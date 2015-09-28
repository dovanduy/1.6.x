program setup_zarafa_debian;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils
  { you can add units after this }, setup_ubuntu_class, distriDetect,setup_libs,unix;

  var
     install:tubuntu;
     EnableSystemUpdates:integer;
     distri:tdistriDetect;
     libs:tlibs;
     ArchStruct:integer;
begin

     if FileExists('/etc/artica-postfix/PROXYTINY_APPLIANCE') then begin
        writeln('This program cannot be executed in Tiny Proxy mode...');
        halt(0);
     end;





     distri:=tdistriDetect.Create;
     libs:=tlibs.Create;
     try
        libs.EXPORT_PATH();
     except
     writeln('ERROR while exporting PATH');
     end;



     ForceDirectories('/etc/artica-postfix/settings/Daemons');
     fpsystem('/bin/echo "'+distri.DISTRINAME_CODE+'" >/etc/artica-postfix/settings/Daemons/LinuxDistributionCodeName');
     fpsystem('/bin/echo "'+distri.DISTRINAME+'" >/etc/artica-postfix/settings/Daemons/LinuxDistributionFullName');
     if ParamStr(1)='--kill' then halt(0);


     if ParamStr(1)='--version' then begin
        writeln('Artica binary installer version 1.5 (March 2014) on '+distri.DISTRINAME,' version ',distri.DISTRI_MAJOR,'.',distri.DISTRI_MINOR);
        halt(0);
     end;

     if ParamStr(1)='--distri' then begin
            writeln('CODE:'+distri.DISTRINAME_CODE);
            writeln('NAME:'+distri.DISTRINAME);
            writeln('VERSION:'+distri.DISTRINAME_VERSION);
            writeln('MAJOR:',distri.DISTRI_MAJOR);
            writeln('MINOR:',distri.DISTRI_MINOR);
            halt(0);
     end;




     if ParamStr(1)='--help' then begin
        writeln('This tool is designed to install a full Zarafa Server + Postfix on a Debian 7 system 64Bits');
        writeln('--version             Display tool version');
        writeln('--distri              Display Detected OS');
        writeln('--verbose             verbosed mode');
        halt(0);
     end;
     if ParamStr(1)='--h' then begin
        writeln('This tool is designed to install a full Zarafa Server + Postfix on a Debian 7 system 64Bits');
        writeln('--version             Display tool version');
        writeln('--distri              Display Detected OS');
        writeln('--verbose             verbosed mode');
        halt(0);
     end;

if length(paramstr(1))>0 then begin
   writeln('Unable to understand ',paramstr(1));
   halt(0);
end;
writeln('initialize... ');
writeln('Detected:',distri.DISTRINAME_CODE+' "' + distri.DISTRINAME_VERSION+'" Major version:', distri.DISTRI_MAJOR,' Minor:', distri.DISTRI_MINOR,' Arch:',libs.ArchStruct(),'bits kernel: "'+libs.KERNEL_VERSION()+'"');

if FileExists('/etc/init.d/zimbra') then begin
   writeln('It seems that Zimbra is installed on this computer.');
   writeln('Artica did not support server with Zimbra installed');
   writeln('Choose a fresh server instead');
   halt(0);
end;

ArchStruct:=distri.ArchStruct();

if ArchStruct<>64 then begin
   writeln('Only Debian 7.x+ 64bits');
    writeln('Not supported ' + IntTostr(ArchStruct)+'Bits');
    halt(0);
end;

if distri.DISTRINAME_CODE<>'DEBIAN' then begin
   writeln('Only Debian 7.x+ 64bits');
   writeln('Not supported ' + distri.DISTRINAME+ '/' + distri.DISTRINAME_CODE+' Version:'+IntToStr(distri.DISTRI_MAJOR));
   halt(0);
end;
if distri.DISTRI_MAJOR<6 then begin
   writeln('Only Debian 7.x+ 64bits');
   writeln('Not supported ' + distri.DISTRINAME+ '/' + distri.DISTRINAME_CODE+' Version:'+IntToStr(distri.DISTRI_MAJOR));
   halt(0);
end;
install:=tubuntu.Create;
install.Show_Welcome();
halt(0);


end.

