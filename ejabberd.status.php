<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.ejabberd.inc');
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["logo"])){logo();exit;}
	if(isset($_GET["status"])){status();exit;}
	page();
	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td width=1% valign='top'><div id='$t-1'></div></td>
		<td width=99%>
			
			<div id='$t-2'></div></td>
	</tr>
	</table>
	<script>
		LoadAjax('$t-1','$page?logo=yes&t=$t');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function logo(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$html="<img src='img/jabberd-128.png'>
	<center ></center>
	<script>
		LoadAjax('$t-2','$page?status=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function status(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$array[]="APP_EJABBERD";
	$array[]="APP_PYMSNT";


	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork('services.php?ejabberd-status=yes'));
	$ini->loadString($datas);
	
	while (list ($num, $ligne) = each ($array) ){
		$tr[]=DAEMON_STATUS_ROUND($ligne,$ini,null,1);
		
	}
	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>
<div style='text-align:right'>". imgtootltip("64-refresh.png","{refresh}","RefreshTab('main_ejabberd_tabs')")."</div>";

$html="<H3 style='font-size:18px;margin-bottom:10px'>{APP_EJABBERD}:{services_status}:</H3><div style='font-size:14px' class=text-info>{APP_EJABBERD_ABOUT}</div>".implode("\n",$tables);	
echo $tpl->_ENGINE_parse_body($html);	
	
}


