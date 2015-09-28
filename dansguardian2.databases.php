<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
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
if(isset($_POST["ArticaDBPath"])){ArticaDBPathSave();exit;}
if(isset($_GET["instant-update-daily"])){instant_update_daily();exit;}
if(isset($_GET["instant-update-weekly"])){instant_update_weekly();exit;}
if(isset($_POST["enable-clamav-global"])){enable_clamav_global();exit;}
if(isset($_POST["CategoriesDatabasesByCron"])){CategoriesDatabasesByCron();exit;}
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
	
	if($_GET["from-ufdbguard"]=="yes"){
		echo $tpl->_ENGINE_parse_body("
				<div style='margin:15px;text-align:right'>
				". button("{back_to_webfiltering}",
							"AnimateDiv('BodyContent');LoadAjax('BodyContent','dansguardian2.mainrules.php')",18)."
				</div>");
	}
	
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
	if(!$q->DELETE_CATEGORY($category)){return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
}

function CategoriesDatabasesByCron(){
	$sock=new sockets();
	$sock->SET_INFO("DisableCategoriesDatabasesUpdates", $_POST["DisableCategoriesDatabasesUpdates"]);
	$sock->SET_INFO("CategoriesDatabasesByCron", $_POST["CategoriesDatabasesByCron"]);
	$sock->SET_INFO("CategoriesDatabasesShowIndex", $_POST["CategoriesDatabasesShowIndex"]);
}

function ArticaDBPathSave(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaDBPath", $_POST["ArticaDBPath"]);
	
}

function statusDB_not_installed(){
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	
	$ArticaDBPathenc=urlencode($ArticaDBPath);
	$arrayinfos=unserialize(base64_decode($sock->getFrameWork("services.php?dir-status=$ArticaDBPathenc")));
	
	$REQUIRE=round(1753076/1024);
	$SIZE=round($arrayinfos["SIZE"]/1024);
	
	if($SIZE<$REQUIRE){
		$error="<center style='color:#d32d2d;font-weight:bold;font-size:16px;margin:20px'><span >".
				$tpl->_ENGINE_parse_body("{no_enough_free_space_on_target}<br>&laquo;$ArticaDBPath&raquo;<br>({$SIZE}MB {require} {$REQUIRE}MB)</center>");
	}
	
	
	
	$page=CurrentPageName();
	$html=FATAL_ERROR_SHOW_128("{ARTICADB_NOT_INSTALLED_EXPLAIN}")."<center style='margin:80px'>
		<hr>".button("{install_now}", "Loadjs('squid.blacklist.upd.php')",16)."
		<hr>".button("{manual_update}", "Loadjs('squid.catzdb.manual-update.php')",16)."
		<hr>$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
	
	<tr>
		<td class=legend style='font-size:16px'>{database_storage_path} ({$SIZE}MB):</td>
		<td>". Field_text("ArticaDBPath-$t",$ArticaDBPath,"font-size:16px;width:320px")."</td>
		<td width=1%>". button_browse("ArticaDBPath-$t")."</td>
	</tr>				
	<tr>
		<td colspan=3 align='right'>". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
	</center>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.blacklist.upd.php');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ArticaDBPath',document.getElementById('ArticaDBPath-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function statusDB(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$backbutton=null;
	
	if($_GET["from-ufdbguard"]=="yes"){
		echo $tpl->_ENGINE_parse_body("
				<div style='margin:15px;text-align:right'>
				". button("{back_to_webfiltering}",
				"AnimateDiv('BodyContent');LoadAjax('BodyContent','dansguardian2.mainrules.php')",18)."
				</div>");
	}
	
	$q=new mysql_catz();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$catz=$q->LIST_TABLES_CATEGORIES();
	$sock->getFrameWork('cmd.php?squid-ini-status=yes');
	$ini->loadFile("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
	
	
	
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	$date=$CATZ_ARRAY["TIME"];
	$LOCAL_VERSION=$CATZ_ARRAY["TIME"];
	$title=$tpl->_ENGINE_parse_body("{APP_ARTICADB}");
	$q=new mysql_catz();
	$LOCAL_VERSION_TEXT=$tpl->time_to_date($date);
	
	
	$CountDecategories=intval(@file_get_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_COUNT"));
	$CountDeDatabases=intval(@file_get_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_DBS"));
	if(!is_numeric($CountDecategories)){$CountDecategories=0;}
	$CountDecategories=numberFormat($CountDecategories,0,""," ");
	
	
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$APP_UFDBCAT=DAEMON_STATUS_ROUND("APP_UFDBCAT",$ini,null,1);
	
	$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));
	$CategoriesDatabasesUpdatesAllTimes=intval($sock->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabasesByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	
	$CategoriesDatabasesShowIndex=$sock->GET_INFO("CategoriesDatabasesShowIndex");
	if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}	
	
	$fbdize=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/categories-db.size.db"));
	$DBSIZE=FormatBytes($fbdize["DBSIZE"]);
	$POURC=$fbdize["POURC"];
	if(is_numeric($POURC)){$POURC_TXT="&nbsp;{$POURC}% {used}";}
	
	
	$p2=Paragraphe_switch_img("{disable_udpates}",
	"{disable_udpates_explain}<br>{APP_ARTICADB_EXPLAIN}","DisableCategoriesDatabasesUpdates",
	$DisableCategoriesDatabasesUpdates,null,700);	
			
	
	$p3=Paragraphe_switch_img("{free_update_during_the_day}",
			"{free_update_during_the_day_explain}","CategoriesDatabasesUpdatesAllTimes",
			$CategoriesDatabasesUpdatesAllTimes,null,700);
	
	
	$p=Paragraphe_switch_img("{update_only_by_schedule}", 
	"{articadb_update_only_by_schedule}","CategoriesDatabasesByCron",
	$CategoriesDatabasesByCron,null,700);
	
	$tt0[]="<tr><td colspan=2>$p3</td></tr>";
	$tt0[]="<tr><td colspan=2>$p2</td></tr>";
	$tt0[]="<tr><td colspan=2>$p</td></tr>";	
	
	$tt0[]="<tr>
			<td width=1%>". Field_checkbox_design("CategoriesDatabasesShowIndex", 1,$CategoriesDatabasesShowIndex,"CategoriesDatabasesByCron()")."</td>
			<td nowrap style='font-size:14px;'>:{display_update_info_index}</a></td>
		</tr>
		<tr><td colspan=2 align='right'>".button("{apply}","CategoriesDatabasesByCron()",22)."</td></tr>			
					
					";
	
	
	
		

		
		/*$tt[]="<tr>
		<td width=1%><img src='img/arrow-right-16.png'>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.catzdb.manual-update.php');\"
		style='font-size:14px;text-decoration:underline;'>{manual_update}</a></td>
		</tr>";		
		*/
		
		/*$tt[]="<tr>
		<td width=1%><img src='img/arrow-right-16.png'>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.catzdb.changedir.php');\"
		style='font-size:14px;text-decoration:underline;'>{change_directory}</a></td>
		</tr>";	
		*/
		
		if($DisableArticaProxyStatistics==1){
			$tt[]="<tr>
		<td width=1%><img src='img/arrow-right-16.png'>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.artica.statistics.php');\"
		style='font-size:14px;text-decoration:underline;color:#D10000'>{ARTICA_STATISTICS} {disabled}</a></td>
		</tr>";			
			
		}else{
			$tt[]="<tr>
		<td width=1%><img src='img/arrow-right-16.png'>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.artica.statistics.php');\"
		style='font-size:14px;text-decoration:underline;color:black'>{ARTICA_STATISTICS}</a></td>
		</tr>";			
		}

		
		
	
	$arrayV=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-nextversion=yes")));
	$REMOTE_VERSION=$arrayV["TIME"];
	if($REMOTE_VERSION>$date){
		$REMOTE_VERSION_TEXT=$tpl->time_to_date($REMOTE_VERSION);
		$newver="	<tr>
		<td colspan=2><div style='font-size:16px;color:#D52210'>{new_version}:&nbsp;$REMOTE_VERSION <i style='font-size:11px'>$REMOTE_VERSION_TEXT</i>&nbsp</div></td>
	</tr>";
		
		
		$updaebutton="<div style='text-align:right'><hr>".button("{update_now}", "Loadjs('squid.blacklist.upd.php')",22)."</div>";
	}
	
	$nextcheck=$sock->getFrameWork("squid.php?articadb-nextcheck=yes");
	$nextcheck=intval($nextcheck);
	if($nextcheck>0){
		$nextcheck_text="	
	<tr>
		<td colspan=2><div style='font-size:16px'>{next_check_in}:&nbsp;{$nextcheck}Mn</div></td>
	</tr>";
	}
	
	if($nextcheck<0){
		$nextcheck=str_replace("-", "", $nextcheck);
		$nextcheckTime=time()-(intval($nextcheck)*60);
		$nextcheckTimeText=distanceOfTimeInWords($nextcheckTime,time());
		$nextcheck_text="	
	<tr>
		<td colspan=2><div style='font-size:16px'>{last_check}:&nbsp;$nextcheckTimeText</div></td>
	</tr>";		
	}
	
	
	
	$dbsize=$sock->getFrameWork("squid.php?articadbsize=yes");
	$items=numberFormat($q->COUNT_CATEGORIES(),0,""," ");
	$html="
	<div style='width:98%' class=form>
	
	<table style='width:100%'>
	<tr>
	<td valign='top'>$APP_SQUID_DB<br>$APP_UFDBCAT</td>
	<td valign='top'>
	<table style='width:100%'>".@implode("\n", $tt0)."</table>
	<hr>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td colspan=2><div style='font-size:16px'>{pattern_database_version}:&nbsp;$date <i style='font-size:11px'>$LOCAL_VERSION_TEXT</i>&nbsp$POURC_TXT</div></td>
	</tr>
	$newver
	$nextcheck_text
	<tr>
		<td colspan=2><div style='font-size:16px'>{categories}:&nbsp;$CountDeDatabases</a></div></td>
	</tr>
	<tr>
		<td colspan=2><div style='font-size:16px'>{categorized_websites}:&nbsp;
		<a href=\"javascript:Loadjs('squid.catz.php');\" style='font-size:16px;text-decoration:underline'>
		$CountDecategories</a>&nbsp</div></td>
	</tr>
	".@implode("", $tt)."
	</tbody>
	</table>
	</td>
	</tr>
	</table>
	$updaebutton
	<div id='database-progress-status'></div>
<script>
var xCategoriesDatabasesByCron= function (obj) {
	var results=obj.responseText;
	if(results.length>1){alert(results);}
}
	
function CategoriesDatabasesByCron(){
	var XHR = new XHRConnection();
	XHR.appendData('CategoriesDatabasesByCron',document.getElementById('CategoriesDatabasesByCron').value);
	XHR.appendData('DisableCategoriesDatabasesUpdates',document.getElementById('DisableCategoriesDatabasesUpdates').value);
	XHR.appendData('CategoriesDatabasesUpdatesAllTimes',document.getElementById('CategoriesDatabasesUpdatesAllTimes').value);
	if(document.getElementById('CategoriesDatabasesShowIndex').checked){XHR.appendData('CategoriesDatabasesShowIndex','1');}else{XHR.appendData('CategoriesDatabasesShowIndex','0');}
	XHR.sendAndLoad('$page', 'POST',xCategoriesDatabasesByCron);
}
	

</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function GetLastUpdateDate(){
	$sock=new sockets();
	return $sock->getFrameWork("squid.php?articadb-version=yes");
}


function compile_db_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{confirm_dnsg_compile_db} {$_GET["compile-db-js"]}");
	$t=time();
	$html="
	function compiledb$t(){
		if(confirm('$ask')){
			Loadjs('dansguardian2.databases.compile.php?db={$_GET["compile-db-js"]}');
		}
	}
	
	compiledb$t();
	";
	
	echo $html;
	
}

function compile_all_db_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ask=$tpl->javascript_parse_text("{confirm_dnsg_compileall_db}");
	header("content-type: application/x-javascript");
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
	//$array["stats"]='{statistics}';	
	$array["events"]='{events}';
	$array["backup"]='{backup_stats}';	
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="events-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.blacklist.php?status=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="stats"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.statistics.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;			
			
		}
		
		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.admin.events.php?ufdbguard-artica=\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="backup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.stats.backup.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&maximize=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "main_databasesCAT_quicklinks_tabs");
	

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

	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}else{
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");
	}
	
	$q->QUERY_SQL("DELETE FROM personal_categories WHERE category='';");
	$OnlyPersonal=null;
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();	
	
	$AS_SELECT=false;
	if($_GET["select"]=="yes"){$AS_SELECT=true;}
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$purge=$tpl->_ENGINE_parse_body("{purgeAll}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$license=$tpl->javascript_parse_text("{license}");
	$timeText=$tpl->javascript_parse_text("{time}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$COMPILESIZE="{display: 'compile', name : 'icon2', width : $compilesize, sortable : false, align: 'left'},";
	
	$categorysize=387;
	if($_GET["minisize"]=="yes"){
		$tablewith=625;
		$categorysize=356;
		$delete=null;
		$compilesize="51";
	}
	

	
	if($_GET["maximize"]=="yes"){
		$tablewith=900;
		$categorysize=522;
		$size_size=72;
		$size_elemnts=105;
		$TABLE_ROWS3="{display: '$timeText', name : 'TABLE_ROWS3', width : $size_elemnts, sortable : false, align: 'right'},";
		$delete="{display: 'delete', name : 'icon3', width : 70, sortable : false, align: 'center'},";
		$COMPILESIZE="{display: 'compile', name : 'icon2', width : 70, sortable : false, align: 'center'},";
	}	
	if($_GET["middlesize"]=="yes"){
		$tablewith=828;
		$size_elemnts=70;
		$size_size=80;
		$categorysize=400;
		$TABLE_ROWS2="{display: '$license', name : 'TABLE_ROWS2', width : $size_elemnts, sortable : false, align: 'right'},";
		
		$artica="&artica=yes";
		
	}
	
	if($_GET["minisize-middle"]=="yes"){
		$tablewith=917;
		$categorysize=470;
		$size_elemnts=70;
		$size_size=80;
		$compilesize="51";
		$TABLE_ROWS2="{display: '$license', name : 'TABLE_ROWS2', width : $size_elemnts, sortable : false, align: 'right'},";
		$artica="&artica=yes";
	}	
	
	if(isset($_GET["OnlyPersonal"])){$OnlyPersonal="&OnlyPersonal=yes";}

	$select_uri=null;
	
	if($AS_SELECT){
		$select_uri="&select=yes&&callback={$_GET["callback"]}";
		$TABLE_ROWS2=null;
		$select_text=$tpl->javascript_parse_text("{select}");
		$COMPILESIZE=null;
		$size_elemnts=98;
		$delete="{display: '$select_text', name : 'icon3', width : 120, sortable : false, align: 'center'},";
	}
	
	
	$t=time();
	$html="
	<div style='margin-left:-15px'>
	<table class='dansguardian2-category-$t' style='display: none' id='dansguardian2-category-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#dansguardian2-category-$t').flexigrid({
	url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t$artica$OnlyPersonal$select_uri',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon1', width : 32, sortable : false, align: 'left'},
		{display: '$category', name : 'categorykey', width : $categorysize, sortable : false, align: 'left'},
		{display: '$size', name : 'category', width : $size_size, sortable : false, align: 'left'},
		{display: '$items', name : 'TABLE_ROWS', width : $size_elemnts, sortable : true, align: 'right'},
		$TABLE_ROWS2
		$TABLE_ROWS3
		$COMPILESIZE
		
		$delete
		
	],
buttons : [
	{name: '$addCat', bclass: 'add', onpress : AddNewCategory},
	{name: '$SaveToDisk', bclass: 'Catz', onpress : SaveAllToDisk},
	{name: '$size', bclass: 'Search', onpress : LoadCategoriesSize},
	{name: '$purge', bclass: 'Delz', onpress : PurgeCategoriesDatabase},
		],	
	searchitems : [
		{display: '$category', name : 'categorykey'},
		],
	sortname: 'table_name',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
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
		
		function TableCategoryPurge(tablename){
			if(confirm('$purge_catagories_table_explain')){
				var XHR = new XHRConnection();
				XHR.appendData('PurgeCategoryTable',tablename);
				XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',X_TableCategoryPurge);					
			}
		}
		
		
		CheckStatsApplianceC();		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function CATZ_ARRAY_FILE(){

	$f[]="/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY";
	$f[]="/home/artica/categories_databases/CATZ_ARRAY";

	while (list ($index, $line) = each ($f) ){
		if(is_file($line)){return $line;}
	}
}

function categories_search($forceArtica=false){
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
	$artica=$forceArtica;
	if(isset($_GET["OnlyPersonal"])){$OnlyPersonal=1;}
	$rp=200;
	if(isset($_GET["artica"])){$artica=true;}
	if($_POST["sortname"]=="table_name"){$_POST["sortname"]="categorykey";}
	if(!$q->BD_CONNECT()){json_error_show("Testing connection to MySQL server failed...",1);}
	$table="webfilters_categories_caches";
	$sql="SELECT * FROM personal_categories";
	
	if($OnlyPersonal==0){
		if(!$q->TABLE_EXISTS($table)){ $q->create_webfilters_categories_caches(); }
		$dans=new dansguardian_rules();
		if($q->COUNT_ROWS($table)==0){ $dans->CategoriesTableCache(); }		
		$dans->LoadBlackListes();
	}else{
		$table="personal_categories";
		if($_POST["sortname"]=="categorykey"){$_POST["sortname"]="category";}
	}
	
	$prefix="INSERT IGNORE INTO webfilters_categories_caches (`categorykey`,`description`,`picture`,`master_category`,`categoryname`) VALUES ";
	
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
	writelogs("$q->mysql_admin:$q->mysql_password:$sql",__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("Not found...",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$AS_SELECT=false;
	if($_GET["select"]=="yes"){$AS_SELECT=true;}
	
	
	$enc=new mysql_catz();
	
	$field="categorykey";
	$field_description="description";
	if($OnlyPersonal==1){
		$field="category";
		$field_description="category_description";
	}
	$ProductName="Artica";
	$ProductNamef=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/ProducName.conf";
	if(is_file($ProductNamef)){
		$ProductName=trim(@file_get_contents($ProductNamef));
	}
	$CATZ_ARRAY=unserialize(base64_decode(@file_get_contents(CATZ_ARRAY_FILE())));
	$FULL_ARRAY=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS_FULL.db"));
	$TLSE_ARRAY=$FULL_ARRAY["TLSE_ARRAY"];
	$ARTICA_ARRAY=$FULL_ARRAY["CAT_ARTICAT_ARRAY"];
	
	//print_r($ARTICA_ARRAY);
	$TransArray=$enc->TransArray();
	while (list ($tablename, $items) = each ($CATZ_ARRAY) ){
		if(!isset($TransArray[$tablename])){continue;}
		$CATZ_ARRAY2[$TransArray[$tablename]]=$items;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$sizedb=array();
		$ZZCOUNT=0;
		$categorykey=$ligne[$field];
		if($categorykey==null){$categorykey="UnkNown";}
		//Array ( [category] => [category_description] => Ma catégorie [master_category] => [sended] => 1 )
		if($GLOBALS["VERBOSE"]){echo "Found  $field:{$categorykey}<br>\n";}
		$categoryname=$categorykey;
		$ITEMS_COLONE=array();
		$Time=array();
		
		$text_category=null;
		
		$table=$q->cat_totablename($categorykey);
		if($GLOBALS["VERBOSE"]){echo "Scanning table $table<br>\n";}
		$UnivToulouseItems=null;
		
		$ligne_databases=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM UPDATE_DBWF_INFOS WHERE category='$categoryname'"));
		$size_artica=$ligne_databases["size_artica"];
		$date_artica=$ligne_databases["date_artica"];
		$count_artica=$ligne_databases["count_artica"];
		$size_tlse=$ligne_databases["size_tlse"];
		$date_tlse=$ligne_databases["date_tlse"];
		$count_tlse=$ligne_databases["count_tlse"];
		$size_perso=$ligne_databases["size_perso"];
		$date_perso=$ligne_databases["date_perso"];
		$count_perso=$ligne_databases["count_perso"];
		
		
		$items=$count_perso;
		$itemsEnc=$count_artica;
		$ZZCOUNT=$ZZCOUNT+$items;
		$ZZCOUNT=$ZZCOUNT+$itemsEnc;
		
		if($date_perso>0){
			$Time[]=date("m-d H:i",$date_perso);
		}else{
			$Time[]="-";
		}
		
		$sizeArtica=$size_artica;
		if($date_artica>0){
			$Time[]=date("m-d H:i",$date_artica);
		}else{
			$Time[]="-";
		}
		
		$ITEMS_COLONE[]="Perso.:&nbsp;".numberFormat($items,0,""," ");
		$ITEMS_COLONE[]="$ProductName:&nbsp;".numberFormat($itemsEnc,0,""," ");
		
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");

		if(!isset($dans->array_blacksites[$categoryname])){
			if(isset($dans->array_blacksites[str_replace("_","-",$categoryname)])){$categoryname=str_replace("_","-",$categoryname);}
			if(isset($dans->array_blacksites[str_replace("_","/",$categoryname)])){$categoryname=str_replace("_","/",$categoryname);}
		}
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
	

		
		
		
		
		
		$sizedb[]=FormatBytes($size_perso/1024);
		$sizedb[]=FormatBytes($size_artica/1024);
		
		
		$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$categoryname}&t=$t',true)\"
		style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		$text_category=$tpl->_ENGINE_parse_body(utf8_decode($ligne[$field_description]));
		$text_category=trim($text_category);
		
		
		$pic="<img src='img/20-categories-personnal.png'>";
		if($ligne["picture"]<>null){$pic="<img src='img/{$ligne["picture"]}'>";}

		if($OnlyPersonal==0){
			if(!isset($dans->array_blacksites[$categoryname])){
				$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
				style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
			}
		}else{
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
			style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		}
		
		
		$viewDB=imgsimple("mysql-browse-database-32.png","{view}","javascript:Loadjs('squid.categories.php?category={$categoryname}',true)");		
		
		
		$text_category=utf8_encode($text_category);
		$categoryname_text=utf8_encode($categoryname);
		$categoryText=$tpl->_ENGINE_parse_body("<span style='font-size:14px';font-weight:bold'>$linkcat$categoryname_text</span>
		</a><br><span style='font-size:11px;width:100%;font-weight:normal'>{$text_category}</span>");
		

		
		
		
		if($OnlyPersonal==1){
			$itemsEncTxt="<br><span style='font-size:11px'>".numberFormat($itemsEnc,0,""," ");"</span>";
		}
		
		$compile=imgsimple("compile-distri-32.png",null,"DansGuardianCompileDB('$categoryname')");
		$delete=imgsimple("delete-32.png","{delete}","TableCategoryPurge('$table')");
		if($_GET["minisize"]=="yes"){$delete=null;}
		
		if($OnlyPersonal==0){
			$UnivToulouse_websitesnum=$count_tlse;
			$ZZCOUNT=$ZZCOUNT+$UnivToulouse_websitesnum;
			$UnivToulouse_size=$size_tlse;
			$sizedb[]=FormatBytes($UnivToulouse_size/1024);
			$ITEMS_COLONE[]="University:&nbsp;".numberFormat($UnivToulouse_websitesnum,0,""," ");
			if($date_tlse>0){
			$Time[]=date("m-d H:i",$date_tlse);
			}else{
				$Time[]="-";
			}
		}
		
		if($categoryname=="UnkNown"){
			$linkcat=null;
			$delete=imgsimple("delete-32.png","{delete}","TableCategoryPurge('')");
		}
		
		if($EnableWebProxyStatsAppliance==0){if($ZZCOUNT==0){$pic="<img src='img/warning-panneau-32.png'>";}}
		
		
		$cell=array();
		$cell[]=$pic;
		$cell[]=$categoryText;
		$cell[]="<span style='font-size:11px;padding-top:15px;font-weight:bold'>".@implode("<br>", $sizedb)."</span>";
		$cell[]="<span style='font-size:11px;padding-top:5px;font-weight:bold'>".@implode("<br>", $ITEMS_COLONE)."</span>";
		
	
		
		
		if(!$AS_SELECT){
			$cell[]="<span style='font-size:11px;padding-top:5px;font-weight:bold'>".@implode("<br>", $Time)."</span>";
			$cell[]=$compile;
			$cell[]=$delete;
		}else{
			$select=imgsimple("arrow-right-32.png",null,"{$_GET["callback"]}('$categorykey')");
			$cell[]=$select;
		}
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => $cell
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
		$UnivToulouse=null;
		
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgsimple("32-parameters.png","{apply}","DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		$delete=imgsimple("delete-32.png","{delete}","DansGuardianDeleteMember('{$ligne["ID"]}')");
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");
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
		
		$q2=new mysql_squid_builder();
		$ligneTLS=mysql_fetch_array($q2->QUERY_SQL("SELECT websitesnum FROM univtlse1fr WHERE category='{$ligne['categorykey']}'"));
		$UnivToulouse_websitesnum=$ligneTLS["websitesnum"];
		if($UnivToulouse_websitesnum>0){
			$UnivToulouse="T:".numberFormat($UnivToulouse_websitesnum,0,""," ");
		}
		
		if($EnableWebProxyStatsAppliance==0){
			if($sizedb_org<35){$pic="<img src='img/warning-panneau-32.png'>";}
		}
		$viewDB=imgsimple("mysql-browse-database-32.png","{view}","javascript:Loadjs('squid.categories.php?category={$categoryname}')");
		$html=$html."
		<tr class=$classtr>
			<td width=1%>$pic</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=99%>
			$linkcat$categoryname</a><div style='font-size:11px;width:100%;font-weight:normal'>{$text_category}</div></td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='right'>$sizedb</td>
			<td width=1%>$viewDB</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='right'>".numberFormat($items,0,""," ")."$UnivToulouse</td>
			<td width=1%>$compile</td>
			<td width=1%>$delete</td>
		</tr>
		";
	}
	
	$TOTAL_ITEMS=numberFormat($TOTAL_ITEMS,0,""," ");	
	$PurgeDatabase=imgsimple("database-32-delete.png","{purge_catagories_database_text}","PurgeCategoriesDatabase()");
	
	
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
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{add}::{personal_category}");
	if($_GET["cat"]<>null){$title=$tpl->_ENGINE_parse_body("{$_GET["cat"]}::{personal_category}");$widownsize=995;}
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
	
	
	$catzenc=urlencode($_GET["cat"]);
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="manage"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
			<a href=\"squid.categories.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" 
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="urls"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.categories.urls.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="security"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
			<a href=\"squid.categories.security.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" 
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="category-events"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
			<a href=\"squid.update.logs.php?popup=yes&category=$catname_enc&t=$t&tablesize=695&descriptionsize=530\" 
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&t=$t&cat=$catname_enc\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_zoom_catz");
	
	
	
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
	
	$actions="
	<p>&nbsp;</p>
	<center style='margin-bottom:50px'>". button("{compile_this_category}","Loadjs('ufdbguard.compile.category.php?category={$_GET["cat"]}&t=$t');",18)."</center>
	<center style='margin-bottom:50px'>". button("{delete_this_category}","DeletePersonalCat$t()",18)."</center>
	
	";
	
if($_GET["cat"]==null){$actions=null;}
	
	if($_GET["cat"]<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT category_description,master_category FROM personal_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$titleBT="{apply}";
	}else{
		$action=null;
		$titleBT='{add}';
	}
	
	$groups=$dans->LoadBlackListesGroups();
	$groups[null]="{select}";
	$field=Field_array_Hash($groups, "CatzByGroupL",null,$jsnull,null,0,"font-size:22px");
	
	$blacklists=$dans->array_blacksites;
	$description="<textarea name='category_text' 
			id='category_text-$t' style='height:50px;overflow:auto;width:99%;font-size:22px !important'>"
	.utf8_encode($ligne["category_description"])."</textarea>";
	
	if(isset($blacklists[$_GET["cat"]])){
		$description="<input type='hidden' id='category_text-$t' value=''>
		<div class=explain style='font-size:16px'>{$blacklists[$_GET["cat"]]}</div>";
	}
	
	$html="
	<div id='perso-cat-form'></div>
	<table style='width:100%'>
	<tr>
		<td valign='top' width=99%>
			<table style='width:99%' class=form>
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
				<td class=legend style='font-size:22px'>{group}:</td>
				<td>$field</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:22px'>{group} ({add}):</td>
				<td>". Field_text("CatzByGroupA",null,"font-size:22px;padding:3px;width:70%")."</td>
			</tr>	
			
			<tr>
			<td colspan=2 align='right'><hr>". button($titleBT,"SavePersonalCategory()",32)."</td>
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
		if(results.length>3){alert(results);return;};
		$('#dansguardian2-category-$t').flexReload();
		YahooWin5Hide();
	
	}
		
	function SavePersonalCategory(){
		var XHR = new XHRConnection();
		var db=document.getElementById('category-to-add-$t').value;
		var expl=document.getElementById('category_text-$t').value;
		if(db.length<5){alert('$error_category_nomore5');return;}
		if(expl.length<5){alert('$error_category_textexpl');return;}
		if(db.length>15){alert('$error_max_dbname: 15');return;}
		XHR.appendData('personal_database',db);
		var pp=encodeURIComponent(document.getElementById('category_text-$t').value);
		XHR.appendData('category_text',pp);
		XHR.appendData('CatzByGroupA',document.getElementById('CatzByGroupA').value);
		XHR.appendData('CatzByGroupL',document.getElementById('CatzByGroupL').value);
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
	
	function checkform(){
		var cat='{$_GET["cat"]}';
		if(cat.length>0){document.getElementById('category-to-add-$t').disabled=true;}
	}
checkform();
</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function add_category_save(){
	$_POST["personal_database"]=url_decode_special_tool($_POST["personal_database"]);
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
			master_category='{$_POST["CatzByGroupL"]}'
			WHERE category='{$_POST["personal_database"]}'
			";
	}else{
		
		if(isset($dans->array_blacksites[$_POST["personal_database"]])){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{$_POST["personal_database"]}:{category_already_exists}");
			return;
		}
		
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
	width: '99%',
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





function global_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$t=time();
$html="
<div class=explain style='font-size:14px'>{webfilter_status_text}</div>
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

<script>
	function RefreshArticaDBStatus(){
		LoadAjax('artica-status-databases-$t','$page?global-artica-status-databases=yes&t=$t',false);
		LoadAjax('clamav-status-databases-$t','$page?global-clamav-status-databases=yes',false);
		LoadAjax('statistics-status-databases-$t','$page?global-statistics-status-databases=yes',false);
		
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
<div style='width:95%;min-height:254px' class=form>
<table style='width:99%'>
	<tbody>
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
	$sock=new sockets();
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
	
	$DB_STATUS=unserialize(base64_decode($sock->getFrameWork("ufdbguard.php?databases-percent=yes")));
	
	
	$DB_STATUS_TIME=$DB_STATUS["CATZ"]["LAST_TIME"];
	$DB_STATUS_MAX=$DB_STATUS["CATZ"]["MAX"];
	$DB_STATUS_COUNT=$DB_STATUS["CATZ"]["COUNT"];
	$DB_STATUS_PERC=round(($DB_STATUS_COUNT/$DB_STATUS_MAX)*100);
	if($DB_STATUS_PERC>100){$DB_STATUS_PERC=100;}
	//$items=numberFormat($items,0,""," ");	
	
	
	$tableau="<div style='width:95%;min-height:254px' class=form>
<table style='width:99%'>
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
		<tr>
			<td class=legend style='font-size:12px;font-weight:bold;$color'>{update_status}:</td>
			<td>". pourcentage($DB_STATUS_PERC,0,"green")."</td>
		</tr>				
	</tbody>
	</table>
	</div>
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
		
		UnlockPage();
	
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
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(websitesnum) as tcount FROM univtlse1fr"));	
	$items=$ligne["tcount"];
	if($items==0){
		if($SquidDatabasesUtlseEnable==1){
			$sock=new sockets();
			$sock->getFrameWork("squid.php?tlse-checks=yes");
			$running="<br><strong style='color:#d32d2d;font-weight:normal'>{please_refresh_again_this_pannel}</strong>";	
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
				OnClick=\"javascript:Loadjs('squid.update.logs.php?filename=exec.update.squid.tlse.php');\" 
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
	UnlockPage();
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
$tt=time();
echo "<div id='$tt'></div><script>LoadAjax('$tt','dansguardian2.databases.compiled.php?global-artica-status-databases=yes&t={$_GET["t"]}',false);</script>";
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
		$html[]="<center style='width:98%' class=form><img src='$targetedfile?t=$t'></center>";
		
		
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
		$html[]="<center style='width:98%' class=form><img src='$targetedfile'></center>";
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


