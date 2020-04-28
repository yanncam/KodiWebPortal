<?php
require_once("./config.php");
require_once("./functions.php");
defineSecurityHeaders();
sessionStartSecurely();
require_once("./db.php");

if(ENABLE_AUTHENTICATION){
	if(!isAuthenticated() && !isset($_POST['user'], $_POST['pass'])){
		header("Location: login.php");
		exit;
	} elseif(!isAuthenticated() && isset($_POST['user'], $_POST['pass'])){
		sleep(2);
		if(!checkAuthentication(trim(strval($_POST['user'])), trim(strval($_POST['pass'])))){
			echo "<span class='error'>" . AUTHENTICATION_ERROR_CREDENTIAL . "</span>";
			authSyslog("Client authentication failure");
			exit;
		} else {
			echo "<span class='success'>" . AUTHENTICATION_SUCCESS_REDIRECT . "&nbsp;&nbsp;&nbsp;</span><img src='./images/loading-login.gif' />";
			echo "<script type='text/javascript'>document.location='./';</script>";
			authSyslog("New client authentication");
			exit;
		}
	}
}

// Clean GET vars for filter
$title 			= (isset($_GET["title"]) && !empty($_GET["title"])) ? substr($db->quote(trim(strval($_GET["title"]))),1,-1) : "";
$watched 		= (isset($_GET["watched"]) && !empty($_GET["watched"])) ? substr($db->quote(trim(strval($_GET["watched"]))),1,-1) : "";
$genre 			= (isset($_GET["genre"]) && !empty($_GET["genre"])) ? substr($db->quote(trim(strval($_GET["genre"]))),1,-1) : "";
$year 			= (isset($_GET["year"]) && !empty($_GET["year"])) ? intval($_GET["year"]) : "";
$realisator 		= (isset($_GET["realisator"]) && !empty($_GET["realisator"])) ? substr($db->quote(trim(strval($_GET["realisator"]))),1,-1) : "";
$nationality 		= (isset($_GET["nationality"]) && !empty($_GET["nationality"])) ? substr($db->quote(trim(strval($_GET["nationality"]))),1,-1) : "";
$offset 		= (isset($_GET["offset"]) && trim(strval($_GET["offset"])) != "" && intval($_GET["offset"]) >= 0) ? intval($_GET["offset"]) : 0;

// Compute SQL filters
$filters = array();
if(!empty($title)){
	$filters[] = "(c00 LIKE '%" . $title . "%' OR c16 LIKE '%" . $title . "%' OR c01 LIKE '%" . $title . "%')";
}
if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))){
	if($watched === "YES")
		$filters[] = "playCount>=1";
	if($watched === "NO")
		$filters[] = "(playCount <= 0 OR playCount IS NULL)";
}
if(!empty($genre)){
	$filters[] = "c14 LIKE '%" . $genre . "%'";
}
if(!empty($year)){
	$filters[] = "(c07='" . $year . "' OR premiered LIKE '" . $year . "-%')";
}
if(!empty($realisator)){
	$filters[] = "(c06 LIKE '%" . $realisator . "%' OR c15 LIKE '%" . $realisator . "%')";
}
if(!empty($nationality)){
	$filters[] = "c21 LIKE '%" . $nationality . "%'";
}

if(count($filters) > 0){
	$sql = "SELECT 
				" . NAX_MOVIE_VIEW . ".idMovie,
				" . NAX_MOVIE_VIEW . ".c00 AS movieTitleFR,
				" . NAX_MOVIE_VIEW . ".c01 AS movieSynopsis,
				" . NAX_MOVIE_VIEW . ".c08 AS movieThumbs,
				" . NAX_MOVIE_VIEW . ".c19 AS movieToutube,
				" . NAX_MOVIE_VIEW . ".c20 AS movieFanarts,
				" . NAX_MOVIE_VIEW . ".playCount,
				" . NAX_MOVIE_VIEW . ".premiered AS movieYear
			FROM " . NAX_MOVIE_VIEW . " WHERE ";
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
				" . NAX_MOVIE_VIEW . ".idMovie,
				" . NAX_MOVIE_VIEW . ".c00 AS movieTitleFR,
				" . NAX_MOVIE_VIEW . ".c01 AS movieSynopsis,
				" . NAX_MOVIE_VIEW . ".c08 AS movieThumbs,
				" . NAX_MOVIE_VIEW . ".c19 AS movieToutube,
				" . NAX_MOVIE_VIEW . ".c20 AS movieFanarts,
				" . NAX_MOVIE_VIEW . ".playCount,
				" . NAX_MOVIE_VIEW . ".premiered AS movieYear
			FROM " . NAX_MOVIE_VIEW . " ORDER BY dateAdded DESC LIMIT $offset," . DEFAULT_ENTRIES_DISPLAY . ";"; 
}
if(isset($_GET["action"]) && $_GET["action"] == "logout"){
	session_destroy();
	setcookie("KODIWEBPORTAL", "", time()-3600);
	header("Location: login.php");
	exit;
}

if(isset($_GET["id"]) && isset($_GET["action"]) && ($_GET["action"] === "toWatched" || $_GET["action"] === "toUnwatched")){
	$id = intval($_GET["id"]);
	$toStatus = strval($_GET["action"]);
	if($id > 0)
		changeStatusMovie($id, $toStatus);
	exit;
}

if(isset($_GET["id"]) && isset($_GET["action"]) && $_GET["action"] == "detail"){
	$id = intval($_GET["id"]);
	if($id > 0)
		getDetailsEntryMovie($id);
	exit;
}

if(isset($_GET["offset"])){
	$offset = intval($_GET["offset"]);
	if($offset < 0)
		$offset = 0;
	sleep(1);
	getEntriesMovies($sql);
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
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />
	<title>KodiWebPortal <?php echo KODI_WEB_PORTAL_VERSION; ?></title>
	<link rel="stylesheet" href="style.css">
	<script type="text/javascript" src="https://code.jquery.com/jquery.min.js"></script>
	<script type="text/javascript" src="./script.js"></script>
</head>

<body>
<a href="index.php" alt="<?php echo LOGO_MOVIES_LABEL; ?>" title="<?php echo LOGO_MOVIES_LABEL; ?>"><img class="image-nav" src="./images/cinema-logo.png" style="margin-top:30px;" /></a>
<a href="tvshow.php" alt="<?php echo LOGO_TVSHOWS_LABEL; ?>" title="<?php echo LOGO_TVSHOWS_LABEL; ?>"><img class="image-nav image-nav-move" src="./images/tvshow-logo.png" style="margin-top:160px;" /></a>
<div id="opacity"></div>

<div id="search">

<form action="" method="GET">
<?php
	$stmt = $db->query("SELECT DISTINCT COUNT(*) FROM " . NAX_MOVIE_VIEW . ";");
	$count = $stmt->fetch();
	echo "<b>[ " . $count[0] . " " . MOVIES_LABEL . " ]</b> - ";
?>
	<label for="title"><?php echo TITLE_LABEL; ?></label>
	<input id="title" type="text" name="title" placeholder="Indiana Jones" value="<?php echo htmlentities(stripcslashes($title)); ?>" />

<?php 
	if(WATCHED_STATUS_FOR_ALL || (ENABLE_AUTHENTICATION && in_array($_SESSION['user'], $WATCH_STATUS_FOR_USERS))) {
		echo '<label for="watched">'.STATUS_WATCHED_LABEL.' </label>';
		echo '<select id="watched" name="watched">';
		echo '        <option value="*" '; if($watched === "*") echo 'selected'; echo '>*</option>';
		echo '        <option value="YES" '; if($watched === "YES") echo 'selected'; echo '>'.YES.'</option>';
		echo '        <option value="NO" '; if($watched === "NO") echo 'selected'; echo '>'.NO.'</option>';
		echo '</select>';
	}
?>

	<label for="genre"><?php echo GENRE_LABEL; ?></label>
	<select id="genre" name="genre">
		<option value="">*</option>
<?php
	$gs = getAllGenres("movies");
	foreach($gs as $g){
		if(stripcslashes($genre) == $g)
			echo "<option value='" . $g . "' selected>" . $g . "</option>";
		else
			echo "<option value='" . $g . "'>" . $g . "</option>";
	}
?>
	</select>
	
	<label for="year"><?php echo YEAR_LABEL; ?></label>
	<select id="year" name="year">
		<option value="">*</option>
<?php
	$ys = getAllYears();
	foreach($ys as $y){
		if($year == $y)
			echo "<option value='" . $y . "' selected>" . $y . "</option>";
		else
			echo "<option value='" . $y . "'>" . $y . "</option>";
	}
?>
	</select>
	
	<label for="realisator"><?php echo REALISATOR_LABEL; ?></label>
	<input id="realisator" type="text" name="realisator" placeholder="Spielberg" value="<?php echo htmlentities(stripcslashes($realisator)); ?>" />

	<label for="nationality"><?php echo NATIONALITY_LABEL; ?></label>
	<select id="nationality" name="nationality">
		<option value="">*</option>
<?php
	$ns = getAllNationalities();
	foreach($ns as $n){
		if(stripcslashes($nationality) == $n)
			echo "<option value='" . $n . "' selected>" . $n . "</option>";
		else
			echo "<option value='" . $n . "'>" . $n . "</option>";
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
