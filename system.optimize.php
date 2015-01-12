<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_POST["EnableSystemOptimize"])){EnableSystemOptimize();exit;}
if(isset($_GET["popup"])){popup();exit();}

tabs();

function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{system_optimization}';
	$fontsize=22;
	while (list ($num, $ligne) = each ($array) ){
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	echo build_artica_tabs($tab, "main_system_optimize",990)."<script>LeftDesign('optimize-256.png');</script>";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableSystemOptimize=intval($sock->GET_INFO("EnableSystemOptimize"));
	$t=time();
	$html="<div style='width:98%' class=form>
			
	". Paragraphe_switch_img("{enable_system_optimization}", 
			"{enable_system_optimization_text}","EnableSystemOptimize",$EnableSystemOptimize,null,880)."
	<div style='margin-top:25px;text-align:right'>". button("{apply}","Save$t()",40)."</div>
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
</script>			
</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableSystemOptimize(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSystemOptimize", $_POST["EnableSystemOptimize"]);
	
	
}

