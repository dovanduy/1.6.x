<?php
session_start();

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["ByJs"])){main_js();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");die();}
if(count($_SESSION["ProxyCategoriesPermissions"])==0){header("location:miniadm.logon.php");die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["category-tab"])){category_section();exit;}
if(isset($_GET["category-search"])){category_search();exit;}
if(isset($_GET["ruleid"])){ruleid_popup();exit;}
if(isset($_POST["SaveRule"])){ruleid_save();exit;}
if(isset($_POST["enable_id"])){ruleid_enable();exit;}
if(isset($_POST["delete-id"])){ruleid_delete();exit;}

if(isset($_GET["add-www-js"])){add_www_js();exit;}
if(isset($_GET["add-www-tab"])){add_www_tab();exit;}
if(isset($_GET["add-www-popup"])){add_www_import();exit;}
if(isset($_POST["categorize"])){categorize();exit;}
if(isset($_POST["www-delete"])){www_delete();exit;}

main_page();

function main_js(){
	if(!isset($_SESSION["uid"])){
		echo "alert('No Session!!!');";
		die();
	}
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die();}
	$gpid=$_GET["ByJs"];
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	
	if(!$_SESSION["MINIADM"]){echo "alert('Should be viewed trough End-Users Web interface');";}
	
	$tpl=new templates();
	$dynamic_acls_newbee=$tpl->javascript_parse_text("{dynamic_acls_newbee}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	$html="YahooWin4('890','$page?tab-gpid=$gpid','$dynamic_acls_newbee::{$ligne["GroupName"]}')";
	echo $html;
	
}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function add_www_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$category=urlencode($_GET["category"]);
	$title=$tpl->javascript_parse_text("{$_GET["category"]}:: {new_item}");
	echo "YahooWin5('700','$page?add-www-tab=yes&category=$category','$title')";
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$users=new usersMenus();
	
	$dynamic_acls_newbee_explain=$tpl->_ENGINE_parse_body("{dynamic_categories_newbee_explain}");
	$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["ProxyCategoriesPermissions"]), $dynamic_acls_newbee_explain);
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;|&nbsp;<a href=\"$page\" ><strong>{dynamic_acls_newbee}</strong></a>";
	if($users->AsSquidAdministrator){
		$html=$html."&nbsp;|&nbsp;<a href=\"miniadmin.proxy.index.php\"><strong>{APP_PROXY}</strong></a>
		";
	}
	$html=$html."
		</div>
		
		
		<H1>{categories}</H1>
		<p>$dynamic_acls_newbee_explain</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='left-$t' class=BodyContent></div>
	
	<script>
		LoadAjax('left-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function add_www_tab(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$arrayACLS=$_SESSION["ProxyCategoriesPermissions"];
	if(!is_array($arrayACLS)){
		$miniadm=new miniadm();
		$arrayACLS=$miniadm->ProxyCategoriesPermissions(true);
	
	}
	
	if(!isset($arrayACLS[$_GET["category"]])){senderror("no permission");}
	$categoryenc=urlencode($_GET["category"]);
	$array["{import}"]="$page?add-www-popup=yes&category=$categoryenc";
	echo $boot->build_tab($array);	
	
}
function add_www_import(){
	
	$boot=new boostrap_form();
	
	$boot->set_formdescription("{free_catgorized_explain}");
	$boot->set_hidden("category", $_GET["category"]);
	$boot->set_checkbox("ForceCat", "{force}", 0,array("TOOLTIP"=>"{free_cat_force_explain}"));
	$boot->set_textarea("categorize", "{items}", null,array("HEIGHT"=>250));
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	$boot->set_RefreshSearchsForced();
	echo $boot->Compile();
	
}

function categorize(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',"\n");
	$q=new mysql_squid_builder();
	$q->free_categorizeSave($_POST["categorize"],$_POST["category"],$_POST["ForceCat"]);
}

function tabs(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();	
	$arrayACLS=$_SESSION["ProxyCategoriesPermissions"];
	if(!is_array($arrayACLS)){
		$miniadm=new miniadm();
		$arrayACLS=$miniadm->ProxyCategoriesPermissions(true);
		
	}
	$q=new mysql_squid_builder();
	
	while (list ($category, $val) = each ($arrayACLS) ){
		$table=$q->cat_totablename($category);
		if(!$q->TABLE_EXISTS($table)){
			$q->QUERY_SQL("DELETE FROM webfilter_catprivs WHERE categorykey='$category'");
			continue;
		}
		$categoryenc=urlencode($category);
		$array[$category]="$page?category-tab=yes&category=$categoryenc";
		
	}
	
	echo $boot->build_tab($array);
}
function category_explain($category){
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	if(!isset($cats[$category])){$cats[$category]=null;}
	if($cats[$category]==null){
		$q=new mysql_squid_builder();
		$sql="SELECT category_description FROM personal_categories WHERE category='$category'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$content=$ligne["category_description"];
		$content=utf8_encode($content);
	}else{
		$content=$cats[$category];
	}

	return utf8_encode($content);

}


function category_section(){
	$categoryenc=urlencode($_GET["category"]);
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$OPTIONS["BUTTONS"][]=button("{new_item}","Loadjs('$page?add-www-js=yes&category=$categoryenc')",16);
	if($_SESSION["ProxyCategoriesPermissions"][$_GET["category"]]==1){
		$OPTIONS["BUTTONS"][]=button("{apply_modifications_to_the_proxy}","Loadjs('ufdbguard.compile.category.php?category=$categoryenc')",16);
	}
	$category_explain=category_explain($_GET["category"]);
	
	echo "<div class=text-info style='margin:20px;font-size:18px'>$category_explain</div>".$boot->SearchFormGen("pattern,zDate","category-search","&category=$categoryenc",$OPTIONS);


}
function category_search(){
	$t=time();
	ini_set('display_errors', 1);
	ini_set('error_prepend_string',"<p class=text-error>");
	ini_set('error_append_string',"</p>\n");
	
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$ORDER=$boot->TableOrder(array("pattern"=>"ASC"));	
	$searchstring=string_to_flexquery("category-search");
	$table=$q->cat_totablename($_GET["category"]);
	
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr>$sql</p>\n";}
	
	$tr=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$date=$boot->time_to_date(strtotime($ligne['zDate']),true);
		$md5=md5(serialize($ligne));
		$pattern=$ligne["pattern"];
		$delete=imgsimple("delete-32.png",null,"Delete$t('$pattern','$md5')");
		$tr[]="
		<tr id='$md5'>
		<td style='font-size:18px' nowrap  width=1% nowrap>$date</td>
		<td style='font-size:18px' nowrap >$pattern</td>
		<td style='font-size:18px' nowrap width=1% >$delete</td>
		</tr>";
		
	}
	echo $boot->TableCompile(
			array("zDate"=>"{date}",
					"pattern"=>"{sitename}",
					"delete"=>"{delete}"

			
			),
			$tr
	)."
					
<script>
var id$t='';
	var xDelete$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#'+id$t).remove();
		
	}

function Delete$t(www,md){
	id$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('www-delete',www);
	XHR.appendData('category','{$_GET["category"]}');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);		
}
</script>";
	
}
function www_delete(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',"<p class=text-error>");
	ini_set('error_append_string',"</p>\n");
	$q=new mysql_squid_builder();
	$category_table="category_".$q->category_transform_name($_POST["category"]);
	$q->QUERY_SQL("DELETE FROM $category_table WHERE pattern='{$_POST["www-delete"]}'");
	$q->categorize_logs($_POST["category"],"{delete}",$_POST["www-delete"]);
	
	if(!$q->ok){echo $q->mysql_error;}
}
