<?php
include_once(dirname(__FILE__)."/class.squid.inc");
class squidusers{
	var $userid=null;
	var $ProxyPacDatas=array();
	var $ProxyPacRemoveProxyListAtTheEnd=0;
	public function squidusers(){
		session_start();
		$this->userid=$_SESSION["uid"];
		$this->Load();
	}
	
	
	private function Load(){
		
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM usersisp WHERE userid='{$_SESSION["uid"]}'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));	
		if(!$q->ok){echo $q->mysql_error."\n"."$sql\n";return;}
		$this->ProxyPacDatas=unserialize(base64_decode($ligne["ProxyPacDatas"]));		
		$this->ProxyPacRemoveProxyListAtTheEnd=$ligne["ProxyPacRemoveProxyListAtTheEnd"];
		
		
	}
	
	public function UpdateProxyPacDatas(){
		if($this->userid==null){echo "Fatal error; userid is null\n";return;}
		if(!is_array($this->ProxyPacDatas)){echo "Fatal error; ProxyPacDatas not an array()\n";return;}
		$serialize=serialize($this->ProxyPacDatas);
		$newdata=base64_encode($serialize);
		$sql="UPDATE usersisp SET ProxyPacDatas='$newdata', ProxyPacCompiled='' WHERE userid='$this->userid'";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(strpos(" $q->mysql_error", "Unknown column")>0){$q->CheckTables();$q->QUERY_SQL($sql);}
		}
		if(!$q->ok){	
			echo $q->mysql_error;
			return;
		}
		
	}
	
	public function GetFamilySites($domain){
		$q=new mysql_squid_builder();
		return $q->GetFamilySites($domain);
	}
	
	
	
	
}