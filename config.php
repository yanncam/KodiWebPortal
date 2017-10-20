<?php
###############################################################################################################
# Default configuration
# - DEFAULT_ENTRIES_DISPLAY : number of entries to display on each locading (first loading and then at each 
# scroll down).
# - ENABLE_DOWNLOAD : allow user to download your content (display the download icon)
###############################################################################################################
define("DEFAULT_ENTRIES_DISPLAY", 16);
define("ENABLE_DOWNLOAD", true);

###############################################################################################################
# NAS parameters (Synology like)
# KodiWebPortal needs to be installed on the same server that host your media content (or media content can be
# acceded through filesystem with mount share). These "local path" for movies and/or tvshow are defined below.
# Usually, media file path are referenced with a different path in Kodi SQL database, like "smb://".
# Remote path defined below represent this kind of path in SQL Kodi DB.
# KodiWebPortal will replace each "NAX_XXX_REMOTE_PATH" part or media filepath extracted from Kodi DB with the
# "NAX_XXX_LOCAL_PATH" corresponding to allow downloading.
###############################################################################################################
define("NAX_LOGS_PATH", "logs");							// Path to log activity
define("NAX_MOVIES_LOCAL_PATH", "/volume1/MEDIATHEQUE/");	// Local path where are movies
define("NAX_MOVIES_REMOTE_PATH", "smb://NAX/MEDIATHEQUE/");	// Location of movies in Kodi DB
define("NAX_TVSHOW_LOCAL_PATH", "/volume1/MEDIATHEQUE/");	// Local path where are TVshow
define("NAX_TVSHOW_REMOTE_PATH", "smb://NAX/MEDIATHEQUE/");	// Location of TVshow in Kodi DB

###############################################################################################################
# MySQL XBMC/KODI user authentication
# Configure here how KodiWebPortal can connect to the MySQL server who hosts the Kodi dabatase (xbmc_videoXX).
# SQL_XBMC_DBNAME can be "xbmc_videoXX" as a string, of false to autoselect dbname with latest version.
# The local absolute path to SQL socket can be defined in SQL_XBMC_SOCK:
# 	- /run/mysqld/mysqld.sock 	: default MariaDB5 on Synology
# 	- /run/mysqld/mysqld10.sock : MariaDB10 custom socket on Synology
###############################################################################################################
define("SQL_XBMC_HOST", "localhost");
define("SQL_XBMC_PORT", 3306);
define("SQL_XBMC_SOCK", "");
//define("SQL_XBMC_SOCK", "/run/mysqld/mysqld10.sock");
define("SQL_XBMC_USER", "root");
define("SQL_XBMC_PASS", "");
//define("SQL_XBMC_DBNAME", "xbmc_video107");
define("SQL_XBMC_DBNAME", false); // set to false to autoselect latest XBMC/KODI database

###############################################################################################################
# Internal authentication mecanism
# If you want to allow or disallow KodiWebPortal access, you can use internal authentication with user's account
# defined below or use LDAP authentication.
# If ENABLE_INTERNAL_AUTHENTICATION is true and ENABLE_LDAP_AUTHENTICATION too, then INTERNAL authentication is
# performed before LDAP authentication.
# If ENABLE_INTERNAL_AUTHENTICATION is false and ENABLE_LDAP_AUTHENTICATION too, then visitor can access to
# KodiWebPortal without login.
# Internal password hash are made with bcrypt() like (PHP >= 5.5) :
# 	echo password_hash("MyU53rP4s5W0rd", PASSWORD_DEFAULT);
###############################################################################################################
define("ENABLE_INTERNAL_AUTHENTICATION", true);
$USERS = array(
	"kodi" 		=> "$2y$10$471cELEUsyQJqaDyJBuXzOoBCEWfZwvMBtGsGLOQPwNH8DLInzJlq",		// K0d1P4s5W0rD		- Define first internal user
	"xbmc" 		=> "$2y$10$3FaeYaRCEYUVm6rM2d3ixe3YaEcIvdp8/dmkU8y48X5rQILyoceo2",		// X8mCP4s5W0rD 	- Define second internal user
//	"myUser"	=> "$2y$10$U7arjRoGuVd4DPueJjAnSeqDKGgTs3bXmViQlGlGxKNBgLMS1uAQq",		// MyU53rP4s5W0rd 	- Define another internal user
);

###############################################################################################################
# LDAP directory parameters (user authentication and group authoization)
# If ENABLE_INTERNAL_AUTHENTICATION is true and ENABLE_LDAP_AUTHENTICATION too, then INTERNAL authentication is
# performed before LDAP authentication.
# If ENABLE_INTERNAL_AUTHENTICATION is false and ENABLE_LDAP_AUTHENTICATION too, then visitor can access to
# KodiWebPortal without login.
###############################################################################################################
define("ENABLE_LDAP_AUTHENTICATION", false);
define("LDAP_AUTH_HOST", "localhost");
define("LDAP_USERS_DN", "cn=users,dc=x,dc=local");
define("LDAP_GROUPS_DN", "cn=groups,dc=x,dc=local");
define("LDAP_GROUP_XBMC_FILTER", "(cn=xbmc)");
define("LDAP_USERID_ATTRIBUTE", "uid");
define("LDAP_GROUP_ATTRIBUTE", "memberuid");

###############################################################################################################
# Watched / Unwatched / Started status parameters
# If WATCHED_STATUS_FOR_ALL is true, a status "WATCHED" (a tick logo), "UNWATCHED" (a star logo) or "STARTED" 
# (a pause logo) (not watched entirely) is displayed as indicator on all media (movies, tvshow, season or 
# episode).
# To display watched-status only for few user, put WATCHED_STATUS_FOR_ALL to false and fullfill the 
# WATCH_STATUS_FOR_USERS with all usernames allowed to see status.
###############################################################################################################
define("WATCHED_STATUS_FOR_ALL", false);
$WATCH_STATUS_FOR_USERS = ["kodi", "mylogin"];

###############################################################################################################
# XBMC / Kodi tables definition
# Do not edit !
###############################################################################################################
define("NAX_MOVIE_VIEW", "movie_view");
define("NAX_STREAMDETAILS_TABLE", "streamdetails");
define("NAX_ACTORS_TABLE", "actor");
define("NAX_ACTORLINKMOVIE_TABLE", "actor_link");
define("NAX_TVSHOW_VIEW","tvshow_view");
define("NAX_TVSHOWSEASON_VIEW","season_view");
define("NAX_TVSHOWEPISODE_VIEW","episode_view");
define("ENABLE_AUTHENTICATION", (ENABLE_INTERNAL_AUTHENTICATION || ENABLE_LDAP_AUTHENTICATION));
define("KODI_WEB_PORTAL_VERSION", "1.1.1");
define("IS_INCLUDED", true);

$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
switch($lang){
	case "fr":
        require_once("./i18n/lang_fr.php");
        break;
    case "en":
        require_once("./i18n/lang_en.php");
        break;
    case "es":
        require_once("./i18n/lang_es.php");
        break;
	case "de":
		require_once("./i18n/lang_de.php");
		break;
    default:
        require_once("./i18n/lang_en.php");
        break;
}

?>