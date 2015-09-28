<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	

	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	

if(isset($_GET["frontend-mem-used"])){frontend_mem_used();exit;}
if(isset($_GET["frontend-swap-used"])){frontend_swap_used();exit;}
if(isset($_GET["frontend-psmem-used"])){frontend_psmem_used();exit;}

if(isset($_GET["js"])){echo js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["SwapEnabled"])){SaveSwapAuto();exit;}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{memory_info}');
	$html="
	 YahooWin5('990','$page?popup=yes','$title');";
	
	echo $html;
}

function SaveSwapAuto(){
	$sock=new sockets();
	
	$SwapOffOn=unserialize(base64_decode($sock->GET_INFO("SwapOffOn")));
	$sock->SET_INFO("DisableSWAPP", $_POST["DisableSWAPP"]);
	while (list ($num, $line) = each ($_POST) ){
		$SwapOffOn[$num]=$line;
	}
	
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}	
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	
	$sock->SaveConfigFile(base64_encode(serialize($SwapOffOn)),"SwapOffOn");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("system.php?swap-init=yes");
	
	
}

function popup(){
	$table_memory=table_memory();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("system.php?ps-mem=yes");
	$SwapOffOn=unserialize(base64_decode($sock->GET_INFO("SwapOffOn")));
	$DisableSWAPP=$sock->GET_INFO("DisableSWAPP");
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	$table_swap="
	
	
			
			
			
	<div style='font-size:24px;margin-top:30px'>{automatic_swap_cleaning}</div>
	<div class=explain style='font-size:14px'>{automatic_swap_cleaning_explain}</div>
	<div id='AutoSwapDiv' width=98% class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{DisableSWAPP}:</td>
		<td>". Field_checkbox_design("DisableSWAPP",1,$DisableSWAPP,"CheckSwap()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{enable}:</td>
		<td>". Field_checkbox_design("SwapEnabled",1,$SwapOffOn["SwapEnabled"],"CheckSwap()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{xtimeout}:</td>
		<td style='font-size:22px;'>". Field_text("SwapTimeOut",$SwapOffOn["SwapTimeOut"],"font-size:22px;padding:3px;width:90px")."&nbsp;Mn</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{MaxDiskUsage}:</td>
		<td>". Field_text("SwapMaxPourc",$SwapOffOn["SwapMaxPourc"],"font-size:22px;padding:3px;width:44px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{maxsize}:</td>
		<td>". Field_text("SwapMaxMB",$SwapOffOn["SwapMaxMB"],"font-size:22px;padding:3px;width:90px")."&nbsp;<strong style='font-size:22px'>MB</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSwapAuto()",35)."</td>
	</tr>
	</table>
	</div>
	<p>&nbsp;</p>
	
	<div style='font-size:24px;margin-top:30px'>{automatic_memory_cleaning}</div>	
	<div class=explain style='font-size:14px'>{automatic_memory_cleaning_explain}</div>	
	<div id='AutoSwapDiv2' width=98% class=form>		
			
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{enable}:</td>
		<td>". Field_checkbox_design("AutoMemWatchdog",1,$SwapOffOn["AutoMemWatchdog"],"CheckWatch()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{max_usage}:</td>
		<td style='font-size:22px;'>". Field_text("AutoMemPerc",$SwapOffOn["AutoMemPerc"],"font-size:22px;padding:3px;width:44px")."&nbsp;%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{interval}:</td>
		<td style='font-size:22px;'>". Field_text("AutoMemInterval",$SwapOffOn["AutoMemInterval"],"font-size:22px;padding:3px;width:90px")."&nbsp;{minutes}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSwapAuto()",35)."</td>
	</tr>
	</table>													
	</div>";	
	
	$html="
	<div style='font-size:42px;margin-bottom:20px'>{memory_info}</div>
	$table_memory
	$table_swap
	
	<script>
	
	
	var x_SaveSwapAuto= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		Loadjs('$page?js=yes');
		
	}		
	
	function SaveSwapAuto(){
		var XHR=XHRParseElements('AutoSwapDiv');
		DisableSWAPP=0;
		SwapEnabled=0;
		AutoMemWatchdog=0;
		if(document.getElementById('SwapEnabled').checked){SwapEnabled=1;}
		if(document.getElementById('DisableSWAPP').checked){DisableSWAPP=1;}
		if(document.getElementById('AutoMemWatchdog').checked){AutoMemWatchdog=1;}
		var XHR = new XHRConnection();
		XHR.appendData('SwapMaxPourc',document.getElementById('SwapMaxPourc').value);
		XHR.appendData('SwapMaxMB',document.getElementById('SwapMaxMB').value);
		XHR.appendData('SwapTimeOut',document.getElementById('SwapTimeOut').value);
		
		XHR.appendData('AutoMemPerc',document.getElementById('AutoMemPerc').value);
		XHR.appendData('AutoMemInterval',document.getElementById('AutoMemInterval').value);
		
		XHR.appendData('SwapEnabled',SwapEnabled);
		XHR.appendData('DisableSWAPP',DisableSWAPP);
		XHR.appendData('AutoMemWatchdog',AutoMemWatchdog);
		AnimateDiv('AutoSwapDiv');
		XHR.sendAndLoad('$page', 'POST',x_SaveSwapAuto);	
		
	}
	
	function CheckSwap(){
		document.getElementById('SwapEnabled').disabled=true;
		document.getElementById('SwapMaxPourc').disabled=true;
		document.getElementById('SwapMaxMB').disabled=true;
		document.getElementById('SwapTimeOut').disabled=true;
		
		
		if(document.getElementById('DisableSWAPP').checked){return;}
		document.getElementById('SwapEnabled').disabled=false;
		if(!document.getElementById('SwapEnabled').checked){return;}
		document.getElementById('SwapMaxPourc').disabled=false;
		document.getElementById('SwapTimeOut').disabled=false;
		document.getElementById('SwapMaxMB').disabled=false;		
	
	}
	
function CheckWatch(){
	document.getElementById('AutoMemPerc').disabled=true;
	document.getElementById('AutoMemInterval').disabled=true;
	if(!document.getElementById('AutoMemWatchdog').checked){return;}
	document.getElementById('AutoMemPerc').disabled=false;
	document.getElementById('AutoMemInterval').disabled=false;	
	
}
	
	CheckSwap();
	CheckWatch();
	</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


applications_Status();	

function applications_Status(){
	$tpl=new templates();
	

	
	
	$html="
	<div class=form>{memory_info_text}</div>
		<h4>{memory_info}</H4>
		<table style='width:100%'>
		<tr>
		<td valign='top' width=1%><img src='img/bg_memory.jpg'></td>
		<td valign='top'>
		".table_memory()."
		
			</td>
		</tr>
	</table>
	";
	$tpl=new template_users('{memory_info}',$html);
	echo $tpl->web_page;
	
}

function table_memory(){
	$sys=new systeminfos();
	$page=CurrentPageName();
	$tpl=new templates();
	if($sys->swap_total>0){
		$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	}
	
	if(is_numeric($pourc)){
		$pourc_swap= pourcentage($pourc);
				
		
	}
	
	$sys->memory_used=$sys->memory_used-$sys->memory_cached;
	$sys->memory_free=$sys->memory_total-$sys->memory_used;
	$swap_free=$sys->swap_total-$sys->swap_used;
	$prc=round( ($sys->memory_free/$sys->memory_total)*100);
	
	$sock=new sockets();
	

	
	$html="
<div  style='width:98%;margin:5px;padding:5px;border:1px solid #CCCCCC;-webkit-border-radius: 5px 5px 0 0;-moz-border-radius: 5px 5px 0 0;border-radius: 5px 5px 0 0;'>
<table  style='width:100%'>
<tr>
	<td valign='top' style='width:450px'>
	<div style='width:400px;height:400px' id='frontend-mem-used'></div>
<center><table>
	<tr>
	<td style='font-size:22px;text-align:right' width=20%>{total}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->memory_total Mb</strong></td>
	</tr><tr>
	<td style='font-size:22px;text-align:right' width=20%>{used}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->memory_used Mb</strong></td>	
	</tr><tr>
	<td style='font-size:22px;text-align:right' width=20%>{free}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->memory_free Mb</strong></td>	
	</tr><tr>
	<td style='font-size:22px;text-align:right' width=20%>{shared}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->memory_shared Mb</strong></td>
	</tr><tr>
	<td style='font-size:22px;text-align:right' width=20%>{cached}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->memory_cached Mb</strong></td>	
	</tr>
</table>
</center>	
</td>


<td valign='top' style='width:450px'>
<div style='width:400px;height:400px' id='frontend-swap-used'></div>
<center>
<table>
<tr>
	<td style='font-size:22px;text-align:right' width=50%>{total}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->swap_total Mb</strong></td>	
	
</tr>
<tr>
	<td style='font-size:22px;text-align:right' width=50%>{free}:</strong></td>
	<td style='font-size:22px;text-align:left'>$sys->swap_free Mb</strong></td>	
</tr>
</table>
</center>
</td>

<td valign='top' style='width:450px'>
	<div style='width:400px;height:400px' id='frontend-psmem-used'></div>
</td>

</tr>
</table>



</div>
<script>
	Loadjs('$page?frontend-mem-used=yes&U=$sys->memory_used&F=$sys->memory_free');
	Loadjs('$page?frontend-swap-used=yes&U=$sys->swap_used&F=$swap_free');
	Loadjs('$page?frontend-psmem-used=yes');				
</script>	
	";
	
	return $tpl->_ENGINE_parse_body($html);
	
}


function frontend_psmem_used(){
	
	
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ps_mem.array"));
	
	
	
	$highcharts=new highcharts();
	$highcharts->container="frontend-psmem-used";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle=">>";
	$highcharts->PieDataLabels=false;
	$highcharts->PieRedGreen=false;
	$highcharts->Title="{running_processes}";
	$highcharts->LegendSuffix=" MiB";
	echo $highcharts->BuildChart();
	
}


function disk(){
$tpl=new templates();
	$sys=new systeminfos();
	$hash=$sys->DiskUsages();	
	if(!is_array($hash)){return null;}
	$img="<img src='img/fw_bold.gif'>";
	$html="<H4>{disks_usage}:</h4>
	<table style='width:600px' align=center>
	<tr style='background-color:#CCCCCC'>
	<td>&nbsp;</td>
	<td class=legend>{Filesystem}:</strong></td>
	<td class=legend>{size}:</strong></td>
	<td class=legend>{used}:</strong></td>
	<td class=legend>{available}:</strong></td>
	<td align='center'><strong>{use%}:</strong></td>
	<td class=legend>{mounted_on}:</strong></td>
	</tr>
	";
	
	 while (list ($num, $ligne) = each ($hash) ){
	 	$html=$html . "<tr " . CellRollOver().">
	 	<td width=1% class=bottom>$img</td>
	 	<td class=bottom>{$ligne[0]}:</td>
	 	<td class=bottom>{$ligne[2]}:</td>
	 	<td class=bottom>{$ligne[3]}:</td>
	 	<td class=bottom>{$ligne[4]}:</td>
	 	<td align='center' class=bottom><strong>{$ligne[5]}:</strong></td>
	 	<td class=bottom>{$ligne[6]}:</td>
	 	</tr>
	 	";
	 	
	 }
	return $html . "</table>";
	
}
function frontend_mem_used(){

	$PieData["{free}"]=$_GET["F"];
	$PieData["{used}"]=$_GET["U"];
	

	$highcharts=new highcharts();
	$highcharts->container="frontend-mem-used";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle=">>";
	$highcharts->PieDataLabels=false;
	$highcharts->PieRedGreen=true;
	$highcharts->Title="{physical_memory}";
	$highcharts->LegendSuffix=" MB";
	echo $highcharts->BuildChart();
}	

function frontend_swap_used(){
	$PieData["{free}"]=$_GET["F"];
	$PieData["{used}"]=$_GET["U"];
	
	
	$highcharts=new highcharts();
	$highcharts->container="frontend-swap-used";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle=">>";
	$highcharts->PieDataLabels=false;
	$highcharts->PieRedGreen=true;
	$highcharts->Title="{swap_memory}";
	$highcharts->LegendSuffix=" MB";
	echo $highcharts->BuildChart();
	
}