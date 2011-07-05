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

