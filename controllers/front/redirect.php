<?php
/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 * @link      https://cardinity.com
 */

class CardinityRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $payment_id = urldecode(Tools::getValue('payment_id'));
        $url = urldecode(Tools::getValue('url'));
        $data = urldecode(Tools::getValue('data'));
        $link = new Link();
        $callback_url = $link->getModuleLink('cardinity', 'process');
        $order = $this->module->getPaymentOrder($payment_id);
        $order = new Order($order['id_order']);

        if ($this->module->validateOrderPayment($order)) {
            $this->context->smarty->assign(array(
                'cardinityUrl' => $url,
                'cardinityData' => $data,
                'cardinityCallbackUrl' => $callback_url,
                'cardinityPaymentId' => $payment_id
            ));

            $this->setTemplate('module:cardinity/views/templates/front/redirect.tpl');
        } else {
            $this->setTemplate('module:cardinity/views/templates/front/payment_process_error.tpl');
        }
    }
}
