<?php

class processes_php{
	
	
	function processes_php(){
		if(!isset($GLOBALS["CLASS_UNIX"])){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$GLOBALS["CLASS_UNIX"]=new unix();
			}
		
		if(!isset($GLOBALS["PHP5"])){$GLOBALS["PHP5"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();}
		if(!isset($GLOBALS["NICE"])){$GLOBALS["NICE"]=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();}
		if(!isset($GLOBALS["nohup"])){$GLOBALS["nohup"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");}
		if(!isset($GLOBALS["CHMOD"])){$GLOBALS["CHMOD"]=$GLOBALS["CLASS_UNIX"]->find_program("chmod");}
		if(!isset($GLOBALS["CHOWN"])){$GLOBALS["CHOWN"]=$GLOBALS["CLASS_UNIX"]->find_program("chown");}
		if(!isset($GLOBALS["KILLBIN"])){$GLOBALS["KILLBIN"]=$GLOBALS["CLASS_UNIX"]->find_program("kill");}
		if(!isset($GLOBALS["PGREP"])){$GLOBALS["PGREP"]=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");}
		if(!isset($GLOBALS["TOTAL_MEMORY_MB"])){$GLOBALS["TOTAL_MEMORY_MB"]=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();}
		
		
		$GLOBALS["EXEC_NICE"]=$GLOBALS["NICE"];
		$GLOBALS["NOHUP"]=$GLOBALS["nohup"];
		$GLOBALS["PGREP"]=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		
		
	}
	
	
function MemoryInstances(){
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();}
		if(isset($GLOBALS["CLASS_UNIX/MemoryInstances"][date("Hi")])){return $GLOBALS["CLASS_UNIX/MemoryInstances"][date("Hi")];}
		
		$pgrep=$GLOBALS["PGREP"];
		if(!is_file($pgrep)){if($GLOBALS["VERBOSE"]){echo "pgrep, no such bin\n";}return 0;}
		$ArticaProxyStatisticsMaxTime=$GLOBALS["CLASS_SOCKET"]->GET_INFO("ArticaProxyStatisticsMaxTime");
		if(!is_numeric($ArticaProxyStatisticsMaxTime)){$ArticaProxyStatisticsMaxTime=420;}
		if($ArticaProxyStatisticsMaxTime<5){$ArticaProxyStatisticsMaxTime=420;}
		
		
		$Toremove["exec.squid.blacklists.php"]=true;
		
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
		$Toremove["exec.smtp-senderadv.php"]=true;	
		$Toremove["external_acl_squid.php"]=true;
		$Toremove["exec.offlineimap.php"]=true;
		$Toremove["exec.mailbox.migration.php"]=true;
		$Toremove["exec.logfile_daemon.php"]=true;
		$Toremove["exec.pdns.pipe.php"]=true;
		
		$Toremove["exec.syslog.php"]=true;
		$Toremove["exec.maillog.php"]=true;
		$Toremove["exec.status.php"]=true;
		$Toremove["exec.executor.php"]=true;
		$Toremove["exec.ufdbguard-tail.php"]=true;
		$Toremove["exec.squid-tail.php"]=true;
		$Toremove["exec.fetmaillog.php"]=true;
		$Toremove["exec.dansguardian-tail.php"]=true;
		$Toremove["exec.cache-logs.php"]=true;
		$Toremove["exec.auth-tail.php"]=true;
		$Toremove["exec.artica-filter-daemon.php"]=true;
		$Toremove["exec.postfix-logger.php"]=true;
		$Toremove["exec.squid2.logger.php"]=true;
		$Toremove["exec.openvpn.php"]=true;
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
		$Toremove["exec.pdns.pipe.php"]=true;

		$arrayK["exec.freeweb.php"]=15;
		$arrayK["exec.squid.stats.hours.php"]=60;
		$arrayK["exec.squid.php"]=10;
		$arrayK["exec.squid.watchdog.php"]=10;
		$arrayK["exec.squid-db.php"]=5;
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
		$arrayK["exec.syslog-engine.php"]=10;
		$arrayK["exec.cache.pages.php"]=10;
		$arrayK["exec.admin.status.postfix.flow.php"]=10;
		$arrayK["exec.dansguardian.injector.php"]=15;
		$arrayK["exec.squid-tail-injector.php"]=20;
		$arrayK["exec.schedules.php"]=5;
		$arrayK["exec.jgrowl.php"]=5;
		$arrayK["compile-apache-mod.php"]=10;
		$arrayK["exec.squid.hourly.tables.php"]=50;
		$arrayK["exec.zarafa.build.stores.php"]=30;
		$arrayK["exec.zarafa.adbookldap.php"]=30;
		$arrayK["exec.squid.cloud.compile.php"]=1440;
		$arrayK["exec.cleancloudcatz.php"]=1440;
		$arrayK["exec.kerbauth.php"]=15;
		$arrayK["exec.admin.smtp.flow.status.php"]=10;
		$arrayK["exec.squid.stats.central.php"]=$ArticaProxyStatisticsMaxTime;
		$arrayK["exec.squid.stats.hours.php"]=59;
		$arrayK["exec.initslapd.php"]=10;
		$arrayK["exec.c-icap.php"]=10;
		$arrayK["exec.containers.php"]=5;
		$arrayK["exec.openvpn.php"]=10;
			
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
				$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
				

				if(isset($arrayK[$filename])){
					if($time>$arrayK[$filename]){
					if($filename=="exec.squid.stats.central.php"){
						stats_admin_events(1,"Statistics overtime {$time}Mn/{$arrayK[$filename]}Mn, killing process",null,__FILE__,__LINE__);
					}
					$this->ToSyslog("MemoryInstances(): killing $filename it freeze {$time}Mn/{$arrayK[$filename]}Mn",__FUNCTION__,__LINE__);
					
					unix_system_kill_force($re[1]);
					
					continue;						
					}
				}
				
			 $GLOBALS["INSTANCES_EXECUTED"][]="\"$filename\" {$time}Mn";	
			 if($time>0){$this->ToSyslog("MemoryInstances(): PROCESS IN MEMORY: \"$filename\"[PID:{$re[1]}] `$ligne` {$time}Mn ",__FUNCTION__,__LINE__);}
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
					$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
					if($time>10){
						$this->events("killing yorel-upd it {$re[1]} freeze {$time}Mn...",__FUNCTION__,__LINE__);
						unix_system_kill_force($re[1]);
					}
				}
			}
			$GLOBALS["YORELOK"]=true;
		}
		
		
		unset($GLOBALS["CLASS_UNIX/MemoryInstances"]);
		$GLOBALS["CLASS_UNIX/MemoryInstances"][date("Hi")]=$count;
		return $count;
		
		
	}
	
	private function ToSyslog($text){
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		if(!function_exists("syslog")){return;}
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	}
	
	private function events($text,$function=null,$line=null){
		if($function==null){$function=__FUNCTION__;}
		if(!is_numeric($line)){$line=0;}
		$this->ToSyslog("$function(): [$line]: $text");
		
	}
	
	public function IfQueue(){
		$result=false;
		if(is_file("/usr/share/artica-postfix/exec.parse-orders.php")){@unlink("/usr/share/artica-postfix/exec.parse-orders.php");}
		if(is_file("/etc/artica-postfix/orders.queue")){return true;}
		if(is_file("/etc/artica-postfix/background")){return true;}
	}
	
	
	public function ParseLocalQueue(){
		if(system_is_overloaded()){$this->ToSyslog("[OVERLOAD]:: running in max overload mode, aborting queue");return;}
		if(!$this->IfQueue()){return;}
	
		$MemoryInstances=$this->MemoryInstances();
		if(!is_numeric($MemoryInstances)){$MemoryInstances=0;}
		if($MemoryInstances>4){$this->ToSyslog("Too much php processes in memory ( $MemoryInstances ), aborting");return;}
		
	
		if(is_file("/etc/artica-postfix/orders.queue")){
			$size=@filesize("/etc/artica-postfix/orders.queue");
			if($size>0){
				$orders_queue=explode("\n",@file_get_contents("/etc/artica-postfix/orders.queue"));
				if(is_array($orders_queue)){
					while (list ($num, $ligne) = each ($orders_queue) ){
						if(trim($ligne)==null){continue;}
						$orders[md5($ligne)]=$ligne;
					}
				}
			}
			@unlink("/etc/artica-postfix/orders.queue");
		}
	
	
		if(is_file("/etc/artica-postfix/background")){
			$size=@filesize("/etc/artica-postfix/background");
			if($size>0){
				$background=explode("\n",@file_get_contents("/etc/artica-postfix/background"));
				if(is_array($background)){
					while (list ($num, $ligne) = each ($background) ){
						if(trim($ligne)==null){continue;}
						$orders[md5($ligne)]=$ligne;
					}
				}
			}
			@unlink("/etc/artica-postfix/background");
	
		}



	
	
		if(count($orders)==0){return null;}
	
		$nice=$GLOBALS["EXEC_NICE"];
		$nohup=$GLOBALS["NOHUP"];		
		$orders_number=count($orders);
		$count_max=$orders_number;
		if($count_max>4){$count_max=4;}
		if($orders_number>10){if(!$GLOBALS["OVERLOAD"]){$count_max=10;}}
		$count=0;
	
	
	
		if($count_max+$MemoryInstances>10){$count_max=10-$MemoryInstances;}
	
		if($GLOBALS["TOTAL_MEMORY_MB"]<400){$count_max=2;}
	
		while (list ($num, $cmd) = each ($orders) ){
			if(trim($cmd)==null){continue;}
	
			$devnull=" >/dev/null 2>&1";
			if(strpos($cmd,">")>0){$devnull=null;}
	
			if(preg_match("#artica-make#", $cmd)){
				$this->ToSyslog("artica-make detected \"$cmd\", execute this task first...");
				shell_exec("$nice$cmd$devnull");
				unset($orders[$num]);
			}
		}
		
		reset($orders);
		$this->ToSyslog("Orders:$orders_number Loaded instances:$MemoryInstances Max to order:$count_max");
		
		
	
		while (list ($num, $cmd) = each ($orders) ){
			
			if(trim($cmd)==null){continue;}
	
			$devnull=" >/dev/null 2>&1";
			if(strpos($cmd,">")>0){$devnull=null;}
	
			if(system_is_overloaded(__FILE__)){
				$this->ToSyslog("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, Memory free:{$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB return....");
				$this->flush_queue($orders);
				break;
			}
			
			
			$count++;
			$this->ToSyslog("L:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]} M:{$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB $nohup $nice$cmd$devnull &");
			
			if(preg_match("#--schedule-id=([0-9]+)#", $cmd,$re)){
				system_admin_events("Running Task {$re[1]} `$cmd`" , 
				__FUNCTION__, __FILE__, __LINE__, "tasks",$re[1]);
			}
			
			shell_exec(trim("$nohup $nice$cmd$devnull &"));
			unset($orders[$num]);
			if($count>=$count_max){break;}
		}
	
	
		$this->ToSyslog("$count/$orders_number order(s) executed...end;");
		$this->flush_queue($orders);
	}	
	
	private function flush_queue($orders){
		$order_file="/etc/artica-postfix/background";
		if(!is_array($orders)){@unlink($order_file);return;}
		if(count($orders)==0){@unlink($order_file);return;}
		reset($orders);
		$fh = fopen("/etc/artica-postfix/background", 'w') or die("can't open file");
		while (list ($num, $cmd) = each ($orders) ){
			$datas="$cmd\n";
			fwrite($fh, $datas);
		}
		fclose($fh);
		$this->ToSyslog("Queued ". count($orders)." order(s)");
	}
	
}