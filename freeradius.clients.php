<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die();}
	// freeradius_db
	
	
	if(isset($_GET["connection-id-js"])){connection_id_js();exit;}
	if(isset($_GET["connection-form-id"])){connection_id();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["shortname"])){connection_save();exit;}
	if(isset($_GET["query"])){connection_list();exit;}
	if(isset($_POST["EnableLocalLDAPServer"])){EnableLocalLDAPServer();exit;}
	if(isset($_POST["connection-delete"])){connection_delete();exit;}
	if(isset($_POST["EnableDisable"])){connection_enable();exit;}
	page();
function connection_id_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$connection_id=urlencode($_GET["connection-id-js"]);
	$title=$tpl->javascript_parse_text("{new_profile}");
	$t=$_GET["t"];
	
	if($connection_id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array(
				$q->QUERY_SQL("SELECT shortname FROM freeradius_clients WHERE ipaddr='{$_GET["connection-id-js"]}'","artica_backup"));
		$title=utf8_decode($tpl->javascript_parse_text($ligne["shortname"]));
	}
	
	echo "YahooWin2('650','$page?connection-form-id=$connection_id&t=$t','$title')";
	
}


function connection_id(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$connection_id=$_GET["connection-form-id"];
	if(strlen($connection_id)>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysql_fetch_array(
				$q->QUERY_SQL("SELECT * FROM freeradius_clients WHERE ipaddr='$connection_id'","artica_backup"));
	}
	if($ligne["nastype"]==null){$ligne["nastype"]="other";}
	$CONNECTIONS_TYPE["other"]="{other}";
	$CONNECTIONS_TYPE["cisco"]="Cisco Access Server family ";
	$CONNECTIONS_TYPE["computone"]="Computone PowerRack";
	$CONNECTIONS_TYPE["livingston"]="Livingston PortMaster";
	$CONNECTIONS_TYPE["max40xx"]="Ascend Max 4000 family";
	$CONNECTIONS_TYPE["multitech"]="Multitech CommPlete Server";
	$CONNECTIONS_TYPE["netserver"]="3Com/USR NetServer";
	$CONNECTIONS_TYPE["pathras"]="Cyclades PathRAS";
	$CONNECTIONS_TYPE["patton"]="Patton 2800 family";
	$CONNECTIONS_TYPE["portslave"]="Cistron PortSlave";
	$CONNECTIONS_TYPE["tc"]="3Com/USR TotalControl ";
	$CONNECTIONS_TYPE["usrhiper"]="3Com/USR Hiper Arc Total Control";	
	
	$nastype=Field_array_Hash($CONNECTIONS_TYPE, "nastype-$t",$ligne["nastype"],
			"blur()",null,0,"font-size:16px");
	
	$html="<div id='anim-$t'></div>
	<div style='font-size:14px' class=text-info>{freeradius_addrexpl}</div>		
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{name}:</td>
		<td>". Field_text("shortname-$t",$ligne["shortname"],"font-size:16px;width:220px")."</td>		
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{address}:</td>
		<td>". Field_text("ipaddr-$t",$ligne["ipaddr"],"font-size:16px;width:220px")."</td>		
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td>$nastype</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("secret-$t",$ligne["secret"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>		
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",18)."</td>
	</tr>	
	</table>	
	
			
	
	<script>
	var x_Save$t= function (obj) {
	var connection_id='$connection_id';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	document.getElementById('$t').innerHTML='';
	if(connection_id.length==0){YahooWin2Hide();}
	$('#$t').flexReload();
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('shortname', encodeURIComponent(document.getElementById('shortname-$t').value));
	XHR.appendData('ipaddr', document.getElementById('ipaddr-$t').value);
	XHR.appendData('nastype', document.getElementById('nastype-$t').value);
	XHR.appendData('ID', '$connection_id');
	XHR.appendData('secret', encodeURIComponent(document.getElementById('secret-$t').value));
	AnimateDiv('anim-$t');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}	

function check$t(){
	var connection_id='$connection_id';
	document.getElementById('ipaddr-$t').disabled=true;
	if(connection_id.length==0){ document.getElementById('ipaddr-$t').disabled=false;}
	
	
}
 check$t();
 </script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function connection_form(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$connection_id=$_GET["connection-id"];	
	$cnxt=$_GET["cnxt"];
	
	if($cnxt=="ldap"){connection_form_ldap();exit;}
	if($cnxt=="ad"){connection_form_ad();exit;}

	
}



function connection_save(){
	$q=new mysql();
	$_POST["shortname"]=url_decode_special_tool($_POST["shortname"]);
	$_POST["secret"]=url_decode_special_tool($_POST["secret"]);
	if($_POST["shortname"]==null){$_POST["shortname"]=time();}
	
	$ligne=mysql_fetch_array(
	$q->QUERY_SQL("SELECT ipaddr FROM freeradius_clients WHERE ipaddr='{$_GET["connection-id-js"]}'","artica_backup"));
	
	if($ligne["ipaddr"]==null){
		$sql="INSERT IGNORE INTO freeradius_clients
				(`shortname`,`ipaddr` ,`nastype`,`secret`,`enabled`)
			VALUES('{$_POST["shortname"]}','{$_POST["ipaddr"]}','{$_POST["nastype"]}','{$_POST["secret"]}',1)";
		
	}else{
		$sql="UPDATE freeradius_db SET `shortname`='{$_POST["shortname"]}',
		`nastype`='{$_POST["nastype"]}',`secret`='{$_POST["secret"]}'
		WHERE `ipaddr`='{$_POST["ipaddr"]}'
		";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");
	
}
	
function page(){
	
		$page=CurrentPageName();
		$tpl=new templates();
		$q=new mysql();
		$sock=new sockets();
		$shortname=$tpl->javascript_parse_text("{name}");
		$nastype=$tpl->javascript_parse_text("{type}");
		$enabled=$tpl->javascript_parse_text("{enabled}");
		$connection=$tpl->javascript_parse_text("{connection}");
		$add=$tpl->javascript_parse_text("{new_profile}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$tablewidht=883;
		$t=time();
	
		$buttons="buttons : [
		{name: '$add', bclass: 'Add', onpress : AddConnection$t},
		],	";
	

	
echo "
<table class='$t' style='display: none' id='$t' style='width:99%;text-align:left'></table>
<script>
	var MEMM$t='';
	$(document).ready(function(){
		$('#$t').flexigrid({
			url: '$page?query=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'center'},
			{display: '$shortname', name : 'shortname', width : 519, sortable : false, align: 'left'},
			{display: '$nastype', name : 'nastype', width : 158, sortable : true, align: 'left'},
			{display: '$enabled', name : 'enabled', width : 40, sortable : true, align: 'center'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$shortname', name : 'shortname'},
		{display: '$ipaddr', name : 'ipaddr'}
		],
		sortname: 'shortname',
		sortorder: 'asc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $tablewidht,
		height: 450,
		singleSelect: true
		});
	});
	
	
	
	function RefreshTable$t(){
		$('#$t').flexReload();
	}
	
	function enable_ip_authentication_save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
	XHR.appendData('servername','{$_GET["servername"]}');
			XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
	}
	
	var x_Refresh$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTable$t()
	}
	
	var x_ConnectionDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#row'+MEMM$t).remove();
	}
	
	function AddConnection$t(){
		Loadjs('$page?connection-id-js=&t=$t');
	}
	
	function EnableLocalLDAPServer$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableLocalLDAPServer','yes');
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function EnableDisable$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableDisable',ID);
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function ConnectionDelete$t(id,row){
	MEMM$t=row;
	var XHR = new XHRConnection();
	XHR.appendData('connection-delete',id);
	XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
		
	}
</script>
	";
}	

function connection_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$database="artica_backup";
	$t=$_GET["t"];
	$search='%';
	$table="freeradius_clients";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=null;
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$ldap=$tpl->javascript_parse_text("{ldap}");
	$local_ldap_service=$tpl->_ENGINE_parse_body("{local_ldap_service}");
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	$CONNECTIONS_TYPE["other"]="{other}";
	$CONNECTIONS_TYPE["cisco"]="Cisco Access Server family ";
	$CONNECTIONS_TYPE["computone"]="Computone PowerRack";
	$CONNECTIONS_TYPE["livingston"]="Livingston PortMaster";
	$CONNECTIONS_TYPE["max40xx"]="Ascend Max 4000 family";
	$CONNECTIONS_TYPE["multitech"]="Multitech CommPlete Server";
	$CONNECTIONS_TYPE["mikrotik"]="Mikrotik";
	$CONNECTIONS_TYPE["netserver"]="3Com/USR NetServer";
	$CONNECTIONS_TYPE["pathras"]="Cyclades PathRAS";
	$CONNECTIONS_TYPE["patton"]="Patton 2800 family";
	$CONNECTIONS_TYPE["portslave"]="Cistron PortSlave";
	$CONNECTIONS_TYPE["tc"]="3Com/USR TotalControl ";
	$CONNECTIONS_TYPE["usrhiper"]="3Com/USR Hiper Arc Total Control";
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$ipaddrenc=urlencode($ligne["ipaddr"]);
		$ipaddrencc=md5($ipaddrenc);
		$disable=Field_checkbox("sessionid_$ipaddrencc", 1,$ligne["enabled"],"EnableDisable$t('{$ligne["ipaddr"]}')");
		//$ligne['shortname']=utf8_encode($ligne['shortname']);
		
		
		
		$delete=imgsimple("delete-24.png",null,"ConnectionDelete$t('{$ligne['ipaddr']}','$ipaddrencc')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		

		$data['rows'][] = array(
				'id' => $ipaddrencc,
				'cell' => array("
						<img src='img/folder-network-32.png'>",
						"<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('$MyPage?connection-id-js=$ipaddrenc&t=$t');\" 
						style=\"font-size:16px;text-decoration:underline;color:$color\">
						{$ligne['shortname']}</a>
						<div style='font-size:11px'><i>{$ligne['ipaddr']}</i></div>",
				$tpl->_ENGINE_parse_body("<span style=\"font-size:16px;color:$color\">{$CONNECTIONS_TYPE[$ligne['nastype']]}</span>"),
				$disable,
				$delete
				)
		);
	}
	
	
	echo json_encode($data);	
	
}
function EnableLocalLDAPServer(){
	$sock=new sockets();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
	if($FreeRadiusEnableLocalLdap==1){$sock->GET_INFO("FreeRadiusEnableLocalLdap",0);}
	if($FreeRadiusEnableLocalLdap==0){$sock->GET_INFO("FreeRadiusEnableLocalLdap",1);}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");	
}

function connection_delete(){
	$q=new mysql();
	$ID=$_POST["connection-delete"];
	$sql="DELETE FROM freeradius_clients WHERE ipaddr='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");	
}
function connection_enable(){
	$ipaddr=$_POST["EnableDisable"];
	$q=new mysql();
	$ligne=mysql_fetch_array(
			$q->QUERY_SQL("SELECT enabled FROM freeradius_clients WHERE ipaddr='$ipaddr'","artica_backup")
	);
	if(!$q->ok){echo $q->mysql_error;return;}
	if($ligne["enabled"]==0){$enable=1;}else{$enable=0;}
	$q->QUERY_SQL("UPDATE freeradius_clients SET enabled=$enable WHERE ipaddr='$ipaddr'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");
}

