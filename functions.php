<?php

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
  */
function checkInternalAuthentication($user, $password){
	global $USERS;
	if(is_array($USERS) && !empty($USERS)){
		if(array_key_exists(strval($user), $USERS)){
			return ($USERS[$user] === $password);					// PHP juggling attack protected
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
  * Check login and password against LDAP directory
  * Current user need to be memberOf the LDAP_GROUP_XBMC_FILTER.
  * return true or false if authentication succeed.
  */
function checkLDAPAuthentication($user, $password){
	if(preg_match("/^[a-zA-Z]+$/",$user)){
		$query_user = "uid=$user,".LDAP_USERS_DN;
		$ldap 		= ldap_connect(LDAP_AUTH_HOST);
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		if(!$ldap){
			echo "LDAP connection error";
			return false;
		}
		if(!@ldap_bind($ldap, $query_user, $password)){
			echo "LDAP bind error";
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
	$pictures = array();
	$pattern = "/((http|https|ftp)\:\/\/)?[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?\/([a-zA-Z0-9\-\.\?_&amp;%\$#\=~\/\'\,])*/";
	preg_match_all($pattern, $picturesXML, $pictures);
	return ((count($pictures) > 0) && (count($pictures[1]) > 0)) ? $pictures[0] : array();
}

/**
  * Extract the Youtube ID from an URL stored in KODI/XBMC database.
  */
function extractYoutubeId($url){
	$return = "";
	$urls = array();
	$pattern = "/(v=|videoid=)([a-zA-Z0-9_\-]{10,})&?/";
	preg_match_all($pattern, $url, $urls);
	return ((count($urls) > 0)) ? $urls[count($urls)-1][count(count($urls)-1)-1] : array();
}

/**
  * Get an array of all Year of movies.
  */
function getAllYears(){
	$return = array();
	$sql = 'SELECT DISTINCT c07 FROM `' . NAX_MOVIE_VIEW . '` ORDER BY c07 DESC;'; 
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)){
		if(intval($data["c07"]) > 0)
			$return[] = intval($data["c07"]);
	}
	return $return;
}

/**
  * Get an array of all Studios of TVShow.
  */
function getAllStudios(){
	$return = array();
	$sql = 'SELECT DISTINCT c14 FROM `' . NAX_TVSHOW_VIEW . '` ORDER BY c14 DESC;'; 
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)){
		if(!empty(trim(strval($data["c14"]))))
			$return[] = trim(strval($data["c14"]));
	}
	return $return;
}

/**
  * Get an array of all Genre of movies or TVshow ($type).
  */
function getAllGenres($type){
	if($type == "tvshow"){
		$column = "c08";
		$table  = NAX_TVSHOW_VIEW;
	} else {
		$column = "c14";
		$table  = NAX_MOVIE_VIEW;
	}
	$return = array();
	$sql = "SELECT DISTINCT $column FROM `$table` ORDER BY $column ASC;"; 
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)){
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
	$return = array();
	$sql = 'SELECT DISTINCT c21 FROM `' . NAX_MOVIE_VIEW . '` ORDER BY c21 ASC;'; 
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)){
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
  */
function showsize($file) {
	$return = "";
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
      $file = trim(shell_exec("$statBin -f %z " . escapeshellarg($file)));
    } elseif ((PHP_OS == 'Linux') || (PHP_OS == 'FreeBSD') || (PHP_OS == 'Unix') || (PHP_OS == 'SunOS')) {
	  $file = trim(shell_exec("$statBin -t " . escapeshellarg($file) . " | awk '{ print $(NF-14) }'"));
    } else {
      $file = filesize($file);
    }
    if ($file < 1024) {
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
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)) {
		$thumbs = picturesXMLtoURLArray($data["c08"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$thumbs[0] = str_replace("http://image.tmdb.org", "https://image.tmdb.org", $thumbs[0]); // HSTS
		$fanarts = picturesXMLtoURLArray($data["c20"]);  // loading all thumb
		if(strtolower(substr($fanarts[0], 0, 4)) != "http")
			$fanarts[0] = "http://" . $fanarts[0];
		$fanarts[0] = str_replace("http://image.tmdb.org", "https://image.tmdb.org", $fanarts[0]); // HSTS
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
	mysql_close(); 
}

/**
  * Return HTML formated result of tvshow entries to display in the main page.
  * Results depend on the $sql query passed in argument.
  */
function getEntriesTvShow($sql){
	$req = mysql_query($sql);
	while($data = mysql_fetch_array($req)) { 
		$thumbs = picturesXMLtoURLArray($data["thumb"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$thumbs[0] = str_replace("http://thetvdb.com", "https://thetvdb.com", $thumbs[0]); // HSTS
		$fanart = str_replace("http://thetvdb.com", "https://thetvdb.com", ($data["fanartURL"].$data["fanartValue"])); // HSTS
		
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

	mysql_close(); 
}

/**
  * Return HTML formated result of details about a movie entry to display in the centered div.
  * Movie is identified by $id.
  */
function getDetailsEntryMovie($id){
	$id = intval($_GET["id"]);
	$sql = "SELECT * FROM " . NAX_MOVIE_VIEW . ", " . NAX_ACTORS_TABLE . ", " . NAX_ACTORLINKMOVIE_TABLE . " WHERE " . NAX_MOVIE_VIEW . ".idMovie=" . NAX_ACTORLINKMOVIE_TABLE . ".media_id AND " . NAX_ACTORLINKMOVIE_TABLE . ".actor_id=" . NAX_ACTORS_TABLE . ".actor_id AND " . NAX_MOVIE_VIEW . ".idMovie=$id;";
	$req = mysql_query($sql);
	$data = mysql_fetch_array($req);
	$titleFR = $data["c00"];
	$titleEN = $data["c16"];
	$synopsis = $data["c01"];
	$thumbs = picturesXMLtoURLArray($data["c08"]);  // loading all thumb
	if(strtolower(substr($thumbs[0], 0, 4)) != "http")
		$thumbs[0] = "http://" . $thumbs[0];
	$thumbs[0] = str_replace("http://image.tmdb.org", "https://image.tmdb.org", $thumbs[0]); // HSTS
	$genre = $data["c14"];
	$year = $data["c07"];
	$realisator = $data["c15"];
	$nationality = $data["c21"];
	$path = str_replace("//", "/", (str_ireplace(NAX_MOVIES_REMOTE_PATH, NAX_MOVIES_LOCAL_PATH, $data["strPath"]) . "/" . $data["strFileName"]));
	$size = showsize($path);
	$actors = $data["name"] . " (" . $data["role"] . ")";
	while($data = mysql_fetch_array($req)){
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
	mysql_close();
}

/**
  * Return HTML formated result of details about a tvshow entry to display in the centered div.
  * TVShow is identified by $id.
  */
function getDetailsEntryTvShow($id){
	$id = intval($_GET["id"]);
	$sql = "SELECT *,ExtractValue(c06,'/thumb[@season=\"-1\"]') AS thumb FROM " . NAX_TVSHOW_VIEW . " WHERE " . NAX_TVSHOW_VIEW . ".idShow=$id;";
	//echo $sql;
	$req = mysql_query($sql);
	$data = mysql_fetch_array($req);
	$totalSaisons = intval($data["totalSeasons"]);
	$titleFR = $data["c00"];
	//$titleEN = $data["c16"];
	$synopsis = $data["c01"];
	$thumbs = picturesXMLtoURLArray($data["thumb"]);  // loading all thumb
	if(strtolower(substr($thumbs[0], 0, 4)) != "http")
		$thumbs[0] = "http://" . $thumbs[0];
	$thumbs[0] = str_replace("http://thetvdb.com", "https://thetvdb.com", $thumbs[0]); // HSTS

	//$actors = $data["name"] . " (" . $data["role"] . ")";
	//while($data = mysql_fetch_array($req))
	//	$actors .= ", " . $data["name"] . " (" . $data["role"] . ")";
	$echo =  "<div class='serie-details-title'>$titleFR</div>";
	$echo .= "<div class='serie-details-details'><div><b>Synopsis : </b><br />" . $synopsis . "<br /><br />";
	//$echo .= "Actors : $actors<br /><br />";
	$echo .= "</div>";
	$echo .= "<div class='serie-details-saison'>";
	$sql = "SELECT * FROM " . NAX_TVSHOWSEASON_VIEW . " WHERE " . NAX_TVSHOWSEASON_VIEW . ".idShow=$id ORDER BY " . NAX_TVSHOWSEASON_VIEW . ".idSeason ASC;";
	$req = mysql_query($sql);
	for($i=0;$i<$totalSaisons;$i++)
	{
		$dataSeason = mysql_fetch_array($req);
		$episodes = $dataSeason["episodes"];
		$echo .= "<div class='serie-details-saison-details' onclick=\"toggleTvshowContent('serie-details-saison-episodes', '".$i."');\">";
		$sqlThumb = "SELECT ExtractValue(c06,'/thumb[@season=\"".$dataSeason["season"]."\"]') AS thumb FROM " . NAX_TVSHOW_VIEW . " WHERE " . NAX_TVSHOW_VIEW . ".idShow=$id;";
		$reqThumb = mysql_query($sqlThumb);
		$dataThumb = mysql_fetch_array($reqThumb);
		$thumbs = picturesXMLtoURLArray($dataThumb["thumb"]);  // loading all thumb
		if(strtolower(substr($thumbs[0], 0, 4)) != "http")
			$thumbs[0] = "http://" . $thumbs[0];
		$thumbs[0] = str_replace("http://thetvdb.com", "https://thetvdb.com", $thumbs[0]); // HSTS
		$echo .= "	<img class='serie-details-saison-thumb arrondi' src='" . $thumbs[0] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
		$echo .= "	<div class='serie-details-saison-details-infos'>";
		$echo .= "		<div class='text-up bold size125'>Season ".$dataSeason["season"]."</div>";
		$echo .= "		<div class='text-down'>First broadcast ".$dataSeason["premiered"]."<br/>Episodes: ".$dataSeason["episodes"]."</div>";
		$echo .= "	</div>";
		$echo .= "</div>";
		// Hidden div with all season's episodes
		$echo .= "<div class='serie-details-saison-episodes' id='serie-details-saison-episodes-".$i."'>";
		// Get all episode for current season
		$sqlEpisodes = "SELECT * FROM " . NAX_TVSHOWEPISODE_VIEW . " WHERE " . NAX_TVSHOWEPISODE_VIEW . ".idShow=$id AND " . NAX_TVSHOWEPISODE_VIEW . ".c12=".$dataSeason["season"]." ORDER BY CAST(c13 as SIGNED INTEGER) ASC;";
		$reqEpisodes = mysql_query($sqlEpisodes);
		for($j=0;$j<$episodes;$j++){
			$dataEpisodes = mysql_fetch_array($reqEpisodes);
			$echo .= "<div class='serie-details-saison-episode'>";
			$path = str_replace("//", "/", (str_ireplace(NAX_TVSHOW_REMOTE_PATH, NAX_TVSHOW_LOCAL_PATH, $dataEpisodes["strPath"]) . "/" . $dataEpisodes["strFileName"]));
			$size = showsize($path);
			$echo .= "	<div class='serie-details-saison-episode-titre' >";
			$echo .= $dataEpisodes["c12"]."x".$dataEpisodes["c13"]." - ".$dataEpisodes["c00"];
			$echo .= "		<a onclick=\"toggleTvshowContent('serie-details-saison-episode-synopsis', '".$dataEpisodes["idEpisode"]."');\" style=\"cursor:pointer;float:right;\"><img src='images/info.png' title='Description' /></a>";
			if(ENABLE_DOWNLOAD)
				$echo .= "		<a target='_blank' style=\"cursor:pointer;float:right;\" href='dl.php?type=tvshow&id=" . $dataEpisodes["idEpisode"] . "'><img src='images/download.png' title='Download' /></a>";
			$echo .= "		<p class='serie-details-saison-episode-synopsis' id='serie-details-saison-episode-synopsis-".$dataEpisodes["idEpisode"]."'>";
			$echo .= $dataEpisodes["c01"];
			$echo .= "			<br /><br />";
			$echo .= "			<b>Realisator :</b> " . $dataEpisodes["c10"] . "<br />";
			$echo .= "			<b>Scriptwriter :</b> " . $dataEpisodes["c04"] . "<br />";
			$echo .= "			<b>Studio :</b> " . $dataEpisodes["studio"] . "<br />";
			$echo .= "			<b>Note :</b> " . floatval($dataEpisodes["c03"]) . "<br />";
			$echo .= "			<b>File path :</b> $path<br />";
			if($size)
				$echo .= "		<b>File size :</b> $size<br />";
			//$echo .= "			<img class='serie-details-episode-thumb' style='float:right;' src='" . $dataEpisodes["thumb"] . "' onerror=\"this.src='images/thumb-onerror.jpg';\" />";
			$echo .= "		</p>";
			$echo .= "	</div>";
			$echo .= "</div>";
		}
		$echo .= "</div>";
	}
	$echo .= "</div>";
	$echo .= "</div>";
	echo $echo;
	mysql_close();
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