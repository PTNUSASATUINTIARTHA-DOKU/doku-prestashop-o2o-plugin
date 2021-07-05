<?php

class JokulO2oRequestModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	public function postProcess()
	{
		$jokulo2o = new jokulo2o();
		$task		         = $_GET['task'];
		$path = 'module:jokulo2o/views/templates/front/';

		switch ($task) {
			case "redirect":
				if (empty($_POST)) {
					echo "Stop : Access Not Valid";
					die;
				}

				$rawdata =	"PaymentCode:" . $_POST['PAYMENTCODE'] . "\n" .
					"PaymentExpired:" . $_POST['PAYMENTEXP'] . "\n" .
					"PaymentHow:" . $_POST['JOKULO2O_PAYMENTHOW'];

				$trx = array();
				$trx['amount']               	= $_POST['AMOUNT'];
				$trx['status_code']          	= $_POST['STATUSCODE'];
				$trx['payment_code']          	= $_POST['PAYMENTCODE'];
				$trx['raw_post_data']          	= $rawdata;

				$config = $jokulo2o->getServerConfig();

				$order_id = $jokulo2o->get_order_id($_POST['ORDERID']);

				$extra_vars = array(
					'{order_name}' => $_POST['TRANSIDMERCHANT']
				);
				if (!$order_id) {
					$order_state 				  = $config['DOKU_O2O_INITIALIZE_PAYMENT'];
					$trx['amount']                = $_POST['AMOUNT'];
					$jokulo2o->validateOrder($_POST['ORDERID'], $order_state, $trx['amount'], $jokulo2o->displayName, '', $extra_vars);
					$order_id = $jokulo2o->get_order_id($_POST['ORDERID']);
				}

				$order = new Order($order_id);
				$order->reference = $_POST['TRANSIDMERCHANT'];
				$order->update();

				$trx['invoice_number'] = $order->reference;
				$trx['order_id'] = $order_id;
				$trx_amount = number_format($order->getOrdersTotalPaid(), 2, '.', '');

				$trx['payment_channel']  = $_POST['PAYMENTCHANNEL'];
				$trx['ip_address']       = $jokulo2o->getipaddress();
				$trx['process_datetime'] = date("Y-m-d H:i:s");
				$trx['process_type']     = 'REDIRECT';

				$statuscode = $trx['status_code'];
				$statusnotify = 'SUCCESS';
				$resultcheck = $jokulo2o->checkTrx($trx, 'NOTIFY', $statusnotify);

				switch ($trx['status_code']) {
					case "0000":
						$result_msg = "SUCCESS";
						break;

					default:
						$result_msg = "FAILED";
						break;
				}

				# Check if the transaction have notify message  
				$result = $jokulo2o->checkTrx($trx, 'NOTIFY', $result_msg);
				$checkredirect = $jokulo2o->checkTrx($trx, 'REDIRECT');

				$trx['message'] = "Redirect process come from DOKU. Transaction is awaiting for payment";
				$status         = "pending";
				$status_no      = $config['DOKU_O2O_AWAITING_PAYMENT'];
				$template       = "pending_va.tpl";

				switch ($trx['payment_channel']) {
					case "ALFA":
						$payment_channel = "Alfamart";
						break;
				}

				$this->context->smarty->assign(array(
					'payment_channel' => $payment_channel, # ATM Transfer / Alfa Payment
					'payment_code'    => $trx['payment_code']
				));

				# Update order status
				$howToPay = $this->fetchEmailTemplate($_POST['JOKULO2O_PAYMENTHOWAPI']);
				$email_data = array(
					'{payment_channel}' => $payment_channel,
					'{amount}' => $trx['amount'],
					'{payment_code}' => $trx['payment_code'],
					'{how_to_pay}' => $howToPay
				);
				$jokulo2o->set_order_status($order_id, $status_no, $email_data);

				# Insert transaction redirect to table jokulo2o
				$jokulo2o->add_jokulo2o($trx);

				$this->setTemplate($path . $template);

				$cart = $this->context->cart;

				if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
					Tools::redirect('index.php?controller=order&step=1');
				}

				$customer = new Customer($cart->id_customer);
				if (!Validate::isLoadedObject($customer))
					Tools::redirect('index.php?controller=order&step=1');

				$currency = $this->context->currency;

				$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

				Configuration::updateValue('JOKULO2O_PAYMENT_CHANNEL', trim($payment_channel));
				Configuration::updateValue('JOKULO2O_PAYMENT_CODE', trim($trx['payment_code']));

				Configuration::updateValue('JOKULO2O_PAYMENT_AMOUNT', $trx_amount);

				$newDate = new DateTime($_POST['PAYMENTEXP']);
				Configuration::updateValue('JOKULO2O_PAYMENT_EXP', $newDate->format('d M Y H:i'));
				Configuration::updateValue('JOKULO2O_PAYMENTHOW', $_POST['JOKULO2O_PAYMENTHOW']);


				$config = Configuration::getMultiple(array('JOKULO2O_SERVER_DEST', 'JOKULO2O_CLIENT_ID_DEV', 'JOKULO2O_SHARED_KEY_DEV', 'JOKULO2O_CLIENT_ID_PROD', 'JOKULO2O_SHARED_KEY_PROD'));

				if (empty($config['JOKULO2O_SERVER_DEST']) || intval($config['JOKULO2O_SERVER_DEST']) == 0) {
					$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_DEV'));
					$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_DEV'));
				} else {
					$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_PROD'));
					$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_PROD'));
				}

				$mailVars = array(
					'{jokulva_client_id}'     => $CLIENT_ID,
					'{jokulva_shared_key}'  => $SHARED_KEY
				);

				Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
				break;
			case "redirectFailed":
				$template       = "failed.tpl";

				if ("ALFA" == $_POST['PAYMENTCHANNEL']) {
					$paymentChannel = "Alfamart";
				} else {
					$paymentChannel = "Unknown Channel";
				}

				$this->context->smarty->assign(array(
					'payment_channel' => $paymentChannel
				));

				parent::initContent();
				$this->setTemplate($path . $template);
				break;
		}
	}

	private function fetchEmailTemplate($url)
	{
		$response = $this->getEmailData($url);
		$paymentInstruction = $response->payment_instruction;
		$outputStep = '';
		foreach ($paymentInstruction as $value) {

			$stepIndex = 1;
			$outputStep .= $value->channel . "<br>" . "<br>";
			foreach ($value->step as $step) {

				$outputStep .= $stepIndex . ". " . $step . "<br>";
				$stepIndex++;
			}
			$outputStep .= "<br>";
		}

		return "<b>Cara Pembayaran</b> <br>" . $outputStep;
	}

	public function getEmailData($url)
	{
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseJson = curl_exec($ch);
		curl_close($ch);

		if (is_string($responseJson)) {
			$responsePayment = json_decode($responseJson, false);
		} else {
			$responsePayment = $responseJson;
		}

		return $responsePayment;
	}
}
