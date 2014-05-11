unit mailmanctl;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,
    RegExpr      in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/RegExpr.pas',
    zsystem      in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/zsystem.pas',
    openldap;


  type
  tmailman=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     MailManEnabled:integer;
     ldap:topenldap;
     function mmsitepass_path():string;

public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    function    PID_NUM():string;
    procedure   STOP();
    function    STATUS():string;
    function    CONFIG_PATH():string;
    function    BIN_PATH():string;
    function    VERSION():string;
    procedure   PUBLIC_ARCHIVES_ON_PHP();
    function    PostFixToMailManPath():string;
    function    PID_PATH():string;
END;

implementation

constructor tmailman.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       ldap:=topenldap.Create;
        if not TryStrToInt(SYS.GET_INFO('MailManEnabled'),MailManEnabled)  then begin
           SYS.set_INFO('MailManEnabled','0');
           MailManEnabled:=0;
        end;

       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tmailman.free();
begin
    logs.Free;
end;
//##############################################################################
function tmailman.PID_NUM():string;
var pid:string;
begin
pid :=SYS.GET_PID_FROM_PATH(PID_PATH());
if length(pid)=0 then pid:=SYS.PIDOF(BIN_PATH());
result:=pid;
end;
//##############################################################################
function tmailman.PID_PATH():string;
begin
if FileExists('/var/run/mailman/mailman.pid') then exit('/var/run/mailman/mailman.pid');
if FileExists('/var/lib/mailman/data/master-qrunner.pid') then exit('/var/lib/mailman/data/master-qrunner.pid');
if FileExists('/var/run/mailman/master-qrunner.pid') then exit('/var/run/mailman/master-qrunner.pid');
end;
//##############################################################################
function tmailman.BIN_PATH():string;
begin
if FileExists('/usr/lib/mailman/bin/mailmanctl') then exit('/usr/lib/mailman/bin/mailmanctl');
end;
//##############################################################################
function tmailman.CONFIG_PATH():string;
begin
///var/lib/mailman/bin/list_lists
if FileExists('/usr/lib/mailman/Mailman/mm_cfg.py') then exit('/usr/lib/mailman/Mailman/mm_cfg.py');
if FileExists('/etc/mailman/mm_cfg.py') then exit('/etc/mailman/mm_cfg.py');
end;
//##############################################################################
function tmailman.mmsitepass_path():string;
begin
if FileExists('/usr/lib/mailman/bin/mmsitepass') then exit('/usr/lib/mailman/bin/mmsitepass');
end;
//##############################################################################
function tmailman.PostFixToMailManPath():string;
begin
if FileExists('/etc/mailman/postfix-to-mailman.py') then exit('/etc/mailman/postfix-to-mailman.py');
if FileExists('/usr/lib/mailman/bin/postfix-to-mailman.py') then exit('/usr/lib/mailman/bin/postfix-to-mailman.py');
if FileExists('/usr/share/mailman/postfix-to-mailman.py') then exit('/usr/share/mailman/postfix-to-mailman.py');
end;
//##############################################################################
procedure tmailman.START();
var

   pid:string;
   count:integer;
   RegExpr        :TRegExpr;
   servername:string;
   DEFAULT_EMAIL_HOST:string;
begin

  DEFAULT_EMAIL_HOST:=trim(SYS.GET_INFO('MAILMAN_DEFAULT_EMAIL_HOST'));



  count:=0;
  logs.DebugLogs('############## Mailman #######################');

  if not FileExists(BIN_PATH()) then begin
     logs.Syslogs('Starting......: mailman is not installed');
     exit;
  end;

  if MailManEnabled=0 then begin
      logs.Syslogs('Starting......: mailman is disabled by MailManEnabled value');
      STOP();
      exit;
  end;


  pid:=PID_NUM();
  logs.DebugLogs('tmailman.START(): PID report "' + PID_NUM()+'"');


   if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: mailman daemon is already running using PID ' + pid + '...');
      exit;
   end;


  logs.DebugLogs('Starting......: mailman daemon cleaning...');
  if FileExists(mmsitepass_path()) then logs.OutputCmd(mmsitepass_path()+' '+ldap.get_LDAP('password'));
  RegExpr:=TRegExpr.Create;
  logs.OutputCmd(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.mailman.php');
  logs.OutputCmd('/etc/init.d/mailman start');


  pid:=PID_NUM();
  while not SYS.PROCESS_EXIST(pid) do begin

        sleep(500);
        count:=count+1;
        logs.DebugLogs('tmailman.START(): wait sequence ' + intToStr(count) + ' PID=' + pid);
        if count>20 then begin
            logs.DebugLogs('Starting......: mailman daemon failed...');
            exit;
        end;
        pid:=PID_NUM();
  end;
  logs.Syslogs('Success starting mailman daemon...');
  logs.DebugLogs('Starting......: mailman daemon success...');
end;
//##############################################################################
procedure tmailman.STOP();
var
   pid:string;
   count:integer;
begin


  if not FileExists(BIN_PATH()) then begin
     writeln('Stopping mailman..........: not installed');
     exit;
  end;

pid:=PID_NUM();
count:=0;

if SYS.PROCESS_EXIST(pid) then begin
   writeln('Stopping mailman..........: ' + pid + ' PID..');
   fpsystem('/bin/kill ' + pid);
end else begin
    writeln('Stopping mailman..........: Already stopped');
    exit;
end;

  pid:=PID_NUM();
  while SYS.PROCESS_EXIST(pid) do begin
        pid:=PID_NUM();
        sleep(100);
        count:=count+1;
        if count>20 then begin
            writeln('Stopping mailman..........: timeout');
            fpsystem('/bin/kill -9 ' + pid);
        end;
  end;

pid:=PID_NUM();
if not SYS.PROCESS_EXIST(pid) then writeln('Stopping mailman..........: success');

//DEFAULT_EMAIL_HOST

end;
//##############################################################################
function tmailman.VERSION():string;
var
   RegExpr        :TRegExpr;
   F              :TstringList;
   i              :integer;
   tmpstr:string;
begin

  if not FileExists('/usr/lib/mailman/bin/version') then begin
     exit;
  end;
  tmpstr:=LOGS.FILE_TEMP();
  fpsystem('/usr/lib/mailman/bin/version >'+tmpstr +' 2>&1');
  F:=TstringList.Create;

  if FileExists(tmpstr) then F.LoadFromFile(tmpstr);
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='([0-9\.\-]+)';

  For i:=0 to F.Count-1 do begin
     if RegExpr.Exec(F.Strings[i]) then begin
        result:=RegExpr.Match[1];
        break;
     end;
  end;
F.free;
RegExpr.Free;
end;
//##############################################################################
function tmailman.STATUS():string;
var pidpath:string;
begin
if not FileExists(BIN_PATH()) then exit;
SYS.MONIT_DELETE('APP_MAILMAN');
pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --mailman >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);
end;
//#########################################################################################
procedure tmailman.PUBLIC_ARCHIVES_ON_PHP();
var
   i:integer;
   newpath:string;
   l:TstringList;
begin

    l:=TstringList.Create;
    l.Add('<?php');
    l.Add('header("location:index.html");');
    l.Add('?>');
    
    if not DirectoryExists('/var/lib/mailman/archives/public') then exit;
    SYS.DirDir('/var/lib/mailman/archives/public');
    for i:=0 to SYS.DirListFiles.Count-1 do begin
        newpath:='/var/lib/mailman/archives/public/'+SYS.DirListFiles.Strings[i]+'/index.php';
        if not FileExists(newpath) then begin
            l.SaveToFile(newpath);
            logs.OutputCmd('/bin/chmod 755 ' + newpath);
            logs.OutputCmd('/bin/chown www-data:www-data ' + newpath);
        end;
    end;
end;
//#########################################################################################
end.
