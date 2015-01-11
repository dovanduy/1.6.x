<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
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
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["timerules-list"])){timerules_list();exit;}
if(isset($_GET["EditTimeRule-js"])){EditTimeRule_js();exit;}
if(isset($_GET["EditTimeRule-popup"])){EditTimeRule_tabs();exit;}
if(isset($_GET["EditTimeRule-time"])){EditTimeRule_popup();exit;}
if(isset($_POST["EditTimeRule"])){EditTimeRule_save();exit;}
if(isset($_POST["DeleteTimeRule"])){EditTimeRule_delete();exit;}
if(isset($_POST["EnbleTimeRule"])){EditTimeRule_enable();exit;}


page();



function EditTimeRule_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$RULEID=$_GET["RULEID"];
	$title="{edit_time_rule}:$ID";
	if($ID<0){$title="{new_time_rule}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin2(646,'$page?EditTimeRule-popup=yes&ID=$ID&RULEID=$RULEID','$title')";
	echo $html;
}

function EditTimeRule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	
	$RULEID=$_GET["RULEID"];	
	
	if($GLOBALS["VERBOSE"]){echo "RuleID=$RULEID<br>\n";}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_dtimes_rules WHERE ID='$ID'"));
	$TimeSpace=unserialize($ligne["TimeCode"]);
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	$cron=new cron_macros();
	$buttonname="{apply}";
	if($ID<1){$buttonname="{add}";}
	
	$t=time();
	while (list ($num, $val) = each ($days) ){
		
		$jsjs[]="if(document.getElementById('day_{$num}').checked){ XHR.appendData('day_{$num}',1);}else{ XHR.appendData('day_{$num}',0);}";
		
		
		$dd=$dd."
		<tr>
		<td width=1%>". Field_checkbox("day_{$num}",1,$TimeSpace["DAYS"][$num])."</td>
		<td width=99% class=legend style='font-size:14px' align='left'>{{$val}}</td>
		</tr>
		";
		
	}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__."RULEID:$RULEID<br>\n";}
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{rulename}:</td>
		<td>". Field_text("TimeName",utf8_encode($ligne["TimeName"]),"font-size:14px;width:350px")."</td>
	</tr>
	
	
	<tr>
		<td style='width:50%' valign='top'>
			<table style='width:99%'>
				<tbody>
					$dd
				</tbody>
			</table>
		</td>
		<td style='width:50%' valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourBegin}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"BEGINH",$TimeSpace["BEGINH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"BEGINM",$TimeSpace["BEGINM"],null,null,0,"font-size:14px")."M</td>
					</tr>
					<tr><td colspan=3>&nbsp;</td></tr>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourEnd}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"ENDH",$TimeSpace["ENDH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"ENDM",$TimeSpace["ENDM"],null,null,0,"font-size:14px")."M</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "TimeSpaceDansTimes()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_TimeSpaceDansTimes= function (obj) {
		var res=obj.responseText;
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		YahooWin2Hide();
	}
	
	function TimeSpaceDansTimes(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeName', document.getElementById('TimeName').value);
		      XHR.appendData('EditTimeRule', 'yes');
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('RULEID', '$RULEID');
		      ". @implode("\n", $jsjs)."
		      XHR.appendData('BEGINH', document.getElementById('BEGINH').value);
		      XHR.appendData('BEGINM', document.getElementById('BEGINM').value);
		      XHR.appendData('ENDH', document.getElementById('ENDH').value);
		      XHR.appendData('ENDM', document.getElementById('ENDM').value);		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_TimeSpaceDansTimes);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function EditTimeRule_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$RULEID=$_POST["RULEID"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_dtimes_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilters_dtimes_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");	
}

function EditTimeRule_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$RULEID=$_POST["RULEID"];	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_dtimes_rules WHERE ID='$ID'"));
	$TimeSpace=unserialize($ligne["TimeCode"]);	
	while (list ($num, $val) = each ($_POST) ){
		if(preg_match("#day_([0-9]+)#", $num,$re)){
			$TimeSpace["DAYS"][$re[1]]=$val;
		}
		
	}
	
	$TimeSpace["BEGINH"]=$_POST["BEGINH"];
	$TimeSpace["BEGINM"]=$_POST["BEGINM"];
	$TimeSpace["ENDH"]=$_POST["ENDH"];
	$TimeSpace["ENDM"]=$_POST["ENDM"];
	$TimeSpaceFinal=serialize($TimeSpace);
	
	$sqladd="INSERT INTO webfilters_dtimes_rules (TimeName,TimeCode,ruleid,enabled) 
	VALUES ('{$_POST["TimeName"]}','$TimeSpaceFinal','$RULEID','1');";
	
	$sql="UPDATE webfilters_dtimes_rules SET TimeName='{$_POST["TimeName"]}',TimeCode='$TimeSpaceFinal' WHERE ID='$ID'";

	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");	

}
	
	
	
function EditTimeRule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$RULEID=$_GET["RULEID"];	

	$array["time"]='{time}';
	if($ID>0){
		$array["blacklist"]='{blacklist}';
		$array["whitelist"]='{whitelist}';
	}
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="blacklist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'>
			<a href=\"dansguardian2.edit.php?blacklist=yes&modeblk=0&RULEID=$ID&TimeID=$ID&ID=0\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'>
			<a href=\"dansguardian2.edit.php?blacklist=yes&modeblk=1&RULEID=$ID&TimeID=$ID&ID=0\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
//https://192.168.1.197:9000/dansguardian2.edit.php?blacklist=yes&RULEID=0&ID=0&modeblk=0		
//https://192.168.1.197:9000/dansguardian2.edit.php?blacklist-list=yes&RULEID=0&modeblk=0&group=&page=1&qtype=categorykey&query=&rp=15&sortname=categorykey&sortorder=asc
		if($num=="expressionslist"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.expressionslist.php?RULEID={$_GET["ID"]}&ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?EditTimeRule-$num=yes&RULEID=$RULEID&ID=$ID\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "$menus
	<div id=main_content_rule_editdTimerule style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_content_rule_editdTimerule').tabs();
			
			
			});
		</script>";	
	
	
}

function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$time=$tpl->_ENGINE_parse_body("{time}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_time_rule=$tpl->_ENGINE_parse_body("{new_time_rule}");
	$t=time();		
	$RULEID=$ID;
	$html=$tpl->_ENGINE_parse_body("<div class=text-info style='font-size:13px'>{dansguardian_timelimit_explain}</div>")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var TimeRuleIDTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?timerules-list=yes&RULEID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$description', name : 'TimeName', width : 159, sortable : true, align: 'left'},
		{display: '$time', name : 'TimeText', width : 586, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_time_rule', bclass: 'add', onpress : AddTimeRule},
		],	
	searchitems : [
		{display: '$description', name : 'description'},
		],
	sortname: 'TimeName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 875,
	height: 250,
	singleSelect: true
	
	});   
});
function AddTimeRule() {
	Loadjs('$page?EditTimeRule-js=yes&RULEID=$ID&ID=-1');
	
}	


	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	var x_EnableDisableTimeRule= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		
	}	
	
	function TimeRuleDansDelete(ID){
		TimeRuleIDTemp=ID;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteTimeRule', 'yes');
		XHR.appendData('ID', ID);
		XHR.appendData('RULEID', '$RULEID');
		XHR.sendAndLoad('$page', 'POST',x_TimeRuleDansDelete);  		
	}

	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	function EnableDisableTimeRule(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnbleTimeRule', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('ruleTime_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableTimeRule);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function EditTimeRule_enable(){
$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_dtimes_rules SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");	
}

function timerules_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="webfilters_dtimes_rules";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No rule...",1);}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE ruleid='$RULEID' $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE ruleid='$RULEID' $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	
	
	$sql="SELECT *  FROM `$table` WHERE ruleid='$RULEID' $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("ruleTime_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableTimeRule('{$ligne['ID']}')");
		writelogs($ligne['TimeName'],__FUNCTION__,__FILE__,__LINE__);
		$ligne['TimeName']=utf8_encode($ligne['TimeName']);
		$TimeSpace=unserialize($ligne["TimeCode"]);
		$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
		$f=array();
		while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	
			if($num==array()){continue;}
			if(!isset($days[$num])){continue;}
			if($days[$num]==array()){continue;}
			if($val<>1){continue;}
			$f[]= "{{$days[$num]}}";
		}	
		
		
		if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
		if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
		if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
		if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}

		
		$text=$tpl->_ENGINE_parse_body("{from} {$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]} {to} {$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]} (".@implode(", ", $f).")");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","TimeRuleDansDelete('{$ligne['ID']}')");
		
		$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks 
		WHERE webfilter_id={$ligne["ID"]} AND modeblk=0"));
		$blacklist=$ligneTOT["tcount"];
		
		$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks 
		WHERE webfilter_id={$ligne["ID"]} AND modeblk=1"));
		$whitelist=$ligneTOT["tcount"];	

		$text=$text.$tpl->_ENGINE_parse_body("<div><i>{blacklist}:<b>$blacklist</b> {whitelist}:<b>$whitelist</b></div>");
		
	$data['rows'][] = array(
		'id' => "time{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?EditTimeRule-js=yes&RULEID={$ligne['ruleid']}&ID={$ligne['ID']}');\" 
		style='font-size:16px;text-decoration:underline'>{$ligne['TimeName']}</span>", $text,$disable,$delete)
		);
	}
	
	
echo json_encode($data);	
	
	
}



