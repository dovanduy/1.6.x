<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["graph"])){(GenGraphs($_GET["time"]));exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week();exit;}
if(isset($_GET["month"])){month();exit;}
if(isset($_GET["year"])){year();exit;}	
if(isset($_GET["report"])){report();exit;}	
if(isset($_GET["tuning"])){tuning();exit;}
if(isset($_GET["rbuildbtt"])){rbuildbtt();exit;}
if(isset($_POST["MysqlTunerRebuild"])){MysqlTunerRebuild();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$title=$tpl->_ENGINE_parse_body("{mysql_graphs}::Instance $instance_id");
	$html="RTMMail('790','$page?tabs=yes&instance-id=$instance_id','$title');";
	echo $html;
}

function report(){
	$instance_id=$_GET["instance-id"];
	if($instance_id>0){
		$q=new mysql_multi($instance_id);
		$user=base64_encode($q->mysql_admin);
		$password=base64_encode($q->mysql_password);
		$socket=base64_encode($q->SocketPath);
		$hostname=base64_encode($q->listen_addr);
		$port=base64_encode($q->mysql_port);
		
	}else{
		$q=new mysql();
		$user=base64_encode($q->mysql_admin);
		$password=base64_encode($q->mysql_password);
		$hostname=base64_encode($q->mysql_server);
		$port=base64_encode($q->mysql_port);
	}
	
	$sock=new sockets();
	$datas=$sock->getFrameWork("mysql.php?mysqlreport=yes&user=$user&password=$password&socket=$socket&hostname=$hostname&port=$port&instance-id=$instance_id");
	$tr=unserialize(base64_decode($datas));
	
	while (list ($num, $ligne) = each ($tr) ){
	
		
		
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+)#", $ligne,$re)){
			$html=$html."
			<tr>
				<td><code style='font-size:14px'>{$re[1]}</code></td>
				<td><code style='font-size:14px'>{$re[2]}</code></td>
				<td><code style='font-size:14px'>{$re[3]}</code></td>
				<td><code style='font-size:14px'>{$re[4]}</code></td>
				
				
			</tr>
			";
			continue;
		}
		$html=$html."<tr><td colspan=4><code style='font-size:14px'>$ligne</code></td></tr>";
		
		
	}
	

	
	$html="<table style='width:95%' class=form>$html</table>";
	echo $html;
	
}

function tabs(){
	$tpl=new templates();
	$array["hour"]='{thishour}';
	$array["today"]='{today}';
	$array["week"]='{last_7_days}';
	$array["month"]='{month}';
	$array["year"]='{year}';
	$array["report"]='{mysql_report}';
	$array["tuning"]='{mysql_tuning}';
	$page=CurrentPageName();
	$instance_id=$_GET["instance-id"];
	$time=time();
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="report"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?report=yes&time=$num&instance-id=$instance_id&t=$time\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="tuning"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?tuning=yes&time=$num&instance-id=$instance_id&t=$time\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?graph=yes&time=$num&instance-id=$instance_id&t=$time\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	echo "
	<div id=main_mysql_graphs$instance_id style='width:100%;height:750px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_mysql_graphs$instance_id').tabs();
			
			
			});
		</script>";	
}

function GenGraphs($time="hour"){
$tpl=new templates();
$requests=$tpl->javascript_parse_text("{requests}");
$connections_number=$tpl->javascript_parse_text("{connections_number}");
$requests_number=$tpl->javascript_parse_text("{requests_number}");
$received=$tpl->javascript_parse_text("{received}");
$sended=$tpl->javascript_parse_text("{sended}");
$network=$tpl->javascript_parse_text("{network");
if($time=="hour"){$start="-1hour";}
if($time=="today"){$start="-1day";}
if($time=="week"){$start="-1week";}
if($time=="month"){$start="-1month";}
if($time=="year"){$start="-1year";}
$t=time();
$instance_id=$_GET["instance-id"];
$WORKING_DIR="/usr/share/artica-postfix/ressources/databases/rrd";
$DBFILE=$WORKING_DIR."/mysql$instance_id.rrd";
if(is_file("$WORKING_DIR/cnx-$time-$instance_id.png")){@unlink("$WORKING_DIR/cnx-$time-$instance_id.png");}
$a[]="rrdtool graph $WORKING_DIR/cnx-$time-$instance_id.png";
$a[]="--vertical-label \"$requests /s\" --title \"$connections_number\"";
$a[]="--start $start --width 640 --height 250";
$a[]="DEF:totaleedc=$DBFILE:total:AVERAGE LINE1:totaleedc#FF0000:\"SQL \"";
$a[]="GPRINT:totaleedc:MAX:\"\\tMax\: %6.2lf%s\" GPRINT:totaleedc:MIN:\"Min\: %6.2lf%s\"";
$a[]="GPRINT:totaleedc:AVERAGE:\"Avg\: %6.2lf%s\" GPRINT:totaleedc:LAST:\"Cur\: %6.2lf%s\\n\"";
$a[]="DEF:connect7fb3=$DBFILE:connect:AVERAGE AREA:connect7fb3#009977:\"TCP \" GPRINT:connect7fb3:MAX:\"\\tMax\: %6.2lf%s\"";
$a[]="GPRINT:connect7fb3:MIN:\"Min\: %6.2lf%s\" GPRINT:connect7fb3:AVERAGE:\"Avg\: %6.2lf%s\" GPRINT:connect7fb3:LAST:\"Cur\: %6.2lf%s\\n\" 2>&1";

$cmdline=@implode(" ", $a);
exec(@implode(" ", $a),$results);

if(is_file("$WORKING_DIR/query-$time-$instance_id.png")){@unlink("$WORKING_DIR/query-day-$instance_id.png");}

$b[]="rrdtool graph $WORKING_DIR/query-$time-$instance_id.png ";
$b[]="--vertical-label \"$requests_number /s\" ";
$b[]="--title \"$requests_number\" ";
$b[]="--start $start ";
$b[]="--width 640 ";
$b[]="--height 250 ";
$b[]="DEF:select1d00=$DBFILE:select:AVERAGE AREA:select1d00#00DD22:\"Select \"";
$b[]="GPRINT:select1d00:MAX:\"\\tMax\: %6.2lf%s\"";
$b[]="GPRINT:select1d00:MIN:\"Min\: %6.2lf%s\" ";
$b[]="GPRINT:select1d00:AVERAGE:\"Avg\: %6.2lf%s\"";
$b[]="GPRINT:select1d00:LAST:\"Cur\: %6.2lf%s\\n\"";
$b[]="DEF:insert5a25=$DBFILE:insert:AVERAGE LINE1:insert5a25#EEAF00:\"Insert \"";
$b[]="GPRINT:insert5a25:MAX:\"\\tMax\: %6.2lf%s\"";
$b[]="GPRINT:insert5a25:MIN:\"Min\: %6.2lf%s\" ";
$b[]="GPRINT:insert5a25:AVERAGE:\"Avg\: %6.2lf%s\"";
$b[]="GPRINT:insert5a25:LAST:\"Cur\: %6.2lf%s\\n\"";
$b[]="DEF:delete4f48=$DBFILE:delete:AVERAGE LINE1:delete4f48#FF0000:\"Delete \"";
$b[]="GPRINT:delete4f48:MAX:\"\\tMax\: %6.2lf%s\"";
$b[]="GPRINT:delete4f48:MIN:\"Min\: %6.2lf%s\" ";
$b[]="GPRINT:delete4f48:AVERAGE:\"Avg\: %6.2lf%s\"";
$b[]="GPRINT:delete4f48:LAST:\"Cur\: %6.2lf%s\\n\"";
$b[]="DEF:update8bff=$DBFILE:update:AVERAGE AREA:update8bff#0022DD:\"Update \"";
$b[]="GPRINT:update8bff:MAX:\"\\tMax\: %6.2lf%s\"";
$b[]="GPRINT:update8bff:MIN:\"Min\: %6.2lf%s\"";
$b[]="GPRINT:update8bff:AVERAGE:\"Avg\: %6.2lf%s\"";
$b[]="GPRINT:update8bff:LAST:\"Cur\: %6.2lf%s\\n\" 2>&1";

$cmdline=@implode(" ", $b);
exec(@implode(" ", $b),$results);

if(is_file("$WORKING_DIR/net-$time-$instance_id.png")){@unlink("$WORKING_DIR/net-$time-$instance_id.png");}
$c[]="rrdtool graph $WORKING_DIR/net-$time-$instance_id.png";
$c[]="--vertical-label \"Bytes /s\"";
$c[]="--title \"$network\"";
$c[]="--start $start";
$c[]="--width 640";
$c[]="--height 250";
$c[]="DEF:inbound5fd3=$DBFILE:inbound:AVERAGE AREA:inbound5fd3#DD2222:\"$received \"";
$c[]="GPRINT:inbound5fd3:MAX:\"\\tMax\: %6.2lf%s\"";
$c[]="GPRINT:inbound5fd3:MIN:\"Min\: %6.2lf%s\"";
$c[]="GPRINT:inbound5fd3:AVERAGE:\"Avg\: %6.2lf%s\"";
$c[]="GPRINT:inbound5fd3:LAST:\"Cur\: %6.2lf%s\\n\"";
$c[]="DEF:outbound22eb=$DBFILE:outbound:AVERAGE AREA:outbound22eb#009977:\"$sended \"";
$c[]="GPRINT:outbound22eb:MAX:\"\\tMax\: %6.2lf%s\"";
$c[]="GPRINT:outbound22eb:MIN:\"Min\: %6.2lf%s\"";
$c[]="GPRINT:outbound22eb:AVERAGE:\"Avg\: %6.2lf%s\"";
$c[]="GPRINT:outbound22eb:LAST:\"Cur\: %6.2lf%s\\n\"";
$cmdline=@implode(" ", $c);
exec(@implode(" ", $c),$results);

echo "
<center>
<img src='ressources/databases/rrd/cnx-$time-$instance_id.png?$t' style='margin-bottom:5px'>
<img src='ressources/databases/rrd/query-$time-$instance_id.png?$t style='margin-bottom:5px''>
<img src='ressources/databases/rrd/net-$time-$instance_id.png?$t' style='margin-bottom:5px'>
</center>";
 	
	
}
function tuning(){
	$page=CurrentPageName();
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$f=@file("ressources/mysqltuner/instance-$instance_id.db");
	$rebuildS=button("{rebuild}","MySqlTunerBuild()",16);
	$t=time();
	$html="<table style='width:99%' class=form>";
	
	while (list ($index, $ligne) = each ($f) ){
		if(trim($ligne)==null){continue;}
		if(strpos($ligne,"***")>0){
			$ligne="<span style='color:#D50A0A'>$ligne</span>";
		}
		
		if(strpos($ligne,"feature requests,")>0){continue;}
		if(strpos($ligne,"for additional options")>0){continue;}
		if(strpos($ligne,"MySQLTuner")>0){continue;}
		
		$ligne=str_replace("%%REBUILD", "<div id='$t' style='float:right'></div>", $ligne);
		
		$ligne=trim($ligne);
		if(preg_match("#^>>\s+(.+)#", $ligne,$re)){
			$icon="<img src='img/arrow.gif'>";
			$html=$html."<tr>
			<td width=1%>&nbsp;</td>
			<td style='font-size:14px;font-weight:bold' width=99%>{$re[1]}</td>
			</tr>
			";
			continue;
			
		}
		if(preg_match("#^\[\-\-\]\s+(.+)#", $ligne,$re)){
			$icon="<img src='img/info-24.png'>";
			$html=$html."<tr>
			<td width=1%>$icon</td>
			<td style='font-size:14px'>{$re[1]}</td>
			</tr>
			";
			continue;			
			
		}
		if(preg_match("#^\[OK]\s+(.+)#", $ligne,$re)){
			$icon="<img src='img/ok24.png'>";
			$html=$html."<tr>
			<td width=1%>$icon</td>
			<td style='font-size:14px'>{$re[1]}</td>
			</tr>
			";
			continue;			
			
		}		
		if(preg_match("#^\[\!\!]\s+(.+)#", $ligne,$re)){
			$icon="<img src='img/warning-panneau-24.png'>";
			$html=$html."<tr>
			<td width=1%>$icon</td>
			<td style='font-size:14px'>{$re[1]}</td>
			</tr>
			";
			continue;			
			
		}

		if(preg_match("#--------\s+(.+?)---#", $ligne,$re)){
			
			$html=$html."
			<tr>
				<td colspan=2>&nbsp;</td>
			</tr>
			<tr>
				<td colspan=2 style='font-size:18px;font-weight:bold;padding-bottom:3px;border-bottom:2px solid #CCCCCC'>{$re[1]}</td>
			</tr>
			<tr>
				<td colspan=2>&nbsp;</td>
			</tr>
			";
			continue;				
			
		}
		
$html=$html."<tr>
				<td width=1%>&nbsp;</td>
				<td colspan=2 style='font-size:14px'>$ligne</td>
			</tr>
			";		
		
		
	}
	
	$html=$html."</table>
	
	<script>
		LoadAjaxTiny('$t','$page?rbuildbtt=yes&instance-id=$instance_id');
		
		
	var x_MySqlTunerBuild= function (obj) {
			var results=obj.responseText;
			alert(results);
			RefreshTab('main_mysql_graphs$instance_id');
		}		
	
		function MySqlTunerBuild(){	
			var XHR = new XHRConnection();
			XHR.appendData('MysqlTunerRebuild','yes');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_MySqlTunerBuild);
			
		}	
		
	</script>
	";
	echo $html;
	
}
function rbuildbtt(){
$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}	
	$tpl=new templates();
	$rebuildS=button("{rebuild}","MySqlTunerBuild()",16);
	$html="<table width=1%>
	<tr>
	<td>$rebuildS</td>
	<td>". imgtootltip("32-refresh.png","{refresh}","RefreshTab('main_mysql_graphs$instance_id');")."</td>
	</tr>
	</table>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function MysqlTunerRebuild(){
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?MysqlTunerRebuild=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{install_app}");
	
}
