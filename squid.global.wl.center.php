<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();	
}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-id"])){rule_popup();exit;}
if(isset($_GET["group-text"])){echo rule_group_text($_GET["group-text"]);exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_rule_js();exit;}
if(isset($_POST["DELETERULE"])){delete_rule();exit;}

start();



function start(){
	$page=CurrentPageName();
	$t=time();
	$html="<div id='$t'></div>
	
	<script>
	
	LoadAjaxRound('$t','$page?table=yes&t=$t');
	
	</script>
	";
	
	echo $html;
	
}

function delete_rule_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#GLOBAL_ACCESS_CENTER{$_GET["t"]}').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DELETERULE','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	
}

function delete_rule(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM global_whitelist WHERE ID={$_POST["DELETERULE"]}");
}

function rule_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-id-js"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_rule}");

	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM global_whitelist WHERE ID=$ID"));
		$title=$tpl->javascript_parse_text("{rule}:{$ligne["ID"]}");

	}
	echo "YahooWin2Hide();YahooWin2('850','$page?rule-id=yes&ID=$ID&t={$_GET["t"]}','$title')";
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$title=$tpl->javascript_parse_text("{GLOBAL_ACCESS_CENTER}");
	$cache_deny=$tpl->javascript_parse_text("{cache}");
	$global_access=$tpl->javascript_parse_text("{global_access}");
	$deny_auth=$tpl->javascript_parse_text("{authentication}");
	$deny_ufdb=$tpl->javascript_parse_text("{webfiltering}");
	$deny_icap=$tpl->javascript_parse_text("{antivirus}");
	$groupid=$tpl->javascript_parse_text("{SquidGroup}");
	$deny_ext=$tpl->javascript_parse_text("{dangerous_extensions}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$nonntlm=$tpl->javascript_parse_text("{whitelist_ntlm}");
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$t=time();
	$apply=$tpl->javascript_parse_text("{compile_rules}");
	

	$sql="CREATE TABLE IF NOT EXISTS `global_whitelist` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`groupid` BIGINT( 100 ) NOT NULL,
				`description` VARCHAR( 128 ) NOT NULL,
				`zDate` datetime NOT NULL,
				`type` smallint( 1 ) NOT NULL DEFAULT '0',
				`enabled` smallint(1) NULL,
				`ruletype` smallint(1) NULL,
				`deny_cache` smallint(1) NULL DEFAULT 0,
				`deny_auth` smallint(1) NULL DEFAULT 0,
				`deny_ufdb` smallint(1) NULL DEFAULT 0,
				`deny_icap` smallint(1) NULL DEFAULT 0,
				`deny_ext` smallint(1) NULL DEFAULT 0,
				`deny_global` smallint(1) NULL DEFAULT 0,
				`frommeta` smallint(1) NULL,
				 KEY `groupid` (`groupid`),
				 KEY `ALL_INDEXES`(deny_cache,deny_auth,deny_ufdb,deny_icap,deny_ext,deny_global),
				 KEY `zDate` (`zDate`),
				 KEY `type` (`type`),
				 KEY `enabled` (`enabled`),
				 KEY `frommeta` (`frommeta`)
			 )  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error_html();}
	
	if($EnableKerbAuth==1){
		$nonntlm_button="{name: '<strong style=font-size:18px>$nonntlm</strong>', bclass: 'Db', onpress : nonntlm$t},";
		
	}
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : SquidBuildNow$t},
	],";	
	
	
	$html="
<table class='GLOBAL_ACCESS_CENTER{$_GET["t"]}' style='display: none' id='GLOBAL_ACCESS_CENTER{$_GET["t"]}' style='width:100%'></table>
<script>
function Load{$_GET["t"]}(){

	$('#GLOBAL_ACCESS_CENTER{$_GET["t"]}').flexigrid({
	url: '$page?search=yes&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '$groupid', name : 'groupid', width :297, sortable : true, align: 'left'},
	{display: '$global_access', name : 'deny_global', width :118, sortable : true, align: 'center'},
	{display: '$cache_deny', name : 'deny_cache', width :118, sortable : true, align: 'center'},
	{display: '$deny_auth', name : 'deny_auth', width :118, sortable : true, align: 'center'},
	{display: '$deny_ufdb', name : 'deny_ufdb', width :118, sortable : true, align: 'center'},
	{display: '$deny_icap', name : 'deny_icap', width :118, sortable : true, align: 'center'},
	{display: '$deny_ext', name : 'deny_ext', width :118, sortable : true, align: 'center'},
	{display: '$enabled', name : 'enabled', width : 118, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	});
}

function NewRule$t(){
	Loadjs('$page?rule-id-js=0&t={$_GET["t"]}');
}

function SquidBuildNow$t(){
	Loadjs('squid.global.wl.center.progress.php');
}

function nonntlm$t(){
	Loadjs('squid.whitelist.ntlm.php');
}


Load{$_GET["t"]}();

</script>
";	
	
	echo $html;
	
}

function rule_save(){
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	while (list ($key, $val) = each ($_POST)){
		
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
		
		
	}
	
	
	if($ID==0){
		$sql="INSERT IGNORE INTO global_whitelist (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
		
	}else{
		$sql="UPDATE global_whitelist SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	}
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("global_whitelist", "groupid")){
		$q->QUERY_SQL("ALTER TABLE `global_whitelist` ADD `groupid` BIGINT( 100 ) NOT NULL,ADD INDEX(`groupid`)");
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $sql."\n".$q->mysql_error;}
	
}

function rule_popup(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$btname="{add}";
	$t=time();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_rule}");
	
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM global_whitelist WHERE ID=$ID"));
		$btname="{apply}";
		$title="{rule}:$ID";
	}
	
if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
if(!is_numeric($ligne["deny_global"])){$ligne["deny_global"]=0;}
if(!is_numeric($ligne["groupid"])){$ligne["groupid"]=0;}





$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=3><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
	<td class=legend style='font-size:20px'>{enabled}:</td>
	<td style='font-size:20px'>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"],"Check$t()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px;vertical-align:top'>{SquidGroup}:</td>
		<td style='font-size:20px'nowrap>
		<strong style='font-size:20px' id='groupid-$t-text'></strong>
		<input type='hidden' name='groupid' id='groupid-$t' value='{$ligne["groupid"]}'></td>
		<td>". button("{browse}...","Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid$t')",16)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{deny_access}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_global-$t", 1,$ligne["deny_global"],"Checkdeny_global()")."</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:20px'>{deny_from_cache}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_cache-$t", 1,$ligne["deny_cache"],"blur()")."</td>
 		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{pass_trough_authentication}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_auth-$t", 1,$ligne["deny_auth"],"blur()")."</td>
 		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:20px'>{pass_trough_thewebfilter_engine}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_ufdb-$t", 1,$ligne["deny_ufdb"],"blur()")."</td>
 		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:20px'>{pass_trough_antivirus_engine}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_icap-$t", 1,$ligne["deny_icap"],"blur()")."</td>
 		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>{deny_dangerous_extentions}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("deny_ext-$t", 1,$ligne["deny_icap"],"blur()")."</td>
 		<td>&nbsp;</td>
	</tr>							
	<tr>
		<td colspan=3 align='right'><hr>". button($btname,"Save$t()",32)."</td>
 </tr>
 </table>
	
 <script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	var ID=$ID;
	if(ID==0){YahooWin2Hide();}
	$('#GLOBAL_ACCESS_CENTER{$_GET["t"]}').flexReload();
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('groupid',document.getElementById('groupid-$t').value);
 	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
 	if(document.getElementById('deny_global-$t').checked){XHR.appendData('deny_global',1);}else{XHR.appendData('deny_global',0);}
 	if(document.getElementById('deny_auth-$t').checked){XHR.appendData('deny_auth',1);}else{XHR.appendData('deny_auth',0);}
 	if(document.getElementById('deny_ufdb-$t').checked){XHR.appendData('deny_ufdb',1);}else{XHR.appendData('deny_ufdb',0);}
 	if(document.getElementById('deny_icap-$t').checked){XHR.appendData('deny_icap',1);}else{XHR.appendData('deny_icap',0);}
 	if(document.getElementById('deny_ext-$t').checked){XHR.appendData('deny_ext',1);}else{XHR.appendData('deny_ext',0);}
 	if(document.getElementById('deny_cache-$t').checked){XHR.appendData('deny_cache',1);}else{XHR.appendData('deny_cache',0);}
 	
 	
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function Checkdeny_global(){
 	Disableall$t();

	
 if(!document.getElementById('deny_global-$t').checked){
	document.getElementById('deny_cache-$t').disabled=false;
	document.getElementById('deny_auth-$t').disabled=false; 
	document.getElementById('deny_ufdb-$t').disabled=false; 
	document.getElementById('deny_icap-$t').disabled=false;
	document.getElementById('deny_ext-$t').disabled=false;
 }
}

function Disableall$t(){
	document.getElementById('deny_cache-$t').disabled=true;
	document.getElementById('deny_auth-$t').disabled=true; 
	document.getElementById('deny_ufdb-$t').disabled=true; 
	document.getElementById('deny_icap-$t').disabled=true;
	document.getElementById('deny_ext-$t').disabled=true;
	
	
}

function LinkAclRuleGpid$t(gpid){
		document.getElementById('groupid-$t').value=gpid;
		getGroupName();	
	}
	
function getGroupName(){
	var groupname=document.getElementById('groupid-$t').value;
	LoadAjaxSilent('groupid-$t-text','$page?group-text='+groupname);
}
	
function Check$t(){
	document.getElementById('deny_global-$t').disabled=true;
	Disableall$t();
	if(document.getElementById('enabled-$t').checked){
		document.getElementById('deny_global-$t').disabled=false;
		Checkdeny_global();
	}

}


	
 Check$t();
 getGroupName();
 </script>
	
	
 ";
	
 echo $tpl->_ENGINE_parse_body($html);
}

function rule_group_text($ID,$edit=null,$link=null){
	$q=new mysql_squid_builder();
	$GroupType="all";
	$GroupName=$link.'{AllSystems}</a>';
	if($ID>0){
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$ID'"));
		$GroupType=$ligne["GroupType"];
		$GroupName=$ligne["GroupName"];
	}
	
	$GroupTypeName=$q->acl_GroupType["$GroupType"];
	$tpl=new templates();
	if($GroupName<>null){$GroupName="{$link}$GroupName</a>$edit<br>";}
	return $tpl->_ENGINE_parse_body("$GroupName<i style='font-size:12px;font-weight:normal'>$GroupTypeName</i>");
	
	
}
	
function search(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$t=$_GET["t"];
		$search='%';
		$table="global_whitelist";
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
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql);
	
		$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		if(mysql_num_rows($results)==0){json_error_show("!!! no data");}
	
		$error_firwall_not_configured=$tpl->javascript_parse_text("{error_firwall_not_configuredisquid}");
		$tpl=new templates();
		$all=$tpl->javascript_parse_text("{all}");
		
		$edit=$tpl->javascript_parse_text("{group_properties}");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-rule-js=yes&ID=$ID&t={$_GET["t"]}',true)");
			$edit_group=null;
			

			if($ligne["groupid"]>0){
				
				$edit_group="&nbsp;&nbsp;&nbsp;&nbsp;&laquo;&nbsp;<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne["groupid"]}&t={$_GET["t"]}');\"
				style='font-size:16px;font-weight:normal;color:$color;text-decoration:underline'>$edit</a>&nbsp;&raquo;&nbsp;";
			}
			


			$EditJs="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$MyPage?rule-id-js=$ID&t={$_GET["t"]}');\"
			style='font-size:18px;font-weight:normal;color:$color;text-decoration:underline'>";
			
			$deny_auth=$ligne["deny_auth"];
			$groupName=rule_group_text($ligne["groupid"],$edit_group,$EditJs);
			$deny_global=$ligne["deny_global"];
			$deny_cache=$ligne["deny_cache"];
			$deny_ufdb=$ligne["deny_ufdb"];
			$deny_icap=$ligne["deny_icap"];
			$deny_ext=$ligne["deny_ext"];
			
			$deny_global_img="ok-32.png";
			$deny_auth_img="ok32-grey.png";
			$deny_ufdb_img="ok32-grey.png";
			$deny_icap_img="ok32-grey.png";
			$deny_ext_img="ok32-grey.png";
			$enabled_img="ok-32.png";
			$deny_cache_img="ok32-grey.png";
			
			
			if($deny_auth==1){$deny_auth_img="ok-32.png";}
			if($deny_cache==1){$deny_cache_img="32-red.png";}
			if($deny_icap==1){$deny_icap_img="32-red.png";}
			if($deny_ext==1){$deny_ext_img="32-red.png";}
			if($deny_ufdb==1){$deny_ufdb_img="ok-32.png";}
			

			
			
			if($deny_global==1){
				$deny_global_img="32-red.png";
				$deny_auth_img="ok32-grey.png";
				$deny_cache_img="ok32-grey.png";
				$deny_ufdb_img="ok32-grey.png";
				$deny_icap_img="ok32-grey.png";
			}
			
			
			if($ligne["enabled"]==0){
				$color="#A0A0A0";
				$enabled_img="32-red.png";
				$deny_global_img="ok32-grey.png";
				$deny_auth_img="ok32-grey.png";
				$deny_cache_img="ok32-grey.png";
				$deny_ufdb_img="ok32-grey.png";
				$deny_icap_img="ok32-grey.png";
			
			}

			

	
	
			$data['rows'][] = array(
 'id' => $ID,
 'cell' => array(
 		"<span style='font-size:18px;font-weight:normal;color:$color'>$groupName</span>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_global_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_cache_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_auth_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_ufdb_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_icap_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$deny_ext_img'></a></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$enabled_img'></center>",
 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
			);
		}
	
		echo json_encode($data);
	}
