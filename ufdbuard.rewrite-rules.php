<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}




if(isset($_GET["rewrite_rules_list"])){rewrite_rules_list();exit;}
if(isset($_POST["rewrite_rule_enable"])){rewrite_rule_enable();exit;}


rewrite_rules();

function rewrite_rules_list(){


	$ID=$_GET["ID"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();

	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}else{
		$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	$RewriteRules=unserialize(base64_decode($ligne["RewriteRules"]));

	$search='%';
	$table="webfilters_rewriterules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if($q->COUNT_ROWS($table)==0){json_error_show("no data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){json_error_show("no data");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}

	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"].$ID);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$enabled=0;

		if(isset($RewriteRules[$ligne["ID"]])){
			if($RewriteRules[$ligne["ID"]]){$enabled=1;}
		}

		$enable=Field_checkbox($md5,1,$enabled,"MainRuleRewriteEnable('{$ligne["ID"]}','$md5')");
		$js="Loadjs('ufdbguard.rewrite.php?rewrite-rule=yes&ID={$ligne["ID"]}');";


		writelogs("{$ligne["ID"]} => {$ligne["rulename"]}",__FUNCTION__,__FILE__,__LINE__);
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:22px;text-decoration:underline'>{$ligne["rulename"]}</span>",
						"<span style='font-size:22px'>{$ligne["ItemsCount"]}</span>",$enable )
		);
	}


	echo json_encode($data);

}

function rewrite_rule_enable(){

	$ruleid=$_POST["rewrite_rule_enable"];
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}else{
		$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	$RewriteRules=unserialize(base64_decode($ligne["RewriteRules"]));
	
	
	if(isset($RewriteRules[$ruleid])){
		unset($RewriteRules[$ruleid]);
	}else{
		$RewriteRules[$ruleid]=true;
	}
	
	$ligne["RewriteRules"]=base64_encode(serialize($RewriteRules));

	if($ID==0){
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");
		$sock->getFrameWork("squid.php?rebuild-filters=yes");
		return;
	}

	$sql="UPDATE webfilter_rules SET RewriteRules='{$ligne["RewriteRules"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");

}

function rewrite_rules(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$items=$tpl->javascript_parse_text("{items}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_affect_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_affect_explain}");


	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>


	<script>
	function start$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?rewrite_rules_list=yes&ID={$ID}',
	dataType: 'json',
	colModel : [
	{display: '$rulename', name : 'rulename', width : 587, sortable : false, align: 'left'},
	{display: '$items', name : 'ItemsNumber', width :128, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'enabled', width : 101, sortable : true, align: 'center'},
	],

	searchitems : [
	{display: '$rulename', name : 'rulename'},
	],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:14px>$rewrite_rules_affect_explain</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var x_MainRuleRewriteEnable= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);}
FlexReloadRulesRewrite();
}


function MainRuleRewriteEnable(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rewrite_rule_enable', ID);
	XHR.appendData('ID', $ID);
	XHR.sendAndLoad('$page', 'POST',x_MainRuleRewriteEnable);

}


function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload(); ExecuteByClassName('SearchFunction');
}

start$t();

</script>

";
	echo $html;

}