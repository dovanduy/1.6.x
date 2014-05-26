<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["status"])){amavis_general_status();exit;}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}
	if(isset($_GET["compile-rules-js"])){compile_rules_js();exit;}
	if(isset($_GET["compile-rules-perform"])){	compile_rules_perform();exit;}
	
	amavis_popup();
	
function amavis_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();

	
	$enable_amavisdeamon_ask=$tpl->javascript_parse_text("{enable_amavisdeamon_ask}");		
	$disable_amavisdeamon_ask=$tpl->javascript_parse_text("{disable_amavisdeamon_ask}");	
	$EnableAmavisDaemon=trim($sock->GET_INFO("EnableAmavisDaemon",true));	
	if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}

	if($EnableAmavisDaemon==0){
		$EnableAmavisDaemonP=Paragraphe32("disabled", "amavis_is_currently_disabled_text", "EnablePopupAmavis()", 
				"warning32.png");
	}else{
		$EnableAmavisDaemonP=Paragraphe32("enabled", "amavis_is_currently_enabled_text", "DisablePopupAmavis()", 
		"ok32.png");
	}
	
	$prepost=Paragraphe("folder-equerre-64.png",'{postfix_hooking}','{postfix_hooking_text}',"javascript:Loadjs('$page?hooking-js=yes')",'postfix_hooking_text',210,100);
	$tr[]=$EnableAmavisDaemonP;
	$tr[]=Paragraphe32("watchdog", "watchdog_amavis_text", "Loadjs('amavis.daemon.watchdog.php')", "watchdog-32.png");
	$tr[]=Paragraphe32("postfix_hooking", "postfix_hooking_text", "Loadjs('amavis.index.php?hooking-js=yes')", "folder-equerre-32.png");
	$tr[]=Paragraphe32("amavis_wizard_rule_per_user", "amavis_wizard_rule_per_user_text", "Loadjs('amavis.wizard.users.php')", "32-wizard.png");
	$tr[]=Paragraphe32("reload_service", "reload_service_text", "AmavisCompileRules()", "service-restart-32.png");
	
	
	
	//https://192.168.1.213:9000/amavis.daemon.watchdog.php?_=1345459954124
	
	$table=CompileTr2($tr,"form");
		
	
	
	$html="<table style='width:100%'>
	<tr>
		<td width=1% valign='top'>
			<div id='status-$t'></div>
		</td>
		<td valign='top' style='padding-left:20px'>
			<div style='font-size:32px;margin:bottom:10px;text-align:right'>{APP_AMAVIS}</div>
			<div style='font-size:16px' class=explain>{AMAVIS_DEF}</div>
			<div id='explain-$t'>$table</div>
		</td>
	</tr>
	</table>
	<script>
	
	var x_EnablePopupAmavis= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_amavis');
	}	
	
		function EnablePopupAmavis(){
			if(confirm('$enable_amavisdeamon_ask')){
				var XHR = new XHRConnection();
				XHR.appendData('enable-amavis','yes');
				AnimateDiv('explain-$t');
				XHR.sendAndLoad('amavis.daemon.status.php', 'POST',x_EnablePopupAmavis);
			}
		}
		
		function DisablePopupAmavis(){
			if(confirm('$disable_amavisdeamon_ask')){
				var XHR = new XHRConnection();
				XHR.appendData('disable-amavis','yes');
				AnimateDiv('explain-$t');
				XHR.sendAndLoad('amavis.daemon.status.php', 'POST',x_EnablePopupAmavis);
			}
		}
	
	
	
		LoadAjax('status-$t','$page?status=yes&t=$t');
		
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
	



function amavis_general_status(){
	$t=$_GET["t"];
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?amavis-get-status=yes')));
	$status_amavis=DAEMON_STATUS_ROUND("AMAVISD",$ini,null);
	$status_amavismilter=DAEMON_STATUS_ROUND("AMAVISD_MILTER",$ini,null);
	$status_spamassassin=DAEMON_STATUS_ROUND("SPAMASSASSIN",$ini,null);
	$status_clamav=DAEMON_STATUS_ROUND("CLAMAV",$ini,null);
	$status_amavisdb=DAEMON_STATUS_ROUND("APP_AMAVISDB",$ini,null);

	$tpl=new templates();

	$html="
	<div style='width:98%' class=form>		
	<table style='width:99%'>
	<tr>
	<td>$status_amavis
	
	$status_amavismilter$status_spamassassin$status_clamav$status_amavisdb</td>
	</tr>
	</table>
	<div style='text-align:right'>". 
		imgtootltip("refresh-32.png","{refresh}","LoadAjax('status-$t','$page?status=yes&t=$t');")."</div></div>";
	


					echo $tpl->_ENGINE_parse_body($html);

	}
	function compile_rules_js(){
		$page=CurrentPageName();
		$tpl=new templates();
		$mailman=$tpl->_ENGINE_parse_body("{APP_AMAVIS}::{compile_rules}");
		$html="YahooWinBrowse('750','$page?compile-rules-perform=yes','$mailman::$cmd');";
		echo $html;
	
	}
	
	function compile_rules_perform(){
		$sock=new sockets();
		$datas=base64_decode($sock->getFrameWork("amavis.php?reload-tenir=yes&MyCURLTIMEOUT=300"));
		echo "
		<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$datas</textarea>
		<script>
		RefreshTab('main_config_amavis');
		</script>
	
		";
	
	}
	
	function service_cmds_js(){
		$page=CurrentPageName();
		$tpl=new templates();
		$cmd=$_GET["service-cmds"];
		$mailman=$tpl->_ENGINE_parse_body("{APP_AMAVIS}");
		$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$mailman::$cmd');";
		echo $html;
	}
	function service_cmds_perform(){
		$sock=new sockets();
		$page=CurrentPageName();
		$tpl=new templates();
		$datas=unserialize(base64_decode($sock->getFrameWork("amavis.php?service-cmds={$_GET["service-cmds-peform"]}")));
	
		$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";
	
		while (list ($key, $val) = each ($datas) ){
			if(trim($val)==null){continue;}
			if(trim($val=="->")){continue;}
			if(isset($alread[trim($val)])){continue;}
			$alread[trim($val)]=true;
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$val=htmlentities($val);
			$html=$html."
			<tr class=$classtr>
			<td width=99%><code style='font-size:12px'>$val</code></td>
			</tr>
			";
	
	
		}
	
		$html=$html."
		</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_amavis');
</script>
	
";
		echo $tpl->_ENGINE_parse_body($html);
	}
?>	