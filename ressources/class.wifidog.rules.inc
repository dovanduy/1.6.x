<?php
include_once(dirname(__FILE__)."/class.wifidog.tools.inc");
include_once(dirname(__FILE__)."/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");

class wifidog_rules{
	private $gateway_addr;
	private $gw_port;
	private $gw_id;
	private $ARP;
	private $url;
	private $client_ip;
	public $ruleid=0;
	
	function wifidog_rules(){
		$this->gateway_addr=$_REQUEST["gw_address"];
		$this->gw_port=$_REQUEST["gw_port"];
		$this->gw_id=$_REQUEST["gw_id"];
		$this->ARP=$_REQUEST["mac"];
		$this->url=$_REQUEST["url"];
		if(isset($_REQUEST["ruleid"])){$ruleid=$_REQUEST["ruleid"];}
		if(!isset($_REQUEST["token"])){$_REQUEST["token"]=generateToken($this->ARP);}
		if(!isset($_REQUEST["ip"])){$_REQUEST["ip"]=$this->findip();}
		$this->client_ip=$_REQUEST["ip"];
		if($this->ruleid==0){$this->ruleid=$this->found_rule();}
	}
	
	private function findip(){
		if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
		if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
		return $IPADDR;
		
		
	}
	
	
	private function found_rule(){
		$ip=new IP();
		$q=new mysql_squid_builder();
		$sql="SELECT ruleid,pattern FROM webauth_rules_nets";
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$pattern=$ligne["pattern"];
			if($ip->isInRange($this->client_ip, $pattern)){
				return $ligne["ruleid"];
			}
		}
		
		return 0;
		
	}

	
	
}