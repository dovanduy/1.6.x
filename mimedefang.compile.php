<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.mimedefang.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){header('location:users.index.php');exit();}
	
	
	if(isset($_GET["compile-rules-js"])){compile_rules_js();exit;}
	if(isset($_GET["compile-rules-perform"])){	compile_rules_perform();exit;}

	
function compile_rules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$mailman=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{compile_rules}");
	$html="YahooWinBrowse('750','$page?compile-rules-perform=yes','$mailman');";
	echo $html;		
	
}

function compile_rules_perform(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("mimedefang.php?reload-tenir=yes"));
	echo "
	<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$datas</textarea>
<script>
	RefreshTab('main_config_mimedefang');
</script>
		
	";
	
}