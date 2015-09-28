<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--restart"){restart();exit;}


function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --transmission-daemon >/dev/null 2>&1");
	
	shell_exec($cmd);
	shell_exec("/etc/init.d/transmission-daemon stop");
	shell_exec("/etc/init.d/transmission-daemon start");

	
}

function build(){
	
	
	$sock=new sockets();
	$EnableTransMissionDaemon=intval($sock->GET_INFO("EnableTransMissionDaemon"));
	$TransMissionDaemonDir=$sock->GET_INFO("TransMissionDaemonDir");
	if($TransMissionDaemonDir==null){$TransMissionDaemonDir="/home/transmission-daemon/downloads";}
	
	@mkdir($TransMissionDaemonDir,0755,true);
	@chown($TransMissionDaemonDir, "debian-transmission");
	@chgrp($TransMissionDaemonDir, "debian-transmission");
	
	
	$ldap=new clladp();
	$TransMissionDaemonListen=$sock->GET_INFO("TransMissionDaemonListen");
	if($TransMissionDaemonListen==null){$TransMissionDaemonListen="0.0.0.0";}
	$TransMissionDaemonPort=intval($sock->GET_INFO("TransMissionDaemonPort"));
	if($TransMissionDaemonPort==0){$TransMissionDaemonPort=9091;}
	
	$f[]="{";
	$f[]="    \"alt-speed-down\": 50, ";
	$f[]="    \"alt-speed-enabled\": false, ";
	$f[]="    \"alt-speed-time-begin\": 540, ";
	$f[]="    \"alt-speed-time-day\": 127, ";
	$f[]="    \"alt-speed-time-enabled\": false, ";
	$f[]="    \"alt-speed-time-end\": 1020, ";
	$f[]="    \"alt-speed-up\": 50, ";
	$f[]="    \"bind-address-ipv4\": \"$TransMissionDaemonListen\", ";
	$f[]="    \"bind-address-ipv6\": \"::\", ";
	$f[]="    \"blocklist-enabled\": false, ";
	$f[]="    \"blocklist-url\": \"http://www.example.com/blocklist\", ";
	$f[]="    \"cache-size-mb\": 4, ";
	$f[]="    \"dht-enabled\": true, ";
	$f[]="    \"download-dir\": \"/home/transmission-daemon/downloads\", ";
	$f[]="    \"download-limit\": 100, ";
	$f[]="    \"download-limit-enabled\": 0, ";
	$f[]="    \"download-queue-enabled\": true, ";
	$f[]="    \"download-queue-size\": 5, ";
	$f[]="    \"encryption\": 1, ";
	$f[]="    \"idle-seeding-limit\": 30, ";
	$f[]="    \"idle-seeding-limit-enabled\": false, ";
	$f[]="    \"incomplete-dir\": \"/root/Downloads\", ";
	$f[]="    \"incomplete-dir-enabled\": false, ";
	$f[]="    \"lpd-enabled\": false, ";
	$f[]="    \"max-peers-global\": 200, ";
	$f[]="    \"message-level\": 2, ";
	$f[]="    \"peer-congestion-algorithm\": \"\", ";
	$f[]="    \"peer-limit-global\": 240, ";
	$f[]="    \"peer-limit-per-torrent\": 60, ";
	$f[]="    \"peer-port\": 51413, ";
	$f[]="    \"peer-port-random-high\": 65535, ";
	$f[]="    \"peer-port-random-low\": 49152, ";
	$f[]="    \"peer-port-random-on-start\": false, ";
	$f[]="    \"peer-socket-tos\": \"default\", ";
	$f[]="    \"pex-enabled\": true, ";
	$f[]="    \"port-forwarding-enabled\": false, ";
	$f[]="    \"preallocation\": 1, ";
	$f[]="    \"prefetch-enabled\": 1, ";
	$f[]="    \"queue-stalled-enabled\": true, ";
	$f[]="    \"queue-stalled-minutes\": 30, ";
	$f[]="    \"ratio-limit\": 2, ";
	$f[]="    \"ratio-limit-enabled\": false, ";
	$f[]="    \"rename-partial-files\": true, ";
	$f[]="    \"rpc-authentication-required\": true, ";
	$f[]="    \"rpc-bind-address\": \"$TransMissionDaemonListen\", ";
	$f[]="    \"rpc-enabled\": true, ";
	$f[]="    \"rpc-password\": \"$ldap->ldap_password\", ";
	$f[]="    \"rpc-port\": $TransMissionDaemonPort, ";
	$f[]="    \"rpc-url\": \"/\", ";
	$f[]="    \"rpc-username\": \"$ldap->ldap_admin\", ";
	$f[]="    \"rpc-whitelist\": \"\", ";
	$f[]="    \"rpc-whitelist-enabled\": false, ";
	$f[]="    \"scrape-paused-torrents-enabled\": true, ";
	$f[]="    \"script-torrent-done-enabled\": false, ";
	$f[]="    \"script-torrent-done-filename\": \"\", ";
	$f[]="    \"seed-queue-enabled\": false, ";
	$f[]="    \"seed-queue-size\": 10, ";
	$f[]="    \"speed-limit-down\": 100, ";
	$f[]="    \"speed-limit-down-enabled\": false, ";
	$f[]="    \"speed-limit-up\": 100, ";
	$f[]="    \"speed-limit-up-enabled\": false, ";
	$f[]="    \"start-added-torrents\": true, ";
	$f[]="    \"trash-original-torrent-files\": false, ";
	$f[]="    \"umask\": 18, ";
	$f[]="    \"upload-limit\": 100, ";
	$f[]="    \"upload-limit-enabled\": 0, ";
	$f[]="    \"upload-slots-per-torrent\": 14, ";
	$f[]="    \"utp-enabled\": true";
	$f[]="}";
	$f[]="";	
	
	echo "\nConfiguring bittorrent daemon /etc/transmission-daemon/settings.json DONE.\n";
	@file_put_contents("/etc/transmission-daemon/settings.json", @implode("\n", $f));
	@chown("/etc/transmission-daemon/settings.json", "debian-transmission");
	@chgrp("/etc/transmission-daemon/settings.json", "debian-transmission");
	
}