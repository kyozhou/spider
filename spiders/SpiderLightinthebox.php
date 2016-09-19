<?php

require_once (dirname(dirname(__FILE__)) . '/SpiderBase.php');

class SpiderLightinthebox extends SpiderBase {

	public function __construct($rootPath) {
		$site = "www.lightinthebox.com";
		$basePath = $rootPath . $site . '/';
		$baseURLs = array(
			'narrow-wedding_v2858t0-shoes_c3349' => 'http://www.lightinthebox.com/narrow/wedding_v2858t0/shoes_c3349/{number}.html',
			'wedding-shoes' => 'http://www.lightinthebox.com/c/wedding-shoes_2664/{number}.html',
			'wedding-headpieces' => 'http://www.lightinthebox.com/c/wedding-headpieces_2672/{number}.html',
			'wedding-jewelry-sets' => 'http://www.lightinthebox.com/c/wedding-jewelry-sets_2645/{number}.html',
			'wedding-petticoats' => 'http://www.lightinthebox.com/c/wedding-petticoats_1866/{number}.html',
			'wedding-garter' => 'http://www.lightinthebox.com/c/wedding-garter_2064/{number}.html',
			'favor-holders' => 'http://www.lightinthebox.com/c/favor-holders_4477/{number}.html',
			'practical-favors' => 'http://www.lightinthebox.com/c/practical-favors_4479/{number}.html',
			'bottle-favors' => 'http://www.lightinthebox.com/c/bottle-favors_4883/{number}.html',
			'handbags' => 'http://www.lightinthebox.com/c/handbags_1768/{number}.html',
			'wedding-invitations' => 'http://www.lightinthebox.com/c/wedding-invitations_4992/{number}.html',
			'dance-shoes_1278'=>'http://www.lightinthebox.com/c/dance-shoes_1278/{number}.html'
		);
		$this->baseURLsNeeded = array('dance-shoes_1278');
		$this->patterns = array(
			'link' => array(
				array(
					'pattern' => '/<dd\s+class="prodName">\s*<a.+?href="([^"]+)"/i',
				) //,array()...
			),
			'pageFeature' => array(
				array(
					'pattern' => '/<li\s+class="pageIndex">(.+?)<\/li>/i',
					'is_all' => false,
				)
			),
			'sourceId' => array(
				array(
					'pattern' => '/([0-9]+)\.html/i',
					'is_all' => false,
				)
			),
			'en_meta_title' => array(
				array(
					'pattern' => '/<h1>(.+?)<span/i',
					'is_all' => false,
				)
			),
			'en_meta_marketPrice' => array(
				array(
					'pattern' => '/id="delprice_[0-9]+"\s*>([^<]+)/i',
					'is_all' => false,
				)
			),
			'en_meta_price' => array(
				array(
					'pattern' => '/<strong[^>]+itemprop="price">\s*(.+?)<\/strong>/i',
					'is_all' => false,
				)
			),
			'en_meta_weight' => array(
				array(
					'pattern' => '/<strong\s+class="label">Net\s+Weight\:<\/strong>\s*([0-9\.,]+)\s*kg/i',
					'is_all' => false,
				)
			),
			'en_meta_images' => array(
				array(
					'pattern' => '/<img\s+src="(http:\/\/cloud[0-9]+.lbox.me\/images\/s\/[^"]+)"[^>]+/i',
				),
				array(
					'pattern' => '/<img\s+src="(http:\/\/cloud[0-9]+.lbox.me\/images\/64[^"]+)"[^>]+/i',
				)
			)
		);

		parent::__construct($basePath, $baseURLs, $site);
	}

}

?>