<?php

class GlobResource implements Resource
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	public function __construct($fs, $pattern)
	{
		$this->fs      = $fs;
		$this->pattern = $pattern;
	}

	public function delete()
	{
		$this->fs->deleteGlob($this->pattern);
	}

	public function mergeTo($targetFile) {
		$this->fs->appendTo($targetFile, $this->getContents());
	}

	public function getContents()
	{
		$result = '';
		foreach ($this->fs->getFilesOfGlob($this->pattern) as $file) {
			$result .= $this->fs->contentsOf($file);
		}
		return $result;
	}
}

?>