<?php

function __get($url,$referer='',$gethead=false,$timeout=20){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, $gethead);
	//curl_setopt($ch, CURLOPT_HTTPHEADER,array('X-FORWARDED-FOR:220.181.108.91','CLIENT-IP:220.181.108.91'));
	if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$data = curl_exec($ch);
	if(isset($_GET['debug']) && $_GET['debug'] == 1188){
		var_dump("=================[CURL GET DATA]===============");
		var_dump($data);
	}
	//远程文件判定
	if($data !== false){
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($statusCode == 200){
			if($gethead){
				$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$header = substr($data, 0, $headerSize);
				$data = array('header'=>$header,'data'=>substr($data,$headerSize));
			}
		}else{
			return 404;
		}
	}
	curl_close($ch);
	return $data;
}

$url = $_GET['u'];

$url = 'http://read.html5.qq.com/image?src=forum&q=5&r=0&imgflag=7&imageUrl='.$url;

echo __get($url,'http://www.qq.com/');