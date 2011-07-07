<?php

class Standalone_Builder_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function itShouldRun()
	{
		$targetPath = 'targetPath';
		$builder    = new Standalone_Builder(
			$targetPath, 'startStub', 'endStub', 'classesDir'
		);

		$collector = $this->getMockBuilder('ResourceCollection')
			->disableOriginalConstructor()
			->getMock();

		$project = $this->getMockBuilder('BuildProject')
			->disableOriginalConstructor()
			->getMock();

		$project
			->expects($this->once())->method('collector')
			->will($this->returnValue($collector));

		$project->expects($this->once())->method('mergeCollection')
			->with($collector, $targetPath);
		$project->expects($this->exactly(2))->method('file')
			->will($this->returnValue($this->getMock('Resource')));
		$project->expects($this->once())->method('scan')
			->will($this->returnValue($this->getMock('Resource')));

		$collector->expects($this->exactly(3))->method('collect')
			->will($this->returnValue($collector));

		$builder->run($project);
	}
}

?>