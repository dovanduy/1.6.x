<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["step3"])){step3();exit;}

	js();
	
	
function js() {
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{build_support_package}");
	$html="RTMMail('550','$page?popup=yes','$title');";
	echo $html;
	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<center id='$t-start'><img src=img/load.gif></center>
	<table style='width:99%' class=form>
	<tr>
		<tr>
			<td><div id='step1$t' style='font-size:16px;font-weight:bold'>
					<table style='width:100%'>
					<tr>
						<td width=1%><img src='img/20-check-grey.png'></td>
						<td  style='font-size:16px'>{get_system_informations}</td>
					</tr>
					</table>
			</td>
		</tr>
		<tr>
			<td><div id='step2$t' style='font-size:16px;font-weight:bold'>
					<table style='width:100%'>
					<tr>
						<td width=1%><img src='img/20-check-grey.png'></td>
						<td  style='font-size:16px'>{get_all_logs}</td>
					</tr>
					</table>			
			</div></td>
		</tr>
		<tr>
			<td><div id='step3$t' style='font-size:16px;font-weight:bold'>
					<table style='width:100%'>
					<tr>
						<td width=1%><img src='img/20-check-grey.png'></td>
						<td  style='font-size:16px'>{compressing_package}</td>
					</tr>
					</table>			
			
			</div></td>
		</tr>					
	</table>
	<script>
	setTimeout('step1$t()',3000);
		function step1$t(){
			document.getElementById('$t-start').innerHTML='';
			LoadAjaxTiny('step1$t','$page?step1=yes&t=$t');
			}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html); 
}

function step1(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$sock->getFrameWork("squid.php?support-step1=yes");
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1%><img src='img/20-check.png'></td>
		<td  style='font-size:16px'>{get_system_informations}</td>
	</tr>
	</table>
	<script>

			LoadAjaxTiny('step2$t','$page?step2=yes&t=$t');
			
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function step2(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$sock->getFrameWork("squid.php?support-step2=yes&MyCURLTIMEOUT=1200");
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1%><img src='img/20-check.png'></td>
		<td  style='font-size:16px'>{get_all_logs}</td>
	</tr>
	</table>
	<script>
		
			LoadAjaxTiny('step3$t','$page?step3=yes&t=$t&MyCURLTIMEOUT=1200');
			
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function step3(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$size=$sock->getFrameWork("squid.php?support-step3=yes");
	$size=FormatBytes($size/1024);
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1%><img src='img/20-check.png'></td>
		<td  style='font-size:16px'><a href='ressources/support/support.tar.gz' style='font-size:16px;text-decoration:underline'>support.tar.gz ($size)</a></td>
	</tr>
	</table>
	<script>
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}