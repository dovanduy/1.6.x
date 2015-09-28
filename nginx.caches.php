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
	if(isset($_GET["js-cache"])){cache_js();exit;}
	if(isset($_GET["js-delete"])){delete_js();exit;}
	if(isset($_GET["js-purge"])){purge_js();exit;}
	if(isset($_GET["cache-popup"])){cache_popup();exit;}
	if(isset($_POST["save-cache"])){cache_save();exit;}
	if(isset($_POST["purge-perform"])){purge_save();exit;}
	if(isset($_POST["cache_delete"])){cache_delete();exit;}
	if(isset($_GET["delete-websites-js"])){websites_delete_js();exit;}
	
	if(isset($_GET["list"])){list_items();exit;}

table();


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
	echo "YahooWin4(900,'$page?cache-popup&ID=$ID','$title')";

}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=time();
	$purge_cache=$tpl->javascript_parse_text("{delete_cache}");
	echo "
	var xPurge$t = function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return}
		$('#NGINX_CACHE_TABLE').flexReload();
	}
	
	
	function Purge$t(){
		if(!confirm('$purge_cache: $ID ?')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('cache_delete','$ID');
		XHR.appendData('ID','$ID');
		XHR.sendAndLoad('$page', 'POST',xPurge$t);
	}
	
	 Purge$t()";
	
}

function purge_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=time();
	$purge_cache=$tpl->javascript_parse_text("{purge_cache}");

	echo "
	var xPurge$t = function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return}
		$('#NGINX_CACHE_TABLE').flexReload();
	}
	
	
	function Purge$t(){
		if(!confirm('$purge_cache: $ID ?')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('purge-perform','$ID');
		XHR.appendData('ID','$ID');
		XHR.sendAndLoad('$page', 'POST',xPurge$t);
	}
	
	 Purge$t()";
}

function cache_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_cache}";
	$bt="{add}";
	$ID=$_GET["ID"];
	$sock=new sockets();
	$t=time();
	if(!is_numeric($ID)){$ID=0;}
	
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
	
	
	
	$fontsize=22;
	
	$html[]="<div style='width:100%;font-size:28px;margin-bottom:20px'>$title</div>";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_text_table("keys_zone-$t","{name}",$ligne["keys_zone"],$fontsize,null,450);
	$html[]=Field_text_table("directory-$t","{directory}",$ligne["directory"],$fontsize,null,450);
	$html[]=Field_text_table("levels-$t","{levels}",$ligne["levels"],$fontsize,null,110);
	$html[]=Field_text_table("keys_zone_size-$t","{memory_size} (MB)",$ligne["keys_zone_size"],$fontsize,null,110);
	$html[]=Field_text_table("max_size-$t","{max_size} (GB)",$ligne["max_size"],$fontsize,null,110);
	$html[]=Field_text_table("inactive-$t","{inactive} ({minutes})",$ligne["inactive"],$fontsize,"{nginx_inactive_explain}",110);
	$html[]=Field_text_table("loader_files-$t","{loader_files}",$ligne["loader_files"],$fontsize,null,110);
	$html[]=Field_text_table("loader_sleep-$t","{loader_sleep} {milliseconds}",$ligne["loader_sleep"],$fontsize,null,110);
	$html[]=Field_text_table("loader_threshold-$t","{loader_threshold} {milliseconds}",$ligne["loader_threshold"],$fontsize,null,110);
	$html[]=Field_button_table_autonome($bt,"Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	var ID=$ID;
	if(results.length>3){alert(results);}
	$('#NGINX_CACHE_TABLE').flexReload();
	if(ID==0){ YahooWin4Hide();}
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('save-cache','$ID');
	XHR.appendData('ID','$ID');
	XHR.appendData('keys_zone',document.getElementById('keys_zone-$t').value);
	XHR.appendData('directory',document.getElementById('directory-$t').value);
	XHR.appendData('levels',document.getElementById('levels-$t').value);
	XHR.appendData('keys_zone_size',document.getElementById('keys_zone_size-$t').value);
	XHR.appendData('max_size',document.getElementById('max_size-$t').value);
	XHR.appendData('inactive',document.getElementById('inactive-$t').value);
	XHR.appendData('loader_files',document.getElementById('loader_files-$t').value);
	XHR.appendData('loader_sleep',document.getElementById('loader_sleep-$t').value);
	XHR.appendData('loader_threshold',document.getElementById('loader_threshold-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	

}


function cache_save(){
	unset($_POST["save-cache"]);
	$sqlZ=FORM_CONSTRUCT_SQL_FROM_POST("nginx_caches","ID");
	if($_POST["ID"]==0){$sql=$sqlZ[0];}else{$sql=$sqlZ[1];}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?build-main=yes");
	
}


function websites_delete_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$t=time();
	$servername=$_GET["servername"];
	$md=$_GET["md"];
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("$servername:: {delete_freeweb_nginx_text}");
	$html="
var xDeleteFreeWeb$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#NGINX_MAIN_TABLE').flexReload();

}

function DeleteFreeWeb$t(){
	if(confirm('$delete_freeweb_nginx_text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-servername','$servername');
	XHR.sendAndLoad('freeweb.php', 'GET',xDeleteFreeWeb$t);
}
DeleteFreeWeb$t();
";
echo $html;

}


function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();

	$users=new usersMenus();
	$servername=urlencode($_GET["servername"]);
	$t=time();
	header("content-type: application/x-javascript");
	$html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#NGINX_MAIN_TABLE').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','$servername');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

	";

	echo $html;

}

function move_items(){
	$q=new mysql_squid_builder();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zOrder FROM reverse_www WHERE servername='$ID'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}

	
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE reverse_www SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}


	$sql="UPDATE reverse_www SET zOrder=$NextOrder WHERE servername='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT servername FROM reverse_www ORDER by zOrder","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["servername"];

		$sql="UPDATE reverse_www SET zOrder=$c WHERE servername='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}

}
function table(){
	
	
	$sock=new sockets();
	$q=new mysql();
	$CountDeFreeWebs=$q->COUNT_ROWS("freeweb", "artica_backup");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("reverse_www")){$f=new squid_reverse();}
	if(!$q->TABLE_EXISTS("reverse_sources")){$f=new nginx_sources();$f->PatchTables();}
	if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";} }

	
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$maxsize=$tpl->javascript_parse_text("{maxsize}");
	$apply_parameters=$tpl->javascript_parse_text("{refresh_caches_status}");
	$name=$tpl->javascript_parse_text("{name}");
	$directory=$tpl->javascript_parse_text("{directory}");
	$new_cache=$tpl->javascript_parse_text("{new_cache}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:20px>$new_cache</strong>', bclass: 'add', onpress : New$t},
	{name: '<strong style=font-size:20px>$apply_parameters</strong>', bclass: 'apply', onpress :  apply_parameters$t},
	
	
	
	
	
	],	";
	$html="
	<table class='NGINX_CACHE_TABLE' style='display: none' id='NGINX_CACHE_TABLE' style='width:99%'></table>
	<script>
function BuildTable$t(){
	$('#NGINX_CACHE_TABLE').flexigrid({
	url: '$page?list=yes&t=$t',
		dataType: 'json',
			colModel : [
			{display: '<span style=font-size:18px>$name</span>', name : 'keys_zone', width :284, sortable : true, align: 'left'},
			{display: '<span style=font-size:18px>$directory</span>', name : 'directory', width :602, sortable : false, align: 'left'},
			
			{display: '<span style=font-size:18px>$maxsize</span>', name : 'maxsize', width :220, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'up', width :78, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'down', width :78, sortable : false, align: 'center'},

			],
			$buttons

	searchitems : [
	{display: '$name', name : 'name'},
	{display: '$directory', name : 'directory'},
	],
	sortname: 'keys_zone',
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
	Loadjs('nginx.caches.progress.php');
}
function purge_caches$t(){
	Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
	Loadjs('miniadmin.proxy.reverse.import.php');
}

function New$t(){
	Loadjs('$page?js-cache=yes&ID=0');
}
BuildTable$t();		
</script>";	
echo $html;
}

function purge_save(){
	$ID=intval($_POST["ID"]);
	if($ID>0){
		$sock=new sockets();
		$sock->getFrameWork("nginx.php?purge-cache=$ID");
	}

	sleep(4);


}

function cache_delete(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM nginx_caches WHERE ID='$ID'"));
	$directory=$ligne["directory"];

	$q->QUERY_SQL("DELETE FROM nginx_caches WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("UPDATE reverse_www SET cacheid=0 WHERE cacheid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}

	$sock=new sockets();
	$sock->getFrameWork("nginx.php?delete-cache=".urlencode(base64_decode($directory)));
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?build-main=yes");
}

function list_items(){
	$STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl"));
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$all_text=$tpl->_ENGINE_parse_body("{all}");
	$GLOBALS["CLASS_TPL"]=$tpl;
	$q=new mysql_squid_builder();
	$OrgPage="miniadmin.proxy.reverse.php";
	$sock=new sockets();
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	
	
	$FORCE=1;
	$search='%';
	$table="nginx_caches";
	$page=1;
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("{delete_freeweb_nginx_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	
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
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$CurrentPage=CurrentPageName();
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	
	
	$searchstring=string_to_flexquery();
	
	
	
	if(!AdminPrivs()){
		$sql="SELECT reverse_www.* FROM reverse_www,reverse_privs
		WHERE reverse_privs.servername=reverse_www.servername
		AND reverse_privs.uid='{$_SESSION["uid"]}' $searchstring ORDER BY servername LIMIT 0,250";
	}
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error);}
	$q1=new mysql();
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		
		$keys_zone=$ligne["keys_zone"];
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?js-delete=yes&ID={$ligne["ID"]}')");
		$jsedit="Loadjs('$MyPage?js-cache=yes&ID={$ligne["ID"]}')";
		$CurrentSize=$ligne["CurrentSize"];
		$CurrentSizeText=FormatBytes($CurrentSize/1024);
		$purge=imgsimple("dustbin-48.png",null,"Loadjs('$MyPage?js-purge=yes&ID={$ligne["ID"]}')");
	
		$data['rows'][] = array(
					'id' => $ligne['categorykey'],
					'cell' => array(
							"<span style='font-size:22px;font-weight:bold;padding-top:8px'>$keys_zone</span>",
							"<a href=\"javascript:blur();\"
							style='font-size:22px;font-weight:bold;text-decoration:underline'
							OnClick=\"javascript:$jsedit\">{$ligne["directory"]}</a>",
							"<span style='font-size:22px;font-weight:bold'>$CurrentSizeText/{$ligne["max_size"]}G</span>",	
							"<center>$purge</center>",
							"<center>$delete</center>"
							)
			);			
	}
	
	echo json_encode($data);
}

function AdminPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}

}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}