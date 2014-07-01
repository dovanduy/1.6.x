<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.stats.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

if($argv[1]=="--export"){start_export();exit;}
if($argv[1]=="--import"){start_import();exit;}
if($argv[1]=="--push"){export_push();exit;}
if($argv[1]=="--clients-hours"){clients_hours();exit;}
if($argv[1]=="--clients-hours-perform"){_shell_clients_hours_perfom($argv[2],$argv[3]);exit;}
if($argv[1]=="--step2"){Step2();exit;}
if($argv[1]=="--table-days"){table_days();exit;}
if($argv[1]=="--week-days-nums"){WeekDaysNums();exit;}
if($argv[1]=="--nodes"){nodes_scan();exit;}





//_clients_hours_perfom($tabledata,$nexttable



/*
 * Logfile daemon 
 * $TablePrimaireHour="squidhour_".date("YmdH",$xtime) -> 
 * Class -> LIST_TABLES_HOURS_TEMP
 * Process -> exec.squid.stats.hours.php, exec.squid.stats.quotaday.php --quotatemp
 * ------------------------------------------------------------------------------------------
	$TableSizeHours="sizehour_".date("YmdH",$xtime);
	$tableYoutube="youtubehours_".date("YmdH",$xtime);
	$tableSearchWords="searchwords_".date("YmdH",$xtime);
 * 
 * 
 * 
 */
start();

function start(){
	$unix=new unix();
	$pidfile="/var/run/squid-stats-central.pid";
	$timefile="/var/run/squid-stats-central.run";
	$sock=new sockets();
	
	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){
		stats_admin_events(0,"MySQL server not ready, delay task..." ,null,__FILE__,__LINE__);
		$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
		die();
	}
	
	
	if(!is_file("/home/artica/categories_databases/CATZ_ARRAY")){
		stats_admin_events(0,"Statistics databases are not installed !" ,null,__FILE__,__LINE__);
		
	}
	
	$WizardStatsApplianceDisconnected=intval($sock->GET_INFO("WizardStatsApplianceDisconnected"));
	if($WizardStatsApplianceDisconnected==1){
		start_export();
		export_push();
		die();
	}
	
	start_import(true);
	
	$sock->SQUID_DISABLE_STATS_DIE();
	
	$pid=@file_get_contents($pidfile);
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		percentage("{disabled}",100);
		stats_admin_events(2,"100%) Statistics are disabled");
		return;}
	
	@unlink($timefile);
	@unlink("/var/run/squid-stats-central.stop");
	@file_put_contents($timefile, time());	
	
$tSource=time();

$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
$EXEC_NICE=$unix->EXEC_NICE();
$Prefix="/usr/share/artica-postfix";
$q=new mysql_squid_builder();
$GLOBALS["Q"]=$q;
@mkdir("/home/artica/categories_databases",0755,true);
$unix->chmod_func(0755, "/home/artica/categories_databases/*");
$unix->chmod_func(0755, "/home/artica/categories_perso/*");

$t=time();

$t=time();
percentage("Purge old days",1);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squidlogs.purge.php"));
stats_admin_events(2,"1%) Purge days took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);

percentage("Compile personal tables...",2);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.compile_category_perso.php"));
Step2();

$t=time();
percentage("Running Quota day",2);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.quotaday.php"));
stats_admin_events(2,"2%) Quota day executed took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

percentage("Running Youtube Hours",2);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.youtube.days.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"2%) Youtube Hours executed took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --thumbs-parse >/dev/null 2>&1 &");

percentage("table_days()",2);
table_days();

percentage("Repair Members tables",2);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.members.hours.php --repair --byschedule --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));

percentage("Repair Sum tables",2);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.totals.php --repair --byschedule --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.repair.php --coherences-tables --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));

percentage("WeekDaysNums()",2);
WeekDaysNums();
stats_admin_events(2,"2%) Fix tables executed took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);


$t=time();
percentage("Scanning nodes",3);
nodes_scan();
stats_admin_events(2,"3%) Scanning nodes executed took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);


$t=time();
percentage("Scanning Active Directory",4);
shell_exec(trim("$nohup $EXEC_NICE $php5 $Prefix/exec.clientad.php >/dev/null 2>&1 &"));
stats_admin_events(2,"4%) Scanning Active Directory executed took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);



$t=time();
percentage("Running Active directory translation",7);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.ad.ous.php"));
stats_admin_events(2,"7%) Active directory translation took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Running Search Words hourly",8);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid-searchwords.php --hour"));
stats_admin_events(2,"8%) Running Search Words hourly took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Repair tables",9);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.hours.php --repair --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"9%) Running Search Words hourly took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("Running Clients Hourly (clients_hours())",10);
clients_hours();
stats_admin_events(2,"10%) Running Clients Hourly took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Search Words Hourly",10);
shell_exec(trim("$EXEC_NICE $php5 $Prefix//usr/share/artica-postfix/exec.squid-searchwords.php --hour --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"10%)  Search Words Hourly:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Running Members hour",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --members --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"11%) Running Members hour took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Repair UserSizeD",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --UserSizeD --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"11%) Repair UserSizeD took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("UserAuthDaysGrouped",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --members-central-grouped --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"11%) Repair UserAuthDaysGrouped took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("quotaday (quotamonth)...",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.quotaday.php --quotamonth --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"33%)  Months tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Repair Youtube Ids",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.repair.php --youtube --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"12%) Repair Youtube Ids took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("Running Youtube statistics",11);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.youtube.days.php --youtube-dayz --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"12%) Running Search Words hourly took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Summarize days",12);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --summarize-days --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"11%) Summarize days took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Running Blocked threats day",13);
stats_admin_events(2,"13%) Blocked threats day:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("Running Visited day",15);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --visited-days --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"15%) Visited day took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Running Days Websites",20);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.days.websites.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"20%)  Days Websites took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Week tables...",21);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --week --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"29%)  Week tables... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Repair tables",26);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.totals.php --repair --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"26%)  Repair tables took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

percentage("Youtube All",26);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.youtube_uid.php --all --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"26%)  Youtube All took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("Cache performances",27);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --webcacheperfs --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"27%)  Cache performances took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Interface elements",28);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.totals.php --interface --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"28%)  Interface elements took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Members central...",29);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --members-central --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"29%)  Members central... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Search Words Weekly",29);
shell_exec(trim("$EXEC_NICE $php5 $Prefix//usr/share/artica-postfix/exec.squid-searchwords.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"29%)  Search Words Weekly... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Week tables ( blocked )...",30);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.blocked.week.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"30%)  Week tables ( blocked ).... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Months tables (1) ...",31);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --scan-months --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"31%)  Months tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }




$t=time();
percentage("Months tables (2) ...",32);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.month.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"32%)  Months tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Categorize last 7 days...",37);




$t=time();
percentage("Categorize Month tables (3) ...",32);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.not-categorized.php --months --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"31%)  Months tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }




$t=time();
percentage("Months tables by users (4)...",35);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.uid-month.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"35%)  Months tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }




$t=time();
percentage("Repair categories",40);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --repair-categories --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"40%)  Repair categories.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Categorize last days",45);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.categorize-table.php --last-days --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"45%)  Categorize last days.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Visited Websites",46);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --visited-sites2 --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"25%)  Visited Websites took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }




$t=time();
percentage("Dangerous elements",46);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.dangerous.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"28%)  Interface elements took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }



$t=time();
percentage("Categorize all tables",46);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.categorize-table.php --all --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"46%)  Categorize all tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Scanning Not categorized",47);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.not-categorized.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"47%)  Categorize all tables.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Recategorize",48);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.recategorize.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"48%) Recategorize.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Sync categories",49);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --sync-categories --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"49%) Recategorize.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Global categories",50);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.global.categories.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"50%) Global categories.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Parse thumbs",55);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.php --thumbs-parse --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"50%) Parse thumbs.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

percentage("Repair not categorized",55);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.recategorize.missed.php --repair --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

percentage("Not categorized - last 7 days",55);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.recategorize.missed.php --last7-days --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"5%) Parse thumbs.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Reports",60);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.reports.php --all --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"60%) Reports.... took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Statistics by User",70);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.members_uid.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"70%) Statistics by user took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


$t=time();
percentage("Statistics by Users/Websites",71);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.websites_uid.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"71%) Statistics by user/Websites took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Statistics by Users/MAC",73);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.members_mac.php  --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"73%) Statistics by user/mac took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Statistics by Users/MAC/IP",73);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.members_macip.php  --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"73%) Statistics by user/mac/ip took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Statistics by Users/Blocked",74);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.blocked_uid.php  --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"74%) Statistics by user/blocked:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

$t=time();
percentage("Statistics by User/Youtube",75);
shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.youtube_uid.php  --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
stats_admin_events(2,"74%) Statistics by user/youtube:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }


percentage("Restarting MySQL categories service",90);
shell_exec("/etc/init.d/categories-db restart");
percentage("{finish}:" .date("Y-m-d H:i:s"),100);








}


function percentage($text,$purc){
	
	
	$array["TITLE"]=$text." ".date("d H:i:s");
	$array["POURC"]=$purc;
	@file_put_contents("/usr/share/artica-postfix/ressources/squid.stats.progress.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/squid.stats.progress.inc",0755);
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] [$purc] $text";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";} 
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
	
}
function events_tail($text,$line=0){
	error_log($text);
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] $text Line: $line";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";}
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
}

function clients_hours($nopid=false){
	
	if($GLOBALS["VERBOSE"]){
		echo "L.[".__LINE__."]: processing clients_hours()\n";
	}
	if(isset($GLOBALS["clients_hours_executed"])){
		if($GLOBALS["VERBOSE"]){echo "clients_hours():: Already executed\n";}
		return true;
	}
	$GLOBALS["clients_hours_executed"]=true;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if(!$nopid){
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already executed pid:$pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}

	$currenttable="dansguardian_events_".date('Ymd');
	$next_table=date('Ymd')."_hour";
	
	echo "L.[".__LINE__."]:_clients_hours_perfom($currenttable,$next_table)\n";
	
	
	
	
	_clients_hours_perfom($currenttable,$next_table);


	$q=new mysql_squid_builder();


	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as suffix,tablename FROM tables_day WHERE Hour=0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){events_tail("$q->mysql_error");return;}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){
		if($GLOBALS["VERBOSE"]){echo "clients_hours():: No datas ". __FUNCTION__." ".__LINE__."\n";}
		return;
	}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$next_table=$ligne["suffix"]."_hour";
		if(!$q->CreateHourTable($next_table)){events_tail("Failed to create $next_table");return;}
		if(!_clients_hours_perfom($ligne["tablename"],$next_table)){events_tail("Failed to process {$ligne["tablename"]} to $next_table");return;}
		$q->QUERY_SQL("UPDATE tables_day SET `Hour`=1 WHERE tablename='{$ligne["tablename"]}'");
	}

	
}

function _clients_hours_perfom($tabledata,$nexttable){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nice=$unix->EXEC_NICE();
	@unlink("/etc/artica-postfix/shell_clients_hours_perfom");
	percentage("$nice $php ".__FILE__." --clients-hours-perform $tabledata $nexttable",10);
	shell_exec("$nice $php ".__FILE__." --clients-hours-perform $tabledata $nexttable");
	$result=intval(@file_get_contents("/etc/artica-postfix/shell_clients_hours_perfom"));
	if($result==1){return true;}
}



function _shell_clients_hours_perfom($tabledata,$nexttable){
	$filter_hour=null;
	$filter_hour_1=null;
	$filter_hour_2=null;
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	if(isset($GLOBALS["$tabledata$nexttable"])){
		if($GLOBALS["VERBOSE"]){echo "$tabledata -> $nexttable already executed, return true\n";}
		return true;
	}

	$GLOBALS["$tabledata$nexttable"]=true;

	echo "L.[".__LINE__."]:CreateHourTable($nexttable)\n";
	$GLOBALS["Q"]->CreateHourTable($nexttable);
	$todaytable=date('Ymd')."_hour";
	$CloseTable=true;
	$output_rows=false;


	if($nexttable==$todaytable){
		$filter_hour_1="AND HOUR < HOUR( NOW())";
		$CloseTable=false;
	}

	if(!$CloseTable){
		if($GLOBALS["VERBOSE"]){echo "Ordered to not close table `$nexttable` == `$todaytable`...\n";}
	}

	echo "L.[".__LINE__."]: Processing $tabledata -> $nexttable  (today is $todaytable) filter:'$filter_hour_1' in line \n";
	
	percentage("Processing $tabledata -> $nexttable",10);
	
	events_tail("Processing $tabledata -> $nexttable  (today is $todaytable) filter:'$filter_hour_1' in line ".__LINE__);
	if(!$GLOBALS["Q"]->TABLE_EXISTS($tabledata)){
		events_tail("Create $tabledata in line ".__LINE__);
		$GLOBALS["Q"]->CheckTables($tabledata);
		@file_put_contents("/etc/artica-postfix/shell_clients_hours_perfom", 1);
		return true;
	}

	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS HOUR , CLIENT, Country, uid, sitename,MAC,hostname,account
	FROM $tabledata
	GROUP BY cached, HOUR( zDate ) , CLIENT, Country, uid, sitename,MAC,hostname,account
	HAVING QuerySize>0  $filter_hour_1$filter_hour_2";
	
	
	echo "L.[".__LINE__."]: $sql\n";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows=mysql_num_rows($results);
	
	echo "L.[".__LINE__."]: Processing $tabledata -> $num_rows  rows\n";
	
	if($num_rows<10){$output_rows=true;}

	if($num_rows==0){
		events_tail("$tabledata no rows...");
		if($CloseTable){
			events_tail("$tabledata -> Close table");
			$sql="UPDATE tables_day SET Hour=1 WHERE tablename='$tabledata'";
			$GLOBALS["Q"]->QUERY_SQL($sql);
		}
		@file_put_contents("/etc/artica-postfix/shell_clients_hours_perfom", 1);
		return true;
	}

	$prefix="INSERT IGNORE INTO $nexttable (zMD5,sitename,client,hour,remote_ip,country,size,hits,uid,category,cached,familysite,MAC,hostname,account) VALUES ";
	$prefix_visited="INSERT IGNORE INTO visited_sites (sitename,category,country,familysite) VALUES ";
	$f=array();
	$zzz=0;
	$yyy=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=addslashes(trim(strtolower($ligne["sitename"])));
		$client=addslashes(trim(strtolower($ligne["CLIENT"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
		$Country=mysql_escape_string2(trim(strtolower($ligne["Country"])));
		$category=null;
		$familysite=$GLOBALS["Q"]->GetFamilySites($sitename);
		$ligne["Country"]=mysql_escape_string2($ligne["Country"]);
		$SQLSITESVS[]="('$sitename','$category','{$ligne["Country"]}','$familysite')";
		$yyy++;
		$zzz++;
		if($zzz>500){
			$mem=round((memory_get_usage(true)/1024)/1000);
			$xprec=($yyy/$num_rows)*100;
			$xprec=round($xprec,2);
			percentage("[{$tabledata}]: Processing [{$xprec}%]$yyy/$num_rows ({$mem}MB)",10);
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			$zzz=0;
		}


				$md5=md5("{$ligne["sitename"]}{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["MAC"]}{$ligne["Country"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}{$ligne["cached"]}{$ligne["account"]}$Country");
				$sql_line="('$md5','$sitename','$client','{$ligne["HOUR"]}','$client','$Country','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','$category','{$ligne["cached"]}',
				'$familysite','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
				$f[]=$sql_line;

				if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}

				if(count($f)>500){
		if($GLOBALS["VERBOSE"]){echo "Processing -> SQL -> $yyy/$num_rows in line ".__LINE__."\n";}
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
		$f=array();
	}
	if(count($SQLSITESVS)>0){
	$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
	$SQLSITESVS=array();
}

}

	if(count($f)>0){
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
	events_tail("Processing ". count($f)." rows");
	if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}

	if(count($SQLSITESVS)>0){
		events_tail("Processing ". count($SQLSITESVS)." visited sites");
		$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error} in line " .	__LINE__);}
}
}
	@file_put_contents("/etc/artica-postfix/shell_clients_hours_perfom", 1);
	return true;
}

function table_days(){
	$unix=new unix();
	$GLOBALS["Q"]=new mysql_squid_builder();

	if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }

	percentage("Executed table_days",2);
	$tables=$GLOBALS["Q"]->LIST_TABLES_QUERIES();
	if(count($tables)==0){
		percentage("No working tables ?",2);
		events_tail("No working tables ? in line ".__LINE__);return;}
	$today=date('Y-m-d');
	percentage(count($tables)." tables to scan",2);

	while (list ($tablename, $date) = each ($tables) ){
		if($today==$date){ events_tail("Skipping Today table $tablename in line ".__LINE__); continue; }
		if(!$GLOBALS["Q"]->TABLE_EXISTS($tablename)){events_tail("Skipping Today table $tablename in line (did not exists)".__LINE__); continue; }


		$sql="SELECT zDate FROM tables_day WHERE tablename='$tablename'";
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
		if($ligne["zDate"]<>null){ 
			events_tail("Skipping Today table $tablename -->{$ligne["zDate"]} ",__LINE__);
			continue;
		}
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(QuerySize) as tsize FROM $tablename WHERE cached=0"));
		$notcached=$ligne["tsize"];
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(QuerySize) as tsize FROM $tablename WHERE cached=1"));
		$cached=$ligne["tsize"];
		if(!is_numeric($notcached)){$notcached=0;}
		if(!is_numeric($cached)){$cached=0;}
		$totalsize=$notcached+$cached;
		$cache_perfs=round(($cached/$totalsize)*100);
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(hits) as thist FROM $tablename"));
		$requests=$ligne["thist"];
		events_tail("table_days():: $date cached = $cached , not cached =$notcached total=$totalsize perf=$cache_perfs% requests=$requests",__LINE__);
		$GLOBALS["Q"]->QUERY_SQL("INSERT INTO tables_day (tablename,zDate,size,size_cached,totalsize,cache_perfs,requests)
				VALUES('$tablename','$date','$notcached','$cached','$totalsize','$cache_perfs','$requests');");
		if(!$GLOBALS["Q"]->ok){
			events_tail("{$GLOBALS["Q"]->mysql_error}",__LINE__);
		}
		
	}

}

function WeekDaysNums(){
	if(isset($GLOBALS["ALREADYWeekDaysNums"])){return;}
	$GLOBALS["ALREADYWeekDaysNums"]=true;
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate,DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) as WeekNumber FROM tables_day WHERE WeekNum=0";
	$results=$q->QUERY_SQL($sql);
	
	
	events_tail("WeekDaysNums(): WeekNum=0 -> ".mysql_num_rows($results),__LINE__);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zDate=$ligne["zDate"];
		$WeekNumber=intval($ligne["WeekNumber"]);
		
		if($WeekNumber==0){
			$sql="SELECT WEEK('$zDate 00:00:00') as WeekNumber";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$WeekNumber=$ligne2["WeekNumber"];
			events_tail("WeekDaysNums(): $zDate: ERROR, REPAIR $zDate 00:00:00 -> $WeekNumber",__LINE__);
		}
		
		events_tail("WeekDaysNums(): $zDate: {$ligne["tablename"]} -> WeekDay={$ligne["DayNumber"]}, WeekNum=$WeekNumber",__LINE__);
		$q->QUERY_SQL("UPDATE tables_day SET WeekDay={$ligne["DayNumber"]},WeekNum=$WeekNumber
		WHERE tablename='{$ligne["tablename"]}'");
		
		if(!$q->ok){events_tail("WeekDaysNums(): $q->mysql_error",__LINE__);}

	}


}

function nodes_scan(){
	$f=array();
	$GLOBALS["Q"]=new mysql_squid_builder();
	if(!$GLOBALS["Q"]->TABLE_EXISTS("webfilters_nodes")){$GLOBALS["Q"]->CheckTables();}
	if(!$GLOBALS["Q"]->TABLE_EXISTS("webfilters_nodes")){writelogs("Fatal, webfilters_nodes, nos such table",__FILE__,__FUNCTION__,__LINE__);return;}
	$sql="SELECT MAC FROM UserAutDB GROUP BY MAC";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$prefix="INSERT IGNORE INTO webfilters_nodes (`MAC`) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(trim($ligne["MAC"])==null){continue;}
		if(strpos($ligne["MAC"], ":")==0){continue;}
		$f[]="('{$ligne["MAC"]}')";

	}
	if(count($f)>0){$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));}
}




function start_export(){
	
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($ArticaProxyStatisticsBackupFolder==null){
		$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";
	}
	$ArticaProxyStatisticsBackupFolder=$ArticaProxyStatisticsBackupFolder."/export";
	$LIST_TABLES_YOUTUBE_HOURS=$q->LIST_TABLES_YOUTUBE_HOURS();
	$LIST_TABLES_SIZEHOURS=$q->LIST_TABLES_SIZEHOURS();
	$LIST_TABLES_QUOTA_HOURS=$q->LIST_TABLES_QUOTA_HOURS();
	$LIST_TABLES_QUOTADAY=$q->LIST_TABLES_QUOTADAY();
	$LIST_TABLES_QUOTAMONTH=$q->LIST_TABLES_QUOTAMONTH();
	$LIST_TABLES_SEARCHWORDS_HOURS=$q->LIST_TABLES_SEARCHWORDS_HOURS();
	$LIST_TABLES_SEARCHWORDS_DAY=$q->LIST_TABLES_SEARCHWORDS_DAY();
	$LIST_TABLES_dansguardian_events=$q->LIST_TABLES_dansguardian_events();
	$LIST_TABLES_HOURS=$q->LIST_TABLES_HOURS();
	$LIST_TABLES_USERSIZED=$q->LIST_TABLES_USERSIZED();
	
	$LIST_TABLES_YOUTUBE_WEEK=$q->LIST_TABLES_YOUTUBE_WEEK();
	$LIST_TABLES_WEEKS=$q->LIST_TABLES_WEEKS();
	$LIST_TABLES_MEMBERS=$q->LIST_TABLES_MEMBERS();
	$LIST_TABLES_GSIZE=$q->LIST_TABLES_GSIZE();
	$LIST_TABLES_GCACHE=$q->LIST_TABLES_GCACHE();
	$LIST_TABLES_VISITED=$q->LIST_TABLES_VISITED();
	$LIST_TABLES_BLOCKED=$q->LIST_TABLES_BLOCKED();
	$LIST_TABLES_BLOCKED_WEEK=$q->LIST_TABLES_BLOCKED_WEEK();
	$LIST_TABLES_BLOCKED_DAY=$q->LIST_TABLES_BLOCKED_DAY();
	$LIST_TABLES_WWWUID=$q->LIST_TABLES_WWWUID();
	$LIST_CAT_FAMDAY=$q->LIST_CAT_FAMDAY();

	
	
	while (list ($tablename, $none) = each ($LIST_TABLES_YOUTUBE_HOURS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	while (list ($tablename, $none) = each ($LIST_TABLES_SIZEHOURS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_QUOTADAY) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_QUOTAMONTH) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_SEARCHWORDS_HOURS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}

	while (list ($tablename, $none) = each ($LIST_TABLES_SEARCHWORDS_DAY) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}

	while (list ($tablename, $none) = each ($LIST_TABLES_QUOTA_HOURS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	
	while (list ($tablename, $none) = each ($LIST_TABLES_dansguardian_events) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	
	while (list ($tablename, $none) = each ($LIST_TABLES_HOURS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	
	while (list ($tablename, $none) = each ($LIST_TABLES_USERSIZED) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_BLOCKED_WEEK) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_BLOCKED) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	
	
	while (list ($tablename, $none) = each ($LIST_TABLES_YOUTUBE_WEEK) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_WEEKS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_MEMBERS) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	while (list ($tablename, $none) = each ($LIST_TABLES_GSIZE) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	
	while (list ($tablename, $none) = each ($LIST_TABLES_GCACHE) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	
	while (list ($tablename, $none) = each ($LIST_TABLES_VISITED) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_TABLES_BLOCKED_DAY) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}	
	while (list ($tablename, $none) = each ($LIST_CAT_FAMDAY) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	while (list ($tablename, $none) = each ($LIST_TABLES_WWWUID) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_SOURCES[$tablename]=true;
	}
	
	
	
	
	while (list ($tablename, $none) = each ($EXPORT_SOURCES) ){
		if(trim($tablename)==null){continue;}
		$EXPORT_DESTINATIONS[]=$tablename;
	}
	
	@mkdir($ArticaProxyStatisticsBackupFolder,0755,true);
	$target_file=$ArticaProxyStatisticsBackupFolder."/".date("Ymd").".sql.gz";
	if(is_file($target_file)){@unlink($target_file);}
	$EXPORT_DESTINATIONS[]="visited_sites";
	$EXPORT_DESTINATIONS[]="youtube_objects";
	$EXPORT_DESTINATIONS[]="UserAgents";
	$EXPORT_DESTINATIONS[]="UserAutDB";
	$EXPORT_DESTINATIONS[]="UserAuthDays";
	$EXPORT_DESTINATIONS[]="UserAuthDaysGrouped";
	$EXPORT_DESTINATIONS[]="UserSizeRTT";
	$EXPORT_DESTINATIONS[]="allsizes";
	
	
	
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$bzip2=$unix->find_program("bzip2");
	$bzip2_cmd="| $bzip2 ";
	$AllTables=@implode(" ", $EXPORT_DESTINATIONS);
	$cmd="$mysqldump -S /var/run/mysqld/squid-db.sock --single-transaction --skip-add-drop-table --no-create-db --insert-ignore --skip-add-locks --skip-lock-tables squidlogs $AllTables $bzip2_cmd> $target_file 2>&1";
	$t=time();
	$failed=false;
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Couldn't#", $line)){
			@unlink($target_file);
			stats_admin_events(0,"Exporting tables failed $line took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
			return;
		}
		
		if(preg_match("#Error\s+([0-9]+)#",$line)){
			@unlink($target_file);
			stats_admin_events(0,"Exporting tables failed $line took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
			return;
		}
		
		echo "$line\n";
	}
	
	$size=@filesize($target_file);
	if($size<10000){
		@unlink($target_file);
		stats_admin_events(0,"Exporting tables failed {$size}Bytes < 10000bytes took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
		return;
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "$target_file {$size}Bytes ".FormatBytes($size/1024)."\n";}
	
	reset($EXPORT_SOURCES);
	while (list ($tablename, $none) = each ($EXPORT_SOURCES) ){
		$q->QUERY_SQL("DROP TABLE $tablename");
		if($GLOBALS["VERBOSE"]){echo "Removing table $tablename\n";}
	}
	
	
}

function export_push(){
	$sock=new sockets();
	$unix=new unix();
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($ArticaProxyStatisticsBackupFolder==null){
		$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";
	}
	$ArticaProxyStatisticsBackupFolder=$ArticaProxyStatisticsBackupFolder."/export";	
	
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php";
	
	if($GLOBALS["VERBOSE"]){echo "$uri\n";}
	$credentials["MANAGER"]=$WizardStatsAppliance["MANAGER"];
	$credentials["PASSWORD"]=$WizardStatsAppliance["MANAGER-PASSWORD"];
	
	
	
	
	$files=$unix->DirFiles($ArticaProxyStatisticsBackupFolder);
	if($GLOBALS["VERBOSE"]){echo "PUSH Scanning $ArticaProxyStatisticsBackupFolder\n";}
	while (list ($filename, $none) = each ($files) ){
		
		$size=@filesize("$ArticaProxyStatisticsBackupFolder/$filename");
		
		$array=array("HOSTNAME"=>$unix->hostname_g(),"SIZE"=>$size,"FILENAME"=>$filename,"creds"=>base64_encode(serialize($credentials)));
		
		if($GLOBALS["VERBOSE"]){echo "PUSH $ArticaProxyStatisticsBackupFolder/$filename\n";}
		
		$curl=new ccurl($uri,false,null,true);
		if(!$curl->postFile("SQUID_STATS_CONTAINER","$ArticaProxyStatisticsBackupFolder/$filename",$array)){
			if($GLOBALS["VERBOSE"]){echo "Posting informations Failed $curl->error...\n";}
			$this->events("Failed $curl->error",__FUNCTION__,__FILE__,__LINE__);
				
		}
		
		if(!preg_match("#<RESULTS>(.*?)</RESULTS>#is", $curl->data,$re)){
			stats_admin_events(0, "{$WizardStatsAppliance["SERVER"]} did not report something", $curl->data,__FILE__,__LINE__);
			continue;
		}
		
		$RESULT=$re[1];
		if($RESULT<>"SUCCESS"){
			stats_admin_events(0, "{$WizardStatsAppliance["SERVER"]} report $RESULT", $curl->data,__FILE__,__LINE__);
			continue;
		}
		
		@unlink("$ArticaProxyStatisticsBackupFolder/$filename");
		stats_admin_events(0, "Success uploading $filename to {$WizardStatsAppliance["SERVER"]} report $RESULT", $curl->data,__FILE__,__LINE__);
	}
	
	
}

function start_import($aspid=false){
	$sock=new sockets();
	$syslog=new mysql_storelogs();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if(!$aspid){
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			writelogs("Already executed pid:$pid",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		$mypid=getmypid();
		
	}
	
	@file_put_contents($pidfile,$mypid);
	
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($ArticaProxyStatisticsBackupFolder==null){
		$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";
	}
	$ArticaProxyStatisticsBackupFolder=$ArticaProxyStatisticsBackupFolder."/import";
	$files=$unix->DirFiles($ArticaProxyStatisticsBackupFolder);
	if($GLOBALS["VERBOSE"]){echo "PUSH Scanning $ArticaProxyStatisticsBackupFolder\n";}
	$mysql=$unix->find_program("mysql");
	$bzip2=$unix->find_program("bzip2");
	
	
	
	while (list ($filename, $none) = each ($files) ){
		$size=@filesize("$ArticaProxyStatisticsBackupFolder/$filename");
		if($GLOBALS["VERBOSE"]){echo "IMPORT $ArticaProxyStatisticsBackupFolder/$filename\n";}
		
		$f=array();
		$results=array();
		
		$f[]="$bzip2 -d -c $ArticaProxyStatisticsBackupFolder/$filename |";
		$f[]="$mysql --show-warnings";
		$f[]="--socket=/var/run/mysqld/squid-db.sock";
		$f[]="--protocol=socket --user=root --batch --force";
		$f[]="--debug-info --database=squidlogs 2>&1";
		$cmd=@implode(" ", $f);
		$results[]=$cmd;
		exec($cmd,$results);
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $results)."\n";}
		stats_admin_events(2, "Success importing $filename to MySQL", @implode("\n", $results));
		$syslog->ROTATE_TOMYSQL("$ArticaProxyStatisticsBackupFolder/$filename");
		
	}
		
	
}

function Step2(){
	$q=new mysql_squid_builder();
	percentage("Fix tables",2);
	$q->FixTables();
	percentage("check_to_hour_tables",2);
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_to_hour_tables(true);
	percentage("not_categorized_day_scan",2);
	$squid_stats_tools->not_categorized_day_scan();	
	
}


