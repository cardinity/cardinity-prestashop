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
if (!defined('_PS_VERSION_')) {
    exit;
}
class CardinityValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (0 == $cart->id_customer
            || 0 == $cart->id_address_delivery
            || 0 == $cart->id_address_invoice
            || !$this->module->active
            || !$this->module->checkSupportedCurrencies()
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check if this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ('cardinity' == $module['name']) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = Context::getContext()->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('CARDINITY_PENDING'),
            $total,
            $this->module->displayName,
            null,
            [],
            $currency->id,
            false,
            $cart->secure_key
        );

        $order_id = $this->module->currentOrder;

        $link = new Link();
        Tools::redirect($link->getModuleLink('cardinity', 'process', ['order_id' => $order_id]));
    }
}
