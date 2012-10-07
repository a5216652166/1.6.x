<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
if(isset($_GET["params"])){params();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_POST["RunBackup"])){runbackup();exit;}
popup();
	
	
function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$file=$tpl->_ENGINE_parse_body("{directory}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$time=$tpl->_ENGINE_parse_body("{duration}");
	$scan_dir=$tpl->_ENGINE_parse_body("{scan_dir}");
	$run_backup=$tpl->_ENGINE_parse_body("{run_backup}");
	$title=$tpl->_ENGINE_parse_body("{APP_ZARAFA}::{backups}");
	$run_backup_confirm=$tpl->javascript_parse_text("{run_backup_confirm}");
	$parms="{name: '$parameters', bclass: 'Settings', onpress : Params$t},";
	$run="{name: '$run_backup', bclass: 'Down', onpress : Run$t},";
	$scan="{name: '$scan_dir', bclass: 'Reload', onpress : Scan$t},";
	
	
	

	
$buttons="buttons : [
		$parms$run$scan
			],	";	
	

	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&day=$today$byMonth',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :132, sortable : true, align: 'left'},	
		{display: '$file', name : 'filepath', width :110, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :110, sortable : true, align: 'left'},
		{display: '$time', name : 'ztime', width :190, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'restore', width :31, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$date', name : 'zDate'},
		{display: '$file', name : 'filepath'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 730,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function Params$t(){
	Loadjs('zarafa.backup-params.php');

}


var x_Run$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}			
	
}	

function Run$t(){
	if(confirm('$run_backup_confirm')){
		var XHR = new XHRConnection();
		XHR.appendData('RunBackup','yes');
		XHR.sendAndLoad('$page', 'POST',x_Run$t);
	
	}
}

function Scan$t(){

}

</script>";
	
	echo $html;	
}

function runbackup(){
	$sock=new sockets();
	$tpl=new templates();
	$sock->getFrameWork("zarafa.php?run-backup=yes");
	echo $tpl->javascript_parse_text("{backup_executed_in_background}");
	
}

function items(){
	$myPage=CurrentPageName();
	
	$tpl=new templates();
	$q=new mysql();
	
	$table="`zarafa_backup`";
	$database="artica_backup";
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS($table,$database)){json_error_show("$table: No such table",0,true);}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
		$ligne["filepath"]=basename($ligne["filepath"]);
		$data['rows'][] = array(
				'id' => $ligne["ID"],
				'cell' => array(
					"<span style='font-size:14px;color:$color'>{$ligne["zDate"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs{$ligne["filepath"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs{$ligne["filesize"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs{$ligne["ztime"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs&nbsp;</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs&nbsp;</a></span>",
					
					)
				);
			}
	
	
echo json_encode($data);	
	
}