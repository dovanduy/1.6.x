<?php

session_start();
unset($_SESSION["uid"]);
unset($_SESSION["privileges"]);
unset($_SESSION["qaliases"]);
unset($_SERVER['PHP_AUTH_USER']);
unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
unset($_SESSION['smartsieve']['authz']);
unset($_SESSION["passwd"]);
unset($_SESSION["LANG_FILES"]);
unset($_SESSION["TRANSLATE"]);
unset($_SESSION["__CLASS-USER-MENUS"]);
unset($_SESSION["translation"]);
$_COOKIE["username"]="";
$_COOKIE["password"]="";


while (list ($num, $ligne) = each ($_SESSION) ){
	unset($_SESSION[$num]);
}

session_destroy();

echo "
<html>
<head>
<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=android.logon.php\"> 
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />

</head>
<body style='padding:100px;background-image:url($pattern)'>

	<center style='border:3px solid white;padding:5px'><a style='font-size:22px;font-family:arial,tahoma;font-weight:bold;color:white' href='logon.php'>
	Waiting please, redirecting to logon page</a>
	</center>

<center style='padding:15px;background-image:url($logo);background-repeat:no-repeat;background-position:center top;width:100%;height:768px'>

</body>
</html>
";




?>