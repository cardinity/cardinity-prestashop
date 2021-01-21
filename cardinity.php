<?php

include_once(_PS_MODULE_DIR_.'cardinity/libraries/OAuth/OAuthStore.php');
include_once(_PS_MODULE_DIR_.'cardinity/libraries/OAuth/OAuthRequester.php');

class Cardinity extends PaymentModule {

	public $consumer_key;
	public $consumer_secret;
	public $supported_currencies = array('EUR', 'USD', 'GBP');

	public function __construct()
	{
		$this->name = 'cardinity';
		$this->tab = 'payments_gateways';
		$this->version = '1.4.3';
		$this->author = 'Cardinity';
		$this->module_key = '132b5bda972a4f28b26d559db88e26ba';

		$this->bootstrap = true;

		$config = Configuration::getMultiple(array(
			'CARDINITY_CONSUMER_KEY',
			'CARDINITY_CONSUMER_SECRET'
		));

		$this->consumer_key = (isset($config['CARDINITY_CONSUMER_KEY'])) ? $config['CARDINITY_CONSUMER_KEY'] : 0;
		$this->consumer_secret = (isset($config['CARDINITY_CONSUMER_SECRET'])) ? $config['CARDINITY_CONSUMER_SECRET'] : 0;

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Cardinity');
		$this->description = $this->l('Accept debit or credit card payments on your website with Cardinity.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

		if (! isset($this->consumer_key) || ! isset($this->consumer_secret))
			$this->warning = $this->l('API keys must be configured in order to use this module correctly.');

		if (! count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency set for this module');
	}

	public function install()
	{
		if (! parent::install()
			|| ! $this->registerHook('payment')
			|| ! $this->registerHook('paymentReturn')
			|| ! $this->createTable()
			|| (version_compare(_PS_VERSION_, '1.5', '<') && ! $this->registerHook('header')))
			return false;

		// Order states
		$order_pending = new OrderState();
		$order_pending->module_name = $this->name;
		foreach (Language::getLanguages() as $language)
		{
			if (Tools::strtolower($language['iso_code']) == 'lt')
				$order_pending->name[$language['id_lang']] = 'Laukiama apmokėjimo banko kortele';
			else
				$order_pending->name[$language['id_lang']] = 'Awaiting Credit Card Payment';
		}
		$order_pending->send_email = 0;
		$order_pending->invoice = 0;
		$order_pending->color = '#4169E1';
		$order_pending->unremovable = false;
		$order_pending->logable = 0;

		if ($order_pending->add())
			copy(_PS_ROOT_DIR_.'/modules/cardinity/views/img/creditcards.gif', _PS_ROOT_DIR_.'/img/os/'.(int)$order_pending->id.'.gif');

		Configuration::updateValue('CARDINITY_PENDING', $order_pending->id);

		return true;
	}

	public function uninstall()
	{
		$order_state_pending = new OrderState(Configuration::get('CARDINITY_PENDING'));

		return (
			Configuration::deleteByName('CARDINITY_CONSUMER_KEY')
			&& Configuration::deleteByName('CARDINITY_CONSUMER_SECRET')
			&& Configuration::deleteByName('CARDINITY_PENDING')
			&& $order_state_pending->delete()
			&& parent::uninstall()
		);
	}

	private function createTable()
	{
		$res = (bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'cardinity` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`id_shop` int(10) unsigned NOT NULL,
				`id_payment` varchar(255),
				`id_order` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id`, `id_shop`, `id_payment`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
		');

		return $res;
	}

	private function validatePostRequest()
	{
		if (Tools::getValue('consumer_key'))
		{
			$consumer_key = Tools::getValue('consumer_key');
			if (empty($consumer_key))
				return false;
		}

		return true;
	}

	private function processPostRequest()
	{
		if (Tools::getValue('consumer_key'))
		{
			Configuration::updateValue('CARDINITY_CONSUMER_KEY', Tools::getValue('consumer_key'));
			Configuration::updateValue('CARDINITY_CONSUMER_SECRET', Tools::getValue('consumer_secret'));

			return $this->displayConfirmation($this->l('Settings updated'));
		}
	}

	public function getContent()
	{

		$logMessage = '';

        if(isset($_POST['subaction']) && $_POST['subaction']== 'downloadlog'){            

            $currentFilename = "transactions-". $_POST['year'] .'-'. $_POST['month'];
            
            $currentDir = dirname(__FILE__);
            $transactionFile = $currentDir .DIRECTORY_SEPARATOR . ".."  .DIRECTORY_SEPARATOR. ".."  .DIRECTORY_SEPARATOR .'log'.DIRECTORY_SEPARATOR . $currentFilename .'.log';            

			$downloadFileName = 'crd-'.$currentFilename.'-'.time().'.log';

			if (file_exists($transactionFile)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($downloadFileName).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($transactionFile));
                readfile($transactionFile);
                
				//exit;
			}else{
                $logMessage = "<div class='alert alert-info'>No transaction log found for - ".$_POST['year'] .' / '. $_POST['month']." .</div>";
            }
		}
		

		$html = '';

		if ($this->validatePostRequest())
			$html .= $this->processPostRequest();
		else
			$html .= $this->displayError($this->l('Consumer Key is required!'));

		$html .= $this->displayHeader();

		if (version_compare(_PS_VERSION_, '1.6', '<'))
			$html .= $this->displaySettings();
		else
			$html .= $this->renderForm();


		$html .= $this->displayTransactionHistory($logMessage);

		return $html;
	}

	private function displayHeader()
	{
		return $this->display(__FILE__, 'views/templates/admin/header.tpl');
	}

	private function displayTransactionHistory($logMessage){

        $thisYear = (int) Date("Y");
        $years = '';
        for($i = $thisYear; $i >= $thisYear -10 ; $i--){
            $years .= "<option>$i</option>";
        }
        $months = '';
        for($i = 1; $i <= 12 ; $i++){
            $months .= "<option>$i</option>";
        }
        
        $this->context->smarty->assign(
            array(
                'allYearOptions' => $years,
                'allMonthOptions' => $months,
                'message' => $logMessage
            )
        );
        
        return $this->display(__FILE__, 'views/templates/admin/transactions.tpl');
    }

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend'      => array(
					'title' => $this->l('Settings'),
					'icon'  => 'icon-cogs'
				),
				'description' => $this->l('Please, enter your Cardinity credentials. You can find them on your Cardinity members area under Integration -> API Settings.'),
				'input'       => array(
					array(
						'type'     => 'text',
						'label'    => $this->l('Consumer Key'),
						'name'     => 'consumer_key',
						'required' => true
					),
					array(
						'type'     => 'text',
						'label'    => $this->l('Consumer Secret'),
						'name'     => 'consumer_secret',
						'required' => true
					)
				),
				'submit'      => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages'    => $this->context->controller->getLanguages(),
			'id_language'  => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function displaySettings()
	{
		global $smarty;

		$smarty->assign(array(
			'formUrl'        => Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']),
			'consumerKey'    => Configuration::get('CARDINITY_CONSUMER_KEY'),
			'consumerSecret' => Configuration::get('CARDINITY_CONSUMER_SECRET')
		));

		return $this->display(__FILE__, 'views/templates/admin/settings.tpl');
	}

	public function getConfigFieldsValues()
	{
		return array(
			'consumer_key'    => Configuration::get('CARDINITY_CONSUMER_KEY'),
			'consumer_secret' => Configuration::get('CARDINITY_CONSUMER_SECRET')
		);
	}

	public function hookPayment()
	{
		global $smarty;

		if (! $this->active)
			return;

		if (! $this->checkSupportedCurrencies())
			return;

		$smarty->assign(array(
			'this_path'     => $this->_path,
			'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
				.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	public function getSupportedCurrencies($id_shop = null)
	{
		if (is_null($id_shop) && version_compare(_PS_VERSION_, '1.5', '>='))
			$id_shop = Context::getContext()->shop->id;

		$sql = 'SELECT c.*
				FROM `'._DB_PREFIX_.'module_currency` mc
				LEFT JOIN `'._DB_PREFIX_.'currency` c ON c.`id_currency` = mc.`id_currency`
				WHERE c.`deleted` = 0
					AND mc.`id_module` = '.(int)$this->id.'
					AND c.`active` = 1';

		if (! is_null($id_shop))
			$sql .= ' AND mc.id_shop = '.(int)$id_shop;

		$sql .= ' AND c.`iso_code` IN ("'.implode('", "', $this->supported_currencies).'")
            ORDER BY c.`name` ASC';

		return Db::getInstance()->executeS($sql);
	}

	public function checkSupportedCurrencies()
	{
		$currencies = $this->getSupportedCurrencies();

		if (! empty($currencies))
			return true;

		return false;
	}

	public function getSupportedCurrency()
	{
		$currencies = $this->getSupportedCurrencies();

		if (empty($currencies))
			return null;

		return $currencies[0]['id_currency'];
	}

	public function isSupportedCurrency($currency_id)
	{
		$currencies = $this->getSupportedCurrencies();

		foreach ($currencies as $currency)
		{
			if ($currency['id_currency'] == $currency_id)
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
		$url = 'https://api.cardinity.com/v1/payments/'.$payment_id;
		$method = 'PATCH';

		return $this->sendRequest($url, $method, $data);
	}

	public function sendRequest($url, $method, $data)
	{
		$options = array(
			'consumer_key'    => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret
		);

		OAuthStore::instance('2Leg', $options);

		$request = new OAuthRequester($url, $method, null);

		$oaheader = $request->getAuthorizationHeader();
		$headers = array('Content-Type: application/json', 'Authorization: '.$oaheader);

		$curl_options = array(
			CURLOPT_URL            => $url,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => Tools::jsonEncode($data),
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false
		);

		$response = $request->doRequest(0, $curl_options);

		return Tools::jsonDecode($response['body']);
	}

	public function validateOrderPayment($order)
	{
		global $cookie;

		if (version_compare(_PS_VERSION_, '1.5', '<'))
			$current_state = $order->getCurrentState();
		else
			$current_state = $order->current_state;

		//Logger::addLog("VALIDATING PAYMENT: id = $order->id, valid = $order->valid,  crrent state = $current_state, ", 1, $response->status, null, null, true);

		if ($order->id
			&& $order->module == $this->name
			&& $cookie->id_customer == $order->id_customer
			&& ! $order->valid
			&& $current_state != (int)Configuration::get('PS_OS_CANCELED'))
			return true;

		return false;
	}

	public function savePayment($response, $order_id)
	{
		$id_shop = 1;

		if ((version_compare(_PS_VERSION_, '1.5', '>=')))
			$id_shop = Context::getContext()->shop->id;

		Db::getInstance()->execute('
            INSERT INTO '._DB_PREFIX_.'cardinity (id_shop, id_payment, id_order)
            VALUES ('.$id_shop.', "'.$response->id.'", '.$order_id.')
		');

	}

	public function getPaymentOrder($payment_id)
	{
		return Db::getInstance()->getRow('SELECT id_order FROM '._DB_PREFIX_.'cardinity WHERE id_payment = "'.$payment_id.'"');
	}

	public function approveOrderPayment($order, $transactionLogData = false)
	{
		$history = new OrderHistory();
		$history->id_order = $order->id;
		$history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $order->id);
		$history->addWithemail(true, array(
			'order_name' => $order->id,
		));

		
        if($transactionLogData){
            $this->addTransactionHistory($transactionLogData);
        }
	}

	public function addTransactionHistory($data){

        $currentFilename = "transactions-".date("Y-n").'.log';
       

        $currentDir = dirname(__FILE__);

        $transactionFile = $currentDir .DIRECTORY_SEPARATOR . ".."  .DIRECTORY_SEPARATOR. ".."  .DIRECTORY_SEPARATOR .'log'.DIRECTORY_SEPARATOR.$currentFilename;
        //$transactionFile = WP_CONTENT_DIR.'/uploads/wc-logs/cardinity-transactions.log';

        $message = "";
        if (!file_exists($transactionFile)) {
         $message = "OrderID :: PaymentID :: 3dsVersion :: Amount :: Status\n";
        }       
        $message .= implode(" :: ",$data);
        
        file_put_contents($transactionFile, $message."\n", FILE_APPEND);

    }

	public function hookPaymentReturn($params)
	{
		global $smarty;

		$state = $params['objOrder']->getCurrentState();
		if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK'))))
			$smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status'       => 'ok',
				'id_order'     => $params['objOrder']->id
			));
		else
			$this->smarty->assign('status', 'failed');

		return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
	}

	/* Payment form for PS 1.4 */
	public function execPayment($cart)
	{
		global $smarty;

		if (! $this->active)
			return;

		if (! $this->checkSupportedCurrencies())
			Tools::redirectLink(__PS_BASE_URI__.'order.php');

		if (! $this->isSupportedCurrency($cart->id_currency))
			$this->updateCurrency();

		$currency = new Currency($cart->id_currency);

		$smarty->assign(array(
			'nbProducts'       => $cart->nbProducts(),
			'customerCurrency' => $cart->id_currency,
			'currencies'       => $this->getSupportedCurrencies(),
			'currency'         => $currency,
			'total'            => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path'        => $this->_path,
			'this_path_bw'     => $this->_path,
			'this_path_ssl'    => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		Tools::addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/cardinity14.css', 'all');

		return $this->display(__FILE__, 'views/templates/front/payment_execution.tpl');
	}

	/* Used only in PS 1.4. */
	private function updateCurrency()
	{
		global $cookie;

		$cookie->id_currency = $this->getSupportedCurrency();

		Tools::redirect(_MODULE_DIR_.$this->name.'/payment.php');
	}

	public function processPayment()
	{
		global $smarty;

		$order_id = (int)Tools::getValue('order_id');
		$order = new Order($order_id);
		$currency = new Currency($order->id_currency);
		$cart = new Cart($order->id_cart);
		$errors = array();

		if ($this->validateOrderPayment($order))
		{
			if (Tools::getValue('make_payment'))
			{
				$customer = new Customer($cart->id_customer);
				$address = new Address((int)$cart->id_address_invoice);
				$country = new Country((int)$address->id_country);
				$total = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

				// Convert order ID to string with leading 0 because
				// Cardinity accepts only IDs with minimum length of 2
				$order_id_length = Tools::strlen((string)$order->id);
				$order_id_length++;
				$order_id_string = sprintf('%0'.$order_id_length.'d', $order->id);

				$response = $this->makePayment(array(
					'amount'             => $total,
					'currency'           => $currency->iso_code,
					'order_id'           => $order_id_string,
					'country'            => $country->iso_code,
					'payment_method'     => 'card',
					//'description'        => '3d-pass',
					'payment_instrument' => array(
						'pan'       => strip_tags(str_replace(' ', '', Tools::getValue('card_pan'))),
						'exp_year'  => (int)Tools::getValue('expiration_year'),
						'exp_month' => (int)Tools::getValue('expiration_month'),
						'cvc'       => strip_tags(trim(Tools::getValue('cvc'))),
						'holder'    => strip_tags(trim(Tools::getValue('card_holder')))
					)
				));

				if ($response->status == 'approved')
				{
					$transactionData = array(
						$order->id,
						$response->id,
						'none',
						$response->amount ." ". $response->currency,
						'approved'
					);

					$this->approveOrderPayment($order, $transactionData);

					Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id
						.'&id_module='.$this->id.'&id_order='.$this->currentOrder.'&key='.$customer->secure_key);
				} elseif ($response->status == 'pending')
				{
					$this->savePayment($response, $order->id);

					$url = $response->authorization_information->url;
					$data = $response->authorization_information->data;
					$url_params = array(
						'url'        => urlencode($url),
						'data'       => urlencode($data),
						//'data' => '3d-fail',
						'payment_id' => urlencode($response->id)
					);

					Tools::redirect(_MODULE_DIR_.$this->name.'/redirect.php?'.http_build_query($url_params));
				}

				// Validation errors are returned in errors array
				if (isset($response->errors))
					foreach ($response->errors as $error)
						$errors[] = Tools::ucfirst($error->message);
				elseif ($response->status == 'declined' && isset($response->error))
					$errors[] = $response->error;
				elseif ($response->status == 402)
					$errors[] = $response->detail;
				elseif ($response->status >= 400)
				{
					$errors[] = $this->l('Payment failed.', 'process');

					if (version_compare(_PS_VERSION_, '1.6', '>='))
						PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
					else
						Logger::addLog($response->detail, 4, $response->status, null, null, true);
				}
			}

			$smarty->assign(array(
				'currency'      => $currency,
				'orderId'       => $order->id,
				'total'         => $cart->getOrderTotal(true, Cart::BOTH),
				'this_path'     => $this->_path,
				'this_path_bw'  => $this->_path,
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
				'errors'        => $errors,
				'input'         => $_POST
			));

			return $this->display(__FILE__, 'views/templates/front/payment_process.tpl');
		}
		else
			return $this->display(__FILE__, 'views/templates/front/payment_process_error.tpl');
	}

	public function showCVVDescription()
	{
		global $smarty;

		$smarty->assign(array(
			'this_path'     => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'views/templates/front/cvv.tpl');
	}

	public function hookHeader()
	{
		if (! $this->isModuleAvailable())
			return;

		Tools::addCSS($this->_path.'views/css/cardinity.css', 'all');
		Tools::addCSS($this->_path.'views/css/cardinity14.css', 'all');
		Tools::addCSS($this->_path.'views/css/jquery.fancybox-1.3.4.css', 'all');
		Tools::addJS($this->_path.'views/js/jquery/jquery.fancybox-1.3.4.js');
		Tools::addJS($this->_path.'views/js/cardinity.js');
	}

	private function isModuleAvailable()
	{
		$fileName = basename($_SERVER['SCRIPT_FILENAME']);

		if (! in_array($fileName, array('payment.php', 'process.php', 'callback.php')))
			return false;

		return true;
	}

	public function redirect()
	{
		global $smarty;

		$payment_id = urldecode(Tools::getValue('payment_id'));
		$url = urldecode(Tools::getValue('url'));
		$data = urldecode(Tools::getValue('data'));
		$callback_url = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/callback.php';
		$order = $this->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);

		if ($this->validateOrderPayment($order))
		{
			$smarty->assign(array(
				'cardinityUrl'         => $url,
				'cardinityData'        => $data,
				'cardinityCallbackUrl' => $callback_url,
				'cardinityPaymentId'   => $payment_id
			));

			return $this->display(__FILE__, 'views/templates/front/redirect.tpl');
		}
		else
			return $this->display(__FILE__, 'views/templates/front/payment_process_error.tpl');
	}

	public function callback()
	{
		global $smarty;

		$payment_id = Tools::getValue('MD');
		$pares = Tools::getValue('PaRes');
		$order = $this->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);
		$cart = new Cart($order->id_cart);
		$customer = new Customer($cart->id_customer);
		$currency = new Currency($order->id_currency);
		$data = array('authorize_data' => $pares);
		$errors = array();

		if ($this->validateOrderPayment($order))
		{
			$response = $this->finalizePayment($payment_id, $data);

			if ($response->status == 'approved')
			{
				$transactionData = array(
					$order->id,
					$response->id,
					'v1',
					$response->amount ." ". $response->currency,
					'approved'
				);

				$this->approveOrderPayment($order, $transactionData);

				Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='
					.$this->id.'&id_order='.$this->currentOrder.'&key='.$customer->secure_key);
			}

			// Validation errors are returned in errors array
			if ($response->status == 'declined' && isset($response->error))
				$errors[] = $response->error;
			elseif ($response->status == 402)
				$errors[] = $response->detail;
			elseif ($response->status >= 400)
			{
				$errors[] = $this->l('Payment failed.', 'callback');
				Logger::addLog($response->detail, 4, $response->status, null, null, true);
			}

			$smarty->assign(array(
				'currency'      => $currency,
				'orderId'       => $order->id,
				'total'         => $cart->getOrderTotal(true, Cart::BOTH),
				'this_path'     => $this->_path,
				'this_path_bw'  => $this->_path,
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
				'errors'        => $errors
			));

			return $this->display(__FILE__, 'views/templates/front/payment_process.tpl');
		}
		else
			return $this->display(__FILE__, 'views/templates/front/payment_process_error.tpl');
	}
}
