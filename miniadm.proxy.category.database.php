<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"<p>");
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");

if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){header("location:miniadm.logon.php");}



if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["innodb_buffer_pool_size"])){tune_save();exit;}
if(isset($_GET["section-categories"])){section_categories();exit;}
if(isset($_GET["categories-search"])){categories_search();exit;}

main_page();
exit;

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function content(){

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{APP_ARTICADB}</H1>
	<p>{category_database_explain_text}</p>
	<div id='$t-status'></div>
	<script>
		LoadAjax('$t-status','$page?tabs=yes');
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);


}

function section_categories(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	echo $tpl->_ENGINE_parse_body("<div style='float:left'><p>{catz_explain}</p></div>");
	$form=$boot->SearchFormGen("category","categories-search",$suffix);
	echo $form;
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{DisableArticaProxyStatistics_disabled_explain}</p>
				<center style='margin:30px;font-size:18px;text-decoration:underline'>
				<a href=\"javascript:Loadjs('squid.artica.statistics.php')\">{ARTICA_STATISTICS_TEXT}</a>
				</center>
				");
		return;
	}
	
	if(isset($_GET["title"])){
		echo $tpl->_ENGINE_parse_body("<H4>{APP_ARTICADB}</H4>
			<p>{category_database_explain_text}</p>");
		
	}
	
	$boot=new boostrap_form();
	$array["{status}"]="$page?status=yes";
	$array["{tasks}"]="miniadm.ajax.squid.schedules.php?TaskID=1";
	$array["{settings}"]="$page?settings=yes";
	$array["{categories}"]="$page?section-categories=yes";
	//$array["{status}"]="$page?status=yes";
	//$array["{events}"]="$page?events=yes";
	echo $boot->build_tab($array);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
}

function status(){

	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	if(!$users->ARTICADB_INSTALLED){
		$html=FATAL_ERROR_SHOW_128("{ARTICADB_NOT_INSTALLED_EXPLAIN}")."<center style='margin:80px'>
		<hr>".button("{install}", "Loadjs('squid.blacklist.upd.php')",16)."</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	$date=$sock->getFrameWork("squid.php?articadb-version=yes");
	$q=new mysql_catz();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$catz=$q->LIST_TABLES_CATEGORIES();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$sql="SHOW VARIABLES LIKE '%version%';";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error</p>";
	}else{
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["Variable_name"]=="slave_type_conversions"){continue;}
			$tt[]="	<tr>
			<td colspan=2><div style='font-size:14px'>{{$ligne["Variable_name"]}}:&nbsp;{$ligne["Value"]}</a></div></td>
			</tr>";
		}
	
	}
	
	$arrayV=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-nextversion=yes")));
	$REMOTE_VERSION=$arrayV["ARTICATECH"]["VERSION"];
	if($REMOTE_VERSION>$date){
		$REMOTE_SIZE=$arrayV["ARTICATECH"]["SIZE"];
		$REMOTE_SIZE=FormatBytes($REMOTE_SIZE/1024);
			$updaebutton="<div style='text-align:right'><hr>".button("{update}:{version} $REMOTE_VERSION ($REMOTE_SIZE)", "Loadjs('squid.blacklist.upd.php')",16)."</div>";
	}
	
	$nextcheck=$sock->getFrameWork("squid.php?articadb-nextcheck=yes");
	$nextcheck=intval($nextcheck);
		if($nextcheck>0){
		$nextcheck_text="
			<tr>
			<td colspan=2><div style='font-size:16px'>{next_check_in}:&nbsp;{$nextcheck}Mn</div></td>
			</tr>";
		}
	
		if($nextcheck<0){
			$nextcheck=str_replace("-", "", $nextcheck);
					$nextcheckTime=time()-(intval($nextcheck)*60);
					$nextcheckTimeText=distanceOfTimeInWords($nextcheckTime,time());
					$nextcheck_text="
					<tr>
					<td colspan=2><div style='font-size:16px'>{last_check}:&nbsp;$nextcheckTimeText</div></td>
					</tr>";
			}
	
	
	
	$dbsize=$sock->getFrameWork("squid.php?articadbsize=yes");
	$items=numberFormat($q->COUNT_CATEGORIES(),0,""," ");
				$html="
				<table style='width:100%' class=TableRemove>
				<tr>
					<td width=130px' ><img src='img/spider-database-128.png'></td>
					<td valign='top'>
				<table style='width:99%'>
				<tr>
				<td valign='top' style='width:320px'>$APP_ARTICADB$APP_SQUID_DB</td>
				<td valign='top' style='padding-left:10px'>
				<table style='width:100%'>
				<tbody>
				<tr>
				<td colspan=2><div style='font-size:16px'>{pattern_database_version}:&nbsp;$date&nbsp($dbsize)</div></td>
				</tr>
				$nextcheck_text
				<tr>
				<td colspan=2><div style='font-size:16px'>{categories}:&nbsp;<strong>".count($catz)."</strong></a></div></td>
	
						</tr>
						<tr>
						<td colspan=2><div style='font-size:16px'>{categorized_websites}:&nbsp;<strong>$items</strong>&nbsp</div></td>
						</tr>
						<tr>
						<td colspan=2><div style='font-size:16px;font-weight:bold;margin-top:10px'>{mysql_engine}:</div></td>
						</tr>
						".@implode("", $tt)."
						</tbody>
						</table>
						</td>
						</tr>
						</table>
		$updaebutton
		</td>
		</tr>
		</table>
		";
		echo $tpl->_ENGINE_parse_body($html);	
	
}

function settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_catz();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLCatzParams")));
	$query_cache_size=$SquidDBTuningParameters["query_cache_size"];
	$max_allowed_packet=$SquidDBTuningParameters["max_allowed_packet"];
	$max_connections=$SquidDBTuningParameters["max_connections"];
	$connect_timeout=$SquidDBTuningParameters["connect_timeout"];
	$interactive_timeout=$SquidDBTuningParameters["interactive_timeout"];
	$key_buffer_size=$SquidDBTuningParameters["key_buffer_size"];
	$table_open_cache=$SquidDBTuningParameters["table_open_cache"];
	$myisam_sort_buffer_size=$SquidDBTuningParameters["myisam_sort_buffer_size"];
	$ListenPort=$SquidDBTuningParameters["ListenPort"];
	$tmpdir=$SquidDBTuningParameters["tmpdir"];
	
	
	if(!isset($SquidDBTuningParameters["net_read_timeout"])){$SquidDBTuningParameters["net_read_timeout"]=120;}
	
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
	
	$VARIABLES=$q->SHOW_VARIABLES();
	
	
	
	if(is_array($SquidDBTuningParameters)){
		while (list ($key, $value) = each ($SquidDBTuningParameters) ){
			if(isset($SquidDBTuningParameters[$key])){
				if($GLOBALS["VERBOSE"]){echo "VARIABLES[$key]={$VARIABLES[$key]} MySQLCatzParams[$key]={$SquidDBTuningParameters[$key]}<br>\n";}
				if($VARIABLES[$key]==null){$VARIABLES[$key]=$SquidDBTuningParameters[$key];}
			}
		
		}
	}
	
	
	$read_buffer_size=round(($VARIABLES["read_buffer_size"]/1024)/1000,2);
	$read_rnd_buffer_size=round(($VARIABLES["read_rnd_buffer_size"]/1024)/1000,2);
	$sort_buffer_size=round(($VARIABLES["sort_buffer_size"]/1024)/1000,2);
	$thread_stack=round(($VARIABLES["thread_stack"]/1024)/1000,2);
	$join_buffer_size=round(($VARIABLES["join_buffer_size"]/1024)/1000,2);
	$max_tmp_table_size=round(($VARIABLES["max_tmp_table_size"]/1024)/1000,2);
	$innodb_log_buffer_size=round(($VARIABLES["innodb_log_buffer_size"]/1024)/1000,2);
	$innodb_additional_mem_pool_size=round(($VARIABLES["innodb_additional_mem_pool_size"]/1024)/1000,2);
	$innodb_log_buffer_size=round(($VARIABLES["innodb_log_buffer_size"]/1024)/1000,2);
	$innodb_buffer_pool_size=round(($VARIABLES["innodb_buffer_pool_size"]/1024)/1000,2);
	$max_connections=$VARIABLES["max_connections"];
	$per_thread_buffers=$sort_buffer_size+$read_rnd_buffer_size+$sort_buffer_size+$thread_stack+$join_buffer_size;
	
	$total_per_thread_buffers=$per_thread_buffers*$max_connections;
	if($total_per_thread_buffers>$serverMem){$color="#EB0000";}
	
	
	$query_cache_size=round(($VARIABLES["query_cache_size"]/1024)/1000,2);
	$key_buffer_size=round(($VARIABLES["key_buffer_size"]/1024)/1000,2);
	if($tmpdir==null){$tmpdir="/tmp";}
		
		$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
		if($server_buffers>$serverMem){$color="#EB0000";}
	
		$max_used_memory=$server_buffers+$total_per_thread_buffers;
		if($max_used_memory>$serverMem){$color="#EB0000";}
	
		$UNIT="M";
		if($max_used_memory>1000){$max_used_memory=round(($max_used_memory/1000),2);$UNIT="G";}
	
		if(!is_numeric($ListenPort)){$ListenPort=0;}
	
		$boot=new boostrap_form();
		$boot->set_hidden("innodb_buffer_pool_size", $innodb_buffer_pool_size);
		$boot->set_hidden("innodb_additional_mem_pool_size", $innodb_additional_mem_pool_size);
		$boot->set_hidden("innodb_log_buffer_size", $innodb_log_buffer_size);
		$boot->set_spacertitle("{threads}:");
		$boot->set_field("read_buffer_size", "{read_buffer_size} (MB)", $read_buffer_size,array("TOOLTIP"=>"{read_buffer_size_text}"));
		$boot->set_field("read_rnd_buffer_size", "{read_rnd_buffer_size} (MB)", $read_rnd_buffer_size,array("TOOLTIP"=>"{read_rnd_buffer_size_text}"));
		$boot->set_field("sort_buffer_size", "{sort_buffer_size} (MB)", $sort_buffer_size,array("TOOLTIP"=>"{sort_buffer_size_text}"));
		$boot->set_field("thread_stack", "Thread Stack", $thread_stack,array("TOOLTIP"=>"{thread_stack_text}"));
	
		$boot->set_spacertitle("{server}:");
		$boot->set_field("ListenPort", "{listen_port}", $ListenPort);
		$boot->set_field("tmpdir", "{working_directory}", $tmpdir,array(
				"BUTTON"=>array(
						"LABEL"=>"{browse}",
						"JS"=>"Loadjs('SambaBrowse.php?no-shares=yes&field=%f&no-hidden=yes')")
		));
	
		
		$boot->set_field("net_read_timeout", "{net_read_timeout} ({seconds})", $SquidDBTuningParameters["net_read_timeout"]);
		$boot->set_field("max_connections", "{max_connections}", $max_connections);
		$boot->set_field("key_buffer_size", "{key_buffer_size} (MB)", $key_buffer_size);
		$boot->set_field("max_tmp_table_size", "MAX TMP Table size (MB)", $max_tmp_table_size);
		$boot->set_field("query_cache_size", "{query_cache_size} (MB)", $query_cache_size,array("TOOLTIP"=>"{thread_stack_text}"));
		$boot->set_button("{apply}");
		$boot->set_formdescription("{$server_buffers}M + {$total_per_thread_buffers}M = {$max_used_memory}$UNIT");
	
	
		$html=$boot->Compile();
	
		echo $tpl->_ENGINE_parse_body($html);
	}
	
	function tune_save(){
		$sock=new sockets();
		$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLCatzParams")));
		while (list ($key, $value) = each ($_POST) ){
			$SquidDBTuningParameters[$key]=$value;
	
		}
	
		$newdata=base64_encode(serialize($SquidDBTuningParameters));
		$sock->SaveConfigFile($newdata, "MySQLCatzParams");
		$sock->getFrameWork("squid.php?artica-catz-restart=yes");
	}
function categories_search(){
	$tpl=new templates();
	$catz=new mysql_catz();
	$tables=$catz->LIST_TABLES_CATEGORIES();
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();
	
	$search=string_to_flexregex("categories-search");
	$TransArray=$catz->TransArray();
	while (list ($key, $value) = each ($tables) ){
		
		$categoryname=$TransArray[$key];
		$text_category=$tpl->_ENGINE_parse_body($dans->array_blacksites[$categoryname]);
		if(!isset($dans->array_blacksites[$categoryname])){continue;}
		if($dans->array_pics[$categoryname]<>null){$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
		$CTCOUNT=$catz->COUNT_ROWS($key);
		if($CTCOUNT==0){continue;}
		$items=numberFormat($CTCOUNT,0);
		if($search<>null){if(!preg_match("#$search#", $categoryname)){
			
			if(!preg_match("#$search#", $text_category)){continue;}
		}}
		
		
		$tr[]="
		<tr id='$id'>
		<td width=1% nowrap>$pic</td>
		<td><i class='icon-globe'></i>&nbsp;<strong>$categoryname</strong><div>$text_category</div></td>
		<td nowrap><i class='icon-info-sign'></i>&nbsp;<span style='font-size:18px'>$items</span></td>
		</tr>";		
		
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th colspan=2>{category}</th>
					<th>{websites}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
}
