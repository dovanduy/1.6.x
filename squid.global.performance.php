<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["force"])){$GLOBALS["FORCE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<script>alert('$alert');</script>";
	die();	
}

if(isset($_GET["explain"])){explain_this();exit;}
if(isset($_GET["performance"])){performance();exit;}
if(isset($_POST["SquidPerformance"])){SquidPerformance();exit;}

tabs();
function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_blackbox();
	$tpl=new templates();
	$language=$tpl->language;
	$array["performance"]="{global_performance}";
	$array["squid-memory"]='{memory}';
	$array["peristent_cnx"]="{persistent_connections}";
	$array["performance-reports"]="{performance_reports}";
	$array["logger"]="{artica_logger}";
	
	
	
	$fontsize="18";
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="logger"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.php?logfile-daemon-popup=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="peristent_cnx"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.persistent.connections.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;			
			
		}
		
		if($num=="squid-memory"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
			<a href=\"squid.memory.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
				
		}
		
		if($num=="performance-reports"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.perfs.reports.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
					
	}
		echo build_artica_tabs($html, "squid_main_performance",1100)."";


}


function performance(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->SET_INFO("AsSeenPerformanceFeature",1);
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$t=time();
	
	$array[0]="{full_features}";
	$array[1]="{no_categories}";
	$array[2]="{no_statistics}";
	$array[3]="{minimal_features}";
	$html="<div style='font-size:26px'>{global_performance}</div>
	<div class=text-info style='font-size:18px'>{artica_squid_performance_text}</div>	
	<div class=text-info style='font-size:18px' id='explain-$t'></div>	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='font-size:22px'>{performance_level}:</td>
		<td>". Field_array_Hash($array, "SquidPerformance-$t",$SquidPerformance,"Choose$t()",null,0,"font-size:22px")."</td>
	</tr>
	<tr><td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",32)."</td>
	</tr>
	</table>		
	</div>		
	<script>
		var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
		RefreshTab('squid_main_performance');
	
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidPerformance',document.getElementById('SquidPerformance-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	
		function Choose$t(){
			var choosed=document.getElementById('SquidPerformance-$t').value;
			LoadAjax('explain-$t','$page?explain='+choosed);
		}
			
		Choose$t();	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function explain_this(){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{SquidPerformance_{$_GET["explain"]}}");
	
}

function SquidPerformance(){
	$sock=new sockets();
	$sock->SET_INFO("SquidPerformance", $_POST["SquidPerformance"]);
	$sock->getFrameWork("system.php?restart-all-extrn-scvcs=yes");
	$sock->getFrameWork("cmd.php?reload-ufdbguard=yes");
	$sock->getFrameWork("cmd.php?reload-squidguard=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
}

