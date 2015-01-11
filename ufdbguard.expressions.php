<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
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




if(isset($_GET["list"])){rules_list();exit;}
if(isset($_POST["rule-add"])){rules_add();exit;}
if(isset($_POST["rule-delete"])){rules_delete();exit;}
if(isset($_POST["rule-enable"])){rules_enable();exit;}
if(isset($_GET["change-rule-name-js"])){rules_changename_js();exit;}
if(isset($_POST["rule-edit"])){rules_edit();exit;}

if(isset($_GET["rules-link-js"])){rules_link_js();exit;}
if(isset($_GET["rule-link"])){rules_link_popup();exit;}
if(isset($_GET["rule-link-list"])){rules_link_list();exit;}

if(isset($_GET["rules-browse-js"])){rules_browse_js();exit;}
if(isset($_GET["rules-browse-list"])){rules_browse_list();exit;}
if(isset($_GET["rules-browse-popup"])){rules_browse_popup();exit;}
if(isset($_POST["rule-link-add"])){rules_link_add();exit;}
if(isset($_POST["rule-link-enable"])){rules_link_enable();exit;}
if(isset($_POST["rule-link-unlink"])){rules_link_delete();exit;}
table();


function rules_browse_js(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM webfilter_ufdbexpr WHERE ID={$_GET["ID"]}"));
	$ligne["rulename"]=str_replace("'", "`", $ligne["rulename"]);
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("::{browse}:{groups}:{expressions}");
	$html="
	YahooWinBrowse('600','$page?rules-browse-popup=yes&ID={$_GET["ID"]}&ruleid={$_GET["ruleid"]}&t={$_GET["t"]}','{$ligne["rulename"]}$title');";
	echo $html;
}

function rules_browse_popup(){
	$ID=$_GET["ID"];
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$expressions=$tpl->_ENGINE_parse_body("{expressions}");
	$add=$tpl->_ENGINE_parse_body("{link}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$give_the_rulename=$tpl->javascript_parse_text("{give_the_rulename}");
	$TB_WIDTH=580;
	$time=time();
	
	$html="
	<table class='$t-table2' style='display: none' id='$t-table2' style='width:99%'></table>
<script>
var IDRULEEXPP=0;
$(document).ready(function(){
$('#$t-table2').flexigrid({
	url: '$page?rules-browse-list=yes&ruleid=$ruleid&ID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$groupname', name : 'groupname', width : 140, sortable : true, align: 'left'},	
		{display: '$explain', name : 'ItemsNumber', width :256, sortable : false, align: 'left'},
		{display: '$words', name : 'WordCount', width : 48, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete2', width : 32, sortable : false, align: 'center'},
	],


	searchitems : [
		{display: '$groupname', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true
	
	});   
});

	var x_TermesAssosciate$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table1').flexReload();
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
    }
    
    function LinkExprBrowse$t(){
    	Loadjs('$page?rules-browse-js=yes&ruleid=$ruleid&ID=$ID&t=$t');
    
    }


	function TermesAssosciate(ID) {
		var XHR = new XHRConnection();
		XHR.appendData('rule-link-add','yes');
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('masterid',$ID);
		XHR.appendData('groupid',ID);
		XHR.sendAndLoad('$page', 'POST',x_TermesAssosciate$t);
	}
</script>	";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function rules_browse_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$or=$tpl->_ENGINE_parse_body("{or}");
	
	$search='%';
	$table="webfilter_termsg";
	$page=1;
	
	$total=0;
	
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table");}
	
	if($q->COUNT_ROWS($table)==0){
		if(!$q->ok){json_error_show("No data");}
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne['groupname']=str_replace("'", "`", $ligne['groupname']);
		$tt=array();
		$gpnma=urlencode($ligne['groupname']);
		$JSWORDS="Loadjs('squid.terms.groups.php?AddWordsInGroup-js=yes&ID={$ligne['ID']}&groupname=$gpnma')";
		$select=imgtootltip("arrow-right-24.png","{select} {$ligne["groupname"]}","TermesAssosciate('{$ligne['ID']}')");
		$addwords=imgtootltip("plus-24.png","{add} {$ligne["groupname"]}","$JSWORDS");
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
		
		
		$js="<a href=\"javascript:blur();\" 
		OnClick=\"$JSWORDS\" 
		style='font-size:13px;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => "group".$ligne['ID'],
		'cell' => array("<span style='font-size:13px;font-weight:bold;color:$maincolor'>{$ligne["groupname"]}</span>"
		,"<span style='font-size:13px'>$js". @implode(" $or ", $tt)." $petitspoints</a></span>",
		"<span style='font-size:13px'>$wordscount</span>",$addwords,$select )
		);
	}
	
	
echo json_encode($data);		
	
}


function rules_link_js(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM webfilter_ufdbexpr WHERE ID={$_GET["ID"]}"));
	$ligne["rulename"]=utf8_encode(str_replace("'", "`", $ligne["rulename"]));
	$t=$_GET["t"];
	$description=$tpl->javascript_parse_text("{groups2}:{expressions}::{$ligne["rulename"]}");
	$html="
	YahooWin6('600','$page?rule-link=yes&ID={$_GET["ID"]}&ruleid={$_GET["ruleid"]}&t={$_GET["t"]}','$description');";
	echo $html;
}

function rules_link_popup(){
	$ID=$_GET["ID"];
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$expressions=$tpl->_ENGINE_parse_body("{expressions}::{groups2}");
	$add=$tpl->_ENGINE_parse_body("{link}");
	$ufdbguard_terms_explain=$tpl->_ENGINE_parse_body("{ufdbguard_terms_explain}");
	$give_the_rulename=$tpl->javascript_parse_text("{give_the_rulename}");
	$TB_WIDTH=585;
	$time=time();
	 
	$html="
	<span id='TableDesLaisonsExpressionsUfdb'></span>
	<table class='$t-table1' style='display: none' id='$t-table1' style='width:99%'></table>
<script>
var IDRULEEXPPL=0;
$(document).ready(function(){
$('#$t-table1').flexigrid({
	url: '$page?rule-link-list=yes&ruleid=$ruleid&ID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$expressions', name : 'rulename', width : 467, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none4', width : 30, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : LinkExprBrowse$t},
		{separator: true},

		
		],	
	searchitems : [
		{display: '$expressions', name : 'rulename'},
		],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true
	
	});   
});

	var x_LinkExprRule$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table1').flexReload();
		$('#$t-table').flexReload();
    }
    
    function RefreshTableDesLaisonsExpressionsUfdb(){
    	$('#$t-table1').flexReload();
    	$('#$t-table').flexReload();
    }
    
	var x_LinkExprRuleS$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTableExpressionsParReglesLiees();
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
    }  

	var x_RuleExprUnlink$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#rowruleexpL'+IDRULEEXPPL).remove();
		RefreshTableExpressionsParReglesLiees();
    }      
    
    function LinkExprBrowse$t(){
    	Loadjs('$page?rules-browse-js=yes&ruleid=$ruleid&ID=$ID&t=$t');
    
    }
    
    function RuleExprLinkDisable$t(md,ID){
   	 var XHR = new XHRConnection();
	 	XHR.appendData('rule-link-enable','yes');
		XHR.appendData('ID',ID);
		if(document.getElementById(md).checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
		XHR.sendAndLoad('$page', 'POST',x_LinkExprRuleS$t);
    
    }
    
    function RuleExprUnlink$t(ID){
    	IDRULEEXPPL=ID;
   		 var XHR = new XHRConnection();
	 	 XHR.appendData('rule-link-unlink','yes');
	 	XHR.appendData('ID',ID);
		XHR.sendAndLoad('$page', 'POST',x_RuleExprUnlink$t);
    
    }    


	function LinkExprRule$t() {
		var r=prompt('$give_the_rulename','My expression rule');
		if(r){
			var XHR = new XHRConnection();
		 	XHR.appendData('rule-add',r);
		 	XHR.appendData('ruleid',$ruleid);
		 	XHR.sendAndLoad('$page', 'POST',x_LinkExprRule$t);
		}
	}
</script>	";
echo $tpl->_ENGINE_parse_body($html);
}


function rules_link_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$textdisabled=$tpl->_ENGINE_parse_body("{disabled}");
	$or=$tpl->_ENGINE_parse_body("{or}");
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_ufdbexprassoc";
	$page=1;
	$ORDER="ORDER BY ext ASC";
	$FORCE_FILTER=" AND groupid={$_GET["ID"]}";
	
	$total=0;
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no rules");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$search=null;
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		
		$searchstring="AND webfilter_termsg.groupname LIKE '{$_POST["query"]}'";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`,webfilter_termsg 
		WHERE $table.groupid={$_GET["ID"]}
		AND $table.termsgid=webfilter_termsg.ID $searchstring";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT webfilter_termsg.groupname as rulename,$table.enabled,$table.ID,$table.termsgid FROM `$table`,webfilter_termsg WHERE $table.groupid={$_GET["ID"]} AND $table.termsgid=webfilter_termsg.ID $searchstring $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5("ruleexpLink".$ligne["ID"]);
		$color="black";
		$fontstyle="normal";
		$textdisabledRow=null;
		$disable=Field_checkbox("$md5", 1,$ligne["enabled"],"RuleExprLinkDisable$t('$md5',{$ligne["ID"]})");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['rulename']}","RuleExprUnlink$t('{$ligne["ID"]}')");
		$link=imgtootltip("arrowup-24.png","{link} {$ligne['rulename']}","RuleLink$t('{$ligne["ID"]}')");
		
		$sql="SELECT webfilter_terms.term,webfilter_termsassoc.ID,webfilter_terms.enabled as termenabled,
		webfilter_terms.ID as termzID
		FROM `webfilter_termsassoc`,webfilter_terms WHERE webfilter_terms.ID=`webfilter_termsassoc`.termid
		AND `webfilter_termsassoc`.term_group={$ligne["termsgid"]} $searchstring";
		
		
		$results2 = $q->QUERY_SQL($sql,"artica_backup");
		$tt1=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$color1="black";
			$fontstyle1="normal";
			if($ligne2["termenabled"]==0){$color1="#737373";$fontstyle1="italic";}
			$sjs="Loadjs('squid.terms.groups.php?expression-edit=yes&ID={$ligne2["termzID"]}');";
			$tt1[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$sjs\" style='font-size:11px;font-style:$fontstyle1;color:$color1;text-decoration:underline'>{$ligne2["term"]}</a>";
			
		}
		
		$explain="<div>".@implode(" <span style='font-size:11px'>$or</span> ", $tt1)."</div>";
		if($ligne["enabled"]==0){$color="#737373";$textdisabledRow="<i> ($textdisabled)</i>";$fontstyle="italic";}
		$jsrule="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.terms.groups.php?AddWordsInGroup-js=yes&ID={$ligne["termsgid"]}&t={$_GET["t"]}');\" 
		style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline;font-style:$fontstyle'>";
		
	$data['rows'][] = array(
		'id' => "ruleexpL".$ligne['ID'],
	'cell' => array(
		"<span style='font-size:13px'>$jsrule{$ligne['rulename']}</a>$explain</strong>",
		"<div style='margin-top:5px'>$disable</div>",$delete)
		);
		
	}
	
	// http://*domain.<span style='color:#A40000'>
	
echo json_encode($data);	
		
	
}

function rules_changename_js(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM webfilter_ufdbexpr WHERE ID={$_GET["ID"]}"));
	$ligne["rulename"]=str_replace("'", "`", $ligne["rulename"]);
	$t=$_GET["t"];
	$give_the_rulename=$tpl->javascript_parse_text("{give_the_rulename}");
	$html="
	var x_EditExprRule$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table').flexReload();
    }


	function EditExprRule$t() {
		var r=prompt('$give_the_rulename','{$ligne["rulename"]}');
		if(r){
			var XHR = new XHRConnection();
		 	XHR.appendData('rule-edit',r);
		 	XHR.appendData('ID',{$_GET["ID"]});
		 	XHR.sendAndLoad('$page', 'POST',x_EditExprRule$t);
		}
	}
	
	EditExprRule$t()";
	echo $html;
}


function table(){
	$ID=$_GET["ID"];
	$ruleid=$ID;
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->javascript_parse_text("{groups2}:{expressions}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$files_restrictions=$tpl->_ENGINE_parse_body("{files_restrictions}");
	$add=$tpl->_ENGINE_parse_body("{new_rule}");
	$ufdbguard_terms_explain=$tpl->_ENGINE_parse_body("{ufdbguard_terms_explain}");
	$give_the_rulename=$tpl->javascript_parse_text("{give_the_rulename}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$TB_WIDTH=872;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$t=time();
	
	$html="
	<div id='TableExpressionsParReglesLiees' class=text-info>$ufdbguard_terms_explain</div>
	<table class='$t-table' style='display: none' id='$t-table' style='width:99%'></table>
<script>
var IDRULEEXP=0;
$(document).ready(function(){
$('#$t-table').flexigrid({
	url: '$page?list=yes&RULEID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$rulename', name : 'rulename', width : 289, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 411, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none4', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 30, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : AddNewExprRule$t},
		{separator: true},
		{name: '$online_help', bclass: 'Help', onpress : help$t},
		
		],	
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});
function help$t(){
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=313','1024','900');
}


	var x_AddNewExprRule$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table').flexReload();
    }

    function RefreshTableExpressionsParReglesLiees(){
    	$('#$t-table').flexReload();
    }

	function AddNewExprRule$t() {
		var r=prompt('$give_the_rulename','My expression rule');
		if(r){
			var XHR = new XHRConnection();
		 	XHR.appendData('rule-add',r);
		 	XHR.appendData('ruleid',$ruleid);
		 	XHR.sendAndLoad('$page', 'POST',x_AddNewExprRule$t);
		}
	}

var x_RuleExpEnable$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
}	        
      
function RuleExpEnable$t(md5,id){
	 var XHR = new XHRConnection();
	 XHR.appendData('rule-enable',id);
	 if(document.getElementById(md5).checked){XHR.appendData('value','1');}else{XHR.appendData('value','0');}
	 XHR.sendAndLoad('$page', 'POST',x_RuleExpEnable$t);
}

function RuleLink$t(ID){
	Loadjs('$page?rules-link-js=yes&ID='+ID+'&ruleid=$ruleid&t=$t');
}

var x_RuleExpDelete$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#rowruleexp'+IDRULEEXP).remove();
}

function RuleExpressionDelete$t(ID){
	RuleExpDelete$t(ID);
}

function RuleExpDelete$t(ID){
	IDRULEEXP=ID;
	var XHR = new XHRConnection();
	XHR.appendData('rule-delete',ID);
	XHR.sendAndLoad('$page', 'POST',x_RuleExpDelete$t);
}

</script>	";
echo $tpl->_ENGINE_parse_body($html);
}
function rules_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$textdisabled=$tpl->_ENGINE_parse_body("{disabled}");
	$and=$tpl->_ENGINE_parse_body("{and}");
	$no_expression_group=$tpl->_ENGINE_parse_body("{no_expression_group}");
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_ufdbexpr";
	$page=1;
	$ORDER="ORDER BY ext ASC";
	$FORCE_FILTER=" AND ruleid={$_GET["RULEID"]}";
	
	$total=0;
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("No row");
	}
		
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5("ruleexp".$ligne["ID"]);
		$color="black";
		$textdisabledRow=null;
		$disable=Field_checkbox("$md5", 1,$ligne["enabled"],"RuleExpEnable$t('$md5',{$ligne["ID"]})");
		$delete=imgsimple("delete-24.png","{delete} {$ligne['rulename']}","RuleExpressionDelete$t('{$ligne["ID"]}')");
		$link=imgsimple("arrowup-24.png","{link} {$ligne['rulename']}","RuleLink$t('{$ligne["ID"]}')");
		$RuleName=$ligne['rulename'];
		if(trim($RuleName)==null){$RuleName="New Rule ID {$ligne["ID"]}";}
		$RuleName=utf8_encode($RuleName);
		
		if($ligne["enabled"]==0){$color="#737373";$textdisabledRow="<i> ($textdisabled)</i>";}
		$jsrule="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?change-rule-name-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}');\" 
		style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		$sql="SELECT webfilter_termsg.groupname as rulename,webfilter_ufdbexprassoc.enabled,webfilter_ufdbexprassoc.ID FROM `webfilter_ufdbexprassoc`,webfilter_termsg WHERE webfilter_ufdbexprassoc.groupid={$ligne["ID"]} AND webfilter_ufdbexprassoc.termsgid=webfilter_termsg.ID";	
		$results2 = $q->QUERY_SQL($sql,"artica_backup");
		$tt=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$color1="black";
			$fontstyle="normal";
			if($ligne2["enabled"]==0){$color1="#737373";$fontstyle="italic";}
			$tt[]="<a href=\"javascript:blur();\" OnClick=\"javascript:RuleLink$t('{$ligne["ID"]}');\" 
			style='text-decoration:underline;font-size:16px;color:$color1;font-style:$fontstyle'>{$ligne2["rulename"]}</a>";
		}	

		if(count($tt)==0){$description="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:RuleLink$t('{$ligne["ID"]}');\" 
			style='text-decoration:underline;'>$no_expression_group</a>";}else{
			$description=@implode("<br>$and ", $tt);
		}
		
		
	$data['rows'][] = array(
		'id' => "ruleexp".$ligne['ID'],
	'cell' => array(
		"<span style='font-size:18px'>$jsrule$RuleName</a></strong>",
		"<span style='font-size:16px'>{$description}</span>",
		"<span style='margin-top:5px'>$disable</span>",$link,$delete)
		);
		
	}
	
	// http://*domain.<span style='color:#A40000'>
	
echo json_encode($data);	
	
	
}

function rules_link_add(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$sql="INSERT webfilter_ufdbexprassoc (groupid,termsgid,enabled) VALUES({$_POST["masterid"]},{$_POST["groupid"]},1)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}

function rules_link_enable(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilter_ufdbexprassoc SET enabled={$_POST["enable"]} WHERE ID={$_POST["ID"]}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");		
}
function rules_link_delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM webfilter_ufdbexprassoc WHERE ID={$_POST["ID"]}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");			
}

function rules_edit(){
	$_POST["rule-edit"]=mysql_escape_string2($_POST["rule-edit"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilter_ufdbexpr SET rulename='{$_POST["rule-edit"]}' WHERE ID={$_POST["ID"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}

function rules_add(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$RuleName=addslashes($_POST["rule-add"]);
	$q->QUERY_SQL("INSERT INTO webfilter_ufdbexpr (rulename,enabled,ruleid) VALUES('$RuleName',1,{$_POST["ruleid"]})");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}

function rules_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_ufdbexprassoc WHERE groupid={$_POST["rule-delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM webfilter_ufdbexpr WHERE ID={$_POST["rule-delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function rules_enable(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilter_ufdbexpr SET enabled={$_POST["value"]} WHERE ID={$_POST["rule-enable"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
		
}



