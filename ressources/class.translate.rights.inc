<?php


class TranslateRights{
	private $priv;
	private $uid;
	public $FINAL_PRIVS;
	private $CurrentPageName;
	function TranslateRights($priv=null,$uid=null){
		if(function_exists("CurrentPageName")){$this->CurrentPageName=CurrentPageName();}
		$this->priv=$priv;
		$this->uid=$uid;
	}
	
	public function GetPrivsArray(){
			$f['AsVirtualBoxManager']=true;
			$f['AsSquidAdministrator']=true;
			$f['AsJoomlaWebMaster']=true;
			$f['AseMailCampaignsAdmin']=true;
			$f['AsInventoryAdmin']=true;
			$f['AsOrgAdmin']=true;
			$f['AsMessagingOrg']=true;
			$f['AsDansGuardianGroupRule']=true;
			$f['AsOrgPostfixAdministrator']=true;
			$f['AllowManageOwnComputers']=true;
			$f['AsOrgStorageAdministrator']=true;
			$f['AsMailManAdministrator']=true;
			$f['AllowXapianDownload']=true;
			$f['AllowChangeMailBoxRules']=true;
			$f['AllowDansGuardianBanned']=true;
			$f['AllowOpenVPN']=true;
			$f['AllowEmailing']=true;
			$f['AsQuarantineAdministrator']=true;
			$f['AsDnsAdministrator']=true;
			$f['AsSambaAdministrator']=true;
			$f['AllowEditOuSecurity']=true;
			$f['AllowChangeAntiSpamSettings']=true;
			$f['AllowViewStatistics']=true;
			$f['AllowChangeUserKas']=true;
			$f['AllowSenderCanonical']=true;
			$f['AsMailBoxAdministrator']=true;
			$f['AllowFetchMails']=true;
			$f['AllowChangeDomains']=true;
			$f['AllowEditAliases']=true;
			$f['AllowAddGroup']=true;
			$f['AsSystemAdministrator']=true;
			$f['AllowChangeKav']=true;
			$f['AllowChangeUserPassword']=true;
			$f['AllowChangeKas']=true;
			$f['AllowAddUsers']=true;
			$f['AllowEditAsWbl']=true;
			$f['AsArticaAdministrator']=true;
			$f['AsWebMaster']=true;
			$f['RestrictNabToGroups']=true;
			$f['AsDansGuardianAdministrator']=true;
			$f['AsSquidPersonalCategories']=true;
			
			$f['AsWebFilterRepository']=true;
			$f['AsWebStatisticsAdministrator']=true;
			$f['AllowUserMaillog']=true;
			$f['AsHotSpotManager']=true;
			$f['AsOwnMailBoxBackup']=true;
			$f['AsOrgDNSAdmin']=true;
			$f['ASDCHPAdmin']=true;
			$f['AsAnAdministratorGeneric']=true;
			$f['AsPostfixAdministrator']=true;
			$f['OverWriteRestrictedDomains']=true;
			$f['AsProxyMonitor']=true;
			$f["AsSystemWebMaster"]=true;
			$f["AsArticaMetaAdmin"]=true;
			return $f;		
	}
	
	private function Allrights(){
		$f=$this->GetPrivsArray();
		while (list ($num, $ligne) = each ($f) ){
			
			$this->FINAL_PRIVS[$num]=true;
		
		}	
	}
	
	function IsGlobalAdmin($priv=null,$log=false){
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["posix_getuid"]==0){$this->uid=-100;}
		if($this->uid==-100){
			$this->FINAL_PRIVS["AsArticaAdministrator"]=true;
			$this->FINAL_PRIVS["AsQuarantineAdministrator"]=true;
			$this->FINAL_PRIVS["AsVirtualBoxManager"]=true;
			$this->Allrights();
			if($log){writelogs("It is a global administrator",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			return true;
		}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSquidAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
		
		
		return false;
	}	
	
	function ParseRights(){
		if(isset($_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]])){
			if($this->CurrentPageName=="admin.index.php"){writelogs("$this->CurrentPageName: --> FINAL_PRIVS = FROM Memory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS=$_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]];
			$this->priv=$_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]];
			return;
		}
		
		$log=false;
		$priv=$this->priv;
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::ParseRights() [".__CLASS__."][".__FUNCTION__."][".__LINE__."]\n";}
		if($_SESSION["uid"]==-100){
			
			$this->FINAL_PRIVS["AsArticaAdministrator"]=true;
			$this->FINAL_PRIVS["AllowEditOuSecurity"]=true;
			$this->FINAL_PRIVS["AsSquidAdministrator"]=true;
			$this->FINAL_PRIVS["AsSystemAdministrator"]=true;
			$this->FINAL_PRIVS["AsMessagingOrg"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
			
			$this->Allrights();
			$_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]]=$this->FINAL_PRIVS;
			$this->priv=$this->FINAL_PRIVS;
			return;
		}
		
		if(!is_array($priv)){
			if($GLOBALS["posix_getuid"]<>0){
				if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::No privileges set\n";}
				writelogs("[{$_SESSION["uid"]}]::No privileges set",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				return;
		}
		if($GLOBALS["posix_getuid"]==0){
			$this->FINAL_PRIVS["AsMailBoxAdministrator"]=true;
			$this->FINAL_PRIVS["AsArticaAdministrator"]=true;
			$this->FINAL_PRIVS["AsSystemAdministrator"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
			
			$this->Allrights();
			$_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]]=$this->FINAL_PRIVS;
			$this->priv=$this->FINAL_PRIVS;
			return;
		}
		$this->FINAL_PRIVS["AsArticaAdministrator"]=false;
		$this->FINAL_PRIVS["AsSystemAdministrator"]=false;
		if(isset($GLOBALS["DEBUG_PRIVS"])){if($GLOBALS["DEBUG_PRIVS"]){$log=true;}}
		
		if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::Receive array of ". count($priv)." privileges\n";}
		
		if($log){writelogs("logon: Receive array of ". count($priv)." privileges",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($log){
			while (list ($num, $ligne) = each ($priv) ){
				writelogs("logon: $num = $ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::this->GetPrivsArray()\n";}
		$GetPrivsArray=$this->GetPrivsArray();
		while (list ($num, $ligne) = each ($GetPrivsArray) ){
			if(!isset($priv[$num])){$priv[$num]="no";}
			
		}
		
		reset($GetPrivsArray);
		while (list ($num, $ligne) = each ($GetPrivsArray) ){
			$this->FINAL_PRIVS[$num]=false;
			if($priv[$num]=='yes'){$this->FINAL_PRIVS[$num]=true;}
		}

		
		
		
		

		
		if(isset($priv["PasswdPolicy"])){
			if($priv["PasswdPolicy"]<>null){$this->FINAL_PRIVS["PasswdPolicy"]=unserialize(base64_decode($priv["PasswdPolicy"]));}
		}
		
		if($log){
			if(is_array($priv)){
				while (list ($key, $val) = each ($priv) ){
					if($val=="yes"){
						if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::$key = $val\n";}
						writelogs("$key=$val",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}
				}}
		}
		
		
		if($this->FINAL_PRIVS["AllowEditOuSecurity"]==true){
			if($log){writelogs("[FINAL_PRIVS]:{$_SESSION["uid"]}: AllowEditOuSecurity: TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AseMailCampaignsAdmin"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
				
		}
		
		
		
		
		if($this->IsGlobalAdmin($priv)==true){
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowChangeOrg"]=true;
			$this->FINAL_PRIVS["AsPostfixAdministrator"]=true;
			$this->FINAL_PRIVS["AllowChangeKav"]=true;
			$this->FINAL_PRIVS["AllowChangeKas"]=true;
			$this->FINAL_PRIVS["AllowViewStatistics"]=true;
			$this->FINAL_PRIVS["AllowEditOuSecurity"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AsSquidAdministrator"]=true;
			$this->FINAL_PRIVS["AsSambaAdministrator"]=true;
			$this->FINAL_PRIVS["AsDnsAdministrator"]=true;
			$this->FINAL_PRIVS["AsQuarantineAdministrator"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowViewStatistics"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AsOrgStorageAdministrator"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsMessagingOrg"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
				
		}
		
		if($this->FINAL_PRIVS["AsArticaAdministrator"]==true){
			$this->Allrights();
		}
			
		if($this->FINAL_PRIVS["AsSquidAdministrator"]){
			if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::AsSquidAdministrator: TRUE\n";}
			if($log){writelogs("[FINAL_PRIVS]:{$_SESSION["uid"]}: AsSquidAdministrator: TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["AsWebFilterRepository"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsDansGuardianAdministrator"]=true;
			$this->FINAL_PRIVS["AsWebStatisticsAdministrator"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsProxyMonitor"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
			
		}
			
		if($this->FINAL_PRIVS["AsDansGuardianAdministrator"]){
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsWebStatisticsAdministrator"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
		}
			
		if($this->FINAL_PRIVS["AsPostfixAdministrator"]){
			$this->FINAL_PRIVS["AllowUserMaillog"]=true;
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["OverWriteRestrictedDomains"]=true;
		}
		
		if($this->FINAL_PRIVS["AsArticaMetaAdmin"]){
			$this->FINAL_PRIVS["AsDansGuardianAdministrator"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
		
		}
		
			
		if($this->FINAL_PRIVS["AsSystemAdministrator"]){
			$this->FINAL_PRIVS["AllowUserMaillog"]=true;
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["AsSambaAdministrator"]=true;
			$this->FINAL_PRIVS["AsDnsAdministrator"]=true;
			$this->FINAL_PRIVS["AsVirtualBoxManager"]=true;
			$this->FINAL_PRIVS["OverWriteRestrictedDomains"]=true;
			$this->FINAL_PRIVS["AsWebMaster"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AsSystemWebMaster"]=true;
			
			
				
				
		}
		if($this->FINAL_PRIVS["AsMessagingOrg"]){
			if($log){writelogs("{$_SESSION["uid"]}: AsMessagingOrg: TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowAddUsers"]){
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowChangeDomains"]==true){
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowEditOuSecurity"]==true){
			$this->FINAL_PRIVS["AsOrgAdmin"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AsJoomlaWebMaster"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}
		if($this->FINAL_PRIVS["AsDnsAdministrator"]==true){
			$this->FINAL_PRIVS["AsOrgAdmin"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AsOrgDNSAdmin"]=true;
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
		
		}
		
		if($this->FINAL_PRIVS["AsOrgDNSAdmin"]){
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}
		
		
		
		
		if($this->FINAL_PRIVS["AsOrgAdmin"]==true){
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AsJoomlaWebMaster"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AsOrgDNSAdmin"]=true;
		}
		
		if($this->FINAL_PRIVS["AsMessagingOrg"]){
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
		}
		
		if($this->FINAL_PRIVS["AsQuarantineAdministrator"]==true){$this->FINAL_PRIVS["AsMessagingOrg"]=true;$this->FINAL_PRIVS["AllowUserMaillog"]=true;}
		
		if($this->FINAL_PRIVS["AsInventoryAdmin"]){
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
		}
		
		if($this->FINAL_PRIVS["AsWebStatisticsAdministrator"]){
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
		}

		$_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]]=$this->FINAL_PRIVS;
		$this->priv=$this->FINAL_PRIVS;
	}
	
	
	
	
}