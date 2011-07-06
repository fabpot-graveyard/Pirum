<?php

require_once 'classes/common/FileSystem.php';

$fs = new FileSystem();
$fs->loadClasses('classes');

if (isset($_SERVER['argv'][0]) && __FILE__ == realpath($_SERVER['argv'][0])) {
	exit(Pirum_Base_Builder::build(__dir__, $_SERVER['argv']));
}

?>
