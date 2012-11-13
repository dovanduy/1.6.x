<?php
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
class squid_acls{
	var $acls_array=array();
	var $availableGroups=array();
	var $http_access=array();
	var $ACL_ARP_ENABLED=false;
	var $SQUID_GROUPS_ITEMS_TYPE=array();
	var $RequestedGroupeType=null;
	var $UseDynamicGroupsAcls=0;
	var $EnableKerbAuth=0;
	var $AUTH=0;
	var $instancetime=null;

	public function squid_acls(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		$this->UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=0;}
		$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		$squid=new squidbee();
		if($squid->LDAP_AUTH==1){$this->AUTH=1;}
		if($squid->LDAP_EXTERNAL_AUTH==1){$this->AUTH=1;}
		if($squid->EnableKerbAuth==1){$this->AUTH=1;}
		$this->instancetime=time();
	}

	
	public function Build_Acls(){
		echo "Starting......: [ACLS]: Building ACLS (patched on 2012-10-08)\n";
		$this->Build_Acls_Groups();
		$this->Build_Acls_time();
	}
	
	
	private function Build_Acls_Groups(){
		if(isset($GLOBALS["BUILD_ACLS_EXECUTED"])){
			$this->acls_array=$GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"];
			$this->availableGroups=$GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"];
			return;
		}
		
		$q=new mysql_squid_builder();
		@mkdir("/etc/squid3/acls",0755,true);
		$sql="SELECT ID,GroupName,GroupType FROM webfilters_sqgroups WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
		$ReturnFirstValue=false;
		while ($ligne = mysql_fetch_assoc($results)) {
			$acls_name="Group{$ligne["ID"]}";
			echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]}\n";
			
			if($ligne["GroupType"]=="maxconn"){
				$AclsValue=$this->buildItems($ligne["ID"],true);
				$this->availableGroups[$ligne["ID"]]=true;
				echo "Starting......: [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` maxconn $AclsValue max connection(s)\n";	
				$this->acls_array[]="acl $acls_name {$ligne["GroupType"]} $AclsValue";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}
			
			if($ligne["GroupType"]=="arp"){
				if(!$this->ACL_ARP_ENABLED){
					echo "Starting......: !! *****\n";
					echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} will be not available (ARP is not compiled)\n";
					echo "Starting......: !! *****\n";
				}
			}			
			
			if($ligne["GroupType"]=="proxy_auth_ads"){
				if($this->EnableKerbAuth==0){echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
				echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ads_group ". utf8_encode($ligne["GroupName"]);
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;				
			}
			
			if($ligne["GroupType"]=="NudityScan"){
				if(!$GLOBALS["ArtcExtrnNudeScanner"]){
					echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - ArtcExtrnNudeScanner is disabled !!! Why ???\n";
					continue;
				}
				echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:NudityScan $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ArtcExtrnNudeScanner";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;								
			}
			
			if($ligne["GroupType"]=="time"){
					echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:NudityScan $acls_name is available\n";
					$time=$this->ParseTimeAclsGroup($ligne["ID"]);
					if($time<>null){
						$this->availableGroups[$ligne["ID"]]=true;
						$this->acls_array[]="acl $acls_name time $time";
						$TEMPLATE=$this->acl_tpl($ligne["ID"]);
						if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
					}else{
						echo "Starting......: [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` SKIP with error\n";
					}
				continue;								
			}			
			
			if($ligne["GroupType"]=="proxy_auth"){
				if($this->AUTH==0){
					echo "Starting......: [ACLS]: `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP AUTH = 0\n";
					continue;
				}				
				
			}
			if($ligne["GroupType"]=="browser"){
				$ligne["GroupType"]="browser -i";
			}
			
			
			
			$AclsItemsCount=$this->buildItems($ligne["ID"],$ReturnFirstValue);
			echo "Starting......: [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` `$AclsItemsCount` item(s)\n";	
			
			
			if($AclsItemsCount>0){
				$this->availableGroups[$ligne["ID"]]=true;
				echo "Starting......: [ACLS]: ACL Engine Group: $acls_name is available\n";
				$this->acls_array[]="acl $acls_name {$ligne["GroupType"]} \"/etc/squid3/acls/container_{$ligne["ID"]}.txt\" ";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				$GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"]=$this->acls_array;
				$GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"]=$this->availableGroups;
				
				
			}else{
				echo "Starting......: !! *****\n";
				echo "Starting......: [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} will be not available (no item)\n";
				echo "Starting......: !! *****\n";
			}
		}
	}
	
	private function acl_tpl($aclid){
		$sql="SELECT acltpl FROM webfilters_sqgroups WHERE ID='$aclid'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["acltpl"]==null){return;}
		$sql="SELECT template_name,lang,template_link,template_uri FROM squidtpls WHERE `zmd5`='{$ligne["acltpl"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!preg_match("#ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}
		$template_path="/usr/share/squid3/errors/{$ligne["lang"]}/{$ligne["template_name"]}";
		if($ligne["template_link"]==1){return $ligne["template_uri"];}
		if(!is_file($template_path)){return null;}
		return $ligne["template_name"];
		
	}
	
	private function ParseTimeAclsGroup($ID){
		$sql="SELECT other FROM webfilters_sqitems WHERE gpid='$ID'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$t=time();
		$pattern=base64_decode($ligne["other"]);
		$TimeSpace=unserialize(base64_decode($ligne["other"]));
		if(!is_array($TimeSpace)){return null;}	
		$fromtime=$TimeSpace["H1"];
		$tottime=$TimeSpace["H2"];
		if($fromtime<>null && $tottime<>null){
			$acltime=" $fromtime-$tottime";
		}
		if($fromtime=="00:00" && $tottime=="00:00"){
			$acltime=null;
		}
		
		$days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
		$dd=array();
		while (list ($key, $ligne) = each ($TimeSpace) ){
			if(preg_match("#^day_([0-9]+)#", $key,$re)){
				$dayT=$re[1];
				if($ligne<>1){continue;}
				$dd[]=$days[$dayT];
			}
		}
		if(count($dd)>0){$days_text=@implode($dd, "");}
		
		if($acltime==null && $days_text==null){
			echo "Starting......: [ACLS]: ACL Engine Group: $ID No time set.. SKIP!!!\n";
			return;
		}
		return "$days_text$acltime";
		
	}

	
	
	private function buildItems($gpid,$returnFirstValue=false){
		$filename="/etc/squid3/acls/container_$gpid.txt";
		@unlink($filename);
		$f=$this->GetItems($gpid);
		if($returnFirstValue){return $f[0];}
		$c=count($f);
		if($c>0){@file_put_contents($filename, @implode("\n", $f));}
		return $c;
	}
	
	private function Build_Acls_time(){
		$days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
		$sql="SELECT * FROM webfilters_sqtimes_rules WHERE enabled=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);	
		while ($ligne = mysql_fetch_assoc($results)) {	
			$TimeSpace=unserialize($ligne["TimeCode"]);
			if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
			if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
			if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
			if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}			
			$timeHour="{$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]}-{$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]}";
			
			$f=array();
			while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	
				if($num==array()){continue;}
				if(!isset($days[$num])){continue;}
				if($days[$num]==array()){continue;}
				if($val<>1){continue;}
				$f[]= $days[$num];
			}	
			$days_text=trim(@implode("", $f));
			if($days_text==null){$days_text="MTWHFAS";}
			$acls_name="Time{$ligne["ID"]}";
			$this->acls_array[]="acl $acls_name time $days_text $timeHour";
			
		}
	}
	
	
	public function build_http_access(){
		echo "Starting......: [ACLS]: build_http_access() ACL Engine Rules:[$this->instancetime] ". count($this->http_access)." items (before)\n"; 
		$this->build_http_access_time();
		echo "Starting......: [ACLS]: build_http_access() ACL Engine Rules:[$this->instancetime] ". count($this->http_access)." items (after)\n"; 
		return $this->http_access;
		
	}
	
	public function GetItems($gpid,$required=null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType,enabled FROM webfilters_sqgroups WHERE ID='$gpid'"));
		$GroupeType=$ligne["GroupType"];
		$this->RequestedGroupeType=$GroupeType;
		if($ligne["enabled"]==0){if($GLOBALS["VERBOSE"]){echo "Starting......: [ACLS]: ACL Engine Group:$gpid is disabled skip..\n";}return array();}
		if($required<>null){if($GroupeType<>$required){if($GLOBALS["VERBOSE"]){echo "Starting......: [ACLS]: ACL Engine Group:$gpid {$ligne["GroupType"]} is different than required \"$required\" skip..\n";}return array();}}
		if($GroupeType=="arp"){if(!$this->ACL_ARP_ENABLED){if($GLOBALS["VERBOSE"]){echo "Starting......: [ACLS]: ACL Engine Group:$gpid ARP is not compiled, skip..\n";}return array();}}
		$sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
		$c=0;
		$f=array();
		
		$already=array();
		$MASTERDOMAINAL=array();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if(trim($ligne["pattern"])==null){if($GLOBALS["VERBOSE"]){echo "Starting......: [ACLS]: ACL Engine Group: $gpid pattern is null, skip..\n";}continue;}
			
			if($GroupeType=="dstdomain"){
				$MASTERDOMAIN=$q->GetFamilySites($ligne["pattern"]);
				if(isset($MASTERDOMAINAL[$MASTERDOMAIN])){continue;}
				if(substr($ligne["pattern"], 0,1)<>"."){$ligne["pattern"]=".{$ligne["pattern"]}";}
				$MASTERDOMAINAL[$MASTERDOMAIN]=true;
			}
			
			if($GroupeType=="arp"){
				$ligne["pattern"]=str_replace("-", ":", $ligne["pattern"]);
			}
			if($GroupeType=="src"){
				if(trim($ligne["pattern"])=="0.0.0.0/0"){$ligne["pattern"]="all";}
				if(trim($ligne["pattern"])=="0.0.0.0"){$ligne["pattern"]="all";}
			}
			
			
			
			if(isset($already[$ligne["pattern"]])){continue;}
			$already[$ligne["pattern"]]=true;
			
			if(preg_match("#AD:(.*?):(.+)#", $ligne["pattern"],$re)){
				$entries=$this->members_from_activedirectory($ligne["pattern"]);
				if($entries==null){continue;}
				$ligne["pattern"]=$entries;
			}			
			$f[]=$ligne["pattern"];
			$c++;
		}
		return $f;		
	}
	
	private function members_from_activedirectory($dn){
		if(trim($dn)==null){return;}
		if(preg_match("#AD:(.*?):(.+)#", $dn,$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$dn=base64_decode($dnEnc);
				if($GLOBALS["VERBOSE"]){writelogs("LDAPID:$LDAPID DN=`$dn`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$ad=new ActiveDirectory($LDAPID);
				$USERS=$ad->search_users_from_group($dn,0);
		}else{
			ufdbguard_admin_events("Failed to lookup $dn", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		if($GLOBALS["VERBOSE"]){writelogs("Array(USERS) = ".count($USERS)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($USERS)==0){
			ufdbguard_admin_events("Failed to lookup $dn no such user", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		while (list ($dn, $Props) = each ($USERS) ){
			$type=$Props["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn TYPE=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseLDAPSubUsersGroups($LDAPID,$dn)",__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
					if(count($newrow>0)){
						while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
					}
				}
				continue;
			}			
			
			if(trim($Props["uid"]==null)){continue;}
			$Props["uid"]=trim(strtolower($Props["uid"]));
			
			$f[]=$Props["uid"];
			
		}
		
		return @implode("\n", $f)."\n";
	}	
	
	private function ParseLDAPSubUsersGroups($LDAPID,$dn){
		$f=array();
		if($GLOBALS["VERBOSE"]){writelogs("LDAPID: $LDAPID, Parsing DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$ad=new ActiveDirectory($LDAPID);
		$Array=$ad->search_users_from_group($dn);
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn items=`". count($Array)."`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($ad->ldap_last_error<>null){writelogs("FAILED to lookup dn $dn",__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($Array)==0){
			writelogs("Lookup dn $dn return no user !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		
		
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn START LOOP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		while (list ($dn, $GPARR) = each ($Array) ){
			$dnEnc=base64_encode($dn);
			$type=$GPARR["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn type=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseUsersGroups($LDAPID,$dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
					if(is_array($newrow)){
						if(count($newrow>0)){
							while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
						}
					}
				}
				continue;
			}
				if($GLOBALS["VERBOSE"]){writelogs("DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$cn=trim($GPARR["uid"]);
				if(trim($cn)==null){continue;}
				$cn=str_replace(" ", "%20", $cn);
				$f[]=$cn;
			}
		
		
		return $f;
	}		
	
	
	
	private function build_http_access_time(){
		$sql="SELECT webfilters_sqtimes_assoc . * , `webfilters_sqtimes_rules`.`Allow`
		FROM `webfilters_sqtimes_assoc` , `webfilters_sqgroups` , `webfilters_sqtimes_rules`
		WHERE 
			webfilters_sqtimes_assoc.gpid = webfilters_sqgroups.ID
			AND webfilters_sqtimes_rules.ID = webfilters_sqtimes_assoc.TimeRuleID
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqtimes_rules.enabled =1
			
		ORDER BY webfilters_sqtimes_rules.`Allow` DESC";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);	
		
		if(!$q->ok){if(preg_match("#doesn't exist#", $q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		$alreadyTpl=array();
		if(!$q->ok){echo "\n---------------------\n$q->mysql_error\n$sql\n\nLine: ".__LINE__."\nClass:".__CLASS__."\nFunction: ".__FUNCTION__."\nFile:".__FILE__."\n---------------------\n";}
		while ($ligne = mysql_fetch_assoc($results)) {	
			$Access="deny";
			if($ligne["Allow"]==1){$Access="allow";}
			if(!isset($this->availableGroups[$ligne["gpid"]])){
				echo "Starting......: [ACLS]: ACL Engine Rules Group{$ligne["gpid"]} is empty for Time rule Time{$ligne["TimeRuleID"]}, skip it\n";
				continue;}
			$this->http_access[]="http_access $Access Group{$ligne["gpid"]} Time{$ligne["TimeRuleID"]}";
			if($ligne["Allow"]==0){
				if(!isset($alreadyTpl[$ligne["TimeRuleID"]])){
					$this->deny_info_time($ligne["TimeRuleID"]);
					$this->http_access[]="deny_info ERROR_TIMERULE_{$ligne["TimeRuleID"]} Time{$ligne["TimeRuleID"]}";
					$alreadyTpl[$ligne["TimeRuleID"]]=true;
				}
			}
				
		}
		
		
	}
	
	private function deny_info_time($ID){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TemplateError,`Allow` FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
		$filename="/usr/share/squid-langpack/en/ERROR_TIMERULE_$ID";
		$filename2="/usr/share/squid3/errors/templates/ERROR_TIMERULE_$ID";
		@unlink($filename);
		if($ligne["Allow"]==1){return;}
	
		$array=unserialize(base64_decode($ligne["TemplateError"]));
		if(!isset($array["TITLE"])){$array["TITLE"]="Access denied for this time";}
		if(!isset($array["ERROR"])){$array["ERROR"]="The requested access could not be allowed";}
		if(!isset($array["EXPLAIN"])){$array["EXPLAIN"]="Your are not allowed to acces to internet at this time";}
		if(!isset($array["REASON"])){$array["REASON"]="Surfing to internet banned";}	
		
		@mkdir("/usr/share/squid-langpack/en",0755,true);
		if(!is_dir("/usr/share/squid-langpack/en/templates")){@mkdir("/usr/share/squid-langpack/en/templates",0755,true);}
		if(!is_dir("/usr/share/squid3/errors/templates")){@mkdir("/usr/share/squid3/errors/templates",0755,true);}
		
		$content="<table class=\"w100 h100\">
		<tr>
		<td class=\"c m\">
		<table style=\"margin:0 auto;border:solid 1px #560000\">
		<tr>
		<td class=\"l\" style=\"padding:1px\">
		<div style=\"width:346px;background:#E33630\">
			<div style=\"padding:3px\">
				<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
					<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
						<h1>ERROR: {$array["TITLE"]}</h1>
					</div>
					<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div>
					<div style=\"background:#F7F7F7;padding:20px 28px 36px\"> 
						<div id=\"titles\"> 
							<h1>ERROR</h1> <h2>{$array["ERROR"]}</h2> 
						</div> <hr>  
							<div id=\"content\"> <p>{$array["EXPLAIN"]}</p>  
							<blockquote id=\"error\"> <p><b>{$array["REASON"]}</b></p> </blockquote>  
							<p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";	
		$header=@file_get_contents(dirname(__FILE__)."/databases/squid.default.header.db");
		$newheader=str_replace("{TITLE}", $array["TITLE"], $header);
		$templateDatas="$newheader$content</body></html>";		
		@file_put_contents($filename, $templateDatas);
		@file_put_contents($filename2, $templateDatas);
		
		
	}
	
	
	
	

}