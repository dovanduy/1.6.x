<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if($user->AsDnsAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_POST["ID"])){item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["replic-item"])){item_replic();exit;}
table();



function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=880;
//https://192.168.1.245:9000/
	$new_entry=$tpl->_ENGINE_parse_body("{new_artica_server}");
	$execute_replication=$tpl->_ENGINE_parse_body("{execute_replication}");
	$t=time();
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$events=$tpl->_ENGINE_parse_body("events");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewArticasrv2$t},
	{name: '$execute_replication', bclass: 'Reconf', onpress : ExecReplic$t},
	{name: '$events', bclass: 'Script', onpress : Events$t},
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		{display: '$hostname', name : 'hostname', width :735, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'name'},
		
		
		
		
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 880,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_PdnsRecordDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}
	
	var x_ExecReplic$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#flexRT$t').flexReload();
	}	

function ArticaSRvDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_PdnsRecordDelete$t);	
	}
	
function NewArticasrv2$t(id){
	YahooWin5('550','$page?item-id=0&t=$t','PowerDNS:$new_entry');
}	
	
function NewArticasrv$t(id){
	var title=id;
	if(!id){id=0;title='$new_entry';}
	YahooWin5('550','$page?item-id='+id+'&t=$t','PowerDNS:'+title);
}

function ExecReplic$t(){
	var XHR = new XHRConnection();
	XHR.appendData('replic-item','yes');
    XHR.sendAndLoad('$page', 'POST',x_ExecReplic$t);	
}

function Events$t(){
	Loadjs('squid.update.events.php?table=system_admin_events&category=pdns');
}

	
</script>";
	
	echo $html;		
}	

function items(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="pdns_replic";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",0);}
	
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$delete=imgsimple("delete-24.png",null,"ArticaSRvDelete$t('$id')");
		$servername=gethostbyaddr($ligne["hostname"]);
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<img src='img/30-computer.png'>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:NewArticasrv$t($id);\" 
		style='font-size:16px;text-decoration:underline'>{$ligne["hostname"]}:{$ligne["host_port"]}</a> - $servername",
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	$bname="{add}";
	$page=CurrentPageName();
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM pdns_replic WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hostname=$ligne["hostname"];
		$port=$ligne["host_port"];
		$datas=unserialize(base64_decode($ligne["host_cred"]));
		$username=$datas["username"];
		$password=$datas["password"];
	}

	if(!is_numeric($port)){$port=9000;}
	if($username==null){$username="Manager";}
	$tf=md5($t);
$html="		
<div id='anime-$t'></div>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{artica_server_address}:</strong></td>
	<td align=left>". field_ipv4("ComputerIP-$tf",$hostname,'font-size:14px')."</strong></td>
<tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{artica_console_port}:</strong></td>
	<td align=left>". Field_text("port-$tf",$port,"width:90px;font-size:14px","script:SaveArticaSrvCheck$tf(event)")."</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{artica_manager}:</strong></td>
	<td align=left>". Field_text("username-$tf",$username,"width:180px;font-size:14px","script:SaveArticaSrvCheck$tf(event)")."</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{password}:</strong></td>
	<td align=left>". Field_password("password-$tf",$password,"width:90px;font-size:14px","script:SaveArticaSrvCheck$tf(event)")."</strong></td>
</tr>

<tr>	
	<td colspan=2 align='right'><hr>". button("$bname","SaveArticaSrv$tf();","18px")."</td>
</tr>
</table>

<script>

		
		function SaveArticaSrvCheck$tf(e){
			
			if(checkEnter(e)){SaveArticaSrv$tf();return;}
			
		}
		
		var x_SaveArticaSrv$tf=function (obj) {
			var results=obj.responseText;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>0){alert(results);return;}
			$('#flexRT$t').flexReload();
		}				
		
		function SaveArticaSrv$tf(){
			var XHR = new XHRConnection();
			XHR.appendData('ID','$id');
			XHR.appendData('hostname',document.getElementById('ComputerIP-$tf').value);
			XHR.appendData('port',document.getElementById('port-$tf').value);
			XHR.appendData('username',document.getElementById('username-$tf').value);
			var pp=encodeURIComponent(document.getElementById('password-$tf').value);
			XHR.appendData('password',pp);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveArticaSrv$tf);
		
		}

</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
		$_POST["password"]=url_decode_special_tool($_POST["password"]);
		$datas=base64_encode(serialize($_POST));
		$ID=$_POST["ID"];
	if($ID==0){
		$sql="INSERT IGNORE INTO pdns_replic (hostname,host_port,host_cred) VALUES ('{$_POST["hostname"]}','{$_POST["port"]}','$datas')";
	}else{
		$sql="UPDATE pdns_replic SET hostname='{$_POST["hostname"]}',
		host_port='{$_POST["port"]}',host_cred='$datas' WHERE ID=$ID";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}
function item_delete(){
	$id=$_POST["item-delete"];
	$q=new mysql();
	$sql="SELECT * FROM pdns_replic WHERE ID=$id";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$sql="DELETE FROM records WHERE articasrv='{$ligne["hostname"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="DELETE FROM pdns_replic WHERE ID='$id'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}

function item_replic(){
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?replic=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{replic_executed_in_background_mode}");
	
}


