<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){die();}
	if(isset($_GET["list-table"])){list_table();exit;}

page();
function page(){
	
	$table=date("Ymd")."_dcnx";
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$q=new mysql_postfix_builder();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$build_parameters=$tpl->javascript_parse_text("{build_parameters}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$import=$tpl->javascript_parse_text("{import}");
	$title=$tpl->javascript_parse_text("{today},{last_connections} {total}:&nbsp;").  $q->COUNT_ROWS($table);
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$status=$tpl->javascript_parse_text("{status}");
	$time=$tpl->javascript_parse_text("{time}");
	
	// Hour | cnx | hostname                                       | domain                    | 
	$buttons="
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : NewDiffListItem$t},
	{name: '$import', bclass: 'Reconf', onpress :NewDiffListItemImport$t},
	],";

	$buttons=null;

	$html="


	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>

	<script>
	var memid$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
	
	{display: '$time', name : 'Hour', width : 74, sortable : true, align: 'left'},
	{display: 'CNX', name : 'cnx', width :69, sortable : true, align: 'left'},
	{display: '$hostname', name : 'hostname', width : 381, sortable : false, align: 'left'},
	{display: '$domain', name : 'domain', width : 381, sortable : false, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width : 160, sortable : false, align: 'left'},
	],
	$buttons
	searchitems : [
	{display: '$hostname', name : 'hostname'},
	{display: '$domain', name : 'domain'},
	{display: '$ipaddr', name : 'ipaddr'},
	],
	sortname: 'Hour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

</script>";
	echo $html;
}

function list_table(){

	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();
	$table=date("Ymd")."_dcnx";
	$q=new mysql_postfix_builder();
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table");}
	
	$t=$_GET["t"];
	$database=null;
	$FORCE_FILTER=1;


	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $searchstring");}

	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=1;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
	$today=date('Y-m-d');
	$style="font-size:14px;";

	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	
	//Hour | cnx | hostname                                       | domain                    | 
	
	while ($ligne = mysql_fetch_assoc($results)) {

		
		$md=md5(serialize($ligne));
		$cells=array();
		$cells[]="<span style='font-size:14px;'>{$ligne["Hour"]}h</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["cnx"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["hostname"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["domain"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["ipaddr"]}</span>";
		

			
			
		$data['rows'][] = array(
				'id' =>$line["zmd5"],
				'cell' => $cells
		);


	}

	echo json_encode($data);
}
