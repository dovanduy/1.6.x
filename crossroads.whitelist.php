<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	
	
	
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["allow"])){allow();exit;}
	if(isset($_GET["deny"])){allow(1);exit;}
	if(isset($_POST["value-del"])){item_delete();exit;}
	if(isset($_POST["value-add"])){item_add();exit();}
	if(isset($_GET["help"])){help_page();exit();}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{crossroads_access_control}");
	$html="YahooWin3(600,'$page?popup=yes','$title')";
	echo $html;
	
}


function allow($deny=0){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$users=new usersMenus();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("CrossRoadsParams")));	
	$array=$MAIN["ALLOW"];
	if($deny==1){$array=$MAIN["DENY"];}
	$style="font-size:16px;font-weight:bold";
	$give_pattern=$tpl->javascript_parse_text("{give_pattern}");
	$add=imgtootltip("plus-24.png","{add}","WhiteBlackCrossAdd()");
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th>{hosts}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	while (list ($num, $ligne) = each ($array) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$id=md5($ligne);
		$delete=imgtootltip("delete-32.png","{delete} $ligne","WhiteBlackCrossDelete('$ligne','$id');");
	$html=$html."
		<tr class=$classtr id='$id'>
			<td width=99% $style nowrap colspan=2><strong style='font-size:16px'>$ligne</strong></td>
			<td width=1%>$delete</td>
		</tr>
		";
	}
	$html=$html."</tbody></table>
	
	<script>
		var mime_id='';
	
	
	var x_WhiteBlackCrossDelete= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#'+mime_id).remove();
		}

	var x_WhiteBlackCrossAdd= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			RefreshTab('main_config_crossroads_ad');
		}			
		
	
		function WhiteBlackCrossDelete(host,id){
				mime_id=id;
				var XHR = new XHRConnection();
				XHR.appendData('value-del',host);
				XHR.appendData('value-type',$deny);
				XHR.sendAndLoad('$page', 'POST',x_WhiteBlackCrossDelete);				
		}
		
		function WhiteBlackCrossAdd(){
				var item=prompt('$give_pattern','');
				if(item){
					var XHR = new XHRConnection();
					XHR.appendData('value-add',item);
					XHR.appendData('value-type',$deny);
					XHR.sendAndLoad('$page', 'POST',x_WhiteBlackCrossAdd);
				}				
		}		
		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$users=new usersMenus();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("CrossRoadsParams")));	
	$array=$MAIN["ALLOW"];
	$deny=$_POST["value-type"];
	if($deny==1){$array=$MAIN["DENY"];}
	unset($array[$_POST["value-del"]]);
	if($deny==1){$MAIN["DENY"]=$array;}else{$MAIN["ALLOW"]=$array;}
	$NEW_MAIN=base64_encode(serialize($MAIN));
	$sock->SaveConfigFile($NEW_MAIN, "CrossRoadsParams");
	$sock->getFrameWork("services.php?build-iptables=yes");
	
	
}

function  item_add(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$users=new usersMenus();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("CrossRoadsParams")));	
	$array=$MAIN["ALLOW"];
	$deny=$_POST["value-type"];
	if($deny==1){$array=$MAIN["DENY"];}
	$array[$_POST["value-add"]]=$_POST["value-add"];
	if($deny==1){$MAIN["DENY"]=$array;}else{$MAIN["ALLOW"]=$array;}
	$NEW_MAIN=base64_encode(serialize($MAIN));
	$sock->SaveConfigFile($NEW_MAIN, "CrossRoadsParams");
	$sock->getFrameWork("services.php?build-iptables=yes");	
	
}
	
	
function popup(){
	
	$page=CurrentPageName();
	$array["allow"]='{allow}';
	$array["deny"]='{deny}';
	$array["help"]='{help}';
	
	$tpl=new templates();
	
	$fontsize="style='font-size:14px'";$width="100%";$newinterface="?newinterface=yes";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_crossroads_ad style='width:100%;height:450px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_crossroads_ad').tabs();
			});
		</script>";	
	
}


function  help_page(){
	$page=CurrentPageName();
	$tpl=new templates();			
	$html="<div class=text-info style='font-size:14px'>{crossroads_wb_text}</div>";
	echo $tp->_ENGINE_parse_body($html);
	
}