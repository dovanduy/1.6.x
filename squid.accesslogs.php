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
if(isset($_GET["tabs-all"])){tabs_all();exit;}
if(isset($_GET["external"])){external();exit;}
if(isset($_GET["events-list"])){events_search();exit;}
if(isset($_GET["container-list"])){container_list();exit;}
if(isset($_GET["log-js"])){log_js();exit;}
if(isset($_GET["store-file"])){store_file();exit;}
if(isset($_GET["downloadgz"])){downloadgz();exit;}
if(isset($_GET["downloadf"])){downloadf();exit;}
if(isset($_GET["uncompress"])){uncompress_file();exit;}
if(isset($_GET["uncompress-check"])){uncompress_file_check();exit;}
if(isset($_GET["delete-check"])){uncompress_file_delete();exit;}
if(isset($_POST["csv-delete"])){csv_delete();exit;}
if(isset($_POST["empty-store"])){empty_store();exit;}
page();

function tabs_all(){


	$fontsize=16;
	$tpl=new templates();
	$page=CurrentPageName();
	$array["events-squidaccess"]='{realtime_requests}';
	$array["today-squidaccess"]='{today}';
	$array["watchdog"]="{squid_watchdog_mini}";
	$array["events-squidcache"]='{proxy_service_events}';
	$array["parameters"]='{log_retention}';


	while (list ($num, $ligne) = each ($array) ){

		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?popup=yes&url-row=555\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}
		
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.params.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
				
		
		if($num=="today-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.access.today.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}

		if($num=="watchdog"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.watchdog-events.php\">
			<span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="events-squidcache"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.cachelogs.php\"><span>$ligne</span></a></li>\n");
			continue;
		}



		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "main_squid_logs_tabs")."<script>LeftDesign('logs-white-256-opac20.png');</script>";


}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h$timezone");
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
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
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
		
	if(isset($_GET["bypopup"])){
		$table_size=1019;
		$url_row=576;
		$member_row=333;
		$distance_width=352;
		$margin=0;
		$margin_left="-5";
		$tableprc="99%";
		$button1="{name: '<strong id=refresh-$t>$stopRefresh</stong>', bclass: 'Reload', onpress : StartStopRefresh$t},";
		$table_height=590;
		$Start="StartRefresh$t()";
	}

	$q=new mysql_squid_builder();
	$countContainers=$q->COUNT_ROWS("squid_storelogs");
	if($countContainers>0){
		$button2="{name: '<strong id=container-log-$t>$logs_container</stong>', bclass: 'SSQL', onpress : StartLogsContainer$t},";
		$button_container="{name: '<strong id=container-log-$t>$back_to_events</stong>', bclass: 'SSQL', onpress : StartLogsSquidTable$t},";
		$button_container_delall="{name: '$empty', bclass: 'Delz', onpress : EmptyStore$t},";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(Compressedsize) as tsize FROM squid_storelogs"));
		$title_table_storage="$logs_container $countContainers $files (".FormatBytes($ligne["tsize"]/1024).")";
	}
	
	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";

	
	$buttons[]="{name: '<strong>$reload_proxy_service</stong>', bclass: 'Reload', onpress : ReloadProxy$t},";
	
	
$html="
	<div style='margin:{$margin}px;margin-left:{$margin_left}px' id='$t-main-form'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:$tableprc'></table>
	</div>
	<input type='hidden' id='refresh$t' value='1'>
<script>
var mem$t='';
function StartLogsSquidTable$t(){
	document.getElementById('$t-main-form').innerHTML='';
	document.getElementById('$t-main-form').innerHTML='<table class=\"flexRT$\" style=\"display: none\" id=\"flexRT$t\" style=\"width:$tableprc\"></table>';

	$('#flexRT$t').flexigrid({
		url: '$page?events-list=yes',
		dataType: 'json',
		colModel : [
			{display: '&nbsp;', name : 'filetime', width :16, sortable : true, align: 'left'},
			{display: '$zdate', name : 'zDate', width :52, sortable : true, align: 'left'},
			{display: '$uri', name : 'events', width : $url_row, sortable : false, align: 'left'},
			{display: '$member', name : 'mmeber', width : $member_row, sortable : false, align: 'left'},
			],
			
	buttons : [
			
			],
			
		
		searchitems : [
			{display: '$sitename', name : 'sitename'},
			{display: '$uri', name : 'uri'},
			{display: '$member', name : 'uid'},
			{display: '$error', name : 'TYPE'},
			{display: '$ipaddr', name : 'CLIENT'},
			{display: '$MAC', name : 'MAC'},
			],
		sortname: 'zDate',
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

function OnlyCached$t(){
	$('#flexRT$t').flexOptions({url: '$page?events-list=yes&cached=1'}).flexReload(); 
}
function OnlyAll$t(){
	$('#flexRT$t').flexOptions({url: '$page?events-list=yes'}).flexReload(); 
}

function ReloadProxy$t(){
	Loadjs('squid.reload.progress.php');

}


function StartLogsContainer$t(){
	document.getElementById('$t-main-form').innerHTML='';
	document.getElementById('$t-main-form').innerHTML='<span id=\"StopRefreshNewTable$t\"></span><table class=\"flexRT$\" style=\"display: none\" id=\"flexRT$t\" style=\"width:$tableprc\"></table>';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
		url: '$page?container-list=yes&t=$t',
		dataType: 'json',
		colModel : [
			
			{display: '$zdate', name : 'filetime', width :162, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'filetime', width :$distance_width, sortable : true, align: 'left'},
			{display: '$filename', name : 'filename', width :154, sortable : false, align: 'left'},
			{display: '$ext', name : 'fileext', width :33, sortable : false, align: 'center'},
			{display: '$size', name : 'filesize', width : 92, sortable : true, align: 'left'},
			{display: '$Compressedsize', name : 'Compressedsize', width : 92, sortable : true, align: 'left'},
			{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
			],
			
	buttons : [
			$button_container
			],
			
		
		searchitems : [
			{display: '$sitename', name : 'sitename'},
			{display: '$uri', name : 'uri'},
			{display: '$member', name : 'uid'},
			{display: '$error', name : 'TYPE'},
			{display: '$ipaddr', name : 'CLIENT'},
			],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '$title_table_storage',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $table_size,
		height: $table_height,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
		
		});   
	});
}

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

function ZoomSquidAccessLogs(){
	s_PopUp('squid.accesslogs.php?external=yes',1024,768);
}



function  StartStopRefresh$t(){
	var ratxt='$stopRefresh';
	var rstxt='$refresh';
	var refresh=document.getElementById('refresh$t').value;
	if(refresh==1){
		document.getElementById('refresh$t').value=0;
		document.getElementById('refresh-$t').innerHTML='$refresh';
	}else{
		document.getElementById('refresh$t').value=1;
		document.getElementById('refresh-$t').innerHTML='$stopRefresh';	
		$('#flexRT$t').flexReload();
	}
}

function StartRefresh$t(){
	if(!document.getElementById('flexRT$t')){return;}
	var refresh=document.getElementById('refresh$t').value;
	
	if(refresh==1){
		if(!document.getElementById('StopRefreshNewTable$t')){
			$('#flexRT$t').flexReload();
		}
	}
	
	setTimeout('StartRefresh$t()',5000);
	
}

function LogsContainer$t(){
	StartLogsContainer$t()
}

function SquidRotate$t(){
	Loadjs('squid.perf.logrotate.php?tabs=squid_main_svc');
}

var x_LogsCsvDelte$t = function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}	
	$('#row'+mem$t).remove();
}		
var x_EmptyStore$t = function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}	
	$('#flexRT$t').flexReload();
}	
function EmptyStore$t(){
	if(confirm('$askdelete')){
		var XHR = new XHRConnection();
		XHR.appendData('empty-store','yes');	
		XHR.sendAndLoad('$page', 'POST',x_EmptyStore$t);		
	}
}


function LogsCsvDelte$t(ID,md5){
	mem$t=md5;
	if(confirm('$delete_file :'+ID)){
		var XHR = new XHRConnection();
		XHR.appendData('csv-delete',ID);	
		XHR.sendAndLoad('$page', 'POST',x_LogsCsvDelte$t);	
	}
}
setTimeout('StartLogsSquidTable$t()',800);	
$Start;
	
</script>
";
echo $html;
	
}





function events_search(){
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$q=new mysql_squid_builder();
$sock=new sockets();
$sock->getFrameWork("squid.php?rttlogs-parse=yes");


$GLOBALS["Q"]=$q;
$table="squidhour_".date("YmdH");	
	
		
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
	if(mysql_num_rows($results)==0){json_error_show("no data",2);}
	
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
		$uri=$ligne["uri"];
		$date=$ligne["zDate"];
		$mac=$ligne["MAC"];
		$ip=$ligne["CLIENT"];
		$user=$ligne["uid"];
		$dom=$ligne["sitename"];
		$cached=$ligne["cached"];
		$return_code=$ligne["TYPE"];
		$size=$ligne["QuerySize"];
		$ident=array();
		$md=md5(serialize($ligne));
		$today=date("Y-m-d");
		$date=str_replace($today, "", $date);
		$ident[]="<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$ip',true);\"
		style='text-decoration:underline;color:$color'>$ip</a>";
		$spanON="<span style='color:$color'>";
		$spanOFF="</span>";
		$cached_text=null;
		
		$size=FormatBytes($size/1024);
		
		if($return_code=="Not Found"){$color="#BA0000";}
		if($return_code=="Service Unavailable"){$color="#BA0000";}
		if($return_code=="Bad Gateway"){$color="#BA0000";}
		$return_code_text="<div style='color:$color;font-size:11px'><i>&laquo;$return_code&raquo;$cached_text - $size</i></div>";
		
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
		if($cached==1){$cached_text=" - $cachedT";$colorDiv="#00B954";}
		
		$www=$q->PostedServerToHost($uri);
		$time=time();
		$uri=str_replace($www, "<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.website-zoom.php?js=yes&sitename=$www&xtime=$time')\"
		style='text-decoration:underline;color:$color;font-weight:bold'>$www</a>",$uri);
		
		
		
		
				
		$data['rows'][] = array(
			'id' => $md,
			'cell' => array(
				"<div style='background-color:$colorDiv;margin-top:-5px;margin-left:-5px;margin-right:-5px;margin-bottom:-5px;'>&nbsp;</div>",
				"$spanON$date$spanOFF",
				"$spanON$uri.$return_code_text$spanOFF",
				"$spanON$identities$spanOFF"
				)
			);
				
		
			

	}
	
	echo json_encode($data);	
}

function GetDomainFromURl($myurl){
$raw_url = parse_url($myurl);
$domain_only =str_replace ('www.','', $raw_url);
return $domain_only['host'];  	
	
}

function external(){
	$page=CurrentPageName();
	$tpl=new template_users();
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?bypopup=yes');
	</script>";
	
$tpl=new template_users("{events}::{APP_SQUID}",$html,0,1,1);

$tpl->_BuildPopUpNew($html,"{events}::{APP_SQUID}");
$html=$tpl->web_page;
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;		
	
	
	
	
}


function container_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$search='%';
	$table="squid_storelogs";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT `ID`,`filename`,`fileext`,`filesize`,`Compressedsize`,`filetime` FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	$results=$q->QUERY_SQL($sql);
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!$q->ok){json_error_show($q->mysql_error);}

	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5(serialize($ligne).$t);
		$span="<span style='font-size:16px'>";
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","LogsCsvDelte$t('{$ligne['ID']}','$md5')");
		
		$jsEdit="Loadjs('$MyPage?Rotate-js=yes&ID={$ligne['taskid']}&t=$t');";
		$jstask="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsEdit\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";		
		
		$jslloop="Loadjs('$MyPage?log-js=yes&filename={$ligne['filename']}&t=$t&ID={$ligne['ID']}');";
		$view="<a href=\"javascript:blur();\" OnClick=\"javascript:$jslloop\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";	
		
		if($ligne["filesize"]>1024){$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);}else{$ligne["filesize"]=$ligne["filesize"]." Bytes";}	
		if($ligne["Compressedsize"]>1024){$ligne["Compressedsize"]=FormatBytes($ligne["Compressedsize"]/1024);}else{$ligne["Compressedsize"]=$ligne["Compressedsize"]." Bytes";}		
		
	
		
	
		$time=strtotime($ligne['filetime']);
		$distance=distanceOfTimeInWords($time,time(),false);
		$img="ext/unknown_small.gif";
		if(is_file("img/ext/{$ligne["fileext"]}_small.gif")){
				$img="ext/{$ligne["fileext"]}_small.gif";
		}
		
		$distance=$tpl->javascript_parse_text($distance);
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
		"$span$view{$ligne['filetime']}</a></span>",
		"$span$view{$distance}</a></span>",
		"$span$view{$ligne["filename"]}</a></span>",
		"<img src='img/$img'>",
		"$span{$ligne["filesize"]}</a></span>",
		"$span{$ligne["Compressedsize"]}</a></span>",
		$delete )
		);
	}
	
	
echo json_encode($data);	
	
}

function log_js(){
	$page=CurrentPageName();
	
	if($_GET["filename"]==null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename FROM squid_storelogs WHERE ID = '{$_GET["ID"]}'"));
		$_GET["filename"]=$ligne["filename"];
	}
	
	$html="YahooWin5('550','$page?store-file=yes&t={$_GET["t"]}&ID={$_GET["ID"]}','{$_GET["filename"]}')";
	echo $html;
}
function store_file(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `filename`,`fileext`,`filesize`,`Compressedsize`,`filetime` FROM squid_storelogs WHERE ID={$_GET["ID"]}"));
	
	$t=time();
	
	
	$chekScript="UnCompressCheck$t();";
	
	
	if($ligne["filesize"]>0){$downloadgz="<a href=\"javascript:blur();\" style='font-size:16px;text-decoration:underline;font-weight:bold' 
	OnClick=\"javascript:s_PopUpFull('$page?downloadgz={$_GET["ID"]}','50','50');\">";}
	
	
	
	$ttr=explode(".", $ligne["filename"]);
	$uncompressedfile="ressources/logs/{$ttr[0]}.{$ligne["fileext"]}";
	
	$s=intval($ligne["Compressedsize"]-$ligne["filesize"]);
	$pourc=round($s/$ligne["filesize"],2);
	$pourc="{rate}:&nbsp;".($pourc*100) . "%";
	$pourc=str_replace("-", "", $pourc);
	
	if($ligne["filesize"]>1024){$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);}else{$ligne["filesize"]=$ligne["filesize"]." Bytes";}	
	if($ligne["Compressedsize"]>1024){$ligne["Compressedsize"]=FormatBytes($ligne["Compressedsize"]/1024);}else{$ligne["Compressedsize"]=$ligne["Compressedsize"]." Bytes";}		
		
	if(function_exists("gzopen")){
		if(!is_file("$uncompressedfile")){
			$button="<div style='text-align:right' style='width:100%'>". button("{uncompress}","UnCompress$t()","18px")."</div>";
			$chekScript=null;
			
		}
	}else{
		$button="gzopen no such function...";
	}
	
	
	$ext=
	
	
	$html="
	<input type='hidden' id='$t-mem-id' value='{$_GET["ID"]}'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{zDate}:</td>
		<td style='font-size:16px;font-weight:bold'>{$ligne["filetime"]}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{filename}:</td>
		<td style='font-size:16px;font-weight:bold'>$downloadgz{$ligne["filename"]}</a> (.{$ligne["fileext"]})</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{compressed_size}:</td>
		<td style='font-size:16px;font-weight:bold'>{$ligne["Compressedsize"]}&nbsp;$pourc</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{realsize}:</td>
		<td style='font-size:16px;font-weight:bold'>{$ligne["filesize"]}</td>
	</tr>
	
		
</table>
$button
<div id='uncompress-$t'></div>
<script>
	function UnCompress$t(){
		LoadAjax('uncompress-$t','$page?uncompress={$_GET["ID"]}&t=$t');
	
	}
	
	function UnCompressCheck$t(force){
		if(!force){force=0;}
		LoadAjax('uncompress-$t','$page?uncompress-check={$_GET["ID"]}&t=$t&force='+force);
	
	}
	$chekScript;
</script>


";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function uncompress_file(){
	$tpl=new templates();
	@chmod("ressources/logs",0777);
	$ID=$_GET["uncompress"];
	if(!is_numeric($ID)){die();}
	$t=$_GET["t"];
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$mydir=dirname(__FILE__);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename,fileext FROM squid_storelogs WHERE ID = '$ID'"));
	
	$filename=$ligne["filename"];
	
	$ttr=explode(".", $ligne["filename"]);
	$uncompressedfile="ressources/logs/{$ttr[0]}.{$ligne["fileext"]}";	
	
	$filepath="$mydir/ressources/logs/$filename";
	writelogs("uncompress filename ID:{$_GET["uncompress"]}",__FUNCTION__,__FILE__,__LINE__);
	
	
	$sql="SELECT filecontent INTO DUMPFILE '$filepath' FROM squid_storelogs WHERE ID = '$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL("$sql"));
	
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body("<H1 style='color:red;background:none'>{failed} $q->mysql_error</H1>");
	}
	
	if(!uncompress($filepath, $uncompressedfile)){
		@unlink($filepath);
		echo $tpl->_ENGINE_parse_body("<H1 style='color:red;background:none'>{failed}</H1>");
		return;
	}
		
	@unlink($filepath);
	echo "<script>UnCompressCheck$t(1)</script>";
}
function uncompress_file_check(){
	@chmod("ressources/logs",0777);
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["uncompress-check"];
	if(!is_numeric($ID)){die();}
	$t=$_GET["t"];
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$mydir=dirname(__FILE__);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename,fileext FROM squid_storelogs WHERE ID = '$ID'"));
	
	$filename=$ligne["filename"];
	
	$ttr=explode(".", $ligne["filename"]);
	$uncompressedfile="ressources/logs/{$ttr[0]}.{$ligne["fileext"]}";	
	
	if(is_file($uncompressedfile)){
		$tt=time();
		$link="<a href=\"javascript:blur();\" style='font-size:16px;text-decoration:underline;font-weight:bold' 
	OnClick=\"javascript:s_PopUpFull('$page?downloadf={$ttr[0]}.{$ligne["fileext"]}','50','50');\">";
		
		$html="<table style='width:99%' class=form>
		<tr>
			<td width=1%><img src='img/icon-download.png'></td>
			<td style='font-size:16px'>{download_file}: $link{$ttr[0]}.{$ligne["fileext"]}</a></td>
			<td width=1%>". imgtootltip("delete-32.png","{delete}","DeleteFile$tt()")."</td>
		</tr>
		</table>
		<div id='id-$tt'></div>
		<script>
				function DeleteFile$tt(){
					LoadAjax('id-$tt','$page?delete-check={$ttr[0]}.{$ligne["fileext"]}&t=$t');
				}
		</script>
		
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}

	if($_GET["force"]==1){
		echo $tpl->_ENGINE_parse_body("<H1 style='color:red;background:none'>$uncompressedfile no such file</H1>");
		
	}
	
}
function downloadf(){
	$sock=new sockets();
	$filename=$_GET["downloadf"];
	$filepath=dirname(__FILE__)."/ressources/logs/$filename";	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($filepath)));
	
	$fsize = filesize($filepath);
	header("Content-Length: ".$fsize); 	
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$filename\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($filepath);
	@unlink($filepath);		
}
function csv_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM squid_storelogs WHERE ID={$_POST["sv-delete"]}");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function empty_store(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE squid_storelogs");
	if(!$q->ok){echo $q->mysql_error;}	
}


function downloadgz(){
	@chmod("ressources/logs",0777);
	if(!is_numeric($_GET["downloadgz"])){die();}
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$mydir=dirname(__FILE__);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename,fileext FROM squid_storelogs WHERE ID = '{$_GET["downloadgz"]}'"));
	
	$filename=$ligne["filename"];
	$filepath="$mydir/ressources/logs/$filename";
	writelogs("Send filename ID:{$_GET["downloadgz"]} $content_type ($fsize)",__FUNCTION__,__FILE__,__LINE__);
	
	
	$sql="SELECT filecontent INTO DUMPFILE '$filepath' FROM squid_storelogs WHERE ID = '{$_GET["downloadgz"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL("$sql"));
	
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($filepath)));
	
	$fsize = filesize("{$ligne["filename"]}");

	
	
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$filename\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($filepath);
	@unlink($filepath);	
	
}
function uncompress($srcName, $dstName) {
	    $sfp = gzopen($srcName, "rb");
	    $fp = fopen($dstName, "w");
	    while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	    gzclose($sfp);
	    fclose($fp);
	    return true;
}

function uncompress_file_delete(){
	$page=CurrentPageName();
	$filename=$_GET["delete-check"];
	@unlink("ressources/logs/$filename");
	$t=$_GET["t"];
	echo "
	<script>
		Loadjs('$page?log-js=yes&ID='+document.getElementById('$t-mem-id').value);
	</script>
	
	";
	
}