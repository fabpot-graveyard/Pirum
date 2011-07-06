<?php

/**
 * Command line interface for Pirum.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum_CLI
{
    const VERSION = '@package_version@';

    protected $options;

    protected $commands = array(
        'build',
        'add',
        'remove',
        'clean',
    );

	/**
	 * @var Pirum_CLI_Formatter
	 */
    protected $formatter;

	/**
	 * @var FileSystem
	 */
	protected $fs;

    public function __construct(array $options, $formatter, $fs)
    {
        $this->options   = $options;
        $this->formatter = $formatter;
		$this->fs        = $fs;
    }


    public static function version()
    {
        if (strpos(self::VERSION, '@package_version') === 0) {
            return 'DEV';
        } else {
            return self::VERSION;
        }
    }

	public function run()
    {
		$this->printUsage();

        if (!isset($this->options[1])) {
            return 0;
        }

        $command = $this->options[1];
        if (!$this->isCommand($command)) {
			return $this->formatter->error(
				'"%s" is not a valid command', $command
			);
        }

		$this->formatter->comment("Running the %s command:\n", $command);

        if (!isset($this->options[2]) || !is_dir($this->options[2])) {
			return $this->formatter->error(
				"You must give the root dir of the PEAR channel server"
			);
        }

        $serverDir = $this->options[2];

        $ret = 0;
        try {
            switch ($command)
            {
                case 'build':
                    $this->runBuild($serverDir);
                    break;
                case 'add':
                    $ret = $this->runAdd($serverDir);
                    break;
                case 'remove':
                    $ret = $this->runRemove($serverDir);
                    break;
                case 'clean':
                    $ret = $this->runClean($serverDir);
                    break;
            }

            if (0 == $ret) {
				$this->formatter->info("Command %s run successfully", $command);
            }
        } catch (Pirum_Package_Exception $e) {
			return $this->formatter->error($e->getMessage());
		} catch (Exception $e) {
			return $this->formatter->exception($e);
        }

        return $ret;
    }

	private function printUsage()
	{
		$this->formatter->comment("Pirum %s by Fabien Potencier".PHP_EOL, self::version());
		$this->formatter->comment("Available commands:".PHP_EOL);
		$this->formatter->printUsage(array(
			"  pirum build target_dir",
			"  pirum add target_dir Pirum-1.0.0.tgz",
			"  pirum remove target_dir Pirum-1.0.0.tgz",
		));
	}

    protected function runRemove($targetDir)
    {
		$pearPackage = $this->getPearPackage();

        if (!is_file($targetDir.'/get/'.basename($pearPackage))) {
            return $this->formatter->error(
				'The PEAR package "%s" does not exist in this channel',
				$pearPackage
			);
        }

        unlink($targetDir.'/get/'.basename($pearPackage));
        unlink($targetDir.'/get/'.substr_replace(basename($pearPackage), '.tar', -4));

        $this->runBuild($targetDir);
    }

    protected function runAdd($targetDir)
    {
		$pearPackage = $this->getPearPackage();

        if (!is_file($pearPackage)) {
			throw new Pirum_Package_Exception(sprintf(
				'The PEAR package "%s" does not exist',
				$pearPackage
			));
        }

        if (!is_dir($targetDir.'/get')) {
            mkdir($targetDir.'/get', 0777, true);
        }

        copy($pearPackage, $targetDir.'/get/'.basename($pearPackage));

        $this->runBuild($targetDir);
    }

	private function isValidPearPackageFileName($pearPackage)
	{
		return (bool)preg_match(
			Pirum_Package::PACKAGE_FILE_PATTERN,
			$pearPackage
		);
	}

    protected function runClean($target)
    {
        foreach ($this->fs->resourceDir($target) as $file) {
            if ($file->getFilename() == 'pirum.xml') {
                continue;
            }

            if ($file->isDir()) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }

	private function getPearPackage()
	{
        if (!isset($this->options[3])) {
			throw new Pirum_Package_Exception(
				'You must pass a PEAR package file path'
			);
        }

        if (!$this->isValidPearPackageFileName($this->options[3])) {
            throw new Pirum_Package_Exception(sprintf(
				'The PEAR package "%s" filename is badly formatted',
				$this->options[3]
			));
        }

		return $this->options[3];
	}

    protected function runBuild($targetDir)
    {
		$buildDir = $this->fs->createTempDir('pirum_build', '/rest');
        $this->createServerBuilder($targetDir, $buildDir)->build();
		$this->fs->removeDir($buildDir);
    }

	private function createServerBuilder($targetDir, $buildDir)
	{
        if (!$this->fs->fileExists($targetDir.'/pirum.xml')) {
            throw new InvalidArgumentException(
				'You must create a "pirum.xml" file at the root of the target dir.'
			);
        }

		$this->fs->mkDir($targetDir.'/get');

        $server = simplexml_load_file($targetDir.'/pirum.xml');

        if (!$server) {
            throw new InvalidArgumentException(
				'Invalid pirum.xml (you must have a <server> tag).'
			);
        }

        $emptyFields = array();
        if (empty($server->name)) {
            $emptyFields[] = 'name';
        }
        if (empty($server->summary)) {
            $emptyFields[] = 'summary';
        }
        if (empty($server->url)) {
            $emptyFields[] = 'url';
        }

        if (!empty($emptyFields)) {
            throw new InvalidArgumentException(sprintf(
				'You must fill required tags in your pirum.xml: %s.',
				implode(', ', $emptyFields)
			));
        }

		$repoBuilder = new Pirum_Repository_Builder(
			$server->name,
			$targetDir,
			$this->fs,
			$this->formatter
		);

        return new Pirum_Server_Builder(
			$targetDir, $buildDir,
			$this->fs, $this->formatter,
			$server, $repoBuilder->build()
		);
	}

    protected function isCommand($cmd) {
        return in_array($cmd, $this->commands);
    }
}

?>
