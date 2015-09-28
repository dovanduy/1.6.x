<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$dirname=dirname(__FILE__);
if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}

include_once($dirname.'ressources/class.templates.inc');
include_once($dirname.'/ressources/class.ldap.inc');
include_once($dirname.'/ressources/class.users.menus.inc');
include_once($dirname.'/ressources/class.squid.inc');
include_once($dirname.'/ressources/class.ActiveDirectory.inc');

if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tab"])){rule_tab();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-items-list"])){rule_list();exit;}
if(isset($_GET["rule-categories"])){categories();exit;}
if(isset($_GET["categories-list"])){categories_list();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}

if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_POST["delete"])){rule_delete();exit;}
if(isset($_POST["enable"])){rule_enable();exit;}

rules();

function enable_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_GET["enable-js"];
	
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#SQUID_ARTICA_QUOTA_RULES').flexReload();
	}
	
	function xFunct$t(){
	
	var XHR = new XHRConnection();
	XHR.appendData('enable','$ID');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;
	
	
	
}

function rule_enable(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_POST["enable"];
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM `webfilter_quotas` WHERE `ID`='$ID'"));
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE webfilter_quotas SET `enabled`='$enabled' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_GET["delete-js"];


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM `webfilter_quotas` WHERE `ID`='$ID'"));
	$text=$tpl->javascript_parse_text("{delete} {rule} $ID - {$ligne["groupname"]} ?");

	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#SQUID_ARTICA_QUOTA_RULES').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_quotas WHERE ID='$ID'"));
		$title=$ligne["rulename"];
	}else{
		$title=$tpl->javascript_parse_text("{new_rule}");
	}
	
	$html="YahooWin3('940','$page?rule-tab=yes&ID=$ID','$title')";
	echo $html;
}

function rule_tab(){
	$page=CurrentPageName();
	$fontsize="font-size:18px;";
	$ID=$_GET["ID"];
	$tpl=new templates();

	$array["rule-popup"]="{rule}";
	if($ID>0){
		$array["rule-categories"]="{categories}";
		$array["rule-groups"]="{groups2}";
	}
	
	while (list ($index, $ligne) = each ($array) ){
		
		
		if($index=="rule-categories"){
			$html[]= $tpl->_ENGINE_parse_body("
			<li style='$fontsize'>
					<a href=\"dansguardian2.edit.php?blacklist=yes&RULEID=&ID=&modeblk=0&QuotaID=$ID&t={$_GET["t"]}&main_filter_rule_edit=yes\">
					<span>$ligne</span></a>
			</li>\n");
			continue;
		}
		
		
		if($index=="rule-groups"){
			$html[]= $tpl->_ENGINE_parse_body("
					<li style='$fontsize'>
					<a href=\"dansguardian2.edit.php?groups=&ID=&QuotaID=$ID\">
					<span>$ligne</span></a>
					</li>\n");
			continue;
		}
		
		
		https://192.168.1.225:9000/
		
		$html[]="<li><a href=\"$page?$index=yes&ID=$ID\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		
		
	}


	echo build_artica_tabs($html,'main_quota_rule_'.$ID);

}

function rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$members=$tpl->_ENGINE_parse_body("{members}");
	$categories=$tpl->_ENGINE_parse_body("{categories}");
	$title=$tpl->javascript_parse_text("{quotas}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$newrule=$tpl->javascript_parse_text("{new_rule}");
	$limit=$tpl->javascript_parse_text("{limit}");
	$compile_rules=$tpl->javascript_parse_text("{compile_rules}");
	$title=$tpl->javascript_parse_text("{quota_rules_by_categories_members}");
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilter_quotas")){$q->CheckTables();}
	
	$compile_bt="{name: '<strong style=font-size:16px;font-weight:bold>$compile_rules</strong>', bclass: 'Reconf', onpress : compile$t},";
	
	$buttons="
	buttons : [
		{name: '<strong style=font-size:16px;font-weight:bold>$newrule</strong>', bclass: 'add', onpress : NewRule$t},$compile_bt
	],";
	
	
$html="
<table class='SQUID_ARTICA_QUOTA_RULES' style='display: none' id='SQUID_ARTICA_QUOTA_RULES' style='width:100%'></table>
<script>
	$(document).ready(function(){
		$('#SQUID_ARTICA_QUOTA_RULES').flexigrid({
		url: '$page?rule-items-list=yes',
		dataType: 'json',
		colModel : [
		{display: '$rulename', name : 'groupname', width : 515, sortable : false, align: 'left'},
		{display: '$members', name : 'members', width : 90, sortable : false, align: 'center'},
		{display: '$categories', name : 'categories', width :90, sortable : true, align: 'center'},
		{display: '$limit', name : 'limit', width : 384, sortable : false, align: 'left'},
		
		{display: '&nbsp;', name : 'enabled', width : 35, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 78, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$members', name : 'members'},
		],
		sortname: 'zOrder',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
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
	
function NewRule$t(){
	Loadjs('$page?rule-js=yes&ID=0');
}

function compile$t(){
	Loadjs('squid.artica-quotas.progress.php');
}
	
function FlexReloadRulesRewriteItems(){
	$('#flexRT$t').flexReload();
}
	
	
	
	</script>
	
	";
	echo $html;
	
	}
	
function COUNTDEGBLKS($ruleid){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(ID) as tcount FROM webfilters_quotas_blks WHERE webfilter_id='$ruleid' AND modeblk=0" ;
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
	$C=$ligne["tcount"];
	return $C;
}	
function COUNTDEGROUPES($ruleid){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(ID) as tcount FROM webfilter_assoc_quota_groups WHERE webfilter_id='$ruleid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
	return $ligne["tcount"];
}
	
function rule_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$search='%';
	$table="webfilter_quotas";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("no data"); }
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
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
	
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		
		$Timez[0]=$tpl->javascript_parse_text("{by_hour}");
		$Timez[1]=$tpl->javascript_parse_text("{by_hour}");
		$Timez[2]=$tpl->javascript_parse_text("{by_day}");
		$Timez[3]=$tpl->javascript_parse_text("{by_week}");
		$all_text=$tpl->javascript_parse_text("{all}");
		$only_size=$tpl->javascript_parse_text("{only_size}");
	
		if(!$q->ok){json_error_show($q->mysql_error);}
		if(mysql_num_rows($results)==0){json_error_show("no data");}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$md5=md5($ligne["ID"]);
			$color="black";
			$ligne["rulename"]=utf8_encode($ligne["rulename"]);
			$delete=imgsimple("delete-32.png","","Loadjs('$MyPage?delete-js={$ligne["ID"]}')");
			$enable=Field_checkbox($md5,1,$ligne["enabled"],"Loadjs('$MyPage?enable-js={$ligne["ID"]}')");
			$js="Loadjs('$MyPage?rule-js=yes&ID={$ligne["ID"]}');";
			if($ligne["enabled"]==0){$color="#9A9A9A";}
			$CountOfCategories=COUNTDEGBLKS($ligne["ID"]);
			$CountOfMembers=COUNTDEGROUPES($ligne["ID"]);
			
			$Quota="{$ligne["quotasize"]}MB ".$Timez[$ligne["quotaPeriod"]];
			
			if(intval($ligne["AllSystems"])==1){$CountOfMembers=$all_text;}
			if($CountOfCategories==0){$CountOfCategories=$all_text;}
			
			
			
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" 
							style='font-size:22px;text-decoration:underline;color:$color'>{$ligne["groupname"]}</a>",
							"<span style='font-size:22px;color:$color'>$CountOfMembers</span>",
							"<span style='font-size:22px;color:$color'>$CountOfCategories</span>",
							"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
							style='font-size:22px;text-decoration:underline;color:$color'>$Quota</a>",
							
							$enable,"<center>$delete</center>" )
			);
		}
	
	
		echo json_encode($data);
	
	}	
	

	
function rule_popup(){
		$ID=intval($_GET["ID"]);
		$tpl=new templates();
		$page=CurrentPageName();
		$fields_size=22;
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$t=time();
		$bt="{add}";
		if($ID>0){$bt="{apply}";}
	
		
		$Timez[1]="{by_hour}";
		$Timez[2]="{by_day}";
		$Timez[3]="{by_week}";

		
	
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_quotas WHERE ID='$ID'"));
		if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
		
		$Timez[0]="{by_hour}";
		if(!is_numeric($ligne["quotasize"])){$ligne["quotasize"]=250;}
		if(!is_numeric($ligne["quotaPeriod"])){$ligne["quotaPeriod"]=1;}
		if($ligne["groupname"]==null){
				$ligne["groupname"]=$tpl->javascript_parse_text("{new_rule}");
				$ligne["enabled"]=1;
				$ligne["zOrder"]=0;
		}
		
		$html[]="<div style='width:98%;font-size:28px;margin-bottom:20px'>{rule}: ({$ID}) {$ligne["groupname"]}</div>";
		$html[]="<div style='width:98%' class=form>";
		$html[]="<table style='width:100%'>";
		$html[]=Field_text_table("groupname-$t","{rulename}",$ligne["groupname"],$fields_size,null,450);
		$html[]=Field_checkbox_table("enabled-$t", "{enabled}",$ligne["enabled"],$fields_size,null,"CheckEnabled$t()");
		$html[]=Field_text_table("zorder-$t","{order}",$ligne["zOrder"],$fields_size,null,110);
		$html[]=Field_text_table("quotasize-$t","{max_size} MB",$ligne["quotasize"],$fields_size,null,110);
		$html[]=Field_list_table("quotaPeriod-$t","{period}",$ligne["quotaPeriod"],$fields_size,$Timez,null,450);
		$html[]=Field_checkbox_table("AllSystems-$t", "{AllSystems}",$ligne["AllSystems"],$fields_size,null,"blur()");
		$html[]=Field_checkbox_table("UseExternalWebPage-$t", "{UseExternalWebPage}",$ligne["UseExternalWebPage"],$fields_size,null,"UnCheckUseExternalWebPage$t()");
		$html[]=Field_text_table("ExternalWebPage-$t","{ExternalWebPage}",$ligne["ExternalWebPage"],$fields_size,null,450);
		
		$html[]=Field_button_table_autonome($bt,"Submit$t",30);
		$html[]="</table>";
		$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SQUID_ARTICA_QUOTA_RULES').flexReload();
	var ID='$ID';
	if(ID==0){ YahooWin3Hide();return;}
	RefreshTab('main_quota_rule_$ID');
}
	

	
function UnCheckUseExternalWebPage$t(){
	if(!document.getElementById('enabled-$t').checked){return;}
	if(document.getElementById('UseExternalWebPage-$t').checked){
		document.getElementById('ExternalWebPage-$t').disabled=false;
	}else{
		document.getElementById('ExternalWebPage-$t').disabled=true;
	}
}

function CheckEnabled$t(){
	document.getElementById('ExternalWebPage-$t').disabled=true;
	document.getElementById('UseExternalWebPage-$t').disabled=true;
	document.getElementById('groupname-$t').disabled=true;
	document.getElementById('zorder-$t').disabled=true;
	document.getElementById('quotasize-$t').disabled=true;
	document.getElementById('quotaPeriod-$t').disabled=true;
	document.getElementById('AllSystems-$t').disabled=true;
	if(document.getElementById('enabled-$t').checked){
		document.getElementById('ExternalWebPage-$t').disabled=false;
		document.getElementById('UseExternalWebPage-$t').disabled=false;
		document.getElementById('groupname-$t').disabled=false;
		document.getElementById('quotasize-$t').disabled=false;
		document.getElementById('quotaPeriod-$t').disabled=false;
		document.getElementById('AllSystems-$t').disabled=false;
		document.getElementById('zorder-$t').disabled=false;
	}
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('groupname',encodeURIComponent(document.getElementById('groupname-$t').value));
	XHR.appendData('quotasize',document.getElementById('quotasize-$t').value);
	XHR.appendData('quotaPeriod',document.getElementById('quotaPeriod-$t').value);
	XHR.appendData('ExternalWebPage',encodeURIComponent(document.getElementById('ExternalWebPage-$t').value));
	XHR.appendData('zorder',document.getElementById('zorder-$t').value);
	if(document.getElementById('AllSystems-$t').checked){XHR.appendData('AllSystems','1');}else{XHR.appendData('AllSystems','0');}
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled','1');}else{XHR.appendData('enabled','0');}
	if(document.getElementById('UseExternalWebPage-$t').checked){XHR.appendData('UseExternalWebPage','1');}else{XHR.appendData('UseExternalWebPage','0');}
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	
	
	UnCheckUseExternalWebPage$t();
	CheckEnabled$t();
</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}
	
	
function rule_save(){
	$ID=intval($_POST["ID"]);
	$groupname=url_decode_special_tool($_POST["groupname"]);
	$_POST["ExternalWebPage"]=url_decode_special_tool($_POST["ExternalWebPage"]);
	if($ID==0){
		$sql="INSERT IGNORE INTO webfilter_quotas (groupname,enabled,quotasize,quotaPeriod,ExternalWebPage,zOrder,AllSystems,UseExternalWebPage)
		VALUES('$groupname','1','{$_POST["quotasize"]}','{$_POST["quotaPeriod"]}','{$_POST["ExternalWebPage"]}','{$_POST["zOrder"]}','{$_POST["AllSystems"]}','{$_POST["UseExternalWebPage"]}')";	
	}else{
		$sql="UPDATE webfilter_quotas SET 
				enabled='{$_POST["enabled"]}',
				groupname='$groupname',
				quotasize='{$_POST["quotasize"]}',
				quotaPeriod='{$_POST["quotaPeriod"]}',
				ExternalWebPage='{$_POST["ExternalWebPage"]}',
				AllSystems='{$_POST["AllSystems"]}',
				zOrder='{$_POST["zorder"]}',
				UseExternalWebPage='{$_POST["UseExternalWebPage"]}'
				WHERE ID=$ID";
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function rule_delete(){
	$ID=$_POST["delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_quota_groups WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM webfilters_quotas_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM webfilter_quotas WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}



