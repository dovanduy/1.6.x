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

	if(isset($_POST["ZarafaDBEnable2Instance"])){Save();exit;}
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();

	$ZarafaDBEnable2Instance=$sock->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($ZarafaDBEnable2Instance)){$ZarafaDBEnable2Instance=0;}
	$t=time();
	
	
	
	$p=Paragraphe_switch_img("{zarafa_second_activate}", "{zarafa_second_params_text}",
			"ZarafaDBEnable2Instance-$t",$ZarafaDBEnable2Instance,null,350);
	
	
	$html="
	<div id='$t'></div>
	
	
	<table style='width:99%' class=form>
	<tR>
		<td colspan=3>$p</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>&nbsp;</td>
		<td style='font-size:16px'>&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","ZarafaSave$t()",18)."</td>
	</tr>	
</table>
<script>
	var x_ZarafaSave$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue);}
		
		}	
	
	function ZarafaSave$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaDBEnable2Instance',document.getElementById('ZarafaDBEnable2Instance-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSave$t);
	
	}
	

</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSecondZarafaInstance", $_POST["EnableSecondZarafaInstance"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
}
	
	
	
	