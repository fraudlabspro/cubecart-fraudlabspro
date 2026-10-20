<?php

if (isset($_GET['_g']) && $_GET['_g'] == 'orders') {
	$action = (isset($_GET['action'])) ? $_GET['action'] : '';
	$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';
	$oder_status = (isset($_POST['order']['status'])) ? $_POST['order']['status'] : '';

	if ($action == 'edit' && $order_id) {
		if (!$settings = $GLOBALS['config']->get('FraudLabsPro')) {
			return;
		}

		if (isset($settings['sync_status']) && $settings['sync_status']) {
			// Get FraudLabs Pro ID
			if (!$row = $GLOBALS['db']->select('plugin_fraudlabs_pro', '*', ['order_id' => $order_id])) {
				return;
			}

			if (($json = json_decode($row[0]['raw_data'])) !== null) {
				switch ($order_Status) {
					case 3: // Completed
						feedbackAPI($json->fraudlabspro_id, 'APPROVE');
						break;

					case 4: // Declined
					case 5: // Failed Fraud Review
					case 6: // Cancelled
						feedbackAPI($json->fraudlabspro_id, 'REJECT');
						break;
				}
			}
		}
	}
}

function feedbackAPI($id, $action, $notes = '')
{
	if (!$settings = $GLOBALS['config']->get('FraudLabsPro')) {
		return;
	}

	if (!$settings['api_key']) {
		return;
	}

	$request = new Request('api.fraudlabspro.com', '/v2/order/feedback');
	$request->setMethod('post');
	$request->setSSL();
	$request->setData([
		'key'    => $settings['api_key'],
		'id'     => $id,
		'action' => $action,
		'note'   => $notes,
		'format' => 'json',
		'source' => 'cubecart',
	]);

	$results = $request->send();

	if (($json = json_decode((string) $results)) !== null) {
		if (!isset($json->error)) {
			return true;
		}
	}

	return false;
}