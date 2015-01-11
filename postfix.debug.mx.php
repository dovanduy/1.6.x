<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/smtp/class.phpmailer.inc');
	include_once('ressources/smtp/smtp.php');
	include_once('ressources/class.mime.parser.inc');
	include_once('ressources/class.rfc822.addresses.inc');	
	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){die();}
	
	
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["mx-resolve"])){mx_resolve();exit;}
	if(isset($_GET["mx-connect"])){mx_connect();exit;}
	if(isset($_GET["mx-mbx"])){mx_mbx();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["check-msg"])){check_msg_popup();exit;}
	if(isset($_GET["check-msg-add-content"])){check_msg_add_content_popup();exit;}
	if(isset($_POST["check-msg-analyze-content"])){writelogs("Receive check-msg-analyze-content","MAIN",__FILE__,__LINE__);check_msg_save_content();exit;}
	if(isset($_GET["check-msg-show-table"])){check_msg_analyze_content();exit;}
	if(isset($_POST["banserv"])){banserv();exit;}
	if(isset($_POST["banmail"])){banmail();exit;}
	try {if(isset($_POST["send-msg-popup-sender"])){send_msg_popup_send();exit;}} catch (Exception $e) {echo $e->getMessage(); }
	if(isset($_GET["send-msg"])){send_msg_popup();exit;}
	
	
	js();
	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{check_recipients}/{check_message_content}");
	$html="YahooWinBrowse('700','$page?tabs=yes&t={$_GET["t"]}','$title')";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]="{check_recipients}";
	$array["check-msg"]="{check_message_content}";
	$array["send-msg"]="{send_email}";
		
	
		while (list ($num, $ligne) = each ($array) ){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t={$_GET["t"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_postfix_checks style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_postfix_checks\").tabs();});
			
			
			
		</script>";		
	
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$html="
	<div class=text-info style='font-size:14px'>{check_recipient_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{sender}:</td>
		<td>".Field_text("sender-$t",null,"font-size:16px;width:220px")."</td>
	</tr>
		
	<tr>
		<td class=legend style='font-size:16px'>{recipient}:</td>
		<td>".Field_text("recipient-$t",null,"font-size:16px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{check}","CheckRecipientStart();","18px")."</td>
	</tr>
	</table>
	<div id='mx-resolved-$t'></div>
	<div id='mx-connected-$t'></div>
	<div id='mx-mbx-$t'></div>
	
	
	<script>
		function CheckRecipientStart(){
			var sender=document.getElementById('sender-$t').value;
			var recipient=document.getElementById('recipient-$t').value;
			LoadAjax('mx-resolved-$t','$page?mx-resolve=yes&sender='+sender+'&recipient='+recipient+'&t=$t');
		}
		
	</script>
			
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mx_error_fatal($text){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("
	<table style='width:99%' class=form>
	<tr>
		<td width=1%><img src='img/error-128.png'></td>
		<td width=100% style='font-size:16px'>$text</td>
	</tr>
	</table>
	
	");
	die();
	
}

function mx_resolve(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$tb=explode("@", $_GET["recipient"]);
	$t=$_GET["t"];
	$mxs=array();
	$newarray=array();
	getmxrr($tb[1], $mxhosts,$mxWeight);	

	if(count($mxhosts)==0){
		mx_error_fatal("{failed_no_such_mx}");
	}
	
	echo "<table style='width:99%;margin-top:10px' class=form>";
	
	
	while (list ($index, $hostname) = each ($mxhosts) ){
		$ipaddr=gethostbyname($hostname);
		$img="ok24.png";
		$error=null;
		if($ipaddr==$hostname){
			$ipaddr=null;
			$img="error-24.png";
			$error="{unable_to_resolve}";
		}else{
			
		}
		$weight=$mxWeight[$index];
		$newarray[$weight]=$ipaddr;
		echo $tpl->_ENGINE_parse_body("<tr>
			<td width=1%><img src='img/$img'></td>
			<td style='font-size:14px;font-weight:bold'>{weight}:$weight - $hostname [$ipaddr] $error</td>
			</tr>");
		
	}
	
	if(count($newarray)>0){
		$newarrayenc=base64_encode(serialize($newarray));
		$script="
		<script>
			LoadAjax('mx-connected-$t','$page?mx-connect=yes&sender={$_GET["sender"]}&recipient={$_GET["recipient"]}&mx=$newarrayenc&t=$t');
		</script>
		";
		
	}
	
	
	echo "</table>$script";
	
}

function mx_connect(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$tb=explode("@", $_GET["recipient"]);
	$t=$_GET["t"];	
	$mx=unserialize(base64_decode($_GET["mx"]));
	$users=new usersMenus();
	$newarray=array();
	$smtp=new smtp();
	while (list ($weigth, $ipaddr) = each ($mx) ){
		$params=array();
		$img="ok24.png";
		$error=null;		
		$params["timeout"]=2;
		$params["host"]=$ipaddr;
		$params["port"]=25;
		$params["helo"]=$users->hostname;
		$params["DonotResolvMX"]=true;
		if(!$smtp->connect($params)){
			$img="error-24.png";
			$error="{unable_to_smtp_connect}:<br>".ParseErrorsArray($smtp->errors);		
		}else{
			$newarray[$ipaddr]=$ipaddr;
		}
		echo "<table style='width:99%;margin-top:10px' class=form>";
		echo $tpl->_ENGINE_parse_body("<tr>
			<td width=1% valign='top'><img src='img/$img'></td>
			<td style='font-size:14px;font-weight:bold'>{connect}:{weight}:$weight - $ipaddr $error</td>
			</tr>");		
		
		
		
	}
	
	if(count($newarray)>0){
		$newarrayenc=base64_encode(serialize($newarray));
		$script="
		<script>
			LoadAjax('mx-mbx-$t','$page?mx-mbx=yes&sender={$_GET["sender"]}&recipient={$_GET["recipient"]}&mx=$newarrayenc&t=$t');
		</script>
		";
		
	}	
	
	echo "</table>$script";
	
}

function mx_mbx(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$tb=explode("@", $_GET["recipient"]);
	$t=$_GET["t"];	
	$mx=unserialize(base64_decode($_GET["mx"]));
	$users=new usersMenus();
	$newarray=array();
	$smtp=new smtp();
	while (list ($ip, $ipaddr) = each ($mx) ){
		$params=array();
		$img="ok24.png";
		$error=null;		
		$params["timeout"]=2;
		$params["host"]=$ipaddr;
		$params["port"]=25;
		$params["helo"]=$users->hostname;
		$params["DonotResolvMX"]=true;
		$error="{success}";
		if(!$smtp->connect($params)){
			$img="error-24.png";
			$error="{unable_to_smtp_connect}:<br>".ParseErrorsArray($smtp->errors);	
		}else{
			if(!$smtp->mail($_GET["sender"])){
				$img="error-24.png";
				$error="{unable_to_smtp_mailfrom}:<br>".ParseErrorsArray($smtp->errors);	
			}else{
				if(!$smtp->rcpt($_GET["recipient"])){
					$img="error-24.png";
					$error="{unable_to_smtp_mailto}:<br>".ParseErrorsArray($smtp->errors);		
				}
			}
		}
		
		$smtp->quit();
		echo "<table style='width:99%;margin-top:10px' class=form>";
		echo $tpl->_ENGINE_parse_body("<tr>
			<td width=1% valign='top'><img src='img/$img'></td>
			<td style='font-size:14px;font-weight:bold'>{transaction}:
			<div style='font-size:12px'>$ipaddr -&raquo; {$_GET["sender"]} -&raquo; {$_GET["recipient"]}</div> $error</td>
			</tr>");		
	}
	echo "</table>$script";
	
}

function ParseErrorsArray($array){
	while (list ($ip, $line) = each ($array) ){
		$i=$i."<li style='font-size:11px'>".htmlentities($line)."</li>";
		
	}
	return $i;
	
}

function send_msg_popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
	
	$t=time();
	
	if($EnablePostfixMultiInstance==1){
		
		$hosts["127.0.0.1"]="127.0.0.1";
		$sql="SELECT ou, ip_address, `key` , `value` FROM postfix_multi WHERE (`key` = 'myhostname')";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ip_address=$ligne["ip_address"];
			$hostname=$ligne["value"];
			if(strlen($ip_address)<4){continue;}
			$hosts[$ip_address]=$hostname;		
		}
		
		$hosts_field="	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>".Field_array_Hash($hosts,"host-$t",$_COOKIE["send-msg-popup-host"],null,null,0,"font-size:16px")."</td>
		</tr>";
	}
	
	
	$html="
	<div id='work-$t'></div>
	<div class=text-info style='font-size:14px'>{check_sendmsg_explain}</div>
	<table style='width:99%' class=form>
	$hosts_field
	<tr>
		<td class=legend style='font-size:16px'>{sender}:</td>
		<td>".Field_text("sender-$t",$_COOKIE["send-msg-popup-sender"],"font-size:16px;width:220px")."</td>
	</tr>
		
	<tr>
		<td class=legend style='font-size:16px'>{recipient}:</td>
		<td>".Field_text("recipient-$t",$_COOKIE["send-msg-popup-recipient"],"font-size:16px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{subject}:</td>
		<td>".Field_text("subject-$t",$_COOKIE["send-msg-popup-subject"],"font-size:16px;width:350px")."</td>
	</tr>	
	
	
	<tr>
		<td colspan=2 class=legend style='font-size:16px'>{message}:</td>
	</tr>
	<tr>
		<td colspan=2>
	<textarea id='content-$t' 
		style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;border:5px solid #8E8E8E;overflow:auto;font-size:16px'>{$_COOKIE["send-msg-popup-content"]}</textarea>
	</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{send}","MsgSendTest$t();","18px")."</td>
	</tr>
	</table>

	
	
	<script>
		var x_MsgSendTest$t=function(obj){
	      var tempvalue=trim(obj.responseText);
	      document.getElementById('work-$t').innerHTML=tempvalue;
	      $('#events-table-{$_GET["t"]}').flexReload();
	        
		}	
	
	
		function MsgSendTest$t(){
			var XHR = new XHRConnection();
			var sender=document.getElementById('sender-$t').value;
			var recipient=document.getElementById('recipient-$t').value;
			var subject=document.getElementById('subject-$t').value;
			var content=document.getElementById('content-$t').value;
			
			if(document.getElementById('host-$t')){
				Set_Cookie('send-msg-popup-host',document.getElementById('host-$t').value,'3600', '/', '', '');
				XHR.appendData('send-msg-popup-host',document.getElementById('host-$t').value);
			}
			
			Set_Cookie('send-msg-popup-sender',sender,'3600', '/', '', '');
			Set_Cookie('send-msg-popup-recipient',recipient,'3600', '/', '', '');
			Set_Cookie('send-msg-popup-subject',subject,'3600', '/', '', '');
			Set_Cookie('send-msg-popup-content',content,'3600', '/', '', '');  
			AnimateDiv('work-$t');
			
			
			XHR.appendData('send-msg-popup-sender',sender);
			XHR.appendData('send-msg-popup-recipient',recipient);
			XHR.appendData('send-msg-popup-subject',subject);
			XHR.appendData('send-msg-popup-content',content);
			XHR.sendAndLoad('$page', 'POST',x_MsgSendTest$t);
		}
		
	</script>
			
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function send_msg_popup_send(){
	
	$sender=$_POST["send-msg-popup-sender"];
	$recipient=$_POST["send-msg-popup-recipient"];
	$mail = new PHPMailer(false); 
	$mail->IsSMTP(); 
	$mail->Host       = "127.0.0.1";
	if(isset($_POST["send-msg-popup-host"])){
		$mail->Host       =$_POST["send-msg-popup-host"];
	}
	
	$mail->SMTPDebug  = 2;  
	
	try {
	  $mail->AddReplyTo($sender, $sender);
	  $mail->AddAddress($recipient, 'John Doe');
	  $mail->SetFrom($sender, $sender);
	  $mail->AddReplyTo($sender, $sender);
	  $mail->Subject = $_POST["send-msg-popup-subject"];
	  $mail->AltBody = $_POST["send-msg-popup-content"];
	  
  	  
	  $mail->AddCustomHeader("X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2900.3028");
	  $mail->AddCustomHeader("X-Mailer: Microsoft Office Outlook 11");
	  $mail->AddCustomHeader("Accept-Language: en-US");
	  $mail->AddCustomHeader("Content-Language: en-US");
	  $mail->AddCustomHeader("X-Priority: 0");
	  $mail->MsgHTML(nl2br(htmlentities(stripslashes($_POST["send-msg-popup-content"]))));
	if(!$mail->Send()) {
	  echo "<div><strong style='font-size:12px;color:red'>Mailer Error: " . $mail->ErrorInfo."</strong></div>";
	} else {
	  echo "<div><strong style='font-size:12px;color:black'>Message sent! $sender =&raquo; $recipient</strong></div>";
	}
	  
	} catch (phpmailerException $e) {
	  echo $e->errorMessage(); 
	} catch (Exception $e) {
	  echo $e->getMessage(); //Boring error messages from anything else!
	}	
	
}



function check_msg_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$html="
	<div class=text-info style='font-size:14px'>{check_message_content_explain}</div>
	<div style='text-align:right'><a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=269','1024','900');\" style='text-decoration:underline;font-weight:bold'>{online_help}</a></div> 
	<center>". button("{add_message_content}","CheckMSGADDContent()","16px")."</center>
	<div id='work-$t' style='margin-top:15px'></div>
	
	<script>
		function CheckMSGADDContent(){
			RTMMail('550','$page?check-msg-add-content=yes&t=$t','{add_message_content}');
		}
		
		function ResfreshTable$t(){
			LoadAjax('work-$t','$page?check-msg-show-table=yes&t=$t');
		}
		ResfreshTable$t();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function check_msg_add_content_popup(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();	
	$datas=null;
	if(is_file("ressources/logs/check_msg.msg")){$datas=@file_get_contents("ressources/logs/check_msg.msg");}
	
	$html="
	<textarea id='textarea-$t' style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;border:5px solid #8E8E8E;overflow:auto;font-size:16px'>$datas</textarea>
	<center>". button("{analyze}","AnalyzeMessage$t()","16px")."</center>
	
	<script>
		var x_AnalyzeMessage$t= function (obj) {
			LoadAjax('work-$t','$page?check-msg-show-table=yes&t=$t');
		}
	
	
		function AnalyzeMessage$t(){
			AnimateDiv('work-$t');
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('textarea-$t').value);
			XHR.appendData('check-msg-analyze-content','yes');
			XHR.appendData('msg',document.getElementById('textarea-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_AnalyzeMessage$t);
		
		}
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function check_msg_save_content(){
	writelogs("Saving msg (".strlen($_POST["msg"]).")",__FUNCTION__,__FILE__,__LINE__);
	//$_POST["msg"]=url_decode_special_tool($_POST["msg"]);
	writelogs("Put msg (".strlen($_POST["msg"]).")",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("ressources/logs/check_msg.msg", $_POST["msg"]);
	writelogs("Put msg (".strlen($_POST["msg"]).") done",__FUNCTION__,__FILE__,__LINE__);
}
function check_msg_analyze_content(){
		$page=CurrentPageName();
		$t=$_GET["t"];
		if(!is_file("/usr/share/artica-postfix/ressources/logs/check_msg.msg")){
			if($GLOBALS["VERBOSE"]){echo "ressources/logs/check_msg.msg no such file<br>";}
			return;}
		$mime=new mime_parser_class();
		$mime->decode_bodies = 0;
		$mime->ignore_syntax_errors = 1;
		$decoded=array();
		$parameters=array('File'=>"ressources/logs/check_msg.msg",'SkipBody'=>1);	
		if(!$mime->Decode($parameters, $decoded)){echo "<span style='color:red;font-size:16px'>MIME message decoding error: $mime->error at position $mime->error_position</span>";return;}
		if(!is_array($decoded[0]["Headers"])){echo "MIME message decoding no Headers found";return false;}	
		$mime_decoded_headers=$decoded[0]["Headers"];
		
		krsort($mime_decoded_headers["received:"]);
		
		$html[]="<table style='width:99%' class=form>";
		$serv["127.0.0.1"]=true;
		$serv["localhost"]=true;
		$serv["IPv6:::1"]=true;
		$serv["::1"]=true;
		$serv["Debian-exim"]=true;
		$serv["debian-exim"]=true;
		
		if(preg_match("#<(.*?)>#", $mime_decoded_headers["from:"],$re)){$from[]=$re[1];}
		if(preg_match("#<(.*?)>#", $mime_decoded_headers["reply-to:"],$re)){$from[]=$re[1];}
		if(preg_match("#<(.*?)>#", $mime_decoded_headers["reply-to:"],$re)){$from[]=$re[1];}
		
		
		

		
		if(isset($mime_decoded_headers["envelope-to:"])){$from[]=$mime_decoded_headers["envelope-to:"];}
		if(isset($mime_decoded_headers["x-beenthere:"])){$from[]=$mime_decoded_headers["x-beenthere:"];}
		if(isset($mime_decoded_headers["sender:"])){$from[]=$mime_decoded_headers["sender:"];}
		
		
		
		while (list ($num, $ligne) = each ($mime_decoded_headers["received:"]) ){
			$ligne=str_replace("\r\n", " ", $ligne);
			$ligne=str_replace("\r", " ", $ligne);
			$ligne=str_replace("\n", " ", $ligne);
			if(preg_match("#with LMTPA#", $ligne)){continue;}
			
			if(preg_match("#envelope-from\s+(.*?)@(.+?)[\s|\(|;]#", $ligne,$re)){
				$from[]=$re[1];
			}
			
			if(preg_match("#from User\s+\(.*?\[(.*?)\]\).*?sender:\s+(.+?)\)by\s+(.+?)[\s|\(]#",$ligne,$re)){
					$re[1]=trim($re[1]);
					$re[2]=trim($re[2]);
					$re[3]=trim($re[3]);
					$sender[]=$re[2];
					$server[]=trim($re[3]);
					$server[]=trim($re[1]);
					continue;
			}

			if(preg_match("#from\s+User\s+.*?\[(.*?)\].*?by\s+(.+?)[\s|\(|;]#",$ligne,$re)){
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$server[]=trim($re[1]);
				$server[]=trim($re[2]);	
				continue;			
			}

			if(preg_match("#from\s+(.*?)\s+\(\[(.*?)\]helo=(.*?)\)by\s+(.+?)[\s|\(|;]#", $ligne,$re)){
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$re[3]=trim($re[3]);
				$server[]=trim($re[1]);
				$server[]=trim($re[2]);	
				$server[]=trim($re[3]);		
				continue;			
			}
			
			if(preg_match("#from\s+(.*?)\s+\(\[(.*?)\]\s+helo=(.+?)\)by\s+(.+?)[\s+|;]#", $ligne,$re)){
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$re[3]=trim($re[3]);
				$server[]=trim($re[1]);
				$server[]=trim($re[2]);	
				$server[]=trim($re[3]);		
				continue;			
			}
			if(preg_match("#from\s+(.*?)\s+by\s+(.*?)[;|\s]#", $ligne,$re)){
					$re[1]=trim($re[1]);
					$re[2]=trim($re[2]);
					$server[]=trim($re[1]);
					$server[]=trim($re[2]);
					continue;
			}
			
			
			if(preg_match("#from\s+(.*?)\(.*?\[(.*?)\]\)#", $ligne,$re)){
					$re[1]=trim($re[1]);
					$re[2]=trim($re[2]);
					$server[]=trim($re[1]);
					$server[]=trim($re[2]);		
					continue;
			}
			
			if(preg_match("#^by\s+(.*?)\s+with#", trim($ligne),$re)){
				$re[1]=trim($re[1]);
				$server[]=trim($re[1]);
				continue;
			}
			
			if(preg_match("#^from\s+(.*?)\s+\(\[(.*?)\].*?\)by\s+(.+)\s+with#", trim($ligne),$re)){
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$re[3]=trim($re[3]);
				$server[]=trim($re[1]);
				$server[]=trim($re[2]);	
				$server[]=trim($re[3]);
				continue;
			}			
			if(preg_match("#^from\s+(.*?)\s+\((.*?)\) by(.*?)\s+\((.*?)\)\s+with#", trim($ligne),$re)){
				$re[1]=trim($re[1]);
				$re[2]=trim($re[2]);
				$re[3]=trim($re[3]);
				$server[]=trim($re[1]);
				$server[]=trim($re[2]);	
				$server[]=trim($re[3]);
				continue;
			}					
			
			//$not[]="<strong style='color:red'>Not found: $ligne</strong>";
			
		}
		
		while (list ($num, $servx) = each ($server) ){
			$servx=strtolower($servx);
			if(preg_match("#(.*?)\s+\(#", $servx,$re)){$servx=$re[1];}
			if(preg_match("#(.*?)\(#", $servx,$re)){$servx=$re[1];}
			if(isset($serv[$servx])){continue;}
			$resv=null;
			$hostname=null;
			$ipaddr=null;
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $servx)){
				$resv=gethostbyaddr($servx);
				if($resv<>$servx){
					$hostname=$resv;
					$ipaddr=$servx;
				}
			}else{
				
				$resv=gethostbyname($servx);
				if($resv<>$servx){
					$hostname=$servx;
					$ipaddr=$resv;
				
				}
			}
			if(($hostname==null) && ($ipaddr==null)){
				$hostname=$servx;
			}else{
				$serv[$hostname]=true;
				$serv[$ipaddr]=true;	
			}
			$serv[$servx]=true;
			$textAffich=$hostname;
			if($ipaddr<>null){$textAffich="$hostname <span style='font-size:11px'>[$ipaddr]</span>";}
			
			
				$html[]="<tr>
						<td style='font-size:14px;font-weight:bold'>{server}: $textAffich</td>
						<td nowrap>". button("{add_rule}","BannServ$t('$hostname');","12px")."</td>
					</tr>
					";
			}
			
			
		
		

		
		$html[]="</table>";
		$textSrv=@implode("", $html);
		
		
		
		$html=array();
		$html[]="<table style='width:99%' class=form>";
		
		
		while (list ($num, $email) = each ($from) ){
			if(trim($email)==null){continue;}
			if(preg_match("#<(.*?)>#",$email,$re)){$email=$re[1];}
			$email=str_replace("<", "", $email);
			$email=str_replace(">", "", $email);
			if(isset($alr[$email])){continue;}
			
			
			$html[]="<tr>
						<td style='font-size:14px;font-weight:bold'>{from}: $email</td>
						<td nowrap>". button("{add_rule}","BannMail$t('$email');","12px")."</td>
					</tr>
					";
			$alr[$email]=true;
			
		}
		$html[]="</table>";
		$textemail=@implode("", $html);		
		
		
		$script="
		<script>
		var x_BannServ$t=function (obj) {
			var results=obj.responseText;
			if (results.length>3){alert(results);return;}
			
		}

		function BannServ$t(pattern){
			var XHR = new XHRConnection();
			XHR.appendData('banserv',pattern);
			XHR.sendAndLoad('$page', 'POST',x_BannServ$t);
		
		}	

		function BannMail$t(pattern){
			var XHR = new XHRConnection();
			XHR.appendData('banmail',pattern);
			XHR.sendAndLoad('$page', 'POST',x_BannServ$t);
		
		}		
		
		</script>
		";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($textSrv.$textemail).$script;
		
		
}

function banserv(){
		$pattern=$_POST["banserv"];
		$describe="Bann $pattern ". date('Y-m-d H:i:s');
		$pattern=string_to_regex($pattern);
		$pattern=base64_encode($pattern);
		$describe=addslashes($describe);
		$header="Received";
		$sql="INSERT INTO spamassassin_rules (`describe`,`pattern`,`header`,`score1`,`score2`,`score3`,`score4`,`enabled`) 
		VALUES('$describe','$pattern','$header','9.00','9.00','9.00','9.00',1)
		";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$tpl=new templates();
		echo $tpl->javascript_parse_text($q->last_id." {rule} {added}");
}

function banmail(){
		$pattern=$_POST["banmail"];
		$describe="Bann $pattern ". date('Y-m-d H:i:s');
		$pattern=string_to_regex($pattern);
		$pattern=str_replace("@", "\@", $pattern);
		$pattern=base64_encode($pattern);
		$describe=addslashes($describe);
		$header="From";
		$sql="INSERT INTO spamassassin_rules (`describe`,`pattern`,`header`,`score1`,`score2`,`score3`,`score4`,`enabled`) 
		VALUES('$describe','$pattern','$header','9.00','9.00','9.00','9.00',1)
		";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$tpl=new templates();
		echo $tpl->javascript_parse_text(" {rule} {added}: ID $q->last_id");	
}