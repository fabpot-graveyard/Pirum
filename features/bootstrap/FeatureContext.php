<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\Pending;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

require_once __dir__.'/../../build.php';

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

		$this->fs          = new FileSystem();
		$this->channelName = 'dummy';
		$this->channelUrl  = 'http://localhost/pear/';
		$this->channelDesc = 'Dummy Pear Channel';
		$this->webRoot     = '/var/www/pear';

    }

    /**
     * @Given /^only pirum xml file is in place$/
     */
    public function iCleanTheWebrootAndPlaceOnlyAPirumXmlThere()
    {
		$this->fs->removeDir($this->webRoot);
		$this->fs->writeTo($this->webRoot.'/pirum.xml',
			'<?xml version="1.0" encoding="UTF-8" ?>
			<server>
				<name>'.$this->channelName.'</name>
				<summary>'.$this->channelDesc.'</summary>
				<alias>dummy</alias>
				<url>'.$this->channelUrl.'</url>
			</server>'
		);
    }

    /**
     * @When /^I issue the command `php pirum build webroot`$/
     */
    public function iIssueTheCommandPhpPirumBuild()
    {
		if (0 !== $this->execute('php pirum build '.$this->webRoot)) {
			throw new Exception();
		}
    }

    /**
     * @Given /^the server index contains channel description$/
     */
    public function theServerIndexContainsChannelDescription()
    {
		if (false === $this->textContains($this->serverIndex(), $this->channelDesc)) {
			throw new Exception();
		}
    }

	private function serverIndex()
	{
		return file_get_contents($this->channelUrl);
	}

	private function textContains($baseText, $text, $afterText = '')
	{
		$afterPos = $afterText ? strpos($baseText, $afterText) : 0;
		return false !== strpos($baseText, $text, $afterPos);
	}

    /**
     * @Given /^the channel is discoverable$/
     */
    public function theChannelIsDiscoverable()
    {
		$tmpDir = $this->fs->createTempDir('pear_installation');

		$this->discoverChannel($tmpDir);

		$this->fs->removeDir($tmpDir);
   }

	private function discoverChannel($tmpDir)
	{
		$channel = str_replace('http://', '', $this->channelUrl);
		$cfgFile = $tmpDir.'/dummyconfig';

		if (0 !== $this->execute('pear config-create '.$tmpDir. ' '. $cfgFile)) {
			$this->fs->removeDir($tmpDir);
			throw new Exception();
		}

		if (0 !== $this->execute('pear -c '.$cfgFile.' channel-discover '.$channel)) {
			$this->fs->removeDir($tmpDir);
			throw new Exception();
		}

		return $cfgFile;
	}

	private function execute($command)
	{
		exec($command, $output, $exitStatus);

		if (0 !== $exitStatus) {
			echo implode(PHP_EOL, $output).PHP_EOL.PHP_EOL;
		}

		return $exitStatus;
	}

    /**
     * @Given /^a built up pirum repo is in place$/
     */
    public function aBuiltUpPirumRepoIsInPlace()
    {
        $this->iCleanTheWebrootAndPlaceOnlyAPirumXmlThere();
		$this->iIssueTheCommandPhpPirumBuild();
    }

    /**
     * @Given /^the pirum repo does not contain package$/
     */
    public function thePirumRepoDoesNotContainPackage()
    {
        return false === $this->textContains($this->serverIndex(), 'Dummy-1.0.0', 'Packages');
    }

    /**
     * @When /^I issue the command `php pirum add packagename`$/
     */
    public function iIssueTheCommandPhpPirumAddPackagename()
    {
			$tmpDir   = $this->fs->createTempDir('temp_package');
			$cfgFile  = $this->discoverChannel($tmpDir);
			file_put_contents($tmpDir.'/dummy', '');
			file_put_contents($tmpDir.'/package.xml',
				'<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.8.0" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0
	http://pear.php.net/dtd/tasks-1.0.xsd
	http://pear.php.net/dtd/package-2.0
	http://pear.php.net/dtd/package-2.0.xsd">
 <name>Dummy</name>
 <channel>'.$this->channelName.'</channel>
 <summary>Dummy</summary>
 <description>Dummy</description>
 <lead>
  <name>Dummy</name>
  <user>dummy</user>
  <email>dummy@dummy.net</email>
  <active>yes</active>
 </lead>
 <date>'.date('Y-m-d').'</date>
 <time>12:00:00</time>
 <version>
  <release>1.0.0</release>
  <api>1.0.0</api>
 </version>
 <stability>
  <release>stable</release>
  <api>stable</api>
 </stability>
 <license uri="http://www.opensource.org/licenses/mit-license.php">MIT</license>
 <notes>dummy</notes>
 <contents>
   <dir name="/">
	<file role="script" baseinstalldir="/" name="dummy"></file>
   </dir>
 </contents>
<dependencies>
  <required>
   <php>
	<min>5.2.1</min>
   </php>
   <pearinstaller>
	<min>1.4.0</min>
   </pearinstaller>
  </required>
 </dependencies>
<phprelease>
</phprelease>
</package>');

		$oldCwd = getcwd();
		chdir($tmpDir);
		if (0!==$this->execute('pear -c '.$cfgFile.' package')) {
			throw new Exception();
		}
		chdir($oldCwd);

		if($this->execute('pirum add '.$this->webRoot.' '.$tmpDir.'/Dummy-1.0.0.tgz')) {
			throw new Exception();
		}
	}

    /**
     * @Given /^a package is added$/
     */
    public function aPackageIsAdded()
    {
        $this->iCleanTheWebrootAndPlaceOnlyAPirumXmlThere();
		$this->iIssueTheCommandPhpPirumBuild();
		$this->iIssueTheCommandPhpPirumAddPackagename();
    }

	/**
     * @When /^I issue the command `php pirum remove packagename`$/
     */
    public function iIssueTheCommandPhpPirumRemovePackagename()
    {
        if ($this->execute('pirum remove '.$this->webRoot.' Dummy-1.0.0.tgz')) {
			throw new Exception();
		}
    }

   /**
     * @Then /^the server index contains package description$/
     */
    public function theServerIndexContainsPackageDescription()
    {
        $this->textContains($this->serverIndex(), 'Dummy', 'Packages');
    }

    /**
     * @Given /^the package is installable$/
     */
    public function thePackageIsInstallable()
    {
        $tmpDir  = $this->fs->createTempDir('packageinst');
		$cfgFile = $this->discoverChannel($tmpDir);
		if (0 !== $this->execute('pear -c '.$cfgFile.' install dummy/Dummy')) {
			throw new Exception();
		}
		$this->fs->removeDir($tmpDir);
    }

    /**
     * @When /^I issue the command `php pirum clean`$/
     */
    public function iIssueTheCommandPhpPirumClean()
    {
        if ($this->execute('php pirum clean '.$this->webRoot)) {
			throw new Exception();
		}
		$this->iIssueTheCommandPhpBuildphpClean();
    }

	/**
     * @When /^I issue the command `php build\.php build`$/
     */
    public function iIssueTheCommandPhpBuildphpBuild()
    {
		if($this->execute('php build.php build')) {
			throw new Exception('Failed to execute build');
		}
    }

	/**
     * @When /^I issue the command `php build\.php clean`$/
     */
    public function iIssueTheCommandPhpBuildphpClean()
    {
		if($this->execute('php build.php clean')) {
			throw new Exception('Failed to execute clean');
		}
    }

    /**
     * @Then /^the following files will exist$/
     */
    public function theFollowingFilesWillExist(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
			if (!file_exists($this->baseDir.'/'.$row['File'])) {
				throw new Exception($row['File']. ' is not there');
			}
		}
    }

    /**
     * @Then /^the following files will not exist$/
     */
    public function theFollowingFilesWillNotExist(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
			if (file_exists($this->baseDir.'/'.$row['File'])) {
				throw new Exception($row['File']. ' is still there');
			}
		}
    }

    /**
     * @Given /^pirum is built$/
     */
    public function pirumIsBuilt()
    {
        if (!file_exists($this->baseDir.'/pirum')) {
			$this->iIssueTheCommandPhpBuildphpBuild();
		}
    }

   /**
     * @Given /^the following xml files should exist:$/
     */
    public function theFollowingXmlFilesShouldExist(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
			$absFile = $this->webRoot.'/'.$row['File'];
			if (!file_exists($absFile)) {
				throw new Exception($row['File'].' missing');
			}

			if (!simplexml_load_file($absFile)) {
				throw new Exception($row['File'].' bad xml');
			}

			$content = file_get_contents($absFile);
			if (false === strpos($content, $row['Content'])) {
				throw new Exception($row["Content"] .' not in '. $row['File']);
			}
		}
    }
}

?>
