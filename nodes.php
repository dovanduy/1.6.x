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

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["network"])){network();exit;}
if(isset($_GET["softwares"])){softwares();exit;}
if(isset($_GET["delete-node-js"])){delete_node_js();exit;}
if(isset($_GET["reboot-node-js"])){reboot_node_js();exit;}
if(isset($_GET["hostname-node-js"])){hostname_node_js();exit;}
if(isset($_GET["update-node-js"])){update_node_js();exit;}
if(isset($_GET["update-squid-js"])){update_squid_js();exit;}
if(isset($_GET["services"])){services_status();exit;}

if(isset($_POST["reboot-node"])){reboot_node_perform();exit;}
if(isset($_POST["delete-node"])){delete_node_perform();exit;}
if(isset($_POST["hostname-node"])){hostname_node_perform();exit;}
if(isset($_POST["update-node"])){update_node_perform();exit;}
if(isset($_POST["update-squid"])){update_squid_perform();exit;}



if(isset($_GET["status-list"])){status_list();exit;}
js();


function js(){
	$page=CurrentPageName();
	$blk=new blackboxes($_GET["nodeid"]);
	$title=$blk->hostname;
	$q=new mysql_blackbox();
	$ipZ=array();$ipsT=null;
	$results2=$q->QUERY_SQL("SELECT ipaddr  FROM `nics` WHERE nodeid={$_GET["nodeid"]}");
	while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
		if($ligne2["ipaddr"]=="127.0.0.1"){continue;}
		$ipZ[]=$ligne2["ipaddr"];
	}
	if(count($ipZ)>0){
		$ipsT=implode(", ", $ipZ);
	}	
	
	
	$html="YahooWin('930','$page?tabs=yes&nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}','$title&raquo;$ipsT')";
	echo $html;
	
}
function update_node_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$blk=new blackboxes($_GET["nodeid"]);	
	$ve=$blk->last_available_version();
	$text=$tpl->javascript_parse_text("{no_update_available}");
	if($ve==null){
		echo "alert('$text');";
		return;
	}
	
	$hostname=$blk->hostname;
	
	$text=$tpl->javascript_parse_text("{update} $hostname  -> $ve?");
	$t=time();
	$html="
	var x_update$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('node_tabs_{$_GET["nodeid"]}');
			}		
	
	
	function update$t(){
		var txt=prompt('$text');
		if(txt){
			var XHR = new XHRConnection();
    		XHR.appendData('update-node',{$_GET["nodeid"]});
    		XHR.sendAndLoad('$page', 'POST',x_update$t);
		}
	}		
	
	
	update$t()";
	echo $html;
	
}

function update_squid_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$blk=new blackboxes($_GET["nodeid"]);
	
	$arch=$blk->Architecture;
	if($arch==32){
		$ve=$blk->last_available_squidx32_version();
	}
	
	if($arch==64){
		$ve=$blk->last_available_squidx64_version();
	}	
	
	$text=$tpl->javascript_parse_text("{no_update_available}");
	if($ve==null){
		echo "alert('$text');";
		return;
	}
	
	$hostname=$blk->hostname;
	
	$text=$tpl->javascript_parse_text("{update} $hostname  -> $ve ({$arch}Bits) ?");
	$t=time();
	$html="
	var x_update$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('node_tabs_{$_GET["nodeid"]}');
			}		
	
	
	function updateS$t(){
		if(confirm('$text')){
			var XHR = new XHRConnection();
    		XHR.appendData('update-squid',{$_GET["nodeid"]});
    		XHR.sendAndLoad('$page', 'POST',x_update$t);
		}
	}		
	
	
	updateS$t();";
	echo $html;	
}


function hostname_node_js(){
	$page=CurrentPageName();
	$blk=new blackboxes($_GET["nodeid"]);	
	$hostname=$blk->hostname;
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{hostname} $hostname ?");
	$t=time();
	$html="
	var x_hostname$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('node_tabs_{$_GET["nodeid"]}');
			}		
	
	
	function hostname$t(){
		var txt=prompt('$text');
		if(txt){
			var XHR = new XHRConnection();
    		XHR.appendData('hostname-node',{$_GET["nodeid"]});
    		XHR.appendData('hostname',txt);
    		XHR.sendAndLoad('$page', 'POST',x_hostname$t);
		}
	}		
	
	
	hostname$t()";
	echo $html;
	
}
function delete_node_js(){
	$page=CurrentPageName();
	$blk=new blackboxes($_GET["nodeid"]);	
	$hostname=$blk->hostname;
	$tpl=new templates();
	$confirm=$tpl->javascript_parse_text("{delete_this_node} $hostname ?");
	$t=time();
	$html="
	var x_DeleteNode$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWinHide();
			RefreshTab('main_statsremoteservers_tabs');
			RefreshTab('main_appliances_config');
			}		
	
	
	function DeleteNode$t(){
		if(confirm('$confirm')){
			var XHR = new XHRConnection();
    		XHR.appendData('delete-node',{$_GET["nodeid"]});
    		AnimateDiv('node_tabs_{$_GET["nodeid"]}');
    		XHR.sendAndLoad('$page', 'POST',x_DeleteNode$t);
		}
	}		
	
	
	DeleteNode$t()";
	echo $html;
	
}

function reboot_node_js(){
	$page=CurrentPageName();
	$blk=new blackboxes($_GET["nodeid"]);	
	$hostname=$blk->hostname;
	$tpl=new templates();
	$confirm=$tpl->javascript_parse_text("{reboot_this_node} $hostname ?");
	$t=time();
	$html="
	var x_RebootNode$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWinHide();
			RefreshTab('main_statsremoteservers_tabs');
			}		
	
	
	function RebootNode$t(){
		if(confirm('$confirm')){
			var XHR = new XHRConnection();
    		XHR.appendData('reboot-node',{$_GET["nodeid"]});
    		AnimateDiv('node_tabs_{$_GET["nodeid"]}');
    		XHR.sendAndLoad('$page', 'POST',x_RebootNode$t);
		}
	}		
	
	
	RebootNode$t()";
	echo $html;	
}

function status(){
	
	$blackbox=new blackboxes($_GET["nodeid"]);
	$t=time();
	
	$page=CurrentPageName();	
	$tpl=new templates();
	$software=$tpl->_ENGINE_parse_body("{daemon}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$virtual_memory=$tpl->_ENGINE_parse_body("{virtual_memory}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$CORP=0;
	
	
	if($blackbox->settings_inc["CORP_LICENSE"]){$CORP=1;}
	
	if($CORP==1){
		$CORP_TEXT="{license_active}";
	}else{
		$CORP_TEXT="&nbsp;<img src='img/status_warning.png'><span style='font-weight:normal'>{license_invalid}</span>";
	}
	
	if($blackbox->TotalMemoryMB>0){
		$TotalMemoryMB=" <span style='font-weight:normal;font-size:13px'>({memory}: {$blackbox->TotalMemoryMB}M)";
	}else{
		$TotalMemoryMB="&nbsp;<img src='img/status_warning.png'><span style='font-weight:normal'>No memory receive...</span>";
	}
	
	$updateAgent="<tr>
					<td >&nbsp;</td>
					<td >
						<table style='width:100%'>
							<tr>
								<td valign='top' width=1% nowrap><img src='img/arrow-right-16.png'></td>
								<td><a href=\"javascript:blur();\" 
								OnClick=\"javascript:Loadjs('$page?update-node-js=yes&nodeid={$_GET["nodeid"]}')\" 
								style='font-size:12px;text-decoration:underline'>{update_agent}</td>
							</tr>
						</table>
					</td>
				</tr>";
	
	if($blackbox->IsArtica==1){$updateAgent=null;}
	
	if($blackbox->squid_version<>null){
			$squidStatus="
					<tr>
					<td class=legend style='font-size:14px'>{APP_SQUID}:</td>
					<td><strong style='font-size:14px'><strong style='font-size:14px'>{$blackbox->squid_version}</td>
					</tr>
					<tr>
					<td >&nbsp;</td>
					<td >
						<table style='width:100%'>
							<tr>
								<td valign='top' width=1% nowrap><img src='img/arrow-right-16.png'></td>
								<td><a href=\"javascript:blur();\" 
								OnClick=\"javascript:Loadjs('$page?update-squid-js=yes&nodeid={$_GET["nodeid"]}')\" style='font-size:12px;text-decoration:underline'>{update_squid}</td>
							</tr>
						</table>
					</td>
				</tr>";
	}
	
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td valign='top'>
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:14px'>{last_status}:</td>
					<td><strong style='font-size:14px'>$blackbox->laststatus</td>
				</tr>
				<tr>
					<td class=legend style='font-size:14px'>{uuid}:</td>
					<td><strong style='font-size:14px'>$blackbox->hostid</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:14px'>{artica_license}:</td>
					<td><strong style='font-size:14px'>$CORP_TEXT</td>
				</tr>							
				<tr>
					<td class=legend style='font-size:14px'>{hostname}:</td>
					<td><strong style='font-size:14px'><a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('$page?hostname-node-js=yes&nodeid={$_GET["nodeid"]}');\"
					style='font-size:14px;text-decoration:underline'>
					$blackbox->hostname</a></td>
				</tr>				
				
				<tr>
					<td class=legend style='font-size:14px'>{cpu_number}:</td>
					<td><strong style='font-size:14px'>{$blackbox->settings_inc["CPU_NUMBER"]}$TotalMemoryMB</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:14px'>{version}:</td>
					<td><strong style='font-size:14px'>{$blackbox->VERSION}</td>
				</tr>
				$updateAgent	
				$squidStatus							
			</table>		
		
		
		</td>
		<td valign='top'>
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:14px'>{delete}:</td>
					<td>". imgtootltip("delete-24.png","{delete} node:{$_GET["nodeid"]}",
					"Loadjs('$page?delete-node-js=yes&nodeid={$_GET["nodeid"]}')")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:14px'>{reboot}:</td>
					<td>". imgtootltip("reboot-computer-24.png","{reboot} node:{$_GET["nodeid"]}",
					"Loadjs('$page?reboot-node-js=yes&nodeid={$_GET["nodeid"]}')")."</td>
				</tr>
			</table>
		
		
	</tr>
	</table>
	
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function services_status(){
	
	$t=time();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$software=$tpl->_ENGINE_parse_body("{daemon}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$virtual_memory=$tpl->_ENGINE_parse_body("{virtual_memory}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$status=$tpl->_ENGINE_parse_body("{status}");	
	
	$html="	
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>
	
	$(document).ready(function(){
		$('#table-$t').flexigrid({
			url: '$page?status-list=yes&nodeid={$_GET["nodeid"]}',
			dataType: 'json',
			colModel : [
			{display: '$software', name : 'service_name', width : 272, sortable : true, align: 'left'},
			{display: '$memory', name : 'master_memory', width : 80, sortable : true, align: 'left'},
			{display: '$virtual_memory', name : 'master_cached_memory', width : 124, sortable : true, align: 'left'},
			{display: '$version', name : 'master_version', width : 159, sortable : true, align: 'left'},
			{display: '$status', name : 'running', width : 71, sortable : true, align: 'center'},
			{display: 'PID', name : 'running', width : 71, sortable : true, align: 'left'},
	
			],
	
			searchitems : [
			{display: '$software', name : 'category'},
			],
			sortname: 'running',
			sortorder: 'desc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 15,
			showTableToggleBtn: false,
			width: 871,
			height: 400,
			singleSelect: true
	
		});
	});
	
		</script>";
	
	echo $html;
}


function status_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_blackbox();
	
	
	$search='%';
	$table="nodesstatus";
	$page=1;
	$ORDER="ORDER BY category ASC";
	$FORCE_FILTER="nodeid={$_GET["nodeid"]}";
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$service=$tpl->_ENGINE_parse_body("{{$ligne["service_name"]}}");
		$master_memory=FormatBytes($ligne["master_memory"]);
		$master_cached_memory=FormatBytes($ligne["master_cached_memory"]);
		$master_version=$ligne["master_version"];
		$master_pid=$ligne["master_pid"];
		$img="img/ok24-grey.png";
		if($master_pid>0){
			$img="img/ok24.png";
		}else{
			$img="img/danger24.png";
		}
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<span style='font-size:14px;color:$color'>$service</span>",
		"<span style='font-size:14px;color:$color'>$master_memory</span>",
		"<span style='font-size:14px;color:$color'>$master_cached_memory</a></span>",
		 "<span style='font-size:14px;color:$color'>$master_version</span>",
			"<img src='$img'>",
		 "<span style='font-size:14px;color:$color'>$master_pid</span>",
	$delete)
		);
	}
	
	
echo json_encode($data);	
}	
	
	


function tabs(){
	$blk=new blackboxes($_GET["nodeid"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$hostid=$_GET["hostid"];
	
	if(strlen($hostid)==0){
		$_GET["hostid"]=$blk->hostid;
		$hostid=$_GET["hostid"];
	}
	
	$array["status"]="{status}";
	$array["services"]="{services}";
	$array["system"]="{system}";
	
	
	
	
	if($blk->settings_inc["SQUID_INSTALLED"]){
		$array["squid"]="{APP_SQUID}";
	}
	
	$array["network"]="{network}";
	$array["disks"]="{disks}";
	$array["softwares"]="{softwares}";
	
	


	$t=time();
	while (list ($num, $ligne) = each ($array) ){
			if($num=="softwares"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.softwares.php?nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}
			
			if($num=="network"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.network.php?nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}	
			
			if($num=="disks"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.disks.php?nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}			

			if($num=="squid"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.squid.php?nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}			
			if($num=="system"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.yorel.php?nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}		
		
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&nodeid={$_GET["nodeid"]}&hostid=$hostid\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "node_tabs_{$_GET["nodeid"]}");	

}

function delete_node_perform(){
	$f=new mysql_blackbox();
	$f->CheckTables();
	$q=new blackboxes($_POST["delete-node"]);	
	$q->delete_node();
	
}
function hostname_node_perform(){
	$q=new blackboxes($_POST["hostname-node"]);
	$q->hostname=$_POST["hostname"];
	$q->ChangeHostname();
	
}

function update_node_perform(){
	$q=new blackboxes($_POST["hostname-node"]);
	$q->hostname=$_POST["hostname"];
	$q->updateorder();
	
}

function update_squid_perform(){
	$q=new blackboxes($_POST["update-squid"]);
	$q->updatesquid();	
}

function reboot_node_perform(){
	$q=new blackboxes($_POST["reboot-node"]);
	$q->reboot();	
	
}
