<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_GET["cache_mem"])){save();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{tune_squid_performances}");
	$page=CurrentPageName();
	$html="
		YahooWin3('700','$page?popup=yes','$title');
	
	";
		echo $html;
	
	
	
	
}


function popup(){
	$page=CurrentPageName();
	$html="
	<div class=explain style='font-size:14px'>{tune_squid_performances_explain}</div>
	<div id='squidperfs'></div>
	
	<script>
		function refreshPerfs(){
			LoadAjax('squidperfs','$page?perfs=yes');
		}
		
		refreshPerfs();
	</script>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}


function perfs(){
	include_once("ressources/class.os.system.tools.inc");
	$os=new os_system();
	$page=CurrentPageName();
	$sock=new sockets();
	$mem=$os->memory();
	$squid=new squidbee();
	$mem=$mem["ram"]["total"];
	$memory=$mem/1000;
	$mem_cached=round($memory*0.2);
	$cache_mem=$squid->global_conf_array["cache_mem"];
	$fqdncache_size=$squid->global_conf_array["fqdncache_size"];
	$ipcache_size=$squid->global_conf_array["ipcache_size"];
	$ipcache_low=$squid->global_conf_array["ipcache_low"];
	$ipcache_high=$squid->global_conf_array["ipcache_high"];
	
	if(preg_match("#([0-9]+)\s+#",$cache_mem,$re)){$cache_mem=$re[1];}
	
	$swappiness=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.swappiness")); //2
	$vfs_cache_pressure=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.vfs_cache_pressure")); //50
	$overcommit_memory=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.overcommit_memory")); //2
	
	$tcp_max_syn_backlog=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("net.ipv4.tcp_max_syn_backlog")); //4096 
	
	if(preg_match("#([0-9]+)#",$overcommit_memory,$re)){$overcommit_memory=$re[1];}
	

/*
 * echo 1 > /proc/sys/net/ipv4/ip_forward echo 1 > /proc/sys/net/ipv4/ip_nonlocal_bind echo 0 > /proc/sys/net/ipv4/conf/all/rp_filter echo 1024 65535 > /proc/sys/net/ipv4/ip_local_port_range echo 102400 > /proc/sys/net/ipv4/tcp_max_syn_backlog echo 1000000 > /proc/sys/net/ipv4/ip_conntrack_max echo 1000000 > /proc/sys/fs/file-max echo 60 > /proc/sys/kernel/msgmni echo 32768 > /proc/sys/kernel/msgmax echo 65536 > /proc/sys/kernel/msgmnb :: Maximizing Kernel configuration 
 */	
	

	
	$html="
	<input type='hidden' id='cache_mem' value='$mem_cached'>
	
	<input type='hidden' id='vfs_cache_pressure' value='50'>
	
	<table style='width:99%' class=form>


	<tr>
		<th>&nbsp;</th>
		<th>{current_value}</th>
		<th>{proposal}</th>
	</tr>

		<tr >
			<td align='right' class=legend nowrap style='font-size:16px'>squid:{cache_mem}:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>{$cache_mem}MB</td>
			<td valign='left' style='font-size:18px'>". Field_text("cache_mem",$mem_cached,"font-size:18px;width:70px")."&nbsp;MB</strong></td>
		</tr>
		<tr>
			<td align='right' class=legend nowrap style='font-size:16px'>{fqdncache_size}:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$fqdncache_size</td>
			<td valign='middle' style='font-size:18px'>". Field_text("fqdncache_size",51200,"font-size:18px;width:70px")."&nbsp;{items}</strong></td>
		</tr>		
		
		
		<tr >
			<td align='right' class=legend nowrap style='font-size:16px'>{ipcache_size}:</strong></td>
			<td valign='middle'  align='right' style='font-size:18px;padding-right:5px'>$ipcache_size</td>
			<td valign='middle' style='font-size:18px'>". Field_text("ipcache_size",51200,"font-size:18px;width:70px")."&nbsp;{items}</td>
		</tr>
		
		<tr>
			<td align='right' class=legend nowrap style='font-size:16px'>{ipcache_low}:</strong></td>
			<td valign='middle'  align='right' style='font-size:18px;padding-right:5px'>$ipcache_low&nbsp;%</td>
			<td valign='middle' style='font-size:18px'>". Field_text("ipcache_low",90,"font-size:18px;width:60px")."&nbsp;%</strong></td>
		</tr>

		<tr >
			<td align='right' class=legend nowrap style='font-size:16px'>{ipcache_high}:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$ipcache_high&nbsp;%</td>
			<td valign='middle' style='font-size:18px'>". Field_text("ipcache_high",95,"font-size:18px;width:40px")."&nbsp;%</strong></td>
		</tr>		

		
		<tr>
		<td align='right' class=legend nowrap style='font-size:16px'>Kernel:vm.swappiness:</strong></td>
		<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$swappiness&nbsp;%</td>
		<td valign='middle' style='font-size:18px'>". Field_text("swappiness",5,"font-size:18px;width:40px")."&nbsp;%</td>
		</tr>				
		
		<tr >
			<td align='right' class=legend nowrap style='font-size:16px'>Kernel:vm.vfs_cache_pressure:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$vfs_cache_pressure</td>
			<td valign='middle'>".Field_text("vfs_cache_pressure",50,"font-size:18px;width:40px")."</strong></td>
		</tr>			
		
		
		<tr>
			<td align='right' class=legend nowrap style='font-size:16px'>Kernel:vm.overcommit_memory:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$overcommit_memory</td>
			<td valign='middle'>".Field_text("overcommit_memory",2,"font-size:18px;width:40px")."</strong></td>
		</tr>
		<tr>
			<td align='right' class=legend nowrap style='font-size:16px'>Kernel:net.ipv4.tcp_max_syn_backlog:</strong></td>
			<td valign='middle' align='right' style='font-size:18px;padding-right:5px'>$tcp_max_syn_backlog</td>		
			<td valign='middle'>".Field_text("tcp_max_syn_backlog",4096,"font-size:18px;width:60px")."</strong></td>

		</tr>		
		
					
		</table>
		<hr>
		<div style='text-align:right'>". button("{apply}","SaveSquidPerfs()",16)."</div>
		
		
		<script>
	var x_SaveSquidPerfs=function (obj) {
		var tempvalue=obj.responseText;
		refreshPerfs();
		LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
	}	
	
	function SaveSquidPerfs(){
		var XHR = new XHRConnection();
		XHR.appendData('cache_mem',document.getElementById('cache_mem').value);
		XHR.appendData('swappiness',document.getElementById('swappiness').value);
		XHR.appendData('vfs_cache_pressure',document.getElementById('vfs_cache_pressure').value);
		XHR.appendData('overcommit_memory',document.getElementById('overcommit_memory').value);
		XHR.appendData('tcp_max_syn_backlog',document.getElementById('tcp_max_syn_backlog').value);
		
		XHR.appendData('fqdncache_size',document.getElementById('fqdncache_size').value);
		XHR.appendData('ipcache_size',document.getElementById('ipcache_size').value);
		XHR.appendData('ipcache_low',document.getElementById('ipcache_low').value);
		XHR.appendData('ipcache_high',document.getElementById('ipcache_high').value);
		
		AnimateDiv('squidperfs'); 
		XHR.sendAndLoad('$page', 'GET',x_SaveSquidPerfs);	
	}		
		
		
		</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function save(){
	
	$squid=new squidbee();
	$squid->global_conf_array["cache_mem"]=$_GET["cache_mem"]." MB";
	$squid->global_conf_array["fqdncache_size"]=$_GET["fqdncache_size"];
	$squid->global_conf_array["ipcache_size"]=$_GET["ipcache_size"];
	$squid->global_conf_array["ipcache_low"]=$_GET["ipcache_low"];
	$squid->global_conf_array["ipcache_high"]=$_GET["ipcache_high"];
	$squid->SaveToLdap();
	
	$sock=new sockets();
	
	$sock->SaveConfigFile( base64_encode(serialize($_GET)),"kernel_values");
	$sock->getFrameWork("cmd.php?sysctl-setvalue={$_GET["swappiness"]}&key=".base64_encode("vm.swappiness")); //15
	$sock->getFrameWork("cmd.php?sysctl-setvalue={$_GET["vfs_cache_pressure"]}&key=".base64_encode("vm.vfs_cache_pressure")); //15
	$sock->getFrameWork("cmd.php?sysctl-setvalue={$_GET["overcommit_memory"]}&key=".base64_encode("vm.overcommit_memory")); //15
	$sock->getFrameWork("cmd.php?sysctl-setvalue={$_GET["tcp_max_syn_backlog"]}&key=".base64_encode("net.ipv4.tcp_max_syn_backlog"));	
	
	
}


	
	
?>