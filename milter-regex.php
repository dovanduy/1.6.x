<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	
	$user=new usersMenus();
	if(!isset($_GET["hostname"])){
		if(!$user->AsPostfixAdministrator){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
	}else{
		if(!PostFixMultiVerifyRights()){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
		
	}
	if(isset($_POST["EnableMilterRegex"])){EnableMilterRegex();exit;}
	if(isset($_GET["config"])){config();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}

tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["config"]='{parameters}';
	$array["acls"]='{acls}';
	$font="style='font-size:24px'";

	$master=urlencode(base64_encode("master"));
	$suffix="&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="acls"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"milter-regex.acls.php?$suffix\"><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"$page?$num=yes&hostname=master&ou=$master\"><span>$ligne</span></a></li>\n");


	}


	echo build_artica_tabs($html, "main_config_milter_regex",1490);

}

function config(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();

	
	
	
	$t=time();

	$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));


	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:350px'>
			<div style='width:98%' class=form>
				<div style='font-size:30px;margin-bottom:20px'>{services_status}</div>
				<div id='milterregex-status'></div>
				<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('squidrdp-status','$page?services-status=yes')")."</div>
			</div>
		
		
		</td>
		<td valign='top' style='padding-left:15px'>
	<div style='font-size:60px;margin-bottom:15px'>{milter_regex}</div>	
	<hr>	
	<div id='test-$t'></div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
		<table>
		<tr>
		<td colspan=3>". Paragraphe_switch_img("{milter_regex}", 
				"{milter_regex_explain}","EnableMilterRegex",
				"$EnableMilterRegex",null,1050)."</td>
		</tr>
		<tr>
			<td colspan=3  align='right'><hr>". button("{apply}", "Save$t()","40px")."</td>
		</tr>
</table>
</div>
</td>
</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('postfix.milters.progress.php');
	RefreshTab('transmission_daemon_tabs');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableMilterRegex',document.getElementById('EnableMilterRegex').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(){
	LoadAjax('milterregex-status','$page?services-status=yes');
	
}
Check$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function EnableMilterRegex(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMilterRegex", $_POST["EnableMilterRegex"]);
	
}
function services_status(){

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('milter-regex.php?status=yes')));
	$APP_RDPPROXY=DAEMON_STATUS_ROUND("milter_regex",$ini,null,0);



	$tr[]=$APP_RDPPROXY;

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $tr));

}