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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

include_once _PS_MODULE_DIR_ . 'cardinity/libraries/OAuth/OAuthStore.php';
include_once _PS_MODULE_DIR_ . 'cardinity/libraries/OAuth/OAuthRequester.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cardinity extends PaymentModule
{
    public $consumer_key;
    public $consumer_secret;
    public $external;
    public $project_key;
    public $project_secret;
    public $page;
    public $fields_form;

    public function __construct()
    {
        $this->name = 'cardinity';
        $this->tab = 'payments_gateways';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->version = '4.1.0';
        $this->author = 'Cardinity';
        $this->module_key = 'dbc7d0655fa07a7fdafbc863104cc876';

        $config = Configuration::getMultiple([
            'CARDINITY_CONSUMER_KEY',
            'CARDINITY_CONSUMER_SECRET',
            'CARDINITY_EXTERNAL',
            'CARDINITY_PROJECT_KEY',
            'CARDINITY_PROJECT_SECRET',
        ]);

        $this->consumer_key = (isset($config['CARDINITY_CONSUMER_KEY'])) ? $config['CARDINITY_CONSUMER_KEY'] : 0;
        $this->consumer_secret = (isset($config['CARDINITY_CONSUMER_SECRET'])) ? $config['CARDINITY_CONSUMER_SECRET'] : 0;
        $this->external = (isset($config['CARDINITY_EXTERNAL'])) ? $config['CARDINITY_EXTERNAL'] : 0;
        $this->project_key = (isset($config['CARDINITY_PROJECT_KEY'])) ? $config['CARDINITY_PROJECT_KEY'] : 0;
        $this->project_secret = (isset($config['CARDINITY_PROJECT_SECRET'])) ? $config['CARDINITY_PROJECT_SECRET'] : 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Cardinity Payment Gateway');
        $this->description = $this->l('Accept debit or credit card payments on your website with Cardinity.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!isset($this->consumer_key) || !isset($this->consumer_secret)) {
            $this->warning = $this->l('API keys must be configured in order to use this module correctly.');
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency set for this module');
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn') || !$this->createTable()) {
            return false;
        }

        $order_pending = new OrderState();
        $order_pending->module_name = $this->name;
        foreach (Language::getLanguages() as $language) {
            if ('lt' == Tools::strtolower($language['iso_code'])) {
                $order_pending->name[$language['id_lang']] = 'Laukiama apmokėjimo banko kortele';
            } else {
                $order_pending->name[$language['id_lang']] = 'Awaiting Credit Card Payment';
            }
        }
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = '#4169E1';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        if ($order_pending->add()) {
            copy(_PS_ROOT_DIR_ . '/modules/cardinity/views/img/creditcards.gif', _PS_ROOT_DIR_ . '/img/os/' . (int) $order_pending->id . '.gif');
        }

        Configuration::updateValue('CARDINITY_PENDING', $order_pending->id);

        return true;
    }

    public function uninstall()
    {
        $order_state_pending = new OrderState(Configuration::get('CARDINITY_PENDING'));

        return
            Configuration::deleteByName('CARDINITY_CONSUMER_KEY')
            && Configuration::deleteByName('CARDINITY_CONSUMER_SECRET')
            && Configuration::deleteByName('CARDINITY_PROJECT_KEY')
            && Configuration::deleteByName('CARDINITY_PROJECT_SECRET')
            && Configuration::deleteByName('CARDINITY_EXTERNAL')
            && Configuration::deleteByName('CARDINITY_PENDING')
            && $order_state_pending->delete()
            && parent::uninstall()
        ;
    }

    private function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cardinity` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `id_shop` int(10) unsigned NOT NULL,
                    `id_payment` varchar(255),
                    `id_order` int(10) unsigned NOT NULL,
                    PRIMARY KEY (`id`, `id_shop`, `id_payment`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        $result = (bool) Db::getInstance()->execute($sql);

        return $result;
    }

    private function validatePostRequest()
    {
        if (Tools::getValue('consumer_key')) {
            if (empty(Tools::getValue('consumer_key'))) {
                return false;
            }
        }

        return true;
    }

    /* Admin form submit */
    private function processPostRequest()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (Tools::getValue('consumer_key')) {
                Configuration::updateValue('CARDINITY_CONSUMER_KEY', Tools::getValue('consumer_key'));
                Configuration::updateValue('CARDINITY_CONSUMER_SECRET', Tools::getValue('consumer_secret'));
                Configuration::updateValue('CARDINITY_EXTERNAL', Tools::getValue('external'));
                Configuration::updateValue('CARDINITY_PROJECT_KEY', Tools::getValue('project_key'));
                Configuration::updateValue('CARDINITY_PROJECT_SECRET', Tools::getValue('project_secret'));

                return $this->displayConfirmation($this->l('Settings updated'));
            }
        }
    }

    /* Admin module */
    public function getContent()
    {
        $logMessage = '';

        if ('downloadlog' == Tools::getValue('subaction', false)) {
            $currentFilename = 'transactions-' . Tools::getValue('year') . '-' . Tools::getValue('month');

            $currentDir = dirname(__FILE__);
            $transactionFile = $currentDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $currentFilename . '.log';

            $downloadFileName = 'crd-' . $currentFilename . '-' . time() . '.log';

            if (file_exists($transactionFile)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($downloadFileName) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($transactionFile));
                readfile($transactionFile);

            // exit;
            } else {
                $logMessage = 'No transaction log found for - ' . Tools::getValue('yesar') . ' / ' . Tools::getValue('month');
            }
        }

        $html = '';

        if ($this->validatePostRequest()) {
            $html .= $this->processPostRequest();
        } else {
            $html .= $this->displayError($this->l('Consumer Key is required!'));
        }

        $html .= $this->displayInfos();
        $html .= $this->renderForm();

        $html .= $this->displayTransactionHistory($logMessage);

        return $html;
    }

    /* Displays module info in admin */
    private function displayInfos()
    {
        return $this->display(__FILE__, 'views/templates/admin/infos.tpl');
    }

    private function displayTransactionHistory($logMessage)
    {
        $thisYear = (int) date('Y');
        $years = '';
        for ($i = $thisYear; $i >= $thisYear - 10; --$i) {
            $years .= "<option>$i</option>";
        }
        $months = '';
        for ($i = 1; 12 >= $i; ++$i) {
            $months .= "<option>$i</option>";
        }

        $this->context->smarty->assign(
            [
                'allYearOptions' => $years,
                'allMonthOptions' => $months,
                'message' => $logMessage,
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/transactions.tpl');
    }

    /* Renders admin module configuration form */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Please, enter your Cardinity credentials. You can find them on your Cardinity members area under Integration -> API Settings.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Consumer Key'),
                        'name' => 'consumer_key',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Consumer Secret'),
                        'name' => 'consumer_secret',
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('External checkout option'),
                        'name' => 'external',
                        'desc' => $this->l('Enable to send your customers to Cardinity External Checkout page.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Project Key'),
                        'name' => 'project_key',
                        'required' => false,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Project Secret'),
                        'name' => 'project_secret',
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'consumer_key' => Configuration::get('CARDINITY_CONSUMER_KEY'),
            'consumer_secret' => Configuration::get('CARDINITY_CONSUMER_SECRET'),
            'external' => Configuration::get('CARDINITY_EXTERNAL'),
            'project_key' => Configuration::get('CARDINITY_PROJECT_KEY'),
            'project_secret' => Configuration::get('CARDINITY_PROJECT_SECRET'),
        ];
    }

    public function getSupportedCurrencies($id_shop = null)
    {
        $id_shop = Context::getContext()->shop->id;

        $sql = 'SELECT c.*
                FROM `' . _DB_PREFIX_ . 'module_currency` mc
                LEFT JOIN `' . _DB_PREFIX_ . 'currency` c ON c.`id_currency` = mc.`id_currency`
                WHERE c.`deleted` = 0
                    AND mc.`id_module` = ' . (int) $this->id . '
                    AND c.`active` = 1
                    AND mc.id_shop = ' . (int) $id_shop . '
                ORDER BY c.`name` ASC';

        return Db::getInstance()->executeS($sql);
    }

    public function checkSupportedCurrencies()
    {
        $currencies = $this->getSupportedCurrencies();

        if (!empty($currencies)) {
            return true;
        }

        return false;
    }

    public function makePayment($data)
    {
        $url = 'https://api.cardinity.com/v1/payments';
        $method = 'POST';

        return $this->sendRequest($url, $method, $data);
    }

    public function finalizePayment($payment_id, $data)
    {
        $url = 'https://api.cardinity.com/v1/payments/' . $payment_id;
        $method = 'PATCH';

        return $this->sendRequest($url, $method, $data);
    }

    public function sendRequest($url, $method, $data)
    {
        $options = [
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
        ];

        OAuthStore::instance('2Leg', $options);

        $request = new OAuthRequester($url, $method, null);

        $oaheader = $request->getAuthorizationHeader();
        $headers = ['Content-Type: application/json', 'Authorization: ' . $oaheader];

        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $response = $request->doRequest(0, $curl_options);

        return json_decode($response['body']);
    }

    public function validateOrderPayment($order)
    {
        $state = $order->getCurrentState();

        if ($order->id
            && $order->module == $this->name
            // && $this->context->cookie->id_customer == $order->id_customer
            && !$order->valid
            && $state != (int) Configuration::get('PS_OS_CANCELED')
        ) {
            return true;
        }

        PrestaShopLogger::addLog("Attempt Validating Order Payment : id = $order->id, customer on cookie = " . $this->context->cookie->id_customer . ", customer on order =  $order->id_customer ", 1, $state, null, null, true);
        PrestaShopLogger::addLog('Failed Validating Order Payment', 1, $state, null, null, true);

        return false;
    }

    public function savePayment($response, $order_id)
    {
        $id_shop = Context::getContext()->shop->id;

        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'cardinity (id_shop, id_payment, id_order)
                VALUES (' . (int) $id_shop . ', "' . pSQL($response->id) . '", ' . (int) $order_id . ')';

        Db::getInstance()->execute($sql);
    }

    /* Gets id_order after 3d redirect */
    public function getPaymentOrder($payment_id)
    {
        $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'cardinity
                WHERE id_payment = "' . pSQL($payment_id) . '"';

        return Db::getInstance()->getRow($sql);
    }

    /* Called then respons status == 'approved' */
    public function approveOrderPayment($order, $transactionLogData = false)
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState((int) Configuration::get('PS_OS_PAYMENT'), $order->id);
        $history->addWithemail(true, [
            'order_name' => $order->id,
        ]);

        if ($transactionLogData) {
            $this->addTransactionHistory($transactionLogData);
        }
    }

    public function addTransactionHistory($data)
    {
        $currentFilename = 'transactions-' . date('Y-n') . '.log';

        $currentDir = dirname(__FILE__);

        $transactionFile = $currentDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $currentFilename;
        // $transactionFile = WP_CONTENT_DIR.'/uploads/wc-logs/cardinity-transactions.log';

        $message = '';
        if (!file_exists($transactionFile)) {
            $message = "OrderID :: PaymentID :: 3dsVersion :: Amount :: Status\n";
        }
        $message .= implode(' :: ', $data);

        file_put_contents($transactionFile, $message . "\n", FILE_APPEND);

        /*$fp = fopen($transactionFile, 'a');//opens file in append mode
        fwrite($fp, ' this is additional text ');
        fwrite($fp, 'appending data');
        fclose($fp);            */
    }

    /* Checkout payment gateway */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        // Check supportive currencies
        if (!$this->checkSupportedCurrencies()) {
            return;
        }

        $this->context->smarty->assign([
            'this_path' => $this->_path,
            'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
                . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ]);
        if (1 == Configuration::get('CARDINITY_EXTERNAL')) {
            $payment_options = [
                $this->getExternalPaymentOption($params),
            ];
        } else {
            $payment_options = [
                $this->getEmbeddedPaymentOption(),
            ];
        }

        return $payment_options;
    }

    public function getExternalPaymentOption($params)
    {
        // get currency
        $currency = new Currency($params['cart']->id_currency);
        // get address, from which we will get a country name and from that we will get a country code
        $address = new Address($params['cart']->id_address_delivery);
        $country = new Country($address->id_country);
        $customer = new Customer($params['cart']->id_customer);
        $attributes = [
            'amount' => number_format($params['cart']->getOrderTotal(true, Cart::BOTH), 2),
            'currency' => $currency->iso_code,
            'country' => $country->iso_code,
            'order_id' => str_pad($params['cart']->id, 2, '0', STR_PAD_LEFT),
            'description' => 'PS' . $params['cart']->id,
            'project_id' => Configuration::get('CARDINITY_PROJECT_KEY'),
            'return_url' => $this->context->link->getModuleLink($this->name, 'return'),
            'notification_url' => $this->context->link->getModuleLink($this->name, 'notify'),
        ];
        if ($customer->email) {
            $attributes['email_address'] = $customer->email;
        }
        ksort($attributes);

        $message = '';
        foreach ($attributes as $key => $value) {
            $message .= $key . $value;
        }

        $signature = hash_hmac('sha256', $message, $this->project_secret);
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Cardinity'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true))
            ->setInputs([
                'amount' => [
                    'name' => 'amount',
                    'type' => 'hidden',
                    'value' => $attributes['amount'],
                ],
                'currency' => [
                    'name' => 'currency',
                    'type' => 'hidden',
                    'value' => $attributes['currency'],
                ],
                'country' => [
                    'name' => 'country',
                    'type' => 'hidden',
                    'value' => $attributes['country'],
                ],
                'order_id' => [
                    'name' => 'order_id',
                    'type' => 'hidden',
                    'value' => $attributes['order_id'],
                ],
                'email_address' => [
                    'name' => 'email_address',
                    'type' => 'hidden',
                    'value' => $attributes['email_address'],
                ],
                'description' => [
                    'name' => 'description',
                    'type' => 'hidden',
                    'value' => $attributes['description'],
                ],
                'project_id' => [
                    'name' => 'project_id',
                    'type' => 'hidden',
                    'value' => $attributes['project_id'],
                ],
                'return_url' => [
                    'name' => 'return_url',
                    'type' => 'hidden',
                    'value' => $attributes['return_url'],
                ],
                'notification_url' => [
                    'name' => 'notification_url',
                    'type' => 'hidden',
                    'value' => $attributes['notification_url'],
                ],
                'signature' => [
                    'name' => 'signature',
                    'type' => 'hidden',
                    'value' => $signature,
                ],
            ])
            ->setAdditionalInformation($this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/payment_external.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'))
        ;

        PrestaShopLogger::addLog('Cardinity: External payment prep', 1, null, null, null, true);
        PrestashopLogger::addLog('Cardinity ' . json_encode($attributes), 1, null, null, null, true);

        return $externalOption;
    }

    // After Payment Method was Checked - Method for Payment Options in 1.7
    public function getEmbeddedPaymentOption()
    {
        $embeddedOption = new PaymentOption();
        $embeddedOption->setCallToActionText($this->l('Cardinity'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            // ->setForm($this->generateForm())
            ->setAdditionalInformation($this->context->smarty->fetch('module:cardinity/views/templates/hook/payment.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'))
        ;

        return $embeddedOption;
    }

    public function hookPaymentReturn($params)
    {
        $state = $params['order']->getCurrentState();

        if (in_array(
            $state,
            [
                Configuration::get('PS_OS_PAYMENT'),
                Configuration::get('PS_OS_OUTOFSTOCK'),
            ]
        )) {
            $this->smarty->assign([
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'status' => 'ok',
                'id_order' => $params['order']->id,
            ]);
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }
}
