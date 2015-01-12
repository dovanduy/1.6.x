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
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["liste-rules"])){list_rules();exit;}
if(isset($_POST["sitename"])){Save();exit;}
if(isset($_POST["delete"])){delete();exit;}

table();
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
	$enforce_rules=$tpl->javascript_parse_text("{others_domains}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$sitename_explain=$tpl->javascript_parse_text("{artica_cache_rule_explain_sitename}");
	$t=time();
	$backToDefault=$tpl->javascript_parse_text("{backToDefault}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();


	//if(!$users->CORP_LICENSE){
	//$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{MOD_TEMPLATE_ERROR_LICENSE}")."</p>";
	//}

	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},

	],";

	$html="
	$error
	<table class='squid_others_domains_rules_{$_GET["ID"]}' style='display: none' 
	id='squid_others_domains_rules_{$_GET["ID"]}' style='width:99%'></table>

	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#squid_others_domains_rules_{$_GET["ID"]}').flexigrid({
	url: '$page?liste-rules=yes&ID={$_GET["ID"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$sitename', name : 'sitename', width : 600, sortable : false, align: 'left'},
	{display: '$delete', name : 'del', width :80, sortable : true, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$sitename', name : 'sitename'},
	],
	sortname: 'sitename',
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
	$('#squid_others_domains_rules_{$_GET["ID"]}').flexReload();
	$('#squid_enforce_rules_table').flexReload();
}

function NewRule$t(){
	var sitename=prompt('$sitename_explain');
	if(!sitename){return;}
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('sitename',encodeURIComponent(sitename));
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);
}
function DelRule$t(domain){
	var sitename=confirm('$delete '+domain+' ?');
	if(!sitename){return;}
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('delete',encodeURIComponent(domain));
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);
}
function Apply$t(){
	Loadjs('squid.artica-rules.progress.php');
}

</script>
";

	echo $html;
}

function Save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$_POST["sitename"]=url_decode_special_tool($_POST["sitename"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `OtherDomains` FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$array=unserialize($ligne["OtherDomains"]);
	$array[$_POST["sitename"]]=true;
	
	$new=mysql_escape_string2(serialize($array));
	
	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("artica_caches","StampDelete","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `StampDelete` smallint(1) NOT NULL DEFAULT 0, ADD INDEX (`StampDelete`)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	$q->QUERY_SQL("UPDATE artica_caches SET `OtherDomains`='$new' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function delete(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$_POST["sitename"]=url_decode_special_tool($_POST["sitename"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `OtherDomains` FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$array=unserialize($ligne["OtherDomains"]);
	unset($array[$_POST["delete"]]);
	
	$new=mysql_escape_string2(serialize($array));
	
	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	$q->QUERY_SQL("UPDATE artica_caches SET `OtherDomains`='$new' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}	
	
}

function list_rules(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$search='%';
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	$page=1;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `OtherDomains` FROM artica_caches WHERE ID='$ID'","artica_backup"));
	
	$array=unserialize($ligne["OtherDomains"]);
	
	if(count($array)==0){json_error_show("no data");}
	
	
	
	$searchstring=string_to_flexregex();

	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();

	$c=0;
	while (list ($domain, $pattern) = each ($array)){
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $domain)){continue;}
		}
		$c++;
		$jsfiche=null;
		$herf=null;
		$img="ok32.png";
		$DEL=imgsimple("delete-32.png",null,"DelRule$t('$domain')");
		
		$data['rows'][] = array(
				'id' => md5($domain),
				'cell' => array(
					"<span style='font-size:18px'>$domain</span>",
					"<span style='font-size:16px'>$DEL</a></span>",
				)
		);
	}
	$data['total']=$c;
if($c==0){json_error_show("no data");}
echo json_encode($data);

}

