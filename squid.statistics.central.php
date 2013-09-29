<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["central-infos"])){central_information();exit;}
	if(isset($_GET["panel"])){page();exit;}
	if(isset($_GET["graphique_heure"])){graphique_heure();exit;}
	
	if(isset($_GET["rrd-js"])){rrd_js();exit;}
	if(isset($_POST["rrd-perform"])){rrd_perform();exit;}
	
	if(isset($_GET["squidhour-js"])){squidhour_js();exit;}
	if(isset($_POST["squidhour-perform"])){squidhour_perform();exit;}
tabs();


function rrd_js(){
	$t=time();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	$confirm=$tpl->javascript_parse_text("{run_this_task_now} ?");

	echo "

	var xstart$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('squid_stats_central');
	RefreshTab('squid_main_svc');

}


function start$t(){
if(!confirm('$confirm')){return;}
var XHR = new XHRConnection();
XHR.appendData('rrd-perform','yes');
XHR.sendAndLoad('$page', 'POST',xstart$t);
}
	
start$t();
";

}


function squidhour_js(){
	$t=time();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	header("content-type: application/x-javascript");
	$confirm=$tpl->javascript_parse_text("{run_this_task_now} ?");
	
	echo "
		
	var xstart$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('squid_stats_central');
		
	}		
	
	
	function start$t(){
		if(!confirm('$confirm')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('squidhour-perform','yes');
		XHR.sendAndLoad('$page', 'POST',xstart$t);
	}
			
	start$t();		
	";
	
}



function tabs(){
	$t=time();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$array["panel"]='{panel}';
	$sock=new sockets();
	if(!$users->PROXYTINY_APPLIANCE){
		if(!$sock->SQUID_LOCAL_STATS_DISABLED()){$array["status"]='{status}';}
		//$array["panel-week"]='{this_week}';
		$array["events-squidaccess"]='{realtime_requests}';
		$array["events-mysar"]='{summary}';
	
		$font="style='font-size:14px'";
		if(!$sock->SQUID_LOCAL_STATS_DISABLED()){$array["not_categorized"]='{not_categorized}';}
	}
	
while (list ($num, $ligne) = each ($array) ){
	
		if($num=="panel-week"){
			$html[]= "<li $font><a href=\"squid.traffic.panel.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}	
		
		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"squid.accesslogs.php?table-size=942&url-row=555\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="events-mysar"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"squid.mysar.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="status"){
			$html[]= "<li $font><a href=\"squid.traffic.statistics.php?status=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="days"){
			$html[]= "<li $font><a href=\"squid.traffic.statistics.days.php?day-consumption=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
		if($num=="users"){
			$html[]= "<li $font><a href=\"squid.members.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="week-consumption"){
			$html[]= "<li $font><a href=\"squid.traffic.statistics.week.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="month-consumption"){
			$html[]= "<li $font><a href=\"squid.traffic.statistics.month.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="find"){
			$html[]= "<li $font><a href=\"squid.search.statistics.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="not_categorized"){
			$html[]= "<li $font><a href=\"squid.not-categorized.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="events"){
			$html[]= "<li $font><a href=\"squid.stats.events.php\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
	
		$html[]= "<li $font><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "squid_stats_central",945);
	
}


function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$search=$tpl->javascript_parse_text("{search}");
	
	$p1=Paragraphe32("new_statistics_interface", "new_statistics_interface_text",
			"s_PopUpFull('http://proxy-appliance.org/index.php?cID=326',1024,768,'Statistics');",
		 "help-32.png");
	$p2=Paragraphe32("proxy_statistics_interface", "proxy_statistics_interface_text",
			"document.location.href='logoff.php?goto=miniadm.logon.php';",
			"link-32.png");
	
	$html="
			
	<table style='width:100%'>
	<tr>
		<td valign='top' width=240px>
			<div id='info-gene-$t' style='width:240px' class=form></div>
			<center>
				<hr>
				$p1<br>$p2
			</center>	
		</td>
			<td valign='top'><div id='info-central-$t'></div>
		
		</td>
	</tr>
	</table>
	
	<script>
		LoadAjax('info-central-$t','$page?central-infos=yes&t=$t');
		
		function SearchMember$t(e){
			var pp=encodeURIComponent(document.getElementById('Search-Memb-$t').value);
			if(!checkEnter(e)){return;}
			Loadjs('squid.UserAuthDaysGrouped.php?search-js='+pp);
		}
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function central_information(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TRPTEXT=null;
	$sock=new sockets();
	$processes=unserialize(base64_decode($sock->getFrameWork("squidstats.php?processes-queue=yes")));
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
	if(is_array($processes)){
		$TRP[]="<table style='width:99%' class=form>";
		while (list ($index, $ligne) = each ($processes) ){
			$TTL=$ligne["TTL"];
			$PID=$ligne["PID"];
			$day=$ligne["day"];
			
		
			$TRP[]="<tr>
				<td width=1%><img src='img/preloader.gif'></td>
				<td style='font-weight:bold'>{processing} $day PID:$PID {since} $TTL</td>
				</tr>";
			
		}
		$TRP[]="</table>";
		$TRPTEXT=@implode("\n", $TRP);
	}
	//database-connect-settings-32.png

		$squiddb=Paragraphe32('mysql_statistics_engine','mysql_statistics_engine_params'
				,"blur()","database-connect-settings-32-grey.png");	
	
	if($users->PROXYTINY_APPLIANCE){$squiddb=null;}
		
	
	
	if(!$users->PROXYTINY_APPLIANCE){
	//$tr[]=Paragraphe32("old_statistics_interface", "old_statistics_interface_text", "SquidQuickLinksStatistics();", "status_statistics-22.png");

	
	
	
	if(!$users->PROXYTINY_APPLIANCE){
		
		$tr[]=Paragraphe32("STATISTICS_APPLIANCE","STATISTICS_APPLIANCE_TEXT",
				"javascript:Loadjs('squid.stats-appliance.php')","32-dansguardian-stats.png");
		
		if($users->APP_SQUIDDB_INSTALLED){
			$squiddb=Paragraphe32('mysql_statistics_engine','mysql_statistics_engine_params'
					,"Loadjs('squid.articadb.php')","database-connect-settings-32.png");
				
		}
		
		

	
	if($DisableArticaProxyStatistics==0){
		
		
		
		$tr[]=Paragraphe32('remote_statistics_server',
				'remote_statistics_server_text',"javascript:Loadjs('squid.remotestats.php')",'syslog-32-client.png');
		
		
		$tr[]=Paragraphe32('purge_statistics_database','purge_statistics_database_explain'
				,"Loadjs('squid.artica.statistics.purge.php')",'table-delete-32.png');	
		
		
		$tr[]=table_heures_enretard();
		
		
		$tr[]=$squiddb;
		
		$tr[]=Paragraphe32('remote_mysql_server','remote_mysqlsquidserver_text'
				,"Loadjs('squid.remote-mysql.php')","artica-meta-32.png");
		
		
		
		
		
		$tr[]=Paragraphe32('restore_purged_statistics','restore_purged_statistics_explain'
				,"Loadjs('squid.artica.statistics.restore.php')","32-import.png");		
		
		}
		
		
		$tr[]=Paragraphe32('source_logs','source_logs_squid_text'
				,"Loadjs('squid.logrotate.php')","32-logs.png");		
		
		$tr[]=Paragraphe32('enable_disable_statistics','ARTICA_STATISTICS_TEXT'
				,"Loadjs('squid.artica.statistics.php')","statistics-32.png");
		
		
		
	}
	
	if(!$users->CORP_LICENSE){$more_features="<div class=explain style='font-size:16px;'>{squid_stats_nolicence_explain}</div>";}
	
	
	if($DisableArticaProxyStatistics==1){
		$more_features="<div class=explain style='font-size:16px;'>{squid_stats_disabled_explain}</div>";
		
	}
	
	
	
	}
		
	if($users->PROXYTINY_APPLIANCE){
		//
		$tr[]=Paragraphe32('APP_SARG','APP_SARG_TXT',"Loadjs('sarg.php')","sarg-logo-32.png");
		$tr[]=Paragraphe32('APP_SARG_HOWTO','APP_SARG_HOWTO_TXT',
		"s_PopUpFull('http://proxy-appliance.org/index.php?cID=203',1024,768,'Statistics');","help-32.png");
	}
	
	if($users->URLSNARF_INSTALLED){
		//$tr[]=Paragraphe32('APP_URLSNARF','APP_URLSNARF_TEXT',"Loadjs('urlsnarf.php')","website-32.png");
	}
	
	
	$table=CompileTr2($tr,true);	
	
	$garphs="<div id='graph1-$t' style='height:250px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
	<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","Loadjs('$page?graphique_heure=yes&container=graph1-$t');")."</div>";
	
	$garphsjs="Loadjs('$page?graphique_heure=yes&container=graph1-$t');";
	
	if($sock->SQUID_LOCAL_STATS_DISABLED()){
		$garphs=null;
		$garphsjs=null;
		
	}
	
	$html="
	<div style='font-size:22px;margin-bottom:15px'>{SQUID_STATS}</div>
	$garphs
	$TRPTEXT
	$more_features
	
	<center>
	<div style='margin-top:15px;width:80%'>$table</div>
	</center>
	<script>
		LoadAjax('info-gene-$t','squid.traffic.statistics.php?squid-status-stats=yes');
		$garphsjs
	</script>
	";
	

	echo $tpl->_ENGINE_parse_body($html);
	
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
				,"Loadjs('$page?squidhour-js=yes')","Database32-red.png");
	}
	
}



function graphique_heure(){
	$users=new usersMenus();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.stats.size.hour.db";
	if(!is_file($cacheFile)){
		echo $highcharts->NoreturnedValue(array());
		return;
	}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$currenttime=date("YmdH");
	$table="squidhour_$currenttime";
	$q=new mysql_squid_builder();
	
	$array=unserialize(@file_get_contents($cacheFile));
	if(!is_array($array)){echo $highcharts->NoreturnedValue(array());return;}
	$highcharts->xAxis=$array[0];
	$highcharts->Title="{downloaded_flow_this_hour}";
	$highcharts->yAxisTtitle="{bandwith} KB";
	$highcharts->xAxisTtitle="{minutes}";
	$highcharts->datas=array("{bandwith}"=>$array[1]);
	echo $highcharts->BuildChart();
	
}

function squidhour_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squidhour-repair=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_executed_in_background}");
}
function rrd_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rrd-perform=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_executed_in_background}");	
}