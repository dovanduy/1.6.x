<?php
session_start();

if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');

$users=new usersMenus();
if(!$users->AsHotSpotManager){die();}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["smtp-params"])){smtp_notifs();exit;}
if(isset($_POST["ENABLED_AUTO_LOGIN"])){Save();exit;}
if(isset($_POST["tls_enabled"])){Save();exit;}

if(isset($_GET["test-smtp-js"])){tests_smtp();exit;}

js();


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();

	if(!$users->WIFIDOG_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{ERROR_SERVICE_NOT_INSTALLED} <hr><center>".button("{manual_update}", "Loadjs('update.upload.php')",32)."</center>");
		return;
	}

	$array["popup"]='{self_register}';
	$array["smtp-params"]='{smtp_parameters}';
	$fontsize=18;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="members"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.members.php?members=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t$YahooWinUri\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "squid_hotspot_smtp")."<script>LeftDesign('wifi-white-256-opac20.png');</script>";


	
}

function js(){
	
		$tpl=new templates();
		$page=CurrentPageName();
		header("content-type: application/x-javascript");
		$titl=$tpl->javascript_parse_text("{smtp_parameters}");
		echo "YahooWin3('1202','$page?tabs=yes','$titl');";
}

function tests_smtp(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	$ArticaSplashHotSpotEndTime=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-confirm=NONE";
	$tpl=new templates();
	$smtp_sender=$ArticaHotSpotSMTP["smtp_sender"];
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration";}
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	
	$smtp_senderTR=explode("@",$smtp_sender);
	$instance=$smtp_senderTR[1];
	
	$random_hash = md5(date('r', time()));
	
	$body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="Subject: {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}";
	$body[]="To: $smtp_sender";
	$body[]="";
	$body[]="";
	$body[]=$ArticaHotSpotSMTP["REGISTER_MESSAGE"];
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
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');";
		return;
	}
	
	
	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$smtp_sender,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');";
		return;
	}
	
	echo "alert('".$tpl->javascript_parse_text("{$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}\nTo $smtp_sender: {success}")."');";			
	$smtp->quit();	
	
}

function popup(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$t=time();
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	$ArticaHotSpotSMTP=$sock->FillSMTPNotifsDefaults($ArticaHotSpotSMTP);
	
	if(!isset($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!isset($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	
	
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]="As an customer, you can avail of free access to Internet by registering here.<br>After filling the form you will have access to a short period in order to confirm your registration";}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]="Your Account is Now Validated!<br>Thank you for confirming your email address.";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"]="Success<br>\nA message as been sent to you.<br>\nPlease check your WebMail system in order to recover your password<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"]="Fill out the form below to change your password";}
	if(trim($ArticaHotSpotSMTP["TERMS_EXPLAIN"])==null){$ArticaHotSpotSMTP["TERMS_EXPLAIN"]="To signup you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";}
	if(trim($ArticaHotSpotSMTP["TERMS_CONDITIONS"])==null){$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=@file_get_contents("ressources/databases/wifi-terms.txt");}
	if(trim($ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"])==null){$ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"]="For security reasons We need to hook HTTPS websites.<br>This behavior will generate browser warning when surfing trough HTTPS websites.<br>In order to remove this warning, we suggest to download the certificate by clicking on the &laquo;Download certificate&raquo; button and import it in your prefered browser the trusted SSL certificates section.";}
	
	
	
	
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	if($ArticaHotSpotSMTP["REGISTER_MAX_TIME"]<5){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	
	$html="
		
	<div id='notif1-$t' class=form style='width:98%'>
	". Paragraphe_switch_img("{enable_hotspot_autologin}", "{enable_hotspot_autologin_explain}","ENABLED_AUTO_LOGIN",$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"],null,750)."
	". Paragraphe_switch_img("{enable_ssl_portal}", "{enable_ssl_portal_explain}","SSL_PORTAL-$t",$ArticaHotSpotSMTP["SSL_PORTAL"],null,750)."
	<table style='width:99%' >
	<tr>
		<td class=legend style='font-size:22px'>{max_time_register}:</td>
		<td>". Field_array_Hash($Timez,"REGISTER_MAX_TIME", $ArticaHotSpotSMTP["REGISTER_MAX_TIME"],"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{use_terme_of_use}:</td>
		<td>". Field_checkbox("USE_TERMS-$t",1,intval($ArticaHotSpotSMTP["USE_TERMS"]),"USE_TERMS$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{Terms_Conditions_explain}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TERMS_EXPLAIN-$t'>{$ArticaHotSpotSMTP["TERMS_EXPLAIN"]}</textarea>
		</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{Terms_Conditions}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TERMS_CONDITIONS-$t'>{$ArticaHotSpotSMTP["TERMS_CONDITIONS"]}</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{register_form_explain}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE_EXPLAIN-$t'>{$ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]}</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ssl_certificate_explain}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SSL_PORTAL_EXPLAIN-$t'>{$ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"]}</textarea>
		</td>
	</tr>	
	<tr>
		<td align='right' colspan=2>".button('{apply}',"Save$t();",32)."</td>
	</tr>
</table>
</div>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}

function Save$t(){	
	var XHR = new XHRConnection();
	XHR.appendData('REGISTER_MAX_TIME',document.getElementById('REGISTER_MAX_TIME').value);
	if(document.getElementById('USE_TERMS-$t').checked){XHR.appendData('USE_TERMS',1);}else {XHR.appendData('USE_TERMS',0);}
	XHR.appendData('TERMS_EXPLAIN',document.getElementById('TERMS_EXPLAIN-$t').value);
	XHR.appendData('ENABLED_AUTO_LOGIN',document.getElementById('ENABLED_AUTO_LOGIN').value);
	XHR.appendData('SSL_PORTAL',document.getElementById('SSL_PORTAL-$t').value);
	XHR.appendData('SSL_PORTAL_EXPLAIN',document.getElementById('SSL_PORTAL_EXPLAIN-$t').value);
	XHR.appendData('REGISTER_MESSAGE_EXPLAIN',document.getElementById('REGISTER_MESSAGE_EXPLAIN-$t').value);
	XHR.appendData('TERMS_CONDITIONS',document.getElementById('TERMS_CONDITIONS-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	
	
	
function USE_TERMS$t(){
	document.getElementById('TERMS_EXPLAIN-$t').disabled=true;
	document.getElementById('TERMS_CONDITIONS-$t').disabled=true;
	if(document.getElementById('USE_TERMS-$t').checked){
	document.getElementById('TERMS_EXPLAIN-$t').disabled=false;
	document.getElementById('TERMS_CONDITIONS-$t').disabled=false;	
	}
}
USE_TERMS$t();	
</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function smtp_notifs(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();

	$t=time();
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	$ArticaHotSpotSMTP=$sock->FillSMTPNotifsDefaults($ArticaHotSpotSMTP);
	
	if(!isset($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!isset($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!isset($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	
	
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_AUTO_LOGIN"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_SMTP"])){$ArticaHotSpotSMTP["ENABLED_SMTP"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["REGISTER_MAX_TIME"])){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE_EXPLAIN"]="As an customer, you can avail of free access to Internet by registering here.<br>After filling the form you will have access to a short period in order to confirm your registration";}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]="Your Account is Now Validated!<br>Thank you for confirming your email address.";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["CONFIRM_MESSAGE"]==null){$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"]="Success<br>\nA message as been sent to you.<br>\nPlease check your WebMail system in order to recover your password<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"]="Fill out the form below to change your password";}
	if(trim($ArticaHotSpotSMTP["TERMS_EXPLAIN"])==null){$ArticaHotSpotSMTP["TERMS_EXPLAIN"]="To signup you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";}
	if(trim($ArticaHotSpotSMTP["TERMS_CONDITIONS"])==null){$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=@file_get_contents("ressources/databases/wifi-terms.txt");}
	if(trim($ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"])==null){$ArticaHotSpotSMTP["SSL_PORTAL_EXPLAIN"]="For security reasons We need to hook HTTPS websites.<br>This behavior will generate browser warning when surfing trough HTTPS websites.<br>In order to remove this warning, we suggest to download the certificate by clicking on the &laquo;Download certificate&raquo; button and import it in your prefered browser the trusted SSL certificates section.";}
 
	
	
	
	if($ArticaHotSpotSMTP["REGISTER_SUBJECT"]==null){$ArticaHotSpotSMTP["REGISTER_SUBJECT"]="HotSpot account validation";}
	if($ArticaHotSpotSMTP["REGISTER_MAX_TIME"]<5){$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]=5;}
	
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";

	//Switchdiv

	$html="
	<div id='notif1-$t' class=form style='width:98%'>
	". Paragraphe_switch_img("{enable_hotspot_smtp}", "{enable_hotspot_smtp_explain}","ENABLED_SMTP",$ArticaHotSpotSMTP["ENABLED_SMTP"],null,750)."
	
	<table style='width:99%' >
	
	
	

	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_subject}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_SUBJECT-$t'>{$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE-$t'>{$ArticaHotSpotSMTP["REGISTER_MESSAGE"]}</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_message_success}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE_SUCCESS-$t'>{$ArticaHotSpotSMTP["REGISTER_MESSAGE_SUCCESS"]}</textarea>
		</td>
	</tr>	
	

	<tr>
		<td class=legend style='font-size:22px'>{smtp_recover_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='RECOVER_MESSAGE-$t'>{$ArticaHotSpotSMTP["RECOVER_MESSAGE"]}</textarea>
		</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{smtp_recover_message_confirmation}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='RECOVER_MESSAGE_CONFIRM-$t'>{$ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"]}</textarea>
		</td>
	</tr>
		
	<tr>
		<td class=legend style='font-size:22px'>{smtp_confirm}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='CONFIRM_MESSAGE-$t'>{$ArticaHotSpotSMTP["CONFIRM_MESSAGE"]}</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{recover_password_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='RECOVER_MESSAGE_P1-$t'>{$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"]}</textarea>
		</td>
	</tr>
	
	
	
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text("smtp_server_name-$t",trim($ArticaHotSpotSMTP["smtp_server_name"]),'font-size:22px;padding:3px;width:250px')."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text('smtp_server_port',trim($ArticaHotSpotSMTP["smtp_server_port"]),'font-size:22px;padding:3px;width:90px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_sender}:</strong></td>
		<td>" . Field_text('smtp_sender',trim($ArticaHotSpotSMTP["smtp_sender"]),'font-size:22px;padding:3px;width:290px')."</td>
	</tr>

	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text('smtp_auth_user',trim($ArticaHotSpotSMTP["smtp_auth_user"]),'font-size:22px;padding:3px;width:200px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($ArticaHotSpotSMTP["smtp_auth_passwd"]),'font-size:22px;padding:3px;width:200px')."</td>
				</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox("tls_enabled",1,$ArticaHotSpotSMTP["tls_enabled"])."</td>
		</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox("ssl_enabled",1,$ArticaHotSpotSMTP["ssl_enabled"])."</td>
		</tr>
	<tr>
		<td align='right' colspan=2>
				
				".button('{test}',"TestSMTP$t();",32)."&nbsp;".button('{apply}',"SaveArticaSMTPNotifValues$t();",32)."</td>
	</tr>
</table>
</div>
<script>

function TestSMTP$t(){
	SaveArticaSMTPNotifValues$t();
	Loadjs('$page?test-smtp-js=yes');
}


var x_SaveArticaSMTPNotifValues$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}

function SaveArticaSMTPNotifValues$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
	if(document.getElementById('tls_enabled').checked){XHR.appendData('tls_enabled',1);}else {XHR.appendData('tls_enabled',0);}
	if(document.getElementById('ssl_enabled').checked){XHR.appendData('ssl_enabled',1);}else {XHR.appendData('ssl_enabled',0);}
	
	
	
	XHR.appendData('ENABLED_SMTP',document.getElementById('ENABLED_SMTP').value);
	
	XHR.appendData('REGISTER_MESSAGE',document.getElementById('REGISTER_MESSAGE-$t').value);
	XHR.appendData('RECOVER_MESSAGE',document.getElementById('RECOVER_MESSAGE-$t').value);
	XHR.appendData('RECOVER_MESSAGE_CONFIRM',document.getElementById('RECOVER_MESSAGE_CONFIRM-$t').value);
	XHR.appendData('CONFIRM_MESSAGE',document.getElementById('CONFIRM_MESSAGE-$t').value);
	XHR.appendData('REGISTER_SUBJECT',document.getElementById('REGISTER_SUBJECT-$t').value);
	XHR.appendData('REGISTER_MESSAGE_SUCCESS',document.getElementById('REGISTER_MESSAGE_SUCCESS-$t').value);
	XHR.appendData('RECOVER_MESSAGE_P1',document.getElementById('RECOVER_MESSAGE_P1-$t').value);
	
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name-$t').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
	XHR.appendData('smtp_auth_passwd',pp);
	XHR.appendData('smtp_notifications','yes');
	XHR.sendAndLoad('$page', 'POST',x_SaveArticaSMTPNotifValues$t);
}


</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	
	if(isset($_POST["smtp_auth_passwd"])){
		$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	}
	$sock=new sockets();
	
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$ArticaHotSpotSMTP[$num]=utf8_encode($ligne);
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($ArticaHotSpotSMTP)), "ArticaHotSpotSMTP");
}
