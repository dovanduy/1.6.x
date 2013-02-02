unit squid;

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
  end;

  type
  tsquid=class


private
     LOGS:Tlogs;
     D:boolean;
     GLOBAL_INI:TiniFIle;
     SYS:TSystem;
     artica_path:string;
     SQUIDEnable:integer;
     SquidEnableProxyPac:integer;
     TAIL_STARTUP:string;
     withoutcompile:boolean;
     EnableUfdbGuard:integer;
     EnableSquidClamav:integer;
     MyPidPath:string;
     MyPID:string;
     function COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
     function get_INFOS(key:string):string;
     function ReadFileIntoString(path:string):string;
     function SQUID_DETERMINE_PID_PATH():string;
     procedure WRITE_INITD();
     procedure ERROR_FD();
     function    DANSGUARDIAN_PORT():string;
     function    GET_LOCAL_PORT():string;
     function    TAIL_PID():string;
     function    TAIL_SOCK_PID():string;
     function    PROXY_PAC_PID():string;
     function    GET_SSL_PORT():string;
     function    is31Ver():boolean;
     function    is32Ver():boolean;
     procedure   zapper_install();
     function    SQUID_OLD_PROCESS():integer;
     procedure   SQUID_STOP_GHOST();
     procedure   SQUID_STOP_LISTEN_PROCESSES();
     function    GET_SQUID_PORT():string;
   function      TAIL_SOCK_STOP_LISTEN_PROCESSES():boolean;
   procedure     mounttmpfs();

public
    Caches      :TstringList;
    DansGuardianEnabled:integer;
    procedure   Free;
    constructor Create;
    PROCEDURE   SQUID_RRD_INSTALL();
    function    SQUID_BIN_PATH():string;
    PROCEDURE   SQUID_RRD_EXECUTE();
    procedure   SQUID_START();
    function    SQUID_PID():string;
    PROCEDURE   SQUID_RRD_INIT();
    PROCEDURE   SQUID_VERIFY_CACHE();
    function    SQUID_CONFIG_PATH():string;
    function    SQUID_GET_SINGLE_VALUE(key:string):string;
    procedure   SQUID_SET_CONFIG(key:string;value:string);
    function    SQUID_ARP_ACL_ENABLED():integer;
    function    SQUID_URL_MAP_ACL_ENABLED():integer;
    procedure   SQUID_STOP();
    function    SQUID_STATUS():string;
    function    SQUID_VERSION():string;
    function    ldap_auth_path():string;
    function    ntml_auth_path():string;
    function    SQUID_GET_CONFIG(key:string):string;
    function    SQUIDCLIENT_BIN_PATH():string;
    function    SQUID_INIT_PATH():string;
    function    SQUID_SPOOL_DIR():string;
    procedure   PARSE_ALL_CACHES();
    function    SQUID_BIN_VERSION(version:string):int64;
    function    icap_enabled():boolean;
    function    ntlm_enabled():boolean;
    function    cachemgr_path():string;
    function    squid_kerb_auth_path():string;
    function    ext_session_acl_path():string;

    procedure   TAIL_START();
    procedure   TAIL_STOP();


    procedure   TAIL_SOCK_START(norestart:boolean);
    procedure   TAIL_SOCK_STOP();

    procedure   SQUID_RELOAD();
    procedure   AS_TRANSPARENT_MODE();
    procedure   REMOVE();
    procedure   START_SIMPLE();

    //squidClamav
    procedure TAIL_SQUIDCLAMAV_STOP();
    procedure TAIL_SQUIDCLAMAV_START();
    function  TAIL_SQUIDCLAMAV_PID():string;

    //SARG
    function    SARG_VERSION():string;
    function    SARG_SCAN():string;
    procedure   SARG_CONFIG();
    procedure   SARG_EXECUTE();

    //ProxyPac
    procedure   PROXY_PAC_STOP();
    procedure   PROXY_PAC_START();
    procedure   rebuild_caches();
    function    GetFreeMem():integer;
    function    isMustBeExecuted():boolean;

END;

implementation

constructor tsquid.Create;
begin

       LOGS:=tlogs.Create();
       SYS:=Tsystem.Create;
       withoutcompile:=false;
       D:=COMMANDLINE_PARAMETERS('--verbose');
       MyPidPath:='/etc/artica-postfix/pids/artica-install-squid.pid';
       MyPID:=SYS.SYSTEM_GET_MYPID();
       if not TryStrToInt(SYS.GET_INFO('DansGuardianEnabled'),DansGuardianEnabled) then DansGuardianEnabled:=0;
       if not TryStrToInt(SYS.GET_INFO('SQUIDEnable'),SQUIDEnable) then SQUIDEnable:=1;
       if not TryStrToInt(SYS.GET_INFO('SquidEnableProxyPac'),SquidEnableProxyPac) then SquidEnableProxyPac:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableUfdbGuard'),EnableUfdbGuard) then EnableUfdbGuard:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableSquidClamav'),EnableSquidClamav) then EnableSquidClamav:=0;



if sys.COMMANDLINE_PARAMETERS('--without-compile') then begin
   logs.Debuglogs('Starting......: Squid without compilation parameters');
   withoutcompile:=true;
end;

       if FileExists('/etc/artica-postfix/OPENVPN_APPLIANCE') then SQUIDEnable:=0;
       TAIL_STARTUP:=SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid-tail.php';
       if SQUIDEnable=0 then begin
          DansGuardianEnabled:=0;
          SquidEnableProxyPac:=0;
       end;
       Caches:=TstringList.Create;
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tsquid.free();
begin
    logs.Free;
    try SYS.Free; finally end;
   try Caches.free; finally end;
end;
//##############################################################################
function tsquid.SQUID_BIN_PATH():string;
var
   path:string;
begin

  if FileExists('/usr/sbin/squid3')  then exit('/usr/sbin/squid3');
  if FileExists('/usr/sbin/squid') then exit('/usr/sbin/squid');
  if FileExists('/usr/local/sbin/squid3') then exit('/usr/local/sbin/squid3');
  if FileExists('/usr/local/sbin/squid') then exit('/usr/local/sbin/squid');
  if FileExists('/sbin/squid') then exit('/sbin/squid');
  if FileExists('/opt/artica/sbin/squid') then exit('/opt/artica/sbin/squid');
  path:=SYS.LOCATE_GENERIC_BIN('squid');
  if FileExists(path) then exit(path);
  path:=SYS.LOCATE_GENERIC_BIN('squid3');
  if FileExists(path) then exit(path);
end;
//##############################################################################
function tsquid.SQUIDCLIENT_BIN_PATH():string;
begin
  if FileExists('/usr/bin/squidclient') then exit('/usr/bin/squidclient');
  if FileExists('/usr/local/squid/sbin/squidclient') then exit('/usr/local/squid/sbin/squidclient');
end;
//##############################################################################
function tsquid.SQUID_INIT_PATH():string;
begin
  if FileExists('/etc/init.d/squid3') then exit('/etc/init.d/squid3');
  if FileExists('/etc/init.d/squid') then exit('/etc/init.d/squid');

end;
//##############################################################################
function tsquid.isMustBeExecuted():boolean;
var
EnableWebProxyStatsAppliance:integer;
CategoriesRepositoryEnable:integer;
begin
if FileExists(SQUID_BIN_PATH()) then exit(true);
if Not TryStrToInt(SYS.GET_INFO('EnableWebProxyStatsAppliance'),EnableWebProxyStatsAppliance) then EnableWebProxyStatsAppliance:=0;
if Not TryStrToInt(SYS.GET_INFO('CategoriesRepositoryEnable'),EnableWebProxyStatsAppliance) then CategoriesRepositoryEnable:=0;
if Not TryStrToInt(SYS.GET_INFO('EnableWebProxyStatsAppliance'),EnableWebProxyStatsAppliance) then EnableWebProxyStatsAppliance:=0;
if EnableWebProxyStatsAppliance=1 then exit(true);
if EnableWebProxyStatsAppliance=1 then exit(true);
if EnableWebProxyStatsAppliance=1 then exit(true);
result:=false;
end;
function tsquid.icap_enabled():boolean;
var
   l            :TstringList;
   RegExpr      :TRegExpr;
   i            :integer;
   FileTemp     :string;
   squidbin     :string;
begin
   result:=false;
   squidbin:=SQUID_BIN_PATH();
   if not FileExists(squidbin) then exit;
   FileTemp:=logs.FILE_TEMP();
   fpsystem(squidbin + ' -v >'+FileTemp + ' 2>&1');
   if not FileExists(Filetemp) then exit;
   l:=TstringList.Create;
   l.LoadFromFile(FileTemp);
   logs.DeleteFile(FileTemp);
   RegExpr:=TRegExpr.Create;
   for i:=0 to l.count-1 do begin
       RegExpr.Expression:='--enable-icap-client';
       if  RegExpr.Exec(l.Strings[i]) then begin
           result:=true;
           break;
       end;
       RegExpr.Expression:='--enable-icap-support';
       if  RegExpr.Exec(l.Strings[i]) then begin
           result:=true;
           break;
       end;
   end;
   
   l.free;
   RegExpr.Free;
   

end;
//##############################################################################
function tsquid.ntlm_enabled():boolean;
var
   l            :TstringList;
   RegExpr      :TRegExpr;
   i            :integer;
   FileTemp     :string;
   squidbin     :string;
begin
   result:=false;
   squidbin:=SQUID_BIN_PATH();
   if not FileExists(squidbin) then exit;
   FileTemp:=logs.FILE_TEMP();
   fpsystem(squidbin + ' -v >'+FileTemp + ' 2>&1');
   if not FileExists(Filetemp) then exit;
   l:=TstringList.Create;
   l.LoadFromFile(FileTemp);
   logs.DeleteFile(FileTemp);
   RegExpr:=TRegExpr.Create;
   for i:=0 to l.count-1 do begin
       RegExpr.Expression:='--enable-auth=(.+?)';
       if  RegExpr.Exec(l.Strings[i]) then begin
           RegExpr.Expression:='ntlm';
           if  RegExpr.Exec(l.Strings[i]) then begin
               result:=true;
           end;
           break;
       end;
   end;

   l.free;
   RegExpr.Free;
end;
//##############################################################################



procedure tsquid.ERROR_FD();
var
   l            :TstringList;
   RegExpr      :TRegExpr;
   i            :integer;
   FileTemp     :string;
   LastLog      :string;
begin
  exit;
  caches:=TstringList.Create;
  FileTemp:=SYS.LOCATE_SYSLOG_PATH();
  if not FileExists(FileTemp) then exit;
  l:=TstringList.Create;
  logs.Debuglogs('tsquid.ERROR_FD():: loading ' + FileTemp);
  try
  l.LoadFromFile(FileTemp);
  LastLog:=l.Strings[l.Count-1];
  l.free;
  Except
    logs.Debuglogs('tsquid.ERROR_FD():: Fatal error...');
    exit;
  end;
  logs.Debuglogs('tsquid.ERROR_FD():: Last log='+LastLog);
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='httpAccept.+?FD.+?Invalid argument';

  if RegExpr.Exec(LastLog) then begin
     if RegExpr.Match[1]='26' then begin
        PARSE_ALL_CACHES();
        for i:=0 to caches.Count-1 do begin
            if length(caches.Strings[i])>5 then fpsystem('/bin/rm -rf ' + caches.Strings[i]+'/*');
        end;
     logs.Syslogs('tsquid.ERROR_FD():: Error FD Found, restart all server !!!');
     logs.NOTIFICATION(LastLog + ' Artica will be restarted','Fatal error...','system');
     fpsystem('/etc/init.d/artica-postfix restart');
     halt(0);
    end;
  end;
RegExpr.Expression:='comm_old_accept.+?FD\s+([0-9]+).+?Invalid argument';
if RegExpr.Exec(LastLog) then begin
     if RegExpr.Match[1]='26' then begin
        PARSE_ALL_CACHES();
        for i:=0 to caches.Count-1 do begin
            if length(caches.Strings[i])>5 then fpsystem('/bin/rm -rf ' + caches.Strings[i]+'/*');
        end;
     logs.Syslogs('tsquid.ERROR_FD():: Error FD Found, restart all server !!!');
     logs.NOTIFICATION(LastLog + ' Artica will be restarted','Fatal error...','system');
     fpsystem('/etc/init.d/artica-postfix restart');
     halt(0);
    end;
  end;
end;
//##############################################################################
procedure tsquid.SQUID_RELOAD();
var

pid,timefile:string;
SquidCacheReloadTTL:Integer;
SquidCacheReloadTTLCur:integer;
begin
  timefile:='/etc/artica-postfix/pids/reloadsquid.time';
  if Not TryStrToInt(SYS.GET_INFO('SquidCacheReloadTTL'),SquidCacheReloadTTL) then SquidCacheReloadTTL:=10;
  SquidCacheReloadTTLCur:=SYS.FILE_TIME_BETWEEN_MIN(timefile);
  if SquidCacheReloadTTLCur< SquidCacheReloadTTL then begin
     logs.Syslogs('Reload squid aborted, need at least '+IntToStr(SquidCacheReloadTTL)+'mn current '+IntToStr(SquidCacheReloadTTLCur)+'mn');
     exit;
  end;

  logs.DeleteFile(timefile);
  logs.WriteToFile('#',timefile);

  pid:=SQUID_PID();
  logs.Debuglogs('Starting......: reloading SQUID PID: '+pid);
  if SYS.PROCESS_EXIST(pid) then begin
     AS_TRANSPARENT_MODE();
     logs.Debuglogs('Starting......: reloading squid...');
     if not withoutcompile then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --build');
     fpsystem(SQUID_BIN_PATH() + ' -k reconfigure');
     SYS.THREAD_COMMAND_SET('/etc/init.d/artica-postfix start kav4proxy');
     exit;
  end else begin
     SQUID_START();
  end;
end;
//##############################################################################
procedure tsquid.AS_TRANSPARENT_MODE();
begin
     if DirectoryExists('/opt/urlfilterbox') then exit;
     fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.transparent.php');
end;

//##############################################################################
function tsquid.GET_LOCAL_PORT():string;
var
   RegExpr      :TRegExpr;


begin
    if DansGuardianEnabled=1 then begin
       result:=DANSGUARDIAN_PORT();
       if length(result)>0 then exit;
    end;
    

   result:=SQUID_GET_CONFIG('http_port');
   RegExpr:=TRegExpr.Create;

   RegExpr.Expression:='([0-9\.]+):([0-9]+)';
   if RegExpr.Exec(result) then begin
      result:=RegExpr.Match[2];
      RegExpr.free;
      exit;
   end;


   RegExpr.Expression:='([0-9]+)';
   RegExpr.Exec(result);
   result:=RegExpr.Match[1];


end;

//##############################################################################
function tsquid.GET_SQUID_PORT():string;
var
   RegExpr      :TRegExpr;


begin
   result:=SQUID_GET_CONFIG('http_port');
   RegExpr:=TRegExpr.Create;

   RegExpr.Expression:='([0-9\.]+):([0-9]+)';
   if RegExpr.Exec(result) then begin
      result:=RegExpr.Match[2];
      RegExpr.free;
      exit;
   end;

   RegExpr.Expression:='([0-9]+)';
   RegExpr.Exec(result);
   result:=RegExpr.Match[1];


end;

//##############################################################################

function tsquid.GET_SSL_PORT():string;
var
   RegExpr      :TRegExpr;


begin
   result:=SQUID_GET_CONFIG('https_port');
   RegExpr:=TRegExpr.Create;

   RegExpr.Expression:='([0-9\.]+):([0-9]+)';
   if RegExpr.Exec(result) then result:=RegExpr.Match[2];
   RegExpr.Expression:='([0-9]+)';
   RegExpr.Exec(result);
   result:=RegExpr.Match[1];
end;

//##############################################################################
function tsquid.DANSGUARDIAN_PORT():string;
var
   l            :TstringList;
   RegExpr      :TRegExpr;
   i            :integer;
begin
result:='';
if not FileExists('/etc/dansguardian/dansguardian.conf') then exit;
l:=TstringList.Create;
l.LoadFromFile('/etc/dansguardian/dansguardian.conf');
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='^filterport.+?([0-9]+)';
for i:=0 to l.Count-1 do begin
      if RegExpr.Exec(l.Strings[i]) then begin
         result:=RegExpr.Match[1];
         break;
      end;
end;

RegExpr.free;
l.free;
end;
//##############################################################################
procedure tsquid.START_SIMPLE();
 var
    pid:string;
    count:integer;
    SYS:Tsystem;
    pidpath:string;
    l:TstringList;
    FileTemp:string;
    options:string;
    http_port:string;
    squidconf:string;
    cmd:string;
    mybinVer:integer;
    RegExpr:TRegExpr;
    valInt:integer;
    WithoutConfig:boolean;
    UrlFlilterBox:boolean;
    TimePID:integer;
begin

MyPID:=SYS.SYSTEM_GET_MYPID();
pid:=trim(logs.ReadFromFile(MyPidPath));
if(pid<>MyPID) then begin
    if SYS.PROCESS_EXIST(pid) then begin
         TimePID:=SYS.PROCCESS_TIME_MIN(pid);
         logs.Debuglogs('Starting......: ['+MyPID+']: squid, artica-install already running PID '+pid+' since '+IntToStr(TimePID)+'Mn');
         if TimePID>5 then begin
             logs.Debuglogs('Starting......: ['+MyPID+']: killing artica-install process '+pid);
         end else begin
             logs.Debuglogs('Starting......: ['+MyPID+']: exiting');
             exit;
         end;
    end;
end;
logs.WriteToFile(MyPID,MyPidPath);

mybinVer:=SQUID_BIN_VERSION(SQUID_VERSION());
WithoutConfig :=false;
UrlFlilterBox:=false;
if SYS.COMMANDLINE_PARAMETERS('--without-config') then begin
   WithoutConfig:=true;
   logs.Debuglogs('Starting......: Squid Skip configuration...');
   if FileExists('/etc/artica-postfix/squid.lock') then  logs.DeleteFile('/etc/artica-postfix/squid.lock');
end;

if DirectoryExists('/opt/urlfilterbox') then UrlFlilterBox:=true;

if FileExists('/etc/artica-postfix/squid.lock') then  begin
    logs.Debuglogs('Starting......: [INIT]: Squid.lock aborting !!!...');
    exit;
end;
count:=0;
SYS:=Tsystem.Create;
     logs.Debuglogs('Starting......: [INIT]: System Memory :"'+IntTOStr(GetFreeMem())+'Kb" OK');


  if not FileExists(SQUID_BIN_PATH()) then begin
     logs.Debuglogs('Starting......: [INIT]: Squid is not installed aborting...');
     exit;
  end;

  if SQUIDEnable=0 then begin
      logs.Debuglogs('Starting......: [INIT]: Squid is disabled aborting by SQUIDEnable...'+IntTostr(SQUIDEnable));
       pid:=SQUID_PID();
        if SYS.PROCESS_EXIST(pid) then begin
         SQUID_STOP();
         exit;
        end;
      exit;
  end;

  ERROR_FD();
  pid:=SQUID_PID();
  if SYS.PROCESS_EXIST(pid) then begin
     logs.DebugLogs('Starting......: [INIT]: Squid already running with pid ' + pid+ '...');
     exit;
  end;

if not SYS.IsUserExists('squid') then begin
   logs.Debuglogs('Starting......: [INIT]: Squid, creating squid user...');
   SYS.AddUserToGroup('squid','squid','','');
end;
 logs.Debuglogs('Starting......: [INIT]: Squid, with exec.squid.watchdog.php');
 fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.watchdog.php  --start --bydaemon');


 pid:=SQUID_PID();
  if SYS.PROCESS_EXIST(pid) then begin
     SQUID_RRD_EXECUTE();
  end else begin
   logs.DebugLogs('Starting......: Squid Failed to start...');
  end;

  SYS.FREE;

end;

//##############################################################################
function tsquid.is31Ver():boolean;

var
ver:string;
major,minor:integer;
RegExpr:TRegExpr;
begin
   ver:=SQUID_VERSION();
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^([0-9]+)\.([0-9]+)';
   RegExpr.Exec(ver);
   TryStrToInt(RegExpr.Match[1],major);
   TryStrToInt(RegExpr.Match[1],minor);
   if major>=3 then begin
      if minor>=1 then result:=true;
   end;

end;
//##############################################################################
function tsquid.is32Ver():boolean;

var
ver:string;
major,minor:integer;
RegExpr:TRegExpr;
D:boolean;
begin
  result:=false;
  D:=logs.COMMANDLINE_PARAMETERS('--verbose');
   ver:=SQUID_VERSION();
   if D then writeln('Version:', ver);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^([0-9]+)\.([0-9]+)';
   RegExpr.Exec(ver);
   TryStrToInt(RegExpr.Match[1],major);
   TryStrToInt(RegExpr.Match[2],minor);
   if D then writeln('Major:', major);
   if D then writeln('minor:', major);
   if major>=3 then begin
      if minor>=2 then result:=true;
   end;

end;
//##############################################################################
procedure tsquid.rebuild_caches();
begin
    writeln('Removing all caches in /var/cache');
    fpsystem(SYS.LOCATE_GENERIC_BIN('rm') +' -rf /var/cache/squid');
    fpsystem(SYS.LOCATE_GENERIC_BIN('rm') +' -rf /var/cache/squid-* >/dev/null 2>&1');
    fpsystem(SYS.LOCATE_GENERIC_BIN('rm') +' -rf /var/cache/RockStore* >/dev/null 2>&1');
    fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --build');
    fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --caches-reconstruct');
end;
//##############################################################################
function tsquid.GetFreeMem():integer;
var
tmpfile:string;
i:integer;
l:Tstringlist;
RegExpr:TRegExpr;
D:boolean;
begin
    tmpfile:=logs.FILE_TEMP();
    fpsystem(SYS.LOCATE_GENERIC_BIN('free') +' -k >/'+ tmpfile+' 2>&1');
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='Mem:\s+([0-9]+)';
    l:=Tstringlist.Create;
    l.LoadFromFile(tmpfile);
    logs.DeleteFile(tmpfile);

   For i:=0 to l.count-1 do begin
   if RegExpr.exec(l.Strings[i]) then begin
      TryStrToInt(RegExpr.Match[1],result);
      l.free;
      exit;
   end;
end;

end;

//##############################################################################



procedure tsquid.SQUID_START();
 var
    pid:string;
    SYS:Tsystem;
    pidpath:string;
begin
SYS:=Tsystem.Create;
pid:=trim(logs.ReadFromFile(MyPidPath));
if(pid<>MyPID) then begin
    if SYS.PROCESS_EXIST(pid) then begin
         logs.Debuglogs('Starting......: squid, artica-install already running PID '+pid+' aborting');
         exit;
    end;
end;
logs.WriteToFile(MyPID,MyPidPath);

if FileExists('/etc/artica-postfix/squid.lock') then  begin
    logs.Debuglogs('Starting......: squid.lock aborting...');
    exit;
end;

  if not FileExists(SQUID_BIN_PATH()) then begin
     logs.Debuglogs('Starting......: Squid is not installed aborting...');
     exit;
  end;

  if SQUIDEnable=0 then begin
      logs.Debuglogs('Starting......: Squid is disabled aborting...');
      exit;
  end;

 if SYS.isoverloadedTooMuch() then begin
     logs.DebugLogs('Starting......: Squid System is overloaded');
     exit;
end;
  SYS.MONIT_DELETE('APP_DANSGUARDIAN');
  if is32Ver() then TAIL_SOCK_START(false);
  TAIL_SQUIDCLAMAV_START();
  ERROR_FD();
  pid:=SQUID_PID();
  if SYS.PROCESS_EXIST(pid) then begin
     logs.DebugLogs('Starting......: Squid already running with pid ' + pid+ '...');
     if SQUIDEnable=0 then SQUID_STOP();
   exit;
  end;
  //http_port:=SQUID_GET_CONFIG('http_port');
 // options:=' -D -sYC -a '+http_port +' -f ' +SQUID_CONFIG_PATH();
  

  pidpath:=SQUID_GET_CONFIG('pid_filename');
  LOGS.DeleteFile(pidpath);
 // FileTemp:=artica_path+'/ressources/logs/squid.start.daemon';
  
       if not SYS.IsUserExists('squid') then begin
           logs.DebugLogs('Starting......: Squid user "squid" doesn''t exists... reconfigure squid');
           fpsystem(Paramstr(0) + ' -squid-configure');
       end else begin
           logs.DebugLogs('Starting......: [INIT]: Squid user "squid" exists OK');
       end;
  
        logs.DebugLogs('Starting......: [INIT]: Squid binary: '+SQUID_BIN_PATH());


        logs.DebugLogs('Starting......: [INIT]: Squid configure rrd statistics...');
        SQUID_RRD_INIT();
        SQUID_RRD_INSTALL();
        logs.DebugLogs('Starting......: [INIT]: Squid verify cache containers...');
        SQUID_VERIFY_CACHE();
        logs.DebugLogs('Starting......: [INIT]: Squid change the init.d script');
        WRITE_INITD();
        logs.DebugLogs('Starting......: [INIT]: Squid Starting squid');
        START_SIMPLE();


end;
//#############################################################################
function tsquid.ldap_auth_path():string;
begin
if FileExists('/lib/squid3/basic_ldap_auth') then exit('/lib/squid3/basic_ldap_auth');
if FileExists('/usr/lib/squid3/squid_ldap_auth') then exit('/usr/lib/squid3/squid_ldap_auth');
if FileExists('/usr/lib64/squid3/squid_ldap_auth') then exit('/usr/lib64/squid3/squid_ldap_auth');
if FileExists('/lib/squid3/squid_ldap_auth') then exit('/lib/squid3/squid_ldap_auth');
if FileExists('/lib64/squid3/squid_ldap_auth') then exit('/lib64/squid3/squid_ldap_auth');
if FileExists('/usr/lib/squid/ldap_auth') then exit('/usr/lib/squid/ldap_auth');
if FileExists('/usr/lib/squid/squid_ldap_auth') then exit('/usr/lib/squid/squid_ldap_auth');
if FileExists('/usr/lib64/squid/squid_ldap_auth') then exit('/usr/lib64/squid/squid_ldap_auth');
if FileExists('/usr/lib64/squid/ldap_auth') then exit('/usr/lib64/squid/ldap_auth');
if FileExists('/usr/local/lib/squid/ldap_auth') then exit('/usr/local/lib/squid/ldap_auth');
if FileExists('/usr/local/lib64/squid/ldap_auth') then exit('/usr/local/lib64/squid/ldap_auth');
if FileExists('/opt/artica/libexec/squid_ldap_auth') then exit('/opt/artica/libexec/squid_ldap_auth');
end;
//#############################################################################
function tsquid.squid_kerb_auth_path():string;
begin
if FileExists('/lib/squid3/negotiate_kerberos_auth') then exit('/lib/squid3/negotiate_kerberos_auth');
if FileExists('/usr/lib/squid3/squid_kerb_auth') then exit('/usr/lib/squid3/squid_kerb_auth');
if FileExists('/usr/lib64/squid3/squid_kerb_auth') then exit('/usr/lib64/squid3/squid_kerb_auth');
if FileExists('/lib/squid3/squid_kerb_auth') then exit('/lib/squid3/squid_kerb_auth');
if FileExists('/lib64/squid3/squid_kerb_auth') then exit('/lib64/squid3/squid_kerb_auth');
if FileExists('/usr/lib/squid/squid_kerb_auth') then exit('/usr/lib/squid/squid_kerb_auth');
if FileExists('/usr/lib/squid/squid_kerb_auth') then exit('/usr/lib/squid/squid_kerb_auth');
if FileExists('/usr/lib64/squid/squid_kerb_auth') then exit('/usr/lib64/squid/squid_kerb_auth');
if FileExists('/usr/lib64/squid/squid_kerb_auth') then exit('/usr/lib64/squid/squid_kerb_auth');
if FileExists('/usr/local/lib/squid/squid_kerb_auth') then exit('/usr/local/lib/squid/squid_kerb_auth');
if FileExists('/usr/local/lib64/squid/squid_kerb_auth') then exit('/usr/local/lib64/squid/squid_kerb_auth');
if FileExists('/opt/artica/libexec/squid_kerb_auth') then exit('/opt/artica/libexec/squid_kerb_auth');
end;
//#############################################################################
function tsquid.ext_session_acl_path():string;
begin
if FileExists('/lib/squid3/ext_session_acl') then exit('/lib/squid3/ext_session_acl');
if FileExists('/usr/lib/squid3/ext_session_acl') then exit('/usr/lib/squid3/ext_session_acl');
if FileExists('/usr/lib64/squid3/ext_session_acl') then exit('/usr/lib64/squid3/ext_session_acl');
if FileExists('/usr/lib/squid3/ext_session_acl') then exit('/usr/lib/squid3/ext_session_acl');
if FileExists('/usr/lib64/squid3/ext_session_acl') then exit('/usr/lib64/squid3/ext_session_acl');
if FileExists('/usr/lib/squid3/squid_session') then exit('/usr/lib/squid3/squid_session');
if FileExists('/usr/lib64/squid3/squid_session') then exit('/usr/lib64/squid3/squid_session');
end;
//#############################################################################




//#############################################################################
 function tsquid.cachemgr_path():string;
begin
if FileExists('/usr/lib/squid3/cachemgr.cgi') then exit('/usr/lib/squid3/cachemgr.cgi');
if FileExists('/usr/lib64/squid3/cachemgr.cgi') then exit('/usr/lib64/squid3/cachemgr.cgi');
if FileExists('/lib/squid3/cachemgr.cgi') then exit('/lib/squid3/cachemgr.cgi');
if FileExists('/lib64/squid3/cachemgr.cgi') then exit('/lib64/squid3/cachemgr.cgi');
if FileExists('/usr/lib/squid/ldap_auth') then exit('/usr/lib/squid/ldap_auth');
if FileExists('/usr/lib/squid/cachemgr.cgi') then exit('/usr/lib/squid/cachemgr.cgi');
if FileExists('/usr/lib64/squid/cachemgr.cgi') then exit('/usr/lib64/squid/cachemgr.cgi');
if FileExists('/usr/lib64/squid/ldap_auth') then exit('/usr/lib64/squid/ldap_auth');
if FileExists('/usr/local/lib/squid/ldap_auth') then exit('/usr/local/lib/squid/ldap_auth');
if FileExists('/usr/local/lib64/squid/ldap_auth') then exit('/usr/local/lib64/squid/ldap_auth');
if FileExists('/opt/artica/libexec/cachemgr.cgi') then exit('/opt/artica/libexec/cachemgr.cgi');
if FileExists('/usr/share/doc/packages/squid/scripts/cachemgr.cgi') then exit('/usr/share/doc/packages/squid/scripts/cachemgr.cgi');
if FileExists('/usr/lib/cgi-bin/cachemgr.cgi') then exit('/usr/lib/cgi-bin/cachemgr.cgi');
end;

function tsquid.ntml_auth_path():string;
var binpath:string;
begin
binpath:=SYS.LOCATE_GENERIC_BIN('ntlm_auth');
if FileExists(binpath) then exit(binpath);
end;
//#############################################################################




procedure tsquid.SQUID_SET_CONFIG(key:string;value:string);
var
   tmp          :TstringList;
   RegExpr      :TRegExpr;
   Found        :boolean;
   i            :integer;
begin
 found:=false;
 if not FileExists(SQUID_CONFIG_PATH()) then exit;
 tmp:=TstringList.Create;
 tmp.LoadFromFile(SQUID_CONFIG_PATH());
 RegExpr:=TRegExpr.Create;
 RegExpr.Expression:='^' + key;

 for i:=0 to tmp.Count-1 do begin
       if RegExpr.Exec(tmp.Strings[i]) then begin
         found:=true;
         tmp.Strings[i]:=key + chr(9) + value;
         break;
       end;

 end;

 if not found then begin
     tmp.Add(key + chr(9) + value);

 end;
 tmp.SaveToFile(SQUID_CONFIG_PATH());
 tmp.free;

 RegExpr.Free;
end;
//##############################################################################
function tsquid.SQUID_GET_CONFIG(key:string):string;
var
   tmp          :TstringList;
   RegExpr      :TRegExpr;
   i            :integer;
begin

 if not FileExists(SQUID_CONFIG_PATH()) then exit;
 
 tmp:=TstringList.Create;
 tmp.LoadFromFile(SQUID_CONFIG_PATH());
 RegExpr:=TRegExpr.Create;
 RegExpr.Expression:='^' + key+'\s+(.+)';

 for i:=0 to tmp.Count-1 do begin
       if RegExpr.Exec(tmp.Strings[i]) then begin
         result:=RegExpr.Match[1];
         break;
       end;
 end;
 tmp.free;
 RegExpr.Free;
end;
//##############################################################################
PROCEDURE tsquid.SQUID_VERIFY_CACHE();
 var
    FileS    :TstringList;
    RegExpr  :TRegExpr;
    path     :string;
    i        :integer;
    user,group,cache_store_log,cache_log,access_log,coredump_dir,visible_hostname:string;
begin

   user:=SQUID_GET_CONFIG('cache_effective_user');
   group:=SQUID_GET_CONFIG('cache_effective_group');
   cache_store_log:=SQUID_GET_CONFIG('cache_store_log');
   cache_log:=SQUID_GET_CONFIG('cache_log');
   access_log:=SQUID_GET_CONFIG('access_log');
   coredump_dir:=SQUID_GET_CONFIG('coredump_dir');
   visible_hostname:=SQUID_GET_CONFIG('visible_hostname');
   if DirectoryExists('/var/lib/squidguard') then fpsystem('/bin/chown -R squid:squid /var/lib/squidguard');
           
logs.DebugLogs('Starting......: Hostname ' + visible_hostname+ '...');
logs.DebugLogs('Starting......: Config file ' + SQUID_CONFIG_PATH()+ '...');

PARSE_ALL_CACHES();

   
   if(length(user)=0) then begin
       SQUID_SET_CONFIG('cache_effective_user','squid');
       user:='squid';
   end;

   if(length(group)=0) then group:='squid';

   

   SYS.AddUserToGroup(user,group,'','');

   Files:=TstringList.Create;
   Files.LoadFromFile(SQUID_CONFIG_PATH());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^cache_dir\s+(.+?)\s+(.+?)\s+';
   For i:=0 to Files.Count-1 do begin
       if RegExpr.Exec(Files.Strings[i]) then begin
           path:=RegExpr.Match[2];
           if not FileExists(path) then begin
              logs.DebugLogs('Starting......: Building new folder ' + path);
              forcedirectories(path);
              fpsystem('/bin/chmod 0755 ' + path);
              SYS.FILE_CHOWN(user,group,path);
           end;
       end;
   end;



end;
//#############################################################################
procedure tsquid.PARSE_ALL_CACHES();
var
   RegExpr      :TRegExpr;
   RegExpr2     :TRegExpr;
   tmp          :TstringList;
   i            :integer;
begin
caches.Clear;
     if not FileExists(SQUID_CONFIG_PATH()) then begin
        LOGS.logs('SQUID_GET_SINGLE_VALUE() -> unable to get squid.conf');
        exit;
     end;

   tmp:=TstringList.Create;
   tmp.LoadFromFile(SQUID_CONFIG_PATH());
   RegExpr:=TRegExpr.Create;
   RegExpr2:=TRegExpr.Create;
   RegExpr.Expression:='^cache_dir\s+(.+)';
   RegExpr2.Expression:='(.+?)\s+(.+?)\s+[0-9]+';

 for i:=0 to tmp.Count-1 do begin
      if RegExpr.Exec(tmp.Strings[i]) then begin
          if RegExpr2.Exec(RegExpr.Match[1]) then begin
             caches.Add(RegExpr2.Match[2]);
          end;
      end;
 end;
 
 tmp.free;
 RegExpr.free;
 RegExpr2.free;
end;
//#############################################################################
function tsquid.SQUID_SPOOL_DIR():string;
begin
result:=SQUID_GET_SINGLE_VALUE('coredump_dir');
if length(result)=0 then begin
    if DirectoryExists('/var/spool/squid') then exit('/var/spool/squid');
    if DirectoryExists('/var/spool/squid3') then exit('/var/spool/squid3');
end;

end;
//#############################################################################
function tsquid.SQUID_DETERMINE_PID_PATH():string;
begin
  if FileExists('/opt/artica/sbin/squid') then exit('/var/run/squid.pid');
  if FileExists('/usr/sbin/squid') then exit('/var/run/squid.pid');
  if FileExists('/usr/sbin/squid3')  then exit('/var/run/squid3.pid');
  if FileExists('/usr/local/sbin/squid') then exit('/var/run/squid.pid');
  if FileExists('/sbin/squid') then exit('/var/run/squid.pid');
end;
//#############################################################################
function tsquid.SQUID_CONFIG_PATH():string;
begin
   if FileExists('/etc/squid3/squid.conf') then exit('/etc/squid3/squid.conf');
   if FileExists('/opt/artica/etc/squid.conf') then exit('/opt/artica/etc/squid.conf');
   if FileExists('/etc/squid/squid.conf') then exit('/etc/squid/squid.conf');
end;
//##############################################################################
function tsquid.SQUID_PID():string;
var
   pidpath:string;
   binpath:string;
begin
    result:='';
    binpath:=SQUID_BIN_PATH();
    if not FileExists(binpath) then exit;
    pidpath:=SQUID_GET_CONFIG('pid_filename');

     if length(pidpath)=0 then begin
       SQUID_SET_CONFIG('pid_filename',SQUID_DETERMINE_PID_PATH());
       SQUID_STOP();
       SQUID_START();
       pidpath:=SQUID_GET_CONFIG('pid_filename');
    end;

    if Not FileExists(pidpath) then exit(SYS.PIDOF(binpath));

    result:=SYS.GET_PID_FROM_PATH(pidpath);
    if not SYS.PROCESS_EXIST(result) then result:=SYS.PIDOF(binpath);

    


end;
//##############################################################################
PROCEDURE tsquid.REMOVE();
begin
SQUID_STOP();
fpsystem('/usr/share/artica-postfix/bin/setup-ubuntu --remove "squid"');
fpsystem('/usr/share/artica-postfix/bin/setup-ubuntu --remove "squid3"');
if FIleExists(SQUID_BIN_PATH()) then logs.DeleteFile(SQUID_BIN_PATH());
logs.DeleteFile('/etc/artica-postfix/versions.cache');
fpsystem('/usr/share/artica-postfix/bin/artica-install --write-versions &');
fpsystem('/usr/share/artica-postfix/bin/process1 --force &');
fpsystem('/etc/init.d/artica-postfix restart artica-status');
end;
//#############################################################################


PROCEDURE tsquid.SQUID_RRD_EXECUTE();
var
   TL            :TstringList;
   http_port     :string;
   script_path   :string;
   l             :TstringList;
   RegExpr       :TRegExpr;
begin
     if not FileExists(SQUID_BIN_PATH()) then exit;
     http_port:=SQUID_GET_SINGLE_VALUE('http_port');
     RegExpr:=TRegExpr.Create;
     RegExpr.Expression:='([0-9]+)';
     if RegExpr.Exec(http_port) then http_port:= RegExpr.Match[1];
     
     if length(http_port)=0 then begin
         Logs.logs('SQUID_RRD_EXECUTE():: unable to stat http_port in squid.conf');
     end;
     script_path:=artica_path+ '/bin/install/rrd/squid-rrd.pl';
     if not FileExists(script_path) then begin
        Logs.logs('SQUID_RRD_EXECUTE():: '+artica_path+ '/bin/install/rrd/squid-rrd.pl');
        exit;
     end;
     
     l:=TstringList.Create;
     l.LoadFromFile(script_path);
     if l.Count<5 then begin
        logs.Syslogs('WARNING SQUID_RRD_EXECUTE is empty...');
        if FileExists(artica_path+ '/bin/install/rrd/squid-rrd.pl.bak') then begin
                   logs.Syslogs('restoring script squid-rrd.pl');
                   logs.OutputCmd('/bin/cp '+artica_path+ '/bin/install/rrd/squid-rrd.pl.bak '+script_path);
                   logs.OutputCmd('/bin/chmod 777 '+script_path);
        end;
     end;
     l.free;
     

     logs.OutputCmd(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid-rrd.php');
     if FileExists(artica_path + '/bin/install/rrd/squid-rrdex.pl') then begin
        ForceDirectories('/opt/artica/share/www/squid/rrd');
        if not FileExists('/etc/cron.d/artica-squidRRD0') then begin
         TL:=TstringList.Create;
         TL.Add('#This generate rrd pictures from squid statistics');
         TL.Add('1,2,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * * root ' + artica_path + '/bin/install/rrd/squid-rrdex.pl >/dev/null 2>&1');
         Logs.logs('SQUID_RRD_EXECUTE():: Restore /etc/cron.d/artica-squidRRD0');
         TL.SaveToFile('/etc/cron.d/artica-squidRRD0');
         TL.free;
        end;
    end;

     //


end;
//##############################################################################
function tsquid.SQUID_GET_SINGLE_VALUE(key:string):string;
var
   RegExpr      :TRegExpr;
   tmp          :TstringList;
   i            :integer;
begin
     result:='';
     if not FileExists(SQUID_CONFIG_PATH()) then begin
        LOGS.logs('SQUID_GET_SINGLE_VALUE() -> unable to get squid.conf');
        exit;
     end;
   tmp:=TstringList.Create;
   tmp.LoadFromFile(SQUID_CONFIG_PATH());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^' + key+'\s+(.+)';


 for i:=0 to tmp.Count-1 do begin
      if RegExpr.Exec(tmp.Strings[i]) then begin
         result:=trim(RegExpr.Match[1]);
         break;
      end;
 end;
    tmp.free;

end;
//##############################################################################
PROCEDURE tsquid.SQUID_RRD_INSTALL();
var
   TL     :TstringList;
   i      :integer;
   RegExpr:TRegExpr;
   script_path:string;
   script_path_bak:string;
begin
  //usr/local/bin/rrdcgi
  script_path:=artica_path+ '/bin/install/rrd/squid-rrd.pl';
  script_path_bak:=artica_path+ '/bin/install/rrd/squid-rrd.bak';


  if not FileExists(script_path) then begin
      if FileExists(script_path_bak) then logs.OutputCmd('/bin/cp ' + script_path_bak + ' ' +  script_path);
  end;

  if not FileExists(script_path) then exit;
  if SYS.FileSize_ko(script_path)<5 then logs.OutputCmd('/bin/cp ' + script_path_bak + ' ' +  script_path);


  TL:=TStringList.Create;
  if not FileExists(script_path) then exit;
  RegExpr:=TRegExpr.Create;

  TL.LoadFromFile(script_path);
  for i:=0 to TL.Count-1 do begin
      RegExpr.Expression:='my \$rrdtool';
      if RegExpr.Exec(TL.Strings[i]) then TL.Strings[i]:='my $rrdtool = "' + SYS.RRDTOOL_BIN_PATH() + '";';
      RegExpr.Expression:='my \$rrd_database_path';
      if RegExpr.Exec(TL.Strings[i]) then TL.Strings[i]:='my $rrd_database_path = "/opt/artica/var/rrd";';
  end;

  TL.SaveToFile(script_path);
  TL.Free;
  fpsystem('/bin/chmod 777 ' + script_path);

end;
//##############################################################################
procedure tsquid.SQUID_STOP_LISTEN_PROCESSES();
 var
    port:string;
    count:integer;
    i:integer;
    binpath:string;
    FileTemp:string;
    l:Tstringlist;
    RegExpr:TRegExpr;
    cmdline:string;
begin
binpath:=SYS.LOCATE_GENERIC_BIN('lsof');
port:=GET_SQUID_PORT();
writeln('Stopping Squid...............: checking ghost processes listen on port '+Port);
FileTemp:=logs.FILE_TEMP();
cmdline:=binpath+' -i TCP:'+port+' >'+FileTemp +' 2>&1';
fpsystem(cmdline);
l:=Tstringlist.Create;
l.LoadFromFile(FileTemp);
logs.Debuglogs(cmdline+' = > '+INtTOstr(l.count)+' line(s)');
logs.DeleteFile(FileTemp);
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='.+?\s+([0-9]+)\s+.+?TCP\s+(.+?):'+port+'\s+';
for i:=0 to l.Count-1 do begin
     logs.Debuglogs(l.Strings[i]);
     if RegExpr.Exec(l.Strings[i]) then begin
          writeln('Stopping Squid...............: stop process PID '+RegExpr.Match[1]+' listen on '+RegExpr.Match[2]+':'+port);
          fpsystem('/bin/kill -9 '+RegExpr.Match[1]);
     end;
end;



end;
//##############################################################################
procedure tsquid.SQUID_STOP_GHOST();
 var
    pid:string;
    count:integer;
    i:integer;
    binpath:string;
    FileTemp:string;
    configpath:string;
    oldproc:integer;
begin


binpath:=SQUID_BIN_PATH();
writeln('Stopping Squid...............: checking ghost processes');
writeln('Stopping Squid...............: binary is '+binpath);
pid:=SYS.PIDOF(binpath);
if not SYS.PROCESS_EXIST(pid) then begin
   SQUID_STOP_LISTEN_PROCESSES();
   exit;
end;
 while SYS.PROCESS_EXIST(pid) do begin
        if length(trim(pid))>0 then begin
           writeln('Stopping Squid...............: stopping other process "' + pid + '" PID');
           fpsystem('/bin/kill -9 '+pid);
        end;
        sleep(200);
        inc(count);
        if count>30 then break;
        pid:=SYS.PIDOF(binpath);
   end;

pid:=SYS.PIDOF_PATTERN('\(squid-[0-9]+\)\s+');
 while SYS.PROCESS_EXIST(pid) do begin
        if length(trim(pid))>0 then begin
           writeln('Stopping Squid...............: stopping other process "' + pid + '" PID');
           fpsystem('/bin/kill -9 '+pid);
        end;
        sleep(200);
        inc(count);
        if count>30 then break;
       pid:=SYS.PIDOF_PATTERN('\(squid-[0-9]+\)\s+');
   end;


   SQUID_STOP_LISTEN_PROCESSES();


end;
//##############################################################################
procedure tsquid.SQUID_STOP();
 var
    pid:string;
    count:integer;
    i:integer;
    binpath:string;
    FileTemp:string;
    configpath:string;
    oldproc:integer;
    l:Tstringlist;
    killbin:string;
begin

pid:=trim(logs.ReadFromFile(MyPidPath));
if(pid<>MyPID) then begin
    if SYS.PROCESS_EXIST(pid) then begin
         logs.Debuglogs('Starting......: squid, artica-install already running PID '+pid+' aborting');
         exit;
    end;
end;
logs.WriteToFile(MyPID,MyPidPath);

count:=0;
binpath:=SQUID_BIN_PATH();
configpath:=SQUID_CONFIG_PATH();
SYS.MONIT_DELETE('APP_SQUID');
killbin:=SYS.LOCATE_GENERIC_BIN('kill');
  if not FileExists(binpath) then exit;
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.watchdog.php  --stop --bydaemon');



if not SYS.PROCESS_EXIST(pid) then begin
     writeln('Stopping Squid...............: Success stopping Squid daemon');
     if not DirectoryExists('/opt/urlfilterbox') then begin
       FileTemp:=logs.FILE_TEMP();
       writeln('Stopping Squid...............: Checking transparent mode...');
       AS_TRANSPARENT_MODE();
       writeln('Stopping Squid...............: Stopping artica watchdog logger');
       TAIL_STOP();
       TAIL_SOCK_STOP();
       writeln('Stopping Squid...............: Stopping artica squidclamav logger');
       TAIL_SQUIDCLAMAV_STOP();
     end;
  end;
end;
//##############################################################################

function tsquid.SQUID_OLD_PROCESS():integer;
var
   TL     :TstringList;
   i      :integer;
   RegExpr:TRegExpr;
   tmpstr:string;
   script_path_bak:string;
begin
result:=0;
    tmpstr:=logs.FILE_TEMP();
    fpsystem('/usr/bin/pgrep -l -f "\(squid\)" >'+tmpstr+' 2>&1');
    tl:=tstringlist.Create;
    tl.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='^([0-9]+)\s+';
    for i:=0 to tl.count-1 do begin
      if RegExpr.exec(tl.Strings[i]) then begin
         TryStrToInt(RegExpr.Match[1],result);
         break;
      end;
    end;
     tl.free;
      RegExpr.free;

end;

function tsquid.SQUID_STATUS():string;
var
  pidpath:string;
begin
 if not FileExists(SQUID_BIN_PATH()) then exit;
 SYS.MONIT_DELETE('APP_SQUID');
 pidpath:=logs.FILE_TEMP();
 fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --all-squid >'+pidpath +' 2>&1');
 result:=logs.ReadFromFile(pidpath);
 logs.DeleteFile(pidpath);
end;
//##############################################################################
function tsquid.SQUID_VERSION():string;
var
   tmp            :string;
   RegExpr        :TRegExpr;
   tmpstr         :string;
   squidbin       :string;
begin
   result:='';
   if not SYS.COMMANDLINE_PARAMETERS('--squid-version-bin') then result:=SYS.GET_CACHE_VERSION('APP_SQUID');

   if length(result)>2 then begin
      if SYS.verbosed then writeln('tsquid.SQUID_VERSION():',result,' from memory');
      exit;
   end;

   squidbin:=SQUID_BIN_PATH();
   if SYS.verbosed then writeln('tsquid.SQUID_VERSION():',squidbin);
   if not FileExists(squidbin) then begin
      if SYS.verbosed then writeln('tsquid.SQUID_VERSION():not installed');
      exit;
   end;
   fpsystem(SYS.LOCATE_PHP5_BIN() +' /usr/share/artica-postfix/exec.squid.php --compilation-params');
   tmpstr:=logs.FILE_TEMP();
   fpsystem(squidbin + ' -v >'+tmpstr+' 2>&1');
   tmp:=SYS.ReadFileIntoString(tmpstr);
   LOGS.DeleteFile(tmpstr);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='Squid Cache: Version ([0-9\.A-Za-z]+)';
   if RegExpr.Exec(tmp) then result:=RegExpr.Match[1];
   if SYS.verbosed then writeln('tsquid.SQUID_VERSION(): 1534',result);
   RegExpr.Free;
   SYS.SET_CACHE_VERSION('APP_SQUID',result);

end;
//#############################################################################
function tsquid.SQUID_ARP_ACL_ENABLED():integer;
var
   tmp            :string;
   RegExpr        :TRegExpr;
   tmpstr         :string;
   squidbin       :string;
   TempSquidPath  :string;
   D:boolean;
begin
   D:=false;
   D:=SYS.COMMANDLINE_PARAMETERS('--verbose');
   result:=0;
   squidbin:=SQUID_BIN_PATH();

   if SYS.verbosed then writeln('SQUID_ARP_ACL_ENABLED():',squidbin);
   if not FileExists(squidbin) then exit;
   TempSquidPath:='/etc/artica-postfix/SQUID_COMPILE_PARAMS.cache';
   if SYS.FILE_TIME_BETWEEN_MIN(TempSquidPath)>3600 then logs.DeleteFile(TempSquidPath);
   tmpstr:=TempSquidPath;
   if Not FileExists(tmpstr) then fpsystem(squidbin + ' -v >'+tmpstr+' 2>&1');
   tmp:=SYS.ReadFileIntoString(tmpstr);
   LOGS.DeleteFile(tmpstr);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='enable-arp-acl';
   if RegExpr.Exec(tmp) then result:=1;
   if SYS.verbosed then writeln('SQUID_ARP_ACL_ENABLED():',result);
   RegExpr.Free;
   SYS.SET_CACHE_VERSION('APP_SQUID_ARP_ACL',IntTostr(result));

end;
//#############################################################################
function tsquid.SQUID_URL_MAP_ACL_ENABLED():integer;
var
   tmp            :string;
   RegExpr        :TRegExpr;
   tmpstr         :string;
   squidbin       :string;
   TempSquidPath  :string;
   D:boolean;
begin
   D:=false;
   D:=SYS.COMMANDLINE_PARAMETERS('--verbose');
   result:=0;
   squidbin:=SQUID_BIN_PATH();
   if SYS.verbosed then writeln('SQUID_URL_MAP_ACL_ENABLED():',squidbin);
   if not FileExists(squidbin) then exit;

   TempSquidPath:='/etc/artica-postfix/SQUID_COMPILE_PARAMS.cache';
   if SYS.FILE_TIME_BETWEEN_MIN(TempSquidPath)>3600 then logs.DeleteFile(TempSquidPath);

   tmpstr:=TempSquidPath;
   if Not FileExists(tmpstr) then fpsystem(squidbin + ' -v >'+tmpstr+' 2>&1');
   tmp:=SYS.ReadFileIntoString(tmpstr);
   LOGS.DeleteFile(tmpstr);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='enable-url-maps';
   if RegExpr.Exec(tmp) then result:=1;
   if SYS.verbosed then writeln('SQUID_URL_MAP_ACL_ENABLED():',result);
   RegExpr.Free;
   SYS.SET_CACHE_VERSION('SQUID_URL_MAP_ACL_ENABLED',IntTostr(result));

end;
//#############################################################################
function tsquid.SQUID_BIN_VERSION(version:string):int64;
var
   tmp            :string;
   tmp2           :string;
   RegExpr        :TRegExpr;
begin
   result:=0;
   RegExpr:=TRegExpr.Create;
   //3.0.STABLE13-20090214
   RegExpr.Expression:='([0-9]+)\.([0-9]+)\.STABLE([0-9\-]+)';
   if RegExpr.Exec(version) then begin
     tmp:=RegExpr.Match[1]+RegExpr.Match[2];
     tmp2:=RegExpr.Match[3];
     tmp2:=trim(AnsiReplaceText(tmp2,'-',''));
     if length(tmp2)=1 then tmp2:='0'+tmp2;
     if length(tmp2)<10 then tmp2:=tmp2+'00000000';
     tmp:=tmp+tmp2;
     RegExpr.Free;
     if not TryStrToInt64(tmp,result) then writeln('int64 failed');
     exit;
   end;

   RegExpr.Expression:='([0-9]+)\.([0-9]+)\.([0-9]+)';
   if RegExpr.Exec(version) then begin
      tmp:=RegExpr.Match[1]+RegExpr.Match[2];
      tmp2:=RegExpr.Match[3];
      if length(tmp2)=1 then tmp2:='0'+tmp2;
      if length(tmp2)<10 then tmp2:=tmp2+'00000000';
      tmp:=tmp+tmp2;
      if not TryStrToInt64(tmp,result) then writeln('int64 failed');
      RegExpr.Free;
      exit;
   end;



end;
//#############################################################################

PROCEDURE tsquid.SQUID_RRD_INIT();
var
   TL     :TstringList;
   i      :integer;
   stop   :boolean;
   RegExpr:TRegExpr;
   script_path:string;
begin
     if not FileExists(SQUID_BIN_PATH()) then exit;
     stop:=true;
     script_path:=artica_path+ '/bin/install/rrd/squid-builder.sh';

     if not FileExists(artica_path+ '/bin/install/rrd/squid-builder.info') then begin
        Logs.logs('SQUID_RRD_INIT():: unable to stat '+artica_path+ '/bin/install/rrd/squid-builder.info');
        exit;
     end;

     if not FileExists(script_path) then begin
        Logs.logs('SQUID_RRD_INIT():: unable to stat '+script_path);
        exit;
     end;


     TL:=TStringList.Create;
     TL.LoadFromFile(artica_path+ '/bin/install/rrd/squid-builder.info');

     For i:=0 to TL.Count-1 do begin
          if not FileExists('/opt/artica/var/rrd/' + TL.Strings[i]) then begin
             stop:=false;
             break;
          end;
     end;

     SQUID_RRD_INSTALL();
     if stop=true then exit;
     Logs.Debuglogs('SQUID_RRD_INIT():: Set settings');
     RegExpr:=TRegExpr.Create;


     TL.LoadFromFile(script_path);

     For i:=0 to TL.Count-1 do begin
         RegExpr.Expression:='PATH="(.+)';
         if RegExpr.Exec(TL.Strings[i]) then TL.Strings[i]:='PATH="/opt/artica/var/rrd"';

         RegExpr.Expression:='RRDTOOL="(.+)';
         if RegExpr.Exec(TL.Strings[i]) then TL.Strings[i]:='RRDTOOL="' + SYS.RRDTOOL_BIN_PATH()+'"';

     end;

    TL.SaveToFile(script_path);
    logs.DebugLogs('Starting......: Creating and set rrd parameters for squid OK');
    TL.Free;
    fpsystem('/bin/chmod 777 ' + script_path);
    forcedirectories('/opt/artica/var/rrd');
    fpsystem(script_path);


end;
//##############################################################################
function tsquid.ReadFileIntoString(path:string):string;
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
function tsquid.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
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
function tsquid.get_INFOS(key:string):string;
var value:string;
begin
GLOBAL_INI:=TIniFile.Create('/etc/artica-postfix/artica-postfix.conf');
value:=GLOBAL_INI.ReadString('INFOS',key,'');
result:=value;
GLOBAL_INI.Free;
end;
//#############################################################################
procedure tsquid.WRITE_INITD();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initd-squid.php');
end;

//#############################################################################
procedure tsquid.SARG_EXECUTE();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.sarg.php --exec');
end;
//#############################################################################
function tsquid.SARG_SCAN():string;
var
   RegExpr        :TRegExpr;
   l:Tstringlist;
   i:Integer;

begin
  if not FileExists('/usr/bin/sarg') then begin
   logs.DebugLogs('Starting......: SARG Is not installed');
   exit;
end;
  RegExpr:=TRegExpr.Create;
  SYS.DirDir('/usr/share/artica-postfix/squid/sarg');
  l:=Tstringlist.Create;
  RegExpr.Expression:='(.+?)-(.+)';
  for i:=0 to SYS.DirListFiles.Count-1 do begin
      if SYS.DirListFiles.Strings[i]='sarg-php' then continue;
      if RegExpr.Exec(SYS.DirListFiles.Strings[i]) then  begin
            l.Add(SYS.DirListFiles.Strings[i]);
      end;
  end;
    result:=l.Text;
    l.Free;
    RegExpr.free;

end;
//#############################################################################


procedure tsquid.SARG_CONFIG();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.sarg.php --conf');
end;
//#############################################################################
function tsquid.SARG_VERSION():string;
var
   tmp            :string;
   RegExpr        :TRegExpr;
   l:Tstringlist;
   i:Integer;
   sarg_bin:string;

begin
   result:='';
  sarg_bin:=SYS.LOCATE_GENERIC_BIN('sarg');
  tmp:=logs.FILE_TEMP();
  result:=SYS.GET_CACHE_VERSION('APP_SARG');
  if length(result)>0 then exit;
   if not FileExists(sarg_bin) then exit;
   fpsystem(sarg_bin+' -v >'+tmp+' 2>&1');

   l:=Tstringlist.Create;
   l.LoadFromFile(tmp);
   LOGS.DeleteFile(tmp);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:=':\s+([0-9\.A-Za-z]+)';
   For i:=0 to l.Count-1 do begin
   if RegExpr.Exec(l.Strings[i]) then begin
      result:=RegExpr.Match[1];
      SYS.SET_CACHE_VERSION('APP_SARG',result);
   end;
   end;

   RegExpr.Free;
   l.free;


end;
//#############################################################################
function tsquid.TAIL_PID():string;
var
   pid:string;
begin

if FileExists('/etc/artica-postfix/exec.squid-tail.php.pid') then begin
   pid:=SYS.GET_PID_FROM_PATH('/etc/artica-postfix/exec.squid-tail.php.pid');
   logs.Debuglogs('TAIL_PID /etc/artica-postfix/exec.squid-tail.php.pid='+pid);
   if SYS.PROCESS_EXIST(pid) then result:=pid;
   exit;
end;


result:=SYS.PIDOF_PATTERN(TAIL_STARTUP);
logs.Debuglogs(TAIL_STARTUP+' pid='+pid);
end;
//#####################################################################################
function tsquid.TAIL_SOCK_PID():string;
var
   pid:string;
begin

if FileExists('/etc/artica-postfix/pids/exec.squid2.logger.php.pid') then begin
   pid:=SYS.GET_PID_FROM_PATH('/etc/artica-postfix/pids/exec.squid2.logger.php.pid');

   if SYS.PROCESS_EXIST(pid) then begin
      logs.Debuglogs('TAIL_SOCK_PID /etc/artica-postfix/exec.squid-tail.php.pid='+pid);
      result:=pid;
      exit;
   end;
end;
result:=SYS.PIDOF_PATTERN('exec.squid2.logger.php');

end;
//#####################################################################################
procedure tsquid.TAIL_SOCK_START(norestart:boolean);
var
   pid:string;
   pidint:integer;
   php5:string;
   count:integer;
   cmd:string;
   CountTail:Tstringlist;
begin
   if not FileExists(SQUID_BIN_PATH()) then begin
   logs.Debuglogs('Starting......: squid RealTime log (sock mode) squid is not installed');
   exit;
end;

 pid:=TAIL_SOCK_PID();
  if SYS.PROCESS_EXIST(pid) then begin
      logs.Debuglogs('Starting......: squid RealTime log (sock mode) depreciated');
      TAIL_SOCK_STOP();
  end;

  logs.Debuglogs('Starting......: squid RealTime log (sock mode) depreciated');
  exit;

  php5:=SYS.LOCATE_PHP5_BIN();
  logs.DebugLogs('Starting......: squid RealTime log (sock mode) ...');
  fpsystem(php5 +' /usr/share/artica-postfix/exec.squid2.logger.php');
  count:=0;
while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting......: squid RealTime log (sock mode) (timeout)');
           break;
        end;
        pid:=TAIL_SOCK_PID();
  end;

pid:=TAIL_SOCK_PID();
if SYS.PROCESS_EXIST(pid) then begin
     logs.DebugLogs('Starting......: squid RealTime log (sock mode) (2)...');
     fpsystem(php5 +' /usr/share/artica-postfix/exec.squid2.logger.php');
     count:=0;
  while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting......: squid RealTime log (sock mode) (timeout)');
           break;
        end;
        pid:=TAIL_SOCK_PID();
  end;
end;
pid:=TAIL_SOCK_PID();
if SYS.PROCESS_EXIST(pid) then begin
     logs.DebugLogs('Starting......: squid RealTime log (sock mode) (3)...');
     fpsystem(php5 +' /usr/share/artica-postfix/exec.squid2.logger.php');
     count:=0;
  while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting......: squid RealTime log (sock mode) (timeout)');
           break;
        end;
        pid:=TAIL_SOCK_PID();
  end;
end;

if SYS.PROCESS_EXIST(pid) then begin
      sleep(2000);
      cmd:=trim(logs.ReadFromFile('/etc/artica-postfix/pids/squid-tail-sock'));
      if cmd<>'OK' then begin
         logs.DebugLogs('Starting......: squid RealTime log (sock mode) socket error result:'+cmd);
         TAIL_SOCK_STOP();
         TAIL_SOCK_START(false);
         exit;
      end;
      logs.DebugLogs('Starting......: squid RealTime log (sock mode) success with pid '+pid);
      if SYS.PROCESS_EXIST(SQUID_PID()) then fpsystem(SQUID_BIN_PATH()+' -k reconfigure >/dev/null 2>&1');
      exit;
end else begin
    logs.DebugLogs('Starting......: squid RealTime log (sock mode) failed');
    if not norestart then begin
       TAIL_SOCK_STOP();
       TAIL_SOCK_START(true);
    end;
end;

end;
//#####################################################################################

procedure tsquid.TAIL_START();
var
   pid:string;
   pidint:integer;
   log_path:string;
   count:integer;
   cmd:string;
   CountTail:Tstringlist;
begin

if not FileExists(SQUID_BIN_PATH()) then begin
   logs.Debuglogs('Starting......: squid RealTime log squid is not installed');
   exit;
end;
logs.Debuglogs('Starting......: squid RealTime log is depreciated, exiting.');
end;
//#####################################################################################
procedure tsquid.TAIL_STOP();
var
   pid:string;
   pidint,i:integer;
   count:integer;
   CountTail:Tstringlist;
   pidDaemon:string;
begin

pid:=TAIL_PID();



if not SYS.PROCESS_EXIST(pid) then begin
      writeln('Stopping squid RealTime log: Already stopped');
      CountTail:=Tstringlist.Create;
      try
         CountTail.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST('/usr/bin/tail -f -n 0 /var/log/squid/access.log'));
         writeln('Stopping squid RealTime log: Tail processe(s) number '+IntToStr(CountTail.Count));
      except
        logs.Debuglogs('Stopping squid RealTime log: fatal error on SYS.PIDOF_PATTERN_PROCESS_LIST() function');
      end;

      count:=0;
     for i:=0 to CountTail.Count-1 do begin;
          pid:=CountTail.Strings[i];
          if count>100 then break;
          if not TryStrToInt(pid,pidint) then continue;
          writeln('Stopping squid RealTime log: Stop tail pid '+pid);
          if pidint>0 then  fpsystem('/bin/kill '+pid);
          sleep(100);
          inc(count);
      end;
      exit;
end;

writeln('Stopping squid RealTime log: Stopping pid '+pid);
fpsystem('/bin/kill '+pid);

pid:=TAIL_PID();
if not SYS.PROCESS_EXIST(pid) then begin
      writeln('Stopping squid RealTime log: Stopped');
end;


CountTail:=Tstringlist.Create;
CountTail.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST('/usr/bin/tail -f -n 0 /var/log/squid/access.log'));
writeln('Stopping squid RealTime log: Tail processe(s) number '+IntToStr(CountTail.Count));
count:=0;
     for i:=0 to CountTail.Count-1 do begin;
          pid:=CountTail.Strings[i];
          if count>100 then break;
          if not TryStrToInt(pid,pidint) then continue;
          writeln('Stopping squid RealTime log: Stop tail pid '+pid);
          if pidint>0 then  fpsystem('/bin/kill '+pid);
          sleep(100);
          inc(count);
      end;


end;
//####################################################################################
procedure tsquid.TAIL_SOCK_STOP();
var
   pid,pidDaemon:string;
   pidint,i:integer;
   count:integer;
   CountPgrep:Tstringlist;
   pgrep,tmpstr:string;
   RegExpr:TRegExpr;
   MustWait:boolean;
begin

pidDaemon:=trim(logs.ReadFromFile('/etc/artica-postfix/pids/exec.squid2.logger.php.D.pid'));
if SYS.PROCESS_EXIST(pidDaemon) then begin
    writeln('Stopping squid RealTime log: (sock mode) Daemon PID:"'+pidDaemon+'"');
    fpsystem('/bin/kill -9 '+pidDaemon);
end;

pid:=TAIL_SOCK_PID();
MustWait:=false;
if SYS.PROCESS_EXIST(pid) then begin
writeln('Stopping squid RealTime log: (sock mode) Stopping pid '+pid);
fpsystem('/bin/kill '+pid);
MustWait:=true;
pid:=TAIL_SOCK_PID();
writeln('Stopping squid RealTime log: (sock mode) stop pid "'+pid+'"');
count:=0;
      while SYS.PROCESS_EXIST(pid) do begin
          if count>30 then begin
             writeln('Stopping squid RealTime log: (sock mode) stop pid "'+pid+'" (timeout)');
             break;
          end;

          if not TryStrToInt(pid,pidint) then continue;
          if pidint>0 then  if SYS.PROCESS_EXIST(pid) then fpsystem('/bin/kill '+pid);
          sleep(200);
          pid:=TAIL_SOCK_PID();
          inc(count);
      end;
end;


pgrep:=SYS.LOCATE_GENERIC_BIN('pgrep');
tmpstr:=logs.FILE_TEMP();
if FileExists(pgrep) then begin
 fpsystem(pgrep+' -f "exec.squid2.logger.php" >'+tmpstr+' 2>&1');
 CountPgrep:=Tstringlist.Create();
 CountPgrep.LoadFromFile(tmpstr);
 logs.DeleteFile(tmpstr);
 RegExpr:=TRegExpr.Create;
 for i:=0 to CountPgrep.Count-1 do begin
    RegExpr.Expression:='^([0-9]+)';
    if RegExpr.Exec(CountPgrep.Strings[i]) then begin
       if SYS.PROCESS_EXIST(RegExpr.Match[1]) then begin
             writeln('Stopping squid RealTime log: (sock mode) stop pid "'+RegExpr.Match[1]+'" L.2180');
             fpsystem('/bin/kill '+RegExpr.Match[1]);
             MustWait:=true;
       end;
    end;
 end;
end;
if TAIL_SOCK_STOP_LISTEN_PROCESSES() then MustWait:=true;
if MustWait then sleep(2000);
pid:=TAIL_SOCK_PID();
if not SYS.PROCESS_EXIST(pid) then begin
      writeln('Stopping squid RealTime log: (sock mode) Stopped');
end;

end;
//####################################################################################
function tsquid.TAIL_SOCK_STOP_LISTEN_PROCESSES():boolean;
 var
    port:integer;
    count:integer;
    i:integer;
    binpath:string;
    FileTemp:string;
    l:Tstringlist;
    RegExpr:TRegExpr;
    cmdline:string;
begin
result:=false;
binpath:=SYS.LOCATE_GENERIC_BIN('lsof');
if not TryStrToInt(SYS.GET_INFO('SquidTailSockPort'),port) then port:=54424;

 writeln('Stopping squid RealTime log: (sock mode) processes listen on port ',Port);
FileTemp:=logs.FILE_TEMP();
cmdline:=binpath+' -i TCP:'+IntToStr(port)+' >'+FileTemp +' 2>&1';
fpsystem(cmdline);
l:=Tstringlist.Create;
l.LoadFromFile(FileTemp);
logs.Debuglogs(cmdline+' = > '+INtTOstr(l.count)+' line(s)');
logs.DeleteFile(FileTemp);
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='.+?\s+([0-9]+)\s+.+?TCP\s+(.+?):'+IntToStr(port)+'\s+';
for i:=0 to l.Count-1 do begin
     logs.Debuglogs(l.Strings[i]);
     if RegExpr.Exec(l.Strings[i]) then begin
          writeln('Stopping squid RealTime log: (sock mode) stop process PID `'+RegExpr.Match[1]+'` listen on '+RegExpr.Match[2]+': for port:',port);
          fpsystem('/bin/kill '+RegExpr.Match[1]);
          result:=true;
     end;
end;



end;
//##############################################################################


function  tsquid.PROXY_PAC_PID():string;
begin
result:=SYS.GET_PID_FROM_PATH('/var/run/proxypac.pid');
end;
//####################################################################################
procedure tsquid.PROXY_PAC_START();
var
   pid:string;
   count:integer;
begin

if not FileExists(SQUID_BIN_PATH()) then begin
   logs.Debuglogs('Starting......: proxy.pac service, squid is not installed');
   exit;
end;


if SquidEnableProxyPac=0 then begin
   logs.Debuglogs('Starting......: proxy.pac service is disabled');
   PROXY_PAC_STOP();
   exit;
end;


pid:=PROXY_PAC_PID();
if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: proxy.pac service log already running with pid '+pid);
      exit;
end;

  logs.DebugLogs('Starting......: proxy.pac service');
 fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.proxy.pac.php');
 logs.OutputCmd(SYS.LOCATE_GENERIC_BIN('lighttpd')+ ' -f /etc/lighttpd/proxypac.conf');

pid:=PROXY_PAC_PID();
count:=0;
while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting......: proxy.pac service (timeout)');
           break;
        end;
        pid:=PROXY_PAC_PID();
  end;

pid:=PROXY_PAC_PID();

if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: proxy.pac service success with pid '+pid);
      exit;
end else begin
    logs.DebugLogs('Starting......: proxy.pac service failed');
end;

end;
//####################################################################################
procedure tsquid.PROXY_PAC_STOP();
var
   pid:string;
   count:integer;
begin
if not FileExists(SQUID_BIN_PATH()) then begin
   writeln('Stopping proxy.pac service...: squid is not installed');
   exit;
end;
   pid:=PROXY_PAC_PID();

   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping proxy.pac service...: Already stopped');
       exit;
   end;
   writeln('Stopping proxy.pac service...: PID '+pid);
   fpsystem('/bin/kill '+ pid);
   count:=0;
  while sys.PROCESS_EXIST(pid) do begin
      sleep(100);
      fpsystem('/bin/kill '+ pid);
      inc(count);
      if count>50 then begin
         writeln('Stopping proxy.pac service...: time-out');
         logs.OutputCmd('/bin/kill -9 ' + pid);
         break;
      end;
      pid:=PROXY_PAC_PID();
  end;
pid:=PROXY_PAC_PID();
   if not sys.PROCESS_EXIST(pid) then begin
       writeln('Stopping proxy.pac service...: stopped');
       exit;
   end;
   writeln('Stopping proxy.pac service...: failed');
end;
//##############################################################################
procedure tsquid.zapper_install();
var
   l:tstringlist;
   i:integer;
begin
l:=Tstringlist.create;
l.add('chkzap');
l.add('squid_redirect');
l.add('testpageurls');
l.add('testzap');
l.add('update-zapper');
l.add('update-zapper.damien');
l.add('wrapzap');
l.add('zapchain');

for i:=0 to l.Count-1 do begin
    if not FileExists('/usr/bin/'+l.Strings[i]) then begin
       if FileExists('/usr/share/artica-postfix/bin/install/squid/adzap/scripts/'+l.Strings[i]) then begin
          fpsystem('/bin/cp /usr/share/artica-postfix/bin/install/squid/adzap/scripts/'+l.Strings[i]+' /usr/bin/'+l.Strings[i]);
          logs.DebugLogs('Starting......: squid installing '+l.Strings[i]);
          fpsystem('/bin/chmod 755 /usr/bin/'+l.Strings[i]);
          fpsystem('/bin/chown squid:squid /usr/bin/'+l.Strings[i]);
       end;
    end;
end;
end;
//#####################################################################################
function tsquid.TAIL_SQUIDCLAMAV_PID():string;
var
   pid:string;
   startup:string;
begin

if FileExists('/etc/artica-postfix/exec.squid-clamav-tail.php.pid') then begin
   pid:=SYS.GET_PID_FROM_PATH('/etc/artica-postfix/exec.squid-clamav-tail.php.pid');
   logs.Debuglogs('DANSGUARDIAN_TAIL_PID /etc/artica-postfix/exec.squid-clamav-tail.php.pid='+pid);
   if SYS.PROCESS_EXIST(pid) then result:=pid;
   exit;
end;

startup:=SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid-clamav-tail.php';
result:=SYS.PIDOF_PATTERN(TAIL_STARTUP);
logs.Debuglogs(TAIL_STARTUP+' pid='+pid);
end;
//#####################################################################################


procedure tsquid.TAIL_SQUIDCLAMAV_START();
var
   pid:string;
   pidint:integer;
   log_path:string;
   count:integer;
   cmd,startup:string;
   CountTail:Tstringlist;
begin

if not FileExists(SQUID_BIN_PATH()) then begin
   logs.Debuglogs('Starting......: squidClamav RealTime log squid is not installed');
   exit;
end;
if not FileExists(SYS.LOCATE_GENERIC_BIN('squidclamav')) then begin
   logs.Debuglogs('Starting......: squidClamav is not installed');
   exit;
end;

if EnableSquidClamav=0 then begin
    logs.Debuglogs('Starting......: squidClamav RealTime log squidClamav is disabled');
    TAIL_SQUIDCLAMAV_STOP();
    exit;
end;
startup:=SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid-clamav-tail.php';
pid:=TAIL_SQUIDCLAMAV_PID();
if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: squidClamav RealTime log already running with pid '+pid);
      if DansGuardianEnabled=1 then TAIL_STOP();
      CountTail:=Tstringlist.Create;
      CountTail.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST('/usr/bin/tail -f -n 0 /var/log/squid/squidclamav.log'));
      logs.DebugLogs('Starting......: squid RealTime log process number:'+IntToStr(CountTail.Count));
      if CountTail.Count>3 then fpsystem('/etc/init.d/artica-postfix restart squidclamav-tail');
      CountTail.free;
      exit;
end;
log_path:='/var/log/squid/squidclamav.log';

if not FileExists(log_path) then begin
   logs.DebugLogs('Starting......: squid squidClamav log stats, unable to stats logfile');
   exit;
end;
TAIL_STOP();
logs.DebugLogs('Starting......: squid squidClamav log path: '+log_path);

pid:=SYS.PIDOF_PATTERN('/usr/bin/tail -f -n 0 '+log_path);
count:=0;
pidint:=0;
      while SYS.PROCESS_EXIST(pid) do begin
          if count>0 then break;
          if not TryStrToInt(pid,pidint) then continue;
          logs.DebugLogs('Starting......: squidClamav RealTime log stop tail pid '+pid);
          if pidint>0 then  fpsystem('/bin/kill '+pid);
          sleep(200);
          pid:=SYS.PIDOF_PATTERN('/usr/bin/tail -f -n 0 '+log_path);
          inc(count);
      end;

cmd:='/usr/bin/tail -f -n 0 '+log_path+'|'+startup+' >>/var/log/artica-postfix/squid-logger-start.log 2>&1 &';
logs.Debuglogs(cmd);
fpsystem(cmd);
pid:=TAIL_SQUIDCLAMAV_PID();
count:=0;
while not SYS.PROCESS_EXIST(pid) do begin
        sleep(100);
        inc(count);
        if count>40 then begin
           logs.DebugLogs('Starting......: squidClamav RealTime log (timeout)');
           break;
        end;
        pid:=TAIL_SQUIDCLAMAV_PID();
  end;

pid:=TAIL_SQUIDCLAMAV_PID();

if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: squidClamav RealTime log success with pid '+pid);
      exit;
end else begin
    logs.DebugLogs('Starting......: squidClamav RealTime log failed');
end;
end;
//#####################################################################################
procedure tsquid.mounttmpfs();
         const
            CR = #$0d;
            LF = #$0a;
            CRLF = CR + LF;
var
 MemTotal:integer;
 tmpfsmem:integer;
 procnum:integer;
 i:integer;
begin
    MemTotal:=GetFreeMem();
    tmpfsmem:=round( MemTotal*0.33);
    fpsystem(SYS.LOCATE_GENERIC_BIN('umount')+' -l /var/spool/squid3');
    fpsystem(SYS.LOCATE_GENERIC_BIN('mount')+' -t tmpfs -o size='+IntTostr(tmpfsmem)+'k tmpfs /var/spool/squid3');
    fpsystem(SYS.LOCATE_GENERIC_BIN('chown')+' squid:squid  /var/spool/squid3');
    fpsystem(SYS.LOCATE_GENERIC_BIN('squid') +' -z');
    procnum:=SYS.CPU_NUMBER();
    if not directoryExists('/var/spool/squid3-0/00') then begin
            forcedirectories('/var/spool/squid3-0');
            fpsystem(SYS.LOCATE_GENERIC_BIN('chown')+' squid:squid  /var/spool/squid3-0');
    end;

   for i:=1 to procnum do begin
           forcedirectories('/var/spool/squid3-'+intTostr(i));
           fpsystem(SYS.LOCATE_GENERIC_BIN('chown')+' squid:squid  /var/spool/squid3-'+intTostr(i));
   end;
    logs.WriteToFile('CPU:'+IntTostr(procnum)+CRLF+'MEM:'+IntTostr(tmpfsmem),'/opt/urlfilterbox/squid.vals');

end;
procedure tsquid.TAIL_SQUIDCLAMAV_STOP();
var
   pid:string;
   pidint,i:integer;
   count:integer;
   CountTail:Tstringlist;
begin
pid:=TAIL_SQUIDCLAMAV_PID();
if not SYS.PROCESS_EXIST(pid) then begin
      writeln('Stopping squidclamav RealTime log: Already stopped');
      CountTail:=Tstringlist.Create;
      try
         CountTail.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST('/usr/bin/tail -f -n 0 /var/log/squid/squidclamav.log'));
         writeln('Stopping squidclamav RealTime log: Tail processe(s) number '+IntToStr(CountTail.Count));
      except
        logs.Debuglogs('Stopping squidclamav RealTime log: fatal error on SYS.PIDOF_PATTERN_PROCESS_LIST() function');
      end;

      count:=0;
     for i:=0 to CountTail.Count-1 do begin;
          pid:=CountTail.Strings[i];
          if count>100 then break;
          if not TryStrToInt(pid,pidint) then continue;
          writeln('Stopping squidclamav RealTime log: Stop tail pid '+pid);
          if pidint>0 then  fpsystem('/bin/kill '+pid);
          sleep(100);
          inc(count);
      end;
      exit;
end;

writeln('Stopping squidclamav RealTime log: Stopping pid '+pid);
fpsystem('/bin/kill '+pid);

pid:=TAIL_SQUIDCLAMAV_PID();
if not SYS.PROCESS_EXIST(pid) then begin
      writeln('Stopping squidclamav RealTime log: Stopped');
end;


CountTail:=Tstringlist.Create;
CountTail.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST('/usr/bin/tail -f -n 0 /var/log/squid/squidclamav.log'));
writeln('Stopping squidclamav RealTime log: Tail processe(s) number '+IntToStr(CountTail.Count));
count:=0;
     for i:=0 to CountTail.Count-1 do begin;
          pid:=CountTail.Strings[i];
          if count>100 then break;
          if not TryStrToInt(pid,pidint) then continue;
          writeln('Stopping squidclamav RealTime log: Stop tail pid '+pid);
          if pidint>0 then  fpsystem('/bin/kill '+pid);
          sleep(100);
          inc(count);
      end;


end;
//####################################################################################




end.
