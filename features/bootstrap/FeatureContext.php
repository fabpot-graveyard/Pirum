<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\Pending;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
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
        // Initialize your context here
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
     * @Given /^the pirum build files are cleaned$/
     */
    public function thePirumBuildFilesAreCleaned()
    {
		require_once __dir__.'/../../build.php';
		\PirumBuilder::build(array('', 'clean'));
    }

    /**
     * @Given /^the pirum standalone is built$/
     */
    public function thePirumStandaloneIsBuilt()
    {
		require_once __dir__.'/../../build.php';
		\PirumBuilder::build();
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
     * @Given /^pirum files should exist$/
     */
    public function pirumFilesShouldExist()
    {
//		www
//		├── channel.xml
//		├── feed.xml
//		├── get
//		├── index.html
//		├── pirum.css
//		├── pirum.php
//		├── pirum.xml
//		└── rest
//			├── c
//			│   ├── categories.xml
//			│   └── Default
//			│       ├── info.xml
//			│       ├── packagesinfo.xml
//			│       └── packages.xml
//			├── m
//			│   └── allmaintainers.xml
//			├── p
//			│   └── packages.xml
//			└── r
		throw new Pending("check directory tree");
    }

}
