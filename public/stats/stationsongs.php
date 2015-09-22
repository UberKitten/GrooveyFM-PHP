<?php
include_once 'header.inc.php';
require_once INCLUDES_DIR . 'mysql.inc.php';
?>

<style type="text/css">
table {
	border-width: 1px;
	border-spacing: 2px;
	border-style: outset;
	border-color: black;
	border-collapse: separate;
	background-color: white;
}
table th {
	border-width: 1px;
	padding: 3px;
	border-style: inset;
	border-color: black;
	background-color: white;
	-moz-border-radius: ;
}
table td {
	border-width: 1px;
	padding: 3px;
	border-style: inset;
	border-color: black;
	background-color: white;
	-moz-border-radius: ;
}
</style>

<h2>Station Songs</h2>

<?php
$stations = $GFM_MySQLi->query('SELECT id, callsign FROM `stations`');
while($row = $stations->fetch_object())
{
 echo '<a href="stationsongs.php?id=', $row->id, '">', $row->callsign, '</a> ';
}
$stations->free();
?>
<br />
<br />

<?php
if (isset($_GET['id']))
{
 echo '<table align="center">', PHP_EOL;
 echo '<tr><th>Song</th><th>Artist</th></tr>', PHP_EOL;
 $songs = $GFM_MySQLi->prepare('SELECT title, artist FROM playlists WHERE station = ? ORDER BY id DESC');
 $songs->bind_param('i', $_GET['id']);
 $songs->bind_result($title, $artist);
 $songs->execute();

 while($songs->fetch())
 {
  echo '<tr><td>', $title, '</td><td>', $artist, '</td></tr>', PHP_EOL;
 }

 echo '</table>', PHP_EOL;
}
?>

<?php include_once 'footer.inc.php'; ?>
