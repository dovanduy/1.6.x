<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	if(isset($_GET["prepare-popup"])){popup();exit;}
	if(isset($_GET["prepare-1"])){run_task1();exit;}
	if(isset($_GET["prepare-2"])){run_task2();exit;}
	
js();


function js() {
	$tpl=new templates();
	$page=CurrentPageName();
	if(!is_numeric($_GET["t"])){$t=time();}
	$title=$tpl->javascript_parse_text("{rescan-disk-system}");
	echo "YahooWinBrowse('500','$page?prepare-popup=yes&t=$t','$title')";
	
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait}:{rescan-disk-system} ...");
	
	$html="
	<div id='progress-$t'></div>
	<center id='step$t-0' style='font-size:16px'>$text</center>
	<center id='step$t-1' style='font-size:16px'></center>
	<script>
	function Step1$t(){
		$('#progress-$t').progressbar({ value: 15 });
		LoadAjaxSilent('step$t-1','$page?prepare-1=yes&t=$t');
	}
	
	
	$('#progress-$t').progressbar({ value: 5 });
	setTimeout(\"Step1$t()\",1000);
	</script>
	
	
	";
	echo $html;	
	
}

function run_task1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$data=base64_decode($sock->getFrameWork("cmd.php?usb-scan-write=yes&force=yes&tenir=yes&MyCURLTIMEOUT=120"));
	$text=$tpl->_ENGINE_parse_body("{please_wait_checking_settings}...");
	$html="

	<center id='step$t-2' style='font-size:16px'>$text</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>$data</textarea>
	<script>
	function Step2$t(){
		$('#progress-$t').progressbar({ value: 100 });
		document.getElementById('step$t-2').innerHTML='';
		if(document.getElementById('main_config_internal_disks')){RefreshTab('main_config_internal_disks');}
		if(document.getElementById('btrfs-tabs')){RefreshTab('btrfs-tabs');}
		
	}
	

	setTimeout(\"Step2$t()\",1000);
</script>


";
echo $html;

}