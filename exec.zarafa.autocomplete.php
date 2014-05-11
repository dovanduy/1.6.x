<?php


ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
if($GLOBALS["VERBOSE"]){ echo "starting include functions done..\n";}


@mkdir("/root/properties",0755,true);
$ldap=new clladp();
$q=new mysql();
$suffix=$ldap->suffix;
$arr=array("uid");
$sr = @ldap_search($ldap->ldap_connection,"dc=organizations,$suffix",'(objectclass=userAccount)',$arr);
if ($sr) {
        $hash=ldap_get_entries($ldap->ldap_connection,$sr);
        for($i=0;$i<$hash["count"];$i++){
                $user=$hash[$i]["uid"][0];
                $sql="select val_string from properties where properties.tag=0x6771 and hierarchyid=(select hierarchy_id from stores where user_name='$user');";
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"zarafa"));
				$val_string=x_mysql_escape_string2($ligne["val_string"]);
				$cmd="update properties set val_string='$val_string' where properties.tag=0x6771 and hierarchyid=(select hierarchy_id from stores where user_name='$user');";
				@file_put_contents("/root/properties/$user.sql", $cmd);
				$f[]=$cmd;
        }

}


@file_put_contents("/root/properties/ALL.sql",@implode("\n", $f));


function x_mysql_escape_string2($line){

	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}