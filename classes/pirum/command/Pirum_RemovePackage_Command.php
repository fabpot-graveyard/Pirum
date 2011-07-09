<?php

class Pirum_RemovePackage_Command
{
	/**
	 * @var FileSystem
	 */
	private $fs;

	/**
	 * @var Pirum_Repository
	 */
	private $repo;

	public function __construct($pirum, $fs, $targetDir, $repo)
	{
		$this->pirum     = $pirum;
		$this->fs        = $fs;
		$this->targetDir = $targetDir;
		$this->repo      = $repo;
	}

	public function build()
	{
		$pearPackage = $this->pirum->getPearPackage();

		$archive = $this->targetDir.'/get/'. $this->fs->baseName($pearPackage);

		$this->fs->checkFile($archive);
		$this->fs->deleteFile($archive);
        $this->fs->deleteFile(substr_replace($archive, '.tar', -4));

		$this->repo->removePackage($archive);
	}
}

?>