<?php
namespace Agarithm;

//Singleton Base Class
class Singleton {
	public static function &instance(){
		static $single=array();
		$class = get_called_class();
		if( ! isset($single[$class]) ) $single[$class] = new $class();
		return $single[$class];
	}
}


function RATE_LIMIT_OKAY($what, $seconds){
	//returns true if greater than $seconds has elapsed since last $what (allowed)
	$rtn = true;

	//Get Last Stamp
	$hash = md5(__METHOD__.$what);
	$oldStamp = Memo::GetCache($hash);
	settype($oldStamp,"integer");

	//Check Threshold
	$now = time();
	if($now < ($oldStamp+$seconds))$rtn = false; //too soon

	//if TRUE Update Stamp
	if($rtn){
		Memo::SetCache($hash,$now);
	}else{
		TRACE(__METHOD__." Too soon for $what limited to once every ".Strings::HumanSeconds($seconds));
	}

	return $rtn;
}

