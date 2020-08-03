<?php
namespace Agarithm;

//Master Memoizer Class enables a Global Reset which is needed for Testing and Long Running Workers
class Memo extends Singleton{
	public function __construct(){
		$this->data = array();
	}

	public static function Get($key){
		$memo = static::instance();
		return isset($memo->data[$key]) ? $memo->data[$key] : null ;
	}

	public static function Set($key, $value){
		$memo = static::instance();
		$memo->data[$key]=$value;
		return $value;
	}

	public static function Clear(){
		$memo = static::instance();
		$memo->data = array();
	}

	public static function GetCache($key,$server='127.0.0.1',$port='11211'){
		if(extension_loaded('memcached')){
			$memCache = new \Memcached();
			$memCache->addServer($server,$port);
			return $memCache->get($key);
		}else{
			WARN(__METHOD__." memcached extension not loaded");
			return static::Get($key);
		}
	}

	public static function SetCache($key, $value, $server='127.0.0.1',$port='11211'){
		if(extension_loaded('memcached')){
			$memCache = new \Memcached();
			$memCache->addServer($server,$port);
			return $memCache->set($key, $value);
		}else{
			WARN(__METHOD__." memcached extension not loaded");
			return static::Set($key, $value);
		}
	}

}

