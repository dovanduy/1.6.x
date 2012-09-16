<?php
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
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["PostfixMultiCreateBubble"])){PostfixMultiCreateBubbleSave();exit;}
	if(isset($_POST["PostfixMultiTrustAllInstances"])){PostfixMultiTrustAllInstancesSave();exit;}
	if(isset($_GET["multiple-adv"])){PostfixMultipleAdv();exit;}
	if(isset($_POST["MultipleAdvUseMemory"])){PostfixMultipleAdvSave();exit;}
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{advanced_options}");
	$html="YahooWin5('525','$page?popup=yes','$title')";
	echo $html;
}	

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["params"]='{parameters}';
	$array["whitelisted"]='{white list}';
	$array["multiple-adv"]='{advanced_ISP_routing}';
	
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="whitelisted"){
			$html[]= "<li style='font-size:14px'><a href=\"whitelists.admin.php?popup-hosts=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}
		$html[]= "<li style='font-size:14px'><a href=\"$page?$num=yes&hostname=$hostname\"><span>$ligne</span></a></li>\n";
	}
	
	
	$html= "
	<div id=main_multiple_postfixadv style='width:100%;height:550px;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_multiple_postfixadv\").tabs();});
			
			
			
		</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function PostfixMultipleAdv(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$MEM_TOTAL_INSTALLEE=round($users->MEM_TOTAL_INSTALLEE/1024);
	$MEM_DEFAULT=($MEM_TOTAL_INSTALLEE*45)/100;
	$MEM_TOTAL_INSTALLEE_TEXT=FormatBytes($users->MEM_TOTAL_INSTALLEE);
	$MultipleAdvMemorySize=$sock->GET_INFO("MultipleAdvMemorySize");
	$MultipleAdvUseMemory=$sock->GET_INFO("MultipleAdvUseMemory");
	$MultipleAdvLoad=$sock->GET_INFO("MultipleAdvLoad");
	if(!is_numeric($MultipleAdvUseMemory)){$MultipleAdvUseMemory=0;}
	if(!is_numeric($MultipleAdvLoad)){$MultipleAdvLoad=10;}
	if(!is_numeric($MultipleAdvMemorySize)){$MultipleAdvMemorySize=round($MEM_DEFAULT);}
	$MultipleAdvMemorySizeText=FormatBytes($MultipleAdvMemorySize*1024);
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' style='font-size:14px' class=legend>{server_memory}:</td>
		<td style='font-size:14px'>{$MEM_TOTAL_INSTALLEE}MB ($MEM_TOTAL_INSTALLEE_TEXT)</td>
		<td></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:14px' class=legend>{use_memory}:</td>
		<td>". Field_checkbox("MultipleAdvUseMemory", 1,$MultipleAdvUseMemory,"MultipleAdvUseMemoryCheck()")."</td>
		<td>". help_icon("{MultipleAdvUseMemoryExplain}")."</td>
	</tr>
		<td valign='top' style='font-size:14px' class=legend>{memory_size}:</td>
		<td style='font-size:14px'>". Field_text("MultipleAdvMemorySize",$MultipleAdvMemorySize,"font-size:14px;width:95px")."&nbsp;MB ($MultipleAdvMemorySizeText)</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px' class=legend>{max_load_to_run}:</td>
		<td style='font-size:14px'>". Field_text("MultipleAdvLoad",$MultipleAdvLoad,"font-size:14px;width:65px")."&nbsp;{load}</td>
		<td>". help_icon("{MultipleAdvLoadExplain}")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","MultipleAdvSave()")."</td>
	</tr>
	</table>
</div>
	<script>

	var x_MultipleAdvSave= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');}
		RefreshTab('main_multiple_postfixadv');
	}	
	
	function MultipleAdvSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('MultipleAdvUseMemory').checked){XHR.appendData('MultipleAdvUseMemory',1);}else{XHR.appendData('MultipleAdvUseMemory',0);}
		XHR.appendData('MultipleAdvMemorySize',document.getElementById('MultipleAdvMemorySize').value);
		XHR.appendData('MultipleAdvLoad',document.getElementById('MultipleAdvLoad').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_MultipleAdvSave);	
	}

	function MultipleAdvUseMemoryCheck(){
		document.getElementById('MultipleAdvMemorySize').disabled=true;
		if(document.getElementById('MultipleAdvUseMemory').checked){document.getElementById('MultipleAdvMemorySize').disabled=false;}
	}
	MultipleAdvUseMemoryCheck();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function PostfixMultipleAdvSave(){
	$sock=new sockets();
	$sock->SET_INFO("MultipleAdvUseMemory", $_POST["MultipleAdvUseMemory"]);
	$sock->SET_INFO("MultipleAdvMemorySize", $_POST["MultipleAdvMemorySize"]);
	$sock->SET_INFO("MultipleAdvLoad", $_POST["MultipleAdvLoad"]);
	$sock->getFrameWork("postfix.php?isp-adv-remount=yes");
	
}

function params(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
	$PostfixMultiTrustAllInstances=$sock->GET_INFO("PostfixMultiTrustAllInstances");
	$p=Paragraphe_switch_img("{PostfixMultiCreateBubble}", "{PostfixMultiCreateBubble_text}","PostfixMultiCreateBubble",$PostfixMultiCreateBubble,null,450);
	$p2=Paragraphe_switch_img("{PostfixMultiTrustAllInstances}","{PostfixMultiTrustAllInstances_text}","PostfixMultiTrustAllInstances",$PostfixMultiTrustAllInstances,null,450);
	
	
	
	$t=time();
	$html="
	<div id=$t>
	$p
	<div style='width:100%;text-align:right'><table style='width:30%'><tbody><tr><td width=1%><img src='img/pdf-24.png'></td><td width=99% nowrap><a href='http://www.artica.fr/download/artica-multiple-bubble.pdf' style='font-size:12px;font-weight:bold;text-decoration:underline'>&laquo;{help}&raquo;</a></td></tr></tbody></table></div>
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveBubbleSettings()")."</div>
	</div>
	<hr>
	<div id=t-$t>
	$p2
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveTrustAllInstances()")."</div>
		
	
	
	<script>
	var x_SaveBubbleSettings= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');return;}
		RefreshTab('main_multiple_postfixadv');
	}	
	
	function SaveBubbleSettings(){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixMultiCreateBubble',document.getElementById('PostfixMultiCreateBubble').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveBubbleSettings);	
	}	
	function SaveTrustAllInstances(){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixMultiTrustAllInstances',document.getElementById('PostfixMultiTrustAllInstances').value);
		AnimateDiv('t-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveBubbleSettings);	
	}	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function PostfixMultiTrustAllInstancesSave(){
	$sock=new sockets();
	$sock->SET_INFO("PostfixMultiTrustAllInstances", $_POST["PostfixMultiTrustAllInstances"]);
	$sock->getFrameWork("postfix.php?multibubble=yes");
}

function PostfixMultiCreateBubbleSave(){
	$sock=new sockets();
	$sock->SET_INFO("PostfixMultiCreateBubble", $_POST["PostfixMultiCreateBubble"]);
	$sock->getFrameWork("postfix.php?multibubble=yes");
}