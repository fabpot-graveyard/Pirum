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

	private function createPackage($name)
	{
		$tmpDir   = $this->fs->createTempDir('temp_package');
		$cfgFile  = $this->discoverChannel($tmpDir);

		$this->fs->copyToDir(__dir__.'/'.$name.'/dummy', $tmpDir);
		$this->fs->copyToDir(__dir__.'/'.$name.'/package.xml', $tmpDir);

		$oldCwd = getcwd();
		chdir($tmpDir);
		if (0!==$this->execute('pear -c '.$cfgFile.' package')) {
			throw new Exception();
		}
		chdir($oldCwd);
		return $tmpDir.'/'.$name.'-1.0.0.tgz';
	}

    /**
     * @When /^I issue the command `php pirum add packagename`$/
     */
    public function iIssueTheCommandPhpPirumAddPackagename()
    {
		$file = $this->createPackage('Dummy');
		if($this->execute('pirum add '.$this->webRoot.' '.$file)) {
			throw new Exception();
		}
	}

    /**
     * @When /^I POST a package file to the API URL$/
     */
    public function iPOSTAPackageFileToTheAPIURL()
    {
		$file = $this->createPackage('Dummy2');

		$request_url            = $this->channelUrl.'/pirum.php';
        $post_params['package'] = $file;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
        $result = curl_exec($ch);
        curl_close($ch);

		throw new Exception(strip_tags($result));

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
     * @Given /^the package '([^']+)' is installable$/
     */
    public function thePackageIsInstallable($package)
    {
        $tmpDir  = $this->fs->createTempDir('packageinst');
		$cfgFile = $this->discoverChannel($tmpDir);
		if (0 !== $this->execute('pear -c '.$cfgFile.' install dummy/'.$package)) {
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
