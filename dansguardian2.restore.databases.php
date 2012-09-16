<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_POST["RestoreBackupPerform"])){RestoreBackupPerform();exit;}
	if(isset($_GET["empty-js"])){empty_js();exit;}
	if(isset($_POST["EmptyPersoCatz"])){EmptyPersoCatz();exit;}
	js();
	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{restore_backup}");
	$html="YahooWin3(685,'$page?tabs=yes','$title')";
	echo $html;
}

function empty_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$empty_catz_explain=$tpl->javascript_parse_text("{empty_catz_explain}");
	$t=time();
	$html="
	
	var xEmpty$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			if(document.getElementById('emptypersonaldbdiv')){document.getElementById('emptypersonaldbdiv').src='img/arrow-right-16.png';}
			RefreshTab('main_databasesCAT_quicklinks_tabs');
		}	
	
	
		function Empty$t(){
			if(document.getElementById('emptypersonaldbdiv')){document.getElementById('emptypersonaldbdiv').src='/img/loading.gif';}
			if(confirm('$empty_catz_explain')){
				var XHR = new XHRConnection();
				XHR.appendData('EmptyPersoCatz','yes');
				XHR.sendAndLoad('$page', 'POST',xEmpty$t);					
			
			}else{
				if(document.getElementById('emptypersonaldbdiv')){document.getElementById('emptypersonaldbdiv').src='img/arrow-right-16.png';}
			}
		
		}
	
	Empty$t();";
	echo $html;
}



function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$squid=new squidbee();	
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}			
	if($EnableWebProxyStatsAppliance==1){$users->DANSGUARDIAN_INSTALLED=true;$squid->enable_dansguardian=1;}	
	
	
	
	
	
	$array["popup"]='{restore}';
	$array["events"]='{events}';
	
	

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.update.events.php?popup=yes&category=restore&taskid=0\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&maximize=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_cat_restore_backup style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_cat_restore_backup').tabs();
			});
		</script>";	

}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$restore=$tpl->_ENGINE_parse_body("{restore}");
	$html="
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{backup_container_path}:</td>
		<td>". Field_text("container-path-$t",null,"font-size:14px;width:300px;")."</td>
		<td width=1%>". button("{browse}...", "Loadjs('tree.php?select-file=gz&target-form=container-path-$t');")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{restore}", "RestoreBackupPerform()",16)."</td>
	</tr>
	</table>
	
	<script>
	var xRestoreBackupPerform= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('div-$t').innerHTML='';
			if(tempvalue.length>3){alert(tempvalue)};
		}	

	function RestoreBackupPerform(){
		var filename=document.getElementById('container-path-$t').value;
		if(confirm('$restore '+filename+' ?')){
			var XHR = new XHRConnection();
			AnimateDiv('div-$t');
			XHR.appendData('RestoreBackupPerform',filename);
			XHR.sendAndLoad('$page', 'POST',xRestoreBackupPerform);		
		
		}
	}
	</script>		
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function RestoreBackupPerform(){
	$filepath=base64_encode($_POST["RestoreBackupPerform"]);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?restore-backup-catz=$filepath");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_restore_launched_explain}",1);
}
function EmptyPersoCatz(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?empty-perso-catz=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_remove_launched_explain}",1);	
}

?>

