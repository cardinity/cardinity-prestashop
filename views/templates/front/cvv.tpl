{*
* @author       Cardinity
* @link         https://cardinity.com
* @license      The MIT License (MIT)
*}
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{l s='What is CVV?' mod='cardinity'}</title>
    {if isset($globalCSS)}
        <link rel="stylesheet" href="{$globalCSS|escape:'htmlall':'UTF-8'}" />
    {/if}
</head>
<body style="display: inline-block; height: auto;">
<div class="content">
    <p>{l s='For your safety and security, we require that you enter your card verification number, if one is available. The verification number is a 3 or 4-digit number printed on your card.' mod='cardinity'}</p>

    <p>{l s='If you are using a Visa, Mastercard, or Discover card, it is a 3 digit number that appears to the right of your card number (see below):' mod='cardinity'}</p>

    <p><img src="{$this_path|escape:'htmlall':'UTF-8'}/views/img/cv_card.gif" width="259" height="181" alt="{l s='CVV' mod='cardinity'}"></p>

    <p>{l s='If you are using an American Express card, the verification number is a 4 digit number that appears on the front of your card, above and either on the left or right of the card number (see below):' mod='cardinity'}</p>

    <p><img src="{$this_path|escape:'htmlall':'UTF-8'}/views/img/cv_amex_card.gif" alt="{l s='CVV on AMEX' mod='cardinity'}" width="259" height="181">
    </p>
</div>

</body>
</html>