<?php
App::uses('AppModel', 'Model');
/**
 * Order Model
 *
 * @property User $User
 */
class Order extends AppModel {


	// The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'OrderStatus' => array(
			'className' => 'OrderStatus',
			'foreignKey' => 'status_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),		
	);

	public function getOrdersByUserId($userId)
	{
		$fields = array('Order.order_date', 'Order.shopping_cart', 'User.user_name', 'OrderStatus.status_name');
		return $this->find('all', array(
			'fields' => $fields,
			'conditions' => array('Order.user_id' => $userId),
			'order' => array('Order.id DESC')
		));
	}
}
