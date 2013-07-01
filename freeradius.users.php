<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.freeradius.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die();}
	// freeradius_db
	
	
	if(isset($_GET["member-id-js"])){member_id_js();exit;}
	if(isset($_GET["username-form-id"])){member_id();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["username"])){member_save();exit;}
	if(isset($_GET["query"])){connection_list();exit;}
	if(isset($_POST["EnableLocalLDAPServer"])){EnableLocalLDAPServer();exit;}
	if(isset($_POST["member-delete"])){member_delete();exit;}
	if(isset($_POST["EnableDisable"])){connection_enable();exit;}
	page();
function member_id_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$id=urlencode($_GET["member-id-js"]);
	$title=$tpl->javascript_parse_text("{new_profile}");
	$t=$_GET["t"];
	
	if($id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array(
				$q->QUERY_SQL("SELECT username FROM radcheck WHERE id='$id'","artica_backup"));
				$title=utf8_decode($tpl->javascript_parse_text($ligne["username"]));
	}
	
	echo "YahooWin2('650','$page?username-form-id=$id&t=$t','$title')";
	
}


function member_id(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$q=new mysql();
	$id=$_GET["username-form-id"];
	if($id>0){
		$btname="{apply}";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM radcheck WHERE id='$id'","artica_backup"));
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT gpid FROM radusergroup WHERE username='{$ligne["username"]}'","artica_backup"));
		$gpid=$ligne2["gpid"];
		if(!is_numeric($gpid)){$gpid=0;}		
	}
	
	$GROUPS[0]="{select}";
	$sql="SELECT id,groupname FROM radgroupcheck ORDER BY groupname";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($pg = mysql_fetch_assoc($results)) {
		$GROUPS[$pg["id"]]=$pg["groupname"];
	}	
	
	$groups=Field_array_Hash($GROUPS, "gpid-$t",$gpid,
			"blur()",null,0,"font-size:16px");
	
	$html="<div id='anim-$t'></div>
	<div style='width:95%' class=form>	
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>". Field_text("username-$t",$ligne["username"],"font-size:16px;width:220px")."</td>		
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("value-$t",$ligne["value"],"font-size:16px;width:220px")."</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{group}:</td>
		<td>$groups</td>		
	</tr>					
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",18)."</td>
	</tr>	
	</table>	
	</div>
			
	
	<script>
	var x_Save$t= function (obj) {
	var connection_id='$id';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	document.getElementById('$t').innerHTML='';
	if(connection_id.length==0){YahooWin2Hide();}
	$('#$t').flexReload();
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('username', encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('gpid', document.getElementById('gpid-$t').value);
	XHR.appendData('userid', '$id');
	XHR.appendData('value', encodeURIComponent(document.getElementById('value-$t').value));
	AnimateDiv('anim-$t');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}	
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



function member_save(){
	$q=new mysql();
	$_POST["username"]=url_decode_special_tool($_POST["username"]);
	$_POST["value"]=url_decode_special_tool($_POST["value"]);
	$free=new freeradius();
	$free->MemberSave($_POST["username"],$_POST["value"],$_POST["userid"],$_POST["gpid"]);
	

	
}
	
function page(){
	
		$page=CurrentPageName();
		$tpl=new templates();
		$q=new mysql();
		$sock=new sockets();
		$shortname=$tpl->javascript_parse_text("{member}");
		$nastype=$tpl->javascript_parse_text("{type}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$connection=$tpl->javascript_parse_text("{connection}");
		$add=$tpl->javascript_parse_text("{new_member}");
		$groups=$tpl->javascript_parse_text("{groups2}");
		$freeradius_users_explain=$tpl->_ENGINE_parse_body("{freeradius_users_explain}");
		$tablewidht=883;
		$t=time();
	
		$buttons="buttons : [
		{name: '$add', bclass: 'Add', onpress : AddConnection$t},
		{name: '$groups', bclass: 'Group', onpress : Groups$t},
		],	";
	

	
echo "
<div class=explain style='font-size:14px'>$freeradius_users_explain</div>
<table class='$t' style='display: none' id='$t' style='width:99%;text-align:left'></table>
<script>
	var MEMM$t='';
	$(document).ready(function(){
		$('#$t').flexigrid({
			url: '$page?query=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'center'},
			{display: '$shortname', name : 'username', width : 740, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none3', width : 40, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$shortname', name : 'shortname'},
		
		
		],
		sortname: 'username',
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
	
	function Groups$t(){
		Loadjs('freeradius.groups.php');
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
		Loadjs('$page?member-id-js=&t=$t');
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
	
	function ConnectionDelete$t(id){
		MEMM$t=id;
		if(confirm('$delete '+id+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('member-delete',id);
			XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
		}
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
	$table="radcheck";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=null;
		
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
		
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$delete=imgsimple("delete-24.png",null,"ConnectionDelete$t('{$ligne['id']}')");
		
		

		$data['rows'][] = array(
				'id' => $ligne['id'],
				'cell' => array("
						<img src='img/user-32.png'>",
						"<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('$MyPage?member-id-js={$ligne['id']}&t=$t');\" 
						style=\"font-size:16px;text-decoration:underline;color:$color\">
						{$ligne['username']}</a>",
						$delete
				)
		);
	}
	
	
	echo json_encode($data);	
	
}


function member_delete(){
	$q=new mysql();
	$ID=$_POST["member-delete"];
	$free=new freeradius();
	$free->MemberDelete($ID);
}