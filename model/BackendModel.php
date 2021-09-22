<?php
namespace Edisom\App\user\model;

class BackendModel extends FrontendModel 
{		
	private static $pages = array();
	private static $variants = array();
	private static $users = array();
		
	final function login(string $login, string $hash=null, string $password=null)
	{ 
		if(!$hash && !$password) return false;
		if($user = $this->query('SELECT * FROM users WHERE active=1 and login="'.strtolower($login).'" and password = '.($hash?'MD5(CONCAT("'.$hash.'", UNIX_TIMESTAMP(created)))':'"'.$password.'"'))[0]){
			unset($user['password']); // он нам не нужен ни при каких обстоятельствах более
		
			if($user['login']!='admin')		
				$user['permissions'] = $this->getPermission($user['user_id']);
			
			$_SESSION['login'] = $login;
			if($hash)
				$_SESSION['hash'] = $hash;	
			return $user;			
		}
	}
	
	public function users(int $id = null, array $callback = null, int $limit = 50):array{
		if($id && static::$users[$id])
			return static::$users[$id];
		
		if($data = parent::users($id, $callback, $limit))
		{
			foreach($data['data'] as &$user){
				$user['permissions'] = $this->getPermission($user['user_id']);	
				static::$users[$user['user_id']] = $user;	
			}
		}
		
		return ($id?static::$users[$id]:(!$limit?$data['data']:$data));
	}
	
	final public function check_admin(string $page=null, string $action=null, string $app)
	{
		if(!$action) $action='index';
		if(!$page) $page='backend';
		
		if(
			!defined('USER') 
				|| 
		   (
			USER['login']!='admin'
				&& 
				(
					empty(USER['permissions'])
						|| 
					(
						empty(USER['permissions'][$app]['applications'][$page])
							||
						empty(USER['permissions'][$app]['applications'][$page][$action]) 	
					)
				)
					&& 
				$app!='backend'
					&& 
				$action!='logout'
			)
		)
			return false;
		else
			return true;	
	}
	
	// получить роли пользователя  
	private function getPermission(int $user_id):array{
		$permissions = array();
		if($data = $this->query('SELECT * FROM users__permission where user_id='.$user_id, 'value_id')){
			$permissions = $this->premission_values(array_keys($data));
		}	
		return $permissions;
	}
	
	// одна роль может иметь разные доступы в разные страницы
	private function get_pages($id){
		if(!isset(static::$pages[$id]))
			static::$pages[$id] = $this->query('SELECT * FROM permission__page where value_id = '.$id);
		
		return static::$pages[$id];	
	}
		
	// сгрупированные по приложениям роли
	public function premission_values($id = null):array{
		$return = array();
		if($data = $this->query('SELECT * FROM permission__value'.($id?' WHERE value_id IN('.implode(',', (array)$id).')':''))){
			foreach($data as &$row){
				$row['name'] = $this->get_variants($row['variant_id'])['name'];
				$row['pages'] = $this->get_pages($row['value_id']); // используется этот ключ только для цикла ниже и подсчета общего количества записей в правах пользователя
				$row['applications'] = array();
				foreach($row['pages'] as $page){
					$row['applications'][$page['page']][$page['action']] = true;
				}
				if($id)
					$return[$row['application']] = $row;
				else
					$return[$row['application']][] = $row;
			}
		}

		return $return;
	}

	// просто спрачник названия ролей
	public function get_variants(int $id=null){
		if($id && isset(static::$variants[$id]))
			return static::$variants[$id];
		
		if($data = $this->query('SELECT * FROM permission__variant'.($id?' WHERE variant_id='.$id:''), 'variant_id'))
			foreach($data as $row)
				static::$variants[$row['variant_id']] = $row;
				
				
		return ($id?static::$variants[$id]:$data);
	}
		
	
	public function save_permission($id, array $pr = null)
	{
		$this->query('DELETE FROM users__permission where user_id='.$id);
		if($pr = array_filter($pr))
			$this->query('INSERT INTO users__permission (user_id, value_id) VALUES('.$id.', '.implode(') ,('.$id.',' , $pr).')');
	}	
	
	public function update($id, $callback){
		if($user = $this->users($id)){
			if($callback['password'])
				$callback['password'] = md5(md5($callback['password']).strtotime($user['created']));
			else
				unset($callback['password']);
			
			if($callback['login'])			
				unset($callback['login']);
			if($callback['user_id'])			
				unset($callback['user_id']);

			$this->query('UPDATE users SET '.static::explode($callback).' WHERE user_id='.$id);		
		}
	}
	
	public function enter(int $user_id){
		if($user = $this->query('SELECT login, password FROM users WHERE login!="admin" && user_id='.$user_id)[0]){
			unset($_SESSION['hash']);
			
			$_SESSION['login'] = $user['login'];
			$_SESSION['password'] = $user['password'];
		}	
	}	
	
	public function reset(int $user_id){
		$this->query('UPDATE users SET reset=1 WHERE user_id='.$user_id);	
	}
	
	public function user_delete($id){
		$this->query('DELETE FROM users WHERE user_id='.$id);
		$this->query('DELETE FROM users__permission WHERE user_id='.$id);
	}
}