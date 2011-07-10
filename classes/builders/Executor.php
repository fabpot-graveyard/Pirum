<?php

class Executor
{
	public function __construct($stdIn, $stdOut, $stdErr)
	{
		$this->stdIn  = $stdIn;
		$this->stdOut = $stdOut;
		$this->stdErr = $stdErr;
	}

	public function simpleExec($command)
	{
		exec($command, $output, $exitStatus);
		foreach ($output as $string) {
			fwrite($this->stdOut, $string.PHP_EOL);
		}

		return $exitStatus;
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