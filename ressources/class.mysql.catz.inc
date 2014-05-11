<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.squid.familysites.inc');
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
	function mysql_catz($nomysql=false){

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

	
	function GET_CATEGORIES($sitename){
		$keyMem=date("Ymdh");
		$orgsitename=$sitename;
		$cat=$this->GetMemoryCache($orgsitename);
		if($cat<>null){return $cat;}
		
		$orgsite=$sitename;
		$this->ok=true;
		$t1=time();
		$BADCATZ["category_drogue"]=true;
		$BADCATZ["category_english_malware"]=true;
		$BADCATZ["category_forum"]=true;
		$BADCATZ["category_gambling"]=true;
		$BADCATZ["category_hobby_games"]=true;
		$BADCATZ["category_phishtank"]=true;
		$BADCATZ["category_radio"]=true;
		$BADCATZ["category_radiotv"]=true;
		$BADCATZ["category_spywmare"]=true;	
		$BADCATZ["category_search"]=true;
		$BADCATZ["category_radio"]=true;
		$BADCATZ["categoryuris_phishing"]=true;
		$BADCATZ["categoryuris_malware"]=true;
		$BADCATZ["category_association"]=true;
		
		$debug=false;
		if(isset($_POST["WEBTESTS"])){$debug=true;}
		$GLOBALS["CATEGORIZELOGS"]=array();
		if(isset($GLOBALS["GET_CATEGORIES_MEMORY"][$sitename])){return $GLOBALS["GET_CATEGORIES_MEMORY"][$sitename];}
		$cat=array();
		$cattmp=array();
		if(trim($sitename)==null){return;}
		$sitename=strtolower(trim($sitename));
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		
		if(substr($sitename, 0,1)=="."){$sitename=trim(substr($sitename, 1,strlen($sitename)));}
		
		$sitename=md5($sitename);
		if($GLOBALS["VERBOSE"]){echo "$orgsite = $sitename\n";}
		$trans=$this->TransArray();
		if(!isset($GLOBALS["TABLESCATEZ$keyMem"])){
			$GLOBALS["TABLESCATEZ$keyMem"]=$this->LIST_TABLES_CATEGORIES(true);
		}	
		$tablescat_count=count($GLOBALS["TABLESCATEZ$keyMem"]);
		
		$tablescat=$GLOBALS["TABLESCATEZ$keyMem"];
		while (list ($table, $none) = each ($tablescat) ){
			$category=null;
			if($table=="category_"){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (category_)\n";}
				continue;}
			if(isset($BADCATZ[$table])){continue;}
			if(preg_match("#bak$#", $table)){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (bak)\n";}
				continue;}
			if(isset($GLOBALS["TABLEINEXISTS"][$table])){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (TABLEINEXISTS)\n";}
				continue;}
			if(!$this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) no such table $table\n";}
				$GLOBALS["TABLEINEXISTS"][$table]=true;
				continue;
			}
			
			$t=time();
			
			if(isset($trans[$table])){ $category=$trans[$table]; }
			if($category==null){
				if($GLOBALS["VERBOSE"]){ echo "Cannot translate $table to category\n";}
				continue;
			}
			
			$id=null;
			
			$id = dba_open("/home/artica/categories_databases/$table.db", "r","db4");
			if(!$id){
				if(isset($_REQUEST["WEBTESTS"])){echo "$table.db failed\n";}
				if($GLOBALS["VERBOSE"]){echo "/home/artica/categories_databases/$table.db failed...\n";}
				writelogs("/home/artica/categories_databases/$table.db failed",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
				dba_close($id);
				continue;
			}
			
			
			if(!dba_exists($sitename,$id)){
				dba_close($id);
				continue;
			}
			
			if(dba_exists($sitename,$id)){
				$took=$this->distanceOfTimeInWords($t,time(),true); 
				if($debug){echo "ArticaDB::$orgsite: \"$category\" [".__LINE__."]\n";}
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: Found $category FOR \"$sitename\" in ". __CLASS__ ." line: ".__LINE__."\n";}
				$cattmp[$category]=$category;
				
			}
			
			dba_close($id);
			$tablescat_count++;
			
			
		
		
		
		}	

		$t2=time();
		$time=$t2-$t1;
		
		if(isset($_REQUEST["WEBTESTS"])){echo "GET_CATEGORIES:: $orgsitename Categories found ".count($cattmp)." in $tablescat_count tables for {$time}s\n";}
		
		if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES:: $orgsitename Categories found ".count($cattmp)." in $tablescat_count tables for {$time}s<br>\n";}
		if(count($cattmp)==0){return null;}
		
		
		if(count($cattmp)>0){
			while (list ($a, $b) = each ($cattmp) ){if($b<>null){$cat[]=$b;}}
		}	
		if(count($cat)==0){return null;}
		$final=@implode(",", $cat);
		$this->SetMemoryCache($orgsitename, $final);
		return $final;
		
	}
	
	private function GetMemoryCache($sitename){
		if(!isset($GLOBALS["CATZ"])){return;}
		if($GLOBALS["CATZ"]==null){return;}
		if(strlen($GLOBALS["CATZ"])<2){return;}
		$array=unserialize($GLOBALS["CATZ"]);
		if(count($array)>64000){$GLOBALS["CATZ"]=null;return;}
		if(!isset($array[$sitename])){return;}
		return $array[$sitename];
		
	}
	
	private function SetMemoryCache($sitename,$category){
		if(isset($GLOBALS["CATZ"])){
			if($GLOBALS["CATZ"]<>null){
				if(strlen($GLOBALS["CATZ"])>1){
					$array=unserialize($GLOBALS["CATZ"]);
				}
			}
		}
			
		$array[$sitename]=$category;
		$GLOBALS["CATZ"]=serialize($array);
		
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
		unset($array["TIME"]);
		while (list ($table, $items) = each ($array) ){
			$items=intval($items);
			$c=$c+$items;
		}
		$GLOBALS[__CLASS__.__FUNCTION__]=$c;
		return $c;
	}
	
	FUNCTION DELETE_TABLE($table){
		if($this->DisableArticaProxyStatistics==1){return array();}
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
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
			$trans["category_press"]="press";
			$trans["category_passwords"]="passwords";
			$trans["category_phishing"]="phishing";
			$trans["category_photo"]="photo";
			$trans["category_pictures"]="pictures";
			$trans["category_pictureslib"]="pictureslib";
			$trans["category_politic"]="politic";
			$trans["category_porn"]="porn";
			$trans["category_press"]="press";
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
			$trans["category_translator"]="translators";
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


	

	
	

