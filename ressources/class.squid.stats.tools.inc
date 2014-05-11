<?php


class squid_stats_tools{
	
	
	
	
	
	function squid_stats_tools(){
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		
	}
	
// ***************************************************************************************************************	
	// Parse toutes les tables et les ajoute dans la table principale.
	
	function check_table_days(){
		$array=$GLOBALS["Q"]->LIST_TABLES_dansguardian_events();
		while (list ($tablename,$none) = each ($array) ){
			$time=$GLOBALS["Q"]->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
			$date=date("Y-m-d",$time);
			if($GLOBALS["VERBOSE"]){echo $tablename." -> $date\n";}
			$GLOBALS["Q"]->QUERY_SQL("INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ('$tablename','$date')");
		}
	}
// ***************************************************************************************************************	
// Analyse la table principale et effectue une transformation en cas de nécéssité.
	
	function check_to_hour_tables(){
		$this->check_table_days();
		$q=new mysql_squid_builder();
		$TABLES_dansguardian_events=$GLOBALS["Q"]->LIST_TABLES_dansguardian_events();
		$q=new mysql_squid_builder();
		$t=time();
		$TABLES_HOURS=$GLOBALS["Q"]->LIST_TABLES_HOURS();
		$sql="SELECT zDate FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY);";
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		
		$numrows=mysql_num_rows($results);
		
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xtime=strtotime($ligne["zDate"]." 00:00:00");
			$tableSource="dansguardian_events_".date("Ymd",$xtime);
			$next_table=date('Ymd',$xtime)."_hour";
			
			if(!isset($TABLES_HOURS[$next_table])){
				if($GLOBALS["VERBOSE"]){echo "Analyze $xtime\n";}
				$this->dansguardian_events_to_table_hour($xtime);
				continue;
				
			}
			
			$COUNT_ROWS=$GLOBALS["Q"]->COUNT_ROWS($next_table);
			
			if(!isset($TABLES_dansguardian_events[$tableSource])){
				if($GLOBALS["VERBOSE"]){
					echo "{$ligne["zDate"]}\nSource: $tableSource\nNext: $next_table $tableSource -> No such table\n";
					continue;
				}
			}
			
			$dansguardian_events_rows=$GLOBALS["Q"]->COUNT_ROWS($tableSource);
			
			if($GLOBALS["VERBOSE"]){echo "{$ligne["zDate"]}\nSource: $tableSource ( $dansguardian_events_rows rows)\nNext: $next_table ( $COUNT_ROWS rows)\n";}
			
			if($COUNT_ROWS>1){
				if($GLOBALS["VERBOSE"]){echo "Not necessary to scan\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "Analyze $xtime\n";}
			$this->dansguardian_events_to_table_hour($xtime);
			$this->categorize_table($next_table);
		}
		$this->not_categorized_day_scan();
		
	}
	
// ***************************************************************************************************************
	// Analyse les tables taille.
	public function check_sizehours(){
		//currenttable
		$CurrentTableSizeHours="sizehour_".date("YmdH");
		
		if($GLOBALS["VERBOSE"]){echo "Current Table: $CurrentTableSizeHours\n";}
		
		$TABLES_HOURS=$GLOBALS["Q"]->LIST_TABLES_SIZEHOURS();
		
		if($GLOBALS["VERBOSE"]){echo "TABLES_HOURS = ". count($TABLES_HOURS)." tables`\n";}
		
		while (list ($tablename,$none) = each ($TABLES_HOURS) ){
			if($tablename==$CurrentTableSizeHours){continue;}
			if($GLOBALS["VERBOSE"]){echo "->check_sizehours_parse($tablename)\n";}
			$this->check_sizehours_parse($tablename);
		}
		
	}
	
	private function check_sizehours_parse($tablename){
		if(!preg_match("#sizehour_([0-9]+)#",$tablename,$re)){if($GLOBALS["VERBOSE"]){echo "NOT AN HOUR TABLE `$tablename`\n";}return;}
		$hour=$re[1];
		$year=substr($hour,0,4);
		$month=substr($hour,4,2);
		$day=substr($hour,6,2);
		$day_table="{$year}{$month}{$day}_gsize";
		
		$sql="CREATE TABLE IF NOT EXISTS `$day_table` ( `hour` smallint(2) NOT NULL, `size` BIGINT UNSIGNED, cached smallint(1), KEY `hour` (`hour`), KEY `cached` (`cached`), KEY `size` (`size`) ) ENGINE=MYISAM;";
		$GLOBALS["Q"]->QUERY_SQL($sql,$this->database);
		if(!$GLOBALS["Q"]->ok){return;}
		$f=array();
		$sql="SELECT SUM(`size`) AS size, HOUR(`zDate`) AS thour,cached FROM `$tablename` GROUP BY thour,cached";
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){return;}
		
		$pref="INSERT INTO `$day_table` (`hour`,`size`,`cached`) VALUES ";
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]="('{$ligne["thour"]}','{$ligne["size"]}','{$ligne["cached"]}')";
			
		}
		
		if(count($f)>0){
			$GLOBALS["Q"]->QUERY_SQL($pref.@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){return;}
		}
		
		$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablename`");
	}
	
	
// Analyse les tables heures, et les catégorises.
	
	public function not_categorized_day_scan(){
		$TABLES_HOURS=$GLOBALS["Q"]->LIST_TABLES_HOURS();
		while (list ($tablename,$none) = each ($TABLES_HOURS) ){
			$this->categorize_table($tablename);
		}
		
		$this->categorize_table_resync();
		
	}
	
	
	private function categorize_table($tablename){
		
		$sql="SELECT category,sitename FROM $tablename GROUP BY category,sitename HAVING LENGTH(category)=0";
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){return;}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$sitename=$ligne["sitename"];
			if($sitename==null){continue;}
			if(!isset($GLOBALS["MEMORYSITES"][$sitename])){
				$category=$GLOBALS["Q"]->GET_CATEGORIES($sitename);
				$GLOBALS["MEMORYSITES"][$sitename]=$category;
			}else{
				$category=$GLOBALS["MEMORYSITES"][$sitename];
			}
			if(trim($category)==null){continue;}
			$sitename=mysql_escape_string2($sitename);
			$category=mysql_escape_string2($category);
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET `category`='$category' WHERE `sitename`='$sitename'");
			
		}
		
		
		
		$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
		
		if(!$GLOBALS["Q"]->ok){if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}return;}		
		
		$NotCategorized=mysql_num_rows($results2);
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized=$NotCategorized WHERE tablename='$tablename'");
		
	}
	
	private function categorize_table_resync(){
		$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `tprefix`, DATE_FORMAT(zDate,'%Y%u') AS `tprefixW`, `tablename`  FROM tables_day WHERE `not_categorized`>0 ORDER BY zDate DESC";
		if($GLOBALS["VERBOSE"]){echo "not_categorized_day_resync\n";}
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}return;}
		$num_rows = mysql_num_rows($results);
		
		if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No item\n";}return;}
		
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$tablename=$ligne["tablename"];
				$table_day="{$ligne["tprefix"]}_hour";
				$table_week="{$ligne["tprefixW"]}_week";
		
				if($GLOBALS["Q"]->TABLE_EXISTS("$table_week")){$WEEKZ[$table_week]=true;}
		
				if(!$GLOBALS["Q"]->TABLE_EXISTS("$table_day")){
					if($GLOBALS["VERBOSE"]){echo "$table_day no such table\n";}
					$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized=0 WHERE tablename='$tablename'");
					continue;
				}
				
				
				$sql="SELECT sitename,category FROM $table_day GROUP BY sitename,category HAVING LENGTH(category)=0 ";
				$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
		
				if(!$GLOBALS["Q"]->ok){if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
				$NotCategorized=mysql_num_rows($results2);
				if($GLOBALS["VERBOSE"]){echo "$table_day $NotCategorized Websites...\n";}
				$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized=$NotCategorized WHERE tablename='$tablename'");
			}		
		
	}
	
	
// ***************************************************************************************************************	
	// transforme le table dansguardian_events vers une hour_xxxx
	public function dansguardian_events_to_table_hour($xtime){
		if(!is_numeric($xtime)){
			writelogs_repair($xtime,100,"No timestamp set");
			return;
		}
		$tableSource="dansguardian_events_".date("Ymd",$xtime);
		$dayText=date("{l} {F} d Y",$xtime);
		
		$this->resetlogs($xtime);
		$this->writelogs_repair($xtime,15,"Processing timestamp $xtime `$dayText`");
		$this->writelogs_repair($xtime,16,"Table source `$tableSource` for $dayText");
		stats_admin_events(2, "`$tableSource` for $dayText", "",__FILE__,__LINE__);
		
		
		$q=new mysql_squid_builder();
		
		
		if(!$q->TABLE_EXISTS($tableSource)){
			$q->Check_dansguardian_events_table("dansguardian_events_".date('Ymd',$xtime));
		}
		
		if(!$q->TABLE_EXISTS($tableSource)){
			stats_admin_events(1, "Table source `$tableSource` does not exists", "",__FILE__,__LINE__);
			$this->writelogs_repair($xtime,100,"Table source `$tableSource` does not exists");
			return;
		}		
	
		$next_table=date('Ymd',$xtime)."_hour";
		$this->writelogs_repair($xtime,20,"Destination table: $next_table");
		$this->dansguardian_events_to_table_hour_internal($tableSource,$next_table,$xtime);
		$this->writelogs_repair($xtime,100,"Done...");
	
	
	}
	
	private function resetlogs($xtime){
		$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$xtime";
		@file_put_contents($filelogs, serialize(array()));
	}
	
	private function dansguardian_events_to_table_hour_internal($tabledata,$nexttable,$xtime){
	$filter_hour=null;
	$filter_hour_1=null;
	$filter_hour_2=null;
	if(isset($GLOBALS["$tabledata$nexttable"])){if($GLOBALS["VERBOSE"]){echo "$tabledata -> $nexttable already executed, return true\n";}return true;}
	
	

	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("$nexttable")){
		$this->writelogs_repair($xtime,29,"Removing table `$nexttable` ".__LINE__);
		$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$nexttable`");
	}
	$GLOBALS["$tabledata$nexttable"]=true;
	$GLOBALS["Q"]->QUERY_SQL("CREATE TABLE IF NOT EXISTS `$nexttable` (`zMD5` CHAR(32) NOT NULL, `sitename` varchar(128) NOT NULL,
   `familysite` varchar(128) NOT NULL,
	 `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` INT UNSIGNED NOT NULL,
			  `hour` int(2) NOT NULL,
			  `remote_ip` varchar(50) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `country` varchar(50) NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `country` (`country`),
			  KEY `hour` (`hour`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;");
	$todaytable=date('Ymd')."_hour";
	$CloseTable=true;
	$output_rows=false;
	

	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS HOUR , CLIENT, Country, uid, sitename,MAC,hostname,account
	FROM $tabledata GROUP BY cached, HOUR( zDate ) , CLIENT, Country, uid, sitename,MAC,hostname,account HAVING QuerySize>0";

	$timeStart=time();
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows=mysql_num_rows($results);
	$disantce=$this->distanceOfTimeInWords($timeStart,time(),true);
	$this->writelogs_repair($xtime,30,"Processing $tabledata -> $num_rows rows, Query took: $disantce  in line ".__LINE__);
	if($num_rows<10){$output_rows=true;}

	if($num_rows==0){
		$this->writelogs_repair($xtime,90,"Processing $tabledata -> No row".__LINE__);
		$sql="UPDATE tables_day SET Hour=1 WHERE tablename='$tabledata'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		return true;
	}

	$prefix="INSERT IGNORE INTO $nexttable (zMD5,sitename,client,hour,remote_ip,country,size,hits,uid,category,cached,familysite,MAC,hostname,account) VALUES ";
	$prefix_visited="INSERT IGNORE INTO visited_sites (sitename,category,country,familysite) VALUES ";
	$f=array();
	$c=0;
	$TotalRows=0;
	$timeStart=time();
	
	$mysql_num_rows=mysql_num_rows($results);
	stats_admin_events(2, "Processing $tabledata $mysql_num_rows row(s)", "",__FILE__,__LINE__);
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		
		$sitename=addslashes(trim(strtolower($ligne["sitename"])));
		$client=addslashes(trim(strtolower($ligne["CLIENT"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
		$Country=addslashes(trim(strtolower($ligne["Country"])));
		$category=null;

		$familysite=$GLOBALS["Q"]->GetFamilySites($sitename);
		$ligne["Country"]=mysql_escape_string2($ligne["Country"]);
		$SQLSITESVS[]="('$sitename','$category','{$ligne["Country"]}','$familysite')";



		$md5=md5("{$ligne["sitename"]}{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["MAC"]}{$ligne["Country"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}{$ligne["cached"]}{$ligne["account"]}$category$Country");
		$sql_line="('$md5','$sitename','$client','{$ligne["HOUR"]}','$client','$Country','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','$category','{$ligne["cached"]}',
		'$familysite','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
		$f[]=$sql_line;

		if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}
		
		if($c>200){
			$TotalRows=$TotalRows+$c;
			$disantce=$this->distanceOfTimeInWords($timeStart,time(),true);
			$this->writelogs_repair($xtime,80,"Processing $TotalRows/$num_rows - $disantce");
			$timeStart=time();
			$c=0;
			
		}

		if(count($f)>500){
			if(system_is_overloaded()){
				squid_admin_mysql(2, "Warning, overloaded system after $c items", "",__FILE__,__LINE__);
			}
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){$this->writelogs_repair($xtime,90,"Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}
		if(count($SQLSITESVS)>0){
			$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
			$SQLSITESVS=array();
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){$this->writelogs_repair($xtime,90,"Processing ". count($f)." rows");}
		if(!$GLOBALS["Q"]->ok){if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}}

		if(count($SQLSITESVS)>0){
			if(!$GLOBALS["Q"]->ok){$this->writelogs_repair($xtime,90,"Processing ". count($SQLSITESVS)." visited sites");}
			$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
			if(!$GLOBALS["Q"]->ok){if(!$GLOBALS["Q"]->ok){$this->writelogs_repair($xtime,90,"Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error} in line " .	__LINE__);}}
		}
	}
	
	$tootk=$this->distanceOfTimeInWords($timeStart,time(),true);
	stats_admin_events(2, "Processing table $nexttable from $tabledata done took $tootk", "",__FILE__,__LINE__);
	
	return true;
}
	
	
	
	private function writelogs_repair($xtime,$progress,$text){
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$xtime";
		$array=unserialize(@file_get_contents($filelogs));
		$array["PROGRESS"]=$progress;
		$array["TEXT"][]="$date [$pid]: $text";
		@file_put_contents($filelogs, serialize($array));
		@chmod($filelogs, 0775);
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
	
	
	
}