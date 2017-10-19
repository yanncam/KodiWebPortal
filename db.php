<?php
if(!defined("IS_INCLUDED"))	header("Location: index.php");

if(!isset($_SESSION["database"])){
	// Define dynamically the current xbmc_video database's name
	if(SQL_XBMC_DBNAME === false){
		try {
			if(SQL_XBMC_SOCK == "")
				$db = new PDO("mysql:host=" . SQL_XBMC_HOST . ";dbname=information_schema;port:" . SQL_XBMC_PORT, SQL_XBMC_USER, SQL_XBMC_PASS);
			else
				$db = new PDO("mysql:unix_socket=" . SQL_XBMC_SOCK . ";dbname=information_schema", SQL_XBMC_USER, SQL_XBMC_PASS);
			$stmt = $db->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'xbmc_video%' ORDER BY SUBSTR(SCHEMA_NAME,11,5)+0 DESC LIMIT 0,1;");
			$database = $stmt->fetch();
			if(empty(trim($database["SCHEMA_NAME"]))){
				echo "Error, no xbmc_video% database found...";
				exit;
			} else {
				$_SESSION["database"] = trim($database["SCHEMA_NAME"]);
			}
		}
		catch(PDOException $e){
			echo $e->getMessage();
		}
	} else {
		$_SESSION["database"] = SQL_XBMC_DBNAME;
	}
}

// Connect to xbmc_video% database
$db = null;
try {
	if(SQL_XBMC_SOCK == "")
		$db = new PDO("mysql:host=" . SQL_XBMC_HOST . ";dbname=" . $_SESSION["database"] . ";port:" . SQL_XBMC_PORT, SQL_XBMC_USER, SQL_XBMC_PASS);
	else
		$db = new PDO("mysql:unix_socket=" . SQL_XBMC_SOCK . ";dbname=" . $_SESSION["database"], SQL_XBMC_USER, SQL_XBMC_PASS);
	$db->query("SET NAMES 'utf8';");
}
catch(PDOException $e){
	echo $e->getMessage();
}
?>