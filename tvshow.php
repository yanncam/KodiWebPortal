<?php
require_once("./config.php");
require_once("./functions.php");
defineSecurityHeaders();
sessionStartSecurely();
require_once("./db.php");

if(ENABLE_AUTHENTICATION){
	if(!isAuthenticated()){
		header("Location: login.php");
		exit;
	}
}

// Clean GET vars for filter
$title 			= (isset($_GET["title"]) && !empty($_GET["title"])) ? substr($db->quote(trim(strval($_GET["title"]))),1,-1) : "";
$genre 			= (isset($_GET["genre"]) && !empty($_GET["genre"])) ? substr($db->quote(trim(strval($_GET["genre"]))),1,-1) : "";
$studio		 	= (isset($_GET["studio"]) && !empty($_GET["studio"])) ? substr($db->quote(trim(strval($_GET["studio"]))),1,-1) : "";
$offset 		= (isset($_GET["offset"]) && trim(strval($_GET["offset"])) != "" && intval($_GET["offset"]) >= 0) ? intval($_GET["offset"]) : 0;

// Compute SQL filters
$filters = array();
if(!empty($title)){
	$filters[] = "(c00 LIKE '%" . $title . "%' OR c01 LIKE '%" . $title . "%')";
}
if(!empty($genre)){
	$filters[] = "c08 LIKE '%" . $genre . "%'";
}
if(!empty($studio)){
	$filters[] = "c14 LIKE '%" . $studio . "%'";
}

if(count($filters) > 0){
	$sql = "SELECT 
				" . NAX_TVSHOW_VIEW . ".idShow,
				" . NAX_TVSHOW_VIEW . ".c00 AS tvshowTitle,
				" . NAX_TVSHOW_VIEW . ".c01 AS tvshowSynopsis,
				" . NAX_TVSHOW_VIEW . ".totalCount,
				" . NAX_TVSHOW_VIEW . ".watchedcount,
				ExtractValue(c06,'/thumb[@season=\"-1\"]') AS thumb,
				ExtractValue(c11,'/fanart/@url') AS fanartURL,
				ExtractValue(c11,'/fanart/thumb[position()=1]/@preview') AS fanartValue
			FROM " . NAX_TVSHOW_VIEW . " WHERE ";
	$multi = false;
	foreach($filters as $filter){
		if($multi)
			$sql .= "AND";
		$sql .= " " . $filter . " ";
		$multi = true;
	}
	$sql .= "ORDER BY c00 ASC LIMIT $offset," . DEFAULT_ENTRIES_DISPLAY . ";";
} else {
	$sql = "SELECT 
				" . NAX_TVSHOW_VIEW . ".idShow,
				" . NAX_TVSHOW_VIEW . ".c00 AS tvshowTitle,
				" . NAX_TVSHOW_VIEW . ".c01 AS tvshowSynopsis,
				" . NAX_TVSHOW_VIEW . ".totalCount,
				" . NAX_TVSHOW_VIEW . ".watchedcount,
				ExtractValue(c06,'/thumb[@season=\"-1\"]') AS thumb,
				ExtractValue(c11,'/fanart/@url') AS fanartURL,
				ExtractValue(c11,'/fanart/thumb[position()=1]/@preview') AS fanartValue
			FROM " . NAX_TVSHOW_VIEW . " ORDER BY dateAdded DESC LIMIT $offset," . DEFAULT_ENTRIES_DISPLAY . ";";
}

if(isset($_GET["id"]) && isset($_GET["action"]) && ($_GET["action"] === "toWatched" || $_GET["action"] === "toUnwatched")){
	$id = intval($_GET["id"]);
	$toStatus = strval($_GET["action"]);
	if($id > 0)
		changeStatusEpisode($id, $toStatus);
	exit;
}

if(isset($_GET["id"]) && isset($_GET["action"]) && $_GET["action"] == "detail"){
	$id = intval($_GET["id"]);
	if($id > 0)
		getDetailsEntryTvShow($id);
	exit;
}

if(isset($_GET["offset"])){
	$offset = intval($_GET["offset"]);
	if($offset < 0)
		$offset = 0;
	sleep(1);
	getEntriesTvShow($sql);
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Pragma-directive" content="no-cache" />
	<meta http-equiv="Cache-Directive" content="no-cache" />
	<meta name="robots" content="noindex,follow" />
	<title>KodiWebPortal <?php echo KODI_WEB_PORTAL_VERSION; ?></title>
	<link rel="stylesheet" href="style.css">
	<script type="text/javascript" src="https://code.jquery.com/jquery.min.js"></script>
	<script type="text/javascript" src="script.js"></script>
</head>

<body>
<a href="index.php" alt="<?php echo LOGO_MOVIES_LABEL; ?>" title="<?php echo LOGO_MOVIES_LABEL; ?>"><img class="image-nav" src="./images/cinema-logo.png" style="margin-top:30px;" /></a>
<a href="tvshow.php" alt="<?php echo LOGO_TVSHOWS_LABEL; ?>" title="<?php echo LOGO_TVSHOWS_LABEL; ?>"><img class="image-nav image-nav-move" src="./images/tvshow-logo.png" style="margin-top:160px;" /></a>
<div id="opacity"></div>

<div id="search">
<form action="" method="GET">
<?php
	$stmt = $db->query("SELECT DISTINCT COUNT(*) FROM " . NAX_TVSHOW_VIEW . ";");
	$count = $stmt->fetch();
	echo "<b>[ " . $count[0] . " " . TVSHOWS_LABEL . " ]</b> - ";
?>
	<label for="title"><?php echo TITLE_LABEL; ?></label>
	<input id="title" type="text" name="title" placeholder="Vikings" value="<?php echo htmlentities(stripcslashes($title)); ?>" />

	<label for="genre"><?php echo GENRE_LABEL; ?></label>
	<select id="genre" name="genre">
		<option value="">*</option>
<?php
	$gs = getAllGenres("tvshow");
	foreach($gs as $g){
		if(stripcslashes($genre) == $g)
			echo "<option value='" . $g . "' selected>" . $g . "</option>";
		else
			echo "<option value='" . $g . "'>" . $g . "</option>";
	}
?>
	</select>

	<label for="studio"><?php echo STUDIO_LABEL; ?></label>
	<select id="studio" name="studio">
		<option value="">*</option>
<?php
	$ss = getAllStudios();
	foreach($ss as $s){
		if(stripcslashes($studio) == $s)
			echo "<option value='" . $s . "' selected>" . $s . "</option>";
		else
			echo "<option value='" . $s . "'>" . $s . "</option>";
	}
?>
	</select>

	<input type="submit" value="<?php echo SEARCH_LABEL; ?>" />
	<input type="button" onclick="document.location=document.location.href.replace(document.location.search, '');" value="<?php echo RESET_LABEL; ?>" />
	<a href="index.php?action=logout"><img src="./images/logout.png" title="<?php echo LOGOUT_LABEL; ?>" alt="<?php echo LOGOUT_LABEL; ?>" style="float:right;" /></a>
</form>
</div>

<div id="entries">

<script type="text/javascript">getEntries();</script>

</div>
</body>
</html>