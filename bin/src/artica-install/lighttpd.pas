unit lighttpd;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface
                                                              
uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,awstats,mailmanctl,tcpip,mysql_daemon,zarafa_server,backuppc,memcached;

type
  TStringDynArray = array of string;

  type
  Tlighttpd=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     awstats:tawstats;
     pid_root_path:string;
     mem_pid:string;
     lighttpd_modules:Tstringlist;
     mem_binpath:string;


    function    Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;



    function    ActiveIP():string;
    function    APACHE_ENABLED():string;
    procedure   IS_CGI_SPAWNED();


    function    roundcube_main_folder():string;
    function    lighttpd_modules_path():string;


public
    EnableLighttpd:integer;
    InsufficentRessources:boolean;
    DisableEaccelerator:integer;
    procedure   Free;

    constructor Create(const zSYS:Tsystem);
    procedure   LIGHTTPD_START(notroubleshoot:boolean=false);
    function    LIGHTTPD_BIN_PATH():string;
    function    LIGHTTPD_INITD():string;
    function    LIGHTTPD_LOG_PATH():string;
    function    LIGHTTPD_SOCKET_PATH():string;

    function    LIGHTTPD_GET_USER():string;
    function    LIGHTTPD_CONF_PATH:string;
    procedure   LIGHTTPD_CERTIFICATE();
    function    LIGHTTPD_PID_PATH():string;
    procedure   LIGHTTPD_STOP();
    function    LIGHTTPD_VERSION():string;
    procedure   LIGHTTPD_ADD_INCLUDE_PATH();
    procedure   LIGHTTPD_VERIF_CONFIG();
    procedure   CLEAN_PHP5_SESSIONS();
    procedure   TROUBLESHOTLIGHTTPD();
    function    lighttpd_server_key(key:string):string;




    procedure   PHPMYADMIN();


    FUNCTION    PHP5_CHECK_EXTENSIONS():string;
    FUNCTION    STATUS():string;
    function    PHP5_CGI_BIN_PATH():string;
    function    CACHE_STATUS:string;
    function    LIGHTTPD_LISTEN_PORT():string;
    function    LIGHTTPD_CERTIFICATE_PATH():string;

    procedure   CHANGE_INIT();

    FUNCTION    IS_IPTABLES_INPUT_RULES():boolean;
    procedure   CreateWebFolders();
    function    MON():string;


END;

implementation

constructor tlighttpd.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       forcedirectories('/opt/artica/tmp');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       EnableLighttpd:=1;
       awstats:=tawstats.Create(SYS);
       InsufficentRessources:=SYS.ISMemoryHiger1G();
       DisableEaccelerator:=0;
       lighttpd_modules:=Tstringlist.Create;


       if not TryStrToInt(SYS.GET_INFO('DisableEaccelerator'),DisableEaccelerator) then DisableEaccelerator:=0;

       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tlighttpd.free();
begin
    logs.Free;
    SYS.Free;
end;
//##############################################################################
function Tlighttpd.LIGHTTPD_BIN_PATH():string;
begin
if length(mem_binpath)>2 then exit(mem_binpath);
result:=SYS.LOCATE_LIGHTTPD_BIN_PATH();
mem_binpath:=result;
end;
//##############################################################################
function Tlighttpd.PHP5_CGI_BIN_PATH():string;
begin
   if FileExists('/usr/bin/php-fcgi') then exit('/usr/bin/php-fcgi');
   if FileExists('/usr/bin/php-cgi') then exit('/usr/bin/php-cgi');
   if FileExists('/usr/local/bin/php-cgi') then exit('/usr/local/bin/php-cgi');
end;
//##############################################################################
function Tlighttpd.LIGHTTPD_INITD():string;
begin
    if FileExists('/etc/init.d/lighttpd') then exit('/etc/init.d/lighttpd');
    if FileExists('/usr/local/etc/rc.d/lighttpd') then exit('/usr/local/etc/rc.d/lighttpd');
    if FileExists('/etc/rc.d/lighttpd') then exit('/etc/rc.d/lighttpd');
end;

//##############################################################################
function Tlighttpd.LIGHTTPD_CONF_PATH:string;
begin
  if FileExists('/etc/lighttpd/lighttpd.conf') then exit('/etc/lighttpd/lighttpd.conf');
  if FileExists('/etc/lighttpd/lighttpd.conf') then exit('/etc/lighttpd/lighttpd.conf');
  if FileExists('/opt/artica/conf/lighttpd.conf') then exit('/opt/artica/conf/lighttpd.conf');
  if FileExists('/usr/local/etc/lighttpd.conf') then exit('/usr/local/etc/lighttpd.conf');
end;
//##############################################################################
function Tlighttpd.APACHE_ENABLED():string;
begin
if not FileExists(SYS.LOCATE_APACHE_BIN_PATH()) then exit('0');
if not FileExists(SYS.LOCATE_APACHE_LIBPHP5()) then exit('0');
if not FileExists(SYS.LOCATE_APACHE_MODSSLSO()) then exit('0');
if not FileExists(LIGHTTPD_BIN_PATH()) then exit('1');
result:=SYS.GET_INFO('ApacheArticaEnabled');
end;
//##############################################################################
function Tlighttpd.lighttpd_server_key(key:string):string;
var
   sourcefile:string;
   RegExpr:TRegExpr;
   l:Tstringlist;
   i:integer;
begin

sourcefile:=LIGHTTPD_CONF_PATH();
if not FileExists(sourcefile) then exit;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='server\.'+key+'.*?=.*?"(.+?)"';
l:=Tstringlist.Create;
try
   l.LoadFromFile(sourcefile);

except
      exit;
end;
For i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
      result:=RegExpr.Match[1];
      break;
   end;
end;

l.free;
RegExpr.free;
end;
//##############################################################################
procedure Tlighttpd.CLEAN_PHP5_SESSIONS();
var
   i:integer;
   php_path:string;
begin
 exit;
 php_path:=SYS.LOCATE_PHP5_SESSION_PATH();
 if not DirectoryExists(php_path) then exit;
      logs.Debuglogs('Starting......: lighttpd: Cleaning php sessions');
      SYS.DirFiles(php_path,'sess_*');
      logs.Debuglogs('Starting......: lighttpd: '+ INtTOstr(SYS.DirListFiles.Count)+' files to clean');
      for i:=0 to SYS.DirListFiles.Count-1 do begin
          logs.DeleteFile(php_path+'/'+SYS.DirListFiles.Strings[i]);
      end;



end;
//##############################################################################

function Tlighttpd.ActiveIP():string;
var
   ip:string;
   sip:ttcpip;
begin
    sip:=ttcpip.Create;
    ip:=sip.LOCAL_IP_FROM_NIC('eth0');
    if length(ip)>0 then begin
       result:=ip;
       exit;
    end;

    ip:=sip.LOCAL_IP_FROM_NIC('eth1');
    if length(ip)>0 then begin
       result:=ip;
       exit;
    end;

    ip:=sip.LOCAL_IP_FROM_NIC('eth2');
    if length(ip)>0 then begin
       result:=ip;
       exit;
    end;
end;
//##############################################################################

function Tlighttpd.LIGHTTPD_PID_PATH():string;
var
RegExpr:TRegExpr;
l:TStringList;
i:integer;
begin

if length(pid_root_path)>0 then exit(pid_root_path);

if not FileExists(LIGHTTPD_CONF_PATH()) then begin
   logs.Debuglogs('Tlighttpd.LIGHTTPD_PID_PATH:: unable to stat lighttpd.conf ' + LIGHTTPD_CONF_PATH());
   exit;
end;
l:=TstringList.Create;
try
   l.LoadFromFile(LIGHTTPD_CONF_PATH());

except
   exit;
end;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^server\.pid-file.+?"(.+?)"';
for i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
    result:=RegExpr.Match[1];
    break;
   end;
end;
   pid_root_path:=result;
   l.Free;
   RegExpr.free;
end;
//##############################################################################
function Tlighttpd.LIGHTTPD_GET_USER():string;
var
     l:TstringList;
     RegExpr:TRegExpr;
     i:integer;
     user,group:string;
begin

  user:=SYS.GET_INFO('LighttpdUserAndGroup');
  logs.Debuglogs('LIGHTTPD_GET_USER: user="'+user+'" (LighttpdUserAndGroup)');
  if length(user)>0 then begin
     user:=AnsireplaceText(user,'lighttpd:lighttpd:lighttpd','lighttpd:lighttpd');
     user:=AnsireplaceText(user,'www-data:www-data:www-data','www-data:www-data');
     result:=user;
     exit(user);
  end;

  if not FileExists(LIGHTTPD_CONF_PATH()) then exit;
  l:=TstringList.Create;
  RegExpr:=TRegExpr.Create;
  try
     l.LoadFromFile(LIGHTTPD_CONF_PATH());
except
      exit;
  end;
  for i:=0 to l.Count-1 do begin
    RegExpr.Expression:='^server\.username.+?"(.+?)"';
    if RegExpr.Exec(l.Strings[i]) then user:=RegExpr.Match[1];
    RegExpr.Expression:='^server\.groupname.+?"(.+?)"';
    if RegExpr.Exec(l.Strings[i]) then group:=RegExpr.Match[1];
  end;
  if length(user)>0 then result:=user+':'+group;
  SYS.set_INFO('LighttpdUserAndGroup',result);
  RegExpr.free;
  l.free;
end;
//##############################################################################
procedure Tlighttpd.CreateWebFolders();
var
user:string;
begin
user:=LIGHTTPD_GET_USER();
if length(user)=0 then exit;
forceDirectories('/opt/artica/share/www/jpegPhoto');
logs.OutputCmd('/bin/chown -R ' + user + ' /opt/artica/share/www/jpegPhoto');
logs.OutputCmd('/bin/chmod -R 777 /opt/artica/share/www/jpegPhoto');
end;
//##############################################################################
function Tlighttpd.CACHE_STATUS:string;
var
   sini:TiniFile;
   f:TstringList;
   run:string;
   cache:string;
begin

f:=TstringList.Create;
cache:='/etc/artica-postfix/cache.lighttpd.status';
f.Add(STATUS());
f.SaveToFile(cache);
f.free;
sini:=TiniFile.Create(cache);

run:=sini.ReadString('LIGHTTPD','running','0');

if run='1' then begin
   result:='Running...' + sini.ReadString('LIGHTTPD','master_memory','0') + ' kb mem';
end else begin
result:='Stopped...';

end;
sini.free;
end;
//##############################################################################
procedure Tlighttpd.LIGHTTPD_VERIF_CONFIG();
var
   user:string;
   group:string;
   logs_path:string;
   RegExpr:TRegExpr;

begin

    logs.Debuglogs('LIGHTTPD_VERIF_CONFIG():: Creating user www-data if does not exists');
    SYS.AddUserToGroup('www-data','www-data','','');
    CHANGE_INIT();
    logs.DeleteFile('/etc/artica-postfix/cache.global.status');


   logs_path:=LIGHTTPD_LOG_PATH();
   user:=LIGHTTPD_GET_USER();
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='(.+?):(.+)';
   if RegExpr.Exec(user) then begin
       user:=RegExpr.Match[1];
       group:=RegExpr.Match[2];
   end;
   if RegExpr.Exec(group) then group:=RegExpr.Match[1];
   forcedirectories('/opt/artica/ssl/certs');
   forcedirectories('/var/lib/php/session');
   ForceDirectories('/var/lighttpd/upload');
   ForceDirectories('/var/run/lighttpd');
   logs.Debuglogs('Starting......: lighttpd:  running as '+user+':'+group);

   logs.OutputCmd('/bin/chown -R '+user+':'+group+' /var/run/lighttpd');
   logs.OutputCmd('/bin/chown -R '+user+':'+group+' /var/lighttpd');

   logs.OutputCmd('/bin/chmod 755 /var/lib/php/session');
   logs.OutputCmd('/bin/chmod 755 /var/lighttpd/upload');
   logs.Debuglogs('Starting......: lighttpd: Saving default configuration');
   logs.Debuglogs('Starting......: lighttpd: Adding include path..');
   LIGHTTPD_ADD_INCLUDE_PATH();

   if not FileExists(LIGHTTPD_CERTIFICATE_PATH()) then begin
      logs.Debuglogs('LIGHTTPD_VERIF_CONFIG() -> LIGHTTPD_CERTIFICATE()');
      LIGHTTPD_CERTIFICATE();
   end;



          logs.Debuglogs('Starting......: lighttpd:  Checking pommo aliases');

          forcedirectories('/var/run/lighttpd');
          if length(logs_path)>0 then forcedirectories(logs_path);
          logs.Debuglogs('Starting......: lighttpd:  Checking securities on '+user+':'+group);
          logs.OutputCmd('/bin/chown -R '+user+':'+group+' /var/run/lighttpd');
          logs.OutputCmd('/bin/chown -R '+user+':'+group+' '+ logs_path);


end;

//##############################################################################
procedure Tlighttpd.LIGHTTPD_START(notroubleshoot:boolean);
begin
     fpsystem('/etc/init.d/artica-webconsole start');
end;

//##############################################################################
procedure Tlighttpd.PHPMYADMIN();
begin
     fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.lighttpd.php --phpmyadmin');

end;
//##############################################################################



procedure Tlighttpd.IS_CGI_SPAWNED();
var

   tmpstr:string;
   l:Tstringlist;
   RegExpr:TRegExpr;
   i:integer;
begin
    if not FileExists('/var/log/lighttpd/error.log') then begin
       logs.Debuglogs('Starting......: lighttpd: unable to stat /var/log/lighttpd/error.log (line 454)');
       exit;
    end;
    sleep(1000);
    tmpstr:=logs.FILE_TEMP();
    fpsystem('tail -n 2 /var/log/lighttpd/error.log >'+tmpstr +' 2>&1');
    if not fileExists(tmpstr) then begin
       logs.Debuglogs('Starting......: lighttpd: unable to stat '+tmpstr+' (line 461)');
       exit;
    end;

    logs.Debuglogs('Starting......: lighttpd: testing if cgi is spawned');

    l:=Tstringlist.Create;
    l.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='mod_fastcgi.+?spawning fcgi failed';
    for i:=0 to l.Count-1 do begin
        if RegExpr.Exec(l.Strings[i]) then begin
            logs.Debuglogs('Starting......: lighttpd: spawning fcgi failed !!');
            logs.Debuglogs('Starting......: lighttpd: '+l.Strings[i]);
            if SYS.PROCESS_EXIST(SYS.PIDOF('artica-make')) then begin
               logs.Debuglogs('Starting......: lighttpd: stopping artica-make already running');
               exit;
            end;
            if FIleExists('/usr/share/artica-postfix/ressources/install/APP_PHP.time') then begin
               if SYS.FILE_TIME_BETWEEN_MIN('/usr/share/artica-postfix/ressources/install/APP_PHP.time')<120 then begin
                    logs.Debuglogs('Starting......: lighttpd: need more than 60mn to restart operation');
                    exit;
               end;
            end;

            logs.NOTIFICATION('spawning fcgi failed!','lighttpd could not start.It seems that fcgi is not properly installed, Artica will try to install php5 using compilation mode','system');
            logs.DeleteFile('/usr/share/artica-postfix/ressources/install/APP_PHP.time');
            fpsystem('/usr/share/artica-postfix/bin/artica-make APP_PHP &');
            halt(0);
        end;
    end;
    l.free;
    RegExpr.Free;

end;

//##############################################################################
procedure Tlighttpd.TROUBLESHOTLIGHTTPD();
var
   cmd:string;
   tmpstr,port:string;
   l:Tstringlist;
   RegExpr:TRegExpr;
   i:integer;
begin
logs.Debuglogs('Starting......: lighttpd: Try to understand why is doesn''t start');
tmpstr:=logs.FILE_TEMP();
cmd:=LIGHTTPD_BIN_PATH()+ ' -f /etc/lighttpd/lighttpd.conf >' +tmpstr +' 2>&1';
fpsystem(cmd);
// SSL: Private key does not match the certificate public key
if not FileExists(tmpstr) then begin
        logs.Debuglogs('Starting......: lighttpd: could not stat '+ tmpstr);
        exit;
end;

l:=Tstringlist.Create;
l.LoadFromFile(tmpstr);
logs.DeleteFile(tmpstr);
RegExpr:=TRegExpr.Create;
for i:=0 to l.Count-1 do begin
    RegExpr.Expression:='SSL.+?Private key does not match the certificate public';

    if RegExpr.Exec(l.Strings[i]) then begin
        logs.Debuglogs('Starting......: lighttpd: detecting SSL key error generate new certificat');
        LIGHTTPD_CERTIFICATE();
        LIGHTTPD_START(true);
        break;
    end;

    RegExpr.Expression:='can.+?find username\s+';
    if RegExpr.Exec(l.Strings[i]) then begin
        logs.Debuglogs('Starting......: lighttpd: detecting username error generate new configuration file');
        LIGHTTPD_START(true);
        break;
    end;

    RegExpr.Expression:='can.+?t bind to port:\s+([0-9]+)\s+Address already in use';
    if RegExpr.Exec(l.Strings[i]) then begin
       port:=RegExpr.Match[1];
       tmpstr:=SYS.WHO_LISTEN_PORT(port);
       logs.Debuglogs('Starting......: lighttpd: Another process already using Port: "' + port+'" ('+tmpstr+')');
       RegExpr.Expression:='Pid:([0-9]+);';
       if  RegExpr.Exec(tmpstr) then begin
           logs.Debuglogs('Starting......: lighttpd: kill process Pid:'+tmpstr);
           fpsystem('/bin/kill -9 '+RegExpr.Match[1]);
            LIGHTTPD_START(true);
            break;
       end;

    end;

       RegExpr.Expression:='network.+?SSL.+?error';
       if  RegExpr.Exec(l.Strings[i]) then begin
           logs.Debuglogs('Starting......: lighttpd: FATAL Bug in lighttpd (especially in CentOS 5.4), turn to Apache mode');
           logs.Debuglogs('Starting......: lighttpd: '+l.Strings[i]);
           SYS.set_INFO('ApacheArticaEnabled','1');
           halt(0);
           break;
       end;





    logs.Debuglogs('Starting......: lighttpd: no error found in "'+l.Strings[i]+'"');

end;

 RegExpr.free;
 l.free;


end;
//##############################################################################


function Tlighttpd.MON():string;
var
l:TstringList;
begin
l:=TstringList.Create;
l.ADD('check process '+ExtractFileName(LIGHTTPD_BIN_PATH())+' with pidfile '+LIGHTTPD_PID_PATH());
l.ADD('group lighttpd');
l.ADD('start program = "/etc/init.d/artica-webconsole start"');
l.ADD('stop program = "/etc/init.d/artica-webconsole stop"');
l.ADD('if 5 restarts within 5 cycles then timeout');
result:=l.Text;
l.free;
end;
procedure Tlighttpd.LIGHTTPD_STOP();
begin
  if not FileExists('/etc/init.d/artica-webconsole') then  fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php --artica-web >/dev/null 2>&1');
  fpsystem('/etc/init.d/artica-webconsole restart');
end;
//##############################################################################
procedure Tlighttpd.CHANGE_INIT();
var
l:TstringList;
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php --artica-web >/dev/null 2>&1');
end;
//##############################################################################
FUNCTION Tlighttpd.IS_IPTABLES_INPUT_RULES():boolean;
var
   tmpstr:string;
     l:TstringList;
     RegExpr:TRegExpr;
     i:integer;
begin
    result:=false;
    if not FileExists(SYS.LOCATE_IPTABLES()) then begin
         logs.Debuglogs('Starting......: lighttpd: IpTables is not installed');
         exit;
    end;
tmpstr:=LOGS.FILE_TEMP();
fpsystem(SYS.LOCATE_IPTABLES() + ' -L INPUT >'+tmpstr+' 2>&1');
if not FileExists(tmpstr) then exit;
l:=TstringList.Create;
l.LoadFromFile(tmpstr);
logs.DeleteFile(tmpstr);
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^REJECT\s+';
for i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
      result:=true;
      break;
   end;
end;
RegExpr.free;
l.free;
end;
//##############################################################################
FUNCTION Tlighttpd.STATUS():string;
var
   pidpath:string;
begin
SYS.MONIT_DELETE('APP_LIGHTTPD');
if not FileExists(LIGHTTPD_BIN_PATH()) then exit;
pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --lighttpd >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);
end;
//#########################################################################################
procedure Tlighttpd.LIGHTTPD_CERTIFICATE();
var
   cmd:string;
   openssl_path:string;
   CertificateMaxDays:string;
   extensions:string;
begin
openssl_path:=SYS.LOCATE_OPENSSL_TOOL_PATH();
SYS.OPENSSL_CERTIFCATE_CONFIG();

    CertificateMaxDays:=SYS.GET_INFO('CertificateMaxDays');
    if length(CertificateMaxDays)=0 then CertificateMaxDays:='730';

if Not FileExists('/etc/artica-postfix/ssl.certificate.conf') then begin
   logs.Debuglogs('LIGHTTPD_CERTIFICATE():: unable to stat /etc/artica-postfix/ssl.certificate.conf');
   logs.Debuglogs('Starting......: lighttpd: unable to stat default certificate infos');
   exit;
end;
if length(SYS.OPENSSL_CERTIFCATE_HOSTS())>0 then extensions:=' -extensions HOSTS_ADDONS ';



logs.Debuglogs('Starting......: lighttpd: Creating certificate using /etc/artica-postfix/ssl.certificate.conf');
forcedirectories('/opt/artica/ssl/certs');
cmd:=openssl_path+' req -new -passin pass:artica -x509 -batch -config /etc/artica-postfix/ssl.certificate.conf '+extensions+'-keyout /opt/artica/ssl/certs/lighttpd.pem -out /opt/artica/ssl/certs/lighttpd.pem -days '+CertificateMaxDays+' -nodes';
logs.OutputCmd(cmd);
end;

//#########################################################################################
function Tlighttpd.LIGHTTPD_VERSION():string;
var
     l:TstringList;
     RegExpr:TRegExpr;
     i:integer;
     tmpstr:string;
     D:boolean;
     cmd:string;
begin
    if not FileExists(LIGHTTPD_BIN_PATH()) then exit;
    D:=SYS.COMMANDLINE_PARAMETERS('--verbose');
    result:=SYS.GET_CACHE_VERSION('APP_LIGHTTPD');
    if length(result)>2 then exit;
    tmpstr:=logs.FILE_TEMP();
    cmd:=LIGHTTPD_BIN_PATH()+' -v >'+tmpstr+' 2>&1';
    if D then writeln(cmd);

    fpsystem(cmd);
    if not FileExists(tmpstr) then exit;
    l:=TStringList.Create;
    l.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    RegExpr:=TRegExpr.Create;

    For i:=0 to l.Count-1 do begin
        RegExpr.Expression:='lighttpd-([0-9\.]+)';
        if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            logs.Debuglogs('LIGHTTPD_VERSION:: ' + result);
        end;

        RegExpr.Expression:='lighttpd\/([0-9\.]+)';
        if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            logs.Debuglogs('LIGHTTPD_VERSION:: ' + result);
        end;

    end;

    SYS.SET_CACHE_VERSION('APP_LIGHTTPD',result);

    l.free;
    RegExpr.Free;
end;
//##############################################################################


function Tlighttpd.LIGHTTPD_LOG_PATH():string;
var
RegExpr:TRegExpr;
l:TStringList;
i:integer;
begin


if not FileExists(LIGHTTPD_CONF_PATH()) then begin
   logs.Debuglogs('LIGHTTPD_LOG_PATH:: unable to stat lighttpd.conf');
   exit;
end;
l:=TstringList.Create;
try
   l.LoadFromFile(LIGHTTPD_CONF_PATH());
except
   result:='/var/log/lighttpd';
   exit;
end;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^server\.errorlog.+?"(.+?)"';

for i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
    result:=RegExpr.Match[1];
    break;
   end;
end;

   result:=ExtractFilePath(result);
   if Copy(result,length(result),1)='/' then result:=Copy(result,1,length(result)-1);
   l.Free;
   RegExpr.free;

end;
//##############################################################################
function Tlighttpd.LIGHTTPD_CERTIFICATE_PATH():string;
var
RegExpr:TRegExpr;
l:TStringList;
i:integer;
begin


if not FileExists(LIGHTTPD_CONF_PATH()) then begin
   logs.Debuglogs('LIGHTTPD_LOG_PATH:: unable to stat lighttpd.conf');
   exit;
end;
l:=TstringList.Create;
try
   l.LoadFromFile(LIGHTTPD_CONF_PATH());

except
  exit;
end;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^ssl\.pemfile.+?"(.+?)"';

for i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
    result:=RegExpr.Match[1];
    break;
   end;
end;
end;
//##############################################################################


function Tlighttpd.LIGHTTPD_LISTEN_PORT():string;
var
RegExpr:TRegExpr;
l:TStringList;
i:integer;
begin
if not FileExists(LIGHTTPD_CONF_PATH()) then begin
   logs.logs('LIGHTTPD_LISTEN_PORT:: unable to stat lighttpd.conf');
   exit;
end;
l:=TstringList.Create;
try
l.LoadFromFile(LIGHTTPD_CONF_PATH());

except
exit;
end;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^server\.port.+?=.+?([0-9]+)';
for i:=0 to l.Count-1 do begin

   if RegExpr.Exec(l.Strings[i]) then begin
   result:=RegExpr.Match[1];
   break;
   end;
end;

   RegExpr.Free;
   l.free;

end;
//##############################################################################
function Tlighttpd.LIGHTTPD_SOCKET_PATH():string;
var

RegExpr:TRegExpr;
l:TStringList;
i:integer;

begin

if not FileExists(LIGHTTPD_CONF_PATH()) then begin
   logs.Debuglogs('LIGHTTPD_SOCKET_PATH:: unable to stat lighttpd.conf');
   exit;
end;
l:=TstringList.Create;
try
   l.LoadFromFile(LIGHTTPD_CONF_PATH());

except
  exit
end;
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='\s+"socket".+?"(.+?)"';
for i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
    result:=RegExpr.Match[1];
    break;
   end;
end;
   result:=ExtractFilePath(result);
   if Copy(result,length(result),1)='/' then result:=Copy(result,1,length(result)-1);
   l.Free;
   RegExpr.free;

end;
//##############################################################################
procedure Tlighttpd.LIGHTTPD_ADD_INCLUDE_PATH();
var
   l  :TstringList;
   t  :Tstringlist;
   i:integer;
   timezone:string;
   mysql:tmysql_daemon;
   mysql_socket:string;
   php5FuncOverloadSeven:integer;
   php5DisableMagicQuotesGpc:integer;
   php5UploadMaxFileSize:integer;
   ApcEnabledInPhp:integer;
   php5DefaultCharset:string;
   zarafa:tzarafa_server;
   UseSamePHPMysqlCredentials,PHPDefaultMysqlserverPort,ZarafaSessionTime:integer;
   PHPDefaultMysqlserver,PHPDefaultMysqlRoot,PHPDefaultMysqlPass:string;
   php5PostMaxSize:integer;
   php5MemoryLimit:integer;
begin

if not TryStrToInt(SYS.GET_INFO('php5DisableMagicQuotesGpc'),php5DisableMagicQuotesGpc) then php5DisableMagicQuotesGpc:=0;
if not TryStrToInt(SYS.GET_INFO('php5FuncOverloadSeven'),php5FuncOverloadSeven) then php5FuncOverloadSeven:=0;
if not TryStrToInt(sys.GET_INFO('ApcEnabledInPhp'),ApcEnabledInPhp) then ApcEnabledInPhp:=0;
if not TryStrToInt(sys.GET_INFO('UseSamePHPMysqlCredentials'),UseSamePHPMysqlCredentials) then UseSamePHPMysqlCredentials:=1;
if not TryStrToInt(sys.GET_INFO('PHPDefaultMysqlserverPort'),PHPDefaultMysqlserverPort) then PHPDefaultMysqlserverPort:=3306;
if not TryStrToInt(sys.GET_INFO('ZarafaSessionTime'),ZarafaSessionTime) then ZarafaSessionTime:=1440;
if not TryStrToInt(sys.GET_INFO('php5PostMaxSize'),php5PostMaxSize) then php5PostMaxSize:=128;
if not TryStrToInt(sys.GET_INFO('php5MemoryLimit'),php5MemoryLimit) then php5MemoryLimit:=500;




PHPDefaultMysqlRoot:=SYS.GET_INFO('PHPDefaultMysqlRoot');
PHPDefaultMysqlserver:=SYS.GET_INFO('PHPDefaultMysqlserver');
if PHPDefaultMysqlserver='localhost' then PHPDefaultMysqlserver:='127.0.0.1';
if length(PHPDefaultMysqlserver)=0 then PHPDefaultMysqlserver:='127.0.0.1';
PHPDefaultMysqlPass:=SYS.GET_INFO('PHPDefaultMysqlPass');

php5DefaultCharset:=trim(sys.GET_INFO('php5DefaultCharset'));
if length(php5DefaultCharset)=0 then php5DefaultCharset:='utf-8';

  forceDirectories('/var/lib/php5');
  mysql:=tmysql_daemon.Create(SYS);
  mysql_socket:=mysql.SERVER_PARAMETERS('socket');
  l:=Tstringlist.Create;
  timezone:=SYS.GET_INFO('timezones');
  if length(trim(timezone))=0 then timezone:='Europe/Berlin';
l.Add('[PHP]');
l.Add('safe_mode = Off');
l.Add('safe_mode_gid = Off');
l.Add('short_open_tag = On');
l.Add('engine = On');
l.Add('precision    =  12');
l.Add('y2k_compliance = On');
l.Add('output_buffering = 4096');
l.Add('enable_dl = On');
l.Add('serialize_precision = 100');
l.Add('disable_functions =');
l.Add('disable_classes =');
l.Add('expose_php = Off');
l.Add('max_execution_time = 3600');
l.Add('max_input_time = 3600');
l.Add('memory_limit = '+IntToStr(php5MemoryLimit)+'M');
l.Add('error_reporting  =  E_ALL & ~E_NOTICE');
l.Add('display_errors = Off');
l.Add('display_startup_errors = Off');
l.Add('log_errors = On');
l.Add('log_errors_max_len = 2048');
l.Add('ignore_repeated_errors = Off');
l.Add('ignore_repeated_source = Off');
l.Add('report_memleaks = On');
l.Add('track_errors = Off');
l.Add('error_prepend_string = "<font color=ff0000><code style=''font-size:12px''>"');
l.Add('error_append_string = "</code></font><br>"');
l.Add('html_errors = false');
l.Add('error_log = /usr/share/artica-postfix/ressources/logs/php.log');
l.Add('variables_order = "EGPCS"');
l.Add('register_argc_argv = On');
l.Add('auto_globals_jit = On');
l.Add('post_max_size = '+IntTOStr(php5PostMaxSize)+'M');
l.Add('auto_prepend_file =');
l.Add('auto_append_file =');
l.Add('default_mimetype = "text/html"');
l.Add('default_charset = "'+php5DefaultCharset+'"');
l.Add('unicode.semantics = off');
l.Add('unicode.runtime_encoding = utf-8');
l.Add('unicode.script_encoding = utf-8');
l.Add('unicode.output_encoding = utf-8');
l.Add('unicode.from_error_mode = U_INVALID_SUBSTITUTE');
l.Add('unicode.from_error_subst_char = 3f');
l.Add('include_path = ".:/usr/share/php:/usr/share/obm:/usr/share/php5:/usr/share/obm2:/usr/local/share/php:/usr/share/artica-postfix/ressources/externals:/usr/share/artica-postfix/ressources/externals/Gdata:/usr/share/php5/PEAR:/usr/share/pear"');
l.Add('doc_root =');
l.Add('user_dir =');
l.Add('extension_dir = "'+ SYS.LOCATE_PHP5_EXTENSION_DIR()+'"');
l.Add('cgi.force_redirect = 1');
l.Add('cgi.fix_pathinfo = 1');
l.Add('file_uploads = On');
l.Add('upload_tmp_dir =/var/lighttpd/upload');


if not tryStrToint(SYS.GET_INFO('php5UploadMaxFileSize'),php5UploadMaxFileSize) then begin
   php5UploadMaxFileSize:=256;
   SYS.set_INFO('php5UploadMaxFileSize','256');
end;

logs.Debuglogs('Starting......: lighttpd: Max upload size set to '+IntToStr(php5UploadMaxFileSize)+'M');

l.Add('upload_max_filesize = '+IntToStr(php5UploadMaxFileSize)+'M');
l.Add('allow_url_fopen = On');
l.Add('allow_url_include = Off');
l.Add('from="anonymous@anonymous.com"');
l.Add('default_socket_timeout = 60');
l.Add('safe_mode = Off');
if php5FuncOverloadSeven=1 then begin
   if DirectoryExists(roundcube_main_folder()) then begin
      logs.Debuglogs('Starting......: lighttpd: Warning, mbstring.func_overload is enabled to 7');
      logs.Debuglogs('Starting......: lighttpd: But RoundCube require 0, switch to 0');
      l.Add('mbstring.func_overload = 0');
   end else begin
      l.add('mbstring.func_overload = 7');
   end;
end;
if php5DisableMagicQuotesGpc=1 then begin
   l.add('magic_quotes_gpc = Off');
end;

if FileExists('/usr/local/ioncube/ioncube_loader_lin_5.2.so') then begin
l.add('zend_extension=/usr/local/ioncube/ioncube_loader_lin_5.2.so');
end;

if FileExists('/usr/lib/libxapian.so.22') then begin
    if FileExists('/usr/lib/sse2/libxapian.so.22') then fpsystem(SYS.LOCATE_GENERIC_BIN('mv')+' /usr/lib/sse2/libxapian.so.22 /usr/lib/sse2/libxapian-back.so.22');
end;



l.Add('');
l.Add('[Date]');
l.add('date.timezone = "'+timezone+'"');
l.Add('');
l.Add('[filter]');
l.Add('[iconv]');
l.Add('iconv.input_encoding = utf-8');
l.Add('iconv.internal_encoding = utf-8');
l.Add('iconv.output_encoding = utf-8');
l.Add('[Syslog]');
l.Add('define_syslog_variables  = Off');
l.Add('');
l.Add('[mail function]');
l.Add('[SQL]');
l.Add('sql.safe_mode = Off');
l.Add('');
l.Add('[ODBC]');
l.Add('odbc.allow_persistent = On');
l.Add('odbc.check_persistent = On');
l.Add('odbc.max_persistent = -1');
l.Add('odbc.max_links = -1');
l.Add('odbc.defaultlrl = 4096');
l.Add('odbc.defaultbinmode = 1');
l.Add('');

if UseSamePHPMysqlCredentials=1 then begin
   if not TryStrToInt(SYS.GET_MYSQL('port'),PHPDefaultMysqlserverPort) then PHPDefaultMysqlserverPort:=3306;
   PHPDefaultMysqlserver:=SYS.GET_MYSQL('mysql_server');
   PHPDefaultMysqlRoot:=SYS.GET_MYSQL('database_admin');
   PHPDefaultMysqlPass:=SYS.GET_MYSQL('database_password');
end;

logs.Debuglogs('Starting......: lighttpd: Default mysql settings to "'+PHPDefaultMysqlRoot+'@'+PHPDefaultMysqlserver+':'+intToStr(PHPDefaultMysqlserverPort));

l.Add('[MySQL]');
l.Add('mysql.allow_persistent = On');
l.Add('mysql.max_persistent = -1');
l.Add('mysql.max_links = -1');
l.Add('mysql.default_port ='+IntToStr(PHPDefaultMysqlserverPort));
l.Add('mysql.default_socket ="'+mysql_socket+'"');
l.Add('mysql.default_host ='+PHPDefaultMysqlserver);
l.Add('mysql.default_user ='+PHPDefaultMysqlRoot);
l.Add('mysql.connect_timeout = 60');
l.Add('mysql.trace_mode = Off');
l.Add('[LDAP]');
l.Add('ldap.max_links = -1');
l.Add('ldap.allow_persistent = On');
l.Add('ldap.check_persistent = On');
l.Add('');
l.Add('[MySQLi]');
l.Add('mysqli.max_links = -1');
l.Add('mysqli.default_port = '+IntToStr(PHPDefaultMysqlserverPort));
l.Add('mysqli.default_socket ="'+mysql_socket+'"');
l.Add('mysqli.default_host ='+PHPDefaultMysqlserver);
l.Add('mysqli.default_user ='+PHPDefaultMysqlRoot);
l.Add('mysqli.reconnect = Off');
l.Add('');
l.Add('[mSQL]');
l.Add('msql.allow_persistent = On');
l.Add('msql.max_persistent = -1');
l.Add('msql.max_links = -1');
l.Add('');
l.Add('[OCI8]');
l.Add('[PostgresSQL]');
l.Add('[Sybase]');
l.Add('[Sybase-CT]');
l.Add('[bcmath]');
l.Add('[browscap]');
l.Add('[Informix]');
l.Add('[Session]');
l.Add('session.save_handler = files');
l.Add('session.save_path = "/var/lib/php5"');
l.Add('session.use_cookies = 1');
l.Add('session.use_only_cookies = 1');
l.Add('session.name = PHPSESSID');
l.Add('session.auto_start = 0');
l.Add('session.cookie_lifetime = 0');
l.Add('session.cookie_path = /');
l.Add('session.cookie_domain =');
l.Add('session.cookie_httponly =');
l.Add('session.serialize_handler = php');
l.Add('session.gc_probability = 1');
l.Add('session.gc_divisor     = 100');

l.Add('session.gc_maxlifetime = '+IntToStr(ZarafaSessionTime));
l.Add('session.referer_check =');
l.Add('session.entropy_length = 0');
l.Add('session.entropy_file =');
l.Add('session.cache_limiter = nocache');
l.Add('session.cache_expire = 420');
l.Add('session.use_trans_sid = 0');
l.Add('session.hash_function = 0');
l.Add('session.bug_compat_warn = Off');
l.Add('session.hash_bits_per_character = 4');
l.Add('url_rewriter.tags = "a=href,area=href,frame=src,input=src,form=,fieldset="');
l.Add('');
l.Add('[MSSQL]');
l.Add('mssql.allow_persistent = On');
l.Add('mssql.max_persistent = -1');
l.Add('mssql.max_links = -1');
l.Add('mssql.min_error_severity = 10');
l.Add('mssql.min_message_severity = 10');
l.Add('mssql.compatability_mode = Off');
l.Add('mssql.connect_timeout = 5');
l.Add('mssql.timeout = 60');
l.Add('mssql.textlimit = 4096');
l.Add('mssql.textsize = 4096');
l.Add('mssql.batchsize = 0');
l.Add('mssql.datetimeconvert = On');
l.Add('mssql.secure_connection = Off');
l.Add('mssql.max_procs = -1');
l.Add('mssql.charset = "ISO-8859-1"');
l.Add('');
l.Add('[Assertion]');
l.Add('[COM]');
l.Add('[mbstring]');
l.Add('[FrontBase]');
l.Add('[gd]');
l.Add('[exif]');
l.Add('[Tidy]');
l.Add('tidy.clean_output = Off');
l.Add('');
l.Add('[soap]');
l.Add('soap.wsdl_cache_ttl=86400');
if DisableEaccelerator=0 then begin
   if fileExists(SYS.LOCATE_EACCELERATOR_SO()) then begin
      logs.DebugLogs('Starting......: Apache groupware eaccelerator.so detected');
      forceDirectories('/tmp/eaccelerator2');
      fpsystem('/bin/chmod 700 /tmp/eaccelerator2');
      fpsystem('/bin/chown www-data:www-data /tmp/eaccelerator2');
      l.add('extension="eaccelerator.so"');
      l.Add('eaccelerator.shm_size="16"');
      l.Add('eaccelerator.cache_dir="/tmp/eaccelerator2"');
      l.Add('eaccelerator.enable="1"');
      l.Add('eaccelerator.optimizer="1"');
      l.Add('eaccelerator.check_mtime="1"');
      l.Add('eaccelerator.debug="0"');
      l.Add('eaccelerator.filter=""');
      l.Add('eaccelerator.shm_max="0"');
      l.Add('eaccelerator.shm_ttl="0"');
      l.Add('eaccelerator.shm_prune_period="0"');
      l.Add('eaccelerator.shm_only="0"');
      l.Add('eaccelerator.compress="1"');
      l.Add('eaccelerator.compress_level="9"');
   end;
end else begin
    logs.Debuglogs('Starting......: lighttpd: php.ini key eaccelerator is disabled');
end;

if FileExists(SYS.LOCATE_APC_SO()) then begin
   if ApcEnabledInPhp=1 then begin
      logs.Debuglogs('Starting......: lighttpd: php.ini enable APC client');
      l.Add('');
      l.Add('extension=apc.so');
      l.Add('[APC]');
      l.Add('apc.enable_cli="1"');
      l.Add('apc.stat ="0"');
      l.add('apc.include_once_override="0"');
      l.add('apc.cache_by_default="0"');
      l.add('apc.filters = "-(\.php|\.inc)"');
      l.Add('');
   end else begin
      logs.Debuglogs('Starting......: lighttpd: php.ini disable APC client');
   end;
end;

l.Add(PHP5_CHECK_EXTENSIONS());

zarafa:=tzarafa_server.Create(SYS);
if FileExists(zarafa.BIN_PATH()) then begin
   if FileExists(SYS.LOCATE_MAPI_SO()) then begin
     logs.Debuglogs('Starting......: lighttpd: register mapi.so');
     l.Add('extension=mapi.so');
   end else begin
      logs.Debuglogs('Starting......: lighttpd: mapi.so no such file !!!');
   end;
end;

if FileExists('/etc/artica-postfix/php.include.ini') then begin
      logs.Debuglogs('Starting......: lighttpd: Adding user defined values');
      l.Add(logs.ReadFromFile('/etc/artica-postfix/php.include.ini'));
end;

  t:=Tstringlist.Create;
  t.add('/etc/php.ini');
  t.Add('/etc/php5/cli/php.ini');
  t.Add('/etc/php5/cgi/php.ini');
  t.add('/etc/php5/apache2/php.ini');
  t.add('/etc/php/php.ini');
  t.add('/etc/php-cgi-fcgi.ini');
  t.add('/etc/php5/fastcgi/php.ini');
  t.add('/etc/php5/apache2/php.ini');
  t.add('/etc/php5/fpm/php.ini');

  for i:=0 to t.Count-1 do begin
      if FileExists(t.Strings[i]) then begin
         logs.Debuglogs('Starting......: lighttpd: registers key in '+t.Strings[i]);
         logs.WriteToFile(l.Text,t.Strings[i]);
      end;
  end;

  forceDirectories('/etc/artica-postfix/roundcube');
  logs.WriteToFile(l.Text,'/etc/artica-postfix/roundcube/php.ini');

  t.free;
  l.free;
  ForceDirectories('/usr/share/artica-postfix/ressources/profiles');


  fpsystem('/bin/chmod 755 /usr/share/artica-postfix/ressources/profiles');
  logs.Debuglogs('Starting......: lighttpd: Compile languages');
  fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.shm.php --parse-langs');


  end;
//##############################################################################
function Tlighttpd.roundcube_main_folder():string;
begin

if FileExists('/usr/share/roundcubemail/index.php') then exit('/usr/share/roundcubemail');
if FileExists('/usr/share/roundcube/index.php') then exit('/usr/share/roundcube');
if FileExists('/var/lib/roundcube/index.php') then exit('/var/lib/roundcube');
end;
//##############################################################################
function Tlighttpd.PHP5_CHECK_EXTENSIONS:string;
var
l:TstringList;
z:Tstringlist;
t:Tstringlist;
confdir:string;
i:integer;
sofile:string;
soname:string;
libdir:string;
LOCATE_PHP5_EXTENSION_DIR:string;
NoPHPMcrypt,EnableMemcached,LighttpdMinimalLibraries:integer;
begin



confdir:=SYS.LOCATE_PHP5_EXTCONF_DIR();
LOCATE_PHP5_EXTENSION_DIR:=sys.LOCATE_PHP5_EXTENSION_DIR();
if not DirectoryExists(confdir) then begin
    logs.Debuglogs('Starting......: lighttpd: Unable to stat php5 additional ini files path');
    exit;
end;


sys.DirFiles(confdir,'*.ini');

logs.Debuglogs('Starting......: lighttpd: Ext dir: '+confdir +'('+ intToSTr(sys.DirListFiles.Count)+' ini files)');
for i:=0 to sys.DirListFiles.Count-1 do begin
    logs.DeleteFile(confdir+'/'+sys.DirListFiles.Strings[i]);

end;

if not TryStrToInt(SYS.GET_INFO('NoPHPMcrypt'),NoPHPMcrypt) then NoPHPMcrypt:=0;
if not TryStrToInt(SYS.GET_INFO('EnableMemcached'),EnableMemcached) then EnableMemcached:=1;
if not TryStrToInt(SYS.GET_INFO('LighttpdMinimalLibraries'),LighttpdMinimalLibraries) then LighttpdMinimalLibraries:=0;
t:=Tstringlist.Create;
t.Add('/etc/php5/conf.d/mcrypt.ini');
t.add(confdir+'/z-mailparse.ini');
t.add(confdir+'/mcrypt.ini');
t.add(confdir+'/eaccelerator.ini');
t.add('/etc/php5/conf.d/mcrypt.ini');
t.add('/etc/php5/conf.d/eaccelerator.ini');
t.add('/etc/php5/cli/conf.d/eaccelerator.ini');
t.add('/etc/php5/conf.d/eaccelerator.so.ini');
t.add('/etc/php.d/28_ldap.ini');
t.add('/etc/php.d/29_mbstring.ini');
t.add('/etc/php.d/30_mcrypt.ini');
t.add('/etc/php.d/36_mysql.ini');
t.add('/etc/php.d/23_gd.ini');
t.add('/etc/php.d/82_json.ini');
t.add('/etc/php.d/43_posix.ini');
t.add('/etc/php.d/47_session.ini');
t.add('/etc/php.d/27_imap.ini');
t.add('/etc/php.d/13_curl.ini');
t.add('/etc/php.d/A12_mailparse.ini');
t.add('/etc/php.d/33_apc.ini');
t.add('/etc/php5/cli/conf.d/ming.ini');


for i:=0 to t.Count -1 do begin
    if FIleExists(t.Strings[i]) then logs.DeleteFile(t.Strings[i]);
end;

t.free;


if DirectoryExists('/etc/php5/cli/conf.d') then begin
    fpsystem('rm -f /etc/php5/cli/conf.d/*.ini');
    fpsystem('rm -f /etc/php5/cli/conf.d/*.so.ini');
end;

if DirectoryExists('/etc/php5/cgi/conf.d') then begin
    fpsystem('rm -f /etc/php5/cgi/conf.d/*.ini');
    fpsystem('rm -f /etc/php5/cgi/conf.d/*.so.ini');
end;



fpsystem('/bin/ln -s /usr/share/artica-postfix/ressources/logs/php.log /var/log/php.log >/dev/null 2>&1');

l:=Tstringlist.Create;
z:=Tstringlist.Create;

z.add('ctype.so');
z.add('pcntl.so');
z.add('curl.so');
z.add('openssl.so');
z.add('fileinfo.so');
if LighttpdMinimalLibraries=0 then z.add('dom.so');
if LighttpdMinimalLibraries=0 then z.add('ftp.so');
z.add('gd.so');
z.add('iconv.so');
z.add('imap.so');
z.add('ldap.so');
z.add('mysql.so');
z.add('readline.so');
z.add('hash.so');
z.add('xml.so');
z.add('sockets.so');
//z.add('xmlreader.so');
z.add('xmlwriter.so');
z.add('filter.so');
if LighttpdMinimalLibraries=0 then z.add('phpcups.so');
if LighttpdMinimalLibraries=0 then z.add('mysqli.so');
if LighttpdMinimalLibraries=0 then z.add('pdo.so');
if LighttpdMinimalLibraries=0 then z.add('pdo_mysql.so');
if LighttpdMinimalLibraries=0 then z.add('pdo_sqlite.so');
if LighttpdMinimalLibraries=0 then z.add('sqlite.so');
z.add('posix.so');
if LighttpdMinimalLibraries=0 then z.add('zip.so');
if LighttpdMinimalLibraries=0 then z.add('xapian.so');
z.add('geoip.so');
z.add('zlib.so');
z.add('tokenizer.so');
z.add('mailparse.so');
z.add('json.so');
if LighttpdMinimalLibraries=0 then z.add('uploadprogress.so');
z.add('xmlrpc.so');
z.add('session.so');
z.add('gettext.so');
z.add('mbstring.so');
if LighttpdMinimalLibraries=0 then z.add('ssh2.so');
if LighttpdMinimalLibraries=0 then z.add('pspell.so');
if LighttpdMinimalLibraries=0 then z.add('rrd.so');
if LighttpdMinimalLibraries=0 then z.add('rrdtool.so');

if FileExists('/opt/arkeia/wui/httpd/lib/arkphp.so') then begin
       if not FileExists( LOCATE_PHP5_EXTENSION_DIR+'/arkphp.so') then fpsystem(SYS.LOCATE_GENERIC_BIN('ln')+' -s /opt/arkeia/wui/httpd/lib/arkphp.so '+LOCATE_PHP5_EXTENSION_DIR+'/arkphp.so');
       z.add('arkphp.so');
end;

if LighttpdMinimalLibraries=0 then logs.Debuglogs('Starting......: lighttpd: Loading ALL required libraries');
if LighttpdMinimalLibraries=1 then logs.Debuglogs('Starting......: lighttpd: Loading minimalist required libraries');

//z.add('oauth.so');
if LighttpdMinimalLibraries=0 then  begin
   if EnableMemcached=1 then z.add('memcache.so') else logs.Debuglogs('Starting......: lighttpd: memcached is disabled');
end;

if NoPHPMcrypt=0 then begin
   z.add('mcrypt.so');
   if LighttpdMinimalLibraries=0 then z.add('ming.so');
end else begin
  logs.Debuglogs('Starting......: lighttpd: mcrypt is disabled');
end;


if DisableEaccelerator=1 then begin
    logs.Debuglogs('Starting......: lighttpd: eAccelerator is disabled');
end else begin
   if LighttpdMinimalLibraries=0 then  z.add('eaccelerator.so');
end;


for i:=0 to z.Count-1 do begin
     sofile:=LOCATE_PHP5_EXTENSION_DIR+'/'+z.Strings[i];
     soname:=IntToStr(i)+'_'+z.Strings[i]+'.ini';
     soname:=AnsiReplaceText(soname,'.so','');

     if not FileExists(sofile) then begin
        if FIleExists('/usr/lib/php/modules/'+z.Strings[i]) then begin
           logs.Debuglogs('Starting......: lighttpd: linking '+z.Strings[i]+' from /usr/lib/php/modules');
           fpsystem('/bin/ln -s /usr/lib/php/modules/'+z.Strings[i] +' '+LOCATE_PHP5_EXTENSION_DIR+'/'+z.Strings[i]);
        end;
     end;

     if FileExists(sofile)  then begin
        logs.Debuglogs('Starting......: lighttpd including extension '+z.Strings[i]);
        l.Add('extension='+z.Strings[i]);
     end else begin
     logs.Debuglogs('Starting......: lighttpd excluding extension '+z.Strings[i]+' no such file');
     logs.Debuglogs('lighttpd: '+sofile+' didn''t exists..');
     end;
end;



//             open_basedir

if DisableEaccelerator=0 then begin
   if LighttpdMinimalLibraries=0 then begin
forcedirectories('/tmp/eaccelerator');
if FileExists(SYS.LOCATE_EACCELERATOR_SO()) then begin
   if not FileExists(confdir+'/eaccelerator.so.ini') then begin
      l.Add('extension=eaccelerator.so');
      l.Add('eaccelerator.shm_size="0"');
      l.Add('eaccelerator.cache_dir="/tmp/eaccelerator"');
      l.Add('eaccelerator.enable="1"');
      l.Add('eaccelerator.optimizer="1"');
      l.Add('eaccelerator.check_mtime="1"');
      l.Add('eaccelerator.debug="0"');
      l.Add('eaccelerator.filter=""');
      l.Add('eaccelerator.shm_max="0"');
      l.Add('eaccelerator.shm_ttl="0"');
      l.Add('eaccelerator.shm_prune_period="0"');
      l.Add('eaccelerator.shm_only="0"');
      l.Add('eaccelerator.compress="1"');
      l.Add('eaccelerator.compress_level="9"');
      l.SaveToFile(confdir+'/eaccelerator.ini');
      l.Clear;
   end;
   end;
end;
end;

if LighttpdMinimalLibraries=0 then begin
if EnableMemcached=1 then begin
   forcedirectories('/var/lib/memcache');
   if FileExists(LOCATE_PHP5_EXTENSION_DIR+'/memcache.so') then begin
      logs.Debuglogs('Starting......: lighttpd configuring memcache...');
      l.Add('[memcache]');
      l.Add('memcache.dbpath="/var/lib/memcache"');
      l.Add('memcache.maxreclevel=0');
      l.Add('memcache.maxfiles=0');
      l.Add('memcache.archivememlim=0');
      l.Add('memcache.maxfilesize=0');
      l.Add('memcache.maxratio=0');
   end;
 end;
 end;

result:=L.Text;
FreeAndNil(l);

end;
//##############################################################################
function Tlighttpd.lighttpd_modules_path():string;
begin
if fileExists('/usr/lib64/lighttpd/mod_alias.so') then exit('/usr/lib64/lighttpd');
if fileExists('/usr/local/lib64/lighttpd/mod_alias.so') then exit('/usr/local/lib64/lighttpd');
if FileExists('/usr/lib/lighttpd/mod_alias.so') then exit('/usr/lib/lighttpd');
if FileExists('/usr/local/lib/lighttpd/mod_alias.so') then exit('/usr/local/lib/lighttpd');
end;
//##############################################################################
function Tlighttpd.Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
var
  SepLen       : Integer;
  F, P         : PChar;
  ALen, Index  : Integer;
begin
  SetLength(Result, 0);
  if (S = '') or (Limit < 0) then
    Exit;
  if Separator = '' then
  begin
    SetLength(Result, 1);
    Result[0] := S;
    Exit;
  end;
  SepLen := Length(Separator);
  ALen := Limit;
  SetLength(Result, ALen);

  Index := 0;
  P := PChar(S);
  while P^ <> #0 do
  begin
    F := P;
    P := StrPos(P, PChar(Separator));
    if (P = nil) or ((Limit > 0) and (Index = Limit - 1)) then
      P := StrEnd(F);
    if Index >= ALen then
    begin
      Inc(ALen, 5); // mehrere auf einmal um schneller arbeiten zu können
      SetLength(Result, ALen);
    end;
    SetString(Result[Index], F, P - F);
    Inc(Index);
    if P^ <> #0 then
      Inc(P, SepLen);
  end;
  if Index < ALen then
    SetLength(Result, Index); // wirkliche Länge festlegen
end;

end.

