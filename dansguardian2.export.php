<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.squid.inc');
	
if($argv[1]=="--export"){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
	do_export();exit;
}
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert')";
	die();	
}

if(isset($_GET["export-rules"])){popup();exit;}
if(isset($_GET["do-export"])){do_export();exit;}


js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ACLNAME=null;
	$title_text="{export_rules}";
	if(is_numeric($_GET["single-id"])){
		if($_GET["single-id"]>0){
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM webfilters_sqacls WHERE ID='{$_GET["single-id"]}'"));
			$ACLNAME=" :".utf8_encode($ligne["aclname"]);
			$title_text="{export_rule}";
		}
	}
	
	header("content-type: application/javascript");

	$title=$tpl->javascript_parse_text("$title_text$ACLNAME");
	$t=time();
	$html="
			
		function Export$t(){
			if(!confirm('$title ?')){return;}
			YahooWin2('480','$page?export-rules=yes&single-id={$_GET["single-id"]}','$title');
		}
				
			
	Export$t();";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$please_wait_exporting_rules=$tpl->_ENGINE_parse_body("{please_wait_exporting_rules}");
	$html="
			
	<center id='text-$t' style='font-size:18px'>$please_wait_exporting_rules...</center>
	<div style='font-size:16px' id='$t-wait'></div>
	
<script>
		function Export$t(){
			
			LoadAjaxSilent('$t-wait','$page?do-export=yes&t=$t&single-id={$_GET["single-id"]}');
		}
	setTimeout(\"Export$t()\",2000);			
</script>		
";
		
	echo $html;	
	
	
}

function _do_export_single_id($ID){
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqacls WHERE ID='$ID'"));
	while (list ($key, $value) = each ($ligne)){
		if(is_numeric($key)){continue;}
		if($key=="ID"){continue;}
		if($key=="aclgpid"){continue;}
		$array["webfilters_sqacls"][$key]=$value;
	}
	
	if($ligne["aclgroup"]==1){
		$subrules=array();
		$sql="SELECT ID,enabled FROM webfilters_sqacls WHERE aclgpid=$ID";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$subrules[]=_do_export_single_id($ligne["ID"]);
		}
		$array["SUBRULES"]=$subrules;
		
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqaclaccess WHERE aclid='$ID'"));
	while (list ($key, $value) = each ($ligne)){
		if(is_numeric($key)){continue;}
		if($key=="ID"){continue;}
		if($key=="aclid"){continue;}
		$array["webfilters_sqaclaccess"][$key]=$value;
	}
	
	$sql="SELECT
	webfilters_sqacllinks.gpid,
	webfilters_sqgroups.ID
	FROM webfilters_sqacllinks,webfilters_sqgroups
	WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$ID";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='{$ligne["ID"]}'"));
		while (list ($key, $value) = each ($ligne2)){
			if(is_numeric($key)){continue;}
			if($key=="ID"){continue;}
			if($key=="gpid"){continue;}
			$arrayGP[$key]=$value;
		}
		$results2 = $q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid={$ligne["ID"]}");
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$arrayGD=array();
			while (list ($key, $value) = each ($ligne2)){
				if(is_numeric($key)){continue;}
				if($key=="ID"){continue;}
				if($key=="gpid"){continue;}
				$arrayGD[$key]=$value;
			}
				
			$arrayItems[]=$arrayGD;
				
		}
		$results3 = $q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE gpid={$ligne["ID"]}");
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$arrayTD=array();
			while (list ($key, $value) = each ($ligne2)){
				if(is_numeric($key)){continue;}
				if($key=="ID"){continue;}
				if($key=="gpid"){continue;}
				$arrayTD[$key]=$value;
			}
			
			if(count($arrayTD)>0){
				$arrayDyn[]=$arrayTD;
			}
			
		}
		
	
		$array["webfilters_sqgroups"][]=array("GROUP"=>$arrayGP,"ITEMS"=>$arrayItems,"DYN"=>$arrayDyn);
	
	}

	return $array;
	
}

function do_export_single_id(){

	$ID=$_GET["single-id"];
	$array=_do_export_single_id($ID);
	
	$dir=dirname(__FILE__)."/ressources/logs/web/$ID.acl";
	@file_put_contents($dir, base64_encode(serialize($array)));
	$t=$_GET["t"];
	if(!is_file($dir)){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(
				"<div style='font-size:18px;color:red;margin-top:15px;margin-bottom:15px'>{failed}</div>");
		return;
	}

	$size=@filesize($dir);
	
	echo "
		<div style='margin-top:15px;margin-bottom:15px;text-align:center'>
			<a href=\"ressources/logs/web/$ID.acl\"
			style='text-decoration:underline;font-size:18px;font-weight:bold'>$ID.acl ". FormatBytes($size/1024)."</a>
				</div>
				<script>
				if(document.getElementById('text-$t')){
				document.getElementById('text-$t').innerHTML='';
					
	}
	
	</script>
		
	";	
	
}


function do_export(){
	if($_GET["single-id"]>0){
		do_export_single_id();
		return;
	}
	
	
	$q=new mysql_squid_builder();
	$q->BD_CONNECT();
	$t=$_GET["t"];
	
	
	$LIST_TABLES_CATEGORIES=$q->LIST_TABLES_CATEGORIES();
	
	while (list ($num, $ligne) = each ($LIST_TABLES_CATEGORIES) ){
		$squidlogs[$num]=true;
		
	}
	
	$squidlogs["webfilter_rules"]=true;
	$squidlogs["webfilter_assoc_groups"]=true;
	$squidlogs["webfilter_blks"]=true;
	$squidlogs["webfilter_group"]=true;
	$squidlogs["webfilter_bannedexts"]=true;
	$squidlogs["webfilters_dtimes_blks"]=true;
	$squidlogs["webfilter_bannedextsdoms"]=true;
	$squidlogs["webfilter_termsg"]=true;
	$squidlogs["webfilter_blklnk"]=true;
	$squidlogs["webfilter_blkgp"]=true;
	$squidlogs["webfilter_blkcnt"]=true;
	
	
	
	$artica_backup["personal_categories"]=true;
	
	
	$dir=dirname(__FILE__)."/ressources/logs/web/webfiltering.sql";
	$final=dirname(__FILE__)."/ressources/logs/web/webfiltering.export";
	$compressed=dirname(__FILE__)."/ressources/logs/web/webfiltering.gz";
	
	@unlink($dir);
	@unlink($final);
	@unlink($compressed);
	
	
	
	$databases["squidlogs"]=$squidlogs;
	$databases["artica_backup"]=$artica_backup;
	if(is_file($dir)){@unlink($dir);}
	$dump=new phpMyDumper("squidlogs",$q->mysql_connection,"$dir",false,$squidlogs);
	$dump->doDump();
	
	if(!is_file($dir)){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(
				"<div style='font-size:18px;color:red;margin-top:15px;margin-bottom:15px'>{failed}</div>");
		return;
	}
	
	$sock=new sockets();
	$DansGuardianDefaultMainRule=$sock->GET_INFO("DansGuardianDefaultMainRule");
	
	$array["SQL"]=@file_get_contents($dir);
	$array["DansGuardianDefaultMainRule"]=$DansGuardianDefaultMainRule;
	@file_put_contents($final, base64_encode(serialize($array)));
	compress($final,$compressed);
	$size=@filesize($compressed);
	@unlink($dir);
	@unlink($final);
	
	
	
	echo "
		<div style='margin-top:15px;margin-bottom:15px;text-align:center'>	
			<a href=\"ressources/logs/web/webfiltering.gz\" 
			style='text-decoration:underline;font-size:18px;font-weight:bold'>webfiltering.gz ". FormatBytes($size/1024)."</a>
		</div>
			<script>
			if(document.getElementById('text-$t')){
				document.getElementById('text-$t').innerHTML='';
			
			}
				
			</script>
			
	";
	
	
	
}
function compress($source,$dest){
	if(!function_exists("gzopen")){
		$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
		echo "FATAL!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__);
		return false;
	}
	$mode='wb9';
	$error=false;
	if(is_file($dest)){@unlink($dest);}
	$fp_out=gzopen($dest,$mode);
	if(!$fp_out){return;}
	$fp_in=fopen($source,'rb');
	if(!$fp_in){return;}
	while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	fclose($fp_in);
	gzclose($fp_out);
	return true;
}



?>