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
	
	
	$users=new usersMenus();
	
	
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["js"])){js();}
if(isset($_GET["add-perso-cat-js"])){add_category_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["search"])){items();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["add-perso-cat-tabs"])){add_category_tabs();exit;}


if(isset($_POST["UfdbEnableParanoidMode"])){UfdbEnableParanoidMode();exit;}

if(isset($_POST["delete"])){delete();exit;}


tabs();


function js(){
	$page=CurrentPageName();
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]} ?");
	$t=time();
$html="

var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PARANOID_TABLE').flexReload();
	Loadjs('squid.global.wl.center.progress.php');
}

function DeletePersonalCat$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}

DeletePersonalCat$t();";
	echo $html;
}

function delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_paranoid WHERE `pattern`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
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
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="urls"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.urls.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
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


function settings(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
	$UfdbEnableParanoidBlockW=intval($sock->GET_INFO("UfdbEnableParanoidBlockW"));
	$UfdbEnableParanoidBlockR=intval($sock->GET_INFO("UfdbEnableParanoidBlockR"));
	if($UfdbEnableParanoidBlockW==0){$UfdbEnableParanoidBlockW=100;}
	$UfdbEnableParanoidBlockC=intval($sock->GET_INFO("UfdbEnableParanoidBlockC"));
	if($UfdbEnableParanoidBlockC==0){$UfdbEnableParanoidBlockC=500;}
	if($UfdbEnableParanoidBlockR==0){$UfdbEnableParanoidBlockR=24;}	
	
	
	
	$p=Paragraphe_switch_img("{paranoid_mode}", "{paranoid_squid_mode_explain}","UfdbEnableParanoidMode",$UfdbEnableParanoidMode,null,1400);
	
	$html="<div style='width:98%' class=form>
		$p
		<table style='width:100%'>	
		<tr>
			<td class=legend style='font-size:22px'>{events_number_to_deny_a_website}:</td>
			<td>". Field_text("UfdbEnableParanoidBlockW",$UfdbEnableParanoidBlockW,"font-size:22px;width:150px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{events_number_to_deny_a_computer}:</td>
			<td>". Field_text("UfdbEnableParanoidBlockC",$UfdbEnableParanoidBlockC,"font-size:22px;width:150px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{remove_rules_after}:</td>
			<td style='font-size:22px'>". Field_text("UfdbEnableParanoidBlockR",$UfdbEnableParanoidBlockR,"font-size:22px;width:150px")."&nbsp;{hours}</td>
		</tr>							
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",30)."</td>
		</tr>
		</table>
		</div>
					
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	RefreshTab('main_squid_paranoid');

}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('UfdbEnableParanoidMode',document.getElementById('UfdbEnableParanoidMode').value);
	XHR.appendData('UfdbEnableParanoidBlockW',document.getElementById('UfdbEnableParanoidBlockW').value);
	XHR.appendData('UfdbEnableParanoidBlockC',document.getElementById('UfdbEnableParanoidBlockC').value);
	XHR.appendData('UfdbEnableParanoidBlockR',document.getElementById('UfdbEnableParanoidBlockR').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function UfdbEnableParanoidMode(){
	
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO("$num", $ligne);
	}
	$sock->getFrameWork("squid.php?ufdbguard-tail-restart=yes");	
}






function tabs(){
	
	
	
	$squid=new squidbee();
	$tpl=new templates();
	$WEBFILTERING_TOP_MENU=$tpl->_ENGINE_parse_body(WEBFILTERING_TOP_MENU());
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

	$array["settings"]="{parameters}";
	$array["table"]="{generated_rules}";
	$array["template"]="{template}";
	
	
	

	$fontsize=22;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.templates.skin.php?TEMPLATE_TAB=yes&TEMPLATE_TITLE=ERR_PARANOID&lang=$SquidHTTPTemplateLanguage\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	$html= "<div style='font-size:30px;margin-bottom:20px'>$WEBFILTERING_TOP_MENU</div>".build_artica_tabs($html,'main_squid_paranoid',1490);
	echo $html;

}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_paranoid")){
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_paranoid` (
				`pattern` VARCHAR( 90 ) NOT NULL,
				`object` VARCHAR( 20 ) NOT NULL DEFAULT 'dstdomain',
				`zDate` datetime NOT NULL,
				PRIMARY KEY (`pattern`),
				KEY `object` (`object`),
				KEY `pattern` (`pattern`)
			 )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html();}
	}

	
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$title=$tpl->javascript_parse_text("{generated_rules}");
	$deletetext=$tpl->javascript_parse_text("{purge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	
	$add=$tpl->javascript_parse_text("{add}");

	$buttons="	buttons : [
		{name: '<strong style=font-size:18px>$addCat</strong>', bclass: 'add', onpress : AddNewCategory},
		{name: '<strong style=font-size:18px>$size</strong>', bclass: 'Search', onpress : LoadCategoriesSize},
		{name: '<strong style=font-size:18px>$test_categories</strong>', bclass: 'Search', onpress : LoadTestCategories},
		
		
		
	],";
	$buttons=null;
	$t=time();
	$html="
			
<table class='PARANOID_TABLE' style='display: none' id='PARANOID_TABLE' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#PARANOID_TABLE').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$date</strong>', name : 'zDate', width : 243, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$items</strong>', name : 'pattern', width : 825, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$delete</strong>', name : 'icon2', width : 121, sortable : false, align: 'center'},
	

	],
	$buttons
	searchitems : [
	{display: '$items', name : 'pattern'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
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


</script>

";

echo $tpl->_ENGINE_parse_body($html);


}

function items(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$t=$_GET["t"];
	$OnlyPersonal=0;
	$error_license=null;
	$users=new usersMenus();

	
	
	$table="webfilters_paranoid";
	if($_POST["sortname"]=="categorykey"){$_POST["sortname"]="category";}
	
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
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		
	
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
		if(mysql_num_rows($results)==0){json_error_show("Not found...",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
	
		$enc=new mysql_catz();
	
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$pattern=$ligne["pattern"];
			$Date=$ligne["zDate"];
			$object=$tpl->javascript_parse_text("{{$ligne["object"]}}");
			
			$patternenc=urlencode($pattern);
			$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=$patternenc')");
			
			
	
			
		$cell=array();
		$cell[]="<span style='font-size:18px;padding-top:15px;font-weight:bold'>$Date</div>";
		$cell[]="<span style='font-size:18px;padding-top:15px;font-weight:bold'>$pattern - $object</div>";
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


	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	$dans=new dansguardian_rules();

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
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM personal_categories WHERE category='{$_POST["personal_database"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]<>null){
		$sql="UPDATE personal_categories
		SET category_description='{$_POST["category_text"]}',
		`PublicMode`='{$_POST["PublicMode"]}',
		master_category='{$_POST["CatzByGroupL"]}'
		WHERE category='{$_POST["personal_database"]}'
		";
	}else{

		if(isset($dans->array_blacksites[$_POST["personal_database"]])){
				$tpl=new templates();
				echo $tpl->javascript_parse_text("{$_POST["personal_database"]}:{category_already_exists}");
				return;
		}

		$sql="INSERT IGNORE INTO personal_categories (category,category_description,master_category,PublicMode)
		VALUES ('{$_POST["personal_database"]}','{$_POST["category_text"]}','{$_POST["CatzByGroupL"]}','{$_POST["PublicMode"]}');";
		}




	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->CreateCategoryTable($_POST["personal_database"]);
	$sql="TRUNCATE TABLE webfilters_categories_caches";
	$dans->CategoriesTableCache();
	$dans->CleanCategoryCaches();
	
}
function delete_category(){

	$category=trim($_POST["delete-personal-cat"]);
	if(strlen($category)==0){return;}
	$q=new mysql_squid_builder();
	if(!$q->DELETE_CATEGORY($category)){return;}
	
	
}