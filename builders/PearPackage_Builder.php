<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fqqdk
 * Date: 7/4/11
 * Time: 3:37 PM
 * To change this template use File | Settings | File Templates.
 */
 
class PearPackage_Builder implements Builder
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
