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
	include_once("ressources/class.ldap-extern.inc");
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["Delete-Group"])){groups_delete();exit;}
if(isset($_GET["groups-search"])){groups_search();exit;}
if(isset($_POST["choose-groupe-save"])){groups_choose_add();exit;}
if(isset($_POST["choose-groupe-del"])){groups_choose_del();exit;}

while (list ($num, $ligne) = each ($_REQUEST) ){writelogs("item: $num","MAIN",__FILE__,__LINE__);}



groups();



function isDynamic($ruleid){
	$sql="SELECT webfilter_group.localldap FROM webfilter_group,webfilter_assoc_groups
	WHERE webfilter_assoc_groups.group_id=webfilter_group.ID
	AND webfilter_assoc_groups.webfilter_id=$ruleid
	AND webfilter_group.enabled=1";
	$c=0;
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["localldap"]==0){
			$c++;
		}
		
		if($ligne["localldap"]==2){
			$c++;
		}
	}

		if($c>0){return true;}
return false;
}


function _ActiveDirectoryToName($groupname){
	$groupname=trim($groupname);
	$groupname=strtolower($groupname);
	$groupname=str_replace(" ", "_", $groupname);	
	return $groupname;
}

function checksADGroup($groupname){
	$checked=true;
	$userinfo = @posix_getgrnam($groupname);
	if(!isset($userinfo["gid"])){$checked=false;}
	if(!is_numeric($userinfo["gid"])){$checked=false;}
	if($userinfo["gid"]<1){$checked=false;}	
		$tpl=new templates();
	if(!$checked){
		
	
		$html=$tpl->_ENGINE_parse_body("<table style='border:0px'>
		<tr>
			<td width=1% style='border:0px;border-left:0px;border-bottom:0px;' valign='top'><img src='img/warning-panneau-24.png'></td>
			<td style='border:0px;border-left:0px;border-bottom:0px;' valign='top'><strong style='font-size:12px'>{this_group_is_not_retranslated_to_the_system}</td>
		</tr>
		</table>
		");
	}else{
		$html=$tpl->_ENGINE_parse_body(count($userinfo["members"])." {members}");
	}
	
	return $html;
	
}

function groups(){
	$ID=$_GET["choose-group"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$group=$tpl->_ENGINE_parse_body("{group}");
	$link_group=$tpl->_ENGINE_parse_body("{link_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$items=$tpl->javascript_parse_text("{items}");
	$tt=$_GET["t"];
	$t=time();
	$webfiltering_groups=$tpl->javascript_parse_text("{webfiltering_groups}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$webfiltering_rules=$tpl->javascript_parse_text("{webfiltering_rules}");

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_group</strong>', bclass: 'add', onpress : AddNewDansGuardianGroup$t},
	{name: '<strong style=font-size:18px>$webfiltering_rules</strong>', bclass: 'SSQL', onpress : GotoRules$t},
	],";	
	
	
	$html="
	<input type=hidden id='MAIN_TABLE_UFDB_GROUPS_ALL' value='flexRT$t'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var rowid$t=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?groups-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$group</span>', name : 'groupname', width : 1123, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$items</span>', name : 'items', width : 128, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>&nbsp;</span>', name : 'icon', width :65, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$group', name : 'groupname'}
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$webfiltering_groups</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
function GotoRules$t(){
	GoToUfdbguardRules();
}

var x_SaveDansGUardianMainRule$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	RefreshMainFilterTable();
	if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	$('#flexRT$tt').flexReload(); ExecuteByClassName('SearchFunction');	
}
	
function DansGuardianAddSavedGroup(ID){
	var XHR = new XHRConnection();
	XHR.appendData('choose-groupe-save', ID);
	XHR.appendData('ruleid', '$ID');
	XHR.appendData('QuotaID', '{$_GET["QuotaID"]}');
	XHR.sendAndLoad('$page', 'POST',x_SaveDansGUardianMainRule$t);  		
}
		
function AddNewDansGuardianGroup$t(){
	Loadjs('dansguardian2.edit.group.php?ID-js=-1&yahoo=LoadWinORG');
}
		
function DansGuardianEditGroup$t(ID,rname){
	Loadjs('dansguardian2.edit.group.php?ID-js='+ID+'&t=$t&tt=$tt&yahoo=LoadWinORG');
}	

var x_DansGuardianDelGroup$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
}		
		
function DansGuardianDelGroup$t(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
		rowid=ID;
		var XHR = new XHRConnection();
	    XHR.appendData('Delete-Group', ID);
	    XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup$t); 
	}
}
		
</script>


";
echo $html;
	
}
	
function groups_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$no_group=$tpl->javascript_parse_text("{no_group}");
	
	$search='%';
	$table="webfilter_group";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
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
	

	if($total==0){json_error_show($no_group,1);}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
		
		$localldap[0]="{ldap_group}";
		$localldap[1]="{virtual_group}";
		$localldap[2]="{active_directory_group}";
		$localldap[4]="{remote_ladp_group}";

	
while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		//win7groups-32.png
		$del=imgsimple("delete-32.png",null,"DansGuardianDelGroup$t({$ligne["ID"]})");
		$imgGP="win7groups-32.png";
		if($ligne["localldap"]<2){$imgGP="group-32.png";}
		$typeexplain=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
		if(preg_match("#ExtLDAP:(.+?):#", $ligne["groupname"],$re)){$ligne["groupname"]=$re[1];}
		
		$sql="SELECT count(pattern) as tcount FROM webfilter_members WHERE groupid={$ligne["ID"]}";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		$CountDeGroup=intval($ligne2["tcount"]);
		$groupname_enc=urlencode($ligne["groupname"]);
	
		$uri="<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('dansguardian2.edit.group.php?ID-js={$ligne["ID"]}');\"
				style='font-size:22px;text-decoration:underline'>";
		
		
		$uri_members="<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('dansguardian2.edit.group.php?members-js=yes&groupname=$groupname_enc&ID={$ligne["ID"]}&t=$t');\"
				style='font-size:22px;text-decoration:underline'>";
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<span style='font-size:22px'>$uri{$ligne["groupname"]}&nbsp;</a><span style='font-size:16px'>&laquo;$typeexplain&raquo;</span></div>
			<br><span style='font-size:18px'><i>{$ligne["description"]}</i></span>",
			"<center style='font-size:22px'>$uri_members$CountDeGroup</a></center>",
			"<center>$del</center>"
			)
		);
	}
	
	
	echo json_encode($data);		
	
}
function groups_delete(){
	if(!is_numeric($_POST["Delete-Group"])){return;}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}


	$q->QUERY_SQL("DELETE FROM webfilter_assoc_quota_groups WHERE group_id='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}


	$q->QUERY_SQL("DELETE FROM webfilter_members WHERE groupid='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilter_group WHERE ID='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
} 

