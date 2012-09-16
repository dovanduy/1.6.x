<?php
if(!is_dir("/etc/apparmor.d")){die();}
$f[]="/usr/sbin/mysqld flags=(complain) {";
$f[]="  #include <abstractions/base>";
$f[]="  #include <abstractions/nameservice>";
$f[]="  #include <abstractions/user-tmp>";
$f[]="  #include <abstractions/mysql>";
$f[]="  #include <abstractions/winbind>";
$f[]="";
$f[]="  capability dac_override,";
$f[]="  capability sys_resource,";
$f[]="  capability setgid,";
$f[]="  capability setuid,";
$f[]="";
$f[]="  network tcp,";
$f[]="";
$f[]="  /etc/hosts.allow r,";
$f[]="  /etc/hosts.deny r,";
$f[]="";
$f[]="  /etc/mysql/*.pem r,";
$f[]="  /etc/mysql/conf.d/ r,";
$f[]="  /etc/mysql/conf.d/* r,";
$f[]="  /etc/mysql/my.cnf r,";
$f[]="  /usr/sbin/mysqld mr,";
$f[]="  /usr/share/mysql/** r,";
$f[]="  /var/log/mysql.log rw,";
$f[]="  /var/log/mysql.err rw,";
$f[]="  /var/lib/mysql/ r,";
$f[]="  /var/lib/mysql/** rwk,";
$f[]="  /var/log/mysql/ r,";
$f[]="  /var/log/mysql/* rw,";
$f[]="  /var/run/mysqld/mysqld.pid w,";
$f[]="  /var/run/mysqld/mysqld.sock w,";
$f[]=" /var/run/mysqld/ r,";
$f[]=" /var/run/mysqld/** rwk,";
$f[]=" /sys/devices/system/cpu/ r,";
$f[]="}\n";

@file_put_contents("/etc/apparmor.d/usr.sbin.mysqld", @implode("\n", $f));
$f=array();
?>