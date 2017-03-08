<?php

class CardinityProcessModuleFrontController extends ModuleFrontController {

	public $ssl                  = true;
	public $display_column_left  = false;
	public $display_column_right = false;
	public $errors               = array();
	private $order                = null;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$order_id = (int)Tools::getValue('order_id');
		$this->order = new Order($order_id);
		$currency = new Currency($this->order->id_currency);
		$cart = new Cart($this->order->id_cart);

		if ($this->module->validateOrderPayment($this->order))
		{
			if (Tools::getValue('make_payment'))
			{
				$customer = new Customer($cart->id_customer);
				$address = new Address((int)$cart->id_address_invoice);
				$country = new Country((int)$address->id_country);
				$total = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');

				// Convert order ID to string with leading 0 because
				// Cardinity accepts only IDs with minimum length of 2
				$order_id_length = Tools::strlen((string)$this->order->id);
				$order_id_length++;
				$order_id_string = sprintf('%0'.$order_id_length.'d', $this->order->id);

				$response = $this->module->makePayment(array(
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
					$this->module->approveOrderPayment($this->order);

					Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id
						.'&id_module='.$this->module->id.'&id_order='.$this->order->id.'&key='.$customer->secure_key);
				} elseif ($response->status == 'pending')
				{
					$this->module->savePayment($response, $this->order->id);

					$url = $response->authorization_information->url;
					$data = $response->authorization_information->data;
					$link = new Link();
					$url_params = array(
						'url'        => urlencode($url),
						'data'       => urlencode($data),
						'payment_id' => urlencode($response->id)
					);

					Tools::redirect($link->getModuleLink('cardinity', 'redirect', $url_params));
				}

				// Validation errors are returned in errors array
				if (isset($response->errors))
					foreach ($response->errors as $error)
						$this->errors[] = Tools::ucfirst($error->message);
				elseif ($response->status == 'declined' && isset($response->error))
					$this->errors[] = $response->error;
				elseif ($response->status == 402)
					$this->errors[] = $response->detail;
				elseif ($response->status >= 400)
				{
					$this->errors[] = $this->module->l('Payment failed.', 'process');
					if (version_compare(_PS_VERSION_, '1.6', '>='))
						PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
					else
						Logger::addLog($response->detail, 4, $response->status, null, null, true);
				}
			}

			$this->context->smarty->assign(array(
				'currency'      => $currency,
				'orderId'       => $this->order->id,
				'total'         => $cart->getOrderTotal(true, Cart::BOTH),
				'this_path'     => $this->module->getPathUri(),
				'this_path_bw'  => $this->module->getPathUri(),
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
				'errors'        => $this->errors,
				'input'         => $_POST
			));

			$this->context->controller->addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/cardinity.css', 'all');

			if (version_compare(_PS_VERSION_, '1.6', '>='))
				$this->context->controller->addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/responsive.css', 'all');
			elseif (version_compare(_PS_VERSION_, '1.5'))
			{
				$this->context->controller->addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/cardinity15.css', 'all');
				$this->context->controller->addJqueryPlugin('fancybox');
			}

			$this->context->controller->addJS(__PS_BASE_URI__.'modules/cardinity/views/js/jquery/jquery.payment.js');
			$this->context->controller->addJS(__PS_BASE_URI__.'modules/cardinity/views/js/cardinity.js');

			$this->setTemplate('payment_process.tpl');
		}
		else
			$this->setTemplate('payment_process_error.tpl');
	}
}
