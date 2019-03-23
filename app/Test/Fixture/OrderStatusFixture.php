<?php
/**
 * OrderStatus Fixture
 */
class OrderStatusFixture extends CakeTestFixture {


	public $table = 'order_statuses';

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $import = array('model' => 'OrderStatus');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'status_name' => 'creado',
			'enabled' => 1
		),
		array(
			'id' => 2,
			'status_name' => 'fallido',
			'enabled' => 1
		),
		array(
			'id' => 3,
			'status_name' => 'recibido',
			'enabled' => 1
		),
		array(
			'id' => 4,
			'status_name' => 'aceptado',
			'enabled' => 1
		),
		array(
			'id' => 5,
			'status_name' => 'rechazado',
			'enabled' => 1
		)
	);

}
