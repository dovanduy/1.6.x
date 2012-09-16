<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["list"])){cachelist();exit;}
if(isset($_GET["AddWCatz"])){AddWCatz();exit;}
if(isset($_GET["AddWCatz-ruleid"])){AddWCatz_rules();exit;}
if(isset($_POST["AddWCatz-language"])){AddWCatzSave();exit;}
if(isset($_POST["DeletedWeighted"])){DeletedWeighted();exit;}
page();


function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$filesize=$tpl->_ENGINE_parse_body("{filesize}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$TB_WIDTH=866;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$sql="SELECT SUM(filesize) as tsize FROM youtubecache";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$storage=$tpl->_ENGINE_parse_body("<div style='font-size:16px;text-align:right;font-weight:bold;margin-bottom:8px'>{storage}:". FormatBytes($ligne["tsize"]/1024)."</div>");
	
$buttons="buttons : [
	{name: '$new_category', bclass: 'add', onpress : AddWCatz},
		],";	
	
	$html="
	$storage
	<table class='table-$t' style='display: none' id='blacklist-table-$t' style='width:99%'></table>
<script>
var selected_id=0;
$(document).ready(function(){
$('#blacklist-table-$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 155, sortable : true, align: 'left'},
		{display: '$filename', name : 'filename', width : 208, sortable : true, align: 'left'},
		{display: '$filesize', name : 'filesize', width : 76, sortable : true, align: 'left'},
		{display: '$hostname', name : 'proxyname', width : 268, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 40, sortable : false, align: 'center'},
		
	],
	
	searchitems : [
		{display: '$filename', name : 'category'},
		{display: '$group', name : 'master_category'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
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

function AddWCatz(){
	YahooWin5(650,'$page?AddWCatz=yes&RULEID=$ID','$new_category');

}

	var x_DeletedWeighted= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		if(document.getElementById('row'+selected_id)){
			$('#row'+selected_id).remove();
		}else{
		 alert('#row'+selected_id+' no such id');
		}
	}			
		
		function DeletedWeighted(ID){
			selected_id=ID;
			var XHR = new XHRConnection();
			XHR.appendData('DeletedWeighted',ID);
			XHR.appendData('RULEID','{$_GET["RULEID"]}');
			XHR.sendAndLoad('$page', 'POST',x_DeletedWeighted);	
		}




</script>";
	echo $html;	
	
	
}


function cachelist(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="youtubecache";
	$page=1;
	$FORCE_FILTER=null;
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` ";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		
		$filesize=FormatBytes($ligne["filesize"]/1024);
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUp('{$ligne["urlsrc"]}',600,600,true)\"
		style='font-size:14px;color:$color;text-decoration:underline'>";
		
		if(trim($ligne["urlsrc"])==null){$js="<span style='font-size:14px;color:$color'>";}
		
		$delete=imgtootltip("delete-24.png","{delete}","DeletedWeighted('{$ligne["ID"]}')");
		$filename=basename($ligne["filename"]);
	$data['rows'][] = array(
		'id' => $ligne['zMD5'],
		'cell' => array(
		"<span style='font-size:14px;color:$color'>{$ligne["zDate"]}</span>",
		"<span style='font-size:14px;color:$color'>$js$filename</a></span>",
		 "<span style='font-size:14px;color:$color'>$filesize</span>",
		"<span style='font-size:14px;color:$color'>{$ligne["proxyname"]}</span>",
	
	
	$delete)
		);
	}
	
	
echo json_encode($data);	
	
	
}

function AddWCatz(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$sql="SELECT language FROM phraselists_weigthed GROUP BY language ORDER BY language";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$a[$ligne["language"]]=$ligne["language"];
	}
	$a[null]="{select}";
	
	$b[2]="{blacklist}";
	$b[3]="{whitelist}";
	
	$html="<div class=explain>{dansguardian_weighted_explain}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{language}:</td>
		<td>". Field_array_Hash($a, "AddWCatz-language",null,"AddWCatz_rules()",null,0,"font-size:14px")."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:14px'>{rule}:</td>
	<td><span id='AddWCatz-ruleid'></span></td>
	</tr>
	<tr>
	<td class=legend style='font-size:14px'>{type}:</td>
	<td>". Field_array_Hash($b, "AddWCatz-type",null,null,null,0,"font-size:14px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","AddWCatzSave()")."</td>
	</tr>
	
	</tbody>
	</table>
	<script>
		function AddWCatz_rules(){
			var lang=document.getElementById('AddWCatz-language').value;
			LoadAjax('AddWCatz-ruleid','$page?AddWCatz-ruleid=yes&lang='+lang);
		
		}
		
	var x_AddWCatzSave= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
	}			
		
		function AddWCatzSave(){
			if(!document.getElementById('AddWCatz-category')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('AddWCatz-language',document.getElementById('AddWCatz-language').value);
			XHR.appendData('AddWCatz-category',document.getElementById('AddWCatz-category').value);
			XHR.appendData('AddWCatz-type',document.getElementById('AddWCatz-type').value);
			XHR.appendData('RULEID','{$_GET["RULEID"]}');
			XHR.sendAndLoad('$page', 'POST',x_AddWCatzSave);	
		}
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function AddWCatz_rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$sql="SELECT category FROM phraselists_weigthed GROUP BY category ORDER BY category";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {$a[$ligne["category"]]="{{$ligne["category"]}}";}
		
	echo $tpl->_ENGINE_parse_body(Field_array_Hash($a, "AddWCatz-category",null,null,null,0,"font-size:14px"));
	
}

function AddWCatzSave(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$category="{$_POST["AddWCatz-language"]}-{$_POST["AddWCatz-category"]}";
	$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,	modeblk,category) 
	VALUES('{$_POST["RULEID"]}','{$_POST["AddWCatz-type"]}','$category')");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");		
}

function DeletedWeighted(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE ID='{$_POST["DeletedWeighted"]}'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}
