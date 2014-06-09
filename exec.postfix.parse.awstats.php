<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

parseQueue();

function parseQueue(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		events("Already process PID: $pid running since $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	
	if(system_is_overloaded(basename(__FILE__))){
		events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	
	$EnableArticaSMTPStatistics=$sock->GET_INFO("EnableArticaSMTPStatistics");
	if(!is_numeric($EnableArticaSMTPStatistics)){$EnableArticaSMTPStatistics=0;}
	
	
	$directory="/var/log/artica-mail";
	if(!is_dir($directory)){return;}
	if (!$handle = @opendir($directory)) {return;}
	
	
	$q=new mysql_postfix_builder();
	$q->CheckTables();
		
	
	
	events("open $directory");
	while (false !== ($filename = readdir($handle))) {
		if($EnableArticaSMTPStatistics==0){ @unlink("$directory/$filename"); continue; }
		if(!preg_match("#(.+?)\.[0-9]+\.aws#", $filename,$re)){continue;}
		$instancename=$re[1];
		ParseFile("$directory/$filename");
		if(system_is_overloaded(basename(__FILE__))){
			system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}
}


function ParseFile($filename,$instancename=null){
	events("Parsing  $filename, instance=$instancename");
	$f=explode("\n",@file_get_contents($filename));
	$q=new mysql_postfix_builder();
	@mkdir("/var/log/artica-postfix/Postfix-sql-error",0755,true);
	
	while(list( $num, $line ) = each ($f)){
		if(trim($line)==null){continue;}
		usleep(500);
		if(preg_match("#(.+?)\s+([0-9\:]+)\s+(.+?)\s+(.+?)\s+(.+)\s+(.*?)\s+SMTP\s+-\s+([0-9]+)\s+([0-9]+)#i", $line,$re)){
			
			while(list( $num, $line ) = each ($re)){
				$line=str_replace("'", "", $line);
				$line=mysql_escape_string2($line);
				$re[$num]=$line;
				
			}
			
			$md5=md5(@implode("", $re));
			$zDate="{$re[1]} {$re[2]}";
			$time=strtotime($zDate);
			
			
			$year=date("Y",$time);
			if($year<>date("Y")){
				$zDate=date("Y")."-".date("m-d",$time)." ".date("H:i:s",$time);
				$time=strtotime($zDate);
			}
			$month=date("m",$time);
			if($month<>date("m")){
				$zDate=date("Y")."-".date("m")."-".date("d",$time)." ".date("H:i:s",$time);
				$time=strtotime($zDate);
			}
		
			$table_hour=date("YmdH",$time)."_hour";
			if($re[3]=="<>"){$re[3]="unknown";}
			if($re[4]=="<>"){$re[4]="unknown";}
			if($GLOBALS["VERBOSE"]){echo "$table_hour:$time  From:{$re[3]} to {$re[4]}\n";}
			$from=$re[3];
			$to=$re[4];
			
			
			$from_domain=null;
			$to_domain=null;
			if(strpos($from, "@")>0){
				$tb=explode("@", $from);
				$from_domain=$tb[1];
			}
			if(strpos($to, "@")>0){
				$tb=explode("@", $to);
				$to_domain=$tb[1];
			}
			$hour=date("H",$time);
			
			$senderHost=$re[5];
			$recipientHost=$re[6];
			$SMTPCode=$re[7];
			$MailSize=$re[8];
			$from=strtolower(str_replace("'", "", $from));
			$to=strtolower(str_replace("'", "", $to));
			$from_domain=strtolower(str_replace("'", "", $from_domain));
			$to_domain=strtolower(str_replace("'", "", $to_domain));
			
			$SQL_ARRAY[$table_hour][]="('$md5','$zDate','$hour','$from','$to','$from_domain','$to_domain','$senderHost','$recipientHost','$MailSize','$SMTPCode','$instancename')";
			if(count($SQL_ARRAY[$table_hour])>5000){
				if(!$q->BuildHourTable($table_hour)){echo "Unable to build table $table_hour $q->mysql_error\n";return;}
				$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $SQL_ARRAY[$table_hour]);
				if(!$q->QUERY_SQL($sql)){
					echo $q->mysql_error."\n";
					@file_put_contents("/var/log/artica-postfix/Postfix-sql-error/".md5($sql), $sql);
					return;}
				$SQL_ARRAY[$table_hour]=array();
			}
			continue;
		}
		
		if(preg_match("#(.+?)\s+([0-9\:]+)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.*?)\s+SMTP\s+-\s+([0-9]+)\s+\?#i", $line,$re)){
			while(list( $num, $line ) = each ($re)){
				$line=str_replace("'", "", $line);
				$line=mysql_escape_string2($line);
				$re[$num]=$line;
			
			}
			
			$md5=md5(@implode("", $re));
			$zDate="{$re[1]} {$re[2]}";
			$time=strtotime($zDate);
			
			$year=date("Y",$time);
			if($year<>date("Y")){
				$zDate=date("Y")."-".date("m-d",$time)." ".date("H:i:s",$time);
				$time=strtotime($zDate);
			}
			$month=date("m",$time);
			if($month<>date("m")){
				$zDate=date("Y")."-".date("m")."-".date("d",$time)." ".date("H:i:s",$time);
				$time=strtotime($zDate);
			}
			
			
			$table_hour=date("YmdH",$time)."_hour";
			$hour=date("H",$time);
			$from=$re[3];
			$tb=explode("@", $from);
			$from_domain=$tb[1];			
			$to=$re[4];		
			$tb=explode("@", $to);
			$to_domain=$tb[1];			
			$senderHost=$re[5];	
			$SMTPCode=$re[7];
			$recipientHost=null;
			$from=strtolower(str_replace("'", "", $from));
			$to=strtolower(str_replace("'", "", $to));
			$from_domain=strtolower(str_replace("'", "", $from_domain));
			$to_domain=strtolower(str_replace("'", "", $to_domain));
			$MailSize=0;
			$SQL_ARRAY[$table_hour][]="('$md5','$zDate','$hour','$from','$to','$from_domain','$to_domain','$senderHost','$recipientHost','$MailSize','$SMTPCode','$instancename')";
			if(count($SQL_ARRAY[$table_hour])>5000){
				if(!$q->BuildHourTable($table_hour)){echo "Unable to build table $table_hour $q->mysql_error\n";return;}
				$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $SQL_ARRAY[$table_hour]);
				if(!$q->QUERY_SQL($sql)){
					events($q->mysql_error);
					@file_put_contents("/var/log/artica-postfix/Postfix-sql-error/".md5($sql), $sql);
					return;
				}
				$SQL_ARRAY[$table_hour]=array();
			}			
			continue;
		}
	}

	while(list( $table_hour, $s ) = each ($SQL_ARRAY)){	
		if(count($s)>0){
			if(!$q->BuildHourTable($table_hour)){
				events("Unable to build table $table_hour $q->mysql_error");
				return;
			}
			
			$count=$q->COUNT_ROWS($table_hour);
			
			$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $s);
			if(!$q->QUERY_SQL($sql)){
				@file_put_contents("/var/log/artica-postfix/Postfix-sql-error/".md5($sql), $sql);
				events($q->mysql_error);
				return;
			}
			
			$count2=$q->COUNT_ROWS($table_hour);
			$Allelements=count($s);
			$AddedElements=$count2-$count;
			events("$table_hour $Allelements elements ($AddedElements new added elements)");
			
		}
	}
	events("removing $filename");
	@unlink($filename);
	
	
}

function events($text,$sourcefunction=null,$sourcefile=null,$sourceline=0){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($sourcefile==null){$sourcefile=basename($trace[1]["file"]);}
			if($sourcefunction==null){$sourcefunction=$trace[1]["function"];}
			if($sourceline==null){$sourceline=$trace[1]["line"];}
		}
			
	}

	$unix=new unix();
	$unix->events($text,"/var/log/postfix.stats.log",false,$sourcefunction,$sourceline,basename(__FILE__));
}