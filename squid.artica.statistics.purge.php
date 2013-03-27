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
	if(isset($_GET["title"])){tables_title();exit;}
	if(isset($_GET["schedules"])){schedules();exit;}
	if(isset($_POST["ArticaProxyStatisticsBackupFolder"])){Save();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{purge_statistics_database}");
	$html="YahooWin4('821','$page?popup=yes','$title');";
	echo $html;	
	
}

function tables_title(){
	$q=new mysql_squid_builder();
	$array=$q->COUNT_ALL_TABLES();
	if(!$q->ok){
		if($q->mysql_error==null){$q->mysql_error="MySQL error...";}
		$ff="<div style='font-size:18px'>$q->mysql_error</div>";
	}else{
		$ff="<div style='font-size:18px;margin-bottom:10px'>{$array[0]} Tables (".FormatBytes($array[1]/1024).")</div>";
	}
	echo "
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTableTitle{$_GET["t"]}()")."</div>		
	$ff";
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if($users->CORP_LICENSE){$LICENSE=1;}else{$LICENSE=0;}
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	$q=new mysql_squid_builder();
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if(!$users->CORP_LICENSE){$ArticaProxyStatisticsBackupDays=5;}
	
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	
	
	if($EnableSquidRemoteMySQL==1){
		$EnableSquidRemoteMySQL_text="<div style='font-size:16px;color:#BA1010' class=form>{EnableSquidRemoteMySQL_text}</div>";
	}
	
	$t=time();
	$html="
	$EnableSquidRemoteMySQL_text
	<div id='$t'></div>
	<div id='title-$t'></div>
	<div style='font-size:14px;' class=explain>{purge_statistics_database_explain2}</div>	

	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{backup_folder}:</td>
			<td>". Field_text("ArticaProxyStatisticsBackupFolder-$t",$ArticaProxyStatisticsBackupFolder,"font-size:16px;width:350px")."</td>
			<td width=1%>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=ArticaProxyStatisticsBackupFolder-$t')",12)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{max_days}:</td>
			<td>". Field_text("ArticaProxyStatisticsBackupDays-$t",$ArticaProxyStatisticsBackupDays,"font-size:16px;width:90px")."</td>
			<td>&nbsp;</td>
		</tr>		
		<tr>
			<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
		</tr>
		<tr>
		<td colspan=3 align='left'>
			<table style='width:50%'>
			<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99% nowrap>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:YahooWin3('650','squid.databases.schedules.php?AddNewSchedule-popup=yes&ID=0&t=$t&ForceType=47&YahooWin=3&jsback=ReloadSchedules$t','$new_schedule');\"
					 		style=\"font-size:14px;text-decoration:underline\">$new_schedule</a>
						</td>
					</tr>	
			</table>
		</td>
	</tr>	
	</table>
	
	<div id='schedules-$t'></div>
	
<script>
	var x_Save$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('$t').innerHTML='';
	}

	function Save$t(){
			var LICENSE=$LICENSE;
			var XHR = new XHRConnection();	
			XHR.appendData('ArticaProxyStatisticsBackupFolder',document.getElementById('ArticaProxyStatisticsBackupFolder-$t').value);
			XHR.appendData('ArticaProxyStatisticsBackupDays',document.getElementById('ArticaProxyStatisticsBackupDays-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
			}
			
	function ReloadSchedules$t(){
		var EnableSquidRemoteMySQL=$EnableSquidRemoteMySQL;
		if(EnableSquidRemoteMySQL==1){
			document.getElementById('ArticaProxyStatisticsBackupFolder-$t').disabled=true;
			document.getElementById('ArticaProxyStatisticsBackupDays-$t').disabled=true;
			return;
		}
		LoadAjax('schedules-$t','$page?schedules=yes');
		}
		
	function RefreshTableTitle$t(){
		LoadAjaxTiny('title-$t','$page?title=yes&t=$t');
	}
		RefreshTableTitle$t();
		ReloadSchedules$t();
</script>											
	";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function schedules(){
	include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$task=new system_tasks();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_schedules WHERE TaskType='47'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$TimeDescription=$ligne["TimeDescription"];
		$TimeText=$task->PatternToHuman($ligne["TimeText"],true);
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){$TimeDescription=$TimeText;$TimeText=null;}
		$ID=$ligne["ID"];
		$tr[]="
		<tr>
		<td width=1%><img src=\"img/arrow-right-24.png\"></td>
		<td width=99% nowrap>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.databases.schedules.php?AddNewSchedule-js=yes&ID=$ID&YahooWin=3');\" 
		style=\"font-size:16px;text-decoration:underline\">$TimeDescription</a>
		<div style='font-size:10px'><i>$TimeText</div></div>
		</td>
		<td width=1%>".imgtootltip("32-run.png","{run}","Loadjs('squid.databases.schedules.php?schedule-run-js=yes&ID=$ID');")."</td>
		</tr>
		";
	
	}
	
	$html=$html."
	<div style=\"font-size:18px;margin-top:10px\">{schedules}:</div>
			<table style=\"width:99%\" class=\"form\">".@implode("\n", $tr)."</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	if($users->CORP_LICENSE){
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays", $_POST["ArticaProxyStatisticsBackupDays"]);
	}else{
		echo $tpl->javascript_parse_text("{no_license_backup_max5}",1);
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays",5);
		
	}	
	$sock->SET_INFO("ArticaProxyStatisticsBackupFolder", $_POST["ArticaProxyStatisticsBackupFolder"]);
}
