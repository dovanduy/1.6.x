<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
$GLOBALS["SINGLE_DEBUG"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if($argv[1]=="--multi-start"){BuildRules();die();}
if($argv[1]=="--single-debug"){SingleDebug($argv[2]);die();}
if($argv[1]=="--monit"){build_monit();die();}
if($argv[1]=="--import"){import($argv[2]);die();}



BuildRules();

function SingleDebugEvents($subject,$text,$ID){
	$q=new mysql();
	$pid=getmypid();
	$CurrentDate=date('Y-m-d H:i:s');
	if($GLOBALS["VERBOSE"]){echo "$CurrentDate $subject\n$text\n\n";}
	
	
	$text=addslashes($text);
	$subject=addslashes($subject);
	$sql="INSERT INTO fetchmail_debug_execute (subject,account_id,zDate,events,PID) 
	VALUES('$subject','$ID','$CurrentDate','$text','$pid')";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	return;	
}


function SingleDebug($ID){
	$q=new mysql();
	$q->BuildTables();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	$fetchmail=$unix->find_program("fetchmail");
	if($unix->process_exists($pid)){
		SingleDebugEvents("Task aborted","This task is aborted, it already running PID $pid, please wait before executing a new task",$ID);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		SingleDebugEvents("Task executed","Starting rule number $ID\nThis task is executed please wait before executing a new task",$ID);
	}
	
	$fetch=new fetchmail();
	$output=array();
	
		$fetch=new fetchmail();
		$l[]="set logfile /var/log/fetchmail-rule-$ID.log";
		$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
		$l[]="set idfile \"/var/log/fetchmail.$ID.id\"";	
		$l[]="";	
	$GLOBALS["SINGLE_DEBUG"]=true;
	BuildRules();
	$pattern=$GLOBALS["FETCHMAIL_RULES_ID"][$ID];
	$l[]=$pattern;	
	@file_put_contents("/tmp/fetchmailrc.$ID",@implode("\n", $l));
	shell_exec("/bin/chmod 600 /tmp/fetchmailrc.$ID");
	$cmd="$fetchmail -v --nodetach -f /tmp/fetchmailrc.$ID --pidfile /tmp/fetcmailrc.$ID.pid 2>&1";
	
	if($GLOBALS["VERBOSE"]){
		echo $cmd."\n";
		$cmd="$fetchmail -v --nodetach -f /tmp/fetchmailrc.$ID --pidfile /tmp/fetcmailrc.$ID.pid";
		system($cmd);
		return;
	}
	exec($cmd,$output);
	SingleDebugEvents("Task finish with ". count($output)." event(s)",@implode("\n", $output),$ID);
	
}

function BuildRules_schedule(){
	
		$unix=new unix();
		$fetchmailbin=$unix->find_program("fetchmail");
		$sql="SELECT * FROM fetchmail_rules WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "Starting......: fetchmail saving configuration file FAILED\n";
			return false;
		}
		
		
		foreach (glob("/etc/cron.d/fetchmail*") as $filename) {
			echo "Starting......: fetchmail removing $filename..\n";
			@unlink($filename);
		}
		foreach (glob("/etc/fetchmail-rules/*.rc") as $filename) {
			echo "Starting......: fetchmail removing $filename..\n";
			@unlink($filename);
		}
		
		if(!is_file($fetchmailbin)){return;}
		
		echo "Starting......: fetchmail building ". mysql_num_rows($results)." rules...\n";	
		
		$fetch=new fetchmail();
		@mkdir("/etc/fetchmail-rules",0644,true);
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ID=$ligne["ID"];
			$schedule=$ligne["schedule"];
			if($schedule==null){
				echo "Starting......: fetchmail ID $ID has no schedule, set it to each 10mn `0,10,20,30,40,50 * * * *`";
				$schedule="0,10,20,30,40,50 * * * *";
			}
			$l=array();
			$l[]="set logfile /var/log/fetchmail.log";
			$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
			$l[]="set idfile \"/var/log/fetchmail.id\"";				
			$l[]=build_line($ligne);
			@file_put_contents("/etc/fetchmail-rules/$ID.rc", @implode("\n", $l)."\n");
			@chmod("/etc/fetchmail-rules/$ID.rc", 0600);
			@chown("/etc/fetchmail-rules/$ID.rc","root");
			@chgrp("/etc/fetchmail-rules/$ID.rc","root");
			$destSchedule="/etc/cron.d/fetchmail$ID";
			$t=array();
			$t[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
			$t[]="MAILTO=\"\"";
			$t[]="$schedule  root $fetchmailbin -N -f /etc/fetchmail-rules/$ID.rc --logfile /var/log/fetchmail.log --pidfile /var/run/fetchmail-$ID.pid >/dev/null 2>&1";
			$t[]="";
			
			@file_put_contents($destSchedule, @implode("\n", $t));
			
			echo "Starting......: fetchmail ID $ID done..\n";
		}
		
			@chmod("/etc/fetchmail-rules", 0600);
			@chown("/etc/fetchmail-rules","root");
			@chgrp("/etc/fetchmail-rules","root");		
	
}

function build_line($ligne){
		$sock=new sockets();
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	
			$ID=$ligne["ID"];
			writelogs("Building fetchmail rule for ID: {$ligne["ID"]} user:{$ligne["uid"]}",__FUNCTION__,__FILE__,__LINE__);
			
			$ligne["poll"]=trim($ligne["poll"]);
			if($ligne["poll"]==null){
				echo "Starting......: fetchmail rule {$ligne["ID"]} as no poll, skip it..\n";
				continue;
			}
			if($ligne["proto"]==null){$ligne["proto"]="auto";}
			if($ligne["uid"]==null){
				echo "Starting......: fetchmail rule {$ligne["ID"]} as no uid, skip it..\n";
				continue;
			}
			writelogs("Building \$user->user({$ligne["uid"]})",__FUNCTION__,__FILE__,__LINE__);
			$user=new user($ligne["uid"]);
			writelogs("Building $user->mail",__FUNCTION__,__FILE__,__LINE__);
			if(trim($user->mail)==null){
				writelogs("Building fetchmail uid has no mail !!!, skip it.. user:{$ligne["uid"]}",__FUNCTION__,__FILE__,__LINE__);
				echo "Starting......: fetchmail uid has no mail !!!, skip it..\n";
				$unix->send_email_events("Fetchmail rule for {$ligne["uid"]}/{$ligne["poll"]} has been skipped", "cannot read email address from LDAP", "mailbox");
				continue;
			}
			
			$ligne["is"]=$user->mail;
			$smtphost=null;
			$sslfingerprint=null;
			$fetchall=null;
			$timeout=null;
			$port=null;
			$aka=null;
			$folder=null;
			$tracepolls=null;
			$interval=null;
			$keep=null;
			$fetchall=null;
			$sslcertck=null;
			$limit=null;
			$dropdelivered=null;
			$smtpport=null;
			$multidrop=null;
			if($ligne["proto"]=="httpp"){$ligne["proto"]="pop3";}
			if(!isset($ligne["folder"])){$ligne["folder"]=null;}
			
			if(trim($ligne["port"])>0){$port="port {$ligne["port"]}";}
			if(trim($ligne["aka"])<>null){$aka="\n\taka {$ligne["aka"]}";}
			if($ligne["ssl"]==1){$ssl="\n\tssl\n\tsslproto ''";}	
			if($ligne["timeout"]>0){$timeout="\n\ttimeout {$ligne["timeout"]}";}
			if($ligne["folder"]<>null){$folder="\n\tfolder {$ligne["folder"]}";}				
			if($ligne["tracepolls"]==1){$tracepolls="\n\ttracepolls";}
			if($ligne["interval"]>0){$interval="\n\tinterval {$ligne["interval"]}";}		
			if($ligne["keep"]==1){$keep="\n\tkeep ";}
			if($ligne["nokeep"]==1){$keep="\n\tnokeep";}
			if($ligne["multidrop"]==1){$ligne["is"]="*";}
			if($ligne["fetchall"]==1){$fetchall="\n\tfetchall";}
			if(strlen(trim($ligne["sslfingerprint"]))>10){$sslfingerprint="\n\tsslfingerprint '{$ligne["sslfingerprint"]}'";}
			if($ligne["sslcertck"]==1){$sslcertck="\n\tsslcertck";}		
			if($GLOBALS["FetchMailGLobalDropDelivered"]==1){$ligne["dropdelivered"]=1;}
			
			if(!is_numeric($ligne["limit"])){$ligne["limit"]=2097152;}
			if($ligne["limit"]==0){$ligne["limit"]=2097152;}
			
			if(!isset($ligne["smtp_port"])){$ligne["smtp_port"]=25;}
			if(!isset($ligne["smtp_host"])){$ligne["smtp_host"]="127.0.0.1";}
			if(!is_numeric($ligne["smtp_port"])){$ligne["smtp_port"]=25;}
			if(trim($ligne["smtp_host"])==null){$ligne["smtp_host"]="127.0.0.1";}
			if($ligne["smtp_port"]<>25){
				$smtpport="/{$ligne["smtp_port"]}";
			}			
			
			$smtp="\n\tsmtphost {$ligne["smtp_host"]}$smtpport";
			$limit="\n\tlimit {$ligne["limit"]}";
						
			
			if($ligne["dropdelivered"]==1){
				$dropdelivered="\n\tdropdelivered is {$ligne["is"]} here";
			}
			$tf=array();
			$folders=unserialize(base64_decode($ligne["folders"]));
			if($GLOBALS["VERBOSE"]){echo "Folder: ". count($folders)." items\n";}
			if(is_array($folders)){
				if(count($folders)>0){
					while (list ($md, $fenc) = each ($folders) ){
						$fff=base64_decode($fenc);
						if($GLOBALS["VERBOSE"]){echo "Folder: `$fff`\n";}
						$tf[]="$fff";
					}
				}
			}
			
			if($GLOBALS["VERBOSE"]){echo "Folder: final -> ".count($folders)." items\n";}
			if(count($tf)>0){
				$folder="\n\tfolder INBOX,".@implode(",", $tf);
			}
			
			if($EnablePostfixMultiInstance==1){
				if($GLOBALS["DEBUG"]){echo "multiple instances::poll={$ligne["poll"]} smtp_host={$ligne["smtp_host"]}\n";}
				if(strlen(trim($ligne["smtp_host"]))==0){continue;}
				$smtphost="\n\tsmtphost ".multi_get_smtp_ip($ligne["smtp_host"]);
			}
			
			
			if(trim($ssl)==null){$ssl="\n\tsslproto ssl23\n\tno ssl";}
			$pattern="poll {$ligne["poll"]}$tracepolls\n\tproto {$ligne["proto"]} $port$interval$timeout\n\tuser \"{$ligne["user"]}\"\n\tpass {$ligne["pass"]}\n\tis {$ligne["is"]}$dropdelivered$aka$folder$ssl$fetchall$keep$multidrop$sslfingerprint$sslcertck$smtphost$limit$smtp\n\n";
			if($GLOBALS["DEBUG"]){echo "$pattern\n";}

			$GLOBALS["multi_smtp"][$ligne["smtp_host"]][]=$pattern;
			echo "Starting......: fetchmail poll {$ligne["poll"]} -> {$ligne["user"]} limit ". round($ligne["limit"]/1024)/1024 ." Mo\n";	
	
			return $pattern;
	
}


function BuildRules(){
		$unix=new unix();
		$sock=new sockets();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__,"fetchmail");die();}
		$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");	
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		
		
		if(!isset($GLOBALS["FetchMailGLobalDropDelivered"])){
			$sock=new sockets();
			$GLOBALS["FetchMailGLobalDropDelivered"]=$sock->GET_INFO("FetchMailGLobalDropDelivered");
			if(!is_numeric($GLOBALS["FetchMailGLobalDropDelivered"])){$GLOBALS["FetchMailGLobalDropDelivered"]=0;}
			
		}	

		@file_put_contents("/proc/sys/net/ipv4/tcp_timestamps", "0");
		if($EnableFetchmailScheduler==1){BuildRules_schedule();return;}
		
		
		foreach (glob("/etc/cron.d/fetchmail*") as $filename) {
			echo "Starting......: fetchmail removing $filename..\n";
			@unlink($filename);
		}		
		
		
		$fetch=new fetchmail();
		$l[]="set logfile /var/log/fetchmail.log";
		$l[]="set daemon $fetch->FetchmailPoolingTime";
		$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
		$l[]="set idfile \"/var/log/fetchmail.id\"";	
		$l[]="";

		$sql="SELECT * FROM fetchmail_rules WHERE enabled=1";
		$q=new mysql();
		
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "Starting......: fetchmail saving configuration file FAILED\n";
			return false;
		}
		
		echo "Starting......: fetchmail building ". mysql_num_rows($results)." rules...\n";
		
		
		$array=array();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ID=$ligne["ID"];
			$pattern=build_line($ligne);
			$l[]=$pattern;
			$GLOBALS["FETCHMAIL_RULES_ID"][$ID]=$pattern;
		}
		
		if($GLOBALS["SINGLE_DEBUG"]){
			echo "Starting......: fetchmail single-debug, aborting nex step\n";
			return;
		}
		
		if($EnablePostfixMultiInstance==1){
			echo "Starting......: fetchmail postfix multiple instances enabled (".count($GLOBALS["multi_smtp"]).") hostnames\n";
			@unlink("/etc/artica-postfix/fetchmail.schedules");
			
			if(is_array($GLOBALS["multi_smtp"])){
				if($GLOBALS["DEBUG"]){print_r($GLOBALS["multi_smtp"]);}
				while (list ($hostname, $rules) = each ($GLOBALS["multi_smtp"])){
					echo "Starting......: fetchmail $hostname save rules...\n";
					@file_put_contents("/etc/postfix-$hostname/fetchmail.rc",@implode("\n",$rules));
					@chmod("/etc/postfix-$hostname/fetchmail.rc",0600);
					$schedule[]=multi_build_schedule($hostname);
					if(!is_fetchmailset($hostname)){
						$restart=true;
					}else{
						echo "Starting......: fetchmail $hostname already scheduled...\n";
					}
				}
				if($restart){
					@file_put_contents("/etc/artica-postfix/fetchmail.schedules",@implode("\n",$schedule));
					system("/etc/init.d/artica-postfix restart fcron");
				}
			}
		return;
		}
		
		
		
		if(is_array($l)){
			$conf=implode("\n",$l);
			echo "Starting......: fetchmail building /etc/fetchmailrc ". count($l)." lines\n";
		}else{
			echo "Starting......: fetchmail building /etc/fetchmailrc 0 lines\n";
			$conf=null;}
		@file_put_contents("/etc/fetchmailrc",$conf);
		shell_exec("/bin/chmod 600 /etc/fetchmailrc");
		echo "Starting......: fetchmail saving /etc/fetchmailrc configuration file done\n";
		build_monit();
		if($GLOBALS["RELOAD"]){
			if($EnablePostfixMultiInstance==0){reload();}
		}
			
}

function reload(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$tb=explode("\n", @file_get_contents("/var/run/fetchmail.pid"));
	$isrun=false;
	while (list ($i, $pid) = each ($tb)){
		if(trim($pid)==null){continue;}
		if(!preg_match("#([0-9]+)#", $pid,$re)){continue;}
		$pid=$re[1];
		if(!$unix->process_exists($pid)){continue;}
		$isrun=true;
		echo "Starting......: fetchmail reload pid $pid\n";
		shell_exec("$kill -HUP $pid");
	}
	
	if(!$isrun){
		echo "Starting......: fetchmail is not running, start it\n";
		shell_exec("/etc/init.d/artica-postfix start fetchmail");
	}
	
	
}

function is_fetchmailset($hostname){
	
	if(!is_array($GLOBALS["crontab"])){
		exec("/usr/share/artica-postfix/bin/fcrontab -c /etc/artica-cron/artica-cron.conf  -l -u root 2>&1",$results);
		$GLOBALS["crontab"]=$results;
	}
	if($GLOBALS["DEBUG"]){echo __FUNCTION__.":: $hostname ". count($GLOBALS["crontab"])." lines\n";}
	$hostname=str_replace(".","\.",$hostname);
	while (list ($i, $line) = each ($GLOBALS["crontab"])){
		if(preg_match("#bin\/fetchmail.+?fetchmailrc\s+\/etc\/postfix-$hostname#",$line)){
			return true;
		}else{
		if($GLOBALS["DEBUG"]){echo __FUNCTION__.":: $line NO MATCH #bin\/fetchmail.+?fetchmailrc \/etc\/$hostname#\n";}
		}
		
	}
	return false;
	
}


function multi_get_smtp_ip($hostname){
	if($GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname]<>null){return $GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname];}
	$main=new maincf_multi($hostname);
	$GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname]=$main->ip_addr;
	echo "Starting......: fetchmail $hostname ($main->ip_addr)\n";
	return $main->ip_addr;
	
}

function multi_build_schedule($hostname){
	$unix=new unix();
	$fetchmail=$unix->find_program("fetchmail");
	if($fetchmail==null){return null;}	
	$main=new maincf_multi($hostname);
	$array=unserialize(base64_decode($main->GET_BIGDATA("PostfixMultiFetchMail")));	
	if($array[$hostname]["enabled"]<>1){return null;}
	if($array[$hostname]["schedule"]==null){return null;}
	if($array[$hostname]["schedule"]<2){return null;}
	echo "Starting......: fetchmail $hostname scheduling each {$array[$hostname]["schedule"]}mn\n";
	return "{$array[$hostname]["schedule"]} $fetchmail --nodetach --fetchmailrc /etc/postfix-$hostname/fetchmail.rc >>/var/log/fetchmail.log";
	
	
}
function build_monit(){
	$settings=new settings_inc();
	$sock=new sockets();
	$monit_file="/etc/monit/conf.d/fetchmail.monitrc";
	$start_file="/usr/sbin/fetchmail-monit-start";
	$stop_file="/usr/sbin/fetchmail-monit-stop";
	$processMonitName="fetchmail";
	$reloadmonit=false;
	
	$FilesToCheck[]=$monit_file;
	$FilesToCheck[]=$start_file;
	$FilesToCheck[]=$stop_file;
	
	
	if(!$settings->MONIT_INSTALLED){
		echo "Starting......: $processMonitName Monit is not installed\n";
		return;
	}
	
	$unix=new unix();
	$pidfile="/var/run/fetchmail.pid";
	$chmod=$unix->find_program("chmod");
	
	
	echo "Starting......: $processMonitName PidFile = `$pidfile`\n";
	if($pidfile==null){
		echo "Starting......: $processMonitName PidFile unable to locate\n";
		return ;
	}
	

	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("FetchMailMonitConfig")));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}	
	$EnableDaemon=$sock->GET_INFO("EnableFetchmail");
	$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");
	
	if(!is_numeric($EnableDaemon)){$EnableDaemon=0;}
	if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
	if($EnableDaemon==0){$MonitConfig["watchdog"]=0;}
	if($EnableFetchmailScheduler==1){$MonitConfig["watchdog"]=0;}
	
	if($MonitConfig["watchdog"]==0){
		echo "Starting......: $processMonitName Monit is not enabled ($q->watchdog)\n";
		
		while (list ($i, $Tofile) = each ($FilesToCheck)){
			if(is_file($Tofile)){
				@unlink($Tofile);
				$reloadmonit=true;
			}
		}
	}
	
	if($MonitConfig["watchdog"]==1){
		
		while (list ($i, $Tofile) = each ($FilesToCheck)){if(!is_file($Tofile)){$reloadmonit=true;break;}echo "Starting......: $processMonitName `$Tofile` Monit file done\n";}
		if(!$reloadmonit){	echo "Starting......: $processMonitName Monit is already set check pid `$pidfile`\n";return;}
			
		echo "Starting......: $processMonitName Monit is enabled check pid `$pidfile`\n";
		$reloadmonit=true;
		$f[]="check process $processMonitName";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"$start_file\"";
   		$f[]="stop program =  \"$stop_file\"";
   		if($MonitConfig["watchdogMEM"]){
  			$f[]="if totalmem > {$MonitConfig["watchdogMEM"]} MB for 5 cycles then alert";
   		}
   		if($MonitConfig["watchdogCPU"]>0){
   			$f[]="if cpu > {$MonitConfig["watchdogCPU"]}% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	    echo "Starting......: $processMonitName $monit_file done\n";
	   @file_put_contents($monit_file, @implode("\n", $f));
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/artica-postfix start fetchmail";
	   $f[]="exit 0\n";
 	   @file_put_contents($start_file, @implode("\n", $f));
 	   echo "Starting......: $processMonitName $start_file done\n"; 
 	   shell_exec("$chmod 777 $start_file");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/artica-postfix stop fetchmail";
	   $f[]="exit 0\n";
 	   @file_put_contents($stop_file, @implode("\n", $f));
 	   echo "Starting......: $processMonitName $stop_file done\n";
 	   shell_exec("$chmod 777 $stop_file");	   
	}
	
	if($reloadmonit){
		$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	}	
}

function import($path){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		echo "This task is aborted, it already running PID $pid, please wait before executing a new task\n";
		return;
	}
	$t=time();
	//Mailbox server;Protocol;username;password;local account;SSL Protocol;Use SSL 0/1
	$array=file($path);
	unset($f[0]);
	$c=0;
	$FD=0;
	echo "Importing ". count($array)." lines/rules form \"$path\"\n";
	while (list ($num, $ligne) = each ($array) ){
		$ligne=str_replace("\r", "", $ligne);
		$ligne=str_replace("\n", "", $ligne);
		$ligne=str_replace('"', "", $ligne);
		if(trim($ligne)==null){continue;}
		if(strpos($ligne, ";")==0){continue;}
		$POSTED_ARRAY=array();
		$tb=explode(";", $ligne);
		if(count($tb)<7){echo "Error line: $num..\n";$FD++;continue;}
		$POSTED_ARRAY["poll"]=$tb[0];
		$POSTED_ARRAY["proto"]=$tb[1];
		$POSTED_ARRAY["user"]=$tb[2];
		$POSTED_ARRAY["pass"]=$tb[3];
		$POSTED_ARRAY["uid"]=$tb[4];
		$POSTED_ARRAY["sslproto"]=$tb[5];
		$POSTED_ARRAY["ssl"]=intval($tb[6]);
		$ct=new user($POSTED_ARRAY["uid"]);
		if($ct->mail==null){echo "Error line:$num {$POSTED_ARRAY["uid"]} no such member\n";$FD++;continue;}
		$POSTED_ARRAY["is"]=$ct->mail;
		$fetchmail=new Fetchmail_settings();
		if(!$fetchmail->AddRule($POSTED_ARRAY)){echo "Error adding rule line $num\n";continue;}
		echo "Success adding rule line $num\n";	
		$c++;
		}
		
		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		echo "Import task finish took:$took $FD failed, $c success\n"; 
	
	
	
}



?>