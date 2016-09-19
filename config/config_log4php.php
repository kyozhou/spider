<?php
return array (
		'appenders' => array (
				'default' => array (
						'class' => 'LoggerAppenderDailyFile',
						'layout' => array (
								'class' => 'LoggerLayoutPattern',
								'params' => array (
										'conversionPattern' => '%d{Y-m-d H:i:s.u} %c %-5p %m%n'
								)
						),
						'params' => array (
								'datePattern' => 'Y-m-d',
                				'file' => 'logs/%s.log',
								'append' => true ,
						) 
				) 
		),
		'rootLogger' => array (
				'appenders' => array (
						'default' 
				) 
		) 
);