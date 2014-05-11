<?php
if(isset($_GET["content"])){content();exit;}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$_SESSION=array();
unset($_SESSION["uid"]);
unset($_SESSION["privileges"]);
unset($_SESSION["qaliases"]);
unset($_SERVER['PHP_AUTH_USER']);
unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
unset($_SESSION['smartsieve']['authz']);
unset($_SESSION["passwd"]);
unset($_SESSION["LANG_FILES"]);
unset($_SESSION["TRANSLATE"]);
unset($_SESSION["translation"]);
unset($_SESSION["__CLASS-USER-MENUS"]);
unset($_SESSION["CORP"]);
unset($_SESSION["dynamic_acls_auth"]);
unset($_SESSION["SQUID_DYNAMIC_ACLS"]);

$_COOKIE["username"]="";
$_COOKIE["password"]="";


while (list ($num, $ligne) = each ($_SESSION) ){
	unset($_SESSION[$num]);
}
unset($_SESSION);
session_destroy();

$page=CurrentPageName();
$tplfile="ressources/templates/endusers/index.html";
if(!is_file($tplfile)){echo "$tplfile no such file";die();}
$content=@file_get_contents($tplfile);

$content=str_replace("{SCRIPT}", "<script>MyHref('/miniadm.logon.php');</script>", $content);
echo $content;



