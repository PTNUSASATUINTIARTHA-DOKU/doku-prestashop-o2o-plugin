<?php

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/jokulo2o.php');

$jokulo2o = new JokulO2O();

$task = $_GET['task'];

$json_data_input = json_decode(file_get_contents('php://input'), true);

switch ($task) {
	case "notify":
		if (empty($json_data_input)) {
			http_response_code(404);
			die;
		} else {
			$trx = array();
			$trx['invoice_number']           = $json_data_input['order']['invoice_number'];
			$trx['result_msg']                = null;
			$trx['process_type']             = 'NOTIFY';

			$config = $jokulo2o->getServerConfig();

			$orderId = $jokulo2o->get_order_id_jokul($trx['invoice_number'], $json_data_input['online_to_offline_info']['payment_code']);

			if (!$orderId) {
				$order_state = $config['DOKU_O2O_AWAITING_PAYMENT'];
				$trx['amount'] = $json_data_input["order"]["amount"];
				$orderId = $jokulo2o->get_orderId($trx['invoice_number']);
			}

			$order = new Order($orderId);

			$headers = getallheaders();
			$signature = generateSignature($headers, $jokulo2o->getKey());
			if ($headers['Signature'] == $signature) {
				$trx['raw_post_data']         = file_get_contents('php://input');
				$trx['ip_address']            = $jokulo2o->getipaddress();
				$trx['amount']                = $json_data_input['order']['amount'];
				$trx['invoice_number']        = $json_data_input['order']['invoice_number'];
				$trx['order_id']       		  = $orderId;
				$trx['payment_channel']       = $json_data_input['channel']['id'];
				$trx['payment_code']          = $json_data_input['online_to_offline_info']['payment_code'];
				$trx['doku_payment_datetime'] = $json_data_input['transaction']['date'];
				$trx['process_datetime']      = date("Y-m-d H:i:s");

				$result = $jokulo2o->checkTrxNotify($trx);

				if ($result < 1) {
					http_response_code(404);
				} else {
					$trx['message'] = "Notify process message come from DOKU. Success : completed";
					$status         = "completed";
					$status_no      = $config['DOKU_O2O_PAYMENT_RECEIVED'];
					$jokulo2o->emptybag();

					$jokulo2o->set_order_status($orderId, $status_no);

					$checkStatusTrx = $jokulo2o->checkStatusTrx($trx);
					if ($checkStatusTrx < 1) {
						$jokulo2o->add_jokulo2o($trx);
					}
				}
			} else {
				http_response_code(400);
			}
		}

		break;

	default:
		echo "Stop : Access Not Valid";
		die;
		break;
}

function generateSignature($headers, $secret)
{
	$digest = base64_encode(hash('sha256', file_get_contents('php://input'), true));
	$rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
		. "Request-Id:" . $headers['Request-Id'] . "\n"
		. "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
		. "Request-Target:" . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "\n"
		. "Digest:" . $digest;

	$signature = base64_encode(hash_hmac('sha256', $rawSignature, $secret, true));
	return 'HMACSHA256=' . $signature;
}
