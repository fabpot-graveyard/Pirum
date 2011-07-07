<?php

class Executor
{
	public function simpleExec($command)
	{
		exec($command);
	}

	public function simpleExecInDir($fs, $command, $dir)
	{
		$oldCwd = $fs->getCwd();
		$fs->chDir($dir);
		$this->simpleExec($command);
		$fs->chDir($oldCwd);
	}
}

?>