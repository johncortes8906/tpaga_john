<?php
App::import('Vendor', 'TransmisionPayu', array('file' => 'Proveedores/TransmisionPayu.php'));
class Payu extends TransmisionPayu {
	private $pedido_id = null;
	private $pais = null;
	private $usuario_id = null;
	private $device_session_id = null;
	private $ip = null;
	private $cookie = null;
	private $user_agent = null;

	function __construct($config) {
		parent::__construct($config);
		$this->setCodigoPais();
		
		$this->device_session_id = md5(session_id());

		$this->setIp();
		
		if(isset($_COOKIE['CAKEPHP'])) {
			$this->cookie = $_COOKIE['CAKEPHP'];
		} else {
			$this->cookie = md5(time() + (86400* 7));
		}
		
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
		} else {
			$this->user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:43.0; Domicilios.com) Gecko/20100101 Firefox/43.0';
		}
	}
	private function setIp() {
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$this->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->ip = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
		} else if(!empty($_SERVER['REMOTE_ADDR'])){
			$this->ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$this->ip = '';
		}
	}
	public function hacerPagoConToken($token, $usuario, $valor_compra, $cuotas = 1, $descripcion = '') {
		if(empty($descripcion)) {
			$descripcion = 'Pago';
		}
		$reference_code = sprintf("pago_%s", $this->pedido_id);
		
		if(!isset($usuario['documento_recibe']) || empty($usuario['documento_recibe'])) {
			$usuario['documento_recibe'] = $usuario['documento'];
		}

        if ($this->pais == Paises::EC && $usuario['marca'] == 'DINERS') {
            $cuotas = 0;
        }
		
		$datos = array(
			'transaction' => array(
				'deviceSessionId' => $this->device_session_id,
				'ipAddress' => $this->ip,
				'cookie' => $this->cookie,
				'userAgent' => $this->user_agent,
				'order' => array(
					'accountId' => parent::getAccountId(),
					'referenceCode' => $reference_code,
					'description' => $descripcion,
					'language' => 'es',
					'signature' => $this->firma($reference_code, $valor_compra, $this->pais),
					'buyer' => array(
						'merchantBuyerId' => $usuario['documento_recibe'],
						'fullName' => $usuario['nombre_recibe'],
						'emailAddress' => $usuario['email'],
						'contactPhone' => $usuario['telefono_recibe'],
						'dniNumber' => $usuario['documento_recibe']
					),
					'additionalValues' => array(
						'TX_VALUE' => array(
							'value' => $valor_compra,
							'currency' => Paises::$monedas[$this->pais]
						),
						'TX_TAX' => array(
							'value' => 0,
							'currency' => Paises::$monedas[$this->pais]
						),
						'TX_TAX_RETURN_BASE' => array(
							'value' => 0,
							'currency' => Paises::$monedas[$this->pais]
						)
					)
				),
				'payer' => array(
					'merchantPayerId' => $usuario['documento'],
					'fullName' => $usuario['nombre'],
					'emailAddress' => $usuario['email'],
					'contactPhone' => $usuario['telefono'],
					'dniNumber' => $usuario['documento']
				),
				'creditCardTokenId' => $token['UsuariosToken']['token'],
				'extraParameters' => array(
					'INSTALLMENTS_NUMBER' => $cuotas
				),
				'type' => 'AUTHORIZATION_AND_CAPTURE',
				'paymentMethod' => $usuario['marca'],
				'paymentCountry' => $this->pais == 'EC' ? 'PE' : $this->pais
			)
		);

		if (isset($usuario['cvv'])) {
            $datos['transaction']['creditCard'] = array(
                'securityCode' => $usuario['cvv']
            );
        }

        if ($this->pais == Paises::CO && $datos['transaction']['paymentMethod'] == 'CODENSA') {
            $datos['transaction']['payer']['dniType'] = 'CC';
        }

		$extras = array('transactionResponse');
		$this->setExtrasRetornar($extras);

		$respuesta = $this->enviar('SUBMIT_TRANSACTION', $datos);
		
		if($respuesta['response']) {
			if(in_array($respuesta['data']['transactionResponse']['state'], array( 'APPROVED', 'PENDING'))) {
				$respuesta['msg'] = $respuesta['data']['transactionResponse']['orderId'];
				$respuesta['estado'] = $respuesta['data']['transactionResponse']['state'];
				$respuesta['cod_autorizacion'] = $respuesta['data']['transactionResponse']['transactionId'];
				$respuesta['referencia'] = $respuesta['data']['transactionResponse']['orderId'];
				$respuesta['fecha_transaccion'] = date('Y-m-d H:i:s', $respuesta['data']['transactionResponse']['operationDate'] / 1000);
			} else {
                if ($this->pais == Paises::CO 
                    && $datos['transaction']['paymentMethod'] == 'CODENSA'
                    && !preg_match('/^([1-9]|[1-1][0-2]|18|24|36|48)$/', $cuotas)) {
                    $respuesta['msg'] = "Codensa solo permite la siguiente cantidad de cuotas: 1 a 12, 18, 24, 36 y 48.";
                } else {
                    $respuesta['msg'] = $this->error[$respuesta['data']['extraParameters']['responseCode']];
                }
				$respuesta['cod_autorizacion'] = $respuesta['data']['transactionResponse']['transactionId'];
				$respuesta['referencia'] = $respuesta['data']['transactionResponse']['orderId'];
				$respuesta['response'] = false;
			}
		}
		return $respuesta;
	}

	public function hacerPago($tarjeta, $usuario, $valor_compra, $cuotas = 1, $descripcion = '') {
		if(empty($descripcion)) {
				$descripcion = 'Pago';
		}

		$reference_code = sprintf("pago_%s", $this->pedido_id);
		$datos = array(
			'transaction' => array(
				'deviceSessionId' => $this->device_session_id,
				'ipAddress' => $this->ip,
				'cookie' => $this->cookie,
				'userAgent' => $this->user_agent,
				'order' => array(
					'accountId' => parent::getAccountId(),
					'referenceCode' => $reference_code,
					'description' => $descripcion,
					'language' => 'es',
					'signature' => $this->firma($reference_code, $valor_compra),
					// 'notifyUrl' => '',
					'buyer' => array(
						'merchantBuyerId' => $usuario['documento_recibe'],
						'fullName' => $usuario['nombre_recibe'],
						'emailAddress' => $usuario['email'],
						'contactPhone' => $usuario['telefono_recibe'],
						'dniNumber' => $usuario['documento_recibe']
					),
					'additionalValues' => array(
						'TX_VALUE' => array(
							'value' => $valor_compra,
							'currency' => Paises::$monedas[$this->pais]
						),
						'TX_TAX' => array(
							'value' => 0,
							'currency' => Paises::$monedas[$this->pais]
						),
						'TX_TAX_RETURN_BASE' => array(
							'value' => 0,
							'currency' => Paises::$monedas[$this->pais]
						)
					)
				),
				'payer' => array(
					'merchantPayerId' => $usuario['documento'],
					'fullName' => $usuario['nombre'],
					'emailAddress' => $usuario['email'],
					'contactPhone' => $usuario['telefono'],
					'dniNumber' => $usuario['documento']
				),
				'creditCard' => array(
					'number' => $tarjeta['numero'],
					'securityCode' => $tarjeta['cvv'],
					'expirationDate' => sprintf("%s/%s", $tarjeta['anio_expira'], $tarjeta['mes_expira']),
					'name' => $tarjeta['nombre']
				),
				'extraParameters' => array(
					'INSTALLMENTS_NUMBER' => $cuotas
				),
				'type' => 'AUTHORIZATION_AND_CAPTURE',
				'paymentMethod' => $tarjeta['marca'],
				'paymentCountry' => $this->pais == 'EC' ? 'PE' : $this->pais
			)
		);

		$extras = array(
			'transactionResponse'
		);
		$this->setExtrasRetornar($extras);

		$respuesta = $this->enviar('SUBMIT_TRANSACTION', $datos);

		if($respuesta['response']) {
			if($respuesta['data']['transactionResponse']['state'] == 'APPROVED') {
				$respuesta['msg'] = $respuesta['data']['transactionResponse']['orderId'];
			} else {
				$respuesta['msg'] = $this->error[$respuesta['data']['transactionResponse']['responseCode']];
				$respuesta['response'] = false;
			}
		}

		return $respuesta;
	}
	
	public function devolucion($registro_pago, $razon = 'No se puede enviar el pedido.') {
		$type = 'REFUND';
		
		if($registro_pago['PagosOnline']['marca'] == 'MASTERCARD') {
			$type = 'VOID';
		}
		
		$datos = array(
			'transaction' => array(
				'order' => array(
					'id' => $registro_pago['PagosOnline']['referencia'],
				),
				'type' => $type,
				'reason' => $razon,
				'parentTransactionId' => $registro_pago['PagosOnline']['cod_autorizacion']
			)
		);
		
		$extras = array(
			'transactionResponse'
		);
		$this->setExtrasRetornar($extras);
		$respuesta = $this->enviar('SUBMIT_TRANSACTION', $datos);

		if($respuesta['response'] 
			&& isset($respuesta['data']) 
			&& isset($respuesta['data']['transactionResponse'])
			&& isset($respuesta['data']['transactionResponse']['state']) ) {
			if($respuesta['data']['transactionResponse']['state'] == 'APPROVED') {
				$respuesta['msg'] = $respuesta['data']['transactionResponse']['orderId'];
			} else {
				$respuesta['msg'] = $this->error[$respuesta['data']['transactionResponse']['responseCode']];
				$respuesta['response'] = false;
			}
		} else $respuesta = array('response' => false, 'msg' => 'Error al realizar la devolucion', 'data' => $respuesta);
		
		return $respuesta;
	}
	
	public function pendiente($pago) {
		parent::cambiaUrlConsulta();
		$datos = array(
			'details' => array(
				'transactionId' => $pago['PagosOnline']['cod_autorizacion']
			)
		);

		$extras = array(
			'payload'
		);
		
		$this->setExtrasRetornar($extras);
		$respuesta = $this->enviar('TRANSACTION_RESPONSE_DETAIL', $datos);

		return $respuesta;
	}
	
	// TODO
	public function listarTransaccionesHoy() {
		return array();
	}

	protected function enviar($accion, $datos) {
		parent::setCodigoPais($this->pais);
		return parent::enviar($accion, $datos);
	}

	public function setCodigoPais($pais = Paises::CO) {
		$this->pais = $pais;
	}

	public function setPedidoId($pedido_id) {
		$this->pedido_id = $pedido_id;
	}

	public function setUsuarioId($usuario_id) {
		$this->usuario_id = $usuario_id;
	}

	private function formateaCuotas($cuotas) {
		$cuotas = intval($cuotas);
		return sprintf("%'.03d", $cuotas);
	}
	
	public function test() {
		parent::marcarTest();
	}

	private function realizarConexion() {
		try {
			$this->client = parent::client;
			return true;
		} catch(Exception $e) {
			return false;
		}
	}

	private function firma($reference_code, $tx_value) {
		$api_key = parent::getApiKey();
		$merchant_id = parent::getMerchandId();
		$currency = Paises::$monedas[$this->pais];
		$signature = sprintf('%s~%s~%s~%s~%s', $api_key, $merchant_id, $reference_code, $tx_value, $currency);

		$signature = md5($signature);
		return $signature;
	}
	
	private $error = array(
		'ERROR' => 'Ocurrió un error general.',
		'APPROVED' => 'La transacción fue aprobada.',
		'ANTIFRAUD_REJECTED' => 'La transacción fue rechazada por el sistema anti-fraude.',
		'PAYMENT_NETWORK_REJECTED' => 'La red financiera rechazó la transacción.',
		'ENTITY_DECLINED' => 'Tu tarjeta de crédito no está soportada en el momento. Ingresa otra tarjeta por favor.',
		'INTERNAL_PAYMENT_PROVIDER_ERROR' => 'Ocurrió un error en el sistema intentando procesar el pago.',
		'INACTIVE_PAYMENT_PROVIDER' => 'El proveedor de pagos no se encontraba activo.',
		'DIGITAL_CERTIFICATE_NOT_FOUND' => 'La red financiera reportó un error en la autenticación.',
		'INVALID_EXPIRATION_DATE_OR_SECURITY_CODE' => 'El código de seguridad o la fecha de expiración estaba inválido.',
		'INVALID_RESPONSE_PARTIAL_APPROVAL' => 'Tipo de respuesta no válida. La entidad aprobó parcialmente la transacción y debe ser cancelada automáticamente por el sistema.',
		'INSUFFICIENT_FUNDS' => 'La cuenta no tenía fondos suficientes.',
		'CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS' => 'La tarjeta de crédito no estaba autorizada para transacciones por Internet.',
		'INVALID_TRANSACTION' => 'La red financiera reportó que la transacción fue inválida.',
		'INVALID_CARD' => 'La tarjeta es inválida.',
		'EXPIRED_CARD' => 'La tarjeta ya expiró.',
		'RESTRICTED_CARD' => 'La tarjeta presenta una restricción.',
		'CONTACT_THE_ENTITY' => 'Debe contactar al banco.',
		'REPEAT_TRANSACTION' => 'Se debe repetir la transacción.',
		'ENTITY_MESSAGING_ERROR' => 'La red financiera reportó un error de comunicaciones con el banco.',
		'BANK_UNREACHABLE' => 'El banco no se encontraba disponible.',
		'EXCEEDED_AMOUNT' => 'La transacción excede un monto establecido por el banco.',
		'NOT_ACCEPTED_TRANSACTION' => 'La transacción no fue aceptada por el banco por algún motivo.',
		'ERROR_CONVERTING_TRANSACTION_AMOUNTS' => 'Ocurrió un error convirtiendo los montos a la moneda de pago.',
		'EXPIRED_TRANSACTION' => 'La transacción expiró.',
		'PENDING_TRANSACTION_REVIEW' => 'La transacción fue detenida y debe ser revisada, esto puede ocurrir por filtros de seguridad.',
		'PENDING_TRANSACTION_CONFIRMATION' => 'La transacción está pendiente de ser confirmada.',
		'PENDING_TRANSACTION_TRANSMISSION' => 'La transacción está pendiente para ser trasmitida a la red financiera. Normalmente esto aplica para transacciones con medios de pago en efectivo.',
		'PAYMENT_NETWORK_BAD_RESPONSE' => 'El mensaje retornado por la red financiera es inconsistente.',
		'PAYMENT_NETWORK_NO_CONNECTION' => 'No se pudo realizar la conexión con la red financiera.',
		'PAYMENT_NETWORK_NO_RESPONSE' => 'La red financiera no respondió.',
		'FIX_NOT_REQUIRED' => 'Clínica de transacciones: Código de manejo interno.',
		'AUTOMATICALLY_FIXED_AND_SUCCESS_REVERSAL' => 'Clínica de transacciones: Código de manejo interno.',
		'AUTOMATICALLY_FIXED_AND_UNSUCCESS_REVERSAL' => 'Clínica de transacciones: Código de manejo interno.',
		'AUTOMATIC_FIXED_NOT_SUPPORTED' => 'Clínica de transacciones: Código de manejo interno.',
		'NOT_FIXED_FOR_ERROR_STATE' => 'Clínica de transacciones: Código de manejo interno.',
		'ERROR_FIXING_AND_REVERSING' => 'Clínica de transacciones: Código de manejo interno.',
		'ERROR_FIXING_INCOMPLETE_DATA' => 'Clínica de transacciones: Código de manejo interno.'
	);
}
