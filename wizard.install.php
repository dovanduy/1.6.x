<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.browser.detection.inc');
include_once('ressources/class.resolv.conf.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');

if(isset($_GET["setup-1"])){setup_1();exit;}
if(isset($_GET["setup-2"])){setup_2();exit;}
if(isset($_GET["setup-3"])){setup_3();exit;}
if(isset($_GET["setup-4"])){setup_4();exit;}
if(isset($_POST["savedsettings"])){save();exit;}
if(isset($_GET["settings-dns"])){dns_save();exit;}
if(isset($_GET["settings-ou"])){ou_save();exit;}
if(isset($_GET["settings-final"])){final_show();exit;}

js();



function js(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();
	
	$title=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
	
	echo "
		$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/blurps.css' type='text/css' media='screen' />\"));
		$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/styles_forms.css' type='text/css' media='screen' />\"));
	
	YahooSetupControlModalFixedNoclose(850,'$page?setup-1=yes','$title')";
	
}

function setup_1(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$WizardSavedSettings=$sock->GET_INFO("WizardSavedSettings");
	
	$html="
	<input type='hidden' id='savedsettings' value=''>
	<div id='setup-content'>
	<div style='margin:10px;width:95%' class=form>
	<div style='font-size:22px;font-weight:bolder'>{WELCOME_ON_ARTICA_PROJECT}</div>
	<div style='margin:18px;font-size:14px'>{WELCOME_WIZARD_ARC1}</div>
	<div style='text-align:right'><hr>". button("{next}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$WizardSavedSettings')","18px")."</div>
	<center style='margin:10px;width:95%'><img src='img/bg_user.jpg'></center>
	</div>
	</div>
	<script>
		$(\".ui-dialog-titlebar-close\").remove();

		
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function setup_2(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$netbiosname_field=$tpl->javascript_parse_text("{netbiosname}");
	$domain_field=$tpl->javascript_parse_text("{domain}");
	
	if(count($savedsettings)<3){
			$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));	
			if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}	
		
			
			$resolv_conf=explode("\n",@file_get_contents("/etc/resolv.conf"));
			while (list ($index, $lines) = each ($resolv_conf) ){
				if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
				if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
					$g=trim($re[1]);
					if($g=="127.0.0.1"){continue;}
					$arrayNameServers[]=$g;
				}
			}	
			
			if(count($arrayNameServers)==0){
				$resolv_conf=file("/etc/resolvconf/resolv.conf.d/original");
				while (list ($index, $lines) = each ($resolv_conf) ){
					if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
					if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
						$g=trim($re[1]);
						if($g=="127.0.0.1"){continue;}
						$arrayNameServers[]=$g;
					}
				}			
				
			}
			
			
			if(strpos($hostname, '.')>0){
				$Thostname=explode(".", $hostname);
				$netbiosname=$Thostname[0];
				unset($Thostname[0]);
				$domainname=@implode(".", $Thostname);
			}else{
				$netbiosname=$hostname;
			}
			
			if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $netbiosname)){$netbiosname=null;}	
	
	
	}else{
		$netbiosname=$savedsettings["netbiosname"];
		$domainname=$savedsettings["domain"];
		$arrayNameServers[0]=$savedsettings["DNS1"];
		$arrayNameServers[1]=$savedsettings["DNS2"];
	}
	
	if($users->SQUID_INSTALLED){
		$arrayPP["3128"]=3128;
		$arrayPP["8080"]=8080;
		$arrayPP["9090"]=9090;
		
		$proxy="	<tr>
		<td class=legend style='font-size:14px'>{proxy_listen_port}:</td>
		<td>". Field_array_Hash($arrayPP,"proxy_listen_port",$savedsettings["proxy_listen_port"],null,null,0,"font-size:14px")."</td>
		</tr>";
	}
	
	if($users->POWER_DNS_INSTALLED){
		$pdns="	<tr>
		<td class=legend style='font-size:14px'>{activate_dns_service}:</td>
		<td>". Field_checkbox("EnablePDNS", 1,0)."</td>
		</tr>";		
		
	}
	
	if($users->FREERADIUS_INSTALLED){
		$freeradius="	<tr>
		<td class=legend style='font-size:14px'>{activate_radius_service}:</td>
		<td>". Field_checkbox("EnableFreeRadius", 1,0)."</td>
		</tr>";		
	}
	
	if($users->dhcp_installed){
		$dhcpd="	<tr>
		<td class=legend style='font-size:14px'>{activate_dhcp_service}:</td>
		<td>". Field_checkbox("EnableDHCPServer", 1,0)."</td>
		</tr>";
	}	
	
	//FIRST_WIZARD_NIC2 -> fini -> demande de reboot
	$t=time();
	
	$IPADDR=$savedsettings["IPADDR"];
	$NETMASK=$savedsettings["NETMASK"];
	$GATEWAY=$savedsettings["GATEWAY"];
	$metric=$savedsettings["metric"];
	$BROADCAST=$savedsettings["BROADCAST"];
	
	$nic=new system_nic("eth0");
	if($IPADDR==null){$IPADDR=$nic->IPADDR;}
	if($NETMASK==null){$NETMASK=$nic->NETMASK;}
	if($GATEWAY==null){$GATEWAY=$nic->GATEWAY;}
	if($BROADCAST==null){$BROADCAST=$nic->BROADCAST;}
	if($metric==null){$metric=$nic->metric;}
	if(!is_numeric($metric)){$metric=100;}
	if($metric<2){$metric=100;}
	$DISABLED=false;
	$FORM="
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{serveretdom}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{netbiosname}:</td>
		<td>". Field_text("hostname_netbios",$netbiosname,"font-size:16px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:16px'>{DomainOfThisserver}:</td>
		<td>". Field_text("hostname_domain",$domainname,"font-size:16px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{network_settings_will_be_applied_after_reboot}</td>
	</tr>
		<tr>
			<td class=legend style='font-size:14px'>{tcp_address}:</td>
			<td>" . field_ipv4("IPADDR",$IPADDR,'padding:3px;font-size:18px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{netmask}:</td>
			<td>" . field_ipv4("NETMASK",$NETMASK,'padding:3px;font-size:18px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
			
		<tr>
			<td class=legend style='font-size:14px'>{gateway}:</td>
			<td>" . field_ipv4("GATEWAY",$GATEWAY,'padding:3px;font-size:18px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{metric}:</td>
			<td>" . field_text("metric-$t",$metric,'padding:3px;font-size:18px;width:90px',null,null,null,false,null,$DISABLED)."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:14px'>{broadcast}:</td>
			<td>" . field_ipv4("BROADCAST",$BROADCAST,'padding:3px;font-size:18px',null,null,null,false,null,$DISABLED)."</td>
		</tr>		
			
				
				
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{dns_servers}</div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{primary_dns}:</td>
		<td>". field_ipv4("DNS1", $arrayNameServers[0],"padding:3px;font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{secondary_dns}:</td>
		<td>". field_ipv4("DNS2", $arrayNameServers[1],"padding:3px;font-size:18px")."</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{services}</div></td>
	</tr>	
	$proxy			
	$pdns	
	$freeradius	
	$dhcpd	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'><div style='text-align:right'><hr>". button("{next}","ChangeQuickHostname()","18px")."</div></td>
	</tr>
	</table>
	
	
	<script>
		var X_ChangeQuickHostname= function (obj) {
			var results=obj.responseText;
			LoadAjax('setup-content','$page?setup-3=yes&savedsettings='+results)
		
			}
			
		function ChangeQuickHostnameCheck(e){
			if(checkEnter(e)){ChangeQuickHostname();}
		}

		
		function ChangeQuickHostname(){
			var XHR = new XHRConnection();
			var netbios=document.getElementById('hostname_netbios').value;
			var dom=document.getElementById('hostname_domain').value;
			if(netbios.length==0){alert('$netbiosname_field (Null!)');return;}
			if(dom.length==0){alert('$domain_field (Null!)');return;}
			if(dom=='localhost.localdomain'){alert('localhost.localdomain wrong domain...');return;}
			
			if(document.getElementById('proxy_listen_port')){
				XHR.appendData('proxy_listen_port',document.getElementById('proxy_listen_port').value);
			}
			if(document.getElementById('EnablePDNS')){
				var EnablePDNS=0;
				if(document.getElementById('EnablePDNS').checked){EnablePDNS=1;}
				XHR.appendData('EnablePDNS',EnablePDNS);
			}

			if(document.getElementById('EnableFreeRadius')){
				var EnableFreeRadius=0;
				if(document.getElementById('EnableFreeRadius').checked){EnableFreeRadius=1;}
				XHR.appendData('EnableFreeRadius',EnableFreeRadius);
			}

			if(document.getElementById('EnableDHCPServer')){
				var EnableDHCPServer=0;
				if(document.getElementById('EnableDHCPServer').checked){EnableDHCPServer=1;}
				XHR.appendData('EnableDHCPServer',EnableDHCPServer);
			}				
			
			    
			XHR.appendData('IPADDR',document.getElementById('IPADDR').value);
			XHR.appendData('NETMASK',document.getElementById('NETMASK').value);  
			XHR.appendData('GATEWAY',document.getElementById('GATEWAY').value);
			XHR.appendData('BROADCAST',document.getElementById('BROADCAST').value);
			XHR.appendData('metric',document.getElementById('metric-$t').value);          
			
			
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			XHR.appendData('DNS1',document.getElementById('DNS1').value);
			XHR.appendData('DNS2',document.getElementById('DNS2').value);
			XHR.appendData('netbiosname',netbios);
			XHR.appendData('domain',dom);
			AnimateDiv('setup-content');
			XHR.sendAndLoad('$page', 'POST',X_ChangeQuickHostname);
			
		}
	
	</script>
	
	";
	
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1% valign='top'><img src='img/folder-network-128.png'></td>
		<td><div style='font-size:22px;font-weight:bolder;margin-bottom:10px'>{squid_net_simple}</div>$FORM</td>
	</tr>
	</table>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function setup_3(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	
	$please_fill_all_form_values=$tpl->javascript_parse_text("{please_fill_all_form_values}");
	$organization=$savedsettings["organization"];
	$employees=$savedsettings["employees"];
	$company_name=$savedsettings["company_name"];
	$country=$savedsettings["country"];
	$city=$savedsettings["city"];
	$mail=$savedsettings["mail"];
	$telephone=$savedsettings["telephone"];
	$UseServerV=$savedsettings["UseServer"];
	$smtp_domainname=$savedsettings["smtp_domainname"];
	$t=time();
	$UseServer[null]="{select}";
	$UseServer["ASMAIL"]="{mail_server}";
	$UseServer["ASRELAY"]="{relay_server}";
	$UseServer["ASFILE"]="{file_server}";
	$UseServer["ASPROXY"]="{proxy_server}";
	
	
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];	
	$page=CurrentPageName();
	$tpl=new templates();
	$resolv=new resolv_conf();
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	$resolv->save();

	if($_POST["IPADDR"]<>null){
		$nics=new system_nic("eth0");
		$nics->eth="ethO";
		$nics->IPADDR=$arrayNameServers["IPADDR"];
		$nics->NETMASK=$arrayNameServers["NETMASK"];
		$nics->GATEWAY=$arrayNameServers["GATEWAY"];
		$nics->BROADCAST=$arrayNameServers["BROADCAST"];
		$nics->DNS1=$arrayNameServers[0];
		$nics->DNS2=$arrayNameServers[1];
		$nics->dhcp=0;
		$nics->metric=$savedsettings["metric"];
		$nics->enabled=1;
		$nics->NoReboot=true;
		$nics->SaveNic();
	}
	
	
	
	$UseServerF=Field_array_Hash($UseServer, "UseServer",$UseServerV,"style:font-size:14px");
	$UseServerFF="	
	</tr>
		<td class=legend style='font-size:16px'>{you_using_this_server_for}:</td>
		<td>$UseServerF</td>
	</tr>";
	
	if($users->SMTP_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='SMTP Relay Appliance'>";
	}
	if($users->KASPERSKY_WEB_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Kaspersky Web Appliance'>";
	}	
	if($users->LOAD_BALANCE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Load balance Appliance'>";
	}		
	if($users->ZARAFA_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Zarafa Appliance'>";
	}		
	if($users->SAMBA_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='File Sharing Appliance'>";
	}		
	if($users->WEBSTATS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Web statistics Appliance'>";
	}		
	if($users->KASPERSKY_SMTP_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Kaspersky SMTP Appliance'>";
	}	
	if($users->APACHE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Apache Appliance'>";
	}	
	if($users->SQUID_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Proxy Appliance'>";
	}		
	if($users->HAPRROXY_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Load balance Appliance'>";
	}		
	if($users->FULL_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Artica Full Appliance'>";
	}
	
	if($users->MYCOSI_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='MyCOSI Appliance'>";
	}
	
	if($users->CYRUS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='IMAP-POP3 OpenSource Appliance'>";
	}

	if($users->PROXYTINY_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Tiny Proxy Appliance'>";
	}		
	
	
	
	
	
	//toujours à la fin...
	if($UseServerFF==null){
		if($users->FROM_SETUP){
			$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Installed from Setup'>";
		}
	}
	
	
	$FORM="
	
	<table style='width:99%' class=form id='$t'>
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{YourRealCompany}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{company_name}:</td>
		<td>". Field_text("company_name",$company_name,"font-size:16px;width:220px")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:16px'>{country}:</td>
		<td>". Field_text("country",$country,"font-size:16px;width:220px")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:16px'>{city}:</td>
		<td>". Field_text("city",$city,"font-size:16px;width:220px")."</td>
	</tr>	
	</tr>
		<td class=legend style='font-size:16px'>{your_email_address}:</td>
		<td>". Field_text("mail",$mail,"font-size:16px;width:220px")."</td>
	</tr>	
	</tr>
		<td class=legend style='font-size:16px'>{phone_title}:</td>
		<td>". Field_text("telephone",$telephone,"font-size:16px;width:220px")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:16px'>{nb_employees}:</td>
		<td>". Field_text("employees",$employees,"font-size:16px;width:80px")."</td>
	</tr>

	$UseServerFF
	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>{virtual_company}</div></td>
	</tr>	
	</tr>
		<td class=legend style='font-size:16px'>{organization}:</td>
		<td>". Field_text("organization",$organization,"font-size:16px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{smtp_domain}:</td>
		<td>". Field_text("smtp_domainname",$smtp_domainname,"font-size:16px;width:220px")."</td>
	</tr>	
	
	<tr>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings={$_GET["savedsettings"]}')","18px")."</div></td>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:right'>". button("{build_parameters}","ChangeCompanySettings()","18px")."</div></td>
	</tr>
	</table>
	<div style='font-size:11px;text-align:right'>{noticeregisterform}</div>
	<script>
		var X_ChangeCompanySettings= function (obj) {
			var results=obj.responseText;
			LoadAjax('setup-content','$page?setup-4=yes&savedsettings='+results);
		
			}
		
		
		function ChangeCompanySettings(){
			var XHR = XHRParseElements('$t');
			var testval=document.getElementById('company_name').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('country').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('city').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}						
			var testval=document.getElementById('mail').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('employees').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('organization').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('smtp_domainname').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}			
			
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			XHR.sendAndLoad('$page', 'POST',X_ChangeCompanySettings);
			
		}
	
	</script>
	
	";	
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1% valign='top'><img src='img/users-info-128.png'></td>
		<td><div style='font-size:22px;font-weight:bolder;margin-bottom:10px'>{ContactAndOrganization}</div>$FORM</td>
	</tr>
	</table>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_POST["savedsettings"]));
	unset($_POST["savedsettings"]);
	while (list ($key, $value) = each ($_POST) ){
		$savedsettings[$key]=$value;
		
	}
	
	echo base64_encode(serialize($savedsettings));
	
}

function setup_4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	
	
	$resolv=new resolv_conf();
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];	
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	$resolv->save();	
	$sock->getFrameWork("services.php?resolvConf=yes");	
	sleep(1);
	
	
	if($users->SQUID_INSTALLED){
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		$squid=new squidbee();
		if(is_numeric($savedsettings["proxy_listen_port"])){
			$squid->listen_port=$savedsettings["proxy_listen_port"];
			
		}
		$squid->SaveToLdap();
		
	}
	if(isset($savedsettings["EnablePDNS"])){
		$sock->SET_INFO("EnablePDNS",$savedsettings["EnablePDNS"]);
	}
	
	if(isset($savedsettings["EnableDHCPServer"])){
		$sock->SET_INFO("EnableDHCPServer",$savedsettings["EnableDHCPServer"]);
	}	
	if(isset($savedsettings["EnableFreeRadius"])){
		$sock->SET_INFO("EnableFreeRadius",$savedsettings["EnableFreeRadius"]);
		$sock->getFrameWork("freeradius.php?restart=yes");
	}	
	  
	
	
	
	
	if(!isset($_GET["bypass"])){
		if(!check_email_address($savedsettings["mail"])){
			$warn_email_invalid_wizard=$tpl->_ENGINE_parse_body("{warn_email_invalid_wizard}");
			$warn_email_invalid_wizard=str_replace("%s", $savedsettings["mail"], $warn_email_invalid_wizard);
			$html="
			<table style='width:99%' class=form>
			<tr>
				<td valign='top'><img src='img/error-64.png'></td>
				<td style='padding-left:15px'><strong style='font-size:18px'>$warn_email_invalid_wizard</strong>
				<center>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","22px")."</center>
			</td>
			</tr>
			<tr>
			</table>
			<div style='text-align:right;margin-top:10px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:LoadAjax('setup-content','$page?setup-4=yes&bypass=yes&savedsettings={$_GET["savedsettings"]}');\"
			style='font-size:14px;text-decoration:underline'>{i_understand_continue}...</a></div>						
						
						";
			echo $tpl->_ENGINE_parse_body($html);
			return;
			
		}
	}
	
	$html="
	<div id='settings-final'>
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{build_parameters}</strong>
			
		</td>
		</tr>
		</table>
	
	<div id='settings-dns'></div>
	<div id='settings-ou'></div>
	</div>
	<center>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","22px")."</center>
	<script>
		LoadAjax('settings-dns','$page?settings-dns=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
	
	
}

function dns_save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];
	$page=CurrentPageName();
	$tpl=new templates();	

	if($savedsettings["DNS1"]==null){
		
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/danger64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{saving_network_failed}:<br>$netbiosname.$domainname<br>DNS1:{$arrayNameServers[0]}<br>DNS2{$arrayNameServers[1]}</strong>
			
		</td>
		</tr>
		</table>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	return;	
		
	}
	

	$resolv=new resolv_conf();
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	$resolv->save();
	$sock=new sockets();
	$sock->SET_INFO("myhostname","$netbiosname.$domainname");
	$sock->getFrameWork("cmd.php?ChangeHostName=$netbiosname.$domainname");
	$sock->getFrameWork("services.php?resolvConf=yes");
	$sock->getFrameWork("services.php?folders-security=yes");		
	$sock->SaveConfigFile($_GET["savedsettings"], "WizardSavedSettings");
	
	
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{saving_network_done}:<br>$netbiosname.$domainname<br>{$arrayNameServers[0]}<br>{$arrayNameServers[1]}</strong>
			
		</td>
		</tr>
		</table>
	
	
	<script>
		LoadAjax('settings-ou','$page?settings-ou=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function ou_save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$page=CurrentPageName();
	$tpl=new templates();	
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$ldap=new clladp();
	$ldap->AddOrganization($savedsettings["organization"]);
	$ldap->AddDomainEntity($savedsettings["organization"],$savedsettings["smtp_domainname"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?reconfigure-postfix=yes");
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
	$sock->getFrameWork("cyrus.php?service-cmds=restart");
	
	
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{organization}:{$savedsettings["organization"]}/{$savedsettings["smtp_domainname"]} {success}</strong>
			
		</td>
		</tr>
		</table>
	
	
	<script>
		LoadAjax('settings-final','$page?settings-final=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	 
	
}
function final_show(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$page=CurrentPageName();
	$tpl=new templates();	
	$ldap=new clladp();
	$user=$ldap->ldap_admin;
	$password=$ldap->ldap_password;
	$settings_final_show=$tpl->_ENGINE_parse_body("{settings_final_show}");
	$settings_final_show=str_replace("%a", "<strong>$user</strong>", $settings_final_show);
	$settings_final_show=str_replace("%p", "<strong>$password</strong>", $settings_final_show);
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'>
				<div style='font-size:18px'>$settings_final_show</strong>
				<center style='margin:10px'>". button("{close}","YahooSetupControlHide();document.location.href='logon.php'","22px")."</center>
		</td>
		</tr>
		</table>";
$sock=new sockets();
$sock->getFrameWork("services.php?register=yes");
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function checkDNSEmail($email) {
  if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$email)){
    list($username,$domain)=split('@',$email);
    if(!checkdnsrr($domain,'MX')) {
      return false;
    }
    return true;
  }
  return false;
  
}



function check_email_address($email) {
	$email=trim(strtolower($email));
	$banned["test@test.fr"]=true;
	$banned["tests@test.com"]=true;
	$banned["tests@test.fr"]=true;
	
	
	if($banned[$email]){return false;}
	
	$t=explode("@", $email);
	$lastpart=$t[1];
	$firstpart=$t[0];
	$falseDomains["toto"]=true;
	$falseDomains["tata"]=true;
	$falseDomains["coucocu"]=true;
	$falseDomains["coucou"]=true;
	$falseDomains["titi"]=true;
	$falseDomains["ici"]=true;
	$falseDomains["domains"]=true;
	$falseDomains["default"]=true;
	$falseDomains["myaddress"]=true;
	$falseDomains["mydomain"]=true;
	$falseDomains["demo"]=true;
	$falseDomains["test@"]=true;
	$falseDomains["tests@"]=true;
	$falseDomains["tests\."]=true;
	$falseDomains["test\."]=true;
	$falseDomains["contact@"]=true;
	$falseDomains["nn\.mm@"]=true;
	$falseDomains["nnn\.mmm@"]=true;
	$falseDomains["postmaster@"]=true;
	$falseDomains["root"]=true;
	$falseDomains["pippo"]=true;
	
	
	while (list ($index, $lines) = each ($falseDomains) ){
		if(preg_match("#$index#i", $lastpart)){return false;}
		if(preg_match("#$index#i", $firstpart)){return false;}
		
	}

	if(!checkDNSEmail($email)){return false;}
	
	
  // First, we check that there's one @ symbol, 
  // and that the lengths are right.
  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
    // Email invalid because wrong number of characters 
    // in one section or wrong number of @ symbols.
    return false;
  }
  // Split it into sections to make life easier
  $email_array = explode("@", $email);
  $local_array = explode(".", $email_array[0]);
  for ($i = 0; $i < sizeof($local_array); $i++) {
    if
(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
$local_array[$i])) {
      return false;
    }
  }
  // Check if domain is IP. If not, 
  // it should be valid domain name
  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
    $domain_array = explode(".", $email_array[1]);
    if (sizeof($domain_array) < 2) {
        return false; // Not enough parts to domain
    }
    for ($i = 0; $i < sizeof($domain_array); $i++) {
      if
(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",
$domain_array[$i])) {
        return false;
      }
    }
  }
  return true;
}

