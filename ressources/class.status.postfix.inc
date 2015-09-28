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
	$array[]="milter_greylist_updates";
	$array[]="postfix_schedules";
	$array[]="policyd_weight";
	$array[]="milter_regex";
	$array[]="spamassassin_milter";
	$array[]="spamassassin";
	$array[]="roundcube";
	return $array;
}

function postfix_schedules(){
	$ASPOST=true;
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$ASPOST=false;}
	if($EnableStopPostfix==1){$ASPOST=false;}
	
	if($ASPOST){
		if(!is_file("/etc/cron.d/artica-pflogsumm-hourly")){
			Popuplate_cron_make("artica-pflogsumm-hourly","59 * * * *","exec.pflogsumm.php");
			$CRON_RELOAD=true;
		}
		
		if(!is_file("/etc/cron.d/artica-rotate-postfix")){
			Popuplate_cron_make("artica-rotate-postfix","5 0 * * *","exec.logrotate.postfix.php");
			$CRON_RELOAD=true;
		}
		
		
	}else{
		if(is_file("/etc/cron.d/artica-pflogsumm-hourly")){
			@unlink("/etc/cron.d/artica-pflogsumm-hourly");
			$CRON_RELOAD=true;
		}
	}
	
	if($CRON_RELOAD){shell_exec("/etc/init.d/cron reload");}
	
	
	
	
	
}

function iredmail_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/iredapd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("python.*?/opt/iRedAPD/iredapd");
}

function milter_greylist_updates(){
	$EnableBandwithCalculation=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB"));
	$BandwithCalculationSchedule=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB"));

	if($EnableBandwithCalculation==0){
		if(is_file("/etc/cron.d/artica-miltergreylist")){
			@unlink("/etc/cron.d/artica-miltergreylist");
			shell_exec("/etc/init.d/cron reload");
			return;
		}
	}

	$schedules[1]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *";
	$schedules[2]="0 2,4,6,8,10,12,14,16,18,20,22 * * *";
	$schedules[4]="0 4,8,12,16,20 * * *";
	$schedules[8]="0 8,16 * * *";
	$schedules[24]="0 1 * * *";
	$schedule=$schedules[$BandwithCalculationSchedule];

	
	
	$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-miltergreylist",$schedule,"exec.milter-greylist.update.php");
	shell_exec("/etc/init.d/cron reload");
}

//========================================================================================================================================================
function policyd_weight(){
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return;}
	$EnablePolicydWeight=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePolicydWeight"));
	if($EnablePolicydWeight==null){$EnablePolicydWeight=0;}

	$l[]="[POLICYD_WEIGHT]";
	$l[]="service_name=APP_POLICYD_WEIGHT";
	$l[]="service_cmd=policydw";
	$l[]="family=postfix";
	$l[]="master_version=".GetVersionOf("policydw");
	$l[]="service_disabled=$EnablePolicydWeight";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnablePolicydWeight==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path=$GLOBALS["CLASS_UNIX"]->POLICYD_WEIGHT_GET("PIDFILE");
	
	events("PID Path: $pid_path",__FUNCTION__,__LINE__);
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	events("master_pid: $master_pid",__FUNCTION__,__LINE__);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$unix=new unix();
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("policyd-weight \(");
		events("PIDOF_PATTERN: $master_pid",__FUNCTION__,__LINE__);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Not running: $master_pid",__FUNCTION__,__LINE__);
		
		if(!is_file("/etc/init.d/policyd-weight")){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --policyd-weight");
			
		}
		
		shell_exec2("/etc/init.d/policyd-weight start");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================



function _milter_greylist_enabled(){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled")){@file_put_contents("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled", 1); }
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB", 1); }
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	if(!is_file($bin)){return 0;}
	
	
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

function milter_greylist_version(){
	if(isset($GLOBALS["milter_greylist_version"])){return $GLOBALS["milter_greylist_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	exec("$bin -r 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^milter-greylist-([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["milter_greylist_version"]=$re[1];
	}
	
	return $GLOBALS["milter_greylist_version"];
	
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
	$l[]="master_version=".milter_greylist_version();
	$l[]="service_cmd=/etc/init.d/milter-greylist";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="remove_cmd=--milter-grelist-remove";
	$l[]="family=postfix";
	
	
	
	@chown("/var/run/milter-greylist/milter-greylist.sock","postfix");
	@chgrp("/var/run/milter-greylist/milter-greylist.sock", "postfix");
	@chmod("/var/run/milter-greylist/milter-greylist.sock", 0777);

	$dirname=dirname(__FILE__);
	if($EnablePostfixMultiInstance==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --startall >/dev/null 2>&1 &");
		return;
	}

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!is_file("/etc/init.d/milter-greylist")){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.initslapd.php --milter-greylist >/dev/null 2>&1 &");
	}
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --start-single >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);

	}
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".database.time";
	$TimeExec=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($TimeFile);
	if($TimeExec>5){
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --database >/dev/null 2>&1 &");
	}
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



function milter_regex_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/milter-regex.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}

function milter_regex(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "postconf no such binary.\n";}return;}
	
	$EnableMilterRegex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterRegex");
	if(!is_numeric($EnableMilterRegex)){$EnableMilterRegex=1;}

	$l[]="[milter_regex]";
	$l[]="service_name=milter_regex";
	$l[]="service_cmd=/etc/init.d/milter-regex";
	$l[]="master_version=2.0.0";
	$l[]="service_disabled=$EnableMilterRegex";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableMilterRegex==0){return implode("\n",$l);return;}
	$master_pid=milter_regex_pid();
	ToSyslog("Scanning Postfix Milter-regex watchdog PID: $master_pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(!is_file("/etc/init.d/milter-regex")){
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --milter-regex >/dev/null 2>&1");
			}
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/milter-regex start >/dev/null 2>&1 &");

		}
	}
	
	@chmod("/var/run/milter-regex/milter-regex.sock",0755);
	@chown("/var/run/milter-regex/milter-regex.sock","postfix");
	@chgrp("/var/run/milter-regex/milter-regex.sock","postfix");
	
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
	if(!is_dir("/etc/artica-postfix/pids")){ @mkdir("/etc/artica-postfix/pids",0755,true); }
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

function spamassassin_milter_version(){
	if(isset($GLOBALS["spamassassin_milter_version"])){return $GLOBALS["spamassassin_milter_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spamass-milter");
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#Version\s+([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["spamassassin_milter_version"]=$re[1];
	}
	
	return $GLOBALS["spamassassin_milter_version"];	
	
}

function spamassassin_milter(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamass-milter");
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));
	
	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon"));
		if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
	}
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$SpamAssMilterEnabled=0;}
	
	
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamass/spamass.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASS_MILTER]";
	$l[]="service_name=APP_SPAMASS_MILTER";
	$l[]="master_version=".spamassassin_milter_version();
	$l[]="service_cmd=/etc/init.d/spamass-milter";
	$l[]="service_disabled=$SpamAssMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";

	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	

	if($SpamAssMilterEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/spamass-milter stop");}
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			postfix_admin_mysql(0, "Spamassassin Milter service not running action=Start", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/spamass-milter start >/dev/null 2>&1 &");
			
		}
		
		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function spamassassin_version(){
	if(isset($GLOBALS["spamassassin_version"])){return $GLOBALS["spamassassin_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#SpamAssassin Server version\s+([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["spamassassin_version"]=$re[1];
	}

	return $GLOBALS["spamassassin_version"];

}


function spamassassin(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));

	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon"));
		if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
	}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$SpamAssMilterEnabled=0;}

	
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamd.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASSASSIN]";
	$l[]="service_name=APP_SPAMASSASSIN";
	$l[]="master_version=".spamassassin_version();
	$l[]="service_cmd=/etc/init.d/spamassassin";
	$l[]="service_disabled=$SpamAssMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	
	if($SpamAssMilterEnabled==1){
		if(!is_file("/etc/cron.d/artica-sa-update")){
			Popuplate_cron_make("artica-sa-update","15 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *","exec.spamassassin.php --sa-update-check");
			shell_exec("/etc/init.d/cron reload");
			return;
		}
	}else{
		if(is_file("/etc/cron.d/artica-sa-update")){
			@unlink("/etc/cron.d/artica-sa-update");
			shell_exec("/etc/init.d/cron reload");
		}
	}
	
	

	if($SpamAssMilterEnabled==0){$l[]="";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
		postfix_admin_mysql(0, "Spamassassin Daemon service not running action=Start", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/spamassassin start >/dev/null 2>&1 &");
		}
		$l[]="";
		return implode("\n",$l);
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function roundcube_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/roundcube-apache/apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f.*?apache-roundcube.conf");
}
function roundcube_version(){

	if(isset($GLOBALS["RC_VERSION"])){$GLOBALS["RC_VERSION"];}
	$f=explode("\n",@file_get_contents("/usr/share/roundcube/program/include/iniset.php"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(!preg_match("#RCMAIL_VERSION.*?([0-9\.]+)#", $ligne,$re)){continue;}

		$GLOBALS["RC_VERSION"]= trim($re[1]);
		return $GLOBALS["RC_VERSION"];
	}

}

function roundcube_folders(){
	if(is_file('/usr/share/roundcubemail/index.php')){return '/usr/share/roundcubemail';}
	if(is_file('/usr/share/roundcube/index.php')){return '/usr/share/roundcube';}
	if(is_file('/var/lib/roundcube/index.php')){return '/var/lib/roundcube';}
}

//========================================================================================================================================================
function roundcube(){
	
	$INSTALLED=FALSE;
	$folder=roundcube_folders();
	if(is_dir($folder)){
		if(is_file("$folder/index.php")){$INSTALLED=true;}
	}
	
	
	if(!$INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		$l[]="";
		$l[]="[ROUNDCUBE]";
		$l[]="service_name=APP_ROUNDCUBE";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}

	$users=new settings_inc();
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." postfix not installed\n";}
		$l[]="";
		$l[]="[ROUNDCUBE]";
		$l[]="service_name=APP_ROUNDCUBE";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/RoundCubeHTTPEngineEnabled")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/RoundCubeHTTPEngineEnabled", 1);
	}

	if(!is_file("/etc/artica-postfix/settings/Daemons/RoundCubeAutoCreateuser")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/RoundCubeAutoCreateuser", 1);
	}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeHTTPEngineEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/roundcube-apache/apache.pid";
	$master_pid=roundcube_pid();


	$l[]="";
	$l[]="[ROUNDCUBE]";
	$l[]="service_name=APP_ROUNDCUBE";
	$l[]="master_version=".roundcube_version();
	$l[]="service_cmd=/etc/init.d/roundcube";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="installed=1";
	$l[]="family=mailbox";
	//$l[]="remove_cmd=--samba-remove";



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo "PID {$re[1]} Not exists\n";}
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.roundcube.php --restart >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
