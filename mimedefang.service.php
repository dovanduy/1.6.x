<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["MX_REQUESTS"])){save();exit;}
	js();
	
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}:{performances}:{service_options}");
	echo "YahooWin2('650','$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));
	$t=time();
	if(!is_numeric($Param["DEBUG"])){$Param["DEBUG"]=0;}
	if(!is_numeric($Param["MX_REQUESTS"])){$Param["MX_REQUESTS"]=200;}
	if(!is_numeric($Param["MX_MINIMUM"])){$Param["MX_MINIMUM"]=2;}
	if(!is_numeric($Param["MX_MAXIMUM"])){$Param["MX_MAXIMUM"]=10;}
	if(!is_numeric($Param["MX_MAX_RSS"])){$Param["MX_MAX_RSS"]=30000;}
	if(!is_numeric($Param["MX_MAX_AS"])){$Param["MX_MAX_AS"]=90000;}
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}
	
	
	
	$html="
	<div id='$t-adddis'></div>
	<table style='width:100%' class=form>
	
	<tr>
		<td class=legend style='font-size:16px'>{debug}:</td>
		<td>". Field_checkbox("DEBUG-$t", 1,$Param["DEBUG"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{workingdir_in_memory}:</td>
		<td style='font-size:16px'>". Field_text("MX_TMPFS-$t", $Param["MX_TMPFS"],"font-size:16px;width:90px")."&nbsp;M</td>
		<td>". help_icon("{workingdir_in_memory_text}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{max_requests}:</td>
		<td>". Field_text("MX_REQUESTS-$t", $Param["MX_REQUESTS"],"font-size:16px;width:90px")."</td>
		<td>". help_icon("{MX_REQUESTS_TEXT}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{MX_MINIMUM}:</td>
		<td>". Field_text("MX_MINIMUM-$t", $Param["MX_MINIMUM"],"font-size:16px;width:90px")."</td>
		<td>". help_icon("{MX_MINIMUM_TEXT}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{MX_MAXIMUM}:</td>
		<td>". Field_text("MX_MAXIMUM-$t", $Param["MX_MAXIMUM"],"font-size:16px;width:90px")."</td>
		<td>". help_icon("{MX_MAXIMUM_TEXT}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{MX_MAX_RSS}:</td>
		<td style='font-size:16px'>". Field_text("MX_MAX_RSS-$t", $Param["MX_MAX_RSS"],"font-size:16px;width:110px")."&nbsp;KB</td>
		<td>". help_icon("{MX_MAX_RSS_TEXT}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{MX_MAX_AS}:</td>
		<td style='font-size:16px'>". Field_text("MX_MAX_AS-$t", $Param["MX_MAX_AS"],"font-size:16px;width:110px")."&nbsp;KB</td>
		<td>". help_icon("{MX_MAX_AS_TEXT}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveMimeService$t()","18px")."</td>
	</tr>	
	</table>

	<script>
		var x_SaveMimeService$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t-adddis').innerHTML='';
			YahooWin2Hide();
		}		
	
		function SaveMimeService$t(){
		var XHR = new XHRConnection();  
		  var DEBUG=0;
		  if(document.getElementById('DEBUG-$t').checked){DEBUG=1;}
	      XHR.appendData('MX_MAX_AS',document.getElementById('MX_MAX_AS-$t').value);
	      XHR.appendData('MX_MAX_RSS',document.getElementById('MX_MAX_RSS-$t').value);
	      XHR.appendData('MX_MAXIMUM',document.getElementById('MX_MAXIMUM-$t').value);
	      XHR.appendData('MX_MINIMUM',document.getElementById('MX_MINIMUM-$t').value);
	      XHR.appendData('MX_REQUESTS',document.getElementById('MX_REQUESTS-$t').value);
	      XHR.appendData('MX_TMPFS',document.getElementById('MX_TMPFS-$t').value);
	      XHR.appendData('DEBUG',DEBUG);
	      
	      
		  AnimateDiv('$t-adddis');
		  XHR.sendAndLoad('$page', 'POST',x_SaveMimeService$t);
		}
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "MimeDefangServiceOptions");
	echo $tpl->javascript_parse_text("{you_need_to_restart_service_take_effet}",1);
}
	

