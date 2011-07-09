<?php

class Pirum_AddPackage_Command
{
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
		$archive = $this->pirum->getPearPackage();

		$this->fs->checkFile($archive);
		$this->fs->mkDir($this->targetDir.'/get');
		$this->fs->copyToDir($archive, $this->targetDir.'/get');

		$this->repo->loadPackage($archive);
	}
}

?>