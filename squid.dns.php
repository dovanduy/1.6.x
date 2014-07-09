<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$user=new usersMenus();

	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}	
	
	if(isset($_GET["details-tablerows"])){details_tablerows();exit;}
	if(isset($_POST["nameserver"])){dns_add();exit;}
	if(isset($_POST["DnsDelete"])){dns_del();exit;}
	if(isset($_POST["SquidDNSUpDown"])){SquidDNSUpDown();exit;}
	
table();

function dns_add(){
	$_POST["nameserver"]=trim($_POST["nameserver"]);
	$IPClass=new IP();
	if(!$IPClass->isValid($_POST["nameserver"])){
		echo "{$_POST["nameserver"]} invalid\n";
		return;
	}
	$nameserver[$_POST["nameserver"]]=true;
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$_POST["nameserver"]}','1')");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function dns_del(){
	$_POST["DnsDelete"]=trim($_POST["DnsDelete"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM dns_servers WHERE dnsserver LIKE '%{$_POST["DnsDelete"]}%'");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$dns_nameservers=$tpl->javascript_parse_text("{dns_nameservers}");
	$new_dns=$tpl->_ENGINE_parse_body("{new_dns_server}");
	$blacklist=$tpl->_ENGINE_parse_body("{blacklist}");
	$restart_service=$tpl->javascript_parse_text("{restart_service}");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$SQUID_INSTALLED=1;
	$newdns="{name: '$new_dns', bclass: 'add', onpress : dnsadd},";
	if($EnableRemoteStatisticsAppliance==1){$newdns=null;}
	if(!$users->SQUID_INSTALLED){$SQUID_INSTALLED=0;}
	$buttons="
	buttons : [
		$newdns
		{name: '$blacklist', bclass: 'Copy', onpress : BlackList$t},
		{name: '$restart_service', bclass: 'ReConf', onpress : RestartService$t},
	],";

	

$html="	$texttoadd<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var xmemnum=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?details-tablerows=yes&t=$t&field={$_GET["field"]}&value={$_GET["value"]}&EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :40, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'none', width :45, sortable : false, align: 'center'},
		{display: '$dns_nameservers', name : 'server', width :386, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'zOrder', width :42, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'dup', width :31, sortable : false, align: 'center'},		
		{display: '&nbsp;', name : 'none2', width :45, sortable : false, align: 'center'},
	],
	$buttons
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true
	
	});   
});

function RestartService$t(){
	var SQUID_INSTALLED=$SQUID_INSTALLED;
	if(SQUID_INSTALLED==0){
		Loadjs('system.services.cmd.php?APPNAME=APP_DNSMASQ&action=restart&cmd=%2Fetc%2Finit.d%2Fdnsmasq&appcode=DNSMASQ');
		return;
	}

	Loadjs('squid.restart.php?onlySquid=yes');

}

function BlackList$t(){
	Loadjs('squid.dns.items.black.php');
}
var x_dnsadd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#table-$t').flexReload();
	if(document.getElementById('squid-services')){
		LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
	}
}
		
var x_dnsdel= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#rowsquid-dns-'+xmemnum).remove();
	$('#table-$t').flexReload();
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
var x_dnsupd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#table-$t').flexReload();
	
}

function SquidDNSUpDown(ID,dir){
	var XHR = new XHRConnection();
	XHR.appendData('SquidDNSUpDown',ID);
	XHR.appendData('direction',dir);
	XHR.sendAndLoad('$page', 'POST',x_dnsupd);	
}


</script>";
echo $html;

}

function SquidDNSUpDown(){
	$ID=$_POST["SquidDNSUpDown"];
	$direction=$_POST["direction"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT *  FROM dns_servers WHERE ID='$ID'"));
	$OldZorder=$ligne["zOrder"];
	if($direction==1){
		$zOrder=$ligne["zOrder"]-1;

	}else{
		$zOrder=$ligne["zOrder"]+1;
	}
	
	if($zOrder==-1){$zOrder=0;}
	
	$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$OldZorder WHERE `zOrder`=$zOrder AND ID !='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$zOrder WHERE `ID`='$ID'");
	if(!$q->ok){echo $q->mysql_error;}

	$sql="SELECT *  FROM dns_servers ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$c WHERE `ID`='$ID'");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}
	
}


function details_tablerows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$squid=new squidbee();
	$t=$_GET["t"];
	$search='%';
	$table="dns_servers";

	if(!$q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("CREATE TABLE `squidlogs`.`dns_servers` ( `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				 `dnsserver` VARCHAR( 90 ) NOT NULL , 
				 `zOrder` SMALLINT( 2 ) NOT NULL ,
				 INDEX (`zOrder`), 
				UNIQUE KEY `dnsserver` (`dnsserver`) )" );
	}

	$page=1;
	$FORCE_FILTER=null;

	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no data}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$delete=imgtootltip('delete-24.png','{delete}',"DnsDelete$t('{$ligne["dnsserver"]}')");
		$up=imgsimple("arrow-up-32.png","","SquidDNSUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-32.png","","SquidDNSUpDown('{$ligne['ID']}',0)");
		
		$data['rows'][] = array(
				'id' => "squid-dns-{$ligne["ID"]}",
				'cell' => array(
						"<span style='font-size:18px'>{$ligne["zOrder"]}</span>",
						"<span style='font-size:12.5px'><img src='img/32-samba-pdc.png'></span>",
						"<span style='font-size:18px'>{$ligne["dnsserver"]}</span>",
						"<span style='font-size:12.5px'>$up</span>",
						"<span style='font-size:12.5px'>$down</span>",
						"<span style='font-size:12.5px'>$delete</span>",
				)
		);		

	}

	echo json_encode($data);
}