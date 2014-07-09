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
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["list"])){list_items();exit;}

table();


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
	$apply_parameters=$tpl->javascript_parse_text("{apply_parameters}");
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
	{name: '$new_server', bclass: 'add', onpress : New$t},
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	{name: '$purge_caches', bclass: 'Delz', onpress :  purge_caches$t},
	{name: '$import_export', bclass: 'Down', onpress :  import_export$t},
	
	
	
	
	],	";
	$html="<H1> BETA MODE DON't USE</H1>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
function BuildTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&t=$t',
		dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'severity', width :70, sortable : false, align: 'center'},
			{display: '$website', name : 'servername', width :406, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'stats', width : 70, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'free-icon', width :70, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'icon2', width :70, sortable : false, align: 'center'},
			{display: '$destination', name : 'icon2', width :197, sortable : false, align: 'center'},
			{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},
			],
			$buttons

	searchitems : [
	{display: '$website', name : 'servername'},
	],
	sortname: 'servername',
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
	Loadjs('nginx.site.php?servername=');
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
	
	$FORCE=1;
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
		
		
		
		$icon="clound-in-64.png";
		$freewebicon="64-firewall-search.png";
		$color="black";
		$status=array();
		$portText=null;
		if($ligne["ssl"]==1){
		$certificate_text=$tpl->_ENGINE_parse_body("<div>{certificate}: {default}</div>");;}
		$md=md5(serialize($ligne));
		$RedirectQueries=$ligne["RedirectQueries"];
		$default_server=$ligne["default_server"];
		$explain_text=null;
		$SiteEnabled=$ligne["enabled"];
		$servername=$ligne["servername"];
		$servername_enc=urlencode($servername);
		$limit_rate=$ligne["limit_rate"];
		$limit_rate_after=$ligne["limit_rate_after"];
		$DeleteFreeWeb="Loadjs('$OrgPage?delete-websites-js=yes&servername=$servername_enc&md=$md')";
		$icon2=imgsimple("reconfigure-48.png",null,"Loadjs('miniadmin.proxy.reverse.reconfigure.php?servername=$servername_enc')");
		
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
		$jseditC=imgsimple("script-64.png",null,"Loadjs('$OrgPage?website-script-js=yes&servername=$servername_enc')");
		
		
		
		if($ligne["certificate"]<>null){
			$certificate_text="".$tpl->_ENGINE_parse_body("<div>{certificate}: {$ligne["certificate"]}</div>");;
		}
	
	
		if(isset($STATUS[$servername])){
			$ac=FormatNumber($STATUS[$servername]["AC"]);
			$ACCP=FormatNumber($STATUS[$servername]["ACCP"]);
			$ACHDL=FormatNumber($STATUS[$servername]["ACHDL"]);
			$ACRAQS=FormatNumber($STATUS[$servername]["ACRAQS"]);
			if($STATUS[$servername]["ACCP"]>0){
				$ss=round($STATUS[$servername]["ACRAQS"]/$STATUS[$servername]["ACCP"],2);
			}
			
			$reading=FormatNumber($STATUS[$servername]["reading"]);
			$writing=FormatNumber($STATUS[$servername]["writing"]);
			$waiting=FormatNumber($STATUS[$servername]["waiting"]);
		
			
			$status[]="{active_connections}: $ac&nbsp;|&nbsp;{accepteds}: $ACCP<br>{handles}:$ACRAQS ($ss/{second})";
			$status[]="&nbsp;|&nbsp;{keepalive}: $waiting&nbsp;|&nbsp;{reading}: $reading&nbsp;|&nbsp;{writing}:$writing";
		}
	
	
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
		$explain_text=null;
	
		if($EnableFreeWeb==0){
			if($ligne["ipaddr"]=="127.0.0.1"){$ligne["ipaddr"]="{error}";}
			if($ligne["cache_peer_id"]==0){$ligne["cache_peer_id"]=-1;}
		}
	
	
	
	if(($ligne["ipaddr"]=="127.0.0.1") OR ($ligne["cache_peer_id"]==0)){
			
			$jsedit=imgsimple($icon,null,"Loadjs('freeweb.edit.php?hostname=$servername&t=$t')");
			$certificate_text=null;
			$explain_text=EXPLAIN_REVERSE($ligne["servername"]);
			$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
			$jseditS=null;
			$freewebicon="domain-64.png";
			$FreeWebText="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername&t=$t')\">127.0.0.1:82 (FreeWeb)</a>";
		}else{
			$explain_text=EXPLAIN_REVERSE($ligne["servername"]);
			if($ligne["port"]>0){$portText=":{$ligne["port"]}";}
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
			$OnlyTCP=$ligne2["OnlyTCP"];
			$FreeWebText="{$ligne2["servername"]}:{$ligne2["port"]}";
			//$jseditS=$boot->trswitch("Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')");
			if($OnlyTCP==0){
				$ligne["owa"]=0;
				if($ligne["ssl"]==1){if($ligne["port"]==80){$portText=$portText."/443";}}
			}
			
			if($OnlyTCP==1){
				$certificate_text=null;
				$portText=$portText." <strong>TCP</strong>";
			}
			
		}
	
		
		if($ligne["owa"]==1){
			$freewebicon="exchange-2010-64.png";
		}
	
		if($ligne["poolid"]>0){
			$freewebicon="64-cluster.png";
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='{$ligne["poolid"]}'"));
			$ligne["ipaddr"]=$ligne2["poolname"];
			//$jseditS=$boot->trswitch("Loadjs('miniadmin.proxy.reverse.nginx-pools.php?poolid-js={$ligne["poolid"]}&pool-id={$ligne["poolid"]}')");
			}
	
			$sql="SELECT * FROM nginx_aliases WHERE servername='$servername' $searchstring ORDER BY alias LIMIT 0,250";
			$results2=$q->QUERY_SQL($sql);
			$ali=array();$alitext=null;
			while($ligne=mysql_fetch_array($results2,MYSQL_ASSOC)){$ali[]="{$ligne["alias"]}";}
				if(count($ali)>0){$alitext="&nbsp;<i>(" .@implode("{or} ", $ali).")</i>&nbsp;"		;}
				$stats=null;
				$ligne2=mysql_fetch_array($q1->QUERY_SQL("SELECT COUNT(*) as tcount FROM awstats_files WHERE servername='$servername'","artica_backup"));
				if(!$q1->ok){echo "<p class=text-error>$q1->mysql_error</p>";}
				if($ligne2["tcount"]>0){
					// Abandonné pour l'instant
					//$stats="<a href='miniadmin.proxy.reverse.awstats.php?servername=".urlencode($servername)."'><img src='img/statistics-48.png'></a>";
				}
	
			$FinalDestination="{$ligne["ipaddr"]}$FreeWebText";
			if($RedirectQueries<>null){
				$FinalDestination=$RedirectQueries;
				$explain_text=$tpl->_ENGINE_parse_body("<br>{RedirectQueries_explain_table}");
			}
	
				
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
							$alitext$certificate_text$status_text
							$explain_text",
							$stats,
							"<img src='img/$freewebicon' style='width:64px'>",
							"$icon2",
							"<span style='font-size:18px;font-weight:bold'>$FinalDestination</span>",
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

FUNCTION  EXPLAIN_REVERSE($servername){
	$q=new mysql_squid_builder();
	$servernameencode=urlencode($servername);
	


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$proxy_buffering=$ligne["proxy_buffering"];
	
	
	$ssl="{proto} (HTTP) ";

			if($ligne["ssl"]==1){
			$ssl="{proto} (HTTP<b>S</b>) ";

			if($ligne["port"]==80){
			$ssl="{proto} (HTTP) {and} {proto} (HTTP<b>S</b>) ";
			}
			}



			$page=CurrentPageName();
			$cache_peer_id=$ligne["cache_peer_id"];
			if($cache_peer_id>0){
				$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port,ForceRedirect,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
				if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";}
				$ForceRedirect="<br>{ForceRedirectyes_explain_table}";
				if($ligne["ForceRedirect"]==0){ $ForceRedirect="<br>{ForceRedirectno_explain_table}"; }
				if($ligne["ssl"]==1){ $ssl="{proto} (HTTP<b>S</b>) "; }
				if($ligne["OnlyTCP"]==1){ $ssl="{proto} TCP";$ForceRedirect=null; }
				$js="Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')";



				$exp[]="<i style='font-size:12px'>$ssl";
				if($cache_peer_id>0){
					$exp[]="{redirect_communications_to}";
					$exp[]="<br>{$ligne["servername"]} {address} {$ligne["ipaddr"]}<br>{on_port} {$ligne["port"]} id:$cache_peer_id";
					$exp[]=$ForceRedirect;
				}
			}

			$sql="SELECT * FROM nginx_exploits WHERE servername='$servername' LIMIT 0,5";
			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){senderror($q->mysql_error);}

			$filters=array();
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$groupid=$ligne["groupid"];
				$jsedit="Loadjs('miniadmin.nginx.exploits.groups.php?js-group=yes&ID=$groupid&servername={$_GET["servername"]}')";
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM nginx_exploits_items WHERE groupid='$groupid'"));
				$RulesNumber=$ligne2["tcount"];
				$AF="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsedit\" style='text-decoration:underline'>";
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM nginx_exploits_groups WHERE ID='$groupid'"));
				$filters[]="{group} $AF{$ligne2["groupname"]} ($RulesNumber {items})</a>";
			}
			if(count($filters)>0){
				$exp[]="<br>{check_anti_exploit_with}:".@implode(", ", $filters);
			}

			$jsban="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.nginx.exploits.php?firewall-js=yes&servername=$servername')\"
			style='text-decoration:underline'>";
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT maxaccess,sendlogs FROM nginx_exploits_fw WHERE servername='$servername'"));
			if($ligne["maxaccess"]>0){
			$exp[]="<br>{bann_ip_after} $jsban{$ligne["maxaccess"]} {events}</a>";
}
if($ligne["sendlogs"]==1){$exp[]=",&nbsp;{write_logs_for} {$jsban}403 {errors}</a>";}

$proxy_buffering_text="<br><span style='color:#00B726'>{remote_webpages_are_cached}</span>";

print_r($ligne);

if($ligne["proxy_buffering"]==0){$proxy_buffering_text="<br><span style='color:#878787'>{caching_webpages_is_disabled}</span>";}
$exp[]=$proxy_buffering;
			$exp[]="<i>";
			$tpl=new templates();
	return $tpl->_ENGINE_parse_body(@implode(" ", $exp));

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}