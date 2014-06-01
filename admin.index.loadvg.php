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
if(!$GLOBALS["AS_ROOT"]){if(!$users->AsSystemAdministrator){die();}}
if(isset($_GET["all"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hour"])){hour();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week();exit;}
if(isset($_GET["month"])){month();exit;}
if(isset($_GET["year"])){year();exit;}
if(isset($_POST["LoadAvgClean"])){LoadAvgClean();exit;}


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
	$sock=new sockets();
	$tpl=new templates();
	$MEM_TOTAL_INSTALLEE=$sock->getFrameWork("system.php?MEM_TOTAL_INSTALLEE=yes");
	if($MEM_TOTAL_INSTALLEE<624288){return;}
	$EnableMySQLSyslogWizard=$sock->GET_INFO("EnableMySQLSyslogWizard");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	
	if(!is_numeric($EnableMySQLSyslogWizard)){$EnableMySQLSyslogWizard=0;}
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	
	
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
	if(!$users->ZARAFA_WEBAPP_INSTALLED){return;}
	$sock=new sockets();
	$ZarafaWebAPPWizard=$sock->GET_INFO("ZarafaWebAPPWizard");
	if(!is_numeric($ZarafaWebAPPWizard)){$ZarafaWebAPPWizard=0;}
	if($ZarafaWebAPPWizard==1){return;}
	$tpl=new templates();
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
	
	if($MyBrowsersSetupShow==0){
		if($users->SQUID_INSTALLED){
			$html="<div style='margin-bottom:15px'>".
					Paragraphe("64-info.png", "{my_browsers}","{how_to_connect_browsers}",
							"javascript:Loadjs('squid.dashboard.php?mybrowsers-js=yes',true)","go_to_section",665,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
		
	}
	
	if($SquidDebugAcls==1){
		if($users->SQUID_INSTALLED){
			
			$html="<div style='margin-bottom:15px'>".
					Paragraphe("warning-panneau-64.png", "{acl_in_debug_mode}","{acl_in_debug_mode_explain}",
							"javascript:Loadjs('squid.acls.options.php',true)","go_to_section",665,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
			
		}
		
	}
	
	if($users->KAV4PROXY_INSTALLED){
		$licenseerror=base64_decode($sock->getFrameWork("squid.php?kav4proxy-license-error=yes"));
		if($licenseerror<>null){
			$tpl=new templates();
			$text=$tpl->_ENGINE_parse_body("{KAV_LICENSE_ERROR_EXPLAIN}");
			$text=str_replace("%s", "«{$licenseerror}»", $text);
			echo $tpl->_ENGINE_parse_body(Paragraphe("64-red.png", "Kaspersky {license_error}",$text,
			"javascript:Loadjs('Kav4Proxy.license-manager.php',true)","go_to_section",665,132,1));
		}
	}
	
	
	
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
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$cacheFile exists - ".strlen($data)." bytes</span><br>\n";}
			if(strlen($data)>20){
				echo $tpl->_ENGINE_parse_body($data);
				return;
			}
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>InjectSquid -></span>\n<br>";}
	
	$run=false;
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if($EnableWebProxyStatsAppliance==1){$users->WEBSTATS_APPLIANCE=true;}
	if($users->WEBSTATS_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>WEBSTATS_APPLIANCE -> RUN = TRUE</span>\n<br>";}
		$run=true;}
	if($users->SQUID_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>SQUID_INSTALLED -> RUN = TRUE</span>\n<br>";}
		$run=true;
	}
	if($users->SQUID_REVERSE_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>SQUID_REVERSE_APPLIANCE -> RUN = FALSE</span>\n<br>";}
		$run=false;}
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>run -> $run</span>\n<br>";}
	if(!$run){return;}	
	$inf=trim($sock->getFrameWork("squid.php?isInjectrunning=yes") );
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>inf -> $inf</span>\n<br>";}
	if($inf<>null){
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>inf <> Null</span>\n<br>";}
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
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	if(is_numeric($CPU_NUMBER)){
		if($CPU_NUMBER<2){
			
			$html="<div style='margin-top:15px'>".
					Paragraphe("warning-panneau-64.png", "{performance_issue}","{performance_issue_cpu_number_text}",
							"javascript:Loadjs('artica.license.php')","go_to_section",665,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
	}
	
	
	if($users->CORP_LICENSE){Youtube();return;}
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	
	
		
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	if($EnableKerbAuth==1){
		
		$html="<div style='margin-top:15px'>".
				Paragraphe("warning-panneau-64.png", "Active Directory","{warn_no_license_activedirectory_30days}",
						"javascript:Loadjs('artica.license.php')","go_to_section",665,132,1);
		echo $tpl->_ENGINE_parse_body($html)."</div>";
		
	}
	
	
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	if($LicenseInfos["license_status"]==null){
		$text="{explain_license_free}";
		
	}else{
		$text="{explain_license_order}";
	}
		
	$html="<div style='margin-top:15px'>".
	Paragraphe("license-error-64.png", "{artica_license}",$text, 
	"javascript:Loadjs('artica.license.php')","go_to_section",665,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>".Youtube();
}

function Youtube(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->SQUID_INSTALLED){return;}
	$youtubelink="http://www.youtube.com/playlist?list=PL6GqpiBEyv4q1GqpV5QbdYWbQdyxlWKGW";
	$html="<div style='margin-top:15px'>".
			Paragraphe("youtube-64.png", "{youtube_doc}","{youtube_doc_explain}",
					"javascript:s_PopUpFull('$youtubelink',1024,1024)","go_to_section",665,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";	
	
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
	$EnableBandwithCalculation=$sock->GET_INFO("EnableBandwithCalculation");
	if(!is_numeric($EnableBandwithCalculation)){$EnableBandwithCalculation=1;}
	
	
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
	
	
// --------------------------------------------------------------------------------------	

	if($users->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==1){
			writelogs("Checking squid perf",__FUNCTION__,__FILE__,__LINE__);	
			$cachedTXT=$tpl->_ENGINE_parse_body("{cached}");
			$NOTcachedTXT=$tpl->_ENGINE_parse_body("{not_cached}");
			$today=$tpl->_ENGINE_parse_body("{today}");
			$sql="SELECT SUM( size ) as tsize, cached FROM squid_cache_perfs WHERE DATE_FORMAT( zDate, '%Y-%m-%d' ) = DATE_FORMAT( NOW( ) , '%Y-%m-%d' ) GROUP BY cached LIMIT 0 , 30";
			$results=$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				
				if($ligne["cached"]==1){$cached_size=$ligne["tsize"];}
				if($ligne["cached"]==0){$not_cached_size=$ligne["tsize"];}
			}
				writelogs("Cached: $cached_size not cached: $not_cached_size bytes",__FUNCTION__,__FILE__,__LINE__);
			
			if(($cached_size>0) &&  ($not_cached_size>0)){
				
				
				$sum=$cached_size+$not_cached_size;
				$pourcent=round(($cached_size/$sum)*100);
				$title=$tpl->_ENGINE_parse_body("{cache_performance} $pourcent%");
				$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/squid.cache.perf.today.png",0);
				$gp->xdata[]=$cached_size;
				$gp->ydata[]="$cachedTXT ".FormatBytes($cached_size/1024);	
				$gp->xdata[]=$not_cached_size;
				$gp->ydata[]="$NOTcachedTXT ".FormatBytes($not_cached_size/1024);					
				$gp->width=300;
				$gp->height=120;
				$gp->PieExplode=5;
				$gp->PieLegendHide=true;
				$gp->ViewValues=false;
				$gp->x_title=null;
				$gp->pie();	
				
				if(is_file("ressources/logs/web/squid.cache.perf.today.png")){	
					echo "<div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
					this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed58910\" OnClick=\"javascript:Loadjs('squid.cache.perf.stats.php')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
					<h3 style='text-transform: none;margin-bottom:5px'>$title</h3>
					<div style='font-size:11px;margin-top:-8px'>$today: $cachedTXT: ".FormatBytes($cached_size/1024)." - $NOTcachedTXT ".FormatBytes($not_cached_size/1024)."</div>
					<img src='ressources/logs/web/squid.cache.perf.today.png'>
					</div>";
				}else{
					writelogs("ressources/logs/web/squid.cache.perf.today.png no such file",__FUNCTION__,__FILE__,__LINE__);
				}			
			
			}
			
		}
	}
	
// --------------------------------------------------------------------------------------	

	if($EnableBandwithCalculation==1){
		$targetedfile="ressources/logs/".basename(__FILE__).".bandwithm.png";
		$sql="SELECT DATE_FORMAT(zDate,'%H') as tdate,AVG(download) as tbandwith FROM speedtests 
		WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') 
		GROUP BY DATE_FORMAT(zDate,'%H')
		ORDER BY zDate";
		$results=$q->QUERY_SQL($sql,"artica_events");
		if(mysql_num_rows($results)>1){
			$xtitle=$tpl->javascript_parse_text("{hours}");
			$maintitle=$tpl->javascript_parse_text("{today}: {bandwith}");
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$size=round($ligne["tbandwith"],0);
					$gp->xdata[]=$ligne["thour"];
					$gp->ydata[]=$ligne["tdate"];
					$c++;
					
				}
			
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
			$gp->line_green();	
			if(is_file($targetedfile)){		
				echo "<center><div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" 
				onmouseover=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\" 
				id=\"". md5(time())."\" OnClick=\"javascript:Loadjs('bandwith.stats.php')\" 
				class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$maintitle</h3>
				<img src='$targetedfile'>
				</div></center>";	
			}
			
		}
	}
// --------------------------------------------------------------------------------------		

	
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
				Paragraphe("download-info-64.png", "{NEW_NIGHTLYBUILD}: $nightly"
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
				Paragraphe("download-info-64.png", "{NEW_RELEASE}: $Lastest"
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
	$filecache="ressources/logs/web/cpustats.db";
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
	$highcharts->subtitle="<a href=\"javascript:Loadjs('system.cpustats.php')\" style='text-decoration:underline'>{more_details}</a>";
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
	
	
		$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate, 
		MINUTE(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY `time` ,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d %H') ORDER BY `time`";
		
		$title="{server_load_this_hour}";
		$timetext="{minutes}";
		
	$filecache="ressources/logs/web/INTERFACE_LOAD_AVG.db";
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
	
		$sql="SELECT DATE_FORMAT( zDate, '%Y-%m-%d %H' ) AS tdate, MINUTE( zDate ) AS time, 
				AVG( memory_used ) AS value
				FROM `sys_mem`
				GROUP BY `time` , tdate
				HAVING tdate = DATE_FORMAT( NOW( ) , '%Y-%m-%d %H' )
				ORDER BY `time`";

		$title="{memory_consumption_this_hour}";
		$timetext="{minutes}";

	
	
	$filecache="ressources/logs/web/INTERFACE_LOAD_AVG2.db";
	if(!is_file($filecache)){
		if($GLOBALS["VERBOSE"]){echo "ressources/logs/web/INTERFACE_LOAD_AVG2.db no such file\n<br>";}
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
	$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.blocked.events.php?full-js=yes')\" style='text-decoration:underline'>{more_details}</a>";
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