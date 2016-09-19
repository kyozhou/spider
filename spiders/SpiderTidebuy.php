<?php

require_once (dirname ( dirname ( __FILE__ ) ) . '/SpiderBase.php');
class SpiderTidebuy extends SpiderBase {
	//
	public function __construct($rootPath) {
		$site = "www.tidebuy.com";
		$basePath = $rootPath . $site . '/';
		$baseURLs = array (
				'mother' => 'http://mother.tidebuy.com/Mother-Of-The-Bride-Dresses-73/Discount/{number}/',
				'flower' => 'http://flower.tidebuy.com/Flower-Girl-Dresses-70/{number}/',
				'cocktail' => 'http://cocktail.tidebuy.com/Cocktail-Dresses-4352/{number}/' 
		);
		$this->patterns = array (
						'link' => array (
								array (
										'pattern' => '/<dt\s+class="\s*GBfade".+?<a\s+href="(.+?)"/i',
										'group' => 1
								) //,array()...
						),
						'pageFeature' => array (
								array (
										'pattern' => '/<dt\s+class="fl">(.+?)<\/dt>/i',
										'is_all' => false ,
								)
						),
						'en_meta_title' => array (
								array (
										'pattern' => "/<h1\\s+itemprop=\"name\">([^<]+)<\\/h1>/i",
										'is_all' => false ,
								)
						),
						'en_meta_marketPrice' => array (
								array (
										'pattern' => "/<span\\s+id=\"market_price\">US\\$\\s+([^<]+)<\\/span>/i",
										'is_all' => false ,
								)
						),
						'en_meta_price' => array (
								array (
										'pattern' => "/<span\\s+id='infoprice'[^>]*>([^<]+)<\\/span>/i",
										'is_all' => false ,
								)
						),
						'en_meta_weight' => array (
								array (
										'pattern' => "/Shipping\\s+Weight:\\s*<\\/span>\\s*<p>([^<]+)\\s*\\(\\s*kg\\s*\\)<\\/p>/i" ,
										'is_all' => false ,
								)
						),
						'en_meta_images' => array (
								array (
										'pattern' => "/<li\\s+class=\"xls_imgc?\">\\s*<img\\s+src=\"([^\"]+)\"/i" 
								)
						) 
				);
		$this->spiderConfigs = array (
				
				"list" => array (
						
						'link' => array (
								'pattern' => 'link',
								'formatter' => 'test' 
						),
						'pageFeature' => array (
								'pattern' => 'pageFeature' 
						) 
				),
				"detail" => array (
						'en' => array (
								'meta' => array (
										'title' => array (
												'formatter' => 'functionTest',
												'pattern' => 'en_meta_title' 
										),
										'marketPrice' => array (
												'pattern' => 'en_meta_marketPrice' 
										),
										'price' => array (
												'pattern' => 'en_meta_price' 
										),
										'weight' => array (
												'pattern' => 'en_meta_weight' 
										) 
								),
								'images' => array (
										'nameFormatString' => 'old.{number}.{exten}',
										'pattern' => 'en_meta_images' 
								)
						) 
				) 
		);
		
		parent::__construct ( $basePath, $baseURLs, $site );
	}
	
	public function functionTest(&$info ){
			
	}
}

?>