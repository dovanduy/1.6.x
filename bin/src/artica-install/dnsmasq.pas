unit dnsmasq;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,
    RegExpr in 'RegExpr.pas',
    zsystem in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/zsystem.pas',
    bind9   in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/bind9.pas';

  type
  tdnsmasq=class


private
     LOGS:Tlogs;
     artica_path:string;
     EnableDNSMASQ:integer;
     EnablePDNS:integer;
     SYS:Tsystem;
     bind9:Tbind9;
     function IsLoadedAsuser():string;



public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
      function  DNSMASQ_SET_VALUE(key:string;value:string):string;
      function  DNSMASQ_GET_VALUE(key:string):string;
      function  DNSMASQ_BIN_PATH():string;
      function  DNSMASQ_VERSION:string;
      function  Forwarders():string;



END;

implementation

constructor tdnsmasq.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       if Not TryStrToInt(SYS.GET_INFO('EnablePDNS'),EnablePDNS) then EnablePDNS:=0;
       if Not TryStrToInt(SYS.GET_INFO('EnableDNSMASQ'),EnableDNSMASQ) then EnableDNSMASQ:=0;



       if Not FileExists(SYS.LOCATE_PDNS_BIN()) then EnablePDNS:=0;
       if EnablePDNS=1 then EnableDNSMASQ:=0;

       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tdnsmasq.free();
begin
    logs.Free;
end;
//##############################################################################
function tdnsmasq.DNSMASQ_GET_VALUE(key:string):string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    ValueResulted:string;
begin
   if not FileExists('/etc/dnsmasq.conf') then  exit;
   FileDatas:=TStringList.Create;
   FileDatas.LoadFromFile('/etc/dnsmasq.conf');
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^'+key+'([="''\s]+)(.+)';
   for i:=0 to FileDatas.Count -1 do begin
           if RegExpr.Exec(FileDatas.Strings[i]) then begin
              FileDatas.Free;
              ValueResulted:=RegExpr.Match[2];
              if ValueResulted='"' then ValueResulted:='';
              RegExpr.Free;
              exit(ValueResulted);
           end;

   end;
   FileDatas.Free;
   RegExpr.Free;

end;
//#############################################################################
function tdnsmasq.Forwarders():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
begin
   if not FileExists('/etc/dnsmasq.resolv.conf') then  exit;
   FileDatas:=TStringList.Create;
   FileDatas.LoadFromFile('/etc/dnsmasq.resolv.conf');
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^nameserver\s+(.+)';
   for i:=0 to FileDatas.Count -1 do begin
           if RegExpr.Exec(FileDatas.Strings[i]) then begin
              result:=result + RegExpr.Match[1]+';';
           end;

   end;
   FileDatas.Free;
   RegExpr.Free;

end;
//#############################################################################
function tdnsmasq.IsLoadedAsuser():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
begin
   if not FileExists('/etc/init.d/dnsmasq') then  exit;
   FileDatas:=TStringList.Create;
   FileDatas.LoadFromFile('/etc/init.d/dnsmasq');
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='startproc.+?-u\s+(.+)';
   for i:=0 to FileDatas.Count -1 do begin
           if RegExpr.Exec(FileDatas.Strings[i]) then begin
              result:=RegExpr.Match[1];
              RegExpr.Expression:='(.+?)\s+';
               if RegExpr.Exec(result) then result:=RegExpr.Match[1];
              break;
           end;

   end;
   FileDatas.Free;
   RegExpr.Free;

end;
//#############################################################################



function tdnsmasq.DNSMASQ_SET_VALUE(key:string;value:string):string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    FileToEdit:string;
begin
   FileToEdit:='/etc/dnsmasq.conf';
   if not FileExists(FileToEdit) then  fpsystem('/bin/touch ' + FileToEdit);
   FileDatas:=TStringList.Create;
   FileDatas.LoadFromFile(FileToEdit);
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^'+key+'([="''\s]+)(.+)';
   for i:=0 to FileDatas.Count -1 do begin
           if RegExpr.Exec(FileDatas.Strings[i]) then begin
                FileDatas.Strings[i]:=key + '=' + value;
                FileDatas.SaveToFile(FileToEdit);
                FileDatas.Free;
                RegExpr.Free;
                exit;

           end;

   end;

  FileDatas.Add(key + '=' + value);
  FileDatas.SaveToFile(FileToEdit);
  FileDatas.Free;
  RegExpr.Free;
  result:='';

end;
//#############################################################################
function tdnsmasq.DNSMASQ_BIN_PATH():string;
begin
    exit(SYS.LOCATE_GENERIC_BIN('dnsmasq'));
end;
//#############################################################################
function tdnsmasq.DNSMASQ_VERSION:string;
var
   binPath:string;
    mem:TStringList;
    commandline:string;
    tmp_file:string;
    RegExpr:TRegExpr;
    i:integer;
begin
    binPath:=DNSMASQ_BIN_PATH();

    if not FileExists(binpath) then begin
       exit;
    end;


    result:=trim(SYS.GET_CACHE_VERSION('APP_DNSMASQ'));
    if length(result)>2 then exit();

    if not FIleExists('/etc/dnsmasq.conf') then exit;

    tmp_file:=logs.FILE_TEMP();
    commandline:=binPath+' -v >'+tmp_file +' 2>&1';
    fpsystem(commandline);
    mem:=TStringList.Create;
    if not FileExists(tmp_file) then exit;
    mem.LoadFromFile(tmp_file);
    logs.DeleteFile(tmp_file);


    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='Dnsmasq version\s+([0-9\.]+)';

     for i:=0 to mem.Count-1 do begin
       if RegExpr.Exec(mem.Strings[i]) then begin
          result:=RegExpr.Match[1];
          break;
       end;

     end;
     SYS.SET_CACHE_VERSION('APP_DNSMASQ',result);
     mem.Free;
     RegExpr.Free;

end;


end.

