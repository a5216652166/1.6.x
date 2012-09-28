unit logon;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,md5,logs,unix,RegExpr in 'RegExpr.pas',zsystem,tcpip,openldap;



  type
  tlogon=class


private
     LOGS:Tlogs;
     D:boolean;
     GLOBAL_INI:TiniFIle;
     SYS:TSystem;
     artica_path:string;                      
     ldap:Topenldap;
     function ParseResolvConf():string;
public
    procedure   Free;
    constructor Create();
    procedure Menu();

    procedure ChangeIP();
    procedure ChangeRootpwd();
    procedure RegisterAgent();


END;

implementation

constructor tlogon.Create();
begin
       forcedirectories('/etc/artica-postfix');
       SYS:=Tsystem.Create;
       LOGS:=tlogs.Create();
       D:=LOGS.COMMANDLINE_PARAMETERS('debug');




       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tlogon.free();
begin
    logs.Free;
end;
//##############################################################################
procedure tlogon.Menu();
var
   a:string;
   answerA:string;
   lightstatus:string;
   port,uris,nohup,php5:string;
   SYSURIS:Tsystem;
   ArticaAgent:boolean;
   MASTER_CONSOLE:string;
   NODE_ID:string;
   ISOCanDisplayUserNamePassword,ISOCanChangeIP,ISOCanReboot,ISOCanShutDown,ISOCanChangeRootPWD,ISOCanChangeLanguage:integer;
begin
ArticaAgent:=false;
logs.Debuglogs('Initialize menu....');
SYS:=Tsystem.Create;
if not TryStrToInt(SYS.GET_INFO('ISOCanDisplayUserNamePassword'),ISOCanDisplayUserNamePassword) then ISOCanDisplayUserNamePassword:=1;
if not TryStrToInt(SYS.GET_INFO('ISOCanChangeIP'),ISOCanChangeIP) then ISOCanChangeIP:=1;
if not TryStrToInt(SYS.GET_INFO('ISOCanReboot'),ISOCanReboot) then ISOCanReboot:=1;
if not TryStrToInt(SYS.GET_INFO('ISOCanShutDown'),ISOCanShutDown) then ISOCanShutDown:=1;
if not TryStrToInt(SYS.GET_INFO('ISOCanChangeRootPWD'),ISOCanChangeRootPWD) then ISOCanChangeRootPWD:=1;
if not TryStrToInt(SYS.GET_INFO('ISOCanChangeLanguage'),ISOCanChangeLanguage) then ISOCanChangeLanguage:=1;
logs.Debuglogs('Initialize menu done....');
fpsystem('clear');
nohup:=SYS.LOCATE_GENERIC_BIN('nohup');
php5:=SYS.LOCATE_PHP5_BIN();
if FileExists('/opt/artica-agent/usr/share/artica-agent/artica-agent.php') then ArticaAgent:=true;


if FileExists('/opt/urlfilterbox/urlfilterbox-agent.php') then fpsystem(nohup+' '+php5+' /opt/urlfilterbox/urlfilterbox-agent.php >/dev/null 2>&1');
if ArticaAgent then  ISOCanDisplayUserNamePassword:=0;

   writeln('##########################################');
   writeln('###                                    ###');
   writeln('             URL Filter Box        ');
   writeln('###                                    ###');
   writeln('##########################################');
   writeln('');
   writeln('To configure your proxy, please log on the');
   writeln('Web console: http://www.urlfilterbox.com/');
   writeln('');
   writeln('In order to use the URL Filter Box,');
   writeln('setup your browsers to this/these address(es):'+SYS.txt_ip_agents_line_port('8181'));
   writeln('We strongly advice you to customize the IP ');
   writeln('configuration by using the option [M]');
   writeln('');


writeln('Menu :');
writeln('[M]..... Modify eth0 interface');
if FileExists('/bin/setupcon') then begin
   writeln('[K]..... Keyboard setup');
end;
writeln('[L]..... Configure languages');
writeln('[R]..... Reboot');
writeln('[S]..... Shutdown');
writeln('[T]..... Realtime Logs');
writeln('Your command: ');
readln(a);

a:=UpperCase(a);

if a='L' then begin
   if ISOCanChangeLanguage=0 then begin
      Writeln('Operation not permitted');
      Menu();
      exit;
   end;
   fpsystem('/usr/sbin/dpkg-reconfigure locales');
   Menu();
   exit;
end;

if a='T' then begin
   fpsystem('php /opt/urlfilterbox/urlfilterbox-realtime.php');
   Menu();
   exit;
end;

if a='M' then begin
   if ISOCanReboot=0 then begin
      Writeln('Operation not permitted');
      Menu();
      exit;
   end;
   ChangeIP();
   Menu();
   exit;
end;


if a='R' then begin
   if ISOCanChangeIP=0 then begin
      Writeln('Operation not permitted');
      Menu();
      exit;
   end;

  fpsystem(sys.LOCATE_GENERIC_BIN('reboot'));
  exit;
end;



if a='S' then begin
   if ISOCanShutDown=0 then begin
      Writeln('Operation not permitted');
      Menu();
      exit;
   end;
   fpsystem('init 0');
   exit;
end;

if a='L' then begin
   fpsystem('sudo /usr/sbin/dpkg-reconfigure locales');
   fpsystem('sudo dpkg-reconfigure console-data');
   Menu();
   exit;
end;



if a='K' then begin
   fpsystem('/usr/sbin/dpkg-reconfigure console-setup');
   fpsystem('/bin/setupcon');
   fpsystem('/usr/sbin/dpkg-reconfigure keyboard-configuration');
   writeln('You need to reboot in order to apply changes');
   writeln('Do you want to reboot ? [Y/N]:');
   readln(answerA);
   answerA:=UpperCase(trim(answerA));
   if answerA='Y' then fpsystem('reboot');
end;

 Menu();

end;



procedure tlogon.ChangeRootpwd();
var
   pass1,pass2:string;
begin



   fpsystem('clear');
   writeln('Change the root password');
   writeln('**********************************************');
   writeln('Give the password:');
   readln(pass1);
   if length(pass1)=0 then begin
      writeln('Null root password is not allowed, exiting..');
      writeln('[Enter] key to Exit');
      readln(pass1);
      exit;
   end;

    writeln('retype the password:');
    readln(pass2);

    if pass1<>pass2 then begin
         writeln('Passwords did not match');
         writeln('Press [ENTER] to restart');
         readln(pass2);
         ChangeRootpwd();
         exit;
    end;

    fpsystem('echo "root:'+pass2+'" | chpasswd 2>&1');
    writeln('Updated..');
    writeln('[Enter] key to Exit');
    readln(pass2);
    exit;

end;
//#############################################################################

procedure tlogon.RegisterAgent();
var
   ipaddr,ipaddr2:string;
   NetagentPort:integer;
   RegExpr:TRegExpr;
begin
   ipaddr:=SYS.GET_INFO('ARTICA_MASTER');
   if not TryStrToInt(SYS.GET_INFO('NetagentPort'),NetagentPort) then NetagentPort:=9001;

   fpsystem('clear');
   writeln('Set here the URI to the Artica master');
   writeln('This uri must point to the Artica master Web administration interface');
   writeln('Example: https://10.10.10.2:9000');
   writeln('Example: http://10.10.10.2:9000');
   writeln('**********************************************');
   writeln('Give the uri:'+ipaddr);

   readln(ipaddr2);
   ipaddr2:=trim(ipaddr2);
   RegExpr:=TRegExpr.Create;


   if(length(ipaddr2)>0) then begin
       RegExpr.Expression:='htt.*?:\/\/.+?:[0-9]+';
       if not RegExpr.Exec(ipaddr2) then begin
             writeln('"',ipaddr2,'" is not an uri like http(s)://something:somenumber');
             writeln('type [ENTER] key to exit.');
             readln(ipaddr2);
             exit;
       end;
   end;
       SYS.set_INFO('ARTICA_MASTER',ipaddr2);

   writeln('Give the local listen port of this node:'+IntToStr(NetagentPort));
   readln(ipaddr2);
   if(length(ipaddr2)>0) then SYS.set_INFO('NetagentPort',ipaddr2);


   fpsystem('/opt/artica-agent/usr/share/artica-agent/artica-agent.php --register');
   fpsystem('/opt/artica-agent/usr/share/artica-agent/artica-agent.php --restart');
   writeln('type [ENTER] key to exit.');
   readln(ipaddr2);
   exit;

end;


procedure tlogon.ChangeIP();
var
   IP:string;
   Gateway:string;
   DNS,answer:string;
   NETMASK:string;
   iptcp:ttcpip;
   Gayteway:string;
   perform:string;
   l:Tstringlist;
   RegExpr:TRegExpr;
   AutorizePerform:boolean;
   s:TstringList;
begin
    AutorizePerform:=false;
    iptcp:=ttcpip.Create;
    IP:=iptcp.IP_ADDRESS_INTERFACE('eth0');
    NETMASK:=iptcp.IP_MASK_INTERFACE('eth0');
    Gayteway:=iptcp.IP_LOCAL_GATEWAY('eth0');
    perform:='o';

    if(IP='0.0.0.0') then IP:='172.16.14.135';
    if(Gayteway='0.0.0.0') then Gayteway:='172.16.14.2';
    if length(NETMASK)=0 then NETMASK:='255.255.255.0';
    if length(Gayteway)=0 then Gayteway:='172.16.14.2';
    DNS:=ParseResolvConf();
    fpsystem('clear');
    writeln('Network configurator v1.3');
    writeln('By default, the URL Filter Box server is set in DHCP Mode');
    writeln('You will change eth0 network settings using static mode');
    writeln('');
    writeln('Give the "eth0" network address IP of this computer: ['+IP+']');
    readln(answer);
    if length(trim(answer))>3 then begin
       IP:=trim(answer);

    end else begin
       if length(IP)=0 then begin
        ChangeIP();
        exit;
        end;
    end;


    writeln('Give the netmask of this computer:['+NETMASK+']');
    readln(answer);
    if length(trim(answer))>0 then NETMASK:=answer else answer:=NETMASK;


    writeln('Give the gateway ip address for this computer:['+Gayteway+']');
    readln(answer);
    if length(trim(answer))>0 then Gayteway:=answer;


    writeln('Give the First DNS ip address for this computer:['+Gayteway+']');
    readln(answer);
    if length(trim(answer))>0 then DNS:=answer else DNS:=Gayteway;

    RegExpr:=TRegExpr.Create;
    AutorizePerform:=true;


    RegExpr.Expression:='^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$';
    if not RegExpr.Exec(IP) then begin
       writeln('IP -> FALSE:',IP);
       AutorizePerform:=false;
    end;
    if not RegExpr.Exec(Gayteway) then begin
       writeln('Gayteway -> FALSE:',Gayteway);
       AutorizePerform:=false;
    end;
    if not RegExpr.Exec(NETMASK) then begin
       writeln('NETMASK -> FALSE:',NETMASK);
       AutorizePerform:=false;
    end;

    if not AutorizePerform then begin
        writeln('[Enter] key to Exit');
        readln(answer);
        exit;
    end;

    s:=Tstringlist.Create;
    if length(DNS)>0 then  s.Add('nameserver '+DNS);
    s.Add('nameserver 156.154.70.1');
    s.Add('nameserver 8.8.4.4');

    writeln('Are you OK with these details (Y/N) :');
    writeln('IP address....: "'+IP+'"');
    writeln('Gayteway......: "'+Gayteway+'"');
    writeln('Netmask.......: "'+NETMASK+'"');
    writeln('DNS...........: "'+DNS+'"');
    writeln('');
    readln(answer);
    if length(trim(answer))>0 then perform:=UpperCase(answer) else perform:='Y';

    if perform<>'Y' then begin
        writeln('Choose "'+perform+'"');
        writeln('Operation aborted...[Enter] key to Exit');
        readln(answer);
        exit;
    end;



l:=Tstringlist.Create;
l.add('auto lo');
l.add('iface lo inet loopback');
l.add('auto eth0');
l.add('iface eth0 inet static');
l.add(chr(9)+'address '+IP);
l.add(chr(9)+'gateway '+Gayteway);
l.add(chr(9)+'netmask '+netmask);
if length(DNS)>0 then l.add(chr(9)+'dns-nameservers '+DNS);
writeln('Saving /etc/network/interfaces');
l.SaveToFile('/etc/network/interfaces');
writeln('Shutdown eth0...');
fpsystem('ifdown eth0  >/dev/null 2>&1');
writeln('Starting eth0 with new parameters...');
fpsystem('ifconfig eth0 '+IP+' netmask '+NETMASK +' up >/dev/null 2>&1');
fpsystem('ip route add default via '+Gayteway+' dev eth0  proto static  >/dev/null 2>&1');
writeln('Restarting services, please wait....');
if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart apache >/dev/null 2>&1');
if FileExists('/etc/init.d/artica-agent') then fpsystem('/etc/init.d/artica-agent restart >/dev/null 2>&1');

writeln('**** The system will reboot ****');
writeln('[Enter] key to reboot');
readln(answer);
fpsystem('reboot');
exit;


end;

function tlogon.ParseResolvConf():string;
var
   IP:string;
   Gateway:string;
   DNS,answer:string;
   NETMASK:string;
   iptcp:ttcpip;
   Gayteway:string;
   perform:string;
   l:Tstringlist;
   RegExpr:TRegExpr;
   AutorizePerform:boolean;
   s:TstringList;
   i:integer;
begin
     l:=Tstringlist.Create;
     l.LoadFromFile('/etc/resolv.conf');
     RegExpr:=TRegExpr.Create;
     RegExpr.Expression:='^nameserver\s+(.*)';
     for i:=0 to l.Count-1 do begin
         if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            RegExpr.free;
            l.free;
         end;
     end;
end;







end.
