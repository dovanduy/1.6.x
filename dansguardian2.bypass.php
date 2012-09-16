<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["bypass"])){save();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{rule}&raquo;{$_GET["ID"]}&raquo;{TemporaryDeniedPageBypass}");
	$html="YahooWin4('580','$page?popup=yes&ID={$_GET["ID"]}','$title')";
	echo $html;
	
}

function popup() {
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$ID=$_GET["ID"];
	$sql="SELECT * FROM webfilter_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$more8chars=$tpl->javascript_parse_text("{more8chars}");
	if($ID==0){
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	}
	
	if($ligne["BypassSecretKey"]==null){$ligne["BypassSecretKey"]=md5(time());}	
	
	$TIMES[0]="{none}";
	$TIMES[300]="5 {minutes}";
	$TIMES[900]="15 {minutes}";
	$TIMES[1800]="30 {minutes}";
	$TIMES[3600]="1 {hour}";
	$TIMES[7200]="2 {hours}";
	$TIMES[14400]="4 {hours}";
	$t=time();
	$html="
	<div id='$t'>
	<div class=explain style='font-size:14px'>{TemporaryDeniedPageBypassExplain}<br>{BypassSecretKeyExplain}</div>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:16px'>{bypassFor}:</td>
			<td>". Field_array_Hash($TIMES,"bypass$t", $ligne["bypass"],"style:font-size:16px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{BypassSecretKey}:</td>
			<td>". Field_password("BypassSecretKey$t", $ligne["BypassSecretKey"],"font-size:16px;width:220px")."</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","bypassForSave()")."</td>
		</tr>
		
	</tbody>
	</table>
	</div>
	<script>
	var x_bypassForSave= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
		YahooWin4Hide();
	}
	
		function bypassForSave(){
		      var XHR = new XHRConnection();
		      
		      var bypass=document.getElementById('bypass$t').value;
		      if(bypass.length<8){alert('$more8chars');return;}
		      XHR.appendData('bypass', document.getElementById('bypass$t').value);
		      XHR.appendData('BypassSecretKey', document.getElementById('BypassSecretKey$t').value);
		      XHR.appendData('ID','$ID');
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_bypassForSave);  		
		}
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$sock=new sockets();
	if($_POST["ID"]==0){
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$ligne["bypass"]=$_POST["bypass"];
		$ligne["BypassSecretKey"]=$_POST["BypassSecretKey"];
		
		writelogs("Default rule, saving DansGuardianDefaultMainRule",__FUNCTION__,__FILE__,__LINE__);
		$sock->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");	
		writelogs("Ask to compile rule...",__FUNCTION__,__FILE__,__LINE__);
		$sock->getFrameWork("webfilter.php?compile-rules=yes");
		return;
	}	
	
	
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilter_rules SET bypass='{$_POST["bypass"]}',BypassSecretKey='{$_POST["bypass"]}' WHERE ID='{$_POST["ID"]}'";	
	$q->QUERY_SQL($sql);
	if(!$q->ok){if(strpos($q->mysql_error, "Unknown column")>0){$q->CheckTables();$q->QUERY_SQL($sql);}}
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
}
