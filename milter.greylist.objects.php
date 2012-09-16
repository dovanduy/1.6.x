<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){header('location:users.index.php');exit();}

	
	if(isset($_GET["group-js"])){group_js();exit;}
	if(isset($_GET["group-popup"])){group_popup();exit;}
	if(isset($_GET["group-tabs"])){group_tabs();exit;}
	if(isset($_GET["group-items"])){group_items();exit;}
	if(isset($_GET["group-items-list"])){group_items_list();exit;}
	if(isset($_POST["group-save"])){group_save();exit;}
	if(isset($_POST["group-enable"])){group_enable();exit;}
	if(isset($_POST["group-delete"])){group_delete();exit;}
	
	
	
	if(isset($_GET["item-js"])){item_js();exit;}
	if(isset($_GET["item-popup"])){item_popup();exit;}
	if(isset($_POST["item-save"])){item_save();exit;}
	if(isset($_POST["item-enable"])){item_enable();exit;}
	if(isset($_POST["item-delete"])){item_delete();exit;}
	
	
	if(isset($_GET["search"])){popup_list();exit;}
	
	popup();
	
	
	
function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$q=new mysql();
	$mil=new milter_greylist();
	$action=$mil->actionlist;	
	
	if($ID>0){
		$sql="SELECT * FROM miltergreylist_items WHERE ID='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$new_item=$ligne["item"];
	}
	
	$sql="SELECT * FROM miltergreylist_objects WHERE ID='{$_GET["gpid"]}'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	$action=$tpl->_ENGINE_parse_body("{{$ligne["type"]}}");
	

	
	
	$title="{$_GET["hostname"]}::{$ligne["objectname"]}::$action::$new_item";
	$html="YahooWin4('525','$page?item-popup=yes&ID=$ID&hostname={$_GET["hostname"]}&gpid={$_GET["gpid"]}&type={$ligne["type"]}','$title')";
	echo $html;	
	
	
}
	
function group_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	if($ID==0){$title="{$_GET["hostname"]}::$new_group";}
	
	if($ID>0){
			$sql="SELECT * FROM miltergreylist_objects WHERE ID='{$_GET["ID"]}'";
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
			$title="{$_GET["hostname"]}::{$ligne["objectname"]}";
	}
	
	$html="YahooWin3('550','$page?group-tabs=yes&ID=$ID&hostname={$_GET["hostname"]}','$title')";
	echo $html;
}


function group_tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{parameters}';

	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}	
	$array["items"]='{items}';
	if($ID==0){unset($array["items"]);}
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?group-$num=yes&ID=$ID&hostname={$_GET["hostname"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_mgreylist_groups style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mgreylist_groups').tabs();
			});
		</script>";		
	
}

function item_popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$button="{add}";
	$mil=new milter_greylist();
	$action=$mil->actionlist;
	$sql="SELECT * FROM miltergreylist_items WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	$explain=$tpl->_ENGINE_parse_body("{".$action[$_GET["type"]]."}");
	
	if($_GET["ID"]>0){$button="{apply}";}
	
	
	switch ($_GET["type"]){
		
		case "addr": $field=field_ipv4("src{$t}", $ligne["item"],"font-size:16px");break;

		
		case "dnsrbl":
			$pure=new milter_greylist();
			$field=Field_array_Hash($pure->dnsrbl_class,"src{$t}",$ligne["item"],null,null,0,"font-size:14px");
			break;
			
			
		case "geoip":
			include_once(dirname(__FILE__)."/ressources/class.spamassassin.inc");
			$spam=new spamassassin();
			$spam->CountriesCode[null]="{select}";
			$field=Field_array_Hash($spam->CountriesCode,"src{$t}",$ligne["item"],null,null,0,"font-size:14px");
			break;
		
		default:$field=field_text("src{$t}", $ligne["item"],"font-size:16px;width:220px");break;
		
		
		
		
	}
	
	
	$html="
	<div class=explain style='font-size:13px'>{{$_GET["type"]}_text}</div>
	<div id='$t'>
	
		<table style='width:98%' class=form>
		<tbody>
			<tr>
				<td align='right' width=1% nowrap style='font-size:16px'><strong>{{$_GET["type"]}}:</strong></td>
				<td>$field</td>
			</tr>
			<tr>
				<td align='right' colspan=2><hr>". button($button,"SaveMilter{$t}GreyListITEM()",16)."</td>
			</tr>					
			
			
	</tbody>
	</table>	
<script>
	var SaveMilterGreyListITEM$t= function (obj) {
			var tempvalue=obj.responseText;
			var ID={$_GET["ID"]};
			if(tempvalue.length>3){alert(tempvalue);}
			YahooWin4Hide();
			RefreshTableMiltITZ();
			RefreshTableMiltGP();
		}		

	function SaveMilter{$t}GreyListITEM(){
		if(!document.getElementById('src{$t}')){alert('src{$t} no such id');return;}
		var tt=document.getElementById('src{$t}').value;
		if(tt.length<2){return;}
		var XHR = new XHRConnection();
		
		XHR.appendData('item-save',document.getElementById('src{$t}').value);
		XHR.appendData('item',document.getElementById('src{$t}').value);
		XHR.appendData('gpid','{$_GET["gpid"]}');
		XHR.appendData('ID','{$_GET["ID"]}');
		AnimateDiv('$t');
     	XHR.sendAndLoad('$page', 'POST',SaveMilterGreyListITEM$t);
	}
	
	
	
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function group_popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$button="{add}";
	$mil=new milter_greylist();
	$action=$mil->actionlist;
	
	unset($action["dnsrbl"]);
	unset($action["urlcheck"]);
	
	$sql="SELECT * FROM miltergreylist_objects WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$arrayf=Field_array_Hash($action,"type-$t",$ligne["type"],"explainThisacl('type-$t');",null,0,'width:150px;font-size:16px;padding:5px');
	
	if($_GET["ID"]>0){$button="{apply}";}
	
	$html="
	<div id='$t'>
	
		<table style='width:98%' class=form>
		<tbody>
			<tr>
				<td align='right' width=1% nowrap style='font-size:16px'><strong>{name}:</strong></td>
				<td>". Field_text("objectname-$t",$ligne["objectname"],"font-size:16px;width:260px")."</td>
			</tr>
			<tr>
				<td align='right' width=1% nowrap style='font-size:16px'><strong>{type_of_rule}:</strong></td>
				<td><strong>$arrayf</strong></td>
			</tr>
			<tr>
				<td align='right' colspan=2><hr>". button($button,"SaveMilterGreyListGroup$t()",16)."</td>
			</tr>					
	</tbody>
	</table>
	<div id='explainc'></span>	
<script>
	var x_SaveMilterGreyListGrou$t= function (obj) {
			var tempvalue=obj.responseText;
			var ID={$_GET["ID"]};
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTableMiltGP();
			if(ID==0){YahooWin3Hide();}else{RefreshTab('main_config_mgreylist_groups');}
			
		}		

	function SaveMilterGreyListGroup$t(){
		var XHR = new XHRConnection();
		var tt=document.getElementById('type-$t').value;
		if(tt.length<3){return;}
		XHR.appendData('group-save',document.getElementById('objectname-$t').value);
		XHR.appendData('objectname',document.getElementById('objectname-$t').value);
		XHR.appendData('type',document.getElementById('type-$t').value);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ID','{$_GET["ID"]}');
		AnimateDiv('$t');
     	XHR.sendAndLoad('$page', 'POST',x_SaveMilterGreyListGrou$t);
	}
	
	function CheckForm$t(){
		var ID={$_GET["ID"]};
		if(ID>0){document.getElementById('type-$t').disabled=true;}
	}
	
	CheckForm$t();
	
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function item_enable(){
	$sql="UPDATE miltergreylist_items SET enabled='{$_POST["enabled"]}' WHERE ID='{$_POST["ID"]}'";
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function item_save(){
	$_POST["item"]=mysql_escape_string($_POST["item"]);
	
	$sql="INSERT INTO miltergreylist_items (`item`,`groupid`,`enabled`) 
	VALUES ('{$_POST["item"]}','{$_POST["gpid"]}','1')";


	if($_POST["ID"]>0){$sql="UPDATE miltergreylist_items SET item='{$_POST["item"]}' WHERE ID='{$_POST["ID"]}'";}
	
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function item_delete(){
	
	$sql="DELETE FROM miltergreylist_items WHERE ID={$_POST["ID"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function  group_delete() {
	$q=new mysql();
	
	$q->QUERY_SQL("DELETE FROM miltergreylist_items WHERE groupid={$_POST["ID"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM miltergreylist_objects WHERE ID={$_POST["ID"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}			
}

function group_save(){
		
	$_POST["objectname"]=trim($_POST["objectname"]);
	if($_POST["objectname"]==null){$_POST["objectname"]="New " .$_POST["type"];}
	
	$_POST["objectname"]=mysql_escape_string($_POST["objectname"]);
	
	$sql="INSERT INTO miltergreylist_objects (`instance`,`type`,`enabled`,`objectname`) 
	VALUES ('{$_POST["hostname"]}','{$_POST["type"]}','1','{$_POST["objectname"]}')";
	
	if($_POST["ID"]>0){$sql="UPDATE miltergreylist_objects SET objectname='{$_POST["objectname"]}' WHERE ID='{$_POST["ID"]}'";}
	
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function group_enable(){
	$sql="UPDATE miltergreylist_objects SET enabled={$_POST["enabled"]} WHERE ID={$_POST["ID"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function group_items(){
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$expressions=$tpl->_ENGINE_parse_body("{expressions}");
	$add=$tpl->javascript_parse_text("{new_item}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");

	$TB_WIDTH=495;
	$t=time();
	

	
	$html="
	
	<table class='$t-table2' style='display: none' id='$t-table2' style='width:99%'></table>
	
<script>
var ITEMIDMEM=0;
$(document).ready(function(){
$('#$t-table2').flexigrid({
	url: '$page?group-items-list=yes&hostname={$_GET["hostname"]}&t=$t&gpid={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
		{display: '$items', name : 'item', width : 380, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'delete2', width : 32, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete3', width : 32, sortable : false, align: 'center'},
	],

buttons : [
		{name: '$add', bclass: 'add', onpress : addMgreylistItem$t},
		{separator: true}
		],	
	searchitems : [
		{display: '$items', name : 'item'},
		],
	sortname: 'item',
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

	var x_TermesAssosciate$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table1').flexReload();
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
    }
    
	var x_EEnableMgreyItem$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTableMiltGP();
	} 

	var x_DeleteMgreyItem=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTableMiltITZ();
		RefreshTableMiltGP();
	}   	
    
    function RefreshTableMiltITZ(){
    	$('#$t-table2').flexReload();
    }
    
   
    
    function EnableMgreyItem(md,ID){
		var XHR = new XHRConnection();
		XHR.appendData('item-enable','yes');
		if(document.getElementById(md).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('ID',ID);
     	XHR.sendAndLoad('$page', 'POST',x_EEnableMgreyItem$t);    
    }
    
    function DeleteMgreyItem(ID){
    	ITEMIDMEM=ID;
		var XHR = new XHRConnection();
		XHR.appendData('item-delete','yes');
		XHR.appendData('ID',ID);
     	XHR.sendAndLoad('$page', 'POST',x_DeleteMgreyItem);      	
    	
    }
    
    function addMgreylistItem$t(){
    	Loadjs('$page?item-js=yes&hostname={$_GET["hostname"]}&gpid={$_GET["ID"]}&ID=0&t=$t');
    }

</script>	";
echo $tpl->_ENGINE_parse_body($html);	
	
}
	
function popup(){
	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$expressions=$tpl->_ENGINE_parse_body("{expressions}");
	$add=$tpl->javascript_parse_text("{new_group}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$are_you_sure_to_delete_the_group=$tpl->javascript_parse_text("{are_you_sure_to_delete_the_group}");
	$TB_WIDTH=780;
	$ROW_EXPLAIN=416;
	$TB_HEIGHT=250;
	$t=time();
	
	if(isset($_GET["expand"])){
		$TB_WIDTH=860;
		$ROW_EXPLAIN=484;
		$TB_HEIGHT=600;
	}
	
	$html="
	
	<table class='$t-table2' style='display: none' id='$t-table2' style='width:99%'></table>
	
<script>
var IDRULEEXPPP=0;
$(document).ready(function(){
$('#$t-table2').flexigrid({
	url: '$page?search=yes&hostname={$_GET["hostname"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$groupname', name : 'objectname', width : 239, sortable : true, align: 'left'},	
		{display: '$explain', name : 'explain', width :416, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete2', width : 32, sortable : false, align: 'center'},
	],

buttons : [
		{name: '$add', bclass: 'add', onpress : addMgreylistGroup$t},
		{separator: true}
		],	
	searchitems : [
		{display: '$groupname', name : 'objectname'},
		],
	sortname: 'objectname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	var x_TermesAssosciate$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#$t-table1').flexReload();
		if(document.getElementById('TableDesLaisonsExpressionsUfdb')){RefreshTableDesLaisonsExpressionsUfdb();}
    }
    
	var x_EnableMgreyGroup$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	} 

	var x_DeleteMgreyGroup=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#rowgroupID'+IDRULEEXPPP).remove();
	} 
	
    
    function RefreshTableMiltGP(){
    	$('#$t-table2').flexReload();
    }
    
   
    
    function EnableMgreyGroup(md,ID){
		var XHR = new XHRConnection();
		XHR.appendData('group-enable','yes');
		if(document.getElementById(md).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('ID',ID);
     	XHR.sendAndLoad('$page', 'POST',x_EnableMgreyGroup$t);    
    }
    
    function DeleteMgreyGroup(ID,name){
    	if(confirm('$are_you_sure_to_delete_the_group '+name+' ?')){
    		IDRULEEXPPP=ID;
    		var XHR = new XHRConnection();
    		XHR.appendData('group-delete','yes');
			XHR.appendData('ID',ID);
     		XHR.sendAndLoad('$page', 'POST',x_DeleteMgreyGroup); 
     	} 
    }
    
    function addMgreylistGroup$t(){
    	Loadjs('$page?group-js=yes&hostname={$_GET["hostname"]}&ID=$ID&t=$t');
    }

</script>	";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function group_items_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$or=$tpl->_ENGINE_parse_body("{or}");
	$database="artica_backup";
	$search='%';
	$table="miltergreylist_items";
	$page=1;
	include_once(dirname(__FILE__)."/ressources/class.spamassassin.inc");
	$spam=new spamassassin();
	$t=$_GET["t"];
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE groupid={$_GET["gpid"]} $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE groupid={$_GET["gpid"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE groupid={$_GET["gpid"]} $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
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
		$ligne['item']=str_replace("'", "`", $ligne['item']);
		
		
		
		$tt=array();
		
		
		$id=md5($ligne["ID"].$ligne['item']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["item"]}","DeleteMgreyItem('{$ligne['ID']}')");
		$enable=Field_checkbox($id, 1,$ligne["enabled"],"EnableMgreyItem('$id','{$ligne['ID']}')");
		$maincolor="black";
		$JSGROUP="Loadjs('$MyPage?item-js=yes&hostname={$_GET["hostname"]}&ID={$ligne["ID"]}&gpid={$ligne["groupid"]}&t={$_GET["t"]}');";
		
		
		if($spam->CountriesCode[$ligne["item"]]<>null){$ligne["item"]=$ligne["item"]." ({$spam->CountriesCode[$ligne["item"]]})";}
		
		$actionTXT=$tpl->_ENGINE_parse_body($action[$ligne["type"]]);
		if($ligne["enabled"]==0){$maincolor="#737373";}
		
		
		if($ligne["item"]==null){$ligne["item"]="???";}
	$data['rows'][] = array(
		'id' => "item$t".$ligne['ID'],
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"$JSGROUP\"  
		style='font-size:16px;font-weight:bold;color:$maincolor;text-decoration:underline'>{$ligne["item"]}</span>",
		"<div style='margin-top:5px'>$enable</div>",
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$or=$tpl->_ENGINE_parse_body("{or}");
	$database="artica_backup";
	$search='%';
	$table="miltergreylist_objects";
	$page=1;
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("Table $table is empty");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE instance='{$_GET["hostname"]}' $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`WHERE instance='{$_GET["hostname"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE instance='{$_GET["hostname"]}' $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
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
	
	$mil=new milter_greylist();
	$action=$mil->actionlist;	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["objectname"]==null){$ligne["objectname"]="New " .$ligne["type"];}
		$ligne['objectname']=str_replace("'", "`", $ligne['objectname']);
		$tt=array();
		$petitspoints=null;
		$gpnma=urlencode($ligne['objectname']);
		$id=md5($ligne["ID"].$ligne['objectname']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["objectname"]}","DeleteMgreyGroup('{$ligne['ID']}','{$ligne['objectname']}')");
		$enable=Field_checkbox($id, 1,$ligne["enabled"],"EnableMgreyGroup('$id','{$ligne['ID']}')");
		$maincolor="black";
		$sql="SELECT item,enabled FROM miltergreylist_items WHERE groupid={$ligne['ID']}";	
		$results2 = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);$tt[]=$q->mysql_error;}
		if(mysql_num_rows($results2)==0){$maincolor="#737373";}
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if($ligne2["enabled"]==0){$ligne2["item"]="<i style='color:#737373'>{$ligne2["item"]}</i>";}
			$tt[]=$ligne2["item"];
			if(count($tt)>10){break;$petitspoints="...";}
		}
		
		$JSGROUP="Loadjs('$MyPage?group-js=yes&hostname={$_GET["hostname"]}&ID={$ligne["ID"]}&t={$_GET["t"]}');";
		$jsItems="<a href=\"javascript:blur();\" 
		OnClick=\"$JSGROUP\" 
		style='font-size:13px;text-decoration:underline'>";
		
		
		
		$actionTXT=$tpl->_ENGINE_parse_body($action[$ligne["type"]]);
		
	$data['rows'][] = array(
		'id' => "groupID".$ligne['ID'],
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"$JSGROUP\"  
		style='font-size:16px;font-weight:bold;color:$maincolor;text-decoration:underline'>{$ligne["objectname"]}</span>"
		,"<span style='font-size:13px'>$actionTXT:&nbsp;$jsItems". @implode(" $or ", $tt)." $petitspoints</a></span>",
		"<div style='margin-top:5px'>$enable</div>",
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}