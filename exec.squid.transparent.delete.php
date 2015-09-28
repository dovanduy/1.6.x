<?php
ini_set('display_errors', 1);	
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if(isset($argv[1])){
	if($argv[1]=="--wccp"){wccp_delete();exit;}
	if($argv[1]=="--mikrotik"){MikrotikRemoveIpaddr();MikrotikRemoveIptables();exit;}
	if($argv[1]=="--mikrotik"){MikrotikRemoveIpaddr();MikrotikRemoveIptables();exit;}
	if($argv[1]=="--parent"){SquidParentRemove();MikrotikRemoveIptables();exit;}
}


echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing iptables rules...\n";
$iptables_save=find_program("iptables-save");
$iptables_restore=find_program("iptables-restore");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");

$datas=explode("\n",$data);
$pattern="#.+?ArticaSquidTransparent#";
$pattern2="#.+?ArticaWCCPL3Transparent#";
$SquidWCCPL3Enabled=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidWCCPL3Enabled")));



$d=0;
$conf=null;
while (list ($num, $ligne) = each ($datas) ){
	if($ligne==null){continue;}
	if(preg_match($pattern,$ligne)){$d++;continue;}
	if($SquidWCCPL3Enabled==0){ if(preg_match($pattern2,$ligne)){$d++;continue;} }
	$conf=$conf . $ligne."\n";
}
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing $d iptables rule(s) done...\n";



function wccp_delete(){
	$d=0;
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaWCCPL3Transparent#";
	$iptables_save=find_program("iptables-save");
	$iptables_restore=find_program("iptables-restore");	
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){$d++;continue;}
		
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	echo "Starting......: ".date("H:i:s")." Squid Check WCCP mode: removing $d iptables rule(s) done...\n";
	
}
function MikrotikRemoveIpaddr(){
	$ip=find_program("ip");
	exec("$ip addr show 2>&1",$results);
	echo "Starting......: ".date("H:i:s")." IP BIN........: $ip\n";
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#inet\s+([0-9\.]+)\/([0-9]+).*?scope global\s+(.+?):mikrotik#", $ligne,$re)){continue;}
		echo "Starting......: ".date("H:i:s")." Squid Check MikroTik mode: removing {$re[1]}/{$re[2]} interface\n";
		shell_exec("$ip addr del {$re[1]}/{$re[2]} dev {$re[3]}");
		break;

	}
	echo "Starting......: ".date("H:i:s")." Mikrotik virtual ip done\n";

}


function MikrotikRemoveIptables(){
	$iptables_save=find_program("iptables-save");
	$iptables_restore=find_program("iptables-restore");
	$conf=null;
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaMikroTikTransparent#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	echo "Starting......: ".date("H:i:s")." Squid Check MikroTik mode: removing $d iptables rule(s) done...\n";


}
function SquidParentRemove(){
	$iptables_save=find_program("iptables-save");
	$iptables_restore=find_program("iptables-restore");
	$conf=null;
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaSquidChilds#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	echo "Starting......: ".date("H:i:s")." Squid Check Parent mode: removing $d iptables rule(s) done...\n";
	
	
}


function find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
			'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
	} else {
		return strpos($strProgram, '.exe');
	}
}