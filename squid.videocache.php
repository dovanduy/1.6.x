<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_POST["StreamCacheBindHTTP"])){save_parameters();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["services-videocache-status"])){status_videocache();exit;}
	if(isset($_GET["videocache-graph1"])){status_videocache_graph1();exit;}
	if(isset($_GET["videocache-graph2"])){status_videocache_graph2();exit;}
	if(isset($_GET["videocache-graph3"])){status_videocache_graph3();exit;}
	if(isset($_GET["websites"])){websites();exit;}
	if(isset($_POST["reinstall"])){reinstall();exit;}
	
	
	if(isset($_POST["EnableStreamCache"])){EnableStreamCache();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["stats"])){statistics();exit;}
	if(isset($_GET["reinstall-js"])){reinstall_js();exit;}
	
	tabs();
	
function tabs(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	
	$status=trim($sock->getFrameWork("squid.php?videocache-streamsquidcache=yes"));
	
	if($status<>"TRUE"){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{module_in_squid_not_installed}<hr>{EnableStreamCache_text}"));
		return;
	}
	
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["parameters"]='{parameters}';
	$array["events"]='{events}';
	$array["events-retriver"]='{retreiver_events}';
	if($q->TABLE_EXISTS("videocacheA")){
		$array["stats"]='{statistics}';
	}
	
	$array["websites"]="{supported_websites}";
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.videocache.events.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events-retriver"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.videocache.events-retreiver.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="master"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.master-proxy.php?byQuicklinks=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
		}
	
	echo build_artica_tabs($html, "main_squid_videocache_tabs",1200)."<script>LeftDesign('videocache-256-white-opac20.png');</script>";
}

function reinstall_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	echo "Loadjs('squid.video.cache.progress.php')";return;
	$ask=$tpl->javascript_parse_text("{reinstall_software_ask}");
	$html="
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		RefreshTab('main_squid_videocache_tabs');
	
	}		
	function ReinstallPage$t(){
			if(!confirm('$ask')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('reinstall', 'yes');
			XHR.sendAndLoad('$page', 'POST',xSave$t);
		}
	ReinstallPage$t();";
	echo $html;
	
}

function reinstall(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?videocache-reinstall=yes");
	
}

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$squid=new squidbee();
	$EnableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
	$EnableStreamCacheP=Paragraphe_switch_img("{EnableStreamCache}", 
			"{EnableStreamCache_text}","EnableStreamCache",$EnableStreamCache,null,600);
	$users=new usersMenus();
	
	if(!$users->CORP_LICENSE){
		$error_license="<p class=text-error>{error_corp_30day}</p>";
	}
	
	$SSL_BUMP=$squid->SSL_BUMP;
	
	
	if($SSL_BUMP==0){
		$error_license=$error_license.
		"<table style='margin-top:10px'>
		<tr>
			<td valign='top' nowrap><img src='img/warning-panneau-64.png'></td>
			<td style='padding-left:15px' style='font-size:18px'>{warn_videocache_nossl}</td>
		</tr>
		</table>";
	}
	

	$html="
	<div style='font-size:32px;margin-bottom:30px'>VideoCache v2.4</div>
	$error_license		
	<div style=width:98% class=form>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:450px'><div id='services-videocache-status'></div></td>
		<td style='vertical-align:top;width:600px'>
				$EnableStreamCacheP
				<hr>
				<div style='text-align:right;margin-bottom:50px'>". button("{apply}", "Save$t()",26)."</div>
				<div id='videocache-graph1' style='width:600px;height:450px'></div>
				<div id='videocache-graph2'  style='width:600px;height:450px'></div>
				
				
			</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_squid_videocache_tabs');
	Loadjs('squid.reconfigure.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableStreamCache', document.getElementById('EnableStreamCache').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
LoadAjax('services-videocache-status','$page?services-videocache-status=yes',false);	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function status_videocache(){
	$cachestatus="/usr/share/artica-postfix/ressources/logs/web/videocache.dirs.status.db";
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	
	
	$button_reinstall=$tpl->_ENGINE_parse_body("<center style='margin:10px'>".button("{reinstall_software}",
			"Loadjs('$page?reinstall-js=yes')",26)."</center>");
	
	$ini->loadString(base64_decode($sock->getFrameWork("squid.php?videocache-status=yes")));
	
	$STATUS[]=DAEMON_STATUS_ROUND("APP_VIDEOCACHE_SCHEDULER",$ini,null,1);
	$STATUS[]=DAEMON_STATUS_ROUND("APP_VIDEOCACHE_CLIENT",$ini,null,1);
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $STATUS).
	"<div style='text-align:right'>".
	imgtootltip("refresh-32.png","{refresh}",
	"LoadAjax('services-videocache-status','$page?services-videocache-status=yes',false);")."</div>").$button_reinstall;
	
	if(is_file($cachestatus)){
		echo "<script>
				AnimateDiv('videocache-graph1');
				Loadjs('$page?videocache-graph1=yes&container=videocache-graph1',true);
				</script>
			";
	}
	
}

function EnableStreamCache(){
	$sock=new sockets();
	$sock->SET_INFO("EnableStreamCache", $_POST["EnableStreamCache"]);
	$sock->getFrameWork("squid.php?videocache-restart=yes");
	
}

function status_videocache_graph1(){

	$tpl=new templates();
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/web/videocache.dirs.status.db";
	$MAIN=unserialize(@file_get_contents($filecache));
	$MAIN_TABLE=$MAIN["VIDEOCACHE"];
	$SIZE=$MAIN_TABLE["SIZE"];
	$SIZE2=($SIZE/1024)/1000;
	$TOTAL_TEXT=FormatBytes($MAIN_TABLE["PART"]["TOT"]/1024);

	$PieData["VideoCache ".$tpl->javascript_parse_text(FormatBytes($SIZE/1024))]=$SIZE2;

	$OTHER=intval($MAIN_TABLE["PART"]["USED"]-$SIZE);
	$FREE=$MAIN_TABLE["PART"]["AIV"];

	$OTHER2=($OTHER/1024)/1000;
	$FREE2=($FREE/1024)/1000;

	$other_text=$tpl->javascript_parse_text("{other}");
	$free_text=$tpl->javascript_parse_text("{free}");
	$PieData["$other_text ".FormatBytes($OTHER/1024)]=$OTHER2;
	$PieData["$free_text ".FormatBytes($FREE/1024)]=$FREE2;

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="VideoCache";
	$highcharts->Title="VideoCache  \"{$MAIN_TABLE["PART"]["MOUNT"]}\" $TOTAL_TEXT";
	$highcharts->LegendSuffix=" MB";
	echo $highcharts->BuildChart();
	
	

}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();


	
	$StreamCacheCache=$sock->GET_INFO("StreamCacheCache");
	if($StreamCacheCache==null){$StreamCacheCache="/home/squid/videocache";}
	$StreamCacheMainCache=$sock->GET_INFO("StreamCacheMainCache");
	if($StreamCacheMainCache==null){$StreamCacheMainCache="/home/squid/streamcache";}
	$StreamCacheBindHTTP=$sock->GET_INFO("StreamCacheBindHTTP");
	$StreamCacheBindProxy=$sock->GET_INFO("StreamCacheBindProxy");
	
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	unset($ips["127.0.0.1"]);
	unset($ips["0.0.0.0"]);
	$ips2=$ip->ALL_IPS_GET_ARRAY();
	unset($ips2["0.0.0.0"]);
	
	$ips3[null]="{none}";
	$ips3=$ip->ALL_IPS_GET_ARRAY();
	unset($ips3["0.0.0.0"]);
	unset($ips3["127.0.0.1"]);
	

$html="<div style=width:98% class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=3 style='font-size:32px'>{APP_VIDEOCACHE}<hr></td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>{webserver} ({listen_address}):</td>
		<td style='font-size:18px'>". Field_array_Hash($ips,"StreamCacheBindHTTP",$StreamCacheBindHTTP,"style:font-size:22px")."<td>
		<td style='font-size:18px' width=1%><td>
	</tr>				
	
	<tr>
		<td colspan=3 style='font-size:22px'>{caches}<hr></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{videos_storage}:</td>
		<td style='font-size:22px'>". Field_text("StreamCacheCache",$StreamCacheCache,"font-size:18px;width:350px")."</td>
		<td>". button_browse("StreamCacheCache")."</td>
	</tr>	
	<tr>
	<td colspan=3 align='right' style='padding-top:20px'><hr>". button("{apply}", "Save$t()",32)."</td>
	</tr>
	
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('squid.reconfigure.php?ask=yes');
	
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('StreamCacheCache', document.getElementById('StreamCacheCache').value);
	XHR.appendData('StreamCacheBindHTTP', document.getElementById('StreamCacheBindHTTP').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function save_parameters(){
	$sock=new sockets();
	while (list ($key, $val) = each ($_POST)){
		$sock->SET_INFO($key, $val);
	}
	
	$sock->getFrameWork("squid.php?videocache-restart=yes");
	
}

function statistics(){
	$page=CurrentPageName();
	echo "<div id='videocache-graph3' style='width:900px;height:450px'></div>
			
	<script>
	AnimateDiv('videocache-graph3');
	Loadjs('$page?videocache-graph3=yes&container=videocache-graph3',true);
	</script>
	";
	
}

function status_videocache_graph3(){
	$q=new mysql_squid_builder();;
	$tpl=new templates();
	
	$sql="SELECT SUM(zSize) as tsize, DATE_FORMAT(zDate,'{%W} %m %Hh') as th FROM videocacheA GROUP BY th ORDER BY th";
	$results=$q->QUERY_SQL($sql);
	
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$x[]=$tpl->javascript_parse_text($ligne["th"]);
		$y[]=round($ligne["tsize"]/1024)/1000;
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$x;
	$highcharts->Title="{downloaded_videos}/{hours}";
	$highcharts->yAxisTtitle="{videos}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{size}"=>$y);
	echo $highcharts->BuildChart();
	
}

function websites(){
	$tpl=new templates();
	$html="<div style='font-size:26px;margin-top:15px;margin-bottom:15px'>{listofsupportedwww}</div>
<p style='font-size:18px'>{listofsupportedwww_explain}</p>

<ul style='list-style: none outside none;'>
  <li style='font-size:16px'>YouTube - www.youtube.com (including mobile platforms like iOS/Android)</li>
  <li style='font-size:16px'>AOL - www.aol.com</li>
  <li style='font-size:16px'>Bing - www.bing.com/videos/</li>
  <li style='font-size:16px'>Blip - www.blip.tv</li>
  <li style='font-size:16px'>Break - www.break.com</li>
  <li style='font-size:16px'>Dailymotion - www.dailymotion.com</li>
  <li style='font-size:16px'>Facebook - www.facebook.com</li>
  <li style='font-size:16px'>IMDB - www.imdb.com</li>
  <li style='font-size:16px'>Metacafe - www.metacafe.com</li>
  <li style='font-size:16px'>MySpace - www.myspace.com/video/</li>
  <li style='font-size:16px'>Veoh - www.veoh.com</li>
  <li style='font-size:16px'>VideoBash - www.videobash.com</li>
  <li style='font-size:16px'>Vimeo - www.vimeo.com</li>
  <li style='font-size:16px'>Vube - www.vube.com</li>
  <li style='font-size:16px'>Weather - www.weather.com</li>
  <li style='font-size:16px'>Wrzuta - www.wrzuta.pl (includes audio and video both)</li>
  <li style='font-size:16px'>Youku - www.youku.com</li>
</ul>

<div style='font-size:26px;margin-top:15px;margin-bottom:15px'>{listofsupportedporn}</div>

<ul style='list-style: none outside none;'>
  <li style='font-size:16px'>ExtremeTube - www.extremetube.com</li>
  <li style='font-size:16px'>HardSexTube - www.hardsextube.com</li>
  <li style='font-size:16px'>KeezMovies - www.keezmovies.com</li>
  <li style='font-size:16px'>PornHub - www.pornhub.com</li>
  <li style='font-size:16px'>RedTube - www.redtube.com</li>
  <li style='font-size:16px'>SlutLoad - www.slutload.com</li>
  <li style='font-size:16px'>SpankWire - www.spankwire.com</li>
  <li style='font-size:16px'>Tube8 - www.tube8.com</li>
  <li style='font-size:16px'>Xhamster - www.xhamster.com</li>
  <li style='font-size:16px'>XTube - www.xtube.com</li>
  <li style='font-size:16px'>XVideos - www.xvideos.com</li>
  <li style='font-size:16px'>YouPorn - www.youporn.com</li>
</ul>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

