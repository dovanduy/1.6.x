<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["repair-hour"])){repair_hour();exit;}
if(isset($_GET["processes-queue"])){process_queue();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();



function repair_hour(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$time=$_GET["repair-hour"];

	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.squid.stats.repair.php --repair-table-hour $time >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function process_queue(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("pgrep -l -f \"exec.squid-tail-injector.php --squid-sql-proc\" 2>&1",$results);
	
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#^([0-9]+).*?\s+([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)$#", $ligne,$re)){
			$pid=$re[1];
			if(!$unix->process_exists($pid)){continue;}
			if(!is_dir("/proc/$pid")){continue;}
			$ttl=$unix->PROCESS_UPTIME($pid);
			$day=strtotime("{$re[2]}-{$re[3]}-{$re[4]} {$re[5]}:00:00");
			$dayText=date("{l} {F} d H",$day)."h";
			if($ttl==null){continue;}
			$ttl=str_replace("uptime=", "", $ttl);
			$array[$day]=array("TTL"=>$ttl,"PID"=>$pid,"day"=>$dayText);
			continue;
		}
	
	}	
	
	krsort($array);
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
	
	
}