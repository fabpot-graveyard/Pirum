<?php

// only run Pirum automatically when this file is called directly
// from the command line
if (isset($argv[0]) && __FILE__ == realpath($argv[0]))
{
    $app = new Pirum(
		$argv, new CLI_Formatter(), new FileSystem(), '@package_version@'
	);
    exit($app->cli());
}

?>
