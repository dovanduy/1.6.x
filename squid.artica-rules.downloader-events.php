<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');



if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_POST["empty-table"])){empty_table();exit;}
if(isset($_GET["realtime"])){realtime();exit;}
if(isset($_GET["realtime-table"])){realtime_table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["webevents"])){webevents();exit;}
if(isset($_GET["webevents-table"])){webevents_table();exit;}
tabs();



function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$array["realtime"]='{realtime}';
	$array["popup"]='{service2}';
	$array["webevents"]='{webservice}';
	
	
	
	
		while (list ($num, $ligne) = each ($array) ){
	
			if($num=="downloader-event"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.downloader-events.php\"
						style='font-size:18px'><span>$ligne</span></a></li>\n");
				continue;
	
			}
	
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"
					style='font-size:18px'><span>$ligne</span></a></li>\n");
		}
		echo build_artica_tabs($html, "main_artica_downloader_events");
	
	
	}	


function ShowID_js(){
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
		
		return;
	
	}$tpl=new templates();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sql="SELECT subject FROM squid_admin_enforce WHERE ID=$id";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	echo "YahooWin3('550','$page?ShowID=$id','$subject')";
	
}
function ShowID(){

$tpl=new templates();
$sql="SELECT content FROM squid_admin_enforce WHERE ID={$_GET["ShowID"]}";
$q=new mysql();
$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));

$content=$tpl->_ENGINE_parse_body($ligne["content"]);
$content=nl2br($content);
echo "<p style='font-size:18px'>$content</p>";
}

function empty_table(){
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE squid_admin_enforce","artica_events");
}

function realtime(){
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	
	$buttons="
	buttons : [
	{name: 'Warn', bclass: 'Warn', onpress :  Warn$t},
	{name: 'Info', bclass: 'Help', onpress :  info$t},
	{name: 'Crit.', bclass: 'Err', onpress :  Err$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
	
	
	
	],	";
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>
	
	function BuildTable$t(){
	$('#events-table-$t').flexigrid({
	url: '$page?realtime-table=yes&text-filter={$_GET["text-filter"]}',
		dataType: 'json',
			colModel : [
			{display: '', name : 'severity', width :31, sortable : true, align: 'center'},
			{display: '$date', name : 'zDate', width :60, sortable : true, align: 'left'},
			{display: '$events', name : 'subject', width : 901, sortable : false, align: 'left'},
			{display: '$daemon', name : 'filename', width :46, sortable : true, align: 'left'},
			],
			$buttons
	
	searchitems : [
	{display: '$events', name : 'subject'},
	],
	sortname: 'zDate',
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
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=1'}).flexReload();
	}
	function info$t(){
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=2'}).flexReload();
	}
	function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=0'}).flexReload();
	}
	function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes'}).flexReload();
	}
	function Params$t(){
	Loadjs('squid.proxy.watchdog.php');
	}
	
	function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
	}
	setTimeout(\" BuildTable$t()\",800);
	</script>";
	
	echo $html;
	
	}

function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();

	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	{name: 'Warn', bclass: 'Warn', onpress :  Warn$t},
	{name: 'Info', bclass: 'Help', onpress :  info$t},
	{name: 'Crit.', bclass: 'Err', onpress :  Err$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
	
	

	],	";
	$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>

function BuildTable$t(){
	$('#events-table-$t').flexigrid({
		url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}',
		dataType: 'json',
		colModel : [
		{display: '', name : 'severity', width :31, sortable : true, align: 'center'},
		{display: '$date', name : 'zDate', width :127, sortable : true, align: 'left'},
		{display: '$events', name : 'subject', width : 762, sortable : false, align: 'left'},
		{display: '$daemon', name : 'filename', width :119, sortable : true, align: 'left'},
		],
		$buttons
	
		searchitems : [
		{display: '$events', name : 'subject'},
		],
		sortname: 'zDate',
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
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=1'}).flexReload(); 
}
function info$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=2'}).flexReload(); 
}
function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=0'}).flexReload(); 
}
function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes'}).flexReload(); 
}
function Params$t(){
	Loadjs('squid.proxy.watchdog.php');
}

function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
}
setTimeout(\" BuildTable$t()\",800);
</script>";

echo $html;

}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$FORCE=1;
	$search='%';
	$table="squid_admin_enforce";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	if(is_numeric($_GET["critical"])){
		$FORCE="severity={$_GET["critical"]}";
	}
	
	if($_GET["text-filter"]<>null){
		$FORCE=" subject LIKE '%{$_GET["text-filter"]}%'";
		if(is_numeric($_GET["critical"])){
			$FORCE=$FORCE." AND severity={$_GET["critical"]}";
		}
	}

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
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
		
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
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
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
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
		
		$hostname=$ligne["hostname"];
		$ligne["zDate"]=str_replace($currentdate, "", $ligne["zDate"]);
		$severity_icon=$severity[$ligne["severity"]];
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$CurrentPage?ShowID-js={$ligne["ID"]}')\" style='text-decoration:underline'>";
		$text=$link.$tpl->_ENGINE_parse_body($ligne["subject"]."</a><div style='font-size:10px'>{host}:$hostname {function}:{$ligne["function"]}, {line}:{$ligne["line"]}</div>");
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<img src='img/$severity_icon'>",
						
						$ligne["zDate"],$text,$ligne["filename"] )
		);
	}


	echo json_encode($data);

}

function realtime_table(){
	$tempfile="/usr/share/artica-postfix/ressources/logs/web/HyperCache-downloader.debug";
	$sock=new sockets();
	$tpl=new templates();
	$rp=50;
	
	if(isset($_GET["critical"])){
		if($_GET["critical"]<2){
			$_POST['rp']=2000;
		}
	}
	
	$rp = $_POST['rp'];
	$sock->getFrameWork("squid.php?HyperCache-events=yes&rp=$rp&query=".urlencode($_POST["query"]));
	$array=explode("\n",@file_get_contents($tempfile));
	krsort($array);
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();
	
	if(count($array)==0){json_error_show("no data");}
	$q=new mysql_squid_builder();
	$sql="SELECT rulename FROM artica_caches";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$RRULE[0]=$tpl->_ENGINE_parse_body("{daemon}");

	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$RRULE[$ligne["ID"]]=$ligne["rulename"];
	}
	
	
	
	$c=0;
	while (list ($num, $ligne) = each ($array) ){
		if(trim($ligne)==null){continue;}
		$ligne=str_replace("&nbsp;"," ",$ligne);
		$tr=explode(";", $ligne);
		$time=$tr[0];
		$pid=$tr[1];
		$ruleid=$tr[2];
		$sev=intval($tr[3]);
		if(isset($_GET["critical"])){
			if($_GET["critical"]==0){
				if($sev<>0){continue;}
			}
			if($_GET["critical"]==1){
				if($sev<>1){continue;}
			}			
			
		}
		
		$xline=$tr[4];
		$line=$tr[5];
		$ID=md5($ligne);
		$c++;
		
		$severity[0]="22-red.png";
		$severity[1]="22-warn.png";
		$severity[2]="22-infos.png";
		$severity[3]="22-infos.png";
		$severity_icon=$severity[$sev];
		$text=$tpl->_ENGINE_parse_body("$line<br><span style='font-size:10px'>{rule}:{$RRULE[$ruleid]} {line}:$xline</span>");
		
		
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<img src='img/$severity_icon'>",
						
						$time,$text,$pid)
		);
	}

	if($c==0){json_error_show("no data");}
	$data['total'] = $c;
	echo json_encode($data);
		
		
	
	
}


function webevents(){
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$webservice=$tpl->javascript_parse_text("{webservice}");
	$t=time();

	$buttons="
	buttons : [
	
	],	";
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>

	function BuildTable$t(){
	$('#events-table-$t').flexigrid({
	url: '$page?webevents-table=yes&text-filter={$_GET["text-filter"]}',
	dataType: 'json',
	colModel : [
	{display: '$ipaddr', name : 'severity', width :80, sortable : true, align: 'left'},
	{display: '$date', name : 'zDate', width :161, sortable : true, align: 'left'},
	{display: '$events', name : 'subject', width : 717, sortable : false, align: 'left'},
	{display: '$size', name : 'filename', width :77, sortable : true, align: 'right'},
	],
	$buttons

	searchitems : [
	{display: '$events', name : 'subject'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$webservice</span>',
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
$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=1'}).flexReload();
}
		function info$t(){
		$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=2'}).flexReload();
}
		function Err$t(){
		$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=0'}).flexReload();
}
function All$t(){
$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes'}).flexReload();
}
		function Params$t(){
		Loadjs('squid.proxy.watchdog.php');
}

function EmptyEvents(){
if(!confirm('$empty_events_text_ask')){return;}
var XHR = new XHRConnection();
XHR.appendData('empty-table','yes');
XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
}
setTimeout(\" BuildTable$t()\",800);
</script>";

echo $html;

}

function webevents_table(){
	$tempfile="/usr/share/artica-postfix/ressources/logs/web/HyperCache-webevents.debug";
	$sock=new sockets();
	$tpl=new templates();
	$rp=50;
	
	if(isset($_GET["critical"])){
		if($_GET["critical"]<2){
			$_POST['rp']=2000;
		}
	}
	
	$rp = $_POST['rp'];
	$sock->getFrameWork("squid.php?HyperCache-webevents=yes&rp=$rp&query=".urlencode($_POST["query"]));
	$array=explode("\n",@file_get_contents($tempfile));
	krsort($array);
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();
	
	if(count($array)==0){json_error_show("no data array");}
	
	
	$f=0;
	$c=0;
	while (list ($num, $ligne) = each ($array) ){
		if(trim($ligne)==null){continue;}
		$color="black";
		if(!preg_match('#(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+"GET\s+(.+?)\s+HTTP.*?"\s+([0-9]+)\s+([0-9\-]+)#' ,$ligne,$re)){$f++;continue;}
		
		$c++;
		$ipaddr=$re[1];
		$time=$re[4];
		$query=$re[5];
		$size=FormatBytes($re[7]/1024);
		$code=$re[6];
	
		if($code>399){$color="#D0080A";}
	
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
						"<span style='color:$color'>$ipaddr</span>",
	
						"<span style='color:$color'>$time</span>",
						"<span style='color:$color'>$query</span>",
						"<span style='color:$color'>$size</span>")
		);
	}
	
	if($c==0){json_error_show("no data - $f");}
	$data['total'] = $c;
	echo json_encode($data);
	
	
		
	
	
}

