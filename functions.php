<?php

// temporary no use
function getURL($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// curl_setopt ($ch, CURLOPT_PROXY, '192.168.1.50:9002');
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0');
	// curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

// file get contents and retry
function fileGet($URL) {
	$i = 0;

	while ($i < 3) {
		try {
			$httpOptions = array(
				'http' => array(
					'method' => "GET",
					'proxy' => 'tcp://192.168.1.50:9002',
					'header' => "User-Agent:Mozilla/5.0\r\n"
				)
			);
			$content = file_get_contents($URL, false, stream_context_create($httpOptions));
			// $content = file_get_contents ( $URL );
			return $content;
		} catch (Exception $e) {
			$i++;
			sleep(3);
		}
	}
	return false;
}

// save file to disc
function saveFile($path, $fileName, $stream) {
	try {
		if (!is_dir($path)) {
			mkdir($path, 0777);
		}
		$file = fopen($path . $fileName, "w");
		fwrite($file, $stream);
		fclose($file);
		return true;
	} catch (Exception $e) {
		return false;
	}
}

// for insert or update
function excuteSQL($sql) {
	require dirname(__FILE__) . '/config/config_db.php';
	mysql_connect($config_db['server'], $config_db['username'], $config_db['password']);
	mysql_select_db($config_db['database']);
	mysql_query('set names utf8');
	mysql_query($sql);
	$idInserted = 0;
	if (strpos($sql, 'insert') >= 0) {
		$idInserted = mysql_insert_id();
	}
	mysql_close();
	return $idInserted;
}

// for select sql
function getDataFromSQL($sql) {
	require dirname(__FILE__) . '/config/config_db.php';
	mysql_connect($config_db['server'], $config_db['username'], $config_db['password']);
	mysql_select_db($config_db['database']);

	mysql_query('set names utf8');
	$result = mysql_query($sql);
	$dataToReturn = array();
	if (!empty($result)) {
		while ($row = mysql_fetch_array($result)) {
			$dataToReturn [] = $row;
		}
	}

	mysql_close();
	return $dataToReturn;
}