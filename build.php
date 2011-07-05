<?php

class FileSystem
{
	public function resourceDir($dir) {
		return new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$dir,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);
	}

	public function deleteFile($file)
	{
		if(file_exists($file)) {
			unlink($file);
		}
	}

	public function deleteGlob($glob)
	{
		foreach (glob($glob) as $file) {
			if (is_file($file)) {
				$this->deleteFile($file);
			}
		}
	}

	public function appendTo($file, $contents)
	{
		file_put_contents($file, $contents, FILE_APPEND);
	}

	public function contentsOf($file)
	{
		return file_get_contents($file);
	}
}

interface Builder
{
	public function build(FileSystem $fs);
}

class PirumBuilder
{
	private $baseDir;

	public function  __construct($baseDir, array $argv) {
		$this->baseDir = $baseDir;
		$this->argv    = $argv;
	}

	public function buildAll()
	{
		$fs = new FileSystem();

		$this->loadBuilders($fs);
		$this->runBuilders($fs);
	}

	private function loadBuilders($fs)
	{
		foreach ($fs->resourceDir($this->baseDir.'/builders') as $file) {
			require_once $file;
		}
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
					$this->baseDir.'/stubs',
					$this->baseDir.'/classes'
				),
				new PearPackage_Builder($targetDir),
			);
		}
	}

	public static function build(array $argv = null)
	{
		$project = new self(__dir__, null === $argv ? $_SERVER['argv'] : $argv);
		return $project->buildAll();
	}
}


if (isset($_SERVER['argv'][0]) && __FILE__ == realpath($_SERVER['argv'][0])) {
	exit(PirumBuilder::build());
}

?>
