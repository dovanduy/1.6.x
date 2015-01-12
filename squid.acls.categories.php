<?php
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.catz.inc');

	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["blacklist-list"])){blacklist_list();exit;}
	if(isset($_POST["EnableDisableCategoryACL"])){EnableDisableCategoryACL();exit;}

js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{artica_categories}");
	$html="YahooWin3('920','$page?table=yes&tablet={$_GET["tablet"]}&gpid={$_GET["gpid"]}','$title')";
	echo $html;
}


function table(){
	$users=new usersMenus();
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

	if(!$users->CORP_LICENSE){
		$text_license="<p class=text-error>".$tpl->_ENGINE_parse_body("({category_no_license_explain})")."</p>";
	}
	$description_size=639;


	if(is_numeric($_GET["table-size"])){$TB_WIDTH=$_GET["table-size"];}
	if(is_numeric($_GET["group-size"])){$description_size=$_GET["group-size"];}
	
	$suffix="&tablet={$_GET["tablet"]}&gpid={$_GET["gpid"]}";

	$html="$text_license
<table class='blacklist-table-$t-$d' style='display: none' id='blacklist-table-$t-$d' style='width:99%'></table>
<script>
var CatzByEnable$t=0;
	$(document).ready(function(){
	$('#blacklist-table-$t-$d').flexigrid({
	url: '$page?blacklist-list=yes$suffix',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
	{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
	{display: '$description', name : 'description', width : $description_size, sortable : false, align: 'left'},
	{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},

	],
	buttons : [
	
	{name: '$OnlyActive', bclass: 'Search', onpress : OnlyActive$t},
	{name: '$All', bclass: 'Search', onpress : OnlyAll$t},
	
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

function OnlyActive$t(){
	CatzByEnable$t=1;
	$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes$suffix&CatzByEnabled=yes'}).flexReload(); ExecuteByClassName('SearchFunction');
}
function OnlyAll$t(){
	CatzByEnable$t=0;
	$('#blacklist-table-$t-$d').flexOptions({url: '$page?blacklist-list=yes$suffix'}).flexReload(); ExecuteByClassName('SearchFunction');
}


var xEnableDisableCategoryACL=function(obj){
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	$('#blacklist-table-$t-$d').flexReload();
	$('#{$_GET["tablet"]}').flexReload();
	RefreshAllAclsTables();
	
}

function EnableDisableCategoryACL(pattern){
	var XHR = new XHRConnection();
	XHR.appendData('EnableDisableCategoryACL',pattern);
	XHR.appendData('gpid','{$_GET["gpid"]}');
	XHR.sendAndLoad('$page', 'POST',xEnableDisableCategoryACL);	
}


</script>	";
echo $tpl->_ENGINE_parse_body($html);
}

function EnableDisableCategoryACL(){
	$gpid=$_POST["gpid"];
	$pattern=$_POST["EnableDisableCategoryACL"];
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM `webfilters_sqitems` WHERE `gpid`='$gpid' AND `pattern`='$pattern'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	
	
	if(strlen(trim($ligne["pattern"]))>1){
		$q->QUERY_SQL("DELETE FROM `webfilters_sqitems`  WHERE `gpid`='$gpid' AND `pattern`='$pattern'");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other)  VALUES ('$pattern','$gpid','1','');";
	$q->QUERY_SQL($sqladd);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function blacklist_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$cats=array();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	if(!isset($_GET["group"])){$_GET["group"]=null;}
	$text_license=null;
	$OnlyEnabled=false;
	if(!$users->CORP_LICENSE){
		//$text_license=$tpl->_ENGINE_parse_body("({category_no_license_explain})");
	}
	$search='%';
	$table="webfilters_categories_caches";
	$tableProd="webfilters_sqitems";

	

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
	$sql="SELECT `pattern` FROM webfilters_sqitems WHERE `gpid`={$_GET["gpid"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$cats[$ligne["pattern"]]=true;}
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
		if(!isset($cats[$ligne['categorykey']])){$cats[$ligne['categorykey']]=false;}
		
		if($cats[$ligne['categorykey']]){$val=1;}
		if($OnlyEnabled){if($val==0){continue;}}

		$disable=Field_checkbox("cats_{$ligne['categorykey']}", 1,$val,"EnableDisableCategoryACL('{$ligne['categorykey']}','0','0')");
		$ligne['description']=utf8_encode($ligne['description']);

		$data['rows'][] = array(
				'id' => $ligne['categorykey'],
				'cell' => array("<img src='$img'>","{$ligne['categorykey']}</a>", $ligne['description']."<br>$database_items",$disable)
		);
	}


	echo json_encode($data);


}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}