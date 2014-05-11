<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["cache_mem"])){Save();exit;}
	
page();
	
function page(){	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$meminfo=unserialize(base64_decode($sock->getFrameWork("system.php?meminfo=yes")));
	$kernel_shmmax=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("kernel.shmmax"));
	$MEMTOTAL=intval($meminfo["MEMTOTAL"]);
	$squid=new squidbee();
	$cache_mem=$squid->global_conf_array["cache_mem"];
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){
		$maximum_object_size_in_memory=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
	}
	$SquidMemoryPools=$sock->GET_INFO("SquidMemoryPools");
	if(!is_numeric($SquidMemoryPools)){$SquidMemoryPools=1;}
	$memory_pools_limit_suffix=null;
	$SquidMemoryPoolsLimit=intval($sock->GET_INFO("SquidMemoryPoolsLimit"));
	
	$html="
	<div style='font-size:22px;margin-bottom:20px'>{server_memory}: ". FormatBytes($meminfo["MEMTOTAL"]/1024)."</div>
	<div class=explain style='font-size:16px'>{squid_cache_memory_explain}</div>
	<div style='margin:10px;padding:10px;width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{central_memory}:</td>
		<td style='font-size:18px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:18px;width:110px")."&nbsp;MB</td>
		<td style='font-size:18px' width=1%>". help_icon("{cache_mem_text}")."</td>
	</tr>			
	<tr>
		<td style='font-size:18px' class=legend>{maximum_object_size_in_memory}:</td>
		<td align='left' style='font-size:18px'>" . Field_text("maximum_object_size_in_memory-$t",$maximum_object_size_in_memory,'width:90px;font-size:16px')."&nbsp;MB</td>
		<td width=1%>" . help_icon('{maximum_object_size_in_memory_text}',true)."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{memory_pools}:</td>
		<td align='left' style='font-size:18px'>" . Field_checkbox("SquidMemoryPools-$t", 1,$SquidMemoryPools,"SquidMemoryPools$t()")."</td>
		<td width=1%>" . help_icon('{memory_pools_explain}',true)."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{memory_pools_limit}:</td>
		<td align='left' style='font-size:18px'>" . Field_text("SquidMemoryPoolsLimit-$t", $SquidMemoryPoolsLimit,"font-size:18px;width:110px")."&nbsp;MB</td>
		<td width=1%>" . help_icon('{memory_pools_limit_explain}',true)."</td>
	</tr>									
</tr>	
	<tr><td colspan=3 style='text-align:right'><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>
</table>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.compile.progress.php?ask=yes');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
	XHR.appendData('maximum_object_size_in_memory',document.getElementById('maximum_object_size_in_memory-$t').value);
	if(document.getElementById('SquidMemoryPools-$t').checked){XHR.appendData('SquidMemoryPools',1);}else{
	XHR.appendData('SquidMemoryPools',0);}
	XHR.appendData('SquidMemoryPoolsLimit',document.getElementById('SquidMemoryPoolsLimit-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function SquidMemoryPools$t(){
	document.getElementById('SquidMemoryPoolsLimit-$t').disabled=true;
	if(document.getElementById('SquidMemoryPools-$t').checked){
		document.getElementById('SquidMemoryPoolsLimit-$t').disabled=false;
	}
}
SquidMemoryPools$t();
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$squid=new squidbee();
	$sock=new sockets();
	
	$sock->SET_INFO("SquidMemoryPoolsLimit",$_POST["SquidMemoryPoolsLimit"]);
	$sock->SET_INFO("SquidMemoryPoolsLimit",$_POST["SquidMemoryPoolsLimit"]);
	
	if(is_numeric($_POST["cache_mem"])){
		$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	}	
	
	$squid->global_conf_array["maximum_object_size_in_memory"]=$_POST["maximum_object_size_in_memory"]." MB";
	$squid->SaveToLdap(true);

}

