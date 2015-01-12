<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: DEBUG...()\n<br>";}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.rtmm.tools.inc');
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: Load done...()\n<br>";}
	
	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
		
	}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-start"])){popup_start();exit;}
	if(isset($_GET["categorizer"])){popup_categories();exit;}
	if(isset($_GET["categorizer-list"])){popup_categories_sql();exit;}
	if(isset($_GET["top10"])){popup_top10();exit;}
	if(isset($_GET["top10-list"])){popup_top10_list();exit;}
	if(isset($_GET["top10-users"])){popup_top10_users();exit;}
	
	if(isset($_GET["choose-group"])){choose_group();exit;}
	if(isset($_GET["category"])){save_category();exit;}
	if(isset($_GET["categories-of"])){exit();}
	js();
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$start="CategorizeLoad()";
	
	if(isset($_GET["load-js"])){
		$_GET["www"]=$_GET["load-js"];
		$start="CategorizeLoadAjax()";
		
	}
	
	if(trim($_GET["www"])==null){
			$error_no_website_selected=$tpl->javascript_parse_text("{error_no_website_selected}");
			echo "alert('$error_no_website_selected');";
			return;
	}	
	
	$html="
	function CategorizeLoad(){
		YahooWinBrowse(650,'$page?popup=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&week={$_GET["week"]}','{$_GET["www"]}');
	
	}
	
	function CategorizeLoadAjax(){
		LoadAjax('popup_other_squid_category_webpage','$page?popup=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&week={$_GET["week"]}');
	}
	
	
	var x_DansCommunityCategory= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		if(document.getElementById('tableau-test-categories')){RefreshTableauTestCategories();}
		if(document.getElementById('webalyzer-lock')){return;}
		if(document.getElementById('SQUIDNOCATREFRESHTABLEID')){SQUIDNOCATREFRESHTABLE();}
		if(document.getElementById('FlexReloadWebsiteInfosTablePointer')){FlexReloadWebsiteInfosTable();}
		
		
		
	}		
	
	function DansCommunityCategory(md,category,website){
		var XHR = new XHRConnection();
		XHR.appendData('category',category);
		XHR.appendData('website',website);
		if(document.getElementById(md).checked){
		XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('day','{$_GET["day"]}');
		XHR.appendData('week','{$_GET["week"]}');
		XHR.sendAndLoad('$page', 'GET',x_DansCommunityCategory);	
	}
	
	
	$start;
	
	";
	
	if(isset($_GET["load-js"])){echo "
	<div id='popup_other_squid_category_webpage'></div>
	<script>$html</script>";return;}
	
	echo $html;
	
}



function save_category(){
	if($_GET["website"]==null){return;}
	$www=trim(strtolower(base64_decode($_GET["website"])));
	if(preg_match("#^www\.(.+?)$#i",$www,$re)){$www=$re[1];}
	$category=$_GET["category"];
	$q=new mysql_squid_builder();
	$q->categorize($www, $category);
}







function choose_group(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->create_webfilters_categories_caches();
	$t=$_GET["t"];
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$sql="SELECT master_category FROM webfilters_categories_caches GROUP BY master_category";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$catsz=$ligne["master_category"];
		$butts[$catsz]=$catsz;
	}		
	
	$butts[null]="{all}";	
	$field=Field_array_Hash($butts, "CatzByGroup-$t",null,"RefreshConfigCategorized$t()",null,0,"font-size:16px");
	
	$html="<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{group}:</td>
		<td>". $field."</td>
	</tr>
	</table>
	<script>
	function RefreshConfigCategorized$t(){
	
		var sdate=document.getElementById('CatzByGroup-$t').value;
		$('#dansguardian2-category-$t').flexOptions({url: '$page?categorizer-list=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group='+sdate}).flexReload();
		WinORGHide();
	}
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function popup(){
	$page=CurrentPageName();
	$t=time();

	$html="<div id='$t'></div>
	<script>
		$('#categorizer-table').remove();
		LoadAjax('$t','$page?popup-start=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}&row-explain={$_GET["row-explain"]}&table-size={$_GET["table-size"]}');
	</script>
	
	";
	
	echo $html;
	
}

function popup_start(){
	$tpl=new templates();
	$TB_WIDTH=628;
	$TD_DESC=396;
	$page=CurrentPageName();
	
	if(trim($_GET["www"])==null){
		$error_no_website_selected=$tpl->javascript_parse_text("{error_no_website_selected}");
		echo "<script>alert('$error_no_website_selected');YahooWinBrowseHide();</script>";
		return;
	}
	
	if(is_numeric($_GET["table-size"])){$TB_WIDTH=$_GET["table-size"];}
	if(is_numeric($_GET["row-explain"])){$TD_DESC=$_GET["row-explain"];}
	
	$t=time();
	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$categorize=$tpl->_ENGINE_parse_body("{categorize}");
	$OnlyEnabled=$tpl->_ENGINE_parse_body("{OnlyEnabled}");
	$All=$tpl->_ENGINE_parse_body("{all}");
	$group=$tpl->_ENGINE_parse_body("{group}");
$html="
	<table class='dansguardian2-category-$t' style='display: none' id='dansguardian2-category-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#dansguardian2-category-$t').flexigrid({
	url: '$page?categorizer-list=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
		{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : $TD_DESC, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_category', bclass: 'Catz', onpress : AddCatz},
	{name: '$OnlyEnabled', bclass: 'Search', onpress : OnlyEnabled},
	{name: '$All', bclass: 'Search', onpress : AllBack},
	{name: '$group', bclass: 'Search', onpress : AllGroups},
		],	
	searchitems : [
		{display: '$category', name : 'categorykey'},
		{display: '$description', name : 'description'},
		{display: '$group', name : 'master_category'},
		],
	sortname: 'categorykey',
	sortorder: 'asc',
	usepager: true,
	title: '$categorize::{$_GET["www"]}',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 400,
	singleSelect: true
	
	});   
});

function AddCatz(){
	Loadjs('dansguardian2.databases.php?add-perso-cat-js=yes&t=$t');
}
function OnlyEnabled(){
	$('#dansguardian2-category-$t').flexOptions({url: '$page?categorizer-list=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}&OnlyEnabled=1'}).flexReload(); 
}
function AllBack(){
	$('#dansguardian2-category-$t').flexOptions({url: '$page?categorizer-list=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}'}).flexReload(); 
}

function AllGroups(){
	LoadWinORG('550','$page?choose-group=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}','$category&raquo;$group');
}


</script>
";	
	
	echo $html;
	
}

function popup_categories_sql(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: mysql_squid_builder()\n<br>";}
	$q=new mysql_squid_builder();
	$OnlyEnabled=false;
	if(isset($_GET["OnlyEnabled"])){$OnlyEnabled=true;}
	$search='%';
	$table="webfilters_categories_caches";
	$page=1;
	$ORDER="ORDER BY categorykey ASC";
	if(!$q->TABLE_EXISTS($table)){
		if($GLOBALS["VERBOSE"]){echo "<H2>Create create_webfilters_categories_caches()</H2>\n";}
		$q->create_webfilters_categories_caches();
	}
	
	$FORCE_FILTER=null;
	if(trim($_GET["group"])<>null){$FORCE_FILTER=" AND master_category='{$_GET["group"]}'";}
	if($GLOBALS["VERBOSE"]){echo "<h2>".__FUNCTION__."::".__LINE__.":: q->COUNT_ROWS($table)</h2>\n<br>";}
	
	
	if($q->COUNT_ROWS($table)==0){
		$ss=new dansguardian_rules();
		$ss->CategoriesTableCache();
	}
	
	$www=trim(strtolower($_GET["www"]));
	
	$ArticaDBZ=new mysql_catz();
	$CategoriesFound=$ArticaDBZ->GET_CATEGORIES($www);
	$catArDB=explode(",", $CategoriesFound);
	
	writelogs("ArticaDB($www) = ".@implode(",", $catArDB),__FUNCTION__,__FILE__,__LINE__);
	if(is_array($catArDB)){while (list ($num, $ligne) = each ($catArDB) ){$ligne=trim($ligne);if($ligne==null){continue;}$hash_ARTICA[$ligne]=true;}}
	
	
	if(preg_match("#www\.(.+?)$#i",$www,$re)){$www=$re[1];}
	$q=new mysql_squid_builder();
	
	$CategoriesFound=$q->GET_CATEGORIES($www,true,true,true,true);
	$cats=explode(",", $CategoriesFound);
	$www_encoded=base64_encode($_GET["www"]);
	
	$COUNT_ROWS=$q->COUNT_ROWS($table);
	$hash_community=array();
	if(is_array($cats)){while (list ($num, $ligne) = each ($cats) ){$ligne=trim($ligne);if($ligne==null){continue;}$hash_community[$ligne]=true;}}
	if($COUNT_ROWS==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$_POST["query"]=trim($_POST["query"]);
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $COUNT_ROWS;
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	if($OnlyEnabled){$limitSql=null;}
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne['categorykey']=="phishtank"){continue;}
		$DISABLED=false;
		if($ligne["picture"]==null){$ligne["picture"]="20-categories-personnal.png";}
		$TextInterne=null;
		$img="img/{$ligne["picture"]}";
		$val=0;
		if($hash_community[$ligne['categorykey']]){$val=1;}
		if($hash_ARTICA[$ligne['categorykey']]){
			$TextInterne=$tpl->_ENGINE_parse_body("<div style='color:#D01313;font-size:11px;font-style:italic'>{categorized_in_articadb}</div>");
			$val=1;
			$DISABLED=true;
		}
		$md=md5($ligne['categorykey']);
		
		
		
		if($OnlyEnabled){
			if($val==0){
				if($TextInterne==null){
					
					continue;
				}
			}
		}
		$c++;
		
		
		$js="DansCommunityCategory('$md','{$ligne["categorykey"]}','$www_encoded')";
		$disable=Field_checkbox($md, 1,$val,"$js",null,$DISABLED);
		
		$ligne['description']=utf8_encode($ligne['description']);
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['categorykey'],
		'cell' => array("<img src='$img'>","{$ligne['categorykey']}", $TextInterne.$ligne['description'],$disable)
		);
	}
	
	if($OnlyEnabled){
		$data['total'] = $c;
	}
	
	
echo json_encode($data);
}
?>