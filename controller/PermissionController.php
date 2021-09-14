<?php
namespace Edisom\App\user\controller;

class PermissionController extends \Edisom\Core\Backend 
{			
	function index()
	{	
		$this->view->assign('permissions', $this->model->get_variants());		
		$this->view->assign('permissions_values', $this->model->premission_values());	
		$this->view->display('permission.html');	
	}
	
	function permission_variant(){	
		$this->view->assign('value', end($this->model->premission_values($this->value_id)));
		
		$this->view->assign('permissions_variants',  $this->model->pagesByApp());
		$this->view->display('permission_variants.html');
	}		
	
	function add()
	{	
		$this->model->add_permission($this->name);
		$this->redirect();	
	}		
	
	function add_page(){	
		if($this->groups && $this->variants){
			$this->model->add_permission_values($this->groups, $this->variants);
			$this->redirect();	
		}
		else{
			$this->model->add_permission_page($this->value, $this->permission);
			$this->redirect();
		}
	}
	
	function delete()
	{	
		$this->model->delete_permission($this->table, $this->id);
		$this->redirect();	
	}		
}