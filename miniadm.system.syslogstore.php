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
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["db-stats"])){tables_title();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["max_connections"])){tune_save();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search-events"])){events_table();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["search-members"])){members_list();exit;}
if(isset($_GET["new-member-js"])){member_jsp();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["username"])){member_save();exit;}
if(isset($_POST["members-delete"])){member_delete();exit;}
if(isset($_GET["events-daemon"])){events_daemon();exit;}
if(isset($_GET["search-events-daemon"])){events_table_daemon();exit;}
if(isset($_POST["LogsRotateDeleteSize"])){settings_save();exit;}

if(isset($_GET["database"])){database_section();exit;}
if(isset($_GET["search-database"])){database_search();exit;}
if(isset($_POST["syslog-delete"])){database_delete();exit;}
if(isset($_POST["mysqlserver"])){mysqlserver_save();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["search-dabatase-js"])){search_database_js();exit;}
if(isset($_GET["search-dabatase-popup"])){search_database_popup();exit;}
if(isset($_POST["QUERY_SYSLOG_DATE"])){search_database_popup_save();exit;}
tabs();


function tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$boot=new boostrap_form();
	$sock=new sockets();
	
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}	
	$array["{database}"]="$page?database=yes";
	
	$array["{settings}"]="$page?settings=yes";
	if($MySQLSyslogType==1){
		$array["{status}"]="$page?status=yes";
		$array["{members}"]="$page?members=yes";
		$array["{events} {mysql}"]="$page?events=yes";
		
	}
	$array["{events}"]="$page?events-daemon=yes";
	echo $boot->build_tab($array);

}
function events(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-events");

}
function events_daemon(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-events-daemon");	
}

function member_jsp(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_member}");
	header("content-type: application/x-javascript");	
	echo "YahooWin(700,'$page?member-popup=yes','$title')";
}

function members(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_member}", "Loadjs('$page?new-member-js=yes')"));
	echo $boot->SearchFormGen("User,Host","search-members",null,$EXPLAIN);
}

function search_database_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{advanced_search}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(600,'$page?search-dabatase-popup=yes','$title')";

}

function database_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$LINKS["LINKS"][]=array("LABEL"=>"{advanced_search}","JS"=>"Loadjs('$page?search-dabatase-js=yes')");
	echo $boot->SearchFormGen("filename,hostname,filetime","search-database",null,$LINKS);	
	
}

function search_database_popup(){
	$boot=new boostrap_form();
	$q=new mysql_storelogs();
	
	if(!isset($_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"])){
		$sql="SELECT DATE_FORMAT(filetime,'%Y-%m-%d') as filetime FROM files_info GROUP BY filetime ORDER BY filetime DESC";
		$results=$q->QUERY_SQL($sql);
	
	
		$dayz[null]="{select}";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$time=time_to_date(strtotime($ligne["filetime"]." 00:00:00"));
			$dayz[$ligne["filetime"]]=$time;
		}
		$_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"]=serialize($dayz);
	}
	
	
	
	
	if(!isset($_SESSION["QUERY_SYSLOG_HOST_FIELDZ"])){
		$sql="SELECT hostname FROM files_info GROUP BY hostname ORDER BY hostname ASC";
		$results=$q->QUERY_SQL($sql);
	
	
		$hostz[null]="{select}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$hostz[$ligne["hostname"]]=$ligne["hostname"];
		}
		
		$_SESSION["QUERY_SYSLOG_HOST_FIELDZ"]=serialize($hostz);

	}
	
	$LIMITS[50]=50;
	$LIMITS[250]=250;
	$LIMITS[500]=250;
	$LIMITS[1000]=1000;
	$LIMITS[2000]=2000;
	
	
	if(!isset($_SESSION["QUERY_SYSLOG_LIMIT"])){$_SESSION["QUERY_SYSLOG_LIMIT"]=250;}
	
	$boot->set_list("QUERY_SYSLOG_DATE", "{date}", unserialize($_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"]),$_SESSION["QUERY_SYSLOG_DATE"]);
	$boot->set_list("QUERY_SYSLOG_HOST", "{hostname}", unserialize($_SESSION["QUERY_SYSLOG_HOST_FIELDZ"]),$_SESSION["QUERY_SYSLOG_HOST"]);
	$boot->set_field("QUERY_SYSLOG_FILE", "{filename}", $_SESSION["QUERY_SYSLOG_FILE"]);
	$boot->set_list("QUERY_SYSLOG_LIMIT", "{rows}", $LIMITS,$_SESSION["QUERY_SYSLOG_LIMIT"]);
	$boot->set_button("{search}");
	$boot->set_CloseYahoo("YahooWin2");
	$boot->set_formdescription("{advanced_search_explain}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}
function search_database_popup_save(){
	while (list ($key, $value) = each ($_POST) ){
		$_SESSION[$key]=$value;
	}
}


function member_popup(){
	$page=CurrentPageName();
	$boot=new boostrap_form();	
	$tpl=new templates();
	$boot->set_field("ipaddr", "{ipaddr}", null,array("IPV4"=>true));
	$boot->set_field("username", "{username}", null,array("MANDATORY"=>True));
	$boot->set_fieldpassword("password", "{password}", null,array("MANDATORY"=>True));
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	$boot->set_CloseYahoo("YahooWin");
	echo $boot->Compile();
}

function events_table_daemon(){
	$tpl=new templates();
	$sock=new sockets();
	$pattern=base64_encode($_GET["search-events-daemon"]);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?logrotate-query=$pattern")));

	krsort($datas);
	while (list ($key, $line) = each ($datas) ){
		$newdate=null;//12:18:42 [3027]
		if(preg_match("#^(.*?)\s+\[([0-9]+)\]\s+(.*)#", $line,$re)){
			
			$time=$re[1];
			$pid=$re[2];
			$line=$re[3];
			$newdate=date("Y l F d H:i:s");

		}
		$class=LineToClass($line);

		
		$tr[]="
		<tr class='$class'>
		<td nowrap>$time</td>
		<td nowrap>$pid</td>
		<td width=95%>$line</td>
		</tr>
		";
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>

			<thead>
				<tr>
					<th width=1%>{date}</th>
					<th width=1%>&nbsp;</th>
					<th width=99%>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>

			</table>";

}

function events_table(){
	$tpl=new templates();
	$sock=new sockets();
	$pattern=base64_encode($_GET["search-events"]);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?syslogdb-query=$pattern")));

	krsort($datas);
	while (list ($key, $line) = each ($datas) ){
		$newdate=null;
		if(preg_match("#^([0-9]+)\s+([0-9\:]+)(.*)#", $line,$re)){
			$y=substr($re[1], 0,2);
			$m=substr($re[1], 2,4);
			$d=substr($re[1], 4,6);
			$datestr=date("Y")."-$m-$d {$re[2]}";
			$time=strtotime($datestr);
			$line=$re[3];
			$newdate=date("Y l F d H:i:s");
				
		}
		$class=LineToClass($line);

		$line=htmlentities($line);
		$tr[]="
		<tr class='$class'>
		<td nowrap>$newdate</td>
		<td width=95%>$line</td>
		</tr>
		";
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>

			<thead>
				<tr>
					<th width=1%>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>

			</table>";

}
function status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$boot=new boostrap_form();
	$sock=new sockets();
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}	
	if($MySQLSyslogType==1){status_server();return;}
}

function status_server(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('system.php?syslogdb-status=yes')));
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SYSLOG_DB",$ini,null,1);
	$t=time();
	$q=new mysql_storelogs();
	$sql="SHOW VARIABLES LIKE '%version%';";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["Variable_name"]=="slave_type_conversions"){continue;}
		$tt[]="	<tr>
		<td colspan=2><div style='font-size:14px'>{{$ligne["Variable_name"]}}:&nbsp;{$ligne["Value"]}</a></div></td>
		</tr>";
	}
	
	$STATUS=$q->SHOW_STATUS();
	$tt[]="
	<tr>
	<td colspan=2><div style='font-size:14px'>{Created_tmp_disk_tables}:&nbsp;{$STATUS["Created_tmp_disk_tables"]}</a></div></td>
	</tr>";
	$tt[]="
	<tr>
	<td colspan=2><div style='font-size:14px'>{Created_tmp_tables}:&nbsp;{$STATUS["Created_tmp_tables"]}</a></div></td>
	</tr>";
	$tt[]="
	<tr>
	<td colspan=2><div style='font-size:14px'>{Max_used_connections}:&nbsp;{$STATUS["Max_used_connections"]}</a></div></td>
	</tr>";
	
	
	$html="
	<div id='title-$t' style='font-size:16px;font-weight:bold'></div>
	<div style='width:95%' class=form>
	<table style='width:99%'>
	<tr>
	<td valign='top' style='width:35%'>$APP_SQUID_DB</td>
	<td valign='top' style='width:65%'>
	<table style='width:100%'>
	<tbody>
	<tr>
	<td colspan=2><div style='font-size:16px;font-weight:bold;margin-top:10px'>{mysql_engine}:</div></td>
	</tr>
	".@implode("", $tt)."
	</tbody>
	</table>
	</td>
	</tr>
	</table>
	</div>
	<script>
	function RefreshTableTitle$t(){
	LoadAjaxTiny('title-$t','$page?db-stats=yes&t=$t');
	}
	RefreshTableTitle$t();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function tables_title(){
	$q=new mysql_storelogs();
	
	$array=$q->COUNT_ALL_TABLES();
	if(!$q->ok){
		if($q->mysql_error==null){$q->mysql_error="MySQL error...";}
		$ff="<div style='font-size:18px'>$q->mysql_error</div>";
	}else{
		$ff="<div style='font-size:18px;margin-bottom:10px'>{$array[0]} Tables (".FormatBytes($array[1]/1024).")</div>";
	}
	echo "
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTableTitle{$_GET["t"]}()")."</div>
	$ff";

}

function GetPrivs(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
}

function settings(){
	$sock=new sockets();
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($MySQLSyslogType==1){tune();return;}	
	if($MySQLSyslogType==2){mysqlparams();return;}
}

function engine_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	
	
	
	
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	
	
	$boot=new boostrap_form();
	$boot->set_field("LogsRotateDeleteSize", "{delete_if_file_exceed} (MB)", $LogsRotateDeleteSize);
	$boot->set_field("LogsRotateDefaultSizeRotation", "{default_size_for_rotation} (MB)", $LogsRotateDefaultSizeRotation);
	$boot->set_field("SystemLogsPath", "{system_logs_path}", $SystemLogsPath,array("BROWSE"=>true));
	$boot->set_checkbox("LogRotateCompress", "{compress_files}", $LogRotateCompress);
	if($MySQLSyslogType==1){
		$boot->set_field("storage_files_path", "{storage_files_path}", $LogRotatePath,array("BROWSE"=>true));
		$boot->set_field("BackupMaxDays", "{max_day_in_database}", $BackupMaxDays);
		$boot->set_field("BackupMaxDaysDir", "{backup_folder}", $BackupMaxDaysDir,array("BROWSE"=>true));
	}
	
	return $boot->Compile()."<hr style='margin-bottom:10px'>";
	

	
}

function mysqlparams(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();	
	
	$boot=new boostrap_form();
	$sock=new sockets();
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	$username=$TuningParameters["username"];
	$password=$TuningParameters["password"];
	$mysqlserver=$TuningParameters["mysqlserver"];
	$ListenPort=$TuningParameters["RemotePort"];	
	$boot->set_field("mysqlserver", "{mysqlserver}", $mysqlserver);
	$boot->set_field("RemotePort", "{remote_port}", $ListenPort);
	$boot->set_field("username", "{username}", $username);
	$boot->set_fieldpassword("password", "{password}", $password);
	$boot->set_button("{apply}");
	$boot->set_formtitle("{mysql_parameters}");
	echo $tpl->_ENGINE_parse_body(engine_params().$boot->Compile());
}

function tune(){
$tpl=new templates();
$page=CurrentPageName();
$q=new mysql_squid_builder();
$users=new usersMenus();
$sock=new sockets();
$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
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
$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
if(!isset($SquidDBTuningParameters["net_read_timeout"])){$SquidDBTuningParameters["net_read_timeout"]=120;}


$VARIABLES=$q->SHOW_VARIABLES();


while (list ($key, $value) = each ($SquidDBTuningParameters) ){
	if(isset($SquidDBTuningParameters[$key])){
		if($GLOBALS["VERBOSE"]){echo "VARIABLES[$key]={$VARIABLES[$key]} SquidDBTuningParameters[$key]={$SquidDBTuningParameters[$key]}<br>\n";}
		if($VARIABLES[$key]==null){$VARIABLES[$key]=$SquidDBTuningParameters[$key];}
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
$boot->set_field("read_buffer_size", "{read_buffer_size} (MB)", $read_buffer_size);
$boot->set_field("read_rnd_buffer_size", "{read_rnd_buffer_size} (MB)", $read_rnd_buffer_size);
$boot->set_field("sort_buffer_size", "{sort_buffer_size} (MB)", $sort_buffer_size);
$boot->set_field("thread_stack", "Thread Stack", $thread_stack);

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
$boot->set_field("query_cache_size", "{query_cache_size} (MB)", $query_cache_size);
$boot->set_button("{apply}");
$boot->set_formdescription("{$server_buffers}M + {$total_per_thread_buffers}M = {$max_used_memory}$UNIT");
$boot->set_formtitle("{mysql_parameters}");

$html=engine_params().$boot->Compile();

echo $tpl->_ENGINE_parse_body($html);
}

function settings_save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
}

function tune_save(){
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	while (list ($key, $value) = each ($_POST) ){
		$SquidDBTuningParameters[$key]=$value;

	}

	$newdata=base64_encode(serialize($SquidDBTuningParameters));
	$sock->SaveConfigFile($newdata, "MySQLSyslogParams");
	$sock->getFrameWork("system.php?syslogdb-restart=yes");
}
function mysqlserver_save(){
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($key, $value) = each ($_POST) ){
		$SquidDBTuningParameters[$key]=$value;
	}
	$newdata=base64_encode(serialize($SquidDBTuningParameters));
	$sock->SaveConfigFile($newdata, "MySQLSyslogParams");
}

function database_search(){
	$page=1;
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_storelogs();	
	$table="files_info";
	$tableOrg=$table;
	$database=$q->database;
	$t=time();
	$delete_alert=$tpl->javascript_parse_text("{delete_this_item}");
	
	if($q->COUNT_ROWS($table,$database)==0){senderror("$table/$database is empty");}
	$searchstring=string_to_flexquery("search-database");

	
	$limit="LIMIT 0,250";

	
	if($_SESSION["QUERY_SYSLOG_LIMIT"]>0){
		$limit="LIMIT 0,{$_SESSION["QUERY_SYSLOG_LIMIT"]}";
	}
	$filters=array();
	$filters[]=SearchToSql("DATE_FORMAT(filetime,'%Y-%m-%d')",$_SESSION["QUERY_SYSLOG_DATE"]);
	$filters[]=SearchToSql("filename",$_SESSION["QUERY_SYSLOG_FILE"]);
	$filters[]=SearchToSql("hostname",$_SESSION["QUERY_SYSLOG_HOST"]);

	
	

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring ".@implode(" ", $filters)." ORDER BY `filetime` DESC LIMIT 0,250";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror("$q->mysql_error");}	
	if(mysql_num_rows($results)==0){senderror("Query return empty array");}
	$boot=new boostrap_form();
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5S=md5(serialize($ligne));
		$filename=$ligne["filename"];
		$hostname=$ligne["hostname"];
		$storeid=$ligne["storeid"];
		$taskid=$ligne["taskid"];
		$filesize=FormatBytes($ligne["filesize"]/1024);
		$filetime=$ligne["filetime"];
		$delete=imgsimple("delete-32.png",null,"Delete$t('$storeid','$md5S')");
		$action="&nbsp;";
		if(preg_match("#auth\.log-.*?#", $ligne["filename"])){
			$action=imgsimple("32-import.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}&storeid=$storeid')");
				
		}
		
		$download="<a href=\"$MyPage?download=$storeid&filename={$ligne["filename"]}&storeid=$storeid\"><img src='img/arrow-down-32.png'></a>";
		
		
		$js="Loadjs('logrotate.php?log-js=yes&filename=$filename&storeid=$storeid&t=1368560783');";
		$trlink=$boot->trswitch($js);
		$tr[]="
			<tr id='$md5S'>
				<td nowrap $trlink>$filetime</td>
				<td nowrap $trlink>$filename</td>
				<td nowrap $trlink>$hostname</td>
				<td nowrap $trlink>$filesize</td>
				<td width=1% align=center>$download</td>
				<td width=1% align=center>$action</td>
				<td width=1% align=center>$delete</td>
			</tr>
			";
	}	
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{filename}</th>
					<th>{hostname}</th>
					<th>{size}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>
			<script>
var memedb$t='';			
var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#'+memedb$t).remove();
}		
	
function Delete$t(ID,md){
	memedb$t=md;
	if(confirm('$delete_alert '+ID+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('syslog-delete',ID);
		XHR.sendAndLoad('$MyPage', 'POST', xDelete$t);
	}
}
</script>";	
	
}
function database_delete(){
	
	$q=new mysql_storelogs();
	if(!$q->DelteItem($_POST["syslog-delete"])){
		echo $q->mysql_error;
	}
}


function members_list(){
		$page=1;
		$MyPage=CurrentPageName();
		$users=new usersMenus();
		$tpl=new templates();
		$sock=new sockets();
		$q=new mysql_storelogs();
		$table="user";
		$tableOrg=$table;
		$database="mysql";
		$delete_alert=$tpl->javascript_parse_text("{delete}");
		$FORCE_FILTER=1;
		$t=$_GET["t"];
		if(!is_numeric($t)){$t=time();}
		
	
		if($q->COUNT_ROWS($table,$database)==0){senderror("$table/$database is empty");}
		$searchstring=string_to_flexquery("search-members");
		
	
		$sql="SELECT *  FROM `$table` WHERE  $FORCE_FILTER $searchstring ORDER BY `User`";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){senderror("$q->mysql_error");}
	
	
		
		if(mysql_num_rows($results)==0){senderror("Query return empty array, $sql");}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$password=$ligne["Password"];
			$array=array("host"=>$ligne["Host"],"user"=>$ligne["User"]);
			$databaseText=null;
	
			
			if($ligne["Host"]<>"%"){
					if($ligne["Host"]<>"localhost"){
						if(!preg_match("#^[0-9\%]+\.[0-9\%]+\.[0-9\%]+#", $ligne["Host"])){
							
						}
					}
			}
			
			$ligne["Host"]=str_replace("%","{all}",$ligne["Host"]);
			
			$md5S=md5("{$ligne["User"]}@{$ligne["Host"]}$databaseText");
			
			
			$delete=imgsimple("delete-32.png","{delete}","DeleteMysqlUser$t('". base64_encode(serialize($array))."','{$ligne["User"]}@{$ligne["Host"]}','$md5S')");
			if($ligne["User"]=="root"){
				$delete=null;
			}
			
			
			$js="Loadjs('$MyPage?selectDB-js=yes&host={$ligne["Host"]}&user={$ligne["User"]}&instance-id={$_GET["instance-id"]}&t=$t')";
			$tr[]="
			<tr class='$class' id='$md5S'>
			<td nowrap>{$ligne["User"]}@{$ligne["Host"]}</td>
			<td width=95% align=center>$delete</td>
			</tr>
			";
	
		
		}
	
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
		
			<thead>
				<tr>
					<th width=99%>{members}</th>
					<th width=1% align=center>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		
			</table>
			<script>
var memedb$t='';			
var x_DeleteMysqlUser= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#'+memedb$t).remove();
}		
	
function DeleteMysqlUser$t(arra,user,md){
	memedb$t=md;
	if(confirm('$delete_alert '+user+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('members-delete',arra);
		XHR.sendAndLoad('$MyPage', 'POST',x_DeleteMysqlUser);
	}
}
</script>
";
	
	
	}	
	
function member_save(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."<br>";}
	$server=trim($_POST["ipaddr"]);
	$username=trim($_POST["username"]);
	$password=trim(url_decode_special_tool($_POST["password"]));
	if($server=="*"){$server="%";}
	if($GLOBALS["VERBOSE"]){echo __LINE__." ->mysql()<br>";}
	$q=new mysql_storelogs();
		
	
	$sql="SELECT User FROM user WHERE Host='$server' AND User='$username'";
	if($GLOBALS["VERBOSE"]){echo $sql."<br>";}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"mysql"));
	if($GLOBALS["VERBOSE"]){echo "User:{$ligne["User"]}<br>";}
	if(trim($ligne["User"])==null){
		$sql="CREATE USER '$username'@'$server' IDENTIFIED BY '$password';";
		if($GLOBALS["VERBOSE"]){echo $sql."<br>";}
		if(!$q->EXECUTE_SQL($sql)){
			echo "CREATE USER user:$username\nHost:$server\n\n$q->mysql_error";
			return;
		}
	}
	
	
	$sql="GRANT ALL PRIVILEGES ON * . * TO '$username'@'$server' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
	
	if(!$q->EXECUTE_SQL($sql)){
		echo "user GRANT ALL PRIVILEGES ON:$username\nHost:$server\n\n$q->mysql_error";
		return;
	}
		
	$fADD[]='INSERT INTO `db` (`Host`, `Db`, `User`, `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`,';
	$fADD[]='`References_priv`, `Index_priv`, `Alter_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Create_view_priv`, `Show_view_priv`,';
	$fADD[]=' `Create_routine_priv`, `Alter_routine_priv`, `Execute_priv`, `Event_priv`, `Trigger_priv`) VALUES ';
	$fADD[]='("'.$server.'", "'.$q->database.'", "'.$username.'", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "N", "N", "Y", "Y")';
	$sqladd=@implode(" ", $fADD);
	$q->QUERY_SQL($sql,"mysql");
	if(!$q->ok){
		echo "$sqladd\n\n$q->mysql_error";
		return;
	}	
	
}
function member_delete(){

	$array=unserialize(base64_decode($_POST["members-delete"]));
	if(!is_array($array)){return;}
	$sql="DROP USER '{$array["user"]}'@'{$array["host"]}';";
	$q=new mysql_storelogs();
	

	if(!$q->EXECUTE_SQL($sql)){
		echo "user:{$array["user"]}\nHost:{$array["host"]}\n\n$q->mysql_error";
	}
	$q->QUERY_SQL("DELETE FROM `db` WHERE `Host`='{$array["host"]}' AND `User`='{$array["user"]}'","mysql");
	if(!$q->ok){
		echo "\n$q->mysql_error";
		return;
	}	
	

}
function download(){
	$filename=$_GET["filename"];
	$storeid=$_GET["storeid"];
	$sock=new sockets();
	$q=new mysql_storelogs();
	$WorkDir=dirname(__FILE__)."/ressources/logs/web/export";
	@mkdir($WorkDir,0777,true);
	@chmod($WorkDir, 0777);
	$destination="$WorkDir/$filename";
	if(is_file($destination)){
		$sock->getFrameWork("services.php?chowndir=$destination");
		@unlink($destination);
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT LENGTH(filecontent) as lent FROM files_store WHERE ID = '$storeid'"));
	writelogs("$storeid: {$ligne["lent"]} bytes $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
	$sql="SELECT filecontent INTO DUMPFILE '$destination' FROM files_store WHERE ID = '$storeid'";
	
	$q->QUERY_SQL($sql);
	if(!is_file($destination)){
		writelogs("$destination: No such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	if(!$q->ok){writelogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	$sock->getFrameWork("services.php?chowndir=$destination");
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($destination)));
	writelogs("$destination: $content_type",__FUNCTION__,__FILE__,__LINE__);
	
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($destination);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($destination);	
	
}