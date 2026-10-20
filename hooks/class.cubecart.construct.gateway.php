<?php
// Do not proceed if FraudLabs Pro settings not found
if(!$settings = $GLOBALS['config']->get('FraudLabsPro'))
	return;

// Do not proceed if FraudLabs Pro API key not available
if(!$settings['api_key'])
	return;

// Get order information from basket
$basket = $GLOBALS['cart']->basket;

// Do not proceed if gateway is capturing the credit card information
if($basket['gateway'] == 'Card_Capture')
	return;

// Include FraudLabs Pro library
require_once CC_ROOT_DIR . CC_DS . 'modules' . CC_DS . 'plugins' . CC_DS . 'fraudlabspro' . CC_DS . 'fraudlabspro.class.php';

$fraudlabs_pro = new FraudLabsPro($settings['api_key']);

// Order failed FarudLabs Pro validation
if(!$fraudlabs_pro->validate($basket)){
	$order = Order::getInstance();

	// Mark the order to "Fraudulent"
	$order->orderStatus(Order::ORDER_FAILED, $basket['cart_order_id']);

	// Skip payment page and completed the order immediately
	httpredir('?_a=complete');
}