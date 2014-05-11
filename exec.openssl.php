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
if($argv[1]=="--mysql"){update_from_mysql($argv[2]);}
if($argv[1]=="--squid-auto"){squid_autosigned($argv[2]);}
if($argv[1]=="--squid-validate"){squid_validate($argv[2]);}
if($argv[1]=="--BuildCSR"){BuildCSR($argv[2]);}


function BuildCSR($CommonName){
	$CommonName=str_replace("_ALL_", "*", $CommonName);
	buildkey($CommonName);
	squid_autosigned($CommonName);
	update_from_mysql($CommonName);
}



function buildkey($CommonName){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$CommonName=str_replace("_ALL_", "*", $CommonName);
	
	
	$directory="/etc/openssl/certificate_center/".md5($CommonName);
	if(!is_file($openssl)){
		echo "openssl.......: No such binary, aborting...\n";
	}
	$q=new mysql();
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){$ligne["CommonName"]="*";}
	
	
		
	
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}	

	if($ligne["levelenc"]<1024){$ligne["levelenc"]=1024;}
	
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
	
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
	
	
	@mkdir($directory,0755,true);
	$cmd="$openssl req -nodes -newkey rsa:{$ligne["levelenc"]} -nodes -keyout $directory/myserver.key -out $directory/server.csr -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo @implode("\n", $results)."\n";}
	
	$csr=mysql_escape_string2(@file_get_contents("$directory/server.csr"));
	$privkey=mysql_escape_string2(@file_get_contents("$directory/myserver.key"));
	
	$sql="UPDATE sslcertificates SET `privkey`='$privkey',`csr`='$csr' WHERE CommonName='$CommonName'";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pass >/dev/null 2>&1 &");
	
	
}

function passphrase(){
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

function update_from_mysql($CommonName){
	$unix=new unix();
	$ldap=new clladp();
	if($CommonName==null){echo "openssl.......: Fatal:".__LINE__."::CommonName is null aborting...\n";exit;}
	
	$directory="/etc/openssl/certificate_center/".md5($CommonName);
	$openssl=$unix->find_program("openssl");
	$cp=$unix->find_program("cp");
	if(!is_file($openssl)){echo "openssl.......: No such binary, aborting...\n";exit;}	
	$q=new mysql();
	$q->BuildTables();
	

	
	$sql="SELECT bundle,crt,privkey,Squidkey,SquidCert,CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){echo "Fatal:".__LINE__."::CommonName is null, aborting...\n$sql";exit;}

	if($ligne["privkey"]==null){
		if($GLOBALS["VERBOSE"]){echo "{error_missing_data_in_mysql}: {private_key}\n";}
		return;
	}
	
	if($ligne["crt"]==null){
		if($GLOBALS["VERBOSE"]){echo "{error_missing_data_in_mysql}: {certificate}\n";}
		return;
	}


	
	$privkey="$directory/myserver.key";
	$certificate_path="$directory/server.crt";
	@file_put_contents($privkey, $ligne["privkey"]);
	@file_put_contents($$certificate_path, $ligne["crt"]);
	
	@unlink("$directory/myserver.key");
	@unlink("$directory/server.crt");
	@unlink("$directory/chain.crt");
	@unlink("$directory/cakey.pem");
	@unlink("$directory/$CommonName.crt");
	
	if(strlen($ligne["Squidkey"])>10){
		@file_put_contents("$directory/myserver.key", $ligne["Squidkey"]);
	}
	
	if(strlen($ligne["SquidCert"])>10){
		@file_put_contents("$directory/server.crt", $ligne["SquidCert"]);
	}	

	if(strlen($ligne["bundle"])>10){
		@file_put_contents("$directory/chain.crt", $ligne["bundle"]);
		@file_put_contents("$directory/cakey.pem", $ligne["privkey"]);
		@file_put_contents("$directory/$CommonName.crt", $ligne["crt"]);
	}	
	
	
	
	if(strlen($ligne["bundle"])>10){
		@file_put_contents("$directory/chain.crt", $ligne["bundle"]);
		@file_put_contents("$directory/cakey.pem", $ligne["privkey"]);
		@file_put_contents("$directory/$CommonName.crt", $ligne["crt"]);
	}
	
	/*$conf[]="\tSSLCertificateFile $directory/$CommonName.crt";
	$conf[]="\tSSLCertificateKeyFile $directory/cakey.pem";
	$conf[]="\tSSLCertificateChainFile $directory/chain.crt";
	*/
			
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 ".__FILE__." --pass >/dev/null 2>&1 &");			
	
}

function x509($CommonName){
	$CommonName=str_replace("_ALL_", "*", $CommonName);
	$unix=new unix();
	$ldap=new clladp();
	$directory="/etc/openssl/certificate_center/".md5($CommonName);
	@mkdir($directory,0644,true);
	$openssl=$unix->find_program("openssl");
	$cp=$unix->find_program("cp");
	if(!is_file($openssl)){echo "openssl.......: No such binary, aborting...\n";return;}
	
	$q=new mysql();
	$q->BuildTables();
	
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){echo "MySQL return a null CommonName For `$CommonName`, aborting...\n";return;}

	$csr="$directory/server.csr";
	$privkey="$directory/myserver.key";
	if(!is_file($csr)){if(strlen($ligne["csr"])>10){@file_put_contents($csr, $ligne["csr"]);}}
	if(!is_file($privkey)){if(strlen($ligne["privkey"])>10){@file_put_contents($privkey, $ligne["privkey"]);}}
	if(!is_file($privkey)){buildkey($CommonName);}
	
	if(!is_file($privkey)){
		echo "$privkey no such file\n";
		return;
	}	
	
	if(!is_file($csr)){echo "$csr no such file\n";return;}	
	$CertificateMaxDays=$ligne["CertificateMaxDays"];
	if(!is_numeric($CertificateMaxDays)){$CertificateMaxDays="730";}
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if(trim($ligne["password"])==null){$ligne["password"]=$ldap->ldap_password;}	
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}	
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];	
	@unlink("$directory/.rnd");
	@unlink("$directory/serial.old");
	@unlink("$directory/index.txt.attr");  
	@unlink("$directory/index.txt.old");
	@unlink("$directory/rnd");
	
	
	@file_put_contents("$directory/serial.txt", "01");
	@file_put_contents("$directory/serial", "01");
	shell_exec("$cp /dev/null $directory/index.txt");
	putenv("HOME=/root");
	putenv("RANDFILE=$directory/rnd");
	system("env");
	
	
	
	
$f[]="HOME			= $directory";
$f[]="RANDFILE		= $directory/rnd";
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
$f[]="countryName			= optional";
$f[]="stateOrProvinceName	= optional";
$f[]="organizationName		= optional";
$f[]="organizationalUnitName	= optional";
$f[]="commonName			= supplied";
$f[]="emailAddress			= optional";
$f[]="";
$f[]="[ policy_anything ]";
$f[]="countryName			= optional";
$f[]="stateOrProvinceName	= optional";
$f[]="localityName			= optional";
$f[]="organizationName		= optional";
$f[]="organizationalUnitName	= optional";
$f[]="commonName			= supplied";
$f[]="emailAddress			= optional";
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
$f[]="countryName				= $C";
$f[]="countryName_default		= $C";
$f[]="countryName_min			= 2";
$f[]="countryName_max			= 2";
$f[]="stateOrProvinceName		= {$ligne["stateOrProvinceName"]}";
$f[]="localityName				= {$ligne["localityName"]}";
$f[]="0.organizationName		= {$ligne["OrganizationName"]}";
$f[]="0.organizationName_default= {$ligne["OrganizationName"]}";
$f[]="organizationalUnitName	= {$ligne["OrganizationalUnit"]}";
$f[]="commonName				= $CommonName";
$f[]="commonName_max			= 64";
$f[]="emailAddress				= {$ligne["emailAddress"]}";
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

@chdir($directory);
$server_cert="$directory/server.crt";
$DefaultSubject="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\"";
echo "\n";
echo "[".__LINE__."] ************************************************************************\n";
echo "[".__LINE__."] DefaultSubject = $DefaultSubject\n";
$cmd="$openssl x509 -req -CAcreateserial $DefaultSubject -days $CertificateMaxDays -in $csr -signkey $privkey -out $server_cert -sha1 2>&1";
echo "[".__LINE__."] $cmd\n";
echo "\n";
echo "[".__LINE__."] ************************************************************************\n";
echo "[".__LINE__."] $cmd\n";
exec($cmd,$results0);

while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo $ligneLine."\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";

}

if(!is_file($server_cert)){
	echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
	echo "[".__LINE__."] $directory/server.crt No such file !\n";
	echo "[".__LINE__."] ************************************************************************\n\n";
	return;	
}
	
$ligne["password"]=escapeshellcmd($ligne["password"]);
echo "[".__LINE__."] ************************************************************************\n\n";
if(is_file("$directory/rnd")){
	echo "Removing $directory/rnd\n";
	@unlink("$directory/rnd");
	@touch("$directory/rnd");
	@chmod("$directory/rnd",0644);
}

if(is_file("$directory/cakey.pem")){@unlink("$directory/cakey.pem");}

$cmd="$openssl genrsa -des3 -rand file:$directory/rand -passout pass:{$ligne["password"]} -out $directory/cakey.pem 4096 2>&1";
echo "\n";
echo "[".__LINE__."] ************************************************************************\n$cmd\n";
exec($cmd,$results1);

while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";
	
}
echo "[".__LINE__."] ************************************************************************\n\n";

$cakeySize=@filesize("$directory/cakey.pem");
echo "[".__LINE__."] cakey.pem: $cakeySize bytes";
if($cakeySize==0){
	echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
	echo "[".__LINE__."] $directory/cakey.pem O bytes!!!\n";
	echo "[".__LINE__."] ************************************************************************\n\n";
	return;
}

$cmdS=array();
$cmdS[]="$openssl req -new -sha1 -config $directory/openssl.cf";
$cmdS[]=$DefaultSubject;
$cmdS[]="-key $directory/cakey.pem -out $directory/ca.csr";
$cmdS[]=" 2>&1";
$cmd=@implode(" ", $cmdS);

echo "\n************************************************************************\n$cmd\n";
exec($cmd,$results2);

while (list ($num, $ligneLine) = each ($results2) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";

}
echo "[".__LINE__."] ************************************************************************\n\n";


// #####################################################################################################################

$cmd="$openssl req -new -newkey rsa:1024 $DefaultSubject -days $CertificateMaxDays -nodes -x509 -keyout $directory/DynamicCert.pem -out $directory/DynamicCert.pem 2>&1";
echo "[".__LINE__."] ************************************************************************\n";
echo "$cmd\n";

$results1=array();
exec($cmd,$results1);
while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";
	
}

echo "[".__LINE__."] ************************************************************************\n\n";





// #####################################################################################################################

echo "[".__LINE__."] ************************************************************************\n";
$cmd="$openssl x509 -in $directory/DynamicCert.pem -outform DER -out $directory/DynamicCert.der 2>&1";
echo "$cmd\n";
$results1=array();
exec($cmd,$results1);
while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";

}
// #####################################################################################################################




$cmdS=array();
$cmdS[]="$openssl ca -batch -extensions v3_ca $DefaultSubject -days $CertificateMaxDays -out $directory/cacert-itermediate.pem";
$cmdS[]="-in $directory/ca.csr -config $directory/openssl.cf";
$cmdS[]="-cert $directory/server.crt";
$cmd=@implode(" ", $cmdS);
echo "[".__LINE__."] ************************************************************************\n";
echo "$cmd\n";
$results1=array();
exec($cmd,$results1);
while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";

}

echo "[".__LINE__."] ************************************************************************\n\n";
// #####################################################################################################################
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
	@unlink("$directory/rnd");
	
	@file_put_contents("$directory/serial.txt", "01");
	@file_put_contents("$directory/serial", "01");
	shell_exec("$cp /dev/null $directory/index.txt");	

$cmdS=array();	
$cmdS[]="$openssl ca -batch -config $directory/openssl.cf -passin pass:{$ligne["password"]}";
$cmdS[]="-keyfile $directory/cakey.pem";
$cmdS[]="-cert $directory/cacert-itermediate.pem -policy policy_anything -out $directory/MAIN.crt"; 
$cmdS[]="-infiles $directory/ca.csr";
$cmd=@implode(" ", $cmdS);
echo "[".__LINE__."] ************************************************************************\n";
echo "$cmd\n";
$results1=array();
exec($cmd,$results1);
while (list ($num, $ligneLine) = each ($results1) ){
	if(preg_match("#unable#i", $ligneLine)){
		echo "[".__LINE__."] ************************** ERROR DETECTED !!! **************************\n";
		echo "[".__LINE__."] $ligneLine\n";
		echo "[".__LINE__."] ************************************************************************\n\n";
		return;
	}
	echo "[".__LINE__."] $ligneLine\n";

}

echo "[".__LINE__."] ************************************************************************\n\n";
// #####################################################################################################################



$content=mysql_escape_string2(@file_get_contents("$directory/MAIN.crt"));
$bundle=mysql_escape_string2(@file_get_contents("$directory/chain.crt"));
$sql="UPDATE sslcertificates SET `crt`='$content',`bundle`='$bundle' WHERE CommonName='$CommonName'";
$q->QUERY_SQL($sql,"artica_backup");
if(!$q->ok){echo $q->mysql_error."\n";}
squid_autosigned($CommonName);
$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
chdir("/root");
shell_exec("$nohup $php5 ".__FILE__." --pass >/dev/null 2>&1 &");
}

function echo_implode($array,$xline){
	while (list ($num, $line) = each ($array)){
		echo "Line: $xline: $line\n";
	}
	
}

function squid_autosigned($CommonName){
	$CommonName=str_replace("_ALL_", "*", $CommonName);
	$directory="/etc/openssl/certificate_center/".md5($CommonName);
	$q=new mysql();
	if(!$q->FIELD_EXISTS("sslcertificates","Squidkey","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `Squidkey` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","SquidCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `SquidCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	$unix=new unix();
	$ldap=new clladp();	
	$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){echo "CommonName is null, aborting...\n";return;}
	
	
	$openssl=$unix->find_program("openssl");
	
	$CertificateMaxDays=$ligne["CertificateMaxDays"];
	if(!is_numeric($CertificateMaxDays)){$CertificateMaxDays="730";}
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if($ligne["password"]==null){$ligne["password"]=$ldap->ldap_password;}
	$ligne["password"]=escapeshellcmd($ligne["password"]);
	if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}	
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];	
	if($ligne["levelenc"]<1024){$ligne["levelenc"]=1024;}

	$cmd="$openssl genrsa -des3 -passout pass:{$ligne["password"]} -out $directory/squid-server.key {$ligne["levelenc"]} 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}
	
	$cmd="openssl req -new -key $directory/squid-server.key -passin pass:{$ligne["password"]} -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" -out $directory/squid-server.csr 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}
		
	$cmd="$openssl rsa -in $directory/squid-server.key  -passin pass:{$ligne["password"]} -out $directory/squid-proxy.key 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}
	
	$cmd="$openssl x509 -req -days $CertificateMaxDays -in $directory/squid-server.csr -signkey $directory/squid-proxy.key -out $directory/squid-proxy.crt 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";	}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}
	
	
	// http://wiki.squid-cache.org/Features/DynamicSslCert
	$cmd="$openssl req -new -newkey rsa:{$ligne["levelenc"]} -days $CertificateMaxDays -nodes -x509 -keyout $directory/RootCA.pem  -out $directory/RootCA.pem -subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";	}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}
	
	$cmd="$openssl x509 -in $directory/RootCA.pem -outform DER -out $directory/RootCA.der 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";	}
	$resultsCMD=array();exec($cmd,$resultsCMD);if($GLOBALS["VERBOSE"]){echo_implode($resultsCMD,__LINE__);}	
		
	$SquidSrca=mysql_escape_string2(@file_get_contents("$directory/RootCA.pem"));
	$Squidkey=mysql_escape_string2(@file_get_contents("$directory/squid-proxy.key"));
	$SquidCert=mysql_escape_string2(@file_get_contents("$directory/squid-proxy.crt"));
	$SquidDer=mysql_escape_string2(@file_get_contents("$directory/RootCA.der"));
	$DynamicCert=mysql_escape_string2(@file_get_contents("$directory/DynamicCert.pem"));
	$DynamicDer=mysql_escape_string2(@file_get_contents("$directory/DynamicCert.der"));
	
	
	if(!$q->FIELD_EXISTS("sslcertificates","srca","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `srca` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","der","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `der` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","crt","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `crt` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","bundle","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `bundle` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","password","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `password` VARCHAR(128) NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","Squidkey","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `Squidkey` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","SquidCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `SquidCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","keyPassword","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `keyPassword` VARCHAR(255) NOT NULL,ADD INDEX(`keyPassword`)";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","levelenc","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `levelenc` INT(3) NOT NULL DEFAULT '1024',ADD INDEX(`levelenc`)";$q->QUERY_SQL($sql,'artica_backup');}	
	if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	if(!$q->FIELD_EXISTS("sslcertificates","DynamicDer","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicDer` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
	
	if($GLOBALS["VERBOSE"]){echo "Squidkey......: ".strlen($Squidkey)." bytes\n";	}
	if($GLOBALS["VERBOSE"]){echo "SquidCert.....: ".strlen($SquidCert)." bytes\n";	}
	if($GLOBALS["VERBOSE"]){echo "srca..........: ".strlen($SquidSrca)." bytes\n";	}
	if($GLOBALS["VERBOSE"]){echo "der...........: ".strlen($SquidDer)." bytes\n";	}
	if($GLOBALS["VERBOSE"]){echo "DynamicCert...: ".strlen($DynamicCert)." bytes\n";	}
	if($GLOBALS["VERBOSE"]){echo "DynamicDer....: ".strlen($DynamicDer)." bytes\n";	}
	
	
	
	
	$sql="UPDATE sslcertificates SET 
		`Squidkey`='$Squidkey',
		`SquidCert`='$SquidCert', 
		`srca`='$SquidSrca',
		`der`='$SquidDer',
		`DynamicCert`='$DynamicCert',
		`DynamicDer`='$DynamicDer'
		WHERE CommonName='$CommonName'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}	
}

function squid_validate($CommonName){
	$q=new mysql();
	$tt=time();
	$sql="SELECT Squidkey,SquidCert  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	@mkdir("/etc/ssl/certs",0755,true);
	@file_put_contents("/etc/ssl/certs/$CommonName.key", $ligne["Squidkey"]);
	@file_put_contents("/etc/ssl/certs/$CommonName.cert", $ligne["SquidCert"]);
	
}
?>