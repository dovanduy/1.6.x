<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
if(function_exists("debug_mem")){debug_mem();}

		
class mysql_zarafadb{
	private 	$mysql_admin="root";
	private 	$mysql_password=null;
	private  	$mysql_port;
	public  	$mysql_error;
	private  	$mysql_errornum=0;
	private  	$hostname;
	public 		$ok=false;
	private  	$CurrentPageName;
	private  	$mysql_connection;
	public 		$last_id;
	private  	$UseMysql=true;
	public 		$affected_rows=0;
	private  	$TestIngConnectionPerformed=false;
	private  	$SocketPath="/var/run/mysqld/zarafa-db.sock";
	private 	$database="zarafa";
	
	
	function mysql_zarafadb(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	  	$this->CurrentPageName=$phpPage;

		}
	
	
	
	function TestingConnection($noretry=false){
		$this->TestIngConnectionPerformed=true;
		$bd=false;
		$SockPath="/var/run/mysqld/mysqld.sock";
		if($this->SocketPath<>null){$SockPath=$this->SocketPath;$this->mysql_server=="localhost";}
		
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		
		if($this->SocketPath<>null){
			ini_set("mysql.default_password", null);
			$bd=@mysql_connect(":$SockPath",$this->mysql_admin,$this->mysql_password);
			if($bd){return true;}
			if($GLOBALS["VERBOSE"]){echo "mysql_connect(\":$SockPath\",$this->mysql_admin,..failed\n";}
		}
		
		return false;
		
	}
	
	function writelogs($text=null,$function=null,$line=0){
			$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.zarafa.debug";
			@mkdir(dirname($file_source));
			if(!is_numeric($line)){$line=0;}
			if(function_exists("writelogs")){
				writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
			}
			if(!$GLOBALS["VERBOSE"]){return;}
		    $logFile=$file_source;
			if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
			if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
			$f = @fopen($logFile, 'a');
			$date=date("Y-m-d H:i:s");
			@fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
			@fclose($f);
		}

	FUNCTION TABLE_STATUS($table){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$this->database));		
	}
	
	
	function DATABASE_STATUS(){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		$results=$this->QUERY_SQL("SHOW TABLE STATUS",$this->database);
		$html="<table style='width:100%;margin:5px;border:1px solid #CCCCCC'>
		<tr>
				<td colspan=4 align='left' style='background-color:#CCCCCC'><strong style='font-size:14px'>$database</strong></td>
			</tr>";
		while ($row = mysql_fetch_array($results)) { 
 			$dbsize += $row['Data_length'] + $row['Index_length']; 
 			$html=$html."<tr>
 			<td width=1%><img src='img/fw_bold.gif'></td>
 			<td><strong>{$row["Name"]}</strong></td>
 			<td><strong>{$row["Rows"]}</strong></td>
 			<td><strong>" . FormatBytes($row["Data_length"]/1024)."</td>
 			</tr>";
			
			} 
			
			$html=$html . "
			<tr>
				<td colspan=4 align='right' style='background-color:#CCCCCC'><strong style='font-size:13px'>".FormatBytes($dbsize/1024)."</strong></td>
			</tr>
			
			</table>";
		return $html;
	}
	
	function REPAIR_TABLES(){
		$this->BD_CONNECT();
		$mysql_result = mysql_query("SHOW TABLES;",$this->mysql_connection);
 		while ($ligne = mysql_fetch_row($mysql_result)){
 			$table_name=$ligne[0];
 			echo "<div><code>Repair $database/$table_name</code></div>";
 			$this->REPAIR_TABLE($this->database,$table_name);
     	}
	}
	
	public function ReadBinaryFile($filepath){
		$filesize=filesize($filepath);
		$fileHandle = fopen($filepath, "r");
		$fileContent = fread($fileHandle, $filesize);
		$fileContent = addslashes($fileContent);
		return $fileContent;
		}
		
		
		
	public function test_mysql_connection(){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		
		return $this->BD_CONNECT();
	}
	
	public function EXECUTE_SQL($sql){
		@mysql_close();
		$this->BD_CONNECT();
		@mysql_unbuffered_query($sql,$this->mysql_connection);
		$errnum=@mysql_errno($this->mysql_connection);
		if($errnum<>0){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error query to $MysqlServerLog with username $this->mysql_admin Number ($errnum) ($des)";
				writelogs("Error query to $MysqlServerLog with username $this->mysql_admin Number `$errnum`, $this->mysql_error pass:".strlen($this->mysql_password)." length",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;				
		}
		
		@mysql_close($this->mysql_connection);		
		return true;
	}
	
	
	public function SET_NAMES_UTF8(){
		$this->BD_CONNECT();
		@mysql_query("SET NAMES UTF8",$this->mysql_connection);
		
		
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;
		}
		
		return true;
		
	}
	
	
	function MakeConnection($database){
		
		$users=new usersMenus();
				if(!$this->UseMysql){
					$q=new lib_sqlite();
					$a=$q->QUERY_SQL($sql,$database);
					$this->mysql_error=$q->sql_error;
					$this->ok=$q->ok;
					return $a;
				}
		
		
		$database=trim($database);
		$this->ok=true;
		if(!function_exists('mysql_connect')){
			$this->mysql_error="mysql_connect() function does not exists";
			$this->ok=false;
			return null;
		}
		
		$this->BD_CONNECT();
		
		if($database<>null){
			$ok=@mysql_select_db($database,$this->mysql_connection);
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error($this->mysql_connection);
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
				$page=$this->CurrentPageName;
				if($page=='obm.export.php' OR $page=="exec.obm.synchro.php"){
					echo "Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:". strlen($this->mysql_password)." password length\n";
					echo "Database selected=$database\n";
				}
	    		
	    		$this->writeLogs("MakeConnection:: DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:$this->mysql_password",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="MakeConnection:: mysql_installed={$_GLOBAL["mysql_installed"]}; [UseMysql:$this->UseMysql]: Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:". strlen($this->mysql_password)." password lenght";
	    		
	    		$this->ok=false;
	    		return false;
    		}
		}

		return true;
		
		
	}
	
	private function SendErrorOutput($text,$die=false){
		if($GLOBALS["AS_ROOT"]){return;}
		$currentpage=CurrentPageName();
		$NOOUT["logon.php"]=true;
		$NOOUT["admin.index.php"]=true;
		$NOOUT["admin.top.menus.php"]=true;
		$NOOUT["quicklinks.php"]=true;
		$NOOUT["admin.tabs.php"]=true;
		$NOOUT["admin.index.loadvg.php"]=true;
		$NOOUT["admin.index.status-infos.php"]=true;
		$NOOUT["system.internal.disks.php"]=true;
		$NOOUT["system.mysql.php"]=true;
		$NOOUT["admin.left.php"]=true;
		$NOOUT["Inotify.php"]=true;
		$NOOUT["SambaBrowse.php"]=true;
		if(isset($NOOUT[$currentpage])){return;}
		echo $text;
		if($die){die();}
		
	}
	
	public function BD_CONNECT($noretry=false){
		
		
		if($this->SocketPath<>null){$SocketPath=$this->SocketPath;}
		$this->ok=true;
		if(!function_exists('mysql_connect')){$this->mysql_error="mysql_connect() function does not exists";$this->ok=false;return false;}
		if(($this->mysql_admin==null) && ($this->mysql_password==null)){$this->mysql_admin="root";}
		

		if($this->SocketPath<>null){
			ini_set("mysql.default_password", null);
			$bd=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
			if($bd){
				$this->mysql_connection=$bd;		
				return true;				
			}
		}
		
		
		if(!$bd){
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs(__LINE__."::CONNECT TO DATABASE: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
			$des=@mysql_error($bd);
			$errnum=@mysql_errno($bd);				
    		$this->mysql_error=$des;
			if($GLOBALS["VERBOSE"]){echo "mysql_connect failed ($errnum) \"$des\" $called";}
    		$this->writeLogs(__LINE__."::CONNECT TO DATABASE: Error Number ($errnum) ($des) config:$serverLog@$this->mysql_admin:$this->mysql_password $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );
			return false;
		}		
			
		$this->mysql_connection=$bd;		
		return true;
	}
	
	
	
	function QUERY_SQL($sql){
		$showMysqlQuery=null;
		$database=trim($this->database);
		
		if(!$this->BD_CONNECT()){
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=" Error, unable to connect to MySQL server";
			return false;
		}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
		

		
			$ok=@mysql_select_db($database,$this->mysql_connection);
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error($this->mysql_connection);
	    		$this->mysql_errornum=$errnum;
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
	   			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	    		$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="QUERY_SQL:: mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
	    		$this->ok=false;
	    		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("DB:\"$database\" $this->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
	    		return null;
    		}
		
			$results=@mysql_query($sql,$this->mysql_connection);
		
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if(preg_match("#Got error 134 from storage engine#i", $des)){
				if(preg_match("\s+from\s+(.*?)\s+#i", $sql,$re)){
					$table=$re[1];
					if($this->TABLE_EXISTS($table, $database)){
						$this->writeLogs("QUERY_SQL:: DB:\"$database\" try to repair table \"$table\"",__CLASS__.'/'.__FUNCTION__,__LINE__);
						mysql_query("REPAIR TABLE $table QUICK",$this->mysql_connection);
						$results=mysql_query($sql,$this->mysql_connection);
					}
				}
			}
			if($errnum==1548){
				$sock=new sockets();
				$sock->getFrameWork("mysql.php?mysql-upgrade=yes&instance-id=$this->instance_id");
				$this->mysql_error=$this->mysql_error."\nArtica as performed a mysql_upgrade, please retry later\n";
				if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("DB:\"$database\" $this->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			}
			
			
			
		}
		
		
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			@mysql_free_result($this->mysql_connection);
			if($des<>null){
				if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}}
				
			}
		
	
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				@mysql_free_result($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}}
				if($GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."\n";}}
							
				if(function_exists("debug_backtrace")){
						try {
							$trace=@debug_backtrace();
							if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
						} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					}
				
				$this->mysql_error="Error $des $called line ".__LINE__;
				if(preg_match("#Column count doesn.*?match value count#i", $des)){$showMysqlQuery="\n$sql";}
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=$this->trace_to_string($trace[1]);}}				
				$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error $des $called \"$sql\" line:".__LINE__." of class.mysql.inc",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
				if($errnum<>1062){
					if(function_exists("system_admin_events")){
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\n".$this->trace_to_string($trace[1])."\n";}
						system_admin_events("DB:\"$database\" $this->mysql_error$called$showMysqlQuery" , 
						__FUNCTION__, __FILE__, __LINE__, "mysql-error");
					}
				}
				$this->affected_rows=@mysql_affected_rows($this->mysql_connection);
				
				if($errnum==1062){
					$this->ok=true;
					@mysql_free_result($this->mysql_connection);
					@mysql_close($this->mysql_connection);
					return true;
				}				
				
				$this->ok=false;
				@mysql_free_result($this->mysql_connection);
				@mysql_close($this->mysql_connection);
				return null;
			}
		}	
		
		$this->last_id=@mysql_insert_id($this->mysql_connection);	
		$this->ok=true;
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		return $result_return;
	}
	
	function trace_to_string($array){
		while (list ($num, $ligne) = each ($array) ){
			try {
				if(is_object($ligne)){continue;}
				if(strpos("  $ligne", '/')>0){$ligne=basename($ligne);}
				if(is_array($ligne)){$r=@implode(" ", $ligne);unset($ligne);$ligne=$r;}
			} catch (Exception $e) {
			}
			
			$f[]="$num: $ligne";
		}
		return @implode("\n",$f);
	}
	
	function _mysql_close(){
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
	}
	
	
function myisamchk($db,$table){
	
	$keytask=md5(__FUNCTION__.__CLASS__.$db.$table);
	
	if(isset($GLOBALS[$keytask])){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::Already done";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: Already done...\n";}return;}
		
	$GLOBALS[$keytask]=true;		
		
		
	if($db==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::DB is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: db $db is null, aborting\n";}
		return;
	}
	
	if($table==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::table is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: table is null, aborting\n";}
		return;
	}
		
	$GLOBALS[md5(__FUNCTION__.__CLASS__.$db.$table)]=true;

	
	$sock=new sockets();
	$this->mysql_error=$this->mysql_error.base64_decode($sock->getFrameWork("cmd.php?myisamchk=yes&database=$db&table=$table"));
	
}	
	
	function REPAIR_TABLE($database,$table){
		if(!$GLOBALS["AS_ROOT"]){
			echo "<li>REPAIR_TABLE: DB::$database, Table:$table</li>";
		}
		$this->writeLogs("Try to repair $table in database $database",__CLASS__.'/'.__FUNCTION__,__LINE__);
		$sock=new sockets();
		$results=explode("\n", base64_decode($sock->getFrameWork("cmd.php?myisamchk=yes&table=$table&database=$database&MyCURLTIMEOUT=120")));
		if(!$GLOBALS["AS_ROOT"]){
			echo "<li>REPAIR_TABLE: myisamchk results:".@implode("<br>\n", $results)."</li>";
		}		
		
		$sqlerror[]=$this->mysql_error."myisamchk results:".@implode("<br>\n", $results);
		$this->writeLogs("cmd.php?myisamchk=yes&table=$table&database=$database",__CLASS__.'/'.__FUNCTION__,__LINE__);
		
		$sql="REPAIR TABLE $table use_frm";
		$sqlerror[]="REPAIR TABLE $table use_frm<br>\n";
		$this->BD_CONNECT();
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		
		
		$sql="REPAIR TABLE $table EXTENDED";
		$sqlerror[]="REPAIR TABLE $table EXTENDED";
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
	
	
		$sqlerror[]="myisamchk...";
		$sock->getFrameWork("cmd.php?myisamchk=yes&table=$table&database=$database&nohup=yes");
		
		$sqlerror[]="TABLE_STATUS...";
		$ligne=$this->TABLE_STATUS($table,$database);
		$failed=false;
		$sqlerror[]="TABLE_STATUS:Comment=`{$ligne["Comment"]}`..";
		$this->writeLogs("REPAIR_TABLE: TABLE_STATUS -> `{$ligne["Comment"]}`",__CLASS__.'/'.__FUNCTION__,__LINE__);
		if(preg_match("#is marked as crashed#",$ligne["Comment"])){$failed=true;}
		if(preg_match("#Incorrect file format#",$ligne["Comment"])){$failed=true;}
		
		if($failed){
			$this->writeLogs("REPAIR_TABLE:$table failed -> Try other thing... ",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$sock=new sockets();
			$sqlerror[]="Lauching system repair for table $table in $database mysql-myd-file=yes";
			if(function_exists("system_admin_events")){system_admin_events("Lauching system repair for table $table in $database", __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$results=trim($sock->getFrameWork("cmd.php?mysql-myd-file=yes&table=$table&database=$database"));
			if(function_exists("system_admin_events")){system_admin_events("repair for $database.$table\nResult:=".$results, __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE FAILED, checking MYD: \"$results\"";
			$this->writeLogs("REPAIR_TABLE FAILED, checking MYD: \"$results\"",__CLASS__.'/'.__FUNCTION__,__LINE__);
			if($results=="NO"){
				$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: Failed -> REPAIR_TABLE_FAILED($database,$table)";
				$this->REPAIR_TABLE_FAILED($database,$table);			
			}
		}
		$sqlerror[]="Should success...in line ". __LINE__." ,class:".__CLASS__;
		$sqlerror[]="Optimize table...";
		$sql="OPTIMIZE TABLE $table";
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		$this->mysql_error=@implode("<br>\n", $sqlerror);
		$GLOBALS["REPAIR_MYSQL_TABLE"]=$sqlerror;
		$sock->getFrameWork("cmd.php?mysql-check=yes&database=$database&table=$table&instance-id=0");
	}
	
	private function REPAIR_TABLE_FAILED($database,$table){
		
		

		$this->BuildTables();
			if(function_exists("send_email_events")){
				$subject="Table $table in database $database definitively crashed (repaired)";
				$text[]="The Mysql table $table was crashed and the file $table.MYD was not stored on the disk";
				$text[]="In this case, datas was not recoverable.";
				$text[]="Artica has deleted the table and rebuild it.";
				$text[]="But no datas can be retrieved.";
				$text[]="If you want to recover datas, use your backup.";
				send_email_events($subject,@implode("\n",$text),"system");
			}
			if(function_exists("system_admin_events")){
				system_admin_events($subject."\n". @implode("\n",$text)."\n".@implode("\n", $GLOBALS["REPAIR_MYSQL_TABLE"]), __FUNCTION__, __FILE__, __LINE__, "mysql-error");
			}
			
		}
	
	
	function DELETE_USER_INMYSQL($user){
		$this->BD_CONNECT();
		if($user==$this->mysql_admin){return;}
		if(!$this->mysql_connection){return;}
	    $results=@mysql_query("DROP USER '$user'@'localhost';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
		$results=@mysql_query("DROP USER '$user'@'127.0.0.1';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}

		$results=@mysql_query("DROP USER '$user'@'%';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
	}


	
	function TABLE_EXISTS($table){
		
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->writeLogs("Database $this->database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			if(!$this->CREATE_DATABASE($this->database)){
				$this->writeLogs("Unable to create $this->database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql);
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$this->database"])){return true;}
		}
		
		return false;
		
	}
	
	
	function DATABASE_EXISTS($database,$nocache=false){
		if(!$nocache){
			if(isset($_SESSION["MYSQL_DATABASE_EXISTS"][$database])){
			if($_SESSION["MYSQL_DATABASE_EXISTS"][$database]==true){return true;}
			}
		}
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION["MYSQL_DATABASE_EXISTS"][$database]=true;
				return true;
			}
		}
		
		return false;
		
	}
	
	
	function PRIVILEGES_DELETE($database){
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed  \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}	
		return true;
	}
	
	
	function PRIVILEGES($user,$password,$database){
		$server_mysql_password=$this->mysql_password;
		$OrginalPassword=$this->mysql_password;
		$server_database=$database;
		$mysql_password=$password;
		$sql="SELECT User FROM user WHERE User='$user'";
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));	
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$server_database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $server_database\"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}
	
		if($userfound==null){
			$sql="CREATE USER '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){
					$this->mysql_admin="root";
					$this->mysql_password=null;
					$this->EXECUTE_SQL($sql);
					$this->ok=true;
					if(!$this->ok){
						echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
						$this->mysql_admin="root";
						$this->mysql_password=$server_mysql_password;
						$this->ok=true;
						$this->EXECUTE_SQL($sql);
						if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
					}
					
			}
		}
	
		$sql="CREATE USER '$user'@'localhost' IDENTIFIED BY '$mysql_password';";
		$this->EXECUTE_SQL($sql);	
		if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){
					echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
					return false;
				}			
			}
			
		}
	
	$server_database2=str_replace("_","\_",$server_database);
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'localhost' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}	
	
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	writelogs("success set privileges on $server_database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	$sql="FLUSH PRIVILEGES";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->EXECUTE_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->EXECUTE_SQL($sql,"mysql")){
					echo "$sql\n\n$q->mysql_error\n";
					return;
				}
			}			
		}			
	
		
	$this->mysql_password=$OrginalPassword;	
	return $this->check_write_privs($user,$password,$database);		
		
}

	private function check_write_privs($user,$password,$database){
		$OrginalPassword=$this->mysql_password;
		$write["Insert_priv"]=true;
		$write["Update_priv"]=true;
		$write["Delete_priv"]=true;
		$write["Create_priv"]=true;
		$write["Drop_priv"]=true;
		$write["Index_priv"]=true;
		$write["Alter_priv"]=true;
		$write["Create_tmp_table_priv"]=true;
		$write["Create_view_priv"]=true;
		$write["Show_view_priv"]=true;			
		$USERS=array();
		$sql="SELECT `User` FROM `db` WHERE `Db`='$database'";
		$results=$this->QUERY_SQL($sql,"mysql");
		if(@mysql_num_rows($results)>0){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$USERS[$ligne["User"]]=true;
			}
			
		}
		
		if(isset($USERS[$user])){return true;}
		while (list ($num, $none) = each ($write) ){
			$fileds[]="`$num`";
			$dd[]="'Y'";
		}
	
	
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','127.0.0.1',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','localhost',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}

	$sql="FLUSH PRIVILEGES";	
	$this->mysql_password=$OrginalPassword;
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$this->EXECUTE_SQL($sql,"mysql")){
		$this->mysql_admin="root";
		$this->mysql_password=$OrginalPassword;
		$this->ok=true;
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->ok=true;	
			if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}			
		}

		return true;
	}

	public function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}
		
	function SET_VARIABLES($key,$value){
		$this->BD_CONNECT();
		$results=@mysql_query("SET GLOBAL $key = $value",$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		}	

		
	function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
		}		

		
	function DATABASE_LIST(){
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=$this->DATABASE_INFOS($Database);
			}
			return $array;
		}
		
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;

		}		
		
		
	function TABLES_LIST(){
		$sql="SHOW TABLE STATUS";
		$results=$this->QUERY_SQL($sql);
		$array=array();
		if(!$this->ok){return array();}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize= $ligne['Data_length'] + $ligne['Index_length']; 
			$dbsize_text=ParseBytes($dbsize);
			$Name=$ligne["Name"];
			$Rows=$ligne["Rows"];
			$Max_data_length=$ligne["Max_data_length"];
			$array[$Name]=array($dbsize,$dbsize_text,$Rows,$Max_data_length);
			
		}
		
		return $array;
		
	}
	
	
	private function DATABASE_INFOS($database){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length']; 
			$count=$count+1;}
		return array($count,ParseBytes($dbsize));
	}
	
	
	FUNCTION CREATE_DATABASE($database,$nocache=false){
		$this->mysql_password=trim($this->mysql_password);
		if(!$nocache){
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		if($this->DATABASE_EXISTS($database)){
			writelogs("CREATE_DATABASE $database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}}
  		$results=@mysql_query("CREATE DATABASE `$database`",$this->mysql_connection);
			if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connectiond);
				if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
				$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}

		$this->ok=true;
		return true;
		
	}
	
	FUNCTION DELETE_DATABASE($database){
		if(!function_exists("mysql_connect")){$this->ok=false;return 0;}
		$this->ok=true;
		$this->BD_CONNECT();
		
		
		
		$results=@mysql_query("DROP DATABASE `$database`",$this->mysql_connection);	
		
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$this->ok=false;
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error Number ($errnum) ($des)$called";
				return false;

		}
		$this->ok=true;
		return true;
		
	}
	
	FUNCTION DELETE_TABLE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$database);
		$this->QUERY_SQL("FLUSH TABLES",$database);
		unset($GLOBALS["__MYSQL_TABLE_EXISTS"]);
		unset($_SESSION["__MYSQL_TABLE_EXISTS"]);
	}	
	
	
	FUNCTION COUNT_ROWS($table,$database){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}
		
	FUNCTION TABLE_SIZE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];		
		
	}
	
	

		
	FUNCTION DATABASE_SIZE($database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="SHOW table STATUS";
		$dbsize=0;
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
		}
		$dbsize=round(($dbsize/1024),2);
		if($dbsize>1000){
			$dbsize=round($dbsize/1000,1) . " Mb";
		}else{
			$dbsize=$dbsize . " kb";
		}
		return $dbsize;
		
	}
	
	
	
	FUNCTION INDEX_EXISTS($table,$index,$database){
		
		if(isset($_SESSION["MYSQL_INDEX_EXISTS"])){
			if($_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]==true){return true;}
		}
		$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		
		
		
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	
	FUNCTION FIELD_EXISTS($table,$field,$database){
		$field=trim($field);
		if(isset($GLOBALS["__FIELD_EXISTS"])){
				if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
					if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
						if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]==true){return true;}
					}
				}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		
		if(trim($ligne["Field"])<>null){
			$GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
			return true;
		}else{
			$this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
			$this->writelogs("$sql",__FUNCTION__,__LINE__);
			return false;
		}
		
		
	}
	

	
	
	function FIELD_TYPE($table,$field,$database){
		if(isset($GLOBALS["__FIELD_TYPE"])){
			if(isset($GLOBALS["__FIELD_TYPE"][$database][$table][$field])){
				if($GLOBALS["__FIELD_TYPE"][$database][$table][$field]<>null){return $GLOBALS["__FIELD_TYPE"][$database][$table][$field];}
			}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		$GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
		return strtolower($ligne["Type"]);
	}
	
	
	FUNCTION CREATE_INDEX($table,$index,$database){
		$sql="ALTER TABLE `$table` ADD INDEX ( `$index` )";
		$this->QUERY_SQL($sql,$database);
	}
	
	
	
	
}
?>