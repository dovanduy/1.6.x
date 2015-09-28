<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.syslog.inc');

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["vmware-status"])){vmware_status();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["vmware-install"])){popup_install();exit;}
if(isset($_GET["vmware-install-perfom"])){popup_install_perform();exit;}
if(isset($_GET["vmware-install-logs"])){popup_install_logs();exit;}
popup();


function js_start(){
	$page=CurrentPageName();
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?popup=yes');";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$INSTALL_VMWARE_TOOLS=$tpl->_ENGINE_parse_body("{INSTALL_VMWARE_TOOLS}");
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{INSTALL_VMWARE_TOOLS_TEXT}</div>
	";

	
	$sock=new sockets();
	if(trim($sock->getFrameWork("services.php?vmtools_installed=yes"))<>"TRUE"){
		$html=$html."<center style='font-size:30px;margin-bottom:20px;width:92%;padding:30px' class=form>
			
			
			<div style='margin:40px'>{APP_VMWARE_TOOLS_NOT_INSTALLED}</div>
			<center>". button("{INSTALL_VMWARE_TOOLS}","Loadjs('vmware.install.progress.php')",42)."</center>
		</center>
		
		";
		
		echo $tpl->_ENGINE_parse_body($html);
		return;	
		
	}
	$sock->getFrameWork("services.php?vmtools_stat=yes");
	
		
		
	
			
			
	
	$html=$html."<div style='width:99%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign=top style='width:350px'><div id='vmware-status'></div></td>
		<td valign='top'>		
	
	<table style='width:100%'>
	";
	
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/vmware.array"));
	while (list ($key, $value) = each ($array) ){
		$html=$html."<tr>
				<td class=legend style='font-size:22px'>{{$key}_label}:</td>
				<td style='font-size:22px;font-weight:bold'>$value</td>
				</tr>";
		
	}		
			
			
			
	$html=$html."</table>
	</td></tr>
	</table>
	</div>
	
	<script>
	
	function RefreshVMWareStatus(){
			LoadAjax('vmware-status','$page?vmware-status=yes');
		}
		
	function InstallVMWARECD(){
		Loadjs('vmware.install.progress.php?&CD=1');
		
	}
	
	function InstallVMWARESource(){
		var se=escape(document.getElementById('VMWareSourcePath').value);
		Loadjs('vmware.install.progress.php?&CD=0&path='+se);
		
	}
		
		RefreshVMWareStatus();
	</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function vmware_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	
	$sock=new sockets();
	$datas=$sock->getFrameWork("services.php?vmtools-status=yes");
	$ini->loadString(base64_decode($datas));
	$status=DAEMON_STATUS_ROUND("APP_VMTOOLS",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($status);	

}

function popup_install(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div id='vm-$t' style='width:95%;height:350px;overflow:auto;border:4px solid #CCCCCC;padding:10px'></div>
	
	
	<script>
		Loadjs('vm-$t','$page?vmware-install-perfom=yes&t=$t&CD={$_GET["CD"]}&path={$_GET["path"]}');
	</script>
	
	";
	echo $html;
	
}

function popup_install_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if($_GET["CD"]==1){
		$sock->getFrameWork("services.php?vmwaretoolscd=yes");
		
	}else{
		$sock->getFrameWork("services.php?vmwaretoolspath=".base64_encode($_GET["path"]));
	}
	
	$f=@explode("\n", @file_get_contents("ressources/logs/vmtools.debug"));
	krsort($f);
	while (list ($i, $line) = each ($f)){
		$html=$html."<div style='font-size:11px'>$line</div>";
	}
	
	$html=$html."<script>
		function RereFresh(){
			LoadAjax('vm-$t','$page?vmware-install-logs=yes&t=$t&CD={$_GET["CD"]}&path={$_GET["path"]}');
		
		}
		setTimeout('RereFresh()',5000);
		
	</script>
	";
	echo $html;
	
	
}

function popup_install_logs(){
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	
	if(is_file("ressources/logs/vmtools.debug")){
		$f=@explode("\n", @file_get_contents("ressources/logs/vmtools.debug"));
		krsort($f);
	}else{
		$html=$html."<div style='font-size:11px'>Waiting....</div>";
	}
	while (list ($i, $line) = each ($f)){
		$line=str_replace("Failed", "<strong style='color:#d32d2d'>Failed</strong>", $line);
		$html=$html."<div style='font-size:11px'>$line</div>";
	}
	
	$html=$html."<script>
		function RereFreshSecond(){
			if(YahooWin2Open()){
				LoadAjax('vm-$t','$page?vmware-install-logs=yes&t=$t&CD={$_GET["CD"]}&path={$_GET["path"]}');
				RefreshVMWareStatus();
				}
		}
		setTimeout('RereFreshSecond()',5000);
		
	</script>
	";
	echo $html;	
	
}