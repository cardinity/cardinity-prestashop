<?php
/**
 * MIT License
 *
 * Copyright (c) 2023 Cardinity Payment Gateway
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Cardinity <info@cardinity.com>
 *  @copyright 2023 Cardinity Payment Gateway
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 *
 * @see      https://cardinity.com
 */
class CardinityReturnModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        PrestaShopLogger::addLog('Cardinity: External payment return', 1, null, null, null, true);
        PrestashopLogger::addLog('Cardinity ' . json_encode($_POST), 1, null, null, null, true);

        $message = '';
        ksort($_POST);

        foreach ($_POST as $key => $value) {
            if ('signature' == $key) {
                continue;
            }
            $message .= $key . $value;
        }

        $signature = hash_hmac('sha256', $message, Configuration::get('CARDINITY_PROJECT_SECRET'));
        $cart_id = Tools::getValue('order_id'); // $_POST['order_id'];
        $cart = new Cart($cart_id);
        $customer = new Customer($cart->id_customer);
        $postSignature = Tools::getValue('signature'); // $_POST['signature'];
        $postStatus = Tools::getValue('status'); // $_POST['status'];

        if ($signature == $postSignature && 'approved' == $postStatus) {
            // if everything is a success, mark the order as paid and redirect the client to a success page
            $this->module->validateOrder(
                $cart_id,
                Configuration::get('PS_OS_PAYMENT'),
                $cart->getOrderTotal(true, Cart::BOTH),
                $this->module->displayName,
                null,
                [],
                (int) Context::getContext()->currency->id,
                false,
                $customer->secure_key
            );

            $transactionData = [
                Tools::getValue('order_id'),
                Tools::getValue('id'),
                'unknown(external)',
                Tools::getValue('amount') . ' ' . Tools::getValue('currency'),
                Tools::getValue('status'),
            ];

            $this->module->addTransactionHistory($transactionData);

            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cart_id .
                    '&id_module=' . $this->module->id .
                    '&id_order=' . $cart_id .
                    '&key=' . $customer->secure_key
            );
        } else {
            /*
             * Log the transaction information if the order failed
             */
            error_log(json_encode($_POST));

            return $this->setTemplate('module:cardinity/views/templates/front/payment_process_error_external.tpl');
        }
    }
}
