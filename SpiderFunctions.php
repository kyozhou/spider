<?php

class SpiderFunctions {

	// file get contents and retry
	public static function fileGet($address, $minLen = 1) {
		if (empty($address)) {
			return false;
		} else {
			$i = 0;
			require dirname(__FILE__) . '/config/config_common.php';
			while ($i < 3) {
				try {
					$httpOptions = array(
						'http' => array(
							'method' => "GET",
							'proxy' => $config_proxy[SERVER_ADDRESS],
							'header' => "User-Agent:Mozilla/5.0\r\n"
						)
					);
					$address = trim($address);
					$content = file_get_contents($address, false, stream_context_create($httpOptions));
					if (strlen($content) < $minLen) {
						$i++;
						self::setLogger('Retry URL: ' . $address);
						sleep(3);
						continue;
					}
					// $content = file_get_contents ( $URL );
					return $content;
				} catch (Exception $e) {
					$i++;
					
					sleep(3);
				}
			}
			return false;
		}
	}

	// save file to disc
	public static function saveFile($path, $fileName, $stream) {
		try {
			if (!is_dir($path)) {
				@mkdir($path, 0777);
			}
			$file = @fopen($path . $fileName, "w");
			if ($file === FALSE) {
				return false;
			}
			if (@fwrite($file, $stream) === FALSE) {
				return false;
			}
			fclose($file);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * 检测商品是否已存在
	 *
	 * @param string $url        	
	 * @return boolean number
	 */
	public static function checkExistByURL($url) {
		$goods_url = 'http://www.baidu.com';
		$url = "http://cms.jjshouse.com/index.php?q=soapCMS/checkGoodsExists/url/" . md5($url);
		$username = 'jjshouse'; // secure server username to login
		$password = 'erpjjshouse'; // secure server password to login
		$context = stream_context_create(array(
			'http' => array(
				'header' => "Authorization: Basic " . base64_encode("$username:$password")
			)
				));

// 		$data = file_get_contents ( $url, true, $context );
		$urls = parse_url($url);
		$port = 80;
		$path = $urls['path'];
		if ($urls['query']) {
			$path .= '?' . $urls['query'];
		}
		$host = $urls['host'];
		if (isset($urls['port']) && empty($urls['port'])) {
			$port = 80;
		}

		$out = "GET $path HTTP/1.0\r\n";
		$out .= "Host: $host\r\n";
		$out .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Firefox/13.0.1\r\n";
		$out .= "Accept-Encoding: gzip, deflate\r\n";
		$out .= "Connection: Keep-live\r\n";
		$out .= "Authorization: Basic " . base64_encode("jjshouse:erpjjshouse") . "\r\n";
		$out .= "\r\n";

		if (!$conex = fsockopen($host, $port, $errno, $errstr, 10)) {
			return 0;
		}
		fwrite($conex, $out);
		$data = '';
		while (!feof($conex)) {
			$data = fgets($conex, 512);
		}
		fclose($conex);

		$data = json_decode($data, true);
		if (isset($data ['code'])) {
			if ($data ['code'] == 0) {
				// 不存在
				return false;
			} elseif ($data ['code'] == 1) {
				// 存在
				return true;
			} elseif ($data ['code'] == - 1) {
				// 参数错误
				return 0;
			} else {
				// 其他错误
				return 0;
			}
		} else {
			// 其他错误
			return 0;
		}
	}

	/**
	 * 利用给定正则表达式列表，迭代过滤出需要的字符串
	 * @param string $inputString
	 * @param array $patternList
	 * @return 成功返回group(Array)，失败返回false
	 */
	public static function regularOfFilter($inputString, $patternList) {
		for ($i = 0; $i < count($patternList); $i++) {
			$patternList [$i] ['is_all'] = isset($patternList [$i] ['is_all']) ? $patternList [$i] ['is_all'] : false;
			$patternList [$i] ['group'] = isset($patternList [$i] ['group']) ? $patternList [$i] ['group'] : 1;
			preg_match($patternList[$i], $inputString, $matches);
			if (empty($matches[$patternList[$i]['group']])) {
				return false;
			} else {
				//理论上只有最后一个正则才是 match_all
				if ($patternList [$i] ['is_all']) {
					preg_match_all($patternList [$i] ['pattern'], $inputString, $matches);
				} else {
					preg_match($patternList [$i] ['pattern'], $inputString, $matches);
				}
				$groupToReturn = $matches[$patternList [$i] ['group']];
			}
		}
		return $groupToReturn;
	}

	/**
	 * 一个正则不匹配，则用第二个正则
	 * @param string $inputString
	 * @param array $patternList = array(array(pattern=>'/.../i', group=>1, is_all=false),array(...))
	 * @return 成功返回group(array)，失败返回false
	 */
	public static function regularOfReserve($inputString, $patternList) {
		for ($i = 0; $i < count($patternList); $i++) {
			$patternList [$i] ['is_all'] = isset($patternList [$i] ['is_all']) ? $patternList [$i] ['is_all'] : true;
			$patternList [$i] ['group'] = isset($patternList [$i] ['group']) ? $patternList [$i] ['group'] : 1;
			if (empty($patternList [$i] ['group'])) {
				return false;
			} else {
				
				if ($patternList [$i] ['is_all']) {
					preg_match_all($patternList [$i] ['pattern'], $inputString, $matches);
				} else {
					preg_match($patternList [$i] ['pattern'], $inputString, $matches);
				}
				if (empty($matches [$patternList [$i] ['group']])) {
					continue;
				} else {
					return $matches [$patternList [$i] ['group']];
				}
			}
		}
		return false;
	}

	/**
	 * 获取一个url所附带的ID
	 * @param type $url
	 * @param type $pattern 
	 */
	public static function getURLSourceId($url, $pattern) {
		$match = self::regularOfReserve($url, $pattern);
		$sourceId = !empty($match) ? $match : false;
		return $sourceId;
	}

	//
	public static function array_merge_recursive_distinct(array &$array1, array &$array2) {
		$merged = $array1;

		foreach ($array2 as $key => &$value) {
			if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
				$merged [$key] = self::array_merge_recursive_distinct($merged [$key], $value);
			} else {
				$merged [$key] = $value;
			}
		}

		return $merged;
	}

	// for insert or update
	public static function excuteSQL($sql, $needInsertId = true) {
		require dirname(__FILE__) . '/config/config_common.php';
		mysql_connect($config_db [SERVER_ADDRESS] ['server'], $config_db [SERVER_ADDRESS] ['username'], $config_db [SERVER_ADDRESS] ['password']);
		mysql_select_db($config_db [SERVER_ADDRESS] ['database']);
		mysql_query('set names utf8');
		mysql_query($sql);
		$idInserted = 0;
		if ($needInsertId && strripos($sql, 'insert') == 0) {
			$idInserted = mysql_insert_id();
		}
		mysql_close();
		return $idInserted;
	}

	// for select sql
	public static function getDataFromSQL($sql) {
		require dirname(__FILE__) . '/config/config_common.php';
		mysql_connect($config_db [SERVER_ADDRESS] ['server'], $config_db [SERVER_ADDRESS] ['username'], $config_db [SERVER_ADDRESS] ['password']);
		mysql_select_db($config_db [SERVER_ADDRESS] ['database']);

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

	public static function setLogger($message, $type = LOGGER_INFO, $loggerName = 'SpiderForGoods') {
		$loggerName = empty($_SERVER['argv'][2]) ? $loggerName : $_SERVER['argv'][2];
		include_once (dirname(__FILE__) . '/lib/log4php/Logger.php');
		Logger::configure(dirname(__FILE__) . '/config/config_log4php.php');
		$log = Logger::getLogger($loggerName);
		switch ($type) {
			case LOGGER_ERROR:
				$log->error($message);
				break;
			case LOGGER_WARN:
				$log->warn($message);
				break;
			case LOGGER_INFO:
				$log->info($message);
				break;
			default:
				$log->error(' UNKNOWN ' . $message);
		}
	}

	public static function test() {
		
	}

}

?>