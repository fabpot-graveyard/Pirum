<?php

class Pirum_Channel extends SimpleXMLElement
{
	public $name;
	public $url;
	public $alias;
	public $summary;
	public $validatepackage;
	public $validateversion;

	public function getHref($packageName, $releaseVersion)
	{
		return $this->url.'/get/'.$packageName.'-'. $releaseVersion.'.tgz';
	}

	public function getAtomFeed($entries)
	{
	return <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:base="$this->url">
    <title>$this->summary Latest Releases</title>
    <link href="$this->url" />
    <author>
        <name>$this->url</name>
    </author>

$entries
</feed>
EOF;

	}

	public function buildChannel()
	{
		$serverAlias    = $this->alias;
		$serverName     = $this->name;
		$serverSummary  = $this->summary;
		$serverUrl      = $this->url;
        $suggestedAlias = '';
        if (!empty($serverAlias)) {
            $suggestedAlias = '
	<suggestedalias>'.$serverAlias.'</suggestedalias>';
        }

        $validator = '';

        if (!empty($this->validatepackage) && !empty($this->validateversion)) {
            $validator = '
    <validatepackage version="'.$this->validateversion.'">'.$this->validatepackage.'</validatepackage>';
        }

        return <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<channel version="1.0" xmlns="http://pear.php.net/channel-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/channel-1.0 http://pear.php.net/dtd/channel-1.0.xsd">
    <name>$serverName</name>
    <summary>$serverSummary</summary>$suggestedAlias
    <servers>
        <primary>
            <rest>
                <baseurl type="REST1.0">$serverUrl/rest/</baseurl>
                <baseurl type="REST1.1">$serverUrl/rest/</baseurl>
                <baseurl type="REST1.2">$serverUrl/rest/</baseurl>
                <baseurl type="REST1.3">$serverUrl/rest/</baseurl>
            </rest>
        </primary>
    </servers>{$validator}
</channel>
EOF;

	}

	/**
	 *
	 * @param Pirum_Package_Loader $loader
	 * @param string               $archive
	 */
	public function loadPackage($loader, $archive)
	{
		return $loader->loadPackage($archive);
	}
}

?>