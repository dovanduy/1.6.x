<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["apply-js"])){apply_js();exit;}
if(isset($_GET["apply-popup"])){apply_popup();exit;}
if(isset($_GET["apply-next"])){apply_next();exit;}
if(isset($_POST["shorewall-progress"])){apply_progress();exit;}
if(isset($_POST["shorewall-restart"])){apply_restart();exit;}
tabs();	


function apply_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$title=$tpl->_ENGINE_parse_body("{compiling}");
	$ask=$tpl->javascript_parse_text("{shorewall_ask_compile}");
	$start="Yahoo$t()";
	if($_GET["ask"]=="yes"){
		$start="Ask$t();";
	}
	echo "
	function Ask$t(){
		if(!confirm('$ask')){return;}
		Yahoo$t();
	}
	
	function Yahoo$t(){
		YahooWinBrowse('700','$page?apply-popup=yes&t=$t&ask={$_GET["ask"]}','$title',true);
	}
	
	$start";
	
	
}

function apply_popup(){
	$sock=new sockets();
	$sock->getFrameWork("shorewall.php?check=yes");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{PLEASE_WAIT_COMPILING_RULES}");
	$error_title=$tpl->_ENGINE_parse_body("{compilation_failed}");
	$success_title=$tpl->_ENGINE_parse_body("{compilation_done}");
	$success_restart=$tpl->_ENGINE_parse_body("{success_restarting_service}");
	$restarting_service=$tpl->_ENGINE_parse_body("{restart_firewall_service}");
	$t=time();
	$html="
	<center style='margin:15px;font-size:22px' id='title-$t'>$title</center>
	<div id='progress-status-$t'></div>	
	<div id='progress-next-$t'></div>
	<div id='progress-text-$t' style='margin-top:10px'></div>
	
<script>
var xcount$t=0;
var step2$t=0;
var xSendProgress$t = function (obj) {
	var res=obj.responseText;
	xcount$t=xcount$t+1;
	if (res.length>3){
		document.getElementById('progress-text-$t').innerHTML=res;
	}
	
	if(xcount$t<2){
		$('#progress-status-$t').progressbar({ value: 50 });
	}
	
	if(xcount$t>2){
		$('#progress-status-$t').progressbar({ value: 60 });
	}
	
	if(xcount$t>4){
		$('#progress-status-$t').progressbar({ value: 70 });
	}	
	
	if(!YahooWinBrowseOpen()){return;}
	
	if( document.getElementById('error-$t') ){
		alert('$error_title');
		document.getElementById('title-$t').innerHTML='$error_title';
		$('#progress-status-$t').progressbar({ value: 100 });
		return;
	}
	if( document.getElementById('done-$t') ){
	
		if(step2$t==0){
			document.getElementById('title-$t').innerHTML='$success_title';
		}
		if(step2$t==1){
			document.getElementById('title-$t').innerHTML='$success_restart';
		}		
		$('#progress-status-$t').progressbar({ value: 100 });
		if(step2$t==0){
			LoadAjax('progress-next-$t','$page?apply-next=yes&t=$t');
		}
		return;
	}
	
	setTimeout(	'SendProgress$t()',2000);
}

var xSendProgress2$t = function (obj) {
	step2$t=1;
	document.getElementById('title-$t').innerHTML='$restarting_service';
	document.getElementById('progress-next-$t').innerHTML='';
	document.getElementById('progress-text-$t').innerHTML='';
	$('#progress-status-$t').progressbar({ value: 1 });
	SendProgress$t();
}

function SendProgress$t(){
	var XHR = new XHRConnection();
	XHR.appendData('shorewall-progress',  '$t');
	
	if( document.getElementById('md5-$t') ){
		var md5=document.getElementById('md5-$t').value
		XHR.appendData('md5',  md5);
	}
	XHR.setLockOff();
	XHR.sendAndLoad('$page', 'POST',xSendProgress$t);
		
	}
	$('#progress-status-$t').progressbar({ value: 2 });
	SendProgress$t()
</script>		
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function apply_progress(){
	$t=$_POST["shorewall-progress"];
	$pfile="/usr/share/artica-postfix/ressources/logs/shorewall-output";
	$mf5=$_POST["md5"];
	if(!is_file($pfile)){return;}
	$md5=md5_file($pfile);
	if($md5<>null){
		if($md5==$mf5){return;}
	}
	
	$f=explode("\n",@file_get_contents($pfile));
	
	$tr[]="
		<input type='hidden' id='md5-$t' value='$md5'>
		<table style='width:100%'>";
	
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color=null;
		$icon="20-check.png";
		
		if(preg_match("#FATAL#", $ligne)){
			$color=";color:#BE0303";
			$icon="20-check-red.png";
			echo "<input type='hidden' id='error-$t' value='yes'>";
		}
		
		if(preg_match("#(BUILD|ACTION)\s+DONE#", $ligne)){
			echo "<input type='hidden' id='done-$t' value='yes'>";
		}
		
		
		$tr[]="<tr><td width=1%><img src='img/$icon'></td>
		<td><span style='font-size:13px{$color}'>$ligne</td></tr>";
		
	}
	
	$tr[]="</table>";
	
	
	echo @implode("", $tr);
	
	
}

function apply_next(){
	$tpl=new templates();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$html="
	<center style='margin:15px'>". button("{restart_firewall_service}","Restart$t()",18)."</center>
	<script>

		function Restart$t(){
			var XHR = new XHRConnection();
			XHR.appendData('shorewall-restart',  '$t');
			XHR.sendAndLoad('$page', 'POST',xSendProgress2$t);
		}
			
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function apply_restart(){
	$sock=new sockets();
	$sock->getFrameWork("shorewall.php?restart=yes");
	
}
	
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$array["interfaces"]='{network_interfaces}';
	$array["zones"]='{netzones}';
	$array["policies"]='{policies}';
	$array["rules"]='{rules}';
	$array["localservices"]='{local_services}';
	$array["route"]='{routing_rules}';
	
	
	
	
	$fontsize=14;
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="zones"){
			$tab[]="<li><a href=\"shorewall.zones.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="interfaces"){
			$tab[]="<li><a href=\"shorewall.interfaces.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	
		
		if($num=="policies"){
			$tab[]="<li><a href=\"shorewall.policies.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		

		if($num=="rules"){
			$tab[]="<li><a href=\"shorewall.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="localservices"){
			$tab[]="<li><a href=\"shorewall.localservices.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="route"){
			$tab[]="<li><a href=\"shorewall.routes.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
	}
	
	echo build_artica_tabs($tab, "main_shorewall",915);
}	
?>	