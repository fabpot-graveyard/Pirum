<?php

class Pirum_PearAsset_Builder
{
	public function __construct(
		$buildDir, $formatter, $repo, $fs,
		$channel, $helper1, $helper2
	)
	{
		$this->buildDir  = $buildDir;
		$this->formatter = $formatter;
		$this->repo      = $repo;
		$this->fs        = $fs;
		$this->channel   = $channel;
		$this->helper1   = $helper1;
		$this->helper2   = $helper2;
	}
	public function build()
	{
        $this->buildReleasePackages($this->channel);
        $this->buildPackages($this->channel);
        $this->buildCategories($this->channel);
        $this->buildMaintainers();

	}
   protected function buildReleasePackages($channel)
    {
        $this->formatter->info("Building releases");

		$this->fs->mkDir($this->buildDir.'/rest/r');

		foreach ($this->repo as $package) {
            $this->buildReleasePackage($channel, $package);
        }
    }

    protected function buildReleasePackage($channel, $package)
    {
		$dir = $this->buildDir.'/rest/r/'.strtolower($package['name']);

		$this->fs->mkDir($dir);

        $this->formatter->info("Building releases for %s", $package['name']);

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

            $allreleases  .= $this->helper1->allReleasesItem($release);
            $allreleases2 .= $this->helper2->allReleasesItem($release);


		$channel   = $this->channel->name;
		$serverUrl = $this->channel->url;

        $this->formatter->info("Building release %s for %s", $release['version'], $package['name']);

        $url = strtolower($package['name']);

        reset($release['maintainers']);
        $maintainer = current($release['maintainers']);

        file_put_contents($dir.'/'.$release['version'].'.xml',
			$this->helper1->versionXml(
				$channel, $release, $package, $maintainer
			)
        );

        file_put_contents($dir.'/v2.'.$release['version'].'.xml',
			$this->helper2->versionXml(
				$channel, $release, $package, $maintainer
			)
        );

        file_put_contents($dir.'/deps.'.$release['version'].'.txt', $release['deps']);

		copy($release['packageXml'], $dir."/package.{$release['version']}.xml");
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
    <c>$channel</c>
$allreleases
</a>
EOF
        );

        file_put_contents($dir.'/allreleases2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"     xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases2 http://pear.php.net/dtd/rest.allreleases2.xsd">
    <p>{$package['name']}</p>
    <c>$channel</c>
$allreleases2
</a>
EOF
        );
    }

    protected function buildPackages($channel)
    {
        $this->formatter->info("Building packages");

        mkdir($this->buildDir.'/rest/p', 0777, true);

        $packages = '';
        foreach ($this->repo as $package) {
            $packages .= "  <p>{$package['name']}</p>\n";

            mkdir($dir = $this->buildDir.'/rest/p/'.strtolower($package['name']), 0777, true);
            $this->buildPackage($channel, $dir, $package);
        }

        file_put_contents($this->buildDir.'/rest/p/packages.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allpackages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allpackages http://pear.php.net/dtd/rest.allpackages.xsd">
    <c>$channel</c>
$packages
</a>
EOF
        );
    }

    protected function buildPackage($channel, $dir, $package)
    {
		$this->formatter->info("Building package %s", $package['name']);

        $url = strtolower($package['name']);

        file_put_contents($dir.'/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<p xmlns="http://pear.php.net/dtd/rest.package" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.package    http://pear.php.net/dtd/rest.package.xsd">
<n>{$package['name']}</n>
<c>$channel</c>
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
    <c>$channel</c>
$maintainers
</m>
EOF
        );

        file_put_contents($dir.'/maintainers2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.packagemaintainers2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.packagemaintainers2 http://pear.php.net/dtd/rest.packagemaintainers2.xsd">
    <p>{$package['name']}</p>
    <c>$channel</c>
$maintainers2
</m>
EOF
        );
    }

    protected function buildCategories($channel)
    {
		$this->formatter->info("Building categories");

        mkdir($this->buildDir.'/rest/c/Default', 0777, true);

        file_put_contents($this->buildDir.'/rest/c/categories.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allcategories" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allcategories http://pear.php.net/dtd/rest.allcategories.xsd">
    <ch>$channel</ch>
    <c xlink:href="/rest/c/Default/info.xml">Default</c>
</a>
EOF
        );

        file_put_contents($this->buildDir.'/rest/c/Default/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<c xmlns="http://pear.php.net/dtd/rest.category" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.category http://pear.php.net/dtd/rest.category.xsd">
    <n>Default</n>
    <c>$channel</c>
    <a>Default</a>
    <d>Default category</d>
</c>
EOF
        );

        $packages = '';
        $packagesinfo = '';
        foreach ($this->repo as $package) {
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
            <c>$channel</c>
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
        foreach ($this->repo as $package) {
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
}

?>