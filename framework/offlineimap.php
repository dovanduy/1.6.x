<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["exec"])){execute();exit;}
writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);


function execute(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$md5=$_GET["md5"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.mailbox.migration.php --member $md5 >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}