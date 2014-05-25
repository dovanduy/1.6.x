<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["restart-auth"])){restart_auth();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();



function restart(){
	$unix=new unix();
	$nohup=null;
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --rdpproxy >/dev/null");
	shell_exec("$nohup /etc/init.d/rdpproxy restart >/dev/null 2>&1 &");
}
function restart_auth(){
	$unix=new unix();
	$nohup=null;
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --rdpproxy >/dev/null");
	shell_exec("$nohup /etc/init.d/rdpproxy-authhook restart >/dev/null 2>&1 &");
}


