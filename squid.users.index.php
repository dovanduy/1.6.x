<?php
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){error_log("uid `{$_SESSION["uid"]}` no set -> register:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);registerSession();}
if(!isset($_SESSION["uid"])){error_log("uid `{$_SESSION["uid"]}`  null :".__FUNCTION__." in " . __FILE__. " line ".basename(__FILE__));header('location:squid.users.logon.php');die();}
error_log("uid `{$_SESSION["uid"]}` ok, registered:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');

if(isset($_POST["GetMyTitle"])){GetMyTitle();exit;}
	

page();


function page(){
	
	
$html="



<script>
	LoadAjax('middle','squid.users.quicklinks.php');
	ChangeHTMLTitleUserSquidPerform();
	RemoveSearchEnginePerform();
</script>


";	
	
	
$tpl=new template_users($title,$html,$_SESSION,0,0,0,$cfg);
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$html=$tpl->web_page;
$html=str_replace("admin.tabs.php", "squid.users.admin.tabs.php", $html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	
return;		
	
}

function registerSession(){
	if(!isset($_GET["phpsess"])){return null;}
	
	$array=unserialize(base64_decode($_GET["phpsess"]));
	if(!is_array($array)){return null;}
	while (list ($key, $value) = each ($array) ){		
		error_log("SET $key = $value ".__FUNCTION__."() in " . basename(__FILE__). " line ".__LINE__);
		$_SESSION[$key]=$value;
	}
	
	
	
	
}
function GetMyTitle(){
	error_log("SET email = {$_SESSION["email"]} ".__FUNCTION__."() in " . basename(__FILE__). " line ".__LINE__);
	echo $_SESSION["email"];
}
