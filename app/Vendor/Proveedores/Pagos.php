<?php
class Tarjeta {
	public $numero = '';
	public $cvv = '';
	public $marca = '';
	public $mes_expira = '';
	public $anio_expira = '';
	public $documento = '';
	public $nombre = '';
	public $alias = '';
	public $direccion = '';
	public $celular = '';
	public $pais_cod = '';

	public function __construct($tarjeta = null) {
		$default = array(
			'numero' => '',
			'cvv' => '',
			'marca' => '',
			'mes_expira' => '',
			'anio_expira' => '',
			'documento' => '',
			'nombre' => '',
			'alias' => '',
			'direccion' => '',
			'celular' => '',
			'pais_cod' => ''
		);

		if(is_array($tarjeta)) {
			if(!isset($tarjeta['marca']) || empty($tarjeta['marca'])) {
				$tarjeta['marca'] = $this->detectarFranquiciaTarjeta($tarjeta['numero']);
			}

			$diferencia = array_diff(array_keys($tarjeta), array_keys($default));
			if(count($diferencia) > 0) {
				throw new Exception(sprintf("Los campos no son válidos: [%s]\n", implode(', ', $diferencia)));
			}
			$default = array_merge($default, $tarjeta);
		}

		$this->numero = preg_replace('/[^\d]/','', $default['numero']);
		$this->cvv = $default['cvv'];
		$this->marca = $default['marca'];
		$this->mes_expira = $default['mes_expira'];
		$this->anio_expira = $default['anio_expira'];
		$this->documento = $default['documento'];
		$this->nombre = $default['nombre'];
		$this->alias = $default['alias'];
		$this->direccion = $default['direccion'];
		$this->celular = $default['celular'];
		$this->pais_cod = $default['pais_cod'];
	}

	private function detectarFranquiciaTarjeta($number) {
		$number = preg_replace('/[^\d]/','',$number);
		$re = array(
			'electron' => '/^(4026|417500|4405|4508|4844|4913|4917)\d+$/',
			'maestro' => '/^(5018|5020|5038|5612|5893|6304|6759|6761|6762|6763|0604|6390)\d+$/',
			'dankort:' => '/^(5019)\d+$/',
			'interpayment' => '/^(636)\d+$/',
			'unionpay' => '/^(62|88)\d+$/',
			'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
			'mastercard' => '/^5[1-5][0-9]{14}$/',
			'amex' => '/^3[47][0-9]{13}$/',
			'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
			'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
			'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
			'codensa' => '/^(590712)\d+$/'
		);
		
		foreach($re as $tipo => $regexp) {
			if(preg_match($regexp, $number)) {
				return strtoupper($tipo);
			}
		}
		return 'UNDEFINED';
	}
}

class Adaptador {
	private $Proveedor = null;
	private $pedido_online_id = null;

	public function __construct($proveedor, $pedido_online_id, $config) {
		if(isset($proveedor) && isset($config)) {
			if(is_string($config)) {
				$config = json_decode($config, true);
			}
			$this->cargarProveedor($proveedor, $config);
		}

		if(isset($pedido_online_id)) {
			$this->pedido_online_id = $pedido_online_id;
		}
	}

	public function cargarProveedor($proveedor, $config) {
		try {
			App::import('Vendor', $proveedor, array('file' => 'Proveedores/'.$proveedor.'.php'));
			if(class_exists($proveedor)) {
				$this->Proveedor = new $proveedor($config);
			} else {
				throw new InvalidArgumentException('NoExisteClass');
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function hacerPagoConToken($token, $usuario_info, $valor_compra, $cuotas = 1, $descripcion = '', $establecimiento_id = 0) {
		if(isset($this->pedido_online_id)) {
			$this->Proveedor->setPedidoId($this->pedido_online_id);
		}
		return $this->Proveedor->hacerPagoConToken($token, $usuario_info, $valor_compra, $cuotas, $descripcion, $establecimiento_id);
	}

	public function hacerPago($tarjeta_info, $usuario_info, $valor_compra, $cuotas = 1, $descripcion = '') {
		if(isset($this->pedido_online_id)) {
			$this->Proveedor->setPedidoId($this->pedido_online_id);
		}
		return $this->Proveedor->hacerPago($tarjeta_info, $usuario_info, $valor_compra, $cuotas, $descripcion);
	}

	public function setCodigoPais($codigo_pais) {
		$this->Proveedor->setCodigoPais($codigo_pais);
	}

	public function setUsuarioId($usuario_id) {
		$this->Proveedor->setUsuarioId($usuario_id);
	}

	public function devolucion($registro_pago) {
		return $this->Proveedor->devolucion($registro_pago);
	}
	
	public function pendiente($pedido) {
		return $this->Proveedor->pendiente($pedido);
	}

	public function listarTransaccionesHoy($options = null) {
		return $this->Proveedor->listarTransaccionesHoy($options);
	}
	
	public function test() {
		$this->Proveedor->test();
	}
}


class Tokenizer {
	public $Proveedor = null;

	public function __construct($proveedor, $config) {
		if(is_string($config)) {
			$config = json_decode($config, true);
		}
		
		if(isset($proveedor)) {
			$this->cargarProveedor($proveedor, $config);
		}
	}

	public function cargarProveedor($proveedor, $config) {
		try {
			App::import('Vendor', $proveedor, array('file' => 'Proveedores/'.$proveedor.'.php'));
			if(class_exists($proveedor)) {
				$this->Proveedor = new $proveedor($config);
			} else {
				throw new InvalidArgumentException('NoExisteClass');
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function agregarTarjeta($Tarjeta) {
		return $this->Proveedor->agregarTarjeta($Tarjeta);
	}

	public function eliminarTarjeta($documento, $token, $celular) {
		return $this->Proveedor->eliminarTarjeta($documento, $token, $celular);
	}

	public function entregarAliasTarjeta($documento) {
		return $this->Proveedor->entregarAliasTarjeta($documento);
	}

	public function setCodigoPais($codigo_pais) {
		$this->Proveedor->setCodigoPais($codigo_pais);
	}

	public function setUsuarioId($usuario_id) {
		$this->Proveedor->setUsuarioId($usuario_id);
	}

	public function testMode($mode = true) {
		$this->Proveedor->testMode($mode);
	}
}

class CardData {
	private $brand = '';
	private $number = '';
	private $expiry_month = '';
	private $expiry_year = '';
	private $account_type_id = '04'; // tipo cuenta CMR SAS
	private $security_code = ''; // CVV2, CVC2, ALPHA
	private $name = '';
	private $document = '';
	private $alias = '';
	private $address = '';
	private $mobile = '';

	function __construct($Tarjeta) {
		$this->number = $Tarjeta->numero;
		$this->expiry_month = $Tarjeta->mes_expira;
		$this->expiry_year = $Tarjeta->anio_expira;
		$this->security_code = $Tarjeta->cvv;
		$this->brand = $Tarjeta->marca;
		$this->name = $Tarjet->nombre;
		$this->document = $Tarjet->documento;
		$this->alias = $Tarjet->alias;
		$this->address = $Tarjet->direccion;
		$this->mobile = $Tarjet->celular;

		if(empty($brand)) {
			$this->brand = $this->brandByNumber();
		}
	}

	private function brandByNumber() {
		$brands = array(
			'VISA' => '/^4[0-9]{6,}$/',
			'MASTERCARD' => '/^5[1-5][0-9]{5,}$/',
			'AMEX' => '/^3[47][0-9]{5,}$/',
			'DINERS' => '/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/',
			// 'CREDENCIAL' => ''
		);

		foreach($brands as $brand => $regexp) {
			if(preg_match($regexp, $this->number) == 1) {
				return $brand;
			}
		}
		return false;
	}

	public function alistaDatos() {
		$retornar = array(
			'brand' => $this->brand,
			'number' => $this->number,
			'expiryMonth' => $this->expiry_month,
			'expiryYear' => $this->expiry_year,
			'accountTypeId' => $this->account_type_id,
			'securityCode' => $this->security_code,
			'name' => $this->name,
			'document' => $this->document,
			'alias' => $this->alias,
			'address' => $this->address,
			'mobile' => $this->mobile
		);

		return $retornar;
	}
}

abstract class Paises {
	const CO = 'CO';
	const PE = 'PE';
	const EC = 'EC';

	// ARS 	Peso Argentino
	// BRL 	Real Brasileño
	// CLP 	Peso Chileno
	// COP 	Peso Colombiano
	// MXN 	Peso Mexicano
	// PEN 	Nuevo Sol Peruano
	// USD 	Dólar Americano

	public static $monedas = array(
		self::CO => 'COP',
		self::PE => 'PEN',
		self::EC => 'USD'
	);
}
