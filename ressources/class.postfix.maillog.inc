<?php

class postfix_maillog_buffer{
	var $buffer=null;
	
	
	function postfix_maillog_buffer($buffer){
		$this->buffer=$buffer;
		if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
	}
	
	public function parse(){
		$buffer=$this->buffer;
		
		
		if(preg_match("#klms-milter:.+?started,\s+pid.*?([0-9]+).*?scanner.*?=(.*)#",$buffer,$re )){
			$this->email_events("Kaspersky Mail security Milter started pid {$re[1]} on {$re[2]}",$buffer,'klms');
			return true;
		}
		
		if(preg_match("#relay=none,\s+delay=.+?status=deferred.+?suspended:\s+connect to.+?\.0\.0\.1\]:10024:\s+Connection refused#",$buffer,$re )){
			$this->events("restart amavis !!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/restart-amavis";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>5){
				$this->email_events("Amavis is out of service, restart amavis","Postfix claim: $buffer\nAmavis is restarted","postfix");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix restart amavis >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}			
		}
		
		if(preg_match("#\/master\[.+?fatal:\s+bind\s+0\.0\.0\.0\s+port\s+25:\s+Address already in use#",$buffer,$re )){
			if(is_file("/usr/sbin/exim4")){
				if(is_file("/usr/bin/apt-get")){
				shell_exec("{$GLOBALS["NOHUP_PATH"]} /bin/kill -9 `/bin/pidof /usr/sbin/exim4` >/dev/null 2>&1 &");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /usr/bin/apt-get --purge --yes --force-yes --remove exim* >/dev/null 2>&1 &";
				$this->events("bind 0.0.0.0 port 25: Address already in use: $cmd ",__LINE__); 
				}
			}
		}
		
		if(preg_match("#postfix\/smtpd\[.+?:\s+error:\s+open database\s+(.+?)\.db:\s+No such file or directory#",$buffer,$re )){
			$file=$re[1];
			@file_put_contents($file, "\n");
			$cmd="{$GLOBALS["POSTMAP_PATH"]} hash:$file >/dev/null 2>&1";
			$this->events($cmd,__LINE__);
			shell_exec($cmd);
		}
		
		
		
		if(preg_match("#\/postfix-script\[.+?warning: not owned by root:\s+(.+)#",$buffer,$re )){
			$this->events("chown root {$re[1]}",__LINE__);
			@chown(trim($re[1]),"root");
			return true;
		}
		
		if(preg_match("#\/postfix-script\[.+?warning: not owned by group\s+(.+?):\s+(.+)#",$buffer,$re )){
			$this->events("chown group {$re[1]} ->{$re[2]}",__LINE__);
			@chgrp(trim($re[2]),trim($re[1]));
			return true;			
		}
		
		if(preg_match("#\/postfix-script\[.+?warning:\s+(.+?)\s+and\s+(.+?)\s+differ#",$buffer,$re )){
			$re[2]=trim($re[2]);
			$this->events("Symbolic link from  {$re[2]} -> {$re[1]} \"/bin/ln -s \"{$re[2]}\" \"{$re[1]}\"",__LINE__);
			@unlink(trim($re[1]));
			shell_exec("/bin/ln -s \"{$re[2]}\" \"{$re[1]}\"");
			return true;			
		}
		
		if(preg_match("#postfix-(.+?)\/smtpd\[.+?: warning: connect to Milter service unix:(.+?)\/greylist.sock:\s+Connection refused#",$buffer,$re)){
			$this->events("Instance {$re[1]} path={$re[2]}/greylist.sock -> Connection refused",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/restart-mgreylist-{$re[1]}";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.milter-greylist.php --start --hostname={$re[1]} >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");	
				return true;				
			}
			
			$this->events("Instance {$re[1]}:: {$TimeMin}Mn need to wait 5mn, keep the error...");
			return true;
		}
		
		
		if(preg_match("#postfix\/local.+?status=deferred\s+\(.*?alias database unavailable#",$buffer,$re )){
				$this->events("Master instance Alias database unavailable",__LINE__);
				$timefile="/etc/artica-postfix/croned.1/postfix-alias-database-unavailable";
				$TimeMin=$this->file_time_min($timefile);
				if($TimeMin>5){
					$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.hashtables.php --aliases >/dev/null 2>&1 &";
					$this->events($cmd);	
					shell_exec($cmd);
		 			@unlink($timefile);
					@file_put_contents($timefile,"#");	
					$this->email_events("alias database unavailable","Postfix claim $buffer,\naliases database will be ordered to be generated",'postfix');							
				}
		}
		
		
		if(preg_match('#postfix(.*?)\/smtpd\[.+?:\s+fatal: non-null host address bits in "(.+?)", perhaps you should use "(.+?)" instead#',$buffer,$re)){
			$instance=$re[1];
			if($instance==null){$instance="master";}
			if(substr($instance, 0,1)=="-"){$instance=substr($instance, 1,strlen($instance));}
			$badentry=$re[2];
			$goodentry=$re[3];
			$md5=md5("$instance$badentry$goodentry");
			$timefile="/etc/artica-postfix/croned.1/non-null-host-address.$md5";
			if($this->file_time_min($timefile)>5){
				$this->email_events("[POSTFIX MISCONFIGURATION]: Wrong netwwork rule $badentry from postfix instance `$instance`",
				"Please add  $goodentry instead \"$rule\" in $badentry in the network configuration\nArtica will try to fix this issue\n$buffer",'postfix');
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --badnettr \"$instance\" \"$badentry\" \"$goodentry\" >/dev/null 2>&1";
				$this->events("[POSTFIX MISCONFIGURATION]: -> $cmd",__LINE__);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");					
				return true;
			}
			return true;
			
		}
		
		if(preg_match("#trivial-rewrite\[.+? fatal: open database\s+(.+?)\.db: No such file or directory#",$buffer,$re)){
			@file_put_contents($re[1], "\n");
			$cmd="{$GLOBALS["POSTMAP_PATH"]} hash:{$re[1]}";
			$this->events($cmd,__LINE__);
			shell_exec($cmd);
			return true;
		}
		
		
		if(strpos($buffer, "ARTICA_XNOTIFY")>5){
			if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+(.+?):\s+reject:\s+.+?\s+(.+?)\s+from.+?\[(.+?)\];\s+from=<(.+?)>\s+to=<(.+?)>.+?:\s+([0-9\.]+)\s+(.+?)ARTICA_XNOTIFY#",$buffer,$re)){
				$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$message_id=$re[3];
				$line=$re[4];$server=$re[5];$from=$re[6];$to=$re[7];$code=$re[8];$text=$re[9];
				$GLOBALS["maillog_tools"]->event_messageid_rejected($message_id,"headers rule",$server,$to);
				$text="$buffer\n\n---------\nMessage from $from and to $to was refused from the service $service and instance $mails\nmatching $line with error code $code ($text)";
				$this->events("ARTICA_XNOTIFY -> email event from=<$from> to=<$to> `$line`",__LINE__);
				$this->email_events("[SMTP headers rules]: refused from $from",$text,'postfix');
				return true;			
			}
		}
		
		if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+warning: regexp map\s+(.+?), line\s+[0-9]+:\s+Invalid preceding regular expression#",$buffer,$re)){
			$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$file=$re[3];$line=$re[4];
			$timefile="/etc/artica-postfix/croned.1/regexp.map.".md5("$file$line");
			if($this->file_time_min($timefile)>5){
				$f=explode("\n",@file_get_contents($file));$rule=$f[$line];
				$this->email_events("[SMTP headers rules]: Wrong rule line $line","Please check the rule \"$rule\" in $file",'postfix');
				$this->events("error $rule  in file $file",__LINE__);
				@unlink($timefile);@file_put_contents($timefile,"#");				
			}
			return true;			
		}
		
		if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+(.+?):\s+reject:\s+.+?\s+(.+?)\s+from.+?\[(.+?)\];\s+from=<(.+?)>\s+to=<(.+?)>.+?:\s+([0-9\.]+)\s+(.+?)#",$buffer,$re)){$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$message_id=$re[3];$line=$re[4];$server=$re[5];$from=$re[6];$to=$re[7];$code=$re[8];$text=$re[9];$GLOBALS["maillog_tools"]->event_messageid_rejected($message_id,"headers rule",$server,$to);return true;}
		if(preg_match("#postfix(.*?)\/master\[([0-9]+)\]: terminating on signal#", $buffer,$re)){$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$this->email_events("Postfix PID {$re[2]} $mails was stopped","$buffer",'postfix');return true;}
		if(preg_match("#dkim-filter\[([0-9]+)\].+?Sendmail DKIM Filter v([0-9\.]+) starting#", $buffer,$re)){$this->email_events("DKIM Filter version {$re[2]} was successfully started PID {$re[1]}","$buffer",'postfix');return true;}
		if(preg_match("#smtpd\[.+?warning: connect to Milter service unix:/var/run/dkim-milter/dkim-milter.sock: Connection refused#",$buffer,$re)){shell_exec("{$GLOBALS["CHOWN"]} postfix:postfix /var/run/dkim-milter/dkim-milter.sock >/dev/null 2>&1");return true;}
		
		
		if(preg_match("#bounce\[.+?fatal: open /etc/postfix/bounce.template.cf: No such file or directory#i",$buffer,$re)){
			$this->events("tlsmgr: /etc/postfix/bounce.template.cf  No such file or directory!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/bounce.template.cf.error";
			if($this->file_time_min($timefile)>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --notifs-templates-force >/dev/null 2>&1 &";
				$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}
			return true;			
		}
		
		
		if(preg_match("#postfix\/tlsmgr\[.+?warning: request to update table btree:.+?smtpd_scache in non-postfix directory#i",$buffer,$re)){
			$this->events("tlsmgr: non-postfix directory!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/tlsmgr.non-postfix-dir";
			if($this->file_time_min($timefile)>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sasl >/dev/null 2>&1 &";
				$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}
			return true;
		}
		
		
		if(preg_match("#([0-9\:]+)\s+.+?\s+postfix-(.+?)\/smtpd\[.+?fatal: open database\s+(.+?)\.db: No such file#i",$buffer,$re)){
			$fileDB=trim($re[1]).".db";
			$fileSource=trim($re[1]);
			$md5=md5($fileSource);
			if(!is_file($fileSource)){$this->events("Create null file $fileSource",__LINE__);@file_put_contents($fileSource, "\n");}
			$timefile="/etc/artica-postfix/croned.1/$md5.db.no.such.file";
			if($this->file_time_min($timefile)>5){
		 		$postmap=$GLOBALS["CLASS_UNIX"]->find_program('postmap');
		 		$cmd="{$GLOBALS["NOHUP_PATH"]} $postmap hash:$fileSource >/dev/null 2>&1 &";
		 		$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");
		 	}
		 	return true;
		}
		
		
		
		 if(preg_match("#postfix.+?\]: warning: database .+? is older than source file (.+)#",$buffer,$re)){
		 	$filesource=trim($re[1]);
		 	$md5=md5($filesource);
		 	if(!is_file($filesource)){$this->events("$filesource no such file (is older than source file)",__LINE__);return true;}
		 	$timefile="/etc/artica-postfix/croned.1/$md5.older.than.source";
		 	if($this->file_time_min($timefile)>5){
		 		$postmap=$GLOBALS["CLASS_UNIX"]->find_program('postmap');
		 		$cmd="{$GLOBALS["NOHUP_PATH"]} $postmap hash:$filesource >/dev/null 2>&1 &";
		 		$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");
		 	}
		 	return true;
		 }
		
		if(preg_match("#[0-9]+\s+[0-9\:]+.+?\s+(.+?)\/postsuper\[.+?fatal: scan_dir_push: open directory.+?: Permission denied#",$buffer,$re)){
			$instance=$re[1];
			$timefile="/etc/artica-postfix/croned.1/postsuper.$instance.permission.denied";
			$this->events("Permission denied $instance",__LINE__);
			if($this->file_time_min($timefile)>5){
				$postfixbin=$GLOBALS["CLASS_UNIX"]->find_program('postfix');
					$cmd="{$GLOBALS["NOHUP_PATH"]} $postfixbin -c /etc/$instance set-permissions  >/dev/null 2>&1 &";
					$this->events("Permission denied $cmd",__LINE__);
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,"#");
				}
				return true;
			}
		
		if(preg_match("#postfix-(.+?)\/master\[.+? fatal: open lock file pid\/master.pid: unable to set exclusive lock: Resource temporarily unavailable#i",$buffer,$re)){
			$instance=$re[1];
			$timefile="/etc/artica-postfix/croned.1/master.$instance.master.pid.lock";
			$this->events("$instance unable to set exclusive lock on master.pid",__LINE__);
			if($this->file_time_min($timefile)>5){
				$postmulti=$GLOBALS["CLASS_UNIX"]->find_program('postmulti');
					$cmd="{$GLOBALS["NOHUP_PATH"]} $postmulti -i $instance -p start >/dev/null 2>&1 &";
					$this->events(" exclusive lock on master.pid $cmd",__LINE__);
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,"#");
				}
				return true;
			}	
		

		if(preg_match("#fatal: master_spawn: exec\s+(.+?)\/postscreen: No such file or directory#",$buffer,$re)){
			$postscreen=$GLOBALS["CLASS_UNIX"]->LOCATE_POSTFIX_POSTSCREEN();
			$timefile="/etc/artica-postfix/croned.1/postscreen.no.such.file";
			$this->events("Missing Postscreen binary..",__LINE__);
			if($this->file_time_min($timefile)>10){
				if(is_file($postscreen)){
					if(is_file($GLOBALS["LN_BIN"])){$required=$re[1]."/postscreen";$this->events("Do symbolic link from $postscreen to $required",__LINE__);shell_exec("{$GLOBALS["LN_BIN"]} -s $postscreen $required");}
				}else{
					$this->email_events("Could not find PostScreen binary",
				"Postfix claim \n$buffer\nArtica is unable to find postscreen binary","postfix");
				@file_put_contents($timefile,"#");
				}
			}
		return true;
		}	
		
		// postfix/tlsmgr[4476]: fatal: open database /var/lib/postfix/smtpd_tls_cache.db: Invalid argument
		if(preg_match("#tlsmgr\[.+?fatal: open database\s+(.+?):\s+Invalid argument#", $buffer)){
			$timefile="/etc/artica-postfix/croned.1/".md5($re[1]).".Invalid.argument";
			$this->events("Database {$re[1]} Failed",__LINE__);
			if($this->file_time_min($timefile)>5){
				$this->events("Database {$re[1]} Failed -> Remove it",__LINE__);
				@unlink($re[1]);
				@file_put_contents($timefile, time());
				shell_exec(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
			}
			return true;
		}

		if(preg_match("#warning: connect to Milter service unix:(.+?)spamass.sock: No such file or directory#",$buffer,$re)){
				$this->events("{$re[1]}/spamass.sock Error ",__LINE__);	
				$this->MilterSpamAssassinError($buffer,"$re[1]/spamass.sock");
				return true;
		}
		
		
		if(preg_match("#postfix\/showq\[.+?fatal: open active .+?: Permission denied#",$buffer,$re)){
				$timefile="/etc/artica-postfix/croned.1/showq.Permission.denied";
				$this->events("showq: Permission error",__LINE__);	
				if($this->file_time_min($timefile)>5){
					$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /bin/chown -R postfix:postfix /var/spool/mail &&  {$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");
					$this->events($cmd,__LINE__);
					@unlink($re[1]);
					shell_exec($cmd);
					@file_put_contents($timefile, time());	 
				}
			return true;
		}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	}
	
	
	private function events($text,$line){
		$filename=basename(__FILE__);
		$logFile="/var/log/artica-postfix/postfix-logger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){return;}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text (line $line)",$logFile);		
	}	
	
	
	private function MilterSpamAssassinError($buffer,$socket){
		$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
		if($this->file_time_min($file)<15){
			$this->events("Must wait 15mn",__LINE__);
		}	
		$this->email_events("spamassin-milter socket error: $socket","Postfix claim \"$buffer\", Artica will reload Postfix and compile new Postfix settings",'postfix');
		$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.policyd-weight.php --spamass-milter >/dev/null 2>&1 &";
		$this->events($cmd,__LINE__);
		shell_exec($cmd);
		@unlink($file);
		@file_put_contents($file,"#");	
	}

	
	
	
	
   private function email_events($subject,$text,$context){
	 if(!isset($GLOBALS["CLASS_UNIX"])){return;}
	 $GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}

	private function file_time_min($path){
		if(!is_file($path)){if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}return 100000;}
		$last_modified = filemtime($path);
	 	$data1 = $last_modified;
	 	$data2 = time();
		$difference = ($data2 - $data1); 	 
		return round($difference/60);	 
	}	
	
	
}