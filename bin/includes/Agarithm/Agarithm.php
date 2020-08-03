<?php
namespace Agarithm;

require_once(dirname(__FILE__)."/Singleton.php");
require_once(dirname(__FILE__)."/Logger.php");
require_once(dirname(__FILE__)."/Memo.php");
require_once(dirname(__FILE__)."/Strings.php");
require_once(dirname(__FILE__)."/Curl.php");
require_once(dirname(__FILE__)."/UI/UI.php");
require_once(dirname(__FILE__)."/ORM/DB.php");



//Syntactic Sugar
function URL($link, $params = array(), $hashBangParams = array()) {return Strings::URL($link, $params, $hashBangParams);}
function REDACT($arr,$allowed=false,$remove=false){return Strings::Redact($arr,$allowed,$remove);}

function RenderArray($arr,$name='array',$allow=array()){return '<pre>'.RenderTextArray(REDACT($arr,$allow),$name).'</pre>';}
function RenderTextArray($arr,$name='array',$allow=array()){return UI::RenderTextArray(REDACT($arr,$allow),$name);}

if (!function_exists('array_random')) {
	function array_random($arr, $num=1) {
		//returns random element(s) from the array
		$r = array();
		$idx=null;
		while(($arr = array_values($arr)) && $num>0 ){
			$end = count($arr)-1;
			$idx = mt_rand(0,$end);
			//Add selected item to return array
			$r[] = $arr[$idx];
			//remove this item and repeat
			unset($arr[$idx]);
			//check for exit condition
			if(count($r)==$num)break;
		}
		return (($num==1) && isset($idx)) ? $r[0] : $r;
	}
}

function FIND_KEY_BY_VALUE($haystack,$needle,$caseSensitive=false){
	//Recursive hunt through multi-dimensional array/object to retrieve the value for this key ($needle)
	$out = null;
	$found = false;
	foreach((array)$haystack as $key => $value){
		$value = $caseSensitive ? $value : mb_strtolower($value);
		if(!$found && Strings::Same($value,$needle,$caseSensitive)){
			//Keep looking until a non-empty thing is found
			$found = Strings::isEmpty($key) ? false : true;
			if($found)$out = $key;
		}

		if(!$found && (is_array($value)||is_object($value))){
			//recurse
			$out = FIND_KEY_BY_VALUE($value,$needle,$caseSensitive);
			if($out!==null)$found = true;
		}

		if($found)break; //foreach
	}
	return $out;
}

function FIND_VALUE_BY_KEY($haystack,$needle,$caseSensitive=false){
	//Recursive hunt through multi-dimensional array/object to retrieve the value for this key ($needle)
	$out = null;
	$found = false;
	foreach((array)$haystack as $key => $value){
		$key = $caseSensitive ? $key : mb_strtolower($key);
		if(!$found && Strings::Same($key,$needle,$caseSensitive)){
			//Keep looking until a non-empty thing is found
			$found = Strings::isEmpty($value) ? false : true;
			if($found)$out = $value;
		}

		if(!$found && (is_array($value)||is_object($value))){
			//recurse
			$out = FIND_VALUE_BY_KEY($value,$needle,$caseSensitive);
			if($out!==null)$found = true;
		}

		if($found)break; //foreach
	}
	return $out;
}

function REMOVE_VALUE_BY_KEY(&$haystack,$needle,$caseSensitive=false){
	//Recursive hunt through multi-dimensional array/object to remove the value for this key ($needle)
	if(is_object($haystack)||is_array($haystack)){
		foreach($haystack as $key => $value){
			if(Strings::Same($key,$needle,$caseSensitive)){
				if(is_object($haystack))unset($haystack->$key);
				if(is_array($haystack))unset($haystack[$key]);
			}

			if((is_array($value)||is_object($value))){
				//recurse
				REMOVE_VALUE_BY_KEY($value,$needle,$caseSensitive);
			}
		}
	}
}

function XML2ARRAY($xml){
	if(!is_object($xml)&&is_scalar($xml))$xml = new SimpleXMLElement($xml);
	if(is_array($xml))return $xml;
	$parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
		$nodes = $xml->children();
		$attributes = $xml->attributes();

		if (0 !== count($attributes)) {
			foreach ($attributes as $attrName => $attrValue) {
				$collection['attributes'][$attrName] = strval($attrValue);
			}
		}

		if (0 === $nodes->count()) {
			$collection['value'] = strval($xml);
			return $collection;
		}

		foreach ($nodes as $nodeName => $nodeValue) {
			if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
				$collection[$nodeName] = $parser($nodeValue);
				continue;
			}

			$collection[$nodeName][] = $parser($nodeValue);
		}

		return $collection;
	};

	return [
		$xml->getName() => $parser($xml)
	];
}

function FIND_XML_VALUE($haystack,$needle,$caseSensitive=false){
	$out = null;
	if($xml = XML2ARRAY($haystack)){
		if($value = FIND_VALUE_BY_KEY($xml,$needle,$caseSensitive))$out = isset($value['value']) ? $value['value'] : FIND_VALUE_BY_KEY($value,'value') ;
	}
	return $out;
}

	class DIRTY {
		protected static function HASH(){
			return get_called_class().__METHOD__;
		}

		public static function RAW($key=null){
			if(Memo::Get(static::HASH())===null){
				$dirty = array();
				$dirty += $_COOKIE;
				$dirty += $_POST;
				$dirty += $_GET;
				$json = json_decode(file_get_contents('php://input'), true);  //JSON Payloads
				if(is_array($json))$dirty += $json;

				INFO(__METHOD__." = ".json_encode(REDACT($dirty)));
				Memo::Set(static::HASH(),$dirty);
			}
			return is_scalar($key) ? FIND_VALUE_BY_KEY(Memo::Get(static::HASH()),$key) : Memo::Get(static::HASH());
		}

		public static function GET($key){
			$out = static::RAW($key);
			if(is_scalar($out)){
				//DB SQL Injection Protection
				$out = DB::Escape($out);
				//UI SSTI Protection
				$out = UI::escape($out);
			}
			return $out;
		}

		public static function SET($key, $val){
			$dirty = static::RAW();
			$dirty[$key]=$val;
			Memo::Set(static::HASH(),$dirty);
			return static::GET($val);
		}
	}

	class CLEAN extends DIRTY {

		public static function RAW($key=null){
			if(Memo::Get(static::HASH())===null){
				$clean = array();
				$clean += $_ENV;
				$clean += $_SERVER;
				if(isset($_SESSION))$clean += $_SESSION;

				Memo::Set(static::HASH(),$clean);
			}
			return is_scalar($key) ? FIND_VALUE_BY_KEY(Memo::Get(static::HASH()),$key) : Memo::Get(static::HASH());
		}

	}

//Init base ENVIRONMENT Type
if(CLEAN::GET('IS_PRODUCTION')===null)CLEAN::SET("IS_PRODUCTION",1);
if(CLEAN::GET('IS_STAGING')===null)CLEAN::SET("IS_STAGING",0);
if(CLEAN::GET('IS_DEV')===null)CLEAN::SET("IS_DEV",0);

