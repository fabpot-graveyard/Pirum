<?php

class Pirum_CleanRepo_Builder
{
	public function  __construct($fs, $targetDir) {
		$this->fs        = $fs;
		$this->targetDir = $targetDir;
	}

	public function build()
	{
		foreach ($this->fs->resourceDir($this->targetDir) as $file) {
            if ($file->getFilename() == 'pirum.xml') {
                continue;
            }

            if ($file->isDir()) {
                $this->fs->removeDir($file);
            } else {
                $this->fs->deleteFile($file);
            }
        }
	}
}

?>