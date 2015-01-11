<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSquidAdministrator==false){die('not allowed');}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["CiCAPMemBoost"])){SAVE();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{memory_booster}");
	$html="YahooWin6('512','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$CiCAPMemBoost=$sock->GET_INFO("CiCAPMemBoost");
	if(!is_numeric($CiCAPMemBoost)){$CiCAPMemBoost=0;}
	$users=new usersMenus();
	$licenserror=0;
	if(!$users->CORP_LICENSE){
		$licenserror=1;
		$error_page_js=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	}
	
	$html="
	<div class=text-info style='font-size:14px'>{CICAP_MEM_BOOST_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:24px;vertical-align:top'>{memory}:</td>
		<td style='font-size:24px'>". Field_text("CiCAPMemBoost",$CiCAPMemBoost,"font-size:24px;width:180px")."&nbsp;M</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveTemplateForm$t()","24px")."</td>
	</tr>
	<script>
	var x_SaveTemplateForm$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin6Hide();
    }	    
	
	 function SaveTemplateForm$t(){
	 	var licenserror=$licenserror;
	 	if(licenserror==1){alert('$error_page_js');return;}
      	var XHR = new XHRConnection();
      	XHR.appendData('CiCAPMemBoost',document.getElementById('CiCAPMemBoost').value);
      	XHR.sendAndLoad('$page', 'POST',x_SaveTemplateForm$t);          
      }	
     </script>	
	";	

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SAVE(){
	$sock=new sockets();
	
	$sock->SET_INFO("CiCAPMemBoost", $_POST["CiCAPMemBoost"]);
	$sock->getFrameWork("squid.php?cicap-memboost=yes");
}

