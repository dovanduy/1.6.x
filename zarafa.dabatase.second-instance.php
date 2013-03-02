<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ZarafaDBEnable2Instance"])){ZarafaDBEnable2Instance();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{zarafa_second_instance}");
	$html="YahooWin3('650','$page?popup=yes','$title')";
	echo $html;
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ZarafaDBEnable2Instance=$sock->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($ZarafaDBEnable2Instance)){$ZarafaDBEnable2Instance=0;}
	$t=time();
	$p=Paragraphe_switch_img("{zarafa_second_instance}", "{zarafa_second_instance_explain}","ZarafaDBEnable2Instance",null,550);
	
	$html="
	<div id='start-$t'></div>		
	<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>
</table>
<script>
		var x_Save$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('start-$t').innerHTML='';
	      
	      }		
		
		function Save$t(){
			var value=document.getElementById('ZarafaDBEnable2Instance').value;
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaDBEnable2Instance',value);
			AnimateDiv('start-$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);	
		}
</script>
				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function ZarafaDBEnable2Instance(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaDBEnable2Instance", $_POST["ZarafaDBEnable2Instance"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
}
