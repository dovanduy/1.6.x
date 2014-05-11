<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){die('NO UID');}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
if($_SESSION["uid"]<>-100){if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-left"])){webstats_left();exit;}
if(isset($_GET["settings-retention"])){settings_retention();exit;}
if(isset($_POST["ArticaProxyStatisticsBackupFolder"])){settings_retention_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tabs2"])){tabs2();exit;}
if(isset($_GET["tools"])){tools();exit;}
if(isset($_GET["test-nas-js"])){test_nas_js();exit;}
if(isset($_GET["test-nas-popup"])){test_nas_popup();exit;}

main_page();

function main_page(){
	//annee=2012&mois=9&jour=22
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}')</script>", $content);
	echo $content;	
}

function tabs(){
	$sock=new sockets();
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	if(!is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	$users=new usersMenus();
	$page=CurrentPageName();
	$boot=new boostrap_form();
	if($ProxyUseArticaDB==1){
		$array["{mysql_statistics_engine}"]="miniadm.proxy.mysql.database.php?tabs=yes&title=yes";
	}
	$array["{database_maintenance}"]="$page?tabs2=yes";
	$array["{APP_ARTICADB}"]="miniadm.proxy.category.database.php?tabs=yes&title=yes";	
	$array["{tools}"]="$page?tools=yes";
	$array["{source_logs}"]="miniadm.webstats.logrotate.php";
	echo $boot->build_tab($array);
}

function tabs2(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$array["{retention_time}"]="$page?settings-retention=yes";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);	
	
	
}
function settings_retention(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if($users->CORP_LICENSE){$LICENSE=1;}else{$LICENSE=0;}
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	$ArticaProxyStatisticsBackHourTables=$sock->GET_INFO("ArticaProxyStatisticsBackHourTables");
	
	if(!is_numeric($ArticaProxyStatisticsBackHourTables)){$ArticaProxyStatisticsBackHourTables=1;}
	
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	$q=new mysql_squid_builder();
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
		$ArticaProxyStatisticsBackupDays=5;}
		$t=time();
		$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
		$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
		if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}

		if($EnableSquidRemoteMySQL==1){
			$EnableSquidRemoteMySQL_text="{EnableSquidRemoteMySQL_text}";
		}

		$lock=false;
		$boot=new boostrap_form();

		$boot->set_formdescription($EnableSquidRemoteMySQL_text."<br>{purge_statistics_database_explain2}");
		
		$boot->set_checkbox("ArticaProxyStatisticsBackHourTables", "{backup_hourly_tables}", $ArticaProxyStatisticsBackHourTables,array("TOOLTIP"=>"{backup_hourly_tables_explain}"));
		$boot->set_field("ArticaProxyStatisticsBackupFolder", "{backup_folder}", $ArticaProxyStatisticsBackupFolder,array("BROWSE"=>true));
		$boot->set_field("ArticaProxyStatisticsBackupDays", "{max_days}", $ArticaProxyStatisticsBackupDays);
		
		$BackupSquidStatsUseNas=$sock->GET_INFO("BackupSquidStatsUseNas");
		$BackupSquidStatsNASIpaddr=$sock->GET_INFO("BackupSquidStatsNASIpaddr");
		$BackupSquidStatsNASFolder=$sock->GET_INFO("BackupSquidStatsNASFolder");
		$BackupSquidStatsNASUser=$sock->GET_INFO("BackupSquidStatsNASUser");
		$BackupSquidStatsNASPassword=$sock->GET_INFO("BackupSquidStatsNASPassword");
		$BackupSquidStatsNASRetry=$sock->GET_INFO("BackupSquidStatsNASRetry");
		if(!is_numeric($BackupSquidStatsUseNas)){$BackupSquidStatsUseNas=0;}
		if(!is_numeric($BackupSquidStatsNASRetry)){$BackupSquidStatsNASRetry=0;}
		
		$boot->set_spacertitle("{NAS_storage}");
		$boot->set_checkbox("BackupSquidStatsUseNas", "{use_remote_nas}", $BackupSquidStatsUseNas,
				array("TOOLTIP"=>"{BackupSquidStatsUseNas_explain}",
						"LINK"=>"BackupSquidStatsNASIpaddr,BackupSquidStatsNASFolder,BackupSquidStatsNASUser,BackupSquidStatsNASPassword"
			
				));
		$boot->set_field("BackupSquidStatsNASIpaddr", "{hostname}", $BackupSquidStatsNASIpaddr);
		$boot->set_field("BackupSquidStatsNASFolder", "{shared_folder}", $BackupSquidStatsNASFolder,array("ENCODE"=>true));
		$boot->set_field("BackupSquidStatsNASUser","{username}", $BackupSquidStatsNASUser,array("ENCODE"=>true));
		$boot->set_fieldpassword("BackupSquidStatsNASPassword","{password}", $BackupSquidStatsNASPassword,array("ENCODE"=>true));
		$boot->set_checkbox("BackupSquidStatsNASRetry", "{retry}", $BackupSquidStatsNASRetry,array("TOOLTIP"=>"{BackupSquidLogsNASRetry_explain}"));
		
		$boot->set_button("{apply}");
		$boot->set_formtitle("{purge_statistics_database}");
		if(!$users->CORP_LICENSE){$boot->set_form_locked();$lock=true;}
		if($EnableSquidRemoteMySQL==1){$boot->set_form_locked();$lock=true;}
		$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
		if(!$lock){
			$boot->set_Newbutton("{new_schedule}", "YahooWin3('650','squid.databases.schedules.php?AddNewSchedule-popup=yes&ID=0&t=$t&ForceType=47&YahooWin=3&jsback=ReloadSchedules$t','$new_schedule')");
			$ReloadSchedules="ReloadSchedules$t()";
		}

		$boot->set_Newbutton("{test_connection}", "Loadjs('$page?test-nas-js=yes')");
		$form=$boot->Compile();

		$html="

		<div id='title-$t'></div>
		$error
		$form
		<div id='schedules-$t'></div>

		<script>
		function ReloadSchedules$t(){
		LoadAjax('schedules-$t','squid.artica.statistics.purge.php?schedules=yes');
}

function RefreshTableTitle$t(){
LoadAjaxTiny('title-$t','squid.artica.statistics.purge.php?title=yes&t=$t');
}
RefreshTableTitle$t();
$ReloadSchedules;
</script>

";

echo $tpl->_ENGINE_parse_body($html);
}

function test_nas_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{test_connection}");
	echo "YahooWin2('650','$page?test-nas-popup=yes','$title');";
}

function test_nas_popup(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?squidstats-test-nas=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>".@implode("\n", $datas)."</textarea>";
}

function settings_retention_save(){
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	
	
	
	if($users->CORP_LICENSE){
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays", $_POST["ArticaProxyStatisticsBackupDays"]);
		if(isset($_POST["BackupSquidStatsNASFolder"])){$_POST["BackupSquidStatsNASFolder"]=url_decode_special_tool($_POST["BackupSquidStatsNASFolder"]);}
		if(isset($_POST["BackupSquidStatsNASUser"])){$_POST["BackupSquidStatsNASUser"]=url_decode_special_tool($_POST["BackupSquidStatsNASUser"]);}
		if(isset($_POST["BackupSquidStatsNASPassword"])){$_POST["BackupSquidStatsNASPassword"]=url_decode_special_tool($_POST["BackupSquidStatsNASPassword"]);}
		
		$sock->SET_INFO("BackupSquidStatsUseNas", $_POST["BackupSquidStatsUseNas"]);
		$sock->SET_INFO("BackupSquidStatsNASIpaddr", $_POST["BackupSquidStatsNASIpaddr"]);
		$sock->SET_INFO("BackupSquidStatsNASFolder", $_POST["BackupSquidStatsNASFolder"]);
		$sock->SET_INFO("BackupSquidStatsNASUser", $_POST["BackupSquidStatsNASUser"]);
		$sock->SET_INFO("BackupSquidStatsNASPassword", $_POST["BackupSquidStatsNASPassword"]);
		$sock->SET_INFO("BackupSquidStatsNASRetry", $_POST["BackupSquidStatsNASRetry"]);
		$sock->SET_INFO("ArticaProxyStatisticsBackHourTables", $_POST["ArticaProxyStatisticsBackHourTables"]);
		
		
		
		
	}else{
		echo $tpl->javascript_parse_text("{no_license_backup_max5}",1);
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays",5);

	}
	$sock->SET_INFO("ArticaProxyStatisticsBackupFolder", $_POST["ArticaProxyStatisticsBackupFolder"]);

}

function content(){
	//if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;|&nbsp;
		<a href=\"miniadm.webstats-start.php\">{web_statistics}</a></div>
		<H1>{database_maintenance}</H1>
		<p>{database_maintenance_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?tabs=yes');
	</script>
	";
		
	$html=$tpl->_ENGINE_parse_body($html);
	
	echo $html;
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function tools(){
	//if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	

	
	$tr[]=table_heures_enretard();
	
	
	
	
	$tr[]=Paragraphe32('remote_mysql_server','remote_mysqlsquidserver_text'
			,"Loadjs('squid.remote-mysql.php',true)","artica-meta-32.png");
	
	
	
	
	
	$tr[]=Paragraphe32('restore_purged_statistics','restore_purged_statistics_explain'
			,"Loadjs('squid.artica.statistics.restore.php',true)","32-import.png");
	
	

	
	
	
	$tr[]=Paragraphe32('enable_disable_statistics','ARTICA_STATISTICS_TEXT'
			,"Loadjs('squid.artica.statistics.php',true)","statistics-32.png");	
	
	
	$html="
		<div class=BodyContent>". CompileTr4($tr)."</div>
			
	";
	
	
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}
function table_heures_enretard(){

	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$CurrentHourTable="squidhour_".date("YmdH");
	if($GLOBALS["VERBOSE"]){echo "Find hours tables...\n";}
	$tables=$q->LIST_TABLES_HOURS_TEMP();
	$c=0;
	$t=time();
	$CountDeTable=0;
	while (list ($table, $none) = each ($tables) ){
		if($table==$CurrentHourTable){if($GLOBALS["VERBOSE"]){echo "SKIP `$table`\n";}continue;}
		if(!preg_match("#squidhour_([0-9]+)#",$table,$re)){continue;}
		$hour=$re[1];
		$year=substr($hour,0,4);
		$month=substr($hour,4,2);
		$day=substr($hour,6,2);
		$tt[$table]=true;
	}
	if(!is_array($tt)){return null;}
	$CountDeTable=count($tt);
	if($CountDeTable>0){
		$sock=new sockets();
		$time=$sock->getFrameWork("squid.php?squidhour-repair-exec=yes");
		if(is_numeric($time)){
			$title=$tpl->javascript_parse_text("{squidhour_not_scanned} {running} {$time}Mn");
			$title=str_replace("%s", $CountDeTable, $title);
			$title=str_replace("%", $CountDeTable, $title);
			return Paragraphe32("noacco:$title ",'launch_squidhour_explain'
					,"blur()","wait-clock.gif");
		}
		$launch_squidhour_explain=$tpl->_ENGINE_parse_body("{launch_squidhour_explain}");
		$title=$tpl->javascript_parse_text("{squidhour_not_scanned}");
		$title=str_replace("%s", $CountDeTable, $title);
		$title=str_replace("%", $CountDeTable, $title);
		return Paragraphe32("noacco:$title","$launch_squidhour_explain"
				,"Loadjs('squid.statistics.central.php?squidhour-js=yes')","Database32-red.png");
	}

}
