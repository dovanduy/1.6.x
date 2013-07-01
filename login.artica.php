<?php



$uamsecret  = 'secret'; 
$username   = $_POST['username'];
$password   = $_POST['password'];
$challenge  = $_POST['challenge'];
$redir	    = $_POST['userurl'];
$server_ip  = $_POST['uamip'];
$port       = $_POST['uamport'];
$mac 		= $_POST['mac'];
$ip			= $_POST['ip'];

$ClientIP=$_SERVER["REMOTE_ADDR"];

if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
	if($_SERVER["HTTP_X_FORWARDED_FOR"]<>null){
		$ClientIP=$_SERVER["HTTP_X_FORWARDED_FOR"];
	}
}
if(isset($_SERVER["HTTP_X_REAL_IP"])){
	if($_SERVER["HTTP_X_REAL_IP"]<>null){
		$ClientIP=$_SERVER["HTTP_X_REAL_IP"];
	}
}

if($ip<>null){
	$ClientIP=$ip;
}


events("HTTP_X_FORWARDED_FOR: {$_SERVER["HTTP_X_FORWARDED_FOR"]}: HTTP_X_REAL_IP:{$_SERVER["HTTP_X_REAL_IP"]}: username = $username password = **** ".strlen($password)." bytes");
if(ConnectToAD($username,$password)){
	$username_enc=urlencode($username);
	$ClientIP=urlencode($ClientIP);
	$redirUrl=urldecode($redir);
	events("authorize $username_enc -> `$redirUrl`");
	fopen_framework("chilli.php?authorize=yes&user=$username_enc&ClientIP=$ClientIP&mac=$mac");
	header("Location: $redirUrl");
	exit;
	
}




if( array_key_exists('remember',$_POST)){
	$Year = (2592000*12) + time();
	setcookie("hs[username]",   $username, $Year);
	setcookie('hs[password]',        $password, $Year);
}


if (preg_match("/1\.0\.0\.0/i", $redir)) {

	$default_site = 'google.com';
	$pattern = "/1\.0\.0\.0/i";
	$redir = preg_replace($pattern, $default_site, $redir);
}

$enc_pwd    = return_new_pwd($password,$challenge,$uamsecret);
//$dir		= '/json/logon';
$dir		= '/logon';
$target     = "http://$server_ip".':'.$port.$dir."?username=$username&password=$enc_pwd&userurl=$redir";

events($target);
header("Location: $target");

//Function to do the encryption thing of the password
function return_new_pwd($pwd,$challenge,$uamsecret){
	$hex_chal   = pack('H32', $challenge);                  //Hex the challenge
	$newchal    = pack('H*', md5($hex_chal.$uamsecret));    //Add it to with $uamsecret (shared between chilli an this script)
	$response   = md5("" . $pwd . $newchal);              //md5 the lot
	$newpwd     = pack('a32', $pwd);                //pack again
	$password   = implode ('', unpack('H32', ($newpwd ^ $newchal))); //unpack again
	return $password;
}

function events($text){
	$logFile="logon.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>100000){unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	
	@fwrite($f, "$text\n");
	@fclose($f);	
	
	
}

function fopen_framework($uri){
	$fp = @fopen ("http://127.0.0.1:47980/$uri", "r");
	events("http://127.0.0.1:47980/$uri");

	if (!$fp) {
		events("ERROR: unable to open remote file http://127.0.0.1:47980/$uri $errno $errstr");
		return false;
	}

	while (!feof ($fp)) {$line =$line .  fgets ($fp, 1024) . "\n";}
	fclose($fp);
	return $line;

}
function ConnectToAD($username,$password){
	
	$ChilliConf=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ChilliConf")));
	if($ChilliConf["EnableActiveDirectory"]==0){return false;}
	$AD_DOMAIN=$ChilliConf["AD_DOMAIN"];
	
	define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
	
	
	events("ldap_connect({$ChilliConf["AD_SERVER"]},{$ChilliConf["AD_PORT"]})...");
	$cnx=@ldap_connect($ChilliConf["AD_SERVER"],$ChilliConf["AD_PORT"]);
	
	if(!$cnx){
		events("Fatal: ldap_connect({$ChilliConf["AD_SERVER"]},{$ChilliConf["AD_PORT"]} ) Check your configuration...");
		@ldap_close();
		return false;
	}
	
	events("OK: ldap_connect({$ChilliConf["AD_SERVER"]},{$ChilliConf["AD_PORT"]} ) SUCCESS");
	@ldap_set_option($cnx, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($cnx, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($cnx, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($cnx, LDAP_OPT_REFERRALS, 0);
	events("Check ident $username@$AD_DOMAIN $password");
	$bind=@ldap_bind($cnx, "$username@$AD_DOMAIN", $password);
	
	if(!$bind){
		$errn=ldap_errno($cnx);
		$error="Error $errn: ".ldap_err2str ($errn);
		if (@ldap_get_option($cnx, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error." $extended_error";
			
		}
		events("$error");
		return false;
	}
	events("Active Directory session  SUCCESS");
	return true;
	
}


