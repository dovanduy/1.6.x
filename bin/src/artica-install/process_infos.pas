unit process_infos;

{$mode objfpc}{$H+}
interface

uses
Classes, SysUtils,variants,strutils,unix, Process,md5,logs,baseunix,RegExpr in 'RegExpr.pas',global_conf in 'global_conf.pas',spamass,clamav,spfmilter,openldap,zsystem,cyrus,squid,
dkimfilter,postfix_class,mailgraph_daemon,miltergreylist,roundcube,dansguardian,kav4samba,kav4proxy,bind9,mysql_daemon,zarafa_server, articastatus,articaexecutor,articabackground,munin,
kavmilter in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/kavmilter.pas',
dnsmasq   in   '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/dnsmasq.pas',
saslauthd in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/saslauthd.pas',
collectd  in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/collectd.pas',
fetchmail  in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/fetchmail.pas',
mailspy_milter   in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/mailspy_milter.pas',
amavisd_milter   in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/amavisd_milter.pas',
jcheckmail       in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/jcheckmail.pas',
bogom,syslogng;

  type
  Tprocessinfos=class


private
     GLOBAL_INI:MyConf;
     LOGS:Tlogs;
     ArrDatas:TStringList;
     debug:boolean;
     ccyrus:Tcyrus;
     squid:tsquid;
     postfix:Tpostfix;
     mailgraph:tMailgraphClass;
     miltergreylist:tmilter_greylist;
     roundcube:TRoundCube;
     dansguardian:Tdansguardian;
     kav4samba:Tkav4samba;
     kav4proxy:Tkav4proxy;
     bind9:Tbind9;
     zmysql:tmysql_daemon;
     dnsmasq:tdnsmasq;
     SYS:Tsystem;
     fetchmail:tfetchmail;
//     function ExecPipe(commandline:string;ShowOut:boolean=false):string;
     function MD5FromFile(path:string):string;
     function ReadFileIntoString(path:string):string;
     function COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;



public
    Enable_echo:boolean;
    procedure Free;
    constructor Create;


    function get_file_permission(path:string):string;
    function ExecPipe2(commandline:string;ShowOut:boolean=false):string;
    procedure killfile(path:string);
    function ExecStream(commandline:string;ShowOut:boolean=false):TMemoryStream;
    procedure AutoKill(force:boolean);


END;

implementation

constructor Tprocessinfos.Create;
begin
       forcedirectories('/etc/artica-postfix');
       GLOBAL_INI:=MyConf.Create();
       SYS:=GLOBAL_INI.SYS;
       LOGS:=tlogs.Create();
       LOGS.Enable_echo:=Enable_echo;

       ccyrus:=Tcyrus.Create(SYS);
       squid:=Tsquid.Create;
       postfix:=Tpostfix.Create(SYS);
       mailgraph:=tMailgraphClass.Create(SYS);
       miltergreylist:=tmilter_greylist.Create(SYS);
       roundcube:=Troundcube.Create(SYS);
       dansguardian:=Tdansguardian.Create(SYS);
       kav4samba:=Tkav4samba.Create;
       kav4proxy:=Tkav4proxy.CReate(SYS);
       bind9:=Tbind9.Create(SYS);
       zmysql:=tmysql_daemon.Create(SYS);
       fetchmail:=tfetchmail.Create(SYS);
end;
//##############################################################################

function Tprocessinfos.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
var
   i:integer;
   s:string;
   RegExpr:TRegExpr;

begin
 s:='';
 result:=false;
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
procedure  Tprocessinfos.Free;
begin
    LOGS.Free;
    GLOBAL_INI.Free;
    ArrDatas.Free;
end;
//##############################################################################
function Tprocessinfos.ExecStream(commandline:string;ShowOut:boolean):TMemoryStream;
const
  READ_BYTES = 2048;

var
  M: TMemoryStream;
  P: TProcess;
  n: LongInt;
  BytesRead: LongInt;

begin

  M := TMemoryStream.Create;
  BytesRead := 0;
  P := TProcess.Create(nil);
  P.CommandLine := commandline;
  P.Options := [poUsePipes];
  if ShowOut then WriteLn('-- executing ' + commandline + ' --');
  if debug then LOGS.Logs('Tprocessinfos.ExecPipe -> ' + commandline);

  P.Execute;
  while P.Running do begin
    M.SetSize(BytesRead + READ_BYTES);
    n := P.Output.Read((M.Memory + BytesRead)^, READ_BYTES);
    if n > 0 then begin
      Inc(BytesRead, n);
    end
    else begin
      Sleep(100);
    end;

  end;

  repeat
    M.SetSize(BytesRead + READ_BYTES);
    n := P.Output.Read((M.Memory + BytesRead)^, READ_BYTES);
    if n > 0 then begin
      Inc(BytesRead, n);
    end;
  until n <= 0;
  M.SetSize(BytesRead);
  exit(M);
end;
//##############################################################################
//##############################################################################

function Tprocessinfos.ExecPipe2(commandline:string;ShowOut:boolean):string;
const
  READ_BYTES = 2048;
  CR = #$0d;
  LF = #$0a;
  CRLF = CR + LF;

var
  S: TStringList;
  M: TMemoryStream;
  P: TProcess;
  n: LongInt;
  BytesRead: LongInt;
  xRes:string;

begin

  M := TMemoryStream.Create;
  BytesRead := 0;
  P := TProcess.Create(nil);
  P.CommandLine := commandline;
  P.Options := [poUsePipes];
  xRes:='';
  if ShowOut then WriteLn('-- executing ' + commandline + ' --');
  if debug then LOGS.Logs('Tprocessinfos.ExecPipe -> ' + commandline);

  P.Execute;
  while P.Running do begin
    M.SetSize(BytesRead + READ_BYTES);
    n := P.Output.Read((M.Memory + BytesRead)^, READ_BYTES);
    if n > 0 then begin
      Inc(BytesRead, n);
    end
    else begin
      Sleep(100);
    end;
    
  end;

  repeat
    M.SetSize(BytesRead + READ_BYTES);
    n := P.Output.Read((M.Memory + BytesRead)^, READ_BYTES);
    if n > 0 then begin
      Inc(BytesRead, n);
    end;
  until n <= 0;
  M.SetSize(BytesRead);
  S := TStringList.Create;
  S.LoadFromStream(M);
  if ShowOut then WriteLn('-- linecount = ', S.Count, ' --');
  if debug then LOGS.Logs('Tprocessinfos.ExecPipe -> ' + IntTostr(S.Count) + ' lines');
  for n := 0 to S.Count - 1 do
  begin
    if length(S[n])>1 then begin
      xRes:=xRes + S[n] +CRLF;
    end;
  end;
  if ShowOut  then WriteLn(xRes + '-- end --');
  if debug then LOGS.Logs('Tprocessinfos.ExecPipe -> exit');
  S.Free;
  P.Free;
  M.Free;
  exit( xRes);
end;
//##############################################################################

function Tprocessinfos.ReadFileIntoString(path:string):string;
         const
            CR = #$0d;
            LF = #$0a;
            CRLF = CR + LF;
var
   Afile:text;
   datas:string;
   datas_file:string;
begin
     datas_file:='';
      if not FileExists(path) then begin
        LOGS.logs('Error:thProcThread.ReadFileIntoString -> file not found (' + path + ')');
        exit;

      end;
      TRY
     assign(Afile,path);
     reset(Afile);
     while not EOF(Afile) do
           begin
           readln(Afile,datas);
           datas_file:=datas_file + datas +CRLF;
           end;

close(Afile);
             EXCEPT
              LOGS.logs('Error:thProcThread.ReadFileIntoString -> unable to read (' + path + ')');
           end;
result:=datas_file;


end;
//##############################################################################
procedure Tprocessinfos.AutoKill(force:boolean);
var PID,INADYN_PID:string;
   moinsnef:string;
   D:boolean;
   count:integer;
   spamass:Tspamass;
   clamav:Tclamav;
   ldap:Topenldap;
   spf:tspf;
   pids:string;
   dkim:Tdkim;
   kavmilter:Tkavmilter;
   bogom:Tbogom;
   saslauthd:tsaslauthd;
   collectd:tcollectd;
   mailspy:tmailspy;
   amavis:tamavis;
   jcheckmail:tjcheckmail;
   syslogng:Tsyslogng;
   zarafa_server:tzarafa_server;
   articastatus:tarticastatus;
   articaexecutor:tarticaexecutor;
   articabackground:tarticabackground;
   zmunin:tmunin;
begin
  PID:=GLOBAL_INI.ARTICA_DAEMON_GET_PID();
  dnsmasq:=tdnsmasq.create(GLOBAL_INI.SYS);
  INADYN_PID:=GLOBAL_INI.INADYN_PID();
  spamass:=Tspamass.Create(GLOBAL_INI.SYS);
  clamav:=Tclamav.Create;
  ldap:=Topenldap.Create;
  spf:=tspf.Create;
  dkim:=Tdkim.Create(GLOBAL_INI.SYS);
  jcheckmail:=tjcheckmail.Create(GLOBAL_INI.SYS);
  syslogng:=Tsyslogng.Create(SYS);
  
  D:=COMMANDLINE_PARAMETERS('debug');
  moinsnef:='';
  if force=true then moinsnef:='-9 ';
  if D then writeln(' ARTICA PID=' + PID );
  count:=0;
     if GLOBAL_INI.SYSTEM_PROCESS_EXIST(PID) then begin
                  writeln('Stopping artica..............: ' + PID + ' PID..');
                  fpsystem('kill ' +moinsnef+ PID);

                  while GLOBAL_INI.SYSTEM_PROCESS_EXIST(PID) do begin
                        sleep(100);
                        Inc(count);
                        if count>20 then break;
                  end;
     end;
     
    SYS:=Tsystem.Create();
    pids:=SYS.PROCESS_LIST_PID(GLOBAL_INI.get_ARTICA_PHP_PATH() + '/bin/artica-install');
    pids:=AnsiReplaceText(pids,intTostr(fpgetpid),'');

    
    if length(trim(pids))>0 then begin
         writeln('Stopping artica-install......: proc(s) ' + pids + ' PID..');
         fpsystem('/bin/kill -9 ' + pids);
    end;
     


articastatus:=tarticastatus.Create(SYS);
articaexecutor:=tarticaexecutor.Create(SYS);
articabackground:=tarticabackground.Create(SYS);

articabackground.STOP();
articastatus.STOP();
articaexecutor.STOP();
GLOBAL_INI.APACHE_ARTICA_STOP();

//collectd
collectd:=tcollectd.Create(GLOBAL_INI.SYS);
collectd.STOP();
collectd.Free;

//zarafa
zarafa_server:=tzarafa_server.Create(SYS);
zarafa_server.STOP();



GLOBAL_INI.ARTICA_STOP();

end;


//##############################################################################
function Tprocessinfos.get_file_permission(path:string):string;
var
   s:string;
   RegExpr:TRegExpr;

begin

//nx:=Tunix.Create;
//nx.get_file_permission(path);

s:=ExecPipe2('/usr/bin/stat ' + path,False);
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='Access: \(([0-9]+)\/';
if RegExpr.Exec(s) then result:=RegExpr.Match[1];
RegExpr.Free
end;
//##############################################################################

function Tprocessinfos.MD5FromFile(path:string):string;
var
Digest:TMD5Digest;
begin
Digest:=MD5File(path);
exit(MD5Print(Digest));
end;
//##############################################################################
procedure Tprocessinfos.killfile(path:string);
Var F : Text;
begin
 if not FileExists(path) then begin
        LOGS.logs('Error:Tprocessinfos.killfile -> file not found (' + path + ')');
        exit;
 end;
TRY
 Assign (F,path);
 Erase (f);
 EXCEPT
 LOGS.logs('Error:Tprocessinfos.killfile -> unable to delete (' + path + ')');
 end;
end;
//##############################################################################
end.
