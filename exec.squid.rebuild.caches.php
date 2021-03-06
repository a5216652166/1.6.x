<?php
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--report#",implode(" ",$argv))){$GLOBALS["REPORT"]=true;}
	
	
}

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--reindex"){reindex_caches();die();}
if($argv[1]=="--default"){rebuild_default_cache();die();}
if($argv[1]=="--clean"){clean_old_caches();die();}
if($argv[1]=="--empty"){cache_central_rebuild($argv[2]);}


rebuildcaches();


function cache_central_rebuild($ID){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already executed pid $pid\n";
		die();
	}
	
	cache_central_rebuild_progress("{starting}",0);
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	sleep(3);
	
	if(!is_numeric($ID)){
		cache_central_rebuild_progress("No such ID",100);
		return;
	}
	
	if($ID==0){
		cache_central_rebuild_progress("Cannot accept ID 0",100);
		return;
	}
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){
		echo $q->mysql_error."\n";
		cache_central_rebuild_progress("MySQL error !",100);
		return;
	}
	
	$CacheName=$ligne["cachename"];
	$cache_directory=$ligne["cache_dir"];
	$cache_type=$ligne["cache_type"];
	
	echo "Cache Name..........: $CacheName\n";
	echo "Cache Directory.....: $cache_directory\n";
	echo "Cache Type..........: $cache_type\n";
	
	
	if(!is_dir($cache_directory)){
		echo "\"$cache_directory\" no such directory...\n";
		cache_central_rebuild_progress("&laquo;$cache_directory&raquo; [$ID] no such directory",100);
		return;
	}
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	cache_central_rebuild_progress("{empty} $CacheName",5);
	
	if($cache_type=="Cachenull"){cache_central_rebuild_progress("Null cache, aborting",100);return;}

	
	cache_central_rebuild_progress("{calculate_disk_space}",10);
	$used=$unix->DIRSIZE_MB($cache_directory);
	$cache_partition=$unix->DIRPART_OF($cache_directory);
	$cache_partition_free=$unix->DIRECTORY_FREEM($cache_directory);
	$Required_operation=$used+$used;
	$AfterOperation=$cache_partition_free-$Required_operation;
	$next_cache_directory="$cache_directory-delete-".time();
	$CAN_BE_MOVED=true;
	
	echo "Current size.........: {$used}M\n";
	echo "Partition............: $cache_partition\n";
	echo "Partition Free.......: {$cache_partition_free}M\n";
	echo "Free for operation...: {$Required_operation}M\n";
	echo "Size after operation.: {$AfterOperation}M\n";
	
	if($AfterOperation<10){ 
		echo "No space left on partition, need to remove directly content...\n";
		$CAN_BE_MOVED=false; }
		
	if(!$CAN_BE_MOVED){
		cache_central_rebuild_progress("{removing_cache}",30);
		shell_exec("$rm -rf $cache_directory/*");
	}else{
		cache_central_rebuild_progress("{moving_cache}",30);
		echo "Moving $cache_directory to $next_cache_directory\n";
		shell_exec("$mv $cache_directory $next_cache_directory");
	}
	
	cache_central_rebuild_progress("{reconstruct_cache}",40);
	system("$php5 /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly --norestart");
	cache_central_rebuild_progress("{restarting_proxy_service}",50);
	cache_central_rebuild_progress("{stopping_proxy_service}",60);
	system("/etc/init.d/squid stop --force");
	cache_central_rebuild_progress("{starting_proxy_service}",80);
	system("/etc/init.d/squid start");
	cache_central_rebuild_progress("{refreshing_status}",90);
	
	system("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --caches-center-status --force");
	cache_central_rebuild_progress("{done} {close_windows}",100);

}
function cache_central_rebuild_progress($text,$prc){
	$file="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	@chmod("/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.txt",0755);

}


function reindex_caches(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());
	
	$t=time();
	$array=ListCaches();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	writelogs(count($array)." caches to re-index...",__FUNCTION__,__FILE__,__LINE__);
	if(count($array)==0){writelogs("Fatal error",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		writelogs("squid, no such binary file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	$ToReboot=false;
	
	while (list ($cache_dir, $CacheType) = each ($array) ){	
		if(is_file("$cache_dir/swap.state.new")){
			$size=@filesize("$cache_dir/swap.state.new");
			@unlink("$cache_dir/swap.state.new");
			$size=FormatBytes($size/1024);
			squid_admin_mysql(0,"Reset swap.state.new ($size) of cache $cache_dir type [$CacheType]",null,__FILE__,__LINE__);
			$ToReboot=true;
		}
		
		if(is_file("$cache_dir/swap.state")){
			writelogs("Delete $cache_dir/swap.state",__FUNCTION__,__FILE__,__LINE__);
			$size=@filesize("$cache_dir/swap.state");
			$size=FormatBytes($size/1024);
			@unlink("$cache_dir/swap.state");
			
			squid_admin_mysql(0,"Reset swap.state ($size) of cache $cache_dir type [$CacheType]",null,__FILE__,__LINE__);
			$ToReboot=true;
		}else{
			writelogs("Warning $cache_dir/swap.state no such file",__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	@unlink("/etc/artica-postfix/squid.lock");
	
	if($ToReboot){
		squid_admin_mysql(0,"Restarting Proxy service after Reset cache(s)",null,__FILE__,__LINE__);
		writelogs("Restarting squid",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("/usr/bin/php5 /usr/share/artica-postfix/exec.squid.watchdog.php --restart --by-reset-caches");
	}
	
	
	for($i=0;$i<30;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");	
	
}
	
function rebuild_default_cache(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	
	$squid=new squidbee();
	shell_exec($unix->LOCATE_PHP5_BIN()." ".basename(__FILE__)."/exec.squid.php --build >/dev/null 2>&1");
	$cache_dir=$squid->CACHE_PATH;
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$php5=$unix->LOCATE_PHP5_BIN();
	writelogs("$cache_dir to delete...",__FUNCTION__,__FILE__,__LINE__);
	$t=time();
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());	
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		writelogs("squid, no such binary file",__FUNCTION__,__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
		return;
	}

	writelogs("Stopping squid",__FUNCTION__,__FILE__,__LINE__);
	$sock->TOP_NOTIFY("Proxy is stopped to rebuild default cache...","info");
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");	
	$cachesRename="$cache_dir-delete-$t";	
	exec("$mv $cache_dir $cachesRename 2>&1",$results);
	writelogs("re-create $cache_dir",__FUNCTION__,__FILE__,__LINE__);
	@mkdir($cache_dir,0755,true);
	@chown($cache_dir, "squid");
	@chgrp($cache_dir, "squid");	
	exec("$squidbin -z 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs("$ligne",__FUNCTION__,__FILE__,__LINE__);
		
	}	
	shell_exec("$chown -R squid:squid $cache_dir");
	shell_exec("$chown -R 0755 $cache_dir");
	
	@unlink("/etc/artica-postfix/squid.lock");
	writelogs("starting squid",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix start squid-cache");
	$sock->TOP_NOTIFY("Proxy was restarted to rebuild default cache...","info");
	for($i=0;$i<60;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	writelogs("Deleting  $cachesRename",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$rm -rf $cachesRename");
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$sock->TOP_NOTIFY("Default Proxy cache was rebuilded took: $took","info");
		
}	


function rebuildcaches(){
	$logFile="/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		ouputz("Already process exists $pid, aborting", __LINE__);
		die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@unlink($logFile);
	ouputz("Please wait, rebuild caches....", __LINE__);
	$t=time();
	ouputz("Listing caches....", __LINE__);
	$array=ListCaches();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	ouputz(count($array)." caches to delete...",__LINE__);
	if(count($array)==0){
		ouputz("Fatal, unable to list available caches...", __LINE__);
		squid_admin_mysql(0, "Fatal, unable to list available caches", null,__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
	die();}
	
	
	
	$t=time();
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	if(!is_file($squidbin)){
		ouputz("squid, no such binary file",__LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
		return;
	}
	
	squid_admin_mysql(1, "Stopping Proxy service in order to rebuild caches", null,__FILE__,__LINE__);
	ouputz("Stopping squid, please wait...",__LINE__);
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Asking to Stop Squid for rebuilding caches\n".@implode("\n", $GLOBALS["LOGS"])."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");	
	shell_exec("/etc/init.d/squid stop --force --script=".basename(__FILE__));
	

	
	if($GLOBALS["REPORT"]){
		while (list ($cache_dir, $ligne) = each ($array) ){
			$DIRARRAY=$unix->DIR_STATUS($cache_dir);
			$size=$array["SIZE"];
			$used=$array["USED"];
			$pourc=$array["POURC"];
			$mounted=$array["MOUNTED"];
			$logs[]="$cache_dir size: $size, used:$used {$pourc}% mounted on $mounted";
		}
		
		squid_admin_mysql(2,"Report on caches status",@implode("\n", $logs),__FILE__,__LINE__);
	}
	
	
	
	while (list ($cache_dir, $ligne) = each ($array) ){
		if(preg_match("#MemBooster#", $cache_dir)){
			squid_admin_mysql(1, "Removing cache $cache_dir", null,__FILE__,__LINE__);
			ouputz("Removing $cache_dir content...",__LINE__);
			squid_admin_mysql(2, "Removing cache $cache_dir done", null,__FILE__,__LINE__);
			shell_exec("$rm -rf $cache_dir/*");
			continue;
		}
		
		$DISK_STATUS=$unix->DF_SATUS_K($cache_dir);
		$DIRECTORY_SIZE=($unix->DIRSIZE_BYTES($cache_dir)/1024)/1024;
		$AIVA=$DISK_STATUS["AIVA"]*1024;
		if($AIVA<10){
			ouputz("Removing $cache_dir '$DIRECTORY_SIZE'M Available {$AIVA}M",__LINE__);
			shell_exec("$rm -rf $cache_dir");
			ouputz("re-create $cache_dir",__LINE__);
			squid_admin_mysql(2, "Re-create $cache_dir", null,__FILE__,__LINE__);
			@mkdir($cache_dir,0755,true);
			@chown($cache_dir, "squid");
			@chgrp($cache_dir, "squid");
			continue;
		}
		$DIRECTORY_SIZE_NEC=$DIRECTORY_SIZE*2;
		if($AIVA<$DIRECTORY_SIZE_NEC){
			ouputz("Removing $cache_dir '$DIRECTORY_SIZE'M Available {$AIVA}M",__LINE__);
			shell_exec("$rm -rf $cache_dir");
			ouputz("re-create $cache_dir",__LINE__);
			squid_admin_mysql(2, "Re-create $cache_dir", null,__FILE__,__LINE__);
			@mkdir($cache_dir,0755,true);
			@chown($cache_dir, "squid");
			@chgrp($cache_dir, "squid");
			continue;
			
		}
		
		
		$cachesRename[]="$cache_dir-delete-$t";
		ouputz("Moving $cache_dir to $cache_dir-delete-$t...",__LINE__);
		exec("$mv $cache_dir $cache_dir-delete-$t 2>&1",$results);
		$results=array();
		while (list ($num, $ll) = each ($results) ){ouputz("$ligne",__LINE__);}		
		ouputz("re-create $cache_dir",__LINE__);
		squid_admin_mysql(2, "Re-create $cache_dir", null,__FILE__,__LINE__);
		@mkdir($cache_dir,0755,true);
		@chown($cache_dir, "squid");
		@chgrp($cache_dir, "squid");
	}
	

	$su=$unix->find_program("su");
	$results=array();
	ouputz("Building new caches $su -c \"$squidbin -z\" squid",__LINE__);
	exec("$su -c \"$squidbin -z\" squid 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){ouputz("$ligne",__LINE__);}	
	
	ouputz("Remove lock file...",__LINE__);
	@unlink("/etc/artica-postfix/squid.lock");
	ouputz("Starting squid, please wait...",__LINE__);
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Asking to start squid after rebuilding caches...\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
	
	squid_admin_mysql(2, "Starting Proxy Service", null,__FILE__,__LINE__);
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --start");
	
	for($i=0;$i<60;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		ouputz("Waiting {$i}s/60 to Squid-cache be ready...",__LINE__);
		sleep(1);
	}
	
	ouputz("Done... Squid-cache seems to be ready...",__LINE__);
	squid_admin_mysql(2, "Reloading $squidbin cache", null,__FILE__,__LINE__);
	ouputz("Reloading $squidbin cache",__LINE__);
	$results=array();
	squid_watchdog_events("Reconfiguring Proxy parameters...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
	
	
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null";
	shell_exec($cmd);
	
	
	
	$NICE=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	ouputz("Refresh caches information, please wait...",__LINE__);
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	if(is_array($cachesRename)){
		reset($cachesRename);
		while (list ($index, $cache_dir) = each ($cachesRename) ){
			$cmd="$nohup $NICE $rm -rf $cache_dir >/dev/null 2>&1 &";
			squid_admin_mysql(2, "Ask to delete old cache dir $cache_dir done","$called",__FILE__,__LINE__);
			ouputz("Deleting  $cache_dir $cmd",__LINE__);
			shell_exec($cmd);
		}
	}
	
	$took=$unix->distanceOfTimeInWords($t,time());
	squid_admin_mysql(2, "All Proxy caches was rebuilded took: $took","$called",__FILE__,__LINE__);
	$sock->TOP_NOTIFY("All Proxy caches was rebuilded took: $took","info");	
	
	
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function IfDirIsACache($directory){
	if(is_file("$directory/swap.state")){return true;}
	
	for ($i=0;$i<10;$i++){
		if(is_dir("$directory/0$i/0$i")){return true;}
	}
	
	return false;
}

function clean_old_caches(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){
		echo "pidfile = $pidfile\n";
		echo "PidTime = $PidTime\n";
	}
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$sock=new sockets();
	
	
	
	
	if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	$Time=$unix->file_time_min($PidTime);
	if(!$GLOBALS["FORCE"]){
		if($Time<15){
			if($GLOBALS["VERBOSE"]){ echo "Only each 15mn $PidTime, die()\n";}
			die();
		}
	}
	
	@unlink($PidTime);
	@file_put_contents($PidTime,time());
	
	
	$rm=$unix->find_program("rm");
	$dirs=$unix->dirdir("/home");
	while (list ($directoryName, $ligne) = each ($dirs) ){
		$basename=basename($directoryName);
		if(is_numeric($basename)){
		if($GLOBALS["VERBOSE"]){ echo "remove $directoryName\n";}
		shell_exec("$rm -rf $directoryName");
		}
	}
		
	
	
	
	$Todelete=array();
	if($GLOBALS["VERBOSE"]){echo "Get list of caches....\n";}
	$ListCaches=ListCaches();
	while (list ($directory, $ligne) = each ($ListCaches) ){
		if($GLOBALS["VERBOSE"]){echo "squid-cache........: Using $directory\n";}
		$directory=dirname($directory);
		if($GLOBALS["VERBOSE"]){echo "Add $directory into collection\n";}
		$FINAL[$directory]=true;
	}
	reset($ListCaches);
	
	
	$dirs=$unix->dirdir("/home/squid/cache");
	while (list ($directory, $ligne) = each ($dirs) ){
		if(!IfDirIsACache($directory)){continue;}
		if(!isset($ListCaches[$directory])){
			$Todelete[$directory]=true;
			if($GLOBALS["VERBOSE"]){echo "*** Not used $directory i should remove it.. ***\n";}
		}
	}
	
	
	$dirs=$unix->dirdir("/home/squid/caches");
	while (list ($directory, $ligne) = each ($dirs) ){
		if(!IfDirIsACache($directory)){continue;}
		if(!isset($ListCaches[$directory])){
			$Todelete[$directory]=true;
			if($GLOBALS["VERBOSE"]){echo "*** Not used $directory i should remove it.. ***\n";}
		}
	}

$FINAL["/home/squid/cache"]=true;
	
	while (list ($directory, $ligne) = each ($FINAL) ){
		if($GLOBALS["VERBOSE"]){echo "$directory\n";}
		$dirs=$unix->dirdir($directory);
		while (list ($directoryName, $ligne) = each ($dirs) ){
			$basename=basename($directoryName);
			if(preg_match("#-delete-#", $basename)){
				if($GLOBALS["VERBOSE"]){echo "Found $directoryName\n";}
				$Todelete[$directoryName]=true;
			}
		}
		
	}	
	
	if(count($Todelete)==0){
		if($GLOBALS["VERBOSE"]){echo "No directory\n";}
		return;
	}
	
	while (list ($directory, $ligne) = each ($Todelete) ){
		if(!is_dir($directory)){continue;}
		if($GLOBALS["VERBOSE"]){echo "Must remove `$directory`\n";}
		$t=time();
		$distance=$unix->distanceOfTimeInWords($t,time(),true);
		shell_exec("$rm -rf \"$directory\" >/dev/null 2>&1");
		squid_admin_mysql(2, "$directory removed", "Task took $distance for this directory");
	}
	
}



function ListCaches(){
	$f=file("/etc/squid3/squid.conf");
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^cache_dir\s+(.+?)\s+(.+?)\s+#", $ligne,$re)){
			if($re[1]=="null"){continue;}
			
			$array[trim($re[2])]=$re[1];
		}
	}
	return $array;
}
function ouputz($text,$line){
	
	if($GLOBALS["SCHEDULE_ID"]>1){
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}		
		ufdbguard_admin_events($text, $trace[1]["function"], __FILE__, $line, "proxy");
	}
	
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");

	$logFile="/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt";
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(is_file($logFile)){
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = fopen($logFile, 'a');
	fwrite($f, "$date [$pid][$line]: $text\n");
	fclose($f);
	chmod($logFile, 0777);
}


