<?php

header('Content-Type: text/plain');
require_once INCLUDES_DIR . 'cron_security.inc.php';
ob_start();

require_once INCLUDES_DIR . 'mysql.inc.php';

if (isset($argv))
{
    $stationid = $argv[1];
    $callsign = $argv[2];
    $url = $argv[3];
    $type = $argv[4];
}
else
{
    $stationid = urldecode($_REQUEST['stationid']);
    $callsign = urldecode($_REQUEST['callsign']);
    $url = urldecode($_REQUEST['url']);
    $type = urldecode($_REQUEST['type']);
}

echo 'Parsing of source ', $url, ' starting at ', date('r'), PHP_EOL;

$insert = <<<INSERT
INSERT INTO searchqueue (station, title, artist)
    SELECT ?, UPPER(?), UPPER(?) FROM dual
    WHERE NOT EXISTS(
        SELECT searchqueue.id FROM searchqueue
        WHERE title = UPPER(?)
        AND artist = UPPER(?) LIMIT 1
    ) AND NOT EXISTS(
        SELECT noresults.id FROM noresults
        WHERE title = UPPER(?)
        AND artist = UPPER(?) LIMIT 1
    )
INSERT;

$insert = $GFM_MySQLi->prepare($insert);

$title = '';
$artist = '';
$insert->bind_param('issssss', $stationid, $title, $artist, $title, $artist, $title, $artist);

$toadd = array();

switch ($type)
{
    case 'mediabase':
            require_once INCLUDES_DIR . 'simple_html_dom.inc.php';
            $html = file_get_html($url);
            $counter = 0;
            foreach($html->find('table tr') as $tr)
            {
                if ($counter >= INSERT_LIMIT)
                {
                    break;
                }
                $td = $tr->children();
                if (count($td) < 6 )
                {
                    continue;
                }
                $artist = $tr->children(2)->children(0)->innertext;
                $title = $tr->children(4)->children(0)->innertext;
                if ($artist == 'Artist' || $title == 'Title' ||
                    $artist == '' || $title == '')
                {
                    continue;
                }
                $counter++;
                $toadd[] = array('title' => $title, 'artist' => $artist);
            }
            $html->clear();
            unset($html);
        break;
    
    case 'kvrkpowerfm': // daily
            require_once INCLUDES_DIR . 'simple_html_dom.inc.php';
            $html = file_get_html($url);
            $htmltext = $html->plaintext;
            unset($html);
            
            $htmltext = explode("\n", $htmltext);
            $htmltext = array_filter($htmltext, 'trim');
            $songs = array();
            foreach($htmltext as $line)
            {
                $temp = explode("--", $line);
                if (count($temp) < 3)
                {
                    continue;
                }
                $temp = array_filter($temp, 'trim');
                $songs[] = array('artist' => $temp[1], 'title' => $temp[2]);
            }
            
            $keys = array_rand($songs, 50);
            foreach ($keys as $key)
            {
                $toadd[] = $songs[$key];
            }
        break;
    
    case 'wvum':
            $json = json_decode(file_get_contents($url));
            $counter = 0;
            foreach($json as $tweet)
            {
                if ($counter > INSERT_LIMIT)
                {
                    break;
                }
                $text = $tweet->text;
                if (mb_stripos($text, 'wvum') !== false)
                {
                    continue;
                }
                $text = trim(mb_substr($text, 4)); // #NP:
                $text = trim(mb_substr($text, mb_stripos($text, ' '))); // URL
                $text = preg_replace('/@[A-Za-z0-9_]+/', '', $text); // Twitter handles
                
                $text = explode('-', $text);
                
                if (count($text) < 3)
                {
                    continue;
                }
                $counter++;
                $toadd[] = array('artist' => $text[0], 'title' => $text[2]);
            }
        break;
    
    case 'kpfkts':  // daily      
            $html = file_get_html($url);
            foreach($html->find('table tr') as $tr)
            {
                $td = $tr->children();
                if (count($td) < 2 )
                {
                    continue;
                }
                
                $artist = $td[0]->innertext;
                $title = $td[1]->innertext;
                
                if ($artist == '' || $title == '')
                {
                    continue;
                }
                $toadd[] = array('title' => $title, 'artist' => $artist);
            }
            $html->clear();
            unset($html);
        break;
    
    case 'rss':
            require_once INCLUDES_DIR . 'coreylib.inc.php';
            $api = new clApi($url);
            $feed = $api->parse();
            $counter = 0;
            foreach($feed->get('item') as $entry)
            {
                if ($counter > INSERT_LIMIT)
                {
                    break;
                }
                $feedt = $entry->get('title');
                
                $bypos = strpos($feedt, ' by ');
                
                $title = substr($feedt, 0, $bypos);
                $artist = substr($feedt, $bypos + 4, strpos($feedt, ' from ') - ($bypos + 4));
                $counter++;
                $toadd[] = array('title' => $title, 'artist' => $artist);
            }
        break;
}

$toadd = array_reverse($toadd);
foreach($toadd as $song)
{
    $title = $song['title'];
    
    // Remove everything after f/somename
    $fslash = stripos($title, 'f/');
    if ($fslash !== FALSE)
    {
        $title = substr($title, 0, $fslash);
    }
    
    $title = trim(str_replace('...', '', $title));
    
    $artist = $song['artist'];
    
    // Remove everything after f/somename
    $fslash = stripos($artist, 'f/');
    if ($fslash !== FALSE)
    {
        $artist = substr($artist, 0, $fslash);
    }
    
    $artist = trim(str_replace('...', '', $artist));
    
    $insert->execute();
    echo 'Inserted ', $title, ' by ', $artist, PHP_EOL;
}

file_put_contents(LOGS_DIR . 'source.' . $callsign . '.log', ob_get_contents());
ob_end_flush();

?>
