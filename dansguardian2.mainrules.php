<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$dirname=dirname(__FILE__);
if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}

include_once($dirname.'ressources/class.templates.inc');
include_once($dirname.'/ressources/class.ldap.inc');
include_once($dirname.'/ressources/class.users.menus.inc');
include_once($dirname.'/ressources/class.squid.inc');
include_once($dirname.'/ressources/class.ActiveDirectory.inc');

if(isset($argv[1])){
	if($GLOBALS["AS_ROOT"]){
		if($argv[1]=="rules-toolbox-left"){rules_toolbox_left();die();}
	}
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["main-rules"])){rules();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rules-toolbox"])){rules_toolbox();exit;}
if(isset($_GET["dansguardian-status"])){status_left();exit;}
if(isset($_POST["DansGuardianDeleteMainRule"])){delete_rule();exit;}
if(isset($_GET["rules-table"])){rules_table();exit;}
if(isset($_POST["rule-move"])){rule_move();exit;}
if(isset($_GET["rules-table-list"])){rules_table_list();exit;}
if(isset($_GET["rules-toolbox-left"])){rules_toolbox_left();exit;}
if(isset($_POST["EnableUFDB2"])){EnableUFDB2();exit;}
if(isset($_GET["CompileUfdbGuardRules"])){CompileUfdbGuardRules_js();exit;}
if(isset($_GET["CompileUfdbGuardRules-popup"])){CompileUfdbGuardRules_popup();exit;}
if(isset($_GET["CompileUfdbGuardRules-perform"])){CompileUfdbGuardRules_perform();exit;}
if(isset($_GET["CompileUfdbGuardRules-check"])){CompileUfdbGuardRules_check();exit;}
if(isset($_GET["UfdbguardEvents"])){UfdbguardEvents_js();exit;}
if(isset($_GET["UfdbguardEvents-popup"])){UfdbguardEvents_popup();exit;}
if(isset($_GET["section_ufdbguard_advanced_options"])){section_ufdbguard_advanced_options();exit;}


tabs();


function CompileUfdbGuardRules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$compile_rules=$tpl->_ENGINE_parse_body("{webfilter}::{compile_rules}");
	echo "YahooWinBrowse('700','$page?CompileUfdbGuardRules-popup=yes','$compile_rules',true)";
	
}

function UfdbguardEvents_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$compile_rules=$tpl->_ENGINE_parse_body("{webfilter}::{service_events}");
	echo "YahooWinBrowse('840','$page?UfdbguardEvents-popup=yes','$compile_rules',true)";	
	
}

function CompileUfdbGuardRules_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='CompileUfdbGuardRules-$t' style='width:98%;min-height:450px;overflow:auto' class=form></div>
	<script>
		LoadAjax('CompileUfdbGuardRules-$t','$page?CompileUfdbGuardRules-perform=yes',true);
	</script>
	
	";
	
	echo $html;
}

function UfdbguardEvents_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='UfdbguardEvents-$t' style='width:98%;min-height:450px;overflow:auto' class=form></div>
	<script>
		LoadAjax('UfdbguardEvents-$t','ufdbguard.sevents.php',true);
	</script>
	
	";
	
	echo $html;	
	
	
}

function CompileUfdbGuardRules_perform(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock->getFrameWork("squid.php?ufdbguard-compile-smooth-tenir=yes&MyCURLTIMEOUT=300");
	$t=time();
	$html="
	<input type='hidden' value='0' id='stop-refresh-$t'>
	<div id='$t'><center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}</center></div>
	<script>
		function CompileUfdbGuardRulesCheck$t(){
			if(!document.getElementById('stop-refresh-$t')){return;}
			var StopRefresh=document.getElementById('stop-refresh-$t').value;
			if(StopRefresh==1){return;}
			if(!YahooWinBrowseOpen()){return;}
			LoadAjax('$t','$page?CompileUfdbGuardRules-check=yes&t=$t',true);
		}
		
		setTimeout('CompileUfdbGuardRulesCheck$t()',3000);
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

	//
	
	
	
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		echo "<div><code style='font-size:12px'>$ligne</code></div>";
		
	}
}

function CompileUfdbGuardRules_check(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$StopRefresh=$tpl->_ENGINE_parse_body("{StopRefresh}");
	$t=$_GET["t"];
	if(!is_file("ressources/logs/web/compile.ufdbguard.interface.txt")){
		echo $tpl->_ENGINE_parse_body("<center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}<img src='img/ajax-loader.gif' style='margin:20px'></center>
		<script>
			setTimeout('CompileUfdbGuardRulesCheck$t()',3000);
		</script>
		
		");
		return;
		
	}
	
	$datas=file("ressources/logs/web/compile.ufdbguard.interface.txt");
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=str_replace("\r", "", $ligne);
		if($ligne==null){continue;}
		echo "<div><code style='font-size:12px'>$ligne</code></div>";
		
	}

	
	echo
	
	"
	<center><span id='img-$t'><img src='img/ajax-loader.gif' style='margin:20px'></span>
		<center>
			<span id='bt-$t'>". button("$StopRefresh","document.getElementById('stop-refresh-$t').value=1;document.getElementById('img-$t').innerHTML='';document.getElementById('bt-$t').innerHTML='';","14px")."</span>
		</center>
	</center>
	<script>
			setTimeout('CompileUfdbGuardRulesCheck$t()',8000);
	</script>";
	
	
}

function section_ufdbguard_advanced_options(){
	$array["rewrite-rules"]='{rewrite_rules}';
	$array["section_basic_filters-terms"]='{terms_groups}';
	$array["others"]='{others}';
	
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	

	
	$fontsize=16;
	if(count($array)>6){$fontsize=11.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="rewrite-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"ufdbguard.rewrite.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="others"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"dansguardian2.php?ufdbguard-options=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="section_basic_filters-terms"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.terms.groups.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
				
		}
	

	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html= build_artica_tabs($html,'main_ufdbguard_advanced');
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;
	
	
}


function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["ufdbguard-status"]="{service_status}";
	$array["main-rules"]='{rules}';
	$array["section_ufdbguard_advanced_options"]='{advanced_options}';
	$array["your_categories"]='{your_categories}';
	$array["databases"]='{all_categories}';
	//$array["ufdbguard"]='{service_parameters}';
	$array["error-page"]='{error_page}';
	$array["service"]='{service2}';
	
	
	if(!$users->APP_UFDBGUARD_INSTALLED){
		unset($array["section_basic_filters-terms"]);
		unset($array["section_ufdbguard_advanced_options"]);
		unset($array["databases"]);
		unset($array["ufdbguard"]);
		unset($array["ufdbguard-status"]);
		unset($array["rewrite-rules"]);
	}
	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if($UseRemoteUfdbguardService==1){
		$array=array();
		$array["client_parameters"]="{client_parameters}";
	}
	
	$fontsize=18;
	if(count($array)>8){$fontsize=16;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rewrite-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"ufdbguard.rewrite.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="categories"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"dansguardian2.databases.php?categories=yes&maximize=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="client_parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"ufdbguard.php?ufdbclient=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}

		
		
		if($num=="error-page"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.php?tabs=yes&_=1410377344863\" style='font-size:{$fontsize}px;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}	

		//if($num=="databases"){
		//	$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php\" 
			//		style='font-size:{$fontsize}px;font-weight:normal'><span>$ligne</span></a></li>\n");
			//continue;
		//}		
		
		if($num=="databases"){
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php?categories=yes&maximize=yes\"
				style='font-size:{$fontsize}px;font-weight:normal'><span>$ligne</span></a></li>\n");
				continue;
		}
		
		if($num=="section_basic_filters-bandwith"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.bandwith.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}	
		if($num=="your_categories"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"dansguardian2.databases.php?categories=&middlesize=yes&minisize-middle=yes&OnlyPersonal=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="service"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.php?tabs=yes\"
					style='font-size:{$fontsize}px;font-weight:normal'><span>$ligne</span></a></li>\n");
					continue;
		}		
		
		
		
		

		
		if($num=="ufdbguard"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"dansguardian2.php?ufdbguard=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="section_basic_filters-time"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.connection-time.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="section_basic_filters-terms"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.terms.groups.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}		

		if($num=="c-icap-dnsbl"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"c-icap.dnsbl2.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
				
		if($num=="ufdbguard-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.status.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html= build_artica_tabs($html,'main_dansguardian_mainrules');
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;

}







function rules_ufdb_not_installed(){
	$tpl=new templates();
	$page=CurrentPageName();	
	
	$title=$tpl->javascript_parse_text('{ERROR_NOT_INSTALLED_REDIRECT}');
		$html="
		<center>
		<table style='width:80%' class=form>
		<tr>
			<td valign='middle' width=1%><img src='img/software-remove-128.png'></td>
			<td valign='top' width=99%><div style='font-size:18px'>{ERROR_UFDBGUARD_NOTINSTALLED}</div>
			<div style='float:right'>". imgtootltip("48-refresh.png","{refresh}","RefreshTab('main_dansguardian_mainrules');")."</div>
			<p style='font-size:14px'>{ufdbguard_simple_intro}
			</p>
			<div style='text-align:right'><hr>". button("{install}","InstallUfdbGuard()",18)."</div>
		</td>
		</tr>
		</table>
		</center>
			<script>
				function InstallUfdbGuard(){
					Loadjs('setup.index.progress.php?product=APP_UFDBGUARD&start-install=yes');
				}
			</script>";
		echo $tpl->_ENGINE_parse_body($html);
	
}


function rules(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	if(!$users->APP_UFDBGUARD_INSTALLED){rules_ufdb_not_installed();return;}
	
	$html="<div id='rules-table'></div>
	<script>
		LoadAjax('rules-table','$page?rules-table=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function rules_toolbox_left(){
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html";
	if(isset($_GET["RemoveCache"])){@unlink($cachefile);}
/*	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',"<p class=text-error>");
	ini_set('error_append_string',"</p>");
	
*/	
	if(!$GLOBALS["AS_ROOT"]){
		if(is_file($cachefile)){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body(@file_get_contents($cachefile));
			return;
		}
	}else{
		include_once(dirname(__FILE__).'/framework/class.unix.inc');
		include_once(dirname(__FILE__).'/framework/frame.class.inc');
		$unix=new unix();
		if($unix->file_time_min($cachefile)<5){return;}
	}
	
	
	
	$updateutility=null;
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$Computers=$q->COUNT_ROWS("webfilters_nodes");
	
	if(!$q->TABLE_EXISTS("webfilter_certs")){$q->CheckTables();}
	if($q->COUNT_ROWS("webfilter_certs")==0){$q->fill_webfilter_certs();}
	
	$Computers=numberFormat($Computers,0,""," ");
	$sock=new sockets();
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	

	

	$tablescat=$q->LIST_TABLES_CATEGORIES();
	$CountDeCategories=numberFormat(count($tablescat),0,""," ");
	
	$todayblocked=date("Ymd")."_blocked";
	$CountDeBlocked=$sql=$q->COUNT_ROWS($todayblocked);
	$CountDeBlocked=numberFormat($CountDeBlocked,0,""," ");
	
	$disable_service=$tpl->_ENGINE_parse_body("{disable_service}");
	
	$datasUFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($datasUFDB["DebugAll"])){$datasUFDB["DebugAll"]=0;}
	$update_parameters=$tpl->_ENGINE_parse_body("{update_parameters}");
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	$UFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}
	
	if($UseRemoteUfdbguardService==0){
		if($UFDB["UseRemoteUfdbguardService"]==1){
			$sock->SET_INFO("UseRemoteUfdbguardService", 1);
			$UseRemoteUfdbguardService=1;
		}
	}
	
	
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}	
	
	
	if($users->UPDATE_UTILITYV2_INSTALLED){
		$updateutility="	<tr>
		<td valign='middle' width=1%><img src='img/kaspersky-update-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.UpdateUtility.php')\" nowrap>UpdateUtility</td>
			</tr>
			</table>
		</td>
	</tr>";
		
	}
	
	$ufdbgverb_icon="ok24-grey.png";
	$ufdbgverb_txt="OFF";
	
	
	if($datasUFDB["DebugAll"]==1){
		$ufdbgverb_icon="ok32.png";
		$ufdbgverb_txt="ON";
	}
	
	
	if($EnableUfdbGuard==1){
		$DisableUfdbGuard="
			<tr>
		<td valign='middle' width=1%><img src='img/ok32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='middle' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.disableUfdb.php')\" nowrap><b><span style='font-size:13px;text-decoration:underline'>$disable_service</td>
			</tr>
			</table>
		</td>
		</tr>		
		";
		
	}
	
	if($UseRemoteUfdbguardService==1){	$DisableUfdbGuard=null;}
	
	$html="
	<table style='width:98%' class=form>
	$DisableUfdbGuard
	<tr>
		<td valign='middle' width=1%><img src='img/computer-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='middle' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.nodes.php',true)\" nowrap><b><span style='font-size:13px;text-decoration:underline'>$Computers</span></b><span style='font-size:13px'> {computers}</td>
			</tr>
			</table>
		</td>
	</tr>";
	

	
	
	$html=$html."
			
	
	<tr>
		<td valign='middle' width=1%><img src='img/check-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.tests.php')\" nowrap>
				<span style='font-size:13px;text-decoration:underline'>{verify_rules}</td>
			</tr>
			</table>
		</td>
	</tr>		
	
	
	<tr>
		<td valign='middle' width=1%><img src='img/loupe-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.category.tests.php')\" nowrap><span style='font-size:13px;text-decoration:underline'>{test_categories}</td>
			</tr>
			</table>
		</td>
	</tr>			
	
	
	
";
if($SquidPerformance<3){
$html=$html."
	
<tr>
	<td valign='middle' width=1%><img src='img/group-red-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.blocked.events.php?js=yes')\" nowrap><b><span style='font-size:13px;text-decoration:underline'>$CountDeBlocked</span></b><span style='font-size:13px'> {blocked_requests}</td>
			</tr>
			</table>
		</td>
	</tr>	
";	
}
	
if($UseRemoteUfdbguardService==0){
	$html=$html."<tr>
		<td valign='middle' width=1%><img src='img/service-restart-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.php?force-reload-js=yes')\" nowrap><span style='font-size:13px;text-decoration:underline'>{reload_service}</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td valign='middle' width=1%><img src='img/events-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.sevents.php?js=yes')\" nowrap><span style='font-size:13px;text-decoration:underline'>{service_events}</td>
			</tr>
			</table>
		</td>
	</tr>			
	<tr>
		<td valign='middle' width=1%><img src='img/$ufdbgverb_icon'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline;text-transform:capitalize' 
				OnClick=\"javascript:Loadjs('ufdbguard.debug.php')\" nowrap><span style='font-size:13px;text-decoration:underline'>{debug} [$ufdbgverb_txt]</td>
			</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td valign='middle' width=1%><img src='img/32-stop.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squidguardweb.php')\" nowrap><span style='font-size:13px;text-decoration:underline'>{banned_page_webservice}</td>
			</tr>
			</table>
		</td>
	</tr>
	";
}
$html=$html."	
	<tr>
		<td valign='middle' width=1%><img src='img/32-categories.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.categories.php')\" nowrap><strong style='font-size:13px;text-decoration:underline'>$CountDeCategories&nbsp;{categories}</strong></td>
			</tr>
			</table>
		</td>
	</tr>	
";

if($UseRemoteUfdbguardService==0){
	
$html=$html."
	<tr>
		<td valign='middle' width=1%><img src='img/script-32.png'></td>
		<td valign='middle' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='middle' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.conf.php');\" nowrap><span style='font-size:13px;text-decoration:underline'>{config_status}</td>
			</tr>
			</table>
		</td>
	</tr>	
	$updateutility	
";
}
$html=$html."

	</table> 
	
	
	";
	

	
	if($EnableUfdbGuard==1){
		if($UseRemoteUfdbguardService==0){
			if(!$users->CORP_LICENSE){
				echo $tpl->_ENGINE_parse_body("
				<div id='$t' style='width:90%;margin-bottom:20px' class=form>
					<table style='width:100%;'>
					<tr>
					<td valign='top' width=99%>
						<div style='font-size:14px;color:#CC0A0A'>
						<img src='img/info-48.png' style='float:left;margin:3px'>
						<span style='font-size:11px'>{warn_ufdbguard_no_license}</span>
						<table style='width:100%'>
						<tr>
							<td width=1%><img src='img/arrow-right-16.png'></td>
							<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica.license.php');\" 
							style='text-decoration:underline;color:black'>{artica_license}</a></td>
							</tr>
							</table>
						</div>
					</td>
					</tr>
					</table>
					</div>");			
				}
			
			$ini=new Bs_IniHandler();
			$sock=new sockets();
			$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?ufdb-ini-status=yes')));
			$restartlocalservice="Loadjs('system.services.cmd.php?APPNAME=APP_UFDBGUARD&action=restart&cmd=%2Fetc%2Finit.d%2Fufdb&id=ed8cebc50034e96ed26a4d3cb953403f&appcode=APP_UFDBGUARD');";
			
			$redirector_file="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
			$redirectors_array=unserialize(@file_get_contents($redirector_file));
			if(count($redirectors_array)>1){
				$redirectors_count=count($redirectors_array);
				if(strlen($redirectors_count)==1){$redirectors_count="0$redirectors_count";}
				$redirectors_text="
				<tr>
					<td width=1%><strong style='font-size:14px;color:#04A910'>$redirectors_count</strong></td>
					<td width=99%><a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('squid.redirectors.php');\"
						style='text-decoration:underline;color:black;font-size:14px'>{redirectors}</a>
					</td>
				</tr>";
			}
			
			
			if($ini->_params["APP_UFDBGUARD"]["running"]==0){
				
				$echo1=$tpl->_ENGINE_parse_body(toolp("<span style='font-size:14px;color:#ED0D0D'>{warn_ufdbguard_stopped}</span>", "
						<span style='font-size:14px;color:#ED0D0D'>{warn_ufdbguard_stopped}</span>
									<table style='width:100%'>
										<tr>
											<td width=1%><img src='img/arrow-right-16.png'></td>
											<td width=99%><a href=\"javascript:blur();\" 
								OnClick=\"javascript:$restartlocalservice\"
								style='text-decoration:underline;color:black;font-size:14px'>{start_service}</a></td>
										</tr>
						$redirectors_text
											<tr>
											<td width=1%><img src='img/16-refresh.png'></td>
											<td width=99%><a href=\"javascript:blur();\" 
								OnClick=\"javascript:LoadAjaxTiny('rules-toolbox-left','$page?rules-toolbox-left=yes&RemoveCache=yes');\"
								style='text-decoration:underline;color:black;font-size:14px'>{refresh}</a></td>
										</tr>
									</table>",null,
						"error-48.png"));
		
		
			}else{
				
				
				$echo1= $tpl->_ENGINE_parse_body(toolp("<span style='font-size:14px;color:#04A910;'>{service_running}</span>", "
						<p style='font-size:14px'>
						{running_since}:&nbsp;{$ini->_params["APP_UFDBGUARD"]["uptime"]}<br>
						{memory}:&nbsp;".FormatBytes($ini->_params["APP_UFDBGUARD"]["master_memory"])."</strong>
						
						
						</p>
						<table style='width:100%'>
						<tr>
						<td width=1%><img src='img/arrow-right-16.png'></td>
						<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:$restartlocalservice\"
						style='text-decoration:underline;color:black;font-size:14px'>{restart_service}</a></td>
						</tr>
						$redirectors_text
											<tr>
											<td width=1%><img src='img/16-refresh.png'></td>
											<td width=99%><a href=\"javascript:blur();\" 
								OnClick=\"javascript:LoadAjaxTiny('rules-toolbox-left','$page?rules-toolbox-left=yes&RemoveCache=yes');\"
								style='text-decoration:underline;color:black;font-size:14px'>{refresh}</a></td>
										</tr>						
						</table>",null,
						"ok-42.png"));

				
				
			}
			
		}
		
		
		if($UseRemoteUfdbguardService==1){
			
			$server=$UFDB["remote_server"];
			$port=$UFDB["remote_port"]=3977;
			if(!@fsockopen($server, $port, $errno, $errstr, 1)){
				$echo1=$echo1. $tpl->_ENGINE_parse_body("
						<div id='$t' style='width:90%;margin-bottom:20px' class=form>
						<table style='width:100%'>
						<tr>
						<td valign='top' width=99%>
						<div style='font-size:14px;color:#CC0A0A'>
							<img src='img/error-48.png' style='float:left;margin:3px'>
							<strong style='font-size:11px'>{warn_ufdbguard_remote_error}</strong>
							<p style='font-size:11px'>{server}:&laquo;$server&raquo;:$port<br>{error} $errno $errstr</p>
						<table style='width:100%'>
						<tr>
						<td width=1%><img src='img/arrow-right-16.png'></td>
						<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.php?client-js=yes');\"
						style='text-decoration:underline;color:black'>{client_parameters}</a></td>
						</tr>
						</table>
						</div>
						</td>
						</tr>
						</table>
						</div>");
			}else{
				$echo1=$echo1. $tpl->_ENGINE_parse_body("
						<div id='$t' style='width:90%;margin-bottom:20px' class=form>
						<table style='width:100%'>
						<tr>
						<td valign='top' width=99%>
						<div style='font-size:14px;color:black'>
						<img src='img/48-network-server-star.png' style='float:left;margin:3px'>
						<strong style='font-size:11px'>{warn_ufdbguard_remote_use}</strong>
						<p style='font-size:11px'>{server}:&laquo;$server&raquo;:$port</p>
						<table style='width:100%'>
						<tr>
						<td width=1%><img src='img/arrow-right-16.png'></td>
						<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.php?client-js=yes');\"
						style='text-decoration:underline;color:black'>{client_parameters}</a></td>
						</tr>
						</table>
						</div>
						</td>
						</tr>
						</table>
						</div>");
				
			}
			
		}else{
			
			
				
			
			
		}
		
		
		
		
		
		
		if($EnableWebProxyStatsAppliance==0){
			if(trim($sock->getFrameWork("squid.php?isufdbguard-squidconf=yes"))<>"OK"){
				$echo1=$echo1. $tpl->_ENGINE_parse_body("
						<div id='$t-2' style='width:90%;margin-bottom:20px' class=form>
						<table style='width:100%'>						
						<tr>
						<td valign='top' width=99%>
						<div style='font-size:14px;color:#CC0A0A'>
						<img src='img/info-48.png' style='float:left;margin:3px'>
						<span style='font-size:11px'>{warn_ufdbguard_not_squidconf}</span>
						<table style='width:100%'>
						<tr>
						<td width=1%><img src='img/arrow-right-16.png'></td>
						<td width=99%><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.compile.progress.php');\"
			style='font-size:12px;text-decoration:underline'>{APP_SQUID}:{reconfigure}</a></td>
						</tr>
						</table>
						</div>
						</td>
						</tr>
						</table>
						</div>");			
				
				}
		
		}
		
		
		
		
	}

	
	
	$echo1=$echo1. $tpl->_ENGINE_parse_body($artica_databases);
	
	$t=time();
	
	$users=new usersMenus();
	$PERF=true;
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE)/1024);
	$CPU=$users->CPU_NUMBER;
	if($serverMem<2000){$PERF=FALSE;}
	if($CPU<2){$PERF=FALSE;}
	
	if(!$PERF){
		$echo1=$echo1. $tpl->_ENGINE_parse_body("
		<div id='$t' style='width:90%;margin-bottom:20px' class=form>
		<table style='width:100%'>
		<tr>
		<td valign='top' width=99%>
		<div style='font-size:14px;color:#CC0A0A'>
		<img src='img/warning-panneau-42.png' style='float:left;margin:3px'>
		<span style='font-size:11px'>{warn_no_performance_minimal}</span>
		</div>
		</td>
		</tr>
		</table>
		</div>");	
		
		
	}
	$t=time()+1;
	
	
	$CICAPEnableSquidGuard=intval($sock->GET_INFO("CICAPEnableSquidGuard"));
	
	if($CICAPEnableSquidGuard==0){
		if($EnableUfdbGuard==0){
			$echo1=$echo1. $tpl->_ENGINE_parse_body("
			<div id='$t' style='width:90%;margin-bottom:20px' class=form>
		<table style='width:100%'>
		<tr>
		<td valign='top' width=99%>
			<div style='font-size:14px;color:#CC0A0A'>
			<img src='img/warning-panneau-42.png' style='float:left;margin:3px'>
			<span style='font-size:11px'>{warn_ufdbguard_not_activated_explain}</span>
			<table style='width:100%'>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:EnableUFDB2();\" 
				style='text-decoration:underline;color:black'>{activate_webfilter_engine}</a></td>
				</tr>
				</table>
			</div>
		</td>
		</tr>
		</table>
		</div>
		<script>
		function EnableUFDB2(){
		  Loadjs('squid.EnableUfdb.php');
		 }	
		
		</script>
		
		");
			
		}
	}
	
	
	if($CICAPEnableSquidGuard==1){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `status` FROM c_icap_services WHERE ID=12"));
		$status=$ligne["status"];
		$STATUS_ARRAY[0]="32-green-grey.png";
		$STATUS_ARRAY[1]="32-green.png";
		$STATUS_ARRAY[2]="32-red.png";
		$STATUS_ARRAY[3]="warning32.png";
		$echo1=$echo1. $tpl->_ENGINE_parse_body("
				<div id='$t' style='width:90%;margin-bottom:20px' class=form>
					<table style='width:100%'>
						<tr>
							<td valign='middle' width=1%>
							<img src='img/{$STATUS_ARRAY[$ligne["status"]]}' style='float:left;margin:3px'>
							</td>
							<td valign='middle' width=98%>
							<div style='font-size:14px;color:black'>
									<span style='font-size:11px'>{icap_webfiltering_status}</span>
							
							</div>
							</td>
						</tr>
					</table>
				</div>
		");
	}
	
	
	
	@file_put_contents($cachefile, $echo1.$html);
	@chmod($cachefile,0755);
	echo $tpl->_ENGINE_parse_body($echo1.$html);
	
}

function toolp($title,$text,$js,$img,$size=289){
	
	$img_id=md5($img.time());
	return "

	<div style='width:93%;min-height:110px' class=form 		
		OnMouseOver=\"javascript:this.className='formOver';this.style.cursor='pointer';\" 
		OnMouseOut=\"javascript:this.className='form';this.style.cursor='auto';\" 
		OnClick=\"javascript:SeTimeOutIMG32('$img_id');$js\">
	<table style='width:{$size}px;'>
	<tr>
	<td width=1% valign='top' style='vertical-align:top';>
	<input type='hidden' name='{$img_id}_org' id='{$img_id}_org' value='img/$img'>
	" . imgtootltip($img,"{$text}","$js",null,$img_id)."</td>
	<td>
	<strong style='line-height:normal'>$title</strong>
	<div style='min-height:75px'>
		<div style='font-size:11px;line-height:normal'>$text</div><!-- replace -->
	</div>
		</td>
	</tr>
	</table></div>";
}

function rules_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$webfilter=new webfilter_rules();
	$t=time();	
	$add_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	$groups=$tpl->_ENGINE_parse_body("{groups2}");
	$blacklists=$tpl->_ENGINE_parse_body("{blacklists}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$service_events=$tpl->_ENGINE_parse_body("{service_events}");
	$global_parameters=$tpl->_ENGINE_parse_body("{global_parameters}");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$config_file=$tpl->_ENGINE_parse_body("{config_file}");
	$categories_group=$tpl->_ENGINE_parse_body("{categories_groups}");
	$main_title="<span style=font-size:18px>".$tpl->javascript_parse_text("{main_webfiltering_rules}")."</span>";
	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	$compile_bt="{name: '<strong style=font-size:16px;font-weight:bold>$compile_rules</strong>', bclass: 'Reconf', onpress : CompileUfdbGuardRules},";
	
	if($UseRemoteUfdbguardService==1){$compile_bt=null;}
	
	$error_ldap=null;
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px;>$add_rule</strong>', bclass: 'add', onpress : DansGuardianNewRule},
	{name: '<strong style=font-size:16px;font-weight:bold>$categories_group</strong>', bclass: 'group', onpress : CategoriesGroups},
	{name: '<strong style=font-size:16px;font-weight:bold>$global_parameters</strong>', bclass: 'Settings', onpress : UfdbGuardConfigs},
	$compile_bt	
	
	
	
	],";
	
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");		
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==1){
		$ad=new ActiveDirectory();
		if($ad->ldapFailed){
			$ad->ldap_last_error=nl2br($ad->ldap_last_error);
			$error_ldap=$tpl->_ENGINE_parse_body("
		<div id='$t'>
	<table style='width:98%' class=form>
	<tr>
	<td valign='top' width=95%>
		<div style='font-size:14px;color:#CC0A0A'>
		<img src='img/error-64.png' style='float:left;margin:3px'>
		<strong style='font-size:14px'>{error}:LDAP&nbsp;&raquo;&nbsp;Active Directory ($ad->ldap_host:$ad->ldap_port)</strong><hr>
		<span style='font-size:11px'>$ad->ldap_last_error</span>
		</div>
		<div style='text-align:right;width:100%'>
		<table style='width:5%' align='right'>
			<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:YahooSearchUser('800','squid.adker.php?ldap-params=yes','$ldap_parameters');\" 
					style='font-size:14px;text-decoration:underline;font-weight:bold'>$ldap_parameters</a>
				</td>
		</tr>
		</table>
		</div>
	</td>
	</tr>
	</table>
	</div>");}
	}	
	
$TBSIZE=350;
$TBWIDTH=823;
if($tpl->language=="fr"){$TBSIZE=350;$TBWIDTH=823;}
	//{display: '&nbsp;', name : 'dup', width :31, sortable : false, align: 'center'}, 
	
$html="
<center id='rules-toolbox' style='margin-bottom:5px'></center>
$error_ldap
<input type='hidden' id='WebFilteringMainTableID' value='flexRT$t'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
function flexRTStart$t(){
$('#flexRT$t').flexigrid({
	url: '$page?rules-table-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$rule_text', name : 'groupname', width : 411, sortable : true, align: 'left'},	
		{display: '$groups', name : 'topattern', width :123, sortable : false, align: 'center'},
		{display: '$blacklists', name : 'enabled', width : 123, sortable : false, align: 'center'},
		{display: '$whitelists', name : 'delete', width : 123, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'zOrder', width :90, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'dup', width :65, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width : 90, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$rule_text', name : 'groupname'},
		],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$main_title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
	
	
}
	function DansGuardianNewRule(){
		DansGuardianEditRule(-1)
	}

	function DansGuardianEditRule(ID,rname){
		YahooWin3('1100','dansguardian2.edit.php?ID='+ID+'&t=$t','$rule_text::'+ID+'::'+rname);
	}
	
	function CompileUfdbGuardRules(){
		Loadjs('dansguardian2.compile.php');
	}
	
	function CategoriesGroups(){
		Loadjs('dansguardian2.categories.group.php?tSource=$t');
	}
	
	function UfdbGuardConfigs(){
		Loadjs('ufdbguard.php');
	}
	
	function UfdbguardEvents(){
		Loadjs('$page?UfdbguardEvents=yes');
	}
	var x_RuleDansUpDown$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#flexRT$t').flexReload();
	}	

		
	function RuleDansUpDown(ID,dir){
		var XHR = new XHRConnection();
		XHR.appendData('rule-move', ID);
		XHR.appendData('rule-dir', dir);
		XHR.sendAndLoad('$page', 'POST',x_RuleDansUpDown$t);	
	}
	

	
		var x_DansGuardianDeleteMainRule= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			$('#row'+rowid).remove();
		}		
		
		function DansGuardianDeleteMainRule(ID){
			rowid=ID;
			if(confirm('$action_delete_rule')){
				var XHR = new XHRConnection();
		     	XHR.appendData('DansGuardianDeleteMainRule', ID);
		      	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDeleteMainRule);  
			}
		}
		

	

	setTimeout('flexRTStart$t()',800);	
	
</script>

";	
	
	echo $html;
	
}
	
	
function rules_table_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$webfilter=new webfilter_rules();
	if(!$q->FIELD_EXISTS("webfilter_rules", "zOrder")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `zOrder` SMALLINT(2) NOT NULL,ADD INDEX ( `zOrder` )");}
	if(!$q->ok){json_error_show("$q->mysql_error");}	
	
	if(!$q->FIELD_EXISTS("webfilter_rules", "AllSystems")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `AllSystems` smallint(1),ADD INDEX ( `AllSystems` )");}	
	if(!$q->ok){json_error_show("$q->mysql_error");}
	$sock=new sockets();
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_rules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){

		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	$DefaultPosition=$ligne["defaultPosition"];
	if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	$tmplate=$tpl->_ENGINE_parse_body("{template}");
	
	if(!$q->ok){json_error_show("$q->mysql_error");}	
	
	$AllSystems=$tpl->_ENGINE_parse_body("{AllSystems}");
	
	if($DefaultPosition==0){
		$data['rows'][]=DefaultRule();
	}

	
while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$endofrule_text=null;
		$EnableGoogleSafeSearch_text=null;
		$ligne["groupname"]=utf8_encode($ligne["groupname"]);
		$delete=imgtootltip("delete-32.png","{delete}","DansGuardianDeleteMainRule('{$ligne["ID"]}')");
		
		$js="DansGuardianEditRule('{$ligne["ID"]}','{$ligne["groupname"]}');";
		if($GLOBALS["VERBOSE"]){echo "<HR>webfilter->rule_time_list_from_ruleid({$ligne["ID"]})<HR><br>\n";}
		
		
		
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		
		if($ligne["groupmode"]==0){
			$color="#E91212";
			$warn="<div style='float:right'><img src='img/stop-24.png'></div>";
		}		
		$duplicate=imgsimple("duplicate-32.png",null,"Loadjs('dansguardian2.duplicate.php?from={$ligne['ID']}&t=$t')");
		$jsGroups="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:document.getElementById('anim-img-{$ligne["ID"]}').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-groups={$ligne["ID"]}&ID={$ligne["ID"]}&t=$t');\"
		style='text-decoration:underline;font-weight:bold'>";
		
		$jsblack="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-{$ligne['ID']}').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID={$ligne['ID']}&modeblk=0&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold'>";
		
		
		$jswhite="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-{$ligne['ID']}').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID={$ligne['ID']}&modeblk=1&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold'>";		
		
		
		$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],$ligne["ID"],$t);
    	$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
		
		$styleupd="style='border:0px;margin:0px;padding:0px;background-color:transparent'";
		$up=imgsimple("arrow-up-32.png","","RuleDansUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-32.png","","RuleDansUpDown('{$ligne['ID']}',0)");
		$zorder="<table $styleupd><tr><td $styleupd>$down</td $styleupd><td $styleupd>$up</td></tr></table>";		
		
		
		$CountDeGroups="&laquo;&nbsp;$jswhite$jsGroups".$webfilter->COUNTDEGROUPES($ligne["ID"])."</a>&nbsp;&raquo;";
		
		if($ligne["AllSystems"]==1){
			$jsGroups="*";
			$CountDeGroups="*";
		}
		
		$endofrule=$ligne["endofrule"];
		if($endofrule=="none"){
			$endofrule_text="<br><i style='color:#DB0000;margin-top:8px;font-weight:bold'>".$tpl->javascript_parse_text("{block_unknown_websites}")."</i>";
		}
		
		if($EnableGoogleSafeSearch==0){
			if($ligne["GoogleSafeSearch"]==1){
				$EnableGoogleSafeSearch_text="<br><i style='color:#00A940;margin-top:8px;font-weight:bold'>".$tpl->javascript_parse_text("{EnableGoogleSafeSearch}")."</i>";
			}
			
		}
		
		
	

	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<span id='anim-img-{$ligne["ID"]}'></span>
				<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" 
				style='font-size:24px;color:$color;text-decoration:underline'>{$ligne["groupname"]}</a>
			$TimeSpace$endofrule_text$EnableGoogleSafeSearch_text",
			"<span style='font-size:24px;color:$color;'>$CountDeGroups</span>",
			"<span style='font-size:24px;color:$color;'>&laquo;&nbsp;$jsblack". $webfilter->COUNTDEGBLKS($ligne["ID"])."</a>&nbsp;&raquo;</span>",
			"<span style='font-size:24px;color:$color;'>&laquo;&nbsp;$jswhite". $webfilter->COUNTDEGBWLS($ligne["ID"])."</a>&nbsp;&raquo;</span>",
			"<center>$zorder</center>",
			"<center>$duplicate</center>",
			"<center>$delete</center>", )
		);
	}
	
	if($DefaultPosition==1){
		$data['rows'][]=DefaultRule();
	}	

	
echo json_encode($data);	

}

function DefaultRule(){
	$t=$_GET["t"];
	$sock=new sockets();
	$webfilter=new webfilter_rules();
	$tpl=new templates();
	$tmplate=$tpl->_ENGINE_parse_body("{template}");
	$color="black";
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	$endofrule_text=null;
	//$color="#E91212";
	
	
	if($EnableGoogleSafeSearch==0){
		if($ligne["GoogleSafeSearch"]==1){
			$EnableGoogleSafeSearch_text=$tpl->javascript_parse_text("<br><i style='color:#00A940;margin-top:8px;font-weight:bold'>{EnableGoogleSafeSearch}</i>");
		}
			
	}
	
	if($ligne["groupmode"]==0){
		$color="#E91212";
		$warn="<div style='float:right'><img src='img/stop-24.png'></div>";
	}
	
	$js="DansGuardianEditRule('0','default')";
	$jsblack="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=0&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";
	
	
	$jswhite="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=1&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";
	
	$delete="&nbsp;";
	$duplicate=imgsimple("duplicate-32.png",null,"Loadjs('dansguardian2.duplicate.php?default-rule=yes&t=$t')");
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	if($GLOBALS["VERBOSE"]){echo "<HR>webfilter->rule_time_list_from_ruleid(0)<HR><br>\n";}
	$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],0,$t);
	$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
	
	if($GLOBALS["VERBOSE"]){echo "<HR>$TimeSpace<HR><br>\n";}
	
	$endofrule=$ligne["endofrule"];
	if($endofrule=="none"){
		$endofrule_text="<br><i style='color:#DB0000;margin-top:8px;font-weight:bold'>{block_unknown_websites}</i>";
	}

	
	return array(
		'id' => 0,
		'cell' => array(
						"<span id='anim-img-0'></span><a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
						style='font-size:22px;text-decoration:underline;color:$color'>Default</a>
						$TimeSpace$endofrule_text$EnableGoogleSafeSearch_text
	
						",
						"<span style='font-size:22px;color:$color'>*</span>",
						"<span style='font-size:22px;color:$color'>&laquo;&nbsp;$jsblack". $webfilter->COUNTDEGBLKS(0)."</a>&nbsp;&raquo;</span>",
						"<span style='font-size:22px';color:$color>&laquo;&nbsp;$jswhite". $webfilter->COUNTDEGBWLS(0)."</a>&nbsp;&raquo;</span>",
								"",
								"$duplicate",
								$delete )
				);	
	
}


function rule_move(){

	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM webfilter_rules WHERE `ID`='{$_POST["rule-move"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xORDER_ORG=$ligne["zOrder"];
	$xORDER=$xORDER_ORG;
	if($_POST["rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilter_rules SET zOrder=$xORDER WHERE `ID`='{$_POST["rule-move"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	//echo $sql."\n";

	if($_POST["rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_POST["rule-move"]}' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_POST["rule-move"]}' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilter_rules ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {
		$q->QUERY_SQL("UPDATE webfilter_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}


}











function delete_rule(){
	$q=new mysql_squid_builder();
	$ID=$_POST["DansGuardianDeleteMainRule"];
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	if($q->TABLE_EXISTS("ufdb_design")){
		$q->QUERY_SQL("DELETE FROM ufdb_design WHERE ruleid='$ID'");
	}
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
}

function rules_toolbox(){
	
	$tpl=new templates();

	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=0"));
	$countblk=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=1"));
	$countwhl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=2"));
	$countWwhl=$ligne["tcount"];	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=3"));
	$countBwhl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=4"));
	$countCacheWl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=5"));
	$countUserAgentWL=$ligne["tcount"];	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=6"));
	$countMimeType=$ligne["tcount"];		
	
	
	
	
	$html="<table style='width:5%' class=form>
	<tbody>
	<tr>
		<td width=1% style='font-size:22px'>$countblk</td>
		<td width=1% style='font-size:22px'>". imgtootltip("32-black-computer.png","{black_ip_group_text}","Loadjs('squid.hosts.blks.php?blk=0')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countwhl</td>
		<td width=1% style='font-size:22px'>". imgtootltip("32-white-computer.png","{white_ip_group_text}","Loadjs('squid.hosts.blks.php?blk=1')")."</td>
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countWwhl</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("domain-whitelist-w32.png","{dansguardian_exception_site_list}","Loadjs('squid.hosts.blks.php?blk=2')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countCacheWl</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("database-32-delete.png","{notcaching_websites}","Loadjs('squid.hosts.blks.php?blk=4')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countUserAgentWL</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("user-agent-ok-32.png","{ban_browsers_text}","Loadjs('squid.hosts.blks.php?blk=5')")."</td>				
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countMimeType</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("32-mime.png","{white_mime_type}","Loadjs('squid.hosts.blks.php?blk=6')")."</td>				
				
		
	</tr>
	
	</tbody>
	</table>
		
		
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function EnableUFDB2(){
	$sock=new sockets();
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
	$sock->getFrameWork("cmd.php?reload-dansguardian=yes");
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}




