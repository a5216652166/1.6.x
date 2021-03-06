<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}

if(isset($_GET["graph1-1"])){graph1_1();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){section_graphs();exit;}
if(isset($_GET["www-graĥs-members"])){section_graphs_members();exit;}

if(isset($_GET["www-table"])){section_table_sites();exit;}
if(isset($_GET["www-members"])){section_members_sites();exit;}
if(isset($_GET["sitename-search"])){table_sites_search();exit;}
if(isset($_GET["uid-search"])){uid_search();exit;}


js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$dateT=time_to_date($_GET["xtime"]);
	$category=urlencode($_GET["category"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$title="{category} {$_GET["category"]} $dateT";
	$title=$tpl->javascript_parse_text("$title");
	$html="YahooWin3('900','$page?tabs=yes&category=$category&xtime={$_GET["xtime"]}','$title')";
	echo $html;
	
}

function tabs(){
	//$table=date("Ymd",$_GET["xtime"])."_hour";
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$title=$tpl->_ENGINE_parse_body("$date {category} {$_GET["category"]}");
	$category=urlencode($_GET["category"]);
	$array["{graphs} {websites}"]="$page?www-graĥs=yes&category=$category&xtime={$_GET["xtime"]}";
	$array["{graphs} {members}"]="$page?www-graĥs-members=yes&category=$category&xtime={$_GET["xtime"]}";
	$array["{websites}"]="$page?www-table=yes&category=$category&xtime={$_GET["xtime"]}";
	$array["{members}"]="$page?www-members=yes&category=$category&xtime={$_GET["xtime"]}";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);	
	
}
function suffix(){
	$t=$_GET["t"];
	$category=urlencode($_GET["category"]);
	$suffix="&t=$t&xtime={$_GET["xtime"]}&category=$category";
	return $suffix;
}


function section_graphs(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph1-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>
	<div class=BodyContent id='graph3-$t'></div>
	
	
	<script>
		AnimateDiv('graph-$t');
		AnimateDiv('graph1-$t');
		AnimateDiv('graph2-$t');
		AnimateDiv('graph3-$t');
		Loadjs('$page?graph1=yes&container=graph-$t$suffix');
		Loadjs('$page?graph1-1=yes&container=graph1-$t$suffix');
		Loadjs('$page?graph2=yes&container=graph2-$t$suffix');
		Loadjs('$page?graph3=yes&container=graph3-$t$suffix');
	</script>
	";
	
	echo $html;
	
}
function section_graphs_members(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>
	
	
	
	<script>
	AnimateDiv('graph-$t');
	AnimateDiv('graph2-$t');
	
	Loadjs('$page?graph4=yes&container=graph-$t$suffix');
	Loadjs('$page?graph5=yes&container=graph2-$t$suffix');
	</script>
	";
	
	echo $html;	
	
}

function section_table_sites(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("sitename","sitename-search",$suffix);
	echo $form;	
	
	
}
function section_members_sites(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("uid","uid-search",$suffix);
	echo $form;	
	
	
}




function uid_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$database="squidlogs";
	$search='%';
	$table=date("Ymd",$_GET["xtime"])."_hour";
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$ORDER="ORDER BY size DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("uid-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$category=mysql_escape_string2($_GET["category"]);
	$sql="SELECT SUM(size) as size, sum(hits) as hits,uid,category FROM $table
	GROUP BY uid,category HAVING `category`='$category' $search $ORDER LIMIT 0,500";
	
	
	
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
	
	
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</a></td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	}
	
		echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{member}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";
	
	
}

function table_sites_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$database="squidlogs";
	$search='%';
	$table=date("Ymd",$_GET["xtime"])."_hour";
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$ORDER="ORDER BY size DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("sitename-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$category=mysql_escape_string2($_GET["category"]);
	$sql="SELECT SUM(size) as size, sum(hits) as hits,sitename,category FROM $table
	GROUP BY sitename,category HAVING `category`='$category' $search $ORDER LIMIT 0,500";
	

	
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
	
			$jsSitename=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayBySiteName.php?sitename={$ligne["sitename"]}&xtime={$_GET["xtime"]}')");
			$tr[]="
				<tr id='$id'>
					<td $jsSitename><i class='icon-globe'></i>&nbsp;{$ligne["sitename"]}</a></td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
				</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";	
	
	
}


function graph1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);	
	
	
	if($_GET["category"]=="unknown"){$_GET["category"]=null;}
	
	$sql="SELECT SUM(hits) as thits, `hour`,`category` FROM $tablename GROUP BY `hour`,`category` 
	HAVING `category`='{$_GET["category"]}'
	ORDER BY `hour`";



	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){

		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["thits"];
			$c++;
		}
	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="$category: {hits}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{hits}"=>$ydata);
	echo $highcharts->BuildChart();


}
function graph1_1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);
	
	
	if($_GET["category"]=="unknown"){$_GET["category"]=null;}
	
	$sql="SELECT SUM(size) as size, `hour`,`category` FROM $tablename GROUP BY `hour`,`category`
	HAVING `category`='{$_GET["category"]}'
	ORDER BY `hour`";
	
	
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){
	
	$nb_events=mysql_num_rows($results);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$xdata[]=$ligne["hour"];
	$ligne["size"]=round(($ligne["size"]/1024)/1000);
	$ydata[]=$ligne["size"];
	$c++;
	}
	}
	
	$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->xAxis=$xdata;
		$highcharts->Title="$category: {size}/{hour}";
		$highcharts->yAxisTtitle="{size}";
		$highcharts->xAxisTtitle="{hours}";
		$highcharts->datas=array("{size}"=>$ydata);
		echo $highcharts->BuildChart();	
	
	
}


function graph2(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);	
	
	$sql="SELECT SUM(hits) as thits, category,sitename FROM `$tablename` 
	GROUP BY category,sitename 
	HAVING category='$category'
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["sitename"])==null){$ligne["sitename"]=$unknown;}
		$PieData[$ligne["sitename"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("$category: {top_websites_by_hits}");
	echo $highcharts->BuildChart();

}

function graph3(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);	

	$sql="SELECT SUM(size) as thits, category,sitename FROM `$tablename`
	GROUP BY category,sitename 
	HAVING category='$category'
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	if(trim($ligne["sitename"])==null){$ligne["sitename"]=$unknown;}
	$ligne["thits"]=round(($ligne["thits"]/1024)/1000);
	$PieData[$ligne["sitename"]]=$ligne["thits"];
	$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("$category: {top_websites_by_size} (MB)");
	echo $highcharts->BuildChart();

}
function graph4(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);
	$sql="SELECT SUM(hits) as thits, category,uid FROM `$tablename`
	GROUP BY category,uid 
	HAVING category='$category'
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	if(trim($ligne["uid"])==null){$ligne["uid"]=$unknown;}
	$PieData[$ligne["uid"]]=$ligne["thits"];
	$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
			$highcharts->PieDatas=$PieData;
			$highcharts->ChartType="pie";
			$highcharts->PiePlotTitle="{hits}";
			$highcharts->Title=$tpl->_ENGINE_parse_body("$category: {top_members_by_hits}");
			echo $highcharts->BuildChart();

}


function graph5(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string2($_GET["category"]);
	
	$sql="SELECT SUM(size) as thits, category,uid FROM `$tablename`
	GROUP BY category,uid 
	HAVING category='$category'
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["uid"])==null){$ligne["uid"]=$unknown;}
		$ligne["thits"]=round(($ligne["thits"]/1024)/1000);
		$PieData[$ligne["uid"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("$category: {top_members_by_size} (MB)");
	echo $highcharts->BuildChart();

}