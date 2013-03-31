<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["CacheReplacementPolicy"])){save();exit;}
js();


function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{caches_options}");
	$html="YahooWin4(687,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$CacheReplacementPolicy=$sock->GET_INFO("CacheReplacementPolicy");
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
	$SquidDebugCacheProc=$sock->GET_INFO("SquidDebugCacheProc");
	if(!is_numeric($SquidDebugCacheProc)){$SquidDebugCacheProc=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$squid=new squidbee();
	$t=time();
	$array["lru"]="{cache_lru}";
	$array["heap_GDSF"]="{heap_GDSF}";
	$array["heap_LFUDA"]="{heap_LFUDA}";
	$array["heap_LRU"]="{heap_LRU}";
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size"],$re)){
		$maximum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
		
		
		
	}
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){
		$maximum_object_size_in_memory=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
	
	
	
	}

	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>{DisableAnyCache}:</td>
			<td>". Field_checkbox("DisableAnyCache-$t",1,$DisableAnyCache,"CheckDisableAnyCache$t()")."</td>
		</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{cache_replacement_policy}:</td>
		<td>". Field_array_Hash($array, "CacheReplacementPolicy-$t",$CacheReplacementPolicy,null,null,0,"font-size:14px")."</td>
		<td width=1%>" . help_icon('{cache_replacement_policy_explain}',true)."</td>
	</tr>
	<tr>
			<td style='font-size:14px' class=legend>{maximum_object_size}:</td>
			<td align='left' style='font-size:14px'>" . Field_text("maximum_object_size-$t",$maximum_object_size,'width:90px;font-size:14px')."&nbsp;MB</td>
			<td width=1%>" . help_icon('{maximum_object_size_text}',true)."</td>
	</tr>
	<tr>
			<td style='font-size:14px' class=legend>{maximum_object_size_in_memory}:</td>
			<td align='left' style='font-size:14px'>" . Field_text("maximum_object_size_in_memory-$t",$maximum_object_size_in_memory,'width:90px;font-size:14px')."&nbsp;MB</td>
			<td width=1%>" . help_icon('{maximum_object_size_in_memory_text}',true)."</td>
	</tr>					
	<tr>
			<td style='font-size:14px' class=legend>{debug_cache_processing}:</td>
			<td align='left' style='font-size:14px'>" . Field_checkbox("SquidDebugCacheProc-$t",1,$SquidDebugCacheProc)."</td>
			<td width=1%></td>
	</tr>					
	<tr>
	<td colspan=3 align='right'><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>			
	</table>	
<script>
		var x_Save$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin4Hide();
		}		

		function Save$t(){
			var SquidDebugCacheProc=0;
			var DisableAnyCache=0;
			var XHR = new XHRConnection();
			if(document.getElementById('DisableAnyCache-$t').checked){DisableAnyCache=1;}
			if(document.getElementById('SquidDebugCacheProc-$t').checked){SquidDebugCacheProc=1;}
			XHR.appendData('CacheReplacementPolicy',document.getElementById('CacheReplacementPolicy-$t').value);
			XHR.appendData('maximum_object_size',document.getElementById('maximum_object_size-$t').value);
			XHR.appendData('maximum_object_size_in_memory',document.getElementById('maximum_object_size_in_memory-$t').value);
			XHR.appendData('SquidDebugCacheProc',SquidDebugCacheProc);
			XHR.appendData('DisableAnyCache',DisableAnyCache);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}
		
		function CheckDisableAnyCache$t(){
			document.getElementById('SquidDebugCacheProc-$t').disabled=true;
			document.getElementById('CacheReplacementPolicy-$t').disabled=true;
			document.getElementById('maximum_object_size-$t').disabled=true;
			if(!document.getElementById('DisableAnyCache-$t').checked){
				document.getElementById('SquidDebugCacheProc-$t').disabled=false;
				document.getElementById('CacheReplacementPolicy-$t').disabled=false;
				document.getElementById('maximum_object_size-$t').disabled=false;			
			}
		}
		
		CheckDisableAnyCache$t();
		
</script>				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("CacheReplacementPolicy", $_POST["CacheReplacementPolicy"]);
	$sock->SET_INFO("SquidDebugCacheProc", $_POST["SquidDebugCacheProc"]);
	$sock->SET_INFO("DisableAnyCache", $_POST["DisableAnyCache"]);
	$squid=new squidbee();
	$squid->global_conf_array["maximum_object_size"]=$_POST["maximum_object_size"]." MB";
	$squid->global_conf_array["maximum_object_size_in_memory"]=$_POST["maximum_object_size_in_memory"]." MB";
	$squid->SaveToLdap(true);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{must_restart_proxy_settings}");
	
}

?>
	