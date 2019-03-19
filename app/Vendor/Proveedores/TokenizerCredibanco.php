<?php
App::import('Vendor', 'TransmisionCredibanco', array('file' => 'Proveedores/TransmisionCredibanco.php'));
class TokenizerCredibanco extends TransmisionCredibanco {
	private $pais = null;
	private $usuario_id = null;
	private $test = false;

	function __construct($config) {
		parent::__construct($config);
	}

	public function agregarTarjeta($Tarjeta) {
		if($this->test) {
			$token = $Tarjeta->alias;
		} else {
			$token = $this->getToken(9);
		}

		$datos = array(
			'id_Transaccion' => $this->creaTransaccionId(),
			'id_App' => $this->app_id,
			'numero_Tarjeta' => parent::encrypt($Tarjeta->numero),
			'cvv' => $Tarjeta->cvv,
			'marca' => $Tarjeta->marca,
			'fecha_Expiracion' => sprintf('%s-%s', $Tarjeta->anio_expira, $Tarjeta->mes_expira),
			'identificacion_Tarjetahabiente' => $Tarjeta->documento,
			'nombre_Tarjetahabiente' => $Tarjeta->nombre,
			'dirreccion_Correspondencia' => $Tarjeta->direccion,
			'numero_Celular' => $Tarjeta->celular,
			'alias_Tarjeta' => $token,
			'informacion_Adicional' => array(
				array('entry' => array(
					'key' => 'marca',
					'value' => $Tarjeta->marca
				)),
				array('entry' => array(
					'key' => 'nombre',
					'value' => $Tarjeta->nombre
				)),
				array('entry' => array(
					'key' => 'documento',
					'value' => $Tarjeta->documento
				)),
				array('entry' => array(
					'key' => 'alias',
					'value' => $Tarjeta->alias
				))
			)
		);

		$this->setExtrasRetornar($extras);
		$respuesta = $this->enviar('agregarTarjeta', $datos);

		if($respuesta['response']) {
			$data = array(
				'token' => $token,
				'nombre' => $Tarjeta->nombre,
				'usuario_id' => $this->usuario_id,
				'documento' => $Tarjeta->documento,
				'marca' => $Tarjeta->marca,
				'alias' => $Tarjeta->alias
			);

			$respuesta['data'] = $data;
		}

		return $respuesta;
	}

	public function eliminarTarjeta($documento, $token, $celular) {
		$datos = array(
			'id_Transaccion' => $this->creaTransaccionId(),
			'id_App' => $this->app_id,
			'identificacion_Tarjetahabiente' => $documento,
			'alias_Tarjeta' => $token,
			'numero_Celular' => $celular
		);
		$respuesta = $this->enviar('eliminarTarjeta', $datos);

		if($respuesta['response']) {
			$data = array(
				'token' => $token,
				'nombre' => '',
				'usuario_id' => $usuario_id,
				'marca' => '',
				'alias' => $token
			);
			$respuesta['data'] = $data;
		}

		return $respuesta;
	}

	public function entregarAliasTarjeta($documento) {
		$datos = array(
			'id_Transaccion' => $this->creaTransaccionId(),
			'id_App' => $this->app_id,
			'identificacion_Tarjetahabiente' => $documento
		);

		$extras = array(
			'alias_Tarjeta'
		);
		$this->setExtrasRetornar($extras);
		$respuesta = $this->enviar('entregarAliasTarjeta', $datos);

		$data = array();
		if($respuesta['response']) {
			if(is_array($respuesta['data']['alias_Tarjeta'])) {
				foreach($respuesta['data']['alias_Tarjeta'] as $alias) {
					$data[] = array(
						'token' => $alias,
						'alias' => $alias
					);
				}
			} else {
				$data[] = array(
					'token' => $respuesta['data']['alias_Tarjeta'],
					'alias' => $respuesta['data']['alias_Tarjeta']
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

	protected function setExtrasRetornar($extras) {
		parent::setExtrasRetornar($extras);
	}

	protected function enviar($accion, $datos) {
		return parent::enviar($accion, $datos);
	}

	public function testMode($mode = true) {
		$this->test = $mode;
	}

	private function crypto_rand_secure($min, $max) {
		$range = $max - $min;
		if ($range < 1) return $min; // not so random...
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd >= $range);
		return $min + $rnd;
	}

	private function getToken($length) {
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet) - 1;
		for($i = 0; $i < $length; $i++) {
			$token .= $codeAlphabet[$this->crypto_rand_secure(0, $max)];
		}
		return $token;
	}
}
