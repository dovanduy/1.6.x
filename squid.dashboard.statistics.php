<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

if(isset($_GET["slider"])){Start();exit;}
if(isset($_GET["proxy-services"])){proxy_services();exit;}
if(isset($_GET["Dashboardjs"])){Dashboardjs();exit;}
if(isset($_GET["stats-dahsboard-title"])){Dashboard_title();exit;}
if(isset($_GET["options-icon"])){options_icon();exit;}


if(isset($_GET["graph1-js"])){proxy_graph_js();exit;}
if(isset($_GET["bx-slider-top-right"])){Dashboard_right();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	echo "LoadAjax('BodyContent','$page?slider=yes');";
	
}

function Start(){
$page=CurrentPageName();
echo "
<div id='bxslider-top' class='bx-slider-top'>&nbsp;</div>
<ul class=\"bxslider\"  id='StatsSquidSlider'>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-requests'><script>LoadAjaxRound('stats-requests','squid.statistics.requests.php')</script></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-flow'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-members'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-caches'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-options'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='categories-service'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='stats-websites'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='none'></div></li>
</ul>
		
		
		
<script>
  STATS_ZMD5='';
  UnlockPage();
  var StatsSlider=$('#StatsSquidSlider').bxSlider({
  pager:false,
  autoControls: false,
  adaptiveHeight:true,
  controls:false,
  onSliderLoad: function(){
  	
  },
  onSlideBefore: function(slideElement,oldIndex, newIndex){
    if(newIndex==0){ LoadAjaxRound('stats-requests','squid.statistics.requests.php');}
    if(newIndex==1){ LoadAjaxRound('stats-flow','squid.statistics.flow.php?zmd5='+STATS_ZMD5);}
    if(newIndex==2){ LoadAjaxRound('stats-members','squid.statistics.members.php?zmd5='+STATS_ZMD5);}
    if(newIndex==3){ LoadAjaxRound('stats-caches','squid.statistics.caches.php');}
    if(newIndex==4){ LoadAjaxRound('stats-options','squid.statistics.options.php');}
    if(newIndex==5){ LoadAjaxRound('categories-service','ufdbcat.php?page=yes');}
    if(newIndex==6){ LoadAjaxRound('stats-websites','squid.statistics.websites.php?zmd5='+STATS_ZMD5);}
}
});	


function GoToStatsRequests(){
	StatsSlider.goToSlide(0);
  		
}  		
function GoToStatsFlow(zmd5){
	if(zmd5){STATS_ZMD5=zmd5;}
	StatsSlider.goToSlide(1);
}

function GoToStatsMembers(zmd5){
	if(zmd5){STATS_ZMD5=zmd5;}
	StatsSlider.goToSlide(2);
}

function GoToStatsCache(){
	StatsSlider.goToSlide(3);
}
function GoToStatsOptions(){
	StatsSlider.goToSlide(4);
}
function GoToCategoriesService(){
	StatsSlider.goToSlide(5);
}
function GoToWebsitesStats(zmd5){
	if(zmd5){STATS_ZMD5=zmd5;}
	StatsSlider.goToSlide(6);
}


LoadAjaxTiny('bxslider-top','$page?stats-dahsboard-title=yes');

</script>";

}


function Dashboard_title(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();

	$href[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToStatsRequests();\">{requests}</a>";

	$href[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToStatsFlow();\">{flow}</a>";

	$href[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToStatsMembers();\">{members}</a>";
	
	$href[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToWebsitesStats();\">{websites}</a>";
	
	$href[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToStatsCache();\">{history}</a>";	

	


$filnale=@implode("&nbsp;|&nbsp;", $href);


	$html="
	<table style='width:100%'>
	<tr>
	<td style='font-size:20px;color:#DCDCDC;width:50%'>
	
	<a href=\"javascript:blur();\" OnClick=\"javascript:DashBoardProxy();\">{home}</a>&nbsp;|&nbsp;
	<span id='stats-requeteur'></span>&nbsp;|&nbsp;$filnale</td>
	<td style='font-size:22px;color:#DCDCDC;text-align:right'><span id='bx-slider-top-right'></span></td>
	<td style='font-size:22px;color:#DCDCDC;width:40px;text-align:center'>
		<div id='bx-slider-top-options' style='margin-top:-5px'></div></td>
	</tr>
	</table>
	<script>
		LoadAjaxTiny('bx-slider-top-right','$page?bx-slider-top-right=yes');
		LoadAjaxTiny('bx-slider-top-options','$page?options-icon=yes');</script>				
		
	</script>			
	
	";

	echo $tpl->_ENGINE_parse_body($html);

}

function options_icon(){
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"javascript:MessagesTopshowMessageDisplay('quicklinks_statistics_options');\"";
	echo "<img src='img/options-32.png' $curs>";
}

function Dashboard_right(){
	
}
