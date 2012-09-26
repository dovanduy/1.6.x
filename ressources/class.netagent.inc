<?php
define('PROCESS1_BIN',   "/usr/share/artica-postfix/bin/process1");
include_once(dirname(__FILE__)."/class.ccurl.inc");

class netagent{
	var $master_uri=null;
	var $nodeid=0;
	
	function netagent(){
		
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKET"]->GET_INFO("RemoteStatisticsApplianceSettings")));
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$proto="http://";
		if($RemoteStatisticsApplianceSettings["SSL"]==1){$proto="https://";}
		$this->master_uri="$proto{$RemoteStatisticsApplianceSettings["SERVER"]}:{$RemoteStatisticsApplianceSettings["PORT"]}";	
		$this->nodeid=$GLOBALS["CLASS_SOCKET"]->GET_INFO("NODE_ID");
		
	}
	
	function ping($norestart=false){
		if($this->nodeid<1){
			if(!$norestart){$this->_register();}
		}
		$this->sendstatus();
		
		
	}
	
	
	
	private function _register(){
		writelogs("[$this->nodeid]: master_uri `$this->master_uri`",__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		if(is_numeric($this->nodeid) && $this->nodeid>0){
			$curl->parms["nodeid"]=$this->nodeid;
		}
		$hostid=$this->hostid();
		echo "Register to $this->master_uri with hostid `$hostid`\n";
		$curl->parms["REGISTER"]="yes";
		$curl->parms["nets"]=$this->GetNets();
		$curl->parms["hostname"]=exec("hostname -f");
		$curl->parms["port"]=0;
		$curl->parms["hostid"]=$this->hostid();
		$curl->parms["ISARTICA"]=1;
		if(!$curl->get()){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}
		if(preg_match("#<ERROR>(.+?)</ERROR>#is", $curl->data,$re)){
			echo "FATAL ERROR ".$re[1]."\n";
			return;
		}
		if(preg_match("#<SUCCESS>([0-9]+)</SUCCESS>#is", $curl->data,$re)){
			writelogs("SUCCESS NODE ID ".$re[1],__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_SOCKET"]->SET_INFO("NODE_ID",$re[1]);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/NODE_ID", $re[1]);
			$this->ping(true);
		}
		
	}
	
	private function hostname(){$unix=new unix();return exec($unix->find_program("hostname"));}
	
	public function Architecture(){
		$uname=$GLOBALS["CLASS_UNIX"]->find_program("uname");
		exec("$uname -m 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#i[0-9]86#", $val)){return 32;}
			if(preg_match("#x86_64#", $val)){return 64;}
		}
	}
	
	
	public function compress($source,$dest){
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}	

	
	
	private function perfs($CPU_NUMBER){
		if(!class_exists("os_system")){include_once(dirname(__FILE__)."/class.os.system.tools.inc");}
		$mem=new os_system();
		$cpunum=intval($CPU_NUMBER);
		$array_load=sys_getloadavg();
		$org_load=$array_load[2];
		$load=intval($org_load);
		$max_vert_fonce=$cpunum;
		$max_vert_tfonce=$cpunum+1;
		$max_orange=$cpunum*0.75;
		$max_over=$cpunum*2;
		$purc1=$load/$cpunum;
		$pourc=round($purc1*100,2);
		if($pourc>100){$pourc=100;}
		$color="#5DD13D";
		if($load>=$max_orange){$color="#F59C44";}
		if($load>$max_vert_fonce){$color="#C5792D";}
		if($load>$max_vert_tfonce){$color="#83501F";}	
		if($load>=$max_over){$color="#640000";}
		$array["LOAD_POURC"]=$pourc;
		$array["LOAD_COLOR"]=$color;
		$array["MEMORY"]=$mem->memory();
		$array["REALMEM"]=$mem->realMemory();
		writelogs("[$this->nodeid]: Load:$pourc color:$color",__FUNCTION__,__FILE__,__LINE__);
		return base64_encode(serialize($array));
	}	
	
	
	private function sendstatus(){
		include(dirname(__FILE__)."/settings.inc");
		$sock=new sockets();
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
		$hostname=$this->hostname();
		$rm=$GLOBALS["CLASS_UNIX"]->find_program("rm");
		$values=base64_encode(serialize($_GLOBAL));
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curlparms["SETTINGS_INC"]=$values;
		$curlparms["nodeid"]=$this->nodeid;
		$curlparms["VERSION"]=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
		$curlparms["perfs"]=$this->perfs($_GLOBAL["CPU_NUMBER"]);
		$curlparms["softwares"]=$this->SoftwaresStats();
		$curlparms["nets"]=$this->GetNets();
		$curlparms["hostname"]=$hostname;
		$curlparms["prodstatus"]=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini"));
		$curlparms["squid_caches_info"]=$this->squid_caches_infos();
		$curlparms["disks_list"]=base64_encode(serialize($this->GetDiskList()));
		$curlparms["hostid"]=$this->hostid();
		$curlparms["CACHE_LOGS"]=$this->cache_logs();
		$curlparms["SQUIDVER"]=$GLOBALS["CLASS_SOCKET"]->getFrameWork("squid.php?full-version=yes");
		$curlparms["ARCH"]=$this->Architecture();
		$curlparms["ArticaHttpsPort"]=$ArticaHttpsPort;
		
		$curlparms["ISARTICA"]=1;
		$curlparms["PARMS"]=$this->parse_daemon_settings();
		
		if(is_file("/etc/squid3/squid.conf")){
			$curlparms["ETC_SQUID_CONF"]=base64_encode(@file_get_contents("/etc/squid3/squid.conf"));
		}
		
		
		@file_put_contents(dirname(__FILE__)."/com.txt", base64_encode(serialize($curlparms)));
		$this->compress(dirname(__FILE__)."/com.txt",dirname(__FILE__)."/com.txt.gz");
		writelogs("[$this->nodeid]: ". count($_GLOBAL)." items Softs:".round(strlen($curlparms["softwares"])/1024) ."Ko",__FUNCTION__,__FILE__,__LINE__);
		
		if(!$curl->postFile("SETTINGS_INC",dirname(__FILE__)."/com.txt.gz",array("HOSTNAME"=>$hostname,"nodeid"=>$this->nodeid))){
			writelogs("[$this->nodeid]: Failed $curl->error",__FUNCTION__,__FILE__,__LINE__);
			
		}
		@unlink(dirname(__FILE__)."/com.txt");
		@unlink(dirname(__FILE__)."/com.gz");
		shell_exec("$rm -rf /tmp/process1-*");
		if(preg_match("#<ORDERS>(.+?)</ORDERS>#is", $curl->data,$re)){
			$this->OrdersParse($re[1]);
		}
		
		
	}
	
	private function squid_caches_infos(){
		return base64_encode(serialize($GLOBALS["CLASS_UNIX"]->squid_get_cache_infos()));
	}
	
	private function OrdersParse($EncodedData){
		$data=unserialize(base64_decode($EncodedData));
		while (list ($num, $array) = each ($data) ){
			$orderid=$array["orderid"];
			$orderdata=unserialize(base64_decode($array["orderdata"]));
			$OrderOK=false;
			while (list ($OrderKey, $OrderValue) = each ($orderdata) ){
				if($this->OrderExecute($OrderKey,$OrderValue)){
					$OrderOK=true;
				}
			}
			
			if($OrderOK){$this->OrderOk($orderid);}
			
		}
	}
	
	private function OrderExecute($ORDER,$ORDER_VALUE){
		if($ORDER=="SQUID"){$this->squid_get_confs();return true;}
		
	}
	
	private function OrderOk($orderid){
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curl->parms["nodeid"]=$this->nodeid;
		$curl->parms["hostid"]=$this->hostid();
		$curl->parms["orderid"]=$orderid;
		if(!$curl->get()){writelogs("[$this->nodeid]: orderid=$orderid: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}
	}
	

	private function GetDiskList(){
		$dbPath=dirname(__FILE__)."/usb.scan.inc";
		$unix=new unix();
		$timeFile=$unix->file_time_min($dbPath);
		if($timeFile>30){@unlink($dbPath);}
		if(!is_file($dbPath)){shell_exec(PROCESS1_BIN." --disk-scan");}
		if(!is_file($dbPath)){return;}
		include($dbPath);
		return array("DISKS"=>$_GLOBAL["disks_list"],"USB"=>$_GLOBAL["usb_list"]);
	}	
	
	private function cache_logs(){
		$tail=$GLOBALS["CLASS_UNIX"]->find_program("tail");
		exec("$tail -n 1500 /var/log/squid/cache.log 2>&1",$results);
		return base64_encode(serialize($results));
	}

	public function squid_get_confs(){
		$MASTERURI=$this->master_uri."/ressources/logs/web/squid/$this->nodeid";
		$unix=new unix();
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curl->parms["nodeid"]=$this->nodeid;
		$curl->parms["SQUIDCONF"]="TRUE";
		
		if(!$curl->get()){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}
		writelogs("[$this->nodeid]: save datas to /etc/squid3/squid.conf.gz",__FUNCTION__,__FILE__,__LINE__);
		
		$curl=new ccurl("$MASTERURI/squid.conf.gz");
		if(!$curl->GetFile("/etc/squid3/squid.conf.gz")){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		@mkdir("/etc/squid3",0755,true);
		$this->uncompress("/etc/squid3/squid.conf.gz","/etc/squid3/squid.conf");
		
		$curl=new ccurl("$MASTERURI/squid.db.gz");
		if(!$curl->GetFile("/etc/squid3/squid.db.gz")){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/squid.db.gz","/etc/squid3/squid.db");
		
		$curl=new ccurl("$MASTERURI/squid-block.acl.gz");
		if(!$curl->GetFile("/etc/squid3/squid-block.acl.gz")){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/squid-block.acl.gz","/etc/squid3/squid-block.acl");

		$curl=new ccurl("$MASTERURI/DaemonSettings.conf.gz");
		if(!$curl->GetFile("/etc/squid3/DaemonSettings.conf.gz")){writelogs("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/DaemonSettings.conf.gz","/etc/squid3/DaemonSettings.conf");		
		
		$array=unserialize(base64_decode(@file_get_contents("/etc/squid3/DaemonSettings.conf")));
		if(is_array($array)){
			while (list ($key, $value) = each ($array) ){
				$value=base64_decode($value);
				$GLOBALS["CLASS_SOCKET"]->SET_INFO($key,$value);
			}
		}else{
			writelogs("[$this->nodeid]: /etc/squid3/DaemonSettings.conf not an array",__FUNCTION__,__FILE__,__LINE__);
		}
		
		
		$array=unserialize(base64_decode(@file_get_contents("/etc/squid3/squid.db")));
		while (list ($key, $value) = each ($array) ){
			$value=base64_decode($value);
			writelogs("[$this->nodeid]: Set $key = $value",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_SOCKET"]->SET_INFO($key,$value);
		}		
		
		$chown=$unix->find_program("chown");
		shell_exec("$chown squid:squid /etc/squid3 >/dev/null 2>&1");
		$cmd="/etc/init.d/artica-postfix restart squid-cache";
		writelogs("[$this->nodeid]: reloading squid",__FUNCTION__,__FILE__,__LINE__);
		writelogs("[$this->nodeid]: $cmd",__FUNCTION__,__FILE__,__LINE__);	
		$squidbin=$unix->find_program("squid");
		if(strlen($squidbin)<3){$squidbin=$unix->find_program("squid3");}
		
		@mkdir("/var/log/squid",0755,true);
		@chown("/var/log/squid", "squid");
		@chgrp("/var/log/squid", "squid");
		@chown("/var/log/squid/cache.log", "squid");
		@chgrp("/var/log/squid/cache.log", "squid");
		@chmod("/var/log/squid/cache.log", 0755);		
		
		
		if(is_file($squidbin)){shell_exec("$squidbin -k reconfigure");}
		if(is_file("/usr/share/artica-postfix/exec.squid.transparent.php")){shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.transparent.php");}
		
	}	
	
	public function uncompress($srcName, $dstName) {
	    $sfp = gzopen($srcName, "rb");
	    $fp = fopen($dstName, "w");
	    while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	    gzclose($sfp);
	    fclose($fp);
	}

	private function parse_daemon_settings(){
		writelogs("[$this->nodeid]: Scanning /etc/artica-postfix/settings/Daemons",__FUNCTION__,__FILE__,__LINE__);
		foreach (glob("/etc/artica-postfix/settings/Daemons/*") as $filename) {
			$key=basename($filename);
			$datas=base64_encode(@file_get_contents($filename));
			$array[$key]=$datas;
			
		}
		
		return base64_encode(serialize($array));
		
	}
	

	private function GetNets(){
		exec(PROCESS1_BIN." --nickernel 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			$results1=array();
			exec(PROCESS1_BIN." --nicstatus $ligne 2>&1",$results1);
			$tbl=explode(";",$results1[0]);
			writelogs("Sending status $ligne IP: {$tbl[0]}",__FUNCTION__,__FILE__,__LINE__);
			$array[$ligne]=array(
				"IPADDR"=>$tbl[0],
				"MACADDR"=>$tbl[1],
				"NETMASK"=>$tbl[2],
				"BROADCAST"=>$tbl[3],
				"GATEWAY"=>$tbl[4],
			);

		}
		return base64_encode(serialize($array));
		
	}	
	
	private function SoftwaresStats(){
		$cachefile=dirname(__FILE__)."/softs.cache";
		$TimeSettings=$GLOBALS["CLASS_UNIX"]->file_time_min($cachefile);
		
		$f=explode("\n", @file_get_contents(dirname(__FILE__)."/versions.conf"));
		if($TimeSettings<300){
			$f[]=@file_get_contents($cachefile);
			return base64_encode(@implode("\n", $f));
			
		}
		$dpkg=$GLOBALS["CLASS_UNIX"]->find_program('dpkg');
		
		if(is_file($dpkg)){
				exec("$dpkg -l 2>&1",$results);
				while (list ($num, $ligne) = each ($results) ){if(preg_match("#^ii\s+(.+?)\s+\s+(.+?)\s+\s+(.*)#", $ligne,$re)){$s[]="[{$re[1]}: ({$re[3]})] \"{$re[2]}\"";}}
				@file_put_contents($cachefile, @implode("\n", $s));
		}
			
		
		$f[]=@file_get_contents($cachefile);
		return base64_encode(@implode("\n", $f));
	}
	
	
	private function hostid(){
		$hostid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HOSTID"));
		if($hostid<>null){return $hostid;}
			
		$unix=new unix();
		$hostidbin=$unix->find_program("hostid");
		if(is_file($hostidbin)){
			exec("$hostidbin 2>&1",$results);
			$hostid=trim(@implode("", $results));
			if($hostid<>"00000000"){
				@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
				return $hostid;
			}
		}
			
		$hostid=md5(time());
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
		return $hostid;
	}	
	
}