<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}
if(isset($_GET["page"])){table();exit;}
if(isset($_GET["search"])){search();exit;}

page();

function page(){
	$t=time();
	$page=CurrentPageName();
	echo "<div id='$t'></div>
	
	<script>
		$('#ARTICA_META_MAIN_TABLE').remove();
		LoadAjax('$t','$page?page=yes');
	</script>
	
	";
	
	
}

function table(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	$disks=$tpl->javascript_parse_text("{disks}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$packages=$tpl->javascript_parse_text("{packages}");
	$switch=$tpl->javascript_parse_text("{switch}");
	$new_server=$tpl->javascript_parse_text("{new_server}");
	$software=$tpl->javascript_parse_text("{software}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");
	
	
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("metahosts")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("metahosts")){echo FATAL_ERROR_SHOW_128("MySQL Error, table metahosts does not exists...");return;}
	
	
	$sql="SELECT version FROM metahosts GROUP BY version ORDER BY version";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(stripos($q->mysql_error, "crashed and should be repaired")>0){
			$button="<center style='margin:20px'>".button("{repair_table}","Loadjs('mysql.repair.progress.php?table=metahosts&database=articameta')",40)."</center>";
			
		}
		echo FATAL_ERROR_SHOW_128(__FUNCTION__."/".__LINE__."<br>".$q->mysql_error.$button);
		return;
	}
	
	$ARTICAVER=trim(@file_get_contents("VERSION"));
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["version"]=trim($ligne["version"]);
		if($ligne["version"]==null){continue;}
		$VVERS[]="\t{display: '$version: {$ligne["version"]}', name : '{$ligne["version"]}'}";
	}
	$proxy=0;
	$sql="SELECT squidver,PROXY FROM metahosts GROUP BY squidver,PROXY ORDER BY squidver";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(stripos($q->mysql_error, "crashed and should be repaired")>0){
			$button="<center style='margin:20px'>".button("{repair_table}","Loadjs('mysql.repair.progress.php?table=metahosts&database=articameta')",40)."</center>";
				
		}
		echo FATAL_ERROR_SHOW_128(__FUNCTION__."/".__LINE__."<br>".$q->mysql_error.$button);
		return;
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["PROXY"]==0){continue;}
		$ligne["squidver"]=trim($ligne["squidver"]);
		$proxy++;
		$VVERS[]="\t{display: 'Proxy $version: {$ligne["squidver"]}', name : 'prxy{$ligne["squidver"]}'}";
	}	
	
	
	$clientsNumber=$q->COUNT_ROWS("metahosts");
	$policies_button="{name: '<strong style=font-size:18px>$policies</strong>', bclass: 'Settings', onpress : Policies$t},";
	if($proxy>0){
		$proxy_whitelist="{name: '<strong style=font-size:18px>$global_whitelist</strong>', bclass: 'export', onpress : MetaProxyWhiteList},";
	}
	$buttons="	buttons : [
	{name: '<strong style=font-size:18px>$new_server</strong>', bclass: 'add', onpress : MetaAddServ$t},
	{name: '<strong style=font-size:18px>$events</strong>', bclass: 'Search', onpress : MetaEvents},
	
	{name: '<strong style=font-size:18px>$packages</strong>', bclass: 'Search', onpress : Packages$t},$policies_button
	{name: '<strong style=font-size:18px>$switch</strong>', bclass: 'Search', onpress : Switch$t},
	
	$proxy_whitelist
	
	
	],";
	
	
	$t=time();
	$html="
	<input type='hidden' id='ARTICA_META_MAIN_TABLE_SWITCH' value=0>
<table class='ARTICA_META_MAIN_TABLE' style='display: none' id='ARTICA_META_MAIN_TABLE' style='width:1200px'></table>
<script>
$(document).ready(function(){
	$('#ARTICA_META_MAIN_TABLE').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>status</span>', name : 'icon1', width : 50, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$servername</span>', name : 'hostname', width : 250, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$load</span>', name : 'load', width : 206, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$memory</span>', name : 'mem_perc', width : 122, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$disks</span>', name : 'xdisks', width : 375, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$ipaddr</span>', name : 'public_ip', width : 150, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$software</span>', name : 'squidver', width : 89, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$version</span>', name : 'version', width : 97, sortable : true, align: 'right'},
	
	],
$buttons
	searchitems : [
\t{display: '$servername', name : 'hostname'},
\t{display: '$tag', name : 'hostag'},
\t{display: '$ipaddr', name : 'public_ip'},
\t{display: '$version', name : 'version'},
".@implode($VVERS, ",\n")."
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>Meta Server v$ARTICAVER - $clientsNumber Client(s) </strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 800,
	singleSelect: true
	
	});
});
	
function Profiles$t(){
	Loadjs('artica-meta.profiles.php');
}
	
function  MetaAddServ$t(){
	Loadjs('artica-meta.NewServ.php');
}	
	
function Switch$t(){
	var ARTICA_META_MAIN_TABLE_SWITCH=document.getElementById('ARTICA_META_MAIN_TABLE_SWITCH').value;
	if(ARTICA_META_MAIN_TABLE_SWITCH==0){ARTICA_META_MAIN_TABLE_SWITCH=1;}else{ARTICA_META_MAIN_TABLE_SWITCH=0;}
	document.getElementById('ARTICA_META_MAIN_TABLE_SWITCH').value=ARTICA_META_MAIN_TABLE_SWITCH;
	$('#ARTICA_META_MAIN_TABLE').flexOptions({url: '$page?search=yes&switch='+ARTICA_META_MAIN_TABLE_SWITCH}).flexReload();
}
	
	
function MetaEvents(){
	Loadjs('artica-meta.events.php?js=yes&t=$t');
}
	
function MetaProxyWhiteList(){
	Loadjs('squid.whitelist-meta.php');
}
	
function Policies$t(){
	Loadjs('artica-meta.policies.php');
}
	
function SwitchToArtica(){
	$('#dansguardian2-category-$t').flexOptions({url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t&artica=1'}).flexReload();
}
	
function SaveAllToDisk(){
	Loadjs('$page?compile-all-dbs-js=yes')
}

function LoadCategoriesSize(){
	Loadjs('dansguardian2.compilesize.php')
}
	
function CategoryDansSearchCheck(e){
	if(checkEnter(e)){CategoryDansSearch();}
}
	
function CategoryDansSearch(){
	var se=escape(document.getElementById('category-dnas-search').value);
	LoadAjax('dansguardian2-category-list','$page?category-search='+se,false);
}
	
	function DansGuardianCompileDB(category){
	Loadjs('ufdbguard.compile.category.php?category='+category);
	}
	
	function CheckStatsApplianceC(){
	LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes',false);
	}
	
	var X_PurgeCategoriesDatabase= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	RefreshAllTabs();
	}
	
	function PurgeCategoriesDatabase(){
	if(confirm('$purge_catagories_database_explain')){
	var XHR = new XHRConnection();
	XHR.appendData('PurgeCategoriesDatabase','yes');
	AnimateDiv('dansguardian2-category-list');
	XHR.sendAndLoad('$page', 'POST',X_PurgeCategoriesDatabase);
	}
	
	}
	
	var X_TableCategoryPurge= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	$('#dansguardian2-category-$t').flexReload();
	}
	
	function TableCategoryPurge(tablename){
	if(confirm('$purge_catagories_table_explain')){
	var XHR = new XHRConnection();
	XHR.appendData('PurgeCategoryTable',tablename);
	XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',X_TableCategoryPurge);
	}
	}
	
	function Packages$t(){
		Loadjs('artica-meta.packages.php');
	}
	
	
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();	
	$ArticaMetaPooling=intval($sock->GET_INFO("ArticaMetaPooling"));
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	$ArticaLinkAutoconnect=intval($sock->GET_INFO("ArticaLinkAutoconnect"));
	$MetaUfdbArticaVer=intval($sock->GET_INFO("MetaUfdbArticaVer"));
	if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
	$switch=intval($_GET["switch"]);
	$table="metahosts";
	
	
	if(isset($_POST["qtype"])){
		
		if(preg_match("#prxy([0-9\.]+)#", $_POST["qtype"],$re)){
			$_POST["query"]=$re[1];
			$_POST["qtype"]="squidver";
			
		}
		
		if(preg_match("#^([0-9\.]+)#", $_POST["qtype"])){
			$_POST["query"]=$_POST["qtype"];
			$_POST["qtype"]="version";
		}
		
	}
	
	
	
	
	$searchstring=string_to_flexquery();
	$page=1;

	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";
		}
	}	
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(!is_numeric($rp)){$rp=50;}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";	
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
	
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="18";
	
	if($switch==1){$fontsize=12;}
	
	$style="<span style='font-size:{$fontsize}px'>";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$policies_text=$tpl->javascript_parse_text("{policies}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	$proxy_statistics=$tpl->javascript_parse_text("{SQUID_STATS1}");
	$cache_rate=$tpl->javascript_parse_text("{cache_rate}");
	$clone_of_text=$tpl->javascript_parse_text("{clone_of}");
	$proxy_in_emergency_mode=$tpl->javascript_parse_text("{proxy_in_emergency_mode}");
	$webfiltering=$tpl->javascript_parse_text("{webfiltering}");
	$activedirectory_emergency_mode=$tpl->javascript_parse_text("{activedirectory_emergency_mode}");
	$NeCommuniquePlus_text="<br><span style='font-size:14px;font-weight:bold;color:d32d2d'>".$tpl->_ENGINE_parse_body("{did_not_talk_with_meta}")."</span>";
	$memory_exceed_80=$tpl->javascript_parse_text("{memory_exceed_80}");
	$memory_exceed_90=$tpl->javascript_parse_text("{memory_exceed_90}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$cloneFrom=$ligne["cloneFrom"];
		$loadcolor="black";
		$StatHourColor="black";
		$uuid=$ligne["uuid"];
		$hostname=$ligne["hostname"];
		$public_ip=$ligne["public_ip"];
		$updated=$ligne["updated"];
		$version=$ligne["version"];
		$ColorTime="black";
		$CPU_NUMBER=$ligne["CPU_NUMBER"];
		$CPU_NUMBER_MAX=$ligne["CPU_NUMBER"]+1.5;
		$load=$ligne["load"];
		$mem_perc=$ligne["mem_perc"];
		$mem_total=FormatBytes($ligne["mem_total"]);
		$proxyversion=null;
		$CountdeComputers_text=null;
		$policies_text_line=null;
		$CountdeComputers=$q->network_hosts_count($uuid);
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_panneau_32="warning-panneau-32.png";
		$BANDWIDTH=$ligne["BANDWIDTH"];
		$TaskPercent=intval($ligne["TaskPercent"]);
		$BANDWIDTH_text=null;
		$hostag_switch=null;
		$uuidenc=urlencode($uuid);
		$PING_URI_SWITCH=null;
		$PROXY_PERFS=null;
		$END_PROXY=null;
		$PROXY_LINE=null;
		$PROXYEMERG=$ligne["PROXYEMERG"];
		$webfiltering_version=null;
		$UFDBARTICA=intval($ligne["UFDBARTICA"]);
		$UFDB_ENABLED=intval($ligne["UFDB_ENABLED"]);
		$WINDOWSAD=intval($ligne["WINDOWSAD"]);
		$ADEMERG=intval($ligne["ADEMERG"]);
		$PROXYEMERG_ICON=0;
		$link_ip=null;
		$secondincon=null;
		$NeCommuniquePlus=null;
		$MemoryColor=null;
		$Loadbr="<br>";
		$StyleLoad="font-size:30px;font-weight:bold";
		
		if(preg_match("#^([0-9\.]+)-#", $ligne["squidver"],$rz)){$ligne["squidver"]=$rz[1];}
		
		if($ArticaMetaUseSendClient==1){
			$PING_URI="&nbsp;<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('artica-meta.menus.php?send-ping-js=yes&uuid=$uuidenc&gpid=0');\"
			style='font-size:12px;text-decoration:underline;color:#0021C6'
			>Ping</a>";
					
		}

		if($UFDB_ENABLED==1){
			if($UFDBARTICA>0){
				$UFDBARTICAT=date("Y-m-d H:i:s",$UFDBARTICA);
				$webfiltering_version="<br>$webfiltering $UFDBARTICAT";
			}
		}
		
		
		if($BANDWIDTH>0){
			$BANDWIDTH_text=" (".FormatBytes($BANDWIDTH/1024).")";
		}
		

		
		
		if($switch==1){
			$icon_warning_32="22-warn.png";
			$icon_red_32="22-red.png";
			$icon="ok22.png";
			$icon_panneau_32="warning-panneau-24.png";
			$StyleLoad="font-size:14px;";
			$Loadbr=null;
		}

		if($load>$CPU_NUMBER_MAX){
			$overloaded="<br><strong style='color:#d32d2d'>$overloaded_text</strong>";
			$icon=$icon_warning_32;
			$loadcolor="#d32d2d";
			$LOGSWHY[]="$overloaded_text $load>$CPU_NUMBER_MAX";
		}
		
		$xtime=strtotime($updated);
		$diff=time_diff_min($xtime);
		$Difftext=distanceOfTimeInWords($xtime,time(),true);
		if($diff>$ArticaMetaPooling*1.5){
			$icon=$icon_warning_32;$ColorTime="#d32d2d";
			$LOGSWHY[]=$Difftext."/{$ArticaMetaPooling}Mn";
			$loadcolor=$ColorTime;
		}
		if($diff>$ArticaMetaPooling*4){
			$icon=$icon_red_32; $ColorTime="#d32d2d";
			$LOGSWHY[]=$Difftext."/{$ArticaMetaPooling}Mn";
			$NeCommuniquePlus=$NeCommuniquePlus_text;
		}
		
		
		if($PROXYEMERG==1){
			$icon=$icon_panneau_32;
			$ColorTime="#d32d2d";
			$loadcolor=$ColorTime;
			$LOGSWHY[]="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('artica-meta.urgency.php?uuid=$uuid');\" style='color:$ColorTime;text-decoration:underline'>$proxy_in_emergency_mode</a>";
		}
		
		if($WINDOWSAD==1){
			$secondincon="windows-server-32.png";
			if($ADEMERG==1){
				$icon=$icon_warning_32;$ColorTime="#d32d2d";
				$secondincon="windows-server-32-red.png";
				$LOGSWHY[]=$activedirectory_emergency_mode;
				$loadcolor=$ColorTime;
			}
		}
		
		if($MetaUfdbArticaVer>0){
			if($UFDB_ENABLED==1){
				if($UFDBARTICA<$MetaUfdbArticaVer){
					$LOGSWHY[]="$webfiltering < ". date("Y-m-d H:i:s",$MetaUfdbArticaVer);
				}
			}
			
		}
		
		
		if($mem_perc>80){
			$MemoryColor="#f59c44";
			$icon=$icon_panneau_32;
			$LOGSWHY[]="<span style='color:$MemoryColor'>$memory_exceed_80</span>";
		}
		
		
		if($mem_perc>90){
			$icon=$icon_red_32; 
			$MemoryColor="#d32d2d";
			$icon=$icon_panneau_32;
			$LOGSWHY[]="$memory_exceed_90";
		}
				
		
		
		$disks=unserialize($ligne["disks"]);
		
		$SIZE=0;
		$USED=0;
		$infodisk=null;
		$DISKS_TEXT=array();
		
		$squid_db="squid_{$uuid}";
		if($q->DATABASE_EXISTS($squid_db)){
			$DISKS_TEXT[]="<ul><li><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.hosts.squid.stats.php?uuid=$uuid')\"
			style='font-size:14px;font-weight:bold;text-decoration:underline'>$proxy_statistics</a></li>";
		
		}
		
		
		if($q->philesight_count($uuid)>0){
			if(count($DISKS_TEXT)==0){$DISKS_TEXT[]="<ul>";}
			$DISKS_TEXT[]="<li><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.hosts.philesight.php?uuid=$uuid')\"
			style='font-size:14px;font-weight:bold;text-decoration:underline'>$directories_monitor</a></li>";
		}
		
		

		if(count($DISKS_TEXT)>0){
		$DISKS_TEXT[]="</ul>";
		}
				
// -------------------------------------------- DISKS INFOS		
		if(is_array($disks)){
			if(count($disks)>0){
			$DISKS_TEXT[]="<ul>";
			while (@list ($disks, $mainarray) = each ($disks)){
				$SIZE=FormatBytes(intval($mainarray["SIZE"])/1024);
				
				$DISKS_TEXT[]="<li style='font-weight:bold;font-size:14px'>$disks $SIZE</li>";
					$DISKS_TEXT[]="<ul>";
					while (list ($part, $partArray) = each ($mainarray["PARTS"])){
						$color_disk="black";
						$POURC=$partArray["POURC"];
						if($POURC>95){$color_disk="red";$icon=$icon_red_32;$LOGSWHY[]="$part {used}:{$POURC}%"; }
						
						
						$TOT=FormatBytes($partArray["TOT"]/1024);
						$AIV=FormatBytes($partArray["AIV"]/1024);
						$DISKS_TEXT[]="<li style='color:$color_disk'>$part $TOT - {used}:{$POURC}% {free}: $AIV</li>";
						
					}
					$DISKS_TEXT[]="</ul>";
				
				
				
			}
			$DISKS_TEXT[]="</ul>";
			
			
			
			$infodisk=$tpl->_ENGINE_parse_body(@implode("", $DISKS_TEXT));
			}
		}
// --------------------------------------------	--------------------------------------------------------------------	
		if($switch==1){$infodisk=null;}
		

		$info=$tpl->_ENGINE_parse_body("<br>{last_status}:&nbsp;<span style='color:$ColorTime'>$Difftext</span><br><span style='font-size:12px'>$CPU_NUMBER CPU(s), {memory}:{$mem_total}$overloaded</span>");
		
		$cell=array();
		
		$linkver="<a href=\"javascript:Loadjs('artica-meta.update.artica.php?uuid=$uuid');\" 
		style='text-decoration:underline'>";
		
		if($q->isOrder($uuid,"UPDATE_ARTICA")){
			$version="<center style='margin:10px'><img src='img/preloader.gif'></center>";
		}
		
		if($ligne["PROXY"]==1){
			$proxyuri="<br><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.squid.watchdog-events.php?js=yes&uuid=$uuid');\"
		style='text-decoration:underline'>";
			$proxyversion="<br><span style='font-size:12px'>{$proxyuri}Proxy: {$ligne["squidver"]}</a>$webfiltering_version $BANDWIDTH_text</span>";
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_perfs_gb WHERE uuid='$uuid'"));
			$client_http_hits=$ligne2["client_http_hits"];
			$client_http_requests=round($ligne2["client_http_requests"],2);
			$client_http_kbytes_out=round($ligne2["client_http_kbytes_out"]);
			$TOTALS_NOT_CACHED=$ligne2["TOTALS_NOT_CACHED"];
			$TOTALS_CACHED=$ligne2["TOTALS_CACHED"];
			$TOTALS_CACHED_AVG=round($ligne2["TOTALS_CACHED_AVG"],2);
			$END_PROXY="</span><br>";
			if($ligne2["uuid"]<>null){
				$PROXY_PERFS="<br><span style='font-size:12px'>{$client_http_requests}Req/s, {$client_http_kbytes_out}KB/s
				<br>{$cache_rate}: $TOTALS_CACHED_AVG%";
				$PROXY_LINE="<span style='font-size:12px'>{$client_http_requests}Req/s&nbsp;|&nbsp;{$client_http_kbytes_out}KB/s&nbsp;|&nbsp;{$cache_rate}: $TOTALS_CACHED_AVG%</span>";
			}
			
		}
		
		if($CountdeComputers>0){
			$CountdeComputers=FormatNumber($CountdeComputers);
			$CountdeComputers_uri="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.networks.hosts.php?js=yes&uuid=$uuid');\"
			style='text-decoration:underline'>";
			$CountdeComputers_text="<br><span style='font-size:12px'>{$CountdeComputers_uri}{$computers}: <strong>$CountdeComputers</strong></a></span>";
			
		}
		
		$OrdersText=null;
		$ligneOrders=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(orderid) as tcount FROM `metaorders` WHERE `uuid`='$uuid'"));
		$OrdersCount=$ligneOrders["tcount"];
		
		if($OrdersCount>0){
			
			$OrdersText="<br><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('artica-meta.hosts.orders.php?uuid=$uuid');\">
			<strong style='text-decoration:underline;color:#E48407'>$orders_text:$OrdersCount ({$TaskPercent}%)</strong>";
		}
		
		$ligneOrders=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(uuid) as tcount FROM `policies_storage` WHERE `uuid`='$uuid'"));
		$OrdersCount=$ligneOrders["tcount"];
		if($OrdersCount>0){
			$policies_text_line="<br><strong style='text-decoration:underline;color:#E48407'>$policies_text:$OrdersCount</strong>";
		}
		
		
		
		
		$events="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.events.php?js=yes&uuid=$uuid');\"
		style='float:right'><img src='css/images-flexigrid/magnifier.png'></a>";
		
		$cpus="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.cpustats.php?js=yes&uuid=$uuid');\"
		style='text-decoration:underline'>";
		
		$hostsCommands="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.menus.php?js=yes&uuid=$uuid');\"
		style='text-decoration:underline'>";
		
		$hostag=utf8_encode($ligne["hostag"]);
		if($hostag<>null){$hostag="<br><i style='font-size:12px'>$hostag</i>";}
		
		if($cloneFrom<>null){
			$cloneFrom="<br><i style='font-weight:bold;font-size:12px'>$clone_of_text:&nbsp;".$q->uuid_to_host($cloneFrom)."</i>";
		}
		if($secondincon<>null){$secondincon="<br><img src='img/$secondincon' style='margin-top:10px'>";}
		
		$LicenseJs="OnClick=\"javascript:Loadjs('artica-meta.host.license.php?uuid=$uuid')\"";
		
		
		$LicenseInfos=$q->LicenseInfos($uuid);
		$LICT="Community Edition";
		if($LicenseInfos["CORP_LICENSE"]){$LICT="Entreprise Edition";}
		if($LicenseInfos["ExpiresSoon"]>0){if($LicenseInfos["ExpiresSoon"]<31){$LICT="<span style='color:red'>{trial_mode}</span>";}}
		$LicenseText="<br><a href=\"javascript:blur();\" $LicenseJs style='font-size:12px;text-decoration:underline'>". $tpl->_ENGINE_parse_body($LICT)."</a>";
	
		
		
		
		if($switch==1){
			$info=null;
			$secondincon=null;
			$hostag=null;
			$cloneFrom=null;
			$proxyversion=null;
			$CountdeComputers_text=null;
			$events=null;
			$hostag_switch=utf8_encode($ligne["hostag"]);
			$PING_URI_SWITCH=$PING_URI;
			$PING_URI=null;
			$PROXY_PERFS=null;
			$END_PROXY=null;
			$LicenseText=null;
			if(count($LOGSWHY)>0){
				$infodisk=$tpl->_ENGINE_parse_body("<span style='color:#d32d2d;font-size:14px'>".@implode("<br>", $LOGSWHY)."</span>");
			}
		}else{
			$GroupsList=GetGroups($uuid);
			$PROXY_LINE=null;
			if(count($LOGSWHY)>0){
				$infodisk=$tpl->_ENGINE_parse_body("<span style='color:#d32d2d;font-size:14px'>".@implode("<br>", $LOGSWHY)."</span>")."<hr>".$infodisk;
			}
		}
		
		
		
		$cell=array();
		
		
		
		
		
		if($ArticaMetaUseSendClient==1){
			$uriAdd=null;
			if($ArticaLinkAutoconnect==1){
				if($ligne["system_adm"]<>null){$uriAdd="/logon.php?autologmeta=".md5($ligne["system_adm"].$ligne["system_password"]);}
			}
			$link_ip="<a href=\"https://$public_ip:9000$uriAdd\" style='text-decoration:underline;color:$loadcolor' target=_new>";
			
		}
		if($MemoryColor==null){$MemoryColor=$loadcolor;}
		
		
		$cell[]="$Loadbr<center><img src=\"img/$icon\">$secondincon</center>";
		$cell[]="$style$hostsCommands$hostname$hostag</a>$LicenseText$NeCommuniquePlus$cloneFrom$PING_URI_SWITCH$events </span>$info
		$proxyversion$PROXY_PERFS$END_PROXY
		$CountdeComputers_text$OrdersText$policies_text_line";
		$cell[]="<span style='color:$loadcolor;$StyleLoad'>$Loadbr$load</span></span>";
		$cell[]="$style$cpus<span style='$StyleLoad;color:$MemoryColor'>$Loadbr{$mem_perc}%</a></span>";
		$cell[]=$hostag_switch."&nbsp;".$infodisk.$PROXY_LINE;
		
		$cell[]="$style<span style='color:$loadcolor !important'>$link_ip$public_ip</span></a>$PING_URI</span>$GroupsList";
		$cell[]="$style{$ligne["squidver"]}</span>";
		$cell[]="$style$linkver$version</a></span>";
		
		
	$data['rows'][] = array(
		'id' => $ligne['uuid'],
		'cell' => $cell
		);
	}
	
	
echo json_encode($data);	
	
}

function GetGroups($uuid){
	$q=new mysql_meta();
	$f=array();
	$sql="SELECT metagroups.groupname,
	metahosts.uuid,metagroups_link.zmd5
	FROM metahosts,metagroups_link,metagroups WHERE
	metagroups_link.uuid=metahosts.uuid
	AND metagroups.ID=metagroups_link.gpid
	AND metahosts.uuid='$uuid' ORDER BY metagroups.groupname";
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return "<li>$q->mysql_error</li>";}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="<li><a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('artica-meta.menus.php?gpid=1');\"
		style='text-decoration:underline'>{$ligne["groupname"]}</a>";
		
	}
	
	if(count($f)>0){return "<ul style='margin-top:10px'>".@implode("", $f)."</ul>";}
}

function time_diff_min($xtime){
	$data1 = $xtime;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}