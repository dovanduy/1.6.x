<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["members-search"])){members_search();exit;}
	if(isset($_POST["kill-userid"])){members_delete();exit;}
	if(isset($_POST["enable-userid"])){members_enable();exit;}
	
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$publicip=$tpl->_ENGINE_parse_body("{public_ip}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	
	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");
	
$html="
$explain
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
MEM_ISP_MD='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: 'ID', name : 'userid', width : 31, sortable : false, align: 'center'},	
		{display: '$publicip', name : 'publicip', width :100, sortable : true, align: 'left'},
		{display: '$email', name : 'email', width :496, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$publicip', name : 'publicip'},
		{display: '$email', name : 'email'}
		],
	sortname: 'userid',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 765,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

    
	var X_DeleteISPMEmber=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		if(MEM_ISP_MD.length>0){ $('#row'+MEM_ISP_MD).remove();}

		
    }  

	function DeleteISPMEmber(userid,md,email){
		MEM_ISP_MD=md;
		if(confirm('$delete_this_member_ask:'+email)){
			var XHR = new XHRConnection();
	    	XHR.appendData('kill-userid',userid);
			XHR.sendAndLoad('$page', 'POST',X_DeleteISPMEmber);    		
		
		}
	}
	
	function ISPMemberEnable(userid,md){
			MEM_ISP_MD='';
			var XHR = new XHRConnection();
	    	XHR.appendData('enable-userid',userid);
	    	if(!document.getElementById(md)){alert('Fatal:Error\\n'+md);return;}
			if(document.getElementById(md).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
			XHR.sendAndLoad('$page', 'POST',X_DeleteISPMEmber); 
		}


</script>

";
	
	echo $html;
}

function members_delete(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();	
	
	$sql="DELETE FROM usersisp WHERE userid='{$_POST["kill-userid"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function members_enable(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();	
	$sql="UPDATE usersisp SET enabled={$_POST["value"]} WHERE userid='{$_POST["enable-userid"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function members_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="usersisp";
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Empty table");}
	
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}
		
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		
		$delete="<a href=\"javascript:blur()\" OnClick=\"javascript:DeleteISPMEmber('{$ligne["userid"]}','$id','{$ligne["email"]}');\"><img src='img/delete-24.png'></a>";   
		
		
		
		$enable=Field_checkbox("check-$id",1,$ligne["enabled"],"ISPMemberEnable('{$ligne["userid"]}','check-$id')");	
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<span style='font-size:14px'>{$ligne["userid"]}</span>"
		,"<span style='font-size:14px'>{$ligne["publicip"]}</span>",
		"<span style='font-size:14px'>{$ligne["email"]}</span>",$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}
