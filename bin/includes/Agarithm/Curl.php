<?php
namespace Agarithm;

function CURL_GET($url, $timeout=10, $options=array() ){

	$conn = curl_init();

	$logURL = REDACT($url);
	INFO("CURL_GET: $logURL");

	$ssl_host = (CLEAN::GET("IS_PRODUCTION")) ? 2 : 0 ;
	$ssl_peer = (CLEAN::GET("IS_PRODUCTION")) ? true : false ;

	$defaults = [
		CURLOPT_URL => $url,
		CURLOPT_FAILONERROR => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_HTTPHEADER => array(),
		CURLOPT_TIMEOUT => $timeout,
		CURLOPT_SSL_VERIFYPEER => $ssl_peer,
		CURLOPT_SSL_VERIFYHOST => $ssl_host
	];

	//Merge any injected Headers with headers needed for this interface
	if(isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])){
		$options[CURLOPT_HTTPHEADER] = array_unique(array_merge($defaults[CURLOPT_HTTPHEADER],$options[CURLOPT_HTTPHEADER]));
	}

	foreach($defaults as $key => $default)$options[$key] = isset($options[$key]) ? $options[$key] : $default;

	foreach ($options as $constantName => $option) curl_setopt($conn, $constantName, $option);

	$result = curl_exec($conn);
	$err = curl_error($conn);
	if($err){
		$result .= " ".$err;
		ERROR("CURL_GET: ERROR on $logURL - $result");
	}
	curl_close($conn);

	return $result;
}

function CURL_POST($url,$params,$timeout=10,$options=array()){
	$logURL = REDACT($url);
	INFO("CURL_POST: $logURL - ".json_encode(REDACT($params)));

	$defaults = [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $params,
		CURLOPT_HTTPHEADER => array('Expect: '),
	];

	//Merge any injected Headers with headers needed for this interface
	if(isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])){
		$options[CURLOPT_HTTPHEADER] = array_unique(array_merge($defaults[CURLOPT_HTTPHEADER],$options[CURLOPT_HTTPHEADER]));
	}

	foreach($defaults as $key => $default)$options[$key] = isset($options[$key]) ? $options[$key] : $default;

	return CURL_GET($url,$timeout,$options);
}

function CURL_POST_JSON($url,$params,$timeout=10, $options=array()){
	$logURL = REDACT($url);
	INFO("CURL_POST_JSON: $logURL - ".json_encode(REDACT($params)));
	TRACE("CURL_POST_JSON: $logURL - ".json_encode($params));
	$defaults = [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode($params),
		CURLOPT_HTTPHEADER => array('Content-Type: application/json','Expect:'),
	];

	//Merge any injected Headers with headers needed for this interface
	if(isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])){
		$options[CURLOPT_HTTPHEADER] = array_unique(array_merge($defaults[CURLOPT_HTTPHEADER],$options[CURLOPT_HTTPHEADER]));
	}

	foreach($defaults as $key => $default)$options[$key] = isset($options[$key]) ? $options[$key] : $default;

	return CURL_GET($url,$timeout,$options);
}

function CURL_POST_XML($url,$payload,$timeout=10, $options=array()){
	$logURL = REDACT($url);
	$data = new SimpleXMLElement($payload);
	$data = json_decode(json_encode($data),true);
	INFO("CURL_POST_XML: $logURL - ".json_encode(REDACT($data)));
	$defaults = [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $payload,
		CURLOPT_HTTPHEADER => array('Content-Type: application/xml','Expect:'),
	];

	//Merge any injected Headers with headers needed for this interface
	if(isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])){
		$options[CURLOPT_HTTPHEADER] = array_unique(array_merge($defaults[CURLOPT_HTTPHEADER],$options[CURLOPT_HTTPHEADER]));
	}

	foreach($defaults as $key => $default)$options[$key] = isset($options[$key]) ? $options[$key] : $default;

	return CURL_GET($url,$timeout,$options);
}

