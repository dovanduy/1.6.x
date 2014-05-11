<?php
echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing iptables rules...\n";
$iptables_save=find_program("iptables-save");
$iptables_restore=find_program("iptables-restore");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaSquidTransparent#";
$d=0;
while (list ($num, $ligne) = each ($datas) ){
	if($ligne==null){continue;}
	if(preg_match($pattern,$ligne)){$d++;continue;}
	$conf=$conf . $ligne."\n";
}
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing $d iptables rule(s) done...\n";




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