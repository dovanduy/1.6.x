<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--dump"){dump();exit;}


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.artica-quotas.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.artica-quotas.progress",0755);

}


function dump(){
	print_r(unserialize(@file_get_contents("/etc/squid3/artica.quotas.rules.db")));
	
}


function build(){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$array=array();
	build_progress("Building rules...",15);
	
	$sql="SELECT * FROM webfilter_quotas WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress("{failed}",110);
		echo $q->mysql_error;
		sleep(3);
		return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$groupname=$ligne["groupname"];
		build_progress($groupname,30);
		$ID=$ligne["ID"];
		$quotasize=$ligne["quotasize"];
		$quotaPeriod=$ligne["quotaPeriod"];
		$AllSystems=$ligne["AllSystems"];
		
		$array[$ID]["quotasize"]=$quotasize;
		$array[$ID]["quotaPeriod"]=$quotaPeriod;
		$array[$ID]["AllSystems"]=$AllSystems;
		
		$sql="SELECT category FROM webfilters_quotas_blks WHERE webfilter_id='$ID'";
		$results2=$q->QUERY_SQL($sql);
		if(!$q->ok){
			build_progress("{failed}",110);
			echo $q->mysql_error;
			sleep(3);
			return;
		}
		
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			echo "Category {$ligne2["category"]}\n";
			$array[$ID]["categories"][$ligne2["category"]]=true;
				
		}
		
		$array[$ID]["GROUPS"]=DUMP_GROUPS($ID);
		
		
		

	}
	build_progress("Saving configuration ".count($array)." {rules}",50);
	@unlink("/etc/squid3/artica.quotas.rules.db");
	if(count($array)>0){
		@file_put_contents("/etc/squid3/artica.quotas.rules.db", serialize($array));
		@chown("/etc/squid3/artica.quotas.rules.db","squid");
	}
	build_progress("Saving configuration",90);
	
	build_progress("{reloading_proxy_configuration}",95);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	squid_admin_mysql(1, "Reloading configuration to apply quotas settings", null,__FILE__,__LINE__);
	system("$squidbin -k reconfigure");
	
	sleep(5);
	build_progress("{success}",100);
	print_r($array);
}

function DUMP_GROUPS($ruleid){
	
	$q=new mysql_squid_builder();
	$sql="SELECT webfilter_group.* FROM webfilter_group,webfilter_assoc_quota_groups
	WHERE webfilter_assoc_quota_groups.group_id=webfilter_group.ID
	AND webfilter_assoc_quota_groups.webfilter_id=$ruleid
	AND webfilter_group.enabled=1";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress("{failed}",110);
		echo $q->mysql_error;
		sleep(3);
	}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$groupname=$ligne["groupname"];
		echo "Dump $groupname ";
		
		if($ligne["localldap"]==0){
			if($ligne["dn"]<>null){
				$squid=new squidbee();
				echo "External OpenLDAP Group {$ligne["dn"]}\n";
				$arrayGROUPS["EXTLDAP"][]=array("DN"=>$ligne["dn"],"CONF"=>$squid->EXTERNAL_LDAP_AUTH_PARAMS);
				continue;
			}
			echo "OpenLDAP Group {$ligne["gpid"]}\n";
			$arrayGROUPS["LDAP"][]=$ligne["gpid"];
			continue;
		}
			
		if($ligne["localldap"]==2){
			if(preg_match("#AD:[0-9]+:(.+)#", $ligne["dn"],$re)){
				$EXP=base64_decode($re[1]);
				echo "ActiveDirectory Group $EXP\n";
				$arrayGROUPS["AD"][]=$EXP;
				continue;
			}
				
			$DN=$ligne["dn"];
			echo "ActiveDirectory Group DN: $DN\n";
			$arrayGROUPS["AD"][]=$DN;
			continue;
		}
	
		$sql="SELECT * FROM webfilter_members WHERE enabled=1 AND groupid={$ligne["ID"]}";
		$results2=$q->QUERY_SQL($sql);
		echo "$groupname({$ligne["ID"]}) webfilter_members = ". mysql_num_rows($results2)." items\n";
			while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
				if(trim($ligne2["pattern"])==null){continue;}
				echo "$groupname {$ligne2["pattern"]}={$ligne2["membertype"]}\n";
				if($ligne2["membertype"]==0){
					$arrayGROUPS["FREE"]["IP"][]=$ligne2["pattern"];
					continue;
				}
				if($ligne2["membertype"]==2){
					$arrayGROUPS["FREE"]["IP"][]=$ligne2["pattern"];
				}
				
				if($ligne2["membertype"]==1){
					$arrayGROUPS["FREE"]["USER"][]=$ligne2["pattern"];
					continue;
				}
			}
		}
	return $arrayGROUPS;
	
}	



