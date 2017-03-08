<?php

class CardinityCvvModuleFrontController extends ModuleFrontController {

	public $ssl                  = true;
	public $display_column_left  = false;
	public $display_column_right = false;
	public $display_header       = false;
	public $display_footer       = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->context->smarty->assign(array(
			'this_path'     => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
			'globalCSS'     => _THEME_CSS_DIR_.'global.css'
		));

		$this->setTemplate('cvv.tpl');
	}
}