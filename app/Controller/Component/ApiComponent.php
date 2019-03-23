 <?php

App::uses('Component', 'Controller');
App::uses('HttpSocket', 'Network/Http');

class ApiComponent Extends Component
{

	public function sendRequest($url, $params, $order)
	{
		$this->OrderStatus = ClassRegistry::init('OrderStatus');
		$http = new HttpSocket();
		$body = $this->getBody($order);
		return $http->post(
			$url,
			json_encode($body),
			$params
		);	
	}

	public function getBody($order)
	{
		$token = md5(date('Y-m-d\TH:i:s.u\Z') . '-' . $order['id']);
		$description = 'Compra realizada a sucursal de ID: ' . $order['establishment_id'];
		$url = Configure::read('DOMAIN_URL') . DS . 'ventas/estado_de_mi_compra' . DS . $order['id'];
		return array(
			'cost' => $order['total'],
			'purchase_details_url' => $url,
			'idempotency_token' => $token,
			'order_id' => $order['id'],
			'terminal_id' => $order['establishment_id'],
			'purchase_description' => $description,
			'purchase_items' => json_decode($order['shopping_cart']),
			'user_ip_address' => '127.0.0.1',
			'expires_at' => date('Y-m-d\TH:i:s.u\Z', strtotime("+2 hours"))
		);
	}

	public function addNewOrder($request)
	{
		$this->Order = ClassRegistry::init('Order');
		$total = $this->getTotal($request['shopping_cart']);
		$status = Configure::read('OrderStatuses.CREATED');
		$params = array('Order' => array(
			'order_date' => date('Y-m-d H:i:s'),
			'establishment_id' => $request['establishment_id'],
			'total' => $total,
			'shopping_cart' => json_encode($request['shopping_cart']),
			'user_id' => $request['user_id'],
			'status_id' => $status
		));
		return ($this->Order->save($params));
	}

	public function getTotal($orderDetail)
	{
		$total = 0.00;
		foreach ($orderDetail as $item) {
			$total += (number_format($item['price'], 2, '.', '') * intval($item['quantity']));
		}
		return $total;
	}

	public function sendOrderConfirmation($url, $headers, $body = '')
	{
		$http = new HttpSocket();
		$jsonBody = json_encode($body);
		return $http->post($url, $jsonBody, $headers);
	}

	public function getTpagaStatus($url, $headers, $body = '')
	{
		$http = new HttpSocket();
		$body = json_encode($body);
		return $http->get($url, $body, $headers);
	}

}