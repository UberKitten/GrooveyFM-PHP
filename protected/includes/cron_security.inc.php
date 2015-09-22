<?php

if ((!isset($_REQUEST['password']) || $_REQUEST['password'] != CRON_PASSWORD) && !isset($argv) )
{    
    header('HTTP/1.1 403 Forbidden');
    die("403 Forbidden");
}

?>
