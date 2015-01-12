<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	include_once('ressources/class.nginx.interface-tools.php');
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	if(isset($_GET["list"])){list_items();exit;}
	if(isset($_GET["md5-js"])){rule_js();exit;}
	if(isset($_GET["md5-id"])){rule_popup();exit;}
	
	if(isset($_POST["username"])){rule_save();exit;}
	
table();



function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$md5=$_GET["md5-js"];
	if($md5==null){$title="{new_rule}";}else{
		$title="{rule}:: $md5";
	}
	$title=$tpl->_ENGINE_parse_body($title);
	
	echo "YahooWin('915','$page?md5-id=$md5','$title')";
}

function rule_popup(){
	$md5=$_GET["md5-id"];
	$tpl=new templates();
	$page=CurrentPageName();
	$fields_size=22;
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$t=time();
	
	$protcols["imap"]="IMAP";
	$protcols["pop3"]="POP3";
	$protcols["smtp"]="SMTP";
	

	$title="{new_rule}";
	$bt="{add}";
	if($md5<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_mailauth WHERE zmd5='$md5'"));
		if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
		$title="{rule} {$ligne["username"]} {$ligne["protocol"]}:{$ligne["backend"]}:{$ligne["port"]}";
		$bt="{apply}";
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["backend_port"])){$ligne["backend_port"]=143;}
	if($ligne["protocol"]==null){$ligne["protocol"]="imap";}
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2 style='font-size:28px;padding-bottom:20px'>{rule} {$ligne["ipaddr"]}:{$ligne["port"]}</td></tr>";
	$html[]="<tr><td colspan=2>".Paragraphe_switch_img("{enable_rule}", "{enable_rule_text}","enabled-$t",$ligne["enabled"],null,820)."</td></tr>";
	$html[]=Field_text_table("username-$t","{username}",$ligne["username"],$fields_size,null,450);
	$html[]=Field_ipv4_table("ipsrc-$t","{src}",$ligne["ipsrc"],$fields_size,null,110);
	$html[]=Field_text_table("destination-$t","{recipient} SMTP",$ligne["destination"],$fields_size,null,450);
	$html[]=Field_list_table("protocol-$t","{protocol}",$ligne["protocol"],$fields_size,$protcols,"Check$t()");
	$html[]=Field_text_table("backend-$t","{mail_server}",$ligne["backend"],$fields_size,null,450);
	$html[]=Field_text_table("backend_port-$t","{port}",$ligne["backend_port"],$fields_size,null,110);
	$html[]=Field_button_table_autonome("$bt","Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
	<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#NGINX_MAIN_MAIL').flexReload();
		var md5='$md5';
		if(md5==''){ YahooWinHide();}
	
	}
	
	
	function Submit$t(){
		var XHR = new XHRConnection();
		XHR.appendData('zmd5','$md5');
		XHR.appendData('username',encodeURIComponent(document.getElementById('username-$t').value));
		XHR.appendData('ipsrc',encodeURIComponent(document.getElementById('ipsrc-$t').value));
		XHR.appendData('protocol',document.getElementById('protocol-$t').value);
		XHR.appendData('backend',document.getElementById('backend-$t').value);
		XHR.appendData('backend_port',document.getElementById('backend_port-$t').value);
		XHR.appendData('enabled',document.getElementById('enabled-$t').value);
		XHR.appendData('destination',encodeURIComponent(document.getElementById('destination-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	
	function Check$t(){
		var protocol=document.getElementById('protocol-$t').value;
		if(protocol!=='smtp'){
			document.getElementById('destination-$t').disabled=true;
		}else{
		document.getElementById('destination-$t').disabled=false;
		}
	
	}
	Check$t();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}
	
function rule_save(){
	$md5=$_POST["zmd5"];
	if($_POST["protocol"]<>"smtp"){$_POST["destination"]=null;}
	$_POST["username"]=trim(mysql_escape_string2(url_decode_special_tool($_POST["username"])));
	$_POST["destination"]=trim(mysql_escape_string2(url_decode_special_tool($_POST["destination"])));
	$_POST["ipsrc"]=trim(mysql_escape_string2(url_decode_special_tool($_POST["ipsrc"])));
	if($_POST["ipsrc"]=="*"){$_POST["ipsrc"]=null;}
	$squid=new squid_reverse();
	
	
	if($md5==null){
		$md5=md5("{$_POST["username"]}{$_POST["ipsrc"]}{$_POST["protocol"]}");
		$sql="INSERT IGNORE INTO reverse_mailauth (zmd5,username,ipsrc,protocol,backend,backend_port,enabled,destination)
			VALUES('$md5','{$_POST["username"]}','{$_POST["ipsrc"]}','{$_POST["protocol"]}',
			'{$_POST["backend"]}','{$_POST["backend_port"]}','{$_POST["enabled"]}','{$_POST["destination"]}')";
			
		
		
	}else{
		$array=FORM_CONSTRUCT_SQL_FROM_POST("reverse_mailauth","md5");
		$sql=$array[1];
	}
	$q=new mysql_squid_builder();
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}



function table(){
	$sock=new sockets();
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("reverse_mail")){$f=new squid_reverse();}
	if(!$q->TABLE_EXISTS("reverse_www")){$f=new squid_reverse();}
	if(!$q->TABLE_EXISTS("reverse_sources")){$f=new nginx_sources();$f->PatchTables();}
	if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";} }


	$page=CurrentPageName();
	$tpl=new templates();
	$username=$tpl->_ENGINE_parse_body("{username}");
	$ipsrc=$tpl->_ENGINE_parse_body("{ipaddr}");
	$protocol=$tpl->_ENGINE_parse_body("{protocol}");
	$mail_server=$tpl->_ENGINE_parse_body("{mail_server}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$apply_parameters=$tpl->javascript_parse_text("{rebuild_all_websites}");
	$purge_caches=$tpl->javascript_parse_text("{purge_caches}");
	$import_export=$tpl->javascript_parse_text("{import_export}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();

	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : New$t},
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	{name: '$purge_caches', bclass: 'Delz', onpress :  purge_caches$t},
	{name: '$import_export', bclass: 'Down', onpress :  import_export$t},
	],	";
	
$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : New$t},
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	],	";
	
	$html="
	<table class='NGINX_MAIN_MAIL' style='display: none' id='NGINX_MAIN_MAIL' style='width:99%'></table>
	<script>
	function BuildTable$t(){
	$('#NGINX_MAIN_MAIL').flexigrid({
	url: '$page?list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$username', name : 'username', width :169, sortable : false, align: 'left'},
	{display: '$ipsrc', name : 'ipsrc', width :167, sortable : false, align: 'center'},
	{display: '$destination', name : 'destination', width :201, sortable : false, align: 'left'},
	{display: '$mail_server', name : 'backend', width :473, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width :60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
	{display: '$username', name : 'username'},
	{display: '$ipsrc', name : 'ipsrc'},
	{display: '$destination', name : 'destination'},
	{display: '$protocol', name : 'protocol'},
	{display: '$mail_server', name : 'mail_server'},
	],
	sortname: 'username',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
}

function apply_parameters$t(){
	Loadjs('nginx.destination.progress.php?cacheid={$_GET["ID"]}')
}
function purge_caches$t(){
Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
Loadjs('miniadmin.proxy.reverse.import.php');
}

function New$t(){
	Loadjs('$page?md5-js=');
}
	BuildTable$t();
	</script>";
	echo $html;
}


function list_items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$all_text=$tpl->_ENGINE_parse_body("{all}");
	$GLOBALS["CLASS_TPL"]=$tpl;
	$q=new mysql_squid_builder();
	$table="reverse_mailauth";
	$sock=new sockets();
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	
	$FORCE=1;
	$search='%';
	
	$page=1;
	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();
	
	$all=$tpl->_ENGINE_parse_body("{all}");

	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		$status=array();
		$portText=null;
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$md5=$ligne["zmd5"];
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=$md5");
		$jsedit="Loadjs('$MyPage?md5-js=$md5')";
		$username=$ligne["username"];
		$protocol=$ligne["protocol"];
		$ipsrc=$ligne["ipsrc"];
		$destination=$ligne["destination"];
		$backend=$ligne["backend"];
		$backend_port=$ligne["backend_port"];

		
		$url="<a href=\"javascript:blur();\"
				style='font-size:18px;font-weight:bold;text-decoration:underline'
				OnClick=\"javascript:$jsedit\">";
		
		$url_destination=$url;
		$url_ipsrc=$url;
		
		if($protocol<>"smtp"){
			$destination="<center style='font-size:40px'>-</center>";$url_ipsrc=null;
		}
		
		if($ipsrc==null){$ipsrc="<center style='font-size:40px;padding-top:8px'>*</center>";$url_destination=null;}
		
		$destination_final="$protocol $backend:$backend_port";
		

		$data['rows'][] = array(
			'id' => $md5,
			'cell' => array(
				"$url$username</a>",
				"$url_ipsrc$ipsrc</a>",
				"$url_destination$destination</a>",
				"$url$destination_final</a>",
				"$url$delete</a>",
				)
			);
	}

	echo json_encode($data);
}
