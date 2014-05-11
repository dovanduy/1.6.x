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
	
	if(isset($_GET["list"])){interfaces_list();exit;}
	if(isset($_GET["hosts"])){Loadaddresses();exit;}
	if(isset($_GET["DnsmasqDeleteAddress"])){DnsmasqDeleteAddress();exit();}
	if(isset($_GET["add-dnsmasq-js"])){add_dnsmasq_js();exit;}
	if(isset($_GET["add-dnsmasq-interface"])){add_dnsmasq_interface();exit;}
	if(isset($_GET["import-dnsmasq-popup"])){import_dnsmasq_popup();exit;}
	if(isset($_POST["interfaces"])){interfaces_add();exit;}
	table();
	
	
function table(){
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
		$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
		$hosts=$tpl->_ENGINE_parse_body("{hosts}");
		$addr=$tpl->_ENGINE_parse_body("{addr}");
		$new_interface=$tpl->_ENGINE_parse_body("{new_interface}");
		$interface=$tpl->_ENGINE_parse_body("{interface}");
		$title=$tpl->_ENGINE_parse_body("{network}");
		$buttons="
		buttons : [
		{name: '$new_interface', bclass: 'add', onpress : Add$t},
		],";
	
		$html="
	
		
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
		url: '$page?list=yes',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : '&nbsp;', width : 50, sortable : false, align: 'centers'},
		{display: '$interface', name : 'hosts', width : 786, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'left'},
		],
		$buttons

		sortname: 'hostname',
		sortorder: 'asc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 10,
		showTableToggleBtn: false,
		width: '99%',
		height: 300,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	
	function Add$t(){
	YahooWin2('650','$page?add-dnsmasq-interface=yes&t=$t','$new_interface',true);
	}
	
	function Import$t(){
	YahooWin2('550','$page?import-dnsmasq-popup=yes&t=$t','$import');
	}
	
function DnsmasqDeleteInterface(num){
	var XHR = new XHRConnection();	
	XHR.appendData('DnsmasqDeleteInterface',num);	
	XHR.sendAndLoad('dnsmasq.dns.settings.php', 'GET',xDnsmasqDeleteInterface);	
	
	
}	
	
var xDnsmasqDeleteInterface= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT$t').flexReload();
}
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function interfaces_list(){
	$tpl=new templates();
	$conf=new dnsmasq();
	if( !is_array($conf->array_interface) OR count($conf->array_interface)==0  ){
		
		$data['rows'][] = array(
				'id' => "null",
				'cell' => array(
						"<span style='font-size:18px;font-weight:bold'><img src='img/folder-network-48.png'></a></span>"
						,"<span style='font-size:32px'>". $tpl->_ENGINE_parse_body("{all}")."</a></span>",
						null )
		);
		$data['page'] = 1;
		$data['total'] = 1;
		echo json_encode($data);
		return;
		
	}
	
	$page=1;
	
	
	$c=0;
	while (list ($index, $line) = each ($conf->array_interface) ){
		if(trim($line)==null){continue;}
		if(isset($aL[$line])){continue;}
		$md5=md5("$index$line");
		$aL[$line]=true;
		
		$net=new system_nic($line);
		
		
		$c++;
		$delete=imgsimple('delete-48.png','{delete}',"DnsmasqDeleteInterface('$index');");
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:16px;font-weight:bold'><img src='img/folder-network-48.png'></a></span>"
						,"<span style='font-size:32px'>$line - $net->IPADDR $net->NICNAME</a></span>",
						$delete )
		);
			
	}
	if($c==0){

			$data['rows'][] = array(
					'id' => "null",
					'cell' => array(
							"<span style='font-size:18px;font-weight:bold'><img src='img/folder-network-48.png'></a></span>"
							,"<span style='font-size:32px'>". $tpl->_ENGINE_parse_body("{all}")."</a></span>",
							null )
			);
			$data['page'] = 1;
			$data['total'] = 1;
			echo json_encode($data);
			return;
		
		}
		
		
	
	$data['page'] = $page;
	$data['total'] = $c;
	echo json_encode($data);
}

function add_dnsmasq_interface(){
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$t=time();
	$sys->array_interfaces[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,"interfaces-$t",null,"style:font-size:26px;padding:3px;");
	



	$html="<div style='font-size:22px'>{interface} {dnsmasq_listen_address}</div>
	
		<center style='width:98%' class=form>
		<table>
			<tr>
				<td valign='middle' class=legend nowrap style='font-size:16px'>{interface}:</td>
				<td valign='middle'>$interfaces</td>
				<td width=1%>". help_icon("{dnmasq_interface_text}")."</td>
			</tr>
			<tr>
				<td colspan=3 align=right><hr>". button("{add}", "Save$t()",32)."</td>
			</tr>
		</table>
		
</center>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT{$_GET["t"]}').flexReload();
	YahooWin2Hide();
}		
	
	
function Save$t(){	
	var XHR = new XHRConnection();
	XHR.appendData('interfaces',document.getElementById('interfaces-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
</script>						
						
";
echo $tpl->_ENGINE_parse_body($html);

}

function interfaces_add(){
	$conf=new dnsmasq();
	$conf->array_interface[]=$_POST["interfaces"];
	$conf->SaveConf();
		
}