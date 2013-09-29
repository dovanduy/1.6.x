<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework(@implode(" - ",$a),__FUNCTION__,__FILE__,__LINE__);
if(isset($_GET["NetworkManager-check-redhat"])){NetworkManager_redhat();exit;}
if(isset($_GET["reconfigure-postfix-instances"])){postfix_reconfigures_multiples_instances();exit;}
if(isset($_GET["ping"])){pinghost();exit;}
if(isset($_GET["crossroads-restart"])){crossroads_restart();exit;}
if(isset($_GET["ipv6"])){ipv6();exit;}
if(isset($_GET["OpenVPNServerLogs"])){OpenVPN_ServerLogs();exit;}
if(isset($_GET["ipdeny"])){ipdeny();exit;}
if(isset($_GET["fw-inbound-rules"])){iptables_inbound();exit;}
if(isset($_GET["fqdn"])){fqdn();exit;}
if(isset($_GET["iptaccount-installed"])){iptaccount_check();exit;}
if(isset($_GET["ifup-ifdown"])){ifup_ifdown();exit;}
if(isset($_GET["reconstruct-interface"])){reconstruct_interface();exit;}
if(isset($_GET["reconstruct-all-interfaces"])){reconstruct_all_interfaces();exit;}
if(isset($_GET["arp-delete"])){arptable_delete();exit;}
if(isset($_GET["arp-edit"])){arptable_edit();exit;}
if(isset($_GET["ifconfig"])){ifconfig();exit;}
if(isset($_GET["ifconfig6"])){ifconfig6();exit;}
if(isset($_GET["vde-restart"])){vde_restart();exit;}
if(isset($_GET["vde-status"])){vde_status();exit;}


if(isset($_GET["dhcpd-leases"])){dhcpd_leases_force();exit;}
if(isset($_GET["dhcpd-leases-script"])){dhcpd_leases_script();exit;}




while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework("unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function NetworkManager_redhat(){
	$unix=new unix();
	$chkconfig=$unix->find_program("chkconfig");
	if(!is_file($chkconfig)){return;}
	exec("$chkconfig --list NetworkManager 2>&1",$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
	
	
}

function iptaccount_check(){
	$unix=new unix();
	$iptaccount=$unix->find_program("iptaccount");
	if(!is_file($iptaccount)){echo "<articadatascgi>FALSE</articadatascgi>";return;}
	exec("$iptaccount -a 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#failed: Can't get table names from kernel#", $ligne)){
			echo "<articadatascgi>FALSE</articadatascgi>";return;
		}
	}
	echo "<articadatascgi>TRUE</articadatascgi>";return;
}

function fqdn(){
	$unix=new unix();
	$hostname=$unix->FULL_HOSTNAME();
	echo "<articadatascgi>". base64_encode($hostname)."</articadatascgi>";
}

function postfix_reconfigures_multiples_instances(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --postfix-instances >/dev/null 2>&1 &"));

}

function ifconfig(){
	$net=$_GET["ifconfig"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig $net 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function ifconfig6(){
	$net=$_GET["ifconfig6"];
	$unix=new unix();
	$ip=$unix->find_program("ip");	
	$cmd="$ip -6 address show $net 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	$array=array();
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#inet6\s+(.*?)\s+scope#", $ligne,$re)){
			$array[$re[1]]=$re[1];
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function pinghost(){
	$host=$_GET["ping"];
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
}


	
function ifup_ifdown(){
	$eth=$_GET["ifup-ifdown"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --ifupifdown $eth >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function crossroads_restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.crossroads.php --multiples-restart >/dev/null 2>&1 &"));	
	
}
function dhcpd_leases_force(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.dhcpd-leases.php --force >/dev/null 2>&1 &"));		

}

function dhcpd_leases_script(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "<articadatascgi>". base64_encode(serialize(array($pid,$time)))."</articadatascgi>";
	}

}

function ipv6(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --ipv6 >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function OpenVPN_ServerLogs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tail=$unix->find_program("tail");
	$cmd=trim("$tail -n 300 /var/log/openvpn/openvpn.log 2>&1 ");
	
	exec($cmd,$results);		
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function ipdeny(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.postfix.iptables.php --ipdeny >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function iptables_inbound(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.postfix.iptables.php --perso >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function reconstruct_interface(){
	$eth=$_GET["reconstruct-interface"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --reconstruct-interface $eth --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function reconstruct_all_interfaces(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function arptable_edit(){
	$unix=new unix();
	$datas=unserialize(base64_decode($_GET["arp-edit"]));
	if(!is_array($datas)){
		writelogs_framework("Not an array",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$datas["ARP_IP"];
	$mac=$datas["ARP_MAC"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
			
	$cmd="$arpbin -s $host $mac";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);}
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function arptable_delete(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$_GET["arp-delete"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function vde_restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/vde.status.html";
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php --vde-switch >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@unlink($cachefile);
	@touch($cachefile);
	@chmod(0755,$cachefile);
	$cmd="$nohup /etc/init.d/vde_switch restart >$cachefile 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function vde_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.initslapd.php --vde-switch >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
	$cmd=trim("$php /usr/share/artica-postfix/exec.vde.php --status >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

