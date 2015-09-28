<?php
#!/usr/bin/php -q
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');

scan();

function scan(){
	
	$unix=new unix();
	
	$calamaris=$unix->find_program("calamaris");
	if(!is_file($calamaris)){
		$unix->DEBIAN_INSTALL_PACKAGE("calamaris");
	}
	
	if(!is_file("/usr/share/perl5/GD/Graph.pm")){
		$unix->DEBIAN_INSTALL_PACKAGE("libgd-graph-perl");
		
	}
	
	$unix=new unix();
	$calamaris=$unix->find_program("calamaris");
	if(!is_file($calamaris)){return;}
	$cat=$unix->find_program("cat");
	$NICE=$unix->EXEC_NICE();
	
	$f[]="###############################################################################";
	$f[]="##################   CONFIGURATION FILE FOR CALAMARIS V3   ####################";
	$f[]="###############################################################################";
	$f[]="\$fresh_tags{'squid'}   = [( 'TCP_HIT', 'TCP_MEM_HIT', 'TCP_IMS_HIT', 'TCP_IMS_MISS' )];";
	$f[]="\$stale_tags{'squid'}   = [( 'TCP_REFRESH_HIT', 'TCP_REFRESH_MISS', 'TCP_REF_FAIL_HIT' )];";
	$f[]="\$refresh_tags{'squid'} = [( 'TCP_CLIENT_REFRESH' )];";
	$f[]="\$mod_tags{'squid'}     = [( 'TCP_REFRESH_MISS' )];";
	$f[]="\$unmod_tags{'squid'}   = [( 'TCP_REFRESH_HIT' )];";
	$f[]="\$response_time_limit = 2000;";
	$f[]="# Graph colours:";
	$f[]="# Default:";
	$f[]="\$column1_color = '#6699cc';";
	$f[]="\$column2_color = '#ff9900';";
	$f[]="\$text_color    = '#222266';";
	$f[]="\$image_type = 'png';";
	$f[]="\$formats[3]  = [ 30, 9, '%', 'spr', 8, '%', 'kbps' ];";
	$f[]="\$formats[4]  = [ 30, 9, '%', 'mspr', 8, '%', 'kbps' ];";
	$f[]="\$formats[5]  = [ 30, 9, '%', 'spr', 8, '%', 'kbps' ];";
	$f[]="\$formats[6]  = [ 30, 9, '%', 'spr', 8, '%', 'kbps' ];";
	$f[]="\$formats[7]  = [ 30, 9, '%', 'spr', 8, '%', 'kbps' ];";
	$f[]="\$formats[8]  = [ 26, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[9]  = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[10] = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[11] = [ 26, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[12] = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps', 11, 11 ];";
	$f[]="\$formats[13] = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[14] = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[15] = [ 16, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[16] = [ 15, 9, '%', 5, '%', 6, 'kbps', 'kbps', 'kbps', 'kbps', 'kbps', 'kbps' ];";
	$f[]="\$formats[17] = [ 16, 9, '%', '%', 'mspr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[18] = [ 16, 9, '%', '%', 'mspr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[19] = [ 36, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$formats[20] = [ 36, 9, '%', '%', 'spr', 8, '%', '%', 'kbps' ];";
	$f[]="\$unit = M;";
	$f[]="\$width = 1390;";
	$f[]="";	
	
	
	
	
	
	@file_put_contents("/etc/calamaris/calamaris.conf",@implode("\n", $f));
	
	
	shell_exec("$cat /var/log/squid/access.log|$NICE$calamaris --config-file /etc/calamaris/calamaris.conf --input-format squid --unit M --output-format html-embed,graph -a  --output-path {$GLOBALS["BASEDIR"]} --output-file CALAMARIS");
	@chmod("{$GLOBALS["BASEDIR"]}/CALAMARIS", 0755);
	
	
	
}