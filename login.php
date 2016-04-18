<?php
session_start();
include_once("config.php");
include_once("functions.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Pragma-directive" content="no-cache" />
	<meta http-equiv="Cache-Directive" content="no-cache" />
	<meta name="robots" content="noindex,follow" />
	<title>Authentication page</title>
</head>
<body>
<div align="center">
	<img border="0" src="./images/kodi_logo.png" />
</div>
<br />

<style>
body{
	background:url(./images/kodi_background.png) #F4F5F2 no-repeat center;
	background-size:cover;
}

input {
    position: relative;
    overflow: visible;
    display: inline-block;
    padding: 10px;
    border: 1px solid #CCC;
    margin: 0;
    text-decoration: none;
    text-align: center;
    text-shadow: none;
    font: 11px/normal sans-serif;
    font-weight: 600;
    color: #297189;
    white-space: nowrap;
    cursor: pointer;
    outline: none;
    background-color: #fff;
    background-clip: padding-box;
    /* border-radius: 3px; */
    zoom: 1;
}

input[type="submit"]:hover {
    color: #fff;
    background-color: #297189;
}

input[type="text"], input[type="password"] {
    font-weight: 400;
    cursor: auto;
    text-shadow: none;
    text-align: left;
}

#loginBox{
	background-color:rgba(255,255,255,0.85);
	padding-top:30px;
	padding-bottom:30px;
} 

#loginField{
	color:#777;
	border:1px solid #777;
	background:url(./images/user.png) 5px no-repeat #FFFFFF;
	padding-left:30px;
} 

#passwordField{
	color:#777;
	border:1px solid #777;
	background:url(./images/lock.png) 5px no-repeat #FFFFFF;
	padding-left:30px;
}</style>

<br />
<div id="loginBox">
	<div align="center" >
		<form name="login_form" method="post" action="">
			<br />
			<input id="loginField" type="text" name="user" size="32" maxlength="32" value="" placeholder="Username" autocomplete="off" />
			<br /><br />
			<input type="password" id="passwordField" name="pass" size="32" maxlength="1024" placeholder="Password" autocomplete="off" />
			<br /><br /><br />
			<input type="submit" class="button" value="Login" />
		</form>
	</div>
</div>

<script type="text/javascript" language="JavaScript">
	window.document.login_form.user.focus();
</script>

</body>
</html>
