<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.kav4samba.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSambaAdministrator){
		$tpl=new templates();
		echo $tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
		exit;
	}

	if(isset($_GET["SambaEnabled"])){SambaEnabled();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();



function js(){
	
$page=CurrentPageName();
$tpl=new templates();
$prefix=str_replace(".","_",$page);
$title=$tpl->_ENGINE_parse_body("{enable_disable_samba}");

$html="

function {$prefix}StartPage(){
	YahooWin2(550,'$page?popup=yes','$title');
}

	function x_SaveSambaEnabled(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		{$prefix}StartPage();
		if(document.getElementById('main_smb_config')){
			javascript:LoadAjax('main_smb_config','samba.index.php?main=yes');
		}
		
		if(document.getElementById('main_samba_config')){
			javascript:LoadAjax('main_samba_config','fileshares.index.php?main=net_share');
		}		
		CacheOff();
		
		
		YahooWin2Hide();
	}		
	
	function SaveSambaEnabled(){
		var SambaEnabled=document.getElementById('SambaEnabled').value;
		document.getElementById('SambaEnabledDiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		var XHR = new XHRConnection();
		XHR.appendData('SambaEnabled',SambaEnabled);
		XHR.sendAndLoad('$page', 'GET',x_SaveSambaEnabled);				
	}
	

{$prefix}StartPage();

";


echo $html;
	
}


function popup(){
	
	$sock=new sockets();
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
	
	
	$p=Paragraphe_switch_img('{enable_disable_samba}','{enable_disable}','SambaEnabled',$SambaEnabled,null,350);
	
	$html="
	<div id='SambaEnabledDiv'>
	<table style='width:99%' class=form>
	<td valign='top'><img src='img/server-disable-128.png'></td>
	<td valign='top'>
		<div class=explain style='font-size:14px'>{enable_disable_samba_text}</div>
		$p
		<hr>
		<div style='width:100%;text-align:right'>". button("{apply}", "SaveSambaEnabled()","18px")."</div>
			
		</div>
	</td>
	</tr>
	</table></div>";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SambaEnabled(){
	$sock=new sockets();
	$sock->SET_INFO("SambaEnabled",$_GET["SambaEnabled"]);
	$sock->getFrameWork("cmd.php?restart-samba=yes");
	$sock->getFrameWork('cmd.php?refresh-status=yes');
	$sock->getFrameWork("services.php?restart-artica-status=yes");
}


?>