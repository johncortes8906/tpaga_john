<?php
/**
 * Order Fixture
 */
class OrderFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $import = array('model' => 'Order');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '1',
			'order_date' => '2019-03-21 11:35:02',
			'establishment_id' => '1',
			'total' => '0',
			'shopping_cart' => '[]',
			'user_id' => '1',
			'status_id' => '2',
			'payment_token' => 'pr-b9b447dc9288d1c409f9b678ec5abd2b595bbb54566d25039041954680533a1da4172bf2'
		),
	);

}
