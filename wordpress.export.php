<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
$usersmenus=new usersMenus();
if($usersmenus->AsWebMaster==false){echo "alert('No privs');";die();}


if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{export} {$_GET["servername"]}");
	$servername=$_GET["servername"];
	$servername_enc=urlencode($servername);
	echo "
	YahooWinBrowseHide();		
	RTMMail('800','$page?popup=yes&servername=$servername_enc','$title');";
	
	
}

function FilesLogs(){
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.export.{$_REQUEST["servername"]}.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wordpress.export.{$_REQUEST["servername"]}.progress.txt";
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	FilesLogs();
	
	
	$cachefile=$GLOBALS["PROGRESS_FILE"];
	$logsFile=$GLOBALS["LOG_FILE"];
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($cachefile));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
if($prc==0){
echo "
// $cachefile
// $logsFile
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&servername=".urlencode($_REQUEST["servername"])."');
}
setTimeout(\"Start$time()\",1000);";
return;
}

$md5file=md5_file($logsFile);
if($md5file<>$_GET["md5file"]){
	echo "
// $cachefile
// $logsFile
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file&servername=".urlencode($_REQUEST["servername"])."');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('servername','".urlencode($_REQUEST["servername"])."');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc>100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		document.getElementById('title-$t').style.border='1px solid #C60000';
		document.getElementById('title-$t').style.color='#C60000';
		$('#progress-$t').progressbar({ value: 100 });
		
	}
	setTimeout(\"Start$time()\",1000);
	";
	return;	
	
}

if($prc==100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		LayersTabsAllAfter();
		RefreshTab('main_artica_wordpress');
		RTMMailHide();
		Loadjs('wordpress.export.download.php?&servername=".urlencode($_REQUEST["servername"])."');
		}
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
// $cachefile
// $logsFile
function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&servername=".urlencode($_GET["servername"])."');
	}
	setTimeout(\"Start$time()\",1500);
";




//Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
		
	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$restart=null;
	$servername=$_GET["servername"];
	$servername_enc=urlencode($servername);
	$sock->getFrameWork("wordpress.php?export=yes&servername=$servername_enc");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("$servername: {please_wait_preparing_settings}...");
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&servername=".urlencode($_GET["servername"])."');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	FilesLogs();
	$logsFile=$GLOBALS["LOG_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
	
}