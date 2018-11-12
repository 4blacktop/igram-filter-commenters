<?php

// преобразовани кривосимволов 

// преобразование json
// $api = file_get_contents("http://api.instagram.com/oembed?url=http://instagram.com/p/Y7‌​GF-5vftL/");      
// $apiObj = json_decode($api,true);      
// $media_id = $apiObj['media_id'];

// оптимизирует массив
function optimizeArray($array)
	{
	$array = array_unique($array);
	$array = array_map('trim', $array);
	$array = array_filter($array);
	return $array;
	}	

function post_multi_habr($mtime,$useragentArray,$proxyArray,$proxyPass,$array_multi_accs) {	

	if (file_exists("stop.txt")) {exit();} //стоп-кран
	// print_r($array_multi_accs);
	$mh = curl_multi_init();
	$chs = array();
	$proxy = null;

	foreach ( $array_multi_accs as $key => $acc ) {
		$counter_bad_check = 0;
		$counter_good_check = 0;
		$counter_blproxy_check = 0;

		$keyUserAgent = mt_rand(0, count($useragentArray)-1); 	$userAgent = trim($useragentArray["$keyUserAgent"]); // случайный useragent
		$keyProxy = mt_rand(0, count($proxyArray)-1); 			$proxy["$key"] = trim($proxyArray["$keyProxy"]); // случайный proxy
		// echo '<br />' . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t$keyProxy:" . $proxy["$key"] . "\t$userAgent";
		// echo '<br />' . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t<strong>$key</strong>: " . $acc; flush();
		
		// $array_acc = explode(':',$acc);
		$array_acc = explode('|%|',$acc);

		
		// КОСЯК!!!! ПРОВЕРКА КРИВАЯ, ПРОБЕЛ ВСЕГДА НЕ НУЛЛ!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		
		$mra_name = $name . " " . $surname;
		if(!$mra_name) {
			$mra_name = $nickname;
			echo "<h3>Name+Surname NULL, using Nickname!</h3>";
			}
		if(!$mra_name) {
			$mra_name = $mra_mail;
			echo "<h3>Nickname NULL, using Mail Address!</h3>";
			}
		

		$header = array(
		"Host:".$jc_domain.".justclick.ru",
		"User-Agent:" . $userAgent,
		'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
		'Accept-Encoding:gzip, deflate',
		"Referer:" . $jc_referer,
		"Connection:keep-alive",
		"Cache-Control:max-age=0",
		"Content-Type:application/x-www-form-urlencoded",
		'Expect:'
		);	

		// создадим массив post с запросом, преобразовав его urlencode
		$url = "http://$jc_domain.justclick.ru/subscribe/process/?rid[0]=$jc_group_name&doneurl2=$jc_link_after";
		$POST = http_build_query(array(
		'lead_name' => $mra_name,
		'lead_email' => $mra_mail,
		'lead_subscribe' => 'Подписаться!'
		));


		// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t$url"; flush();
		// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t$POST"; flush();
		
		
		$chs[] = ( $ch = curl_init() );

		// хедер и прокси
		if ($header) { curl_setopt($ch, CURLOPT_HTTPHEADER, $header);}
		else { echo "<br /><h1>Header DISABLED!</h1>";	}
		if ($proxy) { curl_setopt($ch, CURLOPT_PROXY, $proxy["$key"]);  curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);	}
		else { 
			// echo "<br /><h1>Proxy DISABLED!</h1>";
			}

		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_URL, "$url");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT,20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
		// curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
		// curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');

		curl_multi_add_handle( $mh, $ch );
		}

	$prev_running = $running = null;

	// print_r($chs);
	// print_r($proxy);
					
	do {
		curl_multi_exec( $mh, $running );
		curl_multi_select($mh); // снижаем нагрузку процессора
		if ( $running != $prev_running ) {
			$info = curl_multi_info_read( $mh );// получаю информацию о текущих соединениях
			if ( is_array( $info ) && ( $ch = $info['handle'] ) ) {
              $key_success = array_search($ch, $chs, true);
				$regPage = curl_multi_getcontent( $ch );// получаю содержимое загруженной страницы
				$curl_info = curl_getinfo ($info ['handle']);
				
				// $out[] = trim ($proxies [array_search ($done['handle'], $c)]) . "\r\n";

				// получим ответ сервера и разберем его 
				$pos = strpos($regPage, 'вы должны активировать подписку');
				if ($pos === false) {
					$counter_bad_check++;
					// file_put_contents("logs/inviter/inviter-fail.txt","\r\n" . $array_multi_accs["$key_success"], FILE_APPEND);
					// file_put_contents("logs/inviter/_regpage-fail.html",$regPage, FILE_APPEND);
					
					// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t" . '<font style="background-color: #FF0000">Invite failed</font>' . "\tvia proxy: " . $proxy["$key_success"];
					}
				else {
					$counter_good_check++;
					// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t" . '<font style="background-color: #00FF00">Invite ok</font>' . "\tvia proxy: " . $proxy["$key_success"];
					file_put_contents("logs/inviter/inviter-ok.txt","\r\n" . $array_multi_accs["$key_success"], FILE_APPEND);
					file_put_contents("logs/inviter/inviter-ok-" . date("Ymd") . ".txt","\r\n" . $array_multi_accs["$key_success"], FILE_APPEND);
					// file_put_contents("logs/inviter/_regpage-ok.html",$regPage . "\r\n" . $proxy["$key_success"], FILE_APPEND);
					} 
					
				$pos2 = strpos($regPage, 'лимит на количество подписок');
				if ($pos2 != null) {
					$counter_blproxy_check ++;
					echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\t" . '<font style="background-color: #FF0000">Add proxy to BL</font>: ' . $proxy["$key_success"] . "\t". count($proxyArray);
					// print_r($regPage);
					
					$key_bl_proxy = array_search($proxy["$key_success"], $proxyArray, true);
					echo "<br />key to delete: ";
					print_r($key_bl_proxy);
					echo "<br />proxy IP to BL: ";
					print_r($proxyArray[$key_bl_proxy]);
										
					echo "<br />" . count($proxyArray);
					$proxyArray[$key_bl_proxy] = null;
					$proxyArray = optimizeArray($proxyArray);
					$proxyArray = array_values($proxyArray);
					echo " --> ". count($proxyArray);
					
					file_put_contents("resource/proxy-bl.txt","\r\n" . $proxy["$key_success"], FILE_APPEND);
					}
					
				// echo "\t" . 'http_code: ' . $curl_info ['http_code'] . "\tcurl_info-handle: " . $info['handle'];
				}
			$prev_running = $running;// обновляю кешируемое число текущих активных соединений
			}
		}
	while ( $running > 0 );


	foreach ($chs as $ch) {
		curl_multi_remove_handle($mh, $ch);
		curl_close( $ch );
		}
	curl_multi_close($mh);
	echo "<br /><br />Result:<br />Good: $counter_good_check<br />Bad: $counter_bad_check";
	flush();
	
	unset($regPage);
	unset($acc);
	unset($mh);
	unset($chs);
	unset($ch);
	// gc_collect_cycles();
	
	return array($counter_good_check,$counter_bad_check,$counter_blproxy_check,$proxyArray);
	
	// Solve CPU 100% usage, a more simple and right way:
	// do {
		// curl_multi_exec($mh, $running);
		// curl_multi_select($mh);
	// } while ($running > 0);
	}
	
// функция используется в однопоточной функции getTokenChrome
function loadPage($url, $proxy, $proxyPass, $userAgent, $header)
    {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$url");
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	if ($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header); else { echo "<br />Header DISABLED!";}
	if ($proxy) {curl_setopt($ch, CURLOPT_PROXY, $proxy); }	else { 
		// echo "<br />Proxy DISABLED!";
		}
	if ($proxyPass) {url_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);}else {
		// echo "<br />proxyPass DISABLED!";
		}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__)."/".$cookie);
    // curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__)."/".$cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	curl_setopt($ch, CURLOPT_POST, false);
	$answer = curl_exec($ch);
	$curl_info = curl_getinfo($ch);
	echo '<br />url: ' . $url . '<br />http_code: ' . $curl_info ['http_code'] . '<br />';
	curl_close($ch);
	return $answer; 
	}

// функция используется в однопоточной функции getTokenChrome	
function postSend($url, $POST, $proxy, $proxyPass, $userAgent, $header){
	$ch = curl_init("$url");
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL, "$url");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING,'gzip,deflate');
	curl_setopt($ch, CURLOPT_TIMEOUT,10);
	if ($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header); else { echo "<br />Header DISABLED!";}
	if ($proxy) {curl_setopt($ch, CURLOPT_PROXY, $proxy); }	else { 
		// echo "<br />Proxy DISABLED!";
		}
	if ($proxyPass) {url_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);}else {
		// echo "<br />proxyPass DISABLED!";
		}
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
    // curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__)."/".$cookie);
    // curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__)."/".$cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	$ok = curl_exec($ch);
	$curl_info = curl_getinfo($ch);
	echo '<br />url: ' . $url . '<br />http_code: ' . $curl_info ['http_code'] . '<br />';
	curl_close($ch);

	return $ok;
	}	

function getTokenChrome($accounts, $client_id, $redirect_uri, $scope, $proxy = '', $proxyPass = '')
	{
	echo '<pre><h1>Вместо редиректа $redirect_uri заглушка!</h1>' . date("Ymd-His", time()+14400) . '<br /><br />';
	if (!$proxy) echo "<h1>Proxy DISABLED!</h1>";
	if (!$proxyPass) echo "<h1>proxyPass DISABLED!</h1>";
	foreach ($accounts as $key=>$account) {
		$array_acc = explode(":",$account);
		$username = $array_acc[0];
		$password = $array_acc[1];

		// $cookie=$username.".txt";
		// $cookieFilename = dirname(__FILE__)."/".$cookie;
		$cookie="cookie.txt";
		$cookieFilename = dirname(__FILE__) . '/cookie.txt';
		if (file_exists($cookieFilename)) {
			echo "DELETING COOKIE FILE: $cookieFilename... ok<br /><br />";
			unlink($cookieFilename);
		} else {
			echo "NO COOKIE FILE: $cookieFilename<br /><br />";
		}

		// 1. Получаем урл авторизации
		echo "<br /><hr />1. Загрузка страницы с авторизацией..."; flush();
		$authPageURL = "https://api.instagram.com/oauth/authorize/?client_id=$client_id&redirect_uri=http%3A%2F%2Fzachallia.com%2Ftoken&response_type=token&scope=$scope";
		$header = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Host: api.instagram.com',
		'Connection: keep-alive',
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36',
		'Accept-Encoding: gzip, deflate, sdch',
		'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
		'Expect:'
		);

		$authPage = loadPage($authPageURL, $proxy, '', '', $header);
		preg_match('#<input type="hidden" name="csrfmiddlewaretoken" value="(.*?)"/>#', $authPage, $csrfmiddlewaretoken);
		$csrfmiddlewaretoken = trim($csrfmiddlewaretoken[1]);
		echo "<br />csrfmiddlewaretoken: $csrfmiddlewaretoken";

		// 2. Авторизуемся
		echo "<br /><hr />2. POST-запрос авторизации в Instagram/получение ранее выданного токена..."; flush();
		$tokenPageURL = "https://instagram.com/accounts/login/?force_classic_login=&next=/oauth/authorize/%3Fclient_id%3D2974fce230f74bd38c726d5b18761dc2%26redirect_uri%3Dhttp%253A%252F%252Fzachallia.com%252Ftoken%26response_type%3Dtoken%26scope%3Dlikes%2Bcomments%2Brelationships";
		$header = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Content-Type: application/x-www-form-urlencoded',
		'Host: instagram.com',
		'Connection: keep-alive',
		'Cache-Control: max-age=0',
		'Origin: https://instagram.com',
		'Referer: https://instagram.com/accounts/login/?force_classic_login=&next=/oauth/authorize/%3Fclient_id%3D' . $client_id . '%26redirect_uri%3Dhttp%253A%252F%252Fzachallia.com%252Ftoken%26response_type%3Dtoken%26scope%3Dlikes%2Bcomments%2Brelationships',
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36',
		'Accept-Encoding: gzip, deflate',
		'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
		'Expect:'
		);
		$POST = http_build_query(array(
			'csrfmiddlewaretoken' 	=> $csrfmiddlewaretoken,
			'username' 				=> $username,
			'password'         		=> $password
			));
			
		$tokenPage = postSend($tokenPageURL, $POST, $proxy, '', '', $header);
		
		preg_match('#<input type="hidden" name="csrfmiddlewaretoken" value="(.*?)"/>#', $tokenPage, $csrfmiddlewaretoken);
		$csrfmiddlewaretoken = trim($csrfmiddlewaretoken[1]);
		echo "<br />csrfmiddlewaretoken: $csrfmiddlewaretoken";
		
		preg_match('#\#access\_token\=(.*?)#iU', $tokenPage, $token);
		$token = trim($token[1]);
		
		// 3. Если раньше токен не получали, авторизуем приложение
		if (!$token) {
			
			echo "<br /><hr />3. POST-запрос авторизации приложения..."; flush();
				
			$tokenPageURL = "https://instagram.com/oauth/authorize/?client_id=" . $client_id . "&redirect_uri=http%3A%2F%2Fzachallia.com%2Ftoken&response_type=token&scope=likes+comments+relationships";
			$header = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Content-Type: application/x-www-form-urlencoded',
			'Host: instagram.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'Origin: https://instagram.com',
			'Referer: https://instagram.com/oauth/authorize/?client_id=' . $client_id . '&redirect_uri=http%3A%2F%2Fzachallia.com%2Ftoken&response_type=token&scope=likes+comments+relationships',
			'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
			'Expect:'
			);
			$POST = http_build_query(array(
				'csrfmiddlewaretoken' 	=> $csrfmiddlewaretoken,
				'allow' 				=> 'Authorize'
				));
				
			$tokenPage = postSend($tokenPageURL, $POST, $proxy, '', '', $header);
			preg_match('#\#access\_token\=(.*?)#iU', $tokenPage, $token);
			$token = trim($token[1]);
			echo "csrfmiddlewaretoken: $csrfmiddlewaretoken<h3>account: $account<br />token: $token</h3>";
			if ($token) file_put_contents("resource/accounts.txt","\r\n" . $username . ":" . $password. ":" . $client_id. ":" . $token, FILE_APPEND);
			}
		
		else {
			echo "csrfmiddlewaretoken: $csrfmiddlewaretoken<h3>account: $account<br />token: $token</h3>";
			if ($token) file_put_contents("resource/accounts.txt","\r\n" . $username . ":" . $password. ":" . $client_id. ":" . $token, FILE_APPEND);
			flush();
			}
		}
	if ($token) return $token;
	else echo "<h1>Проблема с токеном!</h1>";
	}

// генерация случайного пароля
function rand_passwd( $length = 10, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ) {
    return substr( str_shuffle( $chars ), 0, $length );
}

function dues($str)
{
    return html_entity_decode(
        preg_replace('/\\\\u([a-f0-9]{4})/i', '&#x$1;', $str),
        ENT_QUOTES, 'UTF-8'
    );
}


function HandleHeaderLine( $ch, $header_line ) {
	// if (strpos($header_line, "X-Ratelimit"))
	if (strpos($header_line, "Ratelimit"))
		echo $header_line; // or do whatever
    return strlen($header_line);
}

// получение инфы через API
function getInstagram($url, $proxy = '', $proxyPass = '')
	{
	// echo "<br /><small>";
	$mtimeFunc = microtime(true);
	$ch = curl_init();
		// CURLOPT_HEADER => true,
		
				// curl_setopt($ch, CURLOPT_PROXY, $proxy); // прокси 
		// curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
		
		
	curl_setopt_array($ch, array(
		CURLOPT_HEADERFUNCTION => "HandleHeaderLine",
		CURLOPT_URL => $url,
		
		CURLOPT_PROXY => $proxy,
		CURLOPT_PROXYUSERPWD => $proxyPass,
		
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => 2
		));
	
	while (!$result)	{
		if (file_exists("stop.txt")) {exit("<br />stop.txt getInstagram");}
		$result = curl_exec($ch);
		// echo "<br />getInstagram. URL... " . $url; 
		flush();
		}	
	
	curl_close($ch);
	// echo "<h1>$curl</h1>";
	// echo "<br />GET response size: " . mb_strlen ($result);
	// echo "<br />GET response time: " . round((microtime(true) - $mtimeFunc) * 1, 4) . '<br />';
	flush();
	return $result;
	}

// отправка POST запроса (лайки, каменты, фоловинг)
function postInstagram($url, $post, $proxy = '', $proxyPass = ''){
	echo "<br /><hr />";
	$mtimeFunc = microtime(true);
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_HEADERFUNCTION => "HandleHeaderLine",
		CURLOPT_URL => $url,
		
		CURLOPT_PROXY => $proxy,
		CURLOPT_PROXYUSERPWD => $proxyPass,
		
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_POSTFIELDS => $post,
		CURLOPT_POST, true
		));
		
		
	// $result = curl_exec($ch);
	while (!$result)	{
		if (file_exists("stop.txt")) {exit("<br />stop.txt postInstagram");}
		$result = curl_exec($ch);
		echo "<br />postInstagram. URL... " . $url; 
		flush();
		}	
	
	curl_close($ch);
	// echo "<br />POST response size: " . mb_strlen ($result);
	// echo "<br />POST response time: " . round((microtime(true) - $mtimeFunc) * 1, 4) . '<br />';
	return $result;


	}

// пауза случайной длительности	
function rndSleep($pauseMin,$pauseMax) {
	$pause = rand ($pauseMin,$pauseMax); 
	echo "<br />Pause $pause s.";
	flush();
	sleep($pause);
	}

// получает популярные media (время запроса около 4.0 секунд без прокси)
function getPopular($token, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	$url = 'https://api.instagram.com/v1/media/popular?access_token=' . $token;

	$popular = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($popular,true);      
	$arrayMedia = $jsonDecoded['data'];
	$arrayMeta = $jsonDecoded['meta'];

	foreach($arrayMedia as $key => $media) {
		echo "<br /><hr /><br />$key. ID: " . $media['id'] . 
		"<br />Caption: " . $media['caption']['text'] . 
		"<br />From User:UserName:UserID: " . $media['caption']['from']['username'] . ":" . $media['caption']['from']['full_name'] . ":" . $media['caption']['from']['id'] . 
		"<br />Likes: " . $media['likes']['count'] . 
		"<br />Comments: ". $media['comments']['count'] . 
		"<br />I like? " . $media['user_has_liked'];
		}
	
	return $arrayMedia;
	}	
	
	
// получает выдачу по тегу
function getTag($token, $tagName, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	
	$url = 'https://api.instagram.com/v1/tags/' . $tagName . '/media/recent?access_token=' . $token;
	echo "<br />url: $url";
	$tag = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($tag,true);      
	$tagMedia = $jsonDecoded['data'];

	foreach($tagMedia as $key => $media) {
		echo "<br /><hr /><br />$key. ID: " . $media['id'] . 
		"<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		"<br />Comments: " . $media['comments']['count'] . 
		"<br />Likes: " . $media['likes']['count'] . 
		"<br />I like? " . $media['user_has_liked'];
		}
		
	print_r($jsonDecoded);	
	
	return $tagMedia;
	}	


// получает общую инфу про медиа по media-id
function getMediaID($token, $mediaID, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	$url = 'https://api.instagram.com/v1/media/' . $mediaID . '?access_token=' . $token;
// echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);      
	$arrayMedia = $jsonDecoded['data'];
	// print_r($jsonDecoded);	

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $jsonDecoded;
	}	
	
	
// получает лайки к медиа по media-id
function getLikes($token, $mediaID, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	$url = 'https://api.instagram.com/v1/media/' . $mediaID . '/likes?access_token=' . $token;
// echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);      
	$arrayMedia = $jsonDecoded['data'];
	print_r($jsonDecoded);	

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $arrayMedia;
	}		

// получает каменты к медиа по media-id
function getComments($token, $mediaID, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	$url = 'https://api.instagram.com/v1/media/' . $mediaID . '/comments?access_token=' . $token;
// echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);      
	$arrayMedia = $jsonDecoded['data'];
	// print_r($jsonDecoded);	

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $arrayMedia;
	}
	
// получает follows (кого читает аккаунт субъекта) по userID
function getFollows($token, $userID, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}

	$url = 'https://api.instagram.com/v1/users/' . $userID . '/follows?access_token=' . $token;
// echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);      
	$arrayMedia = $jsonDecoded['data'];
	print_r($jsonDecoded);	

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $arrayMedia;
	}	
	
	
// получает список медиа по userID исходного пользователя
function getMediaAll($token, $userID, $startCursor = '', $proxy = '', $proxyPass = '', $mtime)
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	$getCommentersСounter = 0;
	
	// &cursor=1424848270706
	if ($startCursor) { 
		// https://api.instagram.com/v1/users/13151837/media/recent?access_token=1673153022.3a81a9f.8cd7a4b7a820493c9105b116beb43c62&max_id=1373647833890335863_13151837
		$url = 'https://api.instagram.com/v1/users/' . $userID . '/media/recent/?access_token=' . $token . '&max_id=' . $startCursor;
	}
	else {
		// https://api.instagram.com/v1/users/13151837/media/recent/?access_token=1673153022.3a81a9f.8cd7a4b7a820493c9105b116beb43c62
		$url = 'https://api.instagram.com/v1/users/' . $userID . '/media/recent/?access_token=' . $token;
	}
echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true); 
	$arrayData = $jsonDecoded['data'];
	$url = $jsonDecoded['pagination']['next_url'];
	// print_r($jsonDecoded);	
	
	// первый проход
	foreach($arrayData as $key => $media) {
		// echo "<br />$key. ID: " . $media['id'];
		$mediaIDnow = trim($media['id']);
		file_put_contents("resource/commenters/" . $userID . "-media-all.txt", "\n" . $mediaIDnow, FILE_APPEND);
		}
	$getCommentersСounter++;
		

	// дальнейшие проходы
	while ($url)	{
		if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
		echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tCounter: " . $getCommentersСounter*20;
		$url = changeToken($url);
		$arrayMedia = getInstagram($url, $proxy, $proxyPass);
		$jsonDecoded = json_decode($arrayMedia,true); 
		$arrayData = $jsonDecoded['data'];
		$url = $jsonDecoded['pagination']['next_url'];
		// print_r($arrayMedia);
		// file_put_contents("resource/log-getmediaall.txt", "\n\n\n" . $arrayMedia, FILE_APPEND);
		
		if (!json_decode($arrayMedia,true)) {
			
			echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tError 725-1: JSON DECODE ERROR! getCommentersСounter: " . $getCommentersСounter*20;
			$url = changeToken($url);
			$arrayMedia = getInstagram($url, $proxy, $proxyPass);
			$jsonDecoded = json_decode($arrayMedia,true); 
			$arrayData = $jsonDecoded['data'];
			$url = $jsonDecoded['pagination']['next_url'];
			// file_put_contents("resource/log-getmediaall.txt", "\n\nERROR 724!\n" . $arrayMedia, FILE_APPEND);
			
			if (!json_decode($arrayMedia,true)) {
				echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tError 725-2: JSON DECODE ERROR! getCommentersСounter: " . $getCommentersСounter*20;
				$url = changeToken($url);
				$arrayMedia = getInstagram($url, $proxy, $proxyPass);
				$jsonDecoded = json_decode($arrayMedia,true); 
				$arrayData = $jsonDecoded['data'];
				$url = $jsonDecoded['pagination']['next_url'];
				// file_put_contents("resource/log-getmediaall.txt", "\n\nERROR 733!\n" . $arrayMedia, FILE_APPEND);
				
				if (!json_decode($arrayMedia,true)) {
					echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tError 725-3: JSON DECODE ERROR! getCommentersСounter: " . $getCommentersСounter*20;
					$url = changeToken($url);
					$arrayMedia = getInstagram($url, $proxy, $proxyPass);
					$jsonDecoded = json_decode($arrayMedia,true); 
					$arrayData = $jsonDecoded['data'];
					$url = $jsonDecoded['pagination']['next_url'];
					// file_put_contents("resource/log-getmediaall.txt", "\n\nERROR 742!\n" . $arrayMedia, FILE_APPEND);
				}
			}
		}

			
		
		foreach($arrayData as $key => $media) {
			// echo "<br />$key. ID: " . $media['id'];	
			$mediaIDnow = trim($media['id']);
			file_put_contents("resource/commenters/" . $userID . "-media-all.txt", "\n" . $mediaIDnow, FILE_APPEND);
			}	
			
		// $cursor = parseParameterFromURL($url, 'cursor');
		// if ($cursor) file_put_contents("resource/commenters/" . $userID . "-cursor.txt", $cursor);
		// rndSleep(1,3);
		usleep(300000);
		$getCommentersСounter++;
		}
	
	// https://api.instagram.com/v1/users/27392117/followed-by?cursor=1423980818141&access_token=1688876767.2974fce.bbcb3df5eea94622b74ff90368bda8d1 HTTP/1.1

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
	 
	
	return $arrayMedia;
	}	


// получает followed by (подписчики, т.е. кто читает аккаунт субъекта) по userID
// function getFollowedBy($token, $userID, $startCursor = '', $proxy = '', $proxyPass = '', $mtime)
function getFollowedBy($token, $userID, $startCursor = '', $proxy = '', $proxyPass = '', $mtime)
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	$getFollowedByСounter = 0;
	
	// &cursor=1424848270706
	if ($startCursor) { $url = 'https://api.instagram.com/v1/users/' . $userID . '/followed-by?access_token=' . $token . '&cursor=' . $startCursor;}
	else {$url = 'https://api.instagram.com/v1/users/' . $userID . '/followed-by?access_token=' . $token;}
	echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true); 
	$arrayData = $jsonDecoded['data'];
	$url = $jsonDecoded['pagination']['next_url'];
	// print_r($jsonDecoded);	
	
	// первый проход
	foreach($arrayData as $key => $media) {
		echo "<br />$key. ID: " . $media['id'] . ":" . $media['username'] . ":" . $media['full_name'];
		file_put_contents("resource/followed-by/" . $userID . "-onlyid.txt", "\r\n" . $media['id'], FILE_APPEND);
		file_put_contents("resource/followed-by/" . $userID . "-full.txt", "\r\n" . $media['id'] . "|%|" . $media['username'] . "|%|" . $media['full_name'] . "|%|" . $media['bio'] . "|%|" . $media['website'] . "|%%%%%|", FILE_APPEND);
		$getFollowedByСounter++;
		}

	// дальнейшие проходы
	while ($url)	{
		// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tCounter: " . ($getFollowedByСounter*50);
		echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tCounter: " . $getFollowedByСounter;
		$url = changeToken($url);
		$arrayMedia = getInstagram($url, $proxy, $proxyPass);
		$jsonDecoded = json_decode($arrayMedia,true); 
		$arrayData = $jsonDecoded['data'];
		$url = $jsonDecoded['pagination']['next_url'];

		// print_r($arrayMedia);
		foreach($arrayData as $key => $media) {
			// echo "<br />$key. ID: " . $media['id'] . ":" . $media['username'] . ":" . $media['full_name'];
		file_put_contents("resource/followed-by/" . $userID . "-onlyid.txt", "\r\n" . $media['id'], FILE_APPEND);
		// file_put_contents("resource/followed-by/" . $userID . "-full.txt", "\r\n" . $media['id'] . "|%|" . $media['username'] . "|%|" . $media['full_name'] . "|%|" . $media['bio'] . "|%|" . $media['website'] . "|%%%%%|", FILE_APPEND);
			}	
			
		$cursor = parseParameterFromURL($url, 'cursor');
		// echo "<br /><br /><br />МЛЯ КУРСОР!!1: ". $cursor;
		// echo '<br />';
		// print_r($cursor);
		if ($cursor) file_put_contents("resource/followed-by/" . $userID . "-cursor.txt", $cursor);
		// rndSleep(3,5);
		$getFollowedByСounter++;
		}
	
	// https://api.instagram.com/v1/users/27392117/followed-by?cursor=1423980818141&access_token=1688876767.2974fce.bbcb3df5eea94622b74ff90368bda8d1 HTTP/1.1

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $arrayMedia;
	}		
	
// поиск ID пользователей по имени пользователя
function searchUserId($token, $query, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	
	// https://api.instagram.com/v1/users/search?q=jack&access_token=ACCESS-TOKEN
	$url = 'https://api.instagram.com/v1/users/search?q=' . $query . '&access_token=' . $token;
	echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);  

	foreach ($jsonDecoded['data'] as $key => $data) {
		$imgSrc = $data['profile_picture'];
		$username = $data['username'];
		$fullname = $data['full_name'];
		$userID = $data['id'];
		echo '<br /><hr /><img src="' . $imgSrc . '" alt="' . $fullname . '">';
		echo '<br />' . $userID . ":" . $username . ":" . $fullname . "<br />";
		
		// file_put_contents("resource/users.txt","\r\n" . $userID . "|%|" . $username. "|%|" . $fullname, FILE_APPEND);
		file_put_contents("resource/users.txt","\n" . $userID . "|%|" . $username. "|%|" . $fullname, FILE_APPEND);
		
		
		$arrayUserID = getUserID($token, $userID, $proxy, $proxyPass);
		print_r($arrayUserID);	
		}

	// foreach($arrayMedia as $key => $media) {
		// echo "<hr /><br />$key. ID: " . $media['id'] . 
		// "<br />From User:UserName:UserID: " . $media['user']['username'] . ":" . $media['user']['full_name'] . ":" . $media['user']['id'] . 
		// "<br />Comments: " . $media['comments']['count'] . 
		// "<br />Comments: " . $media['likes']['count'] . 
		// "<br />I like? " . $media['user_has_liked'];
		// }
		
	
	return $jsonDecoded;
	}	

// выбирает случайный токен, указанный в $path	
function rndToken($path = 'resource/accounts.txt', $default=null) {
// function rndToken($path = 'http://27podarkov.ru/kayla/resource/accounts.txt', $default=null) {
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	$arrayToken = file($path);	
	$arrayToken = optimizeArray($arrayToken);
	// print_r($arrayToken);
    $k = mt_rand(0, count($arrayToken) - 1);
	$account = explode(":", $arrayToken[$k]);
	echo "\n<br />Changing token. Using account: " . $account[0] . " | Token: " . $account[3];
	return $account[3];
	}
	
// выбирает случайный элемент массива
function rndArrayValue($array, $default=null)
	{
    $k = mt_rand(0, count($array) - 1);
    return isset($array[$k])? $array[$k]: $default;
	}
	
// получает информацию о пользователе по userID
function getUserID($token, $query, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	
	$url = 'https://api.instagram.com/v1/users/' . $query . '?access_token=' . $token;
// echo "<br />url: $url";
	$arrayMedia = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayMedia,true);  
	$data = $jsonDecoded['data'];
	// foreach ($jsonDecoded['data'] as $key => $data) {
		// $imgSrc = $data['profile_picture'];
		// $username = $data['username'];
		// $fullname = $data['full_name'];
		// $userID = $data['id'];
		// echo '<hr /><img src="' . $imgSrc . '" alt="' . $fullname . '">';
		// echo '<br />' . $userID . ":" . $username . ":" . $fullname;
		// file_put_contents("resource/users.txt","\r\n" . $userID . "|%|" . $username. "|%|" . $fullname, FILE_APPEND);
		// }
	
	
		
	// $imgSrc = $data['profile_picture'];
	// $username = $data['username'];
	// $fullname = $data['full_name'];
	// $userID = $data['id'];
	// echo '<br /><hr /><img src="' . $imgSrc . '" alt="' . $fullname . '">';
	// echo '<br />' . $userID . ":" . $username . ":" . $fullname;	
	// echo '<br />Media: ' . $data['counts']['media'] . "<br />followed_by: " . $data['counts']['followed_by'] . "<br />follows: " . $data['counts']['follows'];	
	
	// print_r($jsonDecoded);
	
	return $jsonDecoded;
	}	
	
// меняет токен в url (используется для сбора последовательной информации, например, подписчиков субъекта, где курсор указывает на новую страницу и парсить ее другим токеном
function changeToken($url) {
	echo '<br /><br />Changing token...';
	$oldUrl = $url;
	$parts = parse_url($url);
	parse_str($parts['query'], $query);
	$query['access_token'] = rndToken();
	$newQuery = http_build_query($query);
	$newUrl = $parts['scheme'] . '://' . $parts['host']. $parts['path'] . "?" . $newQuery;
	echo '<br />Old URL: ' . $oldUrl . '<br />New URL: ' . $newUrl;
	return $newUrl;
	}	
	
	
// получает все медиа указанного пользователя
function getUserMedia($token, $userID, $proxy = '', $proxyPass = '')
	{
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	$url = 'https://api.instagram.com/v1/users/' . $userID . '/media/recent/?access_token=' . $token;
// echo "<br />url: $url";
	$arrayQuery = getInstagram($url, $proxy, $proxyPass);
	$jsonDecoded = json_decode($arrayQuery,true); 
	$arrayData = $jsonDecoded['data'];
	$url = $jsonDecoded['pagination']['next_url'];
	// print_r($arrayData);	
	
	// получим случайный элемент из массива списка медиа
	$rndMedia = rndArrayValue($arrayData); 
	
	// инфа о медиа
	// echo "<br />Random media from (UserID:Username:Fullname): " . $rndMedia['user']['id'] . ':' . $rndMedia['user']['username'] . ':' . $rndMedia['user']['full_name'];
	// echo "<br />Media ID: " . $rndMedia['id'] . "<br />I give a like? " . $rndMedia['user_has_liked'] . "<br />Likes: " . $rndMedia['likes']['count'] . ", Comments: " . $rndMedia['comments']['count'];
	// echo '<br /><img src="' . $rndMedia['images']['low_resolution']['url'] . '">' . '<br />' . $rndMedia['caption']['text'] ;
	
	
	
	/* 
	// первый проход (он же единственный, если надо будет получить всю ленту - смотрим аналогичный цикл функции getFollowedBy со сменой токена
	foreach($arrayData as $key => $media) {
		// инфа о каждой медиа
		echo "<br /><br />$key. ID: " . "<br />Media ID: " . $media['id'] . "<br />I give a like? " . $media['user_has_liked'] . "<br />Likes: " . $media['likes']['count'] . "<br />Comments: " . $media['comments']['count'];
	 */	
		// случайная медиа указанного пользователя
		

		
	// print_r($jsonDecoded);
	return $jsonDecoded;
	}		
	
// сбор параметров из урла	
function parseParameterFromURL($url, $parameter) {
	$parts = parse_url($url);
	parse_str($parts['query'], $query);
	echo "<br />Parsing " . $parameter . "... " . $query["$parameter"];
	return $query["$parameter"];
	}	
	
// уникализирование файла построчно	
function uniqueFile($path) {
	$time = time();
	$zapros = file_get_contents($path);
	file_put_contents($path . ".$time.bak",$zapros);	
	$urls = explode("\r\n", $zapros);
	$zapros = null;
	echo '<br />Уникализируем файл построчно...<br />Было: ' . count($urls);
	flush();
	// $urls = array_unique($urls);
	// $urls = array_diff($urls, array(''));
	$urls = optimizeArray($urls);
	echo '<br />Стало: ' . count($urls) . '<br /><br />';
	// foreach ($urls as $key => $url) {
		// echo "<br />$url";
		// }
	echo "<br />$time";
	file_put_contents($path,implode($urls,"\r\n"));	
	// gc_collect_cycles();
	}
?>

