<?php

class Pirum_Feed_Builder
{
	public function releaseItem($server, $package, $release)
	{
		$date = date(DATE_ATOM, strtotime($release['date']));

		reset($release['maintainers']);
		$maintainer = current($release['maintainers']);
		$packageHref = $server->getHref($package['name'], $release['version']);

return <<<EOF
    <entry>
        <title>{$package['name']} {$release['version']} ({$release['stability']})</title>
        <link href="$packageHref" />
        <id>{$package['name']}-{$release['version']}</id>
        <author>
            <name>{$maintainer['nickname']}</name>
        </author>
        <updated>$date</updated>
        <content>
            {$package['description']}
        </content>
    </entry>
EOF;
		
	}
}

?>