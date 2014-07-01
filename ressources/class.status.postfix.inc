<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}




function postfix_increment_func($array){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){ if(function_exists("LoadIncludes")){LoadIncludes();}else{return $array;} }
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$EnableStopPostfix=1;$EnablePostfixMultiInstance=0;}
	if($DisableMessaging==1){return $array;}
	if($EnableStopPostfix==1){return $array;}
	
	$array[]="iredmail";
	$array[]="mailarchiver";
	$array[]="postfix_logger";
	$array[]="opendkim";
	$array[]="milter_greylist";
	$array[]="postfix_schedules";
	return $array;
}

function postfix_schedules(){
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){return;}
	if($EnableStopPostfix==1){return;}
	
	$BASEDIR="/usr/share/artica-postfix";
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.postfix-stats.php.STATS_hourly_cnx_to_daily_cnx.time");
	if($time_file>59){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.postfix-stats.php --hourly-cnx >/dev/null 2>&1 &");
	}
	
	if(_milter_greylist_enabled()==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.postfix.miltergrey.stats.php.MiltergreyList_days.time");
		if($time_file>59){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.postfix.miltergrey.stats.php >/dev/null 2>&1 &");
		}
	}
			
	
	
}

function iredmail_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/iredapd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("python.*?/opt/iRedAPD/iredapd");
}

function _milter_greylist_enabled(){
	if(!$GLOBALS["CLASS_USERS"]->MILTERGREYLIST_INSTALLED){return 0;}
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled");
	$EnableASSP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableASSP');
	if($enabled==null){$enabled=0;}
	if($EnablePostfixMultiInstance==1){$enabled=0;}
	if($EnableASSP==1){$enabled=0;}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnablePostfixMultiInstance: $EnablePostfixMultiInstance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnableASSP: $EnableASSP\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: enabled: $enabled\n";}
	
	return $enabled;
}

function milter_greylist(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($DisableMessaging==1){return;}
	if($EnableStopPostfix==1){return;}
	$enabled=_milter_greylist_enabled();
	$pid_path="/var/run/milter-greylist/milter-greylist.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path: $pid_path\n";}

	if($pid_path==null){
		$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_MILTER_GREYLIST_PID();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= pid path: $pid_path\n";}
	}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= master pid: $master_pid\n";}
	$l[]="[MILTER_GREYLIST]";
	$l[]="service_name=APP_MILTERGREYLIST";
	$l[]="master_version=".GetVersionOf("milter-greylist");
	$l[]="service_cmd=/etc/init.d/milter-greylist";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="remove_cmd=--milter-grelist-remove";
	$l[]="family=postfix";


	$dirname=dirname(__FILE__);
	if($EnablePostfixMultiInstance==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --startall >/dev/null 2>&1 &");
		return;
	}

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --start-single >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);

	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

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
	ToSyslog("Scanning Postfix iRedMail watchdog PID: $master_pid");
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
	ToSyslog("Scanning Postfix MailArchiver watchdog PID: $master_pid");
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
	ToSyslog("Scanning Postfix mail.log watchdog PID: $master_pid");

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
		ToSyslog("Fatal, Postfix-logger did not running, start it...");
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
			ToSyslog("Postfix-logger -> restart it");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/postfix-logger restart");
			return implode("\n",$l);return;
		}
	}
	ToSyslog("Postfix-logger -> check done");
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function opendkim(){
	$debugFunc=false;
	if($debugFunc){ToSyslog("opendkim() -> EXECUTED");}
	if(!$GLOBALS["CLASS_USERS"]->OPENDKIM_INSTALLED){
		if($debugFunc){ToSyslog("opendkim() -> Not installed...`OPENDKIM_INSTALLED`");}
		
		return;}
	$EnableDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter");
	if(!is_numeric($EnableDKFilter)){$EnableDKFilter=0;}
	$DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}


	$pid_path="/var/run/opendkim/opendkim.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableDKFilter=0;}

	$l[]="[APP_OPENDKIM]";
	$l[]="service_name=APP_OPENDKIM";
	$l[]="master_version=".opendkim_version();
	$l[]="service_cmd=/etc/init.d/opendkim";
	$l[]="service_disabled=$EnableDKFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	if($debugFunc){ToSyslog("opendkim() -> EnableDKFilter..... = $EnableDKFilter");}
	if($debugFunc){ToSyslog("opendkim() -> DisconnectDKFilter  = $DisconnectDKFilter");}

	if($EnableDKFilter==0){$l[]="";
	if($debugFunc){ToSyslog("opendkim() -> EnableDKFilter  = $EnableDKFilter -> ABORT");}
		return implode("\n",$l);
		
	}

	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> ?");}
	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> Not running");}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($DisconnectDKFilter==0){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				if(!is_file("/etc/init.d/opendkim")){
					shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --opendkim >/dev/null 2>&1 &");
				}
				ToSyslog("opendkim() -> master_pid  = $master_pid -> Not running -> /etc/init.d/opendkim start");
				shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim start >/dev/null 2>&1 &");
					
			}
		}
		$l[]="";
		return implode("\n",$l);
		
	}
	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> running OK");}
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/opendkim/opendkim.sock")){
		ToSyslog("Fatal, opendkim: /var/run/opendkim/opendkim.sock not available -> restart");
		shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
	}else{
		if($debugFunc){ToSyslog("opendkim() -> /var/run/opendkim/opendkim.sock OK");}
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	ToSyslog("opendkim() -> check done");
	return implode("\n",$l);return;

}
//========================================================================================================================================================

