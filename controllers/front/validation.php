<?php

class CardinityValidationModuleFrontController extends ModuleFrontController {

	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0
			|| $cart->id_address_delivery == 0
			|| $cart->id_address_invoice == 0
			|| ! $this->module->active
			|| ! $this->module->checkSupportedCurrencies())
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;

		foreach (Module::getPaymentModules() as $module)
		{
			if ($module['name'] == 'cardinity')
			{
				$authorized = true;
				break;
			}
		}

		if (! $authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);

		if (! Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = Context::getContext()->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		$this->module->validateOrder($cart->id, Configuration::get('CARDINITY_PENDING'), $total, $this->module->displayName, null, null, $currency->id);

		$order_id = $this->module->currentOrder;

		$link = new Link();

		Tools::redirect($link->getModuleLink('cardinity', 'process', array('order_id' => $order_id)));
	}
}