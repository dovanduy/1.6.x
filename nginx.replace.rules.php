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

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-id"])){delete();exit;}
if(isset($_GET["popup-webserver-replace-js"])){websites_popup_webserver_replace_js();exit;}
if(isset($_GET["popup-webserver-replace-popup"])){websites_popup_webserver_replace_popup();exit;}
if(isset($_POST["replaceid"])){websites_popup_webserver_replace_save();exit;}
if(isset($_POST["popup-webserver-replace-delete"])){websites_popup_webserver_replace_delete();exit;}

replace_table();

function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}
function delete(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["replaceid"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM nginx_replace_www WHERE ID='$ID'"));
	
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_rule} {$ligne["rulename"]}");
	echo "var xDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>10){alert(results);return;}
		$('#NGINX_MAIN_TABLE').flexReload();
		$('#NGINX_REPLACE_RULES').flexReload();
	}
	
	function Delete$t(server,md){
		if(confirm('$delete_freeweb_text ?')){
			var XHR = new XHRConnection();
			XHR.appendData('popup-webserver-replace-delete','$ID');
			XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
	
	Delete$t();
	";
	
	
}

function websites_popup_webserver_replace_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["replaceid"];
	$servername=urlencode($_GET["servername"]);
	$title="{new_rule}";
	if($ID>0){
		$title="{rule}:$ID";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(800,'$page?popup-webserver-replace-popup=yes&replaceid=$ID&servername=$servername','$title')";

}
function websites_popup_webserver_replace_delete(){
	$ID=$_POST["popup-webserver-replace-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}
function websites_popup_webserver_replace_save(){
	$ID=$_POST["replaceid"];
	unset($_POST["replaceid"]);
	$_POST["rulename"]=url_decode_special_tool($_POST["rulename"]);
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
		$sql="UPDATE nginx_replace_www SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{

		$sql="INSERT IGNORE INTO nginx_replace_www (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n".__FUNCTION__."\n"."line:".__LINE__;return;}
}


function websites_popup_webserver_replace_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_rule}";
	$bt="{add}";
	$ID=intval($_GET["replaceid"]);
	$boot=new boostrap_form();
	$sock=new sockets();
	$servername=$_GET["servername"];
	$t=time();
	$fields_size=22;

	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace_www WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["rulename"]}";
		$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
		$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
		$servername=$ligne["servername"];
	}
	if( $ligne["tokens"]==null){ $ligne["tokens"]="gir";}
	if($ligne["rulename"]==null){$ligne["rulename"]=time();}
	
	
	$html[]="<div style='width:98%' class=form><div style='font-size:30px;margin-bottom:20px'>{$title}</div>
	<div class=explain style='font-size:18px'>{nginx_subst_explain}</div>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_text_table("rulename-$t","{rulename}",$ligne["rulename"],$fields_size,null,450);
	$html[]=Field_text_table("zorder-$t","{order}",intval($ligne["zorder"]),$fields_size,null,110);
	$html[]=Field_area_table("stringtosearch-$t","{search}",$ligne["stringtosearch"],$fields_size,null,145);
	
	
	$html[]=Field_checkbox_table("AsRegex-$t", "{regex}",$ligne["AsRegex"],$fields_size,"{replace_regex_explain}");
	$html[]=Field_area_table("replaceby-$t","{replace}",$ligne["replaceby"],$fields_size,null,145);
	$html[]=Field_text_table("tokens-$t","{flags}",$ligne["tokens"],$fields_size,null);
	$html[]=Field_button_table_autonome($bt,"Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
<script>
	var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	$('#NGINX_MAIN_TABLE').flexReload();
	$('#NGINX_REPLACE_RULES').flexReload();
	var ID=$ID
	if(ID==0){ YahooWin3Hide();}
	
}
	
	
function Submit$t(){
	var AsRegex=0
	var XHR = new XHRConnection();
	XHR.appendData('replaceid','$ID');
	if(document.getElementById('AsRegex-$t').checked){AsRegex=1;}
	XHR.appendData('servername','$servername');
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('zorder',document.getElementById('zorder-$t').value);
	XHR.appendData('stringtosearch',encodeURIComponent(document.getElementById('stringtosearch-$t').value));
	XHR.appendData('replaceby',encodeURIComponent(document.getElementById('replaceby-$t').value));
	XHR.appendData('AsRegex',AsRegex);
	XHR.appendData('tokens',document.getElementById('tokens-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}
</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}


function replace_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$servername=urlencode($_GET["servername"]);
	$replace_rules=$tpl->javascript_parse_text("{replace_rules}");
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
		echo FATAL_ERROR_SHOW_128("{error_nginx_substitutions_filter}");
		die();
	}
	

	$apply=$tpl->javascript_parse_text("{apply}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$newrule=$tpl->javascript_parse_text("{new_rule}");
	
	$buttons="	buttons : [
	{name: '<strong style=font-size:18px>$newrule</strong>', bclass: 'Add', onpress : New$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Down', onpress : apply$t},
	
	

	],";
	
	$html="
	<table class='NGINX_REPLACE_RULES' style='display: none' id='NGINX_REPLACE_RULES'></table>
	<script>
	$(document).ready(function(){
	$('#NGINX_REPLACE_RULES').flexigrid({
	url: '$page?search=yes&servername=$servername',
	dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'zorder', width : 60, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>$rulename</span>', name : 'rulename', width : 1081, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
	
		],
		$buttons
		searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
		sortname: 'rulename',
		sortorder: 'asc',
		usepager: true,
		title: '<strong style=font-size:22px>$replace_rules</strong>',
		useRp: true,
		rpOptions: [10, 20, 30, 50,100,200],
		rp:50,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	
function New$t(){
	Loadjs('$page?popup-webserver-replace-js=yes&servername=$servername&replaceid=0');
}

function apply$t(){
	Loadjs('nginx.single.progress.php?servername=$servername');
}
</script>";
	
	echo $html;	
	
}

function search(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$table="(SELECT * FROM nginx_replace_www WHERE servername='$servername') as t";
	$total=0;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	$total=mysql_num_rows($results);
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$CurrentPage=CurrentPageName();
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md=md5(serialize($ligne));
		$rulename=utf8_encode($ligne["rulename"]);
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-id=yes&servername=$servername&replaceid={$ligne["ID"]}')");
		$jsEdit="Loadjs('$MyPage?popup-webserver-replace-js=yes&servername=$servername&replaceid={$ligne["ID"]}')";
		if($rulename==null){$rulename="????";}
		$data['rows'][] = array(
				'id' =>$md,
				'cell' => array(
						
						"<a href=\"javascript:blur();\"
						style='font-size:26px;font-weight:bold;text-decoration:underline'
						OnClick=\"javascript:$jsEdit\">{$ligne["zorder"]}</a>
						",
						
						"<a href=\"javascript:blur();\"
						style='font-size:26px;font-weight:bold;text-decoration:underline'
						OnClick=\"javascript:$jsEdit\">$rulename</a>
						",
						
						"<center>$delete</center>"
				)
		);
	}
	echo json_encode($data);
}