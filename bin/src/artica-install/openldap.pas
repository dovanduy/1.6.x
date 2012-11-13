unit openldap;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem;

type LDAP=record
      admin:string;
      password:string;
      suffix:string;
      servername:string;
      Port:string;
      cyrus_password:string;
  end;

  type
  topenldap=class


private
     LOGS:Tlogs;
     GLOBAL_INI:TiniFIle;
     SYS:TSystem;
     artica_path:string;
     schemas:TstringList;
     OpenLDAPDisableSSL:integer;
     local_server:boolean;
     function COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
     function get_INFOS(key:string):string;
     function get_LDAP_ADMIN():string;
     function get_LDAP_PASSWORD():string;
     function get_LDAP_suffix():string;
     function ReadFileIntoString(path:string):string;
     procedure CHECK_SCHEMA_DEBIAN_PERMISSIONS();
     function FIND_USERID():string;





public
    ldap_settings:LDAP;
    procedure   Free;
    constructor Create;
    function    SLAPD_BIN_PATH():string;
    function    SLAPD_CONF_PATH():string;
    function    SCHEMA_PATH():string;
    function    USE_SUSE_SCHEMA():boolean;
    function    INITD_PATH():string;
    procedure   LDAP_START();
    procedure   LDAP_STOP();
    function    LDAP_PID():string;
    function    LDAP_VERSION():string;
    function    SLAPCAT_PATH():string;
    function    DAEMON_PATH():string;
    function    PID_PATH():string;
    function    get_LDAP(key:string):string;
    procedure   set_LDAP(key:string;val:string);
    function    LDAP_DATABASES_PATH():string;
    procedure   SAVE_SLAPD_CONF();
    function    STATUS():string;
    function    FindModulepath(modulelib:string):string;
    procedure   WRITE_INITD();
    function    IS_DYNALITS():boolean;
    procedure   ChangeSettings(server_name:string;port:string;username:string;password:string;suffix:string;ChangeSlapd:string);
    procedure   SLAP_INDEX();
    function    CREATE_CERTIFICATE():boolean;
    procedure   SET_DB_CONFIG();
END;

implementation

constructor topenldap.Create;
begin

       LOGS:=tlogs.Create();
       SYS:=Tsystem.Create;
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');
      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;


   schemas:=TstringList.Create;
   schemas.Add('core.schema');
   schemas.Add('cosine.schema');
   schemas.add('mod_vhost_ldap.schema');
   schemas.Add('nis.schema');
   schemas.Add('inetorgperson.schema');
   schemas.Add('evolutionperson.schema');
   schemas.Add('postfix.schema');
   schemas.add('dhcp.schema');
   schemas.Add('samba.schema');
   schemas.Add('ISPEnv.schema');
   schemas.Add('mozilla-thunderbird.schema');
   schemas.Add('officeperson.schema');
   schemas.Add('pureftpd.schema');
   schemas.add('joomla.schema');
   schemas.add('autofs.schema');
   schemas.add('dnsdomain2.schema');
   schemas.add('zarafa.schema');

   OpenLDAPDisableSSL:=0;
   if not TryStrToInt(SYS.GET_INFO('OpenLDAPDisableSSL'),OpenLDAPDisableSSL) then OpenLDAPDisableSSL:=0;


      ldap_settings.admin:=get_LDAP('admin');
      ldap_settings.password:=get_LDAP('password');
      ldap_settings.Port:=Get_LDAP('port');
      ldap_settings.servername:=Get_LDAP('server');
      ldap_settings.suffix:=Get_LDAP('suffix');
      ldap_settings.cyrus_password:=Get_LDAP('cyrus_password');

      if length(ldap_settings.cyrus_password)=0 then begin
             ldap_settings.cyrus_password:=ldap_settings.password;
      end;


      if length(ldap_settings.servername)=0 then ldap_settings.servername:='127.0.0.1';
      if ldap_settings.servername='*' then ldap_settings.servername:='127.0.0.1';
      if length(ldap_settings.Port)=0 then ldap_settings.Port:='389';
      local_server:=false;
      if ldap_settings.servername='127.0.0.1' then local_server:=true;
      if ldap_settings.servername='localhost' then local_server:=true;

      if length(trim(ldap_settings.admin))=0 then begin
             ldap_settings.admin:=get_LDAP_ADMIN();
             set_LDAP('admin',ldap_settings.admin);
      end;

       if length(trim(ldap_settings.password))=0 then begin
             ldap_settings.password:=get_LDAP_PASSWORD();
             set_LDAP('password',ldap_settings.password);
      end;

       if length(trim(ldap_settings.suffix))=0 then begin
             ldap_settings.suffix:=get_LDAP_suffix();
             set_LDAP('suffix',ldap_settings.suffix);
      end;

end;
//##############################################################################
procedure topenldap.free();
begin
    logs.Free;
    SYS.Free;
    schemas.free;
end;
//##############################################################################
function topenldap.SLAPD_CONF_PATH():string;
begin
   if FileExists('/etc/ldap/slapd.conf') then exit('/etc/ldap/slapd.conf');
   if FileExists('/etc/openldap/slapd.conf') then exit('/etc/openldap/slapd.conf');
   if FileExists('/etc/openldap/ldap.conf') then exit('/etc/openldap/slapd.conf');
   if FileExists('/opt/artica/etc/openldap/slapd.conf') then exit('/opt/artica/etc/openldap/slapd.conf');
   if FileExists('/usr/local/etc/openldap/slapd.conf') then exit('/usr/local/etc/openldap/slapd.conf');
   exit('/etc/ldap/slapd.conf');

end;
//##############################################################################
function topenldap.STATUS():string;
var
pidpath:string;
begin
   SYS.MONIT_DELETE('APP_LDAP');
   pidpath:=logs.FILE_TEMP();
   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --openldap >'+pidpath +' 2>&1');
   result:=logs.ReadFromFile(pidpath);
   logs.DeleteFile(pidpath);
end;
//#########################################################################################
function topenldap.SLAPCAT_PATH():string;
begin
result:=SYS.LOCATE_SLAPCAT()
end;
//##############################################################################

function topenldap.SCHEMA_PATH():string;
begin
   if DirectoryExists('/etc/ldap/schema') then exit('/etc/ldap/schema');
   if DirectoryExists('/etc/openldap/schema') then exit('/etc/openldap/schema');
   if DirectoryExists('/opt/artica/etc/openldap/schema') then exit('/opt/artica/etc/openldap/schema');
end;
//##############################################################################
function topenldap.INITD_PATH():string;
begin
   if FileExists('/opt/artica/bin/slapd') then begin
        result:=ExtractFilePath(ParamStr(0));
        result:=result + 'artica-install slapd';
        exit();
   end;
   if FileExists('/etc/init.d/ldap') then result:='/etc/init.d/ldap';
   if FileExists('/etc/init.d/slapd') then result:='/etc/init.d/slapd';
   if FileExists('/usr/local/etc/rc.d/slapd') then result:='/usr/local/etc/rc.d/slapd';
end;
//##############################################################################

function  topenldap.DAEMON_PATH():string;
begin
exit(SYS.LOCATE_SLAPD());
end;
//##############################################################################
function topenldap.USE_SUSE_SCHEMA():boolean;
var path:string;
begin
  path:=SCHEMA_PATH();
  if not DirectoryExists(path) then exit(false);
  path:=path + '/rfc2307bis.schema';
  if FIleExists(path) then begin
     logs.DeleteFile(path);
     fpsystem('/bin/cp /usr/share/artica-postfix/bin/install/rfc2307bis.schema '+path);
      exit(false);
  end;

  if not fileExists(path) then exit(false);
  exit(false);
  exit(true);
end;
//##############################################################################
function topenldap.LDAP_PID():string;
var pid:string;
begin

pid:=SYS.GET_PID_FROM_PATH(PID_PATH());
if length(pid)=0 then begin
  //logs.DebugLogs('slapd: [INFO] "' + PID_PATH() + '" is null');
  pid:=SYS.PIDOF(SLAPD_BIN_PATH());
  if length(pid)=0 then begin
       //logs.DebugLogs('slapd: [INFO] pidof "' + SLAPD_BIN_PATH() + '" is null');
       exit;
  end;
end;
exit(pid);
end;
//##############################################################################




procedure topenldap.LDAP_STOP();
var pid:string;
count:integer;
D:boolean;
tmp:string;
l:Tstringlist;
top:string;
begin
  d:=COMMANDLINE_PARAMETERS('debug');
  pid:=LDAP_PID();
  count:=0;

  if SYS.COMMANDLINE_PARAMETERS('--monit') then begin
       logs.DebugLogs('slapd: [INFO] is stopped from monit !');
       tmp:=logs.FILE_TEMP();
       top:=SYS.LOCATE_GENERIC_BIN('top');
       if length(top)>0 then fpsystem(top+' -b -n 1 >'+tmp+' 2>&1');
       logs.NOTIFICATION('[ARTICA]: ' + SYS.HOSTNAME_g()+': Stopping LDAP PID '+pid+' from Process Monitor','Process Monitor has decided to start OpenLDAP because it found a CPU overload or slpad was not running...'+LOGS.ReadFromFile(tmp),'system');
       LOGS.DeleteFile(tmp);
  end;

  if not SYS.PROCESS_EXIST(pid) then begin
      logs.Syslogs('Stopping openLdap server.....: Already stopped');
      exit;
  end;

  fpsystem('kill '+pid);
  pid:=LDAP_PID();
  sleep(900);
   if not SYS.PROCESS_EXIST(pid) then begin
      logs.Syslogs('Stopping openLdap server.....: '+pid+' Stopped');
      exit;
  end;

  fpsystem(INITD_PATH()+' stop');

end;
//##############################################################################
function topenldap.LDAP_VERSION():string;
var
    path,ver:string;
    RegExpr:TRegExpr;
    tmpstr:string;
    commandline:string;
    D:Boolean;
begin
   D:=COMMANDLINE_PARAMETERS('debug');
   tmpstr:=LOGS.FILE_TEMP();
   path:=SLAPD_BIN_PATH();


   if not FileExists(path) then begin
      if D then logs.Debuglogs('LDAP_VERSION:: Unable to locate slapd bin');
      exit;
   end;
   result:=SYS.GET_CACHE_VERSION('APP_OPENLDAP');
   if length(result)>2 then exit;
   commandline:='/bin/cat -v ' + path + '|grep ''$OpenLDAP:'' >'+tmpstr+' 2>&1';
   if D then logs.Debuglogs('LDAP_VERSION:: ' + commandline);

   fpsystem(commandline);
   ver:=logs.ReadFromFile(tmpstr);
   logs.DeleteFile(tmpstr);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='\$OpenLDAP:\s+slapd\s+([0-9\.]+)';
   if RegExpr.Exec(ver) then begin
      ver:=RegExpr.Match[1];
      RegExpr.Free;
      exit(ver);
   end;
  SYS.SET_CACHE_VERSION('APP_OPENLDAP',result);
end;
//#############################################################################
function topenldap.LDAP_DATABASES_PATH():string;
var
    path,ver:string;
    RegExpr:TRegExpr;
    l:TstringList;
    i:integer;
begin

   path:=SLAPD_CONF_PATH();


   if not FileExists(path) then begin
      logs.Debuglogs('LDAP_DATABASES_PATH:: Unable to locate ldap configuration path');
      exit;
   end;

   l:=TstringList.Create;
   l.LoadFromFile(path);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^directory\s+(.+)';
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
            result:=trim(RegExpr.Match[1]);
       end;
   
   end;
   
   FreeAndnil(l);
   FreeAndnil(RegExpr);

end;
//#############################################################################
procedure topenldap.SLAP_INDEX();
begin

     if not FileExists(SYS.LOCATE_SLAPINDEX()) then begin
        writeln('Unable to locate slapindex !!! exiting');
        exit;
     end;


     writeln('locking watchdog...');
     fpsystem('/bin/touch /etc/artica-postfix/STOP-LDAP');
     writeln('stopping ldap');
     fpsystem('/etc/init.d/artica-postfix stop ldap');
     writeln('indexing ldap');
     fpsystem(SYS.LOCATE_SLAPINDEX());
     writeln('unlock watchdog...');
     fpsystem('/bin/rm -f /etc/artica-postfix/STOP-LDAP');
     writeln('starting ldap');
     fpsystem('/etc/init.d/artica-postfix start ldap --verbose');
     writeln('success indexing ldap');
end;
//#############################################################################



procedure topenldap.LDAP_START();
var
   pid:string;
   ck,i:integer;
   cmd:string;
   ldaps:string;
   ldapl:string;
   EnableNonEncryptedLdapSession:integer;
   LdapListenIPAddr:Tstringlist;
   CertificateCreated:boolean;
begin
  ck:=0;
  pid:=LDAP_PID();
  CertificateCreated:=false;

  if SYS.COMMANDLINE_PARAMETERS('--monit') then begin
       logs.DebugLogs('slapd: [INFO] is started from monit !');
       logs.NOTIFICATION('[ARTICA]: ' + SYS.HOSTNAME_g()+': Starting LDAP from Process Monitor','Process Monitor has decided to start OpenLDAP because it found a CPU overload or slpad was not running...','system');
  end;
  if SYS.PROCESS_EXIST(pid) then exit;
  logs.Syslogs('slapd: [INFO] server');
  logs.DebugLogs('slapd: [INFO] Pid Path="' + PID_PATH() + '"');
  logs.DebugLogs('slapd: [INFO] Pid "' + LDAP_PID() + '"');
  forceDirectories('/var/lib/ldap');
  forceDirectories('/var/run/slapd');
  fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php');
  SAVE_SLAPD_CONF();
  fpsystem(INITD_PATH()+' start');
end;
//##############################################################################
function topenldap.SLAPD_BIN_PATH():string;
var path:string;
begin
  path:=get_INFOS('slapd_bin');
  if length(path)>0 then begin
   if fileExists(path) then exit(path);
  end;
  if FileExists('/usr/sbin/slapd') then exit('/usr/sbin/slapd');
  if FileExists('/usr/lib/openldap/slapd') then exit('/usr/lib/openldap/slapd');
  if FileExists('/opt/artica/bin/slapd') then exit('/opt/artica/bin/slapd');
  exit(SYS.LOCATE_GENERIC_BIN('slapd'));
end;
//##############################################################################
procedure topenldap.CHECK_SCHEMA_DEBIAN_PERMISSIONS();
var
   RegExpr:TRegExpr;
   l:TstringList;
   user,group:string;
   i:Integer;
   logs:Tlogs;
   xSCHEMA_PATH:string;
begin
 logs:=Tlogs.Create;
 if not FileExists('/etc/default/slapd') then exit;
 l:=TStringList.Create;
 try
    l.LoadFromFile('/etc/default/slapd');
 except
   exit;
 end;
 RegExpr:=TRegExpr.Create;
 For i:=0 to l.Count-1 do begin
      RegExpr.Expression:='^SLAPD_USER="(.+?)"';
      if RegExpr.Exec(l.Strings[i]) then user:=RegExpr.Match[1];

      RegExpr.Expression:='^SLAPD_GROUP="(.+?)"';
      if RegExpr.Exec(l.Strings[i]) then group:=RegExpr.Match[1];

 end;
  xSCHEMA_PATH:=SCHEMA_PATH();
  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: /etc/default/slapd');
  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: xSCHEMA_PATH="'+xSCHEMA_PATH+'"');
  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: user="'+user+'"');
  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: group="'+group+'"');

  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: Apply security settings for user '+user+':'+group + ' on artica schemas');
  logs.Debuglogs('/bin/chown '+user+':'+group+' ' + artica_path + '/bin/install/*.schema');
  fpsystem('/bin/chown '+user+':'+group+' ' + artica_path + '/bin/install/*.schema');
  logs.Debuglogs('/bin/chown -h '+user+':'+group+' ' + xSCHEMA_PATH + '/*');
  fpsystem('/bin/chown -h '+user+':'+group+' ' + xSCHEMA_PATH + '/*');
  logs.Debuglogs('LDAP_CHECK_SCHEMA_DEBIAN_PERMISSIONS:: End...');
end;
//##############################################################################


procedure topenldap.SET_DB_CONFIG();
var
filedatas:TstringList;
LdapDBSetCachesize:integer;
begin
    if FileExists('/etc/artica-postfix/no-ldap-change') then exit;
    forceDirectories('/var/lib/ldap');
    filedatas:=TstringList.Create;

    if not TRyStrToINt(SYS.GET_INFO('LdapDBSetCachesize'),LdapDBSetCachesize) then LdapDBSetCachesize:=5120000;


    //filedatas.Add('set_cachesize 0 268435456 1');
    //filedatas.Add('set_lg_regionmax 262144');
    //filedatas.Add('set_lg_bsize 2097152');
    filedatas.Add('set_lk_max_objects 1500');
    filedatas.Add('set_lk_max_locks 1500');
    filedatas.Add('set_lk_max_lockers 1500');
    filedatas.Add('set_flags DB_LOG_AUTOREMOVE');
    fileDatas.Add('set_cachesize 0 '+INtTOstr(LdapDBSetCachesize)+' 1');
    logs.DebugLogs('slapd: [INFO] server writing DB_CONFIG');
    logs.WriteToFile(filedatas.Text,'/var/lib/ldap/DB_CONFIG');
    filedatas.Free;
end;
//##############################################################################
function topenldap.IS_DYNALITS():boolean;
var
   l:TstringList;
   RegExpr:TRegExpr;
   i:integer;
begin
  result:=false;
  if not FileExists(SLAPD_CONF_PATH()) then exit;
  l:=TstringList.Create;
  l.LoadFromFile(SLAPD_CONF_PATH());
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='^moduleload\s+dynlist';
  for i:=0 to l.Count-1 do begin
      if RegExpr.Exec(l.Strings[i]) then begin
         result:=true;
         break;
      end;
  end;
  
l.free;
RegExpr.free;

end;
//##############################################################################
procedure topenldap.SAVE_SLAPD_CONF();
var
  ldap_suffix,artica_admin,artica_password,user:string;
  l:TstringList;
  dyngroup:boolean;
  dynlist_path:string;
  modulepath:string;
  back_hdb:string;
  EnableRemoteAddressBook:integer;
  EnablePerUserRemoteAddressBook:integer;
  LdapAclsPlus:string;
  i:integer;
  LdapDBCachesize:integer;
  LdapAllowAnonymous:integer;
  loglevel:integer;
  back_monitor:string;
  syncprov:string;
  EnableLDAPSyncProv:integer;
  EnableLDAPSyncProvClient:integer;
  SyncProvUserDN:string;
  LDAPSyncProvClientServer:string;
  LDAPSyncProvClientSearchBase:string;
  LDAPSyncProvClientBindDN:string;
  LDAPSyncProvClientBindPassword:string;
  NoLDAPBackMonitor:integer;
  artica_password_cmd:string;
  LockLdapConfig:integer;
  SlapdThreads:integer;
  EnableOpenLdapProxy:integer;
  OpenLDAPProxyContent:string;

begin
   EnableRemoteAddressBook:=0;
   EnablePerUserRemoteAddressBook:=0;
   NoLDAPBackMonitor:=0;
   LockLdapConfig:=0;
   if not tryStrToInt(SYS.GET_INFO('LockLdapConfig'),LockLdapConfig) then LockLdapConfig:=0;
   if LockLdapConfig=1 then begin
       logs.Debuglogs('slapd: [INFO] server configuration locked...');
       logs.Debuglogs('slapd: [INFO] server set LockLdapConfig to 0 if you want artica to modify configuration');
       exit;
   end;
   if FileExists('/etc/artica-postfix/no-ldap-change') then begin
      logs.Debuglogs('slapd: [INFO] server skip auto-change ldap configuration...');
      exit;
   end;


  SET_DB_CONFIG();
  CHECK_SCHEMA_DEBIAN_PERMISSIONS();
    if DirectoryExists('/etc/openldap/slapd.d') then begin
       logs.DebugLogs('slapd: [INFO] removing content of /etc/openldap/slapd.d');
       fpsystem('/bin/rm -rf /etc/openldap/slapd.d');
  end;

  if DirectoryExists('/etc/ldap/slapd.d') then begin
       logs.DebugLogs('slapd: [INFO] removing content of /etc/ldap/slapd.d');
       fpsystem('/bin/rm -rf /etc/ldap/slapd.d');
  end;

   if not TryStrToInt(SYS.GET_INFO('EnableRemoteAddressBook'),EnableRemoteAddressBook) then EnableRemoteAddressBook:=0;
   if not TryStrToInt(SYS.GET_INFO('EnablePerUserRemoteAddressBook'),EnablePerUserRemoteAddressBook) then EnablePerUserRemoteAddressBook:=0;
   if not TryStrToInt(SYS.GET_INFO('LdapAllowAnonymous'),LdapAllowAnonymous) then LdapAllowAnonymous:=0;
   if not TryStrToInt(SYS.GET_INFO('EnableLDAPSyncProv'),EnableLDAPSyncProv) then EnableLDAPSyncProv:=0;
   if not TryStrToInt(SYS.GET_INFO('EnableLDAPSyncProvClient'),EnableLDAPSyncProvClient) then EnableLDAPSyncProvClient:=0;
   if not TryStrToInt(SYS.GET_INFO('NoLDAPBackMonitor'),NoLDAPBackMonitor) then NoLDAPBackMonitor:=0;
   if not TryStrToint(SYS.GET_INFO('OpenLDAPLogLevel'),loglevel) then loglevel:=0;
   if not TryStrToint(SYS.GET_INFO('SlapdThreads'),SlapdThreads) then SlapdThreads:=0;
   if not TryStrToint(SYS.GET_INFO('EnableOpenLdapProxy'),EnableOpenLdapProxy) then EnableOpenLdapProxy:=0;


   if EnableOpenLdapProxy=1 then begin
         fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.ldap.php --proxy');
         if FileExists('/etc/artica-postfix/proxy.slpad.conf') then begin
            OpenLDAPProxyContent:=logs.ReadFromFile('/etc/artica-postfix/proxy.slpad.conf');
         end else begin
            EnableOpenLdapProxy:=0;
         end;
   end;


   SyncProvUserDN:=SYS.GET_INFO('SyncProvUserDN');
   LDAPSyncProvClientServer:=SYS.GET_INFO('LDAPSyncProvClientServer');
   LDAPSyncProvClientSearchBase:=SYS.GET_INFO('LDAPSyncProvClientSearchBase');
   LDAPSyncProvClientBindDN:=SYS.GET_INFO('LDAPSyncProvClientBindDN');
   LDAPSyncProvClientBindPassword:=SYS.GET_INFO('LDAPSyncProvClientBindPassword');

   if EnableLDAPSyncProvClient=1 then begin
      if length(LDAPSyncProvClientServer)=0 then EnableLDAPSyncProvClient:=0;
      if length(LDAPSyncProvClientSearchBase)=0 then EnableLDAPSyncProvClient:=0;
      if length(LDAPSyncProvClientBindDN)=0 then EnableLDAPSyncProvClient:=0;
      if length(LDAPSyncProvClientBindPassword)=0 then EnableLDAPSyncProvClient:=0;
   end;

    logs.WriteToFile(ldap_settings.password,'/etc/ldap.secret');
    logs.OutputCmd('/bin/chmod 0600 /etc/ldap.secret');
    fpsystem(SYS.LOCATE_GENERIC_BIN('addgroup')+' nvram >/dev/null 2>&1');

   
   if DirectoryExists('/etc/ldap/slapd.d') then fpsystem('/bin/rm -rf /etc/ldap/slapd.d');
   logs.Debuglogs('slapd: [INFO] writing new configuration...');
   artica_admin:=get_LDAP('admin');
   artica_password:=get_LDAP('password');
   ldap_suffix:=get_LDAP('suffix');
   artica_password_cmd:=SYS.ESCAPE_PASSWORD(artica_password);

   if FIleExists('/usr/bin/smbpasswd') then begin
       logs.Debuglogs('slapd: [INFO] set password in secret.tdb');
       logs.OutputCmd('/usr/bin/smbpasswd -w '+artica_password_cmd);
   end;


       if length(ldap_suffix)=0 then begin
          ldap_suffix:='dc=my-domain,dc=com';
          Set_LDAP('suffix',ldap_suffix);
       end;


       if length(artica_password)=0 then begin
          artica_password:='secret';
          Set_LDAP('password',artica_password);
       end;

       if length(artica_admin)=0 then begin
          artica_admin:='Manager';
          Set_LDAP('admin',artica_admin);
       end;
       

       user:=FIND_USERID();
       if length(user)>0 then begin
          logs.Debuglogs('SAVE_SLAPD_CONF() set permission for ' + user);
          fpsystem('/bin/chown -R ' + user + ' /var/lib/ldap');
          fpsystem('/bin/chown -R ' + user + ' /var/run/slapd');
       end;

l:=TstringList.Create;
l.Add('pidfile         /var/run/slapd/slapd.pid');

l.Add('');
l.Add('#Artica schemas added');
if FileExists(SCHEMA_PATH()+'/rfc2307bis.schema') then begin
   logs.Syslogs('slapd: [INFO] server move rfc2307bis schema');
//   l.Add('include         '+SCHEMA_PATH()+'/rfc2307bis.schema');
     fpsystem('/bin/mv '+SCHEMA_PATH()+'/rfc2307bis.schema ' + SCHEMA_PATH()+'/rfc2307bis.schema.mv');
end;

   for i:=0 to schemas.Count-1 do begin
       if FileExists(SCHEMA_PATH()+'/'+schemas.Strings[i]) then begin
          l.Add('include         '+SCHEMA_PATH()+'/' + schemas.Strings[i]);
       end else begin
           logs.Debuglogs('Starting......: '+SCHEMA_PATH()+'/'+schemas.Strings[i] +' does not exists');
           logs.Debuglogs('Starting......: skipping schema "'+schemas.Strings[i]+'"' );
       end;
   end;

l.add('');

dyngroup:=false;

back_hdb:=FindModulepath('back_hdb.la');
dynlist_path:=FindModulepath('dynlist.la');
back_monitor:=FindModulepath('back_monitor.so');
syncprov:=FindModulepath('syncprov.so');
if not FIleExists(syncprov) then syncprov:=FindModulepath('syncprov-2.4.so.2');


if(NoLDAPBackMonitor=1) then back_monitor:='';

if FileExists(back_hdb) then begin
  logs.Syslogs('slapd: [INFO] server backend will be hdb');
  modulepath:=ExtractFilePath(back_hdb);
  if modulepath[length(modulepath)]='/' then modulepath:=Copy(modulepath,0,length(modulepath)-1);
end;


if FileExists(syncprov) then begin
    logs.Syslogs('slapd: [INFO] server synchronization module installed');
    if length(SyncProvUserDN)=0 then EnableLDAPSyncProv:=0;
end else begin
    EnableLDAPSyncProv:=0;
end;



if FileExists(dynlist_path) then begin
   dyngroup:=true;
   if length(modulepath)=0 then begin
      modulepath:=ExtractFilePath(dynlist_path);
      if modulepath[length(modulepath)]='/' then modulepath:=Copy(modulepath,0,length(modulepath)-1);
   end;
   logs.Syslogs('slapd: [INFO] find dynlist module');
end else begin
    logs.Syslogs('slapd: [INFO] server unable to find dynlist.la');
    logs.Syslogs('slapd: [INFO] server some features could be disabled');
end;

    if dyngroup then begin
       if FileExists(SCHEMA_PATH()+'/dyngroup.schema') then begin
          dyngroup:=true;
          logs.Syslogs('slapd: [INFO] server include dyngroup schema');
          l.Add('include         '+SCHEMA_PATH()+'/dyngroup.schema');
       end else begin
           dyngroup:=false;
           logs.Syslogs('slapd: [INFO] server unable to find dyngroup.schema');
           logs.Syslogs('slapd: [INFO] server some features could be disabled');
       end;
    end;



l.Add('');
l.Add('argsfile        /var/run/slapd/slapd.args');

if loglevel<>0 then begin
l.Add('loglevel        '+IntTOStr(loglevel));
     logs.Syslogs('slapd: [INFO] log level to '+IntTOStr(loglevel));
end else begin
   l.Add('loglevel        0');
end;

if length(modulepath)>0 then l.Add('modulepath	'+modulepath);
//if dyngroup then l.Add('moduleload'+chr(9)+'dynlist');




//l.add('database monitor');

if length(back_monitor)>0 then l.Add('moduleload'+chr(9)+'back_monitor');
if length(back_hdb)>0 then l.Add('moduleload'+chr(9)+'back_hdb');
if length(back_monitor)=0 then  logs.Syslogs('slapd: [INFO] no backend Monitor is set...!');
if EnableLDAPSyncProv=1 then l.Add('moduleload'+chr(9)+'syncprov');
if EnableOpenLdapProxy=1 then  l.add('# ******* LDAP Proxy Specific modules;');
if EnableOpenLdapProxy=1 then l.Add('moduleload'+chr(9)+'back_meta');
if EnableOpenLdapProxy=1 then l.Add('moduleload'+chr(9)+'back_ldap');
if EnableOpenLdapProxy=1 then l.Add('moduleload'+chr(9)+'rwm');
if length(back_hdb)>0 then l.Add('backend'+chr(9)+'hdb');
   
l.Add('sizelimit 500');
l.Add('tool-threads 1');
if EnableOpenLdapProxy=1 then begin
   l.add('# ******* LDAP Proxy parameters');
   l.Add(OpenLDAPProxyContent);
   l.add('');
end;

l.add('# ******* Main Database parameters');

if length(back_monitor)>0 then l.Add('database'+chr(9)+'monitor');
if length(back_hdb)>0 then l.Add('database'+chr(9)+'hdb');
if length(back_hdb)=0 then l.Add('database'+chr(9)+'bdb');
l.Add('suffix          "' + ldap_suffix + '"');
l.Add('rootdn "cn='+artica_admin+','+ldap_suffix+'"');
l.Add('rootpw '+artica_password);
l.add('');

l.Add('directory       /var/lib/ldap');
if not TRyStrToINt(SYS.GET_INFO('LdapDBCachesize'),LdapDBCachesize) then LdapDBCachesize:=1000;
if SlapdThreads>1 then l.Add('threads '+IntToStr(SlapdThreads));
l.Add('cachesize '+ IntToStr(LdapDBCachesize));
l.Add('dbconfig set_lk_max_objects 1500');
l.Add('dbconfig set_lk_max_locks 1500');
l.Add('dbconfig set_lk_max_lockers 1500');
l.Add('');
l.Add('index objectClass                       eq,pres');
l.Add('index ou,cn,mail,surname,givenname      eq,pres,sub');
l.Add('index uniqueMember,mailAlias,associatedDomain,ComputerIP,ComputerMacAddress    eq,pres');
l.Add('index uidNumber,gidNumber,memberUid,uid eq,pres');
l.Add('index entryUUID,entryCSN                eq');
l.Add('index aRecord            pres,eq');
if FileExists(SCHEMA_PATH()+'/dhcp.schema') then begin
l.Add('index dhcpHWAddress                     eq');
l.Add('index dhcpClassData                     eq');
end;
l.Add('');
l.Add('lastmod         on');
l.Add('checkpoint      512 30');
//l.add('secure tls=0');
l.add('');



// ******** SyncProv server mode ***********
if EnableLDAPSyncProv=1 then begin
   l.Add('overlay syncprov');
   l.Add('syncprov-checkpoint 100 10');
   l.add('');
   l.add('');
end;

if EnableLDAPSyncProvClient=1 then begin
   l.add('');
   l.add('');
   l.add('syncrepl rid=001');
   l.add('   provider=ldap://'+LDAPSyncProvClientServer);
   l.add('   type=refreshAndPersist');
   l.add('   retry="5 10 300 +"');
   l.add('   searchbase="'+LDAPSyncProvClientSearchBase+'"');
   l.add('   bindmethod=simple');
  // l.add('   starttls=critical');
   l.add('   binddn="'+LDAPSyncProvClientBindDN+'"');
   l.add('   credentials="'+LDAPSyncProvClientBindPassword+'"');
   l.add('updateref       ldap://'+LDAPSyncProvClientServer);

   l.add('');
   l.add('');
end;

l.Add('access to dn.base="'+ldap_suffix+'"');
l.Add(' by * read');
l.add('');
if EnableLDAPSyncProv=1 then begin
   l.Add('access to dn.base="cn=Subschema"');
   l.Add(' by dn="'+SyncProvUserDN+'" write');
   l.add('');
end;

l.Add('access to attrs=userPassword,sambaNTPassword,sambaLMPassword,sambaPwdLastSet,shadowLastChange,gecos,sambaPWDMustChange,MailboxSecurityParameters');
l.Add(' by peername.ip=127.0.0.1 write');
if EnableLDAPSyncProv=1 then l.Add(' by dn="'+SyncProvUserDN+'" read');
l.Add(' by anonymous auth');
l.Add(' by self write');
l.Add(' by * none');
l.Add('');

if EnableRemoteAddressBook=1 then begin
   logs.Debuglogs('slapd: [INFO] Enable Remote Address Book ...');
   l.Add('access to dn.regex="(cn=.*,)?ou=users,ou=.+?,dc=organizations,'+ldap_suffix+'"');
   l.Add(' by anonymous read');
   l.Add(' by * none');
   l.Add('');

l.Add('access to dn.regex="(cn=.*,)?ou=groups,ou=.+?,dc=organizations,'+ldap_suffix+'"');
l.Add(' by anonymous read');
l.Add(' by * none');
end;


l.Add('access to dn.subtree="'+ldap_suffix+'"');
l.Add(' by peername.ip=127.0.0.1 write');
l.Add(' by self write');
l.Add(' by users write ');
l.Add(' by anonymous auth');
if EnableLDAPSyncProv=1 then l.Add(' by dn="'+SyncProvUserDN+'" read');
l.Add(' by * none');
l.Add('');



l.Add('access to attrs=userPassword,shadowLastChange');
l.Add(' by anonymous auth');
l.Add(' by self write');
l.Add(' by peername.ip=127.0.0.1 write');
if EnableLDAPSyncProv=1 then l.Add(' by dn="'+SyncProvUserDN+'" read');
l.Add(' by * none');
l.Add('');

if EnablePerUserRemoteAddressBook=1 then begin
   LdapAclsPlus:=SYS.GET_INFO('LdapAclsPlus');
   if length(LdapAclsPlus)>0 then begin
      logs.Debuglogs('slapd: [INFO] Adding new acls given by web interface');
      l.Add(LdapAclsPlus);
   end;
end;






l.Add('');
l.Add('password-hash {CLEARTEXT}');
l.Add('monitoring off');

if OpenLDAPDisableSSL=0 then begin
   if CREATE_CERTIFICATE() then begin
      l.Add('');
      l.Add('TLSCACertificateFile /etc/ssl/certs/openldap/ca.crt');
      l.Add('TLSCertificateFile /etc/ssl/certs/openldap/ldap.crt');
      l.Add('TLSCertificateKeyFile /etc/ssl/certs/openldap/ldap.key');
      l.add('TLSVerifyClient never');
   end;
end;
try
   l.SaveToFile(SLAPD_CONF_PATH());
except
      logs.Syslogs('slapd: [INFO] server Fatal error while writing configuration file "'+SLAPD_CONF_PATH()+'"');
      exit;
end;

l.free;
logs.debuglogs('slapd: [INFO] server success writing settings...');
end;
//##############################################################################
function topenldap.FindModulepath(modulelib:string):string;
var
   l:TstringList;
   i:integer;

begin
 l:=TstringList.Create;
 l.Add('/usr/lib/ldap');
 l.Add('/usr/lib/openldap/modules');
 l.Add('/usr/lib/openldap');
 l.Add('/usr/lib64/openldap');

 for i:=0 to l.COunt-1 do begin
     if FileExists(l.Strings[i]+'/'+modulelib) then begin
        result:=l.Strings[i]+'/'+modulelib;
        break;
     end;
end;

l.free;

end;

function topenldap.FIND_USERID():string;
var
   RegExpr:TRegExpr;
   l:TstringList;
   i:Integer;
   user:string;
   group:string;

begin
 l:=TStringList.Create;
  RegExpr:=TRegExpr.Create;

 if FileExists('/etc/default/slapd') then begin

 l.LoadFromFile('/etc/default/slapd');

 For i:=0 to l.Count-1 do begin
      RegExpr.Expression:='^SLAPD_USER="(.+?)"';
      if RegExpr.Exec(l.Strings[i]) then user:=RegExpr.Match[1];

      RegExpr.Expression:='^SLAPD_GROUP="(.+?)"';
      if RegExpr.Exec(l.Strings[i]) then group:=RegExpr.Match[1];

 end;
 
 end;
 
 if length(user)>0 then exit(user+':'+group);
 l.Clear;

   if not FileExists(INITD_PATH()) then exit;
   try l.LoadFromFile(INITD_PATH()); except exit; end;
   RegExpr.Expression:='user=([a-zA-Z0-9_\-]+)';
   for i:=0 to l.count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
          user:=RegExpr.Match[1];
          break;
       end;
   end;
   
   RegExpr.free;
   l.free;
   if length(user)>0 then exit(user+':'+user);
end;
//##############################################################################


procedure topenldap.WRITE_INITD();

var l:TstringList;

begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php');
end;




function topenldap.PID_PATH():string;
var
   conffile:string;
   RegExpr:TRegExpr;
   FileData:TStringList;
   i:integer;
begin

  if FileExists('/var/run/slapd/slapd.pid') then exit('/var/run/slapd/slapd.pid');
  if FileExists('/var/run/openldap/slapd.pid') then exit('/var/run/openldap/slapd.pid');
  
  conffile:=SLAPD_CONF_PATH();
  if not FileExists(conffile) then begin
     logs.logs('LDAP_PID_PATH:: unable to stat ' + conffile);
     exit('0');
  end;

 RegExpr:=TRegExpr.Create;
  FileData:=TStringList.Create;
  FileData.LoadFromFile(conffile);
  RegExpr.Expression:='pidfile\s+(.+)';
  For i:=0 TO FileData.Count -1 do begin
      if RegExpr.Exec(FileData.Strings[i]) then begin
           result:=RegExpr.Match[1];
           break;
      end;
  end;
  

   if length(result)<5 then exit('/var/run/slapd/slapd.pid');
  
   RegExpr.free;
   FileData.free;

end;

 //##############################################################################
function topenldap.get_INFOS(key:string):string;
var value:string;
begin
try
GLOBAL_INI:=TIniFile.Create('/etc/artica-postfix/artica-postfix.conf');
value:=GLOBAL_INI.ReadString('INFOS',key,'');
result:=value;
finally
GLOBAL_INI.Free;
end;

end;
//#############################################################################
function topenldap.get_LDAP(key:string):string;
var
   value:string;
   Ini:TMemIniFile;

begin


if DirectoryExists('/etc/artica-postfix/ldap_settings') then begin
   if FileExists('/etc/artica-postfix/ldap_settings/'+key) then begin
      result:=trim(logs.ReadFromFile('/etc/artica-postfix/ldap_settings/'+key));
      exit;
   end;
end;

if FileExists('/etc/artica-postfix/artica-postfix-ldap.conf') then begin
   try
      Ini:=TMemIniFile.Create('/etc/artica-postfix/artica-postfix-ldap.conf');
      except
      exit;
   end;
   value:=trim(Ini.ReadString('LDAP',key,''));
   Ini.Free;
end;
if length(trim(value))=0 then begin
 if FileExists('/etc/artica-postfix/artica-postfix-ldap.bak.conf') then begin
    try
       Ini:=TMemIniFile.Create('/etc/artica-postfix/artica-postfix-ldap.bak.conf');
     except
      exit;
    end;
    value:=Ini.ReadString('LDAP',key,'');
    Ini.Free;
    if length(value)>0 then begin
       set_LDAP(key,value);
       result:=value;
       exit;
    end;
  end;


    
    if key='admin' then begin
      value:=get_LDAP_ADMIN();
      if length(value)>0 then begin
         set_LDAP(key,value);
         result:=value;
         exit;
       end;
     end;
     
    if key='password' then begin
      value:=get_LDAP_PASSWORD();
      if length(value)>0 then begin
         set_LDAP(key,value);
         result:=value;
         exit;
       end;
     end;
     
    if key='suffix' then begin
      value:=get_LDAP_suffix();
      if length(value)>0 then begin
         set_LDAP(key,value);
         result:=value;
         exit;
       end;
     end;


     if key='server' then begin
      if length(trim(result))=0 then result:='127.0.0.1';
    end;

end;

result:=value;

end;
//#############################################################################
function topenldap.get_LDAP_ADMIN():string;
var
   RegExpr:TRegExpr;
   l:TstringList;
   i:integer;

begin
  if not FileExists(SLAPD_CONF_PATH()) then exit;
  RegExpr:=TRegExpr.Create;
  l:=TstringList.Create;
  TRY
  l.LoadFromFile(SLAPD_CONF_PATH());
  RegExpr.Expression:='rootdn\s+"cn=(.+?),';
  for i:=0 to l.Count-1 do begin
      if  RegExpr.Exec(l.Strings[i]) then begin
             result:=trim(RegExpr.Match[1]);
             break;
      end;
  end;
  FINALLY
   l.free;
   RegExpr.free;
  END;
end;
//#############################################################################
function topenldap.get_LDAP_PASSWORD():string;
var
   RegExpr:TRegExpr;
   l:TstringList;
   i:integer;

begin
  if not FileExists(SLAPD_CONF_PATH()) then exit;
  RegExpr:=TRegExpr.Create;
  l:=TstringList.Create;
  TRY
  l.LoadFromFile(SLAPD_CONF_PATH());
  RegExpr.Expression:='rootpw\s+(.+)';
  for i:=0 to l.Count-1 do begin
      if  RegExpr.Exec(l.Strings[i]) then begin
             result:=trim(RegExpr.Match[1]);
             result:=AnsiReplaceText(result,'"','');
             result:=AnsiReplaceText(result,'"','');
             break;
      end;
  end;
  FINALLY
   l.free;
   RegExpr.free;
  END;
end;
//#############################################################################
function topenldap.get_LDAP_suffix():string;
var
   RegExpr:TRegExpr;
   l:TstringList;
   i:integer;

begin
  if not FileExists(SLAPD_CONF_PATH()) then exit;
  RegExpr:=TRegExpr.Create;
  l:=TstringList.Create;
  TRY
  l.LoadFromFile(SLAPD_CONF_PATH());
  RegExpr.Expression:='^suffix\s+(.+)';
  for i:=0 to l.Count-1 do begin
      if  RegExpr.Exec(l.Strings[i]) then begin
             result:=trim(RegExpr.Match[1]);
             result:=AnsiReplaceText(result,'"','');
             result:=AnsiReplaceText(result,'"','');
             break;
      end;
  end;
  FINALLY
   l.free;
   RegExpr.free;
  END;
end;
//#############################################################################

function topenldap.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
var
   i:integer;
   s:string;
   RegExpr:TRegExpr;

begin
 result:=false;
 s:='';
 if ParamCount>1 then begin
     for i:=2 to ParamCount do begin
        s:=s  + ' ' +ParamStr(i);
     end;
 end;
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:=FoundWhatPattern;
   if RegExpr.Exec(s) then begin
      RegExpr.Free;
      result:=True;
   end;


end;
//##############################################################################

function topenldap.ReadFileIntoString(path:string):string;
var
   List:TstringList;
begin

      if not FileExists(path) then begin
        exit;
      end;

      List:=Tstringlist.Create;
      List.LoadFromFile(path);
      result:=trim(List.Text);
      List.Free;
end;
//##############################################################################
procedure topenldap.set_LDAP(key:string;val:string);
var ini:TIniFile;
begin

if ForceDirectories('/etc/artica-postfix/ldap_settings') then begin
      try
         LOGS.logs('topenldap.set_LDAP: adding informations ' + key + ' ' + val + ' in /etc/artica-postfix/ldap_settings/'+key);
         logs.WriteToFile(val,'/etc/artica-postfix/ldap_settings/'+key);
      except
          logs.Debuglogs('topenldap.set_LDAP unable to write file /etc/artica-postfix/ldap_settings/'+key );
      end;
end;


LOGS.logs('topenldap.set_LDAP: adding informations ' + key + ' ' + val + ' in /etc/artica-postfix/artica-postfix-ldap.conf');
try
   ini:=TIniFile.Create('/etc/artica-postfix/artica-postfix-ldap.conf');
   ini.WriteString('LDAP',key,val);
   ini.Free;

   ini:=TIniFile.Create('/etc/artica-postfix/artica-postfix-ldap.bak.conf');
   ini.WriteString('LDAP',key,val);
   ini.Free;
except
  logs.Debuglogs('topenldap.set_LDAP unable to write file in old settings files method');
end;

end;
//#############################################################################
procedure topenldap.ChangeSettings(server_name:string;port:string;username:string;password:string;suffix:string;ChangeSlapd:string);
var
   nohup:string;
begin
 Set_LDAP('admin',username);
 Set_LDAP('password',password);
 Set_LDAP('suffix',suffix);
 Set_LDAP('server',server_name);
 Set_LDAP('port',port);
 logs.DeleteFile('/etc/artica-postfix/no-ldap-change');
 SAVE_SLAPD_CONF();
 fpsystem('/usr/share/artica-postfix/bin/process1 --checkout --force '+ logs.DateTimeNowSQL());
 LDAP_STOP();
 LDAP_START();
 nohup:=SYS.LOCATE_GENERIC_BIN('nohup');
 fpsystem(trim(nohup+' '+ SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.change.password.php >/dev/null 2>&1 &'));
end;
//#############################################################################
function topenldap.CREATE_CERTIFICATE():boolean;
var
   openssl:string;
   cf_path:string;
   cmd:string;
   l:TstringList;
   rebuild:boolean;
   i:integer;
   CertificateMaxDays:string;
   extensions:string;

begin

CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';
rebuild:=false;
l:=TstringList.Create;


l.add('ca.crt');
l.add('ldap.crt');
l.add('ldap.key');

 for i:=0 to l.Count-1 do begin
     if Not FileExists('/etc/ssl/certs/openldap/' +l.Strings[i]) then begin
        rebuild:=true;
        break;
     end else begin
         logs.Debuglogs('slapd: [INFO] /etc/ssl/certs/openldap/' +l.Strings[i]+' OK');
     end;
 end;



if rebuild then begin
    SYS.OPENSSL_CERTIFCATE_CONFIG();
    openssl:=SYS.OPENSSL_TOOL_PATH();
    cf_path:=SYS.OPENSSL_CONFIGURATION_PATH();

if length(SYS.OPENSSL_CERTIFCATE_HOSTS())>0 then extensions:=' -extensions HOSTS_ADDONS ';


 if not FileExists(openssl) then begin
    logs.Syslogs('topenldap.CREATE_CERTIFICATE():: FATAL ERROR, Unable to stat openssl ');
    exit;
 end;


 if not FileExists(cf_path) then begin
    logs.logs('topenldap.CREATE_CERTIFICATE():: FATAL ERROR, Unable to stat openssl configuration file');
    exit;
 end;

 forcedirectories('/etc/ssl/certs/openldap');
 logs.Debuglogs('topenldap.CREATE_CERTIFICATE():: using '+cf_path+' has configuration file');
 forcedirectories('/opt/artica/tmp');


   cmd:=openssl+' req -new -config '+cf_path+extensions+' -x509 -nodes -keyout /etc/ssl/certs/openldap/ldap.key -out /etc/ssl/certs/openldap/ldap.crt';
   logs.Debuglogs(cmd);
   fpsystem(cmd);
   cmd:=openssl+' genrsa -des3 -passout pass:secret -out /etc/ssl/certs/openldap/ca.key 1024';
   logs.Debuglogs(cmd);
   fpsystem(cmd);


   cmd:=openssl+' rsa -in /etc/ssl/certs/openldap/ca.key -passin pass:secret -out /etc/ssl/certs/openldap/ca.key';
   logs.Debuglogs(cmd);
   fpsystem(cmd);

   cmd:=openssl+' req -new -config '+cf_path+extensions+' -x509 -days '+CertificateMaxDays+' -key /etc/ssl/certs/openldap/ca.key -out /etc/ssl/certs/openldap/ca.crt';
   logs.Debuglogs(cmd);
   fpsystem(cmd);

end;



 for i:=0 to l.Count-1 do begin
     if Not FileExists('/etc/ssl/certs/openldap/' +l.Strings[i]) then begin
        logs.Debuglogs('slapd: [INFO] /etc/ssl/certs/openldap/' +l.Strings[i]+' Failed');
        exit;
     end;
 end;

result:=true;



end;
//#############################################################################









end.
