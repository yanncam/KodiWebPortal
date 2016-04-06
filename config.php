<?php

##########################################################################################
# NAS parameters (Synology like)
##########################################################################################
define("NAX_LOCAL_PATH", "/volume1/MEDIATHEQUE/");
define("NAX_LOGS_PATH", "logs");
define("NAX_SAMBA_PATH", "smb://NAX/MEDIATHEQUE/");

##########################################################################################
# LDAP directory parameters (user authentication and group authoization)
##########################################################################################
define("LDAP_AUTH_HOST", "localhost");
define("LDAP_USERS_DN", "cn=users,dc=x,dc=local");
define("LDAP_GROUPS_DN", "cn=groups,dc=x,dc=local");
define("LDAP_GROUP_XBMC_FILTER", "(cn=xbmc)");
define("LDAP_GROUP_ATTRIBUTE", "memberuid");

##########################################################################################
# MySQL XBMC/KODI user authentication
##########################################################################################
define("SQL_XBMC_HOST", "localhost");
define("SQL_XBMC_PORT", 3306);
define("SQL_XBMC_USER", "root");
define("SQL_XBMC_PASS", "");

##########################################################################################
# Default configuration
# - DEFAULT_ENTRIES_DISPLAY : number of entries to display on first loading
# - ENABLE_DOWNLOAD : allow user to download your content (display the download icon)
# - ENABLE_AUTHENTICATION : enable authentication mecanism (login / password), or not.
##########################################################################################
define("DEFAULT_ENTRIES_DISPLAY", 16);
define("ENABLE_DOWNLOAD", true);
define("ENABLE_AUTHENTICATION", false);

##########################################################################################
# XBMC / Kodi tables definition
# Do not edit !
##########################################################################################
define("NAX_MOVIE_VIEW", "movie_view");
define("NAX_ACTORS_TABLE", "actor");
define("NAX_ACTORLINKMOVIE_TABLE", "actor_link");
define("NAX_TVSHOW_VIEW","tvshow_view");
define("NAX_TVSHOWSEASON_VIEW","season_view");
define("NAX_TVSHOWEPISODE_VIEW","episode_view");

$db = mysql_connect(SQL_XBMC_HOST.":".SQL_XBMC_PORT, SQL_XBMC_USER, SQL_XBMC_PASS);
if(!$db){
	echo "Fail to connect...";
}

// Connect to the latest XBMC/KODI database presents in the backend, database name is get automaticaly.
$req = mysql_query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'xbmc_video%' ORDER BY SCHEMA_NAME DESC LIMIT 0,1;");
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
}

?>