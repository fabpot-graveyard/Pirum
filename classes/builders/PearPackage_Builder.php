<?php

class PearPackage_Builder
{
	private $command = 'pear package';
	private $targetDir;

	public function  __construct($targetDir)
	{
		$this->targetDir = $targetDir;
	}

	public function run(BuildProject $p)
	{
		$p->runCommandInDir($this->command, $this->targetDir);
	}
}

?>
