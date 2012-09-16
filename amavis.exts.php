<?php
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["add-gp"])){add_gp();exit;}
	if(isset($_POST["del-gp"])){del_gp();exit;}
	if(isset($_POST["enable-gp"])){enable_gp();exit;}
	if(isset($_GET["items-groups"])){items_groups();exit;}
	if(isset($_GET["gpid-js"])){group_js();exit;}
	if(isset($_GET["gpid-tabs"])){group_tabs();exit;}
	
	if(isset($_GET["gpid-members-table"])){group_members_table();exit;}
	if(isset($_GET["gpid-members-items"])){group_members_items();exit;}
	if(isset($_POST["add-members"])){group_members_add();exit;}
	if(isset($_POST["del-members"])){group_members_del();exit;}
	
	
	if(isset($_GET["gpid-members-rules"])){rules_table();exit;}
	
	if(isset($_GET["rules-items"])){rules_items();exit;}
	if(isset($_POST["add-rule"])){rules_add();exit;}
	if(isset($_POST["enable-rules"])){rules_enable();exit;}
	if(isset($_POST["del-ext-rule"])){rules_delete();exit;}
	
	if(isset($_GET["ruleid-js"])){simplerule_js();exit;}
	if(isset($_GET["ruleid-popup"])){simplerule_table();exit;}
	if(isset($_GET["ruleid-items"])){simplerule_items();exit;}
	if(isset($_POST["add-extensions"])){simplerule_items_add();exit;}
	if(isset($_POST["del-ext"])){simplerule_items_del();exit;}
	if(isset($_POST["pass-ext"])){pass_extention();exit;}
	
	
	
	js();
	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{filter_extension}");
	$html="YahooWin2('550','$page?popup=yes','$title')";
	echo $html;
}

function group_js(){
	$page=CurrentPageName();
	$gpid=$_GET["gpid-js"];
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM amavisd_ext_grps WHERE ID='$gpid'","artica_backup"));
	$html="YahooWin3('470','$page?gpid-tabs=$gpid&t={$_GET["t"]}','{$ligne["groupname"]}')";
	echo $html;	
}

function simplerule_js(){
	$page=CurrentPageName();
	$ruleid=$_GET["ruleid-js"];
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM amavisd_ext_rules WHERE ID='$ruleid'","artica_backup"));
	$html="YahooWin4('470','$page?ruleid-popup=$ruleid&ruleid=$ruleid&t={$_GET["t"]}&tt={$_GET["tt"]}&ttt={$_GET["ttt"]}','{$ligne["groupname"]}')";
	echo $html;		
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=520;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_group}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$title=$tpl->_ENGINE_parse_body("{groups}:&nbsp;&laquo;{filter_extension}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_gorup=$tpl->javascript_parse_text("{inputbox delete group}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-groups=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '$groupname', name : 'groupname', width :176, sortable : true, align: 'left'},
		{display: '$members', name : 'CountDeMembers', width :31, sortable : false, align: 'center'},
		{display: '$rules', name : 'action', width :177, sortable : false, align: 'left'},
		{display: '$enable', name : 'action', width :31, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$groupname', name : 'groupname'},

	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=304','1024','900');
}


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}

function NewGItem$t(){
	var gp=prompt('$groupname','New Group');
	if(gp){
	  var XHR = new XHRConnection();
      XHR.appendData('add-gp',gp);
      XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);	
	
	}
	
}

var x_AmavisExtDeleteGroups=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row$t'+mem$t).remove();
}

function GroupAmavisExtEnable(id){
	var value=0;
	if(document.getElementById('gp'+id).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-gp',id);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}


function AmavisExtDeleteGroups(id){
	if(confirm('$ask_delete_gorup')){
		mem$t=id;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-gp',id);
      	XHR.sendAndLoad('$page', 'POST',x_AmavisExtDeleteGroups);		
	
	}

}

</script>";
	
	echo $html;
}
function items_groups(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		
	
	$search='%';
	$table="amavisd_ext_grps";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=$ligne["ID"];
	$groupname=$ligne["groupname"];
	$delete=imgsimple("delete-24.png",null,"AmavisExtDeleteGroups('$id')");
	$color="black";
	if($ligne["enabled"]==0){$color="#B6ACAC";}
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?gpid-js=$id&t=$t');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM amavisd_ext_members WHERE gpid='$id'",
	"artica_backup"));
	$CountDeMembers=$ligne2["tcount"];
	if(!is_numeric($CountDeMembers)){$CountDeMembers=0;}

	$tr=array();
	$sql="SELECT amavisd_ext_rules.groupname,
	amavisd_ext_rules.ID FROM amavisd_ext_rules,amavisd_ext_link
	WHERE amavisd_ext_link.ruleid=amavisd_ext_rules.ID AND 
	amavisd_ext_rules.enabled=1 AND
	amavisd_ext_link.gpid=$id ORDER BY amavisd_ext_rules.groupname";
	$results2 = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){$tr[]=$q->mysql_error;}
	//$tr[]=nl2br($sql);
	while ($ligne2 = mysql_fetch_assoc($results2)) {
		if(isset($ALR[$ruleid])){continue;}
		$ruleid=$ligne["ID"];
		$urlff="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?ruleid-js=$ruleid&t=$t&tt=$tt&ttt=$ttt');\"
		style='font-size:14px;color:$color;text-decoration:underline'>";
		$tr[]="<div style='width:11px'>$urlff{$ligne2["groupname"]}</a></div>";
		$ALR[$ruleid]=true;
	}

	if(count($tr)==0){
		$tr[]=$tpl->_ENGINE_parse_body("{disabled_no_rule}");
	}
	
	
	
	$enabled=Field_checkbox("gp$id", 1,$ligne["enabled"],"GroupAmavisExtEnable('$id')");
	
	$data['rows'][] = array(
		'id' => "$t$id",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs$groupname</a></span>",
			"<span style='font-size:18px;color:$color'>$CountDeMembers</a></span>",
			"<span style='font-size:14px;color:$color'>". @implode(" ",$tr)."</a></span>",
			"<span style='font-size:16px;color:$color'>$enabled</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function add_gp(){
	$gp=$_POST["add-gp"];
	$sql="INSERT INTO amavisd_ext_grps (groupname,enabled) VALUE('$gp',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function enable_gp(){
	$gpid=$_POST["enable-gp"];
	$sql="UPDATE amavisd_ext_grps SET enabled={$_POST["value"]} WHERE ID=$gpid";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}	
}

function del_gp(){
	$gpid=$_POST["del-gp"];
	$sql="DELETE FROM amavisd_ext_members WHERE gpid=$gpid";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}

	$sql="DELETE FROM amavisd_ext_link WHERE gpid='$gpid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}	
	
	$sql="DELETE FROM amavisd_ext_grps WHERE ID='$gpid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}		
	
}

function rules_add(){
	$rulename=$_POST["add-rule"];
	$gpid=$_POST["gpid"];
	$sql="INSERT INTO amavisd_ext_rules (groupname,enabled) VALUE('$rulename',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	if(is_numeric($gpid)){
		$sql="INSERT INTO amavisd_ext_link (gpid,ruleid) VALUE('$gpid',$q->last_id)";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}	
	}
}
function rules_enable(){
$ID=$_POST["enable-rules"];
$gpid=$_POST["gpid"];
$value=$_POST["value"];
$q=new mysql();
if($value==1){
		$sql="INSERT INTO amavisd_ext_link (gpid,ruleid) VALUE('$gpid','$ID')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}		
	
}else{
		$sql="DELETE FROM amavisd_ext_link WHERE gpid='$gpid' AND ruleid='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}	
	
}

}

function rules_delete(){
	$q=new mysql();
	$ruleid=$_POST["del-ext-rule"];
	
	$sql="DELETE FROM amavisd_ext_link WHERE ruleid='$ruleid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	
	$sql="DELETE FROM amavisd_ext_items WHERE ruleid='$ruleid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	

	$sql="DELETE FROM amavisd_ext_rules WHERE ID='$ruleid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}


function group_tabs(){
	$page=CurrentPageName();
	$gpid=$_GET["gpid-tabs"];
	$t=$_GET["t"];
	$tpl=new templates();	
	
	$array["gpid-members-table"]='{members}';
	$array["gpid-members-rules"]='{rules}';
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&gpid=$gpid&t=$t\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_extgp style='width:100%;height:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_extgp').tabs();
			
			
			});
		</script>";			
}

function group_members_table(){
	$gpid=$_GET["gpid"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=430;
	
	
	$t=$_GET["t"];
	$tt=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_member}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$title=$tpl->_ENGINE_parse_body("{groups}:&nbsp;&laquo;{filter_extension}&raquo;");
	$amavis_addmember_explain=$tpl->javascript_parse_text("{amavis_addmember_explain}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewMItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
<script>
var mem$tt='';
$(document).ready(function(){
$('#flexRT$tt').flexigrid({
	url: '$page?gpid-members-items=yes&t=$t&tt=$tt&gpid=$gpid',
	dataType: 'json',
	colModel : [	
		{display: '$members', name : 'member', width :356, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : true, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$members', name : 'member'},

	],
	sortname: 'member',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=304','1024','900');
}

var x_NewMItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$tt').flexReload();
    $('#flexRT$t').flexReload();
}

function NewMItem$t(){
	var gp=prompt('$amavis_addmember_explain','');
	if(gp){
	  var XHR = new XHRConnection();
      XHR.appendData('add-members',gp);
      XHR.appendData('gpid','$gpid');
      XHR.sendAndLoad('$page', 'POST',x_NewMItem$t);	
	}
	
}
var x_AmavisExtDeleteMember$tt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowM'+mem$tt).remove();
    $('#flexRT$t').flexReload();
}

function AmavisExtDeleteMember$tt(id){
	  mem$tt=id;
	  var XHR = new XHRConnection();
      XHR.appendData('del-members',id);
      XHR.sendAndLoad('$page', 'POST',x_AmavisExtDeleteMember$tt);
}


</script>";
	
	echo $html;	
	
}

function group_members_items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$tt=$_GET["tt"];
	
	$search='%';
	$table="amavisd_ext_members";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="AND gpid='{$_GET["gpid"]}'";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=$ligne["ID"];
	$member=$ligne["member"];
	$delete=imgsimple("delete-24.png",null,"AmavisExtDeleteMember$tt('$id')");
	
	if(trim($member)=="."){$member="&laquo;.&raquo; (".$tpl->_ENGINE_parse_body("{all}").")";}

	
	
	$data['rows'][] = array(
		'id' => "M$id",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$member</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
}

function group_members_del(){
	$id=$_POST["del-members"];
		$sq="DELETE FROM amavisd_ext_members WHERE ID='$id'";
		$q=new mysql();
		$q->QUERY_SQL($sq,"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error;
			return;
		}	
	
}

function group_members_add(){
	
	$gpid=$_POST["gpid"];
	if(!is_numeric($gpid)){return;}
	$_POST["add-members"]=$_POST["add-members"].",";
	$tp=explode(",", $_POST["add-members"]);
	while (list ($num, $ligne) = each ($tp) ){
		if(trim($ligne)==null){continue;}
		if(strpos($ligne, "@")==0){if(substr($ligne, 0,1)<>"."){$ligne=".$ligne";}}
		$f[]="('$ligne','$gpid')";
		
	}
	
	if(count($f)>0){
		$q=new mysql();
		$sq="INSERT INTO amavisd_ext_members (member,gpid) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sq,"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error;
			return;
		}
		
	}
}


function rules_table(){
	$gpid=$_GET["gpid"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=430;
	
	
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_give_rulename=$tpl->javascript_parse_text("{ask_give_rulename}");
	$ask_delete_this_rule=$tpl->javascript_parse_text("{ask_delete_this_rule}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewRItem$ttt},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	],	";
	
	
	$html="
	<table class='flexRT$ttt' style='display: none' id='flexRT$ttt' style='width:99%'></table>
<script>
var mem$ttt='';
$(document).ready(function(){
$('#flexRT$ttt').flexigrid({
	url: '$page?rules-items=yes&t=$t&tt=$tt&ttt=$ttt&gpid=$gpid',
	dataType: 'json',
	colModel : [	
		{display: '$rules', name : 'groupname', width :272, sortable : true, align: 'left'},
		{display: '$items', name : 'item', width :31, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'action', width :31, sortable : true, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$rules', name : 'groupname'},

	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

var x_NewRItem$ttt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$tt').flexReload();
    $('#flexRT$t').flexReload();
    $('#flexRT$ttt').flexReload();
    
    
}

function NewRItem$ttt(){
	var gp=prompt('$ask_give_rulename','New rule');
	if(gp){
	  var XHR = new XHRConnection();
      XHR.appendData('add-rule',gp);
      XHR.appendData('gpid','$gpid');
      XHR.sendAndLoad('$page', 'POST',x_NewRItem$ttt);	
	}
	
}
var x_AmavisExtDeleteRule$tt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
     $('#row$ttt'+mem$ttt).remove();
	
}

function AmavisExtDeleteRule$tt(id){
	  mem$ttt=id;
	  if(confirm('$ask_delete_this_rule')){
		  var XHR = new XHRConnection();
	      XHR.appendData('del-ext-rule',id);
	      XHR.sendAndLoad('$page', 'POST',x_AmavisExtDeleteRule$tt);
      }
}
var x_ExtRuleEnable$ttt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#flexRT$t').flexReload();
    $('#flexRT$tt').flexReload();
    $('#flexRT$ttt').flexReload();
}
function ExtRuleEnable$ttt(ruleid,gpid,itemid){
  	var value=0;
	 if(document.getElementById(itemid).checked){value=1;}
	 var XHR = new XHRConnection();
     XHR.appendData('enable-rules',ruleid);
     XHR.appendData('gpid',gpid);
     XHR.appendData('value',value);
     XHR.sendAndLoad('$page', 'POST',x_ExtRuleEnable$ttt);
}


</script>";
	
	echo $html;	
}

function rules_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$tt=$_GET["tt"];
	$ttt=$_GET["ttt"];
	$gpid=$_GET["gpid"];
	$search='%';
	$table="amavisd_ext_rules";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=$ligne["ID"];
	$rulename=$ligne["groupname"];
	$delete=imgsimple("delete-24.png",null,"AmavisExtDeleteRule$tt('$id')");
	$enabled=0;
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM amavisd_ext_link WHERE gpid=$gpid AND ruleid='$id'",
	"artica_backup"));
	$linkid=$ligne2["ID"];
	if($linkid>0){$enabled=1;}
	
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM amavisd_ext_items WHERE ruleid=$id",
	"artica_backup"));
	$CountDeExts=$ligne2["tcount"];
	
	
	
	$color="black";
	if($enabled==0){$color="#B6ACAC";}
	$enable=Field_checkbox("$t$id", 1,$enabled,"ExtRuleEnable$ttt('$id','$gpid','$t$id')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?ruleid-js=$id&t=$t&tt=$tt&ttt=$ttt');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
		'id' => "$ttt$id",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs$rulename</a></span>",
		"<span style='font-size:16px;color:$color'>$CountDeExts</a></span>",
			"<span style='font-size:16px;color:$color'>$enable</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		
}

function simplerule_items_add(){
	
	$_POST["add-extensions"]=$_POST["add-extensions"]." ";
	$ruleid=$_POST["ruleid"];
	
	if(!is_numeric($ruleid)){return;}
	
	$tp=explode(" ", $_POST["add-extensions"]);
	while (list ($num, $ligne) = each ($tp) ){
		if(trim($ligne)==null){continue;}
		if(substr($ligne, 0,1)=="."){$ligne=substr($ligne,1, strlen($ligne));}
		$f[]="('$ligne','$ruleid')";
		
	}
	
	if(count($f)>0){
		$q=new mysql();
		$sq="INSERT INTO amavisd_ext_items (pattern,ruleid) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sq,"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error;
			return;
		}
		
	}	
}

function simplerule_items_del(){
	$id=$_POST["del-ext"];
	$q=new mysql();
	$sql="DELETE FROM amavisd_ext_items WHERE ID='$id'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
			echo $q->mysql_error;
			return;
		}	
}

function pass_extention(){
	
	$sql="UPDATE amavisd_ext_items SET `pass`={$_POST["value"]} WHERE ID='{$_POST["pass-ext"]}'";
	
$q=new mysql();
	
	$q->QUERY_SQL($sql,"artica_backup");	
if(!$q->ok){
			echo $q->mysql_error;
			return;
		}		
}

	

function simplerule_table(){
	$ruleid=$_GET["ruleid"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=430;
	
	
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=$_GET["ttt"];
	$tttt=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$extensions=$tpl->_ENGINE_parse_body("{extensions}");
	$AmavisAddExtFilter_text=$tpl->javascript_parse_text("{AmavisAddExtFilter_text}");
	$compile_rules=$tpl->javascript_parse_text("{compile_rules}");
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewMItem$tttt},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	],	";
	
	
	$html="
	<table class='flexRT$tttt' style='display: none' id='flexRT$tttt' style='width:99%'></table>
<script>
var mem$tttt='';
$(document).ready(function(){
$('#flexRT$tttt').flexigrid({
	url: '$page?ruleid-items=yes&t=$t&tt=$tt&ttt=$ttt&tttt=$tttt&ruleid=$ruleid',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'action', width :31, sortable : true, align: 'center'},
		{display: '$extensions', name : 'pattern', width :257, sortable : true, align: 'left'},
		{display: 'PASS', name : 'pass', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$extensions', name : 'pattern'},

	],
	sortname: 'pattern',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

var x_NewMItem$tttt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$tttt').flexReload();
    $('#flexRT$ttt').flexReload();
    $('#flexRT$tt').flexReload();
    $('#flexRT$t').flexReload();
}

function NewMItem$tttt(){
	var gp=prompt('$AmavisAddExtFilter_text','');
	if(gp){
	  var XHR = new XHRConnection();
      XHR.appendData('add-extensions',gp);
      XHR.appendData('ruleid','$ruleid');
      XHR.sendAndLoad('$page', 'POST',x_NewMItem$tttt);	
	}
	
}
var x_AmavisDelext$tttt=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row$tttt'+mem$tttt).remove();
    $('#flexRT$t').flexReload();
    $('#flexRT$ttt').flexReload();
    $('#flexRT$tt').flexReload();
     
}

function AmavisDelext$tttt(id){
	  mem$tttt=id;
	  var XHR = new XHRConnection();
      XHR.appendData('del-ext',id);
      XHR.sendAndLoad('$page', 'POST',x_AmavisDelext$tttt);
}

var x_PassExtAmavis=function(obj){
	var tempvalue=obj.responseText;
 	$('#flexRT$tttt').flexReload();
}

function PassExtAmavis(id){
	var value=0;
	 if(document.getElementById('ENA'+id).checked){value=1;}
	  var XHR = new XHRConnection();
      XHR.appendData('pass-ext',id);
      XHR.appendData('value',value);
      XHR.sendAndLoad('$page', 'POST',x_PassExtAmavis);
}




</script>";
	
	echo $html;	
		
	
	
}

function simplerule_items(){
	//1.4.010916
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=$_GET["ttt"];
	$tttt=$_GET["tttt"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="amavisd_ext_items";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="AND ruleid='{$_GET["ruleid"]}'";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=$ligne["ID"];
	$pattern=$ligne["pattern"];
	$delete=imgsimple("delete-24.png",null,"AmavisDelext$tttt('$id')");
	$pass=$ligne["pass"];
	$img="stop-24.png";
	if($pass==1){$img="ok24.png";}
	
	$enable=Field_checkbox("ENA$id", 1,$pass,"PassExtAmavis('$id')");
	
	
	$data['rows'][] = array(
		'id' => "$tttt$id",
		'cell' => array(
			"<span style='font-size:16px;color:$color'><img src='img/$img'></span>",
			"<span style='font-size:16px;color:$color'>$pattern</a></span>",
			"<span style='font-size:16px;color:$color'>$enable</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
}
?>