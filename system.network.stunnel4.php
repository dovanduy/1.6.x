<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.stunnel4.inc');
	include_once('ressources/class.main_cf.inc');
	
	if(!checkprivs_stunnel()){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["stunnel-status"])){stunnel4_status();exit;}

tabs();	





function tabs(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	
	if(!$users->stunnel4_installed){
		echo FATAL_ERROR_SHOW_128("{stunnel_not_installed}");
		die();
	
	}
	
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$height="850px";

	$array["status"]='{status}';
	$array["rules"]='{rules}';
	
	$style="style='font-size:20px'";
	
	if(isset($_GET["font-size"])){
		$style="style='font-size:22px'";
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li $style>
					<a href=\system.network.stunnel4.rules.php\">
					<span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		if($num=="authentication"){
			$html[]= "<li $style><a href=\"postfix.index.php?popup-auth=yes&hostname=$hostname\"><span>$ligne</span></a></li>\n";
			continue;
		}
	
		$html[]= "<li $style><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "main_tabs_stunnel4",1200)."<script>LeftDesign('ssl-256-white.png');</script>";
		
	
	
}
// smtps_relayhost_text

function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sTunnel4enabled=$sock->GET_INFO('sTunnel4enabled');
	$enable=Paragraphe_switch_img('{enable_stunnel4}',"{enable_stunnel_gtext}",'sTunnel4enabled',$sTunnel4enabled,null,880);
	
	
$html="
<div style='width:98%' class=form>
	<table style='width:100%' align=center>
	<tr>
		<td valign='top' style='width:260px'><div id='stunnel4-status'></div></td>
		<td style='width:99%'>$enable
		<hr><div style='margin-top:20px;text-align:right'>". button('{apply}',"Save$t()",32)."</div>
		</td>
	</tr>
	</table>
</div>
<script>

	function xSave$t(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		LoadAjax('stunnel4-status','$page?stunnel-status=yes&hostname={$_GET["hostname"]}');
		
	}

	function Save$t(){
		XHR.appendData('sTunnel4enabled',document.getElementById('sTunnel4enabled').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}

	LoadAjax('stunnel4-status','$page?stunnel-status=yes&hostname={$_GET["hostname"]}');
</script>";	
	
echo $tpl->_ENGINE_parse_body($html);
	
}


function stunnel4_status(){
	
	$users=new usersMenus();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("cmd.php?stunnel-ini-status=yes"));
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("STUNNEL",$ini);
	echo $tpl->_ENGINE_parse_body($status);
	
}

	
function checkprivs_stunnel(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	if($users->AsWebMaster){return true;}
	return false;
}


	
