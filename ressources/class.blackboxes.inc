<?php
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
include_once(dirname(__FILE__)."/class.ccurl.inc");
include_once(dirname(__FILE__)."/class.nodes.squid.inc");

class blackboxes{
	
	var $nodeid=0;
	var $hostname=null;
	var $ipaddress;
	var $port=9001;
	var $last_error;
	var $settings_inc=array();
	var $laststatus=null;
	var $VERSION=null;
	var $etcsquidconf=null;
	var $squid_version;
	var $Architecture=0;
	var $IsArtica=0;
	var $hostid=null;
	
	function blackboxes($nodeid=0){
		$this->nodeid=$nodeid;
		if(!is_numeric($this->nodeid)){$this->nodeid=0;return;}
		if($this->nodeid>0){$this->load();}
	}
	
	
	private function load(){
		$q=new mysql_blackbox();
		$sql="SELECT * FROM nodes WHERE nodeid=$this->nodeid";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$this->hostname=$ligne["hostname"];
		$this->ipaddress=$ligne["ipaddress"];
		$this->port=$ligne["port"];
		$this->settings_inc=unserialize(base64_decode($ligne["settingsinc"]));
		$this->laststatus=distanceOfTimeInWords(time(),strtotime($ligne["laststatus"]));
		$this->VERSION=$ligne["articaver"];
		$this->etcsquidconf=base64_decode($ligne["squidconf"]);
		$this->squid_version=$ligne["squidver"];
		$this->Architecture=$ligne["arch"];
		$this->IsArtica=$ligne["BigArtica"];
		$this->hostid=$ligne["hostid"];
		if(!$q->TABLE_EXISTS("pms$this->nodeid")){$q->build_cached_items_table($this->nodeid);}
	}
	
	public function chock(){
		$parms["REGISTER"]="yes";
		return $this->SendOrder($parms);
	}
	
	public function reboot(){
		$parms["REBOOT"]="yes";
		return $this->SendOrder($parms);		
		
	}
	
	public function updateorder(){
		$parms["UPDATE_AGENT"]="yes";
		return $this->SendOrder($parms);		
		
	}	
	
	public function updatesquid(){
		$parms["UPDATE_SQUID"]="yes";
		return $this->SendOrder($parms);			
	}
	
	public function restart_squid(){
		$parms["RESTART_SQUID"]="yes";
		return $this->SendOrder($parms);			
	}	
	
	public function ChangeHostname(){
		if($this->nodeid<1){return;}
		$q=new mysql_blackbox();	
		$q->QUERY_SQL("UPDATE nodes SET hostname='$this->hostname' WHERE  nodeid=$this->nodeid");
		$parms["CHHOSTANME"]=$this->hostname;
		return $this->SendOrder($parms);	
		
	}
	
	private function SendOrder($parms){
		if(($this->port<5) OR ($this->IsArtica==1)){
			return $this->OrderQueue($parms);
			
		}		
		$sock=new sockets();
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
		if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}	
		$curl=new ccurl("http://$this->ipaddress:$this->port/listener.php");		
		$curl->parms["SSL"]=$ArticaHttpUseSSL;
		$curl->parms["PORT"]=$ArticaHttpsPort;
		$curl->parms["NODE_ID"]=$this->nodeid;	
		while (list ($key, $arr) = each ($parms) ){
			writelogs("Asking $key  node:http://$this->ipaddress:$this->port",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$curl->parms[$key]=$arr;
		}
		if(!$curl->get()){$this->last_error="{$curl->error} http://$this->ipaddress:$this->port";return;}
		if(!preg_match("#<SUCCESS>#is", $curl->data)){$this->last_error="{failed} `http://$this->ipaddress:$this->port`";return;}	
		return true;			
		
	}
	
	private function OrderQueue($parms){
			$order=base64_encode(serialize($parms));
			$orderid=md5($order);
			$sql="INSERT IGNORE INTO `poolorders` (`hostid`,`nodeid`,`orderid`,`orderdata`) VALUES ('$this->hostid','$this->nodeid','$orderid','$order')";		
			$q=new mysql_blackbox();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return false;}
			return true;
	}
	
	
	public function SaveSettingsInc($EncodedDatas,$PerfsEncoded,$softsEncoded,$prodstatusEncoded,$IsArtica){
		if($this->nodeid<1){return;}
		if(!is_numeric($IsArtica)){$IsArtica=0;}
		$q=new mysql_blackbox();
		
		$sql="SELECT nodeid FROM nodes WHERE nodeid=$this->nodeid";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!is_numeric($ligne["nodeid"])){$ligne["nodeid"]=0;}
		if($ligne["nodeid"]==0){
			$q->QUERY_SQL("INSERT INTO nodes (nodeid,hostname,ipaddress,port,BigArtica) 
			VALUES ($this->nodeid,'$this->hostname','{$_SERVER["REMOTE_ADDR"]}','9001',$IsArtica)");
		}
		
		
		$sql="UPDATE nodes SET settingsinc='$EncodedDatas',
		`perfs`='$PerfsEncoded',
		`prodstatus`='$prodstatusEncoded',
		`articaver`='$this->VERSION',
		`BigArtica`=$IsArtica,
		laststatus=NOW() WHERE nodeid=$this->nodeid";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(preg_match("#Error Unknown column#", $q->mysql_error)){$q->CheckTables();$q->QUERY_SQL($sql);}
			if(!$q->ok){
				writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		$this->ParseSofts($softsEncoded);
		$this->ParseStatus($prodstatusEncoded);
		
	}
	
	public function NotifySquid(){
		return $this->NotifyNode("SQUID");
	}
	
	public function NotifyNode($ORDER){
		$parms[$ORDER]="yes";
		return $this->SendOrder($parms);	
			
	}	
	

	private function ParseStatus($prodstatusEncoded){
		$inidata=base64_decode($prodstatusEncoded);
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("nodesstatus")){$q->CheckTables();}
		$ini=new Bs_IniHandler();
		$ini->loadString($inidata);

		$prefix="INSERT IGNORE INTO nodesstatus (
		`nodeid`,
		`service_name`,`master_version`,`master_pid`,`master_memory`,`master_cached_memory`,
		`processes_number`,`running`,`installed`) VALUES ";
		
		while (list ($key, $arr) = each ($ini->_params) ){
			$ttlogs[]=$key;
			
			$f[]="('$this->nodeid',
			'{$arr["service_name"]}',
			'{$arr["master_version"]}','{$arr["master_pid"]}','{$arr["master_memory"]}','{$arr["master_cached_memory"]}',
			'{$arr["processes_number"]}','{$arr["running"]}','{$arr["installed"]}')";
			
		}
		writelogs(@implode(",", $ttlogs),__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(count($f)==0){return;}
		$q->QUERY_SQL("DELETE FROM nodesstatus WHERE nodeid='$this->nodeid'");	
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	
	public function SaveNets($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){writelogs("blackboxes::Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array)==0){writelogs("blackboxes::No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		$f=array();
		$q=new mysql_blackbox();
		while (list ($eth, $arr) = each ($array) ){
			$arr["MACADDR"]=strtolower($arr["MACADDR"]);
			$arr["MACADDR"]=str_replace("-",":",$arr["MACADDR"]);
			if($arr["MACADDR"]=="00:00:00:00:00:00"){$arr["MACADDR"]=$arr["MACADDR"]."-$this->nodeid";}
			writelogs("blackboxes:: {$arr["MACADDR"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("DELETE FROM nics WHERE `mac`='{$arr["MACADDR"]}'");
			$f[]="('$this->nodeid','$eth','{$arr["IPADDR"]}','{$arr["MACADDR"]}','{$arr["NETMASK"]}','{$arr["GATEWAY"]}','{$arr["BROADCAST"]}')";	
			
		}
		
		writelogs("blackboxes::".count($f)." items ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		if(count($f)>0){
			
			$q->QUERY_SQL("DELETE FROM nics WHERE nodeid='$this->nodeid'");	
			if(!$q->ok){writelogs("blackboxes::$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$prefix="INSERT IGNORE INTO nics (nodeid,nic,ipaddr,mac,netmask,gateway,broadcast) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($prefix);
			if(!$q->ok){writelogs("blackboxes::$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
		}
	}
	
	public function SaveDisks($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array["DISKS"])){writelogs("Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array["DISKS"])==0){writelogs("No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}		
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("harddrives")){$q->CheckTables();}
		if(!$q->TABLE_EXISTS("harddrives")){writelogs("No harddrives table... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		$DISK=$array["DISKS"];
		$prefix="INSERT IGNORE INTO harddrives (`nodeid` ,`path`,`size`,`ID_FS_LABEL`,`ID_MODEL_1`,`ID_MODEL_2`,`ID_VENDOR`,`PARTITIONS`) VALUES ";
		$f=array();
		while (list ($path, $infos) = each ($DISK)){
			$PARTITIONS=base64_encode(serialize($infos["PARTITIONS"]));
			if(preg_match("#([0-9]+)\s+MB#", $infos["SIZE"],$re)){$size=$re[1];}
			writelogs("$this->nodeid:$path:($size -> `{$infos["SIZE"]}`)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$f[]="($this->nodeid,'$path','$size','{$infos["ID_FS_LABEL"]}','{$infos["ID_MODEL_1"]}','{$infos["ID_MODEL_2"]}','{$infos["ID_VENDOR"]}','$PARTITIONS')";
		}
		
		if(count($f)==0){return;}
		$q->QUERY_SQL("DELETE FROM harddrives WHERE `nodeid`=$this->nodeid");
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
		
	}
	
	
	public function squid_save_cache_infos($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){return;}
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("cachestatus")){$q->CheckTables();}
		$uuid=$this->nodeid;
	
		$profix="INSERT IGNORE INTO cachestatus(nodeid,cachedir,maxsize,currentsize,pourc) VALUES ";
		while (list ($directory, $arrayDir) = each ($array)){
			$directory=trim($directory);
			if($directory==null){continue;}
			$f[]="('$uuid','$directory','{$arrayDir["MAX"]}','{$arrayDir["CURRENT"]}','{$arrayDir["POURC"]}')";
		}
		
		if(count($f)>0){
			$q->QUERY_SQL("DELETE FROM cachestatus WHERE nodeid='$uuid'");
			$q->QUERY_SQL("$profix".@implode(",", $f));
			if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		
	}
	
	public function squid_save_cachelogs($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){
			writelogs("Not an array",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;}
			
		if(count($array)==0){
			writelogs("No rows",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;				
		}	
		$q=new mysql_blackbox();
		$tt=array();
		$prefix="INSERT IGNORE INTO `cachelogs$this->nodeid`(`zDate`,`line`) VALUES ";
		while (list ($directory, $line) = each ($array)){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#^(.+)\s+([0-9:]+)\s+(.+)#", $line,$re)){
				$time=strtotime("{$re[1]} {$re[2]}");
				$zDate=date("Y-m-d H:i:s",$time);
				$line=$re[3];
			}
			$line=mysql_escape_string($line);
			$tt[]="('$zDate','$line')";
		}
		writelogs("Injecting ".count($tt)." lines",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(count($tt)>0){
			$q->QUERY_SQL("DROP TABLE `cachelogs$this->nodeid`");
			$q->BuildCachelogs($this->nodeid);
			$q->QUERY_SQL($prefix.@implode(",", $tt));
			if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
	}
	
	public function squid_save_etcconf($EncodedDatas){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET squidconf='$EncodedDatas' WHERE  nodeid=$this->nodeid";
		writelogs("/etc/squid3/squid.conf (virtual) ".strlen($EncodedDatas)." bytes",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(strpos($q->mysql_error, "column 'squidconf'")){$q->CheckTables();$q->QUERY_SQL($sql);}
		}
		if(!$q->ok){
			writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	function squid_save_squidver($ver){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET squidver='$ver' WHERE  nodeid=$this->nodeid";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){if(strpos($q->mysql_error, "column 'squidver'")){$q->CheckTables();$q->QUERY_SQL($sql);}}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
		
	}
	
	function SetArch($Arch){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET arch='$Arch' WHERE  nodeid=$this->nodeid";
		$q->QUERY_SQL($sql);
		if(!$q->ok){if(strpos($q->mysql_error, "column 'arch'")){$q->CheckTables();$q->QUERY_SQL($sql);}}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}			
		
	}
	
	
	function DaemonsSettings($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){return;}
		if(count($array)<3){return;}
		$q=new mysql_blackbox();
		$q->QUERY_SQL("TRUNCATE TABLE `pms$this->nodeid`");
		while (list ($key, $datas) = each ($array)){$f[]="('$key','$datas')";}
		$q->QUERY_SQL("INSERT IGNORE INTO pms$this->nodeid (zkey,`value`) VALUES ". @implode(",", $f));
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	public function DumpSettings(){
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `zkey`,`value` FROM `pms$this->nodeid`";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$array[$ligne["zkey"]]=$ligne["value"];
		}
		return $array;		
		
		
	}	
	
	function SET_INFO($key,$value){
		$q=new mysql_blackbox();
		$value=base64_encode($value);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zkey FROM pms$this->nodeid WHERE zkey='$key'"));	
		if(trim($ligne["zkey"])==null){
			$q->QUERY_SQL("INSERT IGNORE INTO pms$this->nodeid (zkey,`value`) VALUES ('$key','$value')"); 
		}else{
			$q->QUERY_SQL("UPDATE pms$this->nodeid SET `value`='$value' WHERE zkey='$key'");
		}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		echo $q->mysql_error;
	}
	
	function EchoOrders(){
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("poolorders")){$q->CheckTables();}
		
		$sql="SELECT * FROM poolorders WHERE hostid='$this->hostid'";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$t[]=array("orderid"=>$ligne["orderid"],"orderdata"=>$ligne["orderdata"]);
		}
		
		echo "\n<ORDERS>".base64_encode(serialize($t))."</ORDERS>\n";
		
	}
	
	
	
	public function delete_node(){
		$q=new mysql_blackbox();
		$q->QUERY_SQL("DELETE FROM softwares WHERE nodeid=$this->nodeid");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM nics WHERE nodeid=$this->nodeid");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM nodes WHERE nodeid=$this->nodeid");
		if(!$q->ok){echo $q->mysql_error;return;}			
		$q->QUERY_SQL("DELETE FROM squidconf WHERE nodeid=$this->nodeid");
		if(!$q->ok){echo $q->mysql_error;return;}	
		$q->QUERY_SQL("DELETE FROM cachestatus WHERE nodeid=$this->nodeid");
		if(!$q->ok){echo $q->mysql_error;return;}	
		if($q->TABLE_EXISTS("cachelogs$this->nodeid")){
			$q->QUERY_SQL("DROP TABLE cachelogs$this->nodeid");
			if(!$q->ok){echo $q->mysql_error;return;}	
		}
		if($q->TABLE_EXISTS("poolorders")){
			$q->QUERY_SQL("DELETE FROM poolorders WHERE nodeid=$this->nodeid");
		}
		
		if($q->TABLE_EXISTS("pms$this->nodeid")){
			$q->QUERY_SQL("DROP TABLE pms$this->nodeid");
			if(!$q->ok){echo $q->mysql_error;return;}
		}
		
		
		
	}
	
	function last_available_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#artica-agent-([0-9\.\-\_]+)\.[a\.-z]+$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	}
  	
	function last_available_squidx64_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#squid32-x64-([0-9\.\-\_a-z]+)\.tar\.gz$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	} 

	function last_available_squidx32_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#^squid32-i386-([0-9\.\-\_a-z]+)\.tar\.gz$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	}   	
	
	
	private function ParseSofts($softsEncoded){
		$softs=explode("\n",base64_decode($softsEncoded));
			while (list ($num, $val) = each ($softs) ){
				if(preg_match('#\[(.+?)\]\s+"(.+?)"#', $val,$re)){
					if($re[2]=="0"){continue;}
					if($re[2]=="."){continue;}
					if($re[2]=="0.0"){continue;}
					$re[1]=addslashes($re[1]);
					$re[2]=addslashes($re[2]);
					$f[]="('$this->nodeid','{$re[1]}','{$re[2]}')";	
				}
			}

		$q=new mysql_blackbox();
		$q->QUERY_SQL("DELETE FROM softwares WHERE nodeid='$this->nodeid'");	
		$prefix="INSERT DELAYED INTO softwares (nodeid,software,version) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($prefix);
	}
	
	
}