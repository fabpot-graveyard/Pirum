<?php

class Pirum_Repository implements IteratorAggregate
{
	private $packageData     = array();
	private $releasePackages = array();

	/**
	 * @var FileSystem
	 */
	private $fs;

	/**
	 * @var CLI_Formatter
	 */
	private $formatter;

	/**
	 * @var Pirum_Package_Loader
	 */
	private $loader;

	public function __construct($targetDir, $fs, $formatter, $loader)
	{
		$this->targetDir  = $targetDir;
		$this->fs         = $fs;
		$this->formatter  = $formatter;
		$this->loader     = $loader;
	}

	public function collectReleasePackageList()
	{
        foreach ($this->getPackageFiles() as $archive) {
			$this->loadPackage($archive);
        }
	}

	public function loadPackage($archive)
	{
		$this->releasePackages[]= $this->loader->loadPackage($archive);
	}

	public function processReleasePackageList()
	{
		/* @var $package Pirum_Package_Release */
        foreach ($this->releasePackages as $file => $package) {
			$package->printProcessingWith($this->formatter);
			$this->initPackageMetaData($package);
			$this->addPackageRelease($package);
			$this->addPackageMaintainers($package);
        }

        ksort($this->packageData);
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

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function initPackageMetaData($package)
	{
		if (isset($this->packageData[$package->getName()])) {
			return;
		}

		$this->packageData[$package->getName()] =
			$package->getMetaData();
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function addPackageRelease($package)
	{
		$this->packageData[$package->getName()]['releases'][] =
			$package->getReleaseData();
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	private function addPackageMaintainers($package)
	{
		$this->packageData[$package->getName()]['maintainers'] =
		array_merge(
			$package->getMaintainers(),
			$this->packageData[$package->getName()]['maintainers']
		);
	}

	private function getReleaseInfoFrom(SplFileInfo $file)
	{
		if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $file->getFileName(), $match)) {
			return null;
		}

		return $match['release'];
	}

	public function  getIterator()
	{
		return new ArrayIterator($this->packageData);
	}
}

?>