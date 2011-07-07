<?php
/**
 * Holds the PhpUnit_Builder class
 *
 * @author fqqdk <simon.csaba@ustream.tv>
 */

/**
 * Description of PhpUnit_Builder
 */
class PhpUnit_Builder
{
	public function __construct($baseDir, $bootstrap, $test)
	{
		$this->baseDir   = $baseDir;
		$this->bootstrap = $bootstrap;
		$this->test      = $test;
	}

	public function run(BuildProject $p)
	{
		$p->runCommandInDir(
			'phpunit --bootstrap '.$this->bootstrap.' '.$this->test,
			$this->baseDir
		);
	}
}

?>