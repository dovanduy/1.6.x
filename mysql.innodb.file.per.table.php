<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsAnAdministratorGeneric){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}
	
	if(isset($_POST["InnoDBFilePerTableAsk"])){InnoDBFilePerTableAsk();exit;}
	if(isset($_POST["ConvertInnoDB"])){ConvertInnoDB();exit;}
	
page();	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql();
	$databases=$q->DATABASE_LIST();
	
	while (list ($database, $line) = each ($databases) ){
		$tbs=$tbs+$line[0];
		$tx=trim($line[1]);
		
		$re=explode("&nbsp;", $tx);
		$tsize=$re[0];
		$unit=$re[1];
		if($unit=="KB"){$size=$size+$tsize;}
		if($unit=="MB"){$tsize=intval($tsize)*1024;$size=$size+$tsize;}
		if($unit=="GB"){$tsize=intval($tsize)*1024;$tsize=$tsize*1024;$size=$size+$tsize;}
			
		
		
		
	}
	
	$text=$tpl->_ENGINE_parse_body("{INNODB_FILE_PER_TABLE_ASK}");
	$size=FormatBytes($size);
	$text=str_replace("%free", "<strong>$size</strong>", $text);
	$text=str_replace("%tablesnum", "<strong>$tbs</strong>", $text);

	$t=time();
	
	$html="
	
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/database-connect-128.png'></td>
		<td valign='top' width=99% style='padding-left:20px'>
		<div style='font-size:18px;font-weight:bold'>InnoDB File per Table</div>
		<div id='$t-div'></div>
		<div style='font-size:14px'>$text</div>
		<div style='margin:10px;text-align:center'>". button("{i_understand_continue}","SaveContinue$t()","18px")."</div>
		<div style='margin:10px;text-align:center'>". button("{convertto_innodb_file_per_table}","ConvertInnodb$t()","18px")."</div>
		<div style='margin:10px;text-align:center'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=278','1024','900');\"
		style='font-size:18px;'>{online_help}</a>
		
		
		</td>
	</tr>
	</table>
	
	<script>
		var x_SaveContinue$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.location.href='admin.index.php';
			
		}
	
	
		function SaveContinue$t(){
			var XHR = new XHRConnection();
			XHR.appendData('InnoDBFilePerTableAsk','yes');
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveContinue$t);	
		}
		
		function ConvertInnodb$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ConvertInnoDB','yes');
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveContinue$t);			
		
		}
	
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	
function InnoDBFilePerTableAsk(){
	$sock=new sockets();
	$sock->SET_INFO("InnoDBFilePerTableAsk", 1);
}

function ConvertInnoDB(){
	$sock=new sockets();
	$sock->SET_INFO("InnoDBFilePerTableAsk", 1);
	$sock->getFrameWork("mysql.php?convert-innodb-file-persize=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{convert_background_warn}",1);
	
}