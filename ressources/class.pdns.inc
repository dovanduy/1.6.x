<?php
include_once(dirname(__FILE__) . '/logs.inc');
include_once(dirname(__FILE__) . '/class.templates.inc');
include_once(dirname(__FILE__) . '/class.ldap.inc');
include_once(dirname(__FILE__) . '/class.mysql.inc');


class pdns{
	var $domainname=null;
	var $suffix;
	var $last_error;
	var $DisablePowerDnsManagement=0;
	var $PowerDNSDisableLDAP=0;
	var $PowerDNSMySQLEngine=1;
	var $ttl=null;
	var $prio=null;
	var $articasrv=null;
//------------------------------------------------------------------------------------------------------------------------------	
	function pdns($domainname=null){
		$sock=new sockets();
		$this->PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
		$this->PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSMySQLEngine");
		if(!is_numeric($this->PowerDNSDisableLDAP)){$this->PowerDNSDisableLDAP=1;}
		if(!is_numeric($this->PowerDNSMySQLEngine)){$this->PowerDNSMySQLEngine=1;}
		if(!isset($GLOBALS["DisablePowerDnsManagement"])){
			
			$this->DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
			if(!is_numeric($this->DisablePowerDnsManagement)){$this->DisablePowerDnsManagement=0;}
			$GLOBALS["DisablePowerDnsManagement"]=$this->DisablePowerDnsManagement;
		}else{
			$this->DisablePowerDnsManagement=$GLOBALS["DisablePowerDnsManagement"];
		}
		
		
		if($domainname<>null){
			$this->domainname=strtolower($domainname);
			$this->BuildBranch();
			
		}
		
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function mysql_checkMasterDefaulZone(){
		$sql="SELECT name FROM domains WHERE id=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$this->domainname=$ligne["name"];
		if($this->domainname==null){
			$this->domainname="localhost.localdomain";
		}
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function mysql_checkMasterZone(){
		if($this->PowerDNSMySQLEngine==0){return;}
		if($this->domainname==null){$this->mysql_checkMasterDefaulZone();}
		$q=new mysql();
		$sql="SELECT id FROM domains WHERE name='$this->domainname'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(($ligne["id"]==0) OR !is_numeric($ligne["id"])){
		 	$q->QUERY_SQL("INSERT IGNORE INTO domains (name,`type`) VALUE('$this->domainname','MASTER')","powerdns");
		 	$nextid=$q->last_id;
		 	if(!$q->ok){return;}
		 	$time=date("YmdH");
		 	$q->QUERY_SQL("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`)
			VALUES ($nextid, '$this->domainname', 'SOA', 'ns1.$this->domainname hostmaster.$this->domainname $time 28800 7200 604800 86400', 86400, 0, 1343065389, NULL, NULL)","powerdns");
		 	
		}else{
			$nextid=$ligne["id"];
		}
		
		return $nextid;
		
	}
	
	private function mysql_checkin_addr_arpa(){
		if($this->PowerDNSMySQLEngine==0){return;}
		$q=new mysql();
		$sql="SELECT id FROM domains WHERE name='in-addr.arpa'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(($ligne["id"]==0) OR !is_numeric($ligne["id"])){
		 	$q->QUERY_SQL("INSERT IGNORE INTO domains (name,`type`) VALUE('in-addr.arpa','MASTER')","powerdns");
		 	$nextid=$q->last_id;
		 	if(!$q->ok){return;}
		 	$time=date("YmdH");
		 	$q->QUERY_SQL("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`)
			VALUES ($nextid, 'in-addr.arpa', 'SOA', 'ns1.in-addr.arpa hostmaster.in-addr.arpa $time 28800 7200 604800 86400', 86400, 0, 1343065389, NULL, NULL)","powerdns");
		 	
		}else{
			$nextid=$ligne["id"];
		}
		
		return $nextid;		
		
		
		
	}
	
	
	
	private function BuildBranch(){
		writelogs("PowerDNSMySQLEngine=$this->PowerDNSMySQLEngine PowerDNSDisableLDAP=$this->PowerDNSDisableLDAP DisablePowerDnsManagement=$this->DisablePowerDnsManagement",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if($this->PowerDNSMySQLEngine==1){$this->mysql_checkMasterZone();}
		if($this->PowerDNSDisableLDAP==1){return;}
		if($this->DisablePowerDnsManagement==1){return;}
		
		$ldap=new clladp();
		$up=array();
		$dn="ou=dns,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$up["objectClass"][]="top";
			$up["objectClass"][]="organizationalUnit";
			$up["ou"][]="dns";
			$ldap->ldap_add($dn,$up);
		}else{
			writelogs("ou=dns,$ldap->suffix exists",__CLASS__.'/'.__FUNCTION__,__FILE__);
		}
		
		
		$tbl=explode(".",$this->domainname);
		if(!is_array($tbl)){
			echo "Check domain $this->domainname, cannot split in '.'\n Class: ".__CLASS__."\nLine:".__LINE__;
			writelogs("Check domain $this->domainname, cannot split in '.' ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		
		$extension=$tbl[count($tbl)-1];
		if($extension==null){return null;}
		
		
		$dn="dc=$extension,ou=dns,$ldap->suffix";

		
		
		if(!$ldap->ExistsDN($dn)){
			$up=array();
			writelogs("Check domain $dn doesn`t exists, add it.",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$up["objectClass"][]="top";
			$up["objectClass"][]="dcobject";
			$up["objectClass"][]="dNSDomain2";
			$up["objectClass"][]="domainRelatedObject";
			$up["dc"][]="$extension";
			$up["associateddomain"][]="$extension";
			if(!$ldap->ldap_add($dn,$up)){
				echo "Perhaps the dNSDomain2 is not loaded, restart the LDAP service and retry adding the entry\n";
				return;
			}
			unset($up);
			
		}else{
			writelogs(" $dn exists",__CLASS__.'/'.__FUNCTION__,__FILE__);
		}

		
		
		$strippeddomain=str_replace(".$extension","",$this->domainname);
		$dn="dc=$strippeddomain,dc=$extension,ou=dns,$ldap->suffix";
		$this->suffix=$dn;
		
		
		if(!$ldap->ExistsDN($dn)){
			writelogs("Check domain $dn doesn`t exists, add it.",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$up["objectClass"][]="top";
			$up["objectClass"][]="dcobject";
			$up["objectClass"][]="dNSDomain2";
			$up["objectClass"][]="domainRelatedObject";
			$up["dc"][]="$strippeddomain";
			$up["soarecord"][]="ns.$this->domainname me@$this->domainname 1 1800 3600 86400 7200";
			$up["nsrecord"][]="ns.$this->domainname";
			$up["ARecord"][]="127.0.0.1";
			$up["associateddomain"][]="$this->domainname";
			$ldap->ldap_add($dn,$up);
			unset($up);
			}
		$dn="dc=ns,dc=$strippeddomain,dc=$extension,ou=dns,$ldap->suffix";
		
		if(!$ldap->ExistsDN($dn)){
			writelogs("Check domain $dn doesn`t exists, add it.",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$up["objectClass"][]="top";
			$up["objectClass"][]="dNSDomain2";
			$up["objectClass"][]="domainRelatedObject";
			$up["dc"][]="ns";
			$up["ARecord"][]="127.0.0.1";
			$up["associateddomain"][]="ns.$this->domainname";
			$ldap->ldap_add($dn,$up);
			unset($up);
			}
	
	
	}
	
//------------------------------------------------------------------------------------------------------------------------------	
	private function GetDNFromMac($macaddress){
		$suffix="ou=dns,$ldap->suffix";
		$sr = @ldap_search($ldap->ldap_connection,$suffix,"(&(objectclass=dNSDomain2)(MacRecord=$macaddress))",array());
		if($sr){
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["dn"];
		}
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function DeleteFromMacAddrr($macaddress){
		$ldap=new clladp();
		if($macaddress==null){return null;}
		$suffix="ou=dns,$ldap->suffix";
		
		$sr = @ldap_search($ldap->ldap_connection,$suffix,"(&(objectclass=dNSDomain2)(MacRecord=$macaddress))",array());
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			writelogs("find mac $macaddress ".count($hash)." entries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			for($i=0;$i<$hash["count"];$i++){
				if($hash[$i]["dn"]<>null){
					writelogs("Delete {$hash[$i]["dn"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$ldap->ldap_delete($hash[$i]["dn"]);
				}
			}
		}else{
			writelogs("find mac (&(objectclass=dNSDomain2)(MacRecord=$macaddress)) ERROR",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		
		
	}
//------------------------------------------------------------------------------------------------------------------------------	
	private function ArticaMetaReplic(){
		if($this->DisablePowerDnsManagement==1){return;}
		$sock=new sockets();
		if($sock->GET_INFO("ArticaMetaEnabled")==1){
			$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");
		}		
	}
	
	private function mysql_EditIPName($computername,$IP,$DnsType,$id=0){
		if(!is_numeric($id)){$id=0;}
		if(trim($computername)==null){return false;}
		$tr=array();
		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$computername)){writelogs("Cannot put in DNS an ip address as computer name",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		if(strpos($computername, ".")>0){
			$tr=explode(".", $computername);
			$computername=strtolower($tr[0]);
			unset($tr[0]);
			$this->domainname=strtolower(@implode(".", $tr));
		}
				
			
		if(substr($computername,0,1)=='.'){$computername=substr($computername,1,strlen($computername));}
		$computername=str_replace('..','.',$computername);
		$computername=str_replace('$','',$computername);
		$fqdn="$computername.$this->domainname";		
		$fqdn=str_replace('..','.',$fqdn);	
		$domain_id=$this->mysql_checkMasterZone();
		if($domain_id<1){return false;}
		if(!is_numeric($this->ttl)){$this->ttl=	86400;}
		if(!is_numeric($this->prio)){$this->prio=0;}
		if($this->ttl<500){$this->ttl=86400;}
		$q=new mysql();
		$zdate=time();
		if($id==0){
			$sql="SELECT id FROM records WHERE name='$fqdn'";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
			$id=$ligne["id"];
		}
		if($id>0){
			$q->QUERY_SQL("UPDATE records SET content='$IP',
			`change_date`='$zdate',
			`ttl`='$this->ttl',`prio`='$this->prio' WHERE id='$id'","powerdns");
			if(!$q->ok){
				echo $q->mysql_error;
				return false;}
			
		}else{
			if($this->articasrv<>null){
				$articasrv_field=",`articasrv`";
				$articasrv_data=",'$this->articasrv'";
			}
			$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`$articasrv_field)
			VALUES($domain_id,'$fqdn','$DnsType','$IP','$this->ttl','$this->prio','$zdate'$articasrv_data)","powerdns");
			if(!$q->ok){return false;}	
		}
		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$IP,$re)){
			$arpa_domain_id=$this->mysql_checkin_addr_arpa();
			if($arpa_domain_id>0){
				$arpa="{$re[4]}.{$re[3]}.{$re[2]}.{$re[1]}.in-addr.arpa";
				$sql="SELECT id FROM records WHERE content='$fqdn'";
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
				if($ligne["id"]>0){
					$q->QUERY_SQL("UPDATE records SET `name`='$arpa',
					`change_date`='$zdate',`ttl`='$this->ttl',`prio`='$this->prio' WHERE id='{$ligne["id"]}'","powerdns");
					if(!$q->ok){return false;}
				}else{
					
				if($this->articasrv<>null){
					$articasrv_field=",`articasrv`";
					$articasrv_data=",'$this->articasrv'";
				}					
					
				$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`$articasrv_field)
				VALUES($arpa_domain_id,'$arpa','PTR','$fqdn','$this->ttl','$this->prio','$zdate'$articasrv_data)","powerdns");
				if(!$q->ok){return false;}		
				}
			}
				
		}
		
	}
	
	
//------------------------------------------------------------------------------------------------------------------------------	
	public function EditIPName($computername,$IP,$DnsType,$macaddress=null){
			if($this->PowerDNSMySQLEngine==1){if(!$this->mysql_EditIPName($computername,$IP,$DnsType,$macaddress)){return false;}}
			if($this->PowerDNSDisableLDAP==1){return true;}
			if($this->DisablePowerDnsManagement==1){return true;}
			if(trim($computername)==null){return false;}
			$ldap=new clladp();
			$up=array();
			
			if(preg_match('#[0-9]+\.[0-9]+\.[0-9]+\.#',$computername)){
				writelogs("Cannot put in DNS an ip address as computer name",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
			if(strpos($computername, ".")>0){
					$tr=explode(".", $computername);
					$computername=strtolower($tr[0]);
					unset($tr[0]);
					if($this->domainname==null){
						$this->domainname=strtolower(@implode(".", $tr));
					}
				}			
			
			if($this->domainname==null){
				writelogs("Cannot put in DNS a computer that did not have any domain",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}	

			if(substr($computername,0,1)=='.'){$computername=substr($computername,1,strlen($computername));}
			$computername=str_replace('..','.',$computername);
			$computername=str_replace('$','',$computername);
			$fqdn="$computername.$this->domainname";		
			$fqdn=str_replace('..','.',$fqdn);			
			
			$macaddress=trim($macaddress);
			if($macaddress<>null){
				$dn=$this->GetDNFromMac($macaddress);
				if($dn<>null){
					writelogs("$macaddress = \"$dn\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$up["associateddomain"]="$fqdn";
					if(!$ldap->Ldap_add_mod($dn,$up)){echo $ldap->ldap_last_error;return false;}
					$this->ArticaMetaReplic();
					return true;
				}
			}
			
			
			
			$dn="dc=$computername,$this->suffix";
			if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";
				$up["associatedDomain"][]="$fqdn";
				$up["dc"][0]=$computername;
				if($macaddress<>null){$up["MacRecord"][]=$macaddress;}
				if($DnsType=="A"){$up["arecord"][]=$IP;}
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}else{
				if($DnsType=="A"){$up["arecord"][]=$IP;}
				$up["arecord"][]=$IP;
				if(!$ldap->Ldap_modify($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
				
			}

			unset($up);
			if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$IP,$re)){
				$arpa1=$re[3].".".$re[2].".". $re[1].".in-addr.arpa";
				$arpa2_dc_ip=$re[4].".".$re[3].".".$re[2]."in-addr.arpa";
				
			}else{
				$this->last_error=" $IP bad value\n";
				return false;
			}
			
			$dn="dc=$arpa1,$this->suffix";
			if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";	
				$up["dc"][]="$arpa1";	
				$up["soarecord"][]="ns.$this->domainname me@$this->domainname 1 1800 3600 86400 7200";
				$up["nsrecord"][]="ns.$this->domainname";
				$up["associateddomain"][]="$arpa1";
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}
			unset($up);
			$dn="dc={$re[4]},dc=$arpa1,$this->suffix";
		if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";	
				//$up["objectClass"][]="ArticaComputerInfos";
				$up["dc"][]="$re[4]";	
				$up["ptrrecord"][]="$fqdn";
				$up["associateddomain"][]="{$re[4]}.$arpa1";
				$up["MacRecord"][]=$macaddress;
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}else{
				$up["ptrrecord"]="$fqdn";
				if(!$ldap->Ldap_add_mod($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}		
			}
			$this->ArticaMetaReplic();
			return true;
			
		}
		
	private function mysql_GetIP($servername){
		$q=new mysql();
		$sql="SELECT content FROM records WHERE name='$servername'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		return $ligne["content"];
		
	}
		
	public function GetIp($servername){
			if($this->PowerDNSMySQLEngine==1){
				return $this->mysql_GetIP($servername);
			}
			//if($this->DisablePowerDnsManagement==1){return null;}
			$ldap=new clladp();
			$user=new usersMenus();
			$filter="(&(Objectclass=dNSDomain2)(associatedDomain=$servername))";
			$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("aRecord"));
			if(!$sr){return null;}
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["arecord"][0];
			
		}
		
	private function mysql_DelHostname($hostname_full){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM records WHERE name='$hostname_full'","powerdns");
		$q->QUERY_SQL("DELETE FROM records WHERE content='$hostname_full'","powerdns");
	} 

	public function mysql_delete_record_id($id){
		$q=new mysql();
		$sql="SELECT name FROM records WHERE id='$id'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname_full=$ligne["name"];		
		$q->QUERY_SQL("DELETE FROM records WHERE id='$id'","powerdns");
		$q->QUERY_SQL("DELETE FROM records WHERE content='$hostname_full'","powerdns");		
		
	}
		
	public function DelHostname($hostname_full){
		if($this->PowerDNSMySQLEngine==1){return $this->mysql_DelHostname($hostname_full);}
		if(trim($hostname_full)==null){return;}
		$dn=$this->GetIp($hostname_full);
		if(strlen($dn)<10){return null;}
		$ldap=new clladp();
		if(!$ldap->ldap_delete($dn,true)){
			echo $ldap->ldap_last_error;
		}
		
	}
	
	
		
	public function GetIpDN($servername){
			if($this->PowerDNSMySQLEngine==1){return $this->mysql_GetIP($servername);}
			$ldap=new clladp();
			$user=new usersMenus();
			$filter="(&(Objectclass=dNSDomain2)(associatedDomain=$servername))";
			$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("dn"));
			if(!$sr){return null;}
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["dn"];		
		
	}
	
	private function mysql_IpToHosts($ip){
		$q=new mysql();
		$sql="SELECT name FROM records WHERE content='$ip'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		return $ligne["name"];		
		
	} 
	
		
	public function IpToHosts($ip){
		if($this->PowerDNSMySQLEngine==1){return $this->mysql_IpToHosts($ip);}
		if($this->DisablePowerDnsManagement==1){return null;}
		$ldap=new clladp();
		
		$resolved=gethostbyaddr($ip);
		if($resolved<>null){
			if($resolved<>$ip){
				$array[$resolved]=$resolved;
			}
		}
		$filter="(&(Objectclass=dNSDomain2)(aRecord=$ip))";
		$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("associatedDomain"));
		if(!$sr){return null;}
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		
		
		for($i=0;$i<$hash["count"];$i++){
			for($Z=0;$Z<$hash[$i]["associateddomain"]["count"];$Z++){
				$array[$hash[$i]["associateddomain"][$Z]]=$hash[$i]["associateddomain"][$Z];
			}
			
		}
		
		if(is_array($array)){
			while (list ($index, $host) = each ($array) ){
				$return[]=$host;
			}
		}
		
		return $return;}
	

	
	
}




?>