<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."')";
	die();
}
if(isset($_POST["EnableSystemOptimize"])){EnableSystemOptimize();exit;}
if(isset($_POST["EnableIntelCeleron"])){EnableIntelCeleron();exit;}
if(isset($_GET["popup"])){popup();exit();}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{low_performance}");
	echo "YahooWin4(900,'$page?popup=yes','$title');";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$processor_type=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/processor_type"));
	$BOGOMIPS=intval($processor_type["BOGOMIPS"]);
	$MODEL=$processor_type["MODEL"];
	$text=$tpl->_ENGINE_parse_body("{low_performance_text_explain}");
	$text=str_replace("%p", $MODEL, $text);
	$text=str_replace("%m", $BOGOMIPS, $text);
	$t=time();
	$html="<div style='width:98%' class=form>
	<div class=explain style='font-size:22px'>$text</div>

	<center style='margin:50px'>". button("{optimize}","Save2$t()",42)."</center>
	
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('system.optimize.progress.php');
}	
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSystemOptimize',document.getElementById('EnableSystemOptimize').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

var xSave2$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	YahooWin4Hide();
	Loadjs('system.optimize.celeron.php');
}

function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableIntelCeleron',1);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

</script>			
</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableSystemOptimize(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSystemOptimize", $_POST["EnableSystemOptimize"]);
	
	
}

function EnableIntelCeleron(){
	$sock=new sockets();
	$sock->SET_INFO("EnableIntelCeleron", $_POST["EnableIntelCeleron"]);
}

