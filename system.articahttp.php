<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	if(isset($_GET["list"])){table_list();exit;}
	if(isset($_GET["popup"])){table();exit;}
	
	if(isset($_GET["today"])){today();exit;}
	if(isset($_GET["today-list"])){today_list();exit;}
	
	if(isset($_GET["yesterday"])){yesterday();exit;}
	
	tabs();
	
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	
	$array["popup"]='{this_hour}';
	$array["today"]='{today}';
	$array["yesterday"]='{yesterday}';
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_artica_httprqs");
		
	
	
}

function yesterday(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	$date=$tpl->javascript_parse_text("{zDate}");
	$url=$tpl->javascript_parse_text("{website}");
	$incoming=$tpl->javascript_parse_text("{incoming2}");
	$outgoing=$tpl->javascript_parse_text("{outgoing}");
	
	$q=new mysql_squid_builder();
	$hier=$q->HIER_TIME();
	
	$q=new mysql();
	
	$table=date("Ymd",$hier)."_dcurl";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size_download) as incoming FROM `$table`",
			"artica_events"));
	if($q->ok){
		$incomingS=FormatBytes($ligne["incoming"]/1024);
	
	}else{
		$incomingS=$tpl->javascript_parse_text("{mysql_error} $table");
	}
	
	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_network_bridge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$destination_port=$tpl->javascript_parse_text("{destination_port}");
	$time=$tpl->javascript_parse_text("{duration}");
	$artica_HTTP_traffic=$tpl->javascript_parse_text("{artica_HTTP_traffic}");
	
	$bts[]="{name: '$add', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '$reconstruct', bclass: 'apply', onpress : BuildVLANs$t},";
	
	
	
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	$reboot_network_explain=$tpl->_ENGINE_parse_body("{bridges_iptables_explain}<p>&nbsp;</p>{reboot_network_explain}");
	
	$buttons=null;
	
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
	<script>
	var mm$t=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?today-list=yes&t=$t&tablename=$table',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'Hour', width : 160, sortable : true, align: 'left'},
	{display: '$url', name : 'www', width : 577, sortable : true, align: 'left'},
	{display: '$incoming', name : 'size_download', width : 160, sortable : true, align: 'left'},
	
	
	
	],$buttons
	searchitems : [
	{display: '$url', name : 'url'},
	
	
	
	],
	sortname: 'Hour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$artica_HTTP_traffic ".date("{l} {F} d")." $incoming: $incomingS</span>',
	useRp: true,
	rp: 25,
	rpOptions: [25,50,100,200,500,1000],
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
	
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	}

function today(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();


	$date=$tpl->javascript_parse_text("{zDate}");
	$url=$tpl->javascript_parse_text("{website}");
	$incoming=$tpl->javascript_parse_text("{incoming2}");
	$outgoing=$tpl->javascript_parse_text("{outgoing}");

	$q=new mysql();
	$table=date("Ymd")."_dcurl";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size_download) as incoming FROM `$table`",
			"artica_events"));
	if($q->ok){
		$incomingS=FormatBytes($ligne["incoming"]/1024);
		
	}else{
		$incomingS=$tpl->javascript_parse_text("{mysql_error} $table");
	}
	

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_network_bridge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$destination_port=$tpl->javascript_parse_text("{destination_port}");
	$time=$tpl->javascript_parse_text("{duration}");
	$artica_HTTP_traffic=$tpl->javascript_parse_text("{artica_HTTP_traffic}");

	$bts[]="{name: '$add', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '$reconstruct', bclass: 'apply', onpress : BuildVLANs$t},";




	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}

	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	$reboot_network_explain=$tpl->_ENGINE_parse_body("{bridges_iptables_explain}<p>&nbsp;</p>{reboot_network_explain}");

	$buttons=null;

	$html="

	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>

	<script>
	var mm$t=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?today-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'Hour', width : 160, sortable : true, align: 'left'},
	{display: '$url', name : 'www', width : 577, sortable : true, align: 'left'},
	{display: '$incoming', name : 'size_download', width : 160, sortable : true, align: 'left'},
	


	],$buttons
	searchitems : [
	{display: '$url', name : 'url'},



	],
	sortname: 'Hour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$artica_HTTP_traffic ".date("{l} {F} d")." $incoming: $incomingS</span>',
	useRp: true,
	rp: 25,
	rpOptions: [25,50,100,200,500,1000],
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});



</script>

";

	echo $tpl->_ENGINE_parse_body($html);
}
	
	
function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	

	$date=$tpl->javascript_parse_text("{zDate}");
	$url=$tpl->javascript_parse_text("{url}");
	$incoming=$tpl->javascript_parse_text("{incoming2}");
	$outgoing=$tpl->javascript_parse_text("{outgoing}");
	
	
	$q=new mysql();
	$table=date("YmdH")."_curl";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size_download) as incoming FROM `$table`",
			"artica_events"));
	if($q->ok){
		$incomingS=FormatBytes($ligne["incoming"]/1024);
		
	}else{
		$incomingS=$tpl->javascript_parse_text("{mysql_error} $table");
	}
	
	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_network_bridge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$destination_port=$tpl->javascript_parse_text("{destination_port}");
	$time=$tpl->javascript_parse_text("{duration}");
	$artica_HTTP_traffic=$tpl->javascript_parse_text("{artica_HTTP_traffic}");

	$bts[]="{name: '$add', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '$reconstruct', bclass: 'apply', onpress : BuildVLANs$t},";




	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}

	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	$reboot_network_explain=$tpl->_ENGINE_parse_body("{bridges_iptables_explain}<p>&nbsp;</p>{reboot_network_explain}");

	$buttons=null;

	$html="

	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>

	<script>
	var mm$t=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 160, sortable : true, align: 'left'},
	{display: '$url', name : 'url', width : 577, sortable : true, align: 'left'},
	{display: '$incoming', name : 'size_download', width : 160, sortable : true, align: 'left'},
	
	
	
	],$buttons
	searchitems : [
	{display: '$url', name : 'url'},
	


	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$artica_HTTP_traffic ".date("H")."h - $incoming $incomingS</span>',
	useRp: true,
	rp: 25,
	rpOptions: [25,50,100,200,500,1000],
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

function RuleAdd$t(){
Loadjs('$page?network-bridge-js=yes&ID=0&t=$t',true);
}

function BuildVLANs$t(){
Loadjs('network.restart.php?t=$t');

}



function EmptyTask$t(){
if(confirm('$empty::{$_GET["taskid"]}')){

}
}


</script>

";

	echo $tpl->_ENGINE_parse_body($html);
}

function table_list(){
	
	$HourTable=date("YmdH");
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$table="{$HourTable}_curl";
	
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table,"artica_events")){
			json_error_show("$table: No such table",1,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
		$md5=$ligne["md5"];
		
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$download_text="-";
		$upload_text="-";
		$mbs_download=null;
		$mbs_upload=null;
		$size_download=$ligne["size_download"];
		$size_upload=$ligne["size_upload"];
		$time=$ligne["time"];
		if($size_download>0){
			$size_download_text=FormatBytes($size_download/1024);
			$mbs_download=sprintf(" %0.4f mbps", $size_download * 8 / $time / 1024 / 1024);
		}
		if($size_upload>0){
			$upload_text=FormatBytes($size_upload/1024);
			$mbs_upload=sprintf(" %0.4f mbps", $size_upload * 8 / $time / 1024 / 1024);
		}
		
		
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $ligne["md5"],
				'cell' => array(
					"<span style='font-size:14px;color:$color'>{$ligne["zDate"]}</span>",
					"<span style='font-size:14px;color:$color'>{$ligne["url"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$size_download_text</a></span>",
					

					
					)
				);
			}
	
	
echo json_encode($data);	
}

function today_list(){
	$HourTable=date("Ymd");
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$table="{$HourTable}_dcurl";
	
	if(isset($_GET["tablename"])){$table=$_GET["tablename"];}
	
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table,"artica_events")){
			json_error_show("$table: No such table",1,true);
		}
	}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$md5=$ligne["md5"];
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$download_text="-";
		$upload_text="-";
		$mbs_download=null;
		$mbs_upload=null;
		$size_download=$ligne["size_download"];
		$size_upload=$ligne["size_upload"];
		
		if($size_download>0){
			$size_download_text=FormatBytes($size_download/1024);
			
		}
		if($size_upload>0){
			$upload_text=FormatBytes($size_upload/1024);
			
		}
	
	
	
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $ligne["md5"],
				'cell' => array(
						"<span style='font-size:14px;color:$color'>{$ligne["Hour"]}H</span>",
						"<span style='font-size:14px;color:$color'>{$ligne["www"]}</a></span>",
						"<span style='font-size:14px;color:$color'>$size_download_text</a></span>",
						"<span style='font-size:14px;color:$color'>$upload_text</span>",
	
							
				)
		);
	}
	
	
	echo json_encode($data);	
}
?>