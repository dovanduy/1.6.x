<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if($GLOBALS["VERBOSE"]){
	if(!$users->AsProxyMonitor){
		echo "<H1>AsProxyMonitor = FALSE</H1>";
		return;
	
	}else{
		echo "<H1>AsProxyMonitor = TRUE</H1>";
	}
}
if(!$users->AsProxyMonitor){header("location:miniadm.logon.php");}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["proxy-events"])){proxy_events();exit;}
if(isset($_GET["watchdog-events"])){watchdog_events();exit;}
if(isset($_GET["watchdog"])){table_watchdog();exit;}

main_page();
exit;


if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function proxy_events(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	echo $tpl->_ENGINE_parse_body("	    <div class='form-search' style='margin:10px;text-align:right'>
   	 	<input type='text' id='s-$t' class='input-medium search-query' OnKeyPress=\"javascript:SearchQueryQ$t(event)\">
   		 <button type='button' class='btn' OnClick=\"javascript:SearchQuery$t()\">{search}</button>
    </div>
	<div class=BodyContentWork id='$t'></div>

	<script>
		function SearchQueryQ$t(e){
			if(!checkEnter(e)){return;}
			SearchQuery$t();
		}
		
		function SearchQuery$t(){
			var pp=encodeURIComponent(document.getElementById('s-$t').value);
			LoadAjax('$t','$page?table=yes&query='+pp)		
		}
	
	SearchQuery$t();
	</script>");
	
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{proxy_service_events}"]="$page?proxy-events=yes";
	
	echo $boot->build_tab($array);
}

function content(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{proxy_service_events}</H1>
	<p>{proxy_events_text}</p>
	</div>
	<div id='$t-tabs'></div>
	<script>
		LoadAjax('$t-tabs','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);


}

function watchdog_events(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	echo $tpl->_ENGINE_parse_body("	    <div class='form-search' style='margin:10px;text-align:right'>
			<input type='text' id='s-$t' class='input-medium search-query' OnKeyPress=\"javascript:SearchQueryQ$t(event)\">
			<button type='button' class='btn' OnClick=\"javascript:SearchQuery$t()\">{search}</button>
			</div>
			<div class=BodyContentWork id='$t'></div>
	
			<script>
			function SearchQueryQ$t(e){
			if(!checkEnter(e)){return;}
			SearchQuery$t();
	}
	
			function SearchQuery$t(){
			var pp=encodeURIComponent(document.getElementById('s-$t').value);
			LoadAjax('$t','$page?watchdog=yes&query='+pp)
	}
	
			SearchQuery$t();
			</script>");	
	
}
function table_watchdog(){
	$_GET["query"]=url_decode_special_tool($_GET["query"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	if(!isset($_GET["rp"])){$_GET["rp"]=150;}
	if($_GET["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-logs=$search&rp={$_GET["rp"]}")));
		$total=count($datas);
	
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-log=&rp={$_GET["rp"]}")));
		$total=count($datas);
	}
	while (list ($key, $line) = each ($datas) ){
		$line=trim($line);
		$lineS=$line;
	
			
		if(preg_match("#^([0-9-]+)\s+([0-9\:]+)\s+\[(.+?)\]\s+#", $line,$re)){
			$date=$re[1]." ".$re[2];
			$line=str_replace($date,"",$line);
			$pid=$re[3];
			$line=str_replace("[{$pid}]","",$line);
			$line=trim($line);
		}		
		
		if(preg_match("^([0-9-]+)\s+([0-9\:]+)", $line,$re)){
			$date=$re[1]." ".$re[2];
			$line=str_replace($date,"",$line);
		}
	
		$class=LineToClass($line);

		$line=	$tpl->_ENGINE_parse_body($line);
	
		$tr[]="
		<tr class='$class'>
			<td>$date</td>
			<td>$pid</td>
			<td>$line</td>
		</tr>
		";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
		
			<thead>
				<tr>
					<th>{date}</th>
					<th>{pid}</th>
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
					
			</table>";
	
}

function table(){
	$_GET["query"]=url_decode_special_tool($_GET["query"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	if(!isset($_GET["rp"])){$_GET["rp"]=150;}
	if($_GET["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?cachelogs=$search&rp={$_GET["rp"]}")));
		$total=count($datas);
	
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?cachelogs=&rp={$_GET["rp"]}")));
		$total=count($datas);
	}
	while (list ($key, $line) = each ($datas) ){
		$line=trim($line);
		$lineS=$line;
		
		if(preg_match("#^([0-9\.\/\s+\:]+)\s+([0-9\:]+)#", $line,$re)){
			$date=$re[1]." ".$re[2];
			$line=str_replace($date,"",$line);
		}
		

		$class=LineToClass($line);
				
		
		$tr[]="
				<tr class='$class'>
					<td>$date</td>
					<td>$line</td>
				</tr>
		";
		
		
	
	}
	
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
			
			<thead>
				<tr>
					<th>{date}</th>
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
					
			</table>";
	
}
