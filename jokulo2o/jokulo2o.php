<?php

/*
    Plugin Name : Prestashop DOKU Jokul O2O Payment Gateway
    Plugin URI  : http://www.doku.com
    Description : DOKU Jokul Convenoence Store Payment Gateway for Prestashop 1.7
    Version     : 1.0.0
    Author      : DOKU
    Author URI  : http://www.doku.com
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
	exit;

class JokulO2O extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();
	public $payment_channels;
	public $expiry_time;
	public $ip_range;
	public $o2o_channel;

	public function __construct()
	{
		$this->name             = 'jokulo2o';
		$this->tab              = 'payments_gateways';
		$this->author           = 'DOKU';
		$this->version          = '1.0.0';
		$this->need_instance 	= 0;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap 		= true;

		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();
		$this->displayName      = $this->l('Jokul - O2O');
		$this->description      = $this->l('Accept payments through convenience store with Jokul. Make it easy for your customers to purchase on your store.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		$this->o2o_channel       = array("ALFA");
	}

	public function install()
	{
		parent::install();
		$this->registerHook('paymentOptions');
		$this->registerHook('displayPaymentReturn');
		$this->registerHook('paymentReturn');
		$this->registerHook('updateOrderStatus');
		$this->addO2OOrderStatus();
		$this->copyEmailFiles();
		$this->createjokulo2oTable();
		Configuration::updateGlobalValue('JOKULO2O_MODULE_NAME', "O2O");
		Configuration::updateGlobalValue('JOKULO2O_MODULE_DESCRIPTION', "Please select payment channel");
		return true;
	}

	public function hookPaymentOptions($params)
	{
		if (!$this->active) {
			return;
		}

		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

		$cart = $this->context->cart;
		$this->execPayment($cart);

		$paymentOption = new PaymentOption();

		$paymentForm = $this->context->smarty->fetch('module:jokulo2o/views/templates/hook/payment_channel.tpl');
		$paymentOption->setCallToActionText(Configuration::get('JOKULO2O_MODULE_NAME'))
			->setForm($paymentForm)
			->setBinary(true);

		$payment_options = [
			$paymentOption,
		];

		return $payment_options;
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency']) {
					return true;
				}
			}
		}
		return false;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active) {
			return;
		}

		$this->smarty->assign(array(
			'payment_channel' => Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_CHANNEL', Configuration::get('JOKULO2O_PAYMENT_CHANNEL'))),
			'payment_code' => Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_CODE', Configuration::get('JOKULO2O_PAYMENT_CODE'))),
			'payment_amount' => Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_AMOUNT', Configuration::get('JOKULO2O_PAYMENT_AMOUNT'))),
			'payment_exp' => Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_EXP', Configuration::get('JOKULO2O_PAYMENT_EXP'))),
			'payment_how' => Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENTHOW', Configuration::get('JOKULO2O_PAYMENTHOW')))
		));


		return $this->fetch('module:jokulo2o/views/templates/hook/payment_return.tpl');
	}

	public function uninstall()
	{
		if (
			!parent::uninstall()
		) {
			return false;
		} else {
			Configuration::deleteByName('JOKULO2O_SERVER_DEST');
			Configuration::deleteByName('JOKULO2O_CLIENT_ID_DEV');
			Configuration::deleteByName('JOKULO2O_SHARED_KEY_DEV');
			Configuration::deleteByName('JOKULO2O_CLIENT_ID_PROD');
			Configuration::deleteByName('JOKULO2O_SHARED_KEY_PROD');
			Configuration::deleteByName('JOKULO2O_MODULE_NAME');
			Configuration::deleteByName('JOKULO2O_MODULE_DESCRIPTION');
			Configuration::deleteByName('JOKULO2O_FOOTER_MESSAGE');
			Configuration::deleteByName('JOKULO2O_EXPIRY_TIME');
			Configuration::deleteByName('JOKULO2O_PAYMENT_CHANNEL');
			Configuration::deleteByName('JOKULO2O_PAYMENT_CODE');
			Configuration::deleteByName('JOKULO2O_PAYMENT_CHANNELS');
			Configuration::deleteByName('JOKULO2O_PAYMENT_CHANNELS_ALFA');

			parent::uninstall();
			Db::getInstance()->Execute("DROP TABLE `" . _DB_PREFIX_ . "jokulo2o`");
			parent::uninstall();
			return true;
		}
	}

	function createjokulo2oTable()
	{
		$db = Db::getInstance();
		$query = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "jokulo2o`(
			`trx_id` int( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`ip_address` VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`process_type` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`process_datetime` DATETIME NULL, 
			`doku_payment_datetime` DATETIME NULL,   
			`invoice_number` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`order_id` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`amount` DECIMAL( 20,2 ) NOT NULL DEFAULT '0',
			`notify_type` VARCHAR( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`response_code` VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`status_code` VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`result_msg` VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`reversal` INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			`approval_code` CHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`payment_channel` VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`payment_code` VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`words` VARCHAR( 200 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',  
			`check_status` INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			`count_check_status` INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			`raw_post_data` TEXT COLLATE utf8_unicode_ci,  
			`message` TEXT COLLATE utf8_unicode_ci
		)";

		$db->Execute($query);
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit')) {
			if (!Tools::getValue('client_id_dev'))
				$this->$_postErrors[] = $this->l('Client ID is required.');
			if (!Tools::getValue('shared_key_dev'))
				$this->$_postErrors[] = $this->l('Secret Key is required.');
			if (!Tools::getValue('client_id_prod'))
				$this->$_postErrors[] = $this->l('Client ID is required.');
			if (!Tools::getValue('shared_key_prod'))
				$this->$_postErrors[] = $this->l('Secret Key is required.');
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit')) {
			Configuration::updateValue('JOKULO2O_SERVER_DEST',               			trim(Tools::getValue('server_dest')));
			Configuration::updateValue('JOKULO2O_CLIENT_ID_DEV',               			trim(Tools::getValue('client_id_dev')));
			Configuration::updateValue('JOKULO2O_SHARED_KEY_DEV',            			trim(Tools::getValue('shared_key_dev')));
			Configuration::updateValue('JOKULO2O_CLIENT_ID_PROD',              			trim(Tools::getValue('client_id_prod')));
			Configuration::updateValue('JOKULO2O_SHARED_KEY_PROD',           			trim(Tools::getValue('shared_key_prod')));
			Configuration::updateValue('JOKULO2O_MODULE_NAME',                 			trim(Tools::getValue('jokulo2o_module_name')));
			Configuration::updateValue('JOKULO2O_MODULE_DESCRIPTION',          			trim(Tools::getValue('jokulo2o_module_description')));
			Configuration::updateValue('JOKULO2O_FOOTER_MESSAGE', 						trim(Tools::getValue('footer_message')));
			Configuration::updateValue('JOKULO2O_EXPIRY_TIME', 							trim(Tools::getValue('expiry_time')));
			Configuration::updateValue('JOKULO2O_PAYMENT_CHANNELS', 					trim(Tools::getValue('payment_channels')));
			Configuration::updateValue('JOKULO2O_PAYMENT_CHANNELS_ALFA', 				trim(Tools::getValue('payment_channels_ALFA')));
		}
		$this->_html .= '<div class="alert alert-success conf confirm"> ' . $this->l('Settings updated') . '</div>';
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit')) {
			$this->_postValidation();
			if (!sizeof($this->$_postErrors)) {
				$this->_postProcess();
			} else {
				foreach ($this->$_postErrors as $err) {
					$this->_html .= '<div class="alert error">' . $err . '</div>';
				}
			}
		} else {
			$this->_html .= '<br />';
		}

		$this->_html .= $this->_displayBanner();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function renderForm()
	{
		$payment_channels = [


			[
				'id_option' => 'ALFA',
				'name' 		=> 'Alfamart',
			]

		];

		$environment = [
			[
				'id_option' => 'https://api-sandbox.doku.com',
				'name' 		=> 'Sandbox',
			],

			[
				'id_option' => 'https://api.doku.com',
				'name' 		=> 'Production',
			],
		];

		//CONFIGURATION FORM
		$fields_form = [

			'form'	 => [

				'legend' => [
					'title' => $this->l('Jokul - O2O Payment Configuration'),
					'icon' => 'icon-cogs'
				],
				'input' => 	[

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Payment Method Title'),
						'name'  => 'jokulo2o_module_name',
						'hint'  => [
							$this->l('This controls the title which the user sees during checkout.')
						],
					],

					[
						'type'  => 'textarea',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Description'),
						'name'  => 'jokulo2o_module_description',
						'hint'  => [
							$this->l('This controls the description which the user sees during checkout.')
						],
					],

					[
						'type' 		=> 'select',
						'label' 	=> $this->l('Environment'),
						'name' 		=> 'server_dest',
						'required' 	=> false,
						'hint'  	=> [
							$this->l('Sandbox mode provides you with a chance to test your gateway integration with Jokul. The payment requests will be send to the Jokul sandbox URL. Production to start accepting live payment.')
						],
						'options' 	=> [
							'query' => $environment,
							'id' 	=> 'id_option',
							'name' 	=> 'name'
						]
					],
					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Sandbox Client ID'),
						'name'  => 'client_id_dev',
						'hint'  => [
							$this->l('Sandbox Client ID.'),
						],
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Sandbox Secret Key'),
						'name'  => 'shared_key_dev',
						'hint'  => [
							$this->l('Sandbox Secret Key.')
						],
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Production Client ID'),
						'name'  => 'client_id_prod',
						'hint'  => [
							$this->l('Production Client ID.'),
						],
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Production Secret Key'),
						'name'  => 'shared_key_prod',
						'hint'  => [
							$this->l('Production Secret Key.')
						],
					],

					[
						'type' 		=> 'checkbox',
						'label' 	=> $this->l('Payment Channels'),
						'name' 		=> 'payment_channels',
						'multiple' 	=> true,

						'hint' 		=> [
							$this->l('Choose the payment channels that you can offer to the customers. The payment channels will be presented to the customer on the checkout page.')
						],

						'values' 	=> [
							'query'  => $payment_channels,
							'id' 	 => 'id_option',
							'name' 	 => 'name',
						]
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Footer Message'),
						'name'  => 'footer_message',
						'hint'  => [
							$this->l('Footer Message.')
						],
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('VA Expiry Time (in minutes)'),
						'name'  => 'expiry_time',
						'hint'  => [
							$this->l('Expiry Time.')
						],
					],

					[
						'type'  => 'text',
						'label' => '<span style="color:red"><b>*</b></span> ' . $this->l('Notification URL'),
						'name'  => 'notification_url',
						'disabled' => true,
						'desc' => 'Set this URL to Jokul Back Office',
						'hint'  => [
							$this->l('Notification URL.')
						],
					],
				],
				'submit' => [
					'title' => $this->l('Save'),
				]
			]
		];

		$helper 				= new HelperForm();
		$helper->show_toolbar 	= false;

		$helper->table 			= $this->table;
		$lang 					= new Language((int)Configuration::get('PS_LANG_DEFAULT'));


		$helper->default_form_language 		= $lang->id;

		$helper->allow_employee_form_lang 	= Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;


		$this->fields_form 		= array();
		$helper->id 			= (int)Tools::getValue('id_carrier');
		$helper->identifier 	= $this->identifier;
		$helper->submit_action 	= 'btnSubmit';


		$helper->currentIndex 	= $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;


		$helper->token 		= Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars 	= array(
			'fields_value' 	=> $this->getConfigFieldsValues(),
			'languages' 	=> $this->context->controller->getLanguages(),
			'id_language' 	=> $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function execPayment($cart)
	{
		if (!$this->active)
			return;

		$basket = '';
		global $cookie, $smarty;

		$jokulo2o = new JokulO2O();
		$cart            = new Cart(intval($cookie->id_cart));
		$address         = new Address(intval($cart->id_address_invoice));
		$country         = new Country(intval($address->id_country));
		$state           = NULL;
		if ($address->id_state)
			$state       = new State(intval($address->id_state));
		$customer        = new Customer(intval($cart->id_customer));
		$currency_order  = new Currency(intval($cart->id_currency));
		$products        = $cart->getProducts();
		$summarydetail   = $cart->getSummaryDetails();

		$i = 0;
		$basket = '';

		foreach ($products as $product) {
			$name_wt1  = preg_replace("/([^a-zA-Z0-9.\-=:&% ]+)/", " ", $product['name']);
			$name_wt = str_replace(',', '-', $name_wt1);
			$price_wt = number_format($product['price_wt'], 2, '.', '');
			$total_wt = number_format($product['total_wt'], 2, '.', '');

			$basket .= $name_wt . ',';
			$basket .= $price_wt . ',';
			$basket .= $product['cart_quantity'] . ',';
			$basket .= $total_wt . ';';
		}

		# Discount
		if ($summarydetail['total_discounts'] > 0) {
			$nDiskon =    number_format($summarydetail['total_discounts'], 2, '.', '');
			$nMinus  = -1 * $nDiskon;

			$basket .= 'Total Discount ,';
			$basket .=  $nMinus . ',';
			$basket .=  '1,';
			$basket .=  $nMinus . ';';
		}

		# Shipping
		if ($summarydetail['total_shipping'] > 0) {
			$basket .= 'Shipping Cost ,';
			$basket .=  number_format($summarydetail['total_shipping'], 2, '.', '') . ',';
			$basket .=  '1,';
			$basket .=  number_format($summarydetail['total_shipping'], 2, '.', '') . ';';
		}

		# Gift Wrapping
		if ($summarydetail['total_wrapping'] > 0) {
			$basket .= 'Gift Wrapping ,';
			$basket .=  number_format($summarydetail['total_wrapping'], 2, '.', '') . ',';
			$basket .=  '1,';
			$basket .=  number_format($summarydetail['total_wrapping'], 2, '.', '') . ';';
		}

		$total = $cart->getOrderTotal(true, Cart::BOTH);

		$this->total_amount = intval($total);

		$order       = new Order($jokulo2o->currentOrder);
		$server_dest = Tools::safeOutput(Configuration::get('JOKULO2O_SERVER_DEST'));

		if (empty($server_dest) || intval($server_dest) == 0) {
			$CLIENT_ID     			= Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_DEV'));
			$SHARED_KEY  			= Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_DEV'));
			$URL				 	= "";
			$URL_MERCHANTHOSTED 	= "config_url_va_dev.ini";
		} else {
			$CLIENT_ID     			= Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_PROD'));
			$SHARED_KEY  			= Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_PROD'));
			$URL				 	= "";
			$URL_MERCHANTHOSTED 	= "config_url_va_prod.ini";
		}

		# Set Redirect Parameter
		$CURRENCY            			= 360;
		$invoiceNumber  	   			= strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
		$orderid                        = intval($cart->id);
		$NAME                			= Tools::safeOutput($address->firstname . ' ' . $address->lastname);
		$EMAIL               			= $customer->email;
		$ADDRESS             			= Tools::safeOutput($address->address1 . ' ' . $address->address2);
		$CITY                			= Tools::safeOutput($address->city);
		$ZIPCODE             			= Tools::safeOutput($address->postcode);
		$STATE               			= Tools::safeOutput($state->name);
		$REQUEST_DATETIME    			= date("YmdHis");
		$IP_ADDRESS          			= $this->getipaddress();
		$PROCESS_DATETIME    			= date("Y-m-d H:i:s");
		$PROCESS_TYPE        			= "REQUEST";
		$amount              			= $total;
		$PHONE               			= trim($address->phone_mobile);
		$JOKULO2O_PAYMENT_CHANNEL     	= "";
		$JOKULO2O_FOOTER_MESSAGE		= Tools::safeOutput(Configuration::get('JOKULO2O_FOOTER_MESSAGE'));
		$JOKULO2O_EXPIRY_TIME 			= Tools::safeOutput(Configuration::get('JOKULO2O_EXPIRY_TIME'));

		$DATETIME = gmdate("Y-m-d H:i:s");
		$DATETIME = date(DATE_ISO8601, strtotime($DATETIME));
		$DATETIMEFINAL = substr($DATETIME, 0, 19) . "Z";

		$REGID = $this->guidv4();

		$SMARTY_ARRAY = 	array(
			'this_path'        				=> $this->_path,
			'this_path_ssl'    				=> Configuration::get('PS_FO_PROTOCOL') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . "modules/{$this->name}/",
			'payment_name'     				=> Configuration::get('JOKULO2O_MODULE_NAME'),
			'payment_description' 			=> Configuration::get('JOKULO2O_MODULE_DESCRIPTION'),
			'URL'			   				=> $URL,
			'amount'           				=> $amount,
			'PURCHASEAMOUNT'           		=> $amount,
			'JOKULO2O_FOOTER_MESSAGE'		=> $JOKULO2O_FOOTER_MESSAGE,
			'JOKULO2O_EXPIRY_TIME'          => $JOKULO2O_EXPIRY_TIME,
			'REGID'           				=> $REGID,
			'DATETIME'           			=> $DATETIMEFINAL,
			'invoice_number'  				=> $invoiceNumber,
			'order_id'  				    => $orderid,
			'REQUESTDATETIME'  				=> $REQUEST_DATETIME,
			'CURRENCY'         				=> $CURRENCY,
			'PURCHASECURRENCY' 				=> $CURRENCY,
			'PAYMENTCHANNEL'   				=> $JOKULO2O_PAYMENT_CHANNEL,
			'HOMEPHONE'        				=> $PHONE,
			'MOBILEPHONE'      				=> $PHONE,
			'BASKET'           				=> $basket,
			'ADDRESS'          				=> $ADDRESS,
			'CITY'             				=> $CITY,
			'STATE'            				=> $STATE,
			'ZIPCODE'          				=> $ZIPCODE,
			'SHIPPING_ZIPCODE' 				=> $ZIPCODE,
			'SHIPPING_CITY'    				=> $CITY,
			'SHIPPING_ADDRESS' 				=> $ADDRESS,
			'NAME' 						    => $NAME,
			'EMAIL' 					    => $EMAIL,
			'SHIPPING_COUNTRY' 				=> 'ID',
			'URL_MERCHANTHOSTED'			=> $URL_MERCHANTHOSTED,
			'JOKULO2O_PAYMENT_CHANNELS_ALFA'=> Tools::safeOutput(Configuration::get('JOKULO2O_PAYMENT_CHANNELS_ALFA')),
			'JOKULO2O_PAYMENT_CHANNELS'		=> Tools::safeOutput(Configuration::get('JOKULO2O_PAYMENT_CHANNELS'))
		);

		$smarty->assign($SMARTY_ARRAY);

		$trx['ip_address']          			= $IP_ADDRESS;
		$trx['process_type']        			= $PROCESS_TYPE;
		$trx['process_datetime']    			= $PROCESS_DATETIME;
		$trx['order_id']     					= $invoiceNumber;
		$trx['amount']              			= $amount;
		$trx['message']             			= "Transaction request start";
		$trx['raw_post_data']					= http_build_query($SMARTY_ARRAY, '', '&');

		$this->add_jokulo2o($trx);
	}

	function addO2OOrderStatus()
	{
		$stateConfig = array();
		try {
			$stateConfig['color'] = '#00ff00';
			$this->addOrderStatus(
				'DOKU_O2O_PAYMENT_RECEIVED',
				'O2O Payment Received',
				$stateConfig,
				true,
				''
			);
			$stateConfig['color'] = 'blue';
			$this->addOrderStatus(
				'DOKU_O2O_AWAITING_PAYMENT',
				'O2O Awaiting for Payment',
				$stateConfig,
				true,
				'doku_payment_code'
			);
			$this->addOrderStatus(
				'DOKU_O2O_INITIALIZE_PAYMENT',
				'O2O Payment Initialization',
				$stateConfig,
				false,
				''
			);
			return true;
		} catch (Exception $exception) {
			return false;
		}
	}

	function addOrderStatus($configKey, $statusName, $stateConfig, $send_email, $template)
	{
		if (!Configuration::get($configKey)) {
			$orderState = new OrderState();
			$orderState->name = array();
			$orderState->module_name = $this->name;
			$orderState->send_email = $send_email;
			$orderState->color = $stateConfig['color'];
			$orderState->hidden = false;
			$orderState->delivery = false;
			$orderState->logable = true;
			$orderState->invoice = false;
			$orderState->paid = false;

			foreach (Language::getLanguages() as $language) {
				$orderState->template[$language['id_lang']] = $template;
				$orderState->name[$language['id_lang']] = $statusName;
			}

			if ($orderState->add()) {
				$dokuIcon = dirname(__FILE__) . '/logo.png';
				$newStateIcon = dirname(__FILE__) . '/../../img/os/' . (int) $orderState->id . '.gif';
				copy($dokuIcon, $newStateIcon);
			}

			$order_state_id = (int) $orderState->id;
		} else {
			$order_state_id = Tools::safeOutput(Configuration::get($configKey));
		}

		Configuration::updateValue($configKey, $order_state_id);
	}

	function copyEmailFiles()
	{
		$folderSource = dirname(__FILE__) . '/mail';
		$folderDestination = _PS_ROOT_DIR_ . '/mails/en';

		$files = glob($folderSource . "/*.*");

		foreach ($files as $file) {
			$file_to_go = str_replace($folderSource, $folderDestination, $file);
			copy($file, $file_to_go);
		}
	}

	function deleteOrderState($id_order_state)
	{
		$orderState = new OrderState($id_order_state);
		$orderState->delete();
	}

	function getipaddress()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	function checkTrx($trx, $process = 'REQUEST', $result_msg = '')
	{
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;

		if ($result_msg == "PENDING") return 0;

		$check_result_msg = "";
		if (!empty($result_msg)) {
			$check_result_msg = " AND result_msg = '$result_msg'";
		}

		$db->Execute("SELECT * FROM " . $db_prefix . "jokulo2o" .
			" WHERE " .
			"invoice_number = '" . $trx['invoice_number'] . "'" .
			" AND payment_code = '" . $trx['payment_code'] . "'" .
			" AND amount = '" . $trx['amount'] . "'");

		return $db->numRows();
	}

	function checkTrxNotify($trx, $process = 'REQUEST', $result_msg = '')
	{
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;

		if ($result_msg == "PENDING") return 0;

		$check_result_msg = "";
		if (!empty($result_msg)) {
			$check_result_msg = " AND result_msg = '$result_msg'";
		}

		$db->Execute("SELECT * FROM " . $db_prefix . "jokulo2o" .
			" WHERE " .
			"invoice_number  = '" . $trx['invoice_number'] . "'" .
			" AND payment_code = '" . $trx['payment_code'] . "'" .
			" AND amount = '" . $trx['amount'] . "'");

		return $db->numRows();
	}

	function checkStatusTrx($trx)
	{
		$result_msg = "";
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;

		if ($result_msg == "PENDING") return 0;

		$check_result_msg = "";
		if (!empty($result_msg)) {
			$check_result_msg = " AND result_msg = '$result_msg'";
		}

		$db->Execute("SELECT * FROM " . $db_prefix . "jokulo2o" .
			" WHERE " .
			"invoice_number = '" . $trx['invoice_number'] . "'" .
			" AND payment_code = '" . $trx['payment_code'] . "'" .
			" AND process_type = 'NOTIFY'" .
			" AND amount = '" . $trx['amount'] . "'");

		return $db->numRows();
	}

	//Insert data to DB
	function add_jokulo2o($datainsert)
	{
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;

		$SQL = "";

		foreach ($datainsert as $field_name => $field_data) {
			$SQL .= " $field_name = '$field_data',";
		}
		$SQL = substr($SQL, 0, -1);

		$db->Execute("INSERT INTO " . $db_prefix . "jokulo2o SET $SQL");
	}

	function emptybag()
	{
		$products = $this->context->cart->getProducts();
		foreach ($products as $product) {
			$this->context->cart->deleteProduct($product["id_product"]);
		}
	}

	function get_order_id($cart_id)
	{
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;
		$SQL       = "SELECT id_order FROM " . $db_prefix . "orders WHERE id_cart = $cart_id";

		return $db->getValue($SQL);
	}

	function get_order_id_jokul($invoiceNumber, $paymentCode)
	{
		$db = Db::getInstance();

		$db_prefix = _DB_PREFIX_;
		$SQL       = "SELECT order_id FROM " . $db_prefix . "jokulo2o where invoice_number ='" . $invoiceNumber . "' and payment_code='" . $paymentCode . "'";

		return $db->getValue($SQL);
	}

	function set_order_status($order_id, $state, $emaildata = array())
	{
		$objOrder = new Order($order_id);
		$history = new OrderHistory();
		$history->id_order = (int) $objOrder->id;
		$history->changeIdOrderState((int) $state, (int) ($objOrder->id));
		$history->addWithemail(true, $emaildata);
		$history->save();
	}

	function getServerConfig()
	{
		$server_dest = Tools::safeOutput(Configuration::get('JOKULO2O_SERVER_DEST'));

		if (empty($server_dest) || intval($server_dest) == 0) {
			$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_DEV'));
			$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_DEV'));
			$URL_CHECK  = "";
		} else {
			$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_PROD'));
			$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_PROD'));
			$URL_CHECK  = "";
		}

		$USE_IDENTIFY = Tools::safeOutput(Configuration::get('USE_IDENTIFY'));
		
		$DOKU_O2O_INITIALIZE_PAYMENT = Tools::safeOutput(Configuration::get('DOKU_O2O_INITIALIZE_PAYMENT'));
		$DOKU_O2O_AWAITING_PAYMENT = Tools::safeOutput(Configuration::get('DOKU_O2O_AWAITING_PAYMENT'));
		$DOKU_O2O_PAYMENT_RECEIVED = Tools::safeOutput(Configuration::get('DOKU_O2O_PAYMENT_RECEIVED'));

		$config = array(
			"CLIENT_ID" => $CLIENT_ID,
			"SHARED_KEY" => $SHARED_KEY,
			"USE_IDENTIFY" => $USE_IDENTIFY,
			"URL_CHECK" => $URL_CHECK,
			"DOKU_O2O_INITIALIZE_PAYMENT" => $DOKU_O2O_INITIALIZE_PAYMENT,
			"DOKU_O2O_AWAITING_PAYMENT" => $DOKU_O2O_AWAITING_PAYMENT,
			"DOKU_O2O_PAYMENT_RECEIVED" => $DOKU_O2O_PAYMENT_RECEIVED
		);

		return $config;
	}

	//ADMIN -- GET DATA FROM CONFIGURATION FORM
	public function getConfigFieldsValues()
	{
		return array(
			'jokulo2o_module_name' 				=> Tools::safeOutput(Tools::getValue('JOKULO2O_MODULE_NAME', Configuration::get('JOKULO2O_MODULE_NAME'))),
			'jokulo2o_module_description' 		=> Tools::safeOutput(Tools::getValue('JOKULO2O_MODULE_DESCRIPTION', Configuration::get('JOKULO2O_MODULE_DESCRIPTION'))),
			'client_id_dev'						=> Tools::safeOutput(Tools::getValue('JOKULO2O_CLIENT_ID_DEV', Configuration::get('JOKULO2O_CLIENT_ID_DEV'))),
			'client_id_prod' 					=> Tools::safeOutput(Tools::getValue('JOKULO2O_CLIENT_ID_PROD', Configuration::get('JOKULO2O_CLIENT_ID_PROD'))),
			'shared_key_dev'					=> Tools::safeOutput(Tools::getValue('JOKULO2O_SHARED_KEY_DEV', Configuration::get('JOKULO2O_SHARED_KEY_DEV'))),
			'shared_key_prod'					=> Tools::safeOutput(Tools::getValue('JOKULO2O_SHARED_KEY_PROD', Configuration::get('JOKULO2O_SHARED_KEY_PROD'))),
			'payment_channels'					=> Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_CHANNELS', Configuration::get('JOKULO2O_PAYMENT_CHANNELS'))),
			'payment_channels_ALFA'				=> Tools::safeOutput(Tools::getValue('JOKULO2O_PAYMENT_CHANNELS_ALFA', Configuration::get('JOKULO2O_PAYMENT_CHANNELS_ALFA'))),
			'footer_message'					=> Tools::safeOutput(Tools::getValue('JOKULO2O_FOOTER_MESSAGE', Configuration::get('JOKULO2O_FOOTER_MESSAGE'))),
			'expiry_time'						=> Tools::safeOutput(Tools::getValue('JOKULO2O_EXPIRY_TIME', Configuration::get('JOKULO2O_EXPIRY_TIME'))),
			'server_dest'						=> Tools::safeOutput(Tools::getValue('JOKULO2O_SERVER_DEST', 	Configuration::get('JOKULO2O_SERVER_DEST'))),
			'notification_url' 					=> _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/jokulo2o/request.php?task=notify'
		);
	}

	private function _displayBanner()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	function getKey()
	{
		if (Configuration::get('SERVER_DEST') == 0) {
			return Configuration::get('JOKULO2O_SHARED_KEY_DEV');
		} else {
			return Configuration::get('JOKULO2O_SHARED_KEY_PROD');
		}
	}

	function doku_log($class, $log_msg, $invoiceNumber = "", $path)
    {
        $log_filename = "doku_log";
        $log_header = date(DATE_ATOM, time()) . ' ' . get_class($class) . '---> ' . $invoiceNumber;
        if (!file_exists($path.$log_filename)) {
            mkdir($path.$log_filename, 0777, true);
        }
        $log_file_data = $path.$log_filename . '/log_' . date('d-M-Y') . '.log';
        file_put_contents($log_file_data, $log_header . $log_msg . "\n", FILE_APPEND);
    }

	function guidv4($data = null)
	{
		$data = $data ?? random_bytes(16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
