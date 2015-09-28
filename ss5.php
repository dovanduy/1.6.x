<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["services-ss5-status"])){status();exit;}
	if(isset($_POST["EnableSS5"])){EnableSS5();exit;}

tabs();
function tabs(){
	$tpl=new templates();
	$array["popup"]='{parameters}';
	//$array["plugins"]='{squid_plugins}';

	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	$style="style='font-size:22px'";
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="plugins"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.client-plugins.php?popup=yes\" $style><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" $style><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "ss5_main");



}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$squid=new squidbee();
	$EnableSS5=intval($sock->GET_INFO("EnableSS5"));
	$EnableSS5P=Paragraphe_switch_img("{EnableSS5}","{APP_SS5_ABOUT}","EnableSS5",$EnableSS5,null,600);


$html="
	<div style='font-size:32px;margin-bottom:30px'>{APP_SS5} - Under construction </div>
	<div style=width:98% class=form>
	<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:450px'><div id='services-ss5-status'></div></td>
	<td style='vertical-align:top;width:600px'>
	$EnableSS5P
	<hr>
	<div style='text-align:right;margin-bottom:50px'>". button("{apply}", "Save$t()",26)."</div>
	</td>
	</tr>
	</table>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('ss5.progress.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSS5', document.getElementById('EnableSS5').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	LoadAjax('services-ss5-status','$page?services-ss5-status=yes',false);
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function EnableSS5(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSS5", $_POST["EnableSS5"]);
	
}

function status(){
	
}