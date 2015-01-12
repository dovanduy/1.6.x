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
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["DisableArticaProxyStatistics"])){Save();exit;}
	if(isset($_POST["EnableProxyLogHostnames"])){Save2();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{ARTICA_STATISTICS}");
	$html="YahooWin4('725','$page?popup=yes','$title');";	
	echo $html;
	}

function popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["params"]="{parameters}";
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	$id=time();
	
	echo build_artica_tabs($html, "artica_stats_tabs");
	
}

function params(){
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	if($SquidPerformance>1){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("{artica_statistics_disabled_see_performance}"));
		return;
	}
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	$CleanArticaSquidDatabases=$sock->GET_INFO("CleanArticaSquidDatabases");
	$EnableProxyLogHostnames=$sock->GET_INFO("EnableProxyLogHostnames");
	$MacResolvInterface=$sock->GET_INFO("MacResolvInterface");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($CleanArticaSquidDatabases)){$CleanArticaSquidDatabases=0;}
	if(!is_numeric($EnableProxyLogHostnames)){$EnableProxyLogHostnames=0;}
	
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	$array[null]="{none}";
	while (list ($eth, $line) = each ($interfaces) ){
		if($eth=="lo"){continue;}
		$ip=new system_nic($eth);
		$array[$eth]=$ip->IPADDR." (" .$ip->NICNAME .")";
	}
	
	$p=Paragraphe_switch_img("{DisableArticaProxyStatistics}", "{DisableArticaProxyStatistics_explain}","DisableArticaProxyStatistics",$DisableArticaProxyStatistics,null,600);
	$p1=Paragraphe_switch_img("{CleanArticaSquidDatabases}", "{CleanArticaSquidDatabases_explain}","CleanArticaSquidDatabases",$CleanArticaSquidDatabases,null,600);
	$p2=Paragraphe_switch_img("{EnableProxyLogHostnames}", "{EnableProxyLogHostnames_explain}","EnableProxyLogHostnames",$EnableProxyLogHostnames,null,600);
	
	
	if($EnableSquidRemoteMySQL==1){
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
		$error="<div style='font-size:16px' class=text-info>{remote_mysqlsquidserver_text}<br><strong>mysql://{$TuningParameters["mysqlserver"]}:{$TuningParameters["RemotePort"]}</strong></div>";
		$p=Paragraphe_switch_disable("{DisableArticaProxyStatistics}", "{DisableArticaProxyStatistics_explain}",null,600).
		"".Field_hidden("DisableArticaProxyStatistics", 0);
	}
	
	$html="
	<div id=$t></div>
	<div style='width:98%' class=form>
	$error
	<table>
	<tr>
		<td colspan=3>$p</td>
	</tr>
	<tr><td colspan=3><hr></td></tr>
	<tr>
		<td colspan=3>$p1</td>
	</tr>

	
	<tr>
		<td colspan=3 align='right'>". button("{apply}", "SaveStopArticaStats()",16)."</td>
	</tr>
	</table>
	</div>
	
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td colspan=3>$p2</td>
	</tr>
		<tr>
		<td class=legend style='font-size:16px'>{mac_resolv_interface}:</td>
		<td>". Field_array_Hash($array, "MacResolvInterface",$MacResolvInterface,null,null,0,"font-size:16px")."</td>
		<td width=1%>". help_icon("{mac_resolv_interface_help}")."</td>
	</tr>
	<tr>
		<td  align='right' colspan=3>". button("{apply}", "SaveOptions$t()",16)."</td>
	</tr>	
	</table>
	</div>
	<script>
	var x_SaveStopArticaStats= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('artica_stats_tabs');
		CacheOff();
		ConfigureYourserver();
	}

		
		
	function SaveStopArticaStats(){
			var XHR = new XHRConnection();	
			XHR.appendData('DisableArticaProxyStatistics',document.getElementById('DisableArticaProxyStatistics').value);
			XHR.appendData('CleanArticaSquidDatabases',document.getElementById('CleanArticaSquidDatabases').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveStopArticaStats);
			}
			
	var xSaveOptions$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};

	}			
			
	function SaveOptions$t(){
			var XHR = new XHRConnection();	
			XHR.appendData('EnableProxyLogHostnames',document.getElementById('EnableProxyLogHostnames').value);
			XHR.appendData('MacResolvInterface',document.getElementById('MacResolvInterface').value);
			XHR.sendAndLoad('$page', 'POST',xSaveOptions$t);
			}			
			
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableArticaProxyStatistics", $_POST["DisableArticaProxyStatistics"]);
	$sock->SET_INFO("CleanArticaSquidDatabases", $_POST["CleanArticaSquidDatabases"]);
	$sock->getFrameWork("squid.php?clean-mysql-stats=yes");
	$sock->getFrameWork('cmd.php?restart-artica-status=yes');
	
	
}
function Save2(){
	$sock=new sockets();
	$sock->SET_INFO("EnableProxyLogHostnames", $_POST["EnableProxyLogHostnames"]);
	$sock->SET_INFO("MacResolvInterface", $_POST["MacResolvInterface"]);
	
	
	$sock->getFrameWork("squid.php?squid-k-reconfigure=yes");
}