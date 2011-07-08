<?php

class Pirum_Package_Loader
{
	/**
	 * @var FileSystem
	 */
	private $fs;
	private $xmlDir;

	public function __construct($fs, $xmlDir)
	{
		$this->fs     = $fs;
		$this->xmlDir = $xmlDir;
	}

	public function loadPackage($file)
	{
		$packageTmpDir = $this->fs->createTempDir('Pirum_Package_Release');

		$baseFileName = basename($file);
		if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $baseFileName, $match)) {
			throw new InvalidArgumentException(sprintf(
				'The archive "%s" does not follow PEAR conventions',
				$file
			));
		}

		$releasePackage = new Pirum_Package_Release(
			$file, $match['name'], $match['version']
		);

		$releasePackage->loadUsing($this, $packageTmpDir);

		$this->fs->removeDir($packageTmpDir);

		return $releasePackage;
	}

	/**
	 * @param Pirum_Package_Release $package
	 */
	public function getPackageXmlFor($package)
	{
		return $package->getPackageXml($this->xmlDir);
	}

	/**
	 * @param string $file
	 *
	 * @return Pirum_Package
	 */
	public function loadPackageFrom($file)
	{
		return new Pirum_Package($this->fs->contentsOf($file));
	}
}

?>