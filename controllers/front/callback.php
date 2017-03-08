<?php

class CardinityCallbackModuleFrontController extends ModuleFrontController {

	public $ssl                  = true;
	public $display_column_left  = false;
	public $display_column_right = false;
	public $errors               = array();

	public function postProcess()
	{
		$payment_id = Tools::getValue('MD');
		$pares = Tools::getValue('PaRes');
		$order = $this->module->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);
		$cart = new Cart($order->id_cart);
		$customer = new Customer($cart->id_customer);
		$currency = new Currency($order->id_currency);
		$data = array('authorize_data' => $pares);

		if ($this->module->validateOrderPayment($order))
		{
			$response = $this->module->finalizePayment($payment_id, $data);

			if ($response->status == 'approved')
			{
				$this->module->approveOrderPayment($order);

				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id
					.'&id_module='.$this->module->id.'&id_order='.$order->id.'&key='.$customer->secure_key);
			}

			// Validation errors are returned in errors array
			if ($response->status == 'declined' && isset($response->error))
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