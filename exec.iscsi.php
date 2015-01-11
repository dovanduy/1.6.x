<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

$GLOBALS["deflog_start"]="Starting......: ".date("H:i:s")." [INIT]: iSCSI target";
$GLOBALS["deflog_sstop"]="Stopping......: ".date("H:i:s")." [INIT]: iSCSI target";

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
	
	
	if($GLOBALS["VERBOSE"]){ini_set_verbosed();}
}

if($argv[1]=="--build"){build();die();}
if($argv[1]=="--clients"){clients();die();}
if($argv[1]=="--stat"){statfile($argv[2]);die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}

function statfile($path){
	echo "$path\n---------------------------------\n";
	$array=stat($path);
	print_r($array);
	echo filetype($path)."\n";
	if(!is_file($path)){echo "is_file:false\n";}
	print_r( pathinfo($path));
}

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system_disks_iscsi_progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}


function FormatPath($dev,$sharedname){
	
	$sharedname=str_replace(" ", "", $sharedname);
	$sharedname=str_replace("/", "-", $sharedname);
	$sharedname=str_replace("\\", "-", $sharedname);
	return "$dev/$sharedname.disk";
}


function build(){
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		build_progress("{$GLOBALS["deflog_start"]} Already process exists $pid",110);
		echo "{$GLOBALS["deflog_start"]} Already process exists $pid\n";
		return;
	}
	
	@file_put_contents($pidfile,getmypid());	
	$year=date('Y');
	$month=date('m');
	$EnableISCSI=intval($sock->GET_INFO("EnableISCSI"));
	$dd=$unix->find_program("dd");
	if($EnableISCSI==0){
		build_progress("{$GLOBALS["deflog_start"]} {service_disabled}",110);
		return;
	}
	
	$sql="SELECT * FROM iscsi_params ORDER BY ID DESC";
	$q=new mysql();
	$c=0;
	$dd=$unix->find_program("dd");
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	if(!$q->ok){
		build_progress("{$GLOBALS["deflog_start"]} MySQL error",110);
		echo "{$GLOBALS["deflog_start"]} $q->mysql_error\n";return;		
	}
	
	build_progress("{$GLOBALS["deflog_start"]} {building}...",10);		
	$max=mysql_num_rows($results);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$hostname=$ligne["hostname"];
		$artica_type=$ligne["type"];
		$tbl=explode(".",$hostname);
		
		echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] ressource type:$artica_type {$ligne["dev"]}\n";
		build_progress("{$GLOBALS["deflog_start"]} {building} $c/$max $artica_type {$ligne["dev"]}",20);
		
		if($artica_type=="file"){
			if(!stat_system($ligne["dev"])){
				echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] creating file {$ligne["dev"]} {$ligne["file_size"]}Go\n";
				$countsize=$ligne["file_size"]*1000;
				$cmd="$dd if=/dev/zero of={$ligne["dev"]} bs=1M count=$countsize";
				if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] $cmd\n";}
				shell_exec($cmd);
				if(!stat_system($ligne["dev"])){
					build_progress("{$GLOBALS["deflog_start"]} {building} $artica_type {$ligne["dev"]} {failed}",20);
					echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] failed\n";
					continue;
				}
			}
		}
		
		krsort($tbl);
		$newhostname=@implode(".",$tbl);
		$Params=unserialize(base64_decode($ligne["Params"]));
		if(!isset($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
		if(!isset($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
		if(!isset($Params["Wthreads"])){$Params["Wthreads"]=8;}
		if(!isset($Params["IoType"])){$Params["IoType"]="fileio";}
		if(!isset($Params["mode"])){$Params["mode"]="wb";}

		if(!is_numeric($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
		if(!is_numeric($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
		if(!is_numeric($Params["Wthreads"])){$Params["Wthreads"]=8;}
		if($Params["IoType"]==null){$Params["IoType"]="fileio";}
		if($Params["mode"]==null){$Params["mode"]="wb";}
		$EnableAuth=$ligne["EnableAuth"];	
		$uid=trim($ligne["uid"]);

		echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] EnableAuth={$ligne["EnableAuth"]}\n";
		echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] uid=\"$uid\"\n";
		echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] Folder name=\"{$ligne["shared_folder"]} / {$ligne["type"]}\"\n";
		echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] Path=\"{$ligne["dev"]}\"\n";
		
		if($ligne["type"]=="file"){
			if(is_dir($ligne["dev"])){
				$newpath=FormatPath($ligne["dev"],$ligne["shared_folder"]);
				echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] Path is a directory assume $newpath\n";
				$ligne["dev"]=$newpath;
				$q->QUERY_SQL("UPDATE iscsi_params SET `dev`='$newpath' WHERE ID='{$ligne["ID"]}'","artica_backup");
			}
			
		}
		
		if(is_link($ligne["dev"])){$ligne["dev"]=@readlink($ligne["dev"]);}
		
		if($ligne["type"]=="file"){
			$pathFile=$ligne["dev"];
			$pathDir=dirname($ligne["dev"]);
			if(!is_dir($pathDir)){@mkdir($pathDir,0755,true);}
			if(!stat_system($pathFile)){
				echo "{$GLOBALS["deflog_start"]} [{$ligne["ID"]}] $pathFile no such file, create it\n";
				build_progress("{$GLOBALS["deflog_start"]} {building} $pathFile",20);
				$countsize=$ligne["file_size"]*1000;
				$cmd="$dd if=/dev/zero of={$ligne["dev"]} bs=1M count=$countsize";
				echo "$cmd\n";
				system($cmd);
				
			}
			
		}
		
		
		
		if($Params["ImmediateData"]==1){$Params["ImmediateData"]="Yes";}else{$Params["ImmediateData"]="No";}
		
		$f[]="Target iqn.$year-$month.$newhostname:{$ligne["shared_folder"]}";
		if($EnableAuth==1){
			if(strlen($uid)>2){
				echo "{$GLOBALS["deflog_start"]} Authentication enabled for {$ligne["dev"]} with member {$ligne["uid"]}\n";
				$user=new user($ligne["uid"]);
				if($user->password<>null){
					$f[]="\tIncomingUser {$ligne["uid"]} $user->password";
				}
			}
		}
		$f[]="\tLun $c Path={$ligne["dev"]},Type={$Params["IoType"]},IOMode={$Params["mode"]}";
		$f[]="\tMaxConnections {$Params["MaxConnections"]}";
		$f[]="\tImmediateData {$Params["MaxConnections"]}";
		$f[]="\tWthreads {$Params["Wthreads"]}";
		/*$f[]="\tMaxRecvDataSegmentLength 65536";
		$f[]="\tMaxXmitDataSegmentLength 65536";
		$f[]="\tMaxBurstLength          1048576";
		$f[]="\tFirstBurstLength        262144";
		$f[]="\tMaxOutstandingR2T       1";
		$f[]="\tHeaderDigest            None";
		$f[]="\tDataDigest              None";
		$f[]="\tNOPInterval             60";
		$f[]="\tNOPTimeout              180";
		$f[]="\tQueuedCommands          64";
		*/
		
		$f[]="";
		
		
		$c++;
	}
	
	@mkdir("/etc/iet",true,0600);
	
	$hostname=$unix->hostname_g();
	$tbl=explode(".",$hostname);
	krsort($tbl);
	$newhostname=@implode(".",$tbl);
	$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0 AND iscsid=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$count=mysql_num_rows($results);
	if($count>0){
		$sock=new sockets();
		$sock->SET_INFO("EnableISCSI", 1);
		
	}
	
	build_progress("{checking_containers}",30);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=trim($ligne["directory"]);
		$ID=$ligne["container_id"];
		$container_time=$ligne["container_time"];
		if(!is_numeric($container_time)){$container_time=0;}
		if($container_time==0){
			$container_time=time();
			$q->QUERY_SQL("UPDATE users_containers SET container_time=$container_time WHERE container_id=$ID","artica_backup");
		}
		
		$year=date("Y",$container_time);
		$month=date("m",$container_time);
		if($directory==null){echo "{$GLOBALS["deflog_start"]} id:$ID No specified main directory...";continue;}
		$ContainerFullPath=$directory."/$ID.disk";
		$f[]="Target iqn.$year-$month.$newhostname:disk$ID";
		$webdav_creds=unserialize(base64_decode($ligne["webdav_creds"]));
		
		echo "{$GLOBALS["deflog_start"]} iqn.$year-$month.$newhostname $ID.disk LUN $ContainerFullPath\n";
		build_progress("iqn.$year-$month.$newhostname $ID.disk",35);
		$f[]="\tIncomingUser {$webdav_creds["username"]} {$webdav_creds["password"]}";
		$f[]="\tLun $c Path=$ContainerFullPath,Type=fileio,IOMode=wb";
		$f[]="\tMaxConnections 5";
		$f[]="\tImmediateData Yes";
		$f[]="\tWthreads 8";
		$f[]="";
		
		
	}
	build_progress("{saving_configuration}",40);
	echo "{$GLOBALS["deflog_start"]} ietd.conf done\n";
	@file_put_contents("/etc/iet/ietd.conf",@implode("\n",$f));
	@file_put_contents("/etc/ietd.conf",@implode("\n",$f));
	build_progress("{checking_startup_script}",50);
	system($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php --iscsi");
	
	if($GLOBALS["PROGRESS"]){
		build_progress("{restarting}",80);
		system("/etc/init.d/iscsitarget restart");
	}
	
	build_progress("{done}",100);
	
}

function etc_default_iscsitarget(){
	if(!is_file("/etc/default/iscsitarget")){return;}
	$f[]="ISCSITARGET_ENABLE=true";
	@file_put_contents("/etc/default/iscsitarget", @implode("\n", true));
}

function clients(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Already process exists $pid\n";
		return;
	}
	
	$iscsiadm=$unix->find_program("iscsiadm");
	if(!is_file($iscsiadm)){
		if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} iscsiadm no such file\n";}
		return;
	}	
	
	@file_put_contents($pidfile,getmypid());
	
	
	
$f[]="node.startup = automatic";
$f[]="#node.session.auth.authmethod = CHAP";
$f[]="#node.session.auth.username = username";
$f[]="#node.session.auth.password = password";
$f[]="#node.session.auth.username_in = username_in";
$f[]="#node.session.auth.password_in = password_in";
$f[]="#discovery.sendtargets.auth.authmethod = CHAP";
$f[]="#discovery.sendtargets.auth.username = username";
$f[]="#discovery.sendtargets.auth.password = password";
$f[]="#discovery.sendtargets.auth.username_in = username_in";
$f[]="#discovery.sendtargets.auth.password_in = password_in";
$f[]="node.session.timeo.replacement_timeout = 120";
$f[]="node.conn[0].timeo.login_timeout = 15";
$f[]="node.conn[0].timeo.logout_timeout = 15";
$f[]="node.conn[0].timeo.noop_out_interval = 10";
$f[]="node.conn[0].timeo.noop_out_timeout = 15";
$f[]="node.session.initial_login_retry_max = 4";
$f[]="#node.session.iscsi.InitialR2T = Yes";
$f[]="node.session.iscsi.InitialR2T = No";
$f[]="#node.session.iscsi.ImmediateData = No";
$f[]="node.session.iscsi.ImmediateData = Yes";
$f[]="node.session.iscsi.FirstBurstLength = 262144";
$f[]="node.session.iscsi.MaxBurstLength = 16776192";
$f[]="node.conn[0].iscsi.MaxRecvDataSegmentLength = 131072";
$f[]="discovery.sendtargets.iscsi.MaxRecvDataSegmentLength = 32768";
$f[]="#node.conn[0].iscsi.HeaderDigest = CRC32C,None";
$f[]="#node.conn[0].iscsi.DataDigest = CRC32C,None";
$f[]="#node.conn[0].iscsi.HeaderDigest = None,CRC32C";
$f[]="#node.conn[0].iscsi.DataDigest = None,CRC32C";
$f[]="#node.conn[0].iscsi.HeaderDigest = CRC32C";
$f[]="#node.conn[0].iscsi.DataDigest = CRC32C";
$f[]="#node.conn[0].iscsi.HeaderDigest = None";
$f[]="#node.conn[0].iscsi.DataDigest = None";
$f[]="";	

@file_put_contents("/etc/iscsi/iscsid.conf",@implode("\n",$f));
	
	
	
	
	
	$sql="SELECT * FROM iscsi_client";
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} iscsiadm $sql\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	if(!$q->ok){
		echo "{$GLOBALS["deflog_start"]} iscsiadm $q->mysql_error\n";
		return;		
	}
	
	if(mysql_num_rows($results)==0){
		echo "{$GLOBALS["deflog_start"]} iscsiadm no iSCSI disk connection scheduled\n";
		return;
	}	
	
	
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} $q->mysql_error\n";}return;}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){		
		$subarray2=unserialize(base64_decode($ligne["Params"]));	
		$iqn="{$subarray2["ISCSI"]}:{$subarray2["FOLDER"]}";
		$port=$subarray2["PORT"];
		$ip=$subarray2["IP"];
		echo "{$GLOBALS["deflog_start"]} $iqn -> $ip:$port Auth:{$ligne["EnableAuth"]} Persistane:{$ligne["Persistante"]}\n";
		if($ligne["EnableAuth"]==1){
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.session.auth.username -v \"{$ligne["username"]}\" 2>&1";
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.session.auth.password -v \"{$ligne["password"]}\" 2>&1";
		}else{
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port --login 2>&1";
		}
		
		if($ligne["Persistante"]==1){
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.startup -v automatic 2>&1";
		}else{
			$cmds[]="$iscsiadm -m node --targetname $iqn -p $ip:$port -o update -n node.startup -v manual 2>&1";
		}
		
		$cmds[]="$iscsiadm -m node --logoutall all 2>&1";
		
	}
		

	if(is_array($cmds)){
		while (list ($num, $line) = each ($cmds)){
			if($GLOBALS["VERBOSE"]){echo "--------------------------\n$line\n";}
			$results=array();
			exec($line,$results);
			if($GLOBALS["VERBOSE"]){@implode("\n",$results);}
		}
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "--------------------------\n$iscsiadm -m node --loginall all\n";}
	shell_exec("$iscsiadm -m node --loginall all");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --usb-scan-write");
	
		
		
}

function stat_system($path){
	exec("stat -f $path -c %b 2>&1",$results);
	$line=trim(@implode("",$results));
	if(preg_match("#^[0-9]+#",$line,$results)){return true;}
	return false;
}
function PID_NUM(){

	$unix=new unix();
	
	$pid=$unix->get_pid_from_file("/var/run/iscsi_trgt.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("ietd");
	return $unix->PIDOF($Masterbin);

}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service shutdown - force - pid $pid...\n";}
	system("$kill -9 $pid");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service failed...\n";}
		return;
	}

}