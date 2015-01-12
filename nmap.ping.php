<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.nmap.inc');
	include_once(dirname(__FILE__).'/ressources/class.computers.inc');
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}
	if(isset($_GET["items"])){items();exit;}
	
	
table();
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$groups=$tpl->javascript_parse_text("{groups}");
		$from=$tpl->_ENGINE_parse_body("{from}");
		$to=$tpl->javascript_parse_text("{to}");
		$rule=$tpl->javascript_parse_text("{rule}");
		$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
		$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
		$new_text=$tpl->javascript_parse_text("{networks}");
		$network=$tpl->javascript_parse_text("{network2}");
		$rules=$tpl->javascript_parse_text("{rules}");
		$vendor=$tpl->javascript_parse_text("{vendor}");
		$analyze=$tpl->javascript_parse_text("{ping_networks}");
		$infos=$tpl->javascript_parse_text("{infos}");
		$MAC=$tpl->javascript_parse_text("{MAC}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$title=$tpl->_ENGINE_parse_body("{nmap_scan_ping_title}");
		$tt=time();
		$buttons="
		buttons : [
		{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
		{name: '$analyze', bclass: 'Reconf', onpress : Apply$tt},
	
		],";
	
$html="
<table class='NMAP_PING_TABLE' style='display: none' id='NMAP_PING_TABLE' style='width:100%'></table>
<script>
	function Start$tt(){
		$('#NMAP_PING_TABLE').flexigrid({
		url: '$page?items=yes',
		dataType: 'json',
		colModel : [
	
		{display: '$ipaddr', name : 'ipaddr', width :250, sortable : true, align: 'left'},
		{display: '$MAC', name : 'MAC', width : 250, sortable : true, align: 'left'},
		{display: '$vendor', name : 'vendor', width : 231, sortable : true, align: 'left'},
		{display: '$infos', name : 'infos', width : 367, sortable : false, align: 'left'},
		],
		$buttons
		searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$MAC', name : 'MAC'},
		{display: '$vendor', name : 'vendor'},
		],
		sortname: 'ipaddr',
		sortorder: 'asc',
		usepager: true,
		title: '$title',
		useRp: false,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 477,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}

	
function Apply$tt(){
	Loadjs('nmap.ping.progress.php',false);
}
	
	
function NewRule$tt(){
	Loadjs('computer-browse.php?network-js=yes',true);
}
	
Start$tt();
</script>
";
echo $html;
	
}
	
function items(){
	$page=1;
	$q=new mysql();
	$table="nmap_scannet";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(!$q->TABLE_EXISTS("nmap_scannet", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `nmap_scannet` (
		`MAC` varchar(90) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		`vendor` varchar(90) NOT NULL DEFAULT '',
		`zDate` datetime NOT NULL,
		PRIMARY KEY (`MAC`),
		KEY `ipaddr` (`ipaddr`),
		KEY `vendor` (`vendor`)
		) ENGINE=MYISAM;";
			$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
		$fontsize="18px";
		$data = array();
		$data['page'] = 1;
		$data['total'] = mysql_num_rows($results);
		$data['rows'] = array();
	
		//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
		$computer=new computers();
		$tpl=new templates();
		$dhcpfixed=$tpl->_ENGINE_parse_body("{dhcpfixed}");
	while ($ligne = mysql_fetch_assoc($results)) {
			$ipaddr=$ligne["ipaddr"];
			$infoZ=array();
			$mac=strtolower($ligne["MAC"]);
			$vendor=$ligne["vendor"];
			$uid=null;
			$uid=$computer->ComputerIDFromMAC($mac);
			$view="&nbsp;";
			$jslink=null;
			$jsfiche=null;
			$infos=null;
			$macenc=urlencode($ligne["MAC"]);
			if($uid<>null){
				
				
				
				$jslink="<a href=\"javascript:blur();\"
				OnClick=\"javascript:".MEMBER_JS($uid,1,1)."\"
				style='font-size:$fontsize;text-decoration:underline'>";
				$computer=new computers($uid);
				
				if($computer->dhcpfixed==1){
					$infoZ[]="<a href=\"javascript:blur();\"
							OnClick=\"javascript:Loadjs('dhcpd.fixed.hosts.php?modify-dhcpd-settings-js=yes&mac=$macenc');\"
							style='text-decoration:underline'>$dhcpfixed</a> - ";
					
				}
				
				if(trim($computer->DisplayName)<>null){
					$infoZ[]=$computer->DisplayName;
				}
				
				if(trim($computer->ComputerMachineType)<>null){
					$infoZ[]=$computer->ComputerMachineType;
				}
				
				if(trim($computer->ComputerOS)<>null){
					$infoZ[]=$computer->ComputerOS;
				}
				
				$infos=@implode(" - ", $infoZ);
				
			
			}else{
				
				$ocs=new ocs($mac);
				if(trim($ocs->HARDWARE_ID>0)){
					
					if($ocs->dhcpfixed==1){
						$infos="<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('dhcpd.fixed.hosts.php?modify-dhcpd-settings-js=yes&mac=$macenc');
						style='text-decoration:underline'>$dhcpfixed</a> - ";
							
					}
					
					$infos=$infos.$ocs->ComputerName." - ".$computer->ComputerOS;
				}
					
				
				
			}

			
			
			
			$data['rows'][] = array(
			'id' => md5(serialize($ligne)),
			'cell' => array("<span style='font-size:18px'>$ipaddr</span>",
			"<span style='font-size:18px'>$jslink$mac</a></span>",
			"<span style='font-size:18px'>$vendor</span>",
			"<span style='font-size:12px'>$infos</span>" )
		);
	}
	
	
	echo json_encode($data);	
}