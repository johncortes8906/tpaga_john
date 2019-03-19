<?php
App::import('Vendor', 'TransmisionPayu', array('file' => 'Proveedores/TransmisionPayu.php'));
class TokenizerPayu extends TransmisionPayu {
	private $pais = null;
	private $usuario_id = null;
	private $test = false;

	function __construct($config) {
		parent::__construct($config);
		$this->setCodigoPais();
	}

	public function agregarTarjeta($Tarjeta) {
		$datos = array(
			'payerId' => $Tarjeta->documento,
			'number' => $Tarjeta->numero,
			'paymentMethod' => $Tarjeta->marca,
			'expirationDate' => sprintf('%s/%s', $Tarjeta->anio_expira, $Tarjeta->mes_expira),
			'identificationNumber' => $Tarjeta->documento,
			'name' => $Tarjeta->nombre
		);

		$extras = array(
			'creditCardToken'
		);
		$this->setExtrasRetornar($extras);

		$respuesta = $this->enviar('CREATE_TOKEN', array('creditCardToken' => $datos));
		if($respuesta['response']) {
			$data = array(
				'token' => $respuesta['data']['creditCardToken']['creditCardTokenId'],
				'nombre' => $respuesta['data']['creditCardToken']['name'],
				'usuario_id' => $this->usuario_id,
				'documento' => $respuesta['data']['creditCardToken']['identificationNumber'],
				'marca' => $respuesta['data']['creditCardToken']['paymentMethod'],
				'alias' => substr($respuesta['data']['creditCardToken']['maskedNumber'], -4),
				'bin' => substr($respuesta['data']['creditCardToken']['maskedNumber'], 0, 6)
			);
			$respuesta['data'] = $data;
		}

		return $respuesta;
	}

	public function eliminarTarjeta($documento, $token, $celular) {
		$datos = array(
			'payerId' => $documento,
			'creditCardTokenId' => $token
		);

		$extras = array(
			'creditCardToken'
		);
		$this->setExtrasRetornar($extras);

		$respuesta = $this->enviar('REMOVE_TOKEN', array('removeCreditCardToken' => $datos));

		if($respuesta['response']) {
			$data = array(
				'token' => $respuesta['data']['creditCardToken']['creditCardTokenId'],
				'nombre' => $respuesta['data']['creditCardToken']['name'],
				'usuario_id' => $this->usuario_id,
				'documento' => $respuesta['data']['creditCardToken']['identificationNumber'],
				'marca' => $respuesta['data']['creditCardToken']['paymentMethod'],
				'alias' => substr($respuesta['data']['creditCardToken']['maskedNumber'], -4)
			);
			$respuesta['data'] = $data;
		}

		return $respuesta;
	}

	public function entregarAliasTarjeta($documento) {
		$datos = array(
			'payerId' => $documento
		);

		$extras = array(
			'creditCardTokenList'
		);
		$this->setExtrasRetornar($extras);

		$respuesta = $this->enviar('GET_TOKENS', array('creditCardTokenInformation' => $datos));

		if($respuesta['response']) {
			$data = array();
			foreach($respuesta['data']['creditCardTokenList'] as $info_token) {
				$data[] = array(
					'token' => $info_token['creditCardTokenId'],
					'alias' => substr($info_token['maskedNumber'], -10),
					'bin' => substr($info_token['maskedNumber'], 0, 6)
				);
			}
			$respuesta['data'] = $data;
		}

		return $respuesta;
	}

	public function setCodigoPais($pais = Paises::CO) {
		$this->pais = $pais;
	}

	public function setUsuarioId($usuario_id) {
		$this->usuario_id = $usuario_id;
	}

    public function testMode($mode = true) {
        $this->test = $mode;
        parent::marcarTest();
	}

	protected function setExtrasRetornar($extras) {
		parent::setExtrasRetornar($extras);
	}

	protected function enviar($accion, $datos) {
		parent::setCodigoPais($this->pais);
		return parent::enviar($accion, $datos);
	}
}
