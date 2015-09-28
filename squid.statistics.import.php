<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.rtmm.tools.inc');
$user=new usersMenus();
if(!$user->AsWebStatisticsAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");exit;}



if(isset($_GET["search"])){search();exit;}
if(isset($_GET["unlink-js"])){unlink_js();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_POST["run"])){run();exit;}
if(isset($_POST["unlink"])){unlink_perform();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}
table();

function unlink_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{unlink}");
	$page=CurrentPageName();
	$zmd5=$_GET["unlink-js"];
	$filename=$_GET["filename"];
	$t=time();
	echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_IMPORT_SOURCE_LOGS_TABLE').flexReload();
}


function LinkEdHosts$t(){
	if(!confirm('$title $filename ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

LinkEdHosts$t();
" ;

}

function reset_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{reset}");
	$page=CurrentPageName();
	$zmd5=$_GET["reset-js"];
	$filename=$_GET["filename"];
	$t=time();
	echo "
	var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_IMPORT_SOURCE_LOGS_TABLE').flexReload();
	}
	
	
	function LinkEdHosts$t(){
	if(!confirm('$title $filename ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reset','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	LinkEdHosts$t();
	" ;
	
	}

function run_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{run}");
	$page=CurrentPageName();
	
	
	$t=time();
	echo "
	var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_IMPORT_SOURCE_LOGS_TABLE').flexReload();
	}
	
	
	function LinkEdHosts$t(){
	if(!confirm('$title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('run','yes');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	LinkEdHosts$t();
	" ;
	
		
	
}

function run(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?source-file-uploaded-run=yes");	
	
	
}

function reset_perform(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE import_srclogs  SET status=0 WHERE `md5file`='{$_POST["reset"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}

function unlink_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?source-file-uploaded-delete=yes&filename=".urlencode($_POST["unlink"]));
}

function table(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$link_host=$tpl->javascript_parse_text("{link_policy}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$upload=$tpl->javascript_parse_text("{upload_a_file}");
	$start=$tpl->javascript_parse_text("{start} {end}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$run=$tpl->javascript_parse_text("{run}");
	$importlocalfiles=$tpl->javascript_parse_text("{import_local_files}");
	
	
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{import}: {sources_files}");
	
	if(!$q->TABLE_EXISTS("import_srclogs")){
		$sql="CREATE TABLE IF NOT EXISTS `import_srclogs` (
		`md5file` varchar(90) NOT NULL,
		`path` varchar(255) NOT NULL,
		`size` INT UNSIGNED NOT NULL DEFAULT '0',
		`zDate` datetime NOT NULL,
		`percent` smallint(2) NOT NULL DEFAULT 0,
		`status` smallint(1) NOT NULL,
		`lastlog` varchar(255) NOT NULL,
		`pid` smallint(5) NOT NULL,
		`first_time` INT UNSIGNED,
		`last_time` INT UNSIGNED,
		PRIMARY KEY (`md5file`),
		KEY `path` (`path`),
		KEY `size` (`size`),
		KEY `zDate` (`zDate`),
		KEY `percent` (`percent`),
		KEY `status` (`status`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
		
	}
	
	if(!$q->FIELD_EXISTS("import_srclogs", "pid")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `pid` smallint(5)");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "first_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `first_time` INT UNSIGNED");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "last_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `last_time` INT UNSIGNED");
	}
	
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$upload</strong>', bclass: 'Down', onpress : Upload$t},
	{name: '<strong style=font-size:18px>$importlocalfiles</strong>', bclass: 'Down', onpress : Importlocal$t},
	{name: '<strong style=font-size:18px>$run</strong>', bclass: 'apply', onpress : Run$t},
	
	
	],";
	
	
	$q=new mysql();
	//$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	
$html="
<table class='ARTICA_IMPORT_SOURCE_LOGS_TABLE' style='display: none' id='ARTICA_IMPORT_SOURCE_LOGS_TABLE' style='width:1200px'></table>
<script>
$(document).ready(function(){
	$('#ARTICA_IMPORT_SOURCE_LOGS_TABLE').flexigrid({
			url: '$page?search=yes',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'status', width : 49, sortable : true, align: 'center'},
			{display: '$date', name : 'zDate', width : 233, sortable : true, align: 'left'},
			{display: '$start', name : 'first_time', width : 169, sortable : true, align: 'left'},
			{display: '$filename', name : 'path', width : 565, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width : 94, sortable : true, align: 'right'},
			{display: '&nbsp;', name : 'percent', width : 52, sortable : true, align: 'center'},
			{display: '&nbsp;', name : 'delete', width : 49, sortable : false, align: 'center'},
	
			],
			$buttons
			searchitems : [
			{display: '$filename', name : 'path'},
	
	
			],
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '<strong style=font-size:22px>$title</strong>',
			useRp: true,
			rpOptions: [10, 20, 30, 50,100,200],
			rp:50,
			showTableToggleBtn: false,
			width: '99%',
			height: 550,
			singleSelect: true
	
	});
});
	
function Upload$t(){
	Loadjs('squid.statistics.import.upload.php');
}
function Run$t(){
	Loadjs('$page?run-js=yes');
}	
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_IMPORT_SOURCE_LOGS_TABLE').flexReload();
}


function Importlocal$t(){
	Loadjs('squid.statistics.import.local.progress.php');
}
	
	function LinkEdHosts$t(policyid){
	var XHR = new XHRConnection();
	XHR.appendData('link-policy',policyid);
	XHR.appendData('gpid','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	function LinkHostsAll$t(){
	if(!confirm('$link_all_hosts_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('link-all','{$_GET["ID"]}');
			XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
	function Orders$t(){
		if(!confirm('$synchronize_policies_explain')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('synchronize-group','{$_GET["ID"]}');
		XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
	}
	
</script>";
echo $html;
}	

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="import_srclogs";
	$database=null;

	if(!$q->TABLE_EXISTS($table,$database)){
		json_error_show("no data - no table");
	}

	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",0);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");

	$icons[0]="clock-gold-32.png";
	$icons[1]="wait-clock.gif";
	$icons[2]="check-32.png";
	$icons[3]="error-32.png";

	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";

		$ColorTime="black";
		$uuid=$ligne["uuid"];
		$hostname=$ligne["hostname"];
		$hostag=utf8_encode($ligne["hostag"]);
		$zmd5=$ligne["zmd5"];
		$first_time_text=null;
		$last_time_text=null;
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";

		$xdate=$ligne["zDate"];
		$xtime=strtotime($xdate);
		$date=$tpl->time_to_date($xtime,true);
		$size=FormatBytes($ligne["size"]/1024);
		$path=basename($ligne["path"]);
		$percent=$ligne["percent"];
		$status=$ligne["status"];
		$lastlog=$tpl->javascript_parse_text($ligne["lastlog"]);
		$icon=$icons[$status];
		$first_time=$ligne["first_time"];
		$last_time=$ligne["last_time"];
		$resetjs=null;
		if($first_time>0){
			$first_time_text=date("Y-m-d H:i:s",$first_time);
		}
		if($last_time>0){
			$last_time_text=date("Y-m-d H:i:s",$last_time);
		}
		
		$pathenc=urlencode($path);
		if($status==1){
			$resetjs="Loadjs('$MyPage?reset-js={$ligne["md5file"]}&filename=$pathenc')";
			
		}
		
		$urijs="Loadjs('$MyPage?content-js=yes&ID={$ligne["ID"]}');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?unlink-js={$ligne["md5file"]}&filename=$pathenc')");
		$restore=imgsimple("32-import.png",null,"Loadjs('artica-meta.menus.php?snapshot-restore-js=yes&zmd5={$ligne["zmd5"]}&uuid={$_GET["uuid"]}')");
		$cell=array();
		$cell[]="<span $style>". imgsimple($icon,null,$resetjs)."</a></span>";
		$cell[]="<span $style>$date</a></span>";
		$cell[]="<span $style>$first_time_text<br>$last_time_text</a></span>";
		$cell[]="<span $style>$path</a></span><br><i style='font-size:14px'>$lastlog</i>";
		$cell[]="<span $style>$size</a></span>";
		$cell[]="<span $style>{$percent}%</a></span>";
		$cell[]="$delete";

		$data['rows'][] = array(
				'id' => $ligne['md5file'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}
