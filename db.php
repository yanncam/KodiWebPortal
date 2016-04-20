<?php
if(!defined("IS_INCLUDED"))	header("Location: index.php");

//$db = mysql_connect(SQL_XBMC_HOST.":".SQL_XBMC_PORT, SQL_XBMC_USER, SQL_XBMC_PASS);
$db = null;
try {
	$db = new PDO("mysql:host=" . SQL_XBMC_HOST . ";dbname=xbmc_video99", SQL_XBMC_USER, SQL_XBMC_PASS);
	$db->query("SET NAMES 'utf8';");
}
catch(PDOException $e){
	echo $e->getMessage();
}

/*if(!$db){
	echo "Fail to connect...";
}*/

// Connect to the latest XBMC/KODI database presents in the backend, database name is get automaticaly.
/*$req = mysql_query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'xbmc_video%' ORDER BY SCHEMA_NAME DESC LIMIT 0,1;");
mysql_query("SET NAMES 'utf8'");
$dbSelected = false;
while($database = mysql_fetch_array($req)){
	if(substr($database[0], 0, 10) == "xbmc_video"){
		mysql_select_db($database[0],$db);
		//mysql_select_db("xbmc_video99",$db);
		$dbSelected = true;
	}
}
if(!$dbSelected){
	echo "Database unavailable";
	exit;
}*/

?>