<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["HardDisksWatchDog"])){Save();exit;}
	js();
function js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body('{internal_hard_drives}:{watchdog} '.$_GET["dev"]);
	$dev=urlencode($_GET["dev"]);
	echo "YahooWin3('895','$page?popup=yes&dev=$dev','$title');";
	
}


function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$WatchDog=unserialize($sock->GET_INFO("HardDisksWatchDog"));
	$WatchDog_enabled=0;
	$dev=$_GET["dev"];
	$t=time();
	if(isset($WatchDog[$dev])){$WatchDog_enabled=1;}
	
	
	$p=Paragraphe_switch_img("{monitor_disk_space}", "{monitor_disk_space_explain}",
	"HardDisksWatchDog-$t",$WatchDog_enabled,null,850);
	
	$html="
	<div style='width:98%' class=form>
	$p
	<p>&nbsp;</p>
	<div style='width:100%;text-align:right'>". button("{apply}","Save$t()",28)."</div>
			
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	YahooWin3Hide();
	LoadAjax('hd-display','system.internal.disks.php?hd-display=yes')
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('HardDisksWatchDog',  document.getElementById('HardDisksWatchDog-$t').value);
	XHR.appendData('dev',  '{$_GET["dev"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}		
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function Save(){
	$sock=new sockets();
	$dev=$_POST["dev"];
	$HardDisksWatchDog=$_POST["HardDisksWatchDog"];
	$WatchDog=unserialize($sock->GET_INFO("HardDisksWatchDog"));
	if($HardDisksWatchDog==0){
		unset($WatchDog[$dev]);
	}else{
		$WatchDog[$dev]=true;
	}
	
	$sock->SaveConfigFile(serialize($WatchDog), "HardDisksWatchDog");
}

