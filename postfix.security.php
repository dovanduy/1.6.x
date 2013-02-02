<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.clamav.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tab"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["status-list"])){status_list();exit;}
	
	
	
	if(isset($_GET["antivirus"])){section_antivirus();exit;}
	if(isset($_GET["status-pattern"])){section_pattern();exit;}
	if(isset($_GET["clamav-pattern"])){ClamAVPatterns();exit;}
	if(isset($_GET["spamass-pattern"])){SpamAsssPatterns();exit;}
	if(isset($_GET["kav-pattern"])){KavAVPatterns();exit;}
	if(isset($_GET["antispam-content"])){section_content_filtering();exit;}
	

	
js();
	
function js(){
	$page=CurrentPageName();
	if(isset($_GET["font-size"])){$fontsize="&font-size={$_GET["font-size"]}";$height="100%";}
	$html="
	$('#BodyContent').load('$page?tab=yes$fontsize');
	
	";
	
	echo $html;
	
	
}
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$filters_settings=$tpl->_ENGINE_parse_body('{antispam_filters}');
	$array["synthesis"]='{synthesis}';
	$array["postfix"]='{mta_policies}';
	$array["status"]='{mailplugins}';
	
	
	$array["antispam-content"]="{content_filtering}";
	$array["filters-connect"]="{filters_connect}";
	$array["antivirus"]="antivirus";
	//$array["status-pattern"]="{patterns_versions}";
	$hostname=$_GET["hostname"];
	$height="850px";
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$height="100%";}

	while (list ($num, $ligne) = each ($array) ){
		if($num=="antispam"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.index.php?main=filters\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="filters-connect"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.index.php?main=filters-connect\"><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="postfix"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.index.php?main=security_settings&hostname=master\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="synthesis"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.synthesis.php?hostname=$hostname\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_postfix_security style='width:100%;height:$height;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_postfix_security\").tabs();});
			
			QuickLinkShow('quicklinks-security');
			
		</script>";		
}

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
$html="<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend>{product}:</td>
				<td>". Field_text("objects-$t",null,"font-size:16px",null,null,null,false,"Searchobjects$t(event)")."</td>
				<td>". button("{search}","SearchobjectsList$t()")."</td>
			</tr>
			<tr>
				<td colspan=2>
				
				<table style='width:100%'>
				<tr>
					<td class=legend>{installed}</td>
					<td width=1%>". Field_checkbox("installed-$t",1, 0,"SearchobjectsList$t()")."</td>
					<td class=legend>{enabled}</td>
					<td width=1%>". Field_checkbox("enabled-$t",1, 0,"SearchobjectsList$t()")."</td>					
				</tr>
				</table>
				</td>
			</tr>
		</tbody>
	</table>
	<div id='object-$t-list' style='width:100%;height:550px;overflow:auto'></div>
<script>
function Searchobjects$t(e){
	if(checkEnter(e)){SearchobjectsList$t();}
}
	
function SearchobjectsList$t(){ 
		var installed=0;
		var enabled=0;
	    var se=escape(document.getElementById('objects-$t').value);
	    if(document.getElementById('installed-$t').checked){installed=1;}
	    if(document.getElementById('enabled-$t').checked){enabled=1;}
		LoadAjax('object-$t-list','$page?status-list=yes&search='+se+'&installed='+installed+'&enabled='+enabled);
	
}

var x_SaveObjectMainForm= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);}
	var ID='{$_GET["ID"]}';
	if(ID==0){YahooWin4Hide();}	
	SearchobjectsList();		
}		


function SaveObjectMainForm(){
	var XHR = new XHRConnection();
	XHR.appendData('ObjectName',document.getElementById('ObjectName').value);
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('instance','{$_GET["instance"]}');
	XHR.sendAndLoad('$page', 'POST',x_SaveObjectMainForm);
}		
	SearchobjectsList$t();
</script>
";

echo $tpl->_ENGINE_parse_body($html);
	
}


function status_list(){
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	$sock=new sockets();	
	$tpl=new templates();

	
	
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	$sock=new sockets();
	$EnableArticaSMTPFilter=$sock->GET_INFO("EnableArticaSMTPFilter");
	$EnableArticaSMTPFilter=0;
	$EnableArticaPolicyFilter=$sock->GET_INFO("EnableArticaPolicyFilter");
	$EnableArticaPolicyFilter=0;
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$amavis=Paragraphe_switch_disable('{enable_amavis}','{feature_not_installed}','{feature_not_installed}');
	$assp=Paragraphe_switch_disable('{enable_assp}','{feature_not_installed}','{feature_not_installed}');
	$main=new maincf_multi("master");
	$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));	
	

	
	$array["APP_POSTFWD2"]["INSTALLED"]=True;
	$array["APP_POSTFWD2"]["NAME"]="APP_POSTFWD2";	
	$array["APP_POSTFWD2"]["ENABLED"]=$array_filters["APP_POSTFWD2"];
	$array["APP_POSTFWD2"]["TEXT"]="POSTFWD2_ABOUT";
	$array["APP_POSTFWD2"]["JS"]="Loadjs('postfwd2.php?instance=master&with-popup=yes')";
	
	$array["MILTER_GREYLIST"]["INSTALLED"]=False;
	$array["MILTER_GREYLIST"]["NAME"]="APP_MILTERGREYLIST";
	
	$array["AMAVISD"]["INSTALLED"]=False;
	$array["AMAVISD_MILTER"]["INSTALLED"]=False;
	$array["AMAVISD"]["NAME"]="APP_AMAVISD_NEW";
	
	
	
	$array["SPAMASSASSIN"]["INSTALLED"]=False;
	$array["SPAMASS_MILTER"]["INSTALLED"]=False;
	$array["CLAMAV"]["INSTALLED"]=False;
	
	
	

	$array["APP_MILTER_DKIM"]["INSTALLED"]=False;
	$array["APP_MILTER_DKIM"]["NAME"]="APP_MILTER_DKIM";
	$array["APP_MILTER_DKIM"]["TEXT"]="dkim_about";	
	
	$array["FRESHCLAM"]["INSTALLED"]=False;
	$array["FRESHCLAM"]["NAME"]="APP_FRESHCLAM";	
	
	$array["APP_CLUEBRINGER"]["INSTALLED"]=False;
	
	$array["DKIM_FILTER"]["INSTALLED"]=False;
	$array["DKIM_FILTER"]["NAME"]="APP_DKIM_FILTER";
	
	
	$array["SPFMILTER"]["INSTALLED"]=False;
	$array["SPFMILTER"]["NAME"]="APP_SPFMILTER";
	$array["APP_CLUEBRINGER"]["NAME"]="APP_CLUEBRINGER";
	

	
	$array["MAILSPY"]["INSTALLED"]=False;
	$array["MAILSPY"]["NAME"]="APP_MAILSPY";

	
	$array["KAVMILTER"]["INSTALLED"]=False;
	$array["KAS_MILTER"]["INSTALLED"]=False;
	$array["KAS3"]["INSTALLED"]=False;
	
	$array["BOGOM"]["INSTALLED"]=False;
	$array["BOGOM"]["NAME"]="APP_BOGOM";
	
	
	
	$array["POLICYD_WEIGHT"]["INSTALLED"]=False;
	

	
	
	//$array["APP_ARTICA_POLICY"]["INSTALLED"]=False;	
	
	
	$array["AMAVISD_MILTER"]["NAME"]="APP_AMAVISD_MILTER";
	$array["KAS3"]["NAME"]="APP_KAS3_MILTER";	
	$array["SPAMASS_MILTER"]["NAME"]="APP_SPAMASS_MILTER";
	$array["SPAMASSASSIN"]["NAME"]="APP_SPAMASSASSIN";

	$array["KAVMILTER"]["NAME"]="APP_KAVMILTER";
	$array["KAS_MILTER"]["NAME"]="APP_KAS3_MILTER";
	$array["ASSP"]["INSTALLED"]=False;
	$array["ASSP"]["NAME"]="APP_ASSP";	

	if($users->SPAMASS_MILTER_INSTALLED){
		$array["SPAMASS_MILTER"]["INSTALLED"]=true;
		$array["SPAMASS_MILTER"]["TEXT"]="feature_not_installed";
		$array["SPAMASS_MILTER"]["ENABLED"]=$users->SpamAssMilterEnabled;
		$array["SPAMASS_MILTER"]["TOKEN"]="enable_spamassassin";
		$array["SPAMASS_MILTER"]["JS"]="Loadjs('postfix.index.php?script=antispam')";
	}
	
	if($users->DKIMFILTER_INSTALLED){
		$array["DKIM_FILTER"]["INSTALLED"]=true;
	}
	
	
	if($users->spamassassin_installed){
		$APP_SPAMASSASSIN_TEXT=$tpl->_ENGINE_parse_body("{APP_SPAMASSASSIN_TEXT}");
		$spamassassin_in_amavis_text=$tpl->_ENGINE_parse_body("{spamassassin_in_amavis_text}");
		$array["SPAMASSASSIN"]["INSTALLED"]=True;
		$array["SPAMASSASSIN"]["TEXT"]="$APP_SPAMASSASSIN_TEXT<br>$spamassassin_in_amavis_text";
		$array["SPAMASSASSIN"]["ENABLED"]=$users->EnableAmavisDaemon;
		$array["SPAMASSASSIN"]["LOCK"]=true;
		$array["SPAMASSASSIN"]["JS"]="Loadjs('postfix.index.php?script=antispam')";
		
	}else{
		$array["SPAMASSASSIN"]["JS-INSTALL"]="Loadjs('spamassassin.install.php');";
		//APP_SPAMASSASSIN
	}
	
	
	
	if($users->AMAVIS_INSTALLED){
		$array["AMAVISD"]["INSTALLED"]=true;
		$array["AMAVISD"]["TEXT"]="enable_amavis_text";
		$array["AMAVISD"]["ENABLED"]=$users->EnableAmavisDaemon;
		$array["AMAVISD"]["TOKEN"]="enable_amavis";	
		$array["AMAVISD"]["JS"]="Loadjs('amavis.index.php?ajax=yes')";
		
		//	
		if($users->EnableAmavisDaemon==1){
			$array["SPAMASS_MILTER"]["INSTALLED"]=true;
			$array["SPAMASS_MILTER"]["TEXT"]="spamassassin_in_amavis_text";
			$array["SPAMASS_MILTER"]["ENABLED"]=0;
			$array["SPAMASS_MILTER"]["LOCK"]=true;	
					
			
		}
	}else{
		$array["AMAVISD"]["JS-INSTALL"]="Loadjs('amavisd.install.php');";
	}
	
	
	if($users->CLAMD_INSTALLED){
		$EnableClamavDaemon=$sock->GET_INFO("EnableClamavDaemon");
		if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
		$array["CLAMAV"]["JS"]="Loadjs('clamav.enable.php')";
		$array["CLAMAV"]["INSTALLED"]=True;
		$array["CLAMAV"]["ENABLED"]=$EnableClamavDaemon;
		$array["CLAMAV"]["TOKEN"]="EnableClamavDaemon";
	}
	
	if($users->MILTERGREYLIST_INSTALLED){
		$array["MILTER_GREYLIST"]["INSTALLED"]=True;
		$array["MILTER_GREYLIST"]["TEXT"]="enable_miltergreylist_text";
		$array["MILTER_GREYLIST"]["ENABLED"]=$users->MilterGreyListEnabled;
		$array["MILTER_GREYLIST"]["TOKEN"]="MilterGreyListEnabled";
		$array["MILTER_GREYLIST"]["JS"]="Loadjs('postfix.index.php?script=antispam')";
		
		//MilterGreyListEnabled
		
	}
	
	if($users->KAV_MILTER_INSTALLED){
		$array["KAVMILTER"]["INSTALLED"]=True;
		$array["KAVMILTER"]["TEXT"]="enable_kavmilter_text";
		$array["KAVMILTER"]["ENABLED"]=$users->KAVMILTER_ENABLED;
		$array["KAVMILTER"]["TOKEN"]="kavmilterEnable";
		$array["KAVMILTER"]["JS"]="Loadjs('postfix.index.php?script=antispam')";
		
		//kavmilterEnable
	}

	if($users->kas_installed){	
		$array["KAS3"]["INSTALLED"]=True;
		$array["KAS3"]["TEXT"]="enable_kaspersky_as_text";
		$array["KAS3"]["ENABLED"]=$users->KasxFilterEnabled;	
		$array["KAS3"]["TOKEN"]="enable_kaspersky_as";
		$array["KAS3"]["JS"]="Loadjs('postfix.index.php?script=antispam')";

			
		//enable_kaspersky_as
	}
	
	//
	
	
/*	if($users->ASSP_INSTALLED){
		$sock=new sockets();
		$EnableASSP=$sock->GET_INFO('EnableASSP');
		$assp=Paragraphe_switch_img('{enable_assp}','{enable_assp_text}','EnableASSP',$EnableASSP,'{enable_disable}',290);
	}
*/	

	
	if(!$users->MEM_HIGER_1G){
		$array["AMAVISD"]["TEXT"]="ressources_insuffisantes";
		$array["SPAMASS_MILTER"]["TEXT"]="ressources_insuffisantes";
		$array["SPAMASSASSIN"]["TEXT"]="ressources_insuffisantes";
	}

	
	if($users->KASPERSKY_SMTP_APPLIANCE){
		unset($array["AMAVISD"]);
		unset($array["SPAMASS_MILTER"]);
		unset($array["SPAMASSASSIN"]);
	}
	
	if($users->CLUEBRINGER_INSTALLED){
		$array["APP_CLUEBRINGER"]["INSTALLED"]=True;
		$array["APP_CLUEBRINGER"]["TEXT"]="enable_cluebringer_text";
		$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
		$array["APP_CLUEBRINGER"]["ENABLED"]=$EnableCluebringer;
		$array["APP_CLUEBRINGER"]["TOKEN"]="EnableCluebringer";
		
		
		//
		
		
	}	
	
	
	if(is_file("ressources/logs/global.status.ini")){
		$ini=new Bs_IniHandler("ressources/logs/global.status.ini");
	}else{
		writelogs("ressources/logs/global.status.ini no such file");
		$sock=new sockets();
		$datas=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
		$ini=new Bs_IniHandler($datas);
	}
	
	$sock=new sockets();
	$datas=$sock->getFrameWork('cmd.php?refresh-status=yes');
	//$activate=Paragraphe('64-folder-install.png','{AS_ACTIVATE}','{AS_ACTIVATE_TEXT}',"javascript:Loadjs('postfix.index.php?script=antispam')",null,210,null,0,true);
	$tr[]=DAEMON_STATUS_ROUND($ligne,$ini,null,1);
	
	
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{service}</th>
	<th>{version}</th>
	<th>{installed}</th>
	<th>{enabled}</th>
	<th>{running}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	//print_r($ini->_params);
	
	
	if($_GET["search"]<>null){
		$search="*{$_GET["search"]}*";
		$search=str_replace(".", "\.",$search);
		$search=str_replace("(", "\(",$search);
		$search=str_replace(")", "\)",$search);
		$search=str_replace("**", "*",$search);
		$search=str_replace("**", "*",$search);
		$search=str_replace("*", ".*?",$search);
		
	}
	
	while (list ($key, $arrayConf) = each ($array) ){
		$servicename=null;
		$enabled=NULL;
		$installed=null;
		$fontColor="black";
		$version="&nbsp;";
		$running=null;
		$since=null;
		$memory=null;
		$text=null;
		$ahref=null;
		if(isset($ini->_params[$key]["service_name"])){$servicename="{{$ini->_params[$key]["service_name"]}}";}
		if($servicename==null){
			if(isset($arrayConf["NAME"])){$servicename="{{$arrayConf["NAME"]}}";}
		}
		if($servicename==null){$servicename=$key;}
		$servicename=$tpl->_ENGINE_parse_body($servicename);
		
		
		
		
		if($arrayConf["INSTALLED"]){
			$installed="<img src='img/check-32.png'>";
		}else{
			if($ini->_params[$key]["application_installed"]==1){
				$installed="<img src='img/check-32.png'>";
				$arrayConf["JS"]=null;
			}
		}
		
		if(isset($arrayConf["JS-INSTALL"])){$arrayConf["JS"]=$arrayConf["JS-INSTALL"];}
		
		if(isset($ini->_params[$key]["master_version"])){$version=$ini->_params[$key]["master_version"];}
		$clickToInstall=null;
		
		if($installed==null){
			
		
			if($_GET["installed"]==1){continue;}
			if(!isset($arrayConf["JS-INSTALL"])){$fontColor="#B5B5B5";}
			$installed="&nbsp;";
			if(isset($arrayConf["TEXT"])){
				if(strpos($arrayConf["TEXT"], "}")>0){$text=$tpl->_ENGINE_parse_body($arrayConf["TEXT"]);}
				else{
					$text=$tpl->_ENGINE_parse_body("{{$arrayConf["TEXT"]}}");
				}
				$text="<hr>".wordwrap($text,95,"<br>")."";
				$text=str_replace("\n", "", $text);
				$text=str_replace("\r", "", $text);	
				$text=str_replace("<br><br>", "<br>", $text);			
			}
			if(isset($arrayConf["JS-INSTALL"])){$clickToInstall="&nbsp;<span style='font-color:#C50000;font-size:10px'>{click_to_install}</strong><br>";}
			
			$text="{feature_not_installed}$text";
		}
		
		if(isset($arrayConf["ENABLED"])){
			if($arrayConf["ENABLED"]==1){
				$enabled=imgtootltip("check-32.png","{enable_disable}","Loadjs('postfix.index.php?script=antispam')");
			}else{
				if($_GET["enabled"]==1){continue;}
				$enabled=imgtootltip("check-32-grey.png","{enable_disable}","Loadjs('postfix.index.php?script=antispam')");
			}
			
		}else{
			if(isset($ini->_params[$key]["service_disabled"])){
				if($ini->_params[$key]["service_disabled"]==1){
					if($_GET["enabled"]==1){continue;}
					$enabled=imgtootltip("check-32.png","{enable_disable}","Loadjs('postfix.index.php?script=antispam')");
				}
			}
			
		}
		
		if($search<>null){
			if(!preg_match("#$search#i", $servicename)){continue;}
		}
		
		
		
		
		if($enabled<>null){
			if(isset($ini->_params[$key]["running"])){
				if($ini->_params[$key]["running"]==1){
				$running="<img src='img/ok32.png'>";
				$memory="&nbsp;{memory}:<span style='font-weight:bolder'> ".FormatBytes($ini->_params[$key]["master_memory"])." </span>/ {virtual_memory}: <span style='font-weight:bolder'>".FormatBytes($ini->_params[$key]["master_cached_memory"])."</span> ";
				$since="&nbsp;{since}: <span style='font-weight:bolder;color:#AB3106'>{$ini->_params[$key]["uptime"]}</span>";
				}else{
					$running="<img src='img/okdanger32.png'>";
				}
			}
		}
		
		if($text==null){if(isset($arrayConf["TEXT"])){
			$text=$tpl->_ENGINE_parse_body("{{$arrayConf["TEXT"]}}");
			$text=str_replace("\n", "", $text);
			$text=wordwrap($text,95,"<br>");
			$text=str_replace("\n", "", $text);
			$text=str_replace("\r", "", $text);
			$text=str_replace("<br><br>", "<br>", $text);
		}}
		
		if($arrayConf["JS"]<>null){
			$ahref="<a href=\"javascript:blur();\" OnClick=\"javascript:{$arrayConf["JS"]}\"
			style='font-size:14px;color:$fontColor;text-decoration:underline;font-weight:bold'>";
		}
		
		
		if($running==null){$running="<img src='img/ok32-grey.png'>";}
		if($enabled==null){$enabled="&nbsp;";}
		if($memory==null){$memory="&nbsp;";}
		if($since==null){$since="&nbsp;";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(strlen($text)>290){$text=substr($text, 0,290)."...";}
		$html=$html."
		<tr class=$classtr>
			<td width=100% align='left'>
				<strong style='font-size:14px;color:$fontColor' align='left'>$ahref$servicename$clickToInstall</a></strong>
				<div style='font-size:11px;color:$fontColor;'>$text$memory$since</div>
				
				</a></td>
			<td width=1% align='left'><strong style='font-size:14px;color:$fontColor' align='center'>$version</a></td>
			<td width=1% nowrap align='center'>$installed</a></td>
			<td width=1% nowrap align='center'>$enabled</td>
			<td  width=1% nowrap align='center'>$running</td>
			
		</tr>";		
		
	}
	

	
	
	
	
$datas=$tpl->_ENGINE_parse_body($html."</table>");		
echo $datas;	
}

function section_content_filtering(){
	$spamassassin="yes";
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	$sock=new sockets();	
	$SpamAssMilterEnabled=$sock->GET_INFO("SpamAssMilterEnabled");
	$ou_encoded=base64_encode("_Global");
	
	$keywords=Paragraphe('keywords-64.png','{block_keywords}','{block_keywords_text}',"javascript:Loadjs('spamassassin.keywords.php')",null,210,null,0,true);
	$keywords_disabled=Paragraphe('keywords-64-grey.png','{block_keywords}','{block_keywords_text}',"javascript:blur()",null,210,null,0,true);
	$global_smtp_rules=Buildicon64('DEF_ICO_POSTFIX_REGEX');
	$extensions_block=Paragraphe("bg_forbiden-attachmt-64.png","{attachment_blocking}","{attachment_blocking_text}","javascript:Loadjs('domains.edit.attachblocking.ou.php?ou=$ou_encoded')",null,210,null,0,true);
	$tests_eml=Paragraphe("email-info-64.png","{message_analyze}","{message_as_analyze_text}","javascript:Loadjs('spamassassin.analyze.php')",null,210,null,0,true);
	$tests_eml_disabled=Paragraphe("email-info-64-grey.png","{message_analyze}","{message_as_analyze_text}","",null,210,null,0,true);
	$message_analyze=$tests_eml;
	$sa_update_disabled=Paragraphe("64-spam-update-grey.png","{UPDATE_SA_UPDATE}","{UPDATE_SA_UPDATE_TEXT}","",null,210,null,0,true);
	$sa_update=Paragraphe("64-spam-update.png","{UPDATE_SA_UPDATE}","{UPDATE_SA_UPDATE_TEXT}","javascript:Loadjs('sa.update.php')",null,210,null,0,true);
	
	
	$sa_rules=Paragraphe("script-64.png","{SPAMASSASSIN_RULES}","{SPAMASSASSIN_RULES_TEXT}","javascript:Loadjs('spamassassin.rules.php?byid=main_config_postfix_security')",null,210,null,0,true);
	$sa_rules_disabled=Paragraphe("script-64-grey.png","{SPAMASSASSIN_RULES}","{SPAMASSASSIN_RULES_TEXT}","",null,210,null,0,true);
	
	
	$spamassassin=Paragraphe('folder-64-spamassassin.png','{APP_SPAMASSASSIN}','{SPAMASSASSIN_TEXT}',"javascript:Loadjs('spamassassin.index.php')",null,210,null,0,true);
	$spamassassin_disabled=Paragraphe('folder-64-spamassassin-64.png','{APP_SPAMASSASSIN}','{SPAMASSASSIN_TEXT}',"javascript:blur()",null,210,null,0,true);
	
	
	$kas3=Paragraphe('folder-caterpillar.png','{APP_KAS3}','{KAS3_TEXT}','javascript:Loadjs("kas.group.rules.php?ajax=yes")',null,210,null,0,true);
	$kas3_disabled=Paragraphe('folder-caterpillar-grey.png','{APP_KAS3}','{KAS3_TEXT}','javascript:blur()',null,210,null,0,true);
	
	
	
	$amavis=Paragraphe('64-amavis.png','{APP_AMAVISD_NEW}','{APP_AMAVISD_NEW_ICON_TEXT}',"javascript:Loadjs('amavis.index.php?ajax=yes')",null,210,100,0,true);
	
	$amavis_disabled=Paragraphe('64-amavis-grey.png','{APP_AMAVISD_NEW}','{feature_not_installed}',"",null,210,100,0,true);
	
	
	$mimedefang=Paragraphe('folder-64-mimedefang.png','{APP_MIMEDEFANG}','{MIMEDEFANG_TEXT}','mimedefang.index.php',null,210,100,0,true);
	$mailspy=Paragraphe('64-milterspy.png','{APP_MAILSPY}','{APP_MAILSPY_TEXT}','mailspy.index.php',null,210,100,0,true);
	$install=Buildicon64("DEF_ICO_CONTROLCENTER");
	$milter_script=Paragraphe('64-milter-behavior.png','{plugins_behavior}','{plugins_behavior_text}',"javascript:Loadjs('postfix.index.php?script=milterbehavior')",null,210,100,0,true);
	$plugins_activate=Paragraphe('folder-lego.png','{postfix_plugins}','{postfix_plugins_text}',"javascript:Loadjs('postfix.plugins.php?js=yes')",null,210,100,0,true);
	$wbl=Buildicon64('DEF_ICO_MAIL_WBL');
	$quarantine=Paragraphe('folder-quarantine-0-64.png','{quarantine_and_backup_storage}','{quarantine_and_backup_storage_text}',"javascript:Loadjs('quarantine.php?script=quarantine')",null,210,100,0,true);
	$apply=applysettings_postfix(true) ;
	$assp=Buildicon64("DEF_ICO_ASSP");
	$quarantine_admin=Paragraphe("biohazard-64.png","{all_quarantines}","{all_quarantines_text}","javascript:Loadjs('domains.quarantine.php?js=yes&Master=yes')",null,210,100,0,true);
	$quarantine_report=Paragraphe("biohazard-settings-64.png","{quarantine_reports}","{quarantine_reports_text}","javascript:Loadjs('domains.quarantine.php?js=yes&MailSettings=yes')",null,210,100,0,true);
	
	$quarantine_policies=Paragraphe("script-64.png","{quarantine_policies}","{quarantine_policies_text}",
			"javascript:Loadjs('quarantine.policies.php')",null,210,null,0,true);	
	
	
	
	if($spamassassin<>null){
		if(!$users->AMAVIS_INSTALLED){
			if($users->SPAMASS_MILTER_INSTALLED){
				if($SpamAssMilterEnabled<>1){
					$keywords=$keywords_disabled;
					$message_analyze=$tests_eml_disabled;
					$sa_update=$sa_update_disabled;
					$sa_rules=$sa_rules_disabled;
				}
			}else{
				$keywords=$keywords_disabled;
				$message_analyze=$tests_eml_disabled;
				$sa_update=$sa_update_disabled;
				$sa_rules=$sa_rules_disabled;
			}
		}
		
		if($users->AMAVIS_INSTALLED){
			if($users->EnableAmavisDaemon<>1){
				if($users->SPAMASS_MILTER_INSTALLED){
					if($SpamAssMilterEnabled<>1){
						$keywords=$keywords_disabled;
						$message_analyze=$tests_eml_disabled;
						$sa_update=$sa_update_disabled;
						$sa_rules=$sa_rules_disabled;
						}	
				}else{
					$keywords=$keywords_disabled;
					$message_analyze=$tests_eml_disabled;
					$sa_update=$sa_update_disabled;
					$sa_rules=$sa_rules_disabled;
				}
			}
				
		}
	}

	if($users->KASPERSKY_SMTP_APPLIANCE){
		$keywords=null;
		$message_analyze=null;
	}	
	

	
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$SpamAssMilterEnabled=$sock->GET_INFO("SpamAssMilterEnabled");
	
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	if($users->KASPERSKY_SMTP_APPLIANCE){return filters_section_kaspersky();}
	if(!$users->ASSP_INSTALLED){$assp=null;}
	
	if($users->EnableAmavisDaemon==0){$amavis=$amavis_disabled;}
	if(!$users->AMAVIS_INSTALLED){$amavis=$amavis_disabled;}
	if(!$users->spamassassin_installed){$spamassassin=$spamassassin_disabled;}
	if(!$users->MEM_HIGER_1G){$spamassassin=$spamassassin_disabled;}
	if($users->KasxFilterEnabled<>1){$kas3=$kas3_disabled;}
	if($users->kas_installed<>1){$kas3=$kas3_disabled;}
	if(!$users->KAV_MILTER_INSTALLED){$kasper=$kas3_disabled;}
	if($users->KAVMILTER_ENABLED<>1){$kasper=$kas3_disabled;}
	if($users->EnableAmavisDaemon==0){$amavis=$amavis_disabled;}
	if(!$users->MEM_HIGER_1G){$amavis=$amavis_disabled;}
	if(!$users->AMAVIS_INSTALLED){$amavis=$amavis_disabled;}
	if($EnablePostfixMultiInstance==1){$amavis=$amavis_disabled;}
	if($users->MimeDefangEnabled<>1){$mimedefang=null;}
	if(!$users->MIMEDEFANG_INSTALLED){$mimedefang=null;}
	if(!$users->spamassassin_installed){$spamassassin=$spamassassin_disabled;}
	if(!$users->spamassassin_installed){$spamassassin=$spamassassin_disabled;}
	if($users->KasxFilterEnabled<>1){$kas3=$kas3_disabled;}
	if($users->kas_installed<>1){$kas3=$kas3_disabled;}
	if($users->ClamavMilterEnabled<>1){$clamav=null;}
	if(!$users->CLAMAV_MILTER_INSTALLED){$clamav=null;}
	if($EnablePostfixMultiInstance==1){$clamav=null;}
	if($users->MilterGreyListEnabled<>1){$mg=null;}
	if(!$users->MILTERGREYLIST_INSTALLED){$mg=null;}
	if($EnablePostfixMultiInstance==1){$mg=null;}
	if($users->EnableMilterSpyDaemon<>1){$mailspy=null;}
	if(!$users->MILTER_SPY_INSTALLED){$mailspy=null;}
	
	
	if($spamassassin<>null){
		if(!$users->AMAVIS_INSTALLED){
			if($users->SPAMASS_MILTER_INSTALLED){
				if($SpamAssMilterEnabled<>1){
					$spamassassin=$spamassassin_disabled;
				}
			}else{
				$spamassassin=$spamassassin_disabled;
	
			}
		}
	
		if($users->AMAVIS_INSTALLED){
			if($users->EnableAmavisDaemon<>1){
				if($users->SPAMASS_MILTER_INSTALLED){
					if($SpamAssMilterEnabled<>1){
						$spamassassin=$spamassassin_disabled;
	
					}
				}else{
					$spamassassin=$spamassassin_disabled;
						
				}
			}
	
		}
	}
	
	if($users->KASPERSKY_SMTP_APPLIANCE){
		$spamassassin=null;
		$keywords=null;
	}
	
	
	$tr[]=$keywords;
	$tr[]=$global_smtp_rules;
	$tr[]=$extensions_block;
	$tr[]=$message_analyze;
	$tr[]=$spamassassin;
	$tr[]=$sa_rules;
	$tr[]=$sa_update;
	
	$tr[]=$amavis;
	$tr[]=$assp;
	$tr[]=$kas3;
	
	
	$tr[]=$keywords;
	$tr[]=$quarantine_policies;
	$tr[]=$quarantine;
	$tr[]=$quarantine_admin;
	$tr[]=$quarantine_report;
	$tr[]=$wbl;
	$tr[]=$clamav;
	$tr[]=$mailspy;	
	
	
$tables[]="<table style='width:99%' class=form><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='middle' align='center'>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}
		}

if($t<3){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
	$t=time();			
$tables[]="</table>";	
$html=implode("\n",$tables);	
$html="<center><div style='width:700px'>$html</div>


</center>
";	
$tpl=new templates();
$datas=$tpl->_ENGINE_parse_body($html);		
echo $datas;	
}


function section_antivirus(){
	$users=new usersMenus();
	$sock=new sockets();
	$activate=Paragraphe('64-folder-install.png','{AS_ACTIVATE}','{AS_ACTIVATE_TEXT}',"javascript:Loadjs('postfix.index.php?script=antispam')",null,210,null,0,true);
	
		
	//$clamav_unofficial=Paragraphe("clamav-64.png","{clamav_unofficial}","{clamav_unofficial_text}",
	//"javascript:Loadjs('clamav.unofficial.php')",null,210,100,0,true);//
	
	$clamav_unofficial=Paragraphe("clamav-64.png","{APP_CLAMAV}","{APP_CLAMAV_TEXT}",
	"javascript:Loadjs('clamd.php')",null,210,100,0,true);//	
	
	

		
	$kasper=Paragraphe('icon-antivirus-64.png','{APP_KAVMILTER}','{APP_KAVMILTER_TEXT}',"javascript:Loadjs('milter.index.php?ajax=yes')",null,210,null,0,true);		

	if(!$users->CLAMD_INSTALLED){
				$clamav_unofficial=Paragraphe("clamav-64-grey.png","{APP_CLAMAV}","{APP_CLAMAV_TEXT}",
				"",null,210,100,0,true);
	}
	
	if($users->KASPERSKY_SMTP_APPLIANCE){$clamav_unofficial=null;}
	
	
	$kavmilterEnable=$sock->GET_INFO("kavmilterEnable");
	

	if(!$users->KAV_MILTER_INSTALLED){
		$kasper=Paragraphe('icon-antivirus-64-grey.png','{APP_KAVMILTER}','{error_module_not_installed}',"",null,210,null,0,true);
	}else{
		if($kavmilterEnable<>1){
			$kasper=Paragraphe('icon-antivirus-64-grey.png','{APP_KAVMILTER}','{error_module_not_enabled}',"",null,210,null,0,true);
		}
	}
	
	
	
	$tr[]=$kasper;	
	$tr[]=$clamav_unofficial;
	
$tables[]="<table style='width:70%' class=form><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='middle' align='center'>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}
		}

if($t<3){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>";	
$html=implode("\n",$tables);	
$html="<center>$html</center>";	
$tpl=new templates();
$datas=$tpl->_ENGINE_parse_body($html);		
echo $datas;	
	
	
}

function section_pattern(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<div id='kav-pattern'></div>
	<hr style='margin:10px'>
	<div id='spamass-pattern'></div>
	<hr style='margin:10px'>
	<div id='clamav-pattern'></div>
	
	
	<script>
		LoadAjax('kav-pattern','$page?kav-pattern=yes');
		LoadAjax('spamass-pattern','$page?spamass-pattern=yes');
		LoadAjax('clamav-pattern','$page?clamav-pattern=yes');
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function filters_section_kaspersky(){

	if(posix_getuid()==0){return null;}

	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$users=new usersMenus();
	$users->LoadModulesEnabled();



	$kas3=Paragraphe('folder-caterpillar.png','{APP_KAS3}','{KAS3_TEXT}','javascript:Loadjs("kas.group.rules.php?ajax=yes")',null,210,null,0,true);
	$kasper=Paragraphe('icon-antivirus-64.png','{APP_KAVMILTER}','{APP_KAVMILTER_TEXT}',"javascript:Loadjs('milter.index.php?ajax=yes')",null,210,null,0,true);
	$activate=Paragraphe('64-folder-install.png','{AS_ACTIVATE}','{AS_ACTIVATE_TEXT}',"javascript:Loadjs('$page?script=antispam')",null,210,null,0,true);
	$mailspy=Paragraphe('64-milterspy.png','{APP_MAILSPY}','{APP_MAILSPY_TEXT}','mailspy.index.php',null,210,100,0,true);
	$install=Buildicon64("DEF_ICO_CONTROLCENTER");
	$milter_script=Paragraphe('64-milter-behavior.png','{plugins_behavior}','{plugins_behavior_text}',"javascript:Loadjs('$page?script=milterbehavior')",null,210,100,0,true);
	$wbl=Buildicon64('DEF_ICO_MAIL_WBL');
	$quarantine=Paragraphe('folder-quarantine-0-64.png','{quarantine_and_backup_storage}','{quarantine_and_backup_storage_text}',"javascript:Loadjs('quarantine.php?script=quarantine')",null,210,100,0,true);
	$apply=applysettings_postfix(true) ;
	$assp=Buildicon64("DEF_ICO_ASSP");
	$quarantine_admin=Paragraphe("biohazard-64.png","{all_quarantines}","{all_quarantines_text}","javascript:Loadjs('domains.quarantine.php?js=yes&Master=yes')",null,210,100,0,true);
	$quarantine_report=Paragraphe("64-administrative-tools.png","{quarantine_reports}","{quarantine_reports_text}","javascript:Loadjs('domains.quarantine.php?js=yes&MailSettings=yes')",null,210,100,0,true);
	$quarantine_policies=Paragraphe("script-64.png","{quanrantine_policies}","{quanrantine_policies_text}",
			"javascript:Loadjs('quarantine.policies.php')",null,210,null,0,true);

	if($users->KasxFilterEnabled<>1){$kas3=null;}
	if($users->kas_installed<>1){$kas3=null;}
	if(!$users->KAV_MILTER_INSTALLED){$kasper=null;}
	if($users->KAVMILTER_ENABLED<>1){$kasper=null;}
	if($users->KasxFilterEnabled<>1){$kas3=null;}
	if($users->kas_installed<>1){$kas3=null;}
	if($users->KAVMILTER_ENABLED<>1){$kav=null;}
	if(!$users->KAV_MILTER_INSTALLED){$kav=null;}
	if($users->MilterGreyListEnabled<>1){$mg=nul;}
	if(!$users->MILTERGREYLIST_INSTALLED){$mg=null;}
	if($EnablePostfixMultiInstance==1){$mg=null;}
	if($users->EnableMilterSpyDaemon<>1){$mailspy=null;}
	if(!$users->MILTER_SPY_INSTALLED){$mailspy=null;}


	$tr[]=$apply;
	$tr[]=$activate;
	$tr[]=$milter_script;
	$tr[]=$kas3;
	$tr[]=$assp;
	$tr[]=$kasper;
	$tr[]=$quarantine_policies;
	$tr[]=$quarantine;
	$tr[]=$quarantine_admin;
	$tr[]=$quarantine_report;
	$tr[]=$wbl;
	$tr[]=$mailspy;


	$tables[]="<table style='width:70%' class=form><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}

	}
	if($t<3){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";
		}
	}

	$tables[]="</table>";

	$html=implode("\n",$tables);



	$tpl=new templates();
	$datas=$tpl->_ENGINE_parse_body($html,"postfix.plugins.php,domain.manage.org.index.php,domains.quarantine.php");
	return $datas;
}

function KavAVPatterns(){
	$icon="datasource-32.png";
	$sock=new sockets();
	$tpl=new templates();
	$kavPattern=$sock->getFrameWork("cmd.php?KavMilterDbVer=yes");
	$kas3Patterns=$sock->getFrameWork("cmd.php?Kas3DbVer=yes");

	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr><th colspan=3>Kaspersky</th></tr>
	<tr>
		<th>&nbsp;</th>
		<th>{date}</th>
		<th>{pattern}</th>
	</tr>
</thead>
<tbody class='tbody'>
		<tr class=oddRow>
			<td width=1%><img src='img/$icon'></td>
			<td style='font-size:14px;font-weight:bold' nowrap>$kavPattern</td>
			<td style='font-size:14px'>{APP_KAVMILTER}</td>
		</tr>
		<tr >
			<td width=1%><img src='img/$icon'></td>
			<td style='font-size:14px;font-weight:bold' nowrap>$kas3Patterns</td>
			<td style='font-size:14px'>{APP_KAS3}</td>
		</tr>		
		

</table>";	
	
	
		echo $tpl->_ENGINE_parse_body($html);
	
	
}



function SpamAsssPatterns(){
	$icon="datasource-32.png";
	$sock=new sockets();
	$tpl=new templates();
	$spamdb=$sock->getFrameWork("cmd.php?SpamAssDBVer=yes");
	

	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr><th colspan=3>Spamassassin</th></tr>
	<tr>
		<th>&nbsp;</th>
		<th>{date}</th>
		<th>{pattern}</th>
	</tr>
</thead>
<tbody class='tbody'>
		<tr class=oddRow>
			<td width=1%><img src='img/$icon'></td>
			<td style='font-size:14px;font-weight:bold' nowrap>$spamdb</td>
			<td style='font-size:14px'>{APP_SPAMASSASSIN}</td>
		</tr>
</table>";	
	
	
		echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function ClamAVPatterns(){
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->CLAMD_INSTALLED){return null;}
	if($users->KASPERSKY_SMTP_APPLIANCE){return;}
	
	$clam=new clamav();
	$array=$clam->LoadDatabasesStatus();
	if(!is_array($array)){return null;}
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr><th colspan=3>{APP_CLAMAV}</th></tr>
	<tr>
		<th>&nbsp;</th>
		<th>{date}</th>
		<th>{pattern}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	$icon="datasource-32.png";
	while (list ($file, $date) = each ($array) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html . "
		<tr class=$classtr>
			<td width=1%><img src='img/$icon'></td>
			<td style='font-size:14px;font-weight:bold' nowrap>{$date[1]}</td>
			<td style='font-size:14px'>{$date[0]}</td>
		</tr>
		";
		
	}
	
	$html=$html . "</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}