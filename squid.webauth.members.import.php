<?php
session_start();
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


$users=new usersMenus();
if(!$users->AsHotSpotManager){die();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["IMPORT"])){IMPORT();exit;}
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{import}");
	echo "YahooWin3('890','$page?popup=yes&t={$_GET["t"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div style='font-size:18px' class=explain>{mysql_hotspot_members_import_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{delete_old_items}","{delete_old_items_table_explain}").":</td>
		<td>". Field_checkbox_design("DeleteOld-$t", 1,0)."</td>
	</tr>
	<tr>
		
		
		<td colspan=2><textarea 
			style='width:100%;height:350px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='IMPORT-$t'></textarea>
		</td>
	</tr>	
	<tr>
		<td align='right' colspan=2>".button('{import}',"Save$t();",32)."</td>
	</tr>
	</table>
	</div>		
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	YahooWin3Hide();
}

function Save$t(){	
	var XHR = new XHRConnection();
	XHR.appendData('IMPORT',document.getElementById('IMPORT-$t').value);
	if(document.getElementById('DeleteOld-$t').checked){XHR.appendData('DeleteOld',1);}else {XHR.appendData('DeleteOld',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	
</script>			
";
echo $tpl->_ENGINE_parse_body($html);

}

function IMPORT(){
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	if(!is_numeric($HotSpotConfig["USERAD"])){$HotSpotConfig["USERAD"]=0;}
	$sessiontime=$HotSpotConfig["CACHE_AUTH"];
	

	
	$prefix="INSERT IGNORE INTO hotspot_members (uid,ttl,sessiontime,password,enabled,creationtime) VALUES ";
	
	
	$tr=explode("\n",$_POST["IMPORT"]);
	
	$SS=array();
	while (list ($num, $ligne) = each ($tr) ){
		$ligne=trim($ligne);
		if(trim($ligne)==null){continue;}
		if(strpos($ligne, ",")>1){
			$MI=explode(",",$ligne);
		}else{
			$MII=explode(" ",$ligne);
			if(count($MII)>1){
				unset($MI);
				while (list ($num, $b) = each ($MII) ){if(trim($b)==null){continue;}$MI[]=$b;}
			}
		}
		if(count($MI)==0){continue;}
		
		$uid=trim($MI[0]);
		if($uid==null){continue;}
		$pass=$MI[1];
		$ttl=intval($MI[2]);
		$creationtime=time();
		if(trim($pass)==null){$pass=$uid;}
		$uid=mysql_escape_string2($uid);
		$pass=md5($pass);
		$SS[]="('$uid','$ttl','$sessiontime','$pass',1,'$creationtime')";
		
	}
	
	if($SS>0){
		if($_POST["DeleteOld"]==1){$q->QUERY_SQL("TRUNCATE TABLE hotspot_members");}
		$q->QUERY_SQL($prefix." ".@implode(",", $SS));
		if(!$q->ok){echo $q->mysql_error;}
	}
}


