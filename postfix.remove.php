<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["RemovePostfix"])){save();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{remove_postfix_section}");
	$html="YahooWin5('501','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	
	$sock=new sockets();
	$lock=0;
	$locked=base64_decode($sock->getFrameWork("postfix.php?islocked=yes"));
	if($locked=="TRUE"){$lock=1;}

	$p=Paragraphe_switch_img("{remove_postfix_section}", "{remove_postfix_section_explain}","RemovePostfix-$t",$lock,
	null,450);
	
	
	$html="
	<div id='$t'></div>
	
	
	<table style='width:99%' class=form>
	<tr>
		<td>$p</p>
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>
	</table>
	<script>
	
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t').innerHTML='';
		CacheOff();
		YahooWin5Hide();
		}		
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('RemovePostfix',document.getElementById('RemovePostfix-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_Save$t);
	}
	
	
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}





function save(){
	
	$sock=new sockets();
	if($_POST["RemovePostfix"]==1){
		$sock->getFrameWork("postfix.php?RemovePostfixInterface=yes");
	}else{
		$sock->getFrameWork("postfix.php?EnablePostfixInterface=yes");
	}
	$tpl=new templates();
	sleep(3);
	echo $tpl->javascript_parse_text("{success}");
}


