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
  */
function checkInternalAuthentication($user, $password){
	global $USERS;
	return (is_array($USERS) && !empty($USERS) && array_key_exists(strval($user), $USERS) && ($USERS[$user] === $password));
}

/**
  * Check login and password against LDAP directory
  * Current user need to be memberOf the LDAP_GROUP_XBMC_FILTER.
  * return true or false if authentication succeed.
  */
function checkLDAPAuthentication($user, $password){
	if(preg_match("/^[a-zA-Z]+$/",$user)){
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
	$stmt = $db->query("SELECT DISTINCT c07 FROM `" . NAX_MOVIE_VIEW . "` ORDER BY c07 DESC;");
	while($data = $stmt->fetch()){
		if(intval($data["c07"]) > 0)
			$return[] = intval($data["c07"]);
	}
	return $return;
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
		$return = $file . ' Byte';
    } elseif ($file < 1048576) {
		$return = round($file / 1024, 2) . ' Ko';
    } elseif ($file < 1073741824) {
		$return = round($file / 1048576, 2) . ' Mo';
    } elseif ($file < 1099511627776) {
		$return = round($file / 1073741824, 2) . ' Go';
    } elseif ($file < 1125899906842624) {
		$return = round($file / 1099511627776, 2) . ' To';
    } elseif ($file < 1152921504606846976) {
		$return = round($file / 1125899906842624, 2) . ' Po';
    } elseif ($file < 1180591620717411303424) {
		$return = round($file / 1152921504606846976, 2) . ' Eo';
    } elseif ($file < 1208925819614629174706176) {
		$return = round($file / 1180591620717411303424, 2) . ' Zo';
    } else {
		$return = round($file / 1208925819614629174706176, 2) . ' Yo';
    }
	return $return;
}

/**
  * Return HTML formated result of movies entries to display in the main page.
  * Results depend on the $sql query passed in argument.
  */
function getEntriesMovies($sql){
	global $db;
	$stmt = $db->query($sql);
	while($data = $stmt->fetch()){
		$thumbs = picturesXMLtoURLArray($data["c08"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$fanarts = picturesXMLtoURLArray($data["c20"]);  // loading all thumb
		if(strtolower(substr($fanarts[0], 0, 4)) != "http")
			$fanarts[0] = "http://" . $fanarts[0];
	?>
	<div class="entry arrondi" id="<?php echo $data["idMovie"]; ?>">
	  <div class="fanart arrondi"><img class="arrondi" src="<?php echo $fanarts[0]; ?>" onerror="this.src='images/fanart-onerror.png';" style="display:none;" /></div>
	  <div class="title"><?php echo $data["c00"]; ?></div>
	  <img class="thumb arrondi" src="<?php echo $thumbs[0]; ?>" onerror="this.src='images/thumb-onerror.jpg';" style="display:none;" />
	  <div class="synopsis"><?php echo $data["c01"]; ?></div>
	  <div class="toolbar">
	<?php
	  $youtubeID = extractYoutubeId($data["c19"]);
	  if(!empty($youtubeID))
		echo "<a onclick=\"displayYoutube('video_" . $data["idMovie"] . "', '" . $youtubeID . "');return false;\" href='https://www.youtube.com/watch?v=" . $youtubeID . "'><img src='images/youtube.png' title='Watch trailer' /></a>";
	?>
		<a onclick="printDetails('details_<?php echo $data["idMovie"]; ?>', <?php echo $data["idMovie"]; ?>);" style="cursor:pointer;"><img src='images/info.png' title='Description' /></a>
	<?php if(ENABLE_DOWNLOAD){ ?>
		<a target="_blank" href="dl.php?type=movie&id=<?php echo $data["idMovie"]; ?>"><img src='images/download.png' title='Download' /></a>
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
	global $db;
	$stmt = $db->query($sql);
	while($data = $stmt->fetch()){
		$thumbs = picturesXMLtoURLArray($data["thumb"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$fanart = $data["fanartURL"].$data["fanartValue"];
		
	?>
	<div class="entry arrondi" id="<?php echo $data["idShow"]; ?>">
	  <div class="fanart arrondi">
		<img class="arrondi" src="<?php echo $fanart; ?>" onerror="this.src='images/fanart-onerror.png';" style="display:none;" />
	  </div>
	  <div class="title"><?php echo $data["c00"]; ?></div>
	  <img class="thumb arrondi" src="<?php echo $thumbs[0]; ?>" onerror="this.src='images/thumb-onerror.jpg';" style="display:none;" />
	  <div class="synopsis"><?php echo $data["c01"]; ?></div>
	  <div class="toolbar">
		<a onclick="printDetails('details_<?php echo $data["idShow"]; ?>', <?php echo $data["idShow"]; ?>);" style="cursor:pointer;"><img src='images/info.png' title='Description' /></a>
	<?php if(ENABLE_DOWNLOAD){ ?>
			<a onclick="printDetails('details_<?php echo $data["idShow"]; ?>', <?php echo $data["idShow"]; ?>);" style="cursor:pointer;"><img src='images/download.png' title='Download' /></a>
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
	global $db;
	$id = intval($_GET["id"]);
	$sql = "SELECT 
				" . NAX_MOVIE_VIEW . ".c00 AS movieTitleFR,
				" . NAX_MOVIE_VIEW . ".c16 AS movieTitleEN,
				" . NAX_MOVIE_VIEW . ".c01 AS movieSynopsis,
				" . NAX_MOVIE_VIEW . ".c08 AS movieThumbs,
				" . NAX_MOVIE_VIEW . ".c14 AS movieGenre,
				" . NAX_MOVIE_VIEW . ".c07 AS movieYear,
				" . NAX_MOVIE_VIEW . ".c15 AS movieRealisator,
				" . NAX_MOVIE_VIEW . ".c21 AS movieNationality,
				" . NAX_MOVIE_VIEW . ".strPath,
				" . NAX_MOVIE_VIEW . ".strFileName,
				" . NAX_ACTORS_TABLE . ".name,
				" . NAX_ACTORLINKMOVIE_TABLE . ".role
			FROM 
				" . NAX_MOVIE_VIEW . ", 
				" . NAX_ACTORS_TABLE . ", 
				" . NAX_ACTORLINKMOVIE_TABLE . "
			WHERE 
					" . NAX_MOVIE_VIEW . ".idMovie=:id
				AND	" . NAX_MOVIE_VIEW . ".idMovie=" . NAX_ACTORLINKMOVIE_TABLE . ".media_id 
				AND " . NAX_ACTORLINKMOVIE_TABLE . ".actor_id=" . NAX_ACTORS_TABLE . ".actor_id;";
	$stmt = $db->prepare($sql);
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$data = $stmt->fetch();
	$titleFR = $data["movieTitleFR"];
	$titleEN = $data["movieTitleEN"];
	$synopsis = $data["movieSynopsis"];
	$thumbs = picturesXMLtoURLArray($data["movieThumbs"]);  // loading all thumb
	if(strtolower(substr($thumbs[0], 0, 4)) != "http")
		$thumbs[0] = "http://" . $thumbs[0];
	$genre = $data["movieGenre"];
	$year = $data["movieYear"];
	$realisator = $data["movieRealisator"];
	$nationality = $data["movieNationality"];
	$path = str_replace("//", "/", (str_ireplace(NAX_MOVIES_REMOTE_PATH, NAX_MOVIES_LOCAL_PATH, $data["strPath"]) . "/" . $data["strFileName"]));
	$size = showsize($path);
	$actors = $data["name"] . " (" . $data["role"] . ")";
	while($data = $stmt->fetch()){
		$actors .= ", " . $data["name"] . " (" . $data["role"] . ")";
	}
	$echo =  "<div class='details-title'>" . $titleFR . " (" . $titleEN . ")</div>";
	$echo .= "<img class='details-thumb arrondi' src='" . $thumbs[0] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
	$echo .= "<div class='details-details'><b>Synopsis : </b><br />" . $synopsis . "<br /><br />";
	$echo .= "	<b>Year : </b><br />" . $year . "<br /><br />";
	$echo .= "	<b>Genre : </b><br />" . $genre . "<br /><br />";
	$echo .= "	<b>Realisator : </b><br />" . $realisator . "<br /><br />";
	$echo .= "	<b>Nationality : </b><br />" . $nationality . "<br /><br />";
	$echo .= "	<b>Actor(s) : </b><br />" . $actors . "<br /><br />";
	$echo .= "	<b>File path : </b><br />" . $path . "<br /><br />";
	if($size)
		$echo .= "<b>File size : </b><br />$size<br />";
	$echo .= "</div>";
	if(ENABLE_DOWNLOAD){
		$echo .= "<div class='details-toolbar'>";
		$echo .= "	<a target='_blank' href='dl.php?type=movie&id=" . $id . "'><img src='images/download.png' title='Download' /></a>";
		$echo .= "</div>";
	}
	echo $echo;
}

/**
  * Return HTML formated result of details about a tvshow entry to display in the centered div.
  * TVShow is identified by $id.
  */
function getDetailsEntryTvShow($id){
	global $db;
	$id = intval($_GET["id"]);
	// One SQL request to rule them all...
	$sql = "SELECT 
				" . NAX_TVSHOW_VIEW . ".c00 AS tvshowTitleFR,
				" . NAX_TVSHOW_VIEW . ".c01 AS tvshowSynopsis,
				" . NAX_TVSHOWSEASON_VIEW . ".episodes AS seasonNbEpisodes,
				" . NAX_TVSHOWSEASON_VIEW . ".season AS seasonIdseason,
				" . NAX_TVSHOWSEASON_VIEW . ".premiered AS seasonPremiered,
				" . NAX_TVSHOWEPISODE_VIEW . ".c13 AS episodeIdepisode,
				" . NAX_TVSHOWEPISODE_VIEW . ".c00 AS episodeTitle,
				" . NAX_TVSHOWEPISODE_VIEW . ".c10 AS episodeRealisator,
				" . NAX_TVSHOWEPISODE_VIEW . ".c04 AS episodeScriptwriter,
				" . NAX_TVSHOWEPISODE_VIEW . ".c03 AS episodeNote,
				" . NAX_TVSHOWEPISODE_VIEW . ".c01 AS episodeSynopsis,
				" . NAX_TVSHOWEPISODE_VIEW . ".studio,
				" . NAX_TVSHOWEPISODE_VIEW . ".strPath,
				" . NAX_TVSHOWEPISODE_VIEW . ".strFileName,
				" . NAX_TVSHOWEPISODE_VIEW . ".idEpisode,
				ExtractValue(" . NAX_TVSHOW_VIEW . ".c06,'/thumb[@season=\"-1\"]') AS tvshowThumb0 
			FROM 
				" . NAX_TVSHOW_VIEW . ", 
				" . NAX_TVSHOWSEASON_VIEW . ", 
				" . NAX_TVSHOWEPISODE_VIEW . " 
			WHERE 
					" . NAX_TVSHOW_VIEW . ".idShow=:id
				AND " . NAX_TVSHOW_VIEW . ".idShow=" . NAX_TVSHOWSEASON_VIEW . ".idShow 
				AND " . NAX_TVSHOWSEASON_VIEW . ".idShow=" . NAX_TVSHOWEPISODE_VIEW . ".idShow 
				AND " . NAX_TVSHOWSEASON_VIEW . ".season=" . NAX_TVSHOWEPISODE_VIEW . ".c12 
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

	$echo =  "<div class='serie-details-title'>$titleFR</div>";
	$echo .= "<div class='serie-details-details'><div><b>Synopsis : </b><br />$synopsis<br /><br /></div>";
	$echo .= "	<div class='serie-details-saison'>";
	$currentSeason = -1;
	do {
		if($currentSeason != $data["seasonIdseason"]){ // Define season's DIV
			if($currentSeason > -1)
				$echo .= "</div>"; // End hidden div with all season's episodes
			$currentSeason = $data["seasonIdseason"];
			$episodes = $data["seasonNbEpisodes"];
			$echo .= "<div class='serie-details-saison-details' onclick=\"toggleTvshowContent('serie-details-saison-episodes', '".$data["seasonIdseason"]."');\">";
			
			// Retrieve current season's thumb (TODO)
			$sqlThumb = "SELECT ExtractValue(c06,'/thumb[@season=\"".$data["seasonIdseason"]."\"]') AS thumb FROM " . NAX_TVSHOW_VIEW . " WHERE " . NAX_TVSHOW_VIEW . ".idShow=:id;";
			$stmtThumb = $db->prepare($sqlThumb);
			$stmtThumb->bindValue('id', $id, PDO::PARAM_INT);
			$stmtThumb->execute();
			$dataThumb = $stmtThumb->fetch();
			$thumbs = picturesXMLtoURLArray($dataThumb["thumb"]);  // loading all thumb
			if(strtolower(substr($thumbs[0], 0, 4)) != "http")
				$thumbs[0] = "http://" . $thumbs[0];
			$echo .= "	<img class='serie-details-saison-thumb arrondi' src='" . $thumbs[0] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
			
			$echo .= "	<div class='serie-details-saison-details-infos'>";
			$echo .= "		<div class='text-up bold size125'>Season ".$data["seasonIdseason"]."</div>";
			$echo .= "		<div class='text-down'>First broadcast ".$data["seasonPremiered"]."<br/>Episodes: ".$data["seasonNbEpisodes"]."</div>";
			$echo .= "	</div>";
			$echo .= "</div>";
			// Begin hidden div with all season's episodes
			$echo .= "<div class='serie-details-saison-episodes' id='serie-details-saison-episodes-".$data["seasonIdseason"]."'>";
		}

		// For each season's episode
		$echo .= "<div class='serie-details-saison-episode'>";
		$path = str_replace("//", "/", (str_ireplace(NAX_TVSHOW_REMOTE_PATH, NAX_TVSHOW_LOCAL_PATH, $data["strPath"]) . "/" . $data["strFileName"]));
		$size = showsize($path);
		$echo .= "	<div class='serie-details-saison-episode-titre' >";
		$echo .= $data["seasonIdseason"]."x".$data["episodeIdepisode"]." - ".$data["episodeTitle"];
		$echo .= "		<a onclick=\"toggleTvshowContent('serie-details-saison-episode-synopsis', '".$data["idEpisode"]."');\" style=\"cursor:pointer;float:right;\"><img src='images/info.png' title='Description' /></a>";
		if(ENABLE_DOWNLOAD)
			$echo .= "	<a target='_blank' style=\"cursor:pointer;float:right;\" href='dl.php?type=tvshow&id=" . $data["idEpisode"] . "'><img src='images/download.png' title='Download' /></a>";
		$echo .= "		<p class='serie-details-saison-episode-synopsis' id='serie-details-saison-episode-synopsis-".$data["idEpisode"]."'>";
		$echo .= $data["episodeSynopsis"];
		$echo .= "			<br /><br />";
		$echo .= "			<b>Realisator :</b> " . $data["episodeRealisator"] . "<br />";
		$echo .= "			<b>Scriptwriter :</b> " . $data["episodeScriptwriter"] . "<br />";
		$echo .= "			<b>Studio :</b> " . $data["studio"] . "<br />";
		$echo .= "			<b>Note :</b> " . floatval($data["episodeNote"]) . "<br />";
		$echo .= "			<b>File path :</b> $path<br />";
		$echo .= "			<b>File size :</b> $size<br />";
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
	fputs($fp, "[" . date("d/m/Y H:i:s") . "] - $user downloading $file\n");
	fclose($fp);
}

?>