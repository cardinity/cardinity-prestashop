<?php
/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 * @link      https://cardinity.com
 */

class CardinityReturnModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $message = '';
        ksort($_POST);

        foreach ($_POST as $key => $value) {
            if ($key == 'signature') {
               continue;
            }
            $message .= $key . $value;
        }

        $signature = hash_hmac('sha256', $message, Configuration::get('CARDINITY_PROJECT_SECRET'));
        $cart_id = Tools::getValue('order_id');//$_POST['order_id'];
        $cart = new Cart($cart_id);
        $customer = new Customer($cart->id_customer);
        $postSignature = Tools::getValue('signature');//$_POST['signature'];
        $postStatus = Tools::getValue('status');//$_POST['status'];
        
        if ($signature == $postSignature && $postStatus == 'approved') {
            // if everything is a success, mark the order as paid and redirect the client to a success page
            $this->module->validateOrder(
                $cart_id,
                Configuration::get('PS_OS_PAYMENT'),
                $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                array(),
                (int) Context::getContext()->currency->id,
                false,
                $customer->secure_key
            );


            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cart_id .
                    '&id_module=' . $this->module->id .
                    '&id_order=' . $cart_id .
                    '&key=' . $customer->secure_key
            );
        } else {
            /**
             * Log the transaction information if the order failed
             */
            error_log(json_encode($_POST));
            return $this->setTemplate('module:cardinity/views/templates/front/payment_process_error_external.tpl');
        }
    }
}
