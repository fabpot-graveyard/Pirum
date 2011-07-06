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
        $this->baseDir     = __dir__.'/../../';
		require_once $this->baseDir.'/build.php';

		$this->fs          = new FileSystem();
		$this->channelUrl  = 'http://localhost/pear/';
		$this->channelDesc = 'Dummy Pear Channel';
		$this->webRoot     = '/var/www/pear';

    }

    /**
     * @Given /^pirum xml file is in place$/
     */
    public function pirumXmlFileIsInPlace()
    {
		if (!is_dir($this->webRoot)) {
			mkdir($this->webRoot, 0777, true);
		}
		file_put_contents(
			'/var/www/pear/pirum.xml',
			'<?xml version="1.0" encoding="UTF-8" ?>
			<server>
				<name>dummy</name>
				<summary>'.$this->channelDesc.'</summary>
				<alias>dummy</alias>
				<url>'.$this->channelUrl.'</url>
			</server>'
		);
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
     * @When /^I issue the command `php pirum build webroot`$/
     */
    public function iIssueTheCommandPhpPirumBuild()
    {
        exec('php pirum build '.$this->webRoot, $output, $exitStatus);

		if (0 !== $exitStatus) {
			echo implode(PHP_EOL, $output).PHP_EOL.PHP_EOL;
			throw new Exception();
		}
    }

    /**
     * @Given /^the server index contains channel description$/
     */
    public function theServerIndexContainsChannelDescription()
    {
        $index = file_get_contents($this->channelUrl);
		if (false === strpos($index, $this->channelDesc)) {
			throw new Exception();
		}
    }

    /**
     * @Given /^the channel is discoverable$/
     */
    public function theChannelIsDiscoverable()
    {
		$channel = str_replace('http://', '', $this->channelUrl);
		$tmpDir = $this->fs->createTempDir('pear_installation');
		$cfgFile = $tmpDir.'/dummyconfig';

		exec('pear config-create '.$tmpDir. ' '. $cfgFile, $output, $exitStatus);

		if (0 !== $exitStatus) {
			echo implode(PHP_EOL, $output).PHP_EOL.PHP_EOL;
			$this->fs->removeDir($tmpDir);
			throw new Exception();
		}

        exec('pear -c '.$cfgFile.' channel-discover '.$channel, $output, $exitStatus);

		if (0 !== $exitStatus) {
			echo implode(PHP_EOL, $output).PHP_EOL.PHP_EOL;
			$this->fs->removeDir($tmpDir);
			throw new Exception();
		}

		$this->fs->removeDir($tmpDir);
    }
}

?>
