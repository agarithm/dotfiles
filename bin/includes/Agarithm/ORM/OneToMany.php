<?php
namespace Agarithm;

//////////////////////////////////////////////////////////////////////////////////////////
// Copyright Mike Agar 2014
// MIT License

abstract class OneToMany {

	public function __construct($classOne,$classMany){
		$DB_PREFIX = DB::Prefix();
		$this->one = new $classOne();
		$this->many = new $classMany();

		$this->tableName = $DB_PREFIX."_".$this->one->className."_to_".$this->many->className;

		//dynamically create and modify tables
		$this->CreateTable();

		$this->reset();
	}

	public function getOneIds($manyPK){
		settype($manyPK, "integer");
		$one = $this->one->primaryKey;
		$many = $this->many->primaryKey;

		$out = array();

		$cmd = "SELECT $one FROM ".$this->tableName." WHERE $many=$manyPK";
		$result = DB::Query($cmd);

		if($result && mysqli_num_rows($result)>0){
			//found some
			while(list($onePK) = mysqli_fetch_row($result)){
				$out[] = $onePK;
			}
		}

		return $out;
	}

	public function getManyIds($onePK){
		settype($onePK, "integer");
		if(isset($this->cacheIds[$onePK]))return $this->cacheIds[$onePK];

		//cache miss
		$out = array();

		$one = $this->one->primaryKey;
		$many = $this->many->primaryKey;

		$cmd = "SELECT $many FROM ".$this->tableName." WHERE $one=$onePK";
		$result = DB::Query($cmd);

		if($result && mysqli_num_rows($result)>0){
			//found some
			while(list($manyPK) = mysqli_fetch_row($result)){
				$out[] = $manyPK;
			}
		}

		//cache the result
		$this->cacheIds[$onePK] = $out;

		return $out;
	}

	public function getManyObjs($onePK){
		settype($onePK, "integer");
		if(isset($this->cacheObjs[$onePK]))return $this->cacheObjs[$onePK];

		//cache miss
		$out = array();
		$ids = $this->getManyIds($onePK);
		$manyClass = $this->many->className;
		foreach ($ids as $id) {
			$obj = new $manyClass();
			if($obj->become($id)){
				$out[] = $obj;
			}else{
				WARN(__METHOD__." Many Obj missing, deleting from O2M");
				$this->delete($onePK,$id);
			}
		}

		//cache the result
		$this->cacheObjs[$onePK] = $out;
		return $out;
	}

	public function dropTable(){
		DB::DropTable($this->tableName);
	}

	public function reset(){
		//Reset Memoizers
		$this->cacheIds = array();
		$this->cacheObjs = array();
	}

	public function add($one,$many,$meta=""){
		$rtn = false; //true on success
		$o = $one;
		$m = $many;
		settype($o, "integer");
		settype($m, "integer");
		if($o&&$m){
			$md = DB::Escape($meta);
			$cmd  = "INSERT INTO ".$this->tableName;
			$cmd .= " (".$this->one->primaryKey.",".$this->many->primaryKey.",METADATA)";
			$cmd .= " VALUES ($o,$m,'".$md."')";
			DB::Query($cmd);
			$this->reset();
			$rtn = true;
		}else{
			ERROR("OneToMany(".$this->tableName."): error on add($one,$many)");
		}

		return $rtn;
	}

	public function upsert($one,$many,$meta=""){
		foreach($this->getManyIds($one) as $maybe){
			if($maybe == $many)return $this->setMetadata($one,$many,$meta);
		}
		return $this->add($one,$many,$meta);
	}

	public function exists($one,$many){
		$o = $one;
		$m = $many;
		settype($o, "integer");
		settype($m, "integer");

		$cmd  = "SELECT * FROM ".$this->tableName;
		$cmd .= " WHERE ".$this->one->primaryKey."=".$o;
		$cmd .= " AND ".$this->many->primaryKey."=".$m;

		$result = DB::Query($cmd);

		if($result && mysqli_num_rows($result)>0){
			return true;
		}

		return false;
	}

	public function getMetadata($one,$many){
		$o = $one;
		$m = $many;
		settype($o, "integer");
		settype($m, "integer");

		$cmd  = "SELECT METADATA FROM ".$this->tableName;
		$cmd .= " WHERE ".$this->one->primaryKey."=".$o;
		$cmd .= " AND ".$this->many->primaryKey."=".$m;

		$result = DB::Query($cmd);

		if($result && mysqli_num_rows($result)>0){
			//found some
			while(list($md) = mysqli_fetch_row($result)){
				return $md;
			}
		}

		return false;
	}

	public function setMetadata($one,$many,$meta){
		$rtn = false;
		$o = $one;
		$m = $many;
		settype($o, "integer");
		settype($m, "integer");
		$md = DB::Escape($meta);

		$cmd  = "UPDATE ".$this->tableName;
		$cmd .= " SET METADATA='".$md."'";
		$cmd .= " WHERE ".$this->one->primaryKey."=".$o;
		$cmd .= " AND ".$this->many->primaryKey."=".$m;

		if(DB::Query($cmd))$rtn=true;

		return $rtn;
	}

	public function delete($one,$many){
		$rtn = false; //true on success
		$o = $one;
		$m = $many;
		settype($o, "integer");
		settype($m, "integer");
		if($o&&$m){
			$cmd  = "DELETE FROM ".$this->tableName;
			$cmd .= " WHERE ".$this->one->primaryKey."=".$o;
			$cmd .= " AND ".$this->many->primaryKey."=".$m;
			DB::Query($cmd);
			$this->reset();
			$rtn = true;
		}else{
			ERROR("OneToMany(".$this->tableName."): error on delete($one,$many)");
		}

		return $rtn;
	}

	public function CreateTable(){
		$DBU = DB::instance();
		$DB_AUTO_INSTALL = $DBU->DB_AUTO_INSTALL;
		$DEBUG = $DBU->DB_DEBUG;

		static $newTables = array();
		$table = $this->tableName;

		if(!array_key_exists($table, $newTables)){ //Only do this once
			if($this->one instanceof SimpleDatastore && $this->many instanceof SimpleDatastore){
				$newTables[$table] = true;

				if($DB_AUTO_INSTALL&&!DB::IsATable($table)){
					TRACE("OneToMany::CreateTable($table): Missing table, creating it now.");
					$cmd = "CREATE TABLE IF NOT EXISTS `$table` (";
					$cmd .= "`".$this->one->primaryKey."` int(11) not null, ";
					$cmd .= "`".$this->many->primaryKey."` int(11) not null, ";
					$cmd .= "METADATA text character set utf8, ";
					$cmd .= "PRIMARY KEY (`".$this->one->primaryKey."`,`".$this->many->primaryKey."`))";
					DB::Query($cmd);

					$index = $table."_IDX";
					$cmd = "CREATE INDEX $index ON `$table` (`".$this->one->primaryKey."`)";
					DB::Query($cmd);

					$index = $table."_IDX2";
					$cmd = "CREATE INDEX $index ON `$table` (`".$this->many->primaryKey."`)";
					DB::Query($cmd);
				}
			}else{
				die("ERROR: OneToMany expects two classes from SimpleDatastore.");
			}
		}
	}

}
