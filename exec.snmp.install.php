<?php
$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');


if($argv[1]=="--run"){run();exit;}



install();
function install(){
	if(extension_loaded('snmp')){return;}
	$unix=new unix();
	
	$FileTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	if($unix->file_time_min($FileTime)<15){return;}
	@unlink($FileTime);
	@file_put_contents($FileTime, time());
	$unix->DEBIAN_INSTALL_PACKAGE("php5-snmp");
	system("/etc/init.d/artica-webconsole restart");
	system("/etc/init.d/artica-status restart");
}




function run(){
	if(!extension_loaded('snmp')){install();exit;}
	if(!class_exists("SNMP")){exit;}
	
  $session = new SNMP(SNMP::VERSION_1, "127.0.0.1:3401", "public");
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $ifDescr = $session->walk(".1.3.6.1.4.1.3495", TRUE);
 // $session->valueretrieval = SNMP_VALUE_LIBRARY;
 // $ifType = $session->walk(".1.3.6.1.4.1.3495.1.3", TRUE);
 
  // 2.2.1.10.5
  print_r($ifDescr);

  
  
}
  
?>
