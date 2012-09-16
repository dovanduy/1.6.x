<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["add-ip"])){add_ip();exit;}
	if(isset($_GET["ip-list"])){ip_list();exit;}
	if(isset($_GET["del-ip"])){ip_del();exit;}
page();


//EnableAmavisInMasterCF
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$check_client_access_ip_explain=$tpl->javascript_parse_text("{check_client_access_ip_explain}");
	$ip_addr=$tpl->_ENGINE_parse_body("{ip_addr}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$sock=new sockets();
	$EnableAmavisInMasterCF=$sock->GET_INFO("EnableAmavisInMasterCF");
	if(!is_numeric($EnableAmavisInMasterCF)){$EnableAmavisInMasterCF=0;}	
	
	
	$buttons="buttons : [
		{name: '$add', bclass: 'add', onpress : AddAmavisBypass},
		{separator: true},
		
		],	";
	
	if($EnableAmavisInMasterCF<>1){$buttons=null;}
	
	
	$html="<center id='amavisbypassList'></center>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<div class=explain style='font-size:14px'>{amavis_bypass_servers_explain}</div>
	<script>
	
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?ip-list=yes',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width :31, sortable : true, align: 'center'},
		{display: '$ip_addr', name : '$ip_addr', width : 590, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 31, sortable : false, align: 'left'},
	],
$buttons
	searchitems : [
		{display: '$ip_addr', name : 'ip_addr'},
		
		
		],
	sortname: 'ip_addr',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 701,
	height: 250,
	singleSelect: true
	
	});   
});

function add_task$t(){
	Loadjs('wizard.backup-all.php');
}	
	
	
	function RefreshAmavisdByPass(){
		$('#table-$t').flexReload();
	
	}
	
	
	var x_AddAmavisBypass= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
		  document.getElementById('amavisbypassList').innerHTML='';
	   	  RefreshAmavisdByPass();  
		}		
	
		function AddAmavisBypass(){
			var ip=prompt('$check_client_access_ip_explain');
			if(ip){
				var XHR = new XHRConnection();
				XHR.appendData('add-ip',ip);
				AnimateDiv('amavisbypassList');
				XHR.sendAndLoad('$page', 'GET',x_AddAmavisBypass);	
			}
		}
		
		function DeleteAmavisBypass(ip){
			var XHR = new XHRConnection();
			XHR.appendData('del-ip',ip);
			document.getElementById('amavisbypassList').innerHTML='<img src=\"img/wait_verybig.gif\">';
			XHR.sendAndLoad('$page', 'GET',x_AddAmavisBypass);	
		}

		RefreshAmavisdByPass();	
	
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function add_ip(){
	$sql="INSERT INTO amavisd_bypass (`ip_addr`) VALUES('{$_GET["add-ip"]}')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-smtp-sender-restrictions=master");
	$sock->getFrameWork("cmd.php?amavis-restart=yes");
}
function ip_del(){
	$sql="DELETE FROM amavisd_bypass WHERE `ip_addr`='{$_GET["del-ip"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-smtp-sender-restrictions=master");
	$sock->getFrameWork("cmd.php?amavis-restart=yes");
}

function ip_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		
	
	$search='%';
	$table="amavisd_bypass";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	$EnableAmavisInMasterCF=$sock->GET_INFO("EnableAmavisInMasterCF");
	if(!is_numeric($EnableAmavisInMasterCF)){$EnableAmavisInMasterCF=0;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
			$md=md5($ligne["ip_addr"]);
			$delete=imgsimple("delete-24.png","{delete}","DeleteAmavisBypass('{$ligne["ip_addr"]}')");
			$color="black";
			if($EnableAmavisInMasterCF<>1){$color="#CCCCCC";}
			

		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<img src='img/folder-network-24.png'>",
			"<span style='font-size:16px;color:$color'>{$ligne["ip_addr"]}</a></span>",
			"<span style='font-size:12px;'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
	
}


function ip_list_old(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableAmavisInMasterCF=$sock->GET_INFO("EnableAmavisInMasterCF");

	
	$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>{ip_addr}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	$sql="SELECT * FROM amavisd_bypass ORDER BY ip_addr";
	

	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		if(preg_match("#doesn't exist#",$q->mysql_error)){$q->BuildTables();$results=$q->QUERY_SQL($sql,"artica_backup");}
		if(!$q->ok){
			echo $q->mysql_error;return;
		}
	}	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
			$delete=imgtootltip("delete-32.png","{delete}","DeleteAmavisBypass('{$ligne["ip_addr"]}')");
			$color="black";
			if($EnableAmavisInMasterCF<>1){$color="#CCCCCC";}
			
			
		$html=$html . "
		<tr  class=$classtr>
		<td width=99% style='font-size:16px;color:$color' nowrap>{$ligne["ip_addr"]}</td>
		<td width=1%>$delete</td>
		</tr>";

	}
	
	$html=$html."</table>";
	echo $tpl->_ENGINE_parse_body($html);
		
	
	
}
