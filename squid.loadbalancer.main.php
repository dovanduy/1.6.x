<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."<H1>";
		die();exit();
	}	
	
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["edit-proxy-parent-js"])){parent_config_js();exit;}
	if(isset($_GET["edit-proxy-parent"])){parent_config();exit;}
	if(isset($_GET["edit-proxy-parent-tab"])){parent_tab();exit;}
	if(isset($_GET["edit-proxy-parent-table"])){parent_options_table();exit;}
	if(isset($_GET["SaveParentProxy"])){parent_save();exit;}
	if(isset($_GET["edit-proxy-parent-options"])){parent_options_popup();exit;}
	if(isset($_GET["edit-proxy-parent-options-explain"])){parent_options_explain();exit;}
	if(isset($_GET["extract-options"])){extract_options();exit;}
	if(isset($_POST["AddSquidParentOptionOrginal"])){construct_options();exit;}
	if(isset($_POST["DeleteSquidOption"])){delete_options();exit;}
	if(isset($_GET["parent-list"])){popup_list();exit;}
	if(isset($_GET["DeleteSquidParent"])){parent_delete();exit;}
	if(isset($_GET["EnableParentProxy"])){EnableParentProxy();exit;}
	if(isset($_GET["prefer_direct"])){prefer_direct();exit;}
	if(isset($_GET["nonhierarchical_direct"])){nonhierarchical_direct();exit;}
	if(isset($_GET["parent-list-options"])){extract_options();exit;}
	if(isset($_GET["ActionRun"])){ActionRun();exit;}
	if(isset($_GET["peer_infos"])){$GLOBALS["VERBOSE"]=true;peer_infos();exit;}
popup();
	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{squid_parent_proxy}");
	$html="YahooWin3('875','$page','$title');";
	echo $html;

}

function parent_delete(){
	$ID=$_GET["DeleteSquidParent"];
	
	$sql="DELETE FROM squid_parents WHERE ID=$ID";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");
	
}

function parent_config_js(){
	$ID=$_GET["edit-proxy-parent-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT * FROM squid_parents WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$title=$tpl->javascript_parse_text("{backend}:{$ligne["servername"]}");
		echo "YahooWin4('650','$page?edit-proxy-parent-tab=$ID&t={$_GET["t"]}','$title')";
	}else{
		$title=$tpl->javascript_parse_text("{new_backend}");
		echo "YahooWin4('450','$page?edit-proxy-parent=$ID&t={$_GET["t"]}','$title')";
	}
}

function parent_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["edit-proxy-parent-tab"];
	$q=new mysql();
	$sql="SELECT * FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
	$md5=md5(time().$ID);
	$array["config"]=$ligne["servername"];
	$array["options"]='{options}';
	$array["architecture-adv"]='{advanced_options}';
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="config"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?edit-proxy-parent=$ID&t={$_GET["t"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="options"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?edit-proxy-parent-table=$ID&t={$_GET["t"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		
	}
	
	
	
	echo "
	<div id=$md5>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#$md5').tabs();
			});
		</script>";	
	
	
	
}

function parent_save(){
	$ID=$_GET["ID"];
	if(strlen(trim($_GET["icp_port"]))==null){$_GET["icp_port"]=0;}
	$sql_add="INSERT INTO squid_parents (servername,server_port,server_type,icp_port,htcp_port)
	VALUES('{$_GET["servername"]}','{$_GET["server_port"]}','{$_GET["server_type"]}','{$_GET["icp_port"]}',
	'{$_GET["htcp_port"]}')";
	
	$sql_edit="UPDATE squid_parents SET 
		servername='{$_GET["servername"]}',
		server_port='{$_GET["server_port"]}',
		server_type='{$_GET["server_type"]}',
		icp_port='{$_GET["icp_port"]}',
		htcp_port='{$_GET["htcp_port"]}'
		WHERE ID=$ID";
	
	
	$q=new mysql();
	$sql=$sql_add;
	if($ID>0){$sql=$sql_edit;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n$sql";
		return;
	}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");
	
}

function popup(){
	
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$server_type=$tpl->_ENGINE_parse_body("{server_type}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$add_a_parent_proxy=$tpl->_ENGINE_parse_body("{new_backend}");
	$enable_squid_parent=$tpl->_ENGINE_parse_body("{enable_squid_parent}");
	$title2=$tpl->_ENGINE_parse_body("{edit_squid_parent_parameters}");
	$build_parameters=$tpl->javascript_parse_text("{apply_parameters}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
$html="

<div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:13px'>{prefer_direct}:</td>
		<td>". Field_checkbox("prefer_direct",1,$squid->prefer_direct,"prefer_direct$t()")."</td>
		<td width=1%>". help_icon("{squid_prefer_direct}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:13px'>{nonhierarchical_direct}:</td>
		<td>". Field_checkbox("nonhierarchical_direct",1,$squid->nonhierarchical_direct,"nonhierarchical_direct$t()")."</td>
		<td width=1%>". help_icon("{squid_nonhierarchical_direct}")."</td>
	</tr>	
	
	</table>

	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var md$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?parent-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
		{display: '$servername', name : 'servername', width :442, sortable : true, align: 'left'},
		{display: 'N', name : 'needed', width : 31, sortable : false, align: 'center'},
		{display: 'U', name : 'usable', width : 31, sortable : false, align: 'center'},
		{display: 'R', name : 'requested', width : 31, sortable : false, align: 'center'},
		{display: '$server_type', name : 'server_type', width : 124, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
		{display: 'delete', name : 'hits', width : 31, sortable : false, align: 'center'}

		],
		
buttons : [
		{name: '$add_a_parent_proxy', bclass: 'add', onpress : add_a_parent_proxy$t},
		{name: '$build_parameters', bclass: 'Reconf', onpress : build_parameters$t},
		
		],			
	
	searchitems : [
		{display: '$servername', name : 'servername'},
		],
	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 860,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function add_a_parent_proxy$t(){
		Loadjs('$page?edit-proxy-parent-js=0&t=$t');
	}
	
	function build_parameters$t(){
		Loadjs('squid.compile.progress.php');
	}
	
	var x_ActionRun$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
	}	
	
	function ActionRun$t(ID,value){
		var XHR = new XHRConnection();
		XHR.appendData('ActionRun',value);
		XHR.appendData('ID',ID);
		XHR.sendAndLoad('$page', 'GET',x_ActionRun$t);
	}


	function EditSquidParent$t(ID){
			Loadjs('$page?edit-proxy-parent-js='+ID+'&t=$t');
		}
		
		var x_DeleteSquidParent$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+md$t).remove();
		}		
		
		
		function DeleteSquidParent$t(ID,md){
			if(confirm('$delete '+ID+' ?')){
				md$t=md;
				var XHR = new XHRConnection();
				XHR.appendData('DeleteSquidParent',ID);
				XHR.sendAndLoad('$page', 'GET',x_DeleteSquidParent$t);
			}
		}

	function nonhierarchical_direct$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('nonhierarchical_direct').checked){
			XHR.appendData('nonhierarchical_direct',1);
		}else{
			XHR.appendData('nonhierarchical_direct',0);
		}
		XHR.sendAndLoad('$page', 'GET');
	}

	function prefer_direct$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('prefer_direct').checked){
			XHR.appendData('prefer_direct',1);
		}else{
			XHR.appendData('prefer_direct',0);
		}
		XHR.sendAndLoad('$page', 'GET');
	}		

</script>
";	
	echo $tpl->_ENGINE_parse_body($html);
}

function popup_list_options($datas){
	
	$array=unserialize(base64_decode($datas));
	if(!is_array($array)){return null;}
	
	
	
		while (list($num,$val)=each($array)){
			$c++;	
			if($num=="loginPASSTHRU"){$num="login=PASSTHRU";}
			if($num=="loginPASS"){$num="login=PASS";}			
			$tb[]="<i style='font-size:11px'>$num $val</i>";
							
					
			}	
			
		if(count($tb)>0){
			return "<div>".@implode(", " , $tb)."</div>";
		}
			
		
	
}

function peer_infos(){
	include_once("ressources/class.ccurl.inc");
	$squid=new squidbee();
	$sock=new sockets();
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$listenport=$squid->listen_port;
	
	}else{
		$listenport=$SquidMgrListenPort;
	}


	$uri="http://127.0.0.1:$listenport/squid-internal-mgr/peer_select";
	if($GLOBALS["VERBOSE"]){echo "<li> curl -> $uri</li>";}
	$curl=new ccurl("http://127.0.0.1:$squid->listen_port/squid-internal-mgr/peer_select");
	$curl->NoHTTP_POST=true;
	$curl->ArticaProxyServerEnabled="no";
	$curl->CURLOPT_NOPROXY="127.0.0.1";
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=5;
	$curl->UseDirect=true;		
	$curl->get();
	if($GLOBALS["VERBOSE"]){echo "<li> ". strlen( $curl->data)."</li>";}
	$datas=explode("\n", $curl->data);
	while (list ($index, $line) = each ($datas)){
		
		if(preg_match("#peer digest from\s+(.+)#", $line,$re)){$serv=$re[1];}
		if(preg_match("#needed:\s+(.+?),\s+usable:\s+(.+?),\s+requested:\s+(.+)#", $line,$re)){
			$ARR[$serv]["needed"]=$re[1];
			$ARR[$serv]["usable"]=$re[2];
			$ARR[$serv]["requested"]=$re[3];
		}
	
		if(preg_match("#requests sent: ([0-9]+), volume: (.+)#",$line,$re)){
			$ARR[$serv]["RQSENT"]=$re[1];
			$ARR[$serv]["VOL"]=$re[2];
		}
	
	
		
	}
	
	return $ARR;
	
}

function popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$squid=new squidbee();
	$no_backend_defined=$tpl->javascript_parse_text("{no_backend_defined}");
	$search='%';
	$table="squid_parents";
	$MySQLbase="artica_backup";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	$t=$_GET["t"];
	
	
	$ARR=peer_infos();
	
		
	if(!$q->FIELD_EXISTS("squid_parents","enabled","artica_backup")){
		$sql="ALTER TABLE `squid_parents` ADD `enabled` smallint( 1 ) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
		
	$total=0;
	if($q->COUNT_ROWS($table,$MySQLbase)==0){json_error_show($no_backend_defined,1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$MySQLbase);
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$md5=md5(serialize($ligne));
		//if($squid->EnableParentProxy==0){$color="#CACACA";}
		if(!is_numeric($ligne["icp_port"])){$ligne["icp_port"]=0;}
		
		if($ligne["enabled"]==0){
			$icon="24-stop.png";
			$action_run=1;
			$color="#CACACA";
		}else{
			$icon="24-run.png";
			$action_run=0;
		}		
		
		$ahref="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:EditSquidParent$t({$ligne["ID"]})\" 
		style='font-size:16px;text-decoration:underline;font-weight:bold;color:$color'>";

		$delete="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:DeleteSquidParent$t({$ligne["ID"]},'$md5')\">
		<img src='img/delete-24.png' style='border:0px;color:$color'>
		</a>";
		
		if($ligne["icp_port"]>0){$ligne["server_port"]=$ligne["server_port"]."&raquo;&nbsp;".$ligne["icp_port"];}
		if($ligne["htcp_port"]>0){$ligne["server_port"]=$ligne["server_port"]."&raquo;&nbsp;".$ligne["htcp_port"];}
		$option=popup_list_options($ligne["options"]);
		if($ligne["icp_port"]==0){
			$option=$option.$tpl->_ENGINE_parse_body("&nbsp;<i style='font-size:11px'>ICP {disabled}</i>");
		}
		
		if($ligne["htcp_port"]==0){
			$option=$option.$tpl->_ENGINE_parse_body("&nbsp;<i style='font-size:11px'>HTCP {disabled}</i>");
		}		
		 
		$needed=$ARR[$ligne["servername"]]["needed"];
		$usable=$ARR[$ligne["servername"]]["usable"];
		$requested=$ARR[$ligne["servername"]]["requested"];
		
		
		$run=imgsimple($icon,null,"ActionRun$t({$ligne["ID"]},$action_run)");
		
	$data['rows'][] = array(
		'id' =>$md5,
		'cell' => array(
				"<img src='img/32-network-server.png'>",
				"$ahref{$ligne["servername"]}:{$ligne["server_port"]}</a>$option",
				"$ahref$needed</a>",
				"$ahref$usable</a>",
				"$ahref$requested</a>",
				"$ahref{$ligne["server_type"]}</a>",
				"$run",
				$delete )
		);
	}
	
	
echo json_encode($data);	
}


function parent_options_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["edit-proxy-parent-table"];
	$addoptions=$tpl->javascript_parse_text("{squid_parent_options}");
	$add=$tpl->javascript_parse_text("{add}");
	$tt=time();
	$t=$_GET["t"];
	
	$html="<table class='parent-options-$tt' style='display: none' id='parent-options-$tt' style='width:100%'></table>
<script>
	var rowmem='';
$(document).ready(function(){
$('#parent-options-$tt').flexigrid({
	url: '$page?parent-list-options=yes&t=$tt&ID=$ID&table-source=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :24, sortable : true, align: 'left'},
		{display: '$options', name : 'server_port', width : 486, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'left'}

		],
		
buttons : [
		{name: '$add', bclass: 'add', onpress : add_a_parent_option},
		],			
	

	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '$addoptions',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 597,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
		function add_a_parent_option(){ 
			YahooWin5('450','$page?edit-proxy-parent-options=yes&ID=$ID&t=$tt&table-source=$t','$addoptions');
		
		}
		
		var x_AddSquidOption$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+rowmem).remove();
			$('#parent-options-$tt').flexReload();
			$('#flexRT$t').flexReload();
		}		

		function DeleteSquidOption(key,ID){
			var rowmem=ID;
			var XHR = new XHRConnection();
			XHR.appendData('DeleteSquidOption',key);
			XHR.appendData('ID',$ID);
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$t);
		}


	</script>	
	
	
	";
	echo $html;
}



function parent_config(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$tt=$_GET["table-source"];
	$ID=$_GET["edit-proxy-parent"];
	$array_type["parent"]="parent";
	$array_type["sibling"]="sibling";
	$array_type["multicast"]="multicast";
	$q=new mysql();
	$sql="SELECT * FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$button="{apply}";

	$add=$tpl->_ENGINE_parse_body("{add}");
	
	if($ID<1){$button="{add}";$addoptions=null;}
	if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
	$options=$tpl->_ENGINE_parse_body("{options}");
	$html="
	<div class=text-info style='font-size:14px'>{warning_lb_icp}</div>
	<input type='hidden' id='SquidParentOptions' name='SquidParentOptions' value=\"{$ligne["options"]}\">
	<div id='EditSquidParentSaveID-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("servername-$t",$ligne["servername"],"font-size:16px;padding:3px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("server_port-$t",$ligne["server_port"],"font-size:16px;padding:3px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{icp_port}:</td>
		<td>". Field_text("icp_port-$t",$ligne["icp_port"],"font-size:16px;padding:3px;width:50px")."</td>
		<td>". help_icon("{icp_port_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{htcp_port}:</td>
		<td>". Field_text("htcp_port-$t",$ligne["htcp_port"],"font-size:16px;padding:3px;width:50px")."</td>
		<td>". help_icon("{htcp_port_explain}")."</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:16px'>{server_type}:</td>
		<td>". Field_array_Hash($array_type,"server_type-$t",$ligne["server_type"],null,null,0,"font-size:16px")."</td>
		<td>". help_icon("{squid_parent_sibling_how_to}")."</td>
	</tr>
	<tr>
	
		<td colspan=3 align='right'><hr>". button("$button","EditSquidParentSave$t()",18)."
	</td>
	
	</table>

				

	</div>
	<script>
	
		var x_EditSquidParentSaveReturn$t= function (obj) {
			document.getElementById('EditSquidParentSaveID-$t').innerHTML='';
			var results=obj.responseText;
			var ID=$ID;
			$('#flexRT$t').flexReload();
			if(results.length>0){alert(results);return;}
			if(ID==0){YahooWin4Hide();}
			
		}			
		
		function EditSquidParentSave$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ID','$ID');
			XHR.appendData('SaveParentProxy','$ID');
			XHR.appendData('servername',document.getElementById('servername-$t').value);
			XHR.appendData('server_port',document.getElementById('server_port-$t').value);
			XHR.appendData('server_type',document.getElementById('server_type-$t').value);
			XHR.appendData('icp_port',document.getElementById('icp_port-$t').value);
			XHR.appendData('htcp_port',document.getElementById('htcp_port-$t').value);
			
			
			
			AnimateDiv('EditSquidParentSaveID-$t');
			XHR.sendAndLoad('$page', 'GET',x_EditSquidParentSaveReturn$t);			
		
		}	
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function parent_options_popup(){
	$tt=time();
	$t=$_GET["t"];
	$ttt=$_GET["table-source"];
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$array=unserialize(base64_decode($_GET["edit-proxy-parent-options"]));
	$options[null]="{select}";
	$options[base64_encode("proxy-only")]="proxy-only";
	$options[base64_encode("Weight=n")]="Weight=n";
	$options[base64_encode("ttl=n")]="ttl=n";
	$options[base64_encode("basetime=n")]="basetime=n";
	$options[base64_encode("no-query")]="no-query";
	$options[base64_encode("default")]="default";
	$options[base64_encode("round-robin")]="round-robin";
	$options[base64_encode("multicast-responder")]="multicast-responder";
	$options[base64_encode("closest-only")]="closest-only";
	$options[base64_encode("no-digest")]="no-digest";
	$options[base64_encode("no-netdb-exchange")]="no-netdb-exchange";
	$options[base64_encode("no-delay")]="no-delay";
	$options[base64_encode("login=user:password")]="login=user:password";
	$options[base64_encode("connect-timeout=nn")]="connect-timeout=nn";
	$options[base64_encode("digest-url=url")]="digest-url=url";
	$options[base64_encode("connect-fail-limit=n")]="connect-fail-limit=n";
	$options[base64_encode("loginPASSTHRU")]="login=PASSTHRU";
	$options[base64_encode("connection-auth")]="connection-auth=on|off";
	$options[base64_encode("loginPASS")]="login=PASS";
	$options[base64_encode("carp")]="carp";
	//$options[base64_encode("ssl")]="ssl";
	
	$html="
	<table style='width:100%'>
	<tr>	
		<td class=legend style='font-size:13px'>{squid_parent_options}:</td>
		<td>". Field_array_Hash($options,"squid_parent_options_f",base64_encode("proxy-only"),"FillSquidParentOptions$tt()",null,0,
		"font-size:16px;padding:5px")."</td>
	</tr>
	</table>
	<div id='squid_parent_options_filled'></div>
	<script>
	
	function FillSquidParentOptions$tt(){
			var selected=document.getElementById('squid_parent_options_f').value
			LoadAjax('squid_parent_options_filled','$page?edit-proxy-parent-options-explain='+selected+'&ID=$ID&tt=$tt');
		}
		
		var x_AddSquidOption$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin5Hide();
			$('#parent-options-$t').flexReload();
			$('#flexRT$ttt').flexReload();
		}		
	
	
		function AddSquidOption$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('AddSquidParentOptionOrginal',document.getElementById('SquidParentOptions').value);
			XHR.appendData('key',document.getElementById('squid_parent_options_f').value);
			XHR.appendData('ID',$ID);
			if(document.getElementById('parent_proxy_add_value')){
				XHR.appendData('value',document.getElementById('parent_proxy_add_value').value);
			}
			
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$tt);
		}
	
	
		FillSquidParentOptions$tt();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function parent_options_explain(){
	$tt=$_GET["tt"];
	if($_GET["edit-proxy-parent-options-explain"]==null){return null;}
	$page=CurrentPageName();
	$options[base64_encode("proxy-only")]="{parent_options_proxy_only}";
	$options[base64_encode("Weight=n")]="{parent_options_proxy_weight}";
	$options[base64_encode("ttl=n")]="{parent_options_proxy_ttl}";
	$options[base64_encode("no-query")]="{parent_options_proxy_no_query}";
	$options[base64_encode("default")]="{parent_options_proxy_default}";
	$options[base64_encode("round-robin")]="{parent_options_proxy_round_robin}";
	$options[base64_encode("multicast-responder")]="{parent_options_proxy_multicast_responder}";
	$options[base64_encode("closest-only")]="{parent_options_proxy_closest_only}";
	$options[base64_encode("no-digest")]="{parent_options_proxy_no_digest}";
	$options[base64_encode("no-netdb-exchange")]="{parent_options_proxy_no_netdb_exchange}";
	$options[base64_encode("no-delay")]="{parent_options_proxy_no_delay}";
	$options[base64_encode("login=user:password")]="{parent_options_proxy_login}";
	$options[base64_encode("connect-timeout=nn")]="{parent_options_proxy_connect_timeout}";
	$options[base64_encode("digest-url=url")]="{parent_options_proxy_digest_url}";
	$options[base64_encode("connect-fail-limit=n")]="{parent_options_connect_fail_limit}";
	$options[base64_encode("carp")]="{parent_options_carp}";
	$options[base64_encode("loginPASSTHRU")]="{parent_options_login_passthru}";
	$options[base64_encode("loginPASS")]="{parent_options_login_pass}";
	$options[base64_encode("connection-auth")]="{parent_options_connection_auth}";
	$options[base64_encode("basetime=n")]="{parent_options_basetime_n}";
	
		
	
	$options_forms[base64_encode("digest-url=url")]=true;
	$options_forms[base64_encode("connect-timeout=nn")]=true;
	$options_forms[base64_encode("ttl=n")]=true;
	$options_forms[base64_encode("Weight=n")]=true;
	$options_forms[base64_encode("login=user:password")]=true;
	$options_forms[base64_encode("connect-fail-limit=n")]=true;
	$options_forms[base64_encode("connection-auth")]=true;
	$options_forms[base64_encode("basetime=n")]=true;
	
	if($options_forms[$_GET["edit-proxy-parent-options-explain"]]){
		$form="
		<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>". base64_decode($_GET["edit-proxy-parent-options-explain"]).":</td>
			<td>". Field_text("parent_proxy_add_value",null,"font-size:14px;padding:3px")."</td>
		</tr>
		</table>";
		
	}
	
	$html="<div class=text-info style='font-size:14px'>{$options[$_GET["edit-proxy-parent-options-explain"]]}</div>
	$form
	<div style='text-align:right'><hr>
	". button("{add} ".base64_decode($_GET["edit-proxy-parent-options-explain"]),"AddSquidOption$tt()",16)."</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function extract_options(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_GET["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$array=unserialize(base64_decode($ligne["options"]));
	if(!is_array($array)){json_error_show("No data");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
		$c=0;
		while (list($num,$val)=each($array)){
			$c++;	
			$md5=md5("PPROXY-OPTION-$ID-$num");
			$data['rows'][] = array(
					'id' =>"$md5",
					'cell' => array(
							"<img src='img/arrow-right-24.png'>",
							"<strong style='font-size:14px'>$num <i>$val</i></strong>",
							imgsimple("delete-24.png","{delete}","DeleteSquidOption('$num','$md5')") )
					);			
			}
		
	
	
	$data['page'] = 1;
	$data['total'] = $c;
	
	echo json_encode($data);
	
}




function construct_options(){
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$based=unserialize(base64_decode($ligne["options"]));
	$key=base64_decode($_POST["key"]);
	
	writelogs("$ID]decoded key:\"$key\"",__FUNCTION__,__FILE__,__LINE__);
	if(preg_match("#(.+?)=#",$key,$re)){
		$key=$re[1];
	}
	
	
	if(!is_array($based)){
		$based[$key]=$_POST["value"];
		writelogs("$ID]send ". serialize($based),__FUNCTION__,__FILE__,__LINE__);
		$NewOptions=base64_encode(serialize($based));
		$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
		return;
	}
	
	$based[$key]=$_POST["value"];
	
	while (list($num,$val)=each($based)){	
		if(trim($num)==null){continue;}
		$f[$num]=$val;
	}
	
	
	$NewOptions=base64_encode(serialize($f));
	$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	
	
}

function ActionRun(){
	$q=new mysql();
		if(!$q->FIELD_EXISTS("squid_parents","enabled","artica_backup")){
			$sql="ALTER TABLE `squid_parents` ADD `enabled` smallint( 1 ) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}	
	
	$sql="UPDATE squid_parents SET enabled='{$_GET["ActionRun"]}' WHERE ID='{$_GET["ID"]}'";
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();	
	$sock->getFrameWork("squid.php?build-smooth=yes");
}

function delete_options(){
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$array=unserialize(base64_decode($ligne["options"]));
	$key=$_POST["DeleteSquidOption"];
	
	writelogs("DELETING $key FOR {$_POST["ID"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_array($array)){
		writelogs("Not an array...",__FUNCTION__,__FILE__,__LINE__);
		echo "unable to unserialize $array\n";
		$array=array();
		return;
		}
	unset($array[$key]);
	$newarray=base64_encode(serialize($array));	
	$sql="UPDATE squid_parents SET options='$newarray' WHERE ID='{$_POST["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
}

function EnableParentProxy(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$ini->_params["NETWORK"]["EnableParentProxy"]=$_GET["EnableParentProxy"];
	$sock->SET_INFO("ArticaSquidParameters",$ini->toString());
	
	
}
function prefer_direct(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$ini->_params["NETWORK"]["prefer_direct"]=$_GET["prefer_direct"];
	$sock->SET_INFO("ArticaSquidParameters",$ini->toString());
	
}function nonhierarchical_direct(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$ini->_params["NETWORK"]["nonhierarchical_direct"]=$_GET["nonhierarchical_direct"];
	$sock->SET_INFO("ArticaSquidParameters",$ini->toString());
	
}



?>