unit setup_ubuntu_class;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}

interface

uses
  Classes, SysUtils,RegExpr in 'RegExpr.pas',unix,setup_libs,distridetect;
type
  TStringDynArray = array of string;
  type
  tubuntu=class


private
       ArchStruct:integer;
       AsKimSuffi:boolean;
       kvmready:boolean;
       libs:tlibs;
       EXCLUDE_APPS:TstringList;
       LIGHT_INSTALL:boolean;
       function is_application_installed(appname:string):boolean;
       function InstallPackageLists(list:string):boolean;
       function CheckCyrus():string;
       function CheckAmavisPerl():string;
       function Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
       procedure DisableApparmor();
       function UbuntuName():string;
       procedure checkRepostitories6();
       procedure S00vzreboot();
       function isVPSDetected():boolean;
       function is_application_excluded(appname:string):boolean;

public
      DEBUG:boolean;
      constructor Create();
      procedure Free;
      procedure Show_Welcome;
      function CheckDevcollectd():string;
      function checkSamba():string;
      function InstallPackageListssilent(list:string):boolean;
      function checkApps(l:tstringlist):string;
      function CheckBaseSystem():string;
      function CheckPostfix():string;
      function checkSQuid():string;
      function CheckNFS():string;
      function CheckBasePHP():string;
      function CheckPDNS():string;
      function CheckZabbix():string;
      function CheckOpenVPN():string;
      function CheckKvm():string;
      function CheckFuppes():string;
      function VirtualBoxAptConf():boolean;
      function CheckLXC():string;
      procedure InstallVirtualBox();
      procedure CheckvzQuota();
      function  CheckAmanda():string;
      function ejabberd():string;
      function CheckProxyAppliance():string;
      function CheckProxyAppliance_debs():string;
      function krb5():string;
END;

implementation

constructor tubuntu.Create();
begin

libs:=tlibs.Create;
ArchStruct:=libs.ArchStruct();
AsKimSuffi:=libs.IsKImsuffi();
EXCLUDE_APPS:=TStringList.Create;
   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   LIGHT_INSTALL:=false;

     if length(Paramstr(1))>0 then begin
        if Paramstr(1)<>'--check-base-system' then begin
            writeln('you can use --silent in order to install packages without human interaction');
        end;

        if Paramstr(1)='--light' then begin
           ForceDirectories('/etc/artica-postfix');
           fpsystem('/bin/touch /etc/artica-postfix/LIGHT_INSTALL');
        end;

        if FileExists('/etc/artica-postfix/LIGHT_INSTALL') then LIGHT_INSTALL:=true;

     end;
end;
//#########################################################################################
procedure tubuntu.Free();
begin
  libs.Free;
end;
//#########################################################################################
procedure tubuntu.DisableApparmor();
begin
  if FileExists('/etc/init.d/apparmor') then begin
     writeln('Disable AppArmor.....');
     fpsystem('/etc/init.d/apparmor stop');
     fpsystem('update-rc.d -f apparmor remove');
     fpsystem('/bin/mv /etc/init.d/apparmor /etc/init.d/bk.apparmor');
     writeln('You need to reboot after installation');
     writeln('Enter key');
     readln();
  end;

end;
//#########################################################################################
procedure tubuntu.Show_Welcome;
var
   base,postfix,u,cyrus,samba,squid,nfs,pdns,zabbix,openvpn,kvmPackages:string;
   srclist:tstringlist;
   distri:tdistriDetect;
   country:string;
begin

    distri:=tdistriDetect.Create;
    if not FileExists('/usr/bin/apt-get') then begin
      writeln('Your system does not store apt-get utils, this program must be closed...');
      exit;
    end;

    if FileExists('/etc/rc6.d/S00vzreboot') then begin
       writeln('Patching  /etc/rc6.d/S00vzreboot for the issue...');
       S00vzreboot();
    end;

    if not FileExists('/etc/resolvconf/resolv.conf.d/base') then begin
       fpsystem('/bin/cp /etc/resolv.conf /etc/resolvconf/resolv.conf.d/base');
    end;

    libs.CheckResolvConf();
    writeln('Checking.............: resolv.conf done');

    if not AsKimSuffi then begin
    if not FileExists('/tmp/apt-update.patch') then begin
       if distri.DISTRINAME_CODE='DEBIAN' then begin
          if distri.DISTRINAME_VERSION='6' then begin
             checkRepostitories6();
             writeln('Do want the installer patch your debian "sources.list" ?......: [Y]');
              readln(u);
              if length(u)=0 then u:='Y';
              if LowerCase(u)='y' then begin
                 writeln('Give the country code: fr or us or de or uk or it ...: [us]');
                 readln(country);
                 if length(country)=0 then country:='us';
                 country:=LowerCase(country);
                 srclist:=TStringList.Create;
                 srclist.add('deb http://ftp.'+country+'.debian.org/debian/ squeeze main');
                 srclist.add('deb http://www.deb-multimedia.org squeeze main non-free');
                 srclist.add('deb http://security.debian.org/ squeeze/updates main');
                 srclist.add('deb http://ftp.'+country+'.debian.org/debian/ squeeze-updates main');
                 srclist.add('');
                 srclist.add('deb-src http://ftp.'+country+'.debian.org/debian/ squeeze-updates main');
                 srclist.add('deb-src http://security.debian.org/ squeeze/updates main');
                 srclist.add('deb-src http://ftp.'+country+'.debian.org/debian/ squeeze main');


                 srclist.SaveToFile('/etc/apt/sources.list');
              end;
              fpsystem('touch /tmp/apt-update.patch');
            end;
       end;
    end;
    end;

    libs.CheckResolvConf();

    if not FileExists('/tmp/apt-update') then begin
       fpsystem('touch /tmp/apt-update');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update');
    end;
    

    kvmready:=false;
    writeln('Checking.............: system...');
    writeln('Checking.............: AppArmor...');
    DisableApparmor();
    writeln('Checking.............: Base system...');
    base:=CheckBaseSystem();
    writeln('Checking.............: Postfix system...');
    postfix:=CheckPostfix();
    if Not FileExists('/usr/bin/zarafa-server') then begin
       writeln('Checking.............: Cyrus system...');
       cyrus:=CheckCyrus();
    end;
    writeln('Checking.............: Files Sharing system...');
    samba:=checkSamba();
    writeln('Checking.............: Squid proxy and securities...');
    squid:=checkSQuid();
    writeln('Checking.............: NFS System...');
    nfs:=CheckNFS();
    writeln('Checking.............: PowerDNS System...');
    pdns:=CheckPDNS();
    writeln('Checking.............: OpenVPN System...');
    openvpn:=CheckOpenVPN();
    writeln('Checking.............: VZQuota issue on squeeze');
    CheckvzQuota();
    writeln('Checking.............: KVM compatibility');
    kvmready:=libs.CheckKVM();
    if kvmready then  kvmPackages:=CheckKvm() else  writeln('Checking.............: KVM compatibility [NO]');
    u:=libs.INTRODUCTION(base,postfix,cyrus,samba,squid,nfs,pdns,openvpn,kvmready,kvmPackages);
    u:=LowerCase(u);


    if length(u)=0 then begin
        if length(base)>0 then u:='B';
        writeln('Installing mandatories packages.....');
    end;

    writeln('You have selected the option : ' + u);

    if u='B' then begin
        if FileExists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 stop');
        if not FileExists('/tmp/apt-update-f') then begin
           fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
           fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
           fpsystem('/bin/touch /tmp/apt-update-f');
        end;
        InstallPackageLists(base);
        Show_Welcome();
        if FileExists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 start');
        exit;
    end;
    
    if length(u)=0 then begin
        Show_Welcome();
        exit;
    end;
    
    if lowercase(u)='a' then begin
       if FileExists('/usr/sbin/postconf') then fpsystem('/usr/sbin/postconf -e "mydomain = domain.tld"');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove exim*');
       InstallPackageLists(postfix+' '+cyrus+' '+samba+' '+squid);
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
       libs.InstallArtica();
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_ROUNDCUBE3');
          fpsystem('/etc/init.d/artica-postfix restart postfix >/dev/null 2>&1 &');
       Show_Welcome;
       exit;
    end;
    
    if u='1' then begin
           if FileExists('/usr/sbin/postconf') then fpsystem('/usr/sbin/postconf -e "mydomain = domain.tld"');
          InstallPackageLists(postfix);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove exim*');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart postfix >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          Show_Welcome;
          exit;
    end;
    
    if u='2' then begin
       if Not FileExists('/usr/bin/zarafa-server') then begin
          InstallPackageLists(cyrus);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart imap');
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_ROUNDCUBE3');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
       end;
          Show_Welcome;
          exit;
    end;
    
    if u='3' then begin
          InstallPackageLists(samba);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then  begin
             fpsystem('/etc/init.d/artica-postfix restart samba >/dev/null 2>&1 &');
             fpsystem('/usr/share/artica-postfix/bin/artica-install --nsswitch');
             fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          end;
          Show_Welcome;
          exit;
    end;
    
    if u='4' then begin
          InstallPackageLists(squid);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart squid >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          Show_Welcome;
          exit;
    end;
    
    if u='5' then begin
          libs.InstallArtica();
          Show_Welcome;
          exit;
    end;

    if u='6' then begin
          if FIleExists('/etc/init.d/portmap') then fpsystem('/etc/init.d/portmap start');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(nfs);
          if FIleExists('/etc/init.d/portmap') then fpsystem('/etc/init.d/portmap start');
          Show_Welcome;
          exit;
    end;

    if u='7' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove bind9 bind9-utils');
          InstallPackageLists(pdns);
          fpsystem('/etc/init.d/artica-postfix restart pdns');
          Show_Welcome;
          exit;
    end;

    if u='8' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove bind9 bind9-utils');
          InstallPackageLists(zabbix);
          fpsystem('/etc/init.d/artica-postfix restart zabbix');
          Show_Welcome;
          exit;
    end;

    if u='9' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(openvpn);
          Show_Welcome;
          exit;
    end;

     if u='k' then begin
      if kvmready then  begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(kvmPackages);
          fpsystem('adduser `id -un` libvirtd >/dev/null 2>&1');
      end;
          Show_Welcome;
          exit;
    end;




    if lowercase(u)='c' then begin
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_AMAVISD_MILTER');
          Show_Welcome;
          exit;
    end;


end;
//#########################################################################################



procedure tubuntu.CheckvzQuota();
var
   cmd:string;
   u  :string;
   i  :integer;
   l :Tstringlist;
   ll:Tstringlist;
   RegExpr:TRegExpr;
begin
  if not FileExists('/etc/init.d/vzquota') then exit;

  l:=Tstringlist.Create();
  l.LoadFromFile('/etc/init.d/vzquota');
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='Required-Start';
  for i:=0 to l.Count-1 do begin
      if  RegExpr.Exec(l.Strings[i]) then begin
         RegExpr.free;
         l.free;
         exit;
      end;
  end;

  writeln('/etc/init.d/vzquota is bugged, this setup will patch it...');
  ll:=tstringlist.Create;
  ll.add('#!/bin/sh');
  ll.add('### BEGIN INIT INFO');
  ll.add('# Provides:                 vzquota');
  ll.add('# Required-Start:');
  ll.add('# Required-Stop:');
  ll.add('# Should-Start:             $local_fs $syslog');
  ll.add('# Should-Stop:              $local_fs $syslog');
  ll.add('# Default-Start:            0 1 2 3 4 5 6');
  ll.add('# Default-Stop:');
  ll.add('# Short-Description:        Fixed(?) vzquota init script');
  ll.add('### END INIT INFO');
  for i:=1 to l.Count-1 do begin
      ll.Add(l.Strings[i]);
  end;

  ll.SaveToFile('/etc/init.d/vzquota');
  ll.free;
  l.free;
  RegExpr.free;

end;
//#########################################################################################


function tubuntu.InstallPackageLists(list:string):boolean;
var
   cmd:string;
   u  :string;
   i  :integer;
   ll :TStringDynArray;
begin
if length(trim(list))=0 then exit;
result:=false;

writeln('');
writeln('The following package(s) must be installed in order to perform continue setup');
writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('"',list,'"');
writeln('-----------------------------------------------------------------------------');
writeln('');
writeln('Do you allow install these packages? [Y]');

if not libs.COMMANDLINE_PARAMETERS('--silent') then begin
   readln(u);
end else begin
    u:='y';
end;


if length(u)=0 then u:='y';

if LowerCase(u)<>'y' then exit;


   ll:=Explode(',',list);
   for i:=0 to length(ll)-1 do begin
       if length(trim(ll[i]))>0 then begin


writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('');
writeln(' Check ',i,'/',length(ll)-1,': "',ll[i],'"');
writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('');

          if(trim(ll[i])='lighttpd') then begin
              writeln('Stopping apache2....');
              if Fileexists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 stop');
          end;

          cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install ' + ll[i];
          fpsystem(cmd);
       end;
   end;



   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   result:=true;


end;
//#########################################################################################
function tubuntu.InstallPackageListssilent(list:string):boolean;
var
   cmd:string;
   u  :string;
   i  :integer;
   ll :TStringDynArray;
   rebot:boolean;
begin
if length(trim(list))=0 then exit;
result:=false;
rebot:=false;

   writeln('Checking bad installations with -f install...');
   cmd:='/usr/bin/apt-get -y -f install';
   fpsystem(cmd);
   writeln('Checking Autoremove packages....');
   cmd:='/usr/bin/apt-get -y autoremove';
   fpsystem(cmd);
   writeln('Checking Autoremove packages....');
   if FileExists('/etc/artica-postfix/apt_get_update.time') then begin
      if libs.FILE_TIME_BETWEEN_MIN('/etc/artica-postfix/apt_get_update.time')>480 then begin
           cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get update';
           fpsystem(cmd);
           fpsystem('/bin/rm /etc/artica-postfix/apt_get_update.time');
           fpsystem('/bin/touch /etc/artica-postfix/apt_get_update.time');
      end;
   end else begin
           cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get update';
           fpsystem(cmd);
           fpsystem('/bin/rm /etc/artica-postfix/apt_get_update.time');
           fpsystem('/bin/touch /etc/artica-postfix/apt_get_update.time');
   end;

   writeln('****** Installing the following packages ******');
   writeln(list);
   writeln('');

   ll:=Explode(',',list);
   for i:=0 to length(ll)-1 do begin
       if length(trim(ll[i]))>0 then begin
          cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install ' + ll[i];
          fpsystem(cmd);
          rebot:=true;
       end;
   end;


   if rebot then begin
      if FileExists('/usr/share/artica-postfix/exec.initslapd.php') then fpsystem('php /usr/share/artica-postfix/exec.initslapd.php');
   end;
   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   result:=true;


end;
//#########################################################################################
function tubuntu.is_application_excluded(appname:string):boolean;
var

   RegExpr:TRegExpr;
   i:integer;
   D:boolean;
   tmpstr:string;
begin
     result:=false;
     if EXCLUDE_APPS.Count>0 then begin
        for i:=0 to EXCLUDE_APPS.Count-1 do begin
            if trim(EXCLUDE_APPS.Strings[i])=trim(appname) then exit(true);
        end;
        exit(false);
     end;
     if not FileExists('/root/exclude-apps.txt') then exit(false);
     if EXCLUDE_APPS.Count=0 then EXCLUDE_APPS.LoadFromFile('/root/exclude-apps.txt');


     if EXCLUDE_APPS.Count>0 then begin
        for i:=0 to EXCLUDE_APPS.Count-1 do begin
            if trim(EXCLUDE_APPS.Strings[i])=trim(appname) then exit(true);
        end;
        exit(false);
     end;

end;
//#########################################################################################
function tubuntu.is_application_installed(appname:string):boolean;
var
   l:TstringList;
   RegExpr:TRegExpr;
   RegExpr2:TRegExpr;
   i:integer;
   D:boolean;
   tmpstr:string;
begin
    D:=false;
    result:=false;
    appname:=trim(appname);
    if is_application_excluded(appname) then exit(true);


    D:=libs.COMMANDLINE_PARAMETERS('--verbose');
    if D then  DEBUG:=true;
    if not FileExists('/tmp/packages.list') then begin
       if DEBUG then writeln('/usr/bin/dpkg -l >/tmp/packages.list');
       fpsystem('/usr/bin/dpkg -l >/tmp/packages.list');
    end;

    l:=TstringList.Create;
    try
    l.LoadFromFile('/tmp/packages.list');
    except
          writeln('FATAL ERROR WHILE READING /tmp/packages.list! (L.542)');
    end;


    if l.Count<10 then begin
       fpsystem('/bin/rm -rf /tmp/packages.list');
       result:=is_application_installed(appname);
       exit;
    end;
    RegExpr:=TRegExpr.Create;
    RegExpr2:=TRegExpr.Create;

    
    for i:=0 to l.Count-1 do begin
        RegExpr.Expression:='ii\s+(.+?)(:|\s+)';
        if RegExpr.Exec(l.Strings[i]) then begin
           if lowercase(appname)='xtables-addons-modules' then begin
                tmpstr:=RegExpr.Match[1];
                RegExpr.Expression:='xtables-addons-modules.+?';
                if RegExpr.Exec(tmpstr) then begin
                 if DEBUG then writeln(appname,' already installed [OK]');
                 result:=true;
                 break;
                end;
           end;
           if lowercase(trim(RegExpr.Match[1]))=trim(lowercase(appname)) then begin
              if DEBUG then writeln(appname,' already installed [OK]');
              result:=true;
              break;
           end;
        end;

    end;
    if D then writeln('Search ',RegExpr.Expression,' failed');
    l.free;
    RegExpr.free;

end;

//#########################################################################################
function tubuntu.CheckNFS():string;
var
   l:TstringList;
   f:string;
   i:integer;
   UbuntuIntVer:integer;


begin

f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;

   l.add('portmap');
   l.add('nfs-common');
   l.add('nfs-kernel-server');

fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;

end;
//#########################################################################################
function tubuntu.CheckBasePHP():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   libmysqlclient:boolean;
   ArchStruct:integer;

begin
f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
writeln('Checking Code '+distri.DISTRINAME);
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;

if(UbuntuIntVer=0) then begin
      writeln('Unable to obtain Major release of '+distri.DISTRINAME);
      exit;
end;

l.add('apache2-prefork-dev');
l.add('libexpat1-dev');
l.add('libfreetype6-dev');
l.add('libgcrypt11-dev');


l.add('libgmp3-dev');
l.add('libkrb5-dev');
l.add('libldap2-dev');
l.add('libmcrypt-dev');
l.add('libmhash-dev');


l.add('libncurses5-dev');
l.add('libpam0g-dev');
l.add('libpcre3-dev');
l.add('libpng12-dev');
l.add('libpq-dev');
l.add('libpspell-dev');
l.add('librecode-dev');

l.add('libsqlite0-dev');
l.add('libssl-dev');
l.add('libt1-dev');
l.add('libtidy-dev');
l.add('libtool');
l.add('libwrap0-dev');
l.add('libxmltok1-dev');
l.add('libxml2-dev');
l.add('quilt');
l.add('re2c');
l.add('unixodbc-dev');
l.add('zlib1g-dev');
l.add('chrpath');
//l.add('debhelper');
l.add('flex');
l.add('freetds-dev');
l.add('munin');
l.add('munin-node');
l.add('quota');
l.add('psmisc');


if distri.DISTRINAME_CODE='DEBIAN' then begin

      writeln('Checking Code DEBIAN ('+distri.DISTRINAME_VERSION+')');

   if distri.DISTRI_MAJOR<5 then begin
      l.add('libc-client2002-dev');
      l.add('apache2-prefork-dev');
      l.add('libcurl3-openssl-dev');
      L.add('libsnmp9-dev');
      l.add('libmysqlclient15-dev');
      l.add('libgd2-xpm-dev');
   end;

   if distri.DISTRI_MAJOR<6 then begin
       l.add('libc-client2007b-dev');
       l.add('libcurl4-openssl-dev');
       l.add('libsnmp-dev');
       l.add('libmysqlclient15-dev');
       l.add('libxslt1-dev');
       l.add('libgd2-xpm-dev');

   end;

end;

if distri.DISTRINAME_CODE='UBUNTU' then begin
   writeln('Checking Code UBUNTU ('+distri.DISTRINAME_VERSION+') UbuntuIntVer='+IntToStr(UbuntuIntVer));
   l.add('libc-client2007b-dev');
   l.add('libcurl4-openssl-dev');
   l.add('apache2-prefork-dev');
   l.add('libsnmp-dev');
   l.add('libgd2-xpm-dev');


   if UbuntuIntVer<10 then begin
      l.add('libmysqlclient15-dev');
      l.add('libxslt1-dev');
      libmysqlclient:=true;
   end;


   if UbuntuIntVer>9 then  begin
      l.add('libmysqlclient-dev');
      libmysqlclient:=true;
   end;

    if UbuntuIntVer>11 then  begin
       l.add('libxslt1-dev-dev');
   end;
   if not libmysqlclient then begin
       if UbuntuIntVer=10 then l.add('libmysqlclient-dev');
       if UbuntuIntVer=9 then  l.add('libmysqlclient15-dev');
       if UbuntuIntVer=8 then  l.add('libmysqlclient15-dev');
   end;
end;

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;


end;
function tubuntu.CheckKvm():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   libs:tlibs;
   non_free:boolean;

begin
f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
if distri.DISTRINAME_CODE='DEBIAN' then begin
   if distri.DISTRINAME_VERSION='6' then begin
      l.add('kvm');
      l.add('qemu-kvm');
      l.add('libvirt-bin');
      l.add('virtinst');
      l.add('bridge-utils');
   end;
   if distri.DISTRINAME_VERSION='5' then begin
      l.add('kvm');
      l.add('libvirt-bin');
      l.add('virtinst');
      l.add('bridge-utils');
   end;
end;


if distri.DISTRINAME_CODE='UBUNTU' then begin
   l.Add('ubuntu-virt-server');
   l.add('python-vm-builder');
   l.add('bridge-utils');
end;

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;


end;

function tubuntu.CheckAmanda():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   libs:tlibs;
   non_free:boolean;

begin
f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
non_free:=libs.IS_DEBIAN_NON_FREE_IN_SOURCE_LIST();
l.Add('gnuplot-nox');
l.Add('dump'); //dump: /sbin/restore
l.add('xinetd');
L.add('libglib2.0-dev'); //glib.h

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;

function tubuntu.CheckBaseSystem():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   libs:tlibs;
   non_free:boolean;
   KERNEL_VERSION:string;
begin
f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
non_free:=libs.IS_DEBIAN_NON_FREE_IN_SOURCE_LIST();
KERNEL_VERSION:=libs.KERNEL_VERSION();
if Not DirectoryExists('/var/lib/nfs') then forceDirectories('/var/lib/nfs');
if Not DirectoryExists('/lib/init/rw/sendsigs.omit.d') then forceDirectories('/lib/init/rw/sendsigs.omit.d');

//l.Add('dhcp3-client');
l.Add('cron');
l.Add('debconf-utils');
l.Add('file');
l.Add('less');
l.Add('rsync');
l.Add('openssh-client ');
l.Add('openssh-server');
l.Add('strace');
l.add('mtools');
l.add('re2c');
l.add('cron');
l.add('debconf-utils');
l.add('file');
l.add('less');
l.add('rsync');
l.add('sudo');
l.add('iproute');
l.add('curl');
l.add('lm-sensors');
l.add('bison');
l.add('e2fsprogs');



if ArchStruct=64 then begin
   l.add('libc6-i386');
   l.add('lib32stdc++6');
   if distri.DISTRINAME_CODE='UBUNTU' then begin
      if UbuntuIntVer<12 then begin
         l.add('ia32-libs-gtk');
         l.add('libgd2-xpm-dev');
      end;
      if UbuntuIntVer>11 then l.add('ia32-libs');
   end;
   if distri.DISTRINAME_CODE='DEBIAN' then begin
      if distri.DISTRI_MAJOR<7 then begin
         l.add('ia32-libs-gtk');
         l.add('libgd2-xpm-dev');
      end;
   end;
end;


//lvm
if not libs.COMMANDLINE_PARAMETERS('--without-lvm') then begin
   if not LIGHT_INSTALL then l.add('lvm2');
end;

l.add('libc6-dev');
l.add('iptables-dev');
l.add('libssl-dev');
l.add('libpcap0.8-dev');
l.add('byacc');
l.Add('gcc');
l.Add('make');
l.Add('cmake');
l.Add('build-essential');
l.Add('flex');
l.add('libsasl2-dev');
l.add('libcdb-dev');
l.add('apt-mirror');


l.add('fuse-utils');

l.Add('time');
l.Add('eject');
l.Add('locales');

l.Add('pciutils ');
l.Add('usbutils');

if not FileExists('/etc/artica-postfix/NO_DATABASES_ENGINES') then begin
   l.Add('slapd');
   l.add('ldap-utils');
   if not FileExists('/usr/sbin/mysqld') then l.Add('mysql-server');
end;

l.Add('openssl');
l.Add('strace');



l.Add('time');
l.Add('eject');
l.Add('locales');
l.Add('pciutils');
l.Add('usbutils');
l.add('iotop');


//PHP engines
l.Add('php5-cgi');
l.Add('php5-cli');
l.Add('php5-ldap');
l.Add('php5-mysql');
l.Add('php5-gd');
l.add('php5-curl');
l.add('php5-sqlite');
l.Add('php-pear');
l.add('php5-dev'); // To compile PHP5
l.add('librrd-dev');
l.add('ucarp');
l.add('iputils-arping');
l.add('vde2');
l.add('libmodule-build-perl');
l.Add('librrds-perl');
l.add('dnsmasq');
l.Add('libwww-perl');

l.Add('libio-stringy-perl');



l.Add('libauthen-sasl-perl');
l.Add('libdbi-perl');
l.Add('libxml-namespacesupport-perl');
l.Add('libxml-sax-expat-perl');
l.Add('libxml-sax-perl');
l.Add('libxml-filter-buffertext-perl');
l.Add('libtext-iconv-perl');
l.Add('libxml-sax-writer-perl');
l.Add('libconvert-asn1-perl');
l.Add('libnet-ldap-perl');
l.Add('libio-socket-ssl-perl');
l.Add('libnet-ssleay-perl');
l.Add('libhtml-parser-perl');
l.Add('libarchive-zip-perl');
l.add('libsendmail-milter-perl');

l.Add('libwww-perl');
l.Add('libnss-ldap');
l.Add('libpam-ldap');
l.Add('ldap-utils');
if not LIGHT_INSTALL then l.add('resolvconf');



l.Add('sasl2-bin');
l.add('libsasl2-dev');
l.Add('sudo');
l.Add('ntp');
l.Add('iproute');
l.add('bzip2');
l.add('zip');
l.add('re2c');

if not LIGHT_INSTALL then l.add('httrack');//xapian


l.add('libgdbm-dev'); //for grawl


l.Add('libexpat1-dev');
l.Add('libxml2-dev');  //for squid & dansguardian
l.add('scons');
l.add('binutils');

l.add('zlib1g-dev');
l.Add('libpcre3-dev');
l.add('pkg-config');
l.add('libldap2-dev');
l.add('libpam0g-dev');
l.add('libcdio-dev');
l.add('libusb-dev');
l.add('libkrb5-dev');
l.add('zlib1g-dev');
l.add('libfreetype6-dev');
l.add('libt1-dev');
l.add('libpaper-dev');
l.add('libbz2-dev');
l.add('libxml2-dev');
l.add('libaudit-dev');
l.add('libgd-tools');
l.add('libfuse2');
l.add('libusb-0.1-4');
l.add('iptables-dev');
l.add('libssl-dev');
l.add('libpcap0.8-dev');
l.add('libsasl2-dev');
l.add('libcdb-dev');
l.add('pkg-config');
l.add('libpspell-dev');
l.add('libpng12-dev');
l.add('libaio-dev');
l.add('libattr1-dev');
l.add('libmhash-dev'); //mhash.h For LessFS
l.add('libevent-dev');

l.add('libbz2-dev'); // udfbguard
l.add('python-dev');

l.add('libgeoip-dev');
l.add('libgeoip1');
l.add('libwrap0-dev');
l.add('gettext');
l.add('ruby');
l.add('rsync');
l.add('dnsutils');
if not LIGHT_INSTALL then if not FileExists('/usr/sbin/pdns_server') then l.add('dnsmasq');
l.add('curlftpfs');
L.add('davfs2');

l.add('bridge-utils');
l.add('mtools');
L.add('vlan');
l.add('acl');
l.add('iscsitarget');
l.add('quota');

//groupOffice:
//l.add('apache2-mpm-itk');

l.add('apache2');
l.add('apache2-mpm-prefork');
l.add('apache2-utils');
l.add('apache2.2-bin');
l.add('apache2.2-common');

l.add('apache2-prefork-dev');
l.add('libapache2-mod-php5');
l.add('libapache2-mod-evasive');
L.add('libapache2-mod-proxy-html');
L.add('libapache2-mod-jk');
L.add('libapache2-mod-geoip');
//L.add('libapache2-mod-fcgid');
l.Add('libapache2-mod-perl2');
l.Add('libapache2-authenntlm-perl');
l.Add('libapache2-mod-python');
//l.add('libapache2-mod-log-sql-mysql');
//l.Add('libapache2-mod-log-sql');
L.add('apache2-suexec');

   if distri.DISTRINAME_CODE='UBUNTU' then begin
      L.add('libapache2-mod-fastcgi');
      if UbuntuIntVer<12 then  l.add('libiodbc2-dev');
   end;
   if distri.DISTRINAME_CODE='DEBIAN' then begin
      if distri.DISTRI_MAJOR<7 then L.add('libapache2-mod-fastcgi');
      l.add('libiodbc2-dev');
   end;

//openLdap compilation
l.add('libslp-dev');
l.add('libperl-dev');
l.add('python-mysqldb');
L.add('python-crypto');
//msktuitl
l.Add('libldap2-dev');
l.Add('libkrb5-dev');
l.Add('libsasl2-dev');
l.Add('libsasl2-modules-gssapi-mit');
l.Add('krb5-clients');
l.Add('krb5-config');
l.Add('krb5-user');
l.Add('krb5-kdc');
l.Add('conntrack');
l.Add('krb5-admin-server');
l.add('libfuse-dev');
l.add('dsniff');
l.add('libqt4-xml'); // Kaspersky Upate Utility

if FileExists('/usr/bin/zarafa-server') then begin
   l.add('ant'); // WebAPP Zarafa
   l.add('openjdk-6-jdk');
end;





if distri.DISTRINAME_CODE='DEBIAN' then begin
      l.add('libjpeg62-dev');

      if distri.DISTRI_MAJOR<7 then begin
         L.Add('libcompress-zlib-perl');
         l.Add('libdigest-sha1-perl');
         l.Add('libgssapi2-heimdal');
      end;
      l.Add('discover');
      l.Add('jove');
      l.Add('rdate'); // sets the system's date from a remote host
      l.Add('rsh-client');
if not LIGHT_INSTALL then l.add('hddtemp');
      l.Add('tcsh');
      l.Add('console-common');
      l.Add('libmcrypt-dev');
      l.add('libconfuse-dev');
if not LIGHT_INSTALL then L.add('sysstat');
      l.Add('php5-imap');
      l.add('php-net-sieve');
      l.Add('php5-mcrypt');
      l.Add('php-log');
      l.Add('lighttpd');
      l.Add('rrdtool');
      l.Add('librrdp-perl');
      l.Add('libfile-tail-perl');
      l.Add('libgeo-ipfree-perl');
      l.Add('libgeo-ip-perl');
      l.Add('libnet-xwhois-perl');
      l.Add('libgssapi-perl');
      l.Add('libcrypt-openssl-rsa-perl');
      l.Add('libcrypt-openssl-bignum-perl');
      l.Add('libcrypt-openssl-random-perl');
      l.Add('libconfig-inifiles-perl');
      l.Add('libconvert-uulib-perl');
      l.Add('libtest-simple-perl');
      l.Add('libdigest-sha-perl');
      l.Add('libmail-dkim-perl');
      l.Add('libberkeleydb-perl');
      l.Add('libunix-syslog-perl');
      l.add('libsoap-lite-perl');
      l.add('libnet-ip-perl');
      l.Add('libinline-perl');
      l.add('libapache-dbi-perl');
      l.Add('libmailtools-perl');
      l.Add('libsasl2-modules-ldap');
     // l.add('hostapd');
     // l.add('hostap-utils');
      l.Add('arj');
      l.Add('htop');
      l.Add('dar');
      l.add('sshfs');
      l.Add('hdparm');
      l.add('libdb4.2-ruby1.8');
      l.add('libcairo-ruby1.8');
      l.add('libnet-server-perl');
      l.Add('libcrypt-ssleay-perl');
      l.Add('libconvert-tnef-perl');
      l.Add('libhtml-format-perl');
      l.Add('libfile-scan-perl');
      l.add('libtext-template-perl');
      l.add('libnet-dns-perl');
      l.add('libstring-random-perl');
      l.add('unrar-free');
      l.add('arj');
      l.Add('libgssapi-perl');
      l.add('libdotconf-dev');
      l.add('dar');
      l.add('monit');
      l.add('stunnel4');
      l.add('libwbxml2-utils');
      l.Add('libpam0g-dev');
      if distri.DISTRINAME_VERSION='6' then begin
         l.add('pike7.6-core');
         l.add('pike7.6-dev');
         l.add('pike7.6');
      end;
      l.add('libmagic-dev'); // MLDOnkey


   if distri.DISTRINAME_VERSION='4' then begin
      l.Add('sysutils');
      l.add('libcurl3-openssl-dev');
      l.add('libsnmp9-dev');
      l.add('apache');
      l.add('apache-common');
      l.add('libversion-perl');
      l.add('autofs');
      l.add('libmysqlclient15-dev');
      l.add('libdigest-md5-perl');
      l.add('autofs-ldap');
      l.add('libltdl3-dev');
      l.add('libreadline5-dev');
      if non_free then l.Add('lha');

   end;

   if distri.DISTRINAME_VERSION='5' then begin
   if  FileExists('/lib/modules/'+KERNEL_VERSION+'/kernel/fs/autofs4/autofs4.ko') then begin
      l.add('autofs');
      l.add('autofs-ldap');
   end else begin
       writeln('Checking.............: /lib/modules/'+KERNEL_VERSION+'/kernel/fs/autofs4/autofs4.ko no such file !');
       writeln('Checking.............: autofs and autofs-ldap will be skipped');
   end;
      l.add('libltdl3-dev');

      l.add('libtommath-dev'); //clamav
      l.add('memtester');
      l.add('procinfo');
      l.Add('libgeo-ip-perl');
      l.add('libcurl4-openssl-dev');
      l.add('php5-geoip');
      l.add('libsnmp-dev');
      l.add('perl-modules');
      l.add('libmysqlclient15-dev');
      l.add('php-apc');
      l.add('python-apt');
      l.add('update-notifier-common');
      l.add('libjs-mochikit');
      l.add('libtidy-0.99-0');
      l.add('par2');
      l.add('python-chardet');
      l.add('python-cheetah');
      l.add('python-configobj');
      l.add('python-feedparser');
      l.add('python-utidylib');
      l.add('python-yenc');
if not LIGHT_INSTALL then l.add('libboost-filesystem1.35.0');
if not LIGHT_INSTALL then l.add('libboost-system1.35.0');
if not LIGHT_INSTALL then l.add('libicu38');
      l.add('libreadline-gplv2-dev');


     if FileExists('/sbin/udevsettle')  then begin

        if not LIGHT_INSTALL then l.add('open-iscsi');
     end;

   end;





   if distri.DISTRINAME_VERSION='6' then begin
      if  FileExists('/lib/modules/'+KERNEL_VERSION+'/kernel/fs/autofs4/autofs4.ko') then begin
          l.add('autofs5');
          l.add('autofs5-ldap');
      end else begin
          writeln('Checking.............: /lib/modules/'+KERNEL_VERSION+'/kernel/fs/autofs4/autofs4.ko no such file !');
          writeln('Checking.............: autofs5 and autofs5-ldap will be skipped');
      end;
        l.add('libltdl-dev');
        l.add('libtommath-dev'); //clamav
        l.add('memtester');
        l.add('procinfo');
        l.Add('libgeo-ip-perl');
        l.add('libcurl4-openssl-dev');
        l.add('php5-geoip');
        l.add('libsnmp-dev');
        l.add('perl-modules');
        l.add('libmysqlclient-dev');
        l.add('php-apc');
        l.add('python-apt');
        l.add('update-notifier-common');
        l.add('libjs-mochikit');
        l.add('libtidy-0.99-0');
        l.add('par2');
        l.add('python-chardet');
        l.add('python-cheetah');
        l.add('python-configobj');
        l.add('python-feedparser');
        l.add('python-utidylib');
        l.add('python-yenc');
        l.add('libcap2');
        l.add('libcap-dev');
        l.add('iputils-ping');
        l.add('vim-tiny');
        l.add('libapache-mod-security');
        if not LIGHT_INSTALL then l.add('libboost-filesystem1.42.0');
        if not LIGHT_INSTALL then l.add('libboost-system1.42.0');
        l.add('libicu44');
        l.add('libreadline6-dev');
        l.add('libssh2-php');
        L.add('xtables-addons-common');
        L.add('xtables-addons-source');
        l.add('virt-what');
        l.add('suricata'); ///usr/bin/suricata
//        if not AsKimSuffi then if not isVPSDetected() then l.add('cgroup-bin');
        l.add('freeradius');
        l.add('freeradius-ldap');
        l.add('freeradius-mysql');
        l.add('freeradius-krb5');
        l.add('php-radius-legacy');

        if FileExists('/sbin/udevd')  then begin
           if not LIGHT_INSTALL then l.add('open-iscsi');
        end;




   end;


end;


if distri.DISTRINAME_CODE='UBUNTU' then begin
    writeln('Checking.............: Code UBUNTU ('+distri.DISTRINAME_VERSION+') MAJOR='+IntToStr(UbuntuIntVer));
    if(UbuntuIntVer=10) then begin
        non_free:=true;

    end;
    if UbuntuIntVer>10 then begin

    end;


   if UbuntuIntVer>7 then begin
      l.add('nginx');
      l.add('memtester');
      l.add('procinfo');
      l.add('autofs');
      l.Add('discover');
      l.Add('jove');
      l.Add('rdate'); // sets the system's date from a remote host
      l.Add('rsh-client');
      if not LIGHT_INSTALL then l.add('hddtemp');
      l.add('autofs-ldap');
      l.Add('tcsh');
      l.Add('console-common');
      l.Add('libmcrypt-dev');
      l.add('libconfuse-dev');
      l.add('libmagic-dev'); // MLDOnkey
      l.add('libltdl-dev'); //Clamav compilation
      L.add('sysstat');
      l.Add('php5-imap');
      l.add('php-net-sieve');
      l.Add('php5-mcrypt');
      l.Add('php-log');
      l.Add('lighttpd');
      l.Add('rrdtool');
      l.Add('librrdp-perl');
      l.Add('libfile-tail-perl');
      l.Add('libgeo-ipfree-perl');
      l.Add('libgeo-ip-perl');
      l.Add('libnet-xwhois-perl');
      l.Add('libgssapi-perl');
      l.Add('libcrypt-openssl-rsa-perl');
      l.Add('libcrypt-openssl-bignum-perl');
      l.Add('libcrypt-openssl-random-perl');
      l.Add('libconfig-inifiles-perl');
      l.Add('libconvert-uulib-perl');
      l.Add('libtest-simple-perl');
      l.Add('libdigest-sha-perl');
      l.Add('libmail-dkim-perl');
      l.Add('libberkeleydb-perl');
      l.Add('libunix-syslog-perl');
      l.add('libsoap-lite-perl');
      l.add('libnet-ip-perl');
      l.Add('libinline-perl');
      l.add('libapache-dbi-perl');
      l.Add('libmailtools-perl');
      l.Add('libsasl2-modules-ldap');
      l.add('hostapd');
      l.add('hostap-utils');
      l.Add('arj');
      l.Add('htop');
      l.Add('dar');
      l.add('sshfs');
      l.Add('hdparm');
      l.add('libdb4.2-ruby1.8');
      l.add('libcairo-ruby1.8');
      l.add('libnet-server-perl');
      l.Add('libcrypt-ssleay-perl');
      l.Add('libconvert-tnef-perl');
      l.Add('libhtml-format-perl');
      l.Add('libfile-scan-perl');
      l.add('libtext-template-perl');
      l.add('libnet-dns-perl');
      l.add('libstring-random-perl');
      l.add('unrar-free');
      l.add('arj');
      l.Add('libgssapi-perl');
      l.add('libdotconf-dev');
      l.add('dar');
      l.add('monit');
      l.add('stunnel4');
      l.add('libwbxml2-utils');
      l.add('libtidy-0.99-0');
      l.add('par2');
      l.add('python-chardet');
      l.add('python-cheetah');
      l.add('python-configobj');
      l.add('python-feedparser');
      l.add('python-utidylib');
      l.add('python-yenc');
      l.Add('lxc');
      l.Add('vlan');
      l.Add('bridge-utils');
      l.Add('python-software-properties');
      l.Add('screen');
      l.add('suricata');
      l.add('ettercap-common');
      l.add('libxslt-dev');



   end;

  l.add('libcurl4-openssl-dev');
  l.add('libsnmp-dev');



   if UbuntuIntVer=8 then begin
      l.add('libversion-perl');
      l.add('libmysqlclient15-dev');
      l.add('update-notifier-common');
      l.add('python-apt');
      l.add('python-pyopenssl');
      l.add('libicu38');
      l.add('open-iscsi');
      if not non_free then l.Add('lha');


   end;

   if UbuntuIntVer=9 then begin
      l.add('libmysqlclient15-dev');
      l.add('php5-geoip');
      l.add('libtommath-dev');
      l.add('perl-modules');
      l.add('php-apc');
      l.add('update-notifier-common');
      l.add('python-apt');
      l.add('python-pyopenssl');
      l.add('open-iscsi');
      if not non_free then l.Add('lha');
//      if not AsKimSuffi then if not isVPSDetected() then L.add('cgroup-bin');


   end;

   if UbuntuIntVer>8 then begin
       l.add('libjs-excanvas');
       l.add('libjs-mochikit');
       l.add('libapache-mod-security');
       l.add('libssh2-php');
       L.add('xtables-addons-source');
       l.add('virt-what');
       l.add('nginx');

    end;


   if UbuntuIntVer=10 then begin
      if not LIGHT_INSTALL then l.add('libboost-system1.40.0');
      if not LIGHT_INSTALL then l.add('libboost-system1.40.0');
      if not LIGHT_INSTALL then l.add('libicu42');
      if not LIGHT_INSTALL then l.add('open-iscsi');
      if not non_free then l.Add('lha');
//       if not AsKimSuffi then  if not isVPSDetected() then L.add('cgroup-bin');

   end;
   if UbuntuIntVer=11 then begin
        l.add('libboost-filesystem1.42.0');
        l.add('libboost-system1.42.0');
        L.add('libicu44');
//        if not AsKimSuffi then if not isVPSDetected() then L.add('cgroup-bin');
   end;

   if  UbuntuIntVer>9  then begin
       l.add('udisks');
       l.add('libmysqlclient-dev');
       l.add('php-apc');
       l.add('php5-geoip');
       l.add('libtommath-dev');
       l.add('perl-modules');
       l.add('update-notifier-common');
       l.add('python-apt');
       l.add('python-openssl');
       L.add('xtables-addons-common');
       l.add('libcrack2');
       if not LIGHT_INSTALL then L.add('avahi-daemon');
       l.add('libnss-mdns');
    end;

     if  UbuntuIntVer<12 then begin
         l.Add('libgssapi2-heimdal');
         L.Add('libcompress-zlib-perl');
         l.Add('libdigest-sha1-perl');
         l.add('libjpeg62-dev');
         l.add('pike7.6');
     end;

     if  UbuntuIntVer>11 then begin
       l.Add('libio-compress-perl');
       l.add('pike7.8');
       l.add('php5-fpm');
       l.add('ettercap-text-only');
     end;

end;



//OCS

l.Add('libxml-simple-perl');
l.Add('libdbi-perl');
l.add('libdbd-mysql-perl');
l.Add('libusb-dev');
l.Add('libusb-0.1-4');
l.Add('libcdio-dev');
l.add('libssl-dev');
l.Add('curl');
if not LIGHT_INSTALL then l.Add('lm-sensors');
l.Add('libsasl2-modules');
l.Add('libauthen-sasl-perl');
l.add('xutils-dev');
l.Add('bzip2');
l.add('unzip');
l.Add('telnet');
l.Add('lsof');


// Hamachi (  /usr/lib/lsb/install_initd )





fpsystem('/bin/rm -rf /tmp/packages.list');
if DEBUG then writeln('Verify ',l.Count,' packages...');
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;
//#########################################################################################
function tubuntu.CheckProxyAppliance():string;
var ppackages,u:string;
begin
     writeln('Please wait, preparing officials mandatories packages....');
     ppackages:=CheckProxyAppliance_debs();
     if length(ppackages)>0 then begin
        writeln('The setup will install the following packages:');
        writeln(ppackages);
        writeln('*** After installing these packages, the server will be automatically rebooted ***');
        writeln('Perform the operation: [yes]');
        readln(u);
        if length(u)=0 then u:='yes';
        if LowerCase(u)<>'yes' then exit;
        DisableApparmor();
        InstallPackageListssilent(ppackages);
     end;

     ppackages:=CheckProxyAppliance_debs();
     if length(ppackages)>0 then begin
        CheckProxyAppliance();
        exit;
     end;
     if not FileExists('/usr/share/artica-postfix/bin/artica-make') then libs.InstallArtica();
     if not FileExists('/usr/share/artica-postfix/bin/artica-make') then begin
        Writeln('Failed to install Artica...');
        halt(0);
     end;

     if not FileExists('/usr/bin/ufdbguardd') then fpsystem('/usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD');
     if not FileExists('/usr/sbin/squid') then fpsystem('/usr/share/artica-postfix/bin/artica-make APP_SQUID2');

     if not FileExists('/etc/artica-postfix/FROM_ISO') then begin
        fpsystem('/bin/touch /etc/artica-postfix/FROM_ISO');
        fpsystem('/bin/touch /etc/artica-postfix/artica-as-rebooted');
        fpsystem('/bin/touch /etc/artica-postfix/artica-iso-first-reboot');
        fpsystem('/usr/share/artica-postfix/bin/artica-iso');
        fpsystem('beep -f 500 -l 700');
        fpsystem('reboot');
     end;




end;

//#########################################################################################
function tubuntu.CheckProxyAppliance_debs():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin
l:=TstringList.Create;
if ArchStruct=64 then begin
   l.add('libc6-i386');
   //l.add('ia32-libs');
end;

l.Add('php5-cgi');
l.Add('php5-cli');
l.Add('php5-ldap');
l.Add('php5-mysql');
l.Add('php5-gd');
l.add('php5-curl');
l.add('php5-sqlite');
l.Add('php-pear');
l.add('librrd-dev');
l.add('php5-geoip');
l.Add('php5-mcrypt');
l.Add('php-log');
l.Add('slapd');
l.add('ldap-utils');
if Not FileExists('/usr/sbin/mysqld') then l.Add('mysql-server');
l.Add('libldap2-dev');
l.Add('libkrb5-dev');
l.Add('libsasl2-dev');
l.Add('libsasl2-modules-gssapi-mit');
l.Add('libroken18-heimdal');
l.Add('libasn1-8-heimdal');
l.Add('libheimntlm0-heimdal');
l.Add('libgd2-xpm-dev');
l.Add('krb5-clients');
l.Add('krb5-config');
l.Add('krb5-user');
l.Add('krb5-kdc');
l.Add('krb5-admin-server');
l.add('libfuse-dev');
l.add('libcap2');
l.add('libcap-dev');
l.add('iputils-ping');
l.add('virt-what');
l.add('udisks');
l.add('libmysqlclient-dev');
l.add('perl-modules');
l.add('update-notifier-common');
l.add('memtester');
l.add('procinfo');
l.Add('console-common');
l.Add('lighttpd');
l.Add('rrdtool');
l.Add('librrdp-perl');
l.add('sshfs');
l.add('monit');
l.add('htop');
l.add('libssl0.9.8');
l.add('libbz2-dev');
l.add('beep');



fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;


end;

function tubuntu.CheckOpenVPN():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin

f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
L.add('openvpn');
l.add('bridge-utils');
l.add('pptp-linux');
l.add('pptpd');
l.add('strongswan');

fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;

//#########################################################################################



function tubuntu.CheckFuppes():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin

f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;

   l.add('ffmpeg');
   l.add('libmp4v2-dev');
   l.add('build-essential');
   l.add('libavutil-dev');
   l.add('libavformat-dev');
   l.add('libavcodec-dev');
   l.add('libsqlite3-dev');
   l.add('libpcre3-dev');
   l.add('libxml2-dev');
   l.add('liblame-dev');
   l.add('libmpeg4ip-dev');
   l.add('libmpcdec-dev');  //   mpcdec/config_types.h
   l.add('libmyth-dev'); // ffmpeg/avstring.h
   L.add('libflac-dev');  // /FLAC/stream_decoder.h
   l.add('libfaad-dev'); //faad.h
   l.add('imagemagick');
   l.add('libtag1-dev');
   L.add('libmagickwand-dev'); // /usr/include/ImageMagick/wand/MagickWand.h
   l.add('libffmpegthumbnailer-dev'); // /usr/lib/libffmpegthumbnailer.a
   l.add('libavformat-extra-52');
   l.add('libsimage-dev');
   l.add('exiv2');
   l.add('build-essential');
   l.add('libpcre3-dev');
   l.add('libpcre++-dev');
   l.add('libpcre-ocaml');
   l.add('libpcre-ocaml-dev');
   l.add('libxml2-dev');
   l.add('sqlite3');
   l.add('libuuid-perl');
   l.add('libuuidm-ocaml-dev');
   l.add('libuuidm-ocaml-dev');
   l.add('libtaglib-ocaml-dev');
   l.add('libiconv-hook-dev');
   l.add('imagemagick');
   l.add('libavutil-dev');
   l.add('libavformat-dev');
   l.add('libavcodec-dev');
   l.add('libfaad-dev');
   l.add('libgsm1-dev');
   l.add('libogg-dev');
   l.add('libschroedinger-dev');
   l.add('libspeex-dev');
   l.add('libtheora-dev');
   l.add('libvorbis-dev');
   l.add('libx11-dev');
   l.add('libxext-dev');
   l.add('libraw1394-dev');
   l.add('libdc1394-22-dev');
   l.add('libmpeg4ip-dev');
   l.add('libmp3lame-dev');
   l.add('libtwolame-dev');
   l.add('libmpcdec-dev');
   l.add('libflac-dev');
   l.add('libmp4v2-dev');
   l.add('libmad0-dev');
   l.add('libmad-ocaml-dev');
   l.add('ffmpeg');
   l.add('libffmpegthumbnailer-dev');
   l.add('libsqlite3-dev');
   l.add('uuid-dev');
   l.add('libpanel-applet2-dev');
   l.add('libpanelappletmm-2.6-dev');
   l.add('libnotify-dev');
   l.add('libmagick++-dev');
   l.add('libtool');


fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;


end;
//#########################################################################################



function tubuntu.CheckZabbix():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin

f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;

if not FileExists('/etc/artica-postfix/KASPER_MAIL_APP') then begin
   l.add('zabbix-server-mysql');
   l.add('zabbix-frontend-php');
   l.add('zabbix-agent');
end;

fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;


end;
//#########################################################################################
function tubuntu.CheckLXC():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin
f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
 if UbuntuIntVer>8 then begin

end else begin
    writeln('No Supported need ubuntu >8');
end;


end;

function tubuntu.ejabberd():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   no_free:boolean;
begin
l:=Tstringlist.Create;
l.add('ejabberd');
l.add('pymsnt');
fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;

function tubuntu.CheckPostfix():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   no_free:boolean;
   lightinstall:boolean;
begin
f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
if FileExists('/etc/artica-postfix/LIGHT_INSTALL') then lightinstall:=true;

if distri.DISTRINAME_CODE='UBUNTU' then begin
   if UbuntuIntVer=10 then no_free:=true;
   writeln('Checking.............: Postfix: Code UBUNTU ('+distri.DISTRINAME_VERSION+') MAJOR='+IntToStr(UbuntuIntVer),' UBUNTU CODE:',UbuntuIntVer);
    if UbuntuIntVer<10 then begin
        if not no_free then l.add('libcap');
         l.add('libcap-dev');
         l.add('libmail-spf-query-perl');
    end;

    if UbuntuIntVer>9 then begin
        l.add('libmail-spf-perl');
        l.add('libcap2');
        l.add('libcap-dev');
    end;



    if UbuntuIntVer>7 then begin
         if not lightinstall then l.Add('razor');
         if not lightinstall then l.Add('pyzor');
         if not lightinstall then l.Add('sanitizer');
         if not lightinstall then if not FileExists('/usr/bin/spamd') then l.Add('spamassassin');
         l.add('libsys-syslog-perl');
         l.Add('libcrypt-ssleay-perl');
         l.Add('libgeo-ipfree-perl');
         l.Add('libconvert-tnef-perl');
         l.Add('libfile-scan-perl');
         l.add('libmail-imapclient-perl');
         L.add('libemail-mime-perl');
         l.add('libmail-srs-perl');
         l.add('libemail-valid-perl');
         l.add('libfile-readbackwards-perl');
         l.add('libemail-send-perl');
         if not FileExists('/usr/sbin/mailgraph') then begin
            l.Add('queuegraph');
            l.Add('mailgraph');
         end;

         l.Add('wv');
         l.add('libmilter-dev');
         l.add('pflogsumm');
         l.add('mailsync');;
         l.Add('mhonarc');
         l.Add('p7zip'); // 7zr
         l.Add('p7zip-full'); //7za
         l.Add('arc'); // arc
         l.Add('zoo');
         if not no_free then l.Add('lha');
         l.add('lzop');
         l.Add('tnef');
         l.add('libgsasl7-dev'); //gsasl.h
         l.add('libgsasl7');
         l.add('libnet-dns-perl');
         l.Add('cabextract');
         l.add('python-ldap');
    end;
    if UbuntuIntVer<12 then begin
       l.add('libemail-mime-modifier-perl');
    end;
    if UbuntuIntVer>=12 then begin
       l.add('libemail-mime-perl');
    end;
end;

if distri.DISTRINAME_CODE='DEBIAN' then begin
         if not lightinstall then l.Add('razor');
         if not lightinstall then l.Add('pyzor');
         if not lightinstall then l.Add('sanitizer');
         if not lightinstall then if not FileExists('/usr/bin/spamd') then l.Add('spamassassin');
         l.Add('libcrypt-ssleay-perl');
         l.Add('libgeo-ipfree-perl');
         l.Add('libconvert-tnef-perl');
         l.Add('libfile-scan-perl');

         l.add('libmail-imapclient-perl');
         L.add('libemail-mime-perl');
         l.add('libmail-srs-perl');
         if distri.DISTRI_MAJOR<7 then l.add('libemail-mime-modifier-perl');
         if distri.DISTRI_MAJOR>6 then l.add('libemail-mime-perl');
         l.add('libemail-valid-perl');
         l.add('libfile-readbackwards-perl');
         l.add('libemail-send-perl');
         if not FileExists('/usr/sbin/mailgraph') then begin
         l.Add('queuegraph');
         l.Add('mailgraph');
         end;
         l.Add('wv');
         l.add('libmilter-dev');
         l.add('pflogsumm');
         l.Add('mhonarc');
         l.Add('p7zip'); // 7zr
         l.Add('p7zip-full'); //7za
         l.Add('arc'); // arc
         l.Add('zoo');
         l.add('lzop');
         l.Add('tnef');
         l.add('libgsasl7-dev'); //gsasl.h
         l.add('libgsasl7');
         l.add('libnet-dns-perl');
         l.Add('cabextract');
         l.add('python-ldap');
         l.add('mailsync');



         if distri.DISTRINAME_VERSION='4' then begin
            l.add('libsys-syslog-perl');
            l.add('libmail-spf-query-perl');
            l.Add('libungif-bin');
            l.add('libcap2');
            l.add('libcap2-dev');
         end;
         if distri.DISTRINAME_VERSION='5' then begin
            l.add('libsys-syslog-perl');
            l.add('libmail-spf-query-perl');
            l.Add('libungif-bin');
            l.add('libcap2');
            l.add('libcap2-dev');
         end;
         if distri.DISTRINAME_VERSION='6' then begin
            l.add('giflib-tools');
         end;


end;

l.add('libsendmail-milter-perl');
l.Add('libio-socket-ssl-perl');
l.Add('libnet-ssleay-perl');
l.Add('libhtml-parser-perl');
l.Add('libarchive-zip-perl ');
l.add('libtext-wrapper-perl');
l.add('libmath-round-perl');
l.Add('ttf-dustin ');
l.Add('libgd-tools ');
if not lightinstall then l.Add('awstats');
if not FileExists('/usr/sbin/postfix') then begin
   l.add('postfix');
   l.Add('postfix-ldap');
   l.Add('postfix-pcre');
end;
if not lightinstall then l.Add('mailman');
if not lightinstall then l.Add('spamc');
if not lightinstall then l.Add('rpm');
if not lightinstall then l.add('tnef');
L.add('php5-pspell');
L.add('m4');

l.Add('netpbm');
if not lightinstall then l.Add('gifsicle');

if not lightinstall then l.Add('gocr');
if not lightinstall then l.Add('ocrad');
l.Add('libstring-approx-perl');
l.Add('libmldbm-sync-perl');
if not lightinstall then l.Add('imagemagick');
if not lightinstall then l.Add('tesseract-ocr');



//OCS
if not lightinstall then l.Add('libxml-simple-perl');
if not lightinstall then l.Add('libnet-ip-perl');
l.Add('libwww-perl');

if not lightinstall then l.Add('libnet-ssleay-perl');
l.add('ipmitool');
l.add('libnet-cups-perl');
l.add('libmodule-install-perl');



//Zarafa
if not lightinstall then begin
l.add('uuid-dev'); //zarafa
l.add('libnet-ldap-perl');
l.add('libio-socket-ssl-perl');
l.add('aspell');
l.add('aspell-de');
l.add('aspell-es');
l.add('aspell-fr');
l.add('aspell-ro');
l.add('aspell-it');
l.add('libauthen-simple-pam-perl');
L.add('liblog-log4perl-perl');
l.add('libsort-naturally-perl');
l.add('libdata-dump-perl');
l.add('libconfig-general-perl');
l.add('libalgorithm-dependency-perl');
l.add('libxml-libxml-perl');
l.add('libio-interface-perl');
l.add('libjson-perl');
l.add('libmime-lite-perl');
l.add('libio-socket-inet6-perl');
l.add('lsb-release');
end;


if distri.DISTRINAME_CODE='UBUNTU' then begin

   if UbuntuIntVer>=9 then begin
     l.add('libmail-spf-perl');

   end;
   if UbuntuIntVer>9 then l.add('opendkim');
end;




fpsystem('/bin/rm -rf /tmp/packages.list');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;
//#########################################################################################
function tubuntu.CheckPDNS():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri: tdistriDetect;
   UbuntuIntVer:integer;
begin
f:='';
l:=TstringList.Create;
//pdns
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
writeln('Checking Code '+distri.DISTRINAME);
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
//l.add('pdns-server');
//l.add('pdns-recursor');
//l.add('pdns-backend-ldap');
//l.add('pdns-backend-mysql');
l.add('libboost-dev'); //shared_ptr.hpp
l.add('libboost-serialization-dev');
l.add('g++');
l.add('libboost-program-options-dev');
l.add('libboost-serialization-dev');


l.add('libmysqlclient-dev');
l.add('libsqlite3-dev');
l.add('libpq-dev');
l.add('liblua5.1-0-dev');
l.add('liblualib50-dev');
l.add('php5-gd');
l.add('php-pear');
l.add('php5-imap');
l.add('php5-mcrypt');

l.add('php5-ming');
l.add('php5-xmlrpc');
l.add('gettext');
l.add('php-mdb2');
L.add('php-mdb2-driver-mysql');

 if distri.DISTRINAME_CODE='DEBIAN' then begin

 if distri.DISTRI_MAJOR<7 then begin
    l.add('libpqclient-dev');
    l.add('php5-mhash');
 end;
end;

if distri.DISTRINAME_CODE='UBUNTU' then begin
   if UbuntuIntVer<12  then begin
     l.add('libmail-spf-perl');
     l.add('libpqclient-dev');
   end;
end;


for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;

end;
//#########################################################################################
function tubuntu.CheckAmavisPerl():string;
var
   l:TstringList;
   f:string;
   i:integer;


begin
f:='';
l:=TstringList.Create;
l.Add('libcrypt-openssl-rsa-perl');
l.Add('libmail-dkim-perl');

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;

InstallPackageListssilent(f);
l.free;
 
end;
//#########################################################################################
function tubuntu.CheckCyrus():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin
f:='';
distri:=tdistriDetect.Create();
l:=TstringList.Create;
UbuntuIntVer:=9;
libs:=tlibs.Create;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;

if distri.DISTRINAME_CODE='UBUNTU' then begin
   writeln('Checking.............: Cyrus: Code UBUNTU ('+distri.DISTRINAME_VERSION+') MAJOR='+IntToStr(UbuntuIntVer));
   if UbuntuIntVer<8 then begin
        l.Add('cyrus21-imapd');
   end else begin
       l.Add('cyrus-imapd-2.2');
       l.Add('cyrus-admin-2.2');
       l.Add('sasl2-bin');
       l.Add('cyrus-pop3d-2.2');
       l.Add('cyrus-murder-2.2');
       l.add('libsnmp-dev');
       l.add('libopenafs-dev');
       l.add('cyrus-clients-2.2');

       l.Add('fdm');
       l.add('krb5-user');
       l.add('libpam-krb5');
   end;

   if UbuntuIntVer<12 then l.add('imapsync');


end;
if distri.DISTRINAME_CODE='DEBIAN' then begin
       l.Add('cyrus-imapd-2.2');
       l.Add('cyrus-admin-2.2');
       l.Add('sasl2-bin');
       l.Add('cyrus-pop3d-2.2');
       l.Add('cyrus-murder-2.2');
       l.add('libsnmp-dev');
       l.add('libopenafs-dev');
       l.add('cyrus-clients-2.2');
       if distri.DISTRINAME_VERSION='5' then l.add('imapsync');
       l.add('krb5-user');
       l.add('libpam-krb5');
end;

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;
//#########################################################################################
function tubuntu.krb5():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin
l.Add('krb5-clients');
l.Add('krb5-config');
l.Add('krb5-user');
L.add('libpam-modules');
l.add('libpam-krb5');
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;

function tubuntu.checkSamba():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
begin
f:='';
l:=TstringList.Create;
distri:=tdistriDetect.Create();
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;
l.Add('libpam-smbpass');

If not FileExists('/usr/sbin/smbd') then begin
   l.Add('samba');
   l.add('samba-common-bin');
   l.Add('smbldap-tools');
   l.Add('smbclient');
end;

if Not FileExists('/usr/sbin/winbindd') then l.add('winbind');
//l.Add('nscd');
l.Add('nmap');

l.add('attr');
l.add('catdoc'); //xapian
l.add('antiword'); //xapian
l.add('auditd');
l.Add('libldap2-dev');
l.Add('libkrb5-dev');
l.Add('libsasl2-dev');
l.Add('libsasl2-modules-gssapi-mit');
l.add('quota');
l.Add('krb5-clients');
l.Add('krb5-config');
l.Add('krb5-user');
l.add('libpam-krb5');
L.add('libpam-modules');

fpsystem('touch /etc/artica-postfix/samba.check.time');
if distri.DISTRINAME_CODE='UBUNTU' then begin
      if UbuntuIntVer>8 then begin
          l.add('libcups2-dev');
          l.add('cups-driver-gutenprint');
      end;
      if UbuntuIntVer<12 then begin
            l.add('smbfs');
            l.Add('dhcp3-server');
      end;

      if UbuntuIntVer>=12 then begin
           l.add('isc-dhcp-server');
      end;


end;

if distri.DISTRINAME_CODE='DEBIAN' then begin

   if distri.DISTRINAME_VERSION='4' then begin
      l.add('smbfs');
      l.Add('dhcp3-server');
      l.Add('libgssapi2-heimdal');
   end;


   if distri.DISTRINAME_VERSION='5' then begin
      l.add('smbfs');
      l.add('libcups2-dev');
      l.add('cups-driver-gutenprint');
      l.add('perl-sui');
      l.Add('dhcp3-server');
   end;

   if distri.DISTRINAME_VERSION='6' then begin
        l.add('smbfs');
        l.add('isc-dhcp-common');
        l.add('isc-dhcp-server');
   end;

   if distri.DISTRINAME_VERSION='7' then begin
       l.add('cifs-utils');
   end;

end;


l.add('netatalk');
l.add('libcrack2');
L.add('avahi-daemon');
l.add('libnss-mdns');
l.add('foomatic-db-gutenprint');
l.add('libgtk2.0-dev');

l.add('libpam0g-dev');
l.add('uuid-dev');
l.add('cryptsetup');
l.add('libattr1-dev');
l.add('libacl1-dev');
l.add('libtalloc-dev');
l.add('poppler-utils');
l.add('unrtf');
l.add('catdoc');
l.add('unzip');
l.add('antiword');

//backup-pc
l.add('libfile-rsyncp-perl');
l.add('libio-dirent-perl');

//l.add('backuppc');
l.add('par2'); //usr/sbin/par2

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;
//#########################################################################################
function tubuntu.CheckDevcollectd():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;

begin
f:='';
distri:=tdistriDetect.Create;
l:=TstringList.Create;
l.Add('iproute-dev');
l.Add('xfslibs-dev');
l.Add('librrd2-dev');
l.Add('libsensors-dev');
if distri.DISTRINAME_CODE='UBUNTU' then l.Add('libperl-dev') else l.Add('libperl5.8');
l.add('libesmtp-dev');
l.add('libnotify-dev');
l.add('libxml2-dev');
l.add('libpcap-dev');
l.add('hddtemp');
l.add('mbmon');
l.add('libconfig-general-perl');
L.add('libcurl4-openssl-dev');
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;


//#########################################################################################
function tubuntu.checkSQuid():string;
var
   l:TstringList;
   f:string;
   i:integer;
   UbuntuIntVer:integer;
   distri:tdistriDetect;
begin
distri:=tdistriDetect.Create();
UbuntuIntVer:=0;
if not TryStrToInt(distri.DISTRINAME_VERSION,UbuntuIntVer) then UbuntuIntVer:=9;



f:='';
l:=TstringList.Create;
if not FileExists('/usr/sbin/squid') then l.Add('squid3');
if not FileExists('/usr/bin/squidclient') then l.Add('squidclient');



if distri.DISTRINAME_CODE='UBUNTU' then begin
        if UbuntuIntVer=9 then l.add('libltdl-dev');  // lt_system.h
        if UbuntuIntVer>9 then l.add('libltdl-dev');  // lt_system.h
end;

for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;

//#########################################################################################
procedure tubuntu.InstallVirtualBox();
var
   cmd:string;
   distri:tdistriDetect;
begin
   distri:=tdistriDetect.Create();
   writeln('Checking sources repositories');
   if not VirtualBoxAptConf() then  begin
      writeln('Failed...');
      exit;
   end;
   cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy update';
   writeln(cmd);
   fpsystem(cmd);
   cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install virtualbox-3.2';
   writeln(cmd);
   fpsystem(cmd);

   writeln(cmd);
   fpsystem(cmd);
     writeln('------------------------------------------------------');
     writeln('Linux distribution is :',distri.DISTRINAME_CODE);
     writeln('------------------------------------------------------');

   cmd:='';
   if distri.DISTRINAME_CODE='UBUNTU' then begin
       cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install dkms';
       fpsystem(cmd);
       cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install atftpd';
       fpsystem(cmd);

   end else begin
       if distri.DISTRINAME_VERSION='4' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install dkms';
       if distri.DISTRINAME_VERSION='5' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install dkms';
       if distri.DISTRINAME_VERSION='6' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install dkms';
       if length(cmd)>0 then fpsystem(cmd);
       if distri.DISTRINAME_VERSION='4' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install atftpd';
       if distri.DISTRINAME_VERSION='5' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install atftpd';
       if distri.DISTRINAME_VERSION='6' then cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install atftpd';
       if length(cmd)>0 then fpsystem(cmd);

   end;

   cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy upgrade';
   writeln(cmd);
   fpsystem(cmd);
   fpsystem('/usr/share/artica-postfix/bin/artica-make APP_THINSTATION');


end;
procedure tubuntu.checkRepostitories6();
var
   RegExpr:TRegExpr;
   L:Tstringlist;
   x:string;
   found:boolean;
   i:integer;
begin
     if not FileExists('/etc/apt/sources.list') then begin
         Writeln('WARNING !!!  /etc/apt/sources.list no such file');
         Writeln('You should answer "yes" in the next question !');
         Writeln('Type Enter to continue...');
         readln(x);
         exit;
     end;
     found:=false;
     RegExpr:=TRegExpr.Create;
     RegExpr.Expression:='^deb.+?squeeze';
     l:=Tstringlist.Create;
     l.LoadFromFile('/etc/apt/sources.list');
     for i:=0 to l.Count -1 do begin
         if  RegExpr.Exec(l.Strings[i]) then found:=true;
     end;

     if not found then begin
        Writeln('WARNING !!!  /etc/apt/sources.list misconfigured.');
        writeln('It seems you running a debian squeeze on your system');
        writeln('But id did not find any repository about it...');
        writeln('Before continue, you should verify the /etc/apt/sources.list');
        writeln('Or answer to "yes" on the next question..');
        Writeln('Type Enter to continue...');
        readln(x);
        exit;
     end;
end;




function tubuntu.VirtualBoxAptConf():boolean;
var
   l:Tstringlist;
   RegExpr:TRegExpr;
   found:boolean;
   distriname:string;
   distri:tdistriDetect;
   i:integer;
begin
result:=false;
distri:=tdistriDetect.Create();
 RegExpr:=TRegExpr.Create;
 found:=false;
 RegExpr.Expression:='download\.virtualbox\.org';
 l:=Tstringlist.Create;
 l.LoadFromFile('/etc/apt/sources.list');
 for i:=0 to l.Count-1 do begin
      if RegExpr.Exec(l.Strings[i]) then begin
         found:=true;
         break;
      end;

 end;

 if found then begin
    l.free;
    RegExpr.free;
    result:=true;
    exit;
 end;
   if distri.DISTRINAME_CODE='UBUNTU' then distriname:=UbuntuName();

   if distri.DISTRINAME_CODE='DEBIAN' then begin
      if distri.DISTRINAME_VERSION='5' then distriname:='lenny';
      if distri.DISTRINAME_VERSION='4' then distriname:='etch';
      if distri.DISTRINAME_VERSION='6' then distriname:='squeeze';
   end;

   if length(distriname)>0 then begin
      writeln('Adding VirtualBox repository for ',distriname);
      l.add('');
      l.add('#Repository for VirtualBox');
      l.add('deb http://download.virtualbox.org/virtualbox/debian '+distriname+' non-free');
      l.SaveToFile('/etc/apt/sources.list');
      writeln('Importing the public key for apt ',distriname);
      fpsystem('wget -q http://download.virtualbox.org/virtualbox/debian/sun_vbox.asc -O- | sudo apt-key add -');
      l.free;
      RegExpr.free;
      result:=true;
     exit;

   end;
end;

//########################################################################################
procedure tubuntu.S00vzreboot();
var
   l:Tstringlist;
begin
l:=Tstringlist.Create;
l.Add('#!/bin/bash');
l.add('./reboot');
l.SaveToFile('/etc/rc6.d/S00vzreboot');
l.free;
writeln('Patching /etc/rc6.d/S00vzreboot done...');
end;

function tubuntu.checkApps(l:tstringlist):string;
var
   f:string;
   i:integer;

begin
f:='';
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;
//########################################################################################
function tubuntu.UbuntuName():string;
var
   FileTMP:TstringList;
   RegExpr:TRegExpr;
   distri_provider,distri_ver,distri_name:string;
   i:integer;
begin
      RegExpr:=TRegExpr.Create;
             fpsystem('/bin/cp /etc/lsb-release /tmp/lsb-release');
             FileTMP:=TstringList.Create;
             FileTMP.LoadFromFile('/tmp/lsb-release');
             for i:=0 to  FileTMP.Count-1 do begin
                 RegExpr.Expression:='DISTRIB_ID=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_provider:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_RELEASE=([0-9\.]+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_ver:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_CODENAME=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_name:=trim(RegExpr.Match[1]);
             end;

             result:=trim(lowercase(distri_name));
             RegExpr.Free;
             FileTMP.Free;

end;
//########################################################################################
function tubuntu.Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
var
  SepLen       : Integer;
  F, P         : PChar;
  ALen, Index  : Integer;
begin
  SetLength(Result, 0);
  if (S = '') or (Limit < 0) then
    Exit;
  if Separator = '' then
  begin
    SetLength(Result, 1);
    Result[0] := S;
    Exit;
  end;
  SepLen := Length(Separator);
  ALen := Limit;
  SetLength(Result, ALen);

  Index := 0;
  P := PChar(S);
  while P^ <> #0 do
  begin
    F := P;
    P := StrPos(P, PChar(Separator));
    if (P = nil) or ((Limit > 0) and (Index = Limit - 1)) then
      P := StrEnd(F);
    if Index >= ALen then
    begin
      Inc(ALen, 5); // mehrere auf einmal um schneller arbeiten zu können
      SetLength(Result, ALen);
    end;
    SetString(Result[Index], F, P - F);
    Inc(Index);
    if P^ <> #0 then
      Inc(P, SepLen);
  end;
  if Index < ALen then
    SetLength(Result, Index); // wirkliche Länge festlegen
end;
//#########################################################################################

function tubuntu.isVPSDetected():boolean;
begin
result:=false;
if FIleExists('/etc/rc6.d/S00vzreboot') then exit(true);
if FIleExists('/etc/init.d/vzquota') then exit(true);
if FIleExists('/proc/vz/vestat') then exit(true);
end;
//#########################################################################################

end.
