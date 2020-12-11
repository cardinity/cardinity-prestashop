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

        if (Configuration::get('CARDINITY_EXTERNAL') == 1) {
            //External Requested
            $this->context->smarty->assign('attributes', $_POST);
            return $this->setTemplate('module:cardinity/views/templates/front/redirect_external.tpl');
        } else {
            //3ds Requested
            $is_v2 = urldecode(Tools::getValue('is_v2'));

            if ($is_v2) {
                $payment_id = urldecode(Tools::getValue('threeDSSessionData'));
            } else {
                $payment_id = urldecode(Tools::getValue('payment_id'));
            }

            $order = $this->module->getPaymentOrder($payment_id);
            $order = new Order($order['id_order']);

            if ($this->module->validateOrderPayment($order)) {
                if ($is_v2) {
                    $threeDSSessionData = urldecode(Tools::getValue('threeDSSessionData'));
                    $acs_url = urldecode(Tools::getValue('acs_url'));
                    $creq = urldecode(Tools::getValue('creq'));

                    $this->context->smarty->assign(array(
                        'acs_url' => $acs_url,
                        'creq' => $creq,
                        'threeDSSessionData' => $threeDSSessionData
                    ));

                    $this->setTemplate('module:cardinity/views/templates/front/redirect_3dsv2.tpl');
                } else {
                    $url = urldecode(Tools::getValue('url'));
                    $data = urldecode(Tools::getValue('data'));
                    $link = new Link();
                    $callback_url = $link->getModuleLink('cardinity', 'process');

                    $this->context->smarty->assign(array(
                        'cardinityUrl' => $url,
                        'cardinityData' => $data,
                        'cardinityCallbackUrl' => $callback_url,
                        'cardinityPaymentId' => $payment_id
                    ));

                    $this->setTemplate('module:cardinity/views/templates/front/redirect.tpl');
                }
            } else {
                $this->setTemplate('module:cardinity/views/templates/front/payment_process_error.tpl');
            }
        }
    }
}
