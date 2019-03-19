<?php
App::import('Vendor', 'TransmisionCredibanco', array('file' => 'Proveedores/TransmisionCredibanco.php'));
class Credibanco  extends TransmisionCredibanco {
	private $pedido_id = null;
	private $usuario_id = null;
	private $pais = null;

	private $admin_login = '';
	private $admin_password = '';

	function __construct($config) {
		parent::__construct($config);
			$this->admin_login = Configure::read('Credibanco.admin.login');
			$this->admin_password = Configure::read('Credibanco.admin.password');
	}

	public function hacerPagoConToken($token, $usuario, $valor_compra, $cuotas = 1, $descripcion = '', $establecimiento_id = 0) {
		$datos = array(
			'id_Transaccion' => parent::creaTransaccionId(),
			'id_App' => $this->app_id,
			'identificacion_Tarjetahabiente' => $usuario['documento'],
			'alias_Tarjeta' => $token,
			'valor_Compra' => $valor_compra,
			'cuotas' => $cuotas,
			'descripcion_Pago' => $descripcion,
			'informacion_Adicional' => $establecimiento_id
		);

		return $this->enviar('pagarConTarjeta', $datos);
	}

	public function setCodigoPais($pais = Paises::CO) {
		$this->pais = $pais;
	}

	protected function enviar($accion, $datos) {
		return parent::enviar($accion, $datos);
	}

	public function setPedidoId($pedido_id) {
		$this->pedido_id = $pedido_id;
	}

	public function setUsuarioId($usuario_id) {
		$this->usuario_id = $usuario_id;
	}

	public function devolucion($registro_pago) {
		App::import('Vendor', 'AccionesCredibanco', array('file' => 'Proveedores/AccionesCredibanco.php'));

		$AccionesCredibanco = new AccionesCredibanco($this->admin_login, $this->admin_password);
		$respuesta = $AccionesCredibanco->devolucion($registro_pago);

		return $respuesta;
	}

	public function listarTransaccionesHoy() {
		App::import('Vendor', 'AccionesCredibanco', array('file' => 'Proveedores/AccionesCredibanco.php'));

		$AccionesCredibanco = new AccionesCredibanco($this->admin_login, $this->admin_password);
		return $AccionesCredibanco->obtenerPedidos();
	}

	private function formateaCuotas($cuotas) {
		$cuotas = intval($cuotas);
		return sprintf("%'.03d", $cuotas);
	}

	private function realizarConexion() {
		try {
			$this->client = parent::client;
			return true;
		} catch(Exception $e) {
			return false;
		}
	}
}
