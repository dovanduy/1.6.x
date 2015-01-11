<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.updateutility2.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$ERROR_NO_PRIVS');";return;
	}
	
	if(isset($_GET["setup1"])){setup1();exit;}
	if(isset($_POST["UpdateUtilityStorePath"])){Save();exit;}
	
js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$create_a_dedicated_container=$tpl->javascript_parse_text("{create_a_dedicated_container}");
		$html="YahooWin3('650','$page?setup1=yes','$create_a_dedicated_container');";
		echo $html;
	}	

function setup1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}	
	

	$html="
	<div id='$t'></div>		
	<div style='font-size:16px' class=text-info>{updateutility_setup1}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("UpdateUtilityStorePath-$t",$UpdateUtilityStorePath,"font-size:16px;width:300px")."</td>
		<td width=1%>". button("{browse}", "Loadjs('SambaBrowse.php?field=UpdateUtilityStorePath-$t&no-shares=yes');","12px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{disksize}:</td>
		<td  style='font-size:16px'>". Field_text("UpdateUtilityDiskSize-$t",3000,"font-size:16px;width:90px")."&nbsp;MB</td>
		<td></td>
	</tr>		
		<td colspan=3 align='right'>". button("{create_container}","Save$t()",18)."</td>
	</tr>				
	</table>
	<script>
	var x_Save$t= function (obj) {
	      var results=obj.responseText;
	      document.getElementById('$t').innerHTML=''
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	      YahooWin3Hide();
	}	

	function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('UpdateUtilityStorePath',document.getElementById('UpdateUtilityStorePath-$t').value);
			XHR.appendData('disksize',document.getElementById('UpdateUtilityDiskSize-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);	
		}	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$disksize=$_POST["disksize"];
	$UpdateUtilityStorePath=$_POST["UpdateUtilityStorePath"];
	$HardDriveSizeMB=unserialize(base64_decode($sock->getFrameWork("system.php?HardDriveDiskSizeMB=".base64_encode($UpdateUtilityStorePath))));
	if(!is_array($HardDriveSizeMB)){
		echo "Fatal Error Cannot retreive information for `$UpdateUtilityStorePath`";
		return;
	}
	
	if($disksize<2500){
		echo "Fatal 2500MB minimal size";
		return;		
	}
	
	$AVAILABLEMB=$HardDriveSizeMB["AVAILABLE"];
	if($AVAILABLEMB<$disksize){
		$T=$disksize-$AVAILABLEMB;
		echo "Fatal Error : Available: {$AVAILABLEMB}MB, need at least {$T}MB";
		return;
	}
	
	$sql="INSERT INTO loop_disks (`path`,`size`,`disk_name`,`maxfds`) VALUES ('$UpdateUtilityStorePath','$disksize','UpdateUtility','25000')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->SET_INFO("UpdateUtilityUseLoop", 1);
	$sock->getFrameWork("lvm.php?loopcheck=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-updateutility=yes");
	
}
