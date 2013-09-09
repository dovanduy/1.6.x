<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Haarp";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["RGVS"]=@implode(" ", $argv);
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}
if($argv[1]=="--status"){status();exit;}
if($argv[1]=="--squid-pattern"){squid_pattern();exit;}



function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	squid_admin_mysql(1, "HAARP: Restart operation ordered","{$GLOBALS["RGVS"]}");
	stop(true);
	sleep(1);
	start(true);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	squid_admin_mysql(2, "HAARP: Reconfiguring squid-cache", "Operation occurs after restarting HAARP service");
	shell_exec("$squidbin -k reconfigure >/dev/null 2>&1");
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("haarp");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableHaarp=$sock->GET_INFO("EnableHaarp");
	if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}

	if($EnableHaarp==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service disabled\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service\n";}


	$CMDS[]="$nohup";
	$CMDS[]="$Masterbin --conf-file=/etc/haarp/haarp.conf";
	$CMDS[]=">/dev/null 2>&1 &";
	$cmd=@implode(" ", $CMDS);
	shell_exec($cmd);

	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		Killing();
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

	
	


}

function Killing(){
	$unix=new unix();
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}	
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	
}

function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("haarp"));
	
	
	
}

function PID_PATH(){
	return "/var/run/haarp.pid";
}

function squid_pattern(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$haarp=new haarp(true);
	$sql="SELECT pattern FROM haarp_redirpats";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "Starting......: [HAA]: FATAL ! $q->mysql_error\n";
		return;
	}
	$Countrules=mysql_num_rows($results);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	if(trim($ligne["pattern"])==null){continue;}
			$t[]=trim($ligne["pattern"]);
	
	}
			
	@file_put_contents("/etc/squid3/haarp.acl", @implode("\n",$t));
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --reload-squid >/dev/null 2>&1");
	
	
}


function build(){
	$sock=new sockets();
	$q=new mysql();
	$HaarpPort=$sock->GET_INFO("HaarpPort");
	if(!is_numeric($HaarpPort)){$HaarpPort=0;}
	if($HaarpPort==0){
		$HaarpPort=rand(35000, 64000);
		$sock->SET_INFO("HaarpPort", $HaarpPort);
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Listen Port...: `$HaarpPort`\n";}
	if($q->mysql_server=="localhost"){$q->mysql_server="127.0.0.1";}
	
	
	$HaarpSquidPort=$sock->GET_INFO("HaarpSquidPort");
	if(!is_numeric($HaarpSquidPort)){$HaarpSquidPort=0;}
	if($HaarpSquidPort==0){
		$HaarpSquidPort=rand(38000, 64000);
		$sock->SET_INFO("HaarpSquidPort", $HaarpSquidPort);
	}	
	
	$HaarpConf=unserialize(base64_decode($sock->GET_INFO("HaarpConf")));
	$SERVERNUMBER=$HaarpConf["SERVERNUMBER"];
	$MAXSERVERS=$HaarpConf["MAXSERVERS"];
	if(!is_numeric($SERVERNUMBER)){$SERVERNUMBER="15";}
	if(!is_numeric($MAXSERVERS)){$MAXSERVERS="500";}	
	
	$q=new mysql_squid_builder();
	$haarp=new haarp();
	
	$sql="SELECT * FROM haarp_caches";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=trim($ligne["directory"]);
		@mkdir("$directory",0755,true);
		if(substr($directory, strlen($directory)-1,1)<>"/"){$directory=$directory."/";}
		$dir[]=$directory;
	}
	
	if(count($dir)==0){
		@mkdir("/home/haarp-1",0755,true);
		$dir[]="/home/haarp-1";
	}
	
	$CACHEDIR=@implode("|", $dir);
	$f[]="#";
	$f[]="# PARAMETROS PARA EL HAARP";
	$f[]="CACHEDIR $CACHEDIR";
	$f[]="PLUGINSDIR /etc/haarp/plugins/";
	$f[]="# En porcentage (%)";
	$f[]="CACHE_LIMIT 98";
	$f[]="";
	$f[]="#Zero Penalty Hit para haarp";
	$f[]="# defecto:";
	$f[]="# ZPH_TOS_LOCAL 0";
	$f[]="# recomendado:";
	$f[]="# ZPH_TOS_LOCAL 8";
	$f[]="";
	$f[]="#";
	$f[]="# Configuración del MySQL";
	$f[]="MYSQL_HOST $q->mysql_server";
	$f[]="MYSQL_USER $q->mysql_admin";
	if($q->mysql_password<>null){
		$f[]="MYSQL_PASS $q->mysql_password";
	}
	$f[]="MYSQL_DB artica_backup";
	$f[]="";
	$f[]="# extenciones";
	$f[]="JPG_MIN 1000";
	$f[]="JPG_MAX 0";
	$f[]="JPG_EXP 86400";
	$f[]="";
	$f[]="JPEG_MIN 1000";
	$f[]="JPEG_MAX 0";
	$f[]="JPEG_EXP 86400";
	$f[]="";
	$f[]="GIF_MIN 1000";
	$f[]="GIF_MAX 0";
	$f[]="GIF_EXP 86400";
	$f[]="";
	$f[]="FLV_MIN 1000";
	$f[]="FLV_MAX 0";
	$f[]="FLV_EXP 86400";
	$f[]="";
	$f[]="WMV_MIN 1000";
	$f[]="WMV_MAX 0";
	$f[]="WMV_EXP 86400";
	$f[]="";
	$f[]="WMA_MIN 1000";
	$f[]="WMA_MAX 0";
	$f[]="WMA_EXP 86400";
	$f[]="";
	$f[]="RMVB_MIN 1000";
	$f[]="RMVB_MAX 0";
	$f[]="RMVB_EXP 86400";
	$f[]="";
	$f[]="MPG_MIN 1000";
	$f[]="MPG_MAX 0";
	$f[]="MPG_EXP 86400";
	$f[]="";
	$f[]="MPEG_MIN 1000";
	$f[]="MPEG_MAX 0";
	$f[]="MPEG_EXP 86400";
	$f[]="";
	$f[]="AVI_MIN 1000";
	$f[]="AVI_MAX 0";
	$f[]="AVI_EXP 86400";
	$f[]="";
	$f[]="SWF_MIN 1000";
	$f[]="SWF_MAX 0";
	$f[]="SWF_EXP 86400";
	$f[]="";
	$f[]="DOC_MIN 1000";
	$f[]="DOC_MAX 0";
	$f[]="DOC_EXP 86400";
	$f[]="";
	$f[]="DOCX_MIN 1000";
	$f[]="DOCX_MAX 0";
	$f[]="DOCX_EXP 86400";
	$f[]="";
	$f[]="ZIP_MIN 1000";
	$f[]="ZIP_MAX 0";
	$f[]="ZIP_EXP 86400";
	$f[]="";
	$f[]="RAR_MIN 1000";
	$f[]="RAR_MAX 0";
	$f[]="RAR_EXP 86400";
	$f[]="";
	$f[]="EXE_MIN 1000";
	$f[]="EXE_MAX 0";
	$f[]="EXE_EXP 86400";
	$f[]="";
	$f[]="PPT_MIN 1000";
	$f[]="PPT_MAX 0";
	$f[]="PPT_EXP 86400";
	$f[]="";
	$f[]="PDF_MIN 1000";
	$f[]="PDF_MAX 0";
	$f[]="PDF_EXP 86400";
	$f[]="";
	$f[]="ORKUT_NORESUME true";
	$f[]="ORKUT_NODOWN true";
	$f[]="#";
	$f[]="# Por razones de seguridad es mejor no correr un proxy con el usuario root";
	$f[]="# Pero esto no fue probado, entonces ejecutelo como root";
	$f[]="# Cualquier noticia acerca de esto será bienvenido!";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# USER root";
	$f[]="# GROUP root";
	$f[]="";
	$f[]="#";
	$f[]="# Si es true, correr el haarp en background";
	$f[]="# No es recomendado usar false, podría generar inestabilidad";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# Padrão:";
	$f[]="# DAEMON true";
	$f[]="";
	$f[]="#";
	$f[]="# Lugar donde gravar el pidfile";
	$f[]="# Esencial para el funcionamento del Haarp";
	$f[]="# y também del script /etc/init.d/haarp";
	$f[]="#";
	$f[]="# Default:";
	$f[]="PIDFILE /var/run/haarp.pid";
	$f[]="";
	$f[]="#";
	$f[]="# Número de childs creados por Haarp";
	$f[]="# Se iniciará con el valor de SERVERNUMBER";
	$f[]="# e irá creando childs hasta el límite de MAXSERVERS";
	$f[]="#";
	$f[]="# Default:";
	$f[]="SERVERNUMBER $SERVERNUMBER";
	$f[]="MAXSERVERS $MAXSERVERS";
	$f[]=" ";
	$f[]="# Archivos donde serán guardados los access/errores";
	$f[]="#";
	$f[]="# Default:";
	$f[]="ACCESSLOG /var/log/squid/haarp.access.log";
	$f[]="ERRORLOG /var/log/squid/haarp.log";
	$f[]="";
	$f[]="#";
	$f[]="# Niveles de logs";
	$f[]="#  0 = Sólo errores graves";
	$f[]="#  1 = Informacion detallada.";
	$f[]="#";
	$f[]="# Default:";
	$f[]="LOGLEVEL 0";
	$f[]="#";
	$f[]="# Correr el Haarp como proxy transparente?";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# TRANSPARENT false";
	$f[]="";
	$f[]="#";
	$f[]="# Parent Proxy";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# Standar: NONE";
	$f[]="#PARENTPROXY 127.0.0.1";
	$f[]="#PARENTPORT 5478";
	$f[]="";
	$f[]="#";
	$f[]="# Esto activa escribir en los logs la IP real del usuario, y no la IP del proxya";
	$f[]="# que posiblemente es la que se encuentre frente al Haarp";
	$f[]="# No testeado";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# FORWARDED_IP false";
	$f[]="";
	$f[]="#";
	$f[]="# Enviar X-Forwarded-For: para servers?";
	$f[]="#";
	$f[]="# No se recomiendo su uso, los sites descubrirán";
	$f[]="# la IP interna de su red";
	$f[]="#";
	$f[]="# Default:";
	$f[]="# X_FORWARDED_FOR false";
	$f[]="";
	$f[]="#";
	$f[]="# Puerto de escucha del Haarp.";
	$f[]="#";
	$f[]="# Default:";
	$f[]="PORT $HaarpPort";
	$f[]="";
	$f[]="#";
	$f[]="# IP que HAARP escuchará las solicitudes";
	$f[]="# Deja por defecto para escuchar en todas las interfaces";
	$f[]="# Default:";
	$f[]="BIND_ADDRESS 127.0.0.1";
	@file_put_contents("/etc/haarp/haarp.conf", @implode("\n", $f));
	
}

function status(){
	
	if(systemMaxOverloaded()){return;}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());

	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($pidTime);
		if($time<60){return;}
	}
	@unlink($pidTime);
	@file_get_contents($pidTime,time());
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM haarp_caches";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=trim($ligne["directory"]);
		$ID=$ligne["ID"];
		@mkdir("$directory",0755,true);
		if(substr($directory, strlen($directory)-1,1)<>"/"){$directory=$directory."/";}
		$dir[$ID]=$directory;
	}
	
	if(count($dir)==0){
		@mkdir("/home/haarp-1",0755,true);
		$dir[1]="/home/haarp-1";
	}

	while (list ($ID, $directory) = each ($dir) ){
		$size=$unix->dskspace_bytes($directory);
		$q->QUERY_SQL("UPDATE haarp_caches SET `size`='$size' WHERE ID=$ID");
		
	}
	
	
}

