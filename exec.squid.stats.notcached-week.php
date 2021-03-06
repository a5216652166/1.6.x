<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){
		ini_set('display_errors', 1);	
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');



parse();
function parse(){
	$export_path="/home/artica/squid/dbExport";
	$TimeFile="/etc/artica-postfix/pids/exec.squid.stats.notcached-week.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.notcached-week.php.pid";
	$unix=new unix();

	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}

	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<30){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}

	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<30){
			echo "Current {$time}Mn, require at least 30mn\n";
			return;
		}
	}

	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$files=$unix->DirFiles("/var/log/squid","[0-9]+_NOTCACHED_WEEK\.db");
	$currentWeek=date("YW");
	
	@mkdir($export_path,0755,true);
	
	
	$q=new mysql_squid_builder();
	while (list ($filename, $none) = each ($files) ){
		if(!preg_match("#^([0-9]+)_#", $filename,$re)){continue;}
		$WeekName=$re[1];
		$fullpath="/var/log/squid/$filename";
		echo " $fullpath -> $WeekName -> $currentWeek\n";
		$berekley=new parse_berekley_dbs();
		
		$tablename="{$WeekName}_not_cached";
		$sql=$berekley->NOT_CACHED_WEEK_PARSE_TABLE_STRING($tablename);
		$q->QUERY_SQL($sql);
		if(!$q->ok){squid_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__); return; }
		
		if($WeekName==$currentWeek){$q->QUERY_SQL("TRUNCATE TABLE $tablename");}
		$array=$berekley->NOT_CACHED_WEEK_PARSE_DB($fullpath);
		if(!$array){continue;}
		$prefix=$berekley->NOT_CACHED_WEEK_TABLE_PREFIX($tablename);
		$sql=$prefix." ".@implode(",", $array);
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){squid_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__); return; }
		
		if($WeekName==$currentWeek){continue;}
		if(!@copy($fullpath, "$export_path/$filename")){continue;}
		@unlink($fullpath);
		
	}
	
	@mkdir("/usr/share/artica-postfix/ressources/logs/stats",0755,true);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM {$currentWeek}_not_cached"));
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/stats/NOT_CACHED", $ligne["size"]);
	@chmod("/usr/share/artica-postfix/ressources/logs/stats/NOT_CACHED",0755);
	
}


