<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	
	
	if(isset($_POST["DEST"])){ZarafaSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{backup_parameters}");
	echo "YahooWin3('800','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafaBackupParams=unserialize(base64_decode($sock->GET_INFO("ZarafaBackupParams")));
	
	if($ZarafaBackupParams["DEST"]==null){$ZarafaBackupParams["DEST"]="/home/zarafa-backup";}
	if(!is_numeric($ZarafaBackupParams["DELETE_OLD_BACKUPS"])){$ZarafaBackupParams["DELETE_OLD_BACKUPS"]=1;}
	if(!is_numeric($ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"])){
	$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]=10;}
	
	
	
	$t=time();

	$html="
	<div id='div-$t'></div>
	<div class=text-info style='font-size:16px'>{zarafa_backup_parameters}</div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=279','1024','900');\" 
		style='font-size:16px;text-decoration:underline'>{online_help}</a></div>
		<div style='width:98%' class=form>
		<table style='width:99%'>
		
			
			<tr>
				<td class=legend style='font-size:18px'>{backup_directory}:</td>
				<td>". Field_text("DEST-$t",$ZarafaBackupParams["DEST"],"font-size:18px;padding:3px;width:253px")."</td>
				<td>". button("{browse}","Loadjs('SambaBrowse.php?no-shares=yes&field=DEST-$t&no-hidden=yes')")."</td>
			</tr>				
			<tr>
				<td class=legend style='font-size:18px'>{DELETE_OLD_BACKUPS}:</td>
				<td>". Field_checkbox("DELETE_OLD_BACKUPS-$t",1,$ZarafaBackupParams["DELETE_OLD_BACKUPS"],"CheckSaveZarafaBackupParams()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px;vertical-align:middle'>{retention}:</td>
				<td style='font-size:18px'>". Field_text("DELETE_BACKUPS_OLDER_THAN_DAYS-$t",
						$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"],"font-size:18px;padding:3px;width:90px")."&nbsp;{days}</td>
				<td>&nbsp;</td>
			</tr>
			<tr><td colspan=3><hr><span style='font-size:26px;vertical-align:middle'>FTP: {backup}</td></tr>
			<tr><td colspan=3>". Paragraphe_switch_img("{enable_FTP_backup}", 
					"{FTP_backup_zarafa_explain}","FTP_ENABLE-$t",intval($ZarafaBackupParams["FTP_ENABLE"]))."</td>					
			</tr>
			<tr>
				<td class=legend style='font-size:18px;vertical-align:middle'>{ftp_server}:</td>
				<td style='font-size:18px'>". Field_text("FTP_SERVER-$t",
						$ZarafaBackupParams["FTP_SERVER"],"font-size:18px;padding:3px;width:210px")."&nbsp;</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:18px;vertical-align:middle'>{ftp_username}:</td>
				<td style='font-size:18px'>". Field_text("FTP_USER-$t",
						$ZarafaBackupParams["FTP_USER"],"font-size:18px;padding:3px;width:190px")."&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{ftp_password}:</td>
				<td style='font-size:18px;vertical-align:middle'>". Field_password("FTP_PASS-$t",
						$ZarafaBackupParams["FTP_PASS"],"font-size:18px;padding:3px;width:190px")."&nbsp;</td>
				<td>&nbsp;</td>
			</tr>												
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}","SaveZarafaBackupParams$t()","26px")."</td>
			</tr>		
		</table>
	</div>
	<script>
	var x_SaveZarafaBackupParams$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
		
	}	
		
	
	function SaveZarafaBackupParams$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('DELETE_OLD_BACKUPS-$t').checked){XHR.appendData('DELETE_OLD_BACKUPS',1);}else{XHR.appendData('DELETE_OLD_BACKUPS',0);}	
		XHR.appendData('DEST',document.getElementById('DEST-$t').value);
		XHR.appendData('DELETE_BACKUPS_OLDER_THAN_DAYS',document.getElementById('DELETE_BACKUPS_OLDER_THAN_DAYS-$t').value);
		
		XHR.appendData('FTP_ENABLE',document.getElementById('FTP_ENABLE-$t').value);
		XHR.appendData('FTP_USER',document.getElementById('FTP_USER-$t').value);
		XHR.appendData('FTP_PASS',encodeURIComponent(document.getElementById('FTP_PASS-$t').value));
		XHR.appendData('FTP_SERVER',document.getElementById('FTP_SERVER-$t').value);
		
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveZarafaBackupParams$t);
		}
		
	function CheckSaveZarafaBackupParams(){
		document.getElementById('DELETE_BACKUPS_OLDER_THAN_DAYS-$t').disabled=true;
		
		if(document.getElementById('DELETE_OLD_BACKUPS-$t').checked){
			document.getElementById('DELETE_BACKUPS_OLDER_THAN_DAYS-$t').disabled=false;
					
		}
	}
	
	CheckSaveZarafaBackupParams();
		
		
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSave(){
	$sock=new sockets();
	$_POST["FTP_PASS"]=url_decode_special_tool($_POST["FTP_PASS"]);
	
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ZarafaBackupParams");

	
}

