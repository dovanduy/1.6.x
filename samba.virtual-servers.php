<?php
	session_start();
	if(!isset($_SESSION["uid"])){echo "document.location.href='logoff.php'";die();}
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	$users=new usersMenus();
	if(!$users->AsSambaAdministrator){die("<H1>EXIT</H1>");}
	
	if(isset($_GET["js-inline"])){js_inline();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["browse-samba-list"])){virtual_servers_list();exit;}
	if(isset($_POST["delete-hostname"])){virtual_servers_delete();exit;}
	if(isset($_POST["EnableSambaVirtualsServers"])){EnableSambaVirtualsServersSave();exit;}
	if(isset($_GET["params"])){enable_feature();exit;}
	
	
function js_inline(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?popup=yes');";	
}
function EnableSambaVirtualsServersSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSambaVirtualsServers", $_POST["EnableSambaVirtualsServers"]);
	$sock->getFrameWork('cmd.php?samba-save-config=yes');
	
}

function enable_feature(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=$_GET["t"];
	$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");	
	$add=Paragraphe("64-net-server-add.png", "{add_virtual_server}", "{add_smb_virtual_server}","javascript:SambaVirtalServer('')");
	$opt= Paragraphe_switch_img("{enable_samba_virtual_servers}", "{enable_samba_virtual_servers_text}","EnableSambaVirtualsServers",$EnableSambaVirtualsServers,null,550);
	$tt=time();
	$html="
		<div id='$tt'></div>	
		$opt
		<hr>
	 	<div style='text-align:right'>". button("{apply}","EnableSambaVirtualsServersSave()",18)."</div>
	 <script>
		var x_EnableSambaVirtualsServersSave=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}			
			YahooWin3Hide();
			$('#flexRT$t').flexReload();
		}		
		
		function EnableSambaVirtualsServersSave(){
			var XHR = new XHRConnection();
			XHR.appendData('EnableSambaVirtualsServers',document.getElementById('EnableSambaVirtualsServers').value);
			AnimateDiv('$tt');
    		XHR.sendAndLoad('$page', 'POST',x_EnableSambaVirtualsServersSave);
		}
	</script>	 						
	 ";
	echo $tpl->_ENGINE_parse_body($html);
}

function about(){
	$page=CurrentPageName();
	$tpl=new templates();	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>{samba_virtual_explain}</div>");
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
		
	
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$workgroup=$tpl->_ENGINE_parse_body("{workgroup}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$virtual_servers=$tpl->_ENGINE_parse_body("{virtual_servers}");
	$ou=$tpl->_ENGINE_parse_body("{organization}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$sure_delete_smb_vrt=$tpl->javascript_parse_text("{sure_delete_smb_vrt}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$buttons="
	buttons : [
	{name: '$new_server', bclass: 'Add', onpress : SambaVirtalServerAdd},
	{name: '$parameters', bclass: 'Settings', onpress : SambaVirtalServerParams},
	
	],	";
			//$('#flexRT$t').flexReload();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?browse-samba-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width :438, sortable : true, align: 'left'},
		{display: '$workgroup', name : 'workgroup', width :129, sortable : true, align: 'left'},
		{display: '$ou', name : 'ou', width :141, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :53, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$workgroup', name : 'workgroup'},
		{display: '$ou', name : 'ou'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '$virtual_servers',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 864,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function SambaVirtalServer(server){
	Loadjs('samba.virtual-server.edit.php?hostname='+server+'&t=$t');
}

function SambaVirtalServerParams(){
	YahooWin3(650,'$page?params=yes&t=$t','$parameters');
}

function SambaVirtalServerAdd(){SambaVirtalServer('');}

		var x_SambaVirtalDel=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}			
			$('#flexRT$t').flexReload();
		}
	
	
		function SambaVirtalDel(hostname){
			if(confirm('$sure_delete_smb_vrt ['+hostname+']')){
				var XHR = new XHRConnection();
				XHR.appendData('delete-hostname',hostname);
				AnimateDiv('browse-samba-list');
    			XHR.sendAndLoad('$page', 'POST',x_SambaVirtalDel);
			}
		}

";
	echo $tpl->_ENGINE_parse_body($html);
}

function virtual_servers_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$users=new usersMenus();
	$sock=new sockets();
	
	$search='%';
	$table="samba_hosts";
	$tablesrc="samba_hosts";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
	if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
	
	if(!$q->TABLE_EXISTS($tablesrc, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($tablesrc,$database)==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No item");}
	
	
		
	while ($ligne = mysql_fetch_assoc($results)) {
		
		
		if($ligne["hostname"]=="master"){continue;}
		$md5=md5(serialize($ligne));
		$color="black";
		if($EnableSambaVirtualsServers==0){$color="#8a8a8a";}
		
		$select="<a href=\"javascript:blur();\" OnClick=\"javascript:SambaVirtalServer('{$ligne["hostname"]}')\"
		style='font-size:16px;text-decoration:underline;color:$color'>";
		
		$delete=imgsimple("delete-32.png","{delete}","SambaVirtalDel('{$ligne["hostname"]}')");
		

		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
				"$select{$ligne["hostname"]}</a><div><i style='font-size:10px'>{$ligne["ipaddr"]}</i></div>",
				"<span style='font-size:16px;'>{$ligne["workgroup"]}</span>",
				"<span style='font-size:16px;'>{$ligne["ou"]}</span>",
				"<span style='font-size:16px;'>$delete</span>",
				)
		);
	}
	
	
	echo json_encode($data);	
	
}

function virtual_servers_list_old(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();
	$sock=new sockets();
	$users=new usersMenus();
	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("***","*",$search);
	$search=str_replace("**","*",$search);
	$search_sql=str_replace("*","%",$search);
	$search_sql=str_replace("%%","%",$search_sql);
	$search_regex=str_replace(".","\.",$search);	
	$search_regex=str_replace("*",".*?",$search);
	$sure_delete_smb_vrt=$tpl->javascript_parse_text("{sure_delete_smb_vrt}");
	$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
	if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
	
	$add=imgtootltip("plus-24.png","{add}","SambaVirtalServer('')");
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th colspan=5>{virtual_servers}&nbsp;|&nbsp;$search_regex&nbsp;|&nbsp;$search_sql</th>
	</tr>
</thead>
<tbody class='tbody'>";

		$q=new mysql();
		$sql="SELECT hostname,ou,workgroup,ipaddr FROM samba_hosts WHERE hostname LIKE '$search_sql' ORDER BY hostname";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["hostname"]=="master"){continue;}

		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgtootltip("32-parameters.png","{edit}","SambaVirtalServer('{$ligne["hostname"]}')");
		$select2=imgtootltip("32-network-server.png","{edit}","SambaVirtalServer('{$ligne["hostname"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","SambaVirtalDel('{$ligne["hostname"]}')");
		$color="black";
		if($EnableSambaVirtualsServers==0){$color="#8a8a8a";}
		$html=$html."
		<tr class=$classtr>
			<td width=1%>$select2</td>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["hostname"]}<div><i style='font-size:10px'>{$ligne["ipaddr"]}</i></div></a></td>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["workgroup"]}</a></td>
			<td style='font-size:14px;font-weight:bold;color:$color'>&nbsp;{$ligne["ou"]}</td>
			<td width=1%>$select</td>
			<td width=1%>$delete</td>
		</tr>
		";
	}
	
	$html=$html."</table></center>
	<script>
	

	
		


	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function virtual_servers_delete(){
	$sql="DELETE FROM samba_hosts WHERE hostname='{$_POST["delete-hostname"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	}

