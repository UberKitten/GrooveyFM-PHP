<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On'); 
require_once INCLUDES_DIR . 'mysql.inc.php';

if (array_key_exists('file', $_GET))
{
	$station = $GFM_MySQLi->prepare("SELECT playlisturl FROM 
	stations WHERE LOWER(callsign) = LOWER(?) AND playlisturl IS NOT 
NULL LIMIT 1");    
	$station->bind_param('s', strtolower($_GET['file']));
	$station->bind_result($playlisturl);
	$station->execute();
	$station->fetch();
}
    
if (!isset($playlisturl))
{
    $playlisturl = 'http://ub3rk1tten.com/grooveyfm';
}

header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $playlisturl);

?>
