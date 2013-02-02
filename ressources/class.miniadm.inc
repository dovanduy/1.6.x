<?php

class miniadm{
	
	function miniadm(){
		
	}
	
	
	public function IFItsProxy(){
		$users=new usersMenus();
		if($users->WEBSTATS_APPLIANCE){return true;}
		if($users->PROXYTINY_APPLIANCE){return true;}
		if($users->SQUID_INSTALLED){return true;}
	}
	
	
	public function squid_load_dynamic_acls($force=false){
		if($GLOBALS["VERBOSE"]){echo "squid_load_dynamic_acls:: run()<br>\n";}
		if(!$GLOBALS["VERBOSE"]){
			if(!$force){
				if(isset($_SESSION["SQUID_DYNAMIC_ACLS"])){return;}
			}
		}
		if(!$this->IFItsProxy()){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::{$_SESSION["uid"]} Not a proxy...</span><br>\n";}
			return array();
		}
	
	
	
			$sql="SELECT webfilters_sqitems.pattern,webfilters_sqgroups.ID
			FROM webfilters_sqitems, webfilters_sqgroups
			WHERE webfilters_sqitems.enabled =1
			AND webfilters_sqitems.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'dynamic_acls'
			AND webfilters_sqgroups.enabled =1";
	
			$q=new mysql_squid_builder();
			$results=$q->QUERY_SQL($sql);
			$ldap=new clladp();
			$ADenabled=$ldap->IsKerbAuth();
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::IsKerbAuth() = $ADenabled</span><br>\n";}
			if($ADenabled==1){
				$ad=new external_ad_search();
				$ct=new user($_SESSION["uid"]);
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::GroupsOfMember($ct->dn)</span><br>\n";}
				$groupsofusers=$ad->GroupsOfMember($ct->dn);
				if($GLOBALS["VERBOSE"]){
					echo "<H3>$ct->dn</H3>\n";
					while (list ($a, $b) = each ($groupsofusers) ){
						echo "<li>Member of group: groupsofusers[<strong>$a</strong>]</li>\n";
					}
					
				}
			}
		
			$_SESSION["SQUID_DYNAMIC_ACLS"]=array();
			if($GLOBALS["VERBOSE"]){echo "squid_load_dynamic_acls::{$_SESSION["uid"]} ".mysql_num_rows($results)." acls groups<br>\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($GLOBALS["VERBOSE"]){
						echo "<span style='color:blue'>squid_load_dynamic_acls::Pattern:{$ligne["pattern"]}</span><br>\n";
				}
				if(preg_match("#^AD:[0-9]+:(.+)#", $ligne["pattern"],$re)){
					$DNGroup=base64_decode($re[1]);
					if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>OK:squid_load_dynamic_acls::DNGroup:$DNGroup</span><br>\n";}
					if($ADenabled==0){
						if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>FAILED:squid_load_dynamic_acls::AD not enabled</span><br>\n";}
						continue;
					}
					if(isset($groupsofusers[$DNGroup])){
						$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
						if($GLOBALS["VERBOSE"]){echo "<span style='color:blue;font-weight:bold;font-size:14px'><strong>ADDED:</strong> squid_load_dynamic_acls::{$groupsofusers[$DNGroup]}:TRUE</span><br>\n";}
					}else{
						if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-weight:bold;font-size:14px'><strong>groupsofusers[$DNGroup]:</strong> No match</span><br>\n";}
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::No match...</span><br>\n";}
				}
		
		
			}
	}	
	
	
}