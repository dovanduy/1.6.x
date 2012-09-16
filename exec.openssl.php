<?php

include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if($GLOBALS["VERBOSE"]){echo "Debug mode TRUE for {$argv[1]}\n";}

if($argv[1]=="--pass"){passphrase($argv[2]);exit;}
if($argv[1]=="--buildkey"){buildkey($argv[2]);}
if($argv[1]=="--x509"){x509($argv[2]);}





function buildkey($CommonName){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$directory="/etc/openssl/certificate_center/$CommonName";
	if(!is_file($openssl)){
		echo "openssl.......: No such binary, aborting...\n";
	}
	$q=new mysql();
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){
		echo "openssl.......: CommonName is null, aborting...\n";
		exit;
	}
	
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}		
	
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
	
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
	
	
	@mkdir($directory,0755,true);
	$cmd="$openssl req -nodes -newkey rsa:2048 -nodes -keyout $directory/myserver.key -out $directory/server.csr -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\"";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
	$csr=mysql_escape_string(@file_get_contents("$directory/server.csr"));
	$privkey=mysql_escape_string(@file_get_contents("$directory/myserver.key"));
	
	$sql="UPDATE sslcertificates SET `privkey`='$privkey',`csr`='$csr' WHERE CommonName='$CommonName'";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pass >/dev/null 2>&1 &");
	
	
}

function passphrase($CommonName){
	$unix=new unix();
	$ldap=new clladp();	
	$q=new mysql();
	$sql="SELECT servername,sslcertificate  FROM freeweb WHERE LENGTH(sslcertificate)>0";
	
	
	@mkdir("/etc/apache2/ssl-tools",0755,true);	
	
	$data[]="#!/bin/sh";
	$data[]="STR=$1";
	$data[]="STR2=`expr match \"\$STR\" '\(.*\?\):'`";
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$CommonName=$ligne["sslcertificate"];
		$sql="SELECT password from sslcertificates WHERE CommonName='$CommonName'";
		$ligneZ=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligneZ["password"]==null){$ligneZ["password"]=$ldap->ldap_password;}
		$data[]="[ \"$servername\" = \$STR2 ] && echo \"{$ligneZ["password"]}\"";
		
	}
	$data[]="";
	
	
	@file_put_contents("/etc/apache2/ssl-tools/sslpass.sh", @implode("\n", $data));
	@chmod("/etc/apache2/ssl-tools/sslpass.sh", 0755);
}

function x509($CommonName){
	$unix=new unix();
	$ldap=new clladp();
	$directory="/etc/openssl/certificate_center/$CommonName";
	$openssl=$unix->find_program("openssl");
	$cp=$unix->find_program("cp");
	if(!is_file($openssl)){echo "openssl.......: No such binary, aborting...\n";exit;}
	
	$q=new mysql();
	$q->BuildTables();
	
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){echo "CommonName is null, aborting...\n";exit;}

	$csr="$directory/server.csr";
	$privkey="$directory/myserver.key";
	if(!is_file($csr)){if(strlen($ligne["csr"])>10){@file_put_contents($csr, $ligne["csr"]);}}
	if(!is_file($privkey)){if(strlen($ligne["privkey"])>10){@file_put_contents($privkey, $ligne["privkey"]);}}
	if(!is_file($privkey)){return;}
	if(!is_file($csr)){return;}	
	$CertificateMaxDays=$ligne["CertificateMaxDays"];
	if(!is_numeric($CertificateMaxDays)){$CertificateMaxDays="730";}
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if($ligne["password"]==null){$ligne["password"]=$ldap->ldap_password;}	
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}	
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];	
	@unlink("$directory/.rnd");
	@unlink("$directory/serial.old");
	@unlink("$directory/index.txt.attr");  
	@unlink("$directory/index.txt.old");
	
	@file_put_contents("$directory/serial.txt", "01");
	@file_put_contents("$directory/serial", "01");
	shell_exec("$cp /dev/null $directory/index.txt");
	
	
	
	
	
	
$f[]="HOME			= $directory";
$f[]="RANDFILE		= $directory/.rnd";
$f[]="oid_section		= new_oids";
$f[]="";
$f[]="[ new_oids ]";
$f[]="";
$f[]="[ ca ]";
$f[]="default_ca	= CA_default		# The default ca section";
$f[]="[ CA_default ]";
$f[]="dir		= $directory		# Where everything is kept";
$f[]="certs		= $directory		# Where the issued certs are kept";
$f[]="crl_dir		= $directory		# Where the issued crl are kept";
$f[]="database	= $directory/index.txt	# database index file.";
$f[]="new_certs_dir	= $directory		# default place for new certs.";
$f[]="certificate	= $directory/server.crt 	# The CA certificate";
$f[]="serial		= $directory/serial 		# The current serial number";
$f[]="crlnumber	= $directory/crlnumber	# the current crl number";
$f[]="crl		= $directory/crl.pem 		# The current CRL";
$f[]="private_key	= $directory/myserver.key";
$f[]="RANDFILE	= $directory/.rand	# private random number file";
$f[]="x509_extensions	= usr_cert		# The extentions to add to the cert";
$f[]="name_opt 	= ca_default		# Subject Name options";
$f[]="cert_opt 	= ca_default		# Certificate field options";
$f[]="default_days	= $CertificateMaxDays";
$f[]="default_crl_days= 30			# how long before next CRL";
$f[]="default_md	= sha1			# which md to use.";
$f[]="preserve	= no			# keep passed DN ordering";
$f[]="policy		= policy_match";
$f[]="";
$f[]="[ policy_match ]";
$f[]="countryName		= match";
$f[]="stateOrProvinceName	= match";
$f[]="organizationName	= match";
$f[]="organizationalUnitName	= optional";
$f[]="commonName		= supplied";
$f[]="emailAddress		= optional";
$f[]="";
$f[]="[ policy_anything ]";
$f[]="countryName		= optional";
$f[]="stateOrProvinceName	= optional";
$f[]="localityName		= optional";
$f[]="organizationName	= optional";
$f[]="organizationalUnitName	= optional";
$f[]="commonName		= supplied";
$f[]="emailAddress		= optional";
$f[]="";
$f[]="[ req ]";
$f[]="default_bits		= 1024";
$f[]="default_keyfile 	= privkey.pem";
$f[]="distinguished_name	= req_distinguished_name";
$f[]="attributes		= req_attributes";
$f[]="x509_extensions	= v3_ca	# The extentions to add to the self signed cert";
$f[]="input_password = {$ligne["password"]}";
$f[]="output_password = {$ligne["password"]}";
$f[]="string_mask = nombstr";
$f[]="";
$f[]="[ req_distinguished_name ]";
$f[]="countryName			= Country Name (2 letter code)";
$f[]="countryName_default		= $C";
$f[]="countryName_min			= 2";
$f[]="countryName_max			= 2";
$f[]="stateOrProvinceName		= State or Province Name (full name)";
$f[]="stateOrProvinceName_default	= {$ligne["stateOrProvinceName"]}";
$f[]="localityName			= Locality Name (eg, city)";
$f[]="0.organizationName		= Organization Name (eg, company)";
$f[]="0.organizationName_default	= {$ligne["OrganizationName"]}";
$f[]="organizationalUnitName		= Organizational Unit Name (eg, section)";
$f[]="commonName			= Common Name (eg, YOUR name)";
$f[]="commonName_max			= 64";
$f[]="emailAddress			= Email Address";
$f[]="emailAddress_max		= 64";
$f[]="";
$f[]="[ req_attributes ]";
$f[]="challengePassword		= A challenge password";
$f[]="challengePassword_min		= 4";
$f[]="challengePassword_max		= 20";
$f[]="unstructuredName		= An optional company name";
$f[]="";
$f[]="[ usr_cert ]";
$f[]="basicConstraints=CA:FALSE";
$f[]="nsComment			= \"OpenSSL Generated Certificate\"";
$f[]="subjectKeyIdentifier=hash";
$f[]="authorityKeyIdentifier=keyid,issuer";
$f[]="[ v3_req ]";
$f[]="basicConstraints = CA:FALSE";
$f[]="keyUsage = nonRepudiation, digitalSignature, keyEncipherment";
$f[]="";
$f[]="[ v3_ca ]";
$f[]="subjectKeyIdentifier=hash";
$f[]="authorityKeyIdentifier=issuer:always";
$f[]="basicConstraints = CA:true";
$f[]="[ crl_ext ]";
$f[]="authorityKeyIdentifier=keyid:always,issuer:always";
$f[]="";
$f[]="[ proxy_cert_ext ]";
$f[]="basicConstraints=CA:FALSE";
$f[]="nsComment			= \"OpenSSL Generated Certificate\"";
$f[]="subjectKeyIdentifier=hash";
$f[]="authorityKeyIdentifier=keyid,issuer:always";
$f[]="proxyCertInfo=critical,language:id-ppl-anyLanguage,pathlen:3,policy:foo";	
echo "Writing $directory/openssl.cf\n";
@file_put_contents("$directory/openssl.cf", @implode("\n",$f));

$server_cert="$directory/server.crt";
$cmd="$openssl x509 -req -days $CertificateMaxDays -in $csr -signkey $privkey -out $server_cert -sha1";
echo "\n****\n$cmd\n****\n";
shell_exec($cmd);
if(!is_file($server_cert)){echo "$directory/server.crt No such file !\n";return;}
	
$ligne["password"]=escapeshellcmd($ligne["password"]);
$cmd="$openssl genrsa -des3 -passout pass:{$ligne["password"]} -out $directory/cakey.pem 4096";
echo $cmd."\n";
shell_exec($cmd);


// the Intermediate CA private key
$cmdS=array();
$cmdS[]="$openssl req -new -sha1 -config $directory/openssl.cf";
$cmdS[]="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\"";
$cmdS[]="-key $directory/cakey.pem -out $directory/ca.csr";
$cmd=@implode(" ", $cmdS);
echo "\n****\n$cmd\n****\n";
shell_exec($cmd);



$cmdS=array();
$cmdS[]="$openssl ca -batch -extensions v3_ca -days $CertificateMaxDays -out $directory/cacert-itermediate.pem";
$cmdS[]="-in $directory/ca.csr -config $directory/openssl.cf";
$cmdS[]="-cert $directory/server.crt";
$cmd=@implode(" ", $cmdS);
echo "\n****\n$cmd\n****\n";
shell_exec($cmd);

$server_cert_content=@file_get_contents($server_cert);
$intermediate_content=@file_get_contents("$directory/cacert-itermediate.pem");
@file_put_contents("$directory/chain.crt", "$intermediate_content\n$server_cert_content");

//chain.crt = SSLCertificateChainFile

# make sure you are in the Intermediate CA folder and not in the Root CA one<br />
#cd /var/ca/ca2008/<br />
# create the private key<br />
#openssl genrsa -des3 -out toto.key 4096<br />
# generate a certificate sign request<br />
#openssl req -new -key toto.key -out toto.csr<br />
# sign the request with the Intermediate CA<br />
#openssl ca -config openssl.cnf -policy policy_anything -out toto.crt -infiles toto.csr<br />
# and store the server files in the certs/ directory<br />
#mkdir certs/{server_name}<br />
#mv {server_name}.key {server_name}.csr {server_name}.crt certs/<br />
	@unlink("$directory/.rnd");
	@unlink("$directory/serial.old");
	@unlink("$directory/index.txt.attr");  
	@unlink("$directory/index.txt.old");
	
	@file_put_contents("$directory/serial.txt", "01");
	@file_put_contents("$directory/serial", "01");
	shell_exec("$cp /dev/null $directory/index.txt");	

$cmdS=array();	
$cmdS[]="$openssl ca -batch -config openssl.cf -passin pass:{$ligne["password"]}";
$cmdS[]="-keyfile $directory/cakey.pem";
$cmdS[]="-cert cacert-itermediate.pem -policy policy_anything -out $directory/$CommonName.crt -infiles $directory/ca.csr";
$cmd=@implode(" ", $cmdS);
echo "\n****\n$cmd\n****\n";
shell_exec($cmd);	

$content=mysql_escape_string(@file_get_contents("$directory/$CommonName.crt"));
$bundle=mysql_escape_string(@file_get_contents("$directory/chain.crt"));
$sql="UPDATE sslcertificates SET `crt`='$content',`bundle`='$bundle' WHERE CommonName='$CommonName'";
$q->QUERY_SQL($sql,"artica_backup");
if(!$q->ok){echo $q->mysql_error."\n";}
$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pass >/dev/null 2>&1 &");
}
