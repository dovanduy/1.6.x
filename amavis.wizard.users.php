<?php
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup2"])){popup2();exit;}
	if(isset($_POST["BuildAndStartMysql"])){BuildAndStartMysql();exit;}
	js();
	
function js(){
	header("content-type: application/x-javascript");
	echo "alert('Not currently available');";return;
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{amavis_wizard_rule_per_user}");
	echo "YahooWin4(700,'$page?popup=yes','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div id=$t>
		<div style='font-size:16px' class=text-info>{amavis_wizard_rule_per_user_expain1}</div>
		<div style='margin-top:10px;text-align:right'><hr>
			". button("{next}","SaveTime$t()",18)."</div>
	
	<script>
	var xSaveTime$t= function (obj) {
		//var response=obj.responseText;
		//if(response){alert(response);}
    	//YahooWin(550,'amavis.index.php?sanesecurity-popup=yes','SaneSecurity');    
	}	
	
	function SaveTime$t(){
		//var XHR = new XHRConnection();
		//XHR.appendData('sanesecurity_enable',document.getElementById('sanesecurity_enable').value);
		//document.getElementById('sanesecuid').innerHTML='<img src=\"img/wait_verybig.gif\">';
		//XHR.sendAndLoad('amavis.index.php', 'GET',x_sanesecurity_enable);	
		LoadAjax('$t','$page?popup2=yes');
	}
	</div>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function popup2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div style='font-size:16px' class=text-info>{amavis_wizard_rule_per_user_expain2}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{build_mysql_service}:</td>
		<td style='valign='top'><div id='BuildAndStartMysql$t'><img src=img/ok32-grey.png></div>
	</tr>
	</table>
	
	
		<div style='margin-top:10px;text-align:right'><hr>
			". button("{build_feature}","SaveTime$t()",18)."</div>
	<script>
	var xBuildAndStartMysql$t= function (obj) {
			var response=obj.responseText;
			if(response){
				document.getElementById('BuildAndStartMysql$t').innerHTML=response;
			}
 
	}	
	
	function SaveTime$t(){
		var XHR = new XHRConnection();
		XHR.appendData('BuildAndStartMysql','yes');
		document.getElementById('BuildAndStartMysql$t').innerHTML='<img src=\"img/wait_verybig.gif\">';
		XHR.sendAndLoad('$page', 'POST',xBuildAndStartMysql$t);	
	}	
					
	</script>
	";	
	
	
	
}

function BuildAndStartMysql(){
	$sock=new sockets();
	$sock->SET_INFO("AmavisPerUser", 1);
	$sock->getFrameWork("amavis.php?per-user-mysql=yes");
	
}


