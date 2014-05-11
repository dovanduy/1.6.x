<?php
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
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["field-list"])){field_list();exit;}
	if(isset($_GET["query"])){query();exit;}
	if(isset($_POST["LinkGroup"])){LinkGroup();exit;}
	if(isset($_POST["DeleteItem"])){UnlinkGroup();exit;}
	if(isset($_POST["allowrecompile"])){allowrecompile();exit;}
js();	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$width=950;
	$title=$tpl->_ENGINE_parse_body("{categories}");
	if($_GET["category"]<>null){$title=$title."::{$_GET["category"]}";$width=720;}
	if($_GET["website"]<>null){
		if(preg_match("#^www\.(.+)#", $_GET["website"],$re)){$_GET["website"]=$re[1];}
		$title=$title."::{$_GET["website"]}";
		$width=860;
	}
	$start="YahooWin4('$width','$page?tabs=yes&category={$_GET["category"]}&website={$_GET["website"]}','$title');";
	$html="
	$start
	";
	echo $html;
	
}


function LinkGroup(){
	$category=$_POST["category"];
	$category=$_POST["catz"];
	$GroupName=$_POST["LinkGroup"];
	$gid=stripslashes($_POST["gid"]);
	$pattern="$GroupName:".base64_encode($gid);
	
	
	$zmd5=md5("$category$pattern");
	$q=new mysql_squid_builder();
	$sql="INSERT IGNORE INTO `webfilter_catprivs` (zmd5,categorykey,groupdata,allowrecompile)
	VALUES ('$zmd5','$category','$pattern','0')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function allowrecompile(){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT allowrecompile FROM `webfilter_catprivs` WHERE zmd5='{$_POST["allowrecompile"]}'"));
	if($ligne["allowrecompile"]==1){
		$q->QUERY_SQL("UPDATE webfilter_catprivs SET allowrecompile=0 WHERE `zmd5`='{$_POST["allowrecompile"]}'");
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		$q->QUERY_SQL("UPDATE webfilter_catprivs SET allowrecompile=1 WHERE `zmd5`='{$_POST["allowrecompile"]}'");
		if(!$q->ok){echo $q->mysql_error;}
	}
}

function UnlinkGroup(){
	
	$DeleteItem=$_POST["DeleteItem"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_catprivs WHERE `zmd5`='$DeleteItem'");
	if(!$q->ok){echo $q->mysql_error;}
}




function popup(){
	$category=$_GET["category"];
	$tpl=new templates();
	$TB_WIDTH=915;
	
	$users=new usersMenus();
	$table_title=$tpl->_ENGINE_parse_body("{category} $category:: {permissions}");
	
	if(isset($_GET["tablesize"])){$TB_WIDTH=$_GET["tablesize"];}
	$page=CurrentPageName();
	$t=time();
	$groups=$tpl->_ENGINE_parse_body("{groups2}"); 
	$link_group=$tpl->_ENGINE_parse_body("{link_group}");
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$q=new mysql_squid_builder();
	
	$category_text=$tpl->_ENGINE_parse_body("{category}");
	$q=new mysql_squid_builder();
	$category=$_GET["category"];
	if($q->COUNT_ROWS("webfilter_catprivs")==0){
		$q->QUERY_SQL("DROP TABLE webfilter_catprivs");
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `webfilter_catprivs` ( `zmd5` VARCHAR(90) NOT NULL, `categorykey` VARCHAR(128) NOT NULL, `groupdata` VARCHAR(255) NOT NULL, `allowrecompile` smallint(1) NOT NULL DEFAULT 0, PRIMARY KEY `zmd5` (`zmd5`) ) ENGINE = MYISAM;");
	}
	

	
	if($_GET["middlesize"]=="yes"){$TB_WIDTH=915;}

$buttons="buttons : [
	{name: '$link_group', bclass: 'Add', onpress : LinkGroup$t},
	],";
		
$searchitem="	searchitems : [
	{display: '$groups', name : 'groupdata'}
],";
		
		


$rowebsite=461;
if(isset($_GET["rowebsite"])){$rowebsite=$_GET["rowebsite"];$rowebsite=$rowebsite-40;}
$categoryencoded=urlencode($_GET["category"]);
	$CATEGORIES_PRIVS_EXPLAIN=$tpl->_ENGINE_parse_body("{CATEGORIES_PRIVS_EXPLAIN}");
echo "
<div class=explain style='font-size:14px'>$CATEGORIES_PRIVS_EXPLAIN</div>
<table class='$t' style='display: none' id='$t' style='width:99%;'></table>

<script>
var MEMMD$t='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes&category=$categoryencoded&t=$t',
	dataType: 'json',
	colModel : [
			{display: '$groups', name : 'groupdata', width : 554, sortable : true, align: 'left'},
			{display: '$compile', name : 'allowrecompile', width : 40, sortable : true, align: 'center'},		
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
$searchitem
	sortname: 'groupdata',
	sortorder: 'asc',
	usepager: true,
	title: '$table_title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 300,
	singleSelect: true
	
	});   
});

	function LinkGroup$t(){
		Loadjs('MembersBrowse.php?OnlyGroups=1&t=$t&callback=LinkGroupDB$t');
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
	
	var xLinkGroupDB$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#$t').flexReload();
	}	
		
	function LinkGroupDB$t(num,prepend,gid){
		var XHR = new XHRConnection();
		XHR.appendData('catz','{$_GET["category"]}');
		XHR.appendData('LinkGroup',num);
		XHR.appendData('gid',gid);
		XHR.sendAndLoad('$page', 'POST',xLinkGroupDB$t);
		
	
	
	}

	var xallowrecompile$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		
	}		
	
	
	function allowrecompile$t(zmd5){
		var XHR = new XHRConnection();
		XHR.appendData('allowrecompile',zmd5);
		XHR.sendAndLoad('$page', 'POST',xallowrecompile$t);	
	
	}
	

	
	
	var xDeleteItem$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+MEMMD$t).remove();
	}	


	

	
	function DeleteItem$t(zmd5){
		MEMMD$t=zmd5;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteItem',zmd5);
		XHR.sendAndLoad('$page', 'POST',xDeleteItem$t);	
	}	

</script>
";	
}


function query(){
	
	$category=null;
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$category=$_GET["category"];
	$table="webfilter_catprivs";
	
	
	if($_POST["query"]<>null){if($_GET["website"]<>null){$_POST["query"]=$_GET["website"];}}
	if($category==null){json_error_show("Please select a category first");}
	if($_POST["sortname"]=="sitename"){$_POST["sortname"]="zDate";$_POST["sortorder"]="desc";}
	
	
	$search='%';
	$page=1;
	$COUNT_ROWS=$q->COUNT_ROWS($table);
	
	if($COUNT_ROWS==0){json_error_show("no data",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	
	
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(zmd5) as TCOUNT FROM `$table` WHERE categorykey='$category' $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE categorykey='$category' $searchstring $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error,0);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$t=$_GET["t"];
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgtootltip("delete-24.png","{delete}","DeleteItem$t('{$ligne["zmd5"]}')");
		$md5=$ligne["zmd5"];
		$groupdata=$ligne["groupdata"];
		preg_match("#^@(.+?):(.+)#", $groupdata,$re);
		$GroupName=$re[1];
		$gpdata=base64_decode($re[2]);
		if(is_numeric($gpdata)){$type="LDAP";}
		if(strpos($gpdata, ",")>0){$type="Active Directory";}
		$allowrecompile=$ligne["allowrecompile"];
		
		$comp=Field_checkbox("C$md5", 1,$allowrecompile,"allowrecompile$t('{$ligne["zmd5"]}')");
		
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("
		<span style='font-size:16px;'>$GroupName ($type)</span>",$comp,
		$delete)
		);
	}
	
	
echo json_encode($data);	

	
}







