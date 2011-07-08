<?php

class Pirum_Repository_Builder
{
	private $packages = array();

	/**
	 * @var FileSystem
	 */
	private $fs;

	/**
	 * @var CLI_Formatter
	 */
	private $formatter;

	public function __construct($serverName, $targetDir, $fs, $formatter)
	{
		$this->serverName = $serverName;
		$this->targetDir  = $targetDir;
		$this->fs         = $fs;
		$this->formatter  = $formatter;
	}

	private function getServerName()
	{
		return $this->serverName;
	}

	public function build()
	{
		$files    = $this->getPackageFiles();
		$packages = $this->getPackageList($files);

		return $this->processPackageList($packages);
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	public function getPackageXmlFor($package)
	{
		return $package->getPackageXml($this->targetDir.'/rest/r/');
	}

	private function processPackageList($packages)
	{
		/* @var $package Pirum_Package_Release */
        foreach ($packages as $file => $package) {
			$this->formatter->info('Parsing package %s for %s', $package->getVersion(), $package->getName());

            if ($package->getChannel() != $this->getServerName()) {
				throw new Pirum_Package_Release_Exception(sprintf(
					'Package "%s" channel (%s) is not %s.',
					$package->getName(),
					$package->getChannel(),
					$this->getServerName()
				));
            }

			$this->initPackageMetaData($package);
			$this->addPackageRelease($package);
			$this->addPackageMaintainers($package);
        }

        ksort($this->packages);

		return $this->packages;
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function initPackageMetaData($package)
	{
		if (isset($this->packages[$package->getName()])) {
			return;
		}

		$this->packages[$package->getName()] = 
			$package->getMetaData();
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function addPackageRelease($package)
	{
		$this->packages[$package->getName()]['releases'][] =
			$package->getReleaseData();
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function addPackageMaintainers($package)
	{
		$this->packages[$package->getName()]['maintainers'] =
		array_merge(
			$package->getMaintainers(),
			$this->packages[$package->getName()]['maintainers']
		);
	}

	private function getPackageFiles()
	{
        $files = array();

		foreach ($this->fs->resourceDir($this->targetDir.'/get') as $file) {
			if ($this->fs->isDir($file)) {
				continue;
			}
            if (null === $releaseInfo = $this->getReleaseInfoFrom($file)) {
                continue;
            }

            $files[$releaseInfo] = (string) $file;
		}

        // order files to have latest versions first
        uksort($files, 'version_compare');
        $files = array_reverse($files);
		return $files;
	}

	private function getPackageList(array $files)
	{
        $packages = array();
        foreach ($files as $file) {
			$packageTmpDir = $this->fs->createTempDir('Pirum_Package_Release');
			$packages[$file] = $this->loadPackageReleaseFrom($file, $packageTmpDir);
			$this->fs->removeDir($packageTmpDir);
        }

		return $packages;
	}

	public function loadPackageFrom($file)
	{
		return new Pirum_Package($this->fs->contentsOf($file));
	}

	private function loadPackageReleaseFrom($file, $packageTmpDir)
	{
		$package = $this->createReleasePackage($file);
		$package->loadInto($this, $packageTmpDir);
		return $package;
	}

	private function createReleasePackage($file)
	{
		$baseFileName = basename($file);
        if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $baseFileName, $match)) {
            throw new InvalidArgumentException(sprintf(
				'The archive "%s" does not follow PEAR conventions',
				$file
			));
        }

		return new Pirum_Package_Release(
			$file, $match['name'], $match['version']
		);
	}


	private function getReleaseInfoFrom(SplFileInfo $file)
	{
		if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $file->getFileName(), $match)) {
			return null;
		}

		return $match['release'];
	}
}

?>