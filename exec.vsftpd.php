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
	if(is_file("/var/log/vsftpd.log")){@touch("/var/log/vsftpd.log");}
	
	
	
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
	
	$f[]="# Standard behaviour for ftpd(8).";
	$f[]="auth required       pam_listfile.so item=user sense=deny file=/etc/ftpusers onerr=succeed";
	$f[]="";
	$f[]="# Note: vsftpd handles anonymous logins on its own. Do not enable";
	$f[]="# pam_ftp.so.";
	$f[]="";
	$f[]="# Standard blurb.";
	$f[]="#@include common-account";
	$f[]="#@include common-session";
	$f[]="#@include common-auth";
	$f[]="";
	$f[]="account required   pam_unix.so";
	$f[]="account sufficient pam_ldap.so";
	$f[]="";
	$f[]="session required   pam_limits.so";
	$f[]="session required   pam_unix.so";
	$f[]="session optional   pam_ldap.so";
	$f[]="";
	$f[]="auth required      pam_env.so";
	$f[]="auth sufficient    pam_unix.so nullok_secure";
	$f[]="auth sufficient    pam_ldap.so use_first_pass";
	$f[]="";
	$f[]="auth required       pam_shells.so";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/pam.d/vsftpd done\n";}
	@file_put_contents("/etc/pam.d/vsftpd", @implode("\n", $f));
	
	
}

function vsftpd_conf(){
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$VSFTPDPort=intval($sock->GET_INFO("VSFTPDPort"));
	if($VSFTPDPort==0){$VSFTPDPort=21;}
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
	$f[]="listen_port=21";
	$f[]="";
	$f[]="# Pour emprisonner le démon vsftpd.";
	$f[]="secure_chroot_dir=/var/run/vsftpd";
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
	$f[]="pasv_enable=YES";
	$f[]="";
	$f[]="# Définition de la plage de ports à utiliser pour les connexions FTP";
	$f[]="# passives.";
	$f[]="pasv_min_port=40000";
	$f[]="pasv_max_port=40200";
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
	$f[]="";
	$f[]="# Nom du service PAM à utiliser pour l'authentification.";
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
	$f[]="";
	$f[]="# Vous pouvez spécifier une liste d'utilisateurs à chrooter si vous";
	$f[]="# n'activez pas le paramètre 'chroot_local_user'.";
	$f[]="# Par contre, si vous l'activez, cette liste contiendra les utilisateurs";
	$f[]="# à ne pas chrooter.";
	$f[]="#chroot_list_enable=YES";
	$f[]="#chroot_list_file=/etc/vsftpd.chroot_list";
	$f[]="";
	$f[]="# Autorise les opérations d'écriture.";
	$f[]="write_enable=YES";
	$f[]="";
	$f[]="# Je considère que les utilisateurs locaux ont un accès FTP pour gérer";
	$f[]="# les données de leur site Web. De ce fait, j'applique un masque sur les";
	$f[]="# données pour que Apache puisse les lire et écrire. Je refuse";
	$f[]="# l'utilisation de la commande FTP 'chmod'.";
	$f[]="#";
	$f[]="local_umask=007";
	$f[]="#";
	$f[]="";
	$f[]="# Désactive la commande FTP 'chmod'.";
	$f[]="chmod_enable=NO";
	$f[]="";
	$f[]="# Pour afficher 'ftp' comme propriétaire et groupe.";
	$f[]="hide_ids=YES";
	$f[]="";
	$f[]="# Pour limiter le taux de transfert (montant/descendant) des utilisateurs";
	$f[]="# locaux en Octets par seconde.";
	$f[]="local_max_rate=1100";
	$f[]="";
	$f[]="# Active les logs pour les transferts montant/descendant.";
	$f[]="xferlog_enable=YES";
	$f[]="";
	$f[]="# Pour obtenir les logs FTP au format standard xferlog.";
	$f[]="xferlog_std_format=YES";
	$f[]="";
	$f[]="# Fichier de log par défaut.";
	$f[]="xferlog_file=/var/log/vsftpd.log";
	$f[]="";
	$f[]="# Timeout d'une session.";
	$f[]="idle_session_timeout=600";
	$f[]="";
	$f[]="# Timeout pour l'échange de données.";
	$f[]="data_connection_timeout=120";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/vsftpd.conf done\n";}
	@file_put_contents("/etc/vsftpd.conf", @implode("\n", $f));
}