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

if(isset($_POST["PARAMS_SAVE"])){PARAMS_SAVE();exit;}
if(isset($_GET["pages-search"])){pages_search();exit;}
if(isset($_GET["error-add-js"])){page_js();exit;}
if(isset($_GET["page-tab"])){page_tab();exit;}

if(isset($_GET["rules-sources-add-group-js"])){sources_add_group_js();exit;}
if(isset($_GET["page-popup"])){page_popup();exit;}
if(isset($_GET["page-body"])){page_body();exit;}

if(isset($_GET["page-headers"])){page_headers();exit;}



if(isset($_POST["page-save"])){page_save();exit;}
if(isset($_POST["SaveBody"])){page_body_save();exit;}
if(isset($_POST["SaveHeaders"])){page_headers_save();exit;}



if(isset($_GET["sources-link-group-js"])){sources_link_js();exit;}
if(isset($_GET["sources-link-section"])){sources_link_section();exit;}
if(isset($_GET["sources-link-search"])){sources_link_search();exit;}
if(isset($_POST["delete-page"])){page_delete();exit;}
if(isset($_POST["source-link"])){sources_link();exit;}

if(isset($_GET["rules-sources-group-auth"])){sources_group_auth();exit;}


errors_section();

function errors_section(){
	
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
		$error=$tpl->_ENGINE_parse_body($error);
	}
	$EXPLAIN["BUTTONS"][]=button("{new_error_page}","Loadjs('$page?error-add-js=yes&ID=');",16);
	echo $error.$boot->SearchFormGen("pagename,error_code,title","pages-search","",$EXPLAIN);
}

function page_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(!isset($_GET["ID"])){$_GET["ID"]=0;}
	
	$title="{new_error_page}";
	$rulename=null;
	if($_GET["ID"]>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT pagename FROM nginx_error_pages WHERE ID='{$_GET["ID"]}'"));
		$rulename="::{$_GET["ID"]}::".$ligne["pagename"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(820,'$page?page-tab=yes&ID={$_GET["ID"]}','$title$rulename')";
}
function page_tab(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){senderror("no privs");}
	$ID=$_GET["ID"];

	$tpl=new templates();
	$array["{parameters}"]="$page?page-popup=yes&ID=$ID";
	if($ID>0){
		$array["{html_body}"]="$page?page-body=yes&ID=$ID";
		$array["{headers}"]="$page?page-headers=yes&ID=$ID";
	}
	echo $boot->build_tab($array);	
}

function page_body(){
	if(!isset($_GET["ID"])){$_GET["ID"]=0;}
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT body FROM nginx_error_pages WHERE ID='{$_GET["ID"]}'"));
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",18));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",18));
	
	
	if(strlen($ligne["body"])<10){
		$ligne["body"]="<table class=\"w100 h100\">
		<tr>
		<td class=\"c m\">
		<table style=\"margin:0 auto;border:solid 1px #560000\">
		<tr>
		<td class=\"l\" style=\"padding:1px\">
		<div style=\"width:346px;background:#E33630\">
		<div style=\"padding:3px\">
		<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
		<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
		<h1>{TITLE}</h1>
		</div>
		<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">
		{TITLE}
				</div>
				<div style=\"background:#F7F7F7;padding:20px 28px 36px\">
				<div id=\"titles\">
				<h1>Request not allowed</h1> <h2>{uid}</h2>
				</div> <hr>
				<div id=\"content\">
				<blockquote id=\"error\"> <p><b>{explain}</b></p> </blockquote>
				<p>The request:<a href=\"{uri}\">{uri}</a> cannot be displayed<br>
				Please contact your service provider if you feel this is incorrect.
				</p>  <p>Generated by Artica Reverse Proxy <a href=\"http://www.articatech.net\">artica.fr</a></p>
			 <br> </div>  <hr> <div id=\"footer\"> <p>Artica version: {ARTICA_VERSION}</p> <!-- %c --> </div> </div></div>
			 </div>
			 </td>
			 </tr>
			 </table>
			 </td>
			 </tr>
			</table>";
	}
	
	$tiny=TinyMce("body-$t",$ligne["body"],true);
	
	
	$tokens=$tpl->_ENGINE_parse_body("<div class=explain><strong>{tokens}:")."</strong>{TITLE},{ARTICA_VERSION},{uid},
	{error_code},{error_desc},{uri}</div>";
	
$html="$tokens
	<div id='$t'></div>	
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$button<br></div>
	<center>
	<div style='width:750px;height:auto'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>
		$button2
	</div>
	
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
	
}
function Save2$t(){ Save$t();}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SaveBody', '$ID');
	AnimateDiv('$t');
	XHR.appendData('body', encodeURIComponent(tinymce.get('body-$t').getContent()));
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}

</script>
";

echo $html;
	
}

function page_headers(){
	if(!isset($_GET["ID"])){$_GET["ID"]=0;}
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT headers FROM nginx_error_pages WHERE ID='{$_GET["ID"]}'"));
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",18));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",18));	
	
	
	
	if(strlen($ligne["headers"])<10){
		$ligne["headers"]=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");
	}
	$html="
	<div id='$t'></div>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$button<br></div>
	<center>
	<div style='width:750px;height:auto'>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:12px !important'
	id='body-$t'>{$ligne["headers"]}</textarea>
	
	</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>
	$button2
	</div>
	
	<script>
	var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
	
	}
	function Save2$t(){ Save$t();}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SaveHeaders', '$ID');
	AnimateDiv('$t');
	XHR.appendData('headers', encodeURIComponent(tinymce.get('body-$t').getContent()));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	</script>";
	echo $html;
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
		
	}
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body("<H3>$rulename - $group_type_text ({$ligne["group_type"]})</H3>").$form;
	echo $html;
	
	
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


function page_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();

	$ID=$_GET["ID"];
	$title_button="{add}";
	$title="{new_error_page}";
	$f=new squid_reverse();
	


	if($ID>0){
		$sql="SELECT * FROM nginx_error_pages WHERE ID='$ID'";
		$q=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$title_button="{apply}";
		$title=$ligne["pagename"]."&nbsp;&raquo;&raquo;&nbsp;".$ligne["error_code"];
	}
	
	
	
	while (list ($key, $value) = each ($f->errors_page) ){
		$errors_code[$value]=$value;
	}

	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("page-save", "yes");
	$boot->set_hidden("ID", $ID);
	$boot->set_field("pagename","{pagename}",$ligne["pagename"],array("ENCODE"=>true));
	$boot->set_list("error_code", "{error_code}", $errors_code,$ligne["error_code"]);
	$boot->set_field("title","{page_title}",$ligne["title"],array("ENCODE"=>true));
	
	
	$boot->set_button($title_button);
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){$boot->set_form_locked();}

	if($ID==0){$boot->set_CloseYahoo("YahooWin3");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();


}


function pages_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="nginx_error_pages";
	$ORDER=$boot->TableOrder(array("pagename"=>"ASC"));
	$searchstring=string_to_flexquery("pages-search");
	if(!$q->TABLE_EXISTS("nginx_error_pages")){$f=new squid_reverse();}
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$AdminPrivs=AdminPrivs();
	$t=time();



	while ($ligne = mysql_fetch_assoc($results)) {
		$edit=$boot->trswitch("Loadjs('$page?error-add-js=yes&ID={$ligne["ID"]}');");
		$md=md5(serialize($ligne));
		if($AdminPrivs){
			$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		}
			
		$tr[]="
			<tr id='$md'>
				<td style='font-size:18px' width=50% nowrap $edit>{$ligne["pagename"]}</td>
				<td style='font-size:18px' width=50% nowrap $edit>{$ligne["title"]}</td>
				<td style='font-size:18px' width=1% nowrap $edit>{$ligne["error_code"]}</td>
				<td style='font-size:18px' width=1% nowrap>$delete</td>
			</tr>
			";

			}
			$delete_text=$tpl->javascript_parse_text("{delete}");
			echo $boot->TableCompile(array(
				"pagename"=>" {pagename}",
				"title"=>" {page_title}",
				"error_code"=>" {error_code}",					
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
		XHR.appendData('delete-page',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
	";


}
function page_save(){
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	unset($_POST["page-save"]);
	$table="nginx_error_pages";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){$prox=new squid_reverse();}
	$_POST["pagename"]=url_decode_special_tool($_POST["pagename"]);
	$_POST["title"]=url_decode_special_tool($_POST["title"]);


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
	
}

function page_body_save(){
	$ID=$_POST["SaveBody"];
	$_POST["body"]=url_decode_special_tool($_POST["body"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE nginx_error_pages SET `body`='{$_POST["body"]}' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error."\n\n";return;}
}
function page_headers_save(){
	$ID=$_POST["SaveHeaders"];
	$_POST["headers"]=url_decode_special_tool($_POST["headers"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE nginx_error_pages SET `body`='{$_POST["headers"]}' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error."\n\n";return;}
}
function page_delete(){
	$ID=$_POST["delete-page"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_error_pages WHERE ID=$ID");
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