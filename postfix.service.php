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
$tr=Transport_rules($tr);

$icons=CompileTr4($tr);

$html="<center><div style='width:80%'>$icons</div></center>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($icons);


function Transport_rules($tr){
	//$datas=GET_CACHED(__FILE__,__FUNCTION__,null,TRUE);
	//if($datas<>null){return $datas;}
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$EnableArticaSMTPFilter=$sock->GET_INFO("EnableArticaSMTPFilter");
	$EnableArticaSMTPFilter=0;

	$failedtext="{ERROR_NO_PRIVILEGES_OR_PLUGIN_DISABLED}";
	$network=Buildicon64('DEF_ICO_POSTFIX_NETWORK');
	//$transport=Buildicon64('DEF_ICO_POSTFIX_TRANSPORT');
	$applysettings=Buildicon64('DEF_ICO_POSTFIX_APPLY');
	$queue=Buildicon64('DEF_ICO_POSTFIX_QUEUE');
	$relayhost=Buildicon64('DEF_ICO_POSTFIX_RELAYHOST');
	$relayhostssl=Buildicon64('DEF_ICO_POSTFIX_RELAYHOSTSSL');
	$notifs=Buildicon64('DEF_ICO_POSTFIX_NOTIFS');
	$mailman=Buildicon64('DEF_ICO_POSTFIX_MAILMAN');
	$mailgraph=Buildicon64('DEF_ICO_EVENTS_MAILGRAPH');
	



	$POSTFIX_MAIN=base64_encode("POSTFIX_MAIN");

	$applysettings=null;

	$additional_databases=Paragraphe('databases-add-64-grey.png','LDAP','{remote_users_databases_text}',
			"");
	$applysettings=null;


	if($users->POSTFIX_LDAP_COMPLIANCE){
		$master=base64_encode("MASTER");
		$additional_databases=Paragraphe('databases-add-64.png','LDAP','{remote_users_databases_text}',
				"javascript:Loadjs('postfix.smtp.ldap.maps.php?hostname=master&ou=master')");

	}

	$additional_databases2=Paragraphe('databases-add-64.png','{remote_users_databases}','{remote_users_databases_text}',
			"javascript:Loadjs('postfix.smtp.db.maps.php?hostname=master&ou=master')");

	$ecluse=Paragraphe('ecluse-64.png','{domain_throttle}','{domain_throttle_text}',
			"javascript:Loadjs('postfix.smtp.throttle.php?hostname=master&ou=master')");

	$iprotator=Paragraphe('ip-rotator-64.png','{ip_rotator}','{ip_rotator_text}',
			"javascript:Loadjs('postfix.ip.rotator.php?hostname=master&ou=master')");

	$mailinglist_behavior=Paragraphe('64-bg_addresses.png','{mailing_list_behavior}','{mailing_list_behavior_text}',
			"javascript:Loadjs('postfix.maillinglist.php')");



	if($EnablePostfixMultiInstance==1){


		$relayhostssl=null;
		$orange=null;
		$oleane=null;
		$oneone=null;
		$wanadoo=null;
		$notifs=null;
		$mailman=null;
		$mailgraph=null;
		$applysettings=Paragraphe("org-smtp-settings-64.png","{OU_BIND_ADDR_AFFECT}","{OU_BIND_ADDR_AFFECT_TEXT}","javascript:Loadjs('system.nic.config.php?postfix-virtual=yes')");
	}

	$redirect=Paragraphe("redirect-64-grey.png","{REDIRECT_SERVICE}","{REDIRECT_SERVICE_TEXT}","");

	if($EnableArticaSMTPFilter==1){
		$redirect=Paragraphe("redirect-64.png","{REDIRECT_SERVICE}","{REDIRECT_SERVICE_TEXT}","javascript:Loadjs('artica-filter.redirect.php')");

	}





	$tr[]=$network;
	
	$tr[]=$smtp_generic_maps;
	//$tr[]=$additional_databases;
	$tr[]=$additional_databases2;
	$tr[]=$redirect;
	$tr[]=$mailinglist_behavior;
	
	$tr[]=$ecluse;
	$tr[]=$iprotator;
	
	$tr[]=$applysettings;
	$tr[]=$queue;

	$tr[]=$relayhostssl;
	$tr[]=$notifs;
	
	$tr[]=$mailman;
	$tr[]=$mailgraph;
	return $tr;


}