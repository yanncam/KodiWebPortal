<?php
// @see http://sbdomo.esy.es/2014/05/my-readings-configuration-dsm5/
// vi /etc/httpd/conf/extra/mod_xsendfile.conf-user # Synology DSM5
// vi /volume1/@appstore/WebStation/usr/local/etc/httpd/conf/extra/mod_xsendfile.conf-user # Synology DSM6
// XSendFilePath /volume1
// Reboot Apache through DSM (manage package => WebStation)

session_start();
require_once("./config.php");
require_once("./db.php");
require_once("./functions.php");
	
if(ENABLE_DOWNLOAD){
	if(ENABLE_AUTHENTICATION){ // Check authentication and authorization
		if(!isAuthenticated()){
			header("Location: login.php");
			exit;
		}
	}
	
	if(isset($_GET["id"]) && !empty($_GET["id"]) && is_numeric($_GET["id"]) && intval($_GET["id"])>0 && isset($_GET["type"]) && !empty($_GET["type"])){
		$id = intval($_GET["id"]);
		switch(strval($_GET["type"])){
			case "tvshow":
				$sql 		= "SELECT strPath,strFileName FROM " . NAX_TVSHOWEPISODE_VIEW . " WHERE idEpisode=:id LIMIT 0,1;";
				$localPath 	= NAX_TVSHOW_LOCAL_PATH;
				$remotePath = NAX_TVSHOW_REMOTE_PATH;
				break;
			case "movie":
				$sql 		= "SELECT strPath,strFileName FROM " . NAX_MOVIE_VIEW . " WHERE idMovie=:id LIMIT 0,1;";
				$localPath 	= NAX_MOVIES_LOCAL_PATH;
				$remotePath = NAX_MOVIES_REMOTE_PATH;
				break;
		}
		
		$stmt = $db->prepare($sql);
		$stmt->bindValue('id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$data = $stmt->fetch();
		if($data){
			$path = str_ireplace($remotePath, $localPath, $data["strPath"]) . "/" . $data["strFileName"];
			if(ENABLE_AUTHENTICATION)
				logDownload($_SESSION['user'], $path);
			header("X-Sendfile: $path");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"" . $data["strFileName"] . "\"");
		}
	} else
		header("Location: index.php");
} else
	header("Location: index.php");
?>