<?php


class dynamic_acls_auth{
	private $uid=null;
	private $groupsofuser=array();
	private $ADenabled=0;
	private $verbose=false;
	

	
	public function dynamic_acls_auth($userid){
		$this->uid=$userid;
		if($GLOBALS["VERBOSE"]){$this->verbose=true;}
		$this->verbose=true;
		$ldap=new clladp();
		$this->ADenabled=$ldap->IsKerbAuth();
		
		if($this->uid<>null){
			$this->writelogs("->LoadAllItems()",__FUNCTION__,__LINE__);
			$this->LoadAllItems();
		}else{
			$this->writelogs("User is null",__FUNCTION__,__LINE__);
		}
		
		
	}
	
	public function SQUID_DYNAMIC_ACLS(){
		$this->writelogs("->LoadAllItems()",__FUNCTION__,__LINE__);
		return $this->LoadAllItems();
	}
	
	private function writelogs($text,$function,$line){
		if(!$this->verbose){return;}
		writelogs("$text",__CLASS__."/".$function,__FILE__,$line);
	}
	
	private function LoadGroupOfUsers(){
		
		if(isset($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"])){
			$this->writelogs("_SESSION: $this->uid = Groups -> ".count($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"]),__FUNCTION__,__LINE__);
			$this->groupsofuser=$_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"];
			return;
		}
		
		$ldap=new clladp();
		$LOG_PREFIX=__CLASS__."/".__FUNCTION__;
		$ADenabled=$ldap->IsKerbAuth();
		if($this->ADenabled==0){return;}
		$ad=new external_ad_search();
		$ct=new user($this->uid);
		$this->writelogs("$this->uid::GroupsOfMember($ct->dn)",__FUNCTION__,__LINE__);
		$this->groupsofuser=$ad->GroupsOfMember($ct->dn);
		$_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"]=$this->groupsofuser;
		
		if($GLOBALS["VERBOSE"]){
			echo "<H3>$ct->dn</H3>\n";
			$groupsofusers=$this->groupsofusers;
			while (list ($a, $b) = each ($groupsofusers) ){echo "<li>{$LOG_PREFIX} Member of group: groupsofusers[<strong>$a</strong>]</li>\n";}
			reset($groupsofusers);
		}
		
	}
	
	private function CheckUserINAD($DN){
		$LOG_PREFIX=__CLASS__."/".__FUNCTION__;
		if($this->ADenabled==0){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>{$LOG_PREFIX}: FAILED:squid_load_dynamic_acls::AD not enabled</span><br>\n";}
			return false;}
		$this->LoadGroupOfUsers();
		if(!isset($this->groupsofuser[$DN])){
			$this->writelogs("->$DN not in array of ".count($this->groupsofuser)." elements",__FUNCTION__,__LINE__);
			return false;}
		
		$this->writelogs("->$DN {$this->groupsofuser[$DN]}:TRUE",__FUNCTION__,__LINE__);
		return true;
	}
		
	private function GetFromSession(){
		if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"])){return false;}
		if(!is_array($_SESSION["SQUID_DYNAMIC_ACLS"])){return false;}
		if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){return true;}
		if(count($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"])==0){return false;}
		return true;
	}	
	
	
	private function LoadAllItems(){
		if($this->GetFromSession()){
			return $_SESSION["SQUID_DYNAMIC_ACLS"];
		}
		
		
		$this->writelogs("User: $this->uid",__FUNCTION__,__LINE__);
		if($this->uid==null){
			$this->writelogs("User: $this->uid is null ->return ",__FUNCTION__,__LINE__);
			return;}
		
		$sql="SELECT webfilters_sqitems.pattern,webfilters_sqgroups.ID
		FROM webfilters_sqitems, webfilters_sqgroups
		WHERE webfilters_sqitems.enabled =1
		AND webfilters_sqitems.gpid = webfilters_sqgroups.ID
		AND webfilters_sqgroups.GroupType = 'dynamic_acls'
		AND webfilters_sqgroups.enabled =1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			$this->writelogs("$q->mysql_error",__FUNCTION__,__LINE__);
			echo $q->mysql_error_html();
			return;
		}
		
		$CountDerules=mysql_num_rows($results);
		$this->writelogs("$CountDerules rules...",__FUNCTION__,__LINE__);
		if($CountDerules==0){return;}
		
		$this->LoadGroupOfUsers();
		$_SESSION["SQUID_DYNAMIC_ACLS"]=array();
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$this->writelogs("Pattern:{$ligne["pattern"]}",__FUNCTION__,__LINE__);
			
			if(preg_match("#^AD:[0-9]+:(.+)#", $ligne["pattern"],$re)){
				$DNGroup=base64_decode($re[1]);
				
				if(!$this->CheckUserINAD($DNGroup)){
					$this->writelogs("->CheckUserINAD($DNGroup) -> FALSE",__FUNCTION__,__LINE__);
					continue;
				}
				$this->writelogs("->CheckUserINAD($DNGroup) -> TRUE",__FUNCTION__,__LINE__);
				$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
				continue;
			}
		
		}
	
	
	return $_SESSION["SQUID_DYNAMIC_ACLS"];
	}
	
	
}