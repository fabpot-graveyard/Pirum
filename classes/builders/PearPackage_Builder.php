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
		ob_start();
		include $this->targetDir.'/package.xml.php';
		$packageXml = ob_get_contents();
		ob_end_clean();
		$p->writeFile($this->targetDir.'/package.xml', $packageXml);
		$p->runCommandInDir($this->command, $this->targetDir);
	}
}

?>
