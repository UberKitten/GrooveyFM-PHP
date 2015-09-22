<?php

header('Content-Type: text/plain');
require_once INCLUDES_DIR . 'cron_security.inc.php';
ob_start();

echo 'Update Playlists cron starting at ', date('r'), PHP_EOL;

require_once INCLUDES_DIR . 'mysql.inc.php';
require_once GSAPI_DIR . 'gsAPI.php';
require_once GSAPI_DIR . 'gsUser.php';

$stationsresult = $GFM_MySQLi->query("SELECT id, callsign, playlistname, playlistid FROM stations WHERE enabled = 1");
$stations = array();
while($station = $stationsresult->fetch_assoc())
{
    $stations[] = $station;
}
$stationsresult->free();

$songs = $GFM_MySQLi->prepare("SELECT songid FROM playlists WHERE station = ? ORDER BY id DESC LIMIT " . PLAYLIST_LIMIT);
$songs->bind_param('i', $stationid);
$songs->bind_result($songid);

$updateplaylistid = $GFM_MySQLi->prepare("UPDATE stations SET playlistid = ? WHERE id = ? LIMIT 1");
$updateplaylistid->bind_param('ii', $playlistid, $stationid);

$GSAPI = new gsAPI(GS_WS_KEY, GS_SECRET);
$GSAPI->startSession();
$GSAPI->getCountry('208.94.117.100');

$GSUser = new gsUser();
$GSUser->setUsername(GS_USERNAME);
$GSUser->setTokenFromPassword(GS_PASSWORD);
if (!$GSUser->authenticate())
{
    die('Authentication failed!');
}

foreach ($stations as $station)
{
    echo PHP_EOL, 'Updating station ', $station['callsign'], PHP_EOL;
    
    $stationid = $station['id'];
    $songs->execute();
    $songsarray = array();
    while($songs->fetch())
    {
        $songsarray[] = $songid;
    }
    echo 'SongIDs: ', implode(', ', $songsarray), PHP_EOL;
    
    $playlistid = $station['playlistid'];
    if (!$playlistid)
    {
        $results = $GSAPI->createPlaylist($station['playlistname'], $songsarray);
        $playlistid = $results['playlistID'];
        $updateplaylistid->execute();
        echo 'Created new playlist named ', $station['playlistname'], ' id ', $playlistid, PHP_EOL;
    }
    else
    {
        $GSAPI->setPlaylistSongs($playlistid, $songsarray);
        echo 'Updated existing playlist with id ', $playlistid, PHP_EOL;
    }
}

file_put_contents(LOGS_DIR . 'updateplaylists.log', ob_get_contents());
ob_end_flush();

?>
