<?php

	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["Enablentopng"])){save();exit;}
	if(isset($_GET["status"])){status();exit;}
page();	
	
function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$INSTALLED=$sock->getFrameWork("system.php?ntopng-installed=yes");
	if($INSTALLED<>"TRUE"){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_not_installed}"));
		return;
	}
	
	$html="
	<div style='font-size:28px;margin-bottom:30px'>{APP_NTOPNG}</div>
	<div style='width:98%' class=form>		
	<table style='width:100%'>
		<tr>
			<td style='width:350px;vertical-align:top'><div id='APP_NTOPNG_STATUS'></div></td>
			<td style='width:560px'><div id='APP_NTOPNG_PARAMS'></div></td>
		</tr>
	</table>	
	</div>
	<script>
		LoadAjax('APP_NTOPNG_PARAMS','$page?params=yes',false);
	</script>		
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function params(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$arrayConf=unserialize(base64_decode($sock->GET_INFO("ntopng")));
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=1;}
	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	if(!is_numeric($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
	if(!is_numeric($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
	
	$days[1]="1 {day}";
	$days[2]="2 {days}";
	$days[5]="5 {days}";
	$days[10]="10 {days}";
	$days[15]="15 {days}";
	$days[30]="1 {month}";
	
	
	$html=Paragraphe_switch_img("{enable_ntopng}", "{enable_ntopng_text}","Enablentopng",$Enablentopng,null,490).
	"<br>".
	Paragraphe_switch_img("{enable_login}", "{enable_login_ntopng_explain}","ENABLE_LOGIN",$arrayConf["ENABLE_LOGIN"],null,490).
	"<table style='width:100%'>
		<tr>
			<td valign='middle' class=legend style='font-size:22px'>{listen_port}:</td>
			<td>". Field_text("HTTP_PORT",$arrayConf["HTTP_PORT"],"font-size:22px;width:110px")."</td>
		</tr>
		<tr>
			<td valign='middle' class=legend style='font-size:22px'>{webconsole}:</td>
			<td><a href=\"http://{$_SERVER["SERVER_ADDR"]}:{$arrayConf["HTTP_PORT"]}/\" 
			style='font-size:22px;text-decoration:underline'
			target=_new>http://{$_SERVER["SERVER_ADDR"]}:{$arrayConf["HTTP_PORT"]}</a></td>
		</tr>
		".Field_list_table("MAX_DAYS", "{retention_days}", $arrayConf["MAX_DAYS"],22,$days)."				
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",28)."</td>
		</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	RefreshTab('tabs_networktrack');
}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('Enablentopng',document.getElementById('Enablentopng').value);
		XHR.appendData('ENABLE_LOGIN',document.getElementById('ENABLE_LOGIN').value);
		XHR.appendData('MAX_DAYS',document.getElementById('MAX_DAYS').value);
		XHR.appendData('HTTP_PORT',document.getElementById('HTTP_PORT').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	
	}	

LoadAjax('APP_NTOPNG_STATUS','$page?status=yes',false);
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$sock=new sockets();
	$sock->SET_INFO("Enablentopng", $_POST["Enablentopng"]);
	
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ntopng");
	$sock->getFrameWork("system.php?ntopng-restart=yes");
}
function status(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("system.php?ntopng-status=yes")));
	$page=CurrentPageName();
	$tpl=new templates();
	$serv[]=DAEMON_STATUS_ROUND("APP_NTOPNG",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_REDIS_SERVER",$ini,null,0);
	$serv[]="<div style='text-align:right;margin-top:20px'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('APP_NTOPNG_STATUS','$page?status=yes',true);")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("<br>", $serv));
	
}