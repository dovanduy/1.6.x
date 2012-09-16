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

if(isset($_GET["weighted-list"])){weighted_list();exit;}
if(isset($_GET["AddWCatz"])){AddWCatz();exit;}
if(isset($_GET["AddWCatz-ruleid"])){AddWCatz_rules();exit;}
if(isset($_POST["AddWCatz-language"])){AddWCatzSave();exit;}
if(isset($_POST["DeletedWeighted"])){DeletedWeighted();exit;}
page();


function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$category=$tpl->_ENGINE_parse_body("{extension}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$type=$tpl->_ENGINE_parse_body("{type}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$language=$tpl->_ENGINE_parse_body("{language}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$TB_WIDTH=580;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$group=$_GET["group"];
	if(isset($_GET["CatzByEnabled"])){$CatzByEnabled="&CatzByEnabled=yes";}
	$t=$_GET["modeblk"];
	
	
	$html="
	<table class='weighted-table-$t' style='display: none' id='blacklist-table-$t' style='width:99%'></table>
<script>
var selected_id=0;
$(document).ready(function(){
$('#blacklist-table-$t').flexigrid({
	url: '$page?weighted-list=yes&RULEID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$type', name : 'category', width : 70, sortable : false, align: 'left'},
		{display: '$language', name : 'category', width : 76, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 358, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_category', bclass: 'add', onpress : AddWCatz},
		],	
	searchitems : [
		{display: '$category', name : 'category'},
		{display: '$group', name : 'master_category'},
		],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 250,
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


function weighted_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilter_blks";
	$page=1;
	$ORDER="ORDER BY category ASC";
	$FORCE_FILTER=null;
	
	
	$sql="SELECT `category` FROM webfilter_blks WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=2) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=3)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$cats[$ligne["category"]]=true;
		}
		
	
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=2) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=3) $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=2) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=3) $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 
	(`webfilter_id`={$_GET["RULEID"]} AND modeblk=2 $searchstring) 
	OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=3 $searchstring)  $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		if(preg_match("#(.+?)-(.+?)$#", $ligne['category'],$re)){
			$language=$re[1];
			$categoy=$re[2];
		}
		
		
		if($ligne["modeblk"]==2){$ligne["modeblk"]=$tpl->_ENGINE_parse_body("{blacklist}");$color="#A71212";}
		if($ligne["modeblk"]==3){$ligne["modeblk"]=$tpl->_ENGINE_parse_body("{whitelist}");}		
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('dansguardian2.wieghteddb.php?WeightedPhraseEdit-js=yes&language=$language&category=$categoy');\"
		style='font-size:14px;color:$color;text-decoration:underline'>";
		
	
		
		
		$categoy=$tpl->_ENGINE_parse_body("{{$categoy}}");

		$delete=imgtootltip("delete-24.png","{delete}","DeletedWeighted('{$ligne["ID"]}')");
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<span style='font-size:14px;color:$color'>{$ligne["modeblk"]}</span>",
		"<span style='font-size:14px;color:$color'>$js$language</a></span>",
		 "<span style='font-size:14px;color:$color'>$js$categoy</a></span>",$delete)
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
