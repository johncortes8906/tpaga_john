<?php

App::uses('Component', 'Controller');
App::uses('HttpSocket', 'Network/Http');

class ApiComponent Extends Component
{
	public function sendRequest($url, $params)
	{
		$http = new HttpSocket();
		return $http->post(
			$url,
			'',
			$params
		);	
	}
}