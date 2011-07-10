<?php

class ResourceCollection
{
	private $resources = array();

	/**
	 * @var FileSystem
	 */
	private $fs;

	public function __construct($fs)
	{
		$this->fs = $fs;
	}

	public function collect(Resource $resource)
	{
		$this->resources []= $resource;
		return $this;
	}

	public function mergeTo($targetFile)
	{
		$this->fs->deleteFile($targetFile);
		foreach ($this->resources as $resource) {
			$this->fs->appendTo($targetFile, $resource->getContents());
		}
		$this->resources = array();
	}

	public function deleteAll()
	{
		/* @var $resource Resource */
		foreach ($this->resources as $resource) {
			$resource->delete();
		}
		$this->resources = array();
	}
}

?>