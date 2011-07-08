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
}

?>