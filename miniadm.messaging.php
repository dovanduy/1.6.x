<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["messaging-stats"])){messaging_stats();exit;}
main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_postfix_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT DATE_SUB(NOW(),INTERVAL 1 HOUR) as tdate"));
	$currenthour=date("YmdH",strtotime($ligne["tdate"]))."_hour";
	
	
	$rows=$q->COUNT_ROWS($currenthour);
	$jsadd=null;
	if($rows>0){$jsadd="LoadAjax('statistics-$t','$page?messaging-stats=yes');";}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{mymessaging}</H1>
		<p>{mymessaging_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?messaging-left=yes');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function messaging_left(){
	if(!isset($_SESSION["POSTFIX_SERVERS"])){$_SESSION["POSTFIX_SERVERS"]=array();}
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
	$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
	if(!is_numeric($EnableFetchmail)){$EnableFetchmail=0;}
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
	$OfflineImapBackupTool=$sock->GET_INFO("OfflineImapBackupTool");
	if(!is_numeric($OfflineImapBackupTool)){$OfflineImapBackupTool=0;}

	
	if($users->AsPostfixAdministrator){
		$t[]=Paragraphe("server-setup-64.png", "{MESSAGING_SERVICE}", "{MESSAGING_SERVICE_TEXT}","miniadm.messaging.postfix.php");
		
	}
	
	if(count($_SESSION["POSTFIX_SERVERS"])>0){
		$POSTFIX_SERVERS=$_SESSION["POSTFIX_SERVERS"];
		while (list ($hostname, $none) = each ($POSTFIX_SERVERS) ){
		$t[]=Paragraphe("server-setup-64.png", "$hostname", 
				"{MESSAGING_SERVICE_TEXT}","miniadm.messaging.postfix-multi.php?hostname=$hostname&ou={$_SESSION["ou"]}");
			
		}
		
	}
	
	
	if($users->AllowFetchMails){
		if($users->fetchmail_installed){
			if($EnableFetchmail==1){
				$t[]=Paragraphe("fetchmail-rule-64.png", "{myretreival_mailrules}", "{retreival_mailrules_text}","miniadm.fetchmail.php");
				
			}
			
		}
	}
	
	if($OfflineImapBackupTool==0){$users->AsOwnMailBoxBackup=false;}
	
	if($users->AsOwnMailBoxBackup){
		$t[]=Paragraphe("64-backup.png", "{mailboxes_backups}", "{mailboxes_backups_text}","miniadm.mbxbackup.php");
	}else{
		$t[]=Paragraphe("64-backup-grey.png", "{mailboxes_backups}", "{mailboxes_backups_text}");
	}
	
	if($users->AllowEditAsWbl){
		$t[]=Paragraphe("domain-whitelist-64.png", "{white_black_smtp}", "{white_black_smtp_text}","miniadm.messaging.wbl.php");
	}
	
	if($users->AllowEditAliases){
		$t[]=Paragraphe("rebuild-mailboxes-64.png", "{aliases}", "{enduser_aliases_text}","miniadm.aliases.php");
	}

	if($users->AllowUserMaillog){
		$t[]=Paragraphe("64-mailevents.png", "{messaging_events}", "{messaging_events_text}","miniadm.maillog.php");
	}
	
	if($users->ZARAFA_INSTALLED){
		$t[]=Paragraphe("contact-card-csv-64.png", "{import_contacts}", "{import_contacts_csv_text}","miniadm.zarafa-contacts-csv.php");
	}
	
	if($MailArchiverEnabled==1){
		$t[]=Paragraphe("64-backup.png", "{my_backuped_mails}", "{my_backuped_mails_text}","miniadm.messaging.backup.php");
	}	
	
	$t[]=Paragraphe("statistics-64.png", "{messaging_statistics}", "{my_messaging_statistics}","miniadm.messaging.user.stats.php");


	
	$html="<div class=BodyContent><center><div style='width:95%'>".CompileTr3($t,"none")."</div></center></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function messaging_stats(){
	
	$q=new mysql_postfix_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT DATE_SUB(NOW(),INTERVAL 1 HOUR) as tdate"));
	$currenthour=date("YmdH",strtotime($ligne["tdate"]))."_hour";	
	$timeAffiche=date("H",strtotime($ligne["tdate"]));
	$tpl=new templates();
	$ct=new user($_SESSION["uid"]);
	$mails=$ct->HASH_ALL_MAILS;
	while (list ($index, $message) = each ($mails) ){
		$q1[]=" (`mailto`='$message')";
		$q2[]=" (`mailfrom`='$message')";
		
	}
	
	$sql="SELECT COUNT(zmd5) as hits, SUM(mailsize) as size FROM (SELECT zmd5,mailsize from $currenthour
	 WHERE ". @implode("OR", $q1).") as t";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size=FormatBytes($ligne["size"]/1024);
	$messages_recieved=$ligne["hits"];
	
	$f[]="<strong>{your_messaging_statistics} ({$timeAffiche}h)</strong>:&nbsp;";
	
	$f[]="<strong>$messages_recieved</strong> {received_messages} ($size)&nbsp;|&nbsp;";
	
	$sql="SELECT COUNT(zmd5) as hits, SUM(mailsize) as size FROM (SELECT zmd5,mailsize from $currenthour
	 WHERE ". @implode("OR", $q2).") as t";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size=FormatBytes($ligne["size"]/1024);
	$messages_recieved=$ligne["hits"];
	$f[]="<strong>$messages_recieved</strong> {sended_messages} ($size)&nbsp;|&nbsp;";
	
	echo $tpl->_ENGINE_parse_body(@implode("", $f));
	
	
	
}


function messaging_right(){
	$sock=new sockets();
	$users=new usersMenus();

	if(count($t)==0){return;}
	$tpl=new templates();
	$html="<div class=BodyContent>".CompileTr2($t,"none")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}