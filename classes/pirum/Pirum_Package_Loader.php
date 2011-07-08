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
			if (function_exists('gzopen')) {
				$packageXml = $this->loadPackageXmlUsingGzopenFrom($archive);
			} else {
				$packageXml = $this->loadPackageXmlUsingShellFrom($archive);
			}
		}


		$package = $this->loadPackageFrom($packageXml);
		$releasePackage = new Pirum_Package_Release(
			$archive, $name, $version, $packageXml, $package
		);
		$this->fs->removeDir($tmpDir);
		return $releasePackage;
	}

	private function loadPackageXmlUsingShellFrom($archive)
	{
		copy($archive, $tmpDir.'/archive.tgz');
		system('cd '.$tmpDir.' && tar zxpf archive.tgz');

		if (!is_file($tmpDir.'/package.xml')) {
			throw new InvalidArgumentException('The PEAR package does not have a package.xml file.');
		}

		return $tmpDir.'/package.xml';
	}

	private function loadPackageXmlUsingGzopenFrom($archive)
	{
		$tar = $this->loadTar($archive);
		while (strlen($tar)) {
			$filename = rtrim(substr($tar, 0, 100), chr(0));
			$filesize = octdec(rtrim(substr($tar, 124, 12), chr(0)));

			if ($filename != 'package.xml') {
				$offset = $filesize % 512 == 0 ? $filesize : $filesize + (512 - $filesize % 512);
				$tar = substr($tar, 512 + $offset);

				continue;
			}

			$checksum = octdec(rtrim(substr($tar, 148, 8), chr(0)));
			$cchecksum = 0;
			$tar = substr_replace($tar, '        ', 148, 8);
			for ($i = 0; $i < 512; $i++) {
				$cchecksum += ord($tar[$i]);
			}

			if ($checksum != $cchecksum) {
				throw new InvalidArgumentException('The PEAR archive is not a valid archive.');
			}

			$package = substr($tar, 512, $filesize);
			file_put_contents($tmpDir.'/package.xml', $package);
			return $tmpDir.'/package.xml';
		}

		throw new InvalidArgumentException('The PEAR package does not have a package.xml file.');

	}

	private function loadTar($archive)
	{
		$gz = gzopen($archive, 'r');
		$tar = '';
		while (!gzeof($gz)) {
			$tar .= gzread($gz, 10000);
		}
		gzclose($gz);
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