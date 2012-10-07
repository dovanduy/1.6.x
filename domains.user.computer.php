<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
session_start();
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	

	if(!Isright()){$tpl=new templates();echo "<H2>".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."</H2>";die();}
	if(isset($_GET["add-computer"])){computer_add();exit;}
	if(isset($_GET["generate-list"])){computer_list();exit;}
	if(isset($_GET["list"])){computer_list();exit;}
	if(isset($_GET["AddComputer"])){AddComputer_form();exit;}
	if(isset($_POST["computer-name"])){AddComputer_save();exit;}
	if(isset($_POST["DeletedComputerLink"])){DeletedComputerLink();exit;}
page();


function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$users=new usersMenus();
	$mac=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$members=$tpl->_ENGINE_parse_body("{member}");
	$new_member=$tpl->_ENGINE_parse_body("{new_member}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$apply_parameters=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$TB_WIDTH=820;
	$t=time();
	
	if($_GET["userid"]==null){
		$filtersearch="{display: '$members', name : 'uid'},";
		$nemember=",{name: '$new_member', bclass: 'add', onpress : NewMember}";
	}
	
	if($users->SQUID_INSTALLED){
		$helpbt=",{name: '$online_help', bclass: 'Help', onpress : help$t}";
		$btrcompile=",{name: '$apply_parameters', bclass: 'Reconf', onpress : SquidRecompile$t}";
		
	}
	
$buttons="buttons : [
	{name: '$new_computer', bclass: 'add', onpress : LinkComputer}$nemember$btrcompile$helpbt
		],";	
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var selected_id=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&userid={$_GET["userid"]}',
	dataType: 'json',
	colModel : [
		{display: '', name : 'none0', width : 48, sortable : false, align: 'center'},
		{display: '$hostname', name : 'computerid', width : 281, sortable : true, align: 'left'},
		{display: '$members', name : 'uid', width : 233, sortable : true, align: 'left'},
		{display: '$mac', name : 'MacAddress', width : 139, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 40, sortable : false, align: 'center'},
		
	],
	$buttons
	searchitems : [
		$filtersearch
		{display: '$hostname', name : 'computerid'},
		{display: '$mac', name : 'MacAddress'},
		],
	sortname: 'computerid',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 400,
	singleSelect: true
	
	});   
});

function LinkComputer(){
	YahooWin5(630,'$page?AddComputer=yes&userid={$_GET["userid"]}','$new_computer');

}
function help$t(){
	s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=295','1024','900');
}

function SquidRecompile$t(){
	Loadjs('squid.debug.compile.php');
}

function NewMember(){Loadjs('create-user.php');}

function LinkComputerRefresh(){
	$('#table-$t').flexReload();

}


	var x_DeletedComputerLink= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		if(document.getElementById('row'+selected_id)){
			$('#row'+selected_id).remove();
		}else{
		 alert('#row'+selected_id+' no such id');
		}
		
	}			
		
		function DeletedComputerLink(zmd5){
			selected_id=zmd5;
			var XHR = new XHRConnection();
			XHR.appendData('DeletedComputerLink',zmd5);
			XHR.appendData('userid','{$_GET["userid"]}');
			XHR.sendAndLoad('$page', 'POST',x_DeletedComputerLink);	
		}




</script>";
	echo $html;	
	
	
}


function computer_list(){
	
	
	
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$userid=$_GET["userid"];
	
	$search='%';
	$table="hostsusers";
	$page=1;
	$FORCE_FILTER=1;
	
	if(trim($userid<>null)){$FORCE_FILTER="`uid`='$userid'";}
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$also=null;
		$f=substr($ligne["computerid"], strlen($ligne["computerid"])-1,1);
		$js=null;
		
		if($f=="$"){
			$comp=new computers($ligne["computerid"]);
			$ipaddr=$comp->ComputerIP;
			$ligne["computerid"]=substr($ligne["computerid"], 0,strlen($ligne["computerid"])-1)." ($ipaddr)";
			
			$js="<a href=\"javascript:blur();\" ". COMPUTER_JS($comp->uid)."
			style='font-size:16px;color:$color;text-decoration:underline'>";			
		}else{
			$comp=new computers();
			$uid=$comp->ComputerIDFromMAC($ligne["MacAddress"]);
			if($uid<>null){
				$comp=new computers($uid);
				$also=$tpl->_ENGINE_parse_body("{also_known_as}").": $comp->ComputerRealName - $comp->ComputerIP";
				$js="<a href=\"javascript:blur();\" ". COMPUTER_JS($comp->uid)."
				style='font-size:16px;color:$color;text-decoration:underline'>";				
			}
		}
	
		
		$delete=imgtootltip("delete-24.png","{delete}","DeletedComputerLink('{$ligne["zmd5"]}')");
		$filename=basename($ligne["filename"]);
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array(
		"<img src='img/30-computer.png'>",
		"<span style='font-size:16px;color:$color'>$js{$ligne["computerid"]}</a></span><div style='font-size:10px;font-style:italic'>$also</div>",
		"<span style='font-size:16px;color:$color'>{$ligne["uid"]}</span>",
		"<span style='font-size:16px;color:$color'>{$ligne["MacAddress"]}</a></span>",
		$delete)
		);
	}
	
	
echo json_encode($data);	
	
	
}



function AddComputer_form(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	if($_GET["userid"]==null){
		$truid="
		<tr>
			<td class=legend style='font-size:14px'>{member}:</td>
			<td>". Field_text("uid-$t",null,"width:250px;font-size:16px")."</td>
			<td width=1%>". button("{browse}", "Loadjs('MembersBrowse.php?field-user=uid-$t&prepend-guid=1&NOComputers=1&OnlyUsers=1')",14)."</td>
		</tr>		
		
		";
	}
	
	$html="<table style='width:99%' class=form>$truid
	<tr>
		<td class=legend style='font-size:14px'>{computer_name}:</td>
		<td>". Field_text("computer-name-$t",null,"width:250px;font-size:16px")."</td>
		<td width=1%>". button("{browse}", "Loadjs('computer-browse.php?callback=AddNewComputerSaveCallBack&mode=selection&show-title=yes')",14)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{ComputerMacAddress}:</td>
		<td>". Field_text("computer-mac-$t",null,"width:165px;font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td colspan=3 align='right'><hr>". button("{add}","AddNewComputerSave()",16)."</td>
	</tr>
<script>

	var x_AddNewComputerSave= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		LinkComputerRefresh();
		YahooWin5Hide();

	}			
		
		function AddNewComputerSave(){
			var XHR = new XHRConnection();
			XHR.appendData('computer-name',document.getElementById('computer-name-$t').value);
			XHR.appendData('computer-mac',document.getElementById('computer-mac-$t').value);
			if(document.getElementById('uid-$t')){
				XHR.appendData('userid',document.getElementById('uid-$t').value);
			}else{
				XHR.appendData('userid','{$_GET["userid"]}');
			}
			XHR.sendAndLoad('$page', 'POST',x_AddNewComputerSave);	
		}
		
		function AddNewComputerSaveCallBack(uid,mac){
			document.getElementById('computer-name-$t').value=uid;
			document.getElementById('computer-mac-$t').value=mac;	
			YahooLogWatcherHide();	
		}
		
</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function AddComputer_save(){
	$q=new mysql();
	$tpl=new templates();
	$mac=trim($_POST["computer-mac"]);
	$mac=strtolower($mac);
	$mac=str_replace("-", ":", $mac);
	$_POST["computer-name"]=trim($_POST["computer-name"]);
	$_POST["computer-name"]=strtolower($_POST["computer-name"]);
	$_POST["userid"]=trim($_POST["userid"]);
	$_POST["userid"]=strtolower($_POST["userid"]);
	
	if(trim($_POST["userid"]==null)){
		echo $tpl->javascript_parse_text("{error_no_member_set}");
		return;
	}
	
	
	
	if(!IsPhysicalAddress($mac)){
		echo $tpl->javascript_parse_text("{WARNING_MAC_ADDRESS_CORRUPT}");
		return;
	}
	
	$zmd5=md5($_POST["userid"].$mac);
	$sql="INSERT IGNORE INTO hostsusers (`zmd5`,`uid`,`computerid`,`MacAddress`) VALUES('$zmd5','{$_POST["userid"]}','{$_POST["computer-name"]}','$mac')";
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reconfigure-quotas=yes");
}

function DeletedComputerLink(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM hostsusers WHERE `zmd5`='{$_POST["DeletedComputerLink"]}'",'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reconfigure-quotas=yes");	
}

function IsRight(){
	$users=new usersMenus();
	if($users->AsArticaAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	if(isset($_POST["userid"])){$_GET["userid"]=$_POST["userid"];}
	if(!isset($_GET["userid"])){return false;}
	if($users->AsArticaAdministrator){return true;}
	if($users->AsSambaAdministrator){return true;}
	if($users->AllowAddUsers){return true;}
	return false;
	}

?>