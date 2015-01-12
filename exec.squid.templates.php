<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["BY"]=null;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.templates-simple.inc');
if(preg_match("#--smooth#",implode(" ",$argv))){$GLOBALS["SMOOTH"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
if(preg_match("#--noverifcaches#",implode(" ",$argv))){$GLOBALS["NO_VERIF_CACHES"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--initd#",implode(" ",$argv))){$GLOBALS["BYINITD"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--FUNC-(.+?)-L-([0-9]+)#", implode(" ",$argv),$re)){$GLOBALS["BY"]=" By {$re[1]} Line {$re[2]}";}



sexec();

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.templates.single.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}


}

function sexec(){
	$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".sexec.pid";
	$TILE_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".sexec.pid";
	$unix=new unix();
	$sock=new sockets();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){die();}
	$pid=@file_get_contents($EXEC_PID_FILE);
	if($unix->process_exists($pid,basename(__FILE__))){	
		
		build_progress("Already running",110);
		return false;}
	
	$TILE_PID_TIME=$unix->file_time_min($TILE_PID_FILE);
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	
	
	$GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
	$xtpl=new template_simple();
	
	$MAIN=$GLOBALS["XTPL_SQUID_DEFAULT"][$SquidHTTPTemplateLanguage];
	
	@mkdir("/usr/share/squid-langpack/$SquidHTTPTemplateLanguage",0755,true);
	@chown("/usr/share/squid-langpack/$SquidHTTPTemplateLanguage","squid");
	@chgrp("/usr/share/squid-langpack/$SquidHTTPTemplateLanguage", "squid");
	
	
	$arrayxLangs=$xtpl->arrayxLangs;
	
	
	
	
	
	while (list ($TEMPLATE_TITLE, $subarray) = each ($MAIN)){
		build_progress("{building} $TEMPLATE_TITLE",50);
		$xtpl=new template_simple($TEMPLATE_TITLE,$SquidHTTPTemplateLanguage);
		$templateDestination="/usr/share/squid-langpack/templates/$TEMPLATE_TITLE";
		$templateLangDestination="/usr/share/squid-langpack/templates/$SquidHTTPTemplateLanguage/$TEMPLATE_TITLE";
		$design=$xtpl->TemplatesDesign();
		@file_put_contents($templateDestination, $design);
		@file_put_contents($templateLangDestination, $design);
		if($GLOBALS["VERBOSE"]){echo "$TEMPLATE_TITLE: $SquidHTTPTemplateLanguage $templateDestination done\n";}
		if($GLOBALS["VERBOSE"]){echo "$TEMPLATE_TITLE: $SquidHTTPTemplateLanguage $templateLangDestination done\n";}
		
		@chown($templateLangDestination,"squid");
		@chgrp($templateLangDestination, "squid");
		
		@chown($templateDestination,"squid");
		@chgrp($templateDestination, "squid");
		
	}
	
	$ln=$unix->find_program("ln");
	while (list ($Mainlang, $xarr) = each ($xtpl->arrayxLangs)){
		
	
		
		while (list ($index, $z) = each ($xarr)){
			build_progress("Saving $z",60);
			$destination_path="/usr/share/squid-langpack/templates/$z";
			if(!is_link($destination_path)){shell_exec("/bin/rm -rf $destination_path");}
			@unlink("$destination_path");
			shell_exec("$ln -sf \"/usr/share/squid-langpack/templates/$Mainlang\" \"$destination_path\"");
		}
	
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($GLOBALS["BYINITD"]){
		$addon="By init.d";
	}
	if($GLOBALS["BY"]<>null){
		$addon=$GLOBALS["BY"];
	}
	
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --mime");
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONEv3", time());
	
	if($GLOBALS["PROGRESS"]){
		build_progress("{reloading} Proxy service",70);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		squid_admin_mysql(2, "Restarting proxy service in order to refresh templates ($addon)", null,__FILE__,__LINE__);
		system("/etc/init.d/squid restart --force");
		build_progress("{done}",100);
		$TILE_PID_TIME=0;
		return;
	}
	
	if(!$GLOBALS["NORELOAD"]){
		if($TILE_PID_TIME>30){
			
			
			build_progress("{reloading} Proxy service",70);
			squid_admin_mysql(2, "Reloading proxy service in order to refresh templates ($addon)", null,__FILE__,__LINE__);
			$squidbin=$unix->LOCATE_SQUID_BIN();
			system("/etc/init.d/squid reload --script=exec.squid.templates.php/".__LINE__);
			
			@unlink($TILE_PID_FILE);
			@file_put_contents($TILE_PID_FILE, time());
		}else{
			squid_admin_mysql(2, "Reloading proxy service ($addon) skipped (timed out - {$TILE_PID_TIME}Mn )", null,__FILE__,__LINE__);
		}
	}
	build_progress("{done}",100);
	
}
?>