<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


if(isset($_GET["js"])){js();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["service-cmd-js"])){service_cmd_js();exit;}
if(isset($_POST["service"])){service_cmd_perform();exit;}

popup();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{services}");
	$page=CurrentPageName();
	$artica_meta=new mysql_meta();
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	echo "YahooWin3('990','$page?uuid=".urlencode($_GET["uuid"])."','$title:$hostname')";
}


function service_cmd_perform(){
	$artica=new mysql_meta();
	$artica->CreateOrder($_POST["uuid"], "SERVICE_CMD",array("action"=>$_POST["service"],"cmdline"=>$_POST["cmdline"]));
	
}

function service_cmd_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	//action=start&cmdline=$service_cmd&uuid=$uuid
	$text=$tpl->javascript_parse_text("{$_GET["action"]} {{$_GET["app"]}} ?");
	
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('service','{$_GET["action"]}');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('cmdline','{$_GET["cmdline"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;
	
}




function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$start=$tpl->_ENGINE_parse_body("{start}");
	$stop=$tpl->_ENGINE_parse_body("{stop}");
	$restart=$tpl->_ENGINE_parse_body("{restart}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$product=$tpl->javascript_parse_text("{product}");
	$processes=$tpl->javascript_parse_text("{processes}");
	$running=$tpl->javascript_parse_text("{running}");
	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	$extension="&uuid=".urlencode($_GET["uuid"]);

	$buttons="
	buttons : [
	{name: 'Crit.', bclass: 'Err', onpress :  Err$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
	
	

	],	";
	$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>

function BuildTable$t(){
	$('#events-table-$t').flexigrid({
		url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension',
		dataType: 'json',
		colModel : [
		{display: '', name : 'severity', width :45, sortable : false, align: 'center'},
		{display: '$product', name : 'service_name', width :223, sortable : true, align: 'left'},
		{display: '$version', name : 'master_version', width : 127, sortable : true, align: 'left'},
		{display: '$memory', name : 'master_memory', width :145, sortable : true, align: 'left'},
		{display: '$processes', name : 'processes_number', width :35, sortable : true, align: 'center'},
		{display: '$running', name : 'uptime', width :167, sortable : true, align: 'left'},
		{display: '$stop', name : 'stop', width :35, sortable : true, align: 'center'},
		{display: '$start', name : 'start', width :35, sortable : true, align: 'center'},
		{display: '$restart', name : 'restart', width :35, sortable : true, align: 'center'},
		],
		$buttons
	
		searchitems : [
		{display: '$events', name : 'subject'},
		],
		sortname: 'processes_number',
		sortorder: 'desc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: $TB_HEIGHT,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]

	});
}

function articaShowEvent(ID){
	YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
}

var x_EmptyEvents= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#events-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload();
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();

}

function Warn$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=1$extension'}).flexReload(); 
}
function info$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=2$extension'}).flexReload(); 
}
function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension&running=0'}).flexReload(); 
}
function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension'}).flexReload(); 
}
function Params$t(){
	Loadjs('squid.proxy.watchdog.php');
}

function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
}
setTimeout(\" BuildTable$t()\",800);
</script>";

echo $html;

}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_meta();
	$FORCE2="AND uuid='{$_GET["uuid"]}'";
	$FORCE=1;
	$search='%';
	$table="global_status";
	$page=1;
	$ORDER="ORDER BY service_name asc";
	if(isset($_GET["running"])){
		if($_GET["running"]==0){
			$FORCE="running={$_GET["running"]} AND service_disabled=1";
		}else{
			$FORCE="running={$_GET["running"]}";
		}
	}

	$uuid=$_GET["uuid"];
	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$severity[0]="22-red.png";
	$severity[1]="22-warn.png";
	$severity[2]="22-infos.png";
	$currentdate=date("Y-m-d");

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $FORCE2 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
		
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $FORCE2";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $FORCE2 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	if(!$q->ok){json_error_show($q->mysql_error,1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$master_cached_memory=0;
		$service_name=$tpl->javascript_parse_text("{".$ligne["service_name"]."}");
		$service_cmd=$ligne["service_cmd"];
		$service_disabled=$ligne["service_disabled"];
		$watchdog_features=$ligne["watchdog_features"];
		$binpath=$ligne["binpath"];
		$explain=$ligne["explain"];
		$running=$ligne["running"];
		$installed=$ligne["installed"];
		$master_pid=$ligne["master_pid"];
		$master_memory=FormatBytes($ligne["master_memory"]);
		
		$master_cached_memory=$ligne["master_cached_memory"];
		$processes_number=$ligne["processes_number"];
		$uptime=$ligne["uptime"];
		$master_version=$ligne["master_version"];
		if($master_version<>null){$installed=1;}
		
		$service_cmd=urlencode($service_cmd);
		$start=imgsimple("24-run.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=start&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
		$stop=imgsimple("24-stop.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=stop&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
		$restart=imgsimple("restart-24.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=restart&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
		
		if($installed==1){
			if($running==1){
				$severity_icon="ok22.png";
				$start="-";
			}else{
				$severity_icon="22-red.png";
				$stop="-";
			}
		}
		
		if($service_disabled==0){
			$severity_icon="22-warn.png";
			if($master_memory==0){$master_memory="-";}
			$stop="-";
			$start="-";
			$restart="-";
			if($uptime==null){$uptime="-";}
		}
		
		
		if($installed==0){
			$severity_icon="ok22-grey.png";
			$master_version="-";
			$master_memory="-";
			$uptime="-";
			$stop="-";
			$start="-";
			$restart="-";
		}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<img src='img/$severity_icon'>",
						
						$service_name,$master_version,$master_memory,$processes_number,$uptime,$stop,$start,$restart )
		);
	}


	echo json_encode($data);

}