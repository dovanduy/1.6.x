<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if($argv[1]=="--build"){build();die();}


function build(){
	$unix=new unix();
	$user=new settings_inc();
	$sock=new sockets();
	$suricataEnabled=$sock->GET_INFO("suricataEnabled");
	$suricata_bin=$unix->find_program("suricata");
	if(!is_file($suricata_bin)){
		echo "Starting......: Suricata is not installed...\n";
		return;
	}
	
	$oinkmaster=$unix->find_program("oinkmaster");
	if(!is_file($oinkmaster)){
		echo "Starting......: oinkmaster is not installed...\n";
		return;	
	}
	
	if(!is_dir("/etc/suricata/rules")){@mkdir("/etc/suricata/rules");}
	
$f[]="url =  http://rules.emergingthreats.net/open/suricata/emerging.rules.tar.gz";
$f[]="path = /sbin:/usr/sbin:/bin:/usr/bin";
$f[]="use_external_bins = 1";
$f[]="tmpdir = /var/run/oinkmaster";
$f[]="umask = 0027";
$f[]="update_files = \.rules\$|\.config\$|\.conf\$|\.txt\$|\.map\$";
$f[]="use_path_checks = 1";
$f[]="skipfile local.rules";
$f[]="skipfile deleted.rules";
$f[]="skipfile snort.conf";
@file_put_contents("/etc/oinkmaster.conf", @implode("\n", $f));
echo "Starting......: Suricata settings oinkmaster.conf done\n";

	$files=$unix->DirFiles("/etc/suricata/rules","\.rules$");
	if(count($files)==0){
		shell_exec("$oinkmaster -C /etc/oinkmaster.conf -o /etc/suricata/rules");
	}

	@file_put_contents("/etc/suricata/suricata-artica.yaml", build_yaml());
	echo "Starting......: Suricata settings suricata-artica.yaml done\n";
}


function build_yaml(){
	$unix=new unix();
	$f[]="%YAML 1.1";
	$f[]="action-order:";
	$f[]="  - pass";
	$f[]="  - drop";
	$f[]="  - reject";
	$f[]="  - alert";
	$f[]="";
	$f[]="default-log-dir: /var/log/suricata";
	$f[]="outputs:";
	$f[]="";
	$f[]="  # a line based alerts log similar to Snort's fast.log";
	$f[]="  - fast:";
	$f[]="      enabled: yes";
	$f[]="      filename: fast.log";
	$f[]="";
	$f[]="  # log output for use with Barnyard";
	$f[]="  - unified-log:";
	$f[]="      enabled: no";
	$f[]="      filename: unified.log";
	$f[]="";
	$f[]="      # Limit in MB.";
	$f[]="      #limit: 32";
	$f[]="";
	$f[]="  # alert output for use with Barnyard";
	$f[]="  - unified-alert:";
	$f[]="      enabled: no";
	$f[]="      filename: unified.alert";
	$f[]="";
	$f[]="      # Limit in MB.";
	$f[]="      #limit: 32";
	$f[]="";
	$f[]="  # alert output for use with Barnyard2";
	$f[]="  - unified2-alert:";
	$f[]="      enabled: yes";
	$f[]="      filename: unified2.alert";
	$f[]="";
	$f[]="      # Limit in MB.";
	$f[]="      #limit: 32";
	$f[]="";
	$f[]="  # a line based log of HTTP requests (no alerts)";
	$f[]="  - http-log:";
	$f[]="      enabled: yes";
	$f[]="      filename: http.log";
	$f[]="";
	$f[]="  # a full alerts log containing much information for signature writers";
	$f[]="  # or for investigating suspected false positives.";
	$f[]="  - alert-debug:";
	$f[]="      enabled: no";
	$f[]="      filename: alert-debug.log";
	$f[]="";
	$f[]="  # alert output to prelude (http://www.prelude-technologies.com/) only";
	$f[]="  # available if Suricata has been compiled with --enable-prelude";
	$f[]="  - alert-prelude:";
	$f[]="      enabled: no";
	$f[]="      profile: suricata";
	$f[]="";
	$f[]="defrag:";
	$f[]="  max-frags: 65535";
	$f[]="  prealloc: yes";
	$f[]="  timeout: 60";
	$f[]="";
	$f[]="";
	$f[]="detect-engine:";
	$f[]="  - profile: medium";
	$f[]="  - custom-values:";
	$f[]="      toclient_src_groups: 2";
	$f[]="      toclient_dst_groups: 2";
	$f[]="      toclient_sp_groups: 2";
	$f[]="      toclient_dp_groups: 3";
	$f[]="      toserver_src_groups: 2";
	$f[]="      toserver_dst_groups: 4";
	$f[]="      toserver_sp_groups: 2";
	$f[]="      toserver_dp_groups: 25";
	$f[]="";
	$f[]="";
	$f[]="threading:";
	$f[]="  set_cpu_affinity: no";
	$f[]="  detect_thread_ratio: 1.5";
	$f[]="";
	$f[]="cuda:";
	$f[]="  device_id: 0";
	$f[]="  mpm-algo: b2g";
	$f[]="";
	$f[]="pattern-matcher:";
	$f[]="  - b2g:";
	$f[]="      scan_algo: B2gScanBNDMq";
	$f[]="      search_algo: B2gSearchBNDMq";
	$f[]="      hash_size: low";
	$f[]="      bf_size: medium";
	$f[]="  - b3g:";
	$f[]="      scan_algo: B3gScanBNDMq";
	$f[]="      search_algo: B3gSearchBNDMq";
	$f[]="      hash_size: low";
	$f[]="      bf_size: medium";
	$f[]="  - wumanber:";
	$f[]="      hash_size: low";
	$f[]="      bf_size: medium";
	$f[]="";
	$f[]="flow:";
	$f[]="  memcap: 33554432";
	$f[]="  hash_size: 65536";
	$f[]="  prealloc: 10000";
	$f[]="  emergency_recovery: 30";
	$f[]="  prune_flows: 5";
	$f[]="";
	$f[]="flow-timeouts:";
	$f[]="";
	$f[]="  default:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    closed: 0";
	$f[]="    emergency_new: 10";
	$f[]="    emergency_established: 100";
	$f[]="    emergency_closed: 0";
	$f[]="  tcp:";
	$f[]="    new: 60";
	$f[]="    established: 3600";
	$f[]="    closed: 120";
	$f[]="    emergency_new: 10";
	$f[]="    emergency_established: 300";
	$f[]="    emergency_closed: 20";
	$f[]="  udp:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    emergency_new: 10";
	$f[]="    emergency_established: 100";
	$f[]="  icmp:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    emergency_new: 10";
	$f[]="    emergency_established: 100";
	$f[]="stream:";
	$f[]="  memcap: 33554432";
	$f[]="  checksum_validation: yes";
	$f[]="  reassembly:";
	$f[]="    memcap: 67108864";
	$f[]="    depth: 1048576";
	$f[]="";
	$f[]="logging:";
	$f[]="  default-log-level: info";
	$f[]="  #default-log-format: \"[%i] %t - (%f:%l) <%d> (%n) -- \"";
	$f[]="  default-output-filter:";
	$f[]="";
	$f[]="  outputs:";
	$f[]="  - console:";
	$f[]="      enabled: yes";
	$f[]="  - file:";
	$f[]="      enabled: no";
	$f[]="      filename: /var/log/suricata.log";
	$f[]="  - syslog:";
	$f[]="      enabled: no";
	$f[]="      facility: local5";
	$f[]="      format: \"[%i] <%d> -- \"";
	$f[]="";
	$f[]="";
	$f[]="pfring:";
	$f[]="  interface: eth0";
	$f[]="  cluster-id: 99";
	$f[]="  cluster-type: cluster_round_robin";
	$f[]="";
	$f[]="ipfw:";
	$f[]="";
	$f[]="default-rule-path: /etc/suricata/rules";
	$f[]="rule-files:";
	$files=$unix->DirFiles("/etc/suricata/rules","\.rules$");
	while (list ($file, $pp) = each ($files) ){
		$f[]=" - $file";
	}
	$f[]="";
	$f[]="classification-file: /etc/suricata/rules/classification.config";
	$f[]="reference-config-file: /etc/suricata/rules/reference.config";
	$f[]="";
	$f[]="vars:";
	$f[]="  address-groups:";
	$f[]="    HOME_NET: \"[192.168.0.0/16,10.0.0.0/8,172.16.0.0/12]\"";
	$f[]="    EXTERNAL_NET: any";
	$f[]="    HTTP_SERVERS: \"\$HOME_NET\"";
	$f[]="    SMTP_SERVERS: \"\$HOME_NET\"";
	$f[]="    SQL_SERVERS: \"\$HOME_NET\"";
	$f[]="    DNS_SERVERS: \"\$HOME_NET\"";
	$f[]="    TELNET_SERVERS: \"\$HOME_NET\"";
	$f[]="    AIM_SERVERS: any";
	$f[]="";
	$f[]="  port-groups:";
	$f[]="    HTTP_PORTS: \"80\"";
	$f[]="    SHELLCODE_PORTS: \"!80\"";
	$f[]="    ORACLE_PORTS: 1521";
	$f[]="    SSH_PORTS: 22";
	$f[]="";
	$f[]="host-os-policy:";
	$f[]="  windows: [0.0.0.0/0]";
	$f[]="  bsd: []";
	$f[]="  bsd_right: []";
	$f[]="  old_linux: []";
	$f[]="  linux: [10.0.0.0/8, 192.168.1.100, \"8762:2352:6241:7245:E000:0000:0000:0000\"]";
	$f[]="  old_solaris: []";
	$f[]="  solaris: [\"::1\"]";
	$f[]="  hpux10: []";
	$f[]="  hpux11: []";
	$f[]="  irix: []";
	$f[]="  macos: []";
	$f[]="  vista: []";
	$f[]="  windows2k3: []";
	$f[]="";
	$f[]="libhtp:";
	$f[]="";
	$f[]="   default-config:";
	$f[]="     personality: IDS";
	$f[]="";
	$f[]="   server-config:";
	$f[]="";
	$f[]="     - apache:";
	$f[]="         address: [192.168.1.0/24, 127.0.0.0/8, \"::1\"]";
	$f[]="         personality: Apache_2_2";
	$f[]="";
	$f[]="     - iis7:";
	$f[]="         address:";
	$f[]="           - 192.168.0.0/24";
	$f[]="           - 192.168.10.0/24";
	$f[]="         personality: IIS_7_0";
	$f[]="";
	$f[]="profiling:";
	$f[]="";
	$f[]="  rules:";
	$f[]="    enabled: yes";
	$f[]="    sort: avgticks";
	$f[]="    limit: 100";
	$f[]="";	
return @implode("\n", $f);
}