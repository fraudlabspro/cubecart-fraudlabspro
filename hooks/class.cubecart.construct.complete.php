<?php
// Do not proceed if FraudLabs Pro settings not found
if(!$settings = $GLOBALS['config']->get('FraudLabsPro'))
	return;

// Do not proceed if FraudLabs Pro API key not available
if(!$settings['api_key'])
	return;

// Get order information from basket
$basket = $GLOBALS['cart']->basket;

// Get order summary by order Id
$order = Order::getInstance();
$summary = $order->getSummary($basket['cart_order_id']);

// Do not proceed if credit card is not captured
if(empty($summary['offline_capture']))
	return;

// Decrypt the credit card information
$decrypt = Encryption::getInstance();
$decrypt->setup(false, $summary['cart_order_id']);
$card = unserialize($decrypt->decrypt(stripslashes($summary['offline_capture'])));

// Do not proceed if credit number is not available
if(!isset($card['card_number']))
	return;

$basket['card_number'] = $card['card_number'];

// Include FraudLabs Pro library
require_once CC_ROOT_DIR . CC_DS . 'modules' . CC_DS . 'plugins' . CC_DS . 'fraudlabspro' . CC_DS . 'fraudlabspro.class.php';

$fraudlabs_pro = new FraudLabsPro($settings['api_key']);

// Order failed FarudLabs Pro validation
if(!$fraudlabs_pro->validate($basket)){
	$order = Order::getInstance();

	// Mark the order to "Fraudulent"
	$order->orderStatus(Order::ORDER_FAILED, $basket['cart_order_id']);
}