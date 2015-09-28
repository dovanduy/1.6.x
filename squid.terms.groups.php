<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.squid.bandwith.inc');
	
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["group-add"])){term_group_add();exit;}
	if(isset($_GET["group-list"])){term_group_list();exit;}
	if(isset($_POST["group-del"])){term_group_del();exit;}
	
	if(isset($_GET["group-expressions-popup"])){term_group_expression_popup();exit;}
	if(isset($_GET["group-expressions-list"])){term_group_expression_list();exit;}
	
	if(isset($_GET["browse-expressions-popup"])){browse_expressions_popup();exit;}
	if(isset($_GET["browse-expressions-list"])){browse_expressions_list();exit;}
	
	if(isset($_GET["expression-popup"])){expression_popup();exit;}
	if(isset($_GET["expression-edit"])){expression_js();exit;}
	if(isset($_POST["term"])){expression_save();exit;}
	if(isset($_POST["expression-enable"])){expression_enable();exit;}
	if(isset($_POST["expression-del"])){expression_del();exit;}
	if(isset($_POST["expression-link"])){expression_link();exit;}
	if(isset($_POST["expression-unlink"])){expression_unlink();exit;}
	
	if(isset($_GET["AddWordsInGroup-js"])){AddWordsInGroup_js();exit;}
	

	
term_group();	

function AddWordsInGroup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$_GET["groupname"];
	if($title==null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_termsg WHERE ID={$_GET["ID"]}"));
		$title=$ligne["groupname"];
	}
	$html="RTMMail('600','$page?group-expressions-popup=yes&groupid={$_GET["ID"]}','$title');";
	echo $html;
}

function expression_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$expression=$tpl->_ENGINE_parse_body("{expression}");
	$title="$expression:: {$_GET["ID"]}";	
	echo "LoadWinORG('800','$page?expression-popup=yes&ID={$_GET["ID"]}&t={$_GET["t"]}','$title');";
}


function expression_popup(){
	$ID=$_GET["ID"];
	$button="{add}";
	if(!is_numeric($ID)){$ID=0;}
	if($ID>0){
		$button="{apply}";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_terms WHERE ID=$ID"));
	
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["xregex"])){$ligne["xregex"]=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<center id='center-$t'></center>
	<div class=explain style='font-size:14px' id='$t'>{addexpression_ufdbguard_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td><textarea id='pattern-$t' style='font-size:18px !important;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;
		border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:120px;overflow:auto;font-size:16px !important'
		OnKeyPress=\"javascript:CheckBrowseExprField(event)\"
		>{$ligne["term"]}</textarea>
		</td>
	</tr>
	<tr>
		<td>
			<table style='width:99%'>
			<tr>
				<td class=legend style='font-size:18px'>{isaregex_pattern}:</td>
				<td>". Field_checkbox_design("xregex-$t", 1,$ligne["xregex"])."</td>
				</tr> 		
			<tr>
				<td class=legend style='font-size:18px'>{enabled}:</td>
				<td>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"])."</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align='center'>". button("$button","SaveNewTermINDB()",26)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveNewTermINDB= function (obj) {
			var ID=$ID;
			var results=obj.responseText;
			if(results.length>3){alert('\"'+results+'\"');}
			if(ID==0){WinORGHide();}
			GenericReload$t();
			
		}

		function  CheckBrowseExprField(e){
			if(checkEnter(e)){SaveNewTermINDB();}
		}
		

	function GenericReload$t(){
		if(document.getElementById('flexRT_terms_expressions_main')){
			var t=document.getElementById('flexRT_terms_expressions_main').value;
			$('#flexRT'+t).flexReload();
		}
		if(document.getElementById('FlexRT_browse_expression_list')){
			var t=document.getElementById('FlexRT_browse_expression_list').value;
			$('#'+t).flexReload();
		}		
		
	
	
		if(document.getElementById('tableau-termgroupsW-regles')){FlexReloadRulesWTermGroups();}
		if(document.getElementById('tableau-termgroups-regles')){FlexReloadRulesTermGroups();}
		if(document.getElementById('tableau-termgroupsEXP-regles')){FlexReloadBrowseExpressions();}
		if(document.getElementById('TableExpressionsParReglesLiees')){RefreshTableExpressionsParReglesLiees();}
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
		}		
	
	
		function SaveNewTermINDB(){
			var ID=$ID;
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('pattern-$t').value);
			if(ID==0){AnimateDiv('$t');}else{AnimateDiv('center-$t');}
			XHR.appendData('term',pp);
			XHR.appendData('ID',$ID);
			if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
			if(document.getElementById('xregex-$t').checked){XHR.appendData('xregex',1);}else{XHR.appendData('xregex',0);}
			XHR.sendAndLoad('$page', 'POST',x_SaveNewTermINDB);				
		
		}
	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function expression_save(){
	$_POST["term"]=url_decode_special_tool($_POST["term"]);
	$_POST["term"]=trim(str_replace("\n", "", $_POST["term"]));
	$f=mysql_escape_string2($_POST["term"]);
	if($_POST["ID"]==0){
		$sql="INSERT INTO webfilter_terms (term,enabled,xregex) VALUES('$f',{$_POST["enabled"]},{$_POST["xregex"]});";
	}else{
		$sql="UPDATE webfilter_terms SET term='$f',enabled={$_POST["enabled"]},xregex={$_POST["xregex"]} WHERE ID='{$_POST["ID"]}';";
	}
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}

function expression_enable(){
	$sql="UPDATE webfilter_terms SET enabled='{$_POST["value"]}' WHERE ID='{$_POST["ID"]}';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}

function expression_del(){
	$sql="DELETE FROM webfilter_terms WHERE ID='{$_POST["ID"]}';";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");	
}
function expression_link(){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilter_termsassoc WHERE term_group={$_POST["groupid"]} AND termid={$_POST["ID"]}"));
	if($ligne["ID"]>0){return;}
	$sql="INSERT INTO webfilter_termsassoc (term_group,termid) VALUES ({$_POST["groupid"]},{$_POST["ID"]})";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}		
}

function expression_unlink(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_termsassoc WHERE ID={$_POST["expression-unlink"]}");
	if(!$q->ok){echo $q->mysql_error;}	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");			
}


function browse_expressions_popup(){
$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$groupid=$_GET["groupid"];
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$new_expression=$tpl->javascript_parse_text("{new_expression}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$squid_tgroups_expression_explain=$tpl->_ENGINE_parse_body("{squid_tgroups_expressionL_explain}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$expression=$tpl->javascript_parse_text("{expression}");
	$delete_group=$tpl->javascript_parse_text("{delete_group}");
	if(!is_numeric($t)){$t=time();}
	$t1=time();
	
	$buttons="
	buttons : [
	{name: '$new_expression', bclass: 'add', onpress : AddNewTermExpression},
	],";		
		

	
$html="
<input type='hidden' id='FlexRT_browse_expression_list' value='$t1$t'>
<table class='$t1$t' style='display: none' id='$t1$t' style='width:100%'></table>

	
<script>
var EXPRID=0;
$(document).ready(function(){
$('#$t1$t').flexigrid({
	url: '$page?browse-expressions-list=yes&groupid={$_GET["groupid"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$expression', name : 'term', width : 320, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 32, sortable : false, align: 'center'},	
		{display: '&nbsp;', name : 'enable', width : 32, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$expression', name : 'term'},
		],
	sortname: 'term',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


	function AddNewTermExpression(){
			LoadWinORG('800','$page?expression-popup=yes&t=$t1$t','$new_expression');
	}
	
	function GenericReload$t(){
		if(document.getElementById('flexRT_terms_expressions_main')){
			var t=document.getElementById('flexRT_terms_expressions_main').value;
			$('#flexRT'+t).flexReload();
		}
		
		if(document.getElementById('FlexRT_browse_expression_list')){
			var t=document.getElementById('FlexRT_browse_expression_list').value;
			$('#'+t).flexReload();
		}			
	
		if(document.getElementById('tableau-termgroupsW-regles')){FlexReloadRulesWTermGroups();}
		if(document.getElementById('tableau-termgroups-regles')){FlexReloadRulesTermGroups();}
		if(document.getElementById('TableExpressionsParReglesLiees')){RefreshTableExpressionsParReglesLiees();}
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
	}
	

	function FlexReloadBrowseExpressions(){
		if(document.getElementById('$t1$t')){ $('#$t1$t').flexReload();}
	}
	
	function DeleteBrowseExpression(ID){
			EXPRID=ID;
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
			XHR.appendData('expression-del','yes');
			XHR.sendAndLoad('$page', 'POST',x_DeleteBrowseExpression);	
	}
	
	function SwitchBrowseExpr(md,ID){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
			XHR.appendData('expression-enable','yes');
			if(document.getElementById(md).checked){XHR.appendData('value','1');}else{XHR.appendData('value','0');}
			XHR.sendAndLoad('$page', 'POST',x_CallBackSilent);	
	}
	
	function SelectBrowseExpr(ID){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
			XHR.appendData('expression-link','yes');
			XHR.appendData('groupid','$groupid');
			XHR.sendAndLoad('$page', 'POST',x_CallBackSilent);		
	}
	

	
	var x_CallBackSilent= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');}
		GenericReload$t();
		WinORGHide();
	}
	
	function x_DeleteBrowseExpression(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#rowterm'+EXPRID).remove();
	}		



</script>

";	
	echo $html;
}
	
	
function browse_expressions_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilter_terms";
	$page=1;
	
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){
		json_error_show("No data");
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(webfilter_terms.*) as TCOUNT FROM `$table`WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total =$q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT webfilter_terms.* FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql);
	
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne['term']=str_replace("'", "`", $ligne['term']);
		$tt=array();
		$md5=md5("browsExpr".$ligne["ID"]);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["term"]}","DeleteBrowseExpression('{$ligne['ID']}')");
		$select=imgtootltip("arrow-right-24.png","{select} {$ligne["term"]}","SelectBrowseExpr('{$ligne['ID']}')");	
		$enable=Field_checkbox($md5, 1,$ligne["enabled"],"SwitchBrowseExpr('$md5','{$ligne['ID']}')");	
	$data['rows'][] = array(
		'id' => "term".$ligne['ID'],
		'cell' => array("<span style='font-size:16px;font-weight:bold'>{$ligne["term"]}</span>",
		"$enable",$select,$delete )
		);
	}
	
	
echo json_encode($data);		

}


function term_group_expression_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$new_expression=$tpl->javascript_parse_text("{new_expression}");
	$link_expression=$tpl->javascript_parse_text("{link_expression}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$squid_tgroups_expression_explain=$tpl->_ENGINE_parse_body("{squid_tgroups_expression_explain}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$expression=$tpl->javascript_parse_text("{expression}");
	$delete_group=$tpl->javascript_parse_text("{delete_group}");
	$browse=$tpl->_ENGINE_parse_body("{browse}");
	
	
	$buttons="
	buttons : [
	{name: '$link_expression', bclass: 'add', onpress : AddTermExpression},
	],";		
		

	
$html="
<div id='tableau-termgroupsW-regles' class=explain style='font-size:14px'>$squid_tgroups_expression_explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var MEMID=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?group-expressions-list=yes&groupid={$_GET["groupid"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$expression', name : 'term', width : 499, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$expression', name : 'term'},
		],
	sortname: 'term',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function x_AddTermGroup(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		FlexReloadRulesTermGroups();
	}

	function x_UnlinkExpr(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		if(!document.getElementById('rowgroupExp'+MEMID)){alert('rowgroupExp'+MEMID+' no such id');}
		$('#rowgroupExp'+MEMID).remove();
		GenericReload$t();
	}		

	function AddTermExpression(){
		YahooWin6('500','$page?browse-expressions-popup=yes&groupid={$_GET["groupid"]}&t=$t','$browse::$expression');	
	}
	
	function UnlinkExpr(ID){
		MEMID=ID;
		var XHR = new XHRConnection();
		XHR.appendData('expression-unlink',ID);
		XHR.sendAndLoad('$page', 'POST',x_UnlinkExpr);
	}
	
	function GenericReload$t(){
		if(document.getElementById('flexRT_terms_expressions_main')){
			var t=document.getElementById('flexRT_terms_expressions_main').value;
			$('#flexRT'+t).flexReload();
		}
		
		if(document.getElementById('FlexRT_browse_expression_list')){
			var t=document.getElementById('FlexRT_browse_expression_list').value;
			$('#'+t).flexReload();
		}			
	
	
		$('#flexRT{$_GET["t"]}').flexReload();
		if(document.getElementById('tableau-termgroupsW-regles')){FlexReloadRulesWTermGroups();}
		if(document.getElementById('tableau-termgroups-regles')){FlexReloadRulesTermGroups();}
		if(document.getElementById('TableExpressionsParReglesLiees')){RefreshTableExpressionsParReglesLiees();}
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
		 
	}	
	
	
	function AddWordsInGroup$t(ID,name){
		YahooWin5('600','$page?group-expressions-popup=yes&t=$t&groupid='+ID);
	
	}
	

	function FlexReloadRulesWTermGroups(){
		$('#flexRT$t').flexReload();
	}
</script>

";	
	echo $html;
}

	
function term_group(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$squid_termsgroups_explain=$tpl->javascript_parse_text("{squid_termsgroups_explain}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$words=$tpl->javascript_parse_text("{expressions}");
	$delete_group=$tpl->javascript_parse_text("{delete_group}");
	$group=$tpl->_ENGINE_parse_body("{group}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$terms_groups=$tpl->javascript_parse_text("{terms_groups}");
	$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddTermGroup},
	{name: '$about2', bclass: 'Help', onpress : About$t},
	
	],";		
		

	
$html="
<input type='hidden' id='flexRT_terms_expressions_main' value='$t'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var MEMID=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?group-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$groupname', name : 'groupname', width : 202, sortable : true, align: 'left'},	
		{display: '$explain', name : 'ItemsNumber', width :724, sortable : false, align: 'left'},
		{display: '$words', name : 'WordCount', width : 60, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete2', width : 60, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$groupname', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$terms_groups</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function About$t(){
	alert('$squid_termsgroups_explain');
}

	function x_AddTermGroup(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		FlexReloadRulesTermGroups();
	}

	function x_DelTermGroup(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#rowgroup'+MEMID).remove();
	}		

	function AddTermGroup(){
		var groupname=prompt('$new_group','New group');
		if(groupname){
			var XHR = new XHRConnection();
			XHR.appendData('group-add',groupname);
			XHR.sendAndLoad('$page', 'POST',x_AddTermGroup);	
		}
	}
	
	function DelTermGroup(ID){
			MEMID=ID;
			if(confirm('$delete_group ?')){
				var XHR = new XHRConnection();
				XHR.appendData('group-del',ID);
				XHR.sendAndLoad('$page', 'POST',x_DelTermGroup);
			}	
		
	}

	function AddWordsInGroup$t(ID,name){
		YahooWin5('600','$page?group-expressions-popup=yes&groupid='+ID,'$group::'+name);
	
	}
	

	function FlexReloadRulesTermGroups(){
		$('#flexRT$t').flexReload();
	}
	
	function DeleteBandRule(ID){
		if(confirm('$delete_rule ?')){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
			XHR.appendData('rules-del','yes');
			XHR.sendAndLoad('$page', 'GET',x_DeleteBandRule);	
		}
	}

	
	function x_DeleteBandRule(obj){
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);}
				FlexReloadRulesBandwith();
	}		



</script>

";	
	echo $html;

}

function term_group_expression_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$textdisabled=$tpl->_ENGINE_parse_body("{disabled}");
	
	$search='%';
	$table="webfilter_termsassoc";
	$page=1;
	
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(webfilter_terms.*) as TCOUNT FROM `$table`,webfilter_terms WHERE webfilter_terms.ID=`$table`.termid
		AND `$table`.term_group={$_GET["groupid"]} $searchstring";

		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(webfilter_terms.*) as TCOUNT FROM `$table`,webfilter_terms WHERE webfilter_terms.ID=`$table`.termid
		AND `$table`.term_group={$_GET["groupid"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT webfilter_terms.term,$table.ID,webfilter_terms.enabled as termenabled,
	webfilter_terms.ID as termzID
	FROM `$table`,webfilter_terms WHERE webfilter_terms.ID=`$table`.termid
		AND `$table`.term_group={$_GET["groupid"]} $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne['groupname']=str_replace("'", "`", $ligne['groupname']);
		$tt=array();
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["term"]}","UnlinkExpr('{$ligne['ID']}')");
		$color="black";
		$textdisabledRow=null;
		if($ligne["termenabled"]==0){$color="#737373";$textdisabledRow="<i> ($textdisabled)</i>";}
						
	$data['rows'][] = array(
		'id' => "groupExp".$ligne['ID'],
		'cell' => array("<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?expression-edit=yes&ID={$ligne["termzID"]}');\" 
			style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>{$ligne["term"]}$textdisabledRow</span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		

}


function term_group_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$or=$tpl->_ENGINE_parse_body("{or}");
	
	$search='%';
	$table="webfilter_termsg";
	$page=1;
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne['groupname']=str_replace("'", "`", $ligne['groupname']);
		$tt=array();
		$c++;
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["groupname"]}","DelTermGroup('{$ligne['ID']}','{$ligne["groupname"]}')");
		$addwords=imgtootltip("plus-24.png","{add} {$ligne["groupname"]}","AddWordsInGroup{$_GET["t"]}('{$ligne['ID']}','{$ligne['groupname']}')");
		$maincolor="black";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT webfilter_terms.term FROM webfilter_termsassoc WHERE term_group={$ligne['ID']}"));
		
		$sql="SELECT webfilter_terms.term,enabled FROM webfilter_terms,webfilter_termsassoc WHERE webfilter_termsassoc.term_group={$ligne['ID']}  AND webfilter_termsassoc.termid=webfilter_terms.ID ORDER BY webfilter_terms.term";
		
		$results2 = $q->QUERY_SQL($sql);
		$petitspoints=null;
		if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);$ligne2["tcount"]=$q->mysql_error;}
		if(mysql_num_rows($results2)==0){$maincolor="#737373";}
		$wordscount=mysql_numrows($results2);
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if($ligne2["enabled"]==0){$ligne2["term"]="<i style='color:#737373'>{$ligne2["term"]}</i>";}
			$tt[]=$ligne2["term"];if(count($tt)>10){break;$petitspoints="...";}}
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:AddWordsInGroup{$_GET["t"]}('{$ligne['ID']}','{$ligne['groupname']}')\" 
		style='font-size:16px;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => "group".$ligne['ID'],
		'cell' => array("<span style='font-size:16px;font-weight:bold;color:$maincolor'>{$ligne["groupname"]}</span>"
		,"<span style='font-size:16px'>$js". @implode(" $or ", $tt)." $petitspoints</a></span>",
		"<span style='font-size:16px'>$wordscount</span>",$addwords,$delete )
		);
	}
	
	if($c==0){json_error_show("No data",1);}	
echo json_encode($data);		

}

function term_group_del(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_termsassoc WHERE term_group={$_POST['group-del']}");
	if(!$q->ok){echo $q->mysql_error ."\nDELETE FROM webfilter_termsassoc WHERE term_group={$_POST['group-del']}";return;}
	$q->QUERY_SQL("DELETE FROM webfilter_termsg WHERE ID={$_POST['group-del']}");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}

function term_group_add(){
	$_POST["group-add"]=addslashes($_POST["group-add"]);
	$sql="INSERT INTO webfilter_termsg (groupname,enabled) VALUE ('{$_POST["group-add"]}','1')";
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}
