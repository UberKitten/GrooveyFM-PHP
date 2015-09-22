<?php

header('Content-Type: text/plain');
require_once INCLUDES_DIR . 'cron_security.inc.php';
ob_start();

require_once INCLUDES_DIR . 'mysql.inc.php';
include_once GSAPI_DIR . 'gsAPI.php';

echo 'Pruning of playlist songs starting at ', date('r'), PHP_EOL;

$select = $GFM_MySQLi->query('SELECT `songid` FROM `playlists`');

$songids = array();
while($songid = $select->fetch_row())
{
    $songids[] = $songid[0];
}
$select->free();

$delete = $GFM_MySQLi->prepare('DELETE FROM `playlists` WHERE `songid` = ?');
$delete->bind_param('i', $songid);

$GSAPI = new gsAPI(GS_WS_KEY, GS_SECRET);

$goodsongids = $GSAPI->getSongsInfo($songids, true);

if (count($goodsongids) < 1)
{
 echo 'No songs returned from Grooveshark! Aborting';
}
else
{
 echo "Song ID's to delete:", PHP_EOL;

 foreach($songids as $songid)
 {
  if (!array_key_exists($songid, $goodsongids))
  {
   echo $songid, PHP_EOL;
   $delete->execute();
  }
 }
}
file_put_contents(LOGS_DIR . 'prunetables.log', ob_get_contents());
ob_end_flush();

?>
