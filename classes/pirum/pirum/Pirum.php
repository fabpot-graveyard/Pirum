<?php

/**
 * Command line interface for Pirum.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum
{
    private $version;

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

    public function __construct(array $options, $formatter, $fs, $version)
    {
        $this->options   = $options;
        $this->formatter = $formatter;
		$this->fs        = $fs;
		$this->version   = $version;
    }


    private function version()
    {
        if (0 === strpos($this->version, '@package_version')) {
            return 'DEV';
        } else {
            return $this->version;
        }
    }

	public function web()
	{
		$targetDir = dirname(__FILE__);
		$this->formatter->info('Basedir: %s', $targetDir);

		$this->fs->mkDir($targetDir.'/get');
		$tmpDir = $this->fs->createTempDir('pirum_upload');

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$tempFile     = $_FILES['package']['tmp_name'];
			$packageFile  = basename($_FILES['package']['name']);
			$uploadedFile = $tmpDir.'/'.$packageFile;

			$this->formatter->info('Uploading file to: %s', $uploadedFile);

			$succ = move_uploaded_file($tempFile, $uploadedFile);

			if(!$succ) {
				$this->formatter->info('PHP upload error code: %s', $_FILES['package']['error']);
				return $this->formatter->error("There was an error uploading the file, please try again!");
			}

			$this->formatter->info(
				"The file %s has been uploaded",
				$packageFile
			);

			$this->options = array(
				__FILE__,
				'add',
				$targetDir,
				$uploadedFile
			);
		} else if($_SERVER['REQUEST_METHOD'] == 'DELETE') {
			$this->options = array(
				__FILE__,
				'delete',
				$targetDir,
				$_REQUEST['package']
			);
		}
		$this->run($targetDir);
	}

	public function cli()
	{
        if (!isset($this->options[2]) || !is_dir($this->options[2])) {
			return $this->formatter->error(
				"You must give the root dir of the PEAR channel server"
			);
        }

        $targetDir = $this->options[2];
		$this->fs->mkDir($targetDir.'/get');

		$this->run($targetDir);
	}

	private function run($targetDir)
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

        try {
			$server = $this->createServer($targetDir.'/pirum.xml');

			$repo = new Pirum_Repository(
				$targetDir,
				$this->fs,
				$this->formatter,
				$this->createLoader($targetDir, $server->name)
			);

			$repo->collectReleasePackageList();

			$builders = array(
				new Pirum_RepositoryLoad_Command($repo),
				$this->builder($command, $targetDir, $repo),
				$this->createServerBuilder($targetDir, $server, $repo),
			);

			foreach ($builders as $builder) {
				if ($builder) {
					$builder->build();
				}
			}

			$this->formatter->info("Command %s run successfully", $command);
        } catch (Pirum_Package_Exception $e) {
			return $this->formatter->error($e->getMessage());
		} catch (Exception $e) {
			return $this->formatter->exception($e);
        }

        return 0;
    }

	private function builder($command, $serverDir, $repo)
	{
		switch ($command)
		{
			case 'add':
				return new Pirum_AddPackage_Command(
					$this, $this->fs, $serverDir, $repo
				);
			case 'remove':
				return new Pirum_RemovePackage_Command(
					$this, $this->fs, $serverDir, $repo
				);
			case 'clean':
				return new Pirum_CleanRepo_Command(
					$this->fs, $serverDir
				);
		}
	}

	private function printUsage()
	{
		$this->formatter->comment("Pirum %s by Fabien Potencier".PHP_EOL, $this->version());
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
		$exec    = new Executor(STDIN, STDOUT, STDERR);
		$project = new BuildProject($this->fs, $exec);

		return new Pirum_Build_Command(
			$targetDir, dirname(__file__),
			$this->version(), $this->fs, $this->formatter,
			$server, $repo,
			new Pirum_StaticAsset_Builder(),
			$this->createArchiveHandler(),
			$project
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
			$this->fs, $serverDir.'/rest/r/', $channelName,
			$this->createArchiveHandler()
		);
	}

	private function createArchiveHandler()
	{
		return new Pirum_Archive_Handler();
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
