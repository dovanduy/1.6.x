<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/charts.php');
include_once ('ressources/class.mimedefang.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.mapi-zarafa.inc");
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}

//permissions	
$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();
$modify_user = 1;
if ($_SESSION ["uid"] != $_GET["userid"]) {$modify_user = 0;}
if ($change_aliases <>1) {die();}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$uid=$_GET["userid"];
	$title=$tpl->javascript_parse_text("{store}:$uid");
	echo "YahooWinT(650,'$page?popup=yes&uid=$uid','$title')";

}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$userid=$_GET["uid"];
	$mapi=new mapizarafa();
	$mapi->Connect($userid,$user->password);	
	$hash=$mapi->stores_size();
	
	$total=$hash["Total"];
	unset($hash["Total"]);
	
	$html[]="<table style='width:99%' class=form>";
	
	while (list ($storename, $sizeMB) = each ($hash) ){
		$html[]="<tr>
				<td class=legend style='font-size:16px'>$storename</td>
				<td><strong style='font-size:16px'>$sizeMB</strong>
				</tr>
				";
		
	}
	
	$html[]="<tr>
	<td class=legend style='font-size:16px'>{total}:</td>
	<td><strong style='font-size:16px'>". FormatBytes($total)."</strong>
	</tr>
	";	
	
	$html[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}



?>