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

$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}
if(isset($_GET["replace-search"])){replace_search();exit;}
if(isset($_GET["replace-js"])){replace_js();exit;}
if(isset($_POST["replace-delete"])){replace_delete();exit;}
if(isset($_GET["replace-popup"])){replace_popup();exit;}
if(isset($_POST["replaceid"])){replace_save();exit;}

replace_section();


function replace_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$folderid=$_GET["folderid"];
	$q=new mysql_squid_builder();
	
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("nginx.php?status-infos=yes")));
	if(!is_array($ARRAY["MODULES"])){$ARRAY["MODULES"]=array();}
	$COMPAT=FALSE;
	while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
		if(preg_match("#http_substitutions_filter#", $a)){
			$COMPAT=true;
			break;
		}
	}

	if(!$COMPAT){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{error_nginx_substitutions_filter}</p>");
	}



	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}",
	"Loadjs('$page?replace-js=yes&servername=$servername&replaceid=0&folderid=$folderid')"));
	echo $error.$boot->SearchFormGen("rulename","replace-search","&servername=$servername&folderid=$folderid",$EXPLAIN);

}
function replace_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["replaceid"];
	$folderid=$_GET["folderid"];
	$servername=urlencode($_GET["servername"]);
	$title="{new_rule}";
	if($ID>0){
		$title="{rule}:$ID";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(800,'$page?replace-popup=yes&replaceid=$ID&servername=$servername&folderid=$folderid','$title')";

}
function replace_save(){
	$ID=$_POST["replaceid"];
	unset($_POST["replaceid"]);
	$_POST["stringtosearch"]=url_decode_special_tool($_POST["stringtosearch"]);
	$_POST["replaceby"]=url_decode_special_tool($_POST["replaceby"]);
	$q=new mysql_squid_builder();
	$f=new squid_reverse();

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	if($ID>0){
		$sql="UPDATE nginx_replace_folder SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{

		$sql="INSERT IGNORE INTO nginx_replace_folder (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n".__FUNCTION__."\n"."line:".__LINE__;return;}



}


function replace_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_rule}";
	$bt="{add}";
	$ID=$_GET["replaceid"];
	$folderid=$_GET["folderid"];
	$boot=new boostrap_form();
	$sock=new sockets();
	$servername=$_GET["servername"];

	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace_folder WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["rulename"]}";
		$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
		$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
		$servername=$ligne["servername"];
	}
	if( $ligne["tokens"]==null){ $ligne["tokens"]="g";}
	if($ligne["rulename"]==null){$ligne["rulename"]=time();}
	$boot->set_hidden("replaceid", $ID);
	$boot->set_hidden("folderid", $folderid);
	$boot->set_hidden("servername", $servername);
	$boot->set_formtitle($title);
	$boot->set_field("rulename", "{name}", $ligne["rulename"]);
	$boot->set_field("zorder", "{order}", $ligne["zorder"]);

	$boot->set_spacertitle("{search}");
	$boot->set_textarea("stringtosearch", "{search}", $ligne["stringtosearch"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_checkbox("AsRegex", "{regex}", $ligne["AsRegex"],array("TOOLTIP"=>"{replace_regex_explain}"));
	
	$boot->set_spacertitle("{replace}");
	$boot->set_textarea("replaceby", "{replace}", $ligne["replaceby"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_field("tokens", "{flags}", $ligne["tokens"],array("MANDATORY"=>true));


	$boot->set_button($bt);
	if($ID==0){$boot->set_CloseYahoo("YahooWin3");}
	$boot->set_RefreshSearchs();
	$boot->set_formdescription("{nginx_subst_explain}");
	echo $boot->Compile();

}


function replace_search(){
	$searchstring=string_to_flexquery("replace-search");
	$q=new mysql_squid_builder();

	$folderid=$_GET["folderid"];
	$servername=$_GET["servername"];


	$sql="SELECT * FROM nginx_replace_folder WHERE folderid='$folderid' $searchstring ORDER BY zorder LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$f=new squid_reverse();$results=$q->QUERY_SQL($sql);}}
	if(!$q->ok){senderrors($q->mysql_error);}

	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$icon="www-web-search-64.png";
		$md=md5(serialize($ligne));


		$delete=imgsimple("delete-48.png",null,"Delete$t({$ligne["ID"]},'$md')");
		$jsEdit=$boot->trswitch("Loadjs('$page?replace-js=yes&servername=$servername&replaceid={$ligne["ID"]}&folderid=$folderid')");
		$stringtosearch=substr($ligne["stringtosearch"], 0,200)."...";
		$tr[]="
		<tr id='$md'>
		<td width=1% nowrap $jsEdit><img src='img/$icon'></td>
		<td width=80% $jsEdit><span style='font-size:16px;font-weight:bold'>{$ligne["rulename"]}</span><div style='margin-top:10px'><code style='font-size:13px !important'>$stringtosearch</code></div></td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";



	}



	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_rule}");
	echo $tpl->_ENGINE_parse_body("
				<table class='table table-bordered table-hover'>
	<thead>
		<tr>
			<th colspan=2>{rules}</th>
			<th>&nbsp;</th>
		</tr>
		</thead>
<tbody>").@implode("", $tr)."</tbody></table>
<script>
var FreeWebIDMEM$t='';
var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#'+FreeWebIDMEM$t).remove();
	}

function Delete$t(server,md){
	FreeWebIDMEM$t=md;
		if(confirm('$delete_freeweb_text')){
		var XHR = new XHRConnection();
		XHR.appendData('replace-delete',server);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>
";


}
function replace_delete(){
	$ID=$_POST["replace-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_replace_folder WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}


function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}