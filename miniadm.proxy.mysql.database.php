<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if($GLOBALS["VERBOSE"]){if($_SESSION["AsWebStatisticsAdministrator"]){echo "<H1>AsWebStatisticsAdministrator = FALSE</H1>";}}
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search-events"])){events_table();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["statistics-today"])){statistics_today();exit;}
if(isset($_GET["statistics-today-graph-0"])){statistics_today_graph0();exit;}
if(isset($_POST["innodb_buffer_pool_size"])){tune_save();exit;}
if(isset($_GET["tune"])){tune();exit;}
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
	<H1>{mysql_statistics_engine}</H1>
	<p>{mysql_statistics_engine_params}</p>
	<div class=BodyContentWork id='$t'></div>

	<script>
		LoadAjax('$t','$page?tabs=yes');
	</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$boot=new boostrap_form();
	$array["{status}"]="$page?status=yes";
	$array["{parameters}"]="$page?tune=yes";
	if($q->TABLE_EXISTS("MySQLStats")){
		$array["{statistics}"]="$page?statistics=yes";
	}
	
	if(isset($_GET["title"])){
		echo $tpl->_ENGINE_parse_body("<H4>{mysql_statistics_engine}</H4><p>{mysql_statistics_engine_params}</p>");
		
	}
	
	//$array["{status}"]="$page?status=yes";
	$array["{events}"]="$page?events=yes";
	echo $boot->build_tab($array);
}
function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$t=time();
	$q=new mysql_squid_builder(true);
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
		LoadAjaxTiny('title-$t','squid.artica.statistics.purge.php?title=yes&t=$t');
	}
	RefreshTableTitle$t();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}
function events(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-events");
	
}

function events_table(){
	$tpl=new templates();
	$sock=new sockets();
	$pattern=base64_encode($_GET["search-events"]);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?squid-db-query=$pattern")));

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

function statistics(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$boot=new boostrap_form();
	$array["{today}"]="$page?statistics-today=yes";
	
	echo $boot->build_tab($array);	
	
}

function statistics_today(){
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t-1' style='width:990px;height:450px'></div>
	<div id='$t-2' style='width:990px;height:450px'></div>
	<div id='$t-3' style='width:990px;height:450px'></div>
	
	
	<script>
		Loadjs('$page?statistics-today-graph-0=yes&container=$t-1&field=questions');
		Loadjs('$page?statistics-today-graph-0=yes&container=$t-2&field=threads');
		Loadjs('$page?statistics-today-graph-0=yes&container=$t-3&field=queriesavg');
	</script>
	";
	echo $html;
}
function statistics_today_graph0() {
	$tpl=new templates();
	$field=$_GET["field"];
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT HOUR(zDate) as `time`,AVG($field) as $field,
	 DATE_FORMAT( zDate, '%Y-%m-%d' ) AS zDate
	 FROM MySQLStats GROUP BY `time`
	HAVING zDate=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY `time`";
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "alert('".$tpl->javascript_parse_text("Error $q->mysql_error\n$sql\n")."')";
		return;
	}
	
	if(mysql_num_rows($results)>0){
		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["time"];
			$ydata[]=$ligne[$field];
			$c++;
		}
	}
	if($field=="queriesavg"){$field="{requests}";}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{statistics} ". $tpl->_ENGINE_parse_body("$field/{hours}");
	$highcharts->yAxisTtitle=$field;
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("$field"=>$ydata);
	echo $highcharts->BuildChart();
		
}
function tune(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
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
	
	
	$html=$boot->Compile();
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function tune_save(){
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
	while (list ($key, $value) = each ($_POST) ){
		$SquidDBTuningParameters[$key]=$value;
	
	}
	
	$newdata=base64_encode(serialize($SquidDBTuningParameters));
	$sock->SaveConfigFile($newdata, "SquidDBTuningParameters");
	$sock->getFrameWork("squid.php?artica-db-restart=yes");	
}

// 	MySQLStats
	//zDate,uptime,threads,questions,squeries,opens,ftables,open,queriesavg
