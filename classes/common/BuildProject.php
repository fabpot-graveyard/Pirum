<?php

class BuildProject
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	/**
	 * @var Executor
	 */
	private $exec;

	public function __construct($fs, $exec)
	{
		$this->fs   = $fs;
		$this->exec = $exec;
	}

	public function runCommandInDir($command, $dir)
	{
		$this->exec->simpleExecInDir($this->fs, $command, $dir);
	}

	public function collector()
	{
		return new ResourceCollection($this->fs);
	}

	public function file($file)
	{
		return new FileResource($this->fs, new SplFileInfo($file));
	}

	public function glob($pattern)
	{
		return new GlobResource($this->fs, $pattern);
	}

	public function fileFilter($extension = '')
	{
		return new FileFilter($this->fs, $extension);
	}

	public function scan($dir, $collector, $filter)
	{
		return new FileScanner(
			$this->fs,
			$this->fs->resourceDir($dir),
			$filter,
			$collector
		);
	}

	public function mergeCollection($collector, $file)
	{
		$collector->mergeTo($file);
	}

	/**
	 * @param ResourceCollection $collection
	 */
	public function deleteCollection($collection)
	{
		$collection->deleteAll();
	}
}

?>