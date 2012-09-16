<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableSambaHomeService"])){EnableSambaHomeServiceSave();exit;}
js();	
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{HomeUsers}");
	echo "YahooWin4('450','$page?popup=yes','$title')";
}
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableSambaHomeService=$sock->GET_INFO("EnableSambaHomeService");
	if(!is_numeric($EnableSambaHomeService)){$EnableSambaHomeService=1;}
	
	$p=Paragraphe_switch_img("{HomeUsers}", "{HomeUsersFormExplain}","EnableSambaHomeService",$EnableSambaHomeService,null,350);
	$t=time();
	$html="
	<center id=$t>
	<div style='width:90%' class=form>
	$p
	<div style='text-align:right;width:100%'><hr>". button("{apply}","EnableSambaHomeServiceSave()",16)."</div>
	</div>
	</center>
	<script>
	var x_EnableSambaHomeServiceSave=function (obj) {
		tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		YahooWin4Hide();
	   }
	
	
	function EnableSambaHomeServiceSave(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableSambaHomeService',document.getElementById('EnableSambaHomeService').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_EnableSambaHomeServiceSave);		
	}	
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function EnableSambaHomeServiceSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSambaHomeService", $_POST["EnableSambaHomeService"]);
	$sock->getFrameWork("cmd.php?samba-save-config=yes");	
	
}