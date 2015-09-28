unit articadb;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,IniFiles;



  type
  tarticadb=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     TAIL_STARTUP:string;
     TAIL_LOG_PATH:string;
     EnableArticaDB:integer;
     EnableWebProxyStatsAppliance:integer;
     EnableRemoteStatisticsAppliance:integer;
     UseRemoteUfdbguardService:integer;
     DisableArticaProxyStatistics:integer;
     SquidActHasReverse:integer;
     SQUIDEnable:integer;
     squidpath:string;
     binpath:string;
     mem_installee:integer;
     procedure KILL_PROCESSLIST();
     function SQUID_BIN_PATH():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    procedure   STOP();
    function    PID_NUM():string;
    function    PID_PATH():string;

END;

implementation

constructor tarticadb.Create(const zSYS:Tsystem);
begin

       LOGS:=tlogs.Create();
       SYS:=zSYS;
       binpath:='/opt/articatech/bin/articadb';
       EnableArticaDB:=1;
       mem_installee:=SYS.MEM_TOTAL_INSTALLEE();
       squidpath:= SQUID_BIN_PATH();
       if Not FileExists(squidpath) then EnableArticaDB:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableWebProxyStatsAppliance'),EnableWebProxyStatsAppliance) then EnableWebProxyStatsAppliance:=0;
       if not TryStrToInt(SYS.GET_INFO('DisableArticaProxyStatistics'),DisableArticaProxyStatistics) then DisableArticaProxyStatistics:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableRemoteStatisticsAppliance'),EnableRemoteStatisticsAppliance) then EnableRemoteStatisticsAppliance:=0;
       if not TryStrToInt(SYS.GET_INFO('SquidActHasReverse'),SquidActHasReverse) then SquidActHasReverse:=0;

       if FileExists('/etc/artica-postfix/WEBSTATS_APPLIANCE') then EnableWebProxyStatsAppliance:=1;
       if DisableArticaProxyStatistics=1 then EnableArticaDB:=0;
       if EnableRemoteStatisticsAppliance=1 then EnableArticaDB:=0;
       if EnableWebProxyStatsAppliance=1 then EnableArticaDB:=1;
       if SquidActHasReverse=1 then EnableArticaDB:=0;

end;
//##############################################################################
procedure tarticadb.free();
begin
    LOGS.Free;
end;
//##############################################################################
function tarticadb.SQUID_BIN_PATH():string;
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
procedure tarticadb.STOP();
begin
if not FileExists(binpath) then begin
   writeln('Stopping ArticaDBst..........: Not installed');
   exit;
end;

fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.catz-db.php --stop');
end;

//##############################################################################
procedure tarticadb.KILL_PROCESSLIST();
var
   i:integer;
   cmd:string;
   nohup:string;
   pid:integer;
   l:Tstringlist;
   enabled:integer;
   RegExpr:TRegExpr;
   mysqladmin:string;
   tmpfile:string;
   cmdline:string;

begin
     mysqladmin:=SYS.LOCATE_GENERIC_BIN('mysqladmin');
    if not FileExists(mysqladmin) then exit;
    tmpfile:=logs.FILE_TEMP();
    cmdline:=mysqladmin+' --defaults-file=/opt/articatech/my.cnf --user=root -S /var/run/articadb.sock shutdown >'+tmpfile+' 2>&1';
    fpsystem(cmdline);
    if not Fileexists(tmpfile) then exit;
    l:=TStringlist.Create;
    try
       l.LoadFromFile(tmpfile);
    except
       writeln('Stopping ArticaDBst..........: Fatal error while reading '+tmpfile);
       exit;
    end;
    logs.DeleteFile(tmpfile);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='^\|\s+([0-9]+)\s+|';
    for i:=0 to l.Count-1 do begin
        if not RegExpr.Exec(l.Strings[i]) then continue;
        pid:=0;
        if not TryStrToInt(RegExpr.Match[1],pid) then continue;
        if pid>1 then begin
           writeln('Stopping ArticaDBst..........: Stopping sub-process id:',pid);
           fpsystem(mysqladmin +' --defaults-file=/opt/articatech/my.cnf --user=root -S /var/run/articadb.sock kill '+ IntToStr(pid)+' >/dev/null 2>&1');
        end;
    end;
    L.free;
    RegExpr.free;
end;

procedure tarticadb.START();
var
   count:integer;
   cmd:string;
   su,nohup:string;
   conf:TiniFile;
   enabled:integer;
   RegExpr:TRegExpr;
   servername:string;
   tmpfile:string;
   cmdline:string;

begin

   if not FileExists(binpath) then begin
         logs.DebugLogs('Starting......: ArticaDBst not installed');
         exit;
   end;

if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: ArticaDBst Already running using PID ' +PID_NUM()+ '...');
   exit;
end;
if FileExists('/usr/share/artica-postfix/exec.articadb.php') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.articadb.php --build');
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.catz-db.php --start');

end;

 //##############################################################################
 function tarticadb.PID_NUM():string;
begin
  result:=SYS.GET_PID_FROM_PATH(PID_PATH());
  if sys.verbosed then logs.Debuglogs(' ->'+result);
  if length(result)=0 then result:=SYS.PIDOF_PATTERN(binpath);
  if not SYS.PROCESS_EXIST(result) then result:=SYS.PIDOF_PATTERN(binpath);
end;
 //##############################################################################
function tarticadb.PID_PATH():string;
begin
     exit('/var/run/articadb.pid');
end;
 //##############################################################################
end.
