<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidPersonalCategories){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
		
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["test"])){test();exit;}
	js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$width=995;
		$statusfirst=null;
		$title=$tpl->_ENGINE_parse_body("{test_categories}");
		$start="YahooWinBrowse('700','$page?popup=yes','$title');";
		$html="$start";
		echo $html;
	
	}	
	
	
	
function popup() {
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
	if($RemoteUfdbCat==0){
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		if($SquidPerformance>0){
			echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_ufdbcat_disabled}"));
			return;
		}
	}
	
	$t=time();
	$html="<div style='font-size:16px' class=explain>{squid_test_categories_perform}</div>
	<div style='width:95%;padding:15px' class=form>
	<center>
		". Field_text("test-$t",null,"font-size:22px;letter-spacing:2px",null,null,null,false,"Run$t(event)",false)."
	</center>
	</div>		
	<div id='results-$t'></div>		
	<script>
		function Run$t(e){
			if(!checkEnter(e)){return;}
			LoadAjax('results-$t','$page?test='+document.getElementById('test-$t').value,true);
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function test(){
	$tpl=new templates();
	$www=$_GET["test"];
	$q=new mysql_squid_builder();
	$www=$q->WebsiteStrip($www);
	
	if($www==null){echo "<p class=text-error>corrupted</p>";return;}
	
	$catz=$q->GET_FULL_CATEGORIES($www);
	if(trim($catz)==null){echo $tpl->_ENGINE_parse_body("<p class=text-error>{not_categorized}</p>");return;}
	if(strpos(" $catz", ",")==0){$CATEGORIES[]=$catz;}else{$CATEGORIES=explode(",",$catz);}
	
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();

	$html="
	<div style='width:95%;margin-top:15px' class=form>		
	<table style='width:99%'>
		<tr>
			
			<td style='vertical-align:top' colspan=2><div style='font-size:18px;font-weight:bolder;letter-spacing:2px'>
			&laquo;$www&raquo;</td>		
		</tr>		
			
	";
	while (list ($num, $categoryF) = each ($CATEGORIES)){
		if(isset($ALREADY_PARSED[$categoryF])){continue;}
		$ALREADY_PARSED[$categoryF]=true;
		$categoryF=trim($categoryF);
		if(!isset($cats[$categoryF])){$cats[$categoryF]=null;}
		if($cats[$categoryF]==null){
			$sql="SELECT category_description FROM personal_categories WHERE category='$categoryF'";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$content=$ligne["category_description"];
			
		}else{
			$content=$cats[$categoryF];
		}
		
		$pic="<img src='img/20-categories-personnal.png'>";
		if(isset($dans->array_pics[$categoryF])){
			$pic="<img src='img/{$dans->array_pics[$categoryF]}'>";
		}
		
		$html=$html."
		<tr>
			<td style='width:22px;vertical-align:top'>$pic</td>
			<td style='vertical-align:top'><div style='font-size:18px;font-weight:bolder;letter-spacing:2px'>&laquo;$categoryF&raquo;</td>		
		</tr>
		<tr>
			<td colspan=2>
				<i style='font-size:16px;font-weight:normal'>$content</i>
			</td>
		</tr>	
		<tr><td colspan=2>&nbsp;</td></tr>		
		";
		
	
	}
	$html=$html."</table></div>";
	
	echo $html;
	
	
	
	
}