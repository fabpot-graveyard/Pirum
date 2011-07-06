<?php

class PearPackage_Builder
{
	public function  __construct($targetDir)
	{
		$this->targetDir = $targetDir;
	}

    public function build(FileSystem $fs)
    {
		$oldCwd = getcwd();
		chdir($this->targetDir);
        exec('pear package');
		chdir($oldCwd);
    }
}

?>
