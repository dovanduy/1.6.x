<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');

if(isset($_GET["sys_alerts-js"])){sys_alerts_js();exit;}
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

function sys_alerts_js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["md5"];
	$title=$tpl->_ENGINE_parse_body("{sys_alerts}");
	echo "YahooWin3('990','$page?sysalert=yes&md5=".urlencode($md5)."','$title')";
}





function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$user=$tpl->_ENGINE_parse_body("{user}");
	$pid=$tpl->_ENGINE_parse_body("{pid}");
	$cpu=$tpl->_ENGINE_parse_body("{cpu}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$product=$tpl->javascript_parse_text("{product}");
	$processes=$tpl->javascript_parse_text("{processes}");
	$running=$tpl->javascript_parse_text("{running}");
	$cpu_time=$tpl->javascript_parse_text("{time}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	$extension="&uuid=".urlencode($_GET["uuid"])."&sysalert=yes&md5={$_GET["md5"]}";
	$title=$tpl->javascript_parse_text("{task_manager}");

	$buttons="
	buttons : [
	{name: 'Crit.', bclass: 'Err', onpress :  Err$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
	
	

	],	";
	
	$buttons=null;
	
	
	$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>

function BuildTable$t(){
	$('#events-table-$t').flexigrid({
		url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension',
		dataType: 'json',
		colModel : [
		{display: 'UID', name : 'user', width :52, sortable : false, align: 'center'},
		{display: '$pid', name : 'pid', width :52, sortable : true, align: 'right'},
		{display: '$cpu', name : 'CPU', width : 52, sortable : true, align: 'center'},
		{display: '$memory', name : 'MEM', width :52, sortable : true, align: 'center'},
		{display: 'VSZ', name : 'VSZ', width :55, sortable : true, align: 'right'},
		{display: 'RSS', name : 'RSS', width :55, sortable : true, align: 'right'},
		{display: '$cpu_time', name : 'pTIME', width :55, sortable : true, align: 'left'},
		{display: 'CMD', name : 'pcmd', width :306, sortable : true, align: 'left'},
		
		],
		$buttons
	
		searchitems : [
		{display: 'CMD', name : 'pcmd'},
		{display: '$user', name : 'user'},
		],
		sortname: 'CPU',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
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
	$table="psaux";
	$page=1;
	$ORDER="ORDER BY CPU desc";
	
	
	if($_GET["sysalert"]=="yes"){
		$q=new mysql_meta();
		
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
		
		// (uuid,user,pid,CPU,MEM,VSZ,RSS,pTIME,pcmd)
		
		
		$user=$ligne["user"];
		$pid=$ligne["pid"];
		$CPU=$ligne["CPU"];
		$MEM=$ligne["MEM"];
		$VSZ=FormatBytes($ligne["VSZ"]);
		$RSS=FormatBytes($ligne["RSS"]);
		$pTIME=$ligne["pTIME"];
		
		
		if(strlen($CPU)==1){$CPU="$CPU.0";}
		if(strlen($MEM)==1){$MEM="$MEM.0";}
		$pcmd=$ligne["pcmd"];
		$pcmd=str_replace("/usr/bin/php5 /usr/share/artica-postfix/", "", $pcmd);
		$pcmd=str_replace("/bin/sh -c /usr/bin/ionice -c2 -n7 /usr/bin/nice --adjustment=19 ", "", $pcmd);
		$pcmd=str_replace("/usr/bin/php -q /usr/share/artica-postfix/", "", $pcmd);
		$pcmd=str_replace("php5 /usr/share/artica-postfix/", "", $pcmd);
		$pcmd=str_replace("/usr/sbin/apache2 -f /etc/artica-postfix/httpd.conf -k start", "Apache (Web interface)", $pcmd);
	
	//	$start=imgsimple("24-run.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=start&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
	//	$stop=imgsimple("24-stop.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=stop&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
	//	$restart=imgsimple("restart-24.png",null,"Loadjs('$MyPage?service-cmd-js=yes&action=restart&cmdline=$service_cmd&uuid=$uuid&app={$ligne["service_name"]}')");
		
		if($installed==1){
			if($running==1){
				$severity_icon="ok22.png";
				$start="-";
			}else{
				$severity_icon="22-red.png";
				$stop="-";
			}
		}
		
				
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						$user,$pid,"{$CPU}%","{$MEM}%",$VSZ,$RSS,$pTIME,$pcmd)
		);
	}


	echo json_encode($data);

}