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
class CardinityProcessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;
    public $errors = [];

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->controller->addJqueryPlugin('fancybox');
        $order_id = (int) Tools::getValue('order_id');
        $payment_id = trim(Tools::getValue('MD'));
        $threeDSSessionData = trim(Tools::getValue('threeDSSessionData'));

        if (empty($order_id) && !empty($payment_id)) {
            // its a 3dsv1
            $order = $this->module->getPaymentOrder($payment_id);
            $order = new Order($order['id_order']);
        } elseif (empty($order_id) && !empty($threeDSSessionData)) {
            // its a 3dsv2
            $order = $this->module->getPaymentOrder($threeDSSessionData);
            $order = new Order($order['id_order']);
        } else {
            $order = new Order($order_id);
        }

        // Validate order
        if ($this->module->validateOrderPayment($order)) {
            $currency = new Currency($order->id_currency);
            $cart = new Cart($order->id_cart);
            $customer = new Customer($order->id_customer);

            // If 3-D
            if (empty($order_id) && !empty($payment_id)) {
                $pares = Tools::getValue('PaRes');
                $data = ['authorize_data' => $pares];

                PrestaShopLogger::addLog('Cardinity 3ds v1 callback', 1, null, null, null, true);
                PrestashopLogger::addLog('Cardinity ' . json_encode($_POST), 1, null, null, null, true);

                $response = $this->module->finalizePayment($payment_id, $data);

                if ('approved' == $response->status) {
                    // 104 :: b54b19a2-5702-4945-9799-5253f7ce0b81 :: none :: 50.00 EUR :: approved
                    $transactionData = [
                        $order->id,
                        $payment_id,
                        'v1',
                        $response->amount . ' ' . $response->currency,
                        'approved',
                    ];
                    $this->module->approveOrderPayment($order, $transactionData);

                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                }

                // Validation errors are returned in errors array
                if ('declined' == $response->status && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif (402 == $response->status) {
                    $this->errors[] = $response->detail;
                } elseif (400 <= $response->status) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            } elseif (empty($order_id) && !empty($threeDSSessionData)) {
                $cres = Tools::getValue('cres');
                $data = ['cres' => $cres];

                PrestaShopLogger::addLog('Cardinity 3ds v2 callback', 1, null, null, null, true);
                PrestashopLogger::addLog('Cardinity ' . json_encode($_POST), 1, null, null, null, true);

                $response = $this->module->finalizePayment($threeDSSessionData, $data);

                if ('approved' == $response->status) {
                    PrestaShopLogger::addLog('Cardinity Payment Finalized Approved', 1, null, null, null, true);

                    $transactionData = [
                        $order->id,
                        $response->id,
                        'v2',
                        $response->amount . ' ' . $response->currency,
                        'approved',
                    ];

                    $this->module->approveOrderPayment($order, $transactionData);

                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                } elseif ('pending' == $response->status) {
                    PrestaShopLogger::addLog('Cardinity 3ds retry with v1', 1, null, null, null, true);

                    // 3dsv2 failed with pending, retry for 3dsv1
                    $url = $response->authorization_information->url;
                    $data = $response->authorization_information->data;
                    $link = new Link();
                    $url_params = [
                        'is_v2' => urlencode(0),
                        'url' => urlencode($url),
                        'data' => urlencode($data),
                        'payment_id' => urlencode($response->id),
                    ];

                    PrestaShopLogger::addLog('Cardinity: Redirected to 3D secure page', 1, null, null, null, true);
                    Tools::redirect($link->getModuleLink('cardinity', 'redirect', $url_params));
                }

                // Validation errors are returned in errors array
                if ('declined' == $response->status && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif (402 == $response->status) {
                    $this->errors[] = $response->detail;
                } elseif (400 <= $response->status) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            } elseif (Tools::getValue('make_payment')) {
                // If not 3-D yet
                $address = new Address((int) $cart->id_address_invoice);
                $country = new Country((int) $address->id_country);
                $total = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

                // Convert order ID to string with leading 0 because
                // Cardinity accepts only IDs with minimum length of 2
                $order_id_length = Tools::strlen((string) $order->id);
                ++$order_id_length;
                $order_id_string = sprintf('%0' . $order_id_length . 'd', $order->id);

                // $link = new Link();

                $paymentParams = [
                    'amount' => $total,
                    'currency' => $currency->iso_code,
                    'order_id' => $order_id_string,
                    'country' => $country->iso_code,
                    'payment_method' => 'card',
                    'payment_instrument' => [
                        'pan' => strip_tags(str_replace(' ', '', Tools::getValue('card_pan'))),
                        'exp_year' => (int) Tools::getValue('expiration_year'),
                        'exp_month' => (int) Tools::getValue('expiration_month'),
                        'cvc' => strip_tags(trim(Tools::getValue('cvc'))),
                        'holder' => strip_tags(trim(Tools::getValue('card_holder'))),
                    ],
                    'threeds2_data' => [
                        'notification_url' => $this->context->link->getModuleLink('cardinity', 'process'),
                        'browser_info' => [
                            'accept_header' => 'text/html',
                            'browser_language' => strip_tags(trim(Tools::getValue('browser_language'))),
                            'screen_width' => (int) strip_tags(trim(Tools::getValue('screen_width'))),
                            'screen_height' => (int) strip_tags(trim(Tools::getValue('screen_height'))),
                            'challenge_window_size' => strip_tags(trim(Tools::getValue('challenge_window_size'))),
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                            'color_depth' => (int) strip_tags(trim(Tools::getValue('color_depth'))),
                            'time_zone' => (int) strip_tags(trim(Tools::getValue('time_zone'))),
                        ],
                    ],
                ];

                if($customer->email){
                    $paymentParams['threeds2_data']['cardholder_info'] = [
                        'email_address' => $customer->email
                    ];
                }

                $logParams = $paymentParams;
                unset($logParams['payment_instrument']);
                PrestaShopLogger::addLog('Cardinity Creating ' . json_encode($logParams), 1, null, null, null, true);

                $response = $this->module->makePayment($paymentParams);

                if ('approved' == $response->status) {
                    $transactionData = [
                        $order->id,
                        $response->id,
                        'none',
                        $response->amount . ' ' . $response->currency,
                        'approved',
                    ];

                    $this->module->approveOrderPayment($order, $transactionData);

                    PrestaShopLogger::addLog('Cardinity: Response status approved', 1, null, null, null, true);
                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                        '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key
                    );
                } elseif ('pending' == $response->status) {
                    PrestaShopLogger::addLog('Cardinity: response: ' . json_encode($response), 1, null, null, null, true);

                    if (property_exists($response, 'threeds2_data')) {
                        // 3ds v2
                        $this->module->savePayment($response, $order->id);

                        $acs_url = $response->threeds2_data->acs_url;
                        $creq = $response->threeds2_data->creq;
                        $url_params = [
                            'is_v2' => urlencode(1),
                            'acs_url' => urlencode($acs_url),
                            'creq' => urlencode($creq),
                            'payment_id' => urlencode($response->id),
                            'threeDSSessionData' => urlencode($response->id),
                        ];

                        PrestaShopLogger::addLog('Cardinity: Redirected to 3DSv2 page', 1, null, null, null, true);
                        Tools::redirect($this->context->link->getModuleLink('cardinity', 'redirect', $url_params));
                    } else {
                        // 3ds v1
                        $this->module->savePayment($response, $order->id);

                        $url = $response->authorization_information->url;
                        $data = $response->authorization_information->data;
                        $link = new Link();
                        $url_params = [
                            'is_v2' => urlencode(0),
                            'url' => urlencode($url),
                            'data' => urlencode($data),
                            'payment_id' => urlencode($response->id),
                        ];

                        PrestaShopLogger::addLog('Cardinity: Redirected to 3D secure page', 1, null, null, null, true);
                        Tools::redirect($link->getModuleLink('cardinity', 'redirect', $url_params));
                    }
                }

                // Validation errors are returned in errors array
                if (isset($response->errors)) {
                    foreach ($response->errors as $error) {
                        $this->errors[] = Tools::ucfirst($error->message);
                    }
                } elseif ('declined' == $response->status && isset($response->error)) {
                    $this->errors[] = $response->error;
                } elseif (402 == $response->status) {
                    $this->errors[] = $response->detail;
                } elseif (400 <= $response->status) {
                    $this->errors[] = $this->module->l('Payment failed.', 'process');
                    PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
                }
            }

            // Render form
            $this->context->smarty->assign([
                'currency' => $currency,
                'orderId' => $order->id,
                'total' => $cart->getOrderTotal(true, Cart::BOTH),
                'this_path' => $this->module->getPathUri(),
                'this_path_bw' => $this->module->getPathUri(),
                'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
                'errors' => $this->errors,
                'input' => $_POST,
            ]);

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
