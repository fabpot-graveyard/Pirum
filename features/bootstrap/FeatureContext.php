<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\Pending;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

class FeatureContext extends BehatContext
{
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->baseDir = __dir__.'/../../';
		require_once $this->baseDir.'/build.php';
    }

   /**
     * @Given /^there is a directory '(.+)'$/
     */
    public function thereIsADirectory($dir)
    {
        if (!is_dir($dir)) {
			throw new Exception;
		}
    }

	/**
	 * @Given /^the pirum\.xml is in place$/
	 */
    public function thePirumxmlIsInPlace()
    {
		file_put_contents(
			'/var/www/pear/pirum.xml',
			'<?xml version="1.0" encoding="UTF-8" ?>
	<server>
		<name>dummy</name>
		<summary>Dummy PEAR channel</summary>
		<alias>dummy</alias>
		<url>http://localhost/</url>
	</server>');
    }

   /**
     * @Given /^the pirum build files are cleaned$/
     */
    public function thePirumBuildFilesAreCleaned()
    {

		Pirum_Base_Builder::build($this->baseDir, array('', 'clean'));
    }

    /**
     * @Given /^the pirum standalone is built$/
     */
    public function thePirumStandaloneIsBuilt()
    {
		Pirum_Base_Builder::build($this->baseDir, array(''));
	}

    /**
     * @When /^I issue the command `php pirum build '(.+)'`$/
     */
    public function iIssueTheCommandPhpPirumBuild($dir)
    {
        exec('php pirum build '.$dir, $this->output, $this->exitStatus);
    }

    /**
     * @Then /^the exit status of the command should be (\d+)$/
     */
    public function theExitStatusOfTheCommandShouldBe($exitStatus)
    {
        if ($exitStatus != $this->exitStatus) {
			echo implode(PHP_EOL, $this->output).PHP_EOL.PHP_EOL;
			throw new Exception();
		}
    }

    /**
     * @Given /^the following files should exist in '([^\']*)':$/
     */
    public function theFollowingFilesShouldExistIn($dir, TableNode $table)
    {
		foreach ($table->getHash() as $row) {
			if (!file_exists($dir.'/'.$row['file'])) {
				throw new Exception('File '.$row['file'].' does not exist!');
			}
		}
    }

}
