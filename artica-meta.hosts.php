<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();

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
	
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");
	
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(PROXY) as tproxy FROM metahosts WHERE PROXY=1"));
	$proxy=$ligne["tproxy"];
	$policies_button="{name: '$policies', bclass: 'Settings', onpress : Policies$t},";
	if($proxy>0){
		
		$proxy_whitelist="{name: '$global_whitelist', bclass: 'export', onpress : MetaProxyWhiteList},";
	}
	$buttons="	buttons : [
	{name: '$new_server', bclass: 'add', onpress : MetaAddServ$t},
	{name: '$events', bclass: 'Search', onpress : MetaEvents},
	
	{name: '$packages', bclass: 'Search', onpress : Packages$t},$policies_button
	{name: '$switch', bclass: 'Search', onpress : Switch$t},
	
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
	{display: 'status', name : 'icon1', width : 50, sortable : false, align: 'center'},
	{display: '$servername', name : 'hostname', width : 250, sortable : true, align: 'left'},
	{display: '$load', name : 'load', width : 70, sortable : true, align: 'center'},
	{display: '$memory', name : 'mem_perc', width : 70, sortable : true, align: 'center'},
	{display: '$disks', name : 'xdisks', width : 375, sortable : false, align: 'left'},
	{display: '$ipaddr', name : 'public_ip', width : 150, sortable : true, align: 'left'},
	{display: '$version', name : 'version', width : 110, sortable : true, align: 'right'},
	
	],
$buttons
	searchitems : [
	{display: '$servername', name : 'hostname'},
	{display: '$tag', name : 'hostag'},
	
	{display: '$ipaddr', name : 'public_ip'},
	{display: '$version', name : 'version'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>Artica Meta Clients</strong>',
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
	
	function Switch$t(){
		var ARTICA_META_MAIN_TABLE_SWITCH=document.getElementById('ARTICA_META_MAIN_TABLE_SWITCH').value;
		if(ARTICA_META_MAIN_TABLE_SWITCH==0){ARTICA_META_MAIN_TABLE_SWITCH=1;}else{ARTICA_META_MAIN_TABLE_SWITCH=0;}
		document.getElementById('ARTICA_META_MAIN_TABLE_SWITCH').value=ARTICA_META_MAIN_TABLE_SWITCH;
		$('#ARTICA_META_MAIN_TABLE').flexOptions({url: '$page?search=yes&switch='+ARTICA_META_MAIN_TABLE_SWITCH}).flexReload();
	}
	
	
	function MetaEvents(){
		Loadjs('artica-meta.events.php?js=yes&t=$t');
	}
	
	function  MetaAddServ$t(){
		Loadjs('artica-meta.NewServ.php');
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
	if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
	$switch=intval($_GET["switch"]);
	$table="metahosts";
	
	
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
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
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
		$BANDWIDTH=$ligne["BANDWIDTH"];
		$BANDWIDTH_text=null;
		$hostag_switch=null;
		$uuidenc=urlencode($uuid);
		$PING_URI_SWITCH=null;
		
		if($ArticaMetaUseSendClient==1){
			$PING_URI="&nbsp;<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('artica-meta.menus.php?send-ping-js=yes&uuid=$uuidenc&gpid=0');\"
			style='font-size:12px;text-decoration:underline;color:#0021C6'
			>Ping</a>";
					
		}
		
		if($BANDWIDTH>0){
			$BANDWIDTH_text=" (".FormatBytes($BANDWIDTH/1024).")";
		}
		
		if($switch==1){
			$icon_warning_32="22-warn.png";
			$icon_red_32="22-red.png";
			$icon="ok22.png";
		}

		if($load>$CPU_NUMBER_MAX){
			$overloaded="<br><strong style='color:red'>$overloaded_text</strong>";
			$icon=$icon_warning_32;
			$loadcolor="#CC0000";
			$LOGSWHY[]="$overloaded_text $load>$CPU_NUMBER_MAX";
		}
		
		$xtime=strtotime($updated);
		$diff=time_diff_min($xtime);
		$Difftext=distanceOfTimeInWords($xtime,time(),true);
		if($diff>$ArticaMetaPooling){
				$icon=$icon_warning_32;$ColorTime="#CC0000";
				$LOGSWHY[]=$Difftext."/{$ArticaMetaPooling}Mn";
		}
		if($diff>$ArticaMetaPooling*4){
			$icon=$icon_red_32; $ColorTime="#CC0000";
			$LOGSWHY[]=$Difftext."/{$ArticaMetaPooling}Mn";
		}
		
		$disks=unserialize($ligne["disks"]);
		
		$SIZE=0;
		$USED=0;
		$infodisk=null;
		$DISKS_TEXT=array();
		if($q->philesight_count($uuid)>0){
			$DISKS_TEXT[]="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.hosts.philesight.php?uuid=$uuid')\"
			style='font-size:14px;font-weight:bold;text-decoration:underline'>$directories_monitor</a><hr style='margin-bottom:15px;border:0px;'>";
		}
		
		if(is_array($disks)){
			$DISKS_TEXT[]="<ul>";
			while (list ($disks, $mainarray) = each ($disks)){
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
		
		if($switch==1){$infodisk=null;}

		
		$info=$tpl->_ENGINE_parse_body("<br>{last_status}:&nbsp;<span style='color:$ColorTime'>$Difftext</span><br><span style='font-size:12px'>$CPU_NUMBER CPU(s), {memory}:{$mem_total}$overloaded</span>");
		
		$cell=array();
		
		$linkver="<a href=\"javascript:Loadjs('artica-meta.update.artica.php?uuid=$uuid');\" 
		style='text-decoration:underline'>";
		
		if($q->isOrder($uuid,"UPDATE_ARTICA")){
			$version="<center style='margin:10px'><img src='img/preloader.gif'></center>";
		}
		
		if($ligne["PROXY"]==1){
			$proxyuri="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica-meta.squid.watchdog-events.php?js=yes&uuid=$uuid');\"
		style='text-decoration:underline'>";
			$proxyversion="<br><span style='font-size:12px'>{$proxyuri}Proxy: {$ligne["squidver"]}</a> $BANDWIDTH_text</span>";
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
			<strong style='text-decoration:underline;color:#E48407'>$orders_text:$OrdersCount</strong>";
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
		
		if($switch==1){
			$info=null;
			$hostag=null;
			$proxyversion=null;
			$CountdeComputers_text=null;
			$events=null;
			$hostag_switch=utf8_encode($ligne["hostag"]);
			$PING_URI_SWITCH=$PING_URI;
			$PING_URI=null;
			if(count($LOGSWHY)>0){
				$infodisk=$tpl->_ENGINE_parse_body("<span style='color:red;font-size:12px'>".@implode(", ", $LOGSWHY)."</span>");
			}
		}
		
		
		
		
		$cell=array();
		
		$link_ip="<a href=\"https://$public_ip:9000\" style='text-decoration:underline' target=_new>";
		$cell[]="<img src=\"img/$icon\">";
		$cell[]="$style$hostsCommands$hostname$hostag</a>$PING_URI_SWITCH$events </span>$info$proxyversion$CountdeComputers_text$OrdersText$policies_text_line";
		$cell[]="$style<span style='color:$loadcolor'>$load</span></span>";
		$cell[]="$style$cpus{$mem_perc}%</a></span>";
		$cell[]=$hostag_switch."&nbsp;".$infodisk;
		$cell[]="$style$link_ip$public_ip</a>$PING_URI</span>";
		$cell[]="$style$linkver$version</a></span>";
		
		
	$data['rows'][] = array(
		'id' => $ligne['uuid'],
		'cell' => $cell
		);
	}
	
	
echo json_encode($data);	
	
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