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
	if(isset($_POST["SaveCacheMem"])){SaveCacheMem();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["cache-mount"])){caches_mount();exit;}
	if(isset($_POST["HugePages"])){SaveHugePages();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cache_mem}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="YahooWin3('650','$page?tabs=yes','$title');";
	echo $html;
	
	
	
	
}

function caches_mount(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();	
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");

	$users=new usersMenus();
	$meminfo=unserialize(base64_decode($sock->getFrameWork("system.php?meminfo=yes")));
	$MEMTOTAL=intval(($meminfo["MEMTOTAL"]/1024)/1000);
	$MEMTOTAL=$MEMTOTAL-1000;
	
	$EnableSquidCacheBoosters=1;
	
	$SquidCachesMount=unserialize(base64_decode($sock->GET_INFO("SquidCachesMount")));
	if(!is_array($SquidCachesMount)){$EnableSquidCacheBoosters=0;}
	if(count($SquidCachesMount)==0){$EnableSquidCacheBoosters=0;}	
	
	
	
	$t=time();
	$button=button("{apply}","Save$t()",18);
	
	$MEMCPU=round($MEMTOTAL/$CPU_NUMBER);
	$CORP_LICENSE=0;
	if($users->CORP_LICENSE){$CORP_LICENSE=1;}
	if($CORP_LICENSE==1){
		
	}else{
		$button=null;
		$front_error="<br><strong style='color:#E70000'>({license_inactive})</div>";
		$EnableSquidCacheBoosters=0;
	}
	
	
	
	
	$tr[]="
	<tr>
	<td class=legend style='font-size:16px'>{enable}</td>
	<td  style='font-size:16px'>". Field_checkbox("EnableSquidCacheBoosters",1,$EnableSquidCacheBoosters,"CheckEnable$t()")."</td>
	</tr>";	
		
	for($i=1;$i<$CPU_NUMBER+1;$i++){
		if(!is_numeric($SquidCachesMount[$i])){$SquidCachesMount[$i]=$MEMCPU;}
		$tot=$tot+$SquidCachesMount[$i];
		$tr[]="
			<tr>
				<td class=legend style='font-size:16px'>{cpu} $i:&nbsp;</td>
				<td  style='font-size:16px'>". Field_text("CPU-$i-$t",$SquidCachesMount[$i],"font-size:16px;width:110px")."&nbsp;MB</td>
			</tr>	
			";
		
			if($CORP_LICENSE==1){
				$js1[]="XHR.appendData('$i',document.getElementById('CPU-$i-$t').value);";
			}else{
				$js0[]="document.getElementById('CPU-$i-$t').disabled=true";
			}
			
			
			$js3[]="document.getElementById('CPU-$i-$t').disabled=true";
			$js4[]="document.getElementById('CPU-$i-$t').disabled=false";
		
	}
	
	$tot=FormatBytes($tot*1000);
	$html="
	<div class=text-info style='font-size:16px'>{squid_virtual_caches_explain}$front_error</div>
	<div id='$t'></div>		
	<table style='width:99%' class=form>".@implode("\n", $tr)."
			
			
	<tr>
		<td colspan=2 align='right' class=legend style='font-size:16px;font-weight:bold'><hr>{total}:{$tot}<td>
	</tr>			
	<tr>
		<td colspan=2 align='right'><hr>". $button."</td>
	</tr>
	<script>
		var x_SaveCacheMem$t=function (obj) {
		var CORP_LICENSE=$CORP_LICENSE;
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
		RefreshTab('squid_main_svc');
		if(CORP_LICENSE==1){
			Loadjs('squid.restart.php?prepare-js=yes');
		}
	}
	
	
	function Save$t(){
		var CORP_LICENSE=$CORP_LICENSE;
		if(CORP_LICENSE==0){return;}
		var XHR = new XHRConnection();
		var EnableSquidCacheBoosters=0;
		if(document.getElementById('EnableSquidCacheBoosters').checked){EnableSquidCacheBoosters=1;}
		XHR.appendData('EnableSquidCacheBoosters',EnableSquidCacheBoosters);\n".@implode("\n", $js1)."	
		XHR.appendData('SaveCacheMem','yes');\n".@implode("\n", $js1)."	
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem$t);						
				
	}
	
	function CheckEnable$t(){
		var CORP_LICENSE=$CORP_LICENSE;
		if(CORP_LICENSE==0){".@implode("\n", $js3)."\nreturn;}
		if(document.getElementById('EnableSquidCacheBoosters').checked){
			".@implode("\n", $js4)."
		}else{
			".@implode("\n", $js3)."
		}
	}
		
	function Check$t(){
		".@implode("\n", $js0)."	
	}
	Check$t();
	CheckEnable$t();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveCacheMem(){
	$sock=new sockets();
	if($_POST["EnableSquidCacheBoosters"]==0){unset($_POST);}
	
	$sock->SET_INFO("SquidCachesMount", base64_encode(serialize($_POST)));
	
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]="{memory}";
	$array["cache-mount"]="{virtual_caches}";
	$time=time();
	
	while (list ($num, $ligne) = each ($array) ){
	

	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_squid_memory_tabs style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_squid_memory_tabs').tabs();
			});
		</script>";	
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$cache_mem=$squid->global_conf_array["cache_mem"];
	$read_ahead_gap=$squid->global_conf_array["read_ahead_gap"];
	$maximum_object_size_in_memory=$squid->global_conf_array["maximum_object_size_in_memory"];
	
	
	//read_ahead_gap
	
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	if(preg_match("#([0-9]+)\s+#", $read_ahead_gap,$re)){$read_ahead_gap=$re[1];}
	if(preg_match("#([0-9]+)\s+([A-Z]+)#", $maximum_object_size_in_memory,$re)){
		$maximum_object_size_in_memory_value=$re[1];
		$maximum_object_size_in_memory_unit=$re[2];
	}
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
	
	$UNITS["MB"]="MB";
	$UNITS["KB"]="KB";
	$UNITS["G"]="G";
	
	
	
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{server_memory}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["MEMTOTAL"]/1024)."</td>
		<td style='font-size:16px' width=1%></td>
		<td style='font-size:16px' width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px' width=99%>{central_memory}:</td>
		<td style='font-size:16px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:16px;width:65px")."</td>
		<td style='font-size:16px' width=1%>&nbsp;MB</td>
		<td style='font-size:16px' width=1%>". help_icon("{cache_mem_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{read_ahead_gap}:</td>
		<td style='font-size:16px'>". Field_text("read_ahead_gap-$t",$read_ahead_gap,"font-size:16px;width:65px")."</td>
		<td style='font-size:16px' width=1%>&nbsp;MB</td>			
		<td style='font-size:16px' width=1%>". help_icon("{read_ahead_gap_text}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{maximum_object_size_in_memory}:</td>
		<td style='font-size:16px'>". Field_text("maximum_object_size_in_memory-$t",$maximum_object_size_in_memory_value,"font-size:16px;width:65px")."</td>
		<td style='font-size:16px' width=1%>". Field_array_Hash($UNITS, "maximum_object_size_in_memory_unit-$t",$maximum_object_size_in_memory_unit,null,null,0,"font-size:16px;")."</td>			
		<td style='font-size:16px' width=1%>". help_icon("{maximum_object_size_in_memory_text}")."</td>
	</tr>							
	<tr>
		<td colspan=4 align='right'><hr>". button("{apply}","SaveCacheMem()",16)."</td>
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
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGESIZE"]/1024)."</td>
		<td style='font-size:16px' width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{total}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGES_TOTAL"]/1024)."</td>
		<td style='font-size:16px' width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{free}:</td>
		<td style='font-size:16px'>". FormatBytes($meminfo["HUGEPAGES_FREE"]/1024)."</td>
		<td style='font-size:16px' width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>kernel shmmax:</td>
		<td style='font-size:16px'>". FormatBytes($kernel_shmmax/1024)." <strong style='font-size:11px'><b>{proposal}:</b>$kernel_shmmax_proposal</strong></td>
		<td style='font-size:16px' width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>HugePages:</td>
		<td style='font-size:16px'>". FormatBytes($nr_hugepages/1024)." <strong style='font-size:11px'><b>{proposal}:</b>$nr_hugepages_proposal</strong></td>
		<td style='font-size:16px' width=1%></td>
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
		XHR.appendData('read_ahead_gap',document.getElementById('read_ahead_gap-$t').value);
		var maximum_object_size_in_memory=document.getElementById('maximum_object_size_in_memory-$t').value;
		var maximum_object_size_in_memory_unit=document.getElementById('maximum_object_size_in_memory_unit-$t').value;
		XHR.appendData('maximum_object_size_in_memory',maximum_object_size_in_memory+' '+maximum_object_size_in_memory_unit);
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
function save(){
	$squid=new squidbee();
	$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	$squid->global_conf_array["read_ahead_gap"]=trim($_POST["read_ahead_gap"])." MB";
	$squid->global_conf_array["maximum_object_size_in_memory"]=trim($_POST["maximum_object_size_in_memory"]);
	$squid->SaveToLdap(true);
}

function SaveHugePages(){
	$sock=new sockets();
	$sock->SET_INFO("HugePages",$_POST["HugePages"]);
	$sock->SET_INFO("KernelShmmax",$_POST["KernelShmmax"]);	
	$sock->getFrameWork("system.php?HugePages=yes");
	
}
