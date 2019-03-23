<?php
App::uses('AppModel', 'Model');
/**
 * OrderStatus Model
 *
 */
class OrderStatus extends AppModel {

	public $table = 'order_statuses';

	public function getStatusByName($name)
	{
		return $this->find('first', 
			array('conditions' => array('status_name' => $name), 'recursive' => -1
		));
	}
	
	public $records = array(
		array(
			'status_name' => 'recibido',
			'enabled' => 1
		),
				array(
			'status_name' => 'aceptado',
			'enabled' => 1
		),
		array(
			'status_name' => 'rechazado',
			'enabled' => 1
		),
	);
}
