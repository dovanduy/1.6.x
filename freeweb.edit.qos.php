<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){page();exit;}
	if(isset($_GET["EnableQOS"])){SaveWebQOS();exit;}

tabs();	
	
function tabs(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=18;
	
	$array["bandw"]="{limit_rate}";
	$array["qos"]="{QOS}";
	
	$servername=urlencode($_GET["servername"]);	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="qos"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?popup=$t&servername=$servername\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="bandw"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.edit.bw.php?popup=$t&servername=$servername\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
	
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_freeweb_qos");	
	
}
	
function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$free=new freeweb($_GET["servername"]);
	$Params=$free->Params;
	
	$EnableQOS=$Params["QOS"]["EnableQOS"];
	$QS_ClientEntries=$Params["QOS"]["QS_ClientEntries"];
	$QS_SrvMaxConnPerIP=$Params["QOS"]["QS_SrvMaxConnPerIP"];
	$MaxClients=$Params["QOS"]["MaxClients"];
	$QS_SrvMaxConnClose=$Params["QOS"]["QS_SrvMaxConnClose"];
	$QS_SrvMinDataRate=$Params["QOS"]["QS_SrvMinDataRate"];
	$LimitRequestFields=$Params["QOS"]["LimitRequestFields"];
	$QS_LimitRequestBody=$Params["QOS"]["QS_LimitRequestBody"];
	if(!is_numeric($QS_ClientEntries)){$QS_ClientEntries=100000;}
	if(!is_numeric($QS_SrvMaxConnPerIP)){$QS_SrvMaxConnPerIP=50;}
	if(!is_numeric($MaxClients)){$MaxClients=256;}
	if(!is_numeric($QS_SrvMaxConnClose)){$QS_SrvMaxConnClose=180;}
	if($QS_SrvMinDataRate==null){$QS_SrvMinDataRate="150 1200";}
	if(!is_numeric($LimitRequestFields)){$LimitRequestFields=30;}
	if(!is_numeric($QS_LimitRequestBody)){$QS_LimitRequestBody=102400;}
	if(!is_numeric($EnableQOS)){$EnableQOS=0;}
	
	$html="
	<div id='webdav-qos'>
	<div style='width:98%' class=form>
	". Paragraphe_switch_img("{enable_qos_service}", "{mod_qos_explain}","EnableQOS","$EnableQOS",null,650)."
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{QS_ClientEntries}:</td>
		<td>". Field_text("QS_ClientEntries",$QS_ClientEntries,"font-size:18px;width:120px")."</td>
		<td>". help_icon("{QS_ClientEntries_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{QS_SrvMaxConnPerIP}:</td>
		<td>". Field_text("QS_SrvMaxConnPerIP",$QS_SrvMaxConnPerIP,"font-size:18px;width:90px")."</td>
		<td>". help_icon("{QS_SrvMaxConnPerIP_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{QOSMaxClients}:</td>
		<td>". Field_text("MaxClients",$MaxClients,"font-size:18px;width:90px")."</td>
		<td>". help_icon("{QOSMaxClients_explain}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{QS_SrvMaxConnClose}:</td>
		<td>". Field_text("QS_SrvMaxConnClose",$QS_SrvMaxConnClose,"font-size:18px;width:90px")."</td>
		<td>". help_icon("{QS_SrvMaxConnClose_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{QS_SrvMinDataRate}:</td>
		<td>". Field_text("QS_SrvMinDataRate",$QS_SrvMinDataRate,"font-size:18px;width:120px")."</td>
		<td>". help_icon("{QS_SrvMinDataRate_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{LimitRequestFields}:</td>
		<td>". Field_text("LimitRequestFields",$LimitRequestFields,"font-size:18px;width:60px")."</td>
		<td></td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{QS_LimitRequestBody}:</td>
		<td>". Field_text("QS_LimitRequestBody",$QS_LimitRequestBody,"font-size:18px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","SaveWebQOS()",24)."</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	</div>
	</div>
	<script>
	
		var x_SaveWebQOS=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			RefreshTab('main_freeweb_qos');	
		}		
	
		function SaveWebQOS(){
			XHR.appendData('EnableQOS',document.getElementById('EnableQOS').value);
			XHR.appendData('QS_ClientEntries',document.getElementById('QS_ClientEntries').value);
			XHR.appendData('QS_SrvMaxConnPerIP',document.getElementById('QS_SrvMaxConnPerIP').value);
			XHR.appendData('MaxClients',document.getElementById('MaxClients').value);
			XHR.appendData('QS_SrvMaxConnClose',document.getElementById('QS_SrvMaxConnClose').value);
			XHR.appendData('QS_SrvMinDataRate',document.getElementById('QS_SrvMinDataRate').value);
			XHR.appendData('LimitRequestFields',document.getElementById('LimitRequestFields').value);
			XHR.appendData('QS_LimitRequestBody',document.getElementById('QS_LimitRequestBody').value);
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'GET',x_SaveWebQOS);
		}
		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SaveWebQOS(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
		
	$free=new freeweb($_GET["servername"]);
	while (list ($num, $ligne) = each ($_GET) ){
		$free->Params["QOS"][$num]=$ligne;
		
	}
	
	$free->SaveParams();
}



	
	