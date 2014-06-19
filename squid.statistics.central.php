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
	if(isset($_GET["squid-hour-tables"])){squidhour_tables();exit;}
	if(isset($_GET["squid-hour-tables-rows"])){squidhour_tables_rows();exit;}
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
	$tables=$tpl->javascript_parse_text("{squidhour_not_scanned}");

	echo "YahooWin2('770','$page?squid-hour-tables=yes','$tables',true);";

}

function squidhour_tables(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=520;
	$q=new mysql_squid_builder();
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_group}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$title=$tpl->_ENGINE_parse_body("{squidhour_not_scanned}");
	$table=$tpl->_ENGINE_parse_body("{table}");
	$ask_delete_gorup=$tpl->javascript_parse_text("{inputbox delete group}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	
$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?squid-hour-tables-rows&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'b', width :140, sortable : false, align: 'left'},
	{display: '$table', name : 'c', width :427, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'action', width :120, sortable : false, align: 'left'},
	
	],
	
	
	searchitems : [
	{display: '$table', name : 'c'},
	
	],
	sortname: 'c',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	});
});
</script>
";
echo $html;

}

function squidhour_tables_rows(){
	$q=new mysql_squid_builder();
	$Mypage=CurrentPageName();
	$tpl=new templates();
	
	if(!isset($_GET["view-table"])){$_GET["view-table"]=$q->HIER();}
	$search='%';
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$table="(SELECT table_name as c FROM information_schema.tables WHERE 
			table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%') as t";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$linkZoom="<a href=\"javascript:Loadjs('squid.blocked.statistics.days.php?Zoom-js={$ligne['zMD5']}&ID={$ligne['zMD5']}&table=$table&key=zMD5');\" style='font-size:12px;text-decoration:underline'>";
		$table=$ligne["c"];
		$time=$q->TIME_FROM_HOUR_TEMP_TABLE($table);
		$date=date("Y-m-d H:i:s",$time);
		$sum=$q->COUNT_ROWS($table);
		$sum=FormatNumber($sum);
		$data['rows'][] = array(
			'id' => $table,
			'cell' => array($date,$table,$sum,'')
		);
	}
	echo json_encode($data);
	
	}	
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function squidhour_js_perform(){
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
	$array["mysql_statistics_engine"]='{mysql_statistics_engine}';
	$array["status-1"]='{status}';
	$array["schedule-1"]='{schedule}';
	$array["stats_admin_events"]='{events}';
	
	
	$sock=new sockets();
	if(!$users->PROXYTINY_APPLIANCE){
		if(!$sock->SQUID_LOCAL_STATS_DISABLED()){$array["status"]='{globally}';}
		//$array["panel-week"]='{this_week}';
		//$array["events-squidaccess"]='{realtime_requests}';
		$array["events-mysar"]='{summary}';
	
		$font="style='font-size:16px'";
		if(!$sock->SQUID_LOCAL_STATS_DISABLED()){$array["not_categorized"]='{not_categorized}';}
	}
	
while (list ($num, $ligne) = each ($array) ){
	
		if($num=="panel-week"){
			$html[]= "<li $font><a href=\"squid.traffic.panel.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}	
		
		if($num=="stats_admin_events"){
			$html[]= "<li $font><a href=\"stats.admin.events.php?$num\"><span>$ligne</span></a></li>\n";
			continue;			
		}
		
		if($num=="status-1"){
			$html[]= "<li $font><a href=\"squid.statistics.tasks-progress.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}	
		if($num=="schedule-1"){
			$html[]= "<li $font><a href=\"squid.databases.schedules.php?TaskType=53\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="mysql_statistics_engine"){
			$html[]= "<li $font><a href=\"squid.articadb.php?tabs=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="parameters"){
			$html[]= "<li $font><a href=\"squid.statistics.parameters.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"squid.accesslogs.php?table-size=942&url-row=518\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
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
	
	
	echo build_artica_tabs($html, "squid_stats_central",1100)."
			<script>LeftDesign('statistics-white-256-opac20.png');</script>";
	
}


function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$search=$tpl->javascript_parse_text("{search}");
	$sock=new sockets();
	$p1=Paragraphe32("new_statistics_interface", "new_statistics_interface_text",
			"s_PopUpFull('http://proxy-appliance.org/index.php?cID=326',1024,768,'Statistics');",
		 "help-32.png");
	$p2=Paragraphe32("proxy_statistics_interface", "proxy_statistics_interface_text",
			"document.location.href='logoff.php?goto=miniadm.logon.php';",
			"link-32.png");
	
	
	$p3=status_remote_mysql_server();
	
	$p4=status_category_database();
	
	
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){
		$p2=null;
		$p1=null;
		$p4=null;
	}
	
	$html="
			
	<table style='width:100%'>
	<tr>
		<td valign='top' width=240px>
			<div id='info-gene-$t' style='width:240px' class=form></div>
			<center>
				<hr>
				$p3$p4
				<br>
				$p1<br>$p2
			</center>	
		</td>
			<td valign='top'><div id='info-central-$t'></div>
		
		</td>
	</tr>
	</table>
	
	<script>
		LoadAjax('info-central-$t','$page?central-infos=yes&t=$t',true);
		
		function SearchMember$t(e){
			var pp=encodeURIComponent(document.getElementById('Search-Memb-$t').value);
			if(!checkEnter(e)){return;}
			Loadjs('squid.UserAuthDaysGrouped.php?search-js='+pp,true);
		}
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function status_category_database(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){return;}
	
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		
	$LOCAL_VERSION=$CATZ_ARRAY["TIME"];
	$title=$tpl->_ENGINE_parse_body("{APP_ARTICADB}");
	$q=new mysql_catz();
	$categories=$q->COUNT_CATEGORIES();
	if(!is_numeric($categories)){$categories=0;}
	$categories=numberFormat($categories,0,""," ");
	
	
	
	return Paragraphe32("noacco:$title", "
	<strong>Version</strong>:&nbsp;v.$LOCAL_VERSION<br>
	<strong>{items}</strong>:&nbsp;$categories<br>","Loadjs('squid.categories.php?onlyDB=yes',true)","database-link-32.png");
	
}

function status_remote_mysql_server(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	if(!$q->BD_CONNECT()){
		return Paragraphe32("mysql_error", "noacco:mysql://$q->mysql_server:$q->mysql_port<br>$q->mysql_error","","database-error-32.png");
	}else{
		$results=$q->EXECUTE_SQL("SHOW STATUS");
		
		if(!$q->ok){echo $q->mysql_error_html();}
		while ($ligne = mysql_fetch_assoc($results)) { $ARRAY[$ligne["Variable_name"]]=$ligne["Value"]; }
		
		$time=time()-$ARRAY["Uptime"];
		$Uptime=distanceOfTimeInWords($time,time());
		$Threads_connected=$ARRAY["Threads_connected"];
		$Connections=$ARRAY["Connections"];
		$Connections=FormatNumber($Connections);
		$MySqlServer="$q->mysql_server:$q->mysql_port";
		if($q->mysql_server){$MySqlServer=$tpl->_ENGINE_parse_body("{local_database}");}
		
		return Paragraphe32("noacco:$MySqlServer", "{running} {since} $Uptime<br>Threads: $Threads_connected<br>{connections}:$Connections	
				","","database-link-32.png");
	}
	
	
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
	$APP_SQUIDDB_INSTALLED=trim($sock->getFrameWork("squid.php?IS_APP_SQUIDDB_INSTALLED=yes"));
	
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
		
		$tr[]=Paragraphe32('remote_statistics_server','remote_statistics_server_text',"javascript:Loadjs('squid.stats-appliance.php',true)",'syslog-32-client.png');
		

		if($DisableArticaProxyStatistics==0){
			$tr[]=Paragraphe32('import_squid_logs','import_squid_logs_explain',"Loadjs('squid.logs.import.php',true)",'32-import.png');
		}

		
		$tr[]=Paragraphe32('APP_ARTICADB','APP_ARTICADB_TEXT',"Loadjs('squid.categories.php?onlyDB=yes',true)",'32-categories.png');

	
	if($DisableArticaProxyStatistics==0){
			
		$tr[]=table_heures_enretard();
		$tr[]=Paragraphe32('remote_mysql_server','remote_mysqlsquidserver_text',"Loadjs('squid.remote-mysql.php',true)","artica-meta-32.png");
		$tr[]=Paragraphe32('restore_purged_statistics','restore_purged_statistics_explain',"Loadjs('squid.artica.statistics.restore.php',true)","32-import.png");		
		
		}
		
		
		$tr[]=Paragraphe32('source_logs','source_logs_squid_text'
				,"Loadjs('squid.logrotate.php',true)","32-logs.png");		
		
		$tr[]=Paragraphe32('enable_disable_statistics','ARTICA_STATISTICS_TEXT'
				,"Loadjs('squid.artica.statistics.php',true)","statistics-32.png");
		
		
		
	}
	
	if(!$users->CORP_LICENSE){$more_features="<div class=explain style='font-size:16px;'>{squid_stats_nolicence_explain}</div>";}
	
	
	if($DisableArticaProxyStatistics==1){
		$more_features="<div class=explain style='font-size:16px;'>{squid_stats_disabled_explain}</div>";
		
	}
	
	
	
	}
		
	
		//
	$tr[]=Paragraphe32('APP_SARG','APP_SARG_TXT',"Loadjs('sarg.php',true)","sarg-logo-32.png");
	$tr[]=Paragraphe32('APP_SARG_HOWTO','APP_SARG_HOWTO_TXT',
		"s_PopUpFull('http://proxy-appliance.org/index.php?cID=203',1024,768,'Statistics');","help-32.png");
	
	
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
	
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){
		$garphs="<div id='graph1-$t' style='height:250px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
		<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","Loadjs('$page?graphique_heure=yes&container=graph1-$t');")."</div>";
		
		$garphsjs="Loadjs('$page?graphique_heure=yes&container=graph1-$t');";
		$more_features=null;}
	
	$html="
	<div style='font-size:22px;margin-bottom:15px'>{SQUID_STATS}</div>
	$garphs
	$TRPTEXT
	$more_features
	
	<center>
	<div style='margin-top:15px;width:80%'>$table</div>
	</center>
	<script>
		LoadAjax('info-gene-$t','squid.traffic.statistics.php?squid-status-stats=yes',true);
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