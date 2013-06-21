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
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["tab-gpid"])){rule_page();exit;}
if(isset($_GET["table-gpid-search"])){rule_search();exit;}
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
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$params=unserialize(base64_decode($ligne2["params"]));
	if($id==0){
		$ligne["duration"]=$params["duration"];
	}
	
	
	$t=time();
	
	
	if(!is_numeric($gpid)){$gpid=0;}
	$ligne["description"]=stripslashes($ligne["description"]);
	if($id>0){$buttonname="{apply}";}
	
	$durations[0]="{unlimited}";
	$durations[5]="05 {minutes}";
	$durations[10]="10 {minutes}";
	$durations[15]="15 {minutes}";
	$durations[30]="30 {minutes}";
	$durations[60]="1 {hour}";
	$durations[120]="2 {hours}";
	$durations[240]="4 {hours}";
	$durations[480]="8 {hours}";
	$durations[720]="12 {hours}";
	$durations[960]="16 {hours}";
	$durations[1440]="1 {day}";
	$durations[2880]="2 {days}";
	$durations[5760]="4 {days}";
	$durations[10080]="1 {week}";
	$durations[20160]="2 {weeks}";
	$durations[43200]="1 {month}";	
	
	
	
	$boot=new boostrap_form();
	$boot->set_hidden("SaveRule", "yes");
	$boot->set_hidden("gpid", $gpid);
	$boot->set_hidden("ruleid", $id);
	$boot->set_formdescription("{dynaacl_howto}");
	$boot->set_list("type", "{type}", $q->acl_GroupTypeDynamic,$ligne["type"]);
	$boot->set_field("value", "{value}", $ligne["value"],array("MANDATORY"=>true));
	
	if($params["allow_duration"]==1){
		$boot->set_list("duration", "{time_duration}", $durations,$ligne["duration"]);
	}
	
	$boot->set_field("description", "{description}", utf8_encode($ligne["description"]));
	$boot->set_button($buttonname);
	$boot->set_RefreshSearchs();
	$html=$boot->Compile();
	
	echo $tpl->_ENGINE_parse_body($html);
}


function ruleid_save(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$gpid=$_POST["gpid"];
	$ruleid=$_POST["ruleid"];
	
	if($_POST["description"]==null){
		$_POST["description"]=$tpl->javascript_parse_text("{$q->acl_GroupTypeDynamic[$_POST["type"]]} = {$_POST["value"]}");
	}
	
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "maxtime")){
		$q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `maxtime` INT( 100 ) NOT NULL ,
					ADD INDEX ( `maxtime` )");
	}
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "duration")){
		$q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `duration` INT( 100 ) NOT NULL ,
					ADD INDEX ( `duration` )");
	}
	
	if(!$q->TABLE_EXISTS("webfilter_aclsdynamic")){$q->CheckTables();}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$tpl=new templates();
	$params=unserialize(base64_decode($ligne["params"]));
	
	$finaltime=0;
	$duration=0;
	if(isset($_POST["duration"])){
		if($params["allow_duration"]==1){
			if($_POST["duration"]>0){
				$duration=$_POST["duration"];
				$finaltime = strtotime("+{$_POST["duration"]} minutes", time());
			}
		}
	}
	
	
	if($params["allow_duration"]==0){
		if($params["duration"]>0){
			$duration=$params["duration"];
			$finaltime = strtotime("+{$params["duration"]} minutes", time());
		}
	}
	$q=new mysql_squid_builder();
	$uid=mysql_escape_string($_SESSION["uid"]);
	$_POST["value"]=url_decode_special_tool($_POST["value"]);
	if($ruleid>0){$logtype="{update2}";}else{$logtype="{add}";}
	
	$sql="INSERT IGNORE INTO webfilter_aclsdynamic (`gpid`,`type`,`value`,`description`,`who`,`maxtime`,`duration`) 
	VALUES ('$gpid','{$_POST["type"]}','{$_POST["value"]}','{$_POST["description"]}',
	'$uid','{$finaltime}','$duration')";
	
	$logtype=$logtype." {$q->acl_GroupTypeDynamic[$ligne["type"]]} {$_POST["value"]} {$_POST["description"]}";
	$zdate=date("Y-m-d H:i:s");
	$sqllogs="INSERT IGNORE INTO webfilter_aclsdynlogs(`gpid`,`zDate` ,`events` ,`who`)
	VALUES('$gpid','$zdate','$logtype','$uid');";
	$q->QUERY_SQL($sqllogs);
	
	if($ruleid>0){
		$sql="UPDATE webfilter_aclsdynamic 
		SET `type`='{$_POST["type"]}',
		`value`='{$_POST["value"]}',
		`description`='{$_POST["description"]}',
		`maxtime`='$finaltime',
		`duration`='$duration'
		WHERE ID=$ruleid
		";
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
	notifyRemoteProxy();

}
function ruleid_enable(){
	$q=new mysql_squid_builder();
	if($_POST["enabled"]==1){$action="{enable}";}
	if($_POST["enabled"]==0){$action="{disable}";}
	
	$uid=mysql_escape_string($_SESSION["uid"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE ID='{$_POST["enable_id"]}'"));
	$gpid=$ligne["gpid"];
	$logtype=mysql_escape_string("$action {$q->acl_GroupTypeDynamic[$ligne["type"]]} {$ligne["value"]} {$ligne["description"]}");
	$zdate=date("Y-m-d H:i:s");
	$sqllogs="INSERT IGNORE INTO webfilter_aclsdynlogs(`gpid`,`zDate` ,`events` ,`who`)
	VALUES('$gpid','$zdate','$logtype','$uid');";
	$q->QUERY_SQL($sqllogs);
	
	
	$sql="UPDATE webfilter_aclsdynamic SET `enabled`='{$_POST["enabled"]}',`who`='{$_SESSION["uid"]}' 
	WHERE ID={$_POST["enable_id"]}";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	notifyRemoteProxy();
}
function ruleid_delete(){
	$id=$_POST["delete-id"];
	$q=new mysql_squid_builder();
	
	$uid=mysql_escape_string($_SESSION["uid"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE ID='$id'"));
	
	$gpid=$ligne["gpid"];
	$logtype=mysql_escape_string("{delete} {$q->acl_GroupTypeDynamic[$ligne["type"]]} {$ligne["value"]} {$ligne["description"]}");
	$zdate=date("Y-m-d H:i:s");
	$sqllogs="INSERT IGNORE INTO webfilter_aclsdynlogs(`gpid`,`zDate` ,`events` ,`who`)
	VALUES('$gpid','$zdate','$logtype','$uid');";
	$q->QUERY_SQL($sqllogs);	
	
	$sql="DELETE FROM webfilter_aclsdynamic WHERE ID='$id'";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
	notifyRemoteProxy();

	
}

function notifyRemoteProxy(){
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynlogs WHERE zDate<DATE_SUB(NOW(),INTERVAL 15 DAY)");
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
		LoadAjax('left-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function tabs(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();	
	$arrayACLS=$_SESSION["SQUID_DYNAMIC_ACLS"];
	if(!is_array($arrayACLS)){
		$miniadm=new miniadm();
		$miniadm->squid_load_dynamic_acls(true);
		$arrayACLS=$_SESSION["SQUID_DYNAMIC_ACLS"];
	}
	$q=new mysql_squid_builder();
	
	while (list ($gpid, $val) = each ($arrayACLS) ){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
		$array[$ligne["GroupName"]]="$page?tab-gpid=$gpid";
		
	}
	
	echo $boot->build_tab($array);
}

function rule_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$boot=new boostrap_form();
	if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"][$_GET["tab-gpid"]])){die();}
	$tab_gpid="{$_GET["tab-gpid"]}";
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID={$_GET["tab-gpid"]}"));
	$title=$tpl->_ENGINE_parse_body("{dynamic_acls_newbee}::{$ligne["GroupName"]}");	
	

	$buttonAdd=button($new_rule, "Loadjs('$page?rule-id-js=0&t=$t&gpid={$_GET["tab-gpid"]}');",16);
	$OPTIONS["EXPLAIN"]="<strong>{$title}</strong><br>";
	$OPTIONS["BUTTONS"][]=$buttonAdd;
	$html=$boot->SearchFormGen("value,description","table-gpid-search","&gpid=$tab_gpid",$OPTIONS);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_search(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$bywhoAcls=true;
	if(!$users->AsDansGuardianAdministrator){
		$FORCE_FILTER=" AND `who`='{$_SESSION["uid"]}'";
		$bywhoAcls=false;
	}
	$t=time();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$value=$tpl->_ENGINE_parse_body("{value}");
	$gpid=$_GET["gpid"];
	$searchstring=string_to_flexquery("table-gpid-search");
	$sql="SELECT * FROM (SELECT * FROM webfilter_aclsdynamic WHERE gpid=$gpid{$FORCE_FILTER}) as t WHERE 1 $searchstring";
	
	$durations[0]="{unlimited}";
	$durations[5]="05 {minutes}";
	$durations[10]="10 {minutes}";
	$durations[15]="15 {minutes}";
	$durations[30]="30 {minutes}";
	$durations[60]="1 {hour}";
	$durations[120]="2 {hours}";
	$durations[240]="4 {hours}";
	$durations[480]="8 {hours}";
	$durations[720]="12 {hours}";
	$durations[960]="16 {hours}";
	$durations[1440]="1 {day}";
	$durations[2880]="2 {days}";
	$durations[5760]="4 {days}";
	$durations[10080]="1 {week}";
	$durations[20160]="2 {weeks}";
	$durations[43200]="1 {month}";	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	
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
		$settings_js=$boot->trswitch("Loadjs('$page?rule-id-js={$ligne["ID"]}&t=$t');");
		$prctxt=null;
		$urljsSIT="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$settings_js\"
		style='font-size:16px;text-decoration:underline;color:$color'>";
		if($ligne["enabled"]==0){$color="#CCCCCC";}
		$ligne["description"]=utf8_encode($ligne["description"]);
	
		$enabled=Field_checkbox("enable_{$ligne["ID"]}", 1,$ligne["enabled"],"DynAclEnable('{$ligne["ID"]}')");
		$ligne["who"]=str_replace("-100", "SuperAdmin", $ligne["who"]);
		if($ligne["who"]<>null){$ligne["who"]="By:{$ligne["who"]}";}
		
		$classTR=null;
		$type=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}");
		
		if($bywhoAcls){
			$bywhoTR="<td width=1% nowrap $settings_js style='color:$color'><i class='icon-user'></i> {$ligne["who"]}</td>";
			
		}
		$duration=null;
		$finish=null;
		
		if($ligne["duration"]>0){
			if($ligne["maxtime"]>time()){
				$finish=distanceOfTimeInWords(time(),$ligne["maxtime"]);
			}
			$duration="&nbsp;<span style='font-weight:bold;font-size:12px'><i>{$durations[$ligne["duration"]]} ({delete}: {$finish})</i></span>";
			
		}
		$duration=$tpl->_ENGINE_parse_body($duration);
		$tr[]="
			<tr class='$classTR' id='id{$ligne["ID"]}'>	
			<td width=1% nowrap $settings_js style='color:$color'><i class='icon-file'></i> $ID_FIELD</td>
			<td width=1% nowrap $settings_js style='color:$color'><i class='icon-question-sign'></i> $type</td>
			<td width=1% nowrap $settings_js style='color:$color'><i class='icon-user'></i> {$ligne["value"]}</td>
			<td width=70% $settings_js style='color:$color'><i class='icon-comment'></i> {$ligne["description"]}$duration</td>
			$bywhoTR
			<td width=1%>$enabled</td>
			<td width=1%>$delete</td>
		</tr>";

	}
	
	if($bywhoAcls){
		$bywhoTR="<th>{member}</th>";
			
	}	
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>ID</th>
			<th>{type}</th>
			<th>{value}</th>
			<th>&nbsp;</th>
			$bywhoTR
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)." </tbody>
			</table>
					
<script>
	var mem$t='';
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
				
		}
		var DynAclDelete_$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#id'+mem$t).remove();
		}
</script>										
";	
	
	
	
	
}

