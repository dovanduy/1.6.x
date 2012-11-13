<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.rrd.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){die();}
if(isset($_GET["all"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hour"])){hour();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week();exit;}
if(isset($_GET["month"])){month();exit;}
if(isset($_GET["year"])){year();exit;}
if(isset($_POST["LoadAvgClean"])){LoadAvgClean();exit;}

injectSquid();
PageDeGarde();
License();



exit;

function injectSquid(){
	if($GLOBALS["VERBOSE"]){echo "InjectSquid ->\n<br>";}
	$users=new usersMenus();
	$run=false;
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if($users->PROXYTINY_APPLIANCE){return;}
	if($EnableWebProxyStatsAppliance==1){$users->WEBSTATS_APPLIANCE=true;}
	if($users->WEBSTATS_APPLIANCE){$run=true;}
	if($users->SQUID_INSTALLED){$run=true;}
	if($GLOBALS["VERBOSE"]){echo "run -> $run\n<br>";}
	if(!$run){return;}	
	$inf=trim($sock->getFrameWork("squid.php?isInjectrunning=yes") );
	if($GLOBALS["VERBOSE"]){echo "inf -> $inf\n<br>";}
	if($inf<>null){
		$tpl=new templates();
	$html="<div style='margin-bottom:15px'>".
	Paragraphe("tables-64-running.png", "{update_dbcatz_running}","{update_SQUIDAB_EXP}<hr><b>{since}:&nbsp;{$inf}&nbsp;{minutes}</b>", 
	"javascript:Loadjs('squid.blacklist.upd.php')","go_to_section",300,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";	
	return;	
	}
	
	$datas=base64_decode($sock->getFrameWork("squid.php?articadb-checkversion=yes"));
	if($GLOBALS["VERBOSE"]){echo "$datas<br>\n";}
	
	$LOCAL_VERSION=$sock->getFrameWork("squid.php?articadb-version=yes");
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-nextversion=yes")));
	$REMOTE_VERSION=$array["ARTICATECH"]["VERSION"];
	$REMOTE_MD5=$array["ARTICATECH"]["MD5"];
	$REMOTE_SIZE=$array["ARTICATECH"]["SIZE"];	
	$REMOTE_SIZE=FormatBytes($REMOTE_SIZE/1024);
	if($REMOTE_VERSION>$LOCAL_VERSION){
		$tpl=new templates();
		$html="<div style='margin-bottom:15px'>".
		Paragraphe("64-download.png", "{new_database_available}","{new_database_available_category_text}<hr>{version}:$REMOTE_VERSION ($REMOTE_SIZE)", 
		"javascript:Loadjs('squid.categories.php')","go_to_section",300,132,1);
		echo $tpl->_ENGINE_parse_body($html)."</div>";	
		return;			
	}
	
}








function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{computer_load}");
	
	echo "YahooWin3('750','$page?tabs=yes','$title')";
	
	
}


function license(){
	$users=new usersMenus();
	$tpl=new templates();
	if($users->CORP_LICENSE){return;}
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	
		
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	if($LicenseInfos["license_status"]==null){
		$text="{explain_license_free}";
		
	}else{
		$text="{explain_license_order}";
	}
		
	$html="<div style='margin-top:15px'>".
	Paragraphe("license-error-64.png", "{artica_license}",$text, 
	"javascript:Loadjs('artica.license.php')","go_to_section",300,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";
}

function tabs(){
	$tpl=new templates();
	$array["today"]='{today}';
	$array["week"]='{last_7_days}';
	$array["month"]='{month}';
	$array["year"]='{year}';
	$page=CurrentPageName();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$time\"><span>$ligne</span></a></li>\n");
	}
	echo "
	<div id=main_loadavgtabs style='width:100%;height:750px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_loadavgtabs').tabs();
			
			
			});
		</script>";	
}	

	if($GLOBALS["VERBOSE"]){echo __LINE__." instanciate artica_graphs()<br>\n";}

	$gp=new artica_graphs();
	$memory_average=$tpl->_ENGINE_parse_body("{memory_use} {today} (MB)");
	if($GLOBALS["VERBOSE"]){echo "<hr>";}
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tday,HOUR(zDate) as thour,AVG(mem) as tmem FROM ps_mem_tot GROUP BY tday,thour HAVING tday=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY thour";
	if($GLOBALS["VERBOSE"]){echo "<code>$sql</code><br>";}
	$results=$q->QUERY_SQL($sql,"artica_events");
	$mysql_num_rows=mysql_num_rows($results);
	$xtitle=$tpl->javascript_parse_text("{hours}");
	
	if($mysql_num_rows<2){
		$sql="SELECT DATE_FORMAT(zDate,'%h') as thour2,DATE_FORMAT(zDate,'%i') as thour, AVG(mem) as tmem FROM ps_mem_tot GROUP BY DATE_FORMAT(zDate,'%H-%i')
		HAVING thour2=DATE_FORMAT(NOW(),'%h') ORDER BY DATE_FORMAT(zDate,'%H-%i') ";
		if($GLOBALS["VERBOSE"]){echo "<code>$sql</code><br>";}
		$results=$q->QUERY_SQL($sql,"artica_events");
		$memory_average=$tpl->_ENGINE_parse_body("{memory_use} {this_hour} (MB)");
		$mysql_num_rows=mysql_num_rows($results);
		$xtitle=$tpl->javascript_parse_text("{minutes}");		
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".ps-mem.png";
	$xdata=array();
	$ydata[]=array();
	writelogs("mysql return no rows from a table of $mysql_num_rows rows ",__FUNCTION__,__FILE__,__LINE__);
	if($mysql_num_rows>0){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$size=$ligne["tmem"];
				$size=$size/1024;
				$size=$size/1000;
				$size=round($size/1000,0);
				$gp->xdata[]=$ligne["thour"];
				$gp->ydata[]=$size;
				$c++;
				if($GLOBALS["VERBOSE"]){echo "<li>ps_mem $hour -> $size</li>";};
			}
			if($c==0){writelogs("Fatal \"$targetedfile\" no items",__FUNCTION__,__FILE__,__LINE__);return;}
			if(is_file($targetedfile)){@unlink($targetedfile);}
			
			$gp->width=300;
			$gp->height=120;
			$gp->filename="$targetedfile";
			$gp->y_title=null;
			$gp->x_title=$xtitle;
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$tpl=new templates();
			
			//$gp->SetFillColor('green'); 
			
			$gp->line_green();
			if(is_file($targetedfile)){
				echo "<center><div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
				this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed58\" OnClick=\"javascript:Loadjs('admin.index.psmem.php?all=yes')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$memory_average</h3>
				<img src='$targetedfile'>
				</div></center>";	
			}	
		
	}
	
// --------------------------------------------------------------------------------------	
	
	
	
	
	
	
	$sock=new sockets();
	$users=new usersMenus();
	$EnableBandwithCalculation=$sock->GET_INFO("EnableBandwithCalculation");
	if(!is_numeric($EnableBandwithCalculation)){$EnableBandwithCalculation=1;}
	
	
writelogs("Checking milter-greylist",__FUNCTION__,__FILE__,__LINE__);	
// --------------------------------------------------------------------------------------
	if($users->MILTERGREYLIST_INSTALLED){
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==0){
			$APP_MILTERGREYLIST=$tpl->_ENGINE_parse_body("{APP_MILTERGREYLIST}");
			if(is_file("ressources/logs/greylist-count-master.tot")){
			$datas=unserialize(@file_get_contents("ressources/logs/greylist-count-master.tot"));
			if(is_array($datas)){
				@unlink("ressources/logs/web/mgreylist.master1.db.png");
				$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/mgreylist.admin.index.db.png",0);
				$gp->xdata[]=$datas["GREYLISTED"];
				$gp->ydata[]="greylisted";	
				$gp->xdata[]=$datas["WHITELISTED"];
				$gp->ydata[]="whitelisted";				
				$gp->width=300;
				$gp->height=120;
				$gp->PieExplode=5;
				
				$gp->ViewValues=false;
				$gp->x_title=null;
				$gp->pie();	
				
				if(is_file("ressources/logs/web/mgreylist.admin.index.db.png")){	
				echo "<div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
				this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed5898\" OnClick=\"javascript:Loadjs('milter.greylist.index.php?js=yes&in-front-ajax=yes')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$APP_MILTERGREYLIST</h3>
				<img src='ressources/logs/web/mgreylist.admin.index.db.png'>
				</div>";
				}
				
				
			}
			}
		}
		
	}
	
	
// --------------------------------------------------------------------------------------	

	if($users->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==1){
			writelogs("Checking squid perf",__FUNCTION__,__FILE__,__LINE__);	
			$cachedTXT=$tpl->_ENGINE_parse_body("{cached}");
			$NOTcachedTXT=$tpl->_ENGINE_parse_body("{not_cached}");
			$today=$tpl->_ENGINE_parse_body("{today}");
			$sql="SELECT SUM( size ) as tsize, cached FROM squid_cache_perfs WHERE DATE_FORMAT( zDate, '%Y-%m-%d' ) = DATE_FORMAT( NOW( ) , '%Y-%m-%d' ) GROUP BY cached LIMIT 0 , 30";
			$results=$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				
				if($ligne["cached"]==1){$cached_size=$ligne["tsize"];}
				if($ligne["cached"]==0){$not_cached_size=$ligne["tsize"];}
			}
				writelogs("Cached: $cached_size not cached: $not_cached_size bytes",__FUNCTION__,__FILE__,__LINE__);
			
			if(($cached_size>0) &&  ($not_cached_size>0)){
				
				
				$sum=$cached_size+$not_cached_size;
				$pourcent=round(($cached_size/$sum)*100);
				$title=$tpl->_ENGINE_parse_body("{cache_performance} $pourcent%");
				$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/squid.cache.perf.today.png",0);
				$gp->xdata[]=$cached_size;
				$gp->ydata[]="$cachedTXT ".FormatBytes($cached_size/1024);	
				$gp->xdata[]=$not_cached_size;
				$gp->ydata[]="$NOTcachedTXT ".FormatBytes($not_cached_size/1024);					
				$gp->width=300;
				$gp->height=120;
				$gp->PieExplode=5;
				$gp->PieLegendHide=true;
				$gp->ViewValues=false;
				$gp->x_title=null;
				$gp->pie();	
				
				if(is_file("ressources/logs/web/squid.cache.perf.today.png")){	
					echo "<div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" onmouseover=\"javascript:this.className='paragraphe_over';
					this.style.cursor='pointer';\" id=\"6ce2f4832d82c6ebaf5dfbfa1444ed58910\" OnClick=\"javascript:Loadjs('squid.cache.perf.stats.php')\" class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
					<h3 style='text-transform: none;margin-bottom:5px'>$title</h3>
					<div style='font-size:11px;margin-top:-8px'>$today: $cachedTXT: ".FormatBytes($cached_size/1024)." - $NOTcachedTXT ".FormatBytes($not_cached_size/1024)."</div>
					<img src='ressources/logs/web/squid.cache.perf.today.png'>
					</div>";
				}else{
					writelogs("ressources/logs/web/squid.cache.perf.today.png no such file",__FUNCTION__,__FILE__,__LINE__);
				}			
			
			}
			
		}
	}
	
// --------------------------------------------------------------------------------------	

	if($EnableBandwithCalculation==1){
		$targetedfile="ressources/logs/".basename(__FILE__).".bandwithm.png";
		$sql="SELECT DATE_FORMAT(zDate,'%H') as tdate,AVG(download) as tbandwith FROM speedtests 
		WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') 
		GROUP BY DATE_FORMAT(zDate,'%H')
		ORDER BY zDate";
		$results=$q->QUERY_SQL($sql,"artica_events");
		if(mysql_num_rows($results)>1){
			$xtitle=$tpl->javascript_parse_text("{hours}");
			$maintitle=$tpl->javascript_parse_text("{today}: {bandwith}");
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$size=round($ligne["tbandwith"],0);
					$gp->xdata[]=$ligne["thour"];
					$gp->ydata[]=$ligne["tdate"];
					$c++;
					
				}
			
			if(is_file($targetedfile)){@unlink($targetedfile);}
			
			$gp->width=300;
			$gp->height=120;
			$gp->filename="$targetedfile";
			$gp->y_title=null;
			$gp->x_title=$xtitle;
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$tpl=new templates();
			$gp->line_green();	
			if(is_file($targetedfile)){		
				echo "<center><div onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\" 
				onmouseover=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\" 
				id=\"". md5(time())."\" OnClick=\"javascript:Loadjs('bandwith.stats.php')\" 
				class=\"paragraphe\" style=\"width: 300px; min-height: 112px; cursor: default;\">
				<h3 style='text-transform: none;margin-bottom:5px'>$maintitle</h3>
				<img src='$targetedfile'>
				</div></center>";	
			}
			
		}
	}
// --------------------------------------------------------------------------------------		

	
echo "</center>
<div id='notifs-part'></div>
<script>LoadAjax('notifs-part','admin.left.php?partall=yes');</script>

";
	
	
function hour(){

$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
$sql="SELECT AVG( `load` ) AS sload, DATE_FORMAT( stime, '%i' ) AS ttime FROM `loadavg` WHERE `stime` > DATE_SUB( NOW( ) , INTERVAL 60 MINUTE ) GROUP BY ttime ORDER BY `ttime` ASC";

	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$count=mysql_num_rows($results);
	
	if(mysql_num_rows($results)==0){return;}	
	
	if(!$q->ok){echo $q->mysql_error;}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["tsize"]/1024;
		$size=$size/1000;
		$xdata[]=$ligne["ttime"];
		$ydata[]=$ligne["sload"];
		$c++;
		if($ligne["sload"]>$cpunum){
			if($GLOBALS["VERBOSE"]){echo "<li>!!!! {$ligne["stime"]} -> $c</LI>";};
			if(!isset($red["START"])){$red["START"]=$c;}
		}else{
			if(isset($red["START"])){
				$area[]=array($red["START"],$c);
				unset($red);
			}
		}
		
	

		
		
		if($GLOBALS["VERBOSE"]){echo "<li>{$ligne["stime"]} -> {$ligne["ttime"]} -> {$ligne["sload"]}</LI>";};
	}
	if(isset($red["START"])){$area[]=array($red["START"],$c);}

	$file=time();
	$gp=new artica_graphs();
	$gp->RedAreas=$area;
	$gp->width=650;
	$gp->height=350;
	$gp->filename="ressources/logs/loadavg-hour.png";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title="Mn";
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$tpl=new templates();
	//$gp->SetFillColor('green'); 
	
	$gp->line_green();
	
	echo "
	<div id='loadavg-clean'>
	<img src='ressources/logs/loadavg-hour.png'></div></div>
	<div style='text-align:right'><hr>".$tpl->_ENGINE_parse_body(button("{clean_datas}","LoadAvgClean()"))."</div>
	<script>
	
	var x_LoadAvgClean=function(obj){
      var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
      YahooWin3Hide();
      document.getElementById('loadavggraph').innerHTML='';
      }	
	
	function LoadAvgClean(){
		var XHR = new XHRConnection();
		XHR.appendData('LoadAvgClean','yes');
		
		AnimateDiv('loadavg-clean');
		XHR.sendAndLoad('$page', 'POST',x_LoadAvgClean);		
		}	
	
	
	</script>";	
	
	
}

function today(){


	
	if(!is_file("/opt/artica/var/rrd/yorel/loadavg_1.rrd")){
		$sock=new sockets();
		$sock->getFrameWork("services.php?yorel-rebuild=yes");
		
	}
	
$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {today}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=680;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1day";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadd.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadd.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {today}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1day";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memd.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memd.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {today}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1day";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpud.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpud.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";
}
function week(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {week}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=680;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1week";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadw.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {week}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1week";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memw.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {week}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1week";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpuw.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpuw.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";	
	
	
	
}

function month(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {month}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=680;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1month";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loadm.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loadm.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {month}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1month";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memm.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memm.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {month}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1month";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpum.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpum.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";		
	
}
function year(){


$page=CurrentPageName();
$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
$cpunum=$GLOBALS["CPU_NUMBER"]+1;
	$tpl=new templates();
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {year}"));
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	$rrd->width=680;
	$rrd->height=250;
	$rrd->graphTitle=$title;
	$rrd->timestart="-1year";
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.loady.png","loadavg_1")){	
		$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img="<img src=\"ressources/logs/rrd.loady.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}
	
	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {year}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1year";
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.memy.png","mem_user")){	
		$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img2="<img src=\"ressources/logs/rrd.memy.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {year}"));
	$rrd->width=680;
	$rrd->height=250;
	$rrd->timestart="-1year";
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpuy.png","cpu_user")){	
		$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpuy.png?$id\" style='margin-top:5px;border:1px solid #7A7A7A'>";
	}	
	
	
	
	echo "
	<center>
	$img
	$img2
	$img3
	</center>		";		
	
}
function PageDeGarde(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(GET_CACHED(__FILE__, __FUNCTION__,null,false,1)){return;}
	
	if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){return;}}
	
	$title=html_entity_decode($tpl->javascript_parse_text("{server_load} {thishour}"));
	if($GLOBALS["VERBOSE"]){echo __LINE__." rrdbuilder()<br>\n";}
	
	try{
		$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/loadavg_1.rrd");
	} 
	catch (Exception $e) {
		echo "<strong>" . $e->getMessage()."</strong><br>";
		return false; 
	}
	
	
	$rrd->width=299;
	$rrd->height=130;
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{server_load}"));
	$sock=new sockets();
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$id=time();
	if($GLOBALS["VERBOSE"]){echo __LINE__." buildgraph()<br>\n";}
	if(!is_dir(dirname(__FILE__)."/ressources/logs")){
		@mkdir(dirname(__FILE__)."/ressources/logs",0777,true);
	}

	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.load.png","loadavg_1")){	
		if(is_file(dirname(__FILE__)."/ressources/logs/rrd.load.png")){
			$img="<img src=\"ressources/logs/rrd.load.png?$id\">";
		}else{
			$img="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
		}
	}else{
		$img="<img src=\"ressources/logs/rrd.load.png?$id\">";
	}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." rrdbuilder(/opt/artica/var/rrd/yorel/mem_user.rrd)<br>\n";}	
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/mem_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{memory} {thishour}"));
	$rrd->width=299;
	$rrd->height=130;
	$rrd->graphTitle=$title;
	$rrd->watermark="-- ".date('H:i:s')." --";	
	$rrd->base=1024;
	$rrd->GPRINT="%7.2lf %sb";
	$rrd->LineColor="#0136BA";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{memory} MB"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.mem.png","mem_user")){	
		if(is_file(dirname(__FILE__)."/ressources/logs/rrd.mem.png")){
			$img2="<img src=\"ressources/logs/rrd.mem.png?$id\">";
		}else{
			$img2="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
		}
	}else{
		$img2="<img src=\"ressources/logs/rrd.mem.png?$id\">";
	}	
	$sock->getFrameWork("services.php?chmod-rrd=yes");
	$rrd=new rrdbuilder("/opt/artica/var/rrd/yorel/cpu_user.rrd");
	$title=html_entity_decode($tpl->javascript_parse_text("{cpu} {thishour}"));
	$rrd->width=299;
	$rrd->height=130;
	$rrd->graphTitle=$title;
	$rrd->units_exponent=0;
	$rrd->upper_limit=100;
	$rrd->lower_limit=0;
	
	$rrd->GPRINT="%05.2lf %%";
	$rrd->LineColor="#287B30";
	$rrd->line_title=html_entity_decode($tpl->javascript_parse_text("{cpu} %"));
	$id=time();
	if(!$rrd->buildgraph(dirname(__FILE__)."/ressources/logs/rrd.cpu.png","cpu_user")){	
		if(is_file(dirname(__FILE__)."/ressources/logs/rrd.cpu.png")){
			$img3="<img src=\"ressources/logs/rrd.cpu.png?$id\">";
		}else{
			$img3="<span style='color:#CB0B0B;font-size:12px'>Graph error:$rrd->error</span>";
		}
	}else{
		$img3="<img src=\"ressources/logs/rrd.cpu.png?$id\">";
	}	
	
	
	
	$html="<div style=\"width: 300px; min-height: 112px; cursor: default;\" class=\"paragraphe\" 
	onclick=\"javascript:Loadjs('admin.index.loadvg.php?all=yes')\" 
	id=\"$id\" 
	onmouseover=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\" 
	onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\">
	$img
	</div>
	<br>
	<div style=\"width: 300px; min-height: 112px; cursor: default;\" class=\"paragraphe\" 
	onclick=\"javascript:Loadjs('admin.index.loadvg.php?all=yes')\" 
	id=\"$id\" 
	onmouseover=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\" 
	onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\">
	$img2
	</div>
	<br>
	<div style=\"width: 300px; min-height: 112px; cursor: default;\" class=\"paragraphe\" 
	onclick=\"javascript:Loadjs('admin.index.loadvg.php?all=yes')\" 
	id=\"$id\" 
	onmouseover=\"javascript:this.className='paragraphe_over';this.style.cursor='pointer';\" 
	onmouseout=\"javascript:this.className='paragraphe';this.style.cursor='default';\">
	$img3
	</div>
	";
	
	SET_CACHED(__FILE__, __FUNCTION__,null,$html);
	echo $html;
	
	
}


function LoadAvgClean(){
	$q=new mysql();
	$q->DELETE_TABLE("loadavg", "artica_events");
	$q->BuildTables();
	
}

?>