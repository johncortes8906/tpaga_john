<?php

App::uses('AppController', 'Controller');
App::uses('HttpSocket', 'Network/Http');
App::uses('ApiComponent', 'Controller/Component');

class SalesController extends AppController
{

	public $layout = 'ajax';
	public $Html = null;
	public $components = array('Api');
	public $token = null;
	public $helpers = array('Form', 'Html');

	public function beforeFilter()
	{
		$this->Html = new HttpSocket();
		$this->Order = ClassRegistry::init('Order');
		$this->OrderStatus = ClassRegistry::init('OrderStatus');
		$this->header = array('header' => array(
			'Authorization' => 'Basic ' . Configure::read('TPAGA_PARAMS.token'),
			'Cache-Control' => 'no-cache',
			'Content-Type' => 'application/json'
		));
		parent::beforeFilter();
	}

	public function index()
	{
		
	}

	public function new_sale()
	{
		if($this->request->is('post')) {
			$orderStatus = Configure::read('OrderStatuses.FAILED');
			$url = Configure::read('TPAGA_PARAMS.url') . DS . 'payment_requests/create';
			$order = $this->Api->addNewOrder($this->request->data);
			$response = $this->Api->sendRequest($url, $this->header, $order['Order']);
			$paymentUrl = '';
			if ($response->code == '201') {
				$body = json_decode($response['body'], true);
				$orderStatus = Configure::read('OrderStatuses.RECEIVED');
				$this->token = $body['token'];
				$paymentUrl = $body['tpaga_payment_url'];
				$tpagaStatusUrl = Configure::read('TPAGA_PARAMS.url') . DS . 'payment_requests' . DS . $body['token'] . DS . 'info';
				$tpagaStatus = $this->Api->getTpagaStatus($tpagaStatusUrl, $this->header);
			}
			$updatedOrder = $this->setOrder($order['Order']['id'], $orderStatus, $tpagaStatus);
			$this->set('order', $updatedOrder);
			$this->set('payment_url', $paymentUrl);
		}
	}


	public function setOrder($orderId, $orderStatus, $tpagaStatus = null)
	{
		$order = $this->Order->findById($orderId);
		$order['Order']['status_id'] = $orderStatus;
		$orderStatus = $this->OrderStatus->findById($orderStatus);
		$order['OrderStatus'] = $orderStatus['OrderStatus'];
		if (!empty($this->token)) {
			$order['Order']['payment_token'] = $this->token;
		}
		if (!empty($tpagaStatus)) {
			$bodyTpagaStatus = json_decode($tpagaStatus['body'], true);
			$order['Order']['transaction_status'] = $bodyTpagaStatus['status'];
		}
		return ($this->Order->save($order));
	}

	public function confirm_order($orderId)
	{
		$orderStatus = Configure::read('OrderStatuses.REJECTED');
		$order = $this->Order->findById($orderId);
		$url = Configure::read('TPAGA_PARAMS.url') . DS . 'payment_requests/confirm_delivery';
		$body = array('payment_request_token' => $order['Order']['payment_token']);
		$response = $this->Api->sendOrderConfirmation($url, $this->header, $body);
		if ($response->code == '200') {
			$orderStatus = Configure::read('OrderStatuses.ACCEPTED');
		}
		$updatedOrder = $this->setOrder($order['Order']['id'], $orderStatus);
		$this->set('order', $updatedOrder);
	}


	public function sales_list()
	{
		$userId = 1;
		$result = $this->Order->getOrdersByUserId($userId);
		$this->set('result', $result);
	}

	public function order_status()
	{

	}
}