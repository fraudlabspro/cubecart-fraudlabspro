<?php

$GLOBALS['main']->addTabControl('FraudLabs Pro Result', 'fraudlabs_pro_result');

$smarty_data['plugin_tabs'][] = showFraudLabsProResult($_GET['order_id']);

function showFraudLabsProResult($orderId)
{
	if (!$row = $GLOBALS['db']->select('plugin_fraudlabs_pro', '*', ['order_id' => $orderId])) {
		return '
		<div id="gui_message">
			<div class="infoText">
				<p>This order is not processed by FraudLabs Pro.</p>
			</div>
		</div>';
	}

	$json = json_decode($row[0]['raw_data']);

	if (isset($_GET['action']) && isset($_GET['id'])) {
		$note = (isset($_GET['reason'])) ? $_GET['reason'] : '';

		if (feedbackAPI($_GET['id'], ($_GET['action'] == 'approve') ? 'APPROVE' : (($_GET['action'] == 'reject') ? 'REJECT' : 'REJECT_BLACKLIST'), $note)) {
			$json->fraudlabspro_status = ($_GET['action'] == 'approve') ? 'APPROVE' : 'REJECT';

			if ($_GET['action'] == 'blacklist') {
				$json->is_blacklisted = 1;
			}

			$GLOBALS['db']->update('plugin_fraudlabs_pro', [
				'raw_data' => json_encode($json),
			], [
				'order_id' => $orderId,
			]);
		}
	}

	$location = [$json->ip_geolocation->continent, $json->ip_geolocation->country_name, $json->ip_geolocation->region, $json->ip_geolocation->city];
	$location = implode(', ', array_unique(array_diff($location, [''])));

	$html[] = '	<div id="fraudlabs_pro_result" class="tab_content">';
	$html[] = '		<h3>FraudLabs Pro Result</h3>';
	$html[] = '		<table class="table" width="100%" border="0" cellspacing="5" cellpadding="3" style="border-collapse:collapse">';
	$html[] = '			<col width="10%">';
	$html[] = '			<col width="10%">';
	$html[] = '			<col width="15%">';
	$html[] = '			<col width="2%">';
	$html[] = '			<col width="10%">';
	$html[] = '			<col width="15%">';
	$html[] = '			<col width="2%">';
	$html[] = '			<col width="10%">';
	$html[] = '			<col width="15%">';
	$html[] = '			<tr>';
	$html[] = '				<td rowspan="10" valign="top" align="center">';
	$html[] = '					' . ((preg_match('/^\d+$/', $json->fraudlabspro_score)) ? '<img src="https://fraudlabspro.hexa-soft.com/images/fraudscore/fraudlabsproscore' . $json->fraudlabspro_score . '.png" width="110" height="86" border="0">' : '');
	$html[] = '					<p><span style="font-size:1.5em;font-weight:bold;color:#' . (($json->fraudlabspro_status == 'APPROVE') ? '339933' : (($json->fraudlabspro_status == 'REVIEW') ? 'ff7f27' : 'f00')) . '">' . (($json->fraudlabspro_status == 'APPROVE') ? 'APPROVED' : (($json->fraudlabspro_status == 'REJECT') ? 'REJECTED' : $json->fraudlabspro_status)) . '</span></p>';
	$html[] = '					<hr/>';
	$html[] = '					<p><strong>Remaining Credits:</strong></p>';
	$html[] = '					<p>' . number_format($json->remaining_credits, 0, '', ',') . '</p>';
	$html[] = '				</td>';
	$html[] = '				<td><strong>Client IP</strong></td>';
	$html[] = '				<td>' . $json->ip_geolocation->ip . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>IP Location</strong></td>';
	$html[] = '				<td colspan="4"><a href="http://www.geolocation.com/' . $json->ip_geolocation->ip . '" target="_blank">' . $location . ' ' . (($location) ? '</a>' : '') . '<div style="font-size:12px">' . $json->ip_geolocation->latitude . ', ' . $json->ip_geolocation->longitude . '</div></td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>ISP</strong></td>';
	$html[] = '				<td>' . $json->ip_geolocation->isp_name . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>IP Domain</strong></td>';
	$html[] = '				<td>' . $json->ip_geolocation->domain . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>Net Speed</strong></td>';
	$html[] = '				<td>' . $json->ip_geolocation->netspeed . '</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Using Proxy</strong><div style="font-size:10px;color:#4b4b4b">Whether IP address is from Anonymous Proxy Server.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->ip_geolocation->is_proxy) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>IP Usage</strong></td>';
	$html[] = '				<td>' . implode(', ', $json->ip_geolocation->usage_type) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>Time Zone</strong></td>';
	$html[] = '				<td>' . $json->ip_geolocation->timezone . '</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Distance</strong><div style="font-size:10px;color:#4b4b4b">Distance from IP address to Billing Location.</div></td>';
	$html[] = '				<td>' . (($json->billing_address->ip_distance_in_mile) ? (number_format($json->billing_address->ip_distance_in_mile, 2, '.', ',') . ' Miles') : '') . (($json->billing_address->ip_distance_in_km != '-') ? (' (' . number_format($json->billing_address->ip_distance_in_km, 2, '.', ',') . ' KM)') : '') . '</td>';
	$html[] = '				<td colspan="6">&nbsp;</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Free Email</strong><div style="font-size:10px;color:#4b4b4b">Whether email is from free email provider.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->email_address->is_free) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>Ship Forward</strong><div style="font-size:10px;color:#4b4b4b">Whether shipping address is in database of known mail drops.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->shipping_address->is_address_ship_forward) . '</td>';
	$html[] = '				<td colspan="3">&nbsp;</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Email Blacklist</strong><div style="font-size:10px;color:#4b4b4b">Whether the email address is in our blacklist database.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->email_address->is_in_blacklist) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>Credit Card Blacklist</strong><div style="font-size:10px;color:#4b4b4b">Whether the credit card is in our blacklist database.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->credit_card->is_in_blacklist) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>BIN Found</strong><div style="font-size:10px;color:#4b4b4b">Whether the BIN information matches our BIN list.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->credit_card->is_bin_exist) . '</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>IP Blacklist</strong><div style="font-size:10px;color:#4b4b4b">Whether the client IP is in our blacklist database.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->ip_geolocation->is_in_blacklist) . '</td>';
	$html[] = '				<td>&nbsp;</td>';
	$html[] = '				<td><strong>Device Blacklist</strong><div style="font-size:10px;color:#4b4b4b">Whether the client device is in our blacklist database.</div></td>';
	$html[] = '				<td>' . displayYesNo($json->device->is_in_blacklist) . '</td>';
	$html[] = '				<td colspan="3">&nbsp;</td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Transaction ID</strong></td>';
	$html[] = '				<td colspan="8"><a href="https://www.fraudlabspro.com/merchant/transaction-details/' . $json->fraudlabspro_id . '" target="_blank">' . $json->fraudlabspro_id . '</a></td>';
	$html[] = '			</tr>';
	$html[] = '			<tr>';
	$html[] = '				<td><strong>Error Message</strong></td>';
	$html[] = '				<td colspan="7">' . (($json->error->error_message) ?? '-') . '</td>';
	$html[] = '			</tr>';
	$html[] = '		</table>';

	list($path, $queries) = explode('?', $_SERVER['REQUEST_URI']);
	parse_str($queries, $params);

	$params['id'] = $json->fraudlabspro_id;
	$params['checksum'] = md5(microtime());

	if ($json->fraudlabspro_status == 'REVIEW') {
		$html[] = '	<div class="form_control" style="text-align:center">';
		$html[] = '		<input type="button" value="Approve Order" style="background:#5da423;color:#FFFFFF;border:1px solid #4a831c;text-shadow:none" onclick="window.location.href=\'' . $path . '?' . http_build_query(array_merge($params, ['action' => 'approve'])) . '#fraudlabs_pro_result\';">';
		$html[] = '		<input type="button" value="Reject Order" style="background:#c60f13;color:#FFFFFF;border:1px solid #9e0c0f;text-shadow:none" onclick="window.location.href=\'' . $path . '?' . http_build_query(array_merge($params, ['action' => 'reject'])) . '#fraudlabs_pro_result\';">';

		if (!isset($json->is_blacklisted)) {
			$html[] = '		<input type="button" value="Blacklist Order" style="background:#707070;color:#FFFFFF;border:1px solid #616161;text-shadow:none" onclick="$(\'#dialog-reason\').show();$(\'#reason\').focus();">';
		}
		$html[] = '	</div>';
	} elseif (!isset($json->is_blacklisted)) {
		$html[] = '	<div class="form_control" style="text-align:center">';
		$html[] = '		<input type="button" value="Blacklist Order" style="background:#707070;color:#FFFFFF;border:1px solid #616161;text-shadow:none" onclick="$(\'#dialog-reason\').show();$(\'#reason\').focus();">';
		$html[] = '	</div>';
	}

	$html[] = '	<div class="form_control" style="text-align:center">';
	$html[] = '		<div id="dialog-reason" style="display:none">';
	$html[] = '			<p>Why you blacklist this order?</p>';
	$html[] = '			<p><textarea id="reason"></textarea></p>';
	$html[] = '			</p><input type="button" value="Submit Blacklist" onclick="window.location.href=\'' . $path . '?' . http_build_query(array_merge($params, ['action' => 'blacklist'])) . '&reason=\' + $(\'#reason\').val() + \'#fraudlabs_pro_result\';" /> <a href="javascript:;" onclick="$(\'#dialog-reason\').hide();">Close</a></p>';
	$html[] = '		</div>';
	$html[] = '	</div>';

	$html[] = '	</div>';

	return implode("\n", $html);
}

function displayYesNo($value)
{
	return ($value !== null) ? (($value) ? 'Yes' : 'No') : '-';
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
