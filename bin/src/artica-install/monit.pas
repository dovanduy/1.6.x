unit monit;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,logs,unix,RegExpr,zsystem;



  type
  tmonit=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     EnableMONITSmtpNotif:integer;

    function   INIT_D_PATH():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);


    function    VERSION():string;
    function    BIN_PATH():string;
    function    PID_NUM():string;
    function    VERSIONNUM():integer;

END;

implementation

constructor tmonit.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
        if not TryStrToInt(SYS.GET_INFO('EnableMONITSmtpNotif'),EnableMONITSmtpNotif) then EnableMONITSmtpNotif:=0;
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tmonit.free();
begin
    logs.Free;
end;
//##############################################################################
function tmonit.BIN_PATH():string;
begin
   if FileExists(SYS.LOCATE_GENERIC_BIN('monit')) then exit(SYS.LOCATE_GENERIC_BIN('monit'));
   exit('/usr/share/artica-postfix/bin/artica-monit');
end;
//##############################################################################
function tmonit.INIT_D_PATH():string;
begin
   if FileExists('/etc/init.d/monit') then exit('/etc/init.d/monit');
   exit('/etc/init.d/monit');

end;
//##############################################################################
function tmonit.PID_NUM():string;
begin
    if not FIleExists(BIN_PATH()) then exit;
    result:=sys.GET_PID_FROM_PATH('/var/run/monit/monit.pid');
end;
//##############################################################################
function tmonit.VERSION():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    filetmp:string;
begin

result:=SYS.GET_CACHE_VERSION('APP_MONIT');
if length(result)>2 then exit;

filetmp:=logs.FILE_TEMP();
if not FileExists(BIN_PATH()) then begin
   logs.Debuglogs('unable to find monit');
   exit;
end;

logs.Debuglogs(BIN_PATH()+' -V >'+filetmp+' 2>&1');
fpsystem(BIN_PATH()+' -V >'+filetmp+' 2>&1');

    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='version\s+([0-9\.]+)';
    FileDatas:=TStringList.Create;
    FileDatas.LoadFromFile(filetmp);
    logs.DeleteFile(filetmp);
    for i:=0 to FileDatas.Count-1 do begin
        if RegExpr.Exec(FileDatas.Strings[i]) then begin
             result:=RegExpr.Match[1];
             break;
        end;
    end;
             RegExpr.free;
             FileDatas.Free;

SYS.SET_CACHE_VERSION('APP_MONIT',result);

end;
//#############################################################################
function tmonit.VERSIONNUM():integer;
var
   zversion:string;
begin
    zversion:=VERSION();
    zversion:=AnsiReplaceText(zversion,'.','');
    if length(zversion)=3 then zversion:=zversion+'0';
    TryStrToInt(zversion,result);

end;
end.
