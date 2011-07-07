<?php

require_once dirname(__file__).'/../classes/common/FileSystem.php';

$fs = new FileSystem();
$fs->chDir(dirname(__file__).'/../');
$fs->loadClasses('classes');

?>
