<?php
namespace Edisom\App\user\controller;

class BackendController extends \Edisom\Core\Backend {			
	
	function index(){
		$this->view->assign('users', $this->model->users(null, $this->callback));
		$this->view->assign('permission_values', $this->model->premission_values());	
		$this->view->display('main.html');	
	}		
		
	function save_permission(){	
		if($this->save){
			$this->model->save_permission($this->user_id, $this->pr);
			$this->redirect();
		}
		else{
			$this->view->assign('user', $this->model->users($this->user_id));	
			$this->view->assign('permission_values', $this->model->premission_values());	
			$this->view->display('permission_checkbox.html');	
		}
	}
	
	function profile(){
		$this->view->assign('user', $this->model->users(USER['user_id']));

		$this->view->display('profile.html');	
	}	
		
	function update_profile(){	
		$this->model->update(USER['user_id'], $this->callback);
		$this->redirect();
	}		
	
	function save_phone(){	
		$this->model->update($this->user_id, ['phone'=>$this->phone]);
		$this->redirect();
	}		
	
	function save_default_app(){	
		$this->model->update($this->user_id, ['default_app'=>$this->default_app]);
		$this->redirect();
	}	
	
	function insert(){	
		$this->model->insert($this->new_login, $this->new_password);
		$this->redirect();
	}
	
	function user_delete(){	
		$this->model->user_delete($this->id);
		$this->redirect();
	}
	
	function user_enter(){	
		$this->model->enter($this->id);
		$this->redirect('?app=backend');
	}
		
	function user_reset(){	
		$this->model->reset($this->id);
		$this->redirect(null, 'Теперь указанный пользователь может самостоятельно восстановить пароль на странице входа');
	}	
}