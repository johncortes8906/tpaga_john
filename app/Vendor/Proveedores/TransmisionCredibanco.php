<?php
define('CREDIBANCO_TRANSACCION_OK', 0);
Class TransmisionCredibanco {
	private $url = '';
	private $public_key = '';
	private $extras = null;
	private $error_codes = null;

	public $client = null;
	public $app_id = null;
	public $respuesta = null;
	public $pem = null;

	function __construct($config) {
		$this->cambiarMerchant($config);

		App::import('Vendor', 'nusoap/nusoap');
		$wsdl = $this->url;

		$this->cargarCertificado();
		$this->cargaErrorCodes();

		try {
			$this->client = new nusoap_client($wsdl, 'wsdl', false, false, false, false, 30, 40);
			$this->client->soap_defencoding = 'UTF-8';
			$this->client->decode_utf8 = false;
			return true;
		} catch(Exception $e) {
			return false;
		}
	}
	
/**
 * Cambia los datos del merchant
 *
 * @param mixed[] $config Estructura que mantiene los nuevos datos a cambiar. los valores requeridos son: 
 * mixed['url'], mixed['app_id'] y mixed['pem']
 *
 * @return boolean
 */
	protected function cambiarMerchant($config) {
		$this->url = $config['url'];
		$this->app_id = $config['app_id'];
		$this->pem = $config['pem'];
	}

	protected function enviar($accion, $datos) {
		$xml = $this->construirXml($accion, $datos);
		$xml = str_replace('<?xml version="1.0"?>', '', $xml);
		
		CakeLog::write('debugCredibanco', $this->url . ' | ' . $this->app_id . ' | ' . $xml);

		$respuesta = $this->client->call($accion, $xml);

		// print_r($respuesta);
		// print $this->client->request."\n\n";
		// print $this->client->response."\n\n";
		// print_r($this->client->getError());
		return $this->normalizaRespuesta($respuesta);
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

		CakeLog::write('debugCredibanco', json_encode($respuesta));

		if(isset($respuesta['respuesta']['codigo_Transaccion']) && $respuesta['respuesta']['codigo_Transaccion'] == CREDIBANCO_TRANSACCION_OK) {
			$formato_respuesta['response'] = true;
			$formato_respuesta['msg'] = (int)$respuesta['respuesta']['id_Transaccion'];

			if(isset($respuesta['respuesta']['codigo_Autorizacion'])) {
				$formato_respuesta['cod_autorizacion'] = (int)$respuesta['respuesta']['codigo_Autorizacion'];
			}

			if(isset($respuesta['respuesta']['fecha_Trasaccion'])) {
				$formato_respuesta['fecha_transaccion'] = $respuesta['respuesta']['fecha_Trasaccion'];
			}

			if(isset($respuesta['respuesta']['id_Transaccion'])) {
				$formato_respuesta['referencia'] = (int)$respuesta['respuesta']['id_Transaccion'];
			}

			$formato_respuesta['estado'] = '00-Aprobada';

			$info_extra = array();
			if(isset($this->extras)) {
				foreach($this->extras as $campo_extra) {
					$info_extra[ $campo_extra ] = $respuesta['respuesta'][ $campo_extra ];
				}

				$formato_respuesta['data'] = $info_extra;
			}
		} else {
			$estado_error = sprintf('%s-%s', $respuesta['respuesta']['codigo_Transaccion'], $respuesta['respuesta']['estado']);

			if( isset($this->error_codes[(string)$respuesta['respuesta']['codigo_Transaccion']]) ) {
				$codigo = (string)$respuesta['respuesta']['codigo_Transaccion'];
				$estado_error = sprintf('%s-%s', $codigo, $this->error_codes[$codigo]);
			}

			$formato_respuesta['msg'] = sprintf('Error - cod: %s - %s', $respuesta['respuesta']['codigo_Transaccion'], $respuesta['respuesta']['estado']);
			$formato_respuesta['estado'] = $estado_error;
		}

		$this->respuesta = $formato_respuesta;
		CakeLog::write('RespuestaCredibanco', json_encode($respuesta));
		return $this->respuesta;
	}

	protected function creaTransaccionId() {
		return date('YmdHis').rand(1111, 9999);
	}

	protected function encrypt($plain) {
		try {
			$encriptado = '';
			$res_pubkey = openssl_pkey_get_public($this->public_key);
			$resultado = openssl_public_encrypt($plain, $encriptado, $res_pubkey);

			return base64_encode($encriptado);
		} catch (Exception $e) {
			throw $e;
		}
	}

	protected function setExtrasRetornar($extras) {
		$this->extras = $extras;
	}

	private function construirXml($accion, $datos) {
		$Envelope = new SimpleXMLElement(sprintf('<%s/>', $accion));
		$Envelope->addChild('solicitud', '');
		$Envelope->addAttribute('xmlns', 'com.yellowpepper.ecommerce');
		$Envelope->solicitud->addAttribute('xmlns', '');

		$this->arrayToXml($datos, $Envelope->solicitud);

		$xml = $Envelope->asXML();

		return $xml;
	}

	/**
	 * Hace la magia de convertir un array en xml
	 *
	 * @param array $data la info del xml
	 * @param object &$xml el objeto simple xml
	 * @access private
	 */
	private function arrayToXml($data, &$xml) {
		foreach($data as $key => $value) {
			if(is_numeric($key)) {
				$this->arrayToXml($value, $xml);
			} else if(is_string($key) && is_array($value)) {
				$subnode = $xml->addChild("$key");
				$this->arrayToXml($value, $subnode);
			} else {
				$xml->addChild($key, htmlspecialchars($value));
			}
		}
	}

	private function cargarCertificado() {
		$this->public_key = file_get_contents(APP.'pem'.DS.$this->pem);
	}

	private function cargaErrorCodes() {
		$this->error_codes = json_decode(
		'{
		    "0": "Aprobada",
		    "1": "Negada, comuniquese con su entidad",
		    "2": "Negada, comuniquese con su entidad",
		    "3": "Negada, comercio inválido",
		    "4": "Negada, retener tarjeta",
		    "5": "Negada, puede ser tarjeta bloqueada o timeout",
		    "6": "Negada, no se pudo procesar la transacción",
		    "7": "Negada, retener tarjeta",
		    "8": "Aprobada, solicitar más información",
		    "9": "Negada, transacción duplicada",
		    "11": "Aprobada, vip",
		    "12": "Negada, transacción inválida",
		    "13": "Negada, monto inválido",
		    "14": "Negada, estado de la tarjeta inválido",
		    "15": "Negada, la institución no está en el IDF",
		    "16": "Negada, Numero cuotas invalidas",
		    "30": "Negada, error en edición de mensaje",
		    "31": "Negada, el emisor no es soportado por el Sistema",
		    "33": "Negada, tarjeta vencida con orden de retención",
		    "34": "Negada, retener/capturar",
		    "35": "Negada, retener/capturar",
		    "36": "Negada, retener tarjeta",
		    "37": "Negada, tarjeta bloqueada retener/capturar",
		    "38": "Negada, número de intentos del PIN excedidos",
		    "39": "Negada, puede ser tarjeta bloqueada o timeout",
		    "41": "Negada, tarjeta robada o extraviada",
		    "43": "Negada, estado en archivo de tarjetahabientes CAF",
		    "51": "Fondos insuficientes",
		    "54": "Negada, tarjeta vencida",
		    "55": "Negada, PIN inválido",
		    "56": "Negada, no se encontro CAF",
		    "57": "Negada, transacción no permitida a esta tarjeta",
		    "58": "Negada, transacción Inválida",
		    "61": "Negada, excede el monto máximo",
		    "62": "Negada, tarjeta restringida",
		    "65": "Negada, límite de usos por período excedido",
		    "68": "Negada, TIMEOUT",
		    "70": "Negada, tarjeta vencida",
		    "71": "Negada, El tipo de cuenta no corresponde",
		    "75": "Negada, número de intentos de PIN excedidos",
		    "76": "Aprobada, (Privado)",
		    "77": "Aprobada, pendiente identificación firma del comp",
		    "78": "Aprobada a ciegas",
		    "79": "Aprobada, transacción administrativa",
		    "80": "Aprobada por boletín de seguridad",
		    "81": "Aprobada por el establecimiento",
		    "82": "Negada, no hay módulo de seguridad",
		    "83": "Negada, no hay cuenta para la tarjeta",
		    "84": "Negada, no existe el archivo de saldos PBF",
		    "85": "Negada, error en actualización de archivo saldo",
		    "86": "Negada, tipo de autorización errado",
		    "87": "Negada, track 2 errado",
		    "88": "Negada, error en log de transacciones PTLF",
		    "89": "Negada, inválida la ruta de servicio",
		    "90": "Negada, no es posible autorizar",
		    "91": "Negada, no es posible autorizar",
		    "92": "Negada, puede ser tarjeta bloqueada o timeout",
		    "93": "Negada, no es posible autorizar",
		    "94": "Negada, transacción duplicada",
		    "96": "Negada, no se pudo procesar la transacción",
		    "97": "Negada, Número de documento inválido",
		    "98": "Negada, CVV2 invalido",
		    "99": "Error de comunicaciones",
		    "B1": "Numero de Factura invalida",
		    "B2": "Factura vencida",
		    "B3": "Factura pagada",
		    "F1": "Filtro por Comercio",
		    "F2": "FILTRO POR AGENCIA",
		    "F3": "Bin no permitido para esta aerolínea",
		    "N0": "Negada, no es posible autorizar",
		    "N1": "Negada, longitud del número de PAN inválida",
		    "N2": "Negada, se llenó el archivo de preautorizaciones",
		    "N3": "Negada, límite de retiros en línea excedido",
		    "N4": "Negada, límite retiros fuera de línea excedido",
		    "N5": "Negada, límite de crédito por retiro excedido",
		    "N6": "Negada, límite de retiros de crédito excedido",
		    "N7": "Negada, customer selected negative file reason",
		    "N8": "Negada, excede límite de piso",
		    "N9": "Negada, maximum number of refund credit",
		    "O0": "Negada, referral file full",
		    "O1": "Negada, NEG file problem",
		    "O2": "Negada, advances less than minimum",
		    "O3": "Negada, delinquent",
		    "O4": "Negada, over limit table",
		    "O5": "Negada, PIN required",
		    "O6": "Negada, mod 10 check",
		    "O7": "Negada, force post",
		    "O8": "Negada, bad PBF",
		    "O9": "Negada, NEG file problem",
		    "P0": "Negada, CAF problem",
		    "P1": "Negada, over daily limit",
		    "P3": "Negada, advance less than minimum",
		    "P4": "Negada, number times used",
		    "P5": "Negada, delinquent",
		    "P6": "Negada, over limit table",
		    "P7": "Negada, advance less than minimum",
		    "P8": "Negada, administrative card needed",
		    "P9": "Negada, enter lesser amount",
		    "Q0": "Negada, invalid transaction date",
		    "Q1": "Negada, Fecha de vencimiento invalida",
		    "Q2": "Negada, invalid transaction code",
		    "Q3": "Negada, valor del avance menor que el mínimo",
		    "Q4": "Negada, excedido el número de usos por período",
		    "Q5": "Negada, delinquent",
		    "Q6": "Negada, tabla de límites excedida",
		    "Q7": "Negada, el valor excede al máximo",
		    "Q8": "Negada, no se encuentra la tarjeta administrativa",
		    "Q9": "Negada, tarjeta administrativa no está permitida",
		    "R0": "Negada, transacción admin aprobada en ven",
		    "R1": "Negada, transacción admin aprobada fuera",
		    "R2": "Negada, transacción administrativa aprobada",
		    "R3": "Negada, la transacción Chargeback es aprobada",
		    "R4": "Negada, devolución/drchivo de usuario actualizado",
		    "R5": "Negada, devolución/número de prefijo incorrecto",
		    "R6": "Negada, devolución código de rspta incorrecto",
		    "R7": "Negada, transacción administrativa no soportada",
		    "R8": "Negada, la tarjeta está en archivo de negativos",
		    "S4": "Negada, PTLF full",
		    "S5": "Negada, devolución aprobada, archivo de cliente n",
		    "S6": "Negada, devolución aprobada, archivo de cliente n",
		    "S7": "Negada, devolución aceptada, destino incorrecto",
		    "S8": "Negada, ADMIN file problem",
		    "S9": "Negada, unable to validate PIN, security module is",
		    "T1": "Negada, tarjeta de crédito inválida",
		    "T2": "Negada, fecha de transacción inválida",
		    "T3": "Negada, card not supported",
		    "T4": "Negada, amount over maximum",
		    "T5": "Negada, CAF status = 0 or 9",
		    "T6": "Negada, Bad UAF",
		    "T7": "Negada, límite diario excedido en el Cash back",
		    "T8": "Negada, el enlace esta caido",
		    "TO": "Negada, time out"
		}', true);
	}
}
