<?php
	include_once('ressources/class.templates.inc');
	if(posix_getuid()==0){die();}
	if(isset($_POST["WIZINTERFACE"])){$_SESSION["WIZINSTANCE"]["WIZINTERFACE"]=$_POST["WIZINTERFACE"];exit;}
	if(isset($_POST["WIZIPADDR"])){
		$_SESSION["WIZINSTANCE"]["WIZIPADDR"]=$_POST["WIZIPADDR"];
		$_SESSION["WIZINSTANCE"]["WIZIPMASK"]=$_POST["WIZIPMASK"];
		$_SESSION["WIZINSTANCE"]["WIZIPGW"]=$_POST["WIZIPGW"];
		exit;		
	}
	if(isset($_POST["WIZHOST"])){
		$_SESSION["WIZINSTANCE"]["WIZHOST"]=$_POST["WIZHOST"];
		$_SESSION["WIZINSTANCE"]["WIZOU"]=$_POST["WIZOU"];
		exit;
	}

	
	
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["start-ajax"])){start_ajax();exit;}
	if(isset($_GET["start"])){start();exit;}


	if(isset($_POST["WIZ_BUILD"])){WIZ_BUILD_IPADDR();exit;}
	if(isset($_POST["WIZ_BUILD_INSTANCE"])){WIZ_BUILD_INSTANCE();exit;}
	if(isset($_GET["wiz2"])){wizard2();exit;}
	if(isset($_GET["wiz3"])){wizard3();exit;}
	if(isset($_GET["wiz4"])){wizard4();exit;}
	if(isset($_GET["progress"])){progress_build();exit;}
	
	
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{welcome_new_instance_wizard}");
	$html="YahooWinBrowse('550','$page?start-ajax=yes','$title');
	if(!document.getElementById('main_config_postfixmultipe')){QuickLinkPostfixMulti();}
	";
	echo $html;
	
	
}



function start_ajax(){
	$page=CurrentPageName();
	$html="<div id='new_instance_wizard'></div>

<script>
	LoadAjax('new_instance_wizard','$page?start=yes');
	
	function WIZMULTI2(){
		LoadAjax('new_instance_wizard','$page?wiz2=yes');
	}
	
	function WIZMULTI3(){
		LoadAjax('new_instance_wizard','$page?wiz3=yes');
	}	
	
	function WIZMULTI4(){
		LoadAjax('new_instance_wizard','$page?wiz4=yes');
	}	
	
</script>";	
	echo $html;
	
}

function start(){
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$tpc=new networking();
	$interfaces=$tpc->Local_interfaces();
	unset($interfaces["lo"]);
	$html="
	<div class=explain style='font-size:14px'>{welcome_new_instance_wizard_intro}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{Interface}:</td>
		<td>". Field_array_Hash($interfaces, "WIZINTERFACE",$_SESSION["WIZINSTANCE"]["WIZINTERFACE"],"style:font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{next}","WIZ_WIZINTERFACE()",16)."</td>
	</tr>
	</tbody>
	</table>
	
	<script>
	var XWIZ_WIZINTERFACE= function (obj) {
	 var results=obj.responseText;
	 if(results.length>3){alert(results);return;}
	 	WIZMULTI2();
	}	
	
	function WIZ_WIZINTERFACE(){
		var XHR = new XHRConnection();
		XHR.appendData('WIZINTERFACE',document.getElementById('WIZINTERFACE').value);
		AnimateDiv('new_instance_wizard');
		XHR.sendAndLoad('$page', 'POST',XWIZ_WIZINTERFACE);	
	}
	
	</script>
		
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function wizard2(){
	if($_SESSION["WIZINSTANCE"]["WIZINTERFACE"]==null){start();exit;}
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$tpc=new networking();
	$page=CurrentPageName();
	$tpl=new templates();
	
	if(!isset($_SESSION["WIZINSTANCE"]["WIZIPADDR"])){
	$tpc->ifconfig($_SESSION["WIZINSTANCE"]["WIZINTERFACE"]);
		$ipaddr=$tpc->tcp_addr;
		$exploded=explode(".",$ipaddr);
		$lastNumber=$exploded[3];
		$lastNumberPrefix="{$exploded[0]}.{$exploded[1]}.{$exploded[2]}.";
		$iptrue=true;
		while ($iptrue==true) {
			$lastNumber=$lastNumber+1;
			$q=new mysql();
			if($lastNumber>254){break;}
			$newipaddr=$lastNumberPrefix.$lastNumber;
			$sql="SELECT ip_address FROM postfix_multi WHERE ip_address='$newipaddr'";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(trim($ligne["ip_address"]==null)){break;}
		}
		
		$network=$tpc->netmask;
		$gw=$tpc->gateway;
	
	}else{
		$newipaddr=$_SESSION["WIZINSTANCE"]["WIZIPADDR"];
		$network=$_SESSION["WIZINSTANCE"]["WIZIPMASK"];
		$gw=$_SESSION["WIZINSTANCE"]["WIZIPGW"];
	}
	
	
	
	$html="<strong style='font-size:16px'>{Interface}:{$_SESSION["WIZINSTANCE"]["WIZINTERFACE"]}</strong>
	<div class=explain style='font-size:14px'>{welcome_new_instance_wizard_interface}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{ipaddr}:</td>
		<td>". field_ipv4("WIZIPADDR", $newipaddr,"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{netmask}:</td>
		<td>". field_ipv4("WIZIPMASK", $network,"font-size:14px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{gateway}:</td>
		<td>". field_ipv4("WIZIPGW", $gw,"font-size:14px")."</td>
	</tr>	
		<tr>
		<td colspan=2><hr></td>
	</tR>
	<tr>
		<td align='left'>". button("{previous}","LoadAjax('new_instance_wizard','$page?start=yes');",16)."</td>
		<td align='right'>". button("{next}","WIZ_WIZIPSAVE()",16)."</td>
	</tr>
	</tbody>
	</table>	
	<script>
	var XWIZ_WIZIPSAVE= function (obj) {
	 var results=obj.responseText;
	 if(results.length>3){alert(results);return;}
	 	WIZMULTI3();
	}	
	
	function WIZ_WIZIPSAVE(){
		var XHR = new XHRConnection();
		XHR.appendData('WIZIPADDR',document.getElementById('WIZIPADDR').value);
		XHR.appendData('WIZIPMASK',document.getElementById('WIZIPMASK').value);
		XHR.appendData('WIZIPGW',document.getElementById('WIZIPGW').value);
		AnimateDiv('new_instance_wizard');
		XHR.sendAndLoad('$page', 'POST',XWIZ_WIZIPSAVE);	
	}
	
	</script>	
	";	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function wizard3(){
	if(!isset($_SESSION["WIZINSTANCE"]["WIZIPADDR"])){wizard2();exit;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$ldap=new clladp();
	$ous=$ldap->hash_get_ou(true);
	$ous[null]="{none}";
	
	
	
$html="<strong style='font-size:16px'>{Interface}:{$_SESSION["WIZINSTANCE"]["WIZINTERFACE"]}&nbsp;|&nbsp;{$_SESSION["WIZINSTANCE"]["WIZIPADDR"]}/{$_SESSION["WIZINSTANCE"]["WIZIPMASK"]}</strong>
	<div class=explain style='font-size:14px'>{welcome_new_instance_wizard_organdname}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{organization}:</td>
		<td>". Field_array_Hash($ous, "WIZOU",$_SESSION["WIZINSTANCE"]["WIZOU"],"style:font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". field_text("WIZHOST",$_SESSION["WIZINSTANCE"]["WIZHOST"],"font-size:16px;width:240px;font-weight:bolder")."</td>
	</tr>	
	<tr>
	<tr>
		<td colspan=2><hr></td>
	</tr>
	<tr>
	
		<td align='left'>". button("{previous}","LoadAjax('new_instance_wizard','$page?wiz2=yes');",16)."</td>
		<td colspan=2 align='right'>". button("{next}","WIZ_WIZHOST()",16)."</td>
	</tr>
	</tbody>
	</table>
	<script>
	var XWIZ_WIZ_WIZHOST= function (obj) {
	 var results=obj.responseText;
	 if(results.length>3){alert(results);return;}
	 	WIZMULTI4();
	}	
	
	function WIZ_WIZHOST(){
		var XHR = new XHRConnection();
		XHR.appendData('WIZOU',document.getElementById('WIZOU').value);
		XHR.appendData('WIZHOST',document.getElementById('WIZHOST').value);
		AnimateDiv('new_instance_wizard');
		XHR.sendAndLoad('$page', 'POST',XWIZ_WIZ_WIZHOST);	
	}
	
	</script>			
	";

echo $tpl->_ENGINE_parse_body($html);
	
}

function wizard4(){
	if(!isset($_SESSION["WIZINSTANCE"]["WIZHOST"])){wizard3();exit;}
	$page=CurrentPageName();
	$tpl=new templates();	
	if($_SESSION["WIZINSTANCE"]["WIZHOST"]==null){$_SESSION["WIZINSTANCE"]["WIZHOST"]=time().".domain.tld";}
	$ou=$_SESSION["WIZINSTANCE"]["WIZOU"];
	if($ou==null){$ou="{none}";}
	
	
	
	$html="<div class=explain style='font-size:14px'>{welcome_new_instance_wizard_finish}</div>
	<div id='buildinstance-progress'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{ipaddr} {virtual}:</td>
		<td style='font-size:14px;font-weight:bold'>{$_SESSION["WIZINSTANCE"]["WIZIPADDR"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{netmask}:</td>
		<td style='font-size:14px;font-weight:bold'>{$_SESSION["WIZINSTANCE"]["WIZIPMASK"]}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{gateway}:</td>
		<td style='font-size:14px;font-weight:bold'>{$_SESSION["WIZINSTANCE"]["WIZIPGW"]}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{organization}:</td>
		<td style='font-size:14px;font-weight:bold'>$ou</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td style='font-size:14px;font-weight:bold'>{$_SESSION["WIZINSTANCE"]["WIZHOST"]}</td>
	</tr>	
	<tr>
		<td colspan=2><hr></td>
	</tR>
	<tr>
	
		<td align='left'>". button("{previous}","LoadAjax('new_instance_wizard','$page?wiz3=yes');",16)."</td>
		<td colspan=2 align='right'>". button("{build_instance}","WIZ_BUILD()",16)."</td>
	</tr>	
	
	</tbody>
	<table>
	
	<script>
var XWIZ_WIZ_BUILD= function (obj) {
	 var results=obj.responseText;
	 if(results.length>3){alert(results);
	 	LoadAjaxTiny('buildinstance-progress','$page?progress=0');
		return;
	}
	 LoadAjaxTiny('buildinstance-progress','$page?progress=50');
	 WIZ_BUILD_INSTANCE();
	}		
	
	function WIZ_BUILD(){
		var XHR = new XHRConnection();
		XHR.appendData('WIZ_BUILD','yes');
		XHR.sendAndLoad('$page', 'POST',XWIZ_WIZ_BUILD);	
	}
	
var XWIZ_BUILD_INSTANCE=function (obj) {
	 var results=obj.responseText;
	 if(results.length>3){alert(results);
	 	LoadAjaxTiny('buildinstance-progress','$page?progress=0');
		return;
	}
	 LoadAjaxTiny('buildinstance-progress','$page?progress=100');
	 if(document.getElementById('multiples-instances-list-start')){RefreshTableMultiples();}
	  $('#table-postfix-multiples-instances').flexReload();
	}	
	
	function WIZ_BUILD_INSTANCE(){
		var XHR = new XHRConnection();
		XHR.appendData('WIZ_BUILD_INSTANCE','yes');
		XHR.sendAndLoad('$page', 'POST',XWIZ_BUILD_INSTANCE);	
	
	}
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function progress_build(){
	$tpl=new templates();
	echo "<center>";
	echo pourcentage($_GET["progress"]);
	echo "</center>";
	if($_GET["progress"]==100){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<center><hr><strong style='font-size:16px;color:red;margin:15px'>{success}</strong><hr></center>");
		$_SESSION["WIZINSTANCE"]=array();
	}
	
	
}
function WIZ_BUILD_IPADDR(){
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	
	$_GET["virt-ipaddr"]=$_SESSION["WIZINSTANCE"]["WIZIPADDR"];
	$_GET["nic"]=$_SESSION["WIZINSTANCE"]["WIZINTERFACE"];
	$_GET["org"]=$_SESSION["WIZINSTANCE"]["WIZOU"];
	$_GET["netmask"]=$_SESSION["WIZINSTANCE"]["WIZIPMASK"];
	$_GET["gateway"]=$_SESSION["WIZINSTANCE"]["WIZIPGW"];
	$_GET["cdir"]=null;
	
	
	if($_GET["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	$PING=trim($sock->getFrameWork("cmd.php?ping=".urlencode($_GET["virt-ipaddr"])));
	
	if($PING=="TRUE"){
		echo $tpl->javascript_parse_text("{$_GET["virt-ipaddr"]}:\n{ip_already_exists_in_the_network}");
		return;
	}
	
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}	
	
	if($NoGatewayForVirtualNetWork==1){$_GET["gateway"]=null;}
	
	$sql="SELECT ID FROM nics_virtuals WHERE ipaddr='{$_GET["virt-ipaddr"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$_GET["ID"]=$ligne["ID"];
	
	$sql="
	INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway)
	VALUES('{$_GET["nic"]}','{$_GET["org"]}','{$_GET["virt-ipaddr"]}','{$_GET["netmask"]}','{$_GET["cdir"]}','{$_GET["gateway"]}');
	";
	
	if($_GET["ID"]>0){
		$sql="UPDATE nics_virtuals SET nic='{$_GET["nic"]}',
		org='{$_GET["org"]}',
		ipaddr='{$_GET["virt-ipaddr"]}',
		netmask='{$_GET["netmask"]}',
		cdir='{$_GET["cdir"]}',
		gateway='{$_GET["gateway"]}' WHERE ID={$_GET["ID"]}";
	}
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}


function WIZ_BUILD_INSTANCE(){
	
	$q=new mysql();
	$sock=new sockets();
	$inet_interfaces=$_SESSION["WIZINSTANCE"]["WIZIPADDR"];
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$_GET["hostname"]=trim($_SESSION["WIZINSTANCE"]["WIZHOST"]);
	$_GET["ou"]=$_SESSION["WIZINSTANCE"]["WIZOU"];
	
	$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname' AND `value`='{$_GET["hostname"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["value"]<>null){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{servername_already_used}");
		return;
	}
	
	
	$sql="INSERT INTO  postfix_multi (`uuid`,`ou`,`key`,`value`,`ip_address`) VALUES('$uuid','{$_GET["ou"]}','inet_interfaces','$inet_interfaces','$inet_interfaces');";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql\n$q->mysql_error\n";return;}
	
	$sql="INSERT INTO  postfix_multi (`uuid`,`ou`,`key`,`value`,`ip_address`) VALUES('$uuid','{$_GET["ou"]}','myhostname','{$_GET["hostname"]}','$inet_interfaces');";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql\n$q->mysql_error\n";return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-postfix-single-now=yes");
}