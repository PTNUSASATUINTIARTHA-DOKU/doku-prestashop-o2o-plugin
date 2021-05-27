<?php

class JokulO2oValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
    */
    public function postProcess()
    {
            $cart = $this->context->cart;
            if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
                Tools::redirect('index.php?controller=order&step=1');

            // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
            $authorized = false;
            foreach (Module::getPaymentModules() as $module)
                if ($module['name'] == 'jokulo2o')
                {
                        $authorized = true;
                        break;
                }
            
            if (!$authorized)
                die($this->module->l('This payment method is not available.', 'validation'));

            $customer = new Customer($cart->id_customer);
            
            if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');

            $currency = $this->context->currency;
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
						
						$config = Configuration::getMultiple(array('JOKULO2O_SERVER_DEST', 'JOKULO2O_CLIENT_ID_DEV', 'JOKULO2O_SHARED_KEY_DEV', 'CHAIN_DEV', 'JOKULO2O_CLIENT_ID_PROD', 'JOKULO2O_SHARED_KEY_PROD', 'CHAIN_PROD'));

						if ( empty($config['JOKULO2O_SERVER_DEST']) || intval($config['JOKULO2O_SERVER_DEST']) == 0 )
						{
								$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_DEV'));
								$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_DEV'));
								$CHAIN      = Tools::safeOutput(Configuration::get('CHAIN_DEV'));				
						}
						else
						{
								$CLIENT_ID    = Tools::safeOutput(Configuration::get('JOKULO2O_CLIENT_ID_PROD'));
								$SHARED_KEY = Tools::safeOutput(Configuration::get('JOKULO2O_SHARED_KEY_PROD'));
								$CHAIN      = Tools::safeOutput(Configuration::get('CHAIN_PROD'));						
						}
						
            $mailVars = array(
                    '{jokulva_client_id}'     => $CLIENT_ID,
                    '{jokulva_shared_key}'  => $SHARED_KEY,
                    '{jokulva_chain}'       => $CHAIN
            );

            $order_status = $_POST['O_STATUS'];

            if ($order_status == 'succes') {
                $this->module->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder);
            }
            else {
                $this->module->validateOrder($cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder);
            }
    }
}

?>
