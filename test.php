<?php 
set_time_limit ( 0 );
header ( "charset=utf-8" );
 //////////////
include dirname ( __FILE__ ) . '/app.php';
//include_once dirname ( __FILE__ ) . '/spiders/SpiderBase.php';
include_once 'SpiderFunctions.php';

// SpiderFunctions::setLogger ( 'test' );
//$url = 'http://www.lightinthebox.com/satin-stiletto-heel-sandals-honeymoon-wedding-shoes-with-rhinestone-more-colors_p327388.html';
//$html = SpiderFunctions::fileGet($url);
//$html = str_replace("\n", '', $html); // important!!!
//preg_match_all('/<img\s+src="(http:\/\/cloud[0-9]+.lbox.me\/images\/[sd]\/[^"]+)"[^>]+/i', $html, $matches);
//print_r($matches);
//echo strlen($html);
//$sql = "select * from urls WHERE base_url='http://www.lightinthebox.com/narrow/wedding_v2858t0/shoes_c3349/{number}.html'";
//$narrow = SpiderFunctions::getDataFromSQL($sql);
//$n = 0;
//echo SpiderFunctions::checkExistByURL($url);


// print_r($_SERVER);

// ///////////////////////////////////////////////
// $html = SpiderFunctions::fileGet($url);
// preg_match('/<h1>(.+?)<\/h1>/i', $html, $matches);

// print_r($matches);

// ////////////////////////////////////////

//$hash1 = '1100000000000111110000000000011111000000000001111100000000000111110000001000011111000001100001111100000110000111110000111000011111000011100001111100001110000111110000111100011111000101101001111100011101000111110001110000011111000000100001111100011111000111';
//$hash2 = '1000000000000011100000000000001110000011100000111000000110000011100000011000001110000001100000111000000111000011100000111100001110000000110000111000000011000011100000100100001110000000000000111000000000000011101111000000001111111110000111111111111101111111';
//
//
//$start = microtime(); 
//if(ImageHash::isHashSimilar($hash1, $hash2)){
//	echo 'similar';
//}else{
//	echo 'dif';
//}
//echo "\n";
//$end = microtime();
//echo $end-$start;
//echo "\n";
//$start = microtime(true);
//for($i=0; $i<100000; $i++){
//	ImageHash::hashSimilarValue($hash1, $hash2);
//}
//echo "\n";
//$end = microtime(TRUE);
//
//echo $end-$start;

//$hash1 = ImageHash::hashImageFile('d:/test/similar/1.jpg');
//$hash2 = ImageHash::hashImageFile('d:/test/similar/3.jpg');
//
//echo ImageHash::hashSimilarValue($hash1, $hash2);


/////////////////////////////////// text

//$urls = file('d:/urls.txt', FILE_SKIP_EMPTY_LINES);
//foreach ($urls as $key=>$url){
//	$urls[$key] = trim($url);
//}
//print_r($urls);

///////////////////////////////////////////
$images48RootPath = "d:/test/48";
$imagesRootPath = "d:/test/images_all";
for($i=1; $i<49; $i++){
//	$cmd = "E:/kyoz/cpp/SiftTest/Debug/SiftTest.exe";
//	$cmd .= " d:/test/48/$i.jpg d:/test/48/$i.sift";
//	echo shell_exec($cmd);
	$sql = "SELECT similarity_value, img_original FROM images_compare AS ic
				LEFT JOIN hunsha as hs ON hs.img_id=ic.image_id_compare
				WHERE ic.image_id=$i 
				ORDER BY similarity_value DESC
				LIMIT 0,5";
	$bestSimilarImages = SpiderFunctions::getDataFromSQL($sql);
	$image48PathNow = $images48RootPath."/$i";
	if(!is_dir($image48PathNow)){
		@mkdir($image48PathNow);
	}
	foreach($bestSimilarImages as $image){
		
		if(file_exists($imagesRootPath."/{$image['img_original']}")){
			copy($imagesRootPath."/{$image['img_original']}",
					$image48PathNow."/{$image['similarity_value']}.jpg"
					);
			echo $imagesRootPath."/{$image['img_original']}\n";
		}
	}
}















