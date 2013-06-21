<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}



$postfixStop=Paragraphe('pause-64.png','{stop_messaging}','{stop_messaging_text}',"javascript:Loadjs('postfix.stop.php')",90);
$performances=Paragraphe('folder-performances-64.png','{performances_settings}','{performances_settings_text}',"javascript:Loadjs('postfix.performances.php')",90);
$debug=Paragraphe('syslog-64.png','{POSTFIX_DEBUG}','{POSTFIX_DEBUG_TEXT}',"javascript:Loadjs('postfix.debug.php?hostname=master&ou=master')",90);
$artica_stats=Paragraphe('graphs-48.png','{ARTICA_STATS}','{ARTICA_SMTP_STATS_TEXT}',"javascript:Loadjs('postfix.artica-stats.php')",90);
$mastercf=Paragraphe('folder-script-64-master.png','{master.cf}','{mastercf_explain}',"javascript:Loadjs('postfix.master.cf.php?script=yes')",90) ;
$maincf=Paragraphe('folder-script-64.png','{main.cf}','{main.cf_explain}',"javascript:Loadjs('postfix.main.cf.php')",90);
$maincfedit=Paragraphe('folder-maincf-64.png','{main.cf_edit}','{main.cfedit_explain}',"javascript:Loadjs('postfix.main.cf.edit.php?js=yes')",90);
$other=Paragraphe('folder-tools2-64.png','{other_settings}','{other_settings_text}',"javascript:Loadjs('postfix.other.php')",90);
$RemoteSyslog=Paragraphe("syslog-64-client.png","{RemoteSMTPSyslog}","{RemoteSMTPSyslogText}","javascript:Loadjs('syslog.smtp-client.php');");
$HaProxy=Paragraphe("64-computer-alias.png","{load_balancing_compatibility}","{load_balancing_compatibility_text}",
		"javascript:Loadjs('postfix.haproxy.php?hostname=master&ou=master');");
$varspool=Paragraphe("folder-64-fetchmail.png","{move_the_spooldir}","{move_the_spooldir_text}",
		"javascript:Loadjs('postfix.varspool.php?hostname=master&ou=master');");

$removePostfix=Paragraphe("software-remove-64.png","{remove_postfix_section}","{remove_postfix_section_text}",
		"javascript:Loadjs('postfix.remove.php');");


$mailbox_cmd=Paragraphe("64-restore-mailbox.png","{mailbox_agent}","{mailbox_agent_text}",
"javascript:Loadjs('postfix.mailbox_transport.php?hostname=master&ou=master');");


if($user->MAILMAN_INSTALLED){
	$mailman=Paragraphe('mailman-64.png','{APP_MAILMAN}','{manage_distribution_lists}',"javascript:Loadjs('mailman.php?script=yes')");
}

$banner=Paragraphe('banner-loupe-64.png','{SMTP_BANNER}','{SMTP_BANNER_TEXT}',
		"javascript:Loadjs('postfix.banner.php?hostname=master&ou=master')");

$mime=Paragraphe('email-settings-64.png','{MIME_OPTIONS}','{MIME_OPTIONS_TEXT}',
		"javascript:Loadjs('postfix.mime.php?hostname=master&ou=master')");



$tr[]=$postfixStop;
$tr[]=$debug;
$tr[]=$artica_stats;
$tr[]=$banner;
$tr[]=$mime;
$tr[]=$RemoteSyslog;
$tr[]=$HaProxy;
$tr[]=$mailman;
$tr[]=$mailbox_cmd;
$tr[]=$mastercf;
$tr[]=$maincf;
$tr[]=$maincfedit;
$tr[]=$performances;
$tr[]=$varspool;
$tr[]=$other;
$tr[]=$removePostfix;

$icons=CompileTr3($tr);

$html="<center><div style='width:80%'>$icons</div></center>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);