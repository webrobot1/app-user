<?php
namespace Edisom\App\user\model;

class FrontendModel extends \Edisom\Core\Model 
{	

	// не будем объединять Добавление и редактирование пользователя	(как с контактами) - что бы нельзя было если нет доступа к добавлению из адресной строки подделать
	public function insert(string $login, string $password, $pr = array(), $halls = array()){
		if(!$login && !$password) return false;
		
		$time = time();
		$this->query('INSERT INTO users (login, password, created) VALUES ("'.strtolower($login).'", "'.md5(md5($password).$time).'", "'.date("Y-m-d H:i:s", $time).'")');
		$user_id = $this->last();
		
		if($pr)
			$this->save($user_id, $pr); // этот метод в backendModel тк только оттуда и толдько там можно давать првиелегии и в этом методе метод save не нужен
		
		//mail($login, "Регистрация", "Ваш логин: ".$login."\r\n Ваш пароль ".$password."\r\n ");
		
		return $user_id;
	}	
	
	public function users(int $id=null, array $callback = null, int $limit = 50):array
	{

		$where = array();
		if($id)
			$where[] = 'user_id = '.$id;
		else{		
			if(!empty($callback['phone'])){
				$where[] = 'phone LIKE "%'.$callback['phone'].'"';
			}			
			elseif(!empty($callback['token'])){
				$where[] = 'token = "'.$callback['token'].'"';
			}
			elseif(!empty($callback['login'])){
				$where[] = 'login LIKE "%'.$callback['login'].'%"';
			}	
			if(!empty($callback['reset'])){
				$where[] = 'reset = '.$callback['reset'];
			}			
			if(!empty($callback['values'])){
				$where[] = 'user_id IN (SELECT user_id FROM users__permission where value_id IN ('.implode(',', (array)$callback['values']).'))';
			}				
		}				
		
		if(
			($id!==null || !$limit || ($count = @$this->query('SELECT COUNT(*) as count FROM users '.($where?' WHERE '.implode(' AND ', $where):''))[0]['count']))
				&&
			($data = $this->query('SELECT * FROM users '.($where?' WHERE '.implode(' AND ', $where):'').(!empty($callback['sort'])?' ORDER BY '.$callback['sort'].' '.(!empty($callback['order'])?'ASC':'DESC'):'').($limit?' LIMIT '.(@(int)$callback['page']*$limit).', '.$limit:''), 'user_id'))
		){
			foreach($data as &$row)
			{	
				unset($row['password']); // не передаем от греха	
				$row['phone'] = ltrim($row['phone'], 0);										
			}
		}

		return array('data'=>$data, 'count'=>$count);
	}
	
	// не будем объединять Добавление и редактирование пользователя	(как с контактами) - что бы нельзя было если нет доступа к добавлению из адресной строки подделать
	public function change_password(string $token, int $user_id = null, string $password = null):array
	{
		if(!$token || (!$users = $this->users(null, array('token'=>$token))['data']) || !$users[$user_id]){
			\Edisom\Core\Frontend::_404('ссылка устарела или не действительна');
		}
		elseif($user_id && $password){
			$time = time();
			$this->query('UPDATE users set password = "'.md5(md5($password).$time).'", created = "'.date("Y-m-d H:i:s", $time).'" WHERE user_id='.$user_id);
			$this->query('UPDATE users set token = NULL WHERE token = '.$token);
		}
		return $users;
	}
}