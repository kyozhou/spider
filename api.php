<?php

/**
 * 命令行调用方式：
 * api.php -l|-d [fullname of website]
 */
include dirname(__FILE__) . '/app.php';

if (!empty($argv [1]) && $argv [1] == '-h') {
	die(getHelpText());
} elseif (empty($argv [2]) || empty($config_site_map [$argv [2]])) {
	die("\ncall api error\n" . getHelpText());
} else {
	$ROOT_PATH = $config_root_path[SERVER_ADDRESS];
	$className = $config_site_map [$argv [2]];
	if (file_exists(dirname(__FILE__) . '/spiders/' . $className . '.php')) {
		include_once dirname(__FILE__) . '/spiders/' . $className . '.php';
		$spider = new $className($ROOT_PATH);
		switch ($argv [1]) {

			case '-l' : // 获取抓取列表
				$startIndex = 0;
				if (!empty($argv [3]) && is_int($argv[3]) && $argv[3] > 0) {
					$startIndex = $argv[3];
				}
				$spider->catchURLs($startIndex);
				break;

			case '-d' : // 抓取详细页
				$urls = $spider->getURLsInDatabase(true);
				$spider->processURLs($urls);
				break;

			case '-a' : //自动执行 -l -d
				$spider->catchURLs();
				$urls = $spider->getURLsInDatabase(true);
				$spider->processURLs($urls);
				break;

			case '-ru' : // 重新抓取meta或者url有误的URLs

				$urls = $spider->getURLsInDatabase(true);
				echo count($urls)." unprocessed\n";
				$spider->processURLs($urls);
				break;

			case '-ri' : // 重新抓取有误的图片
				$spider->repairImagesOnDisc();
				break;

			case '-dt' : // 从text中读取url后抓取，且需要text路径参数
				if (empty($argv[3])) {
					die("need argv 3(text path)\n" . getHelpText());
				} else {
					$urls = $spider->getURLsInText($argv[3]);
					$spider->processURLs($urls);
				}
				break;

			default :
				die("\ncommand is not exists\n" . getHelpText());
		}
	} else {
		die("the class '$className' is not exists");
	}
}

function getHelpText() {
	include dirname(__FILE__) . '/config/config_site_map.php';

	$stringToReturn = "\napi.php [-l|-d|-a|-ru|-ri|-h] [the body name of website address] [start_index]\n" .
			"-l : get urls of goods\n" .
			"-d : get goods infomation and store\n" .
			"-a : excuting -l and -d automatically\n" .
			"-ri : to patch the uncompleted images\n" .
			"-ru : to patch the uncompleted URLs\n" .
			"-h : help .\n" .
			"path now : " . dirname(__FILE__);
	$stringToReturn .= "\navailable sitename:\n";
	foreach ($config_site_map as $key => $value) {
		$stringToReturn .= "$key\n";
	}
	return $stringToReturn;
}