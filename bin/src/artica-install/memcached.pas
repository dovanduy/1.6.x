unit memcached;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,IniFiles;



  type
  tmemcached=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     TAIL_STARTUP:string;
     TAIL_LOG_PATH:string;
     EnableMemcached:integer;
     binpath:string;

public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    procedure   STOP();
    function    STATUS():string;
    function    PID_NUM():string;
    function    VERSION():string;
    function    BIN_PATH():string;
    function    PID_PATH():string;
END;

implementation

constructor tmemcached.Create(const zSYS:Tsystem);
begin

       LOGS:=tlogs.Create();
       SYS:=zSYS;
       binpath:=BIN_PATH();
       if not TryStrToInt(SYS.GET_INFO('EnableMemcached'),EnableMemcached) then EnableMemcached:=1;

end;
//##############################################################################
procedure tmemcached.free();
begin
    logs.Free;
end;
//##############################################################################

procedure tmemcached.STOP();
var
   count:integer;
   RegExpr:TRegExpr;
   cmd:string;
   pids:Tstringlist;
   pidstring:string;
   fpid,i:integer;
begin
if not FileExists(binpath) then begin


   writeln('Stopping memcached...........: Not installed');
   exit;
end;

if not SYS.PROCESS_EXIST(PID_NUM()) then begin
        writeln('Stopping memcached...........: already Stopped');
        exit;
end;
   pidstring:=PID_NUM();
   writeln('Stopping memcached...........: ' + pidstring + ' PID..');

   cmd:=SYS.LOCATE_GENERIC_BIN('kill') +' '+pidstring;
   fpsystem(cmd);

 if not SYS.PROCESS_EXIST(PID_NUM()) then begin
        writeln('Stopping memcached...........: Stopped');
        exit;
 end;

   cmd:=SYS.LOCATE_GENERIC_BIN('kill')+' '+pidstring+' >/dev/null 2>&1';
   fpsystem(cmd);

   count:=0;

   while SYS.PROCESS_EXIST(pidstring) do begin
        sleep(200);
        count:=count+1;
        if count>50 then begin
            if length(pidstring)>0 then begin
               if SYS.PROCESS_EXIST(pidstring) then begin
                  writeln('Stopping ufdbguardd..........: kill pid '+ pidstring+' after timeout');
                  fpsystem('/bin/kill -9 ' + pidstring);
               end;
            end;
            break;
        end;
        pidstring:=PID_NUM();
  end;

  if not SYS.PROCESS_EXIST(PID_NUM()) then begin
     writeln('Stopping memcached...........: Stopped');
  end;
end;

//##############################################################################
function tmemcached.BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('memcached');
end;
//##############################################################################
procedure tmemcached.START();
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
         logs.DebugLogs('Starting......: memcached not installed');
         exit;
   end;

if EnableMemcached=0 then begin
   logs.DebugLogs('Starting......: memcached is disabled');
   STOP();
   exit;
end;

if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: memcached Already running using PID ' +PID_NUM()+ '...');
   exit;
end;

   if FileExists('/usr/share/artica-postfix/exec.memcached.php') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.memcached.php --build');
   cmd:=binpath+' -d -s /var/run/memcached.sock -a 777 -u root -P /var/run/memcached.pid';
   fpsystem(cmd);
   count:=0;
   while not SYS.PROCESS_EXIST(PID_NUM()) do begin
     sleep(300);
     inc(count);
     if count>50 then begin
       logs.DebugLogs('Starting......: memcached (timeout!!!)');
       logs.DebugLogs('Starting......: memcached "'+cmd+'"');
       break;
     end;
   end;


if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: memcached started with new PID ' +PID_NUM()+ '...');
   exit;
end;


  if not SYS.PROCESS_EXIST(PID_NUM()) then begin
       logs.DebugLogs('Starting......: memcached (failed!!!)');
       logs.DebugLogs('Starting......: memcached "'+cmd+'"');
   end else begin
       logs.DebugLogs('Starting......: memcached started with new PID '+PID_NUM());
   end;

end;
//#####################################################################################
function tmemcached.STATUS():string;
var
pidpath:string;
begin
    if not FileExists(binpath) then exit;
   pidpath:=logs.FILE_TEMP();
   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --memcached >'+pidpath +' 2>&1');
   result:=logs.ReadFromFile(pidpath);
   logs.DeleteFile(pidpath);
end;
//#########################################################################################
 function tmemcached.PID_NUM():string;
begin
  result:=SYS.GET_PID_FROM_PATH('/var/run/memcached.pid');
  if sys.verbosed then logs.Debuglogs(' ->'+result);
  if length(result)=0 then result:=SYS.PIDOF_PATTERN(binpath);
  if not SYS.PROCESS_EXIST(result) then result:=SYS.PIDOF_PATTERN(binpath);
end;
 //##############################################################################
function tmemcached.PID_PATH():string;
begin
     exit('/var/run/memcached.pid');
end;
 //##############################################################################
 function tmemcached.VERSION():string;
var
   l:TstringList;
   i:integer;
   RegExpr:TRegExpr;
   tmpstr:string;
begin

    if length(binpath)=0 then exit;
    if Not Fileexists(binpath) then exit;
    result:=SYS.GET_CACHE_VERSION('APP_MEMCACHED');
     if length(result)>2 then exit;
     if not FileExists(binpath) then exit;

    tmpstr:=logs.FILE_TEMP();
    fpsystem(binpath +' -h >'+tmpstr +' 2>&1');
    if not FileExists(tmpstr) then exit;
    l:=TstringList.Create;
    l.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);

    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='memcached\s+([0-9\.]+)';
    for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            break;
         end;
    end;
 SYS.SET_CACHE_VERSION('APP_MEMCACHED',result);
l.free;
RegExpr.free;
end;
//##############################################################################
end.
