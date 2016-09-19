<?php
date_default_timezone_set('Asia/Shanghai');

ini_set('display_errors', 1);
error_reporting(E_ALL);

define ( 'SERVER_ADDRESS', 'localhost' );
// define('SERVER_ADDRESS', '192.168.1.50');
// define('SERVER_ADDRESS', '192.168.1.51');

define ( 'LOGGER_WARN', 'WARN' );
define ( 'LOGGER_INFO', 'INFO' );
define ( 'LOGGER_ERROR', 'ERROR');

include dirname ( __FILE__ ) . '/config/config_common.php';
include dirname ( __FILE__ ) . '/config/config_site_map.php';
include dirname ( __FILE__ ) . '/lib/ImageHash.php';
include dirname ( __FILE__ ) . '/lib/ImageCleaning.php';

set_error_handler ( 'error_handle' );
function error_handle($errno, $errstr, $errfile, $errline) {
	include_once (dirname ( __FILE__ ) . '/lib/log4php/Logger.php');
	Logger::configure ( dirname ( __FILE__ ) . '/config/config_log4php.php' );
	$log = Logger::getLogger ( '' );
	$outputString = "$errstr $errfile(line : $errline)";
	if (! (error_reporting () & $errno)) {
		return; // This error code is not included in error_reporting
	}
	switch ($errno) {
		case E_ERROR :
			$log->error ( $outputString );
			break;
		
		case E_WARNING :
			$log->warn ( $outputString );
			break;
		
		case E_NOTICE :
			$log->info ( ' PHP NOTICE ' . $outputString );
			break;
		
		default :
			$log->debug ( "UNKNOWN ERROR (errno:$errno) " . $outputString );
			break;
	}
}
