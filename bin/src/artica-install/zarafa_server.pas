unit zarafa_server;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,md5,logs,unix,RegExpr in 'RegExpr.pas',zsystem,openldap,apache_artica;

  type
  tzarafa_server=class


private
     LOGS:Tlogs;
     D:boolean;
     GLOBAL_INI:TiniFIle;
     SYS:TSystem;
     artica_path:string;
    ldap:topenldap;
    ZarafaEnableServer:integer;
    ZarafaApacheEnable:integer;
    function  SPOOLER_BIN_PATH():string;
    function  INDEXER_BIN_PATH():string;
    function  MONITOR_BIN_PATH():string;
    function  DAGENT_BIN_PATH():string;
    function  LICENSED_BIN_PATH():string;
    procedure VERIFY_MAPI_SO_PATH();
    function  MONITOR_GET_PID():string;
    function  SPOOLER_GET_PID():string;
    function  GATEWAY_GET_PID():string;
    function  SERVER_GET_PID():string;
    function  DAGENT_GET_PID():string;
    function  INDEXER_GET_PID():string;
    function  LICENSED_GET_PID():string;
    function  ICAL_GET_PID():string;
    function  LIGHTTPD_PID():string;
    procedure LICENSED_START();
    procedure ICAL_CONFIG();
    function  APACHE_FOUND_ERROR():boolean;
    function GET_CERTIFICATE_INFO(key:string):string;
    function ifIpAddrAvailable(ipadr:string):boolean;

    procedure CHECK_CYRUS_CONFIG();
    function  GATEWAY_BIN_PATH():string;
    function  ICAL_BIN_PATH():string;

    procedure SPOOLER_START();
    procedure MONITOR_START();

    procedure LIGHTTPD_START();
    procedure APACHE_CONFIG();
    procedure INDEXER_START();

    function  ZARAFA_WEB_PID_NUM():string;
    procedure lighttpd_config();


    procedure SPOOLER_STOP();
    procedure MONITOR_STOP();


    procedure LICENSED_STOP();
    procedure INDEXER_STOP();

    function PLUGIN_PATH():string;

public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure ICAL_STOP();
    procedure ICAL_START();
    procedure GATEWAY_START();
    procedure GATEWAY_STOP();
    procedure START();
    procedure DAGENT_START();
    procedure DAGENT_STOP();
    function  VERSION(nocache:boolean=false):string;
    function  SERVER_BIN_PATH():string;
    procedure WEB_ACCESS_CONFIG();
    function  STATUS():string;
    procedure STOP();
    procedure REMOVE();
    procedure APACHE_START();
    procedure APACHE_STOP();
    procedure CERTIFICATES();
    procedure APACHE_CERTIFICATES();
    function  BIN_PATH():string;
    procedure SERVER_START();
    procedure SERVER_STOP();
    function IS_LDAP_ACTIVE():boolean;
    procedure server_cfg();
    procedure BuildCertificate();
    procedure SEARCH_START();
    procedure SEARCH_STOP();
END;

implementation

constructor tzarafa_server.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       ldap:=topenldap.Create;
       if not TryStrToInt(SYS.GET_INFO('ZarafaEnableServer'),ZarafaEnableServer) then ZarafaEnableServer:=1;
       if not TryStrToInt(SYS.GET_INFO('ZarafaApacheEnable'),ZarafaApacheEnable) then ZarafaApacheEnable:=1;



       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tzarafa_server.free();
begin
    FreeAndNil(logs);
    FreeAndNil(ldap);
end;
//##############################################################################
function tzarafa_server.BIN_PATH():string;
begin
result:=SERVER_BIN_PATH();
end;
//##############################################################################
function tzarafa_server.SERVER_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-server');
end;
//##############################################################################
function tzarafa_server.SPOOLER_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-spooler');
end;
//##############################################################################
function tzarafa_server.MONITOR_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-monitor');
end;
//##############################################################################
function tzarafa_server.GATEWAY_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-gateway');
end;
//##############################################################################
function tzarafa_server.INDEXER_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-indexer');
end;
//##############################################################################
function tzarafa_server.LICENSED_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-licensed');
end;
//##############################################################################
function tzarafa_server.DAGENT_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-dagent');
end;
//##############################################################################
function tzarafa_server.ICAL_BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('zarafa-ical');
end;
//##############################################################################
function tzarafa_server.SERVER_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-server.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-server.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF_PATTERN(SERVER_BIN_PATH()+' -c /etc/zarafa/');
end;
//##############################################################################
function tzarafa_server.INDEXER_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-indexer.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-indexer.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF_PATTERN(INDEXER_BIN_PATH()+' -c /etc/zarafa/');
end;
//##############################################################################


function tzarafa_server.ICAL_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-ical.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-ical.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF(ICAL_BIN_PATH());
end;
//##############################################################################

function tzarafa_server.SPOOLER_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-spooler.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-spooler.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF_PATTERN(SPOOLER_BIN_PATH()+' -c /etc/zarafa/');
end;
//##############################################################################
function tzarafa_server.DAGENT_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-dagent.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-dagent.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF_PATTERN(DAGENT_BIN_PATH()+'.+?-d -c /etc/zarafa/');
end;
//##############################################################################


function tzarafa_server.MONITOR_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-monitor.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-monitor.pid');
     end;

     if length(result)>2 then exit;
     result:=SYS.PIDOF_PATTERN(MONITOR_BIN_PATH()+' -c /etc/zarafa/');
end;
//##############################################################################
function tzarafa_server.GATEWAY_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-gateway.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-gateway.pid');
     end;

     if length(result)>2 then begin
        if SYS.PROCESS_EXIST(result) then exit;
     end;
     result:=SYS.PIDOF_PATTERN(GATEWAY_BIN_PATH()+' -c /etc/zarafa/');

end;
//##############################################################################
function tzarafa_server.LICENSED_GET_PID():string;
Var
   RegExpr:TRegExpr;
   list:TstringList;
   i:integer;
   PidPath:string;
   D:boolean;
begin
     if FileExists('/var/run/zarafa-licensed.pid') then begin
        result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-licensed.pid');
     end;

     if length(result)>2 then begin
        if SYS.PROCESS_EXIST(result) then exit;
     end;
     result:=SYS.PIDOF(LICENSED_BIN_PATH());
     result:=SYS.PIDOF_PATTERN(LICENSED_BIN_PATH()+' -c /etc/zarafa/');
end;
//##############################################################################
function tzarafa_server.GET_CERTIFICATE_INFO(key:string):string;
var
   l:TstringList;
   RegExpr:TRegExpr;
   i:integer;
   chg:boolean;
begin
    if not FileExists('/etc/artica-postfix/ssl.certificate.conf') then exit;
    l:=Tstringlist.Create;
    l.LoadFromFile('/etc/artica-postfix/ssl.certificate.conf');
    chg:=false;
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='^'+key+'.*?=(.+)';
    for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
             logs.DebugLogs('Starting......: OpenVPN '+key+' '+RegExpr.Match[1]+' in line '+IntTostr(i));
             result:=trim(RegExpr.Match[1]);
             break;
         end;

    end;


    l.free;
    RegExpr.free;
end;
//#########################################################################################
procedure tzarafa_server.BuildCertificate();
var
l:TstringList;
server_name,cmd:string;
RegExpr:TRegExpr;
i:integer;
pass:string;
KeyPass:string;
OpenSSLconfigFile,openssl,CertificateMaxDays:String;
KEY_COUNTRY:string;
KEY_PROVINCE,KEY_CITY,KEY_ORG,KEY_EMAIL,cf_path,extensions:string;
begin


  l:=TstringList.Create;
  server_name:=UpperCase(SYS.HOSTNAME_g());
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='(.+?)\.';
  if RegExpr.Exec(server_name) then server_name:=RegExpr.Match[1];
  RegExpr.free;
  keyPass:='/etc/ssl/certs/zarafa-server/keys';

  if SYS.COMMANDLINE_PARAMETERS('--rebuild') then fpsystem('/bin/rm /etc/ssl/certs/zarafa-server/keys/*');

SetCurrentDir('/etc/artica-postfix/zarafa');
KEY_COUNTRY:=GET_CERTIFICATE_INFO('countryName_value');
KEY_PROVINCE:=GET_CERTIFICATE_INFO('stateOrProvinceName_value');
KEY_CITY:=GET_CERTIFICATE_INFO('localityName_value');
KEY_ORG:=GET_CERTIFICATE_INFO('organizationName_value');
KEY_EMAIL:=GET_CERTIFICATE_INFO('emailAddress_value');
pass:=ldap.ldap_settings.password;


if length(KEY_PROVINCE)=0 then KEY_PROVINCE:='CA';
if length(KEY_COUNTRY)=0 then KEY_COUNTRY:='US';
if length(KEY_CITY)=0 then KEY_CITY:='SanFrancisco';
if length(KEY_ORG)=0 then KEY_ORG:='Fort-fuston';
if length(KEY_EMAIL)=0 then KEY_EMAIL:='me@localhost.localdomain';

l.add('export EASY_RSA="/etc/artica-postfix/zarafa"');
l.add('export OPENSSL="openssl"');
l.add('export PKCS11TOOL="pkcs11-tool"');
l.add('export GREP="grep"');
l.add('export KEY_CONFIG=`/etc/ssl/certs/zarafa-server/whichopensslcnf /etc/artica-postfix/zarafa`');
l.add('export KEY_DIR="$EASY_RSA/keys"');
l.add('export PKCS11_MODULE_PATH="dummy"');
l.add('export PKCS11_PIN="dummy"');
l.add('export KEY_SIZE=1024');
l.add('export CA_EXPIRE=3650');
l.add('export KEY_EXPIRE=3650');
l.add('export KEY_COUNTRY="'+ KEY_COUNTRY+'"');
l.add('export KEY_PROVINCE="'+ KEY_PROVINCE+'"');
l.add('export KEY_CITY="'+KEY_CITY+'"');
l.add('export KEY_ORG="'+KEY_ORG+'"');
l.add('export KEY_EMAIL="'+ KEY_EMAIL+'"');
forceDirectories('/etc/ssl/certs/zarafa-server');
try
l.SaveToFile('/etc/ssl/certs/zarafa-server/vars');
except
 logs.Syslogs('BuildCertificate():: Unable to save file /etc/ssl/certs/zarafa-server/vars');
 exit;
end;

for i:=0 to l.Count-1 do begin
   logs.OutputCmd(l.Strings[i]);
end;

forceDirectories('/etc/ssl/certs/zarafa-server/keys');




l.Clear;
l.add('# For use with easy-rsa version 2.0');
l.add('HOME='+chr(9)+' .');
l.add('RANDFILE='+chr(9)+' /root/.rnd');
l.add('openssl_conf='+chr(9)+' openssl_init');
l.add('');
l.add('[ openssl_init ]');
l.add('oid_section='+chr(9)+'new_oids');
l.add('engines                ='+chr(9)+'engine_section');
l.add('[ new_oids ]');
l.add('[ ca ]');
l.add('default_ca='+chr(9)+'CA_default		');
l.add('[ CA_default ]');
l.add('');
l.add('dir='+chr(9)+'/etc/ssl/certs/zarafa-server/keys');
l.add('certs='+chr(9)+'/etc/ssl/certs/zarafa-server/keys');
l.add('crl_dir='+chr(9)+'/etc/ssl/certs/zarafa-server/keys');
l.add('database='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/index.txt');
l.add('new_certs_dir='+chr(9)+'/etc/ssl/certs/zarafa-server/keys');
l.add('certificate='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/ca.crt');
l.add('serial='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/serial');
l.add('crl='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/crl.pem');
l.add('private_key='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/ca.key');
l.add('RANDFILE='+chr(9)+'/etc/ssl/certs/zarafa-server/keys/.rand');
l.add('');
l.add('x509_extensions='+chr(9)+'usr_cert		# The extentions to add to the cert');
l.add('default_days='+chr(9)+'3650			# how long to certify for');
l.add('default_crl_days= 30			# how long before next CRL');
l.add('default_md='+chr(9)+'md5			# which md to use.');
l.add('preserve='+chr(9)+'no			# keep passed DN ordering');
l.add('policy='+chr(9)+'policy_anything');
l.add('');
l.add('[ policy_match ]');
l.add('countryName='+chr(9)+'match');
l.add('stateOrProvinceName='+chr(9)+'match');
l.add('organizationName='+chr(9)+'match');
l.add('organizationalUnitName='+chr(9)+'optional');
l.add('commonName='+chr(9)+'supplied');
l.add('emailAddress='+chr(9)+'optional');
l.add('[ policy_anything ]');
l.add('countryName='+chr(9)+'optional');
l.add('stateOrProvinceName='+chr(9)+'optional');
l.add('localityName='+chr(9)+'optional');
l.add('organizationName='+chr(9)+'optional');
l.add('organizationalUnitName='+chr(9)+'optional');
l.add('commonName='+chr(9)+'supplied');
l.add('emailAddress='+chr(9)+'optional');
l.add('[ req ]');
l.add('default_bits='+chr(9)+'1024');
l.add('default_keyfile ='+chr(9)+'privkey.pem');
l.add('distinguished_name='+chr(9)+'req_distinguished_name');
l.add('attributes='+chr(9)+'req_attributes');
l.add('x509_extensions='+chr(9)+'v3_ca');
l.add('');
l.add('# Passwords for private keys if not present they will be prompted for');
l.add('# input_password='+chr(9)+'secret');
l.add('# output_password='+chr(9)+'secret');
l.add('string_mask='+chr(9)+'nombstr');
l.add('');
l.add('[ req_distinguished_name ]');
l.add('countryName='+chr(9)+'Country Name (2 letter code)');
l.add('countryName_default='+chr(9)+KEY_COUNTRY);
l.add('countryName_min='+chr(9)+'2');
l.add('countryName_max='+chr(9)+'2');
l.add('stateOrProvinceName='+chr(9)+'State or Province Name (full name)');
l.add('stateOrProvinceName_default='+chr(9)+KEY_PROVINCE);
l.add('localityName='+chr(9)+'Locality Name (eg, city)');
l.add('localityName_default='+chr(9)+KEY_CITY);
l.add('0.organizationName='+chr(9)+'Organization Name (eg, company)');
l.add('0.organizationName_default='+chr(9)+KEY_ORG);
l.add('organizationalUnitName='+chr(9)+'Organizational Unit Name (eg, section)');
l.add('commonName='+chr(9)+'Common Name (eg, your name or your server\''s hostname)');
l.add('commonName_max='+chr(9)+'64');
l.add('emailAddress='+chr(9)+'Email Address');
l.add('emailAddress_default='+chr(9)+KEY_EMAIL);
l.add('emailAddress_max='+chr(9)+'40');
l.add('organizationalUnitName_default='+chr(9)+KEY_ORG);
l.add('commonName_default='+chr(9)+KEY_ORG);
l.add('[ req_attributes ]');
l.add('challengePassword='+chr(9)+'A challenge password');
l.add('challengePassword_min='+chr(9)+'4');
l.add('challengePassword_max='+chr(9)+'20');
l.add('unstructuredName='+chr(9)+'An optional company name');
l.add('');
l.add('[ usr_cert ]');
l.add('basicConstraints=CA:FALSE');
l.add('nsComment='+chr(9)+'"Easy-RSA Generated Certificate"');
l.add('subjectKeyIdentifier=hash');
l.add('authorityKeyIdentifier=keyid,issuer:always');
l.add('extendedKeyUsage=clientAuth');
l.add('keyUsage='+chr(9)+'digitalSignature');
l.add('[ server ]');
l.add('basicConstraints=CA:FALSE');
l.add('nsCertType='+chr(9)+'server');
l.add('nsComment='+chr(9)+'"Easy-RSA Generated Server Certificate"');
l.add('subjectKeyIdentifier=hash');
l.add('authorityKeyIdentifier=keyid,issuer:always');
l.add('extendedKeyUsage=serverAuth');
l.add('keyUsage='+chr(9)+'digitalSignature, keyEncipherment');
l.add('');
l.add('[ v3_req ]');
l.add('basicConstraints='+chr(9)+'CA:FALSE');
l.add('keyUsage='+chr(9)+'nonRepudiation, digitalSignature, keyEncipherment');
l.add('[ v3_ca ]');
l.add('subjectKeyIdentifier=hash');
l.add('authorityKeyIdentifier=keyid:always,issuer:always');
l.add('basicConstraints='+chr(9)+'CA:true');
l.add('[ crl_ext ]');
l.add('authorityKeyIdentifier=keyid:always,issuer:always');
l.add('');
l.add('[ engine_section ]');
l.add('[ pkcs11_section ]');
l.add('engine_id='+chr(9)+'pkcs11');
l.add('dynamic_path='+chr(9)+'/usr/lib/engines/engine_pkcs11.so');
l.add('MODULE_PATH='+chr(9)+'dummy');
l.add('PIN='+chr(9)+'dummy');
l.add('init='+chr(9)+'0');

cf_path:=SYS.OPENSSL_CONFIGURATION_PATH();
openssl:=SYS.LOCATE_OPENSSL_TOOL_PATH();
CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
if length(SYS.OPENSSL_CERTIFCATE_HOSTS())>0 then extensions:=' -extensions HOSTS_ADDONS ';
if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';
ForceDirectories(keyPass);

try
   l.SaveToFile(keyPass+'/openssl.cnf');
except
logs.Syslogs('BuildCertificate():: Unable to save file /etc/ssl/certs/zarafa-server/openssl.cnf');
 exit;
end;
SetCurrentDir('/etc/artica-postfix/zarafa');
logs.OutputCmd('/bin/chmod 777 /etc/ssl/certs/zarafa-server/vars');
if ParamStr(2)='--rebuild' then begin
   SetCurrentDir('/etc/artica-postfix/zarafa');
   fpsystem('. ./vars');
   fpsystem('./clean-all');
end;



if not FileExists(keyPass+'/index.txt') then begin
   logs.WriteToFile(' ',keyPass+'/index.txt');
end else begin
    logs.DebugLogs('Starting......: zarafa index.txt OK');
end;

if not FileExists(keyPass+'/serial') then begin
   logs.WriteToFile('01',keyPass+'/serial');
end else begin
    logs.DebugLogs('Starting......: zarafa serial OK');
end;

OpenSSLconfigFile:=SYS.OPENSSL_CONFIGURATION_PATH();





if not FileExists(keyPass+'/ca.key') then begin
   cmd:=openssl+' req -new -x509 -keyout '+keyPass+'/ca.key -out '+keyPass+'/ca.crt -config '+OpenSSLconfigFile+' -passout pass:'+pass+' -batch -days '+CertificateMaxDays;
   fpsystem(cmd);
   logs.Debuglogs(cmd);
end else begin
    logs.DebugLogs('Starting......: zarafa /etc/ssl/certs/zarafa-server/keys/ca.key OK');
end;

if not FileExists(keyPass+'/zarafa-ca.key') then begin
   cmd:=openssl+' req -new -batch -keyout '+keyPass+'/zarafa-ca.key -out '+keyPass+'/zarafa-ca.csr -config '+OpenSSLconfigFile+' -passout pass:'+pass;
   logs.Debuglogs(cmd);
   fpsystem(cmd);


end else begin
    logs.DebugLogs('Starting......: zarafa /etc/ssl/certs/zarafa-server/keys/zarafa-ca.key OK');
end;


if not FileExists(keyPass+'/privkey.pem') then begin
   cmd:=openssl+' genrsa -out '+keyPass+'/privkey.pem 2048';
   fpsystem(cmd);
   logs.Debuglogs(cmd);
end else begin
    logs.DebugLogs('Starting......: zarafa '+keyPass+'/privkey.key OK');
end;

if not FileExists(keyPass+'/cert.pem') then begin
   cmd:=openssl+' req -new -x509 -key '+keyPass+'/privkey.pem -out '+keyPass+'/cert.pem -config '+OpenSSLconfigFile+' -days '+CertificateMaxDays;
   fpsystem(cmd);
   logs.Debuglogs(cmd);
end else begin
    logs.DebugLogs('Starting......: zarafa '+keyPass+'/cert.pem OK');
end;


if not FileExists(keyPass+'/server_ssl_key_file.pem') then begin
   cmd:='/bin/cat '+keyPass+'/privkey.pem > '+keyPass+'/server_ssl_key_file.pem';
   fpsystem(cmd);
   logs.Debuglogs(cmd);
   cmd:='/bin/cat '+keyPass+'/cert.pem >> '+keyPass+'/server_ssl_key_file.pem';
   fpsystem(cmd);
   logs.Debuglogs(cmd);
end
else begin
    logs.DebugLogs('Starting......: zarafa '+keyPass+'/server_ssl_key_file.pem OK');
end;


if not FileExists(keyPass+'/zarafa-ca.crt') then begin
   cmd:=openssl+' ca -extensions v3_ca -days 3650 -out '+keyPass+'/zarafa-ca.crt -in '+keyPass+'/zarafa-ca.csr -batch -config '+OpenSSLconfigFile+' -passin pass:'+pass;
   logs.Debuglogs(cmd);
   fpsystem(cmd);

end else begin
    logs.DebugLogs('Starting......: zarafa '+keyPass+'/zarafa-ca.crt OK');
end;

fpsystem('/bin/cat '+keyPass+'/ca.crt '+keyPass+'/zarafa-ca.crt > '+keyPass+'/allca.crt');

if not FileExists(keyPass+'/zarafa-server.key') then begin
   cmd:=openssl+' req -nodes -new -keyout '+keyPass+'/zarafa-server.key -out '+keyPass+'/zarafa-server.csr -batch -config '+OpenSSLconfigFile;
   logs.Debuglogs(cmd);
   fpsystem(cmd);

end else begin
    logs.DebugLogs('Starting......: zarafa /etc/ssl/certs/zarafa-server/keys/zarafa-server.key OK');
end;

if not FileExists(keyPass+'/zarafa-server.crt') then begin
   fpsystem('/bin/rm '+keyPass+'/index.txt');
   fpsystem('/bin/touch '+keyPass+'/index.txt');
   cmd:='openssl ca -keyfile '+keyPass+'/zarafa-ca.key -cert '+keyPass+'/zarafa-ca.crt -out /etc/ssl/certs/zarafa-server/keys/zarafa-server.crt';
   cmd:=cmd+' -in '+keyPass+'/zarafa-server.csr -extensions server -batch -config '+OpenSSLconfigFile+' -passin pass:'+pass;
   logs.Debuglogs(cmd);
   fpsystem(cmd);

end else begin
    logs.DebugLogs('Starting......: zarafa '+keyPass+'/zarafa-server.crt OK');
end;

fpsystem('/bin/chmod 0600 '+keyPass+'/zarafa-server.key');
logs.WriteToFile(pass,keyPass+'/password');

if not FileExists(keyPass+'/dh1024.pem') then begin
   if length(SYS.PIDOF_PATTERN(SYS.LOCATE_OPENSSL_TOOL_PATH()+' dhparam -out'))=0 then begin
      logs.Debuglogs(SYS.LOCATE_OPENSSL_TOOL_PATH() +' dhparam -out '+keyPass+'/dh1024.pem 1024');
      fpsystem(SYS.LOCATE_OPENSSL_TOOL_PATH() +' dhparam -out '+keyPass+'/dh1024.pem 1024 &');
   end;
end else begin
    logs.DebugLogs('Starting......: zarafa dh1024.pem OK');
end;

logs.OutputCmd('/bin/chmod 0600 '+keyPass+'/*');

l.free;
end;

//#########################################################################################


procedure tzarafa_server.server_cfg();
var
   l:TStringList;
   RegExpr:TRegExpr;

   ZarafaUserSafeMode:integer;
   user_safe_mode:string;
   innodb_file_per_table:integer;
   pluginpath:string;
   ZarafaServerListenIP:string;
   ZarafaStoreOutside:integer;
   ZarafaStoreOutsidePath,CertificateMaxDays:string;
   attachment_storage:string;
   ZarafaStoreCompressionLevel:integer;
   ZarafaServerSMTPPORT,EnableZarafaIndexer,ZarafaIndexerInterval,ZarafaIndexerThreads,ZarafaPop3Enable,ZarafaPop3sEnable,ZarafaIMAPEnable:integer;
   ZarafaIMAPsEnable,ZarafaPop3Port,ZarafaIMAPPort,ZarafaPop3sPort,ZarafaIMAPsPort,ZarafaWebNTLM,Zarafa7Pop3Disable,Zarafa7IMAPDisable,ZarafaMAPISSLEnabled:integer;
   ZarafaServerSMTPIP,APACHE_SRC_ACCOUNT,disabled_features,ZarafaGatewayBind,database_password,ZarafadAgentJunkValue,ZarafadAgentJunkHeader:string;
   CyrusToAD:integer;
   ZarafaDeliverBind:string;
   ZarafaMajorVersion,ZarafaMinorVersion,ZarafaSoftDelete,ZarafaCacheCellSize,ZarafaCacheObjectSize,ZarafaCacheIndexedObjectSize,ZarafaCacheQuotaLifeTime:Integer;
   ZarafaCacheQuotaSize,ZarafaCacheAclSize,ZarafaCacheUserSize,ZarafaCacheUserDetailsSize,ZarafaCacheUserDetailsLifeTime,ZarafaThreadStackSize,ZarafaCacheServerSize,ZarafadAgentJunk:integer;
   ZarafaMySQLServiceType:integer;
   EnableZarafaSearch,EnableZarafaSearchAttach,ZarafaLogLevel,ZarafaEnableSecurityLogging,ZarafaDedicateMySQLServer:integer;
begin
attachment_storage:='database';
if not TryStrToInt(SYS.GET_INFO('ZarafaUserSafeMode'),ZarafaUserSafeMode) then ZarafaUserSafeMode:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaMAPISSLEnabled'),ZarafaMAPISSLEnabled) then ZarafaMAPISSLEnabled:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaStoreOutside'),ZarafaStoreOutside) then ZarafaStoreOutside:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaStoreCompressionLevel'),ZarafaStoreCompressionLevel) then ZarafaStoreCompressionLevel:=6;
if not TryStrToInt(SYS.GET_INFO('CyrusToAD'),CyrusToAD) then CyrusToAD:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaServerSMTPPORT'),ZarafaServerSMTPPORT) then ZarafaServerSMTPPORT:=25;
if not TryStrToInt(SYS.GET_INFO('EnableZarafaIndexer'),EnableZarafaIndexer) then EnableZarafaIndexer:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaIndexerInterval'),ZarafaIndexerInterval) then ZarafaIndexerInterval:=60;
if not TryStrToInt(SYS.GET_INFO('ZarafaIndexerThreads'),ZarafaIndexerThreads) then ZarafaIndexerThreads:=2;

if not TryStrToInt(SYS.GET_INFO('ZarafaPop3Enable'),ZarafaPop3Enable) then ZarafaPop3Enable:=1;
if not TryStrToInt(SYS.GET_INFO('ZarafaPop3sEnable'),ZarafaPop3sEnable) then ZarafaPop3sEnable:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaIMAPEnable'),ZarafaIMAPEnable) then ZarafaIMAPEnable:=1;
if not TryStrToInt(SYS.GET_INFO('ZarafaIMAPsEnable'),ZarafaIMAPsEnable) then ZarafaIMAPsEnable:=0;


if not TryStrToInt(SYS.GET_INFO('ZarafaPop3Port'),ZarafaPop3Port) then ZarafaPop3Port:=110;
if not TryStrToInt(SYS.GET_INFO('ZarafaIMAPPort'),ZarafaIMAPPort) then ZarafaIMAPPort:=143;
if not TryStrToInt(SYS.GET_INFO('ZarafaPop3sPort'),ZarafaPop3sPort) then ZarafaPop3sPort:=995;
if not TryStrToInt(SYS.GET_INFO('ZarafaIMAPsPort'),ZarafaIMAPsPort) then ZarafaIMAPsPort:=993;
if not TryStrToInt(SYS.GET_INFO('ZarafaWebNTLM'),ZarafaWebNTLM) then ZarafaWebNTLM:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaSoftDelete'),ZarafaSoftDelete) then ZarafaSoftDelete:=30;

if not TryStrToInt(SYS.GET_INFO('ZarafaCacheCellSize'),ZarafaCacheCellSize) then ZarafaCacheCellSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheObjectSize'),ZarafaCacheObjectSize) then ZarafaCacheObjectSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheIndexedObjectSize'),ZarafaCacheIndexedObjectSize) then ZarafaCacheIndexedObjectSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheQuotaSize'),ZarafaCacheQuotaSize) then ZarafaCacheQuotaSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheQuotaLifeTime'),ZarafaCacheQuotaLifeTime) then ZarafaCacheQuotaLifeTime:=1;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheAclSize'),ZarafaCacheAclSize) then ZarafaCacheAclSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheUserSize'),ZarafaCacheUserSize) then ZarafaCacheUserSize:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheUserDetailsSize'),ZarafaCacheUserDetailsSize) then ZarafaCacheUserDetailsSize:=0;

if not TryStrToInt(SYS.GET_INFO('ZarafaCacheUserDetailsLifeTime'),ZarafaCacheUserDetailsLifeTime) then ZarafaCacheUserDetailsLifeTime:=5;
if not TryStrToInt(SYS.GET_INFO('ZarafaThreadStackSize'),ZarafaThreadStackSize) then ZarafaThreadStackSize:=512;
if not TryStrToInt(SYS.GET_INFO('ZarafaCacheServerSize'),ZarafaCacheServerSize) then ZarafaCacheServerSize:=1048576;

if not TryStrToInt(SYS.GET_INFO('EnableZarafaSearch'),EnableZarafaSearch) then EnableZarafaSearch:=0;
if not TryStrToInt(SYS.GET_INFO('EnableZarafaSearchAttach'),EnableZarafaSearchAttach) then EnableZarafaSearchAttach:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaLogLevel'),ZarafaLogLevel) then ZarafaLogLevel:=2;
if not TryStrToInt(SYS.GET_INFO('ZarafaEnableSecurityLogging'),ZarafaEnableSecurityLogging) then ZarafaEnableSecurityLogging:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaMySQLServiceType'),ZarafaMySQLServiceType) then ZarafaMySQLServiceType:=1;
if not TryStrToInt(SYS.GET_INFO('ZarafaDedicateMySQLServer'),ZarafaDedicateMySQLServer) then ZarafaDedicateMySQLServer:=0;



ZarafaDeliverBind:=SYS.GET_INFO('ZarafaDeliverBind');
if length(ZarafaDeliverBind)=0 then ZarafaDeliverBind:='127.0.0.1';
if not TryStrToInt(SYS.GET_INFO('ZarafadAgentJunk'),ZarafadAgentJunk) then ZarafadAgentJunk:=0;
if ZarafadAgentJunk=1 then begin
    ZarafadAgentJunkValue:=trim(SYS.GET_INFO('ZarafadAgentJunkValue'));
    ZarafadAgentJunkHeader:=trim(SYS.GET_INFO('ZarafadAgentJunkHeader'));
end;




CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';


RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^([0-9]+)\.([0-9]+)';
if RegExpr.Exec(VERSION(true)) then begin
   if not TryStrToInt(RegExpr.Match[1], ZarafaMajorVersion) then ZarafaMajorVersion:=6;
   if not TryStrToInt(RegExpr.Match[2], ZarafaMinorVersion) then ZarafaMinorVersion:=0;



end;

logs.DebugLogs('Starting zarafa..............: Zarafa-server major version :'+INtTOstr(ZarafaMajorVersion)+' minor version :'+INtTOstr(ZarafaMinorVersion));

if ZarafaMajorVersion>6 then begin
   if not TryStrToInt(SYS.GET_INFO('Zarafa7Pop3Disable'),Zarafa7Pop3Disable) then Zarafa7Pop3Disable:=0;
   if not TryStrToInt(SYS.GET_INFO('Zarafa7IMAPDisable'),Zarafa7IMAPDisable) then Zarafa7IMAPDisable:=0;
   if Zarafa7Pop3Disable=1 then disabled_features:='pop3';
   if Zarafa7IMAPDisable=1 then disabled_features:=disabled_features+' imap';
end;

ZarafaServerSMTPIP:=trim(SYS.GET_INFO('ZarafaServerSMTPIP'));
if length(ZarafaServerSMTPIP)=0 then ZarafaServerSMTPIP:='127.0.0.1';
if ZarafaServerSMTPIP='0.0.0.0' then ZarafaServerSMTPIP:='127.0.0.1';
ZarafaStoreOutsidePath:=trim(SYS.GET_INFO('ZarafaStoreOutsidePath'));


if not TryStrToInt(SYS.GET_INFO('innodb_file_per_table'),innodb_file_per_table) then begin
   SYS.set_INFO('innodb_file_per_table','1');
   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.mysql.build.php');
   fpsystem('/etc/init.d/artica-postfix restart mysql');
end;

if length(ZarafaStoreOutsidePath)<3 then ZarafaStoreOutsidePath:='/var/lib/zarafa';


ZarafaServerListenIP:=SYS.GET_INFO('ZarafaServerListenIP');
ZarafaGatewayBind:=SYS.GET_INFO('ZarafaGatewayBind');
if length(trim(ZarafaServerListenIP))=0 then ZarafaServerListenIP:='127.0.0.1';
if length(trim(ZarafaGatewayBind))=0 then ZarafaGatewayBind:='0.0.0.0';

if(ZarafaServerListenIP<>'127.0.0.1') then begin

if(ZarafaServerListenIP<>'0.0.0.0') then begin
   if not ifIpAddrAvailable(trim(ZarafaServerListenIP)) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-server '+ZarafaServerListenIP+' is not available, switch to 127.0.0.1');
      ZarafaServerListenIP:='127.0.0.1';
    end;
end;
end;

forceDirectories('/var/log/zarafa');

if ZarafaUserSafeMode=1 then user_safe_mode:='yes' else user_safe_mode:='no';

logs.DebugLogs('Starting zarafa..............: Zarafa-server user safe mode:'+user_safe_mode);
if(ZarafaStoreOutside=1) then begin
  attachment_storage:='files';
  logs.DebugLogs('Starting zarafa..............: Zarafa-server store attachments in "'+ZarafaStoreOutsidePath+'"');
  try ForceDirectories('ZarafaStoreOutsidePath') except logs.DebugLogs('Starting zarafa..............: Fatal error while creating Zarafa attachments path') end;
end;

if ZarafaWebNTLM=1 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server NTLM Mode is enabled');
   fpsystem(SYS.LOCATE_PHP5_BIN()+ ' /usr/share/artica-postfix/exec.freeweb.php --apache-user');
   APACHE_SRC_ACCOUNT:=SYS.GET_INFO('APACHE_SRC_ACCOUNT');
end else begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server NTLM Mode is disabled');
end;

database_password:=SYS.MYSQL_INFOS('database_password');
if database_password='!nil' then database_password:='';

l:=Tstringlist.Create;
l.add('server_bind		= '+ZarafaServerListenIP);
l.add('server_hostname          = '+SYS.HOSTNAME_g());
l.add('server_tcp_enabled	= yes');
l.add('server_tcp_port		= 236');
l.add('server_pipe_enabled	= yes');
l.add('server_pipe_name	= /var/run/zarafa');
l.add('server_name = Zarafa');
l.add('database_engine		= mysql');
l.add('allow_local_users	= yes');
l.add('local_admin_users	= root vmail mail '+APACHE_SRC_ACCOUNT);
l.add('system_email_address	= postmaster@localhost');
l.add('run_as_user		= ');
l.add('run_as_group		= ');
l.add('pid_file		= /var/run/zarafa-server.pid');
l.add('running_path = /');
l.add('session_timeout		= 300');
l.add('license_socket		= /var/run/zarafa-licensed');
l.add('log_method		= syslog');
if(ZarafaEnableSecurityLogging=1) then begin
l.add('audit_log_enabled         = yes');
l.add('audit_log_method         = syslog');
l.add('audit_log_file           = -');
l.add('audit_log_level          = '+IntToStr(ZarafaLogLevel));
l.add('audit_log_timestamp      = 0');
end;
l.add('log_file		= /var/log/zarafa/server.log');
l.add('log_level		= '+IntTostr(ZarafaLogLevel));
l.add('log_timestamp		= 1');

if ZarafaMySQLServiceType=3 then begin
   if ZarafaDedicateMySQLServer=0 then ZarafaMySQLServiceType:=2;
end;

if ZarafaMySQLServiceType=1 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server Using the same Artica MySQL server '+SYS.MYSQL_INFOS('mysql_server'));
   l.add('mysql_host		= '+SYS.MYSQL_INFOS('mysql_server'));
   l.add('mysql_port		= '+SYS.MYSQL_INFOS('port'));
   l.add('mysql_user		= '+SYS.MYSQL_INFOS('database_admin'));
   if length(database_password)>0 then l.add('mysql_password		= '+SYS.MYSQL_INFOS('database_password'));
   l.add('mysql_database		= zarafa');
end;
if ZarafaMySQLServiceType=2 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server Using the local MySQL server Unix socket');
   l.add('mysql_socket		= /var/run/mysqld/mysqld.sock');
   l.add('mysql_user		= '+SYS.MYSQL_INFOS('database_admin'));
   if length(database_password)>0 then l.add('mysql_password		= '+SYS.MYSQL_INFOS('database_password'));
   l.add('mysql_database		= zarafa');
end;
if ZarafaMySQLServiceType=3 then begin
   if ZarafaDedicateMySQLServer=1 then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-server Using the Dedicate MySQL service using Unix socket');
      l.add('mysql_socket		= /var/run/mysqld/zarafa-db.sock');
      l.add('mysql_user		= root');
      l.add('mysql_database	= zarafa');
   end;
end;
if ZarafaMySQLServiceType=4 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server Using a remote MySQL service '+SYS.GET_INFO('ZarafaRemoteMySQLServer')+':'+SYS.GET_INFO('ZarafaRemoteMySQLServerPort'));
   l.add('mysql_host		= '+SYS.GET_INFO('ZarafaRemoteMySQLServer'));
   l.add('mysql_port		= '+SYS.GET_INFO('ZarafaRemoteMySQLServerPort'));
   l.add('mysql_user		= '+SYS.GET_INFO('ZarafaRemoteMySQLServerAdmin'));
   if length(database_password)>0 then l.add('mysql_password		= '+SYS.GET_INFO('ZarafaRemoteMySQLServerPassword'));
   l.add('mysql_database		= zarafa');
end;

l.add('attachment_storage	= '+attachment_storage);
l.add('attachment_path		= '+ZarafaStoreOutsidePath);
l.add('attachment_compression	= '+IntTOStr(ZarafaStoreCompressionLevel));
if EnableZarafaIndexer=1 then begin
   l.add('index_services_enabled = yes');
   l.add('index_services_path = file://var/run/zarafa-indexer');
end else begin
    l.add('index_services_enabled = no');
end;


if ZarafaMajorVersion>6 then begin
   if ZarafaMinorVersion>0 then begin
     l.add('enable_enhanced_ics = yes');

      if EnableZarafaSearch=1 then begin
         l.add('search_enabled = yes');
         l.add('search_socket = file://var/run/zarafa-search');
      end else begin
         l.add('search_enabled = no');
      end;
   end;
end;


if ZarafaWebNTLM=1 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa-server NTLM enabled, Apache user: '+APACHE_SRC_ACCOUNT);
   l.add('enable_sso_ntlmauth      = yes');
end else begin
    l.add('enable_sso_ntlmauth     = no');
end;




if ZarafaMAPISSLEnabled=1 then begin
   l.add('server_ssl_enabled	= yes');
   BuildCertificate();
end else begin
   l.add('server_ssl_enabled	= no');
end;
l.add('server_ssl_port		= 237');
l.add('server_ssl_key_file	= /etc/ssl/certs/zarafa-server/keys/server_ssl_key_file.pem');
l.add('server_ssl_key_pass	= ');
l.add('server_ssl_ca_file	= /etc/ssl/certs/zarafa-server/keys/server_ssl_key_file.pem');
l.add('server_ssl_ca_path	= /etc/ssl/certs/zarafa');
l.add('sslkeys_path		= /etc/ssl/certs/zarafa');
l.add('softdelete_lifetime	= '+IntToStr(ZarafaSoftDelete));
l.add('sync_lifetime		= '+CertificateMaxDays);
l.add('sync_log_all_changes = yes');
l.add('enable_gab = yes');
l.add('auth_method = plugin');
l.add('pam_service = passwd');
if ZarafaCacheCellSize>0 then l.add('cache_cell_size			= '+IntToStr(ZarafaCacheCellSize)) else l.add('cache_cell_size			= 16777216');
if ZarafaCacheObjectSize>0 then l.add('cache_object_size		= '+IntToStr(ZarafaCacheObjectSize)) else l.add('cache_object_size		= 5242880');
if ZarafaCacheObjectSize>0 then l.add('cache_indexedobject_size		= '+IntToStr(ZarafaCacheIndexedObjectSize)) else l.add('cache_indexedobject_size		= 16777216');
if ZarafaCacheQuotaSize>0 then l.add('cache_quota_size		= '+IntToStr(ZarafaCacheQuotaSize)) else l.add('cache_quota_size		= 1048576');
if ZarafaCacheAclSize>0 then l.add('cache_acl_size		= '+IntToStr(ZarafaCacheAclSize)) else l.add('cache_acl_size		= 1048576');
if ZarafaCacheUserSize>0 then l.add('cache_user_size		= '+IntToStr(ZarafaCacheUserSize)) else l.add('cache_user_size		= 1048576');
if ZarafaCacheUserDetailsSize>0 then l.add('cache_userdetails_size		= '+IntToStr(ZarafaCacheUserDetailsSize)) else l.add('cache_userdetails_size		= 1048576');



l.add('cache_server_size		= '+IntToStr(ZarafaCacheServerSize));
l.add('cache_quota_lifetime		= '+IntToStr(ZarafaCacheQuotaLifeTime));
l.add('cache_userdetails_lifetime	= '+IntToStr(ZarafaCacheUserDetailsLifeTime));
l.add('thread_stacksize = '+IntToStr(ZarafaThreadStackSize));
l.add('quota_warn		= 0');
l.add('quota_soft		= 0');
l.add('quota_hard		= 0');
l.add('companyquota_warn      = 0');
l.add('user_plugin		= ldap');
l.add('user_plugin_config	= /etc/zarafa/ldap.openldap.cfg');
l.add('# Multi-tenancy configurations');

pluginpath:=PLUGIN_PATH();
logs.DebugLogs('Starting zarafa..............: Zarafa-server plugin path: '+plugin_path);


l.add('# Multi-tenancy configurations');
l.add('enable_hosted_zarafa     = yes');

l.add('createuser_script	=	/etc/zarafa/userscripts/createuser');
l.add('deleteuser_script	=	/etc/zarafa/userscripts/deleteuser');
l.add('creategroup_script	=	/etc/zarafa/userscripts/creategroup');
l.add('deletegroup_script	=	/etc/zarafa/userscripts/deletegroup');
l.add('createcompany_script	=	/etc/zarafa/userscripts/createcompany');
l.add('deletecompany_script	=	/etc/zarafa/userscripts/deletecompany');



l.add('enable_distributed_zarafa = false');
l.add('storename_format = %f');
l.add('loginname_format = %u');
l.add('client_update_enabled = true');
l.add('client_update_path = /var/lib/zarafa/client');
l.add('hide_everyone = no');
l.add('plugin_path		= '+pluginpath);
l.add('user_safe_mode = '+user_safe_mode);
if ZarafaMajorVersion>6 then l.add('disabled_features = '+disabled_features);




forceDirectories('/etc/zarafa');
logs.WriteToFile(l.Text,'/etc/zarafa/server.cfg');
l.clear;
l.free;



fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.zarafa.build.stores.php --ldap-config');

// /etc/zarafa/spooler.cfg
if(ZarafaServerSMTPPORT>0) then if ZarafaServerSMTPPORT <>25 then ZarafaServerSMTPIP:=ZarafaServerSMTPIP+':'+IntToStr(ZarafaServerSMTPPORT);
l:=Tstringlist.Create;
l.add('smtp_server	=	'+ZarafaServerSMTPIP);
l.add('server_socket	=	file:///var/run/zarafa');
l.add('run_as_user = ');
l.add('run_as_group = ');
l.add('pid_file = /var/run/zarafa-spooler.pid');
l.add('running_path = /');
l.add('log_method	=	syslog');
l.add('log_level	=	'+IntTostr(ZarafaLogLevel));
l.add('log_file	=	/var/log/zarafa/spooler.log');
l.add('log_timestamp	=	1');
l.add('max_threads = 5');
l.add('fax_domain = fax.local');
l.add('fax_international = 00');
l.add('always_send_delegates = no');
l.add('allow_redirect_spoofing = no');
l.add('copy_delegate_mails = yes');
l.add('always_send_tnef = yes');
if ZarafaMajorVersion>6 then begin
   if ZarafaMinorVersion>0 then begin
      l.add('plugin_enabled     = no');
   end;
end;
logs.WriteToFile(l.Text,'/etc/zarafa/spooler.cfg');
l.clear;
l.free;

 // /etc/zarafa/indexer.cfg
if not DirectoryExists('/var/lib/zarafa/index') then ForceDirectories('/var/lib/zarafa/index');
l:=Tstringlist.Create;
l.add('index_path          =   /var/lib/zarafa/index/');
l.add('run_as_user         =');
l.add('run_as_group        =');
l.add('pid_file            =   /var/run/zarafa-indexer.pid');
l.add('running_path        =   /');
l.add('cleanup_lockfiles	=	no');
l.add('server_socket   =   file:///var/run/zarafa');
l.add('server_bind_name   =   file:///var/run/zarafa-indexer');
l.add('log_method          =   syslog');
l.add('log_level           =   '+IntTostr(ZarafaLogLevel));
l.add('log_timestamp       =   1');
l.add('index_sync_stream	= yes');
l.add('index_interval      =   '+IntToStr(ZarafaIndexerInterval));
l.add('index_threads       =   '+IntToStr(ZarafaIndexerThreads));
l.add('index_max_field_length  = 10000');
l.add('index_merge_factor      = 10');
l.add('index_max_buffered_docs	= 10');
l.add('index_min_merge_docs    = 10');
l.add('index_max_merge_docs    = 2147483647');
l.add('index_term_interval		= 128');
l.add('index_cache_timeout		= 0');
l.add('index_attachments	= yes');
l.add('index_attachment_max_size = 5120');
l.add('index_attachment_parser = /etc/zarafa/indexerscripts/attachments_parser');
l.add('index_attachment_parser_max_memory = 0 ');
l.add('index_attachment_parser_max_cputime = 0');
l.add('index_block_users		=');
l.add('index_block_companies	= ');
l.add('index_allow_servers		=');
logs.WriteToFile(l.Text,'/etc/zarafa/indexer.cfg');
l.clear;
l.free;


 // /etc/zarafa/gateway.cfg
l:=Tstringlist.Create;
l.add('server_bind	=	'+ZarafaGatewayBind);
l.add('server_socket	=	http://'+ZarafaServerListenIP+':236/zarafa');
l.add('run_as_user = root');
l.add('run_as_group = root');
l.add('pid_file = /var/run/zarafa-gateway.pid');
l.add('running_path = /');
if ZarafaPop3Enable=1 then l.add('pop3_enable	=	yes') else l.add('pop3_enable	=	no');
l.add('pop3_port	=	'+IntToStr(ZarafaPop3Port));
if ZarafaPop3sEnable=1 then l.add('pop3s_enable	=	yes') else l.add('pop3s_enable	=	no');
l.add('pop3s_port	=	'+IntTostr(ZarafaPop3sPort));
if ZarafaIMAPEnable=1 then l.add('imap_enable	=	yes') else l.add('imap_enable	=	no');
l.add('imap_port	=	'+IntToStr(ZarafaIMAPPort));
if ZarafaIMAPsEnable=1 then l.add('imaps_enable	=	yes') else l.add('imaps_enable	=	no');
l.add('imaps_port	=	'+IntToStr(ZarafaIMAPsPort));
l.add('');
l.add('');
l.add('imap_only_mailfolders	=	no');
l.add('imap_public_folders	=	yes');
l.add('imap_capability_idle = yes');
l.add('ssl_private_key_file	=	/etc/ssl/certs/postfix/ca.key');
l.add('ssl_certificate_file	=	/etc/ssl/certs/postfix/ca.crt');
l.add('ssl_verify_client	=	no');
l.add('ssl_verify_file		=	');
l.add('ssl_verify_path		=');
l.add('log_method	=	syslog');
l.add('log_level	=	'+IntTostr(ZarafaLogLevel));
l.add('log_file	=	/var/log/zarafa/gateway.log');
l.add('log_timestamp	=	1');
logs.WriteToFile(l.Text,'/etc/zarafa/gateway.cfg');
l.clear;
l.free;


 // /etc/zarafa/dagent.cfg
l:=Tstringlist.Create;
l.add('server_bind	  = '+ZarafaDeliverBind);
l.add('server_socket	  = file:///var/run/zarafa');
l.add('run_as_user        = root');
l.add('run_as_group       = root');
l.add('pid_file           = /var/run/zarafa-dagent.pid');
l.add('lmtp_max_threads   = 20');
l.add('lmtp_port          = 2003');
l.add('log_method	  = syslog');
if ZarafaMajorVersion>6 then begin
   if ZarafaMinorVersion>0 then begin
      l.add('plugin_enabled     = no');
   end;
end;
l.add('archive_on_delivery= no');
l.add('');

l.add('spam_header_name = '+ ZarafadAgentJunkHeader);
l.add('spam_header_value = '+ ZarafadAgentJunkValue);

if ZarafaMajorVersion>6 then begin
  if ZarafaMinorVersion>0 then begin
      logs.DebugLogs('Starting zarafa..............: '+IntToStr(ZarafaMajorVersion)+'.'+IntToStr(ZarafaMinorVersion)+' Adding plugins configuration in dAgent');
     l.add('##############################################################');

     if FileExists('/usr/share/pyshared/MAPICore.py') then begin
        fpsystem('/usr/sbin/update-python-modules python-mapi.public');
        l.add('# DAGENT PLUGIN SETTINGS');
        l.add('');
        l.add('# Enable the dagent plugin framework');
        l.add('plugin_enabled = no');
        l.add('plugin_manager_path = /usr/share/zarafa-dagent/python');
        l.add('');
        l.add('# Path to the activated dagent plugins.');
        l.add('#   This folder contains symlinks to the zarafa plugins and custom scripts. The plugins are');
        l.add('#   installed in ''/usr/share/zarafa-dagent/python/plugins/''. To activate a plugin create a symbolic');
        l.add('#   link in the ''plugin_path'' directory.');
        l.add('');
        l.add('# Example:');
        l.add('#  $ ln -s /usr/share/zarafa-dagent/python/plugins/BMP2PNG.py /var/lib/zarafa/dagent/plugins/BMP2PNG.py');
        l.add('plugin_path = /var/lib/zarafa/dagent/plugins');
        l.add('');

        l.add('##############################################################');
        l.add('# DAGENT RULE SETTINGS');
        l.add('# Enable the addition of X-Zarafa-Rule-Action headers on messages');
        l.add('# that have been forwarded or replied by a rule.');
        l.add('# Default: yes');
        l.add('set_rule_headers = yes');
        end;
     end;
end;


logs.WriteToFile(l.Text,'/etc/zarafa/dagent.cfg');
logs.DebugLogs('Starting zarafa..............: /etc/zarafa/dagent.cfg done...');
l.clear;
l.free;

// /etc/zarafa/monitor.cfg
l:=Tstringlist.Create;
l.add('server_socket	=	file:///var/run/zarafa');
l.add('run_as_user = ');
l.add('run_as_group = ');
l.add('pid_file = /var/run/zarafa-monitor.pid');
l.add('running_path = /');
l.add('log_method	=	syslog');
l.add('log_level	=	2');
l.add('log_file	=	/var/log/zarafa/monitor.log');
l.add('log_timestamp	=	1');
l.add('sslkey_file = /etc/zarafa/ssl/monitor.pem');
l.add('sslkey_pass = replace-with-monitor-cert-password');
l.add('mailquota_resend_interval = 1');
l.add('userquota_warning_template  =   /etc/zarafa/quotamail/userwarning.mail');
l.add('userquota_soft_template     =   /etc/zarafa/quotamail/usersoft.mail');
l.add('userquota_hard_template     =   /etc/zarafa/quotamail/userhard.mail');
l.add('companyquota_warning_template   =   /etc/zarafa/quotamail/companywarning.mail');
l.add('companyquota_soft_template      =   /etc/zarafa/quotamail/companysoft.mail');
l.add('companyquota_hard_template      =   /etc/zarafa/quotamail/companyhard.mail');
logs.WriteToFile(l.Text,'/etc/zarafa/monitor.cfg');
l.clear;
l.free;



// /etc/zarafa/licensed.cfg
l:=Tstringlist.Create;
l.add('server_pipe_name =       /var/run/zarafa-licensed');
l.add('server_socket	=	file:///var/run/zarafa');
l.add('license_path	=       /etc/zarafa/license');
l.add('run_as_user	=');
l.add('run_as_group	=');
l.add('pid_file		=       /var/run/zarafa-licensed.pid');
l.add('running_path     =       /');
l.add('log_method	=       syslog');
l.add('log_file		=       -');
l.add('log_level	=       2');
l.add('log_timestamp	=       1');
logs.WriteToFile(l.Text,'/etc/zarafa/licensed.cfg');
l.clear;
l.free;


// /etc/zarafa/search.cfg
ForceDirectories('/var/lib/zarafa/index');
l:=Tstringlist.Create;
l.add('index_path          =   /var/lib/zarafa/index/');
l.add('run_as_user         =');
l.add('run_as_group        =');
l.add('pid_file            =   /var/run/zarafa-search.pid');
l.add('running_path        =   /');
l.add('limit_results		=	0');
l.add('server_socket   =   file:///var/run/zarafa');
l.add('#sslkey_file         = /etc/zarafa/ssl/search.pem');
l.add('#sslkey_pass         = replace-with-server-cert-password');
l.add('# binding address');
l.add('# To setup for multi-server, use: http://0.0.0.0:port or https://0.0.0.0:port');
l.add('server_bind_name   =   file:///var/run/zarafa-search');
l.add('ssl_private_key_file= /etc/zarafa/search/privkey.pem');
l.add('ssl_certificate_file= /etc/zarafa/search/cert.pem');
l.add('log_method          =   syslog');
l.add('log_level           =   2');
l.add('log_file            =   /var/log/zarafa/search.log');
l.add('log_timestamp       =   1');
l.add('term_cache_size		=   64M');
l.add('#index_exclude_properties = 007D 0064 0C1E 0075 678E 678F');
if(EnableZarafaSearchAttach=1) then l.add('index_attachments	= no') else  l.add('index_attachments	= yes');
l.add('index_attachment_max_size = 5M');
l.add('index_attachment_parser = /etc/zarafa/searchscripts/attachments_parser');
l.add('index_attachment_parser_max_memory = 0 ');
l.add('index_attachment_parser_max_cputime = 0');
l.add('index_attachment_mime_filter =');
l.add('index_attachment_extension_filter =');
logs.WriteToFile(l.Text,'/etc/zarafa/search.cfg');
l.clear;
l.free;


end;
//#############################################################################
function tzarafa_server.PLUGIN_PATH():string;
begin
    if FileExists('/usr/lib/zarafa/ldapplugin.so') then exit('/usr/lib/zarafa');
    if FileExists('/usr/lib64/zarafa/ldapplugin.so') then exit('/usr/lib64/zarafa');
    if FileExists('/usr/local/lib/zarafa/ldapplugin.so') then exit('/usr/local/lib/zarafa');
    if FileExists('/usr/local/lib64/zarafa/ldapplugin.so') then exit('/usr/local/lib64/zarafa');
end;
//#############################################################################
function tzarafa_server.VERSION(nocache:boolean):string;
var
    path:string;
    RegExpr:TRegExpr;
    FileData:TStringList;
    i:integer;
    D:Boolean;
    tmpstr:string;
begin
     path:=SYS.LOCATE_GENERIC_BIN('zarafa-server');
     if not FileExists(path) then begin
        exit;
     end;


     result:=SYS.GET_CACHE_VERSION('APP_ZARAFA');
     if nocache then result:='';
     if length(result)>1 then exit;
     tmpstr:=logs.FILE_TEMP();
     FileData:=TStringList.Create;
     RegExpr:=TRegExpr.Create;
     fpsystem(path+' -V >'+ tmpstr + ' 2>&1');
     FileData.LoadFromFile(tmpstr);
 RegExpr.Expression:='version:\s+([0-9]+),([0-9]+),([0-9]+)';
  for i:=0 to FileData.Count -1 do begin
          if RegExpr.Exec(FileData.Strings[i]) then  begin
            result:=RegExpr.Match[1]+'.'+RegExpr.Match[2]+'.'+RegExpr.Match[3];
            if length(result)>1 then begin
               SYS.SET_CACHE_VERSION('APP_ZARAFA',result);
               FileData.Free;
               RegExpr.Free;
               exit;
            end;
            end;
          end;


end;
//#############################################################################
function tzarafa_server.ifIpAddrAvailable(ipadr:string):boolean;
var
RegExpr:TRegExpr;
 datas : string;
 ifconfig:string;
 l:Tstringlist;
 i:integer;
begin
 Result:=False;
 ifconfig:=SYS.LOCATE_GENERIC_BIN('ifconfig');
 if not FileExists(ifconfig) then exit;
 l:=TstringList.Create;

     fpsystem(ifconfig +' -a 2>&1 >/tmp/artica-ifIpAddrAvailable'+ipadr+'.tmp');
     if not FileExists('/tmp/artica-ifIpAddrAvailable'+ipadr+'.tmp') then exit;
     l.LoadFromFile('/tmp/artica-ifIpAddrAvailable'+ipadr+'.tmp');
     ipadr:=AnsiReplaceText(ipadr,'.','\.');
     RegExpr:=TRegExpr.create;
     RegExpr.expression:=ipadr;
     for i:=0 to l.Count-1 do begin;
         if RegExpr.Exec(l.Strings[i]) then begin
            logs.DebugLogs('Starting zarafa..............:  '+ipadr+' IP available');
            RegExpr.Free;
            Result:=True;
            exit;
         end;
   end;
end;
function tzarafa_server.IS_LDAP_ACTIVE():boolean;
var
   pid:string;
   local:boolean;
   server:string;
begin
   result:=false;
   pid:=ldap.LDAP_PID();
   server:=ldap.ldap_settings.servername;
   if server='127.0.0.1' then local:=true;
   if server='localhost' then local:=true;
   logs.DebugLogs('Starting zarafa..............: LDAP server: '+server);



   if local then begin
      if not SYS.PROCESS_EXIST(pid) then begin
         logs.DebugLogs('Starting zarafa..............: LDAP server is not available');
         exit(false);
      end;
   end;

   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.ldap.rebuild.php --test-connexion');
   if trim(logs.ReadFromFile('/etc/artica-postfix/LDAP_TESTS'))='TRUE' then begin
        result:=true;
        exit(true);
   end else begin
       result:=false;
       exit(false);
   end;


end;
//#############################################################################


procedure tzarafa_server.START();
var zbin:string;
begin

    zbin:=SERVER_BIN_PATH();
    if not FileExists(zbin) then begin
       writeln('Stopping Zarafa..............: Not installed');
       if FileExists('/home/artica/packages/ZARAFA/zarafa.tar') then begin
          fpsystem('/bin/tar -xvf /home/artica/packages/ZARAFA/zarafa.tar -C /');
          fpsystem('/bin/rm /home/artica/packages/ZARAFA/zarafa.tar');
          START();
          exit;
       end;
       exit;
    end;


if not IS_LDAP_ACTIVE() then begin
      logs.DebugLogs('Starting zarafa..............: LDAP server is not up, aborting');
      logs.NOTIFICATION('Unable to start Zarafa services (ldap is not active)','It seems that the LDAP server is not ready, trying to start zarafa in next cycle','mailbox');
      exit;
end;

  if ZarafaEnableServer=0 then begin
    logs.DebugLogs('Starting zarafa..............: Zarafa server is disabled, aborting');
    SERVER_STOP();
    LICENSED_STOP();
    SPOOLER_STOP();
    MONITOR_STOP();
    GATEWAY_STOP();
    DAGENT_STOP();
    ICAL_STOP();
    INDEXER_STOP();
    APACHE_START();
    SEARCH_STOP();
    exit;
  end;



  server_cfg();
  WEB_ACCESS_CONFIG();
  SERVER_START();
  LICENSED_START();
  SPOOLER_START();
  MONITOR_START();
  GATEWAY_START();
  DAGENT_START();
  ICAL_START();
  INDEXER_START();
  SEARCH_START();
  APACHE_START();
  SEARCH_STOP();
end;
//#############################################################################
procedure tzarafa_server.STOP();
var zbin:string;
begin

    zbin:=SERVER_BIN_PATH();
    if not FileExists(zbin) then begin
       logs.DebugLogs('Starting zarafa..............: Zarafa-server not installed');
       exit;
    end;

  SERVER_STOP();
  SPOOLER_STOP();
  MONITOR_STOP();
  GATEWAY_STOP();
  DAGENT_STOP();
  ICAL_STOP();
  INDEXER_STOP();
  LICENSED_STOP();
end;
//#############################################################################
procedure tzarafa_server.ICAL_CONFIG();
var
  ZarafaiCalPort:Integer;
  ZarafaiCalBind:string;
  l:Tstringlist;
begin
 l:=Tstringlist.Create;
 if Not TryStrToInt(SYS.GET_INFO('ZarafaiCalPort'),ZarafaiCalPort) then ZarafaiCalPort:=8088;
 ZarafaiCalBind:=SYS.GET_INFO('ZarafaiCalBind');
 if length(ZarafaiCalBind)<4 then ZarafaiCalBind:='0.0.0.0';
l.add('server_bind	=	'+ZarafaiCalBind);
l.add('run_as_user      =       root');
l.add('run_as_group     =       root');
l.add('ical_port	=	'+IntToStr(ZarafaiCalPort));
l.add('ical_enable      =       yes');
l.add('server_socket	=	http://localhost:236/zarafa');
l.add('pid_file         =	/var/run/zarafa-ical.pid');
l.add('log_method	=	syslog');
l.add('log_level	=	2');
l.add('log_file	        =	/var/log/zarafa/ical.log');
l.add('log_timestamp	=	1');
logs.DebugLogs('Starting zarafa..............: Zarafa iCal gateway listen '+ZarafaiCalBind+':'+IntToStr(ZarafaiCalPort)+' Port');
logs.WriteToFile(l.Text,'/etc/zarafa/ical.cfg');
l.clear;
l.free;
end;


procedure tzarafa_server.SERVER_START();
var
   zbin:string;
   cmd:string;
   pid:string;
   attach:string;
   count:integer;
   ZarafaStoreOutside:integer;
   conflict:string;
   tmpstr:string;
   RegExpr:TRegExpr;
   FileData:TStringList;
   i:integer;
begin
     zbin:=SERVER_BIN_PATH();
      if not FileExists(zbin) then begin
       logs.DebugLogs('Starting zarafa..............: Zarafa-server not installed');
       exit;
    end;

pid:=SERVER_GET_PID();

if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-server already running PID '+ pid);
      exit;
end;


if not IS_LDAP_ACTIVE() then begin
      logs.DebugLogs('Starting zarafa..............: LDAP server is not up, aborting');
      logs.NOTIFICATION('Unable to start Zarafa server (ldap is not active)','It seems that the LDAP server is not ready, trying to start zarafa server in next cycle','mailbox');
      exit;
end;



    if not FileExists('/usr/lib/libicui18n.so.40') then begin
       if FileExists('/usr/lib/libicui18n.so.44') then fpsystem('/bin/ln -s  /usr/lib/libicui18n.so.44 /usr/lib/libicui18n.so.40');
    end;

     if not FileExists('/usr/lib/libicuuc.so.40') then begin
        if FileExists('/usr/lib/libicuuc.so.44') then fpsystem('/bin/ln -s  /usr/lib/libicuuc.so.44 /usr/lib/libicuuc.so.40');
     end;

     if not FileExists('/usr/lib/libicudata.so.40') then begin
        if FileExists('/usr/lib/libicudata.so.44') then fpsystem('/bin/ln -s  /usr/lib/libicudata.so.44 /usr/lib/libicudata.so.40');
     end;

 fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
 fpsystem('/etc/init.d/zarafa-server start');
 pid:=SERVER_GET_PID();

if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-server success running PID '+ pid);
      if FileExists('/usr/share/doc/zarafa/zarafa7-upgrade') then begin
            if not FileExists('/etc/artica-postfix/zarafa7-upgrade') then begin
                logs.DebugLogs('Starting zarafa..............: Zarafa-server upgrading Zarafa...');
                fpsystem(SYS.LOCATE_GENERIC_BIN('touch')+ ' /etc/artica-postfix/zarafa7-upgrade');
                fpsystem(SYS.LOCATE_GENERIC_BIN('nohup')+ ' '+SYS.LOCATE_GENERIC_BIN('python') +' /usr/share/doc/zarafa/zarafa7-upgrade >/dev/null 2>&1 &');
            end;
      end;
      exit;
end;
logs.DebugLogs('Starting zarafa..............: Zarafa-server failed');


end;
//##############################################################################
function tzarafa_server.STATUS():string;
var
pidpath:string;
begin
SYS.MONIT_DELETE('APP_ZARAFA_WEB');
SYS.MONIT_DELETE('APP_ZARAFA_ICAL');
SYS.MONIT_DELETE('APP_ZARAFA_DAGENT');
SYS.MONIT_DELETE('APP_ZARAFA_MONITOR');
SYS.MONIT_DELETE('APP_ZARAFA_GATEWAY');
SYS.MONIT_DELETE('APP_ZARAFA_SPOOLER');
SYS.MONIT_DELETE('APP_ZARAFA_SERVER');
if not FileExists(SERVER_BIN_PATH()) then  exit;

 pidpath:=logs.FILE_TEMP();
 fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --zarafa >'+pidpath +' 2>&1');
 result:=logs.ReadFromFile(pidpath);
 logs.DeleteFile(pidpath);
end;
//##############################################################################
procedure tzarafa_server.SPOOLER_START();
var
   zbin:string;
   cmd:string;
   pid:string;
   count:integer;
begin
     zbin:=SPOOLER_BIN_PATH();
    if not FileExists(zbin) then begin
       exit;
    end;

pid:=SPOOLER_GET_PID();

if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-spooler already running PID '+ pid);
      exit;
end;


logs.DebugLogs('Starting zarafa..............: Zarafa-spooler config "/etc/zarafa/spooler.cfg"');
cmd:=zbin+' -c /etc/zarafa/spooler.cfg';
fpsystem(cmd);

pid:=SPOOLER_GET_PID();
count:=0;
 while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting zarafa..............: Zarafa-spooler (timeout)');
           break;
        end;
        pid:=SPOOLER_GET_PID();
  end;
pid:=SPOOLER_GET_PID();
if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-spooler success running PID '+ pid);
      exit;
end;
logs.DebugLogs('Starting zarafa..............: Zarafa-spooler failed "'+cmd+'"');


end;
//##############################################################################
procedure tzarafa_server.GATEWAY_START();
begin
if not FileExists('/etc/init.d/zarafa-gateway') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
if not FileExists('/etc/init.d/zarafa-gateway') then begin
   writeln('FATAL !!!! /etc/init.d/zarafa-gateway no such file!!!');
   exit;
end;
fpsystem('/etc/init.d/zarafa-gateway start');

end;
//##############################################################################
procedure tzarafa_server.SEARCH_START();
begin
if not FileExists('/etc/init.d/zarafa-search') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
if not FileExists('/etc/init.d/zarafa-search') then exit;
fpsystem('/etc/init.d/zarafa-search start');

end;
//##############################################################################
procedure tzarafa_server.SEARCH_STOP();
begin
if not FileExists('/etc/init.d/zarafa-search') then exit;
fpsystem('/etc/init.d/zarafa-search stop');
end;
//##############################################################################
procedure tzarafa_server.INDEXER_START();
var
   zbin:string;
   cmd:string;
   pid:string;
   count,EnableZarafaIndexer:integer;
begin
     zbin:=INDEXER_BIN_PATH();
    if not FileExists(zbin) then begin
       exit;
    end;
    if not TryStrToInt(SYS.GET_INFO('EnableZarafaIndexer'),EnableZarafaIndexer) then EnableZarafaIndexer:=0;

pid:=INDEXER_GET_PID();


if sys.PROCESS_EXIST(pid) then begin
      if EnableZarafaIndexer=0 then begin
           logs.DebugLogs('Starting zarafa..............: Zarafa-indexer is disabled, stop it');
           INDEXER_STOP();
           exit;
      end;
      logs.DebugLogs('Starting zarafa..............: Zarafa-indexer already running PID '+ pid);
      exit;
end;

if EnableZarafaIndexer=0 then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-indexer is disabled');
      exit;
end;



logs.DebugLogs('Starting zarafa..............: Zarafa-indexer config "/etc/zarafa/indexer.cfg"');
cmd:=zbin+' -c /etc/zarafa/indexer.cfg';
fpsystem(cmd);

pid:=INDEXER_GET_PID();
count:=0;
 while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting zarafa..............: Zarafa-indexer (timeout)');
           break;
        end;
        pid:=INDEXER_GET_PID();
  end;
pid:=INDEXER_GET_PID();
if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-indexer success running PID '+ pid);
      exit;
end;
logs.DebugLogs('Starting zarafa..............: Zarafa-indexer failed "'+cmd+'"');


end;
//##############################################################################
procedure tzarafa_server.INDEXER_STOP();
var
   zbin:string;
   pid:string;
   count:integer;
begin
     zbin:=INDEXER_BIN_PATH();
    if not FileExists(zbin) then begin
       writeln('Stopping Zarafa-indexer......: Not installed');
       exit;
    end;
   pid:=INDEXER_GET_PID();

   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa-indexer......: Already stopped');
       exit;
   end;
   writeln('Stopping Zarafa-indexer......: PID '+pid);
   fpsystem('/bin/kill '+ pid);
  while sys.PROCESS_EXIST(pid) do begin
      sleep(100);
      fpsystem('/bin/kill '+ pid);
      inc(count);
      if count>50 then begin
       writeln('Stopping Zarafa-indexer......: time-out');
         logs.OutputCmd('/bin/kill -9 ' + pid);
         break;
      end;
      pid:=INDEXER_GET_PID();
  end;
pid:=INDEXER_GET_PID();
   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa-indexer......: stopped');
       exit;
   end;
       writeln('Stopping Zarafa-indexer......: failed');
end;
//##############################################################################



procedure tzarafa_server.LICENSED_START();
var
   zbin:string;
   cmd:string;
   pid:string;
   count:integer;
begin
     zbin:=LICENSED_BIN_PATH();
    if not FileExists(zbin) then begin
       exit;
    end;

    if not FileExists('/etc/zarafa/license/base') then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-licensed license, no such file');
      exit;
    end;
pid:=LICENSED_GET_PID();

if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-licensed already running PID '+ pid);
      exit;
end;


logs.DebugLogs('Starting zarafa..............: Zarafa-licensed config "/etc/zarafa/licensed.cfg"');
cmd:=zbin+' -c /etc/zarafa/licensed.cfg';
fpsystem(cmd);

pid:=LICENSED_GET_PID();
count:=0;
 while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting zarafa..............: Zarafa-licensed (timeout)');
           break;
        end;
        pid:=LICENSED_GET_PID();
  end;
pid:=LICENSED_GET_PID();
if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa-licensed success running PID '+ pid);
      exit;
end;
logs.DebugLogs('Starting zarafa..............: Zarafa-licensed failed "'+cmd+'"');


end;
//##############################################################################
procedure tzarafa_server.LICENSED_STOP();
var
   zbin:string;
   pid:string;
   count:integer;
begin
     zbin:=LICENSED_BIN_PATH();
    if not FileExists(zbin) then begin
       writeln('Stopping Zarafa License......: Not installed');
       exit;
    end;
   pid:=LICENSED_GET_PID();

   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa License......: Already stopped');
       exit;
   end;
       writeln('Stopping Zarafa License......: PID '+pid);
   fpsystem('/bin/kill '+ pid);
  while sys.PROCESS_EXIST(pid) do begin
      sleep(100);
      fpsystem('/bin/kill '+ pid);
      inc(count);
      if count>50 then begin
       writeln('Stopping Zarafa License......: Time-out');
         logs.OutputCmd('/bin/kill -9 ' + pid);
         break;
      end;
      pid:=LICENSED_GET_PID();
  end;
pid:=LICENSED_GET_PID();
   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa License......: stopped');
       exit;
   end;
       writeln('Stopping Zarafa License......: failed');
end;
//##############################################################################
procedure tzarafa_server.MONITOR_START();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
fpsystem('/etc/init.d/zarafa-monitor start');

end;
//##############################################################################
procedure tzarafa_server.DAGENT_START();
begin
     fpsystem('/etc/init.d/zarafa-dagent start');

end;
//##############################################################################

procedure tzarafa_server.ICAL_START();
var
   zbin:string;
   cmd:string;
   pid:string;
   count:integer;
   ZarafaiCalEnable:integer;
begin
    zbin:=ICAL_BIN_PATH();
    if not FileExists(zbin) then exit;
    pid:=ICAL_GET_PID();
    if not TryStrToINt(SYS.GET_INFO('ZarafaiCalEnable'),ZarafaiCalEnable) then ZarafaiCalEnable:=0;





if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway already running PID '+ pid);
      if ZarafaiCalEnable=0 then ICAL_STOP();
      exit;
end;

if ZarafaiCalEnable=0 then begin
   logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway is disabled by Artica');
   exit;
end;
ICAL_CONFIG();
logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway config "/etc/zarafa/zarafa-ical.cfg"');
cmd:=zbin+' -c /etc/zarafa/ical.cfg';
fpsystem(cmd);

pid:=ICAL_GET_PID();
count:=0;
 while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway (timeout)');
           break;
        end;
        pid:=ICAL_GET_PID();
  end;
pid:=ICAL_GET_PID();
if sys.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway success running PID '+ pid);
      exit;
end;
logs.DebugLogs('Starting zarafa..............: Zarafa iCal/CalDAV gateway failed "'+cmd+'"');
end;
//##############################################################################
procedure tzarafa_server.ICAL_STOP();
var
   zbin:string;
   pid:string;
   count:integer;
begin
     zbin:=ICAL_BIN_PATH();
    if not FileExists(zbin) then begin
       writeln('Stopping Zarafa iCal/CalDAV..: Not installed');
       exit;
    end;
   pid:=ICAL_GET_PID();

   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa iCal/CalDAV..: Already stopped');
       exit;
   end;
       writeln('Stopping Zarafa iCal/CalDAV..: PID '+pid);
   fpsystem('/bin/kill '+ pid);
  while sys.PROCESS_EXIST(pid) do begin
      sleep(100);
      fpsystem('/bin/kill '+ pid);
      inc(count);
      if count>50 then begin
       writeln('Stopping Zarafa iCal/CalDAV..: Time-out');
         logs.OutputCmd('/bin/kill -9 ' + pid);
         break;
      end;
      pid:=ICAL_GET_PID();
  end;
pid:=ICAL_GET_PID();
   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa iCal/CalDAV..: stopped');
       exit;
   end;
       writeln('Zarafa iCal/CalDAV gateway...: failed');
end;
//##############################################################################

procedure tzarafa_server.DAGENT_STOP();
begin
     fpsystem('/etc/init.d/zarafa-dagent stop');
end;
//##############################################################################



procedure tzarafa_server.SERVER_STOP();
var
   zbin:string;
   pid:string;
   count,i,t:integer;
   pids:tstringlist;
begin
     zbin:=SERVER_BIN_PATH();
    if not FileExists(zbin) then begin
       writeln('Stopping Zarafa-server.......: Not installed');
       exit;
    end;
    fpsystem('/etc/init.d/zarafa-server stop');
end;
//##############################################################################
procedure tzarafa_server.GATEWAY_STOP();
begin
if not FileExists('/etc/init.d/zarafa-gateway') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
fpsystem('/etc/init.d/zarafa-gateway stop');
end;
//##############################################################################
procedure tzarafa_server.SPOOLER_STOP();
var
   zbin:string;
   pid:string;
   count:integer;
begin
   pid:=SPOOLER_GET_PID();

   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa-spooler......: Already stopped');
       exit;
   end;
   writeln('Stopping Zarafa-spooler......: PID '+pid);
   fpsystem('/bin/kill '+ pid);
  while sys.PROCESS_EXIST(pid) do begin
      sleep(100);
      fpsystem('/bin/kill '+ pid);
      inc(count);
      if count>50 then begin
         writeln('Stopping Zarafa-spooler......: time-out');
         logs.OutputCmd('/bin/kill -9 ' + pid);
         break;
      end;
      pid:=SPOOLER_GET_PID();
  end;
pid:=SPOOLER_GET_PID();
   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping Zarafa-spooler......: stopped');
       exit;
   end;
    writeln('Stopping Zarafa-spooler......: failed');
end;
//##############################################################################
procedure tzarafa_server.MONITOR_STOP();
begin
if not FileExists('/etc/init.d/zarafa-monitor') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initdzarafa.php');
fpsystem('/etc/init.d/zarafa-monitor stop');
end;
//##############################################################################
procedure tzarafa_server.REMOVE();
var
   l:Tstringlist;
   i:integer;
   path:string;
begin
l:=Tstringlist.Create;
STOP();
l.add('/usr/local/lib/libical.a');
l.add('/usr/local/lib/libical.la');
l.add('/usr/local/lib/libicalmapi.la');
l.add('/usr/local/lib/libicalmapi.so');
l.add('/usr/local/lib/libicalmapi.so.1');
l.add('/usr/local/lib/libicalmapi.so.1.0.0');
l.add('/usr/local/lib/libical.so');
l.add('/usr/local/lib/libical.so.0');
l.add('/usr/local/lib/libical.so.0.44.0');
l.add('/usr/local/lib/libicalss.a');
l.add('/usr/local/lib/libicalss.la');
l.add('/usr/local/lib/libicalss.so');
l.add('/usr/local/lib/libicalss.so.0');
l.add('/usr/local/lib/libicalss.so.0.44.0');
l.add('/usr/local/lib/libicalvcal.a');
l.add('/usr/local/lib/libicalvcal.la');
l.add('/usr/local/lib/libicalvcal.so');
l.add('/usr/local/lib/libicalvcal.so.0');
l.add('/usr/local/lib/libicalvcal.so.0.44.0');
l.add('/usr/local/lib/libinetmapi.la');
l.add('/usr/local/lib/libinetmapi.so');
l.add('/usr/local/lib/libinetmapi.so.1');
l.add('/usr/local/lib/libinetmapi.so.1.0.0');
l.add('/usr/local/lib/libmapi.la');
l.add('/usr/local/lib/libmapi.so');
l.add('/usr/local/lib/libmapi.so.0');
l.add('/usr/local/lib/libmapi.so.0.0.0');
l.add('/usr/local/lib/libvmime.a');
l.add('/usr/local/lib/libvmime.la');
l.add('/usr/local/lib/libvmime.so');
l.add('/usr/local/lib/libvmime.so.0');
l.add('/usr/local/lib/libvmime.so.0.7.1');
l.add('/usr/local/lib/libzarafaclient.la');
l.add('/usr/local/lib/libzarafaclient.so');
l.add('/usr/local/bin/zarafa-admin');
l.add('/usr/local/bin/zarafa-autorespond');
l.add('/usr/local/bin/zarafa-dagent');
l.add('/usr/local/bin/zarafa-fsck');
l.add('/usr/local/bin/zarafa-gateway');
l.add('/usr/local/bin/zarafa-ical');
l.add('/usr/local/bin/zarafa-monitor');
l.add('/usr/local/bin/zarafa-passwd');
l.add('/usr/local/bin/zarafa-server');
l.add('/usr/local/bin/zarafa-spooler');
l.add('/usr/local/bin/zarafa-stats');




if DirectoryExists('/usr/local/lib/zarafa') then begin
   writeln('Remove directory /usr/local/lib/zarafa');
   fpsystem('/bin/rm -rf /usr/local/lib/zarafa');
end;


for i:=0 TO l.Count-1 do begin
    if FileExists(l.Strings[i]) then begin
       writeln('Remove file '+l.Strings[i]);
       fpsystem('/bin/rm '+ l.Strings[i]);
    end else begin
       writeln('file '+l.Strings[i]+' Already removed');
    end;
end;

l.free;
l:=Tstringlist.Create;
l.add('zarafa-admin');
L.add('zarafa-cfgchecker');
L.add('zarafa-dagent');
L.add('zarafa-fsck');
L.add('zarafa-gateway');
L.add('zarafa-ical');
L.add('zarafa-indexer');
L.add('zarafa-monitor');
L.add('zarafa-passwd');
L.add('zarafa-server');
L.add('zarafa-spooler');
L.add('zarafa-stats');

for i:=0 TO l.Count-1 do begin
    path:=SYS.LOCATE_GENERIC_BIN(l.Strings[i]);
    if FileExists(path) then begin
       writeln('Remove file '+path);
       fpsystem('/bin/rm '+ path);
    end else begin
       writeln('file '+l.Strings[i]+' Already removed');
    end;
end;

l.free;
fpsystem('/usr/share/artica-postfix/bin/process1 --force');
fpsystem('/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus --without-zarafa');
fpsystem('/etc/init.d/artica-postfix restart postfix');
fpsystem('/etc/init.d/artica-postfix restart apache');

writeln('done.');
end;

//##############################################################################
procedure tzarafa_server.WEB_ACCESS_CONFIG();
begin
     fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.zarafa.build.stores.php --config');
end;
//##############################################################################
procedure tzarafa_server.VERIFY_MAPI_SO_PATH();
var
   standard_path:string;
   ext_path:string;
   source_ext_dir:string;
   next_path:string;
   i:integer;
begin
   standard_path:=SYS.LOCATE_MAPI_SO();
   if FileExists(standard_path) then begin
        logs.DebugLogs('Starting zarafa..............: Apache found mapi:'+standard_path);
        exit;
   end;


        logs.DebugLogs('Starting zarafa..............: Apache unable to stat mapi.so');
        source_ext_dir:=SYS.LOCATE_PHP5_EXTENSION_DIR();
        ext_path:=ExtractFilePath(source_ext_dir);
        if Copy(ext_path,length(ext_path),1)='/' then ext_path:=Copy(ext_path,1,length(ext_path)-1);
        logs.DebugLogs('Starting zarafa..............: Apache Search location directory in '+ext_path);
        SYS.DirDir(ext_path);
        for i:=0 to SYS.DirListFiles.Count-1 do begin
             next_path:=ext_path+'/'+SYS.DirListFiles.Strings[i]+'/mapi.so';
             if FileExists(next_path) then begin
                  logs.DebugLogs('Starting zarafa..............: Apache found '+next_path+' link it to right path');
                  logs.DebugLogs('Starting zarafa..............: linking '+next_path+' -> '+source_ext_dir+'/mapi.so');
                  fpsystem('/bin/cp '+next_path+' '+source_ext_dir+'/mapi.so');
                  fpsystem('/bin/cp '+next_path+'/'+SYS.DirListFiles.Strings[i]+'/mapi.la '+source_ext_dir+'/mapi.la');
                  break;
             end;
        end;

end;
//##############################################################################


procedure tzarafa_server.APACHE_CONFIG();
var
   apache:tapache_artica;
   apache_bin_path:string;
   modules:string;
   l:TStringlist;
   ZarafaApachePort,ZarafaApacheSSL,LighttpdArticaDisableSSLv2,ZarafaWebNTLM:integer;
   LighttpdUserAndGroup,username,group,ZarafaApacheServerName:string;
   RegExpr:TRegExpr;
begin
apache:=tapache_artica.Create(SYS);
modules:=apache.SET_MODULES();
LighttpdUserAndGroup:=SYS.LIGHTTPD_GET_USER();
ZarafaApacheSSL:=0;
LighttpdArticaDisableSSLv2:=0;
ZarafaWebNTLM:=0;


if not TryStrToInt(SYS.GET_INFO('ZarafaApachePort'),ZarafaApachePort) then ZarafaApachePort:=9010;
if not TryStrToInt(SYS.GET_INFO('ZarafaApacheSSL'),ZarafaApacheSSL) then ZarafaApacheSSL:=0;
if not TryStrToInt(SYS.GET_INFO('LighttpdArticaDisableSSLv2'),LighttpdArticaDisableSSLv2) then LighttpdArticaDisableSSLv2:=0;
if not TryStrToInt(SYS.GET_INFO('ZarafaWebNTLM'),ZarafaWebNTLM) then ZarafaWebNTLM:=0;

ZarafaApacheServerName:=SYS.GET_INFO('ZarafaApacheServerName');
if length(trim(ZarafaApacheServerName))=0 then ZarafaApacheServerName:=SYS.HOSTNAME_g();
logs.DebugLogs('Starting zarafa..............: Server name: '+ZarafaApacheServerName);
logs.DebugLogs('Starting zarafa..............: Port: '+INtToStr(ZarafaApachePort));
logs.DebugLogs('Starting zarafa..............: username:group "'+LighttpdUserAndGroup+'"');
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^(.+?):(.+)';
if not RegExpr.Exec(LighttpdUserAndGroup) then begin
 logs.DebugLogs('Starting zarafa..............: Apache daemon unable to stat username and group !');
 exit;
end;

if not DirectoryExists('/usr/share/php/mapi') then begin
   if DirectoryExists('/usr/local/share/php/mapi') then begin
       logs.DebugLogs('Starting zarafa..............: Apache Create a symbolic link from /usr/local/share/php/mapi');
       ForceDirectories('/usr/share/php');
      fpsystem('/bin/ln -s /usr/local/share/php/mapi /usr/share/php/mapi');
   end;
end;

fpsystem('/bin/cp /usr/share/artica-postfix/img/zarafa-login.jpg /usr/share/zarafa-webaccess/client/layout/img/login.jpg');

username:=RegExpr.Match[1];
group:=RegExpr.Match[2];
if length(LighttpdUserAndGroup)=0 then LighttpdUserAndGroup:='www-data:www-data';

forceDirectories('/var/run/zarafa-web');
ForceDirectories('/var/log/apache-zarafa');
ForceDirectories('/var/lib/zarafa-webaccess/tmp');
fpsystem('/bin/chown '+LighttpdUserAndGroup+' /var/run/zarafa-web');
fpsystem('/bin/chown '+LighttpdUserAndGroup+' /var/log/apache-zarafa');
fpsystem('/bin/chown '+LighttpdUserAndGroup+' /var/lib/zarafa-webaccess');
fpsystem('/bin/chmod 777 /var/lib/zarafa-webaccess/tmp');
fpsystem('/bin/chown -R '+LighttpdUserAndGroup+' /usr/share/zarafa-webaccess/plugins/');
l:=Tstringlist.Create;
l.add('ServerRoot "/usr/share/zarafa-webaccess"');
l.add('Listen '+INtToStr(ZarafaApachePort));
l.add('User '+username);
l.add('Group '+group);
l.add('PidFile /var/run/zarafa-web/httpd.pid');
l.add(modules);
if ZarafaApacheSSL=1 then begin
 logs.DebugLogs('Starting zarafa..............: Apache daemon SSL enabled');
 l.add('SSLEngine on');
 l.add('SSLCertificateFile /etc/ssl/certs/zarafa/apache.crt.nopass.cert');
 l.add('SSLCertificateKeyFile /etc/ssl/certs/zarafa/apache-ca.key.nopass.key');
 if LighttpdArticaDisableSSLv2=1 then begin
     logs.DebugLogs('Starting zarafa..............: Apache daemon SSLv2 is disabled');
     l.add('SSLProtocol -ALL +SSLv3 +TLSv1');
     l.add('SSLCipherSuite ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM');
 end;

end else begin
 logs.DebugLogs('Starting zarafa..............: Apache daemon SSL disabled');
end;
l.add('<IfModule !mpm_netware_module>');
l.add('          <IfModule !mpm_winnt_module>');
l.add('             User '+username);
l.add('             Group '+username);
l.add('          </IfModule>');
l.add('</IfModule>');
l.add('ServerAdmin you@example.com');
l.add('ServerName '+ ZarafaApacheServerName);
l.add('DocumentRoot "/usr/share/zarafa-webaccess"');
l.add('<Directory /usr/share/zarafa-webaccess/>');
if ZarafaWebNTLM=1 then begin
l.add('    AuthName "Zarafa logon.."');
l.add('    AuthType Basic');
l.add('    AuthLDAPURL ldap://'+ldap.ldap_settings.servername+':'+ldap.ldap_settings.Port+'/dc=organizations,'+ldap.ldap_settings.suffix+'?uid');
l.add('    AuthLDAPBindDN cn='+ldap.ldap_settings.admin+','+ldap.ldap_settings.suffix);
l.add('    AuthLDAPBindPassword '+ldap.ldap_settings.password);
l.add('    AuthLDAPGroupAttribute memberUid');
l.add('    AuthBasicProvider ldap');
l.add('    AuthzLDAPAuthoritative off');
l.add('    require valid-user');
end;
l.add('    php_value magic_quotes_gpc off');
l.add('    php_flag register_globals off');
l.add('    php_flag magic_quotes_gpc off');
l.add('    php_flag magic_quotes_runtime off');
l.add('    php_value post_max_size 31M');
l.add('    php_value include_path  ".:/usr/share/php:/usr/share/php5:/usr/local/share/php"');
l.add('    php_value upload_max_filesize 30M');
l.add('    php_flag short_open_tag on');
l.add('    php_flag log_errors on');
l.add('    php_value  error_log  "/var/log/apache-zarafa/php.log"');
l.add('    DirectoryIndex index.php');
l.add('    Options -Indexes +FollowSymLinks');
l.add('    AllowOverride Options');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('</Directory>');
l.add('<IfModule dir_module>');
l.add('    DirectoryIndex index.php');
l.add('</IfModule>');
l.add('');
l.add('');
l.add('<FilesMatch "^\.ht">');
l.add('    Order allow,deny');
l.add('    Deny from all');
l.add('    Satisfy All');
l.add('</FilesMatch>');
l.add('');
l.add('');
l.add('ErrorLog "/var/log/apache-zarafa/error.log"');
l.add('LogLevel warn');
l.add('');
l.add('<IfModule log_config_module>');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %V" combinedv');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b" common');
l.add('');
l.add('    <IfModule logio_module>');
l.add('      LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %I %O" combinedio');
l.add('    </IfModule>');
l.add('');
l.add('    CustomLog "/var/log/apache-zarafa/access.log" combined');
l.add('    #CustomLog "logs/access_log" combined');
l.add('</IfModule>');
l.add('');
l.add('<IfModule alias_module>');
l.add('    ScriptAlias /cgi-bin/ "/usr/local/apache-groupware/data/cgi-bin/"');
l.add('    Alias /images /usr/share/obm2/resources');
l.add('');
l.add('</IfModule>');
l.add('');
l.add('<IfModule cgid_module>');
l.add('');
l.add('</IfModule>');
l.add('');
l.add('');
l.add('<Directory "/usr/local/apache-groupware/data/cgi-bin">');
l.add('    AllowOverride None');
l.add('    Options None');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('</Directory>');
l.add('');
l.add('');
l.add('DefaultType text/plain');
l.add('');
l.add('<IfModule mime_module>');
l.add('   ');
l.add('    TypesConfig /etc/mime.types');
l.add('    #AddType application/x-gzip .tgz');
l.add('    AddType application/x-compress .Z');
l.add('    AddType application/x-gzip .gz .tgz');
l.add('    AddType application/x-httpd-php .php .phtml');
l.add('    #AddHandler cgi-script .cgi');
l.add('    #AddHandler type-map var');
l.add('    #AddType text/html .shtml');
l.add('    #AddOutputFilter INCLUDES .shtml');
l.add('</IfModule>');
l.add('');
l.add('<IfModule ssl_module>');
l.add('SSLRandomSeed startup builtin');
l.add('SSLRandomSeed connect builtin');
l.add('</IfModule>');
logs.WriteToFile(l.Text,'/etc/zarafa/httpd.conf');
l.free;
end;
//##############################################################################
function tzarafa_server.LIGHTTPD_PID():string;
begin
   result:=SYS.GET_PID_FROM_PATH('/var/run/zarafa-web/httpd.pid');
   if length(result)>0 then exit;
   result:=SYS.PIDOF_PATTERN(SYS.LOCATE_GENERIC_BIN('lighttpd') + ' -f /etc/zarafa/lighttpd.conf');


end;
//##############################################################################
procedure tzarafa_server.LIGHTTPD_START();
var
  pid:string;
begin


     pid:=LIGHTTPD_PID();


   if SYS.PROCESS_EXIST(pid) then begin
      logs.Debuglogs('Starting zarafa..............: lighttpd daemon is already running using PID ' + pid + '...');
      exit();
   end;

    lighttpd_config();
    logs.OutputCmd(SYS.LOCATE_GENERIC_BIN('lighttpd')+ ' -f /etc/zarafa/lighttpd.conf');


   if not SYS.PROCESS_EXIST(LIGHTTPD_PID()) then begin
      logs.Debuglogs('Starting zarafa..............: lighttpd Failed "' + SYS.LOCATE_GENERIC_BIN('lighttpd')+ ' -f /etc/zarafa/lighttpd.conf"');
    end else begin
      logs.Debuglogs('Starting zarafa..............: lighttpd Success (PID ' + LIGHTTPD_PID() + ')');
   end;

end;
//##############################################################################

procedure tzarafa_server.lighttpd_config();
var
   apache:tapache_artica;
   apache_bin_path:string;
   modules:string;
   l:TStringlist;
   ZarafaApachePort,ZarafaApacheSSL:integer;
   LighttpdUserAndGroup,username,group,ZarafaApacheServerName:string;
   RegExpr:TRegExpr;

begin
apache:=tapache_artica.Create(SYS);
modules:=apache.SET_MODULES();
LighttpdUserAndGroup:=SYS.LIGHTTPD_GET_USER();
ZarafaApacheSSL:=0;

SYS.set_INFO('php5DisableMagicQuotesGpc','1');


if not TryStrToInt(SYS.GET_INFO('ZarafaApachePort'),ZarafaApachePort) then ZarafaApachePort:=9010;
if not TryStrToInt(SYS.GET_INFO('ZarafaApacheSSL'),ZarafaApacheSSL) then ZarafaApacheSSL:=0;

ZarafaApacheServerName:=SYS.GET_INFO('ZarafaApacheServerName');
if length(ZarafaApacheServerName)=0 then ZarafaApacheServerName:=SYS.HOSTNAME_g();

logs.DebugLogs('Starting zarafa..............: Port: '+INtToStr(ZarafaApachePort));
logs.DebugLogs('Starting zarafa..............: username:group "'+LighttpdUserAndGroup+'"');
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^(.+?):(.+)';
if not RegExpr.Exec(LighttpdUserAndGroup) then begin
 logs.DebugLogs('Starting zarafa..............: Apache daemon unable to stat username and group !');
 exit;
end;

if not DirectoryExists('/usr/share/php/mapi') then begin
   if DirectoryExists('/usr/local/share/php/mapi') then begin
       logs.DebugLogs('Starting zarafa..............: Apache Create a symbolic link from /usr/local/share/php/mapi');
      fpsystem('/bin/ln -s /usr/local/share/php/mapi /usr/share/php/mapi');
   end;
end;

fpsystem('/bin/cp /usr/share/artica-postfix/img/zarafa-login.jpg /usr/share/zarafa-webaccess/client/layout/img/login.jpg');

username:=RegExpr.Match[1];
group:=RegExpr.Match[2];
if length(LighttpdUserAndGroup)=0 then LighttpdUserAndGroup:='www-data:www-data';

forceDirectories('/var/run/zarafa-web');
ForceDirectories('/var/log/apache-zarafa');
fpsystem('/bin/chown '+LighttpdUserAndGroup+' /var/run/zarafa-web');
fpsystem('/bin/chown '+LighttpdUserAndGroup+' /var/log/apache-zarafa');
l:=Tstringlist.Create;



l.add('#artica-postfix saved by artica lighttpd.conf');
l.add('');
l.add('server.modules = (');
l.add('        "mod_alias",');
l.add('        "mod_access",');
l.add('        "mod_accesslog",');
l.add('        "mod_compress",');
l.add('        "mod_fastcgi",');
l.add('        "mod_cgi",');
l.add('	       "mod_status",');
l.add('	       "mod_auth"');
l.add(')');
l.add('');
l.add('server.document-root        = "/usr/share/zarafa-webaccess"');
l.add('server.username = "'+username+'"');
l.add('server.groupname = "'+group+'"');
l.add('server.errorlog             = "/var/log/lighttpd/zarafa-webaccess-error.log"');
l.add('index-file.names            = ( "index.php")');
l.add('');
l.add('mimetype.assign             = (');
l.add('  ".pdf"          =>      "application/pdf",');
l.add('  ".sig"          =>      "application/pgp-signature",');
l.add('  ".spl"          =>      "application/futuresplash",');
l.add('  ".class"        =>      "application/octet-stream",');
l.add('  ".ps"           =>      "application/postscript",');
l.add('  ".torrent"      =>      "application/x-bittorrent",');
l.add('  ".dvi"          =>      "application/x-dvi",');
l.add('  ".gz"           =>      "application/x-gzip",');
l.add('  ".pac"          =>      "application/x-ns-proxy-autoconfig",');
l.add('  ".swf"          =>      "application/x-shockwave-flash",');
l.add('  ".tar.gz"       =>      "application/x-tgz",');
l.add('  ".tgz"          =>      "application/x-tgz",');
l.add('  ".tar"          =>      "application/x-tar",');
l.add('  ".zip"          =>      "application/zip",');
l.add('  ".mp3"          =>      "audio/mpeg",');
l.add('  ".m3u"          =>      "audio/x-mpegurl",');
l.add('  ".wma"          =>      "audio/x-ms-wma",');
l.add('  ".wax"          =>      "audio/x-ms-wax",');
l.add('  ".ogg"          =>      "application/ogg",');
l.add('  ".wav"          =>      "audio/x-wav",');
l.add('  ".gif"          =>      "image/gif",');
l.add('  ".jar"          =>      "application/x-java-archive",');
l.add('  ".jpg"          =>      "image/jpeg",');
l.add('  ".jpeg"         =>      "image/jpeg",');
l.add('  ".png"          =>      "image/png",');
l.add('  ".xbm"          =>      "image/x-xbitmap",');
l.add('  ".xpm"          =>      "image/x-xpixmap",');
l.add('  ".xwd"          =>      "image/x-xwindowdump",');
l.add('  ".css"          =>      "text/css",');
l.add('  ".html"         =>      "text/html",');
l.add('  ".htm"          =>      "text/html",');
l.add('  ".js"           =>      "text/javascript",');
l.add('  ".asc"          =>      "text/plain",');
l.add('  ".c"            =>      "text/plain",');
l.add('  ".cpp"          =>      "text/plain",');
l.add('  ".log"          =>      "text/plain",');
l.add('  ".conf"         =>      "text/plain",');
l.add('  ".text"         =>      "text/plain",');
l.add('  ".txt"          =>      "text/plain",');
l.add('  ".dtd"          =>      "text/xml",');
l.add('  ".xml"          =>      "text/xml",');
l.add('  ".mpeg"         =>      "video/mpeg",');
l.add('  ".mpg"          =>      "video/mpeg",');
l.add('  ".mov"          =>      "video/quicktime",');
l.add('  ".qt"           =>      "video/quicktime",');
l.add('  ".avi"          =>      "video/x-msvideo",');
l.add('  ".asf"          =>      "video/x-ms-asf",');
l.add('  ".asx"          =>      "video/x-ms-asf",');
l.add('  ".wmv"          =>      "video/x-ms-wmv",');
l.add('  ".bz2"          =>      "application/x-bzip",');
l.add('  ".tbz"          =>      "application/x-bzip-compressed-tar",');
l.add('  ".tar.bz2"      =>      "application/x-bzip-compressed-tar",');
l.add('  ""              =>      "application/octet-stream",');
l.add(' )');
l.add('');
l.add('');
l.add('accesslog.filename          = "/var/log/lighttpd/zarafa-webaccess-access.log"');
l.add('url.access-deny             = ( "~", ".inc" )');
l.add('');
l.add('static-file.exclude-extensions = ( ".php", ".pl", ".fcgi" )');
l.add('server.port                 = '+IntTOStr(ZarafaApachePort));
l.add('#server.bind                = "127.0.0.1"');
l.add('#server.error-handler-404   = "/error-handler.html"');
l.add('#server.error-handler-404   = "/error-handler.php"');
l.add('server.pid-file             = "/var/run/zarafa-web/httpd.pid"');
l.add('server.max-fds 		    = 2048');
l.add('');
l.add('fastcgi.server = ( ".php" =>((');
l.add('                "bin-path" => "/usr/bin/php-cgi",');
l.add('                "socket" => "/var/run/lighttpd/php.socket",');
l.add('		       "min-procs" => 1,');
l.add('                "max-procs" => 2,');
l.add('	               	"max-load-per-proc" => 2,');
l.add('                "idle-timeout" => 10,');
l.add('                "bin-environment" => (');
l.add('                        "PHP_FCGI_CHILDREN" => "4",');
l.add('                        "PHP_FCGI_MAX_REQUESTS" => "100"');
l.add('                ),');
l.add('                "bin-copy-environment" => (');
l.add('                        "PATH", "SHELL", "USER"');
l.add('                ),');
l.add('                "broken-scriptfilename" => "enable"');
l.add('        ))');
l.add(')');
l.add('ssl.engine                 = "enable"');
l.add('ssl.pemfile                = "/opt/artica/ssl/certs/lighttpd.pem"');
l.add('status.status-url          = "/server-status"');
l.add('status.config-url          = "/server-config"');
l.add('alias.url += (	"/webmail" 			 => "/usr/share/roundcube")');
l.add('$HTTP["url"] =~ "^/webmail" {');
l.add('	server.follow-symlink = "enable"');
l.add('}');
l.add('$HTTP["url"] =~ "^/webmail/config|/webmail/temp|/webmail/logs" { url.access-deny = ( "" )}');
l.add('alias.url +=("/monitorix"  => "/var/www/monitorix/")');
l.add('alias.url += ("/blocked_attachments"=> "/var/spool/artica-filter/bightml")');
l.add('alias.url += ("/awstats"=> "/usr/share/awstats")');
l.add('alias.url += ("/pipermail/" => "/var/lib/mailman/archives/public/")');
l.add('alias.url += ( "/cgi-bin/" => "/usr/lib/cgi-bin/" )');
l.add('');
l.add('cgi.assign= (');
l.add('	".pl"  => "/usr/bin/perl",');
l.add('	".php" => "/usr/bin/php-cgi",');
l.add('	".py"  => "/usr/bin/python",');
l.add('	".cgi"  => "/usr/bin/perl",');
l.add('	"/admin" => "",');
l.add('	"/admindb" => "",');
l.add('	"/confirm" => "",');
l.add('	"/create" => "",');
l.add('	"/edithtml" => "",');
l.add('	"/listinfo" => "",');
l.add('	"/options" => "",');
l.add('	"/private" => "",');
l.add('	"/rmlist" => "",');
l.add('	"/roster" => "",');
l.add('	"/subscribe" => ""');
l.add(')');
logs.WriteToFile(l.Text,'/etc/zarafa/lighttpd.conf');
l.free;
end;

procedure tzarafa_server.APACHE_START();
var
   apache:tapache_artica;
   apache_bin_path:string;
   start_command:string;
   pid:string;
   count:integer;
begin
    pid:=ZARAFA_WEB_PID_NUM();
    if SYS.PROCESS_EXIST(pid) then begin
       if(ZarafaApacheEnable=0) then begin
           logs.DebugLogs('Starting zarafa..............: Apache daemon is disabled');
           APACHE_STOP();
           exit;
       end;
      logs.DebugLogs('Starting zarafa..............: Apache daemon. already running PID '+pid);
      exit;
    end;

   if(ZarafaApacheEnable=0) then begin
       logs.DebugLogs('Starting zarafa..............: Apache daemon is disabled');
       APACHE_STOP();
       exit;
   end;

   apache:=tapache_artica.Create(SYS);
   apache_bin_path:=apache.BIN_PATH();
   start_command:=apache_bin_path+' -f /etc/zarafa/httpd.conf';

   if not FileExists(apache_bin_path) then begin
        logs.DebugLogs('Starting zarafa..............: Apache daemon. unable to stat apache web server');
        exit;
   end;



     if not FileExists('/etc/ssl/certs/apache/server.key') then apache.APACHE_ARTICA_SSL_KEY();
     if not FileExists('/etc/ssl/certs/apache/server.crt') then apache.APACHE_ARTICA_SSL_KEY();

     APACHE_CERTIFICATES();
     VERIFY_MAPI_SO_PATH();
     APACHE_CONFIG();
     WEB_ACCESS_CONFIG();
     logs.DeleteFile('/var/log/apache-zarafa/error.log');
     logs.Debuglogs(start_command);
     fpsystem(start_command);

 count:=0;
 while not SYS.PROCESS_EXIST(ZARAFA_WEB_PID_NUM()) do begin
              sleep(150);
              inc(count);
              if count>50 then begin
                 logs.DebugLogs('Starting zarafa..............: Apache daemon. (timeout!!!)');
                 logs.DebugLogs('Starting zarafa..............: Apache daemon. "'+start_command+'" failed');
                 if APACHE_FOUND_ERROR() then begin
                    sleep(500);
                    APACHE_START();
                    exit;
                 end;
                 break;
              end;
        end;

 pid:=ZARAFA_WEB_PID_NUM();
 if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting zarafa..............: Apache daemon with new PID '+pid);
 end;

end;
//##############################################################################
function tzarafa_server.APACHE_FOUND_ERROR():boolean;
var
   RegExpr:TRegExpr;
   l:TstringList;
   i:Integer;
begin
  result:=false;
  logs.DebugLogs('Starting zarafa..............: Apache daemon. try to investigate');
  if not FIleExists('/var/log/apache-zarafa/error.log') then begin
     logs.DebugLogs('Starting zarafa..............: Apache daemon. unable to stat /var/log/apache-zarafa/error.log');
     exit;
  end;

  l:=Tstringlist.Create;
  RegExpr:=TRegExpr.Create;
  l.LoadFromFile('/var/log/apache-zarafa/error.log');
  for i:=0 to l.COunt-1 do begin
       RegExpr.Expression:='RSA server certificate CommonName\s+\(CN\)\s+`(.+?)''\s+does NOT match server name';
       if RegExpr.Exec(l.Strings[i]) then begin
           logs.DebugLogs('Starting zarafa..............: Apache daemon. Change the Apache server name to '+RegExpr.Match[1]);
           SYS.set_INFO('ZarafaApacheServerName',RegExpr.Match[1]);
           RegExpr.free;
           result:=true;
           l.free;
           exit;
       end;
       logs.DebugLogs('Starting zarafa..............: '+l.Strings[i]);
  end;
           RegExpr.free;
           l.free;



end;
//##############################################################################
function tzarafa_server.ZARAFA_WEB_PID_NUM():string;
var
   pid_path:string;

begin
     pid_path:='/var/run/zarafa-web/httpd.pid';
     if FileExists(pid_path) then begin
        result:=SYS.GET_PID_FROM_PATH(pid_path);
        if not SYS.PROCESS_EXIST(result) then begin
           result:=SYS.PIDOF_PATTERN('-f /etc/zarafa/httpd.conf');

           if length(result)>2 then begin
              logs.DebugLogs('Starting zarafa..............: Apache daemon fix pid with "'+result+'"');
              logs.WriteToFile(result,'/var/run/zarafa-web/httpd.pid');
           end;

        end;
     end else begin
         forcedirectories('/var/run/zarafa-web');
         fpsystem('/bin/chmod 777 /var/run/zarafa-web');
     end;
end;
//##############################################################################
procedure tzarafa_server.APACHE_STOP();
var
   count:integer;
   pid:string;
   apache:tapache_artica;
begin
    apache:=tapache_artica.Create(SYS);
    if not FileExists(apache.BIN_PATH()) then begin
    writeln('Stopping Apache Daemon.......: Not installed');
    exit;
    end;
    pid:=ZARAFA_WEB_PID_NUM();
if  not SYS.PROCESS_EXIST(pid) then begin
    writeln('Stopping Apache Daemon.......: Already stopped');
    exit;
end;

    writeln('Stopping Apache Daemon.......: ' + pid + ' PID..');
    fpsystem('/bin/kill '+ pid);
    pid:=ZARAFA_WEB_PID_NUM();
    if FileExists(SYS.LOCATE_APACHECTL()) then begin
       logs.OutputCmd(SYS.LOCATE_APACHECTL() +' -f /etc/zarafa/httpd.conf -k stop');
    end else begin
       writeln('Stopping Apache Daemon.......: failed to stat apachectl');
    end;

  while SYS.PROCESS_EXIST(pid) do begin
        sleep(200);
        count:=count+1;
        if count>20 then begin
            if length(pid)>0 then begin
               if SYS.PROCESS_EXIST(pid) then begin
                  writeln('Stopping Apache Daemon.......: kill pid '+ pid+' after timeout');
                  fpsystem('/bin/kill -9 ' + pid);
               end;
            end;
            break;
        end;
        pid:=ZARAFA_WEB_PID_NUM();
  end;

if  not SYS.PROCESS_EXIST(ZARAFA_WEB_PID_NUM()) then begin
    writeln('Stopping Apache Daemon.......: success');
    exit;
end;
    writeln('Stopping Apache Daemon.......: failed');
end;


//#############################################################################
procedure tzarafa_server.CHECK_CYRUS_CONFIG();
var
   cyrsconf:TstringList;
   i,DisableIMAPVerif:integer;
   RegExpr:TRegExpr;
begin

if not TryStrToInt(SYS.GET_INFO('DisableIMAPVerif'),DisableIMAPVerif) then DisableIMAPVerif:=0;
if DisableIMAPVerif=1 then exit;

if not FileExists('/etc/cyrus.conf') then exit;
cyrsconf:=Tstringlist.Create;
cyrsconf.LoadFromFile('/etc/cyrus.conf');
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='imap\s+cmd=.+?listen="imap';
for i:=0 to cyrsconf.Count-1 do begin
   if RegExpr.Exec(cyrsconf.Strings[i]) then begin
         logs.DebugLogs('Starting zarafa..............: Zarafa-gateway cyrus-imap is installed');
         logs.DebugLogs('Starting zarafa..............: Zarafa-gateway Change cyrus-imap configuration ports');
         fpsystem('/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus');
         break;
   end;
end;
cyrsconf.free;
RegExpr.free;
end;
//#############################################################################
procedure tzarafa_server.CERTIFICATES();
var
   CertificateIniFile:string;
   certini:Tinifile;
   tmpstr,cmd:string;
   smtpd_tls_key_file:string;
   smtpd_tls_cert_file:string;
   smtpd_tls_CAfile:string;
   openssl_path:string;
   POSFTIX_POSTCONF:string;
   l:TstringList;
   generate:boolean;
   i:integer;
   CertificateMaxDays:string;
   input_password,passfile,extensions:string;
   ldap:topenldap;

begin

  ldap:=topenldap.Create;
  if not FileExists(SERVER_BIN_PATH()) then begin
     logs.DebugLogs('Starting zarafa..............: Unable to stat zarafa-server path');
     exit;
  end;

  l:=TstringList.Create;
  l.Add('server.key');
  l.Add('ca.key');
  l.add('ca.csr');
  l.add('ca.crt');

  if SYS.COMMANDLINE_PARAMETERS('--zarafa-certificates') then begin
       for i:=0 to l.Count-1 do begin
           logs.DeleteFile('/etc/ssl/certs/zarafa/' +l.Strings[i]);
       end;
  end;


  forceDirectories('/etc/ssl/certs/zarafa');
    CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
    if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';
    if length(SYS.OPENSSL_CERTIFCATE_HOSTS())>0 then extensions:=' -extensions HOSTS_ADDONS ';

  generate:=false;
  for i:=0 to l.Count-1 do begin
       if not FileExists('/etc/ssl/certs/zarafa/' +l.Strings[i]) then begin
          generate:=true;
          break;
       end else begin
          logs.DebugLogs('Starting zarafa..............: Zarafa-gateway /etc/ssl/certs/zarafa/' +l.Strings[i] + ' OK');
       end;
  end;

  if generate then begin
     SYS.OPENSSL_CERTIFCATE_CONFIG();
     CertificateIniFile:=SYS.OPENSSL_CONFIGURATION_PATH();
     if not FileExists(CertificateIniFile) then begin
        logs.Syslogs('tzarafa_server.GENERATE_CERTIFICATE():: FATAL ERROR, unable to find any ssl configuration path');
        exit;
     end;

     if not fileExists(SYS.LOCATE_OPENSSL_TOOL_PATH()) then begin
        logs.Syslogs('tzarafa_server.GENERATE_CERTIFICATE():: FATAL ERROR, unable to stat openssl');
        exit;
     end;

     tmpstr:=LOGS.FILE_TEMP();
     fpsystem('/bin/cp '+CertificateIniFile+' '+tmpstr);
     certini:=TiniFile.Create(tmpstr);
     input_password:=certini.ReadString('req','input_password',ldap.ldap_settings.password);
     if length(input_password)=0 then input_password:=ldap.ldap_settings.password;

     logs.Debuglogs('Settings certificate file...');
     certini.WriteString('req_distinguished_name','organizationalUnitName_default','Mailserver');
     certini.UpdateFile;
     logs.Debuglogs('Generate server certificate');
     logs.Debuglogs('extensions:"'+extensions+'"');


     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' genrsa -out /etc/ssl/certs/zarafa/server.key 1024';
     writeln(cmd);
     fpsystem(cmd);

     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' req -new -key /etc/ssl/certs/zarafa/server.key -batch -config '+tmpstr+extensions+' -out /etc/ssl/certs/zarafa/server.csr';
     writeln(cmd);
     fpsystem(cmd);
     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' genrsa -out /etc/ssl/certs/zarafa/ca.key 1024 -batch -config '+tmpstr+extensions;

     writeln(cmd);
     fpsystem(cmd);
     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' req -new -x509 -days '+CertificateMaxDays+' -key /etc/ssl/certs/zarafa/ca.key -batch -config '+tmpstr+extensions+' -out /etc/ssl/certs/zarafa/ca.csr';

     writeln(cmd);
     fpsystem(cmd);
     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' x509 -extfile '+tmpstr+extensions+' -x509toreq -days '+CertificateMaxDays+' -in /etc/ssl/certs/zarafa/ca.csr -signkey /etc/ssl/certs/zarafa/ca.key -out /etc/ssl/certs/zarafa/ca.req';

     writeln(cmd);
     fpsystem(cmd);
     cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' x509 -extfile '+tmpstr+extensions+' -req -days '+CertificateMaxDays+' -in /etc/ssl/certs/zarafa/ca.req -signkey /etc/ssl/certs/zarafa/ca.key -out /etc/ssl/certs/zarafa/ca.crt';
     //cmd:=SYS.LOCATE_OPENSSL_TOOL_PATH()+' x509 -req -extfile '+tmpstr+extensions+' -req -days '+CertificateMaxDays+' -CA /etc/ssl/certs/zarafa/server.csr -CAkey /etc/ssl/certs/zarafa/server.key -CAcreateserial -CAserial /etc/ssl/certs/zarafa/ca.srl -in /etc/ssl/certs/zarafa/ca.csr -out /etc/ssl/certs/zarafa/ca.crt';
     writeln(cmd);
     fpsystem(cmd);
  end;
end;
//#############################################################################
procedure tzarafa_server.APACHE_CERTIFICATES();
var
   CertificateIniFile:string;
   certini:Tinifile;
   tmpstr,cmd:string;
   smtpd_tls_key_file:string;
   smtpd_tls_cert_file:string;
   smtpd_tls_CAfile:string;
   POSFTIX_POSTCONF:string;
   l:TstringList;
   generate:boolean;
   i:integer;
   CertificateMaxDays:string;
   input_password,passfile,extensions:string;
   ldap:topenldap;
   CertificatePassword:string;
   openssl_path:string;

   private_ca_key:string;
   private_apache_key:string;
   certificate_request_path:string;
   certificate_path:string;
   x509_ca_crt:string;



begin

  ldap:=topenldap.Create;
  if not FileExists(SERVER_BIN_PATH()) then begin
     logs.DebugLogs('Starting zarafa..............: Unable to stat zarafa-server path');
     exit;
  end;

  l:=TstringList.Create;
  l.Add('internal-ca.key');
  l.Add('internal-ca.crt');
  l.add('apache-ca.key');
  l.add('apache.csr');
  l.add('apache.crt');
  l.add('apache-ca.key.nopass.key');
  l.add('apache.crt.nopass.cert');

  if SYS.COMMANDLINE_PARAMETERS('--zarafa-apache-certificates') then begin
       for i:=0 to l.Count-1 do begin
           logs.DebugLogs('Starting zarafa..............: Apache removing file /etc/ssl/certs/zarafa/' +l.Strings[i]);
           logs.DeleteFile('/etc/ssl/certs/zarafa/' +l.Strings[i]);
       end;
  end;

  generate:=false;
  for i:=0 to l.Count-1 do begin
       if not FileExists('/etc/ssl/certs/zarafa/' +l.Strings[i]) then begin
          generate:=true;
          break;
       end else begin
          logs.DebugLogs('Starting zarafa..............: Apache /etc/ssl/certs/zarafa/' +l.Strings[i] + ' OK');
       end;
  end;
  if not generate then exit;

     SYS.OPENSSL_CERTIFCATE_CONFIG();
     CertificateIniFile:=SYS.OPENSSL_CONFIGURATION_PATH();
     if not FileExists(CertificateIniFile) then begin
        logs.Syslogs('tzarafa_server.APACHE_CERTIFICATES():: FATAL ERROR, unable to find any ssl configuration path');
        exit;
     end;


     private_ca_key:='/etc/ssl/certs/zarafa/internal-ca.key';
     x509_ca_crt:='/etc/ssl/certs/zarafa/internal-ca.crt';
     private_apache_key:='/etc/ssl/certs/zarafa/apache-ca.key';
     certificate_request_path:='/etc/ssl/certs/zarafa/apache.csr';
     certificate_path:='/etc/ssl/certs/zarafa/apache.crt';

    forceDirectories('/etc/ssl/certs/zarafa');
    CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
    openssl_path:=SYS.LOCATE_OPENSSL_TOOL_PATH();
    if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';
    if length(SYS.OPENSSL_CERTIFCATE_HOSTS())>0 then extensions:=' -extensions HOSTS_ADDONS ';
    logs.Debuglogs('Generate server certificate');
    logs.Debuglogs('extensions:"'+extensions+'"');

     tmpstr:=LOGS.FILE_TEMP();
     fpsystem('/bin/cp '+CertificateIniFile+' '+tmpstr);


    CertificatePassword:=ldap.ldap_settings.password;
    logs.DebugLogs('Starting zarafa..............: Apache generate private CA key and private CA X.509 certificate');

    writeln('STEP (1) ----------------------------');
    cmd:=openssl_path+' genrsa -des3 -passout pass:'+CertificatePassword+' -out '+private_ca_key+' 2048';
    writeln(cmd);
    fpsystem(cmd);


    writeln('STEP (2) ----------------------------');
    cmd:=openssl_path+' req -new -x509 -config '+CertificateIniFile+extensions+' -days '+CertificateMaxDays+' -passin pass:'+CertificatePassword+' -key '+private_ca_key+' -out '+x509_ca_crt;
    writeln(cmd);
    fpsystem(cmd);
    logs.DebugLogs('Starting zarafa..............: Apache generate key and a certificate request');

    writeln('STEP (3) ----------------------------');
    cmd:=openssl_path+' genrsa -des3 -passout pass:'+CertificatePassword+' -out '+private_apache_key+' 2048';
    writeln(cmd);
    fpsystem(cmd);

    writeln('STEP (4) ----------------------------');
    cmd:=openssl_path+' req -new -key '+private_apache_key+' -out '+certificate_request_path+' -passin pass:'+CertificatePassword+' -config '+CertificateIniFile+extensions;
    writeln(cmd);
    fpsystem(cmd);

    writeln('STEP (5) ----------------------------');
    cmd:=openssl_path+' x509 -req -in '+certificate_request_path+' -out '+certificate_path+' -passin pass:'+CertificatePassword+' -sha1 -CA '+x509_ca_crt+' -CAkey '+private_ca_key+' -CAcreateserial -days '+CertificateMaxDays;
    writeln(cmd);
    fpsystem(cmd);

    writeln('STEP (6) ----------------------------');
    cmd:=openssl_path+' rsa -in '+private_apache_key+' -passin pass:'+CertificatePassword+' -out '+private_apache_key+'.nopass.key';
    writeln(cmd);
    fpsystem(cmd);

    writeln('STEP (7) ----------------------------');
    cmd:=openssl_path+' x509 -in '+certificate_request_path+' -out '+certificate_path+'.nopass.cert -req -signkey '+private_apache_key+'.nopass.key -days '+CertificateMaxDays;
    writeln(cmd);
    fpsystem(cmd);



    fpsystem('/bin/chmod 0400 /etc/ssl/certs/zarafa/*.key');




end;

end.


