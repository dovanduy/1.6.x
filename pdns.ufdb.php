<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if(!Checkrights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["PDSNInUfdbWebsite"])){save();exit;}
	
		js();
	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{dns_filter}");
	$html="YahooWin4(600,'$page?popup=yes','$title')";
	echo $html;
}

function Checkrights(){
	$users=new usersMenus();
	if($users->AsDansGuardianAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$sock=new sockets();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}

	
	$help="<hr>
	<div style='text-align:right'>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=365','1024','900');\"
	style=\"font-size:16px;font-weight:bold;text-decoration:underline\">{online_help}</a>
	</div>";
	
	if($EnablePDNS==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{PDNS_IS_NOT_ENABLED}$help"));
		return;
	
	}	
	
	if($EnableUfdbGuard==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{UFDBGUARD_NOT_ENABLED_EXPLAIN}$help"));
		return;
		
	}
	
	if($SquidActHasReverse==1){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{SQUID_IS_IN_REVERSE_MODE}$help"));
		return;
	
	}	
	
	$t=time();

	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$PDSNInUfdb=$sock->GET_INFO("PDSNInUfdb");
	if(!is_numeric($PDSNInUfdb)){$PDSNInUfdb=0;}
	$PDSNInUfdbWebsite=$sock->GET_INFO("PDSNInUfdbWebsite");
	if($PDSNInUfdbWebsite==null){$PDSNInUfdbWebsite="google.com";}
	$form=Paragraphe_switch_img("{activate_pdnsinufdb}", "{activate_pdnsinufdb_explain}",
			"PDSNInUfdb",$PDSNInUfdb,null,550);

	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>$form</td>
	</tR>
	<tr>
	<td class=legend style='font-size:16px'>{redirect_queries_to}:</td>
	<td>". Field_text("PDSNInUfdbWebsite",$PDSNInUfdbWebsite,"font-size:16px;width:250px")."</td>
		</tr>


	<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "Save$t()","18px")."</td>
	</tr>
	</table>
	$help
			<script>
			var x_Save$t=function (obj) {
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);}
				document.getElementById('$t').innerHTML='';
				if(document.getElementById('squid-status')){
					LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');
				}
				YahooWin4Hide();
			}

			function Save$t(){
				var lock=$EnableRemoteStatisticsAppliance;
				if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
				var XHR = new XHRConnection();
				XHR.appendData('PDSNInUfdbWebsite',document.getElementById('PDSNInUfdbWebsite').value);
				XHR.appendData('PDSNInUfdb',document.getElementById('PDSNInUfdb').value);
				AnimateDiv('$t');
				XHR.sendAndLoad('$page', 'POST',x_Save$t);
			}

			</script>
			";

			echo $tpl->_ENGINE_parse_body($html);

}


function save(){
	$sock=new sockets();
	if(preg_match("#:\/\/(.+?)$#", $_POST["PDSNInUfdbWebsite"],$re)){
		$_POST["PDSNInUfdbWebsite"]=$re[1];
		
	}
	if(preg_match("#(.+?)\/#", $_POST["PDSNInUfdbWebsite"],$re)){
		$_POST["PDSNInUfdbWebsite"]=$re[1];
	}
	$sock->SET_INFO("PDSNInUfdbWebsite", $_POST["PDSNInUfdbWebsite"]);
	$sock->SET_INFO("PDSNInUfdb", $_POST["PDSNInUfdb"]);
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
	
	
}
