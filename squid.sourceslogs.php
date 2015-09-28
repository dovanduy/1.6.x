<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}


if(isset($_GET["popup"])){page();exit;}
if(isset($_POST["link-policy"])){link_policy();exit;}
if(isset($_GET["unlink-js"])){unlink_path();exit;}
if(isset($_POST["unlink"])){unlink_perform();exit;}
if(isset($_POST["synchronize-group"])){synchronize_group();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download"])){download();exit;}
tabs();

function tabs(){
	$sock=new sockets();
	$fontsize=22;
	$tpl=new templates();
	$page=CurrentPageName();
	$array["popup"]='{legal_logs}';
	$array["parameters"]='{log_retention}';
	$array["NAS_storage"]='{NAS_storage}';
	$array["log_location"]='{log_location}';
	$array["rotate_events"]='{rotate_events}';

	
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.params.php?parameters=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		if($num=="NAS_storage"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.nas.storage.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="log_location"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.varlog.php?popup=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		if($num=="rotate_events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.rotate.events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}				
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_logs_sources");
	
}


function page(){


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
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{sources_files}");
	
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	$title=$title." ".FormatBytes($ligne["size"]/1024);

	$html="

	<table class='ARTICA_SOURCE_LOGS_TABLE' style='display: none' id='ARTICA_SOURCE_LOGS_TABLE' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_SOURCE_LOGS_TABLE').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 317, sortable : true, align: 'left'},
	{display: '$filename', name : 'path', width : 588, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$date', name : 'zDate'},
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
	height: 400,
	singleSelect: true

});
});

function LinkHosts$t(){
	Loadjs('artica-meta.policies.php?function=LinkEdHosts$t');
}

var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_SOURCE_LOGS_TABLE').flexReload();
	
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
	$q=new mysql();
	$table="backuped_logs";
	if(!$q->TABLE_EXISTS("backuped_logs","artica_events")){
		json_error_show("no data (no table)",1);
	}
	


	

	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


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


	while ($ligne = mysql_fetch_assoc($results)) {
	$LOGSWHY=array();
	$overloaded=null;
	$loadcolor="black";
	$StatHourColor="black";
	$expl=null;
	$ColorTime="black";
	$uuid=$ligne["uuid"];
	$xdate=strtotime($ligne["zDate"]);
	$date=$tpl->time_to_date($xdate,true);
	$basename=basename($ligne["path"]);
	$size=FormatBytes($ligne["size"]/1024);
	$icon_warning_32="warning32.png";
	$icon_red_32="32-red.png";
	$icon="ok-32.png";


	
	$link="<a href=\"$MyPage?download=".base64_encode($ligne["path"])."\"
			style='text-decoration:underline;'
			>";
	$path_encoded=urlencode($ligne["path"]);
	
	if(preg_match("#([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)--([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)\.gz$#", $basename,$re)){
		$zdate=("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}:{$re[6]}");
		$zdate2=("{$re[7]}-{$re[8]}-{$re[9]} {$re[10]}:{$re[11]}:{$re[12]}");
		$xdate=strtotime($zdate);
		$datefrom=$tpl->time_to_date($xdate,true);
		$xdate=strtotime($zdate2);
		$dateto=$tpl->time_to_date($xdate,true);
		$expl=$tpl->_ENGINE_parse_body("<br><i style='font-size:12px'>{from}:$datefrom {to} $dateto</i>");
	}
	
			$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?unlink-js=$path_encoded')");
			if(preg_match("#^\/mnt\/", $ligne["path"])){
				$link=null;
				$delete="&nbsp;";
			}

			
			$cell=array();
			$cell[]="<span $style>$link$date</a></span>";
			$cell[]="<span $style>$basename</a>$expl</span>";
			$cell[]="<span $style>$size</a></span>";
			$cell[]="$delete";

			$data['rows'][] = array(
					'id' => $ligne['zmd5'],
							'cell' => $cell
	);
	}


	echo json_encode($data);
}

function unlink_path(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{delete}");
	$page=CurrentPageName();
	$path=$_GET["unlink-js"];
	$t=time();
	echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_SOURCE_LOGS_TABLE').flexReload();
	
}


function LinkEdHosts$t(){
	if(!confirm('$title $path ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink','$path');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

LinkEdHosts$t();
" ;

}
function unlink_perform(){
	$unlink=urlencode($_POST["unlink"]);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?unlink-source-logs=$unlink");
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM `backuped_logs` WHERE `path`='{$_POST["unlink"]}'","artica_events");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function download(){
	$sock=new sockets();
	$download=base64_decode($_GET["download"]);
	$basename=basename($download);
	$sock->getFrameWork("squid.php?replicate-source-logs=".urlencode($download));
	$filepath="/usr/share/artica-postfix/ressources/logs/web/$basename";
	if(!is_file($filepath)){
		$basename="$download-failed-no-such-file.gz";
	}
	
	$fsize = filesize($filepath);
	header("Content-Length: ".$fsize);
	header('Content-type: application/gzip');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"{$basename}\"");
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($filepath);
	@unlink($filepath);
	
}



?>