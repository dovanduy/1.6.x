<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv),$re)){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.categorize.generic.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");




if($argv[1]=="--tests"){tests();die();}






	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$timepid="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$unix=new unix();

	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($timepid)<20){
			if($GLOBALS["VERBOSE"]){echo "Need 20Mb minimal\n";}
			die();
		}
	}
	
	if($unix->process_exists($unix->get_pid_from_file($pidfile),basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already running...\n";}	
		die();
	}
	@unlink($timepid);
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($timepid, time());

	$ldap=new clladp();
	if(!$ldap->IsKerbAuth()){
		if($GLOBALS["VERBOSE"]){echo "Not connected to the Active Directory\n";}
		die();
	}


$ad=new external_ad_search();
echo "Organizations:***********";
$ad->build_sql_activedirectory_ou();


function tests(){
	$ad=new external_ad_search();
	print_r($ad->GroupsOfMember("CN=Jerome JB. Beunel,OU=AFEONLINE,OU=AFE,DC=afeonline,DC=net"));
}
