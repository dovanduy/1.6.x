<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-cmds"])){service_cmds();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Position: $cmds";
	
	if($cmds=="restart"){
		exec("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus 2>&1",$results);
		
		
	}else{
		exec("/etc/init.d/artica-postfix $cmds imap 2>&1",$results);
	}
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	if(is_dir("/var/run/saslauthd")){@chmod("/var/run/saslauthd", 0755);}
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function restart(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus >/dev/null 2>&1 &");
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	if(is_dir("/var/run/saslauthd")){@chmod("/var/run/saslauthd", 0755);}
	
}