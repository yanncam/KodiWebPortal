<?php
if(!defined("IS_INCLUDED"))	header("Location: index.php");

/**
  * Check if current user is authenticated or authentication is not required
  * return true if current user is already authenticated
  * return true is authentication mecanisms are all disabled (like automaticaly authenticated)
  */
function isAuthenticated(){
	return (isset($_SESSION['user'], $_SESSION['pass']) || !ENABLE_AUTHENTICATION);
}

/**
  * Chain all enabled authentication mecanisms in right order.
  * INTERNAL first, LDAP second.
  * If a user exists with the right password in one of authentication mecanisms
  * then the $_SESSION is setted.
  * return true if authentication success, else false.
  */
function checkAuthentication($user, $password){
	$return = false;
	if(!$return && ENABLE_INTERNAL_AUTHENTICATION)					// internal authentication
		$return = checkInternalAuthentication($user, $password);
	if(!$return && ENABLE_LDAP_AUTHENTICATION)						// LDAP authentication
		$return = checkLDAPAuthentication($user, $password);
	if($return){
		$_SESSION['user'] = trim(strval($user));
		$_SESSION['pass'] = trim(strval($password));
	}
	return $return;
}

/**
  * Check login and password against internal users definition.
  * return true or false if authentication succeed.
  * PHP juggling attack protected
  * Password haching method : bcrypt()
  * 	echo password_hash("MyU53rP4s5W0rd", PASSWORD_DEFAULT);
  */
function checkInternalAuthentication($user, $password){
	global $USERS;
	return (is_array($USERS) && !empty($USERS) && array_key_exists(strval($user), $USERS) && password_verify($password, $USERS[$user]));
}

/**
  * Check login and password against LDAP directory
  * Current user need to be memberOf the LDAP_GROUP_XBMC_FILTER.
  * return true or false if authentication succeed.
  */
function checkLDAPAuthentication($user, $password){
	if(preg_match("/^[a-zA-Z0-9\s\_\s\-]+$/",$user)){
		$queryUser = LDAP_USERID_ATTRIBUTE."=$user,".LDAP_USERS_DN;
		$ldap 		= ldap_connect(LDAP_AUTH_HOST);
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		if(!$ldap){
			//echo "LDAP connection error";
			return false;
		}
		if(!@ldap_bind($ldap, $queryUser, $password)){
			//echo "LDAP bind error";
			return false;
		}
		$results = ldap_search($ldap, LDAP_GROUPS_DN, LDAP_GROUP_XBMC_FILTER, array(LDAP_GROUP_ATTRIBUTE));
		$entries = ldap_get_entries($ldap, $results);
		// No information found, bad user
		if($entries['count'] == 0)
			return false;
		return in_array($user, $entries[0][LDAP_GROUP_ATTRIBUTE]);
	} else
		return false;
}

function defineSecurityHeaders(){
	header("X-Frame-Options: sameorigin");
	header("X-XSS-Protection: 1; mode=block");
	header("X-Content-Type-Options: nosniff");
	header("Referrer-Policy: origin-when-cross-origin");
	$contentSecurityPolicy = "default-src 'self'; child-src https://*.youtube.com; img-src 'self' http: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:;";
	header("Content-Security-Policy: " . $contentSecurityPolicy);
	header("X-Webkit-CSP: " . $contentSecurityPolicy);
	header("X-Content-Security-Policy: " . $contentSecurityPolicy);
}

function sessionStartSecurely(){
	@ini_set("session.hash_function", sha512);
	session_set_cookie_params(86400, '/', null, ENFORCE_HTTPS_SECURITY, true);
	if(ENFORCE_HTTPS_SECURITY)
		session_name('__Host-KODIWEBPORTAL'); // @see https://scotthelme.co.uk/tough-cookies/
	else
		session_name('KODIWEBPORTAL');
	session_start();
}

/**
  * Send authentication log to a syslog server
*/

function getClientIP(){
	if (isset ($_SERVER['HTTP_X_FORWARDED_FOR'])){
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		return $_SERVER['REMOTE_ADDR'];
	}
}

function authSyslog($message){
	$ip = preg_replace("/[^0-9.]/",'',getClientIP());
	$sysUser = preg_replace("/[^A-Za-z0-9]/",'',$_POST['user']);
	if(SYSLOG_AUTHD_ENABLE){
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$msg = "kodi_authd ".$message." : {$ip} {$sysUser}";
		$len = strlen($msg);
		socket_sendto($sock, $msg, $len, 0, SYSLOG_AUTHD_HOST, SYSLOG_AUTHD_PORT);
		socket_close($sock);
		exit;
	}
}		

/**
  * Convert XML from KODI/XBMC database to array of URL.
  */
function picturesXMLtoURLArray($picturesXML){
	$return = array("nopicture");
	if(!empty(trim($picturesXML))){
		$pictures = array();
		$pattern = "/((http|https|ftp)\:\/\/)?[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?\/([a-zA-Z0-9\-\.\?_&amp;%\$#\=~\/\'\,])*/";
		preg_match_all($pattern, $picturesXML, $pictures);
		$return = ((count($pictures) > 0) && (isset($pictures[1])) && (count($pictures[1]) > 0)) ? $pictures[0] : array("nopicture");
	}
	return $return;
}

/**
  * Extract the Youtube ID from an URL stored in KODI/XBMC database.
  */
function extractYoutubeId($url){
	$return = array();
	if(!empty(trim($url))){
		$urls = array();
		$pattern = "/(v=|videoid=)([a-zA-Z0-9_\-]{10,})&?/";
		preg_match_all($pattern, $url, $urls);
		$return = ((count($urls) > 0) && !empty($urls[count($urls)-1])) ? $urls[count($urls)-1][count(count($urls)-1)-1] : array();
	}
	return $return;
}

/**
  * Get an array of all Year of movies.
  */
function getAllYears(){
	global $db;
	$return = array();
	$stmt = $db->query("SELECT DISTINCT c07,premiered FROM `" . NAX_MOVIE_VIEW . "` ORDER BY premiered DESC;");
	while($data = $stmt->fetch()){
		$year = strtok($data["premiered"], "-");
		if(intval($year) > 0)
			$return[] = intval($year);
		if(intval($data["c07"]) > 0)
			$return[] = intval($data["c07"]);
	}
	return array_unique($return);
}

/**
  * Get an array of all Studios of TVShow.
  */
function getAllStudios(){
	global $db;
	$return = array();
	$stmt = $db->query("SELECT DISTINCT c14 FROM `" . NAX_TVSHOW_VIEW . "` ORDER BY c14 DESC;");
	while($data = $stmt->fetch()){
		if(!empty(trim(strval($data["c14"]))))
			$return[] = trim(strval($data["c14"]));
	}
	return $return;
}

/**
  * Get an array of all Genre of movies or TVshow ($type).
  */
function getAllGenres($type){
	global $db;
	if($type == "tvshow"){
		$column = "c08";
		$table  = NAX_TVSHOW_VIEW;
	} else {
		$column = "c14";
		$table  = NAX_MOVIE_VIEW;
	}
	$return = array();
	$stmt = $db->query("SELECT DISTINCT $column FROM `$table` ORDER BY $column ASC;");
	while($data = $stmt->fetch()){
		$values = explode("/", $data[$column]);
		foreach($values as $value){
			if(!in_array(trim(strval($value)), $return) && !empty(trim(strval($value)))){
				$return[] = trim(strval($value));
			}
		}
	}
	sort($return);
	$return = array_unique($return);
	return $return;
}

/**
  * Get an array of all Nationality of movies.
  */
function getAllNationalities(){
	global $db;
	$return = array();
	$stmt = $db->query("SELECT DISTINCT c21 FROM `" . NAX_MOVIE_VIEW . "` ORDER BY c21 ASC;");
	while($data = $stmt->fetch()){
		$values = explode("/", $data["c21"]);
		foreach($values as $value){
			if(!in_array(trim(strval($value)), $return) && !empty(trim(strval($value)))){
				$return[] = trim(strval($value));
			}
		}
	}
	sort($return);
	$return = array_unique($return);
	return $return;
}

/**
  * Return the string representing the size in right format (Ko, Mo, Go) of
  * a file located at the path passed in argument.
  * For Unix based host, shell_exec() must be available
  */
function showsize($file) {
	$return = "N/A";
	$statBin = "/bin/stat";
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		if (class_exists("COM")) {
			$fsobj = new COM('Scripting.FileSystemObject');
			$f = $fsobj->GetFile(realpath($file));
			$file = $f->Size;
		} else {
			$file = trim(exec("for %F in (\"" . $file . "\") do @echo %~zF"));
		}
    } elseif (PHP_OS == 'Darwin') {
		$file = trim(@shell_exec("$statBin -f %z " . escapeshellarg($file)));
    } elseif ((PHP_OS == 'Linux') || (PHP_OS == 'FreeBSD') || (PHP_OS == 'Unix') || (PHP_OS == 'SunOS')) {
		$file = trim(@shell_exec("$statBin -t " . escapeshellarg($file) . " | awk '{ print $(NF-14) }'"));
    } else {
		$file = filesize($file);
    }
	$file = intval($file);
	if($file <= 0) {
		$return = "N/A";
    } elseif ($file < 1024) {
		$return = $file . ' ' . FILESIZE_UNITY;
    } elseif ($file < 1048576) {
		$return = round($file / 1024, 2) . ' K' . FILESIZE_UNITY;
    } elseif ($file < 1073741824) {
		$return = round($file / 1048576, 2) . ' M' . FILESIZE_UNITY;
    } elseif ($file < 1099511627776) {
		$return = round($file / 1073741824, 2) . ' G' . FILESIZE_UNITY;
    } elseif ($file < 1125899906842624) {
		$return = round($file / 1099511627776, 2) . ' T' . FILESIZE_UNITY;
    } elseif ($file < 1152921504606846976) {
		$return = round($file / 1125899906842624, 2) . ' P' . FILESIZE_UNITY;
    } elseif ($file < 1180591620717411303424) {
		$return = round($file / 1152921504606846976, 2) . ' E' . FILESIZE_UNITY;
    } elseif ($file < 1208925819614629174706176) {
		$return = round($file / 1180591620717411303424, 2) . ' Z' . FILESIZE_UNITY;
    } else {
		$return = round($file / 1208925819614629174706176, 2) . ' Y' . FILESIZE_UNITY;
    }
	return $return;
}

function changeStatusMovie($id, $toStatus){
	global $db, $WATCH_STATUS_FOR_USERS;
	if($toStatus === "toWatched"){
		$playCount = 1;
		$lastPlayed = date("Y-m-d H:i:s", time());
	} else {
		$playCount = NULL;
		$lastPlayed = NULL;
	}
	$sql = "UPDATE 
				" . NAX_MOVIE_VIEW . "
			SET
				" . NAX_MOVIE_VIEW . ".playCount = :playCount,
				" . NAX_MOVIE_VIEW . ".lastPlayed = :lastPlayed
			WHERE
				" . NAX_MOVIE_VIEW . ".idMovie = :id;"; 
	$stmt = $db->prepare($sql);
	$stmt->bindValue('playCount', $playCount, PDO::PARAM_INT);
	$stmt->bindValue('lastPlayed', $lastPlayed, PDO::PARAM_STR);
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
		$stmt->execute();
	}
}

function changeStatusEpisode($id, $toStatus){
	global $db, $WATCH_STATUS_FOR_USERS;
	if($toStatus === "toWatched"){
		$playCount = 1;
		$lastPlayed = date("Y-m-d H:i:s", time());
	} else {
		$playCount = NULL;
		$lastPlayed = NULL;
	}
	$sql = "UPDATE 
				" . NAX_TVSHOWEPISODE_VIEW . "
			SET
				" . NAX_TVSHOWEPISODE_VIEW . ".playCount = :playCount,
				" . NAX_TVSHOWEPISODE_VIEW . ".lastPlayed = :lastPlayed
			WHERE
				" . NAX_TVSHOWEPISODE_VIEW . ".idEpisode = :id;"; 
	$stmt = $db->prepare($sql);
	$stmt->bindValue('playCount', $playCount, PDO::PARAM_INT);
	$stmt->bindValue('lastPlayed', $lastPlayed, PDO::PARAM_STR);
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
		$stmt->execute();
	}
}

/**
  * Return HTML formated result of movies entries to display in the main page.
  * Results depend on the $sql query passed in argument.
  */
function getEntriesMovies($sql){
	global $db, $WATCH_STATUS_FOR_USERS;
	$stmt = $db->query($sql);
	if($stmt->rowCount() <= 0){
		echo "<div class='noMoreResult'>[ " . NORESULT_LABEL . " ]</div>";
	}
	while($data = $stmt->fetch()){
		$thumbs = picturesXMLtoURLArray($data["movieThumbs"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$fanarts = picturesXMLtoURLArray($data["movieFanarts"]);  // loading all thumb
		if(strtolower(substr($fanarts[0], 0, 4)) != "http")
			$fanarts[0] = "http://" . $fanarts[0];
		$year = strtok($data["movieYear"], "-");
		$playedStatus = "unwatched";
		if(intval($data["playCount"]) > 0)
			$playedStatus = "watched";
		$switchStatus = ($playedStatus === "watched") ? "unwatched" : "watched";
	?>
	<div class="entry arrondi" id="<?php echo $data["idMovie"]; ?>">
	  <div class="fanart arrondi"><img class="arrondi" src="<?php echo $fanarts[0]; ?>" onerror="this.src='images/fanart-onerror.png';" style="display:none;" /></div>
	  <div class="title"><?php echo $data["movieTitleFR"] . " ($year)"; ?></div>
	  <img class="thumb arrondi" src="<?php echo $thumbs[0]; ?>" onerror="this.src='images/thumb-onerror.jpg';" style="display:none;" />
	<?php if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){ ?>
			<div id="cornerStatus_<?php echo $data["idMovie"]; ?>_unwatched" style="cursor:pointer;display:<?php echo ($playedStatus == "unwatched") ? "visible" : "none"; ?>"><img class="thumbStatus arrondiTopLeft"src="images/unwatched.png" title="<?php echo STATUS_UNWATCHED_LABEL; ?>" style="display:none;" /></div>
			<div id="cornerStatus_<?php echo $data["idMovie"]; ?>_watched" style="cursor:pointer;display:<?php echo ($playedStatus == "watched") ? "visible" : "none"; ?>"><img class="thumbStatus arrondiTopLeft"src="images/watched.png" title="<?php echo STATUS_WATCHED_LABEL; ?>" style="display:none;" /></div>
	<?php } ?>
	  <div class="synopsis"><?php echo $data["movieSynopsis"]; ?></div>
	  <div class="toolbar">
	<?php 
	if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){ 
	?>
		<a id="linkStatus_<?php echo $data["idMovie"]; ?>_unwatched" onclick="changeStatus('toWatched', <?php echo $data["idMovie"]; ?>);" style="position:absolute;float:left;cursor:pointer;display:<?php echo ($playedStatus == "unwatched") ? "block" : "none"; ?>"><img id="buttonStatus_<?php echo $data["idMovie"]; ?>" src="images/unwatchedButton.png" title="<?php echo SWITCH_STATUS_WATCHED_LABEL; ?>" /></a>
		<a id="linkStatus_<?php echo $data["idMovie"]; ?>_watched" onclick="changeStatus('toUnwatched', <?php echo $data["idMovie"]; ?>);" style="position:absolute;float:left;cursor:pointer;display:<?php echo ($playedStatus == "watched") ? "block" : "none"; ?>"><img id="buttonStatus_<?php echo $data["idMovie"]; ?>" src="images/watchedButton.png" title="<?php echo SWITCH_STATUS_UNWATCHED_LABEL; ?>" /></a>

	<?php }	?>
	<?php
	  $youtubeID = extractYoutubeId($data["movieToutube"]);
	  if(!empty($youtubeID))
		echo "<a onclick=\"displayYoutube('video_" . $data["idMovie"] . "', '" . $youtubeID . "');return false;\" href='https://www.youtube.com/watch?v=" . $youtubeID . "'><img src='images/youtube.png' title='" . YOUTUBE_LABEL . "' /></a>";
	?>
		<a onclick="printDetails('details_<?php echo $data["idMovie"]; ?>', <?php echo $data["idMovie"]; ?>);" style="cursor:pointer;"><img src='images/info.png' title='<?php echo DESCRIPTION_LABEL; ?>' /></a>
	<?php if(ENABLE_DOWNLOAD){ ?>
		<a target="_blank" href="dl.php?type=movie&id=<?php echo $data["idMovie"]; ?>"><img src='images/download.png' title='<?php echo DOWNLOAD_LABEL; ?>' /></a>
	<?php }	?>
	  </div>
		
	</div>
	<div id="details_<?php echo $data["idMovie"]; ?>" class="details arrondi"></div>
	<div id="video_<?php echo $data["idMovie"]; ?>" class="videos arrondi"></div>

	<?php  
	} 
}

/**
  * Return HTML formated result of tvshow entries to display in the main page.
  * Results depend on the $sql query passed in argument.
  */
function getEntriesTvShow($sql){
	global $db, $WATCH_STATUS_FOR_USERS;
	$stmt = $db->query($sql);
	if($stmt->rowCount() <= 0){
		echo "<div class='noMoreResult'>[ " . NORESULT_LABEL . " ]</div>";
	}
	while($data = $stmt->fetch()){
		$thumbs = picturesXMLtoURLArray($data["thumb"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$fanart = $data["fanartURL"].$data["fanartValue"];
		$playedStatus = "unwatched";
		if(intval($data["watchedcount"]) >= intval($data["totalCount"]))
			$playedStatus = "watched";
		
	?>
	<div class="entry arrondi" id="<?php echo $data["idShow"]; ?>" onclick="printDetails('details_<?php echo $data["idShow"]; ?>', <?php echo $data["idShow"]; ?>);">
	  <div class="fanart arrondi">
		<img class="arrondi" src="<?php echo $fanart; ?>" onerror="this.src='images/fanart-onerror.png';" style="display:none;" />
	  </div>
	  <div class="title"><?php echo $data["tvshowTitle"]; ?></div>
	  <img class="thumb arrondi" src="<?php echo $thumbs[0]; ?>" onerror="this.src='images/thumb-onerror.jpg';" style="display:none;" />
	  <?php
		if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
	  ?>
			<img class="thumbStatus arrondiTopLeft"src="images/<?php echo $playedStatus; ?>.png" title="<?php echo (($playedStatus === "watched") ? STATUS_WATCHED_LABEL : STATUS_UNWATCHED_LABEL ); ?>" style="display:none;" /> 
	  <?php
	  }
	  ?>
	  <div class="synopsis"><?php echo $data["tvshowSynopsis"]; ?></div>
	  <div class="toolbar">
		<a style="cursor:pointer;"><img src='images/info.png' title='<?php echo DESCRIPTION_LABEL; ?>' /></a>
	<?php if(ENABLE_DOWNLOAD){ ?>
			<a style="cursor:pointer;"><img src='images/download.png' title='<?php echo DOWNLOAD_LABEL; ?>' /></a>
	<?php }	?>
	  </div>
		
	</div>
	<div id="details_<?php echo $data["idShow"]; ?>" class="details arrondi"></div>
	<div id="video_<?php echo $data["idShow"]; ?>" class="videos arrondi"></div>

	<?php  
	} 
}

/**
  * Return HTML formated result of details about a movie entry to display in the centered div.
  * Movie is identified by $id.
  */
function getDetailsEntryMovie($id){
	global $db, $WATCH_STATUS_FOR_USERS;
	$id = intval($_GET["id"]);
	$sql = "SELECT 
				" . NAX_MOVIE_VIEW . ".c00 AS movieTitleFR,
				" . NAX_MOVIE_VIEW . ".c16 AS movieTitleEN,
				" . NAX_MOVIE_VIEW . ".c01 AS movieSynopsis,
				" . NAX_MOVIE_VIEW . ".c08 AS movieThumbs,
				" . NAX_MOVIE_VIEW . ".c14 AS movieGenre,
				" . NAX_MOVIE_VIEW . ".premiered AS movieYear,
				" . NAX_MOVIE_VIEW . ".c15 AS movieRealisator,
				" . NAX_MOVIE_VIEW . ".c21 AS movieNationality,
				" . NAX_MOVIE_VIEW . ".strPath,
				" . NAX_MOVIE_VIEW . ".strFileName,
				" . NAX_MOVIE_VIEW . ".playCount,
				" . NAX_ACTORS_TABLE . ".name,
				" . NAX_ACTORLINKMOVIE_TABLE . ".role,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".strVideoCodec) 	AS streamStrVideoCodec,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iVideoWidth) 	AS streamIVideoWidth,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iVideoHeight) 	AS streamIVideoHeight,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".strAudioCodec) 	AS streamStrAudioCodec,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iAudioChannels) AS streamIAudioChannels
			FROM 
				" . NAX_ACTORS_TABLE . ", 
				" . NAX_ACTORLINKMOVIE_TABLE . ",
				" . NAX_MOVIE_VIEW . " LEFT JOIN
				" . NAX_STREAMDETAILS_TABLE . "
				ON " . NAX_MOVIE_VIEW . ".idFile=" . NAX_STREAMDETAILS_TABLE . ".idFile
			WHERE 
					" . NAX_MOVIE_VIEW . ".idMovie=:id
				AND	" . NAX_MOVIE_VIEW . ".idMovie=" . NAX_ACTORLINKMOVIE_TABLE . ".media_id 
				AND " . NAX_ACTORLINKMOVIE_TABLE . ".actor_id=" . NAX_ACTORS_TABLE . ".actor_id
			GROUP BY
					" . NAX_STREAMDETAILS_TABLE . ".idFile,
					" . NAX_ACTORS_TABLE . ".name,
					" . NAX_ACTORLINKMOVIE_TABLE . ".role";
	$stmt = $db->prepare($sql);
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$data = $stmt->fetch();
	$titleFR = $data["movieTitleFR"];
	$titleEN = $data["movieTitleEN"];
	$synopsis = $data["movieSynopsis"];
	$mediaInfo = "";
	if($data["streamStrVideoCodec"] != "")
		$mediaInfo .= "[" . $data["streamStrVideoCodec"] . "]";
	if($data["streamIVideoWidth"] != "" && $data["streamIVideoHeight"] != "")
		$mediaInfo .= "[" . $data["streamIVideoWidth"] . "x" . $data["streamIVideoHeight"] . "]";
	if($data["streamStrAudioCodec"] != "")
		$mediaInfo .= "[" . $data["streamStrAudioCodec"] . "]";
	if($data["streamIAudioChannels"] != ""){
		switch($data["streamIAudioChannels"]){
			case "1":
				$mediaInfo .= "[mono]";break;
			case "2":
				$mediaInfo .= "[stereo]";break;
			case "3":
				$mediaInfo .= "[2.1]";break;
			case "4":
				$mediaInfo .= "[3.1]";break;
			case "5":
				$mediaInfo .= "[4.1]";break;
			case "6":
				$mediaInfo .= "[5.1]";break;
			case "7":
				$mediaInfo .= "[6.1]";break;
			case "8":
				$mediaInfo .= "[7.1]";break;
			case "9":
				$mediaInfo .= "[8.1]";break;
			case "10":
				$mediaInfo .= "[9.1]";break;
		}
	}
	$movieStatus = "unwatched";
	if(intval($data["playCount"]) > 0)
		$movieStatus = "watched";
	$thumbs = picturesXMLtoURLArray($data["movieThumbs"]);  // loading all thumb
	if(strtolower(substr($thumbs[0], 0, 4)) != "http")
		$thumbs[0] = "http://" . $thumbs[0];
	$genre = $data["movieGenre"];
	$year = strtok($data["movieYear"], "-");
	$realisator = $data["movieRealisator"];
	$nationality = $data["movieNationality"];
	$path = str_replace("//", "/", (str_ireplace(NAX_MOVIES_REMOTE_PATH, NAX_MOVIES_LOCAL_PATH, $data["strPath"]) . "/" . $data["strFileName"]));
	$size = showsize($path);
	$path = preg_replace("#(?<=:/)[^:@]+(:[^@]+)?(?=@)#","...", $path);
	$actors = $data["name"] . " (" . $data["role"] . ")";
	while($data = $stmt->fetch()){
		$actors .= ", " . $data["name"] . " (" . $data["role"] . ")";
	}
	$echo =  "<div class='details-title'>" . $titleFR . " (" . $titleEN . ")</div>";
	$echo .= "<img class='details-thumb arrondi' src='" . $thumbs[0] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
	if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS)))
		$echo .= "<img class='thumbStatus arrondiTopLeft' src='images/" . $movieStatus . ".png' title='" . (($movieStatus === "watched") ? STATUS_WATCHED_LABEL : STATUS_UNWATCHED_LABEL) ."' /> ";
	$echo .= "<div class='details-details'><b>" . SYNOPSIS_LABEL . " : </b><br />" . $synopsis . "<br /><br />";
	$echo .= "	<b>" . YEAR_LABEL . " : </b><br />" . $year . "<br /><br />";
	$echo .= "	<b>" . GENRE_LABEL . " : </b><br />" . $genre . "<br /><br />";
	$echo .= "	<b>" . REALISATOR_LABEL . " : </b><br />" . $realisator . "<br /><br />";
	$echo .= "	<b>" . NATIONALITY_LABEL . " : </b><br />" . $nationality . "<br /><br />";
	$echo .= "	<b>" . ACTORS_LABEL . " : </b><br />" . $actors . "<br /><br />";
	$echo .= "	<b>" . FILEPATH_LABEL . " : </b><br />" . $path . "<br /><br />";
	$echo .= "	<b>" . INFOMEDIA_LABEL . " : </b><br />" . $mediaInfo . "<br /><br />";
	if($size)
		$echo .= "<b>" . FILESIZE_LABEL . " : </b><br />$size<br />";
	$echo .= "</div>";
	if(ENABLE_DOWNLOAD){
		$echo .= "<div class='details-toolbar'>";
		$echo .= "	<a target='_blank' href='dl.php?type=movie&id=" . $id . "'><img src='images/download.png' title='" . DOWNLOAD_LABEL . "' /></a>";
		$echo .= "</div>";
	}
	echo $echo;
}

/**
  * Return HTML formated result of details about a tvshow entry to display in the centered div.
  * TVShow is identified by $id.
  */
function getDetailsEntryTvShow($id){
	global $db, $WATCH_STATUS_FOR_USERS;
	$id = intval($_GET["id"]);
	// One SQL request to rule them all...
	$sql = "SELECT 
				" . NAX_TVSHOW_VIEW . ".c00 AS tvshowTitleFR,
				" . NAX_TVSHOW_VIEW . ".c01 AS tvshowSynopsis,
				" . NAX_TVSHOWSEASON_VIEW . ".episodes AS seasonNbEpisodes,
				" . NAX_TVSHOWSEASON_VIEW . ".season AS seasonIdseason,
				" . NAX_TVSHOWSEASON_VIEW . ".premiered AS seasonPremiered,
				" . NAX_TVSHOWSEASON_VIEW . ".playCount AS seasonPlayCount,
				" . NAX_TVSHOWEPISODE_VIEW . ".c13 AS episodeIdepisode,
				" . NAX_TVSHOWEPISODE_VIEW . ".c00 AS episodeTitle,
				" . NAX_TVSHOWEPISODE_VIEW . ".c10 AS episodeRealisator,
				" . NAX_TVSHOWEPISODE_VIEW . ".c04 AS episodeScriptwriter,
				" . NAX_TVSHOWEPISODE_VIEW . ".c03 AS episodeNote,
				" . NAX_TVSHOWEPISODE_VIEW . ".c01 AS episodeSynopsis,
				" . NAX_TVSHOWEPISODE_VIEW . ".playCount AS episodePlayCount,
				" . NAX_TVSHOWEPISODE_VIEW . ".studio,
				" . NAX_TVSHOWEPISODE_VIEW . ".strPath,
				" . NAX_TVSHOWEPISODE_VIEW . ".strFileName,
				" . NAX_TVSHOWEPISODE_VIEW . ".idEpisode,
				" . NAX_TVSHOWEPISODE_VIEW . ".idFile,
				ExtractValue(" . NAX_TVSHOW_VIEW . ".c06,'/thumb[@season=\"-1\"]') AS tvshowThumb0,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".strVideoCodec) 	AS streamStrVideoCodec,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iVideoWidth) 	AS streamIVideoWidth,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iVideoHeight) 	AS streamIVideoHeight,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".strAudioCodec) 	AS streamStrAudioCodec,
				MAX(" . NAX_STREAMDETAILS_TABLE . ".iAudioChannels) AS streamIAudioChannels
			FROM 
				" . NAX_TVSHOW_VIEW . ", 
				" . NAX_TVSHOWSEASON_VIEW . ", 
				" . NAX_TVSHOWEPISODE_VIEW . " LEFT JOIN
				" . NAX_STREAMDETAILS_TABLE . "
				ON " . NAX_TVSHOWEPISODE_VIEW . ".idFile=" . NAX_STREAMDETAILS_TABLE . ".idFile
			WHERE 
					" . NAX_TVSHOW_VIEW . ".idShow=:id
				AND " . NAX_TVSHOW_VIEW . ".idShow=" . NAX_TVSHOWSEASON_VIEW . ".idShow 
				AND " . NAX_TVSHOWSEASON_VIEW . ".idShow=" . NAX_TVSHOWEPISODE_VIEW . ".idShow 
				AND " . NAX_TVSHOWSEASON_VIEW . ".season=" . NAX_TVSHOWEPISODE_VIEW . ".c12
			GROUP BY
				" . NAX_TVSHOWEPISODE_VIEW . ".idFile,
				" . NAX_STREAMDETAILS_TABLE . ".idFile
			ORDER BY 
				" . NAX_TVSHOWSEASON_VIEW . ".idSeason,
				CAST(" . NAX_TVSHOWEPISODE_VIEW . ".c13 as SIGNED INTEGER) 
				ASC;";

	$stmt = $db->prepare($sql);
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$data = $stmt->fetch();
	$titleFR = $data["tvshowTitleFR"];
	$synopsis = $data["tvshowSynopsis"];
	
	$thumbs = picturesXMLtoURLArray($data["tvshowThumb0"]);  // loading all thumb
	if(strtolower(substr($thumbs[0], 0, 4)) != "http")
		$thumbs[0] = "http://" . $thumbs[0];

	$echo =  "<div class='tvshow-details-title'>$titleFR</div>";
	$echo .= "<div class='tvshow-details-details'><div><b>" . SYNOPSIS_LABEL . " : </b><br />$synopsis<br /><br /></div>";
	$echo .= "	<div class='tvshow-details-season'>";
	$currentSeason = -1;
	do {
		if($currentSeason != $data["seasonIdseason"]){ // Define season's DIV
			if($currentSeason > -1)
				$echo .= "</div>"; // End hidden div with all season's episodes
			$currentSeason = $data["seasonIdseason"];
			$episodes = $data["seasonNbEpisodes"];
			$seasonStatus = "unwatched";
			if(intval($data["seasonPlayCount"]) >= intval($episodes))
				$seasonStatus = "watched";
			$echo .= "<div class='tvshow-details-season-details' onclick=\"toggleTvshowContent('tvshow-details-season-episodes', '".$data["seasonIdseason"]."');\">";
			
			// Retrieve current season's thumb
			$sqlThumb = "SELECT ExtractValue(c06,'/thumb[@season=\"".$data["seasonIdseason"]."\"]') AS thumb FROM " . NAX_TVSHOW_VIEW . " WHERE " . NAX_TVSHOW_VIEW . ".idShow=:id;";
			$stmtThumb = $db->prepare($sqlThumb);
			$stmtThumb->bindValue('id', $id, PDO::PARAM_INT);
			$stmtThumb->execute();
			$dataThumb = $stmtThumb->fetch();
			$thumbs = picturesXMLtoURLArray($dataThumb["thumb"]);  // loading all thumb
			if(strtolower(substr($thumbs[0], 0, 4)) != "http")
				$thumbs[0] = "http://" . $thumbs[0];
			$echo .= "	<img class='tvshow-details-season-thumb arrondi' src='" . $thumbs[0] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
			if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
				$echo .= "	<img class='thumbStatusSeason' src='images/" . $seasonStatus . "Season.png' title='" . (($seasonStatus === "watched") ? STATUS_WATCHED_LABEL : STATUS_UNWATCHED_LABEL) . "' />";
			}
			$echo .= "	<div class='tvshow-details-season-details-infos'>";
			$echo .= "		<div class='text-up bold size125'>" . SEASON_LABEL . " ".$data["seasonIdseason"]."</div>";
			$echo .= "		<div class='text-down'>" . YEAR_LABEL . " ".$data["seasonPremiered"]."<br/>" . EPISODE_LABEL . " : ".$data["seasonNbEpisodes"]."</div>";
			$echo .= "	</div>";
			$echo .= "</div>";
			// Begin hidden div with all season's episodes
			$echo .= "<div class='tvshow-details-season-episodes' id='tvshow-details-season-episodes-".$data["seasonIdseason"]."'>";
		}

		// For each season's episode
		$mediaInfo = "";
		if($data["streamStrVideoCodec"] != "")
			$mediaInfo .= "[" . $data["streamStrVideoCodec"] . "]";
		if($data["streamIVideoWidth"] != "" && $data["streamIVideoHeight"] != "")
			$mediaInfo .= "[" . $data["streamIVideoWidth"] . "x" . $data["streamIVideoHeight"] . "]";
		if($data["streamStrAudioCodec"] != "")
			$mediaInfo .= "[" . $data["streamStrAudioCodec"] . "]";
		if($data["streamIAudioChannels"] != ""){
			switch($data["streamIAudioChannels"]){
				case "1":
					$mediaInfo .= "[mono]";break;
				case "2":
					$mediaInfo .= "[stereo]";break;
				case "3":
					$mediaInfo .= "[2.1]";break;
				case "4":
					$mediaInfo .= "[3.1]";break;
				case "5":
					$mediaInfo .= "[4.1]";break;
				case "6":
					$mediaInfo .= "[5.1]";break;
				case "7":
					$mediaInfo .= "[6.1]";break;
				case "8":
					$mediaInfo .= "[7.1]";break;
				case "9":
					$mediaInfo .= "[8.1]";break;
				case "10":
					$mediaInfo .= "[9.1]";break;
			}
		}
		$episodeStatus = "unwatched";
		if(intval($data["episodePlayCount"]) > 0)
			$episodeStatus = "watched";
		$echo .= "<div class='tvshow-details-season-episode'>";
		$path = str_replace("//", "/", (str_ireplace(NAX_TVSHOW_REMOTE_PATH, NAX_TVSHOW_LOCAL_PATH, $data["strPath"]) . "/" . $data["strFileName"]));
		$size = showsize($path);
		$path = preg_replace("#(?<=:/)[^:@]+(:[^@]+)?(?=@)#","...", $path);
		$echo .= "	<div class='tvshow-details-season-episode-titre' >";
		$echo .= $data["seasonIdseason"]."x".$data["episodeIdepisode"]." - ".$data["episodeTitle"];
		$echo .= "		<a onclick=\"toggleTvshowContent('tvshow-details-season-episode-synopsis', '".$data["idEpisode"]."');\" style=\"cursor:pointer;float:right;\"><img src='images/info.png' title='" . DESCRIPTION_LABEL . "' /></a>";
		if(ENABLE_DOWNLOAD)
			$echo .= "	<a target='_blank' style=\"cursor:pointer;float:right;\" href='dl.php?type=tvshow&id=" . $data["idEpisode"] . "'><img src='images/download.png' title='" . DOWNLOAD_LABEL . "' /></a>";
		if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
			$echo .= "	<a id=\"linkStatus_" . $data["idEpisode"] . "_watched\" onclick=\"changeStatusTvShow('toUnwatched', " . $data["idEpisode"] . ");\" style=\"cursor:pointer;float:right;display:" . (($episodeStatus === "watched") ? "visible" : "none") . "\"><img src='images/watchedButton.png' title='" . STATUS_WATCHED_LABEL . "' /></a>";
			$echo .= "	<a id=\"linkStatus_" . $data["idEpisode"] . "_unwatched\" onclick=\"changeStatusTvShow('toWatched', " . $data["idEpisode"] . ");\" style=\"cursor:pointer;float:right;display:" . (($episodeStatus === "unwatched") ? "visible" : "none") . "\"><img src='images/unwatchedButton.png' title='" . STATUS_UNWATCHED_LABEL . "' /></a>";
		}
		$echo .= "		<p class='tvshow-details-season-episode-synopsis' id='tvshow-details-season-episode-synopsis-".$data["idEpisode"]."'>";
		$echo .= $data["episodeSynopsis"];
		$echo .= "			<br /><br />";
		$echo .= "			<b>" . REALISATOR_LABEL . " :</b> " . $data["episodeRealisator"] . "<br />";
		$echo .= "			<b>" . SCRIPTWRITER_LABEL . " :</b> " . $data["episodeScriptwriter"] . "<br />";
		$echo .= "			<b>" . STUDIO_LABEL . " :</b> " . $data["studio"] . "<br />";
		$echo .= "			<b>" . NOTE_LABEL . " :</b> " . floatval($data["episodeNote"]) . "<br />";
		$echo .= "			<b>" . FILEPATH_LABEL . " :</b> $path<br />";
		$echo .= "			<b>" . INFOMEDIA_LABEL . " :</b> $mediaInfo<br />";
		$echo .= "			<b>" . FILESIZE_LABEL . " :</b> $size<br />";
		$echo .= "		</p>";
		$echo .= "	</div>";
		$echo .= "</div>";
	} while($data = $stmt->fetch()); // iterate for others seasons (season > 1)
	$echo .= "	</div>";
	$echo .= "</div>";
	echo $echo;
}

/**
  * Log who, when and what a user is downloading.
  */
function logDownload($user, $file){
	$fp = fopen(NAX_LOGS_PATH . "/" . $user . ".txt", "a");
	fputs($fp, "[" . date("d/m/Y H:i:s") . "] - $user " . DOWNLOAD_LABEL . " $file\n");
	fclose($fp);
}

?>
