<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Pirum_Index_Template
{
	public function __construct($server, $packages)
	{
		$this->server   = $server;
		$this->packages = $packages;
	}

	public function render($version)
	{ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $this->server->summary ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->server->url ?>/pirum.css" />
    <link rel="alternate" type="application/rss+xml" href="<?php echo $this->server->url ?>/feed.xml" title="<?php echo  $this->server->summary ?> Latest Releases" />
</head>
<body>
	<form style="display:block" method="post" enctype="multipart/form-data" action="pirum.php">
		<input type="file" name="package" />
		<input type="submit" name="submit" value="Send package" />
	</form>
    <div id="doc" class="yui-t7">
        <div id="hd">
            <h1><?php echo $this->server->summary ?></h1>
        </div>
        <div id="bd">
            <div class="yui-g">
                <h2>Using this Channel</h2>
                <p>This channel is to be used with the PEAR installer.</p>
                <em>Registering</em> the channel:
                <pre><code>pear channel-discover <?php echo $this->server->name ?></code></pre>
                <em>Listing</em> available packages:
                <pre><code>pear remote-list -c <?php echo $this->server->alias ?></code></pre>
                <em>Installing</em> a package:
                <pre><code>pear install <?php echo $this->server->alias ?>/package_name</code></pre>
                <em>Installing</em> a specific version/stability:
                <pre><code>pear install <?php echo $this->server->alias ?>/package_name-1.0.0
pear install <?php echo $this->server->alias ?>/package_name-beta</code></pre>
                <em>Receiving</em> updates via a <a href="<?php echo $this->server->url ?>/feed.xml">feed</a>:
                <pre><code><?php echo $this->server->url ?>/feed.xml</code></pre>

                <h2>Packages</h2>

                <?php foreach ($this->packages as $package): ?>
                    <h3><?php echo $package['name'] ?><small> - <?php echo $package['summary'] ?></small></h3>
                    <p><?php echo $package['description'] ?></p>
                    <ul>
                        <li><strong>Package name</strong>: <?php echo $package['name'] ?></li>
                        <li><strong>License</strong>: <?php echo $package['license'] ?></li>
                        <?php
                            $maintainers = array();
                            foreach ($package['current_maintainers'] as $nickname => $maintainer) {
                                $maintainers[] = $maintainer['name'].' (as '.$maintainer['role'].')';
                            }
                            $maintainers = implode(', ', $maintainers);
                        ?>
                        <li><strong>Maintainers</strong>: <?php echo $maintainers ?></li>
                        <?php
                        $releases = array();
                        foreach ($package['releases'] as $release) {
                            $releases[] = "<a href=\"{$this->server->url}/get/{$package['name']}-{$release['version']}.tgz\">{$release['version']}</a> ({$release['stability']})";
                        }
                        $releases = implode(', ', $releases);
                        ?>
                        <li><strong>Releases</strong>: <?php echo $releases ?></li>
                        <li><strong>Install command</strong>: <?php echo $package['extension'] != null ? 'pecl' : 'pear' ?> install <?php echo $this->server->alias ?>/<?php echo $package['name'] ?></li>
                    </ul>

                    <hr />
                <?php endforeach; ?>
            </div>
        </div>
        <div id="ft">
            <p><small>The <em><?php echo $this->server->name ?></em> PEAR Channel Server is proudly powered by <a href="http://www.pirum-project.org/">Pirum</a> <?php echo $version ?></small></p>
        </div>
    </div>
</body>
</html>
	<?php }
}

?>
