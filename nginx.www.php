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
	if(isset($_GET["move-item-js"])){move_items_js();exit;}
	if(isset($_POST["move-item"])){move_items();exit;}
	
	if(isset($_GET["delete-websites-js"])){websites_delete_js();exit;}
	
	if(isset($_GET["list"])){list_items();exit;}

table();

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
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$apply_parameters=$tpl->javascript_parse_text("{rebuild_all_websites}");
	$purge_caches=$tpl->javascript_parse_text("{purge_caches}");
	$import_export=$tpl->javascript_parse_text("{import_export}");
	$new_server=$tpl->javascript_parse_text("{new_server}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_server</strong>', bclass: 'add', onpress : New$t},
	{name: '<strong style=font-size:18px>$apply_parameters</strong>', bclass: 'apply', onpress :  apply_parameters$t},
	{name: '<strong style=font-size:18px>$purge_caches</strong>', bclass: 'Delz', onpress :  purge_caches$t},
	{name: '<strong style=font-size:18px>$import_export</strong>', bclass: 'Down', onpress :  import_export$t},
	
	
	
	
	],	";
	$html="
	<table class='NGINX_MAIN_TABLE' style='display: none' id='NGINX_MAIN_TABLE' style='width:99%'></table>
	<script>
function BuildTable$t(){
	$('#NGINX_MAIN_TABLE').flexigrid({
	url: '$page?list=yes&t=$t',
		dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'severity', width :70, sortable : false, align: 'center'},
			{display: '<span style=font-size:22px>$website</span>', name : 'servername', width :595, sortable : true, align: 'left'},
			{display: '<span style=font-size:22px>$destination</span>', name : 'icon2', width :425, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'up', width :65, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'down', width :65, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'compile', width :65, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'delete', width :65, sortable : false, align: 'center'},
			],
			$buttons

	searchitems : [
	{display: '$website', name : 'servername'},
	],
	sortname: 'zOrder',
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
	Loadjs('nginx.reconfigure.progress.php');
}
function purge_caches$t(){
	Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
	Loadjs('miniadmin.proxy.reverse.import.php');
}

function New$t(){
	Loadjs('nginx.new.php');
}
BuildTable$t();		
</script>";	
echo $html;
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
	if(!$q->FIELD_EXISTS("reverse_www", "zOrder")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `zOrder` smallint(100) NOT NULL default '0'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
	$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
	
	
	$FORCE=1;
	$search='%';
	$table="reverse_www";
	$page=1;
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("{delete_freeweb_nginx_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	$SSL_CLIENT_VERIFICATION=$tpl->javascript_parse_text("{SSL_CLIENT_VERIFICATION}");
	
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
		
		$status_text=null;
		$certificate_text=null;
		$icon="clound-in-64.png";
		$freewebicon="64-firewall-search.png";
		$color="black";
		$status=array();
		$color_blue="#1961FF";
		$portText=null;
		$ssl_client_certificate_text=null;
		$RedirectQueries=$ligne["RedirectQueries"];
		$zavail=$ligne["zavail"];
		
		$md=md5(serialize($ligne));
		
		$default_server=$ligne["default_server"];
		$explain_text=null;
		$SiteEnabled=$ligne["enabled"];
		$servername=$ligne["servername"];
		$servername_enc=urlencode($servername);
		$Compile=imgsimple("apply-48.png",null,"Loadjs('nginx.single.progress.php?servername=$servername_enc')");
		$limit_rate=$ligne["limit_rate"];
		$limit_rate_after=$ligne["limit_rate_after"];
		$ssl_backend=$ligne["ssl_backend"];
		$DeleteFreeWeb="Loadjs('$OrgPage?delete-websites-js=yes&servername=$servername_enc&md=$md')";
		$icon2=imgsimple("reconfigure-48.png",null,"Loadjs('miniadmin.proxy.reverse.reconfigure.php?servername=$servername_enc')");
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&servername=$servername_enc&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&servername=$servername_enc&dir=1&t={$_GET["t"]}')");
		
		$ssl_client_certificate=$ligne["ssl_client_certificate"];
		

		
		if($ligne["DenyConf"]==1){$icon="hearth-blocked-64.png";}
		if($SiteEnabled==0){
			$icon="domain-main-64-grey.png";
			$color="#8a8a8a";
			$icon2="&nbsp;";
			$color_blue=$color;
			
		}
	
		$NgINxDestColor=$color;
		$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
		$jsedit=imgsimple($icon,null,"Loadjs('nginx.site.php?servername=$servername_enc')");
		$jsEditWW=$jsedit;
		$jseditA=$jsedit;
		$jseditC=imgsimple("script-48.png",null,"Loadjs('nginx.script.php?website-script-js=yes&servername=$servername_enc')");
		if($RedirectQueries<>null){$NgINxDestColor="#8a8a8a";}
		
		if($zavail==0){
			$jseditC=imgsimple("warning42.png",null,
					"Loadjs('nginx.script.php?website-script-js=yes&servername=$servername_enc')");
		}
	
		
		$NGINX_DESTINATION_EXPLAIN=NGINX_DESTINATION_EXPLAIN($ligne["cache_peer_id"],$NgINxDestColor);
		
		
	
		if($SiteEnabled==1){
			if(isset($STATUS[$servername])){
				if($STATUS[$servername]["ACCP"]>0){
				$ac=FormatNumber($STATUS[$servername]["AC"]);
				$ACCP=FormatNumber($STATUS[$servername]["ACCP"]);
				$ACHDL=FormatNumber($STATUS[$servername]["ACHDL"]);
				$ACRAQS=FormatNumber($STATUS[$servername]["ACRAQS"]);
				if($STATUS[$servername]["ACCP"]>0){$ss=round($STATUS[$servername]["ACRAQS"]/$STATUS[$servername]["ACCP"],2);}
				
				$reading=FormatNumber($STATUS[$servername]["reading"]);
				$writing=FormatNumber($STATUS[$servername]["writing"]);
				$waiting=FormatNumber($STATUS[$servername]["waiting"]);
			
				
				$status[]="{active_connections}: $ac&nbsp;|&nbsp;{accepteds}: $ACCP<br>{handles}:$ACRAQS ($ss/{second})";
				$status[]="&nbsp;|&nbsp;{keepalive}: $waiting&nbsp;|&nbsp;{reading}: $reading&nbsp;|&nbsp;{writing}:$writing";
				}
			}
		}
	
	
		if($limit_rate>0){
				$limit_rate_after_caption=$tpl->_ENGINE_parse_body("{limit_rate_after_caption}");
				$limit_rate_after_caption=str_replace("%s", "{$limit_rate}MB/s", $limit_rate_after_caption);
				$limit_rate_after_caption=str_replace("%f", "{$limit_rate_after}MB", $limit_rate_after_caption);
				$status[]="<br><span style='font-size:18px;font-weight:bold;color:#EEB853'>$limit_rate_after_caption</span>";
		}
	
	
		if(count($status)>0){
			$status_text=$tpl->_ENGINE_parse_body("<br><span style='font-size:18px;color:$color'>".
					@implode("", $status)."</span>");
		}
	
		$FreeWebText=null;
		$explain_text=null;
	
		if($EnableFreeWeb==0){
			if($ligne["ipaddr"]=="127.0.0.1"){$ligne["ipaddr"]="{error}";}
			if($ligne["cache_peer_id"]==0){$ligne["cache_peer_id"]=-1;}
		}
	
	
	
	if(($ligne["ipaddr"]=="127.0.0.1") OR ($ligne["cache_peer_id"]==0)){
			
			$jsedit=imgsimple($icon,null,"Loadjs('freeweb.edit.php?hostname=$servername&t=$t')");
			$certificate_text=null;
			$explain_text=NGINX_EXPLAIN_REVERSE($ligne["servername"]);
			$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
			
			$jseditS=null;
			$freewebicon="domain-64.png";
			$FreeWebText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername&t=$t')\" style='color:$color'>127.0.0.1:82 (FreeWeb)</a>";
		}else{
			
			$explain_text=NGINX_EXPLAIN_REVERSE($ligne["servername"],"$color");
			if($ligne["port"]>0){$portText=":{$ligne["port"]}";}
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
			$OnlyTCP=$ligne2["OnlyTCP"];
			$FreeWebText="{$ligne2["servername"]}:{$ligne2["port"]}";
			
			if($OnlyTCP==0){
				$ligne["owa"]=0;
				if($ligne["ssl"]==1){
					if($ligne["port"]==80){$portText=$portText."/443";}
					if($ssl_client_certificate==1){
						$ssl_client_certificate_text="<br><strong style='color:$color_blue'>&laquo;&nbsp;$SSL_CLIENT_VERIFICATION&nbsp;&raquo;</strong>";
					}
					
				}
			}
			
			if($OnlyTCP==1){
				$certificate_text=null;
				$portText=$portText." <strong style='color:$color'>TCP</strong>";
			}
			
		}
	
		
		if($ligne["owa"]==1){
			$freewebicon="exchange-2010-64.png";
		}
	
		if($ligne["poolid"]>0){
			$freewebicon="64-cluster.png";
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='{$ligne["poolid"]}'"));
			$ligne["ipaddr"]=$ligne2["poolname"];
			
			}
	
		$stats=null;

	
			$FinalDestination="{$ligne["ipaddr"]}$FreeWebText";
	
	
				
			if($default_server==1){
				$servername="$servername ($all_text * )";
				$icon="free-web-64.png";
				if($SiteEnabled==0){
					$icon="free-web-64-grey.png";
				}
			}
			
			if($ssl_backend==1){
				$NGINX_DESTINATION_EXPLAIN=$NGINX_DESTINATION_EXPLAIN.
				$tpl->_ENGINE_parse_body("<li style='font-weight:bold;color:$color'>{UseSSL}</li>");
			}
			
			$data['rows'][] = array(
					'id' => $ligne['categorykey'],
					'cell' => array(
							"<center>$jseditC</center>",
							"<a href=\"javascript:blur();\"
							style='font-size:26px;font-weight:bold;text-decoration:underline;color:$color'
							OnClick=\"javascript:GoToNginxOption('$servername_enc')\">$servername$portText</a>
							$certificate_text$ssl_client_certificate_text$status_text
							$explain_text",
							"<span style='font-size:18px;font-weight:bold;color:$color'>$NGINX_DESTINATION_EXPLAIN</span>",
							"<center style='margin-top:10px'>$up</center>",
							"<center style='margin-top:10px'>$down</center>",
							"<center>$Compile</center>",
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