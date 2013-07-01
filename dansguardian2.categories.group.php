<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["categories-table"])){category_table();exit;}
if(isset($_GET["groups-table-list"])){category_table_list();exit;}
if(isset($_POST["new-category-group"])){category_group_save();exit;}
if(isset($_POST["delete-category-group"])){category_group_delete();exit;}
if(isset($_POST["enable-category-group"])){category_group_enable();exit;}
if(isset($_POST["enable-category-rule"])){category_rule_save();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{categories_groups}");
	$YahooWin="YahooWin4";
	echo "$YahooWin('700','$page?categories-table=yes&tSource={$_GET["tSource"]}&t={$_GET["t"]}','$title');";

}

function category_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$webfilter=new webfilter_rules();
	$t=$_GET["t"];
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groups=$tpl->_ENGINE_parse_body("{groups2}");
	$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$groups=$tpl->_ENGINE_parse_body("{groups}");
	$categories=$tpl->_ENGINE_parse_body("{categories}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$action_delete_group=$tpl->javascript_parse_text("{action_delete_group}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$service_events=$tpl->_ENGINE_parse_body("{service_events}");
	$global_parameters=$tpl->_ENGINE_parse_body("{global_parameters}");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$config_file=$tpl->_ENGINE_parse_body("{config_file}");
	$categories_group=$tpl->_ENGINE_parse_body("{categories_groups}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$tSource=$_GET["tSource"];
	$tt=time();
	$error_ldap=null;
	
	$MainRuleID=$_GET["RULEID"];
	$title=null;
	if(is_numeric($MainRuleID)>0){
	
		$name="{default}";
		$white=$tpl->_ENGINE_parse_body("{blacklist}");
		if($_GET["modeblk"]==1){$white=$tpl->_ENGINE_parse_body("{whitelist}");}
		if($MainRuleID>0){
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_rules WHERE ID=$MainRuleID"));
			$title=$tpl->javascript_parse_text("{rule}")." ".utf8_encode($ligne["groupname"])." | ".$tpl->javascript_parse_text("$white");
		}	
	
	}
	
	$table_size=670;
	$group_size=445;
	
	
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["group-size"])){$group_size=$_GET["group-size"];}
	
	$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddCategoryGroup$tt},
	{name: '$compile_rules', bclass: 'Reconf', onpress : CompileUfdbGuardRules},

	],";

//{display: '&nbsp;', name : 'dup', width :31, sortable : false, align: 'center'},

$html="
<div>
$error_ldap
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
</div>
<script>
var rowid$tt='';
$(document).ready(function(){
	$('#flexRT$tt').flexigrid({
		url: '$page?groups-table-list=yes&t=$tt&tSource={$_GET["tSource"]}&RULEID={$_GET["RULEID"]}&modeblk={$_GET["modeblk"]}',
		dataType: 'json',
		colModel : [
		{display: '$groups', name : 'groupname', width : $group_size, sortable : true, align: 'left'},
		{display: '$categories', name : 'none', width :87, sortable : false, align: 'center'},
		{display: '$enabled', name : 'enabled', width : 31, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$groups', name : 'groupname'},
		],
		sortname: 'groupname',
		sortorder: 'asc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 100,
		showTableToggleBtn: false,
		width: $table_size,
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500,1000]
	});
});

var xAddCategoryGroup$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT$tt').flexReload();
	$('#flexRT$tSource').flexReload();
	ExecuteByClassName('SearchFunction');
	
	
}

function AddCategoryGroup$tt(){
	var groupname=prompt('$new_group','New category group');
	if(!groupname){return;}
	var XHR = new XHRConnection();
	XHR.appendData('new-category-group', groupname);
	XHR.sendAndLoad('$page', 'POST',xAddCategoryGroup$tt);	
		
	
}

function CategoryGroupEnable$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('enable-category-group', ID);
	XHR.sendAndLoad('$page', 'POST',xAddCategoryGroup$tt);
}
function CategoryRuleGroupEnable$tt(ID,ruleid){
	var XHR = new XHRConnection();
	XHR.appendData('enable-category-rule', ID);
	XHR.appendData('ruleid', ruleid);
	XHR.appendData('modeblk', '{$_GET["modeblk"]}');
	XHR.sendAndLoad('$page', 'POST',xAddCategoryGroup$tt);
}



var xCategoryGroupDelete$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#row'+rowid$tt).remove();
	$('#flexRT$tt').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT$tSource').flexReload();	
	ExecuteByClassName('SearchFunction');
}

function CategoryGroupDelete$tt(ID,md){
	rowid$tt=md;
	if(confirm('$action_delete_group')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-category-group', ID);
		XHR.sendAndLoad('$page', 'POST',xCategoryGroupDelete$tt);
	}
}
</script>

		";

		echo $html;

}
function category_rule_save(){

	$q=new mysql_squid_builder();
	$webfilter_blkid=$_POST["enable-category-rule"];
	$MainRuleID=$_POST["ruleid"];
	$modeblk=$_POST["modeblk"];
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `zmd5` FROM webfilter_blklnk WHERE `webfilter_blkid`='$webfilter_blkid'
			AND webfilter_ruleid='$MainRuleID' AND `blacklist`='$modeblk'"));
	if(strlen(trim($ligne["zmd5"]))==0){
		$md5=md5("$webfilter_blkid$MainRuleID$modeblk");
		$sql="INSERT IGNORE INTO webfilter_blklnk (`zmd5`,`webfilter_blkid`,`webfilter_ruleid`,`blacklist`)
		VALUES ('$md5','$webfilter_blkid','$MainRuleID','$modeblk');		
		";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		
		$q->QUERY_SQL("DELETE FROM webfilter_blklnk WHERE `zmd5`='{$ligne["zmd5"]}'");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
}


function category_table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$rp=$_POST["rp"];
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_blkgp";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$tSource=$_GET["tSource"];
	$MainRuleID=$_GET["RULEID"];
	$modeblk=$_GET["modeblk"];
	
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
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
		$total = $ligne["TCOUNT"]+1;
	}
	
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	
		
	while ($ligne = mysql_fetch_assoc($results)) {	
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$color="black";
		$groupname=utf8_encode($ligne["groupname"]);
		$enable=Field_checkbox("enable", 1,$ligne["enabled"],"CategoryGroupEnable$t($ID)");
		$delete=imgsimple("delete-24.png",null,"CategoryGroupDelete$t($ID,'$md')");
		if($ligne["enabled"]==0){$color="#CCCCCC";}
		$TextToAdd=null;
		
		$js="Loadjs('dansguardian2.categories.group.single.php?js=yes&ID=$ID&t=$t&tSource=$tSource')";
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(`category`) AS CountDeCats FROM webfilter_blkcnt WHERE `webfilter_blkid`='$ID'"));
		$CountDeCats=$ligne2["CountDeCats"];
		if(!$q->ok){$CountDeCats=$q->mysql_error;}
		
		if(is_numeric($MainRuleID)){
			$color="black";
			$enabled=1;
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `zmd5` FROM webfilter_blklnk WHERE `webfilter_blkid`='$ID' 
			AND webfilter_ruleid='$MainRuleID' AND `blacklist`='$modeblk'"));
			if(strlen(trim($ligne2["zmd5"]))==0){
				$color="#CCCCCC";
				$enabled=0;
			}
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilter_blkgp WHERE ID=$ID"));
			$jsenable="<a href=\"javascript:blur();\" OnClick=\"javascript:CategoryGroupEnable$t($ID)\" style='font-size:16px;font-weight:bold;text-decoration:underline' >";
			if($ligne2["enabled"]==0){
				$TextToAdd=$tpl->_ENGINE_parse_body("&nbsp;$jsenable({disabled})</a></strong>");
			}else{
				$TextToAdd=$tpl->_ENGINE_parse_body("&nbsp;$jsenable({enabled})</a></strong>");
			}
			
			$enable=Field_checkbox("enable", 1,$enabled,"CategoryRuleGroupEnable$t($ID,$MainRuleID,$modeblk)");
			
		}
		
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
					"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;color:$color;text-decoration:underline'>$groupname</a>$TextToAdd</span>",
					"<span style='font-size:16px;color:$color;'>&laquo;&nbsp;$CountDeCats</a>&nbsp;&raquo;</span>",
					"$enable",
					$delete )
		);		
		
	}
	
	echo json_encode($data);
	
}

function category_group_save(){
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->QUERY_SQL("INSERT IGNORE INTO `webfilter_blkgp` (groupname,enabled) VALUES ('{$_POST["new-category-group"]}','1')");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function category_group_delete(){
	$ID=$_POST["delete-category-group"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM `webfilter_blkcnt` WHERE webfilter_blkid=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM `webfilter_blklnk` WHERE webfilter_blkid=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM `webfilter_blkgp` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}
function category_group_enable(){
	$ID=$_POST["enable-category-group"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `enabled` FROM webfilter_blkgp WHERE `ID`='$ID'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$value=1;
	if($ligne["enabled"]==1){$value=0;}
	
	$q->QUERY_SQL("UPDATE `webfilter_blkgp` SET `enabled`='$value' WHERE `ID`='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	
}