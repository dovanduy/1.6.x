<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["liste-rules"])){list_rules();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$html="YahooWin3('890','$page?table=yes&ID=$ID','{$ligne["rulename"]}')";
	echo $html;	
	
	
}
function table(){
	$error=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("artica_caches")){create_table();}

	$hits=$tpl->javascript_parse_text("{hits}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$lang=$tpl->javascript_parse_text("{language}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$title=$tpl->javascript_parse_text("{subject}");
	$new_template=$tpl->javascript_parse_text("{new_template}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$online_help=$tpl->javascript_parse_text("{online_help}");
	$date=$tpl->javascript_parse_text("{zDate}");
	$replace=$tpl->_ENGINE_parse_body("{replace}");
	
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$size=$tpl->javascript_parse_text("{size}");
	$sitename_explain=$tpl->javascript_parse_text("{artica_cache_rule_explain_sitename}");
	$t=time();
	$backToDefault=$tpl->javascript_parse_text("{backToDefault}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename,foldersize FROM artica_caches WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$enforce_rules=$tpl->javascript_parse_text("{enforce_rules}:{$ligne["rulename"]} (".FormatBytes($ligne["foldersize"]/1024).")");


	//if(!$users->CORP_LICENSE){
	//$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{MOD_TEMPLATE_ERROR_LICENSE}")."</p>";
	//}

	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},

	],";
	$buttons=null;
	$html="
	$error
	<table class='squid_enforce_foldersize' style='display: none' id='squid_enforce_foldersize' style='width:99%'></table>

	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#squid_enforce_foldersize').flexigrid({
	url: '$page?liste-rules=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$sitename', name : 'sitename', width : 629, sortable : false, align: 'left'},
	{display: '$size', name : 'foldersize', width : 184, sortable : true, align: 'right'},

	],
	$buttons
	searchitems : [
	{display: '$sitename', name : 'sitename'},
	],
	sortname: 'sizebytes',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$enforce_rules</span>',
	useRp: true,
	rp: 250,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});



var xNewRule$t= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}
$('#squid_enforce_foldersize').flexReload();
}

function NewRule$t(){
var rule=prompt('$rulename ?');
if(!rule){return;}
var sitename=prompt('$sitename_explain');
if(!sitename){return;}
var XHR = new XHRConnection();
XHR.appendData('new-rule',encodeURIComponent(rule));
XHR.appendData('sitename',encodeURIComponent(sitename));
XHR.sendAndLoad('$page', 'POST',xNewRule$t);
}

function Apply$t(){
Loadjs('squid.artica-rules.progress.php');
}

</script>
";

echo $html;
}
function list_rules(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$search='%';
	$table="(SELECT * FROM artica_caches_sizes WHERE ruleid={$_GET["ID"]}) as t";
	$q=new mysql_squid_builder();
	$page=1;
	$_POST["query"]=trim($_POST["query"]);

	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$sizebytes=FormatBytes($ligne["sizebytes"]/1024);
		$sitename=$ligne["sitename"];
		
		

		$data['rows'][] = array(
				'id' => md5($sitename),
				'cell' => array(
						"<span style='font-size:16px'>$sitename</span>"
						,"<span style='font-size:16px'>$sizebytes</span>",

				)
		);
	}

	if(count($data['rows'])==0){json_error_show("no data");}
	echo json_encode($data);

}