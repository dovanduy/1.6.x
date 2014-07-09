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
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["main"])){main();exit;}
	if(isset($_POST["servername-edit"])){Save();exit;}

js();


function js(){
	$servername=$_GET["servername"];
	$servername_enc=urlencode($_GET["servername"]);
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{settings}:: $servername");
	$html="YahooWin(900,'$page?tabs=yes&t={$_GET["t"]}&servername=$servername_enc','$title')";
	echo $html;	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$servername=$_GET["servername"];
	$servername_text=$servername;
	$servername_enc=urlencode($_GET["servername"]);
	if($servername==null){$servername_text="{new_server}";}
	
	$array["main"]="$servername_text";
	if($servername<>null){
		$array["ssl"]="{ssl}";
		
	}
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="ssl"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.site.ssl.php?servername=$servername_enc\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"apache.watchdog-events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes&servername=$servername_enc\"><span >$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_nginx_server");	
	
}

function main(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$title="{new_webserver}";
	$bt="{add}";
	$t=time();
	FORM_START();
	
	$q=new mysql_squid_builder();
	$squid_reverse=new squid_reverse();
	$tpl=new templates();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$sources_list=$squid_reverse->sources_list();
	$array=$sources_list[0];
	$array2=$sources_list[1];
	$CountDeSources=$sources_list[2];
	$nginx_caches=$squid_reverse->caches_list();
	$nginx_pools=$squid_reverse->pool_list();
	$nginx_replaces=$squid_reverse->replace_list();
	$AsFReeWeb=false;
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	
	
	
	
	
	if($servername<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
		$title=$tpl->_ENGINE_parse_body("{port}:{$ligne["port"]} &laquo;$servername&raquo;");
		$bt="{apply}";
		$Hidden=Field_hidden("servername-edit-$t", $servername).$servername;
		
	}else{
		$ligne["enabled"]=1;
		$Hidden=Field_text("servername-edit-$t", null,"font-size:18px;width:300px");
		FORM_CLOSE("YahooWin");
	}
	
	if(!is_numeric($ligne["port"])){$ligne["port"]=80;}
	if(!is_numeric($ligne["ArticaErrors"])){$ligne["ArticaErrors"]=1;}
	if($servername==null){$ligne["cache_peer_id"]=-1;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}	
	
	
	
	if($ligne["cache_peer_id"]==0){
		if($EnableFreeWeb==1){
			$q2=new mysql();
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT `useSSL`,`sslcertificate` FROM `freeweb` WHERE `servername`='$servername'","artica_backup"));
			$ligne["certificate"]=$ligne2["sslcertificate"];
			$AsFReeWeb=true;
			$title=$tpl->_ENGINE_parse_body("FreeWeb &laquo;$servername&raquo;");
		}else{
			$ligne["cache_peer_id"]=-1;
		}
	}
	
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2>".Paragraphe_switch_img("$title", "{nginx_enable_www_text}","enabled-$t",$ligne["enabled"],null,600)."</td></tr>";
	$html[]="<tr><td class=legend style='font-size:18px'>{webserver}:</td>";
	$html[]="<td style='font-size:18px'>$Hidden</td></tr>";
	
		
	
if(!$AsFReeWeb){
	$html[]=Field_text_table("port-$t","{inbound_port}",$ligne["port"],18,null,110);		
}


	$html[]=Field_checkbox_table("default_server-$t","{default_server}",$ligne["default_server"],18,"{NGINX_DEFAULT_SERVER}",300);
	$html[]=Field_checkbox_table("owa-$t","{protect_owa}",$ligne["owa"],18);
	$html[]=Field_checkbox_table("debug-$t","{debug}",$ligne["debug"],18);
	$html[]=Field_list_table("cacheid-$t","{cache}",$ligne["cacheid"],18,$nginx_caches);
	$html[]=Field_list_table("replaceid-$t","{replace_rule}",$ligne["replaceid"],18,$nginx_replaces);
	
	
	
	if($ligne["cache_peer_id"]==0){
		$html[]=Field_hidden("cache_peer_id-$t", 0);
		$html[]=Field_hidden("enabled-$t", 1);
		$html[]=Field_hidden("certificate-$t", $ligne["certificate"]);
	}else{
	
		if(!AdminPrivs()){
			$html[]=Field_hidden("cache_peer_id-$t", $ligne["cache_peer_id"]);
			$html[]=Field_hidden("start_directory-$t", $ligne["start_directory"]);
		}else{
			$html[]=Field_list_table("cache_peer_id-$t","{destination}",$ligne["cache_peer_id"],18,$array);
			$html[]=Field_text_table("start_directory-$t","{start_path}",$ligne["start_directory"],18,null,300);
			
		}
	
		$html[]=Field_list_table("poolid-$t","{pool}",$ligne["poolid"],18,$nginx_pools);
		$html[]=Field_text_table("RedirectQueries-$t","{RedirectQueries}",$ligne["RedirectQueries"],18,null,300);
		$html[]=Field_checkbox_table("ArticaErrors-$t","{enable_template_errors}",$ligne["ArticaErrors"],18);
		
		
	
		if($CountDeSources==0){
			$html[]="<tr><td colspan=2><p class=text-error>{you_need_to_define_sources_first}</p></td></tr>";
			FORM_LOCK();
			
		}
	
	}
	
	
	
	$html[]=Field_button_table($bt);
	
	echo $tpl->_ENGINE_parse_body(FORM_END(CurrentPageName(),$html));
	
	}
	

function Save(){
	$servername=$_POST["servername-edit"];
	unset($_POST["servername-edit"]);
	$_POST["servername"]=$servername;
	$q=new mysql_squid_builder();
	$q2=new mysql();
	$editF=false;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$servername'"));
	if(trim($ligne["servername"])<>null){
		$editF=true;
	}


	if($_POST["default_server"]==1){$q->QUERY_SQL("UPDATE reverse_www SET `default_server`=0 WHERE `port`='{$_POST["port"]}'");}

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	if($editF){
		$sql="UPDATE reverse_www SET ".@implode(",", $edit)." WHERE servername='$servername'";
	}else{
		$ligne=mysql_fetch_array($q2->QUERY_SQL("SELECT servername FROM freeweb WHERE servername='$servername'","artica_backup"));
		if($ligne["servername"]<>null){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{error_this_hostname_is_reserved_freeweb}");
			return;
		}
		$sql="INSERT IGNORE INTO reverse_www (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}




	function AdminPrivs(){
		$users=new usersMenus();
		if($users->AsSystemWebMaster){return true;}
		if($users->AsSquidAdministrator){return true;}
	
	}