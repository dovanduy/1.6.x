unit framework;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,awstats;

type
  TStringDynArray = array of string;

  type
  tframework=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     awstats:tawstats;
     mem_pid:string;

    function    APACHE_SET_MODULES():string;
    procedure   APACHE_CONFIG();
    function    APACHE_ADD_MODULE(moduleso_file:string):string;
    function    APACHE_AuthorizedModule(module_so:string):boolean;

public
EnableLighttpd:integer;
    InsufficentRessources:Boolean;
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    function    LIGHTTPD_BIN_PATH():string;
    FUNCTION    STATUS():string;
    function    PHP5_CGI_BIN_PATH():string;
    function    MON():string;
    procedure   INSTALL_INIT_D();

END;

implementation                      

constructor tframework.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       forcedirectories('/opt/artica/tmp');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       EnableLighttpd:=1;
       awstats:=tawstats.Create(SYS);
        InsufficentRessources:=SYS.ISMemoryHiger1G();



       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tframework.free();
begin
    logs.Free;
    SYS.Free;
end;
//##############################################################################
function tframework.LIGHTTPD_BIN_PATH():string;
begin
exit(SYS.LOCATE_LIGHTTPD_BIN_PATH());
end;
//##############################################################################
function tframework.PHP5_CGI_BIN_PATH():string;
begin
   if FileExists('/usr/bin/php-fcgi') then exit('/usr/bin/php-fcgi');
   if FileExists('/usr/bin/php-cgi') then exit('/usr/bin/php-cgi');
   if FileExists('/usr/local/bin/php-cgi') then exit('/usr/local/bin/php-cgi');
end;
//##############################################################################



procedure tframework.APACHE_CONFIG();

var
l:Tstringlist;
begin
l:=Tstringlist.Create;
l.add('ServerRoot "/usr/share/artica-postfix/framework"');
l.add('Listen 127.0.0.1:47980');
l.add('');
l.add('<IfModule !mpm_netware_module>');
l.add('User apache-root');
l.add('Group root');
l.add('ServerName '+GetHostname());
l.add('</IfModule>');
l.add('');
l.add('ServerAdmin you@example.com');
l.add('DocumentRoot "/usr/share/artica-postfix/framework"');
l.add('');
l.add('<Directory />');
l.add('    Options FollowSymLinks');
l.add('    AllowOverride None');
l.add('    Order deny,allow');
l.add('    Deny from all');
l.add('</Directory>');
l.add('');
l.add('');
l.add('<Directory "/usr/share/artica-postfix/framework">');
l.add('    Options Indexes FollowSymLinks');
l.add('    AllowOverride None');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('');
l.add('</Directory>');
l.add('');
l.add('<IfModule dir_module>');
l.add('    DirectoryIndex index.php');
l.add('</IfModule>');
l.add('');
l.add('');
l.add('ErrorLog	/var/log/artica-postfix/framework_error.log');
l.add('');
l.add('LogLevel warn');
l.add('');
l.add('<IfModule log_config_module>');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b" common');
l.add('');
l.add('    <IfModule logio_module>');
l.add('      LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %I %O" combinedio');
l.add('    </IfModule>');
l.add('');
l.add('CustomLog	/var/log/artica-postfix/framework_error.log common');
l.add('</IfModule>');
l.add('');
l.add('<IfModule alias_module>');
l.add('    ScriptAlias /cgi-bin/ "/opt/artica/cgi-bin/"');
l.add('');
l.add('</IfModule>');
l.add('');
l.add('<IfModule cgid_module>');
l.add('</IfModule>');
l.add('');
l.add('<Directory "/opt/artica/cgi-bin">');
l.add('    AllowOverride None');
l.add('    Options None');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('</Directory>');
l.add('');
l.add('DefaultType text/plain');
l.add('');
l.add('<IfModule mime_module>');
l.add('TypesConfig	/etc/mime.types');
l.add('');
l.add('    #AddType application/x-gzip .tgz');
l.add('    #AddEncoding x-compress .Z');
l.add('    #AddEncoding x-gzip .gz .tgz');
l.add('    AddType application/x-compress .Z');
l.add('    AddType application/x-gzip .gz .tgz');
l.add('    AddType application/x-httpd-php .php .phtml');
l.add('    AddType application/x-httpd-php-source .phps');
l.add('    #AddHandler cgi-script .cgi');
l.add('    #AddType text/html .shtml');
l.add('    #AddOutputFilter INCLUDES .shtml');
l.add('</IfModule>');
l.add('');
l.add('#MIMEMagicFile conf/magic');
l.add('#ErrorDocument 500 "The server made a boo boo."');
l.add('#ErrorDocument 404 /missing.html');
l.add('#ErrorDocument 404 "/cgi-bin/missing_handler.pl"');
l.add('#ErrorDocument 402 http://www.example.com/subscription_info.html');
l.add('#EnableMMAP off');
l.add('#EnableSendfile off');
l.add('');
l.add('PidFile	/var/run/lighttpd/framework.pid');
l.Add(APACHE_SET_MODULES());
logs.WriteToFile(l.Text,'/etc/artica-postfix/framework.conf');
l.free;
end;
//##############################################################################
function tframework.APACHE_SET_MODULES():string;
var
i:integer;
APACHE_MODULES_PATH:string;
l:Tstringlist;
xmod:string;
begin
l:=Tstringlist.Create;
APACHE_MODULES_PATH:=SYS.LOCATE_APACHE_MODULES_PATH();

if length(APACHE_MODULES_PATH)=0 then begin
    logs.DebugLogs('Starting......: Framework Apache daemon unable to locate modules path');
    result:='';
    exit;
end;


logs.DebugLogs('Starting......: Framework Apache daemon modules are stored in '+APACHE_MODULES_PATH);
SYS.DirFiles(SYS.LOCATE_APACHE_MODULES_PATH(),'*.so');
 for i:=0 to SYS.DirListFiles.Count-1 do begin
       xmod:=trim(APACHE_ADD_MODULE(SYS.DirListFiles.Strings[i]));
       if length(xmod)=0 then begin
          logs.DebugLogs('Apache daemon refused mod:"'+SYS.DirListFiles.Strings[i]+'"');
          continue;
       end;
       logs.DebugLogs('Starting......: Framework Apache daemon add mod:"'+SYS.DirListFiles.Strings[i]+'"');
       l.Add(APACHE_ADD_MODULE(SYS.DirListFiles.Strings[i]));
end;

if FileExists('/usr/lib/apache-extramodules/mod_php5.so') then begin
   logs.DebugLogs('Starting......: Framework Apache daemon add mod:"mod_php5.so"');
   l.add('LoadModule php5_module'+chr(9)+'/usr/lib/apache-extramodules/mod_php5.so');
end;


logs.DebugLogs('Starting......: Framework Apache daemon '+IntTOstr(l.Count) +' modules');
result:=l.Text;

end;
//##############################################################################
function tframework.APACHE_ADD_MODULE(moduleso_file:string):string;
  var
   RegExpr:TRegExpr;
   ADD:boolean;
   l:TstringList;
   i:integer;
   moduleso_file_pattern:string;
   APACHE_MODULES_PATH:string;
   module_name:string;
begin
moduleso_file:=trim(moduleso_file);
APACHE_MODULES_PATH:=SYS.LOCATE_APACHE_MODULES_PATH();


if moduleso_file='mod_perl.so' then begin
    result:='LoadModule perl_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_log_config.so' then begin
    result:='LoadModule log_config_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_vhost_ldap.so' then begin
    result:='LoadModule vhost_ldap_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;


if moduleso_file='mod_ldap.so' then begin
    result:='LoadModule ldap_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_rewrite.so' then begin
    result:='LoadModule rewrite_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;



if not APACHE_AuthorizedModule(moduleso_file) then exit;
if moduleso_file='mod_proxy_connect.so' then exit;
if moduleso_file='mod_dav_lock.so' then exit;
if moduleso_file='mod_mem_cache.so' then exit;
if moduleso_file='mod_cgid.so' then exit;
if moduleso_file='mod_proxy.so' then exit;
if moduleso_file='mod_proxy_http.so' then exit;
if moduleso_file='mod_proxy_ajp.so' then exit;

ADD:=false;
moduleso_file_pattern:=AnsiReplaceText(moduleso_file,'.','\.');
RegExpr:=TRegExpr.CReate;
RegExpr.Expression:='^mod_(.+?)\.so';
if RegExpr.Exec(moduleso_file) then begin
     module_name:=RegExpr.Match[1]+'_module';
end else begin
    RegExpr.Expression:='^(.+?)\.so';
    module_name:=RegExpr.Match[1]+'_module';
end;


if moduleso_file='libphp5.so' then module_name:='php5_module';
result:='LoadModule '+ module_name+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
end;
//##############################################################################
function tframework.APACHE_AuthorizedModule(module_so:string):boolean;
var
   l:Tstringlist;
   i:integer;
begin

l:=Tstringlist.Create;
result:=false;
l.add('mod_alias.so');
l.add('mod_auth_basic.so');
l.add('mod_authn_file.so');
l.add('mod_authz_default.so');
l.add('mod_authz_groupfile.so');
l.add('mod_authz_host.so');
l.add('mod_authz_user.so');
l.add('mod_autoindex.so');
l.add('mod_cgi.so');
l.add('mod_deflate.so');
l.add('mod_dir.so');
l.add('mod_env.so');
l.add('mod_mime.so');
l.add('mod_negotiation.so');
l.add('libphp5.so');
l.add('mod_setenvif.so');
l.add('mod_status.so');
l.add('mod_ssl.so');
for i:=0 to l.Count-1 do begin
    if l.Strings[i]=module_so then begin
       result:=true;
       break;
    end;

end;
l.free;

end;
//##############################################################################
function tframework.MON():string;
var
l:TstringList;
begin
l:=TstringList.Create;
l.ADD('check process '+ExtractFileName(LIGHTTPD_BIN_PATH())+' with pidfile /var/run/lighttpd/framework.pid');
l.ADD('group framework');
l.ADD('start program = "/etc/init.d/artica-postfix start apache"');
l.ADD('stop program = "/etc/init.d/artica-postfix stop apache"');
l.ADD('if 5 restarts within 5 cycles then timeout');
result:=l.Text;
l.free;
end;
//##############################################################################
FUNCTION tframework.STATUS():string;
var
pidpath:string;
begin

pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --framework >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);
end;

//##############################################################################
procedure tframework.INSTALL_INIT_D();
var
   l:Tstringlist;
   php:string;
begin
l:=Tstringlist.Create;
php:=SYS.LOCATE_PHP5_BIN();
l.add('#!/bin/sh');
 if fileExists('/sbin/chkconfig') then begin
    l.Add('# chkconfig: 2345 11 89');
    l.Add('# description: Artica-framework Daemon');
 end;
l.add('### BEGIN INIT INFO');
l.add('# Provides:          Artica-framework ');
l.add('# Required-Start:    $local_fs');
l.add('# Required-Stop:     $local_fs');
l.add('# Should-Start:');
l.add('# Should-Stop:');
l.add('# Default-Start:     2 3 4 5');
l.add('# Default-Stop:      0 1 6');
l.add('# Short-Description: Start Artica framework daemon');
l.add('# chkconfig: 2345 11 89');
l.add('# description: Artica framework Daemon');
l.add('### END INIT INFO');
l.add('');
l.add('case "$1" in');
l.add(' start)');
l.add('    '+php+' /usr/share/artica-postfix/exec.framework.php --start');
l.add('    ;;');
l.add('');
l.add('  stop)');
l.add('    '+php+' /usr/share/artica-postfix/exec.framework.php --stop');
l.add('    ;;');
l.add('');
l.add(' restart)');
l.add('    '+php+' /usr/share/artica-postfix/exec.framework.php --stop');
l.add('     sleep 3');
l.add('    '+php+' /usr/share/artica-postfix/exec.framework.php --start');
l.add('    ;;');
l.add('');
l.add('  *)');
l.add('    echo "Usage: $0 {start|stop|restart}"');
l.add('    exit 1');
l.add('    ;;');
l.add('esac');
l.add('exit 0');

logs.WriteToFile(l.Text,'/etc/init.d/artica-framework');
 fpsystem('/bin/chmod +x /etc/init.d/artica-framework >/dev/null 2>&1');

 if FileExists('/usr/sbin/update-rc.d') then begin
    fpsystem('/usr/sbin/update-rc.d -f artica-framework defaults >/dev/null 2>&1');
 end;

  if FileExists('/sbin/chkconfig') then begin
     fpsystem('/sbin/chkconfig --add artica-framework >/dev/null 2>&1');
     fpsystem('/sbin/chkconfig --level 2345 artica-framework on >/dev/null 2>&1');
  end;

   LOGS.Debuglogs('Starting......: framework install init.d scripts........:OK (/etc/init.d/artica-framework {start,stop,restart})');



end;
//##############################################################################
end.

