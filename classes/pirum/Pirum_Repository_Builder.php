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
        $releasePackages = array();
        foreach ($files as $file) {
			$loader = new Pirum_Package_Loader(
				$this->fs, $this->targetDir.'/rest/r/'
			);
			$releasePackages[]= $loader->loadPackage($file);
        }

		return $releasePackages;
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