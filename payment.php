<?php

$use_ssl = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/cardinity.php');

if (! $cookie->isLogged(true))
	Tools::redirect('authentication.php?back=order.php');
elseif (! Customer::getAddressesTotalById((int)$cookie->id_customer))
	Tools::redirect('address.php?back=order.php?step=1');

$cardinity = new Cardinity();

echo $cardinity->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');