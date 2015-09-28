<?php
if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__)."/framework/frame.class.inc");
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
	include_once(dirname(__FILE__)."/framework/class.settings.inc");
}}

include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.highcharts.inc');
include_once('ressources/class.rrd.inc');
$users=new usersMenus();
if(!$GLOBALS["AS_ROOT"]){if(!$users->AsAnAdministratorGeneric){die();}}
if(isset($_POST["DisableYoutubeLink"])){youtube_link_disable();exit;}
if(isset($_GET["youtube-link"])){youtube_link();exit;}
if(isset($_GET["remove-performance-alert"])){remove_performance_alert();exit;}
if(isset($_GET["all"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hour"])){hour();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week();exit;}
if(isset($_GET["month"])){month();exit;}
if(isset($_GET["year"])){year();exit;}
if(isset($_POST["LoadAvgClean"])){LoadAvgClean();exit;}
if(isset($_GET["apache-front-end-status"])){apache_status();exit;}

if(isset($_GET["squid-front-end-status"])){squid_frontend_status();exit;}

if(isset($_GET["cpustats"])){cpustats();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}

if(isset($_GET["graph4"])){graph4();exit;}


if(isset($_GET["graph6"])){graph6();exit;}




if($GLOBALS["AS_ROOT"]){@mkdir("/usr/share/artica-postfix/ressources/web/cache1",0755,true);}

if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
MySqlSyslog();
ZarafaWebAccess_wizard();
License();
if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
injectSquid();
if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
PageDeGarde();
if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}

exit;


function SquidOneCPU(){
	
	
}

function MySqlSyslog(){
	if($GLOBALS["AS_ROOT"]){return;}
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return ;}
	$sock=new sockets();
	$tpl=new templates();
	$MEM_TOTAL_INSTALLEE=$sock->getFrameWork("system.php?MEM_TOTAL_INSTALLEE=yes");
	if($MEM_TOTAL_INSTALLEE<624288){return;}
	$EnableMySQLSyslogWizard=intval($sock->GET_INFO("EnableMySQLSyslogWizard"));
	$EnableSyslogDB=intval($sock->GET_INFO("EnableSyslogDB"));
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	
	if($EnableArticaMetaClient==1){return;}
	if($EnableMySQLSyslogWizard==1){return;}
	if($EnableSyslogDB==1){return;}
	
	$html="<div style='margin-bottom:15px'>".
			Paragraphe("warning-panneau-64.png", "{MySQL_SYSLOG_NOTSET}","{MySQL_SYSLOG_NOTSET_EXPLAIN}",
			"javascript:Loadjs('MySQLSyslog.wizard.php')","go_to_section",665,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";	
	if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
	
	
	
}

function ZarafaWebAccess_wizard(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->ZARAFA_WEBAPP_INSTALLED){return;}
	$create_zarafa_mailbox_text=$tpl->_ENGINE_parse_body("{create_zarafa_mailbox_text}");
	
	if($users->AsMailBoxAdministrator){
		$html="<div style='margin-bottom:15px'>".Paragraphe("mailbox-add-64.png", "{new_mailbox}", 
				$create_zarafa_mailbox_text,
				"javascript:Loadjs('create-user.php')","go_to_section",665,132,1);
				
		echo $tpl->_ENGINE_parse_body($html)."</div>";	
		
		
	}
	
	
	$sock=new sockets();
	$ZarafaWebAPPWizard=$sock->GET_INFO("ZarafaWebAPPWizard");
	if(!is_numeric($ZarafaWebAPPWizard)){$ZarafaWebAPPWizard=0;}
	if($ZarafaWebAPPWizard==1){return;}
	
	$html="<div style='margin-bottom:15px'>".
			Paragraphe("zarafa-web-64.png", "{CREATE_YOUR_FIRST_WEBMAIL}","{CREATE_YOUR_FIRST_WEBMAIL_TEXT}",
					"javascript:Loadjs('WebAPP.wizard.php')","go_to_section",665,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";
	if($GLOBALS['VERBOSE']){echo "<hr>".date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
	
}



function injectSquid(){
	if($GLOBALS["VERBOSE"]){echo "<H1>injectSquid()</H1>\n";}
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	$MyBrowsersSetupShow=$sock->GET_INFO("MyBrowsersSetupShow");
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($MyBrowsersSetupShow)){$MyBrowsersSetupShow=0;}
	if($SQUIDEnable==0){return;}
	$CategoriesDatabasesShowIndex=$sock->GET_INFO("CategoriesDatabasesShowIndex");
	if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
	if($CategoriesDatabasesShowIndex==0){return;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}
	
	
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($users->PROXYTINY_APPLIANCE){$DisableArticaProxyStatistics=1;}
	

	
	if($DisableArticaProxyStatistics==1){
		if($GLOBALS["VERBOSE"]){echo "<H1>DisableArticaProxyStatistics:{$DisableArticaProxyStatistics} -> return null</H1>\n";}
		return;
	}
	
	if($WizardStatsAppliance["SERVER"]<>null){
		if($GLOBALS["VERBOSE"]){echo "<H1>WizardStatsAppliance:{$WizardStatsAppliance["SERVER"]} -> return null</H1>\n";}
		return;
	}
	
	$cacheFile="/usr/share/artica-postfix/ressources/web/cache1/injectSquid.".basename(__FILE__);
	if($GLOBALS["AS_ROOT"]){
		$unix=new unix();
		$mins=$unix->file_time_min($cacheFile);
		if($mins<5){return;}
		@unlink($cacheFile);
	}
	
	if(!$GLOBALS["AS_ROOT"]){
		if(is_file($cacheFile)){
			$data=@file_get_contents($cacheFile);
			if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>$cacheFile exists - ".strlen($data)." bytes</span><br>\n";}
			if(strlen($data)>20){
				echo $tpl->_ENGINE_parse_body($data);
				return;
			}
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>InjectSquid -></span>\n<br>";}
	
	$run=false;
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if($EnableWebProxyStatsAppliance==1){$users->WEBSTATS_APPLIANCE=true;}
	if($users->WEBSTATS_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>WEBSTATS_APPLIANCE -> RUN = TRUE</span>\n<br>";}
		$run=true;}
	if($users->SQUID_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>SQUID_INSTALLED -> RUN = TRUE</span>\n<br>";}
		$run=true;
	}
	if($users->SQUID_REVERSE_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>SQUID_REVERSE_APPLIANCE -> RUN = FALSE</span>\n<br>";}
		$run=false;}
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>run -> $run</span>\n<br>";}
	if(!$run){return;}	
	$inf=trim($sock->getFrameWork("squid.php?isInjectrunning=yes") );
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>inf -> $inf</span>\n<br>";}
	if($inf<>null){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>inf <> Null</span>\n<br>";}
		$tpl=new templates();
		$html="<div style='margin-bottom:15px'>".
		Paragraphe("tables-64-running.png", "{update_dbcatz_running}","{update_SQUIDAB_EXP}<hr><b>{since}:&nbsp;{$inf}&nbsp;{minutes}</b>", 
		"javascript:Loadjs('squid.blacklist.upd.php')","go_to_section",665,132,1);
		$html=$tpl->_ENGINE_parse_body($html)."</div>";	
	
		if($GLOBALS["AS_ROOT"]){
			@file_put_contents($cacheFile, $html);
			@chmod($cacheFile,0775);
		
		}else{
			echo $html;
		}
	
		return;	
	}
	
	
	$MEMORY=$users->MEM_TOTAL_INSTALLEE;
}


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{computer_load}");
	echo "YahooWin3('750','$page?tabs=yes','$title')";
	
	
}


function license(){
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance<3){
		
		if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
			$sock=new sockets();
			$cpunum=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
		}else{
			$cpunum=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
		}
		
		$CPU_NUMBER=$cpunum;
		
		
		
		if(is_numeric($CPU_NUMBER)){
			if($CPU_NUMBER<2){
				
				$html="<div style='margin-top:15px'>".
						Paragraphe("warning-panneau-64.png", "{performance_issue}","{performance_issue_cpu_number_text}",
								"javascript:GoToArticaLicense()","go_to_section",665,132,1);
				echo $tpl->_ENGINE_parse_body($html)."</div>";
			}
		}
	}
	
	
	if($users->CORP_LICENSE){Youtube();return;}
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	
	
		
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
if(!$users->CORP_LICENSE){
	if($EnableKerbAuth==1){
		
		$Days=86400*30;
		$DayToLeft=30;
		if(is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){
			include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
			$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
			$seconds_diff = $EndTime - time();
			$DayToLeft=floor($seconds_diff/3600/24);
		}
		
		if($DayToLeft<1){
			
			$html="<div style='margin-top:15px'>".
					Paragraphe("warning-panneau-64.png", "Active Directory",
							"{warn_evaluation_period_end}",
							"javascript:GoToArticaLicense()","go_to_section",755,132,1);
		
			echo $tpl->_ENGINE_parse_body($html)."</div>";
			
		}
		
		
		if($DayToLeft>1){
		$html="<div style='margin-top:15px'>".
				Paragraphe("warning-panneau-64.png", "Active Directory",
				"{warn_no_license_activedirectory_30days}",
				"javascript:GoToArticaLicense()","go_to_section",755,132,1);
				$html=str_replace("%s", $DayToLeft, $html);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
		
	}
}
	
	
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	if($LicenseInfos["license_status"]==null){
		$text="{explain_license_free}";
		
	}else{
		$text="{explain_license_order}";
	}
		
	$html="<div style='margin-top:15px'>".
	Paragraphe("license-error-64.png", "{artica_license}",$text, 
	"javascript:GoToArticaLicense()","go_to_section",665,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>".Youtube();
}

function Youtube(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	echo "<script>IndexHorloge();</script>";
	if($users->APACHE_INSTALLED){Apache_frontend();}
	
	if(!$users->SQUID_INSTALLED){return;}
	
	squid_frontend();
	
	$sock=new sockets();
	$DisableYoutubeLink=intval($sock->GET_INFO("DisableYoutubeLink"));
	if($DisableYoutubeLink==1){
	
	$html="<div style='margin-top:15px'>".
			Paragraphe("youtube-64.png", "{youtube_doc}","{youtube_doc_explain}",
					"javascript:Loadjs('$page?youtube-link=yes');","go_to_section",665,132,1);
		echo $tpl->_ENGINE_parse_body($html)."</div>";	
	}
	
}

function youtube_link(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$t=time();
	$youtubelink="http://www.youtube.com/playlist?list=PL6GqpiBEyv4q1GqpV5QbdYWbQdyxlWKGW";
echo"	
	var xsave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		s_PopUpFull('$youtubelink',1024,1024);
		CacheOff();
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('DisableYoutubeLink','1');
		XHR.sendAndLoad('$page', 'POST',xsave$t);
	}
Save$t();";	
	

}

function youtube_link_disable(){
	$sock=new sockets();
	$sock->SET_INFO("DisableYoutubeLink", 1);
}



function BytesToUnit($int,$unit){
	if($unit=="KB"){return round($int/1024,2);}
	if($unit=="MB"){return round($int/1024000,2);}
	if($unit=="GB"){return round($int/1024000000,2);}
	if($unit=="TB"){return round($int/10240000000000,2);}
}


function Apache_frontend_Bytes_to_unit($intmin,$intmax){
	$intmax=intval($intmax);
	
	if($intmax<1024){
		return array($intmin,$intmax,"BYTES");
	}
	
	
	if($intmax<1024000){
		return array(BytesToUnit($intmin,"KB"),BytesToUnit($intmax,"KB"),"KB");
	}	
	
	if($intmax<1024000000){
		return array(BytesToUnit($intmin,"MB"),BytesToUnit($intmax,"MB"),"MB");
	}

	if($intmax<10240000000000){
		return array(BytesToUnit($intmin,"GB"),BytesToUnit($intmax,"GB"),"GB");
	}		
	
	return array(BytesToUnit($intmin,"TB"),BytesToUnit($intmax,"TB"),"TB");
		


}

function remove_performance_alert(){
	$sock=new sockets();
	$sock->SET_INFO("SquidPerformanceRemoveNotice", 1);
	echo "document.getElementById('error-squid-memory-notice').innerHTML=''";
	
}

function squid_frontend(){
	$sock=new sockets();
	$page=CurrentPageName();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$AsSeenPerformanceFeature=intval($sock->GET_INFO("AsSeenPerformanceFeature"));
	$SquidPerformanceRemoveNotice=intval($sock->GET_INFO("SquidPerformanceRemoveNotice"));
	if($SQUIDEnable==0){return;}
	if($SquidPerformance<2){
		$addon="<div id='squid-rttrqs-status'></div>";
	}
	
	$addonUfdBcat="OnMouseOver=\"this.style.cursor='pointer'\"  OnMouseOut=\"this.style.cursor='default';\" OnClick=\"javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','squid.global.performance.php')\"";
	
	if($AsSeenPerformanceFeature==0){
		$tpl=new templates();
		
		echo $tpl->_ENGINE_parse_body("<div class='explain' style='padding:5px;font-size:18px;' $addonUfdBcat >{SquidAsSeenPerformanceFeature}</div>");
		}
		
		
	
	
	
if($SquidPerformance==0){
		if($SquidPerformanceRemoveNotice==0){
			
			if($GLOBALS["AS_ROOT"]){
				$unix=new unix();
				$MEMORY=$unix->TOTAL_MEMORY_MB();
			}else{
				$MEMORY=intval($sock->getFrameWork("cmd.php?GetTotalMemMB=yes"));
			}
			if($MEMORY>5){
			if($MEMORY<4500){
				echo "<div id='error-squid-memory-notice'>".FATAL_ERROR_SHOW_128("
				<strong style='font-size:22px'>{$MEMORY}MB/5900MB</strong>
				<hr>
				<a href=\"javascript:blur();\" OnClick=\"javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','squid.global.performance.php')\">{server_did_not_have_enough_memory_ufdbcat}</a>
				<div style='text-align:right;margin-top:10px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?remove-performance-alert=yes');\" style='text-decoration:underline'>{remove_this_notice}</a></div>
				")."</div>";
			}
		}
	}
}
	
	if($GLOBALS["AS_ROOT"]){
		$unix=new unix();
		$unix->SQUID_ACTIVE_REQUESTS();
	}else{
		$sock->getFrameWork("squid.php?active-requests=yes");
	}
	$page=CurrentPageName();
	
	echo "
	<div id='squid-front-end-status'></div>
	$addon
		<script>LoadAjaxTiny('squid-front-end-status','$page?squid-front-end-status=yes');
	</script>";
	
	
	
}

function squid_hypercache_license(){
	
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	$HyperCacheLicStatus=unserialize($sock->GET_INFO("HyperCacheLicStatus"));
	if($HyperCacheStoreID==0){return;}
	if(!isset($HyperCacheLicStatus["expired"])){return;}
	if($HyperCacheLicStatus["expired"]==1){return;}
	$t=time();
	$seconds_restantes=intval($HyperCacheLicStatus["edate"])- $t;
	$minutes_restantes=$seconds_restantes/60;
	$heures=$minutes_restantes/60;
	$jours=round($heures/24);
	if($jours>31){return;}
	$tpl=new templates();
	$content=$tpl->_ENGINE_parse_body("{HYPERCACHE_EVAL}");
	if($jours>0){
		$content=str_replace("%s", $jours, $content);
		return "<p style='font-size:16px' class=explain >$content
			<br><br>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','squid.artica-rules.php');\"
			style='text-decoration:underline;float:right;margin-top:-15px'>{view_feature}</a>
			
		</p>";
		
	}
	
	
	
}

function squid_frontend_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$TITLE_REQUESTS=null;
	$SQUID_RUNNING=false;
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}

	$squid5mn=unserialize(base64_decode($sock->getFrameWork("squid.php?5mncounter=yes")));
	$CounterInfos=unserialize(base64_decode($sock->getFrameWork("squid.php?CounterInfos=yes")));
	$StorageCapacity=unserialize(base64_decode($sock->getFrameWork("squid.php?StorageCapacity=yes")));
	$SquidMonitorParms=unserialize(base64_decode($sock->GET_INFO("SquidMonitorParms")));
	$t=time();
	$server_all_kbytes_in=$SquidMonitorParms["server_all_kbytes_in"];
	$server_all_kbytes_out=$SquidMonitorParms["server_all_kbytes_out"];
	$HttpRequests=$SquidMonitorParms["HttpRequests"];
	$ActiveRequests=$SquidMonitorParms["ActiveRequests"];
	$TITLE_USERS=null;
	$TITLE_COMPUTERS=null;
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==1){
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ldap=new external_ad_search();
		$NET_RPC_INFOS=$ldap->NET_RPC_INFOS();
		$NumBerOfUsers=intval($NET_RPC_INFOS["Num users"]);
		if($NumBerOfUsers>0){
			$TITLE_USERS="&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
			OnClick=\"javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','squid.adker.php?tabs=yes');\" 
			style='text-decoration:underline'>$NumBerOfUsers {members}</a>";
		}
	}
	
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(MAC) as tcount FROM (SELECT MAC FROM UserAutDB GROUP BY MAC) as t"));
	$Nodes=$ligne["tcount"];
	
	if($Nodes>0){
		$TITLE_COMPUTERS="&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('squid.computer-browse.php')\"
		style='text-decoration:underline'>$Nodes {computers}</a>";
	}
	
	$CACHES_RATES=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOTAL_CACHED"));
	$CACHED_AVG=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/CACHED_AVG");
	$TOTALS_NOT_CACHED=intval($CACHES_RATES["TOTALS_NOT_CACHED"]);
	$TOTALS_CACHED=intval($CACHES_RATES["TOTALS_CACHED"]);
	$TOTALS_DOWNLOAD=$TOTALS_NOT_CACHED+$TOTALS_CACHED;
	
	$TOTALS_NOT_CACHED=intval(@file_get_contents("/usr/share/artica-postfix/ressources/logs/stats/NOT_CACHED"));
	if($TOTALS_NOT_CACHED>0){
		$TOTALS_NOT_CACHED_TEXT="&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('admin.index.loadvg.squid.notcached-week.php');\"
		style='text-decoration:underline;font-weight:bold'>{not_cached_this_week}: ".FormatBytes($TOTALS_NOT_CACHED/1024)."</a>";
	}
	
	
	
	$Status_cache="&nbsp;|&nbsp;{downloaded} ".FormatBytes($TOTALS_DOWNLOAD/1024).
	"&nbsp;|&nbsp;{cached}:".FormatBytes($TOTALS_CACHED/1024).$TOTALS_NOT_CACHED_TEXT;
	
	
	$RATE=round($CACHED_AVG,2);
	$TITLE_RATE="&nbsp;|&nbsp;{cache_rate} {$RATE}%";
	
	
	
	if(!is_numeric($server_all_kbytes_in)){$server_all_kbytes_in=1000;}
	if(!is_numeric($server_all_kbytes_out)){$server_all_kbytes_out=250;}
	if(!is_numeric($HttpRequests)){$HttpRequests=150;}
	if(!is_numeric($ActiveRequests)){$ActiveRequests=150;}
	
	if(!isset($squid5mn["cpu_usage"])){$squid5mn["cpu_usage"]=0;}
	
	$squid5mn["cpu_usage"]=round($squid5mn["cpu_usage"],2);
	$squid5mn["client_http.requests"]=round($squid5mn["client_http.requests"],2);
	$squid5mn["server.all.kbytes_in"]=round($squid5mn["server.all.kbytes_in"],2);
	$squid5mn["server.all.kbytes_out"]=round($squid5mn["server.all.kbytes_out"],2);
	
	
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$ActiveRequestsIpaddr=count($ActiveRequestsR["IPS"]);
	$ActiveRequestsMembers=count($ActiveRequestsR["USERS"]);
	
	if($ActiveRequestsNumber>0){
		$TITLE_REQUESTS="&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('squid.active.requests.php')\"
		style='text-decoration:underline'>$ActiveRequestsNumber {active_requests}</a>";
	}
	
	if(!is_numeric($ActiveRequestsNumber)){$ActiveRequestsNumber=0;}
	if(!is_numeric($ActiveRequestsIpaddr)){$ActiveRequestsIpaddr=0;}
	if(!is_numeric($ActiveRequestsMembers)){$ActiveRequestsMembers=0;}
	
	$server_all_kbytes_in_text=$tpl->javascript_parse_text("{server_all_kbytes_in}");
	$server_all_kbytes_out_text=$tpl->javascript_parse_text("{server_all_kbytes_out}");
	$active_requests=$tpl->javascript_parse_text("{active_requests}");
	$proxy_status=$tpl->javascript_parse_text("{proxy_service}");
	$second=$tpl->javascript_parse_text("{second}");
	$requests=$tpl->javascript_parse_text("{requests}");
	
	$StatusDevSHMPerc=intval($sock->GET_INFO("StatusDevSHMPerc"));
	
$JAUGES_SCRIPT="
var g = new JustGage({
	id: 'squid-1-$t',
	value: {$squid5mn["cpu_usage"]},
	min: 0.1,
	max: 100,
	title: 'Proxy CPU Usage',
	label: '%',
	levelColorsGradient: true
});

var g0 = new JustGage({
	id: 'devshm',
	value: $StatusDevSHMPerc,
	min: 0,
	max: 100,
	title: 'Shared Memory',
	label: '%',
	levelColorsGradient: true
});
	
var g2 = new JustGage({
	id: 'squid-2-$t',
	value: {$squid5mn["client_http.requests"]},
	min: 0.1,
	max: $HttpRequests,
	title: 'HTTP $requests/$second',
	label: 'RQ/s',
	levelColorsGradient: true
});

var g3 = new JustGage({
	id: 'squid-3-$t',
	value: {$squid5mn["server.all.kbytes_in"]},
	min: 0,
	max: $server_all_kbytes_in,
	title: '$server_all_kbytes_in_text',
	label: 'KB',
	levelColorsGradient: true
	});
";	
	
$countStorages=count($StorageCapacity);
for($i=0;$i<$countStorages;$i++){
	$tS[]="<div id='squid-s{$i}-$t' style='width:160px; height:100px'>";
	$js[]=" var s{$i} = new JustGage({
		id: 'squid-s{$i}-$t',
		value: {$StorageCapacity[$i]},
		min: 0,
		max: 100,
		title: 'Storage Capacity Kid ". ($i+1)."',
		label: '%',
		levelColorsGradient: true
	});      ";
	
}
		$storages=CompileTr4($tS,true,null,true);
		
		if(!is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){
			$sock->getFrameWork("cmd.php?squid-ini-status=yes");
			
		}
		
		$ini=new Bs_IniHandler();
		$color="black";
		$ini->loadFile("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
		if($ini->_params["SQUID"]["running"]==0){
			$color="#d32d2d";
			$status=$tpl->_ENGINE_parse_body("{stopped}");
		}else{
			$SQUID_RUNNING=true;
			if($ini->_params["SQUID"]["master_time"]){
				$status="{running}";
				$status2="<span style='color:#47A447'>{running} {since} ".distanceOfTimeInWords($ini->_params["SQUID"]["master_time"],time())."</span>";
			}
		}
	
$version=@file_get_contents("/usr/share/artica-postfix/ressources/databases/SQUID.version");	
if($version<>null){$version=" v.$version";}

if($SquidCacheLevel==0){
	$nocache=" <span style='color:#d32d2d'>{no_cached_sites_warn}</span>";
}
$squi1_text=$tpl->javascript_parse_text("{monitor}");
$squi1_onmouse="OnMouseOver=\"javascript:AffBulle('$squi1_text');this.style.cursor='pointer'\" OnMouseOut=\"javascript:HideBulle();this.style.cursor='default'\"";
$squi1_onClick="OnClick=\"javascript:Loadjs('squid.task.monitor.php')\"";
$start_js=null;
if(!$SQUID_RUNNING){
	$storages=null;
	$JAUGES_SCRIPT=null;
	$js=array();
	
	$start_js=$tpl->_ENGINE_parse_body("<center style='font-size:28px;color:$color;width:98%;margin-top:15px' class=form>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.start.progress.php');\"
			style='margin-top:20px'>
			<p>{start_proxy_service}</p>
			<img src='img/start-256.png'>
			</a>
			</center>");
	
}

		$squid_hypercache_license=squid_hypercache_license();
	echo $tpl->_ENGINE_parse_body("
			<div id='all-squid-warnings'></div>
			
			$squid_hypercache_license
<table  style='width:99%' >
	<tr>
		<td colspan=4 style='font-size:22px'>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:LoadAjax('BodyContent','squid.caches.status.php?tabs=yes')\" 
			style='text-decoration:underline;color:$color'>$proxy_status <span style='font-size:16px'>($status)</span></a>
			$TITLE_RATE$TITLE_USERS$TITLE_REQUESTS$TITLE_COMPUTERS
			<br>
			<div style='font-size:11px'><i>$status2&nbsp;|&nbsp;$version$nocache$Status_cache</i></div>
			$start_js
		</td>
	</tr>
	<tr>
			<td valign='top' width=25%><div id='squid-1-$t' style='width:160px; height:100px' $squi1_onmouse $squi1_onClick></div></td>
			<td valign='top' width=25%><div id='devshm' style='width:170px; height:100px'></div></td>
			<td valign='top' width=25%><div id='squid-2-$t' style='width:170px; height:100px'></div></td>
			<td valign='top' width=25%><div id='squid-3-$t' style='width:170px; height:100px'></div></td>
			
	</tr>
	
</table>$storages
			
"."<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjaxTiny('squid-front-end-status','$page?squid-front-end-status=yes');")."</div>
<script>
$JAUGES_SCRIPT
".@implode("\n", $js)."
if(document.getElementById('squid-rttrqs-status')){
	LoadAjaxSilent('squid-rttrqs-status','admin.index.loadavg.squidrtt.php');
}
			
LoadAjaxSilent('all-squid-warnings','admin.index.squid.warning.php');			
			
</script>
	");	
	
	
	
	
	
	
}




function Apache_frontend(){
	$tpl=new templates();
	$q=new mysql();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	if($EnableFreeWeb==0){return;}
	$t=time();
	if($q->COUNT_ROWS("freeweb", "artica_backup")==0){
		echo "<!-- freeweb = 0  -->";
		
		return;}
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/APACHE_HASH")){
		echo "<!-- APACHE_HASH = no such file -->";
		
		return;}
		
	
	echo "<div id='apache-front-end-status'></div><script>LoadAjaxTiny('apache-front-end-status','$page?apache-front-end-status=yes');</script>";
	
}

function apache_status(){
	$tpl=new templates();
	$q=new mysql();
	$page=CurrentPageName();
	$sock=new sockets();
	$HASH=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/APACHE_HASH"));
	$t=time();
	$total_traffic=$HASH["total_traffic"];
	$avg_total_traffic=$HASH["AVG"]["total_traffic"];
	if($total_traffic>$avg_total_traffic){$avg_total_traffic=$HASH["MAX"]["total_traffic"];}
	if($total_traffic>$avg_total_traffic){$avg_total_traffic=$total_traffic;}	
	
	
	$S=Apache_frontend_Bytes_to_unit($total_traffic,$avg_total_traffic);
	$total_traffic=$S[0];
	$avg_total_traffic=$S[1];
	$total_traffic_unit=$S[2];
	
	$total_traffic_text=$tpl->javascript_parse_text("{total_traffic} $total_traffic_unit");
	
	
	$total_mem=$HASH["total_mem"];
	$avg_total_mem=$HASH["AVG"]["total_mem"];
	if($total_mem>$avg_total_traffic){$avg_total_mem=$HASH["MAX"]["total_mem"];}
	if($total_mem>$avg_total_mem){$avg_total_mem=$total_mem;}
	
	$S=Apache_frontend_Bytes_to_unit($total_mem,$avg_total_mem);
	$total_mem=$S[0];
	$avg_total_mem=$S[1];
	$total_mem_unit=$S[2];
	$total_memory_text=$tpl->javascript_parse_text("{total_memory} $total_mem_unit");

	
	$requests_second=$HASH["requests_second"];
	$avg_requests_second=$HASH["AVG"]["requests_second"];
	if($requests_second>$avg_requests_second){$avg_requests_second=$HASH["MAX"]["requests_second"];}
	if($requests_second>$avg_requests_second){$avg_requests_second=$requests_second;}
	$requests_second_text=$tpl->javascript_parse_text("{requests_second}");
	
	
	$traffic_request=$HASH["traffic_request"];
	$avg_traffic_request=$HASH["AVG"]["traffic_request"];
	if($traffic_request>$avg_traffic_request){$avg_traffic_request=$HASH["MAX"]["traffic_request"];}
	if($traffic_request>$avg_traffic_request){$avg_traffic_request=$traffic_request;}
	
	$S=Apache_frontend_Bytes_to_unit($traffic_request,$avg_traffic_request);
	$traffic_request=$S[0];
	$avg_traffic_request=$S[1];
	$traffic_request_unit=$S[2];
	
	
	$apache_status=$tpl->_ENGINE_parse_body("{webservice_status}");
	$HASH["UPTIME"]=str_replace("hours", "{hours}", $HASH["UPTIME"]);
	$HASH["UPTIME"]=str_replace("minutes", "{minutes}", $HASH["UPTIME"]);
	$HASH["UPTIME"]=str_replace("seconds", "{seconds}", $HASH["UPTIME"]);
	$traffic_request_text=$tpl->javascript_parse_text("{traffic_request} $traffic_request_unit");	
	
	
echo $tpl->_ENGINE_parse_body("
<table  style='width:70%' >
<tr><td colspan=4 style='font-size:18px'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:QuickLinkSystems('section_freeweb')\"
		style='text-decoration:underline'>$apache_status</a><br><div style='font-size:14px;margin-top:10px'>{running_since} {$HASH["UPTIME"]}</div></td></tr>
<tr>
<td valign='top'><div id='apache-total-traffic-$t' style='width:160px; height:100px'></div></td>
<td valign='top'><div id='apache-total-mem-$t' style='width:170px; height:100px'></div></td>
<td valign='top'><div id='apache-total-rqs-$t' style='width:170px; height:100px'></div></td>
<td valign='top'><div id='apache-total-trq-$t' style='width:170px; height:100px'></div></td>
</tr>
<tr><td colspan=4 style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjaxTiny('apache-front-end-status','$page?apache-status=yes');")."</td></tr>
</table>
<script>		
 var g = new JustGage({
id: 'apache-total-traffic-$t',
value: $total_traffic,
min: 0.5,
max: $avg_total_traffic,
title: '$total_traffic_text',
label: '$total_traffic_unit',
levelColorsGradient: true
});	

 var g2 = new JustGage({
id: 'apache-total-mem-$t',
value: $total_mem,
min: 0.5,
max: $avg_total_mem,
title: '$total_memory_text',
label: '$total_mem_unit',
levelColorsGradient: true
});	
 var g3 = new JustGage({
id: 'apache-total-rqs-$t',
value: $requests_second,
min: 0.1,
max: $avg_requests_second,
title: '$requests_second_text',
label: 'RQ/s',
levelColorsGradient: true
});
 var g4 = new JustGage({
id: 'apache-total-trq-$t',
value: $traffic_request,
min: 0.1,
max: $avg_traffic_request,
title: '$traffic_request_text',
label: '$traffic_request_unit',
levelColorsGradient: true
});
</script>			
");
	
	
	
	
/*	(
	[total_traffic] => 23859200000
	[total_mem] => 6208908
	[request_s] => 1.08
	[traffic_sec] => 1331200
	[traffic_request] => 1228800
	[UPTIME] => 5 hours 8 minutes 9 seconds
	[AVG] => Array
	(
			[total_traffic] => 573044462.8000
			[total_memory] => 5985492.2667
			[requests_second] => 0.330667
			[traffic_second] => 164758.2000
			[traffic_request] => 158319.2667
	)
*/
	
	
}

function tabs(){
	$tpl=new templates();
	$array["today"]='{today}';
	$array["week"]='{last_7_days}';
	$array["month"]='{month}';
	$array["year"]='{year}';
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$time\"><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_loadavgtabs");
}	

	if($GLOBALS["VERBOSE"]){echo __LINE__." instanciate artica_graphs()<br>\n";}
	$tpl=new templates();
	$gp=new artica_graphs();
	$memory_average=$tpl->_ENGINE_parse_body("{memory_use} {today} (MB)");
	if($GLOBALS["VERBOSE"]){echo "<hr>";}
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tday,HOUR(zDate) as thour,AVG(mem) as tmem FROM ps_mem_tot GROUP BY tday,thour HAVING tday=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY thour";
	if($GLOBALS["VERBOSE"]){echo "<code>$sql</code><br>";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	$mysql_num_rows=mysql_num_rows($results);
	$xtitle=$tpl->javascript_parse_text("{hours}");
	
	if($mysql_num_rows<2){
		$sql="SELECT DATE_FORMAT(zDate,'%h') as thour2,DATE_FORMAT(zDate,'%i') as thour, AVG(mem) as tmem FROM ps_mem_tot GROUP BY DATE_FORMAT(zDate,'%H-%i')
		HAVING thour2=DATE_FORMAT(NOW(),'%h') ORDER BY DATE_FORMAT(zDate,'%H-%i') ";
		if($GLOBALS["VERBOSE"]){echo "<code>$sql</code><br>";}
		$results=$q->QUERY_SQL($sql,"artica_events");
		$memory_average=$tpl->_ENGINE_parse_body("{memory_use} {this_hour} (MB)");
		$mysql_num_rows=mysql_num_rows($results);
		$xtitle=$tpl->javascript_parse_text("{minutes}");		
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".ps-mem.png";
	$xdata=array();
	$ydata[]=array();
	$c=0;
	writelogs("mysql return no rows from a table of $mysql_num_rows rows ",__FUNCTION__,__FILE__,__LINE__);
	if($mysql_num_rows>0){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$size=$ligne["tmem"];
				$size=$size/1024;
				$size=$size/1000;
				$size=round($size/1000,0);
				$gp->xdata[]=$ligne["thour"];
				$gp->ydata[]=$size;
				$c++;
				if($GLOBALS["VERBOSE"]){echo "<li>ps_mem $hour -> $size</li>";};
			}
			if($c==0){writelogs("Fatal \"$targetedfile\" no items",__FUNCTION__,__FILE__,__LINE__);return;}
			if(is_file($targetedfile)){@unlink($targetedfile);}
			
			$gp->width=300;
			$gp->height=120;
			$gp->filename="$targetedfile";
			$gp->y_title=null;
			$gp->x_title=$xtitle;
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$tpl=new templates();
			
			//$gp->SetFillColor('green'); 
			
			$gp->line_green();
			if(is_file($targetedfile)){
				echo "<center><div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
				this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed58\" OnClick=\"javascript:Loadjs('admin.index.psmem.php?all=yes')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$memory_average</h3>
				<img src='$targetedfile'>
				</div></center>";	
			}	
		
	}
	
// --------------------------------------------------------------------------------------	
	
	
	
	
	
	
	$sock=new sockets();
	$users=new usersMenus();

	
	
writelogs("Checking milter-greylist",__FUNCTION__,__FILE__,__LINE__);	
// --------------------------------------------------------------------------------------
	if($users->MILTERGREYLIST_INSTALLED){
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==0){
			$APP_MILTERGREYLIST=$tpl->_ENGINE_parse_body("{APP_MILTERGREYLIST}");
			if(is_file("ressources/logs/greylist-count-master.tot")){
			$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
			if(is_array($datas)){
				@unlink("ressources/logs/web/mgreylist.master1.db.png");
				$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/mgreylist.admin.index.db.png",0);
				$gp->xdata[]=$datas["GREYLISTED"];
				$gp->ydata[]="greylisted";	
				$gp->xdata[]=$datas["WHITELISTED"];
				$gp->ydata[]="whitelisted";				
				$gp->width=300;
				$gp->height=120;
				$gp->PieExplode=5;
				
				$gp->ViewValues=false;
				$gp->x_title=null;
				$gp->pie();	
				
				if(is_file("ressources/logs/web/mgreylist.admin.index.db.png")){	
				echo "<div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
				this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed5898\" OnClick=\"javascript:Loadjs('milter.greylist.index.php?js=yes&in-front-ajax=yes')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$APP_MILTERGREYLIST</h3>
				<img src='ressources/logs/web/mgreylist.admin.index.db.png'>
				</div>";
				}
				
				
			}
			}
		}
		
	}
	
	
	

	
echo "</center>
<div id='notifs-part'></div>
<script>LoadAjax('notifs-part','admin.left.php?partall=yes');</script>

";
	
	
function hour(){

$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
$sql="SELECT AVG( `load` ) AS sload, DATE_FORMAT( stime, '%i' ) AS ttime FROM `loadavg` WHERE `stime` > DATE_SUB( NOW( ) , INTERVAL 60 MINUTE ) GROUP BY ttime ORDER BY `ttime` ASC";

	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$count=mysql_num_rows($results);
	
	if(mysql_num_rows($results)==0){return;}	
	
	if(!$q->ok){echo $q->mysql_error;}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["tsize"]/1024;
		$size=$size/1000;
		$xdata[]=$ligne["ttime"];
		$ydata[]=$ligne["sload"];
		$c++;
		if($ligne["sload"]>$cpunum){
			if($GLOBALS["VERBOSE"]){echo "<li>!!!! {$ligne["stime"]} -> $c</LI>";};
			if(!isset($red["START"])){$red["START"]=$c;}
		}else{
			if(isset($red["START"])){
				$area[]=array($red["START"],$c);
				unset($red);
			}
		}
		
	

		
		
		if($GLOBALS["VERBOSE"]){echo "<li>{$ligne["stime"]} -> {$ligne["ttime"]} -> {$ligne["sload"]}</LI>";};
	}
	if(isset($red["START"])){$area[]=array($red["START"],$c);}

	$file=time();
	$gp=new artica_graphs();
	$gp->RedAreas=$area;
	$gp->width=650;
	$gp->height=350;
	$gp->filename="ressources/logs/loadavg-hour.png";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title="Mn";
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$tpl=new templates();
	//$gp->SetFillColor('green'); 
	
	$gp->line_green();
	
	echo "
	<div id='loadavg-clean'>
	<img src='ressources/logs/loadavg-hour.png'></div></div>
	<div style='text-align:right'><hr>".$tpl->_ENGINE_parse_body(button("{clean_datas}","LoadAvgClean()"))."</div>
	<script>
	
	var x_LoadAvgClean=function(obj){
      var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
      YahooWin3Hide();
      document.getElementById('loadavggraph').innerHTML='';
      }	
	
	function LoadAvgClean(){
		var XHR = new XHRConnection();
		XHR.appendData('LoadAvgClean','yes');
		
		AnimateDiv('loadavg-clean');
		XHR.sendAndLoad('$page', 'POST',x_LoadAvgClean);		
		}	
	
	
	</script>";	
	
	
}

function today(){


	
	if(!is_file("/opt/artica/var/rrd/yorel/loadavg_1.rrd")){
		$sock=new sockets();
		$sock->getFrameWork("services.php?yorel-rebuild=yes");
		
	}
	
$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("Today: Server Load"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=645;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1day";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("Server load"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadd.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadd.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("Today: memory"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1day";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("Memory MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memd.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memd.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("Today: CPU"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1day";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("CPU %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpud.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpud.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";
}
function week(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {week}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=645;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1week";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("Server Load"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadw.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {week}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1week";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memw.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {week}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1week";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpuw.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpuw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";	
	
	
	
}

function month(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {month}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=645;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1month";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadm.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadm.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {month}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1month";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memm.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memm.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {month}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1month";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpum.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpum.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";		
	
}
function year(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {year}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=645;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1year";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loady.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loady.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {year}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1year";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memy.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memy.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {year}"));
	$rrd->width=645;
	$rrd->height=250;
	$rrd->timestart="-1year";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpuy.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpuy.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";		
	
}
function NightlyNotifs(){
	if($GLOBALS["VERBOSE"]){echo "<H1>NightlyNotifs()</H1>\n";}
	$sock=new sockets();
	$EnableNightlyInFrontEnd=$sock->GET_INFO("EnableNightlyInFrontEnd");
	if(!is_numeric($EnableNightlyInFrontEnd)){$EnableNightlyInFrontEnd=1;}
	if($EnableNightlyInFrontEnd==0){
		if($GLOBALS["VERBOSE"]){echo "<span style=color:blue>EnableNightlyInFrontEnd=$EnableNightlyInFrontEnd</span><br>\n";}
		return;
	}
	
	
	if(!is_file("ressources/index.ini"))	{
		if($GLOBALS["VERBOSE"]){echo "<span style=color:blue>ressources/index.ini no such file</span><br>\n";}
		return;
	}
		
	$ini=new Bs_IniHandler("ressources/index.ini");
	
	if(!isset($ini->_params["NEXT"])){
		$sock->getFrameWork("system.php?refresh-index-ini=yes");
		$ini=new Bs_IniHandler("ressources/index.ini");
	}
	
	$nightly=$ini->get("NEXT","artica-nightly");
	$version=@file_get_contents("VERSION");

	$nightlybin=str_replace('.','',$nightly);
	$versionbin=str_replace('.','',$version);
	
	if($GLOBALS["VERBOSE"]){echo "<span style=color:blue>$nightlybin = $versionbin</span><br>\n";}
	if($versionbin==0){return;}
	if($nightlybin==0){return;}
	
	

	if($nightlybin>$versionbin){
		$tpl=new templates();
		$html="<div style='margin-bottom:15px'>".
				Paragraphe("download-64.png", "{NEW_NIGHTLYBUILD}: $nightly"
				,"{NEW_NIGHTLYBUILD_TEXT}",
				"javascript:Loadjs('artica.update.php?js=yes')","go_to_section",665,132,1);
		$html=$tpl->_ENGINE_parse_body($html)."</div>";
		echo $html;
		return;
	}
	
	
}

function OfficialRelease(){
	
	$sock=new sockets();
	if(!is_file("ressources/index.ini"))	{
		if($GLOBALS["VERBOSE"]){echo "<span style=color:blue>ressources/index.ini no such file</span><br>\n";}
		return;
	}
	
	$ini=new Bs_IniHandler("ressources/index.ini");
	if(!isset($ini->_params["NEXT"])){$sock->getFrameWork("system.php?refresh-index-ini=yes");return;}
	
	$Lastest=trim(strtolower($ini->_params["NEXT"]["artica"]));
	$version=@file_get_contents("VERSION");
	
	$nightlybin=str_replace('.','',$Lastest);
	$versionbin=str_replace('.','',$version);
	
	if($GLOBALS["VERBOSE"]){echo "<span style=color:blue>$nightlybin = $versionbin</span><br>\n";}
	if($versionbin==0){return;}
	if($nightlybin==0){NightlyNotifs();return;}
	
	
	
	if($nightlybin>$versionbin){
		$tpl=new templates();
		$html="<div style='margin-bottom:15px'>".
				Paragraphe("download-64.png", "{NEW_RELEASE}: $Lastest"
						,"{NEW_RELEASE_TEXT}",
						"javascript:Loadjs('artica.update.php?js=yes')","go_to_section",665,132,1);
		$html=$tpl->_ENGINE_parse_body($html)."</div>";
		echo $html;
		return;
	}
	NightlyNotifs();
	
}


function PageDeGarde(){
	if($GLOBALS['VERBOSE']){echo date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
	$cacheFile=dirname(__FILE__)."/ressources/logs/web/".basename(__FILE__).".".__FUNCTION__;
	if($GLOBALS["AS_ROOT"]){return;}
	OfficialRelease();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$time=time();
	
	
	
	
	if(is_file("ressources/logs/web/INTERFACE_LOAD_AVG.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-2'></div>";
		$f2[]="function FDeux$time(){	
				AnimateDiv('$time-2'); 
				Loadjs('$page?graph2=yes&container=$time-2',true); 
			} 
		setTimeout(\"FDeux$time()\",500);";
	}
	
	
	if(is_file("ressources/logs/web/cpustats.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-cpustats'></div>";
		$f2[]="function Fcpustats$time(){AnimateDiv('$time-cpustats');Loadjs('$page?cpustats=yes&container=$time-cpustats',true);} setTimeout(\"Fcpustats$time()\",500);";
		}else{
			if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/cpustats.db no such file</H1>\n";}
		}
	
	
	
	if(is_file("ressources/logs/web/INTERFACE_LOAD_AVG2.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-1'></div>";
		$f2[]="function FOne$time(){AnimateDiv('$time-1');Loadjs('$page?graph1=yes&container=$time-1',true);} setTimeout(\"FOne$time()\",500);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/INTERFACE_LOAD_AVG2.db no such file</H1>\n";}
	}	
	

	
	
	//bandwith
	
	
	


	
	
	
	if($SquidPerformance<3){
		$cacheFile="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_WEBFILTER_BLOCKED.db";
		if(is_file($cacheFile)){
			$f1[]="<div style='width:665px;height:240px' id='$time-6'></div>";
			$f2[]="function Fsix$time(){
			AnimateDiv('$time-6');
			Loadjs('$page?graph6=yes&container=$time-6',true);
		}
		setTimeout(\"Fsix$time()\",800);";
		}else{
			if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/INTERFACE_WEBFILTER_BLOCKED.db no such file</H1>\n";}
		}
	}
	
	
	if($users->cyrus_imapd_installed){
		$CyrusImapPartitionDefaultSize=$sock->GET_INFO("CyrusImapPartitionDefaultSize");
		if(!is_numeric($CyrusImapPartitionDefaultSize)){$CyrusImapPartitionDefaultSize=0;}
		if($CyrusImapPartitionDefaultSize>2){
			$f1[]="<div style='width:665px;height:340px' id='$time-4'></div>";
			$f2[]="function FQuatre$time(){AnimateDiv('$time-4');Loadjs('$page?graph4=yes&container=$time-4',true);} setTimeout(\"FQuatre$time()\",600);";
		}
	}
	
	
	
	if($GLOBALS['VERBOSE']){echo date("H:i.s")." ". __FUNCTION__."::".__LINE__."<br>\n";}
	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;

}


function LoadAvgClean(){
	$q=new mysql();
	$q->DELETE_TABLE("loadavg", "artica_events");
	$q->BuildTables();
	
}

function cpustats(){
	$workingdir="ressources/logs/web";
	if(isset($_GET["uuid"])){
		$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/{$_GET["uuid"]}";
	}
	$filecache="$workingdir/cpustats.db";
	if(!is_file($filecache)){return;}
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];	
	$title="% CPU {this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{cpu}";
	if(!isset($_GET["uuid"])){
		$highcharts->subtitle="<a href=\"statistics.index.php\" style='text-decoration:underline'>{more_details}</a>";
	}
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("%"=>$ydata);
	echo $highcharts->BuildChart();	
}

function graph1(){
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$_GET["time"]="hour";
	$workingdir="ressources/logs/web";
	if(isset($_GET["uuid"])){
		$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/{$_GET["uuid"]}";
	}
	
		
		$title="{server_load_this_hour}";
		$timetext="{minutes}";
		
	$filecache="$workingdir/INTERFACE_LOAD_AVG.db";
	if(!is_file($filecache)){return;}	
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$title="{server_load_this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{load}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{load}"=>$ydata);
	echo $highcharts->BuildChart();
	
}
function graph2(){
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$workingdir="ressources/logs/web";
	if(isset($_GET["uuid"])){
		$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/{$_GET["uuid"]}";
	}
	
	$title="{memory_consumption_this_hour}";
	$timetext="{minutes}";

	
	
	$filecache="$workingdir/INTERFACE_LOAD_AVG2.db";
	if(!is_file($filecache)){
		if($GLOBALS["VERBOSE"]){echo "$workingdir/INTERFACE_LOAD_AVG2.db no such file\n<br>";}
		return;}
	$ARRAY=unserialize(@file_get_contents($filecache));
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
		
	$title="{memory_consumption_this_hour}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{memory} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->datas=array("{memory}"=>$ydata);
	echo $highcharts->BuildChart();

}



function graph6(){
	
	$filecache="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_WEBFILTER_BLOCKED.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{blocked_websites}";
	$timetext="{hour}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->_ENGINE_parse_body("{".date('F') ."} {day}:");
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("{hits}");
	
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	

}




// Cyrus IMAPD Taille sur le disque.
function graph4(){
	$sock=new sockets();
	$tpl=new templates();
	$currentsize=$sock->GET_INFO("CyrusImapPartitionDefaultSize");
	$CyrusImapPartitionDefaultSizeTime=$sock->GET_INFO("CyrusImapPartitionDefaultSizeTime");
	$CyrusImapPartitionDiskSize=$sock->GET_INFO("CyrusImapPartitionDiskSize");
	$tot=$CyrusImapPartitionDiskSize-$currentsize;
	
	$currentsizeT=FormatBytes($currentsize*1024);
	$totT=FormatBytes($tot*1024);
	$PieData["Mailboxes $currentsizeT"]=$currentsize;
	$PieData["Disk $totT"]=$tot;
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{mailboxes}");
	echo $highcharts->BuildChart();	
	
}
//if(!$users->ARTICADB_INSTALLED){ //ARTICADB_NOT_INSTALLED_EXPLAIN
?>