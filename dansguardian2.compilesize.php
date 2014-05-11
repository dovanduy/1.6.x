<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){categories();exit;}
if(isset($_GET["category-search"])){categories_search();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	echo "YahooWin6('750','$page?popup=yes','Databases status')";
	
	
}

function categories(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$purge=$tpl->_ENGINE_parse_body("{purgeAll}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	if($_GET["minisize"]=="yes"){
		$tablewith=625;
		$categorysize=356;
		$delete=null;
		$compilesize="51";
	}
	
	if($_GET["maximize"]=="yes"){
		$tablewith=837;
		$categorysize=515;
		$size_size=72;
	}	
	if($_GET["middlesize"]=="yes"){
		$tablewith=828;
		$size_elemnts=70;
		$size_size=80;
		$categorysize=400;
		$TABLE_ROWS2="{display: 'Artica', name : 'TABLE_ROWS2', width : $size_elemnts, sortable : false, align: 'left'},";
		$artica="&artica=yes";
		
	}
	
	$title=$tpl->_ENGINE_parse_body("{databases_sizes_on_disk}");
	
	$t=time();
	$html="
	<div >
	<table class='dansguardian2-category-$t' style='display: none' id='dansguardian2-category-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#dansguardian2-category-$t').flexigrid({
	url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t$artica',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon1', width : 32, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 350, sortable : true, align: 'left'},
		{display: 'Toulouse', name : 'unitoulouse', width : 91, sortable : true, align: 'left'},
		{display: 'Artica', name : 'articasize', width : 91, sortable : true, align: 'left'},
		{display: 'Perso', name : 'persosize', width : 85, sortable : true, align: 'left'},
		
	],
buttons : [

		],	
	searchitems : [
		{display: '$category', name : 'category'},
		],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 730,
	height: 400,
	singleSelect: true
	
	});   
});

	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	$dans=new dansguardian_rules();	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	$t=$_GET["t"];

	if(isset($_GET["artica"])){$artica=true;}
	$tablename="webfilters_dbstats";
	if(!$q->BD_CONNECT()){json_error_show("Testing connection to MySQL server failed...",1);}
	
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();	
	
	
	$sql="SELECT * FROM personal_categories";
	if(!$q->TABLE_EXISTS("personal_categories")){json_error_show("personal_categories no such table!",1);}

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$PERSONALSCATS[$ligne["category"]]=$ligne["category_description"];}	
	
	
	$search='%';
	$page=1;
	$ORDER="ORDER BY category";
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $tablename WHERE 1 AND $searchstring ";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $tablename";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $tablename $searchstring $ORDER $limitSql";	
	
	writelogs("$q->mysql_admin:$q->mysql_password:$sql",__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
	
	if(mysql_num_rows($results)==0){
		json_error_show("($tablename) No categories table found...",1);
	}	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	$enc=new mysql_catz();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$table=$ligne["c"];
		writelogs("Scanning table $table",__FUNCTION__,__FILE__,__LINE__);
		$select=imgtootltip("32-parameters.png","{edit}","DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		$color="black";
			
		
		
		
		$categoryname=$ligne["category"];
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");

		if(!isset($dans->array_blacksites[$categoryname])){
			if(isset($dans->array_blacksites[str_replace("_","-",$categoryname)])){$categoryname=str_replace("_","-",$categoryname);}
			if(isset($dans->array_blacksites[str_replace("_","/",$categoryname)])){$categoryname=str_replace("_","/",$categoryname);}
		}
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
	

		$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$categoryname}&t=$t',true)\"
		style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		$text_category=$dans->array_blacksites[$categoryname];
		
		
		
		
		if(isset($PERSONALSCATS[$categoryname])){
			$text_category=utf8_encode($PERSONALSCATS[$categoryname]);
			if($pic=="&nbsp;"){$pic="<img src='img/20-categories-personnal.png'>";}
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
			style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		}
		
		
		
		$viewDB=imgsimple("mysql-browse-database-32.png","{view}","javascript:Loadjs('squid.categories.php?category={$categoryname}',true)");		
		
		$categoryText=$tpl->_ENGINE_parse_body("<div style='font-size:14px';font-weight:bold'>$linkcat$categoryname</div>
		</a><div style='font-size:11px;width:100%;font-weight:normal'>{$text_category}</div>");
		$items=numberFormat($items,0,""," ");
		$itemsEnc=numberFormat($itemsEnc,0,""," ");
		$compile=imgsimple("compile-distri-32.png","{saveToDisk} $categoryname","DansGuardianCompileDB('$categoryname')");
		$delete=imgsimple("delete-32.png","{delete}","TableCategoryPurge('$table')");
		


		$articasize=FormatBytes($ligne["articasize"]/1024);
		$unitoulouse=FormatBytes($ligne["unitoulouse"]/1024);
		$persosize=FormatBytes($ligne["persosize"]/1024);
		
		$cell=array();
		$cell[]=$pic;
		$cell[]=$categoryText;
		$cell[]="<div style='font-size:13px;padding-top:15px;font-weight:bold'>$unitoulouse</div>";
		$cell[]="<div style='font-size:13px;padding-top:15px;font-weight:bold'>$articasize</strong>";
		$cell[]="<div style='font-size:13px;padding-top:15px;font-weight:bold'>$persosize</strong>";
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => $cell
		);
	}
	
	
echo json_encode($data);	
	
}
