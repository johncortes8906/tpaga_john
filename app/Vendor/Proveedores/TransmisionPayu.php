<?php
Class TransmisionPayu {

	// Test
	private $url = '';
	private $url_consulta = '';
	private $api_login = '';
	private $api_key = '';
	//pasar esta variable a false una vez terminen las pruebas
	private $test = false;
	// private $account_id = '500546'; // Perú
	private $account_id = ''; // Colombia
	public $merchant_id = '';

	private $extras = null;
	public $client = null;
	public $respuesta = null;
	public $language = 'es';
	public $codigo_pais = '';

	public $headers = array('Content-Type: application/json', 'Accept: application/json');

	function __construct($config) {
		if(isset($config)) {
			$this->cambiarMerchant($config);
		} else {
		}
		
		try {
			$this->client = new Curl($this->url);
			return true;
		} catch(Exception $e) {
			return false;
		}
	}
	
/**
 * Cambia los datos del merchant
 *
 * @param mixed[] $config Estructura que mantiene los nuevos datos a cambiar. los valores requeridos son: 
 * mixed['merchant_id'], mixed['api_login'], mixed['account_id'] Y mixed['api_key']
 *
 * @return boolean
 */
	protected function cambiarMerchant($config) {
		$this->url = $config['url'];
		$this->url_consulta = $config['url_consulta'];
		$this->api_login = $config['api_login'];
		$this->api_key = $config['api_key'];
		$this->account_id = $config['account_id'];
		$this->merchant_id = $config['merchant_id'];
	}

	protected function enviar($accion, $datos) {
		$json_default = array(
			'language' => $this->language,
			'command' => $accion,
			'test' => $this->test,
			'merchant' => array(
				'apiLogin' => $this->api_login,
				'apiKey' => $this->api_key
			)
		);

		$datos = array_merge($json_default, $datos);
		$json = $this->construirJSON($datos);

		$this->client->setHeaders($this->headers);
		$this->client->setData($json);
		$this->client->exec('POST');
		$respuesta = $this->client->getResponse();

		return $this->normalizaRespuesta($respuesta);
	}
	
	public function setCodigoPais($codigo_pais) {
		$this->codigo_pais = $codigo_pais;
	}
	
	protected function cambiaUrlConsulta() {
		$this->client->seturl($this->url_consulta);
	}

	private function normalizaRespuesta($respuesta) {
		// Formato Respuesta
		// 	array(
		// 		'response' => 'true | false',
		// 		'msg' => 'id transaccion cuando es true | si es false va el código de error con texto descriptivo',
		// 		'data' => 'vacío por default | todos los campos que se agreguen con setExtrasRetornar'
		// 	)
		$formato_respuesta = array(
			'response' => false,
			'msg' => '',
			'data' => ''
		);

		if(is_string($respuesta)) {
			$respuesta = json_decode($respuesta, true);
		}

		if(isset($respuesta['code']) && $respuesta['code'] == 'SUCCESS') {
			$formato_respuesta['response'] = true;
			$formato_respuesta['msg'] = 1;
		} else {
			$formato_respuesta['msg'] = 'Hubo un error en la transacción.';
		}
		
		$info_extra = array();
		
		if(isset($this->extras)) {
			foreach($this->extras as $campo_extra) {
				if(isset($respuesta[ $campo_extra ]) && !empty($respuesta[ $campo_extra ])) {
					$info_extra[ $campo_extra ] = $respuesta[ $campo_extra ];
				} else if(!$formato_respuesta['response']) {
					$info_extra[ $campo_extra ] = array(
						'responseCode' => $respuesta['code']
					);
				} else if(isset($respuesta['result']) && isset($respuesta['result'][ $campo_extra])) { // Cuando es consulta devuelve otro formato
					$info_extra[ $campo_extra ] = $respuesta['result'][ $campo_extra];
				}
			}
			$formato_respuesta['data'] = $info_extra;
		}

		$this->respuesta = $formato_respuesta;
		return $this->respuesta;
	}

	protected function creaTransaccionId() {
		return date('YmdHis').rand(1111, 9999);
	}

	protected function setExtrasRetornar($extras) {
		$this->extras = $extras;
	}

	protected function getApiKey() {
		return $this->api_key;
	}

	protected function getMerchandId() {
		return $this->merchant_id;
	}

	protected function getAccountId() {
		return $this->account_id;
	}
	
	protected function marcarTest() {
		$this->test = true;
	}

	protected function getUsuarioId() {
		return $this->usuario_id;
	}

	private function construirJSON($datos) {
		return json_encode($datos);
	}
}


Class Curl {
	private $ch = null;
	private $response = '';
	private $method = 'GET';
	private $data = null;
	private $headers = null;
	private $cookie_file = null;
	private $usar_session = false;

	protected $useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:34.0) Gecko/20100101 Firefox/34.0';

	public function __construct($url = null) {
	  $this->ch = curl_init();

		if(isset($url)) {
			$this->setUrl($url);
		}

		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->useragent);
		// curl_setopt($this->ch, CURLOPT_HEADER, true); // set to 0 to eliminate header info from response
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); // Returns response data instead of TRUE(1)

	}

	public function openSession($url) {
		$this->usar_session = true;

		$tmpfname = dirname(__FILE__).'/cookie.txt';

		$this->cookie_file = $tmpfname;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfname);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfname);
		$page = curl_exec($ch);
		curl_close($ch);
	}

	public function setMethod($method = 'GET') {
		$this->method = strtoupper($method);
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function setData($data) {
		$this->data = $data;
	}

	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	public function exec($method = 'GET') {
		$this->setMethod($method);

		$data_string = $this->dataToString();
		curl_setopt($this->ch,CURLOPT_SSL_VERIFYPEER, false);

		if($this->usar_session) {
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookie_file);
		}

		if($this->method == 'POST') {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($this->ch, CURLOPT_URL, $this->url);
		} else {
			curl_setopt($this->ch, CURLOPT_URL, $this->url.'?'.$data_string);
		}

		$this->addHeaders();

		$this->response = curl_exec($this->ch); //execute post and get results
	}

	private function addHeaders() {
		if(isset($this->headers)) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
		}
	}

	public function getResponse() {
		return $this->response;
	}

	public function close() {
		curl_close($this->ch);
	}

	private function dataToString() {
		if(is_array($this->data)) {
			return http_build_query($this->data);
		}
		return $this->data;
	}
}
