unit spamass;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,
    logs in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/logs.pas',unix,
    RegExpr in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/RegExpr.pas',
    zsystem;

type LDAP=record
      admin:string;
      password:string;
      suffix:string;
      servername:string;
      Port:string;
  end;

  type
  Tspamass=class


private
     LOGS:Tlogs;
     SYS:        TSystem;
     artica_path:string;
     SpamAssMilterEnabled:Integer;
     EnableSaBlackListUpdate:Integer;
     InsufficentRessources:boolean;
     enable_dkim_verification:integer;
     EnableSPF:integer;
     function    COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
     function    ReadFileIntoString(path:string):string;
     function    TRUSTED_NETWORK():string;
     procedure   SPAMASSASSIN_REMOVE_INCLUDE_FILE(filepath:string);
     procedure   SPAMASSASSIN_REMOVE_PLUGIN(plugin:string);
     function    BLOCK_MAIL():string;
     procedure   SPAMASSASSIN_init_pre();
     function    GET_VALUE(key:string):string;


public
    SpamdEnabled:integer;
    procedure   Free;
    constructor Create(const zSYS:Tsystem);



    FUNCTION    MILTER_PID():string;



    FUNCTION    MILTER_DEFAULT_PATH():string;

    function    rewrite_header():string;
    function    SPAMASSASSIN_LOCAL_CF():string;








    procedure   SPAMASSASSIN_ADD_INCLUDE_FILE(filepath:string);
    procedure   SPAMASSASSIN_ADD_PLUGIN(plugin:string);

    function    SA_UPDATE_PATH():string;
    function    IF_PATTERN_FOUND(pattern:string):boolean;
    function    IS_SPAMD_ENABLED:integer;
    function    RAZOR_AGENT_CONF_PATH():string;
    function    RAZOR_ADMIN_PATH():string;


    function    RAZOR_GET_VALUE(key:string):string;
    procedure   DEFAULT_SETTINGS();

    function    PYZOR_BIN_PATH():string;
    procedure   DSPAM_PATCH();

END;

implementation

constructor Tspamass.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       SpamAssMilterEnabled:=0;
       SpamdEnabled:=1;
       enable_dkim_verification:=0;

       if not TryStrToInt(SYS.get_INFO('SpamAssMilterEnabled'),SpamAssMilterEnabled) then SpamAssMilterEnabled:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableSaBlackListUpdate'),EnableSaBlackListUpdate) then EnableSaBlackListUpdate:=0;
       if not TryStrToInt(SYS.GET_INFO('EnableSPF'),EnableSPF) then EnableSPF:=0;
       if not TryStrToInt(SYS.GET_INFO('enable_dkim_verification'),enable_dkim_verification) then enable_dkim_verification:=0;



       InsufficentRessources:=SYS.ISMemoryHiger1G();
        if not InsufficentRessources then begin
             if SpamAssMilterEnabled=1 then begin
                SYS.set_INFO('SpamAssMilterEnabled','0');
                SpamAssMilterEnabled:=0;
          end;
       end;

       SpamdEnabled:=IS_SPAMD_ENABLED();
       
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure Tspamass.free();
begin
    logs.Free;
end;
//##############################################################################
function Tspamass.IS_SPAMD_ENABLED:integer;
var  EnableAmavisDaemon:integer;
begin
result:=0;
EnableAmavisDaemon:=0;
if not SYS.ISMemoryHiger1G() then exit(0);
if not TryStrToInt(SYS.GET_INFO('EnableAmavisDaemon'),EnableAmavisDaemon) then EnableAmavisDaemon:=0;
if EnableAmavisDaemon=1 then exit(0);
if SpamAssMilterEnabled=1 then exit(1);
end;
//##############################################################################
function  Tspamass.RAZOR_AGENT_CONF_PATH():string;
begin
result:=ExtractFilePath(SPAMASSASSIN_LOCAL_CF()) + '.razor/razor-agent.conf';
end;
//##############################################################################
function tspamass.RAZOR_ADMIN_PATH():string;
begin
if FileExists('/usr/bin/razor-admin') then exit('/usr/bin/razor-admin');
if FileExists('/opt/artica/bin/razor-admin') then exit('/opt/artica/bin/razor-admin');
end;
//##############################################################################
function Tspamass.SA_UPDATE_PATH():string;
begin
    if FileExists('/usr/bin/sa-update') then exit('/usr/bin/sa-update');
    if FileExists('/opt/artica/bin/sa-update') then exit('/opt/artica/bin/sa-update');
end;
//##############################################################################
function Tspamass.PYZOR_BIN_PATH():string;
begin
     if FileExists('/usr/bin/pyzor') then exit('/usr/bin/pyzor');
end;
//##############################################################################
FUNCTION Tspamass.MILTER_PID():string;
begin
if FileExists('/var/run/spamass/spamass.pid') then exit(SYS.GET_PID_FROM_PATH('/var/run/spamass/spamass.pid'));
end;
//##############################################################################
FUNCTION Tspamass.MILTER_DEFAULT_PATH():string;
begin
if FileExists('/etc/default/spamass-milter') then exit('/etc/default/spamass-milter');
if FileExists('/etc/sysconfig/spamass-milter') then exit('/etc/sysconfig/spamass-milter');
end;
//##############################################################################

function Tspamass.TRUSTED_NETWORK():string;
var
    RegExpr:TRegExpr;
    l:TStringList;
    i:integer;
    tn:string;
begin

if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Syslogs('Unable to stat spamassassin local.cf');
   exit;
end;

   tn:='';
   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^trusted_networks\s+(.+)';
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
           logs.Syslogs('Starting......: spamass-milter Adding trusted network ' + RegExpr.Match[1]);
           tn:=tn+'-i '+ RegExpr.Match[1]+' ';
       end;
   end;
   
   result:=tn;
   l.free;
   RegExpr.free;
end;
//#############################################################################
function Tspamass.BLOCK_MAIL():string;
var
    RegExpr:TRegExpr;
    l:TStringList;
    i:integer;

begin

if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Syslogs('Unable to stat spamassassin local.cf');
   exit;
end;

   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='milter_block_with_required_score:([0-9\.]+)';
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
           if RegExpr.Match[1]='0' then break;
           logs.Syslogs('Starting......: spamass-milter Block mails up to ' + RegExpr.Match[1]);
           result:=' -r '+ RegExpr.Match[1]+' ';
       end;
   end;


   l.free;
   RegExpr.free;
end;
//#############################################################################
function Tspamass.rewrite_header():string;
var
    RegExpr:TRegExpr;
    l:TStringList;
    i:integer;

begin

if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Debuglogs('Unable to stat spamassassin local.cf');
   exit;
end;

   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='rewrite_header Subject\s+(.+)';
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
          result:=RegExpr.Match[1];
       end;
   end;


   l.free;
   RegExpr.free;
end;
//#############################################################################

function Tspamass.IF_PATTERN_FOUND(pattern:string):boolean;
var
    RegExpr:TRegExpr;
    l:TStringList;
    i:integer;

begin
result:=false;
if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Debuglogs('Unable to stat spamassassin local.cf');
   exit;
end;

   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:=pattern;
   for i:=0 to l.Count-1 do begin
   
       if RegExpr.Exec(l.Strings[i]) then begin
          logs.Syslogs('Starting......: pattern "'+pattern+'" is detected in '+SPAMASSASSIN_LOCAL_CF());
          result:=true;
          break;
       end;
   end;
   l.free;
   RegExpr.free;
end;
//#############################################################################
procedure Tspamass.DSPAM_PATCH();
var l:TStringList;
    localcf:string;
begin
localcf:=SPAMASSASSIN_LOCAL_CF();
if not FileExists(localcf) then begin
      logs.DebugLogs('Starting......: patching DSPAM_PATCH() fatal error unbale to stat local.cf for dpsam+amavis');
      exit;
end;


if not IF_PATTERN_FOUND('header DSPAM_SPAM') then begin
   logs.DebugLogs('Starting......: patching '+SPAMASSASSIN_LOCAL_CF()+' for dpsam+amavis');
   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   l.Add('header DSPAM_SPAM X-DSPAM-Result =~ /^Spam$/');
   l.Add('describe DSPAM_SPAM DSPAM claims it is spam');
   l.Add('score DSPAM_SPAM 0.5');

   l.Add('header DSPAM_HAM X-DSPAM-Result =~ /^Innocent$/');
   l.Add('describe DSPAM_HAM DSPAM claims it is ham');
   l.Add('score DSPAM_HAM -0.1');
   try
   l.SaveToFile(SPAMASSASSIN_LOCAL_CF());
   except
     logs.Syslogs('Starting......: Unable to patch !!! '+SPAMASSASSIN_LOCAL_CF()+' for dpsam+amavis');
     l.free;
   end;
end else begin
     logs.DebugLogs('Starting......: patching '+SPAMASSASSIN_LOCAL_CF()+' already done..');
end;
end;
//#############################################################################
function Tspamass.ReadFileIntoString(path:string):string;
var
   List:TstringList;
begin

      if not FileExists(path) then begin
        exit;
      end;

      List:=Tstringlist.Create;
      List.LoadFromFile(path);
      result:=List.Text;
      List.Free;
end;
//##############################################################################
function Tspamass.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
var
   i:integer;
   s:string;
   RegExpr:TRegExpr;

begin
 result:=false;
 s:='';
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
function Tspamass.SPAMASSASSIN_LOCAL_CF():string;
begin
if FileExists('/etc/spamassassin/local.cf') then exit('/etc/spamassassin/local.cf');
if FileExists('/etc/mail/spamassassin/local.cf') then exit('/etc/mail/spamassassin/local.cf');
if FileExists('/opt/artica/etc/spamassassin/local.cf') then exit('/opt/artica/etc/spamassassin/local.cf');

ForceDirectories('/etc/spamassassin');
fpsystem('/bin/touch /etc/spamassassin/local.cf');
exit('/etc/spamassassin/local.cf');

end;
//##############################################################################

function Tspamass.RAZOR_GET_VALUE(key:string):string;
var
   l:Tstringlist;
   RegExpr:TRegExpr;

   i:integer;
begin
     if not FileExists(RAZOR_AGENT_CONF_PATH()) then exit;
     RegExpr:=TRegExpr.Create;
     l:=Tstringlist.Create;
     l.LoadFromFile(RAZOR_AGENT_CONF_PATH());
     RegExpr.Expression:='^'+key+'[\s=]+(.+)';
     for i:=0 to l.Count-1 do begin
          if RegExpr.Exec(l.Strings[i]) then begin
            result:=trim(RegExpr.Match[1]);
            break;
          end;
     end;


     l.free;
     RegExpr.free;
end;
//##############################################################################
procedure Tspamass.DEFAULT_SETTINGS();
var
   l:TstringList;
   sapmcfDir:string;
   auto_whitelist_path:string;
   auto_whitelist_file_mode:string;
begin

if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Debuglogs('Starting......: Unable to stat spamassassin local.cf');
   exit;
end;
sapmcfDir:=ExtractFilePath(SPAMASSASSIN_LOCAL_CF());

SPAMASSASSIN_ADD_PLUGIN('Rule2XSBody');

if enable_dkim_verification=1 then begin
    SPAMASSASSIN_ADD_PLUGIN('DKIM');
    logs.Debuglogs('Starting......: spamassassin DKIM Engine is enabled');
    fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.spamassassin.php --dkim');
end else begin
     logs.Debuglogs('Starting......: spamassassin DKIM Engine is disbaled');
     SPAMASSASSIN_REMOVE_PLUGIN('DKIM');
end;

SPAMASSASSIN_ADD_PLUGIN('AWL');
SPAMASSASSIN_init_pre();
if EnableSaBlackListUpdate=1 then SPAMASSASSIN_ADD_INCLUDE_FILE(sapmcfDir+'sa-blacklist.work') else SPAMASSASSIN_REMOVE_INCLUDE_FILE(sapmcfDir+'sa-blacklist.work');


auto_whitelist_path:=GET_VALUE('auto_whitelist_path');
auto_whitelist_file_mode:=GET_VALUE('auto_whitelist_file_mode');

if length(auto_whitelist_path)>0 then begin
        logs.Debuglogs('Starting......: spamassassin auto-whitelist path '+auto_whitelist_path+' chmod '+ auto_whitelist_file_mode);
        ForceDirectories(ExtractFilePath(auto_whitelist_path));
        if not FileExists(auto_whitelist_path) then fpsystem('/bin/touch  '+auto_whitelist_path +' >/dev/null 2>&1');
        fpsystem('/bin/chmod '+auto_whitelist_file_mode+' '+auto_whitelist_path);
end;





l:=TstringList.Create;
l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
if not IF_PATTERN_FOUND('^rewrite_header Subject') then begin
      logs.Debuglogs('Starting......: spamassassin Set default rewrite_header parameter');
      l.Add('rewrite_header Subject ***** SPAM *****');
end;

if not IF_PATTERN_FOUND('^required_score') then begin
   l.Add('required_score 5.0');
   logs.Debuglogs('Starting......: spamassassin Set default required_score parameter');
end;

if not IF_PATTERN_FOUND('^report_safe') then begin
   l.Add('report_safe 0');
   logs.Debuglogs('Starting......: spamassassin Set default report_safe parameter');
end;

if not IF_PATTERN_FOUND('^bayes_ignore_header') then begin
   l.Add('bayes_ignore_header X-Bogosity');
   l.Add('bayes_ignore_header X-Spam-Flag');
   l.Add('bayes_ignore_header X-Spam-Status');
   logs.Debuglogs('Starting......: Set default bayes_ignore_header');
end;

if not IF_PATTERN_FOUND('^use_bayes') then l.Add('use_bayes 1');
if not IF_PATTERN_FOUND('^bayes_auto_learn') then l.Add('bayes_auto_learn 1');



try
logs.WriteToFile(l.Text,SPAMASSASSIN_LOCAL_CF());
except
logs.Debuglogs('Starting......: spamassassin Unable to save configuration file '+SPAMASSASSIN_LOCAL_CF());
end;
l.free;
end;
//#########################################################################################
procedure Tspamass.SPAMASSASSIN_ADD_INCLUDE_FILE(filepath:string);
var l:TstringList;
    RegExpr:TRegExpr;
    i:integer;
    F:boolean;
begin
if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Debuglogs('SPAMASSASSIN_ADD_INCLUDE_FILE():: Unable to stat spamassassin local.cf');
   exit;
end;

if not FileExists(filepath) then begin
      logs.Debuglogs('Starting......: spamassassin Unable to stat file '+filepath);
      exit;
end;

   f:=false;
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^include\s+(.+)';
   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
          if trim(RegExpr.Match[1])=filepath then begin
             F:=true;
             break;
          end;
       end;
   end;
   
   if not F then begin
      try
         l.Add('include '+filepath);
         l.SaveToFile(SPAMASSASSIN_LOCAL_CF());
      except
         logs.Syslogs('SPAMASSASSIN_ADD_INCLUDE_FILE():: Unable to save configuration file '+SPAMASSASSIN_LOCAL_CF());
         exit;
      end;
   end else begin
       logs.Debuglogs('SPAMASSASSIN_ADD_INCLUDE_FILE():: '+ filepath +' is already included');
   end;
l.free;
   
end;
//#########################################################################################
function Tspamass.GET_VALUE(key:string):string;
var
   l:TstringList;
   i:integer;
   RegExpr:TRegExpr;
begin

   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^'+key+'\s+(.+)';
   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
          result:=trim(RegExpr.Match[1]);
          break;
       end;
   end;

   l.free;
   RegExpr.free;

end;

procedure Tspamass.SPAMASSASSIN_REMOVE_INCLUDE_FILE(filepath:string);
var l:TstringList;
    RegExpr:TRegExpr;
    i:integer;
    F:boolean;
    orgfilepath:string;
begin
if not FileExists(SPAMASSASSIN_LOCAL_CF()) then begin
   logs.Debuglogs('SPAMASSASSIN_REMOVE_INCLUDE_FILE():: Unable to stat spamassassin local.cf');
   exit;
end;
orgfilepath:=filepath;
RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^include\s+(.+)';
   l:=TstringList.Create;
   l.LoadFromFile(SPAMASSASSIN_LOCAL_CF());
   for i:=0 to l.Count-1 do begin
       if RegExpr.Exec(l.Strings[i]) then begin
          if trim(RegExpr.Match[1])=filepath then begin
             l.Delete(i);
             F:=true;
             break;
          end;
       end;
   end;

   if F then begin
        logs.Debuglogs('Starting......: Success remove '+orgfilepath);
        logs.WriteToFile(l.Text,SPAMASSASSIN_LOCAL_CF());
   end else begin
        logs.Debuglogs('Starting......: Remove '+orgfilepath +' Already removed');
   end;


   l.free;
   RegExpr.free;

end;
//#########################################################################################
procedure Tspamass.SPAMASSASSIN_init_pre();
var filename:string;
    l:Tstringlist;
begin
 filename:='/etc/spamassassin/init.pre';
 l:=Tstringlist.Create;
 logs.Debuglogs('Starting......: spamassassin URIDNSBL enabled');
 l.Add('loadplugin Mail::SpamAssassin::Plugin::Hashcash');
 if EnableSPF=1 then begin
    logs.Debuglogs('Starting......: spamassassin SPF enabled');
    fpsystem(SYS.LOCATE_PHP5_BIN()+' ' + artica_path+'/exec.spamassassin.php --spf');
 end;
  logs.WriteToFile(l.Text,filename);
  logs.Debuglogs('Starting......: spamassassin init.pre success');
  fpsystem(SYS.LOCATE_PHP5_BIN()+' ' + artica_path+'/exec.spamassassin.php --dnsbl');
 l.free;
end;
//#########################################################################################
procedure Tspamass.SPAMASSASSIN_ADD_PLUGIN(plugin:string);
var
   l:Tstringlist;
   RegExpr:TRegExpr;
   found:boolean;
   filename:string;
   i:integer;
begin
    filename:='/etc/spamassassin/v310.pre';
    if not FileExists(filename) then begin
       ForceDirectories(ExtractFilePath(filename));
       fpsystem('/bin/touch '+filename);
    end;


    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='^loadplugin Mail.+?'+plugin;
    l:=TstringList.Create;
    l.LoadFromFile(filename);
    found:=false;
    for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
            found:=true;
            break;
         end;
    end;

    if not found then begin
        logs.DebugLogs('Starting......: spamassassin adding new plugin ' + plugin);
        l.Add('loadplugin Mail::SpamAssassin::Plugin::'+plugin);
        logs.WriteToFile(l.Text,filename);
    end;

    l.free;
    RegExpr.free;

end;
//#########################################################################################
procedure   Tspamass.SPAMASSASSIN_REMOVE_PLUGIN(plugin:string);
var
   l:Tstringlist;
   RegExpr:TRegExpr;
   found:boolean;
   filename:string;
   i:integer;
begin
 filename:='/etc/spamassassin/v310.pre';
    if not FileExists(filename) then begin
       ForceDirectories(ExtractFilePath(filename));
       fpsystem('/bin/touch '+filename);
    end;


    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='^loadplugin Mail.+?'+plugin;
    l:=TstringList.Create;
    l.LoadFromFile(filename);
    found:=false;
    for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
            l.Delete(i);
            found:=true;
            break;
         end;
    end;

    if found then logs.WriteToFile(l.Text,filename);
    l.free;
    RegExpr.free;
end;

//#########################################################################################

end.
