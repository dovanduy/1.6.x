unit setup_haproxy;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}
interface

uses
  Classes, SysUtils,RegExpr in 'RegExpr.pas',
  unix,setup_libs,distridetect,
  install_generic,zsystem;

  type
  haproxy=class


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
END;

implementation

constructor haproxy.Create();
begin
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
install:=tinstall.Create;
source_folder:='';
SYS:=Tsystem.Create;
end;
//#########################################################################################
procedure haproxy.Free();
begin
  libs.Free;
end;
//#########################################################################################      e
procedure haproxy.xinstall();
var
local_int_version:integer;
remote_int_version:integer;
remote_str_version:string;
cmd:string;
begin

  install.INSTALL_PROGRESS('APP_HAPROXY','{checking}');


  install.INSTALL_PROGRESS('APP_HAPROXY','{downloading}');
  install.INSTALL_STATUS('APP_HAPROXY',30);
  SetCurrentDir('/root');


  if length(source_folder)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('haproxy');
  if not DirectoryExists(source_folder) then begin
     writeln('Install haproxy failed...');
     install.INSTALL_PROGRESS('APP_HAPROXY','{failed}');
     install.INSTALL_STATUS('APP_HAPROXY',110);
     exit;
  end;


  writeln('Install haproxy extracted on "'+source_folder+'"');
  install.INSTALL_STATUS('APP_HAPROXY',50);
  install.INSTALL_PROGRESS('APP_HAPROXY','{compiling}');


  SetCurrentDir(source_folder);
  cmd:='make PREFIX=/usr IGNOREGIT=true MANDIR=/usr/share/man DOCDIR=/usr/share/doc/haproxy USE_PCRE=1 TARGET=linux26 USE_LINUX_SPLICE=1 USE_LINUX_TPROXY=1 USE_REGPARM=1';
  fpsystem(cmd);
  fpsystem('make install');
  SetCurrentDir('/root');

if not FileExists(SYS.LOCATE_GENERIC_BIN('haproxy')) then begin
     writeln('Install haproxy failed...');
     install.INSTALL_PROGRESS('APP_HAPROXY','{failed}');
     install.INSTALL_STATUS('APP_HAPROXY',110);
     exit;
end;

    writeln('Install haproxy success...');
    install.INSTALL_PROGRESS('APP_HAPROXY','{success}');
    install.INSTALL_STATUS('APP_HAPROXY',100);


end;
//#########################################################################################


end.
