<?php

$use_ssl = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/cardinity.php');

$cardinity = new Cardinity();

echo $cardinity->callback();

include_once(dirname(__FILE__).'/../../footer.php');