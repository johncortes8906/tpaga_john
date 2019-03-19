<?php

class Request {
	public $http = null;
	public $error = null;
	private $url = null;
	private $data = null;
	private $method = null;
	private $curlopt_useragent='Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3B48b Safari/419.3';
	private $curlopt_returntransfer=true;
	private $curlopt_followlocation=false;
	private $curlopt_timeout=120;

	public function __construct() {
		
		$this->setHttp(new CurlRequest());
		$this->http->setOption(CURLOPT_USERAGENT, $this->curlopt_useragent );
		$this->http->setOption(CURLOPT_RETURNTRANSFER, $this->curlopt_returntransfer);
		$this->http->setOption(CURLOPT_FOLLOWLOCATION, $this->curlopt_followlocation);
		$this->http->setOption(CURLOPT_TIMEOUT, $this->curlopt_timeout);
		$this->http->setOption(CURLOPT_SSL_VERIFYHOST, 2);
		$this->http->setOption(CURLOPT_SSL_VERIFYPEER, true);
		
		$this->setMethod();

	}
	
	public function setHttp($request) {
		$this->http = $request;
	}

	public function setUrl($url) {
		$this->url = $url;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function setMethod($method = 'GET') {
		$this->method = strtoupper($method);
	}
	
	public function send($data, $url, $method, $headers=null) {
				
		$this->setData($data);
		$this->setMethod($method);
		$this->setUrl($url);
		
		$this->http->setOption(CURLOPT_URL, $this->url);

		if(strtoupper($this->method) == 'POST') {
			
			$this->http->setOption(CURLOPT_POST, TRUE);			
			$this->http->setOption(CURLOPT_POSTFIELDS, $this->data);

		} 
		if($headers!=null and is_array($headers)){
				$this->http->setOption(CURLOPT_HTTPHEADER, $headers);
						
		}

		return $this->makeRequest();
	}	

	public function makeRequest() {
		$request = new stdClass();

		$request->result=$this->http->execute();
		
		$request->http_code = $this->http->getInfo(CURLINFO_HTTP_CODE);
		$request->content_type = $this->http->getInfo(CURLINFO_CONTENT_TYPE);

		if(strtolower($request->content_type)=="application/json; charset=utf-8")
			$request->result=json_decode($request->result);

		if(strtolower($request->http_code)==0)
			$request->error=$this->http->getError();

		return $request;
	}	
}


interface HttpRequest {
	public function setOption($name, $value);
	public function execute();
	public function getInfo($name);
	public function close();
}

class CurlRequest implements HttpRequest {
	private $handle = null;

	public function __construct($url = '') {
		$this->handle = curl_init($url);
	}

	public function setOption($name, $value) {
		curl_setopt($this->handle, $name, $value);
	}

	public function execute() {
		return curl_exec($this->handle);
	}

	public function getInfo($name) {
		return curl_getinfo($this->handle, $name);
	}
	
	public function getError() {
		return curl_error($this->handle);
	}
	
	public function close() {
		curl_close($this->handle);
	}
}
