<?php
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
	
	if(isset($_GET["skip_external_locking"])){save();exit;}
	
	if(isset($_GET["popup"])){popup();exit;}
	
	
	js();
	
	
function js(){
$page=CurrentPageName();
$prefix=str_replace(".","_",$page);
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{mysql_settings}');
$load="{$prefix}Load()";


if(isset($_GET["inline"])){
	$prefix2="<div id='mysql-parameters-div'></div>
	
	<script>";
	$suffix="</script>";
	$load="{$prefix}Load2()";
}




$html="
$prefix2
function {$prefix}Load(){
		YahooWin(879,'$page?popup=yes','$title');
	
	}

function {$prefix}Load2(){
	LoadAjax('mysql-parameters-div','$page?popup=yes&divcallback=mysql-parameters-div');

}

	

$load

$suffix
";
	
echo $html;	
}


function popup(){
	$page=CurrentPageName();
	$mysql=new mysqlserver();
	$net=new networking();
	$array=$net->ALL_IPS_GET_ARRAY();
	$sock=new sockets();	
	$users=new usersMenus();
	$MysqlBinAllAdresses=$sock->GET_INFO("MysqlBinAllAdresses");
	if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
	if(!is_numeric($MysqlBinAllAdresses)){$MysqlBinAllAdresses=0;}
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$divcallback=$_GET["divcallback"];
	$t=time();
	$array[null]="{loopback}";
	$array["all"]="{all}";
	
	if($instance_id>0){
		$mysql=new mysqlserver_multi($instance_id);
	}
	
	if($users->ZARAFA_INSTALLED){
		$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
		if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
		if($EnableZarafaTuning==1){
			if($instance_id==0){
				$ZarafTuningParameters=unserialize(base64_decode($sock->GET_INFO("ZarafaTuningParameters")));
				$zarafa_innodb_buffer_pool_size=$ZarafTuningParameters["zarafa_innodb_buffer_pool_size"];
				$zarafa_query_cache_size=$ZarafTuningParameters["zarafa_query_cache_size"];
				$zarafa_innodb_log_file_size=$ZarafTuningParameters["zarafa_innodb_log_file_size"];
				$zarafa_innodb_log_buffer_size=$ZarafTuningParameters["zarafa_innodb_log_buffer_size"];
				$zarafa_max_allowed_packet=$ZarafTuningParameters["zarafa_max_allowed_packet"];
				$zarafa_max_connections=$ZarafTuningParameters["zarafa_max_connections"];
				
				if(!is_numeric($zarafa_max_connections)){$zarafa_max_connections=150;}
				if(!is_numeric($zarafa_innodb_buffer_pool_size)){$zarafa_innodb_buffer_pool_size=round($memory/2.8);}
				if(!is_numeric($zarafa_innodb_log_file_size)){$zarafa_innodb_log_file_size=round($zarafa_innodb_buffer_pool_size*0.25);}
				if(!is_numeric($zarafa_innodb_log_buffer_size)){$zarafa_innodb_log_buffer_size=32;}
				if(!is_numeric($zarafa_max_allowed_packet)){$zarafa_max_allowed_packet=256;}
				if(!is_numeric($zarafa_query_cache_size)){$zarafa_query_cache_size=8;}

				
				if($zarafa_innodb_log_file_size>4000){$zarafa_innodb_log_file_size=2000;}
				
				$mysql->main_array["innodb_buffer_pool_size"]=$zarafa_innodb_buffer_pool_size;
				$mysql->main_array["innodb_log_file_size"]=$zarafa_innodb_log_file_size;
				$mysql->main_array["innodb_log_buffer_size"]=$zarafa_innodb_log_buffer_size;
				$mysql->main_array["max_allowed_packet"]=$zarafa_max_allowed_packet;
				$mysql->main_array["query_cache_size"]=$zarafa_query_cache_size;
				}
		}	
	}
	
	$bind=Field_array_Hash($array,"$t-bind-address",$mysql->main_array["bind-address"],null,null,0,"font-size:14px;padding:3px");
	
	$chars=Charsets();
	$charsets=Field_array_Hash($chars,"$t-default-character-set",$mysql->main_array["default-character-set"],null,null,0,"font-size:14px;padding:3px");

//Les devs de mysql conseillent un key_buffer de la taille de la somme de tous les fichiers .MYI dans le repertoire mysql.	
	
	$hover=CellRollOver();
$form="	
<input type='hidden' value='instance-id' id='instance-id' value='$instance_id'>
<center style='width:90%' class=form>
<table >
	<tr $hover>
		<td class=legend style='font-size:14px'>{skip-name-resolve}:</td>
		<td style='font-size:14px'>". Field_yesno_checkbox("$t-skip-name-resolve",$mysql->main_array["skip_name_resolve"])."</td>
		<td><code style='font-size:12px'>skip-name-resolve</code></td>
		<td style='font-size:14px'>". help_icon('{skip-name-resolve_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px'>{skip-external-locking}:</td>
		<td style='font-size:14px'>". Field_yesno_checkbox("$t-skip-external-locking",$mysql->main_array["skip_external_locking"])."</td>
		<td><code style='font-size:12px'>skip-external-locking</code></td>
		<td style='font-size:14px'>". help_icon('{skip-external-locking_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px'>{skip-character-set-client-handshake}:</td>
		<td style='font-size:14px'>". Field_yesno_checkbox("$t-skip-character-set-client-handshake",$mysql->main_array["skip-character-set-client-handshake"])."</td>
		<td><code style='font-size:12px'>skip-character-set-client-handshake</code></td>
		<td style='font-size:14px'>". help_icon('{skip-character-set-client-handshake_text}')."</td>
	</tr>	
	<tr $hover>
		<td class=legend>Default charset:</td>
		<td colspan=3>$charsets</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px'>{bind-address}:</td>
		<td>$bind</td>
		<td><code style='font-size:12px'>bind-address</code></td>
		<td>&nbsp;</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px'>{bind_all_addresses}:</td>
		<td style='font-size:14px'>". Field_checkbox("$t-MysqlBinAllAdresses",1,$MysqlBinAllAdresses,"MysqlBinAllAdressesCheck()")."</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px'>{key_buffer}:</td>
		<td style='font-size:14px'>". Field_text("$t-key_buffer",$mysql->main_array["key_buffer"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>key_buffer</code></td>
		<td style='font-size:14px'>". help_icon('{key_buffer_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{key_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-key_buffer_size",$mysql->main_array["key_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>key_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{key_buffer_size_text}')."</td>
	</tr>		
	<tr $hover>
		<td class=legend style='font-size:14px'>{innodb_buffer_pool_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-innodb_buffer_pool_size",$mysql->main_array["innodb_buffer_pool_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>innodb_buffer_pool_size</code></td>
		<td style='font-size:14px'>". help_icon('{innodb_buffer_pool_size_text}')."</td>
	</tr>
	
	<tr $hover>
		<td class=legend style='font-size:14px'>{innodb_additional_mem_pool_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-innodb_additional_mem_pool_size",$mysql->main_array["innodb_additional_mem_pool_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>innodb_additional_mem_pool_size</code></td>
		<td style='font-size:14px'>". help_icon('{innodb_additional_mem_pool_size_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px'>{innodb_log_file_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-innodb_log_file_size",$mysql->main_array["innodb_log_file_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>innodb_log_file_size</code></td>
		<td style='font-size:14px'>". help_icon('{innodb_log_file_size_text}')."</td>
	</tr>		
	<tr $hover>
		<td class=legend style='font-size:14px'>{innodb_log_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-innodb_log_buffer_size",$mysql->main_array["innodb_log_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>innodb_log_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{innodb_log_buffer_size_text}')."</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px'>{innodb_lock_wait_timeout}:</td>
		<td style='font-size:14px'>". Field_text("$t-innodb_lock_wait_timeout",$mysql->main_array["innodb_lock_wait_timeout"],"font-size:14px;width:60px;padding:3px")."&nbsp;{seconds}</td>
		<td><code style='font-size:12px'>innodb_lock_wait_timeout</code></td>
		<td style='font-size:14px'>". help_icon('{innodb_lock_wait_timeout_text}')."</td>
	</tr>	

	
	<tr $hover>
		<td class=legend style='font-size:14px'>{myisam_sort_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-myisam_sort_buffer_size",$mysql->main_array["myisam_sort_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>myisam_sort_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{myisam_sort_buffer_size_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px'>{sort_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-sort_buffer_size",$mysql->main_array["sort_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>sort_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{sort_buffer_size_text}')."</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px'>{join_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-join_buffer_size",$mysql->main_array["join_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>join_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{join_buffer_size_text}')."</td>
	</tr>		
	<tr $hover>
		<td class=legend style='font-size:14px'>{read_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-read_buffer_size",$mysql->main_array["read_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>read_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{read_buffer_size_text}')."</td>
	</tr>		
		<td class=legend style='font-size:14px;color:#D50A0A'>{query_cache_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-query_cache_size",$mysql->main_array["query_cache_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>query_cache_size</code></td>
		<td style='font-size:14px'>". help_icon('{query_cache_size_text}')."</td>
	</tr>		
	
	
	<tr $hover>
		<td class=legend style='font-size:14px'>{query_cache_limit}:</td>
		<td style='font-size:14px'>". Field_text("$t-query_cache_limit",$mysql->main_array["query_cache_limit"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>query_cache_limit</code></td>
		<td style='font-size:14px'>". help_icon('{query_cache_limit_text}')."</td>
	</tr>	
	
	

	
	
	
	
	
	<tr $hover>
		<td class=legend style='font-size:14px'>{read_rnd_buffer_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-read_rnd_buffer_size",$mysql->main_array["read_rnd_buffer_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>read_rnd_buffer_size</code></td>
		<td style='font-size:14px'>". help_icon('{read_rnd_buffer_size_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{table_cache}:</td>
		<td style='font-size:14px'>". Field_text("$t-table_cache",$mysql->main_array["table_cache"],"font-size:14px;width:60px;padding:3px")."&nbsp;table(s)</td>
		<td><code style='font-size:12px'>table_cache</code></td>
		<td style='font-size:14px'>". help_icon('{table_cache}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{max_heap_table_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-max_heap_table_size",$mysql->main_array["max_heap_table_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>max_heap_table_size</code></td>
		<td style='font-size:14px'>". help_icon('{max_heap_table_size_text}')."</td>
	</tr>	
	
	
	
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{tmp_table_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-tmp_table_size",$mysql->main_array["tmp_table_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>tmp_table_size</code></td>
		<td style='font-size:14px'>". help_icon('{tmp_table_size}')."</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px'>{max_allowed_packet}:</td>
		<td style='font-size:14px'>". Field_text("$t-max_allowed_packet",$mysql->main_array["max_allowed_packet"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>max_allowed_packet</code></td>
		<td style='font-size:14px'>". help_icon('{max_allowed_packet}')."</td>
	</tr>	
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{max_connections}:</td>
		<td style='font-size:14px'>". Field_text("$t-max_connections",$mysql->main_array["max_connections"],"font-size:14px;width:60px;padding:3px")."&nbsp;</td>
		<td><code style='font-size:12px'>max_connections</code></td>
		<td style='font-size:14px'>". help_icon('{max_connections}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{open_files_limit}:</td>
		<td style='font-size:14px'>". Field_text("$t-open_files_limit",$mysql->main_array["open_files_limit"],"font-size:14px;width:60px;padding:3px")."&nbsp;</td>
		<td><code style='font-size:12px'>open_files_limit</code></td>
		<td style='font-size:14px'>". help_icon('{open_files_limit_explain}')."</td>
	</tr>	
	
	
	
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{wait_timeout}:</td>
		<td style='font-size:14px'>". Field_text("$t-wait_timeout",$mysql->main_array["wait_timeout"],"font-size:14px;width:60px;padding:3px")."&nbsp;{seconds}</td>
		<td><code style='font-size:12px'>wait_timeout</code></td>
		<td style='font-size:14px'>". help_icon('{wait_timeout_text}')."</td>
	</tr>
	
	
	<tr $hover>
		<td class=legend style='font-size:14px'>{net_buffer_length}:</td>
		<td style='font-size:14px'>". Field_text("$t-net_buffer_length",$mysql->main_array["net_buffer_length"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>net_buffer_length</code></td>
		<td style='font-size:14px'>". help_icon('{net_buffer_length_text}')."</td>
	</tr>
	<tr $hover>
		<td class=legend style='font-size:14px;color:#D50A0A'>{thread_cache_size}:</td>
		<td style='font-size:14px'>". Field_text("$t-thread_cache_size",$mysql->main_array["thread_cache_size"],"font-size:14px;width:60px;padding:3px")."&nbsp;M</td>
		<td><code style='font-size:12px'>thread_cache_size</code></td>
		<td style='font-size:14px'>". help_icon('{thread_cache_size_text}')."</td>
	</tr>
	<tr>
		<td colspan=4 align='right'>
		<hr>". button("{apply}","SaveUMysqlParameters$t()",16)."
		
		</td>
	</tr>
	</table></div>";	
	
	$html="<div style='font-size:16px'>{mysql_settings} v. $mysql->mysql_version_string ($mysql->mysqlvbin)
	&nbsp;|&nbsp;<a href=\"javascript:blur();\" OnClick=\"Loadjs('mysql.perfs.php?instance-id=$instance_id')\" style='font-size:16px;text-decoration:underline'>{mysql_performancesM}</a></div>
	<div id='mysqlsettings'>$form</div>
	
	
	<script>
	function EnableZarafaTuningCheck(){
		var EnableZarafaTuning=$EnableZarafaTuning;
		if(EnableZarafaTuning==0){CheckZarafaValues();return;}
		if(document.getElementById('$t-innodb_buffer_pool_size')){document.getElementById('$t-innodb_buffer_pool_size').disabled=true;}
		if(document.getElementById('$t-query_cache_size')){document.getElementById('$t-query_cache_size').disabled=true;}
		if(document.getElementById('$t-innodb_log_file_size')){document.getElementById('i$t-nnodb_log_file_size').disabled=true;}
		if(document.getElementById('$t-innodb_log_buffer_size')){document.getElementById('$t-innodb_log_buffer_size').disabled=true;}
		if(document.getElementById('$t-max_allowed_packet')){document.getElementById('$t-max_allowed_packet').disabled=true;}
		if(document.getElementById('$t-max_connections')){document.getElementById('$t-max_connections').disabled=true;}
		CheckZarafaValues();
	}

	function MysqlBinAllAdressesCheck(){
		if(document.getElementById('$t-MysqlBinAllAdresses').checked){
			document.getElementById('$t-bind-address').disabled=true;
		}else{
			document.getElementById('$t-bind-address').disabled=false;
		}
	}

	function LockNetWorkFields(){
		var instance_id=$instance_id;
		if(instance_id>0){
			document.getElementById('$t-MysqlBinAllAdresses').disabled=true;
			document.getElementById('$t-bind-address').disabled=true;
		}
	
	}
	
	function CheckZarafaValues(){
		var EnableZarafaTuning=$EnableZarafaTuning;	
		if(EnableZarafaTuning==0){return;}
		document.getElementById('$t-innodb_log_buffer_size').disabled=true;
		document.getElementById('$t-innodb_buffer_pool_size').disabled=true;
		document.getElementById('$t-innodb_log_file_size').disabled=true;
		document.getElementById('$t-innodb_log_buffer_size').disabled=true;
		document.getElementById('$t-max_allowed_packet').disabled=true;
		document.getElementById('$t-query_cache_size').disabled=true;
	
	
	}
	
	
var x_SaveUMysqlParameters= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	var instance_id=$instance_id;
	if(instance_id>0){RefreshTab('main_config_instance_mysql_multi');return;}
	LoadAjax('$divcallback','$page?popup=yes&instance-id=$instance_id');
	}
	


function SaveUMysqlParameters$t(){
	var XHR = new XHRConnection();
	
	if(document.getElementById('$t-MysqlBinAllAdresses').checked){XHR.appendData('MysqlBinAllAdresses',1);}else{XHR.appendData('MysqlBinAllAdresses',0);}
	if(document.getElementById('$t-skip-external-locking').checked){XHR.appendData('skip_external_locking','yes');}else{XHR.appendData('skip_external_locking','no');}
	if(document.getElementById('$t-skip-character-set-client-handshake').checked){XHR.appendData('skip-character-set-client-handshake','yes');}else{XHR.appendData('skip-character-set-client-handshake','no');}
	if(document.getElementById('$t-skip-name-resolve').checked){XHR.appendData('skip_name_resolve','yes');}else{XHR.appendData('skip_name_resolve','no');}
	
	
	if(document.getElementById('$t-key_buffer')){XHR.appendData('key_buffer',document.getElementById('$t-key_buffer').value);}
	if(document.getElementById('$t-innodb_buffer_pool_size')){XHR.appendData('innodb_buffer_pool_size',document.getElementById('$t-innodb_buffer_pool_size').value);}
	if(document.getElementById('$t-innodb_additional_mem_pool_size')){XHR.appendData('innodb_additional_mem_pool_size',document.getElementById('$t-innodb_additional_mem_pool_size').value);}
	if(document.getElementById('$t-read_rnd_buffer_size')){XHR.appendData('read_rnd_buffer_size',document.getElementById('$t-read_rnd_buffer_size').value);}
	if(document.getElementById('$t-table_cache')){XHR.appendData('table_cache',document.getElementById('$t-table_cache').value);}
	if(document.getElementById('$t-tmp_table_size')){XHR.appendData('tmp_table_size',document.getElementById('$t-tmp_table_size').value);}
	if(document.getElementById('$t-max_allowed_packet')){XHR.appendData('max_allowed_packet',document.getElementById('$t-max_allowed_packet').value);}
	if(document.getElementById('$t-max_connections')){XHR.appendData('max_connections',document.getElementById('$t-max_connections').value);}
	if(document.getElementById('$t-myisam_sort_buffer_size')){XHR.appendData('myisam_sort_buffer_size',document.getElementById('$t-myisam_sort_buffer_size').value);}
	if(document.getElementById('$t-net_buffer_length')){XHR.appendData('net_buffer_length',document.getElementById('$t-net_buffer_length').value);}
	if(document.getElementById('$t-sort_buffer_size')){XHR.appendData('sort_buffer_size',document.getElementById('$t-sort_buffer_size').value);}
	if(document.getElementById('$t-join_buffer_size')){XHR.appendData('join_buffer_size',document.getElementById('$t-join_buffer_size').value);}
	if(document.getElementById('$t-read_buffer_size')){XHR.appendData('read_buffer_size',document.getElementById('$t-read_buffer_size').value);}
	if(document.getElementById('$t-key_buffer_size')){XHR.appendData('key_buffer_size',document.getElementById('$t-key_buffer_size').value);}
	if(document.getElementById('$t-thread_cache_size')){XHR.appendData('thread_cache_size',document.getElementById('$t-thread_cache_size').value);}
	if(document.getElementById('$t-query_cache_limit')){XHR.appendData('query_cache_limit',document.getElementById('$t-query_cache_limit').value);}
	if(document.getElementById('$t-query_cache_size')){XHR.appendData('query_cache_size',document.getElementById('$t-query_cache_size').value);}
	if(document.getElementById('$t-table_open_cache')){XHR.appendData('table_open_cache',document.getElementById('$t-table_open_cache').value);}
	if(document.getElementById('$t-bind-address')){XHR.appendData('bind-address',document.getElementById('$t-bind-address').value);}	
	if(document.getElementById('$t-default-character-set')){XHR.appendData('default-character-set',document.getElementById('$t-default-character-set').value);}
	
	if(document.getElementById('$t-innodb_log_file_size')){XHR.appendData('innodb_log_file_size',document.getElementById('$t-innodb_log_file_size').value);}
	if(document.getElementById('$t-innodb_log_buffer_size')){XHR.appendData('innodb_log_buffer_size',document.getElementById('$t-innodb_log_buffer_size').value);}
	if(document.getElementById('$t-innodb_lock_wait_timeout')){XHR.appendData('innodb_lock_wait_timeout',document.getElementById('$t-innodb_lock_wait_timeout').value);}
	if(document.getElementById('$t-wait_timeout')){XHR.appendData('wait_timeout',document.getElementById('$t-wait_timeout').value);}
	if(document.getElementById('$t-max_heap_table_size')){XHR.appendData('max_heap_table_size',document.getElementById('$t-max_heap_table_size').value);}
	if(document.getElementById('$t-open_files_limit')){XHR.appendData('open_files_limit',document.getElementById('$t-open_files_limit').value);}

	XHR.appendData('instance-id','$instance_id');
	AnimateDiv('mysqlsettings');
	XHR.sendAndLoad('$page', 'GET',x_SaveUMysqlParameters);	
}	
	
	
	EnableZarafaTuningCheck();
	MysqlBinAllAdressesCheck();
	LockNetWorkFields();
	
</script>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function save(){
	
	$instance_id=$_GET["instance-id"];
	if($instance_id==0){
		if(isset($_GET["MysqlBinAllAdresses"])){
			$sock=new sockets();
			$sock->SET_INFO("MysqlBinAllAdresses", $_GET["MysqlBinAllAdresses"]);
			unset($_GET["MysqlBinAllAdresses"]);
		}
	$mysql=new mysqlserver();}else{
		$mysql=new mysqlserver_multi($instance_id);
	}
	
	
	
	while (list ($index, $line) = each ($_GET) ){

		$mysql->main_array[trim($index)]=trim($line);
		
	}
	
	$mysql->save();
	
}

function Charsets(){
	
$f[]="big5";
$f[]="latin2";
$f[]="dec8";
$f[]="cp850";
$f[]="latin1";
$f[]="hp8";
$f[]="koi8r";
$f[]="swe7";
$f[]="ascii";
$f[]="ujis";
$f[]="sjis";
$f[]="cp1251";
$f[]="hebrew";
$f[]="tis620";
$f[]="euckr";
$f[]="latin7";
$f[]="koi8u";
$f[]="gb2312";
$f[]="greek";
$f[]="cp1250";
$f[]="gbk";
$f[]="cp1257";
$f[]="latin5";
$f[]="armscii8";
$f[]="utf8";
$f[]="ucs2";
$f[]="cp866";
$f[]="keybcs2";
$f[]="macce";
$f[]="macroman";
$f[]="cp852";
$f[]="cp1256";
$f[]="geostd8";
$f[]="binary";
$f[]="cp932";
$f[]="eucjpms";
	
	while (list ($index, $data) = each ($f) ){
		$newar[trim($data)]=strtoupper(trim($data));
	}
	ksort($newar);
	$newar[null]="--";
	return $newar;
}
	
?>