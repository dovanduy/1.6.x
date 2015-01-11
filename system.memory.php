<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}


if(isset($_GET["js"])){echo js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["SwapEnabled"])){SaveSwapAuto();exit;}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{memory_info}');
	$html="
	 YahooWin5('650','$page?popup=yes','$title');
	 
	
	
	";
	
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
	<div class=text-info style='font-size:14px'>{automatic_swap_cleaning_explain}</div>
	<div id='AutoSwapDiv' width=98% class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px'>{DisableSWAPP}:</td>
		<td>". Field_checkbox("DisableSWAPP",1,$DisableSWAPP,"CheckSwap()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td>". Field_checkbox("SwapEnabled",1,$SwapOffOn["SwapEnabled"],"CheckSwap()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{xtimeout}:</td>
		<td style='font-size:16px;'>". Field_text("SwapTimeOut",$SwapOffOn["SwapTimeOut"],"font-size:16px;padding:3px;width:90px")."&nbsp;Mn</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{MaxDiskUsage}:</td>
		<td>". Field_text("SwapMaxPourc",$SwapOffOn["SwapMaxPourc"],"font-size:16px;padding:3px;width:44px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{maxsize}:</td>
		<td>". Field_text("SwapMaxMB",$SwapOffOn["SwapMaxMB"],"font-size:16px;padding:3px;width:90px")."&nbsp;<strong style='font-size:16px'>MB</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSwapAuto()",22)."</td>
	</tr>
	</table>
	</div>
	<p>&nbsp;</p>
	
	<div style='font-size:24px;margin-top:30px'>{automatic_memory_cleaning}</div>	
	<div class=text-info style='font-size:14px'>{automatic_memory_cleaning_explain}</div>	
	<div id='AutoSwapDiv2' width=98% class=form>		
			
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td>". Field_checkbox("AutoMemWatchdog",1,$SwapOffOn["AutoMemWatchdog"],"CheckWatch()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{max_usage}:</td>
		<td style='font-size:16px;'>". Field_text("AutoMemPerc",$SwapOffOn["AutoMemPerc"],"font-size:16px;padding:3px;width:44px")."&nbsp;%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{interval}:</td>
		<td style='font-size:16px;'>". Field_text("AutoMemInterval",$SwapOffOn["AutoMemInterval"],"font-size:16px;padding:3px;width:90px")."&nbsp;{minutes}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSwapAuto()",22)."</td>
	</tr>
	</table>													
	</div>";	
	
	$html="
	<div style='font-size:24px'>{memory_info}</div>
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
	$tpl=new templates();
	if($sys->swap_total>0){
		$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	}
	
	if(is_numeric($pourc)){
		$pourc_swap= pourcentage($pourc);
				
		
	}
	
	
	$prc=round( ($sys->memory_free/$sys->memory_total)*100);
	
	$html="
<div style='width:98%' class=form>
<div style='font-size:20px'>{physical_memory}</div>
". pourcentage($prc)."
<table  style='width:99%'>
	<tr>
	<td style='font-size:14px;text-align:center' width=20%>{total}</strong></td>
	<td style='font-size:14px;text-align:center' width=20%>{used}</strong></td>
	<td style='font-size:14px;text-align:center' width=20%>{free}</strong></td>
	<td style='font-size:14px;text-align:center' width=20%>{shared}</strong></td>
	<td style='font-size:14px;text-align:center' width=20%>{cached}</strong></td>
	</tr>
	<tr>		
	<td style='font-size:14px;text-align:center'>$sys->memory_total Mb</strong></td>	
	<td style='font-size:14px;text-align:center'>$sys->memory_free Mb</strong></td>	
	<td style='font-size:14px;text-align:center'>$sys->memory_used Mb</strong></td>	
	<td style='font-size:14px;text-align:center'>$sys->memory_shared Mb</strong></td>	
	<td style='font-size:14px;text-align:center'>$sys->memory_cached Mb</strong></td>	
	</tr>
</table>
<div style='font-size:20px;margin-top:50px'>{swap_memory}</div>
$pourc_swap
<table  style='width:99%'>
<tr>
	<td style='font-size:14px;text-align:center' width=50%>{total}:</strong></td>
	<td style='font-size:14px;text-align:center' width=50%>{free}:</strong></td>
</tr>
<tr>
<td style='font-size:14px;text-align:center'>$sys->swap_total Mb</strong></td>	
<td style='font-size:14px;text-align:center'>$sys->swap_free Mb</strong></td>	
</tr>
</table></div>";
	
	return $tpl->_ENGINE_parse_body($html);
	
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
	