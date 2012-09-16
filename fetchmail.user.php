<?php
include_once('ressources/class.users.menus.inc');
include_once ("ressources/class.templates.inc");
include_once ("ressources/class.user.inc");
include_once ("ressources/class.fetchmail.inc");
session_start();


	$page=CurrentPageName();
	if($_GET["uid"]){$uid=$_GET["uid"];}else{$uid=$_SESSION["uid"];}
	if(isset($_GET["items-rules"])){items();exit;}
	
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){
		if($uid<>$_SESSION["uid"]){
			echo "alert('No privileges!\n');";
			return false;
		}
	}
	if(isset($_POST["del-ID"])){delete();exit;}
	
popup();
	
function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$uid=$_GET["uid"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=630;
	$user_width=200;
	$poll_width=252;
	
	if($_GET["expanded"]=="usermin"){
		$TB_WIDTH=930;
		$TB_HEIGHT=500;
		$user_width=322;
		$poll_width=421;
	}
	
	$fetchmail_execute_debug_warn=$tpl->javascript_parse_text("{fetchmail_execute_debug_warn}");
	$t=time();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$imap_server_name=$tpl->_ENGINE_parse_body("{imap_server_name}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$ask_delete_rule=$tpl->javascript_parse_text("{ask_delete_rule}");
	$buttons="
	buttons : [
		{name: '$new_rule', bclass: 'Add', onpress : NewGItem$t},
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t&uid=$uid',
	dataType: 'json',
	colModel : [	
		{display: '$user', name : 'user', width :$user_width, sortable : true, align: 'left'},
		{display: '$imap_server_name', name : 'poll', width :$poll_width, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width :31, sortable : true, align: 'center'},
		{display: 'Debug', name : 'action1', width :31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'action2', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$user', name : 'user'},
		{display: '$imap_server_name', name : 'poll'},
		
		
		

	],
	sortname: 'poll',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');
}


	var x_NewGItem$t=function(obj){
		var tempvalue=obj.responseText;
	    if(tempvalue.length>3){alert(tempvalue);}
	    $('#flexRT$t').flexReload();
	}

	function NewGItem$t(){
		YahooWin4('650','wizard.fetchmail.newbee.php?page-modify=-1&uid=$uid&t=$t','$uid::$new_rule');
	}
	function GItem$t(ID,title){
		YahooWin4('550','wizard.fetchmail.newbee.php?page-modify='+ID+'&uid=$uid&t=$t','$uid::'+title);
		
	}

	var x_DeleteAttribute$t=function(obj){
		var tempvalue=obj.responseText;
	    if(tempvalue.length>3){alert(tempvalue);return;}
	    $('#row'+mem$t).remove();
	}

	function DeleteAttribute$t(ID){
		if(confirm('$ask_delete_rule')){
			mem$t=ID;
	 		var XHR = new XHRConnection();
	      	XHR.appendData('del-ID',ID);
	      	XHR.appendData('uid','$uid');
	      	XHR.sendAndLoad('$page', 'POST',x_DeleteAttribute$t);	
	      	}	
		}
	
	var x_ExecuteFetchAccount$t=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			 $('#flexRT$t').flexReload();
		}	
	
	
		function ExecuteFetchAccount$t(ID){
			var XHR = new XHRConnection();
			if(confirm('$fetchmail_execute_debug_warn')){
    			XHR.appendData('ExecuteFetchAccount',ID);
    			XHR.sendAndLoad('wizard.fetchmail.newbee.php', 'POST',x_ExecuteFetchAccount$t);
			}
		}
		
	
		
		function FetchAccountDebugs(ID){
			RTMMail('550','wizard.fetchmail.newbee.php?debug-popup=yes&ID='+ID,ID);
		
		}	

</script>";
	
	echo $html;
	

}	

function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$sock=new sockets();		
	
	$search='%';
	$table="fetchmail_rules";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND uid='{$_GET["uid"]}'";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$execute_in_debug=$tpl->_ENGINE_parse_body("{execute_in_debug}");
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$fsize=14;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$color="black";
		$showdebuglogs="&nbsp;";
		$delete=imgsimple("delete-24.png","","DeleteAttribute$t('$ID')");
		$explain="{$ligne["user"]} [{$ligne["poll"]}] -&raquo; {$ligne["proto"]}";
		$urljs="<a href=\"javascript:Blurz();\" OnClick=\"javascript:GItem$t('$ID','$explain');\"
		style=\"font-size:{$fsize}px;color:$color;text-decoration:underline\">";
		
		$ssl="";
		if($ligne["ssl"]==1){$ssl="&nbsp;(SSL)";}
		$execute="<a href=\"javascript:Blurz();\" OnClick=\"javascript:ExecuteFetchAccount$t($ID);\"
		style=\"font-size:11px;text-decoration:underline\">$execute_in_debug</a>";
		
		
		
		$sub="
		<table>
		<tr>
			<td width=1%><img src='img/arrow-right-16.png'></td>
			<td>$execute</td>
		</tr>
		</table>
		";
		
		$sql="SELECT COUNT(ID) as tcount FROM fetchmail_debug_execute WHERE account_id='$ID'";
		$ligneCOUNT=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
			if($ligneCOUNT["tcount"]>0){
				$showdebuglogs=imgsimple("script-24.png",null,"Loadjs('fetchmail.user.debug.php?uid=$uid&ruleid=$ID')");
			}		
		
			$enable=Field_checkbox("{$ID}_enabled",1,$ligne["enabled"],
			"Loadjs('wizard.fetchmail.newbee.php?enable-js-rule=$ID&uid={$_GET["uid"]}&t={$_GET["t"]}')");
	
	$data['rows'][] = array(
		'id' => "$ID",
		'cell' => array(
			"<span style='font-size:{$fsize}px;color:$color'>$urljs{$ligne["user"]}</a></span>",
			"<span style='font-size:{$fsize}px;color:$color'>$urljs<strong>{$ligne["poll"]}</strong>$ssl</a></span><div style='margin-left:-5px'>$execute</div>",
			"<span style='font-size:{$fsize}px;color:$color'>$enable</a></span>",
			"<span style='font-size:{$fsize}px;color:$color'>$showdebuglogs</a></span>",
			"<span style='font-size:{$fsize}px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function delete(){
	$num=$_POST["del-ID"];
	$uid=$_POST["uid"];
	$fetchmail=new Fetchmail_settings();
	$fetchmail->DeleteRule($num,$uid);
	}