<?php
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');


postfix_hours();

function postfix_hours(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Already PID $pid running since {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}	
	
	if(!$GLOBALS["VERBOSE"]){
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){return;}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$LIST_HOUR_TABLES=$GLOBALS["Q"]->LIST_HOUR_TABLES();
	if(count($LIST_HOUR_TABLES)==0){return;}
	$currentHourTable=date("YmdH")."_hour";
	$MyTime=time();
	while (list ($tablesource, $time) = each ($LIST_HOUR_TABLES) ){
		if($currentHourTable==$tablesource){continue;}
		$xtime=strtotime($time);
		if($xtime>$MyTime){
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablesource`");
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$tablesource\t$time\n";}
		
		if(_parse_hour_table($tablesource,$xtime)){
			$GLOBALS["Q"]->DUMP_TABLE($tablesource);
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablesource`");
		}else{
			if($GLOBALS["VERBOSE"]){echo "$tablesource\t$time FAILED\n";}
		}
		
	}
}

function _parse_hour_table($tablesource,$xtime){
	$tableDest=date("Ymd")."_day";
	if(!$GLOBALS["Q"]->BuildDayTable($tableDest)){
		if($GLOBALS["VERBOSE"]){echo "$tableDest Error\n";}
		return false;}
		
		
		$sql="SELECT COUNT(zhour) as hits,zhour,mailfrom,instancename,
		mailto,domainfrom,domainto,senderhost,recipienthost,SUM(mailsize) as mailsize,
		smtpcode FROM $tablesource
		GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode";
		
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){
			if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}
			system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
		
		
		if(mysql_num_rows($results)==0){
			if($GLOBALS["VERBOSE"]){echo "No row\n";}
			return true;
		}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$md5=md5(@serialize($ligne));
			$f[]="('$md5','{$ligne["zhour"]}','{$ligne["hits"]}','{$ligne["mailsize"]}','{$ligne["mailfrom"]}',
			'{$ligne["instancename"]}','{$ligne["mailto"]}','{$ligne["domainfrom"]}','{$ligne["domainto"]}',
			'{$ligne["senderhost"]}','{$ligne["recipienthost"]}','{$ligne["smtpcode"]}')";
		
			if(count($f)>1500){
				if($GLOBALS["VERBOSE"]){echo count($f)." items\n";}
			$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
					`instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
					`smtpcode`) VALUES ".@implode(",", $f);
					$GLOBALS["Q"]->QUERY_SQL($sql);
					if(!$GLOBALS["Q"]->ok){
						if($GLOBALS["VERBOSE"]){echo "$tableDest {$GLOBALS["Q"]->mysql_error} Error\n";}
						system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
						return;
					}
					$f=array();
			}
		
			}
		
			if(count($f)>0){
				if($GLOBALS["VERBOSE"]){echo count($f)." items\n";}
				$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
				`instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
				`smtpcode`) VALUES ".@implode(",", $f);
				$GLOBALS["Q"]->QUERY_SQL($sql);
				if(!$GLOBALS["Q"]->ok){
					if($GLOBALS["VERBOSE"]){echo "$tableDest {$GLOBALS["Q"]->mysql_error} Error\n";}
					system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
					return;
				}
			}
		
return true;		
}






//SELECT COUNT(zmd5) as tcount, zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,SUM(mailsize) as mailsize,smtpcode FROM 2014021623_hour GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode