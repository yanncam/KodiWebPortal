<?php
require_once("./config.php");
require_once("./functions.php");
defineSecurityHeaders();
if(isAuthenticated()){ // Check authentication and authorization
	header("Location: index.php");
	exit;
}
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
	<title><?php echo AUTHENTICATION_PAGE_TITLE; ?></title>
	<script type="text/javascript" src="https://code.jquery.com/jquery.min.js"></script>
</head>
<body>
<div align="center">
	<img border="0" src="./images/kodi_logo.png" />
</div>
<br />

<style>
html {
	height: 100%;
}

body{
	background:url("./images/kodi_background.png") #F4F5F2 no-repeat center;
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
}

.error{
	color: red;
	font: 11px/normal sans-serif;
    font-weight: 600;
}

.success{
	color: green;
	font: 11px/normal sans-serif;
    font-weight: 600;
}
</style>

<script type="text/javascript">
	function login(user, passwd){
		passwd=encodeURIComponent(passwd)
		$.ajax({
			type: 'POST',
			url: 'index.php',
			contentType: 'application/x-www-form-urlencoded;charset=utf-8',
			dataType: 'text',
			data: "user="+user+"&pass="+passwd,
			success: function(data, textStatus, jqXHR){
				$("#results").empty();
				$("#results").append(data);
		    },
			beforeSend: function(){
				$("#results").empty();
				$("#results").append("<img src='./images/loading-login.gif' />");
		    }
		});
	}
</script>

<br />
<div id="loginBox">
	<div align="center" >
		<div id="results" style="height:20px"></div>
		<form name="login_form" onsubmit="login($('#loginField').val(), $('#passwordField').val());return false;">
			<br />
			<input id="loginField" type="text" name="user" size="32" maxlength="32" value="" placeholder="<?php echo AUTHENTICATION_USER_PLACEHOLDER; ?>" autocomplete="off" />
			<br /><br />
			<input id="passwordField" type="password" name="pass" size="32" maxlength="255" placeholder="<?php echo AUTHENTICATION_PASSWD_PLACEHOLDER; ?>" autocomplete="off" />
			<br /><br /><br />
			<input type="submit" class="button" value="<?php echo AUTHENTICATION_BUTTON; ?>" />
		</form>
	</div>
</div>

<script type="text/javascript" language="JavaScript">
	window.document.login_form.user.focus();
</script>

</body>
</html>
