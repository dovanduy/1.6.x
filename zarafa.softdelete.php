<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	

	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ZarafaSoftDelete"])){ZarafaSoftDeleteSave();exit;}
	if(isset($_POST["ZarafaSoftPerformNow"])){ZarafaSoftPerformNow();exit;}
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->javascript_parse_text("{softdelete_option}");
	$html="YahooWin5('650','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();	
	$sock=new sockets();
	$ZarafaSoftDelete=$sock->getFrameWork("ZarafaSoftDelete");
	if(!is_numeric($ZarafaSoftDelete)){$ZarafaSoftDelete=30;}
	$tpl=new templates();
	$time=time();
	$html="
	<center id='$time'></center>
	<div class=text-info style='font-size:11px'>{soft_delete_explain}<br>{softdelete_lifetime_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{softdelete_lifetime}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaSoftDelete",$ZarafaSoftDelete,"font-size:16px;width:90px")."&nbsp;{days}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","ZarafaSoftDeleteSave()",16)."</td>
	</tr>
	</table>
	<hr>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{clean_now_for}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaSoftPerform",$ZarafaSoftDelete,"font-size:16px;width:90px")."&nbsp;{days}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{clean_now}","ZarafaSoftPerformNow()",16)."</td>
	</tr>
	</table>	
	<script>
	var x_ZarafaSoftDeleteSave=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>5){alert(tempvalue);}
      document.getElementById('$time').innerHTML='';
      }	
		
	function ZarafaSoftDeleteSave(){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaSoftDelete',document.getElementById('ZarafaSoftDelete').value);
		AnimateDiv('$time');
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSoftDeleteSave);
		}
		
	function ZarafaSoftPerformNow(){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaSoftPerformNow',document.getElementById('ZarafaSoftPerform').value);
		AnimateDiv('$time');
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSoftDeleteSave);
		}		
		
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function ZarafaSoftDeleteSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaSoftDelete", $_POST["ZarafaSoftDelete"]);
	$sock->getFrameWork("cmd.php?zarafa-restart-server=yes");
}
function ZarafaSoftPerformNow(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?softdelete={$_POST["ZarafaSoftPerform"]}");	
}