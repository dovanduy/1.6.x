<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.amavis.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-settings"])){popup_settings();exit;}
	if(isset($_POST["smtp_sender"])){save();exit;}
	if(isset($_GET["events-search"])){events_search();exit;}

js();
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{TEST_SMTP_CONNECTION}::{$_GET["hostname"]}");
	echo "YahooWin6('990','$page?popup=yes&servername={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}','$title');";	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{TEST_SMTP_CONNECTION}::{$_GET["servername"]}");
	$parameters=$tpl->javascript_parse_text("{write_message_and_send}");
	$buttons="
	buttons : [
	{name: '$parameters', bclass: 'apply', onpress : SMTPTestsSets},$BrowsAD
	],";	
	
	$events=$tpl->_ENGINE_parse_body("{events}");
$html="<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events-search=yes&t=$t&servername={$_GET["servername"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$events', name : 'events', width : 926, sortable : true, align: 'left'},
	
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'events'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});	

function SMTPTestsSets(){
	YahooWin3('850','$page?popup-settings=yes&servername={$_GET["servername"]}&ou={$_GET["ou"]}&t=$t','$parameters');

}

";
echo $html;
	
}

function popup_settings(){
	$t=$_GET["t"];
	$sock=new sockets();
	$Key=md5("SMTPTESTS-{$_GET["servername"]}&ou={$_GET["ou"]}");
	$datas=unserialize(base64_decode($sock->GET_INFO($Key)));
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="
	<div id='params-$t' style='text-align:right'><strong>Key:$Key</strong></div>
	<table style='width:99%' class=form>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("smtp_sender-$t",trim($datas["smtp_sender"]),'font-size:18px;padding:3px;width:100%')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{recipient}:</strong></td>
		<td>" . Field_text("smtp_dest-$t",trim($datas["smtp_dest"]),'font-size:18px;padding:3px;width:100%')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{use_local_service}:</strong></td>
		<td>" . Field_checkbox_design("smtp_local-$t",1,$datas["smtp_local"],"smtp_localcheck()")."</td>
	</tr>	

	<tr>
		<td nowrap class=legend style='font-size:18px'>{relay}:</strong></td>
		<td>" . Field_text("relay-$t",trim($datas["relay"]),'font-size:18px;padding:3px;width:100%')."</td>
	</tr>	
	<tr>
		<td nowrap class=legend style='font-size:18px'>{authenticate}:</strong></td>
		<td>" . Field_checkbox_design("smtp_auth-$t",1,$datas["smtp_auth"],"smtp_authCheck()")."</td>
	</tr>					
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text("smtp_auth_user-$t",trim($datas["smtp_auth_user"]),'font-size:18px;padding:3px;width:150px')."</td>
	</tr>	
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($datas["smtp_auth_passwd"]),'font-size:18px;padding:3px;width:150px')."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{save_and_send}","SaveTestSend()",35)."</td>
	</tr>
	</table>
	<script>
	var x_SaveTestSend= function (obj) {
		var results=obj.responseText;
		document.getElementById('params-$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
		YahooWin3Hide();
	}
	
	function smtp_localcheck(){
		document.getElementById('relay-$t').disabled=false;
		if(document.getElementById('smtp_local-$t').checked){
			document.getElementById('relay-$t').disabled=true;
			}
	}
	
	function smtp_authCheck(){
		document.getElementById('smtp_auth_user-$t').disabled=true;
		document.getElementById('smtp_auth_passwd-$t').disabled=true;
		
		
		if(document.getElementById('smtp_auth-$t').checked){
			document.getElementById('smtp_auth_user-$t').disabled=false;
			document.getElementById('smtp_auth_passwd-$t').disabled=false;		
		}
	}

	function SaveTestSend(){
		var XHR = new XHRConnection();
		if(document.getElementById('smtp_auth-$t').checked){XHR.appendData('smtp_auth',1);}else {XHR.appendData('smtp_auth',0);}
		if(document.getElementById('smtp_local-$t').checked){XHR.appendData('smtp_local',1);}else {XHR.appendData('smtp_local',0);}
		var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
		XHR.appendData('relay',document.getElementById('relay-$t').value);
		XHR.appendData('smtp_sender',document.getElementById('smtp_sender-$t').value);
		XHR.appendData('smtp_dest',document.getElementById('smtp_dest-$t').value);
		XHR.appendData('smtp_sender',document.getElementById('smtp_sender-$t').value);
		XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user-$t').value);
		XHR.appendData('servername','{$_GET["servername"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('smtp_auth_passwd',pp);
		AnimateDiv('params-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveTestSend);
	}
	smtp_authCheck();
	smtp_localcheck();
</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function  save(){
	$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	$Key=md5("SMTPTESTS-{$_POST["servername"]}&ou={$_POST["ou"]}");
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), $Key);
	$sock->getFrameWork("services.php?test-send-email=$Key");
	
}

function events_search(){
	$sock=new sockets();
	$Key=md5("SMTPTESTS-{$_GET["servername"]}&ou={$_GET["ou"]}");	
	if(!is_file("ressources/logs/$Key.log")){json_error_show("no event");}
	
	$array=file("ressources/logs/$Key.log");
	
$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	
	if($_POST["query"]<>null){$search=$_POST["query"];}
	$search=str_replace("[", "\[", $search);
	$search=str_replace(".", "\.", $search);
	$search=str_replace("*", ".*", $search);
	
	$c=0;
	while (list ($key, $line) = each ($array) ){
			
			if(trim($line)==null){continue;}
			if(strlen(trim($line))<3){continue;}
			$date=null;
			$host=null;
			$service=null;
			$pid=null;
			$color="black";
			$subtext=null;
			if(preg_match("#(.+?)\s+(.+?)\s+(.+?):\[(.+?)\]:\[(.*?)::(.*?)\]\s+(.*)#", $line,$re)){
				$date=$re[2];
				$script=$re[3];
				$pid=$re[4];
				$function=$re[5];
				$lineN=$re[6];
				$line=$re[7];
				$subtext="<div><i style='font-size:16px'>File:$script pid:$pid function:$function on line:$lineN</i></div>";
			}
			
			if($search<>null){if(!preg_match("#$search#i", $line)){continue;}}
			if(preg_match("#(ERROR|WARN|FATAL|UNABLE|Failed)#i", $line)){$color="#D61010";}
				
			$style="<span style='color:$color;font-size:16px'>";
			$styleoff="</span>$subtext";
			$line=str_replace("::", ":", $line);
			$line=str_replace(":", ":<br>", $line);
			$lines=array();
			$lines[]="$style$line$styleoff";
					
		
		$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
		

	}
	$data['total'] = $c;
	
echo json_encode($data);			
	
}