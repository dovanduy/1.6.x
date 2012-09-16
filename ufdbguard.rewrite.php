<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["now-search"])){popup_list();exit;}
if(isset($_GET["rewrite-rule"])){rewrite_rule_js();exit;}
if(isset($_GET["rewrite-rule-tab"])){rewrite_rule_tab();exit;}
if(isset($_GET["rewrite-rule-settings"])){rewrite_rule_settings();exit;}
if(isset($_GET["rewrite-rule-items"])){rewrite_rule_items();exit;}
if(isset($_GET["rewrite-rule-items-list"])){rewrite_rule_items_list();exit;}
if(isset($_GET["rewrite-rule-item-js"])){rewrite_rule_items_js();exit;}
if(isset($_GET["rewrite-rule-item-popup"])){rewrite_rule_items_popup();exit;}
if(isset($_POST["rewrite-rule-item-delete"])){rewrite_rule_items_delete();exit;}
if(isset($_POST["rewrite-rule-item-enable"])){rewrite_rule_items_enable();exit;}
if(isset($_POST["rewrite-rule-delete"])){rewrite_rule_delete();exit;}
if(isset($_POST["rewrite-rule-enable"])){rewrite_rule_enable();exit;}



if(isset($_POST["frompattern"])){rewrite_rule_items_save();exit;}
if(isset($_POST["rulename"])){rewrite_rule_settings_save();exit;}

popup();

function rewrite_rule_items_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$ID=$_GET["ID"];
	$ligne=array();
	$button="{add}";
	$t=time();
	if($ID>0){
		$sql="SELECT * FROM webfilters_rewriteitems WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$button="{apply}";	
	}	
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$html="
	<div id='$t'>
	<center>
	<table style='width:70%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{searchthestring}:</td>
		<td>". Field_text("frompattern-$t",$ligne["frompattern"],"font-size:14px;width:99%",null,null,null,false,"SaveMainRewriteFilterCheck$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{replacewith}:</td>
		<td>". Field_text("topattern-$t",$ligne["topattern"],"font-size:14px;width:99%",null,null,null,false,"SaveMainRewriteFilterCheck$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"SaveMainRewriteFilter$t()",16)."</td>
	</tr>
	</tbody>
	</table>
	</center>
	</div>
	<script>
		var x_SaveMainRewriteFilter2= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			var ID=$ID;
			if(document.getElementById('tableau-reecriture-regles')){FlexReloadRulesRewrite();}
			if(document.getElementById('tableau-reecriture-regles2')){FlexReloadRulesRewriteItems();}
			YahooWin3Hide();
			
		}	

		function SaveMainRewriteFilterCheck$t(e){
			if(checkEnter(e)){SaveMainRewriteFilter$t();}
		}
		
		function SaveMainRewriteFilter$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		    XHR.appendData('frompattern', document.getElementById('frompattern-$t').value);
		    XHR.appendData('topattern', document.getElementById('topattern-$t').value);
		    XHR.appendData('ID', '$ID');
		    XHR.appendData('ruleid', '{$_GET["ruleid"]}');
		    if(document.getElementById('$t')){AnimateDiv('$t');}
		    XHR.sendAndLoad('$page', 'POST',x_SaveMainRewriteFilter2);  
			
		}
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


function rewrite_rule_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$ID=$_GET["ID"];
	$ligne=array();
	$button="{add}";
	$t=time();
	if($ID>0){
		$sql="SELECT * FROM webfilters_rewriterules WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$button="{apply}";	
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	$ligne["rulename"]=utf8_encode($ligne["rulename"]);
	$html="
	<div id='$t'>
	<center>
	<table style='width:70%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{rule_name}:</td>
		<td>". Field_text("rulename-$t",$ligne["rulename"],"font-size:14px;width:99%",null,null,null,false,"SaveMainRewriteFilterCheck(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"])."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"SaveMainRewriteFilter()",16)."</td>
	</tr>
	</tbody>
	</table>
	</center>
	</div>
	<script>
		var x_SaveMainRewriteFilter= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			var ID=$ID;
			if(ID==0){YahooWin2Hide();if(document.getElementById('tableau-reecriture-regles')){FlexReloadRulesRewrite();}return;}
			RefreshTab('main_rewriterule_$ID');
			if(document.getElementById('tableau-reecriture-regles')){FlexReloadRulesRewrite();}
			
			
		}	

		function SaveMainRewriteFilterCheck(e){
			if(checkEnter(e)){SaveMainRewriteFilter();}
		}
		
		function SaveMainRewriteFilter(){
			var XHR = new XHRConnection();
			if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		    XHR.appendData('rulename', document.getElementById('rulename-$t').value);
		    XHR.appendData('ID', '$ID');
		    if(document.getElementById('$t')){AnimateDiv('$t');}
		    XHR.sendAndLoad('$page', 'POST',x_SaveMainRewriteFilter);  
			
		}
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function rewrite_rule_items_enable(){
	$q=new mysql_squid_builder();
	$ID=$_POST["rewrite-rule-item-enable"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ruleid FROM webfilters_rewriteitems WHERE ID='$ID'"));
	$ruleid=$ligne["ruleid"];
	
	
	$sql="UPDATE  webfilters_rewriteitems SET enabled={$_POST["enable"]} WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_rewriteitems WHERE ruleid='$ruleid'"));	
	$q->QUERY_SQL("UPDATE webfilters_rewriterules SET ItemsCount={$ligne["tcount"]} WHERE ID=$ruleid");
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
	
}

function rewrite_rule_enable(){
	$q=new mysql_squid_builder();
	$ID=$_POST["rewrite-rule-enable"];		
	$sql="UPDATE webfilters_rewriterules SET enabled={$_POST["enable"]} WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
	
}

function rewrite_rule_delete(){
	$q=new mysql_squid_builder();
	$ID=$_POST["rewrite-rule-delete"];	
	$sql="DELETE FROM webfilters_rewriteitems WHERE ruleid='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sql="DELETE FROM webfilters_rewriterules WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");				
}

function rewrite_rule_items_delete(){
	$q=new mysql_squid_builder();
	$ID=$_POST["rewrite-rule-item-delete"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ruleid FROM webfilters_rewriteitems WHERE ID='$ID'"));
	$ruleid=$ligne["ruleid"];
	
	
	$sql="DELETE FROM webfilters_rewriteitems WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_rewriteitems WHERE ruleid='$ruleid'"));	
	$q->QUERY_SQL("UPDATE webfilters_rewriterules SET ItemsCount={$ligne["tcount"]} WHERE ID=$ruleid");
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
	
}
function rewrite_rule_items_save(){
	$ID=$_POST["ID"];
	if($ID==0){
		$sql="INSERT INTO webfilters_rewriteitems (enabled,frompattern,topattern,ruleid) 
		VALUES('{$_POST["enabled"]}','{$_POST["frompattern"]}','{$_POST["topattern"]}','{$_POST["ruleid"]}');";
	}else{
		$sql="UPDATE webfilters_rewriteitems SET enabled={$_POST["enabled"]},
		topattern='{$_POST["topattern"]}',
		frompattern='{$_POST["frompattern"]}'
		WHERE ID='{$_POST["ID"]}'";
		
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->CheckTables();$q->QUERY_SQL($sql);}
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_rewriteitems WHERE ruleid='{$_POST["ruleid"]}'"));	
	$q->QUERY_SQL("UPDATE webfilters_rewriterules SET ItemsCount={$ligne["tcount"]} WHERE ID={$_POST["ruleid"]}");
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}

function rewrite_rule_settings_save(){
	$_POST["rulename"]=addslashes($_POST["rulename"]);
	$ID=$_POST["ID"];
	if($ID==0){
		$sql="INSERT INTO webfilters_rewriterules (enabled,rulename) VALUES('{$_POST["enabled"]}','{$_POST["rulename"]}');";
	}else{
		$sql="UPDATE webfilters_rewriterules SET enabled={$_POST["enabled"]},rulename='{$_POST["rulename"]}' WHERE ID='{$_POST["ID"]}'";
		
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->CheckTables();$q->QUERY_SQL($sql);}
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
	
	
}


function rewrite_rule_tab(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$ID=$_GET["ID"];
	
	$array["rewrite-rule-settings"]="{settings}";
	if($ID>0){$array["rewrite-rule-items"]="{items}";}
	$fontsize=14;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&ID=$ID\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}

	echo "
	<div id=main_rewriterule_$ID style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_rewriterule_$ID').tabs();
			});
		</script>";	

}



function rewrite_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql_squid_builder();

	if($ID>0){
		$sql="SELECT rulename FROM webfilters_rewriterules WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
		$title=$ligne["rulename"];
	}else{
		$title="{new_rule}";
	}
	
	$title=$tpl->_ENGINE_parse_body(utf8_encode($title));
	$html="YahooWin2('650','$page?rewrite-rule-tab=yes&ID=$ID','$title');";
	echo $html;
	
	
}

function rewrite_rule_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}

	$title="$ID:{item}";
	$title=$tpl->_ENGINE_parse_body(utf8_encode($title));
	$html="YahooWin3('550','$page?rewrite-rule-item-popup=yes&ID=$ID&ruleid={$_GET["ruleid"]}','$title');";
	echo $html;	
}

function rewrite_rule_items(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$source=$tpl->_ENGINE_parse_body("{searchthestring}");
	$replaceby=$tpl->_ENGINE_parse_body("{replacewith}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	
	
	
	$buttons="
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : ReWriteRuleAddItem},
	],";		
	
	
$html="
<span id='tableau-reecriture-regles2'></div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rewrite-rule-items-list=yes&ruleid={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
		{display: '$source', name : 'frompattern', width : 262, sortable : false, align: 'left'},	
		{display: '$replaceby', name : 'topattern', width :220, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$source', name : 'frompattern'},
		],
	sortname: 'frompattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 600,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_RuleRewriteDeleteItem= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}	
		FlexReloadRulesRewriteItems();
		if(document.getElementById('tableau-reecriture-regles')){FlexReloadRulesRewrite();}
	}
	
	function RuleRewriteDeleteItem(ID){
			var XHR = new XHRConnection();
		    XHR.appendData('rewrite-rule-item-delete', ID);
		    XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDeleteItem); 	
	}
	
	function RuleRewriteEnableItem(ID,md5){
		var XHR = new XHRConnection();
		XHR.appendData('rewrite-rule-item-enable', ID);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDeleteItem); 	
	
	}
	
	function ReWriteRuleAddItem(){
		Loadjs('$page?rewrite-rule-item-js=yes&ID=0&ruleid={$_GET["ID"]}');
	}

	function FlexReloadRulesRewriteItems(){
		$('#flexRT$t').flexReload();
	}



</script>

";	
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : ReWriteRuleAdd},
	],";		
		
	if(($_GET["blk"]==2) OR ($_GET["blk"]==3)){
		$buttons="
		buttons : [
		{name: '$AddWWW', bclass: 'add', onpress : AddByWebsite},
		{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroupWWW},
		],";
	}

if($_GET["blk"]==4){		
	$buttons="
		buttons : [
		{name: '$AddWWW', bclass: 'add', onpress : AddByWebsite},
		{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroupWWW},
		],";
	}	
	
	
$html="
<div id='tableau-reecriture-regles' class=explain style='font-size:14px'>$rewrite_rules_fdb_explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?now-search=yes&blk={$_GET["blk"]}',
	dataType: 'json',
	colModel : [
		{display: '$rulename', name : 'rulename', width : 650, sortable : false, align: 'left'},	
		{display: '$items', name : 'ItemsNumber', width :60, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 830,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}
	
	function ReWriteRuleAdd(){
		Loadjs('$page?rewrite-rule=yes&ID=0');
	}
	
	function RuleRewriteDelete(ID){
		if(confirm('$delete')){
			var XHR = new XHRConnection();
		    XHR.appendData('rewrite-rule-delete', ID);
		    XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDelete); 		
		}
	}
	
	var x_RuleRewriteDelete= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}	
		FlexReloadRulesRewrite();
	}
	
	
	function RuleRewriteEnable(ID,md5){
		var XHR = new XHRConnection();
		XHR.appendData('rewrite-rule-enable', ID);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDelete); 	
	
	}
	

	function FlexReloadRulesRewrite(){
		$('#flexRT$t').flexReload();
	}



</script>

";	
	echo $html;
	
}


function popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_rewriterules";
	$page=1;
	$FORCE_FILTER=null;
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
	$results = $q->QUERY_SQL($sql);
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	
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
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$delete=imgtootltip("delete-32.png","{delete} Rule:{$ligne["rulename"]}","RuleRewriteDelete('{$ligne["ID"]}')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"RuleRewriteEnable('{$ligne["ID"]}','$md5')");	
		$js="Loadjs('$MyPage?rewrite-rule=yes&ID={$ligne["ID"]}');";
		
		
		writelogs("{$ligne["ID"]} => {$ligne["rulename"]}",__FUNCTION__,__FILE__,__LINE__);
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>{$ligne["rulename"]}</span>",
			"<span style='font-size:18px'>{$ligne["ItemsCount"]}</span>",$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}

function rewrite_rule_items_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_rewriteitems";
	$page=1;
	$FORCE_FILTER=null;
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE ruleid={$_GET["ruleid"]} $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE ruleid={$_GET["ruleid"]} $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE ruleid={$_GET["ruleid"]} $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	
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
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"].$_GET["ruleid"]);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$delete=imgtootltip("delete-32.png","{delete} Rule:{$ligne["ID"]}","RuleRewriteDeleteItem('{$ligne["ID"]}')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"RuleRewriteEnableItem('{$ligne["ID"]}','$md5')");	
		$js="Loadjs('$MyPage?rewrite-rule=yes&ID={$ligne["ID"]}');";
		$js="Loadjs('$MyPage?rewrite-rule-item-js=yes&ID={$ligne["ID"]}&ruleid={$_GET["ruleid"]}');";
		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>{$ligne["frompattern"]}</span>",
			"<span style='font-size:18px'>{$ligne["topattern"]}</span>",$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}


