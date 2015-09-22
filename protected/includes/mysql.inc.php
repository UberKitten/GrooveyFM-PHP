<?php

$GFM_MySQLi = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);

if (mysqli_connect_errno())
{
    die('Connection failed: ' . mysqli_connect_error());
}

?>