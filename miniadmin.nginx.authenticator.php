<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class='text-error'>");ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");


if(isset($_GET["parameters"])){parameters();exit;}

if(isset($_GET["rules-section"])){rules_section();exit;}
if(isset($_GET["rules-search"])){rules_search();exit;}
if(isset($_GET["rules-js"])){rules_js();exit;}
if(isset($_GET["rules-tab"])){rules_tabs();exit;}
if(isset($_GET["rules-params"])){rules_params();exit;}
if(isset($_POST["rulename"])){rules_save_main();exit;}
if(isset($_POST["rules-delete"])){rules_delete_main();exit;}

if(isset($_GET["rules-sources-section"])){rules_sources_section();exit;}
if(isset($_GET["rules-sources-search"])){rules_sources_search();exit;}
if(isset($_GET["rules-sources-add-group-js"])){rules_sources_add_group_js();exit;}
if(isset($_GET["rules-sources-add-group-popup"])){rules_sources_add_group_popup();exit;}
if(isset($_POST["rules-sources-add-group-save"])){rules_sources_add_group_save();exit;}

if(isset($_GET["rules-sources-group-js"])){rules_sources_group_js();exit;}
if(isset($_GET["rules-sources-group-tabs"])){rules_sources_group_tabs();exit;}

if(isset($_GET["rules-sources-group-items-section"])){rules_sources_group_items_section();exit;}
if(isset($_GET["rules-sources-group-items-search"])){rules_sources_group_items_search();exit;}
if(isset($_GET["rules-sources-group-item-js"])){rules_sources_group_items_js();exit;}
if(isset($_GET["rules-sources-group-item-popup"])){rules_sources_group_items_popup();exit;}
if(isset($_POST["rules-sources-group-items-add"])){rules_sources_group_items_add();exit;}

if(isset($_GET["rules-sources-link-js"])){rules_sources_group_link_js();exit;}
if(isset($_GET["rules-sources-group-link-section"])){rules_sources_group_link_section();exit;}
if(isset($_GET["rules-sources-group-link-search"])){rules_sources_group_link_search();exit;}


if(isset($_POST["rules-sources-group-link"])){rules_sources_group_link_save();exit;}
if(isset($_POST["rules-sources-group-items-delete"])){rules_sources_group_items_delete();exit;}
if(isset($_POST["rules-sources-group-delete"])){rules_sources_group_delete();exit;}
if(isset($_POST["rules-sources-group-unlink"])){rules_sources_group_unlink();exit;}
if(isset($_POST["NginxAuthPort"])){parameters_save();exit;}






tabs();
function AdminPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}

}

function parameters(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
	if($NginxAuthPort==null){
		$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";
		$sock->SET_INFO("NginxAuthPort",$NginxAuthPort);
	}
	
	
	$title_button="{apply}";
	$title="Authenticator";
	$boot->set_formtitle($title);
	$boot->set_formdescription("{authenticator_explain_section}");
	
	
	
	$boot->set_field("NginxAuthPort","{listen_port}",$NginxAuthPort,array("ENCODE"=>true,"EXPLAIN"=>"{NginxAuthPort_explain}"));
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	echo $boot->Compile();	
	
}

function parameters_save(){
	$sock=new sockets();
	$_POST["NginxAuthPort"]=url_decode_special_tool($_POST["NginxAuthPort"]);
	$sock->SET_INFO("NginxAuthPort", $_POST["NginxAuthPort"]);
	
}

function rules_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["rules-js"];
	if(!is_numeric($ruleid)){$ruleid=0;}
	$title="{new_source}";
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM authenticator_rules WHERE ID='$ruleid'"));
		$title=$ligne["rulename"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(990,'$page?rules-tab=$ruleid','$title')";	
}

function rules_sources_group_link_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["mainrule"];
	if(!is_numeric($ruleid)){$ruleid=0;}
	$title="{link_group}";
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM authenticator_rules WHERE ID='$ruleid'"));
		$title=$ligne["rulename"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(700,'$page?rules-sources-group-link-section=yes&mainrule=$ruleid','$title')";	
}
function rules_sources_group_link_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	echo $boot->SearchFormGen("groupname","rules-sources-group-link-search","&mainrule={$_GET["mainrule"]}");
}
function rules_sources_group_link_search(){

	$table="authenticator_groups";
	
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=time();
	$ORDER=$boot->TableOrder(array("groupname"=>"ASC"));
	
	$searchstring=string_to_flexquery("rules-sources-group-link-search");
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		if($AdminPrivs){
				$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
				$link=imgsimple("arrow-right-32.png","{link}","Link$t('{$ligne["ID"]}','$md')");
		
		}
	
		$tr[]="
		<tr id='$md'>
		<td style='font-size:18px' width=99% nowrap >{$ligne["groupname"]}</td>
		<td style='font-size:18px' width=1% nowrap>$link</td>
		<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
	
	}
		$delete_text=$tpl->javascript_parse_text("{delete}");
	echo $boot->TableCompile(array(
				"groupname"=>" {groupname}",
			"link"=>null,
			"delete"=>null,
		),$tr)."
	
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('rules-sources-group-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
var xLink$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	ExecuteByClassName('SearchFunction');
}
function Link$t(ID,mem){
	mem$t=mem;
	var XHR = new XHRConnection();
	XHR.appendData('rules-sources-group-link',ID);
	XHR.appendData('rules-sources-mainrule','{$_GET["mainrule"]}');
	XHR.sendAndLoad('$page', 'POST',xLink$t);
}
	</script>
	";	
}
function rules_sources_group_link_save(){
	$mainrule=$_POST["rules-sources-mainrule"];
	$ID=$_POST["rules-sources-group-link"];
	$zmd5=md5("$ID$mainrule");
	$q=new mysql_squid_builder();
	$sql="INSERT IGNORE INTO authenticator_sourceslnk (`ruleid`,`groupid`,`zorder`,`zmd5`)
	VALUES('$mainrule','$ID','0','$zmd5');";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}


function rules_sources_group_items_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$groupid=$_GET["groupid"];
	if(!is_numeric($groupid)){$groupid=0;}
	$title="{new_item}";
	if($groupid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM authenticator_groups WHERE ID='$groupid'"));
		$rulename="::".$ligne["groupname"];
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin5(700,'$page?rules-sources-group-item-popup=yes&groupid=$groupid','$title$rulename')";
}



function rules_sources_add_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["mainrule"];
	if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
	if(!is_numeric($ruleid)){$ruleid=0;}
	$title="{new_group}";
	$rulename=null;
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM authenticator_rules WHERE ID='$ruleid'"));
		$rulename="::".$ligne["rulename"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(800,'$page?rules-sources-add-group-popup=yes&mainrule=$ruleid&groupid={$_GET["groupid"]}','$title$rulename')";	
	
}
function rules_sources_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
	$groupid=$_GET["groupid"];
	if(!is_numeric($groupid)){$groupid=0;}
	$title="{group}";
	$rulename=null;
	if($groupid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM authenticator_groups WHERE ID='$groupid'"));
		$rulename="::".$ligne["groupname"];
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin4(800,'$page?rules-sources-group-tabs=yes&groupid={$_GET["groupid"]}','$title$rulename')";

}

function rules_sources_group_items_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	$groupid=$_GET["groupid"];
	$title_button="{add}";
	$title="{new_item}";
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM authenticator_groups WHERE ID='$groupid'"));
	
	
	
	
	$explain[1]="{authenticator_explain_network_text}";
	$explain[3]="{authenticator_explain_cookie_text}";
	
	
	$title=$title."::".$ligne["groupname"];
	
	$boot->set_formtitle($title);
	$boot->set_spacerexplain("<i>{group2} ID:$groupid {group2} {type}:{$ligne["group_type"]} ({$GLOBALS["SOURCE_TYPE"][$ligne["group_type"]]})</i>");
	$boot->set_formdescription($explain[$ligne["group_type"]]);
	$boot->set_hidden("rules-sources-group-items-add", "yes");
	$boot->set_hidden("groupid", $groupid);
	$boot->set_textarea("items", "{items}", null,array("ENCODE"=>true));
	
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	if($ligne["group_type"]==0){$boot->set_form_locked();}
	$boot->set_CloseYahoo("YahooWin3");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
	
}


function rules_sources_add_group_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	
	$ID=$_GET["groupid"];
	$title_button="{add}";
	$title="{new_group}";

	
	
	if($ID>0){
		$sql="SELECT * FROM authenticator_groups WHERE ID='$ID'";
		$q=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$title_button="{apply}";
		$title=$ligne["groupname"]."&nbsp;&raquo;&raquo;&nbsp;".$GLOBALS["SOURCE_TYPE"][$ligne["group_type"]];
	}
	
	$mainrule=$_GET["mainrule"];
	if(!is_numeric($mainrule)){$mainrule=0;}
	if(!is_numeric($mainrule)){$mainrule=0;}
	

	
	$boot->set_formtitle($title);
	$boot->set_hidden("rules-sources-add-group-save", "yes");
	$boot->set_hidden("mainrule", $mainrule);
	$boot->set_hidden("groupid", $ID);
	$boot->set_field("groupname","{groupname}",$ligne["groupname"],array("ENCODE"=>true));
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	if($ID==0){
		$boot->set_list("group_type", "{groupe_type}", $GLOBALS["SOURCE_TYPE"],$ligne["group_type"]);
	}else{
		$boot->set_hidden("group_type", $ligne["group_type"]);
		
	}
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin3");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
	
}

function rules_sources_add_group_save(){
	$mainrule=$_POST["mainrule"];
	$ID=$_POST["groupid"];
	unset($_POST["mainrule"]);
	unset($_POST["groupid"]);
	unset($_POST["rules-sources-add-group-save"]);
	$table="authenticator_groups";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){$prox=new squid_reverse();}
	$_POST["groupname"]=url_decode_special_tool($_POST["groupname"]);
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE $table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO $table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if($ID==0){$ID=$q->last_id;}
	if(!$q->ok){echo $q->mysql_error;return;}
	if($mainrule>0){
		$zmd5=md5("$ID$mainrule");
		$sql="INSERT IGNORE INTO authenticator_sourceslnk (`ruleid`,`groupid`,`zorder`,`zmd5`)
		VALUES('$mainrule','$ID','0','$zmd5');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	 
	
}

function rules_sources_group_unlink(){
	$ID=$_POST["rules-sources-group-unlink"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM authenticator_sourceslnk WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
function rules_sources_group_delete(){
	$ID=$_POST["rules-sources-group-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM authenticator_sourceslnk WHERE groupid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM authenticator_items WHERE groupid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM authenticator_groups WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}


function tabs(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$error=null;
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("nginx.php?status-infos=yes")));
	if(!is_array($ARRAY["MODULES"])){$ARRAY["MODULES"]=array();}
	$COMPAT=FALSE;
	while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
		if(preg_match("#auth-request-nginx-module#", $a)){
			$COMPAT=true;
			break;
		}
	}
	
	if(!$COMPAT){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{error_http_auth_request_module}</p>");
	}
	
	$tpl=new templates();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{rules}"]="$page?rules-section=yes";
	echo $error.$boot->build_tab($array);
}
function rules_tabs(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){senderror("no privs");}
	$ruleid=$_GET["rules-tab"];
	$tpl=new templates();
	$array["{parameters}"]="$page?rules-params=$ruleid";
	if($ruleid>0){
		$array["{sources}"]="$page?rules-sources-section=yes&mainrule=$ruleid";
		$array["{authentication_sources}"]="miniadmin.nginx.authenticator.sources.php?sources-section=yes&mainrule=$ruleid";
	}
	echo $boot->build_tab($array);	
}

function rules_sources_group_tabs(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){senderror("no privs");}
	$groupid=$_GET["groupid"];
	$tpl=new templates();
	$array["{parameters}"]="$page?rules-sources-add-group-popup=yes&groupid=$groupid";
	if($groupid>0){
		$array["{items}"]="$page?rules-sources-group-items-section=yes&groupid=$groupid";
	}
	echo $boot->build_tab($array);	
	
	
	
}

function rules_sources_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_group}","Loadjs('$page?rules-sources-add-group-js=yes&mainrule={$_GET["mainrule"]}');",16);
	$EXPLAIN["BUTTONS"][]=button("{link_group}","Loadjs('$page?rules-sources-link-js=yes&mainrule={$_GET["mainrule"]}');",16);
	echo $boot->SearchFormGen("groupname","rules-sources-search","&mainrule={$_GET["mainrule"]}",$EXPLAIN);	
}

function rules_sources_group_items_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_item}","Loadjs('$page?rules-sources-group-item-js=yes&groupid={$_GET["groupid"]}');",16);
	echo $boot->SearchFormGen("pattern","rules-sources-group-items-search","&groupid={$_GET["groupid"]}",$EXPLAIN);	
}

function rules_sources_group_items_add(){
	$tpl=new templates();
	$table="authenticator_items";
	$items=explode("\n", url_decode_special_tool($_POST["items"]));
	$groupid=$_POST["groupid"];
	$t=array();
	while (list ($key, $value) = each ($items) ){
		if(trim($value)==null){continue;}
		$value=mysql_escape_string2($value);
		$t[]="('$groupid','$value')";
	}
	
	if(count($t)==0){
		echo $tpl->javascript_parse_text("{error_no_item_posted}");
		return;
		
	}	
	
	$sql="INSERT IGNORE INTO $table (`groupid`,`pattern`) VALUES ".@implode(",", $t);;
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
		
	
}


function rules_sources_group_items_search(){
	$table="authenticator_items";
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=time();
	$ORDER=$boot->TableOrder(array("pattern"=>"ASC"));
	
	$searchstring=string_to_flexquery("rules-sources-group-items-search");
	$sql="SELECT * FROM $table WHERE groupid={$_GET["groupid"]} $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		if($AdminPrivs){$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");}
	
		$tr[]="
		<tr id='$md'>
		<td style='font-size:18px' width=99% nowrap >{$ligne["pattern"]}</td>
		<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
	
	}
	$delete_text=$tpl->javascript_parse_text("{delete}");
	echo $boot->TableCompile(array(
				"pattern"=>" {items}",
			"delete"=>null,
		),$tr)."
	
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
	mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('rules-sources-group-items-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
";
}
function rules_sources_group_items_delete(){
	$ID=$_POST["rules-sources-group-items-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM authenticator_items WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function rules_params(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	$ID=$_GET["rules-params"];
	$title_button="{add}";
	$title="{new_authenticator_rule}";
	if($ID>0){
		$sql="SELECT * FROM authenticator_rules WHERE ID='$ID'";
		$q=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		$title_button="{apply}";
		$title=$ligne["rulename"];
	}
	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("ruleid", $ID);
	$boot->set_field("rulename","{rulename}",$ligne["rulename"],array("ENCODE"=>true));
	$boot->set_field("cachetime","{cachetime} ({minutes})",$ligne["cachetime"],array("TOOLTIP"=>"{authenticator_cache_time_explain}"));
	
	
	
	$boot->set_textarea("explain","{explain}",$ligne["explain"],array("ENCODE"=>true));
	
	
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}

function rules_save_main(){

	$ID=$_POST["ruleid"];
	unset($_POST["ruleid"]);
	$table="authenticator_rules";
	$q=new mysql_squid_builder();
	$_POST["explain"]=url_decode_special_tool($_POST["explain"]);
	$_POST["rulename"]=url_decode_special_tool($_POST["rulename"]);
	
	
	if(!$q->FIELD_EXISTS($table, "cachetime")){
		$q->QUERY_SQL("ALTER TABLE `$table` ADD `cachetime` INT NOT NULL DEFAULT '15'");
	}
	
	
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE $table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO $table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function rules_delete_main(){
	$ID=$_POST["rules-delete"];
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("DELETE FROM authenticator_sourceslnk WHERE ruleid='$ID'");
	// A LA FIN.
	$q->QUERY_SQL("DELETE FROM authenticator_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function rules_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_rule}","Loadjs('$page?rules-js=0');",16);
	echo $boot->SearchFormGen("rulename","rules-search",null,$EXPLAIN);
}

function rules_sources_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	$table="authenticator_sourceslnk";
	$ORDER=$boot->TableOrder(array("zorder"=>"ASC"));
	$searchstring=string_to_flexquery("rules-sources-search");
	
	$table="(
			SELECT
			authenticator_sourceslnk.ID,
			authenticator_sourceslnk.zorder,
			authenticator_sourceslnk.groupid,
			authenticator_groups.groupname,
			authenticator_groups.group_type
			FROM authenticator_sourceslnk,authenticator_groups
			WHERE authenticator_sourceslnk.ruleid='{$_GET["mainrule"]}'
			AND authenticator_sourceslnk.groupid=authenticator_groups.ID 
			) as t";
			
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	$t=time();



	while ($ligne = mysql_fetch_assoc($results)) {
	
		$edit=$boot->trswitch("Loadjs('$page?rules-sources-group-js=yes&groupid={$ligne["groupid"]}');");
		$md=md5(serialize($ligne));
		if($AdminPrivs){
			$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		}
		
		$count=0;
		if($ligne["group_type"]==0){$count="*";}
	
		$tr[]="
		<tr id='$md'>
		<td style='font-size:18px;vertical-align:middle' width=99% nowrap $edit>{$ligne["groupname"]}</td>
		<td style='font-size:18px;text-align:center;vertical-align:middle' width=1% nowrap $edit>$count</td>
		<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
	
	}
	$delete_text=$tpl->javascript_parse_text("{unlink}");
	echo $boot->TableCompile(array(
				"rulename"=>" {groupname}",
			"items:no"=>"{items}",
			"delete"=>null,
		),$tr)."
	
		<script>
		var mem$t='';
		var xDelete$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#'+mem$t).remove();
	}
	function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
	mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('rules-sources-group-unlink',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	}
	</script>
	";	
	
	
}

function rules_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="authenticator_rules";
	$t=time();
	$ORDER=$boot->TableOrder(array("ID"=>"DESC"));
	if(!$q->TABLE_EXISTS($table)){
		$f=new squid_reverse();
	}
	
	$searchstring=string_to_flexquery("rules-search");
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$edit=$boot->trswitch("Loadjs('$page?rules-js={$ligne["ID"]}');");
		$md=md5(serialize($ligne));
		if($AdminPrivs){
			$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		}
		
		$explaintext=EXPLAIN_RULE($ligne["ID"]);
		
		$tr[]="
		<tr id='$md'>
		<td style='font-size:18px' width=1% nowrap $edit>{$ligne["rulename"]}</td>
		<td style='font-size:18px' width=90% nowrap>{$ligne["explain"]}<div style='font-size:12px'>$explaintext</div></td>
		<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";		
	
	}
	$delete_text=$tpl->javascript_parse_text("{delete}");
	echo $boot->TableCompile(array(
			"rulename"=>" {rulename}",
			"explain:no"=>"{explain}",
			"delete"=>null,
	),$tr)."
		
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('rules-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
";
	
}

function EXPLAIN_RULE($ID){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="(
	SELECT
	authenticator_sourceslnk.ID,
	authenticator_sourceslnk.zorder,
	authenticator_sourceslnk.groupid,
	authenticator_groups.groupname,
	authenticator_groups.enabled,
	authenticator_groups.group_type
	FROM authenticator_sourceslnk,authenticator_groups
	WHERE authenticator_sourceslnk.ruleid='$ID'
	AND authenticator_sourceslnk.groupid=authenticator_groups.ID
	) as t";
		
	
	$sql="SELECT * FROM $table WHERE 1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	$t=time();
	
	
	$Mainedit="Loadjs('$page?rules-js=$ID');";
	while ($ligne = mysql_fetch_assoc($results)) {	
		$enabled=null;
		if($ligne["enabled"]==0){$enabled=" ({disabled}) ";}
		$edit="Loadjs('$page?rules-sources-group-js=yes&groupid={$ligne["groupid"]}');";
		$a="<a href=\"javascript:blur();\" OnClick=\"javascript:$edit\">";
		$IF[]=" $a".$GLOBALS["SOURCE_TYPE"][$ligne["group_type"]]."</a>$enabled";
		
		
	}
	
	$table="(
	SELECT
	authenticator_authlnk.ID,
	authenticator_authlnk.zorder,
	authenticator_auth.groupname,
	authenticator_auth.group_type,
	authenticator_auth.enabled,
	authenticator_authlnk.groupid
	FROM authenticator_authlnk,authenticator_auth
	WHERE authenticator_authlnk.ruleid='$ID'
			AND authenticator_authlnk.groupid=authenticator_auth.ID
				) as t";	
	
	$sql="SELECT * FROM $table WHERE 1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	$t=time();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$enabled=null;
		if($ligne["enabled"]==0){$enabled=" ({disabled}) ";}
		$js="Loadjs('miniadmin.nginx.authenticator.sources.php?sources-group-js=yes&groupid={$ligne["groupid"]}');";
		$THEN[]=$GLOBALS["DEST_TYPES"][$ligne["group_type"]]."&nbsp;{with_group}&nbsp;
		<a href=\"javascript:blur();\" OnClick=\"javascript:$js\">{$ligne["groupname"]}</a>$enabled";
	
	
	}	
	
	$tpl=new templates();
	
	$a="<a href=\"javascript:blur();\" OnClick=\"javascript:$Mainedit\">";
	$line="$a{rule}</a>: {if} ".@implode(" {and} ", $IF)." {then} ".@implode(" {or} ", $THEN);
	return $tpl->_ENGINE_parse_body($line);
	
}
