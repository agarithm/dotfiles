<?php
namespace Agarithm;

////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LOGGER
class LOGGER extends Singleton{
	public const DEBUG = 0;
	public const TRACE = 1;
	public const INFO  = 2;
	public const WARN  = 3;
	public const ERROR = 4;
	public const ALERT = 5;
	public const FATAL = 6;

	public function __construct(){
		$this->log = array();
		$this->first = $this->utime();
		$this->whoami = '' ;
		$this->ip = empty(CLEAN::GET('REMOTE_ADDR')) ? "127.0.0.1" : CLEAN::GET('REMOTE_ADDR');
		$this->ip = str_pad($this->ip,15);
		$this->level = LOGGER::INFO;
	}

	private function utime(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}


	public static function LOG ($level,$message){
		$label = function($level){
			return [ 
				LOGGER::DEBUG   => 'DEBUG:',
				LOGGER::TRACE   => 'TRACE:',
				LOGGER::INFO    => ' INFO:',
				LOGGER::WARN    => ' WARN:',
				LOGGER::ERROR   => 'ERROR:',
				LOGGER::ALERT   => 'ALERT:',
				LOGGER::FATAL   => 'FATAL:',
			][$level];
		};

		$l = static::instance();
		$now = $l->utime();
		$runtime = $now - $l->first; 
		$runtime = str_replace(',','',number_format(($runtime<0.00001 ? 0.0 : $runtime),4));
		$runtime = $runtime < 300 ? $runtime."s" : Strings::HumanSeconds($runtime) ;
		$stamp = date('Y-m-d H:i:s');
		$prefix = $label($level);
		$whoami  = Strings::isEmpty($l->whoami) ? "" : ' ('.$l->whoami.')';
		$whoami = $l->ip . $whoami;
		$message = strip_tags($message);
		$line = "$stamp - $runtime - $whoami - $prefix $message";

		$l->log[] = array('level'=>$level,'line'=>$line);

		//Comand line should echo to the console now...
		if ((php_sapi_name() == "cli") || ( defined( 'WP_CLI' ) && WP_CLI )) {
			if($level>=$l->level)echo $line.PHP_EOL;
			flush();
		}

		return "$level $message";
	}

	public static function SHOW($level=LOGGER::INFO){
		$l = static::instance();
		$out = '';
		foreach($l->log as $line){
			if($line['level'] >= $level) $out .= $line['line'].PHP_EOL;
		}
		return $out;
	}

	public static function FLUSH($level=LOGGER::INFO){
		//Capture the current buffer
		$out = static::SHOW($level);
		//clear it out
		$l = static::instance();
		$l->log = array();
		$l->first = $l->utime();
		return $out;
	}

}



function HTML_ERROR_LOG($level=LOGGER::INFO){
	return '<pre>'.TEXT_ERROR_LOG($level).'</pre>';
}

function TEXT_ERROR_LOG($level=LOGGER::INFO){
	return LOGGER::SHOW($level);
}

function STOP($message="") {
	//Development Log Level (no log in production)
	if(!CLEAN::GET('IS_PRODUCTION')){
		die("<h1>$message</h1><h2>Stack Trace</h2>".STACK());
	}else{
		die("<h1>$message</h1>");
	}
}

function FATAL($message){
	LOGGER::LOG(LOGGER::FATAL,$message);
	STOP($message);
}

function ALERT($message,$rateLimit=1800){
	return LOGGER::LOG(LOGGER::ALERT,$message);
}

function ERROR($message){
	return LOGGER::LOG(LOGGER::ERROR, $message);
}

function WARN($message){
	return LOGGER::LOG(LOGGER::WARN, $message);
}

function INFO($message) {
	return LOGGER::LOG(LOGGER::INFO, $message);
}

function TRACE($message) {
	return LOGGER::LOG(LOGGER::TRACE, $message);
}

function DEBUG($message) {
	return LOGGER::LOG(LOGGER::DEBUG, $message);
}

function STACK() {
	ob_start();
	debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$trace = ob_get_contents();
	ob_end_clean();

	// Remove first item from backtrace as it's this function which
	// is redundant.
	$trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

	// Renumber backtrace items.
	$trace = preg_replace_callback ('/^#(\d+)/m', function($m){return '#'. ($m[1] - 1);} , $trace);

	return "<pre>$trace</pre>";
}

function CALLER($verbose=false){
	$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
	$out = "";
	if(isset($stack[2]['class']))$out .= $stack[2]['class'];
	if(isset($stack[2]['type']))$out .= $stack[2]['type'];
	if(isset($stack[2]['function']))$out .= $stack[2]['function'];
	if($verbose){
		$out .= ' - ';
		if(isset($stack[1]['file']))$out .= $stack[1]['file'];
		$out .= ':';
		if(isset($stack[1]['line']))$out .= $stack[1]['line'];
	}
	return $out;
}



