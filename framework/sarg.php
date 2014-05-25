<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["version"])){version();exit;}
if(isset($_GET["backup-test-nas"])){backup_test_nas();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function version(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sarg=$unix->find_program("sarg");
	$cmd="$sarg -h 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	
	
	while (list ($key, $line) = each ($results) ){
		if(preg_match("#sarg-([0-9\.]+)#", $line,$re)){$version=$re[1];}
	}
	
	echo "<articadatascgi>$version</articadatascgi>";
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