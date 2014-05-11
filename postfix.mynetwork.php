<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.mysql.inc');
	
	
$usersmenus=new usersMenus();
if(isset($_GET["mynet_ipfrom"])){CalculCDR();exit;}
if(!$usersmenus->AsPostfixAdministrator){$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["Firewall-js"])){firewall_js();exit;}
if(isset($_GET["firewall-popup"])){firewall_popup();exit;}
if(isset($_GET["mynet_ipfrom"])){CalculCDR();exit;}
if(isset($_GET["PostfixAddMyNetwork"])){PostfixAddMyNetwork();exit;}
if(isset($_GET["network-list"])){network_list();exit;}
if(isset($_GET["new-range"])){new_range_popup();exit;}
if(isset($_GET["new-address"])){new_address_popup();exit;}
if(isset($_POST["PostfixBannet"])){PostfixBannet();exit;}
if(isset($_POST["PostFixLimitToNets"])){firewall_save();exit;}
if(isset($_GET["PostFixDeleteMyNetwork"])){PostFixDeleteMyNetwork();exit;}
page();

function PostFixDeleteMyNetwork(){
	$main=new main_cf();
	$sock=new sockets();
	$PostfixBadNettr=unserialize(base64_decode($sock->GET_INFO("PostfixBadNettr")));
	unset($PostfixBadNettr[$main->array_mynetworks[$_GET["PostFixDeleteMyNetwork"]]]);
	$sock->SaveConfigFile(base64_encode(serialize($PostfixBadNettr)), "PostfixBadNettr");		
	$main->delete_my_networks($_GET["PostFixDeleteMyNetwork"]);
	$sock->getFrameWork("cmd.php?postfix-networks=yes");
}

function PostfixAddMyNetwork(){
	$main=new main_cf();
	$main->add_my_networks($_GET["PostfixAddMyNetwork"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-networks=yes");
	}
	
function firewall_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{firewall}");
	echo "YahooWin2(700,'$page?firewall-popup=yes','$title',true)";
	
}

function firewall_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$PostFixLimitToNets=$sock->GET_INFO("PostFixLimitToNets");
	if(!is_numeric($PostFixLimitToNets)){$PostFixLimitToNets=0;}
	$p=Paragraphe_switch_img("{limit_connections_to_these_networks}", "{limit_connections_to_these_networks_explain}",
	"PostFixLimitToNets-$t",$PostFixLimitToNets,null,600);
	
	$html="<div style='width:98%' class=form>
		$p
		<div style='text-align:right'>". button("{apply}","Save$t()",18)."</div>
				
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		YahooWin5Hide();
		$('#flexRT$t').flexReload();
	}	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('PostFixLimitToNets',document.getElementById('PostFixLimitToNets-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}		
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function firewall_save(){
	$sock=new sockets();
	$sock->SET_INFO("PostFixLimitToNets", $_POST["PostFixLimitToNets"]);
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");
}

function page(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$networks=$tpl->javascript_parse_text("{networks}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$title=$tpl->javascript_parse_text("{mynetworks_title}");
	$new_range=$tpl->javascript_parse_text("{new_range}");
	$new_address=$tpl->javascript_parse_text("{new_address}");
	$disable=$tpl->javascript_parse_text("{disable}");
	$firewall=$tpl->javascript_parse_text("{firewall}");
	$buttons="
		buttons : [
		{name: '$new_range', bclass: 'add', onpress : AddNetworkRange$t},
		{name: '$new_address', bclass: 'add', onpress : AddNetworkAddress$t},
		{name: '$firewall', bclass: 'add', onpress : Firewall$t},
		],";
		
	
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var pstfixmd='';

function start$t(){
$('#flexRT$t').flexigrid({
	url: '$page?network-list=yes&hostname={$_GET["hostname"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none0', width : 32, sortable : false, align: 'left'},	
		{display: '$networks', name : 'pattern', width :203, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width :258, sortable : true, align: 'left'},
		{display: '$disable', name : 'ban', width : 49, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 40, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$networks', name : 'netwrok'},
		
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 1024,
	showTableToggleBtn: false,
	width: 660,
	height: 234,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,1024]
	
	});   
}

function AddNetworkRange$t(){
	YahooWin5('550','$page?new-range=yes&hostname={$_GET["hostname"]}&t=$t','$new_range');
}

function AddNetworkAddress$t(){
	YahooWin5('550','$page?new-address=yes&hostname={$_GET["hostname"]}&t=$t','$new_address');

}

var xPostfixBannet= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}

function Firewall$t(){
	Loadjs('$page?Firewall-js=yes',true);
}


function PostfixBannet(md,num){
		var XHR = new XHRConnection();
		if(document.getElementById(md).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
		XHR.appendData('PostfixBannet',num);
		XHR.sendAndLoad('$page', 'POST',xPostfixBannet);

}

var x_PostFixDeleteMyNetwork= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row'+pstfixmd).remove();
}

function PostFixDeleteMyNetwork(md5,num){
	pstfixmd=md5;
	var XHR = new XHRConnection();
	XHR.appendData('PostFixDeleteMyNetwork',num);
	XHR.sendAndLoad('$page', 'GET',x_PostFixDeleteMyNetwork);
}
setTimeout('start$t()',600);

";
	echo $html;
	
}

function PostfixBannet(){
	$sock=new sockets();
	$value=$_POST["value"];
	$PostfixBadNettr=unserialize(base64_decode($sock->GET_INFO("PostfixBadNettr")));
	if($value==0){
		unset($PostfixBadNettr[$_POST["PostfixBannet"]]);
	}else{
		$PostfixBadNettr[$_POST["PostfixBannet"]]=0;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($PostfixBadNettr)), "PostfixBadNettr");
	$sock->getFrameWork("cmd.php?postfix-networks=yes");
}


function new_address_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];		
	
$html="<div style='font-size:14px;' class=explain>{mynetworks_text}</div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{new_address}:</td>
	<td>". Field_text("mynetworks-$t",null,"font-size:16px",null,null,null,false,"PostfixAddMyNetworkCheck$t(event)")."</td>
	<tr><td colspan=2 align='right'><hr>". button("{add}","PostfixAddMyNetwork$t()",16)."</td></tr>
	</table>
<script>
	var x_PostfixAddMyNetwork$t= function (obj) {
		var results=obj.responseText;
		YahooWin5Hide();
		$('#flexRT$t').flexReload();
	}	
	function PostfixAddMyNetwork$t(){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixAddMyNetwork',document.getElementById('mynetworks-$t').value);
		XHR.sendAndLoad('$page', 'GET',x_PostfixAddMyNetwork$t);
	}

	function PostfixAddMyNetworkCheck$t(e){
		if(checkEnter(e)){PostfixAddMyNetwork$t();}
	}
	

	
</script>";
	echo $tpl->_ENGINE_parse_body($html);

}


function new_range_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];	
$html="
<center><span id='mynetworks-text-$t' style='font-size:18px'></span></center>
<div style='font-size:14px;' class=explain>{give_ip_from_ip_to}<br></div>
<input type='hidden' id='mynetworks-$t' value='mynetworks-$t'>
	<table style='width:99%' class=form>
	<tr>
	<td align='right' valign='top' nowrap class=legend style='font-size:14px'>{from}:</td>
	<td>" . 
		field_ipv4("ipfrom-$t",null,'font-size:14px',null,"PostfixCalculateMyNetwork$t()") ."</td>
	</tr>
	<tr>
	<td align='right' valign='top' nowrap class=legend style='font-size:14px'>{to}:</td>
	<td>".field_ipv4("ipto-$t",null,'font-size:14px',null,"PostfixCalculateMyNetwork$t()") ."</td>
	</tr>
	<tr>
	<td align='right' valign='top' nowrap class=legend style='font-size:14px'></td>
	<td align='right'><input type='button' value='{calculate}&raquo;' style='font-size:13px' OnClick=\"PostfixCalculateMyNetwork$t();\"></td>
	</tr>	
	<tr><td colspan=2 align='right'><hr>". button("{add}","PostfixAddMyNetwork$t()",16)."</td></tr>
	</table>
	
<script>
	var x_PostfixCalculateMyNetwork$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('mynetworks-$t').value=trim(results);
		document.getElementById('mynetworks-text-$t').innerHTML=trim(results);
	}
	
	var x_PostfixAddMyNetwork$t= function (obj) {
		var results=obj.responseText;
		YahooWin5Hide();
		$('#flexRT$t').flexReload();
	}	
	function PostfixAddMyNetwork$t(){
		PostfixCalculateMyNetwork$t();
		var XHR = new XHRConnection();
		XHR.appendData('PostfixAddMyNetwork',document.getElementById('mynetworks-$t').value);
		XHR.sendAndLoad('$page', 'GET',x_PostfixAddMyNetwork$t);
	}		
	

	function PostfixCalculateMyNetwork$t(){
		if(!document.getElementById('ipfrom-$t')){return false;}
		var ipfrom=document.getElementById('ipfrom-$t').value;
		var ipto=document.getElementById('ipto-$t').value;
		
		if(ipfrom.length>0){
			var ARRAY=ipfrom.split('\.');
			if(ARRAY.length>3){
				if(ipto.length==0){
					
					if(ARRAY[1]==''){ARRAY[1]=0;}
					if(ARRAY[2]==''){ARRAY[2]=0;}
					ipto=ARRAY[0] + '.' + ARRAY[1] + '.'+ARRAY[2] + '.255';
					document.getElementById('ipto-$t').value=ARRAY[0] + '.' + ARRAY[1] + '.'+ARRAY[2] + '.255';
					
					}
					}else{return false}
		}else{return false;}
		if(ARRAY[1]==''){ARRAY[1]=0;}
		if(ARRAY[2]==''){ARRAY[2]=0;}		
		
		document.getElementById('ipfrom-$t').value=ARRAY[0] + '.' + ARRAY[1] + '.'+ARRAY[2] + '.0';
		ipfrom=ARRAY[0] + '.' + ARRAY[1] + '.'+ARRAY[2] + '.0';
		var XHR = new XHRConnection();
		XHR.appendData('mynet_ipfrom',ipfrom);
		XHR.appendData('mynet_ipto',ipto);
		XHR.sendAndLoad('$page', 'GET',x_PostfixCalculateMyNetwork$t);
		}		
	
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
}
function CalculCDR(){
	$ip=new IP();
	$ipfrom=$_GET["mynet_ipfrom"];
	$ipto=$_GET["mynet_ipto"];
	$SIP=$ip->ip2cidr($ipfrom,$ipto);
	echo trim($SIP);
	}

function network_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$main=new main_cf();
	$total=0;
	$page=1;
	$icon="folder-network-32.png";
	$also=$tpl->_ENGINE_parse_body("{also}");
	$sock=new sockets();
	$MynetworksInISPMode=$sock->GET_INFO("MynetworksInISPMode");
	if(!is_numeric($MynetworksInISPMode)){$MynetworksInISPMode=0;}	
	if($MynetworksInISPMode==1){
		$icon="warning-panneau-32.png";
		$tpl=new templates();
		$explainmore=$tpl->_ENGINE_parse_body("<div>{postfix_mynetwork_isp_why}</div>");
		
	}	
	
	
	$array=$main->array_mynetworks;
	if(!is_array($array)){
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;		
	}
	
	if(count($array)==0){
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;			
	}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*", $_POST["query"]);
		$search=$_POST["query"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$data = array();
	$data['page'] = $page;
	
	$data['rows'] = array();
	
	$PostfixBadNettr=unserialize(base64_decode($sock->GET_INFO("PostfixBadNettr")));	
	
	while (list ($num, $val) = each ($main->array_mynetworks) ){
		$color="black";
		if($search<>null){
			if(!preg_match("#$search#", $val)){continue;}
		}
		
		$sql="SELECT netinfos FROM networks_infos WHERE ipaddr='$val'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ligne["netinfos"]=htmlspecialchars($ligne["netinfos"]);
		$ligne["netinfos"]=nl2br($ligne["netinfos"]);
		if($ligne["netinfos"]==null){$ligne["netinfos"]="{no_info}";}
		$ligne["netinfos"]=$tpl->_ENGINE_parse_body($ligne["netinfos"]);
		$ligne["enabled"]=0;
		$transformed=null;
		$val=trim($val);
		
		if(isset($PostfixBadNettr[$val])){
			if($PostfixBadNettr[$val]==0){
			$ligne["enabled"]=1;
			}else{
				$transformed="$also {$PostfixBadNettr[$val]}";
			}
		}
		$md5=md5($num);
		$delete=imgtootltip('delete-32.png','{delete} {network}',"PostFixDeleteMyNetwork('$md5',$num)");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"PostfixBannet('$md5','$val')");	
		if($ligne["enabled"]==1){$color="#C5C2C2";}
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("
		<img src='img/$icon'>"
		,"<span style='font-size:16px;color:$color'>$val</span>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:GlobalSystemNetInfos('$val')\" 
		style='font-size:12px;text-decoration:underline;color:$color'><i>{$ligne["netinfos"]}</i>$explainmore</a>",$enable,$delete )
		);
	}
	
	$data['total'] = count($data['rows']);
echo json_encode($data);		

}	
	

