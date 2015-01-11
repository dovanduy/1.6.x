<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.fetchmail.inc');	
$user=new usersMenus();
$tpl=new templates();


if(isset($_GET["ssl-fingerprint"])){ssl_fingerprint_js();exit;}
if(isset($_GET["getsslfinger"])){ssl_fingerprint_get();exit;}


if(isset($_GET["poll"])){SavePools();exit();}
if(isset($_GET["AddNewFetchMailRule"])){AddNewFetchMailRule();exit();}
if(isset($_GET["LdapRules"])){LdapRules();exit;}
if(isset($_GET["UserDeleteFetchMailRule"])){UserDeleteFetchMailRule();exit;}
if(isset($_POST["UseDefaultSMTP"])){UseDefaultSMTP_save();exit;}
if($user->AsArticaAdministrator==false){header('location:users.index.php');exit;}
if(isset($_GET["import_1"])){echo import_1();exit;}
if(isset($_GET["LocalRules"])){echo LocalRules();exit;}
if(isset($_GET["fetchmailbuttons"])){echo RoundedLightGrey(LocalFetchMailRc());exit;}
if(isset($_GET["InstallFetchmail"])){echo InstallFetchmail();exit;}
if(isset($_GET["UserRules"])){LoadUserRules();exit;}
if(isset($_GET["LoadFetchMailRuleFromUser"])){LoadFetchMailRuleFromUser();exit;}
if(isset($_GET["ChangeFetchMailUser"])){ChangeFetchMailUser();exit;}


PAGE();




function PAGE(){
$LocalFetchMailRc=LocalFetchMailRc();

$fetchmail_explain="<div style='padding:2px;margin:5px'>
<table>
<tr>
<td valign='top'>
<img src='img/fetchmail_explain.png'>
</td>
<td valign='top'><div class=text-info>{fetchmail_explain}</div></td>
</tr>
</table>";


$return=RoundedLightGreen("<table style='width:100%'>
	<tr><td width=1%>" . imgtootltip('restore-on.png','{go_back}',"MyHref('artica.wizard.php')") . "</td>
	<td><H5>{return_to} {artica_wizard}</H5></td>
	</tr></table>");
$html="
<table style='width:100%'>
<tr>
<td width=50% valign='top'>
	<span id='fetchmailbuttons'>$LocalFetchMailRc</span>
	<br>
	<div id='left'></div>
	</td>
<td width=50% valign='top'>$return<br>" . applysettings("fetch") . "$fetchmail_explain<br>" . RightMenu() . "</td>
</tr>
</table>
";
	
	
	
$CFG["JS"][]='js/wizard.fetchmail.js';
$tpl=new template_users('{get_mails_isp}',$html,0,0,0,0,$CFG);
echo $tpl->web_page;
	
}

function ssl_fingerprint_js(){
	$rule=$_GET["LdapRules"];
	$t=time();
	$uid=$_GET["uid"];
	$page=CurrentPageName();
	$tpl=new templates();
	$fr=new Fetchmail_settings();
	$ligne=$fr->LoadRule($rule);
	$alertssl=0;
	if($ligne["ssl"]<>1){$alertssl=1;}
	$ssl_fingerprint_importerror=$tpl->javascript_parse_text("{ssl_fingerprint_importerror}");
	
	$html="
	
	var x_ssl_finger_js_start$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		UserFetchMailRule($rule,'$uid');
	}		
	
	
	function ssl_finger_js_start$t(){
		var alertssl=$alertssl;
		if(alertssl==1){
			alert('$ssl_fingerprint_importerror');
			return;
		}
		var XHR = new XHRConnection();
		XHR.appendData('getsslfinger','yes');
		XHR.appendData('LdapRules','$rule');
		AnimateDiv('fetchmailadvrule');
		XHR.sendAndLoad('$page', 'GET',x_ssl_finger_js_start$t);
		}
		
		
		
	ssl_finger_js_start$t();	
		
	";
	
	echo $html;
	
}

function ssl_fingerprint_get(){
	$rule_number=$_GET["LdapRules"];
	$fr=new Fetchmail_settings();
	$hash_rules=$fr->LoadRule($rule_number);	
	$poll=$hash_rules["poll"];
	$proto=$hash_rules["proto"];
	if($proto=="pop3"){$port=995;}
	if($proto=="imap"){$port=993;}
	$sock=new sockets();
	$tpl=new templates();
	$finger_print=base64_decode($sock->getFrameWork("cmd.php?sslfingerprint=yes&ip=$poll&port=$port"));
	if($finger_print==null){
		echo $tpl->javascript_parse_text("{failed}\n$poll:$port");
		return;
	}
	
	$sql="UPDATE fetchmail_rules SET sslfingerprint='$finger_print' WHERE ID='$rule_number'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	if($sock->GET_INFO("ArticaMetaEnabled")==1){$sock->getFrameWork("cmd.php?artica-meta-fetchmail-rules=yes");}
	$sock->getFrameWork('cmd.php?restart-fetchmail=yes');
}


function LocalFetchMailRc(){
	
	$user=new usersMenus();
	if($user->fetchmail_installed==false){
		
		$install_fetchmail="<tr><td align='center'><input type='button' value='{install_fetchmail}' OnClick=\"javascript:InstallFetchMail();\" style='width:150px'></td></tr>";
	}
	
	
	
	
	$sock=new sockets();
	$fetchmailrc=$sock->getfile('fetchmailrc');
	if(strlen($fetchmailrc)==0){return null;}
		
	$html="<table style='width:100%'>
	<tr>
	$install_fetchmail
	<td align='center'><input type='button' value='{import_local_rules}' OnClick=\"javascript:import_local_rules();\" style='width:150px'></td></tr>
	</table>
	
	";	
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	return RoundedLightGrey($html);	
	
	
	
}


function RightMenu(){
	
	
	$html= "
	<input type='hidden' id='load_user_rules_text'  value='{load_user_rules_text}'>
	<table style='width:100%'>
	<tr>
		<td align='center'><input type='button' value='{add_new_poll}' OnClick=\"javascript:add_fetchmail_rules();\" style='width:150px'></td>
	</tr>
	<tr>
		<td align='center'><input type='button' value='{load_user_rules}' OnClick=\"javascript:loadUserRules();\" style='width:150px'></td>
	</tr>	
	</table>
	<div id='rightresults'></div>
	";
	
	return RoundedLightGrey($html);
}

function import_1(){
	$sock=new sockets();
	$tpl=new templates();
	$fetchmailrc=$sock->getfile('fetchmailrc');
	$fr=new Fetchmail_settings();
	$fr->parse_config($fetchmailrc);
	if(!is_array($fr->main_array)){
		echo $tpl->_ENGINE_parse_body('{no_datas}');
		exit;
	}
	
	while (list ($num, $line) = each ($fr->main_array) ){
		
		$line="<table>
		<tr>
		<td width=1%><img src='img/fw_bold.gif'></td>
		<td>" . $line["poll"] . " {from} " . $line["user"] . " {to} " . $line["is"] . "</td>
		</tr>
		</table>";
		$list=$list. RoundedLightGrey($line,"javascript:LocalFetchMailRule($num);",1) . "<br>";
		
	}
	
	echo $tpl->_ENGINE_parse_body("<p><strong>{import1}</strong></p>" . $list);
	
}

function LocalRules(){
$sock=new sockets();
	$tpl=new templates();
	$fetchmailrc=$sock->getfile('fetchmailrc');
	$fr=new Fetchmail_settings();
	$fr->parse_config($fetchmailrc);	
	
	$rule=$fr->main_array[$_GET["LocalRules"]];
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(FormRules($rule));
	
}

function AddNewFetchMailRule(){
$tpl=new templates();
if(is_numeric($_GET["t"])){echo "<input type='hidden' id='timestamp-flexgrid' value='{$_GET["t"]}'>";}
echo $tpl->_ENGINE_parse_body(FormRules(array()));
	
}

function LdapRules(){
	
	$rule_number=$_GET["LdapRules"];
	$uid=$_GET["uid"];
	$ldap=new clladp();
	$fr=new Fetchmail_settings();
	$hash_rules=$fr->LoadRule($rule_number);
	
	if(is_numeric($_GET["t"])){echo "<input type='hidden' id='timestamp-flexgrid' value='{$_GET["t"]}'>";}
	echo FormRules($hash_rules,1,$rule_number);
	
	
}

function FormRules($array,$editmode=0,$rulenumber=0){
	$page=CurrentPageName();
	$title="<div style='font-size:28px;text-align:right;font-weight:bolder;margin-bottom:20px'>{$array["poll"]}</div>";
	$sock=new sockets();
	$FetchMailGLobalDropDelivered=$sock->GET_INFO("FetchMailGLobalDropDelivered");
	if(!is_numeric($FetchMailGLobalDropDelivered)){$FetchMailGLobalDropDelivered=0;}
	
	$proto=array(""=>"{select}",
					"auto"=>"AUTO IMAP, POP3,POP2",
				"pop2"=>"Post Office Protocol 2",
				"pop3"=>"Post Office Protocol 3",
				"apop"=>"POP3 with old-fashioned MD5-challenge authentication.",
				"rpop"=>"POP3 RPOP authentication.",
				"kpop"=>"POP3 Kerberos V4 (port 1109).",
				"sdps"=>"POP3 Demon Internet SDPS extensions.",
				"imap"=>"IMAP2bis, IMAP4,IMAP4rev1",
				"etrn"=>"ESMTP ETRN",
				"odmr"=>"On-Demand Mail Relay ESMTP profile. ",
				"hotmail"=>"Get Live Hotmail (@hotmail.x/@live.x)");

	$user=new usersMenus();
	if($user->hotwayd_installed==true){
		$proto["httpp"]="HTTP/webmail providers (lycos...)";
	}
	
	
	$proto=Field_array_Hash($proto,'_proto',$array["proto"],"FetchMailParseConfig()",null,0,"font-size:16px");
	
	$tracepolls=Field_checkbox('_tracepolls',1,$array["tracepolls"]);
	$ssl=Field_checkbox('_ssl',1,$array["ssl"]);
	$fetchall=Field_checkbox('_fetchall',1,$array["fetchall"]);
	$keep=Field_checkbox('_keep',1,$array["keep"]);
	//$nokeep=Field_checkbox('_nokeep',1,$array["nokeep"]); Mettre nokeep si _keep=1
	$sslcertck=Field_checkbox('_sslcertck',1,$array["sslcertck"]);
	$dropdelivered=Field_checkbox('_dropdelivered',1,$array["dropdelivered"],null,"{dropdelivered_explain}");
	
	
			
if($array["is"]==null){
	if($_SESSION["uid"]==-100){
		$array["is"]="{select_user}";
	}else{
		$ldap=new clladp();
		$h=$ldap->UserDatas($_SESSION["uid"]);
		$_GET["uid"]=$_SESSION["uid"];
		$array["is"]=$h["mail"];
	}
}
	
	if(!is_numeric($array["limit"])){$array["limit"]=2097152;}
	if($array["limit"]==0){$array["limit"]=2097152;}
	$array["limit"]=round($array["limit"]/1024);
	$textlimit="&nbsp;(".FormatBytes($array["limit"]).")";
	
	
	if($array["limit"]==1024){$textlimit="&nbsp;(1MB)";}
	if(!is_numeric($array["smtp_port"])){$array["smtp_port"]=25;}
	if(trim($array["smtp_host"])==null){$array["smtp_host"]="127.0.0.1";}
	if(!is_numeric($array["UseDefaultSMTP"])){$array["UseDefaultSMTP"]=1;}
	
	
	
	$form="
	$flexgrid
	<div style='font-size:26px;margin-bottom:20px'>{server_options}:</div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{enable}</strong>:&nbsp;</td>
		<td align='left'>" . Field_numeric_checkbox_img('_enabled',$array["enabled"],'{enable_disable}')."</td>
	</tr>	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{server}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('MailBoxServer',$array["poll"],'width:330px;font-size:16px')."</td>
	</tr>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{UseDefaultSMTP}</strong>:&nbsp;</td>
		<td align='left'>" . Field_checkbox("UseDefaultSMTP", 1,$array["UseDefaultSMTP"],"UseDefaultSMTPCheck()")."</td>
	</tr>				
	<tr>
		<td align='right' class=legend style='font-size:16px'>{server} (SMTP)</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_smtp_host',$array["smtp_host"],'width:330px;font-size:14px')."</td>
	</tr>	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{port} (SMTP)</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_smtp_port',$array["smtp_port"],'width:90px;font-size:14px')."</td>
	</tr>	
	
	<tr>
		<td align='right' class=legend nowrap style='font-size:16px'>{aka}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_aka',$array["aka"],'width:90%;font-size:14px')."</td>
	</tr>	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{protocol}</strong>:&nbsp;</td>
		<td align='left'>$proto</td>
	</tr>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{port}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_port',$array["port"],'width:20%;font-size:14px')."</td>
	</tr>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{max_size}</strong>:&nbsp;</td>
		<td align='left' style='font-size:14px'>" . Field_text('_limit',$array["limit"],'width:120px;font-size:16px')."&nbsp;K$textlimit</td>
	</tr>		
	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{dropdelivered}</strong>:&nbsp;</td>
		<td align='left'>$dropdelivered</td>
	</tr>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{timeout_cnx}</strong>:&nbsp;</td>
		<td align='left' style='font-size:14px'>" . Field_text('_timeout',$array["timeout"],'width:120px;font-size:16px')."&nbsp;{seconds}</td>
	</tr>			
	<tr>
		<td align='right' class=legend style='font-size:16px'>{interval}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_interval',$array["interval"],'width:120px;font-size:16px',null,null)."</td>
	</tr>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{ssl_fingerprint}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_fingerprint',$array["sslfingerprint"],'width:220px;font-size:16px',null,null)."</td>
	</tr>
	
	
	<td colspan=2>
	<table>
		<tr>
		<td align='right' class=legend style='font-size:16px'>{tracepolls}</strong>:&nbsp;</td>
		<td align='left'>$tracepolls&nbsp;</td>	
		<td width=1%>&nbsp;</td>
		</tr>
		<tr>	
		<td align='right' class=legend style='font-size:16px'>{ssl}</strong>:&nbsp;</td>
		<td align='left'>$ssl&nbsp;</td>
		<td width=1%>&nbsp;</td>
		</tr>
		<tr>	
		<td align='right' class=legend style='font-size:16px'>{sslcertck}</strong>:&nbsp;</td>
		<td align='left'>$sslcertck&nbsp;</td>
		<td width=1%>". help_icon("{sslcertck_text}")."</td>
		</tr>		
		
		<tr>
		<td align='right' class=legend style='font-size:16px'>{fetchall}</strong>:&nbsp;</td>
		<td align='left'>$fetchall&nbsp;</td>
		<td width=1%>&nbsp;</td>
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:16px'>{keepmess}</strong>:&nbsp;</td>
			<td align='left'>$keep&nbsp;</td>
			<td width=1%>&nbsp;</td>	
		</tr>		
</table>
</td>
</tR>
</table>

	";
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator or $user->AsPostfixAdministrator){
		$is="<span onMouseOver=\"javascript:AffBulle('{cliktochange}');lightup(this, 100);\" 
		OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" 
		style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\" 
		OnClick=\"javascript:ChangeFetchMailUser();\">
		<a href='#' id='is_html' style='font-size:14px;font-weight:bold;text-decoration:underline'>{$array["is"]}</a>
		</span>
		";
		
	}else{$is=$array["is"];}
	
$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");	

if($EnableFetchmailScheduler==1){
	
	$schedule="	<tr>
		<td align='right' class=legend style='font-size:16px'>{schedule}</strong>:&nbsp;</td>
		<td align='left' style='font-size:16px'>
			<input type='hidden' name='_schedule' value='{$array["schedule"]}' id='_schedule'>
			<strong id='schedule-id'>{$array["schedule"]}</strong>
			</td>
		<td>". button("{schedule}", "Loadjs('cron.php?field=_schedule&function2=UpdateScheduleView')",14)."</td>
	</tr>";
}

$form2="
	<div style='font-size:26px;margin-bottom:20px'>{user_option}:</div>
	<span id='hotmailexplain'></span>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td align='right' class=legend style='font-size:16px'>{remoteuser}</strong>:&nbsp;</td>
		<td align='left'>" . Field_text('_user',$array["user"],'width:70%;font-size:16px')."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{password}</strong>:&nbsp;</td>
		<td align='left'>" . Field_password('_pass',$array["pass"],'width:70%;font-size:16px')."</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td align='right' class=legend style='font-size:16px'>{localuser}</strong>:&nbsp;</td>
		<td align='left' style='font-size:16px'>$is<input type='hidden' name='_is' value='{$array["is"]}' id='_is'></td>
		<td>&nbsp;</td>
	</tr>
	$schedule
	<tr>
		<td align='right' class=legend style='font-size:16px'>{multidrop}</strong>:&nbsp;</td>
		<td align='left'>" . Field_checkbox('_multidrop',1,$array["multidrop"],'{enable_disable}')."</td></td>
		<td>&nbsp;</td>
	</tr>	

	

	</table>
	</div>
		";
	
	

	
	if($editmode==1){
		$button=button("{apply}","FetchMailPostForm($editmode)",28);
		$button_delete=button("{delete}","UserDeleteFetchMailRule($rulenumber)",28);
		if($_SESSION["uid"]<>-100){$button_delete=null;}
		
	}else{
		$button=button("{add}","FetchMailPostForm($editmode)",28);
		
	}
	
	$option4=null;
	$option3=Paragraphe("folder-dedup-64.png",'{folders}','{fetch_folders_explain}',"javascript:Loadjs('fetchmail.rule.folder.php?ruldeid=$rulenumber&uid={$_GET["uid"]}')",null,180,20);
	if($rulenumber>0){
		$option4=Paragraphe("web-ssl-64.png",'{import_fingerprint}','{import_fingerprint}',"javascript:Loadjs('$page?ssl-fingerprint=yes&LdapRules=$rulenumber&uid={$_GET["uid"]}')",null,180,20);
	}
	
	
	$html="
	<div id='fetchmailadvrule'>
	$title
	<input type='hidden' id='uid' value='{$_GET["uid"]}'>
	<input type='hidden' id='rule_number' value='$rulenumber'>
	<input type='hidden' id='confirm' value='{confirm}'>
	<input type='hidden' id='ChangeFetchMailUserText' value='{ChangeFetchMailUserText}'>
	<input type='hidden' id='hotmail_text' value='{hotmail_text}'>
	<input type='hidden' id='hotwayd_text' value='{hotwayd_text}'>
	<form name='FFM1'>
	<div style='margin-top:-5px'>
	<table style='width:100%'>
	<tr>
		<td valign='top'>$option3 $option4
		</td>
	<td valign='top' width=80%>
		<div id='server_options' style='display:block'>
			$form
		</div>
		<div id='users_options' style='display:block'>
			$form2
		</div>
			<div style='text-align:right;width:100%;margin-top:20px'>
			$button_delete&nbsp;&nbsp;$button
			</div>	
	</td>
	</tr>
	</table>			
	</div>
	</form>
	</div>
	<script>
	var x_UseDefaultSMTPCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}
	
	
	
		function UseDefaultSMTPCheck(){
			document.getElementById('_smtp_host').disabled=true;
			document.getElementById('_smtp_port').disabled=true;
			var value=1;
			if(!document.getElementById('UseDefaultSMTP').checked){
				document.getElementById('_smtp_host').disabled=false;
				document.getElementById('_smtp_port').disabled=false;
				value=0;			
			}
			
			var XHR = new XHRConnection();
    		XHR.appendData('UseDefaultSMTP',value);
    		XHR.appendData('ruleid','$rulenumber');
    		XHR.sendAndLoad('$page', 'POST',x_UseDefaultSMTPCheck);
		
		}
	
	
		function CheckForms(){
			document.getElementById('_smtp_host').disabled=true;
			document.getElementById('_smtp_port').disabled=true;
			var FetchMailGLobalDropDelivered=$FetchMailGLobalDropDelivered;
			document.getElementById('_interval').disabled=true;
			
			if(FetchMailGLobalDropDelivered==1){
				document.getElementById('_dropdelivered').checked=true;
				document.getElementById('_dropdelivered').disabled=true;
			}
			
			if(!document.getElementById('UseDefaultSMTP').checked){
				document.getElementById('_smtp_host').disabled=false;
				document.getElementById('_smtp_port').disabled=false;	
			}		
			
			
		}
		
		function UpdateScheduleView(){
			document.getElementById('schedule-id').innerHTML=document.getElementById('_schedule').value;
		}
		
	YahooWinHide();
	CheckForms();
	</script>
	
	";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);;
	
	
}

function LoadUserRules(){
$ldap=new clladp();
$hash=$ldap->find_users_by_mail($_GET["UserRules"]);
$tpl=new templates();
if(!is_array($hash)){
	echo $tpl->_ENGINE_parse_body("Pattern: {$_GET["UserRules"]} : {no_users_in_database");
	exit;
}

$html="<br><table style='width:100%'>";
while (list ($num, $ligne) = each ($hash) ){
	
	$u=$ldap->UserDatas($num);
	$count=count($u["FetchMailsRulesSources"]);
	if($count>0){
		$uri=texttooltip($num,'{edit_rules}',"LoadFetchMailRuleFromUser('$num')");
	}else{$uri=$num;}
	$html=$html . "<tr>
		<td width=1%><img src='img/fw_bold.gif'></td>
		<td>$uri</td>
		<td>$ligne</td>
		<td>$count {rules}</td>
		</tr>";
}	
$html=$html . "</table>";
echo RoundedLightGreen($tpl->_ENGINE_parse_body($html));
	
}


function UseDefaultSMTP_save(){
	$ruleid=$_POST["ruleid"];
	$UseDefaultSMTP=$_POST["UseDefaultSMTP"];
	$q=new mysql();
	$q->QUERY_SQL("UPDATE fetchmail_rules SET UseDefaultSMTP=$UseDefaultSMTP WHERE ID=$ruleid","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function SavePools(){
	
	$user=$_GET["is"];
	writelogs("local user is :\"{$_GET["is"]}\"",__FUNCTION__,__FILE__);
	$tpl=new templates();
	$ldap=new clladp();
		$dn=$ldap->dn_from_email($user);
		if($dn==null){
			echo $tpl->_ENGINE_parse_body("\"$user\"\n{doesntexists}");
			exit;
			}	
			
	
	
	$fr=new Fetchmail_settings();
	if($fr->EditRule($_GET,$_GET["rule_number"])){
		$fetchmail=new fetchmail();
		$fetchmail->Save();
		echo $tpl->javascript_parse_text('{success}');}
		else{echo $tpl->javascript_parse_text('{failed}');}

}
	
function InstallFetchmail(){
	$sock=new sockets();
	$logs=$sock->getfile('AUTOINSTALL:APP_FETCHMAIL');
	$table=explode("\n",$logs);
	if(is_array($table)){
		$html="<table style='width:100%'>";
		while (list ($num, $ligne) = each ($table) ){
			if($ligne<>null){
			$ligne=htmlentities($ligne);
			$html=$html . "<tr><td style='padding:3px'><code>$ligne</code></td></tr>";
			}
			
		}
		
		$html=$html . "</table>";
		
		
	}
	$html="<H5>{results}</h5>$html";
	$tpl=new templates();
	$html=RoundedLightGreen($tpl->_ENGINE_parse_body($html));
	echo $html;
}
function LoadFetchMailRuleFromUser(){
	$ldap=new clladp();
	$u=$ldap->UserDatas($_GET["LoadFetchMailRuleFromUser"]);
	$fr=new Fetchmail_settings();
	$tpl=new templates();
	while (list ($num, $ligne) = each ($u["FetchMailsRulesSources"]) ){
		$arr=$fr->parse_config($ligne);
		$arr=$arr[1];
		$line="
		<table>
		<tr>
		<td width=1% valign='top'><img src='img/fw_bold.gif'></td>
		<td width=1% nowrap valign='top'>{rule} $num</td>
		<td>" . $arr["poll"] . " {from} " . $arr["user"] . " {to} " . $arr["is"] . "</td>
		</tr>
		</table>";
		$res[]=RoundedLightGrey($tpl->_ENGINE_parse_body($line),"javascript:UserFetchMailRule($num,'{$_GET["LoadFetchMailRuleFromUser"]}');",1);
		}
	
	echo $tpl->_ENGINE_parse_body(implode("<br>",$res));
	echo $list;
	
	
	
	
}

function UserDeleteFetchMailRule(){
	$rule_num=$_GET["UserDeleteFetchMailRule"];
	$fetch=new Fetchmail_settings();
	$fetch->DeleteRule($rule_num,$_GET["uid"]);
	
}

function ChangeFetchMailUser(){
	if($_GET["ChangeFetchMailUser"]=="*"){return null;}
	$ldap=new clladp();
	$uid=$ldap->uid_from_email($_GET["ChangeFetchMailUser"]);
	if(trim($uid)==null){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("\n{$_GET["ChangeFetchMailUser"]}\n*****************\n{error_no_user_exists}");
		
	}
	
	
	
}


?>