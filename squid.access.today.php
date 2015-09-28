<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.accesslogs.inc');
	include_once('ressources/class.tcpip.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["events-list"])){events_search();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{hour}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");	
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$rotate_logs=$tpl->javascript_parse_text("{rotate_logs}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$table_size=855;
	$url_row=505;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["url-row"])){$url_row=$_GET["url-row"];}
		
	$q=new mysql_squid_builder();

	$table=date("Ymd")."_hour";
	
	if(!$q->TABLE_EXISTS($table)){
		$hierx=strtotime($q->HIER()." 00:00:00");
		$table=date("Ymd",$hierx)."_hour";
		$title=$tpl->_ENGINE_parse_body("{yesterday}: {realtime_requests}");
	}
	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";

$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:$tableprc'></table>
<script>
var mem$t='';
function StartLogsSquidTable$t(){
	$('#flexRT$t').flexigrid({
		url: '$page?events-list=yes',
		dataType: 'json',
		colModel : [
			{display: '&nbsp;', name : 'filetime', width :16, sortable : true, align: 'left'},
			{display: '$zdate', name : 'hour', width :52, sortable : true, align: 'left'},
			{display: '$uri', name : 'servername', width : $url_row, sortable : false, align: 'left'},
			{display: '$size', name : 'size', width : 110, sortable : true, align: 'left'},
			{display: '$member', name : 'uid', width : $member_row, sortable : false, align: 'left'},
			],
			

		searchitems : [
			{display: '$sitename', name : 'sitename'},
			{display: '$member', name : 'uid'},
			{display: '$error', name : 'TYPE'},
			{display: '$ipaddr', name : 'CLIENT'},
			{display: '$MAC', name : 'MAC'},
			],
		sortname: 'hour',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=\"font-size:16px\">$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: $table_height,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
		
		});   

}
setTimeout('StartLogsSquidTable$t()',800);	
	
</script>
";
echo $html;
	
}
function events_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$GLOBALS["Q"]=$q;
	$table=date("Ymd")."_hour";

	if(!$q->TABLE_EXISTS($table)){
		$hierx=strtotime($q->HIER()." 00:00:00");
		$table=date("Ymd",$hierx)."_hour";
	}

	if(isset($_POST['page'])) {$page = $_POST['page'];}
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{

		$total = $q->COUNT_ROWS($table);
	}

	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date("Y-m-d");
	$tcp=new IP();

	$cachedT=$tpl->_ENGINE_parse_body("{cached}");
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$return_code_text=null;
		$ff=array();
		$color="black";
		$uri=$ligne["sitename"];
		$date=$ligne["hour"];
		$mac=$ligne["MAC"];
		$ip=$ligne["client"];
		$user=$ligne["uid"];
		$dom=$ligne["familysite"];
		if(intval($ligne["size"])>1024){
			$size=FormatBytes(intval($ligne["size"])/1024);
		}else{
			$size="{$ligne["size"]} bytes";
		}
		
		if($size=="0 KB"){$size="{$ligne["size"]} bytes";}
		
		$category=$ligne["category"];
		//sitename                 | familysite     | client        
		//| hostname | account | hour | remote_ip     | MAC               | country | size  | hits | uid  | category
		
		
		$ident=array();
		$md=md5(serialize($ligne));

		$ident[]="<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$ip',true);\"
		style='text-decoration:underline;color:$color'>$ip</a>";
		$spanON="<span style='color:$color'>";
		$spanOFF="</span>";
		$cached_text=null;

		$size=FormatBytes($size/1024);

		$return_code_text="<div style='color:$color;font-size:11px'><i>$size</i></div>";

		if($user<>null){
			$GLOBALS["IPUSERS"][$ip]=$user;
		}else{
			if(isset($GLOBALS["IPUSERS"][$ip])){

				$ident[]="<i>{$GLOBALS["IPUSERS"][$ip]}</i>";
			}
		}

		if($user<>null){
			if($tcp->isValid($user)){
				$ident[]="<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$user',true);\"
				style='text-decoration:underline;color:$color'>$user</a>";
			}else{
				$ident[]="<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&uid=$user',true);\"
				style='text-decoration:underline;color:$color'>$user</a>";
			}
		}

		if($mac<>null){
			$ident[]="<a href=\"javascript:blur()\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac',true);\"
			style='text-decoration:underline;color:$color'>$mac</a>";

		}
		$colorDiv=$color;
		if($colorDiv=="black"){$colorDiv="transparent";}
		$identities=@implode("&nbsp;|&nbsp;", $ident);
		

		$www=$q->PostedServerToHost($uri);
		$time=time();
		$uri=str_replace($www, "<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.website-zoom.php?js=yes&sitename=$www&xtime=$time')\"
				style='text-decoration:underline;color:$color;font-weight:bold'>$www</a>",$uri);



		if($category<>null){$category=" ($category)";}

		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						"<div style='background-color:$colorDiv;margin-top:-5px;margin-left:-5px;margin-right:-5px;margin-bottom:-5px;'>&nbsp;</div>",
						"$spanON{$date}h$spanOFF",
						"$spanON$uri$category$spanOFF",
						"$spanON$size$spanOFF",
						"$spanON$identities$spanOFF"
				)
		);


			

	}

	echo json_encode($data);
}