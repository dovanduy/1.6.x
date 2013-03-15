<?php

class SugarCRM_install{
	var $groupware;
	var $ou;
	var $www_dir;
	var $ServerPort;	
	var $servername;
	var $database;
	var $uid;
	var $rebuildb=false;
	var $mysql_password=null;	
	var $mysql_username=null;
	var $mysql_instance_id=0;
	var $useSSL=0;
	function SugarCRM_install($servername){
		if($servername<>null){$this->servername=$servername;}
		$this->load();
	}
	
	private function Load(){
			$sql="SELECT * from freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$this->groupware=$ligne["groupware"];
			$this->servername=$ligne["servername"];
			$this->ou=$ligne["ou"];
			$this->www_dir=$ligne["www_dir"];
			$this->ServerPort=$ligne["ServerPort"];
			$this->mysql_password=$ligne["mysql_password"];
			$this->mysql_username=$ligne["mysql_username"];	
			$this->mysql_instance_id=$ligne["mysql_instance_id"];	
			$this->useSSL=$ligne["useSSL"];	
			if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
			writelogs("[$this->servername] SUGARCRM mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$this->database="sugar".time();
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			$this->uid=$ligne["uid"];
			
	}
	
	
	private function CheckDatabase(){
		$q=new mysql();
		if($this->mysql_instance_id>0){$q=new mysql_multi($this->mysql_instance_id);}
		if(!$q->DATABASE_EXISTS($this->database)){writelogs("Install SugarCRM sub-system mysql database $server_database...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);$q->CREATE_DATABASE($this->database);}
	}
	public function CheckInstall(){
	$unix=new unix();
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$cp=$unix->find_program("cp");
	$chown=$unix->find_program("chown");
	$this->CheckDatabase();
	$root=$this->www_dir;
	if(!$this->isFirstInstalled()){
		writelogs("Install SugarCRM installing source code Apache username=$apacheusername",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$cp -rf /usr/local/share/artica/sugarcrm_src/* $root/");
	}
	
	if(!is_file("$root/install/siteConfig_a.php")){
		writelogs("Install SugarCRM installing install directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$cp -rf /usr/local/share/artica/sugarcrm_src/install $root/");
	}
	
	if(!is_file("$this->www_dir/config_si.php")){
		writelogs("Install SugarCRM installing silent installer",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		//$this->CreateAutoInstaller();
	}

	writelogs("Install Checking permissions",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	@chmod($root, 0755);
	@chmod("$root/cache", 0777);
	@mkdir("$root/cache/upload/import",0777,true);
	@chown($root, $apacheusername);
	shell_exec("$chown -R $apacheusername $root");
	$this->CreateAdds();
	//$this->CreateAutoInstaller();

	}
	
	
	private function CreateAdds(){
		$f[]="<?php";
		$f[]="\$sugar_config['http_referer']['list'][] = '$this->servername';";
		$f[]="\$sugar_config['http_referer']['actions'] =array( 'index', 'ListView', 'DetailView', 'EditView', 'oauth', 'authorize', 'Authenticate', 'Login', 'SupportPortal', 'AdminWizard', 'index', 'ListView', 'DetailView', 'EditView', 'oauth', 'authorize', 'Authenticate', 'Login', 'SupportPortal', 'SaveTimezone', 'index', 'ListView', 'DetailView', 'EditView', 'oauth', 'authorize', 'Authenticate', 'Login', 'SupportPortal', 'Save', 'index', 'ListView', 'DetailView', 'EditView', 'oauth', 'authorize', 'Authenticate', 'Login', 'SupportPortal', 'delete', 'index', 'ListView', 'DetailView', 'EditView', 'oauth', 'authorize', 'Authenticate', 'Login', 'SupportPortal', 'UpgradeWizard' );"; 
		$f[]="?>";
		@file_put_contents("$this->www_dir/config_override.php",@implode("\n", $f));
		
	}
	
	public function GetVersion(){
		$filename="$this->www_dir/sugar_version.php";
		if(!is_file($filename)){return;}
		$f=file($filename);
		while (list ($index,$line) = each ($f) ){
			if(preg_match("#\$sugar_version.+?([0-9\.a-z]+)#", $line,$re)){
				return $re[1];
			}
		}
	}
	
	
	public function CreateAutoInstaller(){
		return;
		$f=file("$this->www_dir/config.php");
		if(count($f)>20){return;}
		
		$unix=new unix();
		$sock=new sockets();
		
		$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
		$FreeWebListen=$unix->APACHE_ListenDefaultAddress();
		if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
		if(!is_numeric($FreeWebListen)){$FreeWebListen=80;}
		$listen=$FreeWebListen;
		if($this->useSSL==1){$listen=$FreeWebListenSSLPort;}	
		$q=new mysql();
		$SocketPath=$q->mysql_server;
		$mysql_admin=$q->mysql_admin;
		$mysql_password=$q->mysql_password;		
		
		if($this->mysql_instance_id>0){
			$q=new mysql_multi($this->mysql_instance_id);
			$mysql_admin=$q->mysql_admin;
			$mysql_password=$q->mysql_password;
			$SocketPath="127.0.0.1";
			$dbport=$q->mysql_port;
		}
		
		if($this->mysql_username<>null){$mysql_admin=$this->mysql_username;}
		if($this->mysql_password<>null){$mysql_password=$this->mysql_password;}
		
		$f[]="<?php";
		$f[]="\$sugar_config = array (";
		$f[]="'dbconfig' => 
  array (
    'db_host_name' => '$SocketPath',
    'db_host_instance' => 'SQLEXPRESS',
    'db_user_name' => '$mysql_admin',
    'db_password' => '$mysql_password',
    'db_name' => '$this->database',
    'db_type' => 'mysql',
    'db_port' => '$dbport',
    'db_manager' => 'MysqliManager',
  ));";
		
$f[]="?>";		
		@file_put_contents("$this->www_dir/config.php",@implode("\n", $f));
		
		
		
		$f[]="<?php";
		$f[]="\$sugar_config_si = array (";
		$f[]="    'setup_db_host_name' => '$SocketPath',";
		$f[]="    'setup_db_database_name' => '$this->database',";
		$f[]="    'setup_db_drop_tables' => 0,";
		$f[]="    'setup_db_create_database' => 1,";
		$f[]="    'setup_db_pop_demo_data' => 0,";
		$f[]="    'setup_site_admin_password' => '$mysql_password',";
		$f[]="    'setup_db_create_sugarsales_user' => 0,";
		$f[]="    'setup_db_admin_user_name' => '$mysql_admin',";
		$f[]="    'setup_site_admin_user_name' => '$mysql_admin',";
		$f[]="    'setup_db_admin_password' => '$mysql_password',";
		$f[]="    'setup_db_sugarsales_user' => '$mysql_admin',";
		$f[]="    'setup_db_sugarsales_password' => '$mysql_password',";
		$f[]="    'setup_db_use_mb_demo_data'=>'yes',";
		$f[]="    'setup_db_pop_demo_data'=>'yes',";
		$f[]="    'setup_db_type' => 'mysql',";
		$f[]="    'db_type' => 'mysql',";
		$f[]="    'setup_license_key_users' => \${slkeyusers},";
		$f[]="    'setup_license_key_expire_date' => '\${slkeyexpiredate}',";
		$f[]="    'setup_license_key_oc_licences' => \${slkey_oc_licenses},";
		$f[]="    'setup_license_key' => '\${slkey}',";
		$f[]="    'setup_site_url' => 'http://$this->servername:$listen',";
		$f[]="    'setup_system_name' => '$this->servername',";
		$f[]="    'default_currency_iso4217' => 'USD',";
		$f[]="    'default_currency_name' => 'US Dollars',";
		$f[]="    'default_currency_significant_digits' => '2',";
		$f[]="    'default_currency_symbol' => '\$',";
		$f[]="    'default_date_format' => 'Y-m-d',";
		$f[]="    'default_time_format' => 'H:i',";
		$f[]="    'default_decimal_seperator' => '.',";
		$f[]="    'default_export_charset' => 'ISO-8859-1',";
		$f[]="    'default_language' => 'en_us',";
		$f[]="    'default_locale_name_format' => 's f l',";
		$f[]="    'default_number_grouping_seperator' => ',',";
		$f[]="    'export_delimiter' => ',',";
		$f[]=");";
		$f[]="?>";
		@file_put_contents("$this->www_dir/config_si.php",@implode("\n", $f));
		
		
		
		
	}
	
	
	
	public function isFirstInstalled(){
		$installed=true;
		$conf[]="leadCapture.php";
		$conf[]="HandleAjaxCall.php";
		$conf[]="cron.php";
		$conf[]="vcal_server.php";
		$conf[]="json_server.php";
		
		while (list ($none, $xpath) = each ($conf) ){
			if(!is_file("$this->www_dir/$xpath")){
				writelogs("Install SugarCRM $this->www_dir/$xpath no such file...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
		}
		
		return true;
		
	}	
	
	

	
	
}

?>