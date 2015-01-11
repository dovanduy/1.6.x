<?php
$GLOBALS["HOTSPOT_DEBUG"]=true;
$dirname=dirname(__FILE__)."/";
include_once($dirname.'ressources/class.templates.inc');
include_once($dirname.'ressources/class.ldap.inc');
include_once($dirname.'ressources/class.users.menus.inc');
include_once($dirname.'ressources/class.squid.inc');
include_once($dirname.'ressources/class.tcpip.inc');
include_once($dirname.'ressources/class.system.nics.inc');
include_once($dirname.'ressources/externals/adLDAP/adLDAP.php');
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');
if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
}

if($argv[1]=="--templates"){wifidog_templates();die();}
$sock=new sockets();
$WifiDogDebugLevel=intval($sock->GET_INFO("WifiDogDebugLevel"));
if($WifiDogDebugLevel>0){$GLOBALS["HOTSPOT_DEBUG"]=true;}

if($GLOBALS["HOTSPOT_DEBUG"]){while (list ($num, $ligne) = each ($_REQUEST) ){$URIZ[]="$num=$ligne";}ToSyslog("Receive ".@implode(";", $URIZ));}
if(isset($_POST["wifidog-terms"])){wifidog_login();exit;}
if(isset($_POST["confirm-password"])){wifidog_password_perform();exit;}
if(isset($_POST["register-recover"])){wifidog_recover_perform();exit;}
if(isset($_POST["register-member"])){wifidog_register_perform();exit;}
if(isset($_GET["wifidog-login"])){wifidog_login();exit;}
if(isset($_GET["wifidog-ping"])){wifidog_pong();exit;}
if(isset($_GET["wifidog-portal"])){wifidog_portal();exit;}
if(isset($_GET["wifidog-auth"])){wifidog_auth();exit;}
if(isset($_GET["wifidog-register"])){wifidog_register();exit;}
if(isset($_GET["wifidog-recover"])){wifidog_recover();exit;}
if(isset($_GET["wifidog-password"])){wifidog_password();exit;}
if(isset($_GET["wifidog-confirm"])){wifidog_confirm();exit;}



if(isset($_POST["username"])){wifidog_authenticate();exit;}


if(isset($_GET["css"])){css();exit;}

if(isset($_GET["endusers"])){endusers_load();exit;}
if(isset($_GET["jsload"])){js_load();exit;}
if(isset($_GET["imgload"])){imgload();exit;}

session_start();
if(isset($_POST["register-password"])){register_save();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tweaks"])){tweaks();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["popup"])){popup();exit;}

none_page();

function wifidog_pong(){
	$q=new mysql_squid_builder();
	$MAIN=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
	if(count($MAIN["SESSIONS"])>0){
		$MySQLSessions=$q->COUNT_ROWS("hotspot_sessions");
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_pong:: ".count($MAIN["SESSIONS"]) ."/$MySQLSessions exists");}
		if($MySQLSessions==0){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_pong:: Clean all sessions");}
			$sock=new sockets();
			$sock->getFrameWork("hotspot.php?clean-all-sessions=yes");
		}
			
		
	}

	echo "Pong\r\n";
}


//hotspot.php?wifidog-auth=yes&stage=login&ip=192.168.1.18&mac=00:15:5d:01:09:07&token=289a95d50c49c9ce202e4ee349389703&incoming=0&outgoing=0&gw_id=000C291B3AC4
// http://wiki.gergosnet.com/index.php/Installation%2Bclient%2Bwifidog%2Bsur%2BDebian
// iptables -t nat -I WiFiDog_eth0_WIFI2Internet -i eth0 -m mark --mark 0x2 -p tcp --dport 443 -j REDIRECT --to-port 63924
//https://192.168.1.204:9000/portal/?gw_id=000C291B3AC4


// iptables -t nat -I WiFiDog_eth0_Unknown -p tcp -m tcp --dport 443 -j REDIRECT --to-ports 55191 -> A faire
//


function none_page(){
	$tpl=new templates();
	$sock=new sockets();
	$text_redirecting=$sock->GET_INFO("ArticaSplashHotSpotRedirectText");
	if($text_redirecting==null){$text_redirecting=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}:");}
	
	$text_form="
	<div style='width:98%' class=form>		
	<center>
			<div style='font-size:32px'><center>$text_redirecting<br>http://{$_SERVER["SERVER_NAME"]}</center></div>
			<img src='img/wait_verybig_mini_red.gif'></center></div>";
	
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"1; URL=http://{$_SERVER["SERVER_NAME"]}\">");
	
	
	
	
}


function wifidog_auth(){
	$token=$_GET["token"];
	$ARP=$_GET["mac"];
	$mac=$_GET["mac"];
	$ip=$_GET["ip"];
	$stage=$_GET["stage"];
	$token=$_GET["token"];
	$incoming=$_GET["incoming"];
	$outgoing=$_GET["outgoing"];
	$q=new mysql_squid_builder();
	///hotspot.php?wifidog-auth=yes&stage=counters&ip=192.168.1.19&mac=00:15:5d:01:09:06&token=token&incoming=7161&outgoing=10255&gw_id=000C29D5571C
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth:: Receiving stage $stage");}
	
	if($stage=="logout"){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
		$username=$ligne["username"];
		events(1,"LOGOFF $username/$mac",null);
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `MAC`='$mac'");
	}
	
	
	if($stage=="counters"){
		$incoming=$incoming/1024;
		$outgoing=$outgoing/1024;
		
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth:: -> wifidog_auth_auto_created()");}
		if(wifidog_auth_auto_created($token)){
			$q->QUERY_SQL("UPDATE hotspot_sessions SET `incoming`='$incoming',`outgoing`='$outgoing',`ipaddr`='$ip' WHERE `md5`='$token'");
			return;
		}
		
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth:: -> wifidog_is_end_of_life()");}
		if(wifidog_is_end_of_life($token)){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth:: * * * COUNTER: MAC: $mac, Token $token [End-Of-Life] * * *");}
			events(1,"COUNTER: MAC: $mac, Token $token [End-Of-Life]",null);
			echo "Auth: -1\n";
			echo "Messages: No session saved\n";
			return;
		}
		
		$q->QUERY_SQL("UPDATE hotspot_sessions SET `incoming`='$incoming',`outgoing`='$outgoing',`ipaddr`='$ip' WHERE `md5`='$token'");
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
	if(wifidog_auth_auto_created($token)){return;}
	
	
	if($ligne["logintime"]==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth:: * * * logintime = 0  No session saved * * *");}
		echo "Auth: -1\n";
		echo "Messages: No session saved\n";
		return;
	}
/*	0 - AUTH_DENIED - User firewall users are deleted and the user removed.
	6 - AUTH_VALIDATION_FAILED - User email validation timeout has occured and user/firewall is deleted
	1 - AUTH_ALLOWED - User was valid, add firewall rules if not present
	5 - AUTH_VALIDATION - Permit user access to email to get validation email under default rules
	-1 - AUTH_ERROR - An error occurred during the validation process
*/	
	
	events(2,"LOGON MAC: $mac, Token $token",null);
	echo "Auth: 1\n";
	echo "Messages: OK\n";

}

function wifidog_auth_auto_created($token){
	$q=new mysql_squid_builder();
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/wifidog_auth_auto_created::SELECT * FROM hotspot_sessions WHERE `md5`='$token'");}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
	$username=$ligne["username"];
	
	if($ligne["autocreate"]==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/wifidog_auth_auto_created:: $token/$username * * * autocreate = 0");}
		return false;
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_members WHERE `uid`='$username'"));
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth_auto_created::\"$username\", Token:$token, autocreate_confirmed:{$ligne["autocreate_confirmed"]}");}
	
	
	if($ligne["autocreate_confirmed"]==1){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth_auto_created::\"$username\", Token:$token autocreate=1, Confirmed = yes...");}
		return false;
	}
	
	$time=time();
	$autocreate_maxttl=$ligne["autocreate_maxttl"];
	$autocreate_maxttl_sec=intval($autocreate_maxttl)*60;
	$creationtime=$ligne["creationtime"];
	$NextCheck = $creationtime + $autocreate_maxttl_sec;
	$reste=($NextCheck-$time)/60;
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth_auto_created::\"$username\", Token:$token, autocreate_maxttl:{$autocreate_maxttl}Mn creationtime = $creationtime for $NextCheck {$reste}Mn,checks hotspot_members");}
	
	
	if($time>$NextCheck){
		echo "Auth: -1\n";
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth_auto_created:: \"$username\", Token:$token Messages: AUTH_VALIDATION_FAILED - Timed out $time>$NextCheck");}
		echo "Messages: AUTH_VALIDATION_FAILED\n";
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		$q->QUERY_SQL("DELETE FROM hotspot_members WHERE `uid`='$username'");
		return true;
	}
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth_auto_created:: Messages: AUTH_VALIDATION_FAILED - still wait validation");}
	echo "Auth: 1\n";
	echo "Messages: AUTH_VALIDATION\n";
	return true;
	
}

function wifidog_templates(){
	@file_put_contents("/usr/local/etc/wifidog-msg.html",BuildFullPage(null,"<h2>\$title</h2><p>\$message</p>"));
	
}



function  wifidog_terms(){
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];	
	$page=CurrentPageName();
	$tpl=new templates();
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$t=time();
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_FONT_SIZE"]="22px";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"]="32px";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]==null)){$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
	$accept=$tpl->_ENGINE_parse_body("{accept}");
	$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=str_replace("\\n", "\n", $ArticaHotSpotSMTP["TERMS_CONDITIONS"]);
	$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=str_replace("\\\"", "\"", $ArticaHotSpotSMTP["TERMS_CONDITIONS"]);
	$f[]="<div class=form>";
	$f[]="<div style='font-size:$fontsize;margin:10px'>{$ArticaHotSpotSMTP["TERMS_EXPLAIN"]}</div>";
	$f[]="<textarea readonly='yes' style='width:97%;height:450px'>{$ArticaHotSpotSMTP["TERMS_CONDITIONS"]}</textarea>";
	$f[]="			<form id='wifidogform$t' action=\"$page\" method=\"post\">";
	$f[]="<input type='hidden' name='wifidog-terms' value='yes'>";
	$f[]="$HiddenFields";
	$f[]="<p style='text-align:right'><a data-loading-text=\"Chargement...\"
	style=\"font-size:$fontsize;text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
	onclick=\"javascript:document.forms['wifidogform$t'].submit();\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$accept&nbsp;&raquo;</a></p>";
	
	$f[]="</form></div>";
	$text_form=@implode("\n", $f);
	echo BuildFullPage($text_form);
}


function  wifidog_login($error=null){
	$sock=new sockets();
	session_start();
	$ipClass=new IP();
	$tpl=new templates();
	$USE_TERMS_ACCEPTED=false;
	header('Content-Type: text/html; charset=iso-8859-1');
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	if(isset($_POST["wifidog-terms"])){
		$_SESSION["USE_TERMS_ACCEPTED"]=true;
		$USE_TERMS_ACCEPTED=true;
	}
	
	if(isset($_SESSION["USE_TERMS_ACCEPTED"])){
		$USE_TERMS_ACCEPTED=true;
	}
	
	if(!$ipClass->IsvalidMAC($ARP)){
		$text_form=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{hostspot_network_incompatible}"));
	}
	
	
	if(!isset($_REQUEST["token"])){$_REQUEST["token"]=generateToken($ARP);}
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	if($ArticaHotSpotSMTP["USE_TERMS"]==1){
		if(!$USE_TERMS_ACCEPTED){
			return wifidog_terms();
		}
	}
	
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	
	
	
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="HotSpot system";}
	$tpl=new templates();
	$username=$tpl->_ENGINE_parse_body("{username}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="HotSpot system";}
	


	
	$lost_password_text=$tpl->_ENGINE_parse_body("{lost_password}");
	if(!isset($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	
	$t=time();
	unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
	$url_encoded=urlencode($url);
	
	
	
	
	$Connexion=$tpl->_ENGINE_parse_body("{connection}");
	$page=CurrentPageName();
	$f[]="";
	$f[]="    <div id='content'>";
	$f[]="    ";
	$f[]="			<form id='wifidogform' action=\"$page\" method=\"post\">";
	$f[]="$HiddenFields";
	$f[]="				<div class=\"f\">";
	$f[]="					<div class=\"field\">";
	$f[]="						<label for=\"username\" style='font-size:{$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"]}'>$username:</label> <input type=\"text\" 
		name=\"username\" 
		id=\"username\"
		value=\"{$_REQUEST["username"]}\" 
		onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
		onblur=\"this.removeAttribute('class');\" 
		OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="		";
	$f[]="</div>";
	$f[]="	<div class=\"field\">";
	$f[]="		<label for=\"password\" style='font-size:{$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"]}'>$password:</label> <input type=\"password\" name=\"password\" 
				value=\"{$_REQUEST["password"]}\"
				id=\"password\" onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
				onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon$t(event)\">";
	
	if($ArticaHotSpotSMTP["ENABLED_SMTP"]==1){
		$f[]="<div style='text-align:right'><a href=\"$page?wifidog-recover=yes&email={$_REQUEST["username"]}&$uriext\">$lost_password_text</a></div>";
	}
	
	
	$f[]="					</div>";
	$f[]="					<div class=\"field button\">";
	
	if($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]==1){
		$register=$tpl->_ENGINE_parse_body("{register}");
		$f[]="						<a data-loading-text=\"Chargement...\"
		style=\"font-size:$fontsize;text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
		href=\"$page?wifidog-register=yes&$uriext\">&laquo;&nbsp;$register&nbsp;&raquo;</a>";
	}
	
	
	
	$f[]="<a data-loading-text=\"Chargement...\" 
			style=\"font-size:$fontsize;text-transform:capitalize\" 
			class=\"Button2014 Button2014-success Button2014-lg\" 
			id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\" 
			onclick=\"javascript:document.forms['wifidogform'].submit();\" 
			href=\"javascript:Blurz()\">&laquo;&nbsp;$Connexion&nbsp;&raquo;</a>";
	

	
	
	$f[]="					</div>";
	
	if($ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]<>null){
		$f[]="<p style='font-size:{$ArticaHotSpotSMTP["SKIN_FONT_SIZE"]};padding:8px'>{$ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]}</p>";
	}
	
	$f[]="				</div>";
	$f[]="		";
	$f[]="			</form>	";
	
	
	$f[]="</div>
	<script>
		function SendLogon$t(e){
			if(!checkEnter(e)){return;}
			document.forms['wifidogform'].submit();
		}
	</script>
	\n";
	
	
	$text_form=@implode("\n", $f);
	

	
	echo BuildFullPage($text_form,$error);
	
}

function generateToken($MAC=null) {
	if(isset($GLOBALS["generateToken"])){return $GLOBALS["generateToken"];}
	if($MAC<>null){
		$sock=new sockets();
		$sock->getFrameWork("hotspot.php?wifidog-check-status=yes");
		$MAIN=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
		if(isset($MAIN["SESSIONS"][$MAC])){
			$GLOBALS["generateToken"]=$MAIN["SESSIONS"][$MAC];
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/".__FUNCTION__.":: **** OLD TOKEN {$GLOBALS["generateToken"]}");}
			return $GLOBALS["generateToken"];
		}
	}
	
	$GLOBALS["generateToken"]= md5(uniqid(rand(), 1));
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/".__FUNCTION__.":: **** New Token: {$GLOBALS["generateToken"]}");}
	return $GLOBALS["generateToken"];
}


function checkcreds_AD(){
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	$account_suffix=null;
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_activedirectory WHERE enabled=1");
	if(mysql_num_rows($results)==0){return false;}
	
	if(strpos($username, "/")>0){
		$FTR=explode("/",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	if(strpos($username, "\\")>0){
		$FTR=explode("\\",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	if(strpos($username, "@")>0){
		$FTR=explode("@",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	
	
	$username_login = strtoupper($username);
	if($account_suffix<>null){
		$username_login="$username_login@$account_suffix";
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ttl=$ligne["ttl"];
		$md5=$ligne["zmd5"];
		$groups=trim($ligne["groups"]);
		$hostname=$ligne["hostname"];
		$GLOBALS["AD_SERV_TTL"]=$ttl;
		
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog(" *********************************************************");}
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog(" TTL.: {$ttl}Mn");}
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog(" Host: {$hostname}:389");}
		
		if(!checkcreds_AD_ToServer($hostname,$username,$account_suffix,$password)){
			continue;
		}
		
		if(!checkcreds_ADGroups_ToServer($groups,$hostname,$username,$password,$account_suffix)){
			continue;
		}
		
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("checkcreds_AD {$ligne["hostname"]} return true... in line:".__LINE__);}
		if(checkcreds_AD_ToMemberAD("$username_login",$password,$ttl,$md5)){
			return true;
		}
		
		
		
	}
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog(" ****************** END FUNCTION FAILED ****************** in line:".__LINE__);}
	return false;
}

function checkcreds_AD_ToServer($hostname,$username,$account_suffix,$password){
	$options=array(
	
			'ad_username'=>$username,
			'ad_password'=>$password,
			'recursive_groups'=>true,
			'domain_controllers'=>array($hostname),
			'account_suffix'=>"@{$account_suffix}"
	);
	
	
	
	$adldap = new adLDAP($options);
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname:389, account_suffix = $account_suffix username = $username password=\"$password\"");}
	$adldap->setDomainControllers(array($hostname));
	if(!$adldap->authenticate("$username", $password)){
		if($GLOBALS["HOTSPOT_DEBUG"]){
			ToSyslog_array($GLOBALS["CLASS_ACTV"]);
			ToSyslog("$hostname: checkcreds_AD_ToServer Return false... in line:".__LINE__);
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname ****************** FAILED ******************");}
			return false;
		}
	
	}
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname ****************** SUCCESS ******************");}
	return true;
	
}

function checkcreds_ADGroups_ToServer($groups,$hostname,$username,$password,$account_suffix){
	if(strlen($groups)==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer no defined group in line:".__LINE__);}
		return true;
	}
	$YGroups=array();
	$zGroups=explode("\n",$groups);
	while (list ($num, $ligne) = each ($zGroups) ){
		$ligne=trim(strtolower($ligne));
		if($ligne==null){continue;}
		$YGroups[$ligne]=$ligne;
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer checks group $ligne in line:".__LINE__);}
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer ".count($YGroups)." in line:".__LINE__);}
	
	if(count($YGroups)==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer no group defined, return true in line:".__LINE__);}
		return true;
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer checks $username groups in line:".__LINE__);}
	
	$account_suffixZ=explode(".",$account_suffix);
	while (list ($num, $a) = each ($account_suffixZ) ){
		$zsuffix[]="DC=$a";
	}
	
	$suffix=@implode(",", $zsuffix);
	
	$options=array(
			'base_dn'=>$suffix,
			'ad_username'=>$username,
			'ad_password'=>$password,
			'recursive_groups'=>true,
			'domain_controllers'=>array($hostname),
			'account_suffix'=>"@{$account_suffix}"
	);
	
	$adldap = new adLDAP($options);
	$adldap->authenticate("$username", $password);
	$result=$adldap->user()->groups($username);
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog_array($GLOBALS["CLASS_ACTV"]);}
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_ADGroups_ToServer $username in:".count($result)." groups in line:".__LINE__);}
	
	while (list ($num, $group) = each ($result) ){
		$group=trim(strtolower($group));
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_AD checks $group group in line:".__LINE__);}
		if(isset($YGroups[$group])){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$hostname: checkcreds_AD checks $group is OK in line:".__LINE__);}
			return true;
		}
	}
	
	return false;
	
	
}

function checkcreds_AD_ToMemberAD($uid,$password,$ttl,$md5){
	$q=new mysql_squid_builder();
	$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$creationtime=time();
	$password=md5($password);
	
	if($ligne["uid"]==null){
		$uid=strtolower($uid);
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Create new member $uid,$password,$ttl,$md5 in line:".__LINE__);}
		$sql="INSERT IGNORE INTO hotspot_members (uid,ttl,sessiontime,password,enabled,creationtime,activedirectory,activedirectorycnx) VALUES
		('$uid','$ttl','','$password',1,'$creationtime',1,'$md5')";
		$q->QUERY_SQL($sql);
		
	if(!$q->ok){
			if(strpos(" $q->mysql_error", "Unknown column")>0){
				if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("check_hotspot_tables in line:".__LINE__);}
				$q->check_hotspot_tables();
				$q->QUERY_SQL($sql);
			}
		}
			
		if(!$q->ok){
			ToSyslog("$q->mysql_error");
			return false;
		}
		return true;
	}
	
	$uid=strtolower($uid);
	$sql="UPDATE hotspot_members SET `uid`='$uid',
	`ttl`='$ttl',
	`password`='$password',
	`activedirectory`=1,
	`activedirectorycnx`='$md5'
	WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
	return true;
	
	
}


function checkcreds_ldap(){
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	$DEBUG[]="username: $username";
	
	
	
	$DEBUG[]="_POST[password]: $password";
	
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	
	$DEBUG[]="userPassword: $userPassword";
	
	$userPasswordMD=md5(trim($userPassword));
	$passwordMD=md5($password);
	if(trim($u->uidNumber)==null){return false;}
		
	
	
	if($passwordMD<>$userPasswordMD){ 
		$DEBUG[]="userPasswordMD: $userPasswordMD";
		$DEBUG[]="passwordMD: $passwordMD";
		$GLOBALS["ERROR"]=$tpl->javascript_parse_text("{bad_password}\n".@implode("\n", $DEBUG),1);
		return false;
	}
		
		return true;
}

function checkcreds_mysql($array,$noauthent=false){
	$q=new mysql_squid_builder();
	
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];
	$MAC=$array["MAC"];
	$username=$_POST["username"];
	$HOST=$array["HOST"];
	$CACHE_AUTH=$GLOBALS["CACHE_AUTH"];
	$IPADDR=$array["IPADDR"];
	$md5key=md5(strtolower("$username$IPADDR$MAC$HOST"));
	

	$tpl=new templates();
	$time=time();
	$password=trim($_POST["password"]);
	if(!$q->TABLE_EXISTS("hotspot_members")){$q->CheckTables();}
	$sql="SELECT * FROM hotspot_members WHERE uid='$username' AND activedirectory=0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if($ligne["uid"]<>null){
		$GLOBALS["CACHE_AUTH"]=$ligne["sessiontime"];
		$GLOBALS["MAX_TIME"]=$ligne["ttl"];
		
	}
	if($noauthent){return;}
	
	if($ligne["enabled"]==0){
		$GLOBALS["ERROR"]=$tpl->javascript_parse_text("{access_to_internet_disabled} ({disabled})");
		return false;
	}
	
	if($ligne["uid"]==null){
		$GLOBALS["LOGS"][]=__FUNCTION__.":: uid is null";
		return false;
	}

	if($ligne["password"]<>$password){
		if($ligne["password"]<>md5($password)){
			$GLOBALS["LOGS"][]=__FUNCTION__.":: $username `password` mismatch expected `{$ligne["password"]}`";
			
			return false;
		}
	}
	
	
	return true;
}


function wifidog_redirect_uri(){
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	if(isset($_SESSION["HOTSPOT_REDIRECT_URL"])){
		$url=$_SESSION["HOTSPOT_REDIRECT_URL"];
	}else{
		$url=$ArticaHotSpotSMTP["DEFAULT_URL"];
	}
	
	return $url;
}




function wifidog_portal(){
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_portal()");}
	session_start();
	$tpl=new templates();
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$url=wifidog_redirect_uri();
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
	$idbt=md5(time());
	$ssl_button=null;
	$explain2=null;
	$parse=parse_url($url);
	$hostname=$parse["host"];
	
	
	
	$continue_button="<a data-loading-text=\"Chargement...\" 
					style=\"font-size:$fontsize;text-transform:capitalize\" 
					class=\"Button2014 Button2014-success Button2014-lg\" 
					id=\"$idbt\" 
					href=\"$url\">&laquo;&nbsp;$continue_to_internet&nbsp;&raquo;</a>";
	
	
	
	if($ArticaHotSpotSMTP["SSL_PORTAL"]==1){
		$sock=new sockets();
		$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
		if($ArticaSplashHotSpotCertificate==null){$ArticaSplashHotSpotCertificate=$sock->getFrameWork("cmd.php?full-hostname=yes");}
		$explain2="<p style='$fontsize'>". utf8_encode($ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"])."</p>";
		$commname=$ArticaSplashHotSpotCertificate;
		$commname=strtolower($commname);
		$certificate_filename=str_replace("*", "_ALL_", $commname);
		$path="/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der";
		if(!is_file($path)){
			$path="/usr/share/artica-postfix/ressources/squid/certificate.der";
		}
		$download_certificate=$tpl->_ENGINE_parse_body("{download_certificate}");
		
		$ssl_button="<a data-loading-text=\"Chargement...\"
		style=\"font-size:$fontsize;text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"$idbt\"
		href=\"ressources/squid/". basename($path)."\">&laquo;&nbsp;$download_certificate&nbsp;&raquo;</a>";
		if(!is_file($path)){$ssl_button=null;}
		
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		while (list ($num, $ligne) = each ($_SESSION) ){
			if(preg_match("#HOTSPOT_#", $num)){
			ToSyslog("wifidog_portal:: SESSION OF $num = $ligne".__LINE__);
			}
		}
		
	}

	
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_REGISTER"])){
		$tpl=new templates();
		
		$REGISTER_MAX_TIME=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
		
		$text_form=$ArticaHotSpotSMTP["CONFIRM_MESSAGE"];
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		
		
		$html="<div class=form style='font-size:18px'>
		$text_form
		$explain2
		<div style='width:100%;text-align:right'>
		<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</div>";
		
		echo BuildFullPage($html,null);
		return;
		
		
	}
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_RECOVER"])){
		$tpl=new templates();
		$ArticaHotSpotSMTP=SMTP_SETTINGS();
		$REGISTER_MAX_TIME=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
		$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
		$text_form=$ArticaHotSpotSMTP["RECOVER_MESSAGE"];
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		$parse=parse_url($url);
		$hostname=$parse["host"];
		
		$html="<div class=form style='font-size:18px'>
			$text_form
			$explain2
			<div style='width:100%;text-align:right'>
				<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</div>
		</div>";
		
		echo BuildFullPage($html,null);
		return;
		
	}
	
	
	if($ArticaHotSpotSMTP["SSL_PORTAL"]==1){
		$html="<div class=form style='font-size:18px'>
		$explain2
		<div style='width:100%;text-align:right'>
		<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</div>
		</div>";
		
		echo BuildFullPage($html,null);
		return;
		
	}
	
		
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_portal:: buiding redirect to $url in line:".__LINE__);}
	$tpl=new templates();
	$sock=new sockets();
	$text_redirecting=$sock->GET_INFO("ArticaSplashHotSpotRedirectText");
	if($text_redirecting==null){$text_redirecting=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}:");}
		
	
	$parse=parse_url($url);
	$host=$parse["host"];
	
	$text_form="
	<div style='width:98%' class=form>
	<center>
	<div style='font-size:18px'><center>$text_redirecting<br>$host</center></div>
	<img src='img/wait_verybig_mini_red.gif'></center></div>";
		
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Redirect Client {$_SESSION["HOTSPOT_REDIRECT_MAC"]} to $url");}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"3; URL=$url\">");
		
	
}

function wifidog_authenticate(){
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_authenticate()");}
	$tpl=new templates();
	
	session_start();
	if(!isset($_SESSION["HOTSPOT_REDIRECT_URL"])){$_SESSION["HOTSPOT_REDIRECT_URL"]=$_REQUEST["url"];}
	if(!isset($_SESSION["HOTSPOT_REDIRECT_MAC"])){$_SESSION["HOTSPOT_REDIRECT_MAC"]=$_REQUEST["mac"];}
	
	if(!checkcreds()){
		wifidog_login(
		"<span style='color:#CE0000;font-size:18px'>".
		$tpl->_ENGINE_parse_body("{failed}: &laquo;{$GLOBALS["ERROR"]}&raquo;"))."</span>";
		return;
	}
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	ToSyslog("Redirect Token: $token to $gateway_addr:$gw_port");
	header("Location: $redirecturi");
	
	
}

function ToSyslog_array($array){
	while (list ($num, $ligne) = each ($array) ){
		ToSyslog($ligne);}
}

function ToSyslog($text){

	$text=str_replace("\n", " ", $text);
	if(function_exists("openlog")){openlog("wifidog-splash", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog(LOG_INFO, $text);}
	if(function_exists("closelog")){closelog();}
}


function checkcreds(){
	$sock=new sockets();
	$GLOBALS["CACHE_AUTH"]=$sock->GET_INFO("ArticaSplashHotSpotCacheAuth");
	$GLOBALS["MAX_TIME"]=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	
	
	if(!is_numeric($GLOBALS["CACHE_AUTH"])){$GLOBALS["CACHE_AUTH"]=60;}
	if(!is_numeric($GLOBALS["MAX_TIME"])){$GLOBALS["MAX_TIME"]=0;}
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$MAC=$ARP;
	
	ToSyslog("Verify credentials for $ARP/{$_POST["username"]} Token:$token");
	
	$LOGIN=$_POST["username"];
	$IPADDR=null;
	
	$HOST=gethostbyaddr($IPADDR);
	$URI=$url;
	
	$array["LOGIN"]=$LOGIN;
	$array["IPADDR"]=null;
	$array["MAC"]=$MAC;
	$array["ARP"]=$MAC;
	$array["HOST"]=$HOST;
	$array["token"]=$token;
	
	$q=new mysql_squid_builder();
	$sql="SELECT uid,creationtime,ttl,enabled FROM hotspot_members WHERE uid='$LOGIN'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if(trim($ligne["uid"])<>null){
		if($ligne["enabled"]==0){
			$Created=$q->time_to_date($ligne["creationtime"],true);
			ToSyslog("checkcreds:: $LOGIN is disabled $Created");
			$GLOBALS["ERROR"]="<strong>$LOGIN</strong> {your_account_is_disabled}<br>{created}:$Created";
			return false;
		}
		$ttl=$ligne["ttl"];
		if($ligne["creationtime"]>0){
			if($ligne["ttl"]>0){
				$EnOfLife = strtotime("+{$ttl} minutes", $ligne["creationtime"]);
				if(time()>$EnOfLife){
					ToSyslog("checkcreds:: $LOGIN expired - End of Life");
					$GLOBALS["ERROR"]="{accesstime_to_internet_expired}";
					return false;
				}
			}
				
		}
	}
	
	
	$auth=false;
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	
	
	if(checkcreds_AD()){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("checkcreds_AD return true... in line:".__LINE__);}
		checkcreds_mysql($array,true);
		return UnLock($array);
	}
	
	if(checkcreds_ldap()){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("checkcreds_ldap return true... in line:".__LINE__);}
		checkcreds_mysql($array,true);
		return UnLock($array);
	}
	
	
	if(checkcreds_mysql($array)){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("checkcreds_mysql return true... in line:".__LINE__);}
		return UnLock($array);
	}
	
	events(1,"Login failed for $LOGIN/$IPADDR","MAC:$MAC\nHost:$HOST\n".@implode("\n", $GLOBALS["LOGS"]));
	return false;
}

function events($severity,$subject,$content){
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			}
		}
			
	
	$zdate=date("Y-m-d H:i:s");
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
}

function UnLock($array){
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$IPADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];	
	$token=$array["token"];
	$HOST=$array["HOST"];
	
	$MAC=$ARP;
	$username=$uid;
	
	$CACHE_AUTH=$GLOBALS["CACHE_AUTH"];
	$autocreate=0;
	$time=time();
	$sock=new sockets();
	$md5key=$token;
	$q=new mysql_squid_builder();
	
	$sql="SELECT creationtime,uid,ttl,enabled,autocreate,autocreate_confirmed FROM hotspot_members WHERE uid='$username'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$finaltime = strtotime("+525600 minutes", time());
	$ttl=$ligne["ttl"];
	if($ligne["autocreate"]==1){if($ligne["autocreate_confirmed"]==0){ $autocreate=1; } }
	if($ttl>0){$finaltime = strtotime("+{$ttl} minutes", $ligne["creationtime"]); }
	ToSyslog("wifidog_auth/UnLock: $username: Create session  $md5key ( autocreate =$autocreate ) for $LOGIN MAC:$MAC with a Time To Live of {$ttl}Mn in line:".__LINE__);
	
	
	
	$NextCheck = strtotime("+525600 minutes", $time);
	
	$logintime=time();
	
	if(!is_numeric($CACHE_AUTH)){$CACHE_AUTH=60;}
	if(!is_numeric($GLOBALS["MAX_TIME"])){$GLOBALS["MAX_TIME"]=0;}
	
	
	if($CACHE_AUTH>0){ $NextCheck = strtotime("+$CACHE_AUTH minutes", $time); }
	
	$datelogs=date("Y-m-d H:i:s",$NextCheck);
	$finaltimeDate=date("Y-m-d H:i:s",$finaltime);
	
	$MAC=trim(strtolower($MAC));
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/UnLock: Remove sessions for $token,$IPADDR,$MAC,$uid");}
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE ipaddr='$IPADDR'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE MAC='$MAC'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE uid='$uid'");
	
	
	if(!$q->FIELD_EXISTS("hotspot_sessions", "nextcheck")){$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `nextcheck` BIGINT UNSIGNED ,ADD INDEX ( `nextcheck` )");}
	if(!$q->FIELD_EXISTS("hotspot_sessions", "autocreate")){$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `autocreate` smallint(1) NOT NULL DEFAULT 0 ,ADD INDEX ( `autocreate` )");}
	
	$sql="INSERT IGNORE INTO hotspot_sessions (`md5`,logintime, maxtime,finaltime,nextcheck,username,uid,MAC,hostname,ipaddr,autocreate) VALUES('$token',$logintime,$CACHE_AUTH,$finaltime,$NextCheck,'$username','$uid','$MAC','$HOST','$IPADDR','$autocreate')";
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/UnLock: $sql");}
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		ToSyslog("wifidog_auth/UnLock:$q->mysql_error Line:".__LINE__);
		return false;
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `md5` FROM hotspot_sessions WHERE `md5`='$token'"));
	if(trim($ligne["md5"])==null){
		ToSyslog("wifidog_auth/UnLock: MySQL Failed, $token is not saved Line:".__LINE__);
		return false;
	}
	
	events(2,"$username/$MAC Create a new session Finish at $finaltime ($finaltimeDate)",__FILE__,__LINE__);
	ToSyslog("wifidog_auth/UnLock: Create session $token for $LOGIN MAC:$MAC Max time:{$GLOBALS["MAX_TIME"]} Finish at $finaltime ($finaltimeDate)");
	return true;
}

function CAS_SERVICE(){
	
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$redirecturi=urlencode(trim($_SESSION["HOTSPOT"]["redirecturi"]));
	$MAC=trim(strtolower($_SESSION["HOTSPOT"]["ARP"]));
	return urlencode("$ArticaHotSpotInterface?popup=yes&MAC=$MAC&redirecturi=$redirecturi");
}



function CAS_URI(){
	
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	
	
	
	
	$service=CAS_SERVICE();
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$URL_REDIRECT="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/login?service=$service";
	return $URL_REDIRECT;
	
}



function CAS_VALIDATE(){
	$TGT=$_GET["ticket"];
	if($TGT==null){
		$GLOBALS["CASLOGS"]="<br><strong>No ticket returned</strong><br>";
		return null;}
	$sock=new sockets();
	$token=$_GET["popup"];
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$REMOTE_ADDR=$_SESSION["HOTSPOT"]["REMOTE_ADDR"];
	$SERVER_NAME=$_SESSION["HOTSPOT"]["SERVER_NAME"];
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	$ARP=$_SESSION["HOTSPOT"]["ARP"];
	

	
	$cas_service=CAS_SERVICE();
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	

	$uri_check="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/serviceValidate?ticket=$TGT&service=$cas_service";
	
	$curl = curl_init("$uri_check");
	
	if($GLOBALS["VERBOSE"]){echo "<li>$uri_check</li>";}
	
	$t=time();
	curl_setopt($curl,  CURLOPT_HEADER, true);
	curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,  CURLOPT_FAILONERROR, true);
	curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl,  CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($curl,  CURLOPT_POST, 0);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	
	$data = curl_exec($curl);
	$length=strlen($data);
	$CURLINFO_HTTP_CODE=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	
	if($GLOBALS["VERBOSE"]) {
		echo "<strong>CURLINFO_HTTP_CODE:$CURLINFO_HTTP_CODE</strong><br>";
		print_r(curl_getinfo($curl));
	}
	
	curl_close($curl);
	$distanceInSeconds = round(abs(time() - $t));
	if($GLOBALS["VERBOSE"]) {echo "<strong>data: ". strlen($data)."</strong><hr>DATA: $data\n\n</hr>";}
	
	
	
	if($CURLINFO_HTTP_CODE<>200){
		$GLOBALS["CASLOGS"]="<br><strong>Unknown error: HTTP Error: $CURLINFO_HTTP_CODE</strong>";
		return null;}
	

	if($GLOBALS["VERBOSE"]){print_r($_SESSION["HOTSPOT"]);}
	
	if($GLOBALS["VERBOSE"]) {echo "<strong>".htmlentities($data)."</strong>";}
	
	if(preg_match("#INVALID_SERVICE#is", $data)){
		$GLOBALS["CASLOGS"]="<br><strong>Invalid service</strong>";
		events(1,"CAS session failed ticket $TGT INVALID_SERVICE","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi\service: $cas_service");
		return null;
	}
	
	if(preg_match("#<cas:user>(.+?)</cas:user>#is", $data,$re)){
		events(2,"CAS session success ticket $TGT with user {$re[1]}","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi");
		if($GLOBALS["VERBOSE"]){echo "<li>TGT:$TGT - $re[1]</li>";}
		$GLOBALS["CASLOGS"]="<br><strong>$TGT Cas user: {$re[1]}<br>";
		return $re[1];
	}	
	
	$GLOBALS["CASLOGS"]="<br><strong>Unknown error: REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP</strong>";
	$GLOBALS["CASLOGS"]=$data;
	if($GLOBALS["VERBOSE"]){echo "<li>failed</li>";}
	
}

function js_load(){
	$js=$_GET["jsload"];
	$file=basename($js);
	header('Content-type: application/x-javascript');
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($js);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($js);
		
	
}

function css(){
	$css=$_GET["css"];
	$file=basename($css);
	header('Content-type: text/css');
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($css);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($css);
	
}

function endusers_load(){
	$path="ressources/templates/endusers/".$_GET["endusers"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);	
}

function imgload(){
	$path="img/".$_GET["imgload"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	
}



function wifidog_is_end_of_life($token){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
	$finaltime=$ligne["finaltime"];
	
	if($finaltime==0){
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		return true;
	}
	
	if(time()>$finaltime){
		ToSyslog("HotSpot session $token is End-Of-Life...$finaltime >" .time());
		return true;
	}
	
	return false;
	
	
}

function wifidog_is_member_end_of_life($uid){
	
	
}


function wifidog_build_uri(){
	reset($_REQUEST);
	while (list ($num, $ligne) = each ($_REQUEST) ){
		if($num=="wifidog-login"){continue;}
		if($num=="wifidog-register"){continue;}
		if($num=="register-member"){continue;}
		if($num=="wifidog-recover"){continue;}
		if($num=="register-recover"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="confirm-password"){continue;}
		if($num=="wifidog-terms"){continue;}
		
		
		
		$URIZ[]="$num=".urlencode($ligne);
		$inputz[]="<input type='hidden' id='$num' name='$num' value='$ligne'>";
	
	}

	return array(@implode("&", $URIZ),@implode("\n", $inputz));
	
}

function wifidog_recover($error=null){
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_recover($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{lost_password}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	$cancel=$tpl->_ENGINE_parse_body("{cancel}");
	session_start();
	unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	$html="
	
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	<input type='hidden' id='register-recover' name='register-recover' value='yes'>
	$HiddenFields
	<div style='font-size:32px;font-weight:bold;margin-bottom:15px'>$register</div>
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"email-$t\" class=legend>$email:</label>
	
	<input style='font-size:$fontsize;width:80%' type=\"text\" class=\"input-block-level\"
	placeholder=\"$email\" id=\"email\" name=\"email\" value='{$_REQUEST["email"]}'>
	
	<div style='margin-top:20px;text-align:right'>
		<a data-loading-text=\"Chargement...\"
		style=\"font-size:{$btsize};text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		href=\"$page?wifidog-login&$uriext\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>
		&nbsp;&nbsp;
		<a data-loading-text=\"Chargement...\"
		style=\"font-size:{$btsize};text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';\"
		href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
		</div>
	
		</form>
		</div>
		<script>
		$('.input-block-level').keypress(function (e) {
	
		if (e.which == 13) {
		document.forms['register-$t'].submit();
		document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}

function wifidog_confirm($error=null){
	$sessionkey=null;
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_password($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sessionkey=$_REQUEST["wifidog-confirm"];
	if($sessionkey==null){
		if(isset($_REQUEST["sessionkey"])){$sessionkey=$_REQUEST["sessionkey"];}
	}else{
		$_REQUEST["sessionkey"]=$sessionkey;
	}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		echo BuildFullPage(null,"{this_account_didnot_exists}");
		return;
	}
	$email=$ligne["uid"];
	if($_REQUEST["url"]==null){$_REQUEST["url"]=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	if($sessionkey==null){$sessionkey=md5(time().$email);}
	
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=1,
	autocreate=1,
	autocreate_maxttl='{$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]}',
	creationtime='".time()."',
	sessionkey='$sessionkey'
	WHERE uid='$email'";
	
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		if(strpos(" $q->mysql_error", "Unknown column")>0){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("check_hotspot_tables in line:".__LINE__);}
			$q->check_hotspot_tables();
			if(!$q->ok){if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$q->mysql_error in line:".__LINE__);}}
			$q->QUERY_SQL($sql);
		
		}
	}
	
	if(!$q->ok){
		ToSyslog("$q->mysql_error");
		echo BuildFullPage(null,$q->mysql_error);die();
	}

	$REGISTER_MESSAGE_SUCCESS=$ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"];
	
	
	$tpl=new templates();
	$sock=new sockets();
	
	$text_redirecting=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}:");
	
	$url=wifidog_redirect_uri();
	$parse=parse_url($url);
	$host=$parse["host"];
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	$text_form="
	<div style='width:98%' class=form>
	<center>
	<div style='font-size:$fontsize'><center>$REGISTER_MESSAGE_SUCCESS<br>$text_redirecting: <a href=\"$url\">$host</a></center></div>
	<img src='img/wait_verybig_mini_red.gif'></center></div>";
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Redirect Client {$_SESSION["HOTSPOT_REDIRECT_MAC"]} to $url");}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
	
}


function wifidog_password($error=null){
	$sessionkey=null;
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_password($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sessionkey=$_REQUEST["wifidog-password"];
	if($sessionkey==null){
		if(isset($_REQUEST["sessionkey"])){$sessionkey=$_REQUEST["sessionkey"];}
	}else{
		$_REQUEST["sessionkey"]=$sessionkey;
	}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		echo BuildFullPage(null,"{this_account_didnot_exists}");
		return;
	}
	
	if($_REQUEST["url"]==null){$_REQUEST["url"]=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	
	
	
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{change_password}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$RECOVER_MESSAGE_P1=$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"];
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	$html="
	
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	<input type='hidden' id='confirm-password' name='confirm-password' value='yes'>
	$HiddenFields
	<div style='font-size:26px;font-weight:bold;margin-bottom:15px'>$register</div>
	<div style='font-size:18px'>$RECOVER_MESSAGE_P1</div>
	<label style='font-size:$fontsize;margin-top:20px' for=\"email-$t\" class=legend>$email: {$ligne["uid"]}</label>
	<label style='font-size:$fontsize;margin-top:20px' for=\"password\" class=legend>$password:</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	class=\"input-block-level\" placeholder=\"$password\" id=\"password\" name=\"password\" value='{$_REQUEST["password"]}'>
	 
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"password2-$t\" class=legend>$password ($confirm):</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	class=\"input-block-level\" placeholder=\"$password ($confirm)\" name=\"password2\"
	id=\"password2\" value='{$_REQUEST["password2"]}'>
	
	
	<div style='margin-top:20px;text-align:right'>
	
	<a data-loading-text=\"Chargement...\"
	style=\"font-size:$btsize;text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</div>
	
	</form>
	</div>
	<script>
	$('.input-block-level').keypress(function (e) {
	
	if (e.which == 13) {
	document.forms['register-$t'].submit();
	document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}

function wifidog_password_perform(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sessionkey=$_REQUEST["sessionkey"];
	
	if($sessionkey==null){
		return wifidog_password("Missing field sessionkey");
		
	}
	
	$url=$_REQUEST["url"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		echo BuildFullPage(null,"<center>{this_account_didnot_exists}<hr><span style='font-size:12px'>$sessionkey</span></center>","<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
		return;
	}
	
	
	$password2=trim($_POST["password2"]);
	$password=trim($_POST["password"]);
	if($password2<>$password){return wifidog_password("{password_mismatch}");}
	$password=md5($password);
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=1,
		autocreate=1,
		password='$password'
		WHERE sessionkey='$sessionkey'";

	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		return wifidog_password($q->mysql_error_html());
	}
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	
	$text_form="
	<div style='width:98%' class=form>
	<center>
	<div style='font-size:$fontsize'><center>{updated_password_successfully}<br>$url</center></div>
	<img src='img/wait_verybig_mini_red.gif'></center></div>";
	
	$text_form=$tpl->_ENGINE_parse_body($text_form);
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
	
}


function wifidog_register($error=null){
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_register($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{register}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	
	
	
	$cancel=$tpl->_ENGINE_parse_body("{cancel}");
	session_start();
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	if($ArticaHotSpotSMTP["ENABLED_SMTP"]==0){$email=$tpl->_ENGINE_parse_body("{account}");}
	$html="
	
	<div style='width:98%' class=form id='form-$t'>
	
	
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	<input type='hidden' id='register-member' name='register-member' value='yes'>
	$HiddenFields
	<div style='font-size:32px;font-weight:bold;margin-bottom:15px'>$register</div>		
	<p style='font-size:$fontsize'>{$ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]}</p>
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"email-$t\" class=legend>$email:</label>
		
	<input style='font-size:$fontsize;width:80%' type=\"text\" class=\"input-block-level\" 
	placeholder=\"$email\" id=\"email\" name=\"email\" value='{$_REQUEST["email"]}'>
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"password\" class=legend>$password:</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\" 
	class=\"input-block-level\" placeholder=\"$password\" id=\"password\" name=\"password\" value='{$_REQUEST["password"]}'>
			        
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"password2-$t\" class=legend>$password ($confirm):</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\" 
		class=\"input-block-level\" placeholder=\"$password ($confirm)\" name=\"password2\" 
		id=\"password2\" value='{$_REQUEST["password2"]}'>

	
	<div style='margin-top:20px;text-align:right'>
	<a data-loading-text=\"Chargement...\" 
								style=\"font-size:$btsize;text-transform:capitalize\" 
								class=\"Button2014 Button2014-success Button2014-lg\" 
								id=\"".time()."\" 
								
								href=\"$page?wifidog-login&$uriext\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>&nbsp;&nbsp;
	<a data-loading-text=\"Chargement...\" 
								style=\"font-size:$btsize;text-transform:capitalize\" 
								class=\"Button2014 Button2014-success Button2014-lg\" 
								id=\"".time()."\" 
								onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';\" 
								href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</div>

</form>
</div>	
<script>
 $('.input-block-level').keypress(function (e) {
	
	 if (e.which == 13) {
		document.forms['register-$t'].submit();
		document.getElementById('form-$t').innerHTML='<center><img src=img/wait_verybig_mini_red.gif></center>';
	 }

});

</script>
	
	
	
	";
	echo BuildFullPage($html,$error);
}

function SMTP_SETTINGS(){
	if(isset($GLOBALS["SMTP_SETTINGS"])){return $GLOBALS["SMTP_SETTINGS"];}
	$sock=new sockets();
	if(!isset($GLOBALS["CACHE_AUTH"])){
		$GLOBALS["CACHE_AUTH"]=$sock->GET_INFO("ArticaSplashHotSpotCacheAuth");
	}
	if(!isset($GLOBALS["MAX_TIME"])){
		$GLOBALS["MAX_TIME"]=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	}
	
	$users=new usersMenus();
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	$ArticaHotSpotSMTP=$sock->FillSMTPNotifsDefaults($ArticaHotSpotSMTP);
	$ArticaHotSpotSMTP["ArticaSplashHotSpotEndTime"]=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	if(!isset($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if(!is_numeric($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	

	while (list ($num, $ligne) = each ($ArticaHotSpotSMTP) ){
		if(!$users->CORP_LICENSE){if(preg_match("#^SKIN_#i", trim($num))){$ArticaHotSpotSMTP[$num]=null;continue;}}
			$ArticaHotSpotSMTP[$num]=utf8_decode($ligne);
	
	}
	
	
	if($ArticaHotSpotSMTP["REGISTER_MAX_TIME"]<5){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration";}
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]="Your Account is Now Validated!<br>Thank you for confirming your email address.";}
	if(!isset($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!isset($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if(!isset($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"]="Success<br>\nA message as been sent to you.<br>\nPlease check your WebMail system in order to recover your password<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"]="Fill out the form below to change your password";}
	
	
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	if(!isset($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if(!is_numeric($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MAX_TIME"]<5){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MAX_TIME"]<5){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["DEFAULT_URL"]==null){$ArticaHotSpotSMTP["DEFAULT_URL"]="http://www.articatech.net";}
	if(trim($ArticaHotSpotSMTP["TERMS_EXPLAIN"])==null){$ArticaHotSpotSMTP["TERMS_EXPLAIN"]="To signup for a new account you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";}
	if(trim($ArticaHotSpotSMTP["TERMS_CONDITIONS"])==null){$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=@file_get_contents("ressources/databases/wifi-terms.txt");}
	
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_FONT_SIZE"]="22px";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"]="32px";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]==null)){$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]="Calibri, Candara, Segoe, Segoe UI, Optima, Arial, sans-serif";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_FONT_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_LINK_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]="263849";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"]="5CB85C";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]="398439";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"]="FFFFFF";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"]="47A447";}
	
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"]="485px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"]="221px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"]="10pt";}
	if(trim($ArticaHotSpotSMTP["SKIN_TEXT_LOGON"])==null){$ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"]=null;}
	
	
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"]="logo-hotspot.png";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"]="50";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"]="0";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"]="401";}
	
	
	
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]="15px";}
	
	
	
	$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]=str_replace("Segoe UI","\"Segoe UI\"",$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]);
	

	
	reset($ArticaHotSpotSMTP);
	$GLOBALS["SMTP_SETTINGS"]=$ArticaHotSpotSMTP;
	return $ArticaHotSpotSMTP;
}

function wifidog_recover_perform(){
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_recover_perform()");}
	$email=trim(strtolower($_POST["email"]));
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {return wifidog_recover("{error_email_invalid}");}
	$tr=explode("@",$email);
	$domain=$tr[1];
	if (!checkdnsrr($domain, 'MX')) {return wifidog_recover("&laquo;$domain&raquo;<br>{error_domain_email_invalid}");}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid,sessionkey FROM hotspot_members WHERE `uid`='$email'"));
	if($ligne["uid"]==null){return wifidog_register("{this_account_didnot_exists}<hr>"); }
	$sessionkey=$ligne["sessionkey"];
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	
	if($sessionkey==null){$sessionkey=md5(time().$email);}
	
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=0,
		autocreate=1,
		autocreate_maxttl='{$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]}',
		creationtime='".time()."',
		sessionkey='$sessionkey'
		WHERE uid='$email'";

	$q->QUERY_SQL($sql);
	
	
	
	
	
	if(!$q->ok){
		if(strpos(" $q->mysql_error", "Unknown column")>0){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("check_hotspot_tables in line:".__LINE__);}
			$q->check_hotspot_tables();
			if(!$q->ok){if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$q->mysql_error in line:".__LINE__);}}
			$q->QUERY_SQL($sql);
				
		}
	}
	
	if(!$q->ok){
		ToSyslog("$q->mysql_error");
		return wifidog_recover($q->mysql_error);
	}
	
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-password=$sessionkey";
	
	$smtp_sender=$ArticaHotSpotSMTP["smtp_sender"];
	
	$smtp_senderTR=explode("@",$smtp_sender);
	$instance=$smtp_senderTR[1];

	$body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="Subject: {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}";
	$body[]="To: $email";
	$body[]="";
	$body[]="";
	$body[]=$ArticaHotSpotSMTP["RECOVER_MESSAGE"];
	$body[]=$URL_REDIRECT;
	$body[]="";
	$body[]="";
	$finalbody=@implode("\r\n", $body);
	
	$smtp=new smtp();
	if($ArticaHotSpotSMTP["smtp_auth_user"]<>null){
		$params["auth"]=true;
		$params["user"]=$ArticaHotSpotSMTP["smtp_auth_user"];
		$params["pass"]=$ArticaHotSpotSMTP["smtp_auth_passwd"];
	}
	$params["host"]=$ArticaHotSpotSMTP["smtp_server_name"];
	$params["port"]=$ArticaHotSpotSMTP["smtp_server_port"];
	
	
	if(!$smtp->connect($params)){
		return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
	
	}
	
	
	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$email,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/wifidog_recover_perform From: $smtp_sender to $email {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]} success");}
	$smtp->quit();
	
	
	
	$array["LOGIN"]=$email;
	$array["ARP"]=$_REQUEST["mac"];
	$array["token"]=$_REQUEST["token"];
	$array["redirecturi"]=$_REQUEST["url"];
	$array["REMOTE_ADDR"]=$_REQUEST["ip"];
	$array["REGISTER"]=true;
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/wifidog_recover_perform Unlock with token={$array["token"]}");}
	if(!UnLock($array)){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_recover_perform(): failed_to_create_session");}
		return wifidog_register("{error} {failed_to_create_session}");
	
	}
	
	
	session_start();
	$_SESSION["HOTSPOT_AUTO_RECOVER"]=true;
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$_REQUEST["url"];
	
	
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	ToSyslog("wifidog_recover_perform:: Redirect Token: $token to $gateway_addr:$gw_port");
	header("Location: $redirecturi");	
	
	
}


function wifidog_register_perform(){
	session_start();
	$page=CurrentPageName();
	$autocreate_confirmed=0;
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Starting wifidog_register_perform()");}
	$sock=new sockets();

	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	
	$email=trim(strtolower($_POST["email"]));
	$q=new mysql_squid_builder();
	$password2=trim($_POST["password2"]);
	$password=trim($_POST["password"]);
	if($password2<>$password){return wifidog_register("{password_mismatch}");}
	if($ArticaHotSpotSMTP["ENABLED_SMTP"]==1){
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {return wifidog_register("{error_email_invalid}");}
		$tr=explode("@",$email);
		$domain=$tr[1];
		if (!checkdnsrr($domain, 'MX')) {return wifidog_register("&laquo;$domain&raquo;<br>{error_domain_email_invalid}");}
	}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `uid`='$email'"));
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	
	if($ligne["uid"]<>null){ 
		if($ArticaHotSpotSMTP["ENABLED_SMTP"]==1){$link="<center><a href=\"$page?wifidog-recover=yes&$uriext\">{lost_password}</a></center>";}
		return wifidog_register("{this_account_already_exists}<hr>$link"); 
	}
	
	
	
	$MAC=$_REQUEST["mac"];
	
	$REGISTER_MAX_TIME=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
	$ArticaSplashHotSpotEndTime=$ArticaHotSpotSMTP["ArticaSplashHotSpotEndTime"];
	
	$password=md5($password);
	$creationtime=time();
	$autocreate_maxttl=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
	$sessionkey=md5($password.$creationtime.$email);
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	
	if($ArticaHotSpotSMTP["ENABLED_SMTP"]==0){$autocreate_confirmed=1;}
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("Create new member $email,$password,TTL:$ArticaSplashHotSpotEndTime in line:".__LINE__);}
	$sql="INSERT IGNORE INTO hotspot_members 
	(uid,ttl,sessiontime,password,enabled,creationtime,autocreate,autocreate_confirmed,autocreate_maxttl,sessionkey,MAC) VALUES
	('$email','$ArticaSplashHotSpotEndTime','','$password',1,'$creationtime',1,'$autocreate_confirmed',$autocreate_maxttl,'$sessionkey','$MAC')";
	$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){
		if(strpos(" $q->mysql_error", "Unknown column")>0){
			if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("check_hotspot_tables in line:".__LINE__);}
			$q->check_hotspot_tables();
			if(!$q->ok){if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("$q->mysql_error in line:".__LINE__);}}
			$q->QUERY_SQL($sql);
			
		}
	}
		
	if(!$q->ok){
		ToSyslog("$q->mysql_error");
		return wifidog_register($q->mysql_error);
	}	
	
	if($ArticaHotSpotSMTP["ENABLED_SMTP"]==0){
			$array["SERVER_NAME"]=$_SERVER["SERVER_NAME"];
			$array["redirecturi"]=$url;
			$array["LOGIN"]=$email;
			$array["redirecturi"]=$_REQUEST["url"];
			$array["REMOTE_ADDR"]=$_REQUEST["ip"];
			$array["token"]=$token;
			$array["HOST"]=$_REQUEST["ip"];
			UnLock($array);
			ToSyslog("wifidog_auth/".__FUNCTION__.":: SESSION(HOTSPOT_REDIRECT_URL) = $url");
			$_SESSION["HOTSPOT_AUTO_REGISTER"]=true;
			$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
			$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
			ToSyslog("wifidog_auth/".__FUNCTION__.":: Redirect Token: $token to $redirecturi");
			header("Location: $redirecturi");
			return;
	}
	
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-confirm=$sessionkey";
	
	$smtp_sender=$ArticaHotSpotSMTP["smtp_sender"];
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration";}
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	
	$smtp_senderTR=explode("@",$smtp_sender);
	$instance=$smtp_senderTR[1];
	
	$random_hash = md5(date('r', time()));
	$boundary="$random_hash/$instance";
	$body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="Subject: {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}";
	$body[]="To: $email";
	$body[]="Auto-Submitted: auto-replied";
	$body[]="MIME-Version: 1.0";
	$body[]="Content-Type: multipart/mixed;";
	$body[]="	boundary=\"$boundary\"";
	$body[]="Content-Transfer-Encoding: 8bit";
	$body[]="Message-Id: <$random_hash@$instance>";
	$body[]="--$boundary";
	$body[]="Content-Description: Notification";
	$body[]="Content-Type: text/plain; charset=us-ascii";
	$body[]="";
	$body[]=$ArticaHotSpotSMTP["REGISTER_MESSAGE"];
	$body[]=$URL_REDIRECT;
	$body[]="";
	$body[]="";
	$body[]="--$boundary";
	$finalbody=@implode("\r\n", $body);
	
	$smtp=new smtp();
	if($ArticaHotSpotSMTP["smtp_auth_user"]<>null){
		$params["auth"]=true;
		$params["user"]=$ArticaHotSpotSMTP["smtp_auth_user"];
		$params["pass"]=$ArticaHotSpotSMTP["smtp_auth_passwd"];
	}
	$params["host"]=$ArticaHotSpotSMTP["smtp_server_name"];
	$params["port"]=$ArticaHotSpotSMTP["smtp_server_port"];
	
	
	if(!$smtp->connect($params)){
		return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
		
	}
		
	
	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$email,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("From: $smtp_sender to $email {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]} success");}
	$smtp->quit();	
	
	
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/".__FUNCTION__.":: Token: $token -> UnLock(..");}
	$array["LOGIN"]=$email;
	$array["ARP"]=$_REQUEST["mac"];
	$array["token"]=$token;
	$array["redirecturi"]=$_REQUEST["url"];
	$array["REMOTE_ADDR"]=$_REQUEST["ip"];
	$array["REGISTER"]=true;
	
	
	if(!UnLock($array)){
		if($GLOBALS["HOTSPOT_DEBUG"]){ToSyslog("wifidog_auth/".__FUNCTION__."::failed_to_create_session");}
		return wifidog_register("{error} {failed_to_create_session}");
		
	}

	
	ToSyslog("wifidog_auth/".__FUNCTION__.":: SESSION(HOTSPOT_REDIRECT_URL) = $url");
	$_SESSION["HOTSPOT_AUTO_REGISTER"]=true;
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
	

	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	ToSyslog("wifidog_auth/".__FUNCTION__.":: Redirect Token: $token to $redirecturi");
	header("Location: $redirecturi");
	
	
}






function BuildFullPage($content,$error=null,$headerAdd=null){
	$prefix=null;
	$tpl=new templates();
	$users=new usersMenus();
	$hostname=$users->hostname;
	$sock=new sockets();
	
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="HotSpot system";}
	
	if($GLOBALS["AS_ROOT"]){
		$unix=new unix();
		$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
		$ArticaSplashHotSpotPortSSL=intval($sock->GET_INFO("ArticaSplashHotSpotPortSSL"));
		if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
		if($ArticaSplashHotSpotPortSSL==0){$ArticaSplashHotSpotPortSSL=16443;}
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$IPADDR=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
		$prefix="https://$IPADDR:$ArticaSplashHotSpotPortSSL";
		$ArticaSplashHotSpotTitle=$ArticaSplashHotSpotTitle." \$title";
	}
	
	
	$ASIE=false;

	if($users->CORP_LICENSE){
		$logo=$sock->GET_INFO("ArticaSplashHotSpotLogo");
	}
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	$textcolor="#".$ArticaHotSpotSMTP["SKIN_FONT_COLOR"];
	$ArticaSplashHotSpotFontFamily=$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"];
	
	
	
	$logo=$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"];
	if($error<>null){
		$error=$tpl->_ENGINE_parse_body($error);
		$error="<center style='background-color:white;padding:5px;margin:5px;min-height:75px;
		' class=form>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:100px;text-align:center'><span style='font-size:120px;margin:5px;font-weight:bolder;color:#CB0000' nowrap>:(</span></td>
		<td valign='middle' style='font-size:$fontsize !important;color:#CB0000'>$error</td>
		</tr>
		</table>
		</center>";
	}
	
	

	
	if(preg_match("#; MSIE#",$_SERVER["HTTP_USER_AGENT"])){
		$ASIE=true;
	}
	
	
$css[]=".blockUI h1 {";
$css[]="    background:none;";
$css[]="    background-image: none;";
$css[]="	}";
$css[]="	";
$css[]=".blockUI.blockMsg.blockPage > h1 {";
$css[]="	padding-top:1px;";
$css[]="    margin-left: 100px;";
$css[]="    text-align: center;";


$contentBorders=null;
$backPattern=" url('$prefix/ressources/templates/Squid/i/pattern.png')";
$contentBack=" url('$prefix/ressources/templates/Squid/i/form.png') no-repeat";
if($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]<>null){
	$contentBack="#{$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]}";
	$contentBorders="border-radius: 6px 6px 6px 6px;
	-moz-border-radius: 6px 6px 6px 6px;
	-khtml-border-radius: 6px 6px 6px 6px;
	-webkit-border-radius: 6px 6px 6px 6px;";
}
if($ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]<>"263849"){
	$backPattern="//{$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]} is not 263849";

}
$sum_margin_neg=null;
$sum_margin=intval(-100+intval($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"]));
if(intval($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"])<50){
	$sum_margin=100-intval($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"]);
	$sum_margin_neg="-";
}else{
	$sum_margin=$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"];
}

$SKIN_COMPANY_LOGO_HEIGHT=127+intval($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"]);
$SKIN_COMPANY_LOGO_WIDTH=intval($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"]);
$css[]="}
		
	
body{
	font: 10pt $ArticaSplashHotSpotFontFamily;
	background: #{$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]}$backPattern;
}
#sum{
	width: {$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"]};
	height: {$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"]};
	margin: {$sum_margin_neg}{$sum_margin}px auto;
}
h1{
	width: {$SKIN_COMPANY_LOGO_WIDTH}px;
	height: {$SKIN_COMPANY_LOGO_HEIGHT}px;
	background: transparent url('$prefix/img/$logo') no-repeat;
	margin: 0 27px 21px;
	}
	
a{
	color:#{$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]};
	text-decoration:underline;
}
	
a:visited{
	color:#{$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]};
}
	
a:link{
	color:#{$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]};
}
	
	
h1 span{
	display: none;
}
#content{
	width: {$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"]};
	height: {$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"]};
	background: $contentBack;
	$contentBorders
}
.f{
	padding: 23px 23px 45px 38px;
	overflow: hidden;
}
.field{
	clear:both;
	text-align: right;
	margin-bottom: 10px;
}
.field label{
	float:left;
	font-weight: bold;
	line-height: 42px;
}
	
.field input.active{
	background: url('$prefix/ressources/templates/Squid/i/input_act.png') no-repeat;
}
.button{
	width: 450px;
	float: right;
}
.button input{
	width: 69px;
	background: url('$prefix/ressources/templates/Squid/i/btn_bg.png') no-repeat;
	border: 0;
	font-weight: bold;
	height: 27px;
	float: left;
	padding: 0;
}
	
.Button2014-lg {
	border-radius: 6px 6px 6px 6px;
	-moz-border-radius: 6px 6px 6px 6px;
	-khtml-border-radius: 6px 6px 6px 6px;
	-webkit-border-radius: 6px 6px 6px 6px;
	font-size: $btsize;
	line-height: 1.33;
	padding: 10px 16px;
}
.Button2014-success {
	background-color: #5CB85C;
	border-color: #4CAE4C;
	color: #FFFFFF;
}
.Button2014 {
	-moz-user-select: none;
	border: 1px solid transparent;
	border-radius: 4px 4px 4px 4px;
	cursor: pointer;
	display: inline-block;
	font-size: 14px;
	font-weight: normal;
	line-height: 1.42857;
	margin-bottom: 0;
	padding: 6px 22px;
	text-align: center;
	vertical-align: middle;
	white-space: nowrap;
}
	
.form-horizontal .control-label {
	float: left;
	font-size: 14px;
	padding-top: 5px;
	text-align: right;
	width: 240px;
}
.form-horizontal .controls {
	margin-left: 250px;
	}

.form-horizontal button, input, select, textarea {
	font-size: 100%;
	margin: 0;
	vertical-align: middle;
}
.form-horizontal button, input {
	line-height: normal;
	}
.form-horizontal label, select, button, input[type=\"button\"], input[type=\"reset\"], input[type=\"submit\"], input[type=\"radio\"], input[type=\"checkbox\"] {
cursor: pointer;
}
.form-horizontal input, textarea, .uneditable-input {
	width: 250px;
	}
.form-horizontal textarea {
	height: auto;
}
.form-horizontal input[type=\"checkbox\"], input[type=\"radio\"] {
	border: 1px solid #CCCCCC;
	}
	.form-horizontal textarea, input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {
	background-color: #FFFFFF;
	border: 1px solid #CCCCCC;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	transition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;
	}
	.form-horizontal textarea:focus, input[type=\"text\"]:focus, input[type=\"password\"]:focus, input[type=\"datetime\"]:focus, input[type=\"datetime-local\"]:focus, input[type=\"date\"]:focus, input[type=\"month\"]:focus, input[type=\"time\"]:focus, input[type=\"week\"]:focus, input[type=\"number\"]:focus, input[type=\"email\"]:focus, input[type=\"url\"]:focus, input[type=\"search\"]:focus, input[type=\"tel\"]:focus, input[type=\"color\"]:focus, .uneditable-input:focus {
	border-color: rgba(82, 168, 236, 0.8);
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6);
	outline: 0 none;
	}
	.form-horizontal textarea {
	overflow: auto;
	vertical-align: top;
	}
	.form-horizontal h1, h2, h3, h4, h5, h6 {
	color: inherit;
	font-family: inherit;
	font-weight: bold;
	line-height: 20px;
	margin: 10px 0;
	text-rendering: optimizelegibility;
	}
	.form-horizontal h1, h2, h3, h4, h5, h6 *:first-letter {
	text-transform: capitalize;
	}
	.form-horizontal legend {
	-moz-border-bottom-colors: none;
	-moz-border-left-colors: none;
	-moz-border-right-colors: none;
	-moz-border-top-colors: none;
	border-color: -moz-use-text-color -moz-use-text-color #E5E5E5;
	border-image: none;
	border-style: none none solid;
	border-width: 0 0 1px;
	color: #333333;
	display: block;
	font-size: 21px;
	line-height: 40px;
	margin-bottom: 20px;
	padding: 0;
	width: 100%;
	}
	
	
	.form-horizontal label, input, button, select, textarea {
	font-size: 14px;
	font-weight: normal;
	line-height: 20px;
	}
	.form-horizontal input, button, select, textarea {
	font-family: 'Lucida Grande',Arial,Helvetica,sans-serif;
	}
	label {
	display: block;
	margin-bottom: 5px;
	}
	.form-horizontal select, textarea, input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {
	border-radius: 4px 4px 4px 4px;
	color: #555555;
	display: inline-block;
	font-size: 14px;
	height: auto;
	line-height: 20px;
	margin-bottom: 10px;
	padding: 4px 6px;
	vertical-align: middle;
	}
	.form-horizontal textarea, input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {
	background-color: #FFFFFF;
	border: 1px solid #CCCCCC;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	transition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;
	}
	.form-horizontal textarea:focus, input[type=\"text\"]:focus, input[type=\"password\"]:focus, input[type=\"datetime\"]:focus, input[type=\"datetime-local\"]:focus, input[type=\"date\"]:focus, input[type=\"month\"]:focus, input[type=\"time\"]:focus, input[type=\"week\"]:focus, input[type=\"number\"]:focus, input[type=\"email\"]:focus, input[type=\"url\"]:focus, input[type=\"search\"]:focus, input[type=\"tel\"]:focus, input[type=\"color\"]:focus, .uneditable-input:focus {
	border-color: rgba(82, 168, 236, 0.8);
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6);
	outline: 0 none;
	}
	
	a.Button2014, a.Button2014:link, a.Button2014:visited, a.Button2014:hover{
	color: #FFFFFF;
	text-decoration:none;
	}
	
	.Button2014-success {
	background-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"]} !important;
	border-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]} !important;
	color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"]} !important;
	}
	.Button2014-success:hover, .Button2014-success:focus, .Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
	background-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"]} !important;
	border-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]} !important;
	color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"]} !important;
	}
	.Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
	background-image: none;
	}
	.Button2014-success.disabled, .Button2014-success[disabled], fieldset[disabled] .Button2014-success, .Button2014-success.disabled:hover, .Button2014-success[disabled]:hover, fieldset[disabled] .Button2014-success:hover, .Button2014-success.disabled:focus, .Button2014-success[disabled]:focus, fieldset[disabled] .Button2014-success:focus, .Button2014-success.disabled:active, .Button2014-success[disabled]:active, fieldset[disabled] .Button2014-success:active, .Button2014-success.disabled.active, .Button2014-success.active[disabled], fieldset[disabled] .Button2014-success.active {
	background-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"]} !important;
	border-color: #{$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]} !important;
	}
		
		
.field input {
	background: url(\"$prefix/ressources/templates/Squid/i/input.png\") no-repeat scroll 0 0 #FFFFFF;
	border: medium none;
	color: #444444;
	font-size: 18px;
	font-weight: bolder;
	height: 25px;
	outline: medium none;
	padding: 7px 9px 8px;
	width: 279px;
}
	
.input-block-level {
	display: block;
	width: 100%;
	min-height: 30px;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
	}
	";
	$css[]="div .form {";
	if(!$ASIE){
	$css[]="background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
    background: -webkit-gradient(linear, center top, center bottom, from(#F1F1F1), to(#FFFFFF)) repeat scroll 0 0 transparent;
	background: -webkit-linear-gradient( #F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -o-linear-gradient(#F1F1F1, #FFFFFF) repeat scroll 0 0 transparent;
	background: -ms-linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
	background: linear-gradient(#F1F1F1, #ffffff) repeat scroll 0 0 transparent;
";
}
if($ASIE){
	$css[]="filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#F1F1F1', endColorstr='#ffffff');";
	$css[]="/* behavior:url($prefix/css/border-radius.htc); */";
	}
	$css[]="border: 1px solid #DDDDDD;
	border-radius: 5px 5px 5px 5px;
 	-moz-border-radius: 5px 5px 5px 5px;
    -khtml-border-radius: 5px 5px 5px 5px;
    -webkit-border-radius: 5px 5px 5px 5px;
    box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
    margin: 5px;
    padding: 5px;
}";
	
if($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"]<>null){
	
	$css[]=".footer{
	border-radius: 5px 5px 5px 5px;
 	-moz-border-radius: 5px 5px 5px 5px;
    -khtml-border-radius: 5px 5px 5px 5px;
    -webkit-border-radius: 5px 5px 5px 5px;
    box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
    margin: 5px;
    padding: 5px;
	background-color:#{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"]};
}";	
	
}
		
	$cssContent=@implode("\n", $css);

	
	$f[]="<!DOCTYPE html>";
	$f[]="<html lang=\"en\">";
	$f[]="<head>";
	$f[]="<meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">";
	$f[]="<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />";
	$f[]="$headerAdd";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/artica-theme/jquery-ui.custom.css\" />";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/jquery.jgrowl.css\" />";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/jquery.cluetip.css\" />";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/jquery.treeview.css\" />";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/thickbox.css\" media=\"screen\"/>";
	$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/jquery.qtip.css\" />";
	
	if($GLOBALS["AS_ROOT"]){
		$f[]="		<link rel=\"stylesheet\" type=\"text/css\" href=\"$prefix/css/hotspot.css\" />";
		@file_put_contents("/usr/share/artica-postfix/css/hotspot.css", $cssContent);
		@chmod("/usr/share/artica-postfix/css/hotspot.css",0755);
		$cssContent=null;
	}
	
	$f[]="<style type=\"text/css\">";
	$f[]="$cssContent";
	
	$f[]="	</style>";
	$f[]="<title>$ArticaSplashHotSpotTitle</title>";
	$f[]="<!-- HEAD TITLE: ressources/templates/Wordpress/TITLE -->";
	$f[]="<link rel=\"icon\" href=\"/ressources/templates/Wordpress/favicon.ico\" type=\"image/x-icon\" />";
	$f[]="<link rel=\"shortcut icon\" href=\"/ressources/templates/Wordpress/favicon.ico\" type=\"image/x-icon\" />";
	$f[]="<!-- Prepend:  -->";
	$f[]="<link rel=\"icon\" type=\"image/x-icon\" href=\"ressources/templates/default/favicon.ico\" />";
	$f[]="<!--[if IE]><link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"ressources/templates/default/favicon.ico\" /><![endif]-->";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$prefix/js/jquery-1.8.3.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$prefix/js/jquery-ui-1.8.22.custom.min.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$prefix/default.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$prefix/js/rloader1.5.4_min.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\">		$.rloader([ {src:'/mouse.js'},";
	$f[]="	{src:'$prefix/js/md5.js'},";
	$f[]="	{src:'$prefix/TimersLogs.js'},";
	$f[]="	{src:'$prefix/js/cookies.js'},";
	$f[]="	{src:'$prefix/js/thickbox-compressed.js'},";
	$f[]="	{src:'$prefix/js/jquery.jgrowl_minimized.js'},";
	$f[]="	{src:'$prefix/js/jquery.cluetip.js'},";
	$f[]="	{src:'$prefix/js/jquery.treeview.min.js'},";
	$f[]="	{src:'$prefix/js/jquery.treeview.async.js'},";
	$f[]="	{src:'$prefix/js/jquery.tools.min.js'},";
	$f[]="	{src:'$prefix/js/jquery.cookie.js'},";
	$f[]="	{src:'$prefix/js/jquery.watermark.min.js'},";
	$f[]="	{src:'$prefix/bootstrap/js/bootstrap-tab.js'},";
	$f[]="	{src:'$prefix/bootstrap/js/bootstrap-tooltip.js'},";
	$f[]="	{src:'$prefix/bootstrap/js/bootstrap-button.js'} ]);</script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$prefix/XHRConnection.js\"></script>";
	
	$f[]="</head>";
	$f[]="";
	$f[]="<body>";
	$f[]="<div style=\"postition:absolute;top:0px;left:80%;width:100%\">";
	$f[]="<table style='width:100%;padding:0px;margin:0px'>";
	$f[]="<tbody><tr>";
	$f[]="<td width=100%>&nbsp;<td>";
	$f[]="<td width=1% nowrap><div id=\"user_info\" style='text-align:right;width:90px'>";
	$f[]=" <div id=\"langs\" style=\"text-align:right;\">";
	$f[]="	";
	$f[]="    </div>";
	$f[]="</div>";
	$f[]="</td>";
	$f[]="</tr>";
	$f[]="</tbody>";
	$f[]="</table>";
	$f[]="</div>";
	$f[]="";
	$f[]="  <div id=\"sum\">";
	$f[]="    <div id=\"header\">";
	$f[]="      <h1><span>$hostname</span></h1>";
	$f[]="    </div>";
	$f[]="$error";
	$f[]="$content";
	
	if(!$users->CORP_LICENSE){
		$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]="					<span style='color:white'>
						<center style='margin:5px;font-size:{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]};padding:5px;'>-&nbsp;$hostname&nbsp;-</center>
						<center style='margin:5px;font-size:{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]};padding:5px;
						border-top:1px solid white;border-bottom:1px solid white'>-&nbsp;ArticaTech&nbsp;-</center>
				Copyright 2003 - ". date("Y")."&nbsp;<a href=\"http://www.articatech.com\" style='color:white'>Artica Tech</a>
				";
	}
	
	$f[]="";
	$f[]="    <div class=\"footer\">";
	$f[]="    	<center style='font-size:{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]};color:white'>{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]}</center>";

	$f[]="    </div><!-- /#footer -->";
	$f[]="  </div>";
	$f[]="";
	$f[]="</body>";
	$f[]="</html>";
	return @implode("\n", $f);	
	
}




?>