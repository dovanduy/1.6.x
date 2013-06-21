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


end;
//##############################################################################
procedure tmemcached.free();
begin
    logs.Free;
end;
//##############################################################################

procedure tmemcached.STOP();
begin
 if not FileExists('/etc/init.d/artica-memcache') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php --memcache');
  fpsystem('/etc/init.d/artica-memcache stop');
end;

//##############################################################################
function tmemcached.BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('memcached');
end;
//##############################################################################
procedure tmemcached.START();
begin
 if not FileExists('/etc/init.d/artica-memcache') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.initslapd.php --memcache');
  fpsystem('/etc/init.d/artica-memcache start');
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
