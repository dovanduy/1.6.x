<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "<H1>".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		exit;
		
	}
	
	if(isset($_POST["TemplateColor-ruleid"])){TemplateColorSave();exit;}
	if(isset($_POST["ruleid"])){Dans2Save();exit;}
	if(isset($_POST["DansGuardianHTMLTemplate"])){Dans2Save();exit;}
	
	if(isset($_GET["Dans2"])){Dans2();exit;}
	if(isset($_GET["js"])){js();exit;}
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	$groupname="Default";
	if($ID>-1){
		$q=new mysql_squid_builder();
		$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$groupname=$ligne["groupname"];
	}	
	
	$addfree=$tpl->javascript_parse_text("{template}");
	$t=$_GET["t"];
	echo "
	if(document.getElementById('anim-img-{$_GET['ID']}')){document.getElementById('anim-img-{$_GET['ID']}').innerHTML='';}
	
	YahooWin2('850','$page?Dans2=yes&ID={$_GET["ID"]}&byjs=yes','$addfree::$groupname')";	
	
}

function TemplateColorSave(){
	$template=@file_get_contents("ressources/databases/dansguard-template.html");
	if($_POST["TemplateColor1"]==null){$_POST["TemplateColor1"]="BF0A0A";}
	if($_POST["TemplateColor2"]==null){$_POST["TemplateColor2"]="E33630";}
	$template=str_replace("BF0A0A", $_POST["TemplateColor1"], $template);
	$template=str_replace("E33630", $_POST["TemplateColor2"], $template);
	$ID=$_POST["TemplateColor-ruleid"];
	if(!is_numeric($ID)){echo "<H3>Wrong rule ID</H3>";return;}
	
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$ligne["TemplateError"]=$template;
		$ligne["TemplateColor1"]=$_POST["TemplateColor1"];
		$ligne["TemplateColor2"]=$_POST["TemplateColor2"];
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");
		return;
	}
	
	$template=mysql_escape_string2($template);
	
	$sql="UPDATE webfilter_rules SET 
		TemplateError='$template',
		TemplateColor1='{$_POST["TemplateColor1"]}',
		TemplateColor2='{$_POST["TemplateColor2"]}'
		WHERE ID=$ID";
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}	
	
}
	
	
function Dans2(){
	$q=new mysql_squid_builder();	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->CORP_LICENSE){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{MOD_TEMPLATE_ERROR_LICENSE}")."</p>";
	}
	
	$ID=$_GET["ID"];
	$tpl=new templates();
	$onlydefaulttemplate_remove=$tpl->javascript_parse_text("{onlydefaulttemplate_remove}");
	if($ID>0){
		$sql="SELECT groupname,TemplateError,TemplateColor1,TemplateColor2 FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo "<h3>$q->mysql_error</H3>";}
	}
	
	if($ID==0){
		$sock=new sockets();
		$ligne["groupname"]="Default";
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}	
	
	$lenght=strlen($ligne["TemplateError"]);
	
	if($lenght<120){
		$ligne["TemplateError"]=@file_get_contents("ressources/databases/dansguard-template.html");
	}
	if(($ligne["groupname"]==null) && ($ID==0)){
		$ligne["groupname"]="Default";
		
	}

	
	
	$sock=new sockets();
	$DansGuardianHTMLTemplate=$ligne["TemplateError"];
	$foot="</body>
	
	</html>";
	
	$head="	<html>
	<head>
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<script type='text/javascript' language='JavaScript' src='mouse.js'></script>
	<script type='text/javascript' language='javascript' src='XHRConnection.js'></script>
	<script type='text/javascript' language='javascript' src='default.js'></script>
	<script language=\"javascript\" type=\"text/javascript\" src=\"js/tiny_mce/tinymce.min.js\"></script>
	</head>
	<body width=100%> ";
	
	$t=time();
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",18));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",18));
	$tiny=TinyMce('DansGuardianHTMLTemplate',$DansGuardianHTMLTemplate,true);
	
	if($ligne["TemplateColor1"]==null){$ligne["TemplateColor1"]="BF0A0A";}
	if($ligne["TemplateColor2"]==null){$ligne["TemplateColor2"]="E33630";}
	
	if(isset($_GET["byjs"])){
		
		$head=null;$foot=null;}
	
	$html="
	$head	
	
	

	
	<div id='$t'></div>
	
	<div class=form style='width:95%'>
	<table>
	<tr>
		<td class=legend style='font-size:16px'>{color} 1:</td>
		<td>". Field_ColorPicker("TemplateColor1-$t",$ligne["TemplateColor1"],"font-size:16px")."</td>
		<td class=legend style='font-size:16px'>{color} 2:</td>
		<td>". Field_ColorPicker("TemplateColor2-$t",$ligne["TemplateColor2"],"font-size:16px")."</td>
		<td colspan=2 align='right'>". button("{apply}", "SaveColor$t()",14)."</td>
	</tr>
	</table>
	</div>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$button<br></div>
	<center><div style='font-size:16px'>{rule} $ID&nbsp;|&nbsp;{$ligne["groupname"]} ($lenght bytes)</div>
	<div style='width:750px;height:auto'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>
		$button2
	</div>
	
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
	Loadjs('$page?js=yes&ID=$ID');
}
function Save2$t(){ Save$t();}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ruleid', '$ID');
	AnimateDiv('$t');
	XHR.appendData('DansGuardianHTMLTemplate', encodeURIComponent(tinymce.get('DansGuardianHTMLTemplate').getContent()));
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
function SaveColor$t(){
	var XHR = new XHRConnection();
	if(confirm('$onlydefaulttemplate_remove')){
		XHR.appendData('TemplateColor-ruleid', '$ID');
		XHR.appendData('TemplateColor1', document.getElementById('TemplateColor1-$t').value);
		XHR.appendData('TemplateColor2', document.getElementById('TemplateColor2-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}		
}
	</script>
	
	$foot
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function Dans2Save(){
	
	$_POST["DansGuardianHTMLTemplate"]=url_decode_special_tool($_POST["DansGuardianHTMLTemplate"]);
	
	if(!is_numeric($_POST["ruleid"])){echo "<H3>Wrong rule ID</H3>";return;}
	$_GET["ID"]=$_POST["ruleid"];
	if($_POST["ruleid"]==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$ligne["TemplateError"]=stripslashes($_POST["DansGuardianHTMLTemplate"]);
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");
		return;
	}
	
	
	
	$sql="UPDATE webfilter_rules SET TemplateError='{$_POST["DansGuardianHTMLTemplate"]}' WHERE ID={$_POST["ruleid"]}";
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
}
	
	
	
	if(isset($_POST["DansGuardianHTMLTemplate"])){save();}
	
	$sock=new sockets();
	$DansGuardianHTMLTemplate=$sock->GET_INFO("DansGuardianHTMLTemplate");	
	if(strlen($DansGuardianHTMLTemplate)<50){
		$DansGuardianHTMLTemplate=$sock->getFrameWork("cmd.php?dansguardian-get-template=yes");
	}
	
	
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body("<input type='submit' value='{apply}'>");
	$tiny=TinyMce('DansGuardianHTMLTemplate',$DansGuardianHTMLTemplate);
	$html="
	<html>
	<head>
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
	<script type='text/javascript' language='JavaScript' src='mouse.js'></script>
	<script type='text/javascript' language='javascript' src='XHRConnection.js'></script>
	<script type='text/javascript' language='javascript' src='default.js'></script>
		
	</head>
	<body width=100%> 
	<form name='FFM1' METHOD=POST>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$squidguard_form$button<br></div>
	<center>
	<div style='width:750px;height:900px'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>$button</div>
	
	</form>
	</body>
	
	</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
function save(){
$sock=new sockets();
$_POST["DansGuardianHTMLTemplate"]=stripslashes($_POST["DansGuardianHTMLTemplate"]);
$sock->SaveConfigFile($_POST["DansGuardianHTMLTemplate"],"DansGuardianHTMLTemplate");
$sock->getFrameWork("cmd.php?dansguardian-template=yes");
}




?>