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
	echo "YahooWin3('590','$page?popup=yes','$title')";
	
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
	<div class=explain style='font-size:16px'>{zarafa_backup_parameters}</div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=279','1024','900');\" 
		style='font-size:16px;text-decoration:underline'>{online_help}</a></div>
		<table style='width:99%' class=form>
		
			
			<tr>
				<td class=legend style='font-size:16px'>{backup_directory}:</td>
				<td>". Field_text("DEST-$t",$ZarafaBackupParams["DEST"],"font-size:16px;padding:3px;width:190px")."</td>
				<td>". button("{browse}","Loadjs('SambaBrowse.php?no-shares=yes&field=DEST-$t&no-hidden=yes')")."</td>
			</tr>				
			<tr>
				<td class=legend style='font-size:16px'>{DELETE_OLD_BACKUPS}:</td>
				<td>". Field_checkbox("DELETE_OLD_BACKUPS-$t",1,$ZarafaBackupParams["DELETE_OLD_BACKUPS"],"CheckSaveZarafaBackupParams()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>{MaxDays}:</td>
				<td>". Field_text("DELETE_BACKUPS_OLDER_THAN_DAYS-$t",$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"],"font-size:16px;padding:3px;width:90px")."</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}","SaveZarafaBackupParams$t()","16px")."</td>
			</tr>		
		</table>
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
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ZarafaBackupParams");

	
}

