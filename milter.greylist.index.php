<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
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
	
	if(isset($_POST["MilterGreyListEnabled"])){MilterGreyListEnabled();exit;}
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

function MilterGreyListEnabled(){

	$sock=new sockets();
	$MilterGreyListEnabled=$sock->GET_INFO("MilterGreyListEnabled");
	$sock->SET_INFO("RemoteMilterService", $_POST["RemoteMilterService"]);
	$sock->SET_INFO('MilterGreyListEnabled',$_POST["MilterGreyListEnabled"]);
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
		YahooWin4(750,'$page?add_acl=true&num='+index+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$acl::N.'+index);	
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
	$array["index"]='{status}';
	$array["popup-settings"]="{main_settings}";
	$array["popup-acl"]='{acls}';
	$array["popup-groups"]='{objects}';
	
	$array["popup-dumpdb"]='{items}';
	$array["events"]='{events}';
	
	
	
	if(isset($_GET["expand"])){$expdand="&expand=yes";}
	$_GET["ou"]=urlencode($_GET["ou"]);
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-settings"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:18px'>
			<a href=\"$page?popup-settings=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span>
			</a></li>
			\n");
			continue;			
		}
		
		if($num=="popup-groups"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"milter.greylist.objects.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
			continue;			
		}	
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"syslog.php?popup=yes&prepend=milter-greylist\"><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_mgreylist",1170);
	
}



function popup(){
	
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$MilterGreyListEnabled=intval($sock->GET_INFO('MilterGreyListEnabled'));
	$RemoteMilterService=$sock->GET_INFO("RemoteMilterService");
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}
	
	if(is_file("ressources/logs/greylist-count-master.tot")){
	$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
	
	if(is_array($datas)){
		@unlink("ressources/logs/web/mgreylist.master1.db.png");
		$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/mgreylist.master1.db.png",0);
		$gp->xdata[]=$datas["GREYLISTED"];
		$gp->ydata[]="greylisted";	
		$gp->xdata[]=$datas["WHITELISTED"];
		$gp->ydata[]="whitelisted";				
		$gp->width=750;
		$gp->height=750;
		$gp->ViewValues=false;
		$gp->x_title="{status}";
		$gp->pie();			
		$imgG="<img src='ressources/logs/web/mgreylist.master1.db.png'>";
		
	}
	}
	

	
	$P1=Paragraphe_switch_img("{enable_milter}", "{enable_milter_text}","MilterGreyListEnabled-$t",
			$MilterGreyListEnabled,null,650);
	
	$content="
			
	<div id='animate-$t' style='font-size:26px;margin-bottom:15px'>{server}:{$_GET["hostname"]}</div>
	$P1
	<div id='animate-$t' style='font-size:26px;margin-bottom:15px;margin-top:15px'>{use_milter_remote_service}
	<div class=explain style='font-size:16px'>{use_milter_remote_service_explain}</div>
		<div class=form style='width:95%'>
		<table style='width:100%'>
	
		<tr>
		<td align='right' nowrap  class=legend style='font-size:18px;vertical-align:middle'>{value}:</strong></td>
		<td >" . Field_text('RemoteMilterService',$RemoteMilterService,'width:350px;font-size:22px;padding:10px',null,null)."
		</td>
		</tr>
	</table>
	</div>
	
	
	<div style='width:100%;text-align:right'><hr>". button("{apply}","Save$t();",26)."</div>
	
	";
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top'>
			<div id='mgreylist-status'></div>
		</td>
		<td style='vertical-align:top'>
			$content<br><center>$imgG</center>
		</td>
	</tr>
	
	</table>
	
<script>
var x_Save$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_config_mgreylist');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MilterGreyListEnabled',document.getElementById('MilterGreyListEnabled-$t').value);
	XHR.appendData('RemoteMilterService',document.getElementById('RemoteMilterService').value);
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);	
}
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
	$font="style='font-size:18px'";
	
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
	$sock=new sockets();
	$MilterGreyListEnabled=intval($sock->GET_INFO("MilterGreyListEnabled"));
	$t=time();
	$arraytime=array(
		"m"=>"{minutes}","h"=>"{hour}","d"=>"{day}"
	);
	
	
	if($hostname=="master"){
		$portF="
				<tr>
					<td $style align='right' nowrap  class=legend style='font-size:18px'>{useTCPPort}:</strong></td>
					<td $style >" . Field_checkbox('MilterGreyListUseTCPPort',1,$pure->MilterGreyListUseTCPPort,"CheckTCPPOrt$t()")."</td>
					<td $style ></td>
				</tr>
				<tr>
					<td $style align='right' nowrap  class=legend style='font-size:18px'>{listen_port}:</strong></td>
					<td $style >" . Field_text('MilterGeryListTCPPort',$pure->MilterGeryListTCPPort,'width:120px;font-size:18px')."</td>
					<td $style ></td>
				</tr>";
		
	}

	$html="
	<div id='animate-$t' style='font-size:26px;margin:15px'>{server}:$hostname</div>
	<div id='MilterGreyListConfigGeneSaveID0'>
	
	<input type='hidden' name='hostname' value='$hostname'>
	<input type='hidden' name='SaveGeneralSettings' value='yes'>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	


	$portF
<tr>
	<td $style align='right' nowrap  class=legend style='font-size:18px'>{add_default_nets}:</strong></td>
	<td $style >" . Field_checkbox('MiltergreyListAddDefaultNets',1,$pure->MiltergreyListAddDefaultNets)."</td>
	<td $style ></td>
</tr>		
<tr><td colspan=3><p class=text-info style='font-size:14px'>{milter_greylist_add_default_net_explain}</p></td></tr>
<tr>
	<td $style align='right' nowrap  class=legend style='font-size:18px'>{remove_tuple}:</strong></td>
	<td $style >" . Field_checkbox('lazyaw',1,$pure->main_array["lazyaw"])."</td>
	<td $style ></td>
</tr>	
<tr><td colspan=3><p class=text-info style='font-size:14px'>{remove_tuple_text}</p></td></tr>
	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:18px'>{timeout}:</strong></td>
	<td $style  colspan=2>" . Field_text('timeout',$pure->main_array["timeout"],'width:90px;font-size:18px',null,null)."&nbsp;".
		Field_array_Hash($arraytime,'timeout_TIME',$pure->main_array["timeout_TIME"],"style:font-size:18px")."</td>
	</tr>
<tr><td colspan=3><p class=text-info style='font-size:14px'>{mgreylisttimeout_text}</p></td></tr>	
	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:18px'>{greylist}:</strong></td>
	<td $style  colspan=2>
	
	" . Field_text('greylist',$pure->main_array["greylist"],'width:120px;font-size:18px',null,null)."&nbsp;".
		Field_array_Hash($arraytime,'greylist_TIME',$pure->main_array["greylist_TIME"],"style:font-size:18px")."
	
	</td>
	</tr>
	<tr><td colspan=3><p class=text-info style='font-size:14px'>{greylist_text}</p></td></tr>		
	<tr>
	<td $style align='right' nowrap  class=legend style='font-size:16px'>{autowhite}:</strong></td>
	<td $style colspan=2>" . Field_text('autowhite',$pure->main_array["autowhite"],'width:110px;font-size:18px',null,null)."&nbsp;".
		Field_array_Hash($arraytime,'autowhite_TIME',$pure->main_array["autowhite_TIME"],"style:font-size:18px")."</td>
	</tr>
	
	<tr><td colspan=3><p class=text-info style='font-size:14px'>{autowhite_text}</p></td></tr>		

	<tr>
	<td $style colspan=3 align='right' >
	<hr>
	". button("{apply}","MilterGreyListPrincipalSave$t()",26)."
	
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
		var MilterGreyListEnabled=$MilterGreyListEnabled;
		if(MilterGreyListEnabled==1){
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
	$mil->__Parse_DNSBL();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	if(!is_numeric($_GET["num"])){$_GET["num"]=0;}
	$action=$mil->actionlist;
	unset($action["geoip"]);
	$btname="{apply}";
	if($_GET["num"]<1){$btname="{add}";}
	
	
	$sql="SELECT ID,objectname FROM miltergreylist_objects WHERE enabled=1 AND instance='{$_GET["hostname"]}' ORDER BY objectname";
	$tt[null]="{select}";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$Groups[$ligne["ID"]]=$ligne["objectname"];
	}
	
	
	$ArrayACL=$mil->getAclContent("{$_GET["num"]}");
	$line=$mil->ParseAcl($ArrayACL["full"]);
	
	$t=time();
	
	$sql="SELECT * FROM miltergreylist_acls WHERE ID='{$_GET["num"]}'";
	if($ligne["type"]==null){$ligne["type"]="whitelist";}

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$method=$tpl->javascript_parse_text("{method}");
	$arrayd=Field_array_Hash(array(""=>"{select}","blacklist"=>"{blacklist}",
	'whitelist'=>"{whitelist}","greylist"=>"{greylist}"),"$t-mode",
			$ligne["method"],"explainThisacl$t();",null,0,'font-size:22px;padding:5px');
	
	if($ligne["type"]=="dnsrbl"){
		if(preg_match('#delay\s+([0-9]+)([a-z])#',$ligne["pattern"],$re)){
			$grelistdnbltime=$re[1];
			$grelistdnbltemps=$re[2];

		}
		
		$ligne["pattern"]=trim($line[3]);
	}
	
	if($ligne["type"]=="gpid"){
			if(preg_match('#gpid:([0-9]+)#',$ArrayACL["pattern"],$re)){
				$gpid=$re[1];
			}	
			
			if(preg_match('#delay\s+([0-9]+)([a-z])#',$ArrayACL["pattern"],$re)){
				$groupDelay=$re[1]=15;
				$groupDelayInterval=$re[2]="m";
			}	
			
	}	
	
	if(!is_numeric($grelistdnbltime)){$grelistdnbltime=15;}
	if($grelistdnbltemps==null){$grelistdnbltemps="m";}
	if(!is_numeric($groupDelay)){$groupDelay=15;}
	if($groupDelayInterval==null){$groupDelayInterval="m";}
	
	$PatternField="<textarea name='pattern-$t' id='pattern-$t' rows=10 
	style='width:99%;font-size:22px !important;'>{$ligne["pattern"]}</textarea>";
	if($_GET["num"]>0){
		$PatternField=Field_text("pattern-$t",$ligne["pattern"],"font-size:22px;font-weight:bold");
	}
	
	
	$action["gpid"]="{objects_group}";
	$arrayf=Field_array_Hash($action,"$t-type",$ligne["type"],"explainThisacl$t();",null,0,
			'font-size:22px;padding:5px');
	$ligne["pattern"]=trim($ligne["pattern"]);
	$id=time();
	$html="
	<div id='explainThisAcl-$t'></div>
	<input type='hidden' name='SaveAclID' id='SaveAclID' value='{$_GET["num"]}'>
	<input type='hidden' name='hostname-hidden' id='hostname-hidden' value='{$_GET["hostname"]}'>
	<div style='width:98%' class=form>
		<table style='width:100%'>
		<tbody>
			<tr>
				<td align='right' width=1% nowrap style='font-size:22px' class=legend>{method}:</strong></td>
				<td><strong>$arrayd</strong></td>
			</tr>
			<tr>
				<td align='right' width=1% nowrap style='font-size:22px' class=legend>{type_of_rule}:</strong></td>
				<td><strong>$arrayf</strong></td>
			</tr>
			<tr><td colspan=2 style='font-size:26px;height:60px'>DNSRBL</td></tr>
			<tr>
				<td width=1% nowrap style='font-size:22px' class=legend >{dnsrbl_service}:</strong></td>
				<td>" . Field_array_Hash($mil->dnsrbl_class,'dnsrbl_class',null,null,null,0,"font-size:22px") . "</td>
			</tr>
			<tr>
				<td  width=1% nowrap style='font-size:22px' class=legend >{delay}:</strong></td>
				<td>" . Field_text("delay1","{$grelistdnbltime}{$grelistdnbltemps}",'width:150px;font-size:22px') . "</td>
			</tr>	
			<tr><td colspan=2 style='font-size:26px;height:60px'>{groups2}</td></tr>
			<tr>
				<td width=1% nowrap style='font-size:22px' class=legend>{group}:</strong></td>
				<td>" . Field_array_Hash($Groups,'gpid_class',$gpid,null,null,0,"font-size:22px") . "</td>
			</tr>
			<tr>
				<td swidth=1% nowrap style='font-size:22px' class=legend>{delay}:</strong></td>
				<td>" . Field_text("delay2","$groupDelay{$groupDelayInterval}",'width:100px;font-size:22px') . "</td>
			</tr>				
			<tr>
				<td swidth=1% nowrap style='font-size:22px;vertical-align:top' class=legend>{value}:</strong></td>
				<td>$PatternField</td>
			</tr>		
			<tr>
				<td align='right' width=1% nowrap><strong style='font-size:22px'>{infos}:</strong></td>
				<td>
					<textarea name='$t-infos' id='$t-infos' rows=1 
					style='width:100%;font-size:22px !important;'>{$ligne["description"]}</textarea>
				</td>
		</tr>	
	
<tr>
<td colspan=2 align='right'>
<hr>". button("$btname","SaveMilterGreyListAclID$t()",30)."
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
		var xType=document.getElementById('$t-type').value;
		if(mode.length==0){alert('$method = NULL');return;}
		var XHR = new XHRConnection();
		XHR.appendData('SaveAclID','{$_GET["num"]}');
		XHR.appendData('type',xType);
		if(document.getElementById('pattern-$t')){XHR.appendData('pattern',document.getElementById('pattern-$t').value);}
		XHR.appendData('infos',document.getElementById('$t-infos').value);
		XHR.appendData('mode',document.getElementById('$t-mode').value);
		
		if(xType=='dnsrbl'){
			if(document.getElementById('dnsrbl_class')){
				XHR.appendData('dnsrbl_class',document.getElementById('dnsrbl_class').value);
				XHR.appendData('delay',document.getElementById('delay1').value);
			}
		}
		
		if(xType=='gpid'){
			if(document.getElementById('gpid_class')){
				XHR.appendData('gpid_class',document.getElementById('gpid_class').value);
				XHR.appendData('delay',document.getElementById('delay2').value);
			}		
		}
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
     	XHR.sendAndLoad('$page', 'GET',x_SaveMilterGreyListAclID);
	}
	
	function explainThisacl$t(id){
		  var xMode=document.getElementById('$t-mode').value;
		  var fieldz=document.getElementById('$t-type').value;
		  if(!document.getElementById('explainc-$t')){alert('explainc-$t No such ID !');}
	      LoadAjaxTiny('explainThisAcl-$t','$page?explainThisacl='+fieldz+'&xMode='+xMode+'&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}')  ;
	      ChangeForm$t();
	}

function ChangeForm$t(){
      xclass='{$_GET["num"]}';
      var xMode=document.getElementById('$t-mode').value;
      var xType=document.getElementById('$t-type').value;
      document.getElementById('dnsrbl_class').disabled=true;
      document.getElementById('delay1').disabled=true;
      document.getElementById('gpid_class').disabled=true;
      document.getElementById('delay2').disabled=true;
      
      if(xType=='dnsrbl'){
      	 document.getElementById('dnsrbl_class').disabled=false;
      	 document.getElementById('pattern-$t').disabled=true;
      	
      	 if(xMode=='greylist'){document.getElementById('delay1').disabled=false;}
      }
      if(xType=='gpid'){
      	 document.getElementById('gpid_class').disabled=false;
      	 document.getElementById('pattern-$t').disabled=true;
      	 if(xMode=='greylist'){document.getElementById('delay2').disabled=false;}
      }      
      
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
	$html="
	<div id='acllist'></div>

	
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
$about=$tpl->javascript_parse_text("{about2}");
$blacklist=$tpl->javascript_parse_text("{blacklist}");
$whitelist=$tpl->javascript_parse_text("{whitelist}");
$greylist=$tpl->javascript_parse_text("{greylist}");
$all=$tpl->javascript_parse_text("{all}");
$zDate=$tpl->javascript_parse_text("{zDate}");

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
$about_text=$tpl->javascript_parse_text("{acl_text}");
$POSTFIX_MULTI_INSTANCE_INFOS=$tpl->javascript_parse_text("{acls}");

$html="
	<table class='miltergrey-instances-list' style='display: none' id='miltergrey-instances-list' style='width:99%'></table>
	
<script>
var idtmp='';
$(document).ready(function(){
$('#miltergrey-instances-list').flexigrid({
	url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
	
		{display: '$zDate', name : 'zDate', width :70, sortable : true, align: 'left'},
		{display: '$method', name : 'method', width :70, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width : $TB_TYPE, sortable : true, align: 'left'},
		{display: '$pattern', name : 'pattern', width : $TB_PATTERN, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : $ROW_EXPLAIN, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 40, sortable : false, align: 'center'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : addcallistrule$t},
		{separator: true},
		{name: '$blacklist', bclass: 'Search', onpress : blacklist$t},
		{name: '$whitelist', bclass: 'Search', onpress : whitelist$t},
		{name: '$greylist', bclass: 'Search', onpress : greylist$t},
		{name: '$all', bclass: 'Search', onpress : all$t},
		{name: '$about', bclass: 'help', onpress : about$t},
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
	title: '<span style=font-size:18px>$POSTFIX_MULTI_INSTANCE_INFOS</span>',
	useRp: true,
	rp: 20,
	showTableToggleBtn: true,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

function  about$t(){
	alert('$about_text');
}

	function addcallistrule$t(){
		LoadMilterGreyListAcl$t(-1)
	}

	function LoadMilterGreyListAcl$t(index){
		YahooWin4(750,'$page?add_acl=true&num='+index+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$acl&nbsp;$rule&nbsp;'+index);	
	}
	
function blacklist$t(){
	$('#miltergrey-instances-list').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=blacklist'}).flexReload(); 
}
function whitelist$t(){
	$('#miltergrey-instances-list').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=whitelist'}).flexReload();  
}
function greylist$t(){
	$('#miltergrey-instances-list').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=greylist'}).flexReload(); 
}
function all$t(){
$('#miltergrey-instances-list').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby='}).flexReload();
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
	$FORCE=null;
	if(!isset($_GET["filterby"])){$_GET["filterby"]=null;}
	if($_GET["filterby"]<>null){
		$FORCE=" AND `method`='{$_GET["filterby"]}'";
	}
	
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
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring AND (`instance` = '{$_GET["hostname"]}') $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 AND (`instance` = '{$_GET["hostname"]}') $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring AND (`instance` = '{$_GET["hostname"]}') $FORCE $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	$divstart="<span style='font-size:12px;font-weight:normal'>";
	$divstop="</div>";
	if((mysql_num_rows($results)==0)){json_error_show("no data");}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$delete=$tpl->_ENGINE_parse_body(imgsimple('delete-24.png',null,"DeleteAclIDNewFunc('{$ligne["ID"]}');"));
	
	
	
	$link="LoadMilterGreyListAcl$t({$ligne["ID"]});";
	
	
	$js="<a href=\"javascript:blur()\" OnClick=\"javascript:$link\" 
		style='text-decoration:underline;font-size:12px'>";
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
		"<strong  style='font-size:12px'>{$ligne["zDate"]}</strong>",
		"<strong  style='font-size:12px'>{$ligne["method"]}</strong>",
		"<strong  style='font-size:12px'>$type</strong>",
		"$js<strong>{$ligne["pattern"]}</strong></a>",
		"$js<strong>{$ligne["description"]}</strong>",
		 $delete)
		);
	}
	
	
echo json_encode($data);		
	
	
	
}


function explainThisacl(){
	$tpl=new templates();
	if($_GET["explainThisacl"]==null){return;}
	
	$mil=new milter_greylist();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	
	$action=$mil->actionlist;
	$action["gpid"]="{objects_group}";
	$subtitle=$action[$_GET["explainThisacl"]];
	
	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>
	<strong style='font-size:18px'>{{$_GET["xMode"]}}&nbsp;$subtitle</strong><br>
	{{$_GET["explainThisacl"]}_text}</div>");
	
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
		
		$q=new mysql();
		if(!$q->FIELD_EXISTS("miltergreylist_acls","zDate","artica_backup")){
			$sql="ALTER TABLE `miltergreylist_acls` ADD `zDate` DATETIME,ADD INDEX ( `zDate` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
		
		if($id==-1){
			$AllLines=explode("\n",$pattern);
			if(count($AllLines)==0){$AllLines[]=$pattern;}
			$prefix="INSERT INTO `miltergreylist_acls` (`zDate`,`instance`,`method`,`type`,`pattern`,`description`) VALUES ";
			while (list ($index, $patterns) = each ($AllLines) ){
				$zDate=date("Y-m-d H:i:s");
				$patterns=mysql_escape_string2($patterns);
				$TR[]="('$zDate','$instance','$mode','$type','$patterns','$infos')";
			}
			
			$sql=$prefix.@implode(",", $TR);
						
			
		}else{
			$sql="UPDATE `miltergreylist_acls` SET `method`='$mode',`type`='$type',`pattern`='$pattern',`description`='$infos' WHERE ID=$id;
			";
		}
		
		
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
<td colspan=2 align='right'><input type='button' OnClick=\"javascript:ParseYahooForm('ffm11245','$page',true);LoadAjax('acllist','$page?dnsbllist=true');\" value='{apply}&nbsp;&raquo;'></td>
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
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$time=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$from=$tpl->javascript_parse_text("{from}");
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$title=$tpl->javascript_parse_text("{browsers_rules}");
	$whitelist=$tpl->javascript_parse_text("{whitelist}");
	$nowebfilter=$tpl->javascript_parse_text("{bypass_webfilter}");
	$nowcache=$tpl->javascript_parse_text("{no_cache}");
	$deny=$tpl->javascript_parse_text("{deny}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply_params=$tpl->javascript_parse_text("{apply}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$about=$tpl->javascript_parse_text("{about2}");
	$browsers_ntlm_explain=$tpl->javascript_parse_text("{MILTERGREYLIST_STATUSDUMP}\n---------------------\n{MILTERGREYLIST_STATUSDUMP_TEXT}",0);
	$t=time();
	
	if(is_file("ressources/logs/greylist-count-master.tot")){
		$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
		// 
		if(is_array($datas)){
			$title=$tpl->javascript_parse_text("{records}: {$datas["RECORDS"]}&nbsp;-&nbsp;{greylisted}: {$datas["GREYLISTED"]}&nbsp;-&nbsp;{whitelisted}: {$datas["WHITELISTED"]}");
		}
	}

	
	$buttons="buttons : [
	{name: '$about', bclass: 'help', onpress : About$t},
	
	],	";


	$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
	$(document).ready(function(){
			$('#table-$t').flexigrid({
			url: '$page?browse-mgrey-list=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}',
			dataType: 'json',
			colModel : [
			{display: '$time', name : 'stime', width : 151, sortable : true, align: 'left'},
			{display: '$ipaddr', name : 'ip_addr', width : 134, sortable : true, align: 'left'},
			{display: '$from', name : 'mailfrom', width : 392, sortable : true, align: 'left'},
			{display: '$to', name : 'mailto', width : 324, sortable : true, align: 'left'},



			],
			$buttons
			searchitems : [
			{display: '$time', name : 'stime'},
			{display: '$ipaddr', name : 'ip_addr'},
			{display: '$from', name : 'mailfrom'},
			{display: '$to', name : 'mailto'},
			],
			sortname: 'stime',
			sortorder: 'desc',
			usepager: true,
			title: '<span style=font-size:18px>$title</span>',
			useRp: true,
			rp: 15,
			showTableToggleBtn: false,
			width: '99%',
			height: 450,
			singleSelect: true

});
});

function About$t(){
	alert('$browsers_ntlm_explain');
}

</script>
	";
	echo $html;

}



function popup_db_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$table="greylist_turples";
	$MyPage=CurrentPageName();
	$q=new mysql();
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	
	$search='%';
	$table="(SELECT * FROM greylist_turples WHERE hostname='{$_GET["hostname"]}') as t ";
	$page=1;
	
	if($q->COUNT_ROWS("greylist_turples","artica_events")==0){json_error_show("No data");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		if(trim($ligne["ip_addr"])=="#"){continue;}
		if($ligne["mailfrom"]=="Summary:"){continue;}
		$time=date("Y-m-d H:i:s",$ligne["stime"]);
		if($ligne["whitelisted"]==0){$color="#AA1A07";}
		
		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array(
			"<span style='font-size:14px;;color:$color'>$time</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["ip_addr"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ligne["mailto"]}</a></span>",

			)
			);
	}
	
	
	
	echo json_encode($data);

}
	
?>
