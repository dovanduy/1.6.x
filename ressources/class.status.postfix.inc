<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}

function postfix_increment_func($array){
	
	$array[]="iredmail";
	$array[]="mailarchiver";
	$array[]="postfix_logger";
	return $array;
}

function iredmail_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/iredapd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("python.*?/opt/iRedAPD/iredapd");
}



function iredmail(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('postconf');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "postconf no such binary.\n";}return;}
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$EnableiredMail=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableiredMail");
	if(!is_numeric($EnableiredMail)){$EnableiredMail=1;}
	
	$l[]="[APP_IREDMAIL]";
	$l[]="service_name=APP_IREDMAIL";
	$l[]="service_cmd=/etc/init.d/iredmail";
	$l[]="master_version=1.4.2";
	$l[]="service_disabled=$EnableiredMail";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableiredMail==0){return implode("\n",$l);return;}	
	$master_pid=iredmail_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(!is_file("/etc/init.d/iredmail")){
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --iredmail >/dev/null 2>&1");
			}
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/iredmail start >/dev/null 2>&1 &");
		
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
	
}

function mailarchiver(){
	$MailArchiverEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('MailArchiverEnabled');
	$MailArchiverUsePerl=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MailArchiverUsePerl");
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
	if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=0;}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: MailArchiverEnabled..: $MailArchiverEnabled\n";}
	if($MailArchiverUsePerl==0){
		$pid_path="/var/run/maildump/maildump.pid";
		if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
		$master_pid=trim(@file_get_contents($pid_path));
		if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}
	}else{
		$master_pid=mailarchive_pid();
	}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$MailArchiverEnabled=0;}

	$l[]="[APP_MAILARCHIVER]";
	$l[]="service_name=APP_MAILARCHIVER";
	$l[]="master_version=1.0.20090200";
	$l[]="service_cmd=mailarchiver";
	$l[]="service_disabled=$MailArchiverEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	if($MailArchiverEnabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --mailarchive-perl >/dev/null 2>&1");
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/mailarchive-perl restart >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	$unix=new unix();
	$pid=$unix->get_pid_from_file("/etc/artica-postfix/exec.mailarchiver.php.pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		$CountDefiles=$unix->DIR_COUNT_OF_FILES("/var/spool/mail-rtt-backup");
		if($CountDefiles>0){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mailarchiver.php >/dev/null 2>&1 &");
		}
	}
	@mkdir("/etc/artica-postfix/pids",0755,true);
	$PurgeTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".purge.time";
	$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($PurgeTime);
	if($time>480){
		@unlink($PurgeTime);
		@file_put_contents($PurgeTime, time());
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mailarchiver.php --purge >/dev/null 2>&1 &");
	}
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function postfix_logger(){
	if(!is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){return;}
	$ActAsSMTPGatewayStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsSMTPGatewayStatistics");
	if(!is_numeric($ActAsSMTPGatewayStatistics)){$ActAsSMTPGatewayStatistics=0;}
	if($ActAsSMTPGatewayStatistics==0){
		$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
		if($bin_path==null){return null;}
	}
	$pid_path="/etc/artica-postfix/exec.maillog.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[ARTICA_MYSQMAIL]";
	$l[]="service_name=APP_ARTICA_MYSQMAIL";
	$l[]="master_version=".trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	$l[]="service_cmd=/etc/init.d/postfix-logger";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	$l[]="installed=1";

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.service.postfix-logger.php --start >/dev/null 2>&1 &");
			$l[]="running=0\ninstalled=1";$l[]="";
			return implode("\n",$l);
		}
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$time=file_time_min("/var/log/artica-postfix/postfix-logger.debug");

		if($time>45){
			writelogs("LOG TIME: $time -> restart postfix-logger",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/postfix-logger restart");
		}
	}

	return implode("\n",$l);return;

}
//========================================================================================================================================================

