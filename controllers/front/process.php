<?php
/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 * @link      https://cardinity.com
 */

class CardinityProcessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;
    public $errors = array();

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        
        parent::initContent();

        $order_id = (int)Tools::getValue('order_id');
        $payment_id = trim(Tools::getValue('MD'));        
        $threeDSSessionData = trim(Tools::getValue('threeDSSessionData'));
      
        
        if (empty($order_id) && !empty($payment_id)) {
            $order = $this->module->getPaymentOrder($payment_id);
            $order = new Order($order['id_order']);
        }elseif (empty($order_id) && !empty($threeDSSessionData)) {
            $order = $this->module->getPaymentOrder($threeDSSessionData);
            $order = new Order($order['id_order']);
        }else {
            $order = new Order($order_id);
        }
          

        // Validate order
        if ($this->module->validateOrderPayment($order)) {
            
            
            $currency = new Currency($order->id_currency);
            $cart = new Cart($order->id_cart);
            $customer = new Customer($cart->id_customer);

            // If 3-D
            if (empty($order_id) && !empty($payment_id)) {
                
                $pares = Tools::getValue('PaRes');
                $data = array('authorize_data' => $pares);

                $response = $this->module->finalizePayment($payment_id, $data);

                if ($response->status == 'approved') {
                    $this->module->approveOrderPayment($order);

                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                }

                // Validation errors are returned in errors array
                if ($response->status == 'declined' && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif ($response->status == 402) {
                    $this->errors[] = $response->detail;
                } elseif ($response->status >= 400) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            } elseif (empty($order_id) && !empty($threeDSSessionData)) {
                
                $cres = Tools::getValue('cres');
                $data = array('cres' => $cres);
                

                $response = $this->module->finalizePayment($threeDSSessionData, $data);

                PrestaShopLogger::addLog("3ds v2 callback",1);
                PrestaShopLogger::addLog(print_r($response, true) , 1);

                if ($response->status == 'approved') {
                    $this->module->approveOrderPayment($order);

                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                }

                // Validation errors are returned in errors array
                if ($response->status == 'declined' && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif ($response->status == 402) {
                    $this->errors[] = $response->detail;
                } elseif ($response->status >= 400) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            } elseif (Tools::getValue('make_payment')) {
                // If not 3-D yet
                $address = new Address((int)$cart->id_address_invoice);
                $country = new Country((int)$address->id_country);
                $total = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

                // Convert order ID to string with leading 0 because
                // Cardinity accepts only IDs with minimum length of 2
                $order_id_length = Tools::strlen((string)$order->id);
                $order_id_length++;
                $order_id_string = sprintf('%0' . $order_id_length . 'd', $order->id);

                //$link = new Link();
                

                $response = $this->module->makePayment(array(
                    'amount' => $total,
                    'currency' => $currency->iso_code,
                    'order_id' => $order_id_string,
                    'country' => $country->iso_code,
                    'payment_method' => 'card',
                    'payment_instrument' => array(
                        'pan' => strip_tags(str_replace(' ', '', Tools::getValue('card_pan'))),
                        'exp_year' => (int)Tools::getValue('expiration_year'),
                        'exp_month' => (int)Tools::getValue('expiration_month'),
                        'cvc' => strip_tags(trim(Tools::getValue('cvc'))),
                        'holder' => strip_tags(trim(Tools::getValue('card_holder')))
                    ),
                    'threeds2_data' =>  [
                        "notification_url" => $this->context->link->getModuleLink('cardinity', 'process'), 
                        "browser_info" => [
                            "accept_header" => "text/html",
                            "browser_language" => strip_tags(trim(Tools::getValue('browser_language'))),
                            "screen_width" => strip_tags(trim(Tools::getValue('screen_width'))),
                            "screen_height" => strip_tags(trim(Tools::getValue('screen_height'))),
                            'challenge_window_size' => "390x400",
                            "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                            "color_depth" => strip_tags(trim(Tools::getValue('color_depth'))),
                            "time_zone" => strip_tags(trim(Tools::getValue('time_zone')))
                        ],
                    ],
                ));

                if ($response->status == 'approved') {
                    $this->module->approveOrderPayment($order);

                    PrestaShopLogger::addLog('Cardinity: Response status approved', 1, null, null, null, true);
                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                } elseif ($response->status == 'pending') {

                             
                    if(property_exists($response, 'threeds2_data')){
                        //3ds v2
                        $this->module->savePayment($response, $order->id);

                        $acs_url = $response->threeds2_data->acs_url;
                        $creq = $response->threeds2_data->creq;
                        $url_params = array(
                            'is_v2' => urlencode(1),
                            'acs_url' => urlencode($acs_url),
                            'creq' => urlencode($creq),
                            'payment_id' => urlencode($response->id),
                            'threeDSSessionData' => urlencode($response->id),
                        );

                        PrestaShopLogger::addLog('Cardinity: Redirected to 3DSv2 secure page', 1, null, null, null, true);
                        Tools::redirect($this->context->link->getModuleLink('cardinity', 'redirect', $url_params));

                    }else{
                        //3ds v1
                        $this->module->savePayment($response, $order->id);

                        $url = $response->authorization_information->url;
                        $data = $response->authorization_information->data;
                        $link = new Link();
                        $url_params = array(
                            'is_v2' => urlencode(0),
                            'url' => urlencode($url),
                            'data' => urlencode($data),
                            'payment_id' => urlencode($response->id)
                        );

                        PrestaShopLogger::addLog('Cardinity: Redirected to 3D secure page', 1, null, null, null, true);
                        Tools::redirect($link->getModuleLink('cardinity', 'redirect', $url_params));
                    }                    
                }

                // Validation errors are returned in errors array
                if (isset($response->errors)) {
                    foreach ($response->errors as $error) {
                        $this->errors[] = Tools::ucfirst($error->message);
                    }
                } elseif ($response->status == 'declined' && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif ($response->status == 402) {
                    $this->errors[] = $response->detail;
                } elseif ($response->status >= 400) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            }

            // Render form
            $this->context->smarty->assign(array(
                'currency' => $currency,
                'orderId' => $order->id,
                'total' => $cart->getOrderTotal(true, Cart::BOTH),
                'this_path' => $this->module->getPathUri(),
                'this_path_bw' => $this->module->getPathUri(),
                'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
                'errors' => $this->errors,
                'input' => $_POST
            ));

            $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/cardinity/views/css/cardinity.css', 'all');
            $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/cardinity/views/css/responsive.css', 'all');

            $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/cardinity/views/js/jquery/jquery.payment.js');
            $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/cardinity/views/js/cardinity.js');

            $this->setTemplate('module:cardinity/views/templates/front/payment_process.tpl');
        } else {
            $this->setTemplate('module:cardinity/views/templates/front/payment_process_error.tpl');
        }
    }
}
