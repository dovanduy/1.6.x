<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');




function dump(){
	
	
	$unix=new unix();
	if(!$unix->is_socket("/var/run/syslogdb.sock")){return false;}
	
	
	
	$bd=@mysql_connect(":/var/run/syslogdb.sock","root");
	if(!$bd){return;}
	$ok=@mysql_select_db("syslogs",$bd);
	
	$results=QUERY_SQLZ("SELECT storeid,filename FROM accesslogs");
	if(!$results){
		return false;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$storeid=$ligne["storeid"];
		$filename=$ligne["filename"];
		if(!export_storeid_access($storeid,$filename)){continue;}
		
	}
	
	
}


function QUERY_SQLZ($sql){
	$bd=@mysql_connect(":/var/run/syslogdb.sock","root");
	if(!$bd){return false;}
	$ok=@mysql_select_db("syslogs",$bd);
	if(!$ok){
		$errnum=@mysql_errno($bd);
		$des=@mysql_error($bd);
		@mysql_close($bd);
	}
	
	$results=mysql_query($sql,$bd);
	if(!$results){
		$errnum=@mysql_errno($bd);
		$des=@mysql_error($bd);
		@mysql_close($bd);
		return false;
	}
	return $results;
}

function export_storeid_access($storeid,$filename){
	
	$ligne=mysql_fetch_array(QUERY_SQLZ($sql));
	
}





