<?php
session_start();
if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.pure-ftpd.inc');
include_once('ressources/class.apache.inc');
include_once('ressources/class.freeweb.inc');
$user=new usersMenus();
if($user->AsWebMaster==false){
	$tpl=new templates();
	echo "<script>alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');</script>";
	die();exit();
}

if(isset($_POST["bandlimit"])){Save();exit;}



page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->APACHE_MOD_BW){
		echo FATAL_WARNING_SHOW_128("{the_specified_module_is_not_installed}");
		return;
	}
	
	$servername_enc=urlencode($_GET["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$free=new freeweb($_GET["servername"]);
	$Params=$free->Params;
	$t=time();
	$ForceBandWidthModule=intval($Params["ModeBw"]["ForceBandWidthModule"]);
	$BandwidthAll=intval($Params["ModeBw"]["BandwidthAll"]);


	if($BandwidthAll==0){$BandwidthAll=1536000;}
	$BandwidthAll=$BandwidthAll/1024;
	
	$html="

	<div style='width:98%' class=form>
	". Paragraphe_switch_img("{apache_Bandwidth_enable}", "{apache_Bandwidth_explain}","bandlimit","$free->bandlimit",null,650)."
	<table style='width:100%'>
	<td colspan=3 align=right>". button("{rules}","Loadjs('freeweb.mod.bw.php?servername=$servername_enc')",24)."</td>
	<tr>
		<td class=legend style='font-size:18px'>{limit_all_requests}:</td>
		<td>". Field_checkbox("ForceBandWidthModule",1,$ForceBandWidthModule)."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{default_limit}:</td>
		<td style='font-size:18px'>". Field_text("BandwidthAll",$BandwidthAll,"font-size:18px;width:90px")."&nbsp;KB/s</td>
		<td></td>
	</tr>

	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","Save$t()",24)."</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	</div>
<script>
	var xSave$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_freeweb_qos');
	}
	
function Save$t(){
	XHR.appendData('bandlimit',document.getElementById('bandlimit').value);
	if(document.getElementById('ForceBandWidthModule').checked){
		XHR.appendData('ForceBandWidthModule',1);
	}else{
		XHR.appendData('ForceBandWidthModule',0);
	}
	
	
	XHR.appendData('BandwidthAll',document.getElementById('BandwidthAll').value);
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function Save(){
	$free=new freeweb($_POST["servername"]);
	
	$_POST["BandwidthAll"]=$_POST["BandwidthAll"]*1024;
	
	while (list ($num, $ligne) = each ($_POST) ){
		$free->Params["ModeBw"][$num]=$ligne;
		
	}
	
	$q=new mysql();
	$q->QUERY_SQL("UPDATE freeweb SET `bandlimit`='{$_POST["bandlimit"]}' WHERE servername='{$_POST["servername"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$free->SaveParams();

	
}
