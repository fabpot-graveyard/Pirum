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
    protected $packageXml;

    public function __construct($archive, $name, $version, $packageXml, $package)
    {
        $this->archive    = $archive;
		$this->name       = $name;
		$this->version    = $version;
		$this->packageXml = $packageXml;
		$this->package    = $package;
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
			'packageXml'  => $this->packageXml,
		);
	}

	public function validateFor($channel)
	{
		if ($this->getChannel() != $channel) {
			throw new Pirum_Package_Exception(sprintf(
				'Package "%s" channel (%s) is not %s.',
				$this->getName(),
				$this->getChannel(),
				$this->getServerName()
			));
		}
	}

	public function printProcessingWith($formatter)
	{
		$formatter->info(
			'Parsing package %s for %s',
			$this->getVersion(),
			$this->getName()
		);

	}
}

?>
