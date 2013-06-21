<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: Overloaded system,die()","MAIN",__FILE__,__LINE__);die();}

if($argv[1]=="--mysql"){mysql_rrd();die();}


if(!function_exists("rrd_create")){echo "rrd_create() no such function\n";die();}





	  $options = array(
	   // "--slope-mode",
	   "--width",300,
	   "--height",120,
	   "--full-size-mode",
	 //  "--border",0,
	   "--tabwidth",10,
	    "--start", "-1h",
	    "--title=Load avg",
	    "--vertical-label=Load average",
	    "--alt-autoscale-max",
	    "--lower-limit=0",
	    "--lower=0",
	    "DEF:loadavg_1=/opt/artica/var/rrd/yorel/loadavg_1.rrd:loadavg_1:AVERAGE",
	    "LINE:loadavg_1#00FF00:Load average",
  		"GPRINT:loadavg_1:AVERAGE:Avg\: %3.2lf",
  		"GPRINT:loadavg_1:MAX:Max\:%3.2lf",
	);

 $ret = rrd_graph("/usr/share/artica-postfix/ressources/logs/web/load.png", $options, count($options));
	  if (! $ret) {
	    echo "<b>Graph error: </b>".rrd_error()."\n";
	  }



return;

rrd_xload();
function rrd_xload(){
	$filetime="/etc/artica-postfix/pids/rrd.load.time";
	$unix=new unix();
	if(!$GLOBALS["FORCE"]){
		$timeN=$unix->file_time_sec($filetime);
		if($timeN<120){if($GLOBALS["VERBOSE"]){echo "$timeN/120, abroting\n";}return;}
	}
	@unlink($filetime);@file_put_contents($filetime, time());

	if(!is_dir("/usr/share/artica-postfix/ressources/databases/rrd")){
		@mkdir("/usr/share/artica-postfix/ressources/databases/rrd",0755,true);
	}
	
	if(!is_file("/usr/share/artica-postfix/ressources/databases/rrd/load.rrd")){
		echo "Creating load.rrd\n";
	$opts = array( "--step", "120", "--start", 0,
	           "DS:load:GAUGE:120:U:U",
	           "RRA:AVERAGE:0.5:1:1440",
			   "RRA:AVERAGE:0.5:6:1800",      
	 		   "RRA:AVERAGE:0.5:24:1800",   
			   "RRA:AVERAGE:0.5:288:1800",

			   "RRA:MIN:0.5:1:1440", 
	 		   "RRA:MIN:0.5:6:1800",
			   "RRA:MIN:0.5:24:1800",
	 			"RRA:MIN:0.5:288:1800",
	 
	 			"RRA:MAX:0.5:1:1440",  
	 			"RRA:MAX:0.5:6:1800",  
	 			"RRA:MAX:0.5:24:1800",  
	 			"RRA:MAX:0.5:288:1800",
	);
	  $ret = @rrd_create("/usr/share/artica-postfix/ressources/databases/rrd/load.rrd", $opts, count($opts));
	  if( $ret == 0 ){$err = rrd_error();echo "Create error: $err\n";}	
		
	}
	
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$t=time();
		echo "Load:$internal_load\n";
		//$internal_load=str_replace(".", ",", $internal_load);
		$ret = rrd_update("/usr/share/artica-postfix/ressources/databases/rrd/load.rrd", "$t:$internal_load");
		if( $ret == 0 ){$err = rrd_error();echo "update error: $err\n";}
	
	create_graph("/usr/share/artica-postfix/ressources/logs/web/load.png", "-1h", "Load Hourly");
}		
function create_graph($output, $start, $title) {
	  $options = array(
	    "--slope-mode",
	    "--start", $start,
	    "--title=$title",
	    "--vertical-label=Load 0 -> 30",
	    "--alt-autoscale-max",
	    "--lower-limit=0",
	    "--lower=0",
	    "DEF:load2mn=/usr/share/artica-postfix/ressources/databases/rrd/load.rrd:load:AVERAGE",
	    "LINE:load2mn#00FF00:Load average",
  		"GPRINT:load2mn:AVERAGE:Avg\: %3.2lf",
  		"GPRINT:load2mn:MAX:Max\:%3.2lf",
	  
	    
	  );
	
	  $ret = rrd_graph($output, $options, count($options));
	  if (! $ret) {
	    echo "<b>Graph error: </b>".rrd_error()."\n";
	  }
}

function mysql_rrd(){
$unix=new unix();
$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
if($unix->file_time_min($timefile)<5){die();}
@file_put_contents($timefile, time());
$WORKING_DIR="/usr/share/artica-postfix/ressources/databases/rrd";
@mkdir($WORKING_DIR,0755,true);
$rrdtool=$unix->find_program("rrdtool");
$mysqladmin=$unix->find_program("mysqladmin");
if(!is_file("$rrdtool")){return;}
if(!is_file("$mysqladmin")){return;}

$file0="$WORKING_DIR/mysql0.rrd";
$f[]="DS:select:COUNTER:600:0:U";
$f[]="DS:insert:COUNTER:600:0:U";
$f[]="DS:update:COUNTER:600:0:U";
$f[]="DS:delete:COUNTER:600:0:U";
$f[]="DS:cache:COUNTER:600:0:U";
$f[]="DS:total:COUNTER:600:0:U";
$f[]="DS:connect:COUNTER:600:0:U";
$f[]="DS:inbound:COUNTER:600:0:U";
$f[]="DS:outbound:COUNTER:600:0:U";
$f[]="RRA:AVERAGE:0.5:1:600";
$f[]="RRA:AVERAGE:0.5:6:700";
$f[]="RRA:AVERAGE:0.5:24:775";
$f[]="RRA:AVERAGE:0.5:288:797";
$f[]="RRA:MAX:0.5:1:600";
$f[]="RRA:MAX:0.5:6:700";
$f[]="RRA:MAX:0.5:24:775";
$f[]="RRA:MAX:0.5:288:797";
$cmdlines=@implode(" ",$f);
if(!is_file($file0)){shell_exec("$rrdtool create $file0 $cmdlines");}
$q=new mysql();
if($q->mysql_password<>null){$pass="--password=\"$q->mysql_password\" ";}
$cmd="$mysqladmin --user=$q->mysql_admin {$pass}extended-status";
exec($cmd,$results);
$MYR=mysql_parsevals($results);
$select=$MYR["Com_select"];
$insert=$MYR["Com_insert"];
$update=$MYR["Com_update"];
$delete=$MYR["Com_delete"];
$cache=$MYR["Qcache_hits"];
$total=$MYR["Questions"];
$connect=$MYR["Connections"];
$inbound=$MYR["Bytes_received"];
$outbound=$MYR["Bytes_sent"];

$cmdline="$rrdtool update $file0 N:$select:$insert:$update:$delete:$cache:$total:$connect:$inbound:$outbound";
echo $cmdline."\n";
shell_exec($cmdline);

if(!is_file("/etc/mysql-multi.cnf")){echo "/etc/mysql-multi.cnf no such file\n";return;}
$ini=new Bs_IniHandler();
$ini->loadFile("/etc/mysql-multi.cnf");
$INSTANCES=array();
while (list ($key, $line) = each ($ini->_params)){
	echo "F:$key\n";
	if(preg_match("#^mysqld([0-9]+)#", $key,$re)){
		$instance_id=$re[1];
		$INSTANCES[$instance_id]=true;
	}
}
while (list ($instance_id, $line) = each ($INSTANCES)){
	echo "I:$instance_id\n";
	$pass=null;
	$file0="$WORKING_DIR/mysql$instance_id.rrd";
	if(!is_file($file0)){shell_exec("$rrdtool create $file0 $cmdlines");}
	$qA=new mysql_multi($instance_id);
	if($qA->mysql_password<>null){$pass="--password=\"$qA->mysql_password\"";}
	$cmd="$mysqladmin -S /var/run/mysqld/mysqld$instance_id.sock --user=$qA->mysql_admin {$pass}extended-status";
	echo "C:$cmd\n";
	$results=array();exec($cmd,$results);
	exec($cmd,$results);
	$MYR=mysql_parsevals($results);
	echo "I:$instance_id:". count($MYR)." items\n";
	$select=$MYR["Com_select"];
	$insert=$MYR["Com_insert"];
	$update=$MYR["Com_update"];
	$delete=$MYR["Com_delete"];
	$cache=$MYR["Qcache_hits"];
	$total=$MYR["Questions"];
	$connect=$MYR["Connections"];
	$inbound=$MYR["Bytes_received"];
	$outbound=$MYR["Bytes_sent"];
	$cmdline="$rrdtool update $file0 N:$select:$insert:$update:$delete:$cache:$total:$connect:$inbound:$outbound";
	echo $cmdline."\n";
	shell_exec($cmdline);	
}






}

function mysql_parsevals($array){
	while (list ($num, $line) = each ($array)){
		if(preg_match("#\|(.+?)\s+\|(.+?)\|#", $line,$re)){
			$key=trim($re[1]);
			$value=trim($re[2]);
			$f[$key]=$value;
		}
		
	}
	return $f;
	
	
}




