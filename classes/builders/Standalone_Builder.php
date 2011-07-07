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
			->collect($project->scan($this->classesDir, $collector, $filter))
			->collect($project->file($this->endStub));

		$project->mergeCollection($collector, $this->targetPath);
	}

    public function build(FileSystem $fs)
    {
        $fs->deleteFile($this->targetPath);

		$fs->appendTo($this->targetPath, $fs->contentsOf($this->startStub));

        foreach ($fs->resourceDir($this->classesDir) as $classFile) {
            $fs->appendTo($this->targetPath, $fs->contentsOf($classFile));
        }

		$fs->appendTo($this->targetPath, $fs->contentsOf($this->endStub));
    }
}

?>

