<?php
abstract class SAPConfig {
	const CO = 1;
	const PE = 2;
	const EC = 4;

	abstract protected function getMoneda();
	abstract protected function getImpuesto();
	abstract protected function getExento();
	abstract protected function getAll();
	abstract protected function setPais($pais_id);

	public static $monedas = array(
		self::CO => 'COP',
		self::PE => 'SOL',	
		self::EC => 'USD'	
	);

	public static $impuesto = array(
		self::CO => 'IVAG19',
		self::PE => 'IGV',
		self::EC => 'IVA',
	);
	
	public static $exento = array(
		self::CO => 'IVAEXEN',
		self::PE => 'EXO',
		self::EC => 'IVAEXEN'
	);
}

class SAP extends SAPConfig {
	private $pais_id = parent::CO;

	public function __construct($pais_id = null) {
		if(isset($pais_id)) {
			$this->setPais($pais_id);
		}
	}

	public function setPais($pais_id) {
		$this->pais_id = $pais_id;
	}

	public function getMoneda() {
		return parent::$monedas[$this->pais_id];
	}
		
	public function getImpuesto() {
		return parent::$impuesto[$this->pais_id];
	}
		
	public function getExento() {
		return parent::$exento[$this->pais_id];
	}

	public function getAll() {
		return array(
			'moneda' => $this->getMoneda(),
			'impuesto' => $this->getImpuesto(),
			'exento' => $this->getExento()
		);
	}
}

