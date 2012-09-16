<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["TESTS-FROM"])){SEND_MAIL();exit;}
	
js();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{$_GET["hostname"]}:{send_test_message}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="RTMMail('514','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');";
	echo $html;
}



function SEND_MAIL(){
	$main=new maincf_multi($_POST["hostname"]);
	$ipaddr=$main->ip_addr;

	
	
	include_once(dirname(__FILE__)."/ressources/smtp/smtp.php");
	
	$Parms["host"]=$ipaddr;
	$Parms["DonotResolvMX"]=true;
	$smtp=new smtp($Parms);
	$smtp->bindto=$_POST["TESTS-BIND"];;
	$smtp->from=$_POST["TESTS-FROM"];
	$smtp->recipients=$_POST["TESTS-TO"];
	$f[]="Return-Path: <{$_POST["TESTS-FROM"]}>";
	$f[]="Subject: {$_POST["TESTS-SUB"]}";
	$f[]="From: {$_POST["TESTS-FROM"]}";
	$f[]="Sender: {$_POST["TESTS-FROM"]}";
	$f[]="Reply-To: {$_POST["TESTS-FROM"]}";
	$f[]="X-Sender: {$_POST["TESTS-FROM"]}";
	$f[]="Envelope-To: {$_POST["TESTS-TO"]}";
	$smtp->headers=@implode("\n", $f);
	$smtp->body=$_POST["TESTS-BOD"];
	
	if(!$smtp->connect()){
		echo "Instance {$_POST["hostname"]}\nIP: $ipaddr:25\n";
		echo @implode("\n", $smtp->errors);
		return;
	}
	
	if(!$smtp->send()){
		echo @implode("\n", $smtp->errors);
		return;
	}
	echo @implode("\n", $smtp->errors);
	
	
	
	
}



function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$main=new maincf_multi($_GET["hostname"]);
	$ipaddr=$main->ip_addr;	
	
	$nets=Field_array_Hash($ips,"$t-bind",$_COOKIE["TESTS-BIND"],"style:font-size:16px;padding:3px");
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:14px'>{bind_address}:</td>
	<td style='font-size:16px;'> $nets</td>
	</tr>	
	<tr>
	<td class=legend style='font-size:14px'>{destination}:</td>
	<td style='font-size:16px;'>$ipaddr</td>
	</tr>	
	<tr>
	<td class=legend style='font-size:14px'>{from}:</td>
	<td> ". Field_text("$t-from",$_COOKIE["TESTS-FROM"],"font-size:16px;width:228px")."</td>
	</tr>	
	<tr>
	<td class=legend style='font-size:14px'>{to}:</td>
	<td> ". Field_text("$t-to",$_COOKIE["TESTS-TO"],"font-size:16px;width:228px")."</td>
	</tr>	
	<tr>
	<td class=legend style='font-size:14px'>{subject}:</td>
	<td> ". Field_text("$t-subject",$_COOKIE["TESTS-SUB"],"font-size:16px;width:350px")."</td>
	</tr>	
	<tr>
		<td colspan=2 ><textarea id='$t-body' style='width:100%;height:150px;overflow:auto;border:1px solid black;font-size:14px'>{$_COOKIE["TESTS-BOD"]}</textarea>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "MultipleInstanceSendEmailTest()",16)."</td>
	</tr>
	</tbody>
	</table>
	
	
	<script>
	
	var x_MultipleInstanceSendEmailTest= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		document.getElementById('$t').innerHTML='';
		
	}	
		
		function MultipleInstanceSendEmailTest(){
			Set_Cookie('TESTS-FROM', document.getElementById('$t-from').value, '3600', '/', '', '');
			Set_Cookie('TESTS-TO', document.getElementById('$t-to').value, '3600', '/', '', '');
			Set_Cookie('TESTS-SUB', document.getElementById('$t-subject').value, '3600', '/', '', '');
			Set_Cookie('TESTS-BOD', document.getElementById('$t-body').value, '3600', '/', '', '');
			Set_Cookie('TESTS-BIND', document.getElementById('$t-bind').value, '3600', '/', '', '');
			
			var XHR = new XHRConnection();
			XHR.appendData('TESTS-FROM',document.getElementById('$t-from').value);
			XHR.appendData('TESTS-TO',document.getElementById('$t-to').value);
			XHR.appendData('TESTS-SUB',document.getElementById('$t-subject').value);
			XHR.appendData('TESTS-BOD',document.getElementById('$t-body').value);
			XHR.appendData('TESTS-BIND',document.getElementById('$t-bind').value);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');			
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_MultipleInstanceSendEmailTest);				
		}
	</script>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
