<?php

class CardinityRedirectModuleFrontController extends ModuleFrontController {

	public $ssl                 = true;
	public $display_column_left = false;

	public function initContent()
	{
		parent::initContent();

		$payment_id = urldecode(Tools::getValue('payment_id'));
		$url = urldecode(Tools::getValue('url'));
		$data = urldecode(Tools::getValue('data'));
		$link = new Link();
		$callback_url = $link->getModuleLink('cardinity', 'callback');
		$order = $this->module->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);

		if ($this->module->validateOrderPayment($order))
		{
			$this->context->smarty->assign(array(
				'cardinityUrl'         => $url,
				'cardinityData'        => $data,
				'cardinityCallbackUrl' => $callback_url,
				'cardinityPaymentId'   => $payment_id
			));

			$this->setTemplate('redirect.tpl');
		}
		else
			$this->setTemplate('payment_process_error.tpl');
	}
}