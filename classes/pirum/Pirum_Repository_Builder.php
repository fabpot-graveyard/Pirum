<?php

class Pirum_Repository_Builder
{
	private $packages = array();

	public function __construct($serverName, $targetDir, $fs, $formatter)
	{
		$this->serverName = $serverName;
		$this->targetDir  = $targetDir;
		$this->fs         = $fs;
		$this->formatter  = $formatter;
	}

	private function getServerName()
	{
		return $this->serverName;
	}

	public function build()
	{
		$files    = $this->getPackageFiles();
		$packages = $this->getPackageList($files);

		return $this->processPackageList($packages);
	}

	public function getPackageXmlFor($package)
	{
		return $this->targetDir.'/rest/r/'.strtolower($package->getName()).'/package.'.$package->getVersion().'.xml';
	}

	private function processPackageList($packages)
	{
        foreach ($packages as $file => $package) {
			$this->formatter->info('Parsing package %s for %s', $package->getVersion(), $package->getName());

            if ($package->getChannel() != $this->getServerName()) {
                throw new Exception(sprintf('Package "%s" channel (%s) is not %s.', $package->getName(), $package->getChannel(), $this->server->name));
            }

			$this->initPackageMetaData($package);
			$this->addPackageRelease($package);
			$this->addPackageMaintainers($package);
        }

        ksort($this->packages);

		return $this->packages;
	}
	private function initPackageMetaData($package)
	{
		if (isset($this->packages[$package->getName()])) {
			return;
		}

		$this->packages[$package->getName()] = array(
			'name'        => htmlspecialchars($package->getName()),
			'license'     => htmlspecialchars($package->getLicense()),
			'summary'     => htmlspecialchars($package->getSummary()),
			'description' => htmlspecialchars($package->getDescription()),
			'extension'   => $package->getProvidedExtension(),
			'releases'    => array(),
			'maintainers' => array(),
			'current_maintainers' => $package->getMaintainers(),
		);
	}

	private function addPackageRelease($package)
	{
		$this->packages[$package->getName()]['releases'][] = array(
			'version'     => $package->getVersion(),
			'api_version' => $package->getApiVersion(),
			'stability'   => $package->getStability(),
			'date'        => $package->getDate(),
			'filesize'    => $package->getFilesize(),
			'php'         => $package->getMinPhp(),
			'deps'        => $package->getDeps(),
			'notes'       => htmlspecialchars($package->getNotes()),
			'maintainers' => $package->getMaintainers(),
			'info'        => $package,
		);
	}

	private function addPackageMaintainers($package)
	{
		$this->packages[$package->getName()]['maintainers'] = array_merge(
			$package->getMaintainers(),
			$this->packages[$package->getName()]['maintainers']
		);
	}

	private function getPackageFiles()
	{
        $files = array();

		foreach ($this->fs->resourceDir($this->targetDir.'/get') as $file) {
			if ($this->fs->isDir($file)) {
				continue;
			}
            if (null === $releaseInfo = $this->getReleaseInfoFrom($file)) {
                continue;
            }

            $files[$releaseInfo] = (string) $file;
		}

        // order files to have latest versions first
        uksort($files, 'version_compare');
        $files = array_reverse($files);
		return $files;
	}

	private function getPackageList(array $files)
	{
        $packages = array();
        foreach ($files as $file) {
			$packageTmpDir = $this->fs->createTempDir('pirum_package');
            $package       = new Pirum_Package($file);

			$package->loadWith($this);

            $packages[$file] = $package;
			$this->fs->removeDir($packageTmpDir);
        }

		return $packages;
	}
	private function getReleaseInfoFrom(SplFileInfo $file)
	{
		if (!preg_match(Pirum_Package::PACKAGE_FILE_PATTERN, $file->getFileName(), $match)) {
			return null;
		}

		return $match['release'];
	}
}

?>