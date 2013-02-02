<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_POST["visible_hostname"])){visible_hostname_save();exit;}
if(isset($_GET["visible-hostname-js"])){visible_hostname_js();exit;}
if(isset($_POST["reconfigure-squid"])){reconfigure_squid();exit;}
if(isset($_POST["restart-squid"])){restart_squid();exit;}
if(isset($_POST["reconf-squid"])){reconf_squid();exit;}
if(isset($_GET["filters-specific"])){filters_for_node();exit;}

page();

function visible_hostname_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidbee();
	$hostid=$_GET["hostid"];
	$nodeid=$_GET["nodeid"];	
	$visible_hostname=$tpl->javascript_parse_text("{visible_hostname}");
	
	$nodes_names=$squid->visible_hostname;
	if(isset($squid->nodes_names[$hostid])){
		$nodes_names=$squid->nodes_names[$hostid];
	}
	$t=time();
	$html="
	
	var x_nodnemae$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTab('main_squid_quicklinks_tabs{$nodeid}');
		}
		
	
		function nodnemae$t(){
			var node=prompt('$visible_hostname:','$nodes_names');
			if(!node){return;}
			var XHR = new XHRConnection();
			XHR.appendData('visible_hostname',node);
			XHR.appendData('hostid','$hostid');
			XHR.appendData('nodeid','$nodeid');
			XHR.sendAndLoad('$page', 'POST',x_nodnemae$t);
		}

	nodnemae$t();
	";
	echo $html;
}

function squid_booster_smp($encoded){
	$sock=new sockets();
	$array=unserialize(base64_decode($encoded));
	if(!is_array($array)){return;}
	if(count($array)==0){return;}
	$html[]="
			<div style='min-height:115px'>
			<table>
			<tr><td colspan=2 style='font-size:14px;font-weight:bold'>Cache(s) Booster</td></tr>
			";
	while (list ($proc, $pourc) = each ($array)){
		$html[]="<tr>
		<td width=1% nowrap style='font-size:13px;font-weight:bold'>Proc #$proc</td><td width=1% nowrap>". pourcentage($pourc)."</td></tr>";
	}
	$html[]="</table></div>";

	return RoundedLightGreen(@implode("\n", $html));
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$blackbox=new blackboxes($_GET["nodeid"]);
	$squid=new squidbee();
	$hostid=$_GET["hostid"];
	$t=time();
	$tpl=new templates();	
	$t=time();
	$tr=array();
	$DisableSquidSNMPMode=$blackbox->GET_SQUID_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	if($DisableSquidSNMPMode==0){
		$ini=new Bs_IniHandler();
		$ini->loadString($blackbox->SquidSMPStatus);
		
		while (list ($index, $line) = each ($ini->_params) ){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::$index -> DAEMON_STATUS_ROUND<br>\n";}
			$tr[]=DAEMON_STATUS_ROUND($index,$ini,null,1);
				
		}		
		
	}
	
	if(count($tr)>0){
		$tr[]=squid_booster_smp($blackbox->BoosterSMPStatus);
		$smpstatus=CompileTr3($tr);
	}
	
	$actions[]=Paragraphe32("reload_proxy_service", "reload_proxy_service_text", "SquidNodeReload$t()", "reload-32.png");
	$actions[]=Paragraphe32("restart_proxy_service", "restart_proxy_service_text", "SquidNodeRestart$t()", "service-restart-32.png");
	$actions[]=Paragraphe32("reconfigure_proxy_service", "reconfigure_proxy_service_text", "SquidNodeReconf$t()", "reconfigure-32.png");
	$actions[]=Paragraphe32("configuration_file", "display_generated_configuration_file", 
	"Loadjs('nodes.squid.conf.php?nodeid={$_GET["nodeid"]}')", "script-32.png");

	
	$action=CompileTr3($actions);
	$nodes_names=$squid->visible_hostname;
	if(isset($squid->nodes_names[$hostid])){
		$nodes_names=$squid->nodes_names[$hostid];
	}
	
		
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	
			<tr>
				<td class=legend style='font-size:14px'>{ipaddr}:</td>
				<td><strong style='font-size:14px'><strong style='font-size:14px'>{$blackbox->ipaddress}:{$blackbox->port}</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{APP_SQUID}:</td>
				<td><strong style='font-size:14px'><strong style='font-size:14px'>{$blackbox->squid_version}</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{visible_hostname}:</td>
				<td><strong style='font-size:14px'><strong style='font-size:14px'>
					<a href=\"javascript:Loadjs('$page?visible-hostname-js=yes&hostid=$hostid&nodeid={$_GET["nodeid"]}');\"
					 style='font-size:14px;text-decoration:underline;font-weight:bold'>$nodes_names</a>
					</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:14px'>{last_status}:</td>
				<td><strong style='font-size:14px'>$blackbox->laststatus</td>
			</tr>		
			</table>
		
		
		
	$smpstatus
	$action
	
	<script>
	var x_SquidNodeReload$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		document.getElementById('$t').innerHTML='';
		if(document.getElementById('main_squid_quicklinks_tabs{$_GET["nodeid"]}')){
			RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');
		}
	}	


	function SquidNodeReload$t(){
		var XHR = new XHRConnection();
		XHR.appendData('reconfigure-squid','$hostid');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SquidNodeReload$t);
	}
	
	function SquidNodeRestart$t(){
		var XHR = new XHRConnection();
		XHR.appendData('restart-squid','$hostid');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SquidNodeReload$t);
	}

	function SquidNodeReconf$t(){
		var XHR = new XHRConnection();
		XHR.appendData('reconf-squid','$hostid');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SquidNodeReload$t);	
	
	}
	
	LoadAjax('$t-filters','$page?filters-specific=yes&hostid=$hostid');
	
	</script>
	
	";
	

	
	
	
echo $tpl->_ENGINE_parse_body($html);
}

function visible_hostname_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidbee();
	$hostid=$_POST["hostid"];
	$nodeid=$_POST["nodeid"];	
	$visible_hostname=$_POST["visible_hostname"];
	$squid->nodes_names[$hostid]=$visible_hostname;
	$squid->SaveToLdap(true);
	$q=new blackboxes($hostid);
	$q->reconfigure_squid();
}

function reconfigure_squid(){
	$tpl=new templates();
	$hostid=$_POST["reconfigure-squid"];
	$q=new blackboxes($hostid);
	if(!$q->reconfigure_squid()){$tpl->javascript_parse_text("{failed}: $q->ipaddress");return;}
	echo $tpl->javascript_parse_text("{success}: $q->ipaddress");
	
}

function restart_squid(){
	$tpl=new templates();
	$hostid=$_POST["restart-squid"];
	$q=new blackboxes($hostid);
	if(!$q->restart_squid()){$tpl->javascript_parse_text("{failed}: $q->ipaddress");return;}
	echo $tpl->javascript_parse_text("{success}: $q->ipaddress");	
	
}
function reconf_squid(){
	$tpl=new templates();
	$hostid=$_POST["reconf-squid"];
	$q=new blackboxes($hostid);
	if(!$q->reconfigure_squid()){$tpl->javascript_parse_text("{failed}: $q->ipaddress");return;}
	echo $tpl->javascript_parse_text("{success}: $q->ipaddress");	
}



