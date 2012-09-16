<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo @implode(" ", $argv)."\n";}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if($argv[1]=="--restart"){restart_lighttpd();die();}
if($argv[1]=="--phpmyadmin"){phpmyadmin_secu();die();}

lighttpd_nets();


function phpmyadmin_secu(){
	$database="artica_backup";
	$q=new mysql();
	
	$sql="SELECT * FROM phpmyadminsecu WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["pattern"]=trim($ligne["pattern"]);
		if($ligne["pattern"]==null){continue;}
		
		if($ligne["type"]==0){
			$ips[]=$ligne["pattern"];
		}
			
	}
	
	$final=null;
	if(count($ips)>0){	
			$arrayIP[]="\$HTTP[\"remoteip\"] !~ \"". @implode("|",$ips)."\" {";
			$arrayIP[]="\t\$HTTP[\"url\"] =~ \"^/mysql($|/)\" {";
			$arrayIP[]="\t\turl.access-deny = ( \"\" )";
			$arrayIP[]="\t}";					
			$arrayIP[]="}";	
			$final=@implode("\n", $arrayIP);

		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo $final;}
	@unlink("/etc/artica-postfix/lighttpd.phpmyadmin");
	@file_put_contents("/etc/artica-postfix/lighttpd.phpmyadmin", $final);
	
}

function restart_lighttpd(){
	$t=time();
	exec("/etc/init.d/artica-postfix restart apache 2>&1",$results);
	$unix=new unix();
	$took=$unix->distanceOfTimeInWords($t,time());
	system_admin_events("Restart Web interface service done took:$took\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "system");
	
}


function lighttpd_nets(){

$file="/etc/artica-postfix/settings/Daemons/LighttpdNets";
if(!is_file("/etc/artica-postfix/settings/Daemons/LighttpdNets")){
	@unlink("/etc/artica-postfix/lighttpd_nets");
	return;
}

$LighttpdNets=unserialize(base64_decode(@file_get_contents($file)));


if(is_array($LighttpdNets["IPS"])){
	while (list ($num, $ligne) = each ($LighttpdNets["IPS"]) ){
		if(trim($ligne)==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "$ligne\n";}
		$nets[$ligne]=$ligne;
	}
}
if(is_array($LighttpdNets["NETS"])){
	while (list ($num, $ligne) = each ($LighttpdNets["NETS"]) ){
		if(trim($ligne)==null){continue;}
		if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+).([0-9]+)\/([0-9]+)#",$ligne,$re)){
			$newip="{$re[1]}.{$re[2]}.{$re[3]}.*";
			if($GLOBALS["VERBOSE"]){echo "$newip\n";}
			$nets[$newip]=$newip;
		}else{
			if($GLOBALS["VERBOSE"]){echo "No match $ligne\n";}
		}
	}
}

	$sql="SELECT * FROM glusters_clients ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["client_ip"])==null){continue;}
		$nets[$ligne["client_ip"]]=$ligne["client_ip"];
	}

if(!is_array($nets)){
	@unlink("/etc/artica-postfix/lighttpd_nets");
	die();	
}

while (list ($num, $ligne) = each ($nets) ){
	$f[]=$ligne;
	
}

$content="\$HTTP[\"remoteip\"] !~ \"".@implode("|",$f)."\"{   url.access-deny = ( \"\" ) }";

if($GLOBALS["VERBOSE"]){echo $content."\n";}
@file_put_contents("/etc/artica-postfix/lighttpd_nets",$content);

}

?>