<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["import"])){import();exit;}
if(isset($_GET["export1"])){export_domains();exit;}
if(isset($_GET["export2"])){export_websites();exit;}
js();



function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{import}");
	echo "YahooWin2('1024','$page?tabs=yes','$title')";
	
	
	
}

function tabs(){
	$page=CurrentPageName();
	
	$tpl=new templates();
	$style="style='font-size:18px'";
	$array["popup"]="{import}";
	$array["bulk"]="{bulk_import}";
	$array["export1"]="{dns_export}";
	$array["export2"]="{websites_export}";
	
	while (list ($num, $ligne) = each ($array) ){
		
			if($num=="bulk"){
				$html[]= "<li ><a href=\"miniadmin.proxy.reverse.import-bulk.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $style>$ligne</span></a></li>\n");
	}
	
	$t=time();
	
	echo  build_artica_tabs($html, "reverse-proxy-import-tabs")."
	
	<script>// LeftDesign('dashboard-256-opac20.png');</script>";	
	
}



function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	
	<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{import}</div>
	<div style='width:98%' class=form>
	<p class=text-info style='font-size:18px'>{nginx_import_explain}</p>
	<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:98%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important' 
		id='textToParseCats$t'></textarea>
	<hr>
	<div style='text-align:right'>	
	". button("{submit}", "Save$t()",26).	
	"</div>	
	</div>
	<script>
		var xSave$t=function (obj) {
			var results=obj.responseText;	
			UnlockPage();
			if (results.length>3){alert(results);}
			ExecuteByClassName('SearchFunction');
		}	
	
	
		function Save$t(){
		  	var XHR = new XHRConnection();  
		  	LockPage();
    	 	XHR.appendData('import',document.getElementById('textToParseCats$t').value);
		  	XHR.sendAndLoad('$page', 'POST',xSave$t);
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function import(){
	
	$data=$_POST["import"];
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.import";
	@unlink($filename);
	@file_put_contents($filename, $data);
	if(!is_file($filename)){echo "Fatal, permission denied\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?import=yes");
	echo @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.import.results");
	
}

function export_domains(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$f=array();
	
	
	
	
		$results=$q->QUERY_SQL("SELECT servername FROM reverse_www ORDER BY servername");
		$sourcename="127.0.0.1";
		while ($ligne = mysql_fetch_assoc($results)) {
			$servername=$ligne["servername"];
			$cache_peer_id=$ligne["cache_peer_id"];
			$f[]="cnamerecord;$servername;[REVERSE_PROXY_PUBLIC_IP];default";
			
			
			
		}

$html="<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{dns_export}</div>
		<div style='width:98%' class=form>
		<p class=text-info style='font-size:18px'>{nginx_dns_export_explain}</p>
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:98%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
				id='textToParseCats$t'>".@implode("\n", $f)."</textarea>
				<hr>";	
	
echo $tpl->_ENGINE_parse_body($html);	
	
}


function export_websites(){
	
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$f=array();
	
	
	
	
	$results=$q->QUERY_SQL("SELECT servername,cache_peer_id FROM reverse_www ORDER BY servername");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$servername=$ligne["servername"];
		$cache_peer_id=$ligne["cache_peer_id"];
		$sourcename="127.0.0.1";
			
		if($cache_peer_id>0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
			$port=$ligne["port"];
			//print_r($ligne2);
				
			if(preg_match("#^http.*?:\/#", $ligne2["ipaddr"])){
				// c'est une URI, on la décompose
				$URZ=parse_url($ligne2["ipaddr"]);
				$sourcename=$URZ["host"];
				if(preg_match("#(.+?):([0-9]+)#", $sourcename,$re)){$sourcename=$re[1];}
				$f[]="$servername;$sourcename";
				continue;
		
			}
		
			$sourcename=$ligne2["ipaddr"];
			$f[]="$servername;$sourcename";
			continue;
			
		
		
		}	

		$f[]="$servername;$sourcename";
			
	}	
	
	$html="<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{websites_export}</div>
	<div style='width:98%' class=form>
	<p class=text-info style='font-size:18px'>{nginx_websites_export_explain}</p>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
	id='textToParseCats$t'>".@implode("\n", $f)."</textarea>
				<hr>";
	
	echo $tpl->_ENGINE_parse_body($html);	
}



			

