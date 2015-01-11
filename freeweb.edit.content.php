<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["FREEWEB_CONTENT"])){FREEWEB_CONTENT_SAVE();exit;}

page();	


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$free=new freeweb($_GET["servername"]);
	$t=time();
	echo $tpl->_ENGINE_parse_body("<div style='width:98%' class=form>
		<div class=text-info style='font-size:16px'>{freeweb_content_plus_explain}</div>
		<center><textarea 
		style='width:99%;height:350px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
		font-weight:bold;padding:3px;font-family:Courier New;'
		id='FREEWEB_CONTENT-$t'>$free->content_plus</textarea>
		<hr>". button("{apply}","Save$t()",26)."	
			
		</center>
		</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(results.length>3){alert(results);return;}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('FREEWEB_CONTENT', encodeURIComponent(document.getElementById('FREEWEB_CONTENT-$t').value));
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	
</script>		
");
	
	
	
	
}

function FREEWEB_CONTENT_SAVE(){
	$FREEWEB_CONTENT=url_decode_special_tool($_POST["FREEWEB_CONTENT"]);
	$free=new freeweb($_POST["servername"]);
	$free->SaveContentPlus($FREEWEB_CONTENT);
}
