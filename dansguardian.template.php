<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
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
	
	
	if(isset($_POST["ruleid"])){Dans2Save();exit;}
	if(isset($_GET["Dans2"])){Dans2();exit;}
	
	
function Dans2(){
	$q=new mysql_squid_builder();		
	$ID=$_GET["ID"];
	$sql="SELECT groupname,TemplateError FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<h3>$q->mysql_error</H3>";}
	
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
	<script type='text/javascript' language='JavaScript' src='mouse.js'></script>
	<script type='text/javascript' language='javascript' src='XHRConnection.js'></script>
	<script type='text/javascript' language='javascript' src='default.js'></script>
		
	</head>
	<body width=100%> 
	<form name='FFM1' METHOD=POST>
	<input type='hidden' name='ruleid' id='ruleid' value='$ID'>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$squidguard_form$button<br></div>
	<center><div style='font-size:16px'>{rule} $ID&nbsp;|&nbsp;{$ligne["groupname"]} ($lenght bytes)</div>
	<div style='width:750px;height:900px'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>$button</div>
	
	</form>
	</body>
	
	</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function Dans2Save(){
	
	if(!is_numeric($_POST["ruleid"])){echo "<H3>Wrong rule ID</H3>";return;}
	$_GET["ID"]=$_POST["ruleid"];
	if($_POST["ruleid"]==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$ligne["TemplateError"]=stripslashes($_POST["DansGuardianHTMLTemplate"]);
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");
		Dans2();
		return;
	}
	
	
	
	$sql="UPDATE webfilter_rules SET TemplateError='{$_POST["DansGuardianHTMLTemplate"]}' WHERE ID={$_POST["ruleid"]}";
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H1>$q->mysql_error</H1>";return;}
	Dans2();
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