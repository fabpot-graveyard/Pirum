<?php

class FileResource implements Resource
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	public function __construct($fs, SplFileInfo $file)
	{
		$this->fs   = $fs;
		$this->file = $file;
	}

	public function delete()
	{
		$this->fs->deleteFile($this->file);
	}

	public function mergeTo($targetFile)
	{
		$this->fs->appendTo($targetFile, $this->fs->contentsOf($this->file));
	}

	public function getContents()
	{
		return $this->fs->contentsOf($this->file->getPathname());
	}
}

?>