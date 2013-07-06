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
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	public $database="syslogs";
	private $mysql_connection;
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
	
	function mysql_storelogs(){
		$this->PrepareMySQLClass();
		
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->CREATE_DATABASE($this->database);
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		$this->MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
		$this->EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
		if(!is_numeric($this->EnableSyslogDB)){$this->EnableSyslogDB=0;}
		if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
		if($this->MySQLSyslogType==0){$this->MySQLSyslogType=1;}
		$this->LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($this->LogRotatePath==null){$this->LogRotatePath="/home/logrotate";}
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
		$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
		if($this->MySQLSyslogType==1){
			$ListenPort=$TuningParameters["ListenPort"];
			$def["SocketPath"]="/var/run/syslogdb.sock";
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
		return;
	}
	
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	public function BD_CONNECT(){
		$this->PrepareMySQLClass();
		$this->ok=true;
		$results=$this->ClassSQL->BD_CONNECT();
		if($results){
			$this->mysql_connection=$this->ClassSQL->mysql_connection;
			return true;
		}
		$this->ok=false;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		writelogs("Fatal connection failed [".$this->ClassSQL->mysql_error."]",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	
	}

	public function COUNT_ROWS($table,$database=null){
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
	
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
				
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
		
	
	public function QUERY_SQL($sql,$database=null){
		if(!$this->BD_CONNECT()){return false;}
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if($database==null){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
		if(isset($GLOBALS[__CLASS__]["FAILED"])){return;}
		@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
	
		return true;
	}	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		$database=$this->database;
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}	
	
	public function SHOW_STATUS(){
		if(!$this->BD_CONNECT()){return false;}
		return $this->ClassSQL->SHOW_STATUS();
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
			);";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->mysql_error;}
		
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
				);";
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
				
				);";
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		}		
	
	public function TABLE_EXISTS($table,$database=null){
		$this->BD_CONNECT();
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_EXISTS($table,$database);
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
	
	public function ROTATE_TOMYSQL($filename,$sourceDate){
		$sock=new sockets();
		$unix=new unix();
		$taskid=0;
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if($extension=="gz"){
			system_admin_events("$filename => Converting to bz2",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$filename=ConvertGZToBzip($filename);
			if($filename==null){return;}
			$extension="bz2";
		}
		if(!isset($COMPRESSEDS[$extension])){
			if(!$this->COMPRESS_FILE($filename)){return;}
			$filename=$filename.".bz2";
		}
		
		
		if($this->EnableSyslogDB==1){
			if($this->InjectFile($filename, $sourceDate)){
				@unlink($filename);return true;}else{return false;}
		}
	
		$basename=basename($filename);
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
		if(!@copy($filename, $DestinationFile)){
			@unlink($DestinationFile);
			rotate_events("Failed to copy $filename => $DestinationFile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}
	
		if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
	
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$basenameFF=$basename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".time().".$ext";
		$filesize=$unix->file_size($filename);
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
		@unlink($filename);
		return true;
	}	
	
	
	public function InjectFile($filepath,$sourceDate){
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$LogRotatePathWork="$this->LogRotatePath/work";
		@mkdir("$this->LogRotatePath/work",0755);
		if(function_exists("rotate_events")){rotate_events("Info: want to backup $filepath ($sourceDate)",__FUNCTION__,__FILE__,__LINE__,"logrotate");}
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
		
		$this->events("Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate");
		
		if($this->MySQLSyslogType==1){
			$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( LOAD_FILE('$DestinationFile') )";
			
		}
		if($this->MySQLSyslogType==2){
			$fcontent=mysql_escape_string(file_get_contents($DestinationFile));
			$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( '$fcontent' )";
			
			
		}
		
		$this->QUERY_SQL($sql);
		$fcontent=null;
		
		if(!$this->ok){
			@unlink("$DestinationFile");
			if(function_exists("rotate_events")){rotate_events("Failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"logrotate");}
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
		$sql="INSERT INTO files_info (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){
			if(function_exists("rotate_events")){rotate_events("Failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"logrotate");}
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM files_store WHERE ID=$ID");
			return false;
		}
		if(function_exists("rotate_events")){rotate_events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__FILE__,__LINE__,"logrotate");}
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
	
}
