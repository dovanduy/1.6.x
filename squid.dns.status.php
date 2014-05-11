<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

	if($user->SQUID_INSTALLED==false){
		if(!$user->WEBSTATS_APPLIANCE){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["purge-js"])){purge_js();exit;}
	if(isset($_GET["dns-servers"])){dns_servers();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_POST["purge-popup"])){purge();exit;}

	
page();

function purge_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$explain=$tpl->javascript_parse_text("{squid_purge_dns_explain}");
	$dev=$_GET["unlink-disk-js"];
	$t=time();
	$thml="
	var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	UnlockPage();
	RefreshTab('squid_dns_tab');
	}
	
	
	function Save$t(){
	if(!confirm('$explain')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('purge-popup','yes');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	
	
	Save$t();";
	echo $thml;	
	
}

function purge(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?purge-dns=yes");	
	
}

function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["dns-servers"]='{dns_servers}';
	$array["items"]='{items}';
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num&=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "squid_dns_tab");
	
	
}	
function dns_servers(){	
$sock=new sockets();
$data=base64_decode($sock->getFrameWork("squid.php?idns=yes"));
echo "<textarea style='width:100%;height:650px;overflow:auto;border:0px solid #CCCCCC;
	font-size:14px;font-weight:bold;padding:3px' id='SQUID_CONTENT-$t'>$data</textarea>";
}

function items(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$data=base64_decode($sock->getFrameWork("squid.php?ipcache=yes"));
	echo "
	<center style='margin:20px'>". $tpl->_ENGINE_parse_body(button("{purge}", "Loadjs('$page?purge-js=yes',true)",16))."</center>		
	<textarea style='width:100%;height:650px;overflow:auto;border:0px solid #CCCCCC;
	font-size:14px;font-weight:bold;padding:3px' id='SQUID_CONTENT-$t'>$data</textarea>";
}