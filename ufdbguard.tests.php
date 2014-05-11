<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["www"])){test();exit;}
	js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$width=995;
		$statusfirst=null;
		$title=$tpl->_ENGINE_parse_body("{verify_rules}");
		$start="YahooWinBrowse('700','$page?popup=yes','$title');";
		$html="$start";
		echo $html;
	
	}	
	
	
function popup() {
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$ipaddr=$_SESSION["UFDBT"]["IP"];
	$username=$_SESSION["UFDBT"]["USER"];
	$www=$_SESSION["UFDBT"]["WWW"];
	if($ipaddr==null){$ipaddr=$_SERVER["REMOTE_ADDR"];}
	if($www==null){$www="http://www.youporn.com";}
	
	$html="<div style='font-size:16px' class=explain>{ufdbguard_verify_rules_explain}</div>
	<div style='width:95%;padding:15px' class=form>
	<center>
	<table style='width:99%'>
		<tr>
			<td class=legend style='font-size:16px'>{request}:</td>
			<td>". Field_text("www-$t",$www,"font-size:16px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:16px'>{ipaddr}:</td>
			<td>". Field_text("ipaddr-$t",$ipaddr,"font-size:16px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{username}:</td>
			<td>". Field_text("user-$t",$username,"font-size:16px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."</td>
		</tr>	
				<td colspan=2 align='right'>". button("{check}", "check$t()",18)."</td>
		</tr>
	</table>
	</center>
	</div>		
	<div id='results-$t'></div>		
	<script>
	
	var xRun$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			
		}		
	
		function Run$t(e){
			if(!checkEnter(e)){return;}
			check$t();
			
		
		}
		
		
	function check$t(){
		var XHR = new XHRConnection();
		XHR.appendData('www',document.getElementById('www-$t').value);
		XHR.appendData('user',document.getElementById('user-$t').value);
		XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
		XHR.sendAndLoad('$page', 'POST',xRun$t);		
	
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function test(){
	$tpl=new templates();
	$www=$_GET["test"];
	$q=new mysql_squid_builder();
	$ipaddr=$_POST["ipaddr"];
	$user=$_POST["user"];
	$www=$_POST["www"];
	$_SESSION["UFDBT"]["IP"]=$ipaddr;
	$_SESSION["UFDBT"]["USER"]=$user;
	$_SESSION["UFDBT"]["WWW"]=$www;
	if($user==null){$user="-";}
	if($ipaddr==null){$ipaddr="-";}
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
	if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	
	if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
	$address=null;
	
	
	if($datas["UseRemoteUfdbguardService"]==1){
		if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
		$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";
		
	}
	
	
	if($address==null){
		if($datas["tcpsockets"]==1){
			$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";
			
		}else{
			$address="-S 127.0.0.1 -p {$datas["listen_port"]} ";
		}
	}
	if($address==null){echo "Cannot determine address\n";return;}
	
	$cmdline="$address $www $ipaddr $user";
	$cmdline=urlencode(base64_encode($cmdline));
	$datas=base64_decode($sock->getFrameWork("squid.php?ufdbclient=$cmdline"));
	
	
	if(trim($datas)==null){echo "**** OK PASS ****\n";return;}
	
	
	$url=parse_url($datas);

	if(!is_numeric($url["port"])){$url["port"]=80;}
	
	echo "\n************\n******** BLOCK ********\n************\nAnd redirected to: {$url["scheme"]}://{$url["host"]}:{$url["port"]}{$url["path"]}\n";
	
	
	$queries=explode("&",$url["query"]);
	
	while (list ($num, $line) = each ($queries)){
		if(preg_match("#(.+?)=(.+)#", $line,$re)){
			$array[$re[1]]=$re[2];
		}
	}
		
	
	if($url["path"] == "/exec.squidguard.php"){
		if(isset($array["rule-id"])){
			$sql="SELECT * FROM webfilter_rules WHERE ID={$array["rule-id"]}";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			echo "Rule: {$ligne["rulename"]} - {$array["clientgroup"]}\n";
		}
		if(isset($array["clientaddr"])){
			echo "IP: {$array["clientaddr"]}\n";
		}
		if(isset($array["clientuser"])){
			echo "Uid: {$array["clientuser"]}\n";
		}	
		if(isset($array["targetgroup"])){
			echo "Category: {$array["targetgroup"]}\n";
		}		
	}
	
	
	
}