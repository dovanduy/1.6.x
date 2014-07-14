<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
	if(isset($_GET["service-status"])){service_status();exit;}
	if(isset($_GET["main"])){main();exit;}

page();


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}");
	$html="YahooWin4('650','$page?service-cmds-popup=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$cmd=$_GET["service-cmds-popup"];
	$t=time();
	$html="
	<div id='pleasewait-$t''><center><div style='font-size:22px;margin:50px'>{please_wait}</div><img src='img/wait_verybig_mini_red.gif'></center></div>
	<div id='results-$t'></div>
	<script>LoadAjax('results-$t','$page?service-cmds-perform=$cmd&t=$t');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function service_cmds_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("ufdbguard.php?service-cmds={$_GET["service-cmds-perform"]}")));
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:14px'>".@implode("\n", $datas)."</textarea>
<script>
	 document.getElementById('pleasewait-$t').innerHTML='';
	var flexRT;
	if( document.getElementById('WebFilteringMainTableID') ){
		flexRT=document.getElementById('WebFilteringMainTableID').value;
		$('#flexRT'+flexRT).flexReload();
	}
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{web_filtering}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:390px'><div id='service-status-$t'></div></td>
		<td style='vertical-align:top;width:99%;padding-left:20px'>
				<center id='rules-toolbox' style='margin:bottom:15px'></center>
				<div id='main-status-$t'></div>
		</td>
	</tr>
	</table>
	</div>
	
	<script>
		LoadAjax('service-status-$t','$page?service-status=yes&t=$t');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function main(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$sock=new sockets();
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	$UFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	
	if($UseRemoteUfdbguardService==0){
		if($UFDB["UseRemoteUfdbguardService"]==1){
			$sock->SET_INFO("UseRemoteUfdbguardService", 1);
			$UseRemoteUfdbguardService=1;
		}
	}
	
	
	if($SquidGuardIPWeb==null){
		$SquidGuardIPWeb="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/exec.squidguard.php";
		$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/exec.squidguard.php";
		$sock->SET_INFO("SquidGuardIPWeb", $fulluri);
	}else{
		$fulluri=$SquidGuardIPWeb;
	}
	
	if(!$users->CORP_LICENSE){
		$MyVersion="{license_error}";
	}else{
		$q=new mysql_squid_builder();
		$MyVersion=trim($sock->getFrameWork("ufdbguard.php?articawebfilter-database-version=yes"));
		$MyVersion=$q->time_to_date($MyVersion,true);
	}
	
	
	if($UseRemoteUfdbguardService==0){
	
		$wizard="<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td colspan=2 align='center'>". button("{wizard_rule}", "Loadjs('dansguardian2.wizard.rule.php')",26)."
					<div style='font-size:14px;margin-top:15px'>{wizard_rule_ufdb_explain}</div>
					
					</td>
			
		</tr>";		
		

	
	}
	
	
	$t=time();
	$html="
		
		<table style='width:100%;margin-top:30px'>
		<tr>
			<td style='vertical-align:middle;font-size:18px' class=legend>{webpage_deny_url}:</td>
			<td style='vertical-align:middle;font-size:18px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squidguardweb.php');\" style='text-decoration:underline'>$fulluri</a></td>
		</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td style='vertical-align:middle;font-size:18px' class=legend>{artica_databases}:</td>
			<td style='vertical-align:middle;font-size:18px'>$MyVersion</td>
		</tr>

		$wizard
		</table>

		
		<script>
			
		</script>
		
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
}


function service_status(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?ufdb-ini-status=yes')));
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBGUARD_CLIENT",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);

	
	$status=@implode("\n", $tr);
	
	$html="
	<div id='rules-toolbox-left' style='margin-bottom:15px'></div>
	$status
	<script>
		LoadAjax('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');
		LoadAjaxTiny('rules-toolbox','dansguardian2.mainrules.php?rules-toolbox=yes');
		LoadAjax('main-status-$t','$page?main=yes&t=$t');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
