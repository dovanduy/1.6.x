<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

if($argv[1]=="--bycron"){
	visited_sites_by_cron();
	die();
}

visited_sites();


function badCharacters($sitename){
	$cha["$"]=true;
	$cha[";"]=true;
	$cha["#"]=true;
	$cha["!"]=true;
	$cha["%"]=true;
	$cha["'"]=true;
	$cha["@"]=true;
	
	while (list ($ca, $pid) = each ($cha)){
		if(strpos($sitename, $ca)>0){return true;}
		
	}
	return false;
	
}


function visited_sites_by_cron(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/squid.visited_sites_by_cron.pid";
	$TimeFile="/etc/artica-postfix/settings/Daemons/exec.squid.visited.sites.php.time";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$t=time();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$TimeExec=$unix->file_time_min($TimeFile);
	if($TimeExec<240){return;}
	visited_sites(true,true);
	
	
}

function visited_sites($nopid=false,$bycron=false){

	$unix=new unix();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/squid.visited_sites_rescan.pid";
		$pid=@file_get_contents($pidfile);
		if($pid<100){$pid=null;}
		$t=time();
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
			die();
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);	
	}
	
	stats_admin_events(2, "Starting table visited_sites", "",__FILE__,__LINE__);
	progress("Starting table visited_sites",5);
	$q=new mysql_squid_builder();
	$sql="SELECT sitename FROM visited_sites WHERE LENGTH(category)=0";
	$results=$q->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);
	$took=$unix->distanceOfTimeInWords($t,time());
	stats_admin_events(2, "Query done $num_rows websites to scan", "took:$took",__FILE__,__LINE__);
	
	
	if($num_rows==0){progress(null,100);return;}
	progress("Query done $num_rows websites to scan",10);
	

		$n=0;
		$c=0;
		$t=0;
		$d=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$sitenameOrg=$ligne["sitename"];
			$sitename=strtolower(trim($sitenameOrg));
			$n++;
			if(badCharacters($sitename)){
				$q->categorize_reaffected($sitename);
				$sitenameOrg=mysql_escape_string2($sitenameOrg);
				$q->QUERY_SQL("UPDATE visited_sites SET category='reaffected' WHERE `sitename`='$sitenameOrg'");
				if(!$q->ok){progress("Fatal",100);die();}
				$d++;
				$c++;
				continue;
			}			
			
			if(strpos($sitename, ".")==0){
				$q->categorize_reaffected($sitename);
				$sitenameOrg=mysql_escape_string2($sitenameOrg);
				$q->QUERY_SQL("UPDATE visited_sites SET category='reaffected' WHERE `sitename`='$sitenameOrg'");
				if(!$q->ok){progress("Fatal",100);die();}
				$d++;
				$c++;
				continue;
			}
			
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){
				$sitename=gethostbyaddr($sitename);
				if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$c++;continue;}
			}
			
			$ipaddr=gethostbyname($sitename);
			
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){
				$q->categorize_reaffected($sitenameOrg);
				$q->QUERY_SQL("UPDATE visited_sites SET category='reaffected' WHERE `sitename`='$sitenameOrg'");
				if(!$q->ok){progress("Fatal",100);die();}
				$d++;
				$c++;
				continue;				
			}
			
			$cat=$q->GET_CATEGORIES($sitename);
			if($cat<>null){$d++;$q->QUERY_SQL("UPDATE visited_sites SET category='$cat' WHERE `sitename`='$sitenameOrg'");if(!$q->ok){progress("Fatal",100);die();}}
			$c++;
			if($c>50){
				if($bycron){if(system_is_overloaded(__FILE__)){return;} if($n>2000){break;} }
				$t=$t+$c;
				$purc=$t/$num_rows;
				$purc=round($purc,2)*100;
				$c=0;
				if($purc>10){
					progress("$sitename $t/$num_rows",$purc);
				}
			}
			
			
		}
		
		progress($sitename,100);
		
		$took=$unix->distanceOfTimeInWords($t,time());
		
		
		
		if($d>0){
			stats_admin_events(2, "$d New categorized websites...", "took:$took",__FILE__,__LINE__);
		}
		
		
		
}

function progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid_visited_progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid_visited_progress",0777);
	
}
