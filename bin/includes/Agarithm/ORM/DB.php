<?php
namespace Agarithm;

//////////////////////////////////////////////////////////////////////////////////////////
// Copyright Mike Agar 2014
// MIT License

class DB extends Singleton {
	public function __construct(){
		global $wpdb;
		//Expose protected mysqli DB Handle from inside $wpdb
		//see https://stackoverflow.com/a/41697102
		$this->DB = isset($wpdb) ? \Closure::bind(function(){return $this->dbh;},$wpdb,'wpdb')() : null;
		$this->DB_PREFIX = isset($wpdb) ? $wpdb->prefix : '';
		$this->DB_DEBUG = false;
		$this->DB_QUERY_COUNT = 0;
		$this->DB_QUERY_TIME = 0.0;
		$this->DB_QUERY_SLOW = false;
		$this->DB_AUTO_INSTALL = true;
		$this->DB_SLOW_QUERY_THRES = 1;
		$this->DB_TABLE_LIST = null;
		$this->DB_INDEXES = array();
	}

	private function uTime(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}


	public static function Prefix(){
		$DB = static::instance();
		return $DB->DB_PREFIX;
	}

	public static function Query($query,$trace=false){
		$DB = static::instance();
		$start = $DB->uTime();

		//If no DB connection, quit.
		if(!isset($DB->DB))return 0;

		//Okay
		$result = mysqli_query($DB->DB,$query);
		$qTime = $DB->uTime() - $start;
		if(($DB->DB_DEBUG||$trace) && (mysqli_errno($DB->DB)>0||mysqli_warning_count($DB->DB)>0)){
			if(mysqli_errno($DB->DB)>0){
				ERROR("DB TRACE: ERROR - ".mysqli_error($DB->DB)." (".mysqli_errno($DB->DB).") with following SQL: $query ");
			}
			if(mysqli_warning_count($DB->DB)){
				if ($warn = mysqli_query($DB->DB, "SHOW WARNINGS")) {
					while($row = mysqli_fetch_row($warn)){
						WARN(__METHOD__." WARNING on $query ");
						WARN(RenderArray($row,"DB WARNINGS"));
					}
					mysqli_free_result($warn);
				}
			}
		}else if($trace){
			TRACE("DB TRACE: <pre> $query </pre>");
		}

		//Analytics
		$DB->DB_QUERY_COUNT++;
		$DB->DB_QUERY_TIME += $qTime; //accumulate total time
		$thres = $DB->DB_SLOW_QUERY_THRES;
		if($qTime>$thres){
			WARN("WARNING: Slow Query ($qTime seconds): $query");
			//Set Flag
			$DB->DB_QUERY_SLOW = true;
			//Help with the EXPLAIN Output for this query
			if(($DB->DB_DEBUG||$trace) && stripos($query, "SELECT")!==false){
				$eResult = mysqli_query($DB->DB,"EXPLAIN $query");
				if($eResult){
					$results = static::GetResultAsArray($eResult);
					$i = 0;
					foreach ($results as $row) {
						$out = "";
						foreach ($row as $key => $value) $out .= " $key=$value";
						TRACE("EXPLAIN [$i] $out");
					}
				}
			}
		}else{
			$DB->DB_QUERY_SLOW = false; 
		}
		//All done
		return $result;
	}

	private static function FixObjectErrors($obj,$class,$tableName,$primaryKeyName,$callback){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return false;

		//Clear the Memoizers, we're going to be messing with things...
		Memo::Clear();

		$getColumnNames = function ($tableName){
			$out = array();
			$cmd = "SHOW COLUMNS FROM `$tableName`";
			$result = static::Query($cmd);
			if($result){
				while(list($cName) = mysqli_fetch_row($result)){
					$out[] = $cName;
				}
			}
			return $out;
		};

		$getColumnType = function ($value){
			$out = "";
			switch(gettype($value)){
			case "integer":
				$out .= " int(11) DEFAULT $value";
				break;
			case "float":
			case "double":
				$out .= " float(10,6) DEFAULT $value";
				break;
			default:
				$out .= " text character set utf8";
				break;
			}

			return $out;

		};

		if(isset($DB->DB)&&isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			switch(mysqli_errno($DB->DB)){
			case "1054"://Missing Column Name
				TRACE("Attempting to fix DB Schema: Adding Column");
				$currentCols = $getColumnNames($tableName);
				if(count($currentCols)){
					foreach (get_class_vars($class) as $key => $value) {
						if(!in_array($key, $currentCols)){
							if($DB->DB_DEBUG)TRACE("Found a missing column: $key");
							$cmd = "ALTER TABLE `$tableName` ADD COLUMN `$key` ".$getColumnType($value);
							//If this works, try the callback
							if(static::Query($cmd,true))return static::$callback($obj,$class,$tableName,$primaryKeyName);
						}
					}
				}else{
					ERROR("ERROR:  ABORTING DB FIX - Unexpected column count on existing table.");
				}
				TRACE("Unable to fix this DB error.");
				break;
			default:
				WARN("WARNING: Unable to automatically fix - ".mysqli_error($DB->DB)." (".mysqli_errno($DB->DB).")");
				break;
			}
		}
		return 0;
	}

	public static function Escape($sql){
		$DB = static::instance();
		return isset($DB->DB) ? mysqli_real_escape_string($DB->DB,$sql) : $sql;
	}

	//Requires that the Object vars and the Table schema match exactly!
	public static function InsertObject($obj,$class,$tableName,$primaryKeyName="99"){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return 0;

		//Modifing an Object Table, clear all memoized results
		Memo::Clear();

		//Returns the insert ID
		$cmd  = "INSERT INTO `$tableName`";
		$cmd1 = " (";
		$cmd2 = " VALUES (";
		$loopCounter = 0;
		foreach (get_class_vars($class) as $key => $value) {
			if($loopCounter++){
				$cmd1 .= ",";
				$cmd2 .= ",";
			}
			$cmd1 .= "`$key`";
			if(strpos($key,$primaryKeyName)===FALSE){
				$cmd2 .= "'".$DB->Escape($obj->$key)."'";
			}else{
				$cmd2 .= "NULL";
			}
		}
		$cmd1 .= " ) ";
		$cmd2 .= " ) ";
		$result = $DB->Query($cmd.$cmd1.$cmd2);
		if(!$result)$result = $DB->FixObjectErrors($obj,$class,$tableName,$primaryKeyName,"InsertObject");

		return isset($DB->DB) ? mysqli_insert_id($DB->DB) : 0;
	}

	//Requires that the Object vars and the Table schema match exactly!
	public static function UpdateObject($obj,$class,$tableName,$primaryKeyName){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return false;

		//Modifing an Object Table, clear all memoized results
		Memo::Clear();

		$cmd  = "UPDATE `$tableName` SET ";
		$loopCounter = 0;
		foreach (get_class_vars($class) as $key => $value) {
			if(strpos($key,$primaryKeyName)===FALSE){
				if($loopCounter++){
					$cmd .= ", ";
				}
				$cmd .= "`$key`"."='".$DB->Escape($obj->$key)."'";
			}
		}
		$cmd .= " WHERE `".$DB->Escape($primaryKeyName)."`='".$DB->Escape($obj->$primaryKeyName)."'";

		$result = $DB->Query($cmd);

		if(!$result)$result = $DB->FixObjectErrors($obj,$class,$tableName,$primaryKeyName,"UpdateObject");

		return $result;
	}

	public static function DeleteObject($obj,$class,$tableName,$primaryKeyName){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return false;

		$cmd  = "DELETE FROM `$tableName`";
		$cmd .= " WHERE `".$DB->Escape($primaryKeyName)."`='".$DB->Escape($obj->$primaryKeyName)."'";
		$result = $DB->Query($cmd);
		//Modified an Object Table, clear all memoized results
		Memo::Clear();
		return $result;
	}

	public static function GetResultAsArray($result){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return array();

		$out = array();
		if($result && mysqli_num_rows($result)>0){
			while($row = mysqli_fetch_assoc($result)){
				$out[]=$row;
			}
		}
		return $out;
	}

	public static function AddObjectIndex($className,$tableName,$keyName,$sort=""){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return false;

		$rtn = true; //Always Success

		if(isset($DB->DB_AUTO_INSTALL) && $DB->DB_AUTO_INSTALL && $DB->IsATable($tableName)){
			$rtn = false; //only ever return failures if AUTO INSTALL is turned on.

			$obj = new $className();
			if(is_numeric($obj->$keyName)){
				switch($sort){
				case "desc":
				case "asc":
					$sort = strtoupper($sort);
					break;
				case "DESC":
				case "ASC":
					//acceptable values.  do nothing
					break;
				default:
					//anything else we remove
					$sort = "";
				}
			}else{
				$sort = "";
			}

			$idxName = $tableName."_".$keyName."_".$sort."_IDX";
			if(!isset($DB->DB_INDEXES[$idxName])){
				TRACE("DB: Considering adding an index on ( $tableName.$keyName )");
				//check and create once

				$cmd = "SHOW INDEX FROM `$tableName` WHERE Key_name='$idxName'";
				$result = $DB->Query($cmd);
				$rows = $DB->GetResultAsArray($result);
				if(count($rows)==0){
					//Missing: Add the index
					WARN("DB: Adding index $idxName");
					$cmd = "CREATE INDEX `$idxName` ON `$tableName`";
					$obj = new $className();
					switch(gettype($obj->$keyName)){
					case "float":
					case "double":
					case "integer":
						$cmd .= " ( `$keyName` $sort )";
						break;
					default:
						$cmd .= " ( `$keyName` (100))";
						break;
					}

					if(static::Query($cmd)){
						//Only on success
						$rtn = true;
					}
				}else{
					//Already found one
					TRACE("Index $idxName already exists");
					$rtn = true;
				}

				//Cache Return Value for this index
				$DB->DB_INDEXES[$idxName] = $rtn;
			}else{
				//Return the cached value for this index
				$rtn = $DB->DB_INDEXES[$idxName];
			}
		}else{
			if($DB->DB_DEBUG)TRACE("Did not add index, but pretending that we did.  ( $tableName.$keyName )");
		}

		return $rtn;
	}

	public static function GetObjects($className,$tableName,$keyName="",$id="", $sortKey="",$sortOrder="DESC"){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return array();

		$out = Memo::Get(__METHOD__.json_encode(func_get_args()));
		if(!empty($out))return $out;

		//Fall thru and build the $out
		$out = array();

		$cmd  = "SELECT * FROM `".$DB->Escape($tableName)."`";
		if($id)$cmd .= " WHERE `".$DB->Escape($keyName)."`='".$DB->Escape($id)."'";

		if($sortKey){
			$sortKey = $DB->Escape($sortKey);
			$sortOrder = strtoupper($sortOrder)=="DESC" ? "DESC" : "ASC" ;
			$cmd .= " ORDER BY `$sortKey` $sortOrder";
		}

		$result = static::Query($cmd);
		if($result && mysqli_num_rows($result)>0){
			//found you
			while($what = mysqli_fetch_array($result,MYSQLI_ASSOC)){
				$thing = new $className;
				foreach ($what as $key => $value) {
					$thing->$key = $value;
				}
				$out[] = $thing;
			}
		}

		if($DB->DB_QUERY_SLOW && $id){
			//Add an index, cuz this was slow...
			$DB->AddObjectIndex($className,$tableName,$keyName);
		}

		//Memoize this
		return Memo::Set(__METHOD__.json_encode(func_get_args()),$out);
	}


	public static function GetLikeObjects($className,$tableName,$keyName="",$id="", $sortKey="", $sortOrder="DESC"){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return array();

		$out = Memo::Get(__METHOD__.json_encode(func_get_args()));
		if(!empty($out))return $out;

		//Fall thru and build the $out
		$out = array();

		$cmd  = "SELECT * FROM `".$DB->Escape($tableName)."`";
		if($id)$cmd .= " WHERE `".$DB->Escape($keyName)."` LIKE '%".$DB->Escape($id)."%'";

		if($sortKey){
			$sortKey = $DB->Escape($sortKey);
			$sortOrder = strtoupper($sortOrder)=="DESC" ? "DESC" : "ASC" ;
			$cmd .= " ORDER BY `$sortKey` $sortOrder";
		}

		$result = $DB->Query($cmd);
		if($result && mysqli_num_rows($result)>0){
			//found you
			while($what = mysqli_fetch_array($result,MYSQLI_ASSOC)){
				$thing = new $className;
				foreach ($what as $key => $value) {
					$thing->$key = $value;
				}
				$out[] = $thing;
			}
		}

		//Memoize this
		return Memo::Set(__METHOD__.json_encode(func_get_args()),$out);
	}

	public static function ShowTables(){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return array();

		if($tables = Memo::Get(__METHOD__))return $tables;

		$tables = array();
		if($DB->DB_DEBUG)TRACE("ShowTables(): Building Table List Cache");
		$result = $DB->Query( "show tables");
		while(list($tName) = mysqli_fetch_row($result)){
			$tables[] = $tName;
		}

		return Memo::Set(__METHOD__,$tables);
	}

	public static function RepairTables(){
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return true;

		$rtn = true;
		foreach (static::ShowTables() as $table) {
			$cmd = "repair table `$table`";
			$rtn &= $DB->Query($cmd) !== false;
		}
		return $rtn;
	}

	public static function FindPrimaryKeys($search="_ID"){ //ORM Convention is to tag PK and FK with "_ID" suffix
		$out = array();
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return $out;

		foreach($DB->ShowTables() as $tableName){
			$result = $DB->Query("SHOW COLUMNS FROM `$tableName`");
			foreach ($DB->GetResultAsArray($result) as $data) {
				if($data["Key"]=="PRI" && $data["Extra"]=="auto_increment"){
					if(strlen($search)){
						if(StartsWith($data["Field"],$search) || EndsWith($data["Field"],$search)){
							$out[$tableName] = $data["Field"];
						}
					}else{
						$out[$tableName] = $data["Field"];
					}
				}
			}
		}
		return $out;
	}

	public static function FindForeignKeys($fk){
		$out = array();
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return $out;

		foreach($DB->ShowTables() as $tableName){
			$result = $DB->Query("SHOW COLUMNS FROM `$tableName`");
			foreach ($DB->GetResultAsArray($result) as $data) {
				if($data["Extra"]!="auto_increment" && $data["Field"]==$fk)$out[$tableName] = $data["Field"];
			}
		}
		return $out;
	}

	public static function FindIndexes($tableName){
		$out = array();
		$DB = static::instance();

		//If no DB connection, quit.
		if(!isset($DB->DB))return $out;

		$tableName = $DB->Escape($tableName);
		foreach ($DB->GetResultAsArray($DB->Query("show columns from `$tableName`")) as $row1) {
			$colname = $row1["Field"];
			$index = false;
			foreach ($DB->GetResultAsArray($DB->Query("show indexes from `$tableName`")) as $row2){
				$indexName = $row2["Key_name"];
				if($row2["Column_name"]==$colname && $indexName!="PRIMARY")$index = $indexName;
			}
			$out[$colname] = $index;
		}
		return $out;
	}

	public static function IsLikeATable($search){
		//Takes the search string and looks to see if it matches a table name.
		//returns true if it matches a string in a table name
		//FALSE otherwise
		$DB = static::instance();

		$rtn = false;
		
		//If no DB connection, quit.
		if(!isset($DB->DB))return $rtn;


		if(isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			$tables = $DB->ShowTables();
			$end = count($tables);
			for($i=0; $i<$end ; $i++){
				if(stristr($tables[$i],$search)!==false||stristr($search,$tables[$i])!==false){
					//Found a match
					if($DB->DB_DEBUG){
						INFO("IsLikeATable(): FOUND $search = ".$tables[$i]." (haystack=needle) <br>");
					}
					$rtn = true;
					break;
				}
			}
		}else{
			$rtn = true;
		}
		return $rtn;
	}

	public static function IsATable($search){
		//Takes the search string and looks to see if it matches a table name Exactly.
		$DB = static::instance();
		$rtn = false;
		
		//If no DB connection, quit.
		if(!isset($DB->DB))return $rtn;

		if(isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			$tables = $DB->ShowTables();
			$rtn = in_array($search, $tables);
			if($DB->DB_DEBUG&&!$rtn)TRACE("IsATable() Did not find ( $search ) in table list.");
		}
		return $rtn;
	}

	public static function IsAColumn($tableName,$columnName){
		$DB = static::instance();

		if(isset($DB->DB) && isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			$cmd = "SHOW COLUMNS FROM `$tableName`";
			$result = $DB->Query($cmd);
			if($result){
				while(list($cName) = mysqli_fetch_row($result)){
					if($cName==$columnName)return true;
				}
			}
		}
		return false;
	}

	public static function CreateTableFromClass($class,$table,$primaryKey){
		$DB = static::instance();

		if(isset($DB->DB) && isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL&&!static::IsATable($table)){
			if($DB->DB_DEBUG)TRACE("CreateTableFromClass($class): Missing table, creating it now.");
			$cmd = "CREATE TABLE IF NOT EXISTS `$table` (";
			foreach (get_class_vars($class) as $key => $value) {
				$cmd .= " `$key`";
				switch(gettype($value)){
				case "integer":
					$cmd .= " int(11)";
					break;
				case "float":
				case "double":
					$cmd .= " float(10,6)";
					break;
				default:
					$cmd .= " text character set utf8";
					break;
				}

				if(strpos($key,$primaryKey)!==false){
					$cmd .= " not null auto_increment";
				}else if(is_numeric($value)){
					$cmd .= " DEFAULT $value";
				}
				$cmd .= ",";
			}

			$cmd .= " PRIMARY KEY (`$primaryKey`))";
			$cmd .= " ENGINE = InnoDB";
			$DB->Query($cmd,$DB->DB_DEBUG);

			//Added a new table, better clear the table list cache
			Memo::Clear();
		}
	}


	public static function DropTable($table){
		//returns true if DB is changed (tables dropped)
		$DB = static::instance();

		$rtn = false;

		WARN(__METHOD__." $table");
		if(isset($DB->DB) && isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			$cmd = "drop table `$table`";
			$err = $DB->Query($cmd);
			WARN("DropTable(): $cmd <br>");
			$rtn = true;
			Memo::Clear();
		}else{
			if($DB->DB_DEBUG)ERROR(__METHOD__." AUTO INSTALL DISABLED ($table)");
		}
		return $rtn;

	}

	public static function DropTablesLike($search){
		//returns true if DB is changed (tables dropped)
		$DB = static::instance();

		$rtn = false;

		if(isset($DB->DB) && isset($DB->DB_AUTO_INSTALL)&&$DB->DB_AUTO_INSTALL){
			$tables = $DB->ShowTables();
			foreach($tables as $table){
				if(strpos($table,$search)!==false)$rtn = $DB->DropTable($table);
			}
		}

		if($DB->DB_DEBUG&&!$rtn)INFO("DropTablesLike($search): Not Found<br>");
		return $rtn;
	}

	public static function Close() {
		$DB = static::instance();
		TRACE("static::Close() - Releasing our DB handle.  No more Queries for you.");
		if(isset($DB->DB)){
			mysqli_close($DB->DB);
		}
	}
}

require_once(dirname(__FILE__)."/OneToMany.php");
require_once(dirname(__FILE__)."/SimpleDatastore.php");

