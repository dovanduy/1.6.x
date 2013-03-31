<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');

	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_POST["cache_mem"])){save();exit;}
	if(isset($_POST["HugePages"])){SaveHugePages();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cache_mem}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="YahooWin3('650','$page?popup=yes','$title');";
	echo $html;
	
	
	
	
}

function save(){
	$squid=new squidbee();
	$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	$squid->SaveToLdap(true);
}




function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$cache_mem=$squid->global_conf_array["cache_mem"];
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	$sock=new sockets();
	
	$HugePages=$sock->GET_INFO("HugePages");
	$KernelShmmax=$sock->GET_INFO("KernelShmmax");
	
	
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
	$nr_hugepages=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.nr_hugepages"));
	if(!is_numeric($HugePages)){$HugePages=0;}
	
	
	$meminfo=unserialize(base64_decode($sock->getFrameWork("system.php?meminfo=yes")));
	$kernel_shmmax=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("kernel.shmmax"));
	if(!is_numeric($KernelShmmax)){$KernelShmmax=0;}
	
	
	$MEMTOTAL=intval($meminfo["MEMTOTAL"]);
	
	
	if($kernel_shmmax<>$MEMTOTAL){
		$kernel_shmmax_proposal=FormatBytes($meminfo["MEMTOTAL"]/1024);
	}
	
	$HUGEPAGESIZE=intval($meminfo["HUGEPAGESIZE"]);
	
	$memprop=intval($MEMTOTAL*0.7);
	
	$nr_hugepages_proposal=$memprop;
	$nr_hugepages_proposal_prc="$memprop/$HUGEPAGESIZE = $nr_hugepages_proposal";
	

	$nr_hugepages_proposal=FormatBytes($nr_hugepages_proposal/1024);
	
	
	
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{server_memory}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["MEMTOTAL"]/1024)."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{memory}:</td>
		<td style='font-size:16px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:16px;width:65px")."&nbsp;MB<td>
		<td style='font-size:16px' width=1%>". help_icon("{cache_mem_text}")."<td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveCacheMem()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<div id='1$t'>
	</div>
	<div style='font-size:18px;font-weight:bold;margin-top:10px'>{system}::HugePages</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{size}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGESIZE"]/1024)."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{total}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGES_TOTAL"]/1024)."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{free}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGES_FREE"]/1024)."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>kernel shmmax:</td>
		<td style='font-size:16px'>". FormatBytes($kernel_shmmax/1024)." <strong style='font-size:11px'><b>{proposal}:</b>$kernel_shmmax_proposal</strong><td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>HugePages:</td>
		<td style='font-size:16px'>". FormatBytes($nr_hugepages/1024)." <strong style='font-size:11px'><b>{proposal}:</b>$nr_hugepages_proposal</strong><td>
		<td style='font-size:16px' width=1%><td>
	</tr>
	<tr>
	</table>
<script>
	var x_SaveCacheMem=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin3Hide();
		RefreshTab('squid_main_svc');
		Loadjs('squid.restart.php?prepare-js=yes');
	}	
	
	function SaveCacheMem(){
		var XHR = new XHRConnection();
		XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem);	
	}	

	function SaveHugePages(){
		var XHR = new XHRConnection();
		XHR.appendData('HugePages',document.getElementById('HugePages-$t').value);
		XHR.appendData('KernelShmmax',document.getElementById('shmmax-$t').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem);	
	}	
	
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}

function SaveHugePages(){
	$sock=new sockets();
	$sock->SET_INFO("HugePages",$_POST["HugePages"]);
	$sock->SET_INFO("KernelShmmax",$_POST["KernelShmmax"]);	
	$sock->getFrameWork("system.php?HugePages=yes");
	
}
