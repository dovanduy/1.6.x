<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if($argv[1]=="--watch"){watchdog($argv[2]);die();}




function watchdog($maxProcesses=50){
	
	$unix=new unix();
	$pdns_server=$unix->find_program("pdns_server");
	$pdns_recursor=$unix->find_program("pdns_recursor");
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");
	
	echo "pdns_server = $pdns_server\n";
	echo "pdns_recursor = $pdns_recursor\n";
	
	
	exec("$pidof $pdns_server 2>&1",$results);
	$string=@implode("", $results);
	$exploded=@explode(" ", $string);
	while (list ($num, $val) = each ($exploded)){if(!is_numeric($val)){echo "skip $val\n";continue;}$PIDS[$val]=$val;}
	echo count($PIDS)." processes <> $maxProcesses for $pdns_server\n";
	
	if(count($PIDS) > $maxProcesses){
		echo "Watchdog GO -> kill $pdns_server !\n";
		while (list ($num, $int) = each ($PIDS)){
			echo "Killing $pdns_server pid $num\n";
			unix_system_kill_force($num);		
		}
		
	
	
		$PIDS=array();
		exec("$pidof $pdns_recursor 2>&1",$results);
		$string=@implode("", $results);
		$exploded=@explode(" ", $string);
		while (list ($num, $val) = each ($exploded)){if(!is_numeric($val)){continue;}$PIDS[$val]=$val;}	
		echo count($PIDS)." processes <> $maxProcesses for $pdns_recursor\n";
		while (list ($num, $int) = each ($PIDS)){
			echo "Killing $pdns_recursor pid $num \n";
			unix_system_kill_force($num);		
		}		
		
	}
	
	
	echo "Finish\n";
	
}
