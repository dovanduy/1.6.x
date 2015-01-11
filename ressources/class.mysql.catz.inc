<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}

class mysql_catz{
	var $ClassSQL;
	private $called='';
	private $mysql_connection;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="catz";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	var $affected_rows=0;
	var $SocketPath=null;
	var $TryTCP=true;	
	var $arrayConfig=array();
	var $TimeExec=0;
	var $evArray=array();
	var $CacheDatabase="/usr/share/artica-postfix/ressources/logs/web/categories_caches.db";
	
	
	
	
	
	function mysql_catz($nomysql=false){
		
		if(!isset($GLOBALS["SquidPerformance"])){
			if($GLOBALS["AS_ROOT"]){
				$GLOBALS["SquidPerformance"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
			}else{
				
				$GLOBALS["SquidPerformance"]=intval($this->GET_INFO("SquidPerformance"));
			}
		
		}
		
		
		if(!isset($GLOBALS["EnableLocalUfdbCatService"])){
			
			if($GLOBALS["AS_ROOT"]){
				$GLOBALS["EnableLocalUfdbCatService"]=trim(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableLocalUfdbCatService")));
			}else{
				
				$GLOBALS["EnableLocalUfdbCatService"]=intval($this->GET_INFO("EnableLocalUfdbCatService"));
				
			}
			
		}
		if($GLOBALS["EnableLocalUfdbCatService"]==1){
			
			if($this->isRemoteSockets()){
				$GLOBALS["RemoteUfdbCat"]=1;
				$GLOBALS["ufdbCatInterface"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface");
				$GLOBALS["ufdbCatPort"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatPort");
				return;
			}
			
			$GLOBALS["SquidPerformance"]=0;
			$GLOBALS["RemoteUfdbCat"]=0;
			return;
		}
		
		
		if(!isset($GLOBALS["RemoteUfdbCat"])){
			if($GLOBALS["AS_ROOT"]){
				$GLOBALS["RemoteUfdbCat"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteUfdbCat"));
			}else{
				
				$GLOBALS["RemoteUfdbCat"]=intval($this->GET_INFO("RemoteUfdbCat"));
			}
			
		}
		


		if($GLOBALS["RemoteUfdbCat"]==1){
			if(!isset($GLOBALS["ufdbCatPort"])){
				if($GLOBALS["AS_ROOT"]){
					$GLOBALS["ufdbCatPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatPort"));
				}else{
					
					$GLOBALS["ufdbCatPort"]=intval($this->GET_INFO("ufdbCatPort"));
				}
			
			}
			
			if(!isset($GLOBALS["ufdbCatInterface"])){
				if($GLOBALS["AS_ROOT"]){
					$GLOBALS["ufdbCatInterface"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface");
				}else{
					
					$GLOBALS["ufdbCatInterface"]=$this->GET_INFO("ufdbCatInterface");
				}
			}
			
		}
		
		if($GLOBALS["ufdbCatInterface"]==null){$GLOBALS["RemoteUfdbCat"]=0;}
		

	}
	
	private function GET_INFO($key){
		return trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
		
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
	
	public function SHOW_VARIABLES(){
		
	}	
	
	public function TestingConnection(){
		return true;
	}
	
	public function EXECUTE_SQL($sql){

	}	
	
	public function COUNT_ROWS($table,$database=null){

		
	}
	
	
	public function TABLE_SIZE($table,$database=null){
	
	}
	
	public function TABLE_EXISTS($table,$database=null,$nocache=false){
		if(is_file("/home/artica/categories_databases/$table.db")){return true;}
		
	
		
	}
	private function DATABASE_EXISTS($database){
		return true;
	}
	
	private function FIELD_EXISTS($table,$field,$database=null){
		
	}
	
	public function QUERY_SQL($sql,$database=null){
		
	}
	
	private function FIELD_TYPE($table,$field,$database=null){
		
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database=null){
		
	}
	
	private FUNCTION CREATE_DATABASE($database){
		
	}
	
	function category_transform_name($category){
			if($category=="teans"){$category="teens";}
			if($category=="forum"){$category="forums";}
			if($category=="gambling"){$category="gamble";}
			if($category=="radio"){$category="webradio";}
			$category=str_replace('/',"_",$category);
			$category=str_replace('-',"_",$category);
			return $category;	
	}
	
private function ToSyslog($text){
	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("artica-categorize", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

private function microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

	private function ufdbcat_remote($sitename,$forcecache=false){
		$time_start = $this->microtime_float();
		$sock = @fsockopen($GLOBALS["ufdbCatInterface"], $GLOBALS["ufdbCatPort"], $errno, $errst, 2);
		if(!$sock){
			$GLOBALS["ufdbcat"][date("i")]=false;
			if($GLOBALS["VERBOSE"]){echo "*************\n**************\n/var/run/ufdbcat-03977 $errno $errst\n*************\n**************\n";}
			$this->ToSyslog("Error tcp://{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} $errno $errst");
			$this->ok=false;
			$this->mysql_error="tcp://{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} $errno $errst";
			return null;
		}
		
		fwrite($sock, "http://$sitename 192.168.1.158/- - GET myip=192.168.1.238 myport=3128\n");
		
		$resp = trim(fread($sock, 4096));
		fclose($sock);
		
		$time_end = $this->microtime_float();
		$this->TimeExec = round($time_end - $time_start,3);
		
		if($GLOBALS["VERBOSE"]){echo " **** > **** $resp\n";}
		if(preg_match("#\/none\/(.+?)$#", $resp,$re)){
			$re[1]=urldecode($re[1]);
			if(preg_match("#^category_(.+)#", $re[1])){$re[1]=$this->tablename_tocat($re[1]);}
				
			$this->SetMemoryCache($sitename, $re[1]);
			$this->ok=true;
			return $re[1];
		}
		
		
		if($forcecache){$this->SetMemoryCache($sitename, null);}
		$this->ok=true;
		
	}
	
	public function ufdbcat($sitename,$forcecache=false){
		if($GLOBALS["RemoteUfdbCat"]==1){ return $this->ufdbcat_remote($sitename,$forcecache=false);}
		if($GLOBALS["EnableLocalUfdbCatService"]==0){
		if($GLOBALS["SquidPerformance"]>0){if($GLOBALS["SquidPerformance"]) return null;}}
		
		$time_start = $this->microtime_float();
		$sock = stream_socket_client('unix:///var/run/ufdbcat-03977', $errno, $errst);
		if(!$sock){
			if($GLOBALS["VERBOSE"]){echo "<li><strong> unix:///var/run/ufdbcat-03977 socket error</strong></li>\n";}
			$this->mysql_error="Error Unix N.$errno $errst";
			$GLOBALS["ufdbcat"][date("i")]=false;
			if($GLOBALS["VERBOSE"]){echo "*************\n**************\n/var/run/ufdbcat-03977 $errno $errst\n*************\n**************\n";}
			$this->ToSyslog("Error unix:///var/run/ufdbcat-03977 $errno $errst");
			$this->ok=false;
			return false;
		}
		$GLOBALS["ufdbcat"][date("i")]=true;
		fwrite($sock, "http://$sitename 192.168.1.158/- - GET myip=192.168.1.238 myport=3128\n");
		$resp = trim(fread($sock, 4096));
		fclose($sock);
		
		$time_end = $this->microtime_float();
		$this->TimeExec = round($time_end - $time_start,3);
		$this->ok=true;
		
		if($GLOBALS["VERBOSE"]){echo " **** > **** $resp\n";}
		if(preg_match("#\/none\/(.+?)$#", $resp,$re)){
			$re[1]=urldecode($re[1]);
			if(preg_match("#^category_(.+)#", $re[1])){$re[1]=$this->tablename_tocat($re[1]);}
			$this->SetMemoryCache($sitename, $re[1]);
			return $re[1];
		}
		
		
		if($forcecache){$this->SetMemoryCache($sitename, null);}
		return true;
		
	}
	
	private function tablename_tocat($tablename){
		if(isset($GLOBALS["tablename_tocat"][$tablename])){return $GLOBALS["tablename_tocat"][$tablename];}
		$trans=$this->TransArray();
		if(!isset($trans[$tablename])){return $tablename;}
			
	}
	
	
	private function setevents($line){
		if(count($this->evArray)>500){$this->evArray=array();}
		$this->evArray[]=$line;
		
	}
	
	private function GET_LOCAL_DB($sitename){
		if(!is_file($this->CacheDatabase)){return;}
		$db_con = @dba_open($this->CacheDatabase, "r","db4");
		if(!@dba_exists($sitename,$db_con)){
			@dba_close($db_con);
			return;
		}
		
		$catz=dba_fetch($sitename,$db_con);
		@dba_close($db_con);
		return $catz;
	}

	
	public function GET_CATEGORIES($sitename){
		$this->evArray=array();
		if($GLOBALS["RemoteUfdbCat"]==0){
			if($GLOBALS["SquidPerformance"]>1){return null;}
		}
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
		$f = @fopen("/usr/share/artica-postfix/ressources/logs/web/CATZ_GET_CATEGORIES", 'a');
		@fwrite($f, "$trace\n");
		@fclose($f);
		
		
		$keyMem=date("Ymdh");
		$orgsitename=$sitename;
		$cat=$this->GetMemoryCache($orgsitename);
		if($cat<>null){
			$this->setevents("GET_CATEGORIES $orgsitename is in cache");
			return $cat;
		}
		
		
		
		$orgsite=$sitename;
		$cat=$this->ufdbcat($sitename);
		$this->setevents("GET_CATEGORIES ufdbcat: $orgsitename = $cat");
		
		$this->SetMemoryCache($orgsitename, $cat);
		if($cat<>null){return $cat;}
	}
	
	public function GetMemoryCache($sitename,$categorize=false){
		$fill=false;
		if(count($GLOBALS["CATZ"])>65000){unset($GLOBALS["CATZ"]);}
		if(isset($GLOBALS["CATZ"][$sitename])){
			if(strlen($GLOBALS["CATZ"][$sitename])>3){return $GLOBALS["CATZ"][$sitename];}
		}
		
		$catz=$this->GET_LOCAL_DB($sitename);
		if($catz<>null){
			$GLOBALS["CATZ"][$sitename]=$catz;
			return $catz;
		}
		$catz=$this->ufdbcat($sitename,true);
		if($catz<>null){
			$GLOBALS["CATZ"][$sitename]=$catz;
			return $catz;
		}
		
		
		
		
		
		
	}
	
	public function SetMemoryCache($sitename,$category=null){
		if($category==null){return;}
		$GLOBALS["CATZ"][$sitename]=$category;
		$database=$this->CacheDatabase;
		
		if(!is_file($database)){
			try {
				$db_desttmp = @dba_open($database, "c","db4");
			}
			catch (Exception $e) {
				@dba_close($db_con);
				return;
			}
			@dba_close($db_con);
		}
		
		$db_con = @dba_open($database, "c","db4");
		if(!$db_con){return;}
		dba_replace($sitename,$category,$db_con);
		@dba_close($db_con);
	}
	
	
	
	
	public function LIST_TABLES_CATEGORIES($nocache=false){
		if(!$nocache){
			
			if(isset($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])){
				if(strlen($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])>2){
					return unserialize($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]);
				}
			}
		}
		
		$f=$this->TransArray();
		while (list ($table, $none) = each ($f) ){	
			if($this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` exists...\n";}
				$array[$table]=$table;
			}else{
				
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` no such table...\n";}
			}
		}
		
		$array=array();
		
		foreach (glob("/home/artica/categories_databases/*.db") as $filename) {
			$file=basename($filename);
			$file=str_replace(".db", "", $file);
			$array[$file]=$file;
		
		}

		
		$GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]=serialize($array);
		return $array;
		
	}	
	
	function COUNT_DATABASES(){
	
		$c=0;
		$array=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		while (list ($table, $items) = each ($array) ){
			$items=intval($items);
			if($items==0){continue;}
			$c++;
		}
	
		return $c;
	}	
	
	function COUNT_CATEGORIES(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$c=0;
		$array=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		if(!is_array($array)){return 0;}
		unset($array["TIME"]);
		while (list ($table, $items) = each ($array) ){
			$items=intval($items);
			$c=$c+$items;
		}
		$GLOBALS[__CLASS__.__FUNCTION__]=$c;
		return $c;
	}
	
	FUNCTION DELETE_TABLE($table){

	}		
	
	private function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
		$distanceInSeconds = round(abs($toTime - $fromTime));
		$distanceInMinutes = round($distanceInSeconds / 60);
	
		if ( $distanceInMinutes <= 1 ) {
			if ( !$showLessThanAMinute ) {
				return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
			} else {
				if ( $distanceInSeconds < 5 ) {
					return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 10 ) {
					return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 20 ) {
					return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
				}
				if ( $distanceInSeconds < 40 ) {
					return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 60 ) {
					return '{lessthanaminute}';
				}
	
				return '1 minute';
			}
		}
		if ( $distanceInMinutes < 45 ) {
			return $distanceInMinutes . ' {minutes}';
		}
		if ( $distanceInMinutes < 90 ) {
			return '{abouttime} 1 {hour}';
		}
		if ( $distanceInMinutes < 1440 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
		}
		if ( $distanceInMinutes < 2880 ) {
			return '1 {day}';
		}
		if ( $distanceInMinutes < 43200 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
		}
		if ( $distanceInMinutes < 86400 ) {
			return '{abouttime} 1 {month}';
		}
		if ( $distanceInMinutes < 525600 ) {
			return round(floatval($distanceInMinutes) / 43200) . ' {months}';
		}
		if ( $distanceInMinutes < 1051199 ) {
			return '{abouttime} 1 {year}';
		}
	
		return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
		 
	}	

function TransArray(){
		
			$trans["category_society"]="society";
			$trans["category_association"]="associations";
			$trans["category_publicite"]="publicite";
			$trans["category_phishtank"]="phishtank";
			$trans["category_shopping"]="shopping";
			$trans["category_abortion"]="abortion";
			$trans["category_agressive"]="agressive";
			$trans["category_alcohol"]="alcohol";
			$trans["category_animals"]="animals";
			$trans["category_associations"]="associations";
			$trans["category_astrology"]="astrology";
			$trans["category_audio_video"]="audio-video";
			$trans["category_automobile_bikes"]="automobile/bikes";
			$trans["category_automobile_boats"]="automobile/boats";
			$trans["category_automobile_carpool"]="automobile/carpool";
			$trans["category_automobile_cars"]="automobile/cars";
			$trans["category_automobile_planes"]="automobile/planes";
			$trans["category_bicycle"]="bicycle";
			$trans["category_blog"]="blog";
			$trans["category_books"]="books";
			$trans["category_browsersplugins"]="browsersplugins";
			$trans["category_celebrity"]="celebrity";
			$trans["category_chat"]="chat";
			$trans["category_children"]="children";
			$trans["category_cleaning"]="cleaning";
			$trans["category_clothing"]="clothing";
			$trans["category_converters"]="converters";
			$trans["category_cosmetics"]="cosmetics";
			$trans["category_culture"]="culture";
			$trans["category_dangerous_material"]="dangerous_material";
			$trans["category_dating"]="dating";
			$trans["category_dictionaries"]="dictionaries";
			$trans["category_downloads"]="downloads";
			$trans["category_drugs"]="drugs";
			$trans["category_dynamic"]="dynamic";
			$trans["category_electricalapps"]="electricalapps";
			$trans["category_electronichouse"]="electronichouse";
			$trans["category_filehosting"]="filehosting";
			$trans["category_finance_banking"]="finance/banking";
			$trans["category_finance_insurance"]="finance/insurance";
			$trans["category_finance_moneylending"]="finance/moneylending";
			$trans["category_finance_other"]="finance/other";
			$trans["category_finance_realestate"]="finance/realestate";
			$trans["category_financial"]="financial";
			$trans["category_forums"]="forums";
			$trans["category_gamble"]="gamble";
			$trans["category_games"]="games";
			$trans["category_genealogy"]="genealogy";
			$trans["category_gifts"]="gifts";
			$trans["category_governements"]="governments";
			$trans["category_governments"]="governments";
			$trans["category_green"]="green";
			$trans["category_hacking"]="hacking";
			$trans["category_handicap"]="handicap";
			$trans["category_health"]="health";
			$trans["category_hobby_arts"]="hobby/arts";
			$trans["category_hobby_cooking"]="hobby/cooking";
			$trans["category_hobby_other"]="hobby/other";
			$trans["category_hobby_pets"]="hobby/pets";
			$trans["category_paytosurf"]="paytosurf";
			$trans["category_terrorism"]="terrorism";
			$trans["category_hobby_fishing"]="hobby/fishing";
			$trans["category_hospitals"]="hospitals";
			$trans["category_houseads"]="houseads";
			$trans["category_housing_accessories"]="housing/accessories";
			$trans["category_housing_doityourself"]="housing/doityourself";
			$trans["category_housing_builders"]="housing/builders";
			$trans["category_housing_reale_state"]="housing/reale_state";
			$trans["category_humanitarian"]="humanitarian";
			$trans["category_imagehosting"]="imagehosting";
			$trans["category_industry"]="industry";
			$trans["category_internal"]="internal";
			$trans["category_isp"]="isp";
			$trans["category_smalladds"]="smalladds";
			$trans["category_stockexchnage"]="stockexchange";
			$trans["category_jobsearch"]="jobsearch";
			$trans["category_jobtraining"]="jobtraining";
			$trans["category_justice"]="justice";
			$trans["category_learning"]="learning";
			$trans["category_liste_bu"]="liste_bu";
			$trans["category_luxury"]="luxury";
			$trans["category_mailing"]="mailing";
			$trans["category_malware"]="malware";
			$trans["category_manga"]="manga";
			$trans["category_maps"]="maps";
			$trans["category_marketingware"]="marketingware";
			$trans["category_medical"]="medical";
			$trans["category_mixed_adult"]="mixed_adult";
			$trans["category_mobile_phone"]="mobile-phone";
			$trans["category_models"]="models";
			$trans["category_movies"]="movies";
			$trans["category_music"]="music";
			$trans["category_nature"]="nature";
			$trans["category_news"]="news";
			
			$trans["category_passwords"]="passwords";
			$trans["category_phishing"]="phishing";
			$trans["category_photo"]="photo";
			$trans["category_pictures"]="pictures";
			$trans["category_pictureslib"]="pictureslib";
			$trans["category_politic"]="politic";
			$trans["category_porn"]="porn";
			$trans["category_press"]="news";
			$trans["category_proxy"]="proxy";
			$trans["category_publicite"]="publicite";
			$trans["category_reaffected"]="reaffected";
			$trans["category_recreation_humor"]="recreation/humor";
			$trans["category_recreation_nightout"]="recreation/nightout";
			$trans["category_recreation_schools"]="recreation/schools";
			$trans["category_recreation_sports"]="recreation/sports";
			$array["category_getmarried"]="getmarried";
			$array["category_police"]="police";
			$trans["category_recreation_travel"]="recreation/travel";
			$trans["category_recreation_wellness"]="recreation/wellness";
			$trans["category_redirector"]="redirector";
			$trans["category_religion"]="religion";
			$trans["category_remote_control"]="remote-control";
			$trans["category_ringtones"]="ringtones";
			$trans["category_sciences"]="sciences";
			$trans["category_science_astronomy"]="science/astronomy";
			$trans["category_science_computing"]="science/computing";
			$trans["category_science_weather"]="science/weather";
			$trans["category_science_chemistry"]="science/chemistry";
			$trans["category_searchengines"]="searchengines";
			$trans["category_sect"]="sect";
			$trans["category_sexual_education"]="sexual_education";
			$trans["category_sex_lingerie"]="sex/lingerie";
			$trans["category_smallads"]="smallads";
			$trans["category_socialnet"]="socialnet";
			$trans["category_spyware"]="spyware";
			$trans["category_sslsites"]="sslsites";
			$trans["category_stockexchange"]="stockexchange";
			$trans["category_strict_redirector"]="strict_redirector";
			$trans["category_strong_redirector"]="strong_redirector";
			$trans["category_suspicious"]="suspicious";
			$trans["category_teens"]="teens";
			$trans["category_tobacco"]="tobacco";
			$trans["category_tracker"]="tracker";
			$trans["category_translators"]="translators";
			$trans["category_transport"]="transport";
			$trans["category_tricheur"]="tricheur";
			$trans["category_updatesites"]="updatesites";
			$trans["category_violence"]="violence";
			$trans["category_warez"]="warez";
			$trans["category_weapons"]="weapons";
			$trans["category_webapps"]="webapps";
			$trans["category_webmail"]="webmail";
			$trans["category_webphone"]="webphone";
			$trans["category_webplugins"]="webplugins";
			$trans["category_webradio"]="webradio";
			$trans["category_webtv"]="webtv";
			$trans["category_wine"]="wine";
			$trans["category_womanbrand"]="womanbrand";	
			$trans["category_horses"]="horses";	
			$trans["category_meetings"]="meetings";	
			$trans["category_tattooing"]="tattooing";	
			$trans["category_advertising"]="publicite";	
			$trans["category_getmarried"]="getmarried";	
			$trans["category_literature"]="literature";
			$trans["category_police"]="police";
			$trans["category_search"]="searchengines";
			
			return $trans;		
		
	}	
	
	
	
	
	function CreateCategoryTable($category,$fulltablename=null){
	return true;
		
	}	
	
}


	

	
	

