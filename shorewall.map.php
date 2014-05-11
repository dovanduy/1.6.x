<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		

if(isset($_GET["popup"])){table();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_GET["unlink-group-js"])){unlink_group_js();exit;}
if(isset($_POST["unlink-group"])){unlink_group();exit;}
if(isset($_POST["INOUT"])){INOUT();exit;}



js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$zmd5=$_GET["zmd5"];
	$t=$_GET["tsource"];
	$eth=$_GET["eth"];
	if($zmd5==null){$title=$tpl->javascript_parse_text("{new_role}");}
	if($zmd5<>null){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
		$title=$tpl->javascript_parse_text("{shorewall_masq}");
	}

	echo "YahooWin5('650','$page?popup=yes&zmd5=$zmd5&tsource=$t&eth=$eth','$title')";
}

function unlink_group_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_shorewall();
	$linkid=$_GET["linkid"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM fw_objects WHERE ID='{$_GET["groupid"]}'"));
	$t=$_GET["t"];
	$tt=time();
	$ask=$tpl->javascript_parse_text("{unlink_group} {$ligne["groupname"]} ?");
	$html="
			
	var xunlink$tt= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#flexRT$t').flexReload();
		ExecuteByClassName('SearchFunction');
	}
	
	function unlink$tt(){
		if(!confirm('$ask')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('unlink-group',  '$linkid');
		XHR.sendAndLoad('$page', 'POST',xunlink$tt);
	}
			
	unlink$tt();		
			
	";
	
	echo $html;
	
}

function unlink_group(){
	$ID=$_POST["unlink-group"];
	$q=new mysql_shorewall();
	$q->QUERY_SQL("DELETE FROM fw_objects_lnk WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["tsource"];
	$_GET["ruleid"]=$_GET["ID"];
	$groups=$tpl->javascript_parse_text("{groups}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_text=$tpl->javascript_parse_text("{new_network}");
	$network=$tpl->javascript_parse_text("{network2}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$items=$tpl->javascript_parse_text("{items}");
	$title=$tpl->_ENGINE_parse_body("{shorewall_masq}");
	
	$buttons="
	buttons : [
	{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},

	],";

	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&zmd5={$_GET["zmd5"]}',
	dataType: 'json',
	colModel : [
	
	{display: '$network', name : 'net', width :357, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$network', name : 'net'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
Loadjs('shorewall.php?apply-js=yes',true);
}


function NewRule$tt(){
	Loadjs('shorewall.browse.groups.php?ruleid={$_GET["ruleid"]}&t=$tt&t-rule={$_GET["t"]}',true);
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('policy-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}

Start$tt();

</script>
";
echo $html;

}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$shorewall=new mysql_shorewall();
	$zmd5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT nic,roleconf FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
	
	$t=$_GET["t"];
	$search='%';
	
	$page=1;
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	

	
	$roleconf=unserialize(base64_decode($ligne["roleconf"]));
	
	if(count($roleconf)==0){
		$firstnet=$shorewall->FIND_NET($ligne["net"]);
		$roleconf[$firstnet]=$firstnet;
		$roleconfZ=base64_encode(serialize($roleconf));
		$q->QUERY_SQL("UPDATE nics_roles SET roleconf='$roleconfZ' WHERE zmd5='$zmd5'");
	}
	$total=count($roleconf);
	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$fontsize="16";
	if(!is_array($roleconf)){json_error_show("no data");}
	while (list ($key, $value) = each ($roleconf) ){
		$color="black";
		$NICNAME=null;
		$NETENC=urlencode($key);
		$NETMD=md5($NETENC);
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?unlink-group-js=yes&linkid=$NETENC&t={$_GET["t"]}&ruleid=$ruleid')");
		

		
		$data['rows'][] = array(
				'id' => $NETMD,
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$key</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}