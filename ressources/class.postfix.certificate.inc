<?php

// echo "quit"|openssl s_client -starttls smtp -host 127.0.0.1 -port 25

class postfix_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/ssl/certs/postfix";
	private $UsePrivKeyCrt=0;
	private $crt_content=null;
	private $csr_content=null;
	private $srca_content=null;
	private $privkey_content=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
	private $ssl_client_certificate=0;
	private $RootCa=null;
	private $PrefixOutput;
	private $AS_ROOT=false;
	
	function postfix_certificate($CommonName=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($CommonName<>null){$this->CommonName=$CommonName;}
		$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Postfix \"CERT\"";
		if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
	}
	
	
	public function build(){
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $this->CommonName\n";}
		if($this->CommonName==null){return $this->build_default();}
		$this->load_certificate();
		$certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
		
		$Directory="$this->ssl_path/$certificate_subdir";
		@mkdir($Directory,0755,true);
		$PRIVATE_KEY=$this->srca_content;
		$CERTIFICATE=$this->crt_content;
		$ROOT=$this->RootCa;
		
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.crt ".strlen($CERTIFICATE)." bytes\n";}
		@file_put_contents("$Directory/server.crt", "$CERTIFICATE");
		@file_put_contents("$Directory/server.key", "$PRIVATE_KEY");
		@file_put_contents("$Directory/ca.csr", "$ROOT");
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $Directory/server.key ".strlen($PRIVATE_KEY)." bytes\n";}
		
		$cmd["smtpd_tls_key_file"]="$Directory/server.key";
		$cmd["smtpd_tls_cert_file"]="$Directory/server.crt";
		$cmd["smtpd_tls_CAfile"]="$Directory/ca.csr";
		while (list ($num, $ligne) = each ($cmd) ){
			postconf($num,$ligne);
				
		}
		
	}
	
	private function load_certificate(){
		$q=new mysql();
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE CommonName='$this->CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
		$this->crt_content=$ligne["crt"];
		$this->csr_content=$ligne["csr"];
		$this->srca_content=$ligne["srca"];
		
		
		$this->privkey_content=$ligne["privkey"];
		$this->SquidCert=$ligne["SquidCert"];
		$this->Squidkey=$ligne["Squidkey"];
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->RootCa=$ligne["srca"];
		
		if($this->UsePrivKeyCrt==0){
			$this->srca_content=$this->Squidkey;
			$this->crt_content=$this->SquidCert;
		}
			

		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Private key: ".strlen($this->srca_content)." bytes\n";}
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Certificate: ".strlen($this->crt_content)." bytes\n";}
			
		
	}
	
	
	private function build_default(){
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Build default certificate\n";}
		if(!is_file("$this->ssl_path/ca.key")){
			@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
			shell_exec("/usr/share/artica-postfix/bin/artica-install --change-postfix-certificate");
		}
		
		$cmd["smtpd_tls_key_file"]="/etc/ssl/certs/postfix/ca.key";
		$cmd["smtpd_tls_cert_file"]="/etc/ssl/certs/postfix/ca.crt";
		$cmd["smtpd_tls_CAfile"]="/etc/ssl/certs/postfix/ca.csr";
		while (list ($num, $ligne) = each ($cmd) ){
			postconf($num,$ligne);
			
		}
		
	}
	
	
}

