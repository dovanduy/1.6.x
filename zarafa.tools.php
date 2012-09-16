<?php
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
	$confirm_remove_zarafa_db=$tpl->javascript_parse_text("{confirm_remove_zarafa_db}");
	$trash=Paragraphe("table-delete-64.png", "{REMOVE_DATABASE}", "{REMOVE_DATABASE_ZARAFA_TEXT}","javascript:REMOVE_DATABASE()");
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

$q=new mysql();
$globsvls=$q->SHOW_VARIABLES();
if($globsvls["innodb_file_per_table"]=="OFF"){
	$innodb_file_per_table=Paragraphe("tables-64-running.png",'{convertto_innodb_file_per_table}',"{convertto_innodb_file_per_table_text}","javascript:Loadjs('zarafa.innodbfpt.php');");
	
}else{
	$innodb_file_per_table=Paragraphe("tables-64-running-grey.png",'{convertto_innodb_file_per_table}',"{already_converted}","");
}

	$zarafabackup=Paragraphe("64-backup.png",'{backup_parameters}',"{zarafa_backup_parameters}",
	"javascript:Loadjs('zarafa.backup-params.php');");



	$multipledomaines=Paragraphe("folder-org-64.png",'{multidomains}',
	"{multidomains_text}","javascript:Loadjs('postfix.index.php?script=multidomains');");
	
	$junkmove=Paragraphe("mail-flag-64.png",'{junk_mail_folder}',"{junk_mail_folder_zexplain}",
	"javascript:Loadjs('zarafa.junkheader.php');");
	
	
	$autowitelist=Paragraphe("contact-card-show-64.png",'{addressbooks_whitelisting}',"{addressbooks_whitelisting_explain}",
	"javascript:Loadjs('zarafa.nabwhitelist.php');");
	
	$dagent=Paragraphe("64-restore-mailbox.png", "{delivery_agent}", "{delivery_agent_parameters_text}",
	"javascript:Loadjs('zarafa.dagent.php');");
	
	if($users->ZARAFA_SEARCH_INSTALLED){
		$zarafaSearch=Paragraphe("loupe-64.png", "Zarafa Search", "{zarafa_search_text}",
		"javascript:Loadjs('zarafa.search.php');");
	}


	$tr[]=$ical;
	$tr[]=$dagent;
	$tr[]=$zarafaSearch;
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
	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}
		}

if($t<3){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}	
	
	
$time=time();
$html="
<div id='$time'></div>
<div style='width:700px'>". implode("\n",$tables)."</div>	
	
	
<script>	
var x_REMOVE_DATABASE=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>5){alert(tempvalue);}
     	RefreshTab('main_config_zarafa');
      }	
		
	function REMOVE_DATABASE(){
		if(confirm('$confirm_remove_zarafa_db')){
			var XHR = new XHRConnection();
			XHR.appendData('remove-db','1');
			AnimateDiv('$time');
			XHR.sendAndLoad('$page', 'POST',x_REMOVE_DATABASE);
			}
	}
</script>

";

echo $tpl->_ENGINE_parse_body($html);

}
function removedb(){
	$q=new mysql();
	$q->DELETE_DATABASE("zarafa");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?removeidb=yes");
	$sock->getFrameWork("cmd.php?zarafa-restart-server=yes");
	
}