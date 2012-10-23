<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.backup.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql-server-multi.inc');
	
	
	$usersprivs=new usersMenus();
	if(!$usersprivs->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."');";
		die();
		
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["per_thread_buffers"])){per_thread_buffers();exit;}
	if(isset($_POST["server_buffers"])){server_buffers();exit;}
	if(isset($_POST["total_memory"])){total_memory();exit;}
	if(isset($_POST["instance-id"])){save_settings();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$prefix=str_replace(".","_",$page);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{mysql_performancesM}');
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$html="YahooWin4('550','$page?popup=yes&instance-id={$_GET["instance-id"]}','$title')";
	echo $html;
}

function popup(){
	$t=time();
	$instance_id=$_GET["instance_id"];
	$page=CurrentPageName();
	$tpl=new templates();
	$mysql=new mysqlserver();
	if($instance_id>0){
		$mysql=new mysqlserver_multi($instance_id);
		
	}	
	
	$users=new usersMenus();
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);	
	$color="black";
	$VARIABLES=$mysql->SHOW_VARIABLES();

	
	if(!is_numeric($mysql->main_array["max_connections"])){$mysql->main_array["max_connections"]=$VARIABLES["max_connections"];}
	
	
	$read_buffer_size=$mysql->main_array["read_buffer_size"];
	if(!is_numeric($read_buffer_size)){$read_buffer_size=($VARIABLES["read_buffer_size"]/1024)/1000;}
	
	$read_rnd_buffer_size=$mysql->main_array["read_rnd_buffer_size"];
	if(!is_numeric($read_rnd_buffer_size)){$read_rnd_buffer_size=($VARIABLES["read_rnd_buffer_size"]/1024)/1000;}
		
	$sort_buffer_size=$mysql->main_array["sort_buffer_size"];
	if(!is_numeric($sort_buffer_size)){$sort_buffer_size=($VARIABLES["sort_buffer_size"]/1024)/1000;}	

	$thread_stack=$mysql->main_array["thread_stack"];
	if(!is_numeric($thread_stack)){$thread_stack=($VARIABLES["thread_stack"]/1024)/1000;}	
	
	$join_buffer_size=$mysql->main_array["join_buffer_size"];
	if(!is_numeric($join_buffer_size)){$join_buffer_size=($VARIABLES["join_buffer_size"]/1024)/1000;}		
	
	
	$per_thread_buffers=$sort_buffer_size+$read_rnd_buffer_size+$sort_buffer_size+$thread_stack+$join_buffer_size;
	
	$total_per_thread_buffers=$per_thread_buffers*$mysql->main_array["max_connections"];
	if($total_per_thread_buffers>$serverMem){$color="#EB0000";}	
	
	
	$key_buffer_size=$mysql->main_array["key_buffer_size"];
	if(!is_numeric($key_buffer_size)){$key_buffer_size=($VARIABLES["key_buffer_size"]/1024)/1000;}		
	
	$max_tmp_table_size=$mysql->main_array["max_tmp_table_size"];
	if(!is_numeric($max_tmp_table_size)){$max_tmp_table_size=($VARIABLES["max_tmp_table_size"]/1024)/1000;}		
	
	$innodb_buffer_pool_size=$mysql->main_array["innodb_buffer_pool_size"];
	if(!is_numeric($innodb_buffer_pool_size)){$innodb_buffer_pool_size=($VARIABLES["innodb_buffer_pool_size"]/1024)/1000;}		
	
	$innodb_additional_mem_pool_size=$mysql->main_array["innodb_additional_mem_pool_size"];
	if(!is_numeric($innodb_additional_mem_pool_size)){$innodb_additional_mem_pool_size=($VARIABLES["innodb_additional_mem_pool_size"]/1024)/1000;}	
	
	$innodb_log_buffer_size=$mysql->main_array["innodb_log_buffer_size"];
	if(!is_numeric($innodb_log_buffer_size)){$innodb_log_buffer_size=($VARIABLES["innodb_log_buffer_size"]/1024)/1000;}		
	
	$query_cache_size=$mysql->main_array["query_cache_size"];
	if(!is_numeric($query_cache_size)){$query_cache_size=($VARIABLES["query_cache_size"]/1024)/1000;}		
	
	
	
	
	$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
	if($server_buffers>$serverMem){$color="#EB0000";}	
	
	$max_used_memory=$server_buffers+$total_per_thread_buffers;
	if($max_used_memory>$serverMem){$color="#EB0000";}
	
	$UNIT="M";
	if($max_used_memory>1000){$max_used_memory=round(($max_used_memory/1000),2);$UNIT="G";}
	
	
	$html="
	<div id='$t-form'>
	<table width=99% class=form>
	<tr>
		<td colspan=2><div style='font-size:18px'>{threads}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{read_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-read_buffer_size",$read_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{read_rnd_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-read_rnd_buffer_size",$read_rnd_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	
	<tr>	
		<td class=legend style='font-size:16px'>{sort_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-sort_buffer_size",$sort_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>	
		
	<tr>
		<td class=legend style='font-size:16px'>thread_stack:</td>
		<td style='font-size:16px'>". Field_text("$t-thread_stack",$thread_stack,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
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
		<td class=legend style='font-size:16px'>{key_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-key_buffer_size",$key_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>max_tmp_table_size:</td>
		<td style='font-size:16px'>". Field_text("$t-max_tmp_table_size",$max_tmp_table_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{innodb_buffer_pool_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-innodb_buffer_pool_size",$innodb_buffer_pool_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{innodb_additional_mem_pool_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-innodb_additional_mem_pool_size",$innodb_additional_mem_pool_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{innodb_log_buffer_size}:</td>
		<td style='font-size:16px'>". Field_text("$t-innodb_log_buffer_size",$innodb_log_buffer_size,"font-size:16px;width:60px;padding:3px")."&nbsp;M</td>
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
		<td style='font-size:16px'>". Field_text("$t-max_connections",$mysql->main_array["max_connections"],"font-size:16px;width:60px;padding:3px")."&nbsp;</td>
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
	
	</div>       
	<script>
	
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
		YahooWin4Hide();
		CacheOff();
		
	}			
		
		function ParseFormSave$t(){
			var XHR=GetDatas();
			XHR.appendData('instance-id','$instance_id');
			AnimateDiv('$t-form');
			XHR.sendAndLoad('$page', 'POST',x_ParseFormSave$t);
		}
		
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


function save_settings(){
	
	$instance_id=$_POST["instance-id"];
	if($instance_id==0){
		
		$mysql=new mysqlserver();}else{
		$mysql=new mysqlserver_multi($instance_id);
	}
	
	
	
	while (list ($index, $line) = each ($_POST) ){

		$mysql->main_array[trim($index)]=trim($line);
		
	}
	
	$mysql->save();
	$sock=new sockets();
	$sock->SET_INFO("MySqlMemoryCheck", 1);	
}