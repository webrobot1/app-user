<?php
namespace Edisom\App\user\model;

class PermissionModel extends BackendModel 
{				
	function add_permission($name){
		$this->query('INSERT INTO permission__variant (name) values ("'.trim($name).'")');
		return (int)$this->last();
	}	
	
	function add_permission_values($application, $variant){
		$this->query('INSERT INTO permission__value (application, variant_id) values ("'.$application.'", '.$variant.')');
		return (int)$this->last();
	}
	
	function add_permission_page(int $value_id, array $permission = null)
	{
		if(USER['login']=='admin' || (($value = $this->query('SELECT * FROM permission__value WHERE value_id = '.$value_id)[0]) && USER['permissions'][$value['application']])){
			if(
				USER['login']=='admin' 
					||
				(
					($applications = USER['permissions'][$value['application']]['applications']) 
						&& 
					array_walk($applications, function(&$item, $key){$item = '(page="'.$key.'" AND action IN ("'.implode('","', array_keys($item)).'"))';})
				)
			){
				static::transaction_start();	
					$this->query('DELETE FROM permission__page WHERE value_id='.$value_id.($applications?' AND ('.implode(' OR ', $applications).')':''));
					foreach($permission as $page=>$actions){
						foreach(array_keys($actions) as $action){
							if(USER['login']!='admin' && !USER['permissions'][$value['application']]['applications'][$page][$action])
								throw new \Exception('Нет доступа к "'.\Edisom\App\help\model\BackendModel::getInstance()->translate($value['application']).'" - "'.\Edisom\App\help\model\BackendModel::getInstance()->translate($page).'" - "'.\Edisom\App\help\model\BackendModel::getInstance()->translate($action).'" для установки на него доступа');
							
							$this->query('INSERT INTO permission__page (value_id, page, action) values ('.$value_id.', "'.$page.'", "'.$action.'")');
						}
					}
				static::transaction_stop();	
			}			
		}
		else
			throw new \Exception('нет доступа к приложению '.\Edisom\App\help\model\BackendModel::getInstance()->translate($value['application']).' для установки на него прав доступа');
	}
	
	function delete_permission($table, $id){
		$this->query('DELETE FROM permission__'.$table.' WHERE '.$table.'_id='.$id);
	}
		
	// вернет все actions and pages
	// todo reflection ??
	final public function pagesByApp():array{
		$return = array();
		foreach(static::applications() as $app=>$value){
			if(!$value['backend']) continue;
			
			$files = scandir(SITE_PATH.'/app/'.$app.'/controller');
			foreach($files as $file){
				if($file=='.' || $file=='..') continue;
				
				$content = file_get_contents(SITE_PATH.'/app/'.$app.'/controller/'.$file);	
				// не проверяем абстрактные классы и те что наследуюся напрямую от Controller (это frontend)  и Api (это следовательно апи)
				if(!preg_match('/extends[ ]+\\\Edisom\\\Core\\\Api/', $content) && !preg_match('/extends[ ]+ApiController/', $content) && !preg_match('/abstract[ ]+class/', $content) && !preg_match('/extends[ ]+\\\Edisom\\\Core\\\Frontend/', $content) && !preg_match('/extends[ ]+\\\Edisom\\\Core\\\Controller/', $content) && preg_match_all('/function[ ]+([^\(]+)\(/', $content, $methods)){
					array_map('trim', $methods[1]);
					$methods = array_flip($methods[1]);
					
					// удалим магические методы
					unset($methods['__construct']);
					unset($methods['__destruct']);
					unset($methods['__clone']);
					unset($methods['__isset']);
					unset($methods['__sleep']);
					unset($methods['__wakeup']);
					unset($methods[' __call']);
					unset($methods[' __get']);
					unset($methods[' __set']);
					unset($methods[' __unset']);
					unset($methods[' __serialize']);
					unset($methods[' __callStatic']);
					unset($methods[' __unserialize']);
					unset($methods[' __toString']);
					unset($methods[' __invoke']);
					unset($methods[' __set_state']);
					unset($methods[' __set_state']);
					unset($methods[' __debugInfo']);	
					
					// сортировка с учетом локали
					$methods = array_map(array(\Edisom\App\help\model\BackendModel::getInstance(), 'translate'), array_combine(array_keys($methods),array_keys($methods)));
					arsort($methods);
					$return[$app][strtolower(str_replace('Controller.php', '',  $file))] = $methods;	
				}
			}
		}
		return $return;		
	}
}