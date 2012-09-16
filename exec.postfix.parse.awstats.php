<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}

parseQueue();

function parseQueue(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$oldpidTime=$unix->PROCCESS_TIME_MIN($oldpid);
		system_admin_events("Already process PID: $oldpid running since $oldpidTime minutes", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;}
	@file_put_contents($pidfile, getmypid());
	
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	$directory="/var/log/artica-mail";
	if(!is_dir($directory)){return;}
	if (!$handle = @opendir($directory)) {return;}
	
	
	$q=new mysql_postfix_builder();
	$q->CheckTables();
		
	
	
	
	while (false !== ($filename = readdir($handle))) {
		if(!preg_match("#(.+?)\.[0-9]+\.aws#", $filename,$re)){continue;}
		$instancename=$re[1];
		ParseFile("$directory/$filename");
		if(system_is_overloaded(basename(__FILE__))){
			system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}
}


function ParseFile($filename,$instancename){
	$f=explode("\n",@file_get_contents($filename));
	$q=new mysql_postfix_builder();
	
	
	while(list( $num, $line ) = each ($f)){
		if(trim($line)==null){continue;}
		
		if(preg_match("#(.+?)\s+([0-9\:]+)\s+(.+?)\s+(.+?)\s+(.+)\s+(.*?)\s+SMTP\s+-\s+([0-9]+)\s+([0-9]+)#i", $line,$re)){
			$md5=md5(@implode("", $re));
			$zDate="{$re[1]} {$re[2]}";
			$time=strtotime($zDate);
			$table_hour=date("YmdH",$time)."_hour";
			$hour=date("H",$time);
			$from=$re[3];
			$tb=explode("@", $from);
			$from_domain=$tb[1];
			$to=$re[4];
			$tb=explode("@", $to);
			$to_domain=$tb[1];
			$senderHost=$re[5];
			$recipientHost=$re[6];
			$SMTPCode=$re[7];
			$MailSize=$re[8];
			$SQL_ARRAY[$table_hour][]="('$md5','$zDate','$hour','$from','$to','$from_domain','$to_domain','$senderHost','$recipientHost','$MailSize','$SMTPCode','$instancename')";
			if(count($SQL_ARRAY[$table_hour])>5000){
				if(!$q->BuildHourTable($table_hour)){echo "Unable to build table $table_hour $q->mysql_error\n";return;}
				$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $SQL_ARRAY[$table_hour]);
				if(!$q->QUERY_SQL($sql)){echo $q->mysql_error."\n";return;}
				$SQL_ARRAY[$table_hour]=array();
			}
			continue;
		}
		
		if(preg_match("#(.+?)\s+([0-9\:]+)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.*?)\s+SMTP\s+-\s+([0-9]+)\s+\?#i", $line,$re)){
			$md5=md5(@implode("", $re));
			$zDate="{$re[1]} {$re[2]}";
			$time=strtotime($zDate);
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
			$MailSize=0;
			$SQL_ARRAY[$table_hour][]="('$md5','$zDate','$hour','$from','$to','$from_domain','$to_domain','$senderHost','$recipientHost','$MailSize','$SMTPCode','$instancename')";
			if(count($SQL_ARRAY[$table_hour])>5000){
				if(!$q->BuildHourTable($table_hour)){echo "Unable to build table $table_hour $q->mysql_error\n";return;}
				$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $SQL_ARRAY[$table_hour]);
				if(!$q->QUERY_SQL($sql)){echo $q->mysql_error."\n";return;}
				$SQL_ARRAY[$table_hour]=array();
			}			
			continue;
		}
	}

	while(list( $table_hour, $s ) = each ($SQL_ARRAY)){	
		if(count($s)>0){
			if(!$q->BuildHourTable($table_hour)){echo "Unable to build table $table_hour $q->mysql_error\n";return;}
			$sql="INSERT IGNORE INTO `$table_hour` (zmd5,ztime,zhour,mailfrom,mailto,domainfrom,domainto,senderhost,recipienthost,mailsize,smtpcode,instancename) VALUES " .@implode(",", $s);
			if(!$q->QUERY_SQL($sql)){echo $q->mysql_error."\n";return;}
		}
	}
	
	@unlink($filename);
	
	
}

