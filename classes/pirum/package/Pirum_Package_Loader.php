<?php

class Pirum_Package_Loader
{
	/**
	 * @var FileSystem
	 */
	private $fs;
	private $xmlDir;

	public function __construct($fs, $xmlDir, $channelName, $handler)
	{
		$this->fs          = $fs;
		$this->xmlDir      = $xmlDir;
		$this->channelName = $channelName;
		$this->handler     = $handler;
	}

	public function loadPackage($archive)
	{
		$tmpDir = $this->fs->createTempDir('Pirum_Package_Release');

		$baseFileName = basename($archive);
		if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $baseFileName, $match)) {
			throw new InvalidArgumentException(sprintf(
				'The archive "%s" does not follow PEAR conventions',
				$archive
			));
		}
		$name       = $match['name'];
		$version    = $match['version'];
		$packageXml = $this->getPackageXmlFile($name, $version);

		if (!file_exists($packageXml)) {
			$packageXml = $this->handler->loadPackageXmlFrom($archive);
		}

		$package = $this->loadPackageFrom($packageXml);
		$releasePackage = new Pirum_Package_Release(
			$archive, $name, $version, $packageXml, $package
		);
		$releasePackage->validateFor($this->channelName);

		$this->fs->removeDir($tmpDir);
		return $releasePackage;
	}

	public function loadPackageFromXml($packageXml)
	{
		$this->packageFile = $file;
		$this->package     = $this->loadPackageFrom($file);

		$this->package->validate($this->name, $this->version);
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