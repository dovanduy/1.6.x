<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["LogsWarninStop"])){LogsWarninStop();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{squid_logs_urgency_section}");
	echo "YahooWin5('800','$page?popup=yes','$title')";
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
	$varlog=urlencode("/var/log");
	$t=time();
	
	
	if($LogsWarninStop==1){
		$html="<div style='width:97%' class=form>
			<center style='margin:20px'>
			". 	button("{browse} /var/log","Loadjs('tree.php?mount-point=$varlog&emergency=yes')",28)."
			</center>
			<center style='margin:20px'>
			". 	button("{disable_logs_emergency}","Save2$t()",28)."
			</center>
		<center style='margin:20px'>
		". 	button("{clean_log_directory}","Save2$t()",28)."
		</center>";		
		
		
		
	}else{
	
	
	$p=Paragraphe_switch_img("{enable_logs_urgency}", "{enable_logs_urgency_explain}","LogsWarninStop",$LogsWarninStop,null,750);
	
	$html="
	<div style='width:97%' class=form>
			<center style='margin:20px'>
			". 	button("{browse} /var/log","Loadjs('tree.php?mount-point=$varlog&emergency=yes')",28)."</center>
		$p
	<div style='text-align:right;margin-top:20px'><hr>". button("{apply}","Save$t()",28)."</div>
	</div>	
	";
	}
$html=$html."
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}	
	Loadjs('squid.compile.progress.php');
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LogsWarninStop',document.getElementById('LogsWarninStop').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LogsWarninStop','0');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
function CleanLogs$t(){
	Loadjs('squid.cleanlogs.progress.php');
}


</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function LogsWarninStop(){
	$sock=new sockets();
	$sock->SET_INFO("LogsWarninStop", $_POST["LogsWarninStop"]);
	
}


