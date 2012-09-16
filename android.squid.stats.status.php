<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql.inc');



//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){echo "<H1>No right!!!</H1>";die();}


page();
function page(){
	
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$MalwarePatrolDatabasesCount=$sock->getFrameWork("cmd.php?MalwarePatrolDatabasesCount=yes");
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	
	$websitesnums=$q->COUNT_ROWS("visited_sites");
	$websitesnums=numberFormat($websitesnums,0,""," ");	

	$sql="DELETE FROM categorize WHERE LENGTH(pattern)=0";
	$q->QUERY_SQL($sql);
	$export=$q->COUNT_ROWS("categorize");
	$export=numberFormat($export,0,""," ");	
		
	
	$categories=$q->COUNT_CATEGORIES();
	$categories=numberFormat($categories,0,""," ");
	
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	$tablescatNUM=numberFormat(count($tablescat),0,""," ");

	$q=new mysql_squid_builder();
	$requests=$q->EVENTS_SUM();
	$requests=numberFormat($requests,0,""," ");	
	
	
	
	$PhishingURIS=$q->COUNT_ROWS("uris_phishing");
	$PhishingURIS=numberFormat($PhishingURIS,0,""," ");	
	
	
	$MalwaresURIS=$q->COUNT_ROWS("uris_malwares");
	$MalwaresURIS=numberFormat($MalwaresURIS,0,""," ");		
	
	$Computers=$q->COUNT_ROWS("webfilters_nodes");
	$Computers=numberFormat($Computers,0,""," ");
	
	$DAYSNumbers=$q->COUNT_ROWS("tables_day");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(totalsize) as tsize FROM tables_day"));
	$totalsize=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT AVG(cache_perfs) as pourc FROM tables_day"));
	$pref=round($ligne["pourc"]);	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM visited_sites WHERE LENGTH(category)=0"));
	$websitesnumsNot=numberFormat($ligne["tcount"],0,""," ");	
	
	$CachePermformance=$q->CachePerfHour();
	if($CachePermformance>-1){
		$color="#E01313";
		if($CachePermformance>20){$color="#6DBB6A";}
		$cachePerfText="
		<tr>
		<td valign='top' style='font-size:14px;'><b style='color:$color'>$CachePermformance%</b> {cache_performance} ({now})</td>
		</tr>
		";
		
	}		
	
	
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	$submenu="	
	<tr>
		<td valign='top' style='font-size:14px'><b>$totalsize</b> {downloaded_flow}</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px'><b>$pref%</b> {cache_performance}</td>
	</tr>
	";
	
	
	$main_table="
	<table style='width:95%' class=form>
	<tr><td style='font-size:16px'>{statistics}::{status}</td></tr>
	$cachePerfText
	<tr>
		<td valign='top' style='font-size:14px;><b>$DAYSNumbers</b> {daysOfStatistics}</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px'><b>$requests</b> {requests}</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px'><b>$Computers</b> {computers}</td>
	</tr>		
	<tr>
		<td valign='top' style='font-size:14px;'><b>$websitesnums</b> {visited_websites}</td>
	</tr>	

	<tr>
		<td valign='top' style='font-size:14px;'><b>$categories</b> {websites_categorized}</td>
	</tr>
	<tr>
		<td valign='top'  style='font-size:14px;'><b>$PhishingURIS</b> {phishing_uris}</td>
	</tr>	
	<tr>
		<td valign='top'  style='font-size:14px;'><b>$MalwaresURIS</b> {viruses_uris}</td>
	</tr>
	<tr>
		<td valign='top'  style='font-size:14px;'><b>$MalwarePatrolDatabasesCount</b> Malware Patrol</td>
	</tr>					
	<tr>
		<td valign='top'  style='font-size:14px;'><b>$websitesnumsNot</b> {not_categorized}</td>
	</tr>				
	<tr>
		<td valign='top'  style='font-size:14px;'><b>$tablescatNUM</b> {categories}</td>
	</tr>
	</table>	
";	
		
$main_table=$tpl->_ENGINE_parse_body($main_table);
SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $main_table);
echo $main_table;
	
}