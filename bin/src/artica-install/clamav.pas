unit clamav;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,
    postfix_class   in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/postfix_class.pas';

type LDAP=record
      admin:string;
      password:string;
      suffix:string;
      servername:string;
      Port:string;
  end;

  type
  TClamav=class


private
     LOGS:Tlogs;
     SYS:Tsystem;
     artica_path:string;
     postfix:tpostfix;
     FreshClam_pidpath:string;
     Clamd_PidPath:string;
     ClamavMilterEnabled:integer;
     EnableAmavisDaemon:integer;
     EnableClamavDaemon:integer;
     EnableFreshClam:integer;
     EnableClamavDaemonForced:integer;
     CLAMD_BIN_PATH_MEM:string;
     function COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;

     function ReadFileIntoString(path:string):string;
     procedure SENDMAIL_CF_AND_MILTER();
     procedure WRITE_CLAMAV_MILTER();

public
    DEBUG:Boolean;
    NotEngoughMemory:boolean;
    procedure   Free;
    constructor Create;
    function  MILTER_PID():string;
    function  MILTER_DAEMON_PATH():string;
    function  MILTER_INITD_PATH():string;
    procedure MILTER_ETC_DEFAULT();
    procedure MILTER_START();
    procedure MILTER_STOP();
    function  MILTER_SOCK_PATH():string;
    function  MILTER_GET_SOCK_PATH():string;
    procedure MILTER_CHANGE_INITD();

    
    
    function  CLAMAV_VERSION():string;
    function  CLAMD_PID() :string;
    function  CLAMAV_PATTERN_VERSION():string;
    function  CLAMD_BIN_PATH():string;
    function  CLAMD_CONF_PATH():string;
    function  CLAMD_GETINFO(Key:String):string;







    function  CLAMAV_INITD():string;
    FUNCTION  CLAMAV_STATUS():string;
    function  CLAMSCAN_BIN_PATH():string;
    function  CLAMAV_CONFIG_BIN_PATH():string;
    function  StartStopDaemonPath():string;
    
    function  PATTERNS_VERSIONS():string;
    function  CLAMAV_BINVERSION():integer;
    function  AMAVISD_PID() :string;
    function  clamd_LocalSocket():string;




END;

implementation

constructor TClamav.Create;
begin
       DEBUG:=false;
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=Tsystem.Create();
       ClamavMilterEnabled:=0;
       EnableFreshClam:=1;
       NotEngoughMemory:=false;
       if ParamStr(1)='--clamav-status' then DEBUG:=True;
       SYS:=Tsystem.Create;
       postfix:=tpostfix.Create(SYS);
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;

      if not TryStrToInt(SYS.GET_INFO('EnableAmavisDaemon'),EnableAmavisDaemon) then EnableAmavisDaemon:=0;
      if not TryStrToInt(SYS.GET_INFO('ClamavMilterEnabled'),ClamavMilterEnabled) then ClamavMilterEnabled:=0;
      if not TryStrToInt(SYS.GET_INFO('EnableClamavDaemon'),EnableClamavDaemon) then EnableClamavDaemon:=0;
      if not TryStrToInt(SYS.GET_INFO('EnableClamavDaemonForced'),EnableClamavDaemonForced) then EnableClamavDaemonForced:=0;

end;
//##############################################################################
procedure TClamav.free();
begin
    logs.Free;
end;
//##############################################################################
function TClamav.MILTER_DAEMON_PATH():string;
begin
    result:=SYS.LOCATE_GENERIC_BIN('clamav-milter');
end;
//#############################################################################
function TClamav.StartStopDaemonPath():string;
begin
if FileExists('/sbin/start-stop-daemon') then exit('/sbin/start-stop-daemon');
end;
//#############################################################################
function TClamav.clamd_LocalSocket():string;
begin
result:=CLAMD_GETINFO('LocalSocket');
end;
//#############################################################################


function TClamav.MILTER_PID():string;
var pid:string;
begin
    if FileExists('/var/spool/postfix/var/run/clamav/clamav-milter.pid') then begin
       pid:=trim(SYS.GET_PID_FROM_PATH('/var/spool/postfix/var/run/clamav/clamav-milter.pid'));
       //logs.Debuglogs('TClamav.MILTER_PID():: pid='+pid);
       if pid='0' then pid:='';
       if not FileExists('/proc/'+pid+'/exe') then pid:='';
    end;
    if length(pid)=0 then pid:=SYS.PidByProcessPath(MILTER_DAEMON_PATH());
    result:=pid;
end;
//#############################################################################
function TClamav.AMAVISD_PID() :string;
begin
 if FileExists('/var/run/amavisd/amavis-artica.pid') then exit(SYS.GET_PID_FROM_PATH('/var/run/amavisd/amavis-artica.pid'));
 if FileExists('/var/run/amavis/amavisd.pid') then exit(SYS.GET_PID_FROM_PATH('/var/run/amavis/amavisd.pid'));
end;
//##############################################################################
function TClamav.CLAMD_PID() :string;
begin
if length(Clamd_PidPath)=0 then Clamd_PidPath:=CLAMD_GETINFO('PidFile');
if DEBUG then writeln('Clamd_PidPath=',Clamd_PidPath);
result:=SYS.GET_PID_FROM_PATH(Clamd_PidPath);
if result='0' then result:='';
if length(result)=0 then result:=SYS.PIDOF(CLAMD_BIN_PATH());


//logs.Debuglogs('TClamav.CLAMD_PID():: Clamd_PidPath='+Clamd_PidPath+' "' + result + '"');
end;
//##############################################################################
function TClamav.MILTER_INITD_PATH():string;
begin
    if FileExists('/etc/init.d/clamav-milter') then exit('/etc/init.d/clamav-milter');
end;
//#############################################################################
function TClamav.MILTER_SOCK_PATH():string;
var path:string;
begin
    path:=MILTER_GET_SOCK_PATH();
    if length(path)>0 then exit(path);
    if FileExists('/var/spool/postfix/var/run/clamav/clamav-milter.ctl') then exit('/var/spool/postfix/var/run/clamav/clamav-milter.ctl');
end;
//#############################################################################
function TClamav.MILTER_GET_SOCK_PATH():string;
begin
exit('/var/spool/postfix/var/run/clamav/clamav-milter.ctl');
end;
//#############################################################################
function Tclamav.CLAMSCAN_BIN_PATH():string;
begin
if FileExists('/usr/bin/clamscan') then exit('/usr/bin/clamscan');
end;
//#############################################################################
function Tclamav.CLAMAV_CONFIG_BIN_PATH():string;
begin
    if FileExists('/usr/bin/clamav-config') then exit('/usr/bin/clamav-config');
end;
//#############################################################################
function TClamav.CLAMD_BIN_PATH():string;
begin
if length(CLAMD_BIN_PATH_MEM)>0 then exit(CLAMD_BIN_PATH_MEM);

CLAMD_BIN_PATH_MEM:=SYS.LOCATE_GENERIC_BIN('clamd');
if length(CLAMD_BIN_PATH_MEM)>0 then begin
   exit(CLAMD_BIN_PATH_MEM);
end;

if FileExists('/opt/artica/sbin/clamd') then begin
   CLAMD_BIN_PATH_MEM:='/opt/artica/sbin/clamd';
   exit('/opt/artica/sbin/clamd');
end;

end;
//#############################################################################
function TClamav.CLAMAV_INITD():string;
begin
if FileExists('/etc/init.d/clamav-daemon') then exit('/etc/init.d/clamav-daemon');
if FileExists('/etc/init.d/clamd') then exit('/etc/init.d/clamd');
end;
//#############################################################################
function TClamav.CLAMD_CONF_PATH():string;
begin
   if FileExists('/etc/clamav/clamd.conf') then exit('/etc/clamav/clamd.conf');
   if FIleExists('/etc/clamd.conf') then exit('/etc/clamd.conf');
   if FileExists('/usr/local/etc/clamav/clamd.conf') then exit('/usr/local/etc/clamav/clamd.conf');
   if FIleExists('/usr/local/etc/clamd.conf') then exit('/usr/local/etc/clamd.conf');
   if FileExists('/opt/artica/etc/clamd.conf') then exit('/opt/artica/etc/clamd.conf');
end;
//##############################################################################

function Tclamav.PATTERNS_VERSIONS():string;
var
 DatabaseDirectory:string;
 i:integer;
 FilePath:string;
 l:TstringList;

begin
  if not FileExists(CLAMSCAN_BIN_PATH()) then begin
     logs.Debuglogs('Tclamav.PATTERNS_VERSIONS():: unable to stat clamscan');
     exit;
  end;


  DatabaseDirectory:='/var/lib/clamav';
  if not DirectoryExists(DatabaseDirectory) then begin
          logs.Debuglogs('Tclamav.PATTERNS_VERSIONS():: unable to stat DatabaseDirectory ('+DatabaseDirectory+')');
          exit;
  end;

  l:=TstringList.Create;
  l.Add('[CLAMAV]');
  SYS.DirFiles(DatabaseDirectory,'*.*');
  for i:=0 to SYS.DirListFiles.Count-1 do begin
        FilePath:=DatabaseDirectory+'/'+SYS.DirListFiles.Strings[i];
        l.Add(SYS.DirListFiles.Strings[i]+'='+SYS.FILE_TIME(FilePath));
  end;

  result:=l.Text;
  l.free;

end;


//##############################################################################
FUNCTION TClamav.CLAMAV_STATUS():string;
var
   pidpath:string;
begin
if FileExists('/etc/artica-postfix/KASPER_MAIL_APP') then exit;
pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --clamav >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);

end;


procedure TClamav.MILTER_ETC_DEFAULT();
var
   l:TstringList;
begin
    if not FileExists('/etc/default/clamav-milter') then exit();
l:=TstringList.Create;
l.Add('OPTIONS="--max-children=2 -ol"');
l.Add('#If you want to set an alternate pidfile (why?) please do it here:');
l.Add('#PIDFILE=/var/run/clamav/clamav-milter.pid');
l.Add('#If you want to set an alternate socket, do so here (remember to change ');
l.Add('#  sendmail.mc):');
l.Add('#SOCKET=local:/var/run/clamav/clamav-milter.ctl');
l.Add('#');
l.Add('#For postfix, you might want these settings:');
l.Add('USE_POSTFIX=''yes''');
l.Add('SOCKET=local:/var/spool/postfix/var/run/clamav/clamav-milter.ctl');
l.SaveToFile('/etc/default/clamav-milter');
l.free;
end;
//#############################################################################
procedure TClamav.MILTER_START();
var
   daemon_path:string;
   cmd:string;
   ClamavMilterEnabled:integer;
   pid:string;
   count:integer;
   LogFile:string;
   DatabaseDirectory:string;
   UpdateLogFile:string;
begin
   daemon_path:=MILTER_DAEMON_PATH();
   pid:='';
   count:=0;
   if not FileExists(daemon_path) then exit;
   
   if FileExists('/etc/init.d/clamav-milter') then begin
      fpsystem('/bin/rm /etc/init.d/clamav-milter');
      SYS.RemoveService('clamav-milter');
   end;

   if not TryStrToInt(SYS.GET_INFO('ClamavMilterEnabled'),ClamavMilterEnabled) then ClamavMilterEnabled:=0;
   
   if ClamavMilterEnabled=0 then begin
        logs.DebugLogs('Starting......: Clamav-milter daemon is disabled');
        exit;
   end;
   
   if SYS.PROCESS_EXIST(MILTER_PID()) then begin
        logs.DebugLogs('clamav-milter daemon is already running using PID ' + MILTER_PID() + '...');
        exit;
   end;
   
   
   if FileExists(MILTER_INITD_PATH()) then SYS.RemoveService('clamav-milter');
   
   MILTER_ETC_DEFAULT();
   MILTER_CHANGE_INITD();
   SENDMAIL_CF_AND_MILTER();
   WRITE_CLAMAV_MILTER();
   
   if not SYS.IsUserExists('clamav') then begin
      SYS.AddUserToGroup('clamav','clamav','','');
   end;

   
   forcedirectories('/var/spool/postfix/var/run/clamav');

   SYS.AddUserToGroup('postfix','clamav','','');
   SYS.AddUserToGroup('clamav','postfix','','');
   SYS.AddUserToGroup('clamav','mail','','');
   
   LogFile:=ExtractFilePath(CLAMD_GETINFO('LogFile'));
   DatabaseDirectory:=CLAMD_GETINFO('DatabaseDirectory');
   UpdateLogFile:='/var/log/clamav/freshclam.log';
   
   logs.Syslogs('Starting......: Clamav-milter daemon');
   logs.Syslogs('Starting......: Clamav-milter daemon log file: '+LogFile);
   logs.Syslogs('Starting......: Clamav-milter daemon DatabaseDirectory: '+DatabaseDirectory);
   
   

   
      logs.OutputCmd('/bin/chown -R postfix:postfix /var/spool/postfix/var/run/clamav');
      logs.OutputCmd('/bin/chown -R clamav:clamav '+LogFile);
      logs.OutputCmd('/bin/chown -R clamav:clamav '+DatabaseDirectory);
      logs.OutputCmd('/bin/chown -R clamav:clamav '+UpdateLogFile);
      logs.OutputCmd('/bin/chown -R clamav:clamav '+UpdateLogFile);
      logs.OutputCmd('/bin/chown -R clamav:clamav /var/run/clamav');
   

   if not FileExists(SYS.LOCATE_SU()) then begin
      logs.Syslogs('Starting......: Clamav-milter daemon unable to stat su....');
      exit;
   end;

   {cmd:=SYS.LOCATE_SU() + ' postfix -c "' + daemon_path;
   cmd:=cmd+' --sendmail-cf=/etc/mail/sendmail.cf --force-scan --sign';
   cmd:=cmd+' --pidfile=/var/spool/postfix/var/run/clamav/clamav-milter.pid';
   cmd:=cmd+' --config-file='+CLAMD_CONF_PATH();
   cmd:=cmd+' --freshclam-monitor=360 local:/var/spool/postfix/var/run/clamav/clamav-milter.ctl" &';
   }

   cmd:=daemon_path +' --config-file=/etc/clamav/clamav-milter.conf';
   logs.Debuglogs(cmd);
   fpsystem(cmd);


  pid:=MILTER_PID();
  while not SYS.PROCESS_EXIST(pid) do begin

        sleep(500);
        count:=count+1;
        if count>50 then begin
            logs.DebugLogs('Starting......: Clamav-milter daemon timeout...');
            break;
        end;
        pid:=MILTER_PID();
  end;

          
   if not SYS.PROCESS_EXIST(MILTER_PID()) then begin
      logs.DebugLogs('Starting......: Clamav-milter daemon failed to start');
      exit;
   end;

 logs.syslogs('Starting......: Clamav-milter success with new PID '+MILTER_PID());


end;
//#############################################################################
procedure Tclamav.WRITE_CLAMAV_MILTER();
var
   l:Tstringlist;
   LocalSocket:string;
begin

localsocket:=CLAMD_GETINFO('LocalSocket');
l:=Tstringlist.create;
l.add('MilterSocket local:/var/spool/postfix/var/run/clamav/clamav-milter.ctl');
l.add('FixStaleSocket yes');
l.add('User postfix');
l.add('AllowSupplementaryGroups yes');
l.add('ReadTimeout 300');
l.add('Foreground no');
l.add('PidFile /var/spool/postfix/var/run/clamav/clamav-milter.pid');
l.add('TemporaryDirectory /var/tmp');
l.add('## LocalSocket unix: in clamd.conf');
l.add('ClamdSocket unix:'+ localsocket);
l.add('OnClean Accept');
l.add('OnInfected Quarantine');
l.add('OnFail Accept');
l.add('AddHeader Yes');
l.add('LogFile /var/log/clamav/clamav-milter.log');
l.add('LogFileMaxSize 2M');
l.add('LogTime yes');
l.add('LogSyslog yes');
l.add('LogFacility LOG_MAIL');
l.add('LogVerbose no');
l.add('LogInfected Basic');
l.add('MaxFileSize 5');
logs.WriteToFile(l.Text,'/etc/clamav/clamav-milter.conf');
l.free;
end;
//#############################################################################
procedure Tclamav.SENDMAIL_CF_AND_MILTER();
var
   l:TstringList;
   RegExpr:TRegExpr;
   found:boolean;
   sendmail_path:string;
   line:string;
   i:integer;
begin
    found:=false;
    line:='INPUT_MAIL_FILTER(''clamav'', ''S=local:/var/spool/postfix/var/run/clamav/clamav-milter.ctl, F=, T=S:2m;R:2m'')dnl';
    sendmail_path:=SYS.LOCATE_SENDMAIL_CF();
    if not fileExists(sendmail_path) then exit;
     RegExpr:=TRegExpr.Create;
     RegExpr.Expression:='^INPUT_MAIL_FILTER.+clamav';
     l:=TstringList.Create;
     l.LoadFromFile('/etc/mail/sendmail.cf');
     for i:=0 to l.Count-1 do begin
           if RegExpr.Exec(l.Strings[i]) then begin
               l.Strings[i]:=line;
               found:=true;
               break;
           end;
     
     end;

     if not found then l.Add(line);
     logs.Syslogs('Starting......: Clamav-milter Patching ' +sendmail_path+' ->"'+line+'"');
     l.SaveToFile(sendmail_path);
     l.free;
     RegExpr.free;
     
end;
//#############################################################################



procedure Tclamav.MILTER_CHANGE_INITD();
var
l:TstringList;
initpath:string;
begin
  if not FileExists(MILTER_INITD_PATH()) then exit;
  initpath:=MILTER_INITD_PATH();
  l:=TstringList.Create;
  

l.Add('#!/bin/sh');
l.Add('#Begin ' + initpath);

 if fileExists('/sbin/chkconfig') then begin
    l.Add('# chkconfig: 2345 11 89');
    l.Add('# description: Artica-postfix Daemon');
 end;

l.Add('case "$1" in');
l.Add(' start)');
l.Add('    /usr/share/artica-postfix/bin/artica-install start clammilter');
l.Add('    ;;');
l.Add('');
l.Add('  stop)');
l.Add('    /usr/share/artica-postfix/bin/artica-install stop clammilter');
l.Add('    ;;');
l.Add('');
l.Add(' restart)');
l.Add('     /usr/share/artica-postfix/bin/artica-install start clammilter');
l.Add('     sleep 3');
l.Add('     /usr/share/artica-postfix/bin/artica-install stop clammilter');
l.Add('    ;;');
l.Add('');
l.Add('  *)');
l.Add('    echo "Usage: $0 {start|stop|restart}');
l.Add('    exit 1');
l.Add('    ;;');
l.Add('esac');
l.Add('exit 0');
l.SaveToFile(MILTER_INITD_PATH());
l.free;
end;
//#############################################################################


procedure TClamav.MILTER_STOP();
var
count:integer;
pids:string;
begin
  if not FileExists(MILTER_DAEMON_PATH()) then begin
     writeln('Stopping clamav-milter.......: Not installed');
     exit;
  end;

  count:=0;
  pids:=MILTER_PID();
  if SYS.PROCESS_EXIST(pids) then begin
     writeln('Stopping clamav-milter.......: PID '+pids);

     while SYS.PROCESS_EXIST(pids) do begin
           Inc(count);
            fpsystem('/bin/kill ' + pids);
           sleep(100);
           if count>20 then begin
                  logs.Output('killing clamav-milter........: ' + MILTER_PID() + ' PID (timeout)');
                   fpsystem('/bin/kill -9 ' + pids);
                  break;
           end;
     end;
  end;
  pids:=SYS.PidAllByProcessPath(MILTER_DAEMON_PATH());

  if length(pids)>0 then begin
         writeln('Stopping clamav-milter.......: PIDs '+pids);
         fpsystem('/bin/kill -9 ' + pids);
  end;
  
  


end;
//##############################################################################
function TClamav.ReadFileIntoString(path:string):string;
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
function TClamav.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
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
function TClamav.CLAMAV_VERSION():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    BinPath:string;
    tmpstr:string;
begin

if FileExists(CLAMSCAN_BIN_PATH()) then Binpath:=CLAMSCAN_BIN_PATH();
if length(BinPath)=0 then begin
   if not FileExists(CLAMD_BIN_PATH()) then exit;
end;
result:=SYS.GET_CACHE_VERSION('APP_CLAMAV');
if length(result)>1 then exit;

tmpstr:=logs.FILE_TEMP();

fpsystem(Binpath+' --version >'+tmpstr);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='ClamAV\s+([0-9\.a-z]+)';
    FileDatas:=TStringList.Create;
    FileDatas.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    for i:=0 to FileDatas.Count-1 do begin
        if RegExpr.Exec(FileDatas.Strings[i]) then begin
             result:=RegExpr.Match[1];
             break;
        end;
    end;
             RegExpr.free;
             FileDatas.Free;
             SYS.SET_CACHE_VERSION('APP_CLAMAV',result);

end;
//#############################################################################
function TClamav.CLAMAV_BINVERSION():integer;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    BinPath:string;
    tmpstr:string;
    strversion:string;
begin

if FileExists(CLAMSCAN_BIN_PATH()) then Binpath:=CLAMSCAN_BIN_PATH();
if length(BinPath)=0 then begin
   if not FileExists(CLAMD_BIN_PATH()) then exit(0);
end;



   strversion:=SYS.GET_CACHE_VERSION('APP_CLAMAVBIN');
   if length(strversion)>1 then begin
       TryStrToInt(strversion,result);
      exit;
   end;



tmpstr:=logs.FILE_TEMP();


fpsystem(Binpath+' --version >'+tmpstr);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='ClamAV\s+([0-9\.]+)';
    FileDatas:=TStringList.Create;
    FileDatas.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    for i:=0 to FileDatas.Count-1 do begin
        if RegExpr.Exec(FileDatas.Strings[i]) then begin
             strversion:=RegExpr.Match[1];
             break;
        end;
    end;

if strversion='' then strversion:='0';
strversion:=AnsiReplaceText(strversion,'.','');

TryStrToInt(strversion,result);
RegExpr.free;
FileDatas.Free;
SYS.SET_CACHE_VERSION('APP_CLAMAVBIN',IntToSTr(result));
end;
//#############################################################################
function TClamav.CLAMAV_PATTERN_VERSION():string;
var
    RegExpr:TRegExpr;
    FileDatas:TStringList;
    i:integer;
    main_dbver,BinPath:string;
    D:boolean;
    tmpstr:string;
begin
d:=logs.COMMANDLINE_PARAMETERS('--verbose');



if length(BinPath)=0 then begin
   if FileExists(CLAMSCAN_BIN_PATH()) then Binpath:=CLAMSCAN_BIN_PATH();
end;
if length(BinPath)=0 then begin
   if not FileExists(CLAMD_BIN_PATH()) then exit;
end;

result:=SYS.GET_CACHE_VERSION('APP_CLAMAV_PATTERN');
if length(result)>0 then exit;

tmpstr:=logs.FILE_TEMP();
 if D then writeln(BinPath +' -V >'+tmpstr);

fpsystem(BinPath +' -V >'+tmpstr);
if D then writeln('^ClamAV (.+)/([0-9]+) --> ' + logs.ReadFromFile(tmpstr));
    if not FileExists(tmpstr) then exit;
    RegExpr:=TRegExpr.Create;
    FileDatas:=TStringList.Create;
    try FileDatas.LoadFromFile(tmpstr) except exit; end;
    logs.DeleteFile(tmpstr);
    for i:=0 to FileDatas.Count-1 do begin
        RegExpr.Expression:='^ClamAV (.+)/([0-9]+)';
        if RegExpr.Exec(FileDatas.Strings[i]) then begin
             main_dbver:=RegExpr.Match[2];
        end;



    end;
    result:=main_dbver;
    SYS.SET_CACHE_VERSION('APP_CLAMAV_PATTERN',result);
             RegExpr.free;
             FileDatas.Free;

end;
//#############################################################################
function TClamav.CLAMD_GETINFO(Key:String):string;
var
RegExpr:TRegExpr;
l:TStringList;
i:integer;
begin
 if not FileExists(CLAMD_CONF_PATH()) then begin
    logs.Debuglogs('CLAMD_GETINFO:: unable to stat clamd.conf');
    exit;
 end;
 
 if DEBUG then writeln('CLAMD_CONF_PATH()::',CLAMD_CONF_PATH());
 l:=TStringList.Create;
 
 RegExpr:=TRegExpr.Create;
 try
    l.LoadFromFile(CLAMD_CONF_PATH());
 except
    logs.Debuglogs('CLAMD_GETINFO:: Fatal error');
    exit;
 end;
 RegExpr.Expression:='^' + Key + '\s+(.+)';
 For i:=0 to l.Count-1 do begin
     if RegExpr.Exec(l.Strings[i]) then begin
        result:=RegExpr.Match[1];
        break;
     end;

 end;
  RegExpr.Free;
  l.free;
end;

//##############################################################################




end.
