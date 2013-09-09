#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');



if($argv[1]=="--reload"){reload();exit;}



function reload(){
	$unix=new unix();
	$sshd=$unix->find_program("sshd");
	if(!is_file($sshd)){return;}
	
	$pid=$unix->PIDOF($sshd);
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		shell_exec("$kill -HUP $pid");
	}
	
	
	
}
