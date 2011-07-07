<?php
/**
 * Holds the FileScanner class
 *
 * @author fqqdk <simon.csaba@ustream.tv>
 */

/**
 * Description of FileScanner
 */
class FileScanner implements Resource
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	public function  __construct($fs, $resourceDir, $filter, $collector) {
		$this->fs          = $fs;
		$this->resourceDir = $resourceDir;
		$this->filter      = $filter;
		$this->collector   = $collector;
	}

	public function delete()
	{
		foreach ($this->resourceDir as $file)
		{
			$this->fs->deleteFile($file);
		}
	}

	public function getContents()
	{
		$result = '';
		foreach ($this->resourceDir as $file) {
			$result .= $this->fs->contentsOf($file);
		}
		return $result;
	}

	public function mergeTo($targetFile)
	{
		$this->fs->deleteFile($targetFile);
		$this->fs->appendTo($targetFile, $this->getContents());
	}
}

?>