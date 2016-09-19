<?php

require_once dirname(__FILE__) . '/SpiderFunctions.php';

/**
 * 专门负责抓取的基类，提供一个抓取的模板
 * 此类依赖 SpiderFunctions。php中的函数
 *
 * @author kyoz
 *  
 */
class SpiderBase {

	protected $basePath = '';
	protected $baseURLs = array(); // http://.../{number}.html
	protected $baseURLForList = ''; // 获取列表时，当前分类的baseURL
	protected $baseURLForDetail = ''; // 抓取信息时，当前的分类的baseURL
	protected $site = '';
	public $patterns = array(); //正则表达式列表
	public $spiderConfigs = array(); //配置匹配的正则和一些格式
	public $pagesFeature = array(); //页特征列表
	public $pagesHtml = array(); //每页的html
	public $baseURLsNeeded = array(); //需要抓取的url列表，如果是空，则默认全部抓取
	private $temp = array();

	/**
	 *
	 * @param string $basePath
	 *        	举例：d:/goods/www.tidebuy.com/
	 * @param array $baseURLs        	
	 * @param string $site        	
	 */
	protected function __construct($basePath, $baseURLs, $site) {
		$this->basePath = $basePath;
		$this->baseURLs = $baseURLs;
		$this->site = $site;
		if (!is_dir($this->basePath)) {
			mkdir($this->basePath, 0777);
		}
		foreach ($this->baseURLs as $name => $url) {
			if (!is_dir($this->basePath . "$name/")) {
				mkdir($this->basePath . "$name/", 0777);
			}
		}

		//在子类中的定义在此处会合并,优先是子类中的定义
		$spiderConfigsCustom = $this->spiderConfigs;
		$spiderConfigsDefault = array(
			"list" => array(
				'link' => array(
					'pattern' => 'link',
					'formatter' => 'test'
				),
				'pageFeature' => array(
					'pattern' => 'pageFeature'
				)
			),
			"detail" => array(
				'en' => array(
					'meta' => array(
						'title' => array(
							'formatter' => 'functionTest',
							'pattern' => 'en_meta_title'
						),
						'marketPrice' => array(
							'pattern' => 'en_meta_marketPrice'
						),
						'price' => array(
							'pattern' => 'en_meta_price'
						),
						'weight' => array(
							'pattern' => 'en_meta_weight'
						)
					),
					'images' => array(
						'nameFormatString' => 'old.{number}.{exten}',
						'pattern' => 'en_meta_images'
					)
				)
			)
		);
		$this->spiderConfigs = SpiderFunctions::array_merge_recursive_distinct($spiderConfigsDefault, $spiderConfigsCustom);
	}

	/**
	 * 抓取需要的外层url列表
	 * return bool( is success )
	 */
	public function catchURLs($startIndex = 0) {
		foreach ($this->baseURLs as $baseURLName => $baseURL) {
			if (count($this->baseURLsNeeded) != 0
					&& !in_array($baseURLName, $this->baseURLsNeeded)) {
				continue;
			} else {
				$i = $startIndex;
				$this->baseURLForList = $baseURL;
				while (true) {
					$i++;
					$urlPage = str_replace('{number}', $i, $baseURL);
					$URLsInPage = $this->getURLsInPage($urlPage);
					if ($URLsInPage === false) {
						echo "break on $i \n";
						break;
					} else {
						$URLsAllowed = $this->filterURLs($URLsInPage);
						$urlInsertedCount = $this->saveURLs($URLsAllowed);
						echo $urlPage . " , " . count($URLsInPage) . " found , " . count($URLsAllowed) . " allowed , $urlInsertedCount saved\n";
						sleep(mt_rand(3, 7));
					}
				}
			}
		}
	}

	/**
	 * return array
	 *
	 * @param bool $isUnupdated        	
	 */
	public function getURLsInDatabase($isUnupdated = false) {
		$sql = "SELECT url FROM urls WHERE site='{$this->site}' ";
		$timeNow = time();
		$sql .= $isUnupdated ? " AND update_time_end = 0 " : " ";
		$dataResult = SpiderFunctions::getDataFromSQL($sql);
		$urls = array();
		foreach ($dataResult as $row) {
			$urls [] = $row ['url'];
		}
		return $urls;
	}

	/**
	 * process all links of goods
	 *
	 * @param array $urls
	 */
	public function processURLs($urls) {
		$sql = "";

		foreach ($urls as $url) {
			$time_start = time();
			$goodsInfos ['en'] ['meta'] ['url'] = $url;
			$goodsInfos ['en'] ['meta'] ['md5'] = md5($url);
			$sql = "SELECT id,base_url FROM urls WHERE md5='{$goodsInfos['en'] ['meta']['md5']}'";
			$urlId = 0;
			$resultData = SpiderFunctions::getDataFromSQL($sql);
			if (!empty($resultData [0] ['id'])) {
				// update
				$urlId = $resultData [0] ['id'];
				$this->baseURLForDetail = $resultData [0] ['base_url'];
				$sql = "UPDATE urls SET update_time_begin=$time_start WHERE id=$urlId";
				SpiderFunctions::excuteSQL($sql);
				if ($urlId > 0) {
					$goodsInfos ['en'] ['html'] = str_replace("\n", "", SpiderFunctions::fileGet($goodsInfos ['en'] ['meta'] ['url']));
					$this->setGoodsInfo($goodsInfos);
					$this->saveImagesToDatabase($urlId, $goodsInfos ['en'] ['images']);
					if (!empty($goodsInfos ['en'] ['meta'] ['title']) && $this->storeGoodsInfoToDisc($goodsInfos, $urlId) === true) {
						$time_end = time();
						$sql = "UPDATE urls SET update_time_end=$time_end WHERE id=$urlId";
						SpiderFunctions::excuteSQL($sql);
						echo $url . " processed\n";
						sleep(mt_rand(3, 7));
					} else {
						continue;
					}
				} else {
					SpiderFunctions::setLogger("urlId error (= $urlId)");
					continue;
				}
			} else {
				SpiderFunctions::setLogger("url: \"$url\" is not exists in db");
				echo "url: \"$url\" is not exists in db";
				continue;
			}
		}

		echo "mession completed\n";
	}

	//
	public function getURLsInPage($urlPage) {

		$pageHtml = SpiderFunctions::fileGet($urlPage, strlen(end($this->pagesHtml)) * 0.2);
		$pageHtml = str_replace("\n", '', $pageHtml); // important!!!
		$this->pagesHtml[] = $pageHtml;
		$pageFeature = SpiderFunctions::regularOfReserve($pageHtml, $this->patterns [$this->spiderConfigs ['list'] ['pageFeature'] ['pattern']]);
		//echo "\n\n".$pageFeature."\n\n";
		if ($pageFeature != false && is_string($pageFeature) && !empty($pageFeature)) {
			if (in_array(md5($pageFeature), $this->pagesFeature)) {
				print_r($this->pagesFeature);
				return false;
			} else {
				
				$this->pagesFeature [] = md5($pageFeature);
				$links = SpiderFunctions::regularOfReserve($pageHtml, $this->patterns [$this->spiderConfigs ['list'] ['link'] ['pattern']]);
//print_r($links);die;
				if (count($links) > 0) {
					foreach ($links as $index => $url) { // 修复“/。。。。”的链接
						if (strpos($url, '/') == 0) {
							$links[$index] = "http://$this->site" . $url;
						}
					}
					return $links;
				} else {
					echo "Can't read the urls\n";
					return false;
				}
			}
		} else {
			echo "error to read page feature\n";
			return false;
		}
	}

	public function getURLsInText($textPath, $baseURLKey = 'others') {
		$urls = array();
		if (file_exists($textPath)) {
			$urls = file($textPath, FILE_SKIP_EMPTY_LINES);
			foreach ($urls as $index => $url) {
				$urls[$index] = trim($url);
			}
			$urls = $this->filterURLs($urls);
			$this->saveURLs($urls);
		}
		$this->baseURLForList = $baseURLKey;
//		print_r($urls);
//		die;
		return is_array($urls) ? $urls : array();
	}

	/**
	 * 
	 * @param array $goodsInfos
	 */
	public function setGoodsInfo(&$goodsInfos) {
		$url = $goodsInfos ['en'] ['meta'] ['url'];
		$html = $goodsInfos ['en'] ['html'];
		$html = str_replace("\n", '', $html); // important!!!
		if (count($this->spiderConfigs['detail']) > 1) {
			//不只有英语
			$this->multiLanguageHandle($goodsInfos);
		}

		foreach ($this->spiderConfigs['detail'] as $lan => $infos) {
			foreach ($infos['meta'] as $nameOfMeta => $configsOfMeta) {
				if (!empty($configsOfMeta['pattern'])) {
					$content = SpiderFunctions::regularOfReserve($html, $this->patterns[$configsOfMeta['pattern']]);
					$goodsInfos [$lan] ['meta'] [$nameOfMeta] = $content;
					if ($nameOfMeta == 'weight') {
						$content = trim($content);
						$goodsInfos [$lan] ['meta'] [$nameOfMeta] = is_numeric($content) && $content < 100 ? $content * 1000 : $content;
					}
				}
				if (!empty($configsOfMeta['formatter']) && function_exists($configsOfMeta['formatter'])) {
					$configsOfMeta['formatter']($goodsInfos [$lan] ['meta'] [$nameOfMeta]);
				}
			}
			$goodsImages = SpiderFunctions::regularOfReserve($html, $this->patterns[$infos['images']['pattern']]);
			$goodsImages = $goodsImages == false ? array() : array_unique($goodsImages);
			$goodsInfos[$lan]['images'] = array();
			foreach ($goodsImages as $imageUrl) { //修复相对链接
				if (strpos($imageUrl, '/') == 0) {
					$imageUrl = "http://$this->site" . $imageUrl;
				}
				$goodsInfos [$lan] ['images'][] = $imageUrl;
			}
		}
	}

	// 修复抓取失败的图片
	public function repairImagesOnDisc() {
		$sql = "SELECT i.id, i.url, i.url_id, u.base_url, i.disc_index FROM images AS i 
				LEFT JOIN urls AS u ON u.id=i.url_id
				WHERE i.success=0 AND u.site='{$this->site}'";
		$imagesData = SpiderFunctions::getDataFromSQL($sql);

		foreach ($imagesData as $imageRow) {
			$this->baseURLForDetail = $imageRow ['base_url'];
			$this->storeImagesToDisc(array(
				$imageRow ['disc_index'] => $imageRow ['url']
					), $imageRow ['url_id']);
		}
	}

	/**
	 *
	 * @param unknown_type $url        	
	 */
	public function testAURL($url) {
		
	}

	//
	protected function getbaseURLNameByURL($url) {
		$baseURLNames = array_flip($this->baseURLs);
		return empty($baseURLNames [$url]) ? 'others' : $baseURLNames [$url];
	}

	/**
	 * 多语言需要手动编写函数处理
	 * @param array $goodsInfos
	 */
	public function multiLanguageHandle(&$goodsInfos) {
		
	}

	/**
	 * storeGoodsInfoToDisc 方法执行完毕后的后续操作，一般用来重写
	 *
	 * @param unknown_type $goodsInfos        	
	 * @param unknown_type $urlId        	
	 * @return boolean
	 */
	public function storeGoodsInfoToDiscExtension($goodsInfos, $urlId) {
		return true;
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// private functions ////////////////////////////
	// //////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// //////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * 过滤掉重复的或者不被允许的url
	 * @param type $urls
	 * @return type array
	 */
	private function filterURLs($urls) {
		$URLsAllowed = array();
		foreach ($urls as $url) {
			if (SpiderFunctions::checkExistByURL($url) !== true) {
				$URLsAllowed [] = $url;
			} else {
				$md5 = md5($url);
				$time = time();
				$sql = "SELECT id FROM urls_reduplicated WHERE md5='$md5'";
				$resultData = SpiderFunctions::getDataFromSQL($sql);
				if (empty($resultData [0] ['id'])) {
					$sql = "INSERT INTO urls_reduplicated (url,md5,add_time_begin,site,base_url)" .
							" VALUES " .
							"('$url','$md5',$time,'" . $this->site . "','" . $this->baseURLForList . "')";
					SpiderFunctions::excuteSQL($sql);
				}
				//SpiderFunctions::setLogger("exists URL : $url", LOGGER_WARN);
			}
		}
		return $URLsAllowed;
	}

	/**
	 * void
	 *
	 * @param array $urls        	
	 */
	private function saveURLs($urls) {
		$urlInsertedCount = 0;
		foreach ($urls as $url) {
			$timeNow = time();
			$md5 = md5($url);
			$sourceId = !empty($this->patterns['sourceId']) ?
					SpiderFunctions::getURLSourceId($url, $this->patterns['sourceId']) : '';
			$sql = "INSERT INTO urls (url,md5,add_time_begin,site, base_url_name, base_url, source_id)" .
					" VALUES " .
					"('$url','$md5','$timeNow','" . $this->site . "', '" . $this->getbaseURLNameByURL($this->baseURLForList) . "','" .
					$this->baseURLForList . "', '{$sourceId}') " .
					" ON DUPLICATE KEY UPDATE update_time_begin='$timeNow', source_id='{$sourceId}' ";
			$insertedId = SpiderFunctions::excuteSQL($sql);
			$urlInsertedCount += $insertedId > 0 ? 1 : 0;
			//--
//			$sql = "select id from urls where site='www.lightinthebox.com' and 
//				base_url<>'http://www.lightinthebox.com/narrow/wedding_v2858t0/shoes_c3349/{number}.html' and
//				url='$url'";
//			$arr = SpiderFunctions::getDataFromSQL($sql);
//			if(!empty($arr[0]['id'])){
//				array_push($this->temp, $arr[0]['id']);
//			}
		}
		return $urlInsertedCount;
	}

	/**
	 * void
	 * save images to database
	 *
	 * @param int $urlId        	
	 * @param array $imagesURL        	
	 */
	private function saveImagesToDatabase($urlId, $imagesURL) {
		if (!empty($imagesURL)) {
			foreach ($imagesURL as $imageURL) {
				$md5ImageURL = md5($imageURL);
				$sql = "SELECT id FROM images WHERE md5 = '$md5ImageURL'";
				$resultData = SpiderFunctions::getDataFromSQL($sql);
				if (empty($resultData [0] ['id'])) {
					$sql = "INSERT INTO images (url, md5, url_id) VALUES ('$imageURL','$md5ImageURL',{$urlId} )";
					SpiderFunctions::excuteSQL($sql);
				}
			}
		}
	}

	/**
	 * store the meta.txt and images to disc
	 *
	 * @param array $goodsInfo        	
	 * @param int $urlId        	
	 * @return boolean
	 */
	private function storeGoodsInfoToDisc($goodsInfos, $urlId) {
		if ($urlId > 0) {
			$baseURLName = $this->getbaseURLNameByURL($this->baseURLForDetail);
			$pathNow = $this->basePath . "{$baseURLName}/$urlId/";
			if (!is_dir($pathNow)) {
				@mkdir($pathNow, 0777, true);
			}
			foreach ($goodsInfos as $language => $goodsInfo) {
				if (!empty($goodsInfo ['meta'])) {
					foreach ($goodsInfo ['meta'] as $key => $info) {
						if (empty($info)) {
							SpiderFunctions::setLogger("$urlId > $language > $key has not content", LOGGER_WARN);
						}
					}

					$metaFileName = $language == 'en' ? 'meta.txt' : "meta_$language.txt";
					$goodsInfo['meta']['url'] = trim($goodsInfo['meta']['url']);
					$metaString = "{$goodsInfo['meta']['url']}\r\n";
					$metaString .= "{$goodsInfo['meta']['md5']}\r\n";
					$metaString .= "{$goodsInfo['meta']['title']}\r\n";
					$metaString .= @"{$goodsInfo['meta']['marketPrice']}\r\n";
					$metaString .= "{$goodsInfo['meta']['price']}\r\n";
					$metaString .= @"{$goodsInfo['meta']['weight']}";
					SpiderFunctions::saveFile($pathNow, $metaFileName, $metaString);
				}
			}
			$shortcutGoods = "[InternetShortcut]
			URL={$goodsInfos['en']['meta']['url']}
					IDList=
					[{000214A0-0000-0000-C000-000000000046}]
					Prop3=19,2
					";
			SpiderFunctions::saveFile($pathNow, 'goto.url', $shortcutGoods);
			$this->storeGoodsInfoToDiscExtension($goodsInfos, $urlId);
			$this->storeImagesToDisc($goodsInfos ['en'] ['images'], $urlId);
			
			return true;
		} else {
			return false;
		}
	}

	/**
	 * store images to disc
	 */
	private function storeImagesToDisc($images, $urlId) {
		if (!empty($images)) {
			foreach ($images as $index => $imageUrl) {
				$imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
				$baseURLName = $this->getbaseURLNameByURL($this->baseURLForDetail);
				$pathNow = $this->basePath . "{$baseURLName}/$urlId/";
				$imageNameFormat = empty($this->spiderConfigs['detail']['en']['images']['nameFormatString']) ?
						'old.{number}.{exten}' : $this->spiderConfigs['detail']['en']['images']['nameFormatString'];
				$imageNameTemp = str_replace(array('{number}', '{exten}'), array($index + 1, $imageExtension), $imageNameFormat);
				$image = SpiderFunctions::fileGet($imageUrl);
				$saveImageSuccess = '0';
				$sqlExten = '';
				if ($image != false && SpiderFunctions::saveFile($pathNow, $imageNameTemp, $image)) {
					ImageCleaning::clean($pathNow . $imageNameTemp);
					$saveImageSuccess = '1';
					$imageHash = ImageHash::hashImageFile($pathNow . $imageNameTemp);
					$sqlExten = $imageHash === false ? '' : ", image_hash='$imageHash'"; // 保存图片hash
				}
				$md5ImageURL = md5($imageUrl);
				$sql = "UPDATE images SET success=$saveImageSuccess, disc_index=$index $sqlExten WHERE md5='$md5ImageURL'";
				SpiderFunctions::excuteSQL($sql);
			}
		}
	}

}

?>