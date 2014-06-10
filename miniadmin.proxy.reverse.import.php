<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["import"])){import();exit;}
js();



function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{import}");
	echo "YahooWin2('800','$page?popup=yes','$title')";
	
	
	
}



function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	
	<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{import}</div>
	<div style='width:98%' class=form>
	<p class=text-info style='font-size:18px'>{nginx_import_explain}</p>
	<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:98%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important' 
		id='textToParseCats$t'></textarea>
	<hr>
	<div style='text-align:right'>	
	". button("{submit}", "Save$t()",26).	
	"</div>	
	</div>
	<script>
		var xSave$t=function (obj) {
			var results=obj.responseText;	
			UnlockPage();
			if (results.length>3){alert(results);}
			ExecuteByClassName('SearchFunction');
		}	
	
	
		function Save$t(){
		  	var XHR = new XHRConnection();  
		  	LockPage();
    	 	XHR.appendData('import',document.getElementById('textToParseCats$t').value);
		  	XHR.sendAndLoad('$page', 'POST',xSave$t);
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function import(){
	
	$data=$_POST["import"];
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.import";
	@unlink($filename);
	@file_put_contents($filename, $data);
	if(!is_file($filename)){echo "Fatal, permission denied\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?import=yes");
	echo @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.import.results");
	
}

