<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["picscanval"])){Save();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{nudityScan}");
	$page=CurrentPageName();
	$html="YahooWin3('550','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();	
	$sock=new sockets();
	$SquidNuditScanParams=unserialize(base64_decode($sock->GET_INFO("SquidNudityScanParams")));
	$iPicScanVal=$SquidNuditScanParams['picscanval'];
	$curlTimeOut=$SquidNuditScanParams["curlTimeOut"];
	$CacheSizeItems=$SquidNuditScanParams["CacheSizeItems"];
	$ProcessesNumber=$SquidNuditScanParams["ProcessesNumber"];
	$MemoryDir=$SquidNuditScanParams["MemoryDir"];
	if(!is_numeric($iPicScanRes)){$iPicScanRes=480000;}
	if(!is_numeric($iPicScanVal)){$iPicScanVal=70;}
	if(!is_numeric($curlTimeOut)){$curlTimeOut=10;}
	if(!is_numeric($MemoryDir)){$MemoryDir=0;}
	if(!is_numeric($CacheSizeItems)){$CacheSizeItems=50000;}
	if(!is_numeric($ProcessesNumber)){$ProcessesNumber=30;}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	
	
	for($i=35;$i<101;$i++){
		$iPicScanValAR[$i]="{$i}%";
	}
	
	$html="<div class=text-info style='font-size:14px' id='$t-div'>{SquidNudityScanExplain}
				<div>
					<a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=319','1024','900');\"
				style=\"font-size:14px;font-weight;bold;text-decoration:underline\">{online_help}</a>
			</div>
	</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px' widht=1%>{probability}:</td>
		<td width=99%>". Field_array_Hash($iPicScanValAR, "picscanval-$t",$iPicScanVal,null,'',0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' widht=1% nowrap>{download_timeout}:</td>
		<td width=99% style='font-size:16px' >". Field_text("curlTimeOut-$t",$curlTimeOut,"font-size:16px;width:90px")."&nbsp;{seconds}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px' widht=1%>{memory_directory}:</td>
		<td width=99% style='font-size:16px' >". Field_text("MemoryDir-$t",$MemoryDir,"font-size:16px;width:90px")."&nbsp;MB</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' widht=1% nowrap>{max_processes}:</td>
		<td width=99% style='font-size:16px' >". Field_text("ProcessesNumber-$t",$ProcessesNumber,"font-size:16px;width:90px")."&nbsp;{processes}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px' widht=1%>{cache_items}:</td>
		<td width=99% style='font-size:16px' >". Field_text("CacheSizeItems-$t",$CacheSizeItems,"font-size:16px;width:90px")."&nbsp;{items}</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>".button("{apply}","SavePicScan$t()",18)."</td>
	</tr>
	</table>
	
	
	
	
	<script>
	var x_SavePicScan$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>5){alert('Error:`'+tempvalue.length+'`::'+tempvalue);}
      	YahooWin3Hide();
     	}	

	function SavePicScan$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			XHR.appendData('picscanval',document.getElementById('picscanval-$t').value);
			XHR.appendData('curlTimeOut',document.getElementById('curlTimeOut-$t').value);
			XHR.appendData('MemoryDir',document.getElementById('MemoryDir-$t').value);
			XHR.appendData('CacheSizeItems',document.getElementById('CacheSizeItems-$t').value);
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SavePicScan$t);		
		}
	
	}		
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidNudityScanParams");
	$sock->getFrameWork("cmd.php?squid-restart=yes");
	
}

