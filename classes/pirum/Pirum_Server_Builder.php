<?php

/**
 * Builds all the files for a PEAR channel.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum_Server_Builder
{
    protected $buildDir;
    protected $targetDir;
    protected $packages;

	/**
	 * @var Pirum_Server
	 */
    protected $server;

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

    public function __construct(
		$targetDir, $fs, $formatter, $server, $packages, $assetBuilder
	)
    {
        $this->targetDir    = $targetDir;
		$this->fs           = $fs;
        $this->formatter    = $formatter;
		$this->server       = $server;
		$this->packages     = $packages;
		$this->assetBuilder = $assetBuilder;
    }

    public function build()
    {
		$this->buildDir = $this->fs->createTempDir('pirum_build', '/rest');

		$this->fixArchives();
        $this->buildSelf();
        $this->buildChannel();
        $this->buildIndex();
        $this->buildCss();
        $this->buildFeed();

        $this->buildMaintainers();
        $this->buildCategories();
        $this->buildPackages();
        $this->buildReleasePackages();


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

    private function buildSelf()
    {
		$this->formatter->info("Building self");
        file_put_contents($this->buildDir.'/pirum.php', file_get_contents(__FILE__));
    }

    protected function fixArchives()
    {
        // create tar files when missing
        foreach ($this->fs->resourceDir($this->targetDir.'/get') as $file) {
            if (!preg_match(Pirum_Package_Release::PACKAGE_FILE_PATTERN, $file->getFileName())) {
                continue;
            }

            $tar = preg_replace('/\.tgz/', '.tar', $file);
            if (!file_exists($tar)) {
                if (function_exists('gzopen')) {
                    $gz = gzopen($file, 'r');
                    $fp = fopen(str_replace('.tgz', '.tar', $file), 'wb');
                    while (!gzeof($gz)) {
                        fwrite($fp, gzread($gz, 10000));
                    }
                    gzclose($gz);
                    fclose($fp);
                } else {
                    system('cd '.$target.'/get/ && gunzip -c -f '.basename($file));
                }
            }
        }
    }

    protected function buildFeed()
    {
        $this->formatter->info("Building feed");

        $entries = '';
        foreach ($this->packages as $package) {
            foreach ($package['releases'] as $release)
            {
                $entries .= $this->assetBuilder->releaseItemForFeed(
					$this->server, $package, $release
				);
            }
        }

        $this->fs->writeTo(
			$this->buildDir.'/feed.xml',
			$this->server->getAtomFeed($entries)
		);
    }

    protected function buildCss()
    {
        if (file_exists($file = dirname(__FILE__).'/templates/pirum.css') ||
            file_exists($file = $this->buildDir.'/templates/pirum.css')) {
            $content = file_get_contents($file);
        } else {
            $content = $this->assetBuilder->css();
        }

        file_put_contents($this->buildDir.'/pirum.css', $content);
    }

    protected function buildIndex()
    {
        $this->formatter->info("Building index");

        ob_start();

		$template = new Pirum_Index_Template($this->server, $this->packages);
		$template->render(Pirum_CLI::version());

        $index = ob_get_clean();

        file_put_contents($this->buildDir.'/index.html', $index);
    }

    protected function buildReleasePackages()
    {
        $this->formatter->info("Building releases");

		$this->fs->mkDir($this->buildDir.'/rest/r');

		foreach ($this->packages as $package) {
			$dir = $this->buildDir.'/rest/r/'.strtolower($package['name']);
            $this->fs->mkDir($dir);
            $this->buildReleasePackage($dir, $package);
        }
    }

    protected function buildReleasePackage($dir, $package)
    {
		$serverName = $this->server->name;

        $this->formatter->info("Building releases for %s", $package['name']);

        $url = strtolower($package['name']);

        $alpha = '';
        $beta = '';
        $stable = '';
        $snapshot = '';
        $allreleases = '';
        $allreleases2 = '';
        foreach ($package['releases'] as $release) {
            if ('stable' == $release['stability'] && !$stable)
            {
                $stable = $release['version'];
            } elseif ('beta' == $release['stability'] && !$beta) {
                $beta = $release['version'];
            } elseif ('alpha' == $release['stability'] && !$alpha) {
                $alpha = $release['version'];
            } elseif ('snapshot' == $release['stability'] && !$snapshot) {
                $snapshot = $release['version'];
            }

            $allreleases .= <<<EOF
    <r>
        <v>{$release['version']}</v>
        <s>{$release['stability']}</s>
    </r>

EOF;

            $allreleases2 .= <<<EOF
    <r>
        <v>{$release['version']}</v>
        <s>{$release['stability']}</s>
        <m>{$release['php']}</m>
    </r>

EOF;

            $this->buildRelease($dir, $package, $release);
        }

        if (count($package['releases'])) {
            file_put_contents($dir.'/latest.txt', $package['releases'][0]['version']);
        }

        if ($stable) {
            file_put_contents($dir.'/stable.txt', $stable);
        }

        if ($beta) {
            file_put_contents($dir.'/beta.txt', $beta);
        }

        if ($alpha) {
            file_put_contents($dir.'/alpha.txt', $alpha);
        }

        if ($snapshot) {
            file_put_contents($dir.'/snapshot.txt', $snapshot);
        }

        file_put_contents($dir.'/allreleases.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"     xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases http://pear.php.net/dtd/rest.allreleases.xsd">
    <p>{$package['name']}</p>
    <c>$serverName</c>
$allreleases
</a>
EOF
        );

        file_put_contents($dir.'/allreleases2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"     xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases2 http://pear.php.net/dtd/rest.allreleases2.xsd">
    <p>{$package['name']}</p>
    <c>$serverName</c>
$allreleases2
</a>
EOF
        );
    }

    protected function buildRelease($dir, $package, $release)
    {
		$serverName = $this->server->name;
		$serverUrl  = $this->server->url;

        $this->formatter->info("Building release %s for %s", $release['version'], $package['name']);

        $url = strtolower($package['name']);

        reset($release['maintainers']);
        $maintainer = current($release['maintainers']);

        file_put_contents($dir.'/'.$release['version'].'.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/$url">{$package['name']}</p>
    <c>$serverName</c>
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
EOF
        );

        file_put_contents($dir.'/v2.'.$release['version'].'.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release2 http://pear.php.net/dtd/rest.release2.xsd">
    <p xlink:href="/rest/p/$url">{$package['name']}</p>
    <c>$serverName</c>
    <v>{$release['version']}</v>
    <a>{$release['api_version']}</a>
    <mp>{$release['php']}</mp>
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
EOF
        );

        file_put_contents($dir.'/deps.'.$release['version'].'.txt', $release['deps']);

		copy($release['packageXml'], $dir."/package.{$release['version']}.xml");
    }

    protected function buildPackages()
    {
		$serverName = $this->server->name;

        $this->formatter->info("Building packages");

        mkdir($this->buildDir.'/rest/p', 0777, true);

        $packages = '';
        foreach ($this->packages as $package) {
            $packages .= "  <p>{$package['name']}</p>\n";

            mkdir($dir = $this->buildDir.'/rest/p/'.strtolower($package['name']), 0777, true);
            $this->buildPackage($dir, $package);
        }

        file_put_contents($this->buildDir.'/rest/p/packages.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allpackages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allpackages http://pear.php.net/dtd/rest.allpackages.xsd">
    <c>$serverName</c>
$packages
</a>
EOF
        );
    }

    protected function buildPackage($dir, $package)
    {
 		$serverName = $this->server->name;

		$this->formatter->info("Building package %s", $package['name']);

        $url = strtolower($package['name']);

        file_put_contents($dir.'/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<p xmlns="http://pear.php.net/dtd/rest.package" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.package    http://pear.php.net/dtd/rest.package.xsd">
<n>{$package['name']}</n>
<c>$serverName</c>
<ca xlink:href="/rest/c/Default">Default</ca>
<l>{$package['license']}</l>
<s>{$package['summary']}</s>
<d>{$package['description']}</d>
<r xlink:href="/rest/r/{$url}" />
</p>
EOF
        );

        $maintainers = '';
        $maintainers2 = '';
        foreach ($package['current_maintainers'] as $nickname => $maintainer) {
            $maintainers .= <<<EOF
    <m>
        <h>{$nickname}</h>
        <a>{$maintainer['active']}</a>
    </m>

EOF;

            $maintainers2 .= <<<EOF
    <m>
        <h>{$nickname}</h>
        <a>{$maintainer['active']}</a>
        <r>{$maintainer['role']}</r>
    </m>

EOF;
        }

        file_put_contents($dir.'/maintainers.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.packagemaintainers" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.packagemaintainers http://pear.php.net/dtd/rest.packagemaintainers.xsd">
    <p>{$package['name']}</p>
    <c>$serverName</c>
$maintainers
</m>
EOF
        );

        file_put_contents($dir.'/maintainers2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.packagemaintainers2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.packagemaintainers2 http://pear.php.net/dtd/rest.packagemaintainers2.xsd">
    <p>{$package['name']}</p>
    <c>$serverName</c>
$maintainers2
</m>
EOF
        );
    }

    protected function buildCategories()
    {
		$serverName = $this->server->name;

		$this->formatter->info("Building categories");

        mkdir($this->buildDir.'/rest/c/Default', 0777, true);

        file_put_contents($this->buildDir.'/rest/c/categories.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allcategories" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allcategories http://pear.php.net/dtd/rest.allcategories.xsd">
    <ch>$serverName</ch>
    <c xlink:href="/rest/c/Default/info.xml">Default</c>
</a>
EOF
        );

        file_put_contents($this->buildDir.'/rest/c/Default/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<c xmlns="http://pear.php.net/dtd/rest.category" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.category http://pear.php.net/dtd/rest.category.xsd">
    <n>Default</n>
    <c>$serverName</c>
    <a>Default</a>
    <d>Default category</d>
</c>
EOF
        );

        $packages = '';
        $packagesinfo = '';
        foreach ($this->packages as $package) {
            $url = strtolower($package['name']);

            $packages .= "  <p xlink:href=\"/rest/p/$url\">{$package['name']}</p>\n";

            $deps = '';
            $releases = '';
            foreach ($package['releases'] as $release) {
                $releases .= <<<EOF
            <r>
                <v>{$release['version']}</v>
                <s>{$release['stability']}</s>
            </r>

EOF;

                $deps .= <<<EOF
        <deps>
            <v>{$release['version']}</v>
            <d>{$release['deps']}</d>
        </deps>

EOF;
            }

            $packagesinfo .= <<<EOF
    <pi>
        <p>
            <n>{$package['name']}</n>
            <c>$serverName</c>
            <ca xlink:href="/rest/c/Default">Default</ca>
            <l>{$package['license']}</l>
            <s>{$package['summary']}</s>
            <d>{$package['description']}</d>
            <r xlink:href="/rest/r/$url" />
        </p>

        <a>
$releases
        </a>

$deps
    </pi>
EOF;
        }

        file_put_contents($this->buildDir.'/rest/c/Default/packages.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<l xmlns="http://pear.php.net/dtd/rest.categorypackages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.categorypackages http://pear.php.net/dtd/rest.categorypackages.xsd">
$packages
</l>
EOF
        );

        file_put_contents($this->buildDir.'/rest/c/Default/packagesinfo.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<f xmlns="http://pear.php.net/dtd/rest.categorypackageinfo" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.categorypackageinfo     http://pear.php.net/dtd/rest.categorypackageinfo.xsd">
$packagesinfo
</f>
EOF
        );
    }

    protected function buildMaintainers()
    {
        $this->formatter->info("Building maintainers");

        mkdir($dir = $this->buildDir.'/rest/m/', 0777, true);

        $all = '';
        foreach ($this->packages as $package) {
            foreach ($package['maintainers'] as $nickname => $maintainer)
            {
                $dir = $this->buildDir.'/rest/m/'.$nickname;

                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                $info = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.maintainer" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.maintainer http://pear.php.net/dtd/rest.maintainer.xsd">
 <h>{$nickname}</h>
 <n>{$maintainer['name']}</n>
 <u>{$maintainer['url']}</u>
</m>
EOF;

                $all .= "  <h xlink:href=\"/rest/m/{$nickname}\">{$nickname}</h>\n";

                file_put_contents($dir.'/info.xml', $info);
            }
        }

        $all = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.allmaintainers" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allmaintainers http://pear.php.net/dtd/rest.allmaintainers.xsd">
$all
</m>
EOF;

        file_put_contents($this->buildDir.'/rest/m/allmaintainers.xml', $all);
    }

    protected function buildChannel()
    {
        $this->formatter->info("Building channel");

        file_put_contents(
			$this->buildDir.'/channel.xml',
			$this->server->buildChannel()
		);
    }
}

?>
