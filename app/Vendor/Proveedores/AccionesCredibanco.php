<?php
App::import('Vendor', 'phpQuery', array('file' => 'phpQuery/phpQuery.php'));
define('TODAY', date('m/d/Y'));
class AccionesCredibanco {
	private $Curl = null;
	private $login = null;
	private $password = null;
	private $respuesta = null;
	private $trayectos = array();
	private $url_base = '';
	private $host = '';

	function __construct($login = null, $password = null) {
		$this->url_base = Configure::read('Credibanco.admin.url');
		$this->extractHost();
		$this->Curl = new Curl();
		$this->login($login, $password);
	}

	private function login($login = null, $password = null) {
		$this->Curl->openSession($this->url_base.'/vpaymentweb/index.jsp');

		if(isset($login)) {
			$this->login = $login;
			$this->password = $password;
		}

		$this->Curl->setUrl($this->url_base.'/vpaymentweb/login.do');
		$headers = array(
			"Host" => $this->host,
			"User-Agent" => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:39.0) Gecko/20100101 Firefox/39.0",
			"Accept" => "text/xml, */*; q=0.01",
			"Accept-Language" => "en-us,en;q=0.5",
			"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
			"Connection" => "keep-alive",
			"Referer" => $this->url_base."/vpaymentweb/index.jsp"
		);

		$this->Curl->setHeaders($headers);

		$data_login = array(
			'counter' => '',
			'user' => $this->login,
			'passwordLogin' => $this->password
		);
		$this->Curl->setData($data_login);
		$this->Curl->exec('POST');

		return $this->Curl->getResponse();
	}

	public function obtenerPedido($numero_autorizacion) {
		$options = array(
			'numAutoriza' => $numero_autorizacion
		);

		return $this->_obtenerPedidos($options);
	}

	public function obtenerPedidos($options = null) {
		$options_default = array(
			'estado' => 3
		);

		if(isset($options)) {
			$options_default = array_replace($options_default, $options);
		}

		return $this->_obtenerPedidos($options_default);
	}

	private function _obtenerPedidos($options) {
		$this->Curl->setUrl($this->url_base.'/vpaymentweb/buscarConsultaPedidos.do');
		$headers = array(
			"Host" => $this->host,
			"User-Agent" => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:39.0) Gecko/20100101 Firefox/39.0",
			"Accept" => "text/html, */*; q=0.01",
			"Accept-Language" => "en-us,en;q=0.5",
			"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
			"Connection" => "keep-alive",
			"Referer" => $this->url_base."/vpaymentweb/consultaPedidos.jsp"
		);

		$data = array(
			'tipoConsulta' => 'Normal',
			'codPlan' => '',
			'rowsize' => '20',
			'listaPlan' => '0',
			'systemOper' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:39.0) Gecko/20100101 Firefox/39.0',
			'searchingBy' => '1',
			'searchingBy' => '1',
			'index_idACQ' => '',
			'value_idACQ' => '',
			'index_idMall' => '',
			'value_idMall' => '',
			'index_idTienda' => '',
			'value_idTienda' => '',
			'aliasPage' => '0',
			'aliasMoneda' => 'acquirer',
			'valorMoneda' => '',
			'numorden' => '',
			'pan' => '',
			'from' => TODAY,
			'to' => TODAY,
			'orderBy' => 'fecha',
			'orderAZ' => 'descendente',
			'monedas' => '170',
			'montoMenor' => '',
			'montoMayor' => '',
			'nameCH' => '',
			'listaPlanCuota' => '0',
			'marca' => 'todos',
			'bin' => '',
			'referencia' => '',
			'numAutoriza' => '',
			'codRpta' => '',
			'checkrep' => '1',
			'cboTypeArch' => 'texto',
			'correo' => ''
		);

		if(is_array($options) && count($options) > 0) {
			$data = array_replace($data, $options);
		}

		$string_data = http_build_query($data)/*.'&estado=3&estado=13'*/;
		$this->Curl->setData($string_data);
		$this->Curl->exec('POST');
		$this->respuesta = $this->Curl->getResponse();

		// $this->respuesta = file_get_contents('./index.html', FILE_USE_INCLUDE_PATH);

		return $this->formateaRespuesta($this->respuesta);
	}

	public function devolucion($registro_pago) {
		$options = array(
			'numAutoriza' => $registro_pago['PagosOnline']['cod_autorizacion'],
			'estado' => 3
		);

		$pedido = $this->_obtenerPedidos($options);
		if(count($pedido) == 0) {
			return false;
		}

		$id_order = key($pedido);
		$this->Curl->setUrl($this->url_base.'/vpaymentweb/direcciona.do');
		$headers = array(
			"Host" => $this->host,
			"User-Agent" => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:39.0) Gecko/20100101 Firefox/39.0",
			"Accept" => "text/html, */*; q=0.01",
			"Accept-Language" => "en-us,en;q=0.5",
			"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
			"Connection" => "keep-alive",
			"Referer" => $this->url_base."/vpaymentweb/direcciona.do"
		);

		$data = array(
			'valor' => 1072,
			'idOrder' => $id_order,
			'listOperacion' => $id_order,
			'Mid' => $id_order,
			'idLote' => 'null',
			'idPayment' => 'null',
			'querypage' => $pedido[$id_order]['querypage'],
			'anulacionFromTrxOnHold' => 'false',
			'trxDisponible' => 'false'
		);

		$this->Curl->setData($data);
		$this->Curl->exec('POST');
		$this->respuesta = $this->Curl->getResponse();

		// $this->respuesta = file_get_contents('./anular.html', FILE_USE_INCLUDE_PATH);

		$doc = phpQuery::newDocument($this->respuesta);

		phpQuery::selectDocument($doc);

		$trs = pq('form > table tbody')->find('tr');
		$cols = array();
		foreach($trs as $j => $tr) {
			$tagName = $tr->tagName;
			$tds = pq($tr)->find('td');
			foreach($tds as $i => $td) {
				$text = trim(strip_tags(pq($td)->html()));
				$cols[] = $text;
			}
		}
		$resultado = array_slice($cols, -4);
		if(count($resultado) != 4) {
			return false;
		}
		list($comercio, $transaccion_id, $autorizacion, $respuesta_transaccion) = $resultado;
		if($autorizacion != $numero_autorizacion || $respuesta_transaccion != 'Satisfactorio') {
			return false;
		}

		return true;
	}

	private function formateaRespuesta($respuesta) {
		$retornar = array();

		if(!isset($respuesta) || empty($respuesta)) {
			return $retornar;
		}

		$doc = phpQuery::newDocument($respuesta);

		phpQuery::selectDocument($doc);

		$inputs = pq('input[type=hidden]');
		$input_post = array();
		foreach($inputs as $j => $input) {
			$tagName = $input->tagName;
			$input_post[pq($input)->attr('name')] = pq($input)->attr('value');
		}

		$trs = pq('table#elemet tbody')->find('tr');
		foreach($trs as $j => $tr) {
			$tagName = $tr->tagName;
			$as = pq($tr)->find('a');
			foreach($as as $i => $a) {
				$regexp = "/javascript:enviar_listaConsultPedidos\([^0-9]*'([0-9]*)',([0-9]*),[^\)]*\)/mi";
				if(preg_match($regexp, pq($a)->attr('href'), $matches)) {
					$row = $input_post;
					$row['idOrder'] = $matches[1];
					$row['valor'] = $matches[2];
				}
			}
			$retornar[$matches[1]] = $row;
		}

		return $retornar;
	}

	private function extractHost() {
		$this->host = parse_url($this->url_base, PHP_URL_HOST);
		return $this->host;
	}

	public function prettify($datos) {
		printf("\033[1;35m%8s | %-16s | %-16s | %-16s | %-10s | %-10s | %-30s | %-10s | %-10s | %-10s | %-14s | %-50s\033[0m\n", 'valor', 'IDORDER', 'nombreCampo', 'destino', 'tipo', 'numMostrado', 'inicio', 'aliasPage', 'indexPage', 'indexSearch', 'idOrder', 'querypage');
		if(is_array($datos) && count($datos) > 0) {
			$datos = array_values($datos);
			foreach($datos as $i => $dato) {
				list($valor, $idorder, $nombre_campo, $destino, $tipo, $num_mostrado, $inicio, $alias_page, $index_page, $index_search, $id_order, $query_page) = array_values($dato);
				if($i%2) {
					printf("\033[0;1;37m%8s | %-16s | %-16s | %-16s | %-10s | %-10s | %-30s | %-10s | %-10s | %-10s | %-14s | %-50s\033[0m\n", $valor, $idorder, $nombre_campo, $destino, $tipo, $num_mostrado, $inicio, $alias_page, $index_page, $index_search, $id_order, $query_page);
				} else {
					printf("\033[47;1;30m%8s | %-16s | %-16s | %-16s | %-10s | %-10s | %-30s | %-10s | %-10s | %-10s | %-14s | %-50s\033[0m\n", $valor, $idorder, $nombre_campo, $destino, $tipo, $num_mostrado, $inicio, $alias_page, $index_page, $index_search, $id_order, $query_page);
				}
			}
		}
	}

	public function close() {
		$this->Curl->close();
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
		if(file_exists($tmpfname)) {
			return false;
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfname);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfname);
		$page = curl_exec($ch);
		curl_close($ch);
		return true;
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

		if($this->usar_session) {
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_ENCODING,  'gzip');

		if($this->method == 'POST') {
			curl_setopt($this->ch, CURLOPT_POST, true);
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
		} else if(is_string($this->data)) {
			return $this->data;
		}
		return '';
	}
}
