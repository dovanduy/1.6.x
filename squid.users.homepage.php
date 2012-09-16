<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.tcpip.inc');

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");

if(isset($_GET["myhome1"])){myhome1();exit;}
if(isset($_GET["myhome2"])){myhome2();exit;}
if(isset($_GET["myhome3"])){myhome3();exit;}
if(isset($_POST["update-public-ip"])){task_update_public_ip();exit;}

if(isset($_GET["UpdatePublicIPBox"])){UpdatePublicIPBox();exit;}
if(isset($_POST["UpdatePublicIPBoxSave"])){UpdatePublicIPBoxSave();exit;}
page();



function page(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$html="<div class=form>
	<div style='font-size:18px'>{welcome_title} {$_SESSION["email"]}</div>
	<p style='font-size:16px'  class=explain>{instroduction_enduser_proxy}</p>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' style='width:50%'>
					<table style='width:99%' class=form>
					<tr>
						<td width=1% valign='top'><img src='img/infowarn-64.png'></td>
						<td valign='top'>
						<div style='font-size:16px'>{public_information}:</div>
						<div id='myhome1'></div>
						
						</td>
					</tr>
					</table>
				<div id='myhome3'></div>
		</td>
		<td valign='top' style='width:50%'>
			
			<div id='myhome2'></div>
		</td>
	</tr>
	</table>
	</div>
	
	
	<script>
		LoadAjax('myhome1','$page?myhome1=yes');
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function myhome1(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$ip=$_SERVER["REMOTE_ADDR"];
	$you_need_to_update_your_ip_address=$tpl->_ENGINE_parse_body("{you_need_to_update_your_ip_address}");
	$update_your_ip_address=$tpl->_ENGINE_parse_body("{update_your_ip_address}");
	$q=new mysql_squid_builder();
	
	$tcip=new networking();
	$tcp_array=$tcip->ALL_IPS_GET_ARRAY();
	$prefix="http";
	$port=$_SERVER["SERVER_PORT"];
	if($_SERVER["HTTPS"]=="on"){$prefix="https";}
	if( ($port=="80") OR ($port=="443")){$port=null;}else{$port=":$port";}
	$orignaluri="$prefix://{$_SERVER["SERVER_NAME"]}$port";
	
	$q->QUERY_SQL("UPDATE usersisp SET wwwname='$orignaluri' WHERE userid='{$_SESSION["uid"]}'");
	
	if(strpos(" $q->mysql_error", "Unknown column")>0){
		$q->CheckTables();
		echo "<script>
		LoadAjax('myhome1','$page?myhome1=yes');
		</script>";
	}
	
	if(!$q->ok){echo "<strong style='color:#D20404'>$q->mysql_error</strong>";}
	
	
	$sql="SELECT publicip,wwwname FROM usersisp WHERE userid='{$_SESSION["uid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));	
	
	
	
	
	if($ligne["publicip"]<>null){
		$hostname=gethostbyaddr($ip);
		if($tcp_array[$ligne["publicip"]]<>null){
			$hostname=null;
			$hostname="<table style='width:100%'>
			<tr>
			<td width=1%><img src='img/warning-panneau-32.png'>
			<td><strong style='color:#D20404'>
				<a href=\"javascript:blur();\" OnClick=\"javascript:UpdatePublicIPBox();\" style='font-size:12px;text-decoration:underline;color:#D20404'>
					$you_need_to_update_your_ip_address</a>
				</td>
				
			</td>
		</tr>
		</table>
		";		
		}
		
	}else{
		$hostname="<table style='width:100%'>
		<tr>
			<td width=1%><img src='img/warning-panneau-32.png'>
			<td><strong style='color:#D20404'>
				<a href=\"javascript:blur();\" OnClick=\"javascript:UpdatePublicIP();\" style='font-size:12px;text-decoration:underline;color:#D20404'>
					$you_need_to_update_your_ip_address</a>
				</td>
		</tr>
		</table>
		";
		
	}
	
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px'>{public_ip}:</td>
		<td><strong style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"javascript:UpdatePublicIPBox();\" style='font-size:14px;text-decoration:underline;'>{$ligne["publicip"]}</a></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td><strong style='font-size:14px'>$hostname</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{howto}:</ 
	
	
	</table>
	
	<script>
		var x_UpdatePublicIP= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			LoadAjax('myhome1','$page?myhome1=yes');
			}		
	
	
		function UpdatePublicIP(){	
			var XHR = new XHRConnection();
			XHR.appendData('update-public-ip','yes');
			AnimateDiv('myhome1');
			XHR.sendAndLoad('$page', 'POST',x_UpdatePublicIP);		
		}
		
		function UpdatePublicIPBox(){
			YahooWin2('445','$page?UpdatePublicIPBox=yes','$update_your_ip_address');
		}
		
		LoadAjax('myhome2','$page?myhome2=yes');
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function myhome2(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$categories=$q->COUNT_CATEGORIES();
	$categories=numberFormat($categories,0,""," ");
	
	$tablescat=$q->LIST_TABLES_CATEGORIES();
	$tablescatNUM=numberFormat(count($tablescat),0,""," ");	
	
	$PhishingURIS=$q->COUNT_ROWS("uris_phishing");
	$PhishingURIS=numberFormat($PhishingURIS,0,""," ");	
	
	
	$MalwaresURIS=$q->COUNT_ROWS("uris_malwares");
	$MalwaresURIS=numberFormat($MalwaresURIS,0,""," ");	
	
	$sql="SELECT COUNT(category) as tcount FROM usersisp_catztables WHERE userid='{$_SESSION["uid"]}' AND blck=0";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$PersonalBlacklists=$ligne["tcount"];
	
	$sql="SELECT COUNT(category) as tcount FROM usersisp_catztables WHERE userid='{$_SESSION["uid"]}' AND blck=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$PersonalWhitelists=$ligne["tcount"];

	

	
	$html="
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td width=1% valign='top'><img src='img/webfilter-64.png'></td>
	<td width=99% valign='top'>
		<table style='width:100%'>
			<tbody>
				<tr>
					<td colspan=2><div style='font-size:16px'>{webfiltering_service}</div></td>
				</tr>
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\"><b>$tablescatNUM</b> {categories}</td>
				</tr>		
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\"><b>$categories</b> {websites_categorized}</td>
				</tr>
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\"><b>$PhishingURIS</b> {phishing_uris}</td>
				</tr>	
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\"><b>$MalwaresURIS</b> {viruses_uris}</td>
				</tr>				
			</tbody>
		</table>
	</td>
	</tr>
	</tbody>
	</table>	
	<br>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1% valign='top'><img src='img/web-ssl-64.png'></td>
		<td width=99% valign='top'>
		<table style='width:100%'>
			<tbody>
				<tr>
					<td colspan=2><div style='font-size:16px'>{your_settings}</div></td>
				</tr>
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"javascript:QuickLinkSystems('section_webfiltering_dansguardian')\"><b>$PersonalBlacklists</b> {blacklist_categories}</td>
				</tr>	
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"javascript:QuickLinkSystems('section_webfiltering_dansguardian')\"><b>$PersonalWhitelists</b> {whitelist_categories}</td>
				</tr>
			</tbody>
		</table>
		</td>
	</td>
	</tr>
	</tbody>
	</table>	
	
	<script>
		LoadAjax('myhome3','$page?myhome3=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function myhome3(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	if(!is_numeric($_SESSION["uid"])){
		$error="Session:{$_SESSION["uid"]} invalid";
	}
	
	if($_SESSION["uid"]==0){$error="Session:&laquo;{$_SESSION["uid"]}&raquo; invalid";}
	
	if($_SESSION["uid"]>0){
		$currenttable="dansguardian_events_".date('Ymd');
		$sql="SELECT SUM(hits) as thits,SUM(QuerySize) as tsize FROM $currenttable WHERE account={$_SESSION["uid"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
		if(!$q->ok){$error=$q->mysql_error."<br><code>$sql</code>";}
	}
	
	
	if(!is_numeric($ligne["thits"])){$ligne["thits"]=0;}
	if(!is_numeric($ligne["tsize"])){$ligne["tsize"]=0;}
	
	$SumHits=numberFormat($ligne["thits"],0,""," ");
	$SumSize=FormatBytes($ligne["tsize"]/1024);		
	
	
	$html="<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td width=1% valign='top'><img src='img/windows-internet-64.png'></td>
	<td width=99% valign='top'>
		<table style='width:100%'>
			<tbody>
				<tr>
					<td colspan=2><div style='font-size:16px'>{internet_browsing}</div></td>
				</tr>
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\">{today}:<b>$SumHits</b> {hits}</td>
				</tr>		
				<tr>
					<td valign='top' $mouse style='font-size:14px;text-decoration:underline' OnClick=\"blur()\">{today}:<b>$SumSize</b> {downloaded_flow}</td>
				</tr>		
			</tbody>
		</table>
		<span style='font-size:11px;color:red'>$error</span>
	</td>
	</tr>
	</tbody>
	</table>";
	echo $tpl->_ENGINE_parse_body($html);
		
	
}

function task_update_public_ip(){
	$q=new mysql_squid_builder();
	
	$IpToSave=$_SERVER["REMOTE_ADDR"];
	$tcip=new networking();
	$tcp_array=$tcip->ALL_IPS_GET_ARRAY();	
	if($tcp_array[$IpToSave]<>null){$IpToSave=null;}
	$ip=new IP();
	if(!$ip->isValid($IpToSave)){$IpToSave=null;}
	
	if($IpToSave==null){echo $tpl->javascript_parse_text("{$_SERVER["REMOTE_ADDR"]}: {invalid}");return;}
	
	$sql="SELECT userid,email FROM usersisp WHERE publicip='$IpToSave'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));	
	if($ligne["userid"]>0){	
		if($ligne["userid"]<>$_SESSION["uid"]){
			echo $tpl->javascript_parse_text("{$_SERVER["REMOTE_ADDR"]}: {already_affected}");return;}
	}	
	
	$sql="UPDATE usersisp SET publicip='{$_SERVER["REMOTE_ADDR"]}' WHERE userid='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");
}

function UpdatePublicIPBox(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sql="SELECT * FROM usersisp WHERE userid='{$_SESSION["uid"]}'";
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));	
	if($ligne["publicip"]<>null){
		$tcip=new networking();
		$tcp_array=$tcip->ALL_IPS_GET_ARRAY();	
		if($tcp_array[$ligne["publicip"]]<>null){$ligne["publicip"]=null;}
	}else{
		$ligne["publicip"]=$_SERVER["REMOTE_ADDR"];
	}	
	
	
	$t=time();
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{public_ip}:</td>
		<td><strong style='font-size:14px'>". field_ipv4("UpdatePublicIPBoxSave", $ligne["publicip"],"font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{update_your_ip_address}","UpdatePublicIPBoxSave()",16)."</td>
	</tr>
	</table>	
	<script>
		var x_UpdatePublicIPBoxSave= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>0){alert(results);return;}
			LoadAjax('myhome1','$page?myhome1=yes');
			}		
	
	
		function UpdatePublicIPBoxSave(){	
			var XHR = new XHRConnection();
			XHR.appendData('UpdatePublicIPBoxSave',document.getElementById('UpdatePublicIPBoxSave').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_UpdatePublicIPBoxSave);		
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function UpdatePublicIPBoxSave(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql_squid_builder();
	$IpToSave=$_POST["UpdatePublicIPBoxSave"];
	$tpl=new templates();
	$tcip=new networking();
	$tcp_array=$tcip->ALL_IPS_GET_ARRAY();	
	if(isset($tcp_array[$IpToSave])){$IpToSave=null;}
	$ip=new IP();
	if(!$ip->isValid($IpToSave)){$IpToSave=null;}
	
	if($IpToSave==null){echo $tpl->javascript_parse_text("{$_POST["UpdatePublicIPBoxSave"]}: {invalid}");return;}
	
	$sql="SELECT userid,email FROM usersisp WHERE publicip='$IpToSave'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));		
	if($ligne["userid"]>0){	
		if($ligne["userid"]<>$_SESSION["uid"]){
			echo $tpl->javascript_parse_text("{$_SERVER["REMOTE_ADDR"]}: {already_affected}");return;}
	}	
	
	$sql="UPDATE usersisp SET publicip='$IpToSave' WHERE userid='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
	
}

