<?php

$app = new Pirum(
	array(), new WEB_Formatter(), new FileSystem(), '@package_version@'
);
exit($app->web());

?>
