<?php

class CardinityPaymentModuleFrontController extends ModuleFrontController {

	public $ssl                  = true;
	public $display_column_left  = false;
	public $display_column_right = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		if (! $this->module->checkSupportedCurrencies())
			Tools::redirect('index.php?controller=order');

		$cart = $this->context->cart;
		$currency = Context::getContext()->currency;

		if (! $this->module->isSupportedCurrency($cart->id_currency))
			$this->updateCurrency();

		$this->context->smarty->assign(array(
			'nbProducts'       => $cart->nbProducts(),
			'customerCurrency' => $cart->id_currency,
			'currencies'       => $this->module->getSupportedCurrencies(),
			'currency'         => $currency,
			'total'            => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path'        => $this->module->getPathUri(),
			'this_path_bw'     => $this->module->getPathUri(),
			'this_path_ssl'    => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->context->controller->addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/cardinity.css', 'all');

		if (version_compare(_PS_VERSION_, '1.6', '>='))
			$this->addCSS('modules/cardinity/views/css/responsive.css', 'all');
		elseif (version_compare(_PS_VERSION_, '1.5'))
			$this->context->controller->addCSS(__PS_BASE_URI__.'modules/cardinity/views/css/cardinity15.css', 'all');

		$this->setTemplate('payment_execution.tpl');
	}

	private function updateCurrency()
	{
		$this->context->cookie->id_currency = $this->module->getSupportedCurrency();
		$link = new Link();

		Tools::redirect($link->getModuleLink('cardinity', 'payment'));
	}
}
