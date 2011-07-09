<?php
/**
 * Holds the Archive_Handler class
 *
 * @author fqqdk <simon.csaba@ustream.tv>
 */

/**
 * Description of Archive_Handler
 */
class Archive_Handler
{
	public function loadPackageXmlFrom($archive)
	{
		if (function_exists('gzopen')) {
			return $this->loadPackageXmlUsingGzopenFrom($archive);
		}

		return $this->loadPackageXmlUsingShellFrom($archive);
	}

	public function fixArchive($targetDir, $file)
	{
		if (function_exists('gzopen')) {
			$gz = gzopen($file, 'r');
			$fp = fopen(str_replace('.tgz', '.tar', $file), 'wb');
			while (!gzeof($gz)) {
				fwrite($fp, gzread($gz, 10000));
			}
			gzclose($gz);
			fclose($fp);
		} else {
			system('cd '.$targetDir.'/get/ && gunzip -c -f '.basename($file));
		}

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
}

?>