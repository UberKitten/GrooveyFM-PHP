<?php
include_once 'header.inc.php';
require_once 'data.inc.php';
?>

<script type="text/javascript">
    google.load('visualization', '1.0', {'packages':['corechart']});
    
    function drawCharts()
    {
        
        var onedaysumdata = new google.visualization.DataTable();
        onedaysumdata.addColumn('string', 'Call Sign');
        onedaysumdata.addColumn('number', 'Searches');
        onedaysumdata.addRows(
          <?php
            $today = date('Y-m-d');

            echo dataFromSQL( <<<SQL
SELECT stations.callsign, SUM(stats.searches)
FROM stats
INNER JOIN stations
ON stations.id = stats.station
WHERE stats.date = '{$today}'
GROUP BY stats.station
SQL
                            );
          ?>
        );
        
        onedaysum = new google.visualization.PieChart(document.getElementById('OneDaySum'));
        onedaysum.draw(onedaysumdata, {'title':'Searches by station for today', 'width':800, 'height':500});


        var currenthoursumdata = new google.visualization.DataTable();
        currenthoursumdata.addColumn('string', 'Call Sign');
        currenthoursumdata.addColumn('number', 'Searches');
        currenthoursumdata.addRows(
          <?php
            $currenthour = date('h');

            echo dataFromSQL( <<<SQL
SELECT stations.callsign, SUM(stats.searches)
FROM stats
INNER JOIN stations
ON stations.id = stats.station
WHERE stats.hour = '{$currenthour}' AND stats.date = '{$today}'
GROUP BY stats.station
SQL
                            );
          ?>
        );
        
        currenthoursum = new google.visualization.PieChart(document.getElementById('CurrentHourSum'));
        currenthoursum.draw(currenthoursumdata, {'title':'Searches by station for the current hour (<?php echo $currenthour; ?>)', 'width':800, 'height':500});

        var pasthoursumdata = new google.visualization.DataTable();
        pasthoursumdata.addColumn('string', 'Call Sign');
        pasthoursumdata.addColumn('number', 'Searches');
        pasthoursumdata.addRows(
          <?php
            $pasthour = date('h', time() - 60*60);

            echo dataFromSQL( <<<SQL
SELECT stations.callsign, SUM(stats.searches)
FROM stats
INNER JOIN stations
ON stations.id = stats.station
WHERE stats.hour = '{$pasthour}' AND stats.date = '{$today}'
GROUP BY stats.station
SQL
                            );
          ?>
        );
        
        pasthoursum = new google.visualization.PieChart(document.getElementById('PastHourSum'));
        pasthoursum.draw(pasthoursumdata, {'title':'Searches by station for the past hour (<?php echo $pasthour; ?>)', 'width':800, 'height':500});

        var searchesbyhourdata = new google.visualization.DataTable();
        searchesbyhourdata.addColumns('string', 'Call Sign');
        searchesbyhourdata.addColumn('number', 'Searches');
        searchesbyhourdata.addRows(
	<?php
            echo dataFromSQL( <<<SQL
SELECT stations.callsign, SUM(stats.searches)
FROM stats
INNER JOIN stations
ON stations.id = stats.station
WHERE stats.hour = '{$pasthour}' AND stats.date = '{$today}'
GROUP BY stats.station
SQL
                            );
          ?>
        );
        
        searchesbyhour = new google.visualization.PieChart(document.getElementById('SearcesByHour'));
        searchesbyhour.draw(searchesbyhourdata, {'title':'Searches by station for the past hour (<?php echo $pasthour; ?>)', 'width':800, 'height':500});
    }
    google.setOnLoadCallback(drawCharts);
</script>

<div id="OneDaySum"></div>
<div id="CurrentHourSum"></div>
<div id="PastHourSum"></div>
<div id="SearchesByHour"></div>

<?php include_once 'footer.inc.php'; ?>
