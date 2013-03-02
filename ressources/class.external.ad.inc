<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
class external_ad_search{
	private $ldap_connection;
	public $ou;
	public $error;
	public $suffix=null;
	function external_ad_search(){
		if(!isset($GLOBALS["AS_ROOT"])){
			if(posix_geteuid()>0){$GLOBALS["AS_ROOT"]=false;}else{$GLOBALS["AS_ROOT"]=true;}
		}
		$sock=new sockets();
		$this->BuildDefault();
		
	}
	
	
	function CheckUserAuth($username,$password){
		if($this->KerbAuthInfos["LDAP_SERVER"]==null){
			$this->error="Failed to connect to DC Could not obtain `LDAP_SERVER`";
			writelogs("$this->error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	
		$this->ldap_connection=@ldap_connect($this->KerbAuthInfos["LDAP_SERVER"],$this->KerbAuthInfos["LDAP_PORT"]);
		if(!$this->ldap_connection){
			$this->error="Failed to connect to DC {$this->KerbAuthInfos["LDAP_SERVER"]}";
			writelogs("$this->error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			@ldap_close();
			return false;
		}
		
		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
		$bind=ldap_bind($this->ldap_connection, "$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}",
		 $password);
		if(!$bind){
			$error=ldap_err2str($this->ldap_connection);
			$this->error="Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} - $username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]} `$error`";
			writelogs($this->error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		return true;
		
		
		}
	

	
	function BuildDefault_ldap_server(){
		if(isset($this->KerbAuthInfos["ADNETIPADDR"])){return $this->KerbAuthInfos["ADNETIPADDR"];}
		if(isset($this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"])){$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
		$SMB=$this->SAMBA_GetNetAdsInfos();
		return $SMB["LDAP server"];
		
	}
	
	private function BuildDefault(){
		$this->LoadSambaInformations();
		if($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]==null){return;}
		if(!isset($this->KerbAuthInfos["LDAP_SERVER"])){$this->KerbAuthInfos["LDAP_SERVER"]=$this->BuildDefault_ldap_server();}
		if(!isset($this->KerbAuthInfos["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=389;}
		if(!isset($this->KerbAuthInfos["LDAP_SUFFIX"])){
			writelogs("LDAP_SUFFIX not set ->SAMBA_GetNetAdsInfos() ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$SMB=$this->SAMBA_GetNetAdsInfos();
			$this->KerbAuthInfos["LDAP_SUFFIX"]=$SMB["Bind Path"];
		}
		if($this->KerbAuthInfos["ADNETIPADDR"]==null){
			$SMB=$this->SAMBA_GetNetAdsInfos();
			$this->KerbAuthInfos["ADNETIPADDR"]=$SMB["KDC server"];			
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS('adgroupsprivs','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`adgroupsprivs` (`DN` VARCHAR( 255 ) NOT NULL PRIMARY KEY,`content` TEXT NOT NULL) ENGINE=MYISAM;";
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		$this->suffix=$this->KerbAuthInfos["LDAP_SUFFIX"];
	
	}
	
	function Ldap_search($dn,$filter,$attrs,$maxRow=0){
		if(!$this->connect()){return array();}

		if(!is_array($attrs)){
			$sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
		}else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
		if (!$sr) {
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
			writelogs("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);
			return array();
		}
		
		return ldap_get_entries($this->ldap_connection,$sr);
		
	}	
	
	
	public function GroupsOfMember($dn){
		
		if(!$GLOBALS["AS_ROOT"]){if(isset($_SESSION[__CLASS__][__FUNCTION__][$dn])){return $_SESSION[__CLASS__][__FUNCTION__][$dn];}}
		if(!$this->connect()){return null;}
		
		$UserAR=$this->LoadMember($dn);
		$dn=$UserAR["distinguishedName"][0];
		$sAMAccountName=$UserAR["sAMAccountName"][0];
		if($GLOBALS["VERBOSE"]){echo "$dn -> $sAMAccountName\n";}
		
		if($sAMAccountName==null){return array();}
		$searchFilter="(&(objectClass=group)(member=$dn))";
		$filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search {$this->KerbAuthInfos["LDAP_SUFFIX"]} / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		
		
		while (list ($num, $ligne) = each ($hash) ){
			if(!isset($ligne["samaccountname"][0])){continue;}
			if($ligne["samaccountname"][0]==null){continue;}
			$MembersCount=0;
			$GroupName=$ligne["samaccountname"][0];
			$dn=$ligne["dn"];
			$res[$dn]=$GroupName;
		}
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[__CLASS__][__FUNCTION__][$dn]=$res;}
		return $res;		
	}
	
	
	public function LoadMember($uid){
		
		if(!$GLOBALS["AS_ROOT"]){if(isset($_SESSION[__CLASS__][__FUNCTION__][$uid])){$GLOBALS[__CLASS__][__FUNCTION__][$uid]=$_SESSION[__CLASS__][__FUNCTION__][$uid];}}
		
		
		if(isset($GLOBALS[__CLASS__][__FUNCTION__][$uid])){
			if(isset($GLOBALS[__CLASS__][__FUNCTION__][$uid]["ARTICA_OU"])){
				$this->ou=$GLOBALS[__CLASS__][__FUNCTION__][$uid]["ARTICA_OU"];
				return $GLOBALS[__CLASS__][__FUNCTION__][$uid];
			}
		}
		if(!$this->connect()){return null;}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$uid))";
		$filter=array();
		$DN=$this->KerbAuthInfos["LDAP_SUFFIX"];
		if(strpos($uid, ",")>0){
			$DN=$uid;
			$searchFilter="(&(objectClass=user)(sAMAccountName=*))";
		}
		
		$sr =@ldap_search($this->ldap_connection,$DN,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search {$this->KerbAuthInfos["LDAP_SUFFIX"]} / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$entry_id = ldap_first_entry($this->ldap_connection,$sr);
		if(!$entry_id){return null;}
		$attrs = ldap_get_attributes($this->ldap_connection, $entry_id);
		$DN=$attrs["distinguishedName"][0];
		$EXPLODED=explode(",",$DN);
		$prefix=$EXPLODED[0].",";
		$DN_PREFIXED=str_replace($prefix, "", $DN);
		
		
		if(strtolower(trim($DN_PREFIXED))==strtolower(trim("CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}"))){
			$this->ou=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
			$attrs["ARTICA_OU"]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
		}else{
			$EXPLODED=explode(",",$DN);
			while (list ($a, $b) = each ($EXPLODED) ){if(preg_match("#OU=(.+)#i", $b,$re)){$attrs["ARTICA_OU"]=$re[1];$this->ou=$re[1];}}
		}
		
		
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[__CLASS__][__FUNCTION__][$uid]=$attrs;}
		$GLOBALS[__CLASS__][__FUNCTION__][$uid]=$attrs;
		return $attrs;
	}
	
	private function mem_getusage(){
		$time=0;
		$unit="KB";
		$mem=round(memory_get_usage(true)/1024);
		if($mem>1024){$mem=round($mem/1000,2);$unit="MB";}
		 
		$xtime=microtime(true);
		if(!isset($GLOBALS["VERBOSE_MICROTIME"])){
			$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
		}else{
			$time = $xtime - $GLOBALS["VERBOSE_MICROTIME"];
			$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
			$time=round($time,2);
		}
		return $time."s ".$mem.$unit;
	}	
	
	
	private function MemberInfoByDN($dn,$recursive=false){
		if(!$this->connect()){return null;}
		$searchFilter="(objectClass=*)";
		
		$filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","objectClass","member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$AsGroup=false;
		
		
		
		
		for($i=0;$i<$hash[0]["objectclass"]["count"];$i++){
			$class=$hash[0]["objectclass"][$i];
			if($class=="group"){$AsGroup=true;break;}
		}
		
		
		
		
		if($AsGroup){
			if(!$recursive){return null;}
			$MembersCount=$hash[0]["member"]["count"];
			for($i=0;$i<$MembersCount;$i++){
				$member=$this->MemberInfoByDN($hash[0]["member"][$i]);
				if(is_array($member)){
					while (list ($a, $b) = each ($member) ){
						if(trim($b)==null){continue;}
						$f[$b]=$b;
					}
				}else{
					$f[$member]=$member;
				}
			
			return $f;
			}
		}
		
		
		if(!isset($hash[0]["samaccountname"][0])){
			writelogs("samaccountname no such attribute",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return null;}
		return $hash[0]["samaccountname"][0];
	}
	
	
	public function HashUsersFromGroupDN($dn,$recursive=false){
		$ORGDN=$dn;
		$Tosend=array();
		if(isset($GLOBALS["HashUsersFromGroupDN($ORGDN)"])){return $GLOBALS["HashUsersFromGroupDN($ORGDN)"];}
		if(!$this->connect()){writelogs("Error: connection failed",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$f=array();
		$searchFilter="(objectClass=*)";
		
		$filter=array("member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array...$dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		
		if(!isset($hash[0]["member"]["count"])){$hash[0]["member"]["count"]=0;}
		
		$MembersCount=$hash[0]["member"]["count"];
		writelogs("MembersCount:$MembersCount in `$dn`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		
		if($GLOBALS["VERBOSE"]){echo "MembersCount:$MembersCount in `$dn` line ". __LINE__."\n";}
		for($i=0;$i<$MembersCount;$i++){
			$MemberName=$this->MemberInfoByDN($hash[0]["member"][$i]);
			if($GLOBALS["VERBOSE"]){echo "{$hash[0]["member"][$i]} => $MemberName line ". __LINE__."\n";}
			if($MemberName==null){continue;}
			if(is_array($MemberName)){
				while (list ($a, $b) = each ($MemberName) ){
					if(trim($b)==null){continue;}
					$f[$b]=$b;
				}
			}else{
				$f[$MemberName]=$MemberName;
			}
			
			
		}
		
		
		
		if(isset($hash[0]["memberof"]["count"])){
			for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
				$dn1=$hash[0]["memberof"][0];
				$ff=$this->HashUsersFromGroupDN($dn1);
				if(count($ff)>0){
					while (list ($a, $b) = each ($ff) ){
						if(trim($b)==null){continue;}
						$f[$b]=$b;
					}
				}
				
			}
		}
		
		
		while (list ($a, $b) = each ($f) ){
			if(trim($b)==null){continue;}
			$Tosend[]=$b;
		}
		
		
		$GLOBALS["HashUsersFromGroupDN($ORGDN)"]=$Tosend;
		return $Tosend;		
		
	}
	
	
	
	public function SearchOu($ou){
		if(!$this->connect()){return false;}
		if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){return true;}
		$searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return false;
		}	
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return false;}	
		return true;
	}
	
	public function CountDeUSerOu($ou,$dn=null){
		
		if($dn<>null){
			if(!$GLOBALS["AS_ROOT"]){
				$q=new mysql();
				$sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `dn`='$dn' AND CountDeUsers>0";
				writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				return $ligne["CountDeUsers"];
			}
			
		}
		
		if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeUsers"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeUsers"];}
		if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
		if(!$GLOBALS["AS_ROOT"]){
			$q=new mysql();
			
			if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
				$ou=utf8_encode($ou);
				$sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `name`='$ou'";
				writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				if($q->ok){
					$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeUsers"];
					return $ligne["CountDeUsers"];
				}else{
					writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
			}else{
				writelogs("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
			}
			
		}
		
		
		
		if(strpos($ou, ",")==0){
			$dn=$this->GetOuDN($ou);
		}else{
			$dn=$ou;
		}
		
		
		if($dn==null){return 0;}
		$pattern="*";
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn");
		
		
		
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return 0;}
		$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$hash["count"];
		return $hash["count"];
				
		
	}
	
	public function find_users($ou,$tofind,$maxentries=0){
		if(!$this->connect()){
			writelogs("Unable to connect",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return array();}
		$tofind=str_replace("***", "*", $tofind);
		$tofind=str_replace("**", "*", $tofind);
		if($ou==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
		if($dn==null){$dn=$this->GetOuDN($ou);}
		if(strpos($ou, ",")>0){$dn=$ou;}
		if($dn==null){return array();}
		
		
		$searchFilter="(&(objectClass=person)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
		$attrs=array("name","sAMAccountName","mail","userPrincipalName","givenName","telephoneNumber","title","sn");
		$attrs=array("displayName","sAMAccountName","mail","givenName","telephoneNumber","title","sn","userPrincipalName","distinguishedName");
		
		writelogs("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxentries>0){
			$sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
		}else{
			$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
		}
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Error:Unknown suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		
	
		writelogs("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		return $hash;
		
			
	}
	
	function LoadGroupDataByDN($dn){
		$dn=trim($dn);
		
		
		$q=new mysql();
		$sql="SELECT `content` FROM adgroupsprivs WHERE `DN`='$dn'";
		writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ArticaGroupPrivileges=$ligne["content"];
		
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ArticaGroupPrivileges -> `". strlen($ArticaGroupPrivileges)." bytes`<br>\n";}
		
		if(!$this->connect()){
			$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
			return $hash;
		}
		$filter=array();
		$searchFilter="(&(objectClass=group)(sAMAccountName=*))";
		writelogs("$searchFilter in $dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
			return $hash;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[__CLASS__][__FUNCTION__][$dn]=$hash;}
		return $hash;
	}
	
	function SaveGroupPrivileges($content,$dn){
		$q=new mysql();
		$sql="SELECT `DN` FROM adgroupsprivs WHERE `DN`='$dn'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$content=addslashes($content);
		$sql="UPDATE adgroupsprivs SET content='$content' WHERE DN='$dn'";
		if($ligne["DN"]==null){
			
			$sql="INSERT INTO adgroupsprivs (DN,content) VALUES ('$dn','$content')";
		}
		writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	function build_sql_activedirectory_ou(){
		$hash=$this->hash_get_ou(false,array(),true);
		if(!$hash){return;}
		$q=new mysql();
		
		
		$q->QUERY_SQL("TRUNCATE TABLE activedirectory_ou","artica_backup");
		$q->QUERY_SQL("DROP TABLE activedirectory_groupsNames","artica_backup");
		$q->BuildTables();
		while (list ($dn, $name) = each ($hash) ){
			if(trim($dn)==null){continue;}
			
			$CountDeUsers=$this->CountDeUSerOu($dn);
			$CountDeGroup=$this->CountDeGroups($dn,true);
			if($GLOBALS["VERBOSE"]){echo "Organization: $name $CountDeUsers members, $CountDeGroup groups\n";}
			
			$name=addslashes(utf8_encode($name));
			$dn=addslashes(utf8_encode($dn));
			$f[]="('$dn','$name','$CountDeUsers','$CountDeGroup')";
		}
		
		if(count($f)>0){
			if($GLOBALS["VERBOSE"]){echo count($f)." organization(s) found...\n";}
			$q->QUERY_SQL("INSERT IGNORE activedirectory_ou (`dn`,`name`,`CountDeUsers`,`CountDeGroups`) VALUES ".@implode(",", $f),"artica_backup");
			if($GLOBALS["VERBOSE"]){if(!$q->ok){echo "FATAL !!! $q->mysql_error\n";}}
		}
		
	}
	

	function hash_groups($ou,$fieldlist=0,$maxitems=0,$tofind=null){
		if(!$this->connect()){
			writelogs("Unable to connect",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		if(strpos($ou,",")>0){$dn=$ou;}else{
			$dn=$this->GetOuDN($ou);
		}
		if($dn==null){
			writelogs("$ou $tofind, dn=null ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();}
		$res=array();
		if($tofind==null){$tofind="*";}
		writelogs("Search Groups in $dn",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");
		$searchFilter="(&(objectClass=group)(sAMAccountName=$tofind))";
		writelogs("$searchFilter in $dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Unknown error...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not an array ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();}
		
		$NOTCOUNT[strtolower("Domain Computers")]=true;
		$NOTCOUNT[strtolower("Domain Controllers")]=true;
		$NOTCOUNT[strtolower("Domain Users")]=true;
		$NOTCOUNT[strtolower("Domain Guests")]=true;
		$NOTCOUNT[strtolower("RAS and IAS Servers")]=true;

		
		writelogs(count($hash)." items ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		while (list ($num, $ligne) = each ($hash) ){
			if(!isset($ligne["samaccountname"][0])){continue;}
			if($ligne["samaccountname"][0]==null){continue;}
			$MembersCount=0;
			$GroupName=$ligne["samaccountname"][0];
			if(isset($ligne["member"]["count"])){$MembersCount=$ligne["member"]["count"];}
			if($MembersCount==0){
			if(isset($NOTCOUNT[strtolower($GroupName)])){$MembersCount="-";}}
				
			
				
				
			switch ($fieldlist) {
				case 1:$res[$ligne["dn"]]=$ligne["samaccountname"][0];break;
				case 2;$res=$res . "{$ligne["dn"]};{$ligne["samaccountname"][0]}\n";break;
				default:$res[$ligne["cn"][0]]=array(
					"groupname"=>$ligne["samaccountname"][0],
					"gid"=>urlencode($ligne["dn"]),
					"description"=>$ligne["description"][0],
					"UsersCount"=>$this->CountDeMemberGroupDN($ligne["dn"])
					);
				break;
			}
			
				
		
		}
		
		return $res;
		
	}
	
	public function CountDeUsersByGroupName($groupname){
		if(!$this->connect()){return 0;}
		$filter=array("distinguishedName");
		$searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		$DN=$hash[0]["distinguishedname"][0];
		return $this->CountDeMemberGroupDN($DN);
	}
	
	private function CountDeMemberGroupDN($dn){
		if(isset($GLOBALS["CountDeMemberGroupDN($dn)"])){return $GLOBALS["CountDeMemberGroupDN($dn)"];}
		$hash=$this->HashUsersFromGroupDN($dn);
		if($GLOBALS["VERBOSE"]){echo "`$dn` -> ".count($hash)." members line ".__LINE__."\n";}
		$GLOBALS["CountDeMemberGroupDN($dn)"]=count($hash);
		return $GLOBALS["CountDeMemberGroupDN($dn)"];
		
	}
	
	
	private function GetOuDN($ou){
		if(!$this->connect()){return null;}
		if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){
			return "CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}";
		}
		$searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return false;
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return false;}
		if(isset($hash[0]["dn"])){return $hash[0]["dn"];	}	
		if(isset($hash["dn"])){return $hash["dn"];}	
	}
	
	
	public function hash_get_ou($has_key=false,$array,$FullHash=false){
		
		if(!$GLOBALS["AS_ROOT"]){
			$q=new mysql();
			if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
				$sql="SELECT * FROM activedirectory_ou ORDER BY name";
				$results=$q->QUERY_SQL($sql,"artica_backup");
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$ou=$ligne["name"];
					$GLOBALS["hash_get_ou"][$ou]["CountDeGroups"]=$ligne["CountDeGroups"];
					$GLOBALS["hash_get_ou"][$ou]["CountDeUsers"]=$ligne["CountDeUsers"];
					if($has_key){$array[$ou]=$ou;}else{$array[]=$ou;}
					
				}
				return $array;
				
		}
		}
		
		
		
		if(!$this->connect()){return $array;}
		if($has_key){$array[$this->KerbAuthInfos["ADNETBIOSDOMAIN"]]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}else{$array[]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}
		
		
		$searchFilter="(&(objectClass=organizationalUnit)(name=*))";
		$filter=array("name","dn");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}
		writelogs("{$hash["count"]} organization(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		
		
		
		for($i=0;$i<$hash["count"];$i++){
			
			$dn=$hash[$i]["dn"];
			$ou=$hash[$i]["name"][0];
			if(strtolower($ou)==strtolower("Domain Controllers")){
				$dn="CN=Users,".$this->KerbAuthInfos["LDAP_SUFFIX"];
				$ff=explode(",",$this->KerbAuthInfos["LDAP_SUFFIX"]);
				if(preg_match("#(.+?)=(.+)#", $ff[0],$re)){$ou=$re[2];}
			}
			
			
			
			if($FullHash){
				$array[$dn]=$ou;
				continue;
			}
			
			
			if($has_key){$array[$ou]=$ou;}else{$array[]=$ou;}
				
		}
		
		return $array;		
		
		
	}
	
	public function CountDeGroups($ou,$mysql=false){
		if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeGroups"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeGroups"];}
		
			if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
			if(!$GLOBALS["AS_ROOT"]){
				$q=new mysql();
				if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
					$ou=utf8_encode($ou);
					$sql="SELECT CountDeGroups FROM activedirectory_ou WHERE `name`='$ou'";
					writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
					if($q->ok){
						$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeGroups"];
						return $ligne["CountDeGroups"];
					}else{
						writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}
				}else{
					writelogs("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
				}
		}
		
		
		
		if(strpos($ou, ",")==0){$dn=$this->GetOuDN($ou);}else{$dn=$ou;}
		
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "!!! *** Error:connect failed in line ".__LINE__."\n";}
			return 0;
		}
		$pattern="*";
		$searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn","description");
		
		$sr =@ldap_search($this->ldap_connection,$dn,$searchFilter,$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				if($GLOBALS["VERBOSE"]){echo "!!! *** Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter\n";}				
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}

		
		$dn=addslashes($dn);
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			if($GLOBALS["VERBOSE"]){echo "!!! *** Error: Hash is not an array in line ".__LINE__."\n";}
			return 0;
		}
		
		
		$f=array();
		
		if($mysql){
			for($i=0;$i<$hash["count"];$i++){
				$dnG=addslashes($hash[$i]["dn"]);
				
				$samaccountname=addslashes($hash[$i]["samaccountname"][0]);
				if(!isset($hash[$i]["description"][0])){$hash[$i]["description"][0]=null;}
				
				$description=addslashes($hash[$i]["description"][0]);
				$CountDeUsers=$this->CountDeMemberGroupDN($hash[$i]["dn"]);
				$md5=md5($dn);
				if($GLOBALS["VERBOSE"]){echo "('$dnG','$samaccountname','$CountDeUsers','$description','$dn')\n";}
				$f[]="('$dnG','$samaccountname','$CountDeUsers','$description','$dn')";
				if($GLOBALS["VERBOSE"]){echo "$samaccountname $CountDeUsers $dnG\n";}
			}
			
			if(count($f)>0){
				$q=new mysql();
				$sql="INSERT IGNORE INTO activedirectory_groupsNames 
						(`dn`,`groupname`,`UsersCount`,`description`,`oudn`) VALUES ".
						@implode(",", $f);
				
				$q->QUERY_SQL($sql,"artica_backup");
		
				if(!$q->ok){echo "!!! $q->mysql_error\n";}
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Mysql = FALSE line ".__LINE__."\n";}
		}
		
		
		
		return $hash["count"];
	}
	
	
	private function LoadSambaInformations(){
			$sock=new sockets();
			$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
			$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
			if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
			if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
			
			if($EnableKerbAuth==1){
				$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];
				$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["WINDOWS_SERVER_NETBIOSNAME"];
				$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["ADNETBIOSDOMAIN"];
				$this->KerbAuthInfos["ADNETIPADDR"]=$array["ADNETIPADDR"];
				$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
				$this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
				$this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
				$this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];

				
			}
			if($EnableSambaActiveDirectory==1){
				$Save=false;
				$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
				$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["ADSERVER"];
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["ADDOMAIN"];
				$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["WORKGROUP"];
				$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["ADADMIN"];
				$this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["PASSWORD"];
				$this->KerbAuthInfos["ADNETIPADDR"]=$array["ADSERVER_IP"];
				
				
				if(!isset($array["LDAP_SUFFIX"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["Bind Path"]<>null){$array["LDAP_SUFFIX"]=$SMB["Bind Path"];$Save=true;}}
				if(!isset($array["LDAP_SERVER"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["LDAP server"]<>null){$array["LDAP_SERVER"]=$SMB["LDAP server"];$Save=true;}}
				if($Save){$sock->SaveConfigFile(base64_encode(serialize($array)), "SambaAdInfos");}
				$this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
				$this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
			}
				
		}	

	public function searchGroup($pattern,$array,$maxitems=0){
		if(!$this->connect()){return $array;}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
		$filter=array("samaccountname");
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}	

		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}
		writelogs("{$hash["count"]} Group(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			$groupid=$hash[$i]["samaccountname"][0];
			if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
			$array["@$groupid"]="@$groupid:0000000";
			
		}
		
		return $array;		
		
	}
	
	public function GetGroupDN($groupname){
		$maxitems=null;
		if(!$this->connect()){return null;}
		$searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
		$filter=array("samaccountname","dn");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return null;
		}	
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return null;}
		return $hash[0]["dn"];
		
	}
	
	
		
	public function searchUser($pattern,$array){
		if(!$this->connect()){return $array;}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}

		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}	
		writelogs("{$hash["count"]} members found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			$userid=$hash[$i]["samaccountname"][0];
			if(substr($userid,strlen($userid)-1,1)=='$'){continue;}
			$mail=$hash[$i]["mail"][0];
			$userprincipalname=$hash[$i]["userprincipalname"][0];
			$displayname=$hash[$i]["displayname"][0];
			if($displayname==null){$displayname=$userid;}
			$array[$userid]=$displayname;
		}
		
		return $array;
		
	}
	
	
	function UserSearch($ou=null,$tofind,$max=0){
		$uid=$_SESSION["uid"];
		if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
		$filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind)))";
		$attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
		if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,$this->suffix";}
		//writelogs("Ldap_search : $filter in $dn", __CLASS__ . "/" . __FUNCTION__,__FILE__);
		$hash=$this->find_users($ou,$tofind,$max);
			
		$hash2=array();
		return array($hash,$hash2);
	
	}
	

	private function SAMBA_GetNetAdsInfos(){
		$sock=new sockets();
		if(isset($_SESSION["CACHE_NETADS"])){return $_SESSION["CACHE_NETADS"];}
		$_SESSION["CACHE_NETADS"]=unserialize(base64_decode($sock->getFrameWork("samba.php?GetNetAdsInfos=yes")));
	}

	private function connect(){
		if($this->KerbAuthInfos["LDAP_SERVER"]==null){
			writelogs("Failed to connect to DC Could not obtain `LDAP_SERVER`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		if($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]==null){
			writelogs("Failed to connect to DC Could not obtain `WINDOWS_SERVER_ADMIN`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		$this->ldap_connection=@ldap_connect($this->KerbAuthInfos["LDAP_SERVER"],$this->KerbAuthInfos["LDAP_PORT"]);
		if(!$this->ldap_connection){
			writelogs("Failed to connect to DC {$this->KerbAuthInfos["LDAP_SERVER"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			@ldap_close();
			return false;
		}
		
		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
		$bind=ldap_bind($this->ldap_connection, "{$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]}@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}", $this->KerbAuthInfos["WINDOWS_SERVER_PASS"]);
		if(!$bind){
			$error=ldap_err2str($this->ldap_connection);
			writelogs("Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} `$error`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		return true;
		
		
	}
	
}