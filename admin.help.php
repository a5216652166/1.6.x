<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(isset($_GET["loadhelp"])){loadhelp();exit;}
	
	$title=$tpl->javascript_parse_text("{help}");
	$loadhelp=urlencode($_GET["text"]);
	header("content-type: application/x-javascript");
	echo "YahooWinT(600,'$page?loadhelp=$loadhelp','$title');";
	
	
function loadhelp(){
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body(base64_decode($_GET["loadhelp"]));
	
	echo "<div class=text-info style='font-size:14px;width:90%'>$text</div>";
}
