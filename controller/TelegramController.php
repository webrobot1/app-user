<?php
namespace Edisom\App\user\controller;

class TelegramController extends \Edisom\App\telegram\controller\ApiController
{	
	protected function parse(array $data){
		$this->model->run($data, \Edisom\App\telegram\model\ApiModel::getInstance());
	}	
}