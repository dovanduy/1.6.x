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

if(isset($_GET["rule-tabs"])){rule_tab();exit;}
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

function rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	
	
	$array["acl-rule-settings"]='{settings}';
	$array["acl-items"]='{groups2}';
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'>
				<a href=\"squid.ssl.rules.groups.php?aclid=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
					
		}
		
		if($num=="acl-rule-settings"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'>
					<a href=\"$page?rule-id=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");
	
		}
	
	
		echo build_artica_tabs($html, "main_ssl_rule_zoom_$ID");
	
	
}

function delete_rule_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
echo "
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	
	if( document.getElementById('GLOBAL_SSL_CENTER_ID') ){
		$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
		return;
	}
	
	$('#GLOBAL_SSL_CENTER{$_GET["t"]}').flexReload();
	
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DELETERULE','{$_GET["ID"]}');
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	
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
		echo "YahooWin2Hide();YahooWin2('850','$page?rule-tabs=yes&ID=$ID&t={$_GET["t"]}','$title')";
		return;
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
	$title=$tpl->javascript_parse_text("{ssl_rules}");
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
	$options=$tpl->javascript_parse_text("{options}");
	$t=time();
	$apply=$tpl->javascript_parse_text("{compile_rules}");
	$uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_ssl}");

	$sql="CREATE TABLE IF NOT EXISTS `ssl_rules` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`description` VARCHAR( 128 ) NOT NULL,
				`zDate` datetime NOT NULL,
				`crypt` smallint( 1 ) NOT NULL DEFAULT '0',
				`trust` smallint( 1 ) NOT NULL DEFAULT '0',
				`enabled` smallint(1) NULL,
				`ruletype` smallint(1) NULL,
				`frommeta` smallint(1) NULL,
				`zOrder` smallint(5) NULL,
				 KEY `zDate` (`zDate`),
				 KEY `crypt` (`crypt`),
				 KEY `trust` (`trust`),
				 KEY `enabled` (`enabled`),
			 	 KEY `zOrder` (`zOrder`),
				 KEY `frommeta` (`frommeta`)
			 )  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error_html();}
	
	if(!$q->FIELD_EXISTS("ssl_rules", "trust")){
		$q->QUERY_SQL("ALTER TABLE `ssl_rules`
		ADD `trust` smallint(1) NOT NULL  DEFAULT 0,
		ADD INDEX ( `trust` )");
	}
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : SquidBuildNow$t},
	{name: '<strong style=font-size:18px>$options</strong>', bclass: 'Settings', onpress : SSLOptions$t},
	],";	
	
	
	$html="
<input type='hidden' id='GLOBAL_SSL_CENTER_ID' value='GLOBAL_SSL_CENTER{$_GET["t"]}'>
<table class='GLOBAL_SSL_CENTER{$_GET["t"]}' style='display: none' id='GLOBAL_SSL_CENTER{$_GET["t"]}' style='width:100%'></table>
<script>
function Load{$_GET["t"]}(){

	$('#GLOBAL_SSL_CENTER{$_GET["t"]}').flexigrid({
	url: '$page?search=yes&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '$groupid', name : 'crypt', width :989, sortable : true, align: 'left'},
	{display: '$uncrypt_ssl', name : 'none', width :118, sortable : false, align: 'center'},
	{display: '$enabled', name : 'enabled', width : 118, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons

	sortname: 'zOrder',
	sortorder: 'asc',
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
	Loadjs('squid.compile.php');
}

function SSLOptions$t(){
	Loadjs('squid.ssl.center.php?js=yes');
}


Load{$_GET["t"]}();

</script>
";	
	
	echo $html;
	
}

function rule_save(){
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$_POST["description"]=mysql_escape_string2(url_decode_special_tool($_POST["description"]));
	
	while (list ($key, $val) = each ($_POST)){
		
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
		
		
	}
	
	
	if($ID==0){
		$sql="INSERT IGNORE INTO ssl_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
		
	}else{
		$sql="UPDATE ssl_rules SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function delete_rule(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM sslrules_sqacllinks WHERE aclid='{$_POST["DELETERULE"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM ssl_rules WHERE ID='{$_POST["DELETERULE"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
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
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM ssl_rules WHERE ID=$ID"));
		$btname="{apply}";
		$title="{rule}:{$ligne["description"]}";
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["deny_global"])){$ligne["deny_global"]=0;}
	if(!is_numeric($ligne["groupid"])){$ligne["groupid"]=0;}





$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=2><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{rulename}:</td>
		<td style='font-size:20px'>". Field_text("description-$t", $ligne["description"],"font-size:20px;width:350px")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:20px'>{enabled}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"],"Check$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{uncrypt_ssl}","{uncrypt_ssl_explain}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("crypt-$t", 1,$ligne["crypt"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{trust_ssl}","{trust_ssl_explain}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("trust-$t", 1,$ligne["trust"])."</td>
	</tr>				
	<tr style='height:50px'>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
 </tr>
 </table>
	
 <script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	var ID=$ID;
	if(ID==0){YahooWin2Hide();}
	if( document.getElementById('GLOBAL_SSL_CENTER_ID') ){
		
		$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
		return;
	}
	
	$('#GLOBAL_SSL_CENTER{$_GET["t"]}').flexReload();
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	
	XHR.appendData('description',encodeURIComponent(document.getElementById('description-$t').value));
 	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
 	if(document.getElementById('crypt-$t').checked){XHR.appendData('crypt',1);}else{XHR.appendData('crypt',0);}
 	if(document.getElementById('trust-$t').checked){XHR.appendData('trust',1);}else{XHR.appendData('trust',0);}
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function LinkAclRuleGpid$t(gpid){
		document.getElementById('groupid-$t').value=gpid;
		getGroupName();	
	}
	
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
		$table="ssl_rules";
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
		$uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_ssl}");
		$pass_ssl=$tpl->javascript_parse_text("{pass_connect_ssl}");
		$error_firwall_not_configured=$tpl->javascript_parse_text("{error_firwall_not_configuredisquid}");
		$trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
		$tpl=new templates();
		$all=$tpl->javascript_parse_text("{all}");
		$and_text=$tpl->javascript_parse_text("{and}");
		
		$edit=$tpl->javascript_parse_text("{edit}");
		$squid_acls_groups=new squid_acls_groups();
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			$explain=null;
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-rule-js=yes&ID=$ID&t={$_GET["t"]}',true)");
			$edit_group=null;
			$crypt=$ligne["crypt"];
			$rulename=utf8_encode($ligne["description"]);
			$uncrypt_ssl_text=$pass_ssl;
			$ssl_img="ok32-grey.png";
			$enabled_img="ok-32.png";
			$TTEXT=array();
			if($crypt==1){$uncrypt_ssl_text=$uncrypt_ssl;$ssl_img="ok-32.png";}

			if($ligne["enabled"]==0){
				$color="#A0A0A0";
				$enabled_img="ok32-grey.png";
				$ssl_img="ok32-grey.png";
			}

			$EditJs="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$MyPage?rule-id-js=$ID&t={$_GET["t"]}');\"
			style='font-size:18px;font-weight:normal;color:$color;text-decoration:underline'>";
			
			$TTEXT[]=$uncrypt_ssl_text;
			if($ligne["trust"]==1){
				$TTEXT[]=$trust_ssl;
			}
			
			
			$objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,$color,"sslrules_sqacllinks",16);
			
			if($objects>0){
				$explain=$tpl->_ENGINE_parse_body("{for_objects} ". @implode(" <br>{and} ", $objects)."<br>{then} $EditJs".@implode($and_text, $TTEXT)."</a>");
			}
				
			$data['rows'][] = array(
			 'id' => $ID,
			 'cell' => array(
			 		"<span style='font-size:18px;font-weight:normal;color:$color'>$EditJs$rulename</a>:<br><span style='font-size:16px !important'>$explain</span></span>",
			 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$ssl_img'></a></center>",
			 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$enabled_img'></center>",
			 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
						);
		}
	
		echo json_encode($data);
	}
