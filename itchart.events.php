<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	table();
	
	
function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_GET["delete-js"];
	$Remove=$tpl->javascript_parse_text("{remove}");
	
$page=CurrentPageName();
$t=time();
$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#IT_CHART_EVENTS_TABLE').flexReload();
	
}
	
function xFunct$t(){
	if(!confirm('$Remove: $ID?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
xFunct$t();
";
	echo $html;
}
	
function delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM itchartlog WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
}
		
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$new_itchart=$tpl->javascript_parse_text("{new_itchart}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$it_charters=$tpl->javascript_parse_text("{it_charters}");
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$apply=$tpl->javascript_parse_text("{apply}");
	$title=$tpl->javascript_parse_text("{it_charters}: {events}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$buttons="	buttons : [
		
		{name: '<strong style=font-size:22px>$apply</strong>', bclass: 'reload', onpress : Apply$t},
		],";
	
	
	//`uid`,`ipaddr`,`MAC`,`zDate`
	
$html="
<table class='IT_CHART_EVENTS_TABLE' style='display: none' id='IT_CHART_EVENTS_TABLE'></table>
<script>
$('#IT_CHART_EVENTS_TABLE').flexigrid({
		url: '$page?search=yes',
		dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$zDate</span>', name : 'zDate', width : 233, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$it_charters</span>', name : 'title', width : 478, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$uid</span>', name : 'uid', width : 120, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$ipaddr</span>', name : 'ipaddr', width : 176, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$MAC</span>', name : 'MAC', width : 196, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
		{display: '$uid', name : 'uid'},
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$MAC', name : 'MAC'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:30px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	
function New$t(){
	Loadjs('itchart.item.php?ID=0');
}

function Apply$t(){
	Loadjs('itchart.progress.php');
}

</script>";
	echo $html;
}

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="itcharters";

	if(!$q->TABLE_EXISTS("itchartlog")){json_error_show("no data (no table)",1);}


	

	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$sql="SELECT COUNT( * ) AS tcount FROM itchartlog WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM itchartlog WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";
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
	$ID=$ligne["ID"];
	$ColorTime="black";
	$title=$ligne["title"];
	$title=utf8_encode($title);
	$enablepdf=intval($ligne["enablepdf"]);
	$enabled=intval($ligne["enabled"]);
	$iconpdf=null;
	$icon_enabed="ok-64.png";
	
	$chartid=$ligne["chartid"];
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT title FROM itcharters WHERE ID='$chartid'"));
	$title=utf8_encode($ligne2["title"]);
	
	
		$urijs="Loadjs('itchart.item.php?ID=$chartid');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";

		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?delete-js=$ID')");
		$cell=array();
		$cell[]="<span $style>{$ligne["zDate"]}</a></span>";
		$cell[]="<span $style>$link$title</a></span>";
		$cell[]="<span $style>{$ligne["uid"]}</a></span>";
		$cell[]="<span $style>{$ligne["ipaddr"]}</a></span>";
		$cell[]="<span $style>{$ligne["MAC"]}</a></span>";
		$cell[]="<center>$delete</center>";

			$data['rows'][] = array(
					'id' => $ligne['zmd5'],
					'cell' => $cell
			);
	}


	echo json_encode($data);
}
	
