<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
		
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["list"])){serverlist();exit;}
if(isset($_GET["update"])){update();exit;}
if(isset($_GET["update-zoom"])){update_zoom();exit;}
	
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{appliances}");
	$html="YahooWin0(750,'$page?tabs=yes','$title');";
	echo $html;
	
}

function tabs(){
		$tpl=new templates();
		$page=CurrentPageName();
		$users=new usersMenus();
	
		$array["popup"]='{appliances}';
		$array["update"]='{update}';
		
		
		
	while (list ($num, $ligne) = each ($array) ){
		 $tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		}
	
	$html="
		<div id='main_appliances_config' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_appliances_config').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();		
	$t=time();
	$html="
	<div style='text-align:right;width:100%'>". imgtootltip("refresh-32.png","{refresh}","RefreshNodes$t()")."</div>
	<dif id='$t' style='width:100%'></div>
	<script>
		function RefreshNodes$t(){
			LoadAjax('$t','$page?list=yes');
		
		}
		RefreshNodes$t();
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function update(){
	$page=CurrentPageName();
$tpl=new templates();	
	$t=time();
	echo $tpl->_ENGINE_parse_body("
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('$t','$page?update-zoom=yes&t=$t');")."</div>
	<div id='$t'></div>
	
	<script>
		LoadAjax('$t','$page?update-zoom=yes&t=$t');
	</script>
	");
	
	
}

function update_zoom(){
$page=CurrentPageName();
$tpl=new templates();	
$sock=new sockets();
$ini=new Bs_IniHandler();
$ini->loadString(@file_get_contents(dirname(__FILE__). '/ressources/index.ini'));
$users=new usersMenus();
$ArchStruct=$users->ArchStruct;
if($ArchStruct=="32"){$ArchStruct="i386";}
if($ArchStruct=="64"){$ArchStruct="x64";}

$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;}	
$squid_version=	ParseAppli($GlobalApplicationsStatus,"APP_SQUID");

$availableversion32=$ini->_params["NEXT"]["squid32-i386"];
$availableversion64=$ini->_params["NEXT"]["squid32-x64"];
$availableversion_ufdbguard=$ini->_params["NEXT"]["ufdbGuard"];
$actualversion_ufdbguard=$sock->getFrameWork("squid.php?full-ufdbg-version=yes");
if($actualversion_ufdbguard==null){$actualversion_ufdbguard="0.0.0";}
$availableversion_kav4proxy=$ini->_params["NEXT"]["kav4proxy"];
$actualversion_kav4proxy=ParseAppli($GlobalApplicationsStatus,"APP_KAV4PROXY");
if($actualversion_kav4proxy==null){$actualversion_kav4proxy="0.0.0";}
$q=new blackboxes();
$availableversion32=$q->last_available_squidx32_version();
$availableversion64=$q->last_available_squidx64_version();
$html="
<div style='width:100%;height:650px;overflow:auto'>

<table style='width:100%;margin-top:15px'>
<tbody>
<tr>
	<td valign='top' width=1%><img src='img/artica-350px.png'>
				<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:13px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_ARTICA_AGENT}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{available} 32/64bits:</td>
						<td style='font-size:14px;font-weight:bold'>".$q->last_available_version()."</td>
					</tr>				
				</tbody>
			</table>
	
	</td>
	<td valign='top'>

			<div style='font-size:12px'>{APP_ARTICA_AGENT_TEXT}</div>
			<p>&nbsp;</p>
			<center style='margin:10px'>".button("{install_upgrade}", "Loadjs('setup.index.progress.php?product=APP_ARTICA_AGENT&start-install=yes')",14)."</center>
	</td>
</tr>


<table style='width:100%;margin-top:15px'>
<tbody>
<tr>
	<td valign='top' width=1%><img src='img/bg_squid.jpg'>
				<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:13px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_SQUID2}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{available} 32bits:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion32</td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{available} 64bits:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion64</td>
					</tr>					
				</tbody>
			</table>
	
	</td>
	<td valign='top'>

			<div style='font-size:12px'>{APP_SQUID_TEXT}</div>
			<p>&nbsp;</p>
			<center style='margin:10px'>".button("{install_upgrade}", "Loadjs('setup.index.progress.php?product=APP_SQUID32_REPOS&start-install=yes')",14)."</center>
	</td>
</tr>

<tr>
	<td colspan=2><hr></td>
</tr>
<tr>
	<td valign='top' width=1%><img src='img/bg_kav4proxy.jpg'>
				<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:13px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_KAV4PROXY}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_kav4proxy</td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_kav4proxy</td>
					</tr>					
					
				</tbody>
				
			</table>
	
	</td>
	<td valign='top'>

			<div style='font-size:12px'>{APP_KAV4PROXY_TEXT}</div>
			<p>&nbsp;</p>
			<span id='kav4proxy-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","kav4proxy_install_status()")."</div>
	</td>
</tr>
<tr>
	<td colspan=2><hr></td>
</tr>
<tr>
	<td colspan=2><hr></td>
</tr>

<tr>
	<td valign='top' width=1%><img src='img/bg_ufdbguard.png'>
				<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:13px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_UFDBGUARD}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_ufdbguard</td>
					</tr>
					<tr>
						<td class=legend style='font-size:13px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_ufdbguard</td>
					</tr>					
					
				</tbody>
			</table>
	
	</td>
	<td valign='top'>

			<div style='font-size:12px'>{ufdbguard_simple_intro}</div>
			<p>&nbsp;</p>
			<span id='ufdbguard-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","ufdbguard_install_status()")."</div>
	</td>
</tr>



</tbody>
</table>
</div>
<script>

</script>
";

echo $tpl->_ENGINE_parse_body($html);
	
}	
function ParseAppli($status,$key){

if(!is_array($GLOBALS["GLOBAL_VERSIONS_CONF"])){BuildVersions();}
return $GLOBALS["GLOBAL_VERSIONS_CONF"][$key];	
}

function BuildVersions(){
	if(is_file("ressources/logs/global.versions.conf")){
		$GlobalApplicationsStatus=@file_get_contents("ressources/logs/global.versions.conf");
	}else{
		if(is_file("ressources/logs/web/global.versions.conf")){
			$GlobalApplicationsStatus=@file_get_contents("ressources/logs/web/global.versions.conf");
		}
	}
	$tb=explode("\n",$GlobalApplicationsStatus);
	while (list ($num, $line) = each ($tb) ){
		if(preg_match('#\[(.+?)\]\s+"(.+?)"#',$line,$re)){
			$GLOBALS["GLOBAL_VERSIONS_CONF"][trim($re[1])]=trim($re[2]);
		}
		
	}
}


function serverlist(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$q=new mysql_blackbox();
	$add_artica_agent_explain=$tpl->javascript_parse_text("{add_artica_agent_explain}");
	
	$t=time();

	
	$sql="SELECT * FROM nodes";
	$results=$q->QUERY_SQL($sql);
	$classtr=null;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nodeid=$ligne["nodeid"];
		$server=$ligne["ipaddress"];
		$port=$ligne["port"];
		$hostname=$ligne["hostname"];
		$laststatus=distanceOfTimeInWords(time(),strtotime($ligne["laststatus"]));
		$perfs=unserialize(base64_decode($ligne["perfs"]));
		$perftext="&nbsp;";
		$settings=unserialize(base64_decode($ligne["settingsinc"]));
		
		$fqdn_hostname=$settings["fqdn_hostname"];
		if($fqdn_hostname==null){$fqdn_hostname=$server;}
		
		if(is_array($perfs["MEMORY"])){
				
				$hash_mem=$perfs["MEMORY"];
				$Hash_real_mem=$perfs["REALMEM"];
				
				if(is_array($Hash_real_mem)){
					$hash_mem["ram"]["percent"]=$Hash_real_mem["ram"]["percent"];
					$hash_mem["ram"]["used"]=$Hash_real_mem["ram"]["used"];
					$hash_mem["ram"]["total"]=$Hash_real_mem["ram"]["total"];
				}
		
				$mem_used_p=$hash_mem["ram"]["percent"];
				$mem_used_kb=FormatBytes($hash_mem["ram"]["used"]);
				$total=FormatBytes($hash_mem["ram"]["total"]);
				$color="#5DD13D";
				
				$swapar_perc=$hash_mem['swap']['percent'];
				$swap_color="rgb(93, 209, 61)";
				$swap_text="<br><span style='font-size:9px'>swap: {$swapar_perc}% {used}</span>";
				if($swapar_perc>30){$swap_color="#F59C44";}
				if($swapar_perc>50){$swap_color="#D32D2D";}	
				$swap="<div style=\"border: 1px solid $swap_color; width: 100px; background-color: white; padding-left: 0px; margin-top: 3px;\" ". CellRollOver($swap_js).">
						<div style=\"width: {$swapar_perc}px; text-align: center; color: white; padding-top: 3px; padding-bottom: 3px; background-color:$swap_color;\"> </div>
				</div>";
				
				
				if($mem_used_p>70){$color="#F59C44";}
				if($mem_used_p>79){$color="#D32D2D";}		
				$memtext="<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color'>
				<div style='width:{$mem_used_p}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'><strong>{$mem_used_p}%</strong></div>
				</div>$swap"	;
			
			//print_r($perfs["MEMORY"]);
		}
		
		
		if(is_numeric($perfs["LOAD_POURC"])){
			$perfsColor="white";
			if($perfs["LOAD_POURC"]==0){$perfsColor="black";}
		$perftext="
		<table style='width:100%' margin=0 padding=0>
		<tr style='background-color:transparent'>
		<td padding=0px style='border:0px'><span style='font-size:11px'>{load}:</span></td>
		<td padding=0px style='border:0px'>
		<div style='width:100px;background-color:white;padding-left:0px;border:1px solid {$perfs["LOAD_COLOR"]};margin-top:3px'>
			<div style='width:{$perfs["LOAD_POURC"]}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:{$perfs["LOAD_COLOR"]}'>
				<span style='color:$perfsColor;font-size:11px;font-weight:bold'>{$perfs["LOAD_POURC"]}%</span>
			</div>
		</div>
		</td >
		</tr'>
		<tr padding=0px style='background-color:transparent'>
			<td style='border:0px'><span style='font-size:11px'>{memory}:</span></td>
			<td style='border:0px'>$memtext</td>
		</tr>
		</table>";
		}
		
		
		$fqdn_hostnameAR=explode(".",$fqdn_hostname);
		if(strpos($hostname, ".")>0){
			$hostnameTR=explode(".",$hostname);
			$hostname=$hostnameTR[0];
		}
		$hostTXT=$hostname;
		$NODES[]=
		"<table style='width:90%' class=form>
		<tbody>
		<tr>
			<td width=1%>". imgtootltip("64-idisk-server.png","$fqdn_hostname","Loadjs('nodes.php?nodeid=$nodeid')")."</td>
			<td width=99%>
				<strong style='font-size:12px'>
					<a href=\"javascript:blur();\"
					style='font-size:14px;text-decoration:underline'
					OnClick=\"javascript:Loadjs('nodes.php?nodeid=$nodeid')\"
					>$hostTXT</a></strong>
				$perftext
			</td>
		</tr>
		</tbody>
		</table>";

			
		
	}
	
	$html=CompileTr2($NODES);

	echo $tpl->_ENGINE_parse_body($html);
	
}
?>