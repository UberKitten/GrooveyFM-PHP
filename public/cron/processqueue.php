<?php

header('Content-Type: text/plain');
require_once INCLUDES_DIR . 'cron_security.inc.php';

ob_start();

echo 'Process Queue cron starting at ', date('r'), PHP_EOL;

require_once INCLUDES_DIR . 'mysql.inc.php';
include_once GSAPI_DIR . 'gsAPI.php';

$songsresult = $GFM_MySQLi->query("SELECT searchqueue.title, searchqueue.artist, stations.id FROM searchqueue LEFT JOIN stations ON stations.id = searchqueue.station WHERE stations.enabled =1");
$songs = array();
while($song = $songsresult->fetch_assoc())
{
    $songs[] = $song;
}
$songsresult->free();

$searchesthishour = $GFM_MySQLi->prepare("SELECT SUM(searches) FROM stats WHERE date = ? AND hour = ?");
$date = date('Y-m-d');
$hour = date('H');
$searchesthishour->bind_param('si', $date, $hour);
$searchesthishour->execute();
$searchesthishour->bind_result($searchesrunningtotal);
$searchesthishour->fetch();
$searchesthishour->close();

$noresults = $GFM_MySQLi->prepare("INSERT INTO noresults (title, artist) VALUE (?, ?)");
$noresults->bind_param('ss', $title, $artist);

$existing = $GFM_MySQLi->prepare("SELECT songid, station FROM playlists WHERE title = ? AND artist = ?");
$existing->bind_param('ss', $title, $artist);

$deleteplaylists = $GFM_MySQLi->prepare("DELETE FROM playlists WHERE title = ? AND artist = ? AND station = ?");
$deleteplaylists->bind_param('ssi', $title, $artist, $stationid);

$deletesearchqueue = $GFM_MySQLi->prepare("DELETE FROM searchqueue WHERE title = ? AND artist = ?");
$deletesearchqueue->bind_param('ss', $title, $artist);

$insert = $GFM_MySQLi->prepare("INSERT INTO playlists (title, artist, songid, station) VALUES (?, ?, ?, ?)");
$insert->bind_param('sssi', $title, $artist, $songid, $stationid);

$stationstats = array();

$GSAPI = false;
$GSAPISearch = false;

$searchcounter = 0;

foreach ($songs as $song)
{   
    $title = $song['title'];
    $artist = $song['artist'];
    $stationid = $song['id'];
    $songid = '';
    
    echo PHP_EOL, 'Searching for song ', $title, ' by ', $artist, PHP_EOL;
    
    if (!@is_array($stationstats[$stationid]))
    {
        $stationstats[$stationid] = array('searches' => 0, 'inserts' => 0);
    }
    
    $existing->execute();
    $existing->bind_result($esongid, $estation);
    $existingsongs = array();
    while($existing->fetch())
    {
        $existingsongs[] = array('songid' => $esongid, 'station' => $estation);
    }

    foreach($existingsongs as $existingsong)
    {
        $songid = $existingsong['songid'];
        if ($existingsong['station'] == $stationid)
        {
            echo 'Same-station match found with songid ', $songid, ', deleting', PHP_EOL;
            // Delete the match found, it will be recreated later with a higher id
            if (!$deleteplaylists->execute())
            {
                echo 'Error deleting: ', $deleteplaylists->error, PHP_EOL;
            }
        }
        else
        {
            echo 'Different-station match found with songid ', $songid, PHP_EOL;
        }
    }
    
    if ($songid == '')
    {
        if ($searchcounter <= SEARCH_LIMIT && ($searchesrunningtotal + $searchcounter) <= SEARCH_HOURLY_LIMIT)
        {
            $deletesearchqueue->execute();
            
            $searchcounter++;
            if (!$GSAPI)
            {
                require_once GSAPI_DIR . 'gsSearch.php';
                $GSAPI = new gsapi(GS_WS_KEY, GS_SECRET);
                $GSAPI->startSession();
                $GSAPI->getCountry($_SERVER['72.64.99.183']);
                $GSAPISearch = new gsSearch();
            }
            $GSAPISearch->setTitle($title);
            $GSAPISearch->setArtist($artist);
            $results = $GSAPISearch->songSearchResults(20);
            $stationstats[$stationid]['searches']++;
            
            if (count($results) < 1)
            {
                continue;
            }
            if ($results == null)
            {
                echo 'No search results, adding to noresults table (this script search counter: ', $searchcounter, ' hourly running total: ', $searchesrunningtotal, ')', PHP_EOL;
                $noresults->execute();
                continue;
            }
            
            $scoredsongs = array();
            foreach ($results as $songresult)
            {
                $score = 0;
                
                // Artist match
                if (stripos($songresult['ArtistName'], $artist) !== false)
                {
                    $score += 5;
                }
                
                // Song match
                if (stripos($songresult['SongName'], $title) !== false)
                {
                    $score += 15;
                }
                
                // Is Explicit, heh
                if (stripos($songresult['SongName'], 'explicit') !== false ||
                    stripos($songresult['SongName'], 'dirty') !== false)
                {
                    $score += 5;
                }
                
                // Is a remix
                if (stripos($songresult['SongName'], 'mix') !== false )
                {
                    $score -= 10;
                }
                
                // Song is bleh
                if (stripos($songresult['SongName'], 'live') !== false ||
                    stripos($songresult['SongName'], 'acoustic') !== false ||
                    stripos($songresult['SongName'], 'karaoke') !== false ||
                    stripos($songresult['SongName'], 'instrumental') !== false)
                {
                    $score -= 15;
                }
                
                // Album is bleh
                if (stripos($songresult['ArtistName'], 'live') !== false ||
                    stripos($songresult['ArtistName'], 'acoustic') !== false ||
                    stripos($songresult['ArtistName'], 'karaoke') !== false ||
                    stripos($songresult['ArtistName'], 'intrumental') !== false)
                {
                    $score -= 10;
                }
                
                // Artist is bleh
                if (stripos($songresult['ArtistName'], 'live') !== false ||
                    stripos($songresult['ArtistName'], 'acoustic') !== false ||
                    stripos($songresult['ArtistName'], 'karaoke') !== false ||
                    stripos($songresult['ArtistName'], 'instrumental') !== false)
                {
                    $score -= 10;
                }
                
                // Song contains www or com, probably bad copy
                if (stripos($songresult['SongName'], 'www') !== false ||
                    stripos($songresult['SongName'], 'com') !== false)
                {
                    $score -= 10;
                }
                
                // Artist contains www or com, probably bad copy
                if (stripos($songresult['ArtistName'], 'www') !== false ||
                    stripos($songresult['ArtistName'], 'com') !== false)
                {
                    $score -= 10;
                }
                
                // Album contains www or com, probably bad copy
                if (stripos($songresult['AlbumName'], 'www') !== false ||
                    stripos($songresult['AlbumName'], 'com') !== false)
                {
                    $score -= 10;
                }
                
                // Popularity bonus score
                $score += 1/100000000 * $songresult['Popularity'];
                
                echo 'Title: ', $songresult['SongName'], ' Artist: ', $songresult['ArtistName'], ' Album: ', $songresult['AlbumName'], ' Popularity: ', $songresult['Popularity'], ' Score: ', $score, PHP_EOL;
                $scoredsongs[$songresult['SongID']] = $score;
            }
            arsort($scoredsongs, SORT_NUMERIC);
            $songid = array_keys($scoredsongs);
            $songid = $songid[0];
            
            echo 'Inserting with searched songid ', $songid, PHP_EOL;
            $insert->execute();
        }
        else
        {
            echo 'No match and reached search limit, not attempting', PHP_EOL;
        }
    }
    else
    {
        echo 'Inserting with matched songid ', $songid, PHP_EOL;
        $insert->execute();
        $deletesearchqueue->execute();
        
        $stationstats[$stationid]['inserts']++;
    }
}

$existing->free_result();
$existing->close();
$deleteplaylists->close();
$deletesearchqueue->close();
$insert->close();

echo PHP_EOL, 'Beginning updating of station stats', PHP_EOL;

$statsinsert = $GFM_MySQLi->prepare("INSERT INTO stats (date, hour, station, searches, inserts) VALUES (?, ?, ?, ?, ?)");
$statsinsert->bind_param('siiii', $date, $hour, $stationid, 
$searches, 
$inserts);

$statsupdate = $GFM_MySQLi->prepare("UPDATE stats SET searches = ?, inserts = ? WHERE date = ? AND hour = ? AND station = ?");
$statsupdate->bind_param('iisii', $searches, $inserts, $date, $hour, 
$stationid);

$statsselect = $GFM_MySQLi->prepare("SELECT searches, inserts FROM stats WHERE date = ? AND hour = ? AND station = ?");
$statsselect->bind_param('sii', $date, $hour, $stationid);
$statsselect->bind_result($prevsearches, $previnserts);

foreach($stationstats as $stationid => $stationstat)
{
    $searches = $stationstat['searches'];
    $inserts = $stationstat['inserts'];
    
    $statsselect->execute();
    if ($statsselect->num_rows() == 0) // new stats
    {
        $statsselect->fetch();
        $statsselect->free_result();
        echo 'Station ', $stationid, ': ', $searches, ' searches ', $inserts, ' inserts', PHP_EOL;
        $statsinsert->execute();
        if ($statsinsert->error != '')
        {
            echo 'Error inserting: ', $statsinsert->error, PHP_EOL;
        }
    }
    else
    {
        $statsselect->free_result();
        $statselect->close();
        echo 'Station ', $stationid, ': ', $searches, ' ( + ', $prevsearches, ') searches ', $inserts, ' ( + ',$previnserts, ') inserts', PHP_EOL;
        $searches += $prevsearches;
        $inserts += $previnserts;
        $statsupdate->execute();
        if ($statsupdate->error != '')
        {
            echo 'Error updating: ', $statsupdate->error, PHP_EOL;
        }
    }
}

file_put_contents(LOGS_DIR . 'processqueue.log', ob_get_contents());
ob_end_flush();

?>
