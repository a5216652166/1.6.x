<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["DELETE"])){DELETE_FROM_CACHE();exit;}
if(isset($_GET["list"])){WEBSITES_SEARCH();exit;}
if(isset($_GET["DeleteWebsiteZCached-js"])){DeleteWebsiteZCached_js();exit;}

page();

function DeleteWebsiteZCached_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$time=time();
$html="
var xDeleteWebsiteZCached$time= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row{$_GET["ID"]}').remove();			
}	

function DeleteWebsiteZCached$time(){
	var XHR = new XHRConnection();
	XHR.appendData('DELETE','{$_GET["sitename"]}');
	XHR.appendData('hostid','{$_GET["hostid"]}');
	XHR.sendAndLoad('$page', 'POST',xDeleteWebsiteZCached$time);
}			
DeleteWebsiteZCached$time();		
";
	
	echo $html;
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$website=$tpl->_ENGINE_parse_body("{website}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$title=$tpl->_ENGINE_parse_body("{cached_items}");
	
	$t=time();
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
<script>
var websiteMem='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?list=yes&hostid={$_GET["hostid"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width :491, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 145, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 72, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'left'},
		],
	
	searchitems : [
		{display: '$website', name : 'sitename'}
		],
	sortname: 'sitename',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

</script>
	
	
	";
	
	echo $html;
	
}

function WEBSITES_SEARCH(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_blackbox();
	$database="artica_backup";
	
	$search='%';
	$table="cacheitems_{$_GET["hostid"]}";
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){ json_error_show(0,$q->mysql_error); }	
	
	/*`sitename` varchar(255) NOT NULL,
			`familysite` varchar(255) NOT NULL,
			`size` BIGINT UNSIGNED,`items` BIGINT UNSIGNED,
			 PRIMARY KEY (`sitename`),
			 KEY `familysite` (`familysite`),
			 KEY `size` (`size`),
			 KEY `items` (`items`)
	*/
	if(mysql_num_rows($results)==0){json_error_show(0,"no data"); }	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=md5($ligne["sitename"]);
		$siteenc=$ligne["sitename"];
		
		
		$delete=imgtootltip("delete-24.png","{delete}",
				"Loadjs('$MyPage?DeleteWebsiteZCached-js=yes&t={$_GET["t"]}&sitename=$siteenc&ID=$ID&hostid={$_GET["hostid"]}')");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$data['rows'][] = array(
		'id' => $ID,
		'cell' => array("
		<span style='font-size:14px'>$link{$ligne["sitename"]}</a></span>"
		,"<span style='font-size:14px'>{$ligne["size"]}</a></span>",
		"<span style='font-size:14px'>{$ligne["items"]}</a></span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		

}

function DELETE_FROM_CACHE(){
	$sock=new sockets();
	$delete_enc=urlencode($_POST["DELETE"]);
	$sock->getFrameWork("squid.php?purge-site=$delete_enc");
	$sql="DELETE FROM cacheitems_{$_POST["hostid"]} WHERE sitename='{$_POST["DELETE"]}'";
	$q=new mysql_blackbox();
	$q->QUERY_SQL($sql);
	
}
