<?php
	if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(!CategoriesCheckRightsRead()){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}<hr>".@implode("<br>", $GLOBALS["CategoriesCheckRights"]));
		exit;
		
	}
	if(isset($_GET["test-cat"])){test_category();exit;}
	if(isset($_GET["add-uris-js"])){add_uris_js();exit;}
	if(isset($_GET["add-uris-popup"])){add_uris_popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["field-list"])){field_list();exit;}
	if(isset($_GET["query"])){query();exit;}
	if(isset($_POST["items"])){SaveItems();exit;}
	if(isset($_POST["DeleteCategorizedURI"])){DeleteCategorizedURI();exit;}
	if(isset($_GET["move-category-popup"])){MoveCategory_popup();exit;}
	if(isset($_POST["RemoveAll"])){RemoveAll();exit;}
	if(isset($_POST["MoveCategorizedWebsitePattern"])){MoveCategorizedWebsiteAll();exit;}
	if(isset($_GET["RemoveDisabled-popup"])){removedisabled_popup();exit;}
	if(isset($_POST["RemoveDisabled"])){removedisabled_perform();exit;}
	if(isset($_POST["WEBTESTS"])){test_category_perform();exit;}

	
	popup();

function add_uris_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	header("content-type: application/x-javascript");
	$category=$_GET["category"];
	$title=$tpl->javascript_parse_text("{add_urls}::$category");
	echo "RTMMail('720','$page?add-uris-popup=yes&category={$_GET["category"]}&tt={$_GET["t"]}','$title');";
}

function SaveItems(){
	$category=$_POST["category"];
	$datas=$_POST["items"];
	$q=new mysql_meta();
	$tb=explode("\n",$datas);
	while (list ($num, $www) = each ($tb) ){
		if(preg_match("#tp:\/\/(.+)#", $www,$re)){$www=$re[1];}
		$www=str_replace("www.", "", $www);
		$uris[$www]=true;
	}
	$zDate=date("Y-m-d H:i:s");
	while (list ($www, $none) = each ($uris) ){
		if(trim($www)==null){continue;}
		$md5=md5("$category$www");
		echo "Saving $www\n";
		$www=mysql_escape_string2($www);
		$f[]="('$category','$md5','$zDate','$www',1)";
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO `webfiltering_categories_urls` (`category`,`zmd5`,`zDate`,`pattern`,`enabled`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
}

function DeleteCategorizedURI(){
	$md=$_POST["DeleteCategorizedURI"];
	$table=$_POST["TABLE"];
	$category=$_POST["catz"];
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM webfiltering_categories_urls WHERE zmd5='$md'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}
function RemoveAll(){
	$md=$_POST["RemoveAll"];
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM webfiltering_categories_urls WHERE category='$md'");

}

function add_uris_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<div class=explain style='font-size:16px !important'>{webfiltering_add_uris_explain}</div>		
	<textarea 
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:95%;height:150px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important' id='textToParseCats$t'></textarea>
	<center style='margin:15px'>". button("{add}","Save$t()",18)."</center>	
<script>
	var x_FreeCategoryPost$t= function (obj) {
		var res=obj.responseText;
		var tt='{$_GET["tt"]}';
		if (res.length>0){
			document.getElementById('textToParseCats$t').value=res;
		}
		if(tt>0){ if(document.getElementById(tt)){ $('#'+tt).flexReload();} }
		ExecuteByClassName('SearchFunction');
	}	

	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('category','{$_GET["category"]}');
		XHR.appendData('items',document.getElementById('textToParseCats$t').value);
		document.getElementById('textToParseCats$t').value='Processing....\\n\\n'+document.getElementById('textToParseCats$t').value;
		XHR.sendAndLoad('$page', 'POST',x_FreeCategoryPost$t);	
	}
</script>	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function popup(){
	$tpl=new templates();
	$TB_WIDTH=915;
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=time();
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$date=$tpl->_ENGINE_parse_body("{date}");	
	$movetext=$tpl->_ENGINE_parse_body("{move}");
	$moveall=$tpl->_ENGINE_parse_body("{move} {all}");
	$select=$tpl->_ENGINE_parse_body("{select}"); 
	$add_websites=$tpl->_ENGINE_parse_body("{add_urls}");
	$category=$_GET["category"];
	$q=new mysql_meta();
	
	$searchitem=null;
	$category_text=$tpl->_ENGINE_parse_body("{category}");
	$removedAll=$tpl->javascript_parse_text("{delete_all}");
	$removedisabled_warn=$tpl->javascript_parse_text("{remove_disabled_items_warn}");
	$CategoriesCheckRightsWrite=CategoriesCheckRightsWrite();
	$export=$tpl->javascript_parse_text("{export}");
	
		
		
	
	$xls="{name: '<strong style=font-size:18px>$export:CSV</strong>', bclass: 'xls', onpress : xls$t},";
	$RemoveEnabled="{name: '<strong style=font-size:18px>$removedAll</strong>', bclass: 'Delz', onpress : DeleteAll$t},";
	$BUTTON_ADD="{name: '<strong style=font-size:18px>$add_websites</strong>', bclass: 'Add', onpress : AddWebSites$t},";
	$xls=null;
	if(!$CategoriesCheckRightsWrite){
		$RemoveEnabled=null;$BUTTON_ADD=null;
	}
	
		$buttons="buttons : [
			$RemoveEnabled
				],	";	
			

	
		if($_GET["middlesize"]=="yes"){$TB_WIDTH=915;}
		if($_GET["category"]<>null){
			$table_title="$category_text::$category";
		$buttons="buttons : [
			$BUTTON_ADD
			$RemoveEnabled $xls
				],";
		
		$searchitem="	searchitems : [
		{display: '$uri', name : 'pattern'}
		],";
		
		}


$rowebsite=461;
if(isset($_GET["rowebsite"])){$rowebsite=$_GET["rowebsite"];$rowebsite=$rowebsite-40;}
	
echo "

<div style='margin-left:-15px'>
	<table class='$t' style='display: none' id='$t' style='width:99%;'></table>
</div>
<script>
var MEMMD$t='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes&category={$_GET["category"]}&website={$_GET["website"]}',
	dataType: 'json',
	colModel : [
			{display: '<span style=font-size:18px>$date</span>', name : 'zDate', width : 179, sortable : true, align: 'left'},	
			{display: '<span style=font-size:18px>$uri</span>', name : 'pattern', width :665, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
$searchitem
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$table_title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true
	
	});   
});

function xls$t(){
	Loadjs('squid.categories.url.export.php?category={$_GET["category"]}&t=$t');
}

	function AddWebSites$t(){
		Loadjs('$page?add-uris-js=yes&category={$_GET["category"]}&t=$t');
	}

	function MoveCategorizedWebsite(zmd5,website,category,category_table){
		YahooWinBrowse(550,'$page?move-category-popup=yes&YahooWin=YahooWinBrowse&website='+website+'&zmd5='+zmd5+'&category-source='+category+'&table-source='+category_table,'$movetext::'+website);
	}

	function MoveAllCategorizedWebsite(){
		YahooWinBrowse(550,'$page?move-category-popup=yes&YahooWin=YahooWinBrowse&website=&zmd5=&category-source={$_GET["category"]}&table-source=$table&bysearch={$_GET["search"]}','$movetext::{$_GET["search"]}');
		
	}
	
	function MoveAllCategorizedWebsite2(category,table,search){
		YahooWinBrowse(550,'$page?move-category-popup=yes&YahooWin=YahooWinBrowse&website=&zmd5=&category-source='+category+'&table-source='+table+'&bysearch='+search+'&t=$t','$movetext::'+search);
		
	}

	var x_DeleteAll$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#$t').flexReload();
	}		
	
	function DeleteAll$t(){
		var categoryZ = '{$_GET["category"]}'
		if(confirm('$removedAll:'+categoryZ)){
			var XHR = new XHRConnection();
			XHR.appendData('RemoveAll',categoryZ);
			XHR.sendAndLoad('$page', 'POST',x_DeleteAll$t);
		}
	}
	
	
	var x_DeleteCategorizedWebsite$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+MEMMD$t).remove();
	}	


	

	
	function DeleteCategorizedURI(zmd5,table){
		MEMMD$t=zmd5;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteCategorizedURI',zmd5);
		XHR.appendData('catz','{$_GET["category"]}');
		XHR.appendData('TABLE',table);
		XHR.sendAndLoad('$page', 'POST',x_DeleteCategorizedWebsite$t);	
	}	

</script>
";	
}


function query(){
	
	$category=null;
	$tpl=new templates();
	$also=$tpl->_ENGINE_parse_body("{also}");
	$MyPage=CurrentPageName();
	$q=new mysql_meta();
	
	$nowebsites=$tpl->_ENGINE_parse_body("{no_saved_web_site_catz}");
	
	
	if($category==null){if($_GET["category"]<>null){$category=$_GET["category"];}}
	if($category==null){if($_POST["qtype"]<>null){$category=$_POST["qtype"];}}	
	
	
	if($_POST["query"]<>null){if($_GET["website"]<>null){$_POST["query"]=$_GET["website"];}}
	if($category==null){json_error_show("Please select a category first");}
	if($_POST["sortname"]=="sitename"){$_POST["sortname"]="zDate";$_POST["sortorder"]="desc";}
	
	
	$table="webfiltering_categories_urls";

	$search='%';
	$page=1;
	$COUNT_ROWS=$q->COUNT_ROWS($table);
	
	if($COUNT_ROWS==0){json_error_show("no data",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(zmd5) as TCOUNT FROM `$table` WHERE category='$category' $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT zDate,zmd5,pattern,enabled  FROM `$table` WHERE category='$category' $searchstring $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	if(mysql_num_rows($results)==0){json_error_show("$nowebsites",1);}
	$disabled_text=$tpl->_ENGINE_parse_body("{disabled}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgtootltip("delete-24.png","{delete}","DeleteCategorizedURI('{$ligne["zmd5"]}','$table')");
		$color="color:black";
		
		
		
		$jscat="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.categorize.php?www={$ligne["pattern"]}');\"
				style='font-size:14px;text-decoration:underline;$color'>";	


		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array("
		<span style='font-size:18px;$color'>{$ligne['zDate']}</span>",
		"<span style='font-size:18px;$color'>{$ligne['pattern']}</a></span>","<center>$delete</center>")
		);
	}
	
	
echo json_encode($data);	

	
}
?>