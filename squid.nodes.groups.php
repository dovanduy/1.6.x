<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["mac-link-group"])){MAC_LINK_GROUP();exit;}
	if(isset($_GET["NewMacGroup-js"])){macgroup_js();exit;}
	if(isset($_POST["NewMacGroup-save"])){macgroup_add();exit;}
	page();

	
function macgroup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$groups=$tpl->javascript_parse_text("{group}");
	$html="
	var x_AddMacGroup=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			$('#flexRT$t').flexReload(); 
		}		
	
	
	function AddMacGroup(){
		var macgroup=prompt('$ComputerMacAddress:$groups ?');
		if(macgroup){
			var XHR = new XHRConnection();
			XHR.appendData('NewMacGroup-save',macgroup);
			XHR.appendData('NewMacGroup-item','{$_GET["MAC"]}');
			XHR.sendAndLoad('$page', 'POST',x_AddMacGroup);
		}
	}
	
	AddMacGroup();
	";
	
	echo $html;
	
}	

function macgroup_add(){
	$q=new mysql_squid_builder();
	$_POST["NewMacGroup-save"]=addslashes($_POST["NewMacGroup-save"]);
	$sql="INSERT IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled) VALUES ('{$_POST["NewMacGroup-save"]}','arp',1)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	if($q->last_id>0){
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqitems (`pattern`,`gpid`,`enabled`) VALUES ('{$_POST["NewMacGroup-item"]}','$q->last_id','1')");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
}
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$groups=$tpl->_ENGINE_parse_body("{groups}:{ComputerMacAddress}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$t=time();
	$html="
	
	<center>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</center>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&MAC={$_GET["MAC"]}',
	dataType: 'json',
	colModel : [
		{display: '$groups', name : 'GroupName', width :508, sortable : true, align: 'left'},
		{display: '$enabled', name : 'country', width : 70, sortable : false, align: 'center'},
		

		],
		
buttons : [
		{name: '$new_group', bclass: 'add', onpress : NewMACGroup},
		],			
	
	searchitems : [
		{display: '$groups', name : 'GroupName'},
		],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 625,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function NewMACGroup(){
	Loadjs('$page?NewMacGroup-js=yes&t=$t&MAC={$_GET["MAC"]}','$ComputerMacAddress::$new_group');
}

	var x_SqGroupEnable=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			
		}	

function SqGroupEnable(gpid,md){
	var XHR = new XHRConnection();
	XHR.appendData('mac-link-group','{$_GET["MAC"]}');
	XHR.appendData('gpid',gpid);
	if(document.getElementById(md).checked){
		XHR.appendData('action','add');
	}else{
		XHR.appendData('action','del');
	}
	
    XHR.sendAndLoad('$page', 'POST',x_SqGroupEnable);	
}

</script>
	
	
	";
	
	echo $html;
}

function MAC_LINK_GROUP(){
	$action=$_POST["action"];
	$MAC=$_POST["mac-link-group"];
	$gpid=$_POST["gpid"];
	$q=new mysql_squid_builder();
	if($action=="del"){
		$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE `pattern`='{$_GET["MAC"]}' AND gpid='$gpid'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($action=="add"){
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqitems (`pattern`,`gpid`,`enabled`) VALUES ('$MAC','$gpid','1')");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("UPDATE webfilters_sqitems SET enabled=1 WHERE `pattern`='{$_GET["MAC"]}' AND gpid='$gpid'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}


function search(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=13;
	$table="webfilters_sqgroups";
	$search='%';
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$FORCE_FILTER=" AND `GroupType`='arp' AND enabled=1";	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		
		if(strpos(" $search", "%")>0){$QUERY="WHERE (`{$_POST["qtype"]}` LIKE '$search')";}else{$QUERY="WHERE (`{$_POST["qtype"]}` = '$search')";}
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $QUERY $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER ";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $QUERY $FORCE_FILTER $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	

	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"$q->mysql_error", "",""));echo json_encode($data);return;}	
	if(mysql_num_rows($results)==0){array('id' => $ligne[time()],'cell' => array(null,"", "",""));echo json_encode($data);return;}
	
	$data['total'] = mysql_num_rows($results);
	$style="style='font-size:14px'";
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=md5($ligne["ID"]);
		$enabled=0;
 		$sql="SELECT ID FROM webfilters_sqitems WHERE `pattern`='{$_GET["MAC"]}' AND enabled=1 AND gpid='{$ligne["ID"]}'";
 		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne2>0){$enabled=1;}
		$enable=Field_checkbox($id, 1,$enabled,"SqGroupEnable('{$ligne["ID"]}','$id')");
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["GroupName"]}</span>",
			"<span $style>$enable</span>",

			)
			);		
		
		
	}

echo json_encode($data);	
		
	
}