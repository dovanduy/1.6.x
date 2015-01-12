<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["NOUPDATE"]=false;
$GLOBALS["FRAMEWORK"]=false;
$GLOBALS["TITLENAME"]="Categories Service";
$GLOBALS["PID_PATH"]="/var/run/urlfilterdb/ufdbguardd.pid";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--framework#",implode(" ",$argv),$re)){$GLOBALS["FRAMEWORK"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--noupdate#",implode(" ",$argv),$re)){$GLOBALS["NOUPDATE"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--rotatelog"){$GLOBALS["OUTPUT"]=true;rotate();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;buildconfig();die();}
if($argv[1]=="--test"){testssocks();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--checkdirs"){CheckDirectories(true);buildconfig();reload();exit;}


function restart() {
	$unix=new unix();
	$FORCED_TEXT=null;
	$NOTIFY=false;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["FORCE"]){
		$FORCED_TEXT=" (forced)";
	
	}
	
	if($GLOBALS["SCHEDULE_ID"]>0){
			$NOTIFY=true;
			squid_admin_mysql(2, "Scheduled task executed: Restart Categories Service$FORCED_TEXT", 
			"This is a schedule task ID:{$GLOBALS["SCHEDULE_ID"]}",__FILE__,__LINE__);
	}
	if($GLOBALS["WATCHDOG"]){$NOTIFY=true;squid_admin_mysql(2, "Restart Categories Service$FORCED_TEXT ( by Watchdog )", "nothing",__FILE__,__LINE__);}
	if($GLOBALS["UFDBTAIL"]){$NOTIFY=true;squid_admin_mysql(2, "Restart Categories Service$FORCED_TEXT ( by Tailer )", "nothing",__FILE__,__LINE__);}
	if($GLOBALS["FRAMEWORK"]){$NOTIFY=true;squid_admin_mysql(2, "Restart Categories Service$FORCED_TEXT ( by Framework )", "nothing",__FILE__,__LINE__);}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if(!$GLOBALS["FORCE"]){
			$PideExec=$unix->file_time_min($pidTime);
			if($PideExec<60){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed, need to wait 60mn or use --force\n";}
				return;
			}
			
		}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running, start it\n";}
		squid_admin_mysql(1, "Categories Service not running [action=start it]", "nothing",__FILE__,__LINE__);
		start(true,false);
		return;
	}
	
	
	if(!$NOTIFY){
		squid_admin_mysql(1, "Restart Categories Service$FORCED_TEXT", "nothing",__FILE__,__LINE__);
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Restarting...\n";}
	build_progress("{stopping_service}",5);
	stop(true);
	sleep(1);
	build_progress("{starting_service}",9);
	start(true,true);
	
}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$sock=new sockets();
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	

	

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$unix->_syslog("{$GLOBALS["TITLENAME"]} Reloading PID $pid\n",basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reloading PID $pid\n";}
		CheckDirectories();
		buildconfig();
		unix_system_HUP($pid);
		
	}else{
		start(true);
	}
	
}

function CheckDirectories($verifdbs=false){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	$cp=$unix->find_program("cp");
	$PossibleDirs[]="/var/lib/ufdbartica";
	
	
	
	@mkdir("/home/ufdbcat",0755,true);
	$cp=$unix->find_program("cp");
	
	
	
	while (list ($index, $Directory) = each ($PossibleDirs) ){
		if(is_link($Directory)){$Directory=readlink($Directory);}
		$dirs=$unix->dirdir($Directory);
		$max=count($dirs);
		$c=0;
		while (list ($index, $Directory2) = each ($dirs) ){
			if(is_link($Directory2)){$Directory2=readlink($Directory2);}
			if(!is_file("$Directory2/domains.ufdb")){continue;}
			$destdir="/home/ufdbcat/".basename($Directory2);
			$c++;
			$prc=($c/$max)*100;
			$prc=round($prc);
			if($prc>9){
				if($prc<95){
					build_progress("{category}:".basename($Directory2),$prc);
				}
			}
			
			
			
			if($verifdbs){
				$FILETIME1=md5_file("$Directory2/domains.ufdb");
				$FILETIME2=null;
				if(is_file("$destdir/domains.ufdb")){$FILETIME2=md5_file("$destdir/domains.ufdb");}
				@mkdir($destdir,0755,true);
				if($FILETIME2==$FILETIME1){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} OK `".basename($Directory2) ." not changed..\n";}continue;}
					continue;
				}	
				
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Duplicate `".basename($Directory2)."` $FILETIME2<>$FILETIME1\n";}
				shell_exec("$cp -rf $Directory2/* $destdir/");
				@chmod($destdir,0755);
				
			}
	}
	
}


function start($aspid=false,$verifdbs=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/ufdbcat/bin/ufdbcatdd";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ufdbguardd not installed\n";}
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if($GLOBALS["MONIT"]){@file_put_contents($GLOBALS["PID_PATH"],$pid);}
		return;
	}
	
	$DisableUfdbCat=$sock->DisableUfdbCat();
	
	

	if($DisableUfdbCat==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see DisableUfdbCat)\n";}
		stop();
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	$cp=$unix->find_program("cp");

	
	@mkdir("/etc/ufdbcat",0755,true);
	@mkdir("/var/log/ufdbcat",0755,true);
	@mkdir(dirname($GLOBALS["PID_PATH"]),0755,true);
	@chmod($GLOBALS["PID_PATH"],0755);
	@chmod($Masterbin,0755);
	build_progress("{starting_service}",10);
	CheckDirectories($verifdbs);
	
	if(is_file("/var/log/ufdbcat/ufdbguardd.log")){@unlink("/var/log/ufdbcat/ufdbguardd.log");}
	if(is_file("/var/run/ufdbcat-03977")){@unlink("/var/run/ufdbcat-03977");}
	if($unix->is_socket("/var/run/ufdbcat-03977")){@unlink("/var/run/ufdbcat-03977");}
	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));

	$Threads=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbCatThreads"));
	if($Threads==0){$Threads=4;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pid path: {$GLOBALS["PID_PATH"]}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Threads:$Threads\n";}
	
	if(is_file("/opt/ufdbcat/bin/ufdbhttpd")){@unlink("/opt/ufdbcat/bin/ufdbhttpd");}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ** Categories Appliance Mode ***\n";}
	
	$isRemoteSockets=isRemoteSockets();
	
	if($isRemoteSockets){
		if(!is_file("$Masterbin.sock")){
			@copy($Masterbin, "$Masterbin.sock");
		}
		
		$ufdbguardd=$unix->find_program("ufdbguardd");
		if(!is_file($ufdbguardd)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Fatal ufdbguardd no such binary!!!\n";}
			return false;
		}
		@unlink($Masterbin);
		@copy($ufdbguardd,$Masterbin);
	}
	
	if(!$isRemoteSockets){
		if(is_file("$Masterbin.sock")){
			@copy("$Masterbin.sock",$Masterbin);
			@unlink("$Masterbin.sock");
		}
	}
	
	
	@unlink($GLOBALS["PID_PATH"]);
	@chmod($Masterbin,0755);
	$cmd="$Masterbin -c /etc/ufdbcat/ufdbGuard.conf -U root -w $Threads -N >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build_progress("{starting_service}",96);
	squid_admin_mysql(2, "Starting Categories Service", "nothing",__FILE__,__LINE__);
	buildconfig();
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		build_progress("{starting_service} $i/5",98);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
		if(!$isRemoteSockets){
			for($i=1;$i<10;$i++){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Checking socket $i/5...\n";}
				sleep(1);
				if($unix->is_socket("/var/run/ufdbcat-03977")){
				 	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Checking socket success...\n";}
					 @chmod("/var/run/ufdbcat-03977",0777);
				 	break;
				}
			}
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success\n";}
		build_progress("{success}",100);
		return true;
	}
	squid_admin_mysql(0, "Failed to start Categories Service", "nothing",__FILE__,__LINE__);
	build_progress("{failed}",110);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	


}

function stop($aspid=false){
	if($GLOBALS["MONIT"]){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} runned by Monit, abort\n";}
		return;}
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Artica script already running PID $pid since {$time}mn\n";}
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
	build_progress("{stopping_service}",6);
	squid_admin_mysql(1, "Stopping Categories Service", "nothing",__FILE__,__LINE__);
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
		build_progress("{stopping_service} $i/5",7);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
	build_progress("{stopped}",8);
}


function isRemoteSockets(){
	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	$EnableLocalUfdbCatService=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableLocalUfdbCatService"));
	if($AsCategoriesAppliance==1){return true;}
	
	if($EnableLocalUfdbCatService==1){
		$ufdbCatInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface");
		if($ufdbCatInterface<>null){return true;}
	}
	return false;
	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/ufdbcat/ufdbguardd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF_PATTERN("ufdbcatdd.*?-c.*?conf");
	if($unix->process_exists($pid)){return $pid;}
}

function buildconfig(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$dirs=$unix->dirdir("/home/ufdbcat");
	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	
	$array["category_governments"]="governments";
	$array["category_industry"]="industry";
	$array["category_luxury"]="luxury";
	$array["category_shopping"]="shopping";
	$array["category_socialnet"]="socialnet";
	$array["category_searchengines"]="searchengines";
	$array["category_news"]="news";
	$array["category_blog"]="blog";
	$array["category_remote_control"]="remote-control";
	
	
	$array["category_audio_video"]="audio-video";
	$array["category_webtv"]="webtv";
	$array["category_movies"]="movies";
	$array["category_music"]="music";
	
	$array["category_animals"]="animals";
	$array["category_children"]="children";
	
	
	
	
	$array["category_cosmetics"]="cosmetics";
	$array["category_clothing"]="clothing";
	$array["category_electricalapps"]="electricalapps";
	$array["category_electronichouse"]="electronichouse";
	
	
	$array["category_associations"]="associations";
	$array["category_astrology"]="astrology";
	
	$array["category_bicycle"]="bicycle";
	$array["category_automobile_bikes"]="automobile/bikes";
	$array["category_automobile_boats"]="automobile/boats";
	$array["category_automobile_carpool"]="automobile/carpool";
	$array["category_automobile_planes"]="automobile/planes";
	$array["category_automobile_cars"]="automobile/cars";
	
	
	$array["category_cleaning"]="cleaning";
	$array["category_converters"]="converters";
	
	$array["category_finance_realestate"]="finance/realestate";
	$array["category_finance_banking"]="finance/banking";
	$array["category_finance_insurance"]="finance/insurance";
	$array["category_finance_moneylending"]="finance/moneylending";
	$array["category_stockexchange"]="stockexchange";
	$array["category_finance_other"]="finance/other";
	$array["category_financial"]="financial";
	
	
	$array["category_forums"]="forums";
	$array["category_games"]="games";
	$array["category_gamble"]="gamble";
	
	$array["category_getmarried"]="getmarried";
	$array["category_gifts"]="gifts";
	$array["category_green"]="green";
	
	$array["category_handicap"]="handicap";
	$array["category_humanitarian"]="humanitarian";
	$array["category_hospitals"]="hospitals";
	$array["category_medical"]="medical";
	$array["category_health"]="health";
	
	
	
	
	$array["category_hobby_cooking"]="hobby/cooking";
	$array["category_hobby_fishing"]="hobby/fishing";
	$array["category_hobby_other"]="hobby/other";
	$array["category_hobby_pets"]="hobby/pets";
	$array["category_horses"]="horses";
	
	
	$array["category_housing_accessories"]="housing/accessories";
	$array["category_housing_builders"]="housing/builders";
	$array["category_housing_doityourself"]="housing/doityourself";
	
	$array["category_jobsearch"]="jobsearch";
	$array["category_jobtraining"]="jobtraining";
	$array["category_justice"]="justice";
	$array["category_learning"]="learning";
	
	$array["category_manga"]="manga";
	$array["category_maps"]="maps";
	
	$array["category_mobile_phone"]="mobile-phone";
	$array["category_nature"]="nature";
	$array["category_passwords"]="passwords";
	$array["category_police"]="police";
	$array["category_politic"]="politic";
	
	
	$array["category_recreation_humor"]="recreation/humor";
	$array["category_recreation_schools"]="recreation/schools";
	$array["category_recreation_sports"]="recreation/sports";
	$array["category_recreation_travel"]="recreation/travel";
	$array["category_recreation_nightout"]="recreation/nightout";
	$array["category_recreation_wellness"]="recreation/wellness";
	$array["category_models"]="models";
	$array["category_celebrity"]="celebrity";
	$array["category_womanbrand"]="womanbrand";
	
	$array["category_science_astronomy"]="science/astronomy";
	$array["category_science_chemistry"]="science/chemistry";
	$array["category_science_computing"]="science/computing";
	$array["category_science_weather"]="science/weather";
	$array["category_culture"]="culture";
	$array["category_sciences"]="sciences";
	$array["category_literature"]="literature";
	
	
	
	$array["category_smallads"]="smallads";
	$array["category_houseads"]="houseads";
	
	$array["category_tattooing"]="tattooing";
	$array["category_teens"]="teens";
	$array["category_terrorism"]="terrorism";
	
	
	$array["category_translators"]="translators";
	$array["category_transport"]="transport";
	$array["category_tricheur"]="tricheur";
	$array["category_updatesites"]="updatesites";
	
	$array["category_webmail"]="webmail";
	$array["category_chat"]="chat";
	$array["category_meetings"]="meetings";
	$array["category_webapps"]="webapps";
	$array["category_webplugins"]="webplugins";
	$array["category_browsersplugins"]="browsersplugins";
	$array["category_webphone"]="webphone";
	
	
	
	$array["category_wine"]="wine";
	$array["category_tobacco"]="tobacco";
	$array["category_alcohol"]="alcohol";
	$array["category_drugs"]="drugs";
	
	$array["category_books"]="books";
	$array["category_dictionaries"]="dictionaries";
	$array["category_photo"]="photo";
	$array["category_pictureslib"]="pictureslib";
	$array["category_imagehosting"]="imagehosting";
	
	$array["category_downloads"]="downloads";
	$array["category_filehosting"]="filehosting";

	$array["category_society"]="society";
	$array["category_hobby_arts"]="hobby/arts";
	$array["category_webradio"]="webradio";
	
	
	$array["category_genealogy"]="genealogy";
	$array["category_paytosurf"]="paytosurf";
	$array["category_religion"]="religion";
	$array["category_abortion"]="abortion";
	$array["category_sect"]="sect";
	$array["category_suspicious"]="suspicious";
	$array["category_warez"]="warez";
	$array["category_hacking"]="hacking";
	$array["category_proxy"]="proxy";
	$array["category_porn"]="porn";
	$array["category_dating"]="dating";
	$array["category_mixed_adult"]="mixed_adult";
	$array["category_sex_lingerie"]="sex/lingerie";
	$array["category_sexual_education"]="sexual_education";
	$array["category_marketingware"]="marketingware";
	$array["category_publicite"]="publicite";
	$array["category_tracker"]="tracker";
	$array["category_mailing"]="mailing";
	$array["category_redirector"]="redirector";

	$array["category_violence"]="violence";
	$array["category_spyware"]="spyware";
	$array["category_malware"]="malware";
	$array["category_phishing"]="phishing";
	$array["category_dangerous_material"]="dangerous_material";
	$array["category_weapons"]="weapons";
	$array["category_internal"]="internal";
	$array["category_dynamic"]="dynamic";
	$array["category_isp"]="isp";
	$array["category_sslsites"]="sslsites";
	$array["category_reaffected"]="reaffected";
	
	
	while (list ($dirname, $realcat) = each ($array) ){
		
		if(is_file("/home/ufdbcat/$dirname/domains.ufdb")){
			if(!is_file("/home/ufdbcat/$dirname/expressions")){@touch("/home/ufdbcat/$dirname/expressions");}
			$cats[]=$dirname;
			
			$catz[]="category \"$dirname\" {";
			$catz[]="\tdomainlist      \"$dirname/domains\"";
			$catz[]="\texpressionlist  \"$dirname/expressions\"";
			$catz[]="\tredirect        \"http://none/$realcat\"";
			$catz[]="}";
			
		}
	}
	
	
	$f[]="dbhome \"/home/ufdbcat\"";
	$f[]="logdir \"/var/log/ufdbcat\"";
	$f[]="logblock off";
	$f[]="logpass off";
	$f[]="logall off";
	$f[]="squid-version \"3.3\"";
	$f[]="url-lookup-result-during-database-reload deny";
	$f[]="url-lookup-result-when-fatal-error deny";
	$f[]="analyse-uncategorised-urls off";
	$f[]="enforce-https-with-hostname off";
	$f[]="enforce-https-offical-certificate off";
	$f[]="https-prohibit-insecure-sslv2 off";
	
	$EnableLocalUfdbCatService=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableLocalUfdbCatService"));
	if($AsCategoriesAppliance==1){$EnableLocalUfdbCatService=1;}
	
	if($EnableLocalUfdbCatService==1){
		$ufdbCatInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface");
		
		if($ufdbCatInterface<>null){
			if(!$unix->is_interface_available($ufdbCatInterface)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $ufdbCatInterface not available\n";}
			}else{
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $ufdbCatInterface is available\n";}
			}
		}
		
		$ufdbCatPort=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatPort"));
		if($ufdbCatPort==0){$ufdbCatPort=3978;}
		if($ufdbCatInterface==null){$ufdbCatInterface="all";}
		$f[]="port $ufdbCatPort";
		$f[]="interface $ufdbCatInterface";
	}
	
	$f[]="check-proxy-tunnels off";
	$f[]="safe-search off";
	$f[]="youtube-edufilter    off";
	$f[]="max-logfile-size  200000000";
	$f[]="# refreshuserlist 15";
	$f[]="# refreshdomainlist 15";
	$f[]="source allSystems {";
	$f[]="   ip  0.0.0.0/0  ";
	$f[]="}";
	
	$categories=@implode(" !", $cats);
	
	
	if(!is_file("/home/ufdbcat/security/cacerts")){
		@mkdir("/home/ufdbcat/security");
		@touch("/home/ufdbcat/security/cacerts");
	}
	$f[]="category security {";
	$f[]="\tcacerts \"security/cacerts\"";
	$f[]="\toption  enforce-https-with-hostname off";
	$f[]="\toption  enforce-https-official-certificate off";
	$f[]="\toption  https-prohibit-insecure-sslv2 off";
	$f[]="\toption 	allow-aim-over-https off";
	$f[]="\toption 	allow-gtalk-over-https off";
	$f[]="\toption 	allow-skype-over-https off";
	$f[]="\toption 	allow-yahoomsg-over-https off";
	$f[]="\toption 	allow-fb-chat-over-https off";
	$f[]="\toption 	allow-citrixonline-over-https off";
	$f[]="\toption 	allow-unknown-protocol-over-https off";
	$f[]="}";
	
	$f[]=@implode("\n", $catz);
	$f[]="";
	$f[]="";
	$f[]="acl {";
	$f[]="\tallSystems  {";
	$f[]="\t\tpass !$categories any";
	$f[]="\t}";
	$f[]="";
	$f[]="\t\tdefault {";
	$f[]="\tpass !$categories any";
	$f[]="\tredirect        \"http://cgibin.urlfilterdb.com/cgi-bin/URLblocked.cgi?admin=%A&color=orange&size=normal&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&category=%t&url=%u\"";
	$f[]="\t}";
	$f[]="}";	
	@file_put_contents("/etc/ufdbcat/ufdbGuard.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/ufdbcat/ufdbGuard.conf done\n";}
}

function install(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeEx=$unix->file_time_min($pidTime);
	if($pidTimeEx<60){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$Masterbin="/opt/ufdbcat/bin/ufdbcatdd";
	
	$DebianVersion=_DebianVersion();
	$Arch=_Architecture();
	if($Arch==32){return;}
	$filename="ufdbcat-debian{$DebianVersion}-{$Arch}-1.31.tar.gz";
	$url="http://articatech.net/download/Debian7-squid/$filename";
	$curl=new ccurl($url);
	
	$tmpfile=$unix->TEMP_DIR()."/$filename";
	if(!$curl->GetFile($tmpfile)){
		squid_admin_mysql(0, "Unable to download $filename", @implode("\n", $curl->errors),__FILE__,__LINE__);
		return;
	}
	
	$tar=$unix->find_program("tar");
	shell_exec("$tar xf $tmpfile -C /");
	if(is_file($Masterbin)){
		squid_admin_mysql(0, "Success installing Artica Categorize Daemon", null,__FILE__,__LINE__);
		return;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --ufdbcat");
	if(!$GLOBALS["NOUPDATE"]){
		shell_exec("$php /usr/share/artica-postfix/exec.squid.blacklists.php --ufdb --force --".__FUNCTION__."-".__LINE__." >/dev/null 2>&1 &");
	}
	
	
}
function _DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}
function _Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}

function testssocks(){
	$GLOBALS["VERBOSE"]=true;
	include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');
	$catz=new mysql_catz();
	echo "google.fr -> ". $catz->GetMemoryCache("google.fr",true)."\n";
	echo "google.com -> ". $catz->GetMemoryCache("google.com",true)."\n";
	
}


?>