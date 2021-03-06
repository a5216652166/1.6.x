<?php
//exec.squid.stats.days.websites.php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();


if($argv[1]=="--restart"){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET cached=0 WHERE cached=1");
	
}




$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$myfile=basename(__FILE__);
$unix=new unix();


if(!$GLOBALS["FORCE"]){
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myfile)){return;}
	$time=$unix->file_time_min($pidTime);
	if($time<2880){if($GLOBALS["VERBOSE"]){echo "$pidTime -> {$time}Mn/2880mn\n";}die();}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	@file_put_contents($pidfile, getmypid());

}






	$q=new mysql_squid_builder();

	$sql="CREATE TABLE IF NOT EXISTS `cached_total` (
  `size` bigint(255) unsigned NOT NULL,
  `cached` smallint(1) unsigned NOT NULL,
  `zDate` date NOT NULL,
  `zmd5` varchar(40) NOT NULL,
  PRIMARY KEY (`zmd5`),
  KEY `size` (`size`),
  KEY `cached` (`cached`)
) ENGINE=MyISAM;";
	$q->QUERY_SQL($sql,$q->database);
	if(!$q->ok){ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}


	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `suffix`, DATE_FORMAT(zDate,'%Y-%m-%d') AS `zDay`,`tablename`
		FROM tables_day WHERE `cached`=0
		AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)
		ORDER BY zDate DESC";

if($GLOBALS["VERBOSE"]){echo "$sql\n";}
$results=$q->QUERY_SQL($sql);
if(!$q->ok){ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");die();}


if($GLOBALS["VERBOSE"]){echo "checking ".mysql_num_rows($results)." items\n";}
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$table_source="{$ligne["suffix"]}_hour";
	if(!$q->TABLE_EXISTS($table_source)){continue;}
	if(perform($table_source,$ligne["zDay"])){
		$q->QUERY_SQL("UPDATE tables_day SET cached=1 WHERE `tablename`='{$ligne["tablename"]}'");
		
	}
	
	if(system_is_overloaded(__FILE__)){
		ufdbguard_admin_events("Fatal overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task",__FUNCTION__,__FILE__,__LINE__,"stats");
		break;
	}
	
	
}





function perform($tablesource,$zday){
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(size) as size,cached FROM $tablesource GROUP BY cached";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}
	

	$prefix="INSERT IGNORE INTO `cached_total` (`zmd5`,`size`,`cached`,zDate) VALUES ";

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=md5(serialize($ligne));
		$f[]="('$zmd5','{$ligne["size"]}','{$ligne["cached"]}','$zday')";
		
		
	}
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
	}
	
	return true;
	
}
