<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');



xstart();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.ssl.rules.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function checkIntegrated(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f) ){
		
		if(preg_match("#ssl\.conf#", $line)){return true;}
	
	}
	
	return false;
}





function xstart(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{starting} {ssl_rules}",15);
	if(!checkIntegrated()){
		
		build_progress("{starting} {ssl_rules}",30);
		$squid_ssl=new squid_ssl();
		$squid_ssl->build();
		build_progress("{starting} {reconfigure_proxy_service}",50);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		
		if(!checkIntegrated()){
			build_progress("{failed}",110);
			return;
		}
		build_progress("{done} {ssl_rules}",100);
		return;
	}
	
	build_progress("{starting} {ssl_rules}",30);
	$squid_ssl=new squid_ssl();
	$squid_ssl->build();

	
	
	if(!Test_config()){
		build_progress("{failed}",90);
		@file_put_contents("/etc/squid3/GlobalAccessManager_auth.conf", "\n");
		@file_put_contents("/etc/squid3/GlobalAccessManager_url_rewrite.conf", "\n");
		@file_put_contents("/etc/squid3/GlobalAccessManager_deny_cache.conf", "\n");
		@file_put_contents("/etc/squid3/icap.conf","\n");
		build_progress("{failed}",110);
		return;
	}
	
	build_progress("{done} {reloading_proxy_service}",100);
	$squidbin=$unix->find_program("squid");
	system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
}

function Test_config(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}

	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return false;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "[line:$lineNumber]: {$tt[$i]}\n";
				}
			}

			return false;
		}
	
	}

	return true;
	
}


