<?php

class processes_php{
	
	
function MemoryInstances(){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		if(!is_file($pgrep)){
			if($GLOBALS["VERBOSE"]){echo "pgrep, no such bin\n";}
			return 0;}
		
		
		$Toremove["exec.squid.blacklists.php"]=true;
		$Toremove["exec.parse-orders.php"]=true;
		$Toremove["exec.syslog.php"]=true;
		$Toremove["exec.maillog.php"]=true;
		$Toremove["exec.status.php"]=true;
		$Toremove["exec.executor.php"]=true;
		$Toremove["exec.ufdbguard-tail.php"]=true;
		$Toremove["exec.squid-tail.php"]=true;
		$Toremove["exec.fetmaillog.php"]=true;
		$Toremove["exec.dansguardian-tail.php"]=true;	
		$Toremove["exec.auth-tail.php"]=true;
		$Toremove["exec.logfile_daemon.php"]=true;
		$Toremove["exec.cache-logs.php"]=true;
		$Toremove["exec.artica-filter-daemon.php"]=true;	
		$Toremove["exec.postfix-logger.php"]=true;
		$Toremove["exec.squid2.logger.php"]=true;
		$Toremove["exec.openvpn.php"]=true;	
		$Toremove["exec.schedules.php"]=true;	
		$Toremove["exec.smtp-senderadv.php"]=true;	
		$Toremove["external_acl_squid.php"]=true;
		$Toremove["exec.offlineimap.php"]=true;
		$Toremove["exec.mailbox.migration.php"]=true;
		$Toremove["exec.logfile_daemon.php"]=true;
		$Toremove["exec.pdns.pipe.php"]=true;
		
		
		$arrayK["exec.hamachi.php"]=5;
		$arrayK["exec.squid-tail-injector.php"]=320;
		$arrayK["exec.virtuals-ip.php"]=10;
		$arrayK["exec.watchdog.php"]=3;
		$arrayK["exec.test-connection.php"]=5;
		$arrayK["exec.artica.meta.php"]=20;
		$arrayK["exec.clean.logs.php"]=60;
		$arrayK["exec.squid.stats.php"]=380;
		$arrayK["exec.mysql.build.php"]=30;
		$arrayK["exec.smtp-hack.export.php"]=10;
		$arrayK["exec.postfix-logger.php"]=10;	
		$arrayK["exec.openvpn.php"]=5;
		$arrayK["exec.setup-center.php"]=30;
		$arrayK["exec.ipband.php"]=60;					
		$arrayK["exec.testspeed.php"]=15;
		$arrayK["exec.syslog-engine.php"]=30;
		$arrayK["exec.my-rbl.check.php"]=15;
		$arrayK["exec.logrotate.php"]=240;
			
		$array=array();
		$cmd="$pgrep -l -f \"artica-postfix/exec\..*?\.php\" 2>&1";
		
		exec("$cmd",$results);
		if($GLOBALS["VERBOSE"]){echo "$cmd -> ". count($results)." items\n";}
		$GLOBALS["INSTANCES_EXECUTED"]=array();
		$ALRED=array();
		while (list ($num, $ligne) = each ($results) ){
			
			if(preg_match("#bin\/openvpn#",$ligne)){continue;}
			if(strpos($ligne,"sh -c")>0){continue;}
			
			if(preg_match("#^([0-9]+)\s+.+?\s+\/usr\/share\/artica-postfix\/(.+?)\.php.*?$#",$ligne,$re)){
				
				$filename=trim($re[2]).".php";
				if($GLOBALS["VERBOSE"]){echo "[{$re[1]}]: `$filename`\n";}
				if(isset($Toremove[$filename])){continue;}
				if(!is_numeric($re[1])){continue;}
				if(isset($ALRED[$re[1]])){continue;}
				
				$ALRED[$re[1]]=true;
				if(!$GLOBALS["CLASS_UNIX"]->process_exists($re[1])){continue;}
				if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
				$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
				

				if(isset($arrayK[$filename])){
					if($time>$arrayK[$filename]){
					$this->events("killing $filename it freeze...",__FUNCTION__,__LINE__);
					shell_exec("/bin/kill -9 {$re[1]}");
					continue;						
						
					}
					
				}
				
			 $GLOBALS["INSTANCES_EXECUTED"][]="\"$filename\" {$time}Mn";	
			 if($time>0){$this->events("PROCESS IN MEMORY: [PID:{$re[1]}] \"$filename\" `$ligne` {$time}Mn");}
			 $array[]="[{$re[1]}] $filename ({$time}Mn)";
			}
		}
		
		
		$count=count($array);
		if(count($array)>0){
			$this->events("$count processe(s) In memory: ".@implode(" ", $array),__FUNCTION__,__LINE__);
		}
		$mem=round(((memory_get_usage()/1024)/1000),2);
		
		
		
		//yorel
		if(!isset($GLOBALS["YORELOK"])){
			exec("$pgrep -l -f \"perl.+?yorel-upd\" 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){
				if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
					if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
					$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
					if($time>10){
						//$GLOBALS["CLASS_UNIX"]->send_email_events("[artica-background] yorel-upd is killed after {$time}Mn live");
						$this->events("killing yorel-upd it {$re[1]} freeze {$time}Mn...",__FUNCTION__,__LINE__);
						shell_exec("/bin/kill -9 {$re[1]}");
					}
				}
			}
			$GLOBALS["YORELOK"]=true;
		}
		
		
		
		
		return $count;
		
		
	}
	
	function events($text,$function=null,$line=null){
		if($function==null){$function=__FUNCTION__;}
		if(!is_numeric($line)){$line=0;}
		writelogs($text , $function, __FILE__, $line);
		
	}
	
}