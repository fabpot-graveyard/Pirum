<?php

class Pirum_RemovePackage_Builder
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	public function  __construct($pirum, $fs, $targetDir)
	{
		$this->pirum     = $pirum;
		$this->fs        = $fs;
		$this->targetDir = $targetDir;
	}

	public function build()
	{
		$pearPackage = $this->pirum->getPearPackage();

		$targetFile = $this->targetDir.'/get/'. $this->fs->baseName($pearPackage);

		$this->fs->checkFile($targetFile);
		$this->fs->deleteFile($targetFile);
        $this->fs->deleteFile(substr_replace($targetFile, '.tar', -4));

	}
}

?>