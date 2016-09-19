<?php

/**
 * 圖片相似度比較
 *
 * @version     $Id: ImageHash.php 4429 2012-04-17 13:20:31Z jax $
 * @author      jax.hu
 *
 * <code>
 *  //Sample_1
 *  $aHash = ImageHash::hashImageFile('wsz.11.jpg');
 *  $bHash = ImageHash::hashImageFile('wsz.12.jpg');
 *  var_dump(ImageHash::isHashSimilar($aHash, $bHash));
 *
 *  //Sample_2
 *  var_dump(ImageHash::isImageFileSimilar('wsz.11.jpg', 'wsz.12.jpg'));
 * </code>
 */
class ImageHash {
	/*	 * 取樣倍率 1~10
	 * @access public
	 * @staticvar int
	 * */

	public static $rate = 2;

	/*	 * 相似度允許值 0~100
	 * @access public
	 * @staticvar int
	 * */
	public static $similarity = 90;

	/*	 * 圖片類型對應的開啟函數
	 * @access private
	 * @staticvar string
	 * */
	private static $_createFunc = array(
		IMAGETYPE_GIF => 'imageCreateFromGIF',
		IMAGETYPE_JPEG => 'imageCreateFromJPEG',
		IMAGETYPE_PNG => 'imageCreateFromPNG',
		IMAGETYPE_BMP => 'imageCreateFromBMP',
		IMAGETYPE_WBMP => 'imageCreateFromWBMP',
		IMAGETYPE_XBM => 'imageCreateFromXBM',
	);
	private static $imageTypeMap = array(
		'gif' => IMAGETYPE_GIF,
		'jpg' => IMAGETYPE_JPEG,
		'png' => IMAGETYPE_PNG,
		'bmp' => IMAGETYPE_BMP,
		'wbmp' => IMAGETYPE_WBMP,
		'xbm' => IMAGETYPE_XBM
	);

	/*	 * 從檔案建立圖片
	 * @param string $filePath 檔案位址路徑
	 * @return resource 當成功開啟圖片則回傳圖片 resource ID，失敗則是 false
	 * */

	public static function createImage($filePath) {
		if (!file_exists($filePath)) {
			return false;
		}

		/* 判斷檔案類型是否可以開啟 */
		if (function_exists('exif_imagetype')) {
			$type = exif_imagetype($filePath);
		} else {
			$type = self::$imageTypeMap[strtolower(pathinfo($filePath, PATHINFO_EXTENSION))];
		}
		if (!array_key_exists($type, self::$_createFunc)) {
			return false;
		}

		$func = self::$_createFunc[$type];
		if (!function_exists($func)) {
			return false;
		}

		return $func($filePath);
	}

	/*	 * hash 圖片
	 * @param resource $src 圖片 resource ID
	 * @return string 圖片 hash 值，失敗則是 false
	 * */

	public static function hashImage($src) {
		if (!$src) {
			return false;
		}

		/* 缩小圖片尺寸 */
		$delta = 8 * self::$rate;
		$img = imageCreateTrueColor($delta, $delta);
		imageCopyResized($img, $src, 0, 0, 0, 0, $delta, $delta, imagesX($src), imagesY($src));

		/* 計算圖片灰階值 */
		$grayArray = array();
		for ($y = 0; $y < $delta; $y++) {
			for ($x = 0; $x < $delta; $x++) {
				$rgb = imagecolorat($img, $x, $y);
				$col = imagecolorsforindex($img, $rgb);
				$gray = intval(($col['red'] + $col['green'] + $col['blue']) / 3) & 0xFF;

				$grayArray[] = $gray;
			}
		}
		imagedestroy($img);

		/* 計算所有像素的灰階平均值 */
		$average = array_sum($grayArray) / count($grayArray);

		/* 計算 hash 值 */
		$hashStr = '';
		foreach ($grayArray as $gray) {
			$hashStr .= ($gray >= $average) ? '1' : '0';
		}

		return $hashStr;
	}

	/*	 * hash 圖片檔案
	 * @param string $filePath 檔案位址路徑
	 * @return string 圖片 hash 值，失敗則是 false
	 * */

	public static function hashImageFile($filePath) {
		$src = self::createImage($filePath);
		$hashStr = self::hashImage($src);
		imagedestroy($src);

		return $hashStr;
	}

	/*	 * 比較兩個 hash 值，是不是相似
	 * @param string $aHash A圖片的 hash 值
	 * @param string $bHash B圖片的 hash 值
	 * @return bool 當圖片相似則回傳 true，否則是 false
	 * */

	public static function isHashSimilar($aHash, $bHash) {
		$aL = strlen($aHash);
		$bL = strlen($bHash);
		if ($aL !== $bL) {
			return false;
		}

		/* 計算容許落差的數量 */
		$allowGap = $aL * (100 - self::$similarity) / 100;

		/* 計算兩個 hash 值的漢明距離 */
		$distance = 0;
		for ($i = 0; $i < $aL; $i++) {
			if ($aHash{$i} !== $bHash{$i}) {
				$distance++;
			}
		}

		return ($distance <= $allowGap) ? true : false;
	}

	public static function hashSimilarValue($aHash, $bHash) {
		$aL = strlen($aHash);
		$bL = strlen($bHash);
		if ($aL !== $bL) {
			return false;
		}

		/* 計算兩個 hash 值的漢明距離 */
		$distance = 0;
		for ($i = 0; $i < $aL; $i++) {
			if ($aHash{$i} !== $bHash{$i}) {
				$distance++;
			}
		}
		return ($aL - $distance) / $aL;
	}

	/*	 * 比較兩個圖片檔案，是不是相似
	 * @param string $aHash A圖片的路徑
	 * @param string $bHash B圖片的路徑
	 * @return bool 當圖片相似則回傳 true，否則是 false
	 * */

	public static function isImageFileSimilar($aPath, $bPath) {
		$aHash = ImageHash::hashImageFile($aPath);
		$bHash = ImageHash::hashImageFile($bPath);
		return ImageHash::isHashSimilar($aHash, $bHash);
	}

}