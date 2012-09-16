<?php
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.ejabberd.inc');
	if(preg_match("#--verbose#",implode(" ",$argv))){
		echo "Running into verbose mode...\n";
		$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	
	
	
build();



function build(){
	
	$ejb=new ejabberd();
	$conf=$ejb->BuildMasterConf();
	@file_put_contents("/etc/ejabberd/ejabberd.cfg", $conf);
	$unix=new unix();
	$ejabberdctl=$unix->find_program("ejabberdctl");
	if(!is_file($ejabberdctl)){return;}
	$cmd="$ejabberdctl load_config /etc/ejabberd/ejabberd.cfg";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
}