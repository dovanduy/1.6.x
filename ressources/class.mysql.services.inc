<?php
class mysql_services{
	public $TokenParams=null;
	public $SERV_NAME=null;
	public $WORKDIR=null;
	public $MYSQL_PID_FILE=null;
	public $MYSQL_SOCKET=null;
	private $topCopyMysql=array();
	public $InnoDB=false;
	public $INSTALL_DATABASE=true;
	public $MYSQL_BIN_DAEMON_PATH=null;
	public $MYSQL_ERRMSG=null;
	public $OutputBinLog=null;
	function mysql_services(){
		$this->topCopyMysql["host.frm"]=true;
		$this->topCopyMysql["host.MYD"]=true;
		$this->topCopyMysql["host.MYI"]=true;
		
		$this->topCopyMysql["user.MYI"]=true;
		$this->topCopyMysql["user.MYD"]=true;
		$this->topCopyMysql["user.frm"]=true;
		
		$this->topCopyMysql["servers.frm"]=true;
		$this->topCopyMysql["servers.MYD"]=true;
		$this->topCopyMysql["servers.MYI"]=true;
		
		$this->topCopyMysql["plugin.MYI"]=true;
		$this->topCopyMysql["plugin.MYD"]=true;
		$this->topCopyMysql["plugin.frm"]=true;
		
		$this->topCopyMysql["db.frm"]=true;
		$this->topCopyMysql["db.MYD"]=true;
		$this->topCopyMysql["db.MYI"]=true;		
		
		$this->topCopyMysql["tables_priv.frm"]=true;
		$this->topCopyMysql["tables_priv.MYD"]=true;
		$this->topCopyMysql["tables_priv.MYI"]=true;
		$this->topCopyMysql["columns_priv.frm"]=true;
		$this->topCopyMysql["columns_priv.MYD"]=true;
		$this->topCopyMysql["columns_priv.MYI"]=true;
		$this->topCopyMysql["procs_priv.frm"]=true;
		$this->topCopyMysql["procs_priv.MYD"]=true;
		$this->topCopyMysql["procs_priv.MYI"]=true;		
		
		$this->topCopyMysql["plugin.frm"]=true;
		$this->topCopyMysql["plugin.MYD"]=true;
		$this->topCopyMysql["plugin.MYI"]=true;
		
		$this->topCopyMysql["user.frm"]=true;
		$this->topCopyMysql["user.MYD"]=true;
		$this->topCopyMysql["user.MYI"]=true;
		
		$this->topCopyMysql["db.frm"]=true;
		$this->topCopyMysql["db.MYD"]=true;
		$this->topCopyMysql["db.MYI"]=true;		
		
		
	}
	
	
	private function isFirstInstall(){
		if(!$this->INSTALL_DATABASE){return false;}
		$array=$this->topCopyMysql;
		$WORKDIR=$this->WORKDIR;
		while (list ($filename, $ligne) = each ($array) ){
			if(!is_file("$WORKDIR/data/mysql/$filename")){return true;}
				
				
		}		
		
	}
	
	private function install_databases(){
		if(!$this->INSTALL_DATABASE){return false;}
		$lc_messages_dir=true;
		$mysqld_variables_cmdline=$this->GetStartedValues();
		$unix=new unix();
		$mysql_install_db=$unix->find_program("mysql_install_db");
		$WORKDIR=$this->WORKDIR;
		
		if(!is_file("$WORKDIR/my.cnf")){
			@file_put_contents("$WORKDIR/my.cnf", "\n");
		}
		
		if(!isset($mysqld_variables_cmdline["--lc-messages-dir"])){$lc_messages_dir=false;}
		if($lc_messages_dir){$lc_messages_dir_cmd=" --lc-messages-dir=";}
		$mysql_install_db_cmds="$mysql_install_db --defaults-file=$WORKDIR/my.cnf --user=root --datadir=\"$WORKDIR/data\"$lc_messages_dir_cmd";
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME `$mysql_install_db_cmds`\n";}
		shell_exec($mysql_install_db_cmds);
		
		$array=$this->topCopyMysql;
		$WORKDIR=$this->WORKDIR;
		while (list ($filename, $ligne) = each ($array) ){
			if(!is_file("$WORKDIR/data/mysql/$filename")){
				if(is_file("/var/lib/mysql/mysql/$filename")){
					@copy("/var/lib/mysql/mysql/$filename", "$WORKDIR/data/mysql/$filename");
				}
			}
		
		
		}		
		
		
	}
	
	
	
	public function BuildParams(){
		$memory=$this->get_memory();
		$swap=$this->get_swap();
		$sock=new sockets();
		$unix=new unix();
		$SERV_NAME=$this->SERV_NAME;
		$WORKDIR=$this->WORKDIR;
		$mysql_pid_file=$this->MYSQL_PID_FILE;
		$MYSQL_SOCKET=$this->MYSQL_SOCKET;
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Server available memory `{$memory}MB`\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Server available swap `{$swap}MB`\n";}
		$max_allowed_packetd=0;
		$bulk_insert_buffer_sized=0;
		$key_buffer_size_d=0;
		$thread_cache_sized=0;
		$tmp_table_sized=0;
		$mysql_install_db=$unix->find_program("mysql_install_db");
		$GetStartedValues=$this->GetStartedValues();
		
		if($this->MYSQL_BIN_DAEMON_PATH<>null){
			$mysqld=$this->MYSQL_BIN_DAEMON_PATH;}
		else{
			$mysqld=$unix->find_program("mysqld");
		}
		
		$lnbin=$unix->find_program("ln");
		
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO($this->TokenParams)));
		$query_cache_size=$TuningParameters["query_cache_size"];
		$max_allowed_packet=$TuningParameters["max_allowed_packet"];
		$max_connections=$TuningParameters["max_connections"];
		$connect_timeout=$TuningParameters["connect_timeout"];
		$interactive_timeout=$TuningParameters["interactive_timeout"];
		$key_buffer_size=$TuningParameters["key_buffer_size"];
		$table_open_cache=$TuningParameters["table_open_cache"];
		$myisam_sort_buffer_size=$TuningParameters["myisam_sort_buffer_size"];
		$bulk_insert_buffer_size=$TuningParameters["bulk_insert_buffer_size"];
		$tmp_table_size=$TuningParameters["tmp_table_size"];
		$thread_cache_size=$TuningParameters["thread_cache_size"];
		$ListenPort=$TuningParameters["ListenPort"];
		$read_rnd_buffer_size=$TuningParameters["read_rnd_buffer_size"];
		$net_read_timeout=$TuningParameters["net_read_timeout"];
		
		$read_buffer_size=$TuningParameters["read_buffer_size"];
		$sort_buffer_size=$TuningParameters["sort_buffer_size"];
		$thread_stack=$TuningParameters["thread_stack"];
		$join_buffer_size=$TuningParameters["join_buffer_size"];
		$max_tmp_table_size=$TuningParameters["max_tmp_table_size"];		
		$innodb_buffer_pool_size=$TuningParameters["innodb_buffer_pool_size"];	
		$innodb_log_buffer_size=$TuningParameters["innodb_log_buffer_size"];
		$skip_innodb_stats_on_metadata=$SquidDBTuningParameters["skip_innodb_stats_on_metadata"];
		
		if(!is_numeric($ListenPort)){$ListenPort=0;}
		$tmpdir=$TuningParameters["tmpdir"];
		
		$net="--skip-networking";
		
		if($tmpdir==null){$tmpdir="/tmp";}
		
		if($ListenPort>0){
			$net="--port=$ListenPort --skip-name-resolve";
		}
		
		if($memory>512){
			$bulk_insert_buffer_sized=8;$key_buffer_size_d=8;$max_allowed_packetd=50;$thread_cache_sized=2;
			$tmp_table_sized=8;
			$innodb_buffer_pool_sized=128;
			$innodb_log_buffer_sized=1;
		}
		if($memory>1024){
				$bulk_insert_buffer_sized=16;
				$key_buffer_size_d=32;
				$innodb_buffer_pool_sized=128;
				$innodb_log_buffer_sized=4;
		
		}
		if($memory>1500){
			$bulk_insert_buffer_sized=20;
			$key_buffer_size_d=64;
			$thread_cache_sized=2;
			$tmp_table_sized=20;
			$tmp_table_sized=8;
			$innodb_buffer_pool_sized=256;
			$innodb_log_buffer_sized=6;
		}
		if($memory>2048){
			$bulk_insert_buffer_sized=32;
			$key_buffer_size_d=128;
			$tmp_table_sized=64;
			$tmp_table_sized=16;
			$innodb_buffer_pool_sized=256;
			$innodb_log_buffer_sized=6;
		}
		if($memory>2500){
			$bulk_insert_buffer_sized=164;
			$key_buffer_size_d=256;
			$thread_cache_sized=10;
			$tmp_table_sized=16;
			$innodb_buffer_pool_sized=384;
			$innodb_log_buffer_sized=8;
		}
		if($memory>3000){
			$bulk_insert_buffer_sized=196;
			$key_buffer_size_d=256;
			$thread_cache_sized=20;
			$tmp_table_sized=16;
			$innodb_log_buffer_sized=14;
		}
		if($memory>3500){
			$bulk_insert_buffer_sized=200;
			$key_buffer_size_d=256;
			$tmp_table_sized=32;
			$innodb_log_buffer_sized=16;
		}
		if($memory>4000){
			$bulk_insert_buffer_sized=204;
			$key_buffer_size_d=300;
			$max_allowed_packetd=100;
			$thread_cache_sized=64;
			$tmp_table_sized=64;
			$innodb_buffer_pool_size=512;
			$innodb_log_buffer_size=18;
		}
		
		if(!is_numeric($bulk_insert_buffer_size)){$bulk_insert_buffer_size=$bulk_insert_buffer_sized;}
		if(!is_numeric($key_buffer_size)){$key_buffer_size=$key_buffer_size_d;}
		if(!is_numeric($myisam_sort_buffer_size)){$myisam_sort_buffer_size=$key_buffer_size_d;}
		if(!is_numeric($thread_cache_size)){$thread_cache_size=$thread_cache_sized;}
		$read_rnd_buffer_sized=round($memory/1000);
		
		
		if(!is_numeric($interactive_timeout)){$interactive_timeout=57600;}
		if(!is_numeric($connect_timeout)){$connect_timeout=60;}
		if(!is_numeric($max_connections)){$max_connections=60;}
		if(!is_numeric($max_allowed_packet)){$max_allowed_packet=100;}
		if(!is_numeric($query_cache_size)){$query_cache_size=8;}
		if(!is_numeric($table_open_cache)){$table_open_cache=256;}
		if(!is_numeric($tmp_table_size)){$tmp_table_size=$tmp_table_sized;}
		if(!is_numeric($read_rnd_buffer_size)){$read_rnd_buffer_size=$read_rnd_buffer_sized;}
		if($max_allowed_packet<100){$max_allowed_packet=100;}
		
		
		if(!is_numeric($read_buffer_size)){$read_buffer_size=0;}
		if(!is_numeric($sort_buffer_size)){$sort_buffer_size=0;}
		if(!is_numeric($join_buffer_size)){$join_buffer_size=0;}
		if(!is_numeric($max_tmp_table_size)){$max_tmp_table_size=0;}
		if(!is_numeric($thread_stack)){$thread_stack=0;}
		
		if(!is_numeric($innodb_buffer_pool_size)){$innodb_buffer_pool_size=$innodb_buffer_pool_sized;}
		if(!is_numeric($innodb_log_buffer_size)){$innodb_log_buffer_size=$innodb_log_buffer_sized;}
		if(!is_numeric($net_read_timeout)){$net_read_timeout=120;}
		if(!is_numeric($skip_innodb_stats_on_metadata)){$skip_innodb_stats_on_metadata=1;}
		
		
		
		$KERNEL_ARCH=$unix->KERNEL_ARCH();
		
		$KERNEL_ARCH=$unix->KERNEL_ARCH();
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Architecture: $KERNEL_ARCH bits\n";}
		if($unix->KERNEL_ARCH()==32){
			if($innodb_buffer_pool_size>3999){$innodb_buffer_pool_size=3999;}
			if($innodb_buffer_pool_size>$swap){$innodb_buffer_pool_size=$swap;}
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME MySQL Main binary........: $mysqld\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Working directory........: $WORKDIR\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Architecture.............: $KERNEL_ARCH bits\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Memory...................: {$memory}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Max allowed packet.......: {$max_allowed_packet}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Max connections..........: {$max_connections} cnxs\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Key Buffer size..........: {$key_buffer_size}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Sort Buffer size.........: {$myisam_sort_buffer_size}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Bulk Insert Buffer Size..: {$bulk_insert_buffer_size}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Tables Open cache........: {$table_open_cache} tables\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Thread Cache Size........: {$thread_cache_size}\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME TMP Table size...........: {$tmp_table_size}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Read RND Buffer Size.....: {$read_rnd_buffer_size}M\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Net Read Timeout.........: {$net_read_timeout} seconds\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME Skip innodb stats (metada: {$skip_innodb_stats_on_metadata}\n";}
		

		
		
		$CREATEDB=false;
		
		@mkdir("$WORKDIR/bin",0755,true);
		@mkdir("$WORKDIR/data",0755,true);
		@mkdir("$WORKDIR/data/mysql",0755,true);
		@mkdir("$WORKDIR/lib/plugin",0755,true);
		

		
		
		
		if(!is_file("$WORKDIR/my.cnf")){@file_put_contents("$WORKDIR/my.cnf", "\n");}
		
		if(!is_file("$WORKDIR/bin/my_print_defaults")){
			$my_print_defaults=$unix->find_program("my_print_defaults");
			shell_exec("$lnbin -s $my_print_defaults $WORKDIR/bin/my_print_defaults");
		}
		
		@mkdir("$WORKDIR/share/mysql/english",0755,true);
		$this->check_errmsgsys();		
		if($this->INSTALL_DATABASE){
			if($this->isFirstInstall()){
				$this->install_databases();
				if($this->isFirstInstall()){
					if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME creating databases failed..\n";}
				}
			}
		}
		

		$f[]="$mysqld";
		$f[]="--defaults-file=$WORKDIR/my.cnf ";
		if(!$this->InnoDB){
			if($GetStartedValues["--innodb-file-per-table"]){
				$f[]="--innodb=OFF";
			}
		}

		$f[]="--user=root";
		$f[]="--pid-file=$mysql_pid_file";
		$f[]="--basedir=$WORKDIR";
		$f[]="--datadir=$WORKDIR/data";
		//$f[]="--plugin_dir=$WORKDIR/lib/plugin";
		$f[]="--socket=$MYSQL_SOCKET";
		$f[]="--general-log-file=$WORKDIR/general_log.log";
		$f[]="--slow-query-log-file=$WORKDIR/slow-query.log";
		$f[]="--log-error=$WORKDIR/error.log";
		
		if($this->InnoDB){
			if($GetStartedValues["--innodb-file-per-table"]){
				$f[]="--innodb-file-per-table";
			}
			
			if($GetStartedValues["--innodb-buffer-pool-size"]){
				$f[]="--innodb-buffer-pool-size={$innodb_buffer_pool_size}M";
			}
			if($GetStartedValues["--innodb-log-buffer-size"]){
				$f[]="--innodb-log-buffer-size={$innodb_log_buffer_size}M";
			}
			if($GetStartedValues["--innodb-flush-log-at-trx-commit"]){$f[]="--innodb-flush-log-at-trx-commit=0";}
		}
		
		if($max_allowed_packet>0){
			$f[]="--max-allowed-packet={$max_allowed_packet}M";
		}
		
		if($max_connections>0){
			$f[]="--max-connections={$max_connections}";
		}
		if($connect_timeout>0){
			$f[]="--connect_timeout={$connect_timeout}";
		}
		if($interactive_timeout>0){
			$f[]="--interactive_timeout={$interactive_timeout}";
		}
		$f[]="--myisam_repair_threads=4";
		if($key_buffer_size>0){
			$key_buffer_size=($key_buffer_size*1024)*1000;
			$f[]="--key_buffer_size={$key_buffer_size}";
		}
		
		$f[]="--query_cache_type=1";
		if($table_open_cache>0){
			$f[]="--table_open_cache={$table_open_cache}";
		}
		
		$f[]="--myisam_use_mmap=0";
		$f[]="--max_user_connections=0";
		if($myisam_sort_buffer_size>0){
			$myisam_sort_buffer_size=($myisam_sort_buffer_size*1024)*1000;
			$f[]="--myisam_sort_buffer_size={$myisam_sort_buffer_size}";
		}
		if($bulk_insert_buffer_size>0){
			$bulk_insert_buffer_size=($bulk_insert_buffer_size*1024)*1000;
			$f[]="--bulk_insert_buffer_size={$bulk_insert_buffer_size}";
		}
		
		if($read_rnd_buffer_size>1){
			$read_rnd_buffer_size=($read_rnd_buffer_size*1024)*1000;
			$f[]="--read_rnd_buffer_size={$read_rnd_buffer_size}";
		}
		
		if($thread_cache_size>0){
			$f[]="--thread_cache_size=$thread_cache_size";
		}
		
		if($tmp_table_size>0){
			$tmp_table_size=($tmp_table_size*1024)*1000;
			$f[]="--tmp_table_size={$tmp_table_size}";
			$f[]="--max_heap_table_size={$tmp_table_size}";
		}
		
		if($max_tmp_table_size>0){
			if($GetStartedValues["--max_tmp_table_size"]){
				$max_tmp_table_size=($max_tmp_table_size*1024)*1000;
				$f[]="--max_tmp_table_size={$max_tmp_table_size}";
			}
		}
		if($sort_buffer_size>0){
			$sort_buffer_size=($sort_buffer_size*1024)*1000;
			$f[]="--sort_buffer_size={$sort_buffer_size}";
		}
		if($read_buffer_size>0){
			$read_buffer_size=($read_buffer_size*1024)*1000;
			$f[]="--read_buffer_size={$read_buffer_size}";
		}
		if($join_buffer_size>0){
			$join_buffer_size=($join_buffer_size*1024)*1000;
			$f[]="--join_buffer_size={$join_buffer_size}";
		}
		
		if($thread_stack>0){
			$thread_stack=($thread_stack*1024)*1000;
			$f[]="--thread_stack={$thread_stack}";
		}
		
		if($net_read_timeout>0){
			if($GetStartedValues["--net_read_timeout"]){
				$f[]="--net_read_timeout={$net_read_timeout}";
			}
		}
		
		if($GetStartedValues["--skip-innodb-stats-on-metadata"]){
			if($skip_innodb_stats_on_metadata==1){
				$f[]="--skip-innodb-stats-on-metadata";
			}
		}
		
		$f[]="--tmpdir=$tmpdir";
		$f[]="--log-warnings=2";
		if(!$this->InnoDB){
			$f[]="--default-storage-engine=myisam";
			if($GetStartedValues["--default-tmp-storage-engine"]){$f[]="--default-tmp-storage-engine=myisam";}
		}else{
			$f[]="--default-storage-engine=innodb";
		}
			$f[]=$net;
		
		$cmdline=@implode(" ", $f);
		return $cmdline;
		
		
		
	}
	
	private function check_errmsgsys(){
		$Get_errmsgsys=$this->Get_errmsgsys();
		if(!is_file("$this->WORKDIR/share/mysql/english/errmsg.sys")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME Creating errmsg.sys\n";}
			if(is_file($Get_errmsgsys)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME copy $Get_errmsgsys -> $this->WORKDIR/share/mysql/english/errmsg.sys\n";}
				copy($Get_errmsgsys, "$this->WORKDIR/share/mysql/english/errmsg.sys");
				copy($Get_errmsgsys, "$this->WORKDIR/share/errmsg.sys");
				
				
				
			}else{
				file_put_contents("$this->WORKDIR/share/mysql/english/errmsg.sys", "\n");
			}
		
		
		}
		
		if(!is_file("$this->WORKDIR/share/errmsg.sys")){
			if(is_file($Get_errmsgsys)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME copy $Get_errmsgsys -> $this->WORKDIR/share/errmsg.sys\n";}
				copy($Get_errmsgsys, "$this->WORKDIR/share/errmsg.sys");
				
			}
		}
		
	}
	
	public function CheckOutputErrors($filename){
		$f=explode("\n",@file_get_contents($filename));
		if($this->WORKDIR==null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME fatal no directory set Line:".__LINE__."\n";}
			return;
		}
		
		$repair=false;
		
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#Error message file.*?errmsg\.sys.*?had only [0-9]+ error messages#", $ligne)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME errmsg.sys issue (should the MySQL as been upgraded ?)\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME unlink $this->WORKDIR/share/mysql/english/errmsg.sys\n";}
				@unlink("$this->WORKDIR/share/mysql/english/errmsg.sys");
				$this->check_errmsgsys();
				continue;
			}
			
			if(strpos($ligne,"libstdc++.so.6: cannot open shared object file")>0){$repair=true;continue;}
			if(strpos($ligne,"libgcc_s.so.1: cannot open shared object file")>0){$repair=true;continue;}
		}
		
		
		if($repair){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $this->SERV_NAME libstdc++.so.6 or libgcc_s.so.1 issue, repair it...\n";}
			if(is_file("/opt/articatech/bin/libstdc++.so.6")){
				if(!is_file("/usr/lib/libstdc++.so.6")){shell_exec("/bin/ln -s /opt/articatech/bin/libstdc++.so.6 /usr/lib/libstdc++.so.6");}
			}
			if(is_file("/opt/articatech/bin/libgcc_s.so.1")){
				if(!is_file("/usr/lib/libgcc_s.so.1")){shell_exec("/bin/ln -s /opt/articatech/bin/libgcc_s.so.1 /usr/lib/libgcc_s.so.1");}
			}
		}
		
		
		
	}
	
	
	private function Get_errmsgsys(){
		$unix=new unix();
		if($this->MYSQL_ERRMSG){
			return $this->WORKDIR."/".$this->MYSQL_ERRMSG;
		}
		$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
		$f[]="/usr/share/mysql/english/errmsg.sys";
		
		$f[]="/opt/articatech/mysql/share/english/errmsg.sys";
		while (list ($num, $ligne) = each ($f) ){
			if(is_file($ligne)){return $ligne;}
		}
	
	}	
	private function GetStartedValues(){
		$unix=new unix();
		if($this->MYSQL_BIN_DAEMON_PATH<>null){
			$mysqld=$this->MYSQL_BIN_DAEMON_PATH;}
		else{
			$mysqld=$unix->find_program("mysqld");
		}
		exec("$mysqld --help --verbose 2>&1",$results);
	
		while (list ($key, $valueN) = each ($results) ){
			if(preg_match("#--([a-z\-\_\=]+)\s+(.+)#", $valueN,$re)){
				$key=trim($re[1]);
				$value=trim($re[2]);
				$array["--$key"]=true;
			}
				
		}
	
		return $array;
	}	
	
	private function get_memory(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		
		while (list ($num, $ligne) = each ($results) ){
			
			if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){
				return $re[1];
			}
		}
		
		return 0;
	}
	
	private function get_swap(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
	
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){
				return $re[1];
			}
		}
	
		return 0;
	}	
	
}