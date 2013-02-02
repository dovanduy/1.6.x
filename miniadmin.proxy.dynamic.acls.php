<?php
session_start();

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_GET["ByJs"])){main_js();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["tab-gpid"])){table();exit;}
if(isset($_GET["ruleid"])){ruleid_popup();exit;}
if(isset($_POST["SaveRule"])){ruleid_save();exit;}
if(isset($_POST["enable_id"])){ruleid_enable();exit;}
if(isset($_POST["delete-id"])){ruleid_delete();exit;}

if(isset($_GET["rule-id-js"])){ruleid_js();exit;}

main_page();

function main_js(){
	if(!isset($_SESSION["uid"])){
		echo "alert('No Session!!!');";
		die();
	}
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die();}
	$gpid=$_GET["ByJs"];
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$dynamic_acls_newbee=$tpl->javascript_parse_text("{dynamic_acls_newbee}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	$html="YahooWin4('890','$page?tab-gpid=$gpid','$dynamic_acls_newbee::{$ligne["GroupName"]}')";
	echo $html;
	
}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function ruleid_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$id=$_GET["rule-id-js"];
	
	if($id==0){
		$title=$tpl->_ENGINE_parse_body("{new_rule}");
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT type,value FROM webfilter_aclsdynamic WHERE `ID`='$id'"));
		$title=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}::{$ligne["value"]}");
	}
	
	echo "YahooWin5('600','$page?ruleid=$id&t={$_GET["t"]}&gpid={$_GET["gpid"]}','$title')";
	
	
}




function ruleid_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$id=$_GET["ruleid"];
	if(!is_numeric($id)){$id=0;}
	$gpid=$_GET["gpid"];
	$buttonname="{add}";
	if($id>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE `ID`='$id'"));
		$buttonname="{apply}";
		$gpid=$ligne["gpid"];
		
	}
	$t=time();
	
	
	if(!is_numeric($gpid)){$gpid=0;}
	$ligne["description"]=stripslashes($ligne["description"]);
	if($id>0){$buttonname="{apply}";}
	
	$html="
	<center id='anim-$t'></center>
	<div class=explain style='font-size:14px'>{dynaacl_howto}</div>
	<div class=BodyContent >
	<table width=99% class=form>
	<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td>". Field_array_Hash($q->acl_GroupTypeDynamic, "type-$t",$ligne["type"],null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{value}:</td>
		<td>". Field_text("value-$t",$ligne["value"],"font-size:16px;width:80%",null,null,null,false,"CheckForm$t(event)")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{description}:</td>
		<td>". Field_text("description-$t",utf8_encode($ligne["description"]),"font-size:12px;width:80%",null,null,null,false,"CheckForm$t(event)")."</td>
	</tr>					
	<tr>
		<td colspan=2 align=right><hr>". button($buttonname, "Save$t()","18")."<td>
	</tr>		
	</table>	
	</div>	
	<script>
	
		var x_Save$t=function (obj) {
			document.getElementById('anim-$t').innerHTML='';
			var ID=$id;
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			if(ID==0){YahooWin5Hide();}
			$('#flexRT{$_GET["t"]}').flexReload();	
		}	 
	
		function Save$t(){
			var pp=encodeURIComponent(document.getElementById('value-$t').value);
			var gpid=$gpid;
			if(pp.length==0){alert('value = 0');return;}
			if(gpid==0){alert('gpid = 0');return;}
		 	var XHR = new XHRConnection();
		 	
		 	XHR.appendData('SaveRule',$id);
	 		XHR.appendData('ruelid','$id');
	 		XHR.appendData('gpid','$gpid');
	 		XHR.appendData('type',document.getElementById('type-$t').value);
	 		XHR.appendData('value',pp);
	 		XHR.appendData('description',document.getElementById('description-$t').value);
	 		AnimateDiv('anim-$t');
	 		XHR.sendAndLoad('$page', 'POST',x_Save$t);	

	 	}
	 	
	 	function CheckForm$t(e){
	 		if(checkEnter(e)){Save$t();}
	 	}
	 	
	 	
	</script>			
				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function ruleid_save(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	if($_POST["description"]==null){
		$_POST["description"]=$tpl->javascript_parse_text("{$q->acl_GroupTypeDynamic[$_POST["type"]]} = {$_POST["value"]}");
	}
	$_POST["value"]=url_decode_special_tool($_POST["value"]);
	$sql="INSERT IGNORE INTO webfilter_aclsdynamic (`gpid`,`type`,`value`,`description`,`who`) 
	VALUES ('{$_POST["gpid"]}','{$_POST["type"]}','{$_POST["value"]}','{$_POST["description"]}','{$_SESSION["uid"]}')";
	
	if($_POST["ruelid"]>0){
		$sql="UPDATE webfilter_aclsdynamic 
		SET `type`='{$_POST["type"]}',
		`value`='{$_POST["value"]}',
		`description`='{$_POST["description"]}',
		`who`='{$_SESSION["uid"]}'
		WHERE ID={$_POST["ruelid"]}
		";
		
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
	notifyRemoteProxy();

}
function ruleid_enable(){
	$sql="UPDATE webfilter_aclsdynamic SET `enabled`='{$_POST["enabled"]}',`who`='{$_SESSION["uid"]}' WHERE ID={$_POST["enable_id"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	notifyRemoteProxy();
}
function ruleid_delete(){
	$id=$_POST["delete-id"];
	$sql="DELETE FROM webfilter_aclsdynamic WHERE ID='$id'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
	notifyRemoteProxy();

	
}

function notifyRemoteProxy(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==0){return;}
	include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
	$black=new blackboxes();
	$black->NotifyAll("ACLSDYN");
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$users=new usersMenus();
	
	$dynamic_acls_newbee_explain=$tpl->_ENGINE_parse_body("{dynamic_acls_newbee_explain}");
	$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["SQUID_DYNAMIC_ACLS"]), $dynamic_acls_newbee_explain);
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;|&nbsp;<a href=\"$page\" ><strong>{dynamic_acls_newbee}</strong></a>";
	if($users->AsSquidAdministrator){
		$html=$html."&nbsp;|&nbsp;<a href=\"miniadmin.proxy.php\"><strong>{APP_PROXY}</strong></a>
		";
	}
	$html=$html."
		</div>
		
		
		<H1>{dynamic_acls_newbee}</H1>
		<p>$dynamic_acls_newbee_explain</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='left-$t' class=BodyContent></div>
	
	<script>
		LoadAjax('left-$t','$page?popup=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$arrayACLS=$_SESSION["SQUID_DYNAMIC_ACLS"];
	if(!is_array($arrayACLS)){
		$miniadm=new miniadm();
		$miniadm->squid_load_dynamic_acls(true);
		$arrayACLS=$_SESSION["SQUID_DYNAMIC_ACLS"];
	}
	
	
	$q=new mysql_squid_builder();
	$fontsize="14px";
	while (list ($gpid, $val) = each ($arrayACLS) ){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
		$array[$gpid]=$ligne["GroupName"];
	}
	while (list ($num, $ligne) = each ($array) ){
	
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?tab-gpid=$num\" style='font-size:$fontsize;font-weight:normal'>
				<span>$ligne</span></a>
		</li>\n");
	}
	
	
	
	echo "
	<div id=squid_acly_dynamics style='width:99%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#squid_acly_dynamics').tabs();
			});
		</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$TB_HEIGHT=600;
	$TB_WIDTH=870;
	
	
	$t=time();
	$rule=$tpl->javascript_parse_text("{rule}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$value=$tpl->_ENGINE_parse_body("{value}");
	$execute_report_compilation_ask=$tpl->javascript_parse_text("{execute_report_compilation_ask}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID={$_GET["tab-gpid"]}"));
	$title=$tpl->_ENGINE_parse_body("{dynamic_acls_newbee}::{$ligne["GroupName"]}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'Add', onpress : NewRule$t},
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
	<script>
	var mem$t='';
	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
			url: '$page?items=yes&t=$t&gpid={$_GET["tab-gpid"]}',
			dataType: 'json',
			colModel : [
			{display: 'ID', name : 'ID', width :38, sortable : true, align: 'center'},
			{display: '$type', name : 'type', width :134, sortable : true, align: 'left'},
			{display: '$value', name : 'value', width :258, sortable : true, align: 'left'},
			{display: 'description', name : '$description', width :280, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'enabled', width :31, sortable : true, align: 'center'},
			{display: '&nbsp;', name : 'none', width :31, sortable : false, align: 'center'},
			
			
			],
			$buttons
			
			searchitems : [
			{display: '$value', name : 'value'},
			{display: '$description', name : 'description'},
			],
			sortname: 'ID',
			sortorder: 'desc',
			usepager: true,
			title: '<span id=\"title-$t\">$title</span>',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: $TB_WIDTH,
			height: $TB_HEIGHT,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]
		});
	});
	
	function ItemHelp$t(){
		s_PopUpFull('http://proxy-appliance.org/index.php?cID=332','1024','900');
	}
	
	function DynAclDelete$t(ID){
	
	}
	
	var x_RunReport= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#flexRT$t').flexReload();
	}
	
	function NewRule$t(){
		Loadjs('$page?rule-id-js=0&t=$t&gpid={$_GET["tab-gpid"]}');
	}
	
	function RunReport(ID){
		if(confirm('$execute_report_compilation_ask')){
			var XHR = new XHRConnection();
			XHR.appendData('run',ID);
			XHR.sendAndLoad('$page', 'POST',x_RunReport);
		}
	}
	
	function NewReport$t(){
		Loadjs('$page?report-js=yes&ID=0&t=$t');
	}
	
		function DynAclEnable(aclid){
			var enabled=0;
			var XHR = new XHRConnection();
		 	if(document.getElementById('enable_'+aclid).checked){enabled=1;}
	 		XHR.appendData('enabled',enabled);
	 		XHR.appendData('enable_id',aclid);
	 		XHR.sendAndLoad('$page', 'POST',DynAclEnable_$t);	
		}
		
		function DynAclDelete(id){
			mem$t=id;
			var XHR = new XHRConnection();
	 		XHR.appendData('delete-id',id);
	 		XHR.sendAndLoad('$page', 'POST',DynAclDelete_$t);				
		}
	
		var DynAclEnable_$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#flexRT$t').flexReload();	
		}
		var DynAclDelete_$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+mem$t).remove();
		}			 
	

</script>";
	
			echo $html;
	
		
	
}
function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();

	$gpid=$_GET["gpid"];
	$search='%';
	$tablemain="webfilter_aclsdynamic";
	$database="squidlogs";
	$page=1;
	$FORCE_FILTER=null;
	$table="(SELECT * FROM $tablemain WHERE gpid=$gpid) as t";
	if(!$q->TABLE_EXISTS($tablemain, $database)){
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic` (`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY , `type` INT(1) NOT NULL, `value` VARCHAR(255) NOT NULL, `enabled` INT(1) NOT NULL DEFAULT '1' , `gpid` INT(10) NOT NULL DEFAULT '0' , `description` VARCHAR(255) NOT NULL, `who` VARCHAR(128) NOT NULL, KEY `type` (`type`), KEY `value` (`value`), KEY `enabled` (`enabled`), KEY `who` (`who`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		
	}
	if(!$q->TABLE_EXISTS($tablemain, $database)){json_error_show("$tablemain doesn't exists...",1);}
	if($q->COUNT_ROWS($tablemain, $database)==0){json_error_show("No rules",1);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}

	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$color="black";
		$urljsSTAT=null;
		$delete=imgsimple("delete-24.png",null,"DynAclDelete('{$ligne["ID"]}')");
		$stats_img="statistics-32-grey.png";
		$rowsBlock_txt=null;
		$rowsFormat=null;
		$scheduled=null;
		$ID_FIELD=$ligne["ID"];
		$settings_js="Loadjs('$MyPage?rule-id-js={$ligne["ID"]}&t=$t');";
		$prctxt=null;
		$urljsSIT="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$settings_js\"
		style='font-size:16px;text-decoration:underline;color:$color'>";
		$ligne["description"]=utf8_encode($ligne["description"]);
		
		$enabled=Field_checkbox("enable_{$ligne["ID"]}", 1,$ligne["enabled"],"DynAclEnable('{$ligne["ID"]}')");
		$ligne["who"]=str_replace("-100", "SuperAdmin", $ligne["who"]);
		if($ligne["who"]<>null){$ligne["who"]="<div style='font-size:11px'>By:{$ligne["who"]}</div>";}
		
		$type=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}");
		
		$data['rows'][] = array(
				'id' => "{$ligne["ID"]}",
				'cell' => array(
						"<span style='font-size:16px;color:$color'>$urljsSIT$ID_FIELD</a></span>",
						"<span style='font-size:16px;color:$color'>$urljsSIT$type</a></span>",
						"<span style='font-size:16px;color:$color'>$urljsSIT{$ligne["value"]}</a></span>",
						"<span style='font-size:16px;color:$color'>$urljsSIT{$ligne["description"]}</a>{$ligne["who"]}</span>",
						"<span style='font-size:16px;color:$color'>$enabled</span>",
						"<span style='font-size:16px;color:$color'>$delete</span>",
				)
		);
	}


	echo json_encode($data);


}