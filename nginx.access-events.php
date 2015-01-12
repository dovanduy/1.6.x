<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	if(isset($_GET["website-script-tab"])){websites_script_tab();exit;}
	if(isset($_GET["website-script"])){websites_script();exit;}
	if(isset($_POST["nginxconf"])){websites_script_nginxconf();exit;}
	if(isset($_GET["events-list"])){events_list();exit;}
popup();




function popup(){
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
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$proto=$tpl->javascript_parse_text("{proto}");
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

	$date_field=139;
	$uri_field=351;
	$cod_field=128;
	$size_field=71;
	$duration_field=106;
	$ip_field=114;

	if($_GET["minsize"]==1){
		$uri_field=450;
		$date_field=76;
		$ip_field=161;
		$cod_field=103;
		$size_field=86;
		$duration_field=66;
	}


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
		
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>

	<input type='hidden' id='refresh$t' value='1'>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes&minsize={$_GET["minsize"]}&servername={$_GET["servername"]}',
	dataType: 'json',
	colModel : [
		
	{display: '$zdate', name : 'zDate', width :$date_field, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'events', width : $ip_field, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'code', width : $cod_field, sortable : false, align: 'left'},
	{display: '$proto', name : 'proto', width : 42, sortable : false, align: 'left'},
	{display: '$uri', name : 'events', width : $uri_field, sortable : false, align: 'left'},
	{display: '$size', name : 'size', width : $size_field, sortable : false, align: 'right'},
	
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
	width: '98.5%',
	height: 640,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});

}



StartLogsSquidTable$t();
</script>";
	echo $html;
}

function events_list(){
	$sock=new sockets();
	$servername=urlencode($_GET["servername"]);
	$sock->getFrameWork("nginx.php?access-real=yes&servername=$servername&rp={$_POST["rp"]}&query=".urlencode($_POST["query"]));
	$filename="/usr/share/artica-postfix/ressources/logs/access.log.{$_GET["servername"]}.tmp";
	$dataZ=explode("\n",@file_get_contents($filename));
	$tpl=new templates();
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($data);
	$data['rows'] = array();
	$today=date("Y-m-d");
	$tcp=new IP();

	$cachedT=$tpl->_ENGINE_parse_body("{cached}");
	$c=0;

	if(count($dataZ)==0){json_error_show("no data");}
	//$logfileD=new logfile_daemon();

krsort($dataZ);
	while (list ($num, $line) = each ($dataZ)){
		if(!preg_match('#(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+([A-Z]+)\s+(.+?)\s+[A-Z]+\/.*?\s+"([0-9]+)"\s+([0-9]+)\s+"(.+.?)"\s+\"(.+?)"\s+\[([A-Z]+)\]#', $line,$TR)){continue;}
		$c++;
		$color="black";
		
		
		$TR[4]=strtotime($TR[4]);
		$date=date("Y-m-d H:i:s",$TR[4]);
		$ip=$TR[1];
		$CacheCode=$TR[11];
		$zCode=$TR[7];
		$size=$TR[8];
		$PROTO=$TR[5];
		$uri=$TR[6];
		$strong=null;
		
		if($PROTO=="CONNECT"){$color="#BAB700";}
		if($zCode>399){$color="#D0080A";}

		
		if($CacheCode=="HIT"){
			$strong="font-weight:bold";
			$CacheCode="HIT/Cached";
			$color="#005447";
		}
		
		
		
		if($CacheCode=="MISS"){
			$CacheCode="MISS/Not cached";
				
		}		

		$fontsize=12;
		if($_GET["minsize"]==1){
			$fontsize=12;
		}

		if($size>1024){$size=FormatBytes($size/1024);}else{$size="$size Bytes";}
		$date=str_replace($today." ", "", $date);
		$data['rows'][] = array(
				'id' => md5($line),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>$date</span>",
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>$ip</span>",
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>{$zCode}/$CacheCode</span>",
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>{$PROTO}</span>",
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>$uri</span>",
						"<span style='font-size:{$fontsize}px;color:$color;$strong'>$size</span>",

				)
		);

	}

	if($c==0){json_error_show("no data");}
	$data['total'] = $c;
	echo json_encode($data);

}