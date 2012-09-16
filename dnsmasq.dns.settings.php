<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');

	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDnsAdministrator==false){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
			die();exit();
		}
	}	
	
	
if(isset($_POST["SaveConf1"])){SaveConf1();exit;}
if(isset($_POST["restart-dnsmasq"])){restart_service();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["ffm1"])){main_form();exit;}
if(isset($_GET["InterfacesReload"])){echo LoadInterfaces();exit;}
if(isset($_GET["addressesReload"])){echo Loadaddresses();exit;}
if(isset($_GET["ListentAddressesReload"])){echo LoadListenAddress();exit;}
if(isset($_GET["DnsmasqDeleteInterface"])){DnsmasqDeleteInterface();exit;}

if(isset($_GET["listen_addresses"])){SaveListenAddress();exit;}
if(isset($_GET["DnsmasqDeleteListenAddress"])){DnsmasqDeleteListenAddress();exit;}
if(isset($_GET["EnableDNSMASQ"])){EnableDNSMASQSave();exit;}
if(isset($_GET["get-status"])){status();exit;}
if(isset($_GET["sub-status"])){page_status();exit;}
if(isset($_GET["sub-settings"])){page_settings();exit;}
if(isset($_GET["sub-listen"])){page_listen();exit;}
if(isset($_GET["sub-events"])){page_events();exit;}


if(isset($_GET["sub-localdomains"])){page_localdomains();exit;}
if(isset($_GET["localdomain-search"])){page_localdomains_search();exit;}
if(isset($_POST["localdomain-enable"])){page_localdomains_enable();exit;}
if(isset($_POST["localdomain-del"])){page_localdomains_del();exit;}
if(isset($_POST["localdomain-add"])){page_localdomains_add();exit;}



if(isset($_GET["sub-wpad"])){wpad();exit;}
if(isset($_POST["wpad"])){wpad_save();exit;}




page();

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('services.php?dnsmasq-status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("DNSMASQ",$ini,null);
	$status=$tpl->_ENGINE_parse_body($status);
	$status="
	<div id='dnsmasq-status-$t'>
		$status
		<div style='width:100%;text-align:right'>". 
			imgtootltip("refresh-24.png","{refresh}","LoadAjax('dnsmasq-status-$t','$page?get-status=yes')")."
		</div>
	</div>
	
	
	<script>RefreshMainForm()</script>
	
	";
	echo $tpl->_ENGINE_parse_body($status);

}

function restart_service(){
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-dnsmasq=yes");
}


function main_form(){
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$sys->array_interfaces[null]='{select}';
	$sys->array_tcp_addr[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,'interfaces',null,"style:font-size:14px;padding:3px;");
	$tcpaddr=Field_array_Hash($sys->array_tcp_addr,'listen_addresses',null,"style:font-size:14px;padding:3px;");
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}

	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=0;}
	

	
	
	
$f[]="domain-needed";
$f[]="expand-hosts";
$f[]="bogus-priv";
$f[]="filterwin2k";
$f[]="strict-order";
$f[]="no-resolv";
$f[]="no-negcache";
$f[]="no-poll";
$f[]="log-queries";


while (list ($index, $key) = each ($f) ){
	if($cf->main_array[$key]=="yes"){$cf->main_array[$key]=1;}else{$cf->main_array[$key]=0;}
	$js[]="if(document.getElementById('$key').checked){XHR.appendData('$key','yes');	}else{XHR.appendData('$key','no');}";
}	
$html="<table style='width:99%' class=form><tbody>


<tr>
	<td align='right' valign='top' class=legend style='font-size:14px'>{domain-needed}:</td>
	<td align='left' valign='top'>" . Field_checkbox('domain-needed',1,$cf->main_array["domain-needed"])."</td>
	<td align='left' valign='top'  width=1%>". help_icon("{domain-needed_text}")."</td>
</tr>
<tr>
<td align='right' valign='top' class=legend style='font-size:14px'>{expand-hosts}:</td>
<td align='left' valign='top'   >" . Field_checkbox('expand-hosts',1,$cf->main_array["expand-hosts"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{expand-hosts_text}")."</td>
</tr>


<tr>
<td align='right' valign='top' class=legend style='font-size:14px'>{bogus-priv}:</td>
<td align='left' valign='top' >" . Field_checkbox('bogus-priv',1,$cf->main_array["bogus-priv"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{bogus-priv_text}")."</td>
</tr>
<tr>
<td align='right' valign='top'  valign='top'  class=legend style='font-size:14px'>{filterwin2k}:</td>
<td align='left' valign='top' >" . Field_checkbox('filterwin2k',1,$cf->main_array["filterwin2k"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{filterwin2k_text}")."</td>
</tr>
<tr>
<td align='right' valign='top'  valign='top'  class=legend style='font-size:14px'>{strict-order}:</td>
<td align='left' valign='top' >" . Field_checkbox('strict-order',1,$cf->main_array["strict-order"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{strict-order_text}")."</td>
</tr>

<tr>
<td align='right' valign='top'  valign='top' class=legend style='font-size:14px'>{no-resolv}:</td>
<td align='left' valign='top' >" . Field_checkbox('no-resolv',1,$cf->main_array["no-resolv"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{no-resolv_text}")."</td>
</tr>

<tr>
<td align='right' valign='top'  valign='top'  class=legend style='font-size:14px'>{no-negcache}:</td>
<td align='left' valign='top' >" . Field_checkbox('no-negcache',1,$cf->main_array["no-negcache"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{no-negcache_text}")."</td>
</tr>



<tr>
<td align='right' valign='top'  valign='top'  class=legend style='font-size:14px'>{no-poll}:</td>
<td align='left' valign='top' >" . Field_checkbox('no-poll',1,$cf->main_array["no-poll"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{no-poll_text}")."</td>
</tr>

<tr>
<td align='right' valign='top'  valign='top'  class=legend style='font-size:14px'>{log-queries}:</td>
<td align='left' valign='top' >" . Field_checkbox('log-queries',1,$cf->main_array["log-queries"])."</td>
<td align='left' valign='top'  width=1%>". help_icon("{log-queries_text}")."</td>
</tr>


</tbody>
</table>

<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td align='right' valign='top'  valign='top'   nowrap class=legend style='font-size:14px'>{resolv-file}:</td>
			<td align='left' valign='top' >" . Field_text('resolv-file',$cf->main_array["resolv-file"],"font-size:14px;padding:3px;")."</td>
			<td align='left' valign='top'  >". help_icon("{resolv-file_text}")."</td>
		</tr>
		<tr>
			<td align='right' valign='top'  valign='top'   nowrap class=legend style='font-size:14px'>{cache-size}:</td>
			<td align='left' valign='top' >" . Field_text('cache-size',$cf->main_array["cache-size"],"font-size:14px;padding:3px;width:70px")."</td>
			<td align='left' valign='top'  >". help_icon("{cache-size_text}")."</td>
		</tr>
	
		<tr>
			<td align='right' valign='top'  valign='top'   nowrap class=legend style='font-size:14px'>{domain}:</td>
			<td align='left' valign='top' >" . Field_text('dnsmasq-domain',$cf->main_array["domain"],"font-size:14px;padding:3px;")."</td>
			<td align='left' valign='top'  >". help_icon("{dnsmasq_domain_explain}")."</td>
		</tr>
	
		<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveDNSMASQMainConf();",16)."</td>
		</tr>
	</tbody>
</table>";	
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$instance=$_GET["instance"];
	$array["sub-status"]='{status}';
	$array["sub-settings"]='{settings}';
	$array["sub-listen"]='{network}';
	$array["sub-localdomains"]='{local_domains}';
	$array["sub-wpad"]='{wpad_title}';

	
	
	
	$fonctsize="font-size:16px";
	
		
	

	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span span style='$fonctsize'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_dnsmasqsub style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_dnsmasqsub').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";	
	
	
	
}

function page_events(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	


echo $html;
	
}

function page_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1% valign='top'><div id='get-status'></div></td>
		<td width=99% valign='top'>
			<div class=explain id='dnsmaskrool' style='font-size:13px'>{dnsmasq_intro_settings}</div>
			<div style='text-align:right'>
				".imgtootltip("refresh-24.png","{refresh}","LoadAjax('get-status','$page?get-status=yes');")."
			</div>
			<div style='text-align:right'><hr>
				".button("{restart_service}","RestartDNSMASQService()")."
			</div>			
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('get-status','$page?get-status=yes');
		
		
	var x_RestartDNSMASQService= function (obj) {
		RefreshTab('main_config_dnsmasqsub');
	}

	function RestartDNSMASQService(){
		var XHR = new XHRConnection();
		XHR.appendData('restart-dnsmasq','yes');
		AnimateDiv('events-$t');
		XHR.sendAndLoad('$page', 'POST',x_RestartDNSMASQService);
		
	
	}		
		
	</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}



function page_settings(){
	$t=time();
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$sys->array_interfaces[null]='{select}';
	$sys->array_tcp_addr[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,'interfaces',null,"style:font-size:14px;padding:3px;");
	$tcpaddr=Field_array_Hash($sys->array_tcp_addr,'listen_addresses',null,"style:font-size:14px;padding:3px;");
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}

	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=1;}
	
	$EnableDNSMASQOCSDB=$sock->GET_INFO("EnableDNSMASQOCSDB");
	if(!is_numeric($EnableDNSMASQOCSDB)){$EnableDNSMASQOCSDB=1;}	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$DNSMasqUseStatsAppliance=$sock->GET_INFO("DNSMasqUseStatsAppliance");
	if(!is_numeric($DNSMasqUseStatsAppliance)){$DNSMasqUseStatsAppliance=0;}	
	
	
	
	
$f[]="domain-needed";
$f[]="expand-hosts";
$f[]="bogus-priv";
$f[]="filterwin2k";
$f[]="strict-order";
$f[]="no-resolv";
$f[]="no-negcache";
$f[]="no-poll";
$f[]="log-queries";


while (list ($index, $key) = each ($f) ){
	if($cf->main_array[$key]=="yes"){$cf->main_array[$key]=1;}else{$cf->main_array[$key]=0;}
	$js[]="if(document.getElementById('$key').checked){XHR.appendData('$key','yes');	}else{XHR.appendData('$key','no');}";
}

	// kill -USR1 17226
$html="
<table style='width:100%'>
<tr>
	<td valign='top' width=1%><div id='status-$t'></div></td>
	<td valign='top' width=99%>
<table style='width:99%' class=form>
<tr>
	<td align='right' valign='top' class=legend style='font-size:14px'>{EnableDNSMASQ}:</td>
	<td align='left' valign='top'>". Field_checkbox("EnableDNSMASQ",1,$EnableDNSMASQ,"EnableDNSMASQSave()")."</td>
	<td align='left' valign='top'  width=1%>". help_icon("{EnableDNSMASQ_explain}")."</td>
</tr>
<tr>
	<td align='right' valign='top' class=legend style='font-size:14px'>{EnableDNSMASQLDAPDB}:</td>
	<td align='left' valign='top'>". Field_checkbox("EnableDNSMASQLDAPDB",1,$EnableDNSMASQLDAPDB,"EnableDNSMASQSave()")."</td>
	<td align='left' valign='top'  width=1%>". help_icon("{EnableDNSMASQLDAPDB_explain}")."</td>
</tr>
<tr>
	<td align='right' valign='top' class=legend style='font-size:14px'>{EnableDNSMASQOCSDB}:</td>
	<td align='left' valign='top'>". Field_checkbox("EnableDNSMASQOCSDB",1,$EnableDNSMASQOCSDB,"EnableDNSMASQSave()")."</td>
	<td align='left' valign='top'  width=1%>". help_icon("{EnableDNSMASQOCSDB_explain}")."</td>
</tr>
<tr>
	<td align='right' valign='top' class=legend style='font-size:14px'>{DNSMasqUseStatsAppliance}:</td>
	<td align='left' valign='top'>". Field_checkbox("DNSMasqUseStatsAppliance",1,$DNSMasqUseStatsAppliance,"EnableDNSMASQSave()")."</td>
	<td align='left' valign='top'  width=1%>". help_icon("{DNSMasqUseStatsAppliance_explain}")."</td>
</tr>
</table>

<div id='ffm1$t'></div>

</td>
</tr>
</table>






<script>

	var x_SaveDNSMASQMainConf= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_dnsmasqsub');
	}

	function SaveDNSMASQMainConf(){
	var XHR = new XHRConnection();
		XHR.appendData('resolv-file',document.getElementById('resolv-file').value);
		XHR.appendData('cache-size',document.getElementById('cache-size').value);
		XHR.appendData('domain',document.getElementById('dnsmasq-domain').value);
		XHR.appendData('SaveConf1','yes');		
		". @implode("\n", $js)."
		AnimateDiv('ffm1$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveDNSMASQMainConf);
		
	
	}


	var x_EnableDNSMASQSaveBack= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_dnsmasqsub');
		
	}		
	
	function EnableDNSMASQSave(key){
	
		var XHR = new XHRConnection();
		if(document.getElementById('EnableDNSMASQ').checked){XHR.appendData('EnableDNSMASQ',1);	}else{XHR.appendData('EnableDNSMASQ',0);}
		if(document.getElementById('EnableDNSMASQLDAPDB').checked){XHR.appendData('EnableDNSMASQLDAPDB',1);	}else{XHR.appendData('EnableDNSMASQLDAPDB',0);}
		if(document.getElementById('EnableDNSMASQOCSDB').checked){XHR.appendData('EnableDNSMASQOCSDB',1);	}else{XHR.appendData('EnableDNSMASQOCSDB',0);}
		if(document.getElementById('DNSMasqUseStatsAppliance').checked){XHR.appendData('DNSMasqUseStatsAppliance',1);	}else{XHR.appendData('DNSMasqUseStatsAppliance',0);}
		CheckStatsAppliance();
		AnimateDiv('dnsmaskrool');
		XHR.sendAndLoad('$page', 'GET',x_EnableDNSMASQSaveBack);
	}

	function RefreshMainForm(){
		LoadAjax('ffm1$t','$page?ffm1=yes');
	}
	
	function CheckStatsAppliance(){
		var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
		if(EnableRemoteStatisticsAppliance==0){
			document.getElementById('DNSMasqUseStatsAppliance').disabled=true;
			return;
		}
		if(document.getElementById('DNSMasqUseStatsAppliance').checked){
			document.getElementById('EnableDNSMASQLDAPDB').disabled=true;
			document.getElementById('EnableDNSMASQOCSDB').disabled=true;
			return;
		}
		
			document.getElementById('EnableDNSMASQLDAPDB').disabled=false;
			document.getElementById('EnableDNSMASQOCSDB').disabled=false;		
		
	}
	
	
	RefreshMainForm();
	CheckStatsAppliance();
	LoadAjax('status-$t','$page?get-status=yes');
</script>

";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function page_listen(){
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$sys->array_interfaces[null]='{select}';
	$sys->array_tcp_addr[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,'interfaces',null,"style:font-size:16px;padding:3px;");
	$tcpaddr=Field_array_Hash($sys->array_tcp_addr,'listen_addresses',null,"style:font-size:16px;padding:3px;");
	
	
	
$html="<div style='font-size:22px'>{interface} {dnsmasq_listen_address}</div>
<p>&nbsp;</p>
<center>
<table style='width:250px' class=form>
<tr>
<td>
<form name='ffm2'>
<table>
	<tr>
		<td valign='middle' class=legend nowrap style='font-size:16px'>{interface}:</td>
		<td valign='middle'>$interfaces</td>
		<td valign='middle'>". button("{add}","ParseForm('ffm2','$page',true);InterfacesReload()",16)."</td>
		<td width=1%>". help_icon("{dnmasq_interface_text}")."</td>
	</tr>
</table>
</form>
</td>
</tr>
<tr>
<td>
<form name='ffm21'>
<center>
<table >
	<tr>
		<td valign='middle' class=legend nowrap style='font-size:16px'>{dnsmasq_listen_address}:</td>
		<td>$tcpaddr</td>
		<td>". button("{add}","ParseForm('ffm21','$page',true);ListentAddressesReload()",16)."</td>
		<td width=1%>". help_icon("{dnsmasq_listen_address_text}")."</td>
	</tr>
</table>
</center>
</form>
</td>
</tr>
</table>
</center>
<p>&nbsp;</p>
<div id='dnmasq_interface'>" . LoadInterfaces() . "</div>
<div id='dnsmasq_listen_address'>" . LoadListenAddress() . "</div>"	;
	

echo $tpl->_ENGINE_parse_body($html);

}



function LoadInterfaces(){
	$conf=new dnsmasq();
	if(!is_array($conf->array_interface)){return null;}
	$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th colspan=3>&nbsp;</th>
		
	</tr>
</thead>
<tbody class='tbody'>";	
	while (list ($index, $line) = each ($conf->array_interface) ){
	if(trim($line)==null){continue;}
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html . "
		<tr class=$classtr>
			<td width=1%><img src='img/folder-network-32.png'></td>
			<td  width=99% style='font-size:16px'>$line</td>
			<td  width=1%>" . imgtootltip('delete-32.png','{delete}',"DnsmasqDeleteInterface('$index');")."</td>
		</tr>";
	}
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html . "</table>");
	
}
function LoadListenAddress(){
	$conf=new dnsmasq();
	if(!is_array($conf->array_listenaddress)){return null;}
	$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th colspan=3>&nbsp;</th>
		
	</tr>
</thead>
<tbody class='tbody'>";
	while (list ($index, $line) = each ($conf->array_listenaddress) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(trim($line)==null){continue;}
		$html=$html . "
		<tr class=$classtr>
			<td width=1%><img src='img/folder-network-32.png'></td>
			<td  width=99% style='font-size:16px'>$line</td>
			<td  width=1%>" . imgtootltip('delete-32.png','{delete}',"DnsmasqDeleteListenAddress('$index');")."</td>
		</tr>";
	}
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html . "</table></center>");
	
}

function wpad(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["WPAD"];
	$t=time();
	if(!is_numeric($Params["PORT"])){
		$SquidEnableProxyPac=$sock->GET_INFO("SquidEnableProxyPac");
		if($SquidEnableProxyPac==1){
			$listen_port=$sock->GET_INFO("SquidProxyPacPort");
			if(!is_numeric($listen_port)){$listen_port=8890;}
		}
		if(!is_numeric($listen_port)){$listen_port=80;}
		$Params["PORT"]=$listen_port;
		$Params["URI"]="proxy.pac";
	}
	
	if($Params["HOST"]==null){
		$Params["HOST"]="yourserver.yourdomain";
	}
	
	
	$html="
	<div id='div-$t' class=explain style='font-size:14px'>{dnsmasq_wpad_explain}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td valign='top' class=legend style='font-size:16px' valign='middle'>{enable}:</td>
		<td>". Field_checkbox("ENABLE-$t", 1,$Params["ENABLE"],"CheckWpadEnable()")."</td>
	</tr>	
	
	<tr>
		<td valign='top' class=legend style='font-size:16px' valign='middle'>{listen_port}:</td>
		<td>". Field_text("PORT-$t",$Params["PORT"],"font-size:16px;width:90px")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px' valign='middle'>{ipaddr}:</td>
		<td>". field_ipv4("IP_ADDR-$t",$Params["IP_ADDR"],"font-size:16px;")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:16px' valign='middle'>{hostname}:</td>
		<td style='font-size:16px'>wpad.". Field_text("HOST-$t",$Params["HOST"],"font-size:16px;width:190px")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:16px' valign='middle'>{url}:</td>
		<td style='font-size:16px'>http://wpad.{$Params["HOST"]}:{$Params["PORT"]}/". Field_text("URI-$t",$Params["URI"],"font-size:16px;width:120px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}", "SaveForm$t()",18)."</td>
	</tr>
	</table>
	<script>
		
		var x_SaveForm$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_dnsmasqsub');						
				
		}		
	
	
		function CheckWpadEnable(){
			document.getElementById('PORT-$t').disabled=true;
			document.getElementById('IP_ADDR-$t').disabled=true;
			document.getElementById('HOST-$t').disabled=true;
			document.getElementById('URI-$t').disabled=true;
			if(document.getElementById('ENABLE-$t').checked){
				document.getElementById('PORT-$t').disabled=false;
				document.getElementById('IP_ADDR-$t').disabled=false;
				document.getElementById('HOST-$t').disabled=false;
				document.getElementById('URI-$t').disabled=false;		
			}
		}
	
	
		function SaveForm$t(){
			var XHR = new XHRConnection();
			XHR.appendData('wpad','yes');
			XHR.appendData('PORT',document.getElementById('PORT-$t').value);
			XHR.appendData('IP_ADDR',document.getElementById('IP_ADDR-$t').value);
			XHR.appendData('HOST',document.getElementById('HOST-$t').value);
			XHR.appendData('URI',document.getElementById('URI-$t').value);
			AnimateDiv('div-$t');
			if(document.getElementById('ENABLE-$t').checked){XHR.appendData('ENABLE',1);	}else{XHR.appendData('ENABLE',0);}
			XHR.sendAndLoad('$page', 'POST',x_SaveForm$t);		
		
		}
		CheckWpadEnable();
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function wpad_save(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$page=CurrentPageName();
	$conf->ARTICA_ARRAY["WPAD"]=$_POST;
	$conf->SaveConfToServer();
	
}


function SaveConf1(){
	unset($_POST["SaveConf1"]);
	
	if($_POST["resolv-file"]=='/etc/resolv.conf'){$_POST["resolv-file"]="/etc/dnsmasq.resolv.conf";}
	
	$conf=new dnsmasq();
	while (list ($key, $line) = each ($_POST) ){
		if($line<>null){
			$conf->main_array[$key]=$line;	
		}else{unset($conf->main_array[$key]);}
		}
	$conf->SaveConf(); 
}

function interfaces(){
	$conf=new dnsmasq();
	$conf->array_interface[]=$_GET["interfaces"];
	$conf->SaveConf();
}
function DnsmasqDeleteInterface(){
	$conf=new dnsmasq();
	unset($conf->array_interface[$_GET["DnsmasqDeleteInterface"]]);
	$conf->SaveConf();
}



function SaveListenAddress(){
	$addr=$_GET["listen_addresses"];
	$conf=new dnsmasq();
	$conf->array_listenaddress[]=$addr;
	$conf->SaveConf();
}
function DnsmasqDeleteListenAddress(){
	$index=$_GET["DnsmasqDeleteListenAddress"];
	$conf=new dnsmasq();
	unset($conf->array_listenaddress[$index]);
	$conf->SaveConf();
	
}

function EnableDNSMASQSave(){
	$sock=new sockets();
	
	$users=new usersMenus();
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	$sock->SET_INFO("EnableDNSMASQLDAPDB",$_GET["EnableDNSMASQLDAPDB"]);
	$sock->SET_INFO("EnableDNSMASQOCSDB",$_GET["EnableDNSMASQOCSDB"]);
	$sock->SET_INFO("DNSMasqUseStatsAppliance",$_GET["DNSMasqUseStatsAppliance"]);
	
	
	
	
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	
	if($_GET["EnableDNSMASQ"]==1){
		if($users->POWER_DNS_INSTALLED){
			if($EnablePDNS==1){
				$tpl=new templates();
				echo $tpl->javascript_parse_text("{COULD_NOT_PERF_OP_SOFT_ENABLED}:\n{APP_PDNS}");
				$sock->SET_INFO("EnableDNSMASQ",0);
				return;
			}
		}

	}
	writelogs("Save EnableDNSMASQ: {$_GET["EnableDNSMASQ"]}",__FUNCTION__,__FILE__,__LINE__);
	$sock->SET_INFO("EnableDNSMASQ",$_GET["EnableDNSMASQ"]);
	if($_GET["EnableDNSMASQ"]==1){
		$sock->SET_INFO("EnableDNSMASQ",0);
		$sock->SET_INFO("EnableDNSMASQ",1);
	}
	
	
	$sock->getFrameWork("cmd.php?restart-dnsmasq=yes");
	$sock->getFrameWork("services.php?restart-artica-status=yes");
}


function page_localdomains(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["LOCALNET"];	
	$t=time();
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$dnsmasq_localdomains_explain=$tpl->_ENGINE_parse_body("{dnsmasq_localdomains_explain}");
	$new_domain=$tpl->javascript_parse_text("{new_domain}");
	$html="<div class=explain style='font-size:14px'>$dnsmasq_localdomains_explain</div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?localdomain-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$domains', name : 'domain', width : 719, sortable : false, align: 'left'},	
		{display: '&nbsp;', name : 'ItemsNumber', width :37, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 34, sortable : false, align: 'center'},
		],
		
buttons : [
	{name: '$new_domain', bclass: 'add', onpress : AddDnsMasqDomain},
		],			
	
	searchitems : [
		{display: '$domains', name : 'domain'},
		],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 840,
	height: 250,
	singleSelect: true,
	rpOptions: [200]
	
	});   
});	

	var x_DnsMasqLocalDomainEnable= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}	
		$('#flexRT$t').flexReload();
	}
	
	
	function DnsMasqLocalDomainEnable(domain,md5){
		var XHR = new XHRConnection();
		XHR.appendData('localdomain-enable', domain);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',x_DnsMasqLocalDomainEnable); 	
	
	}
	
	function AddDnsMasqDomain(){
		var dom=prompt('$new_domain');
		if(dom){
			var XHR = new XHRConnection();
			XHR.appendData('localdomain-add', dom);
			XHR.sendAndLoad('$page', 'POST',x_DnsMasqLocalDomainEnable); 		
		}
	}
	
	function DnsMasqLocalDomainDelete(domain){
		var XHR = new XHRConnection();
		XHR.appendData('localdomain-del', domain);
		XHR.sendAndLoad('$page', 'POST',x_DnsMasqLocalDomainEnable); 	
	
	}	

</script>
	
	";
	
	echo $html;
	
}

function page_localdomains_enable(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	$conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-enable"]]=$_POST["enable"];
	$conf->SaveConf();
}



function page_localdomains_add(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	$conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-add"]]=1;
	$conf->SaveConf();		
	
}
function page_localdomains_del(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	unset($conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-del"]]);
	$conf->SaveConf();	
}

function page_localdomains_search(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["LOCALNET"];	
	if(count($Params)==0){
		$ldap=new clladp();$hash=$ldap->AllDomains();
		$hash["localdomain"]="localdomain";
		$hash["localhost.localdomain"]="localhost.localdomain";
		while (list ($key, $line) = each ($hash) ){$conf->ARTICA_ARRAY["LOCALNET"][$key]=0;}$conf->SaveConf();
	}
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = count($conf->ARTICA_ARRAY["LOCALNET"]);
	$data['rows'] = array();
	
	$search=null;
	ksort($conf->ARTICA_ARRAY["LOCALNET"]);
	
if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$search=$_POST["query"];

}
	

	while (list ($domain, $enabled) = each ($conf->ARTICA_ARRAY["LOCALNET"]) ){
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		
		$md5=md5($domain);
		$enable=Field_checkbox($md5,1,$enabled,"DnsMasqLocalDomainEnable('$domain','$md5')");	
		$delete=imgtootltip("delete-24.png","{delete} $domain","DnsMasqLocalDomainDelete('$domain')");
		$color="black";
		if($enabled==0){$color="#D0D0D0";}
		
		writelogs("{$ligne["ID"]} => {$ligne["rulename"]}",__FUNCTION__,__FILE__,__LINE__);
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<span style='font-size:18px;color:$color'>$domain</span>",
			$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}	
	
	
	

	