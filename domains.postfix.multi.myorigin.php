<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["myorigin"])){save();exit;}
	
js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="myorigin:{$_GET["hostname"]}";
	$html="YahooWinS('550','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}','$title')";
	echo $html;
}


function popup(){
$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$ldap=new clladp();
	$main=new maincf_multi($_GET["hostname"]);
	$myorigin=$main->GET("myorigin");
	$domains=$ldap->Hash_domains_table($_GET["ou"]);
	while (list ($key, $line) = each ($tr) ){
		$domainz[$key]=$key;
	
	}
if($myorigin==null){$myorigin='$mydomain';}	
if($myorigin<>'$mydomain'){
	$domainz[$myorigin]=$myorigin;
}
$domainz['$myhostname']='$myhostname';
$domainz['$mydomain']='$mydomain';

$html="
<div id='$t'>
<div class=text-info style='font-size:13px'>{myorigin_text}</div>
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:16px'>myorigin:</td>
	<td>". Field_array_Hash($domainz, "domainz-$t",$myorigin,"style:font-size:16px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{or}:</td>
	<td>".  Field_text("free-$t",null,"font-size:16px;width:210px")."</td>
</tr>
<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveMyOrginin$t()",16)."</td>
</tr>
</table>
</div>
<script>
	var xSaveMyOrginin$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"'+results.length);}
		YahooWinSHide();
		RefreshTab('main_multi_config_postfix{$_GET["t"]}')
		}
		
	
		function SaveMyOrginin$t(){
			var XHR = new XHRConnection();
			XHR.appendData('myorigin',document.getElementById('domainz-$t').value);
			XHR.appendData('myorigin2',document.getElementById('free-$t').value);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',xSaveMyOrginin$t);	
			
		}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$main=new maincf_multi($_POST["hostname"]);
	if($_POST["myorigin2"]<>null){$_POST["myorigin"]=$_POST["myorigin2"];}
	$main->SET_VALUE("myorigin", $_POST["myorigin"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-reconfigure={$_POST["hostname"]}");	
	
}