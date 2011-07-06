<?php
/**
 * @author fqqdk
 */
class TargetDir_Cleaner
{
	public function __construct($targetDir)
	{
		$this->targetDir = $targetDir;
	}

	public function build(FileSystem $fs)
	{
		$fs->deleteFile($this->targetDir.'/pirum');
		$fs->deleteGlob($this->targetDir.'/Pirum-*');
	}
}

?>
