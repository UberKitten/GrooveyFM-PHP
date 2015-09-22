<?php
require_once INCLUDES_DIR . 'mysql.inc.php';

function dataFromSQL($query)
{
	$data = '[';
	global $GFM_MySQLi;

	$result = $GFM_MySQLi->query($query);
	while($row = $result->fetch_assoc())
	{
		$data .= '[';
		foreach($row as $field)
		{
			if (is_null($field))
			{
				$data .= "'',";
				continue;
			}
			if (is_numeric($field))
			{
				$data .= $field . ',';
				continue;
			}
			if (is_string($field))
			{	
				$data .= "'" . str_replace("'", "", $field) . "',";
			}
		}
		$data = rtrim($data, ',') . '],';
	}
	$data = rtrim($data, ',') . ']';
	$result->free();
	return $data;
}
