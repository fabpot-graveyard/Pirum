<?php

/**
 * Builds all the files for a PEAR channel.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum_Build_Command
{
	/**
	 * @var string
	 */
    protected $buildDir;

	/**
	 * @var string
	 */
    protected $targetDir;

	/**
	 * @var Pirum_Channel
	 */
    protected $channel;

	/**
	 * @var FileSystem
	 */
	protected $fs;

	/**
	 * @var CLI_Formatter
	 */
    protected $formatter;

	/**
	 * @var Pirum_StaticAsset_Builder
	 */
	protected $assetBuilder;

	/**
	 * @var Pirum_Repository
	 */
	private $repo;

	/**
	 * @var Pirum_Archive_Handler
	 */
	private $handler;

    public function __construct(
		$targetDir, $baseDir, $version, $fs, $formatter,
		$channel, $repo, $assetBuilder, $handler, $project
	)
    {
        $this->targetDir    = $targetDir;
		$this->baseDir      = $baseDir;
		$this->version      = $version;
		$this->fs           = $fs;
        $this->formatter    = $formatter;
		$this->channel      = $channel;
		$this->repo         = $repo;
		$this->assetBuilder = $assetBuilder;
		$this->handler      = $handler;
		$this->project      = $project;
    }

    public function build()
    {
		$this->repo->processReleasePackageList();
		$this->buildDir = $this->fs->createTempDir('pirum_build', '/rest');

		$this->fixArchives();
        $this->buildPirumWeb();
        $this->buildChannel();
        $this->buildIndex();
        $this->buildCss();
        $this->buildFeed();

		$helper1 = new Pirum_Pear_Helper();
		$helper2 = new Pirum_Pear2_Helper();

		$pearBuilder = new Pirum_PearAsset_Builder(
			$this->buildDir, $this->formatter, $this->repo,
			$this->fs, $this->channel->name, $helper1, $helper2
		);

		$pearBuilder->build();

        $this->formatter->info("Updating PEAR server files");

        copy($this->buildDir.'/pirum.php', $this->targetDir.'/pirum.php');
        if (!file_exists($this->targetDir.'/channel.xml') || file_get_contents($this->targetDir.'/channel.xml') != file_get_contents($this->buildDir.'/channel.xml')) {
            if (file_exists($this->targetDir.'/channel.xml'))
            {
                unlink($this->targetDir.'/channel.xml');
            }

            rename($this->buildDir.'/channel.xml', $this->targetDir.'/channel.xml');
        }
        copy($this->buildDir.'/index.html', $this->targetDir.'/index.html');
        copy($this->buildDir.'/pirum.css', $this->targetDir.'/pirum.css');
        copy($this->buildDir.'/feed.xml', $this->targetDir.'/feed.xml');
        $this->fs->mirrorDir($this->buildDir.'/rest', $this->targetDir.'/rest');
		$this->fs->removeDir($this->buildDir);
   }

    private function buildPirumWeb()
    {
		$job = new Standalone_Builder(
			$this->buildDir.'/pirum.php',
			$this->baseDir.'/stubs/pirum_web_start.php',
			$this->baseDir.'/stubs/pirum_web_end.php',
			$this->baseDir.'/classes'
		);

		$job->run($this->project);
    }

    protected function fixArchives()
    {
        // create tar files when missing
        foreach ($this->fs->resourceDir($this->targetDir.'/get') as $file) {
            if (!Pirum_Package_Release::isPackageFile($file->getFilename())) {
                continue;
            }

            if (file_exists(preg_replace('/\.tgz/', '.tar', $file))) {
				continue;
            }

			$this->handler->fixArchive($this->targetDir, $file);
        }
    }

    protected function buildFeed()
    {
        $this->formatter->info("Building feed");

        $entries = '';
        foreach ($this->repo as $package) {
            foreach ($package['releases'] as $release)
            {
                $entries .= $this->assetBuilder->releaseItemForFeed(
					$this->channel, $package, $release
				);
            }
        }

        $this->fs->writeTo(
			$this->buildDir.'/feed.xml',
			$this->channel->getAtomFeed($entries)
		);
    }

    protected function buildCss()
    {
        file_put_contents(
			$this->buildDir.'/pirum.css',
			$this->assetBuilder->css()
		);
    }

    protected function buildIndex()
    {
        $this->formatter->info("Building index");

        file_put_contents(
			$this->buildDir.'/index.html',
			$this->assetBuilder->indexHtml(
				$this->channel, $this->repo, $this->version
			)
		);
    }

 
    protected function buildChannel()
    {
        $this->formatter->info("Building channel");

        file_put_contents(
			$this->buildDir.'/channel.xml',
			$this->channel->buildChannel()
		);
    }
}

?>
