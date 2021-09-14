<?php
namespace Edisom\App\user\controller;

class FrontendController extends \Edisom\Core\Frontend 
{				
	function register(){
		$this->view->display('register.html');	
	}	
	
	function insert(){	
		//die('Регистрация временно закрыта');
		if($this->model->insert($this->new_login, $this->new_password))
			$this->redirect("?app=backend&page=backend&login=".$this->new_login.'&password='.$this->new_password, "Ваш логин: ".$this->new_login.', ваш пароль: '.$this->new_password);
	}	
	
	function forgot(){
		$this->view->assign('bot', \Edisom\App\telegram\model\ApiModel::getInstance());
		$this->view->display('forgot.html');	
	}
	
	function change_password(){
		$this->view->assign('users', $this->model->change_password($this->token, $this->user_id , $this->new_password));	
		if($this->user_id && $this->new_password){
			$this->redirect('?app=backend&page=backend', 'Пароль сменен на '.$this->new_password);
		}		
		$this->view->display('change_password.html');	
	}	
}