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
	include_once('ressources/class.spamassassin.inc');
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	
	$user=new usersMenus();
	if(!isset($_GET["hostname"])){
		if(!$user->AsPostfixAdministrator){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
	}else{
		if(!PostFixMultiVerifyRights()){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
		
	}
	if(isset($_POST["SpamAssMilterEnabled"])){SpamAssMilterEnabled();exit;}
	if(isset($_GET["config"])){config();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}

tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["config"]='{parameters}';
	$array["APP_SPF"]='{APP_SPF}';
	$font="style='font-size:24px'";

	$master=urlencode(base64_encode("master"));
	$suffix="&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="APP_SPF"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"spamassassin.spf.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"$page?$num=yes&hostname=master&ou=$master\"><span>$ligne</span></a></li>\n");


	}


	echo build_artica_tabs($html, "main_config_milter_spamass",1490);

}

function config(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	
	
	
	$t=time();
	$spam=new spamassassin();
	$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
	$block_with_required_score=trim($sock->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($block_with_required_score==null){$block_with_required_score=5;}
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:350px'>
			<div style='width:98%' class=form>
				<div style='font-size:30px;margin-bottom:20px'>{services_status}</div>
				<div id='SpamAssMilter-status'></div>
				<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}",
						"LoadAjax('SpamAssMilter-status','$page?services-status=yes')")."</div>
			</div>
		
		
		</td>
		<td valign='top' style='padding-left:15px'>
	<div style='font-size:60px;margin-bottom:15px'>{APP_SPAMASS_MILTER}</div>	
	<hr>	
	<div id='test-$t'></div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
		<table>
		<tr>
		<td colspan=2>". Paragraphe_switch_img("{enable_spamasssin}", 
				"{enable_spamasssin_text}","SpamAssMilterEnabled",
				"$SpamAssMilterEnabled",null,1050)."</td>
		</tr>
						
	<table style='width:100%'>
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{report_safe}","{report_safe_text}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("report_safe-$t",$spam->main_array["report_safe"])."</td>
		</tr>
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{use_bayes}","{use_bayes}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("use_bayes-$t",$spam->main_array["use_bayes"])."</td>
		</tr>			
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{auto_learn}","{auto_learn}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("bayes_auto_learn-$t",$spam->main_array["bayes_auto_learn"])."</td>
		</tr>	
	
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{required_score}","{required_score_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("required_score-$t",$spam->main_array["required_score"],'width:110px;font-size:22px',null,null,'{required_score_text}')."</td>
		</tr>			
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{block_with_required_score}","{block_with_required_score_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("block_with_required_score-$t",$block_with_required_score,'width:110px;font-size:22px',null,null)."</td>
		</tr>						
						
		<tr>
			<td colspan=2  align='right'><hr>". button("{apply}", "Save$t()","40px")."</td>
		</tr>
</table>
</div>
</td>
</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('postfix.milters.progress.php');
	RefreshTab('main_config_milter_spamass');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SpamAssMilterEnabled',document.getElementById('SpamAssMilterEnabled').value);
	XHR.appendData('block_with_required_score',document.getElementById('block_with_required_score-$t').value);
	XHR.appendData('required_score',document.getElementById('required_score-$t').value);
	if(document.getElementById('report_safe-$t').checked){XHR.appendData('report_safe',1);}else{XHR.appendData('report_safe',0);}
	if(document.getElementById('use_bayes-$t').checked){XHR.appendData('use_bayes',1);}else{XHR.appendData('use_bayes',0);}
	if(document.getElementById('bayes_auto_learn-$t').checked){XHR.appendData('bayes_auto_learn',1);}else{XHR.appendData('bayes_auto_learn',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(){
	LoadAjax('SpamAssMilter-status','$page?services-status=yes');
	
}
Check$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function SpamAssMilterEnabled(){
	$sock=new sockets();
	$sock->SET_INFO("SpamAssMilterEnabled", $_POST["SpamAssMilterEnabled"]);
	$sock->SET_INFO("SpamAssBlockWithRequiredScore", $_POST["block_with_required_score"]);
	
	
	$spam=new spamassassin();
	$spam->block_with_required_score=$_POST["block_with_required_score"];
	$spam->required_score=$_POST["required_score"];
	$spam->report_safe=$_POST["report_safe"];
	$spam->use_bayes=$_POST["use_bayes"];
	$spam->bayes_auto_learn=$_POST["bayes_auto_learn"];
	$spam->SaveToLdap();
	
}
function services_status(){

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('milter-spamass.php?status=yes')));
	$APP_RDPPROXY=DAEMON_STATUS_ROUND("SPAMASS_MILTER",$ini,null,0);
	$SPAMASSASSIN=DAEMON_STATUS_ROUND("SPAMASSASSIN",$ini,null,0);


	$tr[]=$APP_RDPPROXY;
	$tr[]=$SPAMASSASSIN;

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $tr));

}