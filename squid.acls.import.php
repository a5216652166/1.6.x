<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert')";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["importYES"])){importYES();exit;}
if(isset($_POST["importSquidYES"])){importSquidYES();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->javascript_parse_text("{import_rules}");
	$t=time();
	$html="YahooWin3('750','$page?popup=yes&t={$_GET["t"]}','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$tsource=$_GET["t"];
	$confirm=$tpl->javascript_parse_text("{confirm_import_rules_text}");
	if(!is_numeric($tsource)){$tsource=time();}
	$html="
	<div id='id-final-$t'>		
	<div id='text-$t' style='font-size:16px' class=text-info>{import_acl_rules_explain}</div>
	<div style='font-size:16px' id='$t-wait'></div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:16px'>{backup_file}:</td>
		<td class=legend style='font-size:16px'>". Field_text("backupctner-$t",null,"font-size:16px;width:95%")."</td>
		<td width=1%>". button("{browse}...","Loadjs('tree.php?target-form=backupctner-$t&select-file=gz,acl')","12px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{import}...","Save$t()","18px")."</td>
	</tr>
	</table>
	</div>
	
	<div id='text-$t' style='font-size:16px;margin-top:20px' class=text-info>{import_aclsquid_rules_explain}</div>
	<div style='font-size:16px' id='$t-wait'></div>
		<div style='width:98%' class=form>
			<table>
			<tr>
				<td class=legend style='font-size:16px'>squid.conf:</td>
				<td class=legend style='font-size:16px'>". Field_text("squidconf-$t",null,"font-size:16px;width:95%")."</td>
				<td width=1%>". button("{browse}...","Loadjs('tree.php?target-form=squidconf-$t&select-file=conf')","12px")."</td>
			</tr>
			<tr>
				<td colspan=3 align='right'><hr>". button("{import}...","SaveSquidConf$t()","18px")."</td>
			</tr>
			</table>
		</div>	
		
		<div style='width:98%' class=form>
			<table style='width:100%'>
			<tr>
				
				<td class=legend colspan=3><div class=text-info style='font-size:16px'>{import_squid_zip_explain}</td>
			</tr>
			<tr>
				<td colspan=3 align='center'><hr>". button("{compressed_file}...","Loadjs('import.squid.zip.php')","18px")."</td>
			</tr>
			</table>
		</div>			
		
		
	</div>
	
<script>	
	var x_Save$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-wait').innerHTML='';
		if(results.length>3){alert(results);return;}
		RefreshTab('main_dansguardian_tabs');
		$('#table-$tsource').flexReload();
		YahooWin3Hide();
	}
	var x_SaveSquid$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-wait').innerHTML='';
		if(results.length>3){
			document.getElementById('id-final-$t').innerHTML=\"<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px' id='mibtxt$t'>\"+results+\"</textarea>\";
		}
		RefreshTab('main_dansguardian_tabs');
		$('#table-$tsource').flexReload();
		
	}	
	
	function Save$t(){
		if(!confirm('$confirm')){return;}
		var XHR = new XHRConnection();
		AnimateDiv('$t-wait');
		XHR.appendData('importYES',document.getElementById('backupctner-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}	

	function SaveSquidConf$t(){
		var XHR = new XHRConnection();
		AnimateDiv('$t-wait');
		XHR.appendData('importSquidYES',document.getElementById('squidconf-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveSquid$t);
	}	
	
	
</script>		
";
		
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function importSquidYES(){
	$file=urlencode($_POST["importSquidYES"]);
	$sock=new sockets();
	echo @implode("\n",unserialize(base64_decode($sock->getFrameWork("squid.php?import-squid-conf=$file"))));
}

function importYES(){
	$file=urlencode($_POST["importYES"]);
	$sock=new sockets();
	echo @implode("\n",unserialize(base64_decode($sock->getFrameWork("squid.php?import-acls=$file"))));
	
	
}	
?>