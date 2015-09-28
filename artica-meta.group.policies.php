<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_POST["link-policy"])){link_policy();exit;}
if(isset($_GET["unlink-js"])){unlink_host();exit;}
if(isset($_POST["unlink"])){unlink_policy_perform();exit;}
if(isset($_POST["synchronize-group"])){synchronize_group();exit;}
if(isset($_GET["search"])){search();exit;}
page();
function page(){


	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$link_host=$tpl->javascript_parse_text("{link_policy}");
	$link_all_hosts=$tpl->javascript_parse_text("{link_all_hosts}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
	$groupname=$tpl->javascript_parse_text($ligne["groupname"]);
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";



	$html="

	<table class='ARTICA_META_GROUPPOLICY_TABLE' style='display: none' id='ARTICA_META_GROUPPOLICY_TABLE' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_GROUPPOLICY_TABLE').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$policies', name : 'policy_name', width : 482, sortable : true, align: 'left'},
	{display: '$type', name : 'policy_type', width : 300, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$policies', name : 'policy_name'},


	],
	sortname: 'policy_name',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$groupname: $policies</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

function LinkHosts$t(){
	Loadjs('artica-meta.policies.php?function=LinkEdHosts$t');
}

var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_GROUPPOLICY_TABLE').flexReload();
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}			
	

function LinkEdHosts$t(policyid){
	var XHR = new XHRConnection();
	XHR.appendData('link-policy',policyid);
	XHR.appendData('gpid','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

function LinkHostsAll$t(){
	if(!confirm('$link_all_hosts_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('link-all','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

function Orders$t(){
	if(!confirm('$synchronize_policies_explain')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('synchronize-group','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);	
}

</script>";
echo $html;
}

function unlink_host(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{unlink}");
	$page=CurrentPageName();
	$zmd5=$_GET["unlink-js"];
	
	
	
	
	$q=new mysql_meta();
	$sql="SELECT `policy-id` FROM metapolicies_link WHERE zmd5='$zmd5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	$policy_id= $ligne["policy-id"];
	if(!is_numeric($policy_id)){
		
	}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name FROM policies WHERE ID='$policy_id'"));
	$policy_name=$tpl->javascript_parse_text("{policy}:".utf8_encode($ligne["policy_name"]));
	
	
	
	
	$t=time();
	echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_GROUPPOLICY_TABLE').flexReload();
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}			
	

function LinkEdHosts$t(){
	if(!confirm('$title $policy_name ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}	

LinkEdHosts$t();
" ;
	
}
function unlink_policy_perform(){
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM metapolicies_link WHERE zmd5='{$_POST["unlink"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function synchronize_group(){
	$sock=new sockets();
	$sock->getFrameWork("artica.php?sync-policies-group={$_POST["synchronize-group"]}");
}


function link_policy(){
	$zmd5=md5("{$_POST["link-policy"]}{$_POST["gpid"]}");
	$q=new mysql_meta();
	$q->QUERY_SQL("INSERT IGNORE INTO metapolicies_link (zmd5,gpid,`policy-id`) 
				VALUES ('$zmd5','{$_POST["gpid"]}','{$_POST["link-policy"]}')");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("artica.php?sync-policies-group={$_POST["gpid"]}");
	
	
}

function link_all_hosts(){
	$q=new mysql_meta();
	$gpid=$_POST["link-all"];
	$results=$q->QUERY_SQL("SELECT uuid FROM metahosts");
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5("{$ligne["uuid"]}{$gpid}");
		$q->QUERY_SQL("INSERT IGNORE INTO metapolicies_link (zmd5,gpid,`policy-id`)
				VALUES ('$zmd5','$gpid','{$ligne["policy-id"]}')");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	
}


function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="metagroups";

	if(!$q->TABLE_EXISTS("backuped_logs","artica_events")){
		json_error_show("no data (no table)",1);
	}
	
	
	$table="(SELECT policies.policy_name,policies.policy_type,policies.ID,
			metapolicies_link.zmd5 
			FROM policies,metapolicies_link WHERE
			metapolicies_link.`policy-id`=policies.ID
			AND metapolicies_link.gpid={$_GET["ID"]}) as t";
	
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];

	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
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

		$ColorTime="black";
		$uuid=$ligne["uuid"];
		$policy_name=utf8_encode($ligne["policy_name"]);
		$policy_type=$tpl->_ENGINE_parse_body($q->policy_type[$ligne["policy_type"]]);
		$zmd5=$ligne["zmd5"];

		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";

		
		$urijs="Loadjs('artica-meta.policies.php?policy-js=yes&ID={$ligne["ID"]}')";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";

		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?unlink-js=$zmd5')");
		$cell=array();
		$cell[]="<span $style>$link$policy_name</a></span><br>$uuid";
		$cell[]="<span $style>$policy_type</a></span>";
		$cell[]="$delete";

		$data['rows'][] = array(
				'id' => $ligne['zmd5'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}