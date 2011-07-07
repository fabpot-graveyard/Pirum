<?php

class Pirum_Base_Builder
{
	private $baseDir;
	private $commands = array(
		'build' => 'Build pirum standalone file and pear package',
		'clean' => 'Clean build artifacts ^^',
		'test'  => 'Run phpunit and behat tests',
	);

	public function  __construct($formatter, $baseDir, array $argv) {
		$this->formatter = $formatter;
		$this->baseDir   = $baseDir;
		$this->argv      = $argv;
	}

	public function buildAll()
	{
		$fs   = new FileSystem();
		$exec = new Executor(STDIN, STDOUT, STDERR);

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

	private function printUsage()
	{
		$this->formatter->comment('Build for pirum by fqqdk'.PHP_EOL);
		$this->formatter->comment('Usage:'.PHP_EOL.PHP_EOL);

		foreach ($this->commands as $command => $desc) {
			$this->formatter->info($command."\t\t".$desc);
		}
	}

	function builders() {
		if (!isset($this->argv[1])) {
			$this->printUsage();
			return array();
		}

		if (!array_key_exists($this->argv[1], $this->commands)) {
			$this->formatter->error('Invalid build job "%s"', $this->argv[1]);
			$this->printUsage();
			return array();
		}

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
				case 'test': return array(
					new PhpUnit_Builder(
						$this->baseDir,
						'tests/bootstrap.php',
						'tests/'
					),
					new Behat_Builder($this->baseDir),
				);
				default :
					throw new Exception('Invalid build job: '.$buildJob);
			}
		}
	}

	public static function build($baseDir, array $argv = null)
	{
		$project = new Pirum_Base_Builder(
			new Pirum_CLI_Formatter(), $baseDir, $argv
		);

		return $project->buildAll();
	}
}

?>
