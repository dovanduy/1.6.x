<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_POST["EnableL7Filter"])){EnableL7Filter();exit;}
if($_GET["main"]){main();exit;}
if(isset($_GET["status"])){status();exit;}
tabs();


function tabs(){

	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	

	if(!$users->l7FILTER_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{ERROR_SERVICE_NOT_INSTALLED}</center>");
		return;
	}
	
	
	$fontsize=18;

	$array["main"]="{application_detection}";
	$array["applications"]="{applications}";
	

	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		if($num=="applications"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.l7filter.apps.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;

		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}



	$html=build_artica_tabs($html,'main_l7filter_center',1020)."<script>LeftDesign('qos-256-white.png');</script>";

	echo $html;
}



function main(){
	$sock=new sockets();
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$sock=new sockets();
	$ip_queue_maxlen=intval($sock->GET_INFO("ip_queue_maxlen"));
	if($ip_queue_maxlen==0){$ip_queue_maxlen=2048;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr><td style='width:280px;vertical-align:top'><div id='l7filter-status'></div></td>
	<td style='width:99%;vertical-align:top'>". 
			Paragraphe_switch_img("{EnableL7Filter}", 
			"{EnableL7Filter_explain}","EnableL7Filter",$EnableL7Filter,null,600)."
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:22px'>{ip_queue_maxlen}:</td>
				<td>". Field_text("ip_queue_maxlen",$ip_queue_maxlen,"font-size:22px;width:110px")."</td>
			</td>
			</table>		
					
					
	</td>
	</tr>
	</table>
		
			
			
	<div style='margin-top:50px;text-align:right'><hr>". button("{apply}","Save$t()",40)."</div>
	</div>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	Loadjs('system.services.cmd.php?APPNAME=APP_l7FILTER&action=restart&cmd=%2Fetc%2Finit.d%2Fl7filter&appcode=APP_l7FILTER');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableL7Filter',document.getElementById('EnableL7Filter').value);
	XHR.appendData('ip_queue_maxlen',document.getElementById('ip_queue_maxlen').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

LoadAjax('l7filter-status','$page?status=yes');
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}

function EnableL7Filter(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableL7Filter", $_POST["EnableL7Filter"]);
	$sock->SET_INFO("ip_queue_maxlen", $_POST["ip_queue_maxlen"]);
	
}

function status(){
	$tpl=new templates();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('system.php?l7filter-status=yes')));

	$html=DAEMON_STATUS_ROUND("APP_l7FILTER",$ini,null,0)."
	<div style='margin-top:15px;text-align:right'>".imgtootltip("refresh-32.png","{refresh}",
	"LoadAjax('l7filter-status','$page?status=yes');")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}
