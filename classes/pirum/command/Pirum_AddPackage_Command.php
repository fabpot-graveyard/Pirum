<?php

class Pirum_AddPackage_Command
{
	public function __construct($pirum, $fs, $targetDir)
	{
		$this->pirum     = $pirum;
		$this->fs        = $fs;
		$this->targetDir = $targetDir;
	}

	public function build()
	{
		$pearPackage = $this->pirum->getPearPackage();

		$this->fs->checkFile($pearPackage);
		$this->fs->mkDir($this->targetDir.'/get');
		$this->fs->copyToDir($pearPackage, $this->targetDir.'/get');
	}
}

?>