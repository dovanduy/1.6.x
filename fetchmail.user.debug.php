<?php
include_once('ressources/class.users.menus.inc');
include_once ("ressources/class.templates.inc");
include_once ("ressources/class.user.inc");
include_once ("ressources/class.fetchmail.inc");
session_start();


	$page=CurrentPageName();
	if($_GET["uid"]){$uid=$_GET["uid"];}else{$uid=$_SESSION["uid"];}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["del-ID"])){delete();exit;}
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){
		if($uid<>$_SESSION["uid"]){
			echo "alert('No privileges!\n');";
			return false;
		}
	}
	
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$fetchmail=new Fetchmail_settings();
	$ligne=$fetchmail->LoadRule($_GET["ruleid"]);
	$explain=$tpl->_ENGINE_parse_body("{debug}:{$_GET["uid"]}::{$ligne["user"]}@{$ligne["poll"]}");
	echo "YahooWinBrowse('700','$page?popup=yes&uid={$_GET["uid"]}&ruleid={$_GET["ruleid"]}','$explain')";
	
	
}
	
	
function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$uid=$_GET["uid"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=685;
	
	$fetchmail_execute_debug_warn=$tpl->javascript_parse_text("{fetchmail_execute_debug_warn}");
	$t=time();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$ask_delete_rule=$tpl->javascript_parse_text("{ask_delete_rule}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'Add', onpress : NewGItem$t},
	],	";
	
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t&uid=$uid&ID={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [	
		{display: 'PID', name : 'PID', width :60, sortable : true, align: 'right'},
		{display: '$date', name : 'zDate', width :136, sortable : true, align: 'left'},
		{display: '$subject', name : 'subject', width :390, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'action2', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: 'PID', name : 'PID'},
		{display: '$date', name : 'zDate'},
		{display: '$subject', name : 'subject'},
		
		
		

	],
	sortname: 'zDate',
	sortorder: 'desc',
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
		YahooWin6('750','wizard.fetchmail.newbee.php?debug-popup-zoom=yes&ID='+ID,'Zoom:'+ID);
	}

	var x_DeleteAttribute$t=function(obj){
		var tempvalue=obj.responseText;
	    if(tempvalue.length>3){alert(tempvalue);return;}
	    $('#rowDEB'+mem$t).remove();
	}

	function DeleteAttribute$t(ID){
			mem$t=ID;
	 		var XHR = new XHRConnection();
	      	XHR.appendData('del-ID',ID);
	      	XHR.sendAndLoad('$page', 'POST',x_DeleteAttribute$t);		
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
	$table="fetchmail_debug_execute";
	$database="artica_events";
	$page=1;
	$FORCE_FILTER=" AND `account_id`={$_GET["ID"]}";

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
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}	
	$fsize=14;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$color="black";
		$explain="{$ligne["subject"]}";
		$explain=str_replace("'", "`", $explain);
		$urljs="<a href=\"javascript:Blurz();\" OnClick=\"javascript:GItem$t('$ID','$explain');\"
		style=\"font-size:{$fsize}px;color:$color;text-decoration:underline\">";

		$delete=imgtootltip("delete-24.png","{delete}","DeleteAttribute$t('{$ligne["ID"]}')");

	
	$data['rows'][] = array(
		'id' => "DEB$ID",
		'cell' => array(
			"<span style='font-size:{$fsize}px;color:$color'>$urljs{$ligne["PID"]}</a></span>",
			"<span style='font-size:{$fsize}px;color:$color'>$urljs<strong>{$ligne["zDate"]}</strong>",
			"<span style='font-size:{$fsize}px;color:$color'>$urljs{$ligne["subject"]}</a></span>",
			$delete
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM fetchmail_debug_execute WHERE ID={$_POST["del-ID"]}","artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}

