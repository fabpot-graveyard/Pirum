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
	 * @var CLI_Formatter
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

        $targetDir = $this->options[2];

        $ret = 0;
        try {
			$server = $this->createServer($targetDir.'/pirum.xml');

			$repo = new Pirum_Repository(
				$targetDir,
				$this->fs,
				$this->formatter,
				$this->createLoader($targetDir, $server->name)
			);

			$this->fs->mkDir($targetDir.'/get');
			$repo->collectReleasePackageList();

			$builders = array(
				$this->builder($command, $targetDir),
				$this->createServerBuilder($targetDir, $server, $repo),
			);

			print gettype($builders[0]) . ' '. gettype($builders[1]).PHP_EOL.PHP_EOL;

			foreach ($builders as $builder) {
				$builder->build();
			}

			$this->formatter->info("Command %s run successfully", $command);
        } catch (Pirum_Package_Exception $e) {
			return $this->formatter->error($e->getMessage());
		} catch (Exception $e) {
			return $this->formatter->exception($e);
        }

        return $ret;
    }

	private function builder($command, $serverDir)
	{
		switch ($command)
		{
			case 'build':
				return new NullBuilder();
			case 'add':
				return new Pirum_AddPackage_Command(
					$this, $this->fs, $serverDir
				);
			case 'remove':
				return new Pirum_RemovePackage_Command(
					$this, $this->fs, $serverDir
				);
			case 'clean':
				return new Pirum_CleanRepo_Command(
					$this->fs, $serverDir
				);
			default:
				throw new Exception('Invalid command!');
		}
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

    protected function isCommand($cmd) {
        return in_array($cmd, $this->commands);
    }

	private function createServerBuilder($targetDir, $server, $repo)
	{
        return new Pirum_Build_Command(
			$targetDir, $this->fs, $this->formatter,
			$server, $repo,
			new Pirum_StaticAsset_Builder()
		);
	}

	private function createServer($pirumXml)
	{
        if (!$this->fs->fileExists($pirumXml)) {
            throw new InvalidArgumentException(
				'You must create a "pirum.xml" file at the root of the target dir.'
			);
        }

		$channel = simplexml_load_file(
			$pirumXml, 'Pirum_Channel'
		);

        if (!$channel) {
            throw new InvalidArgumentException(
				'Invalid pirum.xml (you must have a <server> tag).'
			);
        }

		$channel->validate();

		return $channel;
	}

	private function createLoader($serverDir, $channelName)
	{
		return new Pirum_Package_Loader(
			$this->fs, $serverDir.'/rest/r/', $channelName
		);
	}

	public function getPearPackage()
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

	private function isValidPearPackageFileName($pearPackage)
	{
		return (bool)preg_match(
			Pirum_Package_Release::PACKAGE_FILE_PATTERN,
			$pearPackage
		);
	}
}

?>
