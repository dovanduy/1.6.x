<?php
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
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){items();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["ruleid"])){rule();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["switch-js"])){switch_js();exit;}
if(isset($_POST["switch"])){switch_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
popup();

function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ruleid-js"];
	if($ID==0){$title="{new_rule}";}else{$title="{rule}:: $ID";}
	$title=$tpl->_ENGINE_parse_body($title);
	$pattern=urlencode($_GET["pattern"]);
	echo "YahooWin('700','$page?ruleid=$ID&t={$_GET["t"]}&pattern=$pattern','$title')";
}
function delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["delete-js"];
	$ask=$tpl->javascript_parse_text("{delete_rule}: $ID ?");
	echo "
	var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
	$('#table-{$_GET["t"]}').flexReload();
	}
	
	
	function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	Save$t();";	
	
}

function delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM UsersAgentsDB WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function switch_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["switch-js"];
	echo "
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;};
		$('#table-{$_GET["t"]}').flexReload();
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('field','{$_GET["field"]}');
		XHR.appendData('switch','$ID');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}

Save$t();";
}

function switch_save(){
	$q=new mysql_squid_builder();
	$sql="SELECT {$_POST["field"]} FROM UsersAgentsDB WHERE ID='{$_POST["switch"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne[$_POST["field"]]==0){$newvalue=1;}
	if($ligne[$_POST["field"]]==1){$newvalue=0;}
	
	$q->QUERY_SQL("UPDATE UsersAgentsDB SET `{$_POST["field"]}`=$newvalue WHERE ID='{$_POST["switch"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($_POST["field"]=="deny"){
		if($newvalue==1){
			$q->QUERY_SQL("UPDATE UsersAgentsDB SET `bypass`=0,
					bypassWebF=0,
					bypassWebC=0
					WHERE ID='{$_POST["switch"]}'");
			if(!$q->ok){echo $q->mysql_error;return;}
		}
	}
	if($_POST["field"]=="bypass"){
		if($newvalue==1){
			$q->QUERY_SQL("UPDATE UsersAgentsDB SET `deny`=0 WHERE ID='{$_POST["switch"]}'");
			if(!$q->ok){echo $q->mysql_error;return;}
		}
		}	
}


function rule(){
	$ID=$_GET["ruleid"];
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($ID)){$ID=0;}
	
	
	$t=time();

	if($ID==0){
		$title="{new_rule}";
		$btname="{add}";
		$ligne["enabled"]=1;

	}else{
		$sql="SELECT * FROM UsersAgentsDB WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			$error="<p class='text-error'>$q->mysql_error.</p>";
		}
		$title="{rule}::$ID";
		$btname="{apply}";
	}
	
	if($_GET["pattern"]<>null){
		$ligne["pattern"]=$_GET["pattern"];
		if($ID==0){
			if(preg_match("#^(.+?)\/#", $ligne["pattern"],$re)){
				$ligne["editor"]=$re[1];
				$ligne["explain"]="Match {$re[1]} UserAgent";
				$ligne["category"]="Personal";
			}
			if(preg_match("#(MacBook|Apple|iPad|iPhone)#", $ligne["pattern"])){$ligne["editor"]="Apple"; }
			if(preg_match("#(Android)#i", $ligne["pattern"])){$ligne["editor"]="Android"; }
			if(preg_match("#(CFNetwork)#i", $ligne["pattern"])){ $ligne["category"]="Smartphones"; }
			if(preg_match("#(Microsoft)#i", $ligne["pattern"])){$ligne["editor"]="Microsoft"; }
		}
		
		
		$ligne["pattern"]="regex:".PatternToRegex($ligne["pattern"]);

	}

	
	$tr[]=Paragraphe_switch_img("{allow}", "{browser_allow_explain}","bypass-$t",$ligne["bypass"],null);
	$tr[]=Paragraphe_switch_img("{no_webfilter}", "{no_webfilter_explain}","bypassWebF-$t",$ligne["bypassWebF"],null);
	$tr[]=Paragraphe_switch_img("{no_cache}", "{no_cache_explain}","bypassWebC-$t",$ligne["bypassWebC"],null);
	$tr[]=Paragraphe_switch_img("{deny}", "{deny_browser_explain}","deny-$t",$ligne["deny"],null);

	$html="
	<div style='font-size:22px;margin-bottom:20px'>$title</div>
	<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{enabled}:</td>
			<td>".Field_checkbox("enabled-$t",1,$ligne["enabled"],"EnabledCheck$t()")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:18px'>{pattern}:</td>
			<td>
			<div class=explain style='font-size:16px'>{browser_pattern_explain}</div>
			<textarea style='margin-top:5px;font-family:Courier New; font-weight:bold;
			width:99%;height:90px;border:5px solid #8E8E8E; overflow:auto;font-size:16px !important' id='pattern-$t'>{$ligne["pattern"]}</textarea></td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{vendor}:</td>
			<td>".Field_text("editor-$t",$ligne["editor"],"font-size:18px;width:500px")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:18px'>{explain}:</td>
			<td>".Field_text("explain-$t",$ligne["explain"],"font-size:18px;width:500px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{category}:</td>
			<td>".Field_text("category-$t",$ligne["category"],"font-size:18px;width:500px")."</td>
		</tr>	
		<tr><td colspan=2 style='padding-top:20px;padding-left:20px'>". CompileTr2($tr)."</td></tr>
		
		
		
		<tr><td colspan=2 style='text-align:right'><hr>". button("$btname","Save$t();","26px")."</td></tr>
	</table>
	</div>
	<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;};
		var ID=$ID;
		if(ID==0){YahooWinHide();}
		$('#table-{$_GET["t"]}').flexReload();
	}	

	function EnabledCheck$t(){
		var enabled=0;
		if(document.getElementById('enabled-$t').checked){ enabled=1;}
		document.getElementById('pattern-$t').disabled=true;
		document.getElementById('editor-$t').disabled=true;
		document.getElementById('explain-$t').disabled=true;
		document.getElementById('category-$t').disabled=true;
		if(enabled==1){
		document.getElementById('pattern-$t').disabled=false;
		document.getElementById('editor-$t').disabled=false;
		document.getElementById('explain-$t').disabled=false;
		document.getElementById('category-$t').disabled=false;		
		}
	
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		var enabled=0;
		if(document.getElementById('enabled-$t').checked){ enabled=1;}
		
		XHR.appendData('pattern',encodeURIComponent(document.getElementById('pattern-$t').value));
		XHR.appendData('editor',encodeURIComponent(document.getElementById('editor-$t').value));
		XHR.appendData('explain',encodeURIComponent(document.getElementById('explain-$t').value));
		XHR.appendData('category',encodeURIComponent(document.getElementById('category-$t').value));
		XHR.appendData('bypass',document.getElementById('bypass-$t').value);
		XHR.appendData('bypassWebF',document.getElementById('bypassWebF-$t').value);
		XHR.appendData('bypassWebC',document.getElementById('bypassWebC-$t').value);
		XHR.appendData('deny',document.getElementById('deny-$t').value);
		XHR.appendData('enabled',enabled);
		XHR.appendData('ID','{$_GET["ruleid"]}');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		}
				
EnabledCheck$t();				
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function PatternToRegex($str){
	$p=new useragents();
	return $p->PatternToRegex($str);
	
}

function rule_save(){
	
	$ID=$_POST["ID"];
	
	
	if($_POST["deny"]==1){
		$_POST["bypassWebF"]=0;
		$_POST["bypassWebC"]=0;
		$_POST["bypass"]=0;
		
	}
	
	if($_POST["bypass"]==1){
		$_POST["deny"]=0;
	}
		
	
	while (list ($fieldname, $data) = each ($_POST) ){
		$_POST[$fieldname]=url_decode_special_tool($data);
		$_POST[$fieldname]=mysql_escape_string2($_POST[$fieldname]);
	}
	
	if($ID>0){
		$sql="UPDATE UsersAgentsDB SET 
			`pattern`='{$_POST["pattern"]}',
			`editor`='{$_POST["editor"]}',
			`explain`='{$_POST["explain"]}',
			`bypass`='{$_POST["bypass"]}',
			`bypassWebF`='{$_POST["bypassWebF"]}',
			`bypassWebC`='{$_POST["bypassWebC"]}',
			`enabled`='{$_POST["enabled"]}',
			`deny`='{$_POST["deny"]}' WHERE ID=$ID";
		
	}else{
		$sql="INSERT IGNORE INTO `UsersAgentsDB` 
		(`pattern`,`explain`,`editor`,`category`,`bypass`,`deny`,`bypassWebF`,`bypassWebC`,`enabled`)
		VALUES ('{$_POST["pattern"]}','{$_POST["explain"]}','{$_POST["editor"]}','{$_POST["category"]}','{$_POST["bypass"]}',
		'{$_POST["deny"]}','{$_POST["bypassWebF"]}','{$_POST["bypassWebC"]}','{$_POST["enabled"]}')";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n\n$sql";}
	
	
}


function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$category=$tpl->javascript_parse_text("{category}");
	$vendor=$tpl->javascript_parse_text("{vendor}");
	$browsers=$tpl->javascript_parse_text("{browsers}");
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$items=$tpl->javascript_parse_text("{items}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$title=$tpl->javascript_parse_text("{browsers_rules}");
	$whitelist=$tpl->javascript_parse_text("{whitelist}");
	$nowebfilter=$tpl->javascript_parse_text("{bypass_webfilter}");
	$nowcache=$tpl->javascript_parse_text("{no_cache}");
	$deny=$tpl->javascript_parse_text("{deny}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply_params=$tpl->javascript_parse_text("{apply}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$about=$tpl->javascript_parse_text("{about2}");
	$all=$tpl->javascript_parse_text("{all}");
	$browsers_ntlm_explain=$tpl->javascript_parse_text("{browsers_ntlm_explain}",0);
	$t=time();		
	

	
	$buttons="buttons : [
	{name: '$new_rule', bclass: 'add', onpress : Add$t},
	{name: '$about', bclass: 'help', onpress : About$t},
	{separator: true},
	{name: '$apply_params', bclass: 'apply', onpress : SquidBuildNow$t},
	{name: '$whitelist', bclass: 'Statok', onpress :  Whitelist$t},
	{name: '$deny', bclass: 'Err', onpress :  Deny$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
		],	";

	
	$html=$tpl->_ENGINE_parse_body("")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$pattern', name : 'pattern', width : 293, sortable : true, align: 'left'},
		{display: '$vendor', name : 'editor', width : 120, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 120, sortable : true, align: 'left'},
		
		{display: '$enabled', name : 'enabled', width : 75, sortable : true, align: 'center'},
		{display: '$whitelist', name : 'bypass', width : 75, sortable : true, align: 'center'},
		{display: '$nowebfilter', name : 'bypassWebF', width : 75, sortable : true, align: 'center'},
		{display: '$nowcache', name : 'bypassWebC', width : 75, sortable : true, align: 'center'},
		{display: '$deny', name : 'deny', width : 75, sortable : true, align: 'center'},
		{display: '$delete', name : 'delte', width : 75, sortable : false, align: 'center'},
		
		
	],
$buttons
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: '$vendor', name : 'editor'},
		{display: '$category', name : 'category'},
		{display: '$explain', name : 'explain'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});   
});

function About$t(){
	alert('$browsers_ntlm_explain');
}

function Add$t(){
Loadjs('$page?ruleid-js=0&t=$t');
}

function Whitelist$t(){
	$('#table-$t').flexOptions({url: '$page?list=yes&t=$t&whitelist=yes'}).flexReload();
}
function All$t(){
	$('#table-$t').flexOptions({url: '$page?list=yes&t=$t'}).flexReload();
}
function Deny$t(){
	$('#table-$t').flexOptions({url: '$page?list=yes&t=$t&deny=yes'}).flexReload();
}
function SquidBuildNow$t(){
	Loadjs('squid.compile.php');
}
</script>
	";
	echo $html;	

}
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	
	$q=new useragents();
	$q->checkTable();
	
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="UsersAgentsDB";
	$page=1;
	$FORCE=1;
	
	if(isset($_GET["whitelist"])){
		$FORCE="bypass=1";
	}
	if(isset($_GET["deny"])){
		$FORCE="deny=1";
	}	
	

	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	

	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$explain=$ligne["explain"];
		$explain=utf8_encode($explain);
		if($explain<>null){$explain="<div><i>$explain</i></div>";}
		
		$bypass="ok32-grey.png";
		$bypassWebF="ok32-grey.png";
		$bypassWebC="ok32-grey.png";
		$deny="ok32-grey.png";
		$enabled="ok32-grey.png";
		$color="black";
		if($ligne["enabled"]==1){
			$enabled="ok32.png";
			if($ligne["deny"]==1){
				$deny="okdanger32.png";
			}else{
				if($ligne["bypass"]==1){$bypass="ok32.png";}
				if($ligne["bypassWebF"]==1){$bypassWebF="ok32.png";}
				if($ligne["bypassWebC"]==1){$bypassWebC="ok32.png";}
				
				
			}
		}
		
		
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		
		
		$js5="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?switch-js={$ligne["ID"]}&t={$_GET["t"]}&field=enabled');\"
		style='text-decoration:underline;color:$color'>";
		
		$js="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?ruleid-js={$ligne["ID"]}&t={$_GET["t"]}');\" 
		style='text-decoration:underline;color:$color'>";
		
		$js1="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?switch-js={$ligne["ID"]}&t={$_GET["t"]}&field=bypass');\" 
		style='text-decoration:underline;color:$color'>";
		
		$js2="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?switch-js={$ligne["ID"]}&t={$_GET["t"]}&field=bypassWebF');\"
		style='text-decoration:underline;color:$color'>";
		
		$js3="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?switch-js={$ligne["ID"]}&t={$_GET["t"]}&field=bypassWebC');\"
		style='text-decoration:underline;color:$color'>";
		
		$js4="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?switch-js={$ligne["ID"]}&t={$_GET["t"]}&field=deny');\"
		style='text-decoration:underline;color:$color'>";	

		$delete="Loadjs('$MyPage?delete-js={$ligne["ID"]}&t={$_GET["t"]}');";
		
		
	$data['rows'][] = array(
		'id' => md5($ligne["pattern"]),
		'cell' => array(
		"<span style='font-size:14px;;color:$color'>{$ligne["pattern"]}</span>",
		"<span style='font-size:14px;color:$color'>$js{$ligne["editor"]}</a></span>",
		"<span style='font-size:14px;color:$color'>$js{$ligne["category"]}</a></span>",
		"<span style='font-size:14px;'>$js5<img src='img/$enabled'></a></span>",
		"<span style='font-size:14px;'>$js1<img src='img/$bypass'></a></span>",
		"<span style='font-size:14px;'>$js2<img src='img/$bypassWebF'></a></span>",
		"<span style='font-size:14px;'>$js3<img src='img/$bypassWebC'></a></span>",
		"<span style='font-size:14px;'>$js4<img src='img/$deny'></a></span>",
		"<span style='font-size:14px;'>". imgsimple("delete-32.png",null,$delete)."</span>",
		)
		);
	}
	
	
	echo json_encode($data);	
}