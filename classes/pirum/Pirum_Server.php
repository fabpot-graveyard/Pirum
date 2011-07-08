<?php
/**
 * Holds the Pirum_Server class
 *
 * @author fqqdk <simon.csaba@ustream.tv>
 */

/**
 * Description of Pirum_Server
 */
class Pirum_Server extends SimpleXMLElement
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
}

?>