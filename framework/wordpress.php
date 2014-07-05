<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["is_installed"])){is_installed();exit;}
if(isset($_GET["version"])){version();exit;}
if(isset($_GET["install"])){install();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function is_installed(){
	if(is_dir("/usr/share/wordpress-src/wp-includes")){ echo "<articadatascgi>TRUE</articadatascgi>"; return; }
	echo "<articadatascgi>FALSE</articadatascgi>";
	
}

function install(){

	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.reconfigure.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.wordpress.download.php >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	
	
}

function version(){
	@chmod("/usr/share/artica-postfix/bin/wp-cli.phar",0755);
	$cmd="/usr/share/artica-postfix/bin/wp-cli.phar --allow-root core version --path=/usr/share/wordpress-src 2>&1";
	
	$version=exec($cmd);
	if(preg_match("#wp core download#", $version)){
		return null;
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".exec($cmd)."</articadatascgi>";
	
}

