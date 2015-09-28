<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["categories-table"])){category_table();exit;}
if(isset($_GET["blacklist-list"])){category_group_blacklist();exit;}
if(isset($_POST["EnableDisableCategoryRule"])){category_group_blacklist_save();exit;}

js();


function js(){
	$ID=$_GET["ID"];
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_blkgp WHERE `ID`='$ID'"));
	$ligne["groupname"]=utf8_encode($ligne["groupname"]);
	$title=$tpl->javascript_parse_text("{group}:{$ligne["groupname"]}");
	$YahooWin="YahooWin5";
	echo "$YahooWin('933','$page?categories-table=yes&ID=$ID&t={$_GET["t"]}&tSource={$_GET["tSource"]}','$title');";

}

function category_table(){
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
	$tSource=$_GET["tSource"];
	$t=$_GET["t"];
	$TB_WIDTH=897;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$group=$_GET["group"];
	if(isset($_GET["CatzByEnabled"])){$CatzByEnabled="&CatzByEnabled=yes";}
	$t=$_GET["t"];
	$tt=time();
	$d=time();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_blkgp WHERE `ID`='$ID'"));
	$ligne["groupname"]=utf8_encode($ligne["groupname"]);
	$title=$tpl->javascript_parse_text("{group}:{$ligne["groupname"]}");	
	
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
<script>
var CatzByEnable$t=0;
$(document).ready(function(){
$('#flexRT$tt').flexigrid({
	url: '$page?blacklist-list=yes&ID=$ID&t=$tt',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
		{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 668, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],
	
buttons : [
	{name: '$OnlyActive', bclass: 'Search', onpress : OnlyActive$t},
	{name: '$All', bclass: 'Search', onpress : OnlyAll$t},
	
		],		
	
	searchitems : [
		{display: '$category', name : 'categorykey'},
		{display: '$description', name : 'description'},
		],
	sortname: 'categorykey',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 90,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 500,
	singleSelect: true
	
	});   
});
function OnlyActive$t(){
	$('#flexRT$tt').flexOptions({url: '$page?blacklist-list=yes&ID=$ID&t=$tt&OnLyActive=1'}).flexReload(); ExecuteByClassName('SearchFunction'); 
}
function OnlyAll$t(){
	$('#flexRT$tt').flexOptions({url: '$page?blacklist-list=yes&ID=$ID&t=$tt&OnLyActive=0'}).flexReload(); ExecuteByClassName('SearchFunction');
}


var xEnableDisableCategoryRule$tt=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#flexRT$t').flexReload();
		if(document.getElementById('WebFilteringMainTableID') ){ $('#'+document.getElementById('WebFilteringMainTableID').value).flexReload(); }
		$('#flexRT$tt').flexReload();
		$('#flexRT$tSource').flexReload();
		
		
		ExecuteByClassName('SearchFunction');
		
    }	  

function EnableDisableCategoryRule$tt(category){
      var XHR = new XHRConnection();
      XHR.appendData('EnableDisableCategoryRule',category);
      XHR.appendData('groupid',$ID);
      XHR.sendAndLoad('$page', 'POST',xEnableDisableCategoryRule$tt);
      
      }
      



</script>	";
echo $tpl->_ENGINE_parse_body($html);	

}

function category_group_blacklist_save(){
	$ID=$_POST["groupid"];
	$category=$_POST["EnableDisableCategoryRule"];
	
	
	
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `category` FROM webfilter_blkcnt WHERE `category`='$category' AND `webfilter_blkid`='$ID'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$value=1;
	if($ligne["category"]<>null){
		$q->QUERY_SQL("DELETE FROM `webfilter_blkcnt` WHERE `category`='$category' AND `webfilter_blkid`='$ID'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}else{
		$q->QUERY_SQL("INSERT IGNORE INTO `webfilter_blkcnt` (`category`,`webfilter_blkid`) VALUES ('$category','$ID')");
		if(!$q->ok){echo $q->mysql_error;return;}		
	}
	
	
}



function category_group_blacklist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$text_license=null;
	$search='%';
	$table="webfilters_categories_caches";
	$tableProd="webfilter_blkcnt";
	$t=$_GET["t"];
	$GroupID=$_GET["ID"];
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

	if($_GET["OnLyActive"]==1){$OnLyActive=true;}
	$page=1;
	$ORDER="ORDER BY categorykey ASC";
	$FORCE_FILTER=null;
	if(trim($_GET["group"])<>null){
		$FORCE_FILTER=" AND master_category='{$_GET["group"]}'";
	}
	
	$count_webfilters_categories_caches=$q->COUNT_ROWS("webfilters_categories_caches");
	if($count_webfilters_categories_caches==0){$ss=new dansguardian_rules();$ss->CategoriesTableCache();}

	if(!$q->TABLE_EXISTS($tableProd)){$q->CheckTables();}
	$sql="SELECT `category` FROM $tableProd WHERE `webfilter_blkid`=$GroupID";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$cats[$ligne["category"]]=true;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$searchstring=string_to_flexquery();
	if($OnLyActive){$limitSql=null;}

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

	
	$sql="SELECT *  FROM `webfilters_categories_caches` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No entry");}

	$items=$tpl->_ENGINE_parse_body("{items}");
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$catz=new mysql_catz();

	

	$c=0;
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
		



		if(count($DBTXT)>0){
			$database_items="<span style='font-size:11px;font-weight:bold'>".@implode("&nbsp;|&nbsp;", $DBTXT)."</span>";
		}

		$img="img/{$ligne["picture"]}";
		$val=0;
		if($cats[$ligne['categorykey']]){$val=1;}
		if($OnLyActive){if($val==0){continue;}}
		$c++;

		$disable=Field_checkbox("cats_{$_GET['RULEID']}_{$_GET['modeblk']}_{$ligne['categorykey']}", 1,$val,"EnableDisableCategoryRule$t('{$ligne['categorykey']}')");


		$data['rows'][] = array(
				'id' => $ligne['categorykey'],
				'cell' => array("<img src='$img'>","$js{$ligne['categorykey']}</a>", $ligne['description']."<br>$database_items",$disable)
		);
	}

	if($OnLyActive){$data['total'] = $c;}

	echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}