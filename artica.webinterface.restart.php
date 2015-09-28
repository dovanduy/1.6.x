<?php

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["VERBOSE"]=true;
}

include_once('ressources/class.templates.inc');




if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{restart_webconsole}");
	if(isset($_GET["nologon"])){$nologon="&nologon=yes";}
	echo "
function Start$t(){
	RTMMail('800','$page?popup=yes$nologon','$title');
}
Start$t();";


}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	
	
	$logon="document.location.href='/logoff.php';";
	
	if(isset($_GET["nologon"])){
		$logon="RTMMailHide();";
	}
	
	$html="<div style='font-size:30px;margin-bottom:20px'>{restart_webconsole}</div>
	<div style='width:98%' class=form>
	<input type='hidden' id='Counter$t' value='10'>		
	<center style='margin:50px;font-size:90px' id='title$t'><center>
	</div>	
	
<script>
setInterval(function () {
	var countdown = document.getElementById('Counter$t').value
	countdown=countdown-1;
	if(countdown==0){
		document.getElementById('Counter$t').value=0;
		document.getElementById('title$t').innerHTML='';
		$logon
		return;
	}
	
	if(countdown<1){ return;}
	
	document.getElementById('Counter$t').value=countdown;
	document.getElementById('title$t').innerHTML=countdown
 
}, 1000);
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);
$sock=new sockets();
$sock->getFrameWork('services.php?restart-lighttpd=yes');
}