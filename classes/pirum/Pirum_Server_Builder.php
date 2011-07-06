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
    protected $server;
    protected $packages;
    protected $formatter;

    public function __construct($targetDir, $buildDir, $formatter, $server)
    {
		$this->server    = $server;
        $this->formatter = $formatter;
        $this->targetDir = $targetDir;
        $this->buildDir  = $buildDir;
    }

    public function build($fs)
    {
        $this->extractInformationFromPackages($fs);

        $this->fixArchives();
        $this->buildSelf();
        $this->buildChannel();
        $this->buildMaintainers();
        $this->buildCategories();
        $this->buildPackages();
        $this->buildReleasePackages();
        $this->buildIndex();
        $this->buildCss();
        $this->buildFeed();

        $this->updateTargetDir($fs);
    }

    private function buildSelf()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building self");
        file_put_contents($this->buildDir.'/pirum.php', file_get_contents(__FILE__));
    }

    protected function fixArchives()
    {
        // create tar files when missing
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->targetDir.'/get'), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match(Pirum_Package::PACKAGE_FILE_PATTERN, $file->getFileName(), $match)) {
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

    protected function updateTargetDir($fs)
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Updating PEAR server files");

        $this->updateSelf();
        $this->updateChannel();
        $this->updateIndex();
        $this->updateCss();
        $this->updateFeed();
        $this->updatePackages($fs);
    }

    protected function updateSelf()
    {
        copy($this->buildDir.'/pirum.php', $this->targetDir.'/pirum.php');
    }

    protected function updateChannel()
    {
        if (!file_exists($this->targetDir.'/channel.xml') || file_get_contents($this->targetDir.'/channel.xml') != file_get_contents($this->buildDir.'/channel.xml')) {
            if (file_exists($this->targetDir.'/channel.xml'))
            {
                unlink($this->targetDir.'/channel.xml');
            }

            rename($this->buildDir.'/channel.xml', $this->targetDir.'/channel.xml');
        }
    }

    protected function updateIndex()
    {
        copy($this->buildDir.'/index.html', $this->targetDir.'/index.html');
    }

    protected function updateCss()
    {
        copy($this->buildDir.'/pirum.css', $this->targetDir.'/pirum.css');
    }

    protected function updateFeed()
    {
        copy($this->buildDir.'/feed.xml', $this->targetDir.'/feed.xml');
    }

    protected function updatePackages($fs)
    {
        $fs->mirrorDir($this->buildDir.'/rest', $this->targetDir.'/rest');
    }

    protected function buildFeed()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building feed");

        $entries = '';
        foreach ($this->packages as $package) {
            foreach ($package['releases'] as $release)
            {
                $date = date(DATE_ATOM, strtotime($release['date']));

                reset($release['maintainers']);
                $maintainer = current($release['maintainers']);

                $entries .= <<<EOF
    <entry>
        <title>{$package['name']} {$release['version']} ({$release['stability']})</title>
        <link href="{$this->server->url}/get/{$package['name']}-{$release['version']}.tgz" />
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

        $index = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:base="{$this->server->url}">
    <title>{$this->server->summary} Latest Releases</title>
    <link href="{$this->server->url}" />
    <author>
        <name>{$this->server->url}</name>
    </author>

$entries
</feed>
EOF;

        file_put_contents($this->buildDir.'/feed.xml', $index);
    }

    protected function buildCss()
    {
        if (file_exists($file = dirname(__FILE__).'/templates/pirum.css') ||
            file_exists($file = $this->buildDir.'/templates/pirum.css')) {
            $content = file_get_contents($file);
        } else {
            $content = <<<EOF
/*
Copyright (c) 2009, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.net/yui/license.txt
version: 2.8.0r4
*/
html{color:#000;background:#FFF;}body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,button,textarea,p,blockquote,th,td{margin:0;padding:0;}table{border-collapse:collapse;border-spacing:0;}fieldset,img{border:0;}address,caption,cite,code,dfn,em,strong,th,var,optgroup{font-style:inherit;font-weight:inherit;}del,ins{text-decoration:none;}li{list-style:none;}caption,th{text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal;}q:before,q:after{content:'';}abbr,acronym{border:0;font-variant:normal;}sup{vertical-align:baseline;}sub{vertical-align:baseline;}legend{color:#000;}input,button,textarea,select,optgroup,option{font-family:inherit;font-size:inherit;font-style:inherit;font-weight:inherit;}input,button,textarea,select{*font-size:100%;}body{font:13px/1.231 arial,helvetica,clean,sans-serif;*font-size:small;*font:x-small;}select,input,button,textarea,button{font:99% arial,helvetica,clean,sans-serif;}table{font-size:inherit;font:100%;}pre,code,kbd,samp,tt{font-family:monospace;*font-size:108%;line-height:100%;}body{text-align:center;}#doc,#doc2,#doc3,#doc4,.yui-t1,.yui-t2,.yui-t3,.yui-t4,.yui-t5,.yui-t6,.yui-t7{margin:auto;text-align:left;width:57.69em;*width:56.25em;}#doc2{width:73.076em;*width:71.25em;}#doc3{margin:auto 10px;width:auto;}#doc4{width:74.923em;*width:73.05em;}.yui-b{position:relative;}.yui-b{_position:static;}#yui-main .yui-b{position:static;}#yui-main,.yui-g .yui-u .yui-g{width:100%;}.yui-t1 #yui-main,.yui-t2 #yui-main,.yui-t3 #yui-main{float:right;margin-left:-25em;}.yui-t4 #yui-main,.yui-t5 #yui-main,.yui-t6 #yui-main{float:left;margin-right:-25em;}.yui-t1 .yui-b{float:left;width:12.30769em;*width:12.00em;}.yui-t1 #yui-main .yui-b{margin-left:13.30769em;*margin-left:13.05em;}.yui-t2 .yui-b{float:left;width:13.8461em;*width:13.50em;}.yui-t2 #yui-main .yui-b{margin-left:14.8461em;*margin-left:14.55em;}.yui-t3 .yui-b{float:left;width:23.0769em;*width:22.50em;}.yui-t3 #yui-main .yui-b{margin-left:24.0769em;*margin-left:23.62em;}.yui-t4 .yui-b{float:right;width:13.8456em;*width:13.50em;}.yui-t4 #yui-main .yui-b{margin-right:14.8456em;*margin-right:14.55em;}.yui-t5 .yui-b{float:right;width:18.4615em;*width:18.00em;}.yui-t5 #yui-main .yui-b{margin-right:19.4615em;*margin-right:19.125em;}.yui-t6 .yui-b{float:right;width:23.0769em;*width:22.50em;}.yui-t6 #yui-main .yui-b{margin-right:24.0769em;*margin-right:23.62em;}.yui-t7 #yui-main .yui-b{display:block;margin:0 0 1em 0;}#yui-main .yui-b{float:none;width:auto;}.yui-gb .yui-u,.yui-g .yui-gb .yui-u,.yui-gb .yui-g,.yui-gb .yui-gb,.yui-gb .yui-gc,.yui-gb .yui-gd,.yui-gb .yui-ge,.yui-gb .yui-gf,.yui-gc .yui-u,.yui-gc .yui-g,.yui-gd .yui-u{float:left;}.yui-g .yui-u,.yui-g .yui-g,.yui-g .yui-gb,.yui-g .yui-gc,.yui-g .yui-gd,.yui-g .yui-ge,.yui-g .yui-gf,.yui-gc .yui-u,.yui-gd .yui-g,.yui-g .yui-gc .yui-u,.yui-ge .yui-u,.yui-ge .yui-g,.yui-gf .yui-g,.yui-gf .yui-u{float:right;}.yui-g div.first,.yui-gb div.first,.yui-gc div.first,.yui-gd div.first,.yui-ge div.first,.yui-gf div.first,.yui-g .yui-gc div.first,.yui-g .yui-ge div.first,.yui-gc div.first div.first{float:left;}.yui-g .yui-u,.yui-g .yui-g,.yui-g .yui-gb,.yui-g .yui-gc,.yui-g .yui-gd,.yui-g .yui-ge,.yui-g .yui-gf{width:49.1%;}.yui-gb .yui-u,.yui-g .yui-gb .yui-u,.yui-gb .yui-g,.yui-gb .yui-gb,.yui-gb .yui-gc,.yui-gb .yui-gd,.yui-gb .yui-ge,.yui-gb .yui-gf,.yui-gc .yui-u,.yui-gc .yui-g,.yui-gd .yui-u{width:32%;margin-left:1.99%;}.yui-gb .yui-u{*margin-left:1.9%;*width:31.9%;}.yui-gc div.first,.yui-gd .yui-u{width:66%;}.yui-gd div.first{width:32%;}.yui-ge div.first,.yui-gf .yui-u{width:74.2%;}.yui-ge .yui-u,.yui-gf div.first{width:24%;}.yui-g .yui-gb div.first,.yui-gb div.first,.yui-gc div.first,.yui-gd div.first{margin-left:0;}.yui-g .yui-g .yui-u,.yui-gb .yui-g .yui-u,.yui-gc .yui-g .yui-u,.yui-gd .yui-g .yui-u,.yui-ge .yui-g .yui-u,.yui-gf .yui-g .yui-u{width:49%;*width:48.1%;*margin-left:0;}.yui-g .yui-g .yui-u{width:48.1%;}.yui-g .yui-gb div.first,.yui-gb .yui-gb div.first{*margin-right:0;*width:32%;_width:31.7%;}.yui-g .yui-gc div.first,.yui-gd .yui-g{width:66%;}.yui-gb .yui-g div.first{*margin-right:4%;_margin-right:1.3%;}.yui-gb .yui-gc div.first,.yui-gb .yui-gd div.first{*margin-right:0;}.yui-gb .yui-gb .yui-u,.yui-gb .yui-gc .yui-u{*margin-left:1.8%;_margin-left:4%;}.yui-g .yui-gb .yui-u{_margin-left:1.0%;}.yui-gb .yui-gd .yui-u{*width:66%;_width:61.2%;}.yui-gb .yui-gd div.first{*width:31%;_width:29.5%;}.yui-g .yui-gc .yui-u,.yui-gb .yui-gc .yui-u{width:32%;_float:right;margin-right:0;_margin-left:0;}.yui-gb .yui-gc div.first{width:66%;*float:left;*margin-left:0;}.yui-gb .yui-ge .yui-u,.yui-gb .yui-gf .yui-u{margin:0;}.yui-gb .yui-gb .yui-u{_margin-left:.7%;}.yui-gb .yui-g div.first,.yui-gb .yui-gb div.first{*margin-left:0;}.yui-gc .yui-g .yui-u,.yui-gd .yui-g .yui-u{*width:48.1%;*margin-left:0;}.yui-gb .yui-gd div.first{width:32%;}.yui-g .yui-gd div.first{_width:29.9%;}.yui-ge .yui-g{width:24%;}.yui-gf .yui-g{width:74.2%;}.yui-gb .yui-ge div.yui-u,.yui-gb .yui-gf div.yui-u{float:right;}.yui-gb .yui-ge div.first,.yui-gb .yui-gf div.first{float:left;}.yui-gb .yui-ge .yui-u,.yui-gb .yui-gf div.first{*width:24%;_width:20%;}.yui-gb .yui-ge div.first,.yui-gb .yui-gf .yui-u{*width:73.5%;_width:65.5%;}.yui-ge div.first .yui-gd .yui-u{width:65%;}.yui-ge div.first .yui-gd div.first{width:32%;}#hd:after,#bd:after,#ft:after,.yui-g:after,.yui-gb:after,.yui-gc:after,.yui-gd:after,.yui-ge:after,.yui-gf:after{content:".";display:block;height:0;clear:both;visibility:hidden;}#hd,#bd,#ft,.yui-g,.yui-gb,.yui-gc,.yui-gd,.yui-ge,.yui-gf{zoom:1;}

/* Pirum stylesheet */
em { font-style: italic }
strong { font-weight: bold }
small { font-size: 80% }
h1, h2, h3 { font-family:Georgia,Times New Roman,serif; letter-spacing: -0.03em; }
h1 { font-size: 35px; margin-top: 20px; margin-bottom: 30px }
h2 { font-size: 30px; margin-bottom: 20px; margin-top: 15px }
h3 { font-size: 26px; margin-bottom: 10px }
pre { background-color: #000; color: #fff; margin: 5px 0; overflow: auto; padding: 10px; font-family: monospace }
#ft { margin-top: 10px }
ul { margin-top: 5px }
li strong { color: #666 }
p { margin-bottom: 5px }
EOF;
        }

        file_put_contents($this->buildDir.'/pirum.css', $content);
    }

    protected function buildIndex()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building index");

        if (file_exists($file = dirname(__FILE__).'/templates/index.html') ||
            file_exists($file = $this->buildDir.'/templates/index.html')) {
            ob_start();
            include $file;
            $html = ob_get_clean();

            file_put_contents($this->buildDir.'/index.html', $html);

            return;
        }

        ob_start();

		$template = new Pirum_Index_Template($this->server, $this->packages);
		$template->render(Pirum_CLI::version());

        $index = ob_get_clean();

        file_put_contents($this->buildDir.'/index.html', $index);
    }

    protected function buildReleasePackages()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building releases");

        mkdir($this->buildDir.'/rest/r', 0777, true);

        foreach ($this->packages as $package) {
            mkdir($dir = $this->buildDir.'/rest/r/'.strtolower($package['name']), 0777, true);

            $this->buildReleasePackage($dir, $package);
        }
    }

    protected function buildReleasePackage($dir, $package)
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building releases for {$package['name']}");

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
    <c>{$this->server->name}</c>
$allreleases
</a>
EOF
        );

        file_put_contents($dir.'/allreleases2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allreleases2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"     xsi:schemaLocation="http://pear.php.net/dtd/rest.allreleases2 http://pear.php.net/dtd/rest.allreleases2.xsd">
    <p>{$package['name']}</p>
    <c>{$this->server->name}</c>
$allreleases2
</a>
EOF
        );
    }

    protected function buildRelease($dir, $package, $release)
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building release {$release['version']} for {$package['name']}");

        $url = strtolower($package['name']);

        reset($release['maintainers']);
        $maintainer = current($release['maintainers']);

        file_put_contents($dir.'/'.$release['version'].'.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/$url">{$package['name']}</p>
    <c>{$this->server->name}</c>
    <v>{$release['version']}</v>
    <st>{$release['stability']}</st>
    <l>{$package['license']}</l>
    <m>{$maintainer['nickname']}</m>
    <s>{$package['summary']}</s>
    <d>{$package['description']}</d>
    <da>{$release['date']}</da>
    <n>{$release['notes']}</n>
    <f>{$release['filesize']}</f>
    <g>{$this->server->url}/get/{$package['name']}-{$release['version']}</g>
    <x xlink:href="package.{$release['version']}.xml"/>
</r>
EOF
        );

        file_put_contents($dir.'/v2.'.$release['version'].'.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release2 http://pear.php.net/dtd/rest.release2.xsd">
    <p xlink:href="/rest/p/$url">{$package['name']}</p>
    <c>{$this->server->name}</c>
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
    <g>{$this->server->url}/get/{$package['name']}-{$release['version']}</g>
    <x xlink:href="package.{$release['version']}.xml"/>
</r>
EOF
        );

        file_put_contents($dir.'/deps.'.$release['version'].'.txt', $release['deps']);

        $release['info']->copyPackageXml($dir."/package.{$release['version']}.xml");
    }

    protected function buildPackages()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building packages");

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
    <c>{$this->server->name}</c>
$packages
</a>
EOF
        );
    }

    protected function buildPackage($dir, $package)
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building package {$package['name']}");

        $url = strtolower($package['name']);

        file_put_contents($dir.'/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<p xmlns="http://pear.php.net/dtd/rest.package" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.package    http://pear.php.net/dtd/rest.package.xsd">
<n>{$package['name']}</n>
<c>{$this->server->name}</c>
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
    <c>{$this->server->name}</c>
$maintainers
</m>
EOF
        );

        file_put_contents($dir.'/maintainers2.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<m xmlns="http://pear.php.net/dtd/rest.packagemaintainers2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.packagemaintainers2 http://pear.php.net/dtd/rest.packagemaintainers2.xsd">
    <p>{$package['name']}</p>
    <c>{$this->server->name}</c>
$maintainers2
</m>
EOF
        );
    }

    protected function buildCategories()
    {
        $this->formatter and print $this->formatter->formatSection('INFO', "Building categories");

        mkdir($this->buildDir.'/rest/c/Default', 0777, true);

        file_put_contents($this->buildDir.'/rest/c/categories.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<a xmlns="http://pear.php.net/dtd/rest.allcategories" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.allcategories http://pear.php.net/dtd/rest.allcategories.xsd">
    <ch>{$this->server->name}</ch>
    <c xlink:href="/rest/c/Default/info.xml">Default</c>
</a>
EOF
        );

        file_put_contents($this->buildDir.'/rest/c/Default/info.xml', <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<c xmlns="http://pear.php.net/dtd/rest.category" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.category http://pear.php.net/dtd/rest.category.xsd">
    <n>Default</n>
    <c>{$this->server->name}</c>
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
            <c>{$this->server->name}</c>
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
        $this->formatter and print $this->formatter->formatSection('INFO', "Building maintainers");

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
        $this->formatter and print $this->formatter->formatSection('INFO', "Building channel");

        $suggestedalias = '';
        if (!empty($this->server->alias)) {
            $suggestedalias = '
    <suggestedalias>'.$this->server->alias.'</suggestedalias>';
        }

        $validator = '';
        if (!empty($this->server->validatepackage) && !empty($this->server->validateversion)) {
            $validator = '
    <validatepackage version="'.$this->server->validateversion.'">'.$this->server->validatepackage.'</validatepackage>';
        }

        $content = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<channel version="1.0" xmlns="http://pear.php.net/channel-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/channel-1.0 http://pear.php.net/dtd/channel-1.0.xsd">
    <name>{$this->server->name}</name>
    <summary>{$this->server->summary}</summary>{$suggestedalias}
    <servers>
        <primary>
            <rest>
                <baseurl type="REST1.0">{$this->server->url}/rest/</baseurl>
                <baseurl type="REST1.1">{$this->server->url}/rest/</baseurl>
                <baseurl type="REST1.2">{$this->server->url}/rest/</baseurl>
                <baseurl type="REST1.3">{$this->server->url}/rest/</baseurl>
            </rest>
        </primary>
    </servers>{$validator}
</channel>
EOF;

        file_put_contents($this->buildDir.'/channel.xml', $content);
    }

	/**
	 *
	 * @param FileSystem $fs
	 */
    protected function extractInformationFromPackages($fs)
    {
        $this->packages = array();

        // get all package files
        $files = array();
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->targetDir.'/get'), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match(Pirum_Package::PACKAGE_FILE_PATTERN, $file->getFileName(), $match)) {
                continue;
            }

            $files[$match['release']] = (string) $file;
        }

        // order files to have latest versions first
        uksort($files, 'version_compare');
        $files = array_reverse($files);

        // get information for each package
        $packages = array();
        foreach ($files as $file) {
			$packageTmpDir = $fs->createTempDir('pirum_package');
            $package       = new Pirum_Package($file);

			$package->loadWith($this);

            $packages[$file] = $package;
			$fs->removeDir($packageTmpDir);
        }

        foreach ($packages as $file => $package) {
            $this->formatter and print $this->formatter->formatSection('INFO', sprintf('Parsing package %s for %s', $package->getVersion(), $package->getName()));

            if ($package->getChannel() != $this->server->name) {
                throw new Exception(sprintf('Package "%s" channel (%s) is not %s.', $package->getName(), $package->getChannel(), $this->server->name));
            }

            if (!isset($this->packages[$package->getName()])) {
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

            $this->packages[$package->getName()]['maintainers'] = array_merge($package->getMaintainers(), $this->packages[$package->getName()]['maintainers']);
        }

        ksort($this->packages);
    }

	public function getPackageXmlFor($package)
	{
		return $this->targetDir.'/rest/r/'.strtolower($package->getName()).'/package.'.$package->getVersion().'.xml';
	}
}

?>
