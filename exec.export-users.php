<?php
include(dirname(__FILE__).'/ressources/class.qos.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.http.pear.inc');
$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid)){		
	$ptime=$unix->PROCESS_TTL($pid);
	die();
}


if($argv[1]=='--org'){export($argv[2],$argv[3]);}
if($argv[1]=='--upload'){export_ou_http($argv[2],$argv[3]);}

function export_ou_http($ou,$session){
	$sock=new sockets();
	$ldap=new clladp();
	$path="/root";
	echo "Exporting meta informations session $session for ou=`$ou`\n";
	export($ou,$path);
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	echo "Reading session $session for ou=`$ou`\n";
	if($ou==null){echo "Failed, no such ou set....\n";die();}
	$ini->loadString($sock->GET_INFO($session));
	
	$filepath="$path/$ou.gz";
	if(!is_file($filepath)){
		echo "$filepath no such file";
	}
	
	echo "Uploading to https://{$ini->_params["CONF"]["servername"]}:{$ini->_params["CONF"]["port"]}\n";
	$uri="https://{$ini->_params["CONF"]["servername"]}:{$ini->_params["CONF"]["port"]}/cyrus.murder.listener.php";
	$command="?export-ou=yes&admin={$ini->_params["CONF"]["username"]}&pass={$ini->_params["CONF"]["password"]}&original-suffix=$ldap->suffix";	
	//lic.users.import.php
	$http=new httpget();
	$http->uploads["EXPORT-OU"]="$filepath";
	$body=$http->send("https://{$ini->_params["CONF"]["servername"]}:{$ini->_params["CONF"]["port"]}/cyrus.murder.listener.php",
	"post",
		array(
			"AUTH"=>base64_encode(serialize($ini->_params["CONF"])),
			"ORG"=>$ou
		)
	);
	@unlink($filepath);	
	echo $body;
	
	
	
	
}

function export($ou,$path){
	@file_put_contents($pidfile,getmypid());
	//if(strlen($GLOBALS["USER_QUERY"])>0){$filter="(uid={$GLOBALS["USER_QUERY"]})";}
	
$ldap=new clladp();
$pattern="(&(objectclass=userAccount)$filter)";
$attr=array();
$sr =@ldap_search($ldap->ldap_connection,"ou=$ou,dc=organizations,$ldap->suffix",$pattern,$attr);
$hash=ldap_get_entries($ldap->ldap_connection,$sr);
$unix=new unix();
$gzip=$unix->find_program("gzip");
$users_array=array();

if(is_array($hash)){
	for($i=0;$i<$hash["count"];$i++){
		$usersArray[]=$hash[$i]["uid"][0];
		
	}
}


	if(is_array($usersArray)){
			while (list ($index, $uid) = each ($usersArray) ){
				echo "Parsing $uid\n";
				$u=new user($uid);
				$array_user=array();
				foreach($u as $key => $value) {$array_user[$key]=$value;}
				$array_users_final[]=$array_user;
				unset($array_user);
			}
	}
	
	
	$groups=$ldap->hash_groups($ou,1);
	
	while (list ($num, $line) = each ($groups)){
		echo "Parsing group $num $line\n";
		$u=new groups($num);
		$array_group=array();
		foreach($u as $key => $value) {$array_group[$key]=$value;}
		$array_group_final[]=$array_group;
		unset($array_group);
	}
	
	
	$array_final["USERS"]=$array_users_final;
	$array_final["GROUPS"]=$array_group_final;
	
	
	$tempfile=$unix->FILE_TEMP();
	$datas=base64_encode(serialize($array_final));
	@file_put_contents($tempfile,$datas);
	if(is_dir($path)){
		compress($tempfile,$path."/$ou.gz");
		echo "Saved in ".$path."/$ou.gz\n";
		@chmod($path."/$ou.gz",0755);
	}else{
		echo $path." no such directory...\n";
	}
	
	@unlink($tempfile);
	
}
	
	
	
function compress($source,$dest){
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
}	