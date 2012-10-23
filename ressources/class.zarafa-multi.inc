<?php

include_once(dirname(__FILE__)."/class.mysql-multi.inc");
include_once(dirname(__FILE__)."/class.mysql.inc");
include_once(dirname(__FILE__)."/class.ldap.inc");

class zarafamulti{
	var $instance_id=0;
	var $etcpath=null;
	var $usesocket=0;
	var $listen_addr=null;
	var $servername=null;
	var $server_tcp_port=237;
	var $mysql_instance_id=0;
	var $attachment_path;
	var $ou=null;
	var $lmtp_port=2005;
	var $server_bind="127.0.0.1";
	var $EnablePOP3=0;
	var $EnableIMAP=0;
	var $GatewayEnabled=0;
	var $enabled=0;
	var $DEFAULT_SERVER_SOCKET="";
	var $PostfixInstance="";

	function zarafamulti($instance_id=0){
		$this->instance_id=$instance_id;
		if($this->instance_id>0){$this->load();}
		
	}
	
	private function Load(){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM zarafamulti WHERE ID='$this->instance_id'","artica_backup"));
		$this->etcpath="/etc/zarafa-$this->instance_id";
		$this->usesocket=$ligne["usesocket"];
		$this->listen_addr=$ligne["listen_addr"];
		$this->servername=$ligne["servername"];
		$this->server_tcp_port=$ligne["listen_port"];
		$this->mysql_instance_id=$ligne["mysql_instance_id"];
		$this->attachment_path=$ligne["Dir"];
		$this->lmtp_port=$ligne["lmtp_port"];
		$this->ou=$ligne["ou"];
		if($this->attachment_path==null){$this->attachment_path="/var/lib/zarafa-$this->instance_id";}
		$params=unserialize(base64_decode($ligne["params"]));
		$this->EnablePOP3=$params["EnablePOP3"];
		$this->EnableIMAP=$params["EnableIMAP"];
		$this->enabled=$ligne["enabled"];
		if($this->EnableIMAP==1){$GatewayEnabled=1;}
		if($this->EnablePOP3==1){$GatewayEnabled=1;}
		$this->DEFAULT_SERVER_SOCKET="file:///var/run/zarafa-$this->instance_id";
		$this->PostfixInstance=$ligne["PostfixInstance"];
		
		
	}
	
	public function Build(){
		if($this->etcpath==null){echo "Starting......: zarafa-server build failed\n";return;}
		if($GLOBALS["AS_ROOT"]){echo "Starting......: zarafa-server building configuration `$this->etcpath`...\n";}
		$this->server_cfg();
		$this->ldap_cfg();
		$this->licensed_cfg();
		$this->monitor_cfg();
		$this->spooler_cfg();
		$this->dagent_cfg();
		$this->gateway_cfg();
	}
	
	
	private function server_cfg(){
			$unix=new unix();
			$this->server_bind=$this->listen_addr;
			if($this->usesocket==0){$this->server_bind="127.0.0.1";}
			if($this->servername==null){$this->servername=$unix->hostname_g();}
			
			if($this->mysql_instance_id>0){
				$q=new mysql_multi($this->mysql_instance_id);
				$mysql_user=$q->mysql_admin;
				$mysql_password=$q->mysql_password;
				$mysql_socket=$q->SocketPath;
				$mysql_database="zarafa$this->instance_id";
			}else{
				$q=new mysql();
				$mysql_user=$q->mysql_admin;
				$mysql_password=$q->mysql_password;	
				$mysql_socket="/var/run/mysqld/mysqld.sock";
				$mysql_database="zarafa$this->instance_id";			
			}
			
			@mkdir($this->attachment_path,0755,true);
			@mkdir("$this->etcpath/userscripts",0755,true);
		
			$f[]="server_bind		    = $this->server_bind";
			$f[]="server_hostname       = $this->servername";
			$f[]="server_tcp_enabled	= yes";
			$f[]="server_tcp_port		= $this->server_tcp_port";
			$f[]="server_pipe_enabled	= yes";
			$f[]="server_pipe_name		= /var/run/zarafa-$this->instance_id";
			$f[]="server_name = zarafa-$this->instance_id";
			$f[]="database_engine		= mysql";
			$f[]="allow_local_users		= yes";
			$f[]="local_admin_users		= root vmail mail ";
			$f[]="system_email_address	= postmaster@localhost";
			$f[]="run_as_user			= ";
			$f[]="run_as_group			= ";
			$f[]="pid_file				= /var/run/zarafa-server-$this->instance_id.pid";
			$f[]="running_path 			= /";
			$f[]="session_timeout		= 300";
			$f[]="license_socket		= /var/run/zarafa-licensed-$this->instance_id";
			$f[]="log_method			= syslog";
			$f[]="log_file				= /var/log/zarafa/server_$this->instance_id.log";
			$f[]="log_level				= 2";
			$f[]="log_timestamp			= 1";
			//$f[]="mysql_host			= 127.0.0.1";
			//$f[]="mysql_port			= 3306";
			$f[]="mysql_user			= $mysql_user";
			$f[]="mysql_password		= $mysql_password";
			$f[]="mysql_socket			= $mysql_socket";
			$f[]="mysql_database		= $mysql_database";
			$f[]="attachment_storage	= files";
			$f[]="attachment_path		= $this->attachment_path";
			$f[]="attachment_compression= 6";
			$f[]="server_ssl_enabled	= no";
			$f[]="server_ssl_port		= 237";
			$f[]="server_ssl_key_file	= $this->etcpath/ssl/server.pem";
			$f[]="server_ssl_key_pass	=  secret";
			$f[]="server_ssl_ca_file	= $this->etcpath/ssl/cacert.pem";
			$f[]="server_ssl_ca_path	=";
			$f[]="sslkeys_path			= /etc/ssl/certs/zarafa-$this->instance_id";
			$f[]="softdelete_lifetime	= 30";
			$f[]="sync_lifetime			= 365";
			$f[]="sync_log_all_changes 	= yes";
			$f[]="enable_sso_ntlmauth 	= no";
			$f[]="enable_gab 			= yes";
			$f[]="auth_method 			= plugin";
			$f[]="pam_service 			= passwd";
			$f[]="cache_cell_size		= 16777216";
			$f[]="cache_object_size		= 5242880";
			$f[]="cache_indexedobject_size= 16777216";
			$f[]="cache_quota_lifetime	= 1";
			$f[]="cache_userdetails_lifetime= 5";
			$f[]="thread_stacksize 		= 512";
			$f[]="quota_warn			= 0";
			$f[]="quota_soft			= 0";
			$f[]="quota_hard			= 0";
			$f[]="companyquota_warn 	= 0";
			$f[]="user_plugin			= ldap";
			$f[]="user_plugin_config	= $this->etcpath/ldap.openldap.cfg";
			$f[]="enable_hosted_zarafa  = yes";
			$f[]="createuser_script	=	$this->etcpath/userscripts/createuser";
			$f[]="deleteuser_script	=	$this->etcpath/userscripts/deleteuser";
			$f[]="creategroup_script	=	$this->etcpath/userscripts/creategroup";
			$f[]="deletegroup_script	=	$this->etcpath/userscripts/deletegroup";
			$f[]="createcompany_script	=	$this->etcpath/userscripts/createcompany";
			$f[]="deletecompany_script	=	$this->etcpath/userscripts/deletecompany";
			$f[]="enable_distributed_zarafa = false";
			$f[]="storename_format 		= %f";
			$f[]="loginname_format		= %u";
			$f[]="client_update_enabled = true";
			$f[]="client_update_path 	= /var/lib/zarafa/client";
			$f[]="hide_everyone 		= no";
			$f[]="plugin_path			= /usr/lib/zarafa";
			$f[]="user_safe_mode 		= no";
			$f[]="disabled_features 	= ";
			$f[]="";
			@file_put_contents("$this->etcpath/server.cfg", @implode("\n", $f));
			echo "Starting......: zarafa-server server.cfg success..\n";
	}
	
	
	private function licensed_cfg(){
		@mkdir("$this->etcpath/license",0755,true);
		$f[]="server_pipe_name 	= /var/run/zarafa-licensed-$this->instance_id";
		$f[]="server_socket		= file:///var/run/zarafa-$this->instance_id";
		$f[]="license_path		= $this->etcpath/license";
		$f[]="run_as_user		=";
		$f[]="run_as_group		=";
		$f[]="pid_file			= /var/run/zarafa-licensed-$this->instance_id.pid";
		$f[]="running_path     	= /";
		$f[]="log_method		= syslog";
		$f[]="log_file			= -";
		$f[]="log_level			= 2";
		$f[]="log_timestamp		= 1";		
		@file_put_contents("$this->etcpath/licensed.cfg", @implode("\n", $f));
		echo "Starting......: zarafa-licensed licensed.cfg success..\n";		
	}
	
private function dagent_cfg(){	
		$f[]="server_bind		= $this->server_bind";
		$f[]="server_socket		= file:///var/run/zarafa-$this->instance_id";
		$f[]="run_as_user 		= root";
		$f[]="run_as_group 		= root";
		$f[]="pid_file 			= /var/run/zarafa-dagent-$this->instance_id.pid";
		$f[]="lmtp_max_threads 	= 20";
		$f[]="lmtp_port  		= $this->lmtp_port";
		$f[]="log_method		= syslog";
		@file_put_contents("$this->etcpath/dagent.cfg", @implode("\n", $f));
		echo "Starting......: zarafa-licensed dagent.cfg success..\n";		

}

private function gateway_cfg(){
		$imap_enable="no";
		$pop3_enable="no";
		if($this->EnableIMAP==1){$imap_enable="yes";}
		if($this->EnablePOP3==1){$pop3_enable="yes";}
		$f[]="server_bind			= $this->server_bind";
		$f[]="server_socket			= http://$this->server_bind:$this->server_tcp_port/zarafa";
		$f[]="run_as_user 			= root";
		$f[]="run_as_group 			= root";
		$f[]="pid_file 				= /var/run/zarafa-gateway-$this->instance_id.pid";
		$f[]="running_path 			= /";
		$f[]="pop3_enable			= $pop3_enable";
		$f[]="pop3s_enable			= no";
		$f[]="imap_enable			= $imap_enable";
		$f[]="imaps_enable			= no";
		$f[]="imap_port				= 143";		
		$f[]="imaps_port			= 993";
		$f[]="pop3s_port			= 995";
		$f[]="pop3_port				= 110";				
		$f[]="imap_only_mailfolders	= no";
		$f[]="imap_public_folders	= yes";
		$f[]="imap_capability_idle 	= yes";
		$f[]="ssl_private_key_file	= /etc/ssl/certs/postfix/ca.key";
		$f[]="ssl_certificate_file	= /etc/ssl/certs/postfix/ca.crt";
		$f[]="ssl_verify_client		= no";
		$f[]="ssl_verify_file		=";
		$f[]="ssl_verify_path		=";
		$f[]="log_method			= syslog";
		$f[]="log_level				= 2";
		$f[]="log_file				= /var/log/zarafa/gateway-$this->instance_id.log";
		$f[]="log_timestamp			= 1";	
		@file_put_contents("$this->etcpath/gateway.cfg", @implode("\n", $f));
		echo "Starting......: zarafa-gateway gateway.cfg success..\n";	
}
	
	
	private function monitor_cfg(){
		if(!is_dir("$this->etcpath/quotamail")){
			$unix=new unix();
			$ln=$unix->find_program("ln");
			shell_exec("$ln -s /etc/zarafa//quotamail $this->etcpath/quotamail >/dev/null 2>&1");
		}
		
		$f[]="server_socket	=	file:///var/run/zarafa-$this->instance_id";
		$f[]="run_as_user = ";
		$f[]="run_as_group = ";
		$f[]="pid_file = /var/run/zarafa-monitor-$this->instance_id.pid";
		$f[]="running_path = /";
		$f[]="log_method	=	syslog";
		$f[]="log_level	=	2";
		$f[]="log_file	=	/var/log/zarafa/monitor-$this->instance_id.log";
		$f[]="log_timestamp	=	1";
		$f[]="sslkey_file = /etc/zarafa/ssl/monitor$this->instance_id.pem";
		$f[]="sslkey_pass = replace-with-monitor-cert-password";
		$f[]="mailquota_resend_interval = 1";
		$f[]="userquota_warning_template  =   $this->etcpath/quotamail/userwarning.mail";
		$f[]="userquota_soft_template     =   $this->etcpath/quotamail/usersoft.mail";
		$f[]="userquota_hard_template     =   $this->etcpath/quotamail/userhard.mail";
		$f[]="companyquota_warning_template   =   $this->etcpath/quotamail/companywarning.mail";
		$f[]="companyquota_soft_template      =   $this->etcpath/quotamail/companysoft.mail";
		$f[]="companyquota_hard_template      =   $this->etcpath/quotamail/companyhard.mail";
		$f[]="";	
		@file_put_contents("$this->etcpath/monitor.cfg", @implode("\n", $f));
		echo "Starting......: zarafa-monitor monitor.cfg success..\n";
	}
	
	private function spooler_cfg(){
			$smtp_server="127.0.0.1";
			if($this->PostfixInstance<>null){
				include_once(dirname(__FILE__)."/class.maincf.multi.inc");
				$main=new maincf_multi($this->PostfixInstance);$smtp_server=$main->ip_addr;
			}
			$f[]="smtp_server	=	$smtp_server";
			$f[]="server_socket	=	file:///var/run/zarafa-$this->instance_id";
			$f[]="run_as_user = ";
			$f[]="run_as_group = ";
			$f[]="pid_file = /var/run/zarafa-spooler-$this->instance_id.pid";
			$f[]="running_path = /";
			$f[]="log_method	=	syslog";
			$f[]="log_level	=	3";
			$f[]="log_file	=	/var/log/zarafa/spooler-$this->instance_id.log";
			$f[]="log_timestamp	=	1";
			$f[]="max_threads = 5";
			$f[]="fax_domain = fax.local";
			$f[]="fax_international = 00";
			$f[]="always_send_delegates = no";
			$f[]="allow_redirect_spoofing = no";
			$f[]="copy_delegate_mails = yes";
			$f[]="always_send_tnef = yes";	
			@file_put_contents("$this->etcpath/spooler.cfg", @implode("\n", $f));
			echo "Starting......: zarafa-spooler spooler.cfg success..\n";				
	}
	
	
	private function ldap_cfg(){
			$ldap=new clladp();
			$f[]="ldap_host = $ldap->ldap_host";
			$f[]="ldap_port = $ldap->ldap_port";
			if($this->ou<>null){$prefix="ou=$this->ou,";}
			$f[]="ldap_search_base = {$prefix}dc=organizations,$ldap->suffix";
			$f[]="ldap_protocol = ldap";
			$f[]="ldap_server_charset = utf-8";
			$f[]="ldap_bind_user = cn=$ldap->ldap_admin,$ldap->suffix";
			$f[]="ldap_bind_passwd = $ldap->ldap_password";
			$f[]="ldap_network_timeout = 30";
			$f[]="ldap_contact_type_attribute_value = zarafa-contact";
			$f[]="ldap_user_search_base =  {$prefix}dc=organizations,$ldap->suffix";
			$f[]="ldap_user_scope = sub";
			$f[]="ldap_user_type_attribute_value = posixAccount";
			$f[]="ldap_user_search_filter = (objectClass=userAccount)";
			$f[]="ldap_user_unique_attribute = uidNumber";
			$f[]="ldap_user_unique_attribute_type = text";
			$f[]="ldap_user_sendas_attribute = zarafaSendAsPrivilege";
			$f[]="ldap_user_sendas_attribute_type = text";
			$f[]="ldap_user_sendas_relation_attribute =";
			$f[]="ldap_user_certificate_attribute = userCertificate";
			$f[]="ldap_fullname_attribute = cn";
			$f[]="ldap_authentication_method = password";
			$f[]="ldap_loginname_attribute = uid";
			$f[]="ldap_password_attribute = userPassword";
			$f[]="ldap_emailaddress_attribute = mail";
			$f[]="ldap_emailaliases_attribute = mailAlias";
			$f[]="ldap_isadmin_attribute = zarafaAdmin";
			$f[]="ldap_nonactive_attribute =zarafaSharedStoreOnly";
			$f[]="ldap_group_search_base = {$prefix}dc=organizations,$ldap->suffix";
			$f[]="ldap_group_scope = sub";
			$f[]="ldap_group_search_filter = (objectClass=posixGroup)";
			$f[]="ldap_group_unique_attribute = gidNumber";
			$f[]="ldap_group_unique_attribute_type = text";
			$f[]="ldap_groupname_attribute = cn";
			$f[]="ldap_group_type_attribute_value = posixGroup";
			$f[]="ldap_groupmembers_attribute = memberUid";
			$f[]="ldap_groupmembers_attribute_type = text";
			$f[]="ldap_groupmembers_relation_attribute =uid";
			$f[]="ldap_company_unique_attribute = ou";
			$f[]="ldap_company_search_base = {$prefix}dc=organizations,$ldap->suffix";
			$f[]="ldap_company_scope = base";
			$f[]="ldap_company_search_filter =(&(objectclass=organizationalUnit)(objectClass=zarafa-company))";
			$f[]="ldap_company_type_attribute_value = organizationalUnit";
			$f[]="ldap_companyname_attribute = ou";
			$f[]="ldap_company_view_attribute = zarafaViewPrivilege";
			$f[]="ldap_company_view_attribute_type = text";
			$f[]="ldap_company_view_relation_attribute =";
			$f[]="ldap_company_admin_attribute = zarafaAdminPrivilege";
			$f[]="ldap_company_admin_attribute_type = text";
			$f[]="ldap_company_admin_relation_attribute = ";
			$f[]="ldap_company_system_admin_attribute = zarafaSystemAdmin";
			$f[]="ldap_company_system_admin_attribute_type = text";
			$f[]="ldap_company_system_admin_relation_attribute =";
			$f[]="ldap_quota_userwarning_recipients_attribute = zarafaQuotaUserWarningRecipients";
			$f[]="ldap_quota_userwarning_recipients_attribute_type = text";
			$f[]="ldap_quota_userwarning_recipients_relation_attribute =";
			$f[]="ldap_quota_companywarning_recipients_attribute = zarafaQuotaCompanyWarningRecipients";
			$f[]="ldap_quota_companywarning_recipients_attribute_type = text";
			$f[]="ldap_quota_companywarning_recipients_relation_attribute=";
			$f[]="ldap_quotaoverride_attribute = zarafaQuotaOverride";
			$f[]="ldap_warnquota_attribute = zarafaQuotaWarn";
			$f[]="ldap_softquota_attribute = zarafaQuotaSoft";
			$f[]="ldap_hardquota_attribute = zarafaQuotaHard";
			$f[]="ldap_userdefault_quotaoverride_attribute = zarafaUserDefaultQuotaOverride";
			$f[]="ldap_userdefault_warnquota_attribute = zarafaUserDefaultQuotaWarn";
			$f[]="ldap_userdefault_softquota_attribute = zarafaUserDefaultQuotaSoft";
			$f[]="ldap_userdefault_hardquota_attribute = zarafaUserDefaultQuotaHard";
			$f[]="ldap_quota_multiplier = 1048576";
			$f[]="ldap_user_department_attribute = departmentNumber";
			$f[]="ldap_user_location_attribute = physicalDeliveryOfficeName";
			$f[]="ldap_user_telephone_attribute = telephoneNumber";
			$f[]="ldap_user_fax_attribute = facsimileTelephoneNumber";
			$f[]="ldap_last_modification_attribute = modifyTimestamp";
			$f[]="ldap_object_search_filter =(|(mail=%s*)(uid=%s*)(cn=*%s*)(sAMAccountName=*%s*)(fullname=*%s*)(givenname=*%s*)(lastname=*%s*)(sn=*%s*)) ";
			$f[]="ldap_filter_cutoff_elements = 1000";
			$f[]="ldap_addresslist_search_base = {$prefix}dc=organizations,$ldap->suffix";
			$f[]="ldap_addresslist_scope = sub";
			$f[]="ldap_addresslist_search_filter = (objectClass=zarafaAddressList)";
			$f[]="ldap_addresslist_unique_attribute = cn";
			$f[]="ldap_addresslist_unique_attribute_type = text";
			$f[]="ldap_addresslist_filter_attribute = zarafaFilter";
			$f[]="ldap_addresslist_name_attribute = cn";		
			@file_put_contents("$this->etcpath/ldap.openldap.cfg", @implode("\n", $f));
			echo "Starting......: zarafa-server ldap.openldap.cfg success..\n";
	}



}