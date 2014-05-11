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
	
	$sock=new sockets();
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){die();}
	$KasperskyPromo022014=$sock->GET_INFO("KasperskyPromo022014");
	if(!is_numeric($KasperskyPromo022014)){$KasperskyPromo022014=0;}
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
	$button=button("Je suis intéressé(e), j'installe la solution","InstallPub$t()",22);
	$users=new usersMenus();
	if($users->KAV4PROXY_INSTALLED){
		$button=button("Je suis intéressé(e), je demande une quotation","ClosePub$t();Loadjs('Kav4Proxy.license-manager.php');",22);
	}
	
	$html="
	<div id='div-$t'>
	<center style='font-size:38px'>KASPERSKY FOR ARTICA</center>
	
	<center style='margin:30px'><img src='img/kaspersky-logo-250.png'></center>		
	<center style='font-size:22px;'>PROTEGEZ LE SURF DE TOUS VOS UTILISATEURS AVEC KASPERSKY</center>
	<center style='font-size:26px;font-weight:bold;margin:14px'>POUR 700 EUROS !*</center>
	<center style='font-size:18px'>Intégrez Kaspersky dans votre Artica Proxy maintenant !</center>
			
	<center style='margin:30px'>$button</center>
	<center style='margin:30px'>". button("Non merci","ClosePub$t()",22)."</center>
	
			
			
	<div style='font-size:14px' class=explain><strong>*Offre soumise à conditions :</strong><br>
	Prix pour un an par serveur Artica proxy protégé.<br>
	Prix de la licence Kaspersky valable uniquement via l'opération &laquo;Kaspersky for Artica&raquo;.<br>
	A activer au travers de l'interface Artica Proxy. Durée de la licence Kaspersky d'une année à compter de la date d’achat.
	</div>
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
		document.getElementById('div-$t').innerHTML='';
		YahooSetupControlHide();
		Loadjs('Kav4Proxy.install.php');
	}	
	
	function InstallPub$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		document.getElementById('div-$t').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'POST',xInstallPub$t);	
	
	}
	
	
	$(\".ui-dialog-titlebar-close\").hide();
	</script>
	";

	echo $html;
}

function KasperskyPromo022014_us_text(){
	$t=time();
	$page=CurrentPageName();
	
	$button=button("I am interested, install the solution","InstallPub$t()",22);
	$users=new usersMenus();
	if($users->KAV4PROXY_INSTALLED){
		$button=button("I am interested, i request a quote","ClosePub$t();Loadjs('Kav4Proxy.license-manager.php');",22);
	}
	
	$html="
	<div id='div-$t'>
	<center style='font-size:38px'>KASPERSKY FOR ARTICA</center>
	<center style='margin:30px'><img src='img/kaspersky-logo-250.png'></center>

	<center style='font-size:22px;'>PROTECT WEB SURFING USING KASPERSKY</center>
	<center style='font-size:26px;font-weight:bold;margin:14px'>FOR 700 EUROS!*</center>
	<center style='font-size:18px'>Integrate Kaspersky into Artica Proxy now! </center>
		
	<center style='margin:30px'>$button</center>
	<center style='margin:30px'>". button("No, thank you","ClosePub$t()",22)."</center>

<div style='font-size:14px' class=explain><strong>*Offer subject to conditions :</strong><br>
700€ (Price for 1 year and for 1 protected Artica proxy server).<br>
Kaspersky license price only valid for ‘Kaspersky for Artica’ operation.<br>
To be activated through Artica proxy Web Interface.<br>
Duration of the Kaspersky license : 1 year from date of purchase.
</div>
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
		document.getElementById('div-$t').innerHTML='';
		YahooSetupControlHide();
		Loadjs('Kav4Proxy.install.php');
	}
	
	function InstallPub$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ClosePub',1);
		document.getElementById('div-$t').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'POST',xInstallPub$t);
	}
	
	$(\".ui-dialog-titlebar-close\").hide();
</script>
";
echo $html;
}

function ClosePub(){
	$sock=new sockets();
	$sock->SET_INFO("KasperskyPromo022014",1);
	
}
