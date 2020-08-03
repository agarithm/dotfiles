<?php
namespace Agarithm;

//////////////////////////////////////////////////////////////////////////////////////////
// Copyright Mike Agar 2014
// MIT License

abstract class SimpleDatastore {

	public function __construct($className,$tableName,$primaryKey,$allowTags=false){
		$this->className = $className;
		$this->tableName = $tableName;
		$this->primaryKey = $primaryKey;

		//dynamically create and modify tables
		DB::CreateTableFromClass($this->className,$this->tableName,$this->primaryKey);

		//Be careful using the DB Lock mechanism.
		$this->locked = false;
		$this->allowTags = $allowTags; // By Default we combat the XSS attack vector.
	}

	public function __destruct(){
		if(isset($this->locked))$this->unlock();
	}

	public function adminString($len=30){
		return isset($this->NAME) ? Strings::Shorten($this->NAME,$len) : $this->className;
	}

	public function lock(){
		//Be careful using the DB Lock mechanism.
		if(!$this->locked){
			$this->locked = true;
			DB::Query("LOCK TABLES ".$this->tableName." WRITE");
		}
	}

	public function unlock(){
		//Be careful using the DB Lock mechanism.
		if($this->locked){
			DB::Query("UNLOCK TABLES");
			$this->locked = false;
		}
	}

	public function save(){
		$pk = $this->primaryKey;

		if(!$this->allowTags){
			foreach ($this->varArray() as $key => $default){
				$this->$key = htmlspecialchars(htmlspecialchars_decode($this->$key),ENT_NOQUOTES,"UTF-8");
			}
		}

		if($this->$pk){
			DB::UpdateObject($this,$this->className,$this->tableName,$this->primaryKey);
		}else{
			$this->$pk = DB::InsertObject($this,$this->className,$this->tableName,$this->primaryKey);
		}
		return $this->$pk;
	}

	public function delete(){
		$pk = $this->primaryKey;
		if($this->$pk){
			DB::DeleteObject($this,$this->className,$this->tableName,$this->primaryKey);
			$this->unbecome(); //Just to be sure this object is not used elsewhere after this moment

		}
	}

	public function search($key,$value){//pattern match
		return DB::GetLikeObjects($this->className,$this->tableName,$key,$value);
	}

	public function match($key,$value,$sort="DESC"){//exact matches sorted by primaryKey
		return DB::GetObjects($this->className,$this->tableName,$key,$value,$this->primaryKey,$sort);
	}

	public function varArray(){
		return get_class_vars($this->className);
	}

	public function become($id){//true on success
		settype($id, "integer");
		return $id ? $this->becomeBy($this->primaryKey,$id) : false;
	}

	public function becomeBy($key,$value){ //USE of becomeBy() enforces singularity and takes the first (oldest) record.
		$potentials = array();
		//Sanity Check on Lookup
		if(strlen($value)>0 && strlen($key)>0)$potentials = $this->match($key,$value,"ASC");

		if(count($potentials)>0){
			$obj = $potentials[0];
			foreach ($this->varArray() as $var => $default) {
				if(isset($obj->$var))$this->$var = $obj->$var;
			}
			return true;
		}else{
			//Valid not found condition
			return false;
		}
	}

	public function unbecome(){//Reset to default values
		foreach ($this->varArray() as $var => $default) {
			$this->$var = $default;
		}
	}

	public function count($key=false,$value=false){
		$out = 0;
		if($key)$key = DB::Escape($key);
		if($key)$value = DB::Escape($value);

		$cmd  = "SELECT count(*) as c FROM ".$this->tableName;
		if($key)$cmd .= " WHERE $key='$value'";
		$result = DB::Query($cmd);
		if($result){
			$row = mysqli_fetch_array($result);
			$out = $row['c'];
		}
		return $out;
	}

	public function countLike($key,$value){
		$out = 0;
		$key = DB::Escape($key);
		$value = DB::Escape($value);

		$cmd  = "SELECT count(*) as c FROM ".$this->tableName;
		$cmd .= " WHERE `".$key."` LIKE '%".$value."%'";
		$result = DB::Query($cmd);
		if($result){
			$row = mysqli_fetch_array($result);
			$out = $row['c'];
		}
		return $out;
	}

	public function dropTable(){
		DB::DropTable($this->tableName);
	}

	public function dump($key=false, $value=false){
		$cmd  = "SELECT * FROM ".$this->tableName;
		if($key && $value!==false){
			$key = DB::Escape($key);
			$value = DB::Escape($value);
			$cmd .= " WHERE $key='$value'";
		}
		$out = array();
		$result = DB::Query($cmd);
		if($result){
			while($row = mysqli_fetch_assoc($result))$out[]=$row;
		}
		return $out;

	}

	public function latest($count=50,$key=false,$value=false){ //Optional filter on Key Value pair
		$out = array();

		$pk = $this->primaryKey;
		$className = $this->className;
		$cmd  = "SELECT * FROM ".$this->tableName;
		if($key){
			$key = DB::Escape($key);
			$value = DB::Escape($value);
			$cmd .= " WHERE `$key`='$value'";
		}
		$cmd .= " ORDER BY `$pk` DESC";
		$cmd .= " LIMIT $count";

		$result = DB::Query($cmd);
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
		return $out;
	}

	/*
	 * TODO: Make this work again in WordPress
	public function rawEditForm($legend="",$div=false){
		$showPrevNext = function($self){
				$out = "";
				$nextPart = array(	"a" => "b"
									,"b" => "c"
									,"c" => "d"
									,"d" => "e"
									);
				$pk = false;
				$url = RootURI();
				//Parse URI Path looking for matching pattern of .../CLASS/PrimaryKey
				foreach($nextPart as $className => $id){
					if(DIRTY::raw($className))$url .= DIRTY::raw($className)."/";
					if(DIRTY::raw($className)==$self->className){
						$pk = DIRTY::toInteger($id);
						break;
					}
				}
				if($pk){//Found matching pattern, so we can append the Prev / Next helpers
					$next = $url.max(1,$pk+1);
					$prev = $url.max(1,$pk-1);
					$out = "&nbsp;&nbsp;&nbsp;<small>[&nbsp;<a href=$prev>PREV</a>&nbsp;|&nbsp;<a href=$next>NEXT</a>&nbsp;]";
				}
				return $out;
			};
		$div = $div ? $div : $this->className."_".md5(microtime(true));
		$out = "<div id='$div'>";
		$pk = $this->primaryKey;
		//Auto adjust to context (add vs. save)
		$submit = ($this->$pk==0) ? "Add": "Save";
		if(!$legend)$legend=isset($this->NAME)&&$this->NAME ? $this->NAME : $this->className;
		$submitJS="SimpleDatastoreSubmit";

		$form = new FormBuilder();
		$form->newForm(RootURI()."ajax.php?op=simpleSave&class=".$this->className
						,$div
						,$submit
						,$legend.$showPrevNext($this)
						,$submitJS
						);

		foreach ($this->varArray() as $var => $default) {
			$value = $this->$var;
			if($var==$this->primaryKey){
				if($value){
					$form->AddElement("readonly",$var,$var,$value);
				}else{
					$form->AddElement("hidden",$var,$var,$value);
				}
			}else{
				$form->AddElement("text",$var,$var,$value);
			}
		}

		if($this->$pk)$form->addExtraButton("Delete");

		$out .= $form->toHTML();

		$out.="</div>";

		return $out;
	}
	 */

}

class sTest extends SimpleDatastore {
	var $PKEY = 0;
	var $FKEY = 0;
	var $NAME = "";
	var $VALUE = 0.0;
	var $NO_SORT = 6;

	public function __construct(){
		global $DB_PREFIX;
		parent::__construct(get_called_class(),$DB_PREFIX."_STEST","PKEY");
	}

	public function SelfTest(){
		$st1 = new sTest();
		$st1->NAME = "one";
		$st1->save();
		if($st1->PKEY != 1){
			ERROR("FAILED to SAVE:  First PK != 1");
			return; //don't continue, weird shit in test setup...
		}

		$st2 = new sTest();
		$st2->become($st1->PKEY);
		if($st2->NAME != $st1->NAME)ERROR("FAILED to become 1");

		$st2 = new sTest();
		$st2->NAME = "two";
		$st2->FKEY = $st1->PKEY;
		$st2->VALUE = 2.2;
		$st2->save();
		if(!$st2->PKEY)ERROR("FAILED to save()");

		$st3 = new sTest();
		$st3->NAME = "three";
		$st3->FKEY = $st1->PKEY;
		$st3->VALUE = 3.2;
		$st3->save();

		$st4 = new sTest();
		$st4->NAME = "four";
		$st4->FKEY = $st1->PKEY;
		$st4->VALUE = 4.2;
		$st4->save();

		if(count($this->match("FKEY",$st1->PKEY))!=3)ERROR("FAILED to match interger ");
		if(count($this->match("NAME","four"))!=1)ERROR("FAILED to match string ");

		$st4->delete();
		if(count($this->match("NAME","four"))!=0)ERROR("FAILED to delete() (match test)");
		if($this->become($st4->PKEY))ERROR("FAILED to delete() (become($st4->PKEY) test)");

		if(!($this->becomeBy("FKEY",$st3->FKEY)&&$this->PKEY==$st2->PKEY))ERROR("FAIL:  expected to become st2 on becomeBy()");
		if(!$this->becomeBy("NAME","two"))ERROR("FAIL:  expected TRUE on becomeBy(NAME,two)");

		$this->unbecome();
		if($this->PKEY||$this->FKEY)ERROR("FAILED to unbecome");

		if(count($this->latest())!=3)ERROR("FAILED to fetch latest()");
		if(count($this->latest(1))!=1)ERROR("FAILED to fetch latest(1)");

		if(count($this->search("NAME","o"))!=2)ERROR("FAILED to search(o)");
		if(count($this->search("NAME","ee"))!=1)ERROR("FAILED to search(ee)");
		if(count($this->search("NAME","four"))!=0)ERROR("FAILED to search(four)");
		if(count($this->search("NAME","one"))!=1)ERROR("FAILED to search(one)");
		if(count($this->search("NAME",";four"))!=0)ERROR("FAILED to search(;four)");
		if(count($this->search("NAME",";o"))!=0)ERROR("FAILED to search(;four)");

		$this->become($st2->PKEY);
		if(!($this->VALUE>2&&$this->VALUE<3))ERROR("FAIL:  unexpected Float value.  2.2 != ".$this->VALUE);
		$this->become($st3->PKEY);
		if(!($this->VALUE>3&&$this->VALUE<4.5))ERROR("FAIL:  unexpected Float value.  3.2 != ".$this->VALUE);
		if($this->VALUE==3)ERROR("FAIL:  unexpected Float value.  3 == ".$this->VALUE);
		$this->become($st1->PKEY);
		if(!($this->VALUE==0))ERROR("FAIL:  unexpected Float value.  0 != ".$this->VALUE);

		//Capture the results
		$out = '<pre>'.LOGGER::FLUSH(LOGGER::DEBUG).'</pre>';

		if(stristr($out, "fail")===false){
			return "<hr><h1>SimpleDatastore: PASS</h1>$out";
		}else{
			return "<hr><h1>SimpleDatastore: FAIL</h1>$out";
		}
	}
}

class sTest2 extends sTest{ //Flexes the Auto Install parts of the ORM
	var $EXTRA = 99;

	public function __construct(){
		global $DB_PREFIX;
		SimpleDatastore::__construct(get_called_class(),$DB_PREFIX."_STEST","PKEY");
	}

	public function SelfTest(){
		//Tests Auto Install of Indexes and Columns 
		$st0 = new sTest();
		$st1 = new sTest();
		$st2 = new sTest2();

		//Seed the Temp Table with stuff
		$out = $st1->SelfTest();
		if(!$st0->becomeBy("NAME","two"))ERROR("FAILED to Become() on line ".__LINE__);
		if(!Strings::Same($st0->NAME,"two") )ERROR("FAILED sanity on line ".__LINE__.RenderArray($st0,'$st0',['name']));

		$st1->NAME = "ten";
		if(!$st1->save())ERROR('FAILED to $st1->save()');
		if(count($this->match("NAME","ten"))!=1)ERROR("FAILED to match string ");

		$st2->NAME = "tenten";
		$st2->FKEY = $st1->PKEY;
		$st2->VALUE = 2.2;
		$st2->EXTRA = 99;
		if(!$st2->save())ERROR('FAILED to $st2->save() (expecting new column to be added)');

		$st1->NAME = "ten1";
		if(!$st1->save())ERROR('FAILED to $st1->save() again after new column added');
		if(count($this->match("NAME","ten"))!=0)ERROR("FAILED to NOT match string ");
		if(count($this->match("NAME","ten1"))!=1)ERROR("FAILED to match string on line ".__LINE__);
		if(!$st1->become($st1->PKEY))ERROR("FAILED to Become() on line ".__LINE__);
		if(isset($st1->EXTRA))ERROR("Unexpected to find EXTRA as property on line ".__LINE__);
		if(!$st1->become($st2->PKEY))ERROR("FAILED to Become() on line ".__LINE__);
		if(isset($st1->EXTRA))ERROR("Unexpected to find EXTRA as property on line ".__LINE__);
		if(!$st2->becomeBy("NAME","ten1"))ERROR("FAILED to Become() on line ".__LINE__);
		if(!isset($st2->EXTRA))ERROR("Expected to find EXTRA as property on line ".__LINE__);
		if($st2->EXTRA!=99 || $st2->NAME != "ten1")ERROR("FAILED:  Did not retrieve the correct Object: ".json_encode($st2));

		//HACK Test internals of DB
		if(!DB::AddObjectIndex($this->className,$this->tableName,"FKEY","ASC"))ERROR("FAIL: did not create index  on line ".__LINE__);
		if(!DB::AddObjectIndex($this->className,$this->tableName,"NAME","DESC"))ERROR("FAIL: did not create index  on line ".__LINE__);
		if(!DB::AddObjectIndex($this->className,$this->tableName,"NO_SORT"))ERROR("FAIL: did not create index  on line ".__LINE__);
		if(!DB::AddObjectIndex($this->className,$this->tableName,"VALUE","DESC"))ERROR("FAIL: did not create index  on line ".__LINE__);
		if(!DB::AddObjectIndex($this->className,$this->tableName,"FKEY","DESC"))ERROR("FAIL: did not create index  on line ".__LINE__);

		//Should silently return success if there is already an existing matching index 
		if(!DB::AddObjectIndex($this->className,$this->tableName,"FKEY","DESC"))ERROR("FAIL: did not create index  on line ".__LINE__);


		//Capture the results
		$out .= '<pre>'.LOGGER::FLUSH(LOGGER::DEBUG).'</pre>';

		if(stristr($out, "fail")===false){
			$this->dropTable();
			return "<hr><h1>Auto Install: PASS</h1>$out";
		}else{
			return "<hr><h1>Auto Install: FAIL</h1>$out";
		}

	}

}
