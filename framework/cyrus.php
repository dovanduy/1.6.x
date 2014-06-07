<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["backup-test-nas"])){backup_test_nas();exit;}
if(isset($_GET["create-mbx"])){create_mailbox();exit;}



writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
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
function backup_test_nas(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.cyrus.backup.php --test-nas --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function create_mailbox(){
	$MailBoxMaxSize=$_GET["MailBoxMaxSize"];
	@unlink("/usr/share/artica-postfix/ressources/logs/cyrus.mbx.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/cyrus.mbx.progress",0777);
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt",0777);

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.cyrus.creatembx.php --create-mbx \"{$_GET["uid"]}\" \"$MailBoxMaxSize\">/usr/share/artica-postfix/ressources/logs/web/cyrus.mbx.txt 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}
