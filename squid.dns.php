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
	
	if(isset($_GET["details-tablerows"])){details_tablerows();exit;}
	if(isset($_POST["nameserver"])){dns_add();exit;}
	if(isset($_POST["DnsDelete"])){dns_del();exit;}
	
table();

function dns_add(){
	$squid=new squidbee();
	$squid->dns_array[]=$_POST["nameserver"];
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		exit;
	}
	
}
function dns_del(){
	$squid=new squidbee();
	unset($squid->dns_array[$_POST["DnsDelete"]]);
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		exit;
	}
	
}


function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$dns_nameservers=$tpl->javascript_parse_text("{dns_nameservers}");
	$new_dns=$tpl->_ENGINE_parse_body("{new_dns_server}");
	$EnableOpenDNSInProxy=$sock->GET_INFO("EnableOpenDNSInProxy");
	$restart_service=$tpl->javascript_parse_text("{restart_service}");


	$buttons="
	buttons : [
		{name: '$new_dns', bclass: 'add', onpress : dnsadd},
		{name: '$restart_service', bclass: 'ReConf', onpress : RestartService$t},
	],";

	if($EnableOpenDNSInProxy==1){
		$js_add="DisableStandardProxyDns()";
		$buttons=null;
		$texttoadd=$tpl->_ENGINE_parse_body("<br><span style='font-size:14px;color:#9B2222'>{currently_user_opendns_service}</span>");
	
	}	

$html="	$texttoadd<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var xmemnum=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?details-tablerows=yes&t=$t&field={$_GET["field"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :45, sortable : false, align: 'center'},
		{display: '$dns_nameservers', name : 'server', width :285, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width :45, sortable : false, align: 'center'},
	],
	$buttons
	sortname: 'zDate',
	sortorder: 'asc',
	usepager: false,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 430,
	height: 250,
	singleSelect: true
	
	});   
});

function RestartService$t(){
	Loadjs('squid.restart.php?onlySquid=yes');

}
		var x_dnsadd= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#table-$t').flexReload();
			
		}
		
		var x_dnsdel= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#rowsquid-dns-'+xmemnum).remove();
			
		}		
		
		function dnsadd(){
			var nameserver=prompt('$dns_nameservers:');
			if(nameserver){
				var XHR = new XHRConnection();
				XHR.appendData('nameserver',nameserver);
				XHR.sendAndLoad('$page', 'POST',x_dnsadd);	
			}
		}
		
		function DnsDelete$t(num){
			xmemnum=num;
			var XHR = new XHRConnection();
			XHR.appendData('DnsDelete',num);
			XHR.sendAndLoad('$page', 'POST',x_dnsdel);	
		}


</script>";
echo $html;

}
function details_tablerows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$squid=new squidbee();
	$t=$_GET["t"];
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($squid->dns_array);
	$data['rows'] = array();
	
	

		while (list ($num, $nameserver) = each ($squid->dns_array) ){
			$delete=imgtootltip('delete-24.png','{delete}',"DnsDelete$t($num)");
			
			$data['rows'][] = array(
				'id' => "squid-dns-$num",
				'cell' => array(
					"<span style='font-size:12.5px'><img src='img/32-samba-pdc.png'></span>",
					"<span style='font-size:16px'>$nameserver</span>",
					"<span style='font-size:12.5px'>$delete</span>",
				)
			);
		}
	
	
echo json_encode($data);		
}