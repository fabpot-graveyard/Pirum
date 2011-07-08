<?php
/**
 * Parses a PEAR package and retrieves useful information from it.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum_Package_Release
{
    const PACKAGE_FILE_PATTERN = '#^(?P<release>(?P<name>.+)\-(?P<version>[\d\.]+((?:RC|beta|alpha|dev|snapshot)\d*)?))\.tgz$#i';

    protected $package;

    protected $name;
    protected $version;
    protected $archive;
    protected $packageFile;

    public function __construct($archive, $name, $version)
    {
        $this->archive = $archive;
		$this->name    = $name;
		$this->version = $version;
    }

    public function getDate($format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($this->package->date.' '.$this->package->time));
    }

    public function getLicense()
    {
        return (string) $this->package->license;
    }

    public function getLicenseUri()
    {
        return (string) $this->package->license['uri'];
    }

    public function getDescription()
    {
        return (string) $this->package->description;
    }

    public function getSummary()
    {
        return (string) $this->package->summary;
    }

    public function getChannel()
    {
        return (string) $this->package->channel;
    }

    public function getNotes()
    {
        return (string) $this->package->notes;
    }

    public function getFileSize()
    {
        return filesize($this->archive);
    }

    public function getApiVersion()
    {
        return (string) $this->package->version->api;
    }

    public function getApiStability()
    {
        return (string) $this->package->stability->api;
    }

    public function getStability()
    {
        return (string) $this->package->stability->release;
    }

    public function getMaintainers()
    {
        $maintainers = array();
        foreach ($this->package->lead as $lead) {
            $maintainers[(string) $lead->user] = array(
                'nickname' => (string) $lead->user,
                'role'     => 'lead',
                'email'    => (string) $lead->email,
                'name'     => (string) $lead->name,
                'url'      => (string) $lead->url,
                'active'   => strtolower((string) $lead->active) == 'yes' ? 1 : 0,
            );
        }

        foreach ($this->package->developer as $developer) {
            $maintainers[(string) $developer->user] = array(
                'nickname' => (string) $developer->user,
                'role'     => 'developer',
                'email'    => (string) $developer->email,
                'name'     => (string) $developer->name,
                'url'      => (string) $developer->url,
                'active'   => strtolower((string) $developer->active) == 'yes' ? 1 : 0,
            );
        }

        return $maintainers;
    }

    public function getDeps()
    {
        return serialize($this->XMLToArray($this->package->dependencies));
    }

    public function getMinPhp()
    {
        return isset($this->package->dependencies->required->php->min) ? (string) $this->package->dependencies->required->php->min : null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getProvidedExtension() {
        return isset($this->package->providesextension) ? (string)$this->package->providesextension : null;
    }

    public function copyPackageXml($target)
    {
        copy($this->packageFile, $target);
    }

	/**
	 * @param Pirum_Repository_Builder $repo
	 * @param string                   $packageTmpDir
	 */
	public function loadInto($repo, $packageTmpDir)
	{
		if (file_exists($file = $repo->getPackageXmlFor($this))) {
			$this->loadPackageFromFile($repo, $file);
		} else {
			$this->loadPackageFromArchive($repo, $packageTmpDir);
		}
	}

	/**
	 * @param Pirum_Repository_Builder $repo
	 * @param string                   $file
	 */
    private function loadPackageFromFile($repo, $file)
    {
        $this->packageFile = $file;
        $this->package     = $repo->loadPackageFrom($file);

		$this->package->validate($this->name, $this->version);
    }

	/**
	 * @param Pirum_Repository_Builder $repo
	 * @param string                   $tmpDir
	 */
    private function loadPackageFromArchive($repo, $tmpDir)
    {
        if (!function_exists('gzopen')) {
            copy($this->archive, $tmpDir.'/archive.tgz');
            system('cd '.$tmpDir.' && tar zxpf archive.tgz');

            if (!is_file($tmpDir.'/package.xml')) {
                throw new InvalidArgumentException('The PEAR package does not have a package.xml file.');
            }

            $this->loadPackageFromFile($repo, $tmpDir.'/package.xml');

            return;
        }

        $gz = gzopen($this->archive, 'r');
        $tar = '';
        while (!gzeof($gz)) {
            $tar .= gzread($gz, 10000);
        }
        gzclose($gz);

        while (strlen($tar)) {
            $filename = rtrim(substr($tar, 0, 100), chr(0));
            $filesize = octdec(rtrim(substr($tar, 124, 12), chr(0)));

            if ($filename != 'package.xml') {
                $offset = $filesize % 512 == 0 ? $filesize : $filesize + (512 - $filesize % 512);
                $tar = substr($tar, 512 + $offset);

                continue;
            }

            $checksum = octdec(rtrim(substr($tar, 148, 8), chr(0)));
            $cchecksum = 0;
            $tar = substr_replace($tar, '        ', 148, 8);
            for ($i = 0; $i < 512; $i++) {
                $cchecksum += ord($tar[$i]);
            }

            if ($checksum != $cchecksum) {
                throw new InvalidArgumentException('The PEAR archive is not a valid archive.');
            }

            $package = substr($tar, 512, $filesize);
            $this->packageFile = $tmpDir.'/package.xml';

            file_put_contents($this->packageFile, $package);

            $this->loadPackageFromFile($tmpDir.'/package.xml');

            return;
        }

        throw new InvalidArgumentException('The PEAR package does not have a package.xml file.');
    }

    protected function XMLToArray($xml)
    {
        $array = array();
        foreach ($xml->children() as $element => $value) {
            $key = (string) $element;
            $value = count($value->children()) ? $this->XMLToArray($value) : (string) $value;

            if (array_key_exists($key, $array)) {
                if (!isset($array[$key][0]))
                {
                    $array[$key] = array($array[$key]);
                }
                $array[$key][] = $value;
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

	public function getMetaData()
	{
		return array(
			'name'        => htmlspecialchars($this->getName()),
			'license'     => htmlspecialchars($this->getLicense()),
			'summary'     => htmlspecialchars($this->getSummary()),
			'description' => htmlspecialchars($this->getDescription()),
			'extension'   => $this->getProvidedExtension(),
			'releases'    => array(),
			'maintainers' => array(),
			'current_maintainers' => $this->getMaintainers(),
		);
	}

	public function getReleaseData()
	{
		return array(
			'version'     => $this->getVersion(),
			'api_version' => $this->getApiVersion(),
			'stability'   => $this->getStability(),
			'date'        => $this->getDate(),
			'filesize'    => $this->getFilesize(),
			'php'         => $this->getMinPhp(),
			'deps'        => $this->getDeps(),
			'notes'       => htmlspecialchars($this->getNotes()),
			'maintainers' => $this->getMaintainers(),
			'info'        => $this,
		);
	}
}

?>
