<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_POST["remove-db"])){removedb();exit;}
	
	
popup();



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	
	
	$bulkExport=Paragraphe("sync-64-grey.png",'{bulk_imap_export}',"{missing_imap_sync_text}");
	$salearn=Paragraphe('64-learning-grey.png','{salearnschedule}','{feature_not_installed}',"");



if($users->imapsync_installed){
	$bulkExport=Paragraphe("sync-64.png",'{bulk_imap_export}',"{bulk_imap_export_text}","javascript:Loadjs('imap.bulk.export.php');");
}

if($users->spamassassin_installed){
	$salearn=Paragraphe('64-learning.png','{salearnschedule}','{salearnschedule_text}',"javascript:Loadjs('zarafa.salearn.php')");
}


$softDelete=Paragraphe("bad-email-64.png",'{softdelete_option}',"{softdelete_option_explain}","javascript:Loadjs('zarafa.softdelete.php');");
$multiple=Paragraphe("postfix-multi-64.png",'{multiple_zarafa_instances}',
"{multiple_zarafa_instances_text}","javascript:Loadjs('zarafa.multiple-enable.php');");

$ical=Paragraphe("busycal-64.png",'{APP_ZARAFA_ICAL}',"{ZARAFA_CALDAV_EXPLAIN}","javascript:Loadjs('zarafa.caldav.php');");



	$zarafabackup=Paragraphe("64-backup.png",'{mailboxes_backups}',"{mailboxes_backups_text_admin}",
	"javascript:Loadjs('imap.mbx.backup.php');");



	$multipledomaines=Paragraphe("folder-org-64.png",'{multidomains}',
	"{multidomains_text}","javascript:Loadjs('postfix.index.php?script=multidomains');");
	
	$junkmove=Paragraphe("mail-flag-64.png",'{junk_mail_folder}',"{junk_mail_folder_zexplain}",
	"javascript:Loadjs('zarafa.junkheader.php');");
	
	
	$autowitelist=Paragraphe("contact-64.png",'{addressbooks_whitelisting}',"{addressbooks_whitelisting_explain}",
	"javascript:Loadjs('zarafa.nabwhitelist.php');");
	
	$dagent=Paragraphe("64-restore-mailbox.png", "{delivery_agent}", "{delivery_agent_parameters_text}",
	"javascript:Loadjs('zarafa.dagent.php');");
	
	
	$ZarafaAlwaysSendDelegates=Paragraphe("64-resend.png", "{ZarafaAlwaysSendDelegates}", "{ZarafaAlwaysSendDelegates_text}",
			"javascript:Loadjs('zarafa.ZarafaAlwaysSendDelegates.php');");	
	
	
	
	
	if($users->ZARAFA_SEARCH_INSTALLED){
		$zarafaSearch=Paragraphe("loupe-64.png", "Zarafa Search", "{zarafa_search_text}",
		"javascript:Loadjs('zarafa.search.php');");
	}
	
	$recover=Paragraphe("database-error-64.png", "{zarafa_database_recovery}", "{zarafa_database_recovery_text}",
		"javascript:Loadjs('zarafa.recover.php');");
	
	
	$DisableAccountLessThan4Caracters=Paragraphe("contact-64.png", "{uid_length_protection}", "{uid_length_protection_text}",
			"javascript:Loadjs('zarafa.DisableAccountLessThan4Caracters.php');");

	$tr[]=$ical;
	$tr[]=$dagent;
	$tr[]=$ZarafaAlwaysSendDelegates;
	$tr[]=$zarafaSearch;
	$tr[]=$DisableAccountLessThan4Caracters;
	$tr[]=$junkmove;
	$tr[]=$innodb_file_per_table;
	$tr[]=$multipledomaines;
	$tr[]=$bulkExport;
	$tr[]=$autowitelist;
	$tr[]=$salearn;
	$tr[]=$softDelete;
	$tr[]=$multiple;
	$tr[]=$trash;
	$tr[]=$zarafabackup;
	
	$compile=CompileTr4($tr);
$time=time();
$html="
<div id='$time'></div>
<center>
<div style='width:1000px'>$compile</div>	
</center>
	
	


";

echo $tpl->_ENGINE_parse_body($html);

}
function removedb(){
	$sock=new sockets();
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?removeidb=yes");
	
	
}