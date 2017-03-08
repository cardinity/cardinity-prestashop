<?php

$use_ssl = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/cardinity.php');

$cardinity = new Cardinity();

if ($cart->id_customer == 0
	|| $cart->id_address_delivery == 0
	|| $cart->id_address_invoice == 0
	|| ! $cardinity->active
	|| ! $cardinity->checkSupportedCurrencies())
	Tools::redirect('index.php?controller=order&step=1');

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
	die(Tools::displayError('This payment method is not available.'));

$customer = new Customer($cart->id_customer);

if (! Validate::isLoadedObject($customer))
	Tools::redirect('index.php?controller=order&step=1');

$currency = new Currency($cookie->id_currency);
$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

$cardinity->validateOrder($cart->id, Configuration::get('CARDINITY_PENDING'), $total, $cardinity->displayName, null, null, $currency->id);

$order_id = $cardinity->currentOrder;

Tools::redirect(_MODULE_DIR_.$cardinity->name.'/process.php?order_id='.$order_id);