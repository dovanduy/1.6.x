<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["DisableGoogleSSL"])){Save();exit;}
	if(isset($_GET["google-dns"])){google_dns();exit;}
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{disable_google_ssl}");
	$page=CurrentPageName();
	$html="YahooWin3('550','$page?popup=yes','$title')";
	echo $html;	
	
}
function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$DisableGoogleSSL=$sock->GET_INFO("DisableGoogleSSL");
	if(!is_numeric($DisableGoogleSSL)){$DisableGoogleSSL=0;}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	$display_dns_items=$tpl->javascript_parse_text("{display_dns_items}");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$t=time();
	
	$button=button("{apply}","DisableGoogleSSLSave$t()",18);
	if($EnableRemoteStatisticsAppliance==1){$button=null;}
	$enable=Paragraphe_switch_img("{enforce_google_to_non_ssl}",
	 "{enforce_google_to_non_ssl_text}","DisableGoogleSSL-$t",$DisableGoogleSSL,null,400);
	
	$html="
	<div id='$t-div'></div>
	<table style='width:99%' class=form>
	<tr>
		
		<td width=99%>$enable</td>
	</tr>
	<tr>
		
		<td width=99% align='right'><a href=\"javascript:blur();\" OnClick=\"javascript:YahooWin4('500','$page?google-dns=yes','$display_dns_items')\"
		style='font-size:16px;text-decoration:underline'>$display_dns_items</a>
		</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>$button</td>
	</tr>
	</table>

	
	<script>
		
	var x_DisableGoogleSSLSave$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	Loadjs('squid.compile.progress.php');
      	YahooWin3Hide();
     	}	

	function DisableGoogleSSLSave$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			XHR.appendData('DisableGoogleSSL',document.getElementById('DisableGoogleSSL-$t').value);
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_DisableGoogleSSLSave$t);		
		}
	
	}		
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableGoogleSSL",$_POST["DisableGoogleSSL"]);
}

function google_dns(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?GoogleSSL-dump=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:16px' id='textToParseCats$t'>".@implode("\n", $datas)."</textarea>";
}