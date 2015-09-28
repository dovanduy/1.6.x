<?php



function squid_dashboard_statistics(){
	if($GLOBALS["CLASS_SOCKETS"]->SquidStatisticsEnabled()){return false;}
	$BASEDIR="/usr/share/artica-postfix/ressources/interface-cache";
	
	if(!is_file($BASEDIR)."/FLUX_HOUR"){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.interface-size.php --flux-hour >/dev/null 2>&1 &");
		
	}
	
}