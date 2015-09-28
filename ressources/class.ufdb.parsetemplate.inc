<?php
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/class.ldap.inc');
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.ldap-extern.inc');

class parse_template_ufdb{
	private $IsKerbAuth=false;
	private $q;
	private $RETURNED=2;
	private $client_username=null;
	private $FIELD_TO_QUERY=null;
	private $VALUE_TO_QUERY=null;
	private $CATEGORY_KEY=null;
	private $FILTER=null;
	private $IPADDR=null;
	private $RULE_ID=0;
	
	function parse_template_ufdb(){
		if(!isset($_SESSION["IsKerbAuth"])){$ldap=new clladp();$_SESSION["IsKerbAuth"]=$ldap->IsKerbAuth();}
		$this->IsKerbAuth=$_SESSION["IsKerbAuth"];
		$this->q=new mysql_squid_builder();
	}
	
	private function debug($text,$line){
		if(!$GLOBALS["VERBOSE"]){return;}
		echo "<p style='color:yellow'>parse_template_ufdb: $text ( in line $line )</p>\n";
	}

	

private function client_generic(){
		if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
		$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
		if($this->RULE_ID==0){
			$this->debug("client_generic: [$FILTER]: Rule id = 0", __LINE__);
			return false;}
			
		
		$this->debug("client_generic: [$FILTER]: Find with ruleid $this->RULE_ID and adgroup='*' and category='' and username=''", __LINE__);
			$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
			WHERE category='' AND adgroup='*'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}

			
			$this->debug("client_generic: [$FILTER]: Find with ruleid $this->RULE_ID and adgroup='*' and category='$this->CATEGORY_KEY'", __LINE__);
			$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
			WHERE category='$this->CATEGORY_KEY' AND adgroup='*' AND username=''
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}
			
						
		return false;
		
		
}

private function client_get_rules_from_ldap_group_mysql($groupdn){
	if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
	$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
	
	if($this->RULE_ID>0){
		$this->debug("Find with ruleid $this->RULE_ID and adgroup='EXTLDAP:$groupdn' ", __LINE__);
		$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
		WHERE category='$this->CATEGORY_KEY' AND adgroup='EXTLDAP:$groupdn'
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$this->debug($sql, __LINE__);
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
	}
	
	$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
	WHERE category='$this->CATEGORY_KEY' AND adgroup='EXTLDAP:$groupdn'
	AND $FILTER";
	$this->debug($sql, __LINE__);
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	
	
	$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
	WHERE category='' AND adgroup='EXTLDAP:$groupdn'
	AND $FILTER";
	$this->debug($sql, __LINE__);
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	
	
	
}


private function client_get_rules_from_ldap_group(){
	if($this->client_username==null){return false;}
	$ldap=new ldap_extern();
	$hash=$ldap->GetGroupsFromuser($this->client_username);
	$GROUPS=$ldap->GetGroupsFromuser($this->client_username);
	
	while (list ($GroupDN, $none) = each ($GROUPS) ){
		
		$results=$this->client_get_rules_from_ldap_group_mysql($GroupDN);
		if($results){return $results;}
		$this->debug("Checking Group $GroupDN * * * * FALSE * * *", __LINE__);
	}
	
	return false;
}
	
	
	
private function client_groupname_all_getvalue(){	
	if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
	$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
	
	if($this->RULE_ID>0){
		$this->debug("Find with ruleid $this->RULE_ID and adgroup='*' ", __LINE__);
		$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules 
		WHERE category='$this->CATEGORY_KEY' AND adgroup='*' 
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
	}
	
	$this->debug("Find with group = *", __LINE__);
	$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
	WHERE category='$this->CATEGORY_KEY' AND adgroup='*'
	AND $FILTER";
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	}
	
	function client_ipaddr_getvalue(){
		if($this->IPADDR==null){return false;}
		if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
		$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
		
		$TR=explode(".", $this->IPADDR);
		$IP_ADDR_PRE="{$TR[0]}.{$TR[1]}.{$TR[2]}";
	
		if($this->RULE_ID>0){
			$this->debug("Find with ruleid $this->RULE_ID", __LINE__);
			$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
			FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY' AND username='$this->IPADDR'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$this->debug($sql, __LINE__);
	
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}
	
			$this->debug("Find with username $this->IPADDR AND Rule:$this->RULE_ID and category ='$this->CATEGORY_KEY'", __LINE__);
	
			$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
			FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY'
			AND username='$this->IPADDR'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}
			
			$this->debug("Find with username $this->IPADDR AND Rule:$this->RULE_ID and category =''", __LINE__);
			
			$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
			FROM ufdb_page_rules WHERE category=''
			AND username='$this->IPADDR'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}
			
			$this->debug("Find with username $IP_ADDR_PRE AND Rule:$this->RULE_ID and category ='$this->CATEGORY_KEY'", __LINE__);
			
			$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
			FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY'
			AND username='$IP_ADDR_PRE'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}
				
			$this->debug("Find with username $IP_ADDR_PRE AND Rule:$this->RULE_ID and category =''", __LINE__);
				
			$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
			FROM ufdb_page_rules WHERE category=''
			AND username='$IP_ADDR_PRE'
			AND webruleid=$this->RULE_ID
			AND $FILTER";
			$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
			$parseResults=$this->parseResults($ligne);
			if($parseResults){return $parseResults;}			
			
		}
	
	
	
		$this->debug("Find with username $this->IPADDR AND $this->CATEGORY_KEY", __LINE__);
	
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
		FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY'
		AND username='$this->IPADDR' AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
		
		
		$this->debug("Find with username only $IP_ADDR_PRE AND $this->CATEGORY_KEY", __LINE__);
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
		FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY'
		AND username='$IP_ADDR_PRE' AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}		
		
		$this->debug("Find with username only $this->IPADDR", __LINE__);
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
		WHERE category='' AND username='$this->IPADDR' AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
		
		$this->debug("Find with username only $IP_ADDR_PRE", __LINE__);
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules
		WHERE category='' AND username='$IP_ADDR_PRE' AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}		
		
		
		
		
		return false;
	}	
	

function client_username_getvalue(){
	if($this->client_username==null){return false;}
	if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
	$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
	
	if($this->RULE_ID>0){
		$this->debug("Find with ruleid $this->RULE_ID category='$this->CATEGORY_KEY' AND username='$this->client_username'", __LINE__);
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY` 
		FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY' AND username='$this->client_username' 
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$this->debug($sql, __LINE__);
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}

		$this->debug("Find with username $this->client_username AND Rule:$this->RULE_ID and category=''", __LINE__);
		$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY`
		FROM ufdb_page_rules WHERE category=''
		AND username='$this->client_username' 
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
	}
	
	
	
	$this->debug("Find with username only $this->client_username AND $this->CATEGORY_KEY", __LINE__);
	$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY` 
	FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY' 
	AND username='$this->client_username' AND $FILTER";
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	
	
	$this->debug("Find with username only $this->client_username", __LINE__);
	
	$sql="SELECT zmd5,maxtime,username,`$this->FIELD_TO_QUERY` FROM ufdb_page_rules 
	WHERE category='' AND username='$this->client_username' AND $FILTER";
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	return false;
	}
	
	
private function client_category_none(){
	if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
	$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
	
	if($this->RULE_ID>0){
		$this->debug("Find with category='' AND adgroup='*'  AND Rule:$this->RULE_ID", __LINE__);
	
		$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY` 
		FROM ufdb_page_rules WHERE category='' AND adgroup='*' 
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		$parseResults=$this->parseResults($ligne);
		if($parseResults){return $parseResults;}
	}
	$this->debug("Find [$FILTER] with category='' AND adgroup='*'", __LINE__);
	$sql="SELECT zmd5,maxtime,`$this->FIELD_TO_QUERY`
	FROM ufdb_page_rules WHERE category='' AND adgroup='*'
	AND $FILTER";
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	$parseResults=$this->parseResults($ligne);
	if($parseResults){return $parseResults;}
	
}

private function parseResults($ligne){
	if($ligne["zmd5"]==null){return;}
	$this->debug("MATCHES !!!", __LINE__);
	if($this->RETURNED==2){
			$this->debug("MATCHES return {$ligne[$this->FIELD_TO_QUERY]} !!!", __LINE__);
			$GLOBALS["RULE_MAX_TIME"]=$ligne["maxtime"];
			return $ligne[$this->FIELD_TO_QUERY];
	}
	if($ligne[$this->FIELD_TO_QUERY]==1){
		$this->debug("MATCHES !!! return $this->RETURNED", __LINE__);
		$GLOBALS["RULE_MAX_TIME"]=$ligne["maxtime"];
		return $this->RETURNED;
	}

}


private function IsKerbAuth_parse($ligne){
	if($ligne["zmd5"]==null){return false;}
	if($ligne["adgroup"]==null){return false;}
	if(!$this->parseTemplate_if_in_ADGroup($ligne["adgroup"],$this->client_username)){return false;}
	return $this->parseResults($ligne);
	

}


private function IsKerbAuth(){
	if(!$_SESSION["IsKerbAuth"]){return false;}
	if($this->client_username==null){return false;}
	if($this->FIELD_TO_QUERY=="addTocat"){$this->FILTER="LENGTH(addTocat)>0"; $this->RETURNED=2; }
	$FILTER="`$this->FIELD_TO_QUERY`=$this->VALUE_TO_QUERY";
	
	if($this->RULE_ID>0){
		$this->debug("Find with adgroup with $this->CATEGORY_KEY AND Rule:$this->RULE_ID", __LINE__);
		$sql="SELECT zmd5,maxtime,adgroup,`$this->FIELD_TO_QUERY` 
		FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY' 
		AND webruleid=$this->RULE_ID
		AND $FILTER";
		$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
		if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
		$parseResults=$this->IsKerbAuth_parse($ligne);
		if($parseResults){return $parseResults;}
	}
	
	$this->debug("Find with adgroup with $this->CATEGORY_KEY", __LINE__);
	$sql="SELECT zmd5,maxtime,adgroup,`$this->FIELD_TO_QUERY`
	FROM ufdb_page_rules WHERE category='$this->CATEGORY_KEY'
	AND $FILTER";
	$ligne=mysql_fetch_array($this->q->QUERY_SQL($sql));
	if(!$this->q->ok){$this->debug($this->q->mysql_error_html, __LINE__);return false;}
	$parseResults=$this->IsKerbAuth_parse($ligne);
	if($parseResults){return $parseResults;}	
	return false;
}


 private function parseTemplate_if_in_ADGroup($adgroup,$client_username){


	

	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	if(!isset($_SESSION["GROUPES_IN_AD"])){
		$ad=new external_acl_squid_ldap();
		$groupes=$ad->ADLdap_getgroups($client_username);
		while (list ($GroupName, $none) = each ($groupes) ){
			$GROUPES_IN_AD[trim(strtolower($GroupName))]=true;
		}
		$_SESSION["GROUPES_IN_AD"]=$GROUPES_IN_AD;
	}else{
		$GROUPES_IN_AD=$_SESSION["GROUPES_IN_AD"];
	}


	if(strpos($adgroup, ",")>0){
		$QueriesGroups=explode(",",$adgroup);
	}else{
		$QueriesGroups[]=$adgroup;
	}

	while (list ($none, $GroupName) = each ($QueriesGroups) ){
		$GROUPES_TO_AD[trim(strtolower($GroupName))]=true;
	}

	while (list ($GroupName,$none ) = each ($GROUPES_TO_AD) ){
		if(isset($GROUPES_IN_AD[$GroupName])){return true;}
	}



}


function parseTemplate_unlock_privs($ARRAY,$pattern="deny=1",$tokenSource,$debug=false){
	
	$HOST=$ARRAY["HOST"];
	$URL=$ARRAY["URL"];
	$IPADDR=$ARRAY["IPADDR"];
	$REASONGIVEN=$ARRAY["REASONGIVEN"];
	$CATEGORY_KEY=$ARRAY["CATEGORY_KEY"];
	$_CATEGORIES_K=$ARRAY["CATEGORY"];
	$_RULE_K=$ARRAY["RULE"];
	$targetgroup=$ARRAY["targetgroup"];
	$RULE_ID=$ARRAY["RULE_ID"];
	$IpToUid=$ARRAY["IpToUid"];
	$SquidGuardIPWeb=base64_decode($_GET["SquidGuardIPWeb"]);
	$client_username=trim($ARRAY["clientname"]);
	$unlock_file=null;
	$RETURNED=2;
	if(preg_match("#(.+?)=([0-9]+)#", $pattern,$re)){
		$FIELD_TO_QUERY=$re[1];
		$VALUE_TO_QUERY=$re[2];
	}

	if($FIELD_TO_QUERY=="deny"){$RETURNED=0;}
	if($FIELD_TO_QUERY=="allow"){$RETURNED=1;}
	if($FIELD_TO_QUERY=="noauth"){$RETURNED=1;}
	
	$DisableSquidGuardHTTPCache=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableSquidGuardHTTPCache"));
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>FIELD_TO_QUERY == &laquo;$FIELD_TO_QUERY&raquo;,return =&laquo;$RETURNED&raquo;</span><br>\n";}

	$this->RULE_ID=$RULE_ID;
	$this->RETURNED=$RETURNED;
	$this->client_username=$client_username;
	$this->FIELD_TO_QUERY=$FIELD_TO_QUERY;
	$this->VALUE_TO_QUERY=$VALUE_TO_QUERY;
	$this->CATEGORY_KEY=$CATEGORY_KEY;
	$this->IPADDR=$IPADDR;
	
	if(!$GLOBALS["VERBOSE"]){
		if($DisableSquidGuardHTTPCache==0){
			$md5=md5("$RULE_ID$RETURNED$client_username$FIELD_TO_QUERY$VALUE_TO_QUERY$CATEGORY_KEY$IPADDR");
			$unlock_file="/home/squid/error_page_cache/$md5.unlock";
			if(is_file($unlock_file)){
				if($this->parseTemplate_file_time_min($unlock_file)<10){
					$array=unserialize(@file_get_contents($unlock_file));
					return $array["RESULTS"];
				}
			}
		}
	}
	
	
	
	
	
	
	
	$results=$this->client_generic();
	if(!$results){$this->debug("client_generic() return false", __LINE__);}
	else{
		$arrayR["RESULTS"]=$results;
		if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
		return $results;
	}
	
	$results=$this->client_username_getvalue();
	if(!$results){$this->debug("client_username_getvalue() return false", __LINE__);}
	else{
		$arrayR["RESULTS"]=$results;
		if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
		return $results;
	}
	
	$sock=new sockets();
	if($sock->SQUID_IS_EXTERNAL_LDAP()){
		$this->debug("Is an external LDAP", __LINE__);
		$results=$this->client_get_rules_from_ldap_group();
		if(!$results){$this->debug("client_get_rules_from_ldap_group() return false", __LINE__);}
		else{
			$arrayR["RESULTS"]=$results;
			if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
			return $results;
		}
	}
	
	
	$results=$this->client_groupname_all_getvalue();
	if(!$results){$this->debug("client_groupname_all_getvalue() return false", __LINE__);}
	else{
		$arrayR["RESULTS"]=$results;
		if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
		return $results;
	}
	$results=$this->IsKerbAuth();
	if(!$results){$this->debug("IsKerbAuth() return false", __LINE__);}
	else{
		$arrayR["RESULTS"]=$results;
		if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
		return $results;
	}
	$results=$this->client_category_none();
	if(!$results){$this->debug("client_category_none() return false", __LINE__);}
	else{
		$arrayR["RESULTS"]=$results;
		if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
		return $results;
	}
	

	
	$this->debug("No rule match return &laquo;$tokenSource&raquo;", __LINE__);
	$arrayR["RESULTS"]=$tokenSource;
	if($unlock_file<>null){@file_put_contents($unlock_file, serialize($arrayR));}
	return $tokenSource;

}
private function parseTemplate_file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}

	$data1 = filemtime($path);
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

}