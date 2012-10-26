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

if(isset($_GET["instant-update-daily"])){instant_update_daily();exit;}
if(isset($_GET["instant-update-weekly"])){instant_update_weekly();exit;}
if(isset($_POST["enable-clamav-global"])){enable_clamav_global();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["category-search"])){categories_search();exit;}

if(isset($_GET["add-perso-cat-js"])){add_category_js();exit;}
if(isset($_GET["add-perso-cat-popup"])){add_category_popup();exit;}
if(isset($_GET["add-perso-cat-tabs"])){add_category_tabs();exit;}
if(isset($_POST["delete-personal-cat"])){delete_category();exit;}

if(isset($_POST["category_text"])){add_category_save();exit;}

if(isset($_GET["events"])){events();exit;}
if(isset($_GET["updtdb-list-search"])){events_search();exit;}

if(isset($_GET["compile-db-js"])){compile_db_js();exit;}
if(isset($_POST["compile-db-perform"])){compile_db_perform();exit;}

if(isset($_GET["compile-all-dbs-js"])){compile_all_db_js();exit;}
if(isset($_POST["compile-alldbs-perform"])){compile_all_db_perform();exit;}

if(isset($_GET["CheckStatsAppliance"])){CheckStatsAppliance();exit;}
if(isset($_POST["PurgeCategoriesDatabase"])){PurgeCategoriesDatabase();exit;}
if(isset($_POST["PurgeCategoryTable"])){PurgeCategoryTable();exit;}

if(isset($_GET["status"])){global_status();exit;}
if(isset($_GET["global-artica-status-databases"])){global_status_artica_db();exit;}
if(isset($_GET["global-tlse-status-databases"])){global_status_tlse_db();exit;}
if(isset($_GET["global-clamav-status-databases"])){global_clamav_db();exit;}
if(isset($_GET["global-statistics-status-databases"])){global_statistics_db();exit;}




if(isset($_GET["mysql-progress"])){statusDB_list();exit;}

if(isset($_POST["global-artica-status-update"])){global_status_artica_update();exit;}
if(isset($_POST["global-toulouse-status-update"])){global_status_tlse_update();exit;}
if(isset($_POST["global-toulouse-enable-update"])){global_status_tlse_enable();exit;}
if(isset($_POST["global-artica-enable-update"])){global_status_articadb_enable();exit;}
if(isset($_POST["ScanThumbnails"])){ScanThumbnails();exit;}


if(isset($_GET["statusDB"])){statusDB();exit;}

tabs();


function statusDB_list(){
	$tpl=new templates();
	$page=CurrentPageName();
	$date=GetLastUpdateDate();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_updates WHERE updated=0";
	$results = $q->QUERY_SQL($sql);
	$style="style='font-size:14px;font-weight:bold'";
	$html="
	<div style='height:450px;width:100%;overflow:auto'>
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99%'>
<thead class='thead'>
	<tr>
		
		<th width=99%>{category}</th>
		<th width=1%>{zDate}</th>
	</tr>
</thead>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	$ligne["tablename"]=$q->tablename_tocat($ligne["tablename"]);
		$html=$html."
		<tr class=$classtr>
			<td width=99% align='left' $style>{$ligne["tablename"]}</td>
			<td width=1% align='left' $style nowrap>{$ligne["zDate"]}</td>
		</tr>
		";		
		
	}
	$html=$html."</table></div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function delete_category(){
	
	$category=trim($_POST["delete-personal-cat"]);
	if(strlen($category)==0){return;}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$category'");
	if(!$q->ok){echo $q->mysql_error."\nline:".__LINE__."\n";return;}
	$q->QUERY_SQL("DELETE FROM usersisp_catztables WHERE category='$category'");
	if(!$q->ok){echo $q->mysql_error."\nline:".__LINE__."\n";return;}
	$q->QUERY_SQL("DELETE FROM usersisp_blkwcatz WHERE category='$category'");
	if(!$q->ok){echo $q->mysql_error."\nline:".__LINE__."\n";return;}
	$q->QUERY_SQL("DELETE FROM usersisp_blkcatz WHERE category='$category'");
	if(!$q->ok){echo $q->mysql_error."\nline:".__LINE__."\n";return;}
	$q->QUERY_SQL("DROP TABLE category_$category");
	if(!$q->ok){echo $q->mysql_error."\nDROP TABLE category_$category\nline:".__LINE__."\n";return;}
	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
}


function statusDB(){
	$tpl=new templates();
	$page=CurrentPageName();
	$date=GetLastUpdateDate();
	$q=new mysql_catz();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$catz=$q->LIST_TABLES_CATEGORIES();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	$sql="SHOW VARIABLES LIKE '%version%';";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["Variable_name"]=="slave_type_conversions"){continue;}
		$tt[]="	<tr>
					<td colspan=2><div style='font-size:16px'>{$ligne["Variable_name"]}:&nbsp;{$ligne["Value"]}</a></div></td>
				</tr>";
		}
	
	
	
	
	$items=numberFormat($q->COUNT_CATEGORIES(),0,""," ");
	$html="
	<div class=explain>{artica_update_categories_howto}</div>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top'>$APP_ARTICADB</td>
	<td valign='top'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td colspan=2><div style='font-size:16px'>{pattern_database_version}:&nbsp;$date&nbsp</div></td>
	</tr>
	
	<tr>
		<td colspan=2><div style='font-size:16px'>{categories}:&nbsp;".count($catz)."</a></div></td>
		
	</tr>
	<tr>
		<td colspan=2><div style='font-size:16px'>{categorized_websites}:&nbsp;$items&nbsp</div></td>
	</tr>
	".@implode("", $tt)."
	</tbody>
	</table>
	</td>
	</tr>
	</table>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function GetLastUpdateDate(){
	$sock=new sockets();
	return $sock->getFrameWork("squid.php?articadb-version=yes");
}


function compile_db_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ask=$tpl->javascript_parse_text("{confirm_dnsg_compile_db} {$_GET["compile-db-js"]}");
	$html="
	
	
var X_compiledb= function (obj) {
		var results=obj.responseText;
		if(results.length>1){alert(results);}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	}
	
	function compiledb(){
		if(confirm('$ask')){
			var XHR = new XHRConnection();
			XHR.appendData('compile-db-perform','{$_GET["compile-db-js"]}');
			XHR.sendAndLoad('$page', 'POST',X_compiledb);
		
		}
	}
	
	compiledb();
	";
	
	echo $html;
	
}

function compile_all_db_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ask=$tpl->javascript_parse_text("{confirm_dnsg_compileall_db}");
	$html="
	
	
var X_compileAlldbs= function (obj) {
	var results=obj.responseText;
	if(results.length>1){alert(results);}
	if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	}
	
	function compileAlldbs(){
		if(confirm('$ask')){
			var XHR = new XHRConnection();
			XHR.appendData('compile-alldbs-perform','yes');
			XHR.sendAndLoad('$page', 'POST',X_compileAlldbs);
		
		}
	}
	
	compileAlldbs();
	";
	
	echo $html;	
	
}

function compile_db_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-database={$_POST["compile-db-perform"]}");
	}
function compile_all_db_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-alldatabases=yes");	
}



function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$squid=new squidbee();	
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}			
	if($EnableWebProxyStatsAppliance==1){$users->DANSGUARDIAN_INSTALLED=true;$squid->enable_dansguardian=1;}	
	
	
	
	
	
	$array["status"]='{status}';
	$array["categories"]='{categories}';
	
	//$array["events-status"]='{update_status}';
	$array["stats"]='{statistics}';	
	$array["events"]='{events}';
	$array["backup"]='{backup_stats}';	
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="events-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.blacklist.php?status=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="stats"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.statistics.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;			
			
		}
		
		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.admin.events.php?ufdbguard-artica=\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="backup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.stats.backup.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&maximize=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_databasesCAT_quicklinks_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_databasesCAT_quicklinks_tabs').tabs();
			});
		</script>";	

}


function CheckStatsAppliance(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==0){return;}
	$users=new usersMenus();
	if(!$users->APP_UFDBGUARD_INSTALLED){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(ParagrapheTEXT("48-infos.png", "{install_ufdbguard}", "{install_ufdbguard_statappliance}","Loadjs('setup.index.progress.php?product=APP_UFDBGUARD&start-install=yes')"));
	}
	
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
		$categorysize=530;
	}	
	
	
	$t=time();
	$html="
	<div style='margin-left:-15px'>
	<table class='dansguardian2-category-$t' style='display: none' id='dansguardian2-category-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#dansguardian2-category-$t').flexigrid({
	url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon1', width : 32, sortable : false, align: 'left'},
		{display: '$category', name : 'table_name', width : $categorysize, sortable : false, align: 'left'},
		{display: '$size', name : 'category', width : 58, sortable : false, align: 'left'},
		{display: '$items', name : 'TABLE_ROWS', width : 50, sortable : true, align: 'left'},
		{display: 'compile', name : 'icon2', width : $compilesize, sortable : false, align: 'left'},
		$delete
		
	],
buttons : [
	{name: '$addCat', bclass: 'add', onpress : AddNewCategory},
	{name: '$SaveToDisk', bclass: 'Catz', onpress : SaveAllToDisk},
	{name: '$purge', bclass: 'Delz', onpress : PurgeCategoriesDatabase},
		],	
	searchitems : [
		{display: '$category', name : 'table_name'},
		],
	sortname: 'table_name',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $tablewith,
	height: 350,
	singleSelect: true
	
	});   
});


		function AddNewCategory(){
			Loadjs('$page?add-perso-cat-js=yes&t=$t');
		}
		
		function SaveAllToDisk(){
			Loadjs('$page?compile-all-dbs-js=yes')
		
		}
	
		function CategoryDansSearchCheck(e){
			if(checkEnter(e)){CategoryDansSearch();}
		}
		
		function CategoryDansSearch(){
			var se=escape(document.getElementById('category-dnas-search').value);
			LoadAjax('dansguardian2-category-list','$page?category-search='+se);
		
		}
		
		function DansGuardianCompileDB(category){
			Loadjs('$page?compile-db-js='+category);
		}
		
		function CheckStatsApplianceC(){
			LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes');
		}
		
		var X_PurgeCategoriesDatabase= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}
			if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
			if(document.getElementById('squid_categories_zoom')){RefreshTab('squid_categories_zoom');}			
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
		
		function TableCategoryPurge(tablename){
			if(confirm('$purge_catagories_table_explain')){
				var XHR = new XHRConnection();
				XHR.appendData('PurgeCategoryTable',tablename);
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
	$q=new mysql_squid_builder();	
	$dans=new dansguardian_rules();	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	$t=$_GET["t"];
	
	if(!$q->TestingConnection()){json_error_show("Testing connection to MySQL server failed...",1);}
	
	
	$sql="SELECT * FROM personal_categories";
	if(!$q->TABLE_EXISTS("personal_categories")){json_error_show("personal_categories no such table!",1);}

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$PERSONALSCATS[$ligne["category"]]=$ligne["category_description"];}	
	
	
	$search='%';
	$page=1;
	$ORDER="ORDER BY table_name";
	$searchstring="table_name LIKE 'category_%'";
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="table_name LIKE 'category_$search'";
		$sql="SELECT COUNT( table_name ) AS tcount FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_$search'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(table_name) as TCOUNT FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT table_name as c,TABLE_ROWS FROM information_schema.tables WHERE table_schema = 'squidlogs' AND $searchstring $ORDER $limitSql";	
	
	writelogs("$q->mysql_admin:$q->mysql_password:$sql",__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error",1);}
	if(mysql_num_rows($results)==0){json_error_show("No categories table found...",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	$enc=new mysql_catz();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$table=$ligne["c"];
		writelogs("Scanning table $table",__FUNCTION__,__FILE__,__LINE__);
		$select=imgtootltip("32-parameters.png","{edit}","DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		
		$compile=imgtootltip("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");		
		$items=$q->COUNT_ROWS($ligne["c"]);
		$itemsEnc=$enc->COUNT_ROWS($ligne["c"]);
		
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];	


		if(!isset($dans->array_blacksites[$categoryname])){
			if(isset($dans->array_blacksites[str_replace("_","-",$categoryname)])){$categoryname=str_replace("_","-",$categoryname);}
			if(isset($dans->array_blacksites[str_replace("_","/",$categoryname)])){$categoryname=str_replace("_","/",$categoryname);}
		}
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
	
		$sizedb_org=$q->TABLE_SIZE($table);
		$sizedb=FormatBytes($sizedb_org/1024);
		
		
		$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$categoryname}&t=$t')\"
		style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		$text_category=$dans->array_blacksites[$categoryname];
		
		
		
		
		if(isset($PERSONALSCATS[$categoryname])){
			$text_category=utf8_encode($PERSONALSCATS[$categoryname]);
			if($pic=="&nbsp;"){$pic="<img src='img/20-categories-personnal.png'>";}
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t')\"
			style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		}
		
		if($EnableWebProxyStatsAppliance==0){if($sizedb_org<35){$pic="<img src='img/warning-panneau-32.png'>";}}
		
		$viewDB=imgtootltip("mysql-browse-database-32.png","{view}","javascript:Loadjs('squid.categories.php?category={$categoryname}')");		
		
		$categoryText=$tpl->_ENGINE_parse_body("<div style='font-size:14px';font-weight:bold'>$linkcat$categoryname</div>
		</a><div style='font-size:11px;width:100%;font-weight:normal'>{$text_category}</div>");
		$items=numberFormat($items,0,""," ");
		$itemsEnc=numberFormat($itemsEnc,0,""," ");
		$compile=imgtootltip("compile-distri-32.png","{saveToDisk} $categoryname","DansGuardianCompileDB('$categoryname')");
		$delete=imgtootltip("delete-32.png","{delete}","TableCategoryPurge('$table')");
		if($_GET["minisize"]=="yes"){$delete=null;}
		
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"$pic",
		"$categoryText","<div style='font-size:13px;padding-top:15px;font-weight:bold'>$sizedb</div>",
		"<div style='font-size:13px;padding-top:5px;font-weight:bold'>$items<br>$itemsEnc</strong>","$compile",$delete)
		);
	}
	
	
echo json_encode($data);	
	
}



function categories_search2(){

	$search=$_GET["category-search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);	
		
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	$dans=new dansguardian_rules();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	

	
	

	
	$sql="SELECT * FROM personal_categories";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		//$q->CreateCategoryTable($ligne["category"]);
		$PERSONALSCATS[$ligne["category"]]=$ligne["category_description"];
	}
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE 
	table_schema = 'squidlogs' AND table_name LIKE 'category_$search' LIMIT 0,7";
	$results=$q->QUERY_SQL($sql);
	$add=imgtootltip("plus-24.png","{add} {category}","Loadjs('$page?add-perso-cat-js=yes')");
	$compile_all=imgtootltip("compile-distri-32.png","{saveToDisk} {all}","Loadjs('$page?compile-all-dbs-js=yes')");
	if(!$q->ok){echo  " <H2>Fatal Error: $q->mysql_error</H2>";}
	
		
	$sock=new sockets();
	$sock->getFrameWork("ufdbguard.php?db-status=yes");
	$ArraySIZES=unserialize(@file_get_contents("ressources/logs/web/ufdbguard_db_status"));
	

	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$CountDeMembers=0;
		$table=$ligne["c"];
		$sizedb=$ArraySIZES[$table]["DBSIZE"];
		$sizeTXT=$ArraySIZES[$table]["TXTSIZE"];
		
		
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgtootltip("32-parameters.png","{edit}","DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","DansGuardianDeleteMember('{$ligne["ID"]}')");
		$compile=imgtootltip("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");
		$color="black";
		
		$items=$q->COUNT_ROWS($ligne["c"]);
		$TOTAL_ITEMS=$TOTAL_ITEMS+$items;
		
		if(!isset($dans->array_blacksites[$categoryname])){
			if(isset($dans->array_blacksites[str_replace("_","-",$categoryname)])){$categoryname=str_replace("_","-",$categoryname);}
			if(isset($dans->array_blacksites[str_replace("_","/",$categoryname)])){$categoryname=str_replace("_","/",$categoryname);}
		}
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
	
		if($EnableWebProxyStatsAppliance==0){
				if($sizedb==0){$pic="<img src='img/warning-panneau-32.png'>";}
				$sizedb_org=$sizedb;
				$sizedb=FormatBytes($sizedb/1024);
		}else{
			$sizedb_org=$q->TABLE_SIZE($table);
			$sizedb=FormatBytes($sizedb_org/1024);
		}
		
		$sizedb=texttooltip($sizedb,"$sizedb_org bytes",null,null,1,"font-size:14px;font-weight:bold;color:$color");
		
		
	
		$linkcat=null;
		$text_category=$dans->array_blacksites[$categoryname];
		if(isset($PERSONALSCATS[$categoryname])){
			$text_category=$PERSONALSCATS[$categoryname];
			if($pic=="&nbsp;"){$pic="<img src='img/20-categories-personnal.png'>";}
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname')\"
			style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		}
		
		if($EnableWebProxyStatsAppliance==0){
			if($sizedb_org<35){$pic="<img src='img/warning-panneau-32.png'>";}
		}
		$viewDB=imgtootltip("mysql-browse-database-32.png","{view}","javascript:Loadjs('squid.categories.php?category={$categoryname}')");
		$html=$html."
		<tr class=$classtr>
			<td width=1%>$pic</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=99%>
			$linkcat$categoryname</a><div style='font-size:11px;width:100%;font-weight:normal'>{$text_category}</div></td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='right'>$sizedb</td>
			<td width=1%>$viewDB</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='right'>".numberFormat($items,0,""," ")."</td>
			<td width=1%>$compile</td>
			<td width=1%>$delete</td>
		</tr>
		";
	}
	
	$TOTAL_ITEMS=numberFormat($TOTAL_ITEMS,0,""," ");	
	$PurgeDatabase=imgtootltip("database-32-delete.png","{purge_catagories_database_text}","PurgeCategoriesDatabase()");
	
	
	$header="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th width=99%>{category}</th>
		<th width=1%>{size}</th>
		<th width=1% colspan=2>$TOTAL_ITEMS {items}</th>
		<th width=1%>$compile_all</th>
		<th width=1%>$PurgeDatabase</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	

	
	$html=$header.$html."</table>
	</center>
	
	<script>

	</script>
	";
	CACHE_SESSION_SET(__FUNCTION__.$search, __FILE__,$tpl->_ENGINE_parse_body($html));
}

function add_category_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=725;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{add}::{personal_category}");
	if($_GET["cat"]<>null){$title=$tpl->_ENGINE_parse_body("{$_GET["cat"]}::{personal_category}");$widownsize=750;}
	$html="YahooWin5('$widownsize','$page?add-perso-cat-tabs=yes&cat={$_GET["cat"]}&t=$t','$title');";
	echo $html;
}

function add_category_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	$catname=trim($_GET["cat"]);
	
	
	if($_GET["cat"]==null){
		$catname="{new_category}";
	}
	
	$array["add-perso-cat-popup"]=$catname;
	if($_GET["cat"]<>null){
		$array["manage"]='{manage_your_items}';
		$array["category-events"]='{events}';
	}
	
	
	$catzenc=urlencode($_GET["cat"]);
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="manage"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.categories.php?popup=yes&category={$_GET["cat"]}&t=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="category-events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.update.events.php?popup=yes&category=$catzenc&t=$t&tablesize=695&descriptionsize=530\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&t=$t&cat={$_GET["cat"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_zoom_catz style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_zoom_catz').tabs();
			});
		</script>";		
	
	
	
}


function add_category_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$dans=new dansguardian_rules();
	$error_max_dbname=$tpl->javascript_parse_text("{error_max_database_name_no_more_than}");
	$error_category_textexpl=$tpl->javascript_parse_text("{error_category_textexpl}");
	$error_category_nomore5=$tpl->javascript_parse_text("{error_category_nomore5}");
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete_personal_cat_ask}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	$actions="<table style='width:180px' class=form>
				<tr>
					<td width=1%><img src='img/delete-24.png'></td>
					<td width=99%><a href=\"javascript:blur();\" 
					OnClick=\"javascript:DeletePersonalCat$t();\" 
					style='font-size:12px;text-decoration:underline'>{delete_this_category}</a>
					</td>
				</tr>
				<tr>
					<td width=1%><img src='img/database-connect-24-2.png'></td>
					<td width=99%><a href=\"javascript:blur();\" 
					OnClick=\"javascript:CompilePersonalCat$t();\" 
					style='font-size:12px;text-decoration:underline'>{compile_this_category}</a>
					</td>
				</tr>				
		</table>";
	
if($_GET["cat"]==null){$actions=null;}
	
	if($_GET["cat"]<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT category_description,master_category FROM personal_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}else{
		$action=null;
	}
	
	$groups=$dans->LoadBlackListesGroups();
	$groups[null]="{select}";
	$field=Field_array_Hash($groups, "CatzByGroupL",null,$jsnull,null,0,"font-size:16px");
	
	$blacklists=$dans->array_blacksites;
	$description="<textarea name='category_text' id='category_text' style='height:50px;overflow:auto;width:320px;font-size:16px'>"
	.utf8_encode($ligne["category_description"])."</textarea>";
	
	if(isset($blacklists[$_GET["cat"]])){
		$description="<input type='hidden' id='category_text' value=''><div class=explain style='font-size:13px'>{$blacklists[$_GET["cat"]]}</div>";
	}
	
	$html="
	<div id='perso-cat-form'></div>
	<table style='width:100%'>
	<tr>
		<td valign='top' width=99%>
			<table style='width:99%' class=form>
			<tbody>
			<tr>
				<td class=legend style='font-size:16px'>{category}:</td>
				<td>". Field_text("category-to-add","{$_GET["cat"]}","font-size:16px;padding:3px;width:320px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>{description}:</td>
				<td>$description</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>{group}:</td>
				<td>$field</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:16px'>{group} ({add}):</td>
				<td>". Field_text("CatzByGroupA",null,"font-size:16px;padding:3px;width:320px")."</td>
			</tr>	
			
			<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SavePersonalCategory()",16)."</td>
			</tr>
			</tbody>
			</table>
		</td>
		<td valign='top' width=99%>
			$actions
		</td>
		</tr>
		</table>

	<script>
var X_SavePersonalCategory= function (obj) {
		var results=obj.responseText;
		document.getElementById('perso-cat-form').innerHTML='';
		if(results.length>3){alert(results);return;};
		$('#dansguardian2-category-$t').flexReload();
		YahooWin5Hide();
	
	}
		
	function SavePersonalCategory(){
		var XHR = new XHRConnection();
		var db=document.getElementById('category-to-add').value;
		var expl=document.getElementById('category_text').value;
		if(db.length<5){alert('$error_category_nomore5');return;}
		if(expl.length<5){alert('$error_category_textexpl');return;}
		if(db.length>15){alert('$error_max_dbname: 15');return;}
		XHR.appendData('personal_database',db);
		XHR.appendData('category_text',document.getElementById('category_text').value);
		XHR.appendData('CatzByGroupA',document.getElementById('CatzByGroupA').value);
		XHR.appendData('CatzByGroupL',document.getElementById('CatzByGroupL').value);
		AnimateDiv('perso-cat-form');
		XHR.sendAndLoad('$page', 'POST',X_SavePersonalCategory);				
	}
	
	var X_DeletePersonalCat$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('perso-cat-form').innerHTML='';
		if(results.length>3){alert(results);return;};
		$('#dansguardian2-category-$t').flexReload();
		YahooWin5Hide();
	}

	var X_CompilePersonalCat$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('perso-cat-form').innerHTML='';
		if(results.length>3){alert(results);return;};
		$('#dansguardian2-category-$t').flexReload();
		
	}		

	
	function DeletePersonalCat$t(){
		if(confirm('$delete_personal_cat_ask')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-personal-cat','{$_GET["cat"]}');
			AnimateDiv('perso-cat-form');
			XHR.sendAndLoad('$page', 'POST',X_DeletePersonalCat$t);
		}
	
	}
	
	function CompilePersonalCat$t(){
		var XHR = new XHRConnection();
		XHR.appendData('compile-db-perform','{$_GET["cat"]}');
		AnimateDiv('perso-cat-form');
		XHR.sendAndLoad('$page', 'POST',X_CompilePersonalCat$t);
	}
	
	function checkform(){
		var cat='{$_GET["cat"]}';
		if(cat.length>0){document.getElementById('category-to-add').disabled=true;}
	}
checkform();
</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function add_category_save(){
	include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
	$ldap=new clladp();
	$dans=new dansguardian_rules();
	$_POST["personal_database"]=strtolower($ldap->StripSpecialsChars($_POST["personal_database"]));
	
	if($_POST["personal_database"]=="security"){$_POST["personal_database"]="security2";}
	
	if(isset($dans->array_blacksites[$_POST["personal_database"]])){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{category_already_exists}");
		return;
	}
	
	if($_POST["CatzByGroupA"]<>null){$_POST["CatzByGroupL"]=$_POST["CatzByGroupA"];}
	
	$_POST["CatzByGroupL"]=addslashes($_POST["CatzByGroupL"]);
	$_POST["category_text"]=addslashes($_POST["category_text"]);
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM personal_categories WHERE category='{$_POST["personal_database"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]<>null){
		$sql="UPDATE personal_categories 
			SET category_description='{$_POST["category_text"]}',
			master_category='{$_POST["CatzByGroupL"]}'
			WHERE category='{$_POST["personal_database"]}'
			";
	}else{
		$sql="INSERT IGNORE INTO personal_categories (category,category_description,master_category) 
		VALUES ('{$_POST["personal_database"]}','{$_POST["category_text"]}','{$_POST["CatzByGroupL"]}');";
	}
	
	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->CreateCategoryTable($_POST["personal_database"]);
	$sql="TRUNCATE TABLE webfilters_categories_caches";
	$dans->CategoriesTableCache();
	$dans->CleanCategoryCaches();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?clean-catz-cache=yes");
	$sock->getFrameWork("squid.php?export-web-categories=yes");
	
}



function events(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM updateblks_events GROUP BY category ORDER BY category";
	$results=$q->QUERY_SQL($sql);	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$cat[$ligne["category"]]=$ligne["category"];
	}
	$cat[null]="{select}";
	
$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$form=$tpl->_ENGINE_parse_body("
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{website}:</td>
		<td>". Field_text("WEBTESTS",null,"font-size:14px;padding:3px;border:2px solid #808080",
	null,null,null,false,"CheckSingleSite(event)")."</td>
	</tr>
	</table>
	");
	

	$buttons="
	buttons : [
	{name: '$category', bclass: 'Catz', onpress : ChangeCategory$t},
	
	],";	
	
	$html="
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?updtdb-list-search=yes&ategory={$_GET["category"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 142, sortable : true, align: 'left'},	
		{display: '$events', name : 'text', width : 646, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'text'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 833,
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function ChangeCategory$t(){

}

	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function events_search(){
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="updateblks_events";
	$FORCE=1;
	if($_GET["category"]<>null){$FORCE=" `category`='{$_GET["category"]}'";}
	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT * FROM $table WHERE $FORCE $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}else{
		$sql="SELECT COUNT(*) AS TCOUNT FROM $table WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	$style="style='font-size:14px;'";
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	

  while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$ligne["text"]=$tpl->_ENGINE_parse_body($ligne["text"]);
		if(preg_match("#line:([0-9]+)#", $ligne["text"],$re)){$ligne["text"]=str_replace("line:{$re[1]}", "", $ligne["text"]);$line=$re[1];}
  		if(preg_match("#script:(.+)#", $ligne["text"],$re)){$ligne["text"]=str_replace("script:{$re[1]}", "", $ligne["text"]);$file=$re[1];}
		$ligne["text"]=trim($ligne["text"]);
		
		
		$ligne["text"]=htmlentities($ligne["text"]);
		$ligne["text"]=nl2br($ligne["text"]);
		
		$data['rows'][] = array(
			'id' => md5("{$ligne["zDate"]}{$ligne["text"]}"),
			'cell' => array(
				 "<span $style>{$ligne["zDate"]}</span>",
				"<span $style>". table_error_showZoom($ligne["text"],0)."<div style='font-size:11px'>$file Pid:{$ligne["PID"]} - {$ligne["function"]}() line:$line</div></span>")
			);		
		
		
	}
echo json_encode($data);
	
}

function PurgeCategoriesDatabase(){
	$q=new mysql_squid_builder();
	$categories=$q->LIST_TABLES_CATEGORIES();
	
	$sql="SELECT category FROM personal_categories";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename="category_{$ligne["category"]}";
		$Perso[$tablename]=true;
	}
	
	while (list ($table, $ligne) = each ($categories) ){
		if($Perso[$table]){continue;}
		$q->QUERY_SQL("TRUNCATE TABLE $table");
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE updates_categories","artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?purge-categories=yes");
	
	
}

function PurgeCategoryTable(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE {$_POST["PurgeCategoryTable"]}");
	$q->CreateCategoryTable(null,$_POST["PurgeCategoryTable"]);
	
}



function global_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$t=time();
$html="
<table style='width:100%'>
<tbody>
<tr>
	<td valign='top' width=50%><div id='artica-status-databases-$t'></td>
	<td valign='top' width=50%><div id='tlse-status-databases-$t'></td>
</tr>
<tr>
	<td valign='top' width=50%><div id='clamav-status-databases-$t'></td>
	<td valign='top' width=50%><div id='statistics-status-databases-$t'></td>
</tr>
</tbody>
</table>
<div class=explain style='font-size:16px'>{webfilter_status_text}</div>
<script>
	function RefreshArticaDBStatus(){
		LoadAjax('artica-status-databases-$t','$page?global-artica-status-databases=yes&t=$t');
		LoadAjax('clamav-status-databases-$t','$page?global-clamav-status-databases=yes');
		LoadAjax('statistics-status-databases-$t','$page?global-statistics-status-databases=yes');
		
	}
	
	RefreshArticaDBStatus();
</script>

";	
echo $tpl->_ENGINE_parse_body($html);
//ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib/blacklists.tar.gz
//http://www.bn-paf.de/filter/de-blacklists.tar.gz
//univ-toulouse-64.png
}

function global_kav4Proxy_db(){
	$t=time();
	$tpl=new templates();
	$sock=new sockets();
	$pattern_date=base64_decode($sock->getFrameWork("cmd.php?kav4proxy-pattern-date=yes"));
	$pattern_date_org=$pattern_date;
	if($pattern_date==null){
		$pattern_date="<strong style='font-size:11px;color:#C61010'>{av_pattern_database_obsolete_or_missing}</strong>";}
	else{
		$day=substr($pattern_date, 0,2);
		$month=substr($pattern_date, 2,2);
		$year=substr($pattern_date, 4,4);
		$re=explode(";",$pattern_date_org);
		$time=$re[1];
		$H=substr($time, 0,2);
		$M=substr($time, 2,2);
		$pattern_date="$year/$month/$day $H:$M:00";	
	}
	
	$pattern_dateU=base64_decode($sock->getFrameWork("cmd.php?UpdateUtility-pattern-date=yes"));
	$pattern_date_orgU=$pattern_dateU;
	if($pattern_date==null){
		$pattern_dateU="<strong style='font-size:11px;color:#C61010'>{av_pattern_database_obsolete_or_missing}</strong>";}
	else{
		$day=substr($pattern_dateU, 0,2);
		$month=substr($pattern_dateU, 2,2);
		$year=substr($pattern_dateU, 4,4);
		$re=explode(";",$pattern_date_orgU);
		$time=$re[1];
		$H=substr($time, 0,2);
		$M=substr($time, 2,2);
		$pattern_dateU="$year/$month/$day $H:$M:00";	
	}	
	
	
	
	
	
	$tableau="
	
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px'>{APP_KAV4PROXY}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' valign='top'>{pattern_date}:</td>
			<td style='font-size:14px;font-weight:bold'>$pattern_date</td>
		</tr>	
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
			<table style='width:2%'>
			<tbody>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:UpdateKav4Proxy$t();\" 
				style='font-size:12px;text-decoration:underline'>{TASK_UPDATE_ANTIVIRUS}</a></td>
			</tr>	
			</tbody>
			</table>
		</tr>
			
	</tbody>
	</table>
	
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px'>UpdateUtility</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' valign='top'>{pattern_date}:</td>
			<td style='font-size:14px;font-weight:bold'>$pattern_dateU</td>
		</tr>	
		
			
	</tbody>
	</table>	
	
	<script>
	var x_UpdateKav4Proxy$t= function (obj) {
	      var results=obj.responseText;
	      alert(results);
	}	

	function UpdateKav4Proxy$t(){
			var XHR = new XHRConnection();
			XHR.appendData('update-kav4proxy','yes');
			XHR.sendAndLoad('Kav4Proxy.Tasks.php', 'POST',x_UpdateKav4Proxy$t);	
	}
	</script>
	
	
	";
	
	

	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><img src='img/bigkav-64.png'></td>
		<td valign='top' width=99%>$tableau</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". imgtootltip("refresh-24.png","{refresh}","RefreshArticaDBStatus();")."</td>
	</tr>
	</tbody>
	</table>
	
	";

	
	echo $tpl->_ENGINE_parse_body($html);
}

function global_clamav_db(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	if($users->KASPERSKY_WEB_APPLIANCE){global_kav4Proxy_db();return;}
	if($users->KAV4PROXY_INSTALLED){global_kav4Proxy_db();return;}
	if(!$users->FRESHCLAM_INSTALLED){return;}
	$t=time();
	$CicapEnabled=$sock->GET_INFO('CicapEnabled');
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}
	
	
	$logo="clamav-64.png";
	if($CicapEnabled==0){$logo="clamav-64-grey.png";}
	if($EnableClamavInCiCap==0){$logo="clamav-64-grey.png";}
	$GLOBALENABLED=1;
	if($CicapEnabled==0){$GLOBALENABLED=0;}
	if($EnableClamavInCiCap==0){$GLOBALENABLED=0;}
	
	$patterns["bytecode.cvd"]=true;
	$patterns["daily.cld"]=true;
	$patterns["main.cvd"]=true;	
	$q=new mysql();
	
		$enable="
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:enable_clamav_global(0)\" 
				style='font-size:12px;text-decoration:underline'>{disable_antivirus_protection}</a></td>
			</tr>			
		";	
	
	if($GLOBALENABLED==0){
		$enable="
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:enable_clamav_global(1);\" 
				style='font-size:12px;text-decoration:underline'>{enable_antivirus_protection}</a></td>
			</tr>			
		";
	}
	
	$htmlT="<table style='width:100%'>";
	
	while (list ($pattern, $none) = each ($patterns) ){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM clamavsig WHERE patternfile='$pattern'","artica_backup"));
		$items=numberFormat($ligne["signatures"],0,""," ");
		$version=$ligne["version"];
		$zdate=$ligne["zDate"];	
		$htmlT=$htmlT."
			<tr>
			<td valign='top' class=legend>$pattern:</td>
			<td valign='top'><strong style='font-size:12px'>v.$version</strong><div style='font-size:10px'>$items {items}
			<br><i>$zdate</i></td>
			</tr>";
		
		
		
	}
	
	$htmlT=$htmlT."</table>";
	
	$items=numberFormat($items,0,""," ");	
	
	
	$tableau="
	<div id='clamav-$t'>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px'>{APP_CLAMAV}$running</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' valign='top'>{items}:</td>
			<td style='font-size:14px;font-weight:bold'>$htmlT</td>
		</tr>	
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
			<table style='width:2%'>
			<tbody>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('clamav.index.php?freshclam-js=yes');\" 
				style='font-size:12px;text-decoration:underline'>{parameters}</a></td>
			</tr>	
			$enable			
			</tbody>
			</table>
		</tr>
			
	</tbody>
	</table>
	</div>
	<script>
	var xenable_clamav_global= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	RefreshArticaDBStatus();
		}	

		function enable_clamav_global(value){
			var XHR = new XHRConnection();
			XHR.appendData('enable-clamav-global',1);
			XHR.sendAndLoad('$page', 'POST',xenable_clamav_global);
		}
		
		
	</script>
	
	
	";
	
	

	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><img src='img/$logo'></td>
		<td valign='top' width=99%>$tableau</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". imgtootltip("refresh-24.png","{refresh}","RefreshArticaDBStatus();")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function global_statistics_db(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$logo="statistics2-64.png";
	$sql="SELECT * FROM `mysqldbs` WHERE databasename='squidlogs'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$databasename=$ligne["databasename"];
	$CountDeTables=$ligne["TableCount"];
	$DatabaseSize=$ligne["dbsize"];	
	$DatabaseSize=FormatBytes($DatabaseSize/1024);
	$CountDeTables=numberFormat($CountDeTables,0,""," ");	
	
	$sql="SELECT * FROM `mysqldbtables` WHERE tablename='webfilters_thumbnails' AND databasename='squidlogs'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$TableRows=$ligne["tableRows"];
	$TableSize=$ligne["tablesize"];		
	$TableRows=numberFormat($TableRows,0,'.',' ',3);
	$TableSize=FormatBytes($TableSize/1024);

	//$items=numberFormat($items,0,""," ");	
	
	
	$tableau="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px'>{statistics_database}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold'>{tables}:</td>
			<td style='font-size:14px;font-weight:bold'>$CountDeTables ($DatabaseSize)</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold'>{thumbnails}:</td>
			<td style='font-size:14px;font-weight:bold'>$TableRows {items} ($TableSize)</td>
		</tr>			
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
			<table style='width:2%'>
			<tbody>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.pagepeeker.php');\" style='font-size:12px;text-decoration:underline'>{thumbnails_parameters}</a></td>
			</tr>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:ScanThumbnails();\" style='font-size:12px;text-decoration:underline'>{scan_thumbnails}</a></td>
			</tr>	
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.update.events.php?category=thumbnails');\" 
				style='font-size:12px;text-decoration:underline'>{events}</a></td>
			</tr>											
			</tbody>
			</table>
		</tr>
			
	</tbody>
	</table>
	<script>
	var xScanThumbnails= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	RefreshArticaDBStatus();
		}	

		function ScanThumbnails(){
			var XHR = new XHRConnection();
			XHR.appendData('ScanThumbnails','yes');
			XHR.sendAndLoad('$page', 'POST',xScanThumbnails);
		}
		
		
	
	</script>
	
	
	";
	
	

	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><img src='img/$logo'></td>
		<td valign='top' width=99%>$tableau</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". imgtootltip("refresh-24.png","{refresh}","RefreshArticaDBStatus();")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}


function global_status_tlse_db(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
	if(!is_numeric($SquidDatabasesUtlseEnable)){$SquidDatabasesUtlseEnable=1;}
	$color="color:black";
	if($SquidDatabasesUtlseEnable==1){$disable_text="{database}:&nbsp;{enabled}";}else{
		$disable_text="{database}:&nbsp;{disabled}";
		$color="color:#B6ACAC";
	}
	$t=$_GET["t"];
	$sock=new sockets();
	$scheduledAR=unserialize(base64_decode($sock->getFrameWork("squid.php?schedule-maintenance-tlse=yes")));
	$running="<br><i style='font-size:12px'>{update_task_stopped}</i>";
	if($scheduledAR["RUNNING"]){$running="<br><i style='font-size:12px;color:#BA0000'>{update_currently_running_since} {$scheduledAR["TIME"]}Mn</i>";}
		
	
	$logo="univ-toulouse-64.png";
	
	//univ-toulouse-64-grey.png
	if($SquidDatabasesUtlseEnable==0){$logo="univ-toulouse-64-grey.png";}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(websitesnum) as tcount FROM ftpunivtlse1fr"));	
	$items=$ligne["tcount"];
	if($items==0){
		if($SquidDatabasesUtlseEnable==1){
			$sock=new sockets();
			$sock->getFrameWork("squid.php?tlse-checks=yes");
			$running="<br><strong style='color:red;font-weight:normal'>{please_refresh_again_this_pannel}</strong>";	
		}
	}
	
	$items=numberFormat($items,0,""," ");	
	
	
	$tableau="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px'>{toulouse_university}$running</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold;$color;'>{items}:</td>
			<td style='font-size:14px;font-weight:bold;$color'>$items</td>
		</tr>	
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
			<table style='width:2%'>
			<tbody>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:ToulouseDBUpdateNow();\" style='font-size:12px;$color;text-decoration:underline'>{update_now}</a></td>
			</tr>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.update.events.php?filename=exec.update.squid.tlse.php');\" 
				style='font-size:12px;text-decoration:underline;$color;'>{display_update_events}</a></td>
			</tr>	
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:TlseDisable();\" style='font-size:12px;$color;text-decoration:underline'>$disable_text</a></td>
			</tr>						
			</tbody>
			</table>
		</tr>
			
	</tbody>
	</table>
	<script>
	var xToulouseDBUpdateNow= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	RefreshArticaDBStatus();
		}	

		function ToulouseDBUpdateNow(){
			var XHR = new XHRConnection();
			XHR.appendData('global-toulouse-status-update','yes');
			XHR.sendAndLoad('$page', 'POST',xToulouseDBUpdateNow);
		}
		
		function TlseDisable(){
			var XHR = new XHRConnection();
			XHR.appendData('global-toulouse-enable-update','$SquidDatabasesUtlseEnable');
			XHR.sendAndLoad('$page', 'POST',xToulouseDBUpdateNow);		
		
		}
	
	</script>
	
	
	";
	
	

	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><img src='img/$logo'></td>
		<td valign='top' width=99%>$tableau</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('tlse-status-databases-$t','$page?global-tlse-status-databases=yes&t=$t');")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function global_status_artica_db(){
	$tpl=new templates();
	$page=CurrentPageName();
	$date=GetLastUpdateDate();
	$users=new usersMenus();
	$q=new mysql();
	$sql="SELECT avg(progress) as pourcent FROM updates_categories  WHERE filesize>0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["pourcent"])){$ligne["pourcent"]=0;}
	$pourcent=round($ligne["pourcent"],2);
	$purc=pourcentage($pourcent);
	$t=$_GET["t"];
	
	
	$color="color:black;";
	$sock=new sockets();
	$scheduledAR=unserialize(base64_decode($sock->getFrameWork("squid.php?schedule-maintenance-exec=yes")));
	$SquidDatabasesArticaEnable=$sock->GET_INFO("SquidDatabasesArticaEnable");
	if(!is_numeric($SquidDatabasesArticaEnable)){$SquidDatabasesArticaEnable=1;}
	if($SquidDatabasesArticaEnable==1){$disable_text="Artica&nbsp;{database}:&nbsp;{enabled}";}else{
		$disable_text="Artica&nbsp;{database}:&nbsp;{disabled}";
		$color="color:#B6ACAC";
	}
	$CORP_LICENSE=1;
	if(!$users->CORP_LICENSE){
		$CORP_LICENSE=0;
		$SquidDatabasesArticaEnable=0;
		$disable_text="Artica&nbsp;{database}:&nbsp;<strong style='color:#BA1010'>{license_inactive}</strong>";
		$color="color:#B6ACAC";
	}
	
	$running="<br><i style='font-size:12px'>{update_task_stopped}</i>";
	if($scheduledAR["RUNNING"]){$running="<br><i style='font-size:12px;color:#BA0000'>{update_currently_running_since} {$scheduledAR["TIME"]}Mn</i>";}
	
	$q=new mysql();
	$SQL_ALL_ITEMS="SELECT SUM( TABLE_ROWS ) AS tcount
	FROM information_schema.tables
	WHERE table_schema = 'squidlogs'
	AND table_name LIKE 'category_%'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($SQL_ALL_ITEMS,"information_schema"));
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}	
	$itemsPerso=$ligne["tcount"];
	$itemsPerso=numberFormat($itemsPerso,0,""," ");
	
	$catz=new mysql_catz();
	$itemsArtica=numberFormat($catz->COUNT_CATEGORIES(),0,""," ");

	$q=new mysql_squid_builder();
	$backuped_items=$q->COUNT_ROWS("webfilters_backupeddbs");
	$sql="SELECT SUM(size) as tszie FROM webfilters_backupeddbs";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$backuped_items_size=FormatBytes($ligne["tszie"]/1024);
	
	$backuped_items_text="$backuped_items {backup_containers} ($backuped_items_size)";
	
	
	$tableau="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td colspan=2 style='font-size:16px;$color'>{artica_databases}$running</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold'>{youritems}:</td>
			<td style='font-size:14px;font-weight:bold'>$itemsPerso</td>
		</tr>
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
				<table style='width:2%'>
						<tr>
							<td width=1%><img src='img/arrow-right-16.png'>
							<td nowrap><a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('dansguardian2.backuped.databases.php');\" 
							style='font-size:12px;text-decoration:underline'>$backuped_items_text</a>
							</td>
						</tr>	
						<tr>
							<td width=1%><img src='img/arrow-right-16.png'>
							<td nowrap><a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('dansguardian2.restore.databases.php');\" 
							style='font-size:12px;text-decoration:underline'>{restore_backup}</a>
							</td>
						</tr>
						<tr>
							<td width=1%><img src='img/arrow-right-16.png' id='emptypersonaldbdiv'>
							<td nowrap><a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('dansguardian2.restore.databases.php?empty-js=yes');\" 
							style='font-size:12px;text-decoration:underline'>{empty_database}</a>
							</td>
						</tr>														
				</table>
			</td>			
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold;$color'>{articaitems}:</td>
			<td style='font-size:14px;font-weight:bold;$color'>$itemsArtica</td>
		</tr>

	
		<tr>
			<td colspan=2 style='font-size:14px' align='right'>
			<table style='width:2%'>
			<tbody>
						<tr>
							<td width=1%><img src='img/arrow-right-16.png'>
							<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:ArticaDBDisable();\" style='font-size:12px;text-decoration:underline;$color'>$disable_text</a></td>
						</tr>			
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:ArticaDBUpdateNow();\" style='font-size:12px;text-decoration:underline;$color'>{update_now}</a></td>
			</tr>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.update.events.php');\" style='font-size:12px;text-decoration:underline;$color'>{display_update_events}</a></td>
			</tr>	
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.databases.php?scripts=compile-schedule');\" style='font-size:12px;text-decoration:underline;$color'>{compilation_schedule}</a></td>
			</tr>						
			</tbody>
			</table>
		</tr>
			
	</tbody>
	</table>
	<script>
	var x_ArticaDBUpdateNow= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	RefreshArticaDBStatus();
		}	

		function ArticaDBUpdateNow(){
			var CORP_LICENSE=$CORP_LICENSE;
			if(CORP_LICENSE==0){alert('license error');return;}
			var XHR = new XHRConnection();
			XHR.appendData('global-artica-status-update','yes');
			XHR.sendAndLoad('$page', 'POST',x_ArticaDBUpdateNow);
		}
	
		
	var xArticaDBDisable= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	RefreshArticaDBStatus();
		}	

		
		function ArticaDBDisable(){
			var CORP_LICENSE=$CORP_LICENSE;
			if(CORP_LICENSE==0){alert('license error');return;}
			var XHR = new XHRConnection();
			XHR.appendData('global-artica-enable-update','$SquidDatabasesArticaEnable');
			XHR.sendAndLoad('$page', 'POST',xArticaDBDisable);		
		
		}		
	</script>
	
	
	";
	
	

	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><img src='img/artica5-64.png'></td>
		<td valign='top' width=99%>$tableau</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('artica-status-databases-$t','$page?global-artica-status-databases=yes&t=$t');")."</td>
	</tr>
	</tbody>
	</table>
	<script>
		
		LoadAjax('tlse-status-databases-$t','$page?global-tlse-status-databases=yes&t=$t');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function global_status_artica_update(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?schedule-maintenance-db=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{database_update_performed_inbackground}");
	
}

function ScanThumbnails(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ScanThumbnails=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{database_update_performed_inbackground}");
		
	
}

function global_status_tlse_update(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?schedule-maintenance-toulouse-db=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{database_update_performed_inbackground}");	
}
function global_status_tlse_enable(){
	$sock=new sockets();
	if($_POST["global-toulouse-enable-update"]==1){$sock->SET_INFO("SquidDatabasesUtlseEnable",0);}
	if($_POST["global-toulouse-enable-update"]==0){$sock->SET_INFO("SquidDatabasesUtlseEnable",1);}
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
}

function global_status_articadb_enable(){
	$sock=new sockets();
	if($_POST["global-artica-enable-update"]==1){$sock->SET_INFO("SquidDatabasesArticaEnable",0);}
	if($_POST["global-artica-enable-update"]==0){$sock->SET_INFO("SquidDatabasesArticaEnable",1);}
	$sock->getFrameWork("webfilter.php?compile-rules=yes");	
}

function instant_update_weekly(){
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	
	$sql="SELECT SUM(CountItems) as tcount , WEEK(zDate) as thour,
	DAY(zDate) as tday FROM instant_updates 
	GROUP BY thour,tday HAVING thour=WEEK(NOW()) ORDER BY tday";	
	$xdata=array();
	$ydata=array();
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tday"];
		$ydata[]=$ligne["tcount"];
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".WEEK.". time().".png";
	$gp=new artica_graphs();
	$gp->width=620;
	$gp->height=250;
	$gp->leftMargin=100;
	$gp->TopMargin=10;	
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=false;

	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$t=time();
	$gp->line_green();
	if(is_file($targetedfile)){
		$html[]=$tpl->_ENGINE_parse_body("<center style='font-size:13px;margin-top:8px'>{graph_instant_update_squid_byday}</center>");
		$html[]="<center style='width:95%' class=form><img src='$targetedfile?t=$t'></center>";
		
		
	}	

	
	echo @implode("\n", $html);
}



function instant_update_daily(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$sql="SELECT SUM(CountItems) as tcount FROM instant_updates WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d')";
	
	$TOTALITEMOS=$q->COUNT_ROWS("instant_updates");
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$itemsToday=$ligne["tcount"];
	if(!is_numeric($itemsToday)){$itemsToday=0;}
	writelogs("$sql=`$itemsToday/$TOTALITEMOS`",__FUNCTION__,__FILE__,__LINE__);
	
	if(!$q->ok){$html[]="<span style='color:#AE0000'>$q->mysql_error</span>";}
	
	$sql="SELECT ID,DATE_FORMAT(zDate,'%H:%i:%s') as tcount 
	FROM instant_updates 
	WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$itemsH=$ligne["tcount"];
	$idt=date("Y-m-d H:i:s",$ligne["ID"]);
	$itemsH =$itemsH ." (v $idt)";
	if(!$q->ok){$html[]="<span style='color:#AE0000'>$q->mysql_error</span>";}
	
	
	if($itemsToday>0){
	$text=$tpl->_ENGINE_parse_body("{today_squid_instant_update}");
	$text=str_replace("XX", "<strong>$itemsToday</strong>", $text);
	$text=str_replace("YY", "<strong>$itemsH</strong>", $text);
	$html[]="<div style='font-size:16px'>$text</div>";		
		
	$sql="SELECT SUM(CountItems) as tcount , HOUR(zDate) as thour,
	DATE_FORMAT(zDate,'%Y-%m-%d') as tday FROM instant_updates 
	GROUP BY thour,tday HAVING tday=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY thour";
	
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["thour"];
		$ydata[]=$ligne["tcount"];
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". time().".png";
	$gp=new artica_graphs();
	$gp->width=620;
	$gp->height=250;
	$gp->leftMargin=100;
	$gp->TopMargin=10;	
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=false;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(is_file($targetedfile)){
		$html[]=$tpl->_ENGINE_parse_body("<center style='font-size:13px;margin-top:8px'>{graph_instant_update_squid_byhour}</center>");
		$html[]="<center style='width:95%' class=form><img src='$targetedfile'></center>";
	}
	
	}
	$t=time();
	
	$html[]="<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?instant-update-weekly=yes');
	</script>
	
	";
	



echo @implode("\n", $html);
}

function enable_clamav_global(){
	$tpl=new templates();
	$sock=new sockets();
	$enable=$_POST["enable-clamav-global"];
	if($enable==1){
		$sock->SET_INFO("CicapEnabled", 1);
		$sock->SET_INFO("EnableClamavInCiCap",1);
		echo $tpl->javascript_parse_text("{CICAP_WILLBEENABLED}");
	}else{
		$sock->SET_INFO("EnableClamavInCiCap",0);
	}
	
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("services.php?restart-squid=yes");
	
}


