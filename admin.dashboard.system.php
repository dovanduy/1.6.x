<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(!$GLOBALS["AS_ROOT"]){session_start();}
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');

if(isset($_GET["hostname-text"])){hostname_text();exit;}
if(isset($_GET["nics-infos"])){nic_infos();exit;}
if(isset($_GET["hardware-section"])){hardware_section();exit;}
if(isset($_GET["services-section"])){services_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["filesharing-section"])){filesharing_section();exit;}
if(isset($_GET["update-section"])){update_maintain_section();exit;}
if(isset($_GET["network-section"])){network_section();exit;}


xstart();


function xstart(){
	$page=CurrentPageName();
	$tpl=new templates();
	$jshostname="Loadjs('system.nic.config.php?change-hostname-js=yes&newinterface=yes');";
	$sock=new sockets();
	
	$hostname=$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
	
	$t=time();
	$html="
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>
	{system_and_network}:&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:$jshostname\"
	style='text-decoration:underline' id='chhostname-text'>$hostname</a></div>	
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:400px'>
		<div id='nics-infos-system'></div>
		<td style='vertical-align:top;width:1100px;padding-left:15px'>
		
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/96-hd.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{your_hardware}</div>
				<div id='hardware-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
			
			<p>&nbsp;</p>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/users-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{filesharing}</div>
				<div id='filesharing-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>			
			
			
			<p>&nbsp;</p>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{monitor}</div>
				<div id='monitor-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
			<p>&nbsp;</p>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/maintenance-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{maintain}</div>
				<div id='update-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>
			
			
		</td>
			
			
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
		
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/network-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{network}</div>
				<div id='network-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
		
		
		  
		
			<p>&nbsp;</p>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/services-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{services}</div>
				<div id='services-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>
	
	</table>
	</td>
		
	</tr>
	</table>
	<script>
		
		LoadAjaxRound('nics-infos-system','$page?nics-infos=yes');
		LoadAjaxRound('hardware-section','$page?hardware-section=yes');
		LoadAjaxRound('network-section','$page?network-section=yes');
		LoadAjaxRound('monitor-section','$page?monitor-section=yes');
		LoadAjaxRound('services-section','$page?services-section=yes');
		LoadAjaxRound('update-section','$page?update-section=yes');
		LoadAjaxRound('filesharing-section','$page?filesharing-section=yes');
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

//

function hostname_text(){
	$sock=new sockets();
	$hostname=$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
	echo $hostname;
}
function nic_infos(){
	$sock=new sockets();
	$q=new mysql();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$tcp=new networking();
	$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));
	
	
	$sql="SELECT Interface FROM nics";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$MYSQL_NIC[$ligne["Interface"]]=$ligne["Interface"];
		
	}
	
	
	
	
	while (list ($num, $val) = each ($datas) ){
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		$wire='';
		$defaultroute_text=null;
		$color="black";
		$error=null;
		$MUST_CHANGE=false;
		$WCCP_INTERFACE=false;
		$icon="network-128-ok.png";
		if(preg_match('#master#',$val)){continue;}
		if(preg_match("#^veth.+?#",$val)){continue;}
		if(preg_match("#^tunl[0-9]+#",$val)){continue;}
		if(preg_match("#^dummy[0-9]+#",$val)){continue;}
		if(preg_match("#^gre[0-9]+#",$val)){continue;}
		if(preg_match("#^ip6tnl[0-9]+#",$val)){continue;}
		if(preg_match("#^sit[0-9]+#",$val)){continue;}
		if(preg_match("#^vlan[0-9]+#",$val)){continue;}
		if(preg_match("#^virt[0-9]+#",$val)){continue;}
		
		if(preg_match("#wccp[0-9]+#", $val)){$WCCP_INTERFACE=true;}
		
		$radius="-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;/* behavior:url(/css/border-radius.htc); */";
		unset($MYSQL_NIC[$val]);
		$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$val");
		$tbl=explode(";",$nicinfos);
		if($IPBANS[$tbl[0]]){continue;}
		$nicz=new system_nic($val);
		if(trim($val)==null){continue;}
		if($nicz->Bridged==1){continue;}
		$tcp->ifconfig(trim($val));
		$TCP_NIC_STATUS=$sock->getFrameWork("cmd.php?nicstatus=$val");
		if(trim($tbl[5])=="yes"){$wire=" (wireless)";}
		$gateway=trim($tbl[4]);
		if($gateway==null){$gateway=$nicz->GATEWAY;}
		if($nicz->defaultroute==1){
			
		$defaultroute_text="<i style='font-weight:blod;font-size:14px'>{default_route}</i>";}
		
		if($tbl[0]==null){
			$error="<span style='color:#BA0000'>{waiting_network_reload}</span>";
			$icon="network-128-warn.png";
		}
		
		if($nicz->IPADDR<>$tbl[0]){$MUST_CHANGE=true;}
		if($nicz->NETMASK<>$tbl[2]){$MUST_CHANGE=true;}
		if($nicz->GATEWAY<>$gateway){$MUST_CHANGE=true;}
		if($nicz->dhcp==1){$ip=new IP();if($ip->isValid($tbl[0])){$MUST_CHANGE=false;}}
		if($nicz->enabled==0){$MUST_CHANGE=false;}
		if($MUST_CHANGE){
			$icon="network-128-warn.png";
			$error="<span style='color:#BA0000;font-size:16px'>{need_to_apply_network_settings_interface}</span>";
		
		}		
		
		if($nicz->enabled==0){
			$icon="network-128-disabled.png";
			$color="#8E8E8E";
		}
	
		
		if($tbl[0]==null){
			$tbl[0]="<span style='color:#8a8a8a'>$nicz->IPADDR</span>";
			
		}
		if($tbl[2]==null){
			$tbl[2]="<span style='color:#8a8a8a'>$nicz->NETMASK</span>";
		}
		
		$button=button("{modify}", "Loadjs('system.nic.edit.php?nic=$val')",16,150);
		
		if($WCCP_INTERFACE){$error=null;$button=null;}
		
		$tr[]="
		<table style='width:100%;border:2px solid #CCCCCC;margin-bottom:10x;$radius'>
		
		<tr>
			<td style='font-size:22px;font-weight:bold' colspan=2>
			<div style='margin-top:10px;margin-bottom:10px'>$nicz->netzone</span>: $nicz->NICNAME ($val)
			<div style='text-align:right;width:80%;margin-top:-2px;
			padding-top:5px;margin-bottom:10px'>$defaultroute_text $error</div>
			</div>
		</td>
		</tr>
		<tr>
		<td colspan=2>
		<table style='width:100%'>
		<td valign='top'><img src='img/$icon'></td>
		<td valign='top'>
			<table style='width:100%'>
			<tr>
				<td style='font-size:16px;color:$color' class=legend>{tcp_address}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>{$tbl[0]}</td>
			</tr>		
			<tr>
				<td class=legend nowrap style='color:$color;font-size:16px'>{netmask}</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>{$tbl[2]}</a></td>
			</tr>	
			<tr>
				<td class=legend nowrap style='color:$color;vertical-align:top;font-size:16px'>{gateway}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>{$gateway}</a></td>
			</tr>
			<tr>
				<td class=legend nowrap style='color:$color;vertical-align:top;font-size:16px'>{metric}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>$nicz->metric</a></td>
			</tr>			
			<tr>
				<td class=legend nowrap style='color:$color;font-size:16px'>{mac_addr}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>{$tbl[1]}</a></td>
			</tr>	
			</table>
		</td>
		</tr>
		</table>
		</td>
		
		<tr>
			<td colspan=2 align='right' style='padding-bottom:9px'>$button</td>
		</tr>		
		</table><p>&nbsp;</p>
		";
	}
	
	if(count($MYSQL_NIC)>0){
		while (list ($num, $val) = each ($MYSQL_NIC) ){
			$nicz=new system_nic($val);
			$tr[]="
			<table style='width:100%;border:2px solid #CCCCCC;margin-bottom:10x;$radius'>
			
			<tr>
			<td style='font-size:22px;font-weight:bold' colspan=2>
			<div style='margin-top:10px;margin-bottom:10px'>$nicz->netzone</span>: $nicz->NICNAME ($val)
			<div style='font-size:18px;color:#d32d2d'>{hardware_error}</div>
			</div>
			</td>
			</tr>
			<tr>
			<td colspan=2>
			<table style='width:100%'>
			<td valign='top'><img src='img/network-128-fatal.png'></td>
			<td valign='top'>
			<table style='width:100%'>
			<tr>
			<td style='font-size:16px;color:$color' class=legend>{tcp_address}:</td>
			<td style='font-weight:bold;font-size:16px;color:$color'>$nicz->IPADDR</td>
			</tr>
			<tr>
			<td class=legend nowrap style='color:$color;font-size:16px'>{netmask}</td>
			<td style='font-weight:bold;font-size:16px;color:$color'>$nicz->NETMASK</a></td>
			</tr>
			<tr>
			<td class=legend nowrap style='color:$color;vertical-align:top;font-size:16px'>{gateway}:</td>
			<td style='font-weight:bold;font-size:16px;color:$color'>$nicz->GATEWAY</a></td>
			</tr>
			<tr>
			<td class=legend nowrap style='color:$color;vertical-align:top;font-size:16px'>{metric}:</td>
			<td style='font-weight:bold;font-size:16px;color:$color'>$nicz->metric</a></td>
			</tr>
			</table>
			</td>
			</tr>
			</table>
			</td>
			
			<tr>
			<td colspan=2 align='right' style='padding-bottom:9px'>".
			button("{delete}", "Loadjs('system.nic.edit.php?nic-delete-js=$val')",16,150)."</td>
			</tr>
			</table><p>&nbsp;</p>
		";
			
		}
		
		
	}
	
	
	$tr[]="
	<center>".button("{apply_network_configuration}","Loadjs('network.restart.php')",18,390)."</center>
	<center style='margin-top:10px'>".button("{network_status}","Loadjs('network.status.php')",18,390)."</center>";
			
	$i=0;
	$datas=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/usb.scan.serialize"));
	while (list ($HDS, $MAIN_HD) = each ($datas) ){
		if($HDS=="UUID"){continue;}
		$HDSENC=urlencode($HDS);
		$tr[]="
		<p>&nbsp;</p>
		<table style='width:100%;border:2px solid #CCCCCC;margin-bottom:10x;$radius'>
		
		<tr>
		<td style='font-size:22px;font-weight:bold'>
		<div style='margin-top:10px;margin-bottom:10px'>{disk} 
		
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('system.internal.disks.php?partinfos-js=$HDSENC')\"
		style='text-decoration:underline;font-weight:bold'>{$HDS}</a> - {$MAIN_HD["SIZE"]}</div></td>
		</tr>
		";
		
		
		
		while (list ($dev, $MAIN_PART) = each ($MAIN_HD["PARTITIONS"]) ){
			$i++;
			$ID_FS_LABEL=$MAIN_PART["ID_FS_LABEL"];
			if($ID_FS_LABEL==null){$ID_FS_LABEL=$MAIN_PART["MOUNTED"];}
			$SIZE=round($MAIN_PART["INFO"]["SIZE"]/1024);
			if($SIZE==0){continue;}
			$UTIL=round($MAIN_PART["INFO"]["UTIL"]/1024);
			$ID_FS_UUID=$MAIN_PART["ID_FS_UUID"];
			$script[]="
var g$i = new JustGage({
	id: '$ID_FS_UUID',
	value: $UTIL,
	min: 0,
	max: $SIZE,
	title: '$ID_FS_LABEL',
	label: 'MB',
	levelColorsGradient: true
});";
			
			$icz[]="<div style='width:130px;height:100px' id='$ID_FS_UUID'></div>
				
		";
			
		}
		$tr[]="
		<tr>
		<td>". CompileTr3($icz,true)	."</td>
				
		</table><p>&nbsp;</p>";
		
	}
			
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr))."

<script>
".@implode("\n", $script)."
</script>";
	
	
	
}
function hardware_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";
	$tr[]="<table style='width:100%'>";
	

	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{system_optimization}",
			"position:right:{system_optimization}","GotoOptimizeSystem()")."</td>
	</tr>";
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{your_hard_disks}",
				"position:right:{your_hard_disks_explain}","GotoHarddrive()")."</td>
	</tr>";
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{memory_info}",
			"position:right:{memory_info}","GotoSystemMemory()")."</td>
	</tr>";	
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{network_interfaces}",
			"position:right:{network_interfaces}","GotoNetHard()")."</td>
	</tr>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{temperature}",
				"position:right:{temperature}","GotoSenSors()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{server_time2}",
				"position:right:{server_time2}","GotoClock()")."</td>
	</tr>";

	
	
	$icon="arrow-right-24.png";
	if($users->VMWARE_HOST){
		if(trim($sock->getFrameWork("services.php?vmtools_installed=yes"))<>"TRUE"){
			$icon="warn-red-24.png";
		}
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_VMTOOLS}",
				"position:right:{APP_VMTOOLS}","GotoVMWareTools()")."</td>
		</tr>";
	}	
	
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

function monitor_section(){
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	$users=new usersMenus();
	$tr[]="<table style='width:100%'>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{system_health_checking}",
			"position:right:{system_health_checking_explain}","GotoSystemHealthMonit()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{system_events}",
			"position:right:{system_events}","GotoSystemEvents()")."</td>
	</tr>";
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{smtp_notifications}",
			"position:right:{smtp_notifications_text}","GotoSMTPNOTIFS()")."</td>
	</tr>";
	
	
	
	$icon_snmp="arrow-right-24.png";
	$color_snmp="black";
	$EnableSNMPD=intval($sock->GET_INFO("EnableSNMPD"));
	if($EnableSNMPD==0){
		$color_snmp="#898989";
		$icon_snmp="arrow-right-24-grey.png";
	}
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_snmp'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_snmp'>".texttooltip("SNMP",
			"position:right:SNMP","GotoSNMPD()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{connections_tracking}",
			"position:right:{connections_tracking}","GotoNetTrack()")."</td>
	</tr>";	
	

	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));

	
}


function filesharing_section(){
	
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	
	$icon="arrow-right-24.png";
	
	
	
	$icon_automount="arrow-right-24.png";
	$js_automount="GotoAutomount()";
	if(!$users->autofs_installed){
		$icon_automount="arrow-right-24-grey.png";
		$js_automount="blur()";
	}
	
	if($SquidPerformance>2){
		$icon_automount="arrow-right-24-grey.png";
		$js_automount="blur()";
	}
	
	if($EnableIntelCeleron==1){
		$icon_automount="arrow-right-24-grey.png";
		$js_automount="blur()";
	}
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_automount'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{automount_center}",
			"position:right:{automount_center_text}","GotoAutomount()")."</td>
	</tr>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_SYNCTHING}",
			"position:right:{APP_SYNCTHING}","GotoSyncThing()")."</td>
	</tr>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("FTP",
			"position:right:FTP","GotoVSFTPD()")."</td>
	</tr>";
	
	
	
	if($users->DROPBOX_INSTALLED){
		
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_DROPBOX}",
				"position:right:{APP_DROPBOX}","Loadjs('samba.dropbox.php')")."</td>
	</tr>";
		
		
	}else{
		$tr[]="<tr><td valign='middle' style='width:25px'><img src='img/arrow-right-24-grey.png'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
				texttooltip("{APP_DROPBOX}",
				"position:right:{APP_DROPBOX}","blur()")."</td></tr>";
		
	}
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}


function update_maintain_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";	

	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{update2}",
			"position:right:Meta","GotToArticaUpdate()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{artica_license}",
			"position:right:{artica_license}","GoToArticaLicense()")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{clamav_antivirus_databases}",
			"position:top:{clamav_antivirus_databases_explain}","GotoClamavUpdates()")."</td>
	</tr>";	

	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{certificates_center}",
			"position:right:{certificate_center_explain}","GotoCertificatesCenter()")."</td>
	</tr>";	

	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("Meta",
			"position:right:Meta","GotoArticaMeta()")."</td>
	</tr>";
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{backup_restore}",
			"position:right:{backup_restore}","GotoArticaBackup()")."</td>
	</tr>";	

	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{system_tasks}",
			"position:right:{system_tasks}","GotoSystemSchedules()")."</td>
	</tr>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

function network_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{tcpip_settings}:</td>
	</tr>";
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{dns_settings}",
			"position:right:{dns_settings}","GotoSystemDNS()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{routes}",
			"position:right:{routes}","GotoNetworkRoutes()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{network_bridges}",
			"position:right:{network_bridges}","GotoNetworkBridges()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{etc_hosts}",
			"position:right:{etc_hosts}","GotoETCHOSTS()")."</td>
	</tr>";
	
	
	

	
	
	
	

	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{virtual_interfaces}",
			"position:right:{virtual_interfaces}","GotoVNI()")."</td>
	</tr>";
	
	if($users->VLAN_INSTALLED){
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("VLAN",
				"position:right:VLAN","GotoVLAN()")."</td>
		</tr>";	
	
	}
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{arp_table}",
			"position:right:{arp_table}","GotoARTPTable()")."</td>
	</tr>";
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{edit_networks}",
			"position:right:{edit_networks}","GotoNetworkNETWORKS()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{browse_computers}",
			"position:right:{browse_computers}","GotoNetworkBrowseComputers()")."</td>
	</tr>";	

	
	
	
	if($users->VDESWITCH_INSTALLED){
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{virtual_switch}",
				"position:right:{virtual_switch}","GotoVDE()")."</td>
		</tr>";
		
	}
	

	
	
	
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}


function services_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$EnablePDNS=intval($sock->GET_INFO("EnablePDNS"));
	
	$js_dnsmasq="GotoNetworkDNSMASQ()";
	$color_dnsmasq="black";
	$icon_dnsmasq="arrow-right-24.png";
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	if($users->POWER_DNS_INSTALLED){
		if($EnablePDNS==1){$EnableDNSMASQ==0;}
	}
	
	if($EnableDNSMASQ==0){
		$color_dnsmasq="#898989";
		$icon_dnsmasq="arrow-right-24-grey.png";
	}
	
	$js_powerdns="GotoNetworkPowerDNS()";
	$js_status_powerdns="GotoNetworkPowerDNSStatus()";
	$js_log_powerdns="GotoNetworkPowerDNSLOGS()";
	$icon_powerdns="arrow-right-24.png";
	$iconL_powerdns="arrow-right-16.png";
	$color_powerdns="black";
	
	if(!$users->POWER_DNS_INSTALLED){
		$js_powerdns="blur();";
		$js_status_powerdns="blur()";
		$js_log_powerdns="blur()";
		
		$color_powerdns="#898989";
		$icon_powerdns="arrow-right-24-grey.png";
		$iconL_powerdns="arrow-right-16-grey.png";
	}else{
		if($EnablePDNS==0){
			$color_powerdns="#898989";
			$icon_powerdns="arrow-right-24-grey.png";
			$iconL_powerdns="arrow-right-16-grey.png";
		}
		
	
	}
	
	if(!$users->AsDnsAdministrator){
		$color_dnsmasq="#898989";
		$icon_dnsmasq="arrow-right-24-grey.png";
		$js_dnsmasq="blur()";
		$js_powerdns="blur();";
		$js_status_powerdns="blur()";
		$js_log_powerdns="blur()";
	}
	
	
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{DNS_SERVER}:</td>
	</tr>";
		
	$tr[]="
	<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$icon_dnsmasq'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;$color_dnsmasq'>".texttooltip("{DNS_SERVER} ({simple})",
				"position:right:{DNS_SERVER_EXPLAIN_TINY}",$js_dnsmasq)."</td>
		</tr>";
		
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_powerdns'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_powerdns'>".texttooltip("PowerDNS",
			"position:right:{APP_POWERADMIN_TEXT}","$js_status_powerdns")."</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><table style='width:100%'>
			<tr>
				<td valign='middle' style='width:16px'><img src='img/$iconL_powerdns'></td>				
				<td valign='middle' style='font-size:16px;width:99%;color:$color_powerdns'>".texttooltip("{parameters}",
				"position:right:{status} PowerDNS","$js_powerdns")."</td>
			</tr>
			<tr>
				<td valign='middle' style='width:16px'><img src='img/$iconL_powerdns'></td>				
				<td valign='middle' style='font-size:16px;width:99%;color:$color_powerdns'>".texttooltip("{events}",
				"position:right:{events} PowerDNS","$js_log_powerdns")."</td>
			</tr>						
			</table>
		</td>
	</tr>
	";	
		
	
	

	
	
	
	$tr[]="
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{network_services}:</td>
	</tr>";
	
	
	if($users->AsSystemAdministrator){
		if($users->SQUID_INSTALLED){
			if($SQUIDEnable==0){
			$tr[]="<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/$icon'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{proxy_service}",
					"position:right:{proxy_service}","Loadjs('squid.newbee.php?js_enable_disable_squid=yes')")."</td>
			</tr>";	
	
			}
		}
	}
	
	if($users->dhcp_installed){
		$color_dhcpd="black";
		$js_dhcpd="GotoNetworkDHCPD()";
		$icon_dhcpd="arrow-right-24.png";
		$EnableDHCPServer=intval($sock->GET_INFO('EnableDHCPServer'));
		
		if($EnableDHCPServer==0){
			$color_dhcpd="#898989";
			$icon_dhcpd="arrow-right-24-grey.png";
		}
		if(!$users->AsSystemAdministrator){
			$color_dhcpd="#898989";
			$icon_dhcpd="arrow-right-24-grey.png";
			$js_dhcpd="blur()";
		}
		
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_dhcpd'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_dhcpd'>".texttooltip("{APP_DHCP}",
			"position:right:{APP_DHCP}",$js_dhcpd)."</td>
	</tr>";
	
	
	}
	

	

	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_OPENSSH}",
			"position:right:{APP_OPENSSH}","GotoSSHD()")."</td>
	</tr>";
		
	if($users->RDPPROXY_INSTALLED){
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_RDPPROXY}",
				"position:right:{APP_RDPPROXY}","GotToRDPPROX()")."</td>
				</tr>";
	
	
	}
	
	if($users->HAPROXY_INSTALLED){
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{load_balancing}",
				"position:right:{load_balancing}","GotToHAPROXY()")."</td>
				</tr>";
	
	
	}
	
	//$SquidAllow80Port
	
	$nginx_icon="arrow-right-24.png";
	$nginx_js="GotoReverseProxy()";
	$nginx_color="black";
	
	if($EnableNginx==0){
		$nginx_icon="arrow-right-24-grey.png";
		$nginx_color="#898989";
	}
	
	if($SquidAllow80Port==1){
		$nginx_icon="arrow-right-24-grey.png";
		$nginx_color="#898989";
		$nginx_js="blur()";
	}
	
	if(!$users->NGINX_INSTALLED){
		$nginx_icon="arrow-right-24-grey.png";
		$nginx_color="#898989";
		$nginx_js="blur()";		
		
	}
	
	if(!$users->AsWebMaster){
		$nginx_icon="arrow-right-24-grey.png";
		$nginx_color="#898989";
		$nginx_js="blur()";		
		
	}
	
	$tr[]="
		<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/$nginx_icon'>
		</td>
			<td valign='middle' style='font-size:18px;width:99%;color:$nginx_color'>".texttooltip("Reverse Proxy",
			"position:right:Reverse Proxy",$nginx_js)."</td>
		</tr>";
	
	
	$color_unifi="black";
	$js_unifi="GotoUnifi()";
	$icon_unifi="arrow-right-24.png";
	$EnableUnifiController=intval($sock->GET_INFO("EnableUnifiController"));
	$is_installed=$sock->getFrameWork("unifi.php?is_installed=yes");
	
	if($EnableUnifiController==0){
		$color_unifi="#898989";
		$icon_unifi="arrow-right-24-grey.png";
	
	}
	if($is_installed<>"TRUE"){
		$color_unifi="#898989";
		$icon_unifi="arrow-right-24-grey.png";
	
	}
	
	if(!$users->AsSystemAdministrator){
		$color_unifi="#898989";
		$icon_unifi="arrow-right-24-grey.png";
		$js_unifi="blur()";
	}	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_unifi'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_unifi'>".texttooltip("{UNIFI_CONTROLLER}",
			"position:right:{UNIFI_CONTROLLER_EXPLAIN}","GotoUnifi()")."</td>
		</tr>";	

	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black;padding-top:20px'>{databases}:</td>
	</tr>";
	
	
	
	$openldap_icon="arrow-right-24.png";
	$openldap_js="GotoOpenLDAP()";
	$openldap_color="black";
	$openldap_explain="{APP_LDAP_DB_EXPLAIN}";
	if($EnableIntelCeleron==1){
		if($users->SQUID_INSTALLED){
			$openldap_icon="arrow-right-24-grey.png";
			$openldap_js="blur();";
			$openldap_explain="{ERROR_FEATURE_CELERON}";
			$openldap_color="#898989";
		}
	}
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$openldap_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$openldap_color'>".texttooltip("{APP_LDAP_DB}",
			"position:right:$openldap_explain",$openldap_js)."</td>
	</tr>";
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_MYSQL}",
			"position:right:MySQL","GotToMySQL()")."</td>
	</tr>";	
	
	

	

	
	
	$icon_freewebs=$icon;
	$text_freewebs="{webservers_section_explain}";
	$color_freewebs="#000000";
	$js_freewebs="GotToFreeWeb()";
	
	$icon_wp=$icon;
	$text_wp="{webservers_section_explain}";
	$color_wp="#000000";
	$js_wp="GotToWordpress()";
	
	$icon_wi=$icon;
	$text_wi="{web_interface_settings_text}";
	$color_wi="#000000";
	$js_wi="GotoArticaWebConsole()";
	
	
	if($sock->SquidPerformance>2){
		$icon_freewebs="arrow-right-24-grey.png";
		$text_freewebs="<strong style='color:white'>{ERROR_FEATURE_MINIMAL_PERFORMANCES}</strong><br>{webservers_section_explain}";
		$color_freewebs="#898989";
		$js_freewebs="blur()";
		
		$icon_wp="arrow-right-24-grey.png";
		$text_wp="<strong style='color:white'>{ERROR_FEATURE_MINIMAL_PERFORMANCES}</strong><br>{webservers_section_explain}";
		$color_wp="#898989";
		$js_wp="blur()";
	
	
	}
	
	if($SquidAllow80Port==1){
		$icon_freewebs="arrow-right-24-grey.png";
		$color_freewebs="#898989";
		$js_freewebs="blur()";
	}
	
	
	
	if($sock->EnableIntelCeleron==1){
		$icon_freewebs="arrow-right-24-grey.png";
		$text_freewebs="<strong style='color:white'>{ERROR_FEATURE_CELERON}</strong><br>{webservers_section_explain}";
		$color_freewebs="#898989";
		$js_freewebs="blur()";
		
		$icon_wp="arrow-right-24-grey.png";
		$text_wp="<strong style='color:white'>{ERROR_FEATURE_CELERON}</strong><br>{webservers_section_explain}";
		$color_wp="#898989";
		$js_wp="blur()";
	}

	if(!$users->AsSystemAdministrator){
		$icon_wi="arrow-right-24-grey.png";
		$text_wi="{web_interface_settings_text}";
		$color_wi="#898989";
		$js_wi="blur()";
		
		
	}
	
	
	$tr[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black;padding-top:20px'>{web_services}:</td>
	</tr>";	
	$tr[]="
	<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$icon_wi'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>
				".texttooltip("{web_interface_settings}","position:right:$text_wi",$js_wi)."
		</td>
	</tr>";
	
	
	
	if($users->APACHE_INSTALLED){
		if($users->AsAnAdministratorGeneric){
				
			$tr[]="<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/$icon_freewebs'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{webservers}",
					"position:right:$text_freewebs",$js_freewebs)."</td>
			</tr>";
						
			$tr[]="<tr>
				<td valign='middle' style='width:25px'>
				<img src='img/$icon_wp'>
				</td>
				<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("Wordpress",
						"position:right:$text_wp",$js_wp)."</td>
			</tr>";
	
		}
		}	
	
	
	
	
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black;padding-top:20px'>{crypt_and_vpn}:</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_OPENVPN}",
			"position:right:{APP_OPENVPN}","GotoNetworkOpenVPN()")."</td>
				</tr>";
	
	if($users->stunnel4_installed){
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{SSL_TUNNELS}",
				"position:right:{SSL_TUNNELS}","GotoStunnels()")."</td>
				</tr>";
	}
	
	if($users->HAMACHI_INSTALLED){
		if($users->AsSystemAdministrator){
			$tr[]="<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/$icon'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_HAMACHI}",
					"position:right:{APP_HAMACHI}","GotoNetworkHamachi()")."</td>
	</tr>";
		}
	}
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black;padding-top:20px'>{others_services}:</td>
	</tr>";
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_CLAMAV}",
			"position:right:{APP_CLAMAV}","GotoClamdSection()")."
		</td>
	</tr>";
	

	
	
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{general_settings}",
			"position:right:{general_settings}","GotoSystemGlobalParameters()")."</td>
				</tr>";	
	
	
	
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
	
	
	
	
}

