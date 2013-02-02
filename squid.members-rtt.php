<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["items"])){items();exit;}
	
	
popup();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$st=new status();
	$count=$st->squid_get_current_users_count();
	$title=$tpl->_ENGINE_parse_body("$count&raquo;{member}");
	$html="YahooWin5('713','$page?popup=yes','$title')";
	echo $html;
	
}




function popup(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
			url: '$page?items=yes',
			dataType: 'json',
			colModel : [
			{display: '$member', name : 'member', width :408, sortable : true, align: 'left'},
			{display: '$hits', name : 'thits', width :114, sortable : true, align: 'right'},
			{display: '$size', name : 'tsize', width :114, sortable : true, align: 'right'},
			
		
			],
		
			searchitems : [
			{display: '$member', name : 'member'},
		
		
			],
			sortname: 'tsize',
			sortorder: 'desc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: 695,
			height: 520,
			singleSelect: true
		});
});

</script>";
echo $html;

}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="UserSizeRTT";
	$page=1;
	$field_default=null;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("No item:".__LINE__,0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$sql="SELECT COUNT(`uid`) as tcount FROM $table WHERE LENGTH(uid)>0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["tcount"]>0){$field_default="uid";}
	
	if($field_default==null){
		$field_default="ipaddr";
	}
	
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
	
		$sql="SELECT hits as thits,size as tsize,`$field_default`
		FROM  $table WHERE $searchstring AND LENGTH(`$field_default`)>0";
	
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($results);
	
	}else{
		$sql="SELECT hits as thits,size as tsize,`$field_default`
		FROM  $table WHERE LENGTH(`$field_default`)>0";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($results);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if($searchstring<>null){$searchstring=" AND $searchstring";}
	$sql="SELECT hits as thits,size as tsize,`$field_default`
	FROM  $table WHERE 1 $searchstring AND LENGTH(`$field_default`)>0 $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,"artica_events");
	
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	if(mysql_num_rows($results)==0){json_error_show("No item",0);}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
			$data['rows'][] = array(
					'id' => md5($ligne[$field_default]),
					
					'cell' => array(
					"<span style='font-size:16px'>{$ligne[$field_default]}</span>",
					"<span style='font-size:16px'>{$ligne["thits"]}</span>",
					"<span style='font-size:16px'>{$ligne["tsize"]}</span>",
					)
			);
		}
	
	
		echo json_encode($data);
	
	}
