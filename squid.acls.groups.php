<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}
if(isset($_POST["TimeSave"])){item_date_save();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}
if(isset($_GET["AddGroup-js"])){AddGroup_js();exit;}
if(isset($_GET["EditGroup-popup"])){EditGroup_popup();exit;}
if(isset($_POST["GroupName"])){EditGroup_save();exit;}
if(isset($_POST["DeleteTimeRule"])){EditTimeRule_delete();exit;}
if(isset($_POST["EnableGroup"])){EditGroup_enable();exit;}
if(isset($_POST["DeleteGroup"])){EditGroup_delete();exit;}



if(isset($_GET["items"])){items_js();exit;}
if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_GET["AddItem-tab"])){item_tab();exit;}
if(isset($_GET["AddItem-js"])){item_popup_js();exit;}
if(isset($_GET["AddItem-popup"])){item_form();exit;}
if(isset($_GET["AddItem-import"])){item_form_import();exit;}
if(isset($_POST["item-import"])){item_import();exit;}
if(isset($_POST["item-pattern"])){item_save();exit;}
if(isset($_POST["EnableItem"])){item_enable();exit;}
if(isset($_POST["DeleteItem"])){item_delete();exit;}
if(isset($_GET["items-date"])){item_date();exit;}



page();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$html="YahooWin('750','$page?table-width=730&table-heigth=450&GroupName-width=360&GroupType-width=169&table-org={$_GET["toexplainorg"]}','$title')";
	echo $html;
	
}



function item_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["item-id"];
	if($ID>0){
		$title="{item}:$ID";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin5(450,'$page?AddItem-tab=yes&item-id=$ID&ID={$_GET["ID"]}&table-t={$_GET["table-t"]}&table-org={$_GET["table-org"]}','$title')";
	echo $html;
}

function AddGroup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$ID'"));
		$title="{group}:$ID&nbsp;&raquo;&nbsp;{$ligne["GroupName"]}&nbsp;&raquo;&nbsp;{$GLOBALS["GroupType"][$ligne["GroupType"]]}";
	}else{
		
		$title="{new_item}";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin4(546,'$page?EditGroup-popup=yes&ID=$ID&link-acl={$_GET["link-acl"]}&table-acls-t={$_GET["table-acls-t"]}&table-org={$_GET["table-org"]}','$title')";
	echo $html;	
	
}

function EditGroup_popup(){
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID>0){if(!isset($_GET["tab"])){EditGroup_tabs();return;}}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$ID'"));
	$buttonname="{apply}";
	$acltpl_md5=trim($ligne["acltpl"]);
	$acltpl="{default}";
	$sock=new sockets();
	$jstpl="blur();";
	$browse="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.templates.php?choose-acl=$ID');\" 
	style='font-weight:normal;text-decoration:underline;font-size:14px'>";
	if($ID<1){$buttonname="{add}";$browse=null;$acltpl=null;}	
	if($acltpl_md5<>null){
			$md5=$acltpl_md5;
			$sql="SELECT template_name,template_link FROM squidtpls WHERE `zmd5`='{$acltpl_md5}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$templatename=$ligne2["template_name"];
			
			$acltpl=addslashes($ligne2["template_name"]);
			$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($acltpl)."');";
			$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:14px;text-decoration:underline'>$templatename</a>";
			if($ligne2["template_link"]==1){
				$acltpl="<span style='font-size:14px;'>$templatename</span>";
			}
		}	
	
	
	$t=time();
	$GroupType=$q->acl_GroupType;

	
	
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{groupname}:</td>
		<td>". Field_text("GroupName",utf8_encode($ligne["GroupName"]),"font-size:14px;width:340px",null,null,null,false,"SaveAclGroupModeCheck(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{group_type}:</td>
		<td>". Field_array_Hash($GroupType,"GroupType",$ligne["GroupType"],"style:font-size:14px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px' valign='top'>{template}:</td>
		<td>
			<table style='width:99%'>
			<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><strong style='font-size:12px'><span id='acltplTxt'>$acltpl</span></a></td>
			</tr>
			<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><span style='font-size:14px'>$browse<span id='acltplTxt'>{change_template}</span></a></td>
			</tr>			
		</table>
		</td>
	</tr>	
	
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "SaveAclGroupMode()",16)."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveAclGroupMode= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		document.getElementById('GroupName').value='';
		if(document.getElementById('formulaire-choix-groupe-proxy')){RefreshFormulaireChoixGroupeProxy();}
		var tableaclt='{$_GET["table-acls-t"]}';
		var tableorg='{$_GET["table-org"]}';
		if(tableaclt.length>3){ $('#table-items-'+tableaclt).flexReload();}
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}
		RefreshSquidGroupTable();
	}
	
	function SaveAclGroupModeCheck(e){
		if(checkEnter(e)){SaveAclGroupMode();}
	}
	
	function SaveAclGroupMode(){
		      var XHR = new XHRConnection();
		      XHR.appendData('GroupName', document.getElementById('GroupName').value);
		      XHR.appendData('GroupType', document.getElementById('GroupType').value);
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('link-acl', '{$_GET["link-acl"]}');
		       AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveAclGroupMode);  		
		}	
		
	function CheckGrouform$t(){
		var id=$ID;
		if(id>0){document.getElementById('GroupType').disabled=true;}
	}
CheckGrouform$t();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function EditGroup_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	 
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}
function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}

function EditGroup_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
		
	$sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled) 
	VALUES ('{$_POST["GroupName"]}','{$_POST["GroupType"]}','1');";
	
	$sql="UPDATE webfilters_sqgroups SET GroupName='{$_POST["GroupName"]}' WHERE ID='$ID'";

	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($ID<1){
		$gpid=$q->last_id;
		if($_POST["link-acl"]>0){
			$aclid=$_POST["link-acl"];
			$md5=md5($aclid.$gpid);
			$sql="INSERT IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid) VALUES('$md5','$aclid','$gpid')";
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;}
		}
	}
	
	

}
function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql_squid_builder();

	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled) 
	VALUES ('{$_POST["item-pattern"]}','$gpid','1');";
	
	$sql="UPDATE webfilters_sqitems SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();	
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}
function item_import(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql_squid_builder();

	$sqladd="INSERT IGNORE INTO webfilters_sqitems (pattern,gpid,enabled) VALUES ";
	
	$f=explode("\n",$_POST["item-import"]);
	while (list ($num, $pattern) = each (	$f)){
		if(trim($pattern)==null){continue;}
		if(preg_match("^www\.(.+)#",$pattern,$re)){$pattern=$re[1];}
		
		
		$t[]="('$pattern','$gpid','1')";
		
	}
	if(count($t)>0){
		
		$sql=$sqladd.@implode(",", $t);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n***\n$sql\n****\n";return;}
		$sock=new sockets();	
		$sock->getFrameWork("cmd.php?squid-rebuild=yes");	
	}
}

function item_date(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tableorg=$_GET["table-org"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT other FROM webfilters_sqitems WHERE gpid='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$t=time();
	$pattern=base64_decode($ligne["other"]);
	$TimeSpace=unserialize(base64_decode($ligne["other"]));
	
	
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	$title="<strong style='font-size:16px'>{days}:</strong>";
	
	while (list ($num, $val) = each ($days) ){
		
		$jsjs[]="if(document.getElementById('$t-day_{$num}').checked){ XHR.appendData('day_{$num}',1);}else{ XHR.appendData('day_{$num}',0);}";
		
		$tt[]="
		<table style='width:1%'>
		<tr>
		<td width=1%>". Field_checkbox("$t-day_{$num}",1,$TimeSpace["day_{$num}"])."</td>
		<td width=99% class=legend style='font-size:16px' align='left'>{{$val}}</td>
		</tr>
		</table>
		";
		
	}	
	
	$dayF=CompileTr3($tt,false,$title);
	
	
	$html="
	<div id='animate-$t'></div>
	$dayF
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' width=50%>
		<center>
		<strong style='font-size:16px'>{from_time}</strong>
		".field_iphone_time("FT-$t",$TimeSpace["H1"])."</center></td>
		<td valign='top' width=50%><center>
		<strong style='font-size:16px'>{to_time}</strong>
		".field_iphone_time("FT2-$t",$TimeSpace["H2"])."</center></td>
	</tr>
	</table>
	<hr>
	<div style='width:100%;text-align:right'>". button("{apply}","SaveFF$t()","18px")."</div>
	
	<script>
	var x_SaveFF$t= function (obj) {
		var tableorg='$tableorg';
		var res=obj.responseText;
		document.getElementById('animate-$t').innerHTML='';
		var ID=$ID;
		if(res.length>3){alert(res);return;}
		RefreshTab('main_content_rule_editsquidgroup');
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}
	}
	
	function SaveFF$t(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeSave', 'yes');
		      XHR.appendData('ID', '$ID');
		      ". @implode("\n", $jsjs)."
		      XHR.appendData('H1', document.getElementById('FT-$t').value);
		      XHR.appendData('H2', document.getElementById('FT2-$t').value);
			  AnimateDiv('animate-$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveFF$t);  		
		}
		
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function item_date_save(){
	$ID=$_POST["ID"];
	$sql="SELECT other,pattern FROM webfilters_sqitems WHERE gpid='$ID'";
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$pattern=base64_encode(serialize($_POST));
	if(strlen(trim($ligne["pattern"]))<3){
		$sql="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ('$pattern','$ID','1','$pattern');";
	}else{
		$sql="UPDATE webfilters_sqitems SET pattern='$pattern',other='$pattern' WHERE gpid='$ID'";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}
	
	
	
function EditGroup_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];

	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID"));
	
	$array["items"]='{items}';
	$array["EditGroup-popup"]='{settings}';
	
	if($ligne["GroupType"]=="proxy_auth_ads"){unset($array["items"]);}
	if($ligne["GroupType"]=="NudityScan"){
		unset($array["items"]);
		$array["NudityParams"]="{global_parameters}";
	
	}
	if($ligne["GroupType"]=="time"){unset($array["items"]);$array["items-date"]='{items}';}

	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="NudityParams"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"squid.nudityscan.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&tab=yes&table-org={$_GET["table-org"]}\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "
	<div id=main_content_rule_editsquidgroup style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_content_rule_editsquidgroup').tabs();
			
			
			});
		</script>";	
}

function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();		
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteGroupItemTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID&table-org={$_GET["table-org"]}',
	dataType: 'json',
	colModel : [
		{display: '$items', name : 'pattern', width : 386, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : AddItem},
		],	
	searchitems : [
		{display: '$items', name : 'pattern'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 504,
	height: 250,
	singleSelect: true
	
	});   
});
function AddItem() {
	Loadjs('$page?AddItem-js=yes&item-id=-1&ID=$ID&table-t=$t&table-org={$_GET["table-org"]}');
	
}	

function RefreshSquidGroupItemsTable(){
	$('#table-$t').flexReload();
}


	var x_DeleteGroupItem= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowitem'+DeleteGroupItemTemp).remove();
		RefreshSquidGroupTable();
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
	}	
	
	function DeleteGroupItem(ID){
		DeleteGroupItemTemp=ID;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteItem', 'yes');
		XHR.appendData('ID', ID);
		XHR.sendAndLoad('$page', 'POST',x_DeleteGroupItem);  		
	}

	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	function EnableDisableItem(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableItem', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('itemid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		

</script>
	
	";
	
	echo $html;
	
}

function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$t=time();		
	$table_width=835;
	$table_height=350;
	$GroupName_width=372;
	$GroupType_width=278;
	if(isset($_GET["table-width"])){$table_width=$_GET["table-width"];}
	if(isset($_GET["table-heigth"])){$table_height=$_GET["table-heigth"];}
	if(isset($_GET["GroupName-width"])){$GroupName_width=$_GET["GroupName-width"];}
	if(isset($_GET["GroupType-width"])){$GroupType_width=$_GET["GroupType-width"];}
	
	
	$html=$tpl->_ENGINE_parse_body("")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?groups-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$description', name : 'GroupName', width : $GroupName_width, sortable : true, align: 'left'},
		{display: '$time', name : 'GroupType', width : $GroupType_width, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 37, sortable : false, align: 'center'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddGroup},
		],	
	searchitems : [
		{display: '$description', name : 'GroupName'},
		],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $table_width,
	height: $table_height,
	singleSelect: true
	
	});   
});
function AddGroup() {
	Loadjs('$page?AddGroup-js=yes&ID=-1&table-org={$_GET["table-org"]}');
	
}	

function RefreshSquidGroupTable(){
	$('#table-$t').flexReload();
	var tableorg='{$_GET["table-org"]}';
	if(tableorg.length>3){ $('#'+tableorg).flexReload();}		
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}		
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
		
	}	
	
	function DeleteSquidAclGroup(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_group_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteGroup', 'yes');
			XHR.appendData('ID', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
		}  		
	}

	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowgroup'+DeleteSquidAclGroupTemp).remove();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}		
	}
	
	function EnableDisableGroup(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableGroup', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('groupid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function item_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	
	$array["AddItem-popup"]='{item}';
	$array["AddItem-import"]='{import}';
	if($_GET["item-id"]>0){
		unset($array["AddItem-import"]);
	}
	

	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&item-id={$_GET["item-id"]}&ID={$_GET["ID"]}&table-t={$_GET["table-t"]}&table-org={$_GET["table-org"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	
	}

	
	echo "
	<div id=squid_aclm_item_add style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_aclm_item_add').tabs();
			
			
			});
		</script>";	
}

function item_form_import(){
	$ID=$_GET["ID"];
	$item_id=$_GET["item_id"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	$GroupTypeText=$q->acl_GroupType[$GroupType];
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE ID='$item_id'"));
	$buttonname="{add}";
	if($ID<1){$buttonname="{add}";}
	
	
	
	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse="<input type='button' value='{browse}...' 
		OnClick=\"javascript:Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr');\" style='font-size:12px'>";
	}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="dst"){$explain="{acl_squid_dst_explain}";}
	if($GroupType=="proxy_auth"){
		
		if($EnableKerbAuth==1){
			$browse="<input type='button' value='{browse}...' 
			OnClick=\"javascript:Loadjs('BrowseActiveDirectory.php?field-user=$t-pattern&OnlyGroups=1&OnlyAD=1&OnlyGUID=1');\" style='font-size:12px'>";
		}
		$explain="{acl_proxy_auth_explain}";}
	
	

	$html="
	<div style='font-size:16px'>$GroupTypeText</div>
	<div class=explain style='font-size:12px'>$explain</div>
	<div id='$t'></div>
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{pattern}:</td>
	</tr>
	<tr>
		<td><textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;border:5px solid #8E8E8E;overflow:auto;font-size:16px' 
		id='textToParseCats-$t'></textarea>
	</td>
	</tr>
	<tr>
	<td><hr>". button($buttonname, "SaveItemsMode$t()",16)."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveItemsMode$t= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		document.getElementById('textToParseCats-$t').value='';
		RefreshSquidGroupTable();
		if(!document.getElementById('table-{$_GET["table-t"]}')){
			if(document.getElementById('main_content_rule_editsquidgroup')){
				RefreshTab('main_content_rule_editsquidgroup');
			}
		}else{
			$('#table-{$_GET["table-t"]}').flexReload();
		}
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}			
		
	}
	
	function SaveItemsModeCheck(e){
		if(checkEnter(e)){SaveItemsMode();}
	}
	
	function SaveItemsMode$t(){
		      var XHR = new XHRConnection();
		      XHR.appendData('item-import', document.getElementById('textToParseCats-$t').value);
		      XHR.appendData('item-id', '$item_id');
		      XHR.appendData('ID', '$ID');		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}


function item_form(){
	$ID=$_GET["ID"];
	$item_id=$_GET["item_id"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	$GroupTypeText=$GLOBALS["GroupType"][$GroupType];
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE ID='$item_id'"));
	$buttonname="{add}";
	if($ID<1){$buttonname="{add}";}
	
	
	
	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse="<input type='button' value='{browse}...' OnClick=\"javascript:Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr');\" style='font-size:12px'>";
	}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
	if($GroupType=="browser"){
		$explain="{acl_squid_browser_explain}";
		$browse=button("{list}..","Loadjs('squid.browsers.php?ShowOnly=1')","12px");
	}
	
	
	
	if($GroupType=="proxy_auth"){
		if($EnableKerbAuth==1){$browse="<input type='button' value='{browse}...' OnClick=\"javascript:Loadjs('BrowseActiveDirectory.php?field-user=$t-pattern&OnlyGroups=1&OnlyAD=1&OnlyGUID=1');\" style='font-size:12px'>";}
		$explain="{acl_proxy_auth_explain}";
	}
	
	

	$html="
	<div style='font-size:16px'>$GroupTypeText</div>
	<div class=explain style='font-size:12px'>$explain</div>
	<div id='$t'></div>
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{pattern}:</td>
		<td>". Field_text("$t-pattern",utf8_encode($ligne["pattern"]),"font-size:14px;width:240px",null,null,null,false,"SaveItemsModeCheck(event)")."</td>
		<td width=1%>$browse</td>
	</tr>
	<tr>
	<td colspan=3 align='right'><hr>". button($buttonname, "SaveItemsMode()",16)."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveItemsMode= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		document.getElementById('$t-pattern').value='';
		$('#table-{$_GET["table-t"]}').flexReload();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}	
		ifFnExistsCallIt('RefreshSquidGroupTable');		
		
	}
	
	function SaveItemsModeCheck(e){
		if(checkEnter(e)){SaveItemsMode();}
	}
	
	function SaveItemsMode(){
		      var XHR = new XHRConnection();
		      XHR.appendData('item-pattern', document.getElementById('$t-pattern').value);
		      XHR.appendData('item-id', '$item_id');
		      XHR.appendData('ID', '$ID');		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function EditGroup_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqgroups SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}
function item_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqitems SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");	
	
}

function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="webfilters_sqgroups";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	$GroupType=$q->acl_GroupType;
	$GroupType["src"]="{addr}";
	$GroupType["arp"]="{ComputerMacAddress}";
	$GroupType["dstdomain"]="{dstdomain}";
	$GroupType["proxy_auth"]="{members}";
	$GroupType["port"]="{remote_ports}";
	$GroupType["maxconn"]="{max_connections}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("groupid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableGroup('{$ligne['ID']}')");
		$ligne['GroupName']=utf8_encode($ligne['GroupName']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($GroupType[$ligne["GroupType"]]);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['GroupName']}","DeleteSquidAclGroup('{$ligne['ID']}')");
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}'"));
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?AddGroup-js=yes&ID={$ligne['ID']}');\" 
		style='font-size:16px;text-decoration:underline'>{$ligne['GroupName']}</span>",
		"<span style='font-size:16px;'>$GroupTypeText</span>",
		"<span style='font-size:16px;'>{$ligne2['tcount']}</span>",
	
	$disable,$delete)
		);
	}
	
	
	echo json_encode($data);	
}
function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	
	$search='%';
	$table="webfilters_sqitems";
	$page=1;

	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE gpid=$ID $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("itemid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableItem('{$ligne['ID']}')");
		$macname=$q->MAC_TO_NAME($ligne['pattern']);
		$ligne['pattern']=utf8_encode($ligne['pattern']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['pattern']}","DeleteGroupItem('{$ligne['ID']}')");
		$additional_text=null;
		
		if($macname){
			$additional_text="<div style='font-size:10px'>$macname</div>";
		}
		
		if(preg_match("#AD:(.*?):(.+)#", $ligne["pattern"],$re)){
			$dnEnc=$re[2];
			$LDAPID=$re[1];
			$ad=new ActiveDirectory($LDAPID);
			$tty=$ad->ObjectProperty(base64_decode($dnEnc));
			$entries=$ad->search_users_from_group(base64_decode($dnEnc),0);
			$ligne['pattern']="Active Directory:&nbsp;".$tty["cn"]." - ".count($entries)." items";
		}		
			
		
		
		
	$data['rows'][] = array(
		'id' => "item{$ligne['ID']}",
		'cell' => array("<span style='font-size:13px;font-weight:bold'>{$ligne['pattern']}</span>$additional_text",
		"<div style='padding-top:5px'>$disable</div>",
		$delete)
		);
	}
	
	
	echo json_encode($data);	
}


