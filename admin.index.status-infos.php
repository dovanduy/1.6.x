<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){echo "window.location.href = 'logoff.php'";die();}


include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){writelogs("Redirect to users.index.php",__FUNCTION__,__FILE__,__LINE__);header('location:miniadm.php');exit;}

if(isset($_GET["showInfos"])){showInfos_js();exit;}

if(isset($_GET["showInfos-id"])){showInfos_popup();exit;}
if(isset($_POST["disable"])){disable();exit;}
if(isset($_GET["left-menus-services"])){left_menus_services();exit;}
if(isset($_GET["left-menus-actions"])){left_menus_actions();exit;}



page();

function page(){

$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();

$datas=base64_decode($sock->getFrameWork("status.php?cpu-check-nx=yes"));
if($datas<>null){NotifyAdmin("system-32.png","CPU Infos !",$datas,null);}

if(is_file("ressources/logs/INTERNET_FAILED")){NotifyAdmin("domain-whitelist-32.png","{INTERNET_FAILED}","{INTERNET_FAILED_TEXT}\n".@file_get_contents("ressources/logs/INTERNET_FAILED"),null);}

$services_next=$tpl->_ENGINE_parse_body("
<div style='font-size:16px;color:white'>{services}:</div>
<div id='left-menus-services'></div>
<div id='left-menus-actions'></div>

<script>
	var content=document.getElementById('left-menus-services').innerHTML;
	if(content.length<5){
		LoadAjaxWhite('left-menus-services','$page?left-menus-services=yes');
	}
	
</script>");

$q=new mysql();

$sql="DELETE FROM adminevents WHERE `text`='{websites_not_categorized_text}'";
$q->QUERY_SQL($sql,"artica_events");

$sql="SELECT * FROM adminevents WHERE enabled=1 ORDER BY zDate DESC LIMIT 0,50";
$results=$q->QUERY_SQL($sql,"artica_events");
$html="<table style='width:99%' class=form><tbody>";
$c=0;
$f=squid_filters_infos();
	if(is_array($f)){
		while (list ($num, $ligne) = each ($f) ){
			$c++;
			if($ligne["subject"]==null){continue;}
			$ligne["subject"]=$tpl->_ENGINE_parse_body($ligne["subject"]);
			$strlen=strlen($ligne["subject"]);
			$org_text=$ligne["subject"];
			if($strlen>25){$text=substr($ligne["subject"], 0,21)."...";}else{$text=$org_text;}
			
			$html=$html."
			<tr>
				<td width=1%><img src='img/{$ligne["icon"]}'></td>
				<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:{$ligne["js"]}\" style='font-size:11px;text-decoration:underline'>$text</a></td>
			</tr>
			";	
		}
	}

if(mysql_num_rows($results)==0){
	if($c==0){echo $services_next;return;}
	$html=$html."</tbody></table><hr>$services_next";echo $html;return;
}


while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	if($ligne["icon"]=="danger64.png"){$ligne["icon"]="danger32.png";}
	if($ligne["icon"]=="warning64.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="pluswarning64.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="danger32.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="license-error-64.png"){$ligne["icon"]="license-error-32.png";}
	
	$ligne["subject"]=$tpl->_ENGINE_parse_body($ligne["subject"]);
	$strlen=strlen($ligne["subject"]);
	$org_text=$ligne["subject"];
	if($strlen>25){$text=substr($ligne["subject"], 0,21)."...";}else{$text=$org_text;}
	$text=texttooltip($text,$org_text,"Loadjs('$page?showInfos={$ligne["zmd5"]}')",null,0,"font-size:11px;text-decoration:underline");
	$html=$html."
	<tr>
		<td width=1%><img src='img/{$ligne["icon"]}'></td>
		<td style='font-size:11px' nowrap>$text</td>
	</tr>
	";
	}





$html=$html."</tbody></table>
<div style='width:100%;text-align:right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjaxWhite('admin-left-infos','admin.index.status-infos.php');")."</div>
<hr>
$services_next


";

echo $tpl->_ENGINE_parse_body($html);

}

function left_menus_services(){
if(GET_CACHED(__FILE__, __FUNCTION__,null,false,1)){wizards();return;}
if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){wizards();return;}}	
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$t=time();
$OnlySMTP=false;
if($users->SMTP_APPLIANCE){$OnlySMTP=true;}
if($users->KASPERSKY_SMTP_APPLIANCE){$OnlySMTP=true;}

	if(!$users->SQUID_INSTALLED){
		if(!$users->POSTFIX_INSTALLED){
			if($users->SAMBA_INSTALLED){
				$ONLY_SAMBA=true;
			}
		}
	}
	
	$DisableFrontBrowseComputers=$sock->GET_INFO('DisableFrontBrowseComputers');
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");	
	if(!is_numeric($DisableFrontBrowseComputers)){$DisableFrontBrowseComputers=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	
	if($DisableFrontBrowseComputers==0){	
		if($ONLY_SAMBA){
			if($users->AsSambaAdministrator){
				$computers=left_menus_format("browse_computers","32-win-nic-browse.png","Loadjs('computer-browse.php');","browse_computers_text");
				
			}
		}
	}

	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	Paragraphe("database-connect-settings-64.png", "{APP_MYSQL}", "{APP_MYSQL_TEXT}","javascript:AnimateDiv('BodyContent');Loadjs('system.mysql.php');");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	
	
	
	if(!$users->WEBSTATS_APPLIANCE){
	if($EnableWebProxyStatsAppliance==1){
		if($users->AsWebStatisticsAdministrator ){
		$GLOBALS["ICON_FAMILY"]="STATISTICS";
		$js="SquidQuickLinks()";
		Paragraphe("statistics2-64.png", "{SQUID_STATS}", "{SQUID_STATS_TEXT}","javascript:$js");
		$squid_stats="
			<tr>
				<td width=1%>". imgtootltip("statistics2-32.png","{SQUID_STATS_TEXT}","SeTimeOutIMG32('{$t}0');SquidQuickLinks()",null,"{$t}0")."</td>
				<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
						OnClick=\"javascript:$js\" 
						style='font-size:11px;text-decoration:underline'>{SQUID_STATS1}</a></td>
			</tr>
						
		
		";
	}}
	
	if(!$users->KASPERSKY_WEB_APPLIANCE){
		if(!$users->SQUID_APPLIANCE){
			if($users->AsSambaAdministrator){
				if($users->SAMBA_INSTALLED){
					$samba=left_menus_format("APP_SAMBA","32-samba.png","QuickLinksSamba();");
				}
			}
			
		}
	}
	
		if($users->SQUID_INSTALLED){
			if($users->AsWebStatisticsAdministrator ){
				$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
				$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
				if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}				
				$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
				if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
				if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
				if($EnableRemoteStatisticsAppliance==0){
					if($DisableArticaProxyStatistics==0){
					if($SQUIDEnable==1){
						$GLOBALS["ICON_FAMILY"]="STATISTICS";
						$js="SquidQuickLinks()";
						Paragraphe("statistics2-64.png", "{SQUID_STATS}", "{SQUID_STATS_TEXT}","javascript:$js");	
						$squid_stats="
							<tr>
								<td width=1%>". imgtootltip("statistics2-32.png","{SQUID_STATS_TEXT}","SeTimeOutIMG32('{$t}1');SquidQuickLinks()",null,"{$t}1")."</td>
								<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
										OnClick=\"javascript:$js;\" 
										style='font-size:11px;text-decoration:underline'>{SQUID_STATS1}</a></td>
							</tr>
							";			
						}
					}
				}
			}
		}
}
	
	
	if($users->OCSI_INSTALLED){
		if($users->AsInventoryAdmin){
			$GLOBALS["ICON_FAMILY"]="COMPUTERS";
			Paragraphe("64-ocs.png", "{APP_OCSI}", "{APP_OCSI_TEXT}","javascript:Loadjs('ocs.ng.php')");
			$ocs="
				<tr>
					<td width=1%>". imgtootltip("32-ocs.png","{APP_OCSI}","SeTimeOutIMG32('{$t}2');Loadjs('ocs.ng.php?in-front-ajax=yes&newinterface=yes')",null,"{$t}2")."</td>
					<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
							OnClick=\"javascript:SeTimeOutIMG32('{$t}2');Loadjs('ocs.ng.php?in-front-ajax=yes&newinterface=yes');\" 
							style='font-size:11px;text-decoration:underline'>{APP_OCSI}</a></td>
				</tr>
				";			
		}
	}
	
	if($users->APACHE_INSTALLED){
		if($users->AsAnAdministratorGeneric){
		 if($EnableRemoteStatisticsAppliance==0){
				
				$freewebs="
					<tr>
						<td width=1%>". imgtootltip("free-web-32.png","FreeWebs","QuickLinkSystems('section_freeweb');",null,"{$t}3")."</td>
						<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
								OnClick=\"javascript:SeTimeOutIMG32('{$t}3');QuickLinkSystems('section_freeweb');;\" 
								style='font-size:11px;text-decoration:underline'>FreeWebs</a></td>
					</tr>
					";			
			}
		}
	}

	if($users->EJABBERD_INSTALLED){
		if($users->AsPostfixAdministrator){
			$ejabberd=left_menus_format("INSTANT_MESSAGING","jabberd-32.png","LoadAjax('BodyContent','ejabberd.php');QuickLinkShow('quicklinks-INSTANT_MESSAGING');");
		}
	}
	
	if($users->AMAVIS_INSTALLED){
		if($users->AsPostfixAdministrator){
			$amavis=left_menus_format("APP_AMAVISD_NEW","32-amavis.png","Loadjs('amavis.index.php?ajax=yes&in-front-ajax=yes');");
			
		}
		
	}
	
	if($users->POWER_DNS_INSTALLED){
		$pdns=left_menus_format("APP_PDNS","dns-32.png","LoadAjax('BodyContent','pdns.php?tabs=yes&expand=yes');QuickLinkShow('quicklinks-APP_PDNS');");
		
	}
	
	
	

	if($users->HAMACHI_INSTALLED){
		if($users->AsSystemAdministrator){
			$GLOBALS["ICON_FAMILY"]="NETWORK";
			Paragraphe("hamachi-logo-64.png", "{APP_HAMACHI}", "{APP_HAMACHI_TEXT}","javascript:Loadjs('hamachi.php?in-line=yes')");
			$hamachi="
				<tr>
					<td width=1%>". imgtootltip("hamachi-logo.png","{APP_HAMACHI}","SeTimeOutIMG32('{$t}4');Loadjs('hamachi.php?in-line=yes')",null,"{$t}4")."</td>
					<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
							OnClick=\"javascript:SeTimeOutIMG32('{$t}4');Loadjs('hamachi.php?in-line=yes');\" 
							style='font-size:11px;text-decoration:underline'>{APP_HAMACHI}</a></td>
				</tr>
				";		
		}
	}
	
	if($users->AsSystemAdministrator){	
		$GLOBALS["ICON_FAMILY"]="SYSTEM";
		$mysql="<tr>
			<td width=1%>". imgtootltip("database-connect-settings-32.png","{APP_MYSQL_TEXT}","SeTimeOutIMG32('{$t}5');Loadjs('system.mysql.php?tabsize=14');",null,"{$t}5")."</td>
			<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:SeTimeOutIMG32('{$t}5');AnimateDiv('BodyContent');Loadjs('system.mysql.php?tabsize=14');\" style='font-size:11px;text-decoration:underline'>{APP_MYSQL1}</a></td>
			</tr>";
		
		$ssh="<tr>
			<td width=1%>". imgtootltip("openssh-32.png","{APP_OPENSSH}","SeTimeOutIMG32('{$t}6');Loadjs('sshd.php?in-front-ajax=yes&tabsize=14');",null,"{$t}6")."</td>
			<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:SeTimeOutIMG32('{$t}6');AnimateDiv('BodyContent');Loadjs('sshd.php?in-front-ajax=yes&tabsize=14');\" 
				style='font-size:11px;text-decoration:underline'>{APP_OPENSSH}</a></td>
			</tr>";
		
		
	}
	
	if($users->AsSystemAdministrator){	
		if($users->OPENVPN_INSTALLED){
			$GLOBALS["ICON_FAMILY"]="NETWORK";
			$openvpn=left_menus_format("APP_OPENVPN","32-openvpn.png","Loadjs('index.openvpn.php?infront=yes')");
		}
	}
	
	if($users->AsSystemAdministrator){	
		if($users->crossroads_installed){
			$GLOBALS["ICON_FAMILY"]="NETWORK";
			$crossreads=left_menus_format("load_balancing","load-balance-32.png","Loadjs('crossroads.index.php?newinterface=yes')");
		}
		if($users->HAPROXY_INSTALLED){
			$haProxy=$tpl->_ENGINE_parse_body(left_menus_format("APP_HAPROXY","load-balance-32.png","Loadjs('haproxy.php')"));
		}
		
	}

	if($users->AsPostfixAdministrator){
		
		if($users->MILTERGREYLIST_INSTALLED){
			$miltergreylist=left_menus_format('APP_MILTERGREYLIST','32-milter-greylist.png',"QuickLinkSystems('section_mgreylist')","APP_MILTERGREYLIST");
			
		}
		
		if($users->MIMEDEFANG_INSTALLED){
			$mimedefang=left_menus_format('APP_MIMEDEFANG','mimedefang-32.png',"only:Loadjs('mimedefang.php?in-front-ajax=yes')","APP_MIMEDEFANG");
			
		}
		
		
		
		if($users->MAILMAN_INSTALLED){
			if(!$users->LIGHT_INSTALL){
				$mailman=left_menus_format('mailman','mailman-32.png',"only:Loadjs('mailman.php?script=yes')","manage_distribution_lists");
			}
		}
		if($users->fetchmail_installed){
			$fetchmail=left_menus_format("APP_FETCHMAIL","fetchmail-rule-32.png","LoadAjax('BodyContent','fetchmail.index.php?quicklinks=yes');QuickLinkShow('quicklinks-APP_FETCHMAIL');");
		}
		
	}
	
	
	$INSTANCEBKM=unserialize(stripslashes($_COOKIE["INSTANCEBKM"]));
	if(is_array($INSTANCEBKM)){
		if(count($INSTANCEBKM)>0){
			$bookmarks="<table style='width:99%' class=form><tbody>";
			while (list ($instances,$arr) = each ($INSTANCEBKM) ){
				$tb=explode(".", $instances);
				$js="Loadjs('domains.postfix.multi.config.php?in-front-ajax=yes&hostname=$instances&ou={$arr["ou"]}')";
				
			$bookmarks=$bookmarks."<tr>
			<td width=1%>". imgtootltip("32-network-server.png","{$tb[0]}","SeTimeOutIMG32('{$t}7');$js",null,"{$t}7")."</td>
			<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:SeTimeOutIMG32('{$t}7');AnimateDiv('BodyContent');$js;\" 
				style='font-size:11px;text-decoration:underline'>{$tb[0]}</a></td>
			</tr>";
			}
			
			$bookmarks=$bookmarks."</tbody></table>";
		}
	}
	
	
	if($users->WEBSTATS_APPLIANCE){
		$squid_stats=null;
		if($users->POSTFIX_INSTALLED){$postfix=left_menus_format("APP_POSTFIX","mass-mailing-postfix-32.png","QuickLinkPostfix()");}
		if($users->cyrus_imapd_installed){$cyrus=left_menus_format("mailboxes","32-mailbox.png","QuickLinkCyrus()");}
		
		
	}	
	
	if($users->AsSambaAdministrator){
		if($users->DROPBOX_INSTALLED){
			$dropbox=left_menus_format("APP_DROPBOX","dropbox-32.png","only:Loadjs('samba.dropbox.php')");
		}
	}
	
	
	
	if($users->AsSystemAdministrator){
		$logrotate=left_menus_format("system_logs","32-logs.png","Loadjs('logrotate.php?in-front-ajax=yes')");
		
		if($users->VMWARE_HOST){
			$vmware=left_menus_format("APP_VMTOOLS","vmware-logo-32.png","Loadjs('VMWareTools.php')");
		}
		
		
	}
	
	if($users->SQUID_INSTALLED){
		$license=left_menus_format("artica_license","32-key.png","only:Loadjs('artica.license.php')");
	}
	
	if($OnlySMTP){
		$samba=null;
		$computers=null;
		$fetchmail=null;
	}
	
	$html="
		$bookmarks
		<table style='width:99%' class=form>
		<tbody>
		$license
		$squid_stats
		$postfix
		$miltergreylist
		$amavis
		$mimedefang
		$cyrus
		$mailman
		$fetchmail
		$haProxy
		$crossreads
		$ssh	
		$openvpn
		$freewebs
		$pdns
		$ejabberd
		$computers
		$samba
		$dropbox
		$hamachi
		$ocs
		$mysql
		$logrotate
		$vmware
		</tbody>
		</table>
		
		
		<script>LoadAjaxWhite('left-menus-actions','$page?left-menus-actions=yes');</script>
		";
		$html=$tpl->_ENGINE_parse_body($html);
		SET_CACHED(__FILE__, __FUNCTION__, null, $html);
		echo $html;
		wizards();
	
}

function left_menus_format($text,$img,$js,$explain=null){
	$id=md5("$text,$img,$js,$explain");
	$animate="AnimateDiv('BodyContent');";
	if(preg_match("#only:(.+)#", $js,$re)){
		$js=$re[1];
		$animate=null;
	}

	$uri="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:{$animate}SeTimeOutIMG32('$id');$js;\" 
				style='font-size:11px;text-decoration:underline;text-transform:capitalize;'>{{$text}}</a>";
	
	if($explain<>null){
		$uri=texttooltip("{{$text}}","{{$explain}}","SeTimeOutIMG32('$id');$js",null,0,"font-size:11px;text-decoration:underline;text-transform:capitalize;");
	}
	
	
	return "<tr>
			<td width=1%>". imgtootltip("$img","{{$text}}","SeTimeOutIMG32('$id');$js",null,$id)."</td>
			<td style='font-size:11px' nowrap>$uri</td>
			</tr>";

}

function left_menus_actions(){
if(GET_CACHED(__FILE__, __FUNCTION__,null,false,1)){return;}	
if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){return;}}		
$sock=new sockets();
$users=new usersMenus();
$tpl=new templates();
$f=array();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
if($EnableWebProxyStatsAppliance==1){$users->SQUID_INSTALLED=true;}
$OnlySMTP=false;
if($users->SMTP_APPLIANCE){$OnlySMTP=true;}
if($users->KASPERSKY_SMTP_APPLIANCE){$OnlySMTP=true;}

	if($users->SQUID_INSTALLED){
		if($users->AsWebStatisticsAdministrator ){
			$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($EnableRemoteStatisticsAppliance==0){
				if($SQUIDEnable==1){
					$f[]=left_menus_format("CATEGORIZE_A_WEBSITE","32-categories-add.png","Loadjs('squid.visited.php?add-www=yes')","ADDWEBSITE_PROXY_EXPLAIN");
				}
			}
		}
	}



	if($users->POSTFIX_INSTALLED){
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==1){
			if($users->AsPostfixAdministrator){
				$f[]=left_menus_format("NEW_SMTP_INSTANCE","32-network-server-add.png","Loadjs('postfix.multiple.instances.wizard.php')");
			}
			
		}
		$EnableAmavisDaemon=trim($sock->GET_INFO("EnableAmavisDaemon",true));	
		if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}	
		
		if($users->AsAnAdministratorGeneric){
			$f[]=left_menus_format("check_recipients","check-32.png","only:Loadjs('postfix.debug.mx.php')");
			if($users->AMAVIS_INSTALLED){
				if($users->spamassassin_installed){
					if($EnableAmavisDaemon==1){
						$f[]=left_menus_format("SPAMASSASSIN_RULES","script-32.png","only:Loadjs('spamassassin.rules.php?byid=admin-start_page')");
					}
				}
			}
		}
		
	}
	

	
	if($users->AsAnAdministratorGeneric){
		if(!$OnlySMTP){$f[]=left_menus_format("explorer","explorer-32.png","only:Loadjs('tree.php');",'SHARE_FOLDER_TEXT');}
		$f[]=left_menus_format("add_user","member-add-32.png","only:Loadjs('create-user.php')","add user explain");
		if(!$OnlySMTP){$f[]=left_menus_format("ADD_COMPUTER","computer-32-add.png","only:YahooUser(870,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');","ADD_COMPUTER_TEXT");}
		if($users->POWER_DNS_INSTALLED){
			$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
			$EnablePDNS=$sock->GET_INFO("EnablePDNS");
			$PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSMySQLEngine");
			if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
			if(!is_numeric($PowerDNSMySQLEngine)){$PowerDNSMySQLEngine=1;}
			if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}	
			if($DisablePowerDnsManagement==0){
				if($EnablePDNS==1){
					if($PowerDNSMySQLEngine==1){
						$f[]=left_menus_format("new_dns_entry","filter-add-32.png","only:YahooWin5('550','pdns.mysql.php?item-id=0&t=$t','PowerDNS');","new_dns_entry");
					}
				}	

			}
		}

	
	}
	
	

	
	if(count($f)>0){
		$html="
		<hr>
		<div style='font-size:16px;color:white;margin-top:8px;text-transform:capitalize;'>{actions}:</div>
		<table style='width:99%' class=form><tbody>
		".@implode("\n", $f)."
		</tbody>
		</tbale;
		";
		$html= $tpl->_ENGINE_parse_body($html);
		SET_CACHED(__FILE__, __FUNCTION__, null, $html);
		echo $html;	
		
	}
	
	
	
}

function squid_filters_infos(){
	$sock=new sockets();
	$ligne2=array();
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	if(!$users->SQUID_INSTALLED){return null;}
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($SQUIDEnable==0){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:squid_filters_infos():: SQUIDEnable is not enabled... Aborting\n";}
		return;
	}	
	
	$sql="SELECT count(*) as tcount FROM `visited_sites` WHERE LENGTH(category)=0";	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("visited_sites")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("visited_sites")){return;}
	
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;}
	if($ligne["tcount"]==0){return null;}
	
	
	$ligne2[0]["icon"]="32-categories.png";
	$ligne2[0]["subject"]=$ligne["tcount"]." {websites_not_categorized}";
	$ligne2[0]["js"]="Loadjs('squid.visited.php?onlyNot=yes');";
	
	return $ligne2;

}


function showInfos_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM adminevents WHERE zmd5='{$_GET["showInfos"]}'","artica_events"));
	$title=$tpl->_ENGINE_parse_body($ligne["subject"]);
	$html="YahooWin('500','$page?showInfos-id={$_GET["showInfos"]}','$title')";
	echo $html;
}

function showInfos_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM adminevents WHERE zmd5='{$_GET["showInfos-id"]}'","artica_events"));
	$icon=$ligne["icon"];
	if(preg_match("#([0-9]+)#", $icon,$re)){
		$icon=str_replace($re[1], 64, $icon);
		if(!is_file("img/$icon")){$icon=$ligne["icon"];}
	}
	
	
	if($ligne["jslink"]<>null){
		$link="
		<tr>
			<td colspan=2 align='right'>
			<table style='width:100%'>
			<tbody>
				<tr>
					<td valign='middle' align='right' style='font-size:16px;font-weight:bold'>{goto}</td>
					<td width=1%>". imgtootltip("arrow-right-64.png","{goto}",$ligne["jslink"])."</td>
				</tr>
				</tbody>
			</table>
			</td>
		</tr>
		
		";
	}
	
	$title=$tpl->_ENGINE_parse_body($ligne["subject"]);	
	$html="<div style='font-size:18px;margin-bottom:20px'>$title</div>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td width=1% valign='top'><img src='img/$icon'></td>
		<td width=99%' valign='top'><div class=explain style='font-size:14px'>{$ligne["text"]}</div></td>
	</tr>$link
			<td colspan=2 align='left' style='font-size:16px;font-weight:bold'><a href=\"javascript:blur();\" OnClick=\"javascript:RemoveNotifAdmin()\"
			style='font-size:16px;font-weight:bold;text-decoration:underline'>{ihavereaditremove}</a>
			
			</td>
		</tr>
		
	</tbody>
	</table>
	<script>
	var x_RemoveNotifAdmin= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWinHide();
		LoadAjax('admin-left-infos','admin.index.status-infos.php');
	}		
	
	
	
	function RemoveNotifAdmin(){
		var XHR = new XHRConnection();
		XHR.appendData('disable','{$_GET["showInfos-id"]}');
		XHR.sendAndLoad('$page', 'POST',x_RemoveNotifAdmin);	
		
	}		
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function disable(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE adminevents SET enabled=0 WHERE zmd5='{$_POST["disable"]}'","artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}


function wizards(){
	$sock=new sockets();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$WizardNetLeaveUnconfigured=$sock->GET_INFO("WizardNetLeaveUnconfigured");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	if(!is_numeric($WizardNetLeaveUnconfigured)){$WizardNetLeaveUnconfigured=0;}		
	$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
	writelogs("network.php?fqdn=yes -> hostname=\"$hostname\"",__FUNCTION__,__FILE__,__LINE__);
	$mustchangeHostname=false;
	if(preg_match("#Name or service not known#", $hostname)){$hostname=trim($sock->GET_INFO("myhostname"));}
	if(preg_match("#locahost\.localdomain#", $hostname)){$mustchangeHostname=true;}
	if(preg_match("#localhost\.localdomain#", $hostname)){$mustchangeHostname=true;}
	if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $hostname)){$mustchangeHostname=true;}
	
	if(!$mustchangeHostname){if(preg_match("#locahost\.localdomain#", $users->hostname)){$mustchangeHostname=true;}}
	if(!$mustchangeHostname){if(strpos($hostname, ".")==0){$mustchangeHostname=true;}}
	
	if($mustchangeHostname){
	writelogs("hostname=\"$hostname\" mustchangeHostname=True",__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs("hostname=\"$hostname\" mustchangeHostname=False",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if($mustchangeHostname){echo "<script>Loadjs('admin.chHostname.php');</script>";}	
	
	if($WizardNetLeaveUnconfigured==0){
		$user=new usersMenus();
		if($user->VPS_OPENVZ){$sock->SET_INFO("WizardNetLeaveUnconfigured", 1);}
		$WizardNetLeaveUnconfigured=1;
	}
	
	
	if($DisableNetworksManagement==0){
		 if($WizardNetLeaveUnconfigured==0){
			if(!$mustchangeHostname){
				$q=new mysql();
				if($q->TestingConnection()){
					$countDeNIC=$q->COUNT_ROWS("nics", "artica_backup");
					if($q->ok){
						if($countDeNIC==0){echo "<script>Loadjs('admin.chNICs.php');</script>";}
					}
				}
			}
		}
	}	
	
}