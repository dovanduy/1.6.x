<?php
$GLOBALS["BYPASS"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$cmdlines=@implode(" ", $argv);
writelogs("Executed `$cmdlines`","MAIN",__FILE__,__LINE__);
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

if($GLOBALS["VERBOSE"]){echo "Debug mode TRUE for {$argv[1]}\n";}

if($argv[1]=="--host"){replic_host($argv[2]);exit;}
if($argv[1]=="--all"){replic_all();exit;}


echo "Help:\n";
echo "--host [hostname].............: replicate a single host\n";
echo "--all.........................: replicate all hosts\n";


function replic_all(){
	$t=time();
	$q=new mysql();
	$sql="SELECT servername from freeweb WHERE groupware='ROUNDCUBE'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		system_admin_events("Error replicate roundcubes $q->mysql_error",__FUNCTION__, __FILE__, __LINE__, "roundcube");
		 die();	
	}
	$count=mysql_num_rows($results);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			replic_host($ligne["servername"]);
		}
		
	system_admin_events("replicate $count roundcubes done",__FUNCTION__, __FILE__, __LINE__, "roundcube");
	
}

function replic_host($servername){
	$t=time();
	$unix=new unix();
	$free=new freeweb($servername);
	$instanceid=$free->mysql_instance_id;
	$localdatabase=$free->mysql_database;
	if(!isset($free->Params["ROUNDCUBE"]["ENABLE_REPLIC"])){
		if($GLOBALS["VERBOSE"]){echo "$servername: ROUNDCUBE/ENABLE_REPLIC no set\n";}
		return null;}
	if($free->Params["ROUNDCUBE"]["ENABLE_REPLIC"]==0){
		if($GLOBALS["VERBOSE"]){echo "$servername: ROUNDCUBE/ENABLE_REPLIC set to disabled\n";}
		return null;}
	
	$ARTICA_PORT=$free->Params["ROUNDCUBE"]["ARTICA_PORT"];
	$ARTICA_ADMIN=$free->Params["ROUNDCUBE"]["ARTICA_ADMIN"];
	$ARTICA_PASSWORD=$free->Params["ROUNDCUBE"]["ARTICA_PASSWORD"];
	$ARTICA_HOST=$free->Params["ROUNDCUBE"]["ARTICA_HOST"];
	$ARTICA_RMWEB=$free->Params["ROUNDCUBE"]["ARTICA_RMWEB"];
	
	if($GLOBALS["VERBOSE"]){echo "Send order to get database dump $ARTICA_HOST:$ARTICA_PORT\n";}
	
	$auth=array("username"=>$ARTICA_ADMIN,"password"=>md5($ARTICA_PASSWORD));
	$auth=base64_encode(serialize($auth));
	
	$curl=new ccurl("https://$ARTICA_HOST:$ARTICA_PORT/exec.gluster.php");
	$curl->noproxyload=true;
	$curl->parms["AUTH"]=$auth;
	$curl->parms["RDCUBE-REPLIC"]=$ARTICA_RMWEB;
	
	if(!$curl->get()){
		if($GLOBALS["VERBOSE"]){echo "Error replicate roundcube to $ARTICA_HOST:$ARTICA_PORT with error $curl->error\n";}
		system_admin_events("Error replicate roundcube to $ARTICA_HOST:$ARTICA_PORT with error $curl->error",
		 __FUNCTION__, __FILE__, __LINE__, "roundcube");
		 return;
	}
	
	preg_match("#<INFOS>(.*?)</INFOS>#is",  $curl->data,$re);
	if($GLOBALS["VERBOSE"]){echo "$curl->data\n";}
	
	if(!preg_match("#<FILENAME>(.*?)</FILENAME>#is", $curl->data,$re)){
		preg_match("#<ERROR>(.*?)</ERROR>#is",  $curl->data,$re);
		if($GLOBALS["VERBOSE"]){echo "Error replicate roundcube to $ARTICA_HOST:$ARTICA_PORT with error {$re[1]}\n";}
		system_admin_events("Error replicate roundcube to $ARTICA_HOST:$ARTICA_PORT with error {$re[1]}",
		 __FUNCTION__, __FILE__, __LINE__, "roundcube");	
		return;
	}
	$filepath=$re[1];
	$filename=basename($filepath);
	$curl=new ccurl("https://$ARTICA_HOST:$ARTICA_PORT/$filepath");
	if(!$curl->GetFile("/tmp/$filename")){
		if($GLOBALS["VERBOSE"]){echo "Error get  roundcube database from $filepath with error $curl->error\n";}
		system_admin_events("Error get  roundcube database from $filepath with error $curl->error",
		 __FUNCTION__, __FILE__, __LINE__, "roundcube");
		 return;		
	}
	
	$filesize=$unix->file_size("/tmp/$filename");
	if($GLOBALS["VERBOSE"]){echo "Downloading $filename done with $filesize bytes\n";}
	if(!$unix->uncompress("/tmp/$filename", "/tmp/$filename.sql")){
		@unlink("/tmp/$filename");
		if($GLOBALS["VERBOSE"]){echo "Error uncompress $filepath\n";}
		system_admin_events("Error uncompress $filepath",
		 __FUNCTION__, __FILE__, __LINE__, "roundcube");
		 return;			
	}
	@unlink("/tmp/$filename");
	$mysqlbin=$unix->find_program("mysql");
	
	if($instanceid>0){
		$q=new mysql_multi($instance_id);
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqlbin --batch --force --user=$q->mysql_admin$password --socket=$q->SocketPath --database=$localdatabase </tmp/$filename.sql 2>&1";
		
	}else{
		$q=new mysql();
		if($q->mysql_server=="127.0.0.1"){
			$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";
		}else{
			$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";
		}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqlbin --batch --force --user=$q->mysql_admin$password $servcmd --database=$localdatabase </tmp/$filename.sql 2>&1";
	}
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	shell_exec($cmdline);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Success import from $filename to $localdatabase took $took",
		 __FUNCTION__, __FILE__, __LINE__, "roundcube");
	@unlink("/tmp/$filename.sql");
	
	
}