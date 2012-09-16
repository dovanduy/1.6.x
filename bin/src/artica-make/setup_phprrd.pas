unit setup_phprrd;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}
//ln -s /usr/lib/libmilter/libsmutil.a /usr/local/lib/libsmutil.a
//apt-get install libmilter-dev
interface

uses
  Classes, SysUtils,strutils,RegExpr in 'RegExpr.pas',
  unix,IniFiles,setup_libs,distridetect,zsystem,
  install_generic;

  type
  tsetup_phprrd=class


private
     libs:tlibs;
     distri:tdistriDetect;
     install:tinstall;
   source_folder,cmd:string;
   webserver_port:string;
   artica_admin:string;
   artica_password:string;
   ldap_suffix:string;
   mysql_server:string;
   mysql_admin:string;
   mysql_password:string;
   ldap_server:string;
   SYS:Tsystem;




public
      constructor Create();
      procedure Free;
      procedure xinstall();
      procedure xmemcached_install();
END;

implementation

constructor tsetup_phprrd.Create();
begin
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
install:=tinstall.Create;
source_folder:='';
SYS:=Tsystem.create;
end;
//#########################################################################################
procedure tsetup_phprrd.Free();
begin
  libs.Free;
end;
//#########################################################################################
procedure tsetup_phprrd.xinstall();
var
local_int_version:integer;
Arch:integer;
apt_get_path,sedpath,phpizebin,cmd,extdir:string;

begin


install.INSTALL_PROGRESS('APP_PHP5_RRD','{checking}');
Arch:=libs.ArchStruct();
extdir:=SYS.LOCATE_PHP5_EXTENSION_DIR();
writeln('RESULT.................: Architecture : ',Arch);
writeln('RESULT.................: Distribution : ',distri.DISTRINAME,' (DISTRINAME)');
writeln('RESULT.................: Major version: ',distri.DISTRI_MAJOR,' (DISTRI_MAJOR)');
writeln('RESULT.................: Artica Code  : ',distri.DISTRINAME_CODE,' (DISTRINAME_CODE)');
writeln('RESULT.................: php ext dir  : ',extdir);
apt_get_path:=SYS.LOCATE_GENERIC_BIN('apt-get');
sedpath:=SYS.LOCATE_GENERIC_BIN('sed');
phpizebin:=SYS.LOCATE_GENERIC_BIN('phpize');

if not FIleExists(phpizebin) then begin
     writeln('Distribution phpize not such binary');
     install.INSTALL_PROGRESS('APP_PHP5_RRD','{failed}');
     install.INSTALL_STATUS('APP_PHP5_RRD',110);
     exit;
end;

if not FIleExists('/usr/include/rrd.h') then begin
     writeln('/usr/include/rrd.h no such file');
     install.INSTALL_PROGRESS('APP_PHP5_RRD','{failed}');
     install.INSTALL_STATUS('APP_PHP5_RRD',110);
     exit;
end;
    install.INSTALL_STATUS('APP_PHP5_RRD',60);
    install.INSTALL_PROGRESS('APP_PHP5_RRD','{downloading}');
 source_folder:=libs.COMPILE_GENERIC_APPS('php-rrdtool');
 if not DirectoryExists(source_folder) then begin
    install.INSTALL_STATUS('APP_PHP5_RRD',110);
    install.INSTALL_PROGRESS('APP_PHP5_RRD','{failed}');
    exit;
end;

SetCurrentDir(source_folder);
    install.INSTALL_STATUS('APP_PHP5_RRD',70);
    install.INSTALL_PROGRESS('APP_PHP5_RRD','{configure}');
fpsystem(phpizebin);
if not FileExists(source_folder+'/configure') then begin
    writeln(source_folder+'/configure no such file');
    install.INSTALL_STATUS('APP_PHP5_RRD',110);
    install.INSTALL_PROGRESS('APP_PHP5_RRD','{failed}');
    exit;
end;
fpsystem('./configure');
fpsystem('make');
install.INSTALL_STATUS('APP_PHP5_RRD',90);
install.INSTALL_PROGRESS('APP_PHP5_RRD','{installing}');
fpsystem('make install');
if FileExists(extdir+'/rrdtool.so') then begin
   install.INSTALL_STATUS('APP_PHP5_RRD',100);
   install.INSTALL_PROGRESS('APP_PHP5_RRD','{success}');
   writeln(extdir+'/rrdtool.so success');
   exit;
end;
 writeln(extdir+'/rrdtool.so no such libray');
   install.INSTALL_STATUS('APP_PHP5_RRD',110);
   install.INSTALL_PROGRESS('APP_PHP5_RRD','{failed}');

end;
//#########################################################################################
procedure tsetup_phprrd.xmemcached_install();
var
local_int_version:integer;
Arch:integer;
apt_get_path,sedpath,phpizebin,cmd,extdir:string;

begin

source_folder:='';
install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{checking}');
Arch:=libs.ArchStruct();
extdir:=SYS.LOCATE_PHP5_EXTENSION_DIR();
writeln('RESULT.................: Architecture : ',Arch);
writeln('RESULT.................: Distribution : ',distri.DISTRINAME,' (DISTRINAME)');
writeln('RESULT.................: Major version: ',distri.DISTRI_MAJOR,' (DISTRI_MAJOR)');
writeln('RESULT.................: Artica Code  : ',distri.DISTRINAME_CODE,' (DISTRINAME_CODE)');
writeln('RESULT.................: php ext dir  : ',extdir);
apt_get_path:=SYS.LOCATE_GENERIC_BIN('apt-get');
sedpath:=SYS.LOCATE_GENERIC_BIN('sed');
phpizebin:=SYS.LOCATE_GENERIC_BIN('phpize');

if not FIleExists(phpizebin) then begin
     writeln('Distribution phpize not such binary');
     install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{failed}');
     install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
     exit;
end;


if not FileExists('/usr/lib/libmemcached.a') then begin
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',20);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{downloading}');
   if length(source_folder)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('libmemcached');

  if not DirectoryExists(source_folder) then begin
     writeln('Install APP_PHP5_MEMCACHED failed...');
     install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
     exit;
  end;

    SetCurrentDir(source_folder);
    cmd:='./configure  --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info"';
    cmd:=cmd+' --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/memcached" --disable-maintainer-mode --disable-dependency-tracking';
    writeln(cmd);
    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',25);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{configure}');
    fpsystem(cmd);

    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',30);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{building}');
    fpsystem('make');

    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',35);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{intalling}');
    fpsystem('make install');

    if not FileExists('/usr/include/libmemcached/memcached.h') then begin
     install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
     writeln('/usr/include/libmemcached/memcached.h no such file');
     install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','/usr/include/libmemcached/memcached.h no such file');
     exit;
    end;
end;

    if not FileExists('/usr/include/libmemcached/memcached.h') then begin
       install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
       writeln('/usr/include/libmemcached/memcached.h no such file');
       install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','/usr/include/libmemcached/memcached.h no such file');
       exit;
    end;


   source_folder:='';
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',40);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{downloading}');
   if length(source_folder)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('memcached');

  if not DirectoryExists(source_folder) then begin
     writeln('Install APP_PHP5_MEMCACHED failed...');
     install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
     exit;
  end;
   SetCurrentDir(source_folder);
    cmd:='./configure  --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info"';
    cmd:=cmd+' --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/memcached" --disable-dependency-tracking';
    writeln(cmd);
    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',45);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{configure}');
    fpsystem(cmd);

    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',50);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{building}');
    fpsystem('make');

    install.INSTALL_STATUS('APP_PHP5_MEMCACHED',55);
    install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{intalling}');
    fpsystem('make install');

    if not FileExists(SYS.LOCATE_GENERIC_BIN('memcached')) then begin
       install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
       writeln('/usr/bin/memcached no such file');
       install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','/usr/bin/memcached no such file');
       exit;
    end;

   source_folder:='';
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',60);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{downloading}');
   if length(source_folder)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('memcache');

  if not DirectoryExists(source_folder) then begin
     writeln('Install APP_PHP5_MEMCACHED failed...');
     install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
     exit;
  end;
  SetCurrentDir(source_folder);
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',65);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{configure}');
   writeln(phpizebin);
   fpsystem(phpizebin);
   cmd:='./configure';
   writeln(cmd);
   fpsystem(cmd);
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',70);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{building}');
   fpsystem('make');

   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',80);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{intalling}');
   fpsystem('make install');


if FileExists(extdir+'/memcache.so') then begin
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',100);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{success}');
   writeln(extdir+'/memcache.so success');
   exit;
end;
 writeln(extdir+'/memcache.so no such libray');
   install.INSTALL_STATUS('APP_PHP5_MEMCACHED',110);
   install.INSTALL_PROGRESS('APP_PHP5_MEMCACHED','{failed}');

end;
//#########################################################################################

end.
