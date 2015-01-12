<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MAIN_PATH"]="/usr/share/artica-postfix/ressources/conf/meta";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--group"){sync_policies_group($argv[2]);exit;}
if($argv[1]=="--policy"){sync_policy($argv[2]);exit;}


function sync_policies_group($gpid){
	$q=new mysql_meta();
	$sql="SELECT policies.ID FROM policies,metapolicies_link WHERE metapolicies_link.`policy-id`=policies.ID AND metapolicies_link.gpid=$gpid";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_admin_mysql(0, "Fatal error: Mysql Error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		meta_events("Building Policy ID: {$ligne["ID"]}");
		$content=BuildPolicy($ligne["ID"]);
		if(!$content){
			meta_admin_mysql(0, "Warning invalid Policy {$ligne["ID"]}", "No Array",__FILE__,__LINE__);
			continue;
		}
		
		Replicate_policy_to_group($gpid,$content);
		
		
	}
}

function sync_policy($policy_id){
	$content=BuildPolicy($policy_id);
	if(!$content){
		meta_admin_mysql(0, "Warning invalid Policy $policy_id", "No Array",__FILE__,__LINE__);
		continue;
	}
	
	$q=new mysql_meta();
	$sql="SELECT gpid FROM metapolicies_link WHERE `policy-id`='$policy_id'";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_admin_mysql(0, "Fatal error: Mysql Error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		Replicate_policy_to_group($gpid,$content);
	}
	
}


function BuildPolicy($policy_id){
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM policies WHERE ID='$policy_id'"));
	
	$policy_name=$ligne["policy_name"];
	$policy_type=$ligne["policy_type"];
	
	$ARRAY["policy_name"]=$policy_name;
	$ARRAY["policy_type"]=$policy_type;
	
	$sql="SELECT `policy_value`,`policy_key` FROM `policies_content` WHERE policy_id='$policy_id'";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_admin_mysql(0, "Fatal error: Mysql Error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	if(mysql_num_rows($results)==0){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ARRAY["policy_content"][$ligne["policy_key"]]=$ligne["policy_value"];
		
	}
	
	return base64_encode(serialize($ARRAY));
	
}

function Replicate_policy_to_group($gpid,$content){
	
	$q=new mysql_meta();
	
	$sql="SELECT uuid FROM metagroups_link WHERE gpid='$gpid'";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_admin_mysql(0, "Fatal error: Mysql Error", $q->mysql_error."\n$sql",__FILE__,__LINE__);
		return;
	}
	
	$md5Content=md5($content);
	
	$content=mysql_escape_string2($content);
	
	if(mysql_num_rows($results)==0){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$md5=md5("$md5Content$uuid");
		$q->QUERY_SQL("DELETE FROM `policies_storage` WHERE `zmd5`='$md5'");
		$sql="INSERT IGNORE INTO `policies_storage` (zmd5,uuid,policy_content) VALUES ('$md5','$uuid','$content')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			meta_admin_mysql(0, "Fatal error: Mysql Error", $q->mysql_error."\n$sql",__FILE__,__LINE__);
			continue;
		}
		
		$q->CreateOrder($uuid, "POLICY",array("VALUE"=>$md5));
		
	}
	
}


function meta_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){$file=basename($trace[0]["file"]); $function=$trace[0]["function"]; $line=$trace[0]["line"]; }
		if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];}
	}
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);

}
