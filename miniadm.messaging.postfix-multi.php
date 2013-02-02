<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!isset($_GET["hostname"])){header('location:miniadm.messaging.php');die();}
if($_GET["hostname"]==null){header('location:miniadm.messaging.php');die();}
if(!isset($_SESSION["POSTFIX_SERVERS"][$_GET["hostname"]])){header('location:miniadm.messaging.php');die();}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-tabs"])){messaging_tabs();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["postfix"])){section_postfix();exit;}
if(isset($_GET["security"])){security();exit;}
if(isset($_GET["queues"])){section_queues();exit;}
if(isset($_GET["wbl"])){section_wbl();exit;}
if(isset($_GET["filters"])){filters();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["transport"])){transport();exit;}

if(isset($_GET["security"])){security();}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&hostname={$_GET["hostname"]}')</script>", $content);
	echo $content;	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		
		<H1>{$_GET["hostname"]}</H1>
		<p>{MESSAGING_SERVICE_TEXT}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='messaging-$t'></div>
	
	<script>
		LoadAjax('messaging-$t','$page?messaging-tabs=yes&hostname={$_GET["hostname"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function messaging_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$t=time();
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["transport"]='{transport_settings}';
	$array["security"]='{security_settings}';
	$array["filters"]='{filters_settings}';
	
	
	
	$fontsize='15';
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		

			
		$tab[]="<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_miniadmpostfixInstance' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_miniadmpostfixInstance').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function section_postfix(){
	$t=time();
	$html="<div id='$t'></div>
	<script>
		$('#BodyContent').remove();
		document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
		AnimateDiv('BodyContent');Loadjs('postfix.index.php?font-size=14');
	</script>
	";
	echo $html;
	
	
}

function section_queues(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('postfix.queue.monitoring.php?inline-js=yes&font-size=14')
	</script>
	";
	echo $html;	
}
function section_wbl(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('whitelists.admin.php?js=yes&js-in-line=yes&font-size=14')
	</script>
	";
	echo $html;	
	
}
function section_postfwd2(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes')
	</script>
	";
	echo $html;	
}

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$_GET["ou"]=$_SESSION["ou"];
	$ou_encoded=base64_encode($_GET["ou"]);
	$delete_postfix_instance_sure=$tpl->javascript_parse_text("{delete_postfix_instance_sure}\n{$_GET["hostname"]}");
	$maincf=new maincf_multi($_GET["hostname"]);
	$enabled=1;
	if($maincf->GET("DisabledInstance")==1){$enabled=0;$fontcolor="#B3B3B3";}
	 
	if($enabled==1){
		
		$tr[]=Paragraphe("64-refresh.png", "{postfix_reload}",
				"{postfix_reload_text}","javascript:MultipleInstanceReload();");		
		
		
		$tr[]=Paragraphe("service-restart-64.png", "{postfix_restart}",
				"{postfix_restart_text}","javascript:MultipleInstanceRestart();");	
		
		$tr[]=Paragraphe("64-settings.png", "{postfix_reconfigure}",
				"{postfix_reconfigure_text}","javascript:MultipleInstanceReconfigure();");
	
		$tr[]=Paragraphe("refresh-queue-64.png", "{flush_queue}",
				"{flush_queue_text}","javascript:MultipleInstanceFlush();");

	}
	
	$tr[]=Paragraphe("delete-64.png", "{delete_postfix_instance}",
			"{delete_postfix_instance_text}","javascript:MultipleInstanceDelete();");
	

	$tr[]=Paragraphe("pause-64.png", "{pause_the_queue}",
			"{pause_the_queue_text}","javascript:Loadjs('postfix.freeze.queue.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");	
	
	$tr[]=Paragraphe("64-hd-delete.png", "{purge_all_queues}",
			"{purge_all_queues_text}","javascript:Loadjs('postfix.purge.queues.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");

	
	$tr[]=Paragraphe("test-message-64.png", "{send_test_message}",
			"{send_test_message_text}","javascript:Loadjs('postfix.multi-tests.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");
	
	$tr[]=Paragraphe("script-64.png", "{main.cf}",
			"{main.cf_explain}","javascript:Loadjs('postfix.main.cf.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");
	

	$tables[]="<table style='width:99%' class=form><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
	
	}
	if($t<2){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";
		}
	}
	
	$tables[]="</table>";
	$toolbox=implode("\n",$tables);
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		<div id='postfix-multi-toolbox'>
			$toolbox</div>
	</td>
	<td valign='top'>
		<div style='float:right'>". imgtootltip("20-refresh.png","{refresh}","MultipleInstanceRefreshEtat()")."</div>
		<div id='postfix-status-etat'></div>
	</td>
	</tr>
	</table>	
	<script>
	
	var x_MultipleInstanceReload=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>0){alert(tempvalue);}
	RefreshTabMainMultiConfigPostfix();
	}
	
	function MultipleInstanceReload(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('instance-reload','{$_GET["hostname"]}');
	AnimateDiv('postfix-multi-toolbox');
	XHR.sendAndLoad('domains.postfix.multi.php', 'GET',x_MultipleInstanceReload);
	}
	
	function MultipleInstanceRestart(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('instance-restart','{$_GET["hostname"]}');
	AnimateDiv('postfix-multi-toolbox');
	XHR.sendAndLoad('domains.postfix.multi.php', 'GET',x_MultipleInstanceReload);
	}
	
	function MultipleInstanceFlush(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('instance-flush','{$_GET["hostname"]}');
	AnimateDiv('postfix-multi-toolbox');
	XHR.sendAndLoad('domains.postfix.multi.php', 'GET',x_MultipleInstanceReload);
	}
	
	function MultipleInstanceReconfigure(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('instance-reconfigure','{$_GET["hostname"]}');
	AnimateDiv('postfix-multi-toolbox');
	XHR.sendAndLoad('domains.postfix.multi.php', 'GET',x_MultipleInstanceReload);
	}
	
	
	
	
	
	var x_MultipleInstanceDelete=function (obj) {
		var tempvalue=obj.responseText;
		if (tempvalue.length>0){alert(tempvalue);}
		if(document.getElementById('org_main')){
		RefreshTab('org_main');
		}else{
			Loadjs('domains.postfix.multi.php?ou=$ou_encoded&encoded=yes&in-front-ajax=yes');
		}
	}
	
	function MultipleInstanceRefreshEtat(){
		LoadAjax('postfix-status-etat','domains.postfix.multi.config.php?status-server=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
	
	}
	
	
			function MultipleInstanceDelete(){
			var a=confirm('$delete_postfix_instance_sure');
					if(a){
					var XHR = new XHRConnection();
					XHR.appendData('ou','{$_GET["ou"]}');
					XHR.appendData('hostname','{$_GET["hostname"]}');
					XHR.appendData('instance-kill','{$_GET["hostname"]}');
					AnimateDiv('postfix-multi-toolbox');
					XHR.sendAndLoad('domains.postfix.multi.php', 'GET',x_MultipleInstanceDelete);
	}
		
	
	}
	
	if(document.getElementById('admin-left-infos')){
	LoadAjax('admin-left-infos','admin.index.status-infos.php');
	}
	
	MultipleInstanceRefreshEtat();
	
	</script>
	
	";
	 
	 
	 
	 
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	SET_CACHED(__FILE__,__FUNCTION__,"{$_GET["hostname"]}&ou={$_GET["ou"]}",$html);	
	
}

function transport(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$_GET["ou"]=$_SESSION["ou"];
	$main=new maincf_multi($_GET["hostname"]);
	$myorigin=$main->GET("myorigin");
	if($myorigin==null){$myorigin="\$mydomain";}
	
	$postfix_network=$tpl->_ENGINE_parse_body("{postfix_network}");
	$myhostname=$tpl->_ENGINE_parse_body("{myhostname}");
	$relay_host=$tpl->_ENGINE_parse_body("{postfix_network}");
	$ou_encoded=base64_encode($_GET["ou"]);
	
	
	

	
	$tr[]=Paragraphe("folder-network-64.png", "{postfix_network}",
			"{postfix_network_text}","javascript:YahooWin5(634,'domains.postfix.multi.config.php?postfix-network=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}','$postfix_network');");
	
	$tr[]=Paragraphe("serv-mail-linux.png", "{smtp_virtual_hostname}",
			"{smtp_virtual_hostname_text}","javascript:YahooWin5(405,'domains.postfix.multi.config.php?postfix-hostname=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}','$myhostname')");
		
	
	$tr[]=Paragraphe("64-relayhost.png", "{relayhost_title}",
			"{relayhost_title_text}",
			"javascript:Loadjs('domains.postfix.multi.relayhost.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("user-internet-arrow-64.png", "{recipient_relay_table}",
			"{recipient_relay_table_text}",
			"javascript:Loadjs('postfix.routing.recipient.php?js=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("64-mailinglist.png", "{diffusion_lists}",
			"{diffusion_lists_text}",
			"javascript:Loadjs('postfix.routing.diffusion.php?js=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");

	$tr[]=Paragraphe("generic-maps-64.png", "{smtp_generic_maps}",
			"{smtp_generic_maps_text}",
			"javascript:Loadjs('postfix.smtp.generic.maps.php?ou=$ou_encoded')");
	
	$tr[]=Paragraphe("64-settings.png", "{title_postfix_tuning}",
			"{title_postfix_tuning_text}",
			"javascript:Loadjs('postfix.performances.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");

	$tr[]=Paragraphe("64-restore-mailbox.png", "{mailbox_agent}",
			"{mailbox_agent_text}",
			"javascript:Loadjs('postfix.mailbox_transport.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("banner-loupe-64.png", "{SMTP_BANNER}",
			"{SMTP_BANNER_TEXT}",
			"javascript:Loadjs('postfix.banner.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");	
	
		
	$tr[]=Paragraphe("mass-mailing-postfix-64.png", "{TEST_SMTP_CONNECTION}",
			"{TEST_SMTP_CONNECTION_TEXT}",
			"javascript:Loadjs('postfix.smtp-tests.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("64-advanced-routing.png", "{advanced_ISP_routing}",
			"{advanced_ISP_routing_text}",
			"javascript:Loadjs('postfix.isp-routing.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("64-computer-alias.png", "{load_balancing_compatibility}",
			"{load_balancing_compatibility_text}",
			"javascript:Loadjs('postfix.haproxy.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	

	$tr[]=Paragraphe("ecluse-64.png", "{domain_throttle}",
			"{domain_throttle_text}",
			"javascript:Loadjs('postfix.smtp.throttle.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");

	$tr[]=Paragraphe("bg_memory-64.png", "{postfix_tmpfs}",
			"{postfix_tmpfs_text}",
			"javascript:Loadjs('domains.postfix.memory.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");
	
	
	$tr[]=Paragraphe("databases-add-64.png", "{remote_users_databases}",
			"{remote_users_databases_text}",
			"javascript:Loadjs('postfix.smtp.db.maps.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");
	
	
	if($users->fetchmail_installed){
		$tr[]=Paragraphe("fetchmail-rule-64.png", "{APP_FETCHMAIL_TINY}",
				"{APP_FETCHMAIL_TEXT}",
				"javascript:Loadjs('domains.postfix.multi.fetchmail.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
				
	
	}
	
	$tr[]=Paragraphe("recup-remote-mail.png", "{POSTFIX_SMTP_NOTIFICATIONS}",
			"{POSTFIX_SMTP_NOTIFICATIONS_TEXT}",
			"javascript:Loadjs('postfix.notifs.php?hostname={$_GET["hostname"]}')");	

	$tr[]=Paragraphe("64-logs.png", "{POSTFIX_DEBUG}",
			"{POSTFIX_DEBUG_TEXT}",
			"javascript:Loadjs('postfix.debug.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')");
	
		

	
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$VirtualHostNameToChange=$main->GET("VirtualHostNameToChange");
	if($VirtualHostNameToChange<>null){$VirtualHostNameToChange="&nbsp;<span style='font-size:11px'>($VirtualHostNameToChange)</span>";}
	
	$html2="<div style='width:100%;font-size:16px;text-align:right'>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('domains.postfix.multi.myorigin.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}');\"
	style='font-size:16px;text-decoration:underline'>myorigin:$myorigin</a>&nbsp;|&nbsp;{$_GET["hostname"]}$VirtualHostNameToChange</div>";

	$html=$html2.CompileTr4($tr);
	
	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	SET_CACHED(__FILE__,__FUNCTION__,"{$_GET["hostname"]}&ou={$_GET["ou"]}",$html);	
	
}

function security(){
	$_GET["ou"]=$_SESSION["ou"];
	$tpl=new templates();
	$ou_encoded=base64_encode($_GET["ou"]);
	$users=new usersMenus();
	
	$tr[]=Paragraphe("folder-64-routing-secure.png", "{SASL_TITLE}",
			"{SASL_TEXT}",
			"javascript:Loadjs('domains.postfix.multi.sasl.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");


	$tr[]=Paragraphe("folder-64-routing-secure.png", "{SSL_ENABLE}",
			"{SSL_ENABLE_TEXT}",
			"javascript:Loadjs('domains.postfix.multi.ssl.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("64-key.png", "{certificate_infos}",
			"{certificate_infos_modify_text}",
			"javascript:Loadjs('domains.postfix.multi.certificate.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");
			
	$tr[]=Paragraphe("folder-64-restrictions-classes.png", "{messages_restriction}",
			"{messages_restriction_text}",
			"javascript:Loadjs('domains.postfix.multi.restriction.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
		
	$tr[]=Paragraphe("64-sender-check.png", "{smtpd_client_restrictions_icon}",
			"{smtpd_client_restrictions_icon_text}",
			"javascript:Loadjs('domains.postfix.multi.client.restriction.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	
	$tr[]=Paragraphe("bg_regex-64.png", "{global_smtp_rules}",
			"{global_smtp_rules_text}",
			"javascript:Loadjs('postfix.headers-body-checks.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
	

	
	if($users->POSTSCREEN_INSTALLED){
		
		$tr[]=Paragraphe("postscreen-64.png", "PostScreen",
				"{POSTSCREEN_MINI_TEXT}",
				"javascript:Loadjs('postscreen.php?ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')");
		
	
	}else{
		$tr[]=Paragraphe("postscreen-64-grey.png", "PostScreen",
				"{POSTSCREEN_MINI_TEXT}",
				"");
		
	}
	
	$tr[]=Paragraphe("gomme-64.png", "{HIDE_CLIENT_MUA}",
			"{HIDE_CLIENT_MUA_TEXT}",
			"javascript:Loadjs('domains.postfix.hide.headers.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");
		
	$html=CompileTr4($tr);
	
	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	SET_CACHED(__FILE__,__FUNCTION__,"{$_GET["hostname"]}&ou={$_GET["ou"]}",$html);
	
}
function filters(){
	$_GET["ou"]=$_SESSION["ou"];
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));
	$ou_encoded=base64_encode($_GET["ou"]);
	
	$KasxFilterEnabled=$sock->GET_INFO("KasxFilterEnabled");
	$kavmilterEnable=$sock->GET_INFO("kavmilterEnable");
	$EnableArticaSMTPFilter=$sock->GET_INFO("EnableArticaSMTPFilter");
	$EnableArticaSMTPFilter=0;
	$EnableDKFilter=$sock->GET_INFO("EnableDKFilter");
	$EnableDkimMilter=$sock->GET_INFO("EnableDkimMilter");
	$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
	$users=new usersMenus();
	$sock=new sockets();
	if($users->kas_installed){
		if($KasxFilterEnabled==1){
			$array["APP_KAS3"]=$array_filters["APP_KAS3"];
		}
	}
	
	if($users->KAV_MILTER_INSTALLED){if($kavmilterEnable==1){$array["APP_KAVMILTER"]=$array_filters["APP_KAVMILTER"];}}
	if($users->MILTERGREYLIST_INSTALLED){$array["APP_MILTERGREYLIST"]=$array_filters["APP_MILTERGREYLIST"];}
	if($users->AMAVIS_INSTALLED){$array["APP_AMAVIS"]=$array_filters["APP_AMAVIS"];}
	if($users->OPENDKIM_INSTALLED){if($EnableDKFilter==1){$array["APP_OPENDKIM"]=$array_filters["APP_OPENDKIM"];}}
	if($users->MILTER_DKIM_INSTALLED){if($EnableDkimMilter==1){$array["APP_MILTER_DKIM"]=$array_filters["APP_MILTER_DKIM"];}}
	if($users->CLUEBRINGER_INSTALLED){if($EnableCluebringer==1){$array["APP_CLUEBRINGER"]=$array_filters["APP_CLUEBRINGER"];}}
	if($EnableArticaSMTPFilter==1){$array["APP_ARTICA_FILTER"]=$array_filters["APP_ARTICA_FILTER"];}
	$array["APP_POSTFWD2"]=$array_filters["APP_POSTFWD2"];
	
	if($array["APP_KAS3"]==1){
		$tr[]=Paragraphe("folder-caterpillar-64.png", "{as_plugin}",
				"{kaspersky_anti_spam_text}",
				"javascript:Loadjs('domains.edit.kas.php?ou=$ou_encoded')");
	}
	
	
	if($array["APP_KAVMILTER"]==1){
		$tr[]=Paragraphe("icon-antivirus-64.png", "{antivirus}",
				"{antivirus_text}",
				"javascript:Loadjs('domains.edit.kavmilter.ou.php?ou=$ou_encoded')");
	
	}
	
	if($array["APP_MILTERGREYLIST"]==1){
	
		$tr[]=Paragraphe("64-milter-greylist.png", "{APP_MILTERGREYLIST}",
				"{APP_MILTERGREYLIST_TEXT}",
				"javascript:Loadjs('domains.postfix.multi.milter-greylist.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");

	}
	
	if($array["APP_AMAVIS"]==1){
		$tr[]=Paragraphe("64-amavis.png", "{APP_AMAVISD_NEW}",
				"{APP_AMAVISD_NEW_ICON_TEXT}",
				"javascript:Loadjs('domains.postfix.multi.amavis.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");
	}
	
	
	if($array["APP_POSTFWD2"]==1){
		
		$tr[]=Paragraphe("Firewall-Secure-64.png", "{APP_POSTFWD2}",
				"{APP_POSTFWD2_TEXT}",
				"javascript:Loadjs('postfwd2.php?ou=$ou_encoded&instance={$_GET["hostname"]}&byou=yes')");		
	
	}else{
		
		$tr[]=Paragraphe("Firewall-Secure-64-grey.png", "{APP_POSTFWD2}",
				"{APP_POSTFWD2_TEXT}",
				"javascript:Loadjs('postfwd2.php?ou=$ou_encoded&instance={$_GET["hostname"]}&byou=yes')");		

	
	}
	
	
	$tr[]=Paragraphe("bg_forbiden-attachmt-64.png", "{attachment_blocking}",
			"{attachment_blocking_text}",
			"javascript:Loadjs('domains.edit.attachblocking.ou.php?ou=$ou_encoded&hostname={$_GET["hostname"]}')");	
	

	$html=CompileTr4($tr);
	
	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	SET_CACHED(__FILE__,__FUNCTION__,"{$_GET["hostname"]}&ou={$_GET["ou"]}",$html);		
		
}
