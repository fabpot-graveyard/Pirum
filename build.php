<?php

interface Project {
	public function resourceDir($dir);
}

class PirumProject implements Project
{
	private $baseDir;

	public function  __construct($baseDir) {
		$this->baseDir = $baseDir;
	}

	public static function build()
	{
		$project = new self(__dir__);
		exit($project->buildAll());
	}

	public function resourceDir($dir) {
		return new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$dir,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);
	}

	public function buildAll()
	{
		$this->loadFramework();
		$this->loadBuilders();
		$this->runBuilders();
	}

	private function loadFramework()
	{
		foreach ($this->resourceDir($this->baseDir.'/buildfw') as $file) {
			require_once $file;
		}
	}

	private function loadBuilders()
	{
		foreach ($this->resourceDir($this->baseDir.'/builders') as $file) {
			require_once $file;
		}
	}

	private function runBuilders()
	{
		foreach ($this->builders() as $builder) {
			$builder->build($this);
		}
	}

	function target() {
		return isset($_SERVER['argv'][1])
			? $_SERVER['argv'][1]
			: null;
	}

	function builders() {
		$targetDir = $this->baseDir.'/target';

		switch ($this->target()) {
			case 'clean' : return array(
				new TargetDir_Cleaner($targetDir)
			);

			default :
			return array(
				new Standalone_Builder(
					$targetDir.'/pirum',
					$this->baseDir.'/stubs',
					$this->baseDir.'/classes'
				),
				new PearPackage_Builder($targetDir),
			);
		}
	}
}


if (isset($_SERVER['argv'][0]) && __FILE__ == realpath($_SERVER['argv'][0])) {
	PirumProject::build();
}

?>
