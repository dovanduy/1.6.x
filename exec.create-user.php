<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.pure-ftpd.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/charts.php");
include_once(dirname(__FILE__)."/ressources/class.mimedefang.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");

if($argv[1]=="--create"){create_user($argv[2]);die();}

function create_user($filename){
	$tpl=new templates();
	$unix=new unix();
	$path="/usr/share/artica-postfix/ressources/logs/web/create-users/$filename";
	$MAIN=unserialize(base64_decode(@file_get_contents($path)));
	
	$users=new user($MAIN["login"]);
	if($users->password<>null){
		writelogs("User already exists {$MAIN["login"]} ",__FUNCTION__,__FILE__);
		echo '{account_already_exists}';
		@unlink($path);
		exit;
	}
	$ou=$MAIN["ou"];
	$password=url_decode_special_tool($MAIN["password"]);
	$MAIN["firstname"]=url_decode_special_tool($MAIN["firstname"]);
	$MAIN["lastname"]=url_decode_special_tool($MAIN["lastname"]);
	 
	 
	if(trim($MAIN["internet_domain"])==null){$MAIN["internet_domain"]="localhost.localdomain";}
	writelogs("Add new user {$MAIN["login"]} {$MAIN["ou"]} {$MAIN["gpid"]}",__FUNCTION__,__FILE__);
	$users->ou=$MAIN["ou"];
	$users->password=url_decode_special_tool($MAIN["password"]);
	$users->mail="{$MAIN["email"]}@{$MAIN["internet_domain"]}";
	$users->DisplayName="{$MAIN["firstname"]} {$MAIN["lastname"]}";
	$users->givenName=$MAIN["firstname"];
    $users->sn=$MAIN["lastname"];
    $users->group_id=$MAIN["gpid"];
	      
	if($MAIN["ByZarafa"]=="yes"){
		$zarafa_admin=$unix->find_program("zarafa-admin");
		$nohup=$unix->find_program("nohup");
		
		if(isset($MAIN["ZARAFA_LANG"])){
			$users->SaveZarafaMbxLang($MAIN["ZARAFA_LANG"]);
			$langcmd=" --lang {$MAIN["ZARAFA_LANG"]} ";
		}
		$ldap=new clladp();
	    $dn="ou={$MAIN["ou"]},dc=organizations,$ldap->suffix";
	    $upd["objectClass"]="zarafa-company";
     	$upd["cn"]=$MAIN["ou"];
	    if(!$ldap->Ldap_add_mod("$dn",$upd)){echo $ldap->ldap_last_error; @unlink($path);return; }
	    $cmd="$nohup $zarafa_admin $langcmd--create-store {$MAIN["login"]} >/dev/null 2>&1 &";
	    shell_exec(trim($cmd));
	}
	      
	      
	      
if(is_numeric($MAIN["gpid"])){
	$gp=new groups($MAIN["gpid"]);
	writelogs( "privileges: {$MAIN["gpid"]} -> AsComplexPassword = \"{$gp->Privileges_array["AsComplexPassword"]}\"", __FUNCTION__, __FILE__, __LINE__ );
	if($gp->Privileges_array["AsComplexPassword"]=="yes"){
		$ldap=new clladp();
		$hash=$ldap->OUDatas($ou);
		$privs=$ldap->_ParsePrivieleges($hash["ArticaGroupPrivileges"],array(),true);
		$policiespwd=unserialize(base64_decode($privs["PasswdPolicy"]));
		if(is_array($policiespwd)){
			$priv=new privileges();
		    	if(!$priv->PolicyPassword($password,$policiespwd)){
		     	echo "Need complex password";@unlink($path);return;
		     }
		    }
	 }
}
	     		 
if(!$users->add_user()){echo $users->error."\n".$users->ldap_error;@unlink($path);return;}
if($MAIN["ByZarafa"]=="yes"){
 $sock=new sockets();
 $sock->getFrameWork("cmd.php?zarafa-hash=yes&rebuild=yes");
}
@unlink($path);
	
		
	
	
}


