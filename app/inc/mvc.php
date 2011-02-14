<?php
require('core.php');

//===============================================================
// Model/ORM
//===============================================================
class Model extends KISS_Model  {

  //Example of adding your own method to the core class
  function gethtmlsafe($key) {
    return htmlspecialchars($this->get($key));
  }

}

//===============================================================
// Controller
//===============================================================
class Controller extends KISS_Controller {


	//This function parses the HTTP request to set the controller name, function name and parameter parts.
	function parse_http_request() {
		// remove the first slash from the URI so the controller is always the first item in the array (later)
		$requri = substr($_SERVER['REQUEST_URI'], 1);
		if (strpos($requri,$this->web_folder)===0)
			$requri=substr($requri,strlen($this->web_folder));
		$request_uri_parts = $requri ? explode('/',$requri) : array();
		// remove the "index.php" the request;
		if( $request_uri_parts[0] == "index.php" ){ array_shift( $request_uri_parts ); }
		$this->request_uri_parts = $request_uri_parts;
		return $this;
	}

	//This function maps the controller name and function name to the file location of the .php file to include
	function route_request() {
		$controller = $this->default_controller;
		$function = $this->default_function;
		$params = array();

		$p = $this->request_uri_parts;
		if (isset($p[0]) && $p[0])
			$controller=$p[0];
		if (isset($p[1]) && $p[1])
			$function=$p[1];
		if (isset($p[2]))
			$params=array_slice($p,2);

		$controllerfile=$this->controller_path.$controller.'.php';
		if (!preg_match('#^[A-Za-z0-9_-]+$#',$controller) || !file_exists($controllerfile)){
			// revert to the main controller
			$params= $controller . "/" . $function;
			$controller = "page";
			$controllerfile=$this->controller_path.$controller.'.php';
		}

		if (!preg_match('#^[A-Za-z_][A-Za-z0-9_-]*$#',$function) || function_exists($function))
			$this->request_not_found();
		require($controllerfile);
		if (!function_exists($function))
			$this->request_not_found();

		call_user_func_array($function,$params);
		return $this;
	}



  //Example of overriding a core class method with your own
  function request_not_found() {
    die(View::do_fetch(VIEW_PATH.'errors/404.php'));
  }

}

//===============================================================
// View
//===============================================================
class View extends KISS_View {

  //Example of overriding a constructor/method, add some code then pass control back to parent
  function __construct($file='',$vars='') {
    $file = VIEW_PATH.$file;
    return parent::__construct($file,$vars);
  }

}

?>