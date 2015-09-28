<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.kerb.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

xrun();



function xrun(){
$unix=new unix();	
$siege=$unix->find_program("siege");
$sock=new sockets();

$ARRAY=unserialize(base64_decode($sock->GET_INFO("SquidSiegeConfig")));
if(!is_numeric($ARRAY["GRAB_URLS"])){$ARRAY["GRAB_URLS"]=0;}
if(!is_numeric($ARRAY["USE_LOCAL_PROXY"])){$ARRAY["USE_LOCAL_PROXY"]=1;}
if(!is_numeric($ARRAY["SESSIONS"])){$ARRAY["SESSIONS"]=150;}
if(!is_numeric($ARRAY["MAX_TIME"])){$ARRAY["MAX_TIME"]=30;}

build_progress_disconnect("{starting}",5);

if(!is_file($siege)){
	build_progress_disconnect("{please_wait} {installing} SIEGE",50);
	$unix->DEBIAN_INSTALL_PACKAGE("siege");
	$siege=$unix->find_program("siege");
	if(!is_file($siege)){
		build_progress_disconnect("{installing} SIEGE {failed}",110);
	}
}

$f[]="internet = true";
if($ARRAY["USE_LOCAL_PROXY"]==1){
	
	$squid=new squidbee();
	if($squid->hasProxyTransparent==1){
		$port=$squid->second_listen_port;
	}else{
		$port=$squid->listen_port;
	}
	$addr="127.0.0.1";
}else{
	$addr=$ARRAY["REMOTE_PROXY"];
	$port=intval($ARRAY["REMOTE_PROXY_PORT"]);
	
	
}

if($addr==null){
	build_progress_disconnect("{failed} No proxy address",110);
	return;
}
if($port==0){
	build_progress_disconnect("{failed} No proxy port",110);
	return;
}
if($ARRAY["SESSIONS"]==0){
	build_progress_disconnect("{failed} {simulate} 0 sessions",110);
	return;
}

$f[]="proxy-host =$addr";
$f[]="proxy-port = $port";
$f[]="user-agent = Mozilla/5.0 (compatible; IE 11.0; Win32; Trident/7.0)";
$f[]="file = /etc/siege/urls.txt";
$f[]="concurrent = {$ARRAY["SESSIONS"]}";
$f[]="time = {$ARRAY["MAX_TIME"]}S";
$f[]="timeout = 5";
$f[]="logfile = /var/log/siege.log";
if(trim($ARRAY["USERNAME"])<>null){
	$f[]="username = {$ARRAY["USERNAME"]}";
	$f[]="password = {$ARRAY["PASSWORD"]}";
}
@file_put_contents("/root/.siegerc", @implode("\n", $f));
$filetemp=$unix->FILE_TEMP();
$nohup=$unix->find_program("nohup");

$URLS_NUMBER=$unix->COUNT_LINES_OF_FILE("/etc/siege/urls.txt");
if($URLS_NUMBER<20){
	@unlink("/etc/siege/urls.txt");
	if($ARRAY["GRAB_URLS"]==1){
			import_urls();
	}else{
		@copy("/usr/share/artica-postfix/bin/install/squid/urls.txt","/etc/siege/urls.txt");
	}
	$URLS_NUMBER=$unix->COUNT_LINES_OF_FILE("/etc/siege/urls.txt");
}


$FINAL["urls"]=$URLS_NUMBER;
$FINAL["START_TIME"]=time();
$ss[]="$nohup $siege --concurrent={$ARRAY["SESSIONS"]}";
$ss[]="--internet --file=/etc/siege/urls.txt --time={$ARRAY["MAX_TIME"]}S"; 
$ss[]="--benchmark --rc=/root/.siegerc >$filetemp 2>&1 &";
$cmd=@implode(" ", $ss);
echo "$cmd\n";
build_progress_disconnect("{executing}",50);

system($cmd);	
sleep(2);
$pid=$unix->PIDOF($siege);
while ($unix->process_exists($pid)){
	$array_mem=getSystemMemInfo();
	$MemFree=$array_mem["MemFree"];
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	echo "Memory Free: ".round($MemFree/1024)." MB\n";
	echo "Load: $internal_load\n";
	build_progress_disconnect("{please_wait} Load:$internal_load",50);
	sleep(2);
	$pid=$unix->PIDOF($siege);
}

build_progress_disconnect("{please_wait} {analyze}...",90);
$array=explode("\n",@file_get_contents($filetemp));
@unlink($filetemp);
while (list ($num, $val) = each ($array) ){
	echo "$val\n";
	if(preg_match("#alert#", $val)){continue;}
	if(preg_match("#ERROR#", $val)){continue;}
	
	if(preg_match("#(.+?):\s+(.+)#", $val,$re)){
		$FINAL[trim($re[1])]=trim($re[2]);
	}
}
$FINAL["STOP_TIME"]=time();
build_progress_disconnect("{done}...",99);
sleep(5);
build_progress_disconnect("{done}...",100);
@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/siege.report.txt", serialize($FINAL));
@chmod("/usr/share/artica-postfix/ressources/logs/web/siege.report.txt",0755);	
}




function build_progress_disconnect($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.siege.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}



function import_urls(){



	$handle = @fopen("/var/log/squid/access.log", "r");
	if (!$handle) {echo "Failed to open file\n";return;}
	
	while (!feof($handle)){
		
		$www =trim(fgets($handle, 4096));
	
		if(!preg_match("#GET http(.+?)\s+#", $www,$re)){continue;}
		$array["http{$re[1]}"]=true;
		if(count($array)>500){break;}
		
		
	}
	
	while (list ($num, $val) = each ($array) ){
		$f[]=$num;
	}
	$array=array();
	@mkdir("/etc/siege");
	@file_put_contents("/etc/siege/urls.txt", @implode("\n", $f));
	$f=array();
	build_progress_disconnect(count($f)." urls saved",20);

}