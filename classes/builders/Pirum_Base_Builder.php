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
		$fs   = new FileSystem();
		$exec = new Executor();

		$this->runBuilders($fs, $exec);
	}

	private function runBuilders($fs, $exec)
	{
		$project = new BuildProject($fs, $exec);
		foreach ($this->builders() as $builder) {
			$builder->run($project);
		}
	}

	function buildJobs()
	{
		if (!isset($this->argv[1])) {
			$this->argv[1] = 'build';
		}
		return array_splice($this->argv, 1);
	}

	function builders() {
		$targetDir = $this->baseDir;
		$buildJobs = $this->buildJobs();

		foreach ($buildJobs as $buildJob) {
			switch ($buildJob) {
				case 'clean' : return array(
					new TargetDir_Cleaner($targetDir)
				);

				case 'build': return array(
					new Standalone_Builder(
						$targetDir.'/pirum',
						$this->baseDir.'/stubs/pirum_start.php',
						$this->baseDir.'/stubs/pirum_end.php',
						$this->baseDir.'/classes'
					),
					new PearPackage_Builder($targetDir),
				);
				default :
					throw new Exception('Invalid build job: '.$buildJob);
			}
		}
	}

	public static function build($baseDir, array $argv = null)
	{
		$project = new Pirum_Base_Builder($baseDir, $argv);
		return $project->buildAll();
	}
}

?>
