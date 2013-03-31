<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	

if(!CheckRightsSyslog()){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["progress"])){progress();exit;}
js();

function CheckRightsSyslog(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator){return true;}
	if($usersmenus->AsSquidAdministrator){return true;}
	if($usersmenus->AsWebStatisticsAdministrator){return true;}
	if($usersmenus->AsDansGuardianAdministrator){return true;}
	return false;
}



function js(){
	$tpl=new templates();
	$filename=$_GET["filename"];
	$page=CurrentPageName();
	$t=time();
	$title=$tpl->javascript_parse_text("{restore}: $filename");
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{ask_squid_restore_log_from_source} - $filename -");
	$html="
		function Start$t(){
			if(!confirm('$ask')){return;}
			YahooWinBrowse('750','$page?popup=yes&filename=$filename','$title');
		
		}
			
	 Start$t();";
	echo $html;
}

function popup(){
	$filename=$_GET["filename"];	
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rotate-restore=$filename");
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td width=1%><div id='wait-$t' style='width:40px'></div></td>
		<td width=99%'><div id='progress-$t'></td>
	</tr>
	<td colspan=2><div id='text-$t' style='font-size:16px'>{PLEASE_WAIT_CALCULATING_STATISTICS}</div></td>
	</tr>
	</table>	
	<script>
	function Refresh$t(){
		if(!YahooWinBrowseOpen()){return;}
		LoadAjaxTiny('wait-$t','$page?progress=yes&t=$t&filename=$filename');
	}
	
	
		$('#progress-$t').progressbar({ value: 2 });
		Refresh$t();
	</script>	
			
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function progress(){
	$filename=$_GET["filename"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/$filename-restore.pr"));
	
	$PLEASE_WAIT_CALCULATING_STATISTICS=$tpl->javascript_parse_text("{PLEASE_WAIT_CALCULATING_STATISTICS}");
	
	if(is_array($array)){
		$pourc=$array["POURC"];
		if($pourc==0){$pourc=2;}
		$text=$tpl->javascript_parse_text("{$array["TEXT"]}");
		$text="document.getElementById('text-$t').innerHTML='$text';";
		if(is_numeric($pourc)){
			if($pourc>2){
				$progress="$('#progress-$t').progressbar({ value: $pourc});";
			}
		}
				
	}
	
	echo "
	<script>		
	
	$text;
	$progress;
	setTimeout(\"Refresh$t()\",3000);
	</script>
	";
	
	
}
