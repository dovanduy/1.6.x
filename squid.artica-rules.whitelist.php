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
if(isset($_POST["sitename"])){new_rule();exit;}
if(isset($_POST["MaxSizeBytes"])){rule_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}

if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tab"])){rule_tabs();exit;}
if(isset($_GET["rule-parameters"])){rule_parameters();exit;}
if(isset($_GET["rule-filestypes"])){rule_files_types();exit;}
if(isset($_POST["MIME-ID"])){rule_files_types_save();exit;}

table();

function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$html="YahooWin3('890','$page?rule-tab=yes&ID=$ID','{$ligne["rulename"]}')";
	echo $html;	
	
	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["delete-js"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM artica_caches_wl WHERE ID='$ID'","artica_backup"));
	$pattern=$ligne["sitename"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	echo "var xNewRule$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_wltable').flexReload();
}

function NewRule$t(){
	if(!confirm('$delete $pattern ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);	
}

	NewRule$t();";
			
	
}

function delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM artica_caches_wl WHERE ID='{$_POST["delete"]}'");
	
}


function rule_parameters(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$t=time();
	
	if($ligne["MaxSizeBytes"]==0){$ligne["MaxSizeBytes"]=3145728000;}
	
	$ligne["MaxSizeBytes"]=$ligne["MaxSizeBytes"]/1024;
	$ligne["MaxSizeBytes"]=$ligne["MaxSizeBytes"]/1024;
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>". 
	Field_checkbox_table("enabled-$t", "{enabled}",$ligne["enabled"]).
	Field_text_table("rulename-$t", "{rulename}",$ligne["rulename"],18,null,250).
	Field_text_table("sitename-$t", "{sitename}",$ligne["sitename"],18,null,250).
	Field_text_table("MaxSizeBytes-$t", "{max_size} MB",$ligne["MaxSizeBytes"],18,null,250).
	Field_button_table_autonome("{apply}", "Save$t",26)."
	</table>
</div>		
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var EnableSquidCacheBoosters=0;
	if(document.getElementById('enabled-$t').checked){
	XHR.appendData('enabled',1);
	}else{XHR.appendData('enabled',0);}
	
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('MaxSizeBytes',document.getElementById('MaxSizeBytes-$t').value)
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('sitename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);						
}
</script>		
";

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function rule_save(){
	$q=new mysql_builder();
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));
	if(!$q->FIELD_EXISTS("artica_caches","MaxSizeBytes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MaxSizeBytes` BIGINT UNSIGNED NOT NULL DEFAULT '3145728000'";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("artica_caches","FileTypes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `FileTypes` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$_POST["sitename"]=mysql_escape_string2(url_decode_special_tool($_POST["sitename"]));
	
	$q->QUERY_SQL("UPDATE artica_caches
			SET MaxSizeBytes='{$_POST["MaxSizeBytes"]}',
			`rulename`='{$_POST["rulename"]}',
			`sitename`='{$_POST["sitename"]}',
			`enabled`='{$_POST["enabled"]}'
			WHERE ID={$_POST["ID"]}
		
			
			");
	
	
	if(!$q->ok){echo $q->mysql_error;}
}




function table(){
	$error=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("artica_caches_wl")){create_table();}
	
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
	$enforce_rules=$tpl->javascript_parse_text("{enforce_rules}: {whitelist}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$sitename_explain=$tpl->javascript_parse_text("{artica_cache_rule_explain_sitename}");
	$t=time();
	$backToDefault=$tpl->javascript_parse_text("{backToDefault}");
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
	<table class='squid_enforce_rules_wltable' style='display: none' id='squid_enforce_rules_wltable' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
	$('#squid_enforce_rules_wltable').flexigrid({
	url: '$page?liste-rules=yes',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'enabled', width :60, sortable : true, align: 'center'},
		{display: '$sitename', name : 'sitename', width : 956, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width :60, sortable : true, align: 'center'},
	
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
	$('#squid_enforce_rules_wltable').flexReload();
}

function NewRule$t(){
	var sitename=prompt('$sitename_explain');
	if(!sitename){return;}
	var XHR = new XHRConnection();
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

function create_table(){
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_caches_wl` (
		`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		`sitename` VARCHAR( 256 ) NOT NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		 UNIQUE KEY `sitename` (`sitename`),
		 KEY `enabled` (`enabled`)
		)  ENGINE = MYISAM;
			";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	


	
	
}

function new_rule(){
	
	$sitename=mysql_escape_string2(url_decode_special_tool($_POST["sitename"]));
	$sql="INSERT IGNORE INTO `artica_caches_wl` (`sitename`,`enabled`) VALUES ('$sitename','1')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}


function list_rules(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$search='%';
	$table="artica_caches_wl";
	$q=new mysql_squid_builder();
	$page=1;
	$_POST["query"]=trim($_POST["query"]);
	
	if(!$q->TABLE_EXISTS("artica_caches_wl")){create_table();}
	
	if($q->COUNT_ROWS($table)==0){
		$q->QUERY_SQL("INSERT IGNORE INTO artica_caches_wl (ID,sitename,enabled)
		VALUES ('1','regex:\\\\/(din|dout)\\\\.aspx\\\\?s=[0-9]+\\\\&client=DynGate',1)");
		if(!$q->ok){json_error_show($q->mysql_error); return ;}
		
		$q->QUERY_SQL("INSERT IGNORE INTO artica_caches_wl (ID,sitename,enabled)
		VALUES ('2','regex:\\\\/traffic_record\\.php\\\\?',1)");
		
		$q->QUERY_SQL("INSERT IGNORE INTO artica_caches_wl (ID,sitename,enabled)
		VALUES ('3','.googleusercontent.com',1)");
		
		
		$q->QUERY_SQL("INSERT IGNORE INTO artica_caches_wl (ID,sitename,enabled)
		VALUES ('4','regex:dropbox\\\\.com\\\\/subscribe\\\\',1)");

		
	}

	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data"); return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show("no data");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$jsfiche=null;
		$herf=null;
		$ID=$ligne["ID"];
		$img="ok32.png";
		$text_mime=null;
		
		//$Modify="<a href=\"javascript:blur();\"
		//OnClick=\"javascript:Loadjs('$MyPage?rule-js=yes&ID=$ID');\"
		//style='font-size:16px;text-decoration:underline;font-weight:bold'>";

		
		if($ligne["enabled"]==0){$img="ok32-grey.png";}
		
		
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}')");
		
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<img src='img/$img'>"
						,"<span style='font-size:18px'>$Modify{$ligne["sitename"]}</a>$text_mime</span>",
						"<span style='font-size:16px'>$delete</a></span>",
				)
		);
	}

if(count($data['rows'])==0){json_error_show("no data");}
echo json_encode($data);

}

