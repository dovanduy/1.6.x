<?php
/*
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;
$GLOBALS["VERBOSE_SYSLOG"]=true;
*/
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!$GLOBALS["AS_ROOT"]){session_start();unset($_SESSION["MINIADM"]);unset($_COOKIE["MINIADM"]);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}
include_once("ressources/logs.inc");
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.stats-appliance.inc');
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}

if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["listenport-js"])){listenport_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_POST["directory"])){Save();exit;}
if(isset($_POST["delete"])){Delete();exit;}
if(isset($_GET["listenport-popup"])){listenport_popup();exit;}
if(isset($_POST["ArticaWebDAVHTTPPort"])){listenport_save();exit;}
table();


function add_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{new_item}");
	echo "YahooWin('890','$page?add-popup=yes&t=$t','$title')";

}
function listenport_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{listen_port}");
	echo "YahooWin('890','$page?listenport-popup=yes&t=$t','$title')";	
	
}
function listenport_save(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaWebDAVHTTPPort", $_POST["ArticaWebDAVHTTPPort"]);
	
}


function listenport_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ArticaWebDAVHTTPPort=intval($sock->GET_INFO("ArticaWebDAVHTTPPort"));
	if($ArticaWebDAVHTTPPort==0){$ArticaWebDAVHTTPPort=9005;}
	
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend nowrap style='font-size:32px'>{listen_port}:</td>
			<td >" . Field_text("ArticaWebDAVHTTPPort-$t",$ArticaWebDAVHTTPPort,"font-size:32px",false,"SaveCK$t(event)")."</td>
		</tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",42)."</td>
			</tr>
			</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#ARTICA_WEBDAV_TABLE').flexReload();
	YahooWinHide();
}
function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ArticaWebDAVHTTPPort',document.getElementById('ArticaWebDAVHTTPPort-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}



function add_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend nowrap style='font-size:18px'>{directory}:</td>
			<td >" . Field_text("directory-$t",null,"font-size:18px",false,"SaveCK$t(event)")."</td>
			<td width=1%>" . button_browse("directory-$t")."</td>
		</tr>
		<tr>
			<td class=legend nowrap style='font-size:18px'>{write}:</td>
			<td >" . Field_checkbox_design("write-$t",1,0)."</td>
			<td width=1%>&nbsp;</td>
		</tr>					
		<td colspan=3 align='right'><hr>". button("{add}","Save$t()",22)."</td>
		</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#ARTICA_WEBDAV_TABLE').flexReload();
	YahooWinHide();
}
function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('directory',document.getElementById('directory-$t').value);
	if( document.getElementById('write-$t').checked){
		XHR.appendData('write',1);
	}else{
		XHR.appendData('write',0);
	}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	
	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`artica_webdav` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`directory` VARCHAR(128) NOT NULL,
				`write` smallint(1),
				UNIQUE KEY `directory` (`directory`)
		
				) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql,'artica_backup');
	
	
	if(!$q->FIELD_EXISTS("artica_webdav","write","artica_backup")){
		$sql="ALTER TABLE `artica_webdav` ADD `write` smallint( 1 ) NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO artica_webdav (`directory`,`write`) VALUES ('{$_POST["directory"]}','{$_POST["write"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
}

function Delete(){
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM artica_webdav WHERE ID={$_POST["delete"]}","artica_backup");
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{TAB_WEBDAV}");
	$new=$tpl->javascript_parse_text("{new_directory}");
	$directory=$tpl->javascript_parse_text("{directory}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$LIGHTTPD_IP_ACCESS_TEXT=$tpl->javascript_parse_text("{LIGHTTPD_IP_ACCESS_TEXT}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$about=$tpl->javascript_parse_text("{about2}");
	$write=$tpl->javascript_parse_text("{write}");
	$listen_port=$tpl->javascript_parse_text("{listen_port}");
	$t=time();
	$html="

	<table class='ARTICA_WEBDAV_TABLE' style='display: none' id='ARTICA_WEBDAV_TABLE' style='width:99%'></table>
	<script>
	function LoadTable$t(){
	$('#ARTICA_WEBDAV_TABLE').flexigrid({
	url: '$page?rules=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'ID', width :70, sortable : true, align: 'center'},
	{display: '<strong style=font-size:20px>$directory</strong>', name : 'directory', width : 880, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>$write</strong>', name : 'write', width : 90, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>$delete</strong>', name : 'del', width : 163, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '<strong style=font-size:20px>$new</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:20px>$listen_port</strong>', bclass: 'Settings', onpress : ListenPort$t},
	{name: '<strong style=font-size:20px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
	{name: '<strong style=font-size:20px>$about</strong>', bclass: 'Help', onpress : About$t},

	],
	searchitems : [
	{display: '$directory', name : 'pattern'},
	],
	sortname: 'directory',
	sortorder: 'asc',
	usepager: true,
	title: '<div style=\"font-size:30px\">$title</div>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
}

function About$t(){
alert('$LIGHTTPD_IP_ACCESS_TEXT');
}

var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#ARTICA_WEBDAV_TABLE').flexReload();
}



function Delete$t(ID){
	if(!confirm('$delete $directory:'+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
	Loadjs('artica.webinterface.restart.php?nologon=yes');
}

function NewRule$t() {
	Loadjs('$page?add-js=yes&t=$t',true);
}

function ListenPort$t(){
	Loadjs('$page?listenport-js=yes&t=$t',true);
}

LoadTable$t();
</script>
";
	echo $html;
}
function rules(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$FORCE_FILTER=null;
	$search='%';
	$table="artica_webdav";
	$page=1;
	$color="black";
	$sock=new sockets();
	$ArticaWebDAVHTTPPort=intval($sock->GET_INFO("ArticaWebDAVHTTPPort"));
	if($ArticaWebDAVHTTPPort==0){$ArticaWebDAVHTTPPort=9005;}
	
	
	if($q->COUNT_ROWS("artica_webdav","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$icon="check-48-grey.png";
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}



	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$delete=imgsimple("delete-48.png",null,"Delete{$_GET["t"]}({$ligne["ID"]})");
		
		if($ligne["write"]==1){
			$icon="check-48.png";
		}
		
		$link="http://{$_SERVER["SERVER_ADDR"]}:{$ArticaWebDAVHTTPPort}/".basename($ligne["directory"]);
		$link2="<a href=\"$link\" style='text-decoration:underline;font-size:18px' target=_new>$link</a>";
		
		$writeimg=imgsimple($icon);
		$data['rows'][] = array(
				'id' => "{$ligne["ID"]}",
				'cell' => array(
						"<span style='font-size:28px;font-weight:bold;color:$color;'>{$ligne["ID"]}</span>",
						"<span style='font-size:28px;font-weight:bold;color:$color;'>{$ligne["directory"]}<br>$link2</span>",
						"<center>$writeimg</center>",
						"<center>$delete</center>")
		);
	}

	echo json_encode($data);

}