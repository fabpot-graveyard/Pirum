<?php

if(php_sapi_name() !== 'CLI' && isset($_REQUEST['__rest']))
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $argv = array(
            __FILE__,
            'add',
            $_FILES['package']['tmp_name']
        );
    }
    else if($_SERVER['REQUEST_METHOD'] == 'DELETE')
    {
        $argv = array(
            __FILE__,
            'delete',
            $_REQUEST['package']
        );
    }
}

// only run Pirum automatically when this file is called directly
// from the command line
if (isset($argv[0]) && __FILE__ == realpath($argv[0]))
{
    $cli = new Pirum_CLI($argv, new CLI_Formatter(), new FileSystem());
    exit($cli->run());
}

?>
