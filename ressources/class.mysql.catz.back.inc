<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
if(function_exists("debug_mem")){debug_mem();}
class mysql_catz{
	var $ClassSQL;
	private $called='';
	private $mysql_connection;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="catz";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	var $affected_rows=0;
	
	var $SocketPath=null;
	var $TryTCP=true;	
	var $DisableArticaProxyStatistics=0;
	var $arrayConfig=array();
	function mysql_catz($nomysql=false){
		if(!$nomysql){
			$sock=new sockets();
			$this->arrayConfig["mysql_admin"]="root";
			$this->arrayConfig["SocketPath"]="/var/run/mysqld/articadb.sock";
			$this->arrayConfig["TryTCP"]=false;
			$this->DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
			$this->EnableArticaDB=$sock->GET_INFO("EnableArticaDB");
			if(!is_numeric($this->DisableArticaProxyStatistics)){$this->DisableArticaProxyStatistics=0;}
			if(!is_numeric($this->EnableArticaDB)){$this->EnableArticaDB=1;}
			
			if($this->EnableRemoteStatisticsAppliance==1){$this->DisableArticaProxyStatistics=1;}
			if($this->EnableArticaDB==0){$this->DisableArticaProxyStatistics=1;}
			if($this->DisableArticaProxyStatistics==1){return;}			
			
			unset($GLOBALS["MYSQL_PARAMETERS"]);
			unset($_SESSION["MYSQL_PARAMETERS"]);
			
			$this->ClassSQL=new mysql($this->arrayConfig);
			$this->UseMysql=$this->ClassSQL->UseMysql;
			$this->mysql_admin=$this->ClassSQL->mysql_admin;
			$this->mysql_password=$this->ClassSQL->mysql_password;
			$this->ClassSQL->SocketPath="/var/run/mysqld/articadb.sock";
			$this->ClassSQL->TryTCP=false;
			

			
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$this->called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]} ";
				}
			}			
			
			if(!$this->TestingConnection()){$this->MysqlFailed=true;return;}
			
			if(!$this->DATABASE_EXISTS($this->database)){
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$this->database no such DB</span><br>\n";}
				$this->MysqlFailed=true;
				return;
			}
			if(function_exists("debug_mem")){debug_mem();}
			
		}
		
	}
	
	public function SHOW_VARIABLES(){
		if($this->DisableArticaProxyStatistics==1){return;}
		$this->TestingConnection();
		return $this->ClassSQL->SHOW_VARIABLES();
	}	
	
	public function TestingConnection(){
		if($this->DisableArticaProxyStatistics==1){return;}
		$this->ok=true;
		$bd=@mysql_connect(":/var/run/mysqld/articadb.sock",root,null);
		
		if($bd){
			$this->mysql_connection=$bd;
			$this->ok=true;
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>/var/run/mysqld/articadb.sock OK</span><br>\n";}
			return true;
		}
		$des=@mysql_error(); $errnum=@mysql_errno();
		$this->mysql_error=$this->called." error: $errnum $des";
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$this->mysql_error</span><br>\n";}
		return false;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->TestingConnection()){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>TestingConnection failed</span><br>\n";}
			return false;
		}
	
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$sql</span><br>\n";}
		if($GLOBALS["VERBOSE"]){$results=mysql_query($sql,$this->mysql_connection);}
		$results=@mysql_query($sql,$this->mysql_connection);
		
		if(!$results){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$this->SocketPath:$this->mysql_error</span><br>\n";}
		}
		
		if(mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des) <hr>$sql";
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>$this->SocketPath:$this->mysql_error</span><br>\n";}
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;
			return false;
		}
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$sql: OK</span><br>\n";}
		$this->ok=true;
		return $results;
	}	
	
	public function COUNT_ROWS($table,$database=null){
		if($this->DisableArticaProxyStatistics==1){return 0;}
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		$sql="show TABLE STATUS WHERE Name='$table'";
		if(!function_exists("mysql_fetch_array")){return 0;}
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		
		return $ligne["Rows"];
		
	}
	
	
	public function TABLE_SIZE($table,$database=null){
		if($this->DisableArticaProxyStatistics==1){return 0;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_SIZE($table,$database);		
	}
	
	public function TABLE_EXISTS($table,$database=null,$nocache=false){
		if($this->DisableArticaProxyStatistics==1){return true;}
		$keyCache=__FUNCTION__;
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if($database==null){$database=$this->database;}
		if(!$nocache){
			if(!isset($GLOBALS[$keyCache])){$GLOBALS[$keyCache]=array();}
			if(isset($GLOBALS[$keyCache][$database][$table])){if($GLOBALS[$keyCache][$database][$table]==true){return true;}}
			if(isset($_SESSION[$keyCache][$database][$table])){if($_SESSION[$keyCache][$database][$table]==true){return true;}}
		}
		
		if(!$this->DATABASE_EXISTS($database)){
			writelogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			if(!$this->CREATE_DATABASE($database)){
				writelogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		
		
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database'";
		$results=$this->QUERY_SQL($sql,$database);
		if(!$this->ok){writelogs($this->mysql_error,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$result=false;

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS[$keyCache][$database][$ligne["c"]]=true;
			if(!$GLOBALS["AS_ROOT"]){$_SESSION[$keyCache][$database][$ligne["c"]]=true;}
			if(strtolower($table)==strtolower($ligne["c"])){
				
				$result=true;}
		}
		
		return $result;
		
	
		
	}
	private function DATABASE_EXISTS($database){
		if($this->DisableArticaProxyStatistics==1){return true;}
		$this->ClassSQL=new mysql($this->arrayConfig);
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database,true);
	}
	
	private function FIELD_EXISTS($table,$field,$database=null){
		if($this->DisableArticaProxyStatistics==1){return true;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}
	
	public function QUERY_SQL($sql,$database=null){
		if($this->DisableArticaProxyStatistics==1){return null;}
		if($database<>$this->database){$database=$this->database;}
		$this->ClassSQL=new mysql($this->arrayConfig);
		$results=$this->ClassSQL->QUERY_SQL($sql,$database,$this->called);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->called.$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		$this->affected_rows=$this->ClassSQL->affected_rows;
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($this->DisableArticaProxyStatistics==1){return null;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	function category_transform_name($category){
			if($category=="teans"){$category="teens";}
			if($category=="forum"){$category="forums";}
			if($category=="gambling"){$category="gamble";}
			if($category=="radio"){$category="webradio";}
			$category=str_replace('/',"_",$category);
			$category=str_replace('-',"_",$category);
			return $category;	
	}

	
	function GET_CATEGORIES($sitename){
		if($this->DisableArticaProxyStatistics==1){return null;}
		$keyMem=date("Ymdh");
		$orgsitename=$sitename;
		$BADCATZ["category_drogue"]=true;
		$BADCATZ["category_english_malware"]=true;
		$BADCATZ["category_forum"]=true;
		$BADCATZ["category_gambling"]=true;
		$BADCATZ["category_hobby_games"]=true;
		$BADCATZ["category_phishtank"]=true;
		$BADCATZ["category_radio"]=true;
		$BADCATZ["category_radiotv"]=true;
		$BADCATZ["category_spywmare"]=true;	
		
		$orgsite=$sitename;
		$debug=false;
		if(isset($_POST["WEBTESTS"])){$debug=true;}
		$GLOBALS["CATEGORIZELOGS"]=array();
		if(isset($GLOBALS["GET_CATEGORIES_MEMORY"][$sitename])){return $GLOBALS["GET_CATEGORIES_MEMORY"][$sitename];}
		$cat=array();
		$cattmp=array();
		if(trim($sitename)==null){return;}
		$sitename=strtolower(trim($sitename));
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		
		if(substr($sitename, 0,1)=="."){$sitename=substr($sitename, 1,strlen($sitename));}
		$orgsite=$sitename;
		$sitename=md5($sitename);
		if($debug){echo "$orgsite = $sitename\n";}
		$trans=$this->TransArray();
		if(!isset($GLOBALS["TABLESCATEZ$keyMem"])){
			$GLOBALS["TABLESCATEZ$keyMem"]=$this->LIST_TABLES_CATEGORIES(true);
		}	
		$tablescat_count=count($GLOBALS["TABLESCATEZ$keyMem"]);
		$t1=time();
		$tablescat=$GLOBALS["TABLESCATEZ$keyMem"];
		while (list ($table, $none) = each ($tablescat) ){
			$category=null;
			if($table=="category_"){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (category_)\n";}
				continue;}
			if(isset($BADCATZ[$table])){continue;}
			if(preg_match("#bak$#", $table)){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (bak)\n";}
				continue;}
			if(isset($GLOBALS["TABLEINEXISTS"][$table])){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) bad $table (TABLEINEXISTS)\n";}
				continue;}
			if(!$this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "qz->GET_CATEGORIES($sitename) no such table $table\n";}
				$GLOBALS["TABLEINEXISTS"][$table]=true;
				continue;
			}
			
			$t=time();
			
			if(isset($trans[$table])){ $category=$trans[$table]; }
			if($category==null){
				if($GLOBALS["VERBOSE"]){ echo "Cannot translate $table to category\n";}
				continue;
			}
			
			$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT zmd5 FROM $table WHERE zmd5='$sitename'"));
			if($GLOBALS["VERBOSE"]){
				$took=distanceOfTimeInWords($t,time(),true); 
				echo "ArticaDB:: GET_CATEGORIES_DB($sitename) = $table/$category -> $sitename {$ligne["zmd5"]} took $took<br>\n";
			}
			
			$tablescat_count++;
			if($ligne["zmd5"]<>null){
				if($debug){echo "ArticaDB::$orgsite: \"$category\" [".__LINE__."]\n";}
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: Found $category FOR \"$sitename\" in ". __CLASS__ ." line: ".__LINE__."\n";}
				$cattmp[$category]=$category;
			}
			
		
		
		
		}	

		$t2=time();
		$time=$t2-$t1;
		if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES:: $orgsitename ".count($cattmp)." {$time}s<br>\n";}
		if(count($cattmp)==0){return null;}
		
		
		if(count($cattmp)>0){
			while (list ($a, $b) = each ($cattmp) ){if($b<>null){$cat[]=$b;}}
		}	
		if(count($cat)==0){return null;}
		$final=@implode(",", $cat);
		if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES:: $orgsitename return ### $final ###\n";}
		return $final;
	}
	
	public function LIST_TABLES_CATEGORIES($nocache=false){
		if($this->DisableArticaProxyStatistics==1){return array();}
		if(!$nocache){
			if(!$GLOBALS["AS_ROOT"]){
				if(isset($_SESSION["CATZLIST_TABLES_CATEGORIES"])){return $_SESSION["CATZLIST_TABLES_CATEGORIES"];}
			}
			if(isset($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])){return $GLOBALS["LIST_TABLES_CATEGORIES_CATZ"];}
		}
		
		$f=$this->TransArray();
		while (list ($table, $none) = each ($f) ){	
			if($this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` exists...\n";}
				
				$array[$table]=$table;
			}else{
				
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` no such table...\n";}
			}
		}
		
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'category_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){if($ligne["c"]=="category_"){$this->QUERY_SQL("DROP TABLE `category_`");continue;}$array[$ligne["c"]]=$ligne["c"];}
		
		if(count($array)>0){
			if(!$GLOBALS["AS_ROOT"]){
				$_SESSION["CATZLIST_TABLES_CATEGORIES"]=$array;
			}
		}
		$GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]=$array;
		return $array;
		
	}	
	
	function COUNT_CATEGORIES(){
		if($this->DisableArticaProxyStatistics==1){return 0;}
		if(!$GLOBALS["AS_ROOT"]){
			if(isset($_SESSION["CATZLIST_COUNT_CATEGORIES"])){return $_SESSION["CATZLIST_COUNT_CATEGORIES"];}
		}
		
		$c=0;
		$tablescat=$this->LIST_TABLES_CATEGORIES();
		while (list ($table, $none) = each ($tablescat) ){		
			$count=$this->COUNT_ROWS($table);
			$c=$c+$count;
		}
		
		if($c>0){
			if(!$GLOBALS["AS_ROOT"]){
				$_SESSION["CATZLIST_COUNT_CATEGORIES"]=$c;
			}
		}
		
		return $c;
	}
	
	FUNCTION DELETE_TABLE($table){
		if($this->DisableArticaProxyStatistics==1){return array();}
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
	}		

function TransArray(){
		
			$trans["category_society"]="society";
			$trans["category_association"]="associations";
			$trans["category_publicite"]="publicite";
			$trans["category_phishtank"]="phishtank";
			$trans["category_shopping"]="shopping";
			$trans["category_abortion"]="abortion";
			$trans["category_agressive"]="agressive";
			$trans["category_alcohol"]="alcohol";
			$trans["category_animals"]="animals";
			$trans["category_associations"]="associations";
			$trans["category_astrology"]="astrology";
			$trans["category_audio_video"]="audio-video";
			$trans["category_automobile_bikes"]="automobile/bikes";
			$trans["category_automobile_boats"]="automobile/boats";
			$trans["category_automobile_carpool"]="automobile/carpool";
			$trans["category_automobile_cars"]="automobile/cars";
			$trans["category_automobile_planes"]="automobile/planes";
			$trans["category_bicycle"]="bicycle";
			$trans["category_blog"]="blog";
			$trans["category_books"]="books";
			$trans["category_browsersplugins"]="browsersplugins";
			$trans["category_celebrity"]="celebrity";
			$trans["category_chat"]="chat";
			$trans["category_children"]="children";
			$trans["category_cleaning"]="cleaning";
			$trans["category_clothing"]="clothing";
			$trans["category_converters"]="converters";
			$trans["category_cosmetics"]="cosmetics";
			$trans["category_culture"]="culture";
			$trans["category_dangerous_material"]="dangerous_material";
			$trans["category_dating"]="dating";
			$trans["category_dictionaries"]="dictionaries";
			$trans["category_downloads"]="downloads";
			$trans["category_drugs"]="drugs";
			$trans["category_dynamic"]="dynamic";
			$trans["category_electricalapps"]="electricalapps";
			$trans["category_electronichouse"]="electronichouse";
			$trans["category_filehosting"]="filehosting";
			$trans["category_finance_banking"]="finance/banking";
			$trans["category_finance_insurance"]="finance/insurance";
			$trans["category_finance_moneylending"]="finance/moneylending";
			$trans["category_finance_other"]="finance/other";
			$trans["category_finance_realestate"]="finance/realestate";
			$trans["category_financial"]="financial";
			$trans["category_forums"]="forums";
			$trans["category_gamble"]="gamble";
			$trans["category_games"]="games";
			$trans["category_genealogy"]="genealogy";
			$trans["category_gifts"]="gifts";
			$trans["category_governments"]="governments";
			$trans["category_green"]="green";
			$trans["category_hacking"]="hacking";
			$trans["category_handicap"]="handicap";
			$trans["category_health"]="health";
			$trans["category_hobby_arts"]="hobby/arts";
			$trans["category_hobby_cooking"]="hobby/cooking";
			$trans["category_hobby_other"]="hobby/other";
			$trans["category_hobby_pets"]="hobby/pets";
			$trans["category_paytosurf"]="paytosurf";
			$trans["category_terrorism"]="terrorism";
			$trans["category_hobby_fishing"]="hobby/fishing";
			$trans["category_hospitals"]="hospitals";
			$trans["category_houseads"]="houseads";
			$trans["category_housing_accessories"]="housing/accessories";
			$trans["category_housing_doityourself"]="housing/doityourself";
			$trans["category_housing_builders"]="housing/builders";
			$trans["category_housing_reale_state"]="housing/reale_state";
			$trans["category_humanitarian"]="humanitarian";
			$trans["category_imagehosting"]="imagehosting";
			$trans["category_industry"]="industry";
			$trans["category_internal"]="internal";
			$trans["category_isp"]="isp";
			$trans["category_jobsearch"]="jobsearch";
			$trans["category_jobtraining"]="jobtraining";
			$trans["category_justice"]="justice";
			$trans["category_learning"]="learning";
			$trans["category_liste_bu"]="liste_bu";
			$trans["category_luxury"]="luxury";
			$trans["category_mailing"]="mailing";
			$trans["category_malware"]="malware";
			$trans["category_manga"]="manga";
			$trans["category_maps"]="maps";
			$trans["category_marketingware"]="marketingware";
			$trans["category_medical"]="medical";
			$trans["category_mixed_adult"]="mixed_adult";
			$trans["category_mobile_phone"]="mobile-phone";
			$trans["category_models"]="models";
			$trans["category_movies"]="movies";
			$trans["category_music"]="music";
			$trans["category_nature"]="nature";
			$trans["category_news"]="news";
			$trans["category_press"]="press";
			$trans["category_passwords"]="passwords";
			$trans["category_phishing"]="phishing";
			$trans["category_photo"]="photo";
			$trans["category_pictures"]="pictures";
			$trans["category_pictureslib"]="pictureslib";
			$trans["category_politic"]="politic";
			$trans["category_porn"]="porn";
			$trans["category_press"]="press";
			$trans["category_proxy"]="proxy";
			$trans["category_publicite"]="publicite";
			$trans["category_reaffected"]="reaffected";
			$trans["category_recreation_humor"]="recreation/humor";
			$trans["category_recreation_nightout"]="recreation/nightout";
			$trans["category_recreation_schools"]="recreation/schools";
			$trans["category_recreation_sports"]="recreation/sports";
			$array["category_getmarried"]="getmarried";
			$array["category_police"]="police";
			$trans["category_recreation_travel"]="recreation/travel";
			$trans["category_recreation_wellness"]="recreation/wellness";
			$trans["category_redirector"]="redirector";
			$trans["category_religion"]="religion";
			$trans["category_remote_control"]="remote-control";
			$trans["category_ringtones"]="ringtones";
			$trans["category_sciences"]="sciences";
			$trans["category_science_astronomy"]="science/astronomy";
			$trans["category_science_computing"]="science/computing";
			$trans["category_science_weather"]="science/weather";
			$trans["category_science_chemistry"]="science/chemistry";
			$trans["category_searchengines"]="searchengines";
			$trans["category_sect"]="sect";
			$trans["category_sexual_education"]="sexual_education";
			$trans["category_sex_lingerie"]="sex/lingerie";
			$trans["category_smallads"]="smallads";
			$trans["category_socialnet"]="socialnet";
			$trans["category_spyware"]="spyware";
			$trans["category_sslsites"]="sslsites";
			$trans["category_stockexchange"]="stockexchange";
			$trans["category_strict_redirector"]="strict_redirector";
			$trans["category_strong_redirector"]="strong_redirector";
			$trans["category_suspicious"]="suspicious";
			$trans["category_teens"]="teens";
			$trans["category_tobacco"]="tobacco";
			$trans["category_tracker"]="tracker";
			$trans["category_translator"]="translators";
			$trans["category_translators"]="translators";
			$trans["category_transport"]="transport";
			$trans["category_tricheur"]="tricheur";
			$trans["category_updatesites"]="updatesites";
			$trans["category_violence"]="violence";
			$trans["category_warez"]="warez";
			$trans["category_weapons"]="weapons";
			$trans["category_webapps"]="webapps";
			$trans["category_webmail"]="webmail";
			$trans["category_webphone"]="webphone";
			$trans["category_webplugins"]="webplugins";
			$trans["category_webradio"]="webradio";
			$trans["category_webtv"]="webtv";
			$trans["category_wine"]="wine";
			$trans["category_womanbrand"]="womanbrand";	
			$trans["category_horses"]="horses";	
			$trans["category_meetings"]="meetings";	
			$trans["category_tattooing"]="tattooing";	
			$trans["category_advertising"]="publicite";	
			$trans["category_getmarried"]="getmarried";	
			$trans["category_literature"]="literature";
			$trans["category_police"]="police";
			$trans["category_search"]="searchengines";
			
			return $trans;		
		
	}	
	
	
	
	
	function CreateCategoryTable($category,$fulltablename=null){
		if($this->DisableArticaProxyStatistics==1){return true;}
		$category=trim($category);
		if($category=="drogue"){$category="drugs";}
		if($category=="gambling"){$category="gamble";}
		if($category=="hobby/games"){$category="games";}
		if($category=="forum"){$category="forums";}
		if($category=="spywmare"){$category="spyware";}

		if(!class_exists("dansguardian_rules")){
			include_once(dirname(__FILE__)."/class.dansguardian.inc");
			$dansguardian_rules=new dansguardian_rules();
			if(!isset($dansguardian_rules->array_blacksites[$category])){
				if($GLOBALS["AS_ROOT"]){echo "$category, Not an official category\n";}
				return;
			}
		}
		
		
		$category=$this->category_transform_name($category);
		$tablename=strtolower("category_$category");
		if($fulltablename<>null){$tablename=$fulltablename;}
		if($tablename=="category_teans"){$tablename="category_teens";}
		$tablename=strtolower($tablename);
		$tablename=str_replace("category_category_","category_",$tablename);
		if($tablename=="category_drogue"){$tablename="category_drugs";}
		if($tablename=="category_gambling"){$tablename="category_gamble";}
		if($tablename=="category_hobby_games"){$tablename="category_games";}
		if($tablename=="category_forum"){$tablename="category_forums";}
		if($tablename=="category_spywmare"){$tablename="category_spyware";}
		if($tablename=="category_housing_reale_state_"){$tablename="category_finance_realestate";}
		
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->CREATE_DATABASE($this->database);
			if(!$this->ok){return false;}
			if(!$this->DATABASE_EXISTS($this->database)){
				$this->mysql_error="$this->database, no such database....";
				return false;
			}
				
			
		}
		
		
		$tablename=strtolower($tablename);
		if(!$this->TABLE_EXISTS($tablename,$this->database)){	
			if($GLOBALS["VERBOSE"]){echo "CREATE CATEGORY TABLE `$tablename`\n";}
			$sql="CREATE TABLE `$this->database`.`$tablename` (
					`zmd5` VARCHAR( 90 ) NOT NULL ,
					PRIMARY KEY ( `zmd5` )
					) ENGINE = MYISAM";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				$this->mysql_error=$this->called.$this->mysql_error." cat:$category $called";
				writelogs("Failed to create $category $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}

		return true;
		
	}	
	
}


	

	
	

