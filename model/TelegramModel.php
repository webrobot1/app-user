<?php
namespace Edisom\App\user\model;

class TelegramModel extends FrontendModel 
{	
	public function run(array $data, \Edisom\App\telegram\model\ApiModel $api)
	{
		if($data['error_code'])
			\Edisom\Core\Api::_400($data['description']);		
		
		if($data['message']['text']=='/start')	{	
			$reply_markup = array('one_time_keyboard'=>false, 'keyboard' =>array(array(array('text'=>'Сбросить пароль', 'request_contact'=>true))));
			$message = array('chat_id'=>$data['message']['chat']['id'], 'text'=>'Для сброса пароля нажмите на кнопку "Сбросить пароль" (пароль должен быть сброшен)', 'reply_markup'=>json_encode($reply_markup));		
			$api->api('sendMessage', $message);
		}
		
		if((!$chat = $api->get($data['message']['chat']['id'])) && ($chat['phone'] = ltrim($data['message']['contact']['phone_number'], '+7'))){
			$chat['name'] = (string)$api;
			$chat['chat_id'] = $data['message']['chat']['id'];
			$api->insert($chat);
		}
		
		if($chat){
			if(!$users = $this->users(null, array('phone'=>ltrim($chat['phone'],0), 'reset'=>1))['data']){
				$message = array('chat_id'=>$chat['chat_id'], 'text'=>'Не найден пользователей Edis с указанным телефоном и сброшенным паролем');
				$api->api('sendMessage', $message);			
			}
			else{
				$token = \Edisom\Core\Model::guid();
				$this->query('UPDATE users set token="'.$token.'" WHERE user_id IN ('.implode(',', array_keys($users)).')');
					
				$reply_markup = array('inline_keyboard'=>array(array(array('text'=>'Ввести новый пароль...', 'url'=>SITE_URL.Template::getInstance()->set_query('?app=user&page=frontend&action=change_password&token='.$token)))));
				$message = array('chat_id'=>$chat['chat_id'], 'text'=>'Для ввода нового пароля нажмите на ссылку ниже. Если появиться сообщение о том что соединение не защищено нажмите на ссылку вида "Все равно перейти" (в зависимости от браузера ссылка может быть в разделе "Подробнее" и тп)', 'reply_markup'=>json_encode($reply_markup));	
				$api->api('sendMessage', $message);
			}			
		}

		static::log(print_r($data, true));		
	}	
}