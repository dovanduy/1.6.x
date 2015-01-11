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
	
	
	
	
	if(isset($_POST["ZarafaDeliverBind"])){ZarafaSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{delivery_agent}");
	echo "YahooWin3('590','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafaDeliverBind=$sock->GET_INFO("ZarafaDeliverBind");
	if($ZarafaDeliverBind==null){$ZarafaDeliverBind="127.0.0.1";}
	$net=new networking();
	$nets=$net->ALL_IPS_GET_ARRAY();
	$nets["0.0.0.0"]="{all}";
	$t=time();

	$html="
	<div id='div-$t'></div>
	<div class=text-info style='font-size:16px'>{delivery_agent_parameters_text}</div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=328','1024','900');\" 
		style='font-size:16px;text-decoration:underline'>{online_help}</a></div>
		<table style='width:99%' class=form>
				<tr>
				
				<td class=legend style='font-size:16px'>{listen_ip}:</td>
				<td style='font-size:16px'>". Field_array_Hash($nets, "ZarafaDeliverBind-$t",$ZarafaDeliverBind,null,null,0,"font-size:16px")."&nbsp;:2003</td>
			</tr>		
			
			
			<tr>
				<td align='right' colspan=2><hr>". button("{apply}","Zarafa$t()","18px")."</td>
			</tr>		
		</table>
	<script>
	var x_Zarafa$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
		
	}	
		
	
	function Zarafa$t(){
		var XHR = new XHRConnection();
		var ZarafadAgentJunk=0;
		XHR.appendData('ZarafaDeliverBind',document.getElementById('ZarafaDeliverBind-$t').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_Zarafa$t);
		}
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaDeliverBind", $_POST["ZarafaDeliverBind"]);
	$sock->getFrameWork("zarafa.php?restart-dagent=yes");
}

