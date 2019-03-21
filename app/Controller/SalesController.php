<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('ApiComponent', 'Controller/Component');

class SalesController extends AppController
{

	public $layout = 'ajax';
	public $Html = null;
	public $components = array('Api');

	public function beforeFilter()
	{
		$this->Html = new HttpSocket();
		$this->Order = ClassRegistry::init('Order');
		parent::beforeFilter();
	}

	public function index()
	{
		
	}

	public function new_sale()
	{
		$tpagaParams = Configure::read('TPAGA_PARAMS');
		$url = $tpagaParams['url'] . DS . 'payment_requests/create';
		$header = array(
			'Authentication' => $tpagaParams['token'],
			'Cache-Control' => 'no-cache',
			'Content-Type' => 'application/json'
		);
		$response = $this->Api->sendRequest($url, $header);
		var_dump($response);
	}


	public function sale_status()
	{
		$this->autoRender = false;
	}


	public function sales_list()
	{
		$userId = 1;
		$orders = $this->Order->getOrdersByUserId($userId);
		$this->set('orders', $orders);
	}
}