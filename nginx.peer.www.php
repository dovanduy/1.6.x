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
	
	if(isset($_GET["unlink-websites-js"])){unlink_js();exit;}
	if(isset($_POST["unlink-websites"])){unlink_perform();exit;}
	if(isset($_GET["list"])){list_items();exit;}	
table();


function unlink_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$_GET["servername"];
	header("content-type: application/x-javascript");
	$explain=$tpl->javascript_parse_text("{warnin_nginx_unlink_site}");
	$explain=str_replace("%s", $_GET["servername"], $explain);
	$t=time();
	$html="
var xDeleteFreeWeb$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#NGINX_MAIN_TABLE').flexReload();
	$('#NGINX_MAIN_TABLE2').flexReload();
}
	
function DeleteFreeWeb$t(){
	if(!confirm('$explain')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink-websites','$servername');
	XHR.sendAndLoad('$page', 'POST',xDeleteFreeWeb$t);
}
DeleteFreeWeb$t();
	";
	echo $html;	
	
}

function unlink_perform(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reverse_www SET `enabled`=0,cache_peer_id=0 WHERE `servername`='{$_POST["unlink-websites"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
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
	
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	{name: '$purge_caches', bclass: 'Delz', onpress :  purge_caches$t},
	{name: '$import_export', bclass: 'Down', onpress :  import_export$t},




	],	";
	
$buttons="
	buttons : [
	{name: '$new_server', bclass: 'add', onpress : New$t},
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	],	";
	
	$html="
	<table class='NGINX_MAIN_TABLE2' style='display: none' id='NGINX_MAIN_TABLE2' style='width:99%'></table>
	<script>
	function BuildTable$t(){
	$('#NGINX_MAIN_TABLE2').flexigrid({
	url: '$page?list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'severity', width :70, sortable : false, align: 'center'},
	{display: '$website', name : 'servername', width :560, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'compile', width :60, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width :60, sortable : false, align: 'center'},
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
	Loadjs('nginx.destination.progress.php?cacheid={$_GET["ID"]}')
}
function purge_caches$t(){
Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
Loadjs('miniadmin.proxy.reverse.import.php');
}

function New$t(){
Loadjs('nginx.new.php?peer-id={$_GET["ID"]}');
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


	$FORCE="cache_peer_id={$_GET["ID"]}";
	$search='%';
	$table="reverse_www";
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
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}

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
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$explain_text=NGINX_EXPLAIN_REVERSE($ligne["servername"]);
		$icon="clound-in-64.png";
		$freewebicon="64-firewall-search.png";
		$color="black";
		$status=array();
		$portText=null;
		
			
			
			$md=md5(serialize($ligne));
			$RedirectQueries=$ligne["RedirectQueries"];
			$default_server=$ligne["default_server"];
			
			$SiteEnabled=$ligne["enabled"];
			
			$servername_enc=urlencode($servername);
			$Compile=imgsimple("apply-48.png",null,"Loadjs('nginx.single.progress.php?servername=$servername_enc')");
			$limit_rate=$ligne["limit_rate"];
			$limit_rate_after=$ligne["limit_rate_after"];
			$DeleteFreeWeb="Loadjs('$MyPage?unlink-websites-js=yes&servername=$servername_enc&md=$md')";
			$icon2=imgsimple("reconfigure-48.png",null,"Loadjs('miniadmin.proxy.reverse.reconfigure.php?servername=$servername_enc')");
			$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&servername=$servername_enc&dir=0&t={$_GET["t"]}')");
			$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&servername=$servername_enc&dir=1&t={$_GET["t"]}')");




			if($ligne["DenyConf"]==1){$icon="hearth-blocked-64.png";}
			if($SiteEnabled==0){
				$icon="domain-main-64-grey.png";
				$color="#8a8a8a";
				$icon2="&nbsp;";
			}


			$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
			$jsedit=imgsimple($icon,null,"Loadjs('nginx.site.php?servername=$servername_enc')");
			$jsEditWW=$jsedit;
			$jseditA=$jsedit;
			$jseditC=imgsimple("script-48.png",null,"Loadjs('nginx.script.php?website-script-js=yes&servername=$servername_enc')");
			


			


			if($limit_rate>0){
				$limit_rate_after_caption=$tpl->_ENGINE_parse_body("{limit_rate_after_caption}");
				$limit_rate_after_caption=str_replace("%s", "{$limit_rate}MB/s", $limit_rate_after_caption);
				$limit_rate_after_caption=str_replace("%f", "{$limit_rate_after}MB", $limit_rate_after_caption);
				$status[]="<br><span style='font-size:12px;font-weight:bold;color:#EEB853'>$limit_rate_after_caption</span>";
			}


			if(count($status)>0){
				$status_text=$tpl->_ENGINE_parse_body("<div style='font-size:12px'>".@implode("", $status)."</div>");
			}

			$FreeWebText=null;
			

			if($EnableFreeWeb==0){
				if($ligne["ipaddr"]=="127.0.0.1"){$ligne["ipaddr"]="{error}";}
				if($ligne["cache_peer_id"]==0){$ligne["cache_peer_id"]=-1;}
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
				
				
				
			$data['rows'][] = array(
					'id' => $ligne['categorykey'],
					'cell' => array(
							"$jseditC",
							"<a href=\"javascript:blur();\"
							style='font-size:18px;font-weight:bold;text-decoration:underline'
							OnClick=\"javascript:Loadjs('nginx.site.php?servername=$servername_enc')\">$servername$portText</a>
							<br>$status_text
							$explain_text",
							$Compile,
							$delete
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