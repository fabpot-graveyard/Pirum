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

    public function build(FileSystem $fs)
    {
		$oldCwd = $fs->getcwd();
		$fs->chDir($this->targetDir);
        $fs->exec($this->command);
		$fs->chDir($oldCwd);
    }
}

?>
