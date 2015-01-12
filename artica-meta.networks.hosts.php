<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
}

if(isset($_GET["SearchComputers"])){SearchComputers();exit;}
if(isset($_GET["compt-status"])){comp_ping();exit;}
if(isset($_GET["PingRestart"])){PingRestart_js();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["mac-js"])){js_mac();exit;}
if(isset($_GET["mac-popup"])){mac_popup();exit;}
if(isset($_POST["MAC"])){mac_edit();exit;}
page();

function GetRights(){
	$users=new usersMenus();
	if($users->AsArticaMetaAdmin){return true;}
	return false;
}
function js_in_front(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	echo "
	document.getElementById('BodyContent').innerHTML='<div id=$t class=form></div>';
	LoadAjax('$t','$page?js-in-front-popup=yes&CorrectMac={$_GET["CorrectMac"]}&fullvalues={$_GET["fullvalues"]}');";
}

function js_in_front_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
$html="<div class=text-info>{OCS_SEARCH_EXPLAIN}</div>
<span id='ocs-search-div'></span>
<script>
	LoadAjax('ocs-search-div','$page?def={$_GET["search"]}');
</script>
";
echo $tpl->_ENGINE_parse_body($html);

	
	
}

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();	
	$tpl=new templates();
	$uuid=$_GET["uuid"];
	$q=new mysql_meta();
	$hostname=$q->uuid_to_host($uuid);
	$uuid=urlencode($uuid);
	$title=$tpl->javascript_parse_text("{computers}::{$hostname}");
	$html="RTMMail(990,'$page?uuid=$uuid&t={$_GET["t"]}','$title');";
	echo $html;

}

function js_mac(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$uuid=urlencode($_GET["uuid"]);
	$mac=$_GET["mac-js"];
	$mac_enc=urlencode($mac);
	$q=new mysql_meta();
	$tpl=new templates();
	$hostname=$q->mac_to_host($mac);
	$title=$tpl->javascript_parse_text("{computer}::{$hostname} [$mac]");
	$html="YahooUser(850,'$page?mac-popup=$mac_enc&uuid=$uuid&t={$_GET["t"]}','$title');";
	echo $html;
}

function mac_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_meta();
	
	$btname="{apply}";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM networks_hosts WHERE MAC='{$_GET["mac-popup"]}'"));
	$t=time();
	$html="
	<div style='font-size:22px;margin-bottom:15px'>{$ligne["hostname"]}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{computer}:</td>
		<td>". Field_text("hostname-$t",$ligne["hostname"],"font-size:18px;width:98%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}:</td>
		<td>". field_ipv4("IPADDR-$t",$ligne["IPADDR"],"font-size:18px;width:98%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{OSNAME}:</td>
		<td>". Field_text("OSNAME-$t",$ligne["OSNAME"],"font-size:18px;width:98%")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{username}:</td>
		<td>". Field_text("username-$t",$ligne["username"],"font-size:18px;width:98%")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>".button($btname, "Save$t()",26)."</td>	
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	$('#flexRT{$_GET["t"]}').flexReload();
}			
			
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.appendData('IPADDR',document.getElementById('IPADDR-$t').value);
	XHR.appendData('OSNAME',document.getElementById('OSNAME-$t').value);
	XHR.appendData('username',document.getElementById('username-$t').value);
	XHR.appendData('MAC','{$_GET["mac-popup"]}');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>		
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mac_edit(){
	

	
	$q=new mysql_meta();
	$sql=$q->SQL_EDIT_FROM_POST("MAC","networks_hosts");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$q->CreateOrder($_POST["uuid"], "UPDATE_HOST",$_POST);
		

	
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql_meta();
	$TB_HEIGHT=400;
	$TB_WIDTH=635;	
	
	if($_GET["uuid"]<>null){
		$uuid_enc=urlencode($_GET["uuid"]);
		$uuid_name="&nbsp;".$q->uuid_to_host($_GET["uuid"]);
	}
	$new_entry=$tpl->_ENGINE_parse_body("{new_computer}");
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$lastest_scan=$tpl->_ENGINE_parse_body("{latest_scan}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$directories=$tpl->_ENGINE_parse_body("{directories}");
	$depth=$tpl->_ENGINE_parse_body("depth");
	$OSNAME=$tpl->_ENGINE_parse_body("{OSNAME}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$import=$tpl->javascript_parse_text("{import_artica_computers}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$username=$tpl->javascript_parse_text("{members}");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : AddComputer$t},
	{name: '$all', bclass: 'search', onpress : all_scan$t},
	
	
	
	],	";
	
	$uri="$page?SearchComputers=yes&uuid=$uuid_enc&t=$t";
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$uri',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width :264, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'IPINT', width :141, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width :141, sortable : true, align: 'left'},
		{display: '$OSNAME', name : 'OSNAME', width :141, sortable : true, align: 'left'},
		{display: '$username', name : 'username', width :131, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :51, sortable : false, align: 'center'},
	 	

	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'IPADDR'},
		{display: '$username', name : 'username'},
		{display: '$OSNAME', name : 'OSNAME'},
		{display: 'MAC', name : 'MAC'},
	],
	sortname: 'IPINT',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$computers$uuid_name</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function AddComputer$t(){
	YahooUser(1051,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');
}

function lastest_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri&latest-scan=yes'}).flexReload(); 
}

function ImportComputer$t(){
	YahooWin3('450','$page?artica-importlist-popup=yes','$import');
}

function all_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri'}).flexReload(); 
}
</script>
";
	
	echo $html;
	
}


function SearchComputers(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_meta();	
	
	$fontsize="14px";
	$cs=0;
	$page=1;
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$_POST["query"]=trim($_POST["query"]);
	
	
	
	
	$FORCE=1;
	if($_GET["uuid"]<>null){
		$FORCE="uuid='{$_GET["uuid"]}'";
	}
	
	$search='%';
	$table="networks_hosts";
	$page=1;
	
	
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}		
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE  $searchstring";
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
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$uuid=urlencode($_GET["uuid"]);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		if($ligne["OSNAME"]=="Unknown"){$ligne["OSNAME"]=null;}
		$cs++;
		$macenc=urlencode($ligne["MAC"]);
		$jslink="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?mac-js=$macenc&uuid=$uuid&t={$_GET["t"]}');\"
		style='font-size:$fontsize;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array(
		"<span style='font-size:$fontsize'>$jslink{$ligne["hostname"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["IPADDR"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["MAC"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["OSNAME"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["username"]}</a></span>",
		"" )
		);		

	}

echo json_encode($data);
	
	
}

function comp_ping(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");		
	$time=md5(microtime());
	$R=$sock->getFrameWork("network.php?ping={$_GET["compt-status"]}");
	writelogs("network.php?ping={$_GET["compt-status"]} -> '$R'",__FUNCTION__,__FILE__,__LINE__);
	if($R=="TRUE"){echo "<img src='img/ok24.png' id='$time'>";return;}
	
	$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart=$time')");
	if(isset($_GET["restart"])){
		$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart={$_GET["restart"]}');");
		echo $tpl->_ENGINE_parse_body("<input type='hidden' id='ip-{$_GET["restart"]}' value='{$_GET["compt-status"]}'>$img</div>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<div id='div-$time'><input type='hidden' id='ip-$time' value='{$_GET["compt-status"]}'>$img</div>");
}


