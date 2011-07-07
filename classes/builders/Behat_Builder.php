<?php

class Behat_Builder
{
	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
	}

	public function run(BuildProject $p)
	{
		$p->runCommandInDir('behat', $this->baseDir);
	}
}

?>