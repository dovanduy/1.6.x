<?php
session_start();



ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");

function AdminPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}

}

//miniadmin.nginx.authenticator.php?rules-sources-group-item-popup=yes&groupid=2

if(isset($_POST["PARAMS_SAVE"])){PARAMS_SAVE();exit;}
if(isset($_GET["sources-search"])){sources_search();exit;}
if(isset($_GET["sources-group-js"])){sources_js();exit;}
if(isset($_GET["sources-group-tab"])){sources_group_tab();exit;}

if(isset($_GET["rules-sources-add-group-js"])){sources_add_group_js();exit;}
if(isset($_GET["rules-sources-add-group-popup"])){sources_add_group_popup();exit;}
if(isset($_POST["rules-sources-add-group-save"])){sources_add_group_save();exit;}
if(isset($_POST["source-unlink"])){sources_unlink();exit;}

if(isset($_GET["sources-link-group-js"])){sources_link_js();exit;}
if(isset($_GET["sources-link-section"])){sources_link_section();exit;}
if(isset($_GET["sources-link-search"])){sources_link_search();exit;}
if(isset($_POST["source-delete"])){sources_delete();exit;}
if(isset($_POST["source-link"])){sources_link();exit;}

if(isset($_GET["rules-sources-group-auth"])){sources_group_auth();exit;}


sources_section();

function sources_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_group}","Loadjs('$page?rules-sources-add-group-js=yes&mainrule={$_GET["mainrule"]}');",16);
	$EXPLAIN["BUTTONS"][]=button("{link_group}","Loadjs('$page?sources-link-group-js=yes&mainrule={$_GET["mainrule"]}');",16);
	echo $boot->SearchFormGen("groupname","sources-search","&mainrule={$_GET["mainrule"]}",$EXPLAIN);
}

function sources_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
	
	$title="{group}";
	$rulename=null;
	if($_GET["groupid"]>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM authenticator_auth WHERE ID='{$_GET["groupid"]}'"));
		$rulename="::{$_GET["groupid"]}::".$ligne["groupname"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(700,'$page?sources-group-tab=yes&groupid={$_GET["groupid"]}','$title$rulename')";
}
function sources_group_tab(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){senderror("no privs");}
	$groupid=$_GET["groupid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,group_type FROM authenticator_auth WHERE ID='{$_GET["groupid"]}'"));
	$rulename=$ligne["groupname"];
	$group_type=$ligne["group_type"];
	$group_type_text=$GLOBALS["TYPES"][$group_type];
	$tpl=new templates();
	$array["{parameters}"]="$page?rules-sources-add-group-popup=yes&groupid=$groupid";
	if($groupid>0){
		$array[$group_type_text]="$page?rules-sources-group-auth=yes&groupid=$groupid";
	}
	echo $boot->build_tab($array);	
}

function sources_group_auth(){
	$groupid=$_GET["groupid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,group_type,params FROM authenticator_auth WHERE ID='{$_GET["groupid"]}'"));

	$rulename=$ligne["groupname"];
	$group_type=$ligne["group_type"];
	$group_type_text=$GLOBALS["TYPES"][$group_type];
	$params=unserialize(base64_decode($ligne["params"]));
	
	switch ($ligne["group_type"]){
		case 0:$form=sources_group_auth_LOCALLDAP();
			break;
			
		case 2:$form=sources_group_auth_activedirectory($groupid,$params);
			break;
		
		case 4:$form=sources_group_auth_url($groupid,$params);
		break;
		
	}
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body("<H3>$rulename - $group_type_text ({$ligne["group_type"]})</H3>").$form;
	echo $html;
	
	
}

function PARAMS_SAVE(){
	$groupid=$_POST["groupid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM authenticator_auth WHERE ID='$groupid'"));
	$params=unserialize(base64_decode($ligne["params"]));
	
	while (list ($key, $line) = each ($_POST) ){
		$params[$key]=url_decode_special_tool($line);
	}
	
	$newparams=base64_encode(serialize($params));
	$sql="UPDATE authenticator_auth SET `params`='".mysql_escape_string2($newparams)."' WHERE ID='$groupid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}


function sources_group_auth_activedirectory($groupid,$params){
	
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	
	$ID=$_GET["groupid"];
	$title_button="{apply}";
	$title="{active_directory}";
	
	if(!is_numeric($params["LDAP_PORT"])){$params["LDAP_PORT"]=389;}
	$boot->set_formtitle($title);
	$boot->set_hidden("PARAMS_SAVE", "yes");
	$boot->set_hidden("groupid", $groupid);
	$boot->set_field("LDAP_SERVER","{hostname}",$params["LDAP_SERVER"],array("ENCODE"=>true));
	$boot->set_field("LDAP_PORT", "{ldap_port}", $params["LDAP_PORT"],array("ENCODE"=>true));
	$boot->set_field("WINDOWS_DNS_SUFFIX", "{WINDOWS_DNS_SUFFIX}", $params["WINDOWS_DNS_SUFFIX"],array("ENCODE"=>true));
	
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	$boot->set_RefreshSearchs();
	return $boot->Compile();
		
}

function sources_group_auth_url($groupid,$params){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	
	$ID=$_GET["groupid"];
	$title_button="{apply}";
	$boot->set_formdescription("{redirect_uri_explain}");
	$boot->set_hidden("PARAMS_SAVE", "yes");
	$boot->set_hidden("groupid", $groupid);
	$boot->set_field("URI","{url}",$params["URI"],array("ENCODE"=>true));
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}
	$boot->set_RefreshSearchs();
	return $boot->Compile();	
	
}

function sources_group_auth_LOCALLDAP(){
	$ldap=new clladp();
	$html="
	<table style='width:100%' class='table table-bordered'>
	<tr>
		<td style='font-size:18px'>{hostname}:</td>
		<td style='font-size:18px;font-weight:bold'>$ldap->ldap_host</td>			
		
	</tr>	
	<tr>
		<td style='font-size:18px'>{ldap_port}:</td>
		<td style='font-size:18px;font-weight:bold'>$ldap->ldap_port</td>			
	</tr>
	<tr>
		<td style='font-size:18px'>{ldap_suffix}:</td>
		<td style='font-size:18px;font-weight:bold'>$ldap->suffix</td>			
	</tr>					
	<tr>
		<td style='font-size:18px'>{bind_dn}:</td>
		<td style='font-size:18px;font-weight:bold'>CN=$ldap->ldap_admin,$ldap->suffix</td>			
	</tr>
	</table>	
	";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
}

function sources_add_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["mainrule"];
	if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
	if(!is_numeric($ruleid)){$ruleid=0;}
	$title="{link_group}";
	$rulename=null;
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM authenticator_rules WHERE ID='$ruleid'"));
		$rulename="::".$ligne["rulename"];
	}
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(800,'$page?rules-sources-add-group-popup=yes&mainrule=$ruleid&groupid={$_GET["groupid"]}','$title$rulename')";
}

function sources_link_js(){
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
	echo "YahooWin3(700,'$page?sources-link-section=yes&mainrule=$ruleid','$title$rulename')";
	
}

function sources_link_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	echo $boot->SearchFormGen("groupname","sources-link-search","&mainrule={$_GET["mainrule"]}");	
	
}

function sources_link_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="authenticator_auth";
	$ORDER=$boot->TableOrder(array("groupname"=>"ASC"));
	$searchstring=string_to_flexquery("sources-link-search");
	if(!$q->TABLE_EXISTS("authenticator_authlnk")){$f=new squid_reverse();}

	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	$t=time();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$edit=$boot->trswitch("Loadjs('$page?sources-group-js=yes&groupid={$ligne["ID"]}');");
		$md=md5(serialize($ligne));
		if($AdminPrivs){
		$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		$link=imgsimple("arrow-right-32.png","{link}","Link$t('{$ligne["ID"]}','$md')");
	}
	
		$tr[]="
		<tr id='$md'>
			<td style='font-size:18px' width=99% nowrap $edit>{$ligne["groupname"]} - ".$tpl->_ENGINE_parse_body($GLOBALS["TYPES"][$ligne["group_type"]])."</td>
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
		XHR.appendData('source-delete',ID);
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
	XHR.appendData('source-link',ID);
	XHR.appendData('main-link','{$_GET["mainrule"]}');
	XHR.sendAndLoad('$page', 'POST',xLink$t);
	
}
</script>
	";	
	
}

function sources_add_group_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();

	$ID=$_GET["groupid"];
	$title_button="{add}";
	$title="{new_group}";
	
	


	if($ID>0){
		$sql="SELECT * FROM authenticator_auth WHERE ID='$ID'";
		$q=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$title_button="{apply}";
		$title=$ligne["groupname"]."&nbsp;&raquo;&raquo;&nbsp;".$GLOBALS["TYPES"][$ligne["group_type"]];
	}

	$mainrule=$_GET["mainrule"];
	if(!is_numeric($mainrule)){$mainrule=0;}
	



	$boot->set_formtitle($title);
	$boot->set_hidden("rules-sources-add-group-save", "yes");
	$boot->set_hidden("mainrule", $mainrule);
	$boot->set_hidden("groupid", $ID);
	$boot->set_field("groupname","{groupname}",$ligne["groupname"],array("ENCODE"=>true));
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	if($ID==0){
		$boot->set_list("group_type", "{groupe_type}", $GLOBALS["TYPES"],$ligne["group_type"]);
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


function sources_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="authenticator_authlnk";
	$ORDER=$boot->TableOrder(array("zorder"=>"ASC"));
	$searchstring=string_to_flexquery("sources-search");
	if(!$q->TABLE_EXISTS("authenticator_authlnk")){$f=new squid_reverse();}
	$table="(
	SELECT
	authenticator_authlnk.ID,
	authenticator_authlnk.zorder,
	authenticator_auth.groupname,
	authenticator_auth.group_type,
	authenticator_authlnk.groupid
	FROM authenticator_authlnk,authenticator_auth
	WHERE authenticator_authlnk.ruleid='{$_GET["mainrule"]}'
			AND authenticator_authlnk.groupid=authenticator_auth.ID
			) as t";
				

			$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
			$results = $q->QUERY_SQL($sql);
			if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
			$AdminPrivs=AdminPrivs();
			$t=time();



			while ($ligne = mysql_fetch_assoc($results)) {

			$edit=$boot->trswitch("Loadjs('$page?sources-group-js=yes&groupid={$ligne["groupid"]}');");
			$md=md5(serialize($ligne));
			if($AdminPrivs){
				$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
				
				
			}
			
			$tr[]="
			<tr id='$md'>
			<td style='font-size:18px' width=99% nowrap $edit>{$ligne["groupname"]} - ".$tpl->_ENGINE_parse_body($GLOBALS["TYPES"][$ligne["group_type"]])."</td>
			<td style='font-size:18px' width=1% nowrap>$delete</td>
			
			</tr>
			";

			}
			$delete_text=$tpl->javascript_parse_text("{unlink}");
			echo $boot->TableCompile(array(
				"rulename"=>" {groupname}",
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
		XHR.appendData('source-unlink',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
	";


}
function sources_add_group_save(){
	$mainrule=$_POST["mainrule"];
	$ID=$_POST["groupid"];
	unset($_POST["mainrule"]);
	unset($_POST["groupid"]);
	unset($_POST["rules-sources-add-group-save"]);
	$table="authenticator_auth";
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
	if(!$q->ok){echo $q->mysql_error."\n\n$sql";return;}
	if($mainrule>0){
		$zmd5=md5("$ID$mainrule");
		$sql="INSERT IGNORE INTO authenticator_authlnk (`ruleid`,`groupid`,`zorder`,`zmd5`)
		VALUES('$mainrule','$ID','0','$zmd5');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
	}
}

function sources_delete(){
	$ID=$_POST["source-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM authenticator_authlnk WHERE groupid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM authenticator_auth WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
}
function sources_link(){
	$ID=$_POST["source-link"];
	$mainrule=$_POST["main-link"];
	$zmd5=md5("$ID$mainrule");
	$q=new mysql_squid_builder();
	$sql="INSERT IGNORE INTO authenticator_authlnk (`ruleid`,`groupid`,`zorder`,`zmd5`)
	VALUES('$mainrule','$ID','0','$zmd5');";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function sources_unlink(){
	
	$ID=$_POST["source-unlink"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM authenticator_authlnk WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	
}