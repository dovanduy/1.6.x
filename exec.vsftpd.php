<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;pamd_conf();vsftpd_conf();}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	start(true);
	
}

function PID_NUM(){

	$unix=new unix();
	$Masterbin=$unix->find_program("vsftpd");
	$pid=$unix->PIDOF_PATTERN("^vsftpd$");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN($Masterbin);

}

function vsftpd_version(){
	return "2.3.5";
	$unix=new unix();
	
	$Masterbin=$unix->find_program("vsftpd");
	$line=shell_exec("$Masterbin -v 2>&1");
	if(preg_match("#vsftpd: version\s+([0-9\.]+)#", $line,$re)){return $re[1];}
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("vsftpd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	


	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		$vsftpd_version=vsftpd_version();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} version $vsftpd_version already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableDaemon=intval($sock->GET_INFO("EnableVSFTPDDaemon"));
	


	if($EnableDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableVSFTPDDaemon)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	pamd_conf();
	vsftpd_conf();
	$cmd="$nohup $Masterbin -olisten=YES /etc/vsftpd.conf >/dev/null 2>&1 &";

	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}



function pamd_conf(){
	
	$f[]="# Note: vsftpd handles anonymous logins on its own. Do not enable";
	$f[]="# pam_ftp.so.";
	$f[]="";

	$f[]="auth 		required 		pam_ldap.so";
	$f[]="account 	required 		pam_ldap.so";
	$f[]="password 	required 		pam_ldap.so";
	
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/pam.d/vsftpd done\n";}
	@file_put_contents("/etc/pam.d/vsftpd", @implode("\n", $f));
	
	
}

function vsftpd_conf(){
	@unlink("/var/log/exim4/paniclog");
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$VSFTPDPort=intval($sock->GET_INFO("VSFTPDPort"));
	$VsFTPDPassive=$sock->GET_INFO("VsFTPDPassive");
	$VsFTPDPassiveAddr=$sock->GET_INFO("VsFTPDPassiveAddr");
	if($VSFTPDPort==0){$VSFTPDPort=21;}
	if(!is_numeric($VsFTPDPassive)){$VsFTPDPassive=1;}
	$VsFTPDFileOpenMode=$sock->GET_INFO("VsFTPDFileOpenMode");
	$VsFTPDLocalUmask=$sock->GET_INFO("VsFTPDLocalUmask");
	if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
	if($VsFTPDLocalUmask==null){$VsFTPDLocalUmask="077";}
	$VsFTPDLocalMaxRate=intval($sock->GET_INFO("VsFTPDLocalMaxRate"));
	
	@mkdir("/var/empty");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen on $VSFTPDPort\n";}
	
	$f[]="#";
	$f[]="# The default compiled in settings are fairly paranoid. This sample file";
	$f[]="# loosens things up a bit, to make the ftp daemon more usable.";
	$f[]="# Please see vsftpd.conf.5 for all compiled in defaults.";
	$f[]="#";
	$f[]="";
	$f[]="# Pour que vsFTPd soit lancé en tant que démon (IPv4).";
	$f[]="listen=YES";
	$f[]="";
	$f[]="# Ou en Ipv6.";
	$f[]="#listen_ipv6=YES";
	$f[]="";
	$f[]="# Adresse d'écoute, sinon toutes les interfaces sont écoutées.";
	$f[]="#listen_address=123.45.67.6";
	$f[]="";
	$f[]="# Port d'écoute.";
	$f[]="listen_port=$VSFTPDPort";
	$f[]="";

	
	$f[]="";
	$f[]="# Utilisateur pour les opérations sans privilèges.";
	$f[]="nopriv_user=nobody";
	$f[]="";
	$f[]="# Pour s'assurer que les données FTP (ftp-data) partent du port 20.";
	$f[]="connect_from_port_20=YES";
	$f[]="";
	$f[]="# Ne pas activer cette option pour des raisons de sécurité.";
	$f[]="#async_abor_enable=YES";
	$f[]="";
	$f[]="# Ne pas activer ces options pour des raisons de sécurité.";
	$f[]="#ascii_upload_enable=YES";
	$f[]="#ascii_download_enable=YES";
	$f[]="";
	$f[]="# Active le mode FTP passif.";
	if($VsFTPDPassive==1){
		$pasv_min_port=intval($sock->GET_INFO("VsFTPDPassiveMinPort"));
		$pasv_max_port=intval($sock->GET_INFO("VsFTPDPassiveMaxPort"));
		if($pasv_min_port==0){$pasv_min_port=40000;}
		if($pasv_max_port==0){$pasv_max_port=40200;}
		$f[]="pasv_enable=YES";
		$f[]="pasv_min_port=$pasv_min_port";
		$f[]="pasv_max_port=$pasv_max_port";
		if($VsFTPDPassiveAddr<>null){$f[]="pasv_address=$VsFTPDPassiveAddr"; }
	}else{
		$f[]="pasv_enable=NO";
	}
	$f[]="";
	$f[]="# Combien de clients peuvent être connectés au maximum.";
	$f[]="max_clients=200";
	$f[]="";
	$f[]="# Le nombre maximum de clients connectés depuis la même adresse IP source.";
	$f[]="max_per_ip=4";
	$f[]="";
	$f[]="# Désactive le listage récursif des répertoires par la commande 'ls -R's,";
	$f[]="# afin d'éviter trop d'appels sur le système de fichier.";
	$f[]="# Certain clients FTP comme 'ncftp' ou 'mirror' réclame l'option '-R'";
	$f[]="# pour fonctionner.";
	$f[]="ls_recurse_enable=YES";
	$f[]="";
	$f[]="# Force l'affichage des données cachées, commençant par un '.'";
	$f[]="force_dot_files=YES";
	$f[]="";
	$f[]="# Commandes autorisées. Voir la liste des commandes.";
	$f[]="#cmds_allowed=PASV,RETR,QUIT";
	$f[]="";
	$f[]="# Données refusées.";
	$f[]="#deny_file={*.mp3,*.mov,.private}";
	$f[]="";
	$f[]="# Données qui seront cachées.";
	$f[]="#hide_file={*.mp3,.hidden,hide*,h?}";
	$f[]="hide_file={Maildir,.spamassassin}";
	$f[]="";
	$f[]="# Bannière affichée au login des clients.";
	$f[]="ftpd_banner=Welcome $hostname FTP service.";
	$f[]="";
	$f[]="# Supprime l'affichage de message pour certain répertoire.";
	$f[]="dirmessage_enable=NO";
	$f[]="";
	$f[]="# Autorise les connexions FTP anonymes.";
	$f[]="anonymous_enable=NO";
	$f[]="";
	$f[]="# Refuse les connexions SSL pour les clients anonymes.";
	$f[]="allow_anon_ssl=NO";
	$f[]="";
	$f[]="# Ne demande pas de mot de passe aux clients anonymes.";
	$f[]="no_anon_password=YES";
	$f[]="";
	$f[]="# Vous pouvez lister les adresses mail à refuser pour les clients";
	$f[]="# anonymes. Utile pour combattre certaines attaques DoS.";
	$f[]="#deny_email_enable=YES";
	$f[]="#banned_email_file=/etc/vsftpd.banned_emails";
	$f[]="";
	$f[]="# Indique dans quel répertoire seront dirigés les clients anonymes.";
	$f[]="anon_root=/home/ftp";
	$f[]="";
	$f[]="# Tous les paramètres commençant par 'anon_ ', concernent les connexions";
	$f[]="# anonymes. Si vous souhaitez autoriser l'upload et d'autres opérations";
	$f[]="# d'écriture, vous devez activer l'option write_enable.";
	$f[]="#";
	$f[]="# Refuser l'upload.";
	$f[]="anon_upload_enable=NO";
	$f[]="";
	$f[]="# Refuse la création de répertoire.";
	$f[]="anon_mkdir_write_enable=NO";
	$f[]="";
	$f[]="# Refuse les opérations d'écriture.";
	$f[]="anon_other_write_enable=NO";
	$f[]="";
	$f[]="# Pour que les clients anonymes voient uniquement les données";
	$f[]="# lisibles par tout le monde.";
	$f[]="anon_world_readable_only=YES";
	$f[]="";
	$f[]="# Pour limiter le taux de transfert (montant/descendant) des clients";
	$f[]="# anonymes en Octets par seconde.";
	$f[]="anon_max_rate=260";
	$f[]="";
	$f[]="# Autorise les utilisateurs 'locaux' à se connecter (authentifiés via PAM)";
	$f[]="local_enable=YES";
	$f[]="session_support=YES";
	$f[]="pam_service_name=vsftpd";
	$f[]="";
	$f[]="# Active le module SSL.";
	$f[]="ssl_enable=NO";
	$f[]="";
	$f[]="# Emplacement du certificat RSA à utiliser pour les connections SSL.";
	$f[]="rsa_cert_file=/etc/vsftpd-ssl/vsftpd.pem";
	$f[]="";
	$f[]="# Autorise les protocoles suivants :";
	$f[]="ssl_tlsv1=YES";
	$f[]="ssl_sslv3=YES";
	$f[]="";
	$f[]="# Refuse le protocole suivant :";
	$f[]="ssl_sslv2=NO";
	$f[]="";
	$f[]="# Force les transactions d'authentification non anonymes via SSL.";
	$f[]="force_local_logins_ssl=YES";
	$f[]="";
	$f[]="# Force le transfert des données via SSL.";
	$f[]="force_local_data_ssl=YES";
	$f[]="";
	$f[]="# Pour refuser certain utilisateurs d'après une liste contenue dans un fichier.";
	$f[]="#userlist_enable=YES";
	$f[]="#userlist_deny=YES";
	$f[]="#userlist_file=/etc/vsftpd.user_list";
	$f[]="";
	$f[]="# Pour restreindre les utilisateurs locaux dans leur home directories.";
	$f[]="chroot_local_user=YES";
	$f[]="secure_chroot_dir=/var/empty";
	$f[]="allow_writeable_chroot=YES";
	$f[]="passwd_chroot_enable=YES";
	$f[]="chown_uploads=YES";
	$f[]="hide_ids=NO";
	$f[]="local_umask=$VsFTPDLocalUmask";
	$f[]="ftp_username=".$unix->APACHE_SRC_ACCOUNT();
	$f[]="nopriv_user=".$unix->APACHE_SRC_ACCOUNT();
	$f[]="";
	$f[]="# Vous pouvez spécifier une liste d'utilisateurs à chrooter si vous";
	$f[]="# n'activez pas le paramètre 'chroot_local_user'.";
	$f[]="# Par contre, si vous l'activez, cette liste contiendra les utilisateurs";
	$f[]="# à ne pas chrooter.";
	$f[]="#chroot_list_enable=YES";
	$f[]="#chroot_list_file=/etc/vsftpd.chroot_list";
	$f[]="write_enable=YES";
	$f[]="chmod_enable=NO";
	$f[]="";
	$f[]="# Pour limiter le taux de transfert (montant/descendant) des utilisateurs";
	$f[]="# locaux en Octets par seconde.";
	if($VsFTPDLocalMaxRate>0){
		$f[]="local_max_rate=".$VsFTPDLocalMaxRate*1000;
	}else{
		$f[]="local_max_rate=0";
	}
	$f[]="";
	$f[]="# Active les logs pour les transferts montant/descendant.";
	$f[]="xferlog_enable=YES";
	$f[]="log_ftp_protocol=YES";
	$f[]="#xferlog_std_format=YES";
	$f[]="xferlog_file=/var/log/vsftpd.log";
	$f[]="syslog_enable=YES";
	$f[]="use_localtime=YES";
	$f[]="";
	$f[]="# Timeout d'une session.";
	$f[]="idle_session_timeout=600";
	$f[]="data_connection_timeout=120";
	$f[]="";
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/vsftpd.conf done\n";}
	@file_put_contents("/etc/vsftpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} set nsswitch..\n";}
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --nsswitch >/dev/null 2>&1");
	
}