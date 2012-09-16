<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.drdb.inc');

	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	
	
	if(!CheckSambaRights()){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H1>$ERROR_NO_PRIVS</H1>";die();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["cluster-status"])){cluster_status();exit;}
	if(isset($_POST["EnableGluster"])){cluster_enable();exit;}
	
	if(isset($_GET["cluster-index"])){cluster_index();exit;}
	if(isset($_GET["cluster-table"])){cluster_table();exit;}
	
	if(isset($_GET["cluster-nodes"])){cluster_nodes();exit;}
	if(isset($_GET["cluster-nodes-list"])){cluster_nodes_list();exit;}
	
	if(isset($_GET["Parameters"])){Parameters();exit;}
	if(isset($_GET["nodeip"])){node_popup();exit;}
	if(isset($_POST["SAVENODE"])){node_save();exit;}
	if(isset($_GET["LocalDisks"])){LocalDisks();exit;}
	
	if(isset($_GET["new-node"])){new_node();exit;}
	if(isset($_POST["client_ip"])){new_node_save();exit;}
	
	if(isset($_GET["node-events"])){node_events();exit;}
	if(isset($_GET["node-events-list"])){node_events_list();exit;}
	
	if(isset($_GET["new-dir"])){new_directory();exit;}
	if(isset($_POST["ClusterDirDelete"])){directory_delete();exit;}
	if(isset($_POST["new-directory"])){new_directory_save();exit;}
	if(isset($_POST["ClusterSetConfig"])){ClusterSetConfig();exit;}
	if(isset($_POST["ClusterSetConfig-nodes"])){ClusterSetConfig_nodes();exit;}
	if(isset($_GET["delete_cluster_client"])){del_node();exit;}
	if(isset($_GET["cluster-master-events"])){master_events();exit;}
	if(isset($_GET["cluster-master-events-search"])){master_events_list();exit;}
	
	index();
	
	
function status(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableGluster=$sock->GET_INFO("EnableGluster");
	if(!is_numeric($EnableGluster)){$EnableGluster=0;}
	
	$p=Paragraphe_switch_img("{EnableClusterConfig}", "{EnableGlusterConfigText}","EnableGluster",$EnableGluster,null,400);
	$p=$tpl->_ENGINE_parse_body($p);
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td valign='top' width=1% valign='top'><div id='status-$t'></div>
		<td valign='top' width=1% valign='top'><div id='service-$t'>
	
			$p
			<div style='text-align:right'><hr>". button("{apply}","SaveEnableGluster()","16px")."</div>
			</div>
	</tr>
	</table>
	<script>
		function RefreshGlusterStatus(){
			LoadAjax('status-$t','$page?cluster-status=yes');
		
		}
		
var X_SaveEnableGluster= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('main_samba_clusters');
	
	}	
	
	function SaveEnableGluster(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableGluster')){
			XHR.appendData('EnableGluster',document.getElementById('EnableGluster').value);
			document.getElementById('img_EnableGluster').src='img/wait_verybig.gif';
			
		}
		
		XHR.sendAndLoad('$page', 'POST',X_SaveEnableGluster);			
		
	}		
		
		RefreshGlusterStatus();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);;
	
	
}

function cluster_status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=$sock->getFrameWork("gluster.php?status=yes");	
	$ini->loadString(base64_decode($datas));
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("GLUSTER",$ini,null,0,0));
}
function cluster_enable(){
	$sock=new sockets();
	$sock->SET_INFO("EnableGluster", $_POST["EnableGluster"]);
	$sock->getFrameWork("cmd.php?gluster-restart=yes");
	
	
}
	
function index(){	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	if(!$users->GLUSTER_INSTALLED){
		echo $tpl->_ENGINE_parse_body("<center style='margin:50px'><table style='width:85%' class=form>
		<tr>
			<td valign='top'><img src='img/error-128.png'></td>
			<td style='font-size:18px;color:#CA2A2A'>{APP_GLUSTER_NOT_INSTALLED}</td>
		</tr>
		</table>
		
		");
		return;
		
	}
	
	$array["status"]="{status}";
	$array["cluster-nodes"]='{trusted_storage_pools}';
	$array["cluster-volumes"]='{volumes}';
	$array["cluster-volumes-client"]='{volumes_client_mode}';
	$array["cluster-master-events"]='{events}';

	
	
	$fontsize=14;
	if(count($array)>5){$fontsize=11.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="cluster-dirs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"samba.clusters.dirs.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="cluster-volumes"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"samba.clusters.volumes.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="cluster-volumes-client"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"samba.clusters.volumes.client.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	
	<div id=main_samba_clusters style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_samba_clusters').tabs();
			});
		</script>";		
	
	
}
	

function cluster_index(){	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=845;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$t=time();
	$directory=$tpl->_ENGINE_parse_body("{directories}");
	$new_directory=$tpl->_ENGINE_parse_body("{new_directory}");
	
	$buttons="
	buttons : [
	{name: '$new_directory', bclass: 'Add', onpress : NewClusterDirectory},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig},
	
	],	";
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cluster-table=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$directory', name : 'cluster_path', width :774, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'events', width : 31, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$directory', name : 'cluster_path'},
		],
	sortname: 'cluster_path',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	
	function NewClusterDirectory(){
		YahooWin6('650','$page?new-dir=&t=$t','$new_directory::Clusters');
	}
	
	function ClusterConfig(){
		YahooWin6('750','$page?Parameters=yes&t=$t','$parameters::Clusters');
	
	}
	
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#cluster-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload(); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}	

	
	
	var x_ClusterDirDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

	var x_ClusterSetConfig$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#flexRT$t').flexReload();
	}	
	
	function ClusterDirDelete$t(direnc,md5){
			mem$t=md5;
			var XHR = new XHRConnection();
			XHR.appendData('ClusterDirDelete',direnc);
    		XHR.sendAndLoad('$page', 'POST',x_ClusterDirDelete$t);		
	}	
	
	function ClusterSetConfig(){
			var XHR = new XHRConnection();
			XHR.appendData('ClusterSetConfig','yes');
    		XHR.sendAndLoad('$page', 'POST',x_ClusterSetConfig$t);		
	
	}
	
	
	
	function EmptyEvents(){
		if(confirm('$empty_events_text_ask')){
			var XHR = new XHRConnection();
			XHR.appendData('empty-table','yes');
			XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);			
		}
	
	}
	
</script>";
	
	echo $html;	
	
}
function node_events(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=350;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=645;
	$TB2_WIDTH=610;
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$t=time();
	$directory=$tpl->_ENGINE_parse_body("{directories}");
	$new_node=$tpl->_ENGINE_parse_body("{new_node}");
	$nodes=$tpl->_ENGINE_parse_body("{nodes}");
	$remote_node=$tpl->_ENGINE_parse_body("{remote_node}");
	$notified=$tpl->_ENGINE_parse_body("{notified}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$ID=$_GET["ID"];

	$sql="SELECT client_ip,parameters FROM glusters_clients WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ip=$ligne["client_ip"];
	
	$html="
	<div style='margin-left:-9px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?node-events-list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
		{display: '$events', name : 'client_ip', width :613, sortable : true, align: 'left'},
		
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'client_ip'},
		],
	sortname: 'client_ip',
	sortorder: 'asc',
	usepager: true,
	title: '$events::$ip',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

</script>";
	
	echo $html;		
	
}


function cluster_nodes(){	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=845;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{synchronize}");
	$t=time();
	$directory=$tpl->_ENGINE_parse_body("{directories}");
	$new_node=$tpl->_ENGINE_parse_body("{new_node}");
	$nodes=$tpl->_ENGINE_parse_body("{nodes}");
	$remote_node=$tpl->_ENGINE_parse_body("{remote_node}");
	$notified=$tpl->_ENGINE_parse_body("{status}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_this_node=$tpl->javascript_parse_text("{delete_this_node}");
	$buttons="
	buttons : [
	{name: '$new_node', bclass: 'Add', onpress : NewClusterNode$t},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig$t},
	
	],	";
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cluster-nodes-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$remote_node', name : 'client_ip', width :583, sortable : true, align: 'left'},
		{display: '$notified', name : 'client_notified', width :63, sortable : true, align: 'center'},
		{display: '$events', name : 'events', width :63, sortable : false, align: 'center'},
		{display: '$delete', name : 'del', width :63, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$nodes', name : 'client_ip'},
		],
	sortname: 'client_ip',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	
	function NewClusterNode$t(){
		YahooWin6('650','$page?new-node=&t=$t','$new_node::$remote_node');
	}
	
	function GlusterEventsClient(ID){
		YahooWin6('650','$page?node-events=yes&t=$t&ID='+ID,'$events::Clusters::'+ID);
	
	}
	
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#cluster-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload(); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}	

	
	
	var x_ClusterDirDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}		
	
	function ClusterDirDelete$t(direnc,md5){
			mem$t=md5;
			var XHR = new XHRConnection();
			XHR.appendData('ClusterDirDelete',direnc);
    		XHR.sendAndLoad('$page', 'POST',x_ClusterDirDelete$t);		
	}	
	
	var x_ClusterSetConfig$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#flexRT$t').flexReload();
	}	
	
	function ClusterDirDelete$t(direnc,md5){
			mem$t=md5;
			var XHR = new XHRConnection();
			XHR.appendData('ClusterDirDelete',direnc);
    		XHR.sendAndLoad('$page', 'POST',x_ClusterDirDelete$t);		
	}	
	
	function ClusterSetConfig$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ClusterSetConfig-nodes','yes');
    		XHR.sendAndLoad('$page', 'POST',x_ClusterSetConfig$t);		
	
	}	
	
	var x_DelClientCLientBut$t= function (obj) {
		var response=obj.responseText;
		if(response.length>3){alert(response);return}
	 	 $('#flexRT$t').flexReload();
	}	
	
	function DelClientCLientBut$t(ID){
		if(confirm('$delete_this_node ?')){
			var XHR = new XHRConnection();
			XHR.appendData('delete_cluster_client',ID);
			XHR.sendAndLoad('$page', 'GET',x_DelClientCLientBut$t);
		}
	}	
	

	
</script>";
	
	echo $html;	
	
}

function del_node(){
	$q=new mysql();
	$sql="SELECT * FROM glusters_clients  WHERE ID='{$_GET["delete_cluster_client"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if($ligne["client_ip"]==null){
		echo "{$_GET["delete_cluster_client"]} no such server";
		return;
	}
	$sql="UPDATE glusters_clients SET NotifToDelete=1 WHERE ID='{$_GET["delete_cluster_client"]}'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?probes=yes");	
}

function new_node(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$html="
	<div id='cluster-add-div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{hostname}:</td>
		<td valign='top' >". Field_text("hostname-$t",null,'width:150px;font-size:16px;padding:3px')."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{ipaddr}:</td>
		<td valign='top' >". field_ipv4("client_ip-$t",9000,'font-size:16px;padding:3px')."</td>
	</tr>	
	<tr>
		<td valign='top' colspan=2 align=right><hr>". button("{add}","AddClientCLientBut$t()","18px")."</td>
	</tr>
	</table>
	<script>
		var x_AddClientCLientBut$t= function (obj) {
			var response=obj.responseText;
			document.getElementById('cluster-add-div-$t').innerHTML='';
			if(response.length>3){alert(response);return}
		    YahooWin6Hide();
		    $('#flexRT$t').flexReload();
		}		
		
		function AddClientCLientBut$t(){
			var XHR = new XHRConnection();
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.appendData('client_ip',document.getElementById('client_ip-$t').value);
			AnimateDiv('cluster-add-div-$t');
			XHR.sendAndLoad('$page', 'POST',x_AddClientCLientBut$t);		
		}	
	</script>
	
	";	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function LocalDisks(){
	include_once 'ressources/usb.scan.inc';
	$sock=new sockets();
	$d=$sock->getFrameWork("cmd.php?fdiskl=yes");
	$d=unserialize(base64_decode($d));
	$t=$_GET["LocalDisks"];
	$tpl=new templates();
	$ARRAY[null]="{select}";
	$artmp=$_GLOBAL["disks_list"];

	while (list ($dev, $line) = each ($artmp)){
		if($dev=="size (logical/physical)"){continue;}
		$SIZE=$line["SIZE"];
		$ID_MODEL_2=$line["ID_MODEL_2"];
		$ID_VENDOR=$line["ID_VENDOR"];
		$ARRAY[$dev]="$dev ($SIZE) - $ID_VENDOR $ID_MODEL_2";
	}

	echo $tpl->_ENGINE_parse_body(Field_array_Hash($ARRAY, "diskMe-$t",null,"style:font-size:14px"));
	
	
}

function cluster_table(){

	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="gluster_paths";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){$q->CheckTables_gluster();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["cluster_path"]);
		$direnc=base64_encode($ligne["cluster_path"]);
		$delete=imgsimple("delete-24.png",null,"ClusterDirDelete$t('$direnc','$id')");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<psan style='font-size:16px;'>{$ligne["cluster_path"]}</span>",

		$delete )
		);
	}
	
	
echo json_encode($data);		


}

function node_events_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	$sql="SELECT parameters FROM glusters_clients WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ip=$ligne["client_ip"];
	$array=unserialize(base64_decode($ligne["parameters"]));
	$logs=$array["LOGS"];	
	@krsort($logs);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($logs);
	$data['rows'] = array();	
	
	while (list ($num, $ligne) = each ($logs) ){
		if(trim($ligne)==null){continue;}
		$ligne=nl2br($ligne);
	$data['rows'][] = array(
		'id' => md5($ligne),
		'cell' => array("<code style='font-size:12px'>$ligne</code>" )
		);		
		
		
	}
	
echo json_encode($data);
	
}



function cluster_nodes_list(){
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?probes=yes");
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="glusters_clients";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){$q->CheckTables_gluster();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
	

		$img="warning24.png";
		$events="&nbsp;";
		
		if(preg_match("#\(Connected\)#i", $ligne["state"])){$img="ok24.png";}
		if(preg_match("#Disconnected\)#i", $ligne["state"])){$img="error-24.png";}
		if(preg_match("#WARN:(.*)#i", $ligne["state"],$re)){$img="warning-panneau-42.png";$ligne["state"]=$re[1];}
		$NotifToDelete=$ligne["NotifToDelete"];
		$delete=imgsimple("delete-24.png","{delete}","DelClientCLientBut$t('{$ligne["ID"]}')");
		if($NotifToDelete==1){
			$text_color="#CCCCCC";
			$delete="&nbsp;";
			$img="ok24-grey.png";
		}else{$text_color="#000000";}
		
		$params=unserialize(base64_decode($ligne["parameters"]));
		if(is_array($params["LOGS"])){
			$events=imgtootltip("30-logs.png","{events}","GlusterEventsClient('{$ligne["ID"]}')");
		}		
		
		
		
	$data['rows'][] = array(
		'id' => $ligne["ID"],
		'cell' => array("
		<code style='font-size:16px;color:$text_color;font-weight:bold'>{$ligne["hostname"]}</code>
		<div style='font-size:11px'><i>{$ligne["client_ip"]}, {$ligne["uuid"]} - {$ligne["state"]}</i></div>
		
		",
		"<img src='img/$img'>",
		$events,
		$delete )
		);
	}
	
	
echo json_encode($data);		


}

function new_node_save(){
	$sql="INSERT INTO glusters_clients (client_ip,hostname) VALUES('{$_POST["client_ip"]}','{$_POST["hostname"]}');";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?probes=yes");
	
}


function Parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableCluster=$sock->GET_INFO("EnableCluster");
	if(!is_numeric($EnableCluster)){$EnableCluster=0;}
	$t=time();
	$p=Paragraphe_switch_img("{ACTIVATE_CLUSTER_MODE}", "{ACTIVATE_CLUSTER_MODE_EXPLAIN}","EnableSambaCluster",$EnableSambaCluster,null,450);
	$t=time();
	$html="
	<div id='$t-div'>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>$p</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveClusterParams()","16px")."</td>
	</tr>
	</table>
	</div>
	<script>
	function x_SaveClusterParams(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		YahooWin6Hide();
		$('#cluster-table-$t').flexReload();		
	}			
	
	
	function SaveClusterParams(){
		var EnableSambaCluster=document.getElementById('EnableSambaCluster').value;
		AnimateDiv('$t-div');
		var XHR = new XHRConnection();
		XHR.appendData('EnableSambaCluster',EnableSambaCluster);
		XHR.sendAndLoad('$page', 'GET',x_SaveClusterParams);			
	
	}	
	
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function new_directory(){
$page=CurrentPageName();
$tpl=new templates();
$t=$_GET["t"];

$samba=new samba();
$NewDirs[null]="{select}";
$dirs=$samba->main_shared_folders;
while (list ($dir, $name) = each ($dirs) ){
	if(trim($dir)==null){continue;}
	$NewDirs[$dir]=$name;
	
}



$html="

	<div id='alias-animate-$t'></div>
	
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("alias_dir-$t",null,"font-size:16px;padding:3px;width:320px",null,null,null,false,"ClusterDirCheck(event)").
		"&nbsp;<input type='button' OnClick=\"javascript:Loadjs('browse-disk.php?field=alias_dir-$t&replace-start-root=0');\" style='font-size:16px' value='{browse}...'></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{shared_directory}:</td>
		<td>". Field_array_Hash($NewDirs, "shared-$t",null,null,null,0,"font-size:16px")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","ClusterDirAdd$t()","18px")."</td>
	</tr>
	</table>
	
	<script>
		var x_ClusterDirAdd$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			document.getElementById('alias-animate-$t').innerHTML='';
			$('#flexRT$t').flexReload();
			YahooWin6Hide();
		}	

		function ClusterDirCheck(e){
			if(checkEnter(e)){
				ClusterDirAdd$t();
			}
		
		}
		

		function ClusterDirAdd$t(){
			var XHR = new XHRConnection();
			var directory=document.getElementById('alias_dir-$t').value;
			if(document.getElementById('shared-$t')){
				var directory2=document.getElementById('shared-$t').value;
				if(directory2.length>3){directory=directory2;}	
			}
			
			if(directory.length<2){return;}		
			
			XHR.appendData('new-directory',directory);
			AnimateDiv('alias-animate-$t');
    		XHR.sendAndLoad('$page', 'POST',x_ClusterDirAdd$t);			
		}
	</script>	
	
	
	";	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function new_directory_save(){
	$zmd=md5($_POST["new-directory"]);
	$_POST["new-directory"]=addslashes($_POST["new-directory"]);
	$sql="INSERT IGNORE INTO gluster_paths (cluster_path,zmd) VALUES ('{$_POST["new-directory"]}','$zmd')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}

function ClusterSetConfig(){
	$tpl=new templates();
	$q=new mysql();
	$items=$q->COUNT_ROWS("glusters_clients", "artica_backup");
	if($items==0){
		echo $tpl->javascript_parse_text("{cluster_not_save_config_no_clients}",1);
		return;
	}
	
	
	
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?conf=yes");
	$sock->getFrameWork("cmd.php?gluster-update-clients=yes");
	$sock->getFrameWork("cmd.php?gluster-notify-clients=yes");		
	
	echo $tpl->javascript_parse_text("{apply_upgrade_help}",1);
}

function ClusterSetConfig_nodes(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?probes=yes");
	
	
	
}

function directory_delete(){
	$path=base64_decode($_POST["ClusterDirDelete"]);
	$sql="DELETE FROM gluster_paths WHERE cluster_path='$path'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function master_events(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=845;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$function=$tpl->_ENGINE_parse_body("{function}");
	$nodes=$tpl->_ENGINE_parse_body("{nodes}");
	$remote_node=$tpl->_ENGINE_parse_body("{remote_node}");
	$notified=$tpl->_ENGINE_parse_body("{notified}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$buttons="
	buttons : [
	{name: '$new_node', bclass: 'Add', onpress : NewClusterNode$t},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig$t},
	
	],	";
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cluster-master-events-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'nonde', width :31, sortable : false, align: 'center'},
		{display: '$date', name : 'date', width :137, sortable : true, align: 'left'},
		{display: '$function', name : 'client_notified', width :207, sortable : false, align: 'left'},
		{display: '$events', name : 'events', width :402, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		],
	sortname: 'date',
	sortorder: 'desc',
	usepager: true,
	title: '/var/log/glusterfs/glusterfs.log',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
</script>";	
	
	echo $html;
}
function master_events_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$sock=new sockets();
	
	if($_POST["query"]<>null){$query="&query=".base64_encode(string_to_regex($_POST["query"]));}
	
	$datasLines=explode("\n", base64_decode($sock->getFrameWork("gluster.php?master-events=yes&rp=$rp$query")));
	
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($datasLines);
	$data['rows'] = array();	
	
	$TYP["W"]="warning24.png";
	$TYP["N"]="info-24.png";
	$TYP["E"]="danger24.png";
	if(($_POST["sortname"]=="date") && ($_POST["sortorder"]=="desc")){krsort($datasLines);}
	
	
	while (list ($num, $ligne) = each ($datasLines) ){
		if(trim($ligne)==null){continue;}
		$zdate="-";
		$type="-";
		$function="-";
		$img="&nbsp;";
		$events=$ligne;
		//[2012-07-17 01:04:30] W [transport.c:70:transport_load] transport: missing 'option transport-type'. defaulting to "socket"
		if(preg_match("#\[(.*?)\]\s+([A-Z])\s+\[(.*?)\]\s+(.+)#", $ligne,$re)){
			$zdate=$re[1];
			$type=$re[2];
			$function=$re[3];
			$events=$re[4];
		}
		
		if(isset($TYP[$type])){
			$img="<img src='img/{$TYP[$type]}'>";
		}
		
		
	$data['rows'][] = array(
		'id' => md5($ligne),
		'cell' => array(
			"$img",
			"<code style='font-size:12px'>$zdate</code>",
			"<code style='font-size:12px'>$function</code>",
			"<code style='font-size:12px'>$events</code>"

			)
		);		
		
		
	}
	
echo json_encode($data);	
	
}
