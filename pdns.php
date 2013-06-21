<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');

if(posix_getuid()<>0){
	$user=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["PDNSRestartIfUpToMB"])){PDNSRestartIfUpToMB();exit;}
if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}
if(isset($_GET["digg"])){digg();exit;}
if(isset($_GET["diggC"])){diggC();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}


if(isset($_GET["config"])){config();exit;}


if(isset($_POST["RebuildPDNSDB"])){RebuildPDNSDB();exit;}
if(isset($_POST["RestartPDNS"])){RestartPDNS();exit;}
if(isset($_POST["SaveEnablePowerDNS"])){SaveEnablePowerDNS();exit;}
if(isset($_GET["config-service"])){config_service();exit;}



if(isset($_GET["status"])){status_section();exit;}
if(isset($_GET["PowerDNS-status"])){PDNSStatus();exit;}

if(isset($_GET["logs"])){events();exit;}
if(isset($_GET["syslog-table"])){events_query();exit;}
if(isset($_GET["syslog-table-events"])){events_query_list();exit;}
if(isset($_GET["LogsParameters"])){LogsParameters();exit;}

if(isset($_GET["popup-dn"])){popup_dn_tabs();exit;}
if(isset($_GET["record"])){popup_record();exit;}

if(isset($_GET["mx"])){popup_mx();exit;}
if(isset($_GET["mx-record-list"])){popup_mx_list();exit;}
if(isset($_GET["mx-records-list"])){popup_mx_records();exit;}
if(isset($_GET["delete-mx-record"])){popup_mx_delete();exit;}
if(isset($_GET["add-mx-record"])){popup_mx_save();exit;}

if(isset($_GET["ns-record-field"])){popup_ns_field();exit;}
if(isset($_GET["ns-records-list"])){popup_ns_list();exit;}
if(isset($_GET["add-ns-record"])){popup_ns_save();exit;}
if(isset($_GET["aRecord-soa"])){popup_arecord_edit();exit;}

if(isset($_GET["AddAssociatedDomain"])){AddPointerDc();exit;}
if(isset($_GET["DelAssociatedDomain"])){DelPointerDC();exit;}
if(isset($_GET["RefreshDNSList"])){echo dnslist();exit;}
if(isset($_GET["popup-add-dns"])){echo popup_adddns();exit;}
if(isset($_GET["SaveDNSEntry"])){AddDNSEntry();exit;}
if(isset($_GET["DelDNSEntry"])){DelDNSEntry();exit;}
if(isset($_GET["EnablePDNS"])){EnablePDNS();exit;}
if(isset($_GET["PowerDNSLogsQueries"])){SaveLogsSettings();exit;}

if(isset($_GET["infos"])){pdns_infos();exit;}
if(isset($_GET["pdns-infos-query"])){pdns_infos_query();exit;}

js();
function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	
}
function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_PDNS}");
	$title=$tpl->javascript_parse_text("$mailman::{{$cmd}}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$title');";
	echo $html;
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("pdns.php?service-cmds={$_GET["service-cmds-peform"]}")));

	$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";

	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
		$html=$html."
		<tr class=$classtr>
		<td width=99%><code style='font-size:12px'>$val</code></td>
		</tr>
		";


	}

	$html=$html."
	</tbody>
	</table>
	</div>
	<script>
	RefreshPDNSStatus();
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);
}

function AddPointerDc(){
	$dn=$_GET["AddAssociatedDomain"];
	$ldap=new clladp();
	$upd["associateddomain"]=$_GET["entry"];
	if(!$ldap->Ldap_add_mod($dn,$upd)){echo $ldap->ldap_last_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");
	restart_dnsmasq();
	
}
function DelPointerDC(){
	$dn=$_GET["DelAssociatedDomain"];
	$ldap=new clladp();
	$upd["associateddomain"]=$_GET["entry"];
	if(!$ldap->Ldap_del_mod($dn,$upd)){echo $ldap->ldap_last_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");		
	restart_dnsmasq();
}



function EnablePDNS(){
	$sock=new sockets();
	
	$users=new usersMenus();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}	
	if($_GET["EnablePDNS"]==1){
		if($users->dnsmasq_installed){
			if($EnableDNSMASQ==1){
				$tpl=new templates();
				echo $tpl->javascript_parse_text("{COULD_NOT_PERF_OP_SOFT_ENABLED}:\n{APP_DNSMASQ}");
				$sock->SET_INFO("EnablePDNS",0);
				return;
			}
		}

	}	
	
	$sock->SET_INFO("EnablePDNS",$_GET["EnablePDNS"]);
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
	}

function DelDNSEntry(){
	$ldap=new clladp();
	if(!$ldap->ldap_delete($_GET["DelDNSEntry"])){
		echo $ldap->ldap_last_error;
		return;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");	
	restart_dnsmasq();		
	
}

function AddDNSEntry(){
	$computername=$_GET["computername"];
	$DnsZoneName=$_GET["DnsZoneName"];
	$pse=strpos($computername,".");
	writelogs("computername=$computername in DNS $DnsZoneName pos:$pse",__FUNCTION__,__FILE__,__LINE__);
	if($pse>0){
		$t=explode(".",$computername);
		$computername=$t[0];
		unset($t[0]);
		$DnsZoneName=@implode('.',$t);
		writelogs("SPLITED: computername=$computername in DNS $DnsZoneName",__FUNCTION__,__FILE__,__LINE__);
	}
	
	$ComputerIP=$_GET["ComputerIP"];
	$pdns=new pdns($DnsZoneName);
	if(!$pdns->EditIPName($computername,$ComputerIP,"A",null)){echo $pdns->last_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");	
	restart_dnsmasq();		
	
}

function popup_dn_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();

	$array["record"]='{record}';
	$array["mx"]='{mx_records}';
	
	
	$dn=$_GET["dn-entry"];
	
	$md5=md5($dn);
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&dn-entry=$dn\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_pdns_$md5 style='width:100%;height:700px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_pdns_$md5\").tabs();});
		</script>";		
	
	
}

function config(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["config-service"]='{global_parameters}';
	$array["forward-zones"]='{forward_zones}';
	$array["restricts"]='{restrictions}';
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="forward-zones"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.forward-zone.php\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		if($num=="restricts"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.restricts.php\"><span>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&dn-entry=$dn\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_pdns_general style='width:100%;'>
	<ul style='font-size:16px'>". implode("\n",$html)."</ul>
	</div>
	<script>
	$(document).ready(function() {
	$(\"#main_config_pdns_general\").tabs();});
	</script>";	
}


function config_service(){
$page=CurrentPageName();
	$user=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();	
	$listen_ip=$tpl->javascript_parse_text("{listen_ip}");
	if(!$user->POWER_DNS_INSTALLED){not_installed();return null;}	
	$PDNSRestartIfUpToMB=$sock->GET_INFO("PDNSRestartIfUpToMB");
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	
	$PowerUseGreenSQL=$sock->GET_INFO("PowerUseGreenSQL");
	$PowerDisableDisplayVersion=$sock->GET_INFO("PowerDisableDisplayVersion");
	$PowerActHasMaster=$sock->GET_INFO("PowerActHasMaster");
	$PowerDNSDNSSEC=$sock->GET_INFO("PowerDNSDNSSEC");
	$PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
	$PowerChroot=$sock->GET_INFO("PowerChroot");
	$PowerActAsSlave=$sock->GET_INFO("PowerActAsSlave");
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$PowerSkipCname=$sock->GET_INFO("PowerSkipCname");
	$PowerDNSRecursorQuerLocalAddr=$sock->GET_INFO("PowerDNSRecursorQuerLocalAddr");
	
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	$PowerDNSMySQLEngine=1;
	if(!is_numeric($PowerActHasMaster)){$PowerActHasMaster=0;}
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if(!is_numeric($PowerUseGreenSQL)){$PowerUseGreenSQL=0;}
	if(!is_numeric($PowerDisableDisplayVersion)){$PowerDisableDisplayVersion=0;}
	if(!is_numeric($PowerDNSDNSSEC)){$PowerDNSDNSSEC=0;}
	if(!is_numeric($PowerDNSDisableLDAP)){$PowerDNSDisableLDAP=1;}
	if(!is_numeric($PowerChroot)){$PowerChroot=0;}
	if(!is_numeric($PowerActAsSlave)){$PowerActAsSlave=0;}
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=1;}
	if(!is_numeric($PowerSkipCname)){$PowerSkipCname=0;}
	$net=new networking();
	
	if($PowerDNSRecursorQuerLocalAddr==null){
		
		$net->ifconfig("eth0");
		if($net->tcp_addr<>null){
			if($net->tcp_addr<>"0.0.0."){
				$PowerDNSRecursorQuerLocalAddr=$net->tcp_addr;
			}
		}
		
	}
	
	
	
	
	$PowerDNSMySQLType=$sock->GET_INFO("PowerDNSMySQLType");
	$PowerDNSMySQLRemoteServer=$sock->GET_INFO("PowerDNSMySQLRemoteServer");
	$PowerDNSMySQLRemotePort=$sock->GET_INFO("PowerDNSMySQLRemotePort");
	$PowerDNSMySQLRemoteAdmin=$sock->GET_INFO("PowerDNSMySQLRemoteAdmin");
	$PowerDNSMySQLRemotePassw=$sock->GET_INFO("PowerDNSMySQLRemotePassw");
	if(!is_numeric($PowerDNSMySQLType)){$PowerDNSMySQLType=1;}
	if(!is_numeric($PowerDNSMySQLRemotePort)){$PowerDNSMySQLRemotePort=3306;}
	
	$PowerDNSMySQLTypeA[1]="{main_mysql_server_2}";
	$PowerDNSMySQLTypeA[2]="{main_mysql_server_4}";
	$PowerDNSMySQLTypeA[3]="{main_mysql_server_5}";
	
	
	
	$PowerDNSMySQLTypeF=Field_array_Hash($PowerDNSMySQLTypeA,"PowerDNSMySQLType",$PowerDNSMySQLType,"PowerDNSMySQLTypeCK()",
			null,0,"font-size:16px");
	
	
	
	$POWER_DNS_MYSQL=1;
	$GREENSQL=1;
	$DNSDNSSEC=1;
	
	if(!$user->APP_GREENSQL_INSTALLED){$GREENSQL=0;$PowerUseGreenSQL=0;}
	if(!$user->PDNSSEC_INSTALLED){$PowerDNSDNSSEC=0;$DNSDNSSEC=0;}
	
	
	$DisablePowerDnsManagement_text=$tpl->javascript_parse_text("{DisablePowerDnsManagement_text}");	
	
	for($i=0;$i<10;$i++){
		$loglevels[$i]=$i;
	}
	
	$ips=$net->ALL_IPS_GET_ARRAY();
	unset($ips["127.0.0.1"]);
	$PowerDNSRecursorQuerLocalAddr=Field_array_Hash($ips, "PowerDNSRecursorQuerLocalAddr",$PowerDNSRecursorQuerLocalAddr,null,null,0,"font-size:16px");
	
	
	$PowerDNSLogLevel=Field_array_Hash($loglevels, "PowerDNSLogLevel",$PowerDNSLogLevel,null,null,0,"font-size:16px");
	
	$old="				<tr>	
					<td valign='top' class=legend style='font-size:16px' nowrap>{pdns-skip-cname}:</td>
					<td width=1%>". Field_checkbox("PowerSkipCname",1,$PowerSkipCname)."</td>
				</tr>";
	
	$html="
	<div id='PowerDNSMAsterConfigDiv'>
	
	
	
<table style='width:99%' class=form>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{DisablePowerDnsManagement}:</td>
					<td width=1%>". Field_checkbox("DisablePowerDnsManagement",1,$DisablePowerDnsManagement,"EnablePowerDNSMySQLEngineCheck()")."</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{EnablePDNS}:</td>
					<td width=1%>". Field_checkbox("EnablePDNS",1,$EnablePDNS,"EnablePDNSCheck()")."</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{ActHasMaster}:</td>
					<td width=1%>". Field_checkbox("PowerActHasMaster",1,$PowerActHasMaster)."</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{ActHasSlave}:</td>
					<td width=1%>". Field_checkbox("PowerActAsSlave",1,$PowerActAsSlave)."</td>
				</tr>				
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{DisableLDAPDatabase}:</td>
					<td width=1%>". Field_checkbox("PowerDNSDisableLDAP",1,$PowerDNSDisableLDAP,"EnablePowerDNSMySQLEngineCheck()")."</td>
				</tr>				
						
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{useMySQL}:</td>
					<td width=1%>". Field_checkbox("PowerDNSMySQLEngine",1,$PowerDNSMySQLEngine,"EnablePowerDNSMySQLEngineCheck()")."</td>
				</tr>
				<tr>	
					<td valign='top' class=legend style='font-size:16px' nowrap>DNSSEC:</td>
					<td width=1%>". Field_checkbox("PowerDNSDNSSEC",1,$PowerDNSDNSSEC)."</td>
				</tr>
				<tr>	
					<td valign='top' class=legend style='font-size:16px' nowrap>{outgoing_network_addr}:</td>
					<td width=1%>$PowerDNSRecursorQuerLocalAddr</td>
				</tr>				

				
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{useGreenSQL}:</td>
					<td width=1%>". Field_checkbox("PowerUseGreenSQL",1,$PowerUseGreenSQL)."</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{DisableDisplayVersion}:</td>
					<td width=1%>". Field_checkbox("PowerDisableDisplayVersion",1,$PowerDisableDisplayVersion)."</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:16px' nowrap>{chroot}:</td>
					<td width=1%>". Field_checkbox("PowerChroot",1,$PowerChroot)."</td>
				</tr>								
				<tr>
					<td class=legend style='font-size:16px' nowrap>{log level}:</td>
					<td style='font-size:16px;padding:3px;' nowrap>$PowerDNSLogLevel</td>
				</tr>				
				<tr>
					<td class=legend style='font-size:16px' nowrap>{RestartServiceifReachMb}:</td>
					<td style='font-size:16px;padding:3px;' nowrap>".Field_text("PDNSRestartIfUpToMB",$PDNSRestartIfUpToMB,"font-size:16px;padding:3px;width:60px")."&nbsp;MB</td>
				</tr>
				<tr>
				<td colspan=2><hr></td>
				</tr>
				<tr>
					<td class=legend style='font-size:16px' nowrap>{mysql_database}:</td>
					<td style='font-size:16px;padding:3px;' nowrap>$PowerDNSMySQLTypeF</td>
				</tr>							
				<tr>
					<td class=legend style='font-size:16px'>{remote_mysql_server}:</td>
					<td style='font-size:16px'>". Field_text("PowerDNSMySQLRemoteServer",$PowerDNSMySQLRemoteServer,"font-size:16px;width:190px")."</td>
					<td width=1%></td>
				</tr>
				<tr>
					<td class=legend style='font-size:16px'>{mysql_server_port}:</td>
					<td style='font-size:16px'>". Field_text("PowerDNSMySQLRemotePort",$PowerDNSMySQLRemotePort,"font-size:16px;width:90px")."</td>
					<td width=1%></td>
				</tr>				
				<tr>
					<td class=legend style='font-size:16px'>{mysql_admin}:</td>
					<td style='font-size:16px'>". Field_text("PowerDNSMySQLRemoteAdmin",$PowerDNSMySQLRemoteAdmin,"font-size:16px;width:190px")."</td>
					<td width=1%></td>
				</tr>				
				<tr>
					<td class=legend style='font-size:16px'>{password}:</td>
					<td style='font-size:16px'>". Field_password("PowerDNSMySQLRemotePassw",$PowerDNSMySQLRemotePassw,"font-size:16px;width:190px")."</td>
					<td width=1%></td>
				</tr>							
							
							
				<tr><td colspan=2 align='right'><hr>". button("{apply}","SavePDNSWatchdog()","18px")."</td></tr>							
			</table>
			</div>
	
<script>

		
	var x_EnablePowerDNSMySQLEngineCheck=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshTab('main_config_pdns');
		}			
		
		function EnablePowerDNSMySQLEngineCheck(){
				CheckDNSMysql();
		}
		
	function DisableAllInstead(zid){
		$('input,select,hidden,textarea', '#PowerDNSMAsterConfigDiv').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	if(zid==id){return;}
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		document.getElementById(id).disabled=true;
		 	}
		 	
		});	
	}

	function EnableAllDiv(){
		$('input,select,hidden,textarea', '#PowerDNSMAsterConfigDiv').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		document.getElementById(id).disabled=false;
		 	}
		 	
		});	
	}	
		
		
		function EnablePDNSCheck(){
			var XHR = new XHRConnection();
			if(document.getElementById('EnablePDNS').checked){
				XHR.appendData('EnablePDNS','1');
				}else{
					XHR.appendData('EnablePDNS','0');
				}
			
			XHR.sendAndLoad('$page', 'GET',x_EnablePowerDNSMySQLEngineCheck);		
		}
		
		function SavePDNSWatchdog(){
			var XHR = new XHRConnection();
			XHR.appendData('PDNSRestartIfUpToMB',document.getElementById('PDNSRestartIfUpToMB').value);
			XHR.appendData('PowerDNSLogLevel',document.getElementById('PowerDNSLogLevel').value);
			
			
			
			if(document.getElementById('PowerUseGreenSQL').checked){XHR.appendData('PowerUseGreenSQL',1);}else{XHR.appendData('PowerUseGreenSQL',0);}
			if(document.getElementById('PowerDisableDisplayVersion').checked){XHR.appendData('PowerDisableDisplayVersion',1);}else{XHR.appendData('PowerDisableDisplayVersion',0);}
			if(document.getElementById('PowerChroot').checked){XHR.appendData('PowerChroot',1);}else{XHR.appendData('PowerChroot',0);}
			if(document.getElementById('PowerActHasMaster').checked){XHR.appendData('PowerActHasMaster',1);}else{XHR.appendData('PowerActHasMaster',0);}
			if(document.getElementById('PowerActAsSlave').checked){XHR.appendData('PowerActAsSlave',1);}else{XHR.appendData('PowerActAsSlave',0);}
			//if(document.getElementById('PowerSkipCname').checked){XHR.appendData('PowerSkipCname',1);}else{XHR.appendData('PowerSkipCname',0);}
			
			
			
			
			if(document.getElementById('PowerDNSDNSSEC').checked){XHR.appendData('PowerDNSDNSSEC',1);document.getElementById('PowerDNSDisableLDAP').checked=true;}else{XHR.appendData('PowerDNSDNSSEC',0);}
			if(document.getElementById('PowerDNSMySQLEngine').checked){XHR.appendData('PowerDNSMySQLEngine',1);}else{XHR.appendData('PowerDNSMySQLEngine',0);}
			if(document.getElementById('PowerDNSDisableLDAP').checked){XHR.appendData('PowerDNSDisableLDAP',1);}else{XHR.appendData('PowerDNSDisableLDAP',0);}
			if(document.getElementById('PowerChroot').checked){XHR.appendData('PowerChroot',1);}else{XHR.appendData('PowerChroot',0);}
			XHR.appendData('PowerDNSMySQLType',document.getElementById('PowerDNSMySQLType').value);
			XHR.appendData('PowerDNSMySQLRemoteServer',document.getElementById('PowerDNSMySQLRemoteServer').value);
			XHR.appendData('PowerDNSMySQLRemotePort',document.getElementById('PowerDNSMySQLRemotePort').value);
			XHR.appendData('PowerDNSMySQLRemoteAdmin',document.getElementById('PowerDNSMySQLRemoteAdmin').value);
			XHR.appendData('PowerDNSRecursorQuerLocalAddr',document.getElementById('PowerDNSRecursorQuerLocalAddr').value);
			
			
			
			var pp=encodeURIComponent(document.getElementById('PowerDNSMySQLRemotePassw').value);
			XHR.appendData('PowerDNSMySQLRemotePassw',pp);
			
			
			if(document.getElementById('DisablePowerDnsManagement').checked){
				if(confirm('$DisablePowerDnsManagement_text')){XHR.appendData('DisablePowerDnsManagement','1');}else{XHR.appendData('DisablePowerDnsManagement','0');}
			}else{
				XHR.appendData('DisablePowerDnsManagement','0');
			}			
			
			
			
			AnimateDiv('PowerDNSMAsterConfigDiv');
			XHR.sendAndLoad('$page', 'GET',x_EnablePowerDNSMySQLEngineCheck);	
		}
		
		function PowerDNSMySQLTypeCK(){
			var DisablePowerDnsManagement=$DisablePowerDnsManagement;
			var EnablePDNS=1;
			if(document.getElementById('DisablePowerDnsManagement').checked){DisablePowerDnsManagement=1;}
			if(!document.getElementById('EnablePDNS').checked){EnablePDNS=0;}
			
			
			document.getElementById('PowerDNSMySQLType').disabled=true;
			document.getElementById('PowerUseGreenSQL').disabled=true;
			document.getElementById('PowerDNSMySQLRemoteServer').disabled=true;
			document.getElementById('PowerDNSMySQLRemotePort').disabled=true;
			document.getElementById('PowerDNSMySQLRemoteAdmin').disabled=true;
			document.getElementById('PowerDNSMySQLRemotePassw').disabled=true;			
			if(DisablePowerDnsManagement==1){return;}
			if(EnablePDNS==0){return;}
			
			if(!document.getElementById('PowerDNSMySQLEngine').checked){return;}
			document.getElementById('PowerDNSMySQLType').disabled=false;
			PowerDNSMySQLType=document.getElementById('PowerDNSMySQLType').value;	
			
			if(PowerDNSMySQLType==2){
				document.getElementById('PowerDNSMySQLRemoteServer').disabled=false;
				document.getElementById('PowerDNSMySQLRemotePort').disabled=false;
				document.getElementById('PowerDNSMySQLRemoteAdmin').disabled=false;
				document.getElementById('PowerDNSMySQLRemotePassw').disabled=false;			
			
			}
			
			if(PowerDNSMySQLType==3){
				document.getElementById('PowerUseGreenSQL').disabled=false;
			}
		
		}


		
		function CheckDNSMysql(){
			var POWER_DNS_MYSQL=$POWER_DNS_MYSQL;
			var DisablePowerDnsManagement=$DisablePowerDnsManagement;
			var EnablePDNS=$EnablePDNS;
			var GREENSQL=$GREENSQL;
			var DNSDNSSEC=$DNSDNSSEC;
			if(document.getElementById('DisablePowerDnsManagement').checked){DisablePowerDnsManagement=1;}
			if(!document.getElementById('EnablePDNS').checked){EnablePDNS=0;}
			document.getElementById('PowerDNSMySQLEngine').disabled=true;
			document.getElementById('PowerUseGreenSQL').disabled=true;
			document.getElementById('PowerDNSDNSSEC').disabled=true;
			
			DisableAllInstead('DisablePowerDnsManagement');
			
			if(DisablePowerDnsManagement==1){
				PowerDNSMySQLTypeCK();
				return;
			}
			
			EnableAllDiv();
			document.getElementById('EnablePDNS').disabled=false;
			if(EnablePDNS==0){
				DisableAllInstead('EnablePDNS');
				PowerDNSMySQLTypeCK();
				return;
				
			}
			if(POWER_DNS_MYSQL==1){
				document.getElementById('PowerDNSMySQLEngine').disabled=false;
			}
			
			if(document.getElementById('PowerDNSMySQLEngine').disabled==false){
				if(document.getElementById('PowerDNSMySQLEngine').checked){
					if(GREENSQL==1){document.getElementById('PowerUseGreenSQL').disabled=false;}
					if(DNSDNSSEC==1){
						document.getElementById('PowerDNSDNSSEC').disabled=false;
							if(document.getElementById('PowerDNSDNSSEC').checked){
								document.getElementById('PowerDNSDisableLDAP').disabled=true;
							}else{
								document.getElementById('PowerDNSDisableLDAP').disabled=false;
							}
						}
					}
				}
				
			PowerDNSMySQLTypeCK();
				
			}
		
		
		
		
		

	
	
	CheckDNSMysql();
	PowerDNSMySQLTypeCK();
	
		
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function PDNSRestartIfUpToMB(){
	$sock=new sockets();
	$_GET["PowerDNSMySQLRemotePassw"]=url_decode_special_tool($_GET["PowerDNSMySQLRemotePassw"]);
	
	
	$sock->SET_INFO("PowerDNSMySQLType",$_GET["PowerDNSMySQLType"]); 
	$sock->SET_INFO("PowerDNSMySQLRemoteServer",$_GET["PowerDNSMySQLRemoteServer"]); 
	$sock->SET_INFO("PowerDNSMySQLRemotePort",$_GET["PowerDNSMySQLRemotePort"]); 
	$sock->SET_INFO("PowerDNSMySQLRemoteAdmin",$_GET["PowerDNSMySQLRemoteAdmin"]);
	$sock->SET_INFO("PowerDNSMySQLRemotePassw",$_GET["PowerDNSMySQLRemotePassw"]);
	$sock->SET_INFO("PowerDNSRecursorQuerLocalAddr",$_GET["PowerDNSRecursorQuerLocalAddr"]);
	
	
	
	$sock->SET_INFO("PDNSRestartIfUpToMB",$_GET["PDNSRestartIfUpToMB"]);
	$sock->SET_INFO("PowerUseGreenSQL",$_GET["PowerUseGreenSQL"]);
	$sock->SET_INFO("PowerDisableDisplayVersion",$_GET["PowerDisableDisplayVersion"]);
	$sock->SET_INFO("PowerChroot",$_GET["PowerChroot"]);
	$sock->SET_INFO("PowerActHasMaster",$_GET["PowerActHasMaster"]);
	$sock->SET_INFO("PowerDNSDNSSEC",$_GET["PowerDNSDNSSEC"]);
	$sock->SET_INFO("PowerDNSMySQLEngine",$_GET["PowerDNSMySQLEngine"]);
	$sock->SET_INFO("DisablePowerDnsManagement",$_GET["DisablePowerDnsManagement"]);
	$sock->SET_INFO("PowerDNSDisableLDAP",$_GET["PowerDNSDisableLDAP"]);
	$sock->SET_INFO("PowerChroot",$_GET["PowerChroot"]);
	$sock->SET_INFO("PowerActAsSlave",$_GET["PowerActAsSlave"]);
	if(isset($_GET["PowerDNSLogLevel"])){$sock->SET_INFO("PowerDNSLogLevel",$_GET["PowerDNSLogLevel"]);}
	if(isset($_GET["PowerSkipCname"])){$sock->SET_INFO("PowerSkipCname",$_GET["PowerSkipCname"]);}
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");

}






	
function js_common(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ADD_DNS_ENTRY=$tpl->_ENGINE_parse_body('{ADD_DNS_ENTRY}');
	$addaliases=$tpl->_ENGINE_parse_body('{pdns_addaliases_text}');
	
	$html="
	function AddPDNSEntry(){
			YahooWin4('450','$page?popup-add-dns=yes','$ADD_DNS_ENTRY');
		}
		
	var x_SaveDNSEntry=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			YahooWin4Hide();
			RefreshDNSList();
			
		}	
		
	var x_DNSAddAssociatedDomain=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			YahooWin4('450','$page?popup-dn='+mem_dn,mem_dc);
			RefreshDNSList();
			
		}
		
	function DNSAddAssociatedDomain(dn,dc){
			mem_dn=dn;
			mem_dc=dc;
			notshow=0;
			var entry=prompt('$addaliases');
			if(!entry){return;}
			var XHR = new XHRConnection();
			XHR.appendData('AddAssociatedDomain',dn);
			XHR.appendData('entry',entry);
			document.getElementById('DNSAssociatedDomain').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_DNSAddAssociatedDomain);
			
		}
		
		

		function DelDNSEntry(dn){
			var XHR = new XHRConnection();
			XHR.appendData('DelDNSEntry',dn);
			document.getElementById('DNSAssociatedDomain').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_SaveDNSEntry);		
		
		}
		
		function DNSDeleteAssociatedDomain(dn,dc){
			mem_dn=dn;
			mem_dc=dc;
			notshow=1;
			var XHR = new XHRConnection();
			XHR.appendData('DelAssociatedDomain',dn);
			XHR.appendData('entry',dc);
			XHR.sendAndLoad('$page', 'GET',x_DNSAddAssociatedDomain);		
		}
		
	var x_RefreshDNSList=function (obj) {
			var results=obj.responseText;
			document.getElementById('dns_list').innerHTML=results;
			
		}			
		
		function RefreshDNSList(){
			var XHR = new XHRConnection();
			XHR.appendData('RefreshDNSList','yes');
			XHR.appendData('pattern',document.getElementById('search-dns').value);
			document.getElementById('dns_list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_RefreshDNSList);
		}
		
		function EditDNSEntry(dn,name){
			YahooWin4('600','$page?popup-dn=yes&dn-entry='+dn,name);
		}
		
		function EditMXEntry(dn,name){
			YahooWin4('600','$page?mx=yes&dn-entry='+dn,name);
		}			
		
		function QueryPowerDNSCHange(e){
			if(checkEnter(e)){RefreshDNSList();}
		}				
	
	";
	
	return $html;
}	


function js(){
	
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_PDNS}');
	$js_common=js_common();
	
	$start="{$prefix}StartPage();";
	if(isset($_GET["in-front-ajax"])){
		$start="{$prefix}StartPage2();";
	}
	
	
	$html="
		var mem_dn='';
		var mem_dc='';
		notshow=0;
		function {$prefix}StartPage(){
			YahooWin3('850','$page?tabs=yes','$title');
		}
		
		function {$prefix}StartPage2(){
			if(!document.getElementById('BodyContent')){alert('BodyContent no such id');}
			$('#BodyContent').load('$page?tabs=yes');
		}		
		
		$js_common
		
		

		
	
	$start";
	
	echo $html;
}

function not_installed(){
	
	$html="
	<div style='margin:75px'>
	". Paragraphe('dns-not-installed-64.png','{APP_PDNS}','{pdns_not_installed}',"javascript:Loadjs('setup.index.progress.php?product=APP_PDNS&start-install=yes');")
	."</div>";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	

}


function tabs(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
	if(!is_numeric($PowerDNSDisableLDAP)){$PowerDNSDisableLDAP=1;}
	$PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSDisableLDAP");
	$PowerDNSMySQLEngine=1;
	if($users->AsDnsAdministrator){$array["status"]='{status}';}
	if($users->AsDnsAdministrator){$array["config"]='{parameters}';}
	if($users->AsDnsAdministrator){$array["listen_ip"]='{listen_ip}';}
	
	
	
	if($PowerDNSDisableLDAP==0){$array["popup"]='{dns_entries} LDAP';}
	if($PowerDNSMySQLEngine==1){
		$array["popup-mysql"]='{dns_entries} MySQL';
		
	
	
	}
	
	if($users->AsDnsAdministrator){$array["popup-replic"]='{replication}';}
	if($users->AsDnsAdministrator){$array["infos"]='{infos}';}
	if($users->AsDnsAdministrator){$array["logs"]='{events}';}
	
	
	
		$fontsize="style='font-size:16px'";
	

	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-mysql"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.mysql.php\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="popup-replic"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.replic.php\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="listen_ip"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.listen-ip.php\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_pdns style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_pdns\").tabs();});
		</script>";		
		
	
}

function status_section(){
	$page=CurrentPageName();
	$user=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();	
	
	if(!$user->POWER_DNS_INSTALLED){not_installed();return null;}	
	$PDNSRestartIfUpToMB=$sock->GET_INFO("PDNSRestartIfUpToMB");
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	$PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSMySQLEngine");
	$PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
	$rebuild_database_warn=$tpl->javascript_parse_text("{rebuild_database_warn}");
	
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	if(!is_numeric($PowerDNSMySQLEngine)){$PowerDNSMySQLEngine=1;}
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if(!is_numeric($PowerDNSDisableLDAP)){$PowerDNSDisableLDAP=1;}
	$PowerDNSMySQLEngine=1;
	
	
	$DisablePowerDnsManagement_text=$tpl->javascript_parse_text("{DisablePowerDnsManagement_text}");
	$add=Paragraphe("dns-cp-add-64.png","{ADD_DNS_ENTRY}","{ADD_DNS_ENTRY_TEXT}","javascript:AddPDNSEntry()");
	$restart=Paragraphe("64-recycle.png","{restart_pdns}","{restart_pdns_text}","javascript:RestartPDNS()");
	$nic=Buildicon64('DEF_ICO_IFCFG');
	$check_resolution=$tpl->_ENGINE_parse_body("{check_resolution}");
	$tests=Paragraphe("check-64.png","$check_resolution","{check_resolution_dns_engine}","javascript:YahooWin5('650','$page?digg=yes','$check_resolution')");
	
	
	
	if($DisablePowerDnsManagement==1){
		$add=Paragraphe("dns-cp-add-64-grey.png","{ADD_DNS_ENTRY}","{ADD_DNS_ENTRY_TEXT}","");
	}
	
	if($EnablePDNS==0){
		$add=Paragraphe("dns-cp-add-64-grey.png","{ADD_DNS_ENTRY}","{ADD_DNS_ENTRY_TEXT}","");
	}	
	
	if($PowerDNSDisableLDAP==1){$add=null;}
	
	
	
	$t=time();
	$ADD_DNS_ENTRY=$tpl->_ENGINE_parse_body("{ADD_DNS_ENTRY}");
	if($PowerDNSMySQLEngine==1){
		$add=Paragraphe("dns-cp-add-64.png","$ADD_DNS_ENTRY","{ADD_DNS_ENTRY_TEXT}","javascript:YahooWin5('550','pdns.mysql.php?item-id=0&t=$t','PowerDNS:$ADD_DNS_ENTRY');");
		if($user->POWERADMIN_INSTALLED){
			if($EnablePDNS==1){
				$poweradmin=Paragraphe("poweradmin-64.png","{APP_POWERADMIN}","{APP_POWERADMIN_TEXT}","javascript:s_PopUp('powerdns/index.php',750,650);");
				if (! function_exists('mcrypt_encrypt')){
					$poweradmin=Paragraphe("warning64.png","{APP_POWERADMIN}","{APP_POWERADMIN_NO_MCRYPT_ENCRYPT}","");
				}
				
			}
		}
		
	}
	
	if($PowerDNSMySQLEngine==1){
		$repair_database=Paragraphe("database-error-64.png", "{recreate_database}", "{recreate_database_text}","javascript:RebuildPDNSDB();");
	}
	
	if($user->APP_UFDBGUARD_INSTALLED){
		$dns_filter=Paragraphe("Firewall-Secure-64.png", "{dns_filter}", "{dns_filters_ufdb_text}",
		"javascript:Loadjs('pdns.ufdb.php')");
	}
	
	
	$help=Paragraphe("help-64.png","{help}","{online_help}","javascript:s_PopUpFull('http://nas-appliance.org/index.php?cID=177','1024','900');");
	
	$POWER_DNS_MYSQL=1;
	//if(!$user->POWER_DNS_MYSQL){$POWER_DNS_MYSQL=0;$PowerDNSMySQLEngine=0;}
	$t=time();
	$time=time();
	
	$ttr[]=$dns_filter;
	$ttr[]=$add;
	$ttr[]=$repair_database;
	$ttr[]=$help;
	$ttr[]=$tests;
	$buttons=CompileTr2($ttr,"form");
	
	if($EnablePDNS==1){
		$q=new mysql();
		if(!$q->TABLE_EXISTS("records", "powerdns")){
			
			$errordb=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_missing_tables_click_to_repair}",true)."
					<hr>
					<center id='$t'>". button("{repair}", "RepairPDNSTables()","22px")."</center>
					<p>&nbsp;</p>
					<script>
						var x_RepairPDNSTables=function (obj) {
							var results=obj.responseText;
							if(results.length>0){alert(results);}
							RefreshTab('main_config_pdns');
						}
						function RepairPDNSTables(){
							var XHR = new XHRConnection();
							XHR.appendData('RepairPDNSTables','yes');
							AnimateDiv('$t');
							XHR.sendAndLoad('pdns.mysql.php', 'POST',x_RepairPDNSTables);
						}
					</script>
		
					");
					
		
				}	
		}
	
	
	$html="
	<div id='t-$time'>
	<table style='width:100%'>
	<tr>
		<td valign='top' width=99%>
			<div id='pdns_status'></div>
			<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshPDNSStatus()")."</div>
			
		</td>
		<td valign='top'>
			<table style='width:99%' class=form>
			<tr>
				<td colspan=2>
					$errordb
					". Paragraphe_switch_img("{EnablePDNS}", "{pdns_explain}","EnablePDNS$t",$EnablePDNS,null,450)."
					<hr>
					<div style='width:100%;text-align:right;margin-bottom:15px'>". button("{apply}","SaveEnablePowerDNS()","18px")."</div>
				</td>
			</tr>
			</table>
$buttons
			
	</tr>
	</table>
	</div>
	<script>
		function RefreshPDNSStatus(){
			LoadAjax('pdns_status','$page?PowerDNS-status=yes');
			
		
		}
		
	var x_RestartPDNS=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshPDNSStatus();
			
		}
		
	var x_SaveEnablePowerDNS=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshTab('main_config_pdns');
			
		}
	
	function RestartPDNS(){
		var XHR = new XHRConnection();
		XHR.appendData('RestartPDNS','yes');
		XHR.sendAndLoad('$page', 'POST',x_RestartPDNS);
	}
	
	function SaveEnablePowerDNS(){
		var XHR = new XHRConnection();
		XHR.appendData('SaveEnablePowerDNS',document.getElementById('EnablePDNS$t').value);
		AnimateDiv('t-$time');
		XHR.sendAndLoad('$page', 'POST',x_SaveEnablePowerDNS);	
	
	}
	
	function RebuildPDNSDB(){
		if(confirm('$rebuild_database_warn')){
			var XHR = new XHRConnection();
			XHR.appendData('RebuildPDNSDB','yes');
			XHR.sendAndLoad('$page', 'POST',x_RestartPDNS);
		
		}
	
	}
	
		
		RefreshPDNSStatus();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function popup(){
	$page=CurrentPageName();
	$user=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$js_common=js_common();
	$sock=new sockets();
	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=0;}	
	
	
	if(!$user->POWER_DNS_INSTALLED){
		if(!$user->dnsmasq_installed){not_installed();return null;}
		if($EnableDNSMASQLDAPDB==0){not_installed();return null;}
	}
	$html="
	<div id='dns_list' style='width:100%;height:590px;overflow:auto'></div>
	<script>
		function refreshpdnlist(){
			LoadAjax('dns_list','$page?RefreshDNSList=yes');
			
		}
		$js_common
		refreshpdnlist();
	</script>
	";
	

echo $tpl->_ENGINE_parse_body($html);
}

function dnslist(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=1;}
	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=0;}	
	
	$original_pattern=$_GET["pattern"];
	if(trim($_GET["pattern"])==null){$pattern="*";}else{
		$pattern=$_GET["pattern"];
		if(strpos($pattern,'*')==0){$pattern=$pattern.'*';}
	}
	
		$ldap=new clladp();
		$pattern="(&(objectclass=dNSDomain2)(|(aRecord=$pattern)(associatedDomain=$pattern)(dc=$pattern)))";
		$attr=array("associatedDomain","MacRecord","aRecord","sOARecord");
		$sr =ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,$attr);
		
		$add=imgtootltip("plus-24.png","{add}","AddPDNSEntry()");
		
		
		if($EnablePDNS==0){
			if(!$users->dnsmasq_installed){$warn="<center><div style='font-size:16px;color:red'>{warn_pdns_is_disabled}</div><hr></center>";}else{if($EnableDNSMASQLDAPDB==0){$warn="<center><div style='font-size:16px;color:red'>{warn_pdns_is_disabled}</div><hr></center>";}}
		}
		
		$html="$warn
		<table style='width:100%'>
					<tr>
					<td class=legend>{search}:</td>
					<td>". Field_text('search-dns',$original_pattern,'width:100%;font-size:16px;font-weight:bold',null,null,null,null,"QueryPowerDNSCHange(event)")."</td>
				</tr>
			</table>
			<table class=tableView style='width:100%'>
				<thead class=thead>
				<tr>
					<th width=1%>$add</td>
					<th width=1% nowrap colspan=2>&nbsp;</td>
					
							
				</tr>
				</thead>			
			
			
			";
				
	$pointer_on="this.style.cursor='pointer'"; 
	$pointer_off="this.style.cursor='default'";		
				
		if($sr){
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			writelogs("Found: {$hash["count"]} entries",__FUNCTION__,__FILE__);
			
			
			$count=0;
			if($hash["count"]>0){	
				if($hash["count"]>200){$hash["count"]=200;}	
				for($i=0;$i<$hash["count"];$i++){
					
					$dn=$hash[$i]["dn"];
					$arecord=$hash[$i]["arecord"][0];
					$macrecord=$hash[$i]["macrecord"][0];
					$sOARecord=$hash[$i]["soarecord"][0];
					if($arecord=="127.0.0.1"){continue;}
					if($sOARecord<>null){continue;}
					if($arecord==null){continue;}
					if($count>100){break;}
				if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
					$count=$count+1;
					$tt="<table style='width:100%;background:transparent'>";
					for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
						
						$hostname=$hash[$i]["associateddomain"][$z];
						$rr=explode('.',$hostname);
						$name=$rr[0];
						unset($rr[0]);
						$dnsdomainname=@implode(".",$rr);
						
						$tt=$tt."
							<tr style='background:transparent;'>
							<td width=1% style='background:transparent;border:0px'><img src='img/fw_bold.gif'></td>
							<td style='background:transparent;border:0px'><strong style='font-size:16px'>{$hash[$i]["associateddomain"][$z]}</strong></td>
							<td width=1% nowrap align='left' style='background:transparent;border:0px'><a href=\"javascript:blur();\" OnClick=\"javascript:EditMXEntry('$dn','$dnsdomainname');\" style='font-size:16px;text-decoration:underline' >$dnsdomainname</strong></td>
							</tr>
							";
						}
					$tt=$tt."</table>";
				
					
					
					
					$html=$html. "
					<tr class=$cl>
						<td width=1% valign='middle'><img src='img/dns-cp-22.png'></td>
						<td valign='middle' nowrap width=1% >
							<span style='font-size:16px;text-decoration:underline;font-weight:bold' 
							OnMouseOver=\"javascript:$pointer_on\" 
							OnMouseOut=\"javascript:$pointer_off\" 
							OnClick=\"javascript:EditDNSEntry('$dn','$arecord::$macrecord');\">$arecord</span>
							</td>
						<td valign='top'>$tt</td>
					</tr>";
					
					
				}
			}else{
				writelogs("Failed search $pattern",__FUNCTION__,__FILE__);
			}
			
		}
		$tpl=new templates();
		return $tpl->_ENGINE_parse_body($html."</table>");
			
}

function popup_adddns(){
	
	
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	$domains=$ldap->hash_get_all_domains();	
	$DnsZoneName=Field_array_Hash($domains,"DnsZoneName",$computer->DnsZoneName,null,null,0,null);
	$dnstypeTable=array(""=>"{select}","MX"=>"{mail_exchanger}","A"=>"{dnstypea}");
	$DnsType=Field_array_Hash($dnstypeTable,"DnsType",$computer->DnsType,null,null,0,null);
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	
	if(count($domains)>0){
		$field_domains="
	<tr>
		<td class=legend>{DnsZoneName}:</strong></td>
		<td align=left>$DnsZoneName</strong></td>
	</tr>";
		
	}else{
	$field_domains="	
		<tr>
			<td class=legend>{DnsZoneName}:</strong></td>
			<td align=left>". Field_text('DnsZoneName',null,'width:220px;font-size:16px',"script:SaveDNSEntryCheck(event)")."</strong></strong></td>
		</tr>";		
	}
	

$html="		

<div class=explain>{ADD_DNS_ENTRY_TEXT}</div>
<div id='SaveDNSEntry'>
<table style='width:99%' class=form>
<tr>	
	<td class=legend>{computer_ip}:</strong></td>
	<td align=left>". Field_text('ComputerIP',$computer->ComputerIP,'width:220px;font-size:16px',"script:SaveDNSEntryCheck(event)")."</strong></td>
<tr>
$field_domains
<tr>
	<td class=legend>{computer_name}:</strong></td>
	<td align=left>". Field_text("computername-pdns-entry",null,"width:220px;font-size:16px","script:SaveDNSEntryCheck(event)","FillDNSNAME()")."</strong></td>
</tr>


<tr>	
	<td colspan=2 align='right'><hr>". button("{add}","SaveDNSEntry();")."</td>
<tr>
</table>
<span style='font-size:16px;font-weight:bold' id='GiveHereComputerName'></span>
</div>

<script>

		
		function SaveDNSEntryCheck(e){
			SaveDNSCheckFields();
			if(checkEnter(e)){SaveDNSEntry();return;}
			FillDNSNAME();
		}
		
		function FillDNSNAME(){
			var computername=document.getElementById('computername-pdns-entry').value;
			var DnsZoneName=document.getElementById('DnsZoneName').value;
			if(computername.length==0){return;}
			if(DnsZoneName.length==0){return;}
			document.getElementById('GiveHereComputerName').innerHTML=computername+'.'+DnsZoneName;
		}
		
		function SaveDNSEntry(){
			var ok=1;
			var computername=document.getElementById('computername-pdns-entry').value;
			var DnsZoneName=document.getElementById('DnsZoneName').value;
			var ComputerIP=document.getElementById('ComputerIP').value;		
			if(DnsZoneName.length==0){ok=0;}
			if(ComputerIP.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			XHR.appendData('SaveDNSEntry','yes');
			XHR.appendData('computername',computername);
			XHR.appendData('DnsZoneName',DnsZoneName);
			XHR.appendData('ComputerIP',ComputerIP);
			XHR.sendAndLoad('$page', 'GET',x_SaveDNSEntry);
		
		}
		
		function SaveDNSCheckFields(){
			document.getElementById('computername-pdns-entry').disabled=true;
			document.getElementById('DnsZoneName').disabled=true;
			
			var ComputerIP=document.getElementById('ComputerIP').value;
			if(ComputerIP.length==0){return;}
			document.getElementById('DnsZoneName').disabled=false;
			var DnsZoneName=document.getElementById('DnsZoneName').value;
			if(DnsZoneName.length==0){return;}
			document.getElementById('computername-pdns-entry').disabled=false;
			
		}
		SaveDNSCheckFields();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function popup_record(){
	
	
	$dn=$_GET["dn-entry"];
	$hash=array();
	$ldap=new clladp();
	$filter="(objectclass=*)";
	$attrs=array();
	$sr=ldap_read($ldap->ldap_connection, $dn, $filter, $attrs);
	if($sr){
    	$hash = ldap_get_entries($ldap->ldap_connection, $sr);
	}
   	@ldap_close($ldap->ldap_connection); 
	$arecord=$hash[0]["arecord"][0];
	$macrecord=$hash[0]["macrecord"][0];
	$dc=$hash[0]["dc"][0];
	

$associateddomain="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99.5%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{aliases}</th>
	<th width=1% colspan=2>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	$associateddomain=$associateddomain."

	<tr ". CellRollOver("DNSAddAssociatedDomain('$dn','$dc')").">
	<td colspan=3 align='right' style='border-bottom:1px solid #CCCCCC;padding-bottom:3px'>
	". imgtootltip("dns-cp-add-22.png","{add aliase}")."
	</tr>
	";
	for($i=0;$i<$hash[0]["associateddomain"]["count"];$i++){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$associateddomain=$associateddomain.
		"<tr class=$classtr>
			<td width=1%><img src='img/dns-cp-22.png'></td>
			<td><strong style='font-size:16px'>{$hash[0]["associateddomain"][$i]}</td>
			<td width=1%>". imgtootltip('delete-24.png',"{delete}","DNSDeleteAssociatedDomain('$dn','{$hash[0]["associateddomain"][$i]}')")."</td>
		</tr>";
		
	}
	$associateddomain=$associateddomain."</table>";
	
	$table="
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		<table>
		<tr>
			<td valign='middle' class=legend>{servername}:</td>
			<td valign='middle'><strong style='font-size:16px'>$dc</strong></td>
		</tr>
		<tr>
			<td valign='middle' class=legend>{ip_address}:</td>
			<td valign='middle'><strong style='font-size:16px'>$arecord</strong></td>
		</tr>
		</table>
	</td>
	<td valign='top'>
		". imgtootltip('delete-32.png','{delete}',"DelDNSEntry('$dn')")."
	</td>
	</tr>
	</table>
	";
	
	
$html="
<div id='DNSAssociatedDomain' style='margin-top:8px;height:350px;overflow:auto'>
$table
<hr>
$associateddomain
</div>";



	
   
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);		
	
}
function PDNSStatus(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	
	$datas=base64_decode($sock->getFrameWork("services.php?pdns-status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	
	
	$status1=DAEMON_STATUS_ROUND("APP_PDNS",$ini,null);
	$status2=DAEMON_STATUS_ROUND("APP_PDNS_INSTANCE",$ini,null);
	$status3=DAEMON_STATUS_ROUND("PDNS_RECURSOR",$ini,null);
	
	$cmds="<center style='margin-top:10px;margin-bottom:10px;width:95%' class=form>
		<table style='width:70%'>
		<tbody>
		<tr>
			<td width=10% align='center;'>". imgtootltip("32-stop.png","{stop}","Loadjs('$page?service-cmds=stop')")."</td>
			<td width=10% align='center'>". imgtootltip("restart-32.png","{stop} & {start}","Loadjs('$page?service-cmds=restart')")."</td>
			<td width=10% align='center'>". imgtootltip("32-run.png","{start}","Loadjs('$page?service-cmds=start')")."</td>
		</tr>
		</tbody>
		</table>
		</center>";
	
	
	$status="<table style='width:99%' class=form>
	<tr>
	<td>$status1</td>
	</tr>
	<tr>
	<td>$status2</td>
	</tr>
	<tr>
	<td>$status3</td>
	</tr>
	</table>
	$cmds	
	";
	
	
	
	echo $tpl->_ENGINE_parse_body($status);
	
	
}

function popup_mx(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dn=$_GET["dn-entry"];
	$ldap=new clladp();
	
	if(preg_match("#dc=(.+?),dc=(.+?),dc=(.+?),ou=dns,$ldap->suffix$#",$dn,$re)){
		$zone=$re[2].".".$re[3];
		$newdn="dc={$re[2]},dc={$re[3]},ou=dns,$ldap->suffix";
		
	}
	
	$hash=array();
	
	$filter="(objectclass=*)";
	$attrs=array();
	$sr=ldap_read($ldap->ldap_connection, $newdn, $filter, $attrs);
	if($sr){
    	$hash = ldap_get_entries($ldap->ldap_connection, $sr);
	}
	//print_r($hash);
	
   	@ldap_close($ldap->ldap_connection); 
   	$soarecord=$hash[0]["soarecord"][0];
   	$nsrecord=$hash[0]["nsrecord"][0];
	$arecord=$hash[0]["arecord"][0];
	$macrecord=$hash[0]["macrecord"][0];
	$dc=$hash[0]["dc"][0];
	
	
	
	
$html="
		<table>
		<tr>
			<td valign='middle' class=legend>{zone}:</td>
			<td valign='middle'><strong style='font-size:16px'>$zone</strong></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td valign='middle' class=legend>soarecord:</td>
			<td valign='middle'><strong style='font-size:11px'>$soarecord</strong></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td valign='middle' class=legend style='font-size:16px' nowrap>{ipaddr}:</td>
			<td valign='middle'>". Field_text("aRecord-soa",$arecord,"font-size:16px;padding:3px;width:130px;font-weight:bold")."</td>
			<td>". button("{apply}","aRecordEdit()")."</td>
		</tr>		
		
		
		<tr>
			<td valign='top' class=legend>{nsrecord}:</td>
			<td valign='top'><div id='nsrecords-field'></div></td>
			<td>". button("{add}","NsRecordAdd()")."</td>
		</tr>	
		</table>
		<hr>	
		<div id='ns-records-list' style='width:100%'></div>
		
		
<p>&nbsp;</p>
<strong style='font-size:16px'>{mxrecords}</strong>		
<table style='width:99%' class=form>
<tr>
	<td class=legend>{score}:</td>
	<td>". Field_text("mx_poids",10,"font-size:16px;width:40px")."</td>		
	<td class=legend>MX:</td>
	<td><span id='mx-record-list'></span></td>
	<td>". button('{add}',"AddMXHost()")."</td>
</tr>
</table>
<hr>
<div id='mx-records-list' style='width:100%;height:250px;overflow:auto'></div>

<script>
	function RefreshMxList(){
		LoadAjax('mx-record-list','$page?mx-record-list=yes&dn=$newdn&domain=$zone');
	}
	
	function RefreshNsrecordfield(){
		LoadAjax('nsrecords-field','$page?ns-record-field=yes&dn=$newdn&domain=$zone');
	}	
	
	function RefreshMxHostsList(){
		LoadAjax('mx-records-list','$page?mx-records-list=yes&dn=$newdn&domain=$zone');
	}	

	function RefreshNSRecordsList(){
		LoadAjax('ns-records-list','$page?ns-records-list=yes&dn=$newdn&domain=$zone');
	}
	
	var x_aRecordEdit=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			
		}
	
	function aRecordEdit(){
		var XHR = new XHRConnection();
		XHR.appendData('dn','$newdn');
		XHR.appendData('aRecord-soa',document.getElementById('aRecord-soa').value);
		XHR.sendAndLoad('$page', 'GET',x_aRecordEdit);
	}
	
	var x_AddMXHost=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshMxHostsList();
		}	

	var x_NsRecordAdd=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshNSRecordsList();
		}			
		
		function AddMXHost(){
			if(!document.getElementById('mx-name')){return;}
			var mx=document.getElementById('mx-name').value;
			var score=document.getElementById('mx_poids').value;
			if(!isNumber(score)){
				alert('Not a number:'+score);
				return;
			}
			if(score<5){
				alert('Not a valid score:'+score);
				return;
			}
			if(mx.length<3){return;}
			var XHR = new XHRConnection();
			XHR.appendData('add-mx-record','yes');
			XHR.appendData('dn','$newdn');
			XHR.appendData('host',mx);
			XHR.appendData('score',score);
			document.getElementById('mx-records-list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_AddMXHost);
		
		}	
		
		function NsRecordAdd(){
			if(!document.getElementById('ns-name')){return;}
			var mx=document.getElementById('ns-name').value;
			if(mx.length<3){return;}
			var XHR = new XHRConnection();
			XHR.appendData('add-ns-record','yes');
			XHR.appendData('dn','$newdn');
			XHR.appendData('host',mx);
			document.getElementById('ns-records-list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_NsRecordAdd);
		
		}			
	
	RefreshMxHostsList();
	RefreshNsrecordfield();
	RefreshNSRecordsList();
	RefreshMxList();
	RefreshMxHostsList();
	
</script>

";

echo $tpl->_ENGINE_parse_body($html);
	
}

function popup_ns_list(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filter="(objectclass=*)";
	$dn=$_GET["dn"];
	$ldap=new clladp();
	$sr=ldap_read($ldap->ldap_connection, $dn, $filter, array("nSRecord"));
	if(!$sr){return;}
    $hash = ldap_get_entries($ldap->ldap_connection, $sr);	
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{nsrecord}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	    
	
	$icon="dns-cp-22.png";
	
    for($i=0;$i<$hash[0]["nsrecord"]["count"];$i++){
    if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
    	$ns=$hash[0]["nsrecord"][$i];
    	$array=array("dn"=>$dn,"entry"=>$ns);
    	$value=base64_encode(serialize($array));
    	$delete=imgtootltip("delete-24.png","{delete}","NSRecordDelete('$value')");
   	 $html=$html . "
		<tr  class=$classtr>
		<td width=1%><img src='img/$icon'></td>
		<td width=99% align='left'><strong style='font-size:16px'>$ns</strong></td>
		<td width=1%>$delete</td>
		</td>
		</tr>";
    
    }
    $html=$html."</tbody></table>
    <script> 	
		function NSRecordDelete(value){
			var XHR = new XHRConnection();
			XHR.appendData('delete-mx-record',value);
			document.getElementById('ns-records-list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_NsRecordAdd);
		
		}    	
    
    </script>
    ";
    echo $tpl->_ENGINE_parse_body($html);
    	
}

function popup_mx_records(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filter="(objectclass=*)";
	$dn=$_GET["dn"];
	$ldap=new clladp();
	$sr=ldap_read($ldap->ldap_connection, $dn, $filter, array("mXRecord"));
	if(!$sr){return;}
    $hash = ldap_get_entries($ldap->ldap_connection, $sr);
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{score}</th>
		<th>MX</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	    
	
	$icon="dns-cp-22.png";
	
    for($i=0;$i<$hash[0]["mxrecord"]["count"];$i++){
    if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
    	if(!preg_match("#([0-9]+)\s+(.+?)$#",$hash[0]["mxrecord"][$i],$re)){continue;}
    	$array=array("dn"=>$dn,"entry"=>$hash[0]["mxrecord"][$i]);
    	$value=base64_encode(serialize($array));
    	$delete=imgtootltip("delete-24.png","{delete}","mxRecordDelete('$value')");
   	 $html=$html . "
		<tr  class=$classtr>
		<td width=1%><img src='img/$icon'></td>
		<td width=1% align='center'><strong style='font-size:16px'>{$re[1]}</strong></td>
		<td width=99%><strong style='font-size:16px'>{$re[2]}</strong></td>
		<td width=1%>$delete</td>
		</td>
		</tr>";
    
    }
    $html=$html."</tbody></table>
    <script> 	
		function mxRecordDelete(value){
			var XHR = new XHRConnection();
			XHR.appendData('delete-mx-record',value);
			document.getElementById('mx-records-list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_AddMXHost);
		
		}    	
    
    </script>
    ";
    echo $tpl->_ENGINE_parse_body($html);
    
}

function popup_mx_list(){
	$filter="(&(objectclass=dNSDomain2)(associatedDomain=*{$_GET["zone"]}))";
	$dn=$_GET["dn"];
	$attrs=array("associatedDomain");
	$ldap=new clladp();
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
			$host=$hash[$i]["associateddomain"][$z];
			if(preg_match("#in-addr\.arpa#",$host)){continue;}
			if(preg_match("#^ns\..*#",$host)){continue;}
			$hosts[$host]=$host;
		}
	}
	
	if(!is_array($hosts)){return;}
	ksort($hosts);
	$hosts[null]="{select}";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(Field_array_Hash($hosts,"mx-name",null,"style:font-size:16px;padding:3px"));
	
}

function popup_ns_field(){
	$filter="(&(objectclass=dNSDomain2)(associatedDomain=*{$_GET["zone"]}))";
	$dn=$_GET["dn"];
	$attrs=array("associatedDomain");
	$ldap=new clladp();
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
			$host=$hash[$i]["associateddomain"][$z];
			if(preg_match("#in-addr\.arpa#",$host)){continue;}
			if(preg_match("#^ns\..*#",$host)){continue;}
			$hosts[$host]=$host;
		}
	}
	
	if(!is_array($hosts)){return;}
	ksort($hosts);
	$hosts[null]="{select}";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(Field_array_Hash($hosts,"ns-name",null,"style:font-size:16px;padding:3px"));
		
}

function popup_mx_delete(){
	$array=unserialize(base64_decode($_GET["delete-mx-record"]));
	$ldap=new clladp();
	$upd["mxrecord"]=$array["entry"];
	if(!$ldap->Ldap_del_mod($array["dn"],$upd)){echo $ldap->ldap_last_error;return ;}
	restart_dnsmasq();
	
}
function restart_dnsmasq(){
	$user=new usersMenus();
	$sock=new sockets();
	if(!$user->dnsmasq_installed){return;}
	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=0;}	
	if($EnableDNSMASQLDAPDB==0){return null;}
	include_once(dirname(__FILE__)."/ressources/class.dnsmasq.inc");
	$dnsmasq=new dnsmasq();
	$dnsmasq->SaveConfToServer();

	
}

function popup_mx_save(){
	$dn=$_GET["dn"];
	
	$ldap=new clladp();
	$upd=array();
	$upd["mxrecord"]="{$_GET["score"]} {$_GET["host"]}";
	if(!$ldap->Ldap_add_mod($dn,$upd)){echo $ldap->ldap_last_error;return;}
	restart_dnsmasq();
}

function popup_ns_save(){
	$dn=$_GET["dn"];
	
	$ldap=new clladp();
	$upd=array();
	$upd["nsrecord"]="{$_GET["host"]}";
	if(!$ldap->Ldap_add_mod($dn,$upd)){echo $ldap->ldap_last_error;return;}
	restart_dnsmasq();	
}

function popup_arecord_edit(){
	$dn=$_GET["dn"];
	$ldap=new clladp();
	$upd=array();
	$upd["aRecord"][0]="{$_GET["aRecord-soa"]}";
	if($ldap->Ldap_modify($dn,$upd)){echo $ldap->ldap_last_error;return;}
	restart_dnsmasq();
}

//dNSTTL aRecord nSRecord cNAMERecord sOARecord pTRRecord hInfoRecord mXRecord tXTRecord rPRecord aFSDBRecord KeyRecord aAAARecord lOCRecord sRVRecord nAPTRRecord kXRecord certRecord dSRecord sSHFPRecord iPSecKeyRecord rRSIGRecord nSECRecord dNSKeyRecord dHCIDRecord sPFRecord

function events(){
	$page=CurrentPageName();
	
	$html="

	
	
	
	<div style='widht:100%;height:93%;margin:-5px' id='syslog-table'></div>
	<script>
		
		function SearchSyslog(){
			
			LoadAjax('syslog-table','$page?syslog-table=yes');
		
		}
		
		
	
	SearchSyslog();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function LogsParameters(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$PowerDNSLogsQueries=$sock->GET_INFO("PowerDNSLogsQueries");
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$t=$_GET["t"];
	for($i=1;$i<10;$i++){
		$hahslevel[$i]=$i;
	}
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=1;}

	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{log_queries}:</td>
		<td>". Field_checkbox("PowerDNSLogsQueries",1,$PowerDNSLogsQueries)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{log_level}:</td>
		<td>". Field_array_Hash($hahslevel,"PowerDNSLogLevel",1,$PowerDNSLogLevel,"style:font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","PowerDNSLogsSave()","18px")."</td>		
	</tr>
	</table>
	
	<script>
	var x_PowerDNSLogsSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		YahooWin5Hide();
		$('#flexRT$t').flexReload();
	}		
	
	function PowerDNSLogsSave(){
			var XHR = new XHRConnection()
			if(document.getElementById('PowerDNSLogsQueries').checked){
				XHR.appendData('PowerDNSLogsQueries',1);
			}else{
				XHR.appendData('PowerDNSLogsQueries',0);
			}
			
			XHR.appendData('PowerDNSLogLevel',document.getElementById('PowerDNSLogLevel').value);	
			XHR.sendAndLoad('$page', 'GET',x_PowerDNSLogsSave);
			
		}	
	</script>	
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function SaveLogsSettings(){
	$sock=new sockets();
	$sock->SET_INFO("PowerDNSLogLevel",$_GET["PowerDNSLogLevel"]);
	$sock->SET_INFO("PowerDNSLogsQueries",$_GET["PowerDNSLogsQueries"]);
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
}
function RestartPDNS(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?pdns-restart=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_in_background_restart_service}");	
}
function RebuildPDNSDB(){
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?rebuild-database=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_in_background_restart_service}");
}

function SaveEnablePowerDNS(){
	$sock=new sockets();
	$sock->SET_INFO("EnablePDNS", $_POST["SaveEnablePowerDNS"]);
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");	
}

if(isset($_POST["SaveEnablePowerDNS"])){SaveEnablePowerDNS();exit;}

function events_query(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=513;
	$TB_WIDTH=883;
	$TB2_WIDTH=400;
	$TB_WIDTH=838;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$volume_type=$tpl->_ENGINE_parse_body("{type}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$events=$tpl->_ENGINE_parse_body("events");
	
	$buttons="
	buttons : [
	{name: '$build_parameters', bclass: 'Reconf', onpress : parameters$t},
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?syslog-table-events=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$events', name : 'content', width :838, sortable : true, align: 'left'},

	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		
		
		],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 870,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_VolumeDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#flexRT$t').flexReload();
	}

function VolumeDelete$t(volenc,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-volume',volenc);
    XHR.sendAndLoad('$page', 'POST',x_VolumeDelete$t);	
	}
	
function parameters$t(){
	YahooWin5('400','$page?LogsParameters=yes&t=$t','PowerDNS:$events:$build_parameters');
}

	
</script>";
	
	echo $html;		
}	
	
	
	
	
function events_query_list(){	
	$pattern=base64_encode($_POST["query"]);
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?syslog-query=$pattern&prefix=pdns*")));
	if(!is_array($array)){return null;}
	
	$t=$_GET["t"];
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();

	$sock=new sockets();
	
	while (list ($volume_name, $ligne) = each ($array) ){
		$id=md5($ligne);
		
		$ligne=wordwrap($ligne,140,"</br>");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>$ligne</span>",
			
		
		  	

		 )
		);
	}
	
	
echo json_encode($data);	
	
}



function pdns_infos(){
	$page=CurrentPageName();
$html="<div id='pdns-infos'></div>

<script>
	function LoadPdns(uri){
		LoadAjax('pdns-infos','$page?pdns-infos-query='+escape(uri));
	
	}

LoadPdns('');
</script>

";
	echo $html;
	
}

function pdns_infos_query(){

	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$uri="http://127.0.0.1:8081";
	if($_GET["pdns-infos-query"]<>null){$uri="http://127.0.0.1:8081/{$_GET["pdns-infos-query"]}";}
	
	writelogs("URI:$uri",__FILE__,__FUNCTION__,__LINE__);
	
	$curl=new ccurl($uri,true);
	if(!$curl->get()){
		echo "<H2>$uri ($curl->error)</H2>";return;
	}
	$datas=$curl->data;
	writelogs(strlen($datas)." bytes ",__FILE__,__FUNCTION__,__LINE__);
	if(strlen($datas)==0){$curl=new ccurl("http://127.0.0.1:8081",true);$curl->get();$datas=$curl->data;}
	
	
	if(preg_match("#<body.*?>(.+?)</body>#s", $datas,$re)){$datas=$re[1];}
	if(preg_match("#<h2>(.+?)</h2>#s", $datas,$re)){$datas=str_replace($re[0], "<div style='font-size:16px;margin-bottom:10px;border-bottom:1px solid black'>{$re[1]}</div>", $datas);}
	
	if(preg_match_all("#<a href=(.+?)>#", $datas, $hrf)){
		while (list ($index, $line) = each ($hrf[0]) ){
			$datas=str_replace($line, "<a href=\"javascript:blur();\" OnClick=\"javascript:LoadPdns('{$hrf[1][$index]}')\" style='font-size:13px;text-decoration:underline'>", $datas);
			
		}
		
		
	}
	
	$datas=str_replace("table border=1", "table cellspacing='0' cellpadding='0' border='0' style='width:100%'", $datas);
	$datas=str_replace("#ff0000","#CCCCCC", $datas);
	$datas=str_replace("<td colspan=3 bgcolor=#0000ff>","<td colspan=3 bgcolor=black style='font-size:13px'>", $datas);
	$datas=str_replace("<td>","<td style='font-size:13px'>", $datas);
	$datas=str_replace("<font color=#ffffff>","<font color=black style='font-size:13px'>", $datas);
	$datas=str_replace("<td align=right>","<td style='font-size:13px' align=right>", $datas);
	
	echo $datas;
	
	
	
}

function digg(){
	$ips=array();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddr"));
	if(is_array($datas)){
		while (list ($index, $ipmask) = each ($datas) ){
			if(trim($ipmask)==null){continue;}
			$ips[$ipmask]=$ipmask;
		}
	}
	if(count($ips)==0){$ips["127.0.0.1"]="127.0.0.1";}

	$html="<table style='width:99%' class=form>
	
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("hostname-$t","www.google.com","font-size:16px;width:350px",null,null,null,false,"SendDigC$t(event)")."</td>
		
	<tr>
		<td class=legend style='font-size:16px'>{nic}:</td>
		<td>". Field_array_Hash($ips, "listen-$t",null,"blur()",null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{check}","SendDig$t()","18px")."</td>
	</tr>
	</table>
	<center><div style='width:95%;min-height:400px;height:400px;overflow:auto' id='r-$t'></div> 
	
	
	<script>
		function SendDigC$t(e){
			if(checkEnter(e)){
				SendDig$t();
			}
		}
		
		function SendDig$t(){
			var interface=document.getElementById('listen-$t').value;
			var host=document.getElementById('hostname-$t').value;
			LoadAjax('r-$t','$page?diggC=yes&hostname='+host+'&interface='+interface);
		
		}
		
	
	</script>
	";
echo $tpl->_ENGINE_parse_body($html);
}
function diggC(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("pdns.php?digg=yes&hostname={$_GET["hostname"]}&interface={$_GET["interface"]}")));
	while (list ($index, $line) = each ($datas) ){
		if(trim($line==null)){continue;}
		$line=htmlentities($line);
		$line=str_replace(" ", "&nbsp;", $line);
		$line=str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $line);
		$f[]="<div style='text-align:left'><code style='font-size:13px'>$line</code></div>";
		
	}
	
	echo @implode("", $f);
}


?>