<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if($argv[1]=="--upgrade-7"){upgradeTo7();exit;}
if($argv[1]=="--mailboxes-ou-lang"){mailboxes_ou_lang($argv[2]);exit;}

sync($argv[1]);

die();


function mailboxes_ou_lang($ou){
	$unix=new unix();
	$sock=new sockets();
	$t=time();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$unix=new unix();
	$me=basename(__FILE__);
	if($unix->process_exists(@file_get_contents($pidfile),$me)){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
		system_admin_events("--> Already executed.. ". @file_get_contents($pidfile). " aborting the process", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		die();
	}

	@file_put_contents($pidfile, getmypid());
	$oumd5=md5(strtolower(trim($ou)));
	$OuDefaultLang=$sock->GET_INFO("zarafaMBXLang$oumd5");
	if($OuDefaultLang==null){system_admin_events("`$ou` no such default language, aborting", __FUNCTION__, __FILE__, __LINE__, "zarafa");return;}
	
	$ldap=new clladp();
	$members=$ldap->hash_users_ou($ou);
	$CountMembers=count($members);
	system_admin_events("$ou $CountMembers to change to $OuDefaultLang", __FUNCTION__, __FILE__, __LINE__, "zarafa");
	$c=0;
	while (list ($uid, $name) = each ($members) ){
		$ct=new user($uid);
		if($ct->zarafaMbxLang==null){
			$ct->SaveZarafaMbxLang($OuDefaultLang);
			$c++;
			$sock->getFrameWork("cmd.php?zarafa-admin=yes");
			$sock->getFrameWork("zarafa.php?zarafa-user-create-store=$uid&lang=$OuDefaultLang");	
			$sock->getFrameWork("zarafa.php?foldersnames=yes&uid=$uid&lang=$OuDefaultLang");
		}
		
		
	}
	
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("$ou $c/$CountMembers changed to $OuDefaultLang done took: $took", __FUNCTION__, __FILE__, __LINE__, "zarafa");
}


function sync($ou){
	
if(!Build_pid_func(__FILE__,__FUNCTION__)){
	writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
	return;
}
$unix=new unix();	
$imapsync=$unix->find_program("imapsync");


	if(!is_file($unix->find_program("imapsync"))){
		writelogs("Unable to stat imapsync",__FUNCTION__,__FILE__,__LINE__);
		send_email_events("Could not migrate from cyrus to zarafa","Unable to stat imapsync tool,aborting","mailbox");
		return;
	}
	
	$ou=base64_decode($ou);
	
	
	$ldap=new clladp();
	$members=$ldap->hash_users_ou($ou);
	writelogs("Loading $ou organization ".count($members)." members imapsync=$imapsync",__FUNCTION__,__FILE__,__LINE__);
	send_email_events("migration from cyrus to zarafa starting","Cyrus to zarafa starting (". count($members)." members)","mailbox");
	

	while (list ($uid, $name) = each ($members) ){
		if($uid==null){continue;}
		$user=new user($uid);
		send_email_events("migration from cyrus to zarafa starting","Cyrus to zarafa starting (". count($members)." members)","mailbox");
		$cmdline="$imapsync  --noauthmd5  --subscribe --host1 127.0.0.1 --port1 1143";
		$cmdline=$cmdline." --user1 $uid --password1 $user->password --delete --expunge1";
		$cmdline=$cmdline." --sep2 / --prefix2 \"\" --host2 127.0.0.1 --user2 $uid --password2 $user->password >/root/imapsync.$uid 2>&1";
		writelogs("$cmdline",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmdline);
		$datas=@file_get_contents("/root/imapsync.$uid");
		if($GLOBALS["VERBOSE"]){
			echo "$datas";
		}
		if(strlen($datas)>0){
			send_email_events("$uid migration status",@file_get_contents("/root/imapsync.$uid"),"mailbox");
		}
		@unlink("/root/imapsync.$uid");
		
		
	}
	
	
	
	
	
	
}


function update_pid($pid){
	$q=new mysql();
	$date=date('Y-m-d H:i:s');
	$sql="UPDATE imapsync SET pid='$pid',zDate='$date' WHERE ID={$GLOBALS["unique_id"]}";
	$q->QUERY_SQL($sql,"artica_backup");
}
function update_status($int,$text){
	$q=new mysql();
	$date=date('Y-m-d H:i:s');
	$sql="UPDATE imapsync SET state='$int',state_event='$text',zDate='$date' WHERE ID={$GLOBALS["unique_id"]}";
	$q->QUERY_SQL($sql,"artica_backup");
}


function cron(){
	$unix=new unix();
	$files=$unix->DirFiles("/etc/cron.d");
	$php5=$unix->LOCATE_PHP5_BIN();
	$sql="SELECT CronSchedule,ID FROM imapsync";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return null;}
	
	
	while (list ($index, $line) = each ($files) ){
		if($index==null){continue;}
		if(preg_match("#^imapsync-#",$index)){
			@unlink("/etc/cron.d/$index");
		}
	}
	
	$sql="SELECT CronSchedule,ID FROM imapsync";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
 	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
 		if(trim($ligne["CronSchedule"]==null)){continue;}
 		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["CronSchedule"]}  root $php5 ".__FILE__." --sync {$ligne["ID"]}";
		$f[]="";
		@file_put_contents("/etc/cron.d/imapsync-{$ligne["ID"]}",implode("\n",$f));
		@chmod("/etc/cron.d/imapsync-{$ligne["ID"]}",600);
		unset($f);
 	}
	
}

function upgradeTo7(){
	return;
	if(is_file("/etc/artica-postfix/NO_ZARAFA_UPGRADE_TO_7")){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}	
	@file_put_contents($pidfile, getmypid());
	$python=$unix->find_program("python");
	$cmd="$python /usr/share/artica-postfix/bin/zarafa7-upgrade 2>&1";
	exec($cmd,$results);
	writelogs("$cmd -> " . count($results)."rows",__FUNCTION__,__FILE__,__LINE__);
	while (list ($index, $line) = each ($results) ){writelogs("$line",__FUNCTION__,__FILE__,__LINE__);}
	$unix->send_email_events("Zarafa upgraded to 7 (see details)", $cmd."\n".@implode("\n", $results), "mailbox");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-postfix restart zarafa >/dev/null 2>&1 &");
}





?>