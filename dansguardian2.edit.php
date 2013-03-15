<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}



if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
if(isset($_GET["rule"])){rule_edit();exit;}

if(isset($_GET["js-blacklist-list"])){blacklist_js_load();exit;}
if(isset($_GET["blacklist"])){blacklist();exit;}
if(isset($_GET["blacklist-list"])){blacklist_list();exit;}
if(isset($_GET["blacklist-js"])){blacklist_js();exit;}
if(isset($_GET["blacklist-list-group"])){blacklist_list_group();exit;}
if(isset($_POST["EnableDisableCategoryRule"])){blacklist_save();exit;}
if(isset($_GET["phraselist"])){content_filter_tab();exit;}

if(isset($_GET["rewrite_rules"])){rewrite_rules();exit;}
if(isset($_GET["rewrite_rules_list"])){rewrite_rules_list();exit;}
if(isset($_POST["rewrite_rule_enable"])){rewrite_rule_enable();exit;}



if(isset($_GET["whitelist"])){whitelist();exit;}
if(isset($_POST["groupname"])){rule_edit_save();exit;}
if(isset($_POST["blacklist"])){blacklist_save();exit;}
if(isset($_POST["whitelist"])){whitelist_save();exit;}


if(isset($_GET["js-groups"])){groups_js();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["groups-search"])){groups_list();exit;}
if(isset($_GET["choose-group"])){groups_choose();exit;}
if(isset($_GET["choose-groups-search"])){groups_choose_search();exit;}
if(isset($_POST["choose-groupe-save"])){groups_choose_add();exit;}
if(isset($_POST["choose-groupe-del"])){groups_choose_del();exit;}

if(isset($_GET["rule-time"])){rule_time();exit;}
if(isset($_GET["rule-time-list"])){rule_time_list();exit;}
if(isset($_GET["rule-time-ID"])){rule_time_edit();exit;}
if(isset($_POST["TimeSpaceSave"])){rule_time_main_save();exit;}
if(isset($_POST["TimeSpaceRuleSave"])){rule_time_save();exit;}
if(isset($_POST["TimeSpaceDelete"])){rule_time_delete();exit;}

if(isset($_GET["fileblock"])){bannedextensionlist_popup();exit;}
if(isset($_GET["bannedextensionlist-table"])){bannedextensionlist_table();exit;}
if(isset($_GET["bannedextensionlist-list"])){bannedextensionlist_list();exit;}
if(isset($_POST["bannedextensionlist-default"])){bannedextensionlist_default();exit;}
if(isset($_POST["bannedextensionlist-enable"])){bannedextensionlist_enable();exit;}
if(isset($_POST["bannedextensionlist-delete"])){bannedextensionlist_delete();exit;}
if(isset($_GET["bannedextensionlist-add-popup"])){bannedextensionlist_add_popup();exit;}
if(isset($_POST["bannedextensionlist-add"])){bannedextensionlist_add();exit;}
while (list ($num, $ligne) = each ($_REQUEST) ){writelogs("item: $num","MAIN",__FILE__,__LINE__);}
tabs();

function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_explain}");
	$t=$_GET["t"];
	$html="
		
var x_AddNewFreeWeb$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('main_filter_rule_edit');
}

function AddNewFreeWeb$t(){
	var servername=prompt('$addfree');
	if(!servername){return;}
	var XHR = new XHRConnection();
	XHR.appendData('ADD_DNS_ENTRY','');
	XHR.appendData('ForceInstanceZarafaID','');
	XHR.appendData('ForwardTo','');
	XHR.appendData('Forwarder','0');
	XHR.appendData('SAVE_FREEWEB_MAIN','yes');
	XHR.appendData('ServerIP','');
	XHR.appendData('UseDefaultPort','0');
	XHR.appendData('UseReverseProxy','0');
	XHR.appendData('gpid','');
	XHR.appendData('lvm_vg','');
	XHR.appendData('servername',servername);
	XHR.appendData('sslcertificate','');
	XHR.appendData('uid','');
	XHR.appendData('useSSL','0');
	XHR.appendData('force-groupware','UFDBGUARD');
	AnimateDiv('status-$t');
	XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);
}


AddNewFreeWeb$t();

";
echo $html;

}

function blacklist_js_load(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["RULEID"];
	$name="{default}";
	$white=$tpl->_ENGINE_parse_body("{blacklist}");
	if($_GET["modeblk"]==1){$white=$tpl->_ENGINE_parse_body("{whitelist}");}
	if($ID>0){
		$q=new mysql_squid_builder();
		$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$name=utf8_encode($ligne["groupname"]);
	}
	
	$title=$tpl->_ENGINE_parse_body("{rule}&nbsp;|&nbsp;$name&nbsp;|&nbsp;$white");
	
	$url="$page?blacklist=yes&RULEID=$ID&ID=$ID&modeblk={$_GET["modeblk"]}&t={$_GET["t"]}";
	
	$html="YahooWin2('920','$url','$title');
	if(document.getElementById('anim-img-$ID')){document.getElementById('anim-img-$ID').innerHTML='';}
	";
	echo $html;	
	
}

function tabs(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$squid=new squidbee();
	$array["rule"]='{rule}';
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}			
	if($EnableWebProxyStatsAppliance==1){$users->DANSGUARDIAN_INSTALLED=true;$squid->enable_dansguardian=1;}
	
	if($_GET["ID"]>-1){
		$array["blacklist"]='{blacklists}';
		$array["whitelist"]='{whitelist}';
		

		$dansG=false;
		if($users->APP_UFDBGUARD_INSTALLED){
			$array["rewrite_rules"]='{rewrite_rules}';
			$array["fileblock"]='{files_restrictions}';
			$array["domains"]='{domains}';
			$array["ufdbguard-expressionlist"]='{expressions}';
		}
		
		
		if($users->DANSGUARDIAN_INSTALLED){
				if($squid->enable_dansguardian==1){
					
					$array["phraselist"]='{content_filter}';
					$array["dans-time"]='{time}';
					$dansG=true;
				}
				
			}		
		
		
		if(!$dansG){$array["rule-time"]='{time}';}
		if($_GET["ID"]<>0){$array["groups"]='{groups2}';}
	}
	

	$textsize="12.5px";

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="blacklist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?blacklist=yes&RULEID={$_GET["ID"]}&ID={$_GET["ID"]}&modeblk=0&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?blacklist=yes&RULEID={$_GET["ID"]}&ID={$_GET["ID"]}&modeblk=1&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="phraselist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num={$_GET["ID"]}&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="domains"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"ufdbguard.ban-domains.php?$num={$_GET["ID"]}&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="dans-time"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"dansguardian2.timelimit.php?RULEID={$_GET["ID"]}&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="ufdbguard-expressionlist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"ufdbguard.expressions.php?$num={$_GET["ID"]}&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
	
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num={$_GET["ID"]}&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_filter_rule_edit style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_filter_rule_edit').tabs();
			
			
			});
		</script>";		
	
	
}


function content_filter_tab(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$array["phraselist"]='{keywords}';
	$array["expressionslist"]='{urls}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="phraselist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"dansguardian2.weighted.php?RULEID={$_GET["ID"]}&ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="expressionslist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"dansguardian2.expressionslist.php?RULEID={$_GET["ID"]}&ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}		
	
	}

	
	echo "
	<div id=main_content_rule_edittabs style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_content_rule_edittabs').tabs();
			
			
			});
		</script>";	
	
}





function blacklist_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='middle'><div class=explain>{dansguardian2_blacklist_explain}</div></td>
		<td valign='middle'>
			<table style='width:99%' class=form>
			<tbody>
			<tr>
			<td class=legend>{search}:</td>
			<td>
			". Field_text("whitelistsearch-$t","*","font-size:16px",null,null,null,false,"SearchBlackCatChk(event)")."</td>
			</tr>
			</tbody>
			</table>
			</td>
	</tr>
	</tbody>
	</table>
	<div style='height:490px;overflow:auto;margin:9px' id='$t'></div>
	
	
	<script>
		function SearchBlackCatChk(e){
			if(checkEnter(e)){SearchBlackCatCh();}
		}
	
		function SearchBlackCatCh(){
			var se=escape(document.getElementById('whitelistsearch-$t').value);
			LoadAjax('$t','$page?blacklist={$_GET["blacklist"]}&ID={$_GET["blacklist"]}&search-whitelist='+se);
		}
		SearchBlackCatCh();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function blacklist_list_group(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();	
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();
	$sql="SELECT master_category FROM webfilters_categories_caches GROUP BY master_category";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$catsz=$ligne["master_category"];
		$butts[$catsz]=$catsz;
	}		
	$t=time();
	$butts[null]="{all}";	
	$field=Field_array_Hash($butts, "CatzByGroup-$t",null,"RefreshBlackListTable$t()",null,0,"font-size:14px");
	$html="<center style='width:95%;' class=form><br><br>$field<br><br></center>
	<script>
		function RefreshBlackListTable$t(){
			var group=escape(document.getElementById('CatzByGroup-$t').value);
			var iditem='{$_GET["iditem"]}';
			var uriplus='';
			var CatzByEnable={$_GET["CatzByEnable"]};
			if(CatzByEnable==1){uriplus='&CatzByEnabled=yes';}
			$('#'+iditem).flexOptions({url: '$page?blacklist-list=yes&RULEID={$_GET["RULEID"]}&modeblk={$_GET["modeblk"]}&group='+group+uriplus+'&TimeID={$_GET["TimeID"]}'}).flexReload();
			YahooSearchUserHide();
		}
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function blacklist(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();	
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}
	
	
	
	$sql="SELECT master_category FROM webfilters_categories_caches GROUP BY master_category";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#does.*?exist#", $q->mysql_error)){
			$q->create_webfilters_categories_caches();
			if(!$q->ok){$create_webfilters_categories_caches="webfilters_categories_caches error while creating the table $q->mysql_error<br>";}
			$results=$q->QUERY_SQL($sql);
			$create_webfilters_categories_caches="$create_webfilters_categories_caches after webfilters_categories_caches created...<br>";
			if(class_exists("dansguardian_rules")){
				$dans=new dansguardian_rules();
				$dans->CategoriesTableCache();
			}
		}
	}
	
	
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$create_webfilters_categories_caches$sql</code>";}	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$catsz=$ligne["master_category"];
		$butts[$catsz]=$catsz;
	}		
	
	$butts[null]="{all}";
	
	$field=Field_array_Hash($butts, "CatzByGroup-$t",null,"RefreshBlackListTable()",null,0,"font-size:10px");
	$onlyEnabled=Field_checkbox("CatzByEnabled-$t", 1,0,"RefreshBlackListTable()");
	$html="
	<div id='blacklist-js-generator-$t'></div>
	
	<script>
		function RefreshBlackListTable(){
			var CatzByEnabled='';
			$('#blacklist-table-1').remove();
			$('#blacklist-table-2').remove();
			LoadAjax('blacklist-js-generator-$t','$page?blacklist-js=yes&t=$t&RULEID={$_GET["RULEID"]}&TimeID={$_GET["TimeID"]}&ID={$_GET["RULEID"]}&modeblk={$_GET["modeblk"]}');
			}
			
	var x_EnableDisableCategoryRule= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	}			
	
	
	function EnableDisableCategoryRule(categorykey,RULEID,modeblk){
		var XHR = new XHRConnection();
		var idname='cats_'+RULEID+'_'+modeblk+'_'+categorykey;
		XHR.appendData('EnableDisableCategoryRule','yes');
		XHR.appendData('categorykey',categorykey);
		XHR.appendData('modeblk',modeblk);
		XHR.appendData('RULEID',RULEID);
		XHR.appendData('TimeID','{$_GET["TimeID"]}');
		if(document.getElementById(idname).checked){
		XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableCategoryRule);	
	}			
			
			
			RefreshBlackListTable();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function blacklist_js(){
	
	$ID=$_GET["ID"];
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
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$group=$_GET["group"];
	if(isset($_GET["CatzByEnabled"])){$CatzByEnabled="&CatzByEnabled=yes";}
	$t=$_GET["modeblk"];
	$d=time();
	
	$html="
	<table class='blacklist-table-$t-$d' style='display: none' id='blacklist-table-$t-$d' style='width:99%'></table>
<script>
var CatzByEnable$t=0;
$(document).ready(function(){
$('#blacklist-table-$t-$d').flexigrid({
	url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group$CatzByEnabled&TimeID={$_GET["TimeID"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
		{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 668, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_category', bclass: 'add', onpress : AddCatz},
	{name: '$OnlyActive', bclass: 'Search', onpress : OnlyActive$t},
	{name: '$All', bclass: 'Search', onpress : OnlyAll$t},
	{name: '$Group', bclass: 'Search', onpress : GroupBy$t},
		],	
	searchitems : [
		{display: '$category', name : 'categorykey'},
		{display: '$description', name : 'description'},
		{display: '$group', name : 'master_category'},
		],
	sortname: 'categorykey',
	sortorder: 'asc',
	usepager: true,
	title: '',
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
	$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group&CatzByEnabled=yes&TimeID={$_GET["TimeID"]}'}).flexReload(); 
}
function OnlyAll$t(){
	CatzByEnable$t=0;
	$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group&TimeID={$_GET["TimeID"]}'}).flexReload();
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

function rewrite_rules(){
	$ID=$_GET["rewrite_rules"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_affect_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_affect_explain}");
	
	
$html="
<div id='tableau-reecriture-regles-affectees' class=explain style='font-size:14px'>$rewrite_rules_affect_explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rewrite_rules_list=yes&ID={$ID}',
	dataType: 'json',
	colModel : [
		{display: '$rulename', name : 'rulename', width : 736, sortable : false, align: 'left'},	
		{display: '$items', name : 'ItemsNumber', width :60, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		],
	
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 878,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_MainRuleRewriteEnable= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}	
		FlexReloadRulesRewrite();
	}
	
	
	function MainRuleRewriteEnable(ID,md5){
		var XHR = new XHRConnection();
		XHR.appendData('rewrite_rule_enable', ID);
		XHR.appendData('ID', $ID);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',x_MainRuleRewriteEnable); 	
	
	}
	

	function FlexReloadRulesRewrite(){
		$('#flexRT$t').flexReload();
	}



</script>

";	
	echo $html;
	
}

function rewrite_rule_enable(){
	
	$ruleid=$_POST["rewrite_rule_enable"];
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}else{
		$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}	
	$RewriteRules=unserialize(base64_decode($ligne["RewriteRules"]));

	if($_POST["enable"]==0){unset($RewriteRules[$ruleid]);}
	if($_POST["enable"]==1){$RewriteRules[$ruleid]=true;}
	$ligne["RewriteRules"]=base64_encode(serialize($RewriteRules));
	
	if($ID==0){
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");	
		$sock->getFrameWork("squid.php?rebuild-filters=yes");
		return;
	}	
	
	$sql="UPDATE webfilter_rules SET RewriteRules='{$ligne["RewriteRules"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");		
	
}

function rewrite_rules_list(){
	
	
	$ID=$_GET["ID"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();

	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}else{
		$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}	
	$RewriteRules=unserialize(base64_decode($ligne["RewriteRules"]));
	
	$search='%';
	$table="webfilters_rewriterules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
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
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	

	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"].$ID);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$enabled=0;
		
		if(isset($RewriteRules[$ligne["ID"]])){
			if($RewriteRules[$ligne["ID"]]){$enabled=1;}
		}
		
		$enable=Field_checkbox($md5,1,$enabled,"MainRuleRewriteEnable('{$ligne["ID"]}','$md5')");	
		$js="Loadjs('$MyPage?rewrite-rule=yes&ID={$ligne["ID"]}');";
		
		
		writelogs("{$ligne["ID"]} => {$ligne["rulename"]}",__FUNCTION__,__FILE__,__LINE__);
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>{$ligne["rulename"]}</span>",
			"<span style='font-size:18px'>{$ligne["ItemsCount"]}</span>",$enable )
		);
	}
	
	
echo json_encode($data);		

}


function rule_time(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();		
	$ID=$_GET["ID"];
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}else{
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	$RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
	if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
	$RULESS["none"]="{none}";
	$RULESS[0]="{default}";
	$sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["ID"]==$ID){continue;}
		$RULESS[$ligne["ID"]]=$ligne["groupname"];
		
	}
	
	
	$html="
	<div id='TimeSpaceSaveID'>
	<div class=explain style='font-size:14px'>{ufdbguardTimeSpaceExplain}</div>
	<table class=form style='width:99%'>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{match}:</td>
			<td>". Field_array_Hash($RuleBH, "RuleMatchTime",$TimeSpace["RuleMatchTime"],null,null,0,"font-size:14px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{alternate_rule}:</td>
			<td>". Field_array_Hash($RULESS, "RuleAlternate",$TimeSpace["RuleAlternate"],null,null,0,"font-size:14px")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}", "TimeSpaceSaveMain()")."</td>
		</tr>
	</tbody>
	</table>
	</div>
	
	
	<div id='TimeSpaceRules' style='width:100%;height:250px;overflow:auto'></div>
	
	
	
<script>

	function RefreshTimeSpaceRules(){
		LoadAjax('TimeSpaceRules','$page?rule-time-list=yes&ID=$ID');
	
	}


	var x_TimeSpaceSaveMain= function (obj) {
		var res=obj.responseText;
		RefreshTab('main_filter_rule_edit');
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	}
	
	function TimeSpaceSaveMain(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeSpaceSave', 'yes');
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('RuleMatchTime', document.getElementById('RuleMatchTime').value);
		      XHR.appendData('RuleAlternate', document.getElementById('RuleAlternate').value);
		      AnimateDiv('TimeSpaceSaveID');
		      XHR.sendAndLoad('$page', 'POST',x_TimeSpaceSaveMain);  		
		}		
	RefreshTimeSpaceRules();
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
}




function rule_time_list(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$add=imgtootltip("plus-24.png","{add}","AddTimeRule()");
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
	$addText=$tpl->_ENGINE_parse_body("{add}");
	
	$html="
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th width=99%>{rules}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>	
";
$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");	
while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	$dd=array();
	if(is_array($array["DAYS"])){
		while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]="{{$daysARR[$day]}}";}}
		$daysText=@implode(", ", $dd);
	}
	if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
	if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
	if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
	if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
	$daysText=$daysText." {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]}";
	
	$delete=imgtootltip("delete-32.png","{delete} {rule}:$TIMEID","TimeSpaceDelete('$TIMEID')");
	
		$href="<a href=\"javascript:blur()\" OnClick=\"javascript:YahooWin5(550,'$page?rule-time-ID=yes&TIMEID=$TIMEID&ID=$ID','{rule}:$TIMEID');\" style='font-size:14px;text-decoration:underline'>";
		
		$html=$html."
		<tr class=$classtr>
			<td width=1% align='center'>$href$TIMEID</a></td>
			<td $style width=99% style='font-size:14px'>$href{each} $daysText</a></td>
			<td width=1% >$delete</td>
		</tr>
		";
	}
	
	$html=$html."</table>
	</center>
	<script>
	function AddTimeRule(){
		YahooWin5(550,'$page?rule-time-ID=yes&TIMEID=-1&ID=$ID','$addText');
	}
	
	
	var x_TimeSpaceDelete= function (obj) {
		var res=obj.responseText;
		RefreshTimeSpaceRules();
	}
	
	function TimeSpaceDelete(TIMEID){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeSpaceDelete', 'yes');
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('TIMEID', TIMEID);
		      AnimateDiv('TimeSpaceRules');
		      XHR.sendAndLoad('$page', 'POST',x_TimeSpaceDelete);  		
		}		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function rule_time_main_save(){
	$ID=$_POST["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
		$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
		$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");	
		$sock->getFrameWork("squid.php?rebuild-filters=yes");
		return;
	}
	
	
	$q=new mysql_squid_builder();		
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
	$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
	$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}


function rule_time_edit(){
	include_once('ressources/class.cron.inc');
	$ID=$_GET["ID"];
	$TIMEID=$_GET["TIMEID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();		
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	$days=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
	$cron=new cron_macros();
	$buttonname="{apply}";
	if($TIMEID==-1){$buttonname="{add}";}
	$Config=$TimeSpace["TIMES"][$TIMEID];
	
	while (list ($num, $val) = each ($days) ){
		
		$jsjs[]="if(document.getElementById('day_{$num}').checked){ XHR.appendData('day_{$num}',1);}else{ XHR.appendData('day_{$num}',0);}";
		
		
		$dd=$dd."
		<tr>
		<td width=1%>". Field_checkbox("day_{$num}",1,$Config["DAYS"][$num])."</td>
		<td width=99% class=legend style='font-size:14px'>{{$val}}</td>
		</tr>
		";
		
	}
	
	$html="
	<div id='TimeSpaceRuleSaveID'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td style='width:50%' valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					$dd
				</tbody>
			</table>
		</td>
		<td style='width:50%' valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourBegin}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"BEGINH",$Config["BEGINH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"BEGINM",$Config["BEGINH"],null,null,0,"font-size:14px")."M</td>
					</tr>
					<tr><td colspan=3>&nbsp;</td></tr>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourEnd}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"ENDH",$Config["ENDH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"ENDM",$Config["ENDM"],null,null,0,"font-size:14px")."M</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "TimeSpaceTimes()")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_TimeSpaceTimes= function (obj) {
		var res=obj.responseText;
		RefreshTab('main_filter_rule_edit');
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		YahooWin5Hide();
	}
	
	function TimeSpaceTimes(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeSpaceRuleSave', 'yes');
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('TIMEID', '$TIMEID');
		      ". @implode("\n", $jsjs)."
		      XHR.appendData('BEGINH', document.getElementById('BEGINH').value);
		      XHR.appendData('BEGINM', document.getElementById('BEGINM').value);
		      XHR.appendData('ENDH', document.getElementById('ENDH').value);
		      XHR.appendData('ENDM', document.getElementById('ENDM').value);		      
		      AnimateDiv('TimeSpaceRuleSaveID');
		      XHR.sendAndLoad('$page', 'POST',x_TimeSpaceTimes);  		
		}	

	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}
function rule_time_save(){
	include_once('ressources/class.cron.inc');
	$ID=$_POST["ID"];
	$TIMEID=$_POST["TIMEID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();		
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
	$Config["ENDH"]=$_POST["ENDH"];
	$Config["ENDM"]=$_POST["ENDM"];
	$Config["BEGINH"]=$_POST["BEGINH"];
	$Config["BEGINM"]=$_POST["BEGINM"];
	while (list ($index, $value) = each ($_POST) ){
		
		if(preg_match("#day_([a-z])#", $index,$re)){
			$Config["DAYS"][$re[1]]=$value;
		}
	}
	if($TIMEID==-1){
		$TimeSpace["TIMES"][]=$Config;
	}else{
		$TimeSpace["TIMES"][$TIMEID]=$Config;
	}
	
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
		$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
		$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");	
		$sock->getFrameWork("squid.php?rebuild-filters=yes");
		return;
	}	
	
	
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");		
	
}

function rule_time_delete(){
	$ID=$_POST["ID"];
	$TIMEID=$_POST["TIMEID"];	
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	unset($TimeSpace["TIMES"][$TIMEID]);	
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));	
		$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
		$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");	
		$sock->getFrameWork("squid.php?rebuild-filters=yes");
		return;
	}	
	
	
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");			
}


function blacklist_save(){
	

	$table="webfilter_blks";
	if(!is_numeric($_POST["TimeID"])){$_POST["TimeID"]=0;}
	if($_POST["TimeID"]>0){$table="webfilters_dtimes_blks";$_POST["RULEID"]=$_POST["TimeID"];}
	
	
	$q=new mysql_squid_builder();	
	$sql="SELECT ID FROM $table WHERE category='{$_POST["categorykey"]}' AND modeblk={$_POST["modeblk"]} 
	AND webfilter_id='{$_POST["RULEID"]}'";
	
	
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	
	
	
	
	if($ligne["ID"]==0){
		$sql="INSERT IGNORE INTO $table (webfilter_id,category,modeblk) 
		VALUES ('{$_POST["RULEID"]}','{$_POST["categorykey"]}','{$_POST["modeblk"]}')";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;} 
	}
	
	if($ligne["ID"]>0){
		$q->QUERY_SQL("DELETE FROM $table WHERE ID={$ligne["ID"]}");
		writelogs("DELETE FROM $table WHERE ID={$ligne["ID"]}",__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){echo $q->mysql_error;return;} 
	}
	
	$order="COMPILEDB:{$_POST["categorykey"]}";
	$md5=md5($order);
	$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");	

	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");
	
}




function rule_edit(){
	$ID=$_GET["rule"];
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();	
	$DISABLE_DANS_FIELDS=0;
	$groupmode[0]="{banned}";
	$groupmode[1]="{filtered}";
	$groupmode[2]="{exception}";
	$button_name="{apply}";
	$t=$_GET["t"];
	if($ID<0){$button_name="{add}";}
	$sock=new sockets();
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}	
	
	$ENDOFRULES[null]="{select}";
	$ENDOFRULES["any"]="{ufdb_any}";
	$ENDOFRULES["none"]="{ufdb_none}";
	
	
	
	if($ID>-1){
		$sql="SELECT * FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
	}else{
		
		if(!isset($ligne["endofrule"])){$ligne["endofrule"]="any";}
	}
	
	$users=new usersMenus();
	if($users->DANSGUARDIAN_INSTALLED){
		$squid=new squidbee();
		if($users->enable_dansguardian==0){$users->DANSGUARDIAN_INSTALLED=false;}
	}
	
	if(!$users->DANSGUARDIAN_INSTALLED){$DISABLE_DANS_FIELDS=1;}
	
	
	
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$ligne["groupname"]="default";
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["searchtermlimit"])){$ligne["searchtermlimit"]=30;}
	if(!is_numeric($ligne["bypass"])){$ligne["bypass"]=0;}
	if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}
	if(!is_numeric($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
	if(!is_numeric($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}
	if(!is_numeric($ligne["GoogleSafeSearch"])){$ligne["GoogleSafeSearch"]=0;}
	if(!is_numeric($ligne["UseExternalWebPage"])){$ligne["UseExternalWebPage"]=0;}
	if(!isset($ligne["freeweb"])){$ligne["freeweb"]=null;}
	
	
	if($EnableGoogleSafeSearch==0){
	$EnableGoogleSafeSearchField="
		<tr>
			<td class=legend style='font-size:14px'>{EnableGoogleSafeSearch}:</td>
			<td>". Field_checkbox("EnableGoogleSafeSearch-$t",1,$ligne["GoogleSafeSearch"])."</td>
			<td width=1%>&nbsp;</td>
		</tr>";	
	}
	
	
	$bypass=Paragraphe32("bypass", "bypass_minitext", "Loadjs('dansguardian2.bypass.php?ID=$ID')", "folder-32-routing-secure.png");
	
	if($ligne["groupmode"]==0){
		$stop=Paragraphe32("navigation_banned", "navigation_banned_text", "", "stop-32.png");
	}
	
	$qq=new mysql();
	$sql="SELECT servername FROM freeweb WHERE groupware='UFDBGUARD'";
	$results = $qq->QUERY_SQL($sql,"artica_backup");
	$freewebs[null]="{select}";
	while ($ligneq = mysql_fetch_assoc($results)) {
		$freewebs[$ligneq["servername"]]=$ligneq["servername"];
	}
	
	if($ligne["freeweb"]<>null){
		$freeweburi="
				<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname={$ligne["freeweb"]}');\" 
				style=\"font-size:14px;text-decoration:underline\">http://{$ligne["freeweb"]}</a></td>
				</tr>";
	}
	
	if(!$users->DANSGUARDIAN_INSTALLED){$bypass=null;}
	if($ID<0){$bypass=null;}
	
/*
 * 		<td class=legend>{sslmitm}:</td>
		<td>". Field_checkbox("sslmitm",1,$ligne["sslmitm"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend>{sslcertcheck}:</td>
		<td>". Field_checkbox("sslcertcheck",1,$ligne["sslcertcheck"])."</td>
		<td>&nbsp;</td>
	</tr>	
 */	
	
	$specificDansguardian="<tr>
		<td class=legend>{blockdownloads}:</td>
		<td>". Field_checkbox("blockdownloads",1,$ligne["blockdownloads"])."</td>
		<td>". help_icon("{blockdownloads_text}")."</td>
	</tr>			
	<tr>
		<td class=legend>{deepurlanalysis}:</td>
		<td>". Field_checkbox("deepurlanalysis",1,$ligne["deepurlanalysis"])."</td>
		<td>". help_icon("{deepurlanalysis_text}")."</td>
	</tr>
	<tr>
		
	<tr>
		<td class=legend>{naughtynesslimit}:</td>
		<td>". Field_text("naughtynesslimit",$ligne["naughtynesslimit"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{naughtynesslimit_text}")."</td>
	</tr>		
	<tr>
		<td class=legend>{searchtermlimit2}:</td>
		<td>". Field_text("searchtermlimit",$ligne["searchtermlimit"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{searchtermlimit_explain2}")."</td>
	</tr>
	<tr>
		<td class=legend>{embeddedurlweight}:</td>
		<td>". Field_text("embeddedurlweight",$ligne["embeddedurlweight"],"font-size:14px;width:60px")."</td>
		<td>". help_icon("{embeddedurlweight_text}")."</td>
	</tr>";
	
	
	$html="
	<div id='dansguardinMainRuleDiv'>
	<input type='hidden' id='bypass' value='{$ligne["bypass"]}'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td>$bypass</td>
		<td>$stop</td>
	</tr>
	</tbody>
	</table>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>$ID)&nbsp;{rule_name}:</td>
		<td style='font-size:16px'>". Field_text("groupname",$ligne["groupname"],"font-size:16px;")."</td>
		<td style='font-size:16px'>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{enabled}:</td>
		<td style='font-size:16px'>". Field_checkbox("enabled",1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:16px'>{groupmode}:</td>
		<td style='font-size:16px'>". Field_array_Hash($groupmode,"groupmode",$ligne["groupmode"],"style:font-size:16px;")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{finish_rule_by}:</td>
		<td style='font-size:16px'>". Field_array_Hash($ENDOFRULES,"endofrule",$ligne["endofrule"],"style:font-size:16px;")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<td colspan=3><hr></td>
	<tr>
		<td class=legend style='font-size:16px'>{dedicated_website_error_page}:</td>
		<td style='font-size:16px'>". Field_array_Hash($freewebs,"freeweb-$t",$ligne["freeweb"],"style:font-size:16px;")."</td>
		<td>&nbsp;</td>
	</tr>	
	<td colspan=3 align='right'><table style='width:5%'>
				<tr><td width=1%><img src='img/plus-big.png'></td>
				<td width=99% nowrap>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('$page?add-freeweb-js=yes&t=$t');\"
					 		style=\"font-size:14px;text-decoration:underline\">{add_a_web_service}</a>
						</td>
				</tr>
	$freeweburi</table></td>			
				
				
	<tr>
		<td class=legend style='font-size:16px'>{external_uri}:</td>
		<td>". Field_checkbox("UseExternalWebPage",1,$ligne["UseExternalWebPage"],"UseExternalWebPageCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{redirect_url}:</td>
		<td style='font-size:16px'>". Field_text("ExternalWebPage",$ligne["ExternalWebPage"],"font-size:16px;")."</td>
		<td style='font-size:16px'>&nbsp;</td>
	</tr>	
	 
	
	 
	
	
$EnableGoogleSafeSearchField	

	
	<tr>
		<td colspan=3 align='right'><hr>". button($button_name,"SaveDansGUardianMainRule()",18)."</td>
	</tr>
	</tbody>
	</table>

	</div>
	<div style='height:300px'>&nbsp;</div>
	<script>
	
	var x_SaveDansGUardianMainRule= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
		if(ID<0){YahooWin3Hide();}else{RefreshTab('main_filter_rule_edit');}
		$('#flexRT$t').flexReload();
		
	}
	
	function UseExternalWebPageCheck(){
		document.getElementById('ExternalWebPage').disabled=true;
		if(document.getElementById('UseExternalWebPage').checked){
			document.getElementById('ExternalWebPage').disabled=false;
		}
	}
	
		function SaveDansGUardianMainRule(){
		      var XHR = new XHRConnection();
		      XHR.appendData('groupname', document.getElementById('groupname').value);
		      if(document.getElementById('naughtynesslimit')){ XHR.appendData('naughtynesslimit', document.getElementById('naughtynesslimit').value);}
		      if(document.getElementById('searchtermlimit')){ XHR.appendData('searchtermlimit', document.getElementById('searchtermlimit').value);}
		      if(document.getElementById('endofrule')){ XHR.appendData('endofrule', document.getElementById('endofrule').value);}
		      if(document.getElementById('ExternalWebPage')){ XHR.appendData('ExternalWebPage', document.getElementById('ExternalWebPage').value);}
		      if(document.getElementById('freeweb-$t')){ XHR.appendData('freeweb', document.getElementById('freeweb-$t').value);}
		      
		      
		      
		      if(document.getElementById('embeddedurlweight')){ XHR.appendData('embeddedurlweight', document.getElementById('embeddedurlweight').value);}
			  if(document.getElementById('bypass')){ XHR.appendData('bypass', document.getElementById('bypass').value);}
			  if(document.getElementById('groupmode')){ XHR.appendData('groupmode', document.getElementById('groupmode').value);}
		      if(document.getElementById('enabled')){ if(document.getElementById('enabled').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}}
		      if(document.getElementById('blockdownloads')){ if(document.getElementById('blockdownloads').checked){ XHR.appendData('blockdownloads',1);}else{ XHR.appendData('blockdownloads',0);}}
		      if(document.getElementById('deepurlanalysis')){ if(document.getElementById('deepurlanalysis').checked){ XHR.appendData('deepurlanalysis',1);}else{ XHR.appendData('deepurlanalysis',0);}}
  			  if(document.getElementById('EnableGoogleSafeSearch-$t')){
  			  	if(document.getElementById('EnableGoogleSafeSearch-$t').checked){XHR.appendData('GoogleSafeSearch',1);}else{XHR.appendData('GoogleSafeSearch',0);}
  			  }
   			  if(document.getElementById('UseExternalWebPage')){
  			  	if(document.getElementById('UseExternalWebPage').checked){XHR.appendData('UseExternalWebPage',1);}else{XHR.appendData('UseExternalWebPage',0);}
  			  } 			  
  			  
		      
		      
		      
		      XHR.appendData('ID','$ID');
		      AnimateDiv('dansguardinMainRuleDiv');
		      XHR.sendAndLoad('$page', 'POST',x_SaveDansGUardianMainRule);  		
		}
		
		function CheckFields(){
			var DISABLE_DANS_FIELDS=$DISABLE_DANS_FIELDS;
			var ID=$ID;
			if(document.getElementById('naughtynesslimit')){document.getElementById('naughtynesslimit').disabled=true;}
			if(document.getElementById('searchtermlimit')){document.getElementById('searchtermlimit').disabled=true;}
			if(document.getElementById('bypass')){document.getElementById('bypass').disabled=true;;}
			if(document.getElementById('blockdownloads')){document.getElementById('blockdownloads').disabled=true;}
			if(document.getElementById('deepurlanalysis')){
				document.getElementById('deepurlanalysis').disabled=true
				document.getElementById('embeddedurlweight').disabled=true;	
			}
			
			if(DISABLE_DANS_FIELDS==0){
				if(document.getElementById('naughtynesslimit')){document.getElementById('naughtynesslimit').disabled=false;}
				if(document.getElementById('searchtermlimit')){document.getElementById('searchtermlimit').disabled=false;}
				if(document.getElementById('bypass')){document.getElementById('bypass').disabled=false;}
				if(document.getElementById('blockdownloads')){document.getElementById('blockdownloads').disabled=false;}
				if(document.getElementById('deepurlanalysis')){document.getElementById('deepurlanalysis').disabled=false;}
				document.getElementById('groupmode').disabled=false;
				document.getElementById('embeddedurlweight').disabled=false;		
						
			
			}
			if(ID==0){
				document.getElementById('enabled').disabled=true;
				document.getElementById('groupname').disabled=true;
			}

		}
	CheckFields();
	UseExternalWebPageCheck();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function rule_edit_save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$sock=new sockets();
	
	writelogs("Save ruleid `$ID`",__FUNCTION__,__FILE__,__LINE__);
	
	if($ID==0){
		writelogs("Default rule, loading DansGuardianDefaultMainRule",__FUNCTION__,__FILE__,__LINE__);
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}
	unset($_POST["ID"]);
	$build=false;
	
	if($_POST["groupname"]==null){$_POST["groupname"]=time();}
	while (list ($num, $ligne) = each ($_POST) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
		$DEFAULTARRAY[$num]=$ligne;
	}
	
	
	if($ID==0){
		$sock=new sockets();
		writelogs("Default rule, saving DansGuardianDefaultMainRule",__FUNCTION__,__FILE__,__LINE__);
		$sock->SaveConfigFile(base64_encode(serialize($DEFAULTARRAY)), "DansGuardianDefaultMainRule");	
		writelogs("Ask to compile rule...",__FUNCTION__,__FILE__,__LINE__);
		$sock->getFrameWork("webfilter.php?compile-rules=yes");
		return;
	}		
	
	$sql_edit="UPDATE webfilter_rules SET ".@implode(",", $fieldsEDIT)." WHERE ID=$ID";
	$sql_add="INSERT IGNORE INTO webfilter_rules (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";
	
	if($ID<0){$s=$sql_add;$build=true;}else{$s=$sql_edit;}
	$q->QUERY_SQL($s);
	 
	if(!$q->ok){echo $q->mysql_error."\n$s\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
	
}

function groups_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$title=utf8_encode($ligne["groupname"]);
	$html="YahooWin2('920','$page?groups=$ID&ID=$ID&t={$_GET["t"]}','$title');
	if(document.getElementById('anim-img-$ID')){
		document.getElementById('anim-img-$ID').innerHTML='';
	}
	";
	echo $html;
	
}

function groups(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$dansguardian2_rules_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_rules_groups_explain}");
	$unlink=$tpl->_ENGINE_parse_body("{unlink}");
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$link_group=$tpl->_ENGINE_parse_body("{link_group}");
	
	$buttons="
	buttons : [
	{name: '$link_group', bclass: 'add', onpress : DansGuardianAddSavedGroup},
	],";		
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<div class=explain>$dansguardian2_rules_groups_explain</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?groups-search=yes&t=$t&rule-id={$_GET["groups"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 30, sortable : false, align: 'left'},
		{display: '$group', name : 'groupname', width : 679, sortable : true, align: 'left'},	
		{display: '$members', name : 'members', width :69, sortable : false, align: 'center'},
		{display: '$unlink', name : 'delete', width : 48, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$group', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 893,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function DansGuardianAddSavedGroup(){
	YahooWin4('590','$page?choose-group={$_GET["groups"]}&t=$t','$link_group');
}

		var x_UnlinkFilterGroup= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			$('#rowgroup'+rowid).remove();
		}
	
		function UnlinkFilterGroup(ID){
			  rowid=ID;
		      var XHR = new XHRConnection();
		      XHR.appendData('choose-groupe-del', ID);
		      XHR.sendAndLoad('$page', 'POST',x_UnlinkFilterGroup);  		
		}
</script>";
	
echo $tpl->_ENGINE_parse_body($html);

}

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

function groups_list(){
	
	$search=$_POST["query"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();	
	$page=1;
	$t=$_GET["t"];
	
	$sqlCount="SELECT COUNT(*) as tcount,webfilter_assoc_groups.ID,webfilter_assoc_groups.webfilter_id,
	webfilter_group.groupname,
	webfilter_group.description,
	webfilter_group.gpid,
	webfilter_group.localldap,
	webfilter_group.ID as webfilter_group_ID,
	webfilter_group.dn as webfilter_group_dn,
	webfilter_group.enabled 
	FROM webfilter_group,webfilter_assoc_groups WHERE ((webfilter_group.groupname LIKE '$search' AND webfilter_assoc_groups.webfilter_id={$_GET["rule-id"]}) 
	OR (webfilter_group.description LIKE '$search' AND webfilter_assoc_groups.webfilter_id={$_GET["rule-id"]}))
	AND webfilter_assoc_groups.group_id=webfilter_group.ID";	
	
	$COUNLIGNE=mysql_fetch_array($q->QUERY_SQL($sqlCount,"artica_backup"));
	if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	
	$isDynamic=isDynamic($_GET["rule-id"]);
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
	$sql="SELECT webfilter_assoc_groups.ID,webfilter_assoc_groups.webfilter_id,
	webfilter_group.groupname,
	webfilter_group.description,
	webfilter_group.gpid,
	webfilter_group.localldap,
	webfilter_group.ID as webfilter_group_ID,
	webfilter_group.dn as webfilter_group_dn,
	webfilter_group.enabled 
	FROM webfilter_group,webfilter_assoc_groups WHERE ((webfilter_group.groupname LIKE '$search' AND webfilter_assoc_groups.webfilter_id={$_GET["rule-id"]}) 
	OR (webfilter_group.description LIKE '$search' AND webfilter_assoc_groups.webfilter_id={$_GET["rule-id"]}))
	AND webfilter_assoc_groups.group_id=webfilter_group.ID	
	ORDER BY webfilter_group.groupname {$_POST["sortorder"]} $limitSql";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}
	writelogs("search:$search webfilter_id={$_GET["rule-id"]} countline:{$COUNLIGNE["tcount"]}",__FUNCTION__,__FILE__,__LINE__);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $COUNLIGNE["tcount"];
	$data['rows'] = array();
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$textExplainGroup=null;
		$KEY_ID_GROUP=$ligne["webfilter_group_ID"];
		$delete="<a href=\"javascript:blur();\" OnClick=\"javascript:UnlinkFilterGroup('{$ligne["ID"]}')\"><img src='img/delete-24.png' style='border:0px'></a>";
		$color="black";
		$CountDeMembers="??";
		$Textdynamic=null;
		
		if($ligne["localldap"]==0){
			$gp=new groups($ligne["gpid"]);
			$groupadd_text="(".$gp->groupName.")";
			$CountDeMembers=count($gp->members);
		}
		
		if($ligne["localldap"]==1){
			$sql="SELECT COUNT(ID) as tcount FROM webfilter_members WHERE `groupid`='$KEY_ID_GROUP'";
			$COUNLIGNE=mysql_fetch_array($q->QUERY_SQL($sql));
			$CountDeMembers=$COUNLIGNE["tcount"];
			if($isDynamic){
				$color="#9A9A9A";
				$Textdynamic=$tpl->_ENGINE_parse_body("<div style='font-weight:bold;color:#E40F0F'>{ufdb_no_dynamic_group}</div>");
			}
			
		}
		
		
		if($ligne["enabled"]==0){$color="#9A9A9A";}
		if($ligne["localldap"]==2){
		if(preg_match("#AD:(.*?):(.+)#", $ligne["webfilter_group_dn"],$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$ad=new ActiveDirectory($LDAPID);
				if($ad->UseDynamicGroupsAcls==1){
					if(preg_match("#^CN=(.+?),.*#i", base64_decode($dnEnc),$re)){
					$groupname=_ActiveDirectoryToName($re[1]);
					$CountDeMembers='-';
					$Debug="&nbsp;<a href=\"javascript:Loadjs('dansguardian2.explodeadgroup.php?rule-id={$_GET["rule-id"]}');\"
					style=\"text-decoration:underline\">{dump_group}</a>";
					}
				}else{
					$tty=$ad->ObjectProperty(base64_decode($dnEnc));
					$CountDeMembers=$tty["MEMBERS"];
				}	

				$description=htmlentities($tty["description"]);
				$description=str_replace("'", "`", $description);	
				if(trim($ligne["description"])==null){$ligne["description"]=$description;}
			}
		}	
		
		$imgGP="win7groups-32.png";
		if($ligne["localldap"]<2){$imgGP="group-32.png";}
		
		if($Textdynamic<>null){$imgGP="warning-panneau-32.png";}
		
		
		$TextGroupType=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
		
		
		$jsSelect="YahooWin4('712','dansguardian2.edit.group.php?ID=$KEY_ID_GROUP&t=$t&YahooWin=4','$KEY_ID_GROUP::{$ligne['groupname']}');";
		
		$data['rows'][] = array(
				'id' => "group{$ligne["ID"]}",
				'cell' => array(
				"<img src='img/$imgGP'>",
				"<a href=\"javascript:blur();\" 
				OnClick=\"javascript:$jsSelect\" 
				style='font-size:16px;text-decoration:underline;color:$color'>{$ligne['groupname']}</span></a>$groupadd_text$Textdynamic<div style='font-size:10px'>$textExplainGroup<i>&laquo;{$ligne["description"]} <i>$TextGroupType</i>&raquo;</i>$Debug",
				"<span style='font-size:16px;color:$color'>$CountDeMembers</span>",$delete
				)
		);		
		
	
	}
	
	echo json_encode($data);
	
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

function groups_choose(){
	$ID=$_GET["choose-group"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$group=$tpl->_ENGINE_parse_body("{group}");
	$link_group=$tpl->_ENGINE_parse_body("{link_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$tt=$_GET["t"];
	$t=time();

	$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddNewDansGuardianGroup$t},
	],";	
	
	
	$html="
	<div style='margin-right:-10px;margin-left:-10px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?choose-groups-search=yes&t=$t&ID=$ID',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width :31, sortable : false, align: 'left'},
		{display: '$group', name : 'groupname', width : 440, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'icon', width :31, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$group', name : 'groupname'}
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 585,
	height: 340,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
		var x_SaveDansGUardianMainRule= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
			$('#flexRT$tt').flexReload();	
			
			
		}
	
		function DansGuardianAddSavedGroup(ID){
		      var XHR = new XHRConnection();
		      XHR.appendData('choose-groupe-save', ID);
		      XHR.appendData('ruleid', '$ID');
		      XHR.sendAndLoad('$page', 'POST',x_SaveDansGUardianMainRule);  		
		}
		
		function AddNewDansGuardianGroup$t(){
			DansGuardianEditGroup$t(-1)
		
		}
		
		function DansGuardianEditGroup$t(ID,rname){
			LoadWinORG('712','dansguardian2.edit.group.php?ID='+ID+'&t=$t&tt=$tt&yahoo=LoadWinORG','$group::$ID::');
		
		}		
		
</script>


";
echo $html;
	
}
	
function groups_choose_search(){
	
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
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((groupname LIKE '$search') OR (description LIKE '$search'))";
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
	if(!$q->ok){json_error_show($error,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
		
		$localldap[0]="{ldap_group}";
		$localldap[1]="{virtual_group}";
		$localldap[2]="{active_directory_group}";	

	
while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		if($ligne["enabled"]==0){$color="#CCCCCC";}
		//win7groups-32.png
		$add=imgsimple("arrow-right-24.png","{select} {group}","DansGuardianAddSavedGroup({$ligne["ID"]})");
		$imgGP="win7groups-32.png";
		if($ligne["localldap"]<2){$imgGP="group-32.png";}
		$typeexplain=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
	
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<img src='img/$imgGP'>",
			"<span style='font-size:16px'>{$ligne["groupname"]}&nbsp;<span style='font-size:13px'>&laquo;$typeexplain&raquo;</span></div>
			<div style='font-size:10px'><i>{$ligne["description"]}</i></div>",
			$add
			)
		);
	}
	
	
	echo json_encode($data);		
	
}

function groups_choose_add(){
	$ruleid=$_POST["ruleid"];
	$groupid=$_POST["choose-groupe-save"];
	$md5=md5("$ruleid$groupid");
	
	$sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5',$ruleid,$groupid)";
	$q=new mysql_squid_builder();
	$q->CheckTables(null);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}

function groups_choose_del(){
	$ID=$_POST["choose-groupe-del"];
	$sql="DELETE FROM webfilter_assoc_groups WHERE ID='$ID'";
	$q=new mysql_squid_builder();
	$q->CheckTables(null);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}

	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function bannedextensionlist_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
$html="
<div id='bannedextensionlist-div'></div>
<div class=explain>{bannedextensionlist_explain}</div>
<script>
	function RefreshBannedextensionlist(){
		$('#bannedextensionlist-table').remove();
		LoadAjax('bannedextensionlist-div','$page?bannedextensionlist-table=yes&ID={$_GET["ID"]}');
	}
	
	RefreshBannedextensionlist();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}
function bannedextensionlist_table(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$extension=$tpl->_ENGINE_parse_body("{extension}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$files_restrictions=$tpl->_ENGINE_parse_body("{files_restrictions}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$TB_WIDTH=878;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	
	$html="
	<table class='bannedextensionlist-table' style='display: none' id='bannedextensionlist-table' style='width:99%'></table>
<script>
var bannedextensionlist_KEY='';
$(document).ready(function(){
$('#bannedextensionlist-table').flexigrid({
	url: '$page?bannedextensionlist-list=yes&RULEID=$ID',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
		{display: '$extension', name : 'ext', width : 80, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 629, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 30, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : AddNewExtension},
		{separator: true},
		{name: '$addDef', bclass: 'addDef', onpress : bannedextensionlist_AddDefault},
		
		],	
	searchitems : [
		{display: '$extension', name : 'ext'},
		{display: '$description', name : 'description'},
		],
	sortname: 'ext',
	sortorder: 'asc',
	usepager: true,
	title: '$files_restrictions',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 278,
	singleSelect: true
	
	});   
});
function AddNewExtension() {
	YahooWin6('400','$page?bannedextensionlist-add-popup=yes&ID=$ID','$add');
	
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

</script>	";
echo $tpl->_ENGINE_parse_body($html);
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
} 

function blacklist_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	if(!is_numeric($_GET["TimeID"])){$_GET["TimeID"]=0;}
	$users=new usersMenus();
	$text_license=null;
	if(!$users->CORP_LICENSE){
		$text_license=$tpl->_ENGINE_parse_body("({category_no_license_explain})");
	}
	$search='%';
	$table="webfilters_categories_caches";
	$tableProd="webfilter_blks";
	
	if($_GET["TimeID"]>0){$tableProd="webfilters_dtimes_blks";}
	
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
	$sql="SELECT `category` FROM $tableProd WHERE `webfilter_id`={$_GET["RULEID"]} AND modeblk={$_GET["modeblk"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$cats[$ligne["category"]]=true;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
			$DBTXT[]="<a href=\"javascript:blurt();\" OnClick=\"javascript:Loadjs('squid.categories.php?category=".urlencode($ligne['categorykey'])."')\" 
			style='font-size:11px;font-weight:bold;text-decoration:underline'>$category_table_elements</a> $items";
			$DBTXT[]="<a href=\"javascript:blurt();\" OnClick=\"javascript:Loadjs('ufdbguard.compile.category.php?category=".urlencode($ligne['categorykey'])."')\" 
			style='font-size:11px;font-weight:bold;text-decoration:underline'>$compile</a>";
			
		}

		
		$ligneTLS=mysql_fetch_array($q->QUERY_SQL("SELECT websitesnum FROM univtlse1fr WHERE category='{$ligne['categorykey']}'"));
		$category_table_elements_tlse=$ligneTLS["websitesnum"];
		if($category_table_elements_tlse>0){
			$category_table_elements_tlse=FormatNumber($category_table_elements_tlse);
			$DBTXT[]="$category_table_elements_tlse Toulouse University $items";
		}		
		
		$catz=new mysql_catz();
		$category_table_elements_artica=$catz->COUNT_ROWS($category_table);
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
		
		$disable=Field_checkbox("cats_{$_GET['RULEID']}_{$_GET['modeblk']}_{$ligne['categorykey']}", 1,$val,"EnableDisableCategoryRule('{$ligne['categorykey']}','{$_GET["RULEID"]}','{$_GET["modeblk"]}')");
		
		
	$data['rows'][] = array(
		'id' => $ligne['categorykey'],
		'cell' => array("<img src='$img'>","$js{$ligne['categorykey']}</a>", $ligne['description']."<br>$database_items",$disable)
		);
	}
	
	
echo json_encode($data);	
	
	
}

function bannedextensionlist_add_popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td class=legend style='font-size:14px'>{extension}:</strong></td>
	<td>" . Field_text('extension_pattern',null,'width:60px;font-size:14px',null,null,null,false,"ext_enter(event)")."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:14px'>{description}:</strong></td>
	<td>" . Field_text('extension_description',null,'width:99%;font-size:14px',null,null,null,false,"ext_enter(event)")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{add_extension}","bannedextension_listadd()",14)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
function bannedextension_listadd(){
      var XHR = new XHRConnection();
      XHR.appendData('ID','$ID');
      XHR.appendData('bannedextensionlist-add',document.getElementById('extension_pattern').value);
      XHR.appendData('description',document.getElementById('extension_description').value);
      AnimateDiv('$t');   
      XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_AddDefault);        
      }  

     function ext_enter(e){
     	if(checkEnter(e)){bannedextension_listadd();}
     }
	
</script>	
	
	";
	
echo $tpl->_ENGINE_parse_body($html);
}

function bannedextensionlist_add(){
	$extension=strtolower(trim($_POST["bannedextensionlist-add"]));
	$description=addslashes($_POST["description"]);
	$ID=$_POST["ID"];
	if(substr($extension,0,1)=='.'){$extension=substr($extension, 1,strlen($extension));}
	$extension=str_replace("*",'',$extension);
	$md5=md5("$ID$extension");
	$q=new mysql_squid_builder();
	$sql="INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES(1,'$md5','$extension','$description',$ID);";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}

function bannedextensionlist_enable(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilter_bannedexts SET enabled={$_POST["bannedextensionlist-enable"]} 
	WHERE zmd5='{$_POST["bannedextensionlist-key"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function bannedextensionlist_delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM webfilter_bannedexts WHERE zmd5='{$_POST["bannedextensionlist-delete"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function bannedextensionlist_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilter_bannedexts";
	$page=1;
	$ORDER="ORDER BY ext ASC";
	$FORCE_FILTER=" AND ruleid={$_GET["RULEID"]}";
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$img="img/ext/def_small.gif";
		if(file_exists("img/ext/{$ligne['ext']}_small.gif")){$img="img/ext/{$ligne['ext']}_small.gif";}
		$disable=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ext']}","bannedextensionlist_delete('{$ligne["zmd5"]}')");
		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array("<img src='$img'>",
		"<strong style='font-family:Courier New;font-size:16px;padding-left:5px'>{$ligne['ext']}</strong>",
		"<span style='font-size:16px'>{$ligne['description']}</span>","<div style='margin-top:5px'>$disable</div>",$delete)
		);
	}
	
	
echo json_encode($data);	
	
	
}




function bannedextensionlist_default(){
$f["ade"]="Microsoft Access project extension";
$f["adp"]="Microsoft Access project";
$f["asx"]="Windows Media Audio / Video";
$f["bas"]="Microsoft Visual Basic class module";
$f["bat"]="Batch file";
$f["cab"]="Windows setup file";
$f["chm"]="Compiled HTML Help file";
$f["cmd"]="Microsoft Windows NT Command script";
$f["com"]="Microsoft MS-DOS program";
$f["cpl"]="Control Panel extension";
$f["crt"]="Security certificate ";
$f["dll"]="Windows system file";
$f["exe"]="Program";
$f["hlp"]="Help file";
$f["ini"]="Windows system file";
$f["hta"]="HTML program";
$f["inf"]="Setup Information";
$f["ins"]="Internet Naming Service";
$f["isp"]="Internet Communication settings";
$f["lnk"]="Windows Shortcut";
$f["mda"]="Microsoft Access add-in program ";
$f["mdb"]="Microsoft Access program";
$f["mde"]="Microsoft Access MDE database";
$f["mdt"]="Microsoft Access workgroup information ";
$f["mdw"]="Microsoft Access workgroup information ";
$f["mdz"]="Microsoft Access wizard program ";
$f["msc"]="Microsoft Common Console document";
$f["msi"]="Microsoft Windows Installer package";
$f["msp"]="Microsoft Windows Installer patch";
$f["mst"]="Microsoft Visual Test source files";
$f["pcd"]="Photo CD image, Microsoft Visual compiled script";
$f["pif"]="Shortcut to MS-DOS program";
$f["prf"]="Microsoft Outlook profile settings";
$f["reg"]="Windows registry entries";
$f["scf"]="Windows Explorer command";
$f["scr"]="Screen saver";
$f["sct"]="Windows Script Component";
$f["sh "]="Shell script";
$f["shs"]="Shell Scrap object";
$f["shb"]="Shell Scrap object";
$f["sys"]="Windows system file";
$f["url"]="Internet shortcut";
$f["vb"]="VBScript file";
$f["vbe"]="VBScript Encoded script file";
$f["vbs"]="VBScript file";
$f["vxd"]="Windows system file";
$f["wsc"]="Windows Script Component";
$f["wsf"]="Windows Script file";
$f["wsh"]="Windows Script Host Settings file";
$f["otf"]="Font file - can be used to instant reboot 2k and xp";
$f["ops"]="Office XP settings ";
$f["doc"]="Word document";
$f["xls"]="Excel document";
$f["pps"]="PowerPoint document";
$f["gz "]="Gziped file";
$f["tar"]="Tape ARchive file";
$f["zip"]="Windows compressed file";
$f["tgz"]="Unix compressed file";
$f["bz2"]="Unix compressed file";
$f["cdr"]="Mac disk image";
$f["dmg"]="Mac disk image";
$f["smi"]="Mac self mounting disk image";
$f["sit"]="Mac compressed file";
$f["sea"]="Mac compressed file, self extracting";
$f["bin"]="Mac binary compressed file";
$f["hqx"]="Mac binhex encoded file";
$f["rar"]="Similar to zip";
$f["mp3"]="Music file";
$f["mpeg"]="Movie file";
$f["mpg"]="Movie file";
$f["avi"]="Movie file";
$f["asf"]="this can also exploit a security hole allowing virus infection";
$f["iso"]="CD ISO image";
$f["ogg"]="Music file";
$f["wmf"]="Movie file";
$f["bin"]="CD ISO image";
$f["cue"]="CD ISO image";	

$prefix="INSERT IGNORE INTO webfilter_bannedexts (`zmd5`,`ext`,`description`,`ruleid`) VALUES ";
while (list ($num, $val) = each ($f) ){
	$md5=md5($num.$_POST["bannedextensionlist-default"]);
	$tt[]="('$md5','$num','$val','{$_POST["bannedextensionlist-default"]}')";
}
$sql=$prefix.@implode(",", $tt);
$q=new mysql_squid_builder();
$q->QUERY_SQL($sql);
if(!$q->ok){echo $q->mysql_error;return;}
$sock=new sockets();
$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

