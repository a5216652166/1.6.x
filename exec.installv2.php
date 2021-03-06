<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(is_file("/etc/artica-postfix/AS_KIMSUFFI")){echo "AS_KIMSUFFI!\n";die();}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($argv[1]=="--install"){install($argv[2]);exit;}


function parsefilename($filename){
	echo "Uploaded $filename\n";
	if(preg_match("#^(.+?)-(.+?)([0-9]+)-([0-9]+)-([0-9\.\-]+)\.tar\.gz$#", $filename,$re)){
		$ARCHBIN=$re[4];
		$VERSION=$re[5];
		$distri=$re[2].$re[3];
		$array["ARCHBIN"]=$ARCHBIN;
		$array["VERSION"]=$VERSION;
		$array["DISTRI"]=$distri;
		return $array;
	}
	
	if(preg_match("#^(.+?)-(.+?)([0-9]+)-([a-z]+)([0-9]+)-(.+?)\.tar\.gz$#", $filename,$re)){
		$array["VERSION"]=$re[6];
		$array["DISTRI"]=$re[4].$re[5];
		$array["ARCHBIN"]=$re[3];
		return $array;
	}
	
	
		
	
	
	return array();
}


function install($filename){
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/$filename.progress";
	$GLOBALS["DOWNLOAD_PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/$filename.download.progress";
	
	echo "Starting $filename\n";
	$unix=new unix();
	$LINUX_CODE_NAME=$unix->LINUX_CODE_NAME();
	$LINUX_DISTRIBUTION=$unix->LINUX_DISTRIBUTION();
	$LINUX_VERS=$unix->LINUX_VERS();
	$LINUX_ARCHITECTURE=$unix->LINUX_ARCHITECTURE();
	$DebianVer="debian{$LINUX_VERS[0]}";
	$TMP_DIR=$unix->TEMP_DIR();
	$UPLOADED_DIR=dirname(__FILE__)."/ressources/conf/upload";
	$UPLOADED_FILE="$UPLOADED_DIR/$filename";
	
	$t=time();
	build_progress("Analyze...$filename",10);
	echo "Operating system........: $DebianVer\n";
	
	if(!is_file($UPLOADED_FILE)){
		echo "$UPLOADED_FILE no such file\n";
		$tarballs_file="/usr/share/artica-postfix/ressources/logs/web/tarballs.cache";
		$Content=@file_get_contents($tarballs_file);
		$strlen=strlen($Content);
		if(preg_match("#<PACKAGES>(.*?)</PACKAGES>#", $Content,$re)){$MAIN=unserialize(base64_decode($re[1])); }
		$ligne=$MAIN[$filename];
		$ARCH=$ligne["ARCH"];
		if(preg_match("#([0-9]+)#", $ARCH,$re)){$ARCHBIN=$re[1];}
		$SIZE=$ligne["SIZE"];
		$VERSION=$ligne["VERSION"];
		$distri=$ligne["distri"];
		$uri=$ligne["uri"];
		$TMP_FILE="$TMP_DIR/$filename";
	}
	
	if(is_file($UPLOADED_FILE)){
		echo "Uploaded $UPLOADED_FILE\n";
		
		$EXTRACT=parsefilename($filename);
		
		if(count($EXTRACT)<2){
			echo "$filename did not match defined pattern...\n";
			build_progress("{failed}...$filename Not supported",110);
			@unlink($UPLOADED_FILE);
			sleep(5);
			return;
		}
		
		$ARCHBIN=$EXTRACT["ARCHBIN"];
		$VERSION=$EXTRACT["VERSION"];
		$distri=$EXTRACT["DISTRI"];
		//$DebianVer=$re[3];
		$TMP_FILE=$UPLOADED_FILE;
		$SIZE=@filesize($TMP_FILE);
	}
	

		
	
	
	

	echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
	echo "Package.................: $filename\n";
	echo "Architecture............: x$ARCHBIN\n";
	echo "Version.................: $VERSION\n";
	echo "Operating system........: $distri/$DebianVer\n";
	echo "Temp dir................: $TMP_DIR\n";
	echo "Size....................: $SIZE bytes\n";
	
	
	
	
	$comp=true;
	if($LINUX_ARCHITECTURE<>$ARCHBIN){$comp=false;$log[]="$LINUX_ARCHITECTURE !== $ARCHBIN";}
	if($LINUX_CODE_NAME<>"DEBIAN"){$log[]="$LINUX_CODE_NAME !== DEBIAN";$comp=false;}
	if($DebianVer<>$distri){$comp=false;$log[]="$DebianVer !== $distri";$comp=false;}
	if(!$comp){
		echo @implode("\n", $log);
		build_progress("{not_compatible}...",110);
		if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
		return;
	}
	
	if(!is_file($UPLOADED_FILE)){
		echo "Downloading $filename...\n";
		build_progress("{downloading} $filename...",20);
		
		$curl=new ccurl($uri);
		$curl->Timeout=2400;
		$curl->WriteProgress=true;
		$curl->ProgressFunction="download_progress";
		if(!$curl->GetFile($TMP_FILE)){
			build_progress("{downloading} $filename {failed}...",110);
			return;
		}
		
		if(@filesize($TMP_FILE)<>$SIZE){
			build_progress("{corrupted} $filename {failed}...",110);
			if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
			return;
		}
	}
	
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squid=$unix->LOCATE_SQUID_BIN();
	build_progress("{extracting} $filename...",50);
	
	if(preg_match("#^hotspot#", $filename)){
		$AS_HOSTPOT=true;
	}
	
	
	if(preg_match("#^squid32#", $filename)){
		echo "Removing /lib/squid3\n";
		shell_exec("$rm -rf /lib/squid3");
		echo "Removing /usr/sbin/squid\n";
		shell_exec("$rm -f /usr/sbin/squid");
	}
	
	
	$ERROR=false;
	exec("$tar xvf $TMP_FILE -C / 2>&1",$EXT);
	while (list ($index, $ligne) = each ($EXT) ){
		echo "$ligne\n";
		if(preg_match("#(Cannot|recoverable|Error|exiting)#", $ligne)){$ERROR=true; }
		
	}
	if($ERROR){
		build_progress("{extraction_failed}...",110);
		sleep(4);
		if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
		return;
		
	}
	if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
	

	build_progress("{restart_services}: Artica Status...",50);
	
	system("/etc/init.d/artica-status reload --force");
	
	if(preg_match("#^squid32#", $filename)){
		@unlink(dirname(__FILE__)."/ressources/logs/squid.compilation.params");
		build_progress("{restart_services}: Squid-Cache...",51);
		system("/etc/init.d/squid restart --force");
		build_progress("{restart_services}: exec.squid.php...",51);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress("{restart_services}: exec.squidguard.php...",51);
		system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
		build_progress("{reconfiguring} {APP_UFDBGUARD}...",51);
		system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
		build_progress("{restarting} {APP_UFDBGUARD}...",51);
		system("/etc/init.d/ufdb restart");
		build_progress("{restart_services}: squid restart --force...",51);
		system("/etc/init.d/squid restart --force");
	}
	
	if(preg_match("#^pdnsc#", $filename)){
		$sock=new sockets();
		$sock->SET_INFO("EnablePDNS",1);
		$sock->SET_INFO("EnableDNSMASQ", 0);
		build_progress("{restart_services}: PowerDNS...",51);
		shell_exec("/etc/init.d/dnsmasq stop");
		shell_exec("/etc/init.d/pdns restart");
		shell_exec("/etc/init.d/pdns-recursor restart");
	}
	
	if(preg_match("#^nginx#", $filename)){
		build_progress("{restart_services}: NGINX...",51);
		system("/etc/init.d/nginx restart --force");
	}
	if(preg_match("#^ntopng#", $filename)){
		build_progress("{restart_services}: NTOPNG...",51);
		system("$php /usr/share/artica-postfix/exec.initslapd.php --ntopng --force");
		system("/etc/init.d/ntopng restart --force");
	}	

	if($AS_HOSTPOT){
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.initslapd.php --wifidog >/dev/null 2>&1 &");
	}
	build_progress("Learning the system...",52);
	system("/usr/share/artica-postfix/bin/process1 --force --verbose -6464646464");
	build_progress("{restart_services}: Auth TAIL...",52);
	system("/etc/init.d/auth-tail restart");
	build_progress("{restart_services}: Building collection...",60);
	system("/usr/share/artica-postfix/bin/process1 --force --verbose");
	build_progress("{restart_services}: MONIT...",60);
	system("/etc/init.d/monit restart");
	build_progress("{restart_services}: Artica Status...",60);
	system("/etc/init.d/artica-status restart --force");
	
	
	if(is_file($squid)){
		build_progress("{restart_services}: {schedules}...",70);
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	}
	build_progress("{restart_services}: init scripts...",80);
	system("$php /usr/share/artica-postfix/exec.initslapd.php --force");

	
	build_progress("{success}...",100);
	
	
	
}

function build_progress($text,$pourc){
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	$info=2;
	if($pourc>100){$info=0;}
	$sock=new sockets();
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	if($EnableArticaMetaClient==0){return;}
	meta_admin_mysql($info, "{$pourc}%: $text", null,__FILE__,__LINE__);
	if($pourc==100){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		system("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force >/dev/null 2>&1 &");
	}
	

}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		
		@file_put_contents($GLOBALS["DOWNLOAD_PROGRESS_FILE"], $progress);
		@chmod($GLOBALS["DOWNLOAD_PROGRESS_FILE"], 0777);
		$GLOBALS["previousProgress"]=$progress;
	}
}