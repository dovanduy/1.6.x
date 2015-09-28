<?php
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	if(!IsDansGuardianrights()){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		
		exit;
	}
	
if(isset($_GET["js"])){js();}
if(isset($_GET["add-perso-cat-js"])){add_category_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["category-search"])){categories_search();exit;}
if(isset($_GET["add-perso-cat-popup"])){add_category_popup();exit;}
if(isset($_GET["add-perso-cat-tabs"])){add_category_tabs();exit;}

if(isset($_GET["category-delete-js"])){del_category_js();exit;}
if(isset($_POST["remove_category"])){delete_category();exit;}
if(isset($_POST["category_text"])){add_category_save();exit;}


categories();


function js(){
	$page=CurrentPageName();
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
	
}

function del_category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete_personal_cat_ask}");
	$t=time();
$html="

var X_DeletePersonalCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#META_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();
}

function DeletePersonalCat$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-personal-cat','{$_GET["category-delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',X_DeletePersonalCat$t);
}

DeletePersonalCat$t();";
	echo $html;
}


function add_category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{your_categories}::{new_category}");
	$html="YahooWin5('$widownsize','$page?add-perso-cat-tabs=yes&cat={$_GET["cat"]}&t=$t','$title');";
	echo $html;
}

function add_category_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();


	$catname=trim($_GET["cat"]);
	$catname_enc=urlencode($catname);

	if($_GET["cat"]==null){
		$catname="{new_category}";
	}

	$array["add-perso-cat-popup"]=$catname;
	if($_GET["cat"]<>null){
		$array["manage"]='{websites}';
		$array["urls"]='{urls}';
		$array["security"]='{permissions}';
		
	}

	$fontsize=18;
	$catzenc=urlencode($_GET["cat"]);
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){

		if($num=="manage"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"artica-meta.proxys.category.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="urls"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"artica-meta.proxys.urls.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="security"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.security.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"$page?$num=$t&t=$t&cat=$catname_enc\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "main_zoom_catz");



}

function add_category_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dans=new dansguardian_rules();
	$time=time();
	$q=new mysql_meta();
	$error_max_dbname=$tpl->javascript_parse_text("{error_max_database_name_no_more_than}");
	$error_category_textexpl=$tpl->javascript_parse_text("{error_category_textexpl}");
	$error_category_nomore5=$tpl->javascript_parse_text("{error_category_nomore5}");
	
	
	
	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$catenc=urlencode($_GET["cat"]);
	
	if($_GET["cat"]==null){$actions=null;}

	if($_GET["cat"]<>null){
		
		$sql="SELECT * FROM webfiltering_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$titleBT="{apply}";
	}else{
	$action=null;
	$titleBT='{add}';
	}
	
	
	
	$proto="http";
	if($_SERVER["HTTPS"]=="on"){$proto="https";}
	$uri="$proto://{$_SERVER["SERVER_ADDR"]}:{$_SERVER["SERVER_PORT"]}/categories";

	$catz_allow_in_public_mode=$tpl->_ENGINE_parse_body("{catz_allow_in_public_mode}");
	$catz_allow_in_public_mode=str_replace("%URI", $uri, $catz_allow_in_public_mode);
	$PublicMode=Paragraphe_switch_img("{allow_in_public_mode}", 
			"$catz_allow_in_public_mode","PublicMode-$time",$ligne["PublicMode"],null,750);
	
	


	$blacklists=$dans->array_blacksites;
	$description="<textarea name='category_text'
	id='category_text-$t' style='height:50px;overflow:auto;width:99%;font-size:22px !important'>"
	.$ligne["category_description"]."</textarea>";

	if(isset($blacklists[$_GET["cat"]])){
		$description="<input type='hidden' id='category_text-$t' value=''>
		<div class=explain style='font-size:13px'>{$blacklists[$_GET["cat"]]}</div>";
	}
	
	$html="
	<div id='perso-cat-form'></div>
	<div style='width:98%' class=form>
			<table style='width:100%'>
			<tbody>
			<tr>
				<td class=legend style='font-size:22px'>{category}:</td>
				<td>". Field_text("category-to-add-$t","{$_GET["cat"]}","font-size:22px;padding:3px;width:99%;font-weight:bold")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{description}:</td>
				<td>$description</td>
			</tr>
			<tr>
				<td colspan=2>$PublicMode</td>
			</tr>
			<tr>
				<td colspan=2 align='right' style='font-size:22px'><hr>$actions". button($titleBT,"SavePersonalCategory$t()",26)."</td>
			</tr>
			</tbody>
			</table>
			</div>
		

		<script>
var X_SavePersonalCategory= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PERSONAL_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();

}

function SavePersonalCategory$t(){
	var XHR = new XHRConnection();
	var db=document.getElementById('category-to-add-$t').value;
	var expl=document.getElementById('category_text-$t').value;
	if(db.length<5){alert('$error_category_nomore5');return;}
	if(expl.length<5){alert('$error_category_textexpl');return;}
	if(db.length>15){alert('$error_max_dbname: 15');return;}
	XHR.appendData('personal_database',db);
	var pp=encodeURIComponent(document.getElementById('category_text-$t').value);
	XHR.appendData('category_text',pp);
	XHR.appendData('PublicMode',document.getElementById('PublicMode-$time').value);
	XHR.sendAndLoad('$page', 'POST',X_SavePersonalCategory);
}

var X_DeletePersonalCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#META_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();
}

var X_CompilePersonalCat$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('perso-cat-form').innerHTML='';
	if(results.length>3){alert(results);return;};
	$('#META_CATEGORIES_TABLE').flexReload();
}


function DeletePersonalCat$t(){
	if(confirm('$delete_personal_cat_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-personal-cat','{$_GET["cat"]}');
		AnimateDiv('perso-cat-form');
		XHR.sendAndLoad('$page', 'POST',X_DeletePersonalCat$t);
	}

}

function checkform$t(){
	var cat='{$_GET["cat"]}';
	if(cat.length>0){document.getElementById('category-to-add-$t').disabled=true;}
}
checkform$t();
</script>

";
echo $tpl->_ENGINE_parse_body($html);


}

function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();

	$array["table"]="{your_categories}";

	$fontsize=18;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		
		if($num=="table"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"$page?categories=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
				
		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}



	$html= build_artica_tabs($html,'main_perso_categories',1024);
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;

}

function categories(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}else{
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");
	}

	$q->QUERY_SQL("DELETE FROM personal_categories WHERE category='';");
	$OnlyPersonal=null;
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();

	
	

	$purge_catagories_database_explain=$tpl->javascript_parse_text("{remove2}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{new_category}");
	$purge=$tpl->_ENGINE_parse_body("{purgeAll}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$title=$tpl->javascript_parse_text("{your_categories}");
	$deletetext=$tpl->javascript_parse_text("{remove2}");
	$delete="{display: '<strong style=font-size:18px>$deletetext</strong>', name : 'icon3', width : 127, sortable : false, align: 'center'},";


	
	
	
	$t=time();
	$html="
<table class='META_CATEGORIES_TABLE' style='display: none' id='META_CATEGORIES_TABLE' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#META_CATEGORIES_TABLE').flexigrid({
	url: '$page?category-search=yes',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$category</strong>', name : 'category', width : 898, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$items</strong>', name : 'TABLE_ROWS', width : 127, sortable : true, align: 'right'},
	
	$delete

	],
	buttons : [
		{name: '<strong style=font-size:18px>$addCat</strong>', bclass: 'add', onpress : AddNewCategory},
		
	],
	searchitems : [
	{display: '$category', name : 'category'},
	],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});


function AddNewCategory(){
	Loadjs('$page?add-perso-cat-js=yes&t=$t');
}

function SwitchToArtica(){
$('#dansguardian2-category-$t').flexOptions({url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t&artica=1'}).flexReload();
}

function SaveAllToDisk(){
Loadjs('$page?compile-all-dbs-js=yes')

}

function LoadCategoriesSize(){
Loadjs('dansguardian2.compilesize.php')
}

function CategoryDansSearchCheck(e){
if(checkEnter(e)){CategoryDansSearch();}
}

function CategoryDansSearch(){
var se=escape(document.getElementById('category-dnas-search').value);
LoadAjax('dansguardian2-category-list','$page?category-search='+se,false);

}

function DansGuardianCompileDB(category){
Loadjs('ufdbguard.compile.category.php?category='+category);
}

function CheckStatsApplianceC(){
LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes',false);
}

var X_PurgeCategoriesDatabase= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
RefreshAllTabs();
}

function PurgeCategoriesDatabase(){
if(confirm('$purge_catagories_database_explain')){
var XHR = new XHRConnection();
XHR.appendData('PurgeCategoriesDatabase','yes');
AnimateDiv('dansguardian2-category-list');
XHR.sendAndLoad('$page', 'POST',X_PurgeCategoriesDatabase);
}

}

var X_TableCategoryPurge= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
$('#dansguardian2-category-$t').flexReload();
}

function TableCategoryPurge(category){
	if(confirm('$purge_catagories_table_explain: << '+category+' >>?')){
		var XHR = new XHRConnection();
		XHR.appendData('remove_category',category);
		XHR.sendAndLoad('$page', 'POST',X_TableCategoryPurge);
	}
}


CheckStatsApplianceC();
</script>

";

echo $tpl->_ENGINE_parse_body($html);


}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$t=$_GET["t"];
	$OnlyPersonal=0;
	$error_license=null;
	
	
	$sql="SELECT * FROM webfiltering_categories";
	$table="webfiltering_categories";
	$searchstring=string_to_flexquery();
	$page=1;
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
		if($searchstring<>null){
			$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
			writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
			$total = $ligne["tcount"];
	
		}else{
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
		if(!is_numeric($rp)){$rp=50;}
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		
	
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	
		
		$results = $q->QUERY_SQL($sql);
	
		if(!$q->ok){if($q->mysql_error<>null){
			json_error_show(date("H:i:s").
			"<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",0);}}
	
	
		if(mysql_num_rows($results)==0){json_error_show("Not found...",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
	
		$enc=new mysql_catz();
	
		$field="category";
		$field_description="category_description";

		$q2=new mysql_squid_builder();
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$category=$ligne["category"];
			$categoryname=$category;
			$text_category=null;
			$itemsEncTxt="-";
	
			$sql="SELECT COUNT( pattern ) AS tcount FROM webfiltering_categories WHERE category='$category'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$items=$ligne2["tcount"];
			
			$text_category=$tpl->_ENGINE_parse_body(utf8_decode($ligne[$field_description]));
			$text_category=trim($text_category);
	
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
			style='font-size:20px;font-weight:bold;color:$color;text-decoration:underline'>";
			
			$categoryname_text=utf8_encode($categoryname);
			$categoryText=$tpl->_ENGINE_parse_body("<span style='font-size:20px';font-weight:bold'>$linkcat$categoryname_text</span>
					</a><br><span style='font-size:18px;width:100%;font-weight:normal'><i>{$text_category}</i></span>$error_license");
			
			if($items>0){
				$itemsEncTxt="<span style='font-size:18px;font-weight:bold'>".numberFormat($items,0,""," ");"</span>";
			}
	
			$compile=imgsimple("compile-distri-48.png",null,"DansGuardianCompileDB('$categoryname')");
			$delete=imgsimple("dustbin-48.png",null,"TableCategoryPurge('$category')");
			

	
			if($categoryname=="UnkNown"){
				$linkcat=null;
				$delete=imgsimple("delete-48.png",null,"TableCategoryPurge('')");
			}
	
		$cell=array();
		$cell[]=$categoryText;
		$cell[]=$itemsEncTxt;
		$cell[]="<center>$delete</center>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => $cell
		);
		}
	
	
		echo json_encode($data);
	
}

function add_category_save(){
	$_POST["personal_database"]=url_decode_special_tool($_POST["personal_database"]);
	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	$org=$_POST["personal_database"];
	$html=new htmltools_inc();
	$_POST["personal_database"]=strtolower($html->StripSpecialsChars($_POST["personal_database"]));

	if($_POST["personal_database"]==null){
		echo "No category set or wrong category name \"$org\"\n";
		return;
	}

	if($_POST["personal_database"]=="security"){$_POST["personal_database"]="security2";}
	if($_POST["CatzByGroupA"]<>null){$_POST["CatzByGroupL"]=$_POST["CatzByGroupA"];}
	$_POST["CatzByGroupL"]=mysql_escape_string2($_POST["CatzByGroupL"]);
	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	$_POST["category_text"]=mysql_escape_string2($_POST["category_text"]);
	
	
	$q=new mysql_meta();
	
	$sql="CREATE TABLE IF NOT EXISTS `webfiltering_categories` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`category_description` VARCHAR( 255 ) NOT NULL ,
				`PublicMode` smallint(1) NOT NULL,
				PRIMARY KEY (`category`),
				INDEX ( `category_description`) ,
				KEY `PublicMode` (`PublicMode`) )  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	$sql="SELECT category FROM webfiltering_categories WHERE category='{$_POST["personal_database"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]==null){
		$sql="INSERT IGNORE INTO webfiltering_categories (category,category_description,PublicMode)
		VALUES ('{$_POST["personal_database"]}','{$_POST["category_text"]}','{$_POST["PublicMode"]}');";
		
	}else{
		$sql="UPDATE webfiltering_categories SET category_description='{$_POST["category_text"]}',
		PublicMode='{$_POST["PublicMode"]}' WHERE category='{$_POST["personal_database"]}'";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	
	
}





function delete_category(){
	$category=trim($_POST["remove_category"]);
	if(strlen($category)==0){return;}
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM webfiltering_categories WHERE `category`='$category'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfiltering_categories_items WHERE `category`='$category'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfiltering_categories_urls WHERE `category`='$category'");	
	$sock=new sockets();
	$sock->getFrameWork("artica.php?scan-categories=yes");
	
	
}