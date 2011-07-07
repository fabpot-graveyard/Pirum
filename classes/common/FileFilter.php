<?php

class FileFilter
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	public function __construct($fs, $extension = '')
	{
		$this->fs        = $fs;
		$this->extension = null;
	}

	public function accept(SplFileInfo $file)
	{
		if ($this->fs->isDir($file)) {
			return false;
		}

		return $this->hasExtension($file);
	}

	private function hasExtension($file)
	{
		return false !== strrpos($file, $this->extension);
	}
}

?>