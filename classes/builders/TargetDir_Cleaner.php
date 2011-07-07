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

	public function run(BuildProject $project)
	{
		$collector = $project->collector();
		$collector
			->collect($project->file($this->targetDir.'/pirum'))
			->collect($project->glob($this->targetDir.'/Pirum-*'));

		$project->deleteCollection($collector);
	}
}

?>
