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
	
	
	if(isset($_GET["general-settings"])){general_settings();exit;}
	if(isset($_POST["general-settings"])){general_settings_save();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	
	

	page();

	
function page(){
	
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$fields_size=22;
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$t=time();
	$servername=$_GET["servername"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$cache_peer_id=$ligne["cache_peer_id"];
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT cacheid FROM reverse_sources WHERE ID='$cache_peer_id'"));
	if(intval($ligne2)==0){
		echo FATAL_ERROR_SHOW_128("{nginx_error_no_cache_defined}");
		return;
		
	}

	

	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2 style='font-size:40px;padding-bottom:20px'>{caching}</td></tr>";
	$html[]=Field_checkbox_table("proxy_buffering-$t", "{proxy_buffering}",$ligne["proxy_buffering"],$fields_size,"{proxy_buffering_text}");
	
	$html[]=Field_text_table("proxy_cache_min_uses-$t","{proxy_cache_min_uses}",$ligne["proxy_cache_min_uses"],$fields_size,"{proxy_cache_min_uses_text}",120);
	$html[]=Field_text_table("proxy_cache_valid-$t","{proxy_cache_valid} ({minutes})",$ligne["proxy_cache_valid"],$fields_size,"{proxy_cache_valid_text}",120);
	$html[]=Field_ipv4_table("proxy_buffers-$t","{proxy_buffers}",$ligne["proxy_buffers"],$fields_size,"{proxy_buffers_text}",120);
	$html[]=Field_text_table("proxy_buffer_size-$t","{proxy_buffer_size} (k)",$ligne["proxy_buffer_size"],$fields_size,"{proxy_buffer_size_text}",110);
	$html[]=Field_text_table("proxy_temp_file_write_size-$t","{proxy_temp_file_write_size} (k)",$ligne["proxy_temp_file_write_size"],$fields_size,"{proxy_temp_file_write_size_text}",110);
	$html[]=Field_text_table("proxy_max_temp_file_size-$t","{proxy_max_temp_file_size} (MB)",$ligne["proxy_max_temp_file_size"],$fields_size,"{proxy_max_temp_file_size_text}",110);
	
	


	
	
	
	$html[]=Field_button_table_autonome("{apply}","Submit$t",40);
	$html[]="</table>";
	$html[]="</div>
	<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('main_nginx_server');
		$('#NGINX_MAIN_TABLE').flexReload();
		$('#NGINX_DESTINATION_MAIN_TABLE').flexReload();
		var ID=$ID
		if(ID==0){ YahooWin2Hide();}
	
	}
	
	
	function Submit$t(){
		var proxy_buffering=0
		var XHR = new XHRConnection();
		XHR.appendData('general-settings','$servername');
		if(document.getElementById('proxy_buffering-$t').checked){proxy_buffering=1;}
		XHR.appendData('proxy_cache_min_uses',document.getElementById('proxy_cache_min_uses-$t').value);
		XHR.appendData('proxy_buffering',proxy_buffering);
		XHR.appendData('proxy_cache_valid',document.getElementById('proxy_cache_valid-$t').value);
		XHR.appendData('proxy_buffers',document.getElementById('proxy_buffers-$t').value);
		XHR.appendData('proxy_buffer_size',document.getElementById('proxy_buffer_size-$t').value);
		
		XHR.appendData('proxy_max_temp_file_size',document.getElementById('proxy_max_temp_file_size-$t').value);
		XHR.appendData('proxy_temp_file_write_size',document.getElementById('proxy_temp_file_write_size-$t').value);
		
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	</script>
		
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function general_settings_save(){
	
	$q=new mysql_squid_builder();
	
	$servername=$_POST["general-settings"];

	$sql="UPDATE `reverse_www` SET
			`proxy_buffering`='{$_POST["proxy_buffering"]}',
			`proxy_cache_min_uses`='{$_POST["proxy_cache_min_uses"]}',
			`proxy_cache_valid`='{$_POST["proxy_cache_valid"]}',
			`proxy_buffers`='{$_POST["proxy_buffers"]}',
			`proxy_buffer_size`='{$_POST["proxy_buffer_size"]}',
			`proxy_max_temp_file_size`='{$_POST["proxy_max_temp_file_size"]}',
			`proxy_temp_file_write_size`='{$_POST["proxy_temp_file_write_size"]}'
			WHERE `servername`='$servername'";
	$q->QUERY_SQL($sql);
	
			if(!$q->ok){echo $q->mysql_error;}
	
}
