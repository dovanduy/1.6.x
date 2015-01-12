<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Network traffic probe";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--test"){$GLOBALS["OUTPUT"]=true;test_sensors();die();}

$GLOBALS["OUTPUT"]=true;
xstart();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.sensors.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}


}


function xstart(){
	
	build_progress("Change settings...",10);
	$sock=new sockets();
	$unix=new unix();
	$LMSensorsEnable=intval($sock->GET_INFO("LMSensorsEnable"));
	build_progress("Enabled: $LMSensorsEnable",15);
	if($LMSensorsEnable==1){
		xenable();
	}
	build_progress("{done}",100);
	
}

function xenable(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$sensors_detect=$unix->find_program("sensors-detect");
	echo "echo: $echo\nsensors_detect: $sensors_detect\n";
	build_progress("Detect sensors....",20);
	$cmd="$echo \"YES\"|$sensors_detect";
	system($cmd);
	
}

function test_sensors(){
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/sensors.array";
	$pidtime="/etc/artica-postfix/pids/exec.lm-sensors.php.time";
	$time=$unix->file_time_min($pidtime);
	if($time<15){
		if($GLOBALS["VERBOSE"]){echo "Current {$time}Mn, require 15Mn...\n";}
	}
	
	$sock=new sockets();
	$LMSensorsEnable=intval($sock->GET_INFO("LMSensorsEnable"));
	if($LMSensorsEnable==0){
		@unlink($cachefile);
		return;
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$sensors=$unix->find_program("sensors");
	exec("$sensors 2>&1",$results);
	
	while (list ($path, $val) = each ($results) ){
		if(preg_match("#Adapter:(.*)#i", $val,$re)){
			$adaptater=trim($re[1]);
		}
		
		if(preg_match("#(.*?):\s+\+([0-9\.]+).*?\((.*?)\)#", $val,$re)){
			$KEY=$re[1];
			$TEMP=$re[2];
			$POSZ=$re[3];
			if(preg_match("#high.*?=\s+\+([0-9\.]+)#", $POSZ,$re)){
				$HIGH=$re[1];
			}
			if(preg_match("#crit.*?=\s+\+([0-9\.]+)#", $POSZ,$re)){
				$CRIT=$re[1];
			}
			if($HIGH==null){$HIGH=$CRIT;}
			$ARRAY[$adaptater][$KEY]["TEMP"]=$TEMP;
			$ARRAY[$adaptater][$KEY]["HIGH"]=$HIGH;
			$ARRAY[$adaptater][$KEY]["CRIT"]=$CRIT;
			$percent=$TEMP/$CRIT;
			$percent=$percent*100;
			$ARRAY[$adaptater][$KEY]["PERC"]=round($percent,2);
			
			if($ARRAY[$adaptater][$KEY]["PERC"]>90){
				squid_admin_mysql(0, "Warning {$ARRAY[$adaptater][$KEY]["PERC"]}% of temperature reached!", 
				"Adaptater:$adaptater\nType:$KEY\nTemperature: {$TEMP}°C\nCritic:{$CRIT}°C",__FILE__,__LINE__
				
				);
				
				
			}
			
		}
		
	}
	
@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/sensors.array", serialize($ARRAY));
@chmod("/usr/share/artica-postfix/ressources/logs/web/sensors.array", 0755);
	
	
}

