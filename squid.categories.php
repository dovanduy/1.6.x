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
	if(isset($_GET["move-js"])){move_js();exit;}
	if(isset($_GET["test-cat"])){test_category();exit;}
	if(isset($_GET["js-popup-master"])){js_popup_master();exit;}
	if(isset($_GET["subtitles-categories"])){subtitle_categories();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["field-list"])){field_list();exit;}
	if(isset($_GET["query"])){query();exit;}
	if(isset($_POST["DeleteCategorizedWebsite"])){DeleteCategorizedWebsite();exit;}
	if(isset($_POST["ReallyDeleteCategorizedWebsite"])){DeleteCategorizedWebsiteReally();exit;}
	if(isset($_GET["move-category-popup"])){MoveCategory_popup();exit;}
	if(isset($_POST["MoveCategorizedWebsite"])){MoveCategorizedWebsite();exit;}
	if(isset($_POST["MoveCategorizedWebsitePattern"])){MoveCategorizedWebsiteAll();exit;}
	if(isset($_GET["RemoveDisabled-popup"])){removedisabled_popup();exit;}
	if(isset($_POST["RemoveDisabled"])){removedisabled_perform();exit;}
	if(isset($_POST["WEBTESTS"])){test_category_perform();exit;}
	if(isset($_POST["move-domain"])){move_domain();exit;}
js();	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$width=1015;
	$statusfirst=null;
	$title=$tpl->_ENGINE_parse_body("{categories}");
	
	if(isset($_GET["statusfirst"])){$statusfirst="&statusfirst=yes";}
	
	if($_GET["category"]<>null){$title=$title."::{$_GET["category"]}";$width=1015;}
	if($_GET["website"]<>null){
		if(preg_match("#^www\.(.+)#", $_GET["website"],$re)){$_GET["website"]=$re[1];}
		$title=$title."::{$_GET["website"]}";
		$width=890;
	}
	
	$YahooWin="YahooWinS";
	if($_GET["category"]==null){$YahooWin="YahooWin2";}
	
	$start="$YahooWin('$width','$page?tabs=yes&onlyDB={$_GET["onlyDB"]}&category={$_GET["category"]}&website={$_GET["website"]}$statusfirst&YahooWin=$YahooWin','$title');";
	$html="
	$start
	";
	echo $html;
	
}

function move_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$domain=$_GET["domain"];
	$Tofield=$_GET["Tofield"];
	$removerow=$_GET["removerow"];
	$fromwhat=$_GET["fromwhat"];
	$t=time();
echo "
var xMoveWebsite$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#$removerow').remove();
	$('#row{$removerow}').remove();
	$('#{$Tofield}').remove();
}
	
function MoveWebsite$t(){
	var XHR = new XHRConnection();
	var to=document.getElementById('$Tofield').value;
	XHR.appendData('move-domain','$domain');
	XHR.appendData('from_cat','$fromwhat');
	XHR.appendData('to_cat',to);
	XHR.sendAndLoad('$page', 'POST',xMoveWebsite$t);
}

MoveWebsite$t();
";
	
}

function move_domain(){
	$q=new mysql_squid_builder();
	$domain=$_POST["move-domain"];
	$table=$q->cat_totablename($_POST["from_cat"]);
	$q->QUERY_SQL("DELETE FROM `$table` WHERE `pattern`='$domain'");
	$q->categorize($domain, $_POST["to_cat"],true);
	
}

function js_popup_master(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{categories}");
	if($_GET["category"]<>null){$title=$title."::{$_GET["category"]}::{$_GET["search"]}";}
	header("content-type: application/x-javascript");
	$start="RTMMail('720','$page?popup=yes&category={$_GET["category"]}&website={$_GET["search"]}&tablesize=700&rowebsite=426','$title');";
	$html="
	$start
	";
	echo $html;	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
	if($DisableArticaProxyStatistics==0){
		if(isset($_GET["statusfirst"])){$array["status"]='{status}';}
	}
	
	$array["free-cat"]='{add_websites}';
	$array["test-cat"]='{test_categories}';
	$array["list"]='{categories}';
	$array["popup"]='{manage_your_items}';
	$array["security"]='{permissions}';
	//$array["size"]='{compiled_categories}';
	//if($DisableArticaProxyStatistics==0){if(!isset($_GET["statusfirst"])){$array["status"]='{status}';}}
	//$array["squidlogs"]='{statistics_database}';
	
	if($_GET["category"]<>null){
		unset($array["free-cat"]);
		unset($array["list"]);
		unset($array["test-cat"]);
		unset($array["status"]);
		unset($array["squidlogs"]);
		unset($array["size"]);
		
	}else{
		$catzadd="&middlesize=yes";
		
	}
	
	if(!$users->APP_UFDBGUARD_INSTALLED){
		unset($array["free-cat"]);
		unset($array["list"]);
		unset($array["popup"]);
		unset($array["size"]);
		
	}
	
	if($_GET["onlyDB"]=="yes"){
		$array=array();
		if($_GET["category"]==null){
			$array["status"]='{statistics_database}:{status}';
			$array["schedule"]='{statistics_database}:{schedule}';
			$array["events"]='{statistics_database}:{events}';
			$array["articaufdb"]='{web_filtering}';
			
		}
		
	}
	
	$fontsize=16;
	if(count($array)<5){$fontsize=16;}
	$catname_enc=urlencode($_GET["category"]);
while (list ($num, $ligne) = each ($array) ){
	
		if($num=="free-cat"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.visited.php?free-cat=yes&category=$catname_enc&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="status"){
			$html[]= "<li style='font-size:{$fontsize}px'>
			<a href=\"dansguardian2.databases.php?statusDB=yes\">
				<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
		
		if($num=="schedule"){
			$html[]= "<li style='font-size:{$fontsize}px'>
			<a href=\"squid.databases.schedules.php?TaskType=1\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
		if($num=="list"){
			$html[]= "<li style='font-size:{$fontsize}px'>
				<a href=\"dansguardian2.databases.php?categories=$catzadd&minisize-middle=yes\">
				<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="articaufdb"){
			$html[]= "<li style='font-size:{$fontsize}px'>
			<a href=\"dansguardian2.databases.compiled.php?articaufdb=yes\">
				<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="events"){
			$html[]= "<li style='font-size:{$fontsize}px'>
			<a href=\"squid.articadb.events.php\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		
		if($num=="size"){
			$html[]= "<li style='font-size:{$fontsize}px'>
				<a href=\"dansguardian2.databases.compiled.php?status=yes\">
					<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="squidlogs"){
			$html[]= "<li style='font-size:{$fontsize}px'><a href=\"squidlogs.php\"
			><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="security"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
					<a href=\"squid.categories.security.php?popup=yes&category=$catname_enc&tablesize=893&t=\" 
					><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$html[]= "<li style='font-size:{$fontsize}px'>
		<a href=\"$page?$num=yes&category={$_GET["category"]}$catzadd&website={$_GET["website"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	$t=time();
	echo build_artica_tabs($html, "squid_categories_zoom-$t");
		
	
	
}

function removedisabled_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$removedisabled=$tpl->_ENGINE_parse_body("{remove_disabled_items}");
	$removedisabled_warn=$tpl->javascript_parse_text("{remove_disabled_items_warn}");	
	$t=$_GET["t"];

		if($q->COUNT_ROWS("webfilters_categories_caches")==0){
			$dans=new dansguardian_rules();
			$dans->CategoriesTableCache();
			
		}
		$sql="SELECT categorykey FROM webfilters_categories_caches ORDER BY categorykey";
		$results = $q->QUERY_SQL($sql);
		
		$s[null]="{select}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$s[$ligne["categorykey"]]=$ligne["categorykey"];
		}	
	
		
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{category}</td>
		<td>". Field_array_Hash($s,"removedisabled-$t",null,null,null,0,"font-size:16px")."</td>
	</tr>
	
	<td colspan=2 align='right'><hr>". button("$removedisabled","RemoveItems$t()",18)."</td>
	</tr>
	<script>
		var x_RemoveItems$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			FlexReloadWebsiteCategoriesManage();
			YahooWin5Hide();
		}		
	
	function RemoveItems$t(){
		var categoryZ = document.getElementById('removedisabled-$t').value;
		if(confirm('$removedisabled_warn:'+categoryZ)){
			var XHR = new XHRConnection();
			XHR.appendData('RemoveDisabled',categoryZ);
			XHR.sendAndLoad('$page', 'POST',x_RemoveItems$t);
		}
	}	
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function subtitle_categories(){
	if(CACHE_SESSION_GET(__FUNCTION__, __FILE__,15)){return;}
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$categories=$q->COUNT_CATEGORIES();
	$categories=numberFormat($categories,0,""," ");
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	$tablescatNUM=numberFormat(count($tablescat),0,""," ");		
	$html="<div style='font-size:16px'><b>$categories</b> {websites_categorized}&nbsp;|&nbsp;<b>$tablescatNUM</b> {categories}</div>";
	CACHE_SESSION_SET(__FUNCTION__, __FILE__, $tpl->_ENGINE_parse_body($html));
}

function popup(){
	
	$tpl=new templates();
	$TB_WIDTH=915;
	
	$users=new usersMenus();

	
	if(isset($_GET["tablesize"])){$TB_WIDTH=$_GET["tablesize"];}
	$page=CurrentPageName();
	$t=time();
	$website=$tpl->_ENGINE_parse_body("{website}");
	$date=$tpl->_ENGINE_parse_body("{date}");	
	$movetext=$tpl->_ENGINE_parse_body("{move}");
	$moveall=$tpl->_ENGINE_parse_body("{move} {all}");
	$select=$tpl->_ENGINE_parse_body("{select}"); 
	$add_websites=$tpl->_ENGINE_parse_body("{add_websites}");
	$category=$_GET["category"];
	$q=new mysql_squid_builder();
	$table="category_".$q->category_transform_name($category);	
	$searchitem=null;
	$category_text=$tpl->_ENGINE_parse_body("{category}");
	$removedisabled=$tpl->_ENGINE_parse_body("{remove_disabled_items}");
	$removedisabled_warn=$tpl->javascript_parse_text("{remove_disabled_items_warn}");
	$export=$tpl->javascript_parse_text("{export}");
	$CategoriesCheckRightsWrite=CategoriesCheckRightsWrite();
	if($category==null){
		
		if($q->COUNT_ROWS("webfilters_categories_caches")==0){
			$dans=new dansguardian_rules();
			$dans->CategoriesTableCache();
			
		}
		$sql="SELECT categorykey FROM webfilters_categories_caches ORDER BY categorykey";
		$results = $q->QUERY_SQL($sql);
		$s[]="{display: '$select', name : ''}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$s[]="{display: '{$ligne["categorykey"]}', name : '{$ligne["categorykey"]}'}";
		}
		
		$searchitem="	searchitems : [
		".@implode(",\n", $s)."
		],";
		
		
		
	}
	$xls="{name: '$export:CSV', bclass: 'xls', onpress : xls$t},";
	$RemoveEnabled="{name: '$removedisabled', bclass: 'Delz', onpress : RemoveDisabled$t},";
	$BUTON_ADD_WEBSITES="{name: '$add_websites', bclass: 'Add', onpress : AddWebSites$t},";
	if(!$CategoriesCheckRightsWrite){$RemoveEnabled=null;$BUTON_ADD_WEBSITES=null;}
	
	
		$buttons="buttons : [
			$RemoveEnabled 
				],	";	
			

	
		if($_GET["middlesize"]=="yes"){$TB_WIDTH=915;}
		if($_GET["category"]<>null){
			$table_title="$category_text::$category";
		$buttons="buttons : [
			$BUTON_ADD_WEBSITES
			$RemoveEnabled $xls
				],";
		
		$searchitem="	searchitems : [
		{display: '$website', name : 'pattern'}
		],";
		
		}


$rowebsite=346;
if(isset($_GET["rowebsite"])){$rowebsite=$_GET["rowebsite"];$rowebsite=$rowebsite-40;}

	if(!$users->CORP_LICENSE){
		$title=$title."<img src='img/status_warning.png'>".$tpl->_ENGINE_parse_body("{license_inactive}!")."";
	}
	
echo "
<span id='FlexReloadWebsiteCategoriesManageDiv'></span>
	<table class='$t' style='display: none' id='$t' style='width:99%;'></table>
<script>
var MEMMD='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes&category={$_GET["category"]}&website={$_GET["website"]}',
	dataType: 'json',
	colModel : [
			{display: '$date', name : 'zDate', width : 153, sortable : true, align: 'left'},	
			{display: '$website', name : 'pattern', width :$rowebsite, sortable : true, align: 'left'},
			{display: '$movetext', name : 'description2', width : 40, sortable : false, align: 'left'},
			{display: '$movetext', name : 'description', width : 40, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
$searchitem
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$table_title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true
	
	});   
});

function xls$t(){
	Loadjs('squid.categories.export.php?category={$_GET["category"]}&t=$t');
}

	function AddWebSites$t(){
		Loadjs('squid.visited.php?add-www=yes&category={$_GET["category"]}&t=$t');
	}

	function MoveCategorizedWebsite(zmd5,website,category,category_table){
		YahooWinBrowse(550,'$page?move-category-popup=yes&website='+website+'&zmd5='+zmd5+'&YahooWin=YahooWinBrowse&category-source='+category+'&table-source='+category_table,'$movetext::'+website);
	}

	function MoveAllCategorizedWebsite(){
		YahooWinBrowse(550,'$page?move-category-popup=yes&YahooWin=YahooWinBrowse&website=&zmd5=&category-source={$_GET["category"]}&table-source=$table&bysearch={$_GET["search"]}','$movetext::{$_GET["search"]}');
		
	}
	
	function MoveAllCategorizedWebsite2(category,table,search){
		YahooWinBrowse(550,'$page?move-category-popup=yes&YahooWin=YahooWinBrowse&website=&zmd5=&category-source='+category+'&table-source='+table+'&bysearch='+search+'&t=$t','$movetext::'+search);
		
	}

		var x_RemoveDisabled$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			FlexReloadWebsiteCategoriesManage();
			
		}		
	
	function RemoveDisabled$t(){
		var categoryZ = '{$_GET["category"]}'
		
		
		
		if(categoryZ.length==0){
			YahooWin5('550','$page?RemoveDisabled-popup=yes','$removedisabled');
			return;
		}
		if(confirm('$removedisabled_warn:'+categoryZ)){
			var XHR = new XHRConnection();
			XHR.appendData('RemoveDisabled',categoryZ);
			XHR.sendAndLoad('$page', 'POST',x_RemoveDisabled$t);
		}
	}
	
	
		var x_DeleteCategorizedWebsite= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+MEMMD).remove();
		}	

	function FlexReloadWebsiteCategoriesManage(){
		$('#$t').flexReload();
	}		
	
	function ReallyDeleteCategorizedWebsite(zmd5,table){
		MEMMD=zmd5;
		var XHR = new XHRConnection();
		XHR.appendData('ReallyDeleteCategorizedWebsite',zmd5);
		XHR.appendData('TABLE',table);
		XHR.sendAndLoad('$page', 'POST',x_DeleteCategorizedWebsite);	
	}	
	
	function DeleteCategorizedWebsite(zmd5,table){
		MEMMD=zmd5;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteCategorizedWebsite',zmd5);
		XHR.appendData('TABLE',table);
		XHR.sendAndLoad('$page', 'POST',x_DeleteCategorizedWebsite);	
	}	

function AddCatz(){
	Loadjs('dansguardian2.databases.php?add-perso-cat-js=yes');
}
</script>
";	
}

function removedisabled_perform(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$category=$_POST["RemoveDisabled"];
	$table="category_".$q->category_transform_name($category);
	$sql="DELETE FROM `$table` WHERE `enabled`=0";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("OPTIMIZE TABLE `$table`");
}

function query(){
	
	
	$tpl=new templates();
	$also=$tpl->_ENGINE_parse_body("{also}");
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$nowebsites=$tpl->_ENGINE_parse_body("{no_saved_web_site_catz}");
	$category=null;
	
	if($category==null){if($_GET["category"]<>null){$category=$_GET["category"];$_POST["qtype"]="pattern";}}
	if($category==null){if($_POST["qtype"]<>null){$category=$_POST["qtype"];$_POST["qtype"]="pattern";}}	
	writelogs("Category:$category",__FUNCTION__,__FILE__,__LINE__);
	
	if($_POST["query"]<>null){if($_GET["website"]<>null){$_POST["query"]=$_GET["website"];}}
	if($category==null){json_error_show("Please select a category first");}
	if($_POST["sortname"]=="sitename"){$_POST["sortname"]="zDate";$_POST["sortorder"]="desc";}
	
	writelogs("Category:$category",__FUNCTION__,__FILE__,__LINE__);
	$table="category_".$q->category_transform_name($category);
	$search='%';
	$page=1;
	$COUNT_ROWS=$q->COUNT_ROWS($table);
	
	if($COUNT_ROWS==0){json_error_show("category:$category ,Table: $table, $nowebsites",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$_POST["query"]=trim($_POST["query"]);
	
	
	$searchstring=string_to_flexquery();
	
	
	
	
	if($searchstring<>null){
		$orgQuery=$_POST["query"];
		$sql="SELECT COUNT(zmd5) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT zDate,zmd5,pattern,enabled  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	if(mysql_num_rows($results)==0){json_error_show("$nowebsites",1);}
	$disabled_text=$tpl->_ENGINE_parse_body("{disabled}");
	$CategoriesCheckRightsWrite=CategoriesCheckRightsWrite();
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if($orgQuery<>null){$moveAll=imgtootltip("arrow-multiple-right-24.png","{move}","MoveAllCategorizedWebsite2('$category','$table','$orgQuery')");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgtootltip("delete-24.png","{delete}","DeleteCategorizedWebsite('{$ligne["zmd5"]}','$table')");
		$move=imgtootltip("arrow-right-24.png","{move}","MoveCategorizedWebsite('{$ligne["zmd5"]}','{$ligne["pattern"]}','$category','$table')");
		
		
		$added=null;
		/*$categories=$q->GET_CATEGORIES($ligne["pattern"],true,true,true);
		writelogs("{$ligne["pattern"]}= $categories ",__FUNCTION__,__FILE__,__LINE__);
		$categories=str_replace($category, "", $categories);
		$ff=explode(",",$categories);
		$tt=array();
		while (list ($a, $b) = each ($ff) ){if(trim($b)==null){continue;}$tt[]=	$b;}
		if(count($tt)>0){
			$added="<div><i style='font-size:11px'>$also: ".@implode(", ", $tt)."</i></div>";
		}
		*/
		$enabled=$ligne["enabled"];
		$color="color:black";
		if($enabled==0){
			$color="color:#B6ACAC";
			$added=$added."<div><i style='font-size:11px'>$disabled_text</i></div>";
			$moveAll="&nbsp;";
			$move="&nbsp;";
			$delete=imgtootltip("delete-24.png","{delete}","ReallyDeleteCategorizedWebsite('{$ligne["zmd5"]}','$table')");
		}
		
$jscat="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.categorize.php?www={$ligne["pattern"]}');\"
		style='font-size:14px;text-decoration:underline;$color'>";	


if(!$CategoriesCheckRightsWrite){
	$jscat=null;
	$moveAll=null;
	$move=null;
	$delete=null;
}
		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array("
		<span style='font-size:16px;$color'>{$ligne['zDate']}</span>",
		"<span style='font-size:16px;$color'>$jscat{$ligne['pattern']}</a></span>$added",$moveAll,
		$move,$delete)
		);
	}
	
	
echo json_encode($data);	
	return;
	$page=CurrentPageName();
	$tpl=new templates();	
	$category=$_GET["category"];
	
	if($category==null){return;}
	$q=new mysql_squid_builder();
	$tableN="category_".$q->category_transform_name($category);
	$strictSearch=$_GET["strictSearch"];
	if(!is_numeric($strictSearch)){$strictSearch=0;}
	$searchOrg=$_GET["search"];
	if($_GET["search"]<>null){
		$search=$_GET["search"];
		if($strictSearch==0){$search="*$search*";}
		$search=str_replace("**", "*", $search);
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", "%", $search);
		$search=" AND pattern LIKE '$search'";
	}
	
	$sql="SELECT * FROM $tableN WHERE enabled=1 $search ORDER BY pattern LIMIT 0,25 ";
	
	$table="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:350px'>
<thead class='thead'>
	<tr>
	<th width=1%>{website} $category</th>
	<th>{date}</th>
	<th>$moveall</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody>";
	
	$results=$q->QUERY_SQL($sql);
	
	$number=mysql_num_rows($results);
	if($number==0){
		if($searchOrg<>null){
			if(strpos($searchOrg, "*")==0){
		$html="
		<center style='margin:30px'>
		<div style='font-size:16px;margin-bottom:15px'><code>&laquo;http://$searchOrg&raquo; {in} &laquo;$category&raquo;</code></div>
		<div style='font-size:14px'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.visited.php?add-www=yes&websitetoadd=$searchOrg')\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'
		>{webiste_notfound_additask}</a>
		</div>
		</center>
		<script>LoadAjaxTiny('subtitles-categories','$page?subtitles-categories=yes');</script>
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		}
		
		}
		
	}
	
	
	
	
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}		
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["pattern"]==null){$q->QUERY_SQL("DELETE FROM $tableN WHERE zmd5='{$ligne["zmd5"]}'");continue;}
		

		
		$jscat="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.categorize.php?www={$ligne["pattern"]}');\"
		style='font-size:14px;text-decoration:underline'>";
		$table=$table."
		<tr class=$classtr>
		<td style='width:1%;font-size:14px' nowrap>{$ligne["zDate"]}</td>
		<td style='width:1%;font-size:14px' nowrap>$jscat{$ligne["pattern"]}</a></td>
		<td>$move</td>
		<td>$delete</td>
		</tr>
		";
		
	}
	$table=$table."</tbody></table>
	<script>
	
	

	

	
	LoadAjaxTiny('subtitles-categories','$page?subtitles-categories=yes');
	
</script>	
	";
	echo $tpl->_ENGINE_parse_body($table);
	
}

function MoveCategory_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$category=$_GET["category-source"];	
	$movetext=$tpl->javascript_parse_text("{move}");
	$webiste=$_GET["website"];
	$tableN=$_GET["table-source"];
	$zmd5=$_GET["zmd5"];
	$button=button("{move}","MoveCategoryPerform()");
	if(isset($_GET["bysearch"])){
		$button=button("{move}&nbsp;{all}","MoveAllCategoryPerform()",16);
		
	}
	
	$Final="YahooWin5Hide";
	if(isset($_GET["YahooWin"])){$Final="{$_GET["YahooWin"]}Hide";}
	
	
	$html="
	<div id='move-category-div'>
	<div class=explain>{move_category_explain}</div>
	<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend>{category}:</td>
				<td><div id='catsmove_list'></div></td>
				<td width=1%>$button</td>
			</tr>
		</tbody>
	</table>
	<span id='catmove-explain'></span>
	
	</div>

	<script>
		var xmd5='';
		
		var x_MoveCategoryPerform= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			if(document.getElementById('FlexReloadWebsiteCategoriesManageDiv')){FlexReloadWebsiteCategoriesManage();}
			$Final();
		}		
		
		function MoveCategoryPerform(){
			var nextCategory=document.getElementById('CategoryNext').value;
			if(confirm('$movetext $webiste/$category ->  $webiste/'+nextCategory+'?')){
				var XHR = new XHRConnection();
				XHR.appendData('MoveCategorizedWebsite','$zmd5');
				XHR.appendData('TABLE','$tableN');
				XHR.appendData('NextCategory',nextCategory);
				XHR.appendData('website','$webiste');
				AnimateDiv('move-category-div');
				XHR.sendAndLoad('$page', 'POST',x_MoveCategoryPerform);				
			}
		
		}
		
		function MoveAllCategoryPerform(){
			var nextCategory=document.getElementById('CategoryNext').value;
			if(confirm('$movetext {$_GET["bysearch"]}/$category ->  {$_GET["bysearch"]}/'+nextCategory+'?')){
				var XHR = new XHRConnection();
				XHR.appendData('MoveCategorizedWebsitePattern','{$_GET["bysearch"]}');
				XHR.appendData('TABLE','$tableN');
				XHR.appendData('NextCategory',nextCategory);
				XHR.appendData('website','$webiste');
				AnimateDiv('move-category-div');
				XHR.sendAndLoad('$page', 'POST',x_MoveCategoryPerform);				
			}
		
		}		
	
		function MoveCategoryPerformText(){
			var nextCategory=document.getElementById('CategoryNext').value;	
			LoadAjax('catmove-explain','squid.visited.php?cat-explain='+nextCategory);
		
		}
		
		LoadAjax('catsmove_list','$page?field-list=yes&category=$category&callback=MoveCategoryPerformText&field-name=CategoryNext&callbackAfter=MoveCategoryPerformText');
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function field_list(){
	$page=CurrentPageName();
	$def=$_COOKIE["urlfilter_category_selected"];
	if($_GET["category"]<>null){$def=$_GET["category"];}
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	$dans=new dansguardian_rules();
	while (list ($num, $ligne) = each ($dans->array_blacksites) ){$array[$num]=$num;}
	
	$sql="SELECT categorykey  FROM `webfilters_categories_caches`";
	$results = $q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$array[$ligne['categorykey']]=$ligne['categorykey'];
	}
	
	$array[null]="{select}";
	
	ksort($array);
	$callback="SearchByCategory";
	$callbackjs="<script>$callback();</script>";
	if(isset($_GET["time"])){$time="-{$_GET["time"]}";}
	$fieldname="category_selected$time";
	if($_GET["field-name"]<>null){$fieldname=$_GET["field-name"];}
	if($_GET["callback"]<>null){$callback=$_GET["callback"];}
	if($_GET["callbackAfter"]<>null){$callbackjs="<script>{$_GET["callbackAfter"]}();</script>";}
	$html=Field_array_Hash($array, $fieldname,$def,"$callback()",null,0,"font-size:16px");
	echo $tpl->_ENGINE_parse_body($html."$callbackjs");

}
function DeleteCategorizedWebsite(){
	$q=new mysql_squid_builder();
	$md5=$_POST["DeleteCategorizedWebsite"];
	
	$table=$_POST["TABLE"];
	$sql="SELECT * FROM $table WHERE zmd5='$md5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;return;}
	
	writelogs("DELETE $md5 {$ligne["pattern"]} {$ligne["category"]}",__FUNCTION__,__FILE__,__LINE__);
	
	$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('{$ligne["pattern"]}','{$ligne["category"]}','{$ligne["zmd5"]}')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE $table SET enabled=0 WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?export-deleted-categories=yes");
}
function DeleteCategorizedWebsiteReally(){
	$q=new mysql_squid_builder();
	$md5=$_POST["ReallyDeleteCategorizedWebsite"];
	$table=$_POST["TABLE"];
	$q->QUERY_SQL("DELETE FROM $table WHERE zmd5='$md5'");
}

function MoveCategorizedWebsite($md5=null,$nextCategory=null,$table=null){
	$q=new mysql_squid_builder();
	$sock=new sockets();
	if($md5==null){$md5=$_POST["MoveCategorizedWebsite"];}
	if($nextCategory==null){$nextCategory=trim($_POST["NextCategory"]);}
	if($table==null){$table=trim($_POST["TABLE"]);}
	
	
	if($nextCategory==null){echo "Next category = Null\n";return;}
	if($table==null){echo "Table = Null\n";return;}
	if($md5==null){echo "md5 = Null\n";return;}
	
	
	if(!isset($GLOBALS["uuid"])){$GLOBALS["uuid"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));}
	$uuid=$GLOBALS["uuid"];
	
	$sql="SELECT * FROM $table WHERE zmd5='$md5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;return;}
	$www=$ligne["pattern"];
	$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('{$ligne["pattern"]}','{$ligne["category"]}','{$ligne["zmd5"]}')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE $table SET enabled=0 WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("squid.php?export-deleted-categories=yes");
	
	
	$newmd5=md5($nextCategory.$www);
	$q->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$www','$nextCategory')");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->CreateCategoryTable($nextCategory);
	$category_table=$q->category_transform_name($nextCategory);	
	$q->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$nextCategory','$www','$uuid')");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("INSERT IGNORE INTO category_$category_table (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'$nextCategory','$www','$uuid',1)");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$cats=addslashes($q->GET_CATEGORIES($www,true));
	
	$q->QUERY_SQL("UPDATE visited_sites SET category='$cats' WHERE sitename='$www'");
	if(!$q->ok){echo $q->mysql_error."\n";echo $sql."\n";}	
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?export-community-categories=yes");
	$sock->getFrameWork("squid.php?re-categorize=yes");	

}

function MoveCategorizedWebsiteAll(){

		$q=new mysql_squid_builder();
		$search=trim($_POST["MoveCategorizedWebsitePattern"]);
		if(strlen($search)<4){echo "Wrong query...No search pattern";return;}
		
		$search="*$search*";
		$search=str_replace("**", "*", $search);
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", "%", $search);
		$search=" AND pattern LIKE '$search'";
		$sql="SELECT * FROM {$_POST["TABLE"]} WHERE enabled=1 $search ORDER BY pattern";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){echo "Wrong query...No rows...";return;}
		if(mysql_num_rows($results)>1000){echo "To many webistes to migrate: ".mysql_num_rows($results)." query is wrong...";return;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["pattern"]==null){$q->QUERY_SQL("DELETE FROM {$_POST["TABLE"]} WHERE zmd5='{$ligne["zmd5"]}'");continue;}
			MoveCategorizedWebsite($ligne["zmd5"],$_POST["NextCategory"],$_POST["TABLE"]);
		}		
		
		
		
}

function test_category(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{website}:</td>
		<td width=99%>". Field_text("WEBTESTS-$t",null,"font-size:22px;width:99%;padding:13px;border:2px solid #808080",
	null,null,null,false,"CheckSingleSite$t(event)")."</td>
	</tr>
	</table>
	<div id='answer-$t'></div>
	
	
	</div>
<script>
		function x_CheckSingleSite$t(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){
				document.getElementById('answer-$t').innerHTML=tempvalue;
			}
		}

function CheckSingleSite$t(e){
		if(!checkEnter(e)){return;}
		var XHR = new XHRConnection();
		XHR.appendData('WEBTESTS',document.getElementById('WEBTESTS-$t').value);
		AnimateDiv('answer-$t');
		XHR.sendAndLoad('$page', 'POST',x_CheckSingleSite$t);	

}
</script>


			
";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function test_category_perform(){
	$www=$_REQUEST["WEBTESTS"];
	
	
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$www=$q->WebsiteStrip($www);
	
	if($www==null){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{corrupted_request}: &laquo;{$_REQUEST["WEBTESTS"]}&raquo;</p>");
		return;
	}
	
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();
	
	$catz=$q->GET_CATEGORIES($www,true);
	if($catz==null){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{unknown}: &laquo;{$_REQUEST["WEBTESTS"]}&raquo;</p>");
		return;
	}
	if(strpos(" $catz", ",")>0){$CATs=explode(",", $catz);}else{$CATs[]=$catz;}
	
	$sql="SELECT * FROM personal_categories";
	if(!$q->TABLE_EXISTS("personal_categories")){json_error_show("personal_categories no such table!",1);}
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$PERSONALSCATS[$ligne["category"]]=$ligne["category_description"];}
		
	
	while (list ($num, $categoryname) = each ($CATs) ){
		
		if(!isset($dans->array_blacksites[$categoryname])){
			if(isset($dans->array_blacksites[str_replace("_","-",$categoryname)])){$categoryname=str_replace("_","-",$categoryname);}
			if(isset($dans->array_blacksites[str_replace("_","/",$categoryname)])){$categoryname=str_replace("_","/",$categoryname);}
		}		
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
		$text_category=$dans->array_blacksites[$categoryname];

		if(isset($PERSONALSCATS[$categoryname])){
			$text_category=utf8_encode($PERSONALSCATS[$categoryname]);
			if($pic=="&nbsp;"){$pic="<img src='img/20-categories-personnal.png'>";}

		}
		$js="javascript:Loadjs('squid.categories.php?category=$categoryname&t=$t')";
		$categoryText[]=$tpl->_ENGINE_parse_body("
		<tr>
			<td width=1% nowrap>$pic</td>
			<td valign='top'>
				<div style='font-size:18px';font-weight:bold'>
					<a href=\"javascript:blur();\" OnClick=\"$js\" style='text-decoration:underline'>$categoryname</a>:</div>
				<div style='font-size:16px;width:100%;font-weight:normal'>{$text_category}</div>
			</td>
		</tr>		
		");
		
		
	}
	$found=$tpl->_ENGINE_parse_body("{found}");
	echo "<div style='width:95%:padding-left:50px;padding-top:20px' class=explain><div style='font-size:18px'>$found</div><table>".@implode("\n", $categoryText)."</table></div>";
	
}


