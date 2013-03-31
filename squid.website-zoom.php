<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	
	if(isset($_GET["js"])){echo js();exit;}
	if(isset($_GET["popup"])){page();exit;}
	
	
tab();


function js(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable');";
		return;
	}		
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$www=$_GET["sitename"];
	$html="RTMMail(1090,'$page?sitename=$www&xtime={$_GET["xtime"]}&week={$_GET["week"]}&year={$_GET["year"]}&day={$_GET["day"]}','ZOOM:$www')";
	echo $html;
	
	
}

function tab(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$familysite=$q->GetFamilySites($_GET["sitename"]);
	
	if(!is_numeric($_GET["xtime"])){
		if($_GET["day"]<>null){
			$_GET["xtime"]=strtotime("{$_GET["day"]} 00:00:00");
		}
	}
	
	if(is_numeric($_GET["xtime"])){
		$dateT=" ".date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}
		$array["day"]="{websites}";
		$array["members"]="{members}";
	}
	
	if(is_numeric($_GET["week"])){
		$dateT="{week} {$_GET["week"]}";
		$array["week"]=$familysite.":{websites}";
		$array["members-week"]="{members}";
	}
	
	$array["popup"]="{status} $dateT";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="day"){
			$day=date("Y-m-d",$_GET["xtime"]);
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.traffic.statistics.days.php?today-zoom-popup-history=yes&day=$day&type=size&familysite=$familysite\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="members"){
			$day=date("Y-m-d",$_GET["xtime"]);
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.traffic.statistics.days.php?today-zoom-popup-members=yes&day=$day&type=size&familysite=$familysite\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="week"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.week.familysite.php?week={$_GET["week"]}&year={$_GET["year"]}&familysite=$familysite\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="members-week"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.week.familysite.php?members-week=yes&week={$_GET["week"]}&year={$_GET["year"]}&familysite=$familysite\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		
	
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&sitename={$_GET["sitename"]}&xtime={$_GET["xtime"]}&week={$_GET["week"]}&year={$_GET["year"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	

	
	echo "
	<div id=main_config_zoomwebsite>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zoomwebsite').tabs();
			
			
			});
		</script>";		
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$www=$_GET["sitename"];
	$md5=md5($www);
	$t=time();
	$html="
	<div id='$t$md5'></div>
	
	
	<script>
		$('#startpoint-$md5').remove();
		LoadAjax('$t$md5','squid.www-ident.php?www=$www&xtime={$_GET["xtime"]}&week={$_GET["week"]}&year={$_GET["year"]}&month={$_GET["month"]}');
	</script>
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
