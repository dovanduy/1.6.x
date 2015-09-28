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
	
	
function js(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{new_server}");
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_sources WHERE ID='$ID'"));
		$title=$ligne["servername"];
	}
	
	echo "YahooWin2(890,'$page?tabs=yes&ID=$ID','$title');";
	
}

function tabs(){
	$ID=intval($_GET["ID"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$title=$tpl->javascript_parse_text("{new_server}");
	$sock=new sockets();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	if($EnableNginx==0){
	
		echo FATAL_ERROR_SHOW_128("
		{enable_reverse_proxy_service}
		<center style='margin:30px'>". button("{enable_reverse_proxy_service}","Loadjs('$page?EnableNginx=yes')",42)."</center>");
		return;
	
	}
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM reverse_sources WHERE ID='$ID'"));
		$title=$ligne["ipaddr"];
	}
	
	
	
	$array["general-settings"]=$title;
	if($ID>0){
		$array["web-servers"]='{web_servers}';
	}
	
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="web-servers"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.peer.www.php?ID=$ID\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.events.php?ID=$ID\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="destinations"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.destinations.php?ID=$ID\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes&ID=$ID\"><span >$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_source_$ID")."<script>";
	
	
	
}



	
function general_settings(){
	
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$fields_size=22;
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$t=time();
	$sslcertificates=$rv->ssl_certificates_list();
	$sslcertificates[null]="{none}";
	
	
	$results=$q->QUERY_SQL("SELECT ID,keys_zone FROM nginx_caches ORDER BY keys_zone LIMIT 0,250");
	$nginx_caches[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_caches[$ligne2["ID"]]=$ligne2["keys_zone"];
	
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$ID'"));
	if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2 style='font-size:28px;padding-bottom:20px'>{main_parameters} {$ligne["ipaddr"]}:{$ligne["port"]}</td></tr>";
	$html[]=Field_text_table("servername-$t","{name}",$ligne["servername"],$fields_size,null,450);
	$html[]=Field_ipv4_table("ipaddr-$t","{ipaddr}",$ligne["ipaddr"],$fields_size,null,110);
	$html[]=Field_text_table("port-$t","{inbound_port}",$ligne["port"],$fields_size,null,110);
	$html[]=Field_checkbox_table("ssl-$t", "{UseSSL}",$ligne["ssl"],$fields_size);
	
	
	
	$html[]=Field_text_table("forceddomain-$t","{forceddomain}",$ligne["forceddomain"],$fields_size,null,450);
	
	
	$html[]=Field_text_table("remote_path-$t","{root_directory}",$ligne["remote_path"],$fields_size,null,450);
	$html[]=Field_list_table("cacheid-$t","{cache_directory}",$ligne["cacheid"],$fields_size,$nginx_caches,null,450);
	$html[]=Field_list_table("certificate-$t","{certificate}",$ligne["certificate"],$fields_size,$sslcertificates,null,450);
	
	
	$html[]=Field_button_table_autonome("{apply}","Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
	<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#NGINX_MAIN_TABLE').flexReload();
		$('#NGINX_DESTINATION_MAIN_TABLE').flexReload();
		var ID=$ID
		if(ID==0){ YahooWin2Hide();}
	
	}
	
	
	function Submit$t(){
		var ssl=0
		var XHR = new XHRConnection();
		XHR.appendData('general-settings','$ID');
		if(document.getElementById('ssl-$t').checked){ss=1;}
		XHR.appendData('servername',document.getElementById('servername-$t').value);
		XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
		XHR.appendData('ssl',ssl);
		XHR.appendData('port',document.getElementById('port-$t').value);
		
		XHR.appendData('forceddomain',document.getElementById('forceddomain-$t').value);
		var pp=encodeURIComponent(document.getElementById('remote_path-$t').value);
		XHR.appendData('remote_path',document.getElementById('remote_path-$t').value);
		XHR.appendData('cacheid',document.getElementById('cacheid-$t').value);
		XHR.appendData('certificate',document.getElementById('certificate-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	</script>
		
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function general_settings_save(){
	$_POST["remote_path"]=url_decode_special_tool($_POST["remote_path"]);
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_sources", "cacheid")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `cacheid` INT(10) NOT NULL DEFAULT '0'");}
	if(!$q->FIELD_EXISTS("reverse_sources", "certificate")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `certificate` CHAR(128) NULL");}
	
	$ID=intval($_POST["general-settings"]);
	if($ID>0){
	$q->QUERY_SQL("UPDATE `reverse_sources` SET
			`servername`='{$_POST["servername"]}',
			`ipaddr`='{$_POST["ipaddr"]}',
			`remote_path`='{$_POST["remote_path"]}',
			`forceddomain`='{$_POST["forceddomain"]}',
			`port`='{$_POST["port"]}',
			`cacheid`='{$_POST["cacheid"]}',
			`ssl`='{$_POST["ssl"]}',
			`certificate`='{$_POST["certificate"]}'
			WHERE ID='{$_POST["general-settings"]}'");
			if(!$q->ok){echo $q->mysql_error;}
	}else{
		$q->QUERY_SQL("INSERT IGNORE INTO `reverse_sources` (`ssl`,`servername`,`ipaddr`,`remote_path`,`forceddomain`,`port`,`cacheid`,`certificate`)
		VALUES('{$_POST["ssl"]}','{$_POST["servername"]}','{$_POST["ipaddr"]}','{$_POST["remote_path"]}','{$_POST["forceddomain"]}','{$_POST["port"]}','{$_POST["cacheid"]}','{$_POST["certificate"]}')");
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	
}
