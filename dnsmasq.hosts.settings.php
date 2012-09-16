<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');

	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDnsAdministrator==false){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
			die();exit();
		}
	}	
	
	if(isset($_GET["address_server"])){SaveAddress();exit;}
	if(isset($_GET["hosts"])){Loadaddresses();exit;}
	if(isset($_GET["DnsmasqDeleteAddress"])){DnsmasqDeleteAddress();exit();}
	if(isset($_GET["add-dnsmasq-js"])){add_dnsmasq_js();exit;}
	if(isset($_GET["add-dnsmasq-popup"])){add_dnsmasq_popup();exit;}
	if(isset($_GET["import-dnsmasq-popup"])){import_dnsmasq_popup();exit;}
	if(isset($_POST["BulkImport"])){import_dnsmasq_perform();exit;}
	page();

	
	
function import_dnsmasq_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$time=time();
	$t=$_GET["t"];
	$html="
	<div class=explain style='font-size:13px'>{dnsmasq_bulk_import_explain}</div>
	<center id='id-$time'>
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>
			<textarea id='bulk-import$t' style='font-size:16px;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:220px;overflow:auto'></textarea></td>
			
			
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","BulkImport$t()",16)."</td>
		</tr>
		</tbody>
	</table>
	</center>			
	<script>
	var x_BulkImport$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			FlexReloadDNSMASQHOSTS();
			YahooWin2Hide();
			}		
	
	
		function BulkImport$t(){	
			var XHR = new XHRConnection();
			XHR.appendData('BulkImport',document.getElementById('bulk-import$t').value);
			AnimateDiv('id-$time');
			XHR.sendAndLoad('$page', 'POST',x_BulkImport$t);		
		}
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}	
	
function add_dnsmasq_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$time=time();
	$t=$_GET["t"];
	$html="
	<center id='id-$time'>
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{domain_or_server}</td>
			<td>" . Field_text("address_server-$time",null,"font-size:14px;padding:3px;width:270px") . "</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{ip}</td>
			<td>" . field_ipv4("address_ip-$time",null,"font-size:14px") . "</td>
		</tr>	
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","AddDnsMasqHost()",16)."</td>
		</tr>
		</tbody>
	</table>
	</center>			
	<script>
		var x_AddDnsMasqHost= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			FlexReloadDNSMASQHOSTS();
			$('#flexRT$t').flexReload();
			YahooWin2Hide();
			}		
	
	
		function AddDnsMasqHost(){	
			var XHR = new XHRConnection();
			XHR.appendData('add-host','yes');
			XHR.appendData('address_server',document.getElementById('address_server-$time').value);
			XHR.appendData('ipaddr',document.getElementById('address_ip-$time').value);
			AnimateDiv('id-$time');
			XHR.sendAndLoad('$page', 'GET',x_AddDnsMasqHost);		
		}
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}	
	
function add_dnsmasq_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{host}::{add}");
	$html="YahooWin4('550','$page?add-dnsmasq-popup=yes','$title');";	
	echo $html;
}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$buttons="
	buttons : [
	{name: '$new_computer', bclass: 'add', onpress : AddHost$t},
	{name: '$import', bclass: 'add', onpress : Import$t},
	
	
	],";	
	
	$html="

<div class=explain style='font-size:13px'>$dnsmasq_address_text</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
var md5H='';
$('#flexRT$t').flexigrid({
	url: '$page?hosts=yes',
	dataType: 'json',
	colModel : [
		{display: '$hosts', name : 'hosts', width : 634, sortable : false, align: 'left'},	
		{display: '$addr', name : 'description', width :130, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 46, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$hosts', name : 'hosts'},
		{display: '$addr', name : 'addr'},
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: 865,
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function FlexReloadDNSMASQHOSTS(){
		$('#flexRT$t').flexReload();
	}

	
	function DnsmasqDeleteAddress(md5,num){
		md5H=md5;
		var XHR = new XHRConnection();	
		XHR.appendData('DnsmasqDeleteAddress',num);	
		XHR.sendAndLoad('$page', 'GET',x_AddDnsMasqHostT);	
	}	
	
	function AddHost$t(){
		YahooWin2('550','$page?add-dnsmasq-popup=yes&t=$t','$new_computer');
	}
	
	function Import$t(){
		YahooWin2('550','$page?import-dnsmasq-popup=yes&t=$t','$import');
	}
	
	

	var x_AddDnsMasqHostT= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+md5H).remove();
	}			

</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}	


function SaveAddress(){
	$ip=new networking();
	$server=$_GET["address_server"];
	if(trim($server)==null){echo "Host cannot be null!\n";return;}
	$adip=$_GET["ipaddr"];
	if(!$ip->checkIP($adip)){echo "IP $adip\nFailed";return;}
	$conf=new dnsmasq();
	$conf->array_address[$server]=$adip;
	writelogs("save $server $adip",__FUNCTION__,__FILE__);
	$conf->SaveConf();
	}
	
function import_dnsmasq_perform(){
	$ip=new networking();
	$conf=new dnsmasq();
	$tbl=explode("\n", $_POST["BulkImport"]);
	writelogs(count($tbl)." line",__FUNCTION__,__FILE__);
	while (list ($num, $ligne) = each ($tbl) ){
		if(!preg_match("#(.+?)(,|;)(.+)#", $ligne,$re)){writelogs("$ligne no match ",__FUNCTION__,__FILE__);continue;}
		$server=trim(strtolower($re[1]));
		$adip=trim($re[3]);
		if(!$ip->checkIP($adip)){
			writelogs("IP $adip Failed",__FUNCTION__,__FILE__);
			echo "IP $adip Failed";continue;
		}
		$conf->array_address[$server]=$adip;
		writelogs("save $server $adip",__FUNCTION__,__FILE__);
		
	}
	
	$conf->SaveConf();
	
	
}	
	
	
function DnsmasqDeleteAddress(){
	$conf=new dnsmasq();
	unset($conf->array_address[$_GET["DnsmasqDeleteAddress"]]);
	$conf->SaveConf();	
}

function Loadaddresses(){
		$conf=new dnsmasq();
		$page=1;

		if(!is_array($conf->array_address)){
			writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
			$data['page'] = $page;$data['total'] = 0;$data['rows'] = array();
			echo json_encode($data);
			return ;		
		}
		
	
	if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*", $_POST["query"]);
		
	}
	
		
		if($_POST["qtype"]=="hosts"){
			$regexHost=$_POST["query"];
			
		}else{
			$regexIP=$_POST["query"];
		}
		
		
		$c=0;
		while (list ($index, $line) = each ($conf->array_address) ){
			if($regexHost<>null){if(!preg_match("#$regexHost#", $index)){continue;}}
			
			if($regexIP<>null){if(!preg_match("#$regexIP#", $line)){continue;}}			
			
			$md5=md5("$index$line");
			$c++;
			$delete=imgtootltip('delete-32.png','{delete}',"DnsmasqDeleteAddress('$md5','$index');");
			$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
				"<span style='font-size:16px;font-weight:bold'>$index</a></span>"
				,"<span style='font-size:16px'>$line</a></span>",
				$delete )
				);		
			
		}
	$data['page'] = $page;
	$data['total'] = $c;
		echo json_encode($data);
	
	
}