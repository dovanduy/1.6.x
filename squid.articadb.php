<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
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
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["title"])){tables_title();exit;}
	if(isset($_GET["schedules"])){schedules();exit;}
	if(isset($_POST["ListenPort"])){SaveParams();exit;}
	if(isset($_POST["per_thread_buffers"])){per_thread_buffers();exit;}
	if(isset($_POST["server_buffers"])){server_buffers();exit;}
	if(isset($_POST["total_memory"])){total_memory();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{mysql_statistics_engine}");
	$html="YahooWin('821','$page?tabs=yes','$title');";
	echo $html;	
	
}

function tabs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();

	$array["status"]='{status}';
	$array["popup"]='{parameters}';
	$array["members"]='{members}';
	
	
	
	//$array["restored"]='{restored}';

	while (list ($num, $ligne) = each ($array) ){
		if($num=="members"){
			$html[]= "<li><a href=\"squid.articadb.mysql.php?members=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		$html[]= "<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}

	$t=time();
	echo $tpl->_ENGINE_parse_body( "
			<div id=squidarticadb style='width:100%;font-size:14px'>
			<ul>". implode("\n",$html)."</ul>
			</div>
			<script>
			$(document).ready(function(){
			$('#squidarticadb').tabs();


});
			</script>");
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
	<table style='width:99%' class=form>
	<tr>
	<td valign='top'>$APP_SQUID_DB</td>
	<td valign='top'>
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
	<script>
		function RefreshTableTitle$t(){
			LoadAjaxTiny('title-$t','squid.artica.statistics.purge.php?title=yes&t=$t');
		}
		RefreshTableTitle$t();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	

}

function popup(){
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
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
	
	$VARIABLES=$q->SHOW_VARIABLES();
	while (list ($key, $value) = each ($SquidDBTuningParameters) ){
		if($VARIABLES[$key]==null){$VARIABLES=$SquidDBTuningParameters[$key];}
	
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
	
	
	
	$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
	if($server_buffers>$serverMem){$color="#EB0000";}
	
	$max_used_memory=$server_buffers+$total_per_thread_buffers;
	if($max_used_memory>$serverMem){$color="#EB0000";}
	
	$UNIT="M";
	if($max_used_memory>1000){$max_used_memory=round(($max_used_memory/1000),2);$UNIT="G";}	
	
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	$t=time();
	$html="
	<div id='$t-form'></div>
	
	<input type='hidden' id='$t-innodb_buffer_pool_size' value='$innodb_buffer_pool_size'>
	<input type='hidden' id='$t-innodb_additional_mem_pool_size' value='$innodb_additional_mem_pool_size'>
	<input type='hidden' id='$t-innodb_log_buffer_size' value='$innodb_log_buffer_size'>
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2><div style='font-size:18px'>{threads}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{read_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-read_buffer_size",$read_buffer_size,"font-size:16px;width:90px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{read_rnd_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-read_rnd_buffer_size",$read_rnd_buffer_size,"font-size:16px;width:90px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>	
		<td class=legend style='font-size:16px'>{sort_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-sort_buffer_size",$sort_buffer_size,"font-size:16px;width:90px;padding:3px")."&nbsp;M</td>
	</tr>	
		
	<tr>
		<td class=legend style='font-size:16px'>thread_stack:</td>
		<td style='font-size:16px'>". Field_text("$t-thread_stack",$thread_stack,"font-size:16px;width:90px;padding:3px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td colspan=2><hr></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{total_per_thread_buffers}:</td>
		<td style='font-size:22px' nowrap><div id='per_thread_buffers'><span style='color:$color'>$per_thread_buffers&nbsp;M x {$mysql->main_array["max_connections"]} = {$total_per_thread_buffers}M</span></div></td>
	</tr>
	</table>
	<table width=99% class=form>	
	<tr>
		<td colspan=2><div style='font-size:18px'>{server}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td valign='top'>". Field_text("$t-ListenPort",$ListenPort,"font-size:16px;width:90px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{key_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-key_buffer_size",$key_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>max_tmp_table_size:</td>
		<td style='font-size:16px'>". Field_text("$t-max_tmp_table_size",$max_tmp_table_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{query_cache_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-query_cache_size",$query_cache_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{server_buffers}:</td>
		<td style='font-size:22px' align='right'><div id='server_buffers'><span style='color:$color'>{$server_buffers}M</span></div></td>
	</tr>					
	<tr>
		<td class=legend style='font-size:16px'>{max_connections}:</td>
		<td style='font-size:16px'>". Field_text("$t-max_connections",$max_connections,"font-size:16px;width:60px;padding:3px")."&nbsp;</td>
	</tr>
	<tr>
		<td colspan=2><hr></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{total_memory}:</td>
		<td style='font-size:22px' align='right' nowrap><div id='total_memory'><span style='color:$color'>{$server_buffers}M + {$total_per_thread_buffers}M = {$max_used_memory}$UNIT</span></div></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr></td>
	</tr>
	<tr>
	<td>". button("{calculate}","ParseFormCalc$t()","16px")."</td>
	<td align='right'>". button("{apply}","ParseFormSave$t()","16px")."</td>
	</table>	
	<script>	
	var XSave$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(results.length>3){alert(results);return;};
		RefreshTab('squidarticadb');
		}
		

	
	var x_per_thread_buffers= function (obj) {
		var results=obj.responseText;
		if(results.length>0){document.getElementById('per_thread_buffers').innerHTML=results;}
		ParseFormCalc2$t();
	}
	var x_server_buffers= function (obj) {
		var results=obj.responseText;
		if(results.length>0){document.getElementById('server_buffers').innerHTML=results;}
		ParseFormCalc3$t();
	}	
	var x_total_memory= function (obj) {
		var results=obj.responseText;
		if(results.length>0){document.getElementById('total_memory').innerHTML=results;}
	}	
	
	
	
		function ParseFormCalc$t(){
			var XHR=GetDatas();
			XHR.appendData('per_thread_buffers','yes');
			document.getElementById('per_thread_buffers').innerHTML='<img src=\"img/loadingAnimation.gif\">';
			document.getElementById('server_buffers').innerHTML='<img src=\"img/loadingAnimation.gif\">';
			document.getElementById('total_memory').innerHTML='<img src=\"img/loadingAnimation.gif\">';
			XHR.sendAndLoad('$page', 'POST',x_per_thread_buffers);	
		}
		
		function ParseFormCalc2$t(){
			var XHR=GetDatas();
			XHR.appendData('server_buffers','yes');
			XHR.sendAndLoad('$page', 'POST',x_server_buffers);	
		}	

		function ParseFormCalc3$t(){
			var XHR=GetDatas();
			XHR.appendData('total_memory','yes');
			XHR.sendAndLoad('$page', 'POST',x_total_memory);	
		}	

		
	var x_ParseFormSave$t= function (obj) {
		var results=obj.responseText;
		RefreshTab('squidarticadb');
		
		
	}			
		
		function ParseFormSave$t(){
			var XHR=GetDatas();
			var port=document.getElementById('$t-ListenPort').value;
			if(port==3306){port=3307;}
			XHR.appendData('ListenPort',port);
			AnimateDiv('$t-form');
			XHR.sendAndLoad('$page', 'POST',x_ParseFormSave$t);
		}		
		

			AnimateDiv('$t');
	
	
		function GetDatas(){
			var XHR = new XHRConnection();
			XHR.appendData('read_buffer_size',document.getElementById('$t-read_buffer_size').value);
			XHR.appendData('read_rnd_buffer_size',document.getElementById('$t-read_rnd_buffer_size').value);
			XHR.appendData('sort_buffer_size',document.getElementById('$t-sort_buffer_size').value);
			XHR.appendData('thread_stack',document.getElementById('$t-thread_stack').value);
			
			XHR.appendData('key_buffer_size',document.getElementById('$t-key_buffer_size').value);
			XHR.appendData('max_tmp_table_size',document.getElementById('$t-max_tmp_table_size').value);
			XHR.appendData('innodb_buffer_pool_size',document.getElementById('$t-innodb_buffer_pool_size').value);
			XHR.appendData('innodb_additional_mem_pool_size',document.getElementById('$t-innodb_additional_mem_pool_size').value);
			XHR.appendData('innodb_log_buffer_size',document.getElementById('$t-innodb_log_buffer_size').value);
			XHR.appendData('query_cache_size',document.getElementById('$t-query_cache_size').value);
			XHR.appendData('max_connections',document.getElementById('$t-max_connections').value);			
			return XHR;
			
		}	
				
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function SaveParams(){
	$sock=new sockets();
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
	while (list ($key, $value) = each ($_POST) ){
		$SquidDBTuningParameters[$key]=$value;
		
	}	
	
	$newdata=base64_encode(serialize($SquidDBTuningParameters));
	$sock->SaveConfigFile($newdata, "SquidDBTuningParameters");
	$sock->getFrameWork("squid.php?artica-db-restart=yes");
	
}


function per_thread_buffers(){
	$users=new usersMenus();
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
	$color="black";
	$read_buffer_size=$_POST["read_buffer_size"];
	$read_rnd_buffer_size=$_POST["read_rnd_buffer_size"];
	$sort_buffer_size=$_POST["sort_buffer_size"];
	$thread_stack=$_POST["thread_stack"];
	$join_buffer_size=$_POST["join_buffer_size"];
	$per_thread_buffers=$sort_buffer_size+$read_rnd_buffer_size+$sort_buffer_size+$thread_stack+$join_buffer_size;
	$total_per_thread_buffers=$per_thread_buffers*$_POST["max_connections"];
	if($total_per_thread_buffers>$serverMem){$color="#EB0000";}
	echo "<span style='color:$color'>$per_thread_buffers&nbsp;M x {$_POST["max_connections"]} = {$total_per_thread_buffers}M</span>";

}

function server_buffers(){
	$color="black";
	$users=new usersMenus();
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
	$key_buffer_size=$_POST["key_buffer_size"];
	$max_tmp_table_size=$_POST["key_buffer_size"];
	$innodb_buffer_pool_size=$_POST["innodb_buffer_pool_size"];
	$innodb_additional_mem_pool_size=$_POST["innodb_additional_mem_pool_size"];
	$innodb_log_buffer_size=$_POST["innodb_log_buffer_size"];
	$query_cache_size=$_POST["query_cache_size"];
	$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
	if($server_buffers>$serverMem){$color="#EB0000";}
	echo "<span style='color:$color'>{$server_buffers}M</span>";

}

function total_memory(){
	$users=new usersMenus();
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);
	$read_buffer_size=$_POST["read_buffer_size"];
	$read_rnd_buffer_size=$_POST["read_rnd_buffer_size"];
	$sort_buffer_size=$_POST["sort_buffer_size"];
	$thread_stack=$_POST["thread_stack"];
	$join_buffer_size=$_POST["join_buffer_size"];
	$per_thread_buffers=$sort_buffer_size+$read_rnd_buffer_size+$sort_buffer_size+$thread_stack+$join_buffer_size;
	$total_per_thread_buffers=$per_thread_buffers*$_POST["max_connections"];
	$color="black";
	$key_buffer_size=$_POST["key_buffer_size"];
	$max_tmp_table_size=$_POST["max_tmp_table_size"];
	$innodb_buffer_pool_size=$_POST["innodb_buffer_pool_size"];
	$innodb_additional_mem_pool_size=$_POST["innodb_additional_mem_pool_size"];
	$innodb_log_buffer_size=$_POST["innodb_log_buffer_size"];
	$query_cache_size=$_POST["query_cache_size"];
	$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
	$max_used_memory=$server_buffers+$total_per_thread_buffers;
	if($max_used_memory>$serverMem){$color="#EB0000";}
	$UNIT="M";
	if($max_used_memory>1000){$max_used_memory=round(($max_used_memory/1000),2);$UNIT="G";}

	echo "<span style='color:$color'>{$server_buffers}M + {$total_per_thread_buffers}M = {$max_used_memory}$UNIT</span>";

}