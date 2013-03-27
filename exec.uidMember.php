<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$oldpid=$unix->get_pid_from_file($pidfile);


if($unix->process_exists($oldpid,__FILE__)){
		echo "Already PID running $oldpid (".basename(__FILE__).")\n";
		die();
	}		
	
	$time=$unix->file_time_min($timefile);
	if($timefile<5){die();}
	
	@mkdir(dirname($pidfile),0755,true);
	@file_put_contents($pidfile, getmypid());
	@unlink($timefile);
	@file_put_contents($timefile, time());


if(system_is_overloaded(basename(__FILE__))){
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
	die();
}


$ERROR=array();
$ldap=new clladp();
$r =@ldap_search($ldap->ldap_connection, $ldap->suffix, '(uidnumber=*)',array("uidnumber","uid"));
if (!$r){die();}

	ldap_sort($ldap->ldap_connection, $r, "uidNumber");
	$result = ldap_get_entries($ldap->ldap_connection, $r);
	$count = $result['count'];
	if($GLOBALS["VERBOSE"]){echo "LastUidNumber:$count items\n";}
	for($i=0;$i<$count;$i++){
		$id=$result[$i]['uidnumber'][0];
		if($id<1000){continue;}
		$uid=$result[$i]['uid'][0];
		if($GLOBALS["VERBOSE"]){echo "[$id] = $uid\n";}
		if(isset($ALR[$id])){
			echo "Duplicate entry found for $uid = $id\n";
			$ERROR[$uid]=true;
			continue;
		}
		
		$ALR[$id]=true;
		
	



if(count($ERROR)>0){
	if($GLOBALS["VERBOSE"]){echo count($ERROR)." duplicates found...\n";}
	while (list ($uid, $ligne) = each ($ERROR) ){
		$LastUidNumber=LastUidNumber();
		if($GLOBALS["VERBOSE"]){echo "fix uid $uid with uidNumber=$LastUidNumber\n";}
			$user=new user($uid);
			$user->uidNumber=$LastUidNumber;
			$user->edit_system();			
		
		}
	}
	
	
}

function LastUidNumber(){
	$ldap=new clladp();
	$r =@ldap_search($ldap->ldap_connection, $ldap->suffix, '(uidnumber=*)',array("uidnumber","uid"));
	if (!$r){die();}
	ldap_sort($ldap->ldap_connection, $r, "uidNumber");
	$result = ldap_get_entries($ldap->ldap_connection, $r);
	$count = $result['count'];
	if($GLOBALS["VERBOSE"]){echo "LastUidNumber:$count items\n";}
	for($i=0;$i<$count;$i++){
		$id=$result[$i]['uidnumber'][0];
		if($id<2000){continue;}
		$hash[$id]=true;
	}

	
	if(count($hash)==0){return 2001;}
	krsort($hash);
	$f=array();
	while (list ($num, $ligne) = each ($hash) ){$f[]=$num;}
	$final=$f[0];
	return $final+1;
}