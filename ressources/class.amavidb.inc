<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');



class amavisdb{
	private $ClassSQL;
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	private $database="amavis";
	private $mysql_connection;
	public $SocketName="";
	public $ok=false;
	public $mysql_error;
	public $last_id=0;
	public $dbidsn="";
	private $AmavisPerUser=0;
	
	
	
	function amavisdb(){
		$this->PrepareMySQLClass();
		
		if(!$this->DATABASE_EXISTS("amavis")){
			$this->CREATE_DATABASE("amavis");
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		$this->AmavisPerUser=$sock->GET_INFO("AmavisPerUser");
		if(!is_numeric($this->AmavisPerUser)){$this->AmavisPerUser=0;}
		$def["SocketPath"]="/var/run/mysqld/amavis-db.sock";
		$def["mysql_admin"]="root";
		$def["mysql_password"]=null;
		$def["TryTCP"]=false;
		$this->SocketName=$def["SocketPath"];
		$this->mysql_admin=$def["mysql_admin"];
		$this->mysql_password=$def["mysql_password"];
		$this->mysql_server="127.0.0.1";
		$this->ClassSQL=new mysql($def);
		$this->MYSQL_CMDLINES="--protocol=socket --socket={$def["SocketPath"]} -u root";
		
		if($this->mysql_server<>"127.0.0.1"){
			$this->dbidsn="['DBI:mysql:database=amavis;host=$this->mysql_server;port=3306', '$this->mysql_admin', '$this->mysql_password']";
		}else{
			$this->dbidsn="['DBI:mysql:database=amavis;mysql_socket=$this->SocketName', '$this->mysql_admin', '$this->mysql_password']";
		}
		
		return;
	}
	
	private function DATABASE_EXISTS($database){
		if($this->AmavisPerUser==0){return;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	private FUNCTION CREATE_DATABASE($database){
		if($this->AmavisPerUser==0){return;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	private function BD_CONNECT(){
		if($this->AmavisPerUser==0){return;}
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
	
	public function QUERY_SQL($sql,$database=null){
		
		if($this->AmavisPerUser==0){return;}
		if(!$this->BD_CONNECT()){return false;}
		if($database==null){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	public function FIELD_EXISTS($table,$field,$database=null){
		if($this->AmavisPerUser==0){return;}
		$database=$this->database;
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}	
	
	public function policyid_from_mail($email){
		$sql="SELECT policy_id FROM users WHERE email='$email'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!is_numeric($ligne["policy_id"])){$ligne["policy_id"]=0;}
		return $ligne["policy_id"];
	}
	public function emailid_from_email($email){
		$sql="SELECT id FROM users WHERE email='$email'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
		return $ligne["id"];		
	}
	public function email_from_emailid($id){
		$sql="SELECT email FROM users WHERE id='$id'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["email"];
	}	
	public function email_from_mailid($id){
		$sql="SELECT email FROM mailaddr WHERE id='$id'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["email"];		
	}
	public function emailid_from_mailaddr($email){
		$sql="SELECT id FROM mailaddr WHERE email='$email'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
		return $ligne["id"];		
	}	
	
	
	public function policy_array($id){
		$sql="SELECT policy_name,spam_tag_level,spam_tag2_level,spam_kill_level FROM policy WHERE id='$id'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return array(
					"policy_name"=>$ligne["policy_name"],
					"spam_tag2_level"=>$ligne["spam_tag2_level"],
					"spam_kill_level"=>$ligne["spam_kill_level"],
					"spam_tag_level"=>$ligne["spam_tag_level"],
					);
			}

	public function checkTables(){
		if($this->AmavisPerUser==0){return;}
		if(!$this->BD_CONNECT()){return false;}
		$sql="CREATE TABLE IF NOT EXISTS users (
  			id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  			priority   integer      NOT NULL DEFAULT '7',
  			policy_id  integer unsigned NOT NULL DEFAULT '1',
  			email      varbinary(255) NOT NULL UNIQUE,
			uid   varchar(255) DEFAULT NULL,
  			fullname   varchar(255) DEFAULT NULL,
  			local   char(1),
			KEY `uid` (`uid`)
			);";
		
		$this->QUERY_SQL($sql);

		
		$sql="CREATE TABLE IF NOT EXISTS mailaddr (
  				id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  				priority   integer      NOT NULL DEFAULT '7',
  				email      varbinary(255) NOT NULL UNIQUE
				);";
		$this->QUERY_SQL($sql);
		
		
		$sql="CREATE TABLE IF NOT EXISTS wblist (
  			rid        integer unsigned NOT NULL,  -- recipient: users.id
  			sid        integer unsigned NOT NULL,  -- sender: mailaddr.id
  			wb         varchar(10)  NOT NULL,  -- W or Y / B or N / space=neutral / score
  			PRIMARY KEY (rid,sid)
			);";
		$this->QUERY_SQL($sql);
		
		$sql="CREATE TABLE IF NOT EXISTS policy (
  		  id  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, -- 'id' this is the _only_ required field
		  policy_name      varchar(32),     -- not used by amavisd-new, a comment
  		  virus_lover           char(1) default NULL,     -- Y/N
  		  spam_lover            char(1) default NULL,     -- Y/N
		  unchecked_lover       char(1) default NULL,     -- Y/N
		  banned_files_lover    char(1) default NULL,     -- Y/N
		  bad_header_lover      char(1) default NULL,     -- Y/N
		
		  bypass_virus_checks   char(1) default NULL,     -- Y/N
		  bypass_spam_checks    char(1) default NULL,     -- Y/N
		  bypass_banned_checks  char(1) default NULL,     -- Y/N
		  bypass_header_checks  char(1) default NULL,     -- Y/N
		  spam_modifies_subj    char(1) default NULL,     -- Y/N

  		  virus_quarantine_to      varchar(64) default NULL,
		  spam_quarantine_to       varchar(64) default NULL,
		  banned_quarantine_to     varchar(64) default NULL,
		  unchecked_quarantine_to  varchar(64) default NULL,
		  bad_header_quarantine_to varchar(64) default NULL,
		  clean_quarantine_to      varchar(64) default NULL,
		  archive_quarantine_to    varchar(64) default NULL,
		
		  spam_tag_level  float default NULL, -- higher score inserts spam info headers
		  spam_tag2_level float default NULL, -- inserts 'declared spam' header fields
		  spam_tag3_level float default NULL, -- inserts 'blatant spam' header fields
		  spam_kill_level float default NULL, -- higher score triggers evasive actions
		                                      -- e.g. reject/drop, quarantine, ...
		                                     -- (subject to final_spam_destiny setting)
		  spam_dsn_cutoff_level        float default NULL,
		  spam_quarantine_cutoff_level float default NULL,
		
		  addr_extension_virus      varchar(64) default NULL,
		  addr_extension_spam       varchar(64) default NULL,
		  addr_extension_banned     varchar(64) default NULL,
		  addr_extension_bad_header varchar(64) default NULL,
		
		  warnvirusrecip      char(1)     default NULL, -- Y/N
		  warnbannedrecip     char(1)     default NULL, -- Y/N
		  warnbadhrecip       char(1)     default NULL, -- Y/N
		  newvirus_admin      varchar(64) default NULL,
		  virus_admin         varchar(64) default NULL,
		  banned_admin        varchar(64) default NULL,
		  bad_header_admin    varchar(64) default NULL,
		  spam_admin          varchar(64) default NULL,
		  spam_subject_tag    varchar(64) default NULL,
		  spam_subject_tag2   varchar(64) default NULL,
		  spam_subject_tag3   varchar(64) default NULL,
		  message_size_limit  integer     default NULL, -- max size in bytes, 0 disable
		  banned_rulenames    varchar(64) default NULL, -- comma-separated list of ...
		        -- names mapped through %banned_rules to actual banned_filename tables
		  disclaimer_options  varchar(64) default NULL,
		  forward_method      varchar(64) default NULL,
		  sa_userconf         varchar(64) default NULL,
		  sa_username         varchar(64) default NULL,
		  ou				  varchar(128) default NULL,
		);";		
		$this->QUERY_SQL($sql);		
		
		if(!$this->FIELD_EXISTS("policy", "ou")){
			$sql="ALTER TABLE `policy` ADD `ou` varchar(128) default NULL";
			$this->QUERY_SQL($sql);
		}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `maddr` (
		partition_tag integer      DEFAULT 0, -- see \$partition_tag
		id         bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		email      varbinary(255)  NOT NULL,  -- full mail address
		domain     varchar(255)    NOT NULL,  -- only domain part of the email address
		-- with subdomain fields in reverse
		CONSTRAINT part_email UNIQUE (partition_tag,email)
		) ENGINE=InnoDB;";

		$this->QUERY_SQL($sql);
		
		if(!$this->TABLE_EXISTS("msgs")){
		$sql="CREATE TABLE IF NOT EXISTS msgs (
				  partition_tag integer     DEFAULT 0,
				  mail_id     varbinary(16) NOT NULL,
				  secret_id   varbinary(16) DEFAULT '',
				  am_id       varchar(20)   NOT NULL,  
				  time_num    integer unsigned NOT NULL,
				  time_iso    char(16)      NOT NULL,
				  sid         bigint unsigned NOT NULL,
				  policy      varchar(255)  DEFAULT '',
				  client_addr varchar(255)  DEFAULT '',
				  size        integer unsigned NOT NULL,
				  originating char(1) DEFAULT ' ' NOT NULL,
				  content     char(1),                   
				  quar_type  char(1),
				  quar_loc   varbinary(255) DEFAULT '',
				  dsn_sent   char(1),                  
				  spam_level float,                    
				  message_id varchar(255)  DEFAULT '', 
				  from_addr  varchar(255)  CHARACTER SET utf8 COLLATE utf8_bin  DEFAULT '',
				  subject    varchar(255)  CHARACTER SET utf8 COLLATE utf8_bin  DEFAULT '',
				  host       varchar(255)  NOT NULL, 
				  PRIMARY KEY (partition_tag,mail_id),
				  FOREIGN KEY (sid) REFERENCES maddr(id) ON DELETE RESTRICT
				) ENGINE=InnoDB;";
			$this->QUERY_SQL($sql);	
			if($this->ok){
				$this->QUERY_SQL("CREATE INDEX msgs_idx_sid ON msgs (sid);");
				$this->QUERY_SQL("CREATE INDEX msgs_idx_mess_id  ON msgs (message_id);");
				$this->QUERY_SQL("CREATE INDEX msgs_idx_time_num ON msgs (time_num);");
			}
		}
		if(!$this->TABLE_EXISTS("msgrcpt")){
				$sql="CREATE TABLE msgrcpt (
					partition_tag integer    DEFAULT 0, 
					mail_id    varbinary(16) NOT NULL,  
					rseqnum    integer  DEFAULT 0   NOT NULL,
		  			rid        bigint unsigned NOT NULL,  
		  			is_local   char(1)  DEFAULT ' ' NOT NULL,
		  			content    char(1)  DEFAULT ' ' NOT NULL,
		  			ds         char(1)  NOT NULL,             
		  			rs         char(1)  NOT NULL,
		  			bl         char(1)  DEFAULT ' ',
		  			wl         char(1)  DEFAULT ' ',
		  			bspam_level float,
		  			smtp_resp  varchar(255)  DEFAULT '',
				    PRIMARY KEY (partition_tag,mail_id,rseqnum)
				    
				    
				) ENGINE=InnoDB;";
				
				//FOREIGN KEY (rid)     REFERENCES maddr(id)     ON DELETE RESTRICT,
				$this->QUERY_SQL($sql);
				if($this->ok){
					$this->QUERY_SQL("CREATE INDEX msgrcpt_idx_mail_id  ON msgrcpt (mail_id);");
					$this->QUERY_SQL("CREATE INDEX msgrcpt_idx_rid      ON msgrcpt (rid);");
					//$this->QUERY_SQL("CREATE FOREIGN KEY (mail_id) REFERENCES msgs(mail_id) ON DELETE CASCADE");
				}
				
		}
		
		
		$sql="CREATE TABLE IF NOT EXISTS quarantine (
		partition_tag integer    DEFAULT 0,    -- see \$partition_tag
		mail_id    varbinary(16) NOT NULL,     -- long-term unique mail id
		chunk_ind  integer unsigned NOT NULL,  -- chunk number, starting with 1
		mail_text  blob          NOT NULL,     -- store mail as chunks of octets
		PRIMARY KEY (partition_tag,mail_id,chunk_ind)
		
		) ENGINE=InnoDB;";
		$this->QUERY_SQL($sql);
		//FOREIGN KEY (mail_id) REFERENCES msgs(mail_id) ON DELETE CASCADE
		
		
	}
	
	public function TABLE_EXISTS($table,$database=null){
		if($this->AmavisPerUser==0){return;}
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_EXISTS($table,$database);
		
	
	}	
	
}
