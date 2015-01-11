<?php


if($argv[1]=="--remove-nics"){removeall();exit;}

$iptables_save="/sbin/iptables-save";
$iptables_restore="/sbin/iptables-restore";
shell_exec("$iptables_save > /etc/artica-postfix/iptables-bridges.conf");
$data=file_get_contents("/etc/artica-postfix/iptables-bridges.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaNetworkBridges#";
$conf=array();
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf[]=$ligne;
	}
file_put_contents("/etc/artica-postfix/iptables-bridges.new.conf",@implode("\n", $conf));
shell_exec("$iptables_restore < /etc/artica-postfix/iptables-bridges.new.conf");
@unlink("/etc/artica-postfix/iptables-bridges.new.conf");
@unlink("/etc/artica-postfix/iptables-bridges.conf");
@unlink("/etc/artica-postfix/IPTABLES_BRIDGE");



function removeall(){
	
	exec("/sbin/brctl show 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if(!preg_match("#^br([0-9]+)#", $ligne,$re)){continue;}
		echo "Remove bridge br{$re[1]}";
		$eth="br{$re[1]}";
		shell_exec("/sbin/ifconfig br{$re[1]} down");
		shell_exec("/sbin/brctl delbr br{$re[1]}");
		
		
		
		
		
	}
	
	
}

?>
