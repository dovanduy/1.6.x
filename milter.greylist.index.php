<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	
	$user=new usersMenus();
	if(!isset($_GET["hostname"])){
		if($user->AsPostfixAdministrator==false){header('location:users.index.php');exit();}
	}else{
		if(!PostFixMultiVerifyRights()){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{$_GET["hostname"]}::{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	
	if(isset($_GET["greylist-config"])){popup_settings_tab_params();exit;}
	if(isset($_GET["main"])){main_switch();exit;}
	if(isset($_GET["index"])){popup();exit;}
	if(isset($_POST["SaveGeneralSettings"])){SaveConf();exit;}
	if(isset($_GET["add_acl"])){main_acladd();exit;}
	if(isset($_GET["explainThisacl"])){explainThisacl();exit;}
	if(isset($_GET["SaveAclID"])){SaveAclID();exit;}
	if(isset($_GET["acllist"])){echo main_acl_list();exit;}
	if(isset($_POST["DeleteAclID"])){DeleteAclID();exit;}
	if(isset($_GET["edit_dnsrbl"])){echo main_edit_dnsrbl();exit;}
	if(isset($_GET["dnsbllist"])){echo main_dnsrbl_list();exit;}
	if(isset($_GET["dnsrbl_subindex"])){echo SaveDnsrbl();exit;}
	if(isset($_GET["DeleteDnsbl"])){echo DeleteDnsbl();exit;}
	if(isset($_GET["BackToDNSBLDefault"])){BackToDNSBLDefault();exit;}
	if(isset($_GET["ChangeFormType"])){GetNewForm();exit;}
	if(isset($_GET["status"])){echo main_status();exit;}
	
	if(isset($_GET["acl-table-list"])){main_acl_table();exit;}
	if(isset($_POST["RemoteMilterService"])){popup_remote_save();exit;}
	
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["dumpfile-js"])){dumpfile_js();exit;}
	if(isset($_GET["dumpfile-popup"])){dumpfile_popup();exit;}
	if(isset($_GET["popup-page"])){main_tabs();exit;}
	if(isset($_GET["popup-settings"])){popup_settings();exit;}
	if(isset($_GET["popup-acl"])){popup_acl();exit;}
	if(isset($_GET["popup-save"])){popup_save();exit;}
	if(isset($_GET["popup-logs"])){popup_logs();exit;}
	if(isset($_GET["popup-dumpdb"])){popup_db();exit;}
	if(isset($_GET["browse-mgrey-list"])){popup_db_list();exit;}
	if(isset($_GET["popup-settings-js"])){popup_settings_js();exit;}
	
	if(isset($_GET["remote-js"])){popup_remote_js();exit;}
	if(isset($_GET["popup-remote"])){popup_remote();exit;}
	
	
	
	
function popup_settings_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$main_settings=$tpl->javascript_parse_text('{main_settings}');
	echo "YahooWin2(\"700\",\"$page?popup-settings=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\",\"$title $main_settings\");";	
	
}	

function popup_remote_save(){
	$sock=new sockets();
	$sock->SET_INFO("RemoteMilterService", $_POST["RemoteMilterService"]);
	$sock->getFrameWork("postfix.php?milters=yes");
	
}

function popup_remote_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$main_settings=$tpl->javascript_parse_text('{use_milter_remote_service}');
	echo "YahooWin2(\"700\",\"$page?popup-remote=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\",\"$title $main_settings\");";
		
}
function popup_remote(){
	$style="font-size:16px;vertical-align:top";
	$users=new usersMenus();
	if($_GET["hostname"]==null){
		$hostname="master";
		$_GET["hostname"]=$hostname;
	}
			
	$tpl=new templates();
	$pure=new milter_greylist();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$RemoteMilterService=$sock->GET_INFO("RemoteMilterService");
		
		$html="
		<div id='animate-$t' style='font-size:18px;margin:15px'>{server}:$hostname</div>
		<div class=explain style='font-size:16px'>{use_milter_remote_service_explain}</div>
		<div class=form style='width:95%'>
		<table style='width:100%'>
	
		<tr>
		<td $style align='right' nowrap  class=legend style='font-size:16px'>{value}:</strong></td>
		<td $style >" . Field_text('RemoteMilterService',$RemoteMilterService,'width:350px;font-size:22px;padding:10px',null,null)."
		</td>
		
		<tr>
		<td $style colspan=2 align='right' >
				<hr>
				". button("{apply}","MilterGreyListPrincipalSave$t()",16)."
	
				</tr>
				</table>
		</div>
		</div>
<script>
var x_MilterGreyListPrincipalSave$t= function (obj) {
	document.getElementById('animate-$t').innerHTML='';
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	
}
	
function MilterGreyListPrincipalSave$t(){
	var XHR = new XHRConnection();
	
	
	XHR.appendData('RemoteMilterService',document.getElementById('RemoteMilterService').value);
	XHR.appendData('hostname','$hostname');
	XHR.appendData('ou','{$_GET["ou"]}');
	AnimateDiv('animate-$t');
	XHR.sendAndLoad('$page', 'POST',x_MilterGreyListPrincipalSave$t);
}
</script>
				
			";
echo $tpl->_ENGINE_parse_body("$tabs$html");	
	
}
	
function js(){
	header("content-type: application/x-javascript");
	$content=file_get_contents("js/sqlgrey.js");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_MILTERGREYLIST}');

	$acl=$tpl->_ENGINE_parse_body('{acl}');
	$page=CurrentPageName();
	$start="StartMilterGreylistPage();";
	if(isset($_GET["in-front-ajax"])){$start="StartMilterGreyListInFront();";}
	
	$html="
	$content
	
	function StartMilterGreyListInFront(){
		$('#BodyContent').load('$page?popup-page=yes&expand=yes');
	}
	
	function StartMilterGreylistPage(){
		YahooWin('820','$page?popup-page=yes','$title');
		}
		
	function main_settings_greylist(){
		Loadjs('$page?popup-settings-js=yes&&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		
	
	}
	
	function main_accesslist_greylist(){
		YahooWin2(\"600\",\"$page?popup-acl=yes\",\"$acl $main_settings\")
	
	}	
	
	function main_events_greylist(){
		Loadjs('postfix.events.new.php?js-mgreylist=yes');
	}
	
	
	function LoadMilterGreyListAcl(index){
		YahooWin4(450,'$page?add_acl=true&num='+index+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$acl::N.'+index);	
	}

	function miltergreylist_status(){
		LoadAjax('mgreylist-status','$page?status=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
	
	}	
	


	
	$start
	";
	
	echo $html;
	
}

function dumpfile_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{MILTERGREYLIST_STATUSDUMP}');
	
	
	$page=CurrentPageName();
	$html="
	
	function StartMilterGreylistDumpPage(){
		YahooWin2('650','$page?dumpfile-popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');
		}
		

	StartMilterGreylistDumpPage();
	";
	
	echo $html;	
	
	
}


function popup_logs(){
	$sock=new sockets();
	$tpl=unserialize(base64_decode($sock->getFrameWork("cmd.php?postfix-tail=yes&filter=milter-greylist")));
		
	if(!is_array($tpl)){die("!!Err");}
	$tpl=array_reverse($tpl);
		while (list ($num, $ligne) = each ($tpl) ){
			if(trim($ligne==null)){continue;}
			$t=$t."<div><code style='font-size:10px'>$ligne</code></div>";
			
			
		}
		
	$html="<div style='font-size:16px'>{APP_MILTERGREYLIST} {events}</div>
	<div style='text-align:right'><a href='#' OnClick=\"javascript:main_events_greylist();\">{refresh}</a></div>
	<div style=width:95%;height:300px;overflow:auto;' class=form>
	$t
	</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function popup_save(){
	$milter=new milter_greylist();
	$datas=$milter->SaveToLdap();
	
	$tpl=explode("\n",$datas);
	if(!is_array($tpl)){die("!!Err");}
	$tpl=array_reverse($tpl);
		while (list ($num, $ligne) = each ($tpl) ){
			if(trim($ligne==null)){continue;}
			$t=$t."<div><code>$ligne</code></div>";
			
			
		}
		
	$html="<H1>{APP_MILTERGREYLIST}</H1>

	<div style=width:100%;height:300px;overflow:auto;'>
	$t
	</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function main_tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["index"]='{index}';
	$array["popup-groups"]='{objects}';
	$array["popup-acl"]='{acls}';
	$array["popup-dumpdb"]='{MILTERGREYLIST_STATUSDUMP}';
	
	if(isset($_GET["expand"])){$expdand="&expand=yes";}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-dumpdb"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"milter.greylist.dumpdb.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
			continue;			
		}
		
	if($num=="popup-groups"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"milter.greylist.objects.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
			continue;			
		}		
		
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_mgreylist style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mgreylist').tabs();
			});
		</script>";		
	
}



function popup(){
	$img="<img src='img/bg_sqlgrey-240.jpg'>";
	$page=CurrentPageName();
	$mg=Paragraphe('folder-mailbox-64.png','{main_settings}','{main_settings_text}',"javascript:main_settings_greylist()",null,210,100,0,true);
	//$mg1=Paragraphe('folder-rules2-64.png','{acl}','{acl_text}',"javascript:main_accesslist_greylist()",null,210,100,0,true);
	$mg2=Paragraphe('folder-logs-643.png','{events}','{events_text}',"javascript:main_events_greylist()",null,210,100,0,true);
	//$mg3=Buildicon64("DEF_ICO_EVENTS_MGREYLITS_DUMP");
	
	$m3=Paragraphe('add-64-on-right.png','{remote_server}','{use_milter_remote_service}',"javascript:Loadjs('$page?remote-js=yes')",null,210,100,0,true);
	
	if(is_file("ressources/logs/greylist-count-master.tot")){
	$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
	
	if(is_array($datas)){
		@unlink("ressources/logs/web/mgreylist.master1.db.png");
		$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/mgreylist.master1.db.png",0);
		$gp->xdata[]=$datas["GREYLISTED"];
		$gp->ydata[]="greylisted";	
		$gp->xdata[]=$datas["WHITELISTED"];
		$gp->ydata[]="whitelisted";				
		$gp->width=350;
		$gp->height=350;
		$gp->ViewValues=false;
		$gp->x_title="{status}";
		$gp->pie();			
		$imgG="<img src='ressources/logs/web/mgreylist.master1.db.png'>";
		
	}
	}
	
	
	$content="
	<table style='width:99%' class=form>
	<tr>
		<td style='vertical-align:top'>$mg</td>
		<td style='vertical-align:top'>$mg2</td>
	</tr>
		<td style='vertical-align:top'>$m3</td>
		<td style='vertical-align:top'></td>
	</table>
	";
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top'>
			$img
			<br>
			<div id='mgreylist-status'></div>
		</td>
		<td style='vertical-align:top'>
			$content<br><center>$imgG</center>
		</td>
	</tr>
	
	</table>
	
	<script>
		miltergreylist_status();
	</script>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function popup_settings(){
	
	$content="<div id='greylist_config'>".greylist_config_tab()."</div>";
	$html=$content;
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function popup_settings_tab_params(){
	
	$content=greylist_config(1);
	$html=$content;
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function popup_acl(){
	
	$html="<div id='greylist_config'>".main_acl(1)."</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}
	


function main_switch(){
	
	switch ($_GET["main"]) {
		case "yes":greylist_config();exit;break;
		case "logs":main_logs();exit;break;
		case "acl":main_acl();exit;break;
		case "conf":echo main_conf();exit;break;
		case "dnsrbl";echo main_dnsrbl();exit;
		default:
			break;
	}
	
	
}	

function main_status(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?milter-greylist-ini-status=yes')));
	$status=DAEMON_STATUS_ROUND("MILTER_GREYLIST",$ini);
	echo $tpl->_ENGINE_parse_body($status);
	}
	
	
function greylist_config_tab(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["greylist-config"]='{parameters}';
	$array["multiples-mx"]='{multiples_mx}';
	$font="style='font-size:14px'";
	
	$master=urlencode(base64_encode("master"));
	$suffix="&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}";
	while (list ($num, $ligne) = each ($array) ){
			if($num=="multiples-mx"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"domains.postfix.multi.milter-greylist.mxs.php?hostname=master&ou=$master\"><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"$page?$num=yes&hostname=master&ou=$master\"><span>$ligne</span></a></li>\n");

		
	}
	
	
	return build_artica_tabs($html, "main_config_mgreylistMConfig");
	
}


function greylist_config($noecho=0){
	$style="font-size:16px;vertical-align:top";
	$users=new usersMenus();
	if($_GET["hostname"]==null){
			$hostname=$users->hostname;
			$_GET["hostname"]=$hostname;}
			
	$hostname=$_GET["hostname"];
	$pure=new milter_greylist();
	$page=CurrentPageName();
	$t=time();
	$arraytime=array(
		"m"=>"{minutes}","h"=>"{hour}","d"=>"{day}"
	);
	
	
	if($hostname=="master"){
		$portF="
				<tr>
					<td $style align='right' nowrap  class=legend style='font-size:16px'>{useTCPPort}:</strong></td>
					<td $style >" . Field_checkbox('MilterGreyListUseTCPPort',1,$pure->MilterGreyListUseTCPPort,"CheckTCPPOrt$t()")."</td>
					<td $style ></td>
				</tr>
				<tr>
					<td $style align='right' nowrap  class=legend style='font-size:16px'>{listen_port}:</strong></td>
					<td $style >" . Field_text('MilterGeryListTCPPort',$pure->MilterGeryListTCPPort,'width:90px;font-size:14px')."</td>
					<td $style ></td>
				</tr>";
		
	}

	$html="
	<div id='animate-$t' style='font-size:18px;margin:15px'>{server}:$hostname</div>
	<div id='MilterGreyListConfigGeneSaveID0'>
	
	<input type='hidden' name='hostname' value='$hostname'>
	<input type='hidden' name='SaveGeneralSettings' value='yes'>
	<table style='width:99%' class=form>
	
<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{enable_milter}:</strong></td>
	<td $style >" . Field_checkbox('MilterGreyListEnabled',1,$pure->MilterGreyListEnabled,"CheckAll$t()")."</td>
	<td $style >". help_icon("{enable_milter_text}")."</td>
</tr>$portF
<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{add_default_nets}:</strong></td>
	<td $style >" . Field_checkbox('MiltergreyListAddDefaultNets',1,$pure->MiltergreyListAddDefaultNets)."</td>
	<td $style >". help_icon("{milter_greylist_add_default_net_explain}")."</td>
</tr>		
<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{remove_tuple}:</strong></td>
	<td $style >" . Field_checkbox('lazyaw',1,$pure->main_array["lazyaw"])."</td>
	<td $style >". help_icon("{remove_tuple_text}")."</td>
</tr>	
	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{timeout}:</strong></td>
	<td $style  colspan=2>" . Field_text('timeout',$pure->main_array["timeout"],'width:90px;font-size:16px',null,null,'{mgreylisttimeout_text}')."&nbsp;".
		Field_array_Hash($arraytime,'timeout_TIME',$pure->main_array["timeout_TIME"],"style:font-size:14px")."</td>
	</tr>

	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{greylist}:</strong></td>
	<td $style  colspan=2>
	
	" . Field_text('greylist',$pure->main_array["greylist"],'width:90px;font-size:16px',null,null,'{greylist_text}')."&nbsp;".
		Field_array_Hash($arraytime,'greylist_TIME',$pure->main_array["greylist_TIME"],"style:font-size:16px")."
	
	</td>
	</tr>
	
	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{autowhite}:</strong></td>
	<td $style  colspan=2>" . Field_text('autowhite',$pure->main_array["autowhite"],'width:90px;font-size:16px',null,null,'{autowhite_text}')."&nbsp;".
		Field_array_Hash($arraytime,'autowhite_TIME',$pure->main_array["autowhite_TIME"],"style:font-size:16px")."</td>
	</tr>
			

	<tr>
	<td $style colspan=3 align='right' >
	<hr>
	". button("{apply}","MilterGreyListPrincipalSave$t()",16)."
	
	</tr>
	</table></div>
	<script>
	var x_MilterGreyListPrincipalSave$t= function (obj) {
			document.getElementById('animate-$t').innerHTML='';
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('main_config_mgreylistMConfig');
			miltergreylist_status();
	}	
	
	function MilterGreyListPrincipalSave$t(){
		 var XHR = new XHRConnection();
		 XHR.appendData('SaveGeneralSettings','yes');
		 if(document.getElementById('MilterGreyListEnabled').checked){XHR.appendData('MilterGreyListEnabled',1);}else{XHR.appendData('MilterGreyListEnabled',0);}
		 if(document.getElementById('MiltergreyListAddDefaultNets').checked){XHR.appendData('MiltergreyListAddDefaultNets',1);}else{XHR.appendData('MiltergreyListAddDefaultNets',0);}
		 if(document.getElementById('lazyaw').checked){XHR.appendData('lazyaw',1);}else{XHR.appendData('lazyaw',0);}		
		 XHR.appendData('timeout',document.getElementById('timeout').value);
		 XHR.appendData('timeout_TIME',document.getElementById('timeout_TIME').value);
		 XHR.appendData('greylist',document.getElementById('greylist').value);
		 XHR.appendData('greylist_TIME',document.getElementById('greylist_TIME').value);
		 XHR.appendData('autowhite',document.getElementById('autowhite').value);
		 XHR.appendData('autowhite_TIME',document.getElementById('autowhite_TIME').value);
		 
		 if(document.getElementById('MilterGreyListUseTCPPort')){
		 	if(document.getElementById('MilterGreyListUseTCPPort').checked){
		 		 XHR.appendData('MilterGreyListUseTCPPort',1);
		 	}else{
		 		 XHR.appendData('MilterGreyListUseTCPPort',0);
		 		 
		 	}
		 	XHR.appendData('MilterGeryListTCPPort',document.getElementById('MilterGeryListTCPPort').value);
		 }
		 
		 XHR.appendData('hostname','$hostname');
		 XHR.appendData('ou','{$_GET["ou"]}');
		 AnimateDiv('animate-$t');
		 XHR.sendAndLoad('$page', 'POST',x_MilterGreyListPrincipalSave$t);
	}
	
	function CheckTCPPOrt$t(){
		 if(!document.getElementById('MilterGreyListUseTCPPort')){return;}
		 document.getElementById('MilterGeryListTCPPort').disabled=true;
		 if(document.getElementById('MilterGreyListUseTCPPort').checked){
		 	document.getElementById('MilterGeryListTCPPort').disabled=false;
		}
	}
	
	function CheckAll$t(){
		if(document.getElementById('MilterGreyListEnabled').checked){
			document.getElementById('MiltergreyListAddDefaultNets').disabled=false;
			document.getElementById('lazyaw').disabled=false;
			document.getElementById('timeout').disabled=false;
			document.getElementById('timeout_TIME').disabled=false;
			document.getElementById('greylist').disabled=false;
			document.getElementById('greylist_TIME').disabled=false;
			document.getElementById('autowhite').disabled=false;
			document.getElementById('autowhite_TIME').disabled=false;
			document.getElementById('MilterGreyListUseTCPPort').disabled=false;
		}else{
			document.getElementById('MiltergreyListAddDefaultNets').disabled=true;
			document.getElementById('lazyaw').disabled=true;
			document.getElementById('timeout').disabled=true;
			document.getElementById('timeout_TIME').disabled=true;
			document.getElementById('greylist').disabled=true;
			document.getElementById('greylist_TIME').disabled=true;
			document.getElementById('autowhite').disabled=true;
			document.getElementById('autowhite_TIME').disabled=true;
			document.getElementById('MilterGreyListUseTCPPort').disabled=true;		
		}
	

	}
	
	
	CheckAll$t();
	CheckTCPPOrt$t();
</script>						
			
	";
	if(isset($_GET["notab"])){$tabs=null;}
	$tpl=new templates();
	if($noecho==1){return $tpl->_ENGINE_parse_body($html);}
	echo $tpl->_ENGINE_parse_body("$tabs$html");
	
}

function SaveConf(){
	$mil=new milter_greylist();
	$sock=new sockets();
	$sock->SET_INFO("MilterGreyListEnabled",$_POST["MilterGreyListEnabled"]);
	unset($_GET["MilterGreyListEnabled"]);
	$mil->MiltergreyListAddDefaultNets=$_POST["MiltergreyListAddDefaultNets"];
	unset($_GET["MiltergreyListAddDefaultNets"]);
	
	if(isset($_POST["MilterGreyListUseTCPPort"])){
		$sock->SET_INFO("MilterGreyListUseTCPPort",$_POST["MilterGreyListUseTCPPort"]);
		$sock->SET_INFO("MilterGeryListTCPPort",$_POST["MilterGeryListTCPPort"]);
		unset($_GET["MilterGreyListUseTCPPort"]);
		unset($_GET["MilterGeryListTCPPort"]);
	}
	
	
	
	
	
	while (list ($num, $val) = each ($_POST) ){
		$mil->main_array[$num]=$val;
	}	

	$mil->SaveToLdap();
}

function main_acladd(){
	$mil=new milter_greylist();
	$page=CurrentPageName();
	$action=$mil->actionlist;
	$tpl=new templates();
	$t=time();
	unset($action["geoip"]);
	$sql="SELECT * FROM miltergreylist_acls WHERE ID='{$_GET["num"]}'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$method=$tpl->javascript_parse_text("{method}");
	$arrayd=Field_array_Hash(array(""=>"{select}","blacklist"=>"{blacklist}",
	'whitelist'=>"{whitelist}","greylist"=>"{greylist}"),"$t-mode",$ligne["method"],null,null,0,'width:110px;font-size:14px;padding:5px');
	
	
	$action["gpid"]="{objects_group}";
	$arrayf=Field_array_Hash($action,"$t-type",$ligne["type"],"explainThisacl$t();",null,0,'width:150px;font-size:14px;padding:5px');
	$ligne["pattern"]=trim($ligne["pattern"]);
	$id=time();
	$html="
	<div id='$id'>
	<input type='hidden' name='SaveAclID' id='SaveAclID' value='{$_GET["num"]}'>
	<input type='hidden' name='hostname-hidden' id='hostname-hidden' value='{$_GET["hostname"]}'>
		<table style='width:98%' class=form>
		<tbody>
			<tr>
				<td align='right' width=1% nowrap style='font-size:13px'><strong>{method}:</strong></td>
				<td><strong>$arrayd</strong></td>
			</tr>
			<tr>
				<td align='right' width=1% nowrap style='font-size:13px'><strong>{type_of_rule}:</strong></td>
				<td><strong>$arrayf</strong></td>
			</tr>
			<tr>
				<td colspan=2><div id='addform-$t'></div></td>
			</tr>	

	
		<tr>
				<td align='right' width=1% nowrap><strong style='font-size:13px'>{infos}:</strong></td>
				<td><textarea name='$t-infos' id='$t-infos' rows=1 style='width:100%;font-size:15px;font-weight:bold'>{$ligne["description"]}</textarea>
		</tr>	
	
<tr>
<td colspan=2 align='right'>
<hr>". button("{apply}","SaveMilterGreyListAclID$t()",16)."
</td>
</tr>
</table>
<div id='explainc-$t'></span>
</FORM>

<script>
	var x_SaveMilterGreyListAclID= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			YahooWin4Hide();
			$('#miltergrey-instances-list').flexReload();
		}		

	function SaveMilterGreyListAclID$t(){
		var mode=document.getElementById('$t-mode').value;
		if(mode.length==0){alert('$method = NULL');return;}
		var XHR = new XHRConnection();
		XHR.appendData('SaveAclID','{$_GET["num"]}');
		XHR.appendData('type',document.getElementById('$t-type').value);
		if(document.getElementById('pattern')){XHR.appendData('pattern',document.getElementById('pattern').value);}
		XHR.appendData('infos',document.getElementById('$t-infos').value);
		XHR.appendData('mode',document.getElementById('$t-mode').value);
		if(document.getElementById('dnsrbl_class')){
			XHR.appendData('dnsrbl_class',document.getElementById('dnsrbl_class').value);
			XHR.appendData('delay',document.getElementById('delay').value);
		}
		
		if(document.getElementById('gpid_class')){
			XHR.appendData('gpid_class',document.getElementById('gpid_class').value);
			XHR.appendData('delay',document.getElementById('delay').value);
		}		
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		AnimateDiv('$id');
     	XHR.sendAndLoad('$page', 'GET',x_SaveMilterGreyListAclID);
	}
	
	function explainThisacl$t(id){
		  if(!id){id='$t-type';}
		  var fieldz=document.getElementById(id).value;
		  if(fieldz.length==0){return;}
		  if(!document.getElementById('explainc-$t')){alert('explainc-$t No such ID !');}
	      LoadAjaxTiny('explainc-$t','$page?explainThisacl='+fieldz+'&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')  ;
	      ChangeForm$t();
	}

function ChangeForm$t(){
      xclass='{$_GET["num"]}';
      xtype=document.getElementById('$t-type').value;
      var hostname='{$_GET["hostname"]}';
      LoadAjax('addform-$t','$page?ChangeFormType='+xtype+'&class={$_GET["num"]}&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')
   
      
}	
	
	explainThisacl$t();
</script>


	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function main_acl($noecho=0){

	$pure=new milter_greylist();
	$page=CurrentPageName();
	if(isset($_GET["expand"])){$expand="&expand=yes";}
	$html="<div class=explain style='margin-bottom:10px'>{acl_text}</div>
	
	<div id='acllist' style='margin-left:-10px'></div>

	
	<script>
		LoadAjax('acllist','$page?acllist=true&hostname={$_GET["hostname"]}$expand&ou={$_GET["ou"]}');
	</script>
	";
	
	$tpl=new templates();
	if($noecho==1){return $tpl->_ENGINE_parse_body($html);}
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function main_acl_list(){
$page=CurrentPageName();
$tpl=new templates();
$method=$tpl->_ENGINE_parse_body("{method}");
$type=$tpl->_ENGINE_parse_body("{type_of_rule}");
$pattern=$tpl->_ENGINE_parse_body("{pattern}");
$description=$tpl->_ENGINE_parse_body("{description}");
$hostname=$_GET["hostname"];
$add=$tpl->_ENGINE_parse_body("{add}");
$rule=$tpl->_ENGINE_parse_body("{rule}");
$t=time();
if(trim($hostname)==null){$hostname="master";$_GET["hostname"]="master";}
$TB_WIDTH=750;
$TB_HEIGHT=400;
$ROW_EXPLAIN=300;
$TB_PATTERN=165;
$TB_TYPE=96;
	if(isset($_GET["expand"])){
		$TB_WIDTH=885;
		$ROW_EXPLAIN=346;
		$TB_PATTERN=219;
		$TB_HEIGHT=600;
		$TB_TYPE=125;
	}


$html="
	<table class='miltergrey-instances-list' style='display: none' id='miltergrey-instances-list' style='width:99%'></table>
	
<script>
var idtmp='';
$(document).ready(function(){
$('#miltergrey-instances-list').flexigrid({
	url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$method', name : 'method', width :70, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width : $TB_TYPE, sortable : true, align: 'left'},
		{display: '$pattern', name : 'pattern', width : $TB_PATTERN, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : $ROW_EXPLAIN, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 40, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : addcallistrule$t},
		{separator: true}
		],	
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: '$description', name : 'description'},	
	{display: '$method', name : 'method'},
		{display: '$type', name : 'type'},
		
		
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '$POSTFIX_MULTI_INSTANCE_INFOS',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	function addcallistrule$t(){
		LoadMilterGreyListAcl$t(-1)
	}

	function LoadMilterGreyListAcl$t(index){
		YahooWin4(450,'$page?add_acl=true&num='+index+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$acl&nbsp;$rule&nbsp;'+index);	
	}

var X_DeleteAclIDNewFunc= function (obj) {
	 var results=obj.responseText;
	 if(results.length>1){alert(results);return;}
	 $('#row'+idtmp).remove();
	}	
	
	function DeleteAclIDNewFunc(ID){
			idtmp=ID;
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAclID',ID);
			XHR.appendData('hostname','$hostname');
			XHR.sendAndLoad('$page', 'POST',X_DeleteAclIDNewFunc);	
		}


</script>

";	
echo $html;	
	

	
}

function main_acl_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	$pure=new milter_greylist(false,$_GET["hostname"]);	
	$t=$_GET["t"];
	$search='%';
	$table="miltergreylist_acls";
	$page=1;
	if(!$q->TABLE_EXISTS("miltergreylist_acls", "artica_backup")){$q->BuildTables();}
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("NO item,1");
		
	}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			
			if($_POST["sortname"]=="servername"){$_POST["sortname"]="value";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		if($_POST["qtype"]=="servername"){$searchstring="AND (`value` LIKE '$search')";}
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring AND (`instance` = '{$_GET["hostname"]}')";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 AND (`instance` = '{$_GET["hostname"]}')";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring AND (`instance` = '{$_GET["hostname"]}') $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$delete=$tpl->_ENGINE_parse_body(imgtootltip('delete-32.png','{delete}',"DeleteAclIDNewFunc('{$ligne["ID"]}');"));
	
	
	
	$link="LoadMilterGreyListAcl$t({$ligne["ID"]});";
	
	
	$js="<a href=\"javascript:blur()\" OnClick=\"javascript:$link\" style='text-decoration:underline;font-size:14px'>";
	if($ligne["type"]=="gpid"){
		if(preg_match("#gpid:([0-9]+)\s+(.+)#", $ligne["pattern"],$re)){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT objectname FROM miltergreylist_objects WHERE ID='{$re[1]}'","artica_backup"));
			$ligne["pattern"]=$ligne2["objectname"]." {$re[2]}";
			$ligne["type"]="group";
			$ligne["description"]="{$ligne2["objectname"]} {$re[2]} <br>".$ligne["description"];
		}
		
	}
	$type=$tpl->_ENGINE_parse_body("{{$ligne["type"]}}");
	
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<strong  style='font-size:14px'>{$ligne["method"]}</strong>",
		"<strong  style='font-size:14px'>$type</strong>",
		"$js<strong>{$ligne["pattern"]}</strong></a>",
		"$js<strong>{$ligne["description"]}</strong>",
		 $delete)
		);
	}
	
	
echo json_encode($data);		
	
	
	
}


function explainThisacl(){
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{{$_GET["explainThisacl"]}_text}</div>");
	
}

function GetNewForm(){
	
$pure=new milter_greylist();
$id=$_GET["class"];
$ArrayACL=$pure->getAclContent($id);
$line=$pure->ParseAcl($ArrayACL["full"]);
	
	
	switch ($_GET["ChangeFormType"]) {
		case "dnsrbl":
			if(!preg_match('#delay\s+([0-9]+)([a-z])#',$line[3],$re)){
				$re[1]=15;
				$re[2]="m";
			}
			$line[3]=trim($line[3]);
			$form=
			"<table style='width:99%' class=form>
				<tr>
					<td strong width=1% nowrap align='right'><strong style='font-size:14px'>{dnsrbl_service}:</strong></td>
					<td>" . Field_array_Hash($pure->dnsrbl_class,'dnsrbl_class',null,null,null,0,"font-size:14px") . "</td>
				</tr>
				<tr>
					<td strong width=1% nowrap align='right'><strong style='font-size:14px'>{delay}:</strong></td>
					<td>" . Field_text("delay","{$re[1]}{$re[2]}",'width:100px;font-size:14px') . "</td>
				</tr>				
			</table>";
			
			
			break;
			
		case "gpid":
			
			if(preg_match('#gpid:([0-9]+)#',$ArrayACL["pattern"],$re)){
				$gpid=$re[1];
			}	
			
			if(!preg_match('#delay\s+([0-9]+)([a-z])#',$ArrayACL["pattern"],$re)){
				$re[1]=15;
				$re[2]="m";
			}			
			
			$sql="SELECT ID,objectname FROM miltergreylist_objects WHERE enabled=1 AND instance='{$_GET["hostname"]}' ORDER BY objectname";
			$tt[null]="{select}";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while ($ligne = mysql_fetch_assoc($results)) {
				$tt[$ligne["ID"]]=$ligne["objectname"];
			}
		$form=$pure->acl[$id].
			"<table style='width:99%' class=form>
				<tr>
					<td strong width=1% nowrap align='right'><strong style='font-size:14px'>{group}:</strong></td>
					<td>" . Field_array_Hash($tt,'gpid_class',$gpid,null,null,0,"font-size:14px") . "</td>
				</tr>
				<tr>
					<td strong width=1% nowrap align='right'><strong style='font-size:14px'>{delay}:</strong></td>
					<td>" . Field_text("delay","{$re[1]}{$re[2]}",'width:100px;font-size:14px') . "</td>
				</tr>				
			</table>";
				break;
						
			
			
	
		default:$form="
			<table style='width:99%' class=form>
			<tr>
				<td align='right' width=1% nowrap ><strong style='font-size:14px'> {pattern}:</strong></td>
				<td><textarea name='pattern' id='pattern' rows=2 style='width:100%;font-size:14px;font-weight:bold'>{$line[3]}</textarea>
			</tr>
		</table>";
			break;
	}
		
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($form);
}

function SaveAclID(){
	$tpl=new templates();
	$id=$_GET["SaveAclID"];
	$mode=$_GET["mode"];
	$type=$_GET["type"];
	$pattern=$_GET["pattern"];
	$instance=$_GET["hostname"];
	if($instance==null){$instance="master";}
	if($type=="dnsrbl"){
		$pattern="\"{$_GET["dnsrbl_class"]}\"";
		if($_GET["delay"]<>null){
			$pattern=$pattern . " delay {$_GET["delay"]}";
		}
		
	}
	
	if($type=="gpid"){
		$pattern="gpid:{$_GET["gpid_class"]}";
		if($_GET["delay"]<>null){
			$pattern=$pattern . " delay {$_GET["delay"]}";
		}
		
	}
	
	
	$infos=$_GET["infos"];
	if($mode==null){$err="Error {mode}=null";}
	if($type==null){$err="Error {type}=null";}
	if($pattern==null){$err="Error {pattern}=null";}
	if($infos==null){$infos="saved Date:".date('Y-m-d H:i:s');}

	
	switch ($type) {
		case "body":$first="dacl";break;
		case "header":$first="dacl";break;
		default:$first="acl";break;
	}
	
	if($err<>null){
		echo $tpl->_ENGINE_parse_body($err);
		exit();
	}
	
	
	
	$infos=addslashes($infos);
	$line="$first $mode $type $pattern # $infos";
	if($first=="acl"){
		if($id==-1){
			$sql="INSERT INTO `miltergreylist_acls` (`instance`,`method`,`type`,`pattern`,`description`) VALUES 
			('$instance','$mode','$type','$pattern','$infos')
			";
		}else{
			$sql="UPDATE  `miltergreylist_acls` SET `method`='$mode',`type`='$type',`pattern`='$pattern',`description`='$infos' WHERE ID=$id;
			";
		}
		
		$q=new mysql();
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$pure=new milter_greylist(null,$instance);
		$pure->SaveToLdap();
		return;
		
	}
	
	
	$pure=new milter_greylist(false,$instance);
	if($id>-1){
		$pure->acl[$id]=$line;
	}else{$pure->acl[]=$line;}
	$pure->SaveToLdap();
	
	
}

function DeleteAclID(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM miltergreylist_acls WHERE ID={$_POST["DeleteAclID"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$pure=new milter_greylist(false,$_POST["hostname"]);
	$pure->SaveToLdap();
	
}

function DeleteDnsbl(){
	$pure=new milter_greylist();
	unset($pure->dnsrbl_array[$_GET["class"]]);
	$pure->SaveToLdap();
	echo main_dnsrbl_list();
}

function main_conf(){
$pure=new milter_greylist();
	$page=CurrentPageName();
	$g=$pure->global_conf;
	$g=nl2br($g);
	
	$html="
	<div style='padding:10px'>
	<code>$g</code>
	</div>";
		
$tpl=new templates();
	echo  $tpl->_ENGINE_parse_body($html);		
}


function main_dnsrbl(){
	$pure=new milter_greylist();
$page=CurrentPageName();
	$link="YahooWin(450,'$page?edit_dnsrbl=&subline=0','{add_dnsrbl}');";
	$html="
	<h5>{dnsrbl}</H5>
	<p class=caption><div style='float:right'>
	
	
	<input type='button' OnClick=\"javascript:$link;\" value='{add_dnsrbl}&nbsp;&raquo;'></div>
	{dnsrbl_text}</p>
	<div id='acllist' style='width:100%;height:300px;overflow:auto'>".main_dnsrbl_list()."</div>

	
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function main_dnsrbl_list(){
	$pure=new milter_greylist();
	$table=$pure->dnsrbl_array;
	
	
	

	
$html="<table style='width:100%'>
<tr>
<td colspan=3 align='left'>" . imgtootltip('fleche-20-red.png','{back_to_default}',"LoadAjax('acllist','$page?BackToDNSBLDefault=true')")."</td>
</tr>
";

if(!is_array($table)){return $html. "</table>";}		
		while (list ($num, $cell) = each ($table) ){
			$link="YahooWin(450,'$page?edit_dnsrbl=$num&subline=0','{edit_dnsrbl} $num');";
			$html=$html . "
			<tr " . CellRollOver().">
				<td width=1% ><img src='img/fw_bold.gif'></td>
				<td width=1% nowrap ><a href=\"javascript:$link\"><strong>$num</strong></a></td>
				<td width=1% nowrap >
					<table style='width:100%'>";

					$explain=substr($cell[2],0,40)."...";
					$cell[2]=nl2br($cell[2]);
					$cell[2]=str_replace("\n","",$cell[2]);
					$cell[2]=str_replace("\r","",$cell[2]);
					$cell[2]=str_replace("'","`",$cell[2]);
					$cell[2]=htmlentities($cell[2]);

									
					
					$explain=texttooltip($explain,$cell[2],$link);
					$html=$html . 
					"<tr>
						<td width=1% ><img src='img/fw_bold.gif'></td>
						<td width=120px nowrap ><a href=\"javascript:$link\"><strong>{$cell[0]}</strong></a></td>
						<td width=1% nowrap ><a href=\"javascript:$link\"><strong>{$cell[1]}</strong></a></td>
						<td >$explain</td>
						<td width=1% >". imgtootltip('x.gif','{delete}',"LoadAjax('acllist','$page?DeleteDnsbl=$num&class=$num');")."</td>
					</tr>
					";
				
				
				$html=$html . "
					</table>
				</td>
			</tr>";
			}
		
		
	
	$html=$html."</table>";
	
	$tpl=new templates();
	return  $tpl->_ENGINE_parse_body($html);
	
}

function main_edit_dnsrbl(){
	$mil=new milter_greylist();
	$class=$_GET["edit_dnsrbl"];
	$array=$mil->dnsrbl_array[$class];
	$page=CurrentPageName();
	
	
	
	
	$mil->dnsrbl_class[null]="{select}";
	$classes=Field_text('class',$class,'width:100%');
	
	$datas=file_get_contents('ressources/dnsrbl.db');
	$datas=explode("\n",$datas);
	while (list ($index, $line) = each ($datas) ){
		if(preg_match('#([A-Z\:]+)(.+)#',$line,$re)){
			$dnsbl[$re[2]]=$re[2];
		}
		
	}
	$dnsbl[null]="{select}";
	ksort($dnsbl);
	for($i=0;$i<11;$i++){$ip["127.0.0.$i"]="127.0.0.$i";}
	
	$field_ip=Field_array_Hash($ip,'ip',$array[1]);
	$dnsbl=Field_array_Hash($dnsbl,'dnsbl',$array[0]);
	$html="
	<FORM NAME='ffm11245'>
	<input type='hidden' name='dnsrbl_subindex' value='$dnsrbl_subindex'>
	<table style='width:100%'>
	<tr>
		<td align='right' width=1% nowrap><strong>{class_name}:</strong></td>
		<td><strong>$classes</strong></td>
	</tr>
	<tr>
		<td align='right' width=1% nowrap><strong>{new_class_name}:</strong></td>
		<td><strong>" . Field_text("new_class",null,'width:100%')."</strong></td>
	</tr>	
	<tr>
		<td align='right' width=1% nowrap><strong>{dnsrbl_service}:</strong></td>
		<td><strong>$dnsbl</strong></td>
	</tr>	
	<tr>
		<td align='right' width=1% nowrap><strong>{dnsrbl_answer}:</strong></td>
		<td><strong>$field_ip</strong></td>
	</tr>	
<tr>
	<td align='right' width=1% nowrap><strong>{infos}:</strong></td>
	<td><textarea name='infos' rows=1 style='width:100%'>{$array[2]}</textarea>
	</tr>	
<tr>
<td colspan=2 align='right'><input type='button' OnClick=\"javascript:ParseYahooForm('ffm11245','$page',true);LoadAjax('acllist','$page?dnsbllist=true');\" value='{edit}&nbsp;&raquo;'></td>
</tr>
</table>
</FORM>

	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	}
	
function SaveDnsrbl(){
	$class=$_GET["class"];
	$dnsbl=$_GET["dnsbl"];
	$infos=$_GET["infos"];
	$new_class=$_GET["new_class"];
	$ip=$_GET["ip"];
	
	if($new_class<>null){$class=$new_class;}
	
$mil=new milter_greylist();

	WriteLogs("dnsrbl_array[$class][$subindex] is an array, edit array()",__FUNCTION__,__FILE__);
	WriteLogs("change {$mil->dnsrbl_array[$class][0]} to $dnsbl",__FUNCTION__,__FILE__);
	WriteLogs("change {$mil->dnsrbl_array[$class][1]} to $ip",__FUNCTION__,__FILE__);	
	WriteLogs("change {$mil->dnsrbl_array[$class][2]} to $infos",__FUNCTION__,__FILE__);		
	$mil->dnsrbl_array[$class][0]=$dnsbl;
	$mil->dnsrbl_array[$class][1]=$ip;
	$mil->dnsrbl_array[$class][2]=$infos;
		


$mil->SaveToLdap();
	
}

function BackToDNSBLDefault(){
$mil=new milter_greylist();	
unset($mil->dnsrbl_array);
$mil->SaveToLdap();
echo main_dnsrbl_list();
}
function main_logs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<H5>{events}</H5>
	<iframe src='miltergreylist.events.php' style='width:100%;height:500px;border:0px'></iframe>";
	echo $tpl->_ENGINE_parse_body($html);
	}
	
	
function dumpfile_popup(){

	$sock=new sockets();
	$sock->getFrameWork("milter-greylist.php?dump-database=yes");
	include("ressources/logs/mgrelist-db.inc");
	
	$html="<H1>{MILTERGREYLIST_STATUSDUMP}</H1>
	<p class=caption>{MILTERGREYLIST_STATUSDUMP_TEXT}</p>";
	
	if(is_array($MGREYLIST_DB["GREY"])){
		$grey="
		<table style='width:99%'>
		<tr>
			<th>&nbsp;</th>
			<th>{hostname}</th>
			<th>{sender}</th>
			<th>{recipient}</th>
		</tr>
		";
		while (list ($index, $line) = each ($MGREYLIST_DB["GREY"]) ){
		$grey=$grey."
		
			<tr>
				<td ><img src='img/fw_bold.gif'></td>
				<td >{$line[0]}</td>
				<td >{$line[1]}</td>
				<td >{$line[2]}</td>
			</tr>
		
		";
			
		}

		$grey=$grey."</table>";
		
	}
	
	$grey=RoundedLightWhite("<H3>{greylistedtuples}</h3><br><div style='width:100%;height:200px;overflow:auto'>$grey</div>");
	
	
	if(is_array($MGREYLIST_DB["WHITE"])){
		$white="
		<table style='width:99%'>
		<tr>
			<th width=1%>&nbsp;</th>
			<th width=1% nowrap>{hostname}</th>
			<th>{sender}</th>
			<th>{recipient}</th>
		</tr>
		";
		while (list ($index, $line) = each ($MGREYLIST_DB["WHITE"]) ){
		$white=$white."
		
			<tr>
				<td  width=1%><img src='img/fw_bold.gif'></td>
				<td  width=1% nowrap>{$line[0]}</td>
				<td >{$line[1]}</td>
				<td >{$line[2]}</td>
			</tr>
		
		";
			
		}

		$white=$white."</table>";
		
	}	
	
	$white=RoundedLightWhite("<H3>{Autowhitelistedtuples}</h3><br><div style='width:100%;height:200px;overflow:auto'>$white</div>");
	$html=$html."$grey<br>$white";
	$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function popup_db(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(is_file("ressources/logs/greylist-count-master.tot")){
	$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
	if(is_array($datas)){
		$table="
		<div class=explain>
		<p>{MILTERGREYLIST_STATUSDUMP_TEXT}</p>
		<table>
		<tr>
			<td style='font-size:16px'>{records}:</td>
			<td style='font-size:16px'>{$datas["RECORDS"]}</td>
			<td style='font-size:16px'>{greylisted}:</td>
			<td style='font-size:16px'>{$datas["GREYLISTED"]}</td>
			<td style='font-size:16px'>{whitelisted}:</td>
			<td style='font-size:16px'>{$datas["WHITELISTED"]}</td>	
		</tr>
		</table>	
		</div>				
		";
		
	}
	}
	
	$html="$table
	<center>
			<table style='width:99%' class=form>
			<tr>
				<td class=legend>{pattern}:</td>
				<td>". Field_text("browse-mgreydb-search",null,"font-size:14px;padding:3px",null,null,null,false,"BrowseMiltergreySearchCheck(event)")."</td>
				<td>". button("{search}","BrowseMgreySearch()")."</td>
			</tr>
			</table>
	</center>		
	<div id='browse-mgrey-list' style='width:100%;height:430px;overflow:auto;text-align:center'></div>
	
<script>
		function BrowseMiltergreySearchCheck(e){
			if(checkEnter(e)){BrowseMgreySearch();}
		}
		
		function BrowseMgreySearch(){
			var se=escape(document.getElementById('browse-mgreydb-search').value);
			LoadAjax('browse-mgrey-list','$page?browse-mgrey-list=yes&search='+se+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		}
		
		
	BrowseMgreySearch();
</script>	
	
	";
	
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function popup_db_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if($_GET["search"]<>null){
		$_GET["search"]=str_replace("*", "%", $_GET["search"]);
		$filter="AND ((mailfrom LIKE '{$_GET["search"]}') OR (mailto LIKE '{$_GET["search"]}') OR (ip_addr LIKE '{$_GET["search"]}'))";
	}
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>{time}</th>
		<th width=25%>{ipaddr}</th>
		<th width=25%>{from}</th>
		<th width=25%>{to}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	$maxlen=30;
		$sql="SELECT * FROM greylist_turples WHERE hostname='master' $filter LIMIT 0,150";
		$q=new mysql();
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql,"artica_events");
		
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["ip_addr"])=="#"){continue;}	
		if($ligne["mailfrom"]=="Summary:"){continue;}
		$time=date("Y-m-d H:i:s",$ligne["stime"]);
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$len=strlen($ligne["mailfrom"]);
		if($len>$maxlen){$ligne["mailfrom"]=substr($ligne["mailfrom"],0,$maxlen-3)."...";}
			
		$len=strlen($ligne["mailto"]);
		if($len>$maxlen){$ligne["mailto"]=substr($ligne["mailto"],0,$maxlen-3)."...";}		
		
		
	$color="black";
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:12px;font-weight:bold;color:$color' nowrap>$time</td>
			<td style='font-size:12px;font-weight:bold;color:$color' width=1% nowrap>{$ligne["ip_addr"]}</a></td>
			<td style='font-size:12px;font-weight:bold;color:$color' width=50%>{$ligne["mailfrom"]}</a></td>
			<td style='font-size:12px;font-weight:bold;color:$color' width=50>{$ligne["mailto"]}</a></td>
		</tr>
		";
	}
	
	$html=$html."</table></center>";
	echo $tpl->_ENGINE_parse_body($html);
}
	
?>
