<?php

class SalesControllerTest extends ControllerTestCase
{

	public $fixtures = array(
		'app.order_status',
		'app.order',
		'app.user',
		'app.establishment'
	);
	
	public function setUp()
	{
		$this->Order = ClassRegistry::init('Order');
	}

	public function testVerifyNewSale()
	{
		$shoppingCart = '[{"name":"test 1","price":12000,"quantity":2},{"name":"test 2","price":11600,"quantity":1},{"name":"test 3","price":5800,"quantity":1}]';
		$data = array(
			'shopping_cart' => json_decode($shoppingCart, true),
			'user_id' => 1,
			'establishment_id' => 1
		);
		$this->testAction('sales/new_sale', 
			array('method' => 'POST', 'data' => $data
		));
		$order = $this->Order->find('first', array('order' => 'id DESC', 'recursive' => -1));
		$this->assertEquals($order['Order']['status_id'], Configure::read('OrderStatuses.RECEIVED'));
		$this->assertEquals($order['Order']['total'], 41400);
	}

	public function testConfirmOrder()
	{
		$orderId = 1;
		$order = $this->Order->findById($orderId);
		$this->testAction('sales/confirm_order' . DS . $orderId);
		$orderUpdated = $this->Order->findById($orderId);
		$orderStatus = Configure::read('OrderStatuses.REJECTED');
		$expectedStatus = array(
			'id' => $orderStatus,
			'status_name' => 'rechazado',
			'enabled' => 1
		);
		$this->assertEquals($orderUpdated['OrderStatus'], $expectedStatus);
		$this->assertEquals($orderUpdated['Order']['status_id'], $orderStatus);
	}

	public function testGetOrdersList()
	{
		$this->testAction('sales/sales_list');
		$expectedOrders = '{"result":[{"Order":{"order_date":"2019-03-21 11:35:02","shopping_cart":"[]","transaction_status":null},"User":{"user_name":"Lorem ipsum dolor sit amet"},"OrderStatus":{"status_name":"fallido"}}]}';
		$this->assertEquals($this->vars, json_decode($expectedOrders, true));	
	}
}