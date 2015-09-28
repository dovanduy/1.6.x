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
	
	if(isset($_GET["profiles-list"])){profiles_list();exit;}
	if(isset($_GET["profile-js"])){profile_js();exit;}
	if(isset($_GET["profile-tabs"])){profile_tabs();exit;}
	if(isset($_GET["profile-popup"])){profile_popup();exit;}
	if(isset($_GET["delete-profile-js"])){profile_delete_js();exit;}
	if(isset($_POST["rulename"])){profile_save();exit;}
	if(isset($_POST["delete-profile"])){profile_delete();exit;}
	if(isset($_GET["enable-profile-js"])){profile_enable_js();exit;}
	if(isset($_POST["enable-profile"])){profile_enable();exit;}
	if(isset($_GET["profile-category"])){category_table();exit;}
	if(isset($_GET["category-list"])){category_list();exit;}
	if(isset($_GET["category-enable-js"])){category_enable_js();exit;}
	if(isset($_POST["category-enable"])){category_enable();exit;}
	
profiles_table();	
function profiles_table(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$new_profile=$tpl->javascript_parse_text("{new_profile}");
	$profile=$tpl->javascript_parse_text("{profile}");
	$service_name=$tpl->javascript_parse_text("{description}");
	$blacklist=$tpl->javascript_parse_text("{blacklist}");
	$whitelist=$tpl->javascript_parse_text("{whitelist}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$title=$tpl->javascript_parse_text("{webfiltering} {profiles}");
	$t=time();

	$buttons="buttons : [
	{name: '$new_profile', bclass: 'add', onpress : Add$t}
	
	],";

	// Table cicap_profiles;
	$html="
	<input type='hidden' id='table_icap_profiles' value='flexRT$t'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?profiles-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$profile', name : 'rulename', width :408, sortable : true, align: 'left'},
	{display: '$blacklist', name : 'blacklist', width : 95, sortable : true, align: 'center'},
	{display: '$whitelist', name : 'whitelist', width : 95, sortable : true, align: 'center'},
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
	Loadjs('$page?profile-js=yes&ID=-1&t=$t');
}
	
function Enable$t(){

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

function profile_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{new_profile}");
	if($ID>-1){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM cicap_profiles WHERE ID='$ID'"));
		$title=utf8_encode($ligne["rulename"]);
	}
	
	echo "YahooWin2('945','$page?profile-tabs=yes&ID=$ID&t={$_GET["t"]}','$title');";
	
}

function profile_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$ID=$_GET["delete-profile-js"];
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{new_profile}");
	$profile=$tpl->javascript_parse_text("{profile}");
	$remove=$tpl->javascript_parse_text("{remove}");
	if($ID>-1){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM cicap_profiles WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["rulename"]);
	}	
	$t=time();
	echo "
			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	
}

function start$t(){
	if(! confirm('$remove $profile $title ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-profile','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);			
}

start$t();
";
	
}

function category_enable_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$ID=$_GET["delete-profile-js"];
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	echo "
		
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	
	if(document.getElementById('table_icap_profiles')){
		$('#'+document.getElementById('table_icap_profiles').value).flexReload();
	}
	
	$('#blacklist{$_GET["t"]}{$_GET["bltype"]}').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
}
	
function start$t(){
	var XHR = new XHRConnection();
	XHR.appendData('category-enable','{$_GET["category-enable-js"]}');
	XHR.appendData('mainid','{$_GET["mainid"]}');
	XHR.appendData('bltype','{$_GET["bltype"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
start$t();
	";
}

function category_enable(){
	
	$category=$_POST["category-enable"];
	$mainid=$_POST["mainid"];
	$bltype=$_POST["bltype"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM cicap_profiles_blks WHERE mainid='$mainid' AND bltype='$bltype' AND `category`='$category'"));
	
	if(  ( is_numeric($ligne["ID"]) ) OR ($ligne["ID"]>0)  ){
		$q->QUERY_SQL("DELETE FROM cicap_profiles_blks WHERE ID='{$ligne["ID"]}'");
		if(!$q->ok){echo $q->mysql_error;return;}
		category_calculate($mainid);
		return;
	}
	
	$sql="INSERT INTO `cicap_profiles_blks` ( `mainid`,`bltype`,`category`) VALUES ('$mainid','$bltype','$category')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	category_calculate($mainid);

	
	
	
}

function category_calculate($mainid){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount,mainid,bltype FROM cicap_profiles_blks GROUP BY mainid,bltype
			HAVING mainid='$mainid' AND bltype='0'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE cicap_profiles SET `blacklist`='{$ligne["tcount"]}' WHERE ID=$mainid");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount2,mainid,bltype FROM cicap_profiles_blks GROUP BY mainid,bltype
			HAVING mainid='$mainid' AND bltype='1'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE cicap_profiles SET `whitelist`='{$ligne["tcount2"]}' WHERE ID=$mainid");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function profile_enable_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["enable-profile-js"];
	$t=time();
	echo "
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
}
	
function start$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enable-profile','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
start$t();
	";
	
	
}
function profile_enable(){
	$enabled=0;
	$ID=$_POST["enable-profile"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM cicap_profiles WHERE ID='$ID'"));
	if($ligne["enabled"]==0){$enabled=1;}
	$sql="UPDATE cicap_profiles SET `enabled`='$enabled' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}


function profile_tabs(){
	$tpl=new templates();
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$md5=md5($ID);
	$title=$tpl->javascript_parse_text("{new_profile}");
	if($ID>-1){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM cicap_profiles WHERE ID='$ID'"));
		$title=utf8_encode($ligne["rulename"]);
	}
	
	$array["profile-popup"]=$title;
	if($ID>-1){
		$array["profile-category=yes&bltype=0&mainid=$ID&none"]='{blacklist}';
		$array["profile-category=yes&bltype=1&mainid=$ID&none"]='{whitelist}';
	}
	
	
	//$array["logs"]='{icap_logs}';
	$fontsize="16";
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&ID=$ID&t={$_GET["t"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "cicap_profile_$md5");	
	
	
}

function profile_popup(){
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=time();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();	
	$bt_name="{add}";
	$rulename=$tpl->javascript_parse_text("{new_profile}");
	if($ID>-1){
		$bt_name="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM cicap_profiles WHERE ID='$ID'"));
		$rulename=utf8_encode($ligne["rulename"]);
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
$html="<div style='font-size:26px;margin-bottom:16px'>$rulename</div>
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{profile}:</td>
		<td>". Field_text("rulename-$t",$ligne["rulename"],"font-size:22px;font-weight:bold")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",$ligne["enabled"],1,"enabledCheck$t()")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("$bt_name","Save$t()",26)."</td>
	</tr>
</table>
</div>
<script>
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	YahooWin2Hide();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('ID','$ID');
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function enabledCheck$t(){
	document.getElementById('rulename-$t').disabled=true;
	if(!document.getElementById('enabled-$t').checked){return;}
	document.getElementById('rulename-$t').disabled=false;
}
enabledCheck$t();
</script>
	";
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function profile_save(){
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));
	$ID=$_POST["ID"];
	if($ID<0){
		$sql="INSERT INTO cicap_profiles (rulename,enabled) VALUES ('{$_POST["rulename"]}','{$_POST["enabled"]}')";
		
	}else{
		$sql="UPDATE cicap_profiles SET `rulename`='{$_POST["rulename"]}',`enabled`='{$_POST["enabled"]}'
		WHERE ID=$ID";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
}

function profile_delete(){
	$q=new mysql_squid_builder();
	
	
	$q->QUERY_SQL("DELETE FROM cicap_profiles_blks WHERE mainid='{$_POST["delete-profile"]}'");
	if(!$q->ok){echo $q->mysql_error."\n\n";return;}
	
	$q->QUERY_SQL("DELETE FROM cicap_profiles WHERE ID='{$_POST["delete-profile"]}'");
	if(!$q->ok){echo $q->mysql_error."\n\n";return;}	
	
}


function profiles_list(){
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
function category_table(){

	$ID=$_GET["mainid"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$category=$tpl->_ENGINE_parse_body("{extension}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$group=$tpl->_ENGINE_parse_body("{group}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$OnlyActive=$tpl->_ENGINE_parse_body("{OnlyActive}");
	$Group=$tpl->_ENGINE_parse_body("{group}");
	$All=$tpl->_ENGINE_parse_body("{all}");
	$TB_WIDTH=897;
	
	$group=$_GET["group"];
	if(isset($_GET["CatzByEnabled"])){$CatzByEnabled="&CatzByEnabled=yes";}
	$t=$_GET["t"];
	$d=time();
	

	$sql="CREATE TABLE IF NOT EXISTS `cicap_profiles_blks` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    mainid INT(3) NOT NULL,
				  	bltype smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `mainid` (`mainid`),
				  KEY `category` (`category`),
				  KEY `bltype` (`bltype`)
				)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error_html();
		return;
	}
	
	


	$description_size=639;

	$buttons="	buttons : [
	{name: '$new_category', bclass: 'add', onpress : AddCatz},
	{name: '$OnlyActive', bclass: 'Search', onpress : OnlyActive$t},
	{name: '$All', bclass: 'Search', onpress : OnlyAll$t},
	{name: '$Group', bclass: 'Search', onpress : GroupBy$t},
	],";
	
	$buttons=null;
	if($_GET["bltype"]==1){
	$title=$tpl->javascript_parse_text("{categories}: {whitelist}");
	}else{
		$title=$tpl->javascript_parse_text("{categories}: {blacklist}");
	}

	if(is_numeric($_GET["table-size"])){$TB_WIDTH=$_GET["table-size"];}
	if(is_numeric($_GET["group-size"])){$description_size=$_GET["group-size"];}

	$html="
	<table class='blacklist$t{$_GET["bltype"]}' style='display: none' id='blacklist$t{$_GET["bltype"]}' style='width:99%'></table>
	<script>
	var CatzByEnable$t=0;
	$(document).ready(function(){
	$('#blacklist$t{$_GET["bltype"]}').flexigrid({
	url: '$page?category-list=yes&mainid=$ID&bltype={$_GET["bltype"]}&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
	{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
	{display: '$description', name : 'description', width : $description_size, sortable : false, align: 'left'},
	{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},

	],
	$buttons
	searchitems : [
	{display: '$category', name : 'categorykey'},
	{display: '$description', name : 'description'},
	{display: '$group', name : 'master_category'},
	],
	sortname: 'categorykey',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 350,
	singleSelect: true

});
});
function ChooseGroup(group) {
alert(group);

}

function GroupBy$t(){
YahooSearchUser(300,'$page?blacklist-list-group=yes&iditem=blacklist-table-$t-$d&RULEID=$ID&modeblk={$_GET["modeblk"]}&TimeID={$_GET["TimeID"]}&CatzByEnable='+CatzByEnable$t,'$Group');
}

function OnlyActive$t(){
CatzByEnable$t=1;
$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group&CatzByEnabled=yes&TimeID={$_GET["TimeID"]}'}).flexReload(); ExecuteByClassName('SearchFunction');
}
function OnlyAll$t(){
CatzByEnable$t=0;
$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group&TimeID={$_GET["TimeID"]}'}).flexReload(); ExecuteByClassName('SearchFunction');
}

var x_bannedextensionlist_AddDefault=function(obj){
var results=obj.responseText;
if(results.length>3){alert(results);}
YahooWin6Hide();
RefreshBannedextensionlist();
}

function bannedextensionlist_AddDefault(){
var XHR = new XHRConnection();
XHR.appendData('bannedextensionlist-default','$ID');
AnimateDiv('annedextensionlist-div');
XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_AddDefault);

}

var x_bannedextensionlist_enable=function(obj){
var results=obj.responseText;
if(results.length>3){alert(results);RefreshBannedextensionlist();}
}

function bannedextensionlist_enable(md5){
var XHR = new XHRConnection();
XHR.appendData('bannedextensionlist-key',md5);
if(document.getElementById('disable_'+md5).checked){XHR.appendData('bannedextensionlist-enable','1');}else{XHR.appendData('bannedextensionlist-enable','0');}
XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_enable);
}

var x_bannedextensionlist_delete=function(obj){
var results=obj.responseText;
if(results.length>3){alert(results);return;}
$('#row'+bannedextensionlist_KEY).remove();
}

function bannedextensionlist_delete(md5){
bannedextensionlist_KEY=md5;
var XHR = new XHRConnection();
XHR.appendData('bannedextensionlist-delete',md5);
XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_delete);
}

function AddCatz(){
Loadjs('dansguardian2.databases.php?add-perso-cat-js=yes');
}

</script>	";
echo $tpl->_ENGINE_parse_body($html);
}

function category_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$users=new usersMenus();
	$text_license=null;
	if(!$users->CORP_LICENSE){
		$text_license=$tpl->_ENGINE_parse_body("({category_no_license_explain})");
	}
	$search='%';
	$table="webfilters_categories_caches";
	$tableProd="cicap_profiles_blks";
	
	

	$page=1;
	$ORDER="ORDER BY categorykey ASC";
	$FORCE_FILTER=null;
	if(trim($_GET["group"])<>null){
		$FORCE_FILTER=" AND master_category='{$_GET["group"]}'";
	}
	if(isset($_GET["CatzByEnabled"])){
		$OnlyEnabled=true;
	}


	$count_webfilters_categories_caches=$q->COUNT_ROWS("webfilters_categories_caches");
	writelogs("webfilters_categories_caches $count_webfilters_categories_caches rows",__FUNCTION__,__FILE__,__LINE__);
	if($count_webfilters_categories_caches==0){
		$ss=new dansguardian_rules();
		$ss->CategoriesTableCache();
	}

	if(!$q->TABLE_EXISTS($tableProd)){$q->CheckTables();}
	$sql="SELECT `category` FROM $tableProd WHERE `mainid`={$_GET["mainid"]} AND bltype={$_GET["bltype"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$cats[$ligne["category"]]=true;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$searchstring=string_to_flexquery();

	if($searchstring<>null){

		$sql="SELECT COUNT(*) as TCOUNT FROM `webfilters_categories_caches` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
		$total = $ligne["TCOUNT"];
		writelogs("$sql = $total rows",__FUNCTION__,__FILE__,__LINE__);

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `webfilters_categories_caches` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
		$total = $ligne["TCOUNT"];
	}

	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `webfilters_categories_caches` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}

	$items=$tpl->_ENGINE_parse_body("{items}");
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$catz=new mysql_catz();




	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["picture"]==null){$ligne["picture"]="20-categories-personnal.png";}
		$category_table="category_".$q->category_transform_name($ligne['categorykey']);
		$category_table_elements=$q->COUNT_ROWS($category_table);
		$DBTXT=array();
		$database_items=null;
		if($category_table_elements>0){
			$category_table_elements=FormatNumber($category_table_elements);
			$DBTXT[]="<a href=\"javascript:blurt();\" OnClick=\"javascript:Loadjs('squid.categories.php?category=".urlencode($ligne['categorykey'])."',true)\"
			style='font-size:11px;font-weight:bold;text-decoration:underline'>$category_table_elements</a> $items";
			$DBTXT[]="<a href=\"javascript:blurt();\" OnClick=\"javascript:Loadjs('ufdbguard.compile.category.php?category=".urlencode($ligne['categorykey'])."',true)\"
			style='font-size:11px;font-weight:bold;text-decoration:underline'>$compile</a>";
				
		}


		$ligneTLS=mysql_fetch_array($q->QUERY_SQL("SELECT websitesnum FROM univtlse1fr WHERE category='{$ligne['categorykey']}'"));
		$category_table_elements_tlse=$ligneTLS["websitesnum"];
		if($category_table_elements_tlse>0){
			$category_table_elements_tlse=FormatNumber($category_table_elements_tlse);
			$DBTXT[]="$category_table_elements_tlse Toulouse University $items";
		}

		$catz=new mysql_catz();
		
		if($category_table_elements_artica>0){
			$category_table_elements_artica=FormatNumber($category_table_elements_artica);
			$DBTXT[]="$category_table_elements_artica Artica $items <i style='font-size:10px;font-weight:normal'>$text_license</i>";
		}



		if(count($DBTXT)>0){
			$database_items="<span style='font-size:11px;font-weight:bold'>".@implode("&nbsp;|&nbsp;", $DBTXT)."</span>";
		}

		$img="img/{$ligne["picture"]}";
		$val=0;
		if($cats[$ligne['categorykey']]){$val=1;}
		if($OnlyEnabled){if($val==0){continue;}}

		$disable=Field_checkbox("cats_{$_GET['RULEID']}_{$_GET['bltype']}_{$ligne['categorykey']}", 1,$val,"Loadjs('$MyPage?category-enable-js={$ligne['categorykey']}&mainid={$_GET["mainid"]}&bltype={$_GET["bltype"]}')");
		$ligne['description']=utf8_encode($ligne['description']);

		$data['rows'][] = array(
				'id' => $ligne['categorykey'],
				'cell' => array(
				"<img src='$img'>",
				"$js{$ligne['categorykey']}</a>", 
				$ligne['description']."<br>
				$database_items",
				$disable)
		);
	}


	echo json_encode($data);


}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}