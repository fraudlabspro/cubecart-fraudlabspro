<?php

class FraudLabsPro
{
	protected $apiKey;

	public function __construct($apiKey)
	{
		$this->apiKey = $apiKey;
	}

	public function validate($basket)
	{
		$quantity = 0;

		$items = [];

		foreach ($basket['contents'] as $item) {
			$quantity += $item['quantity'];
			$items[] = $item['product_code'] . ':' . $item['quantity'] . ':' . ((!$item['digital']) ? 'physical' : 'downloadable');
		}

		$coupon_code = '';

		if ($basket['coupons']) {
			foreach ($basket['coupons'] as $code => $values) {
				$coupon_code = $code;
				break;
			}
		}

		$queries = [
			'key'             => $this->apiKey,
			'format'          => 'json',
			'ip'              => $this->getClientIp(),
			'bill_to'         => $basket['billing_address']['company_name'],
			'bill_addr'       => trim($basket['billing_address']['line1'] . ' ' . $basket['billing_address']['line2']),
			'bill_city'       => $basket['billing_address']['town'],
			'bill_state'      => $basket['billing_address']['state'],
			'bill_zip_code'   => $basket['billing_address']['postcode'],
			'bill_country'    => $basket['billing_address']['country_iso'],
			'ship_addr'       => trim($basket['delivery_address']['line1'] . ' ' . $basket['delivery_address']['line2']),
			'ship_city'       => $basket['delivery_address']['town'],
			'ship_state'      => $basket['delivery_address']['state'],
			'ship_zip_code'   => $basket['delivery_address']['postcode'],
			'ship_country'    => $basket['delivery_address']['country_iso'],
			'email_domain'    => substr($basket['billing_address']['email'], strpos($basket['billing_address']['email'], '@') + 1),
			'user_phone'      => $basket['billing_address']['phone'],
			'email_hash'      => $this->hash($basket['billing_address']['email']),
			'first_name'      => $basket['billing_address']['first_name'],
			'last_name'       => $basket['billing_address']['last_name'],
			'email'           => $basket['billing_address']['email'],
			'user_order_id'   => $basket['cart_order_id'],
			'items'           => implode(',', $items),
			'coupon_code'     => $coupon_code,
			'payment_gateway' => $basket['gateway'],
			'payment_mode'    => $this->getPaymentMode($basket['gateway']),
			'currency'        => $GLOBALS['config']->get('config', 'default_currency'),
			'amount'          => $basket['total'],
			'quantity'        => $quantity,
			'source'          => 'CubeCart-' . CC_VERSION,
			'source_version'  => '1.7.0',
		];

		if (isset($basket['card_number'])) {
			$queries['card_hash'] = $this->hash($basket['card_number']);
			$queries['bin_no'] = substr($basket['card_number'], 0, 6);
		}

		$request = new Request('api.fraudlabspro.com', '/v2/order/screen');
		$request->setMethod('post');
		$request->setSSL();
		$request->setData($queries);
		$results = $request->send();

		if (($json = json_decode((string) $results)) !== null) {
			$GLOBALS['db']->insert('plugin_fraudlabs_pro', [
				'order_id' => $basket['cart_order_id'],
				'raw_data' => (string) $results,
			]);

			if ($json->fraudlabspro_status != 'APPROVE') {
				return false;
			}
		}

		return true;
	}

	private function getPaymentMode($gateway)
	{
		if ($gateway == 'Card_Capture') {
			return 'CREDITCARD';
		}

		if (substr(strtolower($gateway), 0, 6) == 'paypal') {
			return 'PAYPAL';
		}

		if (substr(strtolower($gateway), 0, 6) == 'google') {
			return 'GOOGLECHECKOUT';
		}

		return 'OTHER';
	}

	private function hash($s)
	{
		$hash = 'fraudlabspro_' . $s;

		for ($i = 0; $i < 65536; $i++) {
			$hash = sha1('fraudlabspro_' . $hash);
		}

		return hash('sha256', $hash);
	}

	private function getClientIp()
	{
		// For development usage
		if (isset($_SERVER['DEV_MODE'])) {
			return '80.239.243.251';
		}

		$headers = [
			'HTTP_CF_CONNECTING_IP', 'X-Real-IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_INCAP_CLIENT_IP', 'HTTP_X_SUCURI_CLIENTIP',
		];

		foreach ($headers as $header) {
			if (isset($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
				return $_SERVER[$header];
			}
		}

		return $_SERVER['REMOTE_ADDR'];
	}
}
