<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["CHECK"]=false;
$GLOBALS["SERVICE_NAME"]="Squid-Cache Stream Backend";
$GLOBALS["SERVICE_NAME2"]="Videocache Scheduler";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--check#",implode(" ",$argv),$re)){$GLOBALS["CHECK"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();reload(true);die();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_video_cache(true);die();}
if($argv[1]=="--reinstall"){$GLOBALS["OUTPUT"]=true;reinstall_video_cache(true);die();}
if($argv[1]=="--parse"){$GLOBALS["OUTPUT"]=true;ParseScheduler();die();}
if($argv[1]=="--backend-ip"){$GLOBALS["VERBOSE"]=true;BackendIP();die();}


if($argv[1]=="--vc-scheduler-start"){$GLOBALS["OUTPUT"]=true;start_vc_scheduler();die();}
if($argv[1]=="--vc-scheduler-stop"){$GLOBALS["OUTPUT"]=true;stop_vc_scheduler();die();}
if($argv[1]=="--vc-scheduler-restart"){$GLOBALS["OUTPUT"]=true;restart_vc_scheduler();die();}
if($argv[1]=="--vc-scheduler-reload"){$GLOBALS["OUTPUT"]=true;restart_vc_scheduler();die();}

if($argv[1]=="--status"){get_status();exit;}



function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	if($GLOBALS["CHECK"]){check_dirs();}
	build();
	if(!install_video_cache()){
		if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Unable to install\n";}
		return;
	
	}
	start(true);
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reloading Apache\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --reload");
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reloading Main Proxy\n";}
	shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
}

function reload($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$sock=new sockets();
	$EnableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
	if($EnableStreamCache==0){
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableStreamCache )...\n";}
		return;
	}
	
	
	
	$masterbin=$unix->find_program("streamsquidcache");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$pid=streamsquidcache_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$php=$unix->LOCATE_PHP5_BIN();
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Service running since {$time}Mn...\n";}
		shell_exec("$masterbin -f /etc/streamsquidcache/squid.conf -k reconfigure");
		reload_vc_scheduler();
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reloading Apache\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --reload");
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reloading Main Proxy\n";}
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		return;
	}
	start(true);
}

function NETWORK_ALL_INTERFACES(){
	if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
	$unix=new unix();
	$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
	unset($GLOBALS["NETWORK_ALL_INTERFACES"]["127.0.0.1"]);
}

function BackendIP(){
	$squid=new squidbee();
	echo "\n\nResult: ".$squid->VerifStreamProxyBindIP()."\n\n";
}


function build(){
	$sock=new sockets();
	$emailprefix=null;
	$unix=new unix();
	$ini=new Bs_IniHandler();
	$IPADDRSSL=array();
	$IPADDRSSL2=array();
	$users=new usersMenus();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} no UUID !!, return\n";}
		return;
	}
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
	if($visible_hostname==null){$visible_hostname=$unix->hostname_g();}
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
	if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}
	$ini->loadString($ArticaSquidParameters);
	
	NETWORK_ALL_INTERFACES();
	$LISTEN_PORT=intval($ini->_params["NETWORK"]["LISTEN_PORT"]);
	$ICP_PORT=intval(trim($ini->_params["NETWORK"]["ICP_PORT"]));
	$certificate_center=$ini->_params["NETWORK"]["certificate_center"];
	$SSL_BUMP=intval($ini->_params["NETWORK"]["SSL_BUMP"]);
	$ssl=false;
	if($ICP_PORT==0){$ICP_PORT=3130;}
	if($LISTEN_PORT==0){$LISTEN_PORT=3128;}
	$squid=new squidbee();
	$q=new mysql_squid_builder();
	$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));

	
	$chown=$unix->find_program("chown");
	$python=$unix->find_program("python");
	$StreamCachePort=intval($sock->GET_INFO("StreamCachePort"));
	$StreamCacheSize=intval($sock->GET_INFO("StreamCacheSize"));
	$StreamCacheSSLPort=intval($sock->GET_INFO("StreamCacheSSLPort"));
	$StreamCacheICPPort=intval($sock->GET_INFO("StreamCacheICPPort"));
	$StreamCacheLocalPort=intval($sock->GET_INFO("StreamCacheLocalPort"));
	$StreamCacheUrlRewiteNumber=intval($sock->GET_INFO("StreamCacheUrlRewiteNumber"));
	if($StreamCacheSize==0){$StreamCacheSize=1500;}
	if($StreamCachePort==0){$StreamCachePort=5559;}
	if($StreamCacheLocalPort==0){$StreamCacheLocalPort=5563;}
	if($StreamCacheSSLPort==0){$StreamCacheSSLPort=5560;}
	if($StreamCacheICPPort==0){$StreamCacheICPPort=5562;}
	if($StreamCacheUrlRewiteNumber==0){$StreamCacheUrlRewiteNumber=15;}
	$StreamCacheBindProxy=$squid->VerifStreamProxyBindIP();
	$StreamCacheOutProxy=$sock->GET_INFO("StreamCacheOutProxy");
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$StreamCacheOutProxy])){$StreamCacheOutProxy=null;}

	
	$f[]="";
	$f[]="# ************** REDIRECTOR ********************";
	$f[]="url_rewrite_program $python /usr/share/videocache/videocache.py";
	$f[]="url_rewrite_children $StreamCacheUrlRewiteNumber";
	$f[]="url_rewrite_concurrency $StreamCacheUrlRewiteNumber";
	$f[]="";
	$f[]="# ***********************************************";
	
	$f[]="";
	$f[]="acl vc_deny_myport myport $StreamCacheLocalPort";
	
	$f[]="";
	$f[]="acl vc_deny_url url_regex -i \.blip\.tv\/(.*)filename \.hardsextube\.com\/videothumbs \.xtube\.com\/(.*)(Thumb|videowall) www\.youtube\.com\/";
	$f[]="acl vc_deny_url url_regex -i \.(youtube|googlevideo)\.com\/.*\/manifest";
	$f[]="acl vc_deny_url url_regex -i \.(youtube|googlevideo)\.com\/videoplayback?.*playerretry=[0-9]";
	$f[]="acl vc_deny_dom dstdomain .manifest.youtube.com .manifest.googlevideo.com";
	$f[]="acl vc_deny_dom dstdomain .redirector.googlevideo.com .redirector.youtube.com";
	$f[]="";
	$f[]="acl vc_url url_regex -i \/youku\/[0-9A-Z]+\/[0-9A-Z\-]+\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$f[]="acl vc_url url_regex -i \/(.*)key=[a-z0-9]+(.*)\.flv";
	$f[]="acl vc_url url_regex -i \-xh\.clients\.cdn[0-9a-zA-Z]?[0-9a-zA-Z]?[0-9a-zA-Z]?\.com\/data\/(.*)\.flv";
	$f[]="acl vc_url url_regex -i \.(youtube|youtube-nocookie|googlevideo)\.com\/feeds\/api\/videos\/[0-9a-zA-Z_-]{11}\/";
	$f[]="acl vc_url url_regex -i \.(youtube|youtube-nocookie|googlevideo)\.com\/(videoplayback|get_video|watch_popup|user_watch|stream_204|get_ad_tags|get_video_info|player_204|ptracking|set_awesome)\?";
	$f[]="acl vc_url url_regex -i \.(youtube|youtube-nocookie|googlevideo)\.com\/(v|e|embed)\/[0-9a-zA-Z_-]{11}";
	$f[]="acl vc_url url_regex -i \.youtube\.com\/s\? \.youtube\.com\/api\/stats\/(atr|delayplay|playback|watchtime)\?";
	$f[]="acl vc_url url_regex -i \.(youtube|youtube-nocookie|googlevideo)\.com\/videoplayback\/id\/[0-9a-zA-Z_-]+\/";
	$f[]="acl vc_url url_regex -i \.android\.clients\.google\.com\/market\/GetBinary\/";
	$f[]="acl vc_url url_regex -i cs(.*)\.vk\.me\/(.*)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$f[]="acl vc_url url_regex -i video(.*)\.rutube\.ru\/(.*)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)Seg[0-9]+-Frag[0-9]+";
	$f[]="";
	$f[]="acl vc_dom_r dstdom_regex -i msn\..*\.(com|net)";
	$f[]="acl vc_dom_r dstdom_regex -i msnbc\..*\.(com|net)";
	$f[]="acl vc_dom_r dstdom_regex -i video\..*\.fbcdn\.net";
	$f[]="acl vc_dom_r dstdom_regex -i myspacecdn\..*\.footprint\.net";
	$f[]="";
	$f[]="acl vc_dom dstdomain .stream.aol.com .5min.com .msn.com .blip.tv .dmcdn.net .break.com .vimeo.com .vimeocdn.com video.thestaticvube.com";
	$f[]="acl vc_dom dstdomain .dailymotion.com .c.wrzuta.pl .v.imwx.com .mccont.com .myspacecdn.com video-http.media-imdb.com fcache.veoh.com";
	$f[]="acl vc_dom dstdomain .hardsextube.com .public.extremetube.phncdn.com .redtubefiles.com .video.pornhub.phncdn.com .videos.videobash.com";
	$f[]="acl vc_dom dstdomain .public.keezmovies.com .public.keezmovies.phncdn.com .slutload-media.com .public.spankwire.com .xtube.com";
	$f[]="acl vc_dom dstdomain .public.youporn.phncdn.com .xvideos.com .tube8.com .public.spankwire.phncdn.com .pornhub.com";
	$f[]="";
	
	$f[]="";
	$f[]="acl vc_deny_url url_regex -i crossdomain.xml";
	$f[]="acl vc_method method GET";
	$f[]="acl vc_header req_header X-Requested-With -i videocache";
	$f[]="url_rewrite_access deny vc_deny_myport";
	$f[]="url_rewrite_access deny !vc_method";
	$f[]="url_rewrite_access deny vc_header";
	$f[]="url_rewrite_access deny vc_deny_dom";
	$f[]="url_rewrite_access deny vc_deny_url";
	$f[]="url_rewrite_access allow vc_dom";
	$f[]="url_rewrite_access allow vc_url";
	$f[]="url_rewrite_access allow vc_dom_r";

	
	CheckFilesAndSecurity();
	

	
	
	
	

	
	
	
	$f=array();
	$f[]="[main]";
	
	$StreamCacheBindHTTP=VerifHTTPIP();
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	if($FreeWebListenPort<>80){ $StreamCacheBindHTTP="$StreamCacheBindHTTP:$FreeWebListenPort"; }
	
	$SquidMgrListenPort=intval($sock->GET_INFO("SquidMgrListenPort"));
	$SquidDebugPortInterface=intval($sock->GET_INFO("SquidDebugPortInterface"));
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Apache IP...............: $StreamCacheBindHTTP:$FreeWebListenPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Proxy Port..............: $SquidMgrListenPort\n";}
	
	
	if(!$users->CORP_LICENSE){$emailprefix="trial_"; }
	
	$f[]="client_email = {$emailprefix}$uuid@articatech.com";
	$f[]="scheduler_pidfile = /var/run/squid/videocache.pid";
	$f[]="cache_host = $StreamCacheBindHTTP";
	$f[]="source_ip = 127.0.0.1";
	$f[]="videocache_user = squid";
	$f[]="";
	$f[]="# # # Proxy specifications # # #";
	$f[]="squid_access_log=/var/log/squid/access.log";
	$f[]="enable_access_log_monitoring = 1";
	$f[]="squid_access_log_format_combined = 0";
	$f[]="";
	$f[]="base_dir = /home/squid/videocache/";
	$f[]="logdir = /var/log/squid/";
	$f[]="pidfile = pidfile.txt";
	$f[]="this_proxy=127.0.0.1:$SquidMgrListenPort";
	$f[]="cache_swap_low = 90";
	$f[]="cache_swap_high = 93";
	$f[]="disk_cleanup_strategy = 1";
	$f[]="enable_videocache = 1";
	$f[]="offline_mode = 0";
	$f[]="base_dir_selection = 2";
	$f[]="# # # MySQL setup # # #";
	$f[]="db_hostname = /var/run/mysqld/squid-db.sock";
	$f[]="db_username = root";
	$f[]="db_password =";
	$f[]="db_database = videocache";
	$f[]="max_cache_processes = 4";
	$f[]="max_cache_speed = 0";
	$f[]="";

	$f[]="# # # Remote Proxy # # #";
	$f[]="proxy =127.0.0.1:$SquidMgrListenPort";

	
	$f[]="max_video_size = 0";
	$f[]="min_video_size = 0";
	$f[]="force_video_size = 1";
	$f[]="logformat = %tl %p %s %i %w %c %v %m %d";
	$f[]="scheduler_logformat = %tl %p %s %i %w %c %v %m %d";
	$f[]="cleaner_logformat = %tl %p %s %w %c %v %m %d";
	$f[]="db_query_logformat = %tl %m";
	$f[]="timeformat = %d/%b/%Y:%H:%M:%S";
	$f[]="enable_videocache_log = 1";
	$f[]="enable_scheduler_log = 1";
	$f[]="enable_cleaner_log = 1";
	$f[]="enable_trace_log = 1";
	$f[]="enable_db_query_log = 0";
	$f[]="logfile = videocache.log";
	$f[]="scheduler_logfile = videocache-scheduler.log";
	$f[]="cleaner_logfile = videocache-cleaner.log";
	$f[]="tracefile = videocache-trace.log";
	$f[]="db_query_logfile = videocache-database.log";
	$f[]="max_logfile_size = 90";
	$f[]="max_scheduler_logfile_size = 90";
	$f[]="max_cleaner_logfile_size = 5";
	$f[]="max_tracefile_size = 5";
	$f[]="max_db_query_logfile_size = 5";
	
	$f[]="#------------------------------------------------------------------------------";
	$f[]="#                         Website Specific Options                            |";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# This option enables the caching of Android apps across various devices.";
	$f[]="# This option's value can be either 0 or 1.";
	$f[]="enable_android_cache = 1";
	$f[]="";
	$f[]="# These options set minimum and maximum size (in KB) for android apps. An app with";
	$f[]="# size smaller than min_android_app_size or larger than max_android_app_size will";
	$f[]="# not be cached. Set to zero (0) to disable.";
	$f[]="# Default:";
	$f[]="# min_android_app_size = 1024";
	$f[]="# max_android_app_size = 0";
	$f[]="min_android_app_size = 1024";
	$f[]="max_android_app_size = 0";
	$f[]="";
	$f[]="# This option enables the caching of youtube videos.";
	$f[]="# This option's value can be either 0 or 1.";
	$f[]="#----------------------------------------------------------------------------";
	$f[]="# | IMPORTANT : Each supported website have an option to enable or disable  |";
	$f[]="# | caching of its videos in the form enable_website_cache. You can opt to  |";
	$f[]="# | cache the websites you want by disabling the caching for other websites |";
	$f[]="#----------------------------------------------------------------------------";
	$f[]="# Default : 1";
	$f[]="enable_youtube_cache = 1";
	$f[]="";
	$f[]="# This options determines if Videocache will cache different YouTube video";
	$f[]="# formats separately. Please select an appropriate algorithm from the listed below.";
	$f[]="# Available strategies:";
	$f[]="#   1 : (disabled) Don't check for YouTube video formats. Cache one of the formats";
	$f[]="#       and serve it for requests for all kinds of formats.";
	$f[]="#   2 : (strict) Strictly check for YouTube formats and cache all formats separately.";
	$f[]="#       Consumes maximum bandwidth.";
	$f[]="#   3 : (approximate) Check YouTube formats but with approximation. For example,";
	$f[]="#       if a client asked for a video in 480p format and we already have 360p";
	$f[]="#       format of the same video in cache, then serve 360p format and vice-versa.";
	$f[]="# Default : 3";
	$f[]="enable_youtube_format_support = 3";
	$f[]="";
	$f[]="# This option enables the caching of HTML5 videos from YouTube.";
	$f[]="# This option's value can be 0 or 1.";
	$f[]="# Default : 1";
	$f[]="enable_youtube_html5_videos = 1";
	$f[]="";
	$f[]="# This option enables the caching of 3D videos from YouTube.";
	$f[]="# This option's value can either be 0 or 1.";
	$f[]="# Default : 1";
	$f[]="enable_youtube_3d_videos = 1";
	$f[]="";
	$f[]="# This option enables the caching of several video segments used by YouTube";
	$f[]="# to serve a single video. This option works only when enable_store_log_monitoring";
	$f[]="# option is enabled. This option's value can either be 0 or 1.";
	$f[]="# Default : 1";
	$f[]="enable_youtube_partial_caching = 1";
	$f[]="";
	$f[]="# This option enforces the maximum video quality from Youtube. If a user browses";
	$f[]="# a video in higher quality format, Videocache will still cache and serve the video";
	$f[]="# in the format specified below or a lower quality format depending on the availability.";
	$f[]="# Valid values : 480p, 720p, 1080p, 2304p (Please don't append p)";
	$f[]="# Default : 720";
	$f[]="max_youtube_video_quality = 720";
	$f[]="";
	$f[]="# This option will help in enhancing the performance of Videocache.";
	$f[]="# If min_youtube_views is set to 1000, then Videocache will cache a video only";
	$f[]="# if it has received at least 1000 views on Youtube. Otherwise, video will not";
	$f[]="# be cached. Set this to 0 to disable this option.";
	$f[]="# Default : 100";
	$f[]="min_youtube_views = 100";
	$f[]="";
	$f[]="# www.aol.com";
	$f[]="enable_aol_cache = 1";
	$f[]="";
	$f[]="# www.bing.com";
	$f[]="enable_bing_cache = 1";
	$f[]="";
	$f[]="# www.blip.tv";
	$f[]="enable_bliptv_cache = 1";
	$f[]="";
	$f[]="# www.break.com";
	$f[]="enable_breakcom_cache = 1";
	$f[]="";
	$f[]="# www.dailymotion.com";
	$f[]="enable_dailymotion_cache = 1";
	$f[]="";
	$f[]="# www.facebook.com";
	$f[]="enable_facebook_cache = 1";
	$f[]="";
	$f[]="# www.imdb.com";
	$f[]="enable_imdb_cache = 1";
	$f[]="";
	$f[]="# www.metacafe.com";
	$f[]="enable_metacafe_cache = 1";
	$f[]="";
	$f[]="# www.myspace.com";
	$f[]="enable_myspace_cache = 1";
	$f[]="";
	$f[]="# www.veoh.com";
	$f[]="enable_veoh_cache = 1";
	$f[]="";
	$f[]="# www.videobash.com";
	$f[]="enable_videobash_cache = 1";
	$f[]="";
	$f[]="# www.vimeo.com";
	$f[]="enable_vimeo_cache = 1";
	$f[]="";
	$f[]="# www.vube.com";
	$f[]="enable_vube_cache = 1";
	$f[]="";
	$f[]="# www.weather.com";
	$f[]="enable_weather_cache = 1";
	$f[]="";
	$f[]="# www.wrzuta.pl";
	$f[]="enable_wrzuta_cache = 1";
	$f[]="";
	$f[]="# www.youku.com";
	$f[]="enable_youku_cache = 1";
	$f[]="";
	$f[]="# Pr0n sites";
	$f[]="# www.extremetube.com";
	$f[]="enable_extremetube_cache = 1";
	$f[]="";
	$f[]="# www.hardsextube.com";
	$f[]="enable_hardsextube_cache = 1";
	$f[]="";
	$f[]="# www.keezmovies.com";
	$f[]="enable_keezmovies_cache = 1";
	$f[]="";
	$f[]="# www.pornhub.com";
	$f[]="enable_pornhub_cache = 1";
	$f[]="";
	$f[]="# www.redute.com";
	$f[]="enable_redtube_cache = 1";
	$f[]="";
	$f[]="# www.slutload.com";
	$f[]="enable_slutload_cache = 1";
	$f[]="";
	$f[]="# www.spankwire.com";
	$f[]="enable_spankwire_cache = 1";
	$f[]="";
	$f[]="# www.tube8.com";
	$f[]="enable_tube8_cache = 1";
	$f[]="";
	$f[]="# www.xhamster.com";
	$f[]="enable_xhamster_cache = 1";
	$f[]="";
	$f[]="# www.xtube.com";
	$f[]="enable_xtube_cache = 1";
	$f[]="";
	$f[]="# www.xvideos.com";
	$f[]="enable_xvideos_cache = 1";
	$f[]="";
	$f[]="# www.youporn.com";
	$f[]="enable_youporn_cache = 1";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="#                      Apache Configuration Options                           |";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# Use this option if you don't want Videocache to generate Apache specific";
	$f[]="# configuration on your system. This can be used when you are using other";
	$f[]="# web server than Apache. Like lighttpd etc.";
	$f[]="# Default : 0";
	$f[]="skip_apache_conf = 0";
	$f[]="";
	$f[]="# This option specifies the absolute path to your Apache's conf.d or extra";
	$f[]="# directory. Videocache will generate and save Videocache spcecific ";
	$f[]="# configuration for Apache in this directory.";
	$f[]="# Example : /etc/httpd/conf.d/ or /etc/apache2/conf.d/ or /etc/httpd/extra/";
	$f[]="# Default : NOT SET";
	
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$python=$unix->find_program("python");
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	$sock->SET_INFO("EnableFreeWeb",1);

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Apache..................: $DAEMON_PATH\n";}
	
	
	$f[]="apache_conf_dir = $DAEMON_PATH";
	$f[]="";
	$f[]="# This option can be used to hide cache directories from your clients. Your";
	$f[]="# clients will not be able to browse the contents cache directories via HTTP";
	$f[]="# if this option is enabled. Browsing videos will not be affected.";
	$f[]="# Default : 1";
	$f[]="hide_cache_dirs = 1";
	$f[]="";	
	@file_put_contents("/etc/videocache.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/videocache.conf done\n";}
	shell_exec("$python /usr/share/videocache/vc-update >/dev/null 2>&1");
	
	
	$f[]=array();
	
	$StreamCacheCache=$sock->GET_INFO("StreamCacheCache");
	$StreamCacheMainCache=$sock->GET_INFO("StreamCacheMainCache");
	
	if($StreamCacheCache==null){$StreamCacheCache="/home/squid/videocache";}
	if($StreamCacheMainCache==null){$StreamCacheMainCache="/home/squid/streamcache";}
	$f[]="##############################################################################";
	$f[]="#                                                                            #";
	$f[]="# file : $DAEMON_PATH/videocache.conf                                        #";
	$f[]="#                                                                            #";
	$f[]="# Videocache is a squid url rewriter to cache videos from various websites.  #";
	$f[]="# Check http://cachevideos.com/ for more details.                            #";
	$f[]="#                                                                            #";
	$f[]="# ----------------------------- Note This ---------------------------------- #";
	$f[]="# Don't change this file under any circumstances.                            #";
	$f[]="# Use /etc/videocache.conf to configure Videocache.                          #";
	$f[]="#                                                                            #";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="";
	$f[]="Alias /crossdomain.xml /home/squid/videocache/youtube_crossdomain.xml";
	$f[]="Alias /videocache $StreamCacheCache/";
	$f[]="<Directory $StreamCacheCache/>";
	$f[]="  Options -Indexes";
	$f[]="  Order Allow,Deny";
	$f[]="  Allow from all";
	$f[]="  <IfModule mod_headers.c>";
	$f[]="    Header add Videocache \"2.0.0\"";
	$f[]="    Header add X-Cache \"HIT from 192.168.1.210\"";
	$f[]="  </IfModule>";
	$f[]="  <IfModule mod_mime.c>";
	$f[]="    AddType video/webm .webm";
	$f[]="    AddType application/vnd.android.package-archive .android";
	$f[]="  </IfModule>";
	$f[]="</Directory>";
	$f[]="";	
	if(!is_file("$DAEMON_PATH/videocache.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $DAEMON_PATH/videocache.conf done\n";}
		@file_put_contents("$DAEMON_PATH/videocache.conf", @implode("\n", $f));
	}
	$f=array();
	$LOCATE_APACHE_CONF_PATH=$unix->LOCATE_APACHE_CONF_PATH();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Apache config: $LOCATE_APACHE_CONF_PATH\n";}
	
	$APACHECONF=FALSE;
	$exp=explode("\n",@file_get_contents($LOCATE_APACHE_CONF_PATH));
	while (list ($index, $line) = each ($exp)){
		if(!preg_match("#Include.*?videocache\.conf#", $line)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Apache $line Done\n";}
		$APACHECONF=true;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(!$APACHECONF){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reconfigure Apache\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --httpd");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Configuration done..\n";}
	

}

function pythonInstallDir(){
	return "/usr/local/lib/python2.7/dist-packages";
	
}

function VerifHTTPIP(){
	$unix=new unix();
	$sock=new sockets();
	$StreamCacheBindHTTP=$sock->GET_INFO("StreamCacheBindHTTP");
	$IpClass=new IP();
	if(!$IpClass->isIPAddress($StreamCacheBindHTTP)){$StreamCacheBindHTTP=null;}
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
	unset($NETWORK_ALL_INTERFACES["127.0.0.1"]);
	if(!isset($NETWORK_ALL_INTERFACES[$StreamCacheBindHTTP])){$StreamCacheBindHTTP=null;}
	
	if($StreamCacheBindHTTP<>null){return $StreamCacheBindHTTP;}
	return $unix->NETWORK_DEFAULT_IP_ADDR();
	
	
	
}





function install_module($modulename){
	
	
	$unix=new unix();
	$packagePath="/usr/share/artica-postfix/bin/install/squid/$modulename.tar.gz";
	$TMPDIR=$unix->TEMP_DIR()."/".time();
	build_progress("Installing module $modulename",25);
	$tar=$unix->find_program("tar");
	$cd=$unix->find_program("cd");
	$python=$unix->find_program("python");
	$rm=$unix->find_program("rm");
	if(!is_file($packagePath)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ". basename($packagePath)." no such file\n";}
		return false;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} extracting ". basename($packagePath)."\n";}
	
		@mkdir($TMPDIR,0755,true);
		shell_exec("$tar xf $packagePath -C $TMPDIR/");
		$dirs=$unix->dirdir($TMPDIR);
		$workingdir=null;
		while (list ($Directory, $val) = each ($dirs)){
			if(is_file("$Directory/setup.py")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} found $Directory\n";}
				$workingdir=$Directory;
				break;
			}
		}
	
		if($workingdir==null){
			recursive_remove_directory("$TMPDIR");
			return false;
		}
		chdir($workingdir);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} installing - $python - \n";}
		system("$python setup.py install");
		$pythonInstallDir=pythonInstallDir();
		recursive_remove_directory("$TMPDIR");
		if(python_verify_modules($modulename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $modulename installed\n";}
			return true;
		}	
}



function python_verify_modules($modulename){
	$unix=new unix();
	$python=$unix->find_program("python");
	exec("$python -c \"import $modulename\" 2>&1",$results);
	while (list ($index, $line) = each ($results)){
		if(preg_match("#ImportError:#i", $line)){return false;}
		
	}
	return true;
 
	
}




function install_video_cache_python(){
	$unix=new unix();
	$packagePath="/usr/share/artica-postfix/bin/install/squid/videocache.tar.gz";
	$TMPDIR=$unix->TEMP_DIR()."/".time();
	
	$tar=$unix->find_program("tar");
	$cd=$unix->find_program("cd");
	$python=$unix->find_program("python");
	$rm=$unix->find_program("rm");
	if(!is_file($packagePath)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ". basename($packagePath)." no such file\n";}
		return false;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} extracting ". basename($packagePath)."\n";}
	
		@mkdir($TMPDIR,0755,true);
		build_progress("Uncompressing videocache package",30);
		shell_exec("$tar xf $packagePath -C $TMPDIR/");
		$dirs=$unix->dirdir($TMPDIR);
		$workingdir=null;
		while (list ($Directory, $val) = each ($dirs)){
			if(is_file("$Directory/setup.py")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} found $Directory\n";}
				$workingdir=$Directory;
				break;
			}
		}
	
		if($workingdir==null){
			recursive_remove_directory("$TMPDIR");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed workdir = NULL\n";}
			return false;
		}
		chdir($workingdir);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} using $workingdir\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} installing - $python - \n";}
		build_progress("Installing videocache package",35);
		system("$python setup.py -e a@b.me -u squid --cache-host 10.1.1.1 --this-proxy 127.0.0.1:3128 --squid-access-log /var/log/squid3/access.log --apache-conf-dir /etc/httpd/conf.d --db-hostname /var/run/mysqld/squid-db.sock --db-username root --db-database videocache install");	
		system("$python setup.py -e a@b.me -u squid --cache-host 10.1.1.1 --this-proxy 127.0.0.1:3128 --squid-access-log /var/log/squid3/access.log --apache-conf-dir /etc/httpd/conf.d --db-hostname /var/run/mysqld/squid-db.sock --db-username root --db-database videocache install");
		recursive_remove_directory("$TMPDIR");
		chdir("/root");
		if(!is_file("/usr/share/videocache/videocache.py")){return false;}
		return true;
	
}

function reinstall_video_cache($aspid=false){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			build_progress("Already Artica task running",110);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$chattr=$unix->find_program("chattr");
	build_progress("Remove old install",5);
	shell_exec("$chattr -i -R /usr/share/videocache");
	
	@unlink("/usr/share/videocache/videocache.py");
	if(!install_video_cache()){
		build_progress("{failed}",110);
		return;
	}
	build_progress("Stopping services",50);
	stop(true);
	build_progress("Building configuration",55);
	build();
	build_progress("Starting services",60);
	start(true);
	build_progress("{done}",100);
	
}

function build_progress($text,$pourc){
	$PROGRESS_FILE="/usr/share/artica-postfix/ressources/logs/videocache.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($PROGRESS_FILE, serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
function install_video_cache($aspid=false){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	
	
	$modules["setuptools"]=true;
	$modules["iniparse"]=true;
	$modules["netifaces"]=true;
	$modules["cloghandler"]=true;
	$unix=new unix();
	$python=$unix->find_program("python");
	CHECK_DATABASE();
	
	build_progress("Checking modules",15);
	
	while (list ($modulename, $line) = each ($modules)){
		build_progress("Checking modules $modulename",20);
		if(!python_verify_modules($modulename)){
			if(!install_module($modulename)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [!!] $modulename failed\n";}
				build_progress("Installing module $modulename failed",110);
				return false;
			}else{
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [OK] $modulename INSTALLED\n";}
			}
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [OK] $modulename\n";}
		}
		
	}
	
	$files=videocache_files();

	$INSTALLED=true;
	while (list ($modulename, $filepath) = each ($files)){
		if(!is_file($filepath)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [!!] ".basename($filepath)." no such file\n";}
			$INSTALLED=false;
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [OK] ".basename($filepath)." \n";}
	}
	
	
	if(!$INSTALLED){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Installing VideoCache\n";}
		if(!install_video_cache_python()){
			build_progress("Installing videocache package failed",110);
			return false;}
		$files=videocache_files();
		while (list ($modulename, $filepath) = each ($files)){
			if(!is_file($filepath)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [!!] ".basename($filepath)." no such file\n";}
				return false;
			}
		}
	}
	
	$chattr=$unix->find_program("chattr");
	shell_exec("$chattr +i -R /usr/share/videocache");
	
	$tables["video_files"]=true;
	$tables["video_queue"]=true;         
	$tables["youtube_cpns"]=true; 
	
	
	$tablesz=true;
	build_progress("Installing Tables",40);
	while (list ($tablename, $line) = each ($tables)){
		if(!TABLE_EXISTS($tablename,"videocache")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} missing table $tablename\n";} 
			$tablesz=false;}
	}
	
	if(!$tablesz){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} upgrading videocache\n";}
		shell_exec("$python /usr/share/videocache/vc-update >/dev/null 2>&1");
		reset($tables);
		$GLOBALS["VIDEOCACHE_TABLES"]=array();
		$tablesz=true;
		while (list ($tablename, $line) = each ($tables)){
			if(!TABLE_EXISTS($tablename,"videocache")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} missing table $tablename\n";}
				$tablesz=false;
				break;
			}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} `$tablename` OK\n";}
		}
	}
	
	if(!$tablesz){
		build_progress("Installing Tables failed",110);
		return false;}
	
	build_progress("Installing Tables success",45);
	return true;
	// /artica-postfix/bin/install/squid/videocache.tar.gz
	
	
}


function videocache_files(){
	$f[]="/usr/share/videocache/vcconfig.py";
	$f[]="/usr/share/videocache/vcsysinfo.py";
	$f[]="/usr/share/videocache/store.pyc";
	$f[]="/usr/share/videocache/__init__.py";
	$f[]="/usr/share/videocache/vcconfig.pyc";
	$f[]="/usr/share/videocache/database.pyc";
	$f[]="/usr/share/videocache/common.pyc";
	$f[]="/usr/share/videocache/vcoptions.pyc";
	$f[]="/usr/share/videocache/videocache.py";
	$f[]="/usr/share/videocache/database.py";
	$f[]="/usr/share/videocache/vcoptions.py";
	$f[]="/usr/share/videocache/common.py";
	$f[]="/usr/share/videocache/vcsysinfo.pyc";
	$f[]="/usr/share/videocache/vcdaemon.py";
	$f[]="/usr/share/videocache/websites/hardsextube.py";
	$f[]="/usr/share/videocache/websites/redtube.py";
	$f[]="/usr/share/videocache/websites/dailymotion.py";
	$f[]="/usr/share/videocache/websites/youporn.py";
	$f[]="/usr/share/videocache/websites/wrzuta.py";
	$f[]="/usr/share/videocache/websites/extremetube.py";
	$f[]="/usr/share/videocache/websites/rutube.pyc";
	$f[]="/usr/share/videocache/websites/pornhub.py";
	$f[]="/usr/share/videocache/websites/bing.py";
	$f[]="/usr/share/videocache/websites/slutload.py";
	$f[]="/usr/share/videocache/websites/__init__.py";
	$f[]="/usr/share/videocache/websites/vube.py";
	$f[]="/usr/share/videocache/websites/imdb.py";
	$f[]="/usr/share/videocache/websites/bliptv.py";
	$f[]="/usr/share/videocache/websites/vimeo.py";
	$f[]="/usr/share/videocache/websites/tube8.py";
	$f[]="/usr/share/videocache/websites/keezmovies.py";
	$f[]="/usr/share/videocache/websites/youtube.py";
	$f[]="/usr/share/videocache/websites/android.py";
	$f[]="/usr/share/videocache/websites/veoh.py";
	$f[]="/usr/share/videocache/websites/rutube.py";
	$f[]="/usr/share/videocache/websites/xhamster.py";
	$f[]="/usr/share/videocache/websites/videobash.py";
	$f[]="/usr/share/videocache/websites/myspace.py";
	$f[]="/usr/share/videocache/websites/vkcom.py";
	$f[]="/usr/share/videocache/websites/weather.py";
	$f[]="/usr/share/videocache/websites/aol.py";
	$f[]="/usr/share/videocache/websites/xvideos.py";
	$f[]="/usr/share/videocache/websites/facebook.py";
	$f[]="/usr/share/videocache/websites/xtube.py";
	$f[]="/usr/share/videocache/websites/vkcom.pyc";
	$f[]="/usr/share/videocache/websites/youku.py";
	$f[]="/usr/share/videocache/websites/breakcom.py";
	$f[]="/usr/share/videocache/websites/__init__.pyc";
	$f[]="/usr/share/videocache/websites/spankwire.py";
	$f[]="/usr/share/videocache/websites/metacafe.py";
	$f[]="/usr/share/videocache/vc-update";
	$f[]="/usr/share/videocache/vc-scheduler";
	$f[]="/usr/share/videocache/Commercial License.txt";
	$f[]="/usr/share/videocache/store.py";
	$f[]="/usr/share/videocache/fsop.pyc";
	$f[]="/usr/share/videocache/fsop.py";
	return $f;
	
}

function CHECK_DATABASE(){
	build_progress("Chking database",10);
	$bd=@mysql_connect(":/var/run/mysqld/squid-db.sock","root",null);
	if(!$bd){
		$des=@mysql_error(); $errnum=@mysql_errno();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Error $errnum $des\n";}
		return false;
	}
	$ok=@mysql_select_db("mysql",$bd);
	
	
	
	if(!$ok){
		$des=@mysql_error(); $errnum=@mysql_errno();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Error $errnum $des\n";}
		return false;
	}	
	
	@mysql_unbuffered_query("CREATE DATABASE videocache",$bd);
	
}

function TABLE_EXISTS($table){
	$resutz=false;
	if(isset($GLOBALS["VIDEOCACHE_TABLES"][$table])){return true;}
	$bd=@mysql_connect(":/var/run/mysqld/squid-db.sock","root",null);
	if(!$bd){
		$des=@mysql_error(); $errnum=@mysql_errno();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Error $errnum $des\n";}
		return false; 
	}
	$ok=@mysql_select_db("videocache",$bd);
	if(!$ok){
		$des=@mysql_error(); $errnum=@mysql_errno();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Error $errnum $des\n";}
		return false;
	}
	$results=@mysql_unbuffered_query("SHOW TABLES",$bd);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$GLOBALS["VIDEOCACHE_TABLES"][$ligne["Tables_in_videocache"]]=true;
		if(strtolower($table)==strtolower($ligne["Tables_in_videocache"])){$resutz= true;}
	}
	return $resutz;
	
}


function start($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	
	
	
	$enableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
	if($enableStreamCache==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see enableStreamCache )...\n";}
		stop_vc_scheduler(true);
		return;		
	}

	start_vc_scheduler(true);
	shell_exec("/etc/init.d/squid reload");
		
	
}

function CheckFilesAndSecurity(){
	$unix=new unix();
	$sock=new sockets();
	
	$StreamCacheCache=$sock->GET_INFO("StreamCacheCache");
	if($StreamCacheCache==null){$StreamCacheCache="/home/squid/videocache";}
	
	$StreamCacheMainCache=$sock->GET_INFO("StreamCacheMainCache");
	if($StreamCacheMainCache==null){$StreamCacheMainCache="/home/squid/streamcache";}
	
	$f[]="/var/log/videocache";
	$f[]=$StreamCacheCache;
	$f[]="/etc/streamsquidcache";
	$f[]="/var/spool/streamsquidcache";
	$f[]=$StreamCacheMainCache;
	$f[]="/var/run/squid";
	$f[]="/usr/share/streamsquidcache";
	
	
		
	
	while (list ($num, $val) = each ($f)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
		if(!is_dir($val)){@mkdir($val,0755,true);}
		$unix->chown_func("squid","squid","$val/*");
	}
	
	$MAINDIR=true;
	
	for($i=0;$i<10;$i++){
		$dir="$StreamCacheMainCache/0{$i}";
		if(!is_dir($dir)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $dir no such directory\n";}
			$MAINDIR=false;
			break;
		}
		
		
	}
	
	if(!$MAINDIR){
		$masterbin=$unix->find_program("streamsquidcache");
		shell_exec("$masterbin -f /etc/streamsquidcache/squid.conf -z");
	}
	
}

function stop(){

	$unix=new unix();
	
	$sock=new sockets();
	$masterbin=$unix->find_program("streamsquidcache");
	$python=$unix->find_program("python");
	stop_vc_scheduler(true);
}
function reload_vc_scheduler(){
	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("streamsquidcache");
	$python=$unix->find_program("python");
	$pid=videocache_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler stopped...\n";}
		start(true);
		return;
	}
	
	if(!is_file("/usr/share/videocache/vc-scheduler")){
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} vc-scheduler no found, reinstall!\n";}
		reinstall_video_cache();
	}
	
	exec("$python /usr/share/videocache/vc-scheduler -s restart 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $val\n";}
	}
	
	
}

function stop_vc_scheduler($nopid=false){
	$unix=new unix();
	$sock=new sockets();

	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	
	$masterbin="/usr/share/videocache/vc-scheduler";
	$python=$unix->find_program("python");	
	$kill=$unix->find_program("kill");
	$pid=videocache_pid();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler stopped...\n";}
		return;
	}
	
	$results=array();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler..\n";}
	exec("$python /usr/share/videocache/vc-scheduler -s stop 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $val\n";}
	}
	
	$pid=videocache_pid();
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler KILLING PID $pid...\n";}
		unix_system_kill_force($pid);
	}
	
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler success stopped...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Videocache Scheduler failed to stop...\n";}
		return;
	}
	
}
function restart_vc_scheduler($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop_vc_scheduler(true);
	build();
	if(!install_video_cache()){
		if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Unable to install\n";}
		return;

	}
	start_vc_scheduler(true);
}


function videocache_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/squid/videocache.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("python.*?vc-scheduler");	
}




function start_vc_scheduler($nopid=false){
	$unix=new unix();
	$sock=new sockets();

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}



	$pid=videocache_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Already running since {$time}Mn...\n";}
		return;
	}

	$enableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
	if($enableStreamCache==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Disabled ( see enableStreamCache )...\n";}
		return;
	}


	$python=$unix->find_program("python");
	$masterbin="/usr/share/videocache/vc-scheduler";
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Not installed...\n";}
		return;
	}


	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Starting service\n";}
	@unlink("/var/run/videocache.pid");
	$cmd="$python $masterbin -s start";
	shell_exec($cmd);

	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Starting service waiting $c/10\n";}
		$pid=videocache_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}

	$pid=videocache_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME2"]} $cmd\n";}
		return;
	}
}

function get_status(){

	$unix=new unix();

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $TimeFile\n";}
	if(!$GLOBALS["VERBOSE"]){
		$zTime=$unix->file_time_min($TimeFile);
		if($zTime<10){return;}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());	
	$sock=new sockets();
	$StreamCacheCache=$sock->GET_INFO("StreamCacheCache");
	$StreamCacheMainCache=$sock->GET_INFO("StreamCacheMainCache");
	
	if($StreamCacheCache==null){$StreamCacheCache="/home/squid/videocache";}
	if($StreamCacheMainCache==null){$StreamCacheMainCache="/home/squid/streamcache";}
	
	
	$dir=$StreamCacheCache;
	if(is_link($dir)){$dir=readlink($dir);}
	$Partition=$unix->DIRPART_INFO($dir);
	$SIZE=$unix->DIRSIZE_BYTES($dir);
	
	$array["VIDEOCACHE"]["SIZE"]=$SIZE;
	$array["VIDEOCACHE"]["PART"]=$Partition;
	
	$dir=$StreamCacheMainCache;
	if(is_link($dir)){$dir=readlink($dir);}
	$Partition=$unix->DIRPART_INFO($dir);
	$SIZE=$unix->DIRSIZE_BYTES($dir);
	
	$array["SQUID"]["SIZE"]=$SIZE;
	$array["SQUID"]["PART"]=$Partition;
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/videocache.dirs.status.db", @serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/videocache.dirs.status.db",0755);
}
function check_dirs(){
	$sock=new sockets();
	$unix=new unix();
	

	$StreamCacheCache=$sock->GET_INFO("StreamCacheCache");
	$StreamCacheMainCache=$sock->GET_INFO("StreamCacheMainCache");
	
	if($StreamCacheCache==null){$StreamCacheCache="/home/squid/videocache";}
	if($StreamCacheMainCache==null){$StreamCacheMainCache="/home/squid/streamcache";}
	
	$cp=$unix->find_program("cp");
	$ln=$unix->find_program("ln");
	$chmod=$unix->find_program("chmod");
	$rm=$unix->find_program("rm");
	$chown=$unix->find_program("chown");
	
	
	$src="/home/squid/videocache";
	if(is_link($src)){$src=readlink($src);}
	if(is_dir($src)){
		if($StreamCacheCache<>$src){
			@mkdir($StreamCacheCache,0755,true);
			shell_exec("$cp -rfd $src/* $StreamCacheCache/");
			recursive_remove_directory("$src");
			shell_exec("$chown -R squid:squid $StreamCacheCache");
		}
	}
	
	$src="/home/squid/streamcache";
	if(is_link($src)){$src=readlink($src);}
	if(is_dir($src)){
		if($StreamCacheMainCache<>$src){
			@mkdir($StreamCacheMainCache,0755,true);
			shell_exec("$cp -rfd $src/* $StreamCacheMainCache/");
			recursive_remove_directory("$src");
			shell_exec("$chown -R squid:squid $StreamCacheMainCache");
		}
	}
		
}

function ParseScheduler(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $TimeFile\n";}
	if(!$GLOBALS["VERBOSE"]){
		$zTime=$unix->file_time_min($TimeFile);
		if($zTime<5){return;}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$echo=$unix->find_program("echo");
	
	
	if(!is_file("/var/log/squid/videocache-scheduler.log")){return;}
	
	if(!is_file("/var/log/squid/videocache-scheduler.log.temp")){
		@copy("/var/log/squid/videocache-scheduler.log", "/var/log/squid/videocache-scheduler.log.temp");
		shell_exec("$echo \"\" > /var/log/squid/videocache-scheduler.log");
	}
	
	
	
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`videocacheA` (
				`zDate` DATETIME NOT NULL,
				`zSize` INT UNSIGNED ,
				 KEY `zDate` ( `zDate` ),
				 KEY `zSize`(`zSize`)
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	
	$pattern="#^(.+?)\s+[0-9]+\s+INFO\s+-\s+YOUTUBE VIDEO_CACHED\s+.*?Video fetched from squid disk cache and stored at\s+(.+)\s+#";
	
	$handle = @fopen("/var/log/squid/videocache-scheduler.log.temp", "r");
	
	
	
	if (!$handle) {
		if($GLOBALS["VERBOSE"]){echo "/var/log/squid/videocache-scheduler.log.temp !!!\n";}
		return;}
	$date=null;
	$c=0;
	while (!feof($handle)){

		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		if(!preg_match($pattern, $buffer,$re)){
				//if($GLOBALS["VERBOSE"]){echo "$buffer NO MATCH\n";} 
				continue;}
		$filename=trim($re[2]);
		if(!is_file($filename)){echo "\"$filename\" no such file\n";continue;}
		$xdate=$re[1];
		if(preg_match("#([0-9]+)\/(.+?)\/([0-9]+):([0-9]+):([0-9]+):([0-9]+)#", $xdate,$ri)){
			$day=$ri[1];
			$Month=$ri[2];
			$year=$ri[3];
			$hour=$ri[4];
			$min=$ri[5];
			$sec=$ri[6];
			$strtime="$day $Month $year $hour:$min:$sec";
			$date=strtotime($strtime);
		}

		$zdate=date("Y-m-d H:i:s",$date);
		$size=@filesize($filename);
		if($GLOBALS["VERBOSE"]){echo "{$re[1]} - $zdate $filename - $size\n";}
		$f[]="('$zdate','$size')";
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO videocacheA (`zDate`,`zSize`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){return;}
	}
	
	
	@unlink("/var/log/squid/videocache-scheduler.log.temp");
}

