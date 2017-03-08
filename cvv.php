<?php

$use_ssl = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/cardinity.php');

$cardinity = new Cardinity();

echo $cardinity->showCVVDescription();