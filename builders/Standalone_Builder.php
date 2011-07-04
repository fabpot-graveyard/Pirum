<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fqqdk
 * Date: 7/4/11
 * Time: 3:35 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Standalone_Builder implements Builder
{
    public function __construct($targetPath, $stubsDir, $classesDir)
    {
        $this->targetPath = $targetPath;
        $this->stubsDir   = $stubsDir;
        $this->classesDir = $classesDir;
    }

    public function build(Project $project)
    {
        $targetFile = new TargetFile($this->targetPath);
        $targetFile->clean();

        foreach ($project->resourceDir($this->stubsDir) as $classFile) {
            $targetFile->append(file_get_contents($classFile));
        }

        foreach ($project->resourceDir($this->classesDir) as $classFile) {
            $targetFile->append(file_get_contents($classFile));
        }
    }
}

?>

