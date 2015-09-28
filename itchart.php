<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.users.menus.inc');
	call_user_func(base64_decode("bGtkZmpvemlmX3VlaGZl"));
	
	
	if(isset($_POST["AcceptChart"])){ItChartSave();exit;}
	if(isset($_GET["pdf"])){output_pdf();exit;}
	ItChart();
	
function ItChart(){
	$users=new usersMenus();
	$array=unserialize(base64_decode($_GET["request"]));
	if(defined(base64_decode("a2Rmam96aWY="))){$a=constant(base64_decode("a2Rmam96aWY="));}
	$src=$array["src"];
	$pdf=null;
	$ChartID=$array["ChartID"];
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$t=time();
	$Curpage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enablepdf,PdfFileName,ChartContent,ChartHeaders,TextIntro,TextButton,
			title FROM itcharters WHERE ID='$ChartID'"));
	
	$page=@file_get_contents("ressources/templates/endusers/splash.html");

	$page=str_replace("{PAGE_TITLE}", $ligne["title"], $page);
	$page=str_replace("{HEADS}", $ligne["ChartHeaders"], $page);
	
	
	
	
	if($ligne["TextIntro"]==null){
		$ligne["TextIntro"]="Please read the IT chart before accessing trough Internet";
	}
	if($ligne["TextButton"]==null){
		$ligne["TextButton"]="I accept the terms and conditions of this agreement";
		
	}
	
	if($ligne["enablepdf"]==1){
		$ligne["ChartContent"]="
		<object data=\"$Curpage?pdf=$ChartID\" type=\"application/pdf\" width=\"800\" height=\"600\">
 		<p class=Textintro>It appears you don't have a PDF plugin for this browser.
 		 <br>You can <a href=\"$Curpage?pdf=$ChartID\">click here to download the {$ligne["PdfFileName"]} file.</a></p>
  		</object>		
		";
	}
	
	
	
	$content="{$a}<p class='Textintro'>{$ligne["TextIntro"]}</p>
	 <!-- chart id: $ChartID -->
		
	
	<p style='margin-left:50px' id='$t'>{$ligne["ChartContent"]}</p>
	
	<form method='post' action='$Curpage' id='post-itchart'>
		<input type='hidden' name='AcceptChart' value='yes'>
		<input type='hidden' name='AcceptChartContent' value='{$_GET["request"]}'>
	</form>
	
	
	<center><div style='margin:50px'>". button($ligne["TextButton"], "Accept$t()",32)."</center>
	
	";
	$page=str_replace("{CONTENT}", $content, $page);
	$scriptJS="
function Accept$t(){
	document.forms['post-itchart'].submit();
}
	";
		

$page=str_replace("{SCRIPT}", "$scriptJS", $page);
echo $page;
	
	
}

function output_pdf(){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT PdfFileName,PdfContent FROM itcharters WHERE ID='{$_GET["pdf"]}'"));
	
	
	header("Content-Type: application/pdf");
	header("Content-Disposition: attachment; filename=\"{$ligne["PdfFileName"]}\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".strlen($ligne["PdfContent"]));
	ob_clean();
	flush();
	echo $ligne["PdfContent"];
	
}


function ItChartSave(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$array=unserialize(base64_decode($_POST["AcceptChartContent"]));
	$src=$array["src"];
	$ChartID=$array["ChartID"];
	$LOGIN=trim(strtolower($array["LOGIN"]));
	$IPADDR=trim($array["IPADDR"]);
	$MAC=trim(strtolower($array["MAC"]));
	$tpl=new templates();
	
	$parse_url=parse_url($src);
	$host=$parse_url["host"];
	
	
	$newURI="{$parse_url["scheme"]}://$host/?itchart-time=".time();
	
	
	
	
	

	$q=new mysql_squid_builder();
	$zDate=date("Y-m-d H:i:s");
	
	$sql="INSERT IGNORE INTO `itchartlog` (`chartid`,`uid`,`ipaddr`,`MAC`,`zDate`)
	VALUES ('$ChartID','$LOGIN','$IPADDR','$MAC','$zDate')";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enablepdf,ChartHeaders,title FROM itcharters WHERE ID='$ChartID'"));
	
	$page=@file_get_contents("ressources/templates/endusers/splash.html");
	$redirect_text=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to} $host");
	
	$page=str_replace("{PAGE_TITLE}", $redirect_text, $page);
	$page=str_replace("{HEADS}", $ligne["ChartHeaders"], $page);

	
		$MAIN_BODY="<center>
	<div id='maincountdown' style='width:100%'>
	<center style='margin:20px;padding:20px;;color:black;width:80%' >
		<input type='hidden' id='countdownvalue' value='10'>
		<span id='countdown' style='font-size:70px'></span>
	</center>
	</div>
	<p style='font-size:22px'>
			<center style='margin:50px;;color:black;width:80%'>
				<center style='margin:20px;font-size:70px' id='wait_verybig_mini_red'>
					<img src='img/wait_verybig_mini_red.gif'>
				</center>
			</center>
	</p> 
	</center>
	<script>

	
 
setInterval(function () {
	var countdown = document.getElementById('countdownvalue').value
	countdown=countdown-1;
	if(countdown==0){
		document.getElementById('countdownvalue').value=0;
		document.getElementById('maincountdown').innerHTML='';
		window.location.href =\"$newURI\";
		return;
	}
	document.getElementById('countdownvalue').value=countdown;
	document.getElementById('countdown').innerHTML=countdown
 
}, 1000);
</script>";
$page=str_replace("{CONTENT}", $MAIN_BODY, $page);
$page=str_replace("{SCRIPT}",null, $page);
echo $page;

	
}
