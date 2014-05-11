<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["conf"])){conf();exit;}
	
popup();	
	

function popup(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$_GET["hostname"];
// 

	$array["backup-status"]="{backupemail_behavior}";
	$array["backup-options"]='{options}';
	$array["backup-storage"]='{storage}';
	

		
		
	$fontsize="font-size:18px;";
	while (list ($num, $ligne) = each ($array) ){
			
		if($num=="backup-options"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.archiver.php?hostname=$hostname\" style='$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="backup-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.archiver.php?hostname=$hostname&status=yes\" style='$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="backup-storage"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.archiver.database.php?hostname=$hostname&status=yes\" style='$fontsize'><span>$ligne</span></a></li>\n");
			continue;			
		}
			
		$html[]="<li><a href=\"$page?$num=yes\" style='$fontsize' ><span>$ligne</span></a></li>\n";
	}


	$html=build_artica_tabs($html,'main_backup_fly',975)."
		<script>LeftDesign('folder-256-backup-white-opac20.png');</script>";

	echo $html;
}