<?php
/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 * @link      https://cardinity.com
 */

class CardinityValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
            || !$this->module->checkSupportedCurrencies()
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check if this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'cardinity') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = Context::getContext()->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);


        $this->module->validateOrder(
            $cart->id,
            Configuration::get('CARDINITY_PENDING'),
            $total,
            $this->module->displayName,
            null,
            array(),
            $currency->id,
            false,
            $cart->secure_key
        );

        $order_id = $this->module->currentOrder;

        $link = new Link();
        Tools::redirect($link->getModuleLink('cardinity', 'process', array('order_id' => $order_id)));
    }
}
