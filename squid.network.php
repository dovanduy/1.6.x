<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

	if($user->SQUID_INSTALLED==false){
		if(!$user->WEBSTATS_APPLIANCE){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["net-list"])){network_list();exit;}
if(isset($_GET["new-range-popup"])){popup_new_range();exit;}
if(isset($_GET["new-mask-popup"])){popup_new_mask();exit;}
if(isset($_GET["new-free-popup"])){popup_new_free();exit;}



function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
	if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}	
	$networks=$tpl->_ENGINE_parse_body("{network}");
	$new_range=$tpl->_ENGINE_parse_body("{new_range}");
	$new_mask=$tpl->_ENGINE_parse_body("{new_mask}");
	$squid_net_simple=$tpl->javascript_parse_text("{squid_net_simple}");
	$squid_net_calc_mask=$tpl->javascript_parse_text("{squid_net_calc_mask}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$free_pattern=$tpl->javascript_parse_text("{free_pattern}");
	$page=CurrentPageName();
	$t=time();
	
	$form="<table style='width:99%' class=form>
		<tr>
			<td class=legend>{AllowAllNetworks}:</td>
			<td>". Field_checkbox("AllowAllNetworksInSquid",1,$AllowAllNetworksInSquid,"AllowAllNetworksInSquidSave()")."</td>
		</tr>
		</table>";
	
	
$buttons="buttons : [
	{name: '$new_range', bclass: 'add', onpress : AddnewRange$t},
	{name: '$new_mask', bclass: 'add', onpress : AddnewMask$t},
	{name: '$new_item', bclass: 'add', onpress : AddnewItem$t},
		],	";	
	
	$form=$tpl->_ENGINE_parse_body($form);
$html="$form
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?net-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'client', width :35, sortable : false, align: 'left'},
		{display: '$networks', name : 'sitename', width :350, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'client', width :35, sortable : false, align: 'left'},

	],
$buttons
	searchitems : [
		{display: '$networks', name : 'network'},
		],
	sortname: 'HitsNumber',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: 480,
	height: 250,
	singleSelect: true
	
	});   
});

		var x_netadd$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			$('#table-$t').flexReload();
			YahooWin4Hide();		
		}
		
	function AddnewRange$t(){
		YahooWin4('465','$page?new-range-popup=yes&t=$t','$new_range::$squid_net_simple');
	
	}
	
	function AddnewMask$t(){
		YahooWin4('465','$page?new-mask-popup=yes&t=$t','$new_mask::$squid_net_calc_mask');
	
	}	
	function AddnewItem$t(){
		YahooWin4('465','$page?new-free-popup=yes&t=$t','$new_item::$free_pattern');
	
	}		
	
	function NetDelete$t(num){
		var XHR = new XHRConnection();
		XHR.appendData('NetDelete',num);
		XHR.sendAndLoad('squid.popups.php', 'GET',x_netadd$t);	
	}

	function AllowAllNetworksInSquidSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('AllowAllNetworksInSquid').checked){XHR.appendData('AllowAllNetworksInSquid',1);}else{XHR.appendData('AllowAllNetworksInSquid',0);}
		XHR.sendAndLoad('squid.popups.php', 'GET',x_netadd$t);
	}
	


</script>
";

echo $html;
	
}

function popup_new_free(){
	$tpl=new templates();
	$t=$_GET["t"];
	
	$html="
	<div id='div$t$t'>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>{pattern}:</td>
			<td>". Field_text("FREE_FIELD-$t",null,"font-size:14px;padding:3px",null,null,null,false,"SquidnetaddSingleCheck$t(event)")."</td>
			<td width=1%>". help_icon("{SQUID_NETWORK_HELP}")."</td>
		</tr>
		<tr>
			<td colspan=3 align='right'><hr>". button("{add}","SquidnetaddSingle$t()",16)."
		</tr>
	</table>	
	<script>
		function SquidnetaddSingleCheck$t(e){
			if(checkEnter(e)){SquidnetaddSingle$t();}
		}
		
		function SquidnetaddSingle$t(){
			var XHR = new XHRConnection();
			XHR.appendData('add-ip-single',document.getElementById('FREE_FIELD-$t').value);
			AnimateDiv('div$t$t');
			XHR.sendAndLoad('squid.popups.php', 'GET',x_netadd$t);	
		}
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function popup_new_range(){
	$tpl=new templates();
	$t=$_GET["t"];
	
	$html="
	<div id='div$t$t'>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend nowrap style='font-size:13px'>{from_ip}:</td>
			<td>" . field_ipv4("from_ip-$t",null,';font-size:14px;')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:13px'>{to_ip}:</td>
			<td>" . field_ipv4("to_ip-$t",null,';font-size:14px;')."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","Netadd$t()",16)."
		</tr>
	</table>	
	<script>
	function Netadd$t(){
			var XHR = new XHRConnection();
			XHR.appendData('addipfrom',document.getElementById('from_ip-$t').value);
			XHR.appendData('addipto',document.getElementById('to_ip-$t').value);
			AnimateDiv('div$t$t');
			XHR.sendAndLoad('squid.popups.php', 'GET',x_netadd$t);		
	}
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function popup_new_mask(){
	$tpl=new templates();
	$t=$_GET["t"];
	
	$html="
	<div id='div$t$t'>
		<table class=form style='width:99%'>
		<tr>
			<td class=legend style='font-size:14px' nowrap>{ip_address}:</td>
			<td>". Field_ipv4("IP_NET_FIELD-$t",null,"font-size:14px;padding:3px",true)."</td>
			<td width=1%></td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px' nowrap>{netmask}:</td>
			<td>". Field_ipv4("IP_NET_MASK-$t",null,"font-size:14px;padding:3px",null,null,null,false,"SquidnetMaskCheck$t(event)")."</td>
			<td width=1%></td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px' nowrap>{results}:</td>
			<td style='font-size:13px'><input type='hidden' value='' id='IP_NET_CALC-$t'><span id='IP_NET_CALC_TEXT-$t' style='font-size:16px'></span></td>
			<td width=1%>". imgtootltip("img_calc_icon-16.gif","{results}","SquidnetMaskCheck$t()")."</td>
		</tr>
		
		<tr>
			<td colspan=3 align='right'><hr>". button("{add}","SquidnetMaskAddT$t()",16)."</td>
		</table>	
	<script>
	
	 var x_SquidnetMaskCheck$t=function(obj){
     		var tempvalue=obj.responseText;
      		if(tempvalue.length>3){
     			document.getElementById('IP_NET_CALC_TEXT-$t').innerHTML=tempvalue;
     			document.getElementById('IP_NET_CALC-$t').value=tempvalue;
			}
       }	
	
	
	  var x_SquidnetMaskCheckT$t=function(obj){
     		var tempvalue=obj.responseText;
      		if(tempvalue.length>3){
     			document.getElementById('IP_NET_CALC_TEXT-$t').innerHTML=tempvalue;
     			document.getElementById('IP_NET_CALC-$t').value=tempvalue;
     			SquidnetMaskAdd$t();
			}
       }       
       
	function SquidnetMaskAdd$t(){
		var XHR = new XHRConnection();
		XHR.appendData('add-ip-single',document.getElementById('IP_NET_CALC-$t').value);
		AnimateDiv('div$t$t');
		XHR.sendAndLoad('squid.popups.php', 'GET',x_netadd$t);
	}
	
	function SquidnetMaskAddT$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidnetMaskCheckIP',document.getElementById('IP_NET_FIELD-$t').value);
		XHR.appendData('SquidnetMaskCheckMask',document.getElementById('IP_NET_MASK-$t').value);
		AnimateDiv('IP_NET_CALC_TEXT');
		XHR.sendAndLoad('squid.popups.php', 'GET',x_SquidnetMaskCheckT$t);		
	}
	
	
	function SquidnetMaskCheck$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidnetMaskCheckIP',document.getElementById('IP_NET_FIELD-$t').value);
		XHR.appendData('SquidnetMaskCheckMask',document.getElementById('IP_NET_MASK-$t').value);
		AnimateDiv('IP_NET_CALC_TEXT');
		XHR.sendAndLoad('squid.popups.php', 'GET',x_SquidnetMaskCheck$t);		
	
	}	
	
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function network_list(){
	$sock=new sockets();
	$tpl=new templates();
	$t=$_GET["t"];
	$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
	if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}	
	$squid=new squidbee();
	if($AllowAllNetworksInSquid==1){$squid->network_array[-1]=$tpl->_ENGINE_parse_body("{AllowAllNetworks}");}
	$search=null;
	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$categorize_all="CategorizeAll('{$_POST["query"]}');";
		$_POST["query"]=str_replace("*", ".*", $_POST["query"]);
		$search=$_POST["query"];
	}	
	
	if(count($squid->network_array)==0){$data['page'] = 1;$data['total'] = 0;$data['rows'] = array();echo json_encode($data);return ;}
	$c=0;
	while (list ($num, $ligne) = each ($squid->network_array) ){
		if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
		$c++;
		$delete=imgtootltip('delete-24.png','{delete}',"NetDelete$t($num)");
		if($num==-1){$delete="&nbsp;";}
		$data['rows'][] = array(
			'id' => $num,
			'cell' => array(
				"<img src='img/network-1.gif'>",
				 "<strong style='font-size:16px;$color'>$ligne</strong>",
				$delete)
			);		
		}
		
		$data['total'] =$c;
		$data['page'] = 1;
echo json_encode($data);		
	
}
