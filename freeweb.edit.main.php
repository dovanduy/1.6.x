<?php
	if(isset($_POST["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.system.network.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["webservice"])){webservice();exit;}
	if(isset($_GET["mysql"])){mysql_settings();exit;}
	if(isset($_POST["SAVE_FREEWEB_MAIN"])){SAVE_FREEWEB_MAIN();exit;}
	if(isset($_POST["SAVE_FREEWEB_MYSQL"])){SAVE_FREEWEB_MYSQL();exit;}
	if(isset($_GET["mysql-toolbox"])){mysql_toolbox();exit;}
	
	

tabs();
function tabs(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$OnlyWebSite=false;
	$sock=new sockets();
	$remove_sql=false;
	if($_GET["servername"]<>null){
		$apache=new vhosts();
		$free=new freeweb($_GET["servername"]);
		if($free->groupware =="ZARAFA"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="Z-PUSH"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="ZARAFA_MOBILE"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="WEBAPP"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="KLMS"){$remove_sql=true;$OnlyWebSite=true;}		
		if($free->groupware=="MAILMAN"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="ARTICA_MINIADM"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="ARTICA_PRXYLOGS"){$remove_sql=true;$OnlyWebSite=true;}
		if($free->groupware=="MAILHOSTING"){$remove_sql=true;$OnlyWebSite=true;}			
		if($apache->noneeduser_mysql[$free->groupware]){$remove_sql=true;}	
	}
	

	
	
	
	$array["webservice"]='{webservice}';
	if($_GET["servername"]<>null){
		if(!$remove_sql){$array["mysql"]='MySQL/FTP';}
	}
	
	if($_GET["servername"]==null){unset($array["mysql"]);}
	
	
	if( ($free->groupware=="ZARAFA") OR ($free->groupware=="WEBAPP")){
		$array["ZARAFA"]='{APP_ZARAFA}';
	}	
	
	if($_GET["servername"]<>null){
		if($free->NginxFrontEnd==0){
			$array["php_values"]='{php_values}';
		}
	}
	
	if(!$OnlyWebSite){
		if($_GET["servername"]<>null){
			$array["add-content"]='{content_extension}';
		}
		
	}
	
	
	if(count($array)<10){$fontsize="style='font-size:18px'";}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="ZARAFA"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.zarafa.php?servername={$_GET["servername"]}&freewebs=1&group_id={$_REQUEST["group_id"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="php_values"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.php-values.php?servername={$_GET["servername"]}&freewebs=1&group_id={$_REQUEST["group_id"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="add-content"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.edit.content.php?servername={$_GET["servername"]}&freewebs=1&group_id={$_REQUEST["group_id"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}		
				
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&servername={$_GET["servername"]}&freewebs=1&group_id={$_REQUEST["group_id"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&force-groupware={$_GET["force-groupware"]}&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
		
		
	}
	
	
	echo build_artica_tabs($html,"main_config_freewebMains");

		
	
	
}
function countloops(){
	
	$q=new mysql();
	$sql="SELECT count(*) as tcount FROM loop_disks";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if($ligne["tcount"]==null){$ligne["tcount"]=0;}
	return $ligne["tcount"];
}

function webservice(){
	
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$APACHE_PROXY_MODE=0;
	$DNS_INSTALLED=false;
	$remove_sql=false;
	$OnlyWebSite=false;
	$countloops=countloops();
	$no_usersameftpuser=$tpl->javascript_parse_text("{no_usersameftpuser}");
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if(!is_numeric($_GET["t"])){$_GET["t"]=0;}
	$error_field_max_length=$tpl->javascript_parse_text("{error_field_max_length}");
	$error_please_fill_field=$tpl->javascript_parse_text("{error_please_fill_field}");
	$acl_dstdomain=$tpl->javascript_parse_text("{acl_dstdomain}");
	$mysql_database=$tpl->javascript_parse_text("{mysql_database}");
	$username=$tpl->javascript_parse_text("{username}");
	$password=$tpl->javascript_parse_text("{password}");
	$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
	$checkboxes=1;
	$ButtonName="{apply}";
	if($ligne["groupware"]=="cachemgr"){$checkboxes=0;}
	$users=new usersMenus();
	$PUREFTP_INSTALLED=1;
	if(!$users->PUREFTP_INSTALLED){$PUREFTP_INSTALLED=0;}
	$ServerIPVAL=trim($ligne["ServerIP"]);
	$ServerPort=trim($ligne["ServerPort"]);
	$sslcertificate=$ligne["sslcertificate"];
	
	$UseDefaultPort=0;
	if($users->APACHE_PROXY_MODE){$APACHE_PROXY_MODE=1;}
	$parcourir_domaines=button("{browse}...","Loadjs('browse.domains.php?field=domainname')",12);
	if($users->dnsmasq_installed){$DNS_INSTALLED=true;}
	if($users->POWER_DNS_INSTALLED){$DNS_INSTALLED=true;}
	$FreeWebDisableSSL=trim($sock->GET_INFO("FreeWebDisableSSL"));
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}	
	$check_configuration=$tpl->_ENGINE_parse_body("{check_configuration}");	
	$webservice=$tpl->_ENGINE_parse_body("{webservice}");	
	$ServerIPVAL="{$ServerIPVAL}:$ServerPort";
	$freeweb=new freeweb($_GET["servername"]);
	
	$acl_dstdomain_label=$tpl->_ENGINE_parse_body("{acl_dstdomain}");
	
	
	
		
	
	
	
	
	//HTTTRACK ---------------------------------------------------------
	$WebCopyCount=$q->COUNT_ROWS("httrack_sites", "artica_backup");
	if($WebCopyCount>0){
		$sql="SELECT ID,sitename FROM httrack_sites";
		$results_webcopy = $q->QUERY_SQL($sql,"artica_backup");
		$WebCopyHash[0]="{none}";
		while ($ligneWebCopy = mysql_fetch_assoc($results_webcopy)) {
			
			$WebCopyHash[$ligneWebCopy["ID"]]=$ligneWebCopy["sitename"];
		}
		
		$WebCopyTR="<tr>
				<td class=legend nowrap style='font-size:18px'>WebCopy:</td>
				<td>". Field_array_Hash($WebCopyHash, "WebCopyID",$freeweb->WebCopyID,"style:font-size:18px")."</td>
				<td>". help_icon("freeweb_WebCopy_explain")."</td>
			</tr>";			
			
		
	}
	
	
	
	
	if($ligne["groupware"]=="SUGAR"){$additional_infos=sugard_additional_infos();}
	
	if($vgservices["freewebs"]<>null){
		if(!is_numeric($ligne["lvm_size"])){$ligne["lvm_size"]=5000;}
		if($ligne["lvm_vg"]==null){$ligne["lvm_vg"]=$vgservices["freewebs"];}
		$sizelimit="
		<tr>
		<td class=legend style='font-size:18px'>{size}:</td>
		<td style='font-size:14px;'>". Field_text("vg_size",$ligne["lvm_size"],"font-size:18px;padding:3px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
		</tr>";
		
	}
	
	$groupwarelink=$freeweb->groupwares_InstallLink();
	$groupwares_textintro=$freeweb->groupwares_textintro();
	if($groupwarelink<>null){
		
		$explain="
		<div class=explain>$groupwares_textintro:<br><strong style='font-size:14px'>
			<a href=\"javascript:blur()\" OnClick=\"javascript:s_PopUpFull('$groupwarelink',1024,768)\" style='text-decoration:underline;font-weight:bold;color:#969696'>$groupwarelink</a></strong></div>		
		";
		
	}
	
	$img="website-64.png";
	
	if($_GET["force-groupware"]<>null){
		$vhosts=new vhosts();
		$img=$vhosts->IMG_ARRAY_64[$_GET["force-groupware"]];
		$imgtitle="<div style='font-size:14px;font-weight:bold'>{".$vhosts->TEXT_ARRAY[$_GET["force-groupware"]]["TITLE"]."}</div>";
		if($_GET["force-groupware"]=="ZARAFA"){$remove_sql=true;$OnlyWebSite=true;}
		if($_GET["force-groupware"]=="Z-PUSH"){$remove_sql=true;$OnlyWebSite=true;}
		if($_GET["force-groupware"]=="ZARAFA_MOBILE"){$remove_sql=true;$OnlyWebSite=true;}
		if($_GET["force-groupware"]=="ROUNDCUBE"){$OnlyWebSite=true;}
		if($_GET["force-groupware"]=="KLMS"){$OnlyWebSite=true;$remove_sql=true;}
		
	
	}
	
	if($_GET["servername"]==null){$ButtonName="{add}";}
	
	
	
	
		if($ligne["domainname"]==null){
			$dda=explode(".",$ligne["servername"]);
			$hostname=$dda[0];
			unset($dda[0]);
			$domainname=@implode(".",$dda);
		}else{
			$ff=explode(".",$ligne["servername"]);
			if(count($ff)>2){
				$hostname=str_replace(".{$ligne["domainname"]}","",$ligne["servername"]);
			}else{
				$hostname=null;
			}
			$domainname=$ligne["domainname"];
			$parcourir_domaines=null;
		}
		
	if($hostname=="_default_"){$parcourir_domaines=null;}
	
	if($DNS_INSTALLED){
		include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
		include_once(dirname(__FILE__)."/ressources/class.pdns.inc");
		$pdns=new pdns();
		if($ligne["servername"]==null){
			$ip=new networking();
			$ips=$ip->ALL_IPS_GET_ARRAY();
			$ips[null]="{none}";
			$dns_field="<tr>
				<td class=legend nowrap style='font-size:18px'>{dns_entry}:</td>
				<td>". Field_array_Hash($ips, "ADD_DNS_ENTRY",null,"style:font-size:18px")."</td>
				<td>". help_icon("freeweb_add_dns_entry_explain")."</td>
			</tr>";
		}else{
		
		$hostip=$pdns->GetIp($ligne["servername"]);
		if($hostip<>null){
		$dns_field="<tr>
				<td class=legend nowrap style='font-size:18px'>{dns_entry}:</td>
				<td style='font-size:14px'>$hostip</td>
				<td>&nbsp;</td>
			</tr>";	
			
		}else{
			$ip=new networking();
			$ips=$ip->ALL_IPS_GET_ARRAY();
			$ips[null]="{none}";
			$dns_field="<tr>
				<td class=legend nowrap style='font-size:18px'>{dns_entry}:</td>
				<td>". Field_array_Hash($ips, "ADD_DNS_ENTRY",null,"style:font-size:18px")."</td>
				<td>". help_icon("freeweb_add_dns_entry_explain")."</td>
			</tr>";			
			
		}
		
		}
		
		
	}
	
	
	$domain="<table style='width:100%'>
		<tr>
			<td>".Field_text("servername",$hostname,"font-size:18px;padding:3px;font-weight:bold;width:90px")."</td>
			<td style='font-size:18px' align='center' width=1%>&nbsp;.&nbsp;</td>
			<td>".Field_text("domainname",$domainname,"font-size:18px;padding:3px;width:220px;font-weight:bold")."</td>
			<td>$parcourir_domaines</td>
		</tr>
		</table>";
	
	if(!$users->AsSystemAdministrator){
		if($ligne["domainname"]==null){
			$dd=explode(".",$ligne["servername"]);
			$hostname=$dd[0];
			unset($dd[0]);
			$domainname=@implode(".",$dd);
		}else{
			$ff=explode(".",$ligne["servername"]);
			if(count($ff)>2){
				$hostname=str_replace(".{$ligne["domainname"]}","",$ligne["servername"]);
			}else{
				$hostname=null;
			}
			
			$domainname=$ligne["domainname"];
		}

		$ldap=new clladp();
		$domains=$ldap->Hash_domains_table($_SESSION["ou"]);
		while (list ($a, $b) = each ($domains) ){$c[$a]=$a;}
		
		$domain="
		<table style='width:100%'>
		<tr>
			<td>".Field_text("servername",$hostname,"font-size:18px;padding:3px;font-weight:bold;width:90px")."</td>
			<td style='font-size:18px' align='center' width=1%>&nbsp;.&nbsp;</td>
			<td>". Field_array_Hash($c,"domainname",$domainname,"style:font-size:18px;padding:3px;font-weight:bold;width:220px;")."</td>
		</tr>
		</table>";
		
	}
	$NewServer=0;
	
	if(trim($ligne["servername"]==null)){$NewServer=1;}
	
	if($NewServer==0){
		$domain="<div style='font-size:22px'>{$ligne["servername"]}</div>
			<input type='hidden' value='{$ligne["servername"]}' id='servername'>
			<input type='hidden' value='{$ligne["domainname"]}' id='domainname'>";
	}
	

	
	
	if($ligne["groupware"]<>null){
		$apache=new vhosts();
		$img=$apache->IMG_ARRAY_64[$ligne["groupware"]];
		if($ligne["groupware"]=="ROUNDCUBE"){$OnlyWebSite=true;}		
		if($ligne["ForceInstanceZarafaID"]>0){$_GET["ForceInstanceZarafaID"]=$ligne["ForceInstanceZarafaID"];}
	}
	
	
	
	
	
	if($OnlyWebSite){$js_OnlyWebSite="OnlyWebsite()";}
	
	
	$uid_uri=urlencode(base64_encode($ligne["uid"]));
	$nets=unserialize(base64_decode($sock->GET_INFO("FreeWebsApacheListenTable")));
	$znets[null]="{default}";
	while (list($num,$ip)=each($nets)){$znets[$num]=$num;}
	
	
	$ServerIP=Field_array_Hash($znets,'ServerIP',$ServerIPVAL,null,null,0,'font-size:14px;');	
	
	
	$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
	$q=new mysql();
	$sslcertificates[null]="{default}";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"];
	}	
	$sslcertificateF=Field_array_Hash($sslcertificates,"sslcertificate", $sslcertificate,"style:font-size:14px");
	$t=time();
	$html="
	<div id='freewebdiv-$t'></div>
	<input type='hidden' id='force-groupware' name ='force-groupware' value='{$_GET["force-groupware"]}'>
	<table style='width:100%' class=TableRemove>
	<tr>
		<td valign='top' width=1%>
			<center>
				<img src='img/$img'>$imgtitle
			</center><br>
			<div style='width:190px'>
			
			</div>
		</td>
		<td valign='top' width=99%>
	$explain
	<div id='freewebdiv'>
		<div id='block1' style='display:block;'>
			<div style='width:98%' class=form>
			<table>
			<tr> 
				<td class=legend nowrap style='font-size:18px'>$acl_dstdomain_label:</td>
				<td colspan=2>$domain</td>
			</tr>
			<tr> 
				<td class=legend nowrap style='font-size:18px'>{aliases}:</td>
				<td colspan=2><span id='webserver-aliases'></span></td>
			</tr>
			<tr> 
				<td class=legend nowrap style='font-size:18px'>{listen_address}:</td>
				<td colspan=2>$ServerIP</td>
			</tr>					
			$dns_field
			$WebCopyTR	
			
			
			<tr> 
				<td class=legend nowrap style='font-size:18px'>{directory}:</td>
				<td>". Field_text("www_dir",$ligne["www_dir"],"font-size:18px;padding:3px;")."</td>
				<td>". button_browse("www_dir")."</td>
			</tr>			
			<tr> 
				<td class=legend nowrap style='font-size:18px'>{reverse_proxy}:</td>
				<td width=1%>". Field_checkbox("UseReverseProxy", 1,$ligne["UseReverseProxy"],"CheckUseReverseProxy()")."</td>
			</tr>		
			
			$sizelimit
			<tr>
				<td class=legend nowrap style='font-size:18px'>{UseLoopDisk}:</td>
				<td>". Field_checkbox("UseLoopDisk",1,$ligne["UseLoopDisk"],"CheckLoops()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr style='height:auto'>
				<td>&nbsp;</td>
				<td colspan=2 style='height:auto'><span id='loops-list'></span></td></tr>		
			<tr>
				<td class=legend style='font-size:18px'>{member}:</td>
				<td>". Field_text("www_uid",$ligne["uid"],"font-size:18px;padding:3px;")."</td>
				<td><span id='bb_button'>". button("{browse}...","Loadjs('user.browse.php?field=www_uid&YahooWin=6')",12)."</span>
				<span id='status-uid-www' style='float:right'></span></td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{group}:</td>
				<td>". Field_text("www_group",$ligne["gpid"],"font-size:18px;padding:3px;")."</td>
				<td><span id='bb_button1'>". button("{browse}...","Loadjs('MembersBrowse.php?field-user=www_group&OnlyGroups=1&OnlyGUID=1')",12)."</span>
					<span id='status-gpid-www' style='float:right'></span>
				</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:18px'>{ssl}:</td>
				<td>". Field_checkbox("useSSL",1,$ligne["useSSL"],"useSSLCheckCOnf()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{certificate}:</td>
				<td>$sslcertificateF</td>
				<td>&nbsp;</td>
			</tr>			
			
			<tr> 
				<td class=legend nowrap style='font-size:18px'>{www_forward}:</td>
				<td width=1%>". Field_checkbox("Forwarder", 1,$ligne["Forwarder"],"CheckForwarder()")."</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:18px'>{www_ForwardTo}:</td>
				<td>". Field_text("ForwardTo",$ligne["ForwardTo"],"width:270px;font-size:18px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>
		</table>
		</div>
		</div>	
	</div>	
	</div>
	
	<div style='width:100%;text-align:right'><hr>". button("$ButtonName","SaveFreeWebMain()",26)."</div>



	
	
	</td>
	</tr>
	</table>
	
	$additional_infos
<script>

	function CheckDatas(){
		var APACHE_PROXY_MODE=$APACHE_PROXY_MODE;
		var FreeWebDisableSSL=$FreeWebDisableSSL;
		 
		
		if(APACHE_PROXY_MODE==0){
			document.getElementById('UseReverseProxy').checked=false;
			document.getElementById('UseReverseProxy').disabled=true;
		}
		
		var x=document.getElementById('servername').value;
		var z=document.getElementById('domainname').value;
		var w=x.length+z.length;
		if(w>0){
			document.getElementById('servername').disabled=true;
			document.getElementById('domainname').disabled=true;
			}
		
		if(FreeWebDisableSSL==1){
			document.getElementById('useSSL').disabled=true;
		}
		
	}
	

	

	
	function CheckForwarder(){
		if(document.getElementById('Forwarder').checked){
			document.getElementById('UseReverseProxy').disabled=true;
			document.getElementById('UseLoopDisk').disabled=true;
			document.getElementById('ForwardTo').disabled=false;
			
			
		}else{
			document.getElementById('UseReverseProxy').disabled=false;
			document.getElementById('UseLoopDisk').disabled=false;
			document.getElementById('ForwardTo').disabled=true;
			CheckLoops();
		}
	
	}
		
	function FreeWebsRebuildvHosts(){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsRebuildvHosts','{$_GET["servername"]}');
		AnimateDiv('freewebdiv');
		XHR.sendAndLoad('freeweb.edit.php', 'POST',x_SaveFreeWebMain$t);
	}
	
	function FreeWebsRebuildGroupware(){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsRebuildGroupware','{$_GET["servername"]}');
		AnimateDiv('freewebdiv');
		XHR.sendAndLoad('freeweb.edit.php', 'POST',x_SaveFreeWebMain$t);
	}			


	var x_SaveFreeWebMain$t=function (obj) {
		    var NewServer=$NewServer;
			var results=obj.responseText;
			document.getElementById('freewebdiv-$t').innerHTML='';
			if(results.length>0){alert(results);return;}
			var t={$_GET["t"]};	
			if(t>0){
				$('#freewebs-table-{$_GET["t"]}').flexReload();
			}else{
				if(document.getElementById('container-www-tabs')){RefreshTab('container-www-tabs');}
				if(document.getElementById('main_config_freeweb')){FreeWebsRefreshWebServersList();}
			}
			if(NewServer==1){
				YahooWin5Hide();
				ExecuteByClassName('SearchFunction');
				return;
			}
			if(document.getElementById('main_config_freewebMains')){RefreshTab('main_config_freewebMains');}
			ExecuteByClassName('SearchFunction');
		}	
		
		function SaveFreeWebMain(){
			var NewServer=$NewServer;
			
			var XHR = new XHRConnection();
			XHR.appendData('SAVE_FREEWEB_MAIN','yes');
			
			if(NewServer==1){
				var sitename=document.getElementById('servername').value;
				var www_a=document.getElementById('domainname').value;
				var www_b=document.getElementById('servername').value;
				var www_t=www_a.length+www_b.length;
				if(www_t<2){
					alert('$error_please_fill_field:$acl_dstdomain'); 
					return;
				}
			
				if(sitename!=='_default_'){
					var x=document.getElementById('domainname').value;
					if(x.length==0){alert('$error_please_fill_field:$acl_dstdomain');return;}
				}else{
					document.getElementById('domainname').value='';
				}
			}
			if(document.getElementById('ADD_DNS_ENTRY')){
				XHR.appendData('ADD_DNS_ENTRY',document.getElementById('ADD_DNS_ENTRY').value);
			}
			
			if(document.getElementById('useSSL').checked){XHR.appendData('useSSL',1);}else{XHR.appendData('useSSL',0);}
			
			if(document.getElementById('WebCopyID')){
				XHR.appendData('WebCopyID',document.getElementById('WebCopyID').value);
			}
			if(document.getElementById('www_dir')){
				XHR.appendData('www_dir',document.getElementById('www_dir').value);
			}			
			
			
			
			XHR.appendData('UseDefaultPort',0)
			if(document.getElementById('UseReverseProxy').checked){XHR.appendData('UseReverseProxy',1);}else{XHR.appendData('UseReverseProxy',0);}
			if(document.getElementById('Forwarder').checked){XHR.appendData('Forwarder',1);}else{XHR.appendData('Forwarder',0);}
			XHR.appendData('ForceInstanceZarafaID','{$_GET["ForceInstanceZarafaID"]}');
			
			
			if(document.getElementById('LoopMounts')){
				var LoopMounts=document.getElementById('LoopMounts').value;
				if(LoopMounts.length>3){
					if(document.getElementById('UseLoopDisk').checked){XHR.appendData('UseLoopDisk',1);}else{XHR.appendData('UseLoopDisk',0);}
					XHR.appendData('LoopMounts',LoopMounts);
				}
			
			}
			
			
			
			var uid=trim(document.getElementById('www_uid').value);
			
			
			
			if(document.getElementById('vg_size')){XHR.appendData('vg_size',document.getElementById('vg_size').value);}
			XHR.appendData('lvm_vg','{$ligne["lvm_vg"]}');
			if(NewServer==1){
				if(sitename!=='_default_'){
					var www_b=document.getElementById('domainname').value;
					var www_a=document.getElementById('servername').value;
					if(www_a.length>0){XHR.appendData('servername',www_a+'.'+www_b);}else{XHR.appendData('servername',www_b);}
    				}else{
    				XHR.appendData('servername','_default_');
    			}
    		}
    		if(NewServer==0){XHR.appendData('servername',document.getElementById('servername').value);}
    		XHR.appendData('domainname',document.getElementById('domainname').value);
    		XHR.appendData('uid',uid);
    		XHR.appendData('gpid',document.getElementById('www_group').value);
    		XHR.appendData('ForwardTo',document.getElementById('ForwardTo').value);
    		XHR.appendData('force-groupware',document.getElementById('force-groupware').value);
    		XHR.appendData('ServerIP',document.getElementById('ServerIP').value);
    		XHR.appendData('sslcertificate',document.getElementById('sslcertificate').value);
    		
    		
    		
    		AnimateDiv('freewebdiv-$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveFreeWebMain$t);
			
		}	
		
	function CheckLoops(){
		var countloops=$countloops;
		document.getElementById('UseLoopDisk').disabled=true;
		if(countloops>0){
			document.getElementById('UseLoopDisk').disabled=false;
		}
		document.getElementById('loops-list').innerHTML='';
		
		if(document.getElementById('UseLoopDisk').checked){
			if(document.getElementById('vg_size')){
				document.getElementById('vg_size').disabled=true;
			}
			LoadAjax('loops-list','$page?loops-list=yes&servername={$ligne["servername"]}');
		}
	}
	
	

	
		
	function CheckLoops(){
		var countloops=$countloops;
		document.getElementById('UseLoopDisk').disabled=true;
		if(countloops>0){document.getElementById('UseLoopDisk').disabled=false;}
		document.getElementById('loops-list').innerHTML='';
		
		if(document.getElementById('UseLoopDisk').checked){
			if(document.getElementById('vg_size')){
				document.getElementById('vg_size').disabled=true;
			}
			LoadAjax('loops-list','$page?loops-list=yes&servername={$ligne["servername"]}');
		}
	}
	
	function CheckUseReverseProxy(){
		CheckDatas();
		useMysqlCheck();
		CheckLoops();
		$js_removesql;
		$js_OnlyWebSite;
	}
	

	
	function OnlyWebsite(){
		if(document.getElementById('Forwarder')){document.getElementById('Forwarder').disabled=true;}
		if(document.getElementById('UseReverseProxy')){document.getElementById('UseReverseProxy').disabled=true;}
		if(document.getElementById('useFTP')){document.getElementById('useFTP').disabled=true;}
	}
	
	function CheckUId(){
		LoadAjaxTiny('status-uid-www','freeweb.edit.php?uid-check=$uid_uri');
	}
	
	function WebServerAliasesRefresh(){
		LoadAjaxTiny('webserver-aliases','freeweb.edit.php?webserver-aliases=yes&servername={$ligne["servername"]}');
	}
	
	function useSSLCheckCOnf(){
		document.getElementById('sslcertificate').disabled=true;
			if(document.getElementById('useSSL').checked){
				document.getElementById('sslcertificate').disabled=false;
			}
	}
	
	CheckDatas();
	CheckLoops();
	CheckForwarder();
	CheckUId();
	$js_removesql;
	$js_OnlyWebSite;
	WebServerAliasesRefresh();
	useSSLCheckCOnf();
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function mysql_settings(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$APACHE_PROXY_MODE=0;
	$DNS_INSTALLED=false;
	$remove_sql=false;
	$OnlyWebSite=false;
	$countloops=countloops();
	
	$no_usersameftpuser=$tpl->javascript_parse_text("{no_usersameftpuser}");
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$error_field_max_length=$tpl->javascript_parse_text("{error_field_max_length}");
	$error_please_fill_field=$tpl->javascript_parse_text("{error_please_fill_field}");
	$acl_dstdomain=$tpl->javascript_parse_text("{acl_dstdomain}");
	$mysql_database=$tpl->javascript_parse_text("{mysql_database}");
	$username=$tpl->javascript_parse_text("{username}");
	$password=$tpl->javascript_parse_text("{password}");
	$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
	$uid=$ligne["uid"];
	$checkboxes=1;
	$ButtonName="{apply}";
	if($ligne["groupware"]=="cachemgr"){$checkboxes=0;}
	$users=new usersMenus();
	$PUREFTP_INSTALLED=1;
	if(!$users->PUREFTP_INSTALLED){$PUREFTP_INSTALLED=0;}
	$ServerIPVAL=trim($ligne["ServerIP"]);
	$ServerPort=trim($ligne["ServerPort"]);
	$UseDefaultPort=0;
	if($users->APACHE_PROXY_MODE){$APACHE_PROXY_MODE=1;}
	$parcourir_domaines="<input type='button' OnClick=\"javascript:Loadjs('browse.domains.php?field=domainname')\" value='{browse}...'>";
	if($users->dnsmasq_installed){$DNS_INSTALLED=true;}
	if($users->POWER_DNS_INSTALLED){$DNS_INSTALLED=true;}
	$FreeWebDisableSSL=trim($sock->GET_INFO("FreeWebDisableSSL"));
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}	
	
	$webservice=$tpl->_ENGINE_parse_body("{webservice}");	
	$ServerIPVAL="{$ServerIPVAL}:$ServerPort";	
	
	$t=time();
	if($ligne["mysql_database"]<>null){
		$DatabaseText=$ligne["mysql_database"];
	}
	
$html="	
	<div id='$t'></div>
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1%><div id='$t-tool'></div></td>
	<td valign='top' width=99%>
			<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:14px'>{useMySQL}:</td>
				<td>". Field_checkbox("useMysql",1,$ligne["useMysql"],"useMysqlCheck()")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{mysql_instance}:</td>
				<td><div id='freeweb-mysql-instances'></div></td>
				<td align='left'>". imgtootltip("plus-24.png","{add}:{mysql_instance}","Loadjs('mysql.multi.php?mysql-server-js=yes&ID=');")."</td>
			</tr>	
			
			<tr>
				<td class=legend style='font-size:14px'>{mysql_database_name}:</td>
				<td>". Field_text("mysql_database",$ligne["mysql_database"],"width:150px;font-size:14px;padding:3px")."&nbsp;<span style='font-size:11px'>$DatabaseText</span></td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{mysql_username}:</td>
				<td>". Field_text("mysql_username",$ligne["mysql_username"],"width:120px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{password}:</td>
				<td>". Field_password("mysql_password",$ligne["mysql_password"],"width:90px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan=3><span style='font-size:16px'>{ftp_access}<hr style='border-color:005447'></td>
			</tr>	
			
			
			<tr>
				<td class=legend style='font-size:14px'>{allowftp_access}:</td>
				<td>". Field_checkbox("useFTP",1,$ligne["useFTP"],"useMysqlCheck()")."</td>
				<td>&nbsp;</td>
			</tr>	
			
			<tr>
				<td class=legend style='font-size:14px'>{ftp_user}:</td>
				<td>". Field_text("ftpuser",$ligne["ftpuser"],"width:120px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{password}:</td>
				<td>". Field_password("ftppassword",$ligne["ftppassword"],"width:90px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}","SaveFreeWebMySQL()",16)."</td>
			</tr>
			
			
			</table>
		</td>
	</tr>
	</table>
	
	<script>
	
	var x_SaveFreeWebMain$t=function (obj) {
		   	document.getElementById('$t').innerHTML='';
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}			
			
		}		
	
	function SaveFreeWebMySQL(){
		var uid='$uid';
		var XHR = new XHRConnection();
		var ftpuser=trim(document.getElementById('ftpuser').value);
		
		if(document.getElementById('useMysql').checked){XHR.appendData('useMysql',1);}else{XHR.appendData('useMysql',0);}
		if(document.getElementById('useMysql').checked){
			var mysql_database=document.getElementById('mysql_database').value;
			if(mysql_database.length==0){
				alert('$error_please_fill_field:$mysql_database');
				return;						
			}	
			var x=document.getElementById('mysql_password').value;
				if(x.length==0){
					alert('$error_please_fill_field:$mysql_database/$password');
					return;
				}	
			var x=document.getElementById('mysql_username').value;
				if(x.length==0){
					alert('$error_please_fill_field:$mysql_database/$username');
					return;
				}
				
			if(mysql_database.length>16){
				alert('mysql_database:$error_field_max_length: 16');
				document.getElementById('mysql_database').disabled=false;
				return;
			}
		}
		
			
		
			if(document.getElementById('useFTP').checked){
				if(ftpuser.length==0){document.getElementById('useFTP').checked=false;}
			}
		
			if(document.getElementById('useFTP').checked){	
				if(uid==ftpuser){
					alert('$no_usersameftpuser');
					return;
				}
			}
			XHR.appendData('SAVE_FREEWEB_MYSQL','yes');
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.appendData('mysql_instance_id',document.getElementById('mysql_instance_id').value);
    		XHR.appendData('mysql_database',document.getElementById('mysql_database').value);
    		XHR.appendData('mysql_password',document.getElementById('mysql_password').value);
    		XHR.appendData('mysql_username',document.getElementById('mysql_username').value);
    		XHR.appendData('mysql_instance_id',document.getElementById('mysql_instance_id').value);
    		if(document.getElementById('useFTP').checked){XHR.appendData('useFTP',1);}else{XHR.appendData('useFTP',0);}
    		XHR.appendData('ftpuser',ftpuser);
    		XHR.appendData('ftppassword',document.getElementById('ftppassword').value);		
			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveFreeWebMain$t);		
		
		
	
	}
	
	
	
	function useMysqlCheck(){
		var mysql_instance_id=0;
		var checkboxes=$checkboxes;
		var PUREFTP_INSTALLED=$PUREFTP_INSTALLED;
		if(document.getElementById('mysql_instance_id')){mysql_instance_id=document.getElementById('mysql_instance_id').value;}
		document.getElementById('useFTP').disabled=true;
		document.getElementById('ftpuser').disabled=true;
		document.getElementById('ftppassword').disabled=true;		
		
		if(checkboxes==1){
			if(PUREFTP_INSTALLED==1){document.getElementById('useFTP').disabled=false;}
			if(mysql_instance_id==0){document.getElementById('useMysql').disabled=false;}	
		}
		if(PUREFTP_INSTALLED==1){
			document.getElementById('useFTP').disabled=false;
		}else{
			document.getElementById('useFTP').disabled=true;
			document.getElementById('useFTP').checked=false;
		}
		
		document.getElementById('mysql_database').disabled=true;
		document.getElementById('mysql_username').disabled=true;
		document.getElementById('mysql_password').disabled=true;
		document.getElementById('ftpuser').disabled=true;
		document.getElementById('ftppassword').disabled=true;
		if(mysql_instance_id==0){
			if(document.getElementById('useMysql').checked){
				var mysql_database=document.getElementById('mysql_database').value;
				if(mysql_database.length==0){document.getElementById('mysql_database').disabled=false;}
				document.getElementById('mysql_username').disabled=false;
				document.getElementById('mysql_password').disabled=false;
			}
		}
		
		if(mysql_instance_id==1){
			document.getElementById('mysql_database').disabled=false;
		}
		
		if(!document.getElementById('useFTP').checked){return;}
		document.getElementById('ftpuser').disabled=false;
		document.getElementById('ftppassword').disabled=false;		
		
		
	}
	
	function mysql_instance_id_check(){
			
			var mysql_instance_id=document.getElementById('mysql_instance_id').value;
			
			if(mysql_instance_id>0){
				document.getElementById('useMysql').disabled=true;
				useMysqlCheck();
			}else{
				document.getElementById('useMysql').disabled=false;
				useMysqlCheck();
			}
		}	
	
	function freeweb_mysql_instances(){
		LoadAjaxTiny('freeweb-mysql-instances','freeweb.edit.php?freeweb-mysql-instances-field=yes&servername={$ligne["servername"]}');
	
	}	
	freeweb_mysql_instances();
	useMysqlCheck();
	LoadAjax('$t-tool','$page?mysql-toolbox=yes&servername={$ligne["servername"]}');
	</script>	
	
	
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function SAVE_FREEWEB_MYSQL(){
	
	
	$servername=trim(strtolower($_POST["servername"]));
	if(substr($servername, 0,1)=='.'){echo $servername. " FAILED\n";return;}
	$users=new usersMenus();
	$sock=new sockets();
	$FreewebsStorageDirectory=$sock->GET_INFO("FreewebsStorageDirectory");
	
	if(!$users->AsWebMaster){return "FALSE";}
	$uid=$_POST["uid"];
	$mysql_database=format_mysql_table($_POST["mysql_database"]);
	$mysql_password=$_POST["mysql_password"];
	$mysql_username=$_POST["mysql_username"];
	$lvm_vg=$_POST["lvm_vg"];
	$vg_size=$_POST["vg_size"];
	$ServerIP=$_POST["ServerIP"];
	$ServerPort=0;
	if(preg_match("#(.+?):([0-9]+)#", $ServerIP,$re)){$ServerIP=$re[1];$ServerPort=$re[2];}
	
	

	if(!is_numeric($vg_size)){$vg_size=5000;}
	$ftpuser=$_POST["ftpuser"];
	$ftppassword=$_POST["ftppassword"];
	$useSSL=$_POST["useSSL"];
	
	if(!$users->PUREFTP_INSTALLED){
		$_POST["useFTP"]=0;
		$ftpuser=null;
		$ftppassword=null;
	}

	if($_POST["useFTP"]==1){
		if($ftpuser==null){
			$_POST["useFTP"]=0;
			$ftpuser=null;
			$ftppassword=null;			
		}
	}
	
	
	
	$sql="SELECT servername FROM freeweb WHERE servername='{$_POST["servername"]}'";
	$q=new mysql();

	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if($ligne["servername"]<>null){
		if($uid<>null){$u=new user($uid);$ou=$u->ou;}
		if(!$users->AsSystemAdministrator){$ou=$_SESSION["ou"];}
			
		$sql="UPDATE freeweb SET 
			mysql_password='$mysql_password',
			mysql_username='$mysql_username',
			mysql_database='$mysql_database',
			mysql_instance_id='{$_POST["mysql_instance_id"]}',
			ftpuser='$ftpuser',
			ftppassword='$ftppassword',
			useMysql='{$_POST["useMysql"]}',
			useFTP='{$_POST["useFTP"]}'
			WHERE servername='$servername'
		";
	}
	
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		if(preg_match("#Unknown column#i",$q->mysql_error)){
			$q->BuildTables();
			$q->QUERY_SQL($sql,"artica_backup");
		}
	}
	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
	if($_POST["useFTP"]==1){
		if($users->PUREFTP_INSTALLED){
			if(trim($ftpuser)<>null){
				if(trim($ftppassword)<>null){
					$pure=new pureftpd_user();
					if(!$pure->CreateUser($ftpuser,$ftppassword,$servername)){
						echo "FTP: Failed\n";
						return;
					}
				$sock->getFrameWork("services.php?reload-pure-ftpd=yes");
				}
			}
		}
	}
	
	if($_POST["useMysql"]==1){
		if(!$q->DATABASE_EXISTS($mysql_database)){$q->CREATE_DATABASE("$mysql_database");}
		if(!$q->PRIVILEGES($mysql_username,$mysql_password,$mysql_database)){
			echo "GRANT $mysql_database FAILED FOR $mysql_username\n$q->mysql_error";
		}
	}
	
	if(isset($_POST["ADD_DNS_ENTRY"])){
		$dnsDOM=explode(".", $_POST["servername"]);
		$netbiosname=$dnsDOM[0];
		unset($dnsDOM[0]);
		$domainname=implode(".", $dnsDOM);
		include_once(dirname(__FILE__)."/ressources/class.pdns.inc");
		$pdns=new pdns($domainname);
		$pdns->EditIPName($netbiosname, $_POST["ADD_DNS_ENTRY"], "A");
	}
	
	if($ligne["servername"]==null){
		if($_POST["force-groupware"]<>null){
			$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('INSTALL_GROUPWARE','$servername')";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$servername");
		}
	}
	$sock->getFrameWork("services.php?freeweb-start=yes");
	sleep(2);
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
}	
	



function SAVE_FREEWEB_MAIN(){
	
	$servername=trim(strtolower($_POST["servername"]));
	if(substr($servername, 0,1)=='.'){echo $servername. " FAILED\n";return;}
	$users=new usersMenus();
	$sock=new sockets();
	$FreewebsStorageDirectory=$sock->GET_INFO("FreewebsStorageDirectory");
	
	if(!$users->AsWebMaster){return "FALSE";}
	$uid=$_POST["uid"];
	$lvm_vg=$_POST["lvm_vg"];
	$vg_size=$_POST["vg_size"];
	$ServerIP=$_POST["ServerIP"];
	$ServerPort=0;
	if(preg_match("#(.+?):([0-9]+)#", $ServerIP,$re)){$ServerIP=$re[1];$ServerPort=$re[2];}
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	
	if($EnableFreeWeb==0){
		$sock->SET_INFO("EnableFreeWeb",1);
		$sock->SET_INFO("EnableApacheSystem",1);
		$sock->getFrameWork("freeweb.php?changeinit-off=yes");
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
		$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	}
	
	if(!is_numeric($vg_size)){$vg_size=5000;}
	$useSSL=$_POST["useSSL"];
	
	if($_POST["domainname"]==null){
		$TDOM=explode(".",$_POST["domainname"]);
		unset($TDOM[0]);
		$_POST["domainname"]=@implode(".", $TDOM);
	}
	
	if(!isset($_POST["WebCopyID"])){$_POST["WebCopyID"]=0;}
	$sql="SELECT servername FROM freeweb WHERE servername='{$_POST["servername"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if($ligne["servername"]<>null){
		if($uid<>null){$u=new user($uid);$ou=$u->ou;}
		if(!$users->AsSystemAdministrator){$ou=$_SESSION["ou"];}
		
		if(isset($_POST["www_dir"])){
			$www_dir_field="www_dir='{$_POST["www_dir"]}',";
		}
			
		$sql="UPDATE freeweb SET 
			uid='$uid',
			gpid='{$_POST["gpid"]}',
			lvm_vg='{$_POST["lvm_vg"]}',
			lvm_size='{$_POST["vg_size"]}',
			UseLoopDisk='{$_POST["UseLoopDisk"]}',
			LoopMounts='{$_POST["LoopMounts"]}',
			UseReverseProxy='{$_POST["UseReverseProxy"]}',
			ProxyPass='{$_POST["ProxyPass"]}',
			useSSL='$useSSL',
			ServerPort='$ServerPort',
			$www_dir_field
			ou='$ou',
			Forwarder='{$_POST["Forwarder"]}',
			ForwardTo='{$_POST["ForwardTo"]}',
			ServerIP='$ServerIP',
			WebCopyID='{$_POST["WebCopyID"]}',
			sslcertificate='{$_POST["sslcertificate"]}'
			WHERE servername='$servername'
		";
	}else{
		if($uid<>null){$u=new user($uid);$ou=$u->ou;}
		if($ou<>null){if($FreewebsStorageDirectory<>null){$www_dir="$FreewebsStorageDirectory/$servername";}}
		$sock=new sockets();
		$servername=strip_bad_characters($servername);
		if(substr($servername, strlen($servername)-1,1)=='.'){$servername=substr($servername, 0,strlen($servername)-1);}
		if(substr($servername,0,1)=='.'){$servername=substr($servername, 1,strlen($servername));}
		
		if($_POST["force-groupware"]<>null){
			$groupware_field=",groupware";
			$groupware_value=",'{$_POST["force-groupware"]}'";
		}
	
		if(!is_numeric($_POST["WebCopyID"])){
			$WebCopyID_field=",WebCopyID";
			$WebCopyID_value=",'{$_POST["WebCopyID"]}'";
		}
		$sslcertificate=$_POST["sslcertificate"];
		$sock->getFrameWork("freeweb.php?force-resolv=yes");
		$sql="INSERT INTO freeweb (useSSL,servername,
		uid,gpid,lvm_vg,lvm_size,UseLoopDisk,LoopMounts,ou,domainname,www_dir,ServerPort,UseReverseProxy,
		ProxyPass,Forwarder,ForwardTo,ForceInstanceZarafaID,ServerIP,sslcertificate$groupware_field$WebCopyID_field)
		VALUES('$useSSL','$servername','$uid','{$_POST["gpid"]}','{$_POST["lvm_vg"]}','{$_POST["vg_size"]}','{$_POST["UseLoopDisk"]}','{$_POST["LoopMounts"]}','$ou',
		'{$_POST["domainname"]}','$FreewebsStorageDirectory','$ServerPort','{$_POST["UseReverseProxy"]}','{$_POST["ProxyPass"]}',
		'{$_POST["Forwarder"]}','{$_POST["ForwardTo"]}','{$_POST["ForceInstanceZarafaID"]}','$ServerIP','$sslcertificate'$groupware_value$WebCopyID_value
		)";
	}
	
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Function ". __FUNCTION__."\nLine:".__LINE__."\nFile:".__FILE__."\n".$q->mysql_error;return;}
	

	if($_POST["www_dir"]<>null){
		$sql="UPDATE freeweb SET `www_dir`='{$_POST["www_dir"]}' WHERE servername='$servername'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Function ". __FUNCTION__."\nLine:".__LINE__."\nFile:".__FILE__."\n".$q->mysql_error;return;}
		
	}
	
	$sock=new sockets();
	
	
	
	if(isset($_POST["ADD_DNS_ENTRY"])){
		$dnsDOM=explode(".", $_POST["servername"]);
		$netbiosname=$dnsDOM[0];
		unset($dnsDOM[0]);
		$domainname=implode(".", $dnsDOM);
		include_once(dirname(__FILE__)."/ressources/class.pdns.inc");
		$pdns=new pdns($domainname);
		$pdns->EditIPName($netbiosname, $_POST["ADD_DNS_ENTRY"], "A");
	}
	
	if($ligne["servername"]==null){
		if($_POST["force-groupware"]<>null){
			$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('INSTALL_GROUPWARE','$servername')";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$servername");
		}
	}
	$sock->getFrameWork("services.php?freeweb-start=yes");
	sleep(2);
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$servername");
	
	
}	

function mysql_toolbox(){
	$tpl=new templates();
	$free=new freeweb($_GET["servername"]);
	if($free->useMysql==0){
		$browse=ParagrapheTEXT_disabled("table-show-48.png", "{browse_database}", "{browse_database_mysql_text}");
		
	}else{
		$js="YahooWin4('650','mysql.browse.php?database={$free->mysql_database}&instance-id=$free->mysql_instance_id','&raquo;$free->mysql_database');";
		$browse=ParagrapheTEXT("table-show-48.png", "{browse_database}", "{browse_database_mysql_text}",
		"javascript:$js");
	}
echo $tpl->_ENGINE_parse_body("<div style='width:210px;margin-rigth:5px'>$browse</div>");
	
}

function sugard_additional_infos($servername){
	$free=new freeweb($_GET["servername"]);
$sock=new sockets();
		
		$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
		$FreeWebListen=$sock->GET_INFO("FreeWebListenPort");
		if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
		if(!is_numeric($FreeWebListen)){$FreeWebListen=80;}
		$listen=$FreeWebListen;
		if($free->useSSL==1){$listen=$FreeWebListenSSLPort;}	
		$q=new mysql();
		$SocketPath=$q->mysql_server;
		$mysql_admin=$q->mysql_admin;
		$mysql_password=$q->mysql_password;		
		$dbport=$q->mysql_port;
		
		if($free->mysql_instance_id>0){
			$q=new mysql_multi($this->mysql_instance_id);
			$mysql_admin=$q->mysql_admin;
			$mysql_password=$q->mysql_password;
			$SocketPath="127.0.0.1";
			$dbport=$q->mysql_port;
		}
		
		if($free->mysql_username<>null){$mysql_admin=$free->mysql_username;}
		if($free->mysql_password<>null){$mysql_password=$free->mysql_password;}
		$ldap=new clladp();
		$html="<table style='width:99%;margin-top:15px' class=form>
		<tr>
			<td class=legend style='font-size:14px' class=legend>{mysql_database_name}:</td>
			<td style='font-size:14px;font-weight:bold'>$free->mysql_database</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px' class=legend>{mysql_server}:</td>
			<td style='font-size:14px;font-weight:bold'>$SocketPath {port}: $dbport</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:14px' class=legend>{mysql_admin}:</td>
			<td style='font-size:14px;font-weight:bold'>$mysql_admin</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:14px' class=legend>{ldap_server}:</td>
			<td style='font-size:14px;font-weight:bold'>$ldap->ldap_host</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:14px' class=legend>{ldap_suffix}:</td>
			<td style='font-size:14px;font-weight:bold'>$ldap->suffix</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px' class=legend>{ldap_admin}:</td>
			<td style='font-size:14px;font-weight:bold'>$ldap->ldap_admin</td>
		</tr>			
		</table>
		";
		return $html;
		
		
		

}