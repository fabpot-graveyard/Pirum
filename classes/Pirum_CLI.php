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
	/**
	 * @var Pirum_CLI_Formatter
	 */
    protected $formatter;
    protected $commands = array(
        'build',
        'add',
        'remove',
        'clean',
    );

    public function __construct(array $options, $formatter)
    {
        $this->options   = $options;
        $this->formatter = $formatter;
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
		$this->formatter->printUsage(self::version());

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

        $target = $this->options[2];

        $ret = 0;
        try {
            switch ($command)
            {
                case 'build':
                    $this->runBuild($target);
                    break;
                case 'add':
                    $ret = $this->runAdd($target);
                    break;
                case 'remove':
                    $ret = $this->runRemove($target);
                    break;
                case 'clean':
                    $ret = $this->runClean($target);
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

    public static function removeDir($target)
    {
        $fp = opendir($target);
        while (false !== $file = readdir($fp)) {
            if (in_array($file, array('.', '..')))
            {
                continue;
            }

            if (is_dir($target.'/'.$file)) {
                self::removeDir($target.'/'.$file);
            } else {
                unlink($target.'/'.$file);
            }
        }
        closedir($fp);
        rmdir($target);
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

        copy($this->options[3], $targetDir.'/get/'.basename($this->options[3]));

        $this->runBuild($targetDir);

        $package = $this->options[3];
    }

	private function isValidPearPackageFileName($pearPackage)
	{
		return (bool)preg_match(Pirum_Package::PACKAGE_FILE_PATTERN, $pearPackage);
	}

    protected function runClean($target)
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
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
			throw new Pirum_Package_Exception('You must pass a PEAR package file path');
        }

        if (!$this->isValidPearPackageFileName($pearPackage)) {
            throw new Pirum_Package_Exception(sprintf(
				'The PEAR package "%s" filename is badly formatted',
				$this->options[3]
			));
        }

		return $this->options[3];
	}

    protected function runBuild($target)
    {
        $builder = new Pirum_Builder($target, $this->formatter);
        $builder->build();
    }

    protected function isCommand($cmd) {
        return in_array($cmd, $this->commands);
    }
}

?>
