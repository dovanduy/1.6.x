<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');

class mysql_storelogs{
	private $ClassSQL;
	private $LogRotatePath=null;
	private $MySQLSyslogType=1;
	private $mysql_errornum=0;
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	public $database="syslogs";
	private $mysql_connection=false;
	public $SocketName="";
	public $ok=false;
	public $mysql_error;
	public $last_id=0;
	public $dbidsn="";
	public $mysql_port=3306;
	private $NOCHDB=array("mysql"=>true);
	public $start_error=null;
	private $MySQLLogsTypeTextAR=array("{not_set}","{server}","{client}");
	public $MYSQLTypeText;
	public $EnableSyslogDB=0;
	private $filename=null;
	
	function mysql_storelogs(){
		$this->OutputDebugVerbose("Loading...");
		$this->PrepareMySQLClass();
		
		if($this->EnableSyslogDB==0){return;}
		$this->OutputDebugVerbose("DATABASE_EXISTS($this->database) ?");
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->OutputDebugVerbose("CREATE_DATABASE($this->database) ?");
			$this->CREATE_DATABASE($this->database);
			$this->OutputDebugVerbose("CheckTables() ?");
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		unset($_SESSION["MYSQL_PARAMETERS"]);
		$this->MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
		$this->EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
		if(!is_numeric($this->EnableSyslogDB)){$this->EnableSyslogDB=0;}
		if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
		if($this->MySQLSyslogType==0){$this->MySQLSyslogType=1;}
		$this->LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($this->LogRotatePath==null){$this->LogRotatePath="/home/logrotate";}
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
		$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
		
		$this->OutputDebugVerbose("MySQLSyslogType=$this->MySQLSyslogType");
	
		if($this->MySQLSyslogType==1){
			$ListenPort=$TuningParameters["ListenPort"];
			$def["SocketPath"]="/var/run/syslogdb.sock";
			if(!is_file("/var/run/syslogdb.sock")){return;}
			$this->SocketName=$def["SocketPath"];
			$def["mysql_admin"]="root";
			$def["mysql_password"]=null;
			$def["TryTCP"]=false;
			$def["mysql_port"]=$ListenPort;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_port=$def["mysql_port"];
			$this->mysql_server="127.0.0.1";
			$this->ClassSQL=new mysql($def);
			$this->MYSQL_CMDLINES="--protocol=socket --socket={$def["SocketPath"]} -u root";
			$this->dbidsn="['DBI:mysql:database=$this->database;mysql_socket=$this->SocketName', '$this->mysql_admin', '$this->mysql_password']";
		}
		
		
		if($this->MySQLSyslogType==2){
			$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
			$username=$TuningParameters["username"];
			$password=$TuningParameters["password"];
			$mysqlserver=$TuningParameters["mysqlserver"];
			$RemotePort=$TuningParameters["RemotePort"];			
			$def["SocketPath"]=null;
			$def["mysql_admin"]=$username;
			$def["mysql_password"]=$password;
			$def["TryTCP"]=true;
			$def["mysql_port"]=$RemotePort;
			$def["mysql_server"]=$mysqlserver;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_port=$def["mysql_port"];
			$this->mysql_server=$mysqlserver;
			$this->ClassSQL=new mysql($def);
			$pwd=null;
			if($password<>null){ $pwd=" -p$password";}
			$this->MYSQL_CMDLINES="--protocol=tcp --user=$username$pwd --host=$mysqlserver --port=$RemotePort";
			$this->dbidsn="['DBI:mysql:database=$this->database;host=$mysqlserver;port=$RemotePort', '$username', '$password']";
		}
		return $this->ClassSQL;
	}
	
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		if(!method_exists($this->ClassSQL, "DATABASE_EXISTS")) { 
			return $this->THIS_DATABASE_EXISTS($this->database);
		}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		if(!method_exists($this->ClassSQL, "CREATE_DATABASE")) { 
			$this->OutputDebugVerbose("this->CREATE_DATABASE($this->database)");
			return $this->THIS_CREATE_DATABASE($this->database);
		}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	

	FUNCTION TABLE_SIZE($table){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];
	
	}
	
	
	public function GRANT_PRIVS($hostname,$username,$password){
		$sql="SELECT User FROM user WHERE Host='$hostname' AND User='$username'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,"mysql"));
		if(trim($ligne["User"])==null){
			if(!$this->EXECUTE_SQL("CREATE USER '$username'@'$hostname' IDENTIFIED BY '$password';")){
				return false;
			}
				
				
			if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){
				return false;
			}
			$this->EXECUTE_SQL("FLUSH PRIVILEGES;");
			return true;
		}
	
		if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){return false;}
		$this->EXECUTE_SQL("FLUSH PRIVILEGES;");
		return true;
	}
	
	
	private function THIS_CREATE_DATABASE($database){
		$this->OutputDebugVerbose("$database");
		$this->mysql_password=trim($this->mysql_password);
		
		
			if(!$this->BD_CONNECT()){
				writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
				return false;
			}
		
		
			if($GLOBALS["VERBOSE"]){echo " -> ->DATABASE_EXISTS($database)<br>\n";}
				if($this->DATABASE_EXISTS($database)){
					writelogs("CREATE_DATABASE $database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
					$this->ok=true;
					return true;
			}
			
			
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
	
	
	private function THIS_DATABASE_EXISTS($database,$nocache=false){
		$key=__CLASS__.__FUNCTION__;
		if(!$nocache){
			if(isset($_SESSION[$key][$database])){
				if($_SESSION[$key][$database]==true){return true;}
			}
		}
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION[$key][$database]=true;
				return true;
			}
		}
	
		return false;
	
	}

	public function THIS_BD_CONNECT($noretry=false,$called=null){
		if($GLOBALS["VERBOSE"]){echo "THIS_BD_CONNECT()<br>\n";}
		
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection()<br>\n";}
		if($this->THIS_TestingConnection(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection() -> TRUE<br>\n";}
			return true;
		}
		return false;
	}
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	private function THIS_TestingConnection($noretry=false,$called=null){
		
		if($this->mysql_connection){return true;}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		if($this->MySQLSyslogType==1){
			if($GLOBALS["VERBOSE"]){echo "mysql_connect -> /var/run/syslogdb.sock<br>\n";}
			
			if(!$this->is_socket("/var/run/syslogdb.sock")){
				$this->mysql_error="/var/run/syslogdb.sock no such socket";
				OutputDebugVerbose("/var/run/syslogdb.sock !!! false");
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
			}
			
			$bd=@mysql_connect(":/var/run/syslogdb.sock","root",null);
			
			if($bd){
					$this->mysql_connection=$bd;
					$GLOBALS["THIS_TestingConnection"]=true;
					return true;
			}
			if($GLOBALS["VERBOSE"]){echo "mysql_connect /var/run/syslogdb.sock -> error<br>\n";}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->writelogs("mysql_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
		
		if($this->MySQLSyslogType==2){
			
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($bd){$this->mysql_connection=$bd;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->writelogs("mysql_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
			
	}
	
	public function BD_CONNECT(){
		$q=$this->PrepareMySQLClass();
		$this->ok=true;
		if($this->EnableSyslogDB==0){return true;}
		if(!method_exists($this->ClassSQL, "BD_CONNECT")) {
			$results=$this->THIS_BD_CONNECT();
			if($results){return true;}
		}else{
			$results=$this->ClassSQL->BD_CONNECT();
			if($results){$this->mysql_connection=$this->ClassSQL->mysql_connection;return true;}
			$this->mysql_error=$this->ClassSQL->mysql_error;
		}
		
		$this->ok=false;
		$this->writelogs("Fatal connection failed [".$this->ClassSQL->mysql_error."]",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	
	}
	
	private function OutputDebugVerbose($text){
	if(!$GLOBALS["VERBOSE"]){return;}
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$filename=basename($trace[1]["file"]);
	$function=$trace[1]["function"];
	$line=$trace[1]["line"];
	echo "$filename:: $function $text in line $line<br>\n";
}
	
	private function THIS_COUNT_ROWS($table,$database){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}

	public function COUNT_ROWS($table,$database=null){
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
	
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if(!method_exists($this->ClassSQL, "COUNT_ROWS")) {
			return $this->THIS_COUNT_ROWS($table,$database);
		}
		
		
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
		
	
	public function QUERY_SQL($sql,$database=null){
		if(!$this->BD_CONNECT()){return false;}
		if($this->EnableSyslogDB==0){return true;}
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if($database==null){$database=$this->database;}
		
		if(!method_exists($this->ClassSQL, "QUERY_SQL")) {
			return $this->THIS_QUERY_SQL($sql,$database);
		}
		
		
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
	
		@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error<hr>$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
	
		return true;
	}	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		$database=$this->database;
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}	
	
	public function SHOW_STATUS(){
		$sql="SHOW STATUS;";
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
	
	public function COUNT_ALL_TABLES(){
		if(!$this->BD_CONNECT()){return false;}
		$sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = '$this->database'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		return array($ligne["tcount"],$ligne["x"]);
	}	

	public function checkTables(){
		if(!$this->BD_CONNECT()){return false;}
		$sql="CREATE TABLE IF NOT EXISTS files_store (
  			ID  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  		   `filecontent` longblob NOT NULL
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS access_store (
  			ID  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  		   `filecontent` longblob NOT NULL
			) ENGINE=MYISAM;";	

		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS files_info (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`filename` varchar(128) NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`storeid` int unsigned NOT NULL,
				`taskid` int(10) NOT NULL,
				`filesize` bigint(100) NOT NULL,
				`filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				 KEY `filename` (`filename`),
				 KEY `hostname` (`hostname`),
				 KEY `taskid` (`taskid`,`filesize`),
				 KEY `filetime` (`filetime`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS accesslogs (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`filename` varchar(128) NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`storeid` int unsigned NOT NULL,
				`taskid` int(10) NOT NULL,
				`filesize` bigint(100) NOT NULL,
				`filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				 KEY `filename` (`filename`),
				 KEY `hostname` (`hostname`),
				 KEY `taskid` (`taskid`,`filesize`),
				 KEY `filetime` (`filetime`)
				) ENGINE=MYISAM;";
		
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS evnts (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`zDate` datetime NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`content` TEXT NOT NULL,
				`subject` varchar(255) NOT NULL,
				 KEY `zDate` (`zDate`),
				 KEY `hostname` (`hostname`)
				
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		}		
	
	public function TABLE_EXISTS($table,$database=null){
		$this->BD_CONNECT();
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL("SHOW TABLES");
		if(!$this->ok){
			if($GLOBALS["VERBOSE"]){echo "SHOW TABLES -> $q->mysql_error\n";}
		}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$this->database"])){return true;}
		}
		
		return false;
	}

	public function DelteItem($ID){
		$sql="DELETE FROM files_store WHERE ID='$ID'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		$sql="DELETE FROM files_info WHERE storeid='$ID'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		return true;		
	}
	
	public function ExtractFile($filepath,$storeid){
		$sql="SELECT filecontent INTO DUMPFILE '$filepath' FROM files_store WHERE ID = '$storeid'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		return true;
	}
	private function ConvertGZToBzip($filesource){
		$t=time();
		$fromTime=time();
		$fileDest=str_replace(".gz", ".bz2", $filesource);
		$unix=new unix();
		$gunzip=$unix->find_program("gunzip");
		$bzip2=$unix->find_program("bzip2");
		$cmd="$gunzip --to-stdout \"$filesource\" | $bzip2 > \"$fileDest\"";
		shell_exec($cmd);
		if(!is_file($fileDest)){return null;}
		return $fileDest;
	}

	private function COMPRESS_FILE($filename){
		$unix=new unix();
		if(!isset($GLOBALS["BZ2BIN"])){$GLOBALS["BZ2BIN"]=$unix->find_program("bzip2");;}
		$EXEC_NICE=$unix->EXEC_NICE();
		$cmdline="$EXEC_NICE {$GLOBALS["BZ2BIN"]} -z $filename";
		shell_exec($cmdline);
		if(!is_file("$filename.bz2")){return false;}
		$cmdline="{$GLOBALS["BZ2BIN"]} -t -v $filename.bz2 2>&1";
		exec($cmdline,$results);
		while (list ($num, $line) = each ($results) ){
			if(strpos($line,": ok")>0){return true;}
		}
		@unlink("$filename.bz2");
	}	
	
	
	public function ROTATE_ACCESS_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$this->filename=$filename;
		$taskid=0;
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		if(!$this->TestCompressed()){return false;}
		if($this->EnableSyslogDB==0){return false;}
		if(!$this->InjectFile_accesslog($this->filename, $sourceDate)){return false;}
		@unlink($this->filename);
		return true;
		
			
		
		
	}
	
	private function TestCompressed(){
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		
		$extension = pathinfo($this->filename, PATHINFO_EXTENSION);
		if($extension=="gz"){
			system_admin_events("$this->filename => Converting to bz2",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$filename=$this->ConvertGZToBzip($this->filename);
			if($this->filename==null){return false;}
		}
		if(!isset($COMPRESSEDS[$extension])){
			if(!$this->COMPRESS_FILE($this->filename)){return false;}
			$this->filename=$this->filename.".bz2";
		}
		
		return true;
		
	}
	
	
	
	public function ROTATE_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$taskid=0;
		$this->filename=$filename;
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		
		
		if(!$this->TestCompressed()){return false;}
		
		
		if($this->EnableSyslogDB==1){
			if(!$this->InjectFile($this->filename, $sourceDate)){return false;}
			@unlink($this->filename);
			return true;
		}
		
		
		
	
		$basename=basename($this->filename);
		$LogRotatePath=$sock->GET_INFO("LogRotatePath");
		$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
		if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
		if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
		if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
		$LogRotatePathWork="$LogRotatePath/work";
		if($LogRotateMysql==0){$LogRotatePathWork=$LogRotatePath;}
		if(!is_dir($LogRotatePathWork)){@mkdir($LogRotatePathWork,0777);}
		@chmod($LogRotatePathWork, 0777);
		$basenameFF=null;
		$DestinationFile="$LogRotatePathWork/$basename";
	
	
		if(is_file($DestinationFile)){
			$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
			$basenameFF=basename($DestinationFile);
			$basenameFF=str_replace(".$ext", "", $basenameFF);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestinationFile=str_replace(basename($DestinationFile), $basenameFF, $DestinationFile);
		}
	
		@mkdir(dirname($DestinationFile),0755,true);
		if(!@copy($this->filename, $DestinationFile)){
			@unlink($DestinationFile);
			rotate_events("Failed to copy $this->filename => $DestinationFile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}
	
		if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
	
		$ext = pathinfo($this->filename, PATHINFO_EXTENSION);
		$basenameFF=$basename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".time().".$ext";
		$filesize=$unix->file_size($this->filename);
		$ToMySQL=false;
	
		if($LogRotateMysql==1){
			$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`)
			VALUES ('$basenameFF','$taskid','$filesize',LOAD_FILE('$DestinationFile'),'$sourceDate')";
			$ToMySQL=true;
		}
	
		if($LogRotateMysql==0){
			$basenameFF=basename($DestinationFile);
			$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`,`SavedInDisk`,`FileStorePath`)
			VALUES ('$basenameFF','$taskid','$filesize','','$sourceDate',1,'$DestinationFile')";
			$ToMySQL=true;
		}
	
		if(!$ToMySQL){return false;}
		
		$this->CheckTables();
		$this->QUERY_SQL($sql);
		if(!$q->ok){
			system_admin_events("MySQL Failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@unlink($DestinationFile);
			return false;
		}
		if($LogRotateMysql==1){@unlink($DestinationFile);}
		@unlink($this->filename);
		return true;
	}

	
	private function TransformName($filepath){
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));
		$LogRotatePathWork="$this->LogRotatePath/work";	
		@mkdir("$this->LogRotatePath/work",0755);
		if(!is_dir($LogRotatePathWork)){@mkdir($LogRotatePathWork,0777);}
		@chmod($LogRotatePathWork, 0777);
		$basenameFF=null;
		$DestinationFile="$LogRotatePathWork/$basename";
		if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
		if(is_file($DestinationFile)){
			$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
			$basenameFF=basename($DestinationFile);
			$basenameFF=str_replace(".$ext", "", $basenameFF);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestinationFile=str_replace(basename($DestinationFile), $basenameFF, $DestinationFile);
		}
		
		return $DestinationFile;
	}
	
	
	public function InjectFile($filepath,$sourceDate=null){
		$FILE_SOURCE_TO_DELETE=$filepath;
		$taskid=0;
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		

		$this->events("Info: want to backup $filepath ($sourceDate)",__FUNCTION__,__LINE__);
		$DestinationFile=$this->TransformName($filepath);
		


		if(!@copy($filepath, $DestinationFile)){
			@unlink($DestinationFile);
			$this->events("Failed to copy $filepath => $DestinationFile");
			return false;
		}		
		
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		$basenameFF=$basename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".time().".$ext";
		$filesize=$unix->file_size($filepath);		
		$fcontent=null;
		
		$this->events("Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)");
		
		if($this->MySQLSyslogType==1){
			$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( LOAD_FILE('$DestinationFile') )";
			
		}
		if($this->MySQLSyslogType==2){
			$fcontent=mysql_escape_string2(file_get_contents($DestinationFile));
			$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( '$fcontent' )";
		}
		
		$this->QUERY_SQL($sql);
		$fcontent=null;
		
		if(!$this->ok){
			@unlink($DestinationFile);
			$this->events("$q->mysql_error, $sql");
			return false;
		}	

		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($DestinationFile);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO files_info (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){
			
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM files_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
		
	}
	
	public function InjectFile_accesslog($filepath,$sourceDate=null){
		$FILE_SOURCE_TO_DELETE=$filepath;
		$taskid=0;
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$basenameFF=null;
		
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		$this->events("Info: want to backup $filepath ($sourceDate)",__FUNCTION__,__LINE__);
	
		if(!$this->TABLE_EXISTS("access_store")){$this->checkTables();}
	
		$DestinationFile=$this->TransformName($filepath);
		
		if(!@copy($filepath, $DestinationFile)){
			@unlink($DestinationFile);
			$this->events("Failed to copy $filepath => $DestinationFile");
			return false;
		}
	
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		$basenameFF=$basename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".time().".$ext";
		$filesize=$unix->file_size($filepath);
		$fcontent=null;
	
		$this->events("Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)");
	
		if($this->MySQLSyslogType==1){
			
			$sql = "INSERT INTO `access_store` (`filecontent`) VALUES( LOAD_FILE('$DestinationFile') )";
				
		}
		if($this->MySQLSyslogType==2){
			$fcontent=mysql_escape_string2(file_get_contents($DestinationFile));
			$sql = "INSERT INTO `access_store` (`filecontent`) VALUES( '$fcontent' )";
		}
	
		$this->QUERY_SQL($sql);
		$fcontent=null;
	
		if(!$this->ok){
			@unlink("$DestinationFile");
			$this->events("$q->mysql_error, $sql");
			@unlink($DestinationFile);
			return false;
		}
	
		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($DestinationFile);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO accesslogs (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
	
		$this->QUERY_SQL($sql);
		if(!$this->ok){
				
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM access_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
	
	}	
	
	public function events($text,$function=null,$line=0){
		$pid=@getmypid();
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				if($function==null){$function=$trace[1]["function"];}
				if($line==0){$line=$trace[1]["line"];}
			}
		}		
		
		if(function_exists("rotate_events")){rotate_events($text,$function,$file,$line);}
		if(function_exists("events")){events($text,$function,$file,$line);}
		
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/logrotate.debug";
		$textF="$date [$pid] $file $text <div><i>function:$function, line:$line</i></div>\n";
		if($GLOBALS["VERBOSE"]){echo $textF;}
		$size=@filesize($logFile);
		if($size>8000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, $textF);
		@fclose($f);	
		}
	function writelogs($text=null,$function=null,$line=0){
			$class=__CLASS__;
			$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
			@mkdir('/usr/share/artica-postfix/ressources/logs/web');
			if(!is_numeric($line)){$line=0;}
			if(function_exists("writelogs")){writelogs("$text (L.$line)","$class/$function",__FILE__,$line);}
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

		
	private function THIS_QUERY_SQL($sql,$database=null,$called=null){
		$database=trim($database);
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}

		if(!$this->BD_CONNECT(false,$called)){
				if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
				if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
				$this->ok=false;
				$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
				return false;
			}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
		
		
		if($database<>null){
			$ok=@mysql_select_db($database,$this->mysql_connection);
				if (!$ok){
					$errnum=@mysql_errno($this->mysql_connection);
					$des=@mysql_error($this->mysql_connection);
					if($GLOBALS["VERBOSE"]){echo "mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
					$this->mysql_errornum=$errnum;
					$this->mysql_error=$des;
					$time=date('h:i:s');
				    $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
				    $this->mysql_error="QUERY_SQL:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				    $this->ok=false;@mysql_close($this->mysql_connection);
				    return null;
				}
			}
			
			
			$results=mysql_query($sql,$this->mysql_connection);
			if(!$results){
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				
				if(preg_match('#Duplicate entry#',$des)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				
					$this->ok=true;
					@mysql_close($this->mysql_connection);
					return true;
				}				
				
				
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				@mysql_free_result($this->mysql_connection);
				
			}
		
		$this->ok=true;
		$this->last_id=@mysql_insert_id($this->mysql_connection);	
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		$this->mysql_connection=false;
		return $result_return;
		

		}
		
		
}
