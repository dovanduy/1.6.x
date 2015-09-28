<?php
$GLOBALS["MAIN_TABLE"]="pnic_nat";
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	
	
	if(isset($_GET["rules-list"])){rules_list();exit;}
	if(isset($_GET["rule-js"])){network_bridge_js();exit;}
	if(isset($_GET["network-bridge-delete-js"])){rule_delete_js();exit;}
	if(isset($_GET["bridge-wizard-delete-js"])){bridge_wizard_delete_js();exit;}
	if(isset($_POST["Delete-wizard"])){bridge_wizard_delete();exit;}
	
	if(isset($_GET["network-bridge"])){rule_popup();exit;}
	if(isset($_POST["ID"])){rule_save();exit;}
	if(isset($_POST["Delete"])){network_bridge_del();exit;}
	table();
	
	
	
	
	
function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["network-bridge-delete-js"];
	if($ID==0){die();}
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM pnic_nat WHERE ID='$ID'","artica_backup"));
	$confirm=$tpl->javascript_parse_text("{delete} {packets_from} {$ligne["nic_from"]} {should_be_forwarded_to} {$ligne["nic_to"]} ?");
	
	echo "
var xSaveR$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	
}	
	
	
function SaveR$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('Delete', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSaveR$t);  			
}
	
SaveR$t();";
	
	
}
	
function network_bridge_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_nat}");
	}else{
		$title=$tpl->javascript_parse_text("{nat_title} ID:$ID");
	}
		
	echo "YahooWin2('850','$page?network-bridge=yes&ID=$ID&t={$_GET["t"]}','$title',true);";
	
}
function network_bridge_del(){
	$ID=$_POST["Delete"];
	if($ID==0){die();}
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM {$GLOBALS["MAIN_TABLE"]} WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function rule_save(){
	$tpl=new templates();
	$q=new mysql();

	

	if(!$q->FIELD_EXISTS("pnic_nat", "dstaddrport", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_nat ADD dstaddrport INT(10)","artica_backup");
		if(!$q->ok){echo "ALTER TABLE pnic_nat failed\n$q->mysql_error\n";return;}
	}
	
	if(!$q->FIELD_EXISTS("pnic_nat", "dstaddrTarget", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_nat ADD dstaddrTarget VARCHAR(60)","artica_backup");
		if(!$q->ok){echo "ALTER TABLE pnic_nat failed\n$q->mysql_error\n";return;}
	}	
	
	if(!$q->FIELD_EXISTS("pnic_nat", "proto", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_nat ADD proto VARCHAR(10)","artica_backup");
		if(!$q->ok){echo "ALTER TABLE pnic_nat failed\n$q->mysql_error\n";return;}
	}	
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	while (list ($key, $val) = each ($_POST) ){
		$EDIT[]="`$key`='$val'";
		$ADDFIELD[]="`$key`";
		$ADDVALS[]="'$val'";
		
	}

	if($ID==0){
		$zMD5=md5(serialize($_POST));
		$ADDFIELD[]="`zMD5`";
		$ADDVALS[]="'$zMD5'";
		$sql="INSERT INTO {$GLOBALS["MAIN_TABLE"]} (".@implode(",", $ADDFIELD).") VALUES (".@implode(",", $ADDVALS).")";
		
	}else{
		$sql="UPDATE {$GLOBALS["MAIN_TABLE"]} SET ".@implode(",", $EDIT)." WHERE ID=$ID";
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM {$GLOBALS["MAIN_TABLE"]} WHERE ID='$ID'","artica_backup"));
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	$array[null]="{all}";
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
	}
	
	$but="{add}";
	$title="{new_nat}";
	if($ID>0){$but="{apply}"; $title="{nat_title}"; }
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$NAT_TYPE[0]="{destination} NAT";
	//$NAT_TYPE[1]="{source} NAT";
	
	$PROTOR["tcp"]="TCP";
	$PROTOR["udp"]="UDP";
	
	//$NAT_TYPE[2]="{redirect_nat}";
	
	if($ligne["dstaddrTarget"]==null){$ligne["dstaddrTarget"]="0.0.0.0/0";}
	$ligne["dstaddrport"]=intval($ligne["dstaddrport"]);
	if($ligne["proto"]==null){$ligne["proto"]="tcp";}
	$html="
	<div style='font-size:32px;margin-bottom:20px'>$title</div>		
	<div style='width:98%' class=form>
	
	<table style='width:100%'>
	
	<tr>
		<td class=legend style='font-size:18px' nowrap>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t",1, $ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px' nowrap>{type}:</td>
		<td>". Field_array_Hash($NAT_TYPE, "NAT_TYPE-$t",$ligne["NAT_TYPE"],"style:font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{type}:</td>
		<td>". Field_array_Hash($PROTOR, "proto-$t",$ligne["proto"],"style:font-size:18px")."</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:18px' nowrap>{requested_port}:</td>
		<td>". Field_text("dstport-$t", $ligne["dstport"],"font-size:18px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{requested_addr}:</td>
		<td>". Field_text("dstaddrTarget-$t", $ligne["dstaddrTarget"],"font-size:18px;width:120px")."</td>
	</tr>
	

	<tr>
		<td class=legend style='font-size:18px' nowrap>{interface}:</td>
		<td>". Field_array_Hash($array, "nic-$t",$ligne["nic"],"style:font-size:18px")."</td>
	</tr>				

				
	<tr>
		<td class=legend style='font-size:18px' nowrap>{destination_address}:</td>
		<td>". field_ipv4("dstaddr-$t", $ligne["dstaddr"],"font-size:18px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{destination_port}:</td>
		<td>". field_ipv4("dstaddrport-$t", $ligne["dstaddrport"],"font-size:18px;width:120px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px' nowrap>{source_address}:</td>
		<td>". field_ipv4("srcaddr-$t", $ligne["srcaddr"],"font-size:18px;width:120px")."</td>
	</tr>
							
				
				
	<tr>
		<td colspan=2 align='right'><hr>". button($but,"Save$t();","24")."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {	
	var ID='$ID';
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	if(ID==0){YahooWin2Hide();}
}	
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID', '$ID');
	XHR.appendData('nic', document.getElementById('nic-$t').value);	
	XHR.appendData('dstport', document.getElementById('dstport-$t').value);
	XHR.appendData('NAT_TYPE', document.getElementById('NAT_TYPE-$t').value);	
	XHR.appendData('dstaddr', document.getElementById('dstaddr-$t').value);
	XHR.appendData('srcaddr', document.getElementById('srcaddr-$t').value);
	XHR.appendData('dstaddrport', document.getElementById('dstaddrport-$t').value);
	XHR.appendData('dstaddrTarget', document.getElementById('dstaddrTarget-$t').value);	
	XHR.appendData('proto', document.getElementById('proto-$t').value);					
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);};	      
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}

</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


	



function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	$title=$tpl->_ENGINE_parse_body("{nat_title}");
	$nic_from=$tpl->javascript_parse_text("{nic}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_nat}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$about=$tpl->javascript_parse_text("{about2}");
	$type=$tpl->javascript_parse_text("{type}");
	$reconstruct=$tpl->javascript_parse_text("{apply_firewall_rules}");
	$bts[]="{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '<strong style=font-size:18px>$reconstruct</strong>', bclass: 'apply', onpress : BuildVLANs$t},";
	
	$description=$tpl->javascript_parse_text("{description}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
		
		<script>
		var mm$t=0;
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?rules-list=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: 'ID', name : 'ID', width : 31, sortable : true, align: 'center'},
		{display: '$type', name : 'NAT_TYPE', width : 212, sortable : true, align: 'left'},
		{display: '$nic_from', name : 'nic', width : 200, sortable : false, align: 'center'},
		{display: '$description', name : 'desc', width : 706, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
		],$buttons
		searchitems : [
		{display: '$nic_from', name : 'nic'},
		

		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:22px>$title</span>',
		useRp: true,
		rp: 25,
		showTableToggleBtn: false,
		width: '99%',
		height: 500,
		singleSelect: true
	
	});
	});
	
	function RuleAdd$t(){
		Loadjs('$page?rule-js=yes&ID=0&t=$t',true);
	}
	
	function BuildVLANs$t(){
		Loadjs('firehol.progress.php?t=$t');
	
	}

	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function rules_list(){
	$q=new mysql();
	$tpl=new templates();
	$database="artica_backup";
	$table="pnic_nat";
	$MyPage=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();

	if(!$q->TABLE_EXISTS("pnic_nat", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `pnic_nat` (
		`ID` INT(10) NOT NULL AUTO_INCREMENT,
		`zMD5` varchar(90) NOT NULL,
		`NAT_TYPE` smallint(1) NOT NULL,
		`dstport` INT(10) NOT NULL,
		`dstaddr` VARCHAR(60) NOT NULL,
		`srcaddr` VARCHAR(60) NOT NULL,
		`dstaddrport` INT(10) NOT NULL,
		`nic` VARCHAR(60) NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (`ID`),
		UNIQUE KEY (`zMD5`),
		KEY `NAT_TYPE` (`NAT_TYPE`),
		KEY `dstport` (`dstport`),
		KEY `nic` (`nic`),
		KEY `enabled` (`enabled`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,$database);
	}
	

	if(!$q->TABLE_EXISTS("pnic_nat", "artica_backup")){
		json_error_show("Unable to create table...",1);
	}
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	$total = intval($ligne["tcount"]);
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$deny_dhcp_requests=$tpl->_ENGINE_parse_body("{deny_dhcp_requests}");
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rule set...",1);}
	
	$all=$tpl->javascript_parse_text("{all}");
	$NAT_TYPE[0]=$tpl->javascript_parse_text("{destination} NAT");
	$NAT_TYPE[1]=$tpl->javascript_parse_text("{source} NAT");
	$NAT_TYPE[2]=$tpl->javascript_parse_text("{redirect_nat}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$deny_dhcp_requeststxt=null;
		$arrow="arrow-right-32.png";
		if($ligne["enabled"]==0){$color="#ABABAB";$arrow="arrow-right-32-grey.png";}
		$ip=new IP();
		$nic=$ligne["nic"];
		$nic_to=$ligne["nic_to"];
		$masquerading=null;
		
		if($nic==null){
			$nic=$all;}else{
				$nicz=new system_nic($nic);
				$nic="$nic->NICNAME ($nic)";
			}
		
		
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?network-bridge-delete-js={$ligne['ID']}&t=$t',true)");
		$js="Loadjs('$MyPage?rule-js=yes&ID={$ligne['ID']}&t=$t',true);";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;color:$color;font-weight:normal;text-decoration:underline'>";
	
		$NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
		
		if($ligne["NAT_TYPE"]==0){
			$srcaddr=trim($ligne["srcaddr"]);
			if($srcaddr==null){$srcaddr=$all;}
			$explain=$tpl->javascript_parse_text("{NAT_TYPE_0_EXP}");
			$explain=str_replace("%P", $ligne["dstport"]."<br>", $explain);
			$explain=str_replace("%s", $ligne["dstaddr"]."<br>", $explain);
			$explain=str_replace("%p", $ligne["dstaddrport"], $explain);
			$explain=str_replace("%i", $nic, $explain);
			$explain=str_replace("%T", $ligne["proto"],$explain );
			$explain=str_replace("%c", $srcaddr, $explain);
			
		}
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:18px;font-weight:bold;color:$color'>{$ligne['ID']}</span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$href{$NAT_TYPE_TEXT}</a></span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$href{$nic}</a></span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$explain</span>",
						"<center>$delete</center>"
				)
		);
	}
	
	
	echo json_encode($data);	
	
	
}

