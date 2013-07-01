<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["test-auth"])){test_auth();exit;}


reset($_GET);
while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --freeradius --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
}
function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --freeradius 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd=trim("$nohup /etc/init.d/freeradius restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	

}
function events(){
	$unix=new unix();
	$syslog=$unix->LOCATE_SYSLOG_PATH();
	
	
	
	
}


function test_auth(){
	$unix=new unix();
	$username=base64_decode($_GET["username"]);
	$password=base64_decode($_GET["password"]);
	$radtest=$unix->find_program("radtest");
	$username=$unix->shellEscapeChars($username);
	$password=$unix->shellEscapeChars($password);
	$resultsA="\t\t*********************************\n\t\t*********** FAILED *******************\n\t\t*********************************\n";
	$mainpassword=@file_get_contents("/etc/artica-postfix/ldap_settings/password");
	$mainpassword=$unix->shellEscapeChars($mainpassword);
	$cmdline="$radtest $username $password localhost 0 $mainpassword 2>&1";
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	exec($cmdline,$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#User-Password#", $ligne,$re)){
			
		}
		
		if(preg_match("#Access-Accept#", $ligne)){$resultsA="\t\t*********************************\n\t\t*********** SUCCESS ******************\n\t\t*********************************\n";}
	}
	
	$resultsA=str_replace("*", " * ", $resultsA);
	
	echo "<articadatascgi>". base64_encode($resultsA.@implode("\n", $results))."</articadatascgi>";
	
	
}