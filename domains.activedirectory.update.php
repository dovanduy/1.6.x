<?php
	$GLOBALS["ICON_FAMILY"]="organizations";
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	
	
	$users=new usersMenus();
	if($users->AsArticaAdministrator==true or $users->AsPostfixAdministrator or $user->AsSquidAdministrator){}else{
		$tpl=new templates();
		echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");
		die();
	}
	if(isset($_GET["update"])){update();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["content"])){content();exit;}
	
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="Active Directory:{update2}";
	$html="YahooWinBrowse('800','$page?popup=yes&flexigrid={$_GET["flexigrid"]}','$title');";
	echo $html;
	}
	
function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock->getFrameWork("services.php?activedirectory-update=yes");
	$t=time();
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}...");
	$html="
	<div id='wait-$t'></div>
	<div id='content-$t'>
		<center style='font-size:22px'>$please_wait</center>
	</div>		
	<textarea 
			style='margin-top:5px;font-family:Courier New;
			font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:12px' 
			id='textarea-$t'></textarea>
		<script>
			LoadAjax('wait-$t','$page?update=yes&t=$t&flexigrid={$_GET["flexigrid"]}');
		</script>
		
	";
	
echo $html;	
	
}

function update(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$tt=time();
	$t=$_GET["t"];
	$fsize=$_GET["fsize"];
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}...");
	$flexigrid=$_GET["flexigrid"];
	$file="/usr/share/artica-postfix/ressources/logs/web/activedirectory-update.txt";
	$reloaderr=0;
	$readcontent=0;
	if(!is_file($file)){
		$reloaderr=1;
		$minierr="NO file...";
		
	}else{
		$filesize=@filesize($file);
		if($filesize>0){
			$minierr="$filesize<>$fsize";
			if($filesize<>$fsize){
				$readcontent=1;
				$fsize=$filesize;
			}
		}else{
			$minierr="activedirectory-update.txt = 0 bytes";
		}
		
	}
	
	$html="
	<script>
	function Wait$tt(){
		if(!YahooWinBrowseOpen()){return;}
		document.getElementById('content-$t').innerHTML=\"<center style='font-size:22px'>$please_wait</center>\";
		LoadAjax('wait-$t','$page?update=yes&t=$t&flexigrid={$_GET["flexigrid"]}&fsize=$fsize');
	}
	
	var x_ReadContent$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
			document.getElementById('textarea-$t').value=results;
		}
		$('#$flexigrid').flexReload();
		setTimeout(\"restart$tt()\",3000);
	}
	
	function ReadContent$tt(){
		if(!YahooWinBrowseOpen()){return;}
		var XHR = new XHRConnection();
		XHR.appendData('content','yes');
		XHR.sendAndLoad('$page', 'POST',x_ReadContent$t);			
	}	
	
	function restart$tt(){
		if(!YahooWinBrowseOpen()){return;}
		LoadAjax('wait-$t','$page?update=yes&t=$t&flexigrid={$_GET["flexigrid"]}&fsize=$fsize');
	}
	
	function Upd$tt(){
		if(!YahooWinBrowseOpen()){return;}
		var reloaderr=$reloaderr;
		var err='$minierr';
		var readcontent=$readcontent;
		if(reloaderr==1){
			setTimeout(\"Wait$tt()\",3000);
			return;
		}
		
		if(readcontent==1){
			setTimeout(\"ReadContent$tt()\",1000);
			return;
		}
		
		setTimeout(\"restart$tt()\",5000);
		
	}
	Upd$tt();		
	</script>";
	echo $html;
	
}

function content(){
	$file="ressources/logs/web/activedirectory-update.txt";
	$datas=explode("\n",@file_get_contents($file));
	krsort($datas);
	echo @implode("\n", $datas);
}

