<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["report"])){report();exit;}
	
	page();
	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$html="<div style='font-size:30px;margin-bottom:30px'>{test_categories_rate}</div>
	<div class=explain style='font-size:18px'>{test_categories_rate_explain}</div>
	<center style='margin:20px'>". button("{upload2} access.log","Loadjs('ufdbcat.check.upload.php')",22)."</center>
	<div id='WebClassificationReport'></div>		
			
			
	<script>
		LoadAjax('WebClassificationReport','ufdbcat.check.php?report=yes');
	</script>		
	";					

	echo $tpl->_ENGINE_parse_body($html);		
}
function report(){
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/categorized.array")){return;}
	$ARRAY=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/categorized.array"));
	
	
	$stats_sites=$ARRAY["stats_sites"];
	$stats_categorized=$ARRAY["stats_categorized"];
	$stats_not_categorized=$ARRAY["stats_not_categorized"];
	$SumOflines=$ARRAY["SumOflines"];
	$stats_ip=$ARRAY["stats_ip"];
	$FIRSTTIME=$ARRAY["firsttime"];
	$LASTTIME=$ARRAY["lasttime"];
	
	$rate=$stats_categorized/$stats_sites;
	$rate=$rate*100;
	$rate=round($rate,2);
	
	
	$stats_not_categorized=FormatNumber($stats_not_categorized);
	$stats_categorized=FormatNumber($stats_categorized);
	$stats_sites=FormatNumber($stats_sites);
	$stats_ip=FormatNumber($stats_ip);
	$SumOflines=FormatNumber($SumOflines);
	

	
	if($rate>60){$rate_color="46a346";}
	if($rate<60){$rate_color="d32d2d";}
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
	
	<div style='font-size:18px;margin-bottom:15px'>{from} ". $tpl->time_to_date($FIRSTTIME)." {to} ".$tpl->time_to_date($LASTTIME)." (".distanceOfTimeInWords($FIRSTTIME,$LASTTIME).")</div>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{rate}:</td>
		<td style='font-size:22px;text-align:right'><strong style='font-size:36px;color:$rate_color'>{$rate}%</strong></td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{duration}:</td>
		<td style='font-size:22px;text-align:right'><strong>{$ARRAY["DURATION"]}</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{lines}:</td>
		<td style='font-size:22px;text-align:right'><strong>$SumOflines</strong></td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{not_categorized}:</td>
		<td style='font-size:22px;text-align:right'><strong>$stats_not_categorized</strong></td>
		<td><a href=\"ressources/logs/notcategorized.csv\"><img src='img/csv-32.png'></a></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{categorized}:</td>
		<td style='font-size:22px;text-align:right'><strong>$stats_categorized</strong></td>
		<td><a href=\"ressources/logs/categorized.csv\"><img src='img/csv-32.png'></a></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ipaddresses}:</td>
		<td style='font-size:22px;text-align:right'><strong>$stats_ip</strong></td>
		<td><a href=\"ressources/logs/ipcategorized.csv\"><img src='img/csv-32.png'></a></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{websites}:</td>
		<td style='font-size:22px;text-align:right'><strong>$stats_sites</strong></td>
		<td>&nbsp;</td>
		
	</tr>	
	</table>
	</div>					
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}	
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

