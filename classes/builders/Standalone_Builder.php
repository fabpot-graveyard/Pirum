<?php
 
class Standalone_Builder
{
    public function __construct($targetPath, $startStub, $endStub, $classesDir)
    {
        $this->targetPath = $targetPath;
        $this->startStub  = $startStub;
        $this->endStub    = $endStub;
        $this->classesDir = $classesDir;
    }

	public function run(BuildProject $project)
	{
		$collector = $project->collector();
		$filter    = $project->fileFilter('.php');
		$collector
			->collect($project->file($this->startStub))
			->collect($project->scan($this->classesDir.'/_interfaces', $collector, $filter))
			->collect($project->scan($this->classesDir.'/common', $collector, $filter))
			->collect($project->scan($this->classesDir.'/pirum', $collector, $filter))
			->collect($project->file($this->endStub));

		$project->mergeCollection($collector, $this->targetPath);
	}
}

?>

