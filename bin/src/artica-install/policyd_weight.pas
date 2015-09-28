unit policyd_weight;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,md5,logs,unix,RegExpr in 'RegExpr.pas',zsystem;

  type
  tpolicyd_weight=class


private
     LOGS:Tlogs;
     D:boolean;
     GLOBAL_INI:TiniFIle;
     SYS:TSystem;
     artica_path:string;
     EnablePolicydWeight:integer;
    CONFIG_ARRAY:TstringList;
    EnablePostfixMultiInstance:integer;

public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    function  BIN_PATH():string;
    function  CONFIG_PATH():string;
    function  VERSION():string;


END;

implementation

constructor tpolicyd_weight.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       CONFIG_ARRAY:=Tstringlist.Create;
       if FileExists(CONFIG_PATH()) then begin
          try
             CONFIG_ARRAY.LoadFromFile(CONFIG_PATH());
          except
          end;
       end;


       if not TryStrToInt(SYS.GET_INFO('EnablePolicydWeight'),EnablePolicydWeight) then begin
          EnablePolicydWeight:=0;
          SYS.set_INFO('EnablePolicydWeight','0');
       end;

       if not TryStrToInt(SYS.GET_INFO('EnablePostfixMultiInstance'),EnablePostfixMultiInstance) then EnablePostfixMultiInstance:=0;
       if EnablePostfixMultiInstance=1 then EnablePolicydWeight:=0;






       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tpolicyd_weight.free();
begin
    FreeAndNil(logs);
    FreeAndNil(CONFIG_ARRAY);
end;
//##############################################################################
function tpolicyd_weight.CONFIG_PATH():string;
var
   l:Tstringlist;
   i:integer;
begin
    l:=Tstringlist.Create;
    l.Add('/etc/policyd-weight.conf');
    l.Add('/etc/postfix/policyd-weight.cf');
    l.Add('/usr/local/etc/policyd-weight.conf');
    for i:=0 to l.Count-1 do begin
        if FileExists(l.Strings[i]) then begin
           result:=l.Strings[i];
           l.free;
           exit;
        end;
    end;

result:='/etc/policyd-weight.conf';
end;
//##############################################################################

function tpolicyd_weight.BIN_PATH():string;
begin
     if FileExists('/usr/sbin/policyd-weight') then exit('/usr/sbin/policyd-weight');
     if FileExists('/usr/local/sbin/policyd-weight') then exit('/usr/local/sbin/policyd-weight');
     if FileExists('/usr/share/artica-postfix/bin/policyd-weight') then exit('/usr/share/artica-postfix/bin/policyd-weight');
end;
//##############################################################################


function tpolicyd_weight.VERSION():string;
  var
   RegExpr:TRegExpr;
   tmpstr:string;
   l:TstringList;
   i:integer;
   path:string;
begin
 path:=BIN_PATH();
     if not FileExists(path) then begin
        logs.Debuglogs('tpolicyd_weight.VERSION():: policyd-weight is not installed');
        exit;
     end;


   result:=SYS.GET_CACHE_VERSION('APP_POLICYD_WEIGHT');
   if length(result)>0 then exit;
   tmpstr:=logs.FILE_TEMP();
   fpsystem(path+' -v >'+tmpstr+' 2>&1');

     if not FileExists(tmpstr) then exit;
     l:=TstringList.Create;
     RegExpr:=TRegExpr.Create;
     l.LoadFromFile(tmpstr);
     RegExpr.Expression:='policyd-weight version:.+?\s+([0-9\.]+)';
     for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            result:=trim(result);
            result:=AnsiReplaceText(result,'"','');
            break;
         end;
     end;
l.Free;
RegExpr.free;
SYS.SET_CACHE_VERSION('APP_POLICYD_WEIGHT',result);
logs.Debuglogs('APP_POLICYD_WEIGHT:: -> ' + result);
end;
//#############################################################################

end.
