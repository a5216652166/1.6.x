<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["status"])){status();exit;}

if(isset($_GET["dansguardian-status"])){dansguardian_status();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["groups-filters"])){groups_filters();exit;}

if(isset($_GET["groups-search"])){groups_search();exit;}
if(isset($_GET["dansguardian-service-status"])){dansguardian_service_status();exit;}
if(isset($_GET["dansguardian-service_status-nofilters"])){dansguardian_service_status_nofilters();exit;}

if(isset($_POST["Delete-Group"])){groups_delete();exit;}

if(isset($_GET["ufdbguard"])){ufdbguard_service_section();exit;}
if(isset($_GET["ufdbguard-options"])){ufdbguard_service_options();exit;}
if(isset($_GET["js-ufdbguard"])){ufdbguard_service_js();exit;}
if(isset($_POST["DisableAllFilters"])){DisableAllFilters();exit;}
if(isset($_POST["EnableMalWarePatrol"])){EnableMalWarePatrol();exit;}


tabs();


function DisableAllFilters(){
	$sock=new sockets();
	$sock->SET_INFO("SquidDisableAllFilters", $_POST["value"]);
	$sock->getFrameWork("cmd.php?squid-reload=yes");
}
function EnableMalWarePatrol(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMalwarePatrol", $_POST["value"]);
	$sock->getFrameWork("cmd.php?squid-reload=yes");	
}


function tabs(){
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}		
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$enable_streamcache=$sock->GET_INFO("SquidEnableStreamCache");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}	
	
	if($users->SQUID_INSTALLED){
		if($DisableArticaProxyStatistics==0){
			$StatsPerfsSquidAnswered=$sock->GET_INFO("StatsPerfsSquidAnswered");
			if(!is_numeric($StatsPerfsSquidAnswered)){$StatsPerfsSquidAnswered=0;}
			if(!$users->WEBSTATS_APPLIANCE){if($StatsPerfsSquidAnswered==0){$CPU=$users->CPU_NUMBER;$MEM=$users->MEM_TOTAL_INSTALLEE;if(($CPU<4) AND (($MEM<3096088))){WARN_SQUID_STATS();die();}}}
		}
	}
		
	

	
	if($EnableWebProxyStatsAppliance==1){$users->APP_UFDBGUARD_INSTALLED=true;$squid->enable_UfdbGuard=1;}
	
	if($EnableRemoteStatisticsAppliance==0){$array["rules"]='{webfilter}';$array["acls"]='{acls}';}
	$array["ufdbguard"]='{service_parameters}';
	
	if($EnableRemoteStatisticsAppliance==0){
		$array["groups"]='{groups}';
		$array["databases"]='{webfilter_databases}';
		if($enable_streamcache==1){$array["streamcache"]='{streamcache_status}';}
	}
	
	
	
	
	$fontsize=14;
	if(count($array)>5){$fontsize=12;}
	
	if(count($array)>6){$fontsize=11.7;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="acls"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.acls-rules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		if($num=="databases"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="streamcache"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.streamcache.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}


		


				
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_dansguardian_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_dansguardian_tabs').tabs();
			});
		</script>";	

}

function status_left(){
	
}


function groups(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["groups-filters"]='{groups_for_rules}';
	$array["groups-macs"]='{mac_users_linker}';
	$array["section_basic_filters-groups"]='{proxy_objects}';	
	$time=time();
	
	
	

	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="groups-macs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"domains.user.computer.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		if($num=="section_basic_filters-groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.acls.groups.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}			
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "$menus
	<div id=main_dansguardiangroups_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_dansguardiangroups_tabs').tabs();
			});
		</script>";	

}	

function groups_filters(){
	
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$page=CurrentPageName();
	$tpl=new templates();	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$browse=$tpl->_ENGINE_parse_body("{browse} AD");
	if($EnableKerbAuth==1){
		$BrowsAD="{name: '$browse', bclass: 'Search', onpress : BrowseAD},";
	}
	
	$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddNewDansGuardianGroup},$BrowsAD
	],";		

$html="<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<div class=explain>$dansguardian2_members_groups_explain</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?groups-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$group', name : 'groupname', width : 503, sortable : true, align: 'left'},
		{display: '$type', name : 'localldap', width : 151, sortable : true, align: 'left'},		
		{display: '$members', name : 'members', width :57, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width : 48, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$group', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 831,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function BrowseAD(){
	Loadjs('BrowseActiveDirectory.php');
}

		function GroupsDansSearch(){
			$('#flexRT$t').flexReload();
		
		}
		
		function AddNewDansGuardianGroup(){
			DansGuardianEditGroup(-1)
		
		}
		
		function DansGuardianEditGroup(ID,rname){
			YahooWin3('712','dansguardian2.edit.group.php?ID='+ID+'&t=$t','$group::'+ID+'::'+rname);
		
		}
		
	var x_DansGuardianDelGroup= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#row'+rowid).remove();
	}		
		
	function DansGuardianDelGroup(ID){
		if(confirm('$do_you_want_to_delete_this_group ?')){
			rowid=ID;
			var XHR = new XHRConnection();
		    XHR.appendData('Delete-Group', ID);
		    XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup); 
		}
	}		

</script>
";

	echo $html;
	
}

function groups_search(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilter_group";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((groupname LIKE '$search') OR (description LIKE '$search'))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}		
	$localldap[0]=$tpl->_ENGINE_parse_body("{ldap_group}");
	$localldap[1]=$tpl->_ENGINE_parse_body("{virtual_group}");
	$localldap[2]=$tpl->_ENGINE_parse_body("{active_directory_group}");
	
	
while ($ligne = mysql_fetch_assoc($results)) {
		$CountDeMembers=0;
		$suffix=null;
		
		$select=imgtootltip("32-parameters.png","{edit}","DansGuardianEditGroup('{$ligne["ID"]}','{$ligne["groupname"]}')");
		$delete=imgtootltip("delete-24.png","{delete}","DansGuardianDelGroup('{$ligne["ID"]}')");
		$color="black";
		if($ligne["enabled"]==0){$color="#CCCCCC";}
		
		if($ligne["localldap"]==1){
			$q2=new mysql_squid_builder();
			$sql="SELECT COUNT(ID) AS tcount FROM webfilter_members WHERE groupid={$ligne["ID"]}";
			$ligne2=mysql_fetch_array($q2->QUERY_SQL($sql));
			$CountDeMembers=$ligne2["tcount"];
		}
		
		if($ligne["localldap"]==0){
			$gp=new groups($ligne["gpid"]);
			$groupadd_text="(".$gp->groupName.")";
			$CountDeMembers=$CountDeMembers+count($gp->members);
		}
		if($ligne["localldap"]==2){
			$CountDeMembers="-";
		}
		
		$groupeTypeText=$localldap[$ligne["localldap"]];
		
		$js="DansGuardianEditGroup('{$ligne["ID"]}','{$ligne["groupname"]}')";
		$ligne["description"]=stripslashes($ligne["description"]);
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;color:$color;text-decoration:underline;font-weight:bold'>$suffix{$ligne["groupname"]} $groupadd_text</a>
			<div style='font-size:10px'><i style='font-size:14px'>{$ligne["description"]}</i>",
			"<span style='font-size:14px;color:$color;'>$groupeTypeText</span>",
			"<span style='font-size:14px;color:$color;'>$CountDeMembers</span>",
			"<span style='font-size:14px;color:$color;'>$delete</span>",
			 )
		);
	}
	
	
echo json_encode($data);	

}

function groups_delete(){
	if(!is_numeric($_POST["Delete-Group"])){return;}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM webfilter_members WHERE groupid='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM webfilter_group WHERE ID='{$_POST["Delete-Group"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");		
	
}

function status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top'><div id='dansguardian-status'></div>
			<center id='dansguardian-statistics-status' class=form style='width:100%;margin:0px'></center>
		</td>
		<td valign='top'><div id='dansguardian-service-status'></div>
	</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('dansguardian-status','$page?dansguardian-status=yes');
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function dansguardian_status(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$categories=$q->LIST_TABLES_CATEGORIES();
	$sock=new sockets();
	$SquidGuardIPWeb=trim($sock->GET_INFO("SquidGuardIPWeb"));
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidDisableAllFilters=$sock->GET_INFO("SquidDisableAllFilters");
	$SquideCapAVEnabled=$sock->GET_INFO("SquideCapAVEnabled");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
	if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
	if(!is_numeric($SquidDisableAllFilters)){$SquidDisableAllFilters=0;}
	if($SquidGuardIPWeb==null){$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort=9020;}$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/exec.squidguard.php";$sock->SET_INFO("SquidGuardIPWeb", $fulluri);}
	if($SquidGuardServerName==null){$sock->SET_INFO("SquidGuardServerName",$_SERVER['SERVER_ADDR']);}	
	$eCapClam=null;
	
	
		$pic="status_ok-grey.gif";
		$EnableActiveDirectoryText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.adker.php');\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($SquideCapAVEnabled)){$SquideCapAVEnabled=0;}
	
	
	if($EnableKerbAuth==1){
		$pic="status_ok.gif";
		$EnableActiveDirectoryText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.adker.php');\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";		
		
	}
	
	
				$EnableActiveDirectoryTextTR="<tr>
				<td width=1%><span id='AdSquidStatusLeft'><img src='img/$pic'></span></td>
				<td class=legend>Active Directory:</td>
				<td><div style='font-size:12px' nowrap>$EnableActiveDirectoryText</td>
				</tr>";		
	
	$ufdb=null;$dansgu=null;
	$t=0;
	$time=time();
	if($users->APP_UFDBGUARD_INSTALLED){
		$t++;
		$APP_UFDBGUARD_INSTALLED="{installed}";
		
		$pic="status_ok-grey.gif";
		$EnableUfdbGuardText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:EnableUfdbGuard(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
				
		$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
		if($EnableUfdbGuard==1){
			$pic="status_ok.gif";
			$EnableUfdbGuardText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:EnableUfdbGuard(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
		}
		

		
		$ufdb="
		<tr>
			<td width=1%><span id='ufd-$time'><img src='img/$pic'></span></td>
			<td class=legend nowrap>{APP_UFDBGUARD}:</td>
			<td><div style='font-size:12px' nowrap><span id='ufd-$time'>$EnableUfdbGuardText</span></td>
		</tr>";			
		
	}
	
	if($users->DANSGUARDIAN_INSTALLED){
		$t++;
		$pic=null;
		$DANSGUARDIAN_INSTALLED="{installed}";
		$DansGuardianEnabled=$sock->GET_INFO("DansGuardianEnabled");
		if(!is_numeric($DansGuardianEnabled)){$DansGuardianEnabled=0;}
		
		$DansGuardianEnabledText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:EnableDansguardian(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
		$pic="status_ok-grey.gif";		
		
		
		if($DansGuardianEnabled==1){
			$DansGuardianEnabledText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:EnableDansguardian(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.gif";
			
		}
			$dansgu="<tr>
			<td width=1%><span id='dans-$time'><img src='img/$pic'></td>
			<td class=legend nowrap>{APP_DANSGUARDIAN}:</td>
			<td><div style='font-size:12px' nowrap>$DansGuardianEnabledText</td>
			</tr>";			
		
	}

	if(!$users->KASPERSKY_WEB_APPLIANCE){
		$pic="status_ok-grey.gif";
		$eCapAVText="{not_installed}";
		
		
		if($users->ECAPAV_INSTALLED){
			if($SquideCapAVEnabled==0){
				$eCapAVText="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:EnableeCapAV(1);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";	
			}else{
				$eCapAVText="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:EnableeCapAV(0);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
				$pic="status_ok.gif";
				
			}
			
			
			
		}
		
		$eCapClam="
			<tr>
				<td width=1%><span id='ecapav-$time'><img src='img/$pic'></span></td>
				<td class=legend>{APP_ECAPAV}:</td>
				<td><div style='font-size:12px' nowrap>$eCapAVText</td>
			</tr>";			
		
		
	}
	
	
	if($users->KAV4PROXY_INSTALLED){
		$t++;
		$kavicapserverEnabledText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:EnableKav4Proxy(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
		$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
		$pic="status_ok-grey.gif";
		
		if($kavicapserverEnabled==1){
			$kavicapserverEnabledText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:EnableKav4Proxy(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.gif";
		
		}
		
		$kav="<tr>
			<td width=1%><span id='kav4-$time'><img src='img/$pic'></span></td>
			<td class=legend nowrap>{APP_KAV4PROXY}:</td>
			<td><div style='font-size:12px' nowrap>$kavicapserverEnabledText</td>
			</tr>";			
	}else{
		$pic="status_ok-grey.gif";
		$kavicapserverEnabledText="{not_installed}";
		$kav="<tr>
			<td width=1%><img src='img/$pic'></td>
			<td class=legend nowrap>{APP_KAV4PROXY}:</td>
			<td><div style='font-size:12px' nowrap>$kavicapserverEnabledText</td>
			</tr>";			
	}
	
	
	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
		$pic="status_ok-grey.gif";
		$CicapEnabledText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:EnableCiCap(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";

		
		if($CicapEnabled==1){
			$CicapEnabledText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:EnableCiCap(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.gif";
		
		}
		if($users->APP_KHSE_INSTALLED){
			$KavMetascannerEnable=$sock->GET_INFO("KavMetascannerEnable");
			if(!is_numeric($KavMetascannerEnable)){$KavMetascannerEnable=0;}
			if($KavMetascannerEnable==1){$CicapEnabledText="<span style='font-size:12px;font-weight:bold;'>{enabled}</span>";}
		}
			
		$cicap="<tr>
				<td width=1%><span id='cicap-$time'><img src='img/$pic'></span></td>
				<td class=legend>{APP_C_ICAP}:</td>
				<td><div style='font-size:12px' nowrap>$CicapEnabledText</td>
				</tr>";
		
		
	 	if($users->APP_KHSE_INSTALLED){
			$t++;
			$pic="status_ok-grey.gif";
			$KavMetascannerEnableText="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:EnableMetaScan(1);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";			

			if($KavMetascannerEnable==1){
				$KavMetascannerEnableText="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:EnableMetaScan(0);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
				$pic="status_ok.gif";
			}
			

			$kavMeta="
				<tr>
				<td width=1%><span id='kavmeta-$time'><img src='img/$pic'></span></td>
				<td class=legend>{APP_KAVMETASCANNER}:</td>
				<td><div style='font-size:12px' nowrap>$KavMetascannerEnableText</td>
				</tr>";			
			}else{
				$kavMeta="
					<tr>
					<td width=1%><img src='img/$pic'></td>
					<td class=legend>{APP_KAVMETASCANNER}:</td>
					<td><div style='font-size:12px' nowrap>{not_installed}</td>
					</tr>";			
			}
				
	}else{
		$pic="status_ok-grey.gif";
		$cicap="<tr>
				<td width=1%><img src='img/$pic'></td>
				<td class=legend>{APP_C_ICAP}:</td>
				<td><div style='font-size:12px' nowrap>{not_installed}</td>
				</tr>";			
		
	}
	
	$SquidEnableStreamCache=$sock->GET_INFO("SquidEnableStreamCache");
	if(!is_numeric($SquidEnableStreamCache)){$SquidEnableStreamCache=0;}
	$pic="status_ok-grey.gif";
	$SquidEnableStreamCacheText="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:JSEnableStreamCache(1);\" 
	style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}";	
	
	
	if($SquidEnableStreamCache==1){
		
		$pic="status_ok.gif";
		$SquidEnableStreamCacheText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:JSEnableStreamCache(0);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
	}

	
	$StreamCache="<tr>
				<td width=1%><span id='stream-$time'><img src='img/$pic'></span></td>
				<td class=legend>{StreamSquidCache}:</td>
				<td><div style='font-size:12px' nowrap>$SquidEnableStreamCacheText</td>
			</tr>";
	
	//-------------------------- MALWARE PATROL --------------------------------------
	
	$pic="status_ok-grey.gif";
	$SquidEnableMalWarePatrol="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:JSEnableMalWarePatrol(1);\" 
	style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}";	
	
	
	if($EnableMalwarePatrol==1){
		$pic="status_ok.gif";
		$SquidEnableMalWarePatrol="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:JSEnableMalWarePatrol(0);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";		
		
	}
	
	$MalWarePatrol="<tr>
				<td width=1%><span id='malwarepatrol-$time'><img src='img/$pic'></span></td>
				<td class=legend>Malware Patrol:</td>
				<td><div style='font-size:12px' nowrap>$SquidEnableMalWarePatrol</td>
			</tr>";	
	
	//-----------------------------------------------------------------------------------
	
	
	$SquidDisableAllFiltersText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:JSDisableAllFilters(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";	
		$pic="status_ok-grey.gif";
		
	if($SquidDisableAllFilters==1){
		$pic="status_ok_red.gif";
		$SquidDisableAllFiltersText="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:JSDisableAllFilters(0);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";	
		
		
	}
$DisableAllFilters="<tr>
					<td width=1%><span id='disableall-$time'><img src='img/$pic'></span></td>
					<td class=legend>{disable_filters}:</td>
					<td><div style='font-size:12px' nowrap>$SquidDisableAllFiltersText</td>
					</tr>";	
				
		
		
	$eCapClam=null;
	
	if(!$users->APP_KHSE_INSTALLED){
		$kavMeta=null;
	}
	
	if($t>0){
		$table="<table style='width:99%' class=form><tbody>$EnableActiveDirectoryTextTR$ufdb$eCapClam$dansgu$cicap$kav$kavMeta$MalWarePatrol$StreamCache$DisableAllFilters</tbody></table>";
		
	}
	
	$MEM_HIGER_1G=1;
	
	if(!$users->MEM_HIGER_1G){
		$MEM_HIGER_1G=0;
		
	}
	
	$html="
	$table
	<script>
		function RefreshDansguardianMainService(){
			LoadAjax('dansguardian-service-status','$page?dansguardian-service-status=yes');
		}
		
		var x_enable_plugins= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			Loadjs('squid.popups.php?x-save-plugins=yes');
		}

		function ThisisAClientStats(){
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			if(EnableRemoteStatisticsAppliance){
				return true;
			}
			return false;
		}
		
		
		function EnableUfdbGuard(value){
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_ufdbguardd',value);
			 document.getElementById('ufd-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}
		function EnableKav4Proxy(value){
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_kavproxy',value);
			 document.getElementById('kav4-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}
		
		function EnableDansguardian(value){
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_dansguardian',value);
			 document.getElementById('dans-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);		
		}
		
		function JSDisableAllFilters(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('DisableAllFilters','yes');
			 XHR.appendData('value',value);
			 document.getElementById('disableall-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('$page', 'POST',x_enable_plugins);		
		}
		
		function JSEnableMalWarePatrol(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('EnableMalWarePatrol','yes');
			 XHR.appendData('value',value);
			 document.getElementById('malwarepatrol-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('$page', 'POST',x_enable_plugins);		
		}		

		
		function EnableeCapAV(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_ecapav',value);
			 document.getElementById('ecapav-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}			
		
		

		function EnableCiCap(value){
			if(ThisisAClientStats()){return 1;}
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_c_icap',value);
			 document.getElementById('cicap-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}		
		function EnableMetaScan(value){
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_metascanner',value);
			 document.getElementById('kavmeta-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}		
		
		function JSEnableStreamCache(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_streamcache',value);
			 document.getElementById('stream-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins);	
		}		
		
		RefreshDansguardianMainService();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function ufdbguard_service_js(){
$page=CurrentPageName();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body("{web_proxy}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{APP_UFDBGUARD}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{parameters}");
echo "YahooWin('760','$page?ufdbguard=yes&width=100%','$title')";
}


function ufdbguard_service_section(){
	
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}				
	
	$array["ufdbguard-options"]='{service_parameters}';
	$array["ufdbguard-events"]='{service_events}';
	
	if($EnableRemoteStatisticsAppliance==0){
		$array["ufdbguard-blocked"]='{blocked_websites}';
	}
	

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		if($num=="ufdbguard-events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.admin.events.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}

		if($num=="ufdbguard-blocked"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.blocked.events.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_ufdbguards_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_ufdbguards_tabs').tabs();
			});
		</script>";	

}
function ufdbguard_service_options(){
	$sock=new sockets();
	$squid=new squidbee();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$width="650px";
	if(isset($_GET["width"])){$width="{$_GET["width"]}";}
	$template=Paragraphe("banned-template-64.png","{template_label}",'{template_explain}',"javascript:s_PopUp('dansguardian.template.php',800,800)"); 
	$squidguardweb=Paragraphe("parameters2-64.png","{banned_page_webservice}","{banned_page_webservice_text}","javascript:Loadjs('squidguardweb.php')");
	$ufdbguard_settings=Paragraphe("filter-sieve-64.png","{APP_UFDBGUARD}","{APP_UFDBGUARD_PARAMETERS}","javascript:Loadjs('ufdbguard.php')");
		
	$recompile_all_database=Paragraphe("database-spider-compile2-64.png",
	"{recompile_all_db}","{recompile_all_db_ww_text}","javascript:Loadjs('ufdbguard.databases.php?scripts=recompile')");
	
	$compile_schedule=Paragraphe("clock-gold-64.png","{compilation_schedule}","{compilation_schedule_text}","javascript:Loadjs('ufdbguard.databases.php?scripts=compile-schedule')");	
	
	$ufdbguard_conf=Paragraphe("script-64.png","ufdbguard.conf","{ufdbguard_conf_read_text}","javascript:Loadjs('ufdbguard.databases.php?scripts=config-file')");
	
	$cicap=Paragraphe('c-icap-64-grey.png','{APP_C_ICAP}','{feature_not_installed}',"");
	
	
	
	$youtubeSchools=Paragraphe('YoutubeSchools-64.png','Youtube For Schools','{YoutubeForSchoolsExplainT}',"javascript:Loadjs('squid.youtube-schools.php')");
	
	
	if($users->C_ICAP_INSTALLED){
		$cicap=Paragraphe('c-icap-64.png','{APP_C_ICAP}','{CICAP_SERVICE_TEXT}',"javascript:Loadjs('c-icap.index.php');");
	}	
	
	if($EnableWebProxyStatsAppliance==0){
		if($users->DANSGUARDIAN_INSTALLED){
			if($squid->enable_dansguardian==1){
				$template=null; 
				$squidguardweb=null;
				$ufdbguard_settings=null;
				$ufdbguard_conf=null;
			}
			
		}

		if(!$users->APP_UFDBGUARD_INSTALLED){
			$squidguardweb=null;
			$ufdbguard_settings=null;
			$ufdbguard_conf=null;			
			
		}
		
	}
	
	$streamCacheGet=Paragraphe("parametersytube-64.png","{streamGetService}","{streamGetService_text}","javascript:Loadjs('streamget-params.php')");
	$streamCacheGet_disabled=Paragraphe("parametersytube-64-grey.png","{streamGetService}","{streamGetService_text}","");
	$PagePeeker=Paragraphe("pagepeeker-64.png","PagePeeker","{pagepeeker_icon_text}","javascript:Loadjs('squid.pagepeeker.php')");
	
	if(!$users->SQUID_INSTALLED){$streamCacheGet=$streamCacheGet_disabled;}
	
	
	$tr[]=$ufdbguard_settings;
	$tr[]=$ufdbguard_conf;
	$tr[]=$cicap;
	$tr[]=$squidguardweb;
	$tr[]=$youtubeSchools;
	$tr[]=$streamCacheGet;
	$tr[]=$PagePeeker;
	$tr[]=$recompile_all_database;
	$tr[]=$compile_schedule;
	
	
	$html="<center><div style='width:$width'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	echo $html;			
	
}


function dansguardian_service_status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$squid=new squidbee();


	
	
	$CicapEnabled=$sock->GET_INFO('CicapEnabled');
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=0;}
	
	
	
	

	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$KAV4PROXY=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$CICAP=DAEMON_STATUS_ROUND("C-ICAP",$ini,null,1);
	
	
	$FRESHCLAM=DAEMON_STATUS_ROUND("FRESHCLAM",$ini,null,1);
	if($users->KASPERSKY_WEB_APPLIANCE){$FRESHCLAM=null;}
	if($users->KAV4PROXY_INSTALLED){$FRESHCLAM=null;}
	if(!$users->FRESHCLAM_INSTALLED){$FRESHCLAM=null;}
	if($CicapEnabled==0){$FRESHCLAM=null;}
	if($EnableClamavInCiCap==0){$FRESHCLAM=null;}
	
	
	$tr[]=$squid_status;
	$tr[]=$dansguardian_status;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$CICAP;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$KAV4PROXY;
	$tr[]=$FRESHCLAM;
	
	
	$html="$okFilterText
		<center style='padding-left:5px;margin-right:-5px'>
			<div id='nofilters'></div>
			<div style='width:550px'>".CompileTr2($tr)."</div>
		</center>
		<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","RefreshDansguardianMainService()")."</div>
	<script>
		LoadAjax('nofilters','$page?dansguardian-service_status-nofilters=yes');
		LoadAjax('dansguardian-statistics-status','squid.traffic.statistics.php?squid-status-stats=yes');
	</script>
	
	";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	echo $html;
	
}
function dansguardian_service_status_nofilters(){
	$sock=new sockets();
	$users=new usersMenus();
	$okFilter=false;
	$squid=new squidbee();
	$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	$KAV4PROXY_INSTALLED=0;
	$APP_UFDBGUARD_INSTALLED=0;
	$KAV4PROXY_INSTALLED=0;
	if($users->DANSGUARDIAN_INSTALLED){$KAV4PROXY_INSTALLED=1;}
	if($users->APP_UFDBGUARD_INSTALLED){$APP_UFDBGUARD_INSTALLED=1;}
	if($users->KAV4PROXY_INSTALLED){$KAV4PROXY_INSTALLED=1;}
		
	writelogs("EnableUfdbGuard=$EnableUfdbGuard; KAV4PROXY_INSTALLED:$KAV4PROXY_INSTALLED APP_UFDBGUARD_INSTALLED:$APP_UFDBGUARD_INSTALLED KAV4PROXY_INSTALLED:$KAV4PROXY_INSTALLED",__FUNCTION__,__FILE__,__LINE__);
	
	if($users->DANSGUARDIAN_INSTALLED){if($squid->enable_dansguardian==1){$okFilter=true;}}
	if($users->APP_UFDBGUARD_INSTALLED){if($EnableUfdbGuard==1){$okFilter=true;}}
	if($users->KAV4PROXY_INSTALLED){if($kavicapserverEnabled==1){$okFilter=true;}}
	if($okFilter){return;}
	

		$okFilterText="
		<center style='margin-bottom:20px'>
		<table style='width:90%' class=form>
			<tbody>
			<tr>
				<td width=1%><img src='img/warning-panneau-64.png'></td>
				<td style='font-size:14px'>
						{dansguardian_no_filters_explain}
						<div style='text-align:right;font-size:12px'>
							<a href=\"javascript:blur();\" style='text-align:right;font-size:12px' 
							OnClick=\"Loadjs('squid.popups.php?script=plugins')\">{click_here_to_enable_filters}</a>
						</div>
				</td>
			</tr>
			</tbody>
		</tr>
		</table>
		</center>
		
		";
		
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($okFilterText,'squid.index.php');	
	echo $html;

	
}
function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}	
	
	