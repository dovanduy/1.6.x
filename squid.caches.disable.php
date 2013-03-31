<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsSquidAdministrator){die("NO PRIVS");}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["DisableAnyCache"])){DisableAnyCache();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$uuid=$_GET["uuid"];
	$title=$tpl->javascript_parse_text("{caches_on_disk}");
	echo "YahooWin3('650','$page?popup=yes','$title')";
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}	
	$level=Paragraphe_switch_img('{DisableAnyCache}',"{DisableAnyCache_explain2}","DisableAnyCache-$t",$DisableAnyCache,null,440);
	
	$html="
	<div id='animate-$t'></div>		
	<div style='margin:10px;padding:10px' class=form>
			$level
			<div style='text-align:right'><hr>". button("{apply}","Save$t()",18)."</div>
		</div>
					
<script>
	var xsave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin3Hide();
			RefreshTab('squid_main_svc');
			Loadjs('squid.compile.progress.php?ask=yes');
		}	
		
		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('DisableAnyCache',document.getElementById('DisableAnyCache-$t').value);
			XHR.sendAndLoad('$page', 'POST',xsave$t);
		}
			
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function DisableAnyCache(){
	$sock=new sockets();
	$sock->SET_INFO('DisableAnyCache',$_POST["DisableAnyCache"]);
}

