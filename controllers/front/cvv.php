<?php
/**
 * Cardinity for Prestashop 1.7.x
 *
 * @author    Cardinity
 * @copyright 2017
 * @license   The MIT License (MIT)
 * @link      https://cardinity.com
 */

class CardinityCvvModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;
    public $display_header = false;
    public $display_footer = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->context->smarty->assign(array(
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
            'globalCSS' => _THEME_CSS_DIR_ . 'global.css'
        ));

        $this->setTemplate('module:cardinity/views/templates/front/cvv.tpl');
    }
}
