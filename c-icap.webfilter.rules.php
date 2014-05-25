<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
	// CicapEnabled
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){die('not allowed');}
	if(isset($_GET["rules-list"])){rules_list();exit;}
	
rules_table();
function rules_table(){
$q=new mysql_squid_builder();
$page=CurrentPageName();
$tpl=new templates();
$new_rule=$tpl->javascript_parse_text("{new_rule}");
$rulename=$tpl->javascript_parse_text("{rulename}");
$profile=$tpl->javascript_parse_text("{profile}");
$service_name=$tpl->javascript_parse_text("{description}");
$blacklist=$tpl->javascript_parse_text("{blacklist}");
$whitelist=$tpl->javascript_parse_text("{whitelist}");
$enabled=$tpl->javascript_parse_text("{enabled}");
$delete=$tpl->javascript_parse_text("{delete}");
$type=$tpl->javascript_parse_text("{type}");
$title=$tpl->javascript_parse_text("{webfiltering} {rules}");
$t=time();
	
$buttons="buttons : [
	{name: '$new_rule', bclass: 'add', onpress : Add$t}
	
		],";
	
		// Table cicap_profiles;
$html="
<input type='hidden' id='table_icap_rules' value='flexRT$t'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?rules-list=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '$rulename', name : 'rulename', width :408, sortable : true, align: 'left'},
		{display: '$profile', name : 'profile', width :200, sortable : true, align: 'left'},
		{display: '$type', name : 'GroupType', width :150, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 95, sortable : true, align: 'center'},
		{display: '$delete', name : 'delete', width : 95, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$profile', name : 'rulename'},
		],
		sortname: 'rulename',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 15,
		showTableToggleBtn: false,
		width: '100%',
		height: 450,
		singleSelect: true
	
	});
	});
	
function Add$t(){
	Loadjs('$page?rule-js=yes&ID=-1&t=$t');
}

var x_EnableDisableCiCapDNSBL= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
}
	
	
function EnableDisableCiCapDNSBL(md5,serv){
	var XHR = new XHRConnection();
	XHR.appendData('EnableDNSBL',serv);
	if(document.getElementById(md5).checked){
	XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableCiCapDNSBL);
}
	
</script>";
echo $html;
}	
function rules_list(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="cicap_profiles";
	$page=1;
	$FORCE_FILTER="";

	if($q->COUNT_ROWS($table)==0){
		json_error_show("$table no items");
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql);


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}


	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$zmd5=md5(serialize($ligne));

		$delete=imgsimple("delete-32.png","","Loadjs('$MyPage?delete-profile-js=$ID&t=$t');");
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$urljs="<a href=\"javascript:Loadjs('$MyPage?profile-js=yes&ID=$ID&t=$t');\"
		style='font-size:18px;color:$color;text-decoration:underline'>";

		$data['rows'][] = array(
				'id' => "$zmd5",
				'cell' => array(
						"<span style='font-size:18px;color:$color'>$urljs{$ligne["rulename"]}</a></span>",
						"<span style='font-size:18px;color:$color'>{$ligne["blacklist"]}</a></span>",
						"<span style='font-size:18px;color:$color'>{$ligne["whitelist"]}</a></span>",
						"<span style='font-size:18px;color:$color'>". Field_checkbox("enable-$ID", 1,$ligne["enabled"],"Loadjs('$MyPage?enable-profile-js=$ID&t=$t');")."</span>",
						"<span style='font-size:18px;color:$color'>$delete</a></span>",
				)
		);
	}


	echo json_encode($data);

}