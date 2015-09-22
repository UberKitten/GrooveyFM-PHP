<?php

header('Content-Type: text/plain');
require_once INCLUDES_DIR . 'cron_security.inc.php';
ob_start();

echo 'Parse Sources cron starting at ', date('r'), PHP_EOL;

require_once INCLUDES_DIR . 'mysql.inc.php';
require_once INCLUDES_DIR . 'curl_post_async.inc.php';

$station = null;
if (isset($_GET['daily']))
{
    echo 'Processing daily-only sources', PHP_EOL;
    $sources = $GFM_MySQLi->query("SELECT stations.id, stations.callsign, sources.type, sources.url FROM stations LEFT JOIN sources ON sources.station = stations.id WHERE sources.daily = 1 AND sources.enabled = 1");
}
else
{
    $sources = $GFM_MySQLi->query("SELECT stations.id, stations.callsign, sources.type, sources.url FROM stations LEFT JOIN sources ON sources.station = stations.id WHERE sources.daily = 0 AND sources.enabled = 1");    
}

while ($source = $sources->fetch_object())
{
    echo 'Starting parse of source ', $source->url, PHP_EOL;
    curl_post_async(ABSOLUTE_URL . 'cron/parsesource.php',
                    array(
                            'password' => urlencode(CRON_PASSWORD),
                            'url' => urlencode($source->url),
                            'stationid' => urlencode($source->id),
                            'callsign' => urlencode($source->callsign),
                            'type' => urlencode($source->type)
                          )
                    );
}

if (isset($_GET['daily']))
{
    file_put_contents(LOGS_DIR . 'parsesources.daily.log', ob_get_contents());
}
else
{
    file_put_contents(LOGS_DIR . 'parsesources.log', ob_get_contents());
}

sleep(4);

ob_end_flush();

?>
