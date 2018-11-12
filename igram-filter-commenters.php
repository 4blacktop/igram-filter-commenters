<?php
// проходит список медиа из файла
// открывает каждое медиа и собирает ид комментаторов
// фильтрует комментаторов
// каунтерMax было 100 для крона (300-400 сек) 5000 локально (долго) на годадди 3 юсера проходит (15-17 сек) сейчас думаю ок будет 50 чтобы 150-200 выполнялся
// ид 27билетов 1773227325 а основной который щас скрейпится 10051934

// готовимся
header("Content-Type: text/html; charset=utf-8");
// set_time_limit(86400);ini_set('max_execution_time', 86400);




set_time_limit(3600);ini_set('max_execution_time', 3600);
ob_implicit_flush(true);session_start();ob_end_flush();ob_start();

$mtime = microtime(true);
include_once('inc.php'); 
ini_set('memory_limit', '512M');
echo '<pre>' . date("Ymd-His", time()) . '<br /><br />'; flush(); ob_flush();
echo '<br />max_execution_time: ' . ini_get('max_execution_time'); 



// main settings
$mainUserId = '1800284431'; // main user ID

// $counterMax = 100; // not recommended if using cron // max number of commenting user per launch
// $counterMax = 25; // default setting before 2018  // max number of commenting user per launch
$counterMax = 50; // max number of commenting user per launch
$pauseMin = 1; // min random pause between queries
$pauseMax = 2; // max random pause between queries
$kSleep = 5; // pause multiplier between media id

// filter settings
$filterMediaMin = 200; // min number of media (posts, video) of commenting user
$filterFollowersMin = 10000; // min number of followers of commenting user
$filterFollowersMax = 100000; // max number of followers of commenting user
$filterBioText = '@'; // search string in bio of  commenting user
$filterLikesMin = 200; // min number of likes in positions 4,5,6,7,8,9 of commenting user
$filterCommentsMin = 10; // min number of comments in positions 4,5,6,7,8,9 of commenting user

// сбросим кэш
clearstatcache(); // var_dump(file_exists("lock.txt")); 
// if (file_exists("lock.txt")) {}

if (file_exists("lock.txt")) {
	$timeCreated = filemtime("lock.txt");
	$runningTime = time() - $timeCreated;
		if ($runningTime > 3600) {
		unlink('lock.txt');
		$execTime = round((microtime(true) - $mtime) * 1, 4);
		file_put_contents("resource/log.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId. "\trst", FILE_APPEND);
		// echo '<span style="background-color: aqua;">STATUS: Script is finishing.</span>';
	}
	else {
		exit("<br />lock.txt: Only one thread of script allowed at a time!");
		// echo '<span style="background-color: aqua;">STATUS: Script is running for ' . $runningTime .  ' seconds.</span>';
	}
}
else {
	// echo '<span style="background-color: yellow;">STATUS: Script is waiting for cron</span>';
	file_put_contents("lock.txt","lock");
}






echo "<h2>Settings</h2>";
echo "<br />filterLikesMin: " . $filterLikesMin;
echo "<br />filterCommentsMin: " . $filterCommentsMin;
echo "<br />filterMediaMin: " . $filterMediaMin;
echo "<br />filterFollowersMin: " . $filterFollowersMin;
echo "<br />filterFollowersMax: " . $filterFollowersMax;
echo "<br />filterBioText: " . $filterBioText;


// получим массив обработанных медиа ИД исходного пользователя
// $arrayProcessedMediaID = file("resource/commenters/$mainUserId-processed-mediaid.txt");	
// $arrayProcessedMediaID = optimizeArray($arrayProcessedMediaID);

// получим массив со списком медиа ИД
// $arrayMediaAll = file("resource/commenters/$mainUserId-media-all-test.txt");	

// проверим сколько сейчас у пользователя всего медиа
$token = rndToken();
$arrayUserID = getUserID($token, $mainUserId, $proxy, $proxyPass);
$countMedia = $arrayUserID['data']['counts']['media'];
echo "<br />Media count by BIO: " . $countMedia;

// проверим, есть ли файл со всеми медиа, да = загрузим
if (file_exists("resource/commenters/$mainUserId-media-all.txt")) {
	$arrayMediaAll = file("resource/commenters/$mainUserId-media-all.txt");	
	$arrayMediaAll = optimizeArray($arrayMediaAll);
	$fileMediaCount = count($arrayMediaAll);
	echo "<br />Media count by FILE: " . $fileMediaCount;
	
	// if ($fileMediaCount < $countMedia) {
	if ($fileMediaCount < $countMedia) { // если появились новые медиа
		// получим список фоловеров
		unlink("resource/commenters/$mainUserId-media-all.txt");
		$arrayFollowedBy = getMediaAll($token, $mainUserId, $startCursor, $proxy, $proxyPass, $mtime);
		unlink('lock.txt');
		$execTime = round((microtime(true) - $mtime) * 1, 4);
		file_put_contents("resource/log.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId. "\tupdate media", FILE_APPEND);
		exit('<br /><br />Done getMediaAll if');
	}
	
}

// нет = получим все медиа и подгрузим файл
else {
	// получим список фоловеров
	$arrayFollowedBy = getMediaAll($token, $mainUserId, $startCursor, $proxy, $proxyPass, $mtime);
	unlink('lock.txt');
	$execTime = round((microtime(true) - $mtime) * 1, 4);
	file_put_contents("resource/log.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId. "\tinitial media", FILE_APPEND);
	exit('<br /><br />Done getMediaAll else');
}
	

// получим массив со списком обработанных медиа ИД
if (file_exists("resource/commenters/$mainUserId-media-processed.txt")) {
$arrayMediaProcessed = file("resource/commenters/$mainUserId-media-processed.txt");	
$arrayMediaProcessed = optimizeArray($arrayMediaProcessed);
}

// удалим из исходного массива акков те, которые успешно приглашены
if ($arrayMediaProcessed) {
	$arrayMedia = array_diff ($arrayMediaAll, $arrayMediaProcessed);
	echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tMedia to process: " . count($arrayMedia); flush(); ob_flush();
	}
else {
	$arrayMedia = $arrayMediaAll;
	echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tNO PROCESSED MEDIA! Media to process: " . count($arrayMedia); flush(); ob_flush();
	}
// print_r($arrayMedia);
// gc_collect_cycles();

			
// получим массив обработанных юзеров
// $arrayProcessedUserID = file("resource/commenters/$mainUserId-users-processed-all.txt");	
$arrayProcessedUserID = file("resource/commenters/users-processed-all.txt");	
$arrayProcessedUserID = optimizeArray($arrayProcessedUserID);


// получим инфо о главном юзере
$token = rndToken();
$arrayMainUserID = getUserID($token, $mainUserId, $proxy, $proxyPass);
// <h1>Отключен "почистим файл на входе"!</h1>
echo "<h1>Main User ID: " . $mainUserId . ", Full Name: " . $arrayMainUserID['data']['username'] . "<h1>";
// print_r($arrayMainUserID);

foreach ($arrayMedia as $key => $mediaID) {
	if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
	
	// так можно получить информацию о медиа, но нам для фильтра она не нужна
	// $mediaInfo = getMediaID($token, $mediaID, $proxy, $proxyPass);
	
	// получаем каменты к медиа
	$token = rndToken();
	echo '<hr /><h2>' . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tScraping Media ID: " . $mediaID . " ($counterGood / $counterMax)</h2>"; flush(); ob_flush();
	$commentsMedia = getComments($token, $mediaID, $proxy, $proxyPass);
	// print_r($commentsMedia);
	
	// запускаем проход всех комментариев к медиа ИД
	foreach ($commentsMedia as $keyComment => $comment) {
		if (file_exists("stop.txt")) {exit("<br />stop.txt!");}
		// if ($keyComment > 4) {exit("<br />keyComment > 1!");} // ограничение на проход
		// if ($counterGood >= $counterMax) {exit("<br />counterGood >= counterMax!");}	
		if ($counterGood >= $counterMax) {
			echo "<br /><br />Memory Usage: " . number_format(memory_get_usage(), 0, '.', ' ');
			echo "<br />Memory Peak Usage: " . number_format(memory_get_peak_usage(), 0, '.', ' ');
			$execTime = round((microtime(true) - $mtime) * 1, 4);
			file_put_contents("resource/log.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId, FILE_APPEND);
			echo '<br /><br />Exec time: ' . $execTime . ' s.';
			echo "<br />DEBUG INFO 169 ExecTime: " . $execTime;
			unlink('lock.txt');
			exit('<hr /><br />Script finished ok (counterGood reached), you may download files with right click and "save as..." option<br />
		<a href="resource/commenters/' . $mainUserId . '-user-info-filtered.txt">Filtered Users</a><br />
		<a href="resource/commenters/user-info-all.txt">All Users</a>
		');}	
		
		// echo '<h3>' . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tScraping Comment ID: " . $mediaID . "</h3>"; flush();
		
		$userID = $comment['from']['id'];
		
		if (in_array($userID, $arrayProcessedUserID)) {
			// echo "<br />UserID: $userID already processed!";
			echo ".";
			continue; // для перехода к следующему, после отладки раскомментить строку!!!11111
		}
		
		// запишем что прошли пользователя
		file_put_contents("resource/commenters/users-processed-all.txt","\n" . $userID, FILE_APPEND);
		$arrayProcessedUserID[] = $userID;
		
		// получаем массив данных о комментаторе
// echo "<h3>" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tComment from userID: " . $userID . " ($keyComment / " . count($commentsMedia) . ")</h3>"; flush(); ob_flush();
		$token = rndToken();
		$userInfo = getUserID($token, $userID, $proxy, $proxyPass);
		
		$metaCode = $userInfo['meta']['code'];
		file_put_contents("resource/getuserid.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId . "\t" . $metaCode . "\t" . $userID, FILE_APPEND);
		
		// лимит токена
		if (($userInfo['meta']['code']) == '429') {
			print_r($userInfo);
			echo "<br /><br />Memory Usage: " . number_format(memory_get_usage(), 0, '.', ' ');
			echo "<br />Memory Peak Usage: " . number_format(memory_get_peak_usage(), 0, '.', ' ');
			echo '<br /><br />Exec time: ' . round((microtime(true) - $mtime) * 1, 4) . ' s.</pre>';
			unlink('lock.txt');
			exit("<br />Error 429 Token limits!");
			}
			
		// ошибка 400 - подозрение на спам
		elseif (($userInfo['meta']['code']) == '400') {
			print_r($userInfo);
			echo '<br />Error 400';
		// echo "<h3>DEBUG 175 counterGood++:" . $counterGood . "</h3>";
		$counterGood++;			
			continue; // для перехода к следующему юсеру, т.к. у этого закрытый профиль и поэтому АПИ дает ошибку 400
			// exit("<br />Error 400");
			}	
			
		// общая ошибка
		elseif (($userInfo['meta']['code']) != '200') {
			echo '<br />ERROR! Code != 200';
			print_r($userInfo);
			sleep($pauseMax*$kSleep);
			}
		
		$userInfo = $userInfo['data'];
		
		
		$commenterImgSrc = $userInfo['profile_picture'];
		$commenterUsername = $userInfo['username'];
		$commenterBio = $userInfo['bio'];
		$commenterBio = str_replace("\r", "", $commenterBio);
		$commenterBio = str_replace("\n", "", $commenterBio);
		$commenterWebsite = $userInfo['website'];
		$commenterFullname = $userInfo['full_name'];
		$commenterMediaCounter = $userInfo['counts']['media'];
		$commenterFollowedBy = $userInfo['counts']['followed_by'];
		$commenterFollows = $userInfo['counts']['follows'];
		$commenterUserID = $userInfo['id'];
	
		// получим имя и фамилию
		$commenterUsernameArray = explode(" ", $commenterFullname);
		$commenterFirstName = $commenterUsernameArray[0];
		$commenterLastName = $commenterUsernameArray[1];
		
		// получим мыло
		preg_match_all("#[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+#i", $commenterBio, $email, PREG_PATTERN_ORDER);
		$email = implode(",", $email[0]);
		
		
		
		// информация о комментаторе
		$TMPcommenterImgSrc = $userInfo['profile_picture'];
		$TMPcommenterUsername = $userInfo['username'];
		$TMPcommenterBio = $userInfo['bio'];
		$TMPcommenterBio = str_replace("\r", " ", $TMPcommenterBio);
		$TMPcommenterBio = str_replace("\n", " ", $TMPcommenterBio);
		$TMPcommenterWebsite = $userInfo['website'];
		$TMPcommenterFullname = $userInfo['full_name'];
		$TMPcommenterMediaCounter = $userInfo['counts']['media'];
		$TMPcommenterFollowedBy = $userInfo['counts']['followed_by'];
		$TMPcommenterFollows = $userInfo['counts']['follows'];
		$TMPcommenterUserID = $userInfo['id'];
		// print_r($userInfo);
		
		// выведем инфу о комментаторе
		// echo '<br />media: ' . $commenterMediaCounter . "<br />followed_by: " . $commenterFollowedBy . "<br />follows: " . $commenterFollows;	
		// echo '<br /><hr /><img src="' . $commenterImgSrc . '" alt="' . $fullname . '">';
		// echo "<br />" . number_format((microtime(true) - $mtime), 2, '.', ' ') . "\tComment from  userID: " . $userID; flush();
		
		// получим имя и фамилию
		$TMPcommenterUsernameArray = explode(" ", $TMPcommenterFullname);
		$TMPcommenterFirstName = $TMPcommenterUsernameArray[0];
		$TMPcommenterLastName = $TMPcommenterUsernameArray[1];
		
		// получим мыло
		preg_match_all("#[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+#i", $TMPcommenterBio, $TMPemail, PREG_PATTERN_ORDER);
		$TMPemail = implode(",", $TMPemail[0]);
		
		$TMPoutput = 
		$TMPcommenterUsername . "\t" .
		"https://www.instagram.com/" . $TMPcommenterUsername . "/\t" .
		$TMPcommenterMediaCounter . "\t" .
		$TMPcommenterFollowedBy . "\t" .
		$TMPemail . "\t" .
		$TMPcommenterBio . "\t" .
		"https://www.instagram.com/" . $arrayMainUserID['data']['username'] . "/\t" .
		$TMPcommenterFirstName . "\t" .
		$TMPcommenterLastName . "\t" .
		$TMPcommenterFullname . "\t" .
		$arrayMainUserID['data']['username'] . "\t" .
		$mainUserId . "\t" .
		$TMPcommenterUserID;
		
		file_put_contents("resource/commenters/user-info-all.txt","\n" . $TMPoutput, FILE_APPEND);
		
		// проверка пользователя на соответствие фильтру по количеству медиа и фолловеров, а также наличия знака '@' в био
		// if($commenterFollowedBy > 30000 && $commenterFollowedBy < 85000 && $commenterMediaCounter > 1000){
		$pos = strpos($commenterBio, $filterBioText);	
		rndSleep($pauseMin,$pauseMax);
// echo 'FollowedBy & Media Number & Bio filter:<br />Media: ' . $commenterMediaCounter . "\tFollowed_by: " . $commenterFollowedBy; flush(); ob_flush();	
// echo "\tstrpos of filterBioText '" . $filterBioText . "': " . $pos;
		if($commenterFollowedBy > $filterFollowersMin && $commenterFollowedBy < $filterFollowersMax && $commenterMediaCounter > $filterMediaMin && $pos !== false){	
// echo '<br /><strong><span style="background-color: aquamarine;">Commenter FollowedBy & Media Number & Bio filter OK!</span></strong>';
		}
		else {
// echo "<br /><strong>Commenter FollowedBy & Media Number & Bio filter BAD!</strong>";
// echo "<br />commenterFollowedBy = $commenterFollowedBy\tMin = $filterFollowersMin\tMax = $filterFollowersMax";
// echo "<br />commenterMediaCounter = $commenterMediaCounter\tMin = $filterMediaMin";
// echo "<br />bio: $commenterBio\tSearch = $filterBioText";
			$counterGood++;
			// echo "<h3>DEBUG 306 counterGood++:" . $counterGood . "</h3>";
			continue; // для перехода к следующему юсеру, т.к. этот кривой (после отладки раскомментить строку!!!11111)
		}
		
		// получим недавние медиа комментатора для дальнейшей проверки количества лайков на позициях 4-9 а в массиве 3-8
		$token = rndToken();
// echo "<br /><br />Commenter Media filter"; flush(); ob_flush();
		$commenterMedia = getUserMedia($token, $commenterUserID, $proxy = '', $proxyPass = '');
		
		// пройдем медиа пользователя в позициях 3-8
		$arrayCommenterRecentMedia = $commenterMedia['data'];
		for ($i = 3; $i <= 8; $i++) {
// echo '<br />Media #' . $i . "\tLikes :" . $arrayCommenterRecentMedia["$i"]['likes']['count'] . "\tComments:" . $arrayCommenterRecentMedia["$i"]['comments']['count'];
			if ($arrayCommenterRecentMedia["$i"]['likes']['count'] > $filterLikesMin && $arrayCommenterRecentMedia["$i"]['comments']['count'] > $filterCommentsMin) {
				$counterGoodMediaLikes++;
				$counterGoodMediaComments++;
			}								
		}
		
		// проверка на соответсвие фильтру по лайкам и каментам, число в условии - это сколько надо постов с минимальным количеством лайков
		if($counterGoodMediaLikes >= 3 && $counterGoodMediaComments >= 3){
// echo '<br /><strong><span style="background-color: aqua ;">GREAT! Commenter Media filter OK!</span></strong>';
		}
		else {
// echo "<br /><strong>Commenter's Media Likes & Comments BAD!</strong>";
// echo "<br />counterGoodMediaLikes = $counterGoodMediaLikes\tMin = 6";
// echo "<br />commenterFollowedBy = $commenterFollowedBy\tMin = 6";
			$counterGood++;
		// echo "<h3>DEBUG 341 counterGood++:" . $counterGood . "</h3>";
			continue; // 																			для перехода к следующему юсеру, т.к. этот кривой (после отладки раскомментить строку!!!11111)
		}
		$counterGoodMediaLikes = 0; // сброс счетчика количества медиа, подходящих по фильтру лайков
		$counterGoodMediaComments = 0; // сброс счетчика количества медиа, подходящих по фильтру комментов
		
		
		// проверка на соответсвие фильтру по каментам к фото
		// $filterBioInclude = '@';
		
		// echo "<h3>DEBUG 351 counterGood++:" . $counterGood . "</h3>";
		$counterGood++;
		
		// Instagram Username
		// Instagram profile URL
		// # of posts
		// # of followers
		// Email(s) contained in bio
		// Full Bio
		// Instagram Username/profile where this user was found
		// First name
		// Last name
		
		$commenterImgSrc = $userInfo['profile_picture'];
		$commenterUsername = $userInfo['username'];
		$commenterBio = $userInfo['bio'];
		$commenterBio = str_replace("\r", " ", $commenterBio);
		$commenterBio = str_replace("\n", " ", $commenterBio);
		$commenterWebsite = $userInfo['website'];
		$commenterFullname = $userInfo['full_name'];
		$commenterMediaCounter = $userInfo['counts']['media'];
		$commenterFollowedBy = $userInfo['counts']['followed_by'];
		$commenterFollows = $userInfo['counts']['follows'];
		$commenterUserID = $userInfo['id'];
	
		// получим имя и фамилию
		$commenterUsernameArray = explode(" ", $commenterFullname);
		$commenterFirstName = $commenterUsernameArray[0];
		$commenterLastName = $commenterUsernameArray[1];
		
		// получим мыло
		preg_match_all("#[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+#i", $commenterBio, $email, PREG_PATTERN_ORDER);
		$email = implode(",", $email[0]);
		
		$output = 
		$commenterUsername . "\t" .
		"https://www.instagram.com/" . $commenterUsername . "/\t" .
		$commenterMediaCounter . "\t" .
		$commenterFollowedBy . "\t" .
		$email . "\t" .
		$commenterBio . "\t" .
		"https://www.instagram.com/" . $arrayMainUserID['data']['username'] . "/\t" .
		$commenterFirstName . "\t" .
		$commenterLastName . "\t" .
		$commenterFullname . "\t" .
		$arrayMainUserID['data']['username'] . "\t" .
		$mainUserId . "\t" .
		$commenterUserID;
		
		// запишем в выходной файл
		file_put_contents("resource/commenters/$mainUserId-user-info-filtered.txt", "\n" . $output, FILE_APPEND);		
		file_put_contents("resource/commenters/user-info-filtered-all.txt", "\n" . $output, FILE_APPEND);		
		}
	// запишем что прошли медиа главного пользователя
	file_put_contents("resource/commenters/$mainUserId-media-processed.txt","\n" . $mediaID, FILE_APPEND);
	
	// подождем немного
	rndSleep(($pauseMin*$kSleep),($pauseMax*$kSleep));
	// rndSleep(100,200);
}
	
unlink('lock.txt');
echo "<br /><br />Memory Usage: " . number_format(memory_get_usage(), 0, '.', ' ');
echo "<br />Memory Peak Usage: " . number_format(memory_get_peak_usage(), 0, '.', ' ');
$execTime = round((microtime(true) - $mtime) * 1, 4);
echo "<br />DEBUG INFO 435: " . file_put_contents("resource/log.txt","\n" . date("Ymd-His", time()) . "\t" . $execTime . "\t" . $mainUserId, FILE_APPEND);

echo '<hr /><br />Script finished ok, you may download files with right click and "save as..." option<br /><a href="resource/commenters/' . $mainUserId . '-user-info-filtered.txt">Filtered Users</a><br /><a href="resource/commenters/user-info-all.txt">All Users</a>';
echo '</pre>';	
?>