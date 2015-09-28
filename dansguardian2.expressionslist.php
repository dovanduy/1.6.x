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
if(isset($_GET["AddWCatz-explain"])){AddWCatz_explain();exit;}
if(isset($_POST["AddWCatz-category"])){AddWCatzSave();exit;}
if(isset($_POST["DeletedWeighted"])){DeletedWeighted();exit;}
if(isset($_GET["edit-js"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_GET["edit-search"])){edit_search();exit;}
if(isset($_GET["expression-edit"])){expression_popup();exit;}
if(isset($_POST["expression-save"])){expression_save();exit;}
if(isset($_POST["expression-delete"])){expression_delete();exit;}
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
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$TB_WIDTH=580;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$group=$_GET["group"];
	if(isset($_GET["CatzByEnabled"])){$CatzByEnabled="&CatzByEnabled=yes";}
	$t=$_GET["modeblk"];
	$bannedregexpurllist_explain=$tpl->_ENGINE_parse_body("{bannedregexpurllist_explain}");
	
	$html="
	<div class=explain>$bannedregexpurllist_explain</div>
	<table class='bannedregexpurllist-table-$t' style='display: none' id='bannedregexpurllist-table-$t' style='width:99%'></table>
<script>
var selected_id=0;
$(document).ready(function(){
$('#bannedregexpurllist-table-$t').flexigrid({
	url: '$page?weighted-list=yes&RULEID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$type', name : 'type', width : 70, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 68, sortable : true, align: 'left'},
		{display: '$description', name : 'category2', width : 308, sortable : false, align: 'left'},
		{display: '$rows', name : 'tcount', width : 46, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_category', bclass: 'add', onpress : AddRulsWCatz},
		],	
	searchitems : [
		{display: '$category', name : 'category'},
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

function AddRulsWCatz(){
	YahooWin5(409,'$page?AddWCatz=yes&RULEID=$ID','$new_category');

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
	$q->CreateCategoryBannedRegexPurllistTable();
	
	$search='%';
	$table="webfilter_blks";
	$page=1;
	$ORDER="ORDER BY category ASC";
	$FORCE_FILTER=null;
	
	
	$sql="SELECT `category` FROM webfilter_blks WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=5) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=6)";
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=5) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=6) $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE (`webfilter_id`={$_GET["RULEID"]} AND modeblk=5) OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=6) $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 
	(`webfilter_id`={$_GET["RULEID"]} AND modeblk=5 $searchstring) 
	OR (`webfilter_id`={$_GET["RULEID"]} AND modeblk=6 $searchstring)  $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$dans=new dansguardian_rules();
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
		
		
		if($ligne["modeblk"]==5){$ligne["modeblk"]=$tpl->_ENGINE_parse_body("{blacklist}");$color="#A71212";}
		if($ligne["modeblk"]==6){$ligne["modeblk"]=$tpl->_ENGINE_parse_body("{whitelist}");}		
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?edit-js=yes&category={$ligne["category"]}');\"
		style='font-size:14px;color:$color;text-decoration:underline'>";
		
	
		$categoy=$ligne["category"];
		$text=$tpl->_ENGINE_parse_body($dans->array_blacksites[$ligne["category"]]);
		
		if(strpos($text, "}")>0){$text=$ligne["category"];}
		
		$ligne3=mysql_fetch_array($q->QUERY_SQL("SELECT count(zmd5) as tcount FROM regex_urls WHERE category='{$ligne["category"]}'"));

		$delete=imgsimple("delete-24.png","{delete}","DeletedWeighted('{$ligne["ID"]}')");
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<span style='font-size:14px;color:$color'>{$ligne["modeblk"]}</span>",
		"<span style='font-size:14px;color:$color'>$js{$ligne["category"]}</span>",
		"<span style='font-size:14px;color:$color'>$js$text</a></span>",
		 "<span style='font-size:14px;color:$color'>{$ligne3["tcount"]}</span>",$delete)
		);
	}
	
	
echo json_encode($data);	
}

function AddWCatz(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	
	$sql="SELECT categorykey FROM webfilters_categories_caches ORDER BY categorykey";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$a[$ligne["categorykey"]]=$ligne["categorykey"] ." 0 {rows}";
	}
	
	$sql="SELECT COUNT(*) AS tcount ,category FROM regex_urls GROUP BY category ORDER BY category";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$a[$ligne["category"]]=$ligne["category"] . " {$ligne["tcount"]} {rows}";
	}
	
	
	
	
	
	
	$a[null]="{select}";
	
	$b[5]="{blacklist}";
	$b[6]="{whitelist}";
	$t=time();
	$html="
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{category}:</td>
		<td>". Field_array_Hash($a, "AddWCatz-category",null,"AddWCatz_explain()",null,0,"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{type}:</td>
		<td>". Field_array_Hash($b, "AddWCatz-type",null,null,null,0,"font-size:14px")."</td>
	</tr>
	<tr>
		<td colspan=2><spand id='$t'></span></td>
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","AddWCatzSave()")."</td>
	</tr>
	
	</tbody>
	</table>
	<script>
		function AddWCatz_explain(){
			LoadAjax('$t','$page?AddWCatz-explain=yes&category='+escape(document.getElementById('AddWCatz-category').value));
		
		}
		
	var x_AddWCatzSave= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		if(document.getElementById('main_content_rule_edittabs')){RefreshTab('main_content_rule_edittabs');}
		YahooWin5Hide();
	}			
		
		function AddWCatzSave(){
			var XHR = new XHRConnection();
			XHR.appendData('AddWCatz-category',document.getElementById('AddWCatz-category').value);
			XHR.appendData('AddWCatz-type',document.getElementById('AddWCatz-type').value);
			XHR.appendData('RULEID','{$_GET["RULEID"]}');
			XHR.sendAndLoad('$page', 'POST',x_AddWCatzSave);	
		}
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function AddWCatz_explain(){
	$dans=new dansguardian_rules();
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body($dans->array_blacksites[$_GET["category"]]);
	echo "<div class=explain style='font-size:14px'>$text</div>";
	
	
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
	$category="{$_POST["AddWCatz-category"]}";
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

function edit_js(){
	$category=$_GET["category"];
	$page=CurrentPageName();
	$html="YahooWin6('650','$page?edit-popup=yes&category=$category','$category')";
	echo $html;
}


function edit_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	

	$html="
	
	<center>
	<table style='width:55%' class=form>
	<tbody>
		<tr><td class=legend>{pattern}:</td>
		<td>". Field_text("$t-search",null,"font-size:16px;width:220px",null,null,null,false,"Category{$t}SearchCheck(event)")."</td>
		<td width=1%>". button("{search}","Category{$t}Search()")."</td>
		</tr>
	</tbody>
	</table>
	
	
	<div id='dansguardian2-{$t}-list' style='width:100%;height:350px;overlow:auto'></div>
	
	<script>
		function Category{$t}SearchCheck(e){
			if(checkEnter(e)){Category{$t}Search();}
		}
		
		function Category{$t}Search(){
			var se=escape(document.getElementById('{$t}-search').value);
			LoadAjax('dansguardian2-{$t}-list','$page?edit-search='+se+'&category={$_GET["category"]}&t=$t');
		
		}
		
		function ExpressionsRegexListSearchBack(){Category{$t}Search();}
		
		Category{$t}Search();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}
function edit_search(){
	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);	
	
	
	$category=$_GET["category"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	
	
	$sql="SELECT zmd5,category,pattern,enabled FROM regex_urls WHERE category='$category' 
	AND pattern LIKE '$search' ORDER BY zDate DESC LIMIT 0,10";
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>Fatal Error: $q->mysql_error</H2>";}
	
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgtootltip("32-parameters.png","{apply}","ExpressionEdit('{$ligne["zmd5"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","ExpressionDelete('{$ligne["zmd5"]}')");
		$color="black;font-weight:bolder;";
		if(trim($ligne["pattern"]==null)){continue;}
		
		if($ligne["enabled"]==0){$color="#757575;";}
		$ligne["pattern"]=htmlentities($ligne["pattern"]);
		$ligne["pattern"]=wordwrap($ligne["pattern"],80,"<br>","<br>");
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:ExpressionEdit('{$ligne["zmd5"]}')\" 
		style='font-size:12px;color:$color;font-family:Courier New;text-decoration:underline'>";
		$html=$html."
		<tr class=$classtr id='{$ligne["zmd5"]}'>
			<td style='font-size:12px;color:$color;font-family:Courier New' width=99% colspan=2>$js{$ligne["pattern"]}</a></td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='left'>$delete</td>
		</tr>
		";
	}
	
	$TOTAL_ITEMS=numberFormat($TOTAL_ITEMS,0,""," ");	
	$add=imgtootltip("plus-24.png","{add} {regex}","ExpressionEdit('')");
	$header="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th width=99%>{items}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	

	
	$html=$header.$html."</table>
	</center>
	
	<script>
		var xzmd5='';
		function ExpressionEdit(zmd5){
			LoadWinORG('600','$page?expression-edit=yes&zmd5='+zmd5+'&category={$_GET["category"]}',zmd5);
		}
		
	var x_ExpressionDelete=function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#'+xzmd5).remove();
		
	}	
		function ExpressionDelete(zmd5){
		var XHR = new XHRConnection();
		XHR.appendData('expression-delete','yes');
		XHR.appendData('zmd5',zmd5);
		xzmd5=zmd5;
		XHR.sendAndLoad('$page', 'POST',x_ExpressionDelete);	
	}	
		
		function CheckStatsApplianceC(){
			LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes');
		}
		
		function WeightedPhraseEdit(lang,cat){
			Loadjs('$page?WeightedPhraseEdit-js=yes&language='+lang+'&category='+cat);
		
		}
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function expression_popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$md5=$_GET["zmd5"];
	$category=$_GET["category"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM regex_urls WHERE zmd5='$md5'"));
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{enable_rule}:</td>
		<td>". Field_checkbox("regexenabled", 1,$ligne["enabled"])."</td>
	</tr>
	<tr>
		<td colspan=2><textarea id='pattern' style='font-size:16px;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:180px;overflow:auto'>{$ligne["pattern"]}</textarea></td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveExpressionPattern()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
		
	var x_SaveExpressionPattern= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert('\"'+results+'\"');}
			WinORGHide();
			ExpressionsRegexListSearchBack();
		}		
		
		function SaveExpressionPattern(){
			var XHR = new XHRConnection();
			var pp=document.getElementById('pattern').value;
			pp = pp.replace(/\+/g,'%2B');
			var pp=encodeURIComponent(document.getElementById('pattern').value);
			XHR.appendData('expression-save','yes');
			XHR.appendData('zmd5','$md5');
			XHR.appendData('category','$category');
			XHR.appendData('pattern',pp);
			if(document.getElementById('regexenabled').checked){XHR.appendData('enabled','1');	}else{XHR.appendData('enabled','0');	}
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveExpressionPattern);			
		
		}
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
	
}
function expression_save(){
	
	$_POST["pattern"]=url_decode_special_tool($_POST["pattern"]);
	$sql="UPDATE regex_urls SET `pattern`='{$_POST["pattern"]}',`enabled`='{$_POST["enabled"]}' WHERE zmd5='{$_POST["zmd5"]}'";
	if($_POST["zmd5"]==null){
		$md5=md5($_POST["pattern"].$_POST["category"]);
		$sql="INSERT IGNORE INTO regex_urls (zmd5,zDate,category,pattern,enabled) VALUES 
		('$md5',NOW(),'{$_POST["category"]}','{$_POST["pattern"]}','{$_POST["enabled"]}')
		";
	}
	
	$q=new mysql_squid_builder();
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
$sock=new sockets();
$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}
function expression_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM regex_urls WHERE zmd5='{$_POST["zmd5"]}'");	
	if(!$q->ok){echo $q->mysql_error;return;}
$sock=new sockets();
$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}