<?php

class CardinityCallbackModuleFrontController extends ModuleFrontController {

	public $ssl                  = true;
	public $display_column_left  = false;
	public $display_column_right = false;
	public $errors               = array();

	public function postProcess()
	{



		$callbackParams = Tools::getAllValues();

		if(isset($callbackParams['MD'])){

			//its 3ds v1 callback
			$payment_id = Tools::getValue('MD');
			$pares = Tools::getValue('PaRes');
			$data = array('authorize_data' => $pares);

		}else{

			//its 3ds v2 callback
			$payment_id = Tools::getValue('threeDSSessionData');
			$cres = Tools::getValue('cres');
			$data = array('cres' => $cres);
		}

		$order = $this->module->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);
		$cart = new Cart($order->id_cart);
		$customer = new Customer($cart->id_customer);
		$currency = new Currency($order->id_currency);


		if ($this->module->validateOrderPayment($order))
		{
			$response = $this->module->finalizePayment($payment_id, $data);

			if ($response->status == 'approved')
			{
				$this->module->approveOrderPayment($order);

				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id
					.'&id_module='.$this->module->id.'&id_order='.$order->id.'&key='.$customer->secure_key);

			}elseif($response->status == 'pending' && $response->authorization_information){
				//need v1 auth fallback

				$url = $response->authorization_information->url;
				$data = $response->authorization_information->data;
				$link = new Link();
				$url_params = array(
					'url'        => urlencode($url),
					'data'       => urlencode($data),
					'payment_id' => urlencode($response->id),
					'is_v2' => urlencode(0),
				);

				Tools::redirect($link->getModuleLink('cardinity', 'redirect', $url_params));

			}elseif ($response->status == 'declined' && isset($response->error))
				// Validation errors are returned in errors array
				$this->errors[] = $response->error;
			elseif ($response->status == 402)
				$this->errors[] = $response->detail;
			elseif ($response->status >= 400)
			{
				$this->errors[] = $this->module->l('Payment failed.', 'callback');
				PrestaShopLogger::addLog($response->detail, 4, $response->status, null, null, true);
			}

			$this->context->smarty->assign(array(
				'currency'      => $currency,
				'orderId'       => $order->id,
				'total'         => $cart->getOrderTotal(true, Cart::BOTH),
				'this_path'     => $this->module->getPathUri(),
				'this_path_bw'  => $this->module->getPathUri(),
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
				'errors'        => $this->errors
			));

			$this->addCSS('modules/cardinity/views/css/cardinity.css', 'all');
			$this->addJS('modules/cardinity/views/js/cardinity.js');

			$this->setTemplate('payment_process.tpl');
		}
		else
			$this->setTemplate('payment_process_error.tpl');
	}
}
