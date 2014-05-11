<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsMailBoxAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(function_exists($_GET["function"])){call_user_func($_GET["function"]);exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["start"])){start();exit;}

js();
function js(){
	$page=CurrentPageName();
$html="	
<script>
function PostfixQuickLinks(){
	var z = $('#middle').css('display');
	if(z!=='none'){
		$('#middle').slideUp('normal');
		$('#middle').html('');
		$('#quick-links').html('');
		$('#middle').slideDown({
			duration:900,
			easing:'easeOutExpo',
			complete:function(){
				CyrusQuickLinksMainLoad();
				}
			});
		}
	
}
function CyrusQuickLinksMainLoad(){
	LoadAjax('middle','$page?start=yes');
}	
CyrusQuickLinksMainLoad();
</script>
";
echo $html;
	
}

function start(){
	
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}

  if($users->cyrus_imapd_installed){
		if($users->AsMailBoxAdministrator){
			$cyrus=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("bg-cyrus-48.png", "APP_CYRUS",null, "QuickLinkSystems('section_cyrus')"));
		}
	}
	
if($users->roundcube_installed){
	$roundcube=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("roundcube-48.png", "webmail",null, "QuickLinkSystems('section_roundcube')"));
	
}

$fetchmail=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("fetchmail-rule-48.png", "APP_FETCHMAIL_TINY",null, "QuickLinkSystems('section_fetchmail')"));



$postfix=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("mass-mailing-postfix-48.png", "APP_POSTFIX",null, "QuickLinkPostfix()"));


$tr[]=$cyrus;
$tr[]=$fetchmail;
$tr[]=$roundcube;
$tr[]=$postfix;
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-site-48.png", "main_interface","main_interface_back_interface_text", "QuickLinksHide()"));

$count=1;
while (list ($key, $line) = each ($tr) ){
	if($line==null){continue;}
	$f[]="<li id='kwick1'>$line</li>";
	$count++;
	
}

while (list ($key, $line) = each ($GLOBALS["QUICKLINKS-ITEMS"]) ){
	
	$jsitems[]="\tif(document.getElementById('$line')){document.getElementById('$line').className='QuickLinkTable';}";
}


	$html="<div id='BodyContent' style='width:100%'></div>
	
	
	<script>
		
		function QuickLinkCyrusInternal(){
			Loadjs('quicklinks.postfix.multiple.php?js=yes');
		
		}		
		
		function QuickLinkPostfix(){
			Loadjs('quicklinks.postfix.php?js=yes');		
		}		
		
	
		function QuickLinkSystems(sfunction){
			Set_Cookie('QuickLinkCacheCyrus', '$page?function='+sfunction, '3600', '/', '', '');
			LoadAjax('BodyContent','$page?function='+sfunction);
		}
		
		function QuickLinkMemory(){
			var memorized=Get_Cookie('QuickLinkCacheCyrus');
			if(memorized=='section_instances'){QuickLinkSystems('section_cyrus');return;}
			if(!memorized){QuickLinkSystems('section_cyrus');return;}
			if(memorized.length>0){LoadAjax('BodyContent',memorized);}else{QuickLinkSystems('section_cyrus');}
		
		}
		
		function QuickLinkShow(id){
			".@implode("\n", $jsitems)."
			if(document.getElementById(id)){document.getElementById(id).className='QuickLinkOverTable';}
			}		
		
		LoadQuickTaskBar();
		QuickLinkMemory();
	</script>
	";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}

function section_cyrus(){echo "<script>AnimateDiv('BodyContent');javascript:Loadjs('cyrus.index.php?in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-APP_CYRUS');</script>";}
function section_postfix(){echo "<script>AnimateDiv('BodyContent');Loadjs('postfix.index.php?font-size=14');</script>";}
function section_security(){echo "<script>AnimateDiv('BodyContent');Loadjs('postfix.security.php?font-size=14');QuickLinkShow('quicklinks-APP_CYRUS');";}
function section_fetchmail(){echo "<script>AnimateDiv('BodyContent');Loadjs('fetchmail.index.php?ajax=yes&in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-APP_FETCHMAIL_TINY');";}
function section_roundcube(){echo "<script>AnimateDiv('BodyContent');Loadjs('roundcube.index.php?script=yes&in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-webmail');";}



