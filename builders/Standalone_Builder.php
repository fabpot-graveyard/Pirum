<?php
 
class Standalone_Builder implements Builder
{
    public function __construct($targetPath, $stubsDir, $classesDir)
    {
        $this->targetPath = $targetPath;
        $this->stubsDir   = $stubsDir;
        $this->classesDir = $classesDir;
    }

    public function build(FileSystem $fs)
    {
        $fs->deleteFile($this->targetPath);

        foreach ($fs->resourceDir($this->stubsDir) as $classFile) {
            $fs->appendTo($this->targetPath, $fs->contentsOf($classFile));
        }

        foreach ($fs->resourceDir($this->classesDir) as $classFile) {
            $fs->appendTo($this->targetPath, $fs->contentsOf($classFile));
        }
    }
}

?>

