<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){header("location:miniadm.index.php");}

if(isset($_GET["category-js"])){category_js();exit;}
if(isset($_GET["category-tabs"])){category_tabs();exit;}
if(isset($_GET["category-settings"])){category_settings();exit;}
if(isset($_POST["category-to-add"])){category_save();exit;}
if(isset($_GET["category-search"])){category_search();exit;}

if(isset($_GET["category-items"])){category_items();exit;}
if(isset($_GET["category-items-search"])){category_items_search();exit;}
if(isset($_POST["category-items-delete"])){category_items_delete();exit;}


if(isset($_GET["category-urls"])){category_urls();exit;}
if(isset($_GET["category-urls-search"])){category_urls_search();exit;}
if(isset($_POST["category-urls-delete"])){category_urls_delete();exit;}



if(isset($_POST["category-delete"])){category_delete();exit;}
page();

function category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=725;
	$t=$_GET["t"];
	if(!isset($_GET["cat"])){$_GET["cat"]=null;}
	$catname=trim($_GET["cat"]);
	$suffix=suffix();
	$title=$tpl->_ENGINE_parse_body("{add}::{personal_category}");
		
	if($catname<>null){$title=$tpl->_ENGINE_parse_body("{$_GET["cat"]}::{personal_category}");$widownsize=750;}
	$html="YahooWin5('850','$page?category-tabs=yes$suffix','$title');";
	echo $html;
}

function suffix(){
	$cat=$_GET["cat"];
	return "&cat=".urlencode($cat)."&t=".$_GET["t"];
}

function category_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$suffix=suffix();
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?category-settings=yes$suffix";
	$array["{websites}"]="$page?category-items=yes$suffix";
	$array["{urls}"]="$page?category-urls=yes$suffix";
	
	echo $boot->build_tab($array);	
}

function category_settings(){
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$lock=false;
	if($_GET["cat"]<>null){
		$lock=true;
		$sql="SELECT category_description,master_category FROM personal_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	
	$groups=$dans->LoadBlackListesGroups();
	$groups[null]="{select}";

	$boot=new boostrap_form();
	if(!$lock){
		$boot->set_field("category-to-add", "{category}", null,array("ENCODE"=>true));
		$boot->set_button("{add}");
		$boot->set_CloseYahoo("YahooWin5");
		
	}else{
		$boot->set_formtitle("{$_GET["cat"]}");
		$boot->set_hidden("category-to-add", $_GET["cat"]);
		$boot->set_button("{apply}");
	}
	
	$boot->set_textarea("category_text", "{description}", $ligne["category_description"],array("ENCODE"=>true));
	$boot->set_list("group", "{group}", $groups,$ligne["master_category"]);
	$boot->set_field("CatzByGroupA", "{group} ({add})", null);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}

function category_save(){
	include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
	$ldap=new clladp();
	$dans=new dansguardian_rules();
	$_POST["category-to-add"]=url_decode_special_tool($_POST["category-to-add"]);
	
	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	if($_POST["category-to-add"]=="security"){$_POST["category-to-add"]="security2";}
	
	if($_POST["CatzByGroupA"]<>null){$_POST["group"]=$_POST["CatzByGroupA"];}
	
	
	$_POST["CatzByGroupL"]=mysql_escape_string2($_POST["CatzByGroupL"]);
	$_POST["category_text"]=mysql_escape_string2($_POST["category_text"]);
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM personal_categories WHERE category='".mysql_escape_string2($_POST["category-to-add"])."'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]<>null){
		$sql="UPDATE personal_categories
			SET category_description='{$_POST["category_text"]}',
			master_category='{$_POST["group"]}'
			WHERE category='{$_POST["category-to-add"]}'";
	}else{
		$_POST["category-to-add"]=strtolower($ldap->StripSpecialsChars($_POST["category-to-add"]));
		if(isset($dans->array_blacksites[$_POST["category-to-add"]])){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{$_POST["category-to-add"]}:: {category_already_exists}");
			return;
		}
		
		$sql="INSERT IGNORE INTO personal_categories (category,category_description,master_category)
		VALUES ('{$_POST["category-to-add"]}','{$_POST["category_text"]}','{$_POST["group"]}');";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$q->CreateCategoryTable($_POST["category-to-add"]);
	$sql="TRUNCATE TABLE webfilters_categories_caches";
	$dans->CategoriesTableCache();
	$dans->CleanCategoryCaches();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?clean-catz-cache=yes");
	$sock->getFrameWork("squid.php?export-web-categories=yes");	
}


function page(){
	//personal_categories
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_category}","Loadjs('$page?category-js=yes')",16);
	$button_edit=null;
	
	
	$compile_rules=button("{compile_rules}","Loadjs('dansguardian2.compile.php');;",16);
	$EXPLAIN["BUTTONS"][]=$button;
	$EXPLAIN["BUTTONS"][]=$compile_rules;
	$SearchQuery=$boot->SearchFormGen("category,category_description,master_category","category-search");
	echo $tpl->_ENGINE_parse_body($SearchQuery);
}

function category_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$action_delete_rule=$tpl->javascript_parse_text("{delete}");
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$searchstring=string_to_flexquery("category-search");
	$sql="SELECT * FROM personal_categories WHERE 1 $searchstring ORDER BY category";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error");}
	$styleTD="style='font-size:16px;font-weight:bold'";
	$t=time();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$id=md5(serialize($ligne));
		$category=$ligne["category"];
		$category_table="category_$category";
		$rows=$q->COUNT_ROWS($category_table);
		$rows=FormatNumber($rows);
		$link=$boot->trswitch("Loadjs('$page?category-js=yes&cat={$ligne["category"]}')");
		$ligne["category_description"]=utf8_encode($ligne["category_description"]);
		$delete=imgsimple("delete-32.png",null,"Delete$t('{$ligne["category"]}','$id')");
		
		$tr[]="
		<tr id='$id'>
			<td $styleTD $link width=1% nowrap >{$ligne["category"]}</td>
			<td $styleTD $link width=1% nowrap >{$ligne["master_category"]}</td>
			<td $styleTD $link width=99% align=center>{$ligne["category_description"]}</td>
			<td $styleTD width=1% align=center><span style='font-weight:bold;font-size:18px'>$rows</td>
			<td width=35px align='center' nowrap>$delete</td>
		</tr>";		
		
		
	}
	$table=$tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
				<th>{category}</th>
				<th>{groups2}</th>
				<th>{description}</th>
				<th>{rows}</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)."			
</tbody></table>
<script>
var memid$t='';
			
var xDelete$t= function (obj) {
	var res=obj.responseText;
	if (res.length>2){
		alert(res);
		return;
	}
	$('#'+memid$t).remove();
	
}	

function Delete$t(item,id){
	if(item.length==0){alert('??? no category to delete');return;}
	if(!confirm('$action_delete_rule '+item)){return;}
	memid$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('category',item);
	XHR.appendData('category-delete',item);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);	
}	
</script>";	
	
	
	$html=$table;
	echo $html;
	
}
function category_items(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_category}","Loadjs('$page?category-js=yes')",16);
	$button_edit=null;
	$suffix=suffix();
	
	$compile_rules=button("{compile_rules}","Loadjs('dansguardian2.compile.php');;",16);
	
	$EXPLAIN["BUTTONS"][]=button("{new_item}","Loadjs('squid.visited.php?add-www=yes&category={$_GET["cat"]}&t=$t')",16);
	
	$global_parameters=button("{global_parameters}","UfdbGuardConfigs()",16);
	$SearchQuery=$boot->SearchFormGen("pattern","category-items-search",$suffix,$EXPLAIN);
	
	$html=$SearchQuery;
	echo $tpl->_ENGINE_parse_body($html);
}

function category_urls(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_category}","Loadjs('$page?category-js=yes')",16);
	$button_edit=null;
	$suffix=suffix();
	
	$compile_rules=button("{compile_rules}","Loadjs('dansguardian2.compile.php');;",16);
	
	$EXPLAIN["BUTTONS"][]=button("{new_item}","Loadjs('squid.categories.urls.php?add-uris-js=yes&category={$_GET["cat"]}&t=$t')",16);
	
	$global_parameters=button("{global_parameters}","UfdbGuardConfigs()",16);
	$SearchQuery=$boot->SearchFormGen("pattern","category-urls-search",$suffix,$EXPLAIN);
	
	$html=$SearchQuery;
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function category_urls_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$action_delete_rule=$tpl->javascript_parse_text("{delete}");
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$searchstring=string_to_flexquery("category-urls-search");
	$table="categoryuris_".$q->category_transform_name($_GET["cat"]);
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY pattern LIMIT 0,550";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error");}
	$styleTD="style='font-size:16px;font-weight:bold'";
	
	$t=time();
	while ($ligne = mysql_fetch_assoc($results)) {
	
	
		$pattern=$ligne["pattern"];
		$id=md5($pattern);
		
		$delete=imgsimple("delete-32.png",null,"Delete$t('{$ligne["pattern"]}','$id')");
	
		$tr[]="
		<tr id='$id'>
		<td $styleTD width=99% nowrap ><i class='icon-globe'></i>&nbsp;$pattern</td>
		<td width=35px align='center' nowrap>$delete</td>
		</tr>";
	
	
	}
	$table=$tpl->_ENGINE_parse_body("
			<table class='table table-bordered'>
			<thead>
			<tr>
				<th>{urls}</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)."
				
			</tbody></table>
<script>
var memid$t='';
var xDelete$t= function (obj) {
	var res=obj.responseText;
	if (res.length>2){
		alert(res);
		return;
	}
	$('#'+memid$t).remove();
	
}
function Delete$t(item,id){
	memid$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('category','{$_GET["cat"]}');
	XHR.appendData('category-urls-delete',item);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
</script>
					";
					$html=$table;
	echo $html;
	}

function category_items_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$action_delete_rule=$tpl->javascript_parse_text("{delete}");
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$searchstring=string_to_flexquery("category-items-search");
	
	$table="category_".$_GET["cat"];
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY pattern LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error");}
	$styleTD="style='font-size:16px;font-weight:bold'";
	
	$t=time();
	while ($ligne = mysql_fetch_assoc($results)) {
	
	
		$pattern=$ligne["pattern"];
		$id=md5($pattern);
		$link=$boot->trswitch("Loadjs('$page?category-js=yes&cat={$ligne["category"]}')");
		$ligne["category_description"]=utf8_encode($ligne["category_description"]);
		
		$delete=imgsimple("delete-32.png",null,"Delete$t('{$ligne["pattern"]}','$id')");
		
		$tr[]="
		<tr id='$id'>
		<td $styleTD width=99% nowrap ><i class='icon-globe'></i>&nbsp;$pattern</td>
		<td width=35px align='center' nowrap>$delete</td>
		</tr>";
	
	
	}
	$table=$tpl->_ENGINE_parse_body("
			<table class='table table-bordered'>
			<thead>
			<tr>
				<th>{websites}</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)."
					
			</tbody></table>
<script>
var memid$t='';
			
var xDelete$t= function (obj) {
	var res=obj.responseText;
	if (res.length>2){
		alert(res);
		return;
	}
	$('#'+memid$t).remove();
	
}	

function Delete$t(item,id){
	memid$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('category','{$_GET["cat"]}');
	XHR.appendData('category-items-delete',item);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);	
}	
</script>
";
$html=$table;
echo $html;
}

function category_items_delete(){
	$category=$_POST["category"];
	$item=mysql_escape_string2($_POST["category-items-delete"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM category_{$category} WHERE `pattern`='$item'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function category_delete(){
	$category=$_POST["category-delete"];
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("category_{$category}")){
		$q->QUERY_SQL("DROP TABLE category_{$category}");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$category=mysql_escape_string2($category);
	$q->QUERY_SQL("DELETE FROM personal_categories WHERE `category`='$category'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
function category_urls_delete(){
	$category=$_POST["category"];
	$item=mysql_escape_string2($_POST["category-items-delete"]);
	$q=new mysql_squid_builder();
	$table="categoryuris_".$q->category_transform_name($category);
	$q->QUERY_SQL("DELETE FROM $table WHERE `pattern`='$item'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-database=$category");
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
