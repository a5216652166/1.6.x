<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.squid.bandwith.inc');
	
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["js"])){js();exit;}

	if(isset($_POST["choose-acl-rule"])){browse_acl_rule_save();exit;}
	
	if(isset($_GET["BandExplainRuleClass"])){bandwith_rule_class_explain();exit;}
	if(isset($_GET["rules"])){rules_popup();exit;}
	if(isset($_GET["rules-add"])){rules_add();exit;}
	if(isset($_GET["rules-del"])){rules_del();exit;}
	if(isset($_GET["rule_name"])){rules_save();exit;}
	if(isset($_GET["rule-id"])){rule_panel();exit;}
	
	
	if(isset($_GET["bandwith-rules-list"])){bandwith_rules_list();exit;}
	if(isset($_GET["bandwith-rule-js"])){bandwith_rule_js();exit;}
	if(isset($_GET["bandwith-rule-tabs"])){bandwith_rule_tabs();exit;}
	if(isset($_GET["bandwith-rule-parameters"])){rules_add();exit;}
	
	if(isset($_GET["bandwith-rule-networks"])){acl_net_popup();exit;}
	if(isset($_GET["bandwith-rule-websites"])){acl_www_popup();exit;}
	if(isset($_GET["bandwith-rule-files"])){acl_file_popup();exit;}
	if(isset($_GET["bandwith-rule-time"])){acl_time();exit;}
	
	if(isset($_GET["bandwith-rule-pobjects"])){acl_net_pobjects();exit;}
	if(isset($_GET["acl-group-add"])){acl_net_pobjects_add();exit;}
	if(isset($_GET["by-acls-js"])){byacls_js();exit;}
	if(isset($_GET["browser-acl-js"])){browser_acl_js();exit;}
	
	
	
	if(isset($_GET["bandwith-acl-list"])){bandwith_table_list();exit;}
	if(isset($_POST["acl-delete-item"])){bandwith_table_delete_item();exit;}
	if(isset($_POST["ENABLE-ITEM"])){bandwith_table_enable_item();exit;}
	
	
	
	if(isset($_GET["acl-time"])){acl_time();exit;}
	if(isset($_GET["bandacltime_ID"])){acl_time_save();exit;}
	
	if(isset($_GET["acl-net"])){acl_net_popup();exit;}
	if(isset($_GET["acl-net-popup-add"])){acl_net_add_popup();exit;}
	if(isset($_GET["acl-net-add"])){acl_net_add();exit;}
	if(isset($_GET["acl-net-enable"])){acl_net_enabled();exit;}
	
	if(isset($_GET["acl-www"])){acl_www_popup();exit;}
	if(isset($_GET["acl-www-add"])){acl_www_add();exit;}
	if(isset($_GET["acl-www-del"])){acl_www_del();exit;}
	if(isset($_GET["acl-www-enable"])){acl_www_enabled();exit;}
	
	
	if(isset($_GET["acl-file"])){acl_file_popup();exit;}
	if(isset($_GET["acl-file-list"])){acl_file_list();exit;}
	if(isset($_GET["acl-file-add"])){acl_file_add();exit;}
	if(isset($_GET["acl-file-del"])){acl_file_del();exit;}
	if(isset($_GET["acl-file-add-all"])){acl_file_add_all();exit;}
	if(isset($_GET["acl-file-enable"])){acl_file_enabled();exit;}
	if(isset($_GET["acl-file-del-all"])){acl_file_del_all();exit;}
	
	
	
popup();

function byacls_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{bandwith_limitation_full}");
	echo "YahooWinS('650','$page?by-acls=yes&table-source={$_GET["t"]}','$title')";
	
}
function browser_acl_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{browse}&raquo;{bandwith_limitation_full}");
	echo "YahooWinS('650','$page?by-acls=yes&table-source={$_GET["t"]}&choose=yes&aclruleid={$_GET["aclruleid"]}','$title')";	
	
}

function js(){
	
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page');";
}

function browse_acl_rule_save(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$aclname=$_POST["aclname-save"];
	
	if($ID<0){
		$sql="INSERT INTO webfilters_sqacls (aclname,enabled) VALUES ('$aclname',1)";
	}
	
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function bandwith_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if(isset($_GET["by-acls"])){$byacl="&by-acls=yes";}
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT rulename FROM squid_pools WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$title=$ligne["rulename"];
	}else{
		$title="{new_rule}";
	}	
	
	
	$title=$tpl->_ENGINE_parse_body(utf8_encode($title));
	$html="YahooWin('750','$page?bandwith-rule-tabs=yes&ID=$ID&t=$t$byacl','$title');";
	echo $html;		
}
function bandwith_rule_class_explain(){
	$classid=$_GET["BandExplainRuleClass"];
	if($classid==0){return;}
	$rules_class_explains[1]="{delay_class_1}";
	$rules_class_explains[2]="{delay_class_2}";
	$rules_class_explains[3]="{delay_class_3}";
	$rules_class_explains[4]="{delay_class_4}";
	$rules_class_explains[5]="{delay_class_5}";

	$html="<div style='font-size:14px' class=text-info><strong>{class} $classid:</strong><br>".$rules_class_explains[$classid]."</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function bandwith_rule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}	
	$t=$_GET["t"];
	
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$ID=$_GET["ID"];
	$array["parameters"]="{parameters}";
	$array["networks"]="{networks}";
	$array["pobjects"]="{proxy_objects}";
	
	 
	$array["websites"]="{websites}";
	$array["files"]="{by_file_type}";
	$array["time"]="{time_restriction}";
	
	if(isset($_GET["by-acls"])){
		unset($array);
		$array["parameters"]="{parameters}";
		
	}
	
	$fontsize=14;
	if(!is_numeric($t)){$t=time();}
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?bandwith-rule-$num=$t&ID=$ID&t=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}

	echo "
	<div id=main_bandwithrule_$ID style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_bandwithrule_$ID').tabs();
			});
		</script>";	

	
}


function bandwith_rules_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="squid_pools";
	$database="artica_backup";
	$t=$_GET["t"];
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("no data");
	}
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
	$results = $q->QUERY_SQL($sql,$database);
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show("$q->mysql_error");
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	

	$byacl=false;
	if(isset($_GET["by-acls"])){$byacl=true;$byaclToken="&by-acls=yes";}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$text=$tpl->_ENGINE_parse_body(rule_format_text($ID,$byacl));
		writelogs("{$ligne["ID"]} => {$ligne["rulename"]}",__FUNCTION__,__FILE__,__LINE__);
		$js="Loadjs('$MyPage?bandwith-rule-js=yes&ID={$ligne["ID"]}&t=$t$byaclToken')";
		
		$delete=imgsimple("delete-24.png",$ligne["rulename"],"DeleteBandRule({$ligne['ID']})");
		if(isset($_GET["choose"])){
			$rulenameTT=$tpl->javascript_parse_text($ligne["rulename"]);
			$delete=imgsimple("arrow-right-24.png",$ligne["rulename"],"ChooseBandwithAclRule({$ligne['ID']},{$_GET["aclruleid"]},'$rulenameTT')");
		}
	
		
		$color="black";
		if($ligne["enable"]==0){
			$color="#7B7B7B";
			if(isset($_GET["choose"])){
				$delete=imgsimple("arrow-right-24-grey.png",$ligne["rulename"],null);
			}
		}
		
		
		
		
		$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline;color:$color'>{$ligne["ID"]}</span>",
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline;color:$color'>{$ligne["rulename"]}</span>",
			"<span style='font-size:14px;color:$color'>$text</span>",$delete )
		);
		
		
	}
	
	
echo json_encode($data);			
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$new_rule=$tpl->_ENGINE_parse_body("{add_bandwith_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$squid_bandwith_rules_explain=$tpl->_ENGINE_parse_body("{squid_bandwith_rules_explain}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	
	$explain_size=528;
	$table_width=830;
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddBandRule},
	],";		
		
if(isset($_GET["by-acls"])){
	$byacl="&by-acls=yes";
	$explain_size=339;
	$table_width=630;
}

if(isset($_GET["choose"])){
	$choose="&choose=yes";
}
	
$html="
<div id='tableau-bandwith-regles' class=text-info style='font-size:14px'>$squid_bandwith_rules_explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?bandwith-rules-list=yes&t=$t$byacl$choose&aclruleid={$_GET["aclruleid"]}',
	dataType: 'json',
	colModel : [
		{display: 'ID', name : 'ID', width : 32, sortable : true, align: 'center'},
		{display: '$rulename', name : 'rulename', width : 140, sortable : true, align: 'left'},	
		{display: '$explain', name : 'ItemsNumber', width :$explain_size, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $table_width,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function AddBandRule(){
		YahooWin(650,'$page?rules-add=yes','$new_rule');
	}
	

	function FlexReloadRulesBandwith(){
		$('#flexRT$t').flexReload();
		var tsource='{$_GET["table-source"]}';
		if(tsource.length>0){
			$('#flexRT'+tsource).flexReload();
		}
	}
	
	function DeleteBandRule(ID){
		if(confirm('$delete_rule ?')){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
			XHR.appendData('rules-del','yes');
			XHR.sendAndLoad('$page', 'GET',x_DeleteBandRule);	
		}
	}

	
	function x_DeleteBandRule(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		FlexReloadRulesBandwith();
	}	

	function ChooseBandwithAclRule(bandid,aclid,ruletxt){
		document.getElementById('delay_access_id').value=bandid;
		document.getElementById('delay_access_id_text').innerHTML=ruletxt;
		document.getElementById('delay_access').checked=true;
		YahooWinSHide();
		limit_bandwidth_check();
		
		
	}



</script>

";	
	echo $html;
		
	return ;
	
	$page=CurrentPageName();
	$tpl=new templates();
	$edit_the_rule=$tpl->_ENGINE_parse_body("{edit_the_rule}");
	$add_rule=$tpl->_ENGINE_parse_body("{add_rule}");

	$time_restriction=$tpl->_ENGINE_parse_body("{time_restriction}");
	$networks=$tpl->_ENGINE_parse_body("{networks}");
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$BannedMimetype=$tpl->_ENGINE_parse_body("{BannedMimetype}");
	$by_file_type=$tpl->_ENGINE_parse_body("{by_file_type}");
	$html="
	
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1%><div id='SquidBandLeft'></div></td>
	<td valign='top' width=100%><div id='SquidBandRight'></div></td>
	</tr>
	</table>
	<script>
	function RefreshPanel(){
		LoadAjax('SquidBandLeft','$page?rules=yes');
		if(document.getElementById('right-panel-id')){
			var IDsel=document.getElementById('right-panel-id').value;
			if(IDsel>0){SquidBandRightPanel(IDsel);}
		}
		
	}
	
	function SquidBandRightPanel(ID){
		LoadAjax('SquidBandRight','$page?rule-id='+ID);
	}
	
	function EditBandRule(ID){
		YahooWin(500,'$page?rules-add=yes&ID='+ID,'$edit_the_rule');
	}
	
	function x_DeleteBandRule(obj){
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);}
				document.getElementById('SquidBandRight').innerHTML='';
				RefreshPanel();
	}	

	function BandAclTime(ID){
		YahooWin(500,'$page?acl-time=yes&ID='+ID,'$time_restriction');
	}
	
	function BandAclNet(ID){
		YahooWin(500,'$page?acl-net=yes&ID='+ID,'$networks');
	}	
	
	function BandAclWWW(ID){
		YahooWin(500,'$page?acl-www=yes&ID='+ID,'$websites');
	}		

	function BandAclMIME(ID){
		YahooWin(500,'$page?acl-mime=yes&ID='+ID,'$BannedMimetype');
	}	

	function BandAclFILE(ID){
		YahooWin(650,'$page?acl-file=yes&ID='+ID,'$by_file_type');
	}	
	RefreshPanel();
	</script>
	
	
	";
	
	echo $html;
}

//databases/extentions-mime.db

function rule_panel(){
	
	$sql="SELECT * FROM squid_pools WHERE ID={$_GET["rule-id"]}";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	$edit=Paragraphe("bandwith-limit-edit-64.png","{edit_the_rule}","{edit_the_rule} {$ligne["rulename"]}","javascript:EditBandRule({$_GET["rule-id"]})");
	$delete=Paragraphe("bandwith-limit-del-64.png","{delete_rule}","{delete_rule} {$ligne["rulename"]}",
	"javascript:DeleteBandRule({$_GET["rule-id"]})");
	
	$time=Paragraphe("64-planning.png","{time_restriction}","{squid_band_time_restriction_text}",
	"javascript:BandAclTime({$_GET["rule-id"]})");
	
	$net=Paragraphe("bandwith-limit-user-64.png","{networks}","{squid_band_net_restriction_text}",
	"javascript:BandAclNet({$_GET["rule-id"]})");
	
	$domains=Paragraphe("bandwith-limit-www-64.png","{websites}","{squid_band_www_restriction_text}",
	"javascript:BandAclWWW({$_GET["rule-id"]})");
	
	$file=Paragraphe("64-filetype.png","{by_file_type}","{squid_band_file_restriction_text}",
	"javascript:BandAclFILE({$_GET["rule-id"]})");	
	
	
	
	$tr[]=$edit;
	$tr[]=$delete;
	$tr[]=$net;
	$tr[]=$domains;
	$tr[]=$file;
	$tr[]=$time;
	
	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		
}
if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>";

	$maintext=rule_format_text($ligne["total_net"],$ligne["total_users"]);
	$s=new squid_bandwith_builder();
	$s->compile();
	$html=implode("\n",$tables)."
	<hr>
	<div class=text-info>".@implode("<br>",$s->rules_explain[$_GET["rule-id"]])." {then} $maintext</div>
	<input type='hidden' id='right-panel-id' value='{$_GET["rule-id"]}'>";
	
	
	
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");
	
}



function rules_popup(){
	
	

}

function compile_acls_datas($array,$keyforlog){
	if(!is_array($array)){writelogs("$keyforlog: Not an array....",__FUNCTION__,__FILE__,__LINE__); return false;}
	if(count($array)==0){writelogs("$keyforlog: Array = 0 items....",__FUNCTION__,__FILE__,__LINE__); return false;}
	$f=array();
	while (list ($ID, $net) = each ($array) ){
		if(trim($net)==null){continue;}
		$f[]=$net;
	}
	
	if(count($f)==0){return false;}
	return @implode(", ", $f);
}
function compile_acls_datas_groups($array,$keyforlog){
	$q2=new mysql_squid_builder();
	$tpl=new templates();
	if(!is_array($array)){writelogs("$keyforlog: Not an array....",__FUNCTION__,__FILE__,__LINE__); return false;}
	if(count($array)==0){writelogs("$keyforlog: Array = 0 items....",__FUNCTION__,__FILE__,__LINE__); return false;}
	$f=array();
	while (list ($ID, $net) = each ($array) ){
		if(trim($net)==null){continue;}
		if(!is_numeric($net)){continue;}
		$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$net'"));
		$gpname=utf8_encode($ligne2["GroupName"]);
		$Type=$q2->acl_GroupType[$ligne2["GroupType"]];
		$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$net'"));
		$itemsC=$ligne2["tcount"];
		$net=$tpl->_ENGINE_parse_body("<div style='margin-left:10px'>$gpname ($Type) - $itemsC {items}</div>");		
		$f[]=$net;
	}
	
	if(count($f)==0){return false;}
	return @implode("", $f);
}
function rule_format_text($ruleid,$byacl=false){
	
	
	$q=new mysql();
	$sql="SELECT * FROM squid_pools WHERE ID='$ruleid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total_net=$ligne["total_net"];
	$total_users=$ligne["total_users"];
	$t=explode("/",$total_net);
	$delay_pool_net=$t[0];
	$delay_pool_net=$delay_pool_net*8;
	$delay_pool_net=$delay_pool_net/1000;
	
	$t=explode("/",$total_users);
	$delay_pool_limit=$t[0];
	$delay_pool_limit=$delay_pool_limit*8;
	$delay_pool_limit=$delay_pool_limit/1000;	
	
	$delay_pool_max_file=$t[1];
	$delay_pool_max_file=$delay_pool_max_file*8;
	$delay_pool_max_file=$delay_pool_max_file/1000;		
	
	if(!$byacl){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$ruleid AND ACL_TYPE='SRC_RESTRICT' AND enabled=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
		$txt=compile_acls_datas($ACL_DATAS,"$ruleid -> SRC_RESTRICT");
		if($txt<>null){$aclInt1="<br><i>{and_only_on_specified_networks}:$txt</i>";}
		
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$ruleid AND ACL_TYPE='DOMAIN_RESTRICT' AND enabled=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
		$txt=compile_acls_datas($ACL_DATAS,"$ruleid -> DOMAIN_RESTRICT");
		if($txt<>null){$aclInt2="<br><i>{and_only_to_these_websites}:$txt</i>";}	
		
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$ruleid AND ACL_TYPE='FILE_RESTRICT' AND enabled=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
		$txt=compile_acls_datas($ACL_DATAS,"$ruleid -> FILE_RESTRICT");
		if($txt<>null){$aclInt3="<br><i>{and_only_when_downloading_files_with_extension}:$txt</i>";}	
		
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$ruleid AND ACL_TYPE='TIME_RESTRICT' AND enabled=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
		$txt=rule_format_time($ACL_DATAS,"$ruleid -> TIME_RESTRICT");
		if($txt<>null){$aclInt4="<br><i>{time}: $txt</i>";}	
	
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$ruleid AND ACL_TYPE='GROUP_RESTRICT' AND enabled=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
		$txt=compile_acls_datas_groups($ACL_DATAS,"$ruleid -> GROUP_RESTRICT");
		if($txt<>null){$aclInt5="<br><i>{proxy_objects}: $txt</i>";}	
	}
	
		
	return "{delay_pool_param_net} <strong>{$delay_pool_net}kb/s</strong>.<br>{delay_pool_param_user_max} {$delay_pool_max_file}kb/s<br> 
	{delay_pool_param_user_limit} {$delay_pool_limit}kb/s$aclInt1$aclInt2$aclInt3$aclInt4$aclInt5";	
	
	}
	
function rule_format_time($ACL_DATAS,$forlog=null){
	$tpl=new templates();
	if(!is_array($ACL_DATAS)){writelogs("$forlog: Not an array....",__FUNCTION__,__FILE__,__LINE__); return false;}
	if(count($ACL_DATAS)==0){writelogs("$forlog: Array = 0 items....",__FUNCTION__,__FILE__,__LINE__); return false;}	
	$cron=new cron_macros();
	while (list ($key, $day) = each ($cron->cron_squid) ){
		$value=$ACL_DATAS[$key];
		if($value<>1){continue;}
		$days[]=$tpl->_ENGINE_parse_body("{$day}");
		
	}
	
	if(strlen($ACL_DATAS["hour1"])==1){$ACL_DATAS["hour1"]="{$ACL_DATAS["hour1"]}0";}
	if(strlen($ACL_DATAS["min1"])==1){$ACL_DATAS["min1"]="{$ACL_DATAS["min1"]}0";}
	if(strlen($ACL_DATAS["hour2"])==1){$ACL_DATAS["hour2"]="{$ACL_DATAS["hour2"]}0";}
	if(strlen($ACL_DATAS["min2"])==1){$ACL_DATAS["min2"]="{$ACL_DATAS["min2"]}0";}
	$html="{from}:{$ACL_DATAS["hour1"]}:{$ACL_DATAS["min1"]} {to}:{$ACL_DATAS["hour2"]}:{$ACL_DATAS["min2"]} ". @implode(",", $days);
	return $html;	
		
	
}


function rules_add(){
	$button_title="{add}";
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	$enable=1;
	

	
	if($ID>0){
		$sql="SELECT * FROM squid_pools WHERE ID=$ID";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$rule_name=$ligne["rulename"];
		$t=explode("/",$ligne["total_net"]);
		$delay_pool_net=$t[0];
		$delay_pool_net=$delay_pool_net*8;
		$delay_pool_net=$delay_pool_net/1000;	

		$t=explode("/",$ligne["total_users"]);
		$delay_pool_limit=$t[0];
		$delay_pool_limit=$delay_pool_limit*8;
		$delay_pool_limit=$delay_pool_limit/1000;	
		
		$delay_pool_max_file=$t[1];
		$delay_pool_max_file=$delay_pool_max_file*8;
		$delay_pool_max_file=$delay_pool_max_file/1000;			
		$button_title="{apply}";
		$enable=$ligne["enable"];
		
		$rule_class=$ligne["rule_class"];

	}
	
		$rules_classes[1]="{class} 1";
		$rules_classes[2]="{class} 2";
		$rules_classes[3]="{class} 3";
		$rules_classes[4]="{class} 4";
		$rules_classes[5]="{class} 5";	

	if(!is_numeric($rule_class)){$rule_class=2;}
	$rule_name=utf8_encode($rule_name);
	$page=CurrentPageName();
	$html="
	<div id='DelayPoolDiv'>
	<input type='hidden' id='ID' value='$ID'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap style='font-size:14px'>{rule_name}:</td>
		<td style='font-size:14px'>". 
		Field_text("rule_name",$rule_name,'width:230px;font-size:16px;padding:3px')."</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:14px' nowrap>{activate_rule}:</td>
		<td style='font-size:13px'>". Field_checkbox("enable",1,$enable)."</td>
	</tr>		
	<tr>
		<td class=legend nowrap style='font-size:14px'>{delay_pool_param_net}:</td>
		<td style='font-size:14px'>". 
		Field_text("delay_pool_net",$delay_pool_net,'width:60px;font-size:14px;padding:3px')." KB/s</td>
	</tr>
	<tr>
	<td class=legend nowrap style='font-size:14px'>{delay_pool_param_user_max}:</td>	
	<td style='font-size:14px'>". 
		Field_text("delay_pool_max_file",$delay_pool_max_file,'width:60px;font-size:14px;padding:3px')." KB/s</td>
	</tr>
	
	<tr>
	<td class=legend nowrap style='font-size:14px'>{delay_pool_param_user_limit}:</td>	
	<td style='font-size:14px'>". 
		Field_text("delay_pool_limit",$delay_pool_limit,'width:60px;font-size:14px;padding:3px')." KB/s</td>
	</tr>	
	
	<tr>
	<td class=legend nowrap style='font-size:14px'>{class}:</td>	
	<td style='font-size:14px'>". 
		Field_array_Hash($rules_classes,"rule_class",$rule_class,"BandExplainRuleClass()",null,0,"font-size:14px")."</td>
	</tr>		
	
	<tr>
		<td colspan=2 align='right'><hr>". button($button_title,"SaveSquidBand()",16)."</td>
	</tr>		
	</table>
	</div>
	<span id='BandExplainRuleClassText'></span>
	<script>
		function x_SaveSquidBand(obj){
			var ID=$ID;
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			if(ID==0){YahooWinHide();}
			if(document.getElementById('tableau-bandwith-regles')){FlexReloadRulesBandwith();}
			if(document.getElementById('main_bandwithrule_$ID')){RefreshTab('main_bandwithrule_$ID');}
			if(document.getElementById('table-$t')){ $('#table-$t').flexReload(); }
			
		}			
			
		function SaveSquidBand(){
			var XHR = new XHRConnection();
			XHR.appendData('ID','$ID');
			
			if(document.getElementById('enable').checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
			XHR.appendData('rule_name',document.getElementById('rule_name').value);
			XHR.appendData('delay_pool_net',document.getElementById('delay_pool_net').value);
			XHR.appendData('delay_pool_max_file',document.getElementById('delay_pool_max_file').value);
			XHR.appendData('delay_pool_limit',document.getElementById('delay_pool_limit').value);
			XHR.appendData('rule_class',document.getElementById('rule_class').value);
			
			
			AnimateDiv('DelayPoolDiv');
			XHR.sendAndLoad('$page', 'GET',x_SaveSquidBand);	
			}
			

			
		function BandExplainRuleClass(){
			var BandExplainRuleClass=document.getElementById('rule_class').value;
			LoadAjaxTiny('BandExplainRuleClassText','$page?BandExplainRuleClass='+BandExplainRuleClass);
		}
			
	BandExplainRuleClass();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function rules_save(){
	$q=new mysql();
	$rulename=$q->mysql_real_escape_string2($_GET["rule_name"]);
	if($rulename==null){$rulename=$_GET["rule_name"];}
	$delay_pool_net=$_GET["delay_pool_net"];
	$delay_pool_net=$delay_pool_net*1000;
	$delay_pool_net=$delay_pool_net/8;
	
	$delay_pool_max_file=$_GET["delay_pool_max_file"];
	$delay_pool_max_file=$delay_pool_max_file*1000;
	$delay_pool_max_file=$delay_pool_max_file/8;
	
	$delay_pool_limit=$_GET["delay_pool_limit"];
	$delay_pool_limit=$delay_pool_limit*1000;
	$delay_pool_limit=$delay_pool_limit/8;	
	
	
	$delay_pool_net="$delay_pool_net/$delay_pool_net";
	$delay_pool_net2="$delay_pool_limit/$delay_pool_max_file";
	
	$rule_class=$_GET["rule_class"];
	if(!is_numeric($rule_class)){$rule_class=2;}
	
	$sql="INSERT INTO squid_pools (rulename,total_net,total_users,rule_class)
	VALUES('{$_GET["rule_name"]}','$delay_pool_net','$delay_pool_net2',$rule_class)";
	
	if($_GET["ID"]>0){
		$sql="UPDATE squid_pools
		SET rulename='$rulename',
		total_net='$delay_pool_net',
		total_users='$delay_pool_net2',
		rule_class=$rule_class,
		enable='{$_GET["enable"]}'
		WHERE ID={$_GET["ID"]}
		";
	}
	
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}
function rules_del(){
	$ID=$_GET["ID"];
	$sql="DELETE FROM squid_pools WHERE ID=$ID";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sql="DELETE FROM squid_pools_acls WHERE pool_id=$ID";	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function rule_name($ID){
	$sql="SELECT rulename FROM squid_pools WHERE ID=$ID";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	return $ligne["rulename"];
}

function acl_time(){
	$page=CurrentPageName();
	$pool_id=$_GET["ID"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='TIME_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
	if($ACL_DATAS["enable"]==null){$ACL_DATAS["enable"]="1";}
	$d="
	
	<table style='width:99%' class=form>";
	$cron=new cron_macros();
	while (list ($key, $day) = each ($cron->cron_squid) ){
		$value=$ACL_DATAS[$key];
		$d=$d."
		<tr>
			<td class=legend style='font-size:14px'>$day</td>
			<td>". Field_checkbox($key,1,$value)."</td>
		</tr>
		";
		$js[]="if(document.getElementById('$key').checked){XHR.appendData('$key',1);}else{XHR.appendData('$key',0);}";
	}
	
	$jsc=implode("\n",$js);
	
	$d=$d."</table>";
	
	$e="<table style='with:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{activate_rule}:</td>
		<td>". Field_checkbox("time_restriction_enable",1,$ACL_DATAS["enable"])."</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{from}:</td>
		<td>". Field_array_Hash($cron->cron_hours,"hour1",$ACL_DATAS["hour1"],null,null,0,'font-size:16px;padding:3px')."</td>
		<td style='font-size:13px'>:</td>
		<td>". Field_array_Hash($cron->cron_mins,"min1",$ACL_DATAS["min1"],null,null,0,'font-size:16px;padding:3px')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{to}:</td>
		<td>". Field_array_Hash($cron->cron_hours,"hour2",$ACL_DATAS["hour2"],null,null,0,'font-size:16px;padding:3px')."</td>
		<td style='font-size:16px'>:</td>
		<td>". Field_array_Hash($cron->cron_mins,"min2",$ACL_DATAS["min2"],null,null,0,'font-size:16px;padding:3px')."</td>
	</tr>	
	</table>";
	
	
	
	$html="
	<div id='BandAclTimeID'>
	<table style='width:100%'>
	<tr>
		<td valign='top'>$d</td>
		<td valign='top'>$e</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","BandAclTimeSave()",18)."</td>
	</tr>
	</table>
	
	</div>
	
	<script>
		function x_BandAclTimeSave(obj){
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);}
				RefreshTab('main_bandwithrule_$pool_id');
				FlexReloadRulesBandwith();
				}			
			
		function BandAclTimeSave(){
			var XHR = new XHRConnection();
			XHR.appendData('pool_id','$pool_id');
			XHR.appendData('bandacltime_ID','{$ligne["ID"]}');
			if(document.getElementById('time_restriction_enable').checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
			$jsc
			XHR.appendData('hour1',document.getElementById('hour1').value);
			XHR.appendData('min1',document.getElementById('min1').value);
			XHR.appendData('hour2',document.getElementById('hour2').value);
			XHR.appendData('min2',document.getElementById('min2').value);			
			document.getElementById('BandAclTimeID').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_BandAclTimeSave);	
			}		
</script>";
	
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function acl_time_save(){
	$pool_id=$_GET["pool_id"];
	$acl_time_id=$_GET["bandacltime_ID"];
	if($acl_time_id<1){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='TIME_RESTRICT'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$acl_time_id=$ligne["ID"];
	}	
	
	$datas=base64_encode(serialize($_GET));
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS,enabled) VALUES('$pool_id','TIME_RESTRICT','$datas','{$_GET["enable"]}')";
	if($acl_time_id>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas',enabled='{$_GET["enable"]}' WHERE ID='$acl_time_id'";
	}
	
	$q=new mysql();
	$q->CheckTablesSquid();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function acl_net_popup(){echo bandwith_table("SRC_RESTRICT",$_GET["ID"]);}

function acl_net_add_popup(){
	$tpl=new templates();
	$html="
	<div id='BandAclNetDivAdd'>
	<div class=text-info style='font-size:13px'>{SQUID_NETWORK_HELP}</div>
	<table style='width:99%' class=form>
	<tr>
	<td width=100%'>
	". Field_text("squid-band-acl-net-field",null,"font-size:18px;padding:3px;margin:10px",
	null,null,null,false,"BandAclNetAddCheck(event)")."
	</td>
	</tr>
	</table>
	</div>
	</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

function acl_net_pobjects(){echo bandwith_table("GROUP_RESTRICT",$_GET["ID"]);}
	
function acl_net_pobjects_add(){
	$pool_id=$_GET["pool_id"];
	$pattern=$_GET["acl-group-add"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='GROUP_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	$ACL_DATAS[]=$pattern;
	$datas=base64_encode(serialize($ACL_DATAS));
	
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS) VALUES('$pool_id','GROUP_RESTRICT','$datas')";
	if($ligne["ID"]>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");	
}

function acl_net_add(){
	$pool_id=$_GET["pool_id"];
	
	$pattern=$_GET["acl-net-add"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='SRC_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	$ACL_DATAS[]=$pattern;
	$datas=base64_encode(serialize($ACL_DATAS));
	
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS) VALUES('$pool_id','SRC_RESTRICT','$datas')";
	if($ligne["ID"]>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function acl_net_enabled(){
	$pool_id=$_GET["pool_id"];
	$index=$_GET["acl-net-enable"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='SRC_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	if($ligne["ID"]<1){echo "???\n";exit;}
	$sql="UPDATE squid_pools_acls SET enabled='$index' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}


function bandwith_table($ACL_TYPE,$pool_id){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$tt=$_GET["t"];

	$sql="SELECT enabled FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='$ACL_TYPE'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	if($ligne["enabled"]==null){$ligne["enabled"]="1";}	
	
	$t=time();
	
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$give_internet_domain_name=$tpl->javascript_parse_text("{give_internet_domain_name}");	
	$add_network=$tpl->_ENGINE_parse_body("{add_network}"); 
	$acl_file_type_add_popup=$tpl->_ENGINE_parse_body("{acl_file_type_add_popup}"); 
	$enable=$tpl->_ENGINE_parse_body("{enable}");
		
	$arrayTR["SRC_RESTRICT"]="{networks}";
	$arrayTR["DOMAIN_RESTRICT"]="{websites}";
	$arrayTR["FILE_RESTRICT"]="{by_file_type}";
	$arrayTR["GROUP_RESTRICT"]="{by_proxy_groups}";
	$arrayTR["time"]="{time_restriction}";
	$field=$tpl->_ENGINE_parse_body($arrayTR[$ACL_TYPE]);
	
	
	$arrayEXP["SRC_RESTRICT"]="{squid_band_net_restriction_text}";
	$arrayEXP["DOMAIN_RESTRICT"]="{squid_band_www_restriction_text}";
	$arrayEXP["FILE_RESTRICT"]="{squid_band_file_restriction_text}";
	$arrayEXP["GROUP_RESTRICT"]="{squid_band_aclgroup_restriction_text}";
		
	

	
	switch ($ACL_TYPE) {
		case "DOMAIN_RESTRICT":$jsbutton="BandAclWWWAddPopup";break;
		case "SRC_RESTRICT":$jsbutton="BandAclNetAddPopup";break;
		case "FILE_RESTRICT":$jsbutton="BandAclFileAddPopup";break;
		case "GROUP_RESTRICT":$jsbutton="BandAclPGroupAddPopup";break;
		
		
		
		default:
			;
		break;
	}
	
	$buttons="
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : $jsbutton},
	],";		
		
$mdACL_TYPE=md5($ACL_TYPE);
$arrayEXP[$ACL_TYPE]=$tpl->_ENGINE_parse_body($arrayEXP[$ACL_TYPE]);
$t=time();	
$html="
<div id='tableau-$mdACL_TYPE' class=text-info style='font-size:14px'>{$arrayEXP[$ACL_TYPE]}</div>
<div style='text-align:right'><table style='width:5%'>
<tbody>
<tr>
	<td class=legend style='font-size:16px'>$enable:</td>
	<td>". Field_checkbox("enable-$t", 1,$ligne["enabled"],"CheckEnable$t()")."</td>
</tr>
</table></div>

<table class='$mdACL_TYPE' style='display: none' id='$mdACL_TYPE' style='width:100%'></table>
<script>
$(document).ready(function(){
$('#$mdACL_TYPE').flexigrid({
	url: '$page?bandwith-acl-list=yes&ACL_TYPE=$ACL_TYPE&pool_id=$pool_id',
	dataType: 'json',
	colModel : [
		{display: '$field', name : 'rulename', width : 626, sortable : false, align: 'left'},	
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$field', name : 'rulename'},
		],
	sortname: '$field',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 690,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function BandAclWWWAddPopup(){
			var www=prompt('$give_internet_domain_name');
			if(www){
				var XHR = new XHRConnection();
				XHR.appendData('acl-www-add',www);
				XHR.appendData('pool_id','$pool_id');
				XHR.sendAndLoad('$page', 'GET',x_additem);
			}
		}
		
	function BandAclNetAddPopup(){
			YahooWin2('550','$page?acl-net-popup-add=yes','$add_network');
		}

	function CheckEnable$t(){
		var XHR = new XHRConnection();
		XHR.appendData('pool_id','$pool_id');
		XHR.appendData('ACL_TYPE','$ACL_TYPE');
		XHR.appendData('ENABLE-ITEM','yes');
		if(document.getElementById('enable-$t').checked){XHR.appendData('ENABLE-VALUE','1');}else{XHR.appendData('ENABLE-VALUE','0');}
		XHR.sendAndLoad('$page', 'POST',x_additemSilent);	
	}
		
	function BandAclDeleteItem(INDEX){
		var XHR = new XHRConnection();
		XHR.appendData('acl-delete-item','yes');
		XHR.appendData('pool_id','$pool_id');
		XHR.appendData('ACL_TYPE','$ACL_TYPE');
		XHR.appendData('INDEX',INDEX);
		XHR.sendAndLoad('$page', 'POST',x_additem);	
	}
	
	function BandAclNetAddCheck(e){if(checkEnter(e)){BandAclNetAdd();}}
		
	function BandAclNetAdd(){
		var XHR = new XHRConnection();
		XHR.appendData('acl-net-add',document.getElementById('squid-band-acl-net-field').value);
		XHR.appendData('pool_id','$pool_id');
		XHR.sendAndLoad('$page', 'GET',x_additem);	
	}

	function BandAclPGroupAddPopup(){
	
		Loadjs('squid.BrowseAclGroups.php?callback=BandAclPGroupAddPopupCallBack');
	}
	
	function BandAclPGroupAddPopupCallBack(gpid){
		var XHR = new XHRConnection();
		XHR.appendData('acl-group-add',gpid);
		XHR.appendData('pool_id','$pool_id');
		XHR.sendAndLoad('$page', 'GET',x_additem);
	}
	
	function BandAclFileAddPopup(){
		var ext=prompt('$acl_file_type_add_popup');
			if(ext){
				var XHR = new XHRConnection();
				XHR.appendData('acl-file-add',ext);
				XHR.appendData('pool_id','$pool_id');
				XHR.sendAndLoad('$page', 'GET',x_additem);
			}
		}

	function x_additemSilent(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		FlexReloadRulesBandwith();
		$('#$tt').flexReload();
		
	}		

	function x_additem(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		FlexReload$ACL_TYPE();
		YahooWin2Hide();
	}	

	function FlexReload$ACL_TYPE(){
		$('#$mdACL_TYPE').flexReload();
		$('#$tt').flexReload();
	}



</script>

";		
	
	return $html;
	
}

function bandwith_table_enable_item(){
	$sql="UPDATE squid_pools_acls SET enabled={$_POST["ENABLE-VALUE"]} WHERE pool_id={$_POST["pool_id"]} AND ACL_TYPE='{$_POST["ACL_TYPE"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
	
}

function bandwith_table_delete_item(){
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id={$_POST["pool_id"]} AND ACL_TYPE='{$_POST["ACL_TYPE"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));			
	unset($ACL_DATAS[$_POST["INDEX"]]);
	$ACL_DATAS_NEW=base64_encode(serialize($ACL_DATAS));
	$sql="UPDATE squid_pools_acls SET ACL_DATAS='$ACL_DATAS_NEW' WHERE pool_id={$_POST["pool_id"]} AND ACL_TYPE='{$_POST["ACL_TYPE"]}'";
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}


function bandwith_table_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="squid_pools";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id={$_GET["pool_id"]} AND ACL_TYPE='{$_GET["ACL_TYPE"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	
	
	if(!$q->ok){
		json_error_show($q->mysql_error);
	}
	
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));		
	
	if(count($ACL_DATAS)==0){
		json_error_show("no data");
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	
	

	
	$total=count($ACL_DATAS);
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	

	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	

		//<td>". Paragraphe("bandwith-limit-64.png","{$ligne["rulename"]}","$text","javascript:SquidBandRightPanel('{$ligne["ID"]}')")."</td>
		

	$q2=new mysql_squid_builder();
	
	while (list ($ID, $net) = each ($ACL_DATAS) ){
		
		$md5=md5($ID);
		$delete=imgsimple("delete-24.png","{delete} $net",
		"BandAclDeleteItem('$ID')");
		if($_GET["ACL_TYPE"]=="GROUP_RESTRICT"){
			
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$net'"));
			$gpname=utf8_encode($ligne2["GroupName"]);
			$Type=$q2->acl_GroupType[$ligne2["GroupType"]];
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$net'"));
			$itemsC=$ligne2["tcount"];
			$net=$tpl->_ENGINE_parse_body("$gpname ($Type) - $itemsC {items}");
		}
		
		$data['rows'][] = array(
		'id' => $ID,
		'cell' => array("<span style='font-size:16px;'>$net</span>",$delete)
		);
		
		
	}
	
	
echo json_encode($data);		
	
}


function acl_www_popup(){echo bandwith_table("DOMAIN_RESTRICT",$_GET["ID"]);}
function acl_www_add(){
	$pool_id=$_GET["pool_id"];
	$pattern=$_GET["acl-www-add"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='DOMAIN_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	$ACL_DATAS[]=$pattern;
	$datas=base64_encode(serialize($ACL_DATAS));
	
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS) VALUES('$pool_id','DOMAIN_RESTRICT','$datas')";
	if($ligne["ID"]>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}
function acl_www_del(){
	$pool_id=$_GET["pool_id"];
	$index=$_GET["acl-www-del"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='DOMAIN_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	unset($ACL_DATAS[$index]);
	$datas=base64_encode(serialize($ACL_DATAS));
	if($ligne["ID"]<1){echo "???\n";exit;}
	$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}
function acl_www_enabled(){
	$pool_id=$_GET["pool_id"];
	$index=$_GET["acl-www-enable"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='DOMAIN_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if($ligne["ID"]<1){echo "???\n";exit;}
	$sql="UPDATE squid_pools_acls SET enabled='$index' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function acl_file_popup(){echo bandwith_table("FILE_RESTRICT",$_GET["ID"]);}	
	
	
	
function acl_file_list(){
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id={$_GET["acl-file-list"]} AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	
	$html="
	<table class=tableView style='width:95%'>
		<thead class=thead>
			<tr>
				<th width=1% nowrap colspan=3>{by_file_type}:</td>
			</tr>
		</thead>";	
	
	if(is_array($ACL_DATAS)){
	while (list ($key, $file) = each ($ACL_DATAS) ){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		if($_SESSION["FILES_TYPE"][$file]==null){	
			if(is_file("img/ext/{$file}_small.gif")){
				$_SESSION["FILES_TYPE"][$file]="img/ext/{$file}_small.gif";}else{
			}
		}	

		if($_SESSION["FILES_TYPE"][$file]==null){$_SESSION["FILES_TYPE"][$file]="img/ext/ico_small.gif";}
		
		$html=$html."
		<tr class=$cl>
			<td width=1%><img src='{$_SESSION["FILES_TYPE"][$file]}'></td>
			<td width=99%><code style='font-size:14px'>$file</td>
			<td width=1%>". imgtootltip("delete-24.png","{delete}","BandAclFileDel('$key')")."</td>
		</tr>";
		}
	}
		
	$html=$html."</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}
function acl_file_add(){
	$pool_id=$_GET["pool_id"];
	$pattern=$_GET["acl-file-add"];
	
	if(strpos($pattern,',')>0){$tbl=explode(",",$pattern);}else{$tbl[]=$pattern;}
	
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	
	while (list ($index, $file) = each ($tbl) ){
		$ACL_DATAS[]=$file;
	}
	$datas=base64_encode(serialize($ACL_DATAS));
	
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS) VALUES('$pool_id','FILE_RESTRICT','$datas')";
	if($ligne["ID"]>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function acl_file_add_all(){
	$pool_id=$_GET["pool_id"];
	$pattern="asf|avi|m1v|mp2|mp2v|mpa|flv|x-flv|mpe|mpeg|mpg|mpv2|wmv|dat|mkv|div|divx|ac3|dts|vob|dvr-ms|mp4|m2v|vro|rm|3gp|ram|raw|qt|mov|svcd|xdiv|m4v|m2ts|bup|3gpp|3g2|3gp2|3mm|aep|ajp|amv|amx|arf|asf|avs|d2v|d3v|dmb|dxr|
dvx|f4v|dv|bsf|rmvb|rv|aif|aifc|aiff|au|mid|midi|mp3|rmi|snd|wav|wma|vqf|aaf|ogg|srf|tga|hdf|wbmp|wmf|x3f|xbm|xpm|cr2|crw|dcr|djvu|emf|fpx|icl|icn|mrw|nef|orf|pbm|pcd|pef|pgm|plp|ppm|raf|ras|raw|rs|exe|msi|rpm|bin|dmg|cab|ace|arj|bzip2|cab|gzip|lzh|lzw|tar|tbz|gz|jar|tgz|uue|iso|7-zip|rar|alz|nrg|zip|";
	
	$tbl=explode("|",$pattern);
	
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	
	while (list ($index, $file) = each ($ACL_DATAS) ){
		$ff[$file]=$file;
	}
	
	while (list ($index, $file) = each ($tbl) ){
		$ff[$file]=$file;
	}	

	
	while (list ($index, $file) = each ($ff) ){
		$new[]=$file;
	}	
	
	$datas=base64_encode(serialize($new));
	
	$sql="INSERT INTO squid_pools_acls (pool_id,ACL_TYPE,ACL_DATAS) VALUES('$pool_id','FILE_RESTRICT','$datas')";
	if($ligne["ID"]>0){
		$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");	
}


function acl_file_del(){
	$pool_id=$_GET["pool_id"];
	$index=$_GET["acl-file-del"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	unset($ACL_DATAS[$index]);
	$datas=base64_encode(serialize($ACL_DATAS));
	if($ligne["ID"]<1){echo "???\n";exit;}
	$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");	
}
function acl_file_del_all(){
	$pool_id=$_GET["pool_id"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));	
	unset($ACL_DATAS);
	$datas=base64_encode(serialize($ACL_DATAS));
	if($ligne["ID"]<1){echo "???\nID={$ligne["ID"]}\n";exit;}
	$sql="UPDATE squid_pools_acls SET ACL_DATAS='$datas' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");
	
}


function acl_file_enabled(){
	$pool_id=$_GET["pool_id"];
	$index=$_GET["acl-file-enable"];
	$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if($ligne["ID"]<1){echo "???\n";exit;}
	$sql="UPDATE squid_pools_acls SET enabled='$index' WHERE ID='{$ligne["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();$sock->getFrameWork("cmd.php?squid-reload=yes");		
}

?>