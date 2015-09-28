<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
	if(isset($_GET["KasperskyPromo-022014-show"])){KasperskyPromo022014();exit;}
	if(isset($_GET["KasperskyPromo022014"])){KasperskyPromo022014_text();exit;}
	if(isset($_POST["ClosePub"])){ClosePub();exit;}
js();


function js(){
	
	
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){die();}
	if($users->WEBSECURIZE){die();}
	if($users->LANWANSAT){die();}
	$sock=new sockets();
	$AsMetaServer=intval($sock->GET_INFO("AsMetaServer"));
	if($AsMetaServer==1){die();}
	$KasperskyPromo022014=$sock->GET_INFO("KasperskyPromo022014");
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	if(!is_numeric($KasperskyPromo022014)){$KasperskyPromo022014=0;}
	if($AsCategoriesAppliance){$KasperskyPromo022014=1;}
	if($KasperskyPromo022014==0){KasperskyPromo022014();return;}
	

}

function KasperskyPromo022014(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("Artica For Kaspersky");
	$html="YahooSetupControlModalFixedNoclose(910,'$page?KasperskyPromo022014=yes','KASPERSKY FOR ARTICA')";
	echo $html;	
	
}

function KasperskyPromo022014_text(){
	$users=new usersMenus();
	if($users->language=="fr"){KasperskyPromo022014_fr_text();return;}
	KasperskyPromo022014_us_text();
}

function KasperskyPromo022014_fr_text(){
	$t=time();
	$page=CurrentPageName();
	$button=button("Je suis intéressé(e), j'installe la solution","InstallPub$t()",40);
	$users=new usersMenus();
	
	$html="
	<div id='div-$t' style='margin:50px'>
	<center style='font-size:50px'>KASPERSKY FOR ARTICA</center>
	
	<center style='margin:30px'><img src='img/kaspersky-logo-250.png'></center>		
	<center style='font-size:22px;'>PROTEGEZ LE SURF DE TOUS VOS UTILISATEURS AVEC KASPERSKY</center>
	
	<center style='font-size:18px'>Intégrez Kaspersky dans votre Artica Proxy maintenant !</center>
			
	<center style='margin:30px'>$button</center>
	</div>		
	<script>
	var xClosePub$t= function (obj) {
		var results=obj.responseText;
	}
	
	
	function ClosePub$t(){
		YahooSetupControlHide();
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		XHR.sendAndLoad('$page', 'POST',xClosePub$t);
	}
	
	var xInstallPub$t = function (obj) {
		LoadMainDashProxy();
		Loadjs('Kav4Proxy.install.php');
	}	
	
	function InstallPub$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		XHR.sendAndLoad('$page', 'POST',xInstallPub$t);	
	
	}
</script>
	";

	echo $html;
}

function KasperskyPromo022014_us_text(){
	$t=time();
	$page=CurrentPageName();
	
	$button=button("I am interested, install the solution","InstallPub$t()",40);
	$users=new usersMenus();
	
	
	$html="
	<div id='div-$t' style='margin:50px'>
	<center style='font-size:50px'>KASPERSKY FOR ARTICA</center>
	<center style='margin:30px'><img src='img/kaspersky-logo-250.png'></center>

	<center style='font-size:22px;'>PROTECT WEB SURFING USING KASPERSKY</center>
	<center style='font-size:18px'>Integrate Kaspersky into Artica Proxy now! </center>
	<center style='margin:30px'>$button</center>
	

</div>
<script>
	var xClosePub$t= function (obj) {
		var results=obj.responseText;
	}
	
	
	function ClosePub$t(){
		YahooSetupControlHide();
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		XHR.sendAndLoad('$page', 'POST',xClosePub$t);
	}
	
	var xInstallPub$t = function (obj) {
		LoadMainDashProxy();
		Loadjs('Kav4Proxy.install.php');
	}
	
	function InstallPub$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		XHR.sendAndLoad('$page', 'POST',xInstallPub$t);
	}
</script>
";
echo $html;
}

function ClosePub(){
	$sock=new sockets();
	$sock->SET_INFO("KasperskyPromo022014",1);
	
}
