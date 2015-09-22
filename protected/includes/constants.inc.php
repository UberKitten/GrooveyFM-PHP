<?php

define ('ROOT_DIR', '/f5/grooveyfm/public/');
define ('INCLUDES_DIR', '/f5/grooveyfm/protected/includes/');
define ('GSAPI_DIR', INCLUDES_DIR . 'GroovesharkAPI/');
define ('LOGS_DIR', ROOT_DIR . 'logs/');

define ('ABSOLUTE_URL', 'http://grooveyfm.com/');
define ('CRON_PASSWORD', '');

define ('MYSQL_HOST', 'db');
define ('MYSQL_USER', 'grooveyfm');
define ('MYSQL_PASSWORD', '');
define ('MYSQL_DATABASE', 'grooveyfm');

// Limits are per run, not per hour/etc.
define ('INSERT_LIMIT', 10);
define ('SEARCH_LIMIT', 8);
define ('PLAYLIST_LIMIT', 200);

// Limits per hour
define ('SEARCH_HOURLY_LIMIT', 32);

// Grooveshark API
define('GS_HOST', 'api.grooveshark.com');
define('GS_ENDPOINT', 'ws3.php');
define('GS_WS_KEY', 'oldkey');
define('GS_SECRET', 'rip grooveshark');

define('GS_USERNAME', '');
define('GS_PASSWORD', '');

?>
