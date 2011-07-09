<?php

class Pirum_Pear_Helper
{
	public function allReleasesItem($release)
	{
		return <<<EOF
    <r>
        <v>{$release['version']}</v>
        <s>{$release['stability']}</s>
    </r>
EOF;
	}

	public function versionXml($channel, $release, $package, $maintainer)
	{
       $url = strtolower($package['name']);

		return <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/$url">{$package['name']}</p>
    <c>$channel</c>
    <v>{$release['version']}</v>
    <st>{$release['stability']}</st>
    <l>{$package['license']}</l>
    <m>{$maintainer['nickname']}</m>
    <s>{$package['summary']}</s>
    <d>{$package['description']}</d>
    <da>{$release['date']}</da>
    <n>{$release['notes']}</n>
    <f>{$release['filesize']}</f>
    <g>$serverUrl/get/{$package['name']}-{$release['version']}</g>
    <x xlink:href="package.{$release['version']}.xml"/>
</r>
EOF;
	}
}

?>