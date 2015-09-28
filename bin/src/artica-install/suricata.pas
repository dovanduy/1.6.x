unit suricata;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,IniFiles;



  type
  tsuricata=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     TAIL_STARTUP:string;
     TAIL_LOG_PATH:string;
     suricataEnabled:integer;
     suricataInterface:string;
     EnableWebProxyStatsAppliance:integer;
     EnableRemoteStatisticsAppliance:integer;
     UseRemoteUfdbguardService:integer;
     DisableArticaProxyStatistics:integer;
     SQUIDEnable:integer;
     squidpath:string;
     binpath:string;
     mem_installee:integer;
     function CONF_PATH():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    procedure   STOP();
    function    PID_NUM():string;
    function    PID_PATH():string;

END;

implementation

constructor tsuricata.Create(const zSYS:Tsystem);
begin

       LOGS:=tlogs.Create();
       SYS:=zSYS;
       binpath:=SYS.LOCATE_GENERIC_BIN('suricata');
       suricataEnabled:=0;
       mem_installee:=SYS.MEM_TOTAL_INSTALLEE();

       if not TryStrToInt(SYS.GET_INFO('suricataEnabled'),suricataEnabled) then suricataEnabled:=0;
       suricataInterface:=SYS.GET_INFO('suricataInterface');
       if length(suricataInterface)=0 then suricataInterface:='eth0';

end;
//##############################################################################
procedure tsuricata.free();
begin
    LOGS.Free;
end;
//##############################################################################
function tsuricata.CONF_PATH():string;
var
   path:string;
begin

  if FileExists('/etc/suricata/suricata-artica.yaml')  then exit('/etc/suricata/suricata-artica.yaml');

end;
//##############################################################################
procedure tsuricata.STOP();
var
   count,i:integer;
   RegExpr:TRegExpr;
   cmd,cmdline,tmpstr:string;
   pids,l:Tstringlist;
   pidstring:string;
   fpid:integer;
   mysqladmin:string;
begin
if not FileExists(binpath) then begin
   writeln('Stopping Suricata............: Not installed');
   exit;
end;

if not SYS.PROCESS_EXIST(PID_NUM()) then begin
        writeln('Stopping Suricata............: Already Stopped');
        exit;
end;
   pidstring:=PID_NUM();
   writeln('Stopping Suricata............: ' + pidstring + ' PID..');

  if not SYS.PROCESS_EXIST(PID_NUM()) then begin
        writeln('Stopping Suricata............: Stopped');
        exit;
 end;
   cmd:=SYS.LOCATE_GENERIC_BIN('kill')+' '+pidstring+' >/dev/null 2>&1';
   fpsystem(cmd);
   count:=0;

   while SYS.PROCESS_EXIST(pidstring) do begin
        sleep(300);
        count:=count+1;
        if count>50 then begin
            if length(pidstring)>0 then begin
               if SYS.PROCESS_EXIST(pidstring) then begin
                  writeln('Stopping Suricata............: kill pid '+ pidstring+' after '+IntToStr(count)+' CYCLES ');
                  fpsystem('/bin/kill -9 ' + pidstring);
               end;
            end;
            break;
        end;
        pidstring:=PID_NUM();
  end;

  if not SYS.PROCESS_EXIST(PID_NUM()) then begin
     writeln('Stopping Suricata............: Stopped');
  end;
end;

procedure tsuricata.START();
var
   count,i:integer;
   cmd:string;
   su,nohup:string;
   conf:TiniFile;
   enabled:integer;
   RegExpr:TRegExpr;
   servername:string;
   tmpfile:string;
   cmdline:string;
   ConfPath:string;
   l:Tstringlist;
begin

   if not FileExists(binpath) then begin
         logs.DebugLogs('Starting......: Suricata not installed');
         exit;
   end;

if suricataEnabled=0 then begin
   logs.DebugLogs('Starting......: Suricata is disabled Memory: '+IntToStr(suricataEnabled));
   STOP();
   exit;
end;

if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: Suricata Already running using PID ' +PID_NUM()+ '...');
   exit;
end;
nohup:=SYS.LOCATE_GENERIC_BIN('nohup');

if FileExists('/usr/share/artica-postfix/exec.oinkmaster.php') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.oinkmaster.php --build');
ConfPath:=CONF_PATH();
if Not FileExists(ConfPath) then begin
    logs.DebugLogs('Starting......: Suricata server failed, no config path found...');
    exit;
end;
   tmpfile:=logs.FILE_TEMP();
   ForceDirectories('/var/log/suricata');
   logs.DebugLogs('Starting......: Suricata server on "'+suricataInterface+'"...');
   cmd:=nohup+' '+binpath+' -c '+ConfPath+' -l /var/log/suricata -D --pidfile /var/run/suricata.pid -i '+suricataInterface+' >'+tmpfile+' 2>&1 &';
   fpsystem(cmd);
   count:=0;
   while not SYS.PROCESS_EXIST(PID_NUM()) do begin
     sleep(300);
     inc(count);
     if count>50 then begin
       logs.DebugLogs('Starting......: Suricata (timeout!!!)');
       logs.DebugLogs('Starting......: Suricata "'+cmd+'"');
       break;
     end;
   end;

  l:=Tstringlist.Create;
  l.LoadFromFile(tmpfile);
  logs.DeleteFile(tmpfile);

  For i:=0 to l.Count-1 do begin
      logs.DebugLogs('Starting......: Suricata '+ l.Strings[i]);
  end;
  l.free;
  count:=0;


if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: Suricata started with new PID ' +PID_NUM()+ '...');
   exit;
end;
logs.DebugLogs('Starting......: Suricata (failed!!!)');

end;

 //##############################################################################
 function tsuricata.PID_NUM():string;
begin
  result:=SYS.GET_PID_FROM_PATH('/var/run/suricata.pid');
  if sys.verbosed then logs.Debuglogs(' ->'+result);
  if length(result)=0 then result:=SYS.PIDOF_PATTERN(binpath);
  if not SYS.PROCESS_EXIST(result) then result:=SYS.PIDOF_PATTERN(binpath);
end;
 //##############################################################################
function tsuricata.PID_PATH():string;
begin
     exit('/var/run/suricata.pid');
end;
 //##############################################################################
end.
