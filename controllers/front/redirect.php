<?php

class CardinityRedirectModuleFrontController extends ModuleFrontController {

	public $ssl                 = true;
	public $display_column_left = false;

	public function initContent()
	{
		parent::initContent();

		//3ds Requested
		$is_v2 = urldecode(Tools::getValue('is_v2'));
		$payment_id = urldecode(Tools::getValue('payment_id'));


		$order = $this->module->getPaymentOrder($payment_id);
		$order = new Order($order['id_order']);

		if ($this->module->validateOrderPayment($order))
		{

			if($is_v2 == 0){

				$url = urldecode(Tools::getValue('url'));
				$data = urldecode(Tools::getValue('data'));


				$link = new Link();
				$callback_url = $link->getModuleLink('cardinity', 'callback');

				$this->context->smarty->assign(array(
					'cardinityUrl'         => $url,
					'cardinityData'        => $data,
					'cardinityCallbackUrl' => $callback_url,
					'cardinityPaymentId'   => $payment_id
				));

				$this->setTemplate('redirect.tpl');
			}else{

				Logger::addLog("v2 form processing", 3, null, null, null, true);

				$acs_url = urldecode(Tools::getValue('acs_url'));
				$creq = urldecode(Tools::getValue('creq'));

				$this->context->smarty->assign(array(
					'cardinityAcsUrl'         => $acs_url,
					'cardinityCreqData'        => $creq,
					'cardinityThreeDSSessionData'   => $payment_id
				));

				$this->setTemplate('redirect_3dsv2.tpl');
			}

		}
		else
			$this->setTemplate('payment_process_error.tpl');
	}
}
