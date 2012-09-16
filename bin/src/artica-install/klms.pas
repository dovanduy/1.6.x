unit klms;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,RegExpr,zsystem,cyrus;



  type
  tklms=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     binpath:string;
     EnableKlms:integer;
     function PID_NUM():string;
     function DB_PID_NUM():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    function    VERSION():string;
    function    BIN_PATH():string;
    procedure   START();
    procedure   STOP();
    procedure   DBSTART();
    procedure   DBSTOP();

END;

implementation

constructor tklms.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       EnableKlms:=1;

       binpath:='/opt/kaspersky/klms/bin/klms-control';

       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tklms.free();
begin
    logs.Free;
end;
//##############################################################################
function tklms.BIN_PATH():string;
begin
    exit(binpath);
end;
//##############################################################################

function tklms.VERSION():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    filetmp:string;
    debug:boolean;
    dpkg:string;
begin
if not FileExists(BIN_PATH()) then exit;
   debug:=false;
   debug:=SYS.COMMANDLINE_PARAMETERS('--debug');
   result:=SYS.GET_CACHE_VERSION('APP_KLMS');
if length(result)>0 then begin
   if debug then writeln('GET_CACHE_VERSION ->',result);
   exit;
end;

dpkg:=SYS.LOCATE_GENERIC_BIN('dpkg');
filetmp:=logs.FILE_TEMP();
if debug then writeln(dpkg+' -l >'+filetmp+' 2>&1');
fpsystem(dpkg+' -l >'+filetmp+' 2>&1');


if not FileExists(filetmp) then exit;
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='ii\s+klms\s+([0-9\.\-]+)\s+';
    FileDatas:=TStringList.Create;
    try
       FileDatas.LoadFromFile(filetmp);

    except
          exit;
    end;
    logs.DeleteFile(filetmp);
    for i:=0 to FileDatas.Count-1 do begin
        if RegExpr.Exec(FileDatas.Strings[i]) then begin
             result:=RegExpr.Match[1];
             break;
        end;
    end;
RegExpr.free;
FileDatas.Free;
SYS.SET_CACHE_VERSION('APP_KLMS',result);

end;
//#############################################################################
function tklms.PID_NUM():string;
var
   pid:string;
begin
    pid:=SYS.GET_PID_FROM_PATH('/var/run/klms/klms.pid');
    if not SYS.PROCESS_EXIST(pid) then pid:=SYS.PIDOF_PATTERN(BIN_PATH());
    result:=pid;
end;


//#############################################################################
function tklms.DB_PID_NUM():string;
var
   pid:string;
begin
    pid:=SYS.GET_PID_FROM_PATH('/var/opt/kaspersky/klms/postgresql/postmaster.pid');
    if not SYS.PROCESS_EXIST(pid) then pid:=SYS.PIDOF_PATTERN('/opt/kaspersky/klms/libexec/postgresql/postgres');
    result:=pid;
end;
//#############################################################################
 procedure tklms.DBSTART();
var
   pid:string;
   ck:integer;
   cmd:string;
begin
   pid:=DB_PID_NUM();
   if not FileExists(BIN_PATH()) then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security database Not installed');
      exit;
   end;

   if EnableKlms=0 then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security database is disabled');
      if SYS.PROCESS_EXIST(pid) then DBSTOP();
      exit;
   end;
   pid:=DB_PID_NUM();
   if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security database Already running using PID '+pid);
      exit;
   end;

   if not FileExists('/etc/init.d/klmsdb') then  begin
       logs.DebugLogs('Starting......: Kaspersky Mail security Suite /etc/init.d/klmsdb no such file');
       exit;
   end;

   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.klms.php --setup');

   cmd:='/etc/init.d/klmsdb start';
   logs.DebugLogs('Starting......: Kaspersky Mail security database '+ cmd);
   fpsystem(cmd);

   pid:=DB_PID_NUM();
       ck:=0;
       while not SYS.PROCESS_EXIST(pid) do begin
           pid:=DB_PID_NUM();
           sleep(300);
           inc(ck);
           if ck>80 then begin
                logs.DebugLogs('Starting......: Kaspersky Mail security database timeout...');
                break;
           end;
       end;

    pid:=DB_PID_NUM();
    if not SYS.PROCESS_EXIST(pid) then begin
       logs.DebugLogs('Starting......: Kaspersky Mail security database failed...');

    end else begin
        logs.DebugLogs('Starting......: Kaspersky Mail security database success PID '+pid);
    end;
end;
//#############################################################################


procedure tklms.START();
var
   pid:string;
   ck:integer;
   cmd:string;
begin
   pid:=PID_NUM();
   if not FileExists(BIN_PATH()) then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security Suite Not installed');
      exit;
   end;

   if EnableKlms=0 then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security Suite is disabled');
      if SYS.PROCESS_EXIST(pid) then STOP();
      exit;
   end;
   pid:=PID_NUM();
   if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: Kaspersky Mail security Suite Already running using PID '+pid);
      exit;
   end;

   if not FileExists('/etc/init.d/klms') then  begin
       logs.DebugLogs('Starting......: Kaspersky Mail security Suite /etc/init.d/klms no such file');
       exit;
   end;

   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.klms.php --setup');

   cmd:='/etc/init.d/klms start';
   logs.DebugLogs('Starting......: Kaspersky Mail security Suite '+ cmd);
   fpsystem(cmd);

   pid:=PID_NUM();
       ck:=0;
       while not SYS.PROCESS_EXIST(pid) do begin
           pid:=PID_NUM();
           sleep(300);
           inc(ck);
           if ck>80 then begin
                logs.DebugLogs('Starting......: Kaspersky Mail security Suite timeout...');
                break;
           end;
       end;

    pid:=PID_NUM();
    if not SYS.PROCESS_EXIST(pid) then begin
       logs.DebugLogs('Starting......: Kaspersky Mail security Suite failed...');

    end else begin
        logs.DebugLogs('Starting......: Kaspersky Mail security Suite success PID '+pid);
    end;
end;
//#############################################################################
procedure tklms.STOP();
var
 pid:string;
 count:integer;
begin

  pid:=PID_NUM();
  count:=0;

   if not FileExists(BIN_PATH()) then begin
      writeln('Stopping KLMS................: Not installed');
      exit;
   end;


  if not SYS.PROCESS_EXIST(pid) then begin
     writeln('Stopping KLMS................: Already stopped');
     exit;
  end;

     writeln('Stopping KLMS................: Stopping PID '+pid);
     fpsystem('/etc/init.d/klms stop');
     while SYS.PROCESS_EXIST(pid) do begin
           Inc(count);
           sleep(100);
           if count>50 then begin
              writeln('Stopping KLMS................: ' + pid + ' PID (timeout)');
              fpsystem('/bin/kill -9 ' + pid);
              break;
           end;
           pid:=PID_NUM();
     end;
     pid:=PID_NUM();
     if SYS.PROCESS_EXIST(pid) then begin
           writeln('Stopping KLMS................: ' + pid + '  failed already exists PID '+ pid);
           exit;
     end;

       writeln('Stopping KLMS................: success');




end;
//#############################################################################
procedure tklms.DBSTOP();
var
 pid:string;
 count:integer;
begin

  pid:=DB_PID_NUM();
  count:=0;

   if not FileExists(BIN_PATH()) then begin
      writeln('Stopping KLMSDB..............: Not installed');
      exit;
   end;


  if not SYS.PROCESS_EXIST(pid) then begin
     writeln('Stopping KLMSDB..............: Already stopped');
     exit;
  end;

     writeln('Stopping KLMSDB..............: Stopping PID '+pid);
     fpsystem('/etc/init.d/klmsdb stop');
     while SYS.PROCESS_EXIST(pid) do begin
           Inc(count);
           sleep(100);
           if count>50 then begin
              writeln('Stopping KLMSDB..............: ' + pid + ' PID (timeout)');
              fpsystem('/bin/kill -9 ' + pid);
              break;
           end;
           pid:=DB_PID_NUM();
     end;
     pid:=DB_PID_NUM();
     if SYS.PROCESS_EXIST(pid) then begin
           writeln('Stopping KLMSDB..............: ' + pid + '  failed already exists PID '+ pid);
           exit;
     end;

       writeln('Stopping KLMSDB..............: success');




end;
//#############################################################################
end.
