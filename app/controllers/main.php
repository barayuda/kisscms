<?php

class Main extends Controller {

	//This function maps the controller name and function name to the file location of the .php file to include
	function index( $params ) {
		
		
		// get the page details stored in the database
		$is_page = $this->getPage();
		$is_category = $this->getCategoryPages();
		// check if this is a category page
		if(!$is_page && !$is_category){
				$this->getNewPage();
		}
		
		// render the page
		$this->render();
	}

	function render() {
		// define a class accroding to the page type 
		
		//$this->data['style']= ;
			
		// display the page
		Template::output($this->data);
	}
	
	function getPage( ) {
		
		$data = array();
		// if there is no path, load the index
		if( empty( $this->data['path'] ) ){
			$page = new Page(1);
		} else { 
			$page = new Page();
			$page->get_page_from_path($this->data['path']);
		}

		// see if we have found a page
		if( $page->get('id') ){
			// store the information of the page
			$data['id'] = $this->data['id'] = $page->get('id');
			$data['title'] = stripslashes( $page->get('title') );
			$data['content'] = stripslashes( $page->get('content') );
			$data['tags'] = stripslashes( $page->get('tags') );
			$data['date'] = strtotime( stripslashes( $page->get('date') ) );
			
			$data['path']= $this->data['path'];
			$data['view'] = getPath('views/main/body.php');
			$this->data['body'][] = $data;
			$this->data['template'] = stripslashes( $page->get('template') );
			return true;
		} else {
			return false;			
		}

	}
	
	function getCategoryPages() {

		$page=new Page();
		$page->tablename = "pages";
		$pages = $page->retrieve_many("path like '". $this->data['path'] ."%'");
		
		if( count($pages) > 0 ){ 
			foreach( $pages as $data ){
				$data['view'] = getPath('views/main/category.php');
				$this->data['body'][] = $data;
			}
			return true;
		} else {
			return false;
		}
		
	}
	

	function getNewPage() {
		
		if( array_key_exists('admin', $_SESSION) && $_SESSION['admin'] ){ 
			// forward to create a new page
			$data['status']= $this->data['status']="new";
			$data['path']= $this->data['path'];
			$data['view']= getPath('views/admin/confirm_new.php');
			$this->data['body'][] = $data;
		} else { 
			// show 404 error if not loggedin
			$data['view']= getPath('views/main/404.php');
			$this->data['body'][] = $data;
		} 
		
	}
	

}


?>