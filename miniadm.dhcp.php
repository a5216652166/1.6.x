<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!$_SESSION["ASDCHPAdmin"]){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["items"])){report_items();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.network.php\">{network_services}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.dhcp.php\">{APP_DHCP}</a>
		</div>
		<H1>{APP_DHCP}</H1>
		<p>{APP_DHCP_TEXT}</p>
	</div>	
	<div id='webstats-middle-$ff' class=BodyContent></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	if(isset($_GET["explain-title"])){
		$title="<H1>{APP_DHCP}</H1>
		<p>{APP_DHCP_TEXT}</p>";
		
	}
	$boot=new boostrap_form();
	$fontsize=null;
	if(isset($_GET["newinterface"])){$newinterface="&newinterface=yes";$newinterfacesuffix="?newinterface=yes";$fontsize="font-size:14px;";}
	
	
	$array["{status}"]='index.gateway.php?dhcp-tab=status&miniadm=yes';
	$array["{settings}"]='index.gateway.php?dhcp-tab=config&miniadm=yes';
	$array["{fixedHosts}"]="miniadm.dhcpd.fixed.hosts.php";
	$array["{groups2}"]='dhcpd.shared-networks.php?newinterface=yes';
	
	$array["{leases}"]="miniadm.dhcpd.leases.php";
	$array["{events}"]="miniadm.dhcpd.events-sql.php$newinterfacesuffix";
	$html="$title".$boot->build_tab($array);
	echo $tpl->_ENGINE_parse_body($html);
}	
