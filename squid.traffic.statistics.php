<?php
	$GLOBALS["AS_ROOT"]=false;
	$dirname=dirname(__FILE__);
	if(count($argv)>0){if(preg_match("#--verbose#", @implode(" ", $argv))){
			$_GET["verbose"]=1;$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE++"]=true;$GLOBALS["FULL_DEBUG"]=true;
			ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		}}
	if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once("$dirname/ressources/class.templates.inc");
	include_once("$dirname/ressources/class.users.menus.inc");
	include_once("$dirname/ressources/class.squid.inc");
	include_once("$dirname/ressources/class.status.inc");
	include_once("$dirname/ressources/class.artica.graphs.inc");
	include_once("$dirname/ressources/class.mysql.syslogs.inc");
	
	if($GLOBALS["AS_ROOT"]){
		include_once("/usr/share/artica-postfix/framework/class.unix.inc");
		$unix=new unix();
		if(!$unix->is_socket("/var/run/mysqld/articadb.sock")){die();}
	}
	
	
	if(!$GLOBALS["AS_ROOT"]){
		$users=new usersMenus();
		if(!$users->AsWebStatisticsAdministrator){die("Permission denied");}
	}
	
	if($argv[1]=="squid-status-stats"){squid_status_stats();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["squid-general-status"])){general_status();exit;}
	if(isset($_GET["squid-status-stats"])){squid_status_stats();exit;}
	
	if(isset($_GET["squid-status-graphs"])){general_status_graphs();exit;}
	if(isset($_GET["status-graph-flow"])){general_status_graphs_flow();exit;}
	
	
	if(isset($_GET["squid-cache-flow-performance"])){general_status_cache_graphs();exit;}
	
	if(isset($_GET["day-consumption"])){day_consumption();exit;}
	
	if(isset($_GET["now"])){now();exit;}
	if(isset($_GET["now-search"])){now_search_list();exit;}
	if(isset($_GET["now-section"])){now_section();exit;}
	
	
	
tabs();


function tabs(){
	
	$page=CurrentPageName();
	
	$tpl=new templates();
	$array["panel"]='{panel}';
	$array["status"]='{status}';
	$array["now"]='{now}';
	$array["find"]='{query}';
	$array["day-consumption"]='{days}';
	$array["week-consumption"]='{week}';
	$array["month-consumption"]='{month}';
	$array["not_categorized"]='{not_categorized}';
	$array["events"]='{events}';
	
	
	

while (list ($num, $ligne) = each ($array) ){
	
		if($num=="panel"){
			$html[]= "<li><a href=\"squid.traffic.panel.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}	
	
		if($num=="day-consumption"){
			$html[]= "<li><a href=\"squid.traffic.statistics.days.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="week-consumption"){
			$html[]= "<li><a href=\"squid.traffic.statistics.week.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="month-consumption"){
			$html[]= "<li><a href=\"squid.traffic.statistics.month.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="find"){
			$html[]= "<li><a href=\"squid.search.statistics.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="not_categorized"){
			$html[]= "<li><a href=\"squid.not-categorized.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="events"){
			$html[]= "<li><a href=\"squid.stats.events.php\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
	
		$html[]= "<li><a href=\"$page?$num\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_stats_consumption style='width:100%;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_stats_consumption').tabs();
			
			
			});
		</script>");		
}

function status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><div id='squid-general-status'></div></td>
		<td valign='top' width=99%><div id='squid-status-graphs' style='padding:15px'></div></td>
	</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('squid-general-status','$page?squid-general-status=yes');
	
	</script>
	";
	
	echo $html;
}

function now(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["now-section"]='{this_hour}';
	$array["cache-log"]='{proxy_service_events}';
	$array["now-requests"]='{realtime_requests}';
	if($users->WEBSTATS_APPLIANCE){unset($array["cache-log"]);}
	
	

while (list ($num, $ligne) = each ($array) ){
	
		if($num=="cache-log"){
			$html[]= "<li><a href=\"squid.cachelogs.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	
	
		if($num=="now-requests"){
			$html[]= "<li><a href=\"squid.accesslogs.php\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		$html[]= "<li><a href=\"$page?$num\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_now_stats style='width:100%;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_now_stats').tabs();
			
			
			});
		</script>");		
}	
	




function now_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$categories=$tpl->_ENGINE_parse_body("{categories}");
	$t=time();
	$html="
	<div style='margin:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?now-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :53, sortable : true, align: 'left'},
		{display: '$country', name : 'country', width : 92, sortable : false, align: 'left'},
		{display: '$webservers', name : 'sitename', width : 135, sortable : false, align: 'left'},
		{display: '$member', name : 'member', width : 180, sortable : false, align: 'left'},
		{display: '$url', name : 'uri', width : 243, sortable : false, align: 'left'},
		{display: '$size', name : 'Querysize', width : 60, sortable : true, align: 'left'}

		],
		
		
	
	searchitems : [
		{display: '$webservers', name : 'sitename'},
		{display: 'MAC', name : 'MAC'},
		{display: '$hostname', name : 'hostname'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 855,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});



function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
			LoadAjax('table-1-selected','$page?familysite-show='+id);
		}
	}
	 
	$('table-1-selected').remove();
	$('flex1').remove();		 

</script>
	
	
	";
	
	echo $html;
	
}



function now_search_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$search=$_GET["search"];
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="squidhour_".date("YmdH");
	$page=1;
	$ORDER="ORDER BY ID DESC";	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`uri` LIKE '$search')";
		$QUERY="WHERE (`uri` LIKE '$search')";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
		
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
		
	
	
	if($q->COUNT_ROWS($table)==0){return;}
	
	$sql="SELECT *,DATE_FORMAT(zDate,'%H:%i:%s') as ttime  FROM `$table` $QUERY $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);
	
//&nbsp;|&nbsp;{$ligne["CLIENT"]}&nbsp;|&nbsp;{$ligne["uid"]}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	while ($ligne = mysql_fetch_assoc($results)) {
		
		
		if($ligne["QuerySize"]>1024){
			$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
		}else{
			$ligne["QuerySize"]="{$ligne["QuerySize"]} Bytes";
		}
	
		$c++;
		$today=date("Y-m-d");
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		$js="squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite=$familysite&day=$today";
		
		if($ligne["uid"]<>null){
			$uid="&nbsp;|&nbsp;{$ligne["uid"]}";
		}
		
		
		
		if($ligne["CLIENT"]<>null){
			$ip="{$ligne["CLIENT"]}";
			$textname=$ligne["CLIENT"];
			$fieldfilter="CLIENT";
		}
			
		if($ligne["hostname"]<>null){
			$ip="{$ligne["hostname"]}";
			$textname=$ligne["hostname"];
			$fieldfilter="hostname";
		}		
				
		
		if($ligne["MAC"]<>null){
			$ip="{$ligne["MAC"]}";
			$fieldfilter="MAC";
		}
		
		
		$ip="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.now.php?field=$fieldfilter&value=$ip&NameTitle=". base64_encode($textname)."');\" style='text-decoration:underline'>$textname</a>";
		
		$data['rows'][] = array(
			'id' => "{$ligne["ID"]}",
			'cell' => array($ligne["ttime"], $ligne["country"], 
		
		
		
		"<a href=\"#\" style='text-decoration:underline' OnClick=\"javascript:Loadjs('$js');\">{$ligne["sitename"]}</a>",
		"$ip$uid",$ligne["uri"],$ligne["QuerySize"],"add")
		);
	}
	echo json_encode($data);	
}

function general_status(){
	if(CACHE_SESSION_GET(__FUNCTION__,__FILE__)){return;}
	$page=CurrentPageName();
	$tpl=new templates();		

	$stylehref="style='font-size:14px;font-weight:bold;text-decoration:underline'";
	$img="img/server-256.png";
	$html="
	<div class=form>
	<center style='margin:5px'>
	<img src='$img'>
	</center>
	<div id='squid-status-stats'></div>
	
	<p>&nbsp;</p>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%>". imgtootltip("charts-plus-32.png","{downloaded_flow}","SquidFlowSizeQuery('size')")."</td>
		<td valign='top' style='font-size:14px' width=100%><a href=\"javascript:blur();\" OnClick=\"javascript:SquidFlowSizeQuery('size')\"$stylehref>{downloaded_flow}</a></td>
	</tr>
	<tr>
		<td width=1%>". imgtootltip("charts-plus-32.png","{requests}","SquidFlowSizeQuery('req')")."</td>
		<td valign='top' style='font-size:14px' width=100%><a href=\"javascript:blur();\" OnClick=\"javascript:SquidFlowSizeQuery('req')\"$stylehref>{requests}</a></td>
	</tr>		
	</tbody>
	</table>	
	
	<script>
		LoadAjax('squid-status-stats','$page?squid-status-stats=yes');	
		LoadAjax('squid-status-graphs','$page?squid-status-graphs=yes');
		
	</script>
	</div>
	
	";
	
	CACHE_SESSION_SET(__FUNCTION__, __FILE__,$tpl->_ENGINE_parse_body($html));
	
}

function squid_status_stats(){
	

	
	$off="<script>UnlockPage();</script>";
	
	if(!$GLOBALS["AS_ROOT"]){
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/traffic.statistics.html";
		if(is_file($cachefile)){
			$tpl=new templates();
			$cacheContent=@file_get_contents($cachefile);
			if(strlen($cacheContent)>20){
				echo $tpl->_ENGINE_parse_body(@file_get_contents($cachefile)).$off;
				return;
				}
			}
	}
	
	
	if(CACHE_SESSION_GET(__FUNCTION__, __FILE__)){return;}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." Loading classes<br>\n";}
	$sock=new sockets();
	$users=new usersMenus();
	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}	
	if($EnableRemoteStatisticsAppliance==1){return;}
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}	
	$MalwarePatrolDatabasesCount=$sock->getFrameWork("cmd.php?MalwarePatrolDatabasesCount=yes");
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." Loading mysql_storelogs()<br>\n";}
	$syslogs=new mysql_storelogs();
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." Count accesslogs<br>\n";}
	$SyslogsFiles=$syslogs->COUNT_ROWS("accesslogs");
	
	
	$TR_ACCESSLOG="
	<tr>
	<td width=1%><img src='img/arrow-right-16.png'></td>
	<td valign='top' $mouse style='font-size:12px;text-decoration:underline' 
		OnClick=\"javascript:Loadjs('squid.accesses.rotate.php')\"><b><span style='font-size:12px'>$SyslogsFiles</span></b><span style='font-size:12px'> {access_logs}</td>
	</tr>";
	
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	
	
	if($users->PROXYTINY_APPLIANCE){$DisableArticaProxyStatistics=1;}
	
	
	if($DisableArticaProxyStatistics==0){
		$websitesnums=$q->COUNT_ROWS("visited_sites");
		$websitesnums=numberFormat($websitesnums,0,""," ");	
		$sql="DELETE FROM categorize WHERE LENGTH(pattern)=0";
		$q->QUERY_SQL($sql);
		$export=$q->COUNT_ROWS("categorize");
		$export=numberFormat($export,0,""," ");	
	}	
	
	$catz=new mysql_catz();
	$categories=$catz->COUNT_CATEGORIES();
	$categories=numberFormat($categories,0,""," ");
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." categories= $categories<br>\n";}
	
	
	
	
	$YourItems=$q->COUNT_CATEGORIES();
	$YourItems=numberFormat($YourItems,0,""," ");
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." LIST_TABLES_CATEGORIES()<br>\n";}
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." tablescat=$tablescat<br>\n";}
	$tablescatNUM=numberFormat(count($tablescat),0,""," ");

	
	
	if($DisableArticaProxyStatistics==0){
		if($GLOBALS["VERBOSE"]){echo __LINE__." EVENTS_SUM()<br>\n";}
		$requests=$q->EVENTS_SUM();
		$requests=numberFormat($requests,0,""," ");	
		if($GLOBALS["VERBOSE"]){echo __LINE__." requests = $requests<br>\n";}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." no_license -> translate<br>\n";}
	$nolicense=$tpl->_ENGINE_parse_body("{no_license}");
	$PhishingURIS=$q->COUNT_ROWS("categoryuris_phishing");
	$PhishingURIS=numberFormat($PhishingURIS,0,""," ");	
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." >COUNT_ROWS('categoryuris_malware')<br>\n";}
	$MalwaresURIS=$q->COUNT_ROWS("categoryuris_malware");
	$MalwaresURIS=numberFormat($MalwaresURIS,0,""," ");	

	if(!$users->CORP_LICENSE){
		$PhishingURIS=$nolicense;
		$MalwaresURIS=$nolicense;
	}
	
	if($DisableArticaProxyStatistics==0){

	
		if($EnableMacAddressFilter==1){
			$Computers=$q->COUNT_ROWS("webfilters_nodes");
			$Computers=numberFormat($Computers,0,""," ");
			$nodes="
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.nodes.php',true)\"><b><span style='font-size:12px'>$Computers</span></b><span style='font-size:12px'> {computers}</td>
			</tr>";
		
		}else{
			$Computers=$q->COUNT_ROWS("UserAutDB");
			$Computers=numberFormat($Computers,0,""," ");
			
			$nodes="
			<tr>
			<td width=1%><img src='img/arrow-right-16.png'></td>
			<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.UserAutDB.php')\"><b><span style='font-size:12px'>$Computers</span></b><span style='font-size:12px'> {clients}</td>
			</tr>";			
			
		}	
		
		if(!$users->CORP_LICENSE){
			$license_inactive="<br><strong style='font-size:11px;font-weight:bolder;color:#BA1010'>{license_inactive}</strong>";
			
		}
	
		if(!$q->TABLE_EXISTS("tables_day")){$q->CheckTables();}
		$DAYSNumbers=$q->COUNT_ROWS("tables_day");
		if($GLOBALS["VERBOSE"]){echo __LINE__." DAYSNumbers = $DAYSNumbers<br>\n";}
		//$GLOBALS["FULL_DEBUG"]
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(totalsize) as tsize FROM tables_day"));
		$totalsize=FormatBytes($ligne["tsize"]/1024);
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT AVG(cache_perfs) as pourc FROM tables_day"));
		$pref=round($ligne["pourc"]);	
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM visited_sites WHERE LENGTH(category)=0"));
		$websitesnumsNot=numberFormat($ligne["tcount"],0,""," ");
	
		if($GLOBALS["VERBOSE"]){echo __LINE__." SELECT count(youtubeid),youtubeid FROM `youtube_dayz` GROUP BY youtubeid<br>\n";}
		$results=$q->QUERY_SQL("SELECT count(youtubeid),youtubeid FROM `youtube_dayz` GROUP BY youtubeid");
		$youtube_objects=mysql_num_rows($results);
		$youtube_objects=numberFormat($youtube_objects,0,""," ");
		
		$CachePermformance=$q->CachePerfHour();
		
		
		if($GLOBALS["VERBOSE"]){echo __LINE__." CachePermformance = $CachePermformance<br>\n";}
		
		if($CachePermformance>-1){
			$color="#E01313";
			if($CachePermformance>20){$color="#6DBB6A";}
			$cachePerfText="
			<tr>
			<td width=1%><img src='img/arrow-right-16.png'></td>
			<td valign='top' style='font-size:12px;'><b style='color:$color'>$CachePermformance%</span></b><span style='font-size:12px'> {cache_performance} ({now})</td>
			</tr>
			";
			
		}	
	
	


	if($SquidActHasReverse==1){$TR_CAT_NUMBER=null;}	
	$TR_CAT_NUMBER="
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.categories.php')\"><b><span style='font-size:12px'>$categories</span></b><span style='font-size:12px'> {websites_categorized}$license_inactive</td>
	</tr>
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.categories.php')\"><span style='font-size:12px'>{youritems}: <b>$YourItems</span></b><span style='font-size:12px'></td>
	</tr>";

	
	$TR_CATZ="	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td width=99% valign='top' style='font-size:12px;text-decoration:underline' 
		$mouse OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?js=yes&with-purge=yes')\"><b><span style='font-size:12px'>$DAYSNumbers</span></b><span style='font-size:12px'> {daysOfStatistics}</td>
	</tr>
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' style='font-size:12px'><b><span style='font-size:12px'>$requests</span></b><span style='font-size:12px'> {requests}</td>
	</tr>
	$nodes
		
	$TR_CAT_NUMBER
";
	
	$TR_YOUTUBE="	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline'
		$mouse OnClick=\"javascript:Loadjs('squid.youtube.all.php')\"
		><b><span style='font-size:12px'>$youtube_objects</span></b><span style='font-size:12px'> Youtube {objects}</td>
	</tr>";
	
	
	
		if($DisableArticaProxyStatistics==1){$TR_YOUTUBE=null;$TR_CATZ=null;}
		if($SquidActHasReverse==1){$TR_YOUTUBE=null;}	
	
	
	
	$submenu="	
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' style='font-size:12px'><b><span style='font-size:12px'>$totalsize</span></b><span style='font-size:12px'> {downloaded_flow}</td>
	</tr>
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' style='font-size:12px'><b><span style='font-size:12px'>$pref%</span></b><span style='font-size:12px'> {cache_performance}</td>
	</tr>
	
	$cachePerfText";
	
	
	$main_table="
		
		$TR_CATZ
		$TR_YOUTUBE	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$PhishingURIS</span></b><span style='font-size:12px'> {phishing_uris}</td>
	</tr>	
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$MalwaresURIS</span></b><span style='font-size:12px'> {viruses_uris}</td>
	</tr>
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$MalwarePatrolDatabasesCount</span></b><span style='font-size:12px'> Malware Patrol</td>
	</tr>	
					
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.visited.php?onlyNot=yes')\"><b><span style='font-size:12px'>$websitesnumsNot</span></b><span style='font-size:12px'> {not_categorized}</td>
	</tr>	
				
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.categories.php')\"><b><span style='font-size:12px'>$tablescatNUM</span></b><span style='font-size:12px'> {categories}</td>
	</tr>	
	
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.categories.toexport.php')\"><b><span style='font-size:12px'>$export</span></b><span style='font-size:12px'> {websites_to_export}</td>
	</tr>";
	
	}	

	if($DisableArticaProxyStatistics==1){	
		$main_table="	
			
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$PhishingURIS</span></b><span style='font-size:12px'> {phishing_uris}</td>
			</tr>	
			
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$MalwaresURIS</span></b><span style='font-size:12px'> {viruses_uris}</td>
			</tr>
			
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:12px;text-decoration:underline' OnClick=\"blur()\"><b><span style='font-size:12px'>$MalwarePatrolDatabasesCount</span></b><span style='font-size:12px'> Malware Patrol</td>
			</tr>";					
	
		
	}
	
	$addwebsites="
		<tr>
			<td width=1%><img src='img/plus-16.png'></td>
			<td valign='top' $mouse style='font-size:12px;text-decoration:underline' 
			OnClick=\"javascript:Loadjs('squid.visited.php?add-www=yes')\"><b><span style='font-size:12px'>{categorize_websites}</span></b><span style='font-size:12px'></td>
		</tr>	";
	
	if($users->PROXYTINY_APPLIANCE ){$addwebsites=null;$submenu=null;}
	
$html="
<table style='width:100%'>
	<tbody>
	$TR_ACCESSLOG
	$main_table	
	$submenu
	$addwebsites
	</tbody>
	</table>
$off";
if($GLOBALS["VERBOSE"]){echo __LINE__." tpl->_ENGINE_parse_body<br>\n";}
$html=$tpl->_ENGINE_parse_body($html);
if(!$GLOBALS["AS_ROOT"]){
	CACHE_SESSION_SET(__FUNCTION__, __FILE__,$html);
}

	
}

function general_status_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	
	if(isset($_GET["from"])){
		$filter="zDate>='{$_GET["from"]}' AND zDate<='{$_GET["to"]}'";
		$selected_date="{from_date} {$_GET["from"]} - {to_date} {$_GET["to"]}";
		$default_from_date=$_GET["from"];
		$default_to_date=$_GET["to"];
		$file_prefix="$default_from_date-$default_to_date";
	}
	
	if($_GET["type"]<>null){
		$type=$_GET["type"];
		if($_GET["type"]=="req"){
			$field="requests as totalsize";
			$prefix_title="{requests}";
			$hasSize=false;
		}
	}

	if($default_from_date==null){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 30 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$default_from_date=$ligne["tdate"];
	}
	
	if($default_to_date==null){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$default_to_date=$ligne["tdate"];
	}
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=$ligne["tdate"];	
	
	
	$html="<div id='$t-1' style='height:350px'><center><img src='img/wait-clock.gif'></center></div>
		<table style='margin-top:10px' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{from_date}:</td>
		<td>". field_date('from_date1',$default_from_date,"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>
		
		<td class=legend nowrap>{to_date}:</td>
		<td>". field_date('to_date1',$default_to_date,"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>
		<td width=1%>". button("{apply}","SquidFlowSizeQuery('$type')")."</td>
	</tr>
	</table>
	
	
	
	
	<script>
		function SquidFlowSizeQuery(type){
			if(!type){type='';}
			var from=document.getElementById('from_date1').value;
			var to=document.getElementById('to_date1').value;
			LoadAjax('squid-status-graphs','$page?squid-status-graphs=yes&from='+from+'&to='+to+'&type='+type);
		
		}
	
	
		function StartGraph1$t(){
			Loadjs('$page?status-graph-flow=yes&id=$t-1&from={$_GET["from"]}&to={$_GET["to"]}&type={$_GET["type"]}');
			setTimeout(\"StartGraph2$t()\",2000);
		}
		

		StartGraph1$t();
		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function general_status_graphs_flow(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$selected_date="{last_30days}";
	$filter="zDate>DATE_SUB(NOW(),INTERVAL 30 DAY) AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$file_prefix="default";
	$type='size';
	$field="totalsize";
	$prefix_title="{downloaded_flow} (MB)";
	$hasSize=true;
	if($_GET["from"]==null){unset($_GET["from"]);}
	if($_GET["type"]==null){unset($_GET["type"]);}
	
	if(isset($_GET["from"])){
		$filter="zDate>='{$_GET["from"]}' AND zDate<='{$_GET["to"]}'";
		$selected_date="{from_date} {$_GET["from"]} - {to_date} {$_GET["to"]}";
		$default_from_date=$_GET["from"];
		$default_to_date=$_GET["to"];
		$file_prefix="$default_from_date-$default_to_date";
	}
	
	if($_GET["type"]<>null){
		$type=$_GET["type"];
		if($_GET["type"]=="req"){
			$field="requests as totalsize";
			$prefix_title="{requests}";
			$hasSize=false;
		}
	}
	
	
	$sql="SELECT $field,DATE_FORMAT(zDate,'%d') as tdate FROM tables_day WHERE $filter ORDER BY zDate";
	
	$results=$q->QUERY_SQL($sql);

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		if($hasSize){$ydata[]=round(($ligne["totalsize"]/1024)/1000);}else{$ydata[]=$ligne["totalsize"];}
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$prefix_title." - ".$selected_date;
	$highcharts->yAxisTtitle="{bandwith} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{bandwith}"=>$ydata);
	
	echo $highcharts->BuildChart();
}



function general_status_cache_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();		
	
	
	
	$q=new mysql_squid_builder();	
	$selected_date="{last_30days}";
	$filter="zDate>DATE_SUB(NOW(),INTERVAL 30 DAY) AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$file_prefix="default";
	
	if($_GET["from"]<>null){
		$filter="zDate>='{$_GET["from"]}' AND zDate<='{$_GET["to"]}'";
		$selected_date="{from_date} {$_GET["from"]} - {to_date} {$_GET["to"]}";
		$default_from_date=$_GET["from"];
		$default_to_date=$_GET["to"];
		$file_prefix="$default_from_date-$default_to_date";
	}
	
	if($_GET["type"]<>null){
		if($_GET["type"]=="req"){
			$field="requests as totalsize";
			$prefix_title="{requests}";
			$hasSize=false;
		}
	}	
	
	
	$sql="SELECT size_cached as totalsize,DATE_FORMAT(zDate,'%d') as tdate FROM tables_day WHERE $filter ORDER BY zDate";
	
	$results=$q->QUERY_SQL($sql);

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=round(($ligne["totalsize"]/1024)/1000);
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{cache} (MB) /{days} - $selected_date";
	$highcharts->yAxisTtitle="{bandwith} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{bandwith}"=>$ydata);
	
	echo $highcharts->BuildChart();	
	
	
	
}