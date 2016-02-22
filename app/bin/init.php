<?php

//===============================================
// PATHS
//===============================================

// first check where the folders are located ( based on the location of env.json)
define("SITE_ROOT", (file_exists("../env.json")) ? realpath("../") : realpath("./") );

// where the app is located
if(!defined("APP")) define('APP', SITE_ROOT.'/app/'); //with trailing slash pls

// the location where the SQLite databases will be saved
if(!defined("DATA")) define('DATA', SITE_ROOT.'/data/');

// the location of the website in relation with the domain root
// if not manually specified it is calculated based on the position of the index.php
if(!defined("WEB_FOLDER")) define('WEB_FOLDER', substr( $_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], "index.php") ) );
// alternatively use this if you do not have mod_rewrite enabled
//define('WEB_FOLDER','/index.php/');

// #70 : fallback path of where the templates reside (logic messy here...)
$templates = $_SERVER['DOCUMENT_ROOT'] . WEB_FOLDER . 'templates/';
// lookup APP folder first
if( !is_dir($templates) && defined("BASE") ){
	// then look up the default template location
	$templates = realpath(BASE . '../' ) . "/public/templates/";
	if( !is_dir($templates) ){
		// lastly lookup at the root
		$templates = realpath(BASE . '../' ) . "/templates/";
	}
}
if(!defined("TEMPLATES")) define('TEMPLATES', $templates );

//===============================================
// Dependencies
//===============================================
requireAll( "lib" );
// by default load the mvc.php first - which should only be one!
requireAll( "helpers", false, array("mvc.php", "section.php") );


//===============================================
// Static Route(s)
//===============================================
$url = parse_url( $_SERVER['REQUEST_URI'] );
// first check if this is a "static" asset
if ($output = isStatic($url['path']) ) {
	echo getFile( $output );
	exit;
}


//===============================================
// ENVIRONMENT VARIABLES
//===============================================

set_error_handler("custom_error");
set_exception_handler("uncaught_exception_handler");

if( defined("SHARED") ) putenv('TMPDIR=' . ini_get('upload_tmp_dir'));

if( !defined("CIPHER") ) define('CIPHER', NULL);

// set timezone (option?)
date_default_timezone_set('UTC');


//===============================================
// OTHER CONSTANTS
//===============================================

// find if this is running from localhost - $GLOBALS['SERVER_NAME'] is (originally) set in index.php
if( !array_key_exists("SERVER_NAME", $GLOBALS) ) $GLOBALS['SERVER_NAME'] = $_SERVER['SERVER_NAME'];
define("IS_LOCALHOST", (strpos($GLOBALS['SERVER_NAME'], "localhost") !== false) );
// set to true to enable debug mode (where supported)
if(!defined("DEBUG")) define('DEBUG', false);


//===============================================
// Includes
//===============================================
// follows this order:
//- models in the app folder
//- models in the base folder
//- files in this dir
//- plugins init.php in the app/base folder
//- plugins init.php in the plugins folder

lookUpDirs();

// load all the models (dependent on helpers)
requireAll( "models" );
// load all initializations
requireOnly( "bin", array("init.php") );


//===============================================
// Config
//===============================================

$config = new Config();
$GLOBALS['config'] = $config->getConfig();

// load config and other initiators (dependent on helpers)
requireAll( "bin", array("init.php") );


//===============================================
// Session
//===============================================
session_start();


//===============================================
// Start the controller
//===============================================s

$controller = findController($url['path']);
$output = new $controller( 'controllers/', WEB_FOLDER, DEFAULT_ROUTE, DEFAULT_ACTION);




//===============================================
// Helpers
//===============================================
// Lookup available dirs in our environment
function lookUpDirs(){

	if( defined("APP") ){
		// check if there is a directory in that location
		if( is_dir( APP ) ){
			// do nothing atm, this condition will evaluate just true in MOST cases
		} else {
			// create it if not
			mkdir(APP, 0775);
		}
	}

	// in the future create a global array of dirs here to replace the "if app" & "if base" conditions
}


//===============================================
// Including Files
//===============================================
function requireAll($folder='', $exclude=array(), $priority=array()){

	// find all the files in the APP, BASE and the folder
	$files = $app = $base = $plugins = $exception = $priorities = array();

	// all the files that have a full path
	$files = glob("$folder/*",GLOB_BRACE);
	if(!$files) $files = array();

	// all the files in the exception list
	if( is_array($exclude) ){
		foreach($exclude as $file){
			$exception = glob("$folder/$file",GLOB_BRACE);
			if(!$exception) $exception = array();
			if( defined("APP") ){
				$search = glob(APP."$folder/$file",GLOB_BRACE);
				if($search) $exception =  array_merge( $exception, (array)$search );
				// check the plugins subfolder
				$search = glob(APP."plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $exception =  array_merge( $exception, (array)$search );
			}
			if( defined("BASE") ){
				$search = glob(BASE."$folder/$file",GLOB_BRACE);
				if($search) $exception = array_merge( $exception, (array)$search );
				// check the plugins subfolder
				$search = glob(BASE."plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $exception =  array_merge( $exception, (array)$search );
			}
			// check in the plugins directory
			if( defined("PLUGINS")){
				$search = glob(PLUGINS."*/$folder/$file",GLOB_BRACE);
				if($search) $exception = array_merge( $exception, (array)$search );

			}
			# 110 looking into web root for plugins
			if( is_dir( SITE_ROOT . "/plugins" ) ){
				$search = glob(SITE_ROOT . "/plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $exception = array_merge( $exception, (array)$search );
			}
		}
	}
	// all the files in the priority list
	if( is_array($priority) ){
		foreach($priority as $file){
			if(!$priorities) $priorities = array();
			$priorities = array_merge( $priorities, (array)glob("$folder/$file",GLOB_BRACE) );
			if( defined("APP") ){
				$search = glob(APP."$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );
				// check the plugins subfolder
				$search = glob(APP."plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );
			}
			if( defined("BASE") ){
				$search = glob(BASE."$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );
				// check the plugins subfolder
				$search = glob(BASE."plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );
			}
			// check in the plugins directory
			if( defined("PLUGINS")){
				$search = glob(PLUGINS."*/$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );

			}
			# 110 looking into web root for plugins
			if( is_dir( SITE_ROOT . "/plugins" ) ){
				$search = glob(SITE_ROOT . "/plugins/*/$folder/$file",GLOB_BRACE);
				if($search) $priorities = array_merge( $priorities, (array)$search );
			}
		}
	}


	// look into the app folder
	if( defined("APP") ){
		$search = glob(APP."$folder/*",GLOB_BRACE);
		if($search) $app = array_merge( $app, (array)$search );
		// check the plugins subfolder
		$search = glob(APP."plugins/*/$folder/*",GLOB_BRACE);
		if($search) $app =  array_merge( $app, (array)$search );
	}

	// look into the base folder
	if( defined("BASE") ){
		$search = glob(BASE."$folder/*",GLOB_BRACE);
		if($search) $base =  array_merge( $base, (array)$search );
		// check the plugins subfolder
		$search = glob(BASE."plugins/*/$folder/*",GLOB_BRACE);
		if($search) $base =  array_merge( $base, (array)$search );

		// compare the files and exclude all the APP overrides
		foreach($base as $key=>$file){
			// remove the path
			$target = substr($file,strlen(BASE));
			// see if the target exists in the app folder
			if(file_exists(APP.$target)){
				// remove it from the array
				unset($base[$key]);
			}
		}
	}
	// look into the plugins folder
	$plugins = array();
	if( defined("PLUGINS") ){
		$search = glob(PLUGINS."*/$folder/*",GLOB_BRACE);
		if($search) $plugins = array_merge( $plugins, (array)$search );
	}
	# 110 looking into web root for plugins
	if( is_dir( SITE_ROOT . "/plugins" ) ){
		$search = glob(SITE_ROOT . "/plugins/*/$folder/*",GLOB_BRACE);
		if($search) $plugins = array_merge( $plugins, (array)$search );
	}

	// merge all the arrays together
	$files = array_merge( $files, $base, $app, $plugins );

	// remove all the files in the exclude list
	foreach($exception as $key=>$file){

		if(in_array($file, $files)){
			// remove it from the array
			unset($files[array_search($file, $files)]);
		}

	}

	// #118 - remove duplicate classes
	// this filter is intended for files in models/helpers/lib
	if( $folder != "bin" ){
		$names = array();
		foreach($files as $file){
			$name = basename($file);
			if( array_key_exists( $name, $names) ){
				// remove the duplicate
				// app comes first, then plugins, then base
				if( strpos( $file, APP ) === 0 ){
					// always delete the previous file
					//unset( array_search( $names[$name], $files ) );
				} elseif( strpos( $file, PLUGINS ) === 0 ){
					// delete the previous file only if its a base folder
					if( strpos( $names[$name], BASE ) === 0 ){
						$key = array_search( $names[$name], $files );
						unset( $files[$key] );
					} else {
						// delete the new discovery instead
						$key = array_search( $file, $files );
						unset( $files[$key] );
					}
				} else {
					// do nothing?
				}
			} else {
				$names[$name] = $file;
			}
		}
	}

	// require the $priority files first
	foreach($priorities as $key=>$file){
		if(in_array($file, $files)){
			// include it first
			if( is_file( $file )) require_once( $file );
		}
	}

	// require all the rest of the files
	foreach($files as $file){
		if( is_file( $file )) require_once( $file );
	}
}

function requireOnly($folder='', $only=array() ){

	// find all the files in the APP, BASE and the folder
	$files = $app = $base = $plugins = array();

	foreach($only as $file){

		// all the files that have a full path
		$files = glob("$folder/$file",GLOB_BRACE);
		if(!$files) $files = array();

		if( defined("APP") ){
			$search = glob(APP."$folder/$file",GLOB_BRACE);
			if($search) $app = array_merge( $app, (array)$search );
			// check the plugins subfolder
			$search = glob(APP."plugins/*/$folder/$file",GLOB_BRACE);
			if($search) $app = array_merge( $app, (array)$search );
		}
		if( defined("BASE") ){
			$search = glob(BASE."$folder/$file",GLOB_BRACE);
			if($search) $base = array_merge( $base, (array)$search );
			// check the plugins subfolder
			$search = glob(BASE."plugins/*/$folder/$file",GLOB_BRACE);
			if($search) $base = array_merge( $base, (array)$search );

			// compare the files and exclude all the APP overrides
			foreach($base as $key=>$file){
				// remove the path
				$target = substr($file,strlen(BASE));
				// see if the target exists in the app folder
				if(file_exists(APP.$target)){
					// remove it from the array
					unset($base[$key]);
				}
			}
		}
		if( defined("PLUGINS") ){
			$search = glob(PLUGINS."*/$folder/$file",GLOB_BRACE);
			if($search) $plugins = array_merge( $plugins, (array)$search );
		}
		if( is_dir( SITE_ROOT . "/plugins" ) ){
			$search = glob(SITE_ROOT . "/plugins/*/$folder/$file",GLOB_BRACE);
			if($search) $plugins = array_merge( $plugins, (array)$search );
		}
		// merge all the arrays together
		$files = array_merge( $files, $base, $app, $plugins );

	}

	// require all the files found
	foreach($files as $file){
		// finally exclude the file that is running
		if( is_file( $file ) && $file != __FILE__ ) require_once( $file );
	}

}

//===============================================
// Uncaught Exception Handling
//===============================================s

function uncaught_exception_handler($e) {
	if( ob_get_length() ) ob_end_clean(); //dump out remaining buffered text
	$vars['message']=$e;
	die(View::do_fetch( getPath('views/errors/500.php'),$vars));
}

function custom_error($errno, $message, $file, $line){
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting
		return;
	}

	switch ($errno) {
	case E_USER_ERROR:
		$type= "ERROR";
		break;
	case E_USER_WARNING:
		$type= "WARNING";
		break;
	case E_USER_NOTICE:
		$type= "NOTICE";
		break;
	default:
		$type= "Unknown error type";
		break;
	}

	$vars = array(
		'type' => $type,
		'message' => $message,
		'file' => $file,
		'line' => $line
	);
	die(View::do_fetch( getPath('views/errors/400.php'),$vars));

	/* Don't execute PHP internal error handler */
	return true;

}

?>
