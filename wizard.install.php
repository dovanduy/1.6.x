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
if(isset($_GET["setup-5"])){setup_5();exit;}
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
	$users=new usersMenus();
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$WizardSavedSettings=$sock->GET_INFO("WizardSavedSettings");
	if($users->SQUID_INSTALLED){
		if(is_file("ressources/templates/Squid/welcome-$DetectedLanguage.txt")){
			$WELCOME_WIZARD_2=@file_get_contents("ressources/templates/Squid/welcome-$DetectedLanguage.txt");
		}
		
	}
	
	
	
	$html="
	<input type='hidden' id='savedsettings' value=''>
	<div id='setup-content'>
	<div style='margin:10px;width:95%' class=form>
	<div style='font-size:22px;font-weight:bolder'>{WELCOME_ON_ARTICA_PROJECT}</div>
	<div style='margin:18px;font-size:14px'>{WELCOME_WIZARD_ARC1}$WELCOME_WIZARD_2</div>
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
			$arrayNameServers=GetNamesServers();
		
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
	
	if($netbiosname==null){
		$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
		if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}
		if(strpos($hostname, '.')>0){
			$Thostname=explode(".", $hostname);
			$netbiosname=$Thostname[0];
			unset($Thostname[0]);
			$domainname=@implode(".", $Thostname);
		}else{
			$netbiosname=$hostname;
		}
	}
	
	if($arrayNameServers[0]==null){
		$arrayNameServers=GetNamesServers();
	}
	
	if($users->SQUID_INSTALLED){
		
		$arrayPP["3128"]=3128;
		$arrayPP["8080"]=8080;
		$arrayPP["9090"]=9090;
		
		$proxy="
		<tr>
			<td colspan=2 style='padding-top:15px;padding-left:10px;border-right:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC'>
			<div style='font-size:22px;margin-bottom:10px;border-bottom:1px solid #CCCCCC;margin-right:20px'>{proxy_parameters}</div>
			<table style='width:55%'>
		
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' nowrap>{proxy_listen_port}:</td>
			<td>". Field_array_Hash($arrayPP,"proxy_listen_port",$savedsettings["proxy_listen_port"],null,null,0,"font-size:14px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' nowrap>{activate_webfiltering}:</td>
			<td>". Field_checkbox("EnableWebFiltering", 1,$savedsettings["EnableWebFiltering"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px;font-weight:bold' nowrap>{activate_streamcache}:</td>
			<td>". Field_checkbox("EnableYoutubeCache", 1,$savedsettings["EnableYoutubeCache"])."</td>
		</tr>					
		</table>
		</td>
		</tr>			
		";	
						

		if($users->SQUID_REVERSE_APPLIANCE){
			$proxy="<input type='hidden' id='proxy_listen_port' value='80' name='proxy_listen_port'>";
		}
	}
	
	if($users->POWER_DNS_INSTALLED){
		$pdns="	<tr>
		<td class=legend style='font-size:14px' nowrap>{activate_dns_service}:</td>
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
	$KEEPNET=$savedsettings["KEEPNET"];
	$nic=new system_nic("eth0");
	if($IPADDR==null){$IPADDR=$nic->IPADDR;}
	if($NETMASK==null){$NETMASK=$nic->NETMASK;}
	if($GATEWAY==null){$GATEWAY=$nic->GATEWAY;}
	if($BROADCAST==null){$BROADCAST=$nic->BROADCAST;}
	if($metric==null){$metric=$nic->metric;}
	if(!is_numeric($metric)){$metric=100;}
	if($metric<2){$metric=100;}
	$DISABLED=false;
	if(trim($arrayNameServers[1])==null){$arrayNameServers[1]="8.8.8.8";}
	if(!is_numeric($KEEPNET)){$KEEPNET=0;}
	

	
	$timezone=timezonearray();
	for($i=0;$i<count($timezone);$i++){
		$arrayTime[$timezone[$i]]=$timezone[$i];
	}
	
	$timezone_def=trim($sock->GET_INFO('timezones'));
	if($timezone_def==null){$timezone_def=getLocalTimezone();}
	
	$FORM="
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:30px;font-weight:bolder;margin-bottom:15px'>{serveretdom}</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px' nowrap>{timezone}:</td>
		<td valign='top'>".Field_array_Hash($arrayTime,"timezones",$timezone_def,null,null,"style:font-size:16px;padding:3px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px' nowrap>{netbiosname}:</td>
		<td>". Field_text("hostname_netbios",$netbiosname,"font-size:16px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:16px' nowrap>{DomainOfThisserver}:</td>
		<td>". Field_text("hostname_domain",$domainname,"font-size:16px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 style='font-size:30px;font-weight:bolder;padding-top:20px'>{network}</td>
	</tr>				
	<tr>
		<td colspan=2 style='font-size:12px;font-weight:bolder'>{network_settings_will_be_applied_after_reboot}</td>
	</tr>
		<tr>
			<td class=legend style='font-size:14px' nowrap>{keep_current_settings}:</td>
			<td>" . Field_checkbox("KEEPNET",1,$KEEPNET,'KeepNetCheck()')."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:14px' nowrap>{tcp_address}:</td>
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
		<td class=legend style='font-size:14px' nowrap>{primary_dns}:</td>
		<td>". field_ipv4("DNS1", $arrayNameServers[0],"padding:3px;font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{secondary_dns}:</td>
		<td>". field_ipv4("DNS2", $arrayNameServers[1],"padding:3px;font-size:18px")."</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:30px;font-weight:bolder'>{services}</div></td>
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
		
		function KeepNetCheck(){
			
			document.getElementById('hostname_netbios').disabled=false;
			document.getElementById('hostname_domain').disabled=false;
			document.getElementById('IPADDR').disabled=false;
			document.getElementById('NETMASK').disabled=false;
			document.getElementById('GATEWAY').disabled=false;
			document.getElementById('BROADCAST').disabled=false;
			document.getElementById('metric-$t').disabled=false;	
			document.getElementById('DNS1').disabled=false;
			document.getElementById('DNS2').disabled=false;
			
			
			if(document.getElementById('KEEPNET').checked){
				document.getElementById('IPADDR').disabled=true;
				document.getElementById('NETMASK').disabled=true;
				document.getElementById('GATEWAY').disabled=true;
				document.getElementById('BROADCAST').disabled=true;
				document.getElementById('metric-$t').disabled=true;	
				document.getElementById('DNS1').disabled=true;
				document.getElementById('DNS2').disabled=true;
				document.getElementById('hostname_netbios').disabled=true;
				document.getElementById('hostname_domain').disabled=true;				
			
			}
		
		}

		
		function ChangeQuickHostname(){
			KEEPNET=0;
			if(document.getElementById('KEEPNET').checked){KEEPNET=1;}
			var XHR = new XHRConnection();
			var netbios=document.getElementById('hostname_netbios').value;
			var dom=document.getElementById('hostname_domain').value;
			if(KEEPNET==0){
				if(netbios.length==0){alert('$netbiosname_field (Null!)');return;}
				if(dom.length==0){alert('$domain_field (Null!)');return;}
				if(dom=='localhost.localdomain'){alert('localhost.localdomain wrong domain...');return;}
			}
			
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
			
			if(document.getElementById('EnableYoutubeCache')){
				var EnableYoutubeCache=0;
				if(document.getElementById('EnableYoutubeCache').checked){EnableYoutubeCache=1;}
				XHR.appendData('EnableYoutubeCache',EnableYoutubeCache);
			}	

			if(document.getElementById('EnableWebFiltering')){
				var EnableWebFiltering=0;
				if(document.getElementById('EnableWebFiltering').checked){EnableWebFiltering=1;}
				XHR.appendData('EnableWebFiltering',EnableWebFiltering);
			}

			if(document.getElementById('timezones')){
				XHR.appendData('timezones',document.getElementById('timezones').value);
			}
			
			
			 
			XHR.appendData('KEEPNET',KEEPNET);
			if(KEEPNET==0){ 
				XHR.appendData('IPADDR',document.getElementById('IPADDR').value);
				XHR.appendData('NETMASK',document.getElementById('NETMASK').value);  
				XHR.appendData('GATEWAY',document.getElementById('GATEWAY').value);
				XHR.appendData('BROADCAST',document.getElementById('BROADCAST').value);
				XHR.appendData('metric',document.getElementById('metric-$t').value);          
				XHR.appendData('DNS1',document.getElementById('DNS1').value);
				XHR.appendData('DNS2',document.getElementById('DNS2').value);
				XHR.appendData('netbiosname',netbios);
				XHR.appendData('domain',dom);
			}
			
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			AnimateDiv('setup-content');
			XHR.sendAndLoad('$page', 'POST',X_ChangeQuickHostname);
			
		}
		KeepNetCheck();
	</script>
	
	";
	
	$html="
	<div style='font-size:35px;font-weight:bolder;margin-bottom:10px'>{squid_net_simple}</div>
	$FORM
	
	
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
	$KEEPNET=$savedsettings["KEEPNET"];
	if(!is_numeric($KEEPNET)){$KEEPNET=0;}
	$t=time();
	$UseServer[null]="{select}";
	$UseServer["ASMAIL"]="{mail_server}";
	$UseServer["ASRELAY"]="{relay_server}";
	$UseServer["ASFILE"]="{file_server}";
	$UseServer["ASPROXY"]="{proxy_server}";
	$UseServer["ASREVERSEPROXY"]="{reverse_proxy_server}";
	
	
	
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];	
	$page=CurrentPageName();
	$tpl=new templates();
	if($KEEPNET==0){
		$resolv=new resolv_conf();
		$resolv->MainArray["DNS1"]=$arrayNameServers[0];
		$resolv->MainArray["DNS2"]=$arrayNameServers[1];
		$resolv->save();
	}

	if($KEEPNET==0){
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

	if($users->SQUID_REVERSE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Reverse Proxy Appliance' >";
	}	
	
	
	
	
	
	
	//toujours à la fin...
	if($UseServerFF==null){
		if($users->FROM_SETUP){
			$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Installed from Setup'>";
		}
	}
	
	$company_name_txtjs=$tpl->javascript_parse_text("{company_name}");
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
		<td>". Field_text("smtp_domainname",$smtp_domainname,"font-size:16px;width:220px",null,null,null,false,"CheckMyForm$t(event)")."</td>
	</tr>	
	
	<tr>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings={$_GET["savedsettings"]}')","18px")."</div></td>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:right'>". button("{next}","ChangeCompanySettings()","18px")."</div></td>
	</tr>
	</table>
	<div style='font-size:11px;text-align:right'>{noticeregisterform}</div>
	<script>
		var X_ChangeCompanySettings= function (obj) {
			var results=obj.responseText;
			var KEEPNET=$KEEPNET;
			if(KEEPNET==0){
				LoadAjax('setup-content','$page?setup-4=yes&savedsettings='+results);
			}else{
				LoadAjax('setup-content','$page?setup-5=yes&savedsettings='+results);
			}
		
			}
		
			
		function CheckMyForm$t(e){
			if(!checkEnter(e)){return;}
		}
		
		function ChangeCompanySettings(){
			var XHR = XHRParseElements('$t');
			var testval=document.getElementById('company_name').value;
			if(testval.length==0){alert('$please_fill_all_form_values: $company_name_txtjs');return;}
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
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_POST["savedsettings"]));
	unset($_POST["savedsettings"]);
	while (list ($key, $value) = each ($_POST) ){
		$value=str_replace("___.___.___.___", "", $value);
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $value)){
			$pr=explode(".",$value);
			while (list ($index, $number) = each ($pr) ){$pr[$index]=intval($number);}
			$value=@implode(".", $pr);
		}
		
		$savedsettings[$key]=$value;
	}
	
	if(isset($_POST["timezones"])){$sock->SET_INFO("timezones",$_POST["timezones"]);}
	
	$savedsettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	$Encoded=base64_encode(serialize($savedsettings));
	$sock->SET_INFO("WizardSavedSettings", $Encoded);
	echo $Encoded;
	
	
}

function setup_4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	$memory=intval($sock->getFrameWork("services.php?total-memory=yes"));
	$WIZMEM=false;
	$wizard_warn_memory=$tpl->_ENGINE_parse_body("{wizard_warn_memory}");
	if($users->PROXYTINY_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}
	if($users->SAMBA_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}	
	if($users->SMTP_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->LOAD_BALANCE_APPLIANCE){
		if($memory<750){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "750M", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->LOAD_BALANCE_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->APACHE_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}	
	
	
	if(!$WIZMEM){
		if($memory<2450){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "2.5G", $wizard_warn_memory);
			$WIZMEM=true;
			
			$warn_memory="
			<div style='width:95%' class=form>
				<table style='width:100%'>
				<tr>
					<td valign='top' width=1%><img src='img/error-64.png'></td>
					<td style='font-size:16px'>$wizard_warn_memory</td>
				</tr>
				</table>
			</div>
			";
		}
	
	}
	if($savedsettings["adminwebserver"]==null){
		$domainname=$savedsettings["domain"];
		$savedsettings["adminwebserver"]="admin.$domainname";
	}
	
	if($savedsettings["second_webadmin"]==null){
		$savedsettings["second_webadmin"]=$savedsettings["IPADDR"];
	}
	
	$html="
	$warn_memory
	<div style='width:95%' class=form>
	<div style='font-size:18px;font-weight:bolder;margin-bottom:10px'>End-Users WebAccess</div>
		<p style='font-size:14px'>{miniadm_wizard_explain}</p>
		<p style='font-size:14px'>{miniadm_wizard_explain2}</p>
		<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:14px'>{webserver}</td>
					<td style='font-size:14px'>http://". Field_text("adminwebserver",$savedsettings["adminwebserver"],"font-size:14px;width:220px")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:14px'>{second_webadmin}</td>
					<td style='font-size:14px'>http://". Field_text("second_webadmin",$savedsettings["second_webadmin"],"font-size:14px;width:220px")."</td>
				</tr>							
							
							
				<td colspan=2><div style='font-size:22px;font-weight:bolder;margin-bottom:10px'>{administrator}:</div>
				<tr>
					<td class=legend style='font-size:14px'>{username}:</td>
					<td style='font-size:14px'>". Field_text("administrator",$savedsettings["administrator"],"font-size:14px;width:150px")."</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:14px'>{password}:</td>
					<td style='font-size:14px'>". Field_password("administratorpass",$savedsettings["administratorpass"],"font-size:14px;width:150px")."</td>
				</tr>	
					<td colspan=2><div style='font-size:22px;font-weight:bolder;margin-bottom:10px'>{statistics_administrator}:</div>
				<tr>
					<td class=legend style='font-size:14px'>{username}:</td>
					<td style='font-size:14px'>". Field_text("statsadministrator",$savedsettings["statsadministrator"],"font-size:14px;width:150px")."</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:14px'>{password}:</td>
					<td style='font-size:14px'>". Field_password("statsadministratorpass",$savedsettings["statsadministratorpass"],"font-size:14px;width:150px")."</td>
				</tr>	
				<tr>
					<td style='font-size:14px;font-weight:bolder'><div style='text-align:left'>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","18px")."<div></td>
					<td style='font-size:14px;font-weight:bolder'><div style='text-align:right'>". button("{build_parameters}","ChangeWebAccess()","18px")."</div></td>
				</tr>							
		</table>
	</div>
	<script>
		var XChangeWebAccess= function (obj) {
			var results=obj.responseText;
			LoadAjax('setup-content','$page?setup-5=yes&savedsettings='+results);
		
			}
		
		
		function ChangeWebAccess(){
			var XHR = new XHRConnection();
			XHR.appendData('adminwebserver',document.getElementById('adminwebserver').value);
			XHR.appendData('second_webadmin',document.getElementById('second_webadmin').value);
			XHR.appendData('administrator',document.getElementById('administrator').value);
			XHR.appendData('statsadministrator',document.getElementById('statsadministrator').value);
			var statsadministratorpass=encodeURIComponent(document.getElementById('statsadministratorpass').value);
			var administratorpass=encodeURIComponent(document.getElementById('administratorpass').value);
			XHR.appendData('administratorpass',administratorpass);
			XHR.appendData('statsadministratorpass',statsadministratorpass);
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			XHR.sendAndLoad('$page', 'POST',XChangeWebAccess);
			
		}
	
	</script>
			
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function setup_5(){
	//finalisation des paramètres et FIN.
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
		
	
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
			<div style='text-align:right;margin-top:20px;font-size:22px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:LoadAjax('setup-content','$page?setup-5=yes&bypass=yes&savedsettings={$_GET["savedsettings"]}');\"
			style='font-size:22px;text-decoration:underline'>{i_understand_continue}...</a>
			</div>						
						
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
	
	$KEEPNET=$savedsettings["KEEPNET"];
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];
	$page=CurrentPageName();
	$tpl=new templates();	
	if($KEEPNET==0){
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
	}	


	
	
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
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$webinterf=array();
	$webinterf[]="<hr>";
	if($savedsettings["adminwebserver"]<>null){
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["adminwebserver"]}</div>";
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> https://{$savedsettings["IPADDR"]}:9000/miniadm.logon.php</div>";
	}
	if($savedsettings["second_webadmin"]<>null){
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["second_webadmin"]}</div>";
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["second_webadmin"]}/miniadm.logon.php</div>";
	}		
		
	if($savedsettings["adminwebserver"]<>null){	
		if($savedsettings["administrator"]<>null){
			$webinterf[]="<div style='font-size:18px'><strong>WebAccess {username}:</strong>{$savedsettings["administrator"]}</div>";
		}
		if($savedsettings["statsadministrator"]<>null){
			$webinterf[]="<div style='font-size:18px'><strong>WebAccess {username} ({statistics}):</strong>{$savedsettings["statsadministrator"]}</div>";
		}		
	}
	
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'>
				<div style='font-size:18px'>$settings_final_show</strong>
				".@implode("\n", $webinterf)."
				<center style='margin:10px'>". button("{close}","YahooSetupControlHide();document.location.href='logon.php'","22px")."</center>
		</td>
		</tr>
		</table>";
	$sock=new sockets();
	$sock->getFrameWork("system.php?wizard-execute=yes");
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
	$banned["lol@lol.com"]=true;
	
	
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
	$falseDomains["lol.com"]=true;
	$falseDomains["asfsdf"]=true;
	
	
	while (list ($index, $lines) = each ($falseDomains) ){
		if(preg_match("#$index#i", $lastpart)){return false;}
		if(preg_match("#$index#i", $firstpart)){return false;}
		
	}

	return true;
	
	
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



function GetNamesServers(){
	
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
	return $arrayNameServers;
}


function timezonearray(){

	$timezone[]="Africa/Abidjan";                 //,0x000000 },
	$timezone[]="Africa/Accra";                   //,0x000055 },
	$timezone[]="Africa/Addis_Ababa";             //,0x0000FD },
	$timezone[]="Africa/Algiers";                 //,0x000153 },
	$timezone[]="Africa/Asmara";                  //,0x00027E },
	$timezone[]="Africa/Asmera";                  //,0x0002D4 },
	$timezone[]="Africa/Bamako";                  //,0x00032A },
	$timezone[]="Africa/Bangui";                  //,0x000395 },
	$timezone[]="Africa/Banjul";                  //,0x0003EA },
	$timezone[]="Africa/Bissau";                  //,0x000461 },
	$timezone[]="Africa/Blantyre";                //,0x0004C7 },
	$timezone[]="Africa/Brazzaville";             //,0x00051C },
	$timezone[]="Africa/Bujumbura";               //,0x000571 },
	$timezone[]="Africa/Cairo";                   //,0x0005B5 },
	$timezone[]="Africa/Casablanca";              //,0x00097C },
	$timezone[]="Africa/Ceuta";                   //,0x000A58 },
	$timezone[]="Africa/Conakry";                 //,0x000D5F },
	$timezone[]="Africa/Dakar";                   //,0x000DCA },
	$timezone[]="Africa/Dar_es_Salaam";           //,0x000E30 },
	$timezone[]="Africa/Djibouti";                //,0x000E9D },
	$timezone[]="Africa/Douala";                  //,0x000EF2 },
	$timezone[]="Africa/El_Aaiun";                //,0x000F47 },
	$timezone[]="Africa/Freetown";                //,0x000FAD },
	$timezone[]="Africa/Gaborone";                //,0x0010BC },
	$timezone[]="Africa/Harare";                  //,0x001117 },
	$timezone[]="Africa/Johannesburg";            //,0x00116C },
	$timezone[]="Africa/Kampala";                 //,0x0011DA },
	$timezone[]="Africa/Khartoum";                //,0x001259 },
	$timezone[]="Africa/Kigali";                  //,0x00136C },
	$timezone[]="Africa/Kinshasa";                //,0x0013C1 },
	$timezone[]="Africa/Lagos";                   //,0x00141C },
	$timezone[]="Africa/Libreville";              //,0x001471 },
	$timezone[]="Africa/Lome";                    //,0x0014C6 },
	$timezone[]="Africa/Luanda";                  //,0x00150A },
	$timezone[]="Africa/Lubumbashi";              //,0x00155F },
	$timezone[]="Africa/Lusaka";                  //,0x0015BA },
	$timezone[]="Africa/Malabo";                  //,0x00160F },
	$timezone[]="Africa/Maputo";                  //,0x001675 },
	$timezone[]="Africa/Maseru";                  //,0x0016CA },
	$timezone[]="Africa/Mbabane";                 //,0x001732 },
	$timezone[]="Africa/Mogadishu";               //,0x001788 },
	$timezone[]="Africa/Monrovia";                //,0x0017E3 },
	$timezone[]="Africa/Nairobi";                 //,0x001849 },
	$timezone[]="Africa/Ndjamena";                //,0x0018C8 },
	$timezone[]="Africa/Niamey";                  //,0x001934 },
	$timezone[]="Africa/Nouakchott";              //,0x0019A7 },
	$timezone[]="Africa/Ouagadougou";             //,0x001A12 },
	$timezone[]="Africa/Porto-Novo";              //,0x001A67 },
	$timezone[]="Africa/Sao_Tome";                //,0x001ACD },
	$timezone[]="Africa/Timbuktu";                //,0x001B22 },
	$timezone[]="Africa/Tripoli";                 //,0x001B8D },
	$timezone[]="Africa/Tunis";                   //,0x001C87 },
	$timezone[]="Africa/Windhoek";                //,0x001EB1 },
	$timezone[]="America/Adak";                   //,0x0020F8 },
	$timezone[]="America/Anchorage";              //,0x00246E },
	$timezone[]="America/Anguilla";               //,0x0027E2 },
	$timezone[]="America/Antigua";                //,0x002837 },
	$timezone[]="America/Araguaina";              //,0x00289D },
	$timezone[]="America/Argentina/Buenos_Aires"; //,0x0029F8 },
	$timezone[]="America/Argentina/Catamarca";    //,0x002BA6 },
	$timezone[]="America/Argentina/ComodRivadavia";  //,0x002D67 },
	$timezone[]="America/Argentina/Cordoba";      //,0x002F0D },
	$timezone[]="America/Argentina/Jujuy";        //,0x0030E2 },
	$timezone[]="America/Argentina/La_Rioja";     //,0x003296 },
	$timezone[]="America/Argentina/Mendoza";      //,0x00344E },
	$timezone[]="America/Argentina/Rio_Gallegos"; //,0x00360E },
	$timezone[]="America/Argentina/Salta";        //,0x0037C3 },
	$timezone[]="America/Argentina/San_Juan";     //,0x00396F },
	$timezone[]="America/Argentina/San_Luis";     //,0x003B27 },
	$timezone[]="America/Argentina/Tucuman";      //,0x003E05 },
	$timezone[]="America/Argentina/Ushuaia";      //,0x003FC1 },
	$timezone[]="America/Aruba";                  //,0x00417C },
	$timezone[]="America/Asuncion";               //,0x0041E2 },
	$timezone[]="America/Atikokan";               //,0x0044C7 },
	$timezone[]="America/Atka";                   //,0x00459D },
	$timezone[]="America/Bahia";                  //,0x004903 },
	$timezone[]="America/Barbados";               //,0x004A8C },
	$timezone[]="America/Belem";                  //,0x004B26 },
	$timezone[]="America/Belize";                 //,0x004C21 },
	$timezone[]="America/Blanc-Sablon";           //,0x004D9D },
	$timezone[]="America/Boa_Vista";              //,0x004E51 },
	$timezone[]="America/Bogota";                 //,0x004F5A },
	$timezone[]="America/Boise";                  //,0x004FC6 },
	$timezone[]="America/Buenos_Aires";           //,0x00535D },
	$timezone[]="America/Cambridge_Bay";          //,0x0054F6 },
	$timezone[]="America/Campo_Grande";           //,0x00581E },
	$timezone[]="America/Cancun";                 //,0x005B0D },
	$timezone[]="America/Caracas";                //,0x005D4F },
	$timezone[]="America/Catamarca";              //,0x005DB6 },
	$timezone[]="America/Cayenne";                //,0x005F5C },
	$timezone[]="America/Cayman";                 //,0x005FBE },
	$timezone[]="America/Chicago";                //,0x006013 },
	$timezone[]="America/Chihuahua";              //,0x00652A },
	$timezone[]="America/Coral_Harbour";          //,0x006779 },
	$timezone[]="America/Cordoba";                //,0x00680B },
	$timezone[]="America/Costa_Rica";             //,0x0069B1 },
	$timezone[]="America/Cuiaba";                 //,0x006A3B },
	$timezone[]="America/Curacao";                //,0x006D19 },
	$timezone[]="America/Danmarkshavn";           //,0x006D7F },
	$timezone[]="America/Dawson";                 //,0x006EC3 },
	$timezone[]="America/Dawson_Creek";           //,0x0071E0 },
	$timezone[]="America/Denver";                 //,0x0073BA },
	$timezone[]="America/Detroit";                //,0x007740 },
	$timezone[]="America/Dominica";               //,0x007A9F },
	$timezone[]="America/Edmonton";               //,0x007AF4 },
	$timezone[]="America/Eirunepe";               //,0x007EAC },
	$timezone[]="America/El_Salvador";            //,0x007FBF },
	$timezone[]="America/Ensenada";               //,0x008034 },
	$timezone[]="America/Fort_Wayne";             //,0x0084DB },
	$timezone[]="America/Fortaleza";              //,0x00839D },
	$timezone[]="America/Glace_Bay";              //,0x008745 },
	$timezone[]="America/Godthab";                //,0x008ABC },
	$timezone[]="America/Goose_Bay";              //,0x008D80 },
	$timezone[]="America/Grand_Turk";             //,0x00923D },
	$timezone[]="America/Grenada";                //,0x0094EC },
	$timezone[]="America/Guadeloupe";             //,0x009541 },
	$timezone[]="America/Guatemala";              //,0x009596 },
	$timezone[]="America/Guayaquil";              //,0x00961F },
	$timezone[]="America/Guyana";                 //,0x00967C },
	$timezone[]="America/Halifax";                //,0x0096FD },
	$timezone[]="America/Havana";                 //,0x009C13 },
	$timezone[]="America/Hermosillo";             //,0x009F86 },
	$timezone[]="America/Indiana/Indianapolis";   //,0x00A064 },
	$timezone[]="America/Indiana/Knox";           //,0x00A2F5 },
	$timezone[]="America/Indiana/Marengo";        //,0x00A68C },
	$timezone[]="America/Indiana/Petersburg";     //,0x00A932 },
	$timezone[]="America/Indiana/Tell_City";      //,0x00AE7F },
	$timezone[]="America/Indiana/Vevay";          //,0x00B118 },
	$timezone[]="America/Indiana/Vincennes";      //,0x00B353 },
	$timezone[]="America/Indiana/Winamac";        //,0x00B607 },
	$timezone[]="America/Indianapolis";           //,0x00AC15 },
	$timezone[]="America/Inuvik";                 //,0x00B8C0 },
	$timezone[]="America/Iqaluit";                //,0x00BBB7 },
	$timezone[]="America/Jamaica";                //,0x00BED9 },
	$timezone[]="America/Jujuy";                  //,0x00BF9E },
	$timezone[]="America/Juneau";                 //,0x00C148 },
	$timezone[]="America/Kentucky/Louisville";    //,0x00C4C6 },
	$timezone[]="America/Kentucky/Monticello";    //,0x00C8E4 },
	$timezone[]="America/Knox_IN";                //,0x00CC69 },
	$timezone[]="America/La_Paz";                 //,0x00CFDA },
	$timezone[]="America/Lima";                   //,0x00D041 },
	$timezone[]="America/Los_Angeles";            //,0x00D0E9 },
	$timezone[]="America/Louisville";             //,0x00D4FA },
	$timezone[]="America/Maceio";                 //,0x00D8EF },
	$timezone[]="America/Managua";                //,0x00DA29 },
	$timezone[]="America/Manaus";                 //,0x00DADC },
	$timezone[]="America/Marigot";                //,0x00DBDE },
	$timezone[]="America/Martinique";             //,0x00DC33 },
	$timezone[]="America/Mazatlan";               //,0x00DC9F },
	$timezone[]="America/Mendoza";                //,0x00DF0C },
	$timezone[]="America/Menominee";              //,0x00E0C0 },
	$timezone[]="America/Merida";                 //,0x00E441 },
	$timezone[]="America/Mexico_City";            //,0x00E67C },
	$timezone[]="America/Miquelon";               //,0x00E8F7 },
	$timezone[]="America/Moncton";                //,0x00EB69 },
	$timezone[]="America/Monterrey";              //,0x00F000 },
	$timezone[]="America/Montevideo";             //,0x00F247 },
	$timezone[]="America/Montreal";               //,0x00F559 },
	$timezone[]="America/Montserrat";             //,0x00FA6F },
	$timezone[]="America/Nassau";                 //,0x00FAC4 },
	$timezone[]="America/New_York";               //,0x00FE09 },
	$timezone[]="America/Nipigon";                //,0x010314 },
	$timezone[]="America/Nome";                   //,0x010665 },
	$timezone[]="America/Noronha";                //,0x0109E3 },
	$timezone[]="America/North_Dakota/Center";    //,0x010B13 },
	$timezone[]="America/North_Dakota/New_Salem"; //,0x010EA7 },
	$timezone[]="America/Panama";                 //,0x011250 },
	$timezone[]="America/Pangnirtung";            //,0x0112A5 },
	$timezone[]="America/Paramaribo";             //,0x0115DB },
	$timezone[]="America/Phoenix";                //,0x01166D },
	$timezone[]="America/Port-au-Prince";         //,0x01171B },
	$timezone[]="America/Port_of_Spain";          //,0x011936 },
	$timezone[]="America/Porto_Acre";             //,0x011837 },
	$timezone[]="America/Porto_Velho";            //,0x01198B },
	$timezone[]="America/Puerto_Rico";            //,0x011A81 },
	$timezone[]="America/Rainy_River";            //,0x011AEC },
	$timezone[]="America/Rankin_Inlet";           //,0x011E24 },
	$timezone[]="America/Recife";                 //,0x01210A },
	$timezone[]="America/Regina";                 //,0x012234 },
	$timezone[]="America/Resolute";               //,0x0123F2 },
	$timezone[]="America/Rio_Branco";             //,0x0126EB },
	$timezone[]="America/Rosario";                //,0x0127EE },
	$timezone[]="America/Santarem";               //,0x012994 },
	$timezone[]="America/Santiago";               //,0x012A99 },
	$timezone[]="America/Santo_Domingo";          //,0x012E42 },
	$timezone[]="America/Sao_Paulo";              //,0x012F08 },
	$timezone[]="America/Scoresbysund";           //,0x013217 },
	$timezone[]="America/Shiprock";               //,0x013505 },
	$timezone[]="America/St_Barthelemy";          //,0x013894 },
	$timezone[]="America/St_Johns";               //,0x0138E9 },
	$timezone[]="America/St_Kitts";               //,0x013E3C },
	$timezone[]="America/St_Lucia";               //,0x013E91 },
	$timezone[]="America/St_Thomas";              //,0x013EE6 },
	$timezone[]="America/St_Vincent";             //,0x013F3B },
	$timezone[]="America/Swift_Current";          //,0x013F90 },
	$timezone[]="America/Tegucigalpa";            //,0x0140B1 },
	$timezone[]="America/Thule";                  //,0x014130 },
	$timezone[]="America/Thunder_Bay";            //,0x014377 },
	$timezone[]="America/Tijuana";                //,0x0146C0 },
	$timezone[]="America/Toronto";                //,0x014A35 },
	$timezone[]="America/Tortola";                //,0x014F4C },
	$timezone[]="America/Vancouver";              //,0x014FA1 },
	$timezone[]="America/Virgin";                 //,0x0153DE },
	$timezone[]="America/Whitehorse";             //,0x015433 },
	$timezone[]="America/Winnipeg";               //,0x015750 },
	$timezone[]="America/Yakutat";                //,0x015B90 },
	$timezone[]="America/Yellowknife";            //,0x015EFB },
	$timezone[]="Antarctica/Casey";               //,0x01620B },
	$timezone[]="Antarctica/Davis";               //,0x016291 },
	$timezone[]="Antarctica/DumontDUrville";      //,0x01631B },
	$timezone[]="Antarctica/Mawson";              //,0x0163AD },
	$timezone[]="Antarctica/McMurdo";             //,0x016429 },
	$timezone[]="Antarctica/Palmer";              //,0x01672B },
	$timezone[]="Antarctica/Rothera";             //,0x016A47 },
	$timezone[]="Antarctica/South_Pole";          //,0x016ABD },
	$timezone[]="Antarctica/Syowa";               //,0x016DC5 },
	$timezone[]="Antarctica/Vostok";              //,0x016E33 },
	$timezone[]="Arctic/Longyearbyen";            //,0x016EA8 },
	$timezone[]="Asia/Aden";                      //,0x0171DA },
	$timezone[]="Asia/Almaty";                    //,0x01722F },
	$timezone[]="Asia/Amman";                     //,0x0173AE },
	$timezone[]="Asia/Anadyr";                    //,0x01766E },
	$timezone[]="Asia/Aqtau";                     //,0x01795C },
	$timezone[]="Asia/Aqtobe";                    //,0x017B5B },
	$timezone[]="Asia/Ashgabat";                  //,0x017D13 },
	$timezone[]="Asia/Ashkhabad";                 //,0x017E30 },
	$timezone[]="Asia/Baghdad";                   //,0x017F4D },
	$timezone[]="Asia/Bahrain";                   //,0x0180C2 },
	$timezone[]="Asia/Baku";                      //,0x018128 },
	$timezone[]="Asia/Bangkok";                   //,0x018410 },
	$timezone[]="Asia/Beirut";                    //,0x018465 },
	$timezone[]="Asia/Bishkek";                   //,0x018772 },
	$timezone[]="Asia/Brunei";                    //,0x01891E },
	$timezone[]="Asia/Calcutta";                  //,0x018980 },
	$timezone[]="Asia/Choibalsan";                //,0x0189F9 },
	$timezone[]="Asia/Chongqing";                 //,0x018B72 },
	$timezone[]="Asia/Chungking";                 //,0x018C61 },
	$timezone[]="Asia/Colombo";                   //,0x018D10 },
	$timezone[]="Asia/Dacca";                     //,0x018DAC },
	$timezone[]="Asia/Damascus";                  //,0x018E4D },
	$timezone[]="Asia/Dhaka";                     //,0x01919D },
	$timezone[]="Asia/Dili";                      //,0x01923E },
	$timezone[]="Asia/Dubai";                     //,0x0192C7 },
	$timezone[]="Asia/Dushanbe";                  //,0x01931C },
	$timezone[]="Asia/Gaza";                      //,0x01941F },
	$timezone[]="Asia/Harbin";                    //,0x019768 },
	$timezone[]="Asia/Ho_Chi_Minh";               //,0x01984F },
	$timezone[]="Asia/Hong_Kong";                 //,0x0198C7 },
	$timezone[]="Asia/Hovd";                      //,0x019A93 },
	$timezone[]="Asia/Irkutsk";                   //,0x019C0B },
	$timezone[]="Asia/Istanbul";                  //,0x019EF2 },
	$timezone[]="Asia/Jakarta";                   //,0x01A2DF },
	$timezone[]="Asia/Jayapura";                  //,0x01A389 },
	$timezone[]="Asia/Jerusalem";                 //,0x01A40D },
	$timezone[]="Asia/Kabul";                     //,0x01A73C },
	$timezone[]="Asia/Kamchatka";                 //,0x01A78D },
	$timezone[]="Asia/Karachi";                   //,0x01AA72 },
	$timezone[]="Asia/Kashgar";                   //,0x01AC3F },
	$timezone[]="Asia/Kathmandu";                 //,0x01AD10 },
	$timezone[]="Asia/Katmandu";                  //,0x01AD76 },
	$timezone[]="Asia/Kolkata";                   //,0x01ADDC },
	$timezone[]="Asia/Krasnoyarsk";               //,0x01AE55 },
	$timezone[]="Asia/Kuala_Lumpur";              //,0x01B13E },
	$timezone[]="Asia/Kuching";                   //,0x01B1FB },
	$timezone[]="Asia/Kuwait";                    //,0x01B2E9 },
	$timezone[]="Asia/Macao";                     //,0x01B33E },
	$timezone[]="Asia/Macau";                     //,0x01B479 },
	$timezone[]="Asia/Magadan";                   //,0x01B5B4 },
	$timezone[]="Asia/Makassar";                  //,0x01B897 },
	$timezone[]="Asia/Manila";                    //,0x01B950 },
	$timezone[]="Asia/Muscat";                    //,0x01B9D5 },
	$timezone[]="Asia/Nicosia";                   //,0x01BA2A },
	$timezone[]="Asia/Novokuznetsk";              //,0x01BD12 },
	$timezone[]="Asia/Novosibirsk";               //,0x01C015 },
	$timezone[]="Asia/Omsk";                      //,0x01C309 },
	$timezone[]="Asia/Oral";                      //,0x01C5F1 },
	$timezone[]="Asia/Phnom_Penh";                //,0x01C7C1 },
	$timezone[]="Asia/Pontianak";                 //,0x01C839 },
	$timezone[]="Asia/Pyongyang";                 //,0x01C8FA },
	$timezone[]="Asia/Qatar";                     //,0x01C967 },
	$timezone[]="Asia/Qyzylorda";                 //,0x01C9CD },
	$timezone[]="Asia/Rangoon";                   //,0x01CBA3 },
	$timezone[]="Asia/Riyadh";                    //,0x01CC1B },
	$timezone[]="Asia/Saigon";                    //,0x01CC70 },
	$timezone[]="Asia/Sakhalin";                  //,0x01CCE8 },
	$timezone[]="Asia/Samarkand";                 //,0x01CFE8 },
	$timezone[]="Asia/Seoul";                     //,0x01D11E },
	$timezone[]="Asia/Shanghai";                  //,0x01D1C2 },
	$timezone[]="Asia/Singapore";                 //,0x01D2A2 },
	$timezone[]="Asia/Taipei";                    //,0x01D359 },
	$timezone[]="Asia/Tashkent";                  //,0x01D471 },
	$timezone[]="Asia/Tbilisi";                   //,0x01D5A2 },
	$timezone[]="Asia/Tehran";                    //,0x01D75C },
	$timezone[]="Asia/Tel_Aviv";                  //,0x01D9CA },
	$timezone[]="Asia/Thimbu";                    //,0x01DCF9 },
	$timezone[]="Asia/Thimphu";                   //,0x01DD5F },
	$timezone[]="Asia/Tokyo";                     //,0x01DDC5 },
	$timezone[]="Asia/Ujung_Pandang";             //,0x01DE4E },
	$timezone[]="Asia/Ulaanbaatar";               //,0x01DECA },
	$timezone[]="Asia/Ulan_Bator";                //,0x01E025 },
	$timezone[]="Asia/Urumqi";                    //,0x01E172 },
	$timezone[]="Asia/Vientiane";                 //,0x01E239 },
	$timezone[]="Asia/Vladivostok";               //,0x01E2B1 },
	$timezone[]="Asia/Yakutsk";                   //,0x01E59E },
	$timezone[]="Asia/Yekaterinburg";             //,0x01E884 },
	$timezone[]="Asia/Yerevan";                   //,0x01EB90 },
	$timezone[]="Atlantic/Azores";                //,0x01EE94 },
	$timezone[]="Atlantic/Bermuda";               //,0x01F397 },
	$timezone[]="Atlantic/Canary";                //,0x01F678 },
	$timezone[]="Atlantic/Cape_Verde";            //,0x01F94E },
	$timezone[]="Atlantic/Faeroe";                //,0x01F9C7 },
	$timezone[]="Atlantic/Faroe";                 //,0x01FC6B },
	$timezone[]="Atlantic/Jan_Mayen";             //,0x01FF0F },
	$timezone[]="Atlantic/Madeira";               //,0x020241 },
	$timezone[]="Atlantic/Reykjavik";             //,0x02074A },
	$timezone[]="Atlantic/South_Georgia";         //,0x020903 },
	$timezone[]="Atlantic/St_Helena";             //,0x020C1B },
	$timezone[]="Atlantic/Stanley";               //,0x020947 },
	$timezone[]="Australia/ACT";                  //,0x020C70 },
	$timezone[]="Australia/Adelaide";             //,0x020F8D },
	$timezone[]="Australia/Brisbane";             //,0x0212B9 },
	$timezone[]="Australia/Broken_Hill";          //,0x021380 },
	$timezone[]="Australia/Canberra";             //,0x0216BE },
	$timezone[]="Australia/Currie";               //,0x0219DB },
	$timezone[]="Australia/Darwin";               //,0x021D0E },
	$timezone[]="Australia/Eucla";                //,0x021D94 },
	$timezone[]="Australia/Hobart";               //,0x021E69 },
	$timezone[]="Australia/LHI";                  //,0x0221C7 },
	$timezone[]="Australia/Lindeman";             //,0x022462 },
	$timezone[]="Australia/Lord_Howe";            //,0x022543 },
	$timezone[]="Australia/Melbourne";            //,0x0227EE },
	$timezone[]="Australia/North";                //,0x022B13 },
	$timezone[]="Australia/NSW";                  //,0x022B87 },
	$timezone[]="Australia/Perth";                //,0x022EA4 },
	$timezone[]="Australia/Queensland";           //,0x022F7C },
	$timezone[]="Australia/South";                //,0x023028 },
	$timezone[]="Australia/Sydney";               //,0x023345 },
	$timezone[]="Australia/Tasmania";             //,0x023682 },
	$timezone[]="Australia/Victoria";             //,0x0239C7 },
	$timezone[]="Australia/West";                 //,0x023CE4 },
	$timezone[]="Australia/Yancowinna";           //,0x023D9A },
	$timezone[]="Brazil/Acre";                    //,0x0240BC },
	$timezone[]="Brazil/DeNoronha";               //,0x0241BB },
	$timezone[]="Brazil/East";                    //,0x0242DB },
	$timezone[]="Brazil/West";                    //,0x0245B8 },
	$timezone[]="Canada/Atlantic";                //,0x0246B0 },
	$timezone[]="Canada/Central";                 //,0x024B98 },
	$timezone[]="Canada/East-Saskatchewan";       //,0x0254A2 },
	$timezone[]="Canada/Eastern";                 //,0x024FB2 },
	$timezone[]="Canada/Mountain";                //,0x02562B },
	$timezone[]="Canada/Newfoundland";            //,0x0259A1 },
	$timezone[]="Canada/Pacific";                 //,0x025ECC },
	$timezone[]="Canada/Saskatchewan";            //,0x0262E5 },
	$timezone[]="Canada/Yukon";                   //,0x02646E },
	$timezone[]="CET";                            //,0x026771 },
	$timezone[]="Chile/Continental";              //,0x026A7A },
	$timezone[]="Chile/EasterIsland";             //,0x026E15 },
	$timezone[]="CST6CDT";                        //,0x027157 },
	$timezone[]="Cuba";                           //,0x0274A8 },
	$timezone[]="EET";                            //,0x02781B },
	$timezone[]="Egypt";                          //,0x027ACE },
	$timezone[]="Eire";                           //,0x027E95 },
	$timezone[]="EST";                            //,0x0283A6 },
	$timezone[]="EST5EDT";                        //,0x0283EA },
	$timezone[]="Etc/GMT";                        //,0x02873B },
	$timezone[]="Etc/GMT+0";                      //,0x028807 },
	$timezone[]="Etc/GMT+1";                      //,0x028891 },
	$timezone[]="Etc/GMT+10";                     //,0x02891E },
	$timezone[]="Etc/GMT+11";                     //,0x0289AC },
	$timezone[]="Etc/GMT+12";                     //,0x028A3A },
	$timezone[]="Etc/GMT+2";                      //,0x028B55 },
	$timezone[]="Etc/GMT+3";                      //,0x028BE1 },
	$timezone[]="Etc/GMT+4";                      //,0x028C6D },
	$timezone[]="Etc/GMT+5";                      //,0x028CF9 },
	$timezone[]="Etc/GMT+6";                      //,0x028D85 },
	$timezone[]="Etc/GMT+7";                      //,0x028E11 },
	$timezone[]="Etc/GMT+8";                      //,0x028E9D },
	$timezone[]="Etc/GMT+9";                      //,0x028F29 },
	$timezone[]="Etc/GMT-0";                      //,0x0287C3 },
	$timezone[]="Etc/GMT-1";                      //,0x02884B },
	$timezone[]="Etc/GMT-10";                     //,0x0288D7 },
	$timezone[]="Etc/GMT-11";                     //,0x028965 },
	$timezone[]="Etc/GMT-12";                     //,0x0289F3 },
	$timezone[]="Etc/GMT-13";                     //,0x028A81 },
	$timezone[]="Etc/GMT-14";                     //,0x028AC8 },
	$timezone[]="Etc/GMT-2";                      //,0x028B0F },
	$timezone[]="Etc/GMT-3";                      //,0x028B9B },
	$timezone[]="Etc/GMT-4";                      //,0x028C27 },
	$timezone[]="Etc/GMT-5";                      //,0x028CB3 },
	$timezone[]="Etc/GMT-6";                      //,0x028D3F },
	$timezone[]="Etc/GMT-7";                      //,0x028DCB },
	$timezone[]="Etc/GMT-8";                      //,0x028E57 },
	$timezone[]="Etc/GMT-9";                      //,0x028EE3 },
	$timezone[]="Etc/GMT0";                       //,0x02877F },
	$timezone[]="Etc/Greenwich";                  //,0x028F6F },
	$timezone[]="Etc/UCT";                        //,0x028FB3 },
	$timezone[]="Etc/Universal";                  //,0x028FF7 },
	$timezone[]="Etc/UTC";                        //,0x02903B },
	$timezone[]="Etc/Zulu";                       //,0x02907F },
	$timezone[]="Europe/Amsterdam";               //,0x0290C3 },
	$timezone[]="Europe/Andorra";                 //,0x029501 },
	$timezone[]="Europe/Athens";                  //,0x02977D },
	$timezone[]="Europe/Belfast";                 //,0x029AC0 },
	$timezone[]="Europe/Belgrade";                //,0x029FF7 },
	$timezone[]="Europe/Berlin";                  //,0x02A2C0 },
	$timezone[]="Europe/Bratislava";              //,0x02A616 },
	$timezone[]="Europe/Brussels";                //,0x02A948 },
	$timezone[]="Europe/Bucharest";               //,0x02AD7F },
	$timezone[]="Europe/Budapest";                //,0x02B0A9 },
	$timezone[]="Europe/Chisinau";                //,0x02B41C },
	$timezone[]="Europe/Copenhagen";              //,0x02B7AA },
	$timezone[]="Europe/Dublin";                  //,0x02BAB4 },
	$timezone[]="Europe/Gibraltar";               //,0x02BFC5 },
	$timezone[]="Europe/Guernsey";                //,0x02C41C },
	$timezone[]="Europe/Helsinki";                //,0x02C953 },
	$timezone[]="Europe/Isle_of_Man";             //,0x02CC09 },
	$timezone[]="Europe/Istanbul";                //,0x02D140 },
	$timezone[]="Europe/Jersey";                  //,0x02D52D },
	$timezone[]="Europe/Kaliningrad";             //,0x02DA64 },
	$timezone[]="Europe/Kiev";                    //,0x02DDC7 },
	$timezone[]="Europe/Lisbon";                  //,0x02E0DE },
	$timezone[]="Europe/Ljubljana";               //,0x02E5E2 },
	$timezone[]="Europe/London";                  //,0x02E8AB },
	$timezone[]="Europe/Luxembourg";              //,0x02EDE2 },
	$timezone[]="Europe/Madrid";                  //,0x02F238 },
	$timezone[]="Europe/Malta";                   //,0x02F5FE },
	$timezone[]="Europe/Mariehamn";               //,0x02F9B7 },
	$timezone[]="Europe/Minsk";                   //,0x02FC6D },
	$timezone[]="Europe/Monaco";                  //,0x02FF78 },
	$timezone[]="Europe/Moscow";                  //,0x0303B3 },
	$timezone[]="Europe/Nicosia";                 //,0x030705 },
	$timezone[]="Europe/Oslo";                    //,0x0309ED },
	$timezone[]="Europe/Paris";                   //,0x030D1F },
	$timezone[]="Europe/Podgorica";               //,0x031165 },
	$timezone[]="Europe/Prague";                  //,0x03142E },
	$timezone[]="Europe/Riga";                    //,0x031760 },
	$timezone[]="Europe/Rome";                    //,0x031AA5 },
	$timezone[]="Europe/Samara";                  //,0x031E68 },
	$timezone[]="Europe/San_Marino";              //,0x032194 },
	$timezone[]="Europe/Sarajevo";                //,0x032557 },
	$timezone[]="Europe/Simferopol";              //,0x032820 },
	$timezone[]="Europe/Skopje";                  //,0x032B4B },
	$timezone[]="Europe/Sofia";                   //,0x032E14 },
	$timezone[]="Europe/Stockholm";               //,0x03311C },
	$timezone[]="Europe/Tallinn";                 //,0x0333CB },
	$timezone[]="Europe/Tirane";                  //,0x033705 },
	$timezone[]="Europe/Tiraspol";                //,0x033A0B },
	$timezone[]="Europe/Uzhgorod";                //,0x033D99 },
	$timezone[]="Europe/Vaduz";                   //,0x0340B0 },
	$timezone[]="Europe/Vatican";                 //,0x034343 },
	$timezone[]="Europe/Vienna";                  //,0x034706 },
	$timezone[]="Europe/Vilnius";                 //,0x034A33 },
	$timezone[]="Europe/Volgograd";               //,0x034D72 },
	$timezone[]="Europe/Warsaw";                  //,0x03507B },
	$timezone[]="Europe/Zagreb";                  //,0x03545C },
	$timezone[]="Europe/Zaporozhye";              //,0x035725 },
	$timezone[]="Europe/Zurich";                  //,0x035A66 },
	$timezone[]="Factory";                        //,0x035D15 },
	$timezone[]="GB";                             //,0x035D86 },
	$timezone[]="GB-Eire";                        //,0x0362BD },
	$timezone[]="GMT";                            //,0x0367F4 },
	$timezone[]="GMT+0";                          //,0x0368C0 },
	$timezone[]="GMT-0";                          //,0x03687C },
	$timezone[]="GMT0";                           //,0x036838 },
	$timezone[]="Greenwich";                      //,0x036904 },
	$timezone[]="Hongkong";                       //,0x036948 },
	$timezone[]="HST";                            //,0x036B14 },
	$timezone[]="Iceland";                        //,0x036B58 },
	$timezone[]="Indian/Antananarivo";            //,0x036D11 },
	$timezone[]="Indian/Chagos";                  //,0x036D85 },
	$timezone[]="Indian/Christmas";               //,0x036DE7 },
	$timezone[]="Indian/Cocos";                   //,0x036E2B },
	$timezone[]="Indian/Comoro";                  //,0x036E6F },
	$timezone[]="Indian/Kerguelen";               //,0x036EC4 },
	$timezone[]="Indian/Mahe";                    //,0x036F19 },
	$timezone[]="Indian/Maldives";                //,0x036F6E },
	$timezone[]="Indian/Mauritius";               //,0x036FC3 },
	$timezone[]="Indian/Mayotte";                 //,0x037039 },
	$timezone[]="Indian/Reunion";                 //,0x03708E },
	$timezone[]="Iran";                           //,0x0370E3 },
	$timezone[]="Israel";                         //,0x037351 },
	$timezone[]="Jamaica";                        //,0x037680 },
	$timezone[]="Japan";                          //,0x037745 },
	$timezone[]="Kwajalein";                      //,0x0377CE },
	$timezone[]="Libya";                          //,0x037831 },
	$timezone[]="MET";                            //,0x03792B },
	$timezone[]="Mexico/BajaNorte";               //,0x037C34 },
	$timezone[]="Mexico/BajaSur";                 //,0x037F9D },
	$timezone[]="Mexico/General";                 //,0x0381E2 },
	$timezone[]="MST";                            //,0x038440 },
	$timezone[]="MST7MDT";                        //,0x038484 },
	$timezone[]="Navajo";                         //,0x0387D5 },
	$timezone[]="NZ";                             //,0x038B4E },
	$timezone[]="NZ-CHAT";                        //,0x038ECC },
	$timezone[]="Pacific/Apia";                   //,0x0391B4 },
	$timezone[]="Pacific/Auckland";               //,0x039232 },
	$timezone[]="Pacific/Chatham";                //,0x0395BE },
	$timezone[]="Pacific/Easter";                 //,0x0398B5 },
	$timezone[]="Pacific/Efate";                  //,0x039C13 },
	$timezone[]="Pacific/Enderbury";              //,0x039CD9 },
	$timezone[]="Pacific/Fakaofo";                //,0x039D47 },
	$timezone[]="Pacific/Fiji";                   //,0x039D8B },
	$timezone[]="Pacific/Funafuti";               //,0x039E01 },
	$timezone[]="Pacific/Galapagos";              //,0x039E45 },
	$timezone[]="Pacific/Gambier";                //,0x039EBD },
	$timezone[]="Pacific/Guadalcanal";            //,0x039F22 },
	$timezone[]="Pacific/Guam";                   //,0x039F77 },
	$timezone[]="Pacific/Honolulu";               //,0x039FCD },
	$timezone[]="Pacific/Johnston";               //,0x03A061 },
	$timezone[]="Pacific/Kiritimati";             //,0x03A0B3 },
	$timezone[]="Pacific/Kosrae";                 //,0x03A11E },
	$timezone[]="Pacific/Kwajalein";              //,0x03A17B },
	$timezone[]="Pacific/Majuro";                 //,0x03A1E7 },
	$timezone[]="Pacific/Marquesas";              //,0x03A246 },
	$timezone[]="Pacific/Midway";                 //,0x03A2AD },
	$timezone[]="Pacific/Nauru";                  //,0x03A337 },
	$timezone[]="Pacific/Niue";                   //,0x03A3AF },
	$timezone[]="Pacific/Norfolk";                //,0x03A40D },
	$timezone[]="Pacific/Noumea";                 //,0x03A462 },
	$timezone[]="Pacific/Pago_Pago";              //,0x03A4F2 },
	$timezone[]="Pacific/Palau";                  //,0x03A57B },
	$timezone[]="Pacific/Pitcairn";               //,0x03A5BF },
	$timezone[]="Pacific/Ponape";                 //,0x03A614 },
	$timezone[]="Pacific/Port_Moresby";           //,0x03A669 },
	$timezone[]="Pacific/Rarotonga";              //,0x03A6AD },
	$timezone[]="Pacific/Saipan";                 //,0x03A789 },
	$timezone[]="Pacific/Samoa";                  //,0x03A7EC },
	$timezone[]="Pacific/Tahiti";                 //,0x03A875 },
	$timezone[]="Pacific/Tarawa";                 //,0x03A8DA },
	$timezone[]="Pacific/Tongatapu";              //,0x03A92E },
	$timezone[]="Pacific/Truk";                   //,0x03A9BA },
	$timezone[]="Pacific/Wake";                   //,0x03AA13 },
	$timezone[]="Pacific/Wallis";                 //,0x03AA63 },
	$timezone[]="Pacific/Yap";                    //,0x03AAA7 },
	$timezone[]="Poland";                         //,0x03AAEC },
	$timezone[]="Portugal";                       //,0x03AECD },
	$timezone[]="PRC";                            //,0x03B3C9 },
	$timezone[]="PST8PDT";                        //,0x03B47A },
	$timezone[]="ROC";                            //,0x03B7CB },
	$timezone[]="ROK";                            //,0x03B8E3 },
	$timezone[]="Singapore";                      //,0x03B987 },
	$timezone[]="Turkey";                         //,0x03BA3E },
	$timezone[]="UCT";                            //,0x03BE2B },
	$timezone[]="Universal";                      //,0x03BE6F },
	$timezone[]="US/Alaska";                      //,0x03BEB3 },
	$timezone[]="US/Aleutian";                    //,0x03C21C },
	$timezone[]="US/Arizona";                     //,0x03C582 },
	$timezone[]="US/Central";                     //,0x03C610 },
	$timezone[]="US/East-Indiana";                //,0x03D01A },
	$timezone[]="US/Eastern";                     //,0x03CB1B },
	$timezone[]="US/Hawaii";                      //,0x03D284 },
	$timezone[]="US/Indiana-Starke";              //,0x03D312 },
	$timezone[]="US/Michigan";                    //,0x03D683 },
	$timezone[]="US/Mountain";                    //,0x03D9BA },
	$timezone[]="US/Pacific";                     //,0x03DD33 },
	$timezone[]="US/Pacific-New";                 //,0x03E138 },
	$timezone[]="US/Samoa";                       //,0x03E53D },
	$timezone[]="UTC";                            //,0x03E5C6 },
	$timezone[]="W-SU";                           //,0x03E8BD },
	$timezone[]="WET";                            //,0x03E60A },
	$timezone[]="Zulu";                           //,0x03EBF8 },
	return $timezone;


}