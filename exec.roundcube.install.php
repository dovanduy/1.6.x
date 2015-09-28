<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.roundcube.inc');
include_once(dirname(__FILE__) . '/ressources/class.apache.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$bd="roundcubemail";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MYSQL_DB"]=$bd;	
$GLOBALS["SERVICE_NAME"]="RoundCube Web";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
xstart();

function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/roundcube.install.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}


}

function xstart(){
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	build_progress("{downloading} roundcubeemail-1.1.2.tar.gz",20);
	$tmpfile=$unix->FILE_TEMP();
	$curl=new ccurl("http://articatech.net/download/postfix-debian7/roundcubeemail-1.1.2.tar.gz");
	if(!$curl->GetFile($tmpfile)){
		echo "Failed: ".$curl->error."\n";
		@unlink($tmpfile);
		build_progress("{failed} roundcubeemail-1.1.2.tar.gz",110);
		return;
	}
	build_progress("{uncompressing} roundcubeemail-1.1.2.tar.gz",50);
	
	system("$tar xf $tmpfile -C /");
	@unlink($tmpfile);
	if(!is_file("/usr/share/roundcube/index.php")){
		build_progress("{uncompressing} roundcubeemail-1.1.2.tar.gz {failed}",110);
		return;
	}
	build_progress("{verify_database}",60);
	system("$php /usr/share/artica-postfix/exec.roundcube.php --database");
	
	build_progress("{restarting_service}",70);
	system("$php /usr/share/artica-postfix/exec.roundcube.php --restart");
	system("/etc/init.d/artica-status restart");
	
	build_progress("{installing} roundcubeemail-1.1.2.tar.gz {success}",100);
	
	
}
