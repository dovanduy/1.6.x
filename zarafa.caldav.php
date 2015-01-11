<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	
	
	if(isset($_POST["ZarafaiCalPort"])){ZarafaiCalPortSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{APP_ZARAFA_ICAL}");
	echo "YahooWin3('482','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafaiCalEnable=$sock->GET_INFO("ZarafaiCalEnable");
	$ZarafaiCalPort=$sock->GET_INFO('ZarafaiCalPort');	
	if(!is_numeric($ZarafaiCalPort)){$ZarafaiCalPort=8088;}
	$ZarafaiCalBind=$sock->GET_INFO("ZarafaiCalBind");
	if(strlen(trim($ZarafaiCalBind))<4){$ZarafaiCalBind="0.0.0.0";}
	$t=time();
$net=new networking();

$nets=$net->ALL_IPS_GET_ARRAY();
$nets["0.0.0.0"]="{all}";

$netfield=Field_array_Hash($nets,"ZarafaiCalBind-$t",$ZarafaiCalBind,"style:font-size:16px;padding:3px");

	$html="
	<div id='div-$t'></div>
	<div class=text-info style='font-size:16px'>{ZARAFA_CALDAV_EXPLAIN}</div>
		<table style='width:99%' class=form>
		
			<tr>
				<td class=legend style='font-size:16px'>{enable}:</td>
				<td>". Field_checkbox("ZarafaiCalEnable-$t",1,$ZarafaiCalEnable,"CheckZarafaiCal()")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:16px'>{listen_addr}:</td>
				<td>$netfield</td>
			</tr>				
			<tr>
				<td class=legend style='font-size:16px'>{listen_port}:</td>
				<td>". Field_text("ZarafaiCalPort-$t",$ZarafaiCalPort,"font-size:16px;padding:3px;width:90px")."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><hr>". button("{apply}","SaveZarafaIcal$t()","16px")."</td>
			</tr>		
		</table>
	<script>
	var x_SaveZarafaIcal$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	}	
		
	
	function SaveZarafaIcal$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('ZarafaiCalEnable-$t').checked){XHR.appendData('ZarafaiCalEnable',1);}else{XHR.appendData('ZarafaiCalEnable',0);}	
		XHR.appendData('ZarafaiCalPort',document.getElementById('ZarafaiCalPort-$t').value);
		XHR.appendData('ZarafaiCalBind',document.getElementById('ZarafaiCalBind-$t').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveZarafaIcal$t);
		}
		
	function CheckZarafaiCal(){
		document.getElementById('ZarafaiCalBind-$t').disabled=true;
		document.getElementById('ZarafaiCalPort-$t').disabled=true;
		if(document.getElementById('ZarafaiCalEnable-$t').checked){
			document.getElementById('ZarafaiCalBind-$t').disabled=false;
			document.getElementById('ZarafaiCalPort-$t').disabled=false;		
		}
	}
	
	CheckZarafaiCal();
		
		
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaiCalPortSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaiCalEnable",trim($_POST["ZarafaiCalEnable"]));
	$sock->SET_INFO("ZarafaiCalPort",trim($_POST["ZarafaiCalPort"]));
	$sock->SET_INFO("ZarafaiCalBind",trim($_POST["ZarafaiCalBind"]));
	$sock->getFrameWork("zarafa.php?restart-ical=yes"); // restart zarafa-ical
	
}

