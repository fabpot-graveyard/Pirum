<?php

class Pirum_Base_Builder
{
	private $baseDir;

	public function  __construct($baseDir, array $argv) {
		$this->baseDir = $baseDir;
		$this->argv    = $argv;
	}

	public function buildAll()
	{
		$fs = new FileSystem();

		$this->runBuilders($fs);
	}

	private function runBuilders($fs)
	{
		foreach ($this->builders() as $builder) {
			$builder->build($fs);
		}
	}

	function target() {
		return isset($this->argv[1])
			? $this->argv[1]
			: null;
	}

	function builders() {
		$targetDir = $this->baseDir;

		switch ($this->target()) {
			case 'clean' : return array(
				new TargetDir_Cleaner($targetDir)
			);

			default :
			return array(
				new Standalone_Builder(
					$targetDir.'/pirum',
					$this->baseDir.'/stubs/pirum_start.php',
					$this->baseDir.'/stubs/pirum_end.php',
					$this->baseDir.'/classes'
				),
				new PearPackage_Builder($targetDir),
			);
		}
	}

	public static function build($baseDir, array $argv = null)
	{
		$project = new self($baseDir, $argv);
		return $project->buildAll();
	}
}

?>
