<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.kav4proxy.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["TEMPLATE_GET"])){TEMPLATE_GET();exit;}
	if(isset($_POST["TEMPLATE_SET"])){TEMPLATE_SET();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{squid_templates_error}");
	$html="YahooWinBrowse('700','$page?popup=yes','$title')";
	echo $html;	
}


function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$kav=new Kav4Proxy();
	$array=$kav->templates;
	$vals[null]="{select}";
	while (list ($templateName, $val) = each ($array) ){
		$vals[$templateName]="{{$templateName}}";
		
	}
	
	$t=time();
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{squid_templates_error}
		<td>". Field_array_Hash($vals, "template-$t",null,"SelectTemplateData$t()",null,0,"font-size:16px")."</td>
		<td width=1%><span id='anim-$t'></span></td>
	</tr>
	</table>
			<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:620px;border:5px solid #8E8E8E;overflow:auto;font-size:12.5px'
	id='textarea$t'></textarea>
	<center style='margin:10px'>". button("{apply}","SaveTemplateData$t();",18)."</center>
	<script>
	
	var X_SelectTemplateData$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){document.getElementById('textarea$t').value=tempvalue;}
		document.getElementById('anim-$t').innerHTML='';
	}	
	var X_SaveTemplateData$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('anim-$t').innerHTML='';
	}		
	
		function SelectTemplateData$t(){
			var template=document.getElementById('template-$t').value;
			var XHR = new XHRConnection();
			XHR.appendData('TEMPLATE_GET',template);
			document.getElementById('anim-$t').innerHTML='<img src=img/preloader.gif>';
			XHR.sendAndLoad('$page', 'POST',X_SelectTemplateData$t);  		
		}
		function SaveTemplateData$t(){
			var template=document.getElementById('template-$t').value;
			var XHR = new XHRConnection();
			XHR.appendData('TEMPLATE_SET',template);
			XHR.appendData('data',document.getElementById('textarea$t').value);
			document.getElementById('anim-$t').innerHTML='<img src=img/preloader.gif>';
			XHR.sendAndLoad('$page', 'POST',X_SaveTemplateData$t);  		
		}	
	
	</script>
	";

	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function TEMPLATE_GET(){
	$kav=new Kav4Proxy();
	echo $kav->templates_data[$_POST["TEMPLATE_GET"]];
	
}
function TEMPLATE_SET(){
	$kav=new Kav4Proxy();
	$_POST["data"]=stripslashes($_POST["data"]);
	if($kav->set_template($_POST["TEMPLATE_SET"],$_POST["data"])){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{success}");
	}
	
}

