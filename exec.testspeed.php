<?php
	$GLOBALS["SCHEDULE_ID"]=0;
	$GLOBALS["FORCE"]=false;
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc');
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	
	
	if(is_array($argv)){
		if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
		if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
		if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
		if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
		if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	}
	
	if($argv[1]=="--instances"){GetInstances();die();}
	
runProc();
	
function runProc($norestart=false){
	$unix=new unix();
	$sock=new sockets();
	$t=time();
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$timePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	
	$EnableBandwithCalculation=$sock->GET_INFO("EnableBandwithCalculation");
	if(!is_numeric($EnableBandwithCalculation)){$EnableBandwithCalculation=1;}	
	$GetInstances=GetInstances();
	if($GetInstances>0){
		system_admin_events("$GetInstances instance(s) already running.. aborting...", __FUNCTION__, __FILE__, __LINE__, "testspeed");
		return ;
	}
	
	if($EnableBandwithCalculation==0){
		system_admin_events("Feature disabled trough the Interface (EnableBandwithCalculation) you have to disable the schedule too...", __FUNCTION__, __FILE__, __LINE__, "testspeed");
		return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$oldpid=$unix->get_pid_from_file($timePID);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timexec=$unix->PROCCESS_TIME_MIN($oldpid);
			if($timexec<30){
				system_admin_events("Already instance executed pid $pid since {$timexec}Mn", __FUNCTION__, __FILE__, __LINE__, "testspeed");
				return;
			}else{
				$kill=$unix->find_program("kill");
				shell_exec("$kill -9 $oldpid");
				system_admin_events("Instance pid $pid since {$timexec}Mn was killed", __FUNCTION__, __FILE__, __LINE__, "testspeed");
			}
		}
		
		$ExecTimefile=$unix->file_time_min($timeFile);
		if($ExecTimefile<30){
			system_admin_events("Must run minimal 30mn ({$ExecTimefile}Mn), aborting", __FUNCTION__, __FILE__, __LINE__, "testspeed");
			return;
		}
		
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		@file_put_contents($timePID, getmypid());
		
	}
	
	
	$python=$unix->find_program("python");
	if(!is_file($python)){system_admin_events("python, no such binary", __FUNCTION__, __FILE__, __LINE__, "testspeed");return;}
	$speedDNum=0;
	$speedYNum=0;
	$IP=null;
	$ISP=null;
	exec("$python /usr/share/artica-postfix/bin/tespeed.py 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "$line\n";}
		if(preg_match("#IP:\s+(.+?);.*?ISP:\s+(.+?)$#", $line,$re)){
			$IP=trim($re[1]);
			$ISP=trim($re[2]);
		}
		
		if(preg_match("#No module named lxml#", $line)){
			system_admin_events("Error,$line", __FUNCTION__, __FILE__, __LINE__, "testspeed");
			if(!$norestart){install_lxml();}
			return;
		}
		if(preg_match("#Download speed:\s+([0-9\.]+)\s+MiB#", $line,$re)){
			$speedDNum=$re[1];
			$speedDNum=$speedDNum*1024;
		}
		
		if(preg_match("#Upload speed:\s+([0-9\.]+)\s+MiB#", $line,$re)){
			$speedUNum=$re[1];
			$speedYNum=$speedUNum*1024;
		}		
		
	}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	if($GLOBALS["VERBOSE"]){
		echo "ISP:$ISP , IP:$IP Download: $speedDNum Kbi/s upload $speedYNum Kbi/s\n";
	}
	system_admin_events("ISP:$ISP , IP:$IP Download: $speedDNum Kbi/s upload $speedYNum Kbi/s took $took", __FUNCTION__, __FILE__, __LINE__, "testspeed");
	$array["ISP"]=$ISP;
	$array["PUBLIC_IP"]=$IP;
	$array["DOWNLOAD"]=$speedDNum;
	$array["UPLOAD"]=$speedYNum;
	$array["DATE"]=time();
	@mkdir("/usr/share/artica-postfix/ressources/web/cache1",0777,true);
	@file_put_contents("/usr/share/artica-postfix/ressources/web/cache1/bandwith.stats", serialize($array));
	
	if(strlen($speedDNum)>2){
		$q=new mysql();
		$q->BuildTables();
		$q->QUERY_SQL("INSERT INTO speedtests (zDate,ISP,download,upload) VALUES (NOW(),'$ISP','$speedDNum','$speedYNum')","artica_events");
		if(!$q->ok){
			system_admin_events("Fatal error, $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "testspeed");
		}
	}
	
}


function install_lxml(){
	$unix=new unix();
	$run=false;
	$aptget=$unix->find_program("apt-get");
	if(is_file($aptget)){
		if($GLOBALS["VERBOSE"]){echo "Installing python-lxml\n";}
		exec("DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::=\"--force-confnew\" --force-yes -fuy install python-lxml 2>&1",$results);
		system_admin_events("python-lxml (apt): ".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "testspeed");
		$run=true;
		
	}
	$yum=$unix->find_program("yum");
	if(is_file($yum)){
		if($GLOBALS["VERBOSE"]){echo "Installing python-lxml\n";}
		exec("$yum install -y --nogpgcheck --skip-broken python-lxml 2>&1",$results);
		system_admin_events("python-lxml (yum): ".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "testspeed");
		$run=true;	
	}
	if($run){runProc(true);}
	
}

function GetInstances(){
	$unix=new unix();
	$pidsARR=array();
	$kill=$unix->find_program("kill");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"python.*?tespeed\.py\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(!preg_match("#([0-9]+)\s+(.*?)#", $line,$re)){continue;}
		$pid=$re[1];
		$cmdline=trim($re[2]);
		if(preg_match("#^sh\s+#", $cmdline)){continue;}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time>15){shell_exec("$kill -9 $pid >/dev/null 2>&1");continue;}
		$pidsARR[$pid]=true;
	}
	if($GLOBALS["VERBOSE"]){echo "-> ".count($pidsARR)." instances..\n";}
	return count($pidsARR);
	
}
