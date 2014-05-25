<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["installed"])){installed();exit;}
if(isset($_GET["uncompress"])){uncompress();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();




function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --snmpd 2>&1");
	
	
	shell_exec("$nohup /etc/init.d/snmpd restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --snmpd --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
}

function installed(){
	$unix=new unix();
	$snmpd=$unix->find_program("snmpd");
	if(!is_file($snmpd)){echo "<articadatascgi>FALSE</articadatascgi>";return;}
	echo "<articadatascgi>TRUE</articadatascgi>";
}

function pattern(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.haarp.php --squid-pattern >/dev/null 2>&1");	
	
}
function uncompress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
		return;
	}

	
	$cmd="$tar -xf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=snmpd_version();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --snmpd >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";

}

function snmpd_version(){
	$unix=new unix();
	$snmpd=$unix->find_program("snmpd");
	exec("$snmpd -v 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#NET-SNMP version:.*?([0-9\.]+)#", $line,$re)){return $re[1];}

	}
}