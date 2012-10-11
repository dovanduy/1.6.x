<?php
set_time_limit(0);
$squidBuilder=dirname(__FILE__)."/class.mysql.squid.builder.php";
if(is_file($squidBuilder)){include_once($squidBuilder);}
function QUERY_SQL($sql,$logfile=null,$notLog=0,$Database='artica_filter'){$s=new mysql();return $s->QUERY_SQL($sql,$Database);}
		
class mysql{
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $mysql_error;
	var $mysql_errornum=0;
	var $hostname;
	var $ok=false;
	var $CurrentPageName;
	var $mysql_connection;
	var $last_id;
	var $UseMysql=true;
	var $affected_rows=0;
	var $TestIngConnectionPerformed=false;
	
	function mysql(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	  	$this->CurrentPageName=$phpPage;
	  	$this->FillSettings();
	  	if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="locahost"){$this->mysql_server="127.0.0.1";}
			
		if(!$this->TestIngConnectionPerformed){$this->TestingConnection();}
		}
	
	private function FillSettings(){
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}
		
		if(isset($GLOBALS["MYSQL_PARAMETERS"])){if(trim($GLOBALS["MYSQL_PARAMETERS"]["mysql_server"])==null){unset($GLOBALS["MYSQL_PARAMETERS"]);}}
		if(isset($_SESSION["MYSQL_PARAMETERS"])){if(trim($_SESSION["MYSQL_PARAMETERS"]["mysql_server"])==null){unset($_SESSION["MYSQL_PARAMETERS"]);}}		
		if(isset($GLOBALS["MYSQL_PARAMETERS"])){if(trim($GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"])==null){unset($GLOBALS["MYSQL_PARAMETERS"]);}}
		if(isset($_SESSION["MYSQL_PARAMETERS"])){if(trim($_SESSION["MYSQL_PARAMETERS"]["mysql_admin"])==null){unset($_SESSION["MYSQL_PARAMETERS"]);}}		
				
		
		
		if(!$GLOBALS["AS_ROOT"]){
			if(isset($_SESSION["MYSQL_PARAMETERS"])){
				if($_SESSION["MYSQL_PARAMETERS"]["mysql_password"]=="!nil"){$_SESSION["MYSQL_PARAMETERS"]["mysql_password"]=null;}
				$this->mysql_server=$_SESSION["MYSQL_PARAMETERS"]["mysql_server"];
				$this->mysql_admin=$_SESSION["MYSQL_PARAMETERS"]["mysql_admin"];
				$this->mysql_password=$_SESSION["MYSQL_PARAMETERS"]["mysql_password"];
				$this->hostname=$_SESSION["MYSQL_PARAMETERS"]["hostname"];
				$this->mysql_port=$_SESSION["MYSQL_PARAMETERS"]["mysql_port"];
				$this->mysql_password=stripslashes($this->mysql_password);	
				$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
				$this->mysql_admin=trim($this->mysql_admin);
				$this->mysql_password=str_replace("\r", "", $this->mysql_password);
				$this->mysql_password=trim($this->mysql_password);				
				return;				
			}
			
		}
		
		
	if($GLOBALS["AS_ROOT"]){
		if(isset($GLOBALS["MYSQL_PARAMETERS"])){
			if(($GLOBALS["MYSQL_PARAMETERS"]["mysql_server"]<>null) && ($GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"]<>null)){
				if($GLOBALS["MYSQL_PARAMETERS"]["mysql_password"]=="!nil"){$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"]=null;}
				$this->mysql_server=$GLOBALS["MYSQL_PARAMETERS"]["mysql_server"];
				$this->mysql_admin=$GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"];
				$this->mysql_password=$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"];
				$this->mysql_port=$GLOBALS["MYSQL_PARAMETERS"]["mysql_port"];
				$this->mysql_password=stripslashes($this->mysql_password);	
				$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
				$this->mysql_admin=trim($this->mysql_admin);	
				$this->mysql_password=str_replace("\r", "", $this->mysql_password);
				$this->mysql_password=trim($this->mysql_password);												
				return;
			}	
			
		}
		
		

	$this->mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	if($this->mysql_password=="!nil"){$this->mysql_password=null;}
	$this->mysql_password=stripslashes($this->mysql_password);
	$this->mysql_admin=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
	$this->mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
	$this->mysql_port=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
	if($this->mysql_port==null){$this->mysql_port=3306;}
	if($this->mysql_server==null){$this->mysql_server="localhost";}
	$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
	$this->mysql_admin=trim($this->mysql_admin);	
	$this->mysql_password=str_replace("\r", "", $this->mysql_password);
	$this->mysql_password=trim($this->mysql_password);	
	
		if($this->mysql_admin==null){
			if($GLOBALS["VERBOSE"]){echo "Get credentials mysql_admin is null, assume root...\n";}
			$this->mysql_admin="root";
		}
	
	
	if($this->mysql_password=="!nil"){$this->mysql_password=null;}
	$GLOBALS["MYSQL_PARAMETERS"]["mysql_server"]=$this->mysql_server;
	$GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"]=$this->mysql_admin;
	$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"]=$this->mysql_password;
	$GLOBALS["MYSQL_PARAMETERS"]["mysql_port"]=$this->mysql_port;	
	return;		
		
	}		
		

		
		
		if(!isset($GLOBALS["MYSQL_PARAMETERS"])){
			$settings=dirname(__FILE__) . '/settings.inc';
			$sock=new sockets();
			if(!is_file($settings)){die("Could not stat necessary file \"$settings\" (". __FILE__.")\n");}
			include($settings);
			if(!isset($_GLOBAL["mysql_port"])){$_GLOBAL["mysql_port"]=3306;}
			$MySQLInfos=unserialize(base64_decode($sock->getFrameWork("services.php?mysqlinfos=yes")));
			$this->mysql_admin=$MySQLInfos["username"];
			$this->mysql_password=$MySQLInfos["password"];
			$this->mysql_server=$_GLOBAL["mysql_server"];
			$this->hostname=$_GLOBAL["fqdn_hostname"];		
			$this->mysql_port=$_GLOBAL["mysql_port"];
			$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
			$this->mysql_admin=trim($this->mysql_admin);		
			$this->mysql_password=str_replace("\r", "", $this->mysql_password);
			$this->mysql_password=trim($this->mysql_password);					
			if($this->mysql_server==null){$this->mysql_server="localhost";}
			if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
			if($this->mysql_port==null){$this->mysql_port=3306;}
			if($this->mysql_password=="!nil"){$this->mysql_password=null;}
			writelogs("Load mysql parameters $this->mysql_server:$this->mysql_port",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["MYSQL_PARAMETERS"]["mysql_server"]=$this->mysql_server;
			$GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"]=$this->mysql_admin;
			$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"]=$this->mysql_password;
			$GLOBALS["MYSQL_PARAMETERS"]["hostname"]=$this->hostname;
			$GLOBALS["MYSQL_PARAMETERS"]["mysql_port"]=$this->mysql_port;
			if(!$GLOBALS["AS_ROOT"]){
				$_SESSION["MYSQL_PARAMETERS"]["mysql_server"]=$this->mysql_server;
				$_SESSION["MYSQL_PARAMETERS"]["mysql_admin"]=$this->mysql_admin;
				$_SESSION["MYSQL_PARAMETERS"]["mysql_password"]=$this->mysql_password;
				$_SESSION["MYSQL_PARAMETERS"]["hostname"]=$this->hostname;
				$_SESSION["MYSQL_PARAMETERS"]["mysql_port"]=$this->mysql_port;	
			}		
		}else{
			$this->mysql_server=$GLOBALS["MYSQL_PARAMETERS"]["mysql_server"];
			$this->mysql_admin=$GLOBALS["MYSQL_PARAMETERS"]["mysql_admin"];
			$this->mysql_password=$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"];
			$this->hostname=$GLOBALS["MYSQL_PARAMETERS"]["hostname"];
			$this->mysql_port=$GLOBALS["MYSQL_PARAMETERS"]["mysql_port"];
			if($this->mysql_password=="!nil"){$this->mysql_password=null;$GLOBALS["MYSQL_PARAMETERS"]["mysql_password"]=null;}
			$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
			$this->mysql_admin=trim($this->mysql_admin);
			$this->mysql_password=str_replace("\r", "", $this->mysql_password);
			$this->mysql_password=trim($this->mysql_password);							
			
		}
	}
	
	
	function IS_ERROR_DUMP_CMDLINE($results=array()){
		while (list ($index, $line) = each ($results)){
			if(preg_match("#error:\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if(preg_match("#ERROR\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if($GLOBALS["VERBOSE"]){echo "\"$line\" Unknown....line\n";}
		}

		return false;
		
	}
	
	
	function TestingConnection($noretry=false){
		$this->TestIngConnectionPerformed=true;
		
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		
		
		
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			if($this->mysql_password<>null){
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
			}else{
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,null);
			}
		}else{
			if($this->mysql_password<>null){
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			}else{
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,null);
			}
		}
		
		
		
		if(!$bd){
			$des=mysql_error();
			$errnum=mysql_errno();
			if($errnum==1045){
				if(!$noretry){
					$this->mysql_admin="root";
					$this->mysql_password==null;
					ini_set("mysql.default_password", null);
					@mysql_close($bd);
					return $this->TestingConnection(true);
				}
			}
			
			if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		$errnum=mysql_errno();
			if($GLOBALS["VERBOSE"]){echo "mysql_connect failed ($errnum) \"$des\"\n";}
    		$this->writeLogs("Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:$this->mysql_password $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );
			return false;
		}
		
		return true;
		
	}
	
function writelogs($text=null,$function=null,$line=0){
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
		@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){writelogs("$text (L.$line)","$function",__FILE__,$line);}
		if(!$GLOBALS["VERBOSE"]){return;}
	    $logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
			if (is_file($logFile)) { 
		   			$size=filesize($logFile);
				    	if($size>1000000){unlink($logFile);}
		   		}
		
				$f = @fopen($logFile, 'a');
				$date=date("Y-m-d H:i:s");
				@fwrite($f, "$date:[$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
				@fclose($f);
			
		}

	FUNCTION TABLE_STATUS($table,$database){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$database));		
	}
	
	
	function DATABASE_STATUS($database){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		$results=$this->QUERY_SQL("SHOW TABLE STATUS",$database);
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
	
	function REPAIR_TABLES($database){
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
  	 	$mysql_result = mysql_query("SHOW TABLES;",$bd);
 		while ($ligne = mysql_fetch_row($mysql_result)){
 			$table_name=$ligne[0];
 			echo "<div><code>Repair $database/$table_name</code></div>";
 			$this->REPAIR_TABLE($database,$table_name);
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
		
		$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		if(!$bd){
			$errnum=mysql_error();
	    	$des=mysql_error();
	    	writelogs("Error $errnum, $des",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	    	$this->mysql_error="Error $errnum, $des";
	    	return false;
		}
		return true;
	}
	
	public function EXECUTE_SQL($sql){
		@mysql_close();
		if($this->mysql_password=="!nil"){$this->mysql_password=null;}
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$connect=":/var/run/mysqld/mysqld.sock";
			$MysqlServerLog=":/var/run/mysqld/mysqld.sock";
			if(strlen(trim(($this->mysql_password)))==0){
				$connect=$connect." (without password)";
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,null);
			}else{
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
			}
		}
		else{
			$connect="$this->mysql_server:$this->mysql_port";
			if(strlen(trim(($this->mysql_password)))==0){
				$MysqlServerLog="$this->mysql_server:$this->mysql_port";
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin);
			}else{
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			}
			
		}
		
		if(@mysql_errno($bd)>-1){
			if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
				$MysqlServerLog="$this->mysql_server:$this->mysql_port";
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin);
			}
			
		}
		
		if(@mysql_errno($bd)>-1){
			$errnum=@mysql_errno($bd);
			if($errnum<>0){
				$des=mysql_error($bd);
	    		$this->mysql_error="Error connecting to $MysqlServerLog with username $this->mysql_admin Number `$errnum`,$des pass:".strlen($this->mysql_password)." length\nConnect:$connect";
	    		writelogs("Error connecting to $MysqlServerLog with username $this->mysql_admin Number `$errnum`, $this->mysql_error pass:".strlen($this->mysql_password)." length",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	    		return false;
			}
		}
		@mysql_unbuffered_query($sql,$bd);
		$errnum=@mysql_errno($bd);
		if($errnum<>0){
				$time=date('h:i:s');
				$errnum=@mysql_errno($bd);
				$des=@mysql_error($bd);
				$this->mysql_error="Error query to $MysqlServerLog with username $this->mysql_admin Number ($errnum) ($des)";
				writelogs("Error query to $MysqlServerLog with username $this->mysql_admin Number `$errnum`, $this->mysql_error pass:".strlen($this->mysql_password)." length",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($bd);
				return false;				
		}
		
		@mysql_close($bd);		
		return true;
	}
	
	
	public function SET_NAMES_UTF8(){
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		@mysql_query("SET NAMES UTF8");
		
		
		if(mysql_error()){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($bd);
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
		
		if($this->mysql_admin==null){
			$this->writeLogs("MakeConnection:: mysql_admin is null, restart parameters ",__CLASS__.'/'.__FUNCTION__,__LINE__);
			unset($_SESSION["MYSQL_PARAMETERS"]);
			unset($GLOBALS["MYSQL_PARAMETERS"]);
			$this->FillSettings();
		}

		$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			
		$this->mysql_connection=$bd;
		if($database<>null){
			$ok=@mysql_select_db($database);
	    	if (!$ok){
	    		$errnum=mysql_errno($this->mysql_connection);
	    		$des=mysql_error($this->mysql_connection);
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
	
	public function BD_CONNECT($noretry=false){
		
		$this->ok=true;
		if(!function_exists('mysql_connect')){$this->mysql_error="mysql_connect() function does not exists";$this->ok=false;return false;}
		if(($this->mysql_admin==null) && ($this->mysql_password==null)){$this->mysql_admin="root";if(!isset($GLOBALS["ERROR_MYSQL_NOADMIN"])){}$GLOBALS["ERROR_MYSQL_NOADMIN"]=true;}
		
		$MYSQLpassword=$this->mysql_password;
		if($MYSQLpassword=="!nil"){$MYSQLpassword=null;$this->mysql_password=null;}
		$MYSQLpassword=stripslashes($MYSQLpassword);
		if(strlen($MYSQLpassword)==0){
			if(!isset($GLOBALS["ERROR_MYSQL_NOPASSWD"])){
			if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}			
			$GLOBALS["ERROR_MYSQL_NOPASSWD"]=true;
			}
			
		}
		
		
		
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$serverLog=":/var/run/mysqld/mysqld.sock";
			if($this->mysql_password<>null){
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
			}else{
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,null);
			}
		}else{
			$serverLog="$this->mysql_server:$this->mysql_port";
			if($this->mysql_password<>null){
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			}else{
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,null);
			}
		}
		
		
		
		
		
		
		if(!$bd){
			$des=mysql_error();
			$errnum=mysql_errno();	
			if(($errnum==1045) or ($errnum==0) OR !is_numeric($errnum)){if(!$noretry){$this->mysql_admin="root";$this->mysql_password=null;$this->BD_CONNECT(true);return;}}
			
			$this->writeLogs("CONNECT TO DATABASE: Error Number ($errnum) ($des) config:$serverLog@$this->mysql_admin:$this->mysql_password $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );
			if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
				$serverLog="$this->mysql_server:$this->mysql_port";
				if($this->mysql_password<>null){
					$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
				}else{
					ini_set("mysql.default_password", null);
					$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,null);
				}
			}
		}
		
		if(!$bd){
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
			$des=mysql_error();
			$errnum=mysql_errno();				
    		$this->mysql_error=$des;
			if($GLOBALS["VERBOSE"]){echo "mysql_connect failed ($errnum) \"$des\" $called";}
    		$this->writeLogs("CONNECT TO DATABASE: Error Number ($errnum) ($des) config:$serverLog@$this->mysql_admin:$this->mysql_password $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );
			if(strpos($des, "Can't connect to local MySQL server")){
				if($GLOBALS["AS_ROOT"]){shell_exec("/etc/init.d/artica-postfix start mysql");}
			}
    		return false;
		}		
			
		$this->mysql_connection=$bd;		
		return true;
	}
	
	
	
	function QUERY_SQL($sql,$database=null){
		
		$database=trim($database);
		if(!$this->UseMysql){
			$q=new lib_sqlite();
			$a=$q->QUERY_SQL($sql,$database);
			$this->mysql_error=$q->sql_error;
			$this->ok=$q->ok;
			return $a;
		}
		
		if(!$this->BD_CONNECT()){
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=" Error, unable to connect to MySQL server";
			return false;
		}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/{$re[1]} was deleted $called" , __FUNCTION__, __FILE__, __LINE__, $category);
			}
		}
		

		if($database<>null){
			$ok=@mysql_select_db($database);
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error($this->mysql_connection);
	    		$this->mysql_errornum=$errnum;
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
	    		
	    			$page=$this->CurrentPageName;
						if($page=='obm.export.php' OR $page=="exec.obm.synchro.php"){
							echo "Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:****\n";
							echo "Database selected=$database\n";
						}
						
	    		if(preg_match("#Access denied for user#", $des)){
					unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
					unset($GLOBALS["MYSQL_PARAMETERS"]);
					unset($_SESSION["MYSQL_PARAMETERS"]);
					$des=" please retry again...\n".$des;
				}						
	    		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	    		$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="QUERY_SQL:: mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
	    		
	    		$this->ok=false;
	    		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("DB:\"$database\" $this->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
	    		return null;
    		}
		}	
		$results=mysql_query($sql,$this->mysql_connection);
		
		//if(preg_match("#Got error 134 from storage engine#i", $q->mysql_error)){
		if(!$results){
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error();
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
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error();
			@mysql_free_result($this->mysql_connection);
			if($des<>null){
				if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}
			}
		}
	
		if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno($this->mysql_connection);
				@mysql_free_result($this->mysql_connection);
				$des=mysql_error();
				if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error $des $called line ".__LINE__;
				
				if(preg_match("#Incorrect key file for table '.\/(.+?)/(.+?)\.MYI'#",$des,$re)){
					echo "<H2>DB:$database Error table {$re[2]} is crashed $errnum : i will try to repair it</H2>";
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("DB:\"$database\" Error $des\ntable {$re[2]} is crashed $errnum : i will try to repair it " , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
					$this->REPAIR_TABLE($database,$re[2]);
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
				
				
							
				if(preg_match("#Table '(.+?)' is marked as crashed and should be repaired#",$des,$re)){
					echo "<H2>DB:$database Error table {$re[1]} is crashed $errnum : i will try to repair it</H2>";
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("DB:$database Error table {$re[1]} is crashed $errnum : i will try to repair it" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}					
					$this->REPAIR_TABLE($database,$re[1]);
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					
				}
				
				if(preg_match("#Table '\.\/(.+?)\/(.+?)'\s+is marked as crashed and last#",$des,$re)){
					echo "<H2>DB:$database Error table {$re[2]} is crashed $errnum : i will try to repair it</H2>";
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("DB:$database Error $errnum, Table {$re[1]}.{$re[2]} is crashed\n$des\nArtica will try to repair it" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}						
					$this->REPAIR_TABLE($re[1],$re[2]);
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error table {$re[2]} is crashed $errnum -> i will try to repair it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
				
				if(preg_match("#Access denied for user#", $des)){
					unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
					unset($GLOBALS["MYSQL_PARAMETERS"]);
					unset($_SESSION["MYSQL_PARAMETERS"]);
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("$this->mysql_error please retry..." , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}						
					$this->mysql_error=$this->mysql_error." please retry...";
					$this->ok=false;
					return;
				}
				
				if(preg_match("#Table\s+'\.\/(.+?)\/(.+?)'\s+is marked as crashed#",$des,$re)){
					echo "<H2>DB:$database Error table {$re[2]} is crashed $errnum : i will try to repair it</H2>";
					$this->REPAIR_TABLE($re[1],$re[2]);
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
				
				if(preg_match('#Duplicate entry#',$des)){
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("DB:$database Error $errnum $des" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}										
					@mysql_close($bd);
					$this->ok=true;
					return true;
				}

				if(preg_match("#Incorrect file format '(.+?)'#",$des,$re)){
					$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$table=trim($re[1]);
					if($table<>null){
						$this->ok=false;
						$this->mysql_error="Incorrect file format for table $table";
						$this->REPAIR_TABLE($database,$table);
						if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("DB:\"$database\" $this->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
						
					}
					return null;
				}
				

				if(preg_match('#/(.+?)/(.+?)\'\s+.+?crashed.+?(repaired|repair)#',$des,$re)){
					if(!preg_match('#REPAIR TABLE#',$sql)){
						$this->writeLogs("Try to repair {$re[2]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->mysql_error=$des." Artica answer Try to repair {$re[2]}";
						$this->REPAIR_TABLE($re[1],$re[2]);
						if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("DB:\"$database\" $this->mysql_error" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
						$this->ok=false;
						return null;
					}
				}
				
				if(preg_match("#Table.+?quarantine.+?doesn't exist#",$des)){
						if(!$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]){
							$this->writeLogs("Try to build quarantine table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
							$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]=true;
							$this->Check_quarantine_table();
							$this->ok=true;
							return null;
						}
					
				}

				if(preg_match("#Table.+?storage.+?doesn't exist#",$des)){
							if(!$GLOBALS["CHECKMYSQLTABLE"]["storage"]){
								$GLOBALS["CHECKMYSQLTABLE"]["storage"]=true;
								$this->writeLogs("Try to build storage table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
								$this->check_storage_table();
								$this->ok=true;
								return null;
							}
					
				}		

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
		
	$this->last_id=@mysql_insert_id($this->mysql_connection);	
	$this->ok=true;
	$result_return=$results;
	//@mysql_free_result($result); 
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
	
	
function BackupTable($tablename,$database,$addons=array(),$zmd5=false,$endquery=null){
		 if(!$this->MakeConnection($database)){return false;}
		 $this->ok=true;
		 $query = "SELECT * FROM $tablename $endquery";
         $resData = mysql_query($query,$this->mysql_connection);
         $addons_num=count($addons);
         
         
		  
         if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno($this->mysql_connection);
				$des=mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("Exporting table $tablename Error Number ($errnum) ($des)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				writelogs("Exporting table $query (".mysql_num_rows($resData).")",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->ok=false;
				return false;
				
         }
         
         if (mysql_num_rows($resData) == 0){
         	writelogs("Exporting table $tablename no rows, aborting ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
         	return null;
         }
         
         writelogs("Exporting table $query (".mysql_num_rows($resData).")",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
         $num_fields = mysql_num_fields($resData);
          for($j=0; $j < $num_fields; $j++){
                  	$sFieldnames .= "`".mysql_field_name($resData, $j)."`,";
                  }
                  
                if($addons_num>0){
                	while (list ($key, $val) = each ($addons) ){$sFieldnames .= "`$key`,";}
                }
                
                if($zmd5){$sFieldnames .= "`zmd5`,";}
                  
                $sFieldnames = "(".substr($sFieldnames,0,-1).")";
               
            $sInsert = "INSERT INTO `$tablename` $sFieldnames VALUES\n";
            
            while($rowdata = @mysql_fetch_assoc($resData)){
            		$fields=array();
            		$fmd5=null;
 				while (list ($key, $val) = each ($rowdata)){
            			$fields[]="'".addslashes($val)."'";
            			if(strlen($val)<255){$fmd5=$fmd5.$val;}
            		}
            		
             if($addons_num>0){
             		reset($addons);
                	while (list ($key, $val) = each ($addons) ){
                			if(strlen($val)<255){$fmd5=$fmd5.$val;}
                			$fields[]="'".addslashes($val)."'";
                		}
                	
                }   

                if($zmd5){$fields[]="'".md5($fmd5)."'";}
            	$lines[]="(".@implode(",",$fields).")";
				}
				
			$export = "$sInsert".@implode(",\n",$lines)."\n";
				
            
            
            return $export;
                 
   //fclose($fp);
}	

	
	function REPAIR_TABLE($database,$table){
		$this->writeLogs("Try to repair $table in database $database",__CLASS__.'/'.__FUNCTION__,__LINE__);
		
		$sql="REPAIR TABLE $table use_frm";
		$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		$this->mysql_connection=$bd;
		$ok=@mysql_select_db($database);		
		
		if (!$ok){
	    	$errnum=mysql_error();
	    	$des=mysql_error();
	    	$this->mysql_error=$des;
	    	$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: use_frm $this->mysql_error";
			if(function_exists("system_admin_events")){system_admin_events("$this->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
	    	$this->writeLogs("REPAIR_TABLE: use_frm $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    	@mysql_close($this->mysql_error);
		   }		
		
		$sql="REPAIR TABLE $table EXTENDED";
		$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		$this->mysql_connection=$bd;
		$ok=@mysql_select_db($database);
		if (!$ok){
	    	$errnum=mysql_error();
	    	$des=mysql_error();
	    	$this->mysql_error=$des;
	    	$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: EXTENDED $this->mysql_error";
			if(function_exists("system_admin_events")){system_admin_events("$this->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
	    	$this->writeLogs("REPAIR_TABLE: EXTENDED $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    	@mysql_close($this->mysql_error);
	    	return;
		   }
		   
		if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="Error Number ($errnum) ($des)";		   
		   		@mysql_close($this->mysql_error);
		   		$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE:$this->mysql_error";
				if(function_exists("system_admin_events")){system_admin_events("$this->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
		   		$this->writeLogs("REPAIR_TABLE: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__LINE__);
		}
		
		$ligne=$this->TABLE_STATUS($table,$database);
		$failed=false;
		$this->writeLogs("REPAIR_TABLE: TABLE_STATUS -> `{$ligne["Comment"]}`",__CLASS__.'/'.__FUNCTION__,__LINE__);
		if(preg_match("#marked as crashed and#",$ligne["Comment"])){$failed=true;}
		if(preg_match("#Incorrect file format#",$ligne["Comment"])){$failed=true;}
		
		if($failed){
			$sock=new sockets();
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
		$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: Success...";
	}
	
	private function REPAIR_TABLE_FAILED($database,$table){
		$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: DROP $database/$table";
		
		$this->writeLogs("REPAIR_TABLE: DROP $database/$table",__CLASS__.'/'.__FUNCTION__,__LINE__);
		$sql="DROP TABLE $table";
		$this->QUERY_SQL($sql,$database);
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
	
	function SIMPLE_GROUPWARE_ENABLED($userid){
		$database="SimpleGroupware";
		$sql="SELECT id,activated FROM simple_sys_users WHERE username='$userid' LIMIT 0,1";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if(!is_numeric($ligne["id"])){return false;}
		if($ligne["activated"]=="0"){return false;}
		return true;
		}
		
		
	function SET_SIMPLE_GROUPWARE_ACTIVE($userid,$active){
		$database="SimpleGroupware";
		$sql="SELECT id,activated FROM simple_sys_users WHERE username='$userid' LIMIT 0,1";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if(!is_numeric($ligne["id"])){
			$this->SIMPLE_GROUPWARE_USER_ADD($userid,$active);
			return true;}
			
		$this->SIMPLE_GROUPWARE_USER_EDIT($ligne["id"],$active);
		
	}
	
	function COUNT_DB_FROM_USER($user){
		$sql="SELECT COUNT(`Db`) as tcount FROM `db` WHERE `User`='$user'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,"mysql"));
		return $ligne["tcount"];
	}
	
	function DELETE_USER_INMYSQL($user){
		$this->BD_CONNECT();
		if($user==$this->mysql_admin){return;}
		if(!$this->mysql_connection){return;}
	    $results=@mysql_query("DROP USER '$user'@'localhost';",$this->mysql_connection);
		if(mysql_error()){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
		$results=@mysql_query("DROP USER '$user'@'127.0.0.1';",$this->mysql_connection);
		if(mysql_error()){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}

		$results=@mysql_query("DROP USER '$user'@'%';",$this->mysql_connection);
		if(mysql_error()){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
	}
	
	function SIMPLE_GROUPWARE_USER_EDIT($sql_id,$active){
		$database="SimpleGroupware";
		$sql="UPDATE simple_sys_users SET activated='$active' WHERE id='$sql_id'";
		$this->QUERY_SQL($sql,$database);
		
	}
	
	function SIMPLE_GROUPWARE_USER_ADD($userid,$active){
		$user=new user($userid);
		$md=md5($user->password);
		$database="SimpleGroupware";
		$sql="INSERT INTO `simple_sys_users` (
		`id`, 
		`username`, 
		`password`, 
		`email`, 
		`firstname`, 
		`lastname`, 
		`neverexp`, 
		`activated`)
		VALUES(
		'$user->uidNumber',
		'$userid',
		'$md',
		'$user->mail',
		'$user->givenName',
		'$user->sn',
		'1',
		'$active')";
		$this->QUERY_SQL($sql,$database);
		
	}
	
	function APACHE_TABLE_NAME($servername){
		$table_name="access_".str_replace(".", "_", $servername);
		return $table_name;
	}	
	
	
	function TABLE_EXISTS($table,$database,$nocache=false){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!$nocache){
			if(!isset($GLOBALS["__MYSQL_TABLE_EXISTS"])){$GLOBALS["__MYSQL_TABLE_EXISTS"]=array();}
			if(isset($GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table])){if($GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table]==true){return true;}}
			if(isset($_SESSION["__MYSQL_TABLE_EXISTS"][$database][$table])){if($_SESSION["__MYSQL_TABLE_EXISTS"][$database][$table]==true){return true;}}
		}
		
		if(!$this->DATABASE_EXISTS($database)){
			$this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->CREATE_DATABASE($database);
			if(!$this->DATABASE_EXISTS($database)){$this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;}
		}
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql,$database);
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){
				$GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table]=true;
				$_SESSION["__MYSQL_TABLE_EXISTS"][$database][$table]=true;
				return true;
			}
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
			if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
	if(!$bd){
    		$errnum=mysql_error();
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		return null;
		}
		$results=@mysql_query($sql);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}
		
	function SET_VARIABLES($key,$value){
		
	if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}
		if(!$bd){
    		$errnum=mysql_error();
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		return null;
			}
		$results=@mysql_query("SET GLOBAL $key = $value");
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		}	

		
	function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		
		if(!$bd){
    		$errnum=mysql_error();
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		return null;
		}
		$results=@mysql_query($sql);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
		}		

		
	function DATABASE_LIST(){
		$sql="SHOW DATABASES";
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		
		if(!$bd){
    		$errnum=mysql_error();
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		return null;
		}
		$results=@mysql_query($sql);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=$this->DATABASE_INFOS($Database);
			}
			return $array;
		}
		
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		
		if(!$bd){
    		$errnum=mysql_error();
    		$des=mysql_error();
    		$this->mysql_error=$des;
    		return null;
		}
		$results=@mysql_query($sql);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;

		}		
		
		
	function TABLES_LIST($database){
		$sql="SHOW TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
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
	
	
function mysql_real_escape_string2($query){
   $len=strlen($str);
    $escapeCount=0;
    $targetString='';
    for($offset=0;$offset<$len;$offset++) {
        switch($c=$str{$offset}) {
            case "'":
            // Escapes this quote only if its not preceded by an unescaped backslash
                    if($escapeCount % 2 == 0) $targetString.="\\";
                    $escapeCount=0;
                    $targetString.=$c;
                    break;
            case '"':
            // Escapes this quote only if its not preceded by an unescaped backslash
                    if($escapeCount % 2 == 0) $targetString.="\\";
                    $escapeCount=0;
                    $targetString.=$c;
                    break;
            case '\\':
                    $escapeCount++;
                    $targetString.=$c;
                    break;
            default:
                    $escapeCount=0;
                    $targetString.=$c;
        }
    }
    return $targetString;
} 
	
	
	
	
	FUNCTION CREATE_DATABASE($database,$nocache=false){
		$this->mysql_password=trim($this->mysql_password);
		if(!$nocache){
		if($this->DATABASE_EXISTS($database)){
			writelogs("CREATE_DATABASE $database Already exists aborting",__CLASS__,__FILE__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}}
		if(($this->mysql_server=="localhost") OR ($this->mysql_server=="127.0.0.1")){
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		writelogs("CREATE DATABASE $database",__CLASS__,__FILE__,__FILE__,__LINE__);
		if(!$bd){
    		$des=mysql_error();
    		$errnum=mysql_errno();	
    		$this->mysql_error="($errnum) $des $this->mysql_admin@$this->mysql_server";
    		writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server ($des) `$this->mysql_password`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
    		if($this->mysql_server=="127.0.0.1"){
    			if($this->mysql_admin<>null){
    				if($this->mysql_password<>null){
    					$sock=new sockets();
    					if($sock->GET_INFO("MysqlAccountAutoCreated")<>1){
    						writelogs("All parameters are good for creating the $this->mysql_admin account in mysql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
    						$sock->getFrameWork("cmd.php?ChangeMysqlLocalRoot=$this->mysql_admin&password=$this->mysql_password");
    						$sock->SET_INFO("MysqlAccountAutoCreated",1);
    					}
    				}
    			}
    		}
    		return false;
		}
    		$results=@mysql_query("CREATE DATABASE `$database`");
			if(mysql_error()){
				$time=date('h:i:s');
				$errnum=mysql_errno();
				$des=mysql_error();
				$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}

		$this->ok=true;
		return true;
		
	}
	
	FUNCTION CHECK_PERFORMANCES(){
		if(!function_exists("mysql_connect")){return 0;}
		$this->BD_CONNECT();
		$results=@mysql_query("SELECT benchmark(100000000,1+2);");
	}
	
	
	FUNCTION DELETE_DATABASE($database){
		if(!function_exists("mysql_connect")){$this->ok=false;return 0;}
		$this->ok=true;
		$this->BD_CONNECT();
		
		
		
		$results=@mysql_query("DROP DATABASE `$database`",$this->mysql_connection);	
		
		if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$this->ok=false;
				$errnum=mysql_errno($this->mysql_connection);
				$des=mysql_error($this->mysql_connection);
							
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
	
	
	private function Check_mldonkey_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_mldonkey_table();
	}
	
function check_vps_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_vps_tables();	
	}

	function check_networks_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_networks_tables();	
	}
	
	function check_postfwd2_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->check_postfwd2_table();
		if(!$q->ok){$this->ok=false;$this->mysql_error=$q->mysql_error;}
		}	
	
	function Check_iptables_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_iptables_table();
	}
	
	
	function Check_smtp_logs_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_smtp_logs_table();	
	}

	public function CheckTablesAwstats(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTablesAwstats();
	}
	
	
	public function CheckTablesPostfilter(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTablesPostfilter();
	}
	public function checkTablesSamba(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkTablesSamba();
	}	
	
	
	
	public function checkRoundCubeTables($database){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkRoundCubeTables($database);	
	}
	
	
	
	private function checkAgentTables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkAgentTables();		
	}
	
	private function CheckTableWebsites(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableWebsites();			
	}
	
	
	private function CheckTableVirus(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableVirus();			
		
	}
	
	public function check_emailing_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_emailing_tables();		
	}
	
	
	public function CheckTableEmailingContacts($tablename,$merged=array()){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableEmailingContacts($tablename,$merged);
		
	}
	
	
	public function CheckTableThinClient(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableThinClient();
	}
	
	private function CheckTableIMAP(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableIMAP();	
	}

	private function CheckTableEvents(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableEvents();	
	}
	
	private function CheckOutOfOfficeTable(){
			include_once(dirname(__FILE__).'/class.mysql.builder.inc');
			$q=new mysql_builder();
			return $q->CheckOutOfOfficeTable();	
	}
	
	public function CheckTables_gluster(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTables_gluster();	
	}
	
	
	
	function BuildTables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->BuildTables();		
	}
	
	public function check_storage_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->check_storage_table();
	}
	
	
	function Check_reports_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->Check_reports_table();
	}
	
	
	function Check_quarantine_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->Check_quarantine_table();
	}
	
	
	private	function CheckTables_repositories(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_repositories();	
	}
	
	public function CheckTables_openvpn(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_openvpn();	

	}
	
	
	private function  CheckTables_qos(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_qos();
	}
	
	
	private function CheckTables_dar(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_dar();
	}
	
	public function CheckTable_dansguardian(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTable_dansguardian();
	}
	
	public function CheckTablesSquid(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTable_dansguardian();
	}
	
	private function squid_events_sites_day_patch(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->squid_events_sites_day_patch();
	}
	
	private function squid_events_sites_day($check=true){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->squid_events_sites_day($check);		
	} 
	function CheckTables_rsync(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_rsync();		
	}
}
?>