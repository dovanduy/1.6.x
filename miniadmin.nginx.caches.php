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
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}


if(isset($_GET["caches-section"])){section_caches();exit;}
if(isset($_GET["caches-search"])){caches_search();exit;}
if(isset($_GET["js-cache"])){cache_js();exit;}
if(isset($_GET["cache-popup"])){cache_popup();exit;}
if(isset($_POST["ID"])){cache_popup_save();exit;}
if(isset($_POST["DeleteCache"])){cache_delete();exit;}





function GetPrivs(){
		$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
		$users=new usersMenus();
		if($users->AsSystemWebMaster){return true;}
		if($users->AsSquidAdministrator){return true;}
		if(count($_SESSION["NGNIX_PRIVS"])>0){return true;} 
		return false;

}
function AdminPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}	
	
}

/*
 * 		
		`ID` INT NOT NULL AUTO_INCREMENT,
		`directory` CHAR(255)  NOT NULL,
		`levels` CHAR(20)  NOT NULL,
		`keys_zone` CHAR(40)  NOT NULL,
		`keys_zone_size` smallint(1)  NOT NULL,
		`inactive` smallint(3)  NOT NULL DEFAULT '10',
		`max_size` smallint(1)  NOT NULL DEFAULT '1',
		`loader_files` INT(5) NOT NULL DEFAULT '100',
		`loader_sleep` INT(10) NOT NULL DEFAULT '50',
		`loader_threshold` INT(10) NOT NULL DEFAULT '200',
 */

function cache_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_cache}";
	$bt="{add}";
	$ID=$_GET["ID"];
	$boot=new boostrap_form();
	$sock=new sockets();
	
	$NginxProxyStorePath=$sock->GET_INFO("NginxProxyStorePath");
	if($NginxProxyStorePath==null){$NginxProxyStorePath="/home/nginx";}

	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_caches WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["keys_zone"]}";
		
	}

	if($ligne["keys_zone"]==null){$ligne["keys_zone"]=time();}
	if(trim($ligne["directory"])==null){$ligne["directory"]=$NginxProxyStorePath."/{$ligne["keys_zone"]}";}
	if($ligne["levels"]==null){$ligne["levels"]="1:2";}
	if(!is_numeric($ligne["keys_zone_size"])){$ligne["keys_zone_size"]=1;}
	if(!is_numeric($ligne["max_size"])){$ligne["max_size"]=2;}
	if(!is_numeric($ligne["inactive"])){$ligne["inactive"]=10;}
	if(!is_numeric($ligne["loader_files"])){$ligne["loader_files"]=100;}
	if(!is_numeric($ligne["loader_sleep"])){$ligne["loader_sleep"]=10;}
	if(!is_numeric($ligne["loader_threshold"])){$ligne["loader_threshold"]=100;}
	$boot->set_hidden("ID", $ID);
	$boot->set_formtitle($title);
	$boot->set_field("keys_zone", "{name}", $ligne["keys_zone"]);
	$boot->set_field("directory", "{directory}", $ligne["directory"],array("BROWSE"=>true,"MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_field("levels", "{levels}", $ligne["levels"]);
	$boot->set_field("keys_zone_size", "{memory_size} (MB)", $ligne["keys_zone_size"]);
	$boot->set_field("max_size", "{max_size} (GB)", $ligne["max_size"]);
	$boot->set_field("inactive", "{inactive} ({minutes})", $ligne["inactive"],array("TOOLTIP"=>"{nginx_inactive_explain}"));
	$boot->set_field("loader_files", "{loader_files}", $ligne["loader_files"]);
	$boot->set_field("loader_sleep", "{loader_sleep} {milliseconds}", $ligne["loader_sleep"]);
	$boot->set_field("loader_threshold", "{loader_threshold} {milliseconds}", $ligne["loader_threshold"]);
	$boot->set_button($bt);
	if($servername==null){$boot->set_CloseYahoo("YahooWin");}
	$boot->set_RefreshSearchs();
	if(!AdminPrivs()){$boot->set_form_locked();}
	echo $boot->Compile();

}
function cache_popup_save(){
	if(!AdminPrivs()){echo "No rights!";return;}
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$q=new mysql_squid_builder();
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	$_POST["keys_zone"]=$html->StripSpecialsChars($_POST["keys_zone"]);
	$_POST["directory"]=url_decode_special_tool($_POST["directory"]);
	$editF=false;

	if($ID>0){
		$editF=true;
	}

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
	
	}

	if($editF){
		$sql="UPDATE nginx_caches SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO nginx_caches (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");
}

function cache_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];

	$title="{new_cache}";
	if($ID>0){
		$title="{cache}:$ID";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(800,'$page?cache-popup&ID=$ID','$title')";

}

function section_caches(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	if(AdminPrivs()){
		$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_cache}", "Loadjs('$page?js-cache=yes&ID=0')"));
	}
	echo $boot->SearchFormGen("directory,keys_zone","caches-search",null,$EXPLAIN);	
}
function caches_search(){
	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("caches-search");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_caches WHERE 1 $searchstring ORDER BY directory LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$AdminPrivs=AdminPrivs();
	$page=CurrentPageName();
	$t=time();
	$delete_text=$tpl->javascript_parse_text("{delete}");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$icon="disk-64.png";
		$icon2="folder-network-64.png";
		$color="black";
		$md=md5(serialize($ligne));
		
		$keys_zone=$ligne["keys_zone"];
		$delete=imgsimple("delete-64.png",null,"Delete$t('{$ligne["ID"]}','$md')");
		if(!$AdminPrivs){
			$delete=imgsimple("delete-64-grey.png",null,"blur()");
			
		}
	
		$jsedit=$boot->trswitch("Loadjs('$page?js-cache=yes&ID={$ligne["ID"]}')");
		$CurrentSize=$ligne["CurrentSize"];
		$CurrentSizeText=FormatBytes($CurrentSize/1024);
		
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap><img src='img/$icon'></td>
		<td width=80% $jsedit style='vertical-align:middle'>
			<span style='font-size:18px;font-weight:bold'>{$ligne["directory"]}</span>
			<br><div style='font-size:16px'>$CurrentSizeText/{$ligne["max_size"]}G</div>
		</td>
		
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap>
			<span style='font-size:18px;font-weight:bold'>{$keys_zone}</span>
		</td>
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap>
			<span style='font-size:18px;font-weight:bold'>{$ligne["max_size"]}G</span>
		</td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	}	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{directory}</th>
					<th >{name}</th>
					<th >{maxsize}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
var mem$t='';

		var xDelete$t = function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return}
			$('#'+mem$t).remove();
			
		}			

	function Delete$t(id,md){
		mem$t=md;
		if(!confirm('$delete_text: '+id)){return;}
		var XHR = new XHRConnection();
		XHR.appendData('DeleteCache',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);			
		
	
	}
</script>	
			 		
";	
	
}
function cache_delete(){
	$ID=$_POST["DeleteCache"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM nginx_caches WHERE ID='$ID'"));
	$directory=$ligne["directory"];
	
	$q->QUERY_SQL("DELETE FROM nginx_caches WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("UPDATE reverse_www SET cacheid=0 WHERE cacheid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?delete-cache=".urlencode(base64_decode($directory)));
	
}