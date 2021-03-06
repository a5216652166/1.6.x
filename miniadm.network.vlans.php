<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["search-vlans"])){vlans_search();exit;}
if(isset($_GET["vlanid-js"])){vlans_js();exit;}
if(isset($_GET["vlanid-popup"])){vlans_popup();exit;}
if(isset($_POST["ipaddr"])){vlans_save();exit;}
if(isset($_POST["vlan-delete"])){vlans_delete();exit;}
vlan_section();

function vlan_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$OPTIONS["BUTTONS"][]=button("{new_vlan}","Loadjs('$page?vlanid-js=0')",16);
	echo $boot->SearchFormGen(null,"search-vlans",null,$OPTIONS);


}
function vlans_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	header("content-type: application/x-javascript");
	$ID=$_GET["vlanid-js"];
	$title="{new_vlan}";
	if($ID>0){
		$title="VLAN::$ID";
	}
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2('700','$page?vlanid-popup=yes&ID=$ID','$title')";
	
}
function vlans_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	$ID=$_GET["ID"];
	$title_button="{add}";
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_vlan WHERE ID='$ID'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}
	$ous=$ldap->hash_get_ou(true);
	$ous["openvpn_service"]="{APP_OPENVPN}";
	while (list ($num, $val) = each ($nics) ){
		$nics_array[$val]=$val;
	}
	$nics_array[null]="{select}";
	$ous[null]="{select}";
	
	$boot->set_list("nic", "{nic}", $nics_array,$ligne["nic"]);
	$boot->set_list("org", "{organization}", $ous,$ligne["org"]);
	$boot->set_field("vlanid","VLAN ID",$ligne["vlanid"]);
	$boot->set_field("ipaddr","{tcp_address}",$ligne["ipaddr"],array("IPV4"=>true));
	$boot->set_field("netmask","{netmask}",$ligne["netmask"],array("IPV4"=>true));
	$boot->set_field("cdir","CDIR",$ligne["cdir"],array("CDIR"=>"ipaddr,netmask"));
	$boot->set_field("gateway","{gateway}",$ligne["gateway"],array("IPV4"=>true));
	$boot->set_field("metric","{metric}",$ligne["metric"]);
	$boot->set_hidden("ID", $_GET["ID"]);
	
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}


function vlans_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	
	if($_POST["netmask"]=='___.___.___.___'){$_POST["netmask"]="0.0.0.0";}
	if($_POST["gateway"]=='___.___.___.___'){$_POST["gateway"]="0.0.0.0";}
	if($_POST["ipaddr"]=='___.___.___.___'){$_POST["ipaddr"]="0.0.0.0";}
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE nics_vlan SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO nics_vlan (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("system.php?vlans-build=yes");
	
}
function vlans_delete(){
	$sock=new sockets();
	$sock->getFrameWork("system.php?vlans-delete={$_POST["vlan-delete"]}");	
}

function vlans_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$table="nics_vlan";
	$database="artica_backup";
	$t=time();
	
	$ORDER=$boot->TableOrder(array("ID"=>"DESC"));
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	
	$searchstring=string_to_flexquery("search-vlans");
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$ip=new IP();
		$cdir=$ligne["cdir"];
		$eth="{$ligne["nic"]}";
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		
		$img="folder-network-48.png";
		$edit=$boot->trswitch("Loadjs('$page?vlanid-js={$ligne["ID"]}')");
		$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		
		$a=$ip->parseCIDR($cdir);
		if($a[0]==0){
			$img="warning-panneau-24.png";
			$cdir="<span style='color:red'>$cdir</span>";
		}
		
		$tr[]="
		<tr id='$md'>
			<td style='font-size:18px' width=48px nowrap $edit><img src='img/$img'></td>
			<td style='font-size:18px' width=1% nowrap $edit>{$ligne["vlanid"]}</td>
			<td style='font-size:18px' width=90% nowrap $edit>{$ligne["org"]}</td>
			<td style='font-size:18px' width=10% nowrap $edit>$eth</td>
			<td style='font-size:18px' width=5% nowrap $edit>{$ligne["ipaddr"]}</td>
			<td style='font-size:18px' width=5% nowrap $edit>{$ligne["netmask"]}</td>
			<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
				
		
	}
	$delete_text=$tpl->javascript_parse_text("{delete}");
	echo $boot->TableCompile(array("vlanid"=>"ID:colspan=2",
			"org"=>" {organization}",
			"nic"=>"{nic}",
			"ipaddr"=>"{ipaddr}",
			"netmask"=>"{netmask}",
			"delete"=>null,
		),$tr)."
					
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text VLAN ID: '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('vlan-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>					
";
	
	
}