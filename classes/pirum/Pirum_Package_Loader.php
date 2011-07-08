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
		$name    = $match['name'];
		$version = $match['version'];
		$package = new Pirum_Package_Release($file, $name, $version);

		$packageXml = $this->getPackageXmlFile($name, $version);

		if (file_exists($packageXml)) {
			$package->loadPackageFromXml($this, $packageXml);
		} else {
			$package->loadPackageFromArchive($this, $packageTmpDir);
		}

		$this->fs->removeDir($packageTmpDir);

		return $package;
	}

	private function getPackageXmlFile($name, $version)
	{
		return sprintf(
			'%s/%s/package.%s.xml',
			$this->xmlDir,
			strtolower($name),
			$version
		);
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